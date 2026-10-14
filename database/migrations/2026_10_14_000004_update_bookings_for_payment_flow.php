<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('payment_expires_at')->nullable()->index()->after('payment_proof_path');
            $table->timestamp('payment_proof_uploaded_at')->nullable()->after('payment_expires_at');
            $table->text('proof_rejection_note')->nullable()->after('payment_proof_uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['payment_expires_at']);
            $table->dropColumn([
                'payment_expires_at',
                'payment_proof_uploaded_at',
                'proof_rejection_note',
            ]);
        });
    }
};
