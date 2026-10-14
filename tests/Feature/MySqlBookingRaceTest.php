<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MySqlBookingRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_mysql_processes_competing_for_the_last_seat_cannot_overbook(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! function_exists('proc_open')) {
            $this->markTestSkipped('Set DB_CONNECTION=mysql to run the true concurrent-booking test.');
        }

        $film = Film::query()->create([
            'title' => 'Film uji balapan',
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Film fiktif untuk pengujian transaksi.',
            'is_active' => true,
        ]);
        $date = CarbonImmutable::now(config('app.timezone'))->addDay()->toDateString();
        $session = Session::query()->create([
            'session_date' => $date,
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
        Booking::query()->create([
            'code' => 'HC-'.CarbonImmutable::parse($date)->format('ymd').'-SEAT05',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Pemesan Awal',
            'whatsapp' => '6281234567890',
            'pax' => 5,
            'seats_reserved' => 5,
            'booking_type' => 'per_orang',
            'total_price' => 25000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);

        DB::commit();

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'home-cinema-race-'.bin2hex(random_bytes(8));
        mkdir($directory);
        $barrier = $directory.DIRECTORY_SEPARATOR.'start';
        $readyFiles = [
            $directory.DIRECTORY_SEPARATOR.'ready-1',
            $directory.DIRECTORY_SEPARATOR.'ready-2',
        ];
        $processes = [];

        try {
            foreach ($readyFiles as $index => $readyFile) {
                $whatsapp = $index === 0 ? '6281111111111' : '6282222222222';
                $script = $this->workerScript($session->id, $film->id, $whatsapp, $readyFile, $barrier);
                $process = new Process([PHP_BINARY, '-r', $script], base_path());
                $process->start();
                $processes[] = $process;
            }

            $deadline = microtime(true) + 15;

            while ((! file_exists($readyFiles[0]) || ! file_exists($readyFiles[1])) && microtime(true) < $deadline) {
                usleep(10000);
            }

            $this->assertFileExists($readyFiles[0], 'First worker did not reach the concurrency barrier.');
            $this->assertFileExists($readyFiles[1], 'Second worker did not reach the concurrency barrier.');
            file_put_contents($barrier, 'go');

            foreach ($processes as $process) {
                $process->wait();
                $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            }

            $outcomes = array_map(fn (Process $process): string => trim($process->getOutput()), $processes);
            sort($outcomes);
            $this->assertSame(['BOOKED', 'FULL'], $outcomes);
            $this->assertSame(
                6,
                (int) Booking::query()->where('session_id', $session->id)->sum('seats_reserved'),
            );
        } finally {
            file_put_contents($barrier, 'go');

            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }

            foreach ([$barrier, ...$readyFiles] as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }

            rmdir($directory);
            Booking::query()->where('session_id', $session->id)->delete();
            Session::query()->whereKey($session->id)->delete();
            Film::query()->whereKey($film->id)->delete();

            if (DB::transactionLevel() === 0) {
                DB::beginTransaction();
            }
        }
    }

    private function workerScript(int $sessionId, int $filmId, string $whatsapp, string $readyFile, string $barrier): string
    {
        $basePath = var_export(base_path(), true);
        $readyFile = var_export($readyFile, true);
        $barrier = var_export($barrier, true);
        $whatsapp = var_export($whatsapp, true);

        return <<<PHP
            \$app = require {$basePath}.'/bootstrap/app.php';
            \$app->make(\\Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
            \\Illuminate\\Support\\Facades\\DB::purge();
            file_put_contents({$readyFile}, 'ready');
            while (! file_exists({$barrier})) { usleep(10000); }

            try {
                app(\\App\\Services\\BookingService::class)->create(
                    \\App\\Models\\Session::query()->findOrFail({$sessionId}),
                    [
                        'customer_name' => 'Pemesan Serentak',
                        'whatsapp' => {$whatsapp},
                        'pax' => 1,
                        'booking_type' => 'per_orang',
                        'film_id' => {$filmId},
                        'payment_method' => 'di_tempat',
                    ],
                );
                echo 'BOOKED';
            } catch (\\Illuminate\\Validation\\ValidationException) {
                echo 'FULL';
            }
            PHP;
    }
}
