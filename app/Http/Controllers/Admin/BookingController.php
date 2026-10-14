<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CustomerProfile;
use App\Models\MenuCategory;
use App\Models\Session;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CinemaSettings;
use App\Services\SnackOrderService;
use App\Support\WhatsAppNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function index(Request $request, BookingService $bookingService): View
    {
        $bookingService->expireUnpaidBookings();

        $filters = $request->validate([
            'status' => ['nullable', 'in:menunggu_pembayaran,menunggu_konfirmasi,terkonfirmasi,hadir,tidak_hadir,dibatalkan,kedaluwarsa'],
            'session_id' => ['nullable', 'integer', 'exists:screening_sessions,id'],
            'payment_method' => ['nullable', 'in:di_tempat,qris,transfer'],
            'has_proof' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $bookings = Booking::query()
            ->with(['session.film', 'film', 'customerProfile'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['session_id'] ?? null, fn (Builder $query, int $sessionId) => $query->where('session_id', $sessionId))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $method) => $query->where('payment_method', $method))
            ->when(isset($filters['has_proof']), function (Builder $query) use ($filters): void {
                if ($filters['has_proof']) {
                    $query->whereNotNull('payment_proof_path');
                } else {
                    $query->whereNull('payment_proof_path');
                }
            })
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'selectedStatus' => $filters['status'] ?? '',
            'selectedMethod' => $filters['payment_method'] ?? '',
            'search' => $filters['q'] ?? '',
        ]);
    }

    public function show(Booking $booking, CinemaSettings $settings, BookingService $bookingService): View
    {
        $booking = $bookingService->expireIfOverdue($booking);

        $booking->load([
            'session.film',
            'film',
            'customerProfile',
            'items.menuItem',
            'snackChangeLogs.actor',
        ]);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'categories' => MenuCategory::query()
                ->with(['items' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
                ->orderBy('sort_order')
                ->get(),
            'snackMaxQty' => $settings->integer('snack_max_qty_per_item', 10),
            'snackChangeLogs' => $booking->snackChangeLogs,
            'snackUpdateWhatsAppUrl' => $this->snackUpdateWhatsAppUrl($booking),
        ]);
    }

    public function updateSnacks(
        Request $request,
        Booking $booking,
        SnackOrderService $snackOrders,
    ): RedirectResponse {
        $data = $request->validate([
            'snacks' => ['nullable', 'array', 'max:100'],
            'snacks.*' => ['required', 'array:qty,note'],
            'snacks.*.qty' => ['required', 'integer', 'min:0'],
            'snacks.*.note' => ['nullable', 'string', 'max:250'],
        ]);

        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        $updatedBooking = $snackOrders->replaceItems(
            $booking,
            $data['snacks'] ?? [],
            enforceDeadline: false,
            auditActor: $actor,
        );

        return back()
            ->with('status', 'Pesanan jajan dan total booking berhasil diperbarui.')
            ->with('snackUpdateWhatsAppUrl', $this->snackUpdateWhatsAppUrl($updatedBooking));
    }

    public function updateSnackStatuses(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:baru,disiapkan,siap,selesai'],
        ]);

        DB::transaction(function () use ($booking, $data): void {
            $reference = Booking::query()->findOrFail($booking->id);
            Session::query()->lockForUpdate()->findOrFail($reference->session_id);
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($lockedBooking->status === 'dibatalkan') {
                $this->reject('Booking ini sudah dibatalkan.');
            }

            $lockedBooking->items()
                ->where('status', '!=', 'dibatalkan')
                ->update(['status' => $data['status'], 'updated_at' => now()]);
        }, attempts: 3);

        return back()->with('status', 'Status semua jajan di booking berhasil diperbarui.');
    }

    public function updateStatus(
        Request $request,
        Booking $booking,
        SnackOrderService $snackOrders,
        BookingService $bookingService,
    ): RedirectResponse {
        $data = $request->validate([
            'action' => ['required', 'in:konfirmasi,tandai_dibayar,tolak_bukti,batal,hadir,tidak_hadir,pulihkan'],
            'rejection_note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['action'] === 'tolak_bukti' && empty(trim((string) ($data['rejection_note'] ?? '')))) {
            throw ValidationException::withMessages([
                'rejection_note' => 'Alasan penolakan bukti pembayaran wajib diisi.',
            ]);
        }

        DB::transaction(function () use ($booking, $data, $snackOrders, $bookingService): void {
            $bookingReference = Booking::query()->findOrFail($booking->id);
            $session = Session::query()->lockForUpdate()->findOrFail($bookingReference->session_id);
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            match ($data['action']) {
                'konfirmasi', 'tandai_dibayar' => $this->confirm($lockedBooking, $snackOrders),
                'tolak_bukti' => $bookingService->rejectProof($lockedBooking, trim((string) $data['rejection_note'])),
                'pulihkan' => $bookingService->restoreExpired($lockedBooking),
                'batal' => $this->cancel($lockedBooking, $session, $snackOrders),
                'hadir' => $this->markAttended($lockedBooking),
                'tidak_hadir' => $this->markNoShow($lockedBooking),
            };
        }, attempts: 3);

        $msg = match ($data['action']) {
            'tolak_bukti' => 'Bukti pembayaran ditolak dan pelanggan diminta mengunggah ulang.',
            'pulihkan' => 'Booking kedaluwarsa berhasil dipulihkan.',
            default => 'Status booking berhasil diperbarui.',
        };

        return back()->with('status', $msg);
    }

    public function toggleCustomerBlock(CustomerProfile $profile): RedirectResponse
    {
        DB::transaction(function () use ($profile): void {
            $lockedProfile = CustomerProfile::query()->lockForUpdate()->findOrFail($profile->id);
            $lockedProfile->update(['is_blocked' => ! $lockedProfile->is_blocked]);
            $profile->refresh();
        });

        return back()->with(
            'status',
            $profile->is_blocked
                ? 'Nomor WhatsApp diblokir dari booking baru.'
                : 'Blokir nomor WhatsApp sudah dibuka.',
        );
    }

    public function proof(Booking $booking): Response
    {
        abort_if($booking->payment_proof_path === null, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($booking->payment_proof_path), 404);

        return $disk->response($booking->payment_proof_path);
    }

    private function confirm(Booking $booking, SnackOrderService $snackOrders): void
    {
        if (! in_array($booking->status, ['menunggu_konfirmasi', 'menunggu_pembayaran'], true)) {
            $this->reject('Booking ini tidak sedang menunggu konfirmasi atau pembayaran.');
        }

        $snackOrders->confirmStock($booking);
        $booking->update([
            'status' => 'terkonfirmasi',
            'confirmed_at' => now(),
            'proof_rejection_note' => null,
        ]);
    }

    private function cancel(
        Booking $booking,
        Session $session,
        SnackOrderService $snackOrders,
    ): void {
        if (! in_array($booking->status, ['menunggu_pembayaran', 'menunggu_konfirmasi', 'terkonfirmasi'], true)) {
            $this->reject('Booking ini tidak bisa dibatalkan.');
        }

        $booking->update(['status' => 'dibatalkan', 'cancelled_at' => now()]);
        $snackOrders->cancelItems($booking);

        if (
            $session->is_private
            && (int) $session->occupiedBookings()->sum('seats_reserved') === 0
        ) {
            $session->update(['is_private' => false]);
        }
    }

    private function markAttended(Booking $booking): void
    {
        if ($booking->status !== 'terkonfirmasi') {
            $this->reject('Konfirmasi booking sebelum menandai kehadiran.');
        }

        $booking->update(['status' => 'hadir', 'attended_at' => now()]);
    }

    private function markNoShow(Booking $booking): void
    {
        if ($booking->status !== 'terkonfirmasi') {
            $this->reject('Konfirmasi booking sebelum menandai tidak hadir.');
        }

        $booking->update(['status' => 'tidak_hadir']);

        if ($booking->customer_profile_id !== null) {
            CustomerProfile::query()
                ->whereKey($booking->customer_profile_id)
                ->lockForUpdate()
                ->increment('no_show_count');
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['status' => $message]);
    }

    private function snackUpdateWhatsAppUrl(Booking $booking): ?string
    {
        $whatsapp = WhatsAppNumber::normalize($booking->whatsapp);

        if (! WhatsAppNumber::isValid($whatsapp)) {
            return null;
        }

        $items = $booking->items->where('status', '!=', 'dibatalkan');
        $message = implode("\n", [
            'Halo '.$booking->customer_name.', pesanan jajan Home Cinema sudah diperbarui.',
            'Kode booking: '.$booking->code,
            ...$items->map(fn ($item): string => 'Jajan: '.$item->name_snapshot.' × '.$item->qty.($item->note ? ' ('.$item->note.')' : ''))->all(),
            'Total jajan: Rp '.number_format($booking->snack_total, 0, ',', '.'),
            'Total booking: Rp '.number_format($booking->total_price, 0, ',', '.'),
        ]);

        return 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($message);
    }
}
