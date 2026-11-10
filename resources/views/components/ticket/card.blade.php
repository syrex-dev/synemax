@props([
    'booking',
    'mode' => 'customer',
])

@php
    $flowState = $booking->flowState();
    $film = $booking->film;
    $session = $booking->session;
    $isCancelled = $flowState === 'dibatalkan';
    $isExpired = $flowState === 'kedaluwarsa';
    $isCompleted = in_array($flowState, ['selesai', 'terlewat', 'hadir', 'tidak_datang'], true);
    $isWaitingPenonton = $flowState === 'menunggu_penonton';
    $isDiproses = $flowState === 'diproses';
    $isAktif = $flowState === 'aktif';

    $candidateTallies = ($session && method_exists($session, 'candidateFilmTallies')) ? $session->candidateFilmTallies() : collect();
    $currentSeats = $booking->seats_reserved > 0 ? $booking->seats_reserved : ($booking->people_count ?? 6);
    $myFilmTally = $session ? ($candidateTallies->firstWhere('film_id', $booking->film_id)['seats'] ?? $currentSeats) : $currentSeats;
    $quorum = $session ? $session->quorum() : 3;
    $shortage = max(0, $quorum - $myFilmTally);

    $settings = app(\App\Services\CinemaSettings::class);
    $studioName = $settings->ticketStudioName();
    $arriveMinutes = $settings->ticketArriveMinutes();
    $address = $settings->contactAddress();
    $whatsapp = $settings->contactWhatsapp();
    $email = $settings->contactEmail();

    // Ambil item jajan: tetap sertakan jika booking kedaluwarsa/batal agar rincian total biaya transparan
    $displayItems = $booking->items->where('status', '!=', 'dibatalkan');
    if ($displayItems->isEmpty() && $booking->items->isNotEmpty() && $booking->snack_total > 0) {
        $displayItems = $booking->items;
    }
@endphp

