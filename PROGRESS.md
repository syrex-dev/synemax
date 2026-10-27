# Progress Proyek SynemaX

Dokumen ini mencatat status pengerjaan fitur dan perbaikan agar progres tetap transparan dan dapat dilanjutkan dengan aman kapan saja.

## Status Ringkas
- **Tahap Audit & Checkpoint**: SELESAI (`commit: 35fda5d` - Checkpoint: alur pembayaran dan tiket)
- **Tahap 1 (C1: Private vs Per Orang)**: SELESAI (`commit: ce83a03`)
- **Tahap 2 (C2: Halaman Pembayaran & Perbaikan QRIS)**: SELESAI (`commit: e7fa6b1`)
- **Tahap 2b (Keamanan URL Bersih & Referrer-Policy)**: SELESAI (`commit: f55cf0e`)
- **Tahap 3 (Checklist 3: Fitur Info & Nobar)**: SELESAI
- **Tahap 4 (Testing & Validasi Style Linting)**: SELESAI (125 tests lulus, Pint lulus 100%)
- **Tahap 5 (Ruang Poster Nobar & Pengelolaan Media)**:
  - Sub-tahap A (Admin, Layanan Media, Migrasi & Duplikasi): SELESAI (`commit: 43d77ad`)
  - Sub-tahap B (Halaman Detail /nobar/{slug}, Modal Zoom & Tombol Aksi): SELESAI (`commit: 5ad59d0`)
  - Sub-tahap C (Thumbnail 4:5 Listing & Beranda, Open Graph 1200x630): SELESAI (`commit: c4265dc`)
  - Sub-tahap D (Testing Otomatis, Pint & Verifikasi Visual): SELESAI (`commit: 3229c5a`)
- **Tahap 6 (Redesain Visual Publik — Arah: "Layar Tancap")**:
  - Langkah 1 (Rencana Desain, Token, Sketsa ASCII & Perbandingan Kritis): SELESAI & DISETUJUI
  - Langkah 2 (Halaman `/styleguide` Lingkungan Lokal & Catatan A–F): SELESAI (`commit: 70c30c6`, `commit: 583b5ae`)
  - Langkah 3 (Penerapan Bertahap):
    - 3(a) Shell Publik, Komponen `<x-brand.logo>`, Penyelarasan Tema Admin: SELESAI (`commit: 25c52c7`)
    - 3(b) Beranda (Hero Layar Menyala & Dinding Poster) dan Jadwal 7 Hari: SELESAI (`commit: df4365b`)
    - Perbaikan Catatan A s.d. E (Audit CSS, Baseline Ukuran, Font Stretch, Test Baru): SELESAI (`commit: b4abb24`)
    - 3(c) Wizard Booking Semua Langkah (Slot Sobekan Kuning, Radio Pilihan Biru Sablon, 1 Kolom): SELESAI (`commit: fccf956`)
    - 3(d) Pembayaran, Tiket Kertas #ECEFEA, Cek Booking & Booking Saya: SELESAI
    - 3(e) s.d. 3(f): MENUNGGU (Berhenti untuk pemeriksaan pengguna di HP)
  - Langkah 4 (Kritik Diri & Pembersihan CSS): MENUNGGU

---

## Rincian Tahap & Status

### Tahap 1: C1 — Aturan Sewa Private vs Booking Per Orang
- [x] Penyelarasan flag `is_private` pada sesi: otomatis kembali `false` jika booking private dibatalkan, kedaluwarsa, ditolak, atau dipulihkan/diubah admin (`Session::syncPrivateStatus()`).
- [x] Sinkronisasi pesan penolakan server (`BookingService.php`):
  - `"Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia."`
  - `"Sesi ini sudah disewa private."`
- [x] Endpoint ketersediaan segar `GET /booking/{session}/ketersediaan` dengan throttling (`throttle:60,1`), header `Cache-Control: no-store`, tanpa data pemesan, penanganan sesi kedaluwarsa/tidak ada, dan toleransi kegagalan jaringan di frontend.
- [x] Langkah Tipe Wizard (`booking-form.blade.php`):
  - Fetch ketersediaan segar saat masuk langkah Tipe & saat submit.
  - Kartu Private nonaktif dengan atribut `disabled` sungguhan pada input + `aria-disabled="true"`.
  - Teks alasan dikaitkan dengan `aria-describedby="private-desc"`.
  - Pembatalan otomatis state `sessionStorage` lama jika sesi sudah terisi per orang.
