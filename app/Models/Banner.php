<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'type',
    'title',
    'subtitle',
    'badge',
    'image_path',
    'mobile_image_path',
    'cta_text',
    'cta_url',
    'target_type',
    'target_id',
    'sort_order',
    'is_active',
])]
class Banner extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function resolvedUrl(): ?string
    {
        if ($this->target_type === 'none') {
            return null;
        }

        if ($this->target_type === 'film' && filled($this->target_id)) {
            return url('/film/'.$this->target_id);
        }

        if ($this->target_type === 'promo' && filled($this->target_id)) {
            return url('/promo/'.$this->target_id);
        }

        if ($this->target_type === 'nobar' && filled($this->target_id)) {
            return url('/nobar/'.$this->target_id);
        }

        if (filled($this->cta_url)) {
            return $this->cta_url;
        }

        return null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
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

        return Storage::url($this->image_path);
    }
}
