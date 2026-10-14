<?php

namespace App\Services;

use App\Models\Setting;

class CinemaSettings
{
    public function integer(string $key, int $default): int
    {
        $value = Setting::query()->find($key)?->value;

        return $value === null ? $default : (int) $value;
    }

    public function string(string $key, string $default = ''): string
    {
        return (string) (Setting::query()->find($key)?->value ?? $default);
    }

    /**
     * @return list<string>
     */
    public function enabledPaymentMethods(): array
    {
        $value = Setting::query()->find('enabled_payment_methods')?->value;
        $methods = $value === null ? ['di_tempat'] : json_decode($value, true);

        if (! is_array($methods)) {
            return [];
        }

        return array_values(array_intersect($methods, ['di_tempat', 'qris', 'transfer']));
    }
}
