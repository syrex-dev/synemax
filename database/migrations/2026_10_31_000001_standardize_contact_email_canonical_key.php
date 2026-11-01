<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $contactEmail = Setting::where('key', 'contact_email')->value('value');

        // Only copy from cinema_email if canonical key contact_email is missing or empty
        if (empty(trim((string) $contactEmail))) {
            $legacyEmail = Setting::where('key', 'cinema_email')->value('value');
            if (! empty(trim((string) $legacyEmail))) {
                Setting::updateOrCreate(
                    ['key' => 'contact_email'],
                    ['value' => trim((string) $legacyEmail), 'group' => 'kontak']
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Additive migration: preserve data
    }
};
