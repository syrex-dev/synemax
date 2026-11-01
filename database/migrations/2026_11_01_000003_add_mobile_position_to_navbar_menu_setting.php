<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $setting = DB::table('settings')->where('key', 'navbar_menu')->first();
        if (! $setting || empty($setting->value)) {
            return;
        }

        $items = json_decode($setting->value, true);
        if (! is_array($items)) {
            return;
        }

        $updated = false;
        $bottomBarCount = 0;

        foreach ($items as &$item) {
            if (! isset($item['mobile_position'])) {
                $key = $item['key'] ?? $item['id'] ?? '';
                $isBottom = in_array($key, ['home', 'schedule', 'film', 'lookup', 'booking_lookup'], true);
                if ($isBottom && $bottomBarCount < 4) {
                    $item['mobile_position'] = 'bottom_bar';
                    $bottomBarCount++;
                } else {
                    $item['mobile_position'] = 'menu';
                }
                $updated = true;
            }
        }
        unset($item);

        if ($updated) {
            DB::table('settings')
                ->where('key', 'navbar_menu')
                ->update([
                    'value' => json_encode($items),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Additive migration: preserve structure
    }
};
