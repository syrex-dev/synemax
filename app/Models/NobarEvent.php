<?php

namespace App\Models;

use App\Support\SafeMarkdown;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

class NobarEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'judul',
        'slug',
        'kompetisi',
        'tim_a',
        'tim_b',
        'kickoff_at',
        'durasi_menit',
        'lokasi_nama',
        'alamat_patokan',
        'maps_url',
        'is_free',
        'harga_info',
        'catatan',
        'poster_path',
        'status_publikasi',
        'is_pinned',
        'status_acara',
        'alasan_status',
    ];

    protected $casts = [
        'kickoff_at' => 'datetime',
        'durasi_menit' => 'integer',
        'is_free' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    public function endsAt(): Carbon
    {
        return $this->kickoff_at->copy()->addMinutes($this->durasi_menit);
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status_acara === 'dibatalkan') {
            return 'Dibatalkan';
        }

        if ($this->status_acara === 'ditunda') {
            return 'Ditunda';
        }

        $now = now();
        $endsAt = $this->endsAt();

        if ($now->greaterThan($endsAt)) {
            return 'Selesai';
        }

        if ($now->greaterThanOrEqualTo($this->kickoff_at) && $now->lessThanOrEqualTo($endsAt)) {
            return 'Berlangsung';
        }

        if ($this->kickoff_at->isToday()) {
            return 'Hari ini';
        }

        return 'Akan datang';
    }

    public function badgeClasses(): string
    {
        return match ($this->display_status) {
            'Berlangsung' => 'border border-success/40 bg-success/15 text-success',
            'Hari ini' => 'border border-warning/40 bg-warning/15 text-warning font-bold',
            'Akan datang' => 'border border-accent/40 bg-accent/15 text-accent font-semibold',
            'Ditunda' => 'border border-warning/40 bg-warning/10 text-warning',
            'Dibatalkan' => 'border border-danger/40 bg-danger/10 text-danger',
            default => 'border border-border bg-surface-2 text-text-muted',
        };
    }

    public function posterUrl(): ?string
    {
        if (blank($this->poster_path)) {
            return null;
        }

        return route('media.nobar', ['filename' => basename($this->poster_path)]);
    }

    public function catatanHtml(): HtmlString
    {
        return SafeMarkdown::toHtml($this->catatan);
    }

    public function vsTitle(): string
    {
        return filled($this->tim_b)
            ? "{$this->tim_a} vs {$this->tim_b}"
            : $this->tim_a;
    }

    public function shareWhatsAppUrl(): string
    {
        $match = $this->vsTitle();
        $dateStr = $this->kickoff_at->translatedFormat('l, d M Y · H:i').' WIB';
        $url = route('nobar.show', ['slug' => $this->slug]);

        $text = "Ayo nobar {$match} di SynemaX Home Cinema!\n"
            ."📅 {$dateStr}\n"
            ."📍 {$this->lokasi_nama} ({$this->alamat_patokan})\n"
            .($this->is_free ? "🎟️ Tiket Masuk: Gratis\n" : ($this->harga_info ? "🎟️ Info Tiket: {$this->harga_info}\n" : ''))
            ."\nCek info lengkap: {$url}";

        return 'https://wa.me/?text='.rawurlencode($text);
    }

    public function generateIcs(): string
    {
        $dtStart = $this->kickoff_at->copy()->utc()->format('Ymd\THis\Z');
        $dtEnd = $this->endsAt()->copy()->utc()->format('Ymd\THis\Z');
        $summary = 'Nobar: '.$this->judul;
        $location = $this->lokasi_nama.' - '.$this->alamat_patokan;
        $description = 'Nobar pertandingan '.$this->vsTitle().' di SynemaX. Info: '.route('nobar.show', ['slug' => $this->slug]);
        $uid = 'nobar-'.$this->id.'-'.md5($this->slug).'@synemax.local';

        return "BEGIN:VCALENDAR\r\n"
            ."VERSION:2.0\r\n"
            ."PRODID:-//SynemaX Home Cinema//Nobar//ID\r\n"
            ."CALSCALE:GREGORIAN\r\n"
            ."METHOD:PUBLISH\r\n"
            ."BEGIN:VEVENT\r\n"
            ."UID:{$uid}\r\n"
            .'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z')."\r\n"
            ."DTSTART:{$dtStart}\r\n"
            ."DTEND:{$dtEnd}\r\n"
            ."SUMMARY:{$summary}\r\n"
            ."LOCATION:{$location}\r\n"
            ."DESCRIPTION:{$description}\r\n"
            .'URL:'.route('nobar.show', ['slug' => $this->slug])."\r\n"
            ."STATUS:CONFIRMED\r\n"
            ."END:VEVENT\r\n"
            ."END:VCALENDAR\r\n";
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status_publikasi', 'terbit');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status_acara', 'aktif');
    }

    public function scopePublicDisplay(Builder $query): Builder
    {
        return $query->published()
            ->orderByDesc('is_pinned')
            ->orderBy('kickoff_at');
    }
}