<div id="printable-ticket" class="print-ticket w-full mx-auto bg-white text-slate-900 rounded-[12px] border border-slate-300 dark:border-slate-300 shadow-none overflow-hidden select-none break-inside-avoid [page-break-inside:avoid] print:max-w-none print:w-full print:border-none print:rounded-none">

    {{-- 1. STRIP MEREK (Tinggi 48px, Latar Putih) --}}
    <div class="h-12 px-4 flex items-center justify-between border-b border-slate-200 bg-white">
        <div class="flex items-center gap-2">
            <x-brand.logo size="sm" forcedTheme="light" />
            <span class="sr-only">Tiket masuk studio</span>
        </div>
        
        <div>
            @if ($isCancelled)
                <x-ui.status status="dibatalkan">Dibatalkan</x-ui.status>
            @elseif ($isExpired)
                <x-ui.status status="kedaluwarsa">Kedaluwarsa</x-ui.status>
            @elseif ($isDiproses)
                <x-ui.status status="menunggu_konfirmasi">Menunggu konfirmasi</x-ui.status>
            @elseif ($isWaitingPenonton)
                <x-ui.status status="menunggu_penonton">Menunggu penonton</x-ui.status>
            @elseif ($isCompleted)
                <x-ui.status status="selesai">Selesai</x-ui.status>
            @else
                <x-ui.status status="terkonfirmasi">Tiket aktif</x-ui.status>
            @endif
        </div>
    </div>

    {{-- 2. HEADER FOTO (Tinggi ~168px, Latar Blur + Poster Tajam) --}}
    <div class="h-[168px] relative overflow-hidden flex items-center p-4 bg-slate-900">
        {{-- Blur Background --}}
        @if ($film && $film->posterUrl())
            <div class="absolute inset-0 bg-cover bg-top filter blur-md scale-110 opacity-70"
                 style="background-image: url('{{ $film->posterUrl() }}');"></div>
        @else
            <div class="absolute inset-0 bg-slate-900"></div>
        @endif

        {{-- Dark 55% Overlay for Text Contrast --}}
        <div class="absolute inset-0 bg-black/55"></div>

        {{-- Sharp Poster 72x108 dengan Fallback Warna Polos Tanpa Bocoran Teks Alt --}}
        <div class="relative z-10 bg-slate-800 shrink-0 w-[72px] h-[108px] rounded-lg border-2 border-white overflow-hidden shadow-sm flex items-center justify-center">
            @if ($film && $film->posterUrl())
                <img src="{{ $film->posterUrl() }}"
                     alt=""
                     onerror="this.style.display='none'"
                     class="w-full h-full object-cover {{ ($isCancelled || $isExpired) ? 'grayscale' : '' }}">
            @endif
        </div>

        {{-- Title & Metadata --}}
        <div class="relative z-10 ml-3 min-w-0 flex-1 space-y-1">
            <h2 class="text-[20px] font-bold text-white leading-tight line-clamp-2">
                {{ $film?->title ?? 'Film Sesi' }}
            </h2>
            <p class="text-xs text-white/90 truncate">
                {{ $film?->age_rating ?? 'SU' }} · {{ $film?->duration_minutes ?? 105 }} menit · {{ $film?->genre ?? 'Drama' }}
            </p>
        </div>
    </div>

    {{-- 3. RINCIAN JADWAL & STUDIO (Grid 2 Kolom, Informasi Tampil 1 Kali) --}}
    <div class="p-4 border-b border-slate-200 bg-white grid grid-cols-2 gap-y-3.5 gap-x-4 text-left">
        <div>
            <span class="text-[12px] font-medium text-[#475569] block">Tanggal</span>
            <span class="text-[16px] font-bold text-[#0F172A] block tabular-nums">
                {{ format_tanggal_id($session?->session_date) }}
            </span>
            <span class="sr-only">Tanggal & Jam</span>
        </div>
        <div>
            <span class="text-[12px] font-medium text-[#475569] block">Jam</span>
            <span class="text-[16px] font-bold text-[#0F172A] block tabular-nums">
                {{ format_rentang_jam_id($session?->start_time, $session?->end_time) }}
            </span>
        </div>
        <div>
            <span class="text-[12px] font-medium text-[#475569] block">Studio</span>
            <span class="text-[16px] font-bold text-[#0F172A] block">
                {{ $studioName }}
            </span>
            <span class="sr-only">Studio & Layar</span>
        </div>
        <div>
            <span class="text-[12px] font-medium text-[#475569] block">Paket</span>
            <span class="text-[16px] font-bold text-[#0F172A] block">
                {{ $booking->isPrivate() ? ('Sewa Private · ' . ($booking->seats_reserved ?: 6) . ' orang') : ($booking->isCouple() ? 'Paket Couple · 2 orang' : 'Reguler · ' . ($booking->people_count ?? $booking->pax) . ' orang') }}
            </span>
            <span class="sr-only">Paket</span>
        </div>
        <div>
            <span class="text-[12px] font-medium text-[#475569] block">Jumlah</span>
            <span class="text-[16px] font-bold text-[#0F172A] block tabular-nums">
                {{ $booking->people_count ?? $booking->seats_reserved ?? $booking->pax }} orang
            </span>
        </div>
        <div>
            <span class="text-[12px] font-medium text-[#475569] block">Pemesan</span>
            <span class="text-[16px] font-bold text-[#0F172A] block truncate" title="{{ $booking->customer_name }}">
                {{ $booking->customer_name }}
            </span>
            <span class="sr-only">Nama Pemesan</span>
        </div>
    </div>

    {{-- 4. GARIS SOBEKAN DENGAN TAKIK TRANSPARAN (Radial Gradient Mask) --}}
    <div class="tear-notch relative h-6 w-full flex items-center justify-center bg-white"
         style="background: radial-gradient(circle at 0% 50%, transparent 10px, #ffffff 10.5px), radial-gradient(circle at 100% 50%, transparent 10px, #ffffff 10.5px); background-size: 51% 100%; background-position: left, right; background-repeat: no-repeat;">
        <div class="w-full border-t-2 border-dashed border-slate-300 mx-3"></div>
    </div>

    {{-- 5. BAGIAN SOBEKAN (QR / KODE / STATUS KHUSUS) --}}
    <div class="p-4 pt-1 bg-white text-center space-y-3">
        @if ($isCancelled)
            {{-- Status: Dibatalkan --}}
            <div class="p-3.5 bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs space-y-1 text-left">
                <p class="font-bold text-slate-900">Tiket Dibatalkan</p>
                <p class="leading-relaxed">{{ $booking->cancellation_reason ?: 'Booking ini telah dibatalkan oleh sistem atau admin.' }}</p>
                <p class="text-[11px] text-slate-600">QR Code tidak lagi aktif untuk masuk studio.</p>
            </div>
            <div class="space-y-0.5 pt-1">
                <p class="text-xs font-semibold text-[#475569]">Kode booking</p>
                <p class="font-mono text-xl font-bold tracking-widest text-[#0F172A]">{{ $booking->code }}</p>
            </div>
        @elseif ($isExpired)
            {{-- Status: Kedaluwarsa --}}
            <div class="p-3.5 bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs space-y-1 text-left">
                <p class="font-bold text-slate-900">Tiket Kedaluwarsa</p>
                <p class="leading-relaxed">Batas waktu pembayaran telah berakhir dan tempat pesanan telah dilepas.</p>
                <p class="text-[11px] text-slate-600">Silakan lakukan pemesanan sesi baru.</p>
            </div>
            <div class="space-y-0.5 pt-1">
                <p class="text-xs font-semibold text-[#475569]">Kode booking</p>
                <p class="font-mono text-xl font-bold tracking-widest text-[#0F172A]">{{ $booking->code }}</p>
            </div>
        @elseif ($isDiproses)
            {{-- Status: Menunggu Konfirmasi Admin --}}
            <div class="p-3.5 bg-amber-50 border border-amber-200 text-amber-900 rounded-lg text-xs space-y-1 text-left">
                <p class="font-bold">Bukti Pembayaran Diterima</p>
                <p class="leading-relaxed">Admin sedang memverifikasi bukti transfer. Tiket aktif akan muncul setelah dikonfirmasi.</p>
            </div>
            <div class="space-y-0.5 pt-1">
                <p class="text-xs font-semibold text-[#475569]">Kode booking</p>
                <p class="font-mono text-xl font-bold tracking-widest text-[#0F172A]">{{ $booking->code }}</p>
            </div>
        @elseif ($isWaitingPenonton)
            {{-- Status: Menunggu Penonton --}}
            <div class="p-3.5 bg-amber-50 border border-amber-200 text-amber-900 rounded-lg text-xs space-y-1">
                <p class="font-bold">Tiket aktif setelah sesi dipastikan tayang.</p>
                <p class="text-[11px] text-amber-800">QR Code masuk studio akan otomatis muncul setelah sesi pasti tayang.</p>
            </div>
            <div class="space-y-0.5 pt-1">
                <p class="text-xs font-semibold text-[#475569]">Kode booking</p>
                <p class="font-mono text-xl font-bold tracking-widest text-[#0F172A]">{{ $booking->code }}</p>
            </div>
        @elseif ($isCompleted)
            {{-- Status: Selesai / Sudah Dipakai --}}
            <div class="p-3.5 bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs">
                <p class="font-bold text-sm text-slate-900">Tiket sudah digunakan</p>
            </div>
            <div class="space-y-0.5 pt-1">
                <p class="text-xs font-semibold text-[#475569]">Kode booking</p>
                <p class="font-mono text-xl font-bold tracking-widest text-[#0F172A]">{{ $booking->code }}</p>
            </div>
        @else
            {{-- Status: Aktif (QR inline SVG 150px / 36mm cetak + Monospace 20px) --}}
            <div class="flex flex-col items-center justify-center space-y-2">
                <div id="ticket-qr" data-ticket-qr class="size-[150px] border border-slate-200 rounded p-1 bg-white flex items-center justify-center print:size-[36mm] [&>svg]:w-full [&>svg]:h-full [&>svg]:block">
                    {!! generate_inline_qr_svg($booking->code, 150, 1) !!}
                </div>
                
                <div class="space-y-0.5">
                    <p class="font-mono text-[20px] font-bold tracking-widest text-[#0F172A] select-all">
                        {{ $booking->code }}
                    </p>
                    <p class="text-xs text-[#475569]">
                        Tunjukkan QR atau kode ini ke petugas
                    </p>
                </div>

                @if ($mode === 'customer')
                    <div x-data="{ copied: false }" class="pt-1 no-print">
                        <button type="button"
                                @click="if (window.copyToClipboard) { window.copyToClipboard('{{ $booking->code }}'); } else if (navigator.clipboard) { navigator.clipboard.writeText('{{ $booking->code }}'); } copied = true; setTimeout(() => copied = false, 2000)"
                                class="inline-flex min-h-[36px] items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition">
                            <svg class="size-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span x-text="copied ? 'Kode tersalin' : 'Salin kode'">Salin kode</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- 6. PEMBAYARAN (Tiket, Jajan, Total, Metode, Status) --}}
    <div class="p-4 border-t border-slate-200 bg-white space-y-2 text-xs">
        {{-- Baris Tiket --}}
        <div class="flex justify-between text-slate-600">
            <span>
                Tiket ({{ $booking->isPrivate() ? ('Sewa Private · ' . ($booking->seats_reserved ?: 6) . ' orang') : ($booking->isCouple() ? 'Paket Couple · 2 orang' : 'Reguler · ' . ($booking->people_count ?? $booking->pax) . ' orang') }})
            </span>
            <span class="font-semibold text-slate-900 tabular-nums">
                Rp {{ number_format($booking->ticket_total, 0, ',', '.') }}
            </span>
        </div>

        {{-- Baris Jajan (Maksimal 3 item lalu +n item) --}}
        @if ($displayItems->isNotEmpty())
            @foreach ($displayItems->take(3) as $item)
                <div class="flex justify-between text-slate-600">
                    <span class="truncate pr-2">Jajan ({{ $item->name_snapshot }} × {{ $item->qty }}{{ $item->note ? ' · ' . $item->note : '' }})</span>
                    <span class="font-semibold text-slate-900 shrink-0 tabular-nums">
                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                    </span>
                </div>
            @endforeach

            @if ($displayItems->count() > 3)
                <div class="text-[11px] text-slate-500">
                    +{{ $displayItems->count() - 3 }} item jajan lainnya
                </div>
            @endif
        @elseif ($booking->snack_total > 0)
            <div class="flex justify-between text-slate-600">
                <span>Pesanan Jajan</span>
                <span class="font-semibold text-slate-900 shrink-0 tabular-nums">
                    Rp {{ number_format($booking->snack_total, 0, ',', '.') }}
                </span>
            </div>
        @endif

        {{-- Total Pembayaran --}}
        <div class="flex justify-between pt-2 border-t border-slate-200 font-bold text-sm text-[#0F172A]">
            <span>Total Pembayaran</span>
            <span class="tabular-nums">
                Rp {{ number_format($booking->total_price, 0, ',', '.') }}
            </span>
        </div>

        {{-- Metode & Status Pembayaran Terpisah Transparan --}}
        @php
            $statusBayarLabel = $booking->paymentStatusLabel();
            $statusBayarColor = $booking->paymentStatusColor();
        @endphp
        <div class="pt-2 border-t border-slate-100 space-y-1 text-[11px] text-slate-600">
            <div class="flex justify-between items-center">
                <span>Metode Pembayaran</span>
                <span class="font-semibold text-slate-900">
                    {{ ['di_tempat' => 'Bayar di tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'][$booking->payment_method] ?? ucfirst($booking->payment_method) }}
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span>Status Pembayaran</span>
                <span class="font-bold {{ $statusBayarColor }}">
                    {{ $statusBayarLabel }}
                </span>
            </div>
        </div>
    </div>

    {{-- 7. CATATAN & FOOTER KARTU (Maksimal 3 Kalimat Pendek + Lokasi & Kontak) --}}
    <div class="p-4 border-t border-slate-200 bg-slate-50 text-[11px] text-slate-600 leading-relaxed space-y-1.5">
        <p>• Datang {{ $arriveMinutes }} menit sebelum jadwal penayangan dimulai.</p>
        <p>• Tiket hanya berlaku untuk judul film dan sesi penayangan ini.</p>
        <p>• Perubahan atau pembatalan tiket maksimal 6 jam sebelum sesi.</p>
        @if ($address)
            <p class="pt-1 text-slate-500">• Lokasi: {{ $address }}</p>
        @endif
        @if ($whatsapp)
            <p class="text-slate-500">• Bantuan WhatsApp: {{ format_phone_id($whatsapp) }}</p>
        @endif
        @if ($email)
            <p class="text-slate-500">• Email: {{ $email }}</p>
        @endif
    </div>

</div>
