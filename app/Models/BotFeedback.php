<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotFeedback extends Model
{
    use HasFactory;

    protected $table = 'bot_feedbacks';

    protected $fillable = [
        'telegram_user_id',
        'telegram_username',
        'sender_name',
        'category',
        'booking_code',
        'booking_id',
        'message',
        'bot_response',
        'status',
        'admin_note',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'ISSUE_REPORT' => 'Lapor Keluhan / Kendala',
            'ORDER_INQUIRY' => 'Cek Status Booking',
            'FEEDBACK' => 'Masukan / Ulasan',
            default => 'Bantuan Umum',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'AUTO_RESOLVED' => 'Ditangani Bot Otomatis',
            'FLAGGED' => 'Perlu Perhatian Admin',
            'RESOLVED' => 'Selesai',
            default => $this->status,
        };
    }

    public function ticketCode(): string
    {
        return 'TKT-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
