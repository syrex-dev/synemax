@extends('layouts.public')

@section('title', 'Booking sesi Home Cinema')
@section('meta')
    <meta name="description" content="Pilih film, jumlah penonton, dan jajan untuk sesi Home Cinema.">
@endsection

@section('content')
    @php($privateAvailable = $occupiedSeats === 0 && $session->seatCapacity() === \App\Models\Session::MAX_CAPACITY)
    <section class="mx-auto max-w-3xl px-4 pb-80 pt-6 sm:px-6 sm:pb-80 sm:pt-9">
        <a href="{{ route('schedule.date', ['date' => $session->session_date]) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-accent hover:underline">← Ganti jadwal</a>
        <div class="mt-3">
            <h1 class="mt-2 text-3xl font-black tracking-tight text-text sm:text-4xl">Amankan kursimu</h1>
            <p class="mt-2 text-base text-text-muted">{{ $session->startsAt()->locale('id')->translatedFormat('l, d F Y') }} · {{ substr((string) $session->start_time, 0, 5) }} WIB</p>
        </div>

        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-xl border border-danger/30 bg-danger/10 p-4 text-base text-danger">
                <p class="font-bold">Yuk, cek lagi data booking-nya:</p>
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
                  privateAvailable: @js($privateAvailable),
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
                          this.wizardError = 'Pilih film dulu sebelum lanjut.';
                          return;
                      }
                      if (this.step === 3 && !this.type) {
                          this.wizardError = 'Pilih tipe booking dulu.';
                          return;
                      }
                      if (this.step === 3 && this.type === 'private' && !this.privateAvailable) {
                          this.wizardError = 'Sewa Private hanya tersedia jika sesi masih kosong.';
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
                      this.$watch('step', () => this.centerActiveStep());
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

            <p class="mt-5 text-sm font-bold uppercase tracking-[0.16em] text-accent">Langkah <span x-text="step">2</span> dari 5</p>
            <ol x-ref="progress" aria-label="Progres booking" class="mt-3 flex min-w-0 scroll-smooth gap-2 overflow-x-auto pb-2 text-center text-xs sm:grid sm:grid-cols-5 sm:overflow-visible sm:pb-0">
                @foreach (['Jadwal', 'Film', 'Tipe', 'Jajan', 'Ringkasan'] as $index => $label)
                    @php($stepNumber = $index + 1)
                    <li class="min-w-24 flex-1 rounded-xl border px-2 py-2 text-center transition-all sm:min-w-0"
                        :class="step === {{ $stepNumber }}
                            ? 'border-accent bg-accent text-accent-contrast shadow-xs'
                            : (step > {{ $stepNumber }}
                                ? 'border-success/60 bg-success-surface/50 text-success'
                                : 'border-border/60 bg-surface/60 text-text-muted/60')"
                        x-bind:aria-current="step === {{ $stepNumber }} ? 'step' : null">
                        <span class="block text-sm font-bold" x-text="step > {{ $stepNumber }} ? '✓' : '{{ $stepNumber }}'">{{ $stepNumber }}</span>
                        <span class="block truncate font-medium">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
            <p x-show="wizardError" x-cloak role="alert" class="mt-4 rounded-xl border border-danger/30 bg-danger/10 p-3 text-base font-semibold text-danger" x-text="wizardError"></p>
            <p x-show="!storageAvailable" x-cloak role="status" class="mt-3 text-sm text-text-muted">Browser ini tidak menyimpan progres sementara. Booking tetap bisa dilanjutkan.</p>

            <section x-show="step === 2" x-cloak class="mt-6 space-y-4">
                <div class="rounded-2xl border border-border bg-surface p-4 sm:p-6">
                    <h2 class="text-xl font-bold text-text">Pilih film</h2>
                    @if ($session->film_id)
                        <div class="mt-4 rounded-xl border border-accent/30 bg-accent/5 p-4">
                            <p class="text-sm font-bold text-accent">🔒 Film sesi sudah terkunci</p>
                            <p class="mt-2 text-lg font-bold text-text">{{ $session->film?->title ?? 'Film tidak tersedia' }}</p>
                            <p class="mt-1 text-sm text-text-muted">Booking ini akan bergabung ke film yang sama.</p>
                        </div>
                    @else
                        <label for="film-search" class="mt-4 block text-base font-semibold text-text">Cari film</label>
                        <input id="film-search" x-model="filmSearch" type="search" autocomplete="off" placeholder="Cari judul atau genre" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-4 text-base text-text placeholder:text-text-muted focus:border-accent focus:ring-accent">
                        <div class="mt-3 flex gap-2 overflow-x-auto pb-2" aria-label="Filter genre film">
                            @php($genres = $films->pluck('genre')->unique()->values())
                            @foreach (['Semua', ...$genres->all()] as $genre)
                                <button type="button" @click="genreFilter = @js($genre)" :aria-pressed="genreFilter === @js($genre)" class="min-h-11 shrink-0 rounded-full border border-border px-4 text-sm font-semibold text-text transition-colors" :class="genreFilter === @js($genre) ? 'border-accent bg-accent/10 font-bold text-accent' : 'bg-surface hover:bg-surface-2'">{{ $genre }}</button>
                            @endforeach
                        </div>
                        <div class="mt-4 grid grid-cols-2 items-stretch gap-3 sm:grid-cols-3">
                            @foreach ($films as $film)
                                <article x-show="matchesFilm(@js($film->title), @js($film->genre))" class="flex h-full min-w-0 flex-col overflow-hidden rounded-xl border border-border bg-surface shadow-xs transition-shadow hover:shadow-sm">
                                    <button type="button" @click="filmDetail = @js(['id' => $film->id, 'title' => $film->title, 'genre' => $film->genre, 'duration' => $film->duration_minutes, 'rating' => $film->age_rating, 'synopsis' => $film->synopsis, 'trailer' => $film->trailer_url])" class="flex w-full flex-1 flex-col text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent" aria-label="Lihat detail film {{ $film->title }}">
                                        <div class="relative aspect-[2/3] w-full shrink-0 overflow-hidden bg-surface-2" @if ($film->poster_path) x-data="{ posterLoaded: false }" x-init="$nextTick(() => posterLoaded = $refs.poster.complete)" @endif>
                                            @if ($film->poster_path)
                                                <x-skeleton x-show="!posterLoaded" class="absolute inset-0 rounded-none" />
                                                <img x-ref="poster" x-on:load="posterLoaded = true" x-on:error="posterLoaded = true" src="{{ Storage::url($film->poster_path) }}" alt="Poster {{ $film->title }}" width="500" height="750" loading="lazy" decoding="async" class="relative size-full object-cover">
                                            @else
                                                <div class="flex size-full flex-col items-center justify-center gap-2 bg-surface-2 p-3 text-center text-text">
                                                    <svg aria-hidden="true" class="size-8 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                                        <path d="m10 9 5 3-5 3z"></path>
                                                    </svg>
                                                    <span class="line-clamp-2 text-xs font-bold text-text sm:text-sm">{{ $film->title }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <span class="flex flex-1 flex-col p-3">
                                            <span class="line-clamp-2 min-h-10 text-sm font-bold text-text sm:min-h-12 sm:text-base">{{ $film->title }}</span>
                                            <span class="mt-1 block truncate whitespace-nowrap text-xs text-text-muted sm:text-sm" title="{{ $film->genre }} · {{ $film->duration_minutes }} menit">{{ $film->genre }} · {{ $film->duration_minutes }} menit</span>
                                            <span class="mt-2 inline-flex w-fit rounded-full bg-surface-2 px-2 py-0.5 text-xs font-bold text-text">Usia {{ $film->age_rating }}</span>
                                        </span>
                                    </button>
                                    <div class="mt-auto p-2 pt-0">
                                        <button type="button" @click="chooseFilm({{ $film->id }})" class="inline-flex h-11 min-h-11 w-full items-center justify-center rounded-lg border border-accent px-3 text-sm font-bold text-accent transition-colors" :class="filmId === '{{ $film->id }}' ? 'bg-accent text-accent-contrast' : 'bg-transparent hover:bg-accent/10'">
                                            <span x-text="filmId === '{{ $film->id }}' ? '✓ Dipilih' : 'Pilih film'"></span>
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <p x-cloak x-show="filmOptions.length > 0 && filteredFilmCount === 0 && (filmSearch.trim() !== '' || genreFilter !== 'Semua')" class="mt-4 rounded-xl border border-border bg-surface p-4 text-base text-text-muted">Film yang cocok belum ada. Coba kata kunci lain, ya.</p>
                    @endif
                    @error('film_id')<p role="alert" class="mt-3 text-sm font-semibold text-danger">{{ $message }}</p>@enderror
                </div>
            </section>

            <section x-show="step === 3" x-cloak class="mt-6 space-y-4">
                <fieldset class="rounded-2xl border border-border bg-surface p-4 sm:p-6">
                    <legend class="px-1 text-xl font-bold text-text">Pilih tipe booking</legend>
                    <p class="mt-2 text-base text-text-muted">Pilih cara booking yang paling cocok buat rombonganmu.</p>
                    <div class="mt-4 grid items-stretch gap-3 sm:grid-cols-2">
                        @php($perPersonAvailable = $session->remainingSeats() > 0)
                        <label class="group relative flex min-h-40 flex-col justify-between rounded-xl border border-border bg-bg p-4 transition-all duration-200 has-checked:border-accent has-checked:bg-accent/5 motion-reduce:transition-none {{ $perPersonAvailable ? 'cursor-pointer hover:border-accent/60' : 'cursor-not-allowed opacity-60' }} focus-within:ring-2 focus-within:ring-accent focus-within:ring-offset-2">
                            <div class="flex items-start gap-3">
                                <input type="radio" name="booking_type_choice" value="per_orang" x-model="type" @disabled(! $perPersonAvailable) class="peer sr-only">
                                <span aria-hidden="true" class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border-2 border-border bg-surface transition-colors peer-checked:border-accent peer-checked:bg-accent">
                                    <span class="size-2 rounded-full bg-accent-contrast" x-show="type === 'per_orang'"></span>
                                </span>
                                <div class="flex flex-1 flex-col">
                                    <span class="text-base font-bold text-text">Per orang</span>
                                    <span class="mt-1 text-sm text-text-muted">Rp {{ number_format($unitPrice, 0, ',', '.') }} tiap kursi. Berbagi studio dengan pelanggan lain pada film yang sama.</span>
                                    @unless ($perPersonAvailable)
                                        <span class="mt-2 text-sm font-semibold text-danger">Kursi pada sesi ini sudah penuh.</span>
                                    @endunless
                                </div>
                            </div>
                        </label>
                        <label class="group relative flex min-h-40 flex-col justify-between rounded-xl border border-border bg-bg p-4 transition-all duration-200 has-checked:border-accent has-checked:bg-accent/5 motion-reduce:transition-none {{ $privateAvailable ? 'cursor-pointer hover:border-accent/60' : 'cursor-not-allowed opacity-60' }} focus-within:ring-2 focus-within:ring-accent focus-within:ring-offset-2">
                            <div class="flex items-start gap-3">
                                <input type="radio" name="booking_type_choice" value="private" x-model="type" @disabled(! $privateAvailable) class="peer sr-only">
                                <span aria-hidden="true" class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border-2 border-border bg-surface transition-colors peer-checked:border-accent peer-checked:bg-accent">
                                    <span class="size-2 rounded-full bg-accent-contrast" x-show="type === 'private'"></span>
                                </span>
                                <div class="flex flex-1 flex-col">
                                    <span class="text-base font-bold text-text">Sewa Private</span>
                                    <span class="mt-1 text-sm text-text-muted">Studio khusus untuk rombongan, 6 kursi penuh, tanpa pelanggan lain.</span>
                                    <span class="mt-auto pt-3 text-sm font-bold text-accent">Rp {{ number_format($privatePrice, 0, ',', '.') }} · harga flat</span>
                                    @unless ($privateAvailable)
                                        <span class="mt-2 text-sm font-semibold text-danger">{{ $occupiedSeats > 0 ? 'Sudah ada pemesan lain di sesi ini.' : 'Kapasitas sesi tidak tersedia untuk private.' }}</span>
                                    @endunless
                                </div>
                            </div>
                        </label>
                    </div>
                    @error('booking_type')<p role="alert" class="mt-3 text-sm font-semibold text-danger">{{ $message }}</p>@enderror
                </fieldset>

                <div x-show="type === 'per_orang'" x-cloak class="wizard-choice-panel rounded-2xl border border-border bg-surface p-4 sm:p-6">
                    <h2 class="text-xl font-bold text-text">Berapa orang yang ikut?</h2>
                    <p class="mt-1 text-base text-text-muted">Masih ada <span class="font-bold text-accent" x-text="remaining"></span> dari 6 kursi.</p>
                    <div class="mt-5 flex items-center justify-between gap-4 rounded-xl bg-surface-2 p-4">
                        <button type="button" @click="pax = Math.max(1, Number(pax) - 1)" :disabled="Number(pax) <= 1" aria-label="Kurangi jumlah orang" class="grid size-12 place-items-center rounded-full border border-border bg-surface text-2xl text-text transition hover:bg-surface-2 disabled:cursor-not-allowed disabled:opacity-40">−</button>
                        <p class="text-center">
                            <span class="block text-4xl font-black text-text" x-text="pax"></span>
                            <span class="mt-1 block text-sm text-text-muted">orang</span>
                        </p>
                        <button type="button" @click="pax = Math.min(remaining, 6, Number(pax) + 1)" :disabled="Number(pax) >= remaining || Number(pax) >= 6" aria-label="Tambah jumlah orang" class="grid size-12 place-items-center rounded-full bg-accent text-2xl font-bold text-accent-contrast transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">+</button>
                    </div>
                    @error('pax')<p role="alert" class="mt-3 text-sm font-semibold text-danger">{{ $message }}</p>@enderror
                </div>

                <div x-show="type === 'private'" x-cloak class="wizard-choice-panel rounded-2xl border border-accent/30 bg-accent/5 p-4 sm:p-6">
                    <p class="text-lg font-bold text-text">Studio khusus untukmu, 6 kursi dipesan penuh.</p>
                    <p class="mt-1 text-sm text-text-muted">Harga flat: <span class="font-bold text-accent">Rp {{ number_format($privatePrice, 0, ',', '.') }}</span>.</p>
                </div>
            </section>

            @if ($snacksEnabled)
                <section x-show="step === 4" x-cloak class="mt-6 rounded-2xl border border-border bg-surface p-4 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.15em] text-accent">Opsional</p>
                            <h2 class="mt-1 text-xl font-bold text-text">Mau pesan jajan?</h2>
                            <p class="mt-1 text-sm text-text-muted">Bisa dilewati. Dibayar bareng tiket.</p>
                        </div>
                        <button type="button" @click="skipSnacks()" class="min-h-11 shrink-0 rounded-lg border border-border px-3 text-sm font-bold text-text transition-colors hover:bg-surface-2">Lewati</button>
                    </div>
                    <div class="sticky top-16 z-10 -mx-4 mt-4 flex gap-2 overflow-x-auto border-b border-border bg-bg/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6" role="tablist" aria-label="Kategori jajanan">
                        @foreach ($snackCategories as $category)
                            <button id="snack-tab-{{ $category['id'] }}" type="button" role="tab" aria-controls="snack-panel-{{ $category['id'] }}" @click="activeCategory = {{ $category['id'] }}" :aria-selected="activeCategory === {{ $category['id'] }}" class="min-h-11 shrink-0 rounded-full border border-border px-4 text-sm font-semibold text-text transition-colors" :class="activeCategory === {{ $category['id'] }} ? 'border-accent bg-accent/10 font-bold text-accent' : 'bg-surface hover:bg-surface-2'">{{ $category['name'] }}</button>
                        @endforeach
                    </div>
                    @foreach ($snackCategories as $category)
                        <div id="snack-panel-{{ $category['id'] }}" role="tabpanel" aria-labelledby="snack-tab-{{ $category['id'] }}" x-show="activeCategory === {{ $category['id'] }}" class="mt-3 space-y-2">
                            @foreach ($category['items'] as $item)
                                <article class="flex items-center gap-3 rounded-xl border border-border bg-bg p-3" :class="@js($item['available'] ? '' : 'opacity-60 grayscale')">
                                    @if ($item['photo'])
                                        <img src="{{ $item['photo'] }}" alt="" loading="lazy" decoding="async" width="56" height="56" class="size-14 shrink-0 rounded-lg object-cover">
                                    @else
                                        <div class="grid size-14 shrink-0 place-items-center rounded-lg bg-surface-2 text-xs text-text-muted">Jajan</div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-base font-semibold text-text">{{ $item['name'] }}</h3>
                                        <p class="mt-1 text-sm font-semibold text-accent">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                        @unless ($item['available'])
                                            <p class="mt-1 inline-flex items-center gap-1 text-sm font-bold text-danger"><span aria-hidden="true">×</span> {{ $item['unavailable_reason'] }}</p>
                                        @else
                                            <p class="mt-1 inline-flex items-center gap-1 text-sm font-semibold text-success"><span aria-hidden="true">✓</span> Tersedia</p>
                                        @endunless
                                        <button type="button" x-show="qty({{ $item['id'] }}) > 0" @click="openNote({{ $item['id'] }})" class="mt-1 min-h-10 text-sm font-semibold text-accent underline underline-offset-2">
                                            <span x-text="(cart[{{ $item['id'] }}] || {}).note ? 'Ubah catatan' : 'Tambah catatan'"></span>
                                        </button>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <button type="button" @click="changeQty({{ $item['id'] }}, -1)" :disabled="qty({{ $item['id'] }}) === 0" aria-label="Kurangi {{ $item['name'] }}" class="grid size-11 place-items-center rounded-full border border-border text-xl text-text transition hover:bg-surface-2 disabled:opacity-40">−</button>
                                        <span class="min-w-5 text-center text-base font-bold text-text" x-text="qty({{ $item['id'] }})"></span>
                                        <button type="button" @click="changeQty({{ $item['id'] }}, 1)" :disabled="{{ $item['available'] ? 'false' : 'true' }} || qty({{ $item['id'] }}) >= snackMaxQty" aria-label="Tambah {{ $item['name'] }}" class="grid size-11 place-items-center rounded-full bg-accent text-xl font-bold text-accent-contrast transition hover:opacity-90 disabled:opacity-40">+</button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endforeach
                    <template x-for="(line, itemId) in cart" :key="itemId">
                        <div hidden><input type="hidden" :name="'snacks[' + itemId + '][qty]'" :value="line.qty"><input type="hidden" :name="'snacks[' + itemId + '][note]'" :value="line.note || ''"></div>
                    </template>
                    <div x-show="snackCount === 0" class="mt-4 rounded-xl border border-border bg-surface-2 p-3 text-sm text-text-muted">Keranjang masih kosong. Pilih jajan kalau mau, atau lanjut tanpa jajan.</div>
                </section>
            @endif

            <section x-show="step === 5" x-cloak class="mt-6 space-y-4">
                <article class="rounded-2xl border border-accent/30 bg-accent/5 p-4 sm:p-6">
                    <p class="text-sm font-bold uppercase tracking-[0.15em] text-accent">Ringkasan pembayaran</p>
                    <div class="mt-4 space-y-3 text-base">
                        <p class="flex justify-between gap-4"><span class="text-text-muted" x-text="type === 'private' ? 'Sewa Private · 6 kursi' : `Tiket · ${pax} orang`"></span><span class="font-semibold text-text" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(ticketTotal)}`"></span></p>
                        <template x-for="(line, itemId) in cart" :key="`final-${itemId}`">
                            <p x-show="Number(line.qty) > 0" class="flex justify-between gap-4 text-sm"><span class="text-text-muted" x-text="`${(findItem(itemId) || {}).name || 'Jajan'} × ${line.qty}${line.note ? ' · ' + line.note : ''}`"></span><span class="text-text" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(((findItem(itemId) || {}).price || 0) * Number(line.qty))}`"></span></p>
                        </template>
                        <p x-show="snackTotal > 0" class="flex justify-between gap-4"><span class="text-text-muted">Subtotal jajan</span><span class="font-semibold text-text" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(snackTotal)}`"></span></p>
                        <p class="flex justify-between gap-4 border-t border-border pt-3 text-lg font-black"><span class="text-text">Total</span><span class="text-accent" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(total)}`"></span></p>
                    </div>
                </article>
                <div role="note" class="rounded-xl border border-warning/30 bg-warning/10 p-4 text-base leading-6 text-text">
                    <p class="font-bold text-warning">Catatan soal jajan</p>
                    <p class="mt-1">Setelah dikirim, perubahan jajan harus lewat admin. Nanti bisa hubungi admin lewat WhatsApp, ya.</p>
                </div>
                <section class="rounded-2xl border border-border bg-surface p-4 sm:p-6">
                    <h2 class="text-xl font-bold text-text">Data pemesan</h2>
                    <label for="customer_name" class="mt-4 block text-base font-semibold text-text">Nama</label>
                    <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required maxlength="120" autocomplete="name" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-base text-text placeholder:text-text-muted focus:border-accent focus:ring-accent" placeholder="Nama kamu">
                    @error('customer_name')<p role="alert" class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror

                    <label for="whatsapp" class="mt-4 block text-base font-semibold text-text">Nomor WhatsApp</label>
                    <input id="whatsapp" name="whatsapp" type="tel" value="{{ old('whatsapp') }}" required inputmode="numeric" autocomplete="tel" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-base text-text placeholder:text-text-muted focus:border-accent focus:ring-accent" placeholder="081234567890" aria-describedby="whatsapp-help">
                    <p id="whatsapp-help" class="mt-2 text-sm text-text-muted">Boleh pakai format 08xx atau +62.</p>
                    @error('whatsapp')<p role="alert" class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
                </section>
                <section class="rounded-2xl border border-border bg-surface p-4 sm:p-6">
                    <h2 class="text-xl font-bold text-text">Cara bayar</h2>
                    <div class="mt-3 grid gap-2 sm:grid-cols-3">
                        @foreach ($paymentMethods as $method)
                            @php($paymentLabel = ['di_tempat' => 'Bayar di tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'][$method])
                            <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-border bg-surface px-4 py-3 text-text transition-all hover:border-accent/60 has-checked:border-accent has-checked:bg-accent/5 focus-within:ring-2 focus-within:ring-accent focus-within:ring-offset-2">
                                <input type="radio" value="{{ $method }}" x-model="paymentMethod" class="peer sr-only">
                                <span aria-hidden="true" class="grid size-5 shrink-0 place-items-center rounded-full border-2 border-border bg-bg transition-colors peer-checked:border-accent peer-checked:bg-accent">
                                    <span class="size-2 rounded-full bg-accent-contrast" x-show="paymentMethod === '{{ $method }}'"></span>
                                </span>
                                <span class="text-base font-medium">{{ $paymentLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if ($paymentInstructions !== '')
                        <div x-show="paymentMethod !== 'di_tempat'" class="mt-4 rounded-xl border border-accent/25 bg-accent/5 p-4 text-base leading-6 text-text">
                            <p class="font-semibold">Petunjuk pembayaran</p>
                            <p class="mt-1 whitespace-pre-line">{{ $paymentInstructions }}</p>
                        </div>
                    @endif
                    @if (in_array('qris', $paymentMethods, true) || in_array('transfer', $paymentMethods, true))
                        <label for="payment_proof" class="mt-4 block text-base font-semibold text-text">Bukti pembayaran (opsional, JPG/PNG/WebP maks. 2 MB)</label>
                        <input id="payment_proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-base text-text file:mr-3 file:min-h-11 file:rounded-lg file:border-0 file:bg-surface-2 file:px-4 file:font-semibold file:text-text">
                        @error('payment_proof')<p role="alert" class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
                    @endif
                </section>
            </section>

            <div x-show="filmDetail" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-overlay p-0 sm:items-center sm:p-4" @click.self="filmDetail = null" @keydown.escape.window="filmDetail = null">
                <section role="dialog" aria-modal="true" aria-labelledby="film-detail-title" class="max-h-[85dvh] w-full max-w-lg overflow-y-auto rounded-t-3xl border border-border bg-surface p-5 shadow-token sm:rounded-3xl">
                    <div class="flex items-start justify-between gap-3">
                        <div><h2 id="film-detail-title" class="text-2xl font-black text-text" x-text="filmDetail?.title"></h2><p class="mt-1 text-sm text-text-muted"><span x-text="filmDetail?.genre"></span> · <span x-text="filmDetail?.duration"></span> menit · Usia <span x-text="filmDetail?.rating"></span></p></div>
                        <button type="button" @click="filmDetail = null" aria-label="Tutup detail film" class="grid size-11 place-items-center rounded-full border border-border text-xl text-text hover:bg-surface-2">×</button>
                    </div>
                    <p class="mt-4 text-base leading-7 text-text-muted" x-text="filmDetail?.synopsis"></p>
                    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                        <button type="button" @click="chooseFilm(filmDetail.id)" class="min-h-12 flex-1 rounded-xl bg-accent px-4 font-bold text-accent-contrast hover:opacity-90">Pilih film ini</button>
                        <a x-show="filmDetail?.trailer" :href="filmDetail?.trailer" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-border px-4 font-bold text-text hover:bg-surface-2">▶ Lihat trailer</a>
                    </div>
                </section>
            </div>

            <div x-show="noteItemId !== null" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-overlay p-0 sm:items-center sm:p-4" @click.self="noteItemId = null" @keydown.escape.window="noteItemId = null">
                <section role="dialog" aria-modal="true" aria-labelledby="note-title" class="w-full max-w-lg rounded-t-3xl border border-border bg-surface p-5 shadow-token sm:rounded-3xl">
                    <h2 id="note-title" class="text-xl font-bold text-text">Catatan jajan</h2>
                    <p class="mt-1 text-sm text-text-muted">Contoh: mie rebus pedas atau minuman tanpa es.</p>
                    <label for="snack-note" class="sr-only">Catatan untuk jajanan</label>
                    <textarea id="snack-note" x-model="noteValue" maxlength="250" rows="3" class="mt-4 w-full rounded-xl border border-border bg-bg p-3 text-base text-text focus:border-accent focus:ring-accent"></textarea>
                    <button type="button" @click="saveNote()" class="mt-4 min-h-12 w-full rounded-xl bg-accent font-bold text-accent-contrast hover:opacity-90">Simpan catatan</button>
                </section>
            </div>

            <div class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-surface/95 pt-3 shadow-token backdrop-blur" style="padding-bottom: max(env(safe-area-inset-bottom), .75rem)">
                <div class="mx-auto w-full max-w-3xl px-4 sm:px-6">
                    <div x-show="step === 4 && snackCount > 0" class="mb-3">
                        <button type="button" @click="basketOpen = !basketOpen" class="flex min-h-11 w-full items-center justify-between rounded-xl bg-surface-2 px-3 text-sm font-bold text-text" :aria-expanded="basketOpen">
                            <span x-text="`${snackCount} item jajan · ${basketOpen ? 'Tutup keranjang' : 'Lihat keranjang'}`"></span>
                            <span x-text="`Rp ${new Intl.NumberFormat('id-ID').format(snackTotal)}`"></span>
                        </button>
                        <div x-show="basketOpen" x-cloak class="max-h-40 overflow-y-auto rounded-xl bg-surface-2 px-3 text-sm">
                            <template x-for="(line, itemId) in cart" :key="`basket-${itemId}`">
                                <p x-show="Number(line.qty) > 0" class="flex justify-between gap-3 border-b border-border py-2 text-text"><span x-text="`${(findItem(itemId) || {}).name || 'Jajan'} × ${line.qty}${line.note ? ' · ' + line.note : ''}`"></span><span x-text="`Rp ${new Intl.NumberFormat('id-ID').format(((findItem(itemId) || {}).price || 0) * Number(line.qty))}`"></span></p>
                            </template>
                        </div>
                    </div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <span class="block text-xs text-text-muted">Total sementara</span>
                            <span class="block truncate text-base font-black text-accent" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(total)}`"></span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" x-show="step > 2" @click="previousStep()" class="min-h-12 flex-1 rounded-xl border border-border px-3 text-sm font-bold text-text transition-colors hover:bg-surface-2">Kembali</button>
                        <button type="button" x-show="step < 5" @click="nextStep()" :disabled="(step === 2 && !filmId) || (step === 3 && (!type || (type === 'private' && !privateAvailable) || (type === 'per_orang' && !paxIsValid)))" class="min-h-12 flex-1 rounded-xl bg-accent px-3 text-sm font-bold text-accent-contrast transition-opacity disabled:cursor-not-allowed disabled:opacity-45">Lanjut</button>
                        <button type="button" x-bind:type="step === 5 ? 'submit' : 'button'" x-show="step === 5" :disabled="isSubmitting" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-3 text-sm font-bold text-accent-contrast transition-opacity disabled:cursor-wait disabled:opacity-60">
                            <span x-show="!isSubmitting">Kirim Booking</span>
                            <span x-show="isSubmitting" x-cloak role="status">Mengirim…</span>
                        </button>
                    </div>
                    <p class="mt-1 text-right text-xs text-text-muted" x-show="step === 3 && !type">Pilih tipe booking dulu.</p>
                    <p class="mt-1 text-right text-xs text-text-muted" x-show="step === 3 && type === 'private' && !privateAvailable">Sewa Private tidak tersedia untuk sesi ini.</p>
                    <p class="mt-1 text-right text-xs text-text-muted" x-show="step === 3 && type === 'per_orang' && !paxIsValid">Pilih jumlah orang yang valid.</p>
                    <p class="mt-1 text-right text-xs text-text-muted" x-show="step === 2 && !filmId">Pilih film dulu untuk melanjutkan.</p>
                    <p class="mt-1 text-right text-xs text-text-muted" x-show="step === 5">Pastikan nama dan nomor WhatsApp sudah benar.</p>
                </div>
            </div>
        </form>
    </section>
@endsection
