<?php

namespace App\Models;

use App\Services\CinemaSettings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget(CinemaSettings::CACHE_KEY);
        });

        static::deleted(function (): void {
            Cache::forget(CinemaSettings::CACHE_KEY);
        });
    }
}
