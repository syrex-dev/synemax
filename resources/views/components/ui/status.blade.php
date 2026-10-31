@props([
    'status' => '',
    'variant' => 'soft', // 'soft' or 'solid'
    'size' => 'default', // 'sm', 'default'
])

@php
    $normalized = strtolower(trim((string) $status));

    // Mapping status ke semantic type & label baku bahasa Indonesia
    [$type, $defaultLabel] = match ($normalized) {
        'terkonfirmasi', 'lunas', 'confirmed', 'paid', 'pasti_tayang' => ['success', 'Terkonfirmasi'],
        'menunggu_konfirmasi', 'menunggu_pembayaran', 'menunggu', 'pending', 'menunggu_kuorum', 'menunggu_penonton' => ['warning', 'Menunggu'],
        'butuh_kesepakatan' => ['warning', 'Butuh kesepakatan'],
        'hadir', 'selesai', 'completed' => ['info', 'Hadir'],
        'tidak_hadir', 'tidak datang', 'no_show', 'no-show' => ['neutral', 'Tidak datang'],
        'dibatalkan', 'kedaluwarsa', 'cancelled', 'batal' => ['danger', 'Dibatalkan'],
        default => ['neutral', ucfirst($status)],
    };

    $label = $slot->isNotEmpty() ? (string) $slot : match ($normalized) {
        'menunggu_konfirmasi' => 'Menunggu konfirmasi',
        'menunggu_pembayaran' => 'Menunggu pembayaran',
        'menunggu_kuorum', 'menunggu_penonton' => 'Menunggu penonton',
        'butuh_kesepakatan' => 'Butuh kesepakatan',
        'terkonfirmasi' => 'Terkonfirmasi',
        'pasti_tayang' => 'Pasti tayang',
        'lunas' => 'Lunas',
        'hadir' => 'Hadir',
        'selesai' => 'Selesai',
        'tidak_hadir', 'no_show', 'no-show' => 'Tidak datang',
        'kedaluwarsa' => 'Kedaluwarsa',
        'dibatalkan' => 'Dibatalkan',
        default => $defaultLabel,
    };

    $badgeClass = match ("{$type}-{$variant}") {
        'success-solid' => 'badge-success-solid',
        'success-soft' => 'badge-success-soft',
        'warning-solid' => 'badge-warning-solid',
        'warning-soft' => 'badge-warning-soft',
        'danger-solid' => 'badge-danger-solid',
        'danger-soft' => 'badge-danger-soft',
        'info-solid' => 'badge-info-solid',
        'info-soft' => 'badge-info-soft',
        'neutral-solid' => 'badge-neutral-solid',
        default => 'badge-neutral-soft',
    };

    $sizeClass = match ($size) {
        'sm' => 'px-2 py-0.5 text-[10px] font-bold rounded-md',
        default => 'px-2.5 py-1 text-xs font-bold rounded-lg',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap {$badgeClass} {$sizeClass}"]) }}>
    <span class="size-1.5 rounded-full shrink-0" style="background-color: currentColor;" aria-hidden="true"></span>
    <span>{{ $label }}</span>
</span>
