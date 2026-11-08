<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Member;
use App\Models\MemberReward;
use App\Models\MemberVisit;
use App\Models\Session;
use App\Services\CheckInService;
use App\Services\CinemaSettings;
use App\Services\LoyaltyRules;
use App\Services\MemberCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TicketScannerController extends Controller
{
    /**
     * Halaman antarmuka pemindai tiket admin.
     */
    public function index(Request $request, CinemaSettings $settings): Response
    {
        $todayDate = now()->toDateString();
        $todaySessions = Session::query()
            ->with(['film', 'occupiedBookings'])
            ->where('session_date', $todayDate)
            ->orderBy('start_time')
            ->get();

        // Pilih sesi otomatis: sesi yang sedang berjalan sekarang atau sesi terdekat berikutnya
        $requestedSessionId = $request->query('sesi');
        $selectedSession = null;

        if ($requestedSessionId && $requestedSessionId !== 'all') {
            $selectedSession = $todaySessions->firstWhere('id', (int) $requestedSessionId);
        }

        if (! $selectedSession && $requestedSessionId !== 'all' && $todaySessions->isNotEmpty()) {
            $now = now();
            // 1. Sesi yang sedang berjalan
            $selectedSession = $todaySessions->first(fn (Session $s) => $now->greaterThanOrEqualTo($s->startsAt()) && $now->lessThan($s->endsAt()));

            // 2. Jika tidak ada yang sedang berjalan, ambil sesi terdekat yang belum mulai
            if (! $selectedSession) {
                $selectedSession = $todaySessions->first(fn (Session $s) => $now->lessThan($s->startsAt()));
            }

            // 3. Jika semua sudah lewat, ambil sesi terakhir hari ini
            if (! $selectedSession) {
                $selectedSession = $todaySessions->last();
            }
        }

        $soundEnabled = $settings->checkInSoundEnabled();

        /** @var View $view */
        $view = view('admin.scan.index', [
            'todaySessions' => $todaySessions,
            'selectedSession' => $selectedSession,
            'selectedSessionId' => $requestedSessionId === 'all' ? 'all' : ($selectedSession?->id ?? 'all'),
            'soundEnabled' => $soundEnabled,
        ]);

        return response($view)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Cari tiket atau member berdasarkan QR / input kode (POST /admin/scan/cari).
     */
    public function lookup(Request $request, CheckInService $checkIn, LoyaltyRules $loyaltyRules): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'session_id' => ['nullable', 'string'],
        ]);

        $adminId = (int) $request->user()?->id;
        $failedKey = 'scan_failed:'.$adminId;

        // Throttle kegagalan kode berulang (maksimal 20 gagal per menit)
        if (RateLimiter::tooManyAttempts($failedKey, 20)) {
            $seconds = RateLimiter::availableIn($failedKey);

            return response()->json([
                'status' => 'rate_limited',
                'verdict' => 'terlalu_banyak_gagal',
                'message' => "Terlalu banyak kode tidak ditemukan. Mohon jeda {$seconds} detik sebelum mencoba lagi.",
            ], 429)->header('Cache-Control', 'no-store');
        }

        $rawCode = trim($data['code']);
        $normCode = MemberCodeGenerator::normalize($rawCode);

        // 1. Cek apakah kode merupakan ID Member
        $member = null;
        if (str_starts_with($normCode, 'MBR-') || MemberCodeGenerator::isValid($normCode)) {
            $member = Member::where('code', $normCode)->orWhere('member_code', $normCode)->first();
        } elseif (! Booking::where('code', $rawCode)->exists()) {
            // Coba cari member berdasarkan kode atau nama jika bukan booking persis
            $member = Member::where('code', $normCode)
                ->orWhere('member_code', $normCode)
                ->orWhere('code', 'like', "%{$normCode}")
                ->orWhere('name', 'like', "%{$rawCode}%")
                ->first();
        }

        if ($member) {
            RateLimiter::clear($failedKey);

            // Sesi terpilih
            $sessionId = $data['session_id'] ?? null;
            $session = null;
            if ($sessionId && $sessionId !== 'all') {
                $session = Session::find((int) $sessionId);
            }

            if (! $session) {
                // Sesi default hari ini
                $todayDate = now()->toDateString();
                $todaySessions = Session::with(['film', 'occupiedBookings'])
                    ->where('session_date', $todayDate)
                    ->orderBy('start_time')
                    ->get();

                $now = now();
                $session = $todaySessions->first(fn (Session $s) => $now->greaterThanOrEqualTo($s->startsAt()) && $now->lessThan($s->endsAt()))
                    ?? $todaySessions->first(fn (Session $s) => $now->lessThan($s->startsAt()))
                    ?? $todaySessions->last();
            }

            $eval = $loyaltyRules->evaluateCheckIn($member, $session);

            $sessionBookings = $session ? $session->occupiedBookings->map(fn (Booking $b) => [
                'id' => $b->id,
                'code' => $b->code,
                'customer_name' => $b->customer_name,
                'status' => $b->status,
                'is_paid' => $b->isPaid(),
                'payment_method' => $b->payment_method,
                'pax' => $b->pax ?? $b->people_count ?? 1,
            ])->values() : collect();

            return response()->json([
                'type' => 'member',
                'status' => 'found',
                'verdict' => $eval['verdict'],
                'can_record' => $eval['can_record'],
                'requires_override' => $eval['requires_override'],
                'message' => $eval['message'],
                'member' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'code' => $member->code ?: $member->member_code,
                    'status' => $member->status,
                    'photo_url' => $member->photoUrl(),
                    'total_valid_visits' => $member->totalValidVisits(),
                    'next_position' => $eval['next_position'],
                    'current_in_cycle' => $eval['next_position'] - 1,
                    'cycle_number' => $eval['cycle_number'],
                    'progress_text' => $member->progressDescription(),
                    'potential_reward' => $eval['potential_reward'],
                    'potential_reward_label' => LoyaltyRules::rewardLabel($eval['potential_reward']),
                    'reward_amount' => $eval['reward_amount'],
                    'last_valid_visit_id' => $member->lastValidVisit()?->id,
                ],
                'session' => $session ? [
                    'id' => $session->id,
                    'film_title' => $session->film?->title ?? 'Sesi Film',
                    'time' => format_cinema_time($session->start_time),
                    'status' => $session->status,
                ] : null,
                'session_bookings' => $sessionBookings,
            ])->header('Cache-Control', 'no-store');
        }

        // 2. Jika bukan member, jalankan lookup booking biasa
        $result = $checkIn->lookup($data['code'], $data['session_id'] ?? null);

        if ($result['status'] === 'not_found') {
            RateLimiter::hit($failedKey, 60);
        } else {
            RateLimiter::clear($failedKey);
        }

        $result['type'] = 'booking';

        return response()->json($result)
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Tandai hadir member (POST /admin/scan/member-hadir).
     */
    public function memberCheckIn(Request $request, LoyaltyRules $loyaltyRules): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'session_id' => ['required', 'integer'],
            'booking_id' => ['nullable', 'integer'],
            'method' => ['nullable', 'string', 'in:qr,kode,cari'],
            'allow_override' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $member = Member::findOrFail($data['member_id']);
        $session = Session::findOrFail($data['session_id']);
        $booking = ! empty($data['booking_id']) ? Booking::find($data['booking_id']) : null;

        $userId = (int) $request->user()?->id;
        $method = $data['method'] ?? 'qr';
        $overrideReason = $data['override_reason'] ?? null;

        $result = $loyaltyRules->recordVisit(
            member: $member,
            session: $session,
            booking: $booking,
            userId: $userId,
            method: $method,
            overrideReason: $overrideReason,
        );

        return response()->json($result)->header('Cache-Control', 'no-store');
    }

    /**
     * Batalkan tanda hadir member (POST /admin/scan/member-batal-hadir).
     */
    public function memberUndo(Request $request, LoyaltyRules $loyaltyRules): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'visit_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $member = Member::findOrFail($data['member_id']);
        $visit = MemberVisit::where('id', $data['visit_id'])->where('member_id', $member->id)->firstOrFail();
        $userId = (int) $request->user()?->id;

        $result = $loyaltyRules->voidVisit($member, $visit, $userId, $data['reason']);

        return response()->json($result)->header('Cache-Control', 'no-store');
    }

    /**
     * Selesaikan reward member (POST /admin/scan/member-reward-settle).
     */
    public function memberRewardSettle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reward_id' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:given,skipped'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $reward = MemberReward::findOrFail($data['reward_id']);
        $reward->update([
            'status' => $data['action'],
            'given_by' => $request->user()?->id,
            'given_at' => now(),
            'note' => $data['note'] ?? $reward->note,
        ]);

        $reward->member->events()->create([
            'user_id' => $request->user()?->id,
            'event' => $data['action'] === 'given' ? 'reward_given' : 'reward_skipped',
            'event_type' => $data['action'] === 'given' ? 'reward_given' : 'reward_skipped',
            'meta' => [
                'reward_id' => $reward->id,
                'action' => $data['action'],
                'note' => $data['note'] ?? null,
            ],
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $data['action'] === 'given' ? 'Reward berhasil ditandai sudah diberikan.' : 'Reward dilewati.',
            'reward' => $reward,
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Tandai hadir (POST /admin/scan/hadir).
     */
    public function checkIn(Request $request, CheckInService $checkIn): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer'],
            'method' => ['nullable', 'string', 'in:qr,kode,daftar,admin_manual'],
            'reason' => ['nullable', 'string', 'max:255'],
            'allow_override' => ['nullable', 'boolean'],
            'session_id' => ['nullable', 'string'],
        ]);

        $userId = (int) $request->user()->id;
        $method = $data['method'] ?? 'qr';
        $reason = $data['reason'] ?? null;
        $allowOverride = (bool) ($data['allow_override'] ?? false);
        $sessionId = $data['session_id'] ?? null;

        $result = $checkIn->checkIn(
            bookingId: (int) $data['booking_id'],
            userId: $userId,
            method: $method,
            reason: $reason,
            allowOverride: $allowOverride,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            selectedSessionId: $sessionId,
        );

        return response()->json($result)
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Batalkan tanda hadir (POST /admin/scan/batal-hadir).
     */
    public function undo(Request $request, CheckInService $checkIn): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = (int) $request->user()->id;
        $result = $checkIn->undoCheckIn(
            bookingId: (int) $data['booking_id'],
            userId: $userId,
            reason: $data['reason'] ?? null,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json($result)
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Ambil daftar booking sesi untuk tab "Daftar" (GET /admin/scan/sesi-bookings).
     */
    public function sessionBookings(Request $request): JsonResponse
    {
        $sessionId = $request->query('session_id');
        $todayDate = now()->toDateString();

        $query = Booking::query()
            ->with(['film', 'session', 'checkedInBy', 'items.menuItem']);

        if ($sessionId && $sessionId !== 'all') {
            $query->where('session_id', (int) $sessionId);
        } else {
            $query->whereHas('session', fn ($q) => $q->where('session_date', $todayDate));
        }

        $allBookings = $query->get();

        // Pengelompokan & pengurutan:
        // Siap hadir / aktif di atas, lalu belum bayar / menunggu, lalu sudah hadir
        $sorted = $allBookings->sortBy(function (Booking $b) {
            if ($b->status === 'hadir' || $b->checked_in_at !== null) {
                return 3;
            }
            if ($b->flowState() === 'aktif') {
                return 1;
            }

            return 2;
        });

        $hadirCount = 0;
        $belumCount = 0;
        $perluPerhatianCount = 0;

        $items = $sorted->map(function (Booking $b) use (&$hadirCount, &$belumCount, &$perluPerhatianCount) {
            $isHadir = $b->status === 'hadir' || $b->checked_in_at !== null;
            $flow = $b->flowState();

            if ($isHadir) {
                $hadirCount++;
            } elseif ($flow === 'aktif') {
                $belumCount++;
            } else {
                $perluPerhatianCount++;
            }

            return [
                'id' => $b->id,
                'code' => $b->code,
                'customer_name' => $b->customer_name,
                'whatsapp' => $b->whatsapp,
                'status' => $b->status,
                'flow_state' => $flow,
                'is_hadir' => $isHadir,
                'checked_in_at' => $b->checked_in_at ? format_cinema_time($b->checked_in_at) : null,
                'checked_in_by' => $b->checkedInBy?->name,
                'check_in_method' => $b->check_in_method,
                'film_title' => $b->film?->title ?? '-',
                'session_time' => $b->session ? format_cinema_time($b->session->start_time) : '-',
                'pax' => $b->pax ?? $b->people_count ?? 1,
                'people_count' => $b->people_count ?? $b->pax ?? 1,
                'chip' => $isHadir ? 'Hadir' : ($flow === 'aktif' ? 'Belum Hadir' : 'Perlu Perhatian'),
                'total_price' => $b->total_price,
                'is_paid' => $b->isPaid(),
                'items_count' => $b->items->count(),
            ];
        });

        return response()->json([
            'session_id' => $sessionId,
            'summary' => [
                'total' => $allBookings->count(),
                'hadir' => $hadirCount,
                'belum' => $belumCount,
                'belum_hadir' => $belumCount,
                'perlu_perhatian' => $perluPerhatianCount,
                'text' => "{$hadirCount} hadir, {$belumCount} belum",
            ],
            'bookings' => $items->values(),
            'items' => $items->values(),
        ])->header('Cache-Control', 'no-store');
    }
}
