@extends('layouts.public')

@section('title', 'Kartu Member Digital: ' . $member->name . ' - ' . setting('site_name', 'SynemaX'))

@section('content')
@php
    $cycleMax = $settings->memberFreeVisit();
    $discountVisit = $settings->memberDiscountVisit();
    $nextPos = $member->nextVisitPosition($cycleMax);
    $inCycle = $nextPos - 1;
@endphp

<div class="mx-auto max-w-lg px-4 py-8 sm:px-6 space-y-6">
    {{-- Header --}}
    <div class="text-center space-y-2">
        <h1 class="text-xl font-bold tracking-tight text-text sm:text-2xl">Kartu Member Sinema</h1>
        <p class="text-xs text-text-muted">Tunjukkan kode QR ini ke petugas saat menonton untuk mencatat kunjungan Anda.</p>
    </div>

    {{-- Kartu Digital Utama --}}
    <div id="member-digital-card" class="rounded-3xl border border-border bg-surface p-6 sm:p-8 shadow-lg space-y-6 text-center">
        {{-- Foto & Nama --}}
        <div class="flex flex-col items-center space-y-3">
            <div class="size-20 rounded-full border-2 border-amber-500 overflow-hidden bg-surface-2 flex items-center justify-center font-bold text-2xl text-text-muted shadow-sm">
                @if ($member->photo_path)
                    <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" class="size-full object-cover">
                @else
                    <span>{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                @endif
            </div>
            <div>
                <h2 class="text-lg font-extrabold text-text">{{ $member->name }}</h2>
                <p class="text-xs font-mono font-bold tracking-wider text-amber-500 mt-0.5">{{ $member->formattedCode() }}</p>
            </div>
        </div>

        {{-- QR Code (Wajib selalu hitam di atas putih untuk kemudahan pemindaian kamera) --}}
        <div id="member-card-qr" class="inline-block rounded-2xl bg-white p-4 shadow-sm border border-slate-200">
            {!! generate_inline_qr_svg($member->formattedCode(), 190, 1) !!}
        </div>

        {{-- Progres Siklus Kunjungan --}}
        <div class="rounded-2xl border border-border bg-surface-2 p-4 space-y-3 text-left">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-text">Kunjungan {{ $inCycle }} dari {{ $cycleMax }}</span>
                <span class="text-text-muted font-semibold">Siklus {{ $member->currentCycle($cycleMax) }}</span>
            </div>

            <div class="relative h-2.5 w-full rounded-full bg-border overflow-hidden">
                <div
                    class="h-full rounded-full bg-amber-500 transition-all duration-300"
                    style="width: {{ ($inCycle / $cycleMax) * 100 }}%"
                ></div>
            </div>

            <p class="text-xs text-text-muted leading-relaxed">
                {{ $member->progressDescription($settings) }}
            </p>
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
            border: 1px solid #000000 !important;
            box-shadow: none !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            background: #ffffff !important;
            color: #000000 !important;
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

    {{-- 5 Kunjungan Terakhir --}}
    <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm space-y-3">
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
@endsection

