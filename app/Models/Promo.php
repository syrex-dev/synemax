<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Promo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'image_path',
        'start_date',
        'end_date',
        'description',
        'terms_conditions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Promo $promo) {
            if (empty($promo->slug)) {
                $baseSlug = Str::slug($promo->title);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.(++$counter);
                }
                $promo->slug = $slug;
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', Carbon::today());
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
    }

    public function isExpired(): bool
    {
        return $this->end_date !== null && $this->end_date->isPast() && ! $this->end_date->isToday();
    }

    public function hasImage(): bool
    {
        return ! empty($this->image_path);
    }

    public function imageUrl(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://') || str_starts_with($this->image_path, '/')) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function periodFormatted(): string
    {
        if (! $this->start_date && ! $this->end_date) {
            return 'Berlaku setiap hari';
        }

        if ($this->start_date && $this->end_date) {
            return $this->start_date->translatedFormat('d M Y').' – '.$this->end_date->translatedFormat('d M Y');
        }

        if ($this->end_date) {
            return 'Hingga '.$this->end_date->translatedFormat('d M Y');
        }

        return 'Mulai '.$this->start_date->translatedFormat('d M Y');
    }
}
