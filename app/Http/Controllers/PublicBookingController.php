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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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
            'paymentMethods' => $settings->availablePaymentMethodsForSession($session),
            'paymentInstructions' => $settings->string('payment_instructions'),
            'paymentTimeLimit' => $settings->paymentTimeLimitMinutes(),
            'onlinePaymentCutoff' => $settings->onlinePaymentCutoffMinutes(),
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
        );

        $request->session()->put('verified_booking_'.$booking->code, true);

        if ($booking->payment_method === 'di_tempat') {
            return redirect()->route('booking.ticket', ['code' => $booking->code]);
        }

        return redirect()->route('booking.payment', ['code' => $booking->code]);
    }

    public function paymentView(
        Request $request,
        string $code,
        CinemaSettings $settings,
        BookingService $bookingService,
    ): View|RedirectResponse {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->with(['session', 'film', 'items' => fn ($query) => $query->where('status', '!=', 'dibatalkan')])
            ->firstOrFail();

        $this->authorizeBookingAccess($request, $booking);
        $booking = $bookingService->expireIfOverdue($booking);

        if ($booking->payment_method === 'di_tempat') {
            return redirect()->route('booking.ticket', ['code' => $booking->code]);
        }

        if ($booking->status !== 'menunggu_pembayaran') {
            return redirect()->route('booking.ticket', ['code' => $booking->code]);
        }

        return view('public.payment', [
            'booking' => $booking,
            'whatsAppUrl' => $this->paymentWhatsAppUrl($booking, $settings),
            'hasQrisImage' => $settings->hasValidQrisImage(),
            'qrisMerchantName' => $settings->qrisMerchantName(),
            'qrisPaymentNotes' => $settings->qrisPaymentNotes(),
            'qrisVersion' => $settings->qrisImageVersion(),
            'transferAccounts' => $settings->transferAccounts(),
            'transferPaymentNotes' => $settings->transferPaymentNotes(),
            'maxPaymentProofKb' => $settings->maxPaymentProofKb(),
        ]);
    }

    public function ticketView(
        Request $request,
        string $code,
        CinemaSettings $settings,
        BookingService $bookingService,
    ): View|RedirectResponse {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->with([
                'session.film',
                'film',
                'items.menuItem',
            ])
            ->firstOrFail();

        $this->authorizeBookingAccess($request, $booking);
        $booking = $bookingService->expireIfOverdue($booking);

        if ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran' && ! $booking->isExpired()) {
            return redirect()->route('booking.payment', ['code' => $booking->code]);
        }

        $activeItems = $booking->items->where('status', '!=', 'dibatalkan')->values();
        $items = $booking->status === 'dibatalkan'
            ? $booking->items
            : $activeItems;
        $booking->setRelation('items', $items);
        $longestPrep = (int) $activeItems
            ->map(fn ($item): int => (int) ($item->menuItem?->prep_minutes ?? 0))
            ->max();
        $deadline = $activeItems->isNotEmpty()
            ? app(SnackOrderService::class)->deadlineFor($booking->session, $longestPrep)
            : null;
        $snackLocked = $booking->status === 'dibatalkan'
            || ($deadline !== null && now()->greaterThanOrEqualTo($deadline));

        $cancellationCutoff = $settings->cancellationCutoffHours();
        $canCancel = ! in_array($booking->status, ['dibatalkan', 'kedaluwarsa', 'hadir', 'tidak_hadir'], true)
            && now()->lessThanOrEqualTo($booking->session->startsAt()->subHours($cancellationCutoff));

        return view('public.ticket', [
            'booking' => $booking,
            'whatsAppUrl' => $this->ticketWhatsAppUrl($booking, $settings),
            'snackWhatsAppUrl' => $this->snackWhatsAppUrl($booking, $settings),
            'snackLocked' => $snackLocked,
            'canCancel' => $canCancel,
            'cancellationCutoffHours' => $cancellationCutoff,
        ]);
    }

    public function statusJson(
        Request $request,
        string $code,
        BookingService $bookingService,
    ): JsonResponse {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->firstOrFail();

        $this->authorizeBookingAccess($request, $booking);
        $booking = $bookingService->expireIfOverdue($booking);

        $statusLabels = [
            'menunggu_pembayaran' => 'Menunggu pembayaran',
            'menunggu_konfirmasi' => 'Menunggu konfirmasi',
            'terkonfirmasi' => 'Terkonfirmasi',
            'kedaluwarsa' => 'Kedaluwarsa',
            'hadir' => 'Tiket sudah dipakai',
            'tidak_hadir' => 'Tidak hadir',
            'dibatalkan' => 'Dibatalkan',
        ];

        $bannerText = match ($booking->status) {
            'menunggu_konfirmasi' => $booking->payment_method === 'di_tempat'
                ? 'Booking diterima. Bayar tunai di lokasi sebelum film mulai. Admin akan mengonfirmasi lewat WhatsApp.'
                : 'Bukti bayar diterima. Admin akan memeriksa dan mengonfirmasi tiketmu.',
            'terkonfirmasi' => 'Tiket terkonfirmasi. Tunjukkan kode ini saat datang.',
            'kedaluwarsa' => 'Batas waktu pembayaran habis dan kursi telah dilepaskan kembali.',
            'dibatalkan' => 'Booking ini telah dibatalkan.',
            'hadir' => 'Tiket sudah dipakai.',
            default => '',
        };

        return response()->json([
            'code' => $booking->code,
            'status' => $booking->status,
            'status_label' => $statusLabels[$booking->status] ?? $booking->status,
            'is_final' => in_array($booking->status, ['terkonfirmasi', 'dibatalkan', 'kedaluwarsa', 'hadir', 'tidak_hadir'], true),
            'banner_text' => $bannerText,
            'needs_payment' => $booking->status === 'menunggu_pembayaran' && ! $booking->isExpired(),
        ]);
    }

    public function success(
        Request $request,
        string $code,
        BookingService $bookingService,
    ): RedirectResponse {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->firstOrFail();

        $booking = $bookingService->expireIfOverdue($booking);
        $request->session()->put('verified_booking_'.$booking->code, true);

        if ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran' && ! $booking->isExpired()) {
            return redirect()->route('booking.payment', ['code' => $booking->code]);
        }

        return redirect()->route('booking.ticket', ['code' => $booking->code]);
    }

    public function lookup(
        Request $request,
        BookingService $bookingService,
    ): RedirectResponse {
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
            ->first();

        if ($booking === null) {
            throw ValidationException::withMessages([
                'code' => 'Kode booking atau nomor WhatsApp tidak cocok.',
            ]);
        }

        $booking = $bookingService->expireIfOverdue($booking);
        $request->session()->put('verified_booking_'.$booking->code, true);

        if ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran' && ! $booking->isExpired()) {
            return redirect()->route('booking.payment', ['code' => $booking->code]);
        }

        return redirect()->route('booking.ticket', ['code' => $booking->code]);
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

    public function uploadProof(
        Request $request,
        string $code,
        CinemaSettings $settings,
    ): RedirectResponse {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->firstOrFail();

        if ($booking->payment_method === 'di_tempat') {
            throw ValidationException::withMessages([
                'payment_proof' => 'Metode bayar di tempat tidak memerlukan bukti transfer.',
            ]);
        }

        if ($booking->status === 'kedaluwarsa' || $booking->status === 'dibatalkan') {
            throw ValidationException::withMessages([
                'payment_proof' => 'Booking sudah kedaluwarsa atau dibatalkan.',
            ]);
        }

        $maxKb = $settings->maxPaymentProofKb();
        $maxLabel = $maxKb >= 1024 ? round($maxKb / 1024, 1).' MB' : $maxKb.' KB';

        $data = $request->validate([
            'whatsapp' => ['required', 'string', 'max:24'],
            'payment_proof' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
        ], [
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi untuk verifikasi.',
            'payment_proof.required' => 'Pilih file bukti pembayaran terlebih dahulu.',
            'payment_proof.image' => 'File bukti pembayaran harus berupa gambar.',
            'payment_proof.mimes' => 'Format file bukti pembayaran harus JPG, JPEG, PNG, atau WEBP.',
            'payment_proof.max' => 'Ukuran file bukti pembayaran maksimal '.$maxLabel.'.',
        ]);

        $normalized = WhatsAppNumber::normalize($data['whatsapp']);

        if ($normalized !== $booking->whatsapp) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Nomor WhatsApp tidak cocok dengan nomor pemesan booking ini.',
            ]);
        }

        $disk = Storage::disk('local');
        if ($booking->payment_proof_path && $disk->exists($booking->payment_proof_path)) {
            $disk->delete($booking->payment_proof_path);
        }

        $path = $request->file('payment_proof')->store('payment-proofs', 'local');
        $booking->update([
            'payment_proof_path' => $path,
            'payment_proof_uploaded_at' => now(),
            'status' => 'menunggu_konfirmasi',
            'proof_rejection_note' => null,
        ]);

        $request->session()->put('verified_booking_'.$booking->code, true);

        return redirect()
            ->route('booking.ticket', ['code' => $booking->code])
            ->with('status', 'Bukti bayar terkirim. Admin akan memeriksa dan mengonfirmasi tiketmu.');
    }

    public function showProof(Request $request, string $code): Response
    {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->firstOrFail();

        $this->authorizeBookingAccess($request, $booking);

        if (! $booking->payment_proof_path || ! Storage::disk('local')->exists($booking->payment_proof_path)) {
            abort(404, 'Bukti pembayaran tidak ditemukan.');
        }

        return Storage::disk('local')->response($booking->payment_proof_path);
    }

    public function qrisImage(Request $request, CinemaSettings $settings): Response
    {
        $path = $settings->string('qris_image_path');
        $disk = Storage::disk('local');

        if ($path === '' || ! $disk->exists($path)) {
            abort(404, 'Gambar QRIS belum tersedia.');
        }

        $mime = $disk->mimeType($path) ?? 'image/png';
        $content = $disk->get($path);
        $headers = [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ];

        if ($request->boolean('download')) {
            $extension = match ($mime) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $filename = 'QRIS-SynemaX-'.$settings->qrisMerchantName().'.'.$extension;
            $headers['Content-Disposition'] = 'attachment; filename="'.$filename.'"';
        }

        return response($content, 200, $headers);
    }

    private function authorizeBookingAccess(Request $request, Booking $booking): void
    {
        $waParam = $request->query('whatsapp') ?? $request->query('wa');
        $normalizedWa = $waParam ? WhatsAppNumber::normalize((string) $waParam) : null;
        $isVerifiedCustomer = ($normalizedWa !== null && $normalizedWa === $booking->whatsapp)
            || $request->session()->get('verified_booking_'.$booking->code) === true;
        $isAdmin = (bool) $request->user()?->is_admin;

        if (! $isAdmin && ! $isVerifiedCustomer) {
            abort(403, 'Akses ditolak. Silakan cek booking dengan nomor WhatsApp pemesan.');
        }

        if ($normalizedWa !== null && $normalizedWa === $booking->whatsapp) {
            $request->session()->put('verified_booking_'.$booking->code, true);
        }
    }

    private function paymentWhatsAppUrl(Booking $booking, CinemaSettings $settings): ?string
    {
        $adminWhatsApp = preg_replace('/\D+/', '', $settings->string('admin_whatsapp'));

        if ($adminWhatsApp === '') {
            return null;
        }

        $paymentMethodLabel = match ($booking->payment_method) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            default => 'Bayar di tempat',
        };

        $lines = [
            'Halo admin SynemaX, saya ingin kirim bukti bayar:',
            'Kode: '.$booking->code,
            'Nama: '.$booking->customer_name,
            'Film: '.($booking->film?->title ?? 'Film sesi'),
            'Jadwal: '.$booking->session->session_date.' · '.substr((string) $booking->session->start_time, 0, 5).' WIB',
            'Total: Rp '.number_format($booking->total_price, 0, ',', '.'),
            'Metode bayar: '.$paymentMethodLabel,
            'Bukti pembayaran saya lampirkan di pesan WhatsApp ini.',
        ];

        return 'https://wa.me/'.$adminWhatsApp.'?text='.rawurlencode(implode("\n", $lines));
    }

    private function ticketWhatsAppUrl(Booking $booking, CinemaSettings $settings): ?string
    {
        $adminWhatsApp = preg_replace('/\D+/', '', $settings->string('admin_whatsapp'));

        if ($adminWhatsApp === '') {
            return null;
        }

        $paymentMethodLabel = match ($booking->payment_method) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            default => 'Bayar di tempat',
        };

        $statusLabel = match ($booking->status) {
            'menunggu_pembayaran' => 'Menunggu pembayaran',
            'menunggu_konfirmasi' => 'Menunggu konfirmasi admin',
            'terkonfirmasi' => 'Terkonfirmasi',
            'kedaluwarsa' => 'Kedaluwarsa',
            'hadir' => 'Hadir',
            'tidak_hadir' => 'Tidak hadir',
            'dibatalkan' => 'Dibatalkan',
            default => $booking->status,
        };

        $lines = [
            'Halo admin SynemaX, saya pemesan tiket:',
            'Kode: '.$booking->code,
            'Nama: '.$booking->customer_name,
            'Film: '.($booking->film?->title ?? 'Film sesi'),
            'Jadwal: '.$booking->session->session_date.' · '.substr((string) $booking->session->start_time, 0, 5).' WIB',
            $booking->booking_type === 'private'
                ? 'Jumlah: Sewa Private (6 kursi)'
                : 'Jumlah penonton: '.$booking->pax.' orang',
            'Total: Rp '.number_format($booking->total_price, 0, ',', '.'),
            'Metode: '.$paymentMethodLabel,
            'Status saat ini: '.$statusLabel,
        ];

        return 'https://wa.me/'.$adminWhatsApp.'?text='.rawurlencode(implode("\n", $lines));
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
            'Halo admin SynemaX, saya ingin mengajukan perubahan pesanan jajan.',
            'Kode booking: '.$booking->code,
            'Nama: '.$booking->customer_name,
            'Jadwal: '.$booking->session->session_date.' · '.substr((string) $booking->session->start_time, 0, 5).' WIB',
            'Pesanan jajan saat ini: '.($items->isNotEmpty() ? $items->implode(', ') : 'Belum ada jajan'),
            'Saya ingin mengubah pesanan jajan: [tuliskan perubahan yang diinginkan].',
        ]);

        return 'https://wa.me/'.$adminWhatsApp.'?text='.rawurlencode($message);
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
}
