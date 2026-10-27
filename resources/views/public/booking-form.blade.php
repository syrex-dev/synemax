@extends('layouts.public')

@section('title', 'Pesan Tiket Studio: SynemaX')
@section('meta')
    <meta name="description" content="Pilih film, denah kursi studio, dan jajan untuk sesi studio mini SynemaX dengan standar bioskop modern.">
@endsection

@section('content')
    @php
        $canPerPerson = ! $session->isPrivateActive() && $session->remainingSeats() > 0;
        $canPrivate = ! $session->isPrivateActive()
            && ! $session->hasActivePerPersonBookings()
            && $session->remainingSeats() === \App\Models\Session::MAX_CAPACITY
            && $session->seatCapacity() === \App\Models\Session::MAX_CAPACITY;
        $privateDisabledReason = $session->isPrivateActive()
            ? 'Sesi ini sudah disewa privat.'
            : ($session->hasActivePerPersonBookings() || $occupiedSeats > 0
                ? 'Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia.'
                : 'Kapasitas sesi tidak mencukupi untuk sewa privat.');
        $perPersonDisabledReason = $session->isPrivateActive()
            ? 'Sesi ini sudah disewa privat.'
            : 'Kursi pada sesi ini sudah penuh.';
        $occupiedCount = 6 - $session->remainingSeats();
    @endphp

    <div class="mx-auto max-w-xl px-4 pb-40 pt-4 sm:px-6 sm:pb-44 sm:pt-6">
        {{-- Header & Slot Jam Terpilih --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
            <div>
                <a href="{{ route('schedule.date', ['date' => $session->session_date]) }}"
                   class="inline-flex min-h-11 items-center gap-1.5 text-xs font-bold text-amber-500 hover:text-amber-400">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Ganti jadwal</span>
                </a>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">Amankan kursimu</h1>
                <p class="mt-0.5 text-xs text-text-muted">{{ $session->startsAt()->locale('id')->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="inline-flex items-center gap-1.5 rounded-xl border border-amber-400/40 bg-amber-500/10 px-4 py-2 font-tabular text-sm font-black text-amber-400 shadow-sm">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ substr((string) $session->start_time, 0, 5) }} WIB</span>
            </div>
        </div>

        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs text-danger-text">
                <p class="font-bold text-sm">Periksa kembali data booking Anda:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('booking.store', ['session' => $session->id]) }}" enctype="multipart/form-data"
              x-data="{
                  sessionId: @js((string) $session->id),
                  step: @js((int) old('wizard_step', 2)),
                  type: @js(old('booking_type', '')),
                  pax: @js((int) old('pax', 1)),
                  paymentMethod: @js(old('payment_method', $paymentMethods[0] ?? '')),
                  filmId: @js((string) old('film_id', $session->film_id ?? '')),
                  filmOptions: @js($films->map(fn ($film) => ['title' => $film->title, 'genre' => $film->genre])->values()),
                  remaining: {{ $session->remainingSeats() }},
                  occupiedCount: {{ $occupiedCount }},
                  canPrivate: @js($canPrivate),
                  canPerPerson: @js($canPerPerson),
                  privateReason: @js($privateDisabledReason),
                  perPersonReason: @js($perPersonDisabledReason),
                  availabilityEndpoint: @js(route('booking.availability', ['session' => $session->id])),
                  isCheckingAvailability: false,
                  unitPrice: {{ $unitPrice }},
                  privatePrice: {{ $privatePrice }},
                  hasSnacks: @js($snacksEnabled),
                  snackMaxQty: {{ $snackMaxQty }},
                  snackCategories: @js($snackCategories),
                  activeCategory: @js($snackCategories->first()['id'] ?? null),
                  cart: @js(old('snacks', [])),
                  filmSearch: '',
                  genreFilter: 'Semua',
                  filmDetail: null,
                  noteItemId: null,
                  noteValue: '',
                  basketOpen: false,
                  wizardError: '',
                  isSubmitting: false,
                  storageAvailable: true,

                  // Denah Kursi Sinematik (6 Kursi Mini Studio)
                  seats: [
                      { id: 'A1', label: 'A1' },
                      { id: 'A2', label: 'A2' },
                      { id: 'A3', label: 'A3' },
                      { id: 'B1', label: 'B1' },
                      { id: 'B2', label: 'B2' },
                      { id: 'B3', label: 'B3' },
                  ],
                  selectedSeats: [],

                  // Fitur Promo & Voucher
                  promoCode: '',
                  promoApplied: null,
                  promoDiscount: 0,
                  promoMessage: '',
                  isPromoValid: false,

                  isSeatOccupied(seatIndex) {
                      return seatIndex >= (6 - this.occupiedCount);
                  },
                  toggleSeat(seatId, seatIndex) {
                      if (this.isSeatOccupied(seatIndex)) return;
                      if (!this.type || this.type === 'private') {
                          this.type = 'per_orang';
                      }
                      const idx = this.selectedSeats.indexOf(seatId);
                      if (idx > -1) {
                          if (this.selectedSeats.length > 1) {
                              this.selectedSeats.splice(idx, 1);
                          }
                      } else {
                          if (this.selectedSeats.length < this.remaining) {
                              this.selectedSeats.push(seatId);
                          }
                      }
                      this.pax = Math.max(1, this.selectedSeats.length);
                      this.persist();
                  },
                  syncSeatsWithPax() {
                      if (this.type === 'private') {
                          this.selectedSeats = this.seats.filter((_, i) => !this.isSeatOccupied(i)).map(s => s.id);
                          return;
                      }
                      const available = this.seats.filter((_, i) => !this.isSeatOccupied(i)).map(s => s.id);
                      this.selectedSeats = available.slice(0, Math.min(this.pax, available.length));
                  },
                  selectPrivate() {
                      if (!this.canPrivate) return;
                      this.type = 'private';
                      this.pax = 6;
                      this.selectedSeats = this.seats.map(s => s.id);
                      this.persist();
                  },
                  selectPerPerson() {
                      if (!this.canPerPerson) return;
                      this.type = 'per_orang';
                      this.syncSeatsWithPax();
                      this.persist();
                  },

                  // Validasi Promo Code
                  applyPromo() {
                      const code = this.promoCode.trim().toUpperCase();
                      if (!code) return;
                      if (code === 'XXIPREMIERE') {
                          this.isPromoValid = true;
                          this.promoApplied = 'XXIPREMIERE';
                          this.promoDiscount = Math.round(this.ticketTotal * 0.1);
                          this.promoMessage = 'Voucher XXI Premiere Berhasil: Diskon 10% Tiket!';
                      } else if (code === 'SYNEMAX5K') {
                          this.isPromoValid = true;
                          this.promoApplied = 'SYNEMAX5K';
                          this.promoDiscount = Math.min(this.total, 5000);
                          this.promoMessage = 'Voucher SynemaX Berhasil: Potongan Rp 5.000!';
                      } else if (code === 'DISKON50') {
                          this.isPromoValid = true;
                          this.promoApplied = 'DISKON50';
                          this.promoDiscount = Math.round(this.total * 0.5);
                          this.promoMessage = 'Promo Spesial: Diskon 50% Seluruh Pesanan!';
                      } else {
                          this.isPromoValid = false;
                          this.promoApplied = null;
                          this.promoDiscount = 0;
                          this.promoMessage = 'Kode promo tidak valid atau syarat tidak terpenuhi.';
                      }
                  },
                  removePromo() {
                      this.promoCode = '';
                      this.promoApplied = null;
                      this.promoDiscount = 0;
                      this.promoMessage = '';
                      this.isPromoValid = false;
                  },

                  async refreshAvailability() {
                      if (this.isCheckingAvailability) return;
                      this.isCheckingAvailability = true;
                      try {
                          const res = await fetch(this.availabilityEndpoint, {
                              headers: { 'Accept': 'application/json' },
                              cache: 'no-store'
                          });
                          if (res.ok) {
                              const data = await res.json();
                              this.remaining = Number(data.remaining_seats);
                              this.occupiedCount = 6 - this.remaining;
                              this.canPrivate = Boolean(data.can_private);
                              this.canPerPerson = Boolean(data.can_per_orang);
                              this.privateReason = data.private_disabled_reason || 'Sewa Private tidak tersedia.';
                              this.perPersonReason = data.per_orang_disabled_reason || 'Kursi pada sesi ini sudah penuh.';

                              if (this.type === 'private' && !this.canPrivate) {
                                  this.type = '';
                                  this.wizardError = this.privateReason;
                                  this.persist();
                                  if (this.step > 3) this.step = 3;
                              }
                              if (this.type === 'per_orang' && !this.canPerPerson) {
                                  this.type = '';
                                  this.wizardError = this.perPersonReason;
                                  this.persist();
                                  if (this.step > 3) this.step = 3;
                              } else if (this.type === 'per_orang' && Number(this.pax) > this.remaining) {
                                  this.pax = Math.max(1, this.remaining);
                                  this.syncSeatsWithPax();
                                  this.persist();
                              }
                          }
                      } catch (err) {
                      } finally {
                          this.isCheckingAvailability = false;
                      }
                  },
                  get ticketTotal() {
                      if (!this.type) return 0;
                      return this.type === 'private' ? this.privatePrice : this.unitPrice * Number(this.pax);
                  },
                  get filteredFilmCount() {
                      return this.filmOptions.filter((film) => this.matchesFilm(film.title, film.genre)).length;
                  },
                  get paxIsValid() {
                      return Number(this.pax) >= 1 && Number(this.pax) <= this.remaining;
                  },
                  get snackCount() { return Object.values(this.cart).reduce((sum, line) => sum + Number(line.qty || 0), 0) },
                  get snackTotal() {
                      return Object.entries(this.cart).reduce((sum, [id, line]) => {
                          const item = this.findItem(id);
                          return sum + (item ? item.price * Number(line.qty || 0) : 0);
                      }, 0);
                  },
                  get total() { return this.ticketTotal + this.snackTotal },
                  get grandTotal() { return Math.max(0, this.total - this.promoDiscount) },
                  findItem(id) {
                      for (const category of this.snackCategories) {
                          const item = category.items.find((entry) => String(entry.id) === String(id));
                          if (item) return item;
                      }
                      return null;
                  },
                  matchesFilm(title, genre) {
                      const query = this.filmSearch.trim().toLocaleLowerCase('id');
                      return (!query || `${title} ${genre}`.toLocaleLowerCase('id').includes(query))
                          && (this.genreFilter === 'Semua' || this.genreFilter === genre);
                  },
                  qty(id) { return Number((this.cart[id] || {}).qty || 0) },
                  changeQty(id, amount) {
                      const item = this.findItem(id);
                      if (!item || (!item.available && amount > 0)) return;
                      const next = Math.max(0, Math.min(this.snackMaxQty, this.qty(id) + amount));
                      if (next === 0) delete this.cart[id];
                      else this.cart[id] = { qty: next, note: (this.cart[id] || {}).note || '' };
                  },
                  chooseFilm(id) { this.filmId = String(id); this.filmDetail = null; this.persist() },
                  openNote(id) { this.noteItemId = id; this.noteValue = (this.cart[id] || {}).note || '' },
                  saveNote() {
                      if (this.cart[this.noteItemId]) this.cart[this.noteItemId].note = this.noteValue.trim();
                      this.noteItemId = null;
                      this.persist();
                  },
                  nextStep() {
                      if (this.step === 2 && !this.filmId) {
                          this.wizardError = 'Silakan pilih salah satu film sebelum melanjutkan.';
                          return;
                      }
                      if (this.step === 2) {
                          this.refreshAvailability();
                      }
                      if (this.step === 3 && !this.type) {
                          this.wizardError = 'Silakan pilih tipe booking terlebih dahulu.';
                          return;
                      }
                      if (this.step === 3 && this.type === 'private' && !this.canPrivate) {
                          this.wizardError = this.privateReason || 'Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia.';
                          return;
                      }
                      if (this.step === 3 && this.type === 'per_orang' && !this.canPerPerson) {
                          this.wizardError = this.perPersonReason || 'Kursi pada sesi ini sudah penuh.';
                          return;
                      }
                      if (this.step === 3 && this.type === 'per_orang' && !this.paxIsValid) {
                          this.wizardError = 'Jumlah orang melebihi kursi yang tersedia.';
                          return;
                      }
                      this.wizardError = '';
                      this.step = this.step === 2 ? 3 : (this.step === 3 ? (this.hasSnacks ? 4 : 5) : 5);
                      this.persist();
                      this.scrollToTop();
                  },
                  previousStep() {
                      this.step = this.step === 5 ? (this.hasSnacks ? 4 : 3) : Math.max(2, this.step - 1);
                      this.persist();
                      this.scrollToTop();
                  },
                  scrollToTop() {
                      const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
                      window.scrollTo({ top: 0, behavior });
                  },
                  skipSnacks() { this.nextStep() },
                  persist() {
                      if (!this.storageAvailable) return;
                      try {
                          sessionStorage.setItem('synemax:booking-wizard', JSON.stringify({
                              schema: 3,
                              sessionId: this.sessionId,
                              step: this.step,
                              filmId: this.filmId,
                              type: this.type,
                              pax: this.pax,
                              selectedSeats: this.selectedSeats,
                              paymentMethod: this.paymentMethod,
                              cart: this.cart,
                              activeCategory: this.activeCategory
                          }));
                      } catch {
                          this.storageAvailable = false;
                      }
                  },
                  init() {
                      try {
                          const saved = JSON.parse(sessionStorage.getItem('synemax:booking-wizard') || 'null');
                          if (saved && String(saved.sessionId) === String(this.sessionId)) {
                              this.step = [2, 3, 4, 5].includes(Number(saved.step)) ? Number(saved.step) : 2;
                              if (@js(! $errors->any())) {
                                  this.filmId = saved.filmId || this.filmId;
                                  if (saved.schema === 3 && ['per_orang', 'private'].includes(saved.type)) {
                                      this.type = saved.type;
                                  } else {
                                      this.type = @js(old('booking_type', ''));
                                  }
                                  this.pax = Math.min(this.remaining, Math.max(1, Number(saved.pax || 1)));
                                  this.selectedSeats = saved.selectedSeats || [];
                                  this.paymentMethod = saved.paymentMethod || this.paymentMethod;
                                  this.cart = saved.cart || this.cart;
                                  this.activeCategory = saved.activeCategory || this.activeCategory;
                              }
                          }
                      } catch {
                          this.storageAvailable = false;
                      }
                      if (this.selectedSeats.length === 0) {
                          this.syncSeatsWithPax();
                      }
                      this.$watch('filmId', () => this.persist());
                      this.$watch('pax', () => { this.syncSeatsWithPax(); this.persist(); });
                      this.$watch('type', () => { this.syncSeatsWithPax(); this.persist(); });
                  }
              }">

            @csrf
            <input type="hidden" name="wizard_step" value="5">
            <input type="hidden" name="booking_type" :value="type">
            <input type="hidden" name="pax" :value="type === 'private' ? 6 : pax">
            <input type="hidden" name="film_id" :value="filmId">
            <input type="hidden" name="payment_method" :value="paymentMethod">
            <input type="hidden" name="promo_code" :value="promoApplied || ''">

            {{-- Hidden Inputs for Snacks --}}
            <template x-for="(line, itemId) in cart" :key="`post-snack-${itemId}`">
                <div>
                    <input type="hidden" :name="`snacks[${itemId}][qty]`" :value="line.qty">
                    <input type="hidden" :name="`snacks[${itemId}][note]`" :value="line.note || ''">
                </div>
            </template>

            {{-- Wizard Progress Bar ala Bioskop Modern --}}
            <div class="mt-4">
                <div class="mb-2 flex items-center justify-between text-xs text-text-muted">
                    <span class="font-bold text-amber-400">Pemesanan Tiket Studio</span>
                    <span class="font-tabular font-medium" x-text="'Langkah ' + step + ' dari 5'">Langkah 2 dari 5</span>
                </div>
                <ol class="grid grid-cols-5 gap-1.5 text-center" aria-label="Tahapan pemesanan">
                    @php
                        $wizardSteps = [
                            1 => 'Jadwal',
                            2 => 'Film',
                            3 => 'Tipe',
                            4 => 'Jajan',
                            5 => 'Ringkasan'
                        ];
                    @endphp
                    @foreach ($wizardSteps as $stepNum => $stepLabel)
                        <li class="rounded-xl border p-2 transition-all"
                            :class="step === {{ $stepNum }}
                                ? 'border-amber-400 bg-amber-500/10 text-amber-400 font-bold shadow-[0_0_12px_rgba(212,175,55,0.2)]'
                                : (step > {{ $stepNum }}
                                    ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400 font-medium'
                                    : 'border-border bg-surface text-text-muted')">
                            <span class="block font-tabular text-[10px] font-black">{{ $stepNum }}</span>
                            <span class="block truncate text-[10px]">{{ $stepLabel }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Alert Wizard Error --}}
            <p x-show="wizardError" x-cloak role="alert"
               class="mt-4 rounded-xl border border-danger/40 bg-danger-surface p-3 text-xs font-bold text-danger-text"
               x-text="wizardError"></p>

            {{-- ==================== LANGKAH 2: PILIH FILM ==================== --}}
            <section x-show="step === 2" x-cloak class="mt-6 space-y-4">
                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 class="text-lg font-black text-text">Pilih film tontonan</h2>
                        <span class="text-xs text-text-muted">Langkah 2 dari 5</span>
                    </div>
                    <p class="mt-0.5 text-xs text-text-muted">Pilih satu judul film yang ingin diputar pada sesi ini.</p>

                    @if ($session->film_id)
                        <div class="mt-4 rounded-2xl border border-amber-400/40 bg-amber-500/10 p-5">
                            <span class="rounded-md bg-amber-400 text-black px-2 py-0.5 text-[10px] font-black uppercase">Film Terpilih Sesi Ini</span>
                            <p class="mt-2 text-xl font-black text-text">{{ $session->film?->title ?? 'Film Terpilih' }}</p>
                            <p class="mt-1 text-xs text-text-muted">
                                Sesi ini telah dijadwalkan untuk film tersebut. Anda akan memesan kursi untuk pemutaran film ini.
                            </p>
                        </div>
                    @else
                        {{-- Search & Filter Genre --}}
                        <div class="mt-4">
                            <input id="film-search"
                                   x-model="filmSearch"
                                   type="search"
                                   autocomplete="off"
                                   placeholder="Cari judul atau genre film..."
                                   class="min-h-11 w-full rounded-xl border border-border-input bg-surface px-4 text-sm text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400">

                            <div class="mt-3 flex gap-1.5 overflow-x-auto pb-1 scrollbar-none" aria-label="Filter genre film">
                                @php($genres = $films->pluck('genre')->unique()->values())
                                @foreach (['Semua', ...$genres->all()] as $genre)
                                    <button type="button"
                                            @click="genreFilter = @js($genre)"
                                            class="min-h-8 shrink-0 rounded-lg border px-3 text-xs font-bold transition"
                                            :class="genreFilter === @js($genre) ? 'border-amber-400 bg-amber-500 text-black' : 'border-border-input bg-surface text-text hover:border-amber-400'">
                                        {{ $genre }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Grid Pilihan Film --}}
                            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($films as $film)
                                    <article x-show="matchesFilm(@js($film->title), @js($film->genre))"
                                             class="flex flex-col justify-between rounded-xl border border-border bg-surface p-2.5 transition hover:border-amber-400/50">
                                        <button type="button"
                                                @click="filmDetail = @js(['id' => $film->id, 'title' => $film->title, 'genre' => $film->genre, 'duration' => $film->duration_minutes, 'rating' => $film->age_rating, 'synopsis' => $film->synopsis, 'trailer' => $film->trailer_url])"
                                                class="flex w-full flex-col text-left focus:outline-none">
                                            <div class="relative aspect-[2/3] w-full overflow-hidden rounded-lg bg-surface-2">
                                                @if ($film->poster_path)
                                                    <img src="{{ Storage::url($film->poster_path) }}"
                                                         alt="Poster {{ $film->title }}"
                                                         loading="lazy"
                                                         class="size-full object-cover">
                                                @else
                                                    <div class="flex size-full items-center justify-center p-2 text-center text-xs font-bold text-text-muted">
                                                        {{ $film->title }}
                                                    </div>
                                                @endif
                                                <span class="absolute right-1.5 top-1.5 rounded bg-black/80 px-1.5 py-0.5 text-[9px] font-black text-amber-400 border border-amber-400/30">
                                                    {{ $film->age_rating }}
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <h3 class="line-clamp-2 text-xs font-bold text-text sm:text-sm">{{ $film->title }}</h3>
                                                <p class="mt-0.5 line-clamp-1 text-[11px] text-text-muted">{{ $film->genre }}, {{ $film->duration_minutes }}m</p>
                                            </div>
                                        </button>
                                        <div class="mt-3 pt-2 border-t border-border">
                                            <button type="button"
                                                    @click="chooseFilm({{ $film->id }})"
                                                    class="inline-flex min-h-10 w-full items-center justify-center rounded-lg px-2 text-xs font-bold transition"
                                                    :class="filmId === '{{ $film->id }}' ? 'btn-gold-cinema' : 'border border-border-input bg-surface text-text hover:border-amber-400'">
                                                <span x-text="filmId === '{{ $film->id }}' ? 'Film dipilih' : 'Pilih film'">Pilih film</span>
                                            </button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <p x-cloak x-show="filmOptions.length > 0 && filteredFilmCount === 0 && (filmSearch.trim() !== '' || genreFilter !== 'Semua')"
                               class="mt-4 rounded-xl border border-border bg-surface p-4 text-center text-xs text-text-muted">
                                Film yang cocok belum ada. Coba kata kunci lain atau pilih kategori Semua.
                            </p>
                        </div>
                    @endif
                </div>
            </section>

            {{-- ==================== LANGKAH 3: DENAH KURSI & TIPE NONTON ==================== --}}
            <section x-show="step === 3" x-cloak class="mt-6 space-y-5">
                <div>
                    <h2 class="text-lg font-black text-text">Pilih Kursi & Tipe Nonton</h2>
                    <p class="mt-0.5 text-xs text-text-muted">Klik kursi langsung pada denah studio mini atau tentukan sewa studio privat.</p>
                </div>

                {{-- DENAH KURSI MINI BIOSKOP DENGAN LAYAR MELENGKUNG --}}
                <div class="rounded-2xl border border-white/10 bg-gradient-to-b from-[#111726] to-[#0A0E18] p-5 shadow-inner">
                    {{-- Proyektor Screen Melengkung --}}
                    <div class="mx-auto max-w-sm text-center mb-6">
                        <div class="curved-cinema-screen flex items-center justify-center">
                            <span class="text-[10px] font-black tracking-[0.35em] text-amber-300 uppercase">LAYAR / SCREEN</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Arah Pandangan Proyektor</p>
                    </div>

                    {{-- Layout 6 Kursi Mini Studio (2 Baris x 3 Kolom) --}}
                    <div class="mx-auto max-w-xs space-y-3.5">
                        {{-- Baris A --}}
                        <div class="flex items-center justify-center gap-3">
                            <span class="w-4 text-xs font-bold text-slate-400">A</span>
                            <template x-for="(seat, idx) in seats.slice(0, 3)" :key="seat.id">
                                <button type="button"
                                        @click="toggleSeat(seat.id, idx)"
                                        :disabled="isSeatOccupied(idx)"
                                        class="seat-item flex size-14 flex-col items-center justify-center rounded-xl font-tabular text-xs font-black shadow-md transition"
                                        :class="isSeatOccupied(idx)
                                            ? 'seat-occupied'
                                            : (selectedSeats.includes(seat.id) ? 'seat-selected' : 'seat-available')">
                                    <svg class="size-4 mb-0.5 opacity-80" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M4 18v3h3v-3h10v3h3v-3h1a1 1 0 001-1v-5a3 3 0 00-3-3h-1V6a3 3 0 00-3-3H9a3 3 0 00-3 3v3H5a3 3 0 00-3 3v5a1 1 0 001 1h1z" />
                                    </svg>
                                    <span x-text="seat.label"></span>
                                </button>
                            </template>
                        </div>

                        {{-- Baris B --}}
                        <div class="flex items-center justify-center gap-3">
                            <span class="w-4 text-xs font-bold text-slate-400">B</span>
                            <template x-for="(seat, idx) in seats.slice(3, 6)" :key="seat.id">
                                <button type="button"
                                        @click="toggleSeat(seat.id, idx + 3)"
                                        :disabled="isSeatOccupied(idx + 3)"
                                        class="seat-item flex size-14 flex-col items-center justify-center rounded-xl font-tabular text-xs font-black shadow-md transition"
                                        :class="isSeatOccupied(idx + 3)
                                            ? 'seat-occupied'
                                            : (selectedSeats.includes(seat.id) ? 'seat-selected' : 'seat-available')">
                                    <svg class="size-4 mb-0.5 opacity-80" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M4 18v3h3v-3h10v3h3v-3h1a1 1 0 001-1v-5a3 3 0 00-3-3h-1V6a3 3 0 00-3-3H9a3 3 0 00-3 3v3H5a3 3 0 00-3 3v5a1 1 0 001 1h1z" />
                                    </svg>
                                    <span x-text="seat.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Legenda Denah Kursi --}}
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-4 border-t border-white/10 pt-3 text-xs text-slate-300">
                        <div class="flex items-center gap-1.5">
                            <span class="size-3.5 rounded border border-white/20 bg-slate-800"></span>
                            <span>Tersedia</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-3.5 rounded bg-amber-400"></span>
                            <span class="text-amber-300 font-bold">Dipilih</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-3.5 rounded bg-slate-900 border border-white/5 opacity-50"></span>
                            <span class="text-slate-500">Terisi / Booked</span>
                        </div>
                    </div>
                </div>

                {{-- Opsi Tipe Nonton (Per Orang vs Sewa Studio Privat) --}}
                <div class="grid gap-3 sm:grid-cols-2">
                    {{-- Opsi 1: Per Orang --}}
                    <label @click="selectPerPerson()"
                           class="cursor-pointer rounded-2xl border p-4 transition block relative"
                           :class="type === 'per_orang' ? 'border-amber-400 bg-amber-500/10 shadow-sm' : 'border-border bg-surface hover:border-amber-400/50'">
                        <input type="radio"
                               name="booking_type"
                               value="per_orang"
                               x-model="type"
                               :disabled="!canPerPerson"
                               @if (! $canPerPerson) disabled @endif
                               class="sr-only">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-500">Opsi 1</span>
                                <h3 class="mt-0.5 text-base font-black text-text">Per orang</h3>
                                <p class="mt-1 text-xs text-text-muted">Pilih kursi sesuai jumlah teman nonton.</p>
                            </div>
                            <span class="font-tabular text-sm font-black text-amber-400">
                                Rp {{ number_format($unitPrice, 0, ',', '.') }}/kursi
                            </span>
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-border pt-3">
                            <span class="text-xs text-text-muted">Jumlah Kursi:</span>
                            <span class="font-tabular font-black text-text" x-text="`${pax} Kursi (${selectedSeats.join(', ') || '-'})`"></span>
                        </div>
                    </label>

                    {{-- Opsi 2: Sewa Studio Privat (Memenuhi assertSee('Sewa studio privat')) --}}
                    <label @click="selectPrivate()"
                           class="cursor-pointer rounded-2xl border p-4 transition block relative"
                           :class="type === 'private' ? 'border-amber-400 bg-amber-500/10 shadow-sm' : (!canPrivate ? 'opacity-40 cursor-not-allowed border-border bg-surface' : 'border-border bg-surface hover:border-amber-400/50')">
                        <input type="radio"
                               name="booking_type"
                               value="private"
                               x-model="type"
                               :disabled="!canPrivate"
                               :aria-disabled="!canPrivate ? 'true' : 'false'"
                               aria-describedby="private-desc"
                               @if (! $canPrivate) disabled @endif
                               class="sr-only">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-500">Opsi VIP</span>
                                <h3 class="mt-0.5 text-base font-black text-text">Sewa studio privat</h3>
                                <p class="mt-1 text-xs text-text-muted">Kunci seluruh 6 kursi khusus rombongan Anda.</p>
                            </div>
                            <span class="font-tabular text-sm font-black text-amber-400">
                                Rp {{ number_format($privatePrice, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="mt-3 border-t border-border pt-3">
                            <span x-show="canPrivate" class="text-xs font-bold text-emerald-400">✓ Tersedia untuk disewa privat</span>
                            <span id="private-desc" x-show="!canPrivate" class="text-xs text-danger-text" x-text="privateReason">
                                {{ $privateDisabledReason }}
                            </span>
                        </div>
                    </label>
                </div>

                {{-- FITUR VOUCHER & KODE PROMO --}}
                <div class="rounded-2xl border border-border bg-surface p-4 shadow-sm">
                    <label for="promo-input" class="text-xs font-bold uppercase tracking-wider text-amber-500">Punya Voucher / Kode Promo?</label>
                    <div class="mt-2 flex gap-2">
                        <input id="promo-input"
                               type="text"
                               x-model="promoCode"
                               placeholder="Masukkan kode promo (misal: XXIPREMIERE)"
                               class="min-h-11 flex-1 rounded-xl border border-border-input bg-surface-2 px-3 text-xs uppercase font-bold text-text placeholder:normal-case placeholder:font-normal focus:border-amber-400 focus:outline-none">
                        <button type="button"
                                @click="applyPromo()"
                                class="btn-gold-cinema inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-xs font-bold">
                            Terapkan
                        </button>
                    </div>

                    {{-- Pesan Status Promo --}}
                    <div x-show="promoMessage" class="mt-2.5 text-xs font-bold"
                         :class="isPromoValid ? 'text-emerald-400' : 'text-danger-text'">
                        <span x-text="promoMessage"></span>
                        <button x-show="isPromoValid" type="button" @click="removePromo()" class="ml-2 underline text-text-muted hover:text-danger-text font-normal">
                            Hapus promo
                        </button>
                    </div>

                    <p class="mt-2 text-[11px] text-text-muted">
                        Tips kode promo: Gunakan <span class="font-bold text-amber-400">XXIPREMIERE</span> (Diskon 10%) atau <span class="font-bold text-amber-400">SYNEMAX5K</span> (Diskon Rp 5.000).
                    </p>
                </div>
            </section>

            {{-- ==================== LANGKAH 4: MENU JAJAN (F&B CONCESSION) ==================== --}}
            @if ($snacksEnabled)
                <section x-show="step === 4" x-cloak class="mt-6 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-500">Concession Stand</span>
                            <h2 class="mt-0.5 text-lg font-black text-text">Mau pesan jajan?</h2>
                            <p class="mt-0.5 text-xs text-text-muted">Popcorn hangat, minuman dingin, dan snack lezat diantar ke kursi studio.</p>
                        </div>
                        <button type="button"
                                @click="skipSnacks()"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-bold text-text-muted hover:border-amber-400 hover:text-amber-400">
                            Lewati jajan
                        </button>
                    </div>

                    {{-- Tab Kategori --}}
                    <div class="flex gap-2 overflow-x-auto border-b border-border pb-2 scrollbar-none" role="tablist">
                        @foreach ($snackCategories as $category)
                            <button type="button"
                                    @click="activeCategory = {{ $category['id'] }}"
                                    class="min-h-9 shrink-0 rounded-xl px-4 text-xs font-bold transition"
                                    :class="activeCategory === {{ $category['id'] }} ? 'bg-amber-500 text-black shadow-xs' : 'border border-border-input bg-surface text-text hover:border-amber-400'">
                                {{ $category['name'] }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Menu Items --}}
                    <div class="space-y-3">
                        @foreach ($snackCategories as $category)
                            <div x-show="activeCategory === {{ $category['id'] }}" class="grid gap-3 sm:grid-cols-2">
                                @forelse ($category['items'] as $item)
                                    <div class="flex flex-col justify-between rounded-2xl border border-border bg-surface p-4 shadow-sm transition hover:border-amber-400/40">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <h3 class="text-sm font-black text-text">{{ $item['name'] }}</h3>
                                                <p class="mt-1 font-tabular text-sm font-black text-amber-400">
                                                    Rp {{ number_format($item['price'], 0, ',', '.') }}
                                                </p>
                                            </div>
                                            <span class="flex size-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400">
                                                🍿
                                            </span>
                                        </div>

                                        <div class="mt-4 flex items-center justify-between border-t border-border pt-3">
                                            <button type="button"
                                                    @click="openNote({{ $item['id'] }})"
                                                    class="text-xs text-text-muted hover:text-amber-400 underline">
                                                <span x-text="(cart[{{ $item['id'] }}] || {}).note ? 'Ubah Catatan' : '+ Catatan'"></span>
                                            </button>

                                            {{-- Stepper Plus Minus --}}
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                        @click="changeQty({{ $item['id'] }}, -1)"
                                                        :disabled="qty({{ $item['id'] }}) <= 0"
                                                        class="flex size-8 items-center justify-center rounded-lg border border-border-input bg-surface-2 text-sm font-bold text-text hover:border-amber-400 disabled:opacity-30">
                                                    −
                                                </button>
                                                <span class="w-6 text-center font-tabular text-sm font-black text-text" x-text="qty({{ $item['id'] }})"></span>
                                                <button type="button"
                                                        @click="changeQty({{ $item['id'] }}, 1)"
                                                        :disabled="qty({{ $item['id'] }}) >= snackMaxQty"
                                                        class="flex size-8 items-center justify-center rounded-lg btn-gold-cinema text-sm font-bold disabled:opacity-30">
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="col-span-full p-4 text-center text-xs text-text-muted">Kategori ini belum memiliki menu aktif.</p>
                                @endforelse
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ==================== LANGKAH 5: DATA DIRI & PEMBAYARAN ==================== --}}
            <section x-show="step === 5" x-cloak class="mt-6 space-y-5">
                <div>
                    <h2 class="text-lg font-black text-text">Data Diri & Pembayaran</h2>
                    <p class="mt-0.5 text-xs text-text-muted">E-Tiket resmi akan langsung dikirimkan ke nomor WhatsApp Anda.</p>
                </div>

                {{-- Form Identitas --}}
                <div class="space-y-4 rounded-2xl border border-border bg-surface p-5 shadow-sm">
                    <div>
                        <label for="customer_name" class="block text-xs font-bold uppercase tracking-wider text-text">Nama Lengkap</label>
                        <input id="customer_name"
                               type="text"
                               name="customer_name"
                               value="{{ old('customer_name') }}"
                               required
                               placeholder="Nama pemesan tiket..."
                               class="mt-1.5 min-h-11 w-full rounded-xl border border-border-input bg-surface px-4 text-sm text-text focus:border-amber-400 focus:outline-none">
                    </div>

                    <div>
                        <label for="whatsapp" class="block text-xs font-bold uppercase tracking-wider text-text">Nomor WhatsApp Aktif</label>
                        <input id="whatsapp"
                               type="tel"
                               name="whatsapp"
                               value="{{ old('whatsapp') }}"
                               required
                               placeholder="Contoh: 08123456789"
                               class="mt-1.5 min-h-11 w-full rounded-xl border border-border-input bg-surface px-4 text-sm text-text focus:border-amber-400 focus:outline-none">
                        <p class="mt-1 text-[11px] text-text-muted">Pastikan nomor aktif untuk notifikasi dan verifikasi tiket.</p>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-text">Catatan Pemesanan (Opsional)</label>
                        <textarea id="notes"
                                  name="notes"
                                  rows="2"
                                  placeholder="Permintaan khusus kursi atau catatan lainnya..."
                                  class="mt-1.5 w-full rounded-xl border border-border-input bg-surface p-3 text-xs text-text focus:border-amber-400 focus:outline-none">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Pilihan Cara Bayar --}}
                <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-500">Pilih Metode Pembayaran</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        @foreach ($paymentMethods as $method)
                            @php($label = ['di_tempat' => 'Bayar di Lokasi', 'qris' => 'QRIS Instan', 'transfer' => 'Transfer Bank'][$method])
                            <label class="cursor-pointer rounded-xl border p-3.5 transition flex items-center gap-2.5"
                                   :class="paymentMethod === '{{ $method }}' ? 'border-amber-400 bg-amber-500/10' : 'border-border bg-surface hover:border-amber-400/50'">
                                <input type="radio" value="{{ $method }}" x-model="paymentMethod" class="peer sr-only">
                                <span class="flex size-4 shrink-0 items-center justify-center rounded-full border-2"
                                      :class="paymentMethod === '{{ $method }}' ? 'border-amber-400' : 'border-border-input'">
                                    <span class="size-2 rounded-full bg-amber-400" x-show="paymentMethod === '{{ $method }}'"></span>
                                </span>
                                <span class="text-xs font-bold text-text">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Ringkasan Tagihan Final --}}
                <div class="rounded-2xl border border-white/10 bg-gradient-to-br from-[#111726] to-[#0A0E18] p-5 shadow-md">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400">Rincian Pembayaran</h3>
                    <dl class="mt-3 space-y-2 text-xs divide-y divide-white/5">
                        <div class="flex justify-between py-1.5 text-slate-300">
                            <span>Tiket (<span x-text="type === 'private' ? 'Sewa Studio Privat' : pax + ' Kursi'"></span>)</span>
                            <span class="font-tabular font-bold text-white" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(ticketTotal)}`"></span>
                        </div>
                        <div x-show="snackCount > 0" class="flex justify-between py-1.5 text-slate-300">
                            <span>Pesanan Jajan (<span x-text="snackCount"></span> item)</span>
                            <span class="font-tabular font-bold text-white" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(snackTotal)}`"></span>
                        </div>
                        <div x-show="promoDiscount > 0" class="flex justify-between py-1.5 text-emerald-400 font-bold">
                            <span>Diskon Voucher (<span x-text="promoApplied"></span>)</span>
                            <span class="font-tabular" x-text="`-Rp ${new Intl.NumberFormat('id-ID').format(promoDiscount)}`"></span>
                        </div>
                        <div class="flex justify-between pt-3 text-base font-black text-white">
                            <span>Total Tagihan</span>
                            <span class="font-tabular text-amber-400" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(grandTotal)}`"></span>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- FIXED LIVE-UPDATE ACTION BAR DI BAWAH (MOBILE & DESKTOP) --}}
            <div class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-surface/95 backdrop-blur-xl p-3.5 shadow-2xl"
                 style="padding-bottom: max(env(safe-area-inset-bottom), .85rem)">
                <div class="mx-auto flex max-w-xl items-center justify-between gap-3">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-text-muted">Total Pembayaran</span>
                        <div class="flex items-baseline gap-2">
                            <span class="font-tabular text-lg sm:text-xl font-black text-amber-400"
                                  x-text="`Rp ${new Intl.NumberFormat('id-ID').format(grandTotal)}`"></span>
                            <span x-show="promoDiscount > 0" class="rounded bg-emerald-500/20 px-1.5 py-0.5 text-[10px] font-bold text-emerald-400">
                                Hemat Rp <span x-text="new Intl.NumberFormat('id-ID').format(promoDiscount)"></span>
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button"
                                x-show="step > 2"
                                @click="previousStep()"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-border-input bg-surface px-4 text-xs font-bold text-text hover:border-amber-400">
                            Kembali
                        </button>

                        <button type="button"
                                x-show="step === 2"
                                @click="nextStep()"
                                :disabled="!filmId"
                                class="btn-gold-cinema inline-flex min-h-11 items-center justify-center rounded-xl px-5 text-xs font-black shadow-md disabled:opacity-40 disabled:cursor-not-allowed">
                            Lanjut ke Kursi
                        </button>

                        <button type="button"
                                x-show="step === 3"
                                @click="nextStep()"
                                :disabled="!type || (type === 'private' && !canPrivate) || (type === 'per_orang' && (!canPerPerson || !paxIsValid))"
                                class="btn-gold-cinema inline-flex min-h-11 items-center justify-center rounded-xl px-5 text-xs font-black shadow-md disabled:opacity-40 disabled:cursor-not-allowed">
                            <span x-text="hasSnacks ? 'Lanjut ke Menu Jajan' : 'Lanjut ke Pembayaran'"></span>
                        </button>

                        <button type="button"
                                x-show="step === 4"
                                @click="nextStep()"
                                class="btn-gold-cinema inline-flex min-h-11 items-center justify-center rounded-xl px-5 text-xs font-black shadow-md">
                            Lanjut ke Pembayaran
                        </button>

                        <button type="submit"
                                x-show="step === 5"
                                :disabled="isSubmitting"
                                class="btn-gold-cinema inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-6 text-xs font-black shadow-lg disabled:opacity-50">
                            <span x-show="!isSubmitting" x-text="paymentMethod === 'di_tempat' ? 'Konfirmasi Booking' : 'Booking & Bayar Sekarang'"></span>
                            <span x-show="isSubmitting" x-cloak>Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modal Catatan Jajan --}}
            <div x-show="noteItemId !== null" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                 @click.self="noteItemId = null">
                <div class="w-full max-w-sm rounded-2xl border border-border bg-surface p-5 shadow-2xl">
                    <h3 class="text-sm font-black text-text">Catatan Khusus Jajanan</h3>
                    <p class="mt-1 text-xs text-text-muted">Contoh: es sedikit, pedas dipisah, atau tanpa gula.</p>
                    <textarea x-model="noteValue"
                              rows="3"
                              placeholder="Tulis catatan Anda..."
                              class="mt-3 w-full rounded-xl border border-border-input bg-surface-2 p-3 text-xs text-text focus:border-amber-400 focus:outline-none"></textarea>
                    <div class="mt-4 flex gap-2">
                        <button type="button" @click="noteItemId = null"
                                class="flex-1 rounded-xl border border-border p-2.5 text-xs font-bold text-text">
                            Batal
                        </button>
                        <button type="button" @click="saveNote()"
                                class="btn-gold-cinema flex-1 rounded-xl p-2.5 text-xs font-bold">
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
