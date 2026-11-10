<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use App\Services\CinemaSettings;
use App\Support\WhatsAppNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $package = $this->input('package') ?: ($this->input('booking_type') === 'private' ? 'private' : ($this->input('booking_type') === 'couple' ? 'couple' : 'reguler'));
        $bookingType = $this->input('booking_type') ?: ($package === 'private' ? 'private' : ($package === 'couple' ? 'couple' : 'per_orang'));

        $snacks = $this->input('snacks');
        $cleanSnacks = [];
        if (is_array($snacks)) {
            foreach ($snacks as $itemId => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $rawQty = $item['qty'] ?? null;
                if ($rawQty === '' || $rawQty === null || ! is_numeric($rawQty)) {
                    continue;
                }
                $qty = (int) $rawQty;
                if ($qty <= 0) {
                    continue;
                }
                $cleanSnacks[$itemId] = [
                    'qty' => $qty,
                    'note' => isset($item['note']) ? (string) $item['note'] : null,
                ];
            }
        }

        $this->merge([
            'whatsapp' => WhatsAppNumber::normalize((string) $this->input('whatsapp')),
            'package' => $package,
            'booking_type' => $bookingType,
            'snacks' => ! empty($cleanSnacks) ? $cleanSnacks : null,
        ]);
    }

    public function rules(): array
    {
        $package = $this->input('package') ?: ($this->input('booking_type') === 'private' ? 'private' : ($this->input('booking_type') === 'couple' ? 'couple' : 'reguler'));
        $isRegular = ! in_array($package, ['couple', 'private'], true);
        $maxQty = (int) config('cinema.snack_max_qty_per_item', 10);
        $maxCapacity = max(6, app(CinemaSettings::class)->capacityPerSession());

        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'whatsapp' => ['required', 'string', 'regex:/^62\d{9,12}$/'],
            'pax' => [$isRegular ? 'required_without:people_count' : 'nullable', 'integer', "between:1,{$maxCapacity}"],
            'people_count' => ['nullable', 'integer', "between:1,{$maxCapacity}"],
            'booking_type' => ['nullable', 'in:per_orang,private,couple'],
            'package' => ['nullable', 'in:reguler,couple,private'],
            'film_id' => ['nullable', 'integer', 'exists:films,id'],
            'payment_method' => ['required', 'in:di_tempat,qris,transfer'],
            'payment_proof' => ['prohibited'],
            'notes' => ['nullable', 'string', 'max:300'],
            'snacks' => ['nullable', 'array', 'max:100'],
            'snacks.*' => ['array'],
            'snacks.*.qty' => ['nullable', 'integer', 'min:0', "max:{$maxQty}"],
            'snacks.*.note' => ['nullable', 'string', 'max:250'],
        ];
    }

    public function attributes(): array
    {
        $attributes = [
            'customer_name' => 'Nama pemesan',
            'whatsapp' => 'Nomor WhatsApp',
            'pax' => 'Jumlah orang',
            'people_count' => 'Jumlah orang',
            'booking_type' => 'Tipe booking',
            'package' => 'Paket booking',
            'film_id' => 'Pilihan film',
            'payment_method' => 'Metode pembayaran',
            'payment_proof' => 'Bukti pembayaran',
            'notes' => 'Catatan pemesanan',
            'snacks' => 'Pesanan jajan',
            'snacks.*.qty' => 'Jumlah jajan',
            'snacks.*.note' => 'Catatan jajan',
        ];

        try {
            $items = MenuItem::query()->select('id', 'name')->get();
            foreach ($items as $item) {
                $attributes["snacks.{$item->id}.qty"] = "Jumlah {$item->name}";
                $attributes["snacks.{$item->id}.note"] = "Catatan {$item->name}";
            }
        } catch (\Throwable) {
            // Abaikan bila tabel belum siap
        }

        return $attributes;
    }

    public function messages(): array
    {
        $maxCapacity = max(6, app(CinemaSettings::class)->capacityPerSession());

        return [
            'customer_name.required' => 'Nama pemesan wajib diisi.',
            'customer_name.max' => 'Nama pemesan maksimal 120 karakter.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Masukkan nomor WhatsApp Indonesia yang valid, misalnya 081234567890 atau +6281234567890.',
            'pax.required' => 'Pilih jumlah orang.',
            'pax.between' => "Jumlah orang harus antara 1 sampai {$maxCapacity}.",
            'booking_type.required' => 'Pilih tipe booking yang tersedia.',
            'booking_type.in' => 'Pilih tipe booking yang tersedia.',
            'film_id.exists' => 'Film yang dipilih sudah tidak tersedia.',
            'payment_method.required' => 'Pilih metode pembayaran yang tersedia.',
            'payment_method.in' => 'Pilih metode pembayaran yang tersedia.',
            'payment_proof.prohibited' => 'Unggah bukti pembayaran dilakukan di halaman pembayaran setelah booking dibuat.',
            'notes.max' => 'Catatan pemesanan maksimal 300 karakter.',
            'snacks.*.qty.min' => 'Jumlah jajan tidak boleh kurang dari nol.',
            'snacks.*.qty.max' => 'Jumlah jajan melebihi batas maksimal.',
            'snacks.*.note.max' => 'Catatan jajan maksimal 250 karakter.',
        ];
    }
}
