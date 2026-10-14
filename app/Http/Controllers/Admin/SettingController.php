<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CinemaSettings;
use App\Support\WhatsAppNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ],
            'enabledMethods' => $settings->enabledPaymentMethods(),
        ]);
    }

    public function update(Request $request): RedirectResponse
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
        ], [
            'admin_whatsapp.regex' => 'Masukkan nomor WhatsApp admin yang valid, misalnya 081234567890.',
            'minimum_public_viewers.between' => 'Minimal penonton harus antara 1 sampai 6 orang.',
        ]);

        $paymentMethods = array_values(array_unique($data['payment_methods'] ?? []));
        unset($data['payment_methods']);

        foreach ($data as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        Setting::query()->updateOrCreate(
            ['key' => 'enabled_payment_methods'],
            ['value' => json_encode($paymentMethods, JSON_THROW_ON_ERROR)],
        );

        return back()->with('status', 'Pengaturan Home Cinema berhasil disimpan.');
    }
}
