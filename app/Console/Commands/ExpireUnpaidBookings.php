<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid';

    protected $description = 'Tandai booking menunggu_pembayaran yang lewat batas waktu menjadi kedaluwarsa';

    public function handle(BookingService $bookingService): int
    {
        $count = $bookingService->expireUnpaidBookings();
        $this->info("{$count} booking telah kedaluwarsa.");

        return self::SUCCESS;
    }
}
