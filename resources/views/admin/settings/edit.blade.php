@extends('layouts.admin')

@section('title', 'Pengaturan Bioskop')

@section('content')
    <div x-data="{ activeTab: '{{ $currentTab ?? 'identitas' }}' }">
        <x-admin.page-header
            eyebrow="Konfigurasi Sistem"
            title="Pengaturan Bioskop"
            subtitle="Pengaturan Home Cinema: kelola identitas, logo, navbar, operasional, tarif tiket, FAQ, dan SEO tanpa mengubah kode."
        />

        {{-- Tab Navigation Bar --}}
        <div class="mt-6 border-b border-border">
            <nav class="flex space-x-2 overflow-x-auto pb-px scrollbar-none" aria-label="Tabs Pengaturan">
                @php
                    $tabs = [
                        'identitas' => '1. Identitas Situs',
                        'navbar' => '2. Navbar & Menu',
                        'tampilan' => '3. Tampilan',
                        'operasional' => '4. Operasional',
                        'harga' => '5. Harga & FAQ',
                        'kontak' => '6. Kontak & Sosial',
                        'seo' => '7. SEO & Lanjutan',
                    ];
                @endphp
                @foreach ($tabs as $tabKey => $tabLabel)
                    <button
                        type="button"
                        @click="activeTab = '{{ $tabKey }}'; const url = new URL(window.location); url.searchParams.set('tab', '{{ $tabKey }}'); window.history.replaceState({}, '', url);"
                        :class="activeTab === '{{ $tabKey }}'
                            ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-bold bg-amber-500/10'
                            : 'border-transparent text-text-muted hover:text-text hover:border-slate-300 dark:hover:border-white/20'"
                        class="whitespace-nowrap border-b-2 px-4 py-3 text-xs sm:text-sm font-semibold transition-colors rounded-t-xl"
                    >
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 1: IDENTITAS SITUS --}}
        {{-- ============================================================== --}}
        <div x-show="activeTab === 'identitas'" x-cloak class="mt-6 space-y-6">
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_tab" value="identitas">

                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5">
                    <h2 class="text-base font-bold text-text">Identitas & Brand Navbar</h2>
                    <p class="text-xs text-text-muted">Nama dan logo yang muncul di navbar dan tab browser pengunjung.</p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="site_name" class="block text-xs font-bold uppercase tracking-wider text-text">Nama Bioskop</label>
                            <input id="site_name" name="site_name" type="text" required value="{{ old('site_name', $settings->siteName()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <label for="site_tagline" class="block text-xs font-bold uppercase tracking-wider text-text">Tagline / Slogan</label>
                            <input id="site_tagline" name="site_tagline" type="text" value="{{ old('site_tagline', $settings->siteTagline()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                    </div>

                    <div class="w-full sm:w-80">
                        <x-ui.select
                            name="navbar_brand_display"
                            id="navbar_brand_display"
                            label="Tampilan Brand di Navbar"
                            :value="old('navbar_brand_display', $settings->navbarBrandDisplay())"
                            :options="[
                                'logo_text' => 'Logo + Teks',
                                'logo_only' => 'Logo Saja',
                                'text_only' => 'Teks Saja',
                            ]"
                        />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2 pt-2 border-t border-border">
                        <x-admin.image-upload
                            name="site_logo"
                            label="Logo Navbar (Mode Terang)"
                            hint="Standar tinggi 80 px, lebar maks 400 px, PNG/SVG/WebP transparan, maks 1 MB."
                            :currentValue="$settings->siteLogo()"
                            :libraryImages="$libraryImages"
                            maxSizeMb="1"
                        />

                        <x-admin.image-upload
                            name="site_logo_dark"
                            label="Logo Navbar (Mode Gelap, Opsional)"
                            hint="Jika kosong, sistem akan menggunakan logo utama dengan teks putih."
                            :currentValue="$settings->siteLogoDark()"
                            :libraryImages="$libraryImages"
                            maxSizeMb="1"
                        />
                    </div>

                    {{-- Pratinjau Logo Berdampingan (Terang vs Gelap) --}}
                    <div class="mt-4 rounded-xl border border-border bg-surface-sunken p-4">
                        <p class="text-xs font-bold text-text-muted mb-2">Pratinjau Logo Berdampingan (Terang vs Gelap)</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col items-center justify-center p-4 rounded-lg bg-white border border-slate-200 min-h-[90px]">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Pratinjau Tema Terang (Latar Putih)</span>
                                @if ($settings->siteLogo())
                                    <img src="{{ Storage::url($settings->siteLogo()) }}" alt="Logo Terang" class="h-9 w-auto max-w-[200px] object-contain">
                                @else
                                    <span class="text-xs text-slate-400 italic">Belum diunggah</span>
                                @endif
                            </div>
                            <div class="flex flex-col items-center justify-center p-4 rounded-lg bg-[#07090E] border border-amber-500/20 min-h-[90px]">
                                <span class="text-[10px] font-bold text-amber-400/80 uppercase tracking-wider mb-2">Pratinjau Tema Gelap (Latar Obsidian)</span>
                                @if ($settings->siteLogoDark())
                                    <img src="{{ Storage::url($settings->siteLogoDark()) }}" alt="Logo Gelap" class="h-9 w-auto max-w-[200px] object-contain">
                                @elseif ($settings->siteLogo())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-white/95 shadow-sm border border-white/20">
                                        <img src="{{ Storage::url($settings->siteLogo()) }}" alt="Logo Terang (Pill)" class="h-8 w-auto max-w-[190px] object-contain">
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500 italic">Belum diunggah</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2 pt-2 border-t border-border">
                        <x-admin.image-upload
                            name="site_favicon"
                            label="Favicon Browser (Persegi 512×512 px)"
                            hint="Satu gambar persegi 512×512 px (PNG/SVG/ICO, maks 512 KB)."
                            :currentValue="$settings->siteFavicon()"
                            :libraryImages="$libraryImages"
                            maxSizeMb="1"
                        />

                        <x-admin.image-upload
                            name="site_og_image"
                            label="Gambar Share Media Sosial (OG Image 1200×630 px)"
                            hint="Preview link saat website dibagikan di WhatsApp/Facebook (1200×630 px, maks 2 MB)."
                            :currentValue="$settings->siteOgImage()"
                            :recommendedWidth="1200"
                            :recommendedHeight="630"
                            :libraryImages="$libraryImages"
                            maxSizeMb="2"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                        Simpan Perubahan Identitas
                    </button>
                </div>
            </form>
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 2: NAVBAR DAN MENU --}}
        {{-- ============================================================== --}}
        <div x-show="activeTab === 'navbar'" x-cloak class="mt-6 space-y-6">
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="_tab" value="navbar">

                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-text">Daftar Menu Navigasi Publik</h2>
                            <p class="text-xs text-text-muted">Atur urutan, ubah label teks, dan sembunyikan menu tertentu di navbar publik.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th class="w-16 text-center">Urutan</th>
                                    <th>Menu / Halaman</th>
                                    <th>Label di Navbar</th>
                                    <th>Tautan Tujuan</th>
                                    <th class="w-24 text-center">Tampilkan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($menuItems = $settings->navbarMenu())
                                @foreach ($menuItems as $idx => $item)
                                    <tr>
                                        <td class="text-center font-bold">
                                            <input type="hidden" name="menu[{{ $idx }}][key]" value="{{ $item['key'] ?? $item['id'] ?? ('item_'.$idx) }}">
                                            <input type="number" name="menu[{{ $idx }}][order]" value="{{ $item['order'] ?? ($idx + 1) }}" min="1" max="20" class="w-14 text-center rounded-lg border py-1 text-xs">
                                        </td>
                                        <td class="font-bold text-text">
                                            {{ ucfirst($item['key'] ?? $item['id'] ?? 'Menu') }}
                                            @if (($item['key'] ?? $item['id'] ?? '') === 'lookup' || ($item['key'] ?? $item['id'] ?? '') === 'booking_lookup')
                                                <span class="ml-1 text-[10px] text-amber-600 font-normal">(Sangat disarankan tetap aktif)</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="text" name="menu[{{ $idx }}][label]" value="{{ $item['label'] }}" required class="w-full max-w-xs rounded-lg border py-1.5 px-2.5 text-xs">
                                        </td>
                                        <td class="text-xs text-text-muted font-mono">
                                            <input type="hidden" name="menu[{{ $idx }}][url]" value="{{ $item['url'] }}">
                                            <input type="hidden" name="menu[{{ $idx }}][route]" value="{{ $item['route'] ?? '' }}">
                                            {{ $item['url'] }}
                                        </td>
                                        <td class="text-center">
                                            <x-form.switch
                                                name="menu[{{ $idx }}][is_visible]"
                                                value="1"
                                                :checked="!empty($item['is_visible'])"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                        Simpan Perubahan Navbar
                    </button>
                </div>
            </form>
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 3: TAMPILAN --}}
        {{-- ============================================================== --}}
        <div x-show="activeTab === 'tampilan'" x-cloak class="mt-6 space-y-6">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_tab" value="tampilan">

                {{-- Tema & Auto-slide Hero --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5">
                    <h2 class="text-base font-bold text-text">Preferensi Tema & Carousel Beranda</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-ui.select
                                name="default_theme"
                                id="default_theme"
                                label="Tema Default Pengunjung"
                                :value="old('default_theme', $settings->defaultTheme())"
                                :options="[
                                    'system' => 'Ikuti Perangkat (Otomatis)',
                                    'light' => 'Mode Terang',
                                    'dark' => 'Mode Gelap (Bioskop)',
                                ]"
                            />
                        </div>
                        <div class="flex items-center pt-5">
                            <x-form.switch
                                name="show_theme_toggle"
                                label="Tampilkan Tombol Ganti Tema di Navbar"
                                :checked="old('show_theme_toggle', $settings->showThemeToggle())"
                            />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3 pt-3 border-t border-border">
                        <div>
                            <x-ui.select
                                name="home_films_count"
                                id="home_films_count"
                                label="Jumlah Film di Beranda"
                                :value="old('home_films_count', $settings->homeFilmsCount())"
                                :options="[
                                    '6' => '6 Film',
                                    '8' => '8 Film',
                                    '10' => '10 Film',
                                    '12' => '12 Film',
                                ]"
                            />
                        </div>
                        <div>
                            <x-ui.select
                                name="home_hero_interval"
                                id="home_hero_interval"
                                label="Interval Auto-Slide Hero"
                                :value="old('home_hero_interval', $settings->homeHeroInterval())"
                                :options="[
                                    '4' => '4 Detik',
                                    '5' => '5 Detik',
                                    '6' => '6 Detik',
                                    '8' => '8 Detik',
                                ]"
                            />
                        </div>
                        <div class="flex items-center pt-5">
                            <x-form.switch
                                name="home_hero_autoplay"
                                label="Autoplay Geser Otomatis"
                                :checked="old('home_hero_autoplay', $settings->homeHeroAutoplay())"
                            />
                        </div>
                    </div>
                </div>

                {{-- Chip Info Beranda --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
                    <h2 class="text-base font-bold text-text">Chip Informasi Beranda (Bawah Hero)</h2>
                    <p class="text-xs text-text-muted">Tiga chip informasi sorotan yang tampil langsung di bawah hero slide beranda.</p>

                    @php($chips = $settings->homeInfoChips())
                    <div class="space-y-3">
                        @foreach ($chips as $idx => $chip)
                            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-border p-3 bg-surface-2">
                                <div class="w-44">
                                    <x-ui.select
                                        name="chips[{{ $idx }}][icon]"
                                        label="Ikon"
                                        :value="$chip['icon'] ?? 'chair'"
                                        :options="[
                                            'chair' => 'Kursi / Sofa',
                                            'tag' => 'Harga / Tag',
                                            'sparkles' => 'Fasilitas / Bintang',
                                            'clock' => 'Jam / Durasi',
                                        ]"
                                    />
                                </div>
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-[10px] font-bold text-text-muted uppercase">Teks Informasi</label>
                                    <input type="text" name="chips[{{ $idx }}][text]" value="{{ $chip['text'] }}" required class="mt-1 w-full rounded-lg border py-1.5 px-2 text-xs">
                                </div>
                                <div class="pt-4 flex items-center gap-3">
                                    <x-form.switch
                                        name="chips[{{ $idx }}][is_visible]"
                                        value="1"
                                        label="Tampil"
                                        :checked="!empty($chip['is_visible'])"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                        Simpan Perubahan Tampilan
                    </button>
                </div>
            </form>
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 4: OPERASIONAL --}}
        {{-- ============================================================== --}}
        <div x-show="activeTab === 'operasional'" x-cloak class="mt-6 space-y-6">
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_tab" value="operasional">

                {{-- Mode Pemeliharaan --}}
                <div class="rounded-2xl border border-warning/40 bg-warning/10 p-5 shadow-token space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-warning">Mode Pemeliharaan (Under Maintenance)</h3>
                            <p class="text-xs text-text-muted">Jika aktif, pengunjung publik melihat halaman "Sedang dirapikan", namun admin tetap bisa login.</p>
                        </div>
                        <x-form.switch
                            name="maintenance_mode"
                            value="1"
                            :checked="old('maintenance_mode', $settings->maintenanceMode())"
                        />
                    </div>
                    <div>
                        <label for="maintenance_message" class="block text-xs font-semibold text-text">Pesan Pemeliharaan untuk Pengunjung</label>
                        <input id="maintenance_message" name="maintenance_message" type="text" value="{{ old('maintenance_message', $settings->maintenanceMessage()) }}" class="mt-1 min-h-[40px] w-full rounded-xl border px-3 text-xs">
                    </div>
                </div>

                {{-- Aturan Booking --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
                    <h2 class="text-base font-bold text-text">Kapasitas & Batas Waktu Pemesanan</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="capacity_per_session" class="block text-xs font-bold uppercase tracking-wider text-text">Kapasitas Kursi per Sesi</label>
                            <input id="capacity_per_session" name="capacity_per_session" type="number" min="1" max="20" required value="{{ old('capacity_per_session', $settings->capacityPerSession()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <label for="max_seats_per_booking" class="block text-xs font-bold uppercase tracking-wider text-text">Maks Kursi per Booking</label>
                            <input id="max_seats_per_booking" name="max_seats_per_booking" type="number" min="1" max="20" required value="{{ old('max_seats_per_booking', $settings->maxSeatsPerBooking()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <label for="minimum_public_viewers" class="block text-xs font-bold uppercase tracking-wider text-text">Minimal Penonton per Sesi</label>
                            <input id="minimum_public_viewers" name="minimum_public_viewers" type="number" min="1" max="6" required value="{{ old('minimum_public_viewers', $settings->minimumPublicViewers()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <x-ui.select
                                name="low_attendance_warning_window"
                                id="low_attendance_warning_window"
                                label="Jendela Peringatan Sesi Kurang Penonton"
                                :value="old('low_attendance_warning_window', $settings->lowAttendanceWarningWindowHours())"
                                :options="[
                                    '24' => '24 Jam',
                                    '48' => '48 Jam',
                                    '72' => '72 Jam (3 Hari, Default)',
                                    '168' => '7 Hari (1 Minggu)',
                                ]"
                            />
                        </div>
                        <div>
                            <x-ui.select
                                name="booking_cutoff_minutes"
                                id="booking_cutoff_minutes"
                                label="Tutup Pesan Sebelum Mulai"
                                :value="old('booking_cutoff_minutes', $settings->bookingCutoffMinutes())"
                                :options="[
                                    '15' => '15 Menit',
                                    '30' => '30 Menit',
                                    '60' => '1 Jam (60 mnt)',
                                    '120' => '2 Jam (120 mnt)',
                                    '720' => '12 Jam',
                                ]"
                            />
                        </div>
                        <div>
                            <label for="cancellation_cutoff_hours" class="block text-xs font-bold uppercase tracking-wider text-text">Batas Batal Mandiri (Jam)</label>
                            <input id="cancellation_cutoff_hours" name="cancellation_cutoff_hours" type="number" min="0" max="168" required value="{{ old('cancellation_cutoff_hours', $settings->cancellationCutoffHours()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Metode Pembayaran --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5">
                    <div>
                        <h2 class="text-base font-bold text-text">Metode Pembayaran Aktif</h2>
                        <p class="text-xs text-text-muted mt-0.5">Pilih metode yang diizinkan untuk pelanggan saat booking tiket.</p>
                    </div>

                    @if (in_array('qris', $enabledMethods, true) && ! $hasQrisImage)
                        <div role="alert" class="flex items-start gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-amber-500">
                            <svg class="mt-0.5 size-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="flex-1 text-sm text-text">
                                <p class="font-bold text-amber-500">Peringatan: Gambar QRIS belum diunggah atau tidak ditemukan</p>
                                <p class="mt-0.5 text-xs text-text-muted">Metode QRIS saat ini terdaftar aktif, tetapi pelanggan tidak dapat memilihnya sampai gambar QRIS yang valid diunggah di bawah.</p>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        @foreach (['di_tempat' => 'Bayar di tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'] as $method => $label)
                            <label class="choice-chip">
                                <x-form.checkbox
                                    name="payment_methods[]"
                                    value="{{ $method }}"
                                    :checked="in_array($method, old('payment_methods', $enabledMethods), true)"
                                />
                                <span class="text-sm font-semibold text-text">{{ $label }}</span>
                                @if ($method === 'qris' && ! $hasQrisImage)
                                    <span class="text-xs font-normal text-warning">(Wajib upload gambar)</span>
                                @elseif ($method === 'transfer' && ! $hasTransferAccounts)
                                    <span class="text-xs font-normal text-warning">(Wajib isi rekening)</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    {{-- QRIS Details --}}
                    <div class="pt-4 border-t border-border space-y-4">
                        <h3 class="text-sm font-bold text-text">Pengaturan QRIS</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="qris_merchant_name" class="block text-xs font-bold uppercase tracking-wider text-text">Nama Merchant QRIS</label>
                                <input id="qris_merchant_name" name="qris_merchant_name" type="text" value="{{ old('qris_merchant_name', $settings->qrisMerchantName()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                            </div>
                            <div>
                                <label for="qris_image" class="block text-xs font-bold uppercase tracking-wider text-text">File Gambar QRIS (PNG/JPG, maks 2 MB)</label>
                                <input id="qris_image" name="qris_image" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-amber-500 file:text-slate-950 file:font-bold">
                                @if ($qrisRatioWarning)
                                    <div class="mt-2 flex items-start gap-3 rounded-xl border border-amber-500/40 bg-amber-500/10 p-3 text-xs text-amber-500">
                                        <svg class="mt-0.5 size-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <div>
                                            <p class="font-bold">Perhatian Rasio Gambar QRIS</p>
                                            <p class="mt-0.5 text-text">QRIS sebaiknya persegi dan rapi; pangkas bagian lain.</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Transfer Details --}}
                    <div class="pt-4 border-t border-border space-y-3">
                        <h3 class="text-sm font-bold text-text">Rekening Bank / E-Wallet untuk Transfer</h3>
                        @php($accounts = $settings->transferAccounts())
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="block text-[11px] font-bold text-text-muted uppercase">Nama Bank / E-Wallet</label>
                                <input type="text" name="transfer_accounts[0][bank_name]" value="{{ $accounts[0]['bank_name'] ?? 'BCA' }}" class="mt-1 min-h-[40px] w-full rounded-xl border px-3 text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-text-muted uppercase">Nomor Rekening</label>
                                <input type="text" name="transfer_accounts[0][account_number]" value="{{ $accounts[0]['account_number'] ?? '1234567890' }}" class="mt-1 min-h-[40px] w-full rounded-xl border px-3 text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-text-muted uppercase">Atas Nama (Pemilik)</label>
                                <input type="text" name="transfer_accounts[0][account_holder]" value="{{ $accounts[0]['account_holder'] ?? 'SynemaX Cinema' }}" class="mt-1 min-h-[40px] w-full rounded-xl border px-3 text-xs">
                            </div>
                        </div>
                    </div>

                    {{-- Admin WhatsApp & Operasional Lainnya --}}
                    <div class="pt-4 border-t border-border grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="admin_whatsapp" class="block text-xs font-bold uppercase tracking-wider text-text">WhatsApp Admin Pemesanan</label>
                            <input id="admin_whatsapp" name="admin_whatsapp" type="text" required value="{{ old('admin_whatsapp', $settings->string('admin_whatsapp', '081234567890')) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <label for="electricity_cost_per_session" class="block text-xs font-bold uppercase tracking-wider text-text">Biaya Listrik per Sesi (Rp)</label>
                            <input id="electricity_cost_per_session" name="electricity_cost_per_session" type="number" step="500" required value="{{ old('electricity_cost_per_session', $settings->integer('electricity_cost_per_session', 0)) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-border flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-text">Modul Jajanan & Makanan Ringan</p>
                            <p class="text-xs text-text-muted">Izinkan penonton memesan menu jajan saat checkout tiket.</p>
                        </div>
                        <x-form.switch
                            name="snacks_enabled"
                            value="1"
                            :checked="old('snacks_enabled', $settings->integer('snacks_enabled', 1) === 1)"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                        Simpan Perubahan Operasional
                    </button>
                </div>
            </form>
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 5: HARGA DAN FAQ --}}
        {{-- ============================================================== --}}
        <div x-show="activeTab === 'harga'" x-cloak class="mt-6 space-y-6">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_tab" value="harga">

                {{-- Tarif Tiket --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
                    <h2 class="text-base font-bold text-text">Tarif Tiket Bioskop</h2>
                    <p class="text-xs text-text-muted">Harga tiket per kursi dan paket sewa studio privat.</p>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="weekday_price" class="block text-xs font-bold uppercase tracking-wider text-text">Hari Biasa / Jumat (Rp)</label>
                            <input id="weekday_price" name="weekday_price" type="number" min="500" step="500" required value="{{ old('weekday_price', $settings->weekdayPrice()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <label for="weekend_price" class="block text-xs font-bold uppercase tracking-wider text-text">Sabtu – Minggu (Rp)</label>
                            <input id="weekend_price" name="weekend_price" type="number" min="500" step="500" required value="{{ old('weekend_price', $settings->weekendPrice()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <label for="holiday_price" class="block text-xs font-bold uppercase tracking-wider text-text">Hari Libur Nasional (Rp)</label>
                            <input id="holiday_price" name="holiday_price" type="number" min="500" step="500" required value="{{ old('holiday_price', $settings->holidayPrice()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <label for="private_price" class="block text-xs font-bold uppercase tracking-wider text-text">Paket Sewa Studio Privat (Rp)</label>
                            <input id="private_price" name="private_price" type="number" min="1000" step="1000" required value="{{ old('private_price', $settings->privatePrice()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Daftar FAQ --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4" x-data="{
                    faqs: {{ Js::from($settings->faqs()) }},
                    addFaq() {
                        this.faqs.push({ q: '', a: '', order: this.faqs.length + 1 });
                    },
                    removeFaq(idx) {
                        this.faqs.splice(idx, 1);
                    }
                }">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-text">Daftar Pertanyaan Umum (FAQ)</h2>
                            <p class="text-xs text-text-muted">FAQ ini dibaca langsung oleh halaman publik Kabar / FAQ.</p>
                        </div>
                        <button type="button" @click="addFaq()" class="inline-flex min-h-[36px] items-center gap-1 rounded-xl bg-amber-500/15 border border-amber-500/30 px-3 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/25">
                            + Tambah FAQ
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(faq, index) in faqs" :key="index">
                            <div class="rounded-xl border border-border bg-surface-2 p-4 space-y-2 relative">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400" x-text="`Pertanyaan #${index + 1}`"></span>
                                    <button type="button" @click="removeFaq(index)" class="text-xs text-danger font-bold hover:underline">Hapus</button>
                                </div>
                                <input type="text" :name="`faqs[${index}][q]`" x-model="faq.q" placeholder="Pertanyaan..." required class="min-h-[40px] w-full rounded-lg border px-3 text-xs font-bold text-text">
                                <textarea :name="`faqs[${index}][a]`" x-model="faq.a" rows="2" placeholder="Jawaban..." required class="w-full rounded-lg border p-2.5 text-xs text-text"></textarea>
                                <input type="hidden" :name="`faqs[${index}][order]`" :value="index + 1">
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                        Simpan Perubahan Harga & FAQ
                    </button>
                </div>
            </form>
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 6: KONTAK DAN SOSIAL --}}
        {{-- ============================================================== --}}
        <div x-show="activeTab === 'kontak'" x-cloak class="mt-6 space-y-6">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_tab" value="kontak">

                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5">
                    <h2 class="text-base font-bold text-text">Informasi Kontak & Media Sosial</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="contact_email" class="block text-xs font-bold uppercase tracking-wider text-text">Alamat Email</label>
                            <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $settings->contactEmail()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <label for="contact_maps_url" class="block text-xs font-bold uppercase tracking-wider text-text">Tautan Google Maps</label>
                            <input id="contact_maps_url" name="contact_maps_url" type="url" value="{{ old('contact_maps_url', $settings->contactMapsUrl()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="contact_address" class="block text-xs font-bold uppercase tracking-wider text-text">Alamat Bioskop Fisik</label>
                        <textarea id="contact_address" name="contact_address" rows="2" class="mt-1.5 w-full rounded-xl border p-3 text-sm">{{ old('contact_address', $settings->contactAddress()) }}</textarea>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3 pt-3 border-t border-border">
                        <div>
                            <label for="social_instagram" class="block text-xs font-bold uppercase tracking-wider text-text">Instagram</label>
                            <input id="social_instagram" name="social_instagram" type="text" placeholder="@synemax.cinema" value="{{ old('social_instagram', $settings->socialInstagram()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <label for="social_tiktok" class="block text-xs font-bold uppercase tracking-wider text-text">TikTok</label>
                            <input id="social_tiktok" name="social_tiktok" type="text" placeholder="@synemax" value="{{ old('social_tiktok', $settings->socialTiktok()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div>
                            <label for="social_youtube" class="block text-xs font-bold uppercase tracking-wider text-text">YouTube</label>
                            <input id="social_youtube" name="social_youtube" type="text" placeholder="SynemaX Official" value="{{ old('social_youtube', $settings->socialYoutube()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-border space-y-4">
                        <div>
                            <label for="footer_text" class="block text-xs font-bold uppercase tracking-wider text-text">Teks Hak Cipta Footer</label>
                            <input id="footer_text" name="footer_text" type="text" value="{{ old('footer_text', $settings->footerText()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                        <div class="flex items-center">
                            <x-form.switch
                                name="show_floating_whatsapp"
                                label="Tampilkan Tombol WhatsApp Melayang di Situs Publik"
                                :checked="old('show_floating_whatsapp', $settings->showFloatingWhatsapp())"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                        Simpan Perubahan Kontak
                    </button>
                </div>
            </form>
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 7: SEO DAN LANJUTAN --}}
        {{-- ============================================================== --}}
        <div x-show="activeTab === 'seo'" x-cloak class="mt-6 space-y-6">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_tab" value="seo">

                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5">
                    <h2 class="text-base font-bold text-text">Optimasi Mesin Pencari (SEO)</h2>

                    <div>
                        <label for="seo_title_template" class="block text-xs font-bold uppercase tracking-wider text-text">Template Judul Halaman (Title Tag)</label>
                        <input id="seo_title_template" name="seo_title_template" type="text" required value="{{ old('seo_title_template', $settings->seoTitleTemplate()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        <p class="mt-1 text-[11px] text-text-muted">Gunakan %title% sebagai penanda judul halaman.</p>
                    </div>

                    <div>
                        <label for="seo_meta_description" class="block text-xs font-bold uppercase tracking-wider text-text">Deskripsi Meta Default</label>
                        <textarea id="seo_meta_description" name="seo_meta_description" rows="3" required class="mt-1.5 w-full rounded-xl border p-3 text-sm">{{ old('seo_meta_description', $settings->seoDefaultDescription()) }}</textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                            Simpan Perubahan SEO
                        </button>
                    </div>
                </div>
            </form>

            {{-- Operasi Lanjutan: Bersihkan Cache & Reset Defaults --}}
            <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5">
                <h2 class="text-base font-bold text-text">Operasi Pemeliharaan & Reset</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-border p-4 bg-surface-2 space-y-2">
                        <h3 class="text-sm font-bold text-text">Bersihkan Cache Sistem</h3>
                        <p class="text-xs text-text-muted leading-relaxed">Gunakan jika perubahan pengaturan, gambar baru, atau tampilan navbar belum langsung terlihat di publik.</p>
                        <form method="POST" action="{{ route('admin.settings.cache.clear') }}">
                            @csrf
                            <button type="submit" class="mt-2 inline-flex min-h-[38px] items-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2">
                                Bersihkan Cache Sekarang
                            </button>
                        </form>
                    </div>

                    <div class="rounded-xl border border-danger/30 p-4 bg-danger-surface space-y-2">
                        <h3 class="text-sm font-bold text-danger">Kembalikan Semua ke Bawaan</h3>
                        <p class="text-xs text-danger-text leading-relaxed">Mengembalikan semua data pengaturan, tarif, FAQ, dan identitas ke nilai awal standar seeder.</p>
                        <form method="POST" action="{{ route('admin.settings.reset') }}" data-confirm="Peringatan: Seluruh data pengaturan akan dikembalikan ke nilai awal standar. Tindakan ini tidak dapat dibatalkan." data-confirm-title="Kembalikan Pengaturan ke Bawaan" data-confirm-action="Kembalikan ke Bawaan" data-confirm-is-danger="true" novalidate>
                            @csrf
                            <button type="submit" class="mt-2 inline-flex min-h-[38px] items-center rounded-xl bg-danger px-4 text-xs font-bold text-white hover:opacity-90">
                                Kembalikan ke Bawaan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
