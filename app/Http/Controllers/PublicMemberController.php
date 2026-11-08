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
}
