<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['blocked_date', 'scope_key', 'session_id', 'reason'])]
class BlockedDate extends Model
{
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
