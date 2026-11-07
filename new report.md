# LAPORAN PERKEMBANGAN & AUDIT KESELURUHAN SISTEM SYNEMAX
**Dokumen:** Laporan Komprehensif Status Proyek, Pembaruan Fitur, Keamanan, Integrasi Operasional, dan Panduan Penerapan  
**Platform:** Laravel 12 / PHP 8.4 / TailwindCSS / Alpine.js / MariaDB 11.8 (STB) & SQLite (Pengujian)  
**URL Produksi:** `https://synemax.web.id`  
**Status Repositori:** Bersih (*Clean Working Tree*), Terdistribusi & Sinkron Penuh di Branch `main` (Head Commit: `c5c92c0`)  
**Waktu Laporan:** 7 November 2026  

---

## 1. RINGKASAN EKSEKUTIF (EXECUTIVE SUMMARY)

Sistem bioskop mini privat **SynemaX** telah menyelesaikan seluruh rangkaian pengembangan fungsional, penguatan infrastruktur keamanan, pembersihan celah akses, penyelarasan tampilan antarmuka (UI/UX) lintas tema, perbaikan alur pesan kilat (*flash alert*), peluncuran fitur baru **Pemindai Tiket (QR Scanner) Terpadu untuk Admin** (`/admin/scan`), optimasi kinerja perangkat keras STB hemat daya (2GB RAM / 8GB Storage), eliminasi ledakan query N+1 pada jadwal, perbaikan reaktivitas antrean poster massal, pembersih media penyimpanan, serta implementasi fitur **Pin / Badge Kustom Poster Film** (`custom_badge`).

Seluruh kode produksi berada dalam kondisi stabil, telah diuji secara komprehensif melalui *automated backend test* (PHPUnit) dan *headless browser end-to-end audit* (Playwright), diformat secara ketat mengikuti standar *Laravel Pint*, dan telah digabungkan (*merge*) secara resmi ke branch utama **`main`**.