- [x] Test komprehensif C1: `PrivateBookingRulesTest.php` (9 tests, 45 assertions lulus hijau).

### Tahap 2: C2 — Pembersihan Halaman Pembayaran & Perbaikan QRIS
- [x] Hapus indikator tahap (stepper 3-tahap) di `/pembayaran/{kode}` dan rapikan layout langsung mulai dari hitung mundur batas waktu.
- [x] Hapus tag duplikat `</form>` di `resources/views/admin/settings/edit.blade.php`.
- [x] Temukan & atasi akar masalah kotak putih QRIS:
  - Request gambar `/pembayaran/qris` mengembalikan HTTP 200 dengan `Content-Type: image/jpeg` (78,5 KB).
  - Gambar tersimpan berformat landscape 768×432 (16:9).
  - CSS sebelumnya memaksakan `aspect-square w-full object-contain` di dalam kartu ber-padding putih sehingga gambar menyusut drastis menjadi tinggi 123px dengan ruang kosong putih besar atas-bawah (tampak seperti kotak putih kosong).
  - Diperbaiki: kontainer fleksibel mengikuti rasio asli gambar, `max-h-72 sm:max-h-80 w-auto max-w-full mx-auto object-contain`, latar putih + padding, dan modal perbesar tetap utuh tanpa dipotong.
- [x] Peringatan rasio gambar QRIS di admin settings:
  - Jika rasio gambar jauh dari persegi (`< 0.8` atau `> 1.25`), sistem menampilkan pesan: *"QRIS sebaiknya persegi dan rapi; pangkas bagian lain."*
  - Berfungsi di backend (flash session toast saat upload tanpa memotong otomatis) dan di frontend (Alpine.js live check saat admin memilih file sebelum upload, serta badge peringatan jika gambar aktif yang tersimpan non-persegi).
- [x] Update test di `BookingPaymentFlowTest.php` (stepper assertion dihapus dan diganti dengan assertion `assertDontSee`) dan test baru di `QrisPaymentFlowTest.php` (`test_admin_upload_non_square_qris_warns_ratio_without_auto_crop`).

### Tahap 2b: Keamanan URL Bersih & Header Referrer-Policy
- [x] Nomor WhatsApp TIDAK pernah tampil di URL halaman pembayaran atau tiket.
- [x] Jika akses menyertakan query parameter `?wa=` atau `?whatsapp=`:
  - Jika nomor cocok, otorisasi dicatat di session, lalu otomatis di-redirect (302) ke URL bersih (`/pembayaran/{kode}` atau `/tiket/{kode}`).
  - Jika nomor tidak cocok, langsung ditolak dengan `403 Forbidden`.
- [x] Ditambahkan header `Referrer-Policy: same-origin` pada respons halaman pembayaran dan tiket agar URL dan kode booking tidak bocor ke pihak ketiga (misalnya WhatsApp Web).
- [x] Alur pembuatan booking baru dan alur cek booking via form tetap berjalan bersih tanpa parameter query.
- [x] Pengujian otomatis pada `BookingPaymentFlowTest.php` lulus hijau.

### Tahap 3: Fitur Info & Nobar
- [x] Migrasi & Model:
  - `database/migrations/2026_10_15_000001_create_nobar_events_table.php` (tabel `nobar_events`: kickoff_at, durasi_menit, tim_a, tim_b, lokasi_nama, alamat_patokan, maps_url, harga_info, is_free, status_acara, alasan_status, poster_path, status_publikasi, is_pinned).
  - `database/migrations/2026_10_15_000002_create_news_articles_table.php` (tabel `news_articles`: judul, slug, kategori, ringkasan, konten, cover_path, status_publikasi, tanggal_terbit, is_pinned).
  - Model `NobarEvent` dengan accessor status cerdas (`Akan datang`, `Hari ini`, `Berlangsung`, `Selesai`, `Ditunda`, `Dibatalkan`), badge class, generator `.ics` kalender, dan URL share WhatsApp.
  - Model `NewsArticle` dengan lazy scheduling check (`isLive()`, `isScheduled()`), kategori badge, dan URL share WhatsApp.
