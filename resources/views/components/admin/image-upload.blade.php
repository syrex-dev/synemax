@props([
    'name',
    'label' => null,
    'currentValue' => null,
    'hint' => 'JPG, PNG, atau WebP, maks 5 MB (otomatis dioptimalkan HD)',
    'aspectRatio' => null,
    'recommendedWidth' => null,
    'recommendedHeight' => null,
    'maxSizeMb' => 5,
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
    $recW = (int) ($recommendedWidth ?? 0);
    $recH = (int) ($recommendedHeight ?? 0);
    // Double resolution for Retina HD (at least 1200px width, 1800px height for posters)
    $targetBoundW = $recW > 0 ? max($recW * 2, 1000) : 1200;
    $targetBoundH = $recH > 0 ? max($recH * 2, 1500) : 1800;
@endphp

<div
    x-data="{
        previewUrl: @js($initialUrl),
        hasFile: {{ $initialUrl ? 'true' : 'false' }},
        fileName: '',
        clientError: '',
        clientWarning: '',
        optimizationInfo: '',
        isOptimizing: false,
        showLibrary: false,
        deleteMarked: false,

        async handleFileChange(event) {
            const inputEl = event.target;
            let file = inputEl.files && inputEl.files[0];
            if (!file) return;

            this.clientError = '';
            this.clientWarning = '';
            this.optimizationInfo = '';
            this.deleteMarked = false;
            this.fileName = file.name;

            // If SVG vector, no canvas compression needed
            if (file.type === 'image/svg+xml') {
                const maxBytes = {{ $maxSizeMb }} * 1024 * 1024;
                if (file.size > maxBytes) {
                    this.clientError = `Ukuran file ${(file.size / 1024 / 1024).toFixed(1)} MB melebihi batas maksimal {{ $maxSizeMb }} MB.`;
                    inputEl.value = '';
                    return;
                }
                this.previewUrl = URL.createObjectURL(file);
                this.hasFile = true;
                return;
            }

            // Raster image: optimize via Canvas to WebP
            this.isOptimizing = true;
            this.optimizationInfo = 'Mengoptimalkan kualitas gambar...';

            try {
                const result = await this.optimizeImage(file);
                const optimizedFile = result.file;

                // Seamlessly replace the file in the native file input so the form submits the lightweight file
                if (window.DataTransfer && result.wasOptimized) {
                    const dt = new DataTransfer();
                    dt.items.add(optimizedFile);
                    inputEl.files = dt.files;
                }

                this.previewUrl = result.previewUrl;
                this.hasFile = true;

                const optKb = (optimizedFile.size / 1024).toFixed(0);
                this.fileName = `${optimizedFile.name} (${optKb} KB)`;

                if (result.wasOptimized && result.origSize > optimizedFile.size) {
                    const origStr = result.origSize > 1024 * 1024
                        ? (result.origSize / (1024 * 1024)).toFixed(1) + ' MB'
                        : (result.origSize / 1024).toFixed(0) + ' KB';
                    this.optimizationInfo = `Kualitas dioptimalkan: ${origStr} → ${optKb} KB (${result.width}×${result.height} px, HD)`;
                } else {
                    this.optimizationInfo = `Gambar siap (${optKb} KB, ${result.width}×${result.height} px)`;
                }

                // Aspect ratio check
                @if ($recommendedWidth && $recommendedHeight)
                    const expectedRatio = {{ $recommendedWidth }} / {{ $recommendedHeight }};
                    const actualRatio = result.width / result.height;
                    const ratioDiff = Math.abs(actualRatio - expectedRatio) / expectedRatio;

                    if (ratioDiff > 0.08) {
                        @if ($recommendedWidth === $recommendedHeight)
                            this.clientWarning = `Rasio gambar ${result.width}×${result.height} tidak persegi. Disarankan persegi (1:1) dan rapi.`;
                        @else
                            this.clientWarning = `Rasio gambar ${result.width}×${result.height} berbeda dari rasio standar {{ $recommendedWidth }}×{{ $recommendedHeight }} (2:3). Gambar mungkin sedikit terpotong di katalog.`;
                        @endif
                    }
                @endif

                // Max size check
                const maxBytes = {{ $maxSizeMb }} * 1024 * 1024;
                if (optimizedFile.size > maxBytes) {
                    this.clientError = `Ukuran file ${(optimizedFile.size / 1024 / 1024).toFixed(1)} MB melebihi batas maksimal {{ $maxSizeMb }} MB.`;
                }
            } catch (err) {
                console.warn('Image optimization fallback:', err);
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                    this.hasFile = true;
                };
                reader.readAsDataURL(file);
            } finally {
                this.isOptimizing = false;
            }
        },

        optimizeImage(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onerror = () => reject(new Error('Gagal membaca file'));
                reader.onload = (e) => {
                    const img = new Image();
                    img.onerror = () => reject(new Error('Gagal memproses gambar'));
                    img.onload = () => {
                        const origW = img.naturalWidth;
                        const origH = img.naturalHeight;

                        const maxW = {{ $targetBoundW }};
                        const maxH = {{ $targetBoundH }};

                        let targetW = origW;
                        let targetH = origH;

                        // Downscale smoothly if larger than target bounds, preserving exact aspect ratio
                        if (origW > maxW || origH > maxH) {
                            const ratio = Math.min(maxW / origW, maxH / origH);
                            targetW = Math.max(1, Math.round(origW * ratio));
                            targetH = Math.max(1, Math.round(origH * ratio));
                        }

                        // If file is already small WebP within bounds, keep as is
                        if (file.size <= 250 * 1024 && origW <= maxW && origH <= maxH && file.type === 'image/webp') {
                            return resolve({
                                file: file,
                                wasOptimized: false,
                                origSize: file.size,
                                previewUrl: e.target.result,
                                width: origW,
                                height: origH
                            });
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = targetW;
                        canvas.height = targetH;
                        const ctx = canvas.getContext('2d', { alpha: true });
                        if (!ctx) {
                            return resolve({
                                file: file,
                                wasOptimized: false,
                                origSize: file.size,
                                previewUrl: e.target.result,
                                width: origW,
                                height: origH
                            });
                        }

                        // High quality smoothing
                        ctx.imageSmoothingEnabled = true;
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(img, 0, 0, targetW, targetH);

                        // High-quality WebP encoding (0.88 is visually crisp with tiny footprint)
                        canvas.toBlob((blob) => {
                            if (!blob) {
                                return resolve({
                                    file: file,
                                    wasOptimized: false,
                                    origSize: file.size,
                                    previewUrl: e.target.result,
                                    width: origW,
                                    height: origH
                                });
                            }

                            const baseName = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
                            const cleanName = baseName.replace(/[^a-zA-Z0-9_-]/g, '_').substring(0, 50) || 'image';
                            const newFileName = `${cleanName}.webp`;

                            const optimizedFile = new File([blob], newFileName, {
                                type: 'image/webp',
                                lastModified: Date.now()
                            });

                            const previewUrl = URL.createObjectURL(blob);
                            resolve({
                                file: optimizedFile,
                                wasOptimized: true,
                                origSize: file.size,
                                previewUrl: previewUrl,
                                width: targetW,
                                height: targetH
                            });
                        }, 'image/webp', 0.88);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        },

        removeFile() {
            this.previewUrl = '';
            this.hasFile = false;
            this.fileName = '';
            this.clientError = '';
            this.clientWarning = '';
            this.optimizationInfo = '';
            this.deleteMarked = true;
            const input = document.getElementById('{{ $inputId }}');
            if (input) input.value = '';
        },

        selectFromLibrary(url, path) {
            this.previewUrl = url;
            this.hasFile = true;
            this.deleteMarked = false;
            this.showLibrary = false;
            this.clientError = '';
            this.clientWarning = '';
            this.optimizationInfo = '';
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
        <div x-show="hasFile && previewUrl" x-cloak class="space-y-3">
            <div class="relative flex items-center justify-center rounded-xl bg-slate-950/20 p-2 max-h-64 overflow-hidden">
                <img :src="previewUrl" alt="Pratinjau" class="max-h-60 w-auto max-w-full rounded-lg object-contain shadow-sm">

                {{-- Loading Overlay when optimizing --}}
                <div x-show="isOptimizing" x-cloak class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 text-white backdrop-blur-xs rounded-xl">
                    <svg class="size-6 animate-spin text-amber-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span class="mt-2 text-xs font-semibold">Mengoptimalkan gambar...</span>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-border pt-3">
                <div class="min-w-0 max-w-[280px]">
                    <span class="truncate text-xs font-semibold text-text block" x-text="fileName || 'Gambar tersimpan'"></span>
                    <span x-show="optimizationInfo" x-cloak class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                        <svg class="size-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <span x-text="optimizationInfo"></span>
                    </span>
                </div>
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

        {{-- Empty State / File Selector --}}
        <div x-show="!hasFile || !previewUrl" class="flex flex-col items-center justify-center py-6 text-center">
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

    {{-- Client Warning (e.g. Aspect ratio recommendation) --}}
    <div x-show="clientWarning" x-cloak class="rounded-xl border border-amber-600/40 bg-amber-500/15 dark:bg-amber-500/20 p-3 text-xs font-semibold text-[#78350F] dark:text-[#FEF08A] flex items-start gap-2 shadow-xs">
        <svg class="size-4 shrink-0 mt-0.5 text-[#78350F] dark:text-[#FEF08A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span x-text="clientWarning"></span>
    </div>

    {{-- Client Error (e.g. Size exceeds limit) --}}
    <div x-show="clientError" x-cloak class="rounded-xl border border-red-500/40 bg-red-500/10 p-3 text-xs font-semibold text-red-600 dark:text-red-400 flex items-start gap-2 shadow-xs">
        <svg class="size-4 shrink-0 mt-0.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
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
