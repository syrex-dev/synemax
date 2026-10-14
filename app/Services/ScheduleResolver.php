<?php

namespace App\Services;

use App\Models\BlockedDate;
use App\Models\Holiday;
use App\Models\ScheduleTemplate;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class ScheduleResolver
{
    public function dayTypeFor(CarbonInterface $date): ?string
    {
        $dateString = $date->toDateString();

        if (BlockedDate::query()
            ->where('blocked_date', $dateString)
            ->where('scope_key', 'ALL')
            ->exists()) {
            return null;
        }

        if (Holiday::query()->where('holiday_date', $dateString)->exists()) {
            return 'libur';
        }

        return match ($date->dayOfWeek) {
            CarbonInterface::FRIDAY => 'jumat',
            CarbonInterface::SATURDAY, CarbonInterface::SUNDAY => 'weekend',
            default => 'weekday',
        };
    }

    public function templatesForDate(CarbonInterface $date): Collection
    {
        $dayType = $this->dayTypeFor($date);

        if ($dayType === null) {
            return new Collection;
        }

        return ScheduleTemplate::query()
            ->where('day_type', $dayType)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();
    }

    public function isSlotBlocked(CarbonInterface $date, string $startTime): bool
    {
        return BlockedDate::query()
            ->where('blocked_date', $date->toDateString())
            ->where('scope_key', substr($startTime, 0, 5))
            ->exists();
    }
}