- [x] Sanitasi Safe Markdown (`app/Support/SafeMarkdown.php`):
  - Menggunakan `Str::markdown` bawaan Laravel dengan `html_input = 'strip'` dan `allow_unsafe_links = false`.
  - Integrasi `League\CommonMark\Extension\ExternalLink\ExternalLinkExtension` otomatis menambahkan `rel="nofollow noopener noreferrer" target="_blank"` ke seluruh tautan luar tanpa regex manual.
- [x] Layanan Media & Unggah Mandiri (`app/Services/MediaUploadService.php` & `app/Http/Controllers/MediaController.php`):
  - Pemeriksaan dukungan WebP GD (`imagewebp`), resize otomatis maks. 1400px, konversi ke WebP kualitas 85 dengan fallback aman.
  - Rute `/media/nobar/{filename}` dan `/media/berita/{filename}` melayani gambar langsung dengan header `Cache-Control: public, max-age=86400` tanpa ketergantungan `php artisan storage:link`.
- [x] Halaman Publik:
  - `/nobar`: Katalog acara dengan filter (Semua, Akan Datang, Selesai).
  - `/nobar/{slug}`: Halaman detail lengkap dengan Open Graph meta, tombol buka Google Maps, download kalender `.ics`, dan ajak teman via WhatsApp.
  - `/kabar`: Katalog artikel dengan filter kategori (Semua, Berita, Promo, Pengumuman).
  - `/kabar/{slug}`: Detail artikel lengkap dengan Open Graph, render markdown aman, tombol bagikan, dan daftar artikel terkait.
  - Beranda (`/`): Menampilkan kartu Nobar Komunitas terdekat dan Kabar & Promo terbaru jika fitur aktif.
- [x] Antarmuka Admin:
  - Kelola Nobar (`/admin/nobar`): Index tabel status, form create/edit, preview publik, dan modal ganti status cepat (Ditunda / Dibatalkan beserta alasannya).
  - Kelola Berita (`/admin/berita`): Index tabel filter kategori/status, form create/edit dengan penghitung karakter lead dan panduan markdown, serta preview publik.
  - Pengaturan (`/admin/pengaturan`): Toggle aktivasi fitur Info & Nobar.
- [x] Seeder Generik Bertema Nobar (`database/seeders/NobarAndNewsSeeder.php`):
  - Data contoh nobar generik tanpa nama klub berhak cipta (e.g. `Tim Garuda Muda vs Tim Bintang Timur`, `Tim Singa Perkasa vs Tim Rajawali`) berawalan `[Contoh]`.
  - Data contoh artikel dan promo tanpa klaim komersial berhak cipta.
  - Isolasi total 100% dari logika booking tiket bioskop reguler.

### Tahap 4: Testing & Validasi Style Linting
- [x] Test `SafeMarkdownTest`: 5 test assertions lulus uji sanitasi XSS (tag `<script>`, `onerror`, `javascript:`, dan rel/target tautan luar).
- [x] Test `NobarEventTest`: 8 tests komprehensif menguji katalog publik, draft pratinjau admin, detail OG, stream ICS kalender, validasi URL maps HTTPS, serta perubahan status penundaan/pembatalan.
- [x] Test `NewsArticleTest`: 7 tests menguji listing publik, lazy scheduling penjadwalan terbit, detail OG, admin CRUD, pin to top, dan soft deletes.
- [x] Test `MediaServingTest`: 3 tests menguji respons media poster/cover, header caching HTTP, proteksi directory traversal, dan 404 file hilang.
- [x] Seluruh test suite (`php artisan test`): **125 tests passed**, 691 assertions (3 skipped khusus uji race MySQL terisolasi).
- [x] Formatting & Linting (`vendor/bin/pint --test`): **100% passed** tanpa pelanggaran code style.

