<?php

namespace App\Services;

use App\Models\ScheduleTemplate;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SessionGenerationService
{
    public function __construct(
        private readonly ScheduleResolver $scheduleResolver,
    ) {}

    public function generateDays(int $days = 14, ?CarbonImmutable $fromDate = null): int
    {
        if ($days < 1 || $days > 90) {
            throw new InvalidArgumentException('Jumlah hari generate harus antara 1 sampai 90.');
        }

        $fromDate ??= CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $createdCount = 0;

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $fromDate->addDays($offset)->startOfDay();

            DB::transaction(function () use ($date, &$createdCount): void {
                $dayType = $this->scheduleResolver->dayTypeFor($date);

                if ($dayType === null) {
                    $this->closeSessionsForDate($date);

                    return;
                }

                $templates = ScheduleTemplate::query()
                    ->where('day_type', $dayType)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $expectedStartTimes = [];

                foreach ($templates as $template) {
                    $startTime = $this->createSessionIfAvailable($date, $template, $createdCount);

                    if ($startTime !== null) {
                        $expectedStartTimes[] = $startTime;
                    }
                }

                $this->closeSessionsOutsideTemplates($date, $expectedStartTimes);
            });
        }

        return $createdCount;
    }

    private function createSessionIfAvailable(
        CarbonImmutable $date,
        ScheduleTemplate $template,
        int &$createdCount,
    ): ?string {
        $startTime = substr((string) $template->start_time, 0, 5);

        if ($this->scheduleResolver->isSlotBlocked($date, $startTime)) {
            Session::query()
                ->where('session_date', $date->toDateString())
                ->where('start_time', $startTime)
                ->where('status', 'tersedia')
                ->update(['status' => 'ditutup', 'updated_at' => now()]);

            return null;
        }

        $startsAt = CarbonImmutable::parse(
            $date->toDateString().' '.$template->start_time,
            config('app.timezone'),
        );
        $endTime = $startsAt->addMinutes($template->duration_minutes)->format('H:i:s');

        $session = Session::query()->firstOrCreate(
            [
                'session_date' => $date->toDateString(),
                'start_time' => $startTime,
            ],
            [
                'end_time' => $endTime,
                'capacity' => 6,
                'status' => 'tersedia',
            ],
        );

        if ($session->wasRecentlyCreated) {
            $createdCount++;
        } elseif ($session->status === 'tersedia') {
            $lockedSession = Session::query()->lockForUpdate()->findOrFail($session->getKey());

            if (
                $lockedSession->status === 'tersedia'
                && ! $lockedSession->occupiedBookings()->exists()
                && substr((string) $lockedSession->end_time, 0, 8) !== $endTime
            ) {
                $lockedSession->update(['end_time' => $endTime]);
            }
        }

        return $startTime;
    }

    private function closeSessionsForDate(CarbonImmutable $date): void
    {
        Session::query()
            ->where('session_date', $date->toDateString())
            ->where('status', 'tersedia')
            ->update(['status' => 'ditutup', 'updated_at' => now()]);
    }

    /**
     * @param  list<string>  $expectedStartTimes
     */
    private function closeSessionsOutsideTemplates(CarbonImmutable $date, array $expectedStartTimes): void
    {
        $query = Session::query()
            ->where('session_date', $date->toDateString())
            ->where('status', 'tersedia');

        if ($expectedStartTimes !== []) {
            $query->whereNotIn('start_time', $expectedStartTimes);
        }

        $query->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->each(fn (Session $session) => $session->update(['status' => 'ditutup']));
    }
}
