<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\NotificationOutbox;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramNotificationService
{
    public const API_BASE = 'https://api.telegram.org';

    public function __construct(
        protected CinemaSettings $settings,
    ) {}

    /**
     * Check if Telegram notifications are globally enabled.
     */
    public function isEnabled(): bool
    {
        return $this->settings->boolean('telegram_notifications_enabled', false)
            && ! empty($this->getDecryptedToken());
    }

    /**
     * Retrieve decrypted bot token.
     */
    public function getDecryptedToken(): ?string
    {
        $raw = $this->settings->string('telegram_bot_token');
        if (empty($raw)) {
            return null;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (Throwable) {
            // If already plaintext or cannot decrypt
            return $raw;
        }
    }

    /**
     * Get masked token for display in UI.
     */
    public function getMaskedToken(): string
    {
        $token = $this->getDecryptedToken();
        if (empty($token)) {
            return '';
        }

        $parts = explode(':', $token, 2);
        if (count($parts) === 2) {
            $prefix = '••••••';
            $secret = $parts[1];
            $visible = strlen($secret) > 6 ? substr($secret, 0, 3).'...'.substr($secret, -3) : '...';

            return "{$prefix}:{$visible}";
        }

        return strlen($token) > 8 ? substr($token, 0, 3).'••••••'.substr($token, -3) : '••••••••';
    }

    /**
     * Get configured chat IDs.
     *
     * @return list<string>
     */
    public function getChatIds(): array
    {
        $raw = $this->settings->string('telegram_chat_ids');
        if (empty($raw)) {
            return [];
        }

        $lines = preg_split('/[\r\n,]+/', (string) $raw);
        $ids = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $ids[] = $t;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Check if specific event notification is enabled.
     */
    public function isEventEnabled(string $event): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return match ($event) {
            'booking_created' => $this->settings->boolean('telegram_notify_booking_created', true),
            'proof_uploaded' => $this->settings->boolean('telegram_notify_proof_uploaded', true),
            'payment_confirmed' => $this->settings->boolean('telegram_notify_payment_confirmed', false),
            'booking_cancelled' => $this->settings->boolean('telegram_notify_booking_cancelled', true),
            'booking_expired' => $this->settings->boolean('telegram_notify_booking_expired', false),
            'session_confirmed' => $this->settings->boolean('telegram_notify_session_confirmed', true),
            'session_cancelled' => $this->settings->boolean('telegram_notify_session_cancelled', true),
            'customer_checked_in' => $this->settings->boolean('telegram_notify_customer_checked_in', false),
            default => false,
        };
    }

    /**
     * Queue an outbox message inside transaction, and trigger afterResponse dispatch.
     */
    public function queue(string $event, ?Booking $booking, array $payload = []): ?NotificationOutbox
    {
        if (! $this->isEventEnabled($event)) {
            return null;
        }

        $bookingId = $booking?->getKey();

        // Enforce uniqueness per channel + event + booking_id
        $outbox = NotificationOutbox::query()->firstOrCreate(
            [
                'channel' => 'telegram',
                'event' => $event,
                'booking_id' => $bookingId,
            ],
            [
                'payload' => $payload,
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => now(),
            ]
        );

        // Attempt immediate dispatch after HTTP response completes
        app()->terminating(function () use ($outbox) {
            $this->dispatchItem($outbox);
        });

        return $outbox;
    }

    /**
     * Dispatch single outbox item to Telegram.
     */
    public function dispatchItem(NotificationOutbox $item): bool
    {
        if ($item->status === 'sent') {
            return true;
        }

        $token = $this->getDecryptedToken();
        $chatIds = $this->getChatIds();

        if (empty($token) || empty($chatIds)) {
            $item->update([
                'status' => 'failed',
                'last_error' => 'Token bot atau Chat ID Telegram belum dikonfigurasi.',
            ]);

            return false;
        }

        $booking = $item->booking_id ? Booking::with(['session.film', 'items.menuItem'])->find($item->booking_id) : null;
        $text = $this->buildMessageText($item->event, $booking, $item->payload ?? []);

        $inlineKeyboard = [];
        if ($booking && str_starts_with((string) config('app.url'), 'https://')) {
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Buka di admin',
                            'url' => route('admin.bookings.show', ['booking' => $booking->getKey()]),
                        ],
                    ],
                ],
            ];
        }

        $item->increment('attempts');
        $allSuccess = true;
        $lastError = null;

        foreach ($chatIds as $chatId) {
            try {
                $postData = [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ];

                if (! empty($inlineKeyboard)) {
                    $postData['reply_markup'] = $inlineKeyboard;
                }

                $response = Http::baseUrl(self::API_BASE)
                    ->timeout(5)
                    ->post("/bot{$token}/sendMessage", $postData);

                if (! $response->successful()) {
                    $allSuccess = false;
                    $status = $response->status();
                    $body = $response->json();
                    $desc = $body['description'] ?? 'HTTP '.$status;

                    if ($status === 429) {
                        $retryAfter = (int) ($body['parameters']['retry_after'] ?? 60);
                        $item->update([
                            'available_at' => now()->addSeconds($retryAfter),
                            'last_error' => "Rate limited (429): retry after {$retryAfter}s",
                        ]);

                        return false;
                    }

                    $lastError = "Chat ID {$chatId}: {$desc}";
                }
            } catch (Throwable $e) {
                $allSuccess = false;
                $lastError = "Chat ID {$chatId}: ".$e->getMessage();
            }
        }

        if ($allSuccess) {
            $item->update([
                'status' => 'sent',
                'sent_at' => now(),
                'last_error' => null,
            ]);

            return true;
        }

        // Retry backoff: attempt 1 -> +1 min, attempt 2 -> +5 min, attempt 3 -> +15 min
        $delays = [1 => 1, 2 => 5, 3 => 15, 4 => 30];
        $delayMinutes = $delays[$item->attempts] ?? null;

        if ($delayMinutes !== null && $item->attempts < 5) {
            $item->update([
                'status' => 'pending',
                'available_at' => now()->addMinutes($delayMinutes),
                'last_error' => $lastError,
            ]);
        } else {
            $item->update([
                'status' => 'failed',
                'last_error' => $lastError,
            ]);
        }

        return false;
    }

    /**
     * Flush pending outbox messages ready for delivery.
     */
    public function flush(int $limit = 20): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $items = NotificationOutbox::query()
            ->where('channel', 'telegram')
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            })
            ->where('attempts', '<', 5)
            ->limit($limit)
            ->get();

        $sentCount = 0;
        foreach ($items as $item) {
            if ($this->dispatchItem($item)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Test sending a sample notification to a specific chat ID or all configured chats.
     *
     * @return array{success: bool, message: string}
     */
    public function sendTestMessage(?string $overrideChatId = null, ?string $overrideToken = null): array
    {
        $token = $overrideToken ?: $this->getDecryptedToken();
        $chatIds = $overrideChatId ? [$overrideChatId] : $this->getChatIds();

        if (empty($token)) {
            return ['success' => false, 'message' => 'Token bot Telegram belum diisi.'];
        }
        if (empty($chatIds)) {
            return ['success' => false, 'message' => 'Chat ID belum diisi. Masukkan Chat ID atau gunakan fitur Deteksi Chat ID.'];
        }

        $text = "<b>[Pesan Uji Coba SynemaX]</b>\n\n"
            ."Notifikasi Telegram untuk admin bioskop mini berhasil terhubung.\n"
            .'<b>Waktu:</b> '.format_hari_tanggal_id(now()).' · '.format_jam_id(now())."\n"
            .'<b>Status:</b> Terverifikasi';

        try {
            $targetChat = $chatIds[0];
            $response = Http::baseUrl(self::API_BASE)
                ->timeout(5)
                ->post("/bot{$token}/sendMessage", [
                    'chat_id' => $targetChat,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Pesan tes berhasil dikirim ke Telegram!'];
            }

            $body = $response->json();
            $status = $response->status();
            $desc = $body['description'] ?? 'Galat HTTP '.$status;

            $friendlyReason = match ($status) {
                401 => 'Token bot tidak valid atau salah format. Pastikan token dari @BotFather sudah benar.',
                400 => str_contains(strtolower($desc), 'chat not found')
                    ? 'Chat ID tidak ditemukan. Pastikan Anda sudah menekan tombol Start pada bot di Telegram.'
                    : "Permintaan ditolak oleh Telegram: {$desc}",
                403 => 'Bot diblokir atau belum diberikan izin mengirim pesan ke Chat ID ini.',
                default => "Telegram menolak pengiriman: {$desc}",
            };

            return ['success' => false, 'message' => $friendlyReason];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Gagal menghubungi server Telegram: '.$e->getMessage()];
        }
    }

    /**
     * Detect Chat IDs by calling getUpdates.
     *
     * @return array{success: bool, chats: list<array{id: string, name: string, type: string}>, message?: string}
     */
    public function detectChats(?string $overrideToken = null): array
    {
        $token = $overrideToken ?: $this->getDecryptedToken();
        if (empty($token)) {
            return ['success' => false, 'chats' => [], 'message' => 'Token bot Telegram belum diisi.'];
        }

        try {
            $response = Http::baseUrl(self::API_BASE)
                ->timeout(5)
                ->get("/bot{$token}/getUpdates");

            if (! $response->successful()) {
                $body = $response->json();
                $status = $response->status();
                $desc = $body['description'] ?? 'Galat HTTP '.$status;

                $friendlyReason = match ($status) {
                    401 => 'Token bot tidak valid atau salah format. Periksa kembali token dari @BotFather.',
                    default => "Gagal membaca getUpdates: {$desc}",
                };

                return ['success' => false, 'chats' => [], 'message' => $friendlyReason];
            }

            $results = $response->json('result') ?? [];
            $found = [];

            foreach ($results as $update) {
                $chat = $update['message']['chat'] ?? $update['channel_post']['chat'] ?? $update['my_chat_member']['chat'] ?? null;
                if ($chat && ! empty($chat['id'])) {
                    $id = (string) $chat['id'];
                    $type = (string) ($chat['type'] ?? 'private');
                    $title = $chat['title'] ?? trim(($chat['first_name'] ?? '').' '.($chat['last_name'] ?? ''));
                    $username = ! empty($chat['username']) ? '@'.$chat['username'] : '';
                    $name = trim("{$title} {$username}");

                    $found[$id] = [
                        'id' => $id,
                        'name' => $name ?: 'Chat #'.$id,
                        'type' => $type,
                    ];
                }
            }

            $chats = array_values($found);

            return [
                'success' => true,
                'chats' => $chats,
                'message' => count($chats) > 0
                    ? 'Ditemukan '.count($chats).' obrolan aktif.'
                    : 'Belum ada obrolan baru. Kirim /start ke bot Anda di Telegram terlebih dahulu, lalu coba lagi.',
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'chats' => [], 'message' => 'Gagal menghubungi Telegram: '.$e->getMessage()];
        }
    }

    /**
     * Build secure, escaped HTML message body for a given event.
     */
    public function buildMessageText(string $event, ?Booking $booking, array $payload = []): string
    {
        $isUnpaid = $booking && ($booking->status === 'menunggu_pembayaran' || ! $booking->isPaid());
        $title = match ($event) {
            'booking_created' => $isUnpaid ? '<b>[Booking Baru, Menunggu Pembayaran]</b>' : '<b>[Booking Baru Diterima]</b>',
            'proof_uploaded' => '<b>[Bukti Bayar Diunggah - Perlu Konfirmasi]</b>',
            'payment_confirmed' => '<b>[Pembayaran Booking Dikonfirmasi]</b>',
            'booking_cancelled' => '<b>[Booking Dibatalkan Pelanggan]</b>',
            'booking_expired' => '<b>[Booking Kedaluwarsa - Belum Dibayar]</b>',
            'session_confirmed' => '<b>[Sesi Pasti Tayang - Penonton Terpenuhi]</b>',
            'session_cancelled' => '<b>[Sesi Dibatalkan - Penonton Kurang]</b>',
            default => '<b>[Pemberitahuan SynemaX]</b>',
        };

        if ($booking === null) {
            $msg = $title."\n\n";
            if (! empty($payload['message'])) {
                $msg .= htmlspecialchars((string) $payload['message'], ENT_QUOTES, 'UTF-8')."\n";
            }

            return $msg;
        }

        $code = htmlspecialchars($booking->code, ENT_QUOTES, 'UTF-8');
        $customerName = htmlspecialchars($booking->customer_name, ENT_QUOTES, 'UTF-8');

        $hideWa = $this->settings->boolean('telegram_hide_customer_whatsapp', false);
        $waDisplay = $hideWa ? '<i>[Disembunyikan]</i>' : htmlspecialchars(format_phone_id($booking->whatsapp), ENT_QUOTES, 'UTF-8');

        $filmTitle = htmlspecialchars($booking->film?->title ?? 'Belum ada film', ENT_QUOTES, 'UTF-8');

        $session = $booking->session;
        if ($event === 'customer_checked_in') {
            $timeDisplay = $session ? htmlspecialchars(format_jam_id($session->start_time), ENT_QUOTES, 'UTF-8') : '-';

            return "<b>[Pelanggan Hadir]</b>\n\n"
                ."<b>Kode:</b> <code>{$code}</code>\n"
                ."<b>Nama:</b> {$customerName}\n"
                ."<b>Film:</b> {$filmTitle}\n"
                ."<b>Jam:</b> {$timeDisplay}";
        }
        $scheduleDisplay = $session
            ? htmlspecialchars(format_hari_tanggal_id($session->session_date).' · '.format_jam_id($session->start_time), ENT_QUOTES, 'UTF-8')
            : '-';

        $packageLabel = match ($booking->package ?? $booking->booking_type) {
            'private' => 'Sewa Private',
            'couple' => 'Paket Couple',
            default => 'Reguler',
        };
        $paxDisplay = htmlspecialchars("{$packageLabel} ({$booking->seats_reserved} orang)", ENT_QUOTES, 'UTF-8');

        // Snacks summary (max 5 items, then "+N item")
        $snackItems = $booking->items ?? collect();
        $snackDisplay = '-';
        if ($snackItems->isNotEmpty()) {
            $snackLines = [];
            $visibleSnacks = $snackItems->take(5);
            foreach ($visibleSnacks as $item) {
                $name = htmlspecialchars($item->name_snapshot ?? $item->menuItem?->name ?? 'Jajan', ENT_QUOTES, 'UTF-8');
                $qty = (int) ($item->qty ?? $item->quantity ?? 1);
                $snackLines[] = "• {$name} × {$qty}";
            }
            if ($snackItems->count() > 5) {
                $extra = $snackItems->count() - 5;
                $snackLines[] = "<i>+{$extra} item lainnya</i>";
            }
            $snackDisplay = implode("\n", $snackLines);
        }

        $totalDisplay = 'Rp '.number_format($booking->total_price, 0, ',', '.');
        $methodLabel = match ($booking->payment_method) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            default => 'Bayar di tempat',
        };
        $statusLabel = match ($booking->status) {
            'menunggu_pembayaran' => 'Menunggu Pembayaran',
            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
            'terkonfirmasi' => 'Terkonfirmasi',
            'dibatalkan' => 'Dibatalkan',
            'kedaluwarsa' => 'Kedaluwarsa',
            default => ucfirst(str_replace('_', ' ', $booking->status)),
        };

        $includeNotes = $this->settings->boolean('telegram_include_customer_notes', true);
        $notesSection = '';
        if ($includeNotes && ! empty($booking->notes)) {
            $truncatedNotes = htmlspecialchars(mb_strimwidth((string) $booking->notes, 0, 200, '...'), ENT_QUOTES, 'UTF-8');
            $notesSection = "\n<b>Catatan:</b> <i>\"{$truncatedNotes}\"</i>\n";
        }

        return "{$title}\n\n"
            ."<b>Kode:</b> <code>{$code}</code>\n"
            ."<b>Pemesan:</b> {$customerName}\n"
            ."<b>WhatsApp:</b> {$waDisplay}\n"
            ."<b>Film:</b> {$filmTitle}\n"
            ."<b>Jadwal:</b> {$scheduleDisplay}\n"
            ."<b>Paket:</b> {$paxDisplay}\n"
            ."<b>Jajan:</b>\n{$snackDisplay}\n"
            ."<b>Total:</b> <b>{$totalDisplay}</b> ({$methodLabel})\n"
            ."<b>Status:</b> {$statusLabel}"
            .$notesSection;
    }
}
