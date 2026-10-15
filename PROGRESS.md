# Progress Proyek SynemaX

Dokumen ini mencatat status pengerjaan fitur dan perbaikan agar progres tetap transparan dan dapat dilanjutkan dengan aman kapan saja.

## Status Ringkas
- **Tahap Audit & Checkpoint**: SELESAI (`commit: 35fda5d` - Checkpoint: alur pembayaran dan tiket)
- **Tahap 1 (C1: Private vs Per Orang)**: SELESAI (`commit: ce83a03`)
- **Tahap 2 (C2: Halaman Pembayaran & Perbaikan QRIS)**: SELESAI (`commit: e7fa6b1`)
- **Tahap 2b (Keamanan URL Bersih & Referrer-Policy)**: SELESAI (`commit: f55cf0e`)
- **Tahap 3 (Checklist 3: Fitur Info & Nobar)**: SELESAI
- **Tahap 4 (Testing & Validasi Style Linting)**: SELESAI (125 tests lulus, Pint lulus 100%)

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

---

## Keputusan Penting
1. **Aturan Migrasi**: Migrasi `2026_10_14_000004_update_bookings_for_payment_flow` sudah tercatat `Ran` (Batch 4), sehingga tidak diubah langsung. Migrasi baru dibuat untuk tabel tambahan (`nobar_events` dan `news_articles`).
2. **Derivasi Flag `is_private`**: Flag `is_private` pada sesi disinkronkan secara konsisten setiap kali siklus hidup booking berubah (create, cancel, expire, reject, restore).
3. **Endpoint Ketersediaan**: Dibuat sebagai endpoint publik ringan khusus angka dan boolean status tanpa mengekspos identitas pelanggan, dengan proteksi cache `no-store` dan throttling.
4. **Isolasi Fitur Nobar & Berita**: Fitur nobar dan kabar dipisahkan sepenuhnya dari engine reservasi kursi, pembayaran, dan pesanan jajan untuk menjaga integritas transaksi bisnis inti.
5. **Media Serving Tanpa Storage Link**: Serving media poster & cover menggunakan controller internal dengan HTTP caching (`max-age=86400`) agar kompatibel di lingkungan deployment apa pun tanpa memerlukan `storage:link`.

---

## Perintah Yang Perlu Dijalankan Pengguna
1. **Jalankan Migrasi Database Baru (Jika belum dijalankan di environment hosting / produksi)**:
   ```bash
   php artisan migrate
   ```
   *(Di database lokal sesi ini, kedua migrasi `nobar_events` dan `news_articles` telah dijalankan dan diverifikasi).*

2. **Jalankan Seeder Contoh (Opsional)**:
   ```bash
   php artisan db:seed --class=NobarAndNewsSeeder
   ```
   *(Data contoh nobar bola dan artikel generik bertanda `[Contoh]` sudah siap diuji).*

