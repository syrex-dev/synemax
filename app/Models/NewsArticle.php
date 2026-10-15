<?php

namespace App\Models;

use App\Support\SafeMarkdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

class NewsArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'judul',
        'slug',
        'kategori',
        'ringkasan',
        'konten',
        'cover_path',
        'status_publikasi',
        'tanggal_terbit',
        'is_pinned',
    ];

    protected $casts = [
        'tanggal_terbit' => 'datetime',
        'is_pinned' => 'boolean',
    ];

    public function coverUrl(): ?string
    {
        if (blank($this->cover_path)) {
            return null;
        }

        return route('media.article', ['filename' => basename($this->cover_path)]);
    }

    public function kontenHtml(): HtmlString
    {
        return SafeMarkdown::toHtml($this->konten);
    }

    public function isScheduled(): bool
    {
        return $this->status_publikasi === 'terbit'
            && $this->tanggal_terbit !== null
            && $this->tanggal_terbit->isFuture();
    }

    public function isLive(): bool
    {
        return $this->status_publikasi === 'terbit'
            && ($this->tanggal_terbit === null || $this->tanggal_terbit->isPast() || $this->tanggal_terbit->equalTo(now()));
    }

    public function shareWhatsAppUrl(): string
    {
        $url = route('articles.show', ['slug' => $this->slug]);
        $text = "{$this->judul}\n\n{$this->ringkasan}\n\nBaca selengkapnya: {$url}";

        return 'https://wa.me/?text='.rawurlencode($text);
    }

    public function kategoriBadgeClasses(): string
    {
        return match ($this->kategori) {
            'Promo' => 'border border-warning/40 bg-warning/15 text-warning font-bold',
            'Pengumuman' => 'border border-accent/40 bg-accent/15 text-accent font-semibold',
            default => 'border border-border bg-surface-2 text-text',
        };
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status_publikasi', 'terbit')
            ->where(function (Builder $sub): void {
                $sub->whereNull('tanggal_terbit')
                    ->orWhere('tanggal_terbit', '<=', now());
            });
    }

    public function scopePublicDisplay(Builder $query): Builder
    {
        return $query->published()
            ->orderByDesc('is_pinned')
            ->orderByRaw('COALESCE(tanggal_terbit, created_at) DESC');
    }
}
