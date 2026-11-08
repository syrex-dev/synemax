@extends('layouts.public')

@php
    $displayName = $member->nickname ? "{$member->name} ({$member->nickname})" : $member->name;
    $logoRaw = setting('site_logo');
    $logoUrl = $logoRaw ? (str_starts_with($logoRaw, '/') || str_starts_with($logoRaw, 'http') ? $logoRaw : \Illuminate\Support\Facades\Storage::url($logoRaw)) : null;
    $mascotUrl = asset('images/member-mascot.jpg');
    $cycleMax = $settings->memberFreeVisit();
    $discountVisit = $settings->memberDiscountVisit();
    $nextPos = $member->nextVisitPosition($cycleMax);
    $inCycle = $nextPos - 1;
@endphp

@section('title', 'Kartu Member: ' . $displayName . ' - ' . setting('site_name', 'SynemaX'))

@section('content')
<div class="mx-auto max-w-lg px-4 py-8 sm:px-6 space-y-6">
    {{-- Header Halaman --}}
    <div class="text-center space-y-1.5 no-print">
        <h1 class="text-xl font-bold tracking-tight text-text sm:text-2xl">Kartu Member Bioskop</h1>
        <p class="text-xs text-text-muted">Tunjukkan kode QR ke petugas saat menonton untuk mencatat kunjungan & reward.</p>
    </div>

    {{-- KARTU MEMBER UTAMA (Eksklusif Siap Cetak & Unduh) --}}
    <div id="member-digital-card" class="rounded-3xl border border-border bg-surface p-5 sm:p-7 shadow-xl space-y-5 text-left border-b-[6px] border-b-amber-500 overflow-hidden relative">
        {{-- Header Kartu: Logo SynemaX & Badge --}}
        <div class="flex items-center justify-between border-b border-border/80 pb-3.5">
            <div class="flex items-center gap-2">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ setting('site_name', 'SynemaX') }}" class="h-7 sm:h-8 max-w-[140px] sm:max-w-[160px] object-contain">
                @else
                    <span class="text-base sm:text-lg font-black tracking-wider uppercase text-text">{{ setting('site_name', 'SynemaX') }}</span>
                @endif
            </div>
            <span class="rounded-md bg-slate-900 border border-amber-500/60 px-2.5 py-1 text-[10px] font-extrabold tracking-wider text-amber-400 uppercase">
                KARTU MEMBER
            </span>
        </div>

        {{-- Isi Kartu: Foto Maskot, Nama, ID Member & QR Code --}}
        <div class="flex items-center gap-3.5 sm:gap-5">
            {{-- Foto Maskot --}}
            <div class="size-20 sm:size-24 shrink-0 rounded-2xl border-2 border-amber-500/80 overflow-hidden bg-white shadow-sm flex items-center justify-center p-0.5">
                <img src="{{ $mascotUrl }}" alt="Maskot SynemaX" class="size-full object-cover rounded-xl">
            </div>

            {{-- Info Nama & ID Member --}}
            <div class="flex-1 min-w-0 space-y-2">
                <div>
                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-text-muted block">Nama Member</span>
                    <h2 class="text-sm sm:text-base font-extrabold text-text leading-tight truncate" title="{{ $displayName }}">
                        {{ $displayName }}
                    </h2>
                </div>
                <div>
                    <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-text-muted block">ID Member</span>
                    <p class="font-mono text-xs sm:text-sm font-extrabold tracking-wider text-amber-600 dark:text-amber-400">
                        {{ $member->formattedCode() }}
                    </p>
                </div>
            </div>

            {{-- QR Code (Barcode) --}}
            <div class="shrink-0 flex flex-col items-center">
                <div id="member-card-qr" class="size-20 sm:size-24 rounded-2xl bg-white p-1.5 shadow-sm border border-slate-200 flex items-center justify-center">
                    {!! generate_inline_qr_svg($member->formattedCode(), 120, 0) !!}
                </div>
                <span class="text-[8px] sm:text-[9px] font-bold text-text-muted uppercase tracking-wider mt-1">Scan Disini</span>
            </div>
        </div>

        {{-- Footer Kartu --}}
        <div class="flex items-center justify-between pt-2.5 border-t border-border/70 text-[10px] text-text-muted">
            <span>Studio Bioskop Mini Privat</span>
            <span class="font-mono text-[10px] text-text-muted">synemax.web.id</span>
        </div>
    </div>

    {{-- Tombol Aksi: Download Kartu & Cetak / PDF --}}
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 no-print">
        <button
            type="button"
            id="btn-download-member-card"
            onclick="downloadMemberCardPng()"
            class="w-full sm:w-auto flex-1 inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-black hover:bg-amber-400 active:scale-[0.98] transition shadow-sm"
        >
            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            <span id="btn-download-text">Download Kartu (PNG)</span>
        </button>

        <button
            type="button"
            onclick="window.print()"
            class="w-full sm:w-auto inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-border bg-surface px-5 py-2.5 text-xs font-semibold text-text hover:bg-surface-2 active:scale-[0.98] transition"
        >
            <svg class="size-4 shrink-0 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <span>Cetak / Simpan PDF</span>
        </button>
    </div>

    {{-- Progres Siklus Kunjungan & Reward (Fitur Pelanggan di Aplikasi) --}}
    <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm space-y-3 text-left no-print">
        <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-text">Progres Kunjungan</span>
            <span class="text-text-muted font-semibold">Siklus {{ $member->currentCycle($cycleMax) }}</span>
        </div>

        <div class="relative h-2.5 w-full rounded-full bg-border overflow-hidden">
            <div
                class="h-full rounded-full bg-amber-500 transition-all duration-300"
                style="width: {{ ($inCycle / $cycleMax) * 100 }}%"
            ></div>
        </div>

        <div class="flex items-center justify-between text-xs text-text-muted">
            <span>{{ $inCycle }} dari {{ $cycleMax }} kunjungan tercatat</span>
            <span class="font-semibold text-amber-600 dark:text-amber-400">
                {{ $member->progressDescription($settings) }}
            </span>
        </div>
    </div>

    {{-- 5 Kunjungan Terakhir --}}
    <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm space-y-3 no-print">
        <h3 class="text-xs font-bold uppercase tracking-wider text-text-muted">Kunjungan Terakhir</h3>

        @if ($recentVisits->isNotEmpty())
            <ul class="divide-y divide-border text-xs">
                @foreach ($recentVisits as $visit)
                    <li class="py-2.5 flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-text">{{ $visit->session?->film?->title ?? 'Sesi Penayangan' }}</p>
                            <p class="text-[11px] text-text-muted">
                                {{ $visit->created_at ? $visit->created_at->format('d M Y') : '-' }} · Posisi {{ $visit->position }}/{{ $cycleMax }}
                            </p>
                        </div>
                        @if ($visit->reward_type && $visit->reward_type !== 'none')
                            <span class="inline-flex items-center rounded-lg bg-amber-500/15 px-2 py-0.5 text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                {{ \App\Services\LoyaltyRules::rewardLabel($visit->reward_type) }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-xs text-text-muted py-2">Belum ada kunjungan yang tercatat.</p>
        @endif
    </div>
</div>

<style>
@media print {
    header, footer, nav, .no-print, button, a, [class*="z-[9999]"] {
        display: none !important;
    }
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #member-digital-card {
        border: 1px solid #cbd5e1 !important;
        border-bottom: 6px solid #f59e0b !important;
        box-shadow: none !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        background: #ffffff !important;
        color: #000000 !important;
        max-width: 85.6mm !important;
        margin: 0 auto !important;
    }
}
</style>

<x-member.card-download-script :member="$member" />

@if (request()->boolean('download'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (typeof window.downloadMemberCardPng === 'function') {
                    window.downloadMemberCardPng();
                }
            }, 400);
        });
    </script>
@endif
@endsection
