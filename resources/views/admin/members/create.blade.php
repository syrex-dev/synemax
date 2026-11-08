@extends('layouts.admin')

@section('title', 'Tambah Member Baru - Panel Admin SynemaX')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-text sm:text-2xl">Tambah Member Baru</h1>
            <p class="text-xs text-text-muted mt-1">Daftarkan pelanggan baru ke program loyalitas kunjungan berhadiah.</p>
        </div>
        <a
            href="{{ route('admin.members.index') }}"
            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
        >
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            <span>Kembali</span>
        </a>
    </div>

    {{-- Peringatan In-App Nama Ganda --}}
    @if (session('duplicate_name_warning'))
        <div role="alert" class="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-5 space-y-3">
            <div class="flex items-start gap-3">
                <svg class="size-5 shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="space-y-1">
                    <p class="text-sm font-bold text-amber-600 dark:text-amber-400">Peringatan Nama Member Sama</p>
                    <p class="text-xs text-text-muted leading-relaxed">
                        Sudah ada member bernama sama <strong>{{ session('duplicate_name_warning')['name'] }}</strong> dengan ID <strong>{{ session('duplicate_name_warning')['existing_id'] }}</strong>. Apakah Anda yakin ingin tetap membuat member baru dengan nama ini?
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.members.store') }}" enctype="multipart/form-data" class="pt-2">
                @csrf
                <input type="hidden" name="name" value="{{ old('name') }}">
                <input type="hidden" name="nickname" value="{{ old('nickname') }}">
                <input type="hidden" name="notes" value="{{ old('notes') }}">
                <input type="hidden" name="opening_visits" value="{{ old('opening_visits', 0) }}">
                <input type="hidden" name="status" value="{{ old('status', 'aktif') }}">
                <input type="hidden" name="ignore_duplicate_name" value="1">
                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        class="min-h-[40px] rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-black hover:bg-amber-400 transition"
                    >
                        Tetap Buat Member Baru
                    </button>
                    <a
                        href="{{ route('admin.members.create') }}"
                        class="min-h-[40px] inline-flex items-center rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
                    >
                        Ubah Nama
                    </a>
                </div>
            </form>
        </div>
    @endif

    {{-- Error Summary --}}
    @if ($errors->any())
        <div role="alert" class="rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-xs text-red-600 dark:text-red-400 space-y-1">
            <p class="font-bold">Ada isian yang perlu diperiksa:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.members.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- Nama Member --}}
            <div>
                <label for="name" class="block text-xs font-semibold text-text-muted mb-1.5">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    maxlength="80"
                    required
                    placeholder="Contoh: Rian Anggoro"
                    class="w-full min-h-[44px] rounded-xl border {{ $errors->has('name') ? 'border-red-500' : 'border-border' }} bg-surface-2 px-3.5 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                >
                <p class="text-[11px] text-text-muted mt-1">Nama boleh sama dengan member lain. Sistem menghasilkan ID unik tersendiri.</p>
            </div>

            {{-- Nama Panggilan --}}
            <div>
                <label for="nickname" class="block text-xs font-semibold text-text-muted mb-1.5">
                    Nama Panggilan (Opsional)
                </label>
                <input
                    type="text"
                    id="nickname"
                    name="nickname"
                    value="{{ old('nickname') }}"
                    maxlength="50"
                    placeholder="Contoh: Rian"
                    class="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                >
            </div>

            {{-- Upload Foto --}}
            <div>
                <x-admin.image-upload
                    name="photo"
                    label="Foto Profil Member (Opsional)"
                    hint="Rasio persegi 1:1 (disarankan 400 × 400 px), JPG/PNG/WebP, maks 2 MB. Dipakai petugas untuk verifikasi wajah di pintu."
                    :recommendedWidth="400"
                    :recommendedHeight="400"
                    maxSizeMb="2"
                />
            </div>

            {{-- Saldo Awal Kunjungan --}}
            <div>
                <label for="opening_visits" class="block text-xs font-semibold text-text-muted mb-1.5">
                    Saldo Awal Kunjungan
                </label>
                <x-ui.select
                    id="opening_visits"
                    name="opening_visits"
                    :options="collect(range(0, 9))->map(fn($i) => ['value' => (string)$i, 'label' => $i . ' kunjungan'])->all()"
                    :value="(string) old('opening_visits', 0)"
                    class="w-full min-h-[44px] text-xs"
                />
                <p class="text-[11px] text-text-muted mt-1">Isi hanya jika member ini merupakan pindahan dari kartu stempel fisik lama (tidak langsung memicu reward).</p>
            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="block text-xs font-semibold text-text-muted mb-1.5">
                    Status Member <span class="text-red-500">*</span>
                </label>
                <x-ui.select id="status" name="status" class="w-full min-h-[44px] text-xs">
                    <option value="aktif" {{ old('status', 'aktif') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="nonaktif" {{ old('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    <option value="diblokir" {{ old('status') === 'diblokir' ? 'selected' : '' }}>Diblokir</option>
                </x-ui.select>
            </div>

            {{-- Catatan Internal --}}
            <div>
                <label for="notes" class="block text-xs font-semibold text-text-muted mb-1.5">
                    Catatan Internal (Opsional)
                </label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                    maxlength="300"
                    placeholder="Catatan internal pengelola sinema..."
                    class="w-full rounded-xl border border-border bg-surface-2 p-3 text-xs text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                >{{ old('notes') }}</textarea>
                <p class="text-[11px] text-text-muted mt-1">Hanya terlihat oleh admin pengelola bioskop.</p>
            </div>

            {{-- Catatan Privasi --}}
            <div class="rounded-xl border border-border bg-surface-2 p-4 text-xs text-text-muted leading-relaxed">
                <span class="font-bold text-text">Penting:</span> Sistem tidak meminta maupun menyimpan nomor telepon, email, WhatsApp, ataupun alamat demi privasi pelanggan. ID unik dan kartu digital bertanda tangan adalah identitas utama member.
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-border">
                <a
                    href="{{ route('admin.members.index') }}"
                    class="min-h-[44px] inline-flex items-center justify-center rounded-xl border border-border bg-surface px-5 text-xs font-semibold text-text hover:bg-surface-2 transition"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="min-h-[44px] inline-flex items-center justify-center rounded-xl bg-amber-500 px-6 text-xs font-bold text-black shadow-sm hover:bg-amber-400 transition"
                >
                    Simpan & Terbitkan Kartu
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

