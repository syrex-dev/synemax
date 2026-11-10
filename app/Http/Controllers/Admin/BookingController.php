<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CustomerProfile;
use App\Models\Film;
use App\Models\Member;
use App\Models\MenuCategory;
use App\Models\Session;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CheckInService;
use App\Services\CinemaSettings;
use App\Services\LoyaltyRules;
use App\Services\MemberCodeGenerator;
use App\Services\ScheduleResolver;
use App\Services\SnackOrderService;
use App\Services\TelegramNotificationService;
use App\Support\WhatsAppNumber;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
            ->with(['session.film', 'film', 'customerProfile', 'items'])
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
                $digits = preg_replace('/\D+/', '', $search);
                $query->where(function (Builder $query) use ($search, $digits): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");

                    if ($digits !== '') {
                        $query->orWhere('whatsapp', 'like', "%{$digits}%");
                        if (str_starts_with($digits, '0')) {
                            $query->orWhere('whatsapp', 'like', '%'.substr($digits, 1).'%');
                        } elseif (str_starts_with($digits, '62')) {
                            $query->orWhere('whatsapp', 'like', '%'.substr($digits, 2).'%');
                        }
                    }
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

    public function liveSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') {
            return response()->json(['results' => [], 'count' => 0]);
        }

        $digits = preg_replace('/\D+/', '', $q);

        $results = Booking::query()
            ->with(['session.film', 'film'])
            ->where(function (Builder $query) use ($q, $digits): void {
                $query->where('code', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%");

                if ($digits !== '') {
                    $query->orWhere('whatsapp', 'like', "%{$digits}%");
                    if (str_starts_with($digits, '0')) {
                        $query->orWhere('whatsapp', 'like', '%'.substr($digits, 1).'%');
                    } elseif (str_starts_with($digits, '62')) {
                        $query->orWhere('whatsapp', 'like', '%'.substr($digits, 2).'%');
                    }
                }
            })
            ->latest('id')
            ->take(8)
            ->get()
            ->map(function (Booking $b): array {
                $statusLabels = [
                    'menunggu_pembayaran' => 'Menunggu Bayar',
                    'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                    'terkonfirmasi' => 'Terkonfirmasi',
                    'kedaluwarsa' => 'Kedaluwarsa',
                    'hadir' => 'Hadir',
                    'tidak_hadir' => 'Tidak Datang',
                    'dibatalkan' => 'Dibatalkan',
                ];

                $sessionDate = $b->session ? Carbon::parse($b->session->session_date)->locale('id')->translatedFormat('D, d M') : '';
                $sessionTime = $b->session ? str_replace(':', '.', substr((string) $b->session->start_time, 0, 5)).' WIB' : '';

                return [
                    'id' => $b->id,
                    'code' => $b->code,
                    'customer_name' => $b->customer_name,
                    'whatsapp' => $b->whatsapp,
                    'film_title' => $b->session?->film?->title ?? $b->film?->title ?? 'Film',
                    'status' => $b->status,
                    'status_label' => $statusLabels[$b->status] ?? ucfirst($b->status),
                    'total_price_formatted' => 'Rp '.number_format($b->total_price, 0, ',', '.'),
                    'session_info' => trim("{$sessionDate} · {$sessionTime}", ' ·'),
                    'url' => route('admin.bookings.show', $b),
                ];
            });

        return response()->json([
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    public function create(Request $request, CinemaSettings $settings, ScheduleResolver $scheduleResolver): View
    {
        $today = now(config('app.timezone'))->toDateString();

        $sessions = Session::query()
            ->with(['film', 'occupiedBookings'])
            ->where('session_date', '>=', $today)
            ->where('status', 'tersedia')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        $films = Film::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'poster_path', 'duration_minutes', 'age_rating']);

        $categories = MenuCategory::query()
            ->with(['items' => fn ($q) => $q->where('is_available', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->get();

        $selectedSessionId = (int) $request->input('session_id', 0);
        $selectedSession = $sessions->firstWhere('id', $selectedSessionId) ?? $sessions->first();

        return view('admin.bookings.create', [
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
            'films' => $films,
            'categories' => $categories,
            'settings' => $settings,
            'today' => $today,
            'snackMaxQty' => $settings->integer('snack_max_qty_per_item', 10),
            'lastBooking' => session('walk_in_success'),
        ]);
    }

    public function memberLookup(Request $request, LoyaltyRules $loyaltyRules): JsonResponse
    {
        $raw = trim((string) $request->input('q', ''));
        if ($raw === '') {
            return response()->json(['found' => false, 'message' => 'Masukkan kode atau nama member.']);
        }

        $norm = MemberCodeGenerator::normalize($raw);
        $member = Member::query()
            ->where('code', $norm)
            ->orWhere('member_code', $norm)
            ->orWhere('code', 'like', "%{$norm}")
            ->orWhere('name', 'like', "%{$raw}%")
            ->orWhere('nickname', 'like', "%{$raw}%")
            ->first();

        if (! $member) {
            return response()->json(['found' => false, 'message' => 'Member tidak ditemukan.']);
        }

        $sessionId = $request->input('session_id');
        $session = $sessionId ? Session::find((int) $sessionId) : null;
        $eval = $loyaltyRules->evaluateCheckIn($member, $session);
        $cycleMax = app(CinemaSettings::class)->memberFreeVisit();
        $nextPos = $member->nextVisitPosition($cycleMax);

        return response()->json([
            'found' => true,
            'member' => [
                'id' => $member->id,
                'code' => $member->code,
                'name' => $member->name,
                'nickname' => $member->nickname,
                'display_name' => $member->nickname ? "{$member->name} ({$member->nickname})" : $member->name,
                'photo_url' => $member->photo_path ? Storage::url($member->photo_path) : null,
                'status' => $member->status,
                'is_active' => (bool) $member->is_active,
                'next_position' => $nextPos,
                'cycle_max' => $cycleMax,
                'potential_reward' => $eval['potential_reward'],
                'potential_reward_label' => LoyaltyRules::REWARD_LABELS[$eval['potential_reward']] ?? 'Tanpa Reward',
                'reward_amount' => $eval['reward_amount'],
                'reward_amount_formatted' => 'Rp '.number_format($eval['reward_amount'], 0, ',', '.'),
            ],
        ]);
    }

    public function store(Request $request, BookingService $bookingService, CinemaSettings $settings): RedirectResponse
    {
        $maxSeats = max(6, $settings->capacityPerSession());
        $data = $request->validate([
            'session_id' => ['required', 'integer', 'exists:screening_sessions,id'],
            'package' => ['required', 'in:reguler,couple,private'],
            'pax' => ['nullable', 'integer', 'min:1', "max:{$maxSeats}"],
            'film_id' => ['nullable', 'integer', 'exists:films,id'],
            'customer_name' => ['nullable', 'string', 'max:80'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'member_code' => ['nullable', 'string', 'max:60'],
            'apply_member_reward' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', 'in:di_tempat,qris,transfer'],
            'cash_received' => ['nullable', 'integer', 'min:0'],
            'mark_as_attended' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'snacks' => ['nullable', 'array', 'max:100'],
            'snacks.*.qty' => ['nullable', 'integer', 'min:0'],
            'snacks.*.note' => ['nullable', 'string', 'max:250'],
        ]);

        $session = Session::findOrFail($data['session_id']);
        $result = $bookingService->createWalkIn($session, $data, $request->user());
        $booking = $result['booking'];
        $changeAmount = $result['change_amount'];
        $cashReceived = $result['cash_received'];

        $flashMessage = 'Pemesanan loket berhasil dibuat!';
        if ($changeAmount > 0) {
            $flashMessage .= ' Kembalian tunai: Rp '.number_format($changeAmount, 0, ',', '.');
        }

        return redirect()
            ->route('admin.bookings.create', ['session_id' => $session->id])
            ->with('status', $flashMessage)
            ->with('walk_in_success', [
                'id' => $booking->id,
                'code' => $booking->code,
                'customer_name' => $booking->customer_name,
                'total_price' => $booking->total_price,
                'total_price_formatted' => 'Rp '.number_format($booking->total_price, 0, ',', '.'),
                'cash_received' => $cashReceived,
                'cash_received_formatted' => 'Rp '.number_format($cashReceived, 0, ',', '.'),
                'change_amount' => $changeAmount,
                'change_amount_formatted' => 'Rp '.number_format($changeAmount, 0, ',', '.'),
                'ticket_url' => route('admin.bookings.ticket', $booking),
                'show_url' => route('admin.bookings.show', $booking),
                'attended' => $booking->status === 'hadir',
                'member_name' => $result['member']?->name,
                'member_code' => $result['member']?->code,
            ]);
    }

    public function cleanTestData(Request $request, BookingService $bookingService): RedirectResponse
    {
        $resetVisits = $request->boolean('reset_member_visits');
        $result = $bookingService->cleanTestData($resetVisits);

        return redirect()
            ->route('admin.bookings.index')
            ->with('status', "Semua data booking testing berhasil dibersihkan ({$result['deleted_bookings']} booking, {$result['deleted_items']} jajan dihapus). Laporan keuangan kini bersih (Rp 0).");
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

    public function ticket(Booking $booking): View
    {
        $booking->load([
            'session.film',
            'film',
            'customerProfile',
            'items.menuItem',
        ]);

        return view('admin.bookings.ticket', [
            'booking' => $booking,
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
            'manual_reason' => ['nullable', 'string', 'max:120'],
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
                'hadir' => $this->markAttended($lockedBooking, $data['manual_reason'] ?? null),
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
        if ($booking->status === 'kedaluwarsa') {
            $session = $booking->session()->first();
            if ($session) {
                if ($session->status !== 'tersedia') {
                    $this->reject('Sesi untuk booking ini sedang tidak tersedia.');
                }
                $occupiedSeats = (int) $session->occupiedBookings()->where('id', '!=', $booking->id)->sum('seats_reserved');
                if ($booking->isPrivate()) {
                    if ($session->hasActivePerPersonBookings() || $occupiedSeats > 0 || $session->isPrivateActive()) {
                        $this->reject('Tidak dapat mengaktifkan: Sesi ini sudah terisi atau disewa privat oleh pemesan lain.');
                    }
                } else {
                    if ($session->isPrivateActive()) {
                        $this->reject('Tidak dapat mengaktifkan: Sesi ini sudah disewa privat.');
                    }
                    if ($occupiedSeats + $booking->seats_reserved > $session->seatCapacity()) {
                        $this->reject('Tidak dapat mengaktifkan: Kapasitas sesi ini sudah tidak mencukupi.');
                    }
                }
            }
        } elseif (! in_array($booking->status, ['menunggu_konfirmasi', 'menunggu_pembayaran'], true)) {
            $this->reject('Booking ini tidak sedang menunggu konfirmasi atau pembayaran.');
        }

        $snackOrders->confirmStock($booking);
        $booking->update([
            'status' => 'terkonfirmasi',
            'confirmed_at' => now(),
            'proof_rejection_note' => null,
        ]);

        $booking->session?->syncPrivateStatus();

        app(TelegramNotificationService::class)->queue('payment_confirmed', $booking);
        app(TelegramNotificationService::class)->flush();
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

        $session->syncPrivateStatus();

        app(TelegramNotificationService::class)->queue('booking_cancelled', $booking);
        app(TelegramNotificationService::class)->flush();
    }

    private function markAttended(Booking $booking, ?string $reason = null): void
    {
        $service = app(CheckInService::class);
        $result = $service->checkIn(
            bookingId: $booking->id,
            userId: (int) auth()->id(),
            method: 'admin_manual',
            reason: $reason ?: 'Ditandai manual dari detail booking admin',
            allowOverride: true,
            ip: request()->ip(),
            userAgent: request()->userAgent(),
        );

        if (! $result['success'] && $result['verdict'] !== 'siap_hadir' && ($result['result'] ?? '') !== 'duplicate') {
            $this->reject($result['message']);
        }
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
