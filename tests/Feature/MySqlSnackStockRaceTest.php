<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MySqlSnackStockRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_confirmations_cannot_sell_the_last_numeric_stock_item_twice(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! function_exists('proc_open')) {
            $this->markTestSkipped('Set DB_CONNECTION=mysql to run the true concurrent-stock test.');
        }

        $film = Film::query()->create([
            'title' => 'Film stok uji',
            'duration_minutes' => 100,
            'genre' => 'Komedi',
            'age_rating' => 'SU',
            'synopsis' => 'Film fiktif untuk test stok.',
            'is_active' => true,
        ]);
        $category = MenuCategory::query()->create([
            'name' => 'Kategori stok serentak',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Sosis stok terakhir',
            'price' => 5000,
            'prep_minutes' => 2,
            'stock_mode' => 'angka',
            'stock_qty' => 1,
            'is_available' => true,
            'is_active' => true,
        ]);
        $session = Session::query()->create([
            'session_date' => CarbonImmutable::now(config('app.timezone'))->addDay()->toDateString(),
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
        $bookings = collect(range(1, 2))->map(function (int $index) use ($film, $menuItem, $session): Booking {
            $booking = Booking::query()->create([
                'code' => 'HC-'.now()->format('ymd').'-STOCK'.$index,
                'session_id' => $session->id,
                'film_id' => $film->id,
                'customer_name' => 'Pemesan Stok '.$index,
                'whatsapp' => '62812345678'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'pax' => 1,
                'seats_reserved' => 1,
                'booking_type' => 'per_orang',
                'total_price' => 10000,
                'ticket_total' => 5000,
                'snack_total' => 5000,
                'payment_method' => 'di_tempat',
                'status' => 'menunggu_konfirmasi',
            ]);
            $booking->items()->create([
                'menu_item_id' => $menuItem->id,
                'name_snapshot' => $menuItem->name,
                'price_snapshot' => $menuItem->price,
                'qty' => 1,
                'status' => 'baru',
            ]);

            return $booking;
        });

        DB::commit();

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'home-cinema-stock-race-'.bin2hex(random_bytes(8));
        mkdir($directory);
        $barrier = $directory.DIRECTORY_SEPARATOR.'start';
        $readyFiles = [
            $directory.DIRECTORY_SEPARATOR.'ready-1',
            $directory.DIRECTORY_SEPARATOR.'ready-2',
        ];
        $processes = [];

        try {
            foreach ($bookings as $index => $booking) {
                $script = $this->workerScript($booking->id, $readyFiles[$index], $barrier);
                $process = new Process([PHP_BINARY, '-r', $script], base_path());
                $process->start();
                $processes[] = $process;
            }

            $deadline = microtime(true) + 15;

            while ((! file_exists($readyFiles[0]) || ! file_exists($readyFiles[1])) && microtime(true) < $deadline) {
                usleep(10000);
            }

            $this->assertFileExists($readyFiles[0], 'First stock worker did not reach the concurrency barrier.');
            $this->assertFileExists($readyFiles[1], 'Second stock worker did not reach the concurrency barrier.');
            file_put_contents($barrier, 'go');

            foreach ($processes as $process) {
                $process->wait();
                $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
            }

            $outcomes = array_map(fn (Process $process): string => trim($process->getOutput()), $processes);
            sort($outcomes);
            $this->assertSame(['CONFIRMED', 'UNAVAILABLE'], $outcomes);
            $this->assertSame(0, $menuItem->fresh()->stock_qty);
            $this->assertSame(
                1,
                Booking::query()->whereIn('id', $bookings->pluck('id'))->where('status', 'terkonfirmasi')->count(),
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
            BookingItem::query()->whereIn('booking_id', $bookings->pluck('id'))->delete();
            Booking::query()->whereIn('id', $bookings->pluck('id'))->delete();
            Session::query()->whereKey($session->id)->delete();
            MenuItem::query()->whereKey($menuItem->id)->delete();
            MenuCategory::query()->whereKey($category->id)->delete();
            Film::query()->whereKey($film->id)->delete();

            if (DB::transactionLevel() === 0) {
                DB::beginTransaction();
            }
        }
    }

    private function workerScript(int $bookingId, string $readyFile, string $barrier): string
    {
        $basePath = var_export(base_path(), true);
        $readyFile = var_export($readyFile, true);
        $barrier = var_export($barrier, true);

        return <<<PHP
            \$app = require {$basePath}.'/bootstrap/app.php';
            \$app->make(\\Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
            \\Illuminate\\Support\\Facades\\DB::purge();
            file_put_contents({$readyFile}, 'ready');
            while (! file_exists({$barrier})) { usleep(10000); }

            try {
                \\Illuminate\\Support\\Facades\\DB::transaction(function () use ({$bookingId}): void {
                    \$booking = \\App\\Models\\Booking::query()->lockForUpdate()->findOrFail({$bookingId});
                    app(\\App\\Services\\SnackOrderService::class)->confirmStock(\$booking);
                    \$booking->update(['status' => 'terkonfirmasi']);
                }, attempts: 3);
                echo 'CONFIRMED';
            } catch (\\Illuminate\\Validation\\ValidationException) {
                echo 'UNAVAILABLE';
            }
            PHP;
    }
}
