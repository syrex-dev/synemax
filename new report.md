# LAPORAN PERKEMBANGAN & AUDIT KESELURUHAN SISTEM SYNEMAX
**Dokumen:** Laporan Status Proyek, Pembaruan Fitur, Keamanan, dan Panduan Operasional  
**Platform:** Laravel 12 / PHP 8.4 / TailwindCSS / Alpine.js  
**URL Produksi:** `https://synemax.web.id`  
**Status Repositori:** Terdistribusi & Sinkron Penuh di Branch `main` & `dev`  
**Waktu Laporan:** 3 November 2026  

---

## 1. RINGKASAN EKSEKUTIF (EXECUTIVE SUMMARY)

Sistem bioskop online **SynemaX** telah melewati serangkaian pengujian, audit visual, pembersihan celah keamanan, dan penyempurnaan alur pengguna (UX) secara menyeluruh. Seluruh revisi terbaru yang diminta oleh pemilik situs telah diimplementasikan, diuji melalui rangkaian *automated testing* (PHPUnit), diformat dengan standar resmi *Laravel Pint*, dan telah dipublikasikan ke remote repository branch `main` (commit `3c6f840`).

### Pencapaian Utama Sesi Ini:
1. **Eliminasi Error 404 pada Menu & Tombol FAQ & Harga**: Mengatasi masalah tanda petik liar (`/kabar'`) dan menyediakan rute alias / redirect otomatis untuk semua variasi URL seperti `/faq`, `/harga`, `/harga-faq`, `/tarif`, dan `/faq&harga`.
2. **Sinkronisasi Tema Terang (Light Mode) & Bioskop Gelap (Dark Mode)**: Memperbaiki kontras halaman checkout pembayaran transfer dan QRIS sehingga nomor rekening, nama pemilik, rincian biaya, formulir upload bukti bayar, dan modal pembatalan memiliki keterbacaan tinggi (memenuhi standar WCAG AA) di kedua tema.
3. **Repeater Multi-Rekening Transfer Bank / E-Wallet**: Menyediakan antarmuka dinamis pada Panel Admin Operasional untuk menambah, menyusun urutan, dan menghapus banyak rekening bank/e-wallet secara fleksibel.
4. **Pengurutan Film Permanen (Tahun Baru ke Lama)**: Mengatur urutan katalog film di Beranda dan Halaman Film agar film tahun rilis terbaru tampil di posisi teratas, disertai opsi switch konfigurasi urutan di Panel Admin Settings.
5. **Penyempurnaan Halaman Harga & FAQ**:
   - Menghapus tombol *"Pesan Tiket Sekarang"* yang membingungkan pada kartu tarif studio per orang.
   - Menambahkan panduan visual 4 langkah cara pesan tiket online.
   - Mengatur status seluruh accordion FAQ agar **tertutup rapi secara default** saat halaman pertama kali dibuka.
6. **Panduan Standar Ukuran Aset Visual**: Dokumentasi rasio dan resolusi kanonikal untuk seluruh box/card media gambar di situs SynemaX.

---

## 2. DETAIL REVISI & PERBAIKAN FITUR TERBARU

### A. Perbaikan Error 404 pada FAQ & Harga
* **Akar Masalah:**
  Pengguna mengalami tampilan `404 Halaman tidak ditemukan` ketika mengakses menu dengan URL `https://synemax.web.id/kabar'`. Tanda kutip tunggal (`'`) di ujung URL menyebabkan router Laravel memperlakukannya sebagai pencarian artikel dengan slug `'` pada `PublicInfoController::articleShow`, yang kemudian melempar `ModelNotFoundException`.
