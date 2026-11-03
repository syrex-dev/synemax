<?php

namespace App\Models;

use App\Support\BookingStatusPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'session_id',
    'film_id',
    'customer_profile_id',
    'customer_name',
    'whatsapp',
    'pax',
    'seats_reserved',
    'booking_type',
    'package',
    'people_count',
    'reselect_needed',
    'refund_needed',
    'total_price',
    'ticket_total',
    'snack_total',
    'payment_method',
    'payment_proof_path',
    'status',
    'notes',
    'payment_expires_at',
    'payment_proof_uploaded_at',
    'proof_rejection_note',
    'confirmed_at',
    'cancelled_at',
    'attended_at',
    'access_token_hash',
])]
class Booking extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if ($booking->package === null) {
                $booking->package = $booking->booking_type === 'private' ? 'private' : 'reguler';
            }
            if ($booking->people_count === null) {
                $booking->people_count = (int) ($booking->pax ?: 1);
            }
            if ($booking->seats_reserved === null) {
                $booking->seats_reserved = match ($booking->package) {
                    'private' => 6,
                    'couple' => 2,
                    default => (int) ($booking->pax ?: 1),
                };
            }
        });
    }

    protected function casts(): array
    {
        return [
            'pax' => 'integer',
            'seats_reserved' => 'integer',
            'people_count' => 'integer',
            'reselect_needed' => 'boolean',
            'refund_needed' => 'boolean',
            'total_price' => 'integer',
            'ticket_total' => 'integer',
            'snack_total' => 'integer',
            'payment_expires_at' => 'datetime',
            'payment_proof_uploaded_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'attended_at' => 'datetime',
        ];
    }

    public function isCouple(): bool
    {
        return $this->package === 'couple';
    }

    public function isPrivate(): bool
    {
        return $this->package === 'private' || $this->booking_type === 'private';
    }

    public function isReguler(): bool
    {
        return ! $this->isCouple() && ! $this->isPrivate();
    }

    public function needsReselect(): bool
    {
        return (bool) $this->reselect_needed;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === 'menunggu_pembayaran';
    }

    public function isExpired(): bool
    {
        return $this->status === 'kedaluwarsa'
            || ($this->isAwaitingPayment() && $this->payment_expires_at !== null && $this->payment_expires_at->isPast());
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'terkonfirmasi';
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['terkonfirmasi', 'menunggu_konfirmasi'], true)
            || ! empty($this->payment_proof_path);
    }

    public function isOnlinePayment(): bool
    {
        return in_array($this->payment_method, ['qris', 'transfer'], true);
    }

    /**
     * Sumber tunggal kanonik alur status booking untuk semua halaman dan layanan.
     *
     * Tabel Aturan Flow State:
     * | flowState           | Kriteria Penentu                                                             | Halaman Utama   | Kartu Tiket         | Kotak Menunggu |
     * |---------------------|-----------------------------------------------------------------------------|-----------------|---------------------|----------------|
     * | 'dibatalkan'        | status == 'dibatalkan'                                                      | /tiket          | Tiket (tanpa QR)    | Tidak ada      |
     * | 'selesai'           | status == 'selesai'                                                         | /tiket          | Tiket (tanpa QR)    | Tidak ada      |
     * | 'hadir'             | status == 'hadir'                                                           | /tiket          | Tiket (tanpa QR)    | Tidak ada      |
     * | 'tidak_datang'      | in_array(status, ['terlewat', 'tidak_datang', 'tidak_hadir'])               | /tiket          | Tiket (tanpa QR)    | Tidak ada      |
     * | 'kedaluwarsa'       | isExpired() == true (status == 'kedaluwarsa' || waktu bayar habis)           | /tiket          | Tiket (tanpa QR)    | Tidak ada      |
     * | 'menunggu_bayar'    | isOnlinePayment() && status == 'menunggu_pembayaran' && tanpa bukti bayar   | /pembayaran     | Tidak ada           | Tidak ada      |
     * | 'diproses'          | isOnlinePayment() && (bukti diunggah || status == 'menunggu_konfirmasi')    | /tiket          | Ringkasan (tanpa QR)| Tidak ada      |
     * | 'aktif'             | Lunas/Bayar di tempat & (Private/Couple || Sesi pasti tayang || shortage<=0)| /tiket          | Tiket Aktif (+ QR)  | Tidak ada      |
     * | 'menunggu_penonton' | Lunas/Bayar di tempat, paket Reguler, sesi belum pasti tayang, shortage>=1  | /tiket          | Tiket (tanpa QR)    | Ada (Bagian 2) |
     */
    public function flowState(): string
    {
        if ($this->status === 'dibatalkan') {
            return 'dibatalkan';
        }

        if ($this->status === 'selesai') {
            return 'selesai';
        }

        if ($this->status === 'hadir') {
            return 'hadir';
        }

        if (in_array($this->status, ['terlewat', 'tidak_datang', 'tidak_hadir'], true)) {
            return 'tidak_datang';
        }

        if ($this->isExpired()) {
            return 'kedaluwarsa';
        }

        // 1. Belum dibayar online dan belum kedaluwarsa -> menunggu_bayar
        if ($this->isOnlinePayment() && $this->status === 'menunggu_pembayaran' && empty($this->payment_proof_path)) {
            return 'menunggu_bayar';
        }

        // 2. Bukti online sudah diunggah, menunggu konfirmasi admin -> diproses
        if ($this->isOnlinePayment() && ($this->status === 'menunggu_konfirmasi' || ! empty($this->payment_proof_path)) && $this->status !== 'terkonfirmasi') {
            return 'diproses';
        }

        // Evaluasi kuorum sesi jika belum pasti tayang
        $session = $this->relationLoaded('session') ? $this->session : $this->session()->first();
        if ($session && ! $session->isConfirmed()) {
            $session->evaluateQuorum();
        }

        $isSessionConfirmed = $session ? $session->isConfirmed() : false;

        // 3. Paket Private dan Couple TIDAK PERNAH masuk menunggu_penonton (langsung aktif)
        if ($this->isPrivate() || $this->isCouple()) {
            return 'aktif';
        }

        // 4. Reguler: jika sesi sudah terkonfirmasi pasti tayang -> aktif
        if ($isSessionConfirmed) {
            return 'aktif';
        }

        // 5. Cek kekurangan jumlah orang yang dibutuhkan untuk film ini
        $shortage = 1;
        if ($session) {
            $tallies = $session->candidateFilmTallies();
            $filmTally = $tallies->firstWhere('film_id', $this->film_id);
            $myFilmSeats = $filmTally['seats'] ?? $this->seats_reserved;
            $shortage = max(0, $session->quorum() - $myFilmSeats);
        }

        // Jika kekurangan <= 0, otomatis pasti tayang & booking aktif
        if ($shortage <= 0) {
            if ($session && ! $session->isConfirmed() && $this->film_id) {
                $session->confirmAndLock($this->film_id, 'quorum');
            }

            return 'aktif';
        }

        // 6. Reguler, belum kuorum, dan masih butuh >= 1 orang -> menunggu_penonton
        return 'menunggu_penonton';
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function film(): BelongsTo
    {
        return $this->belongsTo(Film::class)->withTrashed();
    }

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function snackChangeLogs(): HasMany
    {
        return $this->hasMany(SnackOrderEdit::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function paymentStatusLabel(): string
    {
        return BookingStatusPresenter::paymentStatusLabel($this);
    }

    public function paymentStatusColor(): string
    {
        return BookingStatusPresenter::paymentStatusColor($this);
    }

    public function ticketGuidanceText(): string
    {
        return BookingStatusPresenter::ticketGuidanceText($this);
    }
}
