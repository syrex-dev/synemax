<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CinemaSettings
{
    public const CACHE_KEY = 'synemax_settings_map';

    /**
     * Get all cached settings key => value.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $settings = Setting::query()->get();
            $map = [];
            foreach ($settings as $setting) {
                $val = $setting->value;
                if (is_string($val) && (str_starts_with($val, '[') || str_starts_with($val, '{'))) {
                    $decoded = json_decode($val, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $map[$setting->key] = $decoded;

                        continue;
                    }
                }
                $map[$setting->key] = $val;
            }

            return $map;
        });
    }

    /**
     * Get a setting by key with a fallback default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * Set a single setting value and invalidate the cache.
     */
    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        $serialized = is_array($value) || is_object($value)
            ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : (string) $value;

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $serialized, 'group' => $group]
        );

        $this->clearCache();
    }

    /**
     * Batch save multiple settings and clear cache once.
     *
     * @param  array<string, mixed>  $items
     */
    public function setMany(array $items, string $group = 'general'): void
    {
        foreach ($items as $key => $value) {
            $serialized = is_array($value) || is_object($value)
                ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : (string) $value;

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $serialized, 'group' => $group]
            );
        }

        $this->clearCache();
    }

    /**
     * Clear the cached settings map.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function integer(string $key, int $default): int
    {
        $val = $this->get($key, $default);

        return is_numeric($val) ? (int) $val : $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $val = $this->get($key, $default);

        return is_string($val) ? $val : (string) $default;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $val = $this->get($key, $default);

        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return list<string>
     */
    public function rawPaymentMethods(): array
    {
        $val = $this->get('enabled_payment_methods', ['di_tempat']);
        if (is_string($val)) {
            $val = json_decode($val, true);
        }

        if (! is_array($val)) {
            return ['di_tempat'];
        }

        return array_values(array_intersect($val, ['di_tempat', 'qris', 'transfer']));
    }

    public function hasValidQrisImage(): bool
    {
        $path = $this->string('qris_image_path');

        if ($path === '') {
            return false;
        }

        return Storage::disk('local')->exists($path);
    }

    public function qrisMerchantName(): string
    {
        return $this->string('qris_merchant_name', 'SynemaX');
    }

    public function qrisPaymentNotes(): string
    {
        return $this->string('qris_payment_notes');
    }

    public function qrisImageVersion(): string
    {
        return (string) $this->integer('qris_image_updated_at', 1);
    }

    /**
     * @return list<array{bank_name: string, account_number: string, account_holder: string}>
     */
    public function transferAccounts(): array
    {
        $val = $this->get('transfer_accounts', []);
        if (is_string($val)) {
            $val = json_decode($val, true);
        }

        if (! is_array($val)) {
            return [];
        }

        return array_values(array_filter($val, function ($item): bool {
            return is_array($item)
                && filled($item['bank_name'] ?? null)
                && filled($item['account_number'] ?? null)
                && filled($item['account_holder'] ?? null);
        }));
    }

    public function hasValidTransferAccounts(): bool
    {
        return count($this->transferAccounts()) > 0;
    }

    public function transferPaymentNotes(): string
    {
        return $this->string('transfer_payment_notes');
    }

    public function checkInOpenMinutesBefore(): int
    {
        return $this->integer('check_in_open_minutes_before', 60);
    }

    public function checkInCloseOption(): string
    {
        return $this->string('check_in_close_after', 'session_end');
    }

    public function checkInSoundEnabled(): bool
    {
        return $this->boolean('check_in_sound_enabled', false);
    }

    public function paymentTimeLimitMinutes(): int
    {
        return $this->integer('payment_time_limit_minutes', 30);
    }

    public function onlinePaymentCutoffMinutes(): int
    {
        return $this->integer('online_payment_cutoff_minutes', 20);
    }

    public function maxPaymentProofKb(): int
    {
        return $this->integer('max_payment_proof_kb', 4096);
    }

    public function cancellationCutoffHours(): int
    {
        return $this->integer('cancellation_cutoff_hours', 6);
    }

    /**
     * @return list<string>
     */
    public function enabledPaymentMethods(): array
    {
        $methods = $this->rawPaymentMethods();

        if (in_array('qris', $methods, true) && ! $this->hasValidQrisImage()) {
            $methods = array_values(array_diff($methods, ['qris']));
        }

        if (in_array('transfer', $methods, true) && ! $this->hasValidTransferAccounts()) {
            $methods = array_values(array_diff($methods, ['transfer']));
        }

        return $methods;
    }

    /**
     * @return list<string>
     */
    public function availablePaymentMethodsForSession(Session $session): array
    {
        $methods = $this->enabledPaymentMethods();
        $cutoff = $session->startsAt()->subMinutes($this->onlinePaymentCutoffMinutes());

        if (now()->greaterThanOrEqualTo($cutoff)) {
            $methods = array_values(array_intersect($methods, ['di_tempat']));
        }

        return $methods;
    }

    public function infoNobarEnabled(): bool
    {
        return $this->integer('info_nobar_enabled', 1) === 1;
    }

    // === TAB 1: IDENTITAS SITUS ===
    public function siteName(): string
    {
        return $this->string('site_name', 'SynemaX');
    }

    public function siteTagline(): string
    {
        return $this->string('site_tagline', 'Studio Bioskop Mini Privat');
    }

    public function siteLogo(): ?string
    {
        $val = $this->string('site_logo');

        return filled($val) ? $val : null;
    }

    public function siteLogoDark(): ?string
    {
        $val = $this->string('site_logo_dark');

        return filled($val) ? $val : null;
    }

    public function navbarBrandDisplay(): string
    {
        return $this->string('navbar_brand_display', 'logo_text');
    }

    public function siteFavicon(): string
    {
        return $this->string('site_favicon') ?: $this->string('favicon', 'favicon.svg');
    }

    public function siteFaviconUrl(): string
    {
        $custom = $this->string('site_favicon') ?: $this->string('favicon');
        if (! empty($custom)) {
            if (str_starts_with($custom, 'http://') || str_starts_with($custom, 'https://') || str_starts_with($custom, '//')) {
                return $custom;
            }

            return Storage::disk('public')->url($custom);
        }

        if (file_exists(public_path('favicon.svg'))) {
            return asset('favicon.svg');
        }

        return asset('icon.svg');
    }

    public function siteFaviconMime(): string
    {
        $url = $this->siteFaviconUrl();
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'ico' => 'image/x-icon',
            default => 'image/x-icon',
        };
    }

    public function siteOgImage(): ?string
    {
        $val = $this->string('site_og_image');

        return filled($val) ? $val : null;
    }

    public function navbarMenu(): array
    {
        $items = $this->get('navbar_menu');
        if (is_array($items) && count($items) > 0) {
            return array_map(function ($item) {
                if (! isset($item['key']) && isset($item['id'])) {
                    $item['key'] = $item['id'];
                }
                $key = $item['key'] ?? '';
                if (! isset($item['mobile_position'])) {
                    $item['mobile_position'] = in_array($key, ['home', 'schedule', 'film', 'lookup', 'booking_lookup'], true) ? 'bottom_bar' : 'menu';
                }
                if (! isset($item['short_label']) || $item['short_label'] === '') {
                    $item['short_label'] = in_array($key, ['lookup', 'booking_lookup'], true) ? 'Booking' : ($item['label'] ?? ucfirst($key));
                }

                return $item;
            }, $items);
        }

        return [
            ['key' => 'home', 'label' => 'Beranda', 'short_label' => 'Beranda', 'url' => '/', 'route' => 'home', 'mobile_position' => 'bottom_bar', 'is_visible' => true, 'order' => 1],
            ['key' => 'schedule', 'label' => 'Jadwal', 'short_label' => 'Jadwal', 'url' => '/jadwal', 'route' => 'schedule.index', 'mobile_position' => 'bottom_bar', 'is_visible' => true, 'order' => 2],
            ['key' => 'film', 'label' => 'Film', 'short_label' => 'Film', 'url' => '/film', 'route' => 'films.index', 'mobile_position' => 'bottom_bar', 'is_visible' => true, 'order' => 3],
            ['key' => 'nobar', 'label' => 'Nobar', 'short_label' => 'Nobar', 'url' => '/nobar', 'route' => 'nobar.index', 'mobile_position' => 'menu', 'is_visible' => true, 'order' => 4],
            ['key' => 'promo', 'label' => 'Promo', 'short_label' => 'Promo', 'url' => '/promo', 'route' => 'promos.index', 'mobile_position' => 'menu', 'is_visible' => true, 'order' => 5],
            ['key' => 'articles', 'label' => 'Kabar', 'short_label' => 'Kabar', 'url' => '/kabar', 'route' => 'articles.index', 'mobile_position' => 'menu', 'is_visible' => true, 'order' => 6],
            ['key' => 'lookup', 'label' => 'Cek booking', 'short_label' => 'Booking', 'url' => '/cek-booking', 'route' => 'booking.lookup.form', 'mobile_position' => 'bottom_bar', 'is_visible' => true, 'order' => 7],
        ];
    }

    // === TAB 3: TAMPILAN ===
    public function defaultTheme(): string
    {
        return $this->string('default_theme', 'system');
    }

    public function showThemeToggle(): bool
    {
        return $this->boolean('show_theme_toggle', true);
    }

    public function homeInfoChips(): array
    {
        $chips = $this->get('home_info_chips');
        if (is_array($chips) && count($chips) > 0) {
            return $chips;
        }

        return [
            ['icon' => '', 'text' => cinema_capacity_text(), 'is_visible' => true, 'order' => 1],
            ['icon' => '', 'text' => cinema_starting_price_text(), 'is_visible' => true, 'order' => 2, 'auto_price' => true],
            ['icon' => '', 'text' => 'Studio AC & tata suara sinematik', 'is_visible' => true, 'order' => 3],
        ];
    }

    public function homeFilmsCount(): int
    {
        return $this->integer('home_films_count', 8);
    }

    public function filmsSortOrder(): string
    {
        return $this->string('films_sort_order', 'year_desc');
    }

    public function homeFilmsMode(): string
    {
        return $this->string('home_films_mode', 'auto');
    }

    /**
     * @return list<int>
     */
    public function homeFeaturedFilmIds(): array
    {
        $val = $this->get('home_featured_film_ids', []);
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                $val = $decoded;
            }
        }

        if (! is_array($val)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $val), fn ($id) => $id > 0));
    }

    public function homeHeroInterval(): int
    {
        return $this->integer('home_hero_interval', 5);
    }

    public function homeHeroAutoplay(): bool
    {
        return $this->boolean('home_hero_autoplay', true);
    }

    // === TAB 4: OPERASIONAL ===
    public function capacityPerSession(): int
    {
        return $this->integer('capacity_per_session', 6);
    }

    public function maxSeatsPerBooking(): int
    {
        return $this->integer('max_seats_per_booking', 6);
    }

    public function minimumPublicViewers(): int
    {
        return $this->integer('minimum_public_viewers', 4);
    }

    public static function minimumViewers(): int
    {
        return app(self::class)->minimumPublicViewers();
    }

    public function quorum(): int
    {
        return self::minimumViewers();
    }

    public static function startingPrice(): int
    {
        $instance = app(self::class);
        $override = $instance->get('starting_price');
        if (filled($override) && is_numeric($override) && (int) $override > 0) {
            return (int) $override;
        }

        $prices = [
            $instance->weekdayPrice(),
            $instance->weekendPrice(),
            $instance->holidayPrice(),
        ];

        return min($prices);
    }

    public function weekdayPrice(): int
    {
        return $this->integer('weekday_price', 5000);
    }

    public function weekendPrice(): int
    {
        return $this->integer('weekend_price', 6000);
    }

    public function holidayPrice(): int
    {
        return $this->integer('holiday_price', 6000);
    }

    public function privatePrice(): int
    {
        return $this->integer('private_price', 36000);
    }

    public function agreementWindowMinutes(): int
    {
        return $this->integer('agreement_window_minutes', 180);
    }

    public function confirmationDeadlineMinutes(): int
    {
        return $this->integer('confirmation_deadline_minutes', 60);
    }

    public function ticketPriceForDayType(?string $dayType): int
    {
        return match ($dayType) {
            'libur' => $this->holidayPrice(),
            'weekend', 'jumat' => $this->weekendPrice(),
            default => $this->weekdayPrice(),
        };
    }

    public function couplePrice(?string $dayType = null): int
    {
        $isWeekend = in_array($dayType, ['weekend', 'jumat', 'libur'], true);
        $settingKey = $isWeekend ? 'couple_price_weekend' : 'couple_price_weekday';
        $altKey = $isWeekend ? 'couple_weekend_price' : 'couple_weekday_price';
        $override = $this->get($settingKey) ?: $this->get($altKey);

        if (! empty($override) && is_numeric($override) && (int) $override > 0) {
            return (int) $override;
        }

        return $this->minimumViewers() * $this->ticketPriceForDayType($dayType);
    }

    public function privatePriceForDayType(?string $dayType = null): int
    {
        $isWeekend = in_array($dayType, ['weekend', 'jumat', 'libur'], true);
        $settingKey = $isWeekend ? 'private_price_weekend' : 'private_price_weekday';
        $altKey = $isWeekend ? 'private_weekend_price' : 'private_weekday_price';
        $override = $this->get($settingKey) ?: $this->get($altKey);

        if (! empty($override) && is_numeric($override) && (int) $override > 0) {
            return (int) $override;
        }

        // If legacy private_price exists and is different from default 36000/30000, check it
        $legacy = $this->get('private_price');
        if (! $isWeekend && ! empty($legacy) && is_numeric($legacy) && (int) $legacy > 0 && (int) $legacy !== 36000) {
            return (int) $legacy;
        }

        return Session::MAX_CAPACITY * $this->ticketPriceForDayType($dayType);
    }

    public function bookingCutoffMinutes(): int
    {
        return $this->integer('booking_cutoff_minutes', 30);
    }

    public function maintenanceMode(): bool
    {
        return $this->boolean('maintenance_mode', false);
    }

    public function maintenanceMessage(): string
    {
        return $this->string('maintenance_message', 'Website kami sedang dalam pemeliharaan rutin. Silakan kembali beberapa saat lagi.');
    }

    // === TIKET ELEKTRONIK (E-TIKET) ===
    public function ticketArriveMinutes(): int
    {
        return $this->integer('ticket_arrive_minutes', 15);
    }

    public function ticketStudioName(): string
    {
        return $this->string('ticket_studio_name', 'Studio 1');
    }

    public function ticketNotes(): string
    {
        return $this->string('ticket_notes', 'Tunjukkan e-tiket ini kepada petugas di pintu masuk bioskop.');
    }

    // === TAB 5: HARGA & FAQ ===

    public function faqs(): array
    {
        $faqs = $this->get('faq_list');
        if (is_array($faqs) && count($faqs) > 0) {
            return $faqs;
        }

        return [
            ['q' => 'Bagaimana cara memesan tiket di SynemaX?', 'a' => 'Pilih film atau jadwal yang Anda inginkan, tentukan sesi tontonan, pilih tipe nonton (per orang atau sewa studio privat), lalu ikuti instruksi pembayaran.', 'order' => 1],
            ['q' => 'Berapa kapasitas satu studio SynemaX?', 'a' => 'Satu studio privat SynemaX memuat hingga 6 penonton dengan sofa empuk, AC dingin, dan tata suara sinematik surround.', 'order' => 2],
            ['q' => 'Bisakah saya membawa makanan dari luar?', 'a' => 'Untuk kenyamanan bersama, kami menyediakan aneka jajanan dan minuman lezat yang bisa dipesan langsung saat booking tiket.', 'order' => 3],
            ['q' => 'Apakah bisa memesan seluruh studio untuk rombongan keluarga?', 'a' => 'Tentu bisa! Pilih opsi Sewa Studio Privat saat memilih jam sesi untuk mendapatkan akses eksklusif 6 kursi.', 'order' => 4],
        ];
    }

    // === TAB 6: KONTAK & SOSIAL ===
    public function contactWhatsapp(): string
    {
        $wa = $this->string('admin_whatsapp', '');
        if ($wa !== '') {
            return $wa;
        }
        $phone = $this->string('whatsapp_number', '');
        if ($phone !== '') {
            return $phone;
        }

        return $this->string('cinema_phone', '');
    }

    public function contactPhone(): string
    {
        return $this->contactWhatsapp();
    }

    public function contactEmail(): string
    {
        $email = $this->string('contact_email', '');
        if ($email !== '') {
            return $email;
        }

        return $this->string('cinema_email', '');
    }

    public function contactAddress(): string
    {
        $address = $this->string('contact_address', '');
        if ($address !== '') {
            return $address;
        }

        return $this->string('cinema_address', '');
    }

    public function contactMapsUrl(): string
    {
        return $this->mapsUrl();
    }

    public function mapsUrl(): string
    {
        $url = $this->string('maps_url', '');
        if ($url !== '' && $url !== 'https://maps.google.com/?q=-6.200000,106.816666') {
            return $url;
        }
        $contactMaps = $this->string('contact_maps_url', '');
        if ($contactMaps !== '' && $contactMaps !== 'https://maps.google.com/?q=-6.200000,106.816666') {
            return $contactMaps;
        }
        $googleMaps = $this->string('google_maps_url', '');
        if ($googleMaps !== '' && $googleMaps !== 'https://maps.google.com/?q=-6.200000,106.816666') {
            return $googleMaps;
        }

        return '';
    }

    public function mapsLabel(): string
    {
        return $this->string('maps_label', 'Lihat di Google Maps');
    }

    public function instagramUrl(): string
    {
        $url = $this->string('instagram_url', '');
        if ($url !== '') {
            return self::cleanSocialUrl($url);
        }
        $old = $this->string('social_instagram', '');
        if ($old !== '') {
            if (str_starts_with($old, 'http://') || str_starts_with($old, 'https://')) {
                return self::cleanSocialUrl($old);
            }
            if (str_contains($old, 'instagram.com')) {
                return self::cleanSocialUrl('https://'.ltrim($old, '/'));
            }

            return self::cleanSocialUrl('https://instagram.com/'.ltrim($old, '@'));
        }

        return '';
    }

    public function instagramLabel(): string
    {
        $lbl = $this->string('instagram_label', '');
        if ($lbl !== '') {
            return $lbl;
        }
        $old = $this->string('social_instagram', '');
        if ($old !== '') {
            if (str_starts_with($old, '@')) {
                return $old;
            }
            if (! str_contains($old, '/') && ! str_contains($old, '.')) {
                return '@'.ltrim($old, '@');
            }
            $url = $this->instagramUrl();
            if ($url !== '') {
                return self::suggestSocialHandle('instagram', $url);
            }
        }
        $url = $this->instagramUrl();
        if ($url !== '') {
            return self::suggestSocialHandle('instagram', $url);
        }

        return '';
    }

    public function tiktokUrl(): string
    {
        $url = $this->string('tiktok_url', '');
        if ($url !== '') {
            return self::cleanSocialUrl($url);
        }
        $old = $this->string('social_tiktok', '');
        if ($old !== '') {
            if (str_starts_with($old, 'http://') || str_starts_with($old, 'https://')) {
                return self::cleanSocialUrl($old);
            }
            if (str_contains($old, 'tiktok.com')) {
                return self::cleanSocialUrl('https://'.ltrim($old, '/'));
            }

            return self::cleanSocialUrl('https://tiktok.com/@'.ltrim($old, '@'));
        }

        return '';
    }

    public function tiktokLabel(): string
    {
        $lbl = $this->string('tiktok_label', '');
        if ($lbl !== '') {
            return $lbl;
        }
        $old = $this->string('social_tiktok', '');
        if ($old !== '') {
            if (str_starts_with($old, '@')) {
                return $old;
            }
            if (! str_contains($old, '/') && ! str_contains($old, '.')) {
                return '@'.ltrim($old, '@');
            }
            $url = $this->tiktokUrl();
            if ($url !== '') {
                return self::suggestSocialHandle('tiktok', $url);
            }
        }
        $url = $this->tiktokUrl();
        if ($url !== '') {
            return self::suggestSocialHandle('tiktok', $url);
        }

        return '';
    }

    public function youtubeUrl(): string
    {
        $url = $this->string('youtube_url', '');
        if ($url !== '' && $url !== 'https://youtube.com/@synemax.cinema' && $url !== 'https://www.youtube.com/@synemax.cinema') {
            return self::cleanSocialUrl($url);
        }
        $old = $this->string('social_youtube', '');
        if ($old !== '' && $old !== 'https://youtube.com/@synemax.cinema' && $old !== 'https://www.youtube.com/@synemax.cinema') {
            if (str_starts_with($old, 'http://') || str_starts_with($old, 'https://')) {
                return self::cleanSocialUrl($old);
            }
            if (str_contains($old, 'youtube.com') || str_contains($old, 'youtu.be')) {
                return self::cleanSocialUrl('https://'.ltrim($old, '/'));
            }

            return self::cleanSocialUrl('https://youtube.com/@'.ltrim($old, '@'));
        }

        return '';
    }

    public function youtubeLabel(): string
    {
        $url = $this->youtubeUrl();
        if ($url === '') {
            return '';
        }

        $lbl = $this->string('youtube_label', '');
        if ($lbl !== '' && $lbl !== '@synemax.cinema') {
            return $lbl;
        }

        return self::suggestSocialHandle('youtube', $url);
    }

    /**
     * @return array<string, array{url: string, label: string}>
     */
    public function socialSettings(): array
    {
        return [
            'instagram' => [
                'url' => $this->instagramUrl(),
                'label' => $this->instagramLabel(),
            ],
            'tiktok' => [
                'url' => $this->tiktokUrl(),
                'label' => $this->tiktokLabel(),
            ],
            'youtube' => [
                'url' => $this->youtubeUrl(),
                'label' => $this->youtubeLabel(),
            ],
        ];
    }

    public function capacityDescription(): string
    {
        $capacity = $this->capacityPerSession();

        return "{$capacity} kursi privat per sesi";
    }

    public function startingPriceDescription(): string
    {
        $price = number_format($this->startingPrice(), 0, ',', '.');

        return "Mulai Rp {$price}";
    }

    public function cinemaFactsText(): string
    {
        return $this->capacityDescription().' · '.$this->startingPriceDescription();
    }

    public function socialInstagram(): string
    {
        return $this->instagramLabel();
    }

    public function socialYoutube(): string
    {
        return $this->youtubeLabel();
    }

    public function socialTiktok(): string
    {
        return $this->tiktokLabel();
    }

    public static function cleanSocialUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.ltrim($url, '/');
        }
        $parsed = parse_url($url);
        if (! isset($parsed['scheme']) || ! isset($parsed['host'])) {
            return $url;
        }
        $cleanUrl = 'https://'.$parsed['host'].($parsed['path'] ?? '');
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
            $stripKeys = [
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'igsh',
                '_r',
                '_t',
                'si',
                'feature',
                'fbclid',
                'ref',
                'ref_src',
            ];
            foreach ($stripKeys as $k) {
                unset($queryParams[$k]);
            }
            if (count($queryParams) > 0) {
                $cleanUrl .= '?'.http_build_query($queryParams);
            }
        }

        return $cleanUrl;
    }

    public static function suggestSocialHandle(string $platform, string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.ltrim($url, '/');
        }
        $parsed = parse_url($url);
        $path = trim($parsed['path'] ?? '', '/');
        if ($platform === 'instagram' || $platform === 'tiktok') {
            $parts = explode('/', $path);
            $handle = $parts[0] ?? '';
            $handle = ltrim($handle, '@');

            return $handle !== '' ? '@'.$handle : '@synemax';
        }
        if ($platform === 'youtube') {
            $parts = explode('/', $path);
            $handle = $parts[0] ?? '';

            return $handle !== '' ? (str_starts_with($handle, '@') ? $handle : '@'.$handle) : 'SynemaX Cinema';
        }
        if ($platform === 'maps') {
            return 'Lihat di Google Maps';
        }

        return '';
    }

    public function footerText(): string
    {
        return $this->string('footer_text', '© {tahun} SynemaX Cinema. Hak cipta dilindungi.');
    }

    public function formattedFooterText(): string
    {
        $raw = trim($this->string('footer_text', ''));
        if ($raw === '') {
            $raw = '© {tahun} SynemaX. Hak cipta dilindungi.';
        }

        $year = date('Y');
        $rendered = str_replace(['{tahun}', '{year}'], $year, $raw);

        if (! str_starts_with($rendered, '©') && ! str_starts_with($rendered, '&copy;')) {
            $rendered = '© '.$rendered;
        }

        return $rendered;
    }

    public function showFloatingWhatsapp(): bool
    {
        return $this->boolean('show_floating_whatsapp', true);
    }

    // === TAB 7: SEO ===
    public function seoTitleTemplate(): string
    {
        return $this->string('seo_title_template', '%title% | SynemaX');
    }

    public function seoDefaultDescription(): string
    {
        return $this->string('seo_meta_description', 'Bioskop mini privat desa dengan proyektor jernih, audio surround Dolby, dan kursi sofa empuk. Pesan tiket online mudah di SynemaX.');
    }

    public function lowAttendanceWarningWindowHours(): int
    {
        return $this->integer('low_attendance_warning_window', 72);
    }

    public function homeSnackBanners(): array
    {
        $banners = $this->get('home_snack_banners');
        if (is_array($banners) && count($banners) > 0) {
            return $banners;
        }

        return [
            [
                'title' => 'Paket Combo Popcorn & Minuman',
                'image_path' => 'banners/snack_combo_banner.png',
                'url' => '/cek-booking',
            ],
            [
                'title' => 'Menu Jajan & Camilan Sinema',
                'image_path' => 'banners/snack_menu_banner.png',
                'url' => '/cek-booking',
            ],
        ];
    }

    public function isMemberProgramEnabled(): bool
    {
        return $this->boolean('member_program_enabled', true);
    }

    public function memberDiscountVisit(): int
    {
        return $this->integer('member_discount_visit', 5);
    }

    public function memberDiscountAmount(): int
    {
        return $this->integer('member_discount_amount', 2000);
    }

    public function memberFreeVisit(): int
    {
        return $this->integer('member_free_visit', 10);
    }

    public function memberMaxVisitsPerDay(): int
    {
        return $this->integer('member_max_visits_per_day', 2);
    }

    public static function __callStatic(string $method, array $parameters): mixed
    {
        return app(self::class)->$method(...$parameters);
    }
}