* **Solusi yang Diterapkan:**
  1. **Sanitasi Output Link di Blade**: Pada [layouts/public.blade.php](file:///d:/Coding/SynemaX/resources/views/layouts/public.blade.php), semua atribut tautan navbar, bar bawah, dan drawer dibersihkan dari karakter petik/spasi menggunakan `rtrim(trim($url), chr(39).chr(34))`.
  2. **Sanitasi Penyimpanan di Admin**: Pada [SettingController.php](file:///d:/Coding/SynemaX/app/Http/Controllers/Admin/SettingController.php), input URL menu navigasi secara otomatis dibersihkan dari karakter petik sebelum disimpan ke database.
  3. **Penanganan Slug Dinamis di Controller**: Pada [PublicInfoController.php](file:///d:/Coding/SynemaX/app/Http/Controllers/PublicInfoController.php), method `articleShow` kini secara otomatis mendeteksi jika slug kosong, hanya berisi tanda petik, atau merupakan kata kunci seperti `faq`, `harga`, `harga-faq`, `tarif`, lalu secara anggun mengalihkan (*302 redirect*) pengunjung ke tab `route('articles.index', ['tab' => 'harga-faq'])`.
  4. **Friendly Aliases di [routes/web.php](file:///d:/Coding/SynemaX/routes/web.php)**:
     ```php
     Route::redirect('/faq', '/kabar?tab=harga-faq');
     Route::redirect('/harga', '/kabar?tab=harga-faq');
     Route::redirect('/harga-faq', '/kabar?tab=harga-faq');
     Route::redirect('/faq-harga', '/kabar?tab=harga-faq');
     Route::redirect('/faq&harga', '/kabar?tab=harga-faq');
     Route::redirect('/tarif', '/kabar?tab=harga-faq');
     Route::redirect('/kabar\'', '/kabar?tab=harga-faq');
     ```

---

### B. Sinkronisasi Tampilan Tema Terang & Gelap (Dark/Light Parity)
* **Akar Masalah:**
  Sebelumnya pada [resources/views/public/payment.blade.php](file:///d:/Coding/SynemaX/resources/views/public/payment.blade.php), beberapa elemen transfer bank menggunakan class warna keras (*hardcoded*) `text-white`, `text-slate-300`, dan `border-white/10`. Saat pengguna mengaktifkan mode terang (Light Mode), teks putih tersebut ditampilkan di atas kartu abu-abu/putih terang (`bg-surface-2`), mengakibatkan nomor rekening transfer (misal BCA `430801020996532` dan DANA `085135002229`), nama pemilik, petunjuk bayar, dan formulir bukti transfer tidak terbaca sama sekali.
* **Solusi yang Diterapkan:**
  1. **Nomor Rekening**: Diganti menjadi `text-text dark:text-white font-tabular text-xl sm:text-2xl font-black tracking-wider` (hitam pekat di mode terang, putih tajam di mode gelap).
  2. **Nama Pemilik Rekening**: Menggunakan `text-text dark:text-slate-200 font-bold`.
  3. **Header Rekening Tujuan Transfer**: Menggunakan `text-sm font-black text-text dark:text-white` dengan subteks `text-xs text-text-muted`.
  4. **Petunjuk Bayar via Transfer / QRIS**: Menggunakan kartu `border-border dark:border-white/10 bg-surface dark:bg-white/5 text-text-muted` dengan heading kontras `text-text dark:text-white`.
  5. **Formulir Unggah Bukti Pembayaran**: Label dan file picker menggunakan `text-text file:border file:border-amber-500/40 file:bg-amber-500/10 file:text-amber-700 dark:file:text-amber-400`.
  6. **Modal Pembatalan**: Dialog modal pembatalan di [payment.blade.php](file:///d:/Coding/SynemaX/resources/views/public/payment.blade.php) dan [ticket.blade.php](file:///d:/Coding/SynemaX/resources/views/public/ticket.blade.php) kini adaptif dengan latar `bg-surface dark:bg-[#0E131F]` dan teks kontras.

---

### C. Repeater Rekening Transfer Bank / E-Wallet Dinamis
* **Pembaruan:**
  Pada Panel Admin `Pengaturan -> Operasional`, pengelola bioskop kini tidak lagi dibatasi hanya pada 1 rekening bank statis.
* **Fitur:**
  * Komponen dinamis berbasis Alpine.js (`addAccount()` dan `removeAccount()`).
  * Admin dapat menambahkan BCA, BRI, Mandiri, BNI, DANA, GoPay, OVO, atau rekening lainnya secara fleksibel.
  * Setiap rekening memuat 3 kolom: Nama Bank/E-Wallet, Nomor Rekening/Nomor HP, dan Nama Pemilik Rekening.
  * Validasi non-blocking: jika metode transfer tidak diaktifkan, field rekening tidak akan menghalangi penyimpanan data lainnya.
  * Dilengkapi banner alert konfirmasi (`session('status')`, `session('warning')`, dan list error validasi).

---

### D. Pengurutan Permanen Film (Tahun Baru ke Lama)
* **Pembaruan:**
  * Daftar film di Halaman Beranda (`home.blade.php`) dan Katalog Film (`films/index.blade.php`) secara otomatis diurutkan berdasarkan `release_year DESC` (dari tahun sekarang/terbaru ke tahun terdahulu), diikuti `created_at DESC`.
  * Disediakan opsi konfigurasi di Panel Admin Pengaturan untuk memilih preferensi urutan default:
    1. Tahun rilis terbaru ke terlama (Default sistem).
    2. Tanggal tayang / jadwal terdekat.
    3. Alfabetis judul film (A - Z).

---

### E. Penyempurnaan Halaman Harga Tiket & FAQ
* **Pembaruan UI/UX:**
  1. **Pembersihan Tombol Redundan**:
     - Menghapus tombol *"Lihat Jadwal"* yang berulang pada kartu katalog film.
     - Menghapus tombol *"Pesan Tiket Sekarang"* pada kartu tarif per orang di halaman Harga & FAQ (tombol pemesanan hanya diletakkan pada kartu Sewa Studio Privat 6 Orang dan tombol utama Alur Pemesanan agar alur pengunjung terarah).
  2. **Panduan 4 Langkah Alur Pesan Tiket**:
     - **01. Pilih Film / Jadwal**: Tentukan film di katalog atau sesi tayang di jadwal.
     - **02. Tentukan Penonton**: Pilih tiket per orang atau kunci 6 tempat lewat sewa privat.
     - **03. Tambah Camilan (Opsional)**: Pilih snack hangat & minuman segar.
     - **04. Bayar & Terima Tiket**: Bayar via QRIS / Transfer, tiket QR resmi langsung terbit.
  3. **Accordion FAQ Default Tertutup**:
     - Seluruh elemen `<details>` pertanyaan umum diposisikan tertutup rapi saat pertama kali halaman dimuat, sehingga halaman terlihat ringkas, bersih, dan tidak memanjang.

---

## 3. PANDUAN STANDAR UKURAN ASET GAMBAR (MEDIA ASSET SPECS)

Untuk menjaga estetika situs tetap tajam, konsisten, dan memuat cepat tanpa distorsi/gepeng, berikut panduan rasio dan dimensi piksel kanonikal untuk pembuatan gambar:

| Jenis Komponen / Box | Rasio Aspek | Rekomendasi Resolusi (Px) | Format Berkas | Keterangan & Posisi Tampilan |
|---|---|---|---|---|
| **Poster Film (Katalog & Detail)** | `2:3` (Vertikal) | **600 × 900 px** *(Maks: 1000 × 1500 px)* | WebP / JPG | Digunakan pada kartu film katalog, hero detail film, slider jadwal, dan preview e-tiket. |
| **Hero Carousel Beranda (Desktop)** | `16:9` (Lanskap) | **1920 × 1080 px** | WebP / JPG | Banner utama bagian atas Beranda. Hindari menaruh teks penting di tepi 10% bawah (area caption). |
| **Hero Carousel Beranda (Mobile)** | `4:3` atau `16:9` | **1080 × 810 px** | WebP / JPG | Tampilan carousel saat dibuka melalui layar smartphone. |
| **Banner Nobar (Nonton Bareng)** | `16:9` (Lanskap) | **1200 × 675 px** | WebP / JPG | Poster laga/pertandingan nobar, header detail nobar, dan kartu preview nobar di beranda. |
| **Banner Promo & Penawaran** | `16:9` (Lanskap) | **1200 × 675 px** | WebP / JPG | Kartu promo katalog, banner diskon combo, dan header rincian promo. |
| **Sampul Warta / Berita Bioskop** | `16:9` (Lanskap) | **1200 × 675 px** | WebP / JPG | Thumbnail kartu warta berita dan header artikel pengumuman studio. |
| **Gambar Produk Camilan & Snack** | `1:1` (Persegi) | **500 × 500 px** | WebP / PNG | Foto menu jajan, popcorn, minuman, dan snack hangat saat booking online. |
| **Gambar Kode QRIS Pembayaran** | `1:1` (Persegi) | **800 × 800 px** | PNG / JPG | Kode QRIS resmi toko dari bank/GPN. Pastikan gambar jelas dan tidak blur untuk scanning kamera HP. |
| **Logo Bioskop (Tema Terang)** | Bebas / Horizontal | **500 × 120 px** (atau vektor) | SVG / PNG Transparan | Logo utama pada navbar terang dan kop cetak tiket. |
| **Logo Bioskop (Tema Gelap)** | Bebas / Horizontal | **500 × 120 px** (atau vektor) | SVG / PNG Transparan | Logo versi warna terang/emas saat navbar dalam mode gelap bioskop. |
| **Favicon Tab Browser** | `1:1` (Persegi) | **64 × 64 px** / **128 × 128 px** | SVG / PNG / ICO | Ikon kecil di tab browser dan bookmark ponsel. |
| **Open Graph (Social Share / WA)** | `1.91:1` (Lanskap) | **1200 × 630 px** | JPG / PNG | Gambar preview otomatis saat tautan situs dibagikan ke WhatsApp, Facebook, atau Twitter. |

---

## 4. KEAMANAN & AUDIT INTEGRITAS SISTEM

### A. Audit Celah Jalur Masuk (Backdoor / Bypass Login)
* Rute pengujian pintas tanpa sandi (`qa-admin-login` / `loginUsingId`) telah **dihapus secara permanen dari kode produksi**.
* Verifikasi dilakukan melalui pencarian seluruh kode sumber: `0 temuan`.
* URL `https://synemax.web.id/qa-admin-login` mengembalikan respon resmi **404 Not Found**.

### B. Proteksi Database Asli Pemilik
* Berkas seeder [database/seeders/DatabaseSeeder.php](file:///d:/Coding/SynemaX/database/seeders/DatabaseSeeder.php) dilindungi dengan *safety guard* yang akan melempar `RuntimeException` jika dijalankan di luar lingkungan database QA/testing.
* Data riwayat booking nyata pelanggan dan pengaturan identitas bioskop di `database/database.sqlite` terjaga aman dan tidak tersentuh oleh proses automated test.

### C. Integritas Akses E-Tiket & Privasi Pelanggan
* Setiap akses tiket dan bukti bayar dilindungi verifikasi hash token SHA-256 (`access_token_hash`) dan pelacakan perangkat aman (`DeviceBookingTracker`).
* Nomor WhatsApp yang dimasukkan dinormalisasi secara otomatis ke format standar internasional (+62) oleh [WhatsAppNumber.php](file:///d:/Coding/SynemaX/app/Support/WhatsAppNumber.php).

---

## 5. HASIL PENGUJIAN OTOMATIS (QA VERIFICATION)

Seluruh perubahan kode telah diuji secara menyeluruh melalui unit dan feature test otomatis:

| Rangkaian Pengujian (Test Suite) | Jumlah Tes | Jumlah Asersi | Status Hasil |
|---|---|---|---|
| **NewsArticleTest** (Pengujian rute FAQ, artikel, sanitasi kutip liar) | 9 tes | 29 asersi | **PASSED (100%)** ✅ |
| **PaymentThemeSyncTest** (Pengujian tema terang/gelap rekening transfer & QRIS) | 2 tes | 17 asersi | **PASSED (100%)** ✅ |
| **AdminSettingOperasionalTest** (Pengujian persistensi setting & multi-bank) | 6 tes | 35 asersi | **PASSED (100%)** ✅ |
| **SettingsManagementTest** (Pengujian panel admin, navbar, SEO, dan identitas) | 5 tes | 35 asersi | **PASSED (100%)** ✅ |
| **SnackOrderFlowTest** (Pengujian alur pemesanan camilan & transparansi tiket) | 9 tes | 78 asersi | **PASSED (100%)** ✅ |
| **Laravel Pint** (Format kode PHP standar PSR-12 / Laravel Agent) | Seluruh file diubah | - | **PASSED (0 error)** ✅ |

---

## 6. PANDUAN PENERAPAN KE SERVER WEB PRODUKSI (DEPLOYMENT GUIDE)

Seluruh perubahan telah di-push dan tersedia di remote repository GitHub pada branch **`main`** dan **`dev`**.

Untuk menerapkan versi terbaru ini ke server hosting/VPS produksi (`https://synemax.web.id`), jalankan langkah-langkah berikut melalui terminal server:

```bash
# 1. Masuk ke direktori aplikasi
cd /path/ke/synemax

# 2. Tarik pembaruan kode terbaru dari branch main
git pull origin main

# 3. Bersihkan seluruh cache tampilan dan routing
php artisan view:clear
php artisan route:clear
php artisan config:clear

# 4. Bangun ulang aset jika diperlukan (opsional jika sudah ada bundle produksi)
# npm run build

# 5. Optimalkan performa cache produksi
php artisan view:cache
php artisan route:cache
```

---
*Laporan ini dibuat secara otomatis dan komprehensif untuk mendokumentasikan seluruh riwayat revisi dan kesiapan rilis situs bioskop SynemaX.*
