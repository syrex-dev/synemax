<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingCheckin;
use App\Support\WhatsAppNumber;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckInService
{
    public function __construct(
        protected CinemaSettings $settings,
        protected TelegramNotificationService $telegram,
    ) {}

    /**
     * Normalisasi string kode input dari pemindai QR atau input manual.
     * Membersihkan URL, spasi berlebih, tanda hubung ganda, dan kapitalisasi.
     */
    public function normalizeCode(string $raw): string
    {
        $input = trim($raw);

        // Abaikan awalan URL, ambil hanya kode setelah /tiket/
        if (preg_match('~(?:/tiket/|tiket/)([^/?#]+)~i', $input, $matches)) {
            $input = $matches[1];
        }

        // Hilangkan query parameters atau hash jika ada
        if (str_contains($input, '?')) {
            $input = explode('?', $input, 2)[0];
        }
        if (str_contains($input, '#')) {
            $input = explode('#', $input, 2)[0];
        }

        // Hapus karakter non-alphanumeric kecuali tanda hubung
        $clean = preg_replace('/[^A-Za-z0-9\-]/', '', $input) ?? '';

        // Rapikan tanda hubung ganda menjadi tanda hubung tunggal
        $clean = preg_replace('/-+/', '-', $clean) ?? '';

        return strtoupper(trim($clean, '-'));
    }

    /**
     * Cari booking berdasarkan kode lengkap atau akhiran.
     *
     * @return array{
     *     status: 'found'|'ambiguous'|'not_found',
     *     booking?: Booking,
     *     matches?: list<array{id: int, code: string, customer_name: string, people_count: int, start_time: string}>,
     *     verdict?: string,
     *     message?: string,
     *     preview?: array<string, mixed>
     * }
     */
    public function lookup(string $rawCode, ?string $selectedSessionId = null): array
    {
        $normalized = $this->normalizeCode($rawCode);

        if ($normalized === '') {
            return [
                'status' => 'not_found',
                'verdict' => 'tidak_ditemukan',
                'message' => 'Kode tidak ditemukan. Cek ejaan atau gunakan tab Kode/Daftar.',
            ];
        }

        // 1. Cari exact match kode booking
        $booking = Booking::with(['film', 'session', 'checkedInBy', 'items.menuItem'])
            ->where('code', $normalized)
            ->first();

        if ($booking) {
            $eval = $this->evaluate($booking, $selectedSessionId);

            return [
                'status' => 'found',
                'booking' => $booking,
                'verdict' => $eval['verdict'],
                'message' => $eval['message'],
                'preview' => $this->previewPayload($booking, $eval['verdict'], $eval['message']),
            ];
        }

        // 2. Pencarian akhiran kode (4-8 karakter) pada sesi aktif atau hari ini
        if (strlen($normalized) >= 4 && strlen($normalized) <= 8 && ! str_starts_with($normalized, 'HC-')) {
            $query = Booking::with(['film', 'session', 'checkedInBy', 'items.menuItem'])
                ->where('code', 'LIKE', '%'.$normalized);

            if ($selectedSessionId && $selectedSessionId !== 'all') {
                $query->where('session_id', $selectedSessionId);
            } else {
                $todayDate = now()->toDateString();
                $query->whereHas('session', fn ($q) => $q->where('session_date', $todayDate));
            }

            $matches = $query->get();

            if ($matches->count() === 1) {
                $single = $matches->first();
                $eval = $this->evaluate($single, $selectedSessionId);

                return [
                    'status' => 'found',
                    'booking' => $single,
                    'verdict' => $eval['verdict'],
                    'message' => $eval['message'],
                    'preview' => $this->previewPayload($single, $eval['verdict'], $eval['message']),
                ];
            }

            if ($matches->count() > 1) {
                return [
                    'status' => 'ambiguous',
                    'verdict' => 'ambigu',
                    'message' => 'Ditemukan '.$matches->count().' tiket dengan akhiran yang cocok. Pilih salah satu dari daftar.',
                    'matches' => $matches->map(fn (Booking $b) => [
                        'id' => $b->id,
                        'code' => $b->code,
                        'customer_name' => $b->customer_name,
                        'people_count' => $b->people_count ?? $b->seats_reserved,
                        'start_time' => format_cinema_time($b->session?->start_time ?? ''),
                    ])->values()->all(),
                ];
            }
        }

        return [
            'status' => 'not_found',
            'verdict' => 'tidak_ditemukan',
            'message' => 'Kode tidak ditemukan. Cek ejaan atau gunakan tab Kode/Daftar.',
        ];
    }

    /**
     * Evaluasi verdict check-in untuk suatu booking.
     *
     * @return array{
     *     verdict: string,
     *     message: string,
     *     can_check_in: bool,
     *     can_override: bool,
     *     details?: array<string, mixed>
     * }
     */
    public function evaluate(Booking $booking, ?string $selectedSessionId = null): array
    {
        $session = $booking->session;

        // 1. Sudah Hadir
        if ($booking->status === 'hadir' || $booking->checked_in_at !== null) {
            return [
                'verdict' => 'sudah_hadir',
                'message' => $this->alreadyCheckedInMessage($booking),
                'can_check_in' => false,
                'can_override' => false,
            ];
        }

        // 2. Dibatalkan
        if ($booking->status === 'dibatalkan') {
            return [
                'verdict' => 'dibatalkan',
                'message' => 'Tiket ini telah dibatalkan dan tidak berlaku.',
                'can_check_in' => false,
                'can_override' => false,
            ];
        }

        // 3. Kedaluwarsa
        if ($booking->status === 'kedaluwarsa' || $booking->flowState() === 'kedaluwarsa') {
            return [
                'verdict' => 'kedaluwarsa',
                'message' => 'Batas waktu pembayaran telah habis. Tiket sudah kedaluwarsa.',
                'can_check_in' => false,
                'can_override' => false,
            ];
        }

        // 4. Tidak Datang / Terlewat
        if (in_array($booking->status, ['terlewat', 'tidak_datang', 'tidak_hadir'], true)) {
            return [
                'verdict' => 'tidak_datang',
                'message' => 'Booking ditandai tidak hadir / no-show.',
                'can_check_in' => false,
                'can_override' => false,
            ];
        }

        // 5. Belum Bayar
        if ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran' && empty($booking->payment_proof_path)) {
            return [
                'verdict' => 'belum_bayar',
                'message' => 'Belum lunas, minta pelanggan menyelesaikan pembayaran atau konfirmasi di admin.',
                'can_check_in' => false,
                'can_override' => false,
            ];
        }

        // 6. Sedang Diproses (Bukti terunggah belum diverifikasi)
        if ($booking->flowState() === 'diproses') {
            return [
                'verdict' => 'diproses',
                'message' => 'Bukti bayar sedang diproses. Mohon verifikasi pembayaran di menu admin terlebih dahulu.',
                'can_check_in' => false,
                'can_override' => false,
            ];
        }

        // 7. Menunggu Penonton
        if ($booking->flowState() === 'menunggu_penonton') {
            return [
                'verdict' => 'menunggu_penonton',
                'message' => 'Sesi belum pasti tayang (jumlah penonton belum memenuhi). Belum dapat check-in.',
                'can_check_in' => false,
                'can_override' => false,
            ];
        }

        // 8. Sesi atau Tanggal Lain
        if (! $session) {
            return [
                'verdict' => 'tidak_ditemukan',
                'message' => 'Sesi untuk booking ini tidak ditemukan.',
                'can_check_in' => false,
                'can_override' => false,
            ];
        }

        $todayDate = now()->toDateString();
        $sessionDate = $session->session_date instanceof CarbonInterface
            ? $session->session_date->toDateString()
            : (string) $session->session_date;

        if ($sessionDate !== $todayDate) {
            $dateFormatted = format_cinema_date($session->session_date, true);
            $timeFormatted = format_cinema_time($session->start_time);

            return [
                'verdict' => 'sesi_lain',
                'message' => "Tiket untuk tanggal lain: {$dateFormatted} pukul {$timeFormatted}.",
                'can_check_in' => false,
                'can_override' => true,
            ];
        }

        // Jika admin memilih sesi tertentu dan tiket milik sesi yang berbeda pada hari ini
        if ($selectedSessionId && $selectedSessionId !== 'all' && (string) $booking->session_id !== (string) $selectedSessionId) {
            $timeFormatted = format_cinema_time($session->start_time);

            return [
                'verdict' => 'sesi_lain',
                'message' => "Tiket untuk sesi lain hari ini: pukul {$timeFormatted}.",
                'can_check_in' => false,
                'can_override' => true,
            ];
        }

        // 9. Jendela Waktu Check-In
        $openMinutes = $this->settings->checkInOpenMinutesBefore();
        $closeOption = $this->settings->checkInCloseOption();

        $sessionStart = $session->startsAt();
        $windowStart = $sessionStart->subMinutes($openMinutes);

        if ($closeOption === 'session_end') {
            $windowEnd = $session->endsAt();
        } else {
            $windowEnd = $sessionStart->addMinutes((int) $closeOption);
        }

        $now = now();
        if ($now->lessThan($windowStart)) {
            $diffMins = $now->diffInMinutes($windowStart, false);
            $timeFormatted = format_cinema_time($windowStart);

            return [
                'verdict' => 'di_luar_jendela',
                'message' => "Terlalu awal. Check-in baru dibuka {$diffMins} menit lagi (pukul {$timeFormatted}).",
                'can_check_in' => false,
                'can_override' => true,
                'details' => [
                    'window_start' => $windowStart->toIso8601String(),
                    'window_end' => $windowEnd->toIso8601String(),
                    'diff_minutes' => $diffMins,
                    'timing' => 'early',
                ],
            ];
        }

        if ($now->greaterThan($windowEnd)) {
            $diffMins = $windowEnd->diffInMinutes($now, false);
            $timeFormatted = format_cinema_time($windowEnd);

            return [
                'verdict' => 'di_luar_jendela',
                'message' => "Terlalu lambat. Check-in telah ditutup {$diffMins} menit lalu (pukul {$timeFormatted}).",
                'can_check_in' => false,
                'can_override' => true,
                'details' => [
                    'window_start' => $windowStart->toIso8601String(),
                    'window_end' => $windowEnd->toIso8601String(),
                    'diff_minutes' => $diffMins,
                    'timing' => 'late',
                ],
            ];
        }

        // 10. Siap Hadir
        return [
            'verdict' => 'siap_hadir',
            'message' => 'Tiket terverifikasi dan siap masuk studio.',
            'can_check_in' => true,
            'can_override' => false,
        ];
    }

    /**
     * Tandai hadir (Check-in) dengan transaksi, locking, idempotency, dan jejak audit.
     *
     * @return array{
     *     success: bool,
     *     verdict: string,
     *     result: string,
     *     message: string,
     *     booking?: Booking,
     *     preview?: array<string, mixed>
     * }
     */
    public function checkIn(
        int $bookingId,
        int $userId,
        string $method = 'qr',
        ?string $reason = null,
        bool $allowOverride = false,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $selectedSessionId = null
    ): array {
        $cleanAgent = $userAgent ? Str::limit($userAgent, 490) : null;

        return DB::transaction(function () use (
            $bookingId,
            $userId,
            $method,
            $reason,
            $allowOverride,
            $ip,
            $cleanAgent,
            $selectedSessionId
        ) {
            /** @var Booking|null $booking */
            $booking = Booking::query()->lockForUpdate()->find($bookingId);

            if (! $booking) {
                return [
                    'success' => false,
                    'verdict' => 'tidak_ditemukan',
                    'result' => 'rejected',
                    'message' => 'Booking tidak ditemukan.',
                ];
            }

            // Pemeriksaan Idempoten: Jika sudah hadir sebelumnya
            if ($booking->status === 'hadir' || $booking->checked_in_at !== null) {
                BookingCheckin::create([
                    'booking_id' => $booking->id,
                    'user_id' => $userId,
                    'method' => $method,
                    'result' => 'duplicate',
                    'reason' => 'Tiket sudah pernah ditandai hadir',
                    'ip' => $ip,
                    'user_agent' => $cleanAgent,
                    'created_at' => now(),
                ]);

                $freshBooking = Booking::with(['film', 'session', 'checkedInBy', 'items.menuItem'])->find($booking->id);

                return [
                    'success' => false,
                    'verdict' => 'sudah_hadir',
                    'result' => 'duplicate',
                    'message' => $this->alreadyCheckedInMessage($freshBooking),
                    'booking' => $freshBooking,
                    'preview' => $this->previewPayload($freshBooking, 'sudah_hadir', $this->alreadyCheckedInMessage($freshBooking)),
                ];
            }

            $eval = $this->evaluate($booking, $selectedSessionId);
            $verdict = $eval['verdict'];

            $isDirectlyAllowed = $verdict === 'siap_hadir';
            $isOverrideAllowed = $allowOverride && in_array($verdict, ['di_luar_jendela', 'sesi_lain'], true);

            if (! $isDirectlyAllowed && ! $isOverrideAllowed) {
                BookingCheckin::create([
                    'booking_id' => $booking->id,
                    'user_id' => $userId,
                    'method' => $method,
                    'result' => 'rejected',
                    'reason' => $eval['message'],
                    'ip' => $ip,
                    'user_agent' => $cleanAgent,
                    'created_at' => now(),
                ]);

                $freshBooking = Booking::with(['film', 'session', 'checkedInBy', 'items.menuItem'])->find($booking->id);

                return [
                    'success' => false,
                    'verdict' => $verdict,
                    'result' => 'rejected',
                    'message' => $eval['message'],
                    'booking' => $freshBooking,
                    'preview' => $this->previewPayload($freshBooking, $verdict, $eval['message']),
                ];
            }

            // Atomic conditional update untuk menjamin tepat satu transaksi menang saat konkurensi
            $affected = Booking::query()
                ->where('id', $booking->id)
                ->whereNull('checked_in_at')
                ->update([
                    'status' => 'hadir',
                    'attended_at' => now(),
                    'checked_in_at' => now(),
                    'checked_in_by' => $userId,
                    'check_in_method' => $method,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                // Kalah balapan konkurensi (sudah ditandai tepat sebelum update ini)
                BookingCheckin::create([
                    'booking_id' => $booking->id,
                    'user_id' => $userId,
                    'method' => $method,
                    'result' => 'duplicate',
                    'reason' => 'Konkurensi: Tiket sudah dipindai oleh admin lain',
                    'ip' => $ip,
                    'user_agent' => $cleanAgent,
                    'created_at' => now(),
                ]);

                $freshBooking = Booking::with(['film', 'session', 'checkedInBy', 'items.menuItem'])->find($booking->id);

                return [
                    'success' => false,
                    'verdict' => 'sudah_hadir',
                    'result' => 'duplicate',
                    'message' => $this->alreadyCheckedInMessage($freshBooking),
                    'booking' => $freshBooking,
                    'preview' => $this->previewPayload($freshBooking, 'sudah_hadir', $this->alreadyCheckedInMessage($freshBooking)),
                ];
            }

            $resultType = $isOverrideAllowed ? 'override' : 'ok';
            BookingCheckin::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'method' => $method,
                'result' => $resultType,
                'reason' => $reason,
                'ip' => $ip,
                'user_agent' => $cleanAgent,
                'created_at' => now(),
            ]);

            $freshBooking = Booking::with(['film', 'session', 'checkedInBy', 'items.menuItem'])->find($booking->id);

            // Kirim notifikasi Telegram jika diaktifkan (OFF bawaan)
            try {
                $this->telegram->queue('customer_checked_in', $freshBooking);
                $this->telegram->flush();
            } catch (\Throwable $e) {
                // Fail-safe: jangan gagalkan check-in hanya karena Telegram
            }

            return [
                'success' => true,
                'verdict' => 'siap_hadir',
                'result' => $resultType,
                'message' => 'Check-in berhasil. Pelanggan dipersilakan masuk.',
                'booking' => $freshBooking,
                'preview' => $this->previewPayload($freshBooking, 'siap_hadir', 'Check-in berhasil. Pelanggan dipersilakan masuk.'),
            ];
        });
    }

    /**
     * Batalkan tanda hadir (Undo check-in).
     *
     * @return array{success: bool, message: string}
     */
    public function undoCheckIn(
        int $bookingId,
        int $userId,
        ?string $reason = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        $cleanAgent = $userAgent ? Str::limit($userAgent, 490) : null;

        return DB::transaction(function () use ($bookingId, $userId, $reason, $ip, $cleanAgent) {
            /** @var Booking|null $booking */
            $booking = Booking::query()->lockForUpdate()->find($bookingId);

            if (! $booking) {
                return ['success' => false, 'message' => 'Booking tidak ditemukan.'];
            }

            if ($booking->status !== 'hadir' && $booking->checked_in_at === null) {
                return ['success' => false, 'message' => 'Booking belum berstatus hadir.'];
            }

            $booking->update([
                'status' => 'terkonfirmasi',
                'attended_at' => null,
                'checked_in_at' => null,
                'checked_in_by' => null,
                'check_in_method' => null,
                'updated_at' => now(),
            ]);

            BookingCheckin::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'method' => 'admin_manual',
                'result' => 'undo',
                'reason' => $reason ?: 'Pembatalan status hadir oleh admin',
                'ip' => $ip,
                'user_agent' => $cleanAgent,
                'created_at' => now(),
            ]);

            return ['success' => true, 'message' => 'Tanda hadir berhasil dibatalkan. Booking kembali ke status aktif.'];
        });
    }

    /**
     * Format teks pesan sudah hadir: "Sudah hadir pukul 09.52 (QR oleh Admin X)"
     */
    public function alreadyCheckedInMessage(Booking $booking): string
    {
        $time = $booking->checked_in_at ?? $booking->attended_at ?? $booking->updated_at;
        $timeStr = $time ? format_cinema_time($time, false) : '-';

        $methodLabel = match ($booking->check_in_method) {
            'qr' => 'QR',
            'kode' => 'Kode',
            'daftar' => 'Daftar',
            'admin_manual' => 'Manual',
            'legacy' => 'Sistem Sebelumnya',
            default => 'Pemindai',
        };

        $adminName = $booking->checkedInBy?->name ?? 'Admin';

        return "Sudah hadir pukul {$timeStr} ({$methodLabel} oleh {$adminName})";
    }

    /**
     * Payload pratinjau tiket yang disajikan pada bottom-sheet pemindai.
     *
     * @return array<string, mixed>
     */
    public function previewPayload(Booking $booking, string $verdict, string $message): array
    {
        $session = $booking->session;
        $film = $booking->film;

        $packageLabel = match ($booking->package ?? $booking->booking_type) {
            'private' => 'Sewa Private',
            'couple' => 'Paket Couple',
            default => 'Reguler',
        };

        $peopleCount = (int) ($booking->people_count ?? $booking->seats_reserved ?? 1);
        $peopleDisplay = "{$packageLabel} · {$peopleCount} orang";

        // Snacks ringkas
        $snacks = $booking->items->where('status', '!=', 'dibatalkan');
        $snackSummary = $snacks->isEmpty()
            ? 'Tidak ada jajan'
            : $snacks->map(fn ($i) => "{$i->qty}× {$i->name_snapshot}")->join(', ');

        $badge = match ($verdict) {
            'siap_hadir', 'sudah_hadir' => ['label' => 'Siap Hadir', 'token' => 'success'],
            'di_luar_jendela' => ['label' => 'Di Luar Jam', 'token' => 'warning'],
            'sesi_lain' => ['label' => 'Sesi / Hari Lain', 'token' => 'danger'],
            'belum_bayar' => ['label' => 'Belum Lunas', 'token' => 'danger'],
            'diproses' => ['label' => 'Bukti Diproses', 'token' => 'warning'],
            'menunggu_penonton' => ['label' => 'Menunggu Penonton', 'token' => 'warning'],
            'dibatalkan' => ['label' => 'Dibatalkan', 'token' => 'danger'],
            'kedaluwarsa' => ['label' => 'Kedaluwarsa', 'token' => 'danger'],
            'tidak_datang' => ['label' => 'Tidak Hadir', 'token' => 'danger'],
            default => ['label' => 'Perlu Cek', 'token' => 'danger'],
        };

        return [
            'booking_id' => $booking->id,
            'code' => $booking->code,
            'customer_name' => $booking->customer_name,
            'whatsapp_masked' => WhatsAppNumber::mask($booking->whatsapp),
            'whatsapp_raw' => WhatsAppNumber::format($booking->whatsapp),
            'film_title' => $film?->title ?? 'Belum ada film',
            'film_poster_url' => $film?->poster_url ?? null,
            'session_date_formatted' => $session ? format_cinema_date($session->session_date, true) : '-',
            'session_time_formatted' => $session ? format_cinema_time($session->start_time) : '-',
            'package_display' => $peopleDisplay,
            'people_count' => $peopleCount,
            'snacks_summary' => $snackSummary,
            'notes' => $booking->notes ? trim((string) $booking->notes) : null,
            'payment_status_label' => $booking->paymentStatusLabel(),
            'verdict' => $verdict,
            'verdict_badge' => $badge,
            'message' => $message,
            'checked_in_info' => ($booking->status === 'hadir' || $booking->checked_in_at)
                ? $this->alreadyCheckedInMessage($booking)
                : null,
            'can_check_in' => $verdict === 'siap_hadir',
            'can_override' => in_array($verdict, ['di_luar_jendela', 'sesi_lain'], true),
            'detail_url' => route('admin.bookings.show', $booking),
        ];
    }
}
