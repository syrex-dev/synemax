<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Booking;
use App\Models\BotFeedback;
use App\Models\Film;
use App\Models\MemberReward;
use App\Models\MenuItem;
use App\Models\Promo;
use App\Models\Session;
use App\Services\CinemaSettings;
use App\Services\TelegramNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    public function __invoke(CinemaSettings $settings): View|InertiaResponse
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $tomorrow = $today->addDay();
        $activeBookings = fn ($query) => $query->where('status', '!=', 'dibatalkan');

        $todaySessions = Session::query()
            ->where('session_date', $today->toDateString())
            ->with(['film', 'bookings' => $activeBookings, 'occupiedBookings.film'])
            ->with([
                'occupiedBookings.items' => fn ($query) => $query
                    ->where('status', '!=', 'dibatalkan')
                    ->orderBy('name_snapshot'),
            ])
            ->withCount([
                'occupiedBookings',
                'bookings as pending_booking_count' => fn ($query) => $query
                    ->where('status', 'menunggu_konfirmasi'),
                'memberVisits as member_visits_count' => fn ($query) => $query
                    ->where('active', 1),
            ])
            ->withSum(['occupiedBookings as viewer_count' => $activeBookings], 'seats_reserved')
            ->orderBy('start_time')
            ->get();

        $minimumViewers = $settings->minimumPublicViewers(); // default 4
        $warningWindowHours = $settings->lowAttendanceWarningWindowHours(); // default 72
        $warningWindowCutoff = now()->addHours($warningWindowHours);

        $upcomingSessions = Session::query()
            ->whereBetween('session_date', [$today->toDateString(), $today->addDays(13)->toDateString()])
            ->where('status', 'tersedia')
            ->where('is_private', false)
            ->with(['film', 'bookings' => $activeBookings, 'occupiedBookings.film'])
            ->withSum(['occupiedBookings as viewer_count' => $activeBookings], 'seats_reserved')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn (Session $session): bool => $session->startsAt()->greaterThan(now()));

        $waitingQuorumSessions = $upcomingSessions
            ->filter(fn (Session $session): bool => ! $session->isConfirmed())
            ->values();

        $todayStr = $today->toDateString();
        $tomorrowStr = $tomorrow->toDateString();

        // 1. Hari ini
        $lowToday = $waitingQuorumSessions
            ->filter(fn (Session $s) => ((string) (is_string($s->session_date) ? $s->session_date : $s->session_date->toDateString())) === $todayStr)
            ->values();

        // 2. Besok
        $lowTomorrow = $waitingQuorumSessions
            ->filter(fn (Session $s) => ((string) (is_string($s->session_date) ? $s->session_date : $s->session_date->toDateString())) === $tomorrowStr)
            ->values();

        // 3. Mendatang
        $lowUpcoming = $waitingQuorumSessions
            ->filter(fn (Session $s) => ((string) (is_string($s->session_date) ? $s->session_date : $s->session_date->toDateString())) > $tomorrowStr && $s->startsAt()->lessThanOrEqualTo($warningWindowCutoff))
            ->values();

        // 4. Distant (beyond warning window)
        $distantCount = $waitingQuorumSessions
            ->filter(fn (Session $s) => $s->startsAt()->greaterThan($warningWindowCutoff))
            ->count();

        // "Hubungi pemesan" button visibility: ONLY if any session has bookings (> 0 viewers)
        $hasAnyBookedLowAttendance = $lowToday->contains(fn ($s) => (int) $s->viewer_count > 0 || $s->bookings->isNotEmpty())
            || $lowTomorrow->contains(fn ($s) => (int) $s->viewer_count > 0 || $s->bookings->isNotEmpty())
            || $lowUpcoming->contains(fn ($s) => (int) $s->viewer_count > 0 || $s->bookings->isNotEmpty());

        // Action Items (Perlu Tindakan) - ordered by urgency
        $waitingBookings = Booking::query()->where('status', 'menunggu_konfirmasi')->count();
        $expiringPromosCount = Promo::query()->where('is_active', true)->whereNotNull('end_date')->whereBetween('end_date', [now()->toDateString(), now()->addDays(3)->toDateString()])->count();
        $filmsWithoutPosterCount = Film::query()->where('is_active', true)->where(fn ($q) => $q->whereNull('poster_path')->orWhere('poster_path', ''))->count();
        $slidesWithoutTargetCount = Banner::query()->where('is_active', true)->where('target_type', 'none')->count();

        $rawMethods = $settings->rawPaymentMethods();
        $qrisSelected = in_array('qris', $rawMethods, true);
        $qrisWarning = $qrisSelected && (! $settings->hasValidQrisImage() || ! $settings->boolean('qris_enabled', true));
        $transferWarning = in_array('transfer', $rawMethods, true) && ! $settings->hasValidTransferAccounts();

        try {
            app(TelegramNotificationService::class)->flush();
        } catch (\Throwable) {
            // Fail safely
        }

        $urgentWaitingBookings = Booking::query()
            ->where('status', 'menunggu_konfirmasi')
            ->where('payment_proof_uploaded_at', '<=', now()->subHours(12))
            ->count();

        $actionItems = collect();

        $pendingRefundCashRewards = MemberReward::where('status', 'pending')
            ->where('settlement', 'refund_cash')
            ->with('member')
            ->get();
        if ($pendingRefundCashRewards->isNotEmpty()) {
            $pendingRefundMembers = $pendingRefundCashRewards->pluck('member')->filter()->unique('id');
            $refundCount = $pendingRefundCashRewards->count();

            if ($pendingRefundMembers->count() === 1) {
                $singleRefundMember = $pendingRefundMembers->first();
                $refundUrl = route('admin.members.show', [$singleRefundMember, 'tab' => 'rewards']);
                $refundTitle = $refundCount === 1
                    ? "Pengembalian tunai reward belum selesai ({$singleRefundMember->name})"
                    : "{$refundCount} pengembalian tunai reward belum selesai ({$singleRefundMember->name})";
            } else {
                $refundUrl = route('admin.members.index', ['reward_status' => 'refund_cash']);
                $names = $pendingRefundMembers->pluck('name')->take(2)->implode(', ');
                $suffix = $pendingRefundMembers->count() <= 2 ? " ({$names})" : " ({$pendingRefundMembers->count()} member)";
                $refundTitle = "{$refundCount} pengembalian tunai reward member belum selesai{$suffix}";
            }

            $actionItems->push([
                'type' => 'member_refund',
                'level' => 'critical',
                'urgency_score' => 0.5,
                'title' => $refundTitle,
                'url' => $refundUrl,
            ]);
        }

        $pendingRewardsQuery = MemberReward::where('status', 'pending');
        if ($pendingRefundCashRewards->isNotEmpty()) {
            $pendingRewardsQuery->where('settlement', '!=', 'refund_cash');
        }
        $pendingMemberRewards = $pendingRewardsQuery->with('member')->get();

        if ($pendingMemberRewards->isNotEmpty()) {
            $pendingMembers = $pendingMemberRewards->pluck('member')->filter()->unique('id');
            $rewardsCount = $pendingMemberRewards->count();

            if ($pendingMembers->count() === 1) {
                $singleMember = $pendingMembers->first();
                $targetUrl = route('admin.members.show', [$singleMember, 'tab' => 'rewards']);
                $title = $rewardsCount === 1
                    ? "1 reward member belum diselesaikan ({$singleMember->name})"
                    : "{$rewardsCount} reward member belum diselesaikan ({$singleMember->name})";
            } else {
                $targetUrl = route('admin.members.index', ['reward_status' => 'pending']);
                $names = $pendingMembers->pluck('name')->take(2)->implode(', ');
                $suffix = $pendingMembers->count() <= 2 ? " ({$names})" : " ({$pendingMembers->count()} member)";
                $title = "{$rewardsCount} reward member belum diselesaikan{$suffix}";
            }

            $actionItems->push([
                'type' => 'member_reward',
                'level' => 'warning',
                'urgency_score' => 1.5,
                'title' => $title,
                'url' => $targetUrl,
            ]);
        }

        // Deduplicate waiting bookings: combine urgent and total into a single crisp item
        if ($waitingBookings > 0) {
            if ($urgentWaitingBookings > 0) {
                $title = "MENDESAK: {$waitingBookings} booking menunggu konfirmasi ({$urgentWaitingBookings} > 12 jam)";
                $level = 'critical';
                $score = 0;
            } else {
                $title = "{$waitingBookings} booking online menunggu konfirmasi";
                $level = 'warning';
                $score = 1;
            }

            $actionItems->push([
                'type' => 'booking',
                'level' => $level,
                'urgency_score' => $score,
                'title' => $title,
                'url' => route('admin.bookings.index', ['status' => 'menunggu_konfirmasi']),
            ]);
        }

        // Bot feedback needing attention
        $unresolvedFeedbacksCount = BotFeedback::query()
            ->whereIn('status', ['FLAGGED', 'OPEN', 'PENDING'])
            ->count();
        if ($unresolvedFeedbacksCount > 0) {
            $actionItems->push([
                'type' => 'bot_feedback',
                'level' => 'warning',
                'urgency_score' => 1.2,
                'title' => "{$unresolvedFeedbacksCount} keluhan / pesan penonton butuh perhatian",
                'url' => route('admin.bot-feedbacks.index'),
            ]);
        }
        if ($qrisWarning) {
            $actionItems->push([
                'type' => 'qris',
                'level' => 'critical',
                'urgency_score' => 2,
                'title' => 'QRIS nonaktif: Metode bayar QRIS dinonaktifkan sementara (unggah gambar QRIS)',
                'url' => route('admin.settings.edit').'#qris-section',
            ]);
        }
        if ($transferWarning) {
            $actionItems->push([
                'type' => 'transfer',
                'level' => 'critical',
                'urgency_score' => 3,
                'title' => 'Metode Transfer belum memiliki rekening aktif',
                'url' => route('admin.settings.edit').'#transfer-section',
            ]);
        }
        if ($expiringPromosCount > 0) {
            $actionItems->push([
                'type' => 'promo',
                'level' => 'warning',
                'urgency_score' => 4,
                'title' => $expiringPromosCount.' promo berakhir dalam 3 hari ke depan',
                'url' => route('admin.promos.index', ['status' => 'aktif']),
            ]);
        }
        if ($filmsWithoutPosterCount > 0) {
            $actionItems->push([
                'type' => 'film',
                'level' => 'info',
                'urgency_score' => 5,
                'title' => $filmsWithoutPosterCount.' film belum memiliki poster',
                'url' => route('admin.films.index', ['filter' => 'tanpa_poster']),
            ]);
        }
        if ($slidesWithoutTargetCount > 0) {
            $actionItems->push([
                'type' => 'banner',
                'level' => 'info',
                'urgency_score' => 6,
                'title' => $slidesWithoutTargetCount.' slide hero tanpa link tujuan',
                'url' => route('admin.banners.index'),
            ]);
        }

        $actionItems = $actionItems->sortBy('urgency_score')->values();

        // Stats calculation
        $todayViewers = (int) $todaySessions->sum('viewer_count');
        $todayCapacity = (int) $todaySessions->sum(fn ($s) => $s->seatCapacity());
        $todayOccupancyPercent = $todayCapacity > 0 ? (int) round(($todayViewers / $todayCapacity) * 100) : 0;

        // Today Revenue and paid transactions
        $paidStatuses = ['terkonfirmasi', 'selesai', 'hadir'];
        $todayBookings = Booking::query()
            ->where(function ($q) use ($today) {
                $q->whereHas('session', fn ($sq) => $sq->where('session_date', $today->toDateString()))
                    ->orWhereBetween('created_at', [$today->startOfDay(), $today->endOfDay()]);
            })
            ->whereIn('status', $paidStatuses)
            ->get();

        $todayRevenue = (int) $todayBookings->sum('total_price');
        $todayPaidCount = (int) $todayBookings->count();

        // Recent Bookings Feed (Latest 6 bookings)
        $recentBookings = Booking::query()
            ->with(['film', 'session'])
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(function (Booking $b) {
                return [
                    'id' => $b->id,
                    'code' => $b->code,
                    'customer_name' => $b->customer_name ?: 'Pelanggan',
                    'whatsapp' => $b->whatsapp,
                    'film_title' => $b->film?->title ?: 'Film Belum Ditentukan',
                    'film_poster' => $b->film?->posterUrl(),
                    'session_time' => $b->session ? substr($b->session->start_time, 0, 5) : '-',
                    'session_date' => $b->session?->session_date ? (is_string($b->session->session_date) ? $b->session->session_date : $b->session->session_date->toDateString()) : '-',
                    'seats_reserved' => (int) ($b->seats_reserved ?: $b->pax ?: 1),
                    'package' => $b->package ?: 'reguler',
                    'payment_method' => $b->payment_method ?: 'kasir',
                    'has_proof' => ! empty($b->payment_proof_path),
                    'total_price' => (int) $b->total_price,
                    'status' => $b->status,
                    'created_at_human' => $b->created_at?->diffForHumans() ?: '-',
                ];
            });

        // Operational Status
        $availableSnacksCount = MenuItem::where('is_available', true)->count();
        $isSnacksEnabled = $settings->boolean('snacks_enabled', true);
        $telegramCsBotEnabled = $settings->boolean('telegram_cs_bot_enabled', true);
        $telegramCsBotUsername = $settings->string('telegram_cs_bot_username', 'SynemaXChat_bot');
        $telegramAdminConfigured = (bool) $settings->string('telegram_admin_chat_id');

        $data = [
            'todaySessions' => $todaySessions,
            'todayViewers' => $todayViewers,
            'todayCapacity' => $todayCapacity,
            'todayOccupancyPercent' => $todayOccupancyPercent,
            'waitingBookings' => $waitingBookings,
            'totalActionsCount' => $actionItems->count(),
            'actionItems' => $actionItems,
            'minimumViewers' => $minimumViewers,
            'warningWindowHours' => $warningWindowHours,
            'lowAttendanceSessions' => $waitingQuorumSessions,
            'waitingQuorumSessions' => $waitingQuorumSessions,
            'lowToday' => $lowToday,
            'lowTomorrow' => $lowTomorrow,
            'lowUpcoming' => $lowUpcoming,
            'distantCount' => $distantCount,
            'hasAnyBookedLowAttendance' => $hasAnyBookedLowAttendance,
            'settings' => $settings,
            // Quick Info Metrics & Feed
            'todayRevenue' => $todayRevenue,
            'todayPaidCount' => $todayPaidCount,
            'recentBookings' => $recentBookings,
            'unresolvedFeedbacksCount' => $unresolvedFeedbacksCount,
            'availableSnacksCount' => $availableSnacksCount,
            'isSnacksEnabled' => $isSnacksEnabled,
            'telegramCsBotEnabled' => $telegramCsBotEnabled,
            'telegramCsBotUsername' => $telegramCsBotUsername,
            'telegramAdminConfigured' => $telegramAdminConfigured,
        ];

        return Inertia::render('Admin/Dashboard', $data)
            ->withViewData($data);
    }

    public function updateDensity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'density' => ['required', 'in:nyaman,ringkas'],
        ]);

        $user = $request->user();
        if ($user) {
            $user->update(['display_density' => $data['density']]);
        }

        return response()->json(['status' => 'ok', 'density' => $data['density']]);
    }

    public function pollStatus(): JsonResponse
    {
        // Cache poll status for 15s with single aggregate query to reduce STB CPU & DB load
        $data = Cache::remember('admin_poll_status', 15, function () {
            $row = Booking::query()
                ->where('status', 'menunggu_konfirmasi')
                ->selectRaw('COUNT(*) as total, MAX(id) as max_id')
                ->first();

            return [
                'waiting_count' => (int) ($row?->total ?? 0),
                'latest_id' => $row?->max_id ? (int) $row->max_id : null,
            ];
        });

        return response()->json($data);
    }
}
