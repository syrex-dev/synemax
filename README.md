# Home Cinema

Aplikasi booking studio home cinema skala kecil. Pelanggan memilih film dan sesi tanpa akun; admin mengelola semuanya dari `/admin`.

## Kebutuhan

- PHP 8.3 atau lebih baru dengan ekstensi `bcmath`, `ctype`, `curl`, `fileinfo`, `gd` (dukungan WebP), `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, dan `xml`.
- Konfigurasi `php.ini` untuk unggahan media:
  - `upload_max_filesize >= 4M` (disarankan 8M).
  - `post_max_size >= 8M` (harus lebih besar dari `upload_max_filesize`).
  Nilai ini diperlukan agar unggahan poster acara nobar (maks 3 MB), gambar QRIS (maks 2 MB), dan berkas bukti pembayaran tidak ditolak oleh server PHP sebelum validasi aplikasi.
- MySQL 8+ atau MariaDB yang mendukung transaksi InnoDB dan `SELECT ... FOR UPDATE`.
- Composer 2.
- Node.js 20.19+ hanya untuk build aset di komputer pengembang. Node tidak dibutuhkan di server.

## Instalasi lokal

1. Pasang dependency PHP:

   ```sh
   composer install
   ```

2. Salin `.env.example` menjadi `.env`, lalu atur koneksi MySQL serta akun admin. `ADMIN_PASSWORD` wajib diisi dengan minimal 12 karakter sebelum seeding.

   ```dotenv
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=home_cinema
   DB_USERNAME=root
   DB_PASSWORD=
   ADMIN_EMAIL=admin@example.com
   ADMIN_PASSWORD=ganti-dengan-kata-sandi-kuat
   ADMIN_WHATSAPP=6281234567890
   ```

3. Buat database MySQL, lalu siapkan aplikasi:

   ```sh
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   php artisan sessions:generate --days=14
   ```

   Seeder membuat admin, jadwal awal, pengaturan harga, dan film fiktif untuk demo. Poster demo sengaja kosong; judul dan sinopsis demo bukan izin pemutaran film komersial. Pastikan hak pemutaran film yang dipakai sudah tersedia.
   Seeder juga menambahkan kategori dan menu jajan dengan harga contoh. Admin perlu menyesuaikan harga, modal, dan waktu persiapan sebelum menerima pesanan.

4. Build aset di komputer lokal:

   ```sh
   npm install
   npm run build
   ```

5. Jalankan server lokal:

   ```sh
   php artisan serve
   ```

   Buka `http://localhost:8000`. Masuk admin lewat `/login` memakai `ADMIN_EMAIL` dan `ADMIN_PASSWORD`.

## Jadwal dan scheduler

Jadwal awal disimpan dalam `schedule_templates`: Senin–Kamis pukul 16:00 dan 19:30; Jumat pukul 13:00, 16:00, dan 19:30; akhir pekan serta hari libur pukul 09:00, 13:00, 16:00, dan 19:30. Durasi awal setiap slot 150 menit.

Generator `sessions:generate` idempotent dan membuat sesi untuk 14 hari ke depan. Jalankan manual setelah instalasi atau dari **Admin → Jadwal → Generate 14 hari**. Scheduler menjalankannya setiap hari pukul 00:05 WIB.

Prioritas jadwal: tanggal yang ditutup tidak menghasilkan sesi; jika tidak ditutup, tanggal pada tabel `holidays` menggunakan template `libur`; selain itu template dipilih dari hari dalam minggu. Penutupan satu sesi menggunakan jam mulai pada `blocked_dates`.

## Aset dan poster

Semua aset publik dibuat dengan Vite di `public/build`. Build dapat dilakukan di komputer pengembang dan direktori `public/build` ikut diunggah ke server; Node.js tidak perlu dipasang di server.

