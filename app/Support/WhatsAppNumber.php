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

        return $number;
    }

    public static function isValid(string $number): bool
    {
        return preg_match('/^62\d{9,12}$/', self::normalize($number)) === 1;
    }
}
