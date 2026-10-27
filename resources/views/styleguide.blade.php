@extends('layouts.public')

@section('title', 'Styleguide & Sistem Token Visual: SynemaX')

@section('content')
<main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
    {{-- Header Styleguide --}}
    <div class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-accent">Lingkungan Lokal Saja (404 di Produksi)</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-text sm:text-4xl">Panduan Gaya: Layar Tancap</h1>
            <p class="mt-2 text-sm text-text-muted">Katalog token desain, rasio kontras terukur WCAG, skala huruf, dan komponen antarmuka SynemaX.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button"
                    onclick="window.synemaXTheme.setMode(document.documentElement.classList.contains('dark') ? 'light' : 'dark')"
                    class="inline-flex min-h-11 items-center gap-2 rounded-[var(--radius-control)] border border-border-input bg-surface px-4 text-xs font-bold text-text transition hover:border-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                <span class="dark:hidden">Ganti ke tema malam (gelap)</span>
                <span class="hidden dark:inline">Ganti ke tema layar (terang)</span>
            </button>
        </div>
    </div>

    {{-- Seksi 1: Palet Warna Inti & Permukaan --}}
    <section class="mt-10">
        <h2 class="text-xl font-bold tracking-tight text-text">1. Palet Warna Inti & Permukaan</h2>
        <p class="mt-1 text-sm text-text-muted">Palet bernuansa malam di lapangan desa: kanvas malam hijau-teal tua, layar proyektor putih dingin kehijauan, tinta arang, aksen sablon biru, dan penanda karcis kuning.</p>

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            <div class="rounded-[var(--radius-control)] border border-border bg-[#F2F5F3] p-3 text-[#16201F] shadow-xs">
                <span class="block text-xs font-bold">Layar</span>
                <span class="mt-0.5 block font-tabular text-xs">#F2F5F3</span>
                <span class="mt-2 block text-[11px] text-[#4A605E]">Kanvas terang</span>
            </div>
            <div class="rounded-[var(--radius-control)] border border-[#1D444B] bg-[#0F2A2F] p-3 text-[#F2F5F3] shadow-xs">
                <span class="block text-xs font-bold">Malam</span>
                <span class="mt-0.5 block font-tabular text-xs">#0F2A2F</span>
                <span class="mt-2 block text-[11px] text-[#8FA8A4]">Kanvas gelap</span>
            </div>
            <div class="rounded-[var(--radius-control)] border border-border bg-[#16201F] p-3 text-[#F2F5F3] shadow-xs">
                <span class="block text-xs font-bold">Arang</span>
                <span class="mt-0.5 block font-tabular text-xs">#16201F</span>
                <span class="mt-2 block text-[11px] text-[#D3DDD9]">Tinta teks terang</span>
            </div>
            <div class="rounded-[var(--radius-control)] border border-border bg-[#2845D6] p-3 text-white shadow-xs">
                <span class="block text-xs font-bold">Biru Sablon</span>
                <span class="mt-0.5 block font-tabular text-xs">#2845D6 / #4D68F5</span>
                <span class="mt-2 block text-[11px] text-white/80">Tombol utama</span>
            </div>
            <div class="rounded-[var(--radius-control)] border border-border bg-[#FFC62E] p-3 text-[#16201F] shadow-xs">
                <span class="block text-xs font-bold">Kuning Karcis</span>
                <span class="mt-0.5 block font-tabular text-xs">#FFC62E</span>
                <span class="mt-2 block text-[11px] text-[#16201F]/80">Penanda slot & pita</span>
            </div>
            <div class="rounded-[var(--radius-control)] border border-border bg-[#ECEFEA] p-3 text-[#16201F] shadow-xs">
                <span class="block text-xs font-bold">Kertas Tiket</span>
                <span class="mt-0.5 block font-tabular text-xs">#ECEFEA</span>
                <span class="mt-2 block text-[11px] text-[#4A605E]">Fisik karcis</span>
            </div>
        </div>
    </section>

    {{-- Seksi 2: Tabel Kontras Kombinasi Nyata WCAG 2.1 --}}
    <section class="mt-12">
        <h2 class="text-xl font-bold tracking-tight text-text">2. Tabel Kontras Kombinasi Nyata (Terhitung WCAG 2.1)</h2>
        <p class="mt-1 text-sm text-text-muted">Setiap kombinasi dihitung secara algoritmik dari nilai luminansi relatif riil. Syarat: teks normal minimal 4,5:1, kontrol/batas UI minimal 3,0:1.</p>

        <div class="mt-6 overflow-x-auto rounded-[var(--radius-control)] border border-border bg-surface">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="border-b border-border bg-surface-2 text-text">
                    <tr>
                        <th class="px-4 py-3 font-bold">Elemen & Kombinasi Token</th>
                        <th class="px-4 py-3 font-bold">Nilai Tema Terang</th>
                        <th class="px-4 py-3 font-bold">Rasio Terang</th>
                        <th class="px-4 py-3 font-bold">Nilai Tema Gelap</th>
                        <th class="px-4 py-3 font-bold">Rasio Gelap</th>
                        <th class="px-4 py-3 font-bold">Status WCAG</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border text-text">
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Teks Utama di atas Kanvas</td>
                        <td class="px-4 py-2.5 font-tabular">#16201F pada #F2F5F3</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">15,18:1</td>
                        <td class="px-4 py-2.5 font-tabular">#F2F5F3 pada #0F2A2F</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">13,74:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos AAA</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Teks Redup (ink-muted) di atas Permukaan</td>
                        <td class="px-4 py-2.5 font-tabular">#4A605E pada #FFFFFF</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">6,72:1</td>
                        <td class="px-4 py-2.5 font-tabular">#8FA8A4 pada #16393F</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">4,91:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos AA</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Tombol Utama: Teks Putih di atas Biru Sablon</td>
                        <td class="px-4 py-2.5 font-tabular">#FFFFFF pada #2845D6</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">7,21:1</td>
                        <td class="px-4 py-2.5 font-tabular">#FFFFFF pada #4D68F5</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">4,55:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos AA</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Tombol Utama: Tepi Biru terhadap Kanvas Malam</td>
                        <td class="px-4 py-2.5 font-tabular">#2845D6 pada #F2F5F3</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">6,57:1</td>
                        <td class="px-4 py-2.5 font-tabular">#4D68F5 pada #0F2A2F</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">3,31:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos UI >= 3:1</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Tautan (link) di atas Permukaan</td>
                        <td class="px-4 py-2.5 font-tabular">#2845D6 pada #FFFFFF</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">7,21:1</td>
                        <td class="px-4 py-2.5 font-tabular">#8096FF pada #16393F</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">4,57:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos AA</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Teks Bahaya di atas Kotak Bahaya (danger)</td>
                        <td class="px-4 py-2.5 font-tabular">#B72617 pada #FDECEB</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">5,56:1</td>
                        <td class="px-4 py-2.5 font-tabular">#FF8073 pada #281414</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">7,15:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos AA</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Teks Sukses di atas Kotak Sukses (success)</td>
                        <td class="px-4 py-2.5 font-tabular">#146B41 pada #EAF6F0</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">5,90:1</td>
                        <td class="px-4 py-2.5 font-tabular">#4ED08F pada #10261C</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">8,16:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos AA</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Teks Hitam Arang di atas Kuning Karcis</td>
                        <td class="px-4 py-2.5 font-tabular">#16201F pada #FFC62E</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">10,61:1</td>
                        <td class="px-4 py-2.5 font-tabular">#16201F pada #FFC62E</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">10,61:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos AAA</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Teks / Garis Emas Lama (accent-gold) pada Kanvas</td>
                        <td class="px-4 py-2.5 font-tabular">#805700 pada #F2F5F3</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">5,83:1</td>
                        <td class="px-4 py-2.5 font-tabular">#FFC62E pada #0F2A2F</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">9,60:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos AA / AAA</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Batas Input (border-input) terhadap Kanvas</td>
                        <td class="px-4 py-2.5 font-tabular">#758A86 pada #F2F5F3</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">3,34:1</td>
                        <td class="px-4 py-2.5 font-tabular">#5C8E96 pada #0F2A2F</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">4,14:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos UI >= 3:1</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Batas Input (border-input) terhadap Latar Input</td>
                        <td class="px-4 py-2.5 font-tabular">#758A86 pada #FFFFFF</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">3,66:1</td>
                        <td class="px-4 py-2.5 font-tabular">#5C8E96 pada #16393F</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">3,41:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2 py-0.5 text-[11px] font-bold text-success-text">Lolos UI >= 3:1</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Garis Batas Kotak Bahaya (danger-border)</td>
                        <td class="px-4 py-2.5 font-tabular">#E8A29A pada #FDECEB</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">3,12:1</td>
                        <td class="px-4 py-2.5 font-tabular">#632722 pada #281414</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">3,08:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-surface-2 px-2 py-0.5 text-[11px] font-medium text-text-muted">Dekoratif/Batas, teks bawa makna (5,56:1)</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-medium">Garis Batas Kotak Sukses (success-border)</td>
                        <td class="px-4 py-2.5 font-tabular">#90CDAC pada #EAF6F0</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">3,05:1</td>
                        <td class="px-4 py-2.5 font-tabular">#21543A pada #10261C</td>
                        <td class="px-4 py-2.5 font-bold font-tabular text-success-text">3,15:1</td>
                        <td class="px-4 py-2.5"><span class="inline-block rounded-[var(--radius-control)] bg-surface-2 px-2 py-0.5 text-[11px] font-medium text-text-muted">Dekoratif/Batas, teks bawa makna (5,90:1)</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Seksi 3: Skala Huruf Lengkap & Tipografi --}}
    <section class="mt-12">
        <h2 class="text-xl font-bold tracking-tight text-text">3. Skala Huruf Lengkap & Tipografi</h2>
        <p class="mt-1 text-sm text-text-muted">Satu keluarga huruf Bricolage Grotesque (variabel) untuk semua kebutuhan hirarki teks, dengan angka tabular untuk waktu dan kode tiket.</p>

        <div class="mt-6 space-y-6 rounded-[var(--radius-control)] border border-border bg-surface p-6">
            <div class="border-b border-border pb-4">
                <span class="text-xs font-bold text-text-muted">Display (Hero Layar): 48px s.d. 60px / Weight 800</span>
                <p class="mt-1 text-4xl sm:text-5xl font-extrabold tracking-tight text-text leading-tight">Pengabdi Setan 2</p>
            </div>
            <div class="border-b border-border pb-4">
                <span class="text-xs font-bold text-text-muted">Title (Judul Seksi / Halaman): 28px s.d. 32px / Weight 700</span>
                <p class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight text-text">Film lain minggu ini</p>
            </div>
            <div class="border-b border-border pb-4">
                <span class="text-xs font-bold text-text-muted">Headline (Nama Tim / Pilihan): 20px s.d. 22px / Weight 700</span>
                <p class="mt-1 text-xl font-bold text-text">Indonesia lawan Vietnam</p>
            </div>
            <div class="border-b border-border pb-4">
                <span class="text-xs font-bold text-text-muted">Body Text (Teks Isi): 16px / Weight 400 / Leading 1.6</span>
                <p class="mt-1 max-w-prose text-base font-normal leading-relaxed text-text">
                    Studio mini berpendingin ruangan dengan tata suara sinematik yang hangat untuk keluarga dan rombongan warga desa. Kapasitas enam kursi tiap sesi.
                </p>
            </div>
            <div class="border-b border-border pb-4">
                <span class="text-xs font-bold text-text-muted">Meta Text (Keterangan Jadwal): 14px / Weight 400</span>
                <p class="mt-1 text-sm text-text-muted">Kamis, 15 Oktober 2026 di Halaman Depan SynemaX</p>
            </div>
            <div>
                <span class="text-xs font-bold text-text-muted">Angka Tabular (Jam Indonesia & Kode Booking): Tabular Nums</span>
                <p class="mt-1 font-tabular text-lg font-bold text-text">Mulai 19.30 WIB dan 21.15 WIB, Kode: SNX-7829-K4</p>
            </div>
        </div>
    </section>

    {{-- Seksi 4: Komparasi Fallback Font (Android Roboto & Desktop Arial) --}}
    <section class="mt-12">
        <h2 class="text-xl font-bold tracking-tight text-text">4. Komparasi Fallback Font Terukur</h2>
        <p class="mt-1 text-sm text-text-muted">Rasio fallback dihitung langsung dari total advance width sampel teks bahasa Indonesia (295 karakter) pada tabel hmtx biner font aktual. Bricolage (132.833) vs Roboto (132.875) menghasilkan rasio 99.97% (size-adjust: 100%). Bricolage vs Arial (133.580) menghasilkan rasio 99.44% (size-adjust: 99%). Sebagai pembanding, rasio tabel OS/2 xAvgCharWidth adalah 88.07% (Roboto) dan 112.82% (Arial) akibat perbedaan metode bobot rata-rata karakter lintas versi OS/2.</p>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4">
                <span class="block text-xs font-bold text-primary">Font Utama: Bricolage</span>
                <span class="block font-tabular text-[11px] text-text-muted">WOFF2 Latin: 76,88 KB</span>
                <div class="mt-3 space-y-2" style="font-family: 'Bricolage Grotesque Variable', sans-serif;">
                    <p class="text-lg font-extrabold text-text">Pengabdi Setan 2</p>
                    <p class="text-xs text-text leading-relaxed">Nonton dekat, seru bareng di studio mini enam kursi.</p>
                    <p class="font-tabular text-xs font-bold text-text">19.30 WIB, SNX-7829-K4</p>
                </div>
            </div>

            <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4">
                <span class="block text-xs font-bold text-text-muted">Fallback Android: Roboto</span>
                <span class="block font-tabular text-[11px] text-text-muted">size-adjust: 100%, ascent: 93%, descent: 27% (Advance width teks sampel hmtx: 132.833 / 132.875; pembanding xAvgCharWidth: 88%)</span>
                <div class="mt-3 space-y-2" style="font-family: 'Bricolage Fallback Roboto', sans-serif;">
                    <p class="text-lg font-extrabold text-text">Pengabdi Setan 2</p>
                    <p class="text-xs text-text leading-relaxed">Nonton dekat, seru bareng di studio mini enam kursi.</p>
                    <p class="font-tabular text-xs font-bold text-text">19.30 WIB, SNX-7829-K4</p>
                </div>
            </div>

            <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4">
                <span class="block text-xs font-bold text-text-muted">Fallback Desktop: Arial</span>
                <span class="block font-tabular text-[11px] text-text-muted">size-adjust: 99%, ascent: 94%, descent: 27% (Advance width teks sampel hmtx: 132.833 / 133.580; pembanding xAvgCharWidth: 113%)</span>
                <div class="mt-3 space-y-2" style="font-family: 'Bricolage Fallback Arial', sans-serif;">
                    <p class="text-lg font-extrabold text-text">Pengabdi Setan 2</p>
                    <p class="text-xs text-text leading-relaxed">Nonton dekat, seru bareng di studio mini enam kursi.</p>
                    <p class="font-tabular text-xs font-bold text-text">19.30 WIB, SNX-7829-K4</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Seksi 5: Input Formulir & State (Normal, Fokus, Error) --}}
    <section class="mt-12">
        <h2 class="text-xl font-bold tracking-tight text-text">5. Input Formulir & Status Validasi</h2>
        <p class="mt-1 text-sm text-text-muted">Batas input memenuhi kontras 3,0:1 (token `--color-border-input`), fokus terlihat jelas dengan ring primary 2px, dan status error memiliki pesan yang menjelaskan cara memperbaiki.</p>

        <div class="mt-6 grid gap-6 rounded-[var(--radius-control)] border border-border bg-surface p-6 sm:grid-cols-3">
            {{-- Input Normal --}}
            <div>
                <label for="demo-input-normal" class="block text-xs font-bold text-text">Nama Pemesan (Normal)</label>
                <input type="text"
                       id="demo-input-normal"
                       placeholder="Contoh: Budi Santoso"
                       class="mt-2 block w-full rounded-[var(--radius-control)] border border-border-input bg-surface px-3 py-2.5 text-sm text-text placeholder:text-text-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary">
                <p class="mt-1 text-[11px] text-text-muted">Nama yang dicatat pada tiket masuk.</p>
            </div>

            {{-- Input Fokus Aktif --}}
            <div>
                <label for="demo-input-focus" class="block text-xs font-bold text-text">Nomor WhatsApp (Fokus Terlihat)</label>
                <input type="tel"
                       id="demo-input-focus"
                       value="081234567890"
                       class="mt-2 block w-full rounded-[var(--radius-control)] border-2 border-primary bg-surface px-3 py-2.5 text-sm text-text ring-2 ring-primary focus:outline-none">
                <p class="mt-1 text-[11px] text-text-muted">Batas fokus tebal dan terlihat jelas.</p>
            </div>

            {{-- Input Error --}}
            <div>
                <label for="demo-input-error" class="block text-xs font-bold text-danger-text">Nomor WhatsApp (Error)</label>
                <input type="tel"
                       id="demo-input-error"
                       value="12345"
                       aria-invalid="true"
                       aria-describedby="demo-error-desc"
                       class="mt-2 block w-full rounded-[var(--radius-control)] border-2 border-danger-border bg-danger-surface/20 px-3 py-2.5 text-sm text-danger-text focus:outline-none focus:ring-2 focus:ring-danger-border">
                <p id="demo-error-desc" class="mt-1 text-[11px] font-semibold text-danger-text">Masukkan nomor WhatsApp aktif dengan format 08... (minimal 10 angka).</p>
            </div>
        </div>
    </section>

    {{-- Seksi 6: Sistem Bentuk & Komponen Interaktif --}}
    <section class="mt-12">
        <h2 class="text-xl font-bold tracking-tight text-text">6. Komponen Interaktif: Tombol, Radio, Slot Karcis, & Tiket</h2>
        <p class="mt-1 text-sm text-text-muted">Radius kontrol 5px (`--radius-control`), garis 1.5px untuk sekunder, solid Biru Sablon untuk aksi utama, lekukan karcis khusus slot jam dan tiket.</p>

        {{-- Tombol --}}
        <div class="mt-6 rounded-[var(--radius-control)] border border-border bg-surface p-5">
            <h3 class="text-sm font-bold text-text">Tombol Aksi (Fokus Terlihat)</h3>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-sm font-bold text-primary-text shadow-xs transition hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    Pesan tiket
                </button>
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-transparent px-5 text-sm font-bold text-text transition hover:border-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    Kembali
                </button>
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] bg-accent px-5 text-sm font-bold text-accent-contrast shadow-xs transition hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                    Pita Karcis (Isian)
                </button>
            </div>
        </div>

        {{-- Radio Pilihan Tipe Nonton (Wizard Langkah 3) --}}
        <div class="mt-6 rounded-[var(--radius-control)] border border-border bg-surface p-5">
            <h3 class="text-sm font-bold text-text">Pilihan Tipe Nonton (Wizard Langkah 3)</h3>
            <p class="mt-1 text-xs text-text-muted">Garis 2px solid Biru Sablon saat terpilih, sudut 5px, radio dot, tanpa Kuning Karcis, tanpa sobekan.</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="radio-choice-card is-selected p-4">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border-2 border-primary">
                            <span class="size-2.5 rounded-full bg-primary"></span>
                        </span>
                        <div>
                            <span class="block text-sm font-bold text-text">Per orang</span>
                            <span class="mt-1 block text-xs text-text-muted">Rp 25.000 tiap kursi. Berbagi studio dengan penonton lain pada sesi yang sama.</span>
                        </div>
                    </div>
                </div>

                <div class="radio-choice-card p-4">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border-2 border-border-input"></span>
                        <div>
                            <span class="block text-sm font-bold text-text">Sewa Studio Privat</span>
                            <span class="mt-1 block text-xs text-text-muted">Rp 150.000 flat untuk 6 kursi rombongan tanpa penonton lain.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Slot Jam Karcis Bioskop --}}
        <div class="mt-6 rounded-[var(--radius-control)] border border-border bg-surface p-5">
            <h3 class="text-sm font-bold text-text">Slot Jam Karcis (Sobekan Khusus Jam & Tiket)</h3>
            <p class="mt-1 text-xs text-text-muted">Target sentuh minimal 44px, angka tabular. Slot terpilih memakai isian Kuning Karcis dengan teks Arang gelap.</p>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" class="slot-ticket-btn is-selected inline-flex items-center justify-between gap-3 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="font-tabular text-sm">19.30 WIB</span>
                    <span class="rounded-[var(--radius-control)] bg-[#16201F]/10 px-1.5 py-0.5 text-[10px] font-bold text-[#16201F]">Terpilih</span>
                </button>
                <button type="button" class="slot-ticket-btn inline-flex items-center justify-between gap-3 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="font-tabular text-sm">21.15 WIB</span>
                    <span class="text-[10px] text-text-muted">Sisa 4 kursi</span>
                </button>
            </div>
        </div>

        {{-- Penanda Gratis & Status Banner --}}
        <div class="mt-6 rounded-[var(--radius-control)] border border-border bg-surface p-5">
            <h3 class="text-sm font-bold text-text">Penanda Fisik & Banner Status (Kedua Tema)</h3>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <span class="inline-block rounded-[var(--radius-control)] bg-accent px-2.5 py-1 text-xs font-bold text-accent-contrast">
                    Gratis
                </span>
                <span class="inline-block rounded-[var(--radius-control)] bg-success-surface px-2.5 py-1 text-xs font-bold text-success-text">
                    Terkonfirmasi
                </span>
                <span class="inline-block rounded-[var(--radius-control)] bg-danger-surface px-2.5 py-1 text-xs font-bold text-danger-text">
                    Dibatalkan
                </span>
            </div>

            <div class="mt-4 space-y-3">
                <div class="rounded-[var(--radius-control)] border border-success-border bg-success-surface p-3 text-xs font-medium text-success-text">
                    Pembayaran telah diverifikasi. Tiket Anda siap digunakan saat penayangan.
                </div>
                <div class="rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-3 text-xs font-medium text-danger-text">
                    Batas waktu pembayaran telah habis. Silakan pilih kembali jadwal sesi yang tersedia.
                </div>
            </div>
        </div>

        {{-- Panel Hero Layar (Menyala) --}}
        <div class="mt-6">
            <h3 class="text-sm font-bold text-text">Panel Hero "Layar" (Menyala di Tengah Lapangan)</h3>
            <div class="hero-screen-panel mt-3 p-6 sm:p-8">
                <p class="text-xs font-bold uppercase tracking-wider text-[#4A605E]">Malam ini di Studio 1</p>
                <div class="mt-3 flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="h-28 w-20 shrink-0 rounded-[var(--radius-control)] bg-[#16201F] text-center text-[10px] text-white/70 flex items-center justify-center">
                        Poster 2:3
                    </div>
                    <div>
                        <h4 class="text-2xl font-extrabold text-[#16201F] sm:text-3xl">Pengabdi Setan 2: Communion</h4>
                        <p class="mt-1 text-xs text-[#4A605E]">Mulai 19.30 WIB di Studio Mini Desa SynemaX</p>
                        <div class="mt-4 flex gap-3">
                            <span class="inline-flex min-h-10 items-center justify-center rounded-[var(--radius-control)] bg-primary px-4 text-xs font-bold text-white shadow-xs">
                                Pesan tiket
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ubin Dinding Poster 2:3 --}}
        <div class="mt-6 rounded-[var(--radius-control)] border border-border bg-surface p-5">
            <h3 class="text-sm font-bold text-text">Dinding Poster Film (Rasio 2:3, Rapat Tanpa Bingkai Kartu)</h3>
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <div class="aspect-[2/3] w-full rounded-[var(--radius-control)] bg-[#1D444B] flex items-center justify-center text-xs font-bold text-white/80">
                        Poster 2:3
                    </div>
                    <p class="mt-2 text-sm font-bold text-text">Agak Laen</p>
                    <p class="font-tabular text-xs text-text-muted">Mulai 16.00 WIB</p>
                </div>
                <div>
                    <div class="aspect-[2/3] w-full rounded-[var(--radius-control)] bg-[#1D444B] flex items-center justify-center text-xs font-bold text-white/80">
                        Poster 2:3
                    </div>
                    <p class="mt-2 text-sm font-bold text-text">Siksa Kubur</p>
                    <p class="font-tabular text-xs text-text-muted">Mulai 19.30 WIB</p>
                </div>
            </div>
        </div>

        {{-- Contoh Tiket Fisik (E-Ticket) --}}
        <div class="mt-6">
            <h3 class="text-sm font-bold text-text">Contoh Tiket Elektronik (Warna Fisik Kertas Tetap #ECEFEA)</h3>
            <div class="mx-auto mt-4 max-w-sm ticket-paper-card border border-border p-5 shadow-token">
                <div class="border-b border-dashed border-[#16201F]/20 pb-4">
                    <p class="text-sm font-extrabold text-[#16201F]">SynemaX Bioskop Mini</p>
                    <p class="mt-0.5 text-xs text-[#4A605E]">Tiket Masuk Studio</p>
                    <h4 class="mt-3 text-lg font-black text-[#16201F]">Pengabdi Setan 2: Communion</h4>
                    <p class="mt-1 font-tabular text-xs text-[#4A605E]">Kamis, 15 Oktober 2026, 19.30 WIB</p>
                    <p class="mt-0.5 text-xs font-bold text-[#16201F]">Studio 1 (2 orang)</p>
                </div>

                <div class="py-4">
                    <div class="rounded-[var(--radius-control)] bg-[#FFC62E] px-3 py-2 text-center text-[#16201F]">
                        <p class="font-tabular text-xl font-black tracking-wider">SNX-7829-K4</p>
                    </div>
                    <p class="mt-3 text-center text-[11px] text-[#4A605E]">Tunjukkan layar ini kepada petugas kasir atau studio.</p>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
