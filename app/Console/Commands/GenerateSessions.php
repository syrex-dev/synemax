<?php

namespace App\Console\Commands;

use App\Services\SessionGenerationService;
use Illuminate\Console\Command;

class GenerateSessions extends Command
{
    protected $signature = 'sessions:generate {--days=14 : Jumlah hari yang akan dibuat}';

    protected $description = 'Buat sesi menonton dari template jadwal yang aktif';

    public function handle(SessionGenerationService $generator): int
    {
        $createdCount = $generator->generateDays((int) $this->option('days'));

        $this->components->info("Selesai. {$createdCount} sesi baru dibuat.");

        return self::SUCCESS;
    }
}