### Tahap 5: Ruang Poster Nobar & Pengelolaan Media
- [x] **Sub-tahap A: Admin, Layanan Media, Migrasi & Duplikasi**:
  - Migrasi `2026_10_15_000003_add_poster_details_to_nobar_events_table.php` menambahkan kolom `poster_width`, `poster_height`, `poster_alt`, `poster_og_path` (nullable) dan otomatis menghitung dimensi poster lama (`getimagesize`) saat migrasi.
  - `MediaUploadService`:
    - Membatasi total piksel maks 40 megapiksel sebelum decode GD guna mencegah OOM.
    - Validasi MIME sungguhan (JPG/PNG/WebP), tolak SVG, sisi terpendek minimal 600px.
    - Pengecilan hanya jika sisi terpanjang > 1600px tanpa pemotongan (menjaga rasio asli dan ketajaman teks poster).
    - Membaca dan menormalisasi orientasi EXIF pada JPEG lalu membuang metadata privasi.
    - Konversi ke WebP kualitas 85 dengan fallback format asli + catatan log peringatan bila ekstensi WebP GD absen.
    - Otomatis membuat varian Open Graph 1200x630 (poster utuh terpusat di atas latar blur gelap).
    - Penanganan salinan fisik independen untuk poster dan OG saat duplikasi acara.
    - Otomatis menghapus berkas poster dan OG lama saat diperbarui atau dihapus.
  - `NobarController`:
    - Validasi batas PHP (`upload_max_filesize` / `post_max_size`) sebelum validasi Laravel dengan pesan bahasa Indonesia yang jelas.
    - Form admin untuk poster potret 4:5, pratinjau, tombol ganti/hapus, dan field teks alternatif poster.
    - Aksi `duplicate()` acara nobar sebagai draft baru dengan salinan poster independen.
    - Pembersihan seluruh emoji dekoratif dari tabel dan modal admin.
  - `MediaController`:
    - Validasi regex nama file yang ketat (hanya alfanumerik, dash, underscore, dan ekstensi gambar aman), tolak path traversal (`..`), 404 jika tidak ditemukan, dan header cache HTTP `public, max-age=86400`.
- [x] **Sub-tahap B: Ruang Poster Publik, Modal Zoom & Perapian Tombol Aksi**:
  - Tata letak 2 kolom adaptif di desktop (`lg:grid lg:grid-cols-[380px,1fr]`) dengan kolom poster sticky di kiri (`lg:sticky lg:top-24 max-h-[calc(100vh-7rem)] overflow-y-auto`), dan tumpukan vertikal alami di mobile.
  - Latar netral untuk poster berlatar putih (`bg-surface-2/60 border border-border rounded-3xl p-3 sm:p-4`), tampilan single-column bersih tanpa celah kosong jika acara tanpa poster.
  - Modal perbesar poster Alpine.js dengan penguncian scroll body (`overflow-hidden`), focus trap keyboard, pengembalian fokus ke elemen pemanggil saat ditutup, backdrop click, dan tombol simpan poster (`poster-{slug}.{ext}`).
  - Tombol aksi standar (tinggi minimal 44px / `min-h-12`, ikon SVG tajam, teks non-breaking), serta penghapusan seluruh emoji dekoratif (`📅`, `📍`, `🎟️`, `⚽`, `⚠️`).
- [x] **Sub-tahap C: Thumbnail 4:5 Listing & Beranda, Open Graph 1200x630**:
  - Tampilkan thumbnail potret 4:5 (`aspect-[4/5] object-cover object-top`) pada kartu nobar di `/nobar` dan teaser beranda `/`.
  - Tampilan elegan tanpa bingkai kosong pada kartu nobar tanpa poster.
  - Pasang meta `og:image` absolut (1200x630) publik tanpa login, dengan fallback otomatis ke `public/social-banner.png`.
  - Pembersihan menyeluruh 100% seluruh emoji dekoratif dari teks WhatsApp share, .ics kalender, halaman artikel/nobar/beranda, tabel/form admin, dan seeder.
  - Penanganan batas unggahan `php.ini` (`upload_max_filesize` / `post_max_size`) pada poster, QRIS, dan bukti transfer dengan pesan bahasa Indonesia yang jelas.
  - Dokumentasi konfigurasi `php.ini` (`upload_max_filesize >= 4M`, `post_max_size >= 8M`) serta ketentuan HTTPS domain publik untuk pratinjau WhatsApp di `README.md`.
