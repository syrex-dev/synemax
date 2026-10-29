@props([
    'name',
    'label' => null,
    'currentValue' => null,
    'hint' => 'JPG, PNG, atau WebP, maks 2 MB',
    'aspectRatio' => null,
    'recommendedWidth' => null,
    'recommendedHeight' => null,
    'maxSizeMb' => 2,
    'accept' => 'image/jpeg,image/png,image/webp,image/svg+xml',
    'required' => false,
    'libraryImages' => [],
])

@php
    $inputId = 'upload_' . $name . '_' . \Illuminate\Support\Str::random(6);
    $initialUrl = null;
    if ($currentValue) {
        if (str_starts_with($currentValue, 'http://') || str_starts_with($currentValue, 'https://') || str_starts_with($currentValue, '/')) {
            $initialUrl = $currentValue;
        } else {
            $initialUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($currentValue);
        }
    }
@endphp

<div
    x-data="{
        previewUrl: '{{ $initialUrl }}',
        hasFile: {{ $initialUrl ? 'true' : 'false' }},
        fileName: '',
        clientError: '',
        showLibrary: false,
        deleteMarked: false,
        handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.clientError = '';
            this.deleteMarked = false;
            this.fileName = file.name;

            // Check size
            const maxBytes = {{ $maxSizeMb }} * 1024 * 1024;
            if (file.size > maxBytes) {
                this.clientError = `Ukuran file ${(file.size / 1024 / 1024).toFixed(1)} MB melebihi batas maksimal {{ $maxSizeMb }} MB.`;
            }

            // Preview & check dimensions
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewUrl = e.target.result;
                this.hasFile = true;

                @if ($recommendedWidth && $recommendedHeight)
                    const img = new Image();
                    img.onload = () => {
                        const actualW = img.naturalWidth;
                        const actualH = img.naturalHeight;
                        const expectedRatio = {{ $recommendedWidth }} / {{ $recommendedHeight }};
                        const actualRatio = actualW / actualH;
                        const ratioDiff = Math.abs(actualRatio - expectedRatio) / expectedRatio;

                        if (ratioDiff > 0.05) {
                            this.clientError = `Rasio gambar ${actualW}×${actualH} berbeda dari rekomendasi {{ $recommendedWidth }}×{{ $recommendedHeight }} px. Gambar mungkin terpotong.`;
                        }
                    };
                    img.src = e.target.result;
                @endif
            };
            reader.readAsDataURL(file);
        },
        removeFile() {
            this.previewUrl = '';
            this.hasFile = false;
            this.fileName = '';
            this.clientError = '';
            this.deleteMarked = true;
            const input = document.getElementById('{{ $inputId }}');
            if (input) input.value = '';
        },
        selectFromLibrary(url, path) {
            this.previewUrl = url;
            this.hasFile = true;
            this.deleteMarked = false;
            this.showLibrary = false;
            document.getElementById('library_{{ $name }}').value = path;
        }
    }"
    class="space-y-2"
>
    @if ($label)
        <label class="block text-xs font-bold uppercase tracking-wider text-text">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    {{-- Hidden fields for deletion or library selection --}}
    <input type="hidden" name="delete_{{ $name }}" :value="deleteMarked ? '1' : '0'">
    <input type="hidden" id="library_{{ $name }}" name="library_{{ $name }}" value="">

    {{-- Dropzone & Preview Box --}}
    <div class="relative overflow-hidden rounded-2xl border-2 border-dashed border-slate-400 dark:border-white/20 bg-surface p-4 transition-colors hover:border-amber-500">
        {{-- Preview Area --}}
        <template x-if="hasFile && previewUrl">
            <div class="space-y-3">
                <div class="relative flex items-center justify-center rounded-xl bg-slate-950/20 p-2 max-h-56 overflow-hidden">
                    <img :src="previewUrl" alt="Pratinjau" class="max-h-52 w-auto max-w-full rounded-lg object-contain shadow-sm">
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-border pt-3">
                    <span class="truncate text-xs font-semibold text-text max-w-[200px]" x-text="fileName || 'Gambar tersimpan'"></span>
                    <div class="flex items-center gap-2">
                        <label for="{{ $inputId }}" class="inline-flex min-h-[34px] cursor-pointer items-center rounded-lg border border-slate-400 dark:border-white/20 bg-surface px-3 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs">
                            Ganti
                        </label>
                        <button type="button" @click="removeFile()" class="inline-flex min-h-[34px] items-center rounded-lg border border-danger/40 bg-surface px-3 text-xs font-bold text-danger hover:bg-danger/10 transition-colors shadow-xs">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Empty State / File Selector --}}
        <template x-if="!hasFile || !previewUrl">
            <div class="flex flex-col items-center justify-center py-6 text-center">
                <div class="flex size-12 items-center justify-center rounded-full bg-amber-500/10 text-amber-500 mb-3">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="text-xs font-bold text-text">Pilih gambar dari komputer</p>
                <p class="mt-1 text-[11px] text-text-muted">{{ $hint }}</p>

                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    <label for="{{ $inputId }}" class="inline-flex min-h-[38px] cursor-pointer items-center rounded-xl bg-amber-500 px-4 py-1.5 text-xs font-bold text-slate-950 hover:bg-amber-400 transition-colors shadow-xs">
                        Pilih File
                    </label>
                    @if (count($libraryImages) > 0)
                        <button type="button" @click="showLibrary = true" class="inline-flex min-h-[38px] items-center rounded-xl border border-slate-400 dark:border-white/20 bg-surface px-3 py-1.5 text-xs font-semibold text-text hover:bg-surface-2 transition-colors shadow-xs">
                            Pilih dari pustaka
                        </button>
                    @endif
                </div>
            </div>
        </template>

        {{-- Hidden Native File Input --}}
        <input
            type="file"
            id="{{ $inputId }}"
            name="{{ $name }}"
            accept="{{ $accept }}"
            @change="handleFileChange($event)"
            class="sr-only"
        >
    </div>

    {{-- Client Warning / Error --}}
    <div x-show="clientError" x-cloak class="rounded-xl border border-warning/40 bg-warning/10 p-2.5 text-xs font-medium text-warning">
        <span x-text="clientError"></span>
    </div>

    {{-- Server Error --}}
    @error($name)
        <p class="text-xs font-bold text-danger">{{ $message }}</p>
    @enderror

    {{-- Media Library Modal --}}
    @if (count($libraryImages) > 0)
        <div x-show="showLibrary" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="showLibrary = false" @keydown.escape.window="showLibrary = false">
            <div class="w-full max-w-2xl rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="text-base font-bold text-text">Pustaka Media (Pilih Gambar)</h3>
                    <button type="button" @click="showLibrary = false" class="size-8 inline-flex items-center justify-center rounded-lg text-text-muted hover:bg-surface-2">×</button>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-h-96 overflow-y-auto p-1">
                    @foreach ($libraryImages as $item)
                        <div
                            @click="selectFromLibrary('{{ $item['url'] }}', '{{ $item['path'] }}')"
                            class="group cursor-pointer rounded-xl border border-border overflow-hidden hover:border-amber-500 transition shadow-xs"
                        >
                            <img src="{{ $item['url'] }}" alt="{{ $item['name'] }}" class="h-24 w-full object-cover group-hover:scale-105 transition-transform">
                            <p class="p-1.5 text-[10px] truncate text-text-muted text-center">{{ $item['name'] }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-end pt-2 border-t border-border">
                    <button type="button" @click="showLibrary = false" class="min-h-[36px] rounded-xl border border-border px-4 text-xs font-semibold text-text hover:bg-surface-2">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