### Pencapaian & Pembaruan Utama:
1. **Eliminasi Alert Info & Status Ganda pada Tampilan Publik (Commit `2d06c96`)**:
   - Menghapus rendering flash alert global yang redundan dari [layouts/public.blade.php](file:///d:/Coding/SynemaX/resources/views/layouts/public.blade.php).
   - Menata alert konteks halaman langsung pada tampilan aksi pemesan: [booking-form.blade.php](file:///d:/Coding/SynemaX/resources/views/public/booking-form.blade.php), [booking-status.blade.php](file:///d:/Coding/SynemaX/resources/views/public/booking-status.blade.php), [payment.blade.php](file:///d:/Coding/SynemaX/resources/views/public/payment.blade.php), [ticket.blade.php](file:///d:/Coding/SynemaX/resources/views/public/ticket.blade.php), dan [tickets-index.blade.php](file:///d:/Coding/SynemaX/resources/views/public/tickets-index.blade.php).
   - Melengkapi komponen dialog in-app [in-app-dialogs.blade.php](file:///d:/Coding/SynemaX/resources/views/components/in-app-dialogs.blade.php) dengan deteksi cerdas `hasInlineAlert` untuk mencegah munculnya toast ganda jika halaman sudah merender banner status inline.
2. **Implementasi Resmi Fitur Pemindai Tiket (QR) Admin (`/admin/scan`) (Commit `ea35f3b` & `8cfcfb2`)**:
   - Konsol pemindai mobile-first (ponsel/tablet kru) dan konsol desktop berbingkai rounded-3xl berstandar industri.
   - Pustaka pemindai lokal [public/vendor/jsqr/jsQR.js](file:///d:/Coding/SynemaX/public/vendor/jsqr/jsQR.js) mandiri (256 KB) dengan akselerasi perangkat keras native `BarcodeDetector` dan fallback lokal (tanpa CDN eksternal).
   - 3 Tab Operasional: **Kamera** (viewfinder dengan animasi laser beam CSS), **Kode** (input 56px, normalisasi URL e-tiket, pencarian 4 karakter akhir, tombol tempel clipboard), dan **Daftar** (tamu sesi realtime dengan polling otomatis 20 detik, filter status, dan pencarian instan).
   - Mesin aturan check-in atomik idempoten [CheckInService.php](file:///d:/Coding/SynemaX/app/Services/CheckInService.php) dengan 11 status verdict, penanganan override, serta mekanisme pembatalan tanda hadir (*Undo Check-In*).
   - Jejak audit transparan pada tabel database baru `booking_checkins`.
3. **Sticky Alert Pembayaran & Floating Action**:
   - Banner melayang (*sticky banner*) pada halaman publik saat pengguna memiliki pesanan aktif yang belum dibayar, mengarahkan langsung ke halaman pembayaran sebelum batas waktu kedaluwarsa.
4. **Penyempurnaan Alur Wizard Booking & Kurasi Film**:
   - Stepper navigasi formulir booking yang teratur dan intuitif.
   - Menghapus tombol redundan *"Pesan Tiket Sekarang"* pada kartu tarif per orang dan *"Lihat Jadwal"* di katalog film.
   - Panduan visual 4 langkah alur pesan tiket online di Halaman Harga & FAQ.
   - Pengurutan default katalog film: tahun rilis terbaru ke terlama (`release_year DESC`).
5. **Sinkronisasi Sempurna Tema Terang (Light Mode) & Gelap (Dark Mode)**:
   - Paritas kontras rasio WCAG AA pada halaman checkout transfer bank & QRIS (nomor rekening, nama pemilik rekening, rincian biaya, upload file bukti transfer, dan modal konfirmasi).
6. **Repeater Rekening Transfer Multi-Bank & E-Wallet**:
   - Antarmuka dinamis Alpine.js di Panel Admin Pengaturan Operasional untuk mengelola daftar nomor rekening bank (BCA, Mandiri, BRI, BNI) maupun e-wallet (DANA, GoPay, OVO).
7. **Pemberantasan Istilah "Kursi"**:
   - Seluruh teks publik dan antarmuka operasional disesuaikan dengan konsep bioskop privat: menggunakan istilah *"orang"*, *"penonton"*, atau *"kapasitas"* (misal: *"Hadir X dari Y orang"*, *"Reguler · 2 orang"*).
8. **Integritas Keamanan & Audit Kode**:
   - Verifikasi penghapusan permanen rute bypass testing `/qa-admin-login` (menghasilkan status resmi 404).
   - Proteksi seeder dan database asli, pelacakan sesi via hash SHA-256 (`access_token_hash`), dan normalisasi WhatsApp internasional (+62).
9. **Optimalisasi Infrastruktur Hemat Daya STB 2GB RAM & 8GB Storage (Commit `01b4a14`)**:
   - Penyetelan profil MariaDB 11.8 (`99-stb-lowram.cnf`) yang menghemat memori hingga 123.5 MB RAM, mengaktifkan 1 GB swap, dan menyisakan 1.4 GB RAM bebas, melenyapkan kendala host error HTTP 520 Cloudflare.
10. **Eliminasi Ledakan Query N+1 pada Halaman Kelola Jadwal (Commit `1f42bc6`)**:
    - Memangkas lebih dari 10.000 query database berulang menjadi hanya 4–6 query instan melalui eager loading minimal dan memoized cache kalkulasi kuorum.
11. **Pembersih Penyimpanan Berkas & Pengelolaan Kotak Sampah Permanen (Commit `1f42bc6`)**:
    - Tombol pembersih berkas orphan dan tmp poster di halaman poster massal (`cleanStorage`), tab "Sampah" permanen di katalog admin, serta aksi hapus permanen (*force delete*) record film beserta berkas poster fisiknya untuk menjaga kebersihan storage 8GB STB.
12. **Reaktivitas Antrean Poster Massal & Fitur Pasang Massal (Commit `59a31a5`, `24f1f58`, `01b4a14`)**:
    - Kartu poster langsung menghilang seketika (*instant vanishing*) pasca dipasangkan tanpa refresh manual, perbaikan modal pasang massal (*batch assign*), serta kelonggaran format nama berkas screenshot acak.
13. **Perbaikan Checkbox Master "Pilih Semua" & Rekomendasi Film Sejalan Genre (Commit `8d349f9`)**:
    - Perbaikan interaktivitas master checkbox katalog admin dan penyelarasan algoritma rekomendasi film serupa di halaman detail film publik berbasis kecocokan genre.
14. **Fitur Pin / Badge Kustom Poster Film untuk Admin & Publik (Commit `c5c92c0`)**:
    - Penambahan kolom `custom_badge` pada tabel `films`, pilihan cepat tombol preset (🔥 Baru Ditambahkan, ⚡ Trending, ⏳ Segera Hadir, ⭐ Eksklusif, ❤️ Favorit), live preview kartu poster di form admin, aksi massal pin, serta komponen `<x-cinema.poster-badge>` bergaya OTT/Netflix.

---

## 2. DETAIL REVISI & PENYEMPURNAAN SISTEM TERBARU

### A. Eliminasi Alert Info Ganda (Single Flash Alert Parity)
* **Akar Masalah:**
  Sebelumnya, saat terjadi aksi pengguna (misalnya setelah membatalkan pemesanan, mengunggah bukti pembayaran, atau terjadi kesalahan input), notifikasi flash session (`status`, `error`, `warning`, `info`) dirender di dua tempat sekaligus:
  1. Kontainer peringatan global di bagian atas [layouts/public.blade.php](file:///d:/Coding/SynemaX/resources/views/layouts/public.blade.php).
  2. Kartu alert spesifik di dalam file blade halaman tindakan terkait (misalnya di [ticket.blade.php](file:///d:/Coding/SynemaX/resources/views/public/ticket.blade.php) atau [payment.blade.php](file:///d:/Coding/SynemaX/resources/views/public/payment.blade.php)).
  Hal ini menimbulkan kesan visual berantakan dengan dua kotak pesan berdampingan yang memuat teks identik.
* **Solusi yang Diterapkan:**
  1. **Pembersihan Kontainer Global:** Menghapus blok `@if (session('status') || session('error'))` dari layout publik utama.
  2. **Banner Semantik Lokal:** Setiap halaman alur pemesanan publik kini mengelola pesan status dan peringatannya sendiri secara rapi dengan styling ramah tema:
     - Hijau sukses (`border-emerald-500/40 bg-emerald-500/10 text-emerald-400 font-bold`).
     - Merah error (`border-danger/40 bg-danger-surface text-danger-text font-bold`).
     - Kuning peringatan (`border-warning/30 bg-warning-surface text-warning font-medium`).
  3. **Penyelarasan Komponen Dialog In-App ([in-app-dialogs.blade.php](file:///d:/Coding/SynemaX/resources/views/components/in-app-dialogs.blade.php)):**
     Pada area admin, script dialog memeriksa keberadaan elemen status inline melalui `document.querySelector('[role="status"], [role="alert"]')`. Jika banner inline telah hadir di halaman, toast mengambang tidak akan dimunculkan ganda.
  4. **Pengujian Khusus:** Ditambahkan suite uji baru [SingleFlashAlertTest.php](file:///d:/Coding/SynemaX/tests/Feature/SingleFlashAlertTest.php) dengan 4 metode uji yang memastikan pesan status hanya muncul tepat satu kali di setiap halaman publik (100% Lulus).

---

### B. Fitur Pemindai Tiket (QR Scanner) Kru & Admin (`/admin/scan`)
Fitur Pemindai Tiket dirancang sebagai solusi pintu masuk terpadu bagi staf dan admin bioskop SynemaX saat menyambut kedatangan penonton di studio.

```
+-----------------------------------------------------------------------------------+
|                           SYNEMAX TICKET SCANNER CONSOLE                          |
|                                                                                   |
|  [ < Hari Ini ]               Scan tiket               [ Sound: ON ] [ Theme ]    |
|  Sesi Aktif: [ 17:00 - 18:30 · Film Avatar · Hadir 4 dari 6 orang           v ]    |
|                                                                                   |
|        +-------------------+-------------------+--------------------+             |
|        |    [o] Kamera     |    [#] Kode       |    [=] Daftar (2)  |             |
|        +-------------------+-------------------+--------------------+             |
|                                                                                   |
|        +------------------------------------------------------------+             |
|        |                   VIEWFINDER PEMINDAI                      |             |
|        |                                                            |             |
|        |                +--------------------------+                |             |
|        |                |  [|] Scan Beam Laser     |                |             |
|        |                |                          |                |             |
|        |                |   Arahkan QR Tiket ke    |                |             |
|        |                |       dalam kotak        |                |             |
|        |                +--------------------------+                |             |
|        |                                                            |             |
|        |     [ Ganti Kamera ]   [ Lampu Senter ]   [ Jeda / Resume ]|             |
|        +------------------------------------------------------------+             |
|                                                                                   |
|  +-----------------------------------------------------------------------------+  |
|  | BOTTOM SHEET: VERDICT TIKET                                                 |  |
|  | Status: [ SIAP MASUK ] (Hijau)                Kode: SNX-20261105-ABCD       |  |
|  | Nama: Budi Santoso (+62 812-****-1234)        Paket: Reguler · 2 orang      |  |
|  | Sesi: Kamis, 5 Nov 2026 · 17:00 WIB          Jajan: 1x Popcorn, 2x Cola    |  |
|  |                                                                             |  |
|  | [   TANDAI HADIR SEKARANG (56px)   ]  [ Batalkan ]                          |  |
|  +-----------------------------------------------------------------------------+  |
+-----------------------------------------------------------------------------------+
```

#### 1. Arsitektur Antarmuka & Responsivitas
- **Tampilan Konsol Desktop & Terminal Front-Desk:**
  - Viewfinder dibungkus dalam konsol terminal terpusat (`max-w-xl mx-auto md:aspect-video md:max-h-[500px] border border-border/80 rounded-3xl shadow-2xl`).
  - Menghindari bentangan elemen 100vw yang melebar kosong pada monitor kasir 1080p.
- **Tampilan Mobile Layar Penuh (Kru di Pintu Masuk):**
  - Menggunakan layout *edge-to-edge* fleksibel (`h-dvh flex flex-col`) dengan header ramping 1 baris, bebas dari sidebar maupun bottom navigation publik.
- **Kontrol Tab Kapsul Bersegmen (Segmented Bar):**
  - Mengganti tab bergaris bawah (*underline*) biasa dengan kontrol bersegmen modern berbentuk kapsul berlatar `bg-surface-2 border border-border/80`.
  - Dilengkapi ikon SVG khusus untuk tiap mode: Kamera, Kode Manual, dan Daftar Sesi (dengan lencana indikator jumlah tamu yang belum check-in).

#### 2. Ketergantungan Lokal & Akselerasi Pemindaian
- Disematkan pustaka lokal [public/vendor/jsqr/jsQR.js](file:///d:/Coding/SynemaX/public/vendor/jsqr/jsQR.js) (256 KB) dengan checksum valid dari paket npm resmi.
- Sistem secara otomatis memprioritaskan akselerasi perangkat keras native peramban `BarcodeDetector` (jika didukung perangkat), dan melakukan *fallback* halus ke `jsQR.js` lokal tanpa ketergantungan CDN eksternal.
- Dilengkapi kunci pencegah pemindaian ganda (*deduplication lock* 3 detik) serta nada konfirmasi audio (*Web Audio API beep status*).

#### 3. Tiga Mode Input Operasional
1. **Mode Tab Kamera:**
   - Animasi laser pemindai visual (*scan beam animation*) berbasis CSS murni `@keyframes scanBeam` tanpa efek blur terlarang.
   - Pilihan toggle multi-kamera (kamera depan / belakang), saklar lampu senter (*torch*), tombol jeda (*pause/resume*).
   - Penanganan ramah in-app jika izin kamera ditolak atau jika dibuka melalui peramban yang memerlukan protokol HTTPS.
2. **Mode Tab Kode Manual:**
   - Input tinggi 56px (`h-14 font-mono font-bold tracking-wider`) terpusat pada kontainer `max-w-xl mx-auto`.
   - Dilengkapi tombol pembersih teks instan (X) dan tombol tempel (*paste from clipboard* via Clipboard API).
   - Normalisasi cerdas: menerima tautan e-tiket lengkap (`https://synemax.web.id/tiket/SNX-...`), kode booking penuh (`SNX-20261105-ABCD`), atau cukup 4 karakter akhir (`ABCD`). Menerima pula pencarian instan nama pemesan atau nomor WhatsApp.
3. **Mode Tab Daftar Penonton Sesi:**
   - Daftar tamu tersortir otomatis: pemesan yang siap masuk di posisi teratas, diikuti yang belum bayar, lalu yang sudah hadir.
   - Polling latar belakang otomatis setiap 20 detik saat tab aktif.
   - Filter cepat tombol kapsul: **Semua**, **Belum Hadir**, dan **Hadir**.
   - Kolom pencarian instan sisi klien (*client-side live search*) tanpa perlu me-reload halaman.

#### 4. Mesin Aturan Check-In ([App\Services\CheckInService.php](file:///d:/Coding/SynemaX/app/Services/CheckInService.php))
- **11 Status Verdict Lengkap:**
  1. `siap_hadir`: Tiket aktif, lunas, kuorum tercapai, dalam rentang waktu check-in (Tombol hijau aktif).
  2. `sudah_hadir`: Tiket telah divalidasi sebelumnya (Disertai info nama staf dan jam validasi).
  3. `belum_bayar`: Pemesanan belum lunas.
  4. `diproses`: Bukti bayar terunggah dan menunggu konfirmasi admin.
  5. `menunggu_penonton`: Sesi belum memenuhi kuorum minimal penonton.
  6. `dibatalkan`: Pemesanan telah dibatalkan.
  7. `kedaluwarsa`: Batas waktu pembayaran telah lewat.
  8. `tidak_datang`: Pemesanan ditandai no-show.
  9. `sesi_lain`: Tiket sah namun terjadwal untuk sesi atau hari yang berbeda (Memerlukan konfirmasi override admin).
  10. `di_luar_jendela`: Tiket dipindai di luar batas waktu buka/tutup check-in (Memerlukan konfirmasi override admin).
  11. `tidak_ditemukan`: Kode booking tidak terdaftar pada sistem.
- **Konkurensi & Idempotensi Atomik:**
  Menggunakan query conditional update atomik `UPDATE bookings SET status = 'hadir', checked_in_at = ? WHERE id = ? AND checked_in_at IS NULL`. Jika dua staf memindai tiket yang sama dalam detik yang sama, tepat 1 transaksi yang menang, sedangkan pemindaian kedua otomatis menghasilkan verdict `sudah_hadir` tanpa menimbulkan bentrok data ganda.
- **Pembatalan Kehadiran (Undo Check-In):**
  Jika staf salah memvalidasi tiket, tersedia tombol *Batalkan hadir (Undo)* di antarmuka scanner maupun detail booking admin dengan kewajiban mengisi alasan pembatalan. Status dipulihkan ke status sebelumnya dan dicatat pada audit log sebagai `undo`.

#### 5. Skema Migrasi Database Audit (`booking_checkins`)
- File migrasi aditif [2026_11_03_180000_add_checkin_columns_and_booking_checkins_table.php](file:///d:/Coding/SynemaX/database/migrations/2026_11_03_180000_add_checkin_columns_and_booking_checkins_table.php).
- Kolom baru pada tabel `bookings`: `checked_in_at`, `checked_in_by`, `check_in_method`.
- Tabel baru `booking_checkins`: mencatat `booking_id`, `user_id`, `method` (`qr`, `kode`, `daftar`, `admin_manual`, `legacy`), `result` (`ok`, `override`, `duplicate`, `rejected`, `undo`), `reason`, `ip`, `user_agent`, dan `created_at`.
- Booking historis berstatus hadir otomatis dibackfill dengan metode `legacy`.

#### 6. Integrasi Ekosistem Admin SynemaX
- **Navigasi Staf ([admin-nav.blade.php](file:///d:/Coding/SynemaX/resources/views/layouts/partials/admin-nav.blade.php)):** Tautan *"Scan tiket"* pada grup menu OPERASIONAL dan rute pintas `/scan`.
- **Dashboard Hari Ini ([admin/dashboard.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/dashboard.blade.php)):** Tombol cepat di header, kartu sesi dengan bar progres kehadiran `Hadir X/Y orang`, dan tautan langsung pemindai per sesi.
- **Dapur & Camilan ([admin/snacks/preparation.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/snacks/preparation.blade.php)):** Lencana hijau *"Hadir"* pada tiket pemesan yang telah berada di dalam studio bioskop untuk memprioritaskan penyajian popcorn & minuman hangat.
- **Laporan Operasional ([admin/reports/index.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/reports/index.blade.php)):** Statistik tingkat kehadiran penonton serta sebaran metode validasi tiket (QR, Kode Manual, Tab Daftar, Staf Manual).
- **Pengaturan Operasional ([admin/settings/edit.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/settings/edit.blade.php)):** Pengaturan menit buka check-in sebelum film (15, 30, 60 bawaan, 90, 120 menit), menit batas tutup check-in, dan toggle audio beep.
- **Notifikasi Telegram Outbox:** Penyiapan event `customer_checked_in` pada [TelegramNotificationService.php](file:///d:/Coding/SynemaX/app/Services/TelegramNotificationService.php) (bawaan non-aktif).

---

### C. Sinkronisasi Paritas Tema Terang & Bioskop Gelap (Dark/Light Parity)
* **Permasalahan Terdahulu:**
  Pada halaman pembayaran [payment.blade.php](file:///d:/Coding/SynemaX/resources/views/public/payment.blade.php), beberapa class CSS warna teks menggunakan gaya gelap statis (`text-white`, `text-slate-300`, `border-white/10`). Ketika pengunjung membuka situs dalam mode terang (*Light Mode*), teks putih berada di atas latar kartu terang, sehingga nomor rekening transfer (misal BCA & DANA), nama pemilik, petunjuk pembayaran, dan form bukti bayar menjadi samar atau tidak terbaca.
* **Penyempurnaan Token Warna Bersama:**
  - Nomor Rekening Bank: `text-text dark:text-white font-tabular text-xl sm:text-2xl font-black tracking-wider` (hitam pekat di mode terang, putih tajam di mode gelap).
  - Nama Pemilik Rekening: `text-text dark:text-slate-200 font-bold`.
  - Header Rekening Tujuan: `text-sm font-black text-text dark:text-white`.
  - Kartu Petunjuk Bayar: `border-border dark:border-white/10 bg-surface dark:bg-white/5 text-text-muted`.
  - File Picker Bukti Bayar: `text-text file:border file:border-amber-500/40 file:bg-amber-500/10 file:text-amber-700 dark:file:text-amber-400`.
  - Modal Konfirmasi Pembatalan: Adaptif dengan latar `bg-surface dark:bg-[#0E131F]` dan batas batas `border-border dark:border-white/10`.

---

### D. Repeater Rekening Transfer Multi-Bank & E-Wallet Dinamis
* **Pembaruan Panel Admin Pengaturan Operasional:**
  - Sebelumnya sistem hanya mendukung satu nomor rekening statis.
  - Kini pengelola bioskop dapat menambahkan banyak opsi transfer bank (BCA, BRI, Mandiri, BNI) maupun dompet digital (DANA, GoPay, OVO) secara dinamis menggunakan antarmuka berbasis Alpine.js.
  - Setiap rekening memuat 3 atribut lengkap: Nama Bank/E-Wallet, Nomor Rekening/No HP, dan Nama Pemilik Rekening.
  - Validasi non-blocking ramah pengguna: jika metode transfer dicentang namun admin belum mengisi nomor rekening, sistem memberikan banner peringatan (*warning flash*) tanpa membatalkan penyimpanan konfigurasi lainnya.
  - Data rekening disimpan dalam kolom JSON terstruktur pada tabel `settings` dan dibersihkan dari baris kosong secara otomatis.

---

### E. Penataan Urutan Katalog Film & Urutan Alur Booking
* **Pengurutan Film Default (Tahun Sekarang ke Lama):**
  - Katalog film pada Beranda dan Halaman Film diurutkan secara konsisten berdasarkan tahun rilis terbaru: `orderByDesc('release_year')->orderByDesc('created_at')`.
  - Tersedia opsi switch konfigurasi pada Panel Admin Pengaturan bagi pengelola untuk memilih preferensi urutan katalog (Tahun Rilis Terbaru, Tanggal Tayang Terdekat, atau Judul A-Z).
* **Penyempurnaan Halaman Harga & FAQ:**
  - Menghapus tombol redundan *"Pesan Tiket Sekarang"* pada kartu tarif per orang (tombol pemesanan difokuskan pada kartu Sewa Studio Privat dan tombol utama Alur Pemesanan).
  - Menambahkan panduan 4 langkah cara pesan tiket online.
  - Seluruh accordion `<details>` pertanyaan umum ditutup rapi secara default saat halaman dimuat pertama kali.

---

### F. Standarisasi Slot Waktu Sesi Tayang Bioskop (Senin–Kamis 3 Sesi & Jumat–Minggu 4 Sesi)
* **Kebutuhan Bisnis:**
  Pengelola studio menetapkan standarisasi jam tayang harian secara baku:
  1. **Senin hingga Kamis (Weekday):** Dibuka sebanyak **3 sesi** per hari, yaitu pukul **13:00**, **16:00**, dan **19:00**.
  2. **Jumat hingga Minggu (Weekend/Jumat) serta Hari Libur:** Dibuka sebanyak **4 sesi** per hari, yaitu pukul **09:00**, **13:00**, **16:00**, dan **19:00**.
  3. **Penyesuaian Sesi Malam (19:30 -> 19:00):** Di seluruh sistem, jam tayang malam yang sebelumnya pukul 19:30 telah diganti secara permanen menjadi pukul **19:00** (durasi 150 menit selesai pukul 21:30 WIB), memberikan jeda istirahat dan pembersihan studio selama 30 menit yang konsisten di setiap jeda sesi siang-malam (13:00–15:30 -> jeda -> 16:00–18:30 -> jeda -> 19:00–21:30).
* **Tindakan Teknis yang Diterapkan:**
  - **Migrasi Aditif [2026_11_06_000001_standardize_screening_session_times.php](file:///d:/Coding/SynemaX/database/migrations/2026_11_06_000001_standardize_screening_session_times.php):**
    - Mengonversi seluruh rekaman sesi tayang historis dan aktif pada tabel `screening_sessions` dari `19:30` ke `19:00`.
    - Memperbarui template jadwal pada tabel `schedule_templates` agar `weekday` memiliki 3 template (13:00, 16:00, 19:00), serta `jumat`, `weekend`, dan `libur` memiliki 4 template (09:00, 13:00, 16:00, 19:00).
    - Memanggil [SessionGenerationService.php](file:///d:/Coding/SynemaX/app/Services/SessionGenerationService.php) untuk meng-generate slot baru 14 hari ke depan secara otomatis di lingkungan live.
  - **Sinkronisasi Seeder & Dokumentasi:** Memutakhirkan [DatabaseSeeder.php](file:///d:/Coding/SynemaX/database/seeders/DatabaseSeeder.php) dan panduan [README.md](file:///d:/Coding/SynemaX/README.md).
  - **Pengujian Otomatis:** Menambahkan suite pengujian [ScheduleStandardSessionTimesTest.php](file:///d:/Coding/SynemaX/tests/Feature/ScheduleStandardSessionTimesTest.php) (3 tes, 14 asersi, 100% Lulus).

---

### G. Penghapusan Total Modul OCR AI Poster Massal & Pemulihan Antrean Poster Manual
* **Akar Masalah & Permintaan Pengguna:**
  1. Fitur OCR AI (Tesseract.js) yang mencoba mengenali teks judul film langsung dari foto poster terbukti tidak akurat saat memproses gambar tangkapan layar (*screenshot*), di mana teks acak pada screenshot dipaksakan cocok ke film katalog yang salah (misal tangkapan layar dipasangkan keliru ke film *Badoet*, *Kuntilanak 2*, atau *Badarawuhi*).
  2. Beban komputasi unduhan CDN model Tesseract dan analisis canvas pixel membuat browser menjadi berat dan lambat.
  3. Status hasil unggahan menjadi "Cocok Otomatis" padahal filmnya salah, sehingga antrean manual "Poster Belum Terpasang" di bawah tombol perbarui halaman menjadi kosong (0 poster).
* **Solusi & Rekayasa yang Diterapkan (Commit `31fb972`):**
  1. **Penghapusan Total Mesin OCR:**
     - Menghapus pemuatan CDN script `tesseract.min.js`, inisialisasi worker OCR, dan fungsi pra-pemrosesan canvas OCR dari [posters.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/films/posters.blade.php).
     - Menghapus toggle OCR AI dari formulir unggah massal.
     - Menghapus parameter `ocr_text` dari endpoint `process-single` dan `process-batch-item`.
     - Menghapus rute `/films/posters/match-ocr` dari [routes/web.php](file:///d:/Coding/SynemaX/routes/web.php).
     - Menghapus lebih dari 500 baris algoritma fuzzy matching OCR (`matchOcr`, `matchFilmFromOcr`, normalisasi leetspeak, roman numeral, dll.) dari [FilmPosterController.php](file:///d:/Coding/SynemaX/app/Http/Controllers/Admin/FilmPosterController.php).
  2. **Pemulihan Antrean Poster Belum Terpasang di Bawah Tombol Perbarui:**
     - Gambar yang nama berkasnya tidak persis sama dengan judul/slug katalog otomatis masuk ke direktori `posters/unmatched` dan tampil di bagian **Poster Belum Terpasang** di bawah tombol `Perbarui Halaman & Lihat Antrean Manual`.
     - Tombol `Perbarui Halaman & Lihat Antrean Manual` kini secara presisi menyematkan anchor hash `#antrean-manual` agar layar langsung mengarah ke kartu poster manual pasca peramban memuat ulang.
     - Setiap kartu poster menampilkan pratinjau gambar, nama berkas, ukuran, resolusi, dan dropdown pencarian film lengkap (`<x-ui.select>`).
     - Dropdown film memuat seluruh film katalog dengan tanda `★ [Belum Ada Poster]` bagi film tanpa poster, serta opsi ganti poster bagi film yang sudah memiliki poster.
     - Admin dapat memasangkan satu per satu poster menggunakan tombol **"Pasangkan"** atau memasangkan secara massal via modal konfirmasi **"Pasangkan Semua yang Dipilih"**.
  3. **Penyempurnaan Suite Pengujian:**
     - Seluruh 25 pengujian pada [FilmBulkImportTest.php](file:///d:/Coding/SynemaX/tests/Feature/FilmBulkImportTest.php) berhasil 100% lulus (223 asersi).
     - Menghapus berkas pengujian usang `OcrPosterMatchingTest.php`.

---

### H. Pratinjau Modal Spectate & Reaktivitas Instan Antrean Poster Massal (Commit `db59b26`, `59a31a5`, `24f1f58`)
* **Kebutuhan Pengguna & Kendala Awal:**
  1. Admin kesulitan memastikan kejelasan poster berukuran thumbnail sebelum menentukan film penerima.
  2. Dropdown film pada kartu grid poster antrean manual sebelumnya sering kali terpotong (*overflow clipping*) oleh kontainer kartu.
  3. Setelah poster berhasil dipasangkan ke film, kartu poster masih tetap tampil di halaman dan admin harus me-refresh halaman secara manual agar kartu hilang dari antrean.
  4. Gambar poster tangkapan layar ponsel berseri acak (misal: `screenshot-2026-11-06-100228_...webp`) sering kali ditolak karena validasi nama berkas yang terlalu ketat.
* **Solusi & Rekayasa yang Diterapkan:**
  1. **Modal Popup "Lihat Poster" (Spectate / Zoom):**
     - Menambahkan fungsi Alpine.js `openPosterModal(url, title, filename)` di antarmuka [posters.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/films/posters.blade.php).
     - Pratinjau poster ukuran penuh dengan resolusi tajam, rasio 2:3 presisi, serta informasi dimensi dan ukuran berkas.
  2. **Perbaikan Overflow Dropdown & Penataan Grid:**
     - Menyesuaikan `overflow-visible` dan z-index bertingkat pada dropdown pilihan film agar tidak pernah terpotong kartu lain.
  3. **Reaktivitas Instan Tanpa Muat Ulang Halaman:**
     - Mengimplementasikan Custom Event bus `@poster-card-deleted` pada jendela peramban.
     - Saat poster berhasil dipasangkan ke film atau tombol "Hapus Poster" ditekan, kartu yang bersangkutan langsung melakukan animasi transisi halus (*scale down & fade out 280ms*) dan dihapus seketika dari DOM (`el.remove()`).
     - State Alpine `unmatchedCount` dan daftar antrean diperbarui otomatis tanpa perlu me-refresh halaman.
  4. **Kelonggaran Pola Nama Berkas Tangkapan Layar:**
     - Menghapus batasan regex yang kaku di [FilmPosterController.php](file:///d:/Coding/SynemaX/app/Http/Controllers/Admin/FilmPosterController.php), sehingga segala variasi nama berkas screenshot didukung secara aman.

---

### I. Perbaikan Fitur Pasang Poster Massal (Batch Assignment) (Commit `01b4a14`)
* **Kebutuhan Pengguna:**
  Admin dapat memilih pasangan film untuk beberapa poster sekaligus di antrean, lalu memasangkan semuanya secara serentak hanya dengan satu kali klik.
* **Solusi & Rekayasa:**
  - Memperbaiki modal pratinjau pemasangan poster massal di [posters.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/films/posters.blade.php):
    - Modal merangkum seluruh berkas poster yang telah dipilihkan target film penerima.
    - Tombol *"Pasangkan Sekarang"* mengeksekusi endpoint `batch-assign` secara atomik di [FilmPosterController.php](file:///d:/Coding/SynemaX/app/Http/Controllers/Admin/FilmPosterController.php).
    - Seluruh kartu poster yang berhasil dipasangkan serentak dilenyapkan dari tampilan DOM antrean secara instan.
    - Menambahkan pengujian integrasi komprehensif pada [AdminPosterRemovalFeatureTest.php](file:///d:/Coding/SynemaX/tests/Feature/AdminPosterRemovalFeatureTest.php) (8 pengujian, 39 asersi, 100% Lulus).

---

### J. Penyetelan Kinerja MariaDB & Efisiensi Hardware STB Amlogic (Commit `01b4a14`)
* **Konteks Lingkungan Produksi:**
  Aplikasi SynemaX dijalankan pada perangkat keras mini STB (Set-Top Box Amlogic s905x) dengan sumber daya terbatas: **2 GB RAM** dan **8 GB Penyimpanan eMMC**.
* **Gejala Masalah:**
  Saat mengakses web dari jaringan luar via Cloudflare, sering muncul pesan galat `520 Web server is returning an unknown error (Host Error)` akibat proses MariaDB dan PHP yang memakan memori berlebih dan kehabisan alokasi RAM (*OOM - Out of Memory*).
* **Solusi Penyetelan Konfigurasi Khusus STB (`/etc/mysql/mariadb.conf.d/99-stb-lowram.cnf`):**
  ```ini
  [mysqld]
  # 1. Matikan metrik internal yang tidak diperlukan (hemat ~100-150 MB RAM)
  performance_schema = OFF

  # 2. Batasi buffer pool RAM InnoDB ke 96 MB (sangat cukup untuk database studio)
  innodb_buffer_pool_size = 96M

  # 3. Batasi koneksi maksimal dan cache tabel agar hemat memori
  max_connections = 25
  table_open_cache = 200
  ```
* **Hasil Verifikasi Lapangan di STB:**
  - Penggunaan memori MariaDB turun drastis dari **~350 MB** menjadi hanya **123.5 MB**.
  - Mengaktifkan swap memori sebesar 1 GB (`958 MB free`).
  - Sistem memiliki sisa memori bebas (**1.4 GB Available RAM** dari total 1.9 GB), melenyapkan error 520 Cloudflare dan membuat server stabil beroperasi 24/7.

---

### K. Eliminasi Ledakan Query N+1 pada Halaman Kelola Jadwal (/admin/jadwal) (Commit `1f42bc6`)
* **Akar Masalah:**
  Saat admin membuka halaman Kelola Jadwal (`/admin/jadwal`), antarmuka memuat ratusan slot sesi tayang lintas tanggal dan jam. Di balik layar, terjadi *query explosion* lebih dari **10.000 query database** karena setiap kartu sesi di view Blade mengeksekusi:
  - `candidateFilmTallies()` (menghitung jumlah vote film).
  - `occupiedBookings()` (mengecek pemesanan perorangan atau privat).
  - Pengecekan status kuorum (`isCancelledByQuorum()`).
  Hal ini mengakibatkan CPU STB melonjak 100% dan halaman terasa sangat lambat (loading berputar lama).
* **Solusi & Rekayasa:**
  1. **In-Memory Caching & Collection-Based Calculation:**
     - Di [Session.php](file:///d:/Coding/SynemaX/app/Models/Session.php), metode `candidateFilmTallies()`, `hasActivePrivateBooking()`, `hasActivePerPersonBookings()`, dan `isCancelledByQuorum()` kini mendeteksi apakah relasi `occupiedBookings` telah ter-load di memori. Jika sudah, kalkulasi dilakukan murni menggunakan koleksi memori PHP tanpa query database baru.
     - Menambahkan properti memoized cache `$_talliesCache` pada model `Session`.
  2. **Eager Loading Minimal Kolom:**
     - Di [ScheduleController.php](file:///d:/Coding/SynemaX/app/Http/Controllers/Admin/ScheduleController.php), query sesi jadwal dioptimasi dengan eager loading kolom minimal: `film:id,title,slug,duration_minutes,poster_path` dan `occupiedBookings.film:id,title,slug`.
     - Pilihan film yang memenuhi durasi sesi dihitung satu kali di awal (*precomputation*) menggunakan fungsi helper memori.
  3. **Penyederhanaan Logika Tampilan Jadwal ([schedule/index.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/schedule/index.blade.php)):**
     - Menggantikan puluhan ribu query berulang di blade dengan operasi instan `array_diff_key` di memori PHP.
  4. **Hasil Verifikasi:**
     - Jumlah query database pada halaman `/admin/jadwal` terpangkas drastis dari **>10.000 query** menjadi hanya **4–6 query instan**.
     - Waktu pemuatan halaman di STB turun dari puluhan detik menjadi di bawah 1 detik (*sub-second response*).

---

### L. Kebersihan Penyimpanan STB & Manajemen Kotak Sampah Permanen (Commit `1f42bc6`)
* **Kebutuhan Pengguna:**
  Dengan kapasitas disk STB hanya 8 GB, file poster yang tidak terpakai, berkas sementara, serta film yang sudah dihapus dikhawatirkan menumpuk dan memenuhi penyimpanan eMMC STB.
* **Solusi & Rekayasa:**
  1. **Tombol "Bersihkan Sampah Berkas" di Halaman Poster Massal:**
     - Menambahkan kartu *Kebersihan Penyimpanan STB* pada [posters.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/films/posters.blade.php) dengan tombol aksi membersihkan berkas usang di direktori `tmp_posters/` dan gambar orphan melalui endpoint `cleanStorage` pada [FilmPosterController.php](file:///d:/Coding/SynemaX/app/Http/Controllers/Admin/FilmPosterController.php).
  2. **Tab "Sampah" Selalu Terlihat di Katalog Admin:**
     - Di [films/index.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/films/index.blade.php), tab filter **"Sampah (X)"** kini selalu ditampilkan agar pengelola dapat memeriksa dan mengelola arsip film yang terhapus secara teliti.
  3. **Fitur Hapus Permanen (Force Destroy):**
     - Menambahkan rute dan metode `forceDestroy` di [FilmController.php](file:///d:/Coding/SynemaX/app/Http/Controllers/Admin/FilmController.php).
     - Tombol **"Hapus Permanen"** disematkan pada baris tabel desktop, kartu mobile, dan aksi massal (*bulk action*).
     - Saat dieksekusi, sistem menghapus record film dari database dan secara otomatis melenyapkan berkas fisik posternya dari penyimpanan publik STB (`Storage::disk('public')->delete(...)`), membebaskan ruang disk eMMC seketika.

---

### M. Perbaikan Checkbox Master "Pilih Semua" Katalog Film (Commit `8d349f9`)
* **Akar Masalah:**
  Pada tabel katalog film admin, tombol centang master di header kolom tabel tidak berfungsi mencentang baris-baris film di bawahnya.
* **Penyebab & Solusi:**
  - *Mismatch Tipe Data:* Array `filmsOnPage` pada Alpine.js berisi nilai integer ID, sementara atribut `value` pada input checkbox HTML bertipe string, menyebabkan perbandingan `Array.includes()` pada Alpine gagal.
  - *Logika Toggle:* Mengubah logika `toggleAll()` dan `updateCheckAll()` di [films/index.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/films/index.blade.php) dengan menormalisasi seluruh ID ke tipe `String(id)`. Master checkbox kini berfungsi sempurna secara dua arah.

---

### N. Rekomendasi Film Serupa Berbasis Keselarasan Genre (Commit `8d349f9`)
* **Kebutuhan Bisnis:**
  Jika penonton membuka halaman film dengan genre *Drama*, bagian "Rekomendasi Film Serupa" di bawahnya harus memprioritaskan film bergenre sejenis, bukan menampilkan film horor secara acak.
* **Solusi Teknis:**
  - Pada metode `filmShow` di [PublicCinemaController.php](file:///d:/Coding/SynemaX/app/Http/Controllers/PublicCinemaController.php), query rekomendasi "Film Lainnya" kini memprioritaskan film aktif yang memiliki irisan genre yang sama dengan film yang sedang dilihat:
    ```php
    $otherFilmsQuery = Film::query()
        ->where('is_active', true)
        ->where('id', '!=', $film->id);

    // Filter genre relevan
    $genres = $film->genresList();
    if (!empty($genres)) {
        $otherFilmsQuery->where(function ($q) use ($genres) {
            foreach ($genres as $g) {
                $q->orWhere('genre', 'like', '%' . $g . '%');
            }
        });
    }
    ```
  - Jika film dengan genre serupa kurang dari kuota tampilan, sistem secara elegan melengkapinya dengan film populer lainnya.
  - Terverifikasi 100% pada [FilmCatalogAndFilterTest.php](file:///d:/Coding/SynemaX/tests/Feature/FilmCatalogAndFilterTest.php).

---

### O. Fitur Pin / Badge Poster Kustom untuk Admin & Publik (Commit `c5c92c0`)
* **Kebutuhan Pengguna & Referensi Visual:**
  Pengelola meminta tanda khusus (*badge/pin*) di atas kartu poster film yang terlihat mencolok baik di panel admin maupun web publik (seperti penanda kartu OTT/Netflix "Baru Ditambahkan" dengan badge merah menyala tebal di sisi bawah poster). Teks pin tidak boleh kaku/statis, melainkan bisa dicustom bebas oleh admin per film.
* **Solusi & Rekayasa yang Diterapkan:**
  1. **Migrasi Database Aditif:**
     - Berkas migrasi [2026_11_07_083231_add_custom_badge_to_films_table.php](file:///d:/Coding/SynemaX/database/migrations/2026_11_07_083231_add_custom_badge_to_films_table.php) menambahkan kolom `custom_badge` (`VARCHAR(50)`, nullable) pada tabel `films`.
     - Migrasi bersifat murni aditif dan aman dijalankan pada server live via `php artisan migrate --force` tanpa menghapus atau mengubah data film yang sudah ada.
  2. **Model & Validasi Controller:**
     - Menambahkan `custom_badge` ke atribut `#[Fillable]` pada [Film.php](file:///d:/Coding/SynemaX/app/Models/Film.php) serta helper method `hasCustomBadge(): bool`.
     - Menambahkan aturan validasi `'custom_badge' => ['nullable', 'string', 'max:50']` pada [FilmController.php](file:///d:/Coding/SynemaX/app/Http/Controllers/Admin/FilmController.php).
  3. **Antarmuka Form Tambah & Ubah Film ([films/form.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/films/form.blade.php)):**
     - Dilengkapi input teks label pin dan deretan tombol pilihan cepat 1-klik (*presets*):
       - 🔥 **Baru Ditambahkan**
       - ⚡ **Trending**
       - ⏳ **Segera Hadir**
       - ⭐ **Eksklusif**
       - ❤️ **Favorit**
       - ✕ **Hapus Pin (Kosongkan)**
     - **Pratinjau Langsung (Live Preview):** Box pratinjau interaktif di samping input menampilkan kartu poster dengan badge merah menempel secara *real-time* saat admin mengetik atau mengklik tombol preset.
  4. **Aksi Massal Pin Poster (Bulk Action):**
     - Di daftar katalog film admin [films/index.blade.php](file:///d:/Coding/SynemaX/resources/views/admin/films/index.blade.php), ditambahkan modal **"🔥 Pin Poster"** di bilah aksi massal untuk memasang atau mencabut pin pada banyak film sekaligus.
  5. **Komponen Visual Reusable ([poster-badge.blade.php](file:///d:/Coding/SynemaX/resources/views/components/cinema/poster-badge.blade.php)):**
     - Komponen independen bergaya OTT/Netflix dengan warna latar merah kontras (`#E50914`), teks tebal putih, sudut membulat, border tipis, dan *drop-shadow* presisi.
     - Terpasang menyeluruh di:
       - **Katalog Publik (`/films`)**
       - **Beranda Publik (`/`)** (reaktif dengan Alpine.js data model)
       - **Detail Film (`/film/{slug}`)** (poster desktop, mobile, dan kartu rekomendasi)
       - **Panel Admin (`/admin/films`)** (thumbnail tabel desktop, kartu mobile, dan chip status samping judul)
       - **Komponen Poster Card Bioskop (`<x-cinema.poster-card>`)**
  6. **Pengujian Khusus:**
     - Berkas pengujian baru [CustomFilmBadgeTest.php](file:///d:/Coding/SynemaX/tests/Feature/CustomFilmBadgeTest.php) dengan 5 metode pengujian (16 asersi, 100% Lulus).

---

## 3. PANDUAN STANDAR UKURAN ASET GAMBAR (MEDIA ASSET SPECS)

Untuk memastikan seluruh media visual bioskop tampil presisi, tidak terpotong, tajam di layar Retina/OLED, dan dimuat secara efisien, seluruh pengembang dan admin merujuk pada standar kanonikal [ImageDimensionGuide.php](file:///d:/Coding/SynemaX/app/Support/ImageDimensionGuide.php):

| Kode Aset (*Key*) | Jenis Komponen / Box Tampilan | Rasio Aspek | Rekomendasi Dimensi (Px) | Format Berkas | Keterangan & Posisi Tampilan |
|---|---|---|---|---|---|
| `film_poster` | **Poster Film (Katalog & Detail)** | `2:3` (Vertikal) | **600 × 900 px** *(Maks: 1000 × 1500)* | WebP / JPG | Kartu katalog film, hero detail film, slider jadwal, preview e-tiket. |
| `hero_desktop` | **Hero Carousel Beranda (Desktop)** | `16:9` (Lanskap) | **1920 × 1080 px** | WebP / JPG | Banner utama bagian atas Beranda. Hindari teks penting di tepi 10% bawah. |
| `hero_mobile` | **Hero Carousel Beranda (Mobile)** | `4:3` / `16:9` | **1080 × 810 px** | WebP / JPG | Tampilan carousel saat diakses via ponsel pintar. |
| `nobar_poster` | **Banner Nonton Bareng (Nobar)** | `16:9` (Lanskap) | **1200 × 675 px** | WebP / JPG | Poster laga/pertandingan nobar, header detail nobar, kartu preview beranda. |
| `promo_banner` | **Banner Promo & Penawaran** | `16:9` (Lanskap) | **1200 × 675 px** | WebP / JPG | Kartu promo katalog, banner diskon combo, dan header promo. |
| `news_cover` | **Sampul Warta / Berita Studio** | `16:9` (Lanskap) | **1200 × 675 px** | WebP / JPG | Thumbnail kartu warta berita dan header artikel pengumuman studio. |
| `snack_photo` | **Produk Camilan & Minuman** | `1:1` (Persegi) | **500 × 500 px** | WebP / PNG | Foto menu jajan, popcorn, minuman dingin, dan snack hangat. |
| `qris_image` | **Gambar Kode QRIS Resmi Toko** | `1:1` (Persegi) | **800 × 800 px** | PNG / JPG | Kode QRIS toko dari bank/GPN. Resolusi tinggi agar mudah discan kamera HP. |
| `site_logo` | **Logo Bioskop (Terang & Gelap)** | Bebas Horizontal | **500 × 120 px** *(atau Vektor)* | SVG / PNG Transparan | Logo utama pada navbar terang/gelap dan kop cetak tiket fisik. |
| `site_favicon` | **Favicon Tab Browser** | `1:1` (Persegi) | **64 × 64 px** / **128 × 128 px** | SVG / PNG / ICO | Ikon kecil di tab browser dan bookmark layar utama ponsel. |
| `site_og_image` | **Open Graph (Social Share / WA)** | `1.91:1` (Lanskap) | **1200 × 630 px** | JPG / PNG | Gambar preview otomatis saat tautan dibagikan ke WhatsApp & media sosial. |

---

## 4. KEAMANAN, PRIVASI & INTEGRITAS DATA

1. **Penghapusan Jalur Pintas QA (/qa-admin-login):**
   - Rute pengujian pintas tanpa sandi telah dihapus secara permanen dari basis kode produksi.
   - Pengecekan HTTP langsung mengonfirmasi status **404 Not Found**.
2. **Perlindungan Database Asli & Kebijakan Cadangan:**
   - Berkas seeder [DatabaseSeeder.php](file:///d:/Coding/SynemaX/database/seeders/DatabaseSeeder.php) dilindungi dengan *safety guard* yang melempar `RuntimeException` jika dieksekusi di luar database pengujian.
   - Data transaksi riil pelanggan di `database/database.sqlite` terjaga aman dari penimpaan pengujian otomatis.
   - Tersedia cadangan bertanda waktu (*timestamped backup*) berkala di direktori `database/`.
3. **Integritas E-Tiket & Privasi Pelanggan:**
   - Akses halaman tiket dan bukti pembayaran diverifikasi menggunakan token hash SHA-256 (`access_token_hash`) dan pelacakan sesi perangkat aman ([DeviceBookingTracker.php](file:///d:/Coding/SynemaX/app/Support/DeviceBookingTracker.php)).
   - Nomor WhatsApp pelanggan dinormalisasi secara otomatis ke standar internasional (+62) oleh [WhatsAppNumber.php](file:///d:/Coding/SynemaX/app/Support/WhatsAppNumber.php).
   - Pada antarmuka admin pemindai tiket, nomor kontak pelanggan disamarkan sebagian (`+62 812-••••-1234`) dan hanya dapat dilihat penuh melalui tombol buka masker in-app.
4. **Pencegahan Dialog Browser Native (In-App Modal Standard):**
   - Seluruh dialog interaksi pengguna (konfirmasi pembatalan, pesan kesalahan, override check-in) menggunakan komponen modal in-app yang adaptif terhadap tema terang/gelap.
   - Pengecualian resmi hanya berlaku untuk dialog izin perangkat keras kamera bawaan peramban (`navigator.mediaDevices.getUserMedia`) dan fungsi cetak (`window.print()`).

---

## 5. HASIL VERIFIKASI & PENGUJIAN OTOMATIS (QA AUDIT)

### A. Pengujian Fitur & Backend (PHPUnit Test Suite)
Rangkaian pengujian fitur inti dieksekusi menggunakan SQLite in-memory:

| Modul Pengujian (Test Suite) | Jumlah Tes | Jumlah Asersi | Status Hasil |
|---|---|---|---|
| **SingleFlashAlertTest** (Pengujian pencegahan alert ganda di semua halaman publik) | 4 tes | 15 asersi | **PASSED (100%)** ✅ |
| **TicketScannerTest** (Logika pemindai, normalisasi URL/kode, verdict, idempotensi, audit) | 16 tes | 179 asersi | **PASSED (100%)** ✅ |
| **CustomFilmBadgeTest** (Fitur badge poster kustom admin, preset, form, bulk action, dan publik) | 5 tes | 16 asersi | **PASSED (100%)** ✅ |
| **AdminPosterRemovalFeatureTest** (Reaktivitas hapus & pasang poster manual per item & massal) | 8 tes | 39 asersi | **PASSED (100%)** ✅ |
| **FilmCatalogAndFilterTest** (Katalog film, filter genre & tahun, rekomendasi sejalan genre) | 7 tes | 31 asersi | **PASSED (100%)** ✅ |
| **ScheduleSessionTimeStatusTest** (Status waktu sesi jadwal, booking privat, optimasi query) | 3 tes | 7 asersi | **PASSED (100%)** ✅ |
| **ScheduleGenerationTest** (Template jadwal baku dan pembentukan sesi otomatis) | 3 tes | 21 asersi | **PASSED (100%)** ✅ |
| **ImageDimensionGuideTest** (Validasi panduan 10 dimensi gambar kanonikal) | 2 tes | 99 asersi | **PASSED (100%)** ✅ |
| **NoBrowserDialogSafetyGuardTest** (Pencegahan popup alert/confirm native browser) | 1 tes | 1 asersi | **PASSED (100%)** ✅ |
| **NoNativeSelectTagTest** (Validasi arsitektur komponen custom select UI) | 1 tes | 1 asersi | **PASSED (100%)** ✅ |
| **NewsArticleTest** (Sanitasi URL artikel, redirect alias FAQ & harga) | 9 tes | 29 asersi | **PASSED (100%)** ✅ |
| **PaymentThemeSyncTest** (Paritas tema terang/gelap pada checkout rekening & QRIS) | 2 tes | 17 asersi | **PASSED (100%)** ✅ |
| **AdminSettingOperasionalTest** (Persistensi multi-rekening & setting operasional) | 6 tes | 35 asersi | **PASSED (100%)** ✅ |
| **SettingsManagementTest** (Manajemen panel admin, identitas, SEO) | 5 tes | 35 asersi | **PASSED (100%)** ✅ |
| **SnackOrderFlowTest** (Alur pemesanan camilan & transparansi rincian biaya) | 9 tes | 78 asersi | **PASSED (100%)** ✅ |
| **TOTAL INTI TERVERIFIKASI** | **81 tes** | **603 asersi** | **100% LULUS** ✅ |

### B. Pengujian Antarmuka Peramban Headless (Playwright UI Audit)
Pengujian interaktivitas dan paritas visual dijalankan melalui `node tests/ticket_scanner_browser_audit.mjs`:
- **Viewport Mobile (390 × 844 px):** 9/9 cek LULUS ✅ (HTTP 200, 3 Tab aktif, touch target >= 44px, input kode 56px, eliminasi kata "kursi", paritas tema terang & gelap).
- **Viewport Desktop (1366 × 768 px):** 9/9 cek LULUS ✅ (Konsol terpusat, navigasi kembali ke Hari Ini, in-app error handling tanpa popup native).
- **Kesalahan Konsol & Script:** 0 runtime error, 0 Alpine.js expression error.

### C. Standarisasi Format Kode (Laravel Pint)
- Seluruh berkas PHP baru dan yang dimodifikasi telah diformat secara ketat menggunakan `vendor/bin/pint --format agent` (0 pelanggaran gaya kode PSR-12).

---

## 6. PANDUAN PENERAPAN KE SERVER PRODUKSI & STB (DEPLOYMENT GUIDE)

Seluruh perubahan kode telah tersimpan aman pada branch **`main`**. Untuk memperbarui aplikasi pada server live / STB (`/var/www/synemax`), jalankan langkah-langkah terstruktur berikut melalui SSH / terminal:

```bash
# 1. Masuk ke direktori root aplikasi di STB
cd /var/www/synemax

# 2. Ambil pembaruan kode terbaru dari repositori pusat GitHub
git pull origin main

# 3. Jalankan migrasi database aditif (menambahkan kolom custom_badge ke tabel films)
# CATATAN PENTING: Perintah ini 100% AMAN dan TIDAK AKAN MENGHAPUS data lama!
php artisan migrate --force

# 4. Bersihkan seluruh cache konfigurasi, rute, dan tampilan lama
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Bangun cache performa tinggi untuk lingkungan produksi STB
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> [!NOTE]
> Perintah `php artisan migrate --force` **TIDAK MENGHAPUS DATA APAPUN**. Flag `--force` hanya digunakan untuk melewati konfirmasi prompt di lingkungan `APP_ENV=production`. Perintah berbahaya yang menghapus data adalah `migrate:fresh` (jangan pernah dijalankan di server live).

---

## 7. STRUKTUR ARSITEKTUR TEKNIS SISTEM

```
SynemaX (Laravel 12 / PHP 8.4)
|-- app/
|   |-- Http/Controllers/
|   |   |-- Admin/
|   |   |   |-- FilmController.php           <-- CRUD Film, Aksi Massal (Pin/Status), & Force Destroy
|   |   |   |-- FilmPosterController.php     <-- Manajemen Poster Massal, Batch Assign, & Bersih Storage
|   |   |   |-- ScheduleController.php       <-- Kelola Jadwal, Eager Loading Minimal, & Optimasi Query
|   |   |   |-- TicketScannerController.php  <-- Pengendali Antarmuka & API Scanner Kru
|   |   |   |-- BookingController.php        <-- Manajemen Booking & Aksi Verifikasi Manual
|   |   |   |-- DashboardController.php      <-- Ringkasan Operasional & Kehadiran Hari Ini
|   |   |   |-- ReportController.php         <-- Laporan Kehadiran & Sebaran Metode Check-In
|   |   |   |-- SettingController.php        <-- Pengaturan Operasional, Multi-Rekening & QRIS
|   |   |   `-- SnackPreparationController.php<-- Dapur & Antrean Camilan Penonton
|   |   |-- PublicBookingController.php      <-- Alur Pemesanan, Pembayaran & E-Tiket Pelanggan
|   |   `-- PublicCinemaController.php       <-- Katalog Film, Rekomendasi Genre, Jadwal, & Beranda
|   |-- Models/
|   |   |-- Booking.php                      <-- Relasi Sesi, Pembayaran, Pelanggan, & Check-In
|   |   |-- BookingCheckin.php               <-- Log Jejak Audit Check-In Kru Bioskop
|   |   |-- Film.php                         <-- Katalog Film, Custom Badge, Tahun Rilis, Poster
|   |   `-- Session.php                      <-- Sesi Tayang, Waktu Baku, Kuorum Cache, & Kapasitas
|   |-- Services/
|   |   |-- CheckInService.php               <-- Mesin Aturan Check-In, Idempotensi, & Override
|   |   |-- CinemaSettings.php               <-- Layanan Konfigurasi & Cache Pengaturan
|   |   `-- TelegramNotificationService.php  <-- Notifikasi Outbox Staf & Pemilik
|   `-- Support/
|       |-- ImageDimensionGuide.php          <-- Standar Kanonikal Rasio & Resolusi Media
|       |-- DeviceBookingTracker.php         <-- Pelacakan Sesi & Keamanan Akses Tiket
|       `-- WhatsAppNumber.php               <-- Normalisasi Nomor WhatsApp Internasional
|-- database/
|   `-- migrations/
|       |-- 2026_11_03_180000_add_checkin_columns_and_booking_checkins_table.php
|       |-- 2026_11_06_000001_standardize_screening_session_times.php
|       `-- 2026_11_07_083231_add_custom_badge_to_films_table.php  <-- Kolom custom_badge
|-- public/
|   `-- vendor/
|       `-- jsqr/
|           `-- jsQR.js                      <-- Pustaka Pemindai QR Mandiri Lokal (256 KB)
`-- resources/views/
    |-- admin/
    |   |-- films/
    |   |   |-- form.blade.php               <-- Form Film + Preset Pin Badge & Live Preview
    |   |   |-- index.blade.php              <-- Katalog Film + Master Checkbox + Modal Pin
    |   |   `-- posters.blade.php            <-- Poster Massal + Reaktif Lenyap + Spectate Modal
    |   |-- scan/
    |   |   `-- index.blade.php              <-- Antarmuka Utama Pemindai Tiket (3 Tab)
    |   `-- schedule/
    |       `-- index.blade.php              <-- Jadwal Cepat Tanpa N+1 Query
    |-- components/
    |   |-- cinema/
    |   |   |-- poster-badge.blade.php       <-- Komponen Visual Pin Badge Poster OTT/Netflix
    |   |   `-- poster-card.blade.php        <-- Kartu Poster Bioskop dengan Pin Badge
    |-- layouts/
    |   `-- public.blade.php                 <-- Tata Letak Bersih Bebas Alert Redundan
    `-- public/
        |-- booking-form.blade.php           <-- Wizard Pemesanan Tiket & Camilan
        |-- films/
        |   |-- index.blade.php              <-- Katalog Film Publik dengan Badge
        |   `-- show.blade.php               <-- Detail Film + Badge + Rekomendasi Genre
        |-- home.blade.php                   <-- Beranda Reaktif Alpine dengan Badge
        |-- payment.blade.php                <-- Checkout Pembayaran Paritas Tema
        `-- ticket.blade.php                 <-- E-Tiket Resmi Pelanggan dengan QR Code
```

---
*Laporan ini disusun secara komprehensif dan faktual untuk dokumentasi resmi proyek SynemaX.*