- [x] **Sub-tahap D: Pengujian Komprehensif, Linting & Verifikasi Visual**:
  - `MediaServingTest`: penolakan path traversal (`..`, `..%2F`, karakter aneh) dan ekstensi tidak sah (`.php`, `.svg`, `.exe`).
  - `NobarPosterFeatureTest`:
    - Dimensi null pada `poster_width`/`poster_height` tidak menimbulkan error atau layout shift.
    - Acara tanpa poster menampilkan tata letak satu kolom bersih.
    - Duplikasi acara menyalin berkas poster & OG independen; penghapusan poster hasil duplikat tidak memengaruhi acara asal.
    - Validasi dimensi minimum (sisi terpendek < 600px ditolak) dan penolakan format SVG.
    - Tag meta Open Graph mengarah ke URL absolut dan fallback ke `social-banner.png`.
  - `MediaUploadServiceTest`:
    - Pemrosesan poster potret sukses membuat poster dan varian OG 1200x630.
    - Pembatasan resolusi memori maks 40 MP.
    - Salinan berkas fisik terisolasi pada duplikasi.
  - Seluruh test suite (`php artisan test`): **140 tests (137 passed, 3 skipped)**, 761 assertions lulus hijau.
  - Formatting & Linting (`vendor/bin/pint --test`): **100% passed** tanpa pelanggaran.

### Tahap 6: Redesain Visual Publik (Arah: "Layar Tancap")
- [x] **Langkah 1: Rencana Desain & Perbandingan Kritis**: SELESAI & DISETUJUI
  - Perancangan token terpusat (`resources/css/app.css`): palet Layar, Malam, Arang, Biru Sablon, Kuning Karcis, Merah Cabe, Hijau Daun.
  - Tipografi tunggal Bricolage Grotesque (variabel self-hosted npm, angka tabular).
  - Sistem bentuk: radius kontrol 5px (`--radius-control`), lekukan sobekan karcis pada tiket & slot terpilih, rasio poster 2:3 rapat tanpa bingkai.
  - Konsep layout & sketsa ASCII untuk Beranda (desktop & mobile 360px), Detail Nobar, Langkah Wizard (Langkah 3 Tipe Nonton), dan Tiket.
  - Perbandingan kritis terhadap pola generik AI bioskop dan daftar DILARANG.
- [x] **Langkah 2: Halaman `/styleguide` Lokal**: SELESAI
  - Halaman katalog token dan komponen di `routes/web.php` aktif hanya saat `app()->isLocal()` (404 di produksi).
  - Paket font `@fontsource-variable/bricolage-grotesque` terpasang (WOFF2 latin 76,88 KB, metrik fallback terukur).
  - Tabel kontras kombinasi nyata WCAG 2.1 terhitung lengkap.
  - Test otomatis `PublicPagesTest` untuk akses lokal & proteksi 404 produksi.
  - Seluruh test (142 tests: 139 passed, 3 skipped) dan Pint 100% passed.
- [x] **Langkah 3 (Penerapan Bertahap a–d)**:
  - [x] (a) Token, font, shell (`layouts/public.blade.php`), komponen logo tunggal `<x-brand.logo>`, dan penyelarasan tema admin (`layouts/admin.blade.php`).
  - [x] (b) Beranda (`home.blade.php`, hero layar menyala fade-in 600ms, dinding poster 2:3 rapat, tanpa pengulangan harga/tombol) dan jadwal (`schedule.blade.php`, tata letak 1 kolom tenang, jam tabular).
  - [x] Catatan A s.d. E: Audit CSS lama 28 token/kelas terbukti dan terpelihara, baseline git worktree terukur, @font-face stretch 75% 100% lengkap, test public pages lulus.
  - [x] (c) Wizard booking semua langkah: satu kolom tenang (`max-w-xl`), slot jam terpilih dengan sobekan karcis dan Kuning Karcis, pilihan tipe nonton radio border 2px Biru Sablon tanpa sobekan/kuning, nomor langkah "Langkah X dari 5", hook Alpine.js dan input utuh 100%.
  - [x] (d) Pembayaran, tiket digital fisik kertas `#ECEFEA` di kedua tema dengan sobekan karcis tengah & pita kode Kuning Karcis Arang tabular, aksi tiket utuh & cetak rapi, Cek Booking & Booking Saya tenang dengan penjelasan cara memperbaiki.
