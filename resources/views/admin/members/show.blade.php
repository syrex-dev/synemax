@extends('layouts.admin')

@section('title', 'Detail Member: ' . $member->name . ' - Panel Admin SynemaX')

@section('content')
@php
    $cycleMax = $settings->memberFreeVisit();
    $discountVisit = $settings->memberDiscountVisit();
    $nextPos = $member->nextVisitPosition($cycleMax);
    $inCycle = $nextPos - 1;
    $digitalUrl = $member->digitalCardSignedUrl();
@endphp

<div class="space-y-6" x-data="{
    activeTab: 'visits',
    copySuccess: false,
    copyUrlSuccess: false,
    newLinkModalOpen: false,
    statusModalOpen: false,
    openingModalOpen: false,
    deleteModalOpen: false,
    voidVisitModalOpen: false,
    voidVisitId: null,
    copyText(text, target) {
        navigator.clipboard.writeText(text).then(() => {
            if (target === 'id') {
                this.copySuccess = true;
                setTimeout(() => this.copySuccess = false, 2500);
            } else if (target === 'url') {
                this.copyUrlSuccess = true;
                setTimeout(() => this.copyUrlSuccess = false, 2500);
            }
        });
    },
    openVoidVisit(id) {
        this.voidVisitId = id;
        this.voidVisitModalOpen = true;
    }
}">
    {{-- Header Member --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <div class="size-16 shrink-0 rounded-2xl border border-border bg-surface-2 overflow-hidden flex items-center justify-center font-bold text-xl text-text-muted">
                @if ($member->photo_path)
                    <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" class="size-full object-cover">
                @else
                    <span>{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                @endif
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-bold tracking-tight text-text sm:text-2xl">{{ $member->name }}</h1>
                    <x-ui.status
                        :status="match($member->status) {
                            'aktif' => 'terkonfirmasi',
                            'diblokir' => 'dibatalkan',
                            default => 'menunggu_pembayaran',
                        }"
                        :label="match($member->status) {
                            'aktif' => 'Aktif',
                            'diblokir' => 'Diblokir',
                            default => 'Nonaktif',
                        }"
                    />
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-text-muted">
                    <span class="font-mono font-bold text-text">{{ $member->formattedCode() }}</span>
                    @if ($member->nickname)
                        <span>Panggilan: {{ $member->nickname }}</span>
                    @endif
                    <span>Terdaftar: {{ $member->created_at ? $member->created_at->format('d M Y') : '-' }}</span>
                </div>
            </div>
        </div>

        {{-- Tombol Aksi Cepat --}}
        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('admin.members.print_single', $member) }}"
                target="_blank"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Kartu</span>
            </a>
            <button
                type="button"
                id="btn-download-member-card"
                onclick="downloadMemberCardPng()"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span id="btn-download-text">Download Kartu (PNG)</span>
            </button>
            <button
                type="button"
                @click="copyText('{{ $digitalUrl }}', 'url')"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                </svg>
                <span x-text="copyUrlSuccess ? 'Tautan Disalin!' : 'Salin Tautan Kartu'"></span>
            </button>
            <a
                href="{{ route('admin.members.edit', $member) }}"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                <span>Ubah</span>
            </a>
            <a
                href="{{ route('admin.members.index') }}"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
            >
                <span>Semua Member</span>
            </a>
        </div>
    </div>

    {{-- Feedback Alerts --}}
    @if (session('status'))
        <div role="alert" class="flex items-center gap-3 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
            <svg class="size-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div role="alert" class="flex items-center gap-3 rounded-2xl border border-red-500/40 bg-red-500/10 p-4 text-xs font-semibold text-red-600 dark:text-red-400">
            <svg class="size-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Panel Sukses Setelah Pendaftaran Baru --}}
    @if (session('member_created_success'))
        <div class="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-6 space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-base font-bold text-amber-600 dark:text-amber-400">Member Berhasil Didaftarkan</h2>
                    <p class="text-xs text-text-muted mt-1">Kartu member siap dicetak atau dibagikan ke pelanggan via tautan digital.</p>
                </div>
                <div class="bg-white p-2 rounded-xl shadow-sm">
                    {!! generate_inline_qr_svg($member->formattedCode(), 90, 1) !!}
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-2">
                <button
                    type="button"
                    @click="copyText('{{ $member->formattedCode() }}', 'id')"
                    class="inline-flex min-h-[40px] items-center gap-2 rounded-xl bg-surface px-4 py-2 text-xs font-bold text-text border border-border hover:bg-surface-2 transition"
                >
                    <span class="font-mono">{{ $member->formattedCode() }}</span>
                    <span class="text-amber-500" x-text="copySuccess ? 'Tersalin' : 'Salin ID'"></span>
                </button>
                <a
                    href="{{ route('admin.members.print_single', $member) }}"
                    target="_blank"
                    class="inline-flex min-h-[40px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-black hover:bg-amber-400 transition"
                >
                    Cetak Kartu
                </a>
                <button
                    type="button"
                    onclick="downloadMemberCardPng()"
                    class="inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Download Kartu (PNG)</span>
                </button>
                <button
                    type="button"
                    @click="copyText('{{ $digitalUrl }}', 'url')"
                    class="inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
                >
                    <span x-text="copyUrlSuccess ? 'Tautan Disalin!' : 'Salin Tautan Kartu Digital'"></span>
                </button>
                @if (session('member_wa_text'))
                    <a
                        href="https://wa.me/?text={{ session('member_wa_text') }}"
                        target="_blank"
                        class="inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 transition"
                    >
                        Kirim lewat WhatsApp
                    </a>
                @endif
                <a
                    href="{{ route('admin.members.create') }}"
                    class="inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
                >
                    Tambah Member Lagi
                </a>
            </div>
        </div>
    @endif

    {{-- Kartu Progres Siklus 10 Kunjungan --}}
    <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-border pb-3">
            <div>
                <h2 class="text-sm font-bold text-text">Progres Siklus Kunjungan</h2>
                <p class="text-xs text-text-muted mt-0.5">{{ $member->progressDescription($settings) }}</p>
            </div>
            <div class="text-xs font-semibold text-text-muted">
                Total Kunjungan Sah: <strong class="text-text font-bold">{{ $member->totalValidVisits() }}</strong> (Siklus {{ $member->currentCycle($cycleMax) }})
            </div>
        </div>

        {{-- 10 Kotak Visual Langkah --}}
        <div class="grid grid-cols-5 sm:grid-cols-10 gap-2 pt-2">
            @for ($step = 1; $step <= $cycleMax; $step++)
                @php
                    $isCompleted = ($step <= $inCycle);
                    $isCurrent = ($step === $nextPos);
                    $isDiscount = ($step === $discountVisit);
                    $isFree = ($step === $cycleMax);
                @endphp
                <div class="rounded-xl border p-2 text-center transition-all {{ $isCompleted ? 'border-amber-500 bg-amber-500/15 text-amber-600 dark:text-amber-400 font-bold' : ($isCurrent ? 'border-amber-500 bg-surface-2 ring-2 ring-amber-500/30 font-bold text-text' : 'border-border bg-surface-2 text-text-muted') }}">
                    <div class="text-sm font-extrabold">{{ $step }}</div>
                    <div class="mt-1 text-[9px] font-semibold uppercase tracking-wider">
                        @if ($isDiscount)
                            <span class="text-amber-500 font-bold">Diskon</span>
                        @elseif ($isFree)
                            <span class="text-emerald-500 font-bold">Gratis</span>
                        @else
                            <span>Normal</span>
                        @endif
                    </div>
                </div>
            @endfor
        </div>
    </div>

    {{-- Navigasi Tab Riwayat, Reward, Jejak --}}
    <div class="border-b border-border">
        <nav class="flex space-x-2" aria-label="Tabs Member">
            <button
                type="button"
                @click="activeTab = 'visits'"
                :class="activeTab === 'visits' ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-bold bg-amber-500/10' : 'border-transparent text-text-muted hover:text-text'"
                class="whitespace-nowrap border-b-2 px-4 py-3 text-xs font-semibold rounded-t-xl transition-colors"
            >
                Riwayat Kunjungan ({{ $visits->count() }})
            </button>
            <button
                type="button"
                @click="activeTab = 'rewards'"
                :class="activeTab === 'rewards' ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-bold bg-amber-500/10' : 'border-transparent text-text-muted hover:text-text'"
                class="whitespace-nowrap border-b-2 px-4 py-3 text-xs font-semibold rounded-t-xl transition-colors"
            >
                Reward & Diskon ({{ $rewards->count() }})
            </button>
            <button
                type="button"
                @click="activeTab = 'events'"
                :class="activeTab === 'events' ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-bold bg-amber-500/10' : 'border-transparent text-text-muted hover:text-text'"
                class="whitespace-nowrap border-b-2 px-4 py-3 text-xs font-semibold rounded-t-xl transition-colors"
            >
                Jejak Audit ({{ $events->count() }})
            </button>
            <button
                type="button"
                @click="activeTab = 'tools'"
                :class="activeTab === 'tools' ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-bold bg-amber-500/10' : 'border-transparent text-text-muted hover:text-text'"
                class="whitespace-nowrap border-b-2 px-4 py-3 text-xs font-semibold rounded-t-xl transition-colors"
            >
                Pengaturan & Keamanan
            </button>
        </nav>
    </div>

    {{-- TAB 1: RIWAYAT KUNJUNGAN --}}
    <div x-show="activeTab === 'visits'" class="space-y-4">
        <div class="rounded-2xl border border-border bg-surface overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Sesi / Film</th>
                            <th class="px-4 py-3 text-center">Posisi</th>
                            <th class="px-4 py-3">Reward Tercatat</th>
                            <th class="px-4 py-3">Metode</th>
                            <th class="px-4 py-3">Petugas</th>
                            <th class="px-4 py-3 text-right">Status / Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($visits as $visit)
                            <tr class="hover:bg-surface-2/60 transition {{ $visit->isVoided() ? 'opacity-50 line-through bg-surface-2/30' : '' }}">
                                <td class="px-4 py-3 text-text-muted whitespace-nowrap">
                                    <div>{{ $visit->created_at ? $visit->created_at->format('d/m/Y') : '-' }}</div>
                                    <div class="text-[10px]">{{ $visit->created_at ? $visit->created_at->format('H.i') . ' WIB' : '' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-text">{{ $visit->session?->film?->title ?? 'Sesi Penayangan' }}</div>
                                    <div class="text-[11px] text-text-muted">
                                        {{ $visit->session ? format_cinema_time($visit->session->start_time) : '-' }}
                                        @if ($visit->booking)
                                            · Kode: {{ $visit->booking->code }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center rounded-lg bg-surface-2 px-2.5 py-1 font-bold text-text">
                                        {{ $visit->position ?? $visit->visit_number }}/{{ $cycleMax }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($visit->reward_type && $visit->reward_type !== 'none')
                                        <span class="font-semibold text-amber-500">
                                            {{ \App\Services\LoyaltyRules::rewardLabel($visit->reward_type) }}
                                        </span>
                                    @else
                                        <span class="text-text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-text-muted uppercase text-[11px]">
                                    {{ $visit->method }}
                                </td>
                                <td class="px-4 py-3 text-text-muted">
                                    {{ $visit->recordedByUser?->name ?? 'Sistem' }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ($visit->isVoided())
                                        <span class="text-[11px] font-semibold text-red-500" title="Dibatalkan: {{ $visit->void_reason }}">
                                            Dibatalkan
                                        </span>
                                    @elseif ($lastValidVisit && $lastValidVisit->id === $visit->id)
                                        <button
                                            type="button"
                                            @click="openVoidVisit({{ $visit->id }})"
                                            class="inline-flex min-h-[32px] items-center rounded-lg border border-red-500/30 bg-red-500/5 px-2.5 py-1 text-[11px] font-semibold text-red-500 hover:bg-red-500/10 transition"
                                        >
                                            Batalkan
                                        </button>
                                    @else
                                        <span class="text-[11px] font-semibold text-emerald-500">Sah</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-text-muted">
                                    Belum ada riwayat kunjungan yang tercatat untuk member ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 2: REWARD --}}
    <div x-show="activeTab === 'rewards'" class="space-y-4" style="display: none;">
        <div class="rounded-2xl border border-border bg-surface overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                        <tr>
                            <th class="px-4 py-3">Jenis Reward</th>
                            <th class="px-4 py-3">Nilai (Rp)</th>
                            <th class="px-4 py-3">Metode Penyelesaian</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Petugas / Waktu</th>
                            <th class="px-4 py-3">Catatan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($rewards as $reward)
                            <tr class="hover:bg-surface-2/60 transition">
                                <td class="px-4 py-3 font-bold text-text">
                                    {{ $reward->label() }}
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-text">
                                    Rp {{ number_format($reward->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-text-muted">
                                    {{ $reward->settlementLabel() }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.status
                                        :status="match($reward->status) {
                                            'given' => 'terkonfirmasi',
                                            'skipped' => 'dibatalkan',
                                            default => 'menunggu_pembayaran',
                                        }"
                                        :label="match($reward->status) {
                                            'given' => 'Sudah Diberikan',
                                            'skipped' => 'Dilewati',
                                            default => 'Pending',
                                        }"
                                    />
                                </td>
                                <td class="px-4 py-3 text-text-muted">
                                    @if ($reward->given_at)
                                        <div>{{ $reward->givenByUser?->name ?? 'Admin' }}</div>
                                        <div class="text-[10px]">{{ $reward->given_at->format('d/m/Y H.i') }} WIB</div>
                                    @else
                                        <span>-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-text-muted max-w-xs truncate">
                                    {{ $reward->note ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ($reward->status === 'pending')
                                        <div class="flex items-center justify-end gap-1.5">
                                            <form method="POST" action="{{ route('admin.members.settle_reward', [$member, $reward]) }}">
                                                @csrf
                                                <input type="hidden" name="action" value="given">
                                                <button
                                                    type="submit"
                                                    class="inline-flex min-h-[32px] items-center rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-emerald-500 transition"
                                                >
                                                    Sudah Diberikan
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.members.settle_reward', [$member, $reward]) }}">
                                                @csrf
                                                <input type="hidden" name="action" value="skipped">
                                                <button
                                                    type="submit"
                                                    class="inline-flex min-h-[32px] items-center rounded-lg border border-border bg-surface px-2.5 py-1 text-[11px] font-semibold text-text-muted hover:text-text hover:bg-surface-2 transition"
                                                >
                                                    Lewati
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-text-muted text-[11px]">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-text-muted">
                                    Belum ada reward yang tercatat untuk member ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 3: JEJAK AUDIT --}}
    <div x-show="activeTab === 'events'" class="space-y-4" style="display: none;">
        <div class="rounded-2xl border border-border bg-surface overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Aktivitas</th>
                            <th class="px-4 py-3">Pengguna</th>
                            <th class="px-4 py-3">Detail Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($events as $event)
                            <tr class="hover:bg-surface-2/60 transition">
                                <td class="px-4 py-3 text-text-muted whitespace-nowrap">
                                    {{ $event->created_at ? $event->created_at->format('d/m/Y H.i') . ' WIB' : '-' }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-text">
                                    {{ match($event->event) {
                                        'created' => 'Member Dibuat',
                                        'updated' => 'Data Profil Diperbarui',
                                        'status_changed' => 'Perubahan Status',
                                        'link_regenerated' => 'Tautan Kartu Dibuat Ulang',
                                        'visit_added' => 'Kunjungan Dicatat',
                                        'visit_voided' => 'Kunjungan Dibatalkan',
                                        'reward_given' => 'Reward Diselesaikan',
                                        'reward_skipped' => 'Reward Dilewati',
                                        'opening_changed' => 'Koreksi Saldo Awal',
                                        'deleted' => 'Member Dihapus (Soft Delete)',
                                        default => $event->event,
                                    } }}
                                </td>
                                <td class="px-4 py-3 text-text-muted">
                                    {{ $event->user?->name ?? 'Sistem' }}
                                </td>
                                <td class="px-4 py-3 text-text-muted text-[11px] font-mono">
                                    @if ($event->meta)
                                        <pre class="whitespace-pre-wrap font-sans text-xs text-text-muted">{{ json_encode($event->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                    @else
                                        <span>-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-text-muted">
                                    Belum ada jejak audit aktivitas untuk member ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 4: PENGATURAN & KEAMANAN --}}
    <div x-show="activeTab === 'tools'" class="space-y-6" style="display: none;">
        {{-- Pencabutan Tautan Kartu Digital --}}
        <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-text">Keamanan Tautan Kartu Digital</h3>
            <p class="text-xs text-text-muted leading-relaxed">
                Kartu digital member saat ini menggunakan versi tautan <strong class="text-text">v{{ $member->link_version }}</strong>. Jika tautan bocor atau dibagikan tanpa izin, Anda dapat membuat tautan baru. Tautan lama otomatis tidak akan bisa dibuka lagi.
            </p>
            <div class="pt-2">
                <button
                    type="button"
                    @click="newLinkModalOpen = true"
                    class="min-h-[40px] inline-flex items-center rounded-xl border border-border bg-surface-2 px-4 py-2 text-xs font-semibold text-text hover:bg-surface hover:border-amber-500 transition"
                >
                    Cabut & Buat Tautan Kartu Baru
                </button>
            </div>
        </div>

        {{-- Koreksi Saldo Awal Kunjungan --}}
        <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-text">Koreksi Saldo Awal Kunjungan</h3>
            <p class="text-xs text-text-muted leading-relaxed">
                Saldo awal saat ini: <strong class="text-text">{{ $member->opening_visits }} kunjungan</strong>. Perubahan saldo awal membutuhkan alasan wajib untuk jejak audit.
            </p>
            <div class="pt-2">
                <button
                    type="button"
                    @click="openingModalOpen = true"
                    class="min-h-[40px] inline-flex items-center rounded-xl border border-border bg-surface-2 px-4 py-2 text-xs font-semibold text-text hover:bg-surface hover:border-amber-500 transition"
                >
                    Koreksi Saldo Awal
                </button>
            </div>
        </div>

        {{-- Status Member & Pemblokiran --}}
        <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-text">Status & Pemblokiran</h3>
            <p class="text-xs text-text-muted leading-relaxed">
                Status saat ini: <strong class="text-text uppercase">{{ $member->status }}</strong>. Member nonaktif atau diblokir tidak dapat dicatat kunjungannya pada panel scan tiket.
            </p>
            <div class="pt-2">
                <button
                    type="button"
                    @click="statusModalOpen = true"
                    class="min-h-[40px] inline-flex items-center rounded-xl border border-border bg-surface-2 px-4 py-2 text-xs font-semibold text-text hover:bg-surface hover:border-amber-500 transition"
                >
                    Ubah Status / Blokir Member
                </button>
            </div>
        </div>

        {{-- Hapus Member (Soft Delete) --}}
        <div class="rounded-2xl border border-red-500/30 bg-red-500/5 p-6 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-red-500">Hapus Member</h3>
            <p class="text-xs text-text-muted leading-relaxed">
                Menghapus member secara soft-delete. Riwayat kunjungan dan tiket tetap tersimpan di database untuk integritas laporan keuangan bioskop.
            </p>
            <div class="pt-2">
                <button
                    type="button"
                    @click="deleteModalOpen = true"
                    class="min-h-[40px] inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-500 transition"
                >
                    Hapus Member Ini
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL IN-APP: BATALKAN KUNJUNGAN TERAKHIR --}}
    <div
        x-show="voidVisitModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        style="display: none;"
    >
        <div @click.away="voidVisitModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-base font-bold text-text">Batalkan Kunjungan Terakhir</h3>
                    <p class="text-xs text-text-muted mt-1">Pembatalan ini akan mengurangi total kunjungan dan membatalkan reward terkait.</p>
                </div>
                <button type="button" @click="voidVisitModalOpen = false" class="p-1 text-text-muted hover:text-text rounded-lg">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="'{{ url('/admin/members/' . $member->id . '/void-visit') }}/' + voidVisitId" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="void_reason" class="block text-xs font-semibold text-text-muted mb-1.5">
                        Alasan Pembatalan <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="void_reason"
                        name="reason"
                        rows="3"
                        required
                        placeholder="Contoh: Salah scan barcode / member batal menonton..."
                        class="w-full rounded-xl border border-border bg-surface-2 p-3 text-xs text-text focus:border-red-500 focus:ring-1 focus:ring-red-500/30"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="voidVisitModalOpen = false" class="min-h-[40px] rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition">
                        Batal
                    </button>
                    <button type="submit" class="min-h-[40px] rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-500 transition">
                        Ya, Batalkan Kunjungan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL IN-APP: BUAT TAUTAN BARU --}}
    <div
        x-show="newLinkModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        style="display: none;"
    >
        <div @click.away="newLinkModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4">
            <h3 class="text-base font-bold text-text">Cabut & Buat Tautan Baru?</h3>
            <p class="text-xs text-text-muted leading-relaxed">
                Tindakan ini akan menaikkan versi tautan menjadi <strong>v{{ $member->link_version + 1 }}</strong>. Tautan kartu digital yang lama tidak akan bisa dibuka lagi.
            </p>
            <form method="POST" action="{{ route('admin.members.regenerate_link', $member) }}" class="flex justify-end gap-2 pt-2">
                @csrf
                <button type="button" @click="newLinkModalOpen = false" class="min-h-[40px] rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition">
                    Batal
                </button>
                <button type="submit" class="min-h-[40px] rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-black hover:bg-amber-400 transition">
                    Ya, Buat Tautan Baru
                </button>
            </form>
        </div>
    </div>

    {{-- MODAL IN-APP: UBAH STATUS MEMBER --}}
    <div
        x-show="statusModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        style="display: none;"
    >
        <div @click.away="statusModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4">
            <h3 class="text-base font-bold text-text">Ubah Status Member</h3>
            <form method="POST" action="{{ route('admin.members.toggle_status', $member) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="status_select" class="block text-xs font-semibold text-text-muted mb-1.5">Pilih Status Baru</label>
                    <x-ui.select id="status_select" name="status" class="w-full min-h-[44px] text-xs">
                        <option value="aktif" {{ $member->status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ $member->status === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        <option value="diblokir" {{ $member->status === 'diblokir' ? 'selected' : '' }}>Diblokir</option>
                    </x-ui.select>
                </div>
                <div>
                    <label for="status_reason" class="block text-xs font-semibold text-text-muted mb-1.5">Alasan Perubahan</label>
                    <input
                        type="text"
                        id="status_reason"
                        name="reason"
                        placeholder="Contoh: Permintaan pelanggan / pelanggaran tata tertib..."
                        class="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-xs text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                    >
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="statusModalOpen = false" class="min-h-[40px] rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition">
                        Batal
                    </button>
                    <button type="submit" class="min-h-[40px] rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-black hover:bg-amber-400 transition">
                        Simpan Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL IN-APP: KOREKSI SALDO AWAL --}}
    <div
        x-show="openingModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        style="display: none;"
    >
        <div @click.away="openingModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4">
            <h3 class="text-base font-bold text-text">Koreksi Saldo Awal Kunjungan</h3>
            <form method="POST" action="{{ route('admin.members.update_opening_visits', $member) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="opening_visits_input" class="block text-xs font-semibold text-text-muted mb-1.5">Saldo Awal Baru</label>
                    <x-ui.select
                        id="opening_visits_input"
                        name="opening_visits"
                        :options="collect(range(0, 9))->map(fn($i) => ['value' => (string)$i, 'label' => $i . ' kunjungan'])->all()"
                        :value="(string) old('opening_visits', $member->opening_visits)"
                        class="w-full min-h-[44px] text-xs"
                    />
                </div>
                <div>
                    <label for="opening_reason" class="block text-xs font-semibold text-text-muted mb-1.5">Alasan Koreksi <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="opening_reason"
                        name="reason"
                        required
                        placeholder="Contoh: Kesalahan input stempel kartu lama..."
                        class="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-xs text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                    >
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openingModalOpen = false" class="min-h-[40px] rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition">
                        Batal
                    </button>
                    <button type="submit" class="min-h-[40px] rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-black hover:bg-amber-400 transition">
                        Simpan Koreksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL IN-APP: HAPUS MEMBER (SOFT DELETE) --}}
    <div
        x-show="deleteModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        style="display: none;"
    >
        <div @click.away="deleteModalOpen = false" class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4">
            <h3 class="text-base font-bold text-text">Hapus Member: {{ $member->name }}</h3>
            <p class="text-xs text-text-muted leading-relaxed">
                Tindakan ini akan menghapus member secara soft-delete.
            </p>
            <form method="POST" action="{{ route('admin.members.destroy', $member) }}" class="space-y-4">
                @csrf
                @method('DELETE')
                <div>
                    <label for="destroy_reason" class="block text-xs font-semibold text-text-muted mb-1.5">Alasan Penghapusan <span class="text-red-500">*</span></label>
                    <textarea
                        id="destroy_reason"
                        name="reason"
                        rows="3"
                        required
                        placeholder="Tulis alasan penghapusan member..."
                        class="w-full rounded-xl border border-border bg-surface-2 p-3 text-xs text-text focus:border-red-500 focus:ring-1 focus:ring-red-500/30"
                    ></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="deleteModalOpen = false" class="min-h-[40px] rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition">
                        Batal
                    </button>
                    <button type="submit" class="min-h-[40px] rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-500 transition">
                        Ya, Hapus Member
                    </button>
                </div>
            </form>
        </div>
    </div>

    <x-member.card-download-script :member="$member" />
</div>
@endsection

