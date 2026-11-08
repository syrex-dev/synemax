@extends('layouts.admin')

@section('title', 'Ubah Member: ' . $member->name . ' - Panel Admin SynemaX')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-text sm:text-2xl">Ubah Data Member</h1>
            <p class="text-xs text-text-muted mt-1">Perbarui profil dan status member <strong class="text-text">{{ $member->name }}</strong> (ID: {{ $member->formattedCode() }}).</p>
        </div>
        <a
            href="{{ route('admin.members.show', $member) }}"
            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
        >
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            <span>Kembali ke Detail</span>
        </a>
    </div>

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
        <form method="POST" action="{{ route('admin.members.update', $member) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- ID Member (Read Only) --}}
            <div>
                <label class="block text-xs font-semibold text-text-muted mb-1.5">
                    ID Unik Member (Permanen)
                </label>
                <input
                    type="text"
                    value="{{ $member->formattedCode() }}"
                    disabled
                    class="w-full min-h-[44px] rounded-xl border border-border bg-surface px-3.5 text-sm font-mono text-text-muted cursor-not-allowed"
                >
            </div>

            {{-- Nama Member --}}
            <div>
                <label for="name" class="block text-xs font-semibold text-text-muted mb-1.5">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $member->name) }}"
                    maxlength="80"
                    required
                    class="w-full min-h-[44px] rounded-xl border {{ $errors->has('name') ? 'border-red-500' : 'border-border' }} bg-surface-2 px-3.5 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                >
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
                    value="{{ old('nickname', $member->nickname) }}"
                    maxlength="50"
                    class="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                >
            </div>

            {{-- Upload Foto --}}
            <div>
                <x-admin.image-upload
                    name="photo"
                    label="Foto Profil Member"
                    hint="Rasio persegi 1:1 (400 × 400 px), maks 2 MB."
                    :currentValue="$member->photo_path"
                    :recommendedWidth="400"
                    :recommendedHeight="400"
                    maxSizeMb="2"
                />
                @if ($member->photo_path)
                    <div class="mt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-text-muted hover:text-text">
                            <input type="checkbox" name="remove_photo" value="1" class="size-4 rounded border-border text-amber-500 focus:ring-amber-500/30">
                            <span>Hapus foto saat ini</span>
                        </label>
                    </div>
                @endif
            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="block text-xs font-semibold text-text-muted mb-1.5">
                    Status Member <span class="text-red-500">*</span>
                </label>
                <x-ui.select id="status" name="status" class="w-full min-h-[44px] text-xs">
                    <option value="aktif" {{ old('status', $member->status) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="nonaktif" {{ old('status', $member->status) === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    <option value="diblokir" {{ old('status', $member->status) === 'diblokir' ? 'selected' : '' }}>Diblokir</option>
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
                    class="w-full rounded-xl border border-border bg-surface-2 p-3 text-xs text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                >{{ old('notes', $member->notes) }}</textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-border">
                <a
                    href="{{ route('admin.members.show', $member) }}"
                    class="min-h-[44px] inline-flex items-center justify-center rounded-xl border border-border bg-surface px-5 text-xs font-semibold text-text hover:bg-surface-2 transition"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="min-h-[44px] inline-flex items-center justify-center rounded-xl bg-amber-500 px-6 text-xs font-bold text-black shadow-sm hover:bg-amber-400 transition"
                >
                    Perbarui Data Member
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