- [ ] **Langkah 3 (Penerapan Bertahap e–f)**:
  - (e) Nobar (katalog & detail) dan Kabar/Promo.
  - (f) Halaman error, footer, dan pembersihan token lama.
- [ ] **Langkah 4: Kritik Diri & Pembersihan CSS**.

---

## Keputusan Penting
1. **Aturan Migrasi**: Migrasi `2026_10_14_000004_update_bookings_for_payment_flow` sudah tercatat `Ran` (Batch 4), sehingga tidak diubah langsung. Migrasi baru dibuat untuk tabel tambahan (`nobar_events`, `news_articles`, dan penambahan kolom poster).
2. **Derivasi Flag `is_private`**: Flag `is_private` pada sesi disinkronkan secara konsisten setiap kali siklus hidup booking berubah (create, cancel, expire, reject, restore).
3. **Endpoint Ketersediaan**: Dibuat sebagai endpoint publik ringan khusus angka dan boolean status tanpa mengekspos identitas pelanggan, dengan proteksi cache `no-store` dan throttling.
4. **Isolasi Fitur Nobar & Berita**: Fitur nobar dan kabar dipisahkan sepenuhnya dari engine reservasi kursi, pembayaran, dan pesanan jajan untuk menjaga integritas transaksi bisnis inti.
5. **Media Serving Tanpa Storage Link**: Serving media poster & cover menggunakan controller internal dengan HTTP caching (`max-age=86400`) agar kompatibel di lingkungan deployment apa pun tanpa memerlukan `storage:link`.
6. **Varian Open Graph 1200x630**: Setiap poster potret nobar otomatis dibuatkan varian landscape 1200x630 dengan poster utuh di tengah latar belakang blur gelap, sehingga tidak terpotong saat dibagikan ke WhatsApp dan media sosial.
7. **Tabel Nama Langkah dan Nama Aksi Wizard & Alur Transaksi**:
   | Tahap / Halaman | File & Baris | Nama Langkah / Layar | Tombol Aksi Utama | Tombol Aksi Sekunder |
   | :--- | :--- | :--- | :--- | :--- |
   | Wizard Langkah 1 | `schedule.blade.php:80` | Jadwal & Sesi | Pilih jam sesi | Ganti tanggal 7 hari |
   | Wizard Langkah 2 | `booking-form.blade.php:320` | Pilih film | Lanjut ke tipe nonton | Kembali ke jadwal |
   | Wizard Langkah 3 | `booking-form.blade.php:420` | Tipe nonton | Lanjut ke pilihan jajan / Lanjut ke ringkasan | Kembali |
   | Wizard Langkah 4 | `booking-form.blade.php:570` | Pilihan jajan | Lanjut ke ringkasan | Kembali |
   | Wizard Langkah 5 | `booking-form.blade.php:670` | Ringkasan | Kirim booking (atau Booking & bayar) | Kembali |
   | Pembayaran | `payment.blade.php:96` | Sisa Waktu Pembayaran | Kirim bukti bayar | Kirim bukti lewat WhatsApp, Batalkan booking |
   | Tiket Digital | `ticket.blade.php:226` | Tiket Masuk Studio | Simpan tiket | Hubungi admin, Ubah jajan via WhatsApp, Booking lagi |
   | Cek Booking | `booking-status.blade.php:31` | Cek booking | Buka tiket atau pembayaran | Sentuh kode di "Booking saya di perangkat ini" |

---

## Perintah Yang Perlu Dijalankan Pengguna
1. **Jalankan Migrasi Database Baru (Menambahkan kolom poster_width, poster_height, poster_alt, poster_og_path)**:
   ```bash
   php artisan migrate
   ```
   *(Migrasi `2026_10_15_000003_add_poster_details_to_nobar_events_table.php` akan menambahkan kolom poster secara aman dan mengisi dimensi poster yang sudah ada).*

2. **Jalankan Test Suite (Opsional untuk memastikan seluruh fitur hijau di terminal pengguna)**:
   ```bash
   php artisan test
   ```

3. **Jalankan Seeder Contoh (Opsional)**:
   ```bash
   php artisan db:seed --class=NobarAndNewsSeeder
   ```


