<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Film;
use App\Models\Holiday;
use App\Models\ScheduleTemplate;
use App\Models\Session;
use App\Services\BookingService;
use App\Services\ScheduleResolver;
use App\Services\SessionGenerationService;
use App\Services\SnackOrderService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        $allSessions = Session::query()
            ->whereBetween('session_date', [$today->toDateString(), $today->addDays(13)->toDateString()])
            ->with(['film', 'bookings.film', 'bookings.customerProfile', 'occupiedBookings'])
            ->withSum('occupiedBookings as occupied_seats', 'seats_reserved')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        $sessionsByDate = $allSessions->groupBy('session_date');

        return view('admin.schedule.index', [
            'templates' => ScheduleTemplate::query()->orderBy('day_type')->orderBy('start_time')->get(),
            'holidays' => Holiday::query()->where('holiday_date', '>=', $today->toDateString())->orderBy('holiday_date')->get(),
            'blockedDates' => BlockedDate::query()->where('blocked_date', '>=', $today->toDateString())->with('session')->orderBy('blocked_date')->get(),
            'sessions' => $allSessions,
            'sessionsByDate' => $sessionsByDate,
            'todayDate' => $today->toDateString(),
            'films' => Film::query()->where('is_active', true)->orderBy('title')->get(),
            'dayTypes' => [
                'weekday' => 'Senin–Kamis',
                'jumat' => 'Jumat',
                'weekend' => 'Sabtu–Minggu',
                'libur' => 'Hari libur',
            ],
        ]);
    }

    public function generate(SessionGenerationService $generator): RedirectResponse
    {
        $count = $generator->generateDays(14);

        return back()->with('status', "{$count} sesi baru berhasil dibuat untuk 14 hari ke depan.");
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'day_type' => ['required', Rule::in(['weekday', 'jumat', 'weekend', 'libur'])],
            'start_time' => [
                'required',
                'date_format:H:i',
                Rule::unique('schedule_templates')->where(
                    fn (Builder $query) => $query->where('day_type', $request->input('day_type')),
                ),
            ],
            'duration_minutes' => ['required', 'integer', 'between:30,600'],
        ], [
            'start_time.unique' => 'Jam tersebut sudah ada untuk tipe hari ini.',
            'duration_minutes.between' => 'Durasi sesi harus antara 30 sampai 600 menit.',
        ]);

        $this->assertNoOverlap($data['day_type'], $data['start_time'], (int) $data['duration_minutes']);
        ScheduleTemplate::query()->create([...$data, 'is_active' => true]);

        return back()->with('status', 'Jam sesi berhasil ditambahkan.');
    }

    public function updateTemplate(Request $request, ScheduleTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'day_type' => ['required', Rule::in(['weekday', 'jumat', 'weekend', 'libur'])],
            'start_time' => [
                'required',
                'date_format:H:i',
                Rule::unique('schedule_templates')
                    ->ignore($template->id)
                    ->where(fn (Builder $query) => $query->where('day_type', $request->input('day_type'))),
            ],
            'duration_minutes' => ['required', 'integer', 'between:30,600'],
            'is_active' => ['required', 'boolean'],
        ], [
            'start_time.unique' => 'Jam tersebut sudah ada untuk tipe hari ini.',
            'duration_minutes.between' => 'Durasi sesi harus antara 30 sampai 600 menit.',
        ]);

        if ($data['is_active']) {
            $this->assertNoOverlap(
                $data['day_type'],
                $data['start_time'],
                (int) $data['duration_minutes'],
                $template->id,
            );
        }

        $template->update($data);

        return back()->with('status', 'Template jadwal berhasil diperbarui.');
    }

    public function destroyTemplate(ScheduleTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('status', 'Template jadwal berhasil dihapus.');
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'holiday_date' => ['required', 'date', 'unique:holidays,holiday_date'],
            'name' => ['required', 'string', 'max:120'],
        ], [
            'holiday_date.unique' => 'Tanggal tersebut sudah terdaftar sebagai hari libur.',
        ]);

        Holiday::query()->create($data);

        return back()->with('status', 'Hari libur berhasil ditambahkan.');
    }

    public function destroyHoliday(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('status', 'Hari libur berhasil dihapus.');
    }

    public function storeBlockedDate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'blocked_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:180'],
        ], [
            'blocked_date.after_or_equal' => 'Tanggal penutupan tidak boleh sebelum hari ini.',
        ]);
        $scopeKey = $data['start_time'] ?? 'ALL';

        $exists = BlockedDate::query()
            ->where('blocked_date', $data['blocked_date'])
            ->where('scope_key', $scopeKey)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'blocked_date' => 'Tanggal atau sesi tersebut sudah ditutup.',
            ])->withInput();
        }

        DB::transaction(function () use ($data, $scopeKey): void {
            $sessionsQuery = Session::query()
                ->where('session_date', $data['blocked_date'])
                ->orderBy('id');

            if ($scopeKey !== 'ALL') {
                $sessionsQuery->where('start_time', $scopeKey);
            }

            $sessions = $sessionsQuery->lockForUpdate()->get();

            BlockedDate::query()->create([
                'blocked_date' => $data['blocked_date'],
                'scope_key' => $scopeKey,
                'session_id' => $sessions->first()?->id,
                'reason' => $data['reason'],
            ]);

            $sessions->where('status', 'tersedia')->each(function (Session $session): void {
                $session->update(['status' => 'ditutup']);
            });
        });

        return back()->with('status', 'Tanggal atau sesi berhasil ditutup.');
    }

    public function destroyBlockedDate(BlockedDate $blockedDate): RedirectResponse
    {
        $blockedDate->delete();

        return back()->with('status', 'Penutupan berhasil dihapus. Jika perlu, buka kembali sesinya dari daftar sesi.');
    }

    public function updateSession(Request $request, Session $session): RedirectResponse
    {
        $data = $request->validate([
            'film_id' => ['nullable', 'integer', 'exists:films,id'],
            'status' => ['required', Rule::in(['tersedia', 'ditutup'])],
        ]);

        DB::transaction(function () use ($session, $data): void {
            $lockedSession = Session::query()->lockForUpdate()->findOrFail($session->id);

            if ($data['film_id'] !== null && ! Film::query()->where('is_active', true)->find($data['film_id'])) {
                throw ValidationException::withMessages(['film_id' => 'Pilih film yang masih aktif.']);
            }

            if ($data['status'] === 'tersedia') {
                $date = CarbonImmutable::parse($lockedSession->session_date, config('app.timezone'));

                if (
                    app(ScheduleResolver::class)->dayTypeFor($date) === null
                    || app(ScheduleResolver::class)->isSlotBlocked($date, substr((string) $lockedSession->start_time, 0, 5))
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Hapus penutupan tanggal atau sesi terlebih dahulu.',
                    ]);
                }
            }

            $lockedSession->update($data);
        });

        return back()->with('status', 'Sesi berhasil diperbarui.');
    }

    public function toggleSessionStatus(Request $request, Session $session): JsonResponse|RedirectResponse
    {
        $targetStatus = $request->input('status');
        if (! in_array($targetStatus, ['tersedia', 'ditutup'], true)) {
            $targetStatus = $session->status === 'tersedia' ? 'ditutup' : 'tersedia';
        }

        $confirmed = $request->boolean('confirmed', false);

        if ($targetStatus === 'ditutup') {
            $activeBookings = $session->occupiedBookings()->get();
            $bookingCount = $activeBookings->count();

            if ($bookingCount > 0 && ! $confirmed) {
                return response()->json([
                    'requires_confirmation' => true,
                    'booking_count' => $bookingCount,
                    'active_bookings_count' => $bookingCount,
                    'session_id' => $session->id,
                    'message' => "Sesi ini memiliki {$bookingCount} booking aktif. Jika ditutup, seluruh booking yang terdampak akan dibatalkan otomatis dan pelanggan perlu dihubungi untuk penjadwalan ulang atau pengembalian dana (refund).",
                ]);
            }

            DB::transaction(function () use ($session, $activeBookings): void {
                $lockedSession = Session::query()->lockForUpdate()->findOrFail($session->id);
                $lockedSession->update(['status' => 'ditutup']);

                foreach ($activeBookings as $booking) {
                    $lockedBooking = Booking::query()->lockForUpdate()->find($booking->id);
                    if ($lockedBooking) {
                        $lockedBooking->update([
                            'status' => 'dibatalkan',
                            'cancelled_at' => now(),
                        ]);
                        app(SnackOrderService::class)->cancelItems($lockedBooking);
                    }
                }

                $lockedSession->syncPrivateStatus();
            });

            $msg = $bookingCount > 0
                ? "Sesi berhasil ditutup dan {$bookingCount} booking aktif dibatalkan."
                : 'Sesi berhasil ditutup.';

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'ok',
                    'session_status' => 'ditutup',
                    'message' => $msg,
                ]);
            }

            return back()->with('status', $msg);
        }

        // Reopen session
        $date = CarbonImmutable::parse($session->session_date, config('app.timezone'));
        if (
            app(ScheduleResolver::class)->dayTypeFor($date) === null
            || app(ScheduleResolver::class)->isSlotBlocked($date, substr((string) $session->start_time, 0, 5))
        ) {
            $err = 'Tanggal atau jam sesi ini sedang ditutup/diblokir.';
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $err], 422);
            }

            return back()->with('error', $err);
        }

        $session->update(['status' => 'tersedia']);
        $msg = 'Sesi berhasil dibuka kembali.';

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'session_status' => 'tersedia',
                'message' => $msg,
            ]);
        }

        return back()->with('status', $msg);
    }

    public function toggleDaySessions(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'action' => ['required', 'in:tutup,buka'],
            'confirmed' => ['nullable', 'boolean'],
        ]);

        $dateStr = $data['date'];
        $action = $data['action'];
        $confirmed = (bool) ($data['confirmed'] ?? false);

        $sessions = Session::query()
            ->where('session_date', $dateStr)
            ->with('occupiedBookings')
            ->get();

        if ($action === 'tutup') {
            $totalBookings = $sessions->sum(fn ($s) => $s->occupiedBookings->count());

            if ($totalBookings > 0 && ! $confirmed) {
                return response()->json([
                    'requires_confirmation' => true,
                    'booking_count' => $totalBookings,
                    'date' => $dateStr,
                    'message' => "Terdapat {$totalBookings} booking aktif pada tanggal ini. Jika seluruh hari ditutup, seluruh booking akan dibatalkan otomatis dan pelanggan perlu dihubungi untuk refund/reschedule.",
                ]);
            }

            DB::transaction(function () use ($sessions): void {
                foreach ($sessions as $session) {
                    $lockedSession = Session::query()->lockForUpdate()->find($session->id);
                    if ($lockedSession) {
                        $lockedSession->update(['status' => 'ditutup']);
                        foreach ($session->occupiedBookings as $booking) {
                            $lockedBooking = Booking::query()->lockForUpdate()->find($booking->id);
                            if ($lockedBooking) {
                                $lockedBooking->update([
                                    'status' => 'dibatalkan',
                                    'cancelled_at' => now(),
                                ]);
                                app(SnackOrderService::class)->cancelItems($lockedBooking);
                            }
                        }
                        $lockedSession->syncPrivateStatus();
                    }
                }
            });

            $msg = "Seluruh sesi pada tanggal {$dateStr} berhasil ditutup.";
            if ($request->wantsJson()) {
                return response()->json(['status' => 'ok', 'message' => $msg]);
            }

            return back()->with('status', $msg);
        }

        // Buka seluruh hari
        DB::transaction(function () use ($sessions): void {
            foreach ($sessions as $session) {
                $session->update(['status' => 'tersedia']);
            }
        });

        $msg = "Seluruh sesi pada tanggal {$dateStr} berhasil dibuka kembali.";
        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok', 'message' => $msg]);
        }

        return back()->with('status', $msg);
    }

    public function resolveSession(
        Request $request,
        Session $session,
        BookingService $bookingService,
    ): RedirectResponse|JsonResponse {
        $action = $request->input('action');

        if ($action === 'tayangkan_tetap') {
            $data = $request->validate([
                'film_id' => ['required', 'integer', 'exists:films,id'],
                'reason' => ['required', 'string', 'max:250'],
            ], [
                'film_id.required' => 'Pilih film yang akan ditayangkan tetap.',
                'reason.required' => 'Wajib menyertakan alasan singkat tayangkan tetap.',
            ]);

            DB::transaction(function () use ($session, $data) {
                $lockedSession = Session::query()->lockForUpdate()->findOrFail($session->id);
                $lockedSession->confirmAndLock((int) $data['film_id'], 'admin', $data['reason']);

                Booking::query()
                    ->where('session_id', $session->id)
                    ->where('status', '!=', 'dibatalkan')
                    ->where('film_id', '!=', (int) $data['film_id'])
                    ->update(['reselect_needed' => true]);
            });

            $msg = 'Sesi berhasil dikonfirmasi tayang tetap dengan film pilihan admin.';

            if ($request->wantsJson()) {
                return response()->json(['status' => 'ok', 'message' => $msg]);
            }

            return back()->with('status', $msg);
        }

        if ($action === 'pindahkan_booking') {
            $data = $request->validate([
                'target_session_id' => ['required', 'integer', 'exists:screening_sessions,id'],
            ], [
                'target_session_id.required' => 'Pilih sesi tujuan pemindahan booking.',
            ]);

            if ((int) $data['target_session_id'] === (int) $session->id) {
                return back()->with('error', 'Sesi tujuan harus berbeda dari sesi saat ini.');
            }

            $targetSession = Session::query()->findOrFail((int) $data['target_session_id']);
            $activeBookings = $session->occupiedBookings()->get();

            DB::transaction(function () use ($activeBookings, $targetSession, $bookingService) {
                foreach ($activeBookings as $b) {
                    $bookingService->reschedule($b, $targetSession, $b->film_id);
                }
            });

            $msg = count($activeBookings).' booking berhasil dipindahkan ke sesi tujuan.';

            if ($request->wantsJson()) {
                return response()->json(['status' => 'ok', 'message' => $msg]);
            }

            return back()->with('status', $msg);
        }

        if ($action === 'batalkan_sesi') {
            $data = $request->validate([
                'reason' => ['required', 'string', 'max:250'],
            ], [
                'reason.required' => 'Wajib menyertakan alasan pembatalan sesi.',
            ]);

            $activeBookings = $session->occupiedBookings()->get();

            DB::transaction(function () use ($session, $data, $activeBookings) {
                $lockedSession = Session::query()->lockForUpdate()->findOrFail($session->id);
                $lockedSession->update([
                    'status' => 'ditutup',
                    'resolution_notes' => 'Dibatalkan admin: '.$data['reason'],
                ]);

                foreach ($activeBookings as $b) {
                    $lockedBooking = Booking::query()->lockForUpdate()->find($b->id);
                    if ($lockedBooking) {
                        $isPaid = $lockedBooking->isPaid() || $lockedBooking->status === 'terkonfirmasi';
                        $lockedBooking->update([
                            'status' => 'dibatalkan',
                            'cancelled_at' => now(),
                            'cancellation_reason' => 'Dibatalkan admin: '.$data['reason'],
                            'refund_needed' => $isPaid,
                        ]);
                        app(SnackOrderService::class)->cancelItems($lockedBooking);
                    }
                }
                $lockedSession->syncPrivateStatus();
            });

            $msg = 'Sesi berhasil dibatalkan dan seluruh booking aktif telah diproses pembatalan.';

            if ($request->wantsJson()) {
                return response()->json(['status' => 'ok', 'message' => $msg]);
            }

            return back()->with('status', $msg);
        }

        return back()->with('error', 'Aksi penyelesaian sesi tidak valid.');
    }

    private function assertNoOverlap(string $dayType, string $startTime, int $duration, ?int $ignoreId = null): void
    {
        $start = $this->minutesFromTime($startTime);
        $end = $start + $duration;

        if ($end > 1440) {
            throw ValidationException::withMessages([
                'duration_minutes' => 'Satu sesi harus selesai sebelum tengah malam.',
            ]);
        }

        $overlaps = ScheduleTemplate::query()
            ->where('day_type', $dayType)
            ->where('is_active', true)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->get()
            ->contains(function (ScheduleTemplate $template) use ($start, $end): bool {
                $otherStart = $this->minutesFromTime(substr((string) $template->start_time, 0, 5));
                $otherEnd = $otherStart + $template->duration_minutes;

                return $start < $otherEnd && $otherStart < $end;
            });

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_time' => 'Jam sesi bertabrakan dengan sesi lain pada tipe hari yang sama.',
            ]);
        }
    }

    private function minutesFromTime(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hours * 60) + $minutes;
    }
}
