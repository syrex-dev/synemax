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
        return $this->string('qris_merchant_name', 'SynemaX Home Cinema');
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
        return $this->string('site_tagline', 'Studio Home Cinema Privat Desa');
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

                return $item;
            }, $items);
        }

        return [
            ['key' => 'home', 'label' => 'Beranda', 'url' => '/', 'route' => 'home', 'is_visible' => true, 'order' => 1],
            ['key' => 'schedule', 'label' => 'Jadwal', 'url' => '/jadwal', 'route' => 'schedule.index', 'is_visible' => true, 'order' => 2],
            ['key' => 'film', 'label' => 'Film', 'url' => '/film', 'route' => 'films.index', 'is_visible' => true, 'order' => 3],
            ['key' => 'nobar', 'label' => 'Nobar', 'url' => '/nobar', 'route' => 'nobar.index', 'is_visible' => true, 'order' => 4],
            ['key' => 'promo', 'label' => 'Promo', 'url' => '/promo', 'route' => 'promos.index', 'is_visible' => true, 'order' => 5],
            ['key' => 'articles', 'label' => 'Kabar', 'url' => '/kabar', 'route' => 'articles.index', 'is_visible' => true, 'order' => 6],
            ['key' => 'lookup', 'label' => 'Cek booking', 'url' => '/cek-booking', 'route' => 'booking.lookup.form', 'is_visible' => true, 'order' => 7],
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

        $minPrice = $this->integer('weekday_price', 5000);

        return [
            ['icon' => 'chair', 'text' => '6 kursi privat per sesi', 'is_visible' => true, 'order' => 1],
            ['icon' => 'tag', 'text' => 'Mulai Rp '.number_format($minPrice, 0, ',', '.'), 'is_visible' => true, 'order' => 2, 'auto_price' => true],
            ['icon' => 'sparkles', 'text' => 'Studio AC & Audio Dolby', 'is_visible' => true, 'order' => 3],
        ];
    }

    public function homeFilmsCount(): int
    {
        return $this->integer('home_films_count', 8);
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

    public function quorum(): int
    {
        return $this->minimumPublicViewers();
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
            'libur' => $this->integer('holiday_price', 6000),
            'weekend' => $this->integer('weekend_price', 6000),
            default => $this->integer('weekday_price', 5000),
        };
    }

    public function couplePrice(?string $dayType = null): int
    {
        $isWeekend = in_array($dayType, ['weekend', 'libur'], true);
        $settingKey = $isWeekend ? 'couple_price_weekend' : 'couple_price_weekday';
        $altKey = $isWeekend ? 'couple_weekend_price' : 'couple_weekday_price';
        $override = $this->get($settingKey) ?: $this->get($altKey);

        if (! empty($override) && is_numeric($override) && (int) $override > 0) {
            return (int) $override;
        }

        return $this->minimumPublicViewers() * $this->ticketPriceForDayType($dayType);
    }

    public function privatePriceForDayType(?string $dayType = null): int
    {
        $isWeekend = in_array($dayType, ['weekend', 'libur'], true);
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

    // === TAB 5: HARGA & FAQ ===
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
        return $this->integer('holiday_price', 20000);
    }

    public function privatePrice(): int
    {
        return $this->integer('private_price', 80000);
    }

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
        return $this->string('admin_whatsapp', '081234567890');
    }

    public function contactEmail(): string
    {
        return $this->string('contact_email', 'kontak@synemax.test');
    }

    public function contactAddress(): string
    {
        return $this->string('contact_address', 'Jl. Bioskop Desa No. 1, Desa Kreatif');
    }

    public function contactMapsUrl(): string
    {
        return $this->string('contact_maps_url', 'https://maps.google.com');
    }

    public function socialInstagram(): string
    {
        return $this->string('social_instagram', '@synemax.cinema');
    }

    public function socialYoutube(): string
    {
        return $this->string('social_youtube', '');
    }

    public function socialTiktok(): string
    {
        return $this->string('social_tiktok', '');
    }

    public function footerText(): string
    {
        return $this->string('footer_text', '© '.date('Y').' SynemaX Home Cinema. Bioskop mini privat desa dengan teknologi modern.');
    }

    public function showFloatingWhatsapp(): bool
    {
        return $this->boolean('show_floating_whatsapp', true);
    }

    // === TAB 7: SEO ===
    public function seoTitleTemplate(): string
    {
        return $this->string('seo_title_template', '%title% | SynemaX Studio Home Cinema');
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
}
