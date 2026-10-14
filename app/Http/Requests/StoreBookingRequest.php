<?php

namespace App\Http\Requests;

use App\Support\WhatsAppNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'whatsapp' => WhatsAppNumber::normalize((string) $this->input('whatsapp')),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'whatsapp' => ['required', 'string', 'regex:/^62\d{9,12}$/'],
            'pax' => ['exclude_unless:booking_type,per_orang', 'required', 'integer', 'between:1,6'],
            'booking_type' => ['required', 'in:per_orang,private'],
            'film_id' => ['nullable', 'integer', 'exists:films,id'],
            'payment_method' => ['required', 'in:di_tempat,qris,transfer'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'snacks' => ['nullable', 'array', 'max:100'],
            'snacks.*' => ['required', 'array:qty,note'],
            'snacks.*.qty' => ['required', 'integer', 'min:0'],
            'snacks.*.note' => ['nullable', 'string', 'max:250'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Nama pemesan wajib diisi.',
            'customer_name.max' => 'Nama pemesan maksimal 120 karakter.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Masukkan nomor WhatsApp Indonesia yang valid, misalnya 081234567890 atau +6281234567890.',
            'pax.required' => 'Pilih jumlah orang.',
            'pax.between' => 'Jumlah orang harus antara 1 sampai 6.',
            'booking_type.required' => 'Pilih tipe booking yang tersedia.',
            'booking_type.in' => 'Pilih tipe booking yang tersedia.',
            'film_id.exists' => 'Film yang dipilih sudah tidak tersedia.',
            'payment_method.in' => 'Pilih metode pembayaran yang tersedia.',
            'payment_proof.image' => 'Bukti pembayaran harus berupa gambar.',
            'payment_proof.mimes' => 'Bukti pembayaran harus JPG, PNG, atau WebP.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 2 MB.',
            'snacks.*.qty.min' => 'Jumlah jajan tidak boleh kurang dari nol.',
            'snacks.*.note.max' => 'Catatan jajan maksimal 250 karakter.',
        ];
    }
}
