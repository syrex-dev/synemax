<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\CinemaSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        $name = htmlspecialchars($member->name, ENT_XML1, 'UTF-8');
        $code = htmlspecialchars($member->formattedCode(), ENT_XML1, 'UTF-8');
        $cycleMax = $settings->memberFreeVisit();
        $nextPos = $member->nextVisitPosition($cycleMax);
        $inCycle = $nextPos - 1;
        $cycleNum = $member->currentCycle($cycleMax);
        $cycleText = htmlspecialchars("Kunjungan {$inCycle} dari {$cycleMax} · Siklus {$cycleNum}", ENT_XML1, 'UTF-8');
        $initial = htmlspecialchars(strtoupper(substr($member->name, 0, 1)), ENT_XML1, 'UTF-8');

        $qrRaw = generate_inline_qr_svg($member->formattedCode(), 220, 0);
        if (preg_match('/<svg[^>]*>(.*)<\/svg>/is', $qrRaw, $m)) {
            $qrInner = $m[1];
        } else {
            $qrInner = $qrRaw;
        }

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="1050" height="660" viewBox="0 0 1050 660">
    <defs>
        <clipPath id="card-clip">
            <rect width="1050" height="660" rx="32" ry="32"/>
        </clipPath>
    </defs>
    <g clip-path="url(#card-clip)">
        <rect width="1050" height="660" fill="#FFFFFF"/>
        <rect width="1050" height="110" fill="#F8FAFC"/>
        <line x1="0" y1="110" x2="1050" y2="110" stroke="#E2E8F0" stroke-width="2"/>
        <text x="50" y="70" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="32" font-weight="900" fill="#0F172A">{$siteName}</text>
        <circle cx="230" cy="60" r="6" fill="#F59E0B"/>
        <rect x="820" y="35" width="180" height="40" rx="10" ry="10" fill="#0F172A"/>
        <text x="910" y="60" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="15" font-weight="800" fill="#FFFFFF" text-anchor="middle" dominant-baseline="middle">KARTU MEMBER</text>
        <rect x="50" y="155" width="170" height="170" rx="24" ry="24" fill="#F1F5F9" stroke="#CBD5E1" stroke-width="2"/>
        <text x="135" y="260" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="76" font-weight="800" fill="#475569" text-anchor="middle">{$initial}</text>
        <text x="250" y="215" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="36" font-weight="800" fill="#0F172A">{$name}</text>
        <text x="250" y="255" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="20" font-weight="600" fill="#64748B">Program Kunjungan Berhadiah</text>
        <text x="250" y="292" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="18" font-weight="700" fill="#D97706">{$cycleText}</text>
        <rect x="730" y="145" width="270" height="270" rx="20" ry="20" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="2"/>
        <g transform="translate(755, 170)">
            {$qrInner}
        </g>
        <rect y="520" width="1050" height="140" fill="#F8FAFC"/>
        <line x1="0" y1="520" x2="1050" y2="520" stroke="#E2E8F0" stroke-width="2"/>
        <text x="50" y="560" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="14" font-weight="700" fill="#94A3B8">ID KARTU MEMBER</text>
        <text x="50" y="600" font-family="ui-monospace, monospace" font-size="28" font-weight="800" fill="#0F172A">{$code}</text>
        <text x="1000" y="575" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="15" font-weight="500" fill="#64748B" text-anchor="end">Tunjukkan kartu ini ke petugas saat menonton.</text>
        <text x="1000" y="600" font-family="ui-monospace, monospace" font-size="14" font-weight="600" fill="#94A3B8" text-anchor="end">synemax.web.id</text>
        <rect y="652" width="1050" height="8" fill="#F59E0B"/>
        <rect x="1" y="1" width="1048" height="658" rx="32" ry="32" fill="none" stroke="#CBD5E1" stroke-width="2"/>
    </g>
</svg>
SVG;
    }
}
