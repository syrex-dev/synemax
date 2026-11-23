<?php

namespace App\Services\Telegram;

use App\Models\Booking;
use App\Models\BotFeedback;
use App\Models\MenuItem;
use App\Services\CinemaSettings;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramBotService
{
    protected ?string $adminToken;

    protected ?string $csToken;

    /** @var list<string> */
    protected array $adminChatIds = [];

    public function __construct(
        protected CinemaSettings $settings,
        protected TelegramNotificationService $telegramService,
    ) {
        $this->adminToken = $this->telegramService->getDecryptedToken();
        $this->csToken = $this->telegramService->getCsDecryptedToken() ?? $this->adminToken;
        $this->adminChatIds = $this->telegramService->getChatIds();
    }

    public function getAdminToken(): ?string
    {
        return $this->adminToken;
    }

    public function getCsToken(): ?string
    {
        return $this->csToken;
    }

    public function getToken(): ?string
    {
        return $this->csToken ?? $this->adminToken;
    }

    public function getMe(): ?array
    {
        return $this->getCsMe() ?? $this->getAdminMe();
    }

    public function getAdminMe(): ?array
    {
        return $this->apiGetMe($this->adminToken);
    }

    public function getCsMe(): ?array
    {
        return $this->apiGetMe($this->csToken);
    }

    protected function apiGetMe(?string $token): ?array
    {
        if (! $token) {
            return null;
        }

        try {
            $res = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getMe");
            if ($res->successful() && $res->json('ok')) {
                return $res->json('result');
            }
        } catch (Throwable $e) {
            Log::warning('[TelegramBot] getMe error: '.$e->getMessage());
        }

        return null;
    }

    public function setWebhook(string $webhookUrl, ?string $secretToken = null): array
    {
        return $this->setCsWebhook($webhookUrl, $secretToken);
    }

    public function setAdminWebhook(string $webhookUrl, ?string $secretToken = null): array
    {
        return $this->apiSetWebhook($this->adminToken, $webhookUrl, $secretToken);
    }

    public function setCsWebhook(string $webhookUrl, ?string $secretToken = null): array
    {
        return $this->apiSetWebhook($this->csToken, $webhookUrl, $secretToken);
    }

    protected function apiSetWebhook(?string $token, string $webhookUrl, ?string $secretToken = null): array
    {
        if (! $token) {
            return ['ok' => false, 'description' => 'Token bot Telegram belum dikonfigurasi.'];
        }

        try {
            $payload = [
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => false,
            ];

            if ($secretToken) {
                $payload['secret_token'] = $secretToken;
            }

            $res = Http::timeout(12)->post("https://api.telegram.org/bot{$token}/setWebhook", $payload);

            return $res->json() ?? ['ok' => false, 'description' => 'Gagal membaca respon Telegram API.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public function getWebhookInfo(): array
    {
        return $this->getCsWebhookInfo();
    }

    public function getAdminWebhookInfo(): array
    {
        return $this->apiGetWebhookInfo($this->adminToken);
    }

    public function getCsWebhookInfo(): array
    {
        return $this->apiGetWebhookInfo($this->csToken);
    }

    protected function apiGetWebhookInfo(?string $token): array
    {
        if (! $token) {
            return ['ok' => false, 'description' => 'Token bot Telegram belum dikonfigurasi.'];
        }

        try {
            $res = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getWebhookInfo");

            return $res->json() ?? ['ok' => false, 'description' => 'Gagal membaca info webhook.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public function deleteWebhook(): array
    {
        return $this->deleteCsWebhook();
    }

    public function deleteAdminWebhook(): array
    {
        return $this->apiDeleteWebhook($this->adminToken);
    }

    public function deleteCsWebhook(): array
    {
        return $this->apiDeleteWebhook($this->csToken);
    }

    protected function apiDeleteWebhook(?string $token): array
    {
        if (! $token) {
            return ['ok' => false, 'description' => 'Token bot Telegram belum dikonfigurasi.'];
        }

        try {
            $res = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/deleteWebhook");

            return $res->json() ?? ['ok' => false, 'description' => 'Gagal menghapus webhook.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public function sendMessage(string|int $chatId, string $text, ?array $replyMarkup = null): bool
    {
        return $this->sendCsMessage($chatId, $text, $replyMarkup);
    }

    public function sendAdminMessage(string|int $chatId, string $text, ?array $replyMarkup = null): bool
    {
        return $this->apiSendMessage($this->adminToken ?? $this->csToken, $chatId, $text, $replyMarkup);
    }

    public function sendCsMessage(string|int $chatId, string $text, ?array $replyMarkup = null): bool
    {
        return $this->apiSendMessage($this->csToken ?? $this->adminToken, $chatId, $text, $replyMarkup);
    }

    protected function apiSendMessage(?string $token, string|int $chatId, string $text, ?array $replyMarkup = null): bool
    {
        if (! $token) {
            return false;
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $res = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

            return $res->successful() && $res->json('ok');
        } catch (Throwable $e) {
            Log::warning('[TelegramBot] sendMessage error: '.$e->getMessage());

            return false;
        }
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): bool
    {
        $token = $this->csToken ?? $this->adminToken;
        if (! $token) {
            return false;
        }

        try {
            $payload = ['callback_query_id' => $callbackQueryId];
            if ($text) {
                $payload['text'] = $text;
                $payload['show_alert'] = $showAlert;
            }

            $res = Http::timeout(8)->post("https://api.telegram.org/bot{$token}/answerCallbackQuery", $payload);

            return $res->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Handle incoming webhook updates for Admin Bot.
     */
    public function handleAdminUpdate(array $update): void
    {
        if (isset($update['message'])) {
            $this->handleAdminMessage($update['message']);
        }
    }

    /**
     * Process message sent to Admin Bot. Automatically registers the admin's Chat ID.
     */
    protected function handleAdminMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $from = $message['from'] ?? [];
        $text = trim($message['text'] ?? '');
        $firstName = $from['first_name'] ?? 'Admin';
        $lastName = $from['last_name'] ?? '';
        $senderName = trim("{$firstName} {$lastName}") ?: 'Admin';

        if (! $chatId) {
            return;
        }

        // Auto-register Chat ID to system settings
        $isNew = $this->telegramService->addChatId((string) $chatId);
        // Refresh local cache of chat IDs
        $this->adminChatIds = $this->telegramService->getChatIds();

        $cinemaName = $this->settings->siteName() ?: 'SynemaX';
        $siteUrl = config('app.url', 'https://synemax.web.id');

        if (str_starts_with($text, '/status')) {
            $pendingBookings = Booking::whereIn('status', ['menunggu_pembayaran', 'menunggu_konfirmasi'])->count();
            $todayBookings = Booking::whereDate('created_at', today())->count();

            $reply = "<b>[STATUS KONTROL {$cinemaName}]</b>\n\n"
                ."• <b>Sistem:</b> Online & Beroperasi\n"
                ."• <b>Website:</b> {$siteUrl}\n"
                ."• <b>Chat ID Anda:</b> <code>{$chatId}</code>\n"
                ."• <b>Booking Perlu Tindakan:</b> {$pendingBookings} pesanan\n"
                ."• <b>Total Booking Hari Ini:</b> {$todayBookings} pesanan\n\n"
                .'<i>Bot kontrol siap mengirimkan setiap update real-time ke akun ini.</i>';

            $this->sendAdminMessage($chatId, $reply, [
                'inline_keyboard' => [
                    [
                        ['text' => '📊 Buka Dashboard Kelola Booking', 'url' => "{$siteUrl}/admin/booking"],
                    ],
                ],
            ]);

            return;
        }

        // Welcome / Start message
        $reply = "<b>[BOT KONTROL ADMIN {$cinemaName}]</b>\n\n"
            ."Halo <b>{$senderName}</b>,\n\n"
            ."🆔 <b>Chat ID Anda:</b> <code>{$chatId}</code>\n"
            .($isNew
                ? "✅ <b>Chat ID Anda telah otomatis terdaftar ke notifikasi sistem!</b>\n\n"
                : "✅ <b>Chat ID Anda sudah aktif terhubung ke notifikasi sistem.</b>\n\n")
            ."Anda akan menerima notifikasi otomatis langsung di sini saat:\n"
            ."• Pesanan tiket baru masuk\n"
            ."• Penonton mengunggah bukti transfer\n"
            ."• Pembayaran dikonfirmasi kasir/admin\n"
            ."• Sesi nonton mencapai kuorum & siap tayang\n"
            ."• Penonton melapor keluhan atau kendala di Bot CS (@SynemaXChat_bot)\n\n"
            .'Ketik <code>/status</code> kapan saja untuk melihat status operasional bioskop.';

        $this->sendAdminMessage($chatId, $reply, [
            'inline_keyboard' => [
                [
                    ['text' => '🎟️ Kelola Booking', 'url' => "{$siteUrl}/admin/booking"],
                    ['text' => '💬 Bot Keluhan & Feedback', 'url' => "{$siteUrl}/admin/bot-feedbacks"],
                ],
                [
                    ['text' => '⚙️ Pengaturan Notifikasi', 'url' => "{$siteUrl}/admin/pengaturan?tab=notifikasi"],
                ],
            ],
        ]);
    }

    /**
     * Process an incoming update from Telegram Webhook or Polling.
     */
    public function handleUpdate(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);

            return;
        }

        if (isset($update['message'])) {
            $this->handleMessage($update['message']);

            return;
        }
    }

    protected function handleCallbackQuery(array $query): void
    {
        $queryId = $query['id'];
        $data = $query['data'] ?? '';
        $from = $query['from'] ?? [];
        $chatId = $query['message']['chat']['id'] ?? ($from['id'] ?? null);
        $firstName = $from['first_name'] ?? 'Penonton SynemaX';

        $this->answerCallbackQuery($queryId);

        if (! $chatId) {
            return;
        }

        switch ($data) {
            case 'menu_main':
                $this->sendMainMenu($chatId, $firstName);
                break;

            case 'menu_check_booking':
                $text = "🔍 <b>Pengecekan Status Booking Tiket & Sesi Real-Time</b>\n\n"
                    ."Silakan ketik atau salin <b>Kode Booking</b> Anda langsung di chat ini.\n\n"
                    ."<b>Contoh format:</b>\n"
                    ."<code>HC-261123-2D60FU</code>\n\n"
                    .'<i>Bot akan langsung memeriksa status pembayaran, jadwal tayang, ketersediaan kursi, dan link e-tiket Anda secara instan tanpa perlu menunggu admin.</i>';
                $this->sendMessage($chatId, $text, [
                    'inline_keyboard' => [
                        [['text' => '◀️ Kembali ke Menu Utama', 'callback_data' => 'menu_main']],
                    ],
                ]);
                break;

            case 'menu_check_snacks':
                $this->sendSnacksList($chatId);
                break;

            case 'menu_report_issue':
                $text = "⚠️ <b>Layanan Lapor Keluhan & Kendala Nonton</b>\n\n"
                    .'Pilih kategori kendala yang Anda alami untuk mendapatkan panduan cepat dan pembuatan tiket laporan langsung ke admin:';
                $keyboard = [
                    'inline_keyboard' => [
                        [['text' => '💳 Pembayaran / Bukti Belum Di-ACC', 'callback_data' => 'issue_payment']],
                        [['text' => '🎟️ Kendala Tiket / Jadwal / Kursi', 'callback_data' => 'issue_ticket_schedule']],
                        [['text' => '🍿 Kendala Pesanan Jajan Bioskop', 'callback_data' => 'issue_snacks']],
                        [['text' => '🎬 Fasilitas Studio (AC, Layar, Audio)', 'callback_data' => 'issue_studio']],
                        [['text' => '📝 Keluhan Lainnya', 'callback_data' => 'issue_other']],
                        [['text' => '◀️ Kembali ke Menu Utama', 'callback_data' => 'menu_main']],
                    ],
                ];
                $this->sendMessage($chatId, $text, $keyboard);
                break;

            case 'issue_payment':
                $text = "💳 <b>Solusi: Verifikasi Bukti Pembayaran</b>\n\n"
                    ."1. Konfirmasi transfer diproses oleh admin/kasir secara berkala.\n"
                    ."2. Pastikan nominal transfer Anda tepat sesuai tagihan pada invoice/tiket.\n"
                    ."3. Pastikan foto bukti transfer terlihat jelas nama pengirim, nominal, dan tanggal.\n\n"
                    ."<i>Jika pembayaran belum diverifikasi lebih dari 15 menit, laporkan dengan format:</i>\n"
                    ."<code>KELUHAN [Kode Booking] Bukti transfer sudah dikirim mohon di-ACC</code>\n\n"
                    .'<i>Bot akan langsung meneruskan pesan prioritas Anda ke dashboard admin.</i>';
                $this->sendMessage($chatId, $text, [
                    'inline_keyboard' => [
                        [['text' => '📝 Ketik Laporan Keluhan', 'callback_data' => 'issue_other']],
                        [['text' => '◀️ Kembali ke Menu Kendala', 'callback_data' => 'menu_report_issue']],
                    ],
                ]);
                break;

            case 'issue_ticket_schedule':
                $text = "🎟️ <b>Kendala Tiket / Jadwal Tayang / Kursi</b>\n\n"
                    ."1. Anda dapat melihat tiket aktif kapan saja dengan mengetikkan kode booking Anda di chat ini.\n"
                    ."2. Kuorum penonton: Sesi reguler siap tayang jika kuorum kursi terpenuhi.\n"
                    ."3. Jika Anda berhalangan hadir atau ingin ubah jadwal, laporkan dengan format:\n\n"
                    .'<code>KELUHAN [Kode Booking] Permohonan ubah jadwal sesi / kendala tiket</code>';
                $this->sendMessage($chatId, $text, [
                    'inline_keyboard' => [
                        [['text' => '🔍 Cek Status Booking', 'callback_data' => 'menu_check_booking']],
                        [['text' => '◀️ Kembali ke Menu Kendala', 'callback_data' => 'menu_report_issue']],
                    ],
                ]);
                break;

            case 'issue_snacks':
                $text = "🍿 <b>Kendala Pesanan Jajanan Bioskop</b>\n\n"
                    ."1. Pesanan jajan disiapkan di studio loket dan dapat diambil sebelum sesi dimulai.\n"
                    ."2. Jika ada menu yang habis atau ingin tambah pesanan jajan, laporkan dengan format:\n\n"
                    .'<code>KELUHAN [Kode Booking] Kendala pesanan jajan...</code>';
                $this->sendMessage($chatId, $text, [
                    'inline_keyboard' => [
                        [['text' => '🍿 Cek Menu Jajan', 'callback_data' => 'menu_check_snacks']],
                        [['text' => '◀️ Kembali ke Menu Kendala', 'callback_data' => 'menu_report_issue']],
                    ],
                ]);
                break;

            case 'issue_studio':
                $cinemaName = $this->settings->siteName() ?: 'SynemaX';
                $text = "🎬 <b>Fasilitas Studio {$cinemaName}</b>\n\n"
                    ."• Ruang studio berpendingin udara (AC dingin eksklusif)\n"
                    ."• Tata suara Dolby Cinematic Surround Sound\n"
                    ."• Sofa empuk nyaman tipe Reguler & Couple Velvet\n"
                    ."• Kapasitas privat 6-10 kursi per sesi\n\n"
                    ."<i>Jika ada kritik atau masukan kenyamanan studio saat sesi berlangsung, laporkan langsung:</i>\n"
                    .'<code>KELUHAN [Kode Booking] AC kurang dingin / suara sound...</code>';
                $this->sendMessage($chatId, $text, [
                    'inline_keyboard' => [
                        [['text' => '📝 Ketik Laporan Keluhan', 'callback_data' => 'issue_other']],
                        [['text' => '◀️ Kembali ke Menu Utama', 'callback_data' => 'menu_main']],
                    ],
                ]);
                break;

            case 'issue_other':
                $text = "📝 <b>Lapor Keluhan ke Manajemen SynemaX</b>\n\n"
                    ."Silakan ketik detail keluhan atau kendala Anda secara langsung di chat ini.\n\n"
                    ."<b>Format saran:</b>\n"
                    ."<code>KELUHAN [Kode Booking] Penjelasan kendala Anda...</code>\n\n"
                    ."<b>Contoh:</b>\n"
                    ."<code>KELUHAN HC-261123-2D60FU Mau konfirmasi pembayaran transfer bank</code>\n\n"
                    .'<i>Sistem akan otomatis mencatat tiket laporan dan langsung mengirim notifikasi darurat ke admin SynemaX.</i>';
                $this->sendMessage($chatId, $text, [
                    'inline_keyboard' => [
                        [['text' => '◀️ Kembali ke Menu Utama', 'callback_data' => 'menu_main']],
                    ],
                ]);
                break;

            case 'menu_feedback':
                $text = "💬 <b>Kirim Masukan, Review, & Usulan Film</b>\n\n"
                    ."Tuliskan kritik, saran pengalaman menonton, atau permohonan judul film yang ingin diputar langsung di chat ini.\n\n"
                    .'Pesan Anda akan otomatis direkam dan diteruskan ke pantauan admin SynemaX. Terima kasih telah membantu bioskop mini kami semakin baik!';
                $this->sendMessage($chatId, $text, [
                    'inline_keyboard' => [
                        [['text' => '◀️ Kembali ke Menu Utama', 'callback_data' => 'menu_main']],
                    ],
                ]);
                break;

            case 'menu_info':
                $this->sendCinemaInfo($chatId);
                break;

            default:
                $this->sendMainMenu($chatId, $firstName);
                break;
        }
    }

    protected function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $from = $message['from'] ?? [];
        $text = trim($message['text'] ?? '');
        $userId = isset($from['id']) ? (string) $from['id'] : null;
        $username = $from['username'] ?? null;
        $firstName = $from['first_name'] ?? 'Penonton SynemaX';
        $lastName = $from['last_name'] ?? '';
        $senderName = trim("{$firstName} {$lastName}") ?: 'Penonton Telegram';

        if (! $chatId || empty($text)) {
            return;
        }

        // 1. Command /start or /menu or /help
        if (str_starts_with($text, '/start') || str_starts_with($text, '/menu') || str_starts_with($text, '/help')) {
            $parts = explode(' ', $text, 2);
            if (isset($parts[1]) && ! empty(trim($parts[1]))) {
                $payload = trim($parts[1]);
                if (preg_match('/HC-\d{6}-[A-Za-z0-9]+/i', $payload, $matches)) {
                    $this->processBookingInquiry($chatId, strtoupper($matches[0]), $userId, $username, $senderName);

                    return;
                }
            }

            $this->sendMainMenu($chatId, $firstName);

            return;
        }

        // 2. Check for SynemaX Booking Code Pattern (e.g. HC-261123-2D60FU or HC-...)
        if (preg_match('/HC-\d{6}-[A-Za-z0-9]+/i', $text, $matches)) {
            $matchedCode = strtoupper($matches[0]);
            $lowerText = strtolower($text);

            $isComplaint = str_contains($lowerText, 'keluhan')
                || str_contains($lowerText, 'kendala')
                || str_contains($lowerText, 'lapor')
                || str_contains($lowerText, 'komplain')
                || str_contains($lowerText, 'batal')
                || str_contains($lowerText, 'salah')
                || str_contains($lowerText, 'belum di-acc')
                || str_contains($lowerText, 'belum acc')
                || str_contains($lowerText, 'gagal')
                || str_contains($lowerText, 'error');

            if ($isComplaint) {
                $this->processIssueReport($chatId, $text, $matchedCode, $userId, $username, $senderName);

                return;
            }

            $this->processBookingInquiry($chatId, $matchedCode, $userId, $username, $senderName);

            return;
        }

        // 3. Keyword check for Complaints / Issues without code
        $lowerText = strtolower($text);
        if (
            str_starts_with($lowerText, 'keluhan') ||
            str_starts_with($lowerText, 'kendala') ||
            str_starts_with($lowerText, 'lapor') ||
            str_starts_with($lowerText, 'komplain') ||
            str_contains($lowerText, 'belum di-acc') ||
            str_contains($lowerText, 'bukti bayar belum') ||
            str_contains($lowerText, 'minta batal') ||
            str_contains($lowerText, 'refund')
        ) {
            $this->processIssueReport($chatId, $text, null, $userId, $username, $senderName);

            return;
        }

        // 4. Greetings / Help words
        if (in_array($lowerText, ['halo', 'hi', 'hai', 'p', 'tes', 'test', 'bantuan', 'min', 'admin', 'assalamualaikum', 'siang', 'malam', 'pagi'], true)) {
            $this->sendMainMenu($chatId, $firstName);

            return;
        }

        // 5. General message / Feedback
        $this->processGeneralFeedback($chatId, $text, $userId, $username, $senderName);
    }

    protected function sendMainMenu(string|int $chatId, string $name): void
    {
        $cinemaName = $this->settings->siteName() ?: 'SynemaX';
        $siteUrl = config('app.url', 'https://synemax.web.id');

        $text = "👋 <b>Halo, {$name}!</b>\n\n"
            ."Selamat datang di <b>Layanan Bantuan Otomatis {$cinemaName}</b>.\n\n"
            ."Bot ini aktif <b>24 jam non-stop</b> sebagai garda depan admin untuk melayani pengecekan tiket, jadwal sesi bioskop, info jajan, dan mencatat laporan keluhan Anda secara instan.\n\n"
            .'Silakan pilih menu bantuan di bawah ini:';

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔍 Cek Status Booking / Tiket', 'callback_data' => 'menu_check_booking'],
                    ['text' => '🍿 Cek Menu Jajanan', 'callback_data' => 'menu_check_snacks'],
                ],
                [
                    ['text' => '⚠️ Lapor Keluhan / Kendala', 'callback_data' => 'menu_report_issue'],
                    ['text' => '💬 Kirim Masukan / Usulan', 'callback_data' => 'menu_feedback'],
                ],
                [
                    ['text' => '📍 Lokasi & Info Studio', 'callback_data' => 'menu_info'],
                    ['text' => '🌐 Buka Website Bioskop', 'url' => $siteUrl],
                ],
            ],
        ];

        $this->sendMessage($chatId, $text, $keyboard);
    }

    protected function sendSnacksList(string|int $chatId): void
    {
        $snacks = MenuItem::where('is_available', true)->limit(10)->get();

        if ($snacks->isEmpty()) {
            $text = "🍿 <b>Menu Jajanan Bioskop</b>\n\n"
                .'Saat ini daftar camilan sedang diperbarui oleh kasir studio.';
        } else {
            $text = "🍿 <b>Daftar Menu Jajanan Favorit SynemaX</b>\n\n";
            foreach ($snacks as $item) {
                $price = 'Rp '.number_format($item->price, 0, ',', '.');
                $text .= "• <b>{$item->name}</b> — {$price}\n";
            }
            $text .= "\n<i>Nikmati camilan lezat selama sesi pemutaran film di studio mini!</i>";
        }

        $this->sendMessage($chatId, $text, [
            'inline_keyboard' => [
                [['text' => '◀️ Kembali ke Menu Utama', 'callback_data' => 'menu_main']],
            ],
        ]);
    }

    protected function sendCinemaInfo(string|int $chatId): void
    {
        $cinemaName = $this->settings->siteName() ?: 'SynemaX';
        $address = $this->settings->contactAddress() ?: 'Jl. Desa Panyingkiran Blok Mawar, RT.09/RW.05, Majalengka, Jawa Barat 45459';
        $phone = $this->settings->contactPhone() ?: $this->settings->contactWhatsapp() ?: '6285169730960';
        $mapsUrl = $this->settings->contactMapsUrl();

        $text = "📍 <b>Informasi Bioskop {$cinemaName}</b>\n\n"
            ."• <b>Alamat:</b> {$address}\n"
            ."• <b>WhatsApp/Kontak:</b> {$phone}\n"
            ."• <b>Fasilitas:</b> Studio Mini Privat Berpendingin Udara (AC), Sofa Empuk, Layar Sinematik, Audio Dolby Surround\n"
            ."• <b>Kapasitas:</b> 6 - 10 Kursi Eksklusif\n\n"
            .'<i>Harap hadir di studio minimal 10-15 menit sebelum jam pemutaran film dimulai.</i>';

        $buttons = [];
        if ($mapsUrl) {
            $buttons[] = [['text' => '🗺️ Buka Google Maps', 'url' => $mapsUrl]];
        }
        $buttons[] = [['text' => '◀️ Kembali ke Menu Utama', 'callback_data' => 'menu_main']];

        $this->sendMessage($chatId, $text, ['inline_keyboard' => $buttons]);
    }

    protected function processBookingInquiry(
        string|int $chatId,
        string $bookingCode,
        ?string $userId,
        ?string $username,
        string $senderName
    ): void {
        $booking = Booking::with(['session.film', 'film', 'items.menuItem'])
            ->where('code', $bookingCode)
            ->first();

        $siteUrl = config('app.url', 'https://synemax.web.id');
        $ticketUrl = "{$siteUrl}/tiket/{$bookingCode}";

        if (! $booking) {
            $text = "🔍 <b>Booking Tidak Ditemukan</b>\n\n"
                ."Kode booking <code>{$bookingCode}</code> tidak terdaftar di sistem {$siteUrl}.\n\n"
                .'Pastikan kode booking yang Anda masukkan sesuai format (contoh: <code>HC-261123-2D60FU</code>).';

            $this->sendMessage($chatId, $text, [
                'inline_keyboard' => [
                    [['text' => '🔄 Coba Cek Lagi', 'callback_data' => 'menu_check_booking']],
                    [['text' => '◀️ Menu Utama', 'callback_data' => 'menu_main']],
                ],
            ]);

            return;
        }

        $filmTitle = $booking->film?->title ?? $booking->session?->film?->title ?? 'Film Studio';
        $sessionDate = $booking->session?->session_date ? format_hari_tanggal_id($booking->session->session_date) : '-';
        $sessionTime = $booking->session?->start_time ? substr($booking->session->start_time, 0, 5).' WIB' : '-';
        $paxCount = $booking->pax ?? $booking->people_count ?? 1;

        $packageLabel = match ($booking->booking_type) {
            'couple' => 'Couple Velvet (2 Orang)',
            'private' => 'Private Studio VIP',
            default => "Reguler ({$paxCount} Orang)",
        };

        $statusLabel = match ($booking->status) {
            'menunggu_pembayaran' => '⏳ Menunggu Pembayaran',
            'menunggu_konfirmasi' => '🔄 Menunggu Verifikasi Admin',
            'terkonfirmasi' => '✅ Terkonfirmasi (Tiket Aktif)',
            'hadir' => '🎟️ Telah Hadir di Studio',
            'kedaluwarsa' => '⌛ Kedaluwarsa',
            'dibatalkan' => '❌ Dibatalkan',
            default => $booking->status,
        };

        $totalFormatted = 'Rp '.number_format($booking->total_price, 0, ',', '.');

        $reply = "🎟️ <b>Rincian Booking #{$booking->code}</b>\n\n"
            ."• <b>Film:</b> {$filmTitle}\n"
            ."• <b>Jadwal:</b> {$sessionDate} · {$sessionTime}\n"
            ."• <b>Paket:</b> {$packageLabel}\n"
            ."• <b>Atas Nama:</b> {$booking->customer_name}\n"
            ."• <b>Total:</b> {$totalFormatted}\n"
            ."• <b>Status:</b> <b>{$statusLabel}</b>\n";

        if ($booking->items && $booking->items->isNotEmpty()) {
            $reply .= "\n🍿 <b>Pesanan Jajanan:</b>\n";
            foreach ($booking->items as $it) {
                $name = $it->menuItem?->name ?? 'Menu';
                $reply .= " - {$it->qty}x {$name}\n";
            }
        }

        if ($booking->status === 'menunggu_pembayaran') {
            $reply .= "\nℹ️ <i>Silakan selesaikan pembayaran dan unggah bukti transfer di website agar tempat duduk Anda segera dikonfirmasi.</i>\n";
        } elseif ($booking->status === 'menunggu_konfirmasi') {
            $reply .= "\nℹ️ <i>Bukti pembayaran Anda sudah masuk dan sedang diperiksa oleh admin/kasir bioskop. Tiket Anda akan aktif segera.</i>\n";
        } elseif ($booking->status === 'terkonfirmasi') {
            $reply .= "\nℹ️ <i>Tiket Anda sudah terkonfirmasi! Harap hadir di studio minimal 10-15 menit sebelum jam tayang.</i>\n";
        }

        $reply .= "\n📄 <b>Halaman Tiket Online:</b>\n{$ticketUrl}";

        // Record feedback
        BotFeedback::create([
            'telegram_user_id' => $userId,
            'telegram_username' => $username,
            'sender_name' => $senderName,
            'category' => 'ORDER_INQUIRY',
            'booking_code' => $bookingCode,
            'booking_id' => $booking->id,
            'message' => "Cek status booking: {$bookingCode}",
            'bot_response' => "Status: {$booking->status} ({$filmTitle})",
            'status' => 'AUTO_RESOLVED',
        ]);

        $this->sendMessage($chatId, $reply, [
            'inline_keyboard' => [
                [
                    ['text' => '🎫 Buka Tiket di Web', 'url' => $ticketUrl],
                ],
                [
                    ['text' => '⚠️ Lapor Kendala Booking Ini', 'callback_data' => 'menu_report_issue'],
                    ['text' => '◀️ Menu Utama', 'callback_data' => 'menu_main'],
                ],
            ],
        ]);
    }

    protected function processIssueReport(
        string|int $chatId,
        string $messageText,
        ?string $bookingCode,
        ?string $userId,
        ?string $username,
        string $senderName
    ): void {
        $booking = null;
        if ($bookingCode) {
            $booking = Booking::where('code', $bookingCode)->first();
        }

        $feedback = BotFeedback::create([
            'telegram_user_id' => $userId,
            'telegram_username' => $username,
            'sender_name' => $senderName,
            'category' => 'ISSUE_REPORT',
            'booking_code' => $bookingCode,
            'booking_id' => $booking?->id,
            'message' => $messageText,
            'bot_response' => 'Laporan keluhan dicatat otomatis dan diteruskan ke admin pantauan.',
            'status' => 'AUTO_RESOLVED',
        ]);

        $ticketCode = 'TKT-'.str_pad((string) $feedback->id, 5, '0', STR_PAD_LEFT);

        $reply = "✅ <b>Laporan Keluhan Anda Berhasil Dicatat!</b>\n\n"
            ."• <b>Nomor Tiket:</b> <code>#{$ticketCode}</code>\n"
            .($bookingCode ? "• <b>Kode Booking:</b> <code>{$bookingCode}</code>\n" : '')
            .'• <b>Waktu:</b> '.now()->format('d M Y, H:i')." WIB\n\n"
            ."🤖 <b>Penanganan Sistem:</b>\n"
            ."Laporan Anda telah disimpan ke sistem dan <b>otomatis diteruskan ke notifikasi admin SynemaX untuk dipantau secara langsung</b>.\n\n"
            .'Admin akan segera meninjau tiket laporan ini jika memerlukan tindakan verifikasi manual pada reservasi Anda.\n\n'
            .'<i>Terima kasih atas laporan Anda. Tim SynemaX berkomitmen menjaga kenyamanan Anda menonton!</i>';

        $this->sendMessage($chatId, $reply, [
            'inline_keyboard' => [
                [['text' => '🔍 Cek Status Booking', 'callback_data' => 'menu_check_booking']],
                [['text' => '◀️ Menu Utama', 'callback_data' => 'menu_main']],
            ],
        ]);

        // Dispatch instant alert to Admin Telegram Chat!
        $this->notifyAdminOfBuyerFeedback($feedback, $booking);
    }

    protected function processGeneralFeedback(
        string|int $chatId,
        string $messageText,
        ?string $userId,
        ?string $username,
        string $senderName
    ): void {
        $feedback = BotFeedback::create([
            'telegram_user_id' => $userId,
            'telegram_username' => $username,
            'sender_name' => $senderName,
            'category' => 'FEEDBACK',
            'message' => $messageText,
            'bot_response' => 'Terima kasih atas masukan Anda. Pesan telah dicatat ke sistem.',
            'status' => 'AUTO_RESOLVED',
        ]);

        $reply = "💬 <b>Terima kasih atas ulasan & masukan Anda!</b>\n\n"
            ."Pesan Anda telah berhasil dicatat oleh Bot SynemaX dan <b>otomatis diteruskan ke pantauan manajemen admin</b>.\n\n"
            .'Untuk melihat opsi bantuan lainnya, silakan klik menu di bawah:';

        $this->sendMessage($chatId, $reply, [
            'inline_keyboard' => [
                [
                    ['text' => '🔍 Cek Booking', 'callback_data' => 'menu_check_booking'],
                    ['text' => '⚠️ Lapor Kendala', 'callback_data' => 'menu_report_issue'],
                ],
                [['text' => '◀️ Menu Utama', 'callback_data' => 'menu_main']],
            ],
        ]);

        // Forward to admin
        $this->notifyAdminOfBuyerFeedback($feedback, null);
    }

    /**
     * Dispatch alert to Admin's Telegram Chat so admin stays completely informed without having to manually reply.
     */
    public function notifyAdminOfBuyerFeedback(BotFeedback $feedback, ?Booking $booking = null): void
    {
        if (empty($this->adminChatIds) || (empty($this->adminToken) && empty($this->csToken))) {
            return;
        }

        $siteUrl = config('app.url', 'https://synemax.web.id');
        $panelUrl = "{$siteUrl}/admin/bot-feedbacks";
        $bookingUrl = $booking ? "{$siteUrl}/admin/booking?q={$booking->code}" : null;

        $userDisplay = htmlspecialchars($feedback->sender_name ?: 'Penonton');
        if ($feedback->telegram_username) {
            $userDisplay .= " (@{$feedback->telegram_username})";
        }
        if ($feedback->telegram_user_id) {
            $userDisplay .= " [ID: <code>{$feedback->telegram_user_id}</code>]";
        }

        $categoryBadge = match ($feedback->category) {
            'ISSUE_REPORT' => '⚠️ KELUHAN / KENDALA',
            'ORDER_INQUIRY' => '🔍 CEK BOOKING',
            'FEEDBACK' => '💬 MASUKAN / SARAN',
            default => 'ℹ️ PESAN BANTUAN',
        };

        $msg = "📬 <b>[BOT CHAT - LAPORAN PENONTON MASUK]</b>\n\n"
            ."<b>Kategori:</b> {$categoryBadge}\n"
            ."<b>Pengirim:</b> {$userDisplay}\n";

        if ($feedback->booking_code) {
            $msg .= "<b>Kode Booking:</b> <code>{$feedback->booking_code}</code>\n";
        }

        $cleanText = htmlspecialchars($feedback->message);
        $msg .= "<b>Pesan Penonton:</b>\n<i>\"{$cleanText}\"</i>\n\n"
            ."🤖 <b>Status Penanganan:</b> Ditangani Bot Otomatis (Admin dapat memantau kapan saja)\n"
            .'📅 <b>Waktu:</b> '.now()->format('d M Y, H:i')." WIB\n";

        $buttons = [
            [['text' => '📊 Pantau di Admin Panel', 'url' => $panelUrl]],
        ];

        if ($bookingUrl) {
            $buttons[] = [['text' => '🎟️ Buka Booking #'.$feedback->booking_code, 'url' => $bookingUrl]];
        }

        foreach ($this->adminChatIds as $adminChatId) {
            $this->sendAdminMessage($adminChatId, $msg, ['inline_keyboard' => $buttons]);
        }
    }

    /**
     * Process message / action from Web In-App Chatbot Widget.
     * Zero daemon overhead, shared logic with Telegram bot, 24/7 automated responses.
     */
    public function processWebChatMessage(
        ?string $message = null,
        ?string $action = null,
        ?string $sessionId = null,
        ?string $senderName = null
    ): array {
        $cinemaName = $this->settings->siteName() ?: 'SynemaX';
        $siteUrl = config('app.url', 'https://synemax.web.id');
        $csBotUsername = $this->telegramService->getCsBotUsername();
        $telegramLink = "https://t.me/{$csBotUsername}";

        $cleanName = trim((string) $senderName) ?: 'Penonton Web';
        $cleanSession = trim((string) $sessionId) ?: 'WEB-'.substr(md5(request()->ip().request()->userAgent()), 0, 8);

        // 1. Handle Explicit Actions
        if ($action) {
            return $this->handleWebAction($action, $cleanName, $cleanSession, $cinemaName, $siteUrl, $telegramLink);
        }

        $text = trim((string) $message);
        if (empty($text) || in_array(strtolower($text), ['/start', '/menu', 'menu', 'bantuan', 'halo', 'hi', 'hai', 'p', 'tes', 'test', 'admin', 'min', 'assalamualaikum', 'siang', 'malam', 'pagi'], true)) {
            return $this->handleWebAction('menu_main', $cleanName, $cleanSession, $cinemaName, $siteUrl, $telegramLink);
        }

        // 2. Check for SynemaX Booking Code Pattern (e.g. HC-261123-2D60FU)
        if (preg_match('/HC-\d{6}-[A-Za-z0-9]+/i', $text, $matches)) {
            $matchedCode = strtoupper($matches[0]);
            $lowerText = strtolower($text);

            $isComplaint = str_contains($lowerText, 'keluhan')
                || str_contains($lowerText, 'kendala')
                || str_contains($lowerText, 'lapor')
                || str_contains($lowerText, 'komplain')
                || str_contains($lowerText, 'batal')
                || str_contains($lowerText, 'salah')
                || str_contains($lowerText, 'belum di-acc')
                || str_contains($lowerText, 'belum acc')
                || str_contains($lowerText, 'gagal')
                || str_contains($lowerText, 'error');

            if ($isComplaint) {
                return $this->handleWebIssueReport($text, $matchedCode, $cleanName, $cleanSession, $siteUrl);
            }

            return $this->handleWebBookingInquiry($matchedCode, $cleanName, $cleanSession, $siteUrl);
        }

        // 3. Check for Complaint Keywords without booking code
        $lowerText = strtolower($text);
        if (
            str_starts_with($lowerText, 'keluhan') ||
            str_starts_with($lowerText, 'kendala') ||
            str_starts_with($lowerText, 'lapor') ||
            str_starts_with($lowerText, 'komplain') ||
            str_contains($lowerText, 'belum di-acc') ||
            str_contains($lowerText, 'bukti bayar belum') ||
            str_contains($lowerText, 'minta batal') ||
            str_contains($lowerText, 'refund')
        ) {
            return $this->handleWebIssueReport($text, null, $cleanName, $cleanSession, $siteUrl);
        }

        // 4. Default: General Feedback / Question
        return $this->handleWebGeneralFeedback($text, $cleanName, $cleanSession);
    }

    protected function handleWebAction(
        string $action,
        string $name,
        string $sessionId,
        string $cinemaName,
        string $siteUrl,
        string $telegramLink
    ): array {
        switch ($action) {
            case 'menu_main':
                return [
                    'ok' => true,
                    'reply' => "<b>Selamat datang di Layanan Bantuan {$cinemaName}</b><br><br>Kami siap membantu Anda untuk pengecekan status booking tiket, jadwal sesi bioskop, info menu jajanan, dan mencatat laporan keluhan Anda secara instan 24 jam.<br><br>Silakan pilih menu bantuan di bawah atau ketik langsung pesan Anda:",
                    'quick_replies' => [
                        ['label' => 'Cek Status Booking Tiket', 'action' => 'menu_check_booking'],
                        ['label' => 'Menu Jajanan Bioskop', 'action' => 'menu_check_snacks'],
                        ['label' => 'Lapor Kendala & Keluhan', 'action' => 'menu_report_issue'],
                        ['label' => 'Info Lokasi & Studio', 'action' => 'menu_info'],
                        ['label' => 'Kirim Masukan / Usulan', 'action' => 'menu_feedback'],
                    ],
                ];

            case 'menu_check_booking':
                return [
                    'ok' => true,
                    'reply' => '<b>Pengecekan Status Booking Tiket</b><br><br>Silakan ketik atau salin <b>Kode Booking</b> Anda di kolom pesan.<br><br><b>Contoh:</b><br><code>HC-261123-2D60FU</code><br><br><i>Sistem akan langsung menampilkan status pembayaran, ketersediaan kursi, jadwal sesi, dan link e-tiket Anda tanpa perlu menunggu admin.</i>',
                    'quick_replies' => [
                        ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
                    ],
                ];

            case 'menu_check_snacks':
                $snacks = MenuItem::where('is_available', true)->limit(10)->get();
                if ($snacks->isEmpty()) {
                    $reply = '<b>Menu Jajanan Bioskop</b><br><br>Saat ini daftar camilan sedang diperbarui oleh kasir studio.';
                } else {
                    $reply = "<b>Daftar Menu Jajanan Favorit {$cinemaName}</b><br><br>";
                    foreach ($snacks as $item) {
                        $price = 'Rp '.number_format($item->price, 0, ',', '.');
                        $reply .= "• <b>{$item->name}</b> — {$price}<br>";
                    }
                    $reply .= '<br><i>Nikmati camilan lezat selama sesi pemutaran film di studio mini!</i>';
                }

                return [
                    'ok' => true,
                    'reply' => $reply,
                    'quick_replies' => [
                        ['label' => 'Cek Status Booking', 'action' => 'menu_check_booking'],
                        ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
                    ],
                ];

            case 'menu_info':
                $address = $this->settings->contactAddress() ?: 'Jl. Desa Panyingkiran Blok Mawar, RT.09/RW.05, Majalengka, Jawa Barat 45459';
                $phone = $this->settings->contactPhone() ?: $this->settings->contactWhatsapp() ?: '6285169730960';
                $mapsUrl = $this->settings->contactMapsUrl();

                $reply = "<b>Informasi Bioskop {$cinemaName}</b><br><br>"
                    ."• <b>Alamat:</b> {$address}<br>"
                    ."• <b>WhatsApp/Kontak:</b> {$phone}<br>"
                    .'• <b>Fasilitas:</b> Studio Mini Privat Berpendingin Udara (AC), Sofa Empuk, Layar Sinematik, Audio Dolby Surround<br>'
                    .'• <b>Kapasitas:</b> 6 - 10 Kursi Eksklusif<br><br>'
                    .'<i>Harap hadir di studio minimal 10-15 menit sebelum jam tayang dimulai.</i>';

                $replies = [];
                if ($mapsUrl) {
                    $replies[] = ['label' => 'Buka Google Maps', 'url' => $mapsUrl];
                }
                $replies[] = ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'];

                return [
                    'ok' => true,
                    'reply' => $reply,
                    'quick_replies' => $replies,
                ];

            case 'menu_report_issue':
                return [
                    'ok' => true,
                    'reply' => '<b>Pusat Bantuan & Lapor Kendala Nonton</b><br><br>Silakan pilih kategori kendala yang Anda alami untuk mendapatkan panduan cepat dan pembuatan tiket laporan:',
                    'quick_replies' => [
                        ['label' => 'Pembayaran Belum Di-ACC', 'action' => 'issue_payment'],
                        ['label' => 'Kendala Tiket / Jadwal / Kursi', 'action' => 'issue_ticket_schedule'],
                        ['label' => 'Pesanan Jajanan Bioskop', 'action' => 'issue_snacks'],
                        ['label' => 'Fasilitas Studio (AC, Layar, Audio)', 'action' => 'issue_studio'],
                        ['label' => 'Kendala Lainnya', 'action' => 'issue_other'],
                        ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
                    ],
                ];

            case 'issue_payment':
                return [
                    'ok' => true,
                    'reply' => '<b>Solusi: Verifikasi Bukti Pembayaran</b><br><br>1. Konfirmasi transfer diproses oleh admin/kasir secara berkala.<br>2. Pastikan nominal transfer Anda tepat sesuai tagihan pada tiket.<br>3. Pastikan foto bukti transfer terlihat jelas nama pengirim, nominal, dan tanggal.<br><br><i>Jika pembayaran belum diverifikasi lebih dari 15 menit, ketik laporan Anda:</i><br><code>KELUHAN [Kode Booking] Bukti transfer sudah dikirim mohon di-ACC</code>',
                    'quick_replies' => [
                        ['label' => 'Ketik Laporan Sekarang', 'action' => 'issue_other'],
                        ['label' => 'Kembali ke Pilihan Kendala', 'action' => 'menu_report_issue'],
                        ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
                    ],
                ];

            case 'issue_ticket_schedule':
                return [
                    'ok' => true,
                    'reply' => '<b>Kendala Tiket / Jadwal Tayang / Kursi</b><br><br>1. Anda dapat melihat tiket aktif kapan saja dengan mengetikkan kode booking Anda.<br>2. Kuorum penonton: Sesi reguler siap tayang jika kuorum kursi terpenuhi.<br><br><i>Jika Anda berhalangan hadir atau ingin ubah jadwal:</i><br><code>KELUHAN [Kode Booking] Permohonan ubah jadwal sesi / kendala tiket</code>',
                    'quick_replies' => [
                        ['label' => 'Cek Status Booking', 'action' => 'menu_check_booking'],
                        ['label' => 'Ketik Laporan Sekarang', 'action' => 'issue_other'],
                        ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
                    ],
                ];

            case 'issue_snacks':
                return [
                    'ok' => true,
                    'reply' => '<b>Kendala Pesanan Jajanan Bioskop</b><br><br>1. Pesanan jajan disiapkan di studio loket dan dapat diambil sebelum sesi dimulai.<br>2. Jika ada menu yang habis atau ingin tambah pesanan jajan:<br><code>KELUHAN [Kode Booking] Kendala pesanan jajan...</code>',
                    'quick_replies' => [
                        ['label' => 'Cek Menu Jajan', 'action' => 'menu_check_snacks'],
                        ['label' => 'Ketik Laporan Sekarang', 'action' => 'issue_other'],
                        ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
                    ],
                ];

            case 'issue_studio':
                return [
                    'ok' => true,
                    'reply' => "<b>Fasilitas Studio {$cinemaName}</b><br><br>• Ruang studio berpendingin udara (AC dingin eksklusif)<br>• Tata suara Dolby Cinematic Surround Sound<br>• Sofa empuk nyaman tipe Reguler & Couple Velvet<br>• Kapasitas privat 6-10 kursi per sesi<br><br><i>Jika ada kritik atau masukan kenyamanan studio saat sesi berlangsung:</i><br><code>KELUHAN [Kode Booking] AC kurang dingin / suara sound...</code>",
                    'quick_replies' => [
                        ['label' => 'Ketik Laporan Sekarang', 'action' => 'issue_other'],
                        ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
                    ],
                ];

            case 'issue_other':
                return [
                    'ok' => true,
                    'reply' => "<b>Lapor Kendala ke Manajemen {$cinemaName}</b><br><br>Silakan ketik detail keluhan atau kendala Anda secara langsung di kolom pesan.<br><br><b>Format saran:</b><br><code>KELUHAN [Kode Booking] Penjelasan kendala Anda...</code><br><br><b>Contoh:</b><br><code>KELUHAN HC-261123-2D60FU Mau konfirmasi pembayaran transfer bank</code><br><br><i>Sistem akan otomatis mencatat tiket laporan dan langsung mengirim notifikasi darurat ke admin SynemaX.</i>",
                    'quick_replies' => [
                        ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
                    ],
                ];

            case 'menu_feedback':
                return [
                    'ok' => true,
                    'reply' => "<b>Kirim Masukan, Review, & Usulan Film</b><br><br>Tuliskan kritik, saran pengalaman menonton, atau usulan judul film yang ingin diputar langsung di kolom pesan.<br><br>Pesan Anda akan otomatis direkam dan diteruskan ke pantauan admin {$cinemaName}. Terima kasih!",
                    'quick_replies' => [
                        ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
                    ],
                ];

            default:
                return $this->handleWebAction('menu_main', $name, $sessionId, $cinemaName, $siteUrl, $telegramLink);
        }
    }

    protected function handleWebBookingInquiry(string $bookingCode, string $name, string $sessionId, string $siteUrl): array
    {
        $booking = Booking::with(['session.film', 'film', 'items.menuItem'])
            ->where('code', $bookingCode)
            ->first();

        $ticketUrl = "{$siteUrl}/tiket/{$bookingCode}";

        if (! $booking) {
            return [
                'ok' => true,
                'reply' => "<b>Booking Tidak Ditemukan</b><br><br>Kode booking <code>{$bookingCode}</code> tidak terdaftar di sistem.<br><br>Pastikan kode booking yang Anda masukkan sesuai format (contoh: <code>HC-261123-2D60FU</code>).",
                'quick_replies' => [
                    ['label' => 'Coba Cek Lagi', 'action' => 'menu_check_booking'],
                    ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
                ],
            ];
        }

        $filmTitle = $booking->film?->title ?? $booking->session?->film?->title ?? 'Film Studio';
        $sessionDate = $booking->session?->session_date ? format_hari_tanggal_id($booking->session->session_date) : '-';
        $sessionTime = $booking->session?->start_time ? substr($booking->session->start_time, 0, 5).' WIB' : '-';
        $paxCount = $booking->pax ?? $booking->people_count ?? 1;

        $packageLabel = match ($booking->booking_type) {
            'couple' => 'Couple Velvet (2 Orang)',
            'private' => 'Private Studio VIP',
            default => "Reguler ({$paxCount} Orang)",
        };

        $statusLabel = match ($booking->status) {
            'menunggu_pembayaran' => 'Menunggu Pembayaran',
            'menunggu_konfirmasi' => 'Menunggu Verifikasi Admin',
            'terkonfirmasi' => 'Terkonfirmasi (Tiket Aktif)',
            'hadir' => 'Telah Hadir di Studio',
            'kedaluwarsa' => 'Kedaluwarsa',
            'dibatalkan' => 'Dibatalkan',
            default => $booking->status,
        };

        $totalFormatted = 'Rp '.number_format($booking->total_price, 0, ',', '.');

        $reply = "<b>Rincian Booking #{$booking->code}</b><br><br>"
            ."• <b>Film:</b> {$filmTitle}<br>"
            ."• <b>Jadwal:</b> {$sessionDate} · {$sessionTime}<br>"
            ."• <b>Paket:</b> {$packageLabel}<br>"
            ."• <b>Atas Nama:</b> {$booking->customer_name}<br>"
            ."• <b>Total:</b> {$totalFormatted}<br>"
            ."• <b>Status:</b> <b>{$statusLabel}</b><br>";

        if ($booking->items && $booking->items->isNotEmpty()) {
            $reply .= '<br><b>Pesanan Jajanan:</b><br>';
            foreach ($booking->items as $it) {
                $itemTitle = $it->menuItem?->name ?? 'Menu';
                $reply .= " - {$it->qty}x {$itemTitle}<br>";
            }
        }

        if ($booking->status === 'menunggu_pembayaran') {
            $reply .= '<br><i>Silakan selesaikan pembayaran dan unggah bukti transfer di website agar tempat duduk Anda segera dikonfirmasi.</i><br>';
        } elseif ($booking->status === 'menunggu_konfirmasi') {
            $reply .= '<br><i>Bukti pembayaran Anda sudah masuk dan sedang diperiksa oleh admin/kasir bioskop. Tiket Anda akan aktif segera.</i><br>';
        } elseif ($booking->status === 'terkonfirmasi') {
            $reply .= '<br><i>Tiket Anda sudah terkonfirmasi! Harap hadir di studio minimal 10-15 menit sebelum jam tayang.</i><br>';
        }

        $reply .= "<br><b>Halaman Tiket Online:</b><br><a href='{$ticketUrl}' target='_blank' class='underline font-semibold'>{$ticketUrl}</a>";

        // Save BotFeedback
        BotFeedback::create([
            'telegram_user_id' => 'Web In-App Chat',
            'telegram_username' => $sessionId,
            'sender_name' => $name,
            'category' => 'ORDER_INQUIRY',
            'booking_code' => $bookingCode,
            'booking_id' => $booking->id,
            'message' => "Cek status booking via Web: {$bookingCode}",
            'bot_response' => "Status: {$booking->status} ({$filmTitle})",
            'status' => 'AUTO_RESOLVED',
        ]);

        return [
            'ok' => true,
            'reply' => $reply,
            'booking' => [
                'code' => $booking->code,
                'status' => $booking->status,
                'ticket_url' => $ticketUrl,
            ],
            'quick_replies' => [
                ['label' => 'Buka Tiket di Tab Baru', 'url' => $ticketUrl],
                ['label' => 'Lapor Kendala Booking Ini', 'action' => 'issue_other'],
                ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
            ],
        ];
    }

    protected function handleWebIssueReport(
        string $messageText,
        ?string $bookingCode,
        string $name,
        string $sessionId,
        string $siteUrl
    ): array {
        $booking = null;
        if ($bookingCode) {
            $booking = Booking::where('code', $bookingCode)->first();
        }

        $feedback = BotFeedback::create([
            'telegram_user_id' => 'Web In-App Chat',
            'telegram_username' => $sessionId,
            'sender_name' => $name,
            'category' => 'ISSUE_REPORT',
            'booking_code' => $bookingCode,
            'booking_id' => $booking?->id,
            'message' => $messageText,
            'bot_response' => 'Laporan keluhan dicatat via Web Chat dan diteruskan ke admin pantauan.',
            'status' => 'AUTO_RESOLVED',
        ]);

        $ticketCode = 'TKT-'.str_pad((string) $feedback->id, 5, '0', STR_PAD_LEFT);

        $reply = '<b>Laporan Keluhan Anda Berhasil Dicatat</b><br><br>'
            ."• <b>Nomor Tiket:</b> <code>#{$ticketCode}</code><br>"
            .($bookingCode ? "• <b>Kode Booking:</b> <code>{$bookingCode}</code><br>" : '')
            .'• <b>Waktu Laporan:</b> '.now()->format('d M Y, H:i').'<br><br>'
            .'<b>Tindak Lanjut Admin:</b><br>'
            .'Laporan Anda telah disimpan ke sistem dan <b>otomatis diteruskan ke notifikasi admin untuk dipantau secara langsung</b>.<br><br>'
            .'<i>Terima kasih atas laporan Anda. Tim SynemaX berkomitmen menjaga kenyamanan Anda menonton!</i>';

        // Dispatch instant alert to Admin Telegram Bot (@SynemaXAdmin_bot)
        $this->notifyAdminOfBuyerFeedback($feedback, $booking);

        return [
            'ok' => true,
            'reply' => $reply,
            'ticket' => [
                'id' => $feedback->id,
                'code' => $ticketCode,
            ],
            'quick_replies' => [
                ['label' => 'Cek Status Booking', 'action' => 'menu_check_booking'],
                ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
            ],
        ];
    }

    protected function handleWebGeneralFeedback(string $messageText, string $name, string $sessionId): array
    {
        $feedback = BotFeedback::create([
            'telegram_user_id' => 'Web In-App Chat',
            'telegram_username' => $sessionId,
            'sender_name' => $name,
            'category' => 'FEEDBACK',
            'message' => $messageText,
            'bot_response' => 'Terima kasih atas masukan Anda. Pesan telah dicatat ke sistem.',
            'status' => 'AUTO_RESOLVED',
        ]);

        $reply = '<b>Terima kasih atas pesan dan masukan Anda</b><br><br>'
            .'Pesan Anda telah berhasil dicatat dan diteruskan ke pantauan manajemen admin.<br><br>'
            .'Untuk bantuan lainnya, silakan pilih menu di bawah:';

        // Dispatch alert to Admin Telegram Bot (@SynemaXAdmin_bot)
        $this->notifyAdminOfBuyerFeedback($feedback, null);

        return [
            'ok' => true,
            'reply' => $reply,
            'quick_replies' => [
                ['label' => 'Cek Booking', 'action' => 'menu_check_booking'],
                ['label' => 'Lapor Kendala', 'action' => 'menu_report_issue'],
                ['label' => 'Kembali ke Menu Utama', 'action' => 'menu_main'],
            ],
        ];
    }
}
