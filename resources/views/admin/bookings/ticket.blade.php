@extends('layouts.admin')

@section('title', 'Pratinjau Tiket: ' . $booking->customer_name)

@push('styles')
<style>
    @page {
        size: A4 portrait;
        margin: 0;
    }
    @media print {
        header, aside, nav, footer, .no-print, button, a, [data-admin-sidebar], [data-admin-header], [class*="z-[9999]"] {
            display: none !important;
        }
        body, html {
            background: #ffffff !important;
            background-color: #ffffff !important;
            color: #0f172a !important;
            margin: 0 !important;
            padding: 0 !important;
            height: auto !important;
            min-height: 0 !important;
        }
        .flex.max-w-screen-2xl, .min-w-0.flex-1 {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        main {
            display: block !important;
            margin: 0 !important;
            padding: 10mm 0 0 0 !important;
        }
        .space-y-6 > :not(.print-ticket-container) {
            display: none !important;
        }
        .print-ticket-container {
            display: flex !important;
            justify-content: center !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        #printable-ticket {
            max-width: 380px !important;
            width: 380px !important;
            margin: 0 auto !important;
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 12px !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        #printable-ticket * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .tear-notch {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $session = $booking->session;
    $isConfirmed = $session ? $session->isConfirmed() : false;
    $isCancelled = $booking->status === 'dibatalkan';
    $isCompleted = in_array($booking->status, ['selesai', 'hadir', 'terlewat'], true);
    $isActive = $isConfirmed && ! $isCancelled && ! $isCompleted;
@endphp

<div class="space-y-6">
    {{-- Breadcrumb (Pengelola / Booking / nama pemesan / Pratinjau tiket) --}}
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-medium text-text-muted no-print">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-text transition">Pengelola</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('admin.bookings.index') }}" class="hover:text-text transition">Booking</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('admin.bookings.show', $booking) }}" class="hover:text-text transition truncate max-w-[150px] sm:max-w-xs">{{ $booking->customer_name }}</a>
        <span aria-hidden="true">/</span>
        <span class="font-bold text-text" aria-current="page">Pratinjau tiket</span>
    </nav>

    {{-- Keterangan kecil --}}
    <div class="no-print">
        <p class="text-xs text-text-muted">
            Ini tampilan tiket yang diterima pelanggan.
        </p>
    </div>

    {{-- Kartu Tiket --}}
    <div class="flex justify-center print-ticket-container">
        <x-ticket.card :booking="$booking" mode="preview" />
    </div>

    {{-- Toolbar Admin: Cetak & Kembali ke detail booking --}}
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2 no-print max-w-[400px] mx-auto w-full">
        @if ($isActive)
            <button type="button"
                    onclick="window.print()"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 px-5 text-sm font-bold border border-amber-500/30 transition">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.076-.667-2.197-1.28-3.183A9.972 9.972 0 0112 3a9.972 9.972 0 016.56 2.646c-.613.986-1.04 2.107-1.28 3.183m-10.56 0A9.96 9.96 0 003 12c0 2.478.9 4.747 2.395 6.486M17.28 13.829A9.96 9.96 0 0121 12c0-2.478-.9-4.747-2.395-6.486m0 0a9.98 9.98 0 00-2.165-2.004M6.72 13.829a9.974 9.974 0 00-2.165 2.004M12 15a3 3 0 100-6 3 3 0 000 6z" />
                </svg>
                <span>Cetak</span>
            </button>
        @else
            <div class="text-center text-xs text-text-muted px-3 py-2 rounded-xl border border-border bg-surface w-full">
                @if ($isCancelled)
                    Tiket tidak dapat dicetak karena booking dibatalkan.
                @elseif ($isCompleted)
                    Tiket tidak dapat dicetak karena sesi telah selesai.
                @else
                    Tiket dapat dicetak setelah sesi dipastikan tayang.
                @endif
            </div>
        @endif

        <a href="{{ route('admin.bookings.show', $booking) }}"
           class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition">
            Kembali ke detail booking
        </a>
    </div>
</div>
@endsection
