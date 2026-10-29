<?php

use App\Services\CinemaSettings;
use Carbon\Carbon;
use Carbon\CarbonInterface;

if (! function_exists('setting')) {
    /**
     * Get a setting value by key with optional default.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(CinemaSettings::class)->get($key, $default);
    }
}

if (! function_exists('format_jam_id')) {
    /**
     * Format time in Indonesian style: e.g. "10.00 WIB" or "10.00"
     */
    function format_jam_id(mixed $time, bool $withWib = true): string
    {
        if (empty($time)) {
            return '-';
        }

        if ($time instanceof DateTimeInterface) {
            $formatted = $time->format('H.i');
        } else {
            $formatted = str_replace(':', '.', substr((string) $time, 0, 5));
        }

        return $withWib ? $formatted.' WIB' : $formatted;
    }
}

if (! function_exists('format_tanggal_id')) {
    /**
     * Format full date in Indonesian style: e.g. "Rab, 28 Okt 2026"
     */
    function format_tanggal_id(mixed $date): string
    {
        if (empty($date)) {
            return '-';
        }

        $carbon = $date instanceof CarbonInterface
            ? $date
            : Carbon::parse($date, config('app.timezone'));

        return $carbon->locale('id')->translatedFormat('D, j M Y');
    }
}

if (! function_exists('format_tanggal_pendek_id')) {
    /**
     * Format short date in Indonesian style: e.g. "28 Okt", "1 Nov"
     */
    function format_tanggal_pendek_id(mixed $date): string
    {
        if (empty($date)) {
            return '-';
        }

        $carbon = $date instanceof CarbonInterface
            ? $date
            : Carbon::parse($date, config('app.timezone'));

        return $carbon->locale('id')->translatedFormat('j M');
    }
}

if (! function_exists('format_hari_tanggal_id')) {
    /**
     * Format full day and date in Indonesian style: e.g. "Rabu, 28 Oktober 2026"
     */
    function format_hari_tanggal_id(mixed $date): string
    {
        if (empty($date)) {
            return '-';
        }

        $carbon = $date instanceof CarbonInterface
            ? $date
            : Carbon::parse($date, config('app.timezone'));

        return $carbon->locale('id')->translatedFormat('l, j F Y');
    }
}
