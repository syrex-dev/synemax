<?php

namespace App\Console\Commands;

use App\Models\BotFeedback;
use App\Services\CinemaSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupBotFeedback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feedback:cleanup {--days= : Override jumlah hari retensi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus data feedback bot dan riwayat komplain yang melebihi batas waktu retensi';

    /**
     * Execute the console command.
     */
    public function handle(CinemaSettings $settings): int
    {
        $daysOption = $this->option('days');

        if ($daysOption !== null) {
            $days = (int) $daysOption;
        } else {
            $days = $settings->feedbackRetentionDays();
        }

        if ($days <= 0) {
            $this->info('Pembersihan otomatis data feedback dinonaktifkan (retensi = 0 hari).');

            return Command::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $deleted = BotFeedback::where('created_at', '<', $cutoff)->delete();

        $message = "Berhasil membersihkan {$deleted} data feedback yang lebih lama dari {$days} hari (sebelum {$cutoff->toDateTimeString()}).";
        $this->info($message);

        if ($deleted > 0) {
            Log::info("[FeedbackCleanup] {$message}");
        }

        return Command::SUCCESS;
    }
}
