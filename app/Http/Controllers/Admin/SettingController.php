<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CinemaSettings;
use App\Support\WhatsAppNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(CinemaSettings $settings): View
    {
        return view('admin.settings.edit', [
            'settings' => [
                'weekday_price' => $settings->integer('weekday_price', 5000),
                'weekend_price' => $settings->integer('weekend_price', 6000),
                'holiday_price' => $settings->integer('holiday_price', 6000),
                'private_price' => $settings->integer('private_price', 36000),
                'minimum_public_viewers' => $settings->integer('minimum_public_viewers', 3),
                'booking_cutoff_minutes' => $settings->integer('booking_cutoff_minutes', 30),
                'cancellation_cutoff_hours' => $settings->integer('cancellation_cutoff_hours', 6),
                'admin_whatsapp' => $settings->string('admin_whatsapp'),
                'payment_instructions' => $settings->string('payment_instructions'),
                'electricity_cost_per_session' => $settings->integer('electricity_cost_per_session', 0),
                'snacks_enabled' => $settings->integer('snacks_enabled', 1),
                'snack_prep_buffer_minutes' => $settings->integer('snack_prep_buffer_minutes', 15),
                'snack_max_qty_per_item' => $settings->integer('snack_max_qty_per_item', 10),
                'qris_image_path' => $settings->string('qris_image_path'),
                'has_qris_image' => $settings->hasValidQrisImage(),
                'qris_merchant_name' => $settings->qrisMerchantName(),
                'qris_payment_notes' => $settings->qrisPaymentNotes(),
                'qris_image_version' => $settings->qrisImageVersion(),
                'transfer_accounts' => $settings->transferAccounts(),
                'has_transfer_accounts' => $settings->hasValidTransferAccounts(),
                'transfer_payment_notes' => $settings->transferPaymentNotes(),
                'payment_time_limit_minutes' => $settings->paymentTimeLimitMinutes(),
                'online_payment_cutoff_minutes' => $settings->onlinePaymentCutoffMinutes(),
                'max_payment_proof_kb' => $settings->maxPaymentProofKb(),
            ],
            'enabledMethods' => $settings->rawPaymentMethods(),
            'qrisWarning' => in_array('qris', $settings->rawPaymentMethods(), true) && ! $settings->hasValidQrisImage(),
            'transferWarning' => in_array('transfer', $settings->rawPaymentMethods(), true) && ! $settings->hasValidTransferAccounts(),
        ]);
    }

    public function update(Request $request, CinemaSettings $settings): RedirectResponse
    {
        $request->merge([
            'admin_whatsapp' => WhatsAppNumber::normalize((string) $request->input('admin_whatsapp')),
            'snacks_enabled' => $request->boolean('snacks_enabled') ? 1 : 0,
        ]);

        $data = $request->validate([
            'weekday_price' => ['required', 'integer', 'between:500,1000000'],
            'weekend_price' => ['required', 'integer', 'between:500,1000000'],
            'holiday_price' => ['required', 'integer', 'between:500,1000000'],
            'private_price' => ['required', 'integer', 'between:1000,6000000'],
            'minimum_public_viewers' => ['required', 'integer', 'between:1,6'],
            'booking_cutoff_minutes' => ['required', 'integer', 'between:0,720'],
            'cancellation_cutoff_hours' => ['required', 'integer', 'between:0,168'],
            'admin_whatsapp' => ['required', 'string', 'regex:/^62\d{9,12}$/'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'electricity_cost_per_session' => ['required', 'integer', 'between:0,10000000'],
            'snacks_enabled' => ['required', 'boolean'],
            'snack_prep_buffer_minutes' => ['required', 'integer', 'between:0,360'],
            'snack_max_qty_per_item' => ['required', 'integer', 'between:1,100'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['required', 'in:di_tempat,qris,transfer'],
            'qris_image' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=300,min_height=300'],
            'qris_merchant_name' => ['nullable', 'string', 'max:120'],
            'qris_payment_notes' => ['nullable', 'string', 'max:2000'],
            'delete_qris_image' => ['nullable', 'boolean'],
            'transfer_accounts' => ['nullable', 'array'],
            'transfer_accounts.*.bank_name' => ['nullable', 'string', 'max:50'],
            'transfer_accounts.*.account_number' => ['nullable', 'string', 'max:50'],
            'transfer_accounts.*.account_holder' => ['nullable', 'string', 'max:100'],
            'transfer_payment_notes' => ['nullable', 'string', 'max:2000'],
            'payment_time_limit_minutes' => ['nullable', 'integer', 'between:5,180'],
            'online_payment_cutoff_minutes' => ['nullable', 'integer', 'between:0,180'],
            'max_payment_proof_kb' => ['nullable', 'integer', 'between:512,10240'],
        ], [
            'admin_whatsapp.regex' => 'Masukkan nomor WhatsApp admin yang valid, misalnya 081234567890.',
            'minimum_public_viewers.between' => 'Minimal penonton harus antara 1 sampai 6 orang.',
            'qris_image.image' => 'File QRIS harus berupa gambar yang valid.',
            'qris_image.mimes' => 'Format gambar QRIS harus PNG, JPG, JPEG, atau WEBP (SVG tidak diizinkan).',
            'qris_image.max' => 'Ukuran file gambar QRIS maksimal 2 MB.',
            'qris_image.dimensions' => 'Ukuran gambar QRIS minimal 300 × 300 piksel.',
            'qris_merchant_name.max' => 'Nama merchant QRIS maksimal 120 karakter.',
            'qris_payment_notes.max' => 'Catatan pembayaran maksimal 2000 karakter.',
            'payment_time_limit_minutes.between' => 'Batas waktu pembayaran harus antara 5 sampai 180 menit.',
            'online_payment_cutoff_minutes.between' => 'Cutoff pembayaran online harus antara 0 sampai 180 menit.',
            'max_payment_proof_kb.between' => 'Batas ukuran bukti pembayaran harus antara 512 KB sampai 10240 KB (10 MB).',
        ]);

        if ($request->hasFile('qris_image')) {
            $mime = $request->file('qris_image')->getMimeType();
            if (! in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
                throw ValidationException::withMessages([
                    'qris_image' => 'Format gambar QRIS harus PNG, JPG, JPEG, atau WEBP (SVG tidak diizinkan).',
                ]);
            }
        }

        $paymentMethods = array_values(array_unique($data['payment_methods'] ?? []));
        unset($data['payment_methods']);

        $deleteQris = $request->boolean('delete_qris_image');
        $hasNewQris = $request->hasFile('qris_image');
        $hasExistingQris = $settings->hasValidQrisImage();

        if (in_array('qris', $paymentMethods, true)) {
            if ($deleteQris || (! $hasNewQris && ! $hasExistingQris)) {
                throw ValidationException::withMessages([
                    'payment_methods' => 'Metode QRIS tidak dapat diaktifkan sebelum gambar QRIS diunggah.',
                ]);
            }
        }

        // Process transfer accounts
        $rawTransferAccounts = $data['transfer_accounts'] ?? [];
        $cleanTransferAccounts = [];
        if (is_array($rawTransferAccounts)) {
            foreach ($rawTransferAccounts as $item) {
                $bankName = trim((string) ($item['bank_name'] ?? ''));
                $accNum = trim((string) ($item['account_number'] ?? ''));
                $accHolder = trim((string) ($item['account_holder'] ?? ''));
                if ($bankName !== '' && $accNum !== '' && $accHolder !== '') {
                    $cleanTransferAccounts[] = [
                        'bank_name' => $bankName,
                        'account_number' => $accNum,
                        'account_holder' => $accHolder,
                    ];
                }
            }
        }
        unset($data['transfer_accounts']);

        if (in_array('transfer', $paymentMethods, true) && count($cleanTransferAccounts) === 0) {
            throw ValidationException::withMessages([
                'payment_methods' => 'Metode Transfer tidak dapat diaktifkan sebelum minimal satu rekening bank/e-wallet ditambahkan.',
            ]);
        }

        Setting::query()->updateOrCreate(
            ['key' => 'transfer_accounts'],
            ['value' => json_encode($cleanTransferAccounts, JSON_THROW_ON_ERROR)],
        );

        if ($deleteQris) {
            $oldPath = $settings->string('qris_image_path');
            if ($oldPath !== '' && Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
            Setting::query()->where('key', 'qris_image_path')->delete();
            Setting::query()->updateOrCreate(['key' => 'qris_image_updated_at'], ['value' => (string) now()->timestamp]);
            $paymentMethods = array_values(array_diff($paymentMethods, ['qris']));
        } elseif ($hasNewQris) {
            $qrisPath = $this->processAndStoreQrisImage($request->file('qris_image'), $settings);
            Setting::query()->updateOrCreate(['key' => 'qris_image_path'], ['value' => $qrisPath]);
            Setting::query()->updateOrCreate(['key' => 'qris_image_updated_at'], ['value' => (string) now()->timestamp]);
        }
        unset($data['qris_image'], $data['delete_qris_image']);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                Setting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
            }
        }

        Setting::query()->updateOrCreate(
            ['key' => 'enabled_payment_methods'],
            ['value' => json_encode($paymentMethods, JSON_THROW_ON_ERROR)],
        );

        return back()->with('status', 'Pengaturan Home Cinema berhasil disimpan.');
    }

    public function destroyQris(CinemaSettings $settings): RedirectResponse
    {
        $oldPath = $settings->string('qris_image_path');
        if ($oldPath !== '' && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        Setting::query()->where('key', 'qris_image_path')->delete();
        Setting::query()->updateOrCreate(['key' => 'qris_image_updated_at'], ['value' => (string) now()->timestamp]);

        $methods = array_values(array_diff($settings->rawPaymentMethods(), ['qris']));
        Setting::query()->updateOrCreate(
            ['key' => 'enabled_payment_methods'],
            ['value' => json_encode($methods, JSON_THROW_ON_ERROR)],
        );

        return back()->with('status', 'Gambar QRIS berhasil dihapus dan opsi QRIS dinonaktifkan.');
    }

    private function processAndStoreQrisImage(UploadedFile $file, CinemaSettings $settings): string
    {
        $extension = match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = 'qris_'.Str::random(32).'.'.$extension;
        $targetPath = 'qris/'.$filename;

        [$width, $height] = getimagesize($file->getPathname());
        $maxDimension = 1600;

        $image = match ($extension) {
            'png' => imagecreatefrompng($file->getPathname()),
            'webp' => imagecreatefromwebp($file->getPathname()),
            default => imagecreatefromjpeg($file->getPathname()),
        };

        if ($image === false) {
            throw new \RuntimeException('Gagal membaca gambar QRIS.');
        }

        if (in_array($extension, ['png', 'webp'], true)) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if (in_array($extension, ['png', 'webp'], true)) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'qris_');
        match ($extension) {
            'png' => imagepng($image, $tempPath, 9),
            'webp' => imagewebp($image, $tempPath, 100),
            default => imagejpeg($image, $tempPath, 100),
        };
        imagedestroy($image);

        $oldPath = $settings->string('qris_image_path');
        if ($oldPath !== '' && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        Storage::disk('local')->put($targetPath, file_get_contents($tempPath));
        @unlink($tempPath);

        return $targetPath;
    }
}
