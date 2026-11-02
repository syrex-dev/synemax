<?php

namespace App\Console\Commands;

use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class FlushNotificationOutbox extends Command
{
    protected $signature = 'notifications:flush {--limit=20 : Jumlah maksimal pesan yang diproses}';

    protected $description = 'Kirim antrean notifikasi outbox (Telegram) yang tertunda ke server tujuan';

    public function handle(TelegramNotificationService $telegram): int
    {
        $limit = (int) $this->option('limit');
        $count = $telegram->flush($limit);

        if ($count > 0) {
            $this->info("{$count} notifikasi berhasil terkirim.");
        } else {
            $this->line('Tidak ada notifikasi tertunda untuk dikirim.');
        }

        return self::SUCCESS;
    }
}
