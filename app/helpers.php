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

if (! function_exists('format_rentang_jam_id')) {
    /**
     * Format time range in Indonesian style: e.g. "10.00 - 11.45 WIB"
     */
    function format_rentang_jam_id(mixed $startTime, mixed $endTime, bool $withWib = true): string
    {
        $start = format_jam_id($startTime, false);
        $end = format_jam_id($endTime, false);

        if ($start === '-' && $end === '-') {
            return '-';
        }
        if ($end === '-' || $end === '') {
            return $withWib ? $start.' WIB' : $start;
        }

        return $start.' - '.$end.($withWib ? ' WIB' : '');
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

if (! function_exists('format_phone_id')) {
    /**
     * Format Indonesian telephone / WhatsApp number into standard readable string:
     * e.g. "6285169730960" -> "+62 851-6973-0960"
     *      "081234567890"  -> "+62 812-3456-7890"
     */
    function format_phone_id(?string $phone): string
    {
        if (empty($phone)) {
            return '-';
        }

        $digits = preg_replace('/[^\d]/', '', (string) $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62') && strlen($digits) >= 9) {
            $digits = '62'.$digits;
        }

        if (str_starts_with($digits, '62')) {
            $subscriber = substr($digits, 2);
            $len = strlen($subscriber);

            if ($len === 10) {
                return '+62 '.substr($subscriber, 0, 3).'-'.substr($subscriber, 3, 3).'-'.substr($subscriber, 6);
            }
            if ($len === 11) {
                return '+62 '.substr($subscriber, 0, 3).'-'.substr($subscriber, 3, 4).'-'.substr($subscriber, 7);
            }
            if ($len >= 12) {
                return '+62 '.substr($subscriber, 0, 3).'-'.substr($subscriber, 3, 4).'-'.substr($subscriber, 7);
            }
            if ($len >= 8) {
                return '+62 '.substr($subscriber, 0, 3).'-'.substr($subscriber, 3, 4).'-'.substr($subscriber, 7);
            }

            return '+62 '.$subscriber;
        }

        return '+'.$digits;
    }
}

if (! function_exists('cinema_facts')) {
    /**
     * Get unified capacity and starting price facts for cinema.
     * Synchronized across homepage chips, desktop footer, and mobile footer.
     *
     * @return array{capacity: string, price: string, combined: string, combined_html: string}
     */
    function cinema_facts(): array
    {
        $settings = app(CinemaSettings::class);
        $capacity = $settings->capacityPerSession();
        $minPrice = $settings->startingPrice();

        $capacityText = "{$capacity} kursi per sesi";
        $priceText = 'Mulai Rp '.number_format($minPrice, 0, ',', '.');

        return [
            'capacity' => $capacityText,
            'price' => $priceText,
            'combined' => $capacityText.' • '.$priceText,
            'combined_html' => $capacityText.' &bull; '.$priceText,
        ];
    }
}

if (! function_exists('cinema_capacity_text')) {
    function cinema_capacity_text(): string
    {
        return cinema_facts()['capacity'];
    }
}

if (! function_exists('cinema_starting_price_text')) {
    function cinema_starting_price_text(): string
    {
        return cinema_facts()['price'];
    }
}
