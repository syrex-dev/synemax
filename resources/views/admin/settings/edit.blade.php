@extends('layouts.admin')

@section('title', 'Pengaturan Bioskop')

@section('content')
    <div
        x-data="{
            activeTab: '{{ old('_tab', $currentTab ?? 'identitas') }}',
            canScrollLeft: false,
            canScrollRight: false,

            updateScrollState() {
                const nav = this.$refs.tabsNav;
                if (!nav) return;
                this.canScrollLeft = nav.scrollLeft > 4;
                this.canScrollRight = nav.scrollLeft + nav.clientWidth < nav.scrollWidth - 4;
            },

            scrollTabs(offset) {
                const nav = this.$refs.tabsNav;
                if (!nav) return;
                nav.scrollBy({ left: offset, behavior: 'smooth' });
                setTimeout(() => this.updateScrollState(), 320);
            },

            scrollToActive() {
                this.$nextTick(() => {
                    const nav = this.$refs.tabsNav;
                    if (!nav) return;
                    const activeBtn = nav.querySelector(`[data-tab-key='${this.activeTab}']`);
                    if (activeBtn) {
                        activeBtn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                    }
                    this.updateScrollState();
                });
            },

            init() {
                this.scrollToActive();
                window.addEventListener('resize', () => this.updateScrollState());
            }
        }"
    >
        <x-admin.page-header
            eyebrow="Konfigurasi Sistem"
            title="Pengaturan Home Cinema"
            subtitle="Pengaturan bioskop SynemaX: kelola identitas, logo, navbar, operasional, tarif tiket, FAQ, dan SEO tanpa mengubah kode."
        />

        {{-- Notifikasi Status Berhasil --}}
        @if (session('status'))
            <div role="alert" class="mt-4 flex items-center gap-3 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-sm text-emerald-600 dark:text-emerald-400">
                <svg class="size-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-bold">{{ session('status') }}</span>
            </div>
        @endif

        {{-- Notifikasi Peringatan / Catatan --}}
        @if (session('warning'))
            <div role="alert" class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-sm text-amber-600 dark:text-amber-400">
                <svg class="size-5 shrink-0 mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="space-y-0.5">
                    <p class="font-bold">Perhatian</p>
                    <p class="text-xs text-text-muted">{{ session('warning') }}</p>
                </div>
            </div>
        @endif

        {{-- Notifikasi Kesalahan Validasi --}}
        @if ($errors->any())
            <div role="alert" class="mt-4 rounded-2xl border border-red-500/50 bg-red-500/10 p-4 text-sm text-red-600 dark:text-red-400 space-y-1.5">
                <p class="font-bold flex items-center gap-2">
                    <svg class="size-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Pengaturan belum dapat disimpan karena ada isian yang perlu diperiksa:</span>
                </p>
                <ul class="list-disc pl-7 text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Tab Navigation Bar (Scrollable dengan Panah Kontrol, Scroll Mouse & Sentuh) --}}
        <div class="mt-6 relative flex items-center border-b border-border bg-surface-2/30 rounded-2xl p-1 shadow-xs">
            {{-- Tombol Geser Kiri --}}
            <button
                type="button"
                x-show="canScrollLeft"
                x-cloak
                @click="scrollTabs(-250)"
                class="absolute left-1 z-10 flex size-9 items-center justify-center rounded-xl border border-border bg-surface text-text shadow-md hover:bg-surface-2 transition"
                aria-label="Geser tab ke kiri"
                title="Geser tab ke kiri"
            >
                <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </button>

            {{-- Navigasi Tab Horizontal --}}
            <nav
                x-ref="tabsNav"
                @scroll.passive="updateScrollState()"
                @wheel.passive="if ($event.deltaY !== 0 && $el.scrollWidth > $el.clientWidth) { $el.scrollLeft += $event.deltaY; updateScrollState(); }"
                class="flex w-full space-x-1.5 overflow-x-auto scroll-smooth py-1 px-1 touch-pan-x [scrollbar-width:thin]"
                aria-label="Tabs Pengaturan"
            >
                @php
                    $tabs = [
                        'identitas' => '1. Identitas Situs',
                        'navbar' => '2. Navbar & Menu',
                        'tampilan' => '3. Tampilan',
                        'operasional' => '4. Operasional',
                        'harga' => '5. Harga & FAQ',
                        'kontak' => '6. Kontak & Sosial',
                        'seo' => '7. SEO & Lanjutan',
                        'notifikasi' => '8. Notifikasi',
                        'member' => '9. Member',
                    ];
                @endphp
                @foreach ($tabs as $tabKey => $tabLabel)
                    <button
                        type="button"
                        data-tab-key="{{ $tabKey }}"
                        @click="activeTab = '{{ $tabKey }}'; const url = new URL(window.location); url.searchParams.set('tab', '{{ $tabKey }}'); window.history.replaceState({}, '', url); scrollToActive();"
                        :class="activeTab === '{{ $tabKey }}'
                            ? 'border-amber-500 bg-amber-500 text-slate-950 font-bold shadow-xs'
                            : 'border-border bg-surface text-text-muted hover:text-text hover:bg-surface-2'"
                        class="whitespace-nowrap shrink-0 border px-4 py-2.5 text-xs sm:text-sm font-semibold transition-all rounded-xl min-h-[42px] flex items-center justify-center"
                    >
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </nav>

            {{-- Tombol Geser Kanan --}}
            <button
                type="button"
                x-show="canScrollRight"
                x-cloak
                @click="scrollTabs(250)"
                class="absolute right-1 z-10 flex size-9 items-center justify-center rounded-xl border border-border bg-surface text-text shadow-md hover:bg-surface-2 transition"
                aria-label="Geser tab ke kanan"
                title="Geser tab ke kanan"
            >
                <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            </button>
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

                {{-- Tabel Panduan Ukuran & Rasio Gambar Resmi (Sumber Tunggal) --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-border">
                        <div>
                            <h2 class="text-base font-bold text-text">Panduan Ukuran Gambar Resmi</h2>
                            <p class="text-xs text-text-muted mt-0.5">Standar tunggal dimensi, rasio, dan batas ukuran file yang divalidasi sistem.</p>
                        </div>
                        <span class="badge-accent-soft text-xs font-bold">Sumber Tunggal</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-border text-text-muted">
                                    <th class="py-2.5 font-bold">Komponen</th>
                                    <th class="py-2.5 font-bold">Rasio</th>
                                    <th class="py-2.5 font-bold">Dimensi Rekomendasi</th>
                                    <th class="py-2.5 font-bold">Batas File</th>
                                    <th class="py-2.5 font-bold">Petunjuk Unggah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60">
                                @foreach (\App\Support\ImageDimensionGuide::all() as $guide)
                                    <tr>
                                        <td class="py-2.5 font-bold text-text">{{ $guide['label'] }}</td>
                                        <td class="py-2.5 font-mono text-amber-600 dark:text-amber-400 font-semibold">{{ $guide['ratio'] }}</td>
                                        <td class="py-2.5 font-mono font-bold text-text">{{ $guide['width'] }} × {{ $guide['height'] }} px</td>
                                        <td class="py-2.5 text-text-muted">{{ $guide['max_mb'] }} MB</td>
                                        <td class="py-2.5 text-text-muted">{{ $guide['hint'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
                            <p class="text-xs text-text-muted">Atur urutan, ubah label teks, posisi di HP (maksimal 4 item di Bar Bawah), dan sembunyikan menu tertentu di navbar publik.</p>
                            @error('menu')
                                <p class="mt-2 text-xs font-bold text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="overflow-x-auto admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th class="w-16 text-center">Urutan</th>
                                    <th>Menu / Halaman</th>
                                    <th>Label di Navbar</th>
                                    <th>Label singkat di HP</th>
                                    <th>Tautan Tujuan</th>
                                    <th class="w-36">Tampil di HP</th>
                                    <th class="w-24 text-center">Tampilkan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($menuItems = $settings->navbarMenu())
                                @foreach ($menuItems as $idx => $item)
                                    <tr>
                                        <td class="text-center font-bold">
                                            <input type="hidden" name="menu[{{ $idx }}][key]" value="{{ $item['key'] ?? $item['id'] ?? ('item_'.$idx) }}">
                                            <input type="number" name="menu[{{ $idx }}][order]" value="{{ $item['order'] ?? ($idx + 1) }}" min="1" max="20" class="w-14 text-center rounded-lg border py-1 text-xs bg-surface text-text border-border">
                                        </td>
                                        <td class="font-bold text-text">
                                            {{ ucfirst($item['key'] ?? $item['id'] ?? 'Menu') }}
                                            @if (($item['key'] ?? $item['id'] ?? '') === 'lookup' || ($item['key'] ?? $item['id'] ?? '') === 'booking_lookup')
                                                <span class="ml-1 text-[10px] text-amber-600 font-normal">(Sangat disarankan tetap aktif)</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="text" name="menu[{{ $idx }}][label]" value="{{ $item['label'] }}" required class="w-full max-w-xs rounded-lg border py-1.5 px-2.5 text-xs bg-surface text-text border-border">
                                        </td>
                                        <td>
                                            <input type="text" name="menu[{{ $idx }}][short_label]" value="{{ $item['short_label'] ?? '' }}" placeholder="{{ ($item['key'] ?? '') === 'lookup' ? 'Booking' : $item['label'] }}" class="w-full max-w-[130px] rounded-lg border py-1.5 px-2.5 text-xs bg-surface text-text border-border">
                                        </td>
                                        <td class="text-xs text-text-muted font-mono">
                                            <input type="hidden" name="menu[{{ $idx }}][url]" value="{{ $item['url'] }}">
                                            <input type="hidden" name="menu[{{ $idx }}][route]" value="{{ $item['route'] ?? '' }}">
                                            {{ $item['url'] }}
                                        </td>
                                        <td>
                                            <x-ui.select
                                                name="menu[{{ $idx }}][mobile_position]"
                                                :value="$item['mobile_position'] ?? 'menu'"
                                                :options="[
                                                    'bottom_bar' => 'Bar bawah',
                                                    'menu' => 'Menu'
                                                ]"
                                            />
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

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 pt-3 border-t border-border">
                        <div>
                            <x-ui.select
                                name="films_sort_order"
                                id="films_sort_order"
                                label="Urutan Film (Beranda & Katalog)"
                                :value="old('films_sort_order', $settings->filmsSortOrder())"
                                :options="[
                                    'year_desc' => 'Tahun Rilis (Terbaru ke Terlama)',
                                    'year_asc' => 'Tahun Rilis (Terlama ke Terbaru)',
                                    'title_asc' => 'Judul Film (A ke Z)',
                                    'title_desc' => 'Judul Film (Z ke A)',
                                    'latest_added' => 'Terbaru Ditambahkan ke Sistem',
                                ]"
                            />
                        </div>
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

                {{-- Kurasi Film Beranda (Film Pilihan & Urutan Tampil) --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5"
                     x-data="{
                         mode: @js(old('home_films_mode', $homeFilmsMode)),
                         selectedFilmId: '',
                         allFilms: @js($activeFilms->map(fn($f) => [
                             'id' => (int) $f->id,
                             'title' => $f->title,
                             'release_year' => $f->release_year,
                             'genre' => $f->genre,
                             'poster' => $f->posterUrl(),
                         ])),
                         featuredIds: @js(old('home_featured_film_ids', $featuredFilmIds)),
                         addFilm() {
                             const id = Number(this.selectedFilmId);
                             if (!id) return;
                             if (!this.featuredIds.includes(id)) {
                                 this.featuredIds.push(id);
                             }
                             this.selectedFilmId = '';
                             window.dispatchEvent(new CustomEvent('synemax-select-set', {
                                 detail: { name: 'featured_film_picker', value: '' }
                             }));
                         },
                         removeFilm(id) {
                             this.featuredIds = this.featuredIds.filter(fId => fId !== id);
                         },
                         moveUp(index) {
                             if (index <= 0) return;
                             const temp = this.featuredIds[index];
                             this.featuredIds[index] = this.featuredIds[index - 1];
                             this.featuredIds[index - 1] = temp;
                         },
                         moveDown(index) {
                             if (index >= this.featuredIds.length - 1) return;
                             const temp = this.featuredIds[index];
                             this.featuredIds[index] = this.featuredIds[index + 1];
                             this.featuredIds[index + 1] = temp;
                         },
                         getFilm(id) {
                             return this.allFilms.find(f => f.id === id) || { id: id, title: 'Film #' + id, release_year: '', genre: '', poster: null };
                         },
                         get availableFilms() {
                             return this.allFilms.filter(f => !this.featuredIds.includes(f.id));
                         }
                     }"
                >
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <h2 class="text-base font-bold text-text">Kurasi Film Beranda (Film Pilihan & Urutan Tampil)</h2>
                                <p class="text-xs text-text-muted mt-0.5">Tentukan apakah film di beranda tampil otomatis atau dipilih dan diurutkan secara khusus oleh admin.</p>
                            </div>
                            {{-- Mode Switcher Radio --}}
                            <div class="flex items-center gap-1.5 p-1 rounded-xl bg-surface-2 border border-border shrink-0">
                                <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer transition select-none"
                                       :class="mode === 'auto' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'text-text-muted hover:text-text'">
                                    <input type="radio" name="home_films_mode" value="auto" x-model="mode" class="sr-only">
                                    <span>Otomatis (Semua Film)</span>
                                </label>
                                <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer transition select-none"
                                       :class="mode === 'custom' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'text-text-muted hover:text-text'">
                                    <input type="radio" name="home_films_mode" value="custom" x-model="mode" class="sr-only">
                                    <span>Pilihan Admin & Urutan</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Custom Featured Films Selector & List (Hanya aktif bila mode === 'custom') --}}
                    <div x-show="mode === 'custom'" x-transition class="space-y-4 pt-4 border-t border-border">
                        {{-- Input Penambah Film --}}
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                            <div class="flex-1 min-w-[240px]" @change="selectedFilmId = $event.target.value">
                                <x-ui.select
                                    name="featured_film_picker"
                                    id="featured_film_picker"
                                    placeholder="-- Pilih film untuk ditambahkan ke beranda --"
                                    :options="$activeFilms->mapWithKeys(fn($f) => [(string)$f->id => $f->title . ' (' . ($f->release_year ?: '-') . ')'])->toArray()"
                                    size="sm"
                                />
                            </div>
                            <button
                                type="button"
                                @click="addFilm()"
                                :disabled="!selectedFilmId"
                                class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-xs shrink-0"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                <span>Tambahkan Film</span>
                            </button>
                        </div>

                        {{-- Daftar Film Terpilih dengan Urutan Tampil --}}
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted mb-2">Daftar Urutan Tayang di Beranda (<span x-text="featuredIds.length"></span> Film)</p>

                            <div class="space-y-2">
                                <template x-for="(fId, index) in featuredIds" :key="fId">
                                    <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-border bg-surface-2 transition">
                                        {{-- Hidden input for form submission --}}
                                        <input type="hidden" name="home_featured_film_ids[]" :value="fId">

                                        <div class="flex items-center gap-3 min-w-0">
                                            {{-- Nomor Urutan Badge --}}
                                            <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 border border-amber-500/30 text-amber-600 dark:text-amber-400 font-black text-xs"
                                                  x-text="index + 1">
                                            </span>

                                            {{-- Poster Thumbnail --}}
                                            <div class="size-11 rounded-lg bg-surface border border-border overflow-hidden shrink-0 flex items-center justify-center">
                                                <template x-if="getFilm(fId).poster">
                                                    <img :src="getFilm(fId).poster" :alt="getFilm(fId).title" class="size-full object-cover">
                                                </template>
                                                <template x-if="!getFilm(fId).poster">
                                                    <svg class="size-5 text-text-muted opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                                    </svg>
                                                </template>
                                            </div>

                                            {{-- Info Film --}}
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-text truncate" x-text="getFilm(fId).title"></p>
                                                <p class="text-[11px] text-text-muted truncate">
                                                    <span x-text="getFilm(fId).genre || 'Genre -'"></span>
                                                    <span class="mx-1">•</span>
                                                    <span x-text="getFilm(fId).release_year || '-'"></span>
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Tombol Geser Atas / Bawah & Hapus --}}
                                        <div class="flex items-center gap-1 shrink-0">
                                            {{-- Geser Ke Atas --}}
                                            <button
                                                type="button"
                                                @click="moveUp(index)"
                                                :disabled="index === 0"
                                                class="inline-flex size-8 items-center justify-center rounded-lg border border-border bg-surface text-text hover:border-amber-500 hover:text-amber-500 disabled:opacity-30 disabled:cursor-not-allowed transition"
                                                title="Geser ke urutan sebelumnya"
                                                aria-label="Geser ke atas"
                                            >
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                                </svg>
                                            </button>

                                            {{-- Geser Ke Bawah --}}
                                            <button
                                                type="button"
                                                @click="moveDown(index)"
                                                :disabled="index === featuredIds.length - 1"
                                                class="inline-flex size-8 items-center justify-center rounded-lg border border-border bg-surface text-text hover:border-amber-500 hover:text-amber-500 disabled:opacity-30 disabled:cursor-not-allowed transition"
                                                title="Geser ke urutan setelahnya"
                                                aria-label="Geser ke bawah"
                                            >
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>

                                            {{-- Hapus dari Urutan --}}
                                            <button
                                                type="button"
                                                @click="removeFilm(fId)"
                                                class="inline-flex size-8 items-center justify-center rounded-lg border border-danger/30 bg-danger/10 text-danger hover:bg-danger/20 transition ml-1"
                                                title="Hapus film dari beranda"
                                                aria-label="Hapus film"
                                            >
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                {{-- Empty State --}}
                                <div x-show="featuredIds.length === 0" class="rounded-xl border border-dashed border-border p-6 text-center text-xs text-text-muted">
                                    <p class="font-bold text-text">Belum ada film yang dipilih secara khusus.</p>
                                    <p class="mt-1">Pilih film dari menu di atas lalu klik <strong>Tambahkan Film</strong> untuk menentukan film dan urutan tampilnya di beranda.</p>
                                </div>
                            </div>
                        </div>
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
                                <x-admin.image-upload
                                    name="qris_image"
                                    label="File Gambar QRIS"
                                    hint="JPG/PNG maks 2 MB, sebaiknya persegi"
                                    :currentValue="$hasQrisImage ? route('payment.qris') : null"
                                    :recommendedWidth="500"
                                    :recommendedHeight="500"
                                    maxSizeMb="2"
                                    accept="image/png,image/jpeg,image/webp"
                                    :libraryImages="$libraryImages"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- Transfer Details: Multi Rekening & E-Wallet --}}
                    @php($savedAccounts = old('transfer_accounts', $settings->transferAccounts()) ?: [['bank_name' => 'BCA', 'account_number' => '', 'account_holder' => $settings->string('site_name', 'SynemaX Cinema')]])
                    <div class="pt-4 border-t border-border space-y-4"
                         x-data="{
                            accounts: @js(array_values($savedAccounts)),
                            addAccount() {
                                this.accounts.push({
                                    bank_name: '',
                                    account_number: '',
                                    account_holder: '{{ addslashes($settings->string('site_name', 'SynemaX Cinema')) }}'
                                });
                            },
                            removeAccount(idx) {
                                if (this.accounts.length > 1) {
                                    this.accounts.splice(idx, 1);
                                } else {
                                    this.accounts = [{
                                        bank_name: '',
                                        account_number: '',
                                        account_holder: '{{ addslashes($settings->string('site_name', 'SynemaX Cinema')) }}'
                                    }];
                                }
                            }
                         }">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-text">Daftar Rekening Bank & E-Wallet untuk Transfer</h3>
                                <p class="text-xs text-text-muted mt-0.5">Tambahkan beberapa rekening (BCA, Mandiri, BRI, BNI, Dana, GoPay, OVO, dll.) agar pelanggan memiliki banyak opsi transfer saat checkout.</p>
                            </div>
                            <button
                                type="button"
                                @click="addAccount()"
                                class="inline-flex min-h-[38px] items-center gap-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 px-3.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/25 transition cursor-pointer"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                <span>+ Tambah Bank / E-Wallet</span>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(account, index) in accounts" :key="index">
                                <div class="rounded-xl border border-border bg-surface-2 p-4 space-y-3 relative">
                                    <div class="flex items-center justify-between border-b border-border/60 pb-2">
                                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400" x-text="`Rekening / E-Wallet #${index + 1}`"></span>
                                        <button
                                            type="button"
                                            @click="removeAccount(index)"
                                            class="text-xs font-bold text-danger hover:underline inline-flex items-center gap-1 cursor-pointer"
                                            title="Hapus rekening ini"
                                        >
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Hapus Rekening</span>
                                        </button>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-3">
                                        <div>
                                            <label class="block text-[11px] font-bold text-text uppercase">Nama Bank / E-Wallet <span class="text-danger">*</span></label>
                                            <input
                                                type="text"
                                                :name="`transfer_accounts[${index}][bank_name]`"
                                                x-model="account.bank_name"
                                                placeholder="Contoh: BCA, Mandiri, Dana, GoPay"
                                                class="mt-1 min-h-[42px] w-full rounded-xl border border-border bg-surface px-3 text-xs text-text focus:border-amber-400 focus:outline-none"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-text uppercase">Nomor Rekening / No. HP <span class="text-danger">*</span></label>
                                            <input
                                                type="text"
                                                :name="`transfer_accounts[${index}][account_number]`"
                                                x-model="account.account_number"
                                                placeholder="Contoh: 1234567890 / 08123456789"
                                                class="mt-1 min-h-[42px] w-full rounded-xl border border-border bg-surface px-3 text-xs font-mono text-text focus:border-amber-400 focus:outline-none"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-text uppercase">Atas Nama (Pemilik Rekening)</label>
                                            <input
                                                type="text"
                                                :name="`transfer_accounts[${index}][account_holder]`"
                                                x-model="account.account_holder"
                                                placeholder="Contoh: SynemaX Cinema / Nama Pemilik"
                                                class="mt-1 min-h-[42px] w-full rounded-xl border border-border bg-surface px-3 text-xs text-text focus:border-amber-400 focus:outline-none"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Batas Waktu Pembayaran --}}
                    <div class="pt-4 border-t border-border">
                        <div class="max-w-xs">
                            <x-ui.select
                                name="payment_time_limit_minutes"
                                id="payment_time_limit_minutes"
                                label="Batas Waktu Pembayaran"
                                hint="Berlaku untuk booking baru."
                                :value="old('payment_time_limit_minutes', (string) $settings->paymentTimeLimitMinutes())"
                                :options="[
                                    '10' => '10 Menit',
                                    '15' => '15 Menit',
                                    '20' => '20 Menit',
                                    '30' => '30 Menit (Bawaan)',
                                    '45' => '45 Menit',
                                    '60' => '60 Menit (1 Jam)',
                                ]"
                            />
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

                    <div class="pt-3 border-t border-border flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-text">Fitur Nonton Bareng (Nobar) & Kabar</p>
                            <p class="text-xs text-text-muted">Tampilkan jadwal nobar komunitas dan artikel kabar di navigasi serta halaman publik.</p>
                        </div>
                        <x-form.switch
                            name="info_nobar_enabled"
                            value="1"
                            :checked="old('info_nobar_enabled', $settings->infoNobarEnabled())"
                        />
                    </div>
                </div>

                {{-- Pengaturan Cetak E-Tiket --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4"
                     x-data="{
                        arriveMinutes: {{ Js::from(old('ticket_arrive_minutes', $settings->ticketArriveMinutes())) }},
                        studioName: {{ Js::from(old('ticket_studio_name', $settings->ticketStudioName())) }},
                        ticketNotes: {{ Js::from(old('ticket_notes', $settings->ticketNotes())) }}
                     }">
                    <div>
                        <h2 class="text-base font-bold text-text">Pengaturan Cetak E-Tiket</h2>
                        <p class="text-xs text-text-muted mt-0.5">Atur instruksi dan teks studio yang tercetak pada e-tiket penonton.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="ticket_arrive_minutes" class="block text-xs font-bold uppercase tracking-wider text-text">Hadir Sebelum Mulai (Menit)</label>
                            <input id="ticket_arrive_minutes" name="ticket_arrive_minutes" type="number" min="0" max="120" required x-model.number="arriveMinutes" class="mt-1.5 min-h-[44px] w-full rounded-xl border border-border-input bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none">
                            <p class="mt-1 text-[11px] text-text-muted">Misal 15 menit. Tercetak sebagai batas waktu kedatangan penonton.</p>
                        </div>
                        <div>
                            <label for="ticket_studio_name" class="block text-xs font-bold uppercase tracking-wider text-text">Nama Studio Default</label>
                            <input id="ticket_studio_name" name="ticket_studio_name" type="text" maxlength="50" required x-model="studioName" class="mt-1.5 min-h-[44px] w-full rounded-xl border border-border-input bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none">
                            <p class="mt-1 text-[11px] text-text-muted">Misal Studio 1 atau Studio Utama.</p>
                        </div>
                    </div>

                    <div>
                        <label for="ticket_notes" class="block text-xs font-bold uppercase tracking-wider text-text">Catatan Tambahan di Tiket (Maks 160 Karakter)</label>
                        <textarea id="ticket_notes" name="ticket_notes" maxlength="160" rows="2" x-model="ticketNotes" class="mt-1.5 w-full rounded-xl border border-border-input bg-surface-2 p-3 text-sm text-text focus:border-amber-400 focus:outline-none"></textarea>
                    </div>

                    {{-- Pratinjau Mini Tiket --}}
                    <div class="pt-2 border-t border-border">
                        <span class="block text-xs font-bold uppercase tracking-wider text-text mb-2">Pratinjau Langsung: Format Tiket</span>
                        <div class="max-w-md rounded-xl border border-slate-200 bg-white text-slate-900 p-4 shadow-sm text-xs space-y-2">
                            <div class="flex items-center justify-between pb-2 border-b border-dashed border-slate-200">
                                <span class="font-bold text-slate-800" x-text="studioName || 'Studio 1'"></span>
                                <span class="rounded bg-emerald-100 text-emerald-800 px-2 py-0.5 text-[10px] font-bold">PASTI TAYANG</span>
                            </div>
                            <div class="space-y-1 text-slate-600">
                                <p>Jadwal: <strong class="text-slate-900">19.00 WIB</strong></p>
                                <p>Hadir: <strong class="text-slate-900" x-text="`Paling lambat ${arriveMinutes || 15} menit sebelum film diputar`"></strong></p>
                                <p class="text-[11px] italic text-slate-500 pt-1" x-text="ticketNotes || 'Tunjukkan e-tiket ini kepada petugas di pintu masuk bioskop.'"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pengaturan Check-in & Pemindai Tiket --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
                    <div>
                        <h2 class="text-base font-bold text-text">Pengaturan Check-In & Pemindai Tiket</h2>
                        <p class="text-xs text-text-muted mt-0.5">Konfigurasi jendela waktu validasi tiket dan preferensi pemindai QR untuk petugas pintu masuk.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="check_in_open_minutes_before" class="block text-xs font-bold uppercase tracking-wider text-text mb-1">
                                Check-in dibuka sebelum sesi mulai
                            </label>
                            <x-ui.select
                                id="check_in_open_minutes_before"
                                name="check_in_open_minutes_before"
                                :value="(string) old('check_in_open_minutes_before', $settings->checkInOpenMinutesBefore())"
                                :options="[
                                    '15' => '15 menit sebelum sesi mulai',
                                    '30' => '30 menit sebelum sesi mulai',
                                    '60' => '60 menit sebelum sesi mulai (Bawaan)',
                                    '90' => '90 menit sebelum sesi mulai',
                                    '120' => '120 menit (2 jam) sebelum sesi mulai',
                                ]"
                            />
                            <p class="mt-1 text-[11px] text-text-muted">Tiket yang dipindai sebelum jendela ini akan berstatus "Terlalu awal".</p>
                        </div>

                        <div>
                            <label for="check_in_close_after" class="block text-xs font-bold uppercase tracking-wider text-text mb-1">
                                Check-in ditutup setelah sesi mulai
                            </label>
                            <x-ui.select
                                id="check_in_close_after"
                                name="check_in_close_after"
                                :value="(string) old('check_in_close_after', $settings->checkInCloseOption())"
                                :options="[
                                    '15' => '15 menit setelah sesi mulai',
                                    '30' => '30 menit setelah sesi mulai',
                                    '60' => '60 menit setelah sesi mulai',
                                    'session_end' => 'Sampai sesi selesai (Bawaan)',
                                ]"
                            />
                            <p class="mt-1 text-[11px] text-text-muted">Tiket yang dipindai setelah batas ini membutuhkan konfirmasi override admin.</p>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-border">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                name="check_in_sound_enabled"
                                value="1"
                                {{ old('check_in_sound_enabled', $settings->checkInSoundEnabled()) ? 'checked' : '' }}
                                class="size-4 rounded border-border-input text-amber-500 focus:ring-amber-400"
                            >
                            <div>
                                <span class="text-xs font-bold text-text">Bunyi saat pemindaian</span>
                                <p class="text-[11px] text-text-muted">Putar audio konfirmasi pendek saat tiket berhasil terverifikasi (dapat dimatikan di halaman scan).</p>
                            </div>
                        </label>
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
                    <h2 class="text-base font-bold text-text">Tarif Tiket & Paket Nonton</h2>
                    <p class="text-xs text-text-muted">Harga tiket per orang, paket couple, dan paket sewa studio privat.</p>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                        <div>
                            <label for="weekday_price" class="block text-xs font-bold uppercase tracking-wider text-text">Hari Biasa / Jumat (Rp)</label>
                            <input id="weekday_price" name="weekday_price" type="number" min="500" step="500" required value="{{ old('weekday_price', $settings->weekdayPrice()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border border-border bg-surface px-3 text-sm text-text">
                        </div>
                        <div>
                            <label for="weekend_price" class="block text-xs font-bold uppercase tracking-wider text-text">Sabtu – Minggu (Rp)</label>
                            <input id="weekend_price" name="weekend_price" type="number" min="500" step="500" required value="{{ old('weekend_price', $settings->weekendPrice()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border border-border bg-surface px-3 text-sm text-text">
                        </div>
                        <div>
                            <label for="holiday_price" class="block text-xs font-bold uppercase tracking-wider text-text">Hari Libur Nasional (Rp)</label>
                            <input id="holiday_price" name="holiday_price" type="number" min="500" step="500" required value="{{ old('holiday_price', $settings->holidayPrice()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border border-border bg-surface px-3 text-sm text-text">
                        </div>
                        <div>
                            <label for="couple_price" class="block text-xs font-bold uppercase tracking-wider text-text">Paket Couple 2 Orang (Rp)</label>
                            <input id="couple_price" name="couple_price" type="number" min="1000" step="1000" required value="{{ old('couple_price', $settings->defaultCouplePrice()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border border-border bg-surface px-3 text-sm text-text">
                        </div>
                        <div>
                            <label for="private_price" class="block text-xs font-bold uppercase tracking-wider text-text">Paket Sewa Studio Privat (Rp)</label>
                            <input id="private_price" name="private_price" type="number" min="1000" step="1000" required value="{{ old('private_price', $settings->privatePrice()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border border-border bg-surface px-3 text-sm text-text">
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

                <div class="rounded-2xl border border-border bg-surface p-6 shadow-none space-y-6"
                     x-data="{
                        email: @js(old('contact_email', $settings->contactEmail())),
                        address: @js(old('contact_address', $settings->contactAddress())),
                        instagramUrl: @js(old('instagram_url', $settings->instagramUrl())),
                        instagramLabel: @js(old('instagram_label', $settings->instagramLabel())),
                        tiktokUrl: @js(old('tiktok_url', $settings->tiktokUrl())),
                        tiktokLabel: @js(old('tiktok_label', $settings->tiktokLabel())),
                        youtubeUrl: @js(old('youtube_url', $settings->youtubeUrl())),
                        youtubeLabel: @js(old('youtube_label', $settings->youtubeLabel())),
                        mapsUrl: @js(old('maps_url', $settings->mapsUrl())),
                        mapsLabel: @js(old('maps_label', $settings->mapsLabel())),
                        footerText: @js(old('footer_text', $settings->footerText())),

                        autoHandle(platform) {
                            if (platform === 'instagram' && this.instagramUrl && !this.instagramLabel) {
                                try {
                                    const p = new URL(this.instagramUrl).pathname.split('/').filter(Boolean)[0];
                                    if (p) this.instagramLabel = '@' + p.replace(/^@/, '');
                                } catch(e) {
                                    const parts = this.instagramUrl.split('/').filter(Boolean);
                                    if (parts.length > 0) this.instagramLabel = '@' + parts[parts.length - 1].replace(/^@/, '');
                                }
                            }
                            if (platform === 'tiktok' && this.tiktokUrl && !this.tiktokLabel) {
                                try {
                                    const p = new URL(this.tiktokUrl).pathname.split('/').filter(Boolean)[0];
                                    if (p) this.tiktokLabel = '@' + p.replace(/^@/, '');
                                } catch(e) {
                                    const parts = this.tiktokUrl.split('/').filter(Boolean);
                                    if (parts.length > 0) this.tiktokLabel = '@' + parts[parts.length - 1].replace(/^@/, '');
                                }
                            }
                            if (platform === 'youtube' && this.youtubeUrl && !this.youtubeLabel) {
                                try {
                                    const p = new URL(this.youtubeUrl).pathname.split('/').filter(Boolean)[0];
                                    if (p) this.youtubeLabel = p.startsWith('@') ? p : 'SynemaX Cinema';
                                } catch(e) {}
                            }
                            if (platform === 'maps' && this.mapsUrl && !this.mapsLabel) {
                                this.mapsLabel = 'Lihat di Google Maps';
                            }
                        }
                     }">
                    <div>
                        <h2 class="text-base font-bold text-text">Informasi Kontak & Media Sosial</h2>
                        <p class="mt-0.5 text-xs text-text-muted">Kelola kontak bioskop dan tautan media sosial. Tautan publik hanya tampil bila URL diisi dan parameter pelacak dibersihkan otomatis.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="contact_email" class="block text-xs font-bold uppercase tracking-wider text-text">Alamat Email</label>
                            <input id="contact_email" name="contact_email" type="email" x-model="email" class="mt-1.5 min-h-[44px] w-full rounded-xl border border-border-input bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none">
                        </div>
                        <div>
                            <label for="contact_address" class="block text-xs font-bold uppercase tracking-wider text-text">Alamat Bioskop Fisik</label>
                            <input id="contact_address" name="contact_address" type="text" x-model="address" class="mt-1.5 min-h-[44px] w-full rounded-xl border border-border-input bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none">
                        </div>
                    </div>

                    {{-- Form Dua Kolom per Platform Media Sosial --}}
                    <div class="pt-3 border-t border-border space-y-4">
                        <span class="block text-xs font-bold uppercase tracking-wider text-text">Platform Media Sosial & Peta</span>

                        {{-- Instagram --}}
                        <div class="space-y-1 sm:space-y-0 sm:grid sm:grid-cols-12 sm:items-center sm:gap-3 rounded-xl border border-border bg-surface-2/40 p-3">
                            <div class="sm:col-span-2">
                                <span class="text-xs font-bold text-text">Instagram</span>
                            </div>
                            <div class="sm:col-span-4">
                                <label for="instagram_label" class="sr-only">Nama Tampil Instagram</label>
                                <input id="instagram_label" name="instagram_label" type="text" x-model="instagramLabel" maxlength="30" placeholder="Nama tampil (misal: @synemax)" class="min-h-11 w-full rounded-lg border border-border-input bg-surface px-3 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none">
                            </div>
                            <div class="sm:col-span-6">
                                <label for="instagram_url" class="sr-only">URL Instagram</label>
                                <input id="instagram_url" name="instagram_url" type="url" x-model="instagramUrl" @blur="autoHandle('instagram')" placeholder="https://instagram.com/synemax" class="min-h-11 w-full rounded-lg border border-border-input bg-surface px-3 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none">
                            </div>
                        </div>

                        {{-- TikTok --}}
                        <div class="space-y-1 sm:space-y-0 sm:grid sm:grid-cols-12 sm:items-center sm:gap-3 rounded-xl border border-border bg-surface-2/40 p-3">
                            <div class="sm:col-span-2">
                                <span class="text-xs font-bold text-text">TikTok</span>
                            </div>
                            <div class="sm:col-span-4">
                                <label for="tiktok_label" class="sr-only">Nama Tampil TikTok</label>
                                <input id="tiktok_label" name="tiktok_label" type="text" x-model="tiktokLabel" maxlength="30" placeholder="Nama tampil (misal: @synemax)" class="min-h-11 w-full rounded-lg border border-border-input bg-surface px-3 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none">
                            </div>
                            <div class="sm:col-span-6">
                                <label for="tiktok_url" class="sr-only">URL TikTok</label>
                                <input id="tiktok_url" name="tiktok_url" type="url" x-model="tiktokUrl" @blur="autoHandle('tiktok')" placeholder="https://tiktok.com/@synemax" class="min-h-11 w-full rounded-lg border border-border-input bg-surface px-3 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none">
                            </div>
                        </div>

                        {{-- YouTube --}}
                        <div class="space-y-1 sm:space-y-0 sm:grid sm:grid-cols-12 sm:items-center sm:gap-3 rounded-xl border border-border bg-surface-2/40 p-3">
                            <div class="sm:col-span-2">
                                <span class="text-xs font-bold text-text">YouTube</span>
                            </div>
                            <div class="sm:col-span-4">
                                <label for="youtube_label" class="sr-only">Nama Tampil YouTube</label>
                                <input id="youtube_label" name="youtube_label" type="text" x-model="youtubeLabel" maxlength="30" placeholder="Nama tampil (misal: SynemaX Cinema)" class="min-h-11 w-full rounded-lg border border-border-input bg-surface px-3 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none">
                            </div>
                            <div class="sm:col-span-6">
                                <label for="youtube_url" class="sr-only">URL YouTube</label>
                                <input id="youtube_url" name="youtube_url" type="url" x-model="youtubeUrl" @blur="autoHandle('youtube')" placeholder="https://youtube.com/@synemax" class="min-h-11 w-full rounded-lg border border-border-input bg-surface px-3 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none">
                            </div>
                        </div>

                        {{-- Google Maps --}}
                        <div class="space-y-1 sm:space-y-0 sm:grid sm:grid-cols-12 sm:items-center sm:gap-3 rounded-xl border border-border bg-surface-2/40 p-3">
                            <div class="sm:col-span-2">
                                <span class="text-xs font-bold text-text">Google Maps</span>
                            </div>
                            <div class="sm:col-span-4">
                                <label for="maps_label" class="sr-only">Nama Tampil Maps</label>
                                <input id="maps_label" name="maps_label" type="text" x-model="mapsLabel" maxlength="30" placeholder="Nama tampil (misal: Lihat di Google Maps)" class="min-h-11 w-full rounded-lg border border-border-input bg-surface px-3 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none">
                            </div>
                            <div class="sm:col-span-6">
                                <label for="maps_url" class="sr-only">URL Google Maps</label>
                                <input id="maps_url" name="maps_url" type="url" x-model="mapsUrl" @blur="autoHandle('maps')" placeholder="https://maps.google.com/..." class="min-h-11 w-full rounded-lg border border-border-input bg-surface px-3 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    {{-- Pratinjau Langsung "Tampilan di footer" --}}
                    <div class="pt-4 border-t border-border">
                        <span class="block text-xs font-bold uppercase tracking-wider text-text mb-2">Pratinjau Langsung: Tampilan di Footer</span>
                        <div class="rounded-xl border border-border bg-surface-2 p-4 text-xs space-y-2">
                            <template x-if="email">
                                <div class="flex items-center gap-2 text-text">
                                    <span class="font-bold">Email:</span>
                                    <span class="text-text-muted" x-text="email"></span>
                                </div>
                            </template>
                            <template x-if="instagramUrl">
                                <div class="flex items-center gap-2 text-text">
                                    <span class="font-bold">Instagram:</span>
                                    <span class="text-text-muted" x-text="instagramLabel || '@synemax'"></span>
                                </div>
                            </template>
                            <template x-if="tiktokUrl">
                                <div class="flex items-center gap-2 text-text">
                                    <span class="font-bold">TikTok:</span>
                                    <span class="text-text-muted" x-text="tiktokLabel || '@synemax'"></span>
                                </div>
                            </template>
                            <template x-if="youtubeUrl">
                                <div class="flex items-center gap-2 text-text">
                                    <span class="font-bold">YouTube:</span>
                                    <span class="text-text-muted" x-text="youtubeLabel || 'SynemaX Cinema'"></span>
                                </div>
                            </template>
                            <template x-if="mapsUrl">
                                <div class="flex items-center gap-2 text-text">
                                    <span class="font-bold">Peta:</span>
                                    <span class="text-text-muted" x-text="mapsLabel || 'Lihat di Google Maps'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-border space-y-4">
                        <div>
                            <label for="footer_text" class="block text-xs font-bold uppercase tracking-wider text-text">Teks Hak Cipta Footer</label>
                            <input id="footer_text" name="footer_text" type="text" x-model="footerText" class="mt-1.5 min-h-[44px] w-full rounded-xl border border-border-input bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none">
                            <p class="mt-1 text-[11px] text-text-muted">Gunakan token <code>{tahun}</code> untuk menyisipkan tahun berjalan otomatis (contoh: <em>&copy; {tahun} SynemaX Cinema. Hak cipta dilindungi.</em>).</p>
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
                    <button type="submit" class="btn-gold-cinema inline-flex min-h-[44px] items-center rounded-xl px-6 font-bold text-sm shadow-none">
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
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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

                    {{-- Hapus Data Contoh --}}
                    <div class="rounded-xl border border-amber-500/30 p-4 bg-amber-500/5 space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-amber-600 dark:text-amber-400">Hapus Data Contoh</h3>
                            <span class="rounded bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400">{{ $sampleCounts['total'] ?? 0 }} item</span>
                        </div>
                        <p class="text-xs text-text-muted leading-relaxed">
                            Hapus seluruh data contoh awal ({{ $sampleCounts['films'] ?? 0 }} film, {{ $sampleCounts['promos'] ?? 0 }} promo, {{ $sampleCounts['banners'] ?? 0 }} hero, {{ $sampleCounts['nobar'] ?? 0 }} nobar, {{ $sampleCounts['news'] ?? 0 }} kabar) sebelum merilis situs ke publik.
                        </p>
                        @if (($sampleCounts['total'] ?? 0) > 0)
                            <form method="POST" action="{{ route('admin.settings.delete_sample') }}"
                                  data-confirm="Hapus seluruh {{ $sampleCounts['total'] }} data contoh ({{ $sampleCounts['films'] }} film, {{ $sampleCounts['promos'] }} promo, {{ $sampleCounts['banners'] }} banner hero, {{ $sampleCounts['nobar'] }} nobar, {{ $sampleCounts['news'] }} kabar) beserta file gambarnya? Data booking dan pengaturan asli Anda tetap aman."
                                  data-confirm-title="Hapus Seluruh Data Contoh"
                                  data-confirm-action="Hapus Sekarang"
                                  data-confirm-is-danger="true"
                                  novalidate>
                                @csrf
                                <button type="submit" class="mt-2 inline-flex min-h-[38px] items-center rounded-xl bg-amber-600 px-4 text-xs font-bold text-white hover:bg-amber-500">
                                    Hapus {{ $sampleCounts['total'] }} Data Contoh
                                </button>
                            </form>
                        @else
                            <button type="button" disabled class="mt-2 inline-flex min-h-[38px] items-center rounded-xl border border-border bg-surface-2 px-4 text-xs font-semibold text-text-muted cursor-not-allowed">
                                Tidak ada data contoh
                            </button>
                        @endif
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

        {{-- ============================================================== --}}
        {{-- TAB 8: NOTIFIKASI TELEGRAM --}}
        {{-- ============================================================== --}}
        <div x-show="activeTab === 'notifikasi'" x-cloak class="mt-6 space-y-6"
             x-data="{
                 isTesting: false,
                 isDetecting: false,
                 testResult: null,
                 detectResult: null,
                 detectedChats: [],
                 chatIdsInput: @js($telegramSettings['chat_ids'] ?? ''),
                 showToken: false,
                 botToken: @js($telegramSettings['masked_token'] ?? ''),

                 async runTest() {
                     this.isTesting = true;
                     this.testResult = null;
                     try {
                         const res = await fetch('{{ route('admin.settings.telegram.test') }}', {
                             method: 'POST',
                             headers: {
                                 'Content-Type': 'application/json',
                                 'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                 'Accept': 'application/json'
                             },
                             body: JSON.stringify({
                                 chat_id: this.chatIdsInput.split(/[\r\n,]+/)[0]?.trim(),
                                 token: this.botToken
                             })
                         });
                         const data = await res.json();
                         this.testResult = data;
                     } catch (err) {
                         this.testResult = { success: false, message: 'Gagal mengirim permintaan: ' + err.message };
                     } finally {
                         this.isTesting = false;
                     }
                 },

                 async runDetect() {
                     this.isDetecting = true;
                     this.detectResult = null;
                     this.detectedChats = [];
                     try {
                         const res = await fetch('{{ route('admin.settings.telegram.detect') }}', {
                             method: 'POST',
                             headers: {
                                 'Content-Type': 'application/json',
                                 'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                 'Accept': 'application/json'
                             },
                             body: JSON.stringify({
                                 token: this.botToken
                             })
                         });
                         const data = await res.json();
                         this.detectResult = data;
                         if (data.chats && data.chats.length > 0) {
                             this.detectedChats = data.chats;
                         }
                     } catch (err) {
                         this.detectResult = { success: false, message: 'Gagal mendeteksi: ' + err.message };
                     } finally {
                         this.isDetecting = false;
                     }
                 },

                 addChatId(id) {
                     const existing = this.chatIdsInput.split(/[\r\n,]+/).map(s => s.trim()).filter(Boolean);
                     if (!existing.includes(String(id))) {
                         existing.push(String(id));
                         this.chatIdsInput = existing.join('\n');
                     }
                 }
             }">

            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_tab" value="notifikasi">

                {{-- Peringatan Keamanan & Privasi Data --}}
                <div role="alert" class="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-5 text-amber-600 dark:text-amber-400">
                    <div class="flex items-start gap-3">
                        <svg class="size-5 shrink-0 mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-xs space-y-1">
                            <p class="font-bold text-sm text-text">Peringatan Privasi Data Pelanggan</p>
                            <p class="text-text-muted">Pesan notifikasi memuat rincian transaksi dan data pemesan. Pastikan bot hanya mengirim ke chat pribadi admin atau grup privat internal pengelola bioskop, jangan hubungkan ke grup atau channel publik.</p>
                        </div>
                    </div>
                </div>

                {{-- Konfigurasi Utama Bot Telegram --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-bold text-text">Integrasi Bot Telegram</h2>
                            <p class="text-xs text-text-muted mt-0.5">Kirim notifikasi otomatis secara real-time ke Telegram admin tanpa perlu selalu membuka website.</p>
                        </div>
                        <x-form.switch
                            name="telegram_notifications_enabled"
                            label="Aktifkan Notifikasi Telegram"
                            value="1"
                            :checked="!empty($telegramSettings['enabled'])"
                        />
                    </div>

                    {{-- Panduan Bertahap --}}
                    <div class="rounded-xl border border-border bg-surface-2 p-4 text-xs text-text-muted space-y-2">
                        <p class="font-bold text-text text-sm">Panduan Menyiapkan Bot Telegram:</p>
                        <ol class="list-decimal list-inside space-y-1.5 pl-1 leading-relaxed">
                            <li>Buka Telegram, cari <a href="https://t.me/BotFather" target="_blank" rel="noopener noreferrer" class="font-semibold text-amber-600 dark:text-amber-400 underline">@BotFather</a>, lalu kirim perintah <code class="rounded bg-surface px-1.5 py-0.5 border border-border font-mono text-[11px]">/newbot</code>.</li>
                            <li>Beri nama dan username bot, lalu salin token API yang diberikan dan tempelkan ke kolom <strong>Token Bot Telegram</strong> di bawah.</li>
                            <li>Buka bot baru Anda di Telegram, tekan tombol <strong>Start</strong> (atau kirim <code class="rounded bg-surface px-1.5 py-0.5 border border-border font-mono text-[11px]">/start</code>). Jika untuk grup, masukkan bot ke grup tersebut.</li>
                            <li>Klik tombol <strong>Deteksi Chat ID</strong> di bawah untuk memilih ID obrolan secara instan (dapat digunakan langsung dengan token yang sedang diketik).</li>
                            <li>Tekan tombol <strong>Kirim Pesan Tes ke Telegram</strong> untuk memverifikasi, lalu klik <strong>Simpan Pengaturan Notifikasi</strong> di bagian bawah halaman.</li>
                        </ol>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 items-start">
                        {{-- Token Bot --}}
                        <div class="space-y-1.5">
                            <label for="telegram_bot_token" class="block text-xs font-bold uppercase tracking-wider text-text h-5 flex items-center">
                                Token Bot Telegram
                            </label>
                            <div class="relative">
                                <input
                                    :type="showToken ? 'text' : 'password'"
                                    id="telegram_bot_token"
                                    name="telegram_bot_token"
                                    x-model="botToken"
                                    placeholder="Contoh: 123456789:AAH_XYZ..."
                                    autocomplete="new-password"
                                    class="min-h-[44px] w-full rounded-xl border border-border bg-surface px-3 pr-10 text-sm font-mono text-text focus:border-amber-500 focus:outline-none"
                                >
                                <button
                                    type="button"
                                    @click="showToken = !showToken"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-text-muted hover:text-text"
                                    aria-label="Lihat atau sembunyikan token"
                                >
                                    <svg x-show="!showToken" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showToken" x-cloak class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-[11px] text-text-muted">Disimpan secara terenkripsi di server (Crypt/AES). Nilai tersamar tidak ditampilkan ulang untuk keamanan.</p>
                        </div>

                        {{-- Chat ID --}}
                        <div class="space-y-1.5">
                            <label for="telegram_chat_ids" class="block text-xs font-bold uppercase tracking-wider text-text h-5 flex items-center">
                                Chat ID Tujuan
                            </label>
                            <textarea
                                id="telegram_chat_ids"
                                name="telegram_chat_ids"
                                rows="3"
                                x-model="chatIdsInput"
                                placeholder="Contoh: 12345678&#10;-100987654321 (ID grup/channel)"
                                class="w-full rounded-xl border border-border bg-surface p-2.5 text-xs font-mono text-text focus:border-amber-500 focus:outline-none"
                            ></textarea>
                            <div class="flex items-center justify-between gap-2 pt-0.5">
                                <p class="text-[11px] text-text-muted">Satu atau beberapa Chat ID (pisahkan dengan baris baru). ID grup diawali tanda minus (-).</p>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button
                                        type="button"
                                        @click="runDetect()"
                                        :disabled="isDetecting || !botToken"
                                        class="inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border border-slate-400 dark:border-white/20 bg-surface px-3 py-1 text-xs font-semibold text-text hover:border-amber-500 hover:text-amber-500 disabled:border-slate-600 disabled:text-slate-400 dark:disabled:text-slate-300 disabled:cursor-not-allowed transition"
                                    >
                                        <span x-show="!isDetecting">Deteksi Chat ID</span>
                                        <span x-show="isDetecting" x-cloak>Mendeteksi...</span>
                                    </button>
                                </div>
                            </div>
                            <div x-show="!botToken" class="text-[11px] text-amber-500 font-medium">Isi token dulu untuk mendeteksi chat ID secara otomatis.</div>

                            {{-- Hasil Deteksi Chat ID --}}
                            <div x-show="detectedChats.length > 0" x-cloak class="mt-2 space-y-1.5 rounded-xl border border-border bg-surface-2 p-3 text-xs">
                                <p class="font-bold text-text">Obrolan Ditemukan di Bot:</p>
                                <div class="space-y-1 max-h-36 overflow-y-auto">
                                    <template x-for="c in detectedChats" :key="c.id">
                                        <div class="flex items-center justify-between gap-2 rounded-lg bg-surface border border-border px-2.5 py-1.5">
                                            <div class="min-w-0">
                                                <p class="font-bold text-text truncate" x-text="c.name"></p>
                                                <p class="text-[10px] text-text-muted font-mono" x-text="c.id + ' (' + c.type + ')'"></p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="addChatId(c.id)"
                                                class="rounded-md border border-border bg-surface-2 px-2 py-0.5 text-[10px] font-bold text-text hover:bg-amber-500 hover:text-slate-950 transition"
                                            >
                                                Pilih
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div x-show="detectResult && !detectResult.success" x-cloak class="mt-1 text-xs text-danger font-semibold" x-text="detectResult?.message"></div>
                        </div>
                    </div>

                    {{-- Tombol Uji Pesan --}}
                    <div class="pt-3 border-t border-border flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="runTest()"
                                :disabled="isTesting || !botToken"
                                class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-slate-400 dark:border-white/20 bg-surface-2 px-4 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 disabled:border-slate-600 disabled:text-slate-400 dark:disabled:text-slate-300 disabled:cursor-not-allowed transition"
                            >
                                <span x-show="!isTesting">Kirim Pesan Tes ke Telegram</span>
                                <span x-show="isTesting" x-cloak>Mengirim Uji Coba...</span>
                            </button>
                            <span x-show="!botToken" class="text-xs text-amber-500 font-medium">Isi token dulu</span>
                        </div>
                        <div x-show="testResult" x-cloak class="text-xs font-semibold">
                            <span :class="testResult?.success ? 'text-success' : 'text-danger'" x-text="testResult?.message"></span>
                        </div>
                    </div>
                </div>

                {{-- Pilihan Peristiwa Notifikasi --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
                    <h2 class="text-base font-bold text-text">Pilihan Peristiwa Notifikasi</h2>
                    <p class="text-xs text-text-muted">Pilih kejadian transaksi yang ingin dikirimkan langsung ke Telegram Anda.</p>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="flex items-center justify-between rounded-xl border border-border p-3.5 bg-surface-2">
                            <div>
                                <p class="text-xs font-bold text-text">Booking Baru Diterima</p>
                                <p class="text-[11px] text-text-muted">Notifikasi saat pelanggan mengirim checkout.</p>
                            </div>
                            <x-form.switch
                                name="telegram_notify_booking_created"
                                value="1"
                                :checked="!empty($telegramSettings['notify_booking_created'])"
                            />
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-border p-3.5 bg-surface-2">
                            <div>
                                <p class="text-xs font-bold text-text">Bukti Bayar Diunggah / Perlu Konfirmasi</p>
                                <p class="text-[11px] text-text-muted">Saat bukti transfer atau QRIS diunggah pelanggan.</p>
                            </div>
                            <x-form.switch
                                name="telegram_notify_proof_uploaded"
                                value="1"
                                :checked="!empty($telegramSettings['notify_proof_uploaded'])"
                            />
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-border p-3.5 bg-surface-2">
                            <div>
                                <p class="text-xs font-bold text-text">Pembayaran Dikonfirmasi</p>
                                <p class="text-[11px] text-text-muted">Saat admin menandai booking telah dibayar.</p>
                            </div>
                            <x-form.switch
                                name="telegram_notify_payment_confirmed"
                                value="1"
                                :checked="!empty($telegramSettings['notify_payment_confirmed'])"
                            />
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-border p-3.5 bg-surface-2">
                            <div>
                                <p class="text-xs font-bold text-text">Booking Dibatalkan Pelanggan</p>
                                <p class="text-[11px] text-text-muted">Saat booking dibatalkan secara mandiri.</p>
                            </div>
                            <x-form.switch
                                name="telegram_notify_booking_cancelled"
                                value="1"
                                :checked="!empty($telegramSettings['notify_booking_cancelled'])"
                            />
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-border p-3.5 bg-surface-2">
                            <div>
                                <p class="text-xs font-bold text-text">Booking Kedaluwarsa</p>
                                <p class="text-[11px] text-text-muted">Saat batas waktu bayar habis tanpa konfirmasi.</p>
                            </div>
                            <x-form.switch
                                name="telegram_notify_booking_expired"
                                value="1"
                                :checked="!empty($telegramSettings['notify_booking_expired'])"
                            />
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-border p-3.5 bg-surface-2">
                            <div>
                                <p class="text-xs font-bold text-text">Sesi Pasti Tayang</p>
                                <p class="text-[11px] text-text-muted">Saat kuorum penonton tercapai atau sesi terkunci.</p>
                            </div>
                            <x-form.switch
                                name="telegram_notify_session_confirmed"
                                value="1"
                                :checked="!empty($telegramSettings['notify_session_confirmed'])"
                            />
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-border p-3.5 bg-surface-2 sm:col-span-2">
                            <div>
                                <p class="text-xs font-bold text-text">Sesi Dibatalkan Karena Penonton Kurang</p>
                                <p class="text-[11px] text-text-muted">Saat batas konfirmasi kuorum habis dan sesi ditutup otomatis.</p>
                            </div>
                            <x-form.switch
                                name="telegram_notify_session_cancelled"
                                value="1"
                                :checked="!empty($telegramSettings['notify_session_cancelled'])"
                            />
                        </div>
                    </div>
                </div>

                {{-- Pengaturan Privasi & Tampilan Pesan --}}
                <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
                    <h2 class="text-base font-bold text-text">Privasi & Format Pesan</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="flex items-center justify-between rounded-xl border border-border p-3.5 bg-surface-2">
                            <div>
                                <p class="text-xs font-bold text-text">Sembunyikan Nomor WhatsApp Pemesan</p>
                                <p class="text-[11px] text-text-muted">Ganti nomor WhatsApp dengan label [Disembunyikan].</p>
                            </div>
                            <x-form.switch
                                name="telegram_hide_customer_whatsapp"
                                value="1"
                                :checked="!empty($telegramSettings['hide_customer_whatsapp'])"
                            />
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-border p-3.5 bg-surface-2">
                            <div>
                                <p class="text-xs font-bold text-text">Sertakan Catatan Pemesan</p>
                                <p class="text-[11px] text-text-muted">Tampilkan catatan khusus pelanggan di badan pesan.</p>
                            </div>
                            <x-form.switch
                                name="telegram_include_customer_notes"
                                value="1"
                                :checked="!empty($telegramSettings['include_customer_notes'])"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                        Simpan Pengaturan Notifikasi
                    </button>
                </div>
            </form>

            {{-- Log Pengiriman Telegram (50 Terakhir) --}}
            <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-text">Log Pengiriman Notifikasi (Outbox)</h2>
                        <p class="text-xs text-text-muted mt-0.5">50 pengiriman pesan Telegram terbaru dari antrean sistem.</p>
                    </div>
                </div>

                @if ($telegramLogs->isEmpty())
                    <div class="rounded-xl border border-dashed border-border p-8 text-center text-xs text-text-muted">
                        Belum ada aktivitas pengiriman notifikasi Telegram.
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-border">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-border bg-surface-2 text-text-muted font-bold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="px-4 py-3">Waktu</th>
                                    <th class="px-4 py-3">Peristiwa</th>
                                    <th class="px-4 py-3">Kode Booking</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Keterangan / Galat</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach ($telegramLogs as $log)
                                    <tr class="hover:bg-surface-2 transition">
                                        <td class="px-4 py-3 whitespace-nowrap text-text font-mono text-[11px]">
                                            {{ format_hari_tanggal_id($log->created_at) }}<br>
                                            <span class="text-text-muted">{{ format_jam_id($log->created_at) }}</span>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-text whitespace-nowrap">
                                            {{ match ($log->event) {
                                                'booking_created' => 'Booking Baru',
                                                'proof_uploaded' => 'Bukti Diunggah',
                                                'payment_confirmed' => 'Bayar Terkonfirmasi',
                                                'booking_cancelled' => 'Booking Batal',
                                                'booking_expired' => 'Kedaluwarsa',
                                                'session_confirmed' => 'Pasti Tayang',
                                                'session_cancelled' => 'Sesi Batal',
                                                default => ucfirst($log->event),
                                            } }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-mono font-bold text-text">
                                            {{ $log->booking?->code ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <x-ui.status
                                                :status="match ($log->status) {
                                                    'sent' => 'terkonfirmasi',
                                                    'failed' => 'dibatalkan',
                                                    default => 'menunggu_pembayaran',
                                                }"
                                                :label="match ($log->status) {
                                                    'sent' => 'Terkirim',
                                                    'failed' => 'Gagal',
                                                    default => 'Antrean (' . $log->attempts . ')',
                                                }"
                                            />
                                        </td>
                                        <td class="px-4 py-3 text-text-muted max-w-xs truncate">
                                            {{ $log->last_error ?? ($log->status === 'sent' ? 'Berhasil terkirim ke Telegram' : '-') }}
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <form method="POST" action="{{ route('admin.settings.telegram.retry', $log->id) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-lg border border-border bg-surface px-2.5 py-1 text-[11px] font-semibold text-text hover:border-amber-500 hover:text-amber-500 transition"
                                                >
                                                    Kirim Ulang
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 9: PROGRAM MEMBER --}}
        {{-- ============================================================== --}}
        <div x-show="activeTab === 'member'" class="space-y-6" style="display: none;">
            <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-border pb-4">
                    <div>
                        <h2 class="text-base font-bold text-text">Program Member & Loyalitas Kunjungan</h2>
                        <p class="text-xs text-text-muted mt-1">Konfigurasi aturan kunjungan berulang, potongan harga, dan tiket gratis.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_tab" value="member">

                    {{-- Switch Aktifkan Program --}}
                    <div class="rounded-xl border border-border bg-surface-2 p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                name="member_program_enabled"
                                value="1"
                                {{ old('member_program_enabled', $settings->isMemberProgramEnabled()) ? 'checked' : '' }}
                                class="mt-0.5 size-4 rounded border-border text-amber-500 focus:ring-amber-500/30"
                            >
                            <div>
                                <span class="text-sm font-semibold text-text">Aktifkan Program Member</span>
                                <p class="text-xs text-text-muted mt-0.5">Bila dinonaktifkan, pemindaian kode member pada menu Scan Tiket akan ditolak sementara.</p>
                            </div>
                        </label>
                    </div>

                    {{-- Konfigurasi Kunjungan & Reward --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Potongan Kunjungan --}}
                        <div class="rounded-xl border border-border bg-surface-2 p-4 space-y-4">
                            <h3 class="text-sm font-bold text-text border-b border-border pb-2">Reward Kunjungan Antara</h3>
                            
                            <div>
                                <label for="member_discount_visit" class="block text-xs font-semibold text-text-muted mb-1.5">
                                    Nomor Kunjungan Mendapat Potongan
                                </label>
                                <input
                                    type="number"
                                    id="member_discount_visit"
                                    name="member_discount_visit"
                                    min="1"
                                    value="{{ old('member_discount_visit', $settings->memberDiscountVisit()) }}"
                                    required
                                    class="w-full rounded-xl border border-border bg-surface px-3.5 py-2 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                                >
                                <p class="text-[11px] text-text-muted mt-1">Bawaan: 5 (kunjungan ke-5 dalam tiap siklus 10 kunjungan).</p>
                            </div>

                            <div>
                                <label for="member_discount_amount" class="block text-xs font-semibold text-text-muted mb-1.5">
                                    Nominal Potongan (Rp)
                                </label>
                                <input
                                    type="number"
                                    id="member_discount_amount"
                                    name="member_discount_amount"
                                    min="0"
                                    step="500"
                                    value="{{ old('member_discount_amount', $settings->memberDiscountAmount()) }}"
                                    required
                                    class="w-full rounded-xl border border-border bg-surface px-3.5 py-2 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                                >
                                <p class="text-[11px] text-text-muted mt-1">Bawaan: Rp 2.000 (maksimal seharga tiket 1 orang).</p>
                            </div>
                        </div>

                        {{-- Siklus & Tiket Gratis --}}
                        <div class="rounded-xl border border-border bg-surface-2 p-4 space-y-4">
                            <h3 class="text-sm font-bold text-text border-b border-border pb-2">Siklus & Tiket Gratis</h3>

                            <div>
                                <label for="member_free_visit" class="block text-xs font-semibold text-text-muted mb-1.5">
                                    Panjang Siklus (Kunjungan Gratis)
                                </label>
                                <input
                                    type="number"
                                    id="member_free_visit"
                                    name="member_free_visit"
                                    min="2"
                                    value="{{ old('member_free_visit', $settings->memberFreeVisit()) }}"
                                    required
                                    class="w-full rounded-xl border border-border bg-surface px-3.5 py-2 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                                >
                                <p class="text-[11px] text-text-muted mt-1">Bawaan: 10 (kunjungan ke-10 gratis tiket nonton 1 orang, lalu siklus berulang dari 1).</p>
                            </div>

                            <div>
                                <label for="member_max_visits_per_day" class="block text-xs font-semibold text-text-muted mb-1.5">
                                    Maksimal Kunjungan Dicatat Per Hari
                                </label>
                                <x-ui.select
                                    id="member_max_visits_per_day"
                                    name="member_max_visits_per_day"
                                    class="w-full"
                                >
                                    <option value="0" {{ old('member_max_visits_per_day', $settings->memberMaxVisitsPerDay()) == 0 ? 'selected' : '' }}>
                                        Tidak ada batas
                                    </option>
                                    @for ($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('member_max_visits_per_day', $settings->memberMaxVisitsPerDay()) == $i ? 'selected' : '' }}>
                                            {{ $i }} kunjungan per hari
                                        </option>
                                    @endfor
                                </x-ui.select>
                                <p class="text-[11px] text-text-muted mt-1">Mencegah penyalahgunaan pemindaian berulang di hari yang sama.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Panduan & Privasi Data --}}
                    <div class="rounded-xl border border-blue-500/30 bg-blue-500/5 p-4 text-xs text-text space-y-2">
                        <p class="font-bold text-blue-600 dark:text-blue-400">Prinsip Privasi & Verifikasi Petugas</p>
                        <p class="text-text-muted leading-relaxed">
                            <strong>Privasi Data:</strong> Member perlu diberi tahu bahwa data (hanya nama dan foto) dipakai semata-mata untuk program loyalitas ini. Tidak ada data kontak (telepon, email, WhatsApp, alamat) yang disimpan oleh sistem demi menjaga keamanan data pribadi pelanggan.
                        </p>
                        <p class="text-text-muted leading-relaxed">
                            <strong>Verifikasi Petugas:</strong> Karena QR code kartu member dapat difoto atau dibagikan ke orang lain, petugas pintu wajib mencocokkan foto dan nama member pada panel hasil pindai sebelum menekan tombol pencatatan kunjungan.
                        </p>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-border">
                        <button
                            type="submit"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold text-black shadow-sm hover:bg-amber-400 transition"
                        >
                            Simpan Pengaturan Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
