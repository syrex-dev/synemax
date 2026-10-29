<?php

namespace App\Http\Requests;

use App\Support\WhatsAppNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $package = $this->input('package') ?: ($this->input('booking_type') === 'private' ? 'private' : ($this->input('booking_type') === 'couple' ? 'couple' : 'reguler'));
        $bookingType = $this->input('booking_type') ?: ($package === 'private' ? 'private' : ($package === 'couple' ? 'couple' : 'per_orang'));

        $this->merge([
            'whatsapp' => WhatsAppNumber::normalize((string) $this->input('whatsapp')),
            'package' => $package,
            'booking_type' => $bookingType,
        ]);
    }

    public function rules(): array
    {
        $package = $this->input('package') ?: ($this->input('booking_type') === 'private' ? 'private' : ($this->input('booking_type') === 'couple' ? 'couple' : 'reguler'));
        $isRegular = ! in_array($package, ['couple', 'private'], true);

        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'whatsapp' => ['required', 'string', 'regex:/^62\d{9,12}$/'],
            'pax' => [$isRegular ? 'required_without:people_count' : 'nullable', 'integer', 'between:1,6'],
            'people_count' => ['nullable', 'integer', 'between:1,6'],
            'booking_type' => ['nullable', 'in:per_orang,private,couple'],
            'package' => ['nullable', 'in:reguler,couple,private'],
            'film_id' => ['nullable', 'integer', 'exists:films,id'],
            'payment_method' => ['required', 'in:di_tempat,qris,transfer'],
            'payment_proof' => ['prohibited'],
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
            'payment_proof.prohibited' => 'Unggah bukti pembayaran dilakukan di halaman pembayaran setelah booking dibuat.',
            'snacks.*.qty.min' => 'Jumlah jajan tidak boleh kurang dari nol.',
            'snacks.*.note.max' => 'Catatan jajan maksimal 250 karakter.',
        ];
    }
}
