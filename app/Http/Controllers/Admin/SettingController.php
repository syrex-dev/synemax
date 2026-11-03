<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Booking;
use App\Models\Film;
use App\Models\NewsArticle;
use App\Models\NobarEvent;
use App\Models\NotificationOutbox;
use App\Models\Promo;
use App\Models\Setting;
use App\Services\CinemaSettings;
use App\Services\TelegramNotificationService;
use App\Support\WhatsAppNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(CinemaSettings $settings, Request $request): View
    {
        $currentTab = $request->query('tab', 'identitas');

        $qrisRatioWarning = false;
        if ($settings->hasValidQrisImage()) {
            $path = $settings->string('qris_image_path');
            if ($path !== '' && Storage::disk('local')->exists($path)) {
                $fullPath = Storage::disk('local')->path($path);
                $size = @getimagesize($fullPath);
                if ($size && $size[1] > 0) {
                    $ratio = $size[0] / $size[1];
                    if ($ratio < 0.8 || $ratio > 1.25) {
                        $qrisRatioWarning = true;
                    }
                }
            }
        }

        // Collect available library images
        $libraryImages = $this->collectMediaLibrary();

        $sampleCounts = [
            'films' => Film::where('is_sample', true)->count(),
            'promos' => Promo::where('is_sample', true)->count(),
            'banners' => Banner::where('is_sample', true)->count(),
            'nobar' => NobarEvent::where('is_sample', true)->count(),
            'news' => NewsArticle::where('is_sample', true)->count(),
        ];
        $activeFilms = Film::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'release_year', 'genre', 'poster_path']);

        return view('admin.settings.edit', [
            'currentTab' => $currentTab,
            'settings' => $settings,
            'enabledMethods' => $settings->rawPaymentMethods(),
            'hasQrisImage' => $settings->hasValidQrisImage(),
            'hasTransferAccounts' => $settings->hasValidTransferAccounts(),
            'qrisRatioWarning' => $qrisRatioWarning,
            'libraryImages' => $libraryImages,
            'sampleCounts' => $sampleCounts,
            'activeFilms' => $activeFilms,
            'featuredFilmIds' => $settings->homeFeaturedFilmIds(),
            'homeFilmsMode' => $settings->homeFilmsMode(),
            'telegramSettings' => [
                'enabled' => $settings->boolean('telegram_notifications_enabled', false),
                'masked_token' => app(TelegramNotificationService::class)->getMaskedToken(),
                'chat_ids' => $settings->string('telegram_chat_ids'),
                'notify_booking_created' => $settings->boolean('telegram_notify_booking_created', true),
                'notify_proof_uploaded' => $settings->boolean('telegram_notify_proof_uploaded', true),
                'notify_payment_confirmed' => $settings->boolean('telegram_notify_payment_confirmed', false),
                'notify_booking_cancelled' => $settings->boolean('telegram_notify_booking_cancelled', true),
                'notify_booking_expired' => $settings->boolean('telegram_notify_booking_expired', false),
                'notify_session_confirmed' => $settings->boolean('telegram_notify_session_confirmed', true),
                'notify_session_cancelled' => $settings->boolean('telegram_notify_session_cancelled', true),
                'hide_customer_whatsapp' => $settings->boolean('telegram_hide_customer_whatsapp', false),
                'include_customer_notes' => $settings->boolean('telegram_include_customer_notes', true),
            ],
            'telegramLogs' => NotificationOutbox::query()
                ->where('channel', 'telegram')
                ->with('booking')
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
        ]);
    }

    public function update(Request $request, CinemaSettings $settings): RedirectResponse
    {
        $tab = $request->input('active_tab', $request->input('_tab', $request->input('tab')));

        if (! $tab && $request->has('weekday_price') && $request->has('payment_methods')) {
            $this->updateHarga($request, $settings);
            $this->updateOperasional($request, $settings);
            $settings->clearCache();
            $redirect = redirect()->route('admin.settings.edit')
                ->with('status', 'Perubahan pengaturan berhasil disimpan.');
            if (session()->has('warning')) {
                $redirect->with('warning', session('warning'));
            }

            return $redirect;
        }

        if (! $tab) {
            if ($request->has('payment_methods') || $request->has('qris_merchant_name') || $request->has('qris_image')) {
                $tab = 'operasional';
            } elseif ($request->has('site_name') || $request->has('navbar_brand_display')) {
                $tab = 'identitas';
            } elseif ($request->has('navbar_menu')) {
                $tab = 'navbar';
            } elseif ($request->has('default_theme') || $request->has('chips')) {
                $tab = 'tampilan';
            } elseif ($request->has('weekday_price') || $request->has('faq_question')) {
                $tab = 'harga';
            } elseif ($request->has('contact_address') || $request->has('contact_email') || $request->has('admin_whatsapp') || $request->has('cinema_phone') || $request->has('cinema_address') || $request->has('whatsapp_number')) {
                $tab = 'kontak';
            } elseif ($request->has('seo_title_template')) {
                $tab = 'seo';
            } elseif ($request->has('telegram_bot_token') || $request->has('telegram_notifications_enabled') || $request->has('telegram_chat_ids')) {
                $tab = 'notifikasi';
            } else {
                $tab = 'operasional';
            }
        }

        switch ($tab) {
            case 'identitas':
                $this->updateIdentitas($request, $settings);
                break;
            case 'navbar':
                $this->updateNavbar($request, $settings);
                break;
            case 'tampilan':
                $this->updateTampilan($request, $settings);
                break;
            case 'operasional':
                $this->updateOperasional($request, $settings);
                break;
            case 'harga':
                $this->updateHarga($request, $settings);
                break;
            case 'kontak':
                $this->updateKontak($request, $settings);
                break;
            case 'seo':
                $this->updateSeo($request, $settings);
                break;
            case 'notifikasi':
                $this->updateNotifikasi($request, $settings);
                break;
            default:
                $this->updateOperasional($request, $settings);
                break;
        }

        $settings->clearCache();

        $redirect = redirect()->route('admin.settings.edit', ['tab' => $tab])
            ->with('status', 'Perubahan pengaturan berhasil disimpan.');
        if (session()->has('warning')) {
            $redirect->with('warning', session('warning'));
        }

        return $redirect;
    }

    private function updateIdentitas(Request $request, CinemaSettings $settings): void
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:100',
            'site_tagline' => 'nullable|string|max:200',
            'navbar_brand_display' => 'required|string|in:logo_text,logo_only,text_only',
            'site_logo' => 'nullable|image|mimes:png,svg,webp,jpg|max:1024',
            'site_logo_dark' => 'nullable|image|mimes:png,svg,webp,jpg|max:1024',
            'site_favicon' => 'nullable|file|mimes:png,svg,ico|max:512',
            'site_og_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $settings->set('site_name', $validated['site_name'], 'identitas');
        $settings->set('site_tagline', $validated['site_tagline'] ?? '', 'identitas');
        $settings->set('navbar_brand_display', $validated['navbar_brand_display'], 'identitas');

        // Handle Site Logo
        if ($request->boolean('delete_site_logo')) {
            $settings->set('site_logo', '', 'identitas');
        } elseif ($request->filled('library_site_logo')) {
            $settings->set('site_logo', $request->input('library_site_logo'), 'identitas');
        } elseif ($request->hasFile('site_logo')) {
            $path = $request->file('site_logo')->store('branding', 'public');
            $settings->set('site_logo', $path, 'identitas');
        }

        // Handle Dark Logo
        if ($request->boolean('delete_site_logo_dark')) {
            $settings->set('site_logo_dark', '', 'identitas');
        } elseif ($request->filled('library_site_logo_dark')) {
            $settings->set('site_logo_dark', $request->input('library_site_logo_dark'), 'identitas');
        } elseif ($request->hasFile('site_logo_dark')) {
            $path = $request->file('site_logo_dark')->store('branding', 'public');
            $settings->set('site_logo_dark', $path, 'identitas');
        }

        // Handle Favicon
        if ($request->boolean('delete_site_favicon')) {
            $settings->set('site_favicon', 'favicon.svg', 'identitas');
        } elseif ($request->hasFile('site_favicon')) {
            $path = $request->file('site_favicon')->store('branding', 'public');
            $settings->set('site_favicon', $path, 'identitas');
        }

        // Handle OG Image
        if ($request->boolean('delete_site_og_image')) {
            $settings->set('site_og_image', '', 'identitas');
        } elseif ($request->filled('library_site_og_image')) {
            $settings->set('site_og_image', $request->input('library_site_og_image'), 'identitas');
        } elseif ($request->hasFile('site_og_image')) {
            $path = $request->file('site_og_image')->store('branding', 'public');
            $settings->set('site_og_image', $path, 'identitas');
        }
    }

    private function updateNavbar(Request $request, CinemaSettings $settings): void
    {
        $items = $request->input('menu', []);
        $cleanMenu = [];
        $bottomBarCount = 0;

        foreach ($items as $item) {
            $key = $item['key'] ?? '';
            if (empty($key)) {
                continue;
            }

            $mobilePos = ($item['mobile_position'] ?? 'menu') === 'bottom_bar' ? 'bottom_bar' : 'menu';
            $isVisible = ! empty($item['is_visible']);

            if ($mobilePos === 'bottom_bar' && $isVisible) {
                $bottomBarCount++;
            }

            $shortLabel = filled($item['short_label'] ?? null)
                ? trim((string) $item['short_label'])
                : (in_array($key, ['lookup', 'booking_lookup'], true) ? 'Booking' : ($item['label'] ?? ucfirst($key)));

            $rawUrl = trim((string) ($item['url'] ?? '/'), " '\"\t\n\r");
            $sanitizedUrl = $rawUrl !== '' ? $rawUrl : '/';

            $cleanMenu[] = [
                'key' => $key,
                'label' => $item['label'] ?? ucfirst($key),
                'short_label' => $shortLabel,
                'url' => $sanitizedUrl,
                'route' => $item['route'] ?? '',
                'mobile_position' => $mobilePos,
                'is_visible' => $isVisible,
                'order' => (int) ($item['order'] ?? 1),
            ];
        }

        if ($bottomBarCount > 4) {
            throw ValidationException::withMessages([
                'menu' => 'Maksimal 4 item untuk Bar Bawah HP (1 slot sisanya untuk Menu).',
            ]);
        }

        // Sort by order
        usort($cleanMenu, fn ($a, $b) => $a['order'] <=> $b['order']);

        $settings->set('navbar_menu', $cleanMenu, 'navbar');
    }

    private function updateTampilan(Request $request, CinemaSettings $settings): void
    {
        $validated = $request->validate([
            'default_theme' => 'required|in:system,light,dark',
            'show_theme_toggle' => 'nullable|boolean',
            'home_films_count' => 'required|integer|in:6,8,10,12',
            'films_sort_order' => 'nullable|string|in:year_desc,year_asc,title_asc,title_desc,latest_added',
            'home_hero_interval' => 'required|integer|in:4,5,6,8',
            'home_hero_autoplay' => 'nullable|boolean',
            'home_films_mode' => 'nullable|in:auto,custom',
            'home_featured_film_ids' => 'nullable|array',
            'home_featured_film_ids.*' => 'nullable|integer',
        ]);

        $settings->set('default_theme', $validated['default_theme'], 'tampilan');
        $settings->set('show_theme_toggle', $request->boolean('show_theme_toggle'), 'tampilan');
        $settings->set('home_films_count', (int) $validated['home_films_count'], 'tampilan');
        $settings->set('films_sort_order', $request->input('films_sort_order', 'year_desc'), 'tampilan');
        $settings->set('home_hero_interval', (int) $validated['home_hero_interval'], 'tampilan');
        $settings->set('home_hero_autoplay', $request->boolean('home_hero_autoplay'), 'tampilan');
        $settings->set('home_films_mode', $request->input('home_films_mode', 'auto'), 'tampilan');

        $featuredIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('home_featured_film_ids', [])))));
        $settings->set('home_featured_film_ids', $featuredIds, 'tampilan');

        // Handle Chips
        $chipsInput = $request->input('chips', []);
        $chips = [];
        foreach ($chipsInput as $index => $chip) {
            $text = trim($chip['text'] ?? '');
            if ($text === '') {
                continue;
            }

            $chips[] = [
                'icon' => $chip['icon'] ?? 'sparkles',
                'text' => $text,
                'is_visible' => ! empty($chip['is_visible']),
                'order' => (int) ($chip['order'] ?? ($index + 1)),
                'auto_price' => ! empty($chip['auto_price']),
            ];
        }
        if (count($chips) > 0) {
            usort($chips, fn ($a, $b) => $a['order'] <=> $b['order']);
            $settings->set('home_info_chips', $chips, 'tampilan');
        }
    }

    private function updateOperasional(Request $request, CinemaSettings $settings): void
    {
        $request->merge([
            'capacity_per_session' => $request->input('capacity_per_session', $settings->integer('capacity_per_session', 6)),
            'max_seats_per_booking' => $request->input('max_seats_per_booking', $settings->integer('max_seats_per_booking', 6)),
            'minimum_public_viewers' => $request->input('minimum_public_viewers', $settings->integer('minimum_public_viewers', 3)),
            'low_attendance_warning_window' => $request->input('low_attendance_warning_window', $settings->integer('low_attendance_warning_window', 72)),
            'booking_cutoff_minutes' => $request->input('booking_cutoff_minutes', $settings->integer('booking_cutoff_minutes', 30)),
            'cancellation_cutoff_hours' => $request->input('cancellation_cutoff_hours', $settings->integer('cancellation_cutoff_hours', 6)),
            'admin_whatsapp' => WhatsAppNumber::normalize((string) $request->input('admin_whatsapp', $settings->string('admin_whatsapp', '6281234567890'))),
            'electricity_cost_per_session' => $request->input('electricity_cost_per_session', $settings->integer('electricity_cost_per_session', 0)),
            'snacks_enabled' => $request->boolean('snacks_enabled') ? 1 : 0,
            'snack_prep_buffer_minutes' => $request->input('snack_prep_buffer_minutes', $settings->integer('snack_prep_buffer_minutes', 15)),
            'snack_max_qty_per_item' => $request->input('snack_max_qty_per_item', $settings->integer('snack_max_qty_per_item', 10)),
            'info_nobar_enabled' => $request->has('info_nobar_enabled') ? ($request->boolean('info_nobar_enabled') ? 1 : 0) : $settings->integer('info_nobar_enabled', 1),
            'maintenance_mode' => $request->boolean('maintenance_mode') ? 1 : 0,
            'ticket_arrive_minutes' => $request->input('ticket_arrive_minutes', $settings->integer('ticket_arrive_minutes', 15)),
            'ticket_studio_name' => $request->input('ticket_studio_name', $settings->string('ticket_studio_name', 'Studio 1')),
            'ticket_notes' => $request->input('ticket_notes', $settings->string('ticket_notes', 'Tunjukkan e-tiket ini kepada petugas di pintu masuk bioskop.')),
            'check_in_open_minutes_before' => $request->input('check_in_open_minutes_before', $settings->integer('check_in_open_minutes_before', 60)),
            'check_in_close_after' => $request->input('check_in_close_after', $settings->string('check_in_close_after', 'session_end')),
            'check_in_sound_enabled' => $request->boolean('check_in_sound_enabled') ? 1 : 0,
        ]);

        $data = $request->validate([
            'capacity_per_session' => ['required', 'integer', 'between:1,20'],
            'max_seats_per_booking' => ['required', 'integer', 'between:1,20'],
            'minimum_public_viewers' => ['required', 'integer', 'between:1,6'],
            'low_attendance_warning_window' => ['required', 'integer', 'in:24,48,72,168'],
            'booking_cutoff_minutes' => ['required', 'integer', 'between:0,720'],
            'cancellation_cutoff_hours' => ['required', 'integer', 'between:0,168'],
            'maintenance_mode' => ['required', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            'ticket_arrive_minutes' => ['required', 'integer', 'between:0,120'],
            'ticket_studio_name' => ['required', 'string', 'max:50'],
            'ticket_notes' => ['nullable', 'string', 'max:160'],
            'check_in_open_minutes_before' => ['required', 'integer', 'in:15,30,60,90,120'],
            'check_in_close_after' => ['required', 'string', 'in:15,30,60,session_end'],
            'check_in_sound_enabled' => ['required', 'boolean'],
            'admin_whatsapp' => ['required', 'string', 'regex:/^62\d{9,12}$/'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'electricity_cost_per_session' => ['required', 'integer', 'between:0,10000000'],
            'snacks_enabled' => ['required', 'boolean'],
            'snack_prep_buffer_minutes' => ['required', 'integer', 'between:0,360'],
            'snack_max_qty_per_item' => ['required', 'integer', 'between:1,100'],
            'info_nobar_enabled' => ['required', 'boolean'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['required', 'in:di_tempat,qris,transfer'],
            'qris_image' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=300,min_height=300'],
            'qris_merchant_name' => ['nullable', 'string', 'max:120'],
            'qris_payment_notes' => ['nullable', 'string', 'max:2000'],
            'delete_qris_image' => ['nullable', 'boolean'],
            'transfer_accounts' => ['nullable', 'array'],
            'transfer_accounts.*.bank_name' => ['nullable', 'string', 'max:50'],
            'transfer_accounts.*.account_number' => ['nullable', 'string', 'max:50'],
            'transfer_accounts.*.account_holder' => ['nullable', 'string', 'max:100'],
            'transfer_payment_notes' => ['nullable', 'string', 'max:2000'],
            'payment_time_limit_minutes' => ['nullable', 'integer', 'between:5,180'],
            'online_payment_cutoff_minutes' => ['nullable', 'integer', 'between:0,180'],
            'max_payment_proof_kb' => ['nullable', 'integer', 'between:512,10240'],
        ]);

        $paymentMethods = array_values(array_unique($data['payment_methods'] ?? []));
        unset($data['payment_methods']);

        $deleteQris = $request->boolean('delete_qris_image');
        $hasNewQris = $request->hasFile('qris_image');
        $hasExistingQris = $settings->hasValidQrisImage();

        $warnings = [];

        if (in_array('qris', $paymentMethods, true) && ! $hasNewQris && ! $hasExistingQris) {
            $warnings[] = 'Metode QRIS dicentang aktif, namun belum akan muncul ke pelanggan sampai file gambar QRIS diunggah.';
        }

        // Process transfer accounts
        $rawTransferAccounts = $request->input('transfer_accounts', []);
        $cleanTransferAccounts = [];
        if (is_array($rawTransferAccounts)) {
            foreach ($rawTransferAccounts as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $bankName = trim((string) ($item['bank_name'] ?? ''));
                $accNum = trim((string) ($item['account_number'] ?? ''));
                $accHolder = trim((string) ($item['account_holder'] ?? ''));
                if ($bankName !== '' && $accNum !== '') {
                    $cleanTransferAccounts[] = [
                        'bank_name' => $bankName,
                        'account_number' => $accNum,
                        'account_holder' => $accHolder !== '' ? $accHolder : ($settings->string('site_name') ?: 'SynemaX Cinema'),
                    ];
                }
            }
        }
        unset($data['transfer_accounts']);

        if (in_array('transfer', $paymentMethods, true) && count($cleanTransferAccounts) === 0) {
            $warnings[] = 'Metode Transfer dicentang aktif, namun belum memiliki data rekening yang lengkap.';
        }

        $settings->set('transfer_accounts', $cleanTransferAccounts, 'operasional');

        if ($deleteQris) {
            $oldPath = $settings->string('qris_image_path');
            if ($oldPath !== '' && Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
            $settings->set('qris_image_path', '', 'operasional');
            $settings->set('qris_image_updated_at', (string) now()->timestamp, 'operasional');
            $paymentMethods = array_values(array_diff($paymentMethods, ['qris']));
        } elseif ($hasNewQris) {
            $uploaded = $request->file('qris_image');
            $size = @getimagesize($uploaded->getPathname());
            if ($size && $size[1] > 0) {
                $ratio = $size[0] / $size[1];
                if ($ratio < 0.8 || $ratio > 1.25) {
                    $warnings[] = 'QRIS sebaiknya persegi dan rapi; pangkas bagian lain.';
                }
            }
            $qrisPath = $this->processAndStoreQrisImage($uploaded, $settings);
            $settings->set('qris_image_path', $qrisPath, 'operasional');
            $settings->set('qris_image_updated_at', (string) now()->timestamp, 'operasional');
        }
        unset($data['qris_image'], $data['delete_qris_image']);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $settings->set($key, (string) $value, 'operasional');
            }
        }

        $settings->set('enabled_payment_methods', $paymentMethods, 'operasional');

        if (count($warnings) > 0) {
            session()->flash('warning', implode(' ', $warnings));
        }
    }

    private function updateHarga(Request $request, CinemaSettings $settings): void
    {
        $validated = $request->validate([
            'weekday_price' => 'required|integer|between:500,1000000',
            'weekend_price' => 'required|integer|between:500,1000000',
            'holiday_price' => 'required|integer|between:500,1000000',
            'private_price' => 'required|integer|between:1000,6000000',
        ]);

        $settings->set('weekday_price', $validated['weekday_price'], 'harga');
        $settings->set('weekend_price', $validated['weekend_price'], 'harga');
        $settings->set('holiday_price', $validated['holiday_price'], 'harga');
        $settings->set('private_price', $validated['private_price'], 'harga');

        // Handle FAQs
        $faqsInput = $request->input('faqs', []);
        $cleanFaqs = [];
        foreach ($faqsInput as $index => $item) {
            $q = trim($item['q'] ?? '');
            $a = trim($item['a'] ?? '');
            if ($q === '' || $a === '') {
                continue;
            }

            $cleanFaqs[] = [
                'q' => $q,
                'a' => $a,
                'order' => (int) ($item['order'] ?? ($index + 1)),
            ];
        }
        if (count($cleanFaqs) > 0) {
            usort($cleanFaqs, fn ($a, $b) => $a['order'] <=> $b['order']);
            $settings->set('faq_list', $cleanFaqs, 'harga');
        }
    }

    private function updateKontak(Request $request, CinemaSettings $settings): void
    {
        $validated = $request->validate([
            'contact_email' => ['nullable', 'email', 'max:100'],
            'contact_address' => ['nullable', 'string', 'max:300'],
            'maps_label' => ['nullable', 'string', 'max:30'],
            'maps_url' => ['nullable', 'string', 'max:500'],
            'instagram_label' => ['nullable', 'string', 'max:30'],
            'instagram_url' => ['nullable', 'string', 'max:500'],
            'tiktok_label' => ['nullable', 'string', 'max:30'],
            'tiktok_url' => ['nullable', 'string', 'max:500'],
            'youtube_label' => ['nullable', 'string', 'max:30'],
            'youtube_url' => ['nullable', 'string', 'max:500'],
            'footer_text' => ['nullable', 'string', 'max:300'],
            'show_floating_whatsapp' => ['nullable', 'boolean'],
        ]);

        $platforms = [
            'instagram' => ['domain' => 'instagram.com', 'default_label' => '@synemax'],
            'tiktok' => ['domain' => 'tiktok.com', 'default_label' => '@synemax'],
            'youtube' => ['domain' => ['youtube.com', 'youtu.be'], 'default_label' => 'SynemaX Cinema'],
            'maps' => ['domain' => ['google.com', 'goo.gl'], 'default_label' => 'Lihat di Google Maps'],
        ];

        $cleanedParamsNotified = false;

        foreach ($platforms as $platform => $cfg) {
            $urlKey = $platform === 'maps' ? 'maps_url' : "{$platform}_url";
            $labelKey = $platform === 'maps' ? 'maps_label' : "{$platform}_label";

            $rawUrl = trim((string) ($validated[$urlKey] ?? ''));
            $rawLabel = trim((string) ($validated[$labelKey] ?? ''));

            if ($rawUrl !== '') {
                // Must be https or valid scheme
                if (str_starts_with(strtolower($rawUrl), 'javascript:') || str_starts_with(strtolower($rawUrl), 'data:')) {
                    throw ValidationException::withMessages([
                        $urlKey => "Alamat URL {$platform} tidak aman atau tidak valid.",
                    ]);
                }

                $cleanUrl = CinemaSettings::cleanSocialUrl($rawUrl);
                if ($cleanUrl !== $rawUrl) {
                    $cleanedParamsNotified = true;
                }

                // Domain check
                $parsedHost = parse_url($cleanUrl, PHP_URL_HOST) ?? '';
                $domains = (array) $cfg['domain'];
                $domainMatches = false;
                foreach ($domains as $d) {
                    if (str_ends_with(strtolower($parsedHost), strtolower($d))) {
                        $domainMatches = true;
                        break;
                    }
                }

                if (! $domainMatches) {
                    throw ValidationException::withMessages([
                        $urlKey => "Alamat URL harus merupakan tautan resmi dari {$platform} (domain: ".implode(', ', $domains).').',
                    ]);
                }

                if ($rawLabel === '') {
                    $rawLabel = CinemaSettings::suggestSocialHandle($platform, $cleanUrl) ?: $cfg['default_label'];
                }

                $settings->set($urlKey, $cleanUrl, 'kontak');
                $settings->set($labelKey, mb_substr($rawLabel, 0, 30), 'kontak');

                if ($platform === 'maps') {
                    $settings->set('contact_maps_url', $cleanUrl, 'kontak');
                }
            } else {
                $settings->set($urlKey, '', 'kontak');
                $settings->set($labelKey, '', 'kontak');
                if ($platform === 'maps') {
                    $settings->set('contact_maps_url', '', 'kontak');
                }
            }
        }

        $contactEmail = $validated['contact_email'] ?? '';
        $contactAddress = $validated['contact_address'] ?? '';

        $settings->set('contact_email', $contactEmail, 'kontak');
        $settings->set('cinema_email', $contactEmail, 'kontak');

        $settings->set('contact_address', $contactAddress, 'kontak');
        $settings->set('cinema_address', $contactAddress, 'kontak');

        $settings->set('footer_text', $validated['footer_text'] ?? '', 'kontak');
        $settings->set('show_floating_whatsapp', $request->boolean('show_floating_whatsapp'), 'kontak');

        if ($cleanedParamsNotified) {
            session()->flash('info', 'Parameter pelacak pada tautan media sosial berhasil dibersihkan otomatis.');
        }
    }

    private function updateSeo(Request $request, CinemaSettings $settings): void
    {
        $validated = $request->validate([
            'seo_title_template' => 'required|string|max:150',
            'seo_meta_description' => 'required|string|max:300',
        ]);

        $settings->set('seo_title_template', $validated['seo_title_template'], 'seo');
        $settings->set('seo_meta_description', $validated['seo_meta_description'], 'seo');
    }

    public function clearCache(CinemaSettings $settings): RedirectResponse
    {
        $settings->clearCache();
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        return back()->with('status', 'Cache sistem dan pengaturan berhasil dibersihkan.');
    }

    public function resetDefaults(CinemaSettings $settings): RedirectResponse
    {
        $wa = Setting::where('key', 'whatsapp_number')->value('value') ?: '6285169730960';
        $adminWa = Setting::where('key', 'admin_whatsapp')->value('value') ?: '6285169730960';

        Setting::query()->delete();

        Setting::create(['key' => 'whatsapp_number', 'value' => $wa, 'group' => 'general']);
        Setting::create(['key' => 'admin_whatsapp', 'value' => $adminWa, 'group' => 'general']);

        Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
        $settings->clearCache();

        return redirect()->route('admin.settings.edit', ['tab' => 'identitas'])
            ->with('status', 'Pengaturan berhasil dikembalikan ke nilai bawaan.');
    }

    public function destroyQris(CinemaSettings $settings): RedirectResponse
    {
        $oldPath = $settings->string('qris_image_path');
        if ($oldPath !== '' && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        $settings->set('qris_image_path', '', 'operasional');
        $settings->set('qris_image_updated_at', (string) now()->timestamp, 'operasional');

        $methods = array_values(array_diff($settings->rawPaymentMethods(), ['qris']));
        $settings->set('enabled_payment_methods', $methods, 'operasional');

        return redirect()->route('admin.settings.edit', ['tab' => 'operasional'])->with('status', 'Gambar QRIS berhasil dihapus.');
    }

    private function processAndStoreQrisImage(UploadedFile $file, CinemaSettings $settings): string
    {
        $directory = 'qris';
        $filename = 'qris_'.Str::random(32).'.'.$file->getClientOriginalExtension();
        $path = $directory.'/'.$filename;

        $oldPath = $settings->string('qris_image_path');
        if ($oldPath !== '' && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        Storage::disk('local')->putFileAs($directory, $file, $filename);

        return $path;
    }

    private function collectMediaLibrary(): array
    {
        $library = [];
        $directories = ['branding', 'banners', 'promos', 'posters'];

        foreach ($directories as $dir) {
            if (Storage::disk('public')->exists($dir)) {
                $files = Storage::disk('public')->files($dir);
                foreach ($files as $file) {
                    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
                        $library[] = [
                            'name' => basename($file),
                            'path' => $file,
                            'url' => Storage::disk('public')->url($file),
                        ];
                    }
                }
            }
        }

        return $library;
    }

    public function deleteSampleData(): RedirectResponse
    {
        $deletedCounts = [
            'films' => 0,
            'promos' => 0,
            'banners' => 0,
            'nobar' => 0,
            'news' => 0,
        ];

        // Delete Sample Films (Guard: jangan hapus film yang pernah diedit atau terkait sesi/booking)
        $films = Film::where('is_sample', true)->get();
        foreach ($films as $film) {
            // Guard 1: Jangan hapus jika film pernah diedit setelah dibuat
            if ($film->updated_at && $film->created_at && $film->updated_at->gt($film->created_at->addSeconds(5))) {
                continue;
            }

            // Guard 2: Jangan hapus jika terkait booking langsung atau melalui sesi
            $hasBookings = Booking::where('film_id', $film->id)->exists()
                || $film->sessions()->whereHas('bookings')->exists();
            if ($hasBookings) {
                continue;
            }

            if ($film->poster_path && Storage::disk('public')->exists($film->poster_path)) {
                Storage::disk('public')->delete($film->poster_path);
            }
            if ($film->banner_path && Storage::disk('public')->exists($film->banner_path)) {
                Storage::disk('public')->delete($film->banner_path);
            }
            $film->sessions()->delete();
            $film->forceDelete();
            $deletedCounts['films']++;
        }

        // Delete Sample Promos
        $promos = Promo::where('is_sample', true)->get();
        foreach ($promos as $promo) {
            if ($promo->image_path && Storage::disk('public')->exists($promo->image_path)) {
                Storage::disk('public')->delete($promo->image_path);
            }
            $promo->forceDelete();
            $deletedCounts['promos']++;
        }

        // Delete Sample Banners
        $banners = Banner::where('is_sample', true)->get();
        foreach ($banners as $banner) {
            if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
                Storage::disk('public')->delete($banner->image_path);
            }
            if ($banner->mobile_image_path && Storage::disk('public')->exists($banner->mobile_image_path)) {
                Storage::disk('public')->delete($banner->mobile_image_path);
            }
            $banner->forceDelete();
            $deletedCounts['banners']++;
        }

        // Delete Sample Nobar
        $nobars = NobarEvent::where('is_sample', true)->get();
        foreach ($nobars as $nobar) {
            if ($nobar->poster_path && Storage::disk('public')->exists($nobar->poster_path)) {
                Storage::disk('public')->delete($nobar->poster_path);
            }
            if ($nobar->poster_og_path && Storage::disk('public')->exists($nobar->poster_og_path)) {
                Storage::disk('public')->delete($nobar->poster_og_path);
            }
            $nobar->forceDelete();
            $deletedCounts['nobar']++;
        }

        // Delete Sample News
        $news = NewsArticle::where('is_sample', true)->get();
        foreach ($news as $article) {
            if ($article->cover_path && Storage::disk('public')->exists($article->cover_path)) {
                Storage::disk('public')->delete($article->cover_path);
            }
            $article->forceDelete();
            $deletedCounts['news']++;
        }

        $total = array_sum($deletedCounts);

        return redirect()->route('admin.settings.edit', ['tab' => 'seo'])->with(
            'status',
            "Data contoh berhasil dibersihkan ({$total} item dihapus: {$deletedCounts['films']} film, {$deletedCounts['promos']} promo, {$deletedCounts['banners']} banner hero, {$deletedCounts['nobar']} nobar, {$deletedCounts['news']} kabar)."
        );
    }

    private function updateNotifikasi(Request $request, CinemaSettings $settings): void
    {
        $enabled = $request->boolean('telegram_notifications_enabled') ? 1 : 0;
        $settings->set('telegram_notifications_enabled', (string) $enabled, 'notifikasi');

        $newToken = trim((string) $request->input('telegram_bot_token', ''));
        if ($newToken !== '' && ! str_contains($newToken, '••••••')) {
            $settings->set('telegram_bot_token', Crypt::encryptString($newToken), 'notifikasi');
        }

        $chatIds = trim((string) $request->input('telegram_chat_ids', ''));
        $settings->set('telegram_chat_ids', $chatIds, 'notifikasi');

        $events = [
            'telegram_notify_booking_created' => $request->boolean('telegram_notify_booking_created', true),
            'telegram_notify_proof_uploaded' => $request->boolean('telegram_notify_proof_uploaded', true),
            'telegram_notify_payment_confirmed' => $request->boolean('telegram_notify_payment_confirmed', false),
            'telegram_notify_booking_cancelled' => $request->boolean('telegram_notify_booking_cancelled', true),
            'telegram_notify_booking_expired' => $request->boolean('telegram_notify_booking_expired', false),
            'telegram_notify_session_confirmed' => $request->boolean('telegram_notify_session_confirmed', true),
            'telegram_notify_session_cancelled' => $request->boolean('telegram_notify_session_cancelled', true),
            'telegram_hide_customer_whatsapp' => $request->boolean('telegram_hide_customer_whatsapp', false),
            'telegram_include_customer_notes' => $request->boolean('telegram_include_customer_notes', true),
        ];

        foreach ($events as $k => $v) {
            $settings->set($k, (string) ($v ? 1 : 0), 'notifikasi');
        }
    }

    public function testTelegram(Request $request, TelegramNotificationService $telegram): JsonResponse
    {
        $chatId = $request->input('chat_id');
        $token = $request->input('token');

        if ($token && str_contains($token, '••••••')) {
            $token = null;
        }

        $result = $telegram->sendTestMessage($chatId, $token);

        return response()->json($result);
    }

    public function detectTelegramChats(Request $request, TelegramNotificationService $telegram): JsonResponse
    {
        $token = $request->input('token');
        if ($token && str_contains($token, '••••••')) {
            $token = null;
        }

        $result = $telegram->detectChats($token);

        return response()->json($result);
    }

    public function retryTelegramNotification(int $id, TelegramNotificationService $telegram): RedirectResponse
    {
        $item = NotificationOutbox::query()->findOrFail($id);
        $ok = $telegram->dispatchItem($item);

        return back()->with(
            $ok ? 'status' : 'error',
            $ok ? 'Pesan notifikasi berhasil dikirim ulang ke Telegram.' : 'Gagal mengirim ulang: '.($item->last_error ?? 'Terjadi kesalahan.')
        );
    }
}