- **Poster Film Bioskop**: Menerima JPG, PNG, atau WebP sampai 4 MB dan dimensi sumber sampai 3000 × 4500 piksel. Poster dikonversi menjadi WebP, lebar maksimal 500 piksel, lalu ditayangkan dengan lazy loading.
- **Poster Acara Nobar**: Menerima JPG, PNG, atau WebP maksimal 3 MB dengan resolusi hingga 40 megapiksel (sisi terpendek minimal 600 piksel). Format SVG ditolak. Poster diperkecil secara proporsional hanya jika sisi terpanjang > 1600 piksel tanpa pemotongan guna menjaga rasio asli dan ketajaman teks. Orientasi EXIF pada foto JPEG dinormalisasi otomatis dan metadata dibersihkan demi privasi. Jika ekstensi WebP GD tidak tersedia, sistem menggunakan fallback format asli dengan pencatatan log peringatan. Sistem juga otomatis membuat varian Open Graph 1200 × 630 piksel untuk pratinjau media sosial.
- **Pratinjau Media Sosial & WhatsApp**:
  - Tautan acara nobar menyertakan metadata Open Graph `og:image` berbasis URL absolut (`APP_URL` / `url()`) yang mengarah ke varian 1200 × 630 piksel dan dapat diakses publik tanpa login.
  - Jika acara tidak memiliki poster khusus, tautan otomatis menggunakan berkas cadangan `public/social-banner.png`.
  - **Catatan Penting Pratinjau WhatsApp**: Kartu pratinjau tautan WhatsApp hanya akan muncul jika aplikasi diakses melalui domain publik dengan sertifikat HTTPS yang valid (misalnya `https://synemax.example.com`). Bot perayap WhatsApp tidak dapat mengakses tautan lokal `localhost` atau alamat IP privat.
- **Bukti Pembayaran & QRIS**: Bukti transfer disimpan pada disk lokal privat dan hanya dapat diakses oleh admin berwenang. Berkas gambar QRIS disimpan dengan nama unik berversi untuk mencegah pembacaan cache browser lama.

## Modul jajanan

Pelanggan dapat memilih jajan saat booking tiket. Setelah booking dikirim, daftar jajan tampil read-only di **Cek booking**; pelanggan tidak bisa mengubahnya lewat aplikasi. Untuk menambah atau mengurangi pesanan, gunakan tombol WhatsApp agar admin yang mengeditnya. Jajan selalu menjadi bagian dari booking yang sama dan dibayar bersama tiket. Tidak ada checkout atau pesanan jajan terpisah. Ringkasan akhir dan tautan konfirmasi WhatsApp memuat rincian tiket, jajan, catatan, serta total gabungan.

Harga disimpan sebagai snapshot per item. Pada `bookings`, `ticket_total` dan `snack_total` memisahkan pendapatan; `total_price` adalah total keseluruhan yang tetap dipakai sebagai nilai utama di halaman booking. Migration mengisi booking lama sebagai tiket saja. Snapshot nama, harga jual, dan modal berada di `booking_items`, sehingga perubahan menu berikutnya tidak mengubah riwayat pesanan.

Batas pemesanan tiap item pada saat booking dihitung sebagai **jam mulai sesi − waktu persiapan item − buffer**. Jika ada beberapa item, menu yang melewati batasnya tidak dapat dipilih. Buffer awal 15 menit dan jumlah maksimal awal 10 buah per menu per booking; keduanya bisa diubah di **Admin → Pengaturan**. Setelah booking dibuat, perubahan jumlah atau catatan hanya dapat dilakukan admin, termasuk setelah batas pesan jajan.

Admin mengatur kategori dan menu di **Admin → Jajanan**. Foto JPG/PNG/WebP dikompres menjadi WebP dengan lebar maksimal 500 piksel. Mode stok `toggle` hanya menandai tersedia/habis; mode `angka` mengurangi stok saat booking dikonfirmasi dengan transaksi dan row lock. Jika stok tidak cukup, konfirmasi ditolak agar pesanan tidak oversell. Pembatalan mengembalikan stok yang sebelumnya dikurangi satu kali. Penjualan warung di luar aplikasi tidak dihitung otomatis, jadi gunakan koreksi stok manual dan ambang peringatan stok menipis.

**Admin → Daftar siapkan** merangkum jumlah jajan per sesi dan catatan per pemesan. Status jajan (`baru`, `disiapkan`, `siap`, `selesai`) dapat diperbarui per item atau sekaligus untuk satu booking/sesi; pelanggan hanya melihat statusnya. Perubahan menu di detail booking menyimpan riwayat admin, waktu, dan rincian sebelum/sesudah, lalu menampilkan tautan WhatsApp untuk mengirim total terbaru kepada pelanggan. Pembatalan booking ikut membatalkan seluruh item jajanan. Halaman daftar bisa dicetak dari ponsel atau browser. Laporan memisahkan pendapatan tiket/jajan, menampilkan laba jajan untuk item yang harga modalnya diketahui, menu terlaris, rata-rata nilai jajan pada booking yang memesan jajan, persentase booking dengan jajan, dan rincian item pada CSV. Paket bundling belum termasuk dalam versi ini.

## Tema dan tampilan

