<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nobar_events', function (Blueprint $table) {
            $table->unsignedInteger('poster_width')->nullable()->after('poster_path');
            $table->unsignedInteger('poster_height')->nullable()->after('poster_width');
            $table->string('poster_alt')->nullable()->after('poster_height');
            $table->string('poster_og_path')->nullable()->after('poster_alt');
        });

        // Data migration for existing events with posters
        $disk = Storage::disk('local');
        $events = DB::table('nobar_events')->whereNotNull('poster_path')->get(['id', 'poster_path']);

        foreach ($events as $event) {
            if ($event->poster_path && $disk->exists($event->poster_path)) {
                $fullPath = $disk->path($event->poster_path);
                $size = @getimagesize($fullPath);
                if ($size && $size[0] > 0 && $size[1] > 0) {
                    DB::table('nobar_events')->where('id', $event->id)->update([
                        'poster_width' => $size[0],
                        'poster_height' => $size[1],
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('nobar_events', function (Blueprint $table) {
            $table->dropColumn(['poster_width', 'poster_height', 'poster_alt', 'poster_og_path']);
        });
    }
};
