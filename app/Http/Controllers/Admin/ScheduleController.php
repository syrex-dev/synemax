<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use App\Models\Film;
use App\Models\Holiday;
use App\Models\ScheduleTemplate;
use App\Models\Session;
use App\Services\ScheduleResolver;
use App\Services\SessionGenerationService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
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

        return view('admin.schedule.index', [
            'templates' => ScheduleTemplate::query()->orderBy('day_type')->orderBy('start_time')->get(),
            'holidays' => Holiday::query()->where('holiday_date', '>=', $today->toDateString())->orderBy('holiday_date')->get(),
            'blockedDates' => BlockedDate::query()->where('blocked_date', '>=', $today->toDateString())->with('session')->orderBy('blocked_date')->get(),
            'sessions' => Session::query()
                ->whereBetween('session_date', [$today->toDateString(), $today->addDays(13)->toDateString()])
                ->with('film')
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get(),
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
