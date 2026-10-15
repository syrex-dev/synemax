@extends('layouts.admin')

@section('title', ($isEdit ? 'Ubah Artikel' : 'Tulis Artikel Baru'))

@section('content')
    <div x-data="{
        coverPreview: null,
        ringkasanLength: @js(strlen(old('ringkasan', $article->ringkasan ?? ''))),
        contentTab: 'write',
        handleCoverChange(event) {
            const file = event.target.files && event.target.files[0];
            if (file) {
                this.coverPreview = URL.createObjectURL(file);
            }
        }
    }">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.articles.index') }}" class="text-xs font-semibold text-text-muted hover:text-text">← Kembali ke Kabar & Promo</a>
                <h1 class="mt-1 text-3xl font-black text-text">{{ $isEdit ? 'Ubah Artikel' : 'Tulis Artikel Baru' }}</h1>
            </div>
            @if ($isEdit)
                <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" target="_blank" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-accent bg-accent/10 px-4 text-xs font-bold text-accent hover:bg-accent/20">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Pratinjau Tampilan Publik</span>
                </a>
            @endif
        </div>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-2xl border border-danger/40 bg-danger/10 p-4 text-sm text-danger">
                <p class="font-bold">Mohon perbaiki isian form berikut:</p>
                <ul class="mt-1 list-disc pl-5 text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $isEdit ? route('admin.articles.update', ['article' => $article->id]) : route('admin.articles.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            {{-- 1. Konten Utama --}}
            <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-token">
                <summary class="cursor-pointer text-lg font-bold text-text">1. Informasi & Konten Artikel</summary>
                <p class="mt-1 text-xs text-text-muted">Judul, kategori, ringkasan dan isi lengkap menggunakan format Safe Markdown.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="judul" class="text-sm font-semibold">Judul Artikel <span class="text-danger">*</span></label>
                        <input id="judul" name="judul" value="{{ old('judul', $article->judul) }}" required placeholder="Contoh: Jadwal Spesial Akhir Pekan & Promo Popcorn Gratis" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="kategori" class="text-sm font-semibold">Kategori <span class="text-danger">*</span></label>
                            <select id="kategori" name="kategori" required class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                                <option value="Berita" @selected(old('kategori', $article->kategori) === 'Berita')>Berita</option>
                                <option value="Promo" @selected(old('kategori', $article->kategori) === 'Promo')>Promo</option>
                                <option value="Pengumuman" @selected(old('kategori', $article->kategori) === 'Pengumuman')>Pengumuman</option>
                            </select>
                        </div>
                        <div>
                            <label for="slug" class="text-sm font-semibold">Slug URL (opsional, otomatis dibuat)</label>
                            <input id="slug" name="slug" value="{{ old('slug', $article->slug) }}" placeholder="jadwal-spesial-akhir-pekan" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 font-mono text-sm text-text">
                            <p class="mt-1 text-[11px] text-text-muted">Biarkan kosong agar sistem membuat slug otomatis dari judul.</p>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="ringkasan" class="text-sm font-semibold">Ringkasan Singkat (Lead) <span class="text-danger">*</span></label>
                            <span class="text-xs text-text-muted"><span x-text="ringkasanLength"></span>/500 karakter</span>
                        </div>
                        <textarea id="ringkasan" name="ringkasan" rows="3" maxlength="500" required @input="ringkasanLength = $event.target.value.length" placeholder="Tuliskan 1-2 kalimat pengantar yang menarik pembaca dan digunakan untuk meta deskripsi SEO..." class="mt-1.5 w-full rounded-xl border border-border bg-bg p-3.5 text-sm text-text">{{ old('ringkasan', $article->ringkasan) }}</textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="konten" class="text-sm font-semibold">Isi Lengkap Artikel (Safe Markdown) <span class="text-danger">*</span></label>
                            <div class="inline-flex rounded-lg border border-border bg-surface-2 p-0.5 text-xs">
                                <button type="button" @click="contentTab = 'write'" :class="contentTab === 'write' ? 'bg-surface text-text shadow-xs font-bold' : 'text-text-muted hover:text-text'" class="rounded-md px-2.5 py-1 transition">
                                    Editor
                                </button>
                                <button type="button" @click="contentTab = 'help'" :class="contentTab === 'help' ? 'bg-surface text-text shadow-xs font-bold' : 'text-text-muted hover:text-text'" class="rounded-md px-2.5 py-1 transition">
                                    Panduan Format
                                </button>
                            </div>
                        </div>

                        <div x-show="contentTab === 'write'">
                            <textarea id="konten" name="konten" rows="14" required placeholder="Tulis isi artikel dengan format Markdown di sini...&#10;&#10;## Judul Bagian&#10;Teks paragraf dengan **tebal**, *miring*, dan [tautan](https://example.com).&#10;&#10;- Poin daftar 1&#10;- Poin daftar 2" class="mt-1.5 w-full rounded-xl border border-border bg-bg p-3.5 font-mono text-sm leading-relaxed text-text">{{ old('konten', $article->konten) }}</textarea>
                        </div>

                        <div x-show="contentTab === 'help'" x-cloak class="mt-1.5 rounded-xl border border-border bg-surface-2 p-4 text-xs text-text-muted space-y-2">
                            <p class="font-bold text-text">Format Safe Markdown yang Didukung:</p>
                            <ul class="list-disc pl-5 space-y-1">
                                <li><strong>Judul:</strong> Gunakan <code>## Judul Sekunder</code> atau <code>### Sub-judul</code>.</li>
                                <li><strong>Penekanan:</strong> <code>**teks tebal**</code> atau <code>*teks miring*</code>.</li>
                                <li><strong>Daftar:</strong> Gunakan tanda minus <code>- item</code> atau angka <code>1. langkah</code>.</li>
                                <li><strong>Kutipan:</strong> Mulai baris dengan <code>&gt; kutipan penting</code>.</li>
                                <li><strong>Tautan Aman:</strong> <code>[Nama Link](https://website.com)</code> (Tautan luar otomatis dibuka di tab baru dengan rel nofollow noopener).</li>
                                <li><strong class="text-accent">Keamanan Terjamin:</strong> Tag HTML mentah seperti <code>&lt;script&gt;</code> atau <code>&lt;iframe&gt;</code> akan otomatis dibersihkan.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </details>

            {{-- 2. Gambar Sampul --}}
            <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-token">
                <summary class="cursor-pointer text-lg font-bold text-text">2. Gambar Sampul (Cover Image)</summary>
                <p class="mt-1 text-xs text-text-muted">Format gambar landscape direkomendasikan (JPG, PNG, atau WebP, maks. 3 MB). Sistem otomatis mengoptimasi gambar menjadi format WebP berkualitas tinggi.</p>

                <div class="mt-5 space-y-4">
                    @if ($article->cover_path)
                        <div class="rounded-xl border border-border bg-surface-2 p-4">
                            <p class="text-xs font-bold text-text">Gambar Sampul Saat Ini:</p>
                            <div class="mt-2 flex items-center gap-4">
                                <img src="{{ $article->coverUrl() }}" alt="" class="h-24 w-36 rounded-lg object-cover border border-border">
                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-danger cursor-pointer">
                                    <input type="checkbox" name="delete_cover" value="1" class="rounded border-border text-danger focus:ring-danger">
                                    <span>Hapus gambar sampul saat ini (tanpa pengganti)</span>
                                </label>
                            </div>
                        </div>
                    @endif

                    <div x-show="coverPreview" x-cloak class="rounded-xl border border-accent/40 bg-accent/5 p-4">
                        <p class="text-xs font-bold text-accent">Pratinjau Gambar Baru yang Dipilih:</p>
                        <div class="mt-2">
                            <img :src="coverPreview" alt="Pratinjau Cover" class="h-28 w-44 rounded-lg object-cover border border-accent/40">
                        </div>
                    </div>

                    <div>
                        <label for="cover" class="text-sm font-semibold">Pilih Berkas Gambar {{ $article->cover_path ? 'Baru (opsional)' : '(opsional)' }}</label>
                        <input id="cover" name="cover" type="file" accept="image/png,image/jpeg,image/webp" @change="handleCoverChange($event)" class="mt-1.5 block w-full text-xs text-text-muted file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-semibold file:text-text hover:file:bg-surface-2/80">
                        <p class="mt-1 text-[11px] text-text-muted">Resolusi rekomendasi: 1200 x 630 piksel untuk optimalisasi kartu Open Graph saat dibagikan ke WhatsApp & media sosial.</p>
                    </div>
                </div>
            </details>

            {{-- 3. Publikasi & Visibilitas --}}
            <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-token">
                <summary class="cursor-pointer text-lg font-bold text-text">3. Publikasi & Penjadwalan</summary>
                <p class="mt-1 text-xs text-text-muted">Atur status draft atau jadwal terbit artikel.</p>

                <div class="mt-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="status_publikasi" class="text-sm font-semibold">Status Publikasi <span class="text-danger">*</span></label>
                            <select id="status_publikasi" name="status_publikasi" required class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                                <option value="terbit" @selected(old('status_publikasi', $article->status_publikasi) === 'terbit')>Terbit (Publik)</option>
                                <option value="draft" @selected(old('status_publikasi', $article->status_publikasi) === 'draft')>Draft (Hanya Admin)</option>
                            </select>
                        </div>

                        <div>
                            <label for="tanggal_terbit" class="text-sm font-semibold">Tanggal & Jam Terbit (WIB)</label>
                            <input id="tanggal_terbit" name="tanggal_terbit" type="datetime-local" value="{{ old('tanggal_terbit', $article->tanggal_terbit ? $article->tanggal_terbit->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                            <p class="mt-1 text-[11px] text-text-muted">Jika diatur ke tanggal masa depan, artikel akan otomatis berstatus terjadwal dan baru tampil setelah waktu tercapai.</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-border bg-surface-2 p-4">
                        <label class="inline-flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_pinned" value="0">
                            <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $article->is_pinned)) class="h-4 w-4 rounded border-border text-accent focus:ring-accent">
                            <div>
                                <span class="text-sm font-bold text-text">Sematkan Artikel (Pin to Top)</span>
                                <p class="text-xs text-text-muted">Artikel yang disematkan akan selalu muncul di posisi teratas daftar berita.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </details>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                <a href="{{ route('admin.articles.index') }}" class="inline-flex min-h-12 items-center rounded-xl border border-border bg-surface px-6 text-sm font-bold text-text hover:bg-surface-2">
                    Batal
                </a>
                <button type="submit" class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-accent px-8 text-sm font-bold text-accent-contrast shadow-sm hover:opacity-90">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    <span>{{ $isEdit ? 'Perbarui Artikel' : 'Simpan Artikel' }}</span>
                </button>
            </div>
        </form>
    </div>
@endsection
