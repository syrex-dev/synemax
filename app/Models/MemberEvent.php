<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'user_id',
    'event',
    'event_type',
    'meta',
    'payload',
    'ip',
    'created_at',
])]
class MemberEvent extends Model
{
    public $timestamps = false;

    protected $casts = [
        'meta' => 'array',
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (MemberEvent $event): void {
            if (empty($event->event) && ! empty($event->event_type)) {
                $event->event = $event->event_type;
            } elseif (! empty($event->event) && empty($event->event_type)) {
                $event->event_type = $event->event;
            }

            if (empty($event->meta) && ! empty($event->payload)) {
                $event->meta = $event->payload;
            } elseif (! empty($event->meta) && empty($event->payload)) {
                $event->payload = $event->meta;
            }

            if (empty($event->created_at)) {
                $event->created_at = now();
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
