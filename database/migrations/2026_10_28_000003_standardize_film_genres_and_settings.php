<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Standardize film genres in films table
        $replacements = [
            'Animasi Keluarga' => 'Animasi, Keluarga',
            'Drama keluarga' => 'Drama, Keluarga',
            'Aksi Fantasi' => 'Aksi, Fantasi',
            'Komedi petualangan' => 'Komedi, Petualangan',
            'Petualangan Maritim' => 'Petualangan',
            'Romansa' => 'Romantis',
        ];

        foreach ($replacements as $old => $new) {
            DB::table('films')
                ->where('genre', $old)
                ->update(['genre' => $new]);
        }

        // 2. Insert or update default settings for low attendance warning window & minimum viewers
        $now = now();
        $defaults = [
            [
                'key' => 'low_attendance_warning_window',
                'group' => 'operational',
                'value' => '72',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'minimum_public_viewers',
                'group' => 'operational',
                'value' => '3',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($defaults as $setting) {
            $exists = DB::table('settings')->where('key', $setting['key'])->exists();
            if (! $exists) {
                DB::table('settings')->insert($setting);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive rollback
    }
};
