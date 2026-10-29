<?php

namespace App\Console\Commands;

use App\Services\SessionQuorumService;
use Illuminate\Console\Command;

class ResolveSessionQuorumCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:resolve-quorum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluasi sesi tayang untuk status kuorum, jendela kesepakatan, dan pembatalan otomatis batas konfirmasi.';

    /**
     * Execute the console command.
     */
    public function handle(SessionQuorumService $service): int
    {
        $result = $service->resolveAll();

        $this->info(sprintf(
            'Selesai: %d sesi dibatalkan (%d booking diproses), %d sesi dalam jendela kesepakatan.',
            $result['cancelled_sessions'],
            $result['cancelled_bookings'],
            $result['agreement_sessions']
        ));

        return self::SUCCESS;
    }
}
