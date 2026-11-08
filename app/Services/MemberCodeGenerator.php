<?php

namespace App\Services;

class MemberCodeGenerator
{
    public const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Generate unique Crockford Base32 Member Code with Check Digit:
     * Format: MBR-XXXX-XXXX-C
     */
    public static function generate(): string
    {
        $alphabetLen = strlen(self::ALPHABET);
        $payload = '';
        for ($i = 0; $i < 8; $i++) {
            $payload .= self::ALPHABET[random_int(0, $alphabetLen - 1)];
        }

        $checkDigit = self::computeCheckDigit($payload);

        $part1 = substr($payload, 0, 4);
        $part2 = substr($payload, 4, 4);

        return "MBR-{$part1}-{$part2}-{$checkDigit}";
    }

    /**
     * Compute check digit using weighted sum modulo 32.
     */
    public static function computeCheckDigit(string $payload): string
    {
        $clean = strtoupper(str_replace(['-', ' '], '', $payload));
        $sum = 0;
        for ($i = 0; $i < strlen($clean); $i++) {
            $char = $clean[$i];
            $val = strpos(self::ALPHABET, $char);
            if ($val === false) {
                $val = match ($char) {
                    'I', 'L' => 1,
                    'O' => 0,
                    default => 0,
                };
            }
            $sum += $val * ($i + 1);
        }

        return self::ALPHABET[$sum % 32];
    }

    /**
     * Verify if a member code is valid according to checksum and format.
     */
    public static function isValid(string $code): bool
    {
        $clean = self::normalize($code);

        if (! preg_match('/^MBR-([0-9A-Z]{4})-([0-9A-Z]{4})-([0-9A-Z])$/', $clean, $matches)) {
            return false;
        }

        $payload = $matches[1].$matches[2];
        $expectedCheck = self::computeCheckDigit($payload);

        return $matches[3] === $expectedCheck;
    }

    /**
     * Normalize member code string (handles lowercases, prefix).
     */
    public static function normalize(string $code): string
    {
        $clean = strtoupper(trim($code));
        if (str_starts_with($clean, 'SYNEMAX-MBR:')) {
            $clean = substr($clean, 12);
        }

        return $clean;
    }
}
