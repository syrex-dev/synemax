@props([
    'variant' => 'inline', // inline | floating | admin
    'booking' => null,
    'message' => null,
    'phone' => null,
    'label' => null,
])

@php
    $settings = app(\App\Services\CinemaSettings::class);
    $rawPhone = $phone ?: $settings->contactWhatsapp();
    $digits = preg_replace('/[^\d]/', '', (string) $rawPhone);

    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    } elseif (! str_starts_with($digits, '62') && strlen($digits) >= 8) {
        $digits = '62' . $digits;
    }

    if (empty($digits)) {
        return;
    }

    if (! empty($message)) {
        $chatMessage = $message;
    } elseif ($booking) {
        $filmTitle = $booking->film?->title ?? 'Film Sesi';
        $sessionDate = format_cinema_date($booking->session?->session_date, true);
        $sessionTime = format_cinema_time($booking->session?->start_time);
        $chatMessage = "Halo admin SynemaX, saya mau tanya soal booking {$booking->code} ({$filmTitle}, {$sessionDate}, {$sessionTime}).";
    } else {
        $chatMessage = 'Halo admin SynemaX, saya mau bertanya.';
    }

    $waUrl = 'https://wa.me/' . $digits . '?text=' . rawurlencode($chatMessage);
    $defaultInlineLabel = $label ?: 'Chat admin via WhatsApp';
    $defaultAdminLabel = $label ?: 'WhatsApp pemesan';
@endphp

@if ($variant === 'inline')
    {{-- Inline WhatsApp Button (Public Ticket, Booking Result, Lookup, Payment) --}}
    <a href="{{ $waUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="inline-flex min-h-12 w-full items-center justify-center gap-2.5 rounded-lg border border-[#166534] bg-[#15803D] hover:bg-[#166534] active:bg-[#14532D] text-white px-5 text-[15px] font-bold shadow-none transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-[#15803D] focus:ring-offset-2 no-underline"
       aria-label="{{ $defaultInlineLabel }}">
        <svg class="size-5 shrink-0 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.449.741.963 1.199.662.59 1.221.774 1.394.861.173.087.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.144.39-.086.159.058 1.011.477 1.184.564.173.087.289.13.332.202.043.072.043.419-.101.824z"/>
        </svg>
        <span>{{ $defaultInlineLabel }}</span>
        {{ $slot }}
    </a>

@elseif ($variant === 'floating')
    {{-- Floating WhatsApp Action Button (Bisa ditutup oleh pengguna) --}}
    <div x-data="{
             waDismissed: localStorage.getItem('synemax_wa_dismissed') === '1'
         }"
         x-show="!waDismissed"
         x-cloak
         class="fixed right-4 z-40 flex items-center group"
         style="bottom: calc(var(--bottom-bar-h, 60px) + 16px + env(safe-area-inset-bottom, 0px));"
    >
        {{-- Tombol Tutup / Close --}}
        <button
            type="button"
            @click.stop.prevent="waDismissed = true; localStorage.setItem('synemax_wa_dismissed', '1')"
            class="absolute -top-1.5 -right-1.5 z-50 flex size-5 items-center justify-center rounded-full bg-slate-900 text-white hover:bg-black border border-white/50 shadow-md transition-transform hover:scale-110 focus:outline-none"
            aria-label="Tutup tombol WhatsApp"
            title="Tutup WhatsApp"
        >
            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <a href="{{ $waUrl }}"
           target="_blank"
           rel="noopener noreferrer"
           class="flex items-center justify-center border border-[#166534] bg-[#15803D] hover:bg-[#166534] active:bg-[#14532D] text-white shadow-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-[#15803D] focus:ring-offset-2 w-14 h-14 rounded-full ring-2 ring-[var(--color-bg-base,#0B0F17)] lg:w-auto lg:h-12 lg:px-4 lg:rounded-full lg:gap-2.5"
           aria-label="Chat admin via WhatsApp">
            <svg class="size-6 shrink-0 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.449.741.963 1.199.662.59 1.221.774 1.394.861.173.087.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.144.39-.086.159.058 1.011.477 1.184.564.173.087.289.13.332.202.043.072.043.419-.101.824z"/>
            </svg>
            <span class="text-sm font-bold hidden lg:inline">Chat admin</span>
        </a>
    </div>

@elseif ($variant === 'admin')
    {{-- Admin WhatsApp Button (Booking Detail, List, Today, Preparation) --}}
    <a href="{{ $waUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-[#166534] bg-[#15803D] hover:bg-[#166534] active:bg-[#14532D] text-white px-3.5 text-xs font-bold shadow-none transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-[#15803D] focus:ring-offset-2 no-underline"
       aria-label="{{ $defaultAdminLabel }}">
        <svg class="size-4 shrink-0 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.449.741.963 1.199.662.59 1.221.774 1.394.861.173.087.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.144.39-.086.159.058 1.011.477 1.184.564.173.087.289.13.332.202.043.072.043.419-.101.824z"/>
        </svg>
        <span>{{ $slot->isNotEmpty() ? $slot : $defaultAdminLabel }}</span>
    </a>
@endif
