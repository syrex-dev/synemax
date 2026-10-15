@extends('layouts.public')

@section('title', 'Pesan Tiket Studio — SynemaX')
@section('meta')
    <meta name="description" content="Pilih film, jumlah penonton, dan jajan untuk sesi studio mini SynemaX.">
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
    @endphp

    <div class="mx-auto max-w-xl px-4 pb-36 pt-4 sm:px-6 sm:pb-40 sm:pt-6">
        {{-- Header & Slot Jam Terpilih (Sobekan Karcis & Kuning Karcis, Hanya di Sini) --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
            <div>
                <a href="{{ route('schedule.date', ['date' => $session->session_date]) }}"
                   class="inline-flex min-h-11 items-center gap-1.5 text-xs font-bold text-primary hover:underline">
                    Ganti jadwal
                </a>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-text sm:text-3xl">Amankan kursimu</h1>
                <p class="mt-0.5 text-xs text-text-muted">{{ $session->startsAt()->locale('id')->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="ticket-paper-card inline-flex items-center rounded-[var(--radius-control)] bg-[#FFC62E] px-3.5 py-2 font-tabular text-sm font-bold text-[#16201F] shadow-xs">
                <span>{{ substr((string) $session->start_time, 0, 5) }} WIB</span>
            </div>
        </div>

        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-4 text-xs text-danger-text">
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
                              this.canPrivate = Boolean(data.can_private);
                              this.canPerPerson = Boolean(data.can_per_orang);
                              this.privateReason = data.private_disabled_reason || 'Sewa Private tidak tersedia.';
                              this.perPersonReason = data.per_orang_disabled_reason || 'Kursi pada sesi ini sudah penuh.';

                              if (this.type === 'private' && !this.canPrivate) {
                                  this.type = '';
                                  this.wizardError = this.privateReason;
                                  this.persist();
                                  if (this.step > 3) {
                                      this.step = 3;
                                  }
                              }

                              if (this.type === 'per_orang' && !this.canPerPerson) {
                                  this.type = '';
                                  this.wizardError = this.perPersonReason;
                                  this.persist();
                                  if (this.step > 3) {
                                      this.step = 3;
                                  }
                              } else if (this.type === 'per_orang' && Number(this.pax) > this.remaining) {
                                  this.pax = Math.max(1, this.remaining);
                                  this.persist();
                              }
                          }
                      } catch (err) {
                          // Toleransi offline / fetch gagal
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
                                  this.paymentMethod = saved.paymentMethod || this.paymentMethod;
                                  this.cart = saved.cart || this.cart;
                                  this.activeCategory = saved.activeCategory || this.activeCategory;
                              }
                          }
                      } catch {
                          this.storageAvailable = false;
                      }
                      this.$watch('filmId', () => this.persist());
                      this.$watch('type', () => this.persist());
                      this.$watch('pax', () => this.persist());
                      this.$watch('paymentMethod', () => this.persist());
                      this.$watch('cart', () => this.persist());
                      this.$watch('activeCategory', () => this.persist());
                      this.$watch('step', (val) => {
                          this.centerActiveStep();
                          if (val === 3) this.refreshAvailability();
                      });
                      this.refreshAvailability();
                      this.centerActiveStep();
                  },
                  centerActiveStep() {
                      const progress = this.$el.querySelector('ol[x-ref=progress]');
                      if (!progress) return;
                      requestAnimationFrame(() => {
                          if (progress.scrollWidth <= progress.clientWidth) return;
                          const active = progress.querySelector('[aria-current=step]');
                          if (!active) return;
                          const activeBounds = active.getBoundingClientRect();
                          const progressBounds = progress.getBoundingClientRect();
                          const offset = activeBounds.left - progressBounds.left
                              - (progress.clientWidth - activeBounds.width) / 2;
                          progress.scrollLeft = progress.scrollLeft + offset;
                      });
                  }
              }"
              @submit="if (isSubmitting) { $event.preventDefault() } else { isSubmitting = true }">
            @csrf
            <input type="hidden" name="wizard_step" :value="step">
            <input type="hidden" name="film_id" :value="filmId">
            <input type="hidden" name="booking_type" :value="type">
            <input type="hidden" name="pax" :value="pax" :disabled="type !== 'per_orang'">
            <input type="hidden" name="payment_method" :value="paymentMethod">

            {{-- Penunjuk Langkah Konsisten: Urutan Nyata 1 s.d. 5 --}}
            <div class="mt-5">
                <p class="text-xs font-bold text-text-muted"
                   x-text="'Langkah ' + step + ' dari 5: ' + (step === 2 ? 'Pilih film' : (step === 3 ? 'Pilih tipe nonton' : (step === 4 ? 'Pilih jajan' : 'Ringkasan')))">
                    Langkah 2 dari 5: Pilih film
                </p>

                <ol x-ref="progress" aria-label="Progres booking" class="mt-2.5 grid grid-cols-5 gap-1.5 text-center text-xs">
                    @foreach (['Jadwal', 'Film', 'Tipe', 'Jajan', 'Ringkasan'] as $index => $label)
                        @php($stepNumber = $index + 1)
                        <li class="rounded-[var(--radius-control)] border px-1.5 py-1.5 text-center transition-all"
                            :class="step === {{ $stepNumber }}
                                ? 'border-primary bg-primary text-white font-bold'
                                : (step > {{ $stepNumber }}
                                    ? 'border-border-input bg-surface-2 text-text font-medium'
                                    : 'border-border bg-surface text-text-muted')"
                            x-bind:aria-current="step === {{ $stepNumber }} ? 'step' : null">
                            <span class="block text-[11px] font-extrabold">{{ $stepNumber }}</span>
                            <span class="block truncate text-[10px]">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Alert Kesalahan Wizard --}}
            <p x-show="wizardError" x-cloak role="alert"
               class="mt-4 rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-3 text-xs font-bold text-danger-text"
               x-text="wizardError"></p>
            <p x-show="!storageAvailable" x-cloak role="status" class="mt-2 text-xs text-text-muted">
                Penyimpanan browser sementara tidak aktif. Booking tetap dapat dilanjutkan sampai selesai.
            </p>

            {{-- LANGKAH 2: PILIH FILM --}}
            <section x-show="step === 2" x-cloak class="mt-6 space-y-4">
                <div>
                    <h2 class="text-lg font-bold text-text">Pilih film tontonan</h2>
                    <p class="mt-0.5 text-xs text-text-muted">Pilih satu film yang ingin ditonton pada sesi ini.</p>

                    @if ($session->film_id)
                        <div class="mt-4 rounded-[var(--radius-control)] border border-primary/40 bg-surface-2 p-4">
                            <p class="text-xs font-bold text-primary">Film sudah ditetapkan</p>
                            <p class="mt-1 text-base font-extrabold text-text">{{ $session->film?->title ?? 'Film terpilih' }}</p>
                            <p class="mt-1 text-xs text-text-muted">
                                Sesi ini sudah memiliki film pilihan dari pemesan sebelumnya. Booking Anda akan bergabung menonton film ini.
                            </p>
                        </div>
                    @else
                        <div class="mt-4">
                            <label for="film-search" class="sr-only">Cari judul atau genre film</label>
                            <input id="film-search"
                                   x-model="filmSearch"
                                   type="search"
                                   autocomplete="off"
                                   placeholder="Cari judul atau genre film..."
                                   class="min-h-11 w-full rounded-[var(--radius-control)] border border-border-input bg-surface px-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">

                            <div class="mt-2.5 flex gap-1.5 overflow-x-auto pb-1" aria-label="Pilihan filter genre film">
                                @php($genres = $films->pluck('genre')->unique()->values())
                                @foreach (['Semua', ...$genres->all()] as $genre)
                                    <button type="button"
                                            @click="genreFilter = @js($genre)"
                                            :aria-pressed="genreFilter === @js($genre)"
                                            class="min-h-9 shrink-0 rounded-[var(--radius-control)] border px-3 text-xs transition-colors"
                                            :class="genreFilter === @js($genre) ? 'border-primary bg-primary text-white font-bold' : 'border-border-input bg-surface text-text hover:border-primary'">
                                        {{ $genre }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($films as $film)
                                    <article x-show="matchesFilm(@js($film->title), @js($film->genre))"
                                             class="flex flex-col justify-between rounded-[var(--radius-control)] border border-border bg-surface p-2.5 transition hover:border-primary">
                                        <button type="button"
                                                @click="filmDetail = @js(['id' => $film->id, 'title' => $film->title, 'genre' => $film->genre, 'duration' => $film->duration_minutes, 'rating' => $film->age_rating, 'synopsis' => $film->synopsis, 'trailer' => $film->trailer_url])"
                                                class="flex w-full flex-col text-left focus:outline-none">
                                            <div class="aspect-[2/3] w-full overflow-hidden rounded-[var(--radius-control)] bg-surface-2"
                                                 @if ($film->poster_path) x-data="{ posterLoaded: false }" x-init="$nextTick(() => posterLoaded = $refs.poster.complete)" @endif>
                                                @if ($film->poster_path)
                                                    <img x-ref="poster"
                                                         x-on:load="posterLoaded = true"
                                                         x-on:error="posterLoaded = true"
                                                         src="{{ Storage::url($film->poster_path) }}"
                                                         alt="Poster {{ $film->title }}"
                                                         loading="lazy"
                                                         decoding="async"
                                                         class="size-full object-cover">
                                                @else
                                                    <div class="flex size-full items-center justify-center p-2 text-center text-xs font-bold text-text-muted">
                                                        {{ $film->title }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="mt-2">
                                                <h3 class="line-clamp-2 text-xs font-bold text-text sm:text-sm">{{ $film->title }}</h3>
                                                <p class="mt-0.5 line-clamp-1 text-[11px] text-text-muted">{{ $film->genre }}, {{ $film->duration_minutes }} mnt</p>
                                                <span class="mt-1 inline-block rounded-[var(--radius-control)] bg-surface-2 px-1.5 py-0.5 text-[10px] font-bold text-text-muted">Usia {{ $film->age_rating }}</span>
                                            </div>
                                        </button>
                                        <div class="mt-3 pt-2 border-t border-border">
                                            <button type="button"
                                                    @click="chooseFilm({{ $film->id }})"
                                                    class="inline-flex min-h-11 w-full items-center justify-center rounded-[var(--radius-control)] px-2 text-xs font-bold transition"
                                                    :class="filmId === '{{ $film->id }}' ? 'bg-primary text-white' : 'border border-border-input bg-surface text-text hover:border-primary'">
                                                <span x-text="filmId === '{{ $film->id }}' ? 'Film dipilih' : 'Pilih film'">Pilih film</span>
                                            </button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <p x-cloak x-show="filmOptions.length > 0 && filteredFilmCount === 0 && (filmSearch.trim() !== '' || genreFilter !== 'Semua')"
                               class="mt-4 rounded-[var(--radius-control)] border border-border bg-surface p-4 text-center text-xs text-text-muted">
                                Film yang cocok belum ada. Coba kata kunci lain atau pilih kategori Semua.
                            </p>
                        </div>
                    @endif
                    @error('film_id')<p role="alert" class="mt-2 text-xs font-semibold text-danger-text">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- LANGKAH 3: PILIH TIPE NONTON (Radio Garis 2px Biru Sablon, Titik Radio, Tanpa Sobekan, Tanpa Kuning) --}}
            <section x-show="step === 3" x-cloak class="mt-6 space-y-4">
                <fieldset>
                    <legend class="text-base font-bold text-text">Pilihan tipe nonton</legend>
                    <p class="mt-0.5 text-xs text-text-muted">Tentukan opsi tiket per kursi atau sewa seluruh 6 kursi studio.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        {{-- Opsi 1: Tiket Per Orang --}}
                        <label class="radio-choice-card relative flex flex-col justify-between p-4 cursor-pointer"
                               :class="{
                                   'is-selected': type === 'per_orang',
                                   'opacity-50 cursor-not-allowed': !canPerPerson
                               }">
                            <div class="flex items-start gap-3">
                                <input type="radio"
                                       name="booking_type_choice"
                                       value="per_orang"
                                       x-model="type"
                                       :disabled="!canPerPerson"
                                       :aria-disabled="!canPerPerson ? 'true' : 'false'"
                                       aria-describedby="per-orang-desc"
                                       class="peer sr-only">
                                <span aria-hidden="true"
                                      class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                      :class="type === 'per_orang' ? 'border-primary' : 'border-border-input'">
                                    <span class="size-2.5 rounded-full bg-primary" x-show="type === 'per_orang'"></span>
                                </span>
                                <div class="flex-1">
                                    <span class="block text-sm font-bold text-text">Per orang</span>
                                    <span class="mt-1 block text-xs text-text-muted leading-relaxed">Berbagi studio dengan penonton lain untuk film yang sama.</span>
                                    <span class="mt-3 block font-tabular text-sm font-extrabold text-text">
                                        Rp {{ number_format($unitPrice, 0, ',', '.') }} <span class="text-xs font-normal text-text-muted">/ kursi</span>
                                    </span>
                                    <span id="per-orang-desc"
                                          x-show="!canPerPerson"
                                          class="mt-2 block text-xs font-semibold text-danger-text"
                                          x-text="perPersonReason">
                                        {{ $perPersonDisabledReason }}
                                    </span>
                                </div>
                            </div>
                        </label>

                        {{-- Opsi 2: Sewa Studio Privat --}}
                        <label class="radio-choice-card relative flex flex-col justify-between p-4 cursor-pointer"
                               :class="{
                                   'is-selected': type === 'private',
                                   'opacity-50 cursor-not-allowed': !canPrivate
                               }">
                            <div class="flex items-start gap-3">
                                <input type="radio"
                                       name="booking_type_choice"
                                       value="private"
                                       x-model="type"
                                       :disabled="!canPrivate"
                                       :aria-disabled="!canPrivate ? 'true' : 'false'"
                                       aria-describedby="private-desc"
                                       class="peer sr-only">
                                <span aria-hidden="true"
                                      class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                      :class="type === 'private' ? 'border-primary' : 'border-border-input'">
                                    <span class="size-2.5 rounded-full bg-primary" x-show="type === 'private'"></span>
                                </span>
                                <div class="flex-1">
                                    <span class="block text-sm font-bold text-text">Sewa private</span>
                                    <span class="mt-1 block text-xs text-text-muted leading-relaxed">Studio khusus rombongan Anda, 6 kursi penuh tanpa penonton lain.</span>
                                    <span class="mt-3 block font-tabular text-sm font-extrabold text-text">
                                        Rp {{ number_format($privatePrice, 0, ',', '.') }} <span class="text-xs font-normal text-text-muted">/ paket</span>
                                    </span>
                                    <span id="private-desc"
                                          x-show="!canPrivate"
                                          class="mt-2 block text-xs font-semibold text-danger-text"
                                          x-text="privateReason">
                                        {{ $privateDisabledReason }}
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>
                    @error('booking_type')<p role="alert" class="mt-2 text-xs font-semibold text-danger-text">{{ $message }}</p>@enderror
                </fieldset>

                {{-- Stepper Jumlah Kursi (Jika Per Orang Terpilih) --}}
                <div x-show="type === 'per_orang'" x-cloak class="mt-4 rounded-[var(--radius-control)] border border-border bg-surface p-4">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-text">Jumlah penonton</h3>
                            <p class="mt-0.5 text-xs text-text-muted">Tersedia <span class="font-bold font-tabular text-text" x-text="remaining"></span> dari 6 kursi.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button"
                                    @click="pax = Math.max(1, Number(pax) - 1)"
                                    :disabled="Number(pax) <= 1"
                                    aria-label="Kurangi jumlah orang"
                                    class="flex size-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface text-lg font-bold text-text transition hover:border-primary disabled:opacity-30 disabled:cursor-not-allowed">
                                −
                            </button>
                            <span class="min-w-8 text-center font-tabular text-xl font-extrabold text-text" x-text="pax">1</span>
                            <button type="button"
                                    @click="pax = Math.min(remaining, 6, Number(pax) + 1)"
                                    :disabled="Number(pax) >= remaining || Number(pax) >= 6"
                                    aria-label="Tambah jumlah orang"
                                    class="flex size-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface text-lg font-bold text-text transition hover:border-primary disabled:opacity-30 disabled:cursor-not-allowed">
                                +
                            </button>
                        </div>
                    </div>
                    <p class="mt-2 text-xs font-semibold text-danger-text" x-show="type === 'per_orang' && canPerPerson && !paxIsValid">Pilih jumlah orang yang valid.</p>
                    @error('pax')<p role="alert" class="mt-2 text-xs font-semibold text-danger-text">{{ $message }}</p>@enderror
                </div>

                {{-- Info Sewa Privat Terpilih --}}
                <div x-show="type === 'private'" x-cloak class="mt-4 rounded-[var(--radius-control)] border border-border bg-surface-2 p-4">
                    <p class="text-sm font-bold text-text">Studio privat terkunci untuk 6 kursi rombongan Anda.</p>
                    <p class="mt-1 text-xs text-text-muted">Tarif flat: <span class="font-bold font-tabular text-text">Rp {{ number_format($privatePrice, 0, ',', '.') }}</span></p>
                </div>
            </section>

            {{-- LANGKAH 4: JAJAN (OPSIONAL) --}}
            @if ($snacksEnabled)
                <section x-show="step === 4" x-cloak class="mt-6 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-text">Mau pesan jajan?</h2>
                            <p class="mt-0.5 text-xs text-text-muted">Opsional, bisa dilewati. Jajanan studio diantar sebelum pemutaran film dimulai.</p>
                        </div>
                        <button type="button"
                                @click="skipSnacks()"
                                class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface px-3 text-xs font-bold text-text transition hover:border-primary">
                            Lewati jajan
                        </button>
                    </div>

                    {{-- Tab Kategori Jajan --}}
                    <div class="flex gap-1.5 overflow-x-auto border-b border-border pb-2" role="tablist" aria-label="Kategori jajanan">
                        @foreach ($snackCategories as $category)
                            <button id="snack-tab-{{ $category['id'] }}"
                                    type="button"
                                    role="tab"
                                    aria-controls="snack-panel-{{ $category['id'] }}"
                                    @click="activeCategory = {{ $category['id'] }}"
                                    :aria-selected="activeCategory === {{ $category['id'] }}"
                                    class="min-h-9 shrink-0 rounded-[var(--radius-control)] border px-3 text-xs transition"
                                    :class="activeCategory === {{ $category['id'] }} ? 'border-primary bg-primary text-white font-bold' : 'border-border-input bg-surface text-text hover:border-primary'">
                                {{ $category['name'] }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Daftar Item Tiap Kategori --}}
                    @foreach ($snackCategories as $category)
                        <div id="snack-panel-{{ $category['id'] }}"
                             role="tabpanel"
                             aria-labelledby="snack-tab-{{ $category['id'] }}"
                             x-show="activeCategory === {{ $category['id'] }}"
                             class="space-y-2.5">
                            @foreach ($category['items'] as $item)
                                <article class="flex items-center gap-3 rounded-[var(--radius-control)] border border-border bg-surface p-3"
                                         :class="@js($item['available'] ? '' : 'opacity-50 grayscale')">
                                    @if ($item['photo'])
                                        <img src="{{ $item['photo'] }}" alt="" loading="lazy" decoding="async" class="size-14 shrink-0 rounded-[var(--radius-control)] object-cover">
                                    @else
                                        <div class="flex size-14 shrink-0 items-center justify-center rounded-[var(--radius-control)] bg-surface-2 text-xs font-bold text-text-muted">
                                            Jajan
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-xs font-bold text-text sm:text-sm">{{ $item['name'] }}</h3>
                                        <p class="mt-0.5 font-tabular text-xs font-extrabold text-text">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                        @unless ($item['available'])
                                            <p class="mt-0.5 text-[11px] font-bold text-danger-text">Habis: {{ $item['unavailable_reason'] }}</p>
                                        @endunless
                                        <button type="button"
                                                x-show="qty({{ $item['id'] }}) > 0"
                                                @click="openNote({{ $item['id'] }})"
                                                class="mt-1 inline-flex min-h-8 items-center text-[11px] font-bold text-primary underline underline-offset-2">
                                            <span x-text="(cart[{{ $item['id'] }}] || {}).note ? 'Ubah catatan' : 'Tambah catatan'">Tambah catatan</span>
                                        </button>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <button type="button"
                                                @click="changeQty({{ $item['id'] }}, -1)"
                                                :disabled="qty({{ $item['id'] }}) === 0"
                                                aria-label="Kurangi {{ $item['name'] }}"
                                                class="flex size-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface text-base font-bold text-text transition hover:border-primary disabled:opacity-30 disabled:cursor-not-allowed">
                                            −
                                        </button>
                                        <span class="min-w-5 text-center font-tabular text-sm font-extrabold text-text" x-text="qty({{ $item['id'] }})">0</span>
                                        <button type="button"
                                                @click="changeQty({{ $item['id'] }}, 1)"
                                                :disabled="{{ $item['available'] ? 'false' : 'true' }} || qty({{ $item['id'] }}) >= snackMaxQty"
                                                aria-label="Tambah {{ $item['name'] }}"
                                                class="flex size-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface text-base font-bold text-text transition hover:border-primary disabled:opacity-30 disabled:cursor-not-allowed">
                                            +
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endforeach

                    <template x-for="(line, itemId) in cart" :key="itemId">
                        <div hidden>
                            <input type="hidden" :name="'snacks[' + itemId + '][qty]'" :value="line.qty">
                            <input type="hidden" :name="'snacks[' + itemId + '][note]'" :value="line.note || ''">
                        </div>
                    </template>

                    <div x-show="snackCount === 0" class="rounded-[var(--radius-control)] border border-border bg-surface-2 p-3 text-xs text-text-muted">
                        Belum ada jajan yang dipilih. Anda dapat memilih jajan sekarang atau langsung melanjutkan ke data diri.
                    </div>
                </section>
            @endif

            {{-- LANGKAH 5: RINGKASAN & DATA DIRI --}}
            <section x-show="step === 5" x-cloak class="mt-6 space-y-5">
                {{-- Rincian Biaya --}}
                <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4">
                    <h2 class="text-base font-bold text-text">Ringkasan pemesanan</h2>
                    <div class="mt-3 space-y-2.5 text-xs">
                        <div class="flex justify-between gap-4">
                            <span class="text-text-muted" x-text="type === 'private' ? 'Sewa studio privat (6 kursi)' : `Tiket bioskop (${pax} orang)`"></span>
                            <span class="font-tabular font-extrabold text-text" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(ticketTotal)}`"></span>
                        </div>
                        <template x-for="(line, itemId) in cart" :key="`final-${itemId}`">
                            <div x-show="Number(line.qty) > 0" class="flex justify-between gap-4 text-text-muted">
                                <span x-text="`${(findItem(itemId) || {}).name || 'Jajan'} × ${line.qty}${line.note ? ' (' + line.note + ')' : ''}`"></span>
                                <span class="font-tabular font-bold text-text" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(((findItem(itemId) || {}).price || 0) * Number(line.qty))}`"></span>
                            </div>
                        </template>
                        <div x-show="snackTotal > 0" class="flex justify-between gap-4 border-t border-border pt-2 text-text-muted">
                            <span>Subtotal jajan</span>
                            <span class="font-tabular font-bold text-text" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(snackTotal)}`"></span>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-border pt-3 text-sm font-extrabold text-text">
                            <span>Total pembayaran</span>
                            <span class="font-tabular text-base text-primary" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(total)}`"></span>
                        </div>
                    </div>
                </div>

                {{-- Formulir Data Pemesan --}}
                <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4">
                    <h2 class="text-base font-bold text-text">Data pemesan</h2>
                    <p class="mt-0.5 text-xs text-text-muted">Nama dan WhatsApp digunakan untuk verifikasi dan tiket.</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label for="customer_name" class="block text-xs font-bold text-text">Nama lengkap</label>
                            <input id="customer_name"
                                   name="customer_name"
                                   value="{{ old('customer_name') }}"
                                   required
                                   maxlength="120"
                                   autocomplete="name"
                                   placeholder="Contoh: Budi Santoso"
                                   class="mt-1 min-h-11 w-full rounded-[var(--radius-control)] border border-border-input bg-surface px-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                   aria-describedby="customer-name-error">
                            @error('customer_name')
                                <p id="customer-name-error" role="alert" class="mt-1 text-xs font-semibold text-danger-text">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-xs font-bold text-text">Nomor WhatsApp</label>
                            <input id="whatsapp"
                                   name="whatsapp"
                                   type="tel"
                                   value="{{ old('whatsapp') }}"
                                   required
                                   inputmode="numeric"
                                   autocomplete="tel"
                                   placeholder="081234567890"
                                   class="mt-1 min-h-11 w-full rounded-[var(--radius-control)] border border-border-input bg-surface px-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                   aria-describedby="whatsapp-help @error('whatsapp') whatsapp-error @enderror">
                            <p id="whatsapp-help" class="mt-1 text-[11px] text-text-muted">Gunakan awalan 08 atau +62 aktif.</p>
                            @error('whatsapp')
                                <p id="whatsapp-error" role="alert" class="mt-1 text-xs font-semibold text-danger-text">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Cara Bayar --}}
                <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4">
                    <h2 class="text-base font-bold text-text">Metode pembayaran</h2>
                    <div class="mt-3 grid gap-2.5 sm:grid-cols-3">
                        @foreach ($paymentMethods as $method)
                            @php($paymentLabel = ['di_tempat' => 'Bayar di tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'][$method])
                            <label class="radio-choice-card relative flex min-h-12 cursor-pointer items-center gap-3 p-3 text-xs font-bold text-text transition"
                                   :class="{'is-selected': paymentMethod === '{{ $method }}'}">
                                <input type="radio" value="{{ $method }}" x-model="paymentMethod" class="peer sr-only">
                                <span aria-hidden="true"
                                      class="flex size-4 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                      :class="paymentMethod === '{{ $method }}' ? 'border-primary' : 'border-border-input'">
                                    <span class="size-2 rounded-full bg-primary" x-show="paymentMethod === '{{ $method }}'"></span>
                                </span>
                                <span>{{ $paymentLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method')<p role="alert" class="mt-2 text-xs font-semibold text-danger-text">{{ $message }}</p>@enderror

                    <div x-show="paymentMethod === 'di_tempat'" class="mt-3 rounded-[var(--radius-control)] bg-surface-2 p-3 text-xs text-text-muted leading-relaxed">
                        Bayar tunai di lokasi studio sebelum sesi penayangan dimulai.
                    </div>

                    <div x-show="paymentMethod === 'qris' || paymentMethod === 'transfer'" class="mt-3 rounded-[var(--radius-control)] bg-surface-2 p-3 text-xs text-text-muted leading-relaxed">
                        <p class="font-bold text-text mb-1" x-text="paymentMethod === 'qris' ? 'Pembayaran QRIS' : 'Pembayaran Transfer Bank / E-Wallet'"></p>
                        Setelah menekan tombol booking di bawah, Anda akan diarahkan ke halaman pembayaran dengan batas waktu {{ $paymentTimeLimit }} menit.
                    </div>
                </div>
            </section>

            {{-- Modal Detail Sinopsis Film --}}
            <div x-show="filmDetail" x-cloak
                 class="fixed inset-0 z-50 flex items-end justify-center bg-overlay p-0 sm:items-center sm:p-4"
                 @click.self="filmDetail = null"
                 @keydown.escape.window="filmDetail = null">
                <section role="dialog" aria-modal="true" aria-labelledby="film-detail-title"
                         class="max-h-[85dvh] w-full max-w-lg overflow-y-auto rounded-[var(--radius-control)] border border-border bg-surface p-5 shadow-token">
                    <div class="flex items-start justify-between gap-3 border-b border-border pb-3">
                        <div>
                            <h2 id="film-detail-title" class="text-xl font-extrabold text-text" x-text="filmDetail?.title"></h2>
                            <p class="mt-0.5 text-xs text-text-muted">
                                <span x-text="filmDetail?.genre"></span>, <span x-text="filmDetail?.duration"></span> menit, usia <span x-text="filmDetail?.rating"></span>
                            </p>
                        </div>
                        <button type="button" @click="filmDetail = null" aria-label="Tutup detail film"
                                class="flex size-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input text-base text-text hover:border-primary">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-4 text-xs leading-relaxed text-text" x-text="filmDetail?.synopsis"></p>
                    <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                        <button type="button" @click="chooseFilm(filmDetail.id)"
                                class="inline-flex min-h-11 flex-1 items-center justify-center rounded-[var(--radius-control)] bg-primary px-4 text-xs font-bold text-white hover:opacity-95">
                            Pilih film ini
                        </button>
                        <a x-show="filmDetail?.trailer" :href="filmDetail?.trailer" target="_blank" rel="noopener noreferrer"
                           class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input px-4 text-xs font-bold text-text hover:border-primary">
                            Buka trailer
                        </a>
                    </div>
                </section>
            </div>

            {{-- Modal Catatan Jajan --}}
            <div x-show="noteItemId !== null" x-cloak
                 class="fixed inset-0 z-50 flex items-end justify-center bg-overlay p-0 sm:items-center sm:p-4"
                 @click.self="noteItemId = null"
                 @keydown.escape.window="noteItemId = null">
                <section role="dialog" aria-modal="true" aria-labelledby="note-title"
                         class="w-full max-w-md rounded-[var(--radius-control)] border border-border bg-surface p-5 shadow-token">
                    <h2 id="note-title" class="text-base font-bold text-text">Catatan pesanan jajan</h2>
                    <p class="mt-0.5 text-xs text-text-muted">Contoh: tidak pakai es, atau tanpa gula.</p>
                    <textarea id="snack-note"
                              x-model="noteValue"
                              maxlength="250"
                              rows="3"
                              class="mt-3 w-full rounded-[var(--radius-control)] border border-border-input bg-surface p-3 text-xs text-text focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
                    <div class="mt-3 flex gap-2">
                        <button type="button" @click="noteItemId = null"
                                class="inline-flex min-h-11 flex-1 items-center justify-center rounded-[var(--radius-control)] border border-border-input text-xs font-bold text-text hover:border-primary">
                            Batal
                        </button>
                        <button type="button" @click="saveNote()"
                                class="inline-flex min-h-11 flex-1 items-center justify-center rounded-[var(--radius-control)] bg-primary text-xs font-bold text-white hover:opacity-95">
                            Simpan catatan
                        </button>
                    </div>
                </section>
            </div>

            {{-- Fixed Action Bar di Bawah Layar (Mobile & Desktop) --}}
            <div class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-surface/95 pt-3 backdrop-blur"
                 style="padding-bottom: max(env(safe-area-inset-bottom), .75rem)">
                <div class="mx-auto w-full max-w-xl px-4 sm:px-6">
                    {{-- Mini Keranjang Jajan di Langkah 4 --}}
                    <div x-show="step === 4 && snackCount > 0" class="mb-2.5">
                        <button type="button"
                                @click="basketOpen = !basketOpen"
                                class="flex min-h-9 w-full items-center justify-between rounded-[var(--radius-control)] border border-border-input bg-surface-2 px-3 text-xs font-bold text-text"
                                :aria-expanded="basketOpen">
                            <span x-text="`${snackCount} item jajan, ${basketOpen ? 'tutup rincian' : 'lihat rincian'}`"></span>
                            <span class="font-tabular" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(snackTotal)}`"></span>
                        </button>
                        <div x-show="basketOpen" x-cloak class="mt-1.5 max-h-36 overflow-y-auto rounded-[var(--radius-control)] border border-border bg-surface p-2 text-xs">
                            <template x-for="(line, itemId) in cart" :key="`basket-${itemId}`">
                                <div x-show="Number(line.qty) > 0" class="flex justify-between gap-3 border-b border-border py-1 text-text">
                                    <span x-text="`${(findItem(itemId) || {}).name || 'Jajan'} × ${line.qty}${line.note ? ' (' + line.note + ')' : ''}`"></span>
                                    <span class="font-tabular" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(((findItem(itemId) || {}).price || 0) * Number(line.qty))}`"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Total Sementara & Tombol Aksi --}}
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="text-xs text-text-muted">Total perkiraan</span>
                        <span class="font-tabular text-sm font-extrabold text-primary" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(total)}`">Rp 0</span>
                    </div>

                    <div class="flex gap-2">
                        <button type="button"
                                x-show="step > 2"
                                @click="previousStep()"
                                class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface px-4 text-xs font-bold text-text transition hover:border-primary">
                            Kembali
                        </button>

                        <button type="button"
                                x-show="step === 2"
                                @click="nextStep()"
                                :disabled="!filmId"
                                class="inline-flex min-h-11 flex-1 items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-45">
                            Lanjut ke tipe nonton
                        </button>

                        <div class="flex-1" x-show="step === 3">
                            <button type="button"
                                    @click="nextStep()"
                                    :disabled="!type || (type === 'private' && !canPrivate) || (type === 'per_orang' && (!canPerPerson || !paxIsValid))"
                                    class="inline-flex min-h-11 w-full items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-45"
                                    x-text="hasSnacks ? 'Lanjut ke pilihan jajan' : 'Lanjut ke ringkasan'">
                                Lanjut ke pilihan jajan
                            </button>
                            <p class="mt-1 text-right text-xs text-text-muted" x-show="step === 3 && type === 'per_orang' && canPerPerson && !paxIsValid">Pilih jumlah orang yang valid.</p>
                        </div>

                        <button type="button"
                                x-show="step === 4"
                                @click="nextStep()"
                                class="inline-flex min-h-11 flex-1 items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs transition hover:opacity-95">
                            Lanjut ke ringkasan
                        </button>

                        <button type="button"
                                x-bind:type="step === 5 ? 'submit' : 'button'"
                                x-show="step === 5"
                                :disabled="isSubmitting"
                                class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs transition hover:opacity-95 disabled:cursor-wait disabled:opacity-60">
                            <span x-show="!isSubmitting" x-text="paymentMethod === 'di_tempat' ? 'Kirim booking' : 'Booking & bayar'">Kirim booking</span>
                            <span x-show="isSubmitting" x-cloak role="status">Mengirim…</span>
                        </button>
                    </div>

                    {{-- Pesan Panduan Ringkas Bawah Tombol --}}
                    <p class="mt-1 text-right text-[11px] text-text-muted" x-show="step === 2 && !filmId">Pilih salah satu film untuk melanjutkan.</p>
                    <p class="mt-1 text-right text-[11px] text-text-muted" x-show="step === 3 && !type">Pilih opsi per orang atau sewa privat.</p>
                    <p class="mt-1 text-right text-[11px] text-text-muted" x-show="step === 3 && type === 'private' && !canPrivate" x-text="privateReason"></p>
                    <p class="mt-1 text-right text-[11px] text-text-muted" x-show="step === 3 && type === 'per_orang' && !canPerPerson" x-text="perPersonReason"></p>
                    <p class="mt-1 text-right text-[11px] text-text-muted" x-show="step === 5">Pastikan nomor WhatsApp aktif untuk pengiriman tiket.</p>
                </div>
            </div>
        </form>
    </div>
@endsection
