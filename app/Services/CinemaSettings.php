<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

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
    public function rawPaymentMethods(): array
    {
        $value = Setting::query()->find('enabled_payment_methods')?->value;
        $methods = $value === null ? ['di_tempat'] : json_decode($value, true);

        if (! is_array($methods)) {
            return [];
        }

        return array_values(array_intersect($methods, ['di_tempat', 'qris', 'transfer']));
    }

    public function hasValidQrisImage(): bool
    {
        $path = $this->string('qris_image_path');

        if ($path === '') {
            return false;
        }

        return Storage::disk('local')->exists($path);
    }

    public function qrisMerchantName(): string
    {
        return $this->string('qris_merchant_name', 'SynemaX Home Cinema');
    }

    public function qrisPaymentNotes(): string
    {
        return $this->string('qris_payment_notes');
    }

    public function qrisImageVersion(): string
    {
        return (string) $this->integer('qris_image_updated_at', 1);
    }

    /**
     * @return list<array{bank_name: string, account_number: string, account_holder: string}>
     */
    public function transferAccounts(): array
    {
        $raw = $this->string('transfer_accounts');
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, function ($item): bool {
            return is_array($item)
                && filled($item['bank_name'] ?? null)
                && filled($item['account_number'] ?? null)
                && filled($item['account_holder'] ?? null);
        }));
    }

    public function hasValidTransferAccounts(): bool
    {
        return count($this->transferAccounts()) > 0;
    }

    public function transferPaymentNotes(): string
    {
        return $this->string('transfer_payment_notes');
    }

    public function paymentTimeLimitMinutes(): int
    {
        return $this->integer('payment_time_limit_minutes', 30);
    }

    public function onlinePaymentCutoffMinutes(): int
    {
        return $this->integer('online_payment_cutoff_minutes', 20);
    }

    public function maxPaymentProofKb(): int
    {
        return $this->integer('max_payment_proof_kb', 4096);
    }

    public function cancellationCutoffHours(): int
    {
        return $this->integer('cancellation_cutoff_hours', 6);
    }

    /**
     * @return list<string>
     */
    public function enabledPaymentMethods(): array
    {
        $methods = $this->rawPaymentMethods();

        if (in_array('qris', $methods, true) && ! $this->hasValidQrisImage()) {
            $methods = array_values(array_diff($methods, ['qris']));
        }

        if (in_array('transfer', $methods, true) && ! $this->hasValidTransferAccounts()) {
            $methods = array_values(array_diff($methods, ['transfer']));
        }

        return $methods;
    }

    /**
     * @return list<string>
     */
    public function availablePaymentMethodsForSession(Session $session): array
    {
        $methods = $this->enabledPaymentMethods();
        $cutoff = $session->startsAt()->subMinutes($this->onlinePaymentCutoffMinutes());

        if (now()->greaterThanOrEqualTo($cutoff)) {
            $methods = array_values(array_intersect($methods, ['di_tempat']));
        }

        return $methods;
    }

    public function infoNobarEnabled(): bool
    {
        return $this->integer('info_nobar_enabled', 1) === 1;
    }
}
