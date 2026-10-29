<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance settings table with 'group' column
        if (Schema::hasTable('settings') && ! Schema::hasColumn('settings', 'group')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('group', 50)->default('general')->after('key')->index();
            });
        }

        // 2. Enhance films table with slug and release_year
        Schema::table('films', function (Blueprint $table) {
            if (! Schema::hasColumn('films', 'slug')) {
                $table->string('slug')->nullable()->after('title');
            }
            if (! Schema::hasColumn('films', 'release_year')) {
                $table->unsignedSmallInteger('release_year')->nullable()->after('duration_minutes');
            }
        });

        // 3. Enhance banners table with target_type, target_id, and mobile_image_path
        if (Schema::hasTable('banners')) {
            Schema::table('banners', function (Blueprint $table) {
                if (! Schema::hasColumn('banners', 'target_type')) {
                    $table->string('target_type', 30)->default('none')->after('cta_url');
                }
                if (! Schema::hasColumn('banners', 'target_id')) {
                    $table->string('target_id', 100)->nullable()->after('target_type');
                }
                if (! Schema::hasColumn('banners', 'mobile_image_path')) {
                    $table->string('mobile_image_path')->nullable()->after('image_path');
                }
            });
        }

        // 4. Create promos table
        if (! Schema::hasTable('promos')) {
            Schema::create('promos', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('image_path')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->text('description')->nullable();
                $table->text('terms_conditions')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->integer('sort_order')->default(0);
                $table->softDeletes();
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promos');

        if (Schema::hasTable('banners')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->dropColumn(['target_type', 'target_id', 'mobile_image_path']);
            });
        }

        Schema::table('films', function (Blueprint $table) {
            $table->dropColumn(['slug', 'release_year']);
        });

        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'group')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('group');
            });
        }
    }
};