Halaman publik dan panel admin mendukung tema **Terang**, **Gelap**, atau **Ikuti Sistem** (pilihan awal). Pilihan disimpan di `localStorage` perangkat dan diterapkan sebelum halaman dirender agar tidak berkedip saat dimuat. Warna komponen menggunakan token desain bersama (`bg-bg`, `bg-surface`, `text-text`, `text-text-muted`, `border-border`, `bg-accent`, token status, dan token kontras tombol status seperti `text-success-contrast`/`text-danger-contrast`) supaya tampilan, fokus, status, dan kontras tetap konsisten di kedua tema. Form booking disusun bertahap dan menyimpan pilihan non-sensitif sementara di `sessionStorage`; nama dan nomor WhatsApp tidak disimpan di penyimpanan browser.

Gunakan komponen Blade bersama untuk menjaga pola UI: `<x-primary-button>` untuk aksi utama, `<x-secondary-button>` untuk aksi netral, `<x-danger-button>` untuk aksi berisiko (selalu minta konfirmasi), `<x-input-label>`/`<x-text-input>` untuk formulir, dan `<x-skeleton>` untuk placeholder gambar. Kontrol sentuh dibuat minimal 44 px, perubahan status selalu menampilkan ikon sekaligus label, dan pesan kosong/error ditulis jelas dalam Bahasa Indonesia.

Beranda menyediakan metadata Open Graph/Twitter dan banner untuk tautan WhatsApp, serta manifest dan ikon untuk ditambahkan ke layar utama. Aset ikon, manifest, banner, dan hasil build Tailwind/Vite berada di `public/`; service worker atau cache offline tidak diperlukan.

## Deploy ke shared hosting atau VPS

1. Buat database dan user MySQL terpisah. Unggah kode aplikasi dan `vendor/` (atau jalankan `composer install --no-dev --optimize-autoloader` bila Composer tersedia).
2. Arahkan document root domain ke folder `public` Laravel. Jangan jadikan root aplikasi sebagai document root dan jangan mengekspos `.env`.
3. Isi `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, detail database, `APP_KEY`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, dan `ADMIN_WHATSAPP`. Gunakan kata sandi admin yang kuat dan unik.
4. Unggah direktori `public/build` yang telah dibuat lokal. Buat symbolic link storage jika hosting mengizinkan:

   ```sh
   php artisan storage:link
   ```

5. Jalankan migrasi dan seed:

   ```sh
   php artisan migrate --force
   php artisan db:seed --force
   php artisan sessions:generate --days=14
   php artisan config:cache
   php artisan view:cache
   ```

   Jangan menjalankan `migrate:fresh` pada server yang menyimpan data. Seeder dapat dijalankan ulang; ia memperbarui akun admin sesuai nilai `ADMIN_PASSWORD` dan `ADMIN_EMAIL` di environment saat itu.

6. Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh PHP. Setelah memperbarui `.env`, jalankan `php artisan config:clear` sebelum membuat ulang cache konfigurasi.

7. Tambahkan cron sistem untuk scheduler Laravel (gunakan path PHP dan aplikasi yang benar pada hosting):

   ```cron
   * * * * * cd /path/ke/home-cinema && php artisan schedule:run >> /dev/null 2>&1
   ```

   Tidak dibutuhkan Redis, worker antrean, atau WhatsApp API. Aplikasi memakai session/cache file dan antrean sinkron secara default.

8. Atur **Admin → Pengaturan** untuk nomor WhatsApp, instruksi QRIS/transfer, metode bayar, harga, batas waktu, minimal penonton, dan biaya listrik. Bayar di tempat aktif sebagai metode awal; QRIS dan transfer baru ditawarkan jika diaktifkan admin.

## Pengujian

```sh
php artisan test
npm run build
npm audit --omit=optional
```

Suite SQLite menguji batas kapasitas, percobaan booking yang bersaing untuk kursi terakhir, pemilihan template hari libur/Jumat/akhir pekan, pembatalan, paket private, dan modul jajan (total, batas waktu saat booking awal, snapshot harga, endpoint pelanggan read-only, perubahan admin beserta riwayatnya, stok dan laporan). Test MySQL khusus memulai dua proses PHP untuk memperebutkan kursi terakhir dan satu stok angka terakhir saat konfirmasi. Jalankan test tersebut hanya terhadap database uji yang boleh di-reset, bukan database produksi:

```sh
DB_CONNECTION=mysql DB_DATABASE=home_cinema_test DB_USERNAME=root DB_PASSWORD=... php artisan test --filter=MySqlBookingRaceTest
DB_CONNECTION=mysql DB_DATABASE=home_cinema_test DB_USERNAME=root DB_PASSWORD=... php artisan test --filter=MySqlSnackStockRaceTest
```

Driver SQLite tidak menyediakan row lock MySQL; karena itu test proses paralel dilewati pada SQLite. Jangan arahkan test MySQL tersebut ke database berisi data penting.
