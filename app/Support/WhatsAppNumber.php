<?php

namespace App\Support;

class WhatsAppNumber
{
    public static function normalize(string $number): string
    {
        $number = preg_replace('/[\s().-]+/', '', trim($number)) ?? '';

        if (str_starts_with($number, '08')) {
            return '62'.substr($number, 1);
        }

        if (str_starts_with($number, '+62')) {
            return substr($number, 1);
        }

        if (str_starts_with($number, '8')) {
            return '62'.$number;
        }

        return $number;
    }

    public static function isValid(string $number): bool
    {
        return preg_match('/^62\d{9,12}$/', self::normalize($number)) === 1;
    }

    public static function mask(string $number): string
    {
        $norm = self::normalize($number);
        if (strlen($norm) < 8) {
            return $number;
        }

        $prefix = '+'.substr($norm, 0, 2).' '.substr($norm, 2, 3);
        $suffix = substr($norm, -4);

        return $prefix.'-••••-'.$suffix;
    }

    public static function format(string $number): string
    {
        $norm = self::normalize($number);
        if (strlen($norm) < 8) {
            return $number;
        }

        return '+'.substr($norm, 0, 2).' '.substr($norm, 2, 3).'-'.substr($norm, 5, 4).'-'.substr($norm, 9);
    }
}
