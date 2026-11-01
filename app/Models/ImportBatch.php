<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'filename',
        'total_rows',
        'imported_count',
        'skipped_count',
        'failed_count',
        'status',
        'failed_rows',
        'user_id',
        'rolled_back_at',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'imported_count' => 'integer',
            'skipped_count' => 'integer',
            'failed_count' => 'integer',
            'failed_rows' => 'array',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function films(): HasMany
    {
        return $this->hasMany(Film::class, 'import_batch_id');
    }

    public function isRolledBack(): bool
    {
        return $this->rolled_back_at !== null;
    }

    public function getBatchUuidAttribute(): string
    {
        return (string) $this->id;
    }

    public function getSuccessfulRowsAttribute(): int
    {
        return (int) $this->imported_count;
    }

    public function getFailedRowsAttribute(): int
    {
        return (int) $this->skipped_count;
    }

    public function getCsvFilenameAttribute(): string
    {
        return (string) $this->filename;
    }
}
