<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\CinemaSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PublicMemberController extends Controller
{
    /**
     * Tampilkan kartu digital member bertanda tangan (HMAC APP_KEY).
     */
    public function show(Request $request, string $code, CinemaSettings $settings): Response
    {
        // 1. Validasi URL bertanda tangan (HMAC)
        if (! $request->hasValidSignature()) {
            abort(403, 'Tautan kartu digital tidak valid atau tanda tangan telah kedaluwarsa.');
        }

        // 2. Cari member berdasarkan code
        $member = Member::where('code', $code)->orWhere('member_code', $code)->firstOrFail();

        // 3. Validasi link_version: mencabut tautan lama bila link_version dinaikkan
        $version = (int) $request->query('v', 1);
        if ($version !== $member->link_version) {
            abort(403, 'Tautan kartu digital ini sudah dicabut atau digantikan dengan tautan baru.');
        }

        // Format SVG download langsung
        if ($request->query('format') === 'svg') {
            $svg = $this->renderCardSvg($member, $settings);
            $filename = 'kartu-member-synemax-'.strtolower((string) preg_replace('/[^A-Za-z0-9]/', '-', $member->formattedCode())).'.svg';

            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        // 4. Ambil 5 kunjungan terakhir yang sah
        $recentVisits = $member->validVisits()
            ->with('session.film')
            ->latest('id')
            ->take(5)
            ->get();

        $view = view('public.members.card', [
            'member' => $member,
            'recentVisits' => $recentVisits,
            'settings' => $settings,
        ]);

        return response($view)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Render kartu member dalam format berkas SVG siap unduh.
     */
    public function renderCardSvg(Member $member, CinemaSettings $settings): string
    {
        $siteName = htmlspecialchars(setting('site_name', 'SynemaX'), ENT_XML1, 'UTF-8');
        $rawDisplayName = $member->nickname ? "{$member->name} ({$member->nickname})" : $member->name;
        $displayName = htmlspecialchars($rawDisplayName, ENT_XML1, 'UTF-8');
        $code = htmlspecialchars($member->formattedCode(), ENT_XML1, 'UTF-8');

        // Mascot image as embedded data URI for 100% self-contained standalone SVG
        $mascotPath = public_path('images/member-mascot.jpg');
        $mascotUri = '';
        if (file_exists($mascotPath)) {
            $mascotUri = 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($mascotPath));
        }

        // Logo if present
        $logoRaw = setting('site_logo');
        $logoUri = '';
        if ($logoRaw && Storage::disk('public')->exists($logoRaw)) {
            $logoFull = Storage::disk('public')->path($logoRaw);
            if (file_exists($logoFull)) {
                $ext = strtolower(pathinfo($logoFull, PATHINFO_EXTENSION));
                $mime = ($ext === 'png') ? 'image/png' : (($ext === 'svg') ? 'image/svg+xml' : 'image/jpeg');
                $logoUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($logoFull));
            }
        }

        $qrRaw = generate_inline_qr_svg($member->formattedCode(), 220, 0);
        if (preg_match('/<svg[^>]*>(.*)<\/svg>/is', $qrRaw, $m)) {
            $qrInner = $m[1];
        } else {
            $qrInner = $qrRaw;
        }

        $logoMarkup = $logoUri
            ? '<image href="'.$logoUri.'" x="50" y="30" height="50" width="220" preserveAspectRatio="xMinYMid meet"/>'
            : '<text x="50" y="70" font-family="-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif" font-size="32" font-weight="900" fill="#0F172A">'.$siteName.'</text><circle cx="230" cy="60" r="6" fill="#F59E0B"/>';

        $mascotMarkup = $mascotUri
            ? '<image href="'.$mascotUri.'" x="50" y="145" width="200" height="200" preserveAspectRatio="xMidYMid slice" clip-path="url(#mascot-clip)"/>'
            : '<rect x="50" y="145" width="200" height="200" rx="26" ry="26" fill="#F1F5F9" stroke="#F59E0B" stroke-width="3"/>';

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="1050" height="660" viewBox="0 0 1050 660">
    <defs>
        <clipPath id="card-clip">
            <rect width="1050" height="660" rx="32" ry="32"/>
        </clipPath>
        <clipPath id="mascot-clip">
            <rect x="50" y="145" width="200" height="200" rx="26" ry="26"/>
        </clipPath>
    </defs>
    <g clip-path="url(#card-clip)">
        <!-- Card Background -->
        <rect width="1050" height="660" fill="#FFFFFF"/>

        <!-- Header -->
        <rect width="1050" height="110" fill="#F8FAFC"/>
        <line x1="0" y1="110" x2="1050" y2="110" stroke="#E2E8F0" stroke-width="2"/>
        {$logoMarkup}

        <!-- Badge KARTU MEMBER -->
        <rect x="820" y="34" width="180" height="42" rx="10" ry="10" fill="#0F172A" stroke="#D97706" stroke-width="2"/>
        <text x="910" y="60" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="15" font-weight="800" fill="#F59E0B" text-anchor="middle" dominant-baseline="middle">KARTU MEMBER</text>

        <!-- Mascot Frame -->
        {$mascotMarkup}
        <rect x="50" y="145" width="200" height="200" rx="26" ry="26" fill="none" stroke="#F59E0B" stroke-width="3"/>

        <!-- Member Info -->
        <text x="285" y="190" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="14" font-weight="700" fill="#94A3B8">NAMA MEMBER</text>
        <text x="285" y="230" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="32" font-weight="800" fill="#0F172A">{$displayName}</text>

        <text x="285" y="290" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="14" font-weight="700" fill="#94A3B8">ID MEMBER</text>
        <text x="285" y="330" font-family="ui-monospace, monospace" font-size="30" font-weight="800" fill="#B45309">{$code}</text>

        <!-- QR Box -->
        <rect x="730" y="135" width="270" height="270" rx="22" ry="22" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="2"/>
        <g transform="translate(755, 160)">
            {$qrInner}
        </g>
        <text x="865" y="425" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="13" font-weight="700" fill="#94A3B8" text-anchor="middle">SCAN DISINI</text>

        <!-- Footer -->
        <rect y="520" width="1050" height="140" fill="#F8FAFC"/>
        <line x1="0" y1="520" x2="1050" y2="520" stroke="#E2E8F0" stroke-width="2"/>
        <text x="50" y="585" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="18" font-weight="600" fill="#64748B">Studio Bioskop Mini Privat</text>
        <text x="1000" y="585" font-family="ui-monospace, monospace" font-size="18" font-weight="600" fill="#94A3B8" text-anchor="end">synemax.web.id</text>

        <!-- Bottom Accent Gold Bar -->
        <rect y="650" width="1050" height="10" fill="#F59E0B"/>

        <!-- Outer Border -->
        <rect x="1" y="1" width="1048" height="658" rx="32" ry="32" fill="none" stroke="#CBD5E1" stroke-width="2"/>
    </g>
</svg>
SVG;
    }
}
