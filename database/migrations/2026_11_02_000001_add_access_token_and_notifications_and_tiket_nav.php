<?php

use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom access_token_hash di tabel bookings jika belum ada
        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'access_token_hash')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('access_token_hash', 64)->nullable()->index();
            });
        }

        // 2. Buat tabel notification_outbox jika belum ada
        if (! Schema::hasTable('notification_outbox')) {
            Schema::create('notification_outbox', function (Blueprint $table) {
                $table->id();
                $table->string('channel', 30)->default('telegram');
                $table->string('event', 50)->index();
                $table->unsignedBigInteger('booking_id')->nullable()->index();
                $table->text('payload');
                $table->string('status', 20)->default('pending')->index();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->timestamp('available_at')->nullable()->index();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->unique(['channel', 'event', 'booking_id'], 'outbox_unique_msg');
            });
        }

        // 3. Perbarui item navigasi Navbar: HANYA bila label masih default "Cek booking" / "Booking"
        try {
            $rawMenu = Setting::where('key', 'navbar_menu')->value('value');
            if ($rawMenu) {
                $menu = json_decode($rawMenu, true);
                if (is_array($menu)) {
                    $changed = false;
                    foreach ($menu as &$item) {
                        $label = strtolower(trim($item['label'] ?? ''));
                        $url = trim($item['url'] ?? '');
                        if (in_array($label, ['cek booking', 'booking'], true) || $url === '/cek-booking') {
                            if (in_array($label, ['cek booking', 'booking'], true)) {
                                $item['label'] = 'Tiket';
                            }
                            if ($url === '/cek-booking') {
                                $item['url'] = '/tiket';
                            }
                            if (($item['id'] ?? '') === 'booking_lookup') {
                                $item['id'] = 'tickets';
                            }
                            $changed = true;
                        }
                    }
                    unset($item);
                    if ($changed) {
                        Setting::updateOrCreate(
                            ['key' => 'navbar_menu'],
                            ['value' => json_encode($menu), 'group' => 'navbar']
                        );
                    }
                }
            }
        } catch (Throwable) {
            // Abaikan bila tabel setting belum ada
        }

        // 4. Backfill access_token_hash untuk booking yang ada agar memiliki token acak
        try {
            Booking::query()->whereNull('access_token_hash')->chunk(100, function ($bookings) {
                foreach ($bookings as $b) {
                    $b->update(['access_token_hash' => hash('sha256', Str::random(32))]);
                }
            });
        } catch (Throwable) {
            // Abaikan jika belum siap
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_outbox')) {
            Schema::dropIfExists('notification_outbox');
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'access_token_hash')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('access_token_hash');
            });
        }
    }
};
