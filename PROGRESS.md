# Progress Proyek SynemaX

Dokumen ini mencatat status pengerjaan fitur dan perbaikan agar progres tetap transparan dan dapat dilanjutkan dengan aman kapan saja.

## Status Ringkas
- **Tahap Audit & Checkpoint**: SELESAI (`commit: 35fda5d` - Checkpoint: alur pembayaran dan tiket)
- **Tahap 1 (C1: Private vs Per Orang)**: SELESAI (`commit: ce83a03`)
- **Tahap 2 (C2: Halaman Pembayaran & Perbaikan QRIS)**: SELESAI
- **Tahap 3 (Checklist 3: Fitur Info & Nobar)**: MENUNGGU PERSETUJUAN MANUAL SETELAH TAHAP 1-2
- **Tahap 4 (Testing & Verifikasi Visual)**: MENUNGGU

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

### Tahap 3: Fitur Info & Nobar (Menunggu Persetujuan Setelah Tahap 1 & 2)
- [ ] Migrasi & Model `NobarEvent` (skema lengkap: `kickoff_at`, `tim_a`, `tim_b`, status Berlangsung/Selesai/Akan datang, dll.) & `NewsArticle`.
- [ ] Sanitasi Markdown menggunakan `Str::markdown` dengan `html_input = 'strip'` dan `allow_unsafe_links = false`, rel `noopener nofollow`.
- [ ] Upload gambar & penanganan WebP/fallback, rute gambar publik aman tanpa ketergantungan `storage:link`.
- [ ] Halaman mandiri detail acara (.ics, share WA, maps) & detail berita dengan Open Graph lengkap.
- [ ] Admin Info & Nobar: CRUD, pratinjau sebelum terbit, tunda/batalkan.
- [ ] Toggle pengaturan "Tampilkan Info & Nobar" di admin.
- [ ] Bagian Nobar Gratis & Berita Terbaru di beranda publik.
- [ ] Seeder generik nobar bola tanpa merek dagang komersial berhak cipta.

---

## Keputusan Penting
1. **Aturan Migrasi**: Migrasi `2026_10_14_000004_update_bookings_for_payment_flow` sudah tercatat `Ran` (Batch 4), sehingga tidak diubah langsung. Migrasi baru dibuat untuk tabel tambahan.
2. **Derivasi Flag `is_private`**: Flag `is_private` pada sesi disinkronkan secara konsisten setiap kali siklus hidup booking berubah (create, cancel, expire, reject, restore).
3. **Endpoint Ketersediaan**: Dibuat sebagai endpoint publik ringan khusus angka dan boolean status tanpa mengekspos identitas pelanggan, dengan proteksi cache `no-store` dan throttling.

---

## Perintah Yang Perlu Dijalankan Pengguna
*(Saat ini belum ada perintah khusus yang perlu dijalankan. Migrasi baru akan diinformasikan pada Tahap 3)*
