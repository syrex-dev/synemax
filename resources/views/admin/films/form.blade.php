@extends('layouts.admin')

@section('title', $film->exists ? 'Ubah film' : 'Tambah film')

@section('content')
    <a href="{{ route('admin.films.index') }}" class="text-sm text-accent hover:underline">← Kembali ke katalog</a>
    <h1 class="mt-3 text-3xl font-black">{{ $film->exists ? 'Ubah film' : 'Tambah film' }}</h1>
    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-danger/50 bg-danger/10 p-4 text-sm text-danger">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    <form method="POST" enctype="multipart/form-data" action="{{ $film->exists ? route('admin.films.update', $film) : route('admin.films.store') }}" class="mt-6 grid gap-5 rounded-2xl border border-border bg-surface p-5 sm:grid-cols-2 sm:p-7">
        @csrf
        @if ($film->exists) @method('PUT') @endif
        <div class="sm:col-span-2">
            <label for="title" class="text-sm font-semibold">Judul film</label>
            <input id="title" name="title" value="{{ old('title', $film->title) }}" required maxlength="180" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text focus:border-accent focus:ring-accent">
        </div>
        <div>
            <label for="duration_minutes" class="text-sm font-semibold">Durasi (menit)</label>
            <input id="duration_minutes" name="duration_minutes" type="number" min="1" max="600" value="{{ old('duration_minutes', $film->duration_minutes ?? 100) }}" required class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text focus:border-accent focus:ring-accent">
        </div>
        <div>
            <label for="genre" class="text-sm font-semibold">Genre</label>
            <input id="genre" name="genre" value="{{ old('genre', $film->genre) }}" required maxlength="100" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text focus:border-accent focus:ring-accent">
        </div>
        <div>
            <label for="age_rating" class="text-sm font-semibold">Rating usia</label>
            <select id="age_rating" name="age_rating" required class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text focus:border-accent focus:ring-accent">
                @foreach (['SU' => 'SU · Semua Umur', '13+' => '13+', '17+' => '17+'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('age_rating', $film->age_rating ?? 'SU') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="trailer_url" class="text-sm font-semibold">Link trailer (opsional)</label>
            <input id="trailer_url" name="trailer_url" type="url" value="{{ old('trailer_url', $film->trailer_url) }}" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text focus:border-accent focus:ring-accent">
        </div>
        <div class="sm:col-span-2">
            <label for="synopsis" class="text-sm font-semibold">Sinopsis singkat</label>
            <textarea id="synopsis" name="synopsis" rows="4" required maxlength="3000" class="mt-2 w-full rounded-xl border border-border bg-bg px-3 py-3 text-sm text-text focus:border-accent focus:ring-accent">{{ old('synopsis', $film->synopsis) }}</textarea>
        </div>
        <div class="sm:col-span-2">
            <label for="license_notes" class="text-sm font-semibold">Catatan sumber/lisensi (internal)</label>
            <textarea id="license_notes" name="license_notes" rows="2" maxlength="3000" class="mt-2 w-full rounded-xl border border-border bg-bg px-3 py-3 text-sm text-text focus:border-accent focus:ring-accent">{{ old('license_notes', $film->license_notes) }}</textarea>
            <p class="mt-1 text-xs text-text-muted">Tidak ditampilkan di halaman pelanggan.</p>
        </div>
        <div class="sm:col-span-2">
            <label for="poster" class="text-sm font-semibold">Poster (JPG/PNG/WebP, maks. 4 MB)</label>
            <input id="poster" name="poster" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-text-muted file:mr-3 file:min-h-11 file:rounded-lg file:border-0 file:bg-surface-2 file:px-4 file:font-semibold file:text-text">
            <p class="mt-1 text-xs text-text-muted">Poster otomatis dikompres ke WebP dengan lebar maksimal 500 piksel.</p>
            @if ($film->poster_path)<img src="{{ Storage::url($film->poster_path) }}" alt="Poster {{ $film->title }}" loading="lazy" class="mt-3 h-32 rounded-xl object-cover">@endif
        </div>
        <div class="sm:col-span-2">
            <input type="hidden" name="is_active" value="0">
            <label for="is_active" class="inline-flex items-center gap-2 text-sm"><input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-border bg-bg text-accent focus:ring-accent" @checked(old('is_active', $film->is_active ?? true))> Tampilkan di katalog publik</label>
        </div>
        <div class="sm:col-span-2">
            <button class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-accent px-5 font-bold text-accent-contrast hover:opacity-90">Simpan film</button>
        </div>
    </form>
@endsection
