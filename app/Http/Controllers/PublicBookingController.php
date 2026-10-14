<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use App\Services\BookingService;
use App\Services\CinemaSettings;
use App\Services\ScheduleResolver;
use App\Services\SnackOrderService;
use App\Support\WhatsAppNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    public function create(
        Session $session,
        CinemaSettings $settings,
        SnackOrderService $snackOrders,
    ): View|RedirectResponse {
        $session->load('film');
        $session->loadSum('occupiedBookings as occupied_seats', 'seats_reserved');
        $deadline = $session->startsAt()->subMinutes($settings->integer('booking_cutoff_minutes', 30));

        if (
            $session->status !== 'tersedia'
            || $session->is_private
            || $session->remainingSeats() === 0
            || now()->greaterThanOrEqualTo($deadline)
        ) {
            return redirect()
                ->route('schedule.date', ['date' => $session->session_date])
                ->with('error', 'Sesi ini sudah tidak bisa dibooking. Pilih jadwal lain, ya.');
        }

        $unitPrice = match (app(ScheduleResolver::class)->dayTypeFor($session->startsAt())) {
            'libur' => $settings->integer('holiday_price', 6000),
            'weekend' => $settings->integer('weekend_price', 6000),
            default => $settings->integer('weekday_price', 5000),
        };

        return view('public.booking-form', [
            'session' => $session,
            'films' => $session->film_id
                ? collect([$session->film])->filter()
                : Film::query()->where('is_active', true)->orderBy('title')->get(),
            'unitPrice' => $unitPrice,
            'privatePrice' => $settings->integer('private_price', 36000),
            'paymentMethods' => $settings->enabledPaymentMethods(),
            'paymentInstructions' => $settings->string('payment_instructions'),
            'occupiedSeats' => $session->seatCapacity() - $session->remainingSeats(),
            'snackCategories' => $this->snackMenu($snackOrders, $session),
            'snacksEnabled' => $snackOrders->enabled(),
            'snackMaxQty' => $settings->integer('snack_max_qty_per_item', 10),
        ]);
    }

    public function store(
        StoreBookingRequest $request,
        Session $session,
        BookingService $bookingService,
    ): RedirectResponse {
        $booking = $bookingService->create(
            $session,
            $request->validated(),
            $request->file('payment_proof'),
        );

        return redirect()->route('booking.success', ['code' => $booking->code]);
    }

    public function success(string $code, CinemaSettings $settings): View
    {
        $booking = Booking::query()
            ->where('code', strtoupper($code))
            ->with(['session', 'film', 'items' => fn ($query) => $query->where('status', '!=', 'dibatalkan')])
            ->firstOrFail();

        return view('public.booking-success', [
            'booking' => $booking,
            'whatsAppUrl' => $this->whatsAppUrl($booking, $settings),
        ]);
    }

    public function lookup(
        Request $request,
        CinemaSettings $settings,
        SnackOrderService $snackOrders,
    ): View|RedirectResponse {
        $data = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:20'],
            'whatsapp' => ['required', 'string', 'max:24'],
        ], [
            'code.required' => 'Kode booking wajib diisi.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
        ])->validate();

        $whatsapp = WhatsAppNumber::normalize($data['whatsapp']);

        if (! WhatsAppNumber::isValid($whatsapp)) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Masukkan nomor WhatsApp Indonesia yang valid.',
            ]);
        }

        $booking = Booking::query()
            ->where('code', strtoupper(trim($data['code'])))
            ->where('whatsapp', $whatsapp)
            ->with(['session', 'film', 'items.menuItem'])
            ->first();

        if ($booking === null) {
            throw ValidationException::withMessages([
                'code' => 'Kode booking atau nomor WhatsApp tidak cocok.',
            ]);
        }

        return $this->statusView($booking, $settings, $snackOrders);
    }

    public function cancel(
        Request $request,
        string $code,
        BookingService $bookingService,
    ): RedirectResponse {
        $data = $request->validate([
            'whatsapp' => ['required', 'string', 'max:24'],
        ], [
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi untuk membatalkan booking.',
        ]);

        $bookingService->cancel($code, $data['whatsapp']);

        return redirect()
            ->route('booking.lookup.form')
            ->with('status', 'Booking berhasil dibatalkan.');
    }

    public function lookupForm(): View
    {
        return view('public.booking-status');
    }

    private function snackMenu(SnackOrderService $snackOrders, Session $session)
    {
        return MenuCategory::query()
            ->where('is_active', true)
            ->whereHas('items', fn ($query) => $query->where('is_active', true))
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (MenuCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'items' => $category->items->map(fn (MenuItem $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'prep_minutes' => $item->prep_minutes,
                    'photo' => $item->photo_path ? asset('storage/'.$item->photo_path) : null,
                    'available' => $item->canBeOrdered()
                        && now()->lessThan($snackOrders->deadlineFor($session, $item->prep_minutes)),
                    'unavailable_reason' => ! $item->is_available
                        ? 'Habis'
                        : (now()->greaterThanOrEqualTo($snackOrders->deadlineFor($session, $item->prep_minutes))
                            ? 'Batas pesan lewat'
                            : 'Menu tidak aktif'),
                ])->values(),
            ])->values();
    }

    private function statusView(
        Booking $booking,
        CinemaSettings $settings,
        SnackOrderService $snackOrders,
    ): View {
        $activeItems = $booking->items->where('status', '!=', 'dibatalkan')->values();
        $items = $booking->status === 'dibatalkan'
            ? $booking->items
            : $activeItems;
        $booking->setRelation('items', $items);
        $longestPrep = (int) $activeItems
            ->map(fn ($item): int => (int) ($item->menuItem?->prep_minutes ?? 0))
            ->max();
        $deadline = $activeItems->isNotEmpty()
            ? $snackOrders->deadlineFor($booking->session, $longestPrep)
            : null;
        $menu = $this->snackMenu($snackOrders, $booking->session);

        return view('public.booking-status', [
            'booking' => $booking,
            'snackCategories' => $menu,
            'snacksEnabled' => $snackOrders->enabled(),
            'snackMaxQty' => $settings->integer('snack_max_qty_per_item', 10),
            'snackDeadline' => $deadline,
            'snackLocked' => $booking->status === 'dibatalkan'
                || ($deadline !== null && now()->greaterThanOrEqualTo($deadline)),
            'whatsAppUrl' => $this->whatsAppUrl($booking, $settings),
            'snackWhatsAppUrl' => $this->snackWhatsAppUrl($booking, $settings),
        ]);
    }

    private function snackWhatsAppUrl(Booking $booking, CinemaSettings $settings): ?string
    {
        $adminWhatsApp = preg_replace('/\D+/', '', $settings->string('admin_whatsapp'));

        if ($adminWhatsApp === '' || ! WhatsAppNumber::isValid($adminWhatsApp)) {
            return null;
        }

        $items = $booking->items
            ->where('status', '!=', 'dibatalkan')
            ->map(fn ($item): string => $item->name_snapshot.' × '.$item->qty.($item->note ? ' ('.$item->note.')' : ''));
        $message = implode("\n", [
            'Halo, saya ingin mengajukan perubahan pesanan jajan.',
            'Kode booking: '.$booking->code,
            'Nama: '.$booking->customer_name,
            'Jadwal: '.$booking->session->session_date.' · '.substr((string) $booking->session->start_time, 0, 5).' WIB',
            'Pesanan jajan saat ini: '.($items->isNotEmpty() ? $items->implode(', ') : 'Belum ada jajan'),
            'Saya ingin mengubah pesanan jajan: [tuliskan perubahan yang diinginkan].',
        ]);

        return 'https://wa.me/'.$adminWhatsApp.'?text='.rawurlencode($message);
    }

    private function whatsAppUrl(Booking $booking, CinemaSettings $settings): ?string
    {
        $adminWhatsApp = preg_replace('/\D+/', '', $settings->string('admin_whatsapp'));

        if ($adminWhatsApp === '') {
            return null;
        }

        $message = implode("\n", [
            'Halo, saya mau konfirmasi booking Home Cinema.',
            'Kode: '.$booking->code,
            'Film: '.($booking->film?->title ?? 'Film sesi'),
            'Tanggal: '.$booking->session->session_date,
            'Jam: '.substr((string) $booking->session->start_time, 0, 5),
            $booking->booking_type === 'private'
                ? 'Jumlah: Sewa Private (6 kursi)'
                : 'Jumlah orang: '.$booking->pax,
            ...$booking->items
                ->where('status', '!=', 'dibatalkan')
                ->map(fn ($item): string => 'Jajan: '.$item->name_snapshot.' × '.$item->qty.($item->note ? ' ('.$item->note.')' : ''))
                ->all(),
            'Tiket: Rp '.number_format($booking->ticket_total, 0, ',', '.'),
            'Jajan: Rp '.number_format($booking->snack_total, 0, ',', '.'),
            'Total: Rp '.number_format($booking->total_price, 0, ',', '.'),
        ]);

        return 'https://wa.me/'.$adminWhatsApp.'?text='.rawurlencode($message);
    }
}
