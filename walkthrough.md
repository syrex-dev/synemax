# PANDUAN PENGGUNA & OPERASIONAL LENGKAP SYNEMAX (WALKTHROUGH.MD)
**Platform:** Sistem Bioskop Mini Privat SynemaX  
**Teknologi:** Laravel 12 / PHP 8.4 / TailwindCSS / Alpine.js / SQLite  
**URL Produksi:** `https://synemax.web.id`  
**Sasaran Pembaca:** Pelanggan Publik, Kru Front-Desk / Kasir Studio, dan Administrator Bioskop  

---

## DAFTAR ISI
1. [Ikhtisar Konsep & Hak Akses Sistem](#1-ikhtisar-konsep--hak-akses-sistem)
2. [Alur Pengalaman Pelanggan (Customer Journey)](#2-alur-pengalaman-pelanggan-customer-journey)
   - 2.1 [Eksplorasi Katalog & Jadwal Tayang](#21-eksplorasi-katalog--jadwal-tayang)
   - 2.2 [Pemesanan Tiket & Pemilihan Paket Studio](#22-pemesanan-tiket--pemilihan-paket-studio)
   - 2.3 [Pemesanan Camilan & Minuman Studio](#23-pemesanan-camilan--minuman-studio)
   - 2.4 [Checkout Pembayaran (Transfer Bank & QRIS)](#24-checkout-pembayaran-transfer-bank--qris)
   - 2.5 [Unggah Bukti Pembayaran & Terbit E-Tiket](#25-unggah-bukti-pembayaran--terbit-e-tiket)
   - 2.6 [Pengecekan Tiket Mandiri Tanpa Login](#26-pengecekan-tiket-mandiri-tanpa-login)
   - 2.7 [Pengajuan Batal / Ubah Sesi Mandiri](#27-pengajuan-batal--ubah-sesi-mandiri)
3. [Alur Operasional Kru Pintu Masuk: Pemindai Tiket (`/admin/scan`)](#3-alur-operasional-kru-pintu-masuk-pemindai-tiket-adminscan)
   - 3.1 [Akses Cepat Konsol Pemindai](#31-akses-cepat-konsol-pemindai)
   - 3.2 [Pemilihan Konteks Sesi Studio](#32-pemilihan-konteks-sesi-studio)
   - 3.3 [Metode 1: Pemindaian QR Kamera Langsung](#33-metode-1-pemindaian-qr-kamera-langsung)
   - 3.4 [Metode 2: Pencarian Kode Manual / Akhiran 4 Karakter](#34-metode-2-pencarian-kode-manual--akhiran-4-karakter)
   - 3.5 [Metode 3: Tab Daftar Hadir Sesi Realtime](#35-metode-3-tab-daftar-hadir-sesi-realtime)
   - 3.6 [Memahami Kartu Verdict & Tombol Tandai Hadir](#36-memahami-kartu-verdict--tombol-tandai-hadir)
   - 3.7 [Prosedur Override Check-In (Sesi Lain / Luar Jam)](#37-prosedur-override-check-in-sesi-lain--luar-jam)
   - 3.8 [Prosedur Pembatalan Kehadiran (Undo Check-In)](#38-prosedur-pembatalan-kehadiran-undo-check-in)
4. [Alur Manajemen Operasional Administrator](#4-alur-manajemen-operasional-administrator)
   - 4.1 [Dashboard Operasional Hari Ini](#41-dashboard-operasional-hari-ini)
   - 4.2 [Verifikasi Bukti Pembayaran Transfer](#42-verifikasi-bukti-pembayaran-transfer)
   - 4.3 [Koordinasi Dapur & Penyiapan Jajan Penonton](#43-koordinasi-dapur--penyiapan-jajan-penonton)
   - 4.4 [Manajemen Jadwal Tayang & Resolusi Kuorum](#44-manajemen-jadwal-tayang--resolusi-kuorum)
   - 4.5 [Laporan Statistik & Sebaran Metode Check-In](#45-laporan-statistik--sebaran-metode-check-in)
5. [Alur Konfigurasi & Pengaturan Sistem Bioskop](#5-alur-konfigurasi--pengaturan-sistem-bioskop)
   - 5.1 [Pengaturan Multi-Rekening Transfer & QRIS Toko](#51-pengaturan-multi-rekening-transfer--qris-toko)
   - 5.2 [Pengaturan Jendela Check-In & Suara Beep](#52-pengaturan-jendela-check-in--suara-beep)
   - 5.3 [Kurasi Urutan Film & Banner Media](#53-kurasi-urutan-film--banner-media)
   - 5.4 [Manajemen Poster Massal & Antrean Pencocokan Manual](#54-manajemen-poster-massal--antrean-pencocokan-manual)
6. [Panduan Pengujian Fisik & Troubleshooting Lapangan](#6-panduan-pengujian-fisik--troubleshooting-lapangan)
   - 6.1 [Uji Coba Pemindai via Ponsel & Wi-Fi Lokal](#61-uji-coba-pemindai-via-ponsel--wi-fi-lokal)
   - 6.2 [Mengatasi Izin Kamera Terblokir & Konteks HTTPS](#62-mengatasi-izin-kamera-terblokir--konteks-https)
   - 6.3 [Menjalankan Rangkaian Pengujian Otomatis](#63-menjalankan-rangkaian-pengujian-otomatis)

---

## 1. IKHTISAR KONSEP & HAK AKSES SISTEM

SynemaX dirancang khusus sebagai sistem manajemen bioskop mini privat (*private mini cinema*). Berbeda dengan bioskop konvensional berskala ratusan kursi bernomor, SynemaX mengusung konsep **ruang studio intim berkapasitas 6 penonton per sesi**.

```
+-------------------------------------------------------------------------------+
|                             STRUKTUR PERAN SISTEM                             |
+-------------------------------------------------------------------------------+
|  1. PELANGGAN UMUM (Publik / Tamu):                                           |
|     - Mengakses katalog film, jadwal, harga paket, dan promo.                 |
|     - Melakukan pemesanan tiket tanpa wajib registrasi akun.                  |
|     - Membayar via Transfer / QRIS dan mendapatkan e-tiket resmi.             |
|     - Memantau status tiket melalui tautan aman atau pencarian No WhatsApp.   |
|                                                                               |
|  2. KRU PINTU MASUK / STAF KASIR:                                             |
|     - Mengakses konsol pemindai tiket cepat: `/admin/scan` atau `/scan`.      |
|     - Memindai QR tiket penonton menggunakan kamera HP / BarcodeDetector.     |
|     - Memvalidasi kehadiran instan (idempoten & bebas bentrok data).          |
|     - Melakukan override check-in jika penonton hadir di sesi berbeda.        |
|                                                                               |
|  3. ADMINISTRATOR OPERASIONAL:                                                |
|     - Mengelola jadwal tayang, kuorum penonton, dan film.                     |
|     - Memverifikasi bukti pembayaran transfer bank.                           |
|     - Mengelola penyiapan camilan di dapur sesuai kedatangan penonton.        |
|     - Mengatur tarif, multi-rekening bank, QRIS, dan laporan performa.        |
+-------------------------------------------------------------------------------+
```

---

## 2. ALUR PENGALAMAN PELANGGAN (CUSTOMER JOURNEY)

### 2.1 Eksplorasi Katalog & Jadwal Tayang
1. Pengunjung membuka halaman utama [synemax.web.id](https://synemax.web.id).
2. **Hero Carousel:** Menampilkan film unggulan atau pertandingan nonton bareng (nobar) terkini.
3. **Katalog Film (`/film`):**
   - Daftar film otomatis terurut dari tahun rilis terbaru ke terlama (`release_year DESC`).
   - Pengunjung dapat memfilter film berdasarkan genre, kategori usia (SU, 13+, 17+), atau mencari berdasarkan judul.
   - Mengklik poster film membuka halaman rincian sinopsis, durasi, trailer, serta jadwal tayang yang tersedia.
4. **Jadwal Tayang (`/jadwal`):**
   - Menampilkan tanggal hari ini dan hari-hari berikutnya.
   - **Standar Slot Jam Tayang Studio:**
     - **Senin – Kamis:** Dibuka 3 sesi per hari, yaitu pukul `13:00`, `16:00`, dan `19:00 WIB`.
     - **Jumat – Minggu & Hari Libur:** Dibuka 4 sesi per hari, yaitu pukul `09:00`, `13:00`, `16:00`, dan `19:00 WIB`.
   - Setiap sesi tayang memuat informasi jam mulai, jam selesai (durasi 150 menit), judul film, jenis sesi (Publik / Sewa Privat), serta jumlah tempat yang masih tersedia (misal: *"Tersisa 4 orang"*).

---

### 2.2 Pemesanan Tiket & Pemilihan Paket Studio
1. Pada sesi yang dipilih, pengunjung menekan tombol **"Pesan Sekarang"** untuk masuk ke wizard pemesanan (`/booking/{session}`).
2. **Pilihan Jenis Paket Nonton:**
   - **Tiket Per Orang (Reguler):** Pengunjung menentukan jumlah tiket (1 sampai sisa kapasitas sesi, maksimal 6 orang).
   - **Paket Couple (2 Orang):** Opsi hemat khusus pasangan selama kuota sesi masih memungkinkan.
   - **Sewa Studio Privat (Kunci 6 Orang):** Mengunci seluruh studio hanya untuk kelompok pemesan secara eksklusif tanpa penonton asing lainnya.
3. **Pengisian Identitas Pemesan:**
   - **Nama Lengkap:** Nama pemesan utama.
   - **Nomor WhatsApp:** Nomor aktif untuk menerima tautan tiket dan notifikasi. Sistem secara otomatis menormalkan format nomor ke standar internasional (`+628...`).
   - **Catatan Tambahan (Opsional):** Pesan khusus untuk kru bioskop.

---

### 2.3 Pemesanan Camilan & Minuman Studio
1. Pada langkah berikutnya, sistem menampilkan menu jajan studio (popcorn hangat, kentang goreng, kopi, cola dingin, air mineral).
2. Pengunjung dapat memilih kuantitas setiap item menggunakan tombol counter `+` / `-`.
3. Total biaya jajan dihitung secara transparan dan ditambahkan ke ringkasan total pembayaran tiket.
4. *Catatan: Langkah ini bersifat opsional; pengunjung dapat melewatinya jika hanya ingin memesan tiket nonton.*

---

### 2.4 Checkout Pembayaran (Transfer Bank & QRIS)
1. Setelah menekan tombol konfirmasi pemesanan, sistem membuat rekaman booking dengan status `menunggu_pembayaran` dan mengalihkan ke halaman checkout (`/pembayaran/{code}`).
2. **Timer Hitung Mundur:** Diberikan batas waktu bayar (misal 15 menit). Jika waktu habis, tempat yang dikunci akan otomatis dilepaskan kembali ke publik.
3. **Pilihan Saluran Pembayaran:**
   - **Metode Transfer Bank / E-Wallet:**
     - Sistem menampilkan daftar rekening tujuan yang aktif (misal BCA, BRI, Mandiri, DANA).
     - Pengunjung dapat menekan tombol **"Salin Nomor Rekening"** untuk kemudahan transfer di aplikasi mobile banking.
     - Tampilan telah disinkronkan dengan rasio kontras tinggi di mode terang maupun bioskop gelap.
   - **Metode QRIS Otomatis:**
     - Menampilkan kode QRIS resmi toko SynemaX beresolusi tinggi.
     - Pengunjung cukup membuka aplikasi bank atau e-wallet apa pun (BCA Mobile, Livin, GoPay, OVO, ShopeePay) lalu memindai kode QRIS di layar.
4. **Sticky Alert Status Belum Bayar:**
   Jika pengunjung keluar dari halaman checkout sebelum menyelesaikan pembayaran, banner melayang (*sticky alert*) berwarna kuning akan muncul di bagian bawah layar publik dengan tombol langsung kembali ke pembayaran.

---

### 2.5 Unggah Bukti Pembayaran & Terbit E-Tiket
1. Setelah melakukan transfer atau memindai QRIS, pengunjung mengunggah foto bukti transfer (struk/tangkapan layar bank) pada form yang tersedia di halaman pembayaran.
2. Sistem memvalidasi berkas gambar (format JPG/PNG/WebP, batas ukuran aman) dan mengubah status booking menjadi `menunggu_konfirmasi` (*Diproses*).
3. Halaman secara otomatis dialihkan ke tampilan e-tiket resmi (`/tiket/{code}`).
4. **Tampilan E-Tiket Memuat:**
   - **Kode Booking Unik:** Format standar (misal: `SNX-20261105-ABCD`).
   - **Kode QR Khusus:** Untuk ditunjukkan kepada kru saat tiba di studio.
   - **Stepper Status Tiket:** Visual progres status (Menunggu Pembayaran -> Menunggu Konfirmasi -> Aktif / Siap Nonton -> Hadir di Studio).
   - **Rincian Sesi:** Judul film, tanggal & jam tayang, jenis paket, rincian pesanan camilan.
   - **Tombol Unduh / Cetak Tiket:** Menghasilkan salinan e-tiket ramah cetak (*print-friendly*).

---

### 2.6 Pengecekan Tiket Mandiri Tanpa Login
Bagi pelanggan yang menutup peramban atau lupa menyimpan tautan tiket:
1. Akses menu **"Tiket"** pada bilah navigasi atau buka URL `/tiket` (alias `/cek-booking`).
2. Masukkan nomor WhatsApp atau Kode Booking yang digunakan saat memesan.
3. Sistem akan menampilkan daftar seluruh tiket pelanggan yang tersimpan pada sesi perangkat maupun hasil pencarian nomor WhatsApp.
4. Tiket dikelompokkan ke dalam tab: **Aktif**, **Menunggu Bayar**, dan **Riwayat**.

---

### 2.7 Pengajuan Batal / Ubah Sesi Mandiri
1. Pada e-tiket yang belum lewat batas waktu pembatalan (*cancellation cutoff*, misal minimal 6 jam sebelum sesi dimulai), tersedia tombol **"Batalkan Pemesanan"**.
2. Pengunjung menekan tombol batal, membaca konfirmasi modal in-app, dan menyetujui pelepasan tempat.
3. Sistem secara otomatis membatalkan status booking, mengembalikan kuota penonton ke sesi, dan menampilkan notifikasi tunggal sukses tanpa menduplikasi banner alert di layar.

---

## 3. ALUR OPERASIONAL KRU PINTU MASUK: PEMINDAI TIKET (`/admin/scan`)

Fitur pemindai tiket dirancang untuk kru front-desk atau staf pintu masuk studio SynemaX agar proses verifikasi penonton berlangsung dalam hitungan detik.

```
                   +---------------------------------------+
                   |   Kru Mengakses /admin/scan / /scan   |
                   +---------------------------------------+
                                       |
                                       v
                   +---------------------------------------+
                   |     Pilih Sesi Studio yang Aktif      |
                   +---------------------------------------+
                                       |
        +------------------------------+------------------------------+
        |                              |                              |
        v                              v                              v
[ TAB 1: KAMERA ]             [ TAB 2: KODE MANUAL ]        [ TAB 3: DAFTAR SESI ]
Arahkan kamera ke QR          Ketik 4 digit akhir           Lihat daftar penonton,
e-tiket penonton              atau paste tautan tiket       pencarian live / filter
        |                              |                              |
        +------------------------------+------------------------------+
                                       |
                                       v
                   +---------------------------------------+
                   |        Evaluasi Status Tiket          |
                   |      oleh CheckInService Engine       |
                   +---------------------------------------+
                                       |
             +-------------------------+-------------------------+
             |                                                   |
             v                                                   v
   [ STATUS: SIAP MASUK ]                               [ STATUS TIDAK COCOK ]
   Kartu Hijau: Siap Nonton                             Kuning / Merah:
   - Identitas & Paket                                  - Sudah Hadir (Info jam)
   - Rincian Camilan Dapur                              - Belum Lunas / Batal
             |                                          - Sesi Lain / Luar Jam
             v                                                   |
   [ Klik TANDAI HADIR ]                                         v
   - Idempoten (Atomic Lock)                            [ Dialog In-App Override ]
   - Nada Audio Ping                                    Konfirmasi khusus kru
   - Status Berubah ke Hadir                                     |
   - Bottom Sheet Tutup Otomatis                                 v
   - Lencana Hadir di Dapur                             [ Simpan Audit Log ]
```

### 3.1 Akses Cepat Konsol Pemindai
- Kru masuk menggunakan akun staf di [synemax.web.id/login](https://synemax.web.id/login).
- Klik menu **"Scan tiket"** pada bilah navigasi admin, atau cukup ketikkan alamat singkat `/scan` pada bilah peramban ponsel.
- Halaman didesain minimalis *mobile-first*, bebas dari sidebar admin yang memakan ruang, sehingga nyaman digenggam satu tangan.

---

### 3.2 Pemilihan Konteks Sesi Studio
- Di bagian atas konsol, terdapat dropdown sesi studio (`<x-ui.select>`).
- Sistem secara cerdas otomatis memilih sesi yang sedang berlangsung saat itu atau sesi terdekat berikutnya.
- Menampilkan indikator ringkas kapasitas penonton: `"Hadir X dari Y orang"` (istilah "kursi" telah ditiadakan sesuai konsep bioskop privat).

---

### 3.3 Metode 1: Pemindaian QR Kamera Langsung
1. Buka **Tab Kamera** (ikon kamera di bilah segmen kapsul).
2. **Aktivasi Kamera:**
   - Tekan tombol **"Aktifkan kamera"**.
   - Pada dialog izin peramban (*permission prompt*), pilih **"Izinkan" / "Allow"**.
3. **Pengaturan Pemindai:**
   - Layar bidik kamera menyala dengan garis laser animasi *scan beam*.
   - **Ganti Kamera:** Tekan tombol putar di pojok atas untuk beralih antara kamera belakang (lingkungan terang) dan kamera depan.
   - **Lampu Senter (Torch):** Aktifkan tombol senter jika pencahayaan di lorong/pintu masuk studio redup.
4. **Arahkan ke QR E-Tiket:**
   - Arahkan kamera ke layar ponsel penonton atau cetakan tiket.
   - Pustaka lokal `jsQR` / `BarcodeDetector` memproses gambar secara instan.
   - Sistem memiliki pengunci pemindaian ganda (*deduplication lock* 3 detik) untuk mencegah trigger berulang.

---

### 3.4 Metode 2: Pencarian Kode Manual / Akhiran 4 Karakter
Jika kamera penonton retak, buram, atau kru menggunakan terminal kasir tanpa webcam:
1. Buka **Tab Kode** (ikon tanda pagar `#`).
2. Masukkan kode tiket pada kotak input tinggi 56px:
   - **Cukup 4-6 Karakter Akhir:** Misal kode `SNX-20261105-ABCD`, kru cukup mengetikkan `ABCD`.
   - **Tautan Tiket Lengkap:** Kru dapat menempelkan URL tiket pelanggan; sistem otomatis mengekstrak kodenya.
   - **Tombol Paste Clipboard:** Tekan tombol tempel untuk memasukkan teks dari clipboard secara instan.
   - **Pencarian Nama / WhatsApp:** Kru juga dapat mengetikkan nama penonton atau nomor telepon untuk mencari tiket.
3. Tekan tombol **"Cari"** atau tekan Enter.

---

### 3.5 Metode 3: Tab Daftar Hadir Sesi Realtime
1. Buka **Tab Daftar** (ikon daftar `=`).
2. Tab menampilkan seluruh penonton yang terdaftar pada sesi terpilih.
3. Dilengkapi filter cepat berbasis tombol kapsul:
   - **Semua Penonton**
   - **Belum Hadir (Pending)**
   - **Sudah Hadir**
4. Terdapat kolom pencarian instan (*client-side search*) yang menyaring nama penonton seketika tanpa me-refresh halaman.
5. Daftar diperbarui secara otomatis di latar belakang setiap 20 detik.

---

### 3.6 Memahami Kartu Verdict & Tombol Tandai Hadir
Setelah kode ditemukan atau QR terpindai, panel lembar bawah (*bottom sheet*) akan terangkat ke atas menampilkan kartu evaluasi tiket:

| Status Verdict | Warna Kartu | Arti & Tindakan yang Disarankan |
|---|---|---|
| **`siap_hadir`** | **Hijau** *(Emerald)* | **Tiket Valid & Lunas.** Tampilkan data penonton, jumlah orang, paket, serta pesanan camilan. Tekan tombol hijau **"Tandai hadir"** (56px). Panel tertutup otomatis dalam 2 detik dan suara nada sukses berbunyi. |
| **`sudah_hadir`** | **Kuning** *(Amber)* | **Tiket Sudah Digunakan.** Menampilkan nama staf yang memvalidasi sebelumnya beserta jam check-in (mencegah tiket dipakai dua kali oleh orang berbeda). |
| **`belum_bayar`** | **Merah** *(Danger)* | **Pemesanan Belum Lunas.** Arahkan penonton untuk menyelesaikan pembayaran terlebih dahulu sebelum diizinkan masuk. |
| **`diproses`** | **Kuning** *(Amber)* | **Bukti Bayar Belum Dikonfirmasi.** Minta admin kasir untuk memeriksa bukti pembayaran di panel admin. |
| **`sesi_lain`** | **Kuning** *(Amber)* | **Salah Sesi Tayang.** Tiket terdaftar untuk tanggal atau jam sesi yang berbeda. Kru dapat melakukan *Override Check-In* jika disetujui. |
| **`di_luar_jendela`** | **Kuning** *(Amber)* | **Terlalu Awal / Telat.** Tiket dipindai di luar batas waktu buka atau tutup check-in. Opsi override tersedia. |
| **`dibatalkan` / `kedaluwarsa`** | **Merah** *(Danger)* | **Tiket Hangus.** Pemesanan telah dibatalkan atau waktu pembayaran telah kedaluwarsa. |

---

### 3.7 Prosedur Override Check-In (Sesi Lain / Luar Jam)
1. Jika penonton tiba di sesi yang berbeda namun pengelola studio menyetujui penonton tersebut masuk (misalnya ada bangku kosong atau pergantian jadwal mendadak):
2. Kartu verdict kuning akan menyediakan tombol **"Tandai Hadir (Override)"**.
3. Kru mengetuk tombol tersebut, lalu mengisi alasan singkat pada modal konfirmasi in-app (misal: *"Dipindahkan ke sesi 19:00 karena sesi sebelumnya terlambat"*).
4. Sistem memproses check-in dan mencatat rekaman audit khusus dengan status `result: override` pada tabel `booking_checkins`.

---

### 3.8 Prosedur Pembatalan Kehadiran (Undo Check-In)
Jika kru salah mengetuk tombol hadir pada tiket orang lain:
1. Buka kembali tiket tersebut melalui Tab Kode atau Tab Daftar.
2. Di bagian bawah kartu rincian, klik tautan merah **"Batalkan kehadiran (Undo)"**.
3. Masukkan alasan pembatalan pada dialog modal (misal: *"Salah klik penonton"*).
4. Status booking seketika dipulihkan kembali ke status aktif sebelum hadir, dan peristiwa pembatalan dicatat secara transparan pada log audit.

---

## 4. ALUR MANAJEMEN OPERASIONAL ADMINISTRATOR

### 4.1 Dashboard Operasional Hari Ini (`/admin`)
- **Header Ringkasan:** Menampilkan total booking hari ini, jumlah penonton hadir, pendapatan harian, dan tombol pintas ke pemindai tiket.
- **Kartu Sesi Berjalan:** Setiap sesi tayang hari ini dilengkapi bar progres kehadiran visual (`Hadir X dari Y orang`) dengan warna dinamis (hijau jika sudah lengkap).
- **Notifikasi Polling Latar Belakang:** Admin menerima toast notifikasi otomatis setiap kali ada pemesanan baru yang masuk tanpa perlu me-refresh halaman web.

---

### 4.2 Verifikasi Bukti Pembayaran Transfer
1. Buka menu **"Booking"** (`/admin/booking`).
2. Filter daftar berdasarkan status **"Menunggu Konfirmasi"** (berwarna kuning).
3. Klik pada kode booking untuk membuka lembar detail (`/admin/booking/{booking}`).
4. Periksa gambar bukti transfer bank yang diunggah pelanggan:
   - Cocokkan jumlah nominal transfer dengan total harga tiket + jajan.
   - Cocokkan nama rekening pengirim atau kode berita transfer.
5. **Tindakan:**
   - **Konfirmasi Lunas:** Status berubah menjadi `terkonfirmasi`. E-tiket pelanggan aktif seketika dan QR code siap dipindai di pintu masuk.
   - **Tolak / Batalkan:** Jika bukti transfer palsu atau tidak terbaca, admin dapat membatalkan booking dengan menyertakan pesan penolakan.

---

### 4.3 Koordinasi Dapur & Penyiapan Jajan Penonton
1. Staf bagian dapur / bar snack membuka menu **"Siapkan Jajan"** (`/admin/jajanan/siapkan`).
2. Halaman menampilkan daftar pesanan makanan & minuman yang dikelompokkan per sesi tayang.
3. **Lencana Hadir:** Pesanan milik penonton yang tiketnya telah discan di pintu masuk akan ditandai dengan **lencana hijau "Hadir"**.
4. Kru dapur memprioritaskan pembuatan popcorn hangat dan penyajian minuman untuk penonton yang sudah berada di lokasi.
5. Setelah makanan diserahkan ke penonton, staf mengubah status pesanan jajan menjadi **"Selesai"**.

---

### 4.4 Manajemen Jadwal Tayang & Resolusi Kuorum
1. Buka menu **"Jadwal Tayang"** (`/admin/jadwal`).
2. **Generate Jadwal & Template Standar:**
   - Admin dapat membuat jadwal 14 hari ke depan secara instan menggunakan tombol **"Generate 14 hari ke depan"**.
   - Sistem secara otomatis mengacu pada template baku:
     - **Senin–Kamis (Weekday):** 3 sesi per hari (`13:00`, `16:00`, dan `19:00 WIB`).
     - **Jumat–Minggu & Hari Libur:** 4 sesi per hari (`09:00`, `13:00`, `16:00`, dan `19:00 WIB`).
     - Sesi malam selesai pada pukul `21:30 WIB` (durasi 150 menit), memberikan jeda istirahat dan pembersihan 30 menit antar sesi.
3. **Resolusi Kuorum Sesi:**
   - Standar bioskop privat menetapkan kuorum minimal penonton (misal minimal 3 atau 4 orang) agar sesi publik dapat diputar secara ekonomis.
   - Jika kuorum belum terpenuhi hingga batas waktu cutoff, sistem memberikan peringatan pada dashboard.
   - Admin dapat memilih: memperpanjang toleransi waktu, memindahkan penonton ke sesi berikutnya, atau membatalkan sesi dengan pengembalian dana.

---

### 4.5 Laporan Statistik & Sebaran Metode Check-In
1. Buka menu **"Laporan"** (`/admin/laporan`).
2. Menampilkan grafik pendapatan, okupansi studio, dan total tiket terjual.
3. **Seksi Khusus Tingkat Kehadiran & Sebaran Metode:**
   - Rasio Kehadiran: Penonton Hadir vs Belum Datang vs No-Show.
   - Sebaran Metode Check-In: Pemindaian Kamera QR, Input Kode Manual, Tab Daftar Tamu, dan Override Staf.
   - Laporan dapat diekspor ke format CSV / spreadsheet melalui tombol **"Ekspor Laporan"**.

---

## 5. ALUR KONFIGURASI & PENGATURAN SISTEM BIOSKOP

Pengaturan sistem dikelola melalui menu **"Pengaturan"** (`/admin/pengaturan`).

```
+-------------------------------------------------------------------------------+
|                       PANEL PENGATURAN BIOSKOP SYNEMAX                        |
+-------------------------------------------------------------------------------+
|  [ Operasional ]  [ Harga & Tarif ]  [ Tampilan ]  [ Identitas ]  [ Navbar ]  |
+-------------------------------------------------------------------------------+
```

### 5.1 Pengaturan Multi-Rekening Transfer & QRIS Toko
1. Buka tab **"Operasional"**.
2. **Repeater Rekening Transfer Bank:**
   - Tekan tombol **"+ Tambah Rekening"** untuk menambahkan opsi bank baru.
   - Masukkan: **Nama Bank / E-Wallet** (misal: *BCA*, *Mandiri*, *BRI*, *DANA*), **Nomor Rekening**, dan **Nama Pemilik Rekening**.
   - Admin dapat menambah atau menghapus rekening secara bebas. Data disimpan secara rapi dalam format JSON.
3. **Gambar QRIS Toko:**
   - Unggah gambar kode QRIS resmi bioskop (format PNG/JPG persegi resolusi minimal 800×800 px).
   - Masukkan nama merchant resmi dan petunjuk transfer.
   - Tersedia tombol hapus QRIS jika sewaktu-waktu merchant berganti.

---

### 5.2 Pengaturan Jendela Check-In & Suara Beep
1. Pada tab **"Operasional"**, gulir ke seksi **"Pengaturan Check-In & Pemindai Tiket"**:
   - **Buka Check-In Sebelum Sesi:** Pilih waktu buka pintu masuk (15, 30, 60 bawaan, 90, atau 120 menit sebelum jadwal tayang).
   - **Tutup Check-In:** Batas akhir penonton diperbolehkan check-in (15 menit, 30 menit, atau sampai film selesai).
   - **Suara Pemindai:** Aktifkan/nonaktifkan nada beep audio saat pemindaian sukses.

---

### 5.3 Kurasi Urutan Film & Banner Media
1. Buka tab **"Tampilan"**.
2. **Preferensi Urutan Film:**
   - Pilih opsi default: **Tahun Rilis Terbaru ke Terlama** (disarankan), **Jadwal Tayang Terdekat**, atau **Judul A-Z**.
3. **Banner Hero Carousel (`/admin/banners`):**
   - Unggah banner lanskap 16:9 (1920×1080 px) untuk promosi film blockbuster atau event nobar akbar.
   - Atur urutan banner dengan drag-and-drop atau nomor urut.

---

### 5.4 Manajemen Poster Massal & Antrean Pencocokan Manual
1. Buka menu **"Poster Massal"** (`/admin/films/posters`).
2. **Unggah Berkas Gambar / ZIP:**
   - Admin dapat memilih puluhan berkas gambar sekaligus (JPG, PNG, WebP) atau mengunggah satu berkas arsip ZIP (maksimal 50 MB).
   - Seluruh gambar otomatis dikompresi ke rasio 2:3 standar (600 × 900 px WebP) secara efisien tanpa membebani browser atau server.
3. **Pencocokan Nama Berkas Otomatis:**
   - Berkas yang nama filenya identik atau mirip dengan judul / slug film katalog akan langsung terpasang otomatis.
4. **Antrean Poster Belum Terpasang (Pencocokan Manual):**
   - Berkas gambar tangkapan layar (seperti `Screenshot ...`) atau berkas yang namanya berbeda dari katalog akan otomatis masuk ke antrean **"Poster Belum Terpasang"** di bagian bawah.
   - Pada kartu poster yang muncul di bawah tombol perbarui halaman:
     - Tampil pratinjau foto poster, nama berkas asli, resolusi, dan ukuran file.
     - Admin memilih film penerima melalui kotak pencarian judul film (`<x-ui.select>`).
     - Tekan tombol **"Pasangkan"** pada poster terkait, atau centang beberapa poster dan tekan tombol **"Pasangkan Semua yang Dipilih"** untuk memasangkan massal via jendela pratinjau.
     - Tersedia tombol **"Hapus Poster"** untuk membersihkan poster yang tidak diinginkan dari antrean.

---

## 6. PANDUAN PENGUJIAN FISIK & TROUBLESHOOTING LAPANGAN

### 6.1 Uji Coba Pemindai via Ponsel & Wi-Fi Lokal
Untuk menguji fitur pemindai kamera langsung menggunakan smartphone di jaringan lokal bersama komputer pengembang (Laravel Herd):
1. **Sambungkan ke Jaringan yang Sama:** Pastikan ponsel dan komputer terhubung ke jaringan Wi-Fi yang sama.
2. **Gunakan Fitur Herd Share (HTTPS Aman):**
   - Buka terminal dan jalankan perintah:
     ```bash
     herd share
     ```
   - Terminal akan memberikan tautan publik HTTPS sementara (misal: `https://synemax.test.herd.live`).
3. **Buka di Ponsel:**
   - Buka peramban di ponsel (Safari di iOS atau Chrome di Android), lalu buka tautan Herd Share tersebut.
   - Masuk sebagai admin (`/login`), lalu buka menu `/scan`.
   - Tekan **"Aktifkan kamera"**, setujui izin kamera, lalu arahkan ke QR e-tiket penonton di layar monitor.

---

### 6.2 Mengatasi Izin Kamera Terblokir & Konteks HTTPS
- **Kendala:** Kamera tidak menyala atau muncul tulisan *"Akses kamera memerlukan koneksi aman (HTTPS)"*.
- **Penyebab:** Peramban modern (khususnya Safari iOS dan Chrome Android) melarang akses perangkat keras kamera (`getUserMedia`) jika situs diakses melalui alamat IP HTTP biasa (seperti `http://192.168.1.15`).
- **Solusi Operasional:**
  1. Selalu gunakan protokol HTTPS (via domain resmi `https://synemax.web.id` atau via tunnel `herd share`).
  2. Jika kamera tetap tidak bisa digunakan pada perangkat tertentu, kru dapat langsung beralih ke **Tab Kode** untuk memasukkan 4 karakter akhir tiket secara manual tanpa hambatan.

---

### 6.3 Menjalankan Rangkaian Pengujian Otomatis
Bagi pengembang yang melakukan pembaruan kode, jalankan pengujian berikut sebelum merilis perubahan ke server:

```bash
# 1. Jalankan pengujian fitur backend (PHPUnit)
php artisan test --compact tests/Feature/SingleFlashAlertTest.php tests/Feature/TicketScannerTest.php tests/Feature/NewsArticleTest.php tests/Feature/PaymentThemeSyncTest.php tests/Feature/AdminSettingOperasionalTest.php

# 2. Jalankan audit antarmuka dan interaktivitas browser (Playwright)
node tests/ticket_scanner_browser_audit.mjs

# 3. Format seluruh kode PHP sesuai standar resmi Laravel Pint
vendor/bin/pint --format agent
```

---
*Dokumen panduan operasional ini disusun secara komprehensif untuk memastikan seluruh staf, pengembang, dan manajemen bioskop SynemaX memiliki acuan kerja yang baku dan profesional.*

