<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class CleanTestBookingsCommand extends Command
{
    protected $signature = 'bookings:clean-test-data {--reset-visits : Hapus juga riwayat stempel kunjungan member testing} {--force : Jalankan pembersihan tanpa konfirmasi prompt}';

    protected $description = 'Bersihkan seluruh riwayat booking, pesanan jajan, bukti bayar testing, dan nolkan laporan keuangan';

    public function handle(BookingService $bookingService): int
    {
        $resetVisits = (bool) $this->option('reset-visits');
        $force = (bool) $this->option('force');

        if (! $force && ! $this->confirm('Apakah Anda yakin ingin menghapus seluruh data booking dan laporan keuangan testing? Tindakan ini tidak dapat dibatalkan.')) {
            $this->warn('Pembersihan data dibatalkan.');

            return self::SUCCESS;
        }

        $this->info('Membersihkan data testing...');
        $result = $bookingService->cleanTestData($resetVisits);

        $this->info('Berhasil dibersihkan:');
        $this->line("- {$result['deleted_bookings']} data booking dihapus.");
        $this->line("- {$result['deleted_items']} data pesanan jajan dihapus.");
        $this->line("- {$result['deleted_checkins']} data riwayat kehadiran dihapus.");
        if ($resetVisits) {
            $this->line("- {$result['deleted_visits']} data kunjungan member testing dihapus.");
        }
        $this->line("- {$result['resynced_sessions']} sesi tayang telah disinkronkan status keterisiannya.");
        $this->info('Laporan keuangan kini telah bersih (Rp 0) dan siap untuk transaksi riil!');

        return self::SUCCESS;
    }
}
