<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Session;
use App\Services\CheckInService;
use App\Services\CinemaSettings;
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
     * Cari tiket berdasarkan QR atau input kode (POST /admin/scan/cari).
     */
    public function lookup(Request $request, CheckInService $checkIn): JsonResponse
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

        $result = $checkIn->lookup($data['code'], $data['session_id'] ?? null);

        if ($result['status'] === 'not_found') {
            RateLimiter::hit($failedKey, 60);
        } else {
            RateLimiter::clear($failedKey);
        }

        return response()->json($result)
            ->header('Cache-Control', 'no-store');
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
                $chip = ['label' => 'Hadir', 'token' => 'success'];
            } elseif ($flow === 'aktif') {
                $belumCount++;
                $chip = ['label' => 'Siap Hadir', 'token' => 'success'];
            } else {
                $perluPerhatianCount++;
                $chip = match ($flow) {
                    'menunggu_bayar' => ['label' => 'Belum Bayar', 'token' => 'danger'],
                    'diproses' => ['label' => 'Diproses', 'token' => 'warning'],
                    'menunggu_penonton' => ['label' => 'Menunggu', 'token' => 'warning'],
                    'dibatalkan' => ['label' => 'Batal', 'token' => 'danger'],
                    'kedaluwarsa' => ['label' => 'Kedaluwarsa', 'token' => 'danger'],
                    default => ['label' => ucfirst($b->status), 'token' => 'neutral'],
                };
            }

            // Kode dengan 4 digit terakhir tebal
            $codeLength = strlen($b->code);
            $codePrefix = substr($b->code, 0, max(0, $codeLength - 4));
            $codeSuffix = substr($b->code, -4);

            return [
                'id' => $b->id,
                'code' => $b->code,
                'code_prefix' => $codePrefix,
                'code_suffix' => $codeSuffix,
                'customer_name' => $b->customer_name,
                'people_count' => (int) ($b->people_count ?? $b->seats_reserved ?? 1),
                'session_time' => format_cinema_time($b->session?->start_time ?? ''),
                'chip' => $chip,
                'is_hadir' => $isHadir,
            ];
        })->values()->all();

        return response()->json([
            'summary' => [
                'hadir' => $hadirCount,
                'belum' => $belumCount,
                'perlu_perhatian' => $perluPerhatianCount,
                'text' => "Hadir {$hadirCount} | Belum {$belumCount} | Perlu perhatian {$perluPerhatianCount}",
            ],
            'items' => $items,
        ])->header('Cache-Control', 'no-store');
    }
}
