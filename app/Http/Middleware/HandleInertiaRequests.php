<?php

namespace App\Http\Middleware;

use App\Services\CinemaSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        try {
            /** @var CinemaSettings $cinemaSettings */
            $cinemaSettings = app(CinemaSettings::class);
            $settingsData = [
                'siteName' => $cinemaSettings->siteName(),
                'siteTagline' => $cinemaSettings->siteTagline(),
                'siteLogo' => $cinemaSettings->siteLogo(),
                'siteLogoDark' => $cinemaSettings->siteLogoDark(),
                'navbarBrandDisplay' => $cinemaSettings->navbarBrandDisplay(),
                'siteFaviconUrl' => $cinemaSettings->siteFaviconUrl(),
                'siteFaviconMime' => $cinemaSettings->siteFaviconMime(),
                'navbarMenu' => $cinemaSettings->navbarMenu(),
                'defaultTheme' => $cinemaSettings->defaultTheme(),
                'showThemeToggle' => $cinemaSettings->showThemeToggle(),
                'capacityPerSession' => $cinemaSettings->capacityPerSession(),
                'startingPrice' => CinemaSettings::startingPrice(),
                'startingPriceDescription' => $cinemaSettings->startingPriceDescription(),
                'capacityDescription' => $cinemaSettings->capacityDescription(),
                'contactWhatsapp' => $cinemaSettings->contactWhatsapp(),
                'contactPhone' => $cinemaSettings->contactPhone(),
                'contactEmail' => $cinemaSettings->contactEmail(),
                'contactAddress' => $cinemaSettings->contactAddress(),
                'contactMapsUrl' => $cinemaSettings->contactMapsUrl(),
                'mapsUrl' => $cinemaSettings->mapsUrl(),
                'mapsLabel' => $cinemaSettings->mapsLabel(),
                'socialSettings' => $cinemaSettings->socialSettings(),
                'formattedFooterText' => $cinemaSettings->formattedFooterText(),
                'showFloatingWhatsapp' => $cinemaSettings->showFloatingWhatsapp(),
                'isMemberProgramEnabled' => $cinemaSettings->isMemberProgramEnabled(),
            ];
        } catch (\Throwable $e) {
            $settingsData = [
                'siteName' => 'SynemaX',
                'siteTagline' => 'Studio Bioskop Mini Privat',
                'siteLogo' => null,
                'siteLogoDark' => null,
                'navbarBrandDisplay' => 'logo_text',
                'siteFaviconUrl' => null,
                'siteFaviconMime' => null,
                'navbarMenu' => [],
                'defaultTheme' => 'system',
                'showThemeToggle' => true,
                'capacityPerSession' => 6,
                'startingPrice' => 5000,
                'startingPriceDescription' => 'Mulai Rp 5.000',
                'capacityDescription' => '6 kursi privat per sesi',
                'contactWhatsapp' => '',
                'contactPhone' => '',
                'contactEmail' => '',
                'contactAddress' => '',
                'contactMapsUrl' => '',
                'mapsUrl' => '',
                'mapsLabel' => 'Lihat di Google Maps',
                'socialSettings' => [
                    'instagram' => ['url' => '', 'label' => ''],
                    'tiktok' => ['url' => '', 'label' => ''],
                    'youtube' => ['url' => '', 'label' => ''],
                ],
                'formattedFooterText' => '(c) '.date('Y').' SynemaX Cinema. Hak cipta dilindungi.',
                'showFloatingWhatsapp' => true,
                'isMemberProgramEnabled' => true,
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'is_admin' => (bool) ($request->user()->is_admin ?? false),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'cinemaSettings' => $settingsData,
        ];
    }
}
