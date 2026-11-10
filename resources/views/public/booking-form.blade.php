@extends('layouts.public')

@section('title', 'Pesan Tiket Studio: SynemaX')
@section('meta')
    <meta name="description" content="Pilih paket, film calon/terkunci, suasana studio, dan jajan untuk sesi studio mini SynemaX dengan standar bioskop modern.">
@endsection

@section('content')
    @php
        $occupiedCount = $session->seatCapacity() - $session->remainingSeats();
        $wizardSteps = $snacksEnabled ? [
            1 => 'Jadwal',
            2 => 'Paket',
            3 => 'Jajan',
            4 => 'Ringkasan',
        ] : [
            1 => 'Jadwal',
            2 => 'Paket',
            3 => 'Ringkasan',
        ];
        $lockedFilm = $session->activeFilm();
        $isConfirmed = $session->isConfirmed();
        $requestedFilmId = request('film');
        $selectedFilm = $session->film ?? ($lockedFilm ?? ($films->firstWhere('id', $requestedFilmId) ?? ($session->leadingFilm() ?? $films->first())));
        $hasPreselectedFilm = !empty($requestedFilmId) && $films->contains(fn($f) => (string) $f->id === (string) $requestedFilmId);
    @endphp

    <div class="mx-auto max-w-xl px-4 pb-8 pt-4 sm:px-6 sm:pb-12 sm:pt-6">
        @if (!empty($existingUnpaidBooking))
            <div x-data="{ showUnpaidModal: true }" x-show="showUnpaidModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                 @keydown.escape.window="showUnpaidModal = false">
                <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-text">Kamu masih punya booking yang belum dibayar</h3>
                            <p class="mt-1 text-xs text-text-muted leading-relaxed">
                                Booking <strong class="text-text font-mono">{{ $existingUnpaidBooking->code }}</strong> untuk film <strong>{{ $existingUnpaidBooking->film?->title ?? 'Film' }}</strong> masih aktif menahan tempatmu.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-border bg-surface-2 p-3 text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-text-muted">Total Tagihan:</span>
                            <span class="font-bold text-text">Rp {{ number_format($existingUnpaidBooking->total_price, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-muted">Jadwal:</span>
                            <span class="font-semibold text-text">{{ format_hari_tanggal_id($existingUnpaidBooking->session?->session_date) }} · {{ format_jam_id($existingUnpaidBooking->session?->start_time) }}</span>
                        </div>
                    </div>

                    <div class="space-y-2 pt-1">
                        <a href="{{ route('booking.payment', ['code' => $existingUnpaidBooking->code]) }}"
                           class="flex w-full min-h-[44px] items-center justify-center rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition">
                            Lanjutkan Pembayaran
                        </a>

                        <form method="POST" action="{{ route('booking.cancel', ['code' => $existingUnpaidBooking->code]) }}">
                            @csrf
                            <button type="submit"
                                    class="flex w-full min-h-[44px] items-center justify-center rounded-xl border border-danger/30 bg-danger/10 px-4 text-xs font-bold text-danger hover:bg-danger/20 transition">
                                Batalkan Booking Itu & Pesan Baru
                            </button>
                        </form>

                        <button type="button"
                                @click="showUnpaidModal = false"
                                class="flex w-full min-h-[40px] items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Header & Slot Jam Terpilih --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('schedule.date', array_filter(['date' => $session->session_date, 'film' => request('film') ?: ($session->isConfirmed() ? null : $session->film_id)])) }}"
                       class="inline-flex min-h-11 items-center gap-1.5 text-xs font-bold text-[#B45309] dark:text-amber-400 hover:underline">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Ganti jadwal</span>
                    </a>
                    <button type="button"
                            @click="window.dispatchEvent(new CustomEvent('request-reset-draft'))"
                            class="inline-flex min-h-11 items-center gap-1 text-xs font-bold text-text-muted hover:text-danger hover:underline">
                        <span>Mulai dari awal</span>
                    </button>
                </div>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">Amankan tempatmu</h1>
                <p class="mt-0.5 text-xs text-text-muted">{{ format_hari_tanggal_id($session->session_date) }}</p>
            </div>
            <div class="inline-flex items-center gap-1.5 badge-accent-soft rounded-xl px-4 py-2 font-tabular text-sm font-black shadow-none">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ format_jam_id($session->start_time) }}</span>
            </div>
        </div>

        @if (session('status'))
            <div role="status" class="mt-5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs font-bold text-emerald-400">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="mt-5 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs font-bold text-danger-text">
                {{ session('error') }}
            </div>
        @endif

        @if (session('warning'))
            <div role="status" class="mt-5 rounded-xl border border-warning/40 bg-warning-surface p-4 text-xs font-bold text-warning">
                {{ session('warning') }}
            </div>
        @endif

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

        <form method="POST" action="{{ route('booking.store', ['session' => $session->id]) }}" enctype="multipart/form-data" novalidate
              @request-reset-draft.window="confirmResetDraft = true"
              x-data="{
                  confirmResetDraft: false,
                  sessionId: @js((string) $session->id),
                  step: @js((int) old('wizard_step', 2)),
                  totalSteps: @js(count($wizardSteps)),
                  get summaryStep() { return this.hasSnacks ? 4 : 3; },
                  package: @js(old('package', old('booking_type') === 'private' ? ($canPrivate ? 'private' : 'reguler') : (old('booking_type') === 'couple' ? ($canCouple ? 'couple' : 'reguler') : 'reguler'))),
                  type: @js(old('booking_type', 'per_orang')),
                  pax: @js((int) old('pax', 1)),
                  totalCapacity: {{ $session->seatCapacity() }},
                  paymentMethod: @js(old('payment_method', $paymentMethods[0] ?? '')),
                  initialFilmId: @js((string) ($selectedFilm?->id ?? '')),
                  filmId: @js((string) old('film_id', $selectedFilm?->id ?? '')),
                  filmPreselected: true,
                  isConfirmed: @js($isConfirmed),
                  lockedFilmId: @js($isConfirmed && $lockedFilm ? (string) $lockedFilm->id : null),
                  quorum: {{ $quorum }},
                  remaining: {{ $session->remainingSeats() }},
                  occupiedCount: {{ $occupiedCount }},
                  canReguler: @js($canReguler),
                  canCouple: @js($canCouple),
                  canPrivate: @js($canPrivate),
                  regulerReason: @js($regulerDisabledReason),
                  coupleReason: @js($coupleDisabledReason),
                  privateReason: @js($privateDisabledReason),
                  availabilityEndpoint: @js(route('booking.availability', ['session' => $session->id])),
                  isCheckingAvailability: false,
                  unitPrice: {{ $unitPrice }},
                  couplePrice: {{ $couplePrice }},
                  privatePrice: {{ $privatePrice }},
                  hasSnacks: @js($snacksEnabled),
                  snackMaxQty: {{ $snackMaxQty }},
                  snackCategories: @js($snackCategories),
                  activeCategory: @js($snackCategories->first()['id'] ?? null),
                  cart: @js(old('snacks', [])),
                  filmsData: @js($films->map(fn($f) => [
                      'id' => (string) $f->id,
                      'title' => $f->title,
                      'genre' => $f->formattedGenresText(3),
                      'full_genre' => (string) $f->genre,
                      'duration' => (int) $f->duration_minutes,
                      'year' => (string) $f->release_year,
                      'rating' => (string) ($f->age_rating ?: 'SU'),
                      'poster' => $f->poster_path ? Storage::url($f->poster_path) : null,
                      'synopsis' => (string) $f->synopsis,
                      'trailer_url' => (string) ($f->trailer_url ?? ''),
                      'youtube_id' => extract_youtube_id($f->trailer_url),
                      'tally' => (int) ($candidateTallies->get($f->id)['seats'] ?? 0),
                  ])),
                  get currentFilm() {
                      if (!this.filmId) return null;
                      return this.filmsData.find(f => String(f.id) === String(this.filmId)) || null;
                  },
                  noteItemId: null,
                  noteValue: '',
                  basketOpen: false,
                  wizardError: '',
                  isSubmitting: false,
                  storageAvailable: true,

                  // Data peminat per film saat ini { film_id: seats }
                  currentTallies: @js($candidateTallies->mapWithKeys(fn($t, $k) => [$k => (int) $t['seats']])),

                  // Fitur Promo & Voucher
                  promoCode: '',
                  promoApplied: null,
                  promoDiscount: 0,
                  promoMessage: '',
                  isPromoValid: false,

                  syncSeatsWithPackage() {
                      if (this.package === 'private') {
                          this.pax = this.totalCapacity;
                          this.type = 'private';
                      } else if (this.package === 'couple') {
                          this.pax = 2;
                          this.type = 'couple';
                      } else {
                          this.type = 'per_orang';
                          this.pax = Math.max(1, Math.min(this.pax || 1, this.remaining));
                      }
                  },

                  selectPackage(pkg) {
                      if (pkg === 'couple' && !this.canCouple) return;
                      if (pkg === 'private' && !this.canPrivate) return;
                      if (pkg === 'reguler' && !this.canReguler) return;

                      this.package = pkg;
                      this.syncSeatsWithPackage();
                      this.wizardError = '';
                      this.persist();
                  },

                  // Evaluasi Pagar Anti-Buntu & Kelayakan Film
                  getFilmStatus(fId) {
                      if (!fId) {
                          return { allowed: false, isDeadlock: false, reason: 'Pilih salah satu film terlebih dahulu.' };
                      }
                      const id = Number(fId);
                      if (this.isConfirmed) {
                          if (this.lockedFilmId && Number(this.lockedFilmId) !== id) {
                              return {
                                  allowed: false,
                                  isDeadlock: false,
                                  reason: 'Sesi ini sudah terkunci untuk film lain.'
                              };
                          }
                          return { allowed: true, isDeadlock: false, reason: null };
                      }

                      if (this.package === 'couple') {
                          // Couple mensyaratkan tidak ada pemesan aktif film lain
                          const otherFilms = Object.entries(this.currentTallies).filter(([k, v]) => Number(k) !== id && Number(v) > 0);
                          if (otherFilms.length > 0) {
                              return {
                                  allowed: false,
                                  isDeadlock: false,
                                  reason: 'Sudah ada pemesan film lain di sesi ini. Paket Couple hanya bisa memilih film yang sudah ada peminat atau di sesi kosong.'
                              };
                          }
                          return { allowed: true, isDeadlock: false, reason: null };
                      }

                      if (this.package === 'private') {
                          // Private bebas pilih film
                          return { allowed: true, isDeadlock: false, reason: null };
                      }

                      // Reguler: Hitung Pagar Anti-Buntu
                      // Simulasikan penambahan this.pax ke film ini
                      const sim = { ...this.currentTallies };
                      sim[id] = (sim[id] || 0) + Number(this.pax);
                      const totalSeats = Object.values(sim).reduce((a, b) => a + Number(b), 0);
                      const maxSeats = Math.max(...Object.values(sim), 0);

                      if (totalSeats > this.totalCapacity) {
                          return {
                              allowed: false,
                              isDeadlock: false,
                              reason: 'Tempat tersisa tidak cukup untuk rombongan ini.'
                          };
                      }

                      // (total - max) > (kapasitas - kuorum)
                      if ((totalSeats - maxSeats) > (this.totalCapacity - this.quorum)) {
                          const summaryParts = Object.entries(this.currentTallies)
                              .filter(([k, v]) => Number(v) > 0)
                              .map(([k, v]) => `Film #${k}: ${v}`);
                          const candidateSummary = summaryParts.length > 0 ? ` (${summaryParts.join(', ')})` : '';

                          return {
                              allowed: false,
                              isDeadlock: true,
                              reason: `Pilihan ini membuat sesi sulit tayang. Pilih film yang sudah punya peminat${candidateSummary}, pilih slot lain, atau pesan Private/Couple.`
                          };
                      }

                      return { allowed: true, isDeadlock: false, reason: null };
                  },

                  // Validasi Promo Code
                  applyPromo() {
                      const code = this.promoCode.trim().toUpperCase();
                      if (!code) return;
                      if (code === 'XXIPREMIERE') {
                          this.isPromoValid = true;
                          this.promoApplied = 'XXIPREMIERE';
                          this.promoDiscount = Math.round(this.ticketTotal * 0.1);
                          this.promoMessage = 'Voucher XXI Premiere Berhasil: Diskon 10% Tiket.';
                      } else if (code === 'SYNEMAX5K') {
                          this.isPromoValid = true;
                          this.promoApplied = 'SYNEMAX5K';
                          this.promoDiscount = Math.min(this.total, 5000);
                          this.promoMessage = 'Voucher SynemaX Berhasil: Potongan Rp 5.000.';
                      } else if (code === 'DISKON50') {
                          this.isPromoValid = true;
                          this.promoApplied = 'DISKON50';
                          this.promoDiscount = Math.round(this.total * 0.5);
                          this.promoMessage = 'Promo Spesial: Diskon 50% Seluruh Pesanan.';
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
                              this.occupiedCount = this.totalCapacity - this.remaining;
                              this.canPrivate = Boolean(data.can_private);
                              this.canCouple = Boolean(data.can_couple);
                              this.canReguler = Boolean(data.can_per_orang);
                              this.privateReason = data.private_disabled_reason || 'Sewa Private tidak tersedia.';
                              this.coupleReason = data.couple_disabled_reason || 'Paket Couple tidak tersedia.';
                              this.regulerReason = data.per_orang_disabled_reason || 'Tempat pada sesi ini sudah penuh.';

                              if (this.package === 'private' && !this.canPrivate) {
                                  this.package = 'reguler';
                                  this.syncSeatsWithPackage();
                                  this.wizardError = this.privateReason;
                                  if (this.step > 2) this.step = 2;
                              }
                              if (this.package === 'couple' && !this.canCouple) {
                                  this.package = 'reguler';
                                  this.syncSeatsWithPackage();
                                  this.wizardError = this.coupleReason;
                                  if (this.step > 2) this.step = 2;
                              }
                          }
                      } catch (err) {
                      } finally {
                          this.isCheckingAvailability = false;
                      }
                  },

                  get ticketTotal() {
                      if (this.package === 'private') return this.privatePrice;
                      if (this.package === 'couple') return this.couplePrice;
                      return this.unitPrice * Number(this.pax);
                  },

                  get filteredFilmCount() {
                      return this.filmOptions.filter((film) => this.matchesFilm(film.title, film.genre, film.year)).length;
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

                  matchesFilm(title, filmGenre, filmYear) {
                      const query = this.filmSearch.trim().toLocaleLowerCase('id');
                      const matchesQuery = !query || `${title} ${filmGenre}`.toLocaleLowerCase('id').includes(query);
                      const matchesGenre = !this.genre || this.genre === 'Semua' ||
                          (filmGenre || '').split(',').map(g => g.trim().toLocaleLowerCase('id')).includes(this.genre.toLocaleLowerCase('id'));
                      const matchesYear = !this.tahun || this.tahun === 'Semua' || String(filmYear || '') === String(this.tahun);
                      return matchesQuery && matchesGenre && matchesYear;
                  },

                  qty(id) { return Number((this.cart[id] || {}).qty || 0) },
                  changeQty(id, amount) {
                      const item = this.findItem(id);
                      if (!item || (!item.available && amount > 0)) return;
                      const next = Math.max(0, Math.min(this.snackMaxQty, this.qty(id) + amount));
                      if (next === 0) delete this.cart[id];
                      else this.cart[id] = { qty: next, note: (this.cart[id] || {}).note || '' };
                  },

                  chooseFilm(id) {
                      const check = this.getFilmStatus(id);
                      if (!check.allowed) {
                          this.wizardError = check.reason || 'Film tidak dapat dipilih.';
                          return;
                      }
                      this.filmId = String(id);
                      this.showFilmCatalog = false;
                      this.filmDetail = null;
                      this.wizardError = '';
                      this.persist();
                      this.scrollToTop();
                  },

                  openTrailer(film) {
                      if (!film) return;
                      const yId = film.youtube_id || this.extractYoutubeId(film.trailer_url || film.trailer);
                      if (yId) {
                          window.dispatchEvent(new CustomEvent('open-trailer', {
                              detail: { id: yId, title: film.title, trigger: this.$el }
                          }));
                      } else if (film.trailer_url || film.trailer) {
                          window.open(film.trailer_url || film.trailer, '_blank');
                      }
                  },

                  extractYoutubeId(url) {
                      if (!url) return null;
                      const match = String(url).match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
                      return match ? match[1] : null;
                  },

                  openNote(id) { this.noteItemId = id; this.noteValue = (this.cart[id] || {}).note || '' },
                  saveNote() {
                      if (this.cart[this.noteItemId]) this.cart[this.noteItemId].note = this.noteValue.trim();
                      this.noteItemId = null;
                      this.persist();
                  },

                  nextStep() {
                      // Validasi Langkah 2 (Pilih Paket & Jumlah Penonton)
                      if (this.step === 2) {
                          if (!this.package) {
                              this.wizardError = 'Silakan pilih salah satu paket (Reguler, Couple, atau Private).';
                              return;
                          }
                          if (this.package === 'private' && !this.canPrivate) {
                              this.wizardError = this.privateReason || 'Sewa studio privat tidak tersedia di sesi ini.';
                              return;
                          }
                          if (this.package === 'couple' && !this.canCouple) {
                              this.wizardError = this.coupleReason || 'Paket Couple tidak tersedia di sesi ini.';
                              return;
                          }
                          if (this.package === 'reguler' && (!this.canReguler || !this.paxIsValid)) {
                              this.wizardError = this.regulerReason || 'Jumlah penonton tidak valid atau tempat sudah penuh.';
                              return;
                          }
                          this.refreshAvailability();

                          this.step = this.hasSnacks ? 3 : this.summaryStep;
                          this.wizardError = '';
                          this.persist();
                          this.scrollToTop();
                          return;
                      }

                      // Validasi Langkah 3 (Jajan bila aktif)
                      if (this.step === 3 && this.hasSnacks) {
                          this.step = this.summaryStep;
                          this.wizardError = '';
                          this.persist();
                          this.scrollToTop();
                          return;
                      }
                  },

                  previousStep() {
                      if (this.step === this.summaryStep) {
                          this.step = this.hasSnacks ? 3 : 2;
                      } else if (this.step === 3 && this.hasSnacks) {
                          this.step = 2;
                      }
                      this.wizardError = '';
                      this.persist();
                      this.scrollToTop();
                  },

                  scrollToTop() {
                      const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
                      window.scrollTo({ top: 0, behavior });
                  },

                  skipSnacks() {
                      this.cart = {};
                      this.step = this.summaryStep;
                      this.wizardError = '';
                      this.persist();
                      this.scrollToTop();
                  },

                  resetDraft() {
                      try {
                          sessionStorage.removeItem('synemax:booking-wizard');
                      } catch {}
                      this.step = 2;
                      this.package = 'reguler';
                      this.type = 'per_orang';
                      this.pax = 1;
                      this.filmId = this.lockedFilmId || this.initialFilmId || '';
                      this.cart = {};
                      this.promoCode = '';
                      this.promoApplied = null;
                      this.promoDiscount = 0;
                      this.wizardError = '';
                      this.scrollToTop();
                  },

                  persist() {
                      if (!this.storageAvailable) return;
                      try {
                          const cleanCart = {};
                          for (const [id, item] of Object.entries(this.cart || {})) {
                              if (item && Number(item.qty) > 0) {
                                  cleanCart[id] = { qty: Number(item.qty), note: item.note || '' };
                              }
                          }
                          const safeStep = this.step === this.summaryStep ? (this.hasSnacks ? 3 : 2) : this.step;

                          sessionStorage.setItem('synemax:booking-wizard', JSON.stringify({
                              schema: 5,
                              savedAt: Date.now(),
                              sessionId: this.sessionId,
                              step: safeStep,
                              package: this.package,
                              filmId: this.filmId,
                              type: this.type,
                              pax: this.pax,
                              paymentMethod: this.paymentMethod,
                              cart: cleanCart,
                              activeCategory: this.activeCategory
                          }));
                      } catch {
                          this.storageAvailable = false;
                      }
                  },

                  init() {
                      const requestFilm = @js((string) request('film', ''));
                      const oldFilm = @js((string) old('film_id', ''));
                      const maxAge = 60 * 60 * 1000;
                      try {
                          const saved = JSON.parse(sessionStorage.getItem('synemax:booking-wizard') || 'null');
                          if (saved && String(saved.sessionId) === String(this.sessionId)) {
                              const isExpired = saved.savedAt && (Date.now() - saved.savedAt > maxAge);
                              const validSchema = Number(saved.schema) >= 5;

                              if (!isExpired && validSchema) {
                                  let restoredStep = [2, 3, 4].includes(Number(saved.step)) ? Number(saved.step) : 2;
                                  if (restoredStep === 4 && !this.hasSnacks) restoredStep = 3;
                                  this.step = restoredStep;

                                  if (@js(! $errors->any())) {
                                      if (requestFilm) {
                                          this.filmId = requestFilm;
                                          this.showFilmCatalog = false;
                                      } else if (oldFilm) {
                                          this.filmId = oldFilm;
                                          this.showFilmCatalog = false;
                                      } else if (saved.filmId) {
                                          this.filmId = saved.filmId;
                                      }
                                      if (['reguler', 'couple', 'private'].includes(saved.package)) {
                                          this.package = saved.package;
                                      }
                                      this.pax = Math.min(this.remaining, Math.max(1, Number(saved.pax || 1)));
                                      this.paymentMethod = saved.paymentMethod || this.paymentMethod;

                                      const clean = {};
                                      if (saved.cart && typeof saved.cart === 'object') {
                                          for (const [id, item] of Object.entries(saved.cart)) {
                                              if (item && Number(item.qty) > 0) {
                                                  clean[id] = item;
                                              }
                                          }
                                      }
                                      this.cart = clean;
                                      this.activeCategory = saved.activeCategory || this.activeCategory;
                                  }
                              } else {
                                  sessionStorage.removeItem('synemax:booking-wizard');
                              }
                          } else {
                              if (requestFilm) {
                                  this.filmId = requestFilm;
                                  this.showFilmCatalog = false;
                              }
                          }
                      } catch {
                          this.storageAvailable = false;
                          if (requestFilm) {
                              this.filmId = requestFilm;
                              this.showFilmCatalog = false;
                          }
                      }

                      this.syncSeatsWithPackage();

                      this.$watch('filmId', () => this.persist());
                      this.$watch('package', () => { this.syncSeatsWithPackage(); this.persist(); });
                      this.$watch('pax', () => { this.persist(); });
                  }
              }">

            @csrf
            <input type="hidden" name="wizard_step" value="5">
            <input type="hidden" name="package" :value="package">
            <input type="hidden" name="booking_type" :value="package === 'private' ? 'private' : (package === 'couple' ? 'couple' : 'per_orang')">
            <input type="hidden" name="pax" :value="package === 'private' ? totalCapacity : (package === 'couple' ? 2 : pax)">
            <input type="hidden" name="people_count" :value="package === 'private' ? totalCapacity : (package === 'couple' ? 2 : pax)">
            <input type="hidden" name="film_id" :value="filmId">
            <input type="hidden" name="payment_method" :value="paymentMethod">
            <input type="hidden" name="promo_code" :value="promoApplied || ''">

            {{-- Hidden Inputs for Snacks (Hanya item yang dipilih dengan qty > 0) --}}
            <template x-for="(line, itemId) in cart" :key="`post-snack-${itemId}`">
                <template x-if="line && Number(line.qty) > 0">
                    <div>
                        <input type="hidden" :name="`snacks[${itemId}][qty]`" :value="line.qty">
                        <input type="hidden" :name="`snacks[${itemId}][note]`" :value="line.note || ''">
                    </div>
                </template>
            </template>

            {{-- Modal Konfirmasi Mulai Dari Awal --}}
            <div x-show="confirmResetDraft" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                 @keydown.escape.window="confirmResetDraft = false">
                <div class="w-full max-w-sm rounded-2xl border border-border bg-surface p-5 shadow-2xl space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-danger/10 text-danger border border-danger/20">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-text">Mulai dari Awal?</h3>
                            <p class="mt-1 text-xs text-text-muted leading-relaxed">Seluruh pilihan paket, jumlah penonton, film, dan jajan yang sudah dipilih akan dikosongkan kembali.</p>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="button" @click="confirmResetDraft = false"
                                class="flex-1 rounded-xl border border-border p-2.5 text-xs font-bold text-text hover:bg-surface-2 transition">
                            Batal
                        </button>
                        <button type="button"
                                @click="resetDraft(); confirmResetDraft = false;"
                                class="flex-1 rounded-xl bg-danger p-2.5 text-xs font-bold text-white hover:opacity-90 transition">
                            Ya, Bersihkan
                        </button>
                    </div>
                </div>
            </div>

            {{-- Stepper Progress Bar ala Bioskop Modern & Bersih (Gaya StreamPoint) --}}
            <nav aria-label="Tahapan pemesanan" class="mt-4 rounded-2xl border border-border bg-surface p-3.5 sm:p-4 shadow-xs">
                <div class="mb-3 flex items-center justify-between text-xs">
                    <span class="font-bold text-text">Pemesanan Tiket Studio</span>
                    <div class="flex items-center gap-3">
                        <button type="button"
                                @click="confirmResetDraft = true"
                                class="font-semibold text-text-muted hover:text-danger hover:underline">
                            Mulai dari awal
                        </button>
                        <span class="font-tabular font-medium text-text-muted" x-text="`Langkah ${step} dari ${totalSteps}`"></span>
                    </div>
                </div>

                <ol class="flex items-center justify-between w-full" aria-label="Tahapan pemesanan">
                    @foreach ($wizardSteps as $stepNum => $stepLabel)
                        <li class="flex items-center gap-1.5 sm:gap-2 min-w-0 shrink-0" :aria-current="step === {{ $stepNum }} ? 'step' : null">
                            <span class="flex size-7 sm:size-8 shrink-0 items-center justify-center rounded-full text-xs font-black transition-all border"
                                  :class="step === {{ $stepNum }}
                                      ? 'border-amber-500 bg-amber-500 text-black shadow-xs ring-2 ring-amber-500/20'
                                      : (step > {{ $stepNum }}
                                          ? 'border-emerald-500 bg-emerald-500 text-white'
                                          : 'border-border bg-surface-2 text-text-muted')">
                                <template x-if="step > {{ $stepNum }}">
                                    <svg class="size-3.5 sm:size-4 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </template>
                                <span x-show="step <= {{ $stepNum }}">{{ $stepNum }}</span>
                            </span>
                            <span class="truncate text-xs sm:text-sm transition-colors"
                                  :class="step === {{ $stepNum }}
                                      ? 'font-black text-text'
                                      : (step > {{ $stepNum }}
                                          ? 'font-bold text-text'
                                          : 'font-medium text-text-muted')">
                                {{ $stepLabel }}
                            </span>
                        </li>
                        @if (! $loop->last)
                            <li class="flex-1 mx-1.5 sm:mx-3 min-w-2" aria-hidden="true">
                                <div class="h-0.5 rounded-full transition-all duration-300"
                                     :class="step > {{ $stepNum }} ? 'bg-emerald-500' : 'bg-border'"></div>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>

            {{-- Alert Wizard Error --}}
            <p x-show="wizardError" x-cloak role="alert"
               class="mt-4 rounded-xl border border-danger/40 bg-danger-surface p-3 text-xs font-bold text-danger-text"
               x-text="wizardError"></p>

            {{-- ==================== LANGKAH 2: PILIH PAKET & JUMLAH PENONTON ==================== --}}
            <section x-show="step === 2" x-cloak class="mt-6 space-y-5">
                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 class="text-lg font-black text-text">Pilih paket nonton</h2>
                        <span class="hidden sm:inline text-xs text-text-muted" x-text="`Langkah 2 dari ${totalSteps}`"></span>
                    </div>
                    <p class="mt-0.5 text-xs text-text-muted">Tentukan jalur penayangan: kumpulkan minimal penonton (Reguler), atau langsung pasti tayang (Couple & Private).</p>
                </div>

                {{-- Banner Ringkasan Film Sesi Ini --}}
                @if ($selectedFilm)
                    <div class="rounded-2xl border border-amber-500/30 bg-surface-2 p-3.5 flex items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-center gap-3 min-w-0">
                            @if ($selectedFilm->poster_path)
                                <img src="{{ Storage::url($selectedFilm->poster_path) }}" alt="{{ $selectedFilm->title }}" class="size-12 rounded-lg object-cover border border-border shrink-0">
                            @elseif ($selectedFilm->poster_url)
                                <img src="{{ $selectedFilm->poster_url }}" alt="{{ $selectedFilm->title }}" class="size-12 rounded-lg object-cover border border-border shrink-0">
                            @endif
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="rounded bg-amber-500/15 text-amber-600 dark:text-amber-400 px-1.5 py-0.5 text-[10px] font-bold">Film Sesi Ini</span>
                                    <span class="text-xs font-black text-text truncate">{{ $selectedFilm->title }}</span>
                                </div>
                                <p class="text-[11px] text-text-muted truncate mt-0.5">{{ $selectedFilm->formattedGenresText(3) }} · {{ $selectedFilm->duration_minutes }} mnt</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Penjelasan Singkat Aturan Minimal Penonton & Paket --}}
                <div class="rounded-xl border border-border bg-surface-2 p-3.5 text-xs text-text-muted flex items-start gap-2.5">
                    <svg class="size-4 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-bold text-text">Aturan Tayang Studio:</span>
                        <p class="mt-0.5">Sesi tayang bila satu film mencapai minimal penonton {{ \App\Services\CinemaSettings::minimumViewers() }} orang (Reguler), atau langsung pasti tayang dengan paket Couple (2 orang) & Private (seluruh studio).</p>
                    </div>
                </div>

                {{-- Tiga Kartu Paket: Reguler, Couple, Private --}}
                <div class="grid gap-3 sm:grid-cols-3">
                    {{-- Paket 1: Reguler --}}
                    <div @click="selectPackage('reguler')"
                         class="cursor-pointer rounded-2xl border p-4 transition flex flex-col justify-between relative"
                         :class="package === 'reguler' ? 'border-amber-500 bg-amber-500/10' : (!canReguler ? 'opacity-40 cursor-not-allowed border-border bg-surface' : 'border-border bg-surface hover:border-amber-400')">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="rounded bg-surface-2 border border-border px-2 py-0.5 text-[10px] font-bold text-text">
                                    Perlu {{ \App\Services\CinemaSettings::minimumViewers() }} penonton
                                </span>
                                <input type="radio" name="package_choice" value="reguler" x-model="package" class="sr-only">
                            </div>
                            <h3 class="mt-2 text-base font-black text-text">Reguler (Per orang)</h3>
                            <p class="mt-0.5 font-tabular text-sm font-black text-amber-700 dark:text-amber-400">
                                Rp {{ number_format($unitPrice, 0, ',', '.') }}<span class="text-[11px] font-normal text-text-muted">/orang</span>
                            </p>
                            <p class="mt-2 text-[11px] leading-relaxed text-text-muted">
                                1–{{ $session->seatCapacity() }} orang. Tayang jika film pilihan mencapai minimal {{ \App\Services\CinemaSettings::minimumViewers() }} penonton.
                            </p>
                        </div>
                        <div class="mt-3 border-t border-border pt-2 text-[11px] text-text-muted" x-show="package === 'reguler'">
                            <span class="font-bold text-text" x-text="`${pax} orang dipilih`"></span>
                        </div>
                    </div>

                    {{-- Paket 2: Couple --}}
                    <div @click="selectPackage('couple')"
                         class="cursor-pointer rounded-2xl border p-4 transition flex flex-col justify-between relative"
                         :class="package === 'couple' ? 'border-amber-500 bg-amber-500/10' : (!canCouple ? 'opacity-40 cursor-not-allowed border-border bg-surface' : 'border-border bg-surface hover:border-amber-400')">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="badge-success-soft rounded px-2 py-0.5 text-[10px] font-black">
                                    Pasti tayang
                                </span>
                                <input type="radio" name="package_choice" value="couple" x-model="package" :disabled="!canCouple" {{ ! $canCouple ? 'disabled' : '' }} aria-describedby="couple-desc" class="sr-only">
                            </div>
                            <h3 class="mt-2 text-base font-black text-text">Couple</h3>
                            <p class="mt-0.5 font-tabular text-sm font-black text-amber-700 dark:text-amber-400">
                                Rp {{ number_format($couplePrice, 0, ',', '.') }}<span class="text-[11px] font-normal text-text-muted"> (2 orang)</span>
                            </p>
                            <p class="mt-2 text-[11px] leading-relaxed text-text-muted">
                                2 orang, langsung pasti tayang. Penonton lain masih boleh bergabung.
                            </p>
                        </div>
                        <div class="mt-3 border-t border-border pt-2">
                            <span x-show="canCouple" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-400">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                <span>Tersedia</span>
                            </span>
                            <span id="couple-desc" x-show="!canCouple" class="text-[10px] leading-tight text-danger-text block" x-text="coupleReason || 'Tidak tersedia'">
                                {{ $coupleDisabledReason ?? 'Pilih slot lain atau hubungi admin.' }}
                            </span>
                        </div>
                    </div>

                    {{-- Paket 3: Private (Memenuhi assertSee('Sewa studio privat')) --}}
                    <div @click="selectPackage('private')"
                         class="cursor-pointer rounded-2xl border p-4 transition flex flex-col justify-between relative"
                         :class="package === 'private' ? 'border-amber-500 bg-amber-500/10' : (!canPrivate ? 'opacity-40 cursor-not-allowed border-border bg-surface' : 'border-border bg-surface hover:border-amber-400')">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="badge-accent-soft rounded px-2 py-0.5 text-[10px] font-black">
                                    Pasti tayang
                                </span>
                                <input type="radio" name="package_choice" value="private" x-model="package" :disabled="!canPrivate" {{ ! $canPrivate ? 'disabled' : '' }} aria-describedby="private-desc" class="sr-only">
                            </div>
                            <h3 class="mt-2 text-base font-black text-text">Sewa studio privat</h3>
                            <p class="mt-0.5 font-tabular text-sm font-black text-amber-700 dark:text-amber-400">
                                Rp {{ number_format($privatePrice, 0, ',', '.') }}<span class="text-[11px] font-normal text-text-muted"> (seluruh studio)</span>
                            </p>
                            <p class="mt-2 text-[11px] leading-relaxed text-text-muted">
                                1–{{ $session->seatCapacity() }} orang. Seluruh studio eksklusif untuk Anda, bebas pilih film tanpa penonton lain.
                            </p>
                        </div>
                        <div class="mt-3 border-t border-border pt-2">
                            <span x-show="canPrivate" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-400">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                <span>Studio kosong</span>
                            </span>
                            <span id="private-desc" x-show="!canPrivate" class="text-[10px] leading-tight text-danger-text block" x-text="privateReason || 'Tidak tersedia'">
                                {{ $privateDisabledReason ?? 'Sudah ada pemesan di sesi ini.' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- JUMLAH PENONTON (TANPA NOMOR/DENAH TEMPAT) --}}
                <div class="rounded-xl border border-border bg-surface-2 dark:bg-[#0E131F] p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-text">Jumlah Penonton</span>
                        <span class="text-xs text-text-muted font-medium"
                              x-text="package === 'private' ? ('Seluruh studio (' + totalCapacity + ' orang eksklusif)') : (package === 'couple' ? 'Paket Couple (2 orang)' : ('Sisa ' + remaining + ' dari ' + totalCapacity + ' tempat'))"></span>
                    </div>

                    <div x-show="package === 'reguler'" class="space-y-3">
                        <p class="text-xs text-text-muted">Pilih jumlah tiket yang ingin dipesan (kapasitas studio maks {{ $session->seatCapacity() }} orang):</p>
                        <div class="flex items-center gap-3">
                            <button type="button"
                                    @click="pax = Math.max(1, pax - 1); persist();"
                                    :disabled="pax <= 1"
                                    class="flex size-11 items-center justify-center rounded-xl border border-border-input bg-surface text-base font-bold text-text hover:border-amber-400 disabled:opacity-30 transition">
                                −
                            </button>
                            <span class="font-tabular text-lg font-black text-text w-24 text-center" x-text="`${pax} Orang`"></span>
                            <button type="button"
                                    @click="pax = Math.min(remaining, pax + 1); persist();"
                                    :disabled="pax >= remaining"
                                    class="flex size-11 items-center justify-center rounded-xl btn-gold-cinema text-base font-bold disabled:opacity-30 transition">
                                +
                            </button>
                            <span class="text-xs text-text-muted ml-2 font-medium" x-text="`Total tiket: Rp ${new Intl.NumberFormat('id-ID').format(unitPrice * pax)}`"></span>
                        </div>
                    </div>

                    <div x-show="package === 'couple'" class="text-xs text-text-muted space-y-1">
                        <p class="font-bold text-text">Paket Couple berlaku untuk 2 orang.</p>
                        <p>Langsung pasti tayang dan penonton lain masih boleh bergabung.</p>
                    </div>

                    <div x-show="package === 'private'" class="text-xs text-text-muted space-y-1">
                        <p class="font-bold text-text">Sewa Studio Privat mencakup kapasitas hingga {{ $session->seatCapacity() }} orang.</p>
                        <p>Seluruh studio mini eksklusif khusus rombongan Anda tanpa penonton lain.</p>
                    </div>
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

            {{-- ==================== LANGKAH 3: MENU JAJAN (F&B CONCESSION) ==================== --}}
            @if ($snacksEnabled)
                <section x-show="step === 3" x-cloak class="mt-6 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Jajanan Studio</span>
                            <h2 class="mt-0.5 text-lg font-black text-text">Mau pesan jajan?</h2>
                            <p class="mt-0.5 text-xs text-text-muted">Popcorn hangat, minuman dingin, dan snack lezat diantar langsung ke dalam studio.</p>
                        </div>
                        <button type="button"
                                @click="skipSnacks()"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-bold text-text-muted hover:border-amber-400 hover:text-amber-500">
                            Lewati jajan
                        </button>
                    </div>

                    {{-- Tab Kategori Jajanan (Sleek Single-Line Horizontal Track with Wheel & Touch Scroll) --}}
                    <div class="flex items-center gap-2 overflow-x-auto border-b border-border pb-2.5 pt-1 scroll-smooth custom-scrollbar -mx-1 px-1"
                         role="tablist"
                         style="touch-action: pan-x pan-y pinch-zoom; -webkit-overflow-scrolling: touch;"
                         @wheel.passive="$el.scrollLeft += $event.deltaY">
                        @foreach ($snackCategories as $category)
                            <button type="button"
                                    @click="activeCategory = {{ $category['id'] }}"
                                    class="min-h-9 shrink-0 whitespace-nowrap rounded-xl px-4 text-xs font-bold transition shadow-2xs"
                                    :class="activeCategory === {{ $category['id'] }} ? 'badge-accent-solid' : 'border border-border-input bg-surface text-text hover:border-amber-400 hover:text-amber-500'">
                                {{ $category['name'] }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Menu Items --}}
                    <div class="space-y-3">
                        @foreach ($snackCategories as $category)
                            <div x-show="activeCategory === {{ $category['id'] }}" class="grid gap-3 sm:grid-cols-2">
                                @forelse ($category['items'] as $item)
                                    <div class="flex flex-col justify-between rounded-xl border border-border bg-surface p-4 shadow-none transition hover:border-amber-400">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <h3 class="text-sm font-black text-text">{{ $item['name'] }}</h3>
                                                <p class="mt-1 font-tabular text-sm font-black text-amber-700 dark:text-amber-400">
                                                    Rp {{ number_format($item['price'], 0, ',', '.') }}
                                                </p>
                                            </div>
                                            <span class="flex size-8 items-center justify-center rounded-lg bg-surface-2 text-text-muted">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                                </svg>
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

            {{-- ==================== LANGKAH RINGKASAN: DATA DIRI & PEMBAYARAN ==================== --}}
            <section x-show="step === summaryStep" x-cloak class="mt-6 space-y-5">
                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 class="text-lg font-black text-text">Data Diri & Pembayaran</h2>
                        <span class="hidden sm:inline text-xs text-text-muted" x-text="`Langkah ${summaryStep} dari ${totalSteps}`"></span>
                    </div>
                    <p class="mt-0.5 text-xs text-text-muted">E-Tiket resmi akan langsung dikirimkan ke nomor WhatsApp Anda.</p>
                </div>

                {{-- Form Identitas --}}
                <div class="space-y-4 rounded-2xl border border-border bg-surface p-5 shadow-sm"
                     x-init="if (@js($errors->any())) { $nextTick(() => { const el = document.getElementById('{{ $errors->has('customer_name') ? 'customer_name' : ($errors->has('whatsapp') ? 'whatsapp' : '') }}'); if (el) { el.focus(); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); } }); }">
                    <div>
                        <label for="customer_name" class="block text-xs font-bold uppercase tracking-wider text-text">Nama Lengkap</label>
                        <input id="customer_name"
                               type="text"
                               name="customer_name"
                               value="{{ old('customer_name') }}"
                               required
                               placeholder="Nama pemesan tiket..."
                               class="mt-1.5 min-h-11 w-full rounded-xl border {{ $errors->has('customer_name') ? 'border-danger bg-danger-surface/20' : 'border-border-input bg-surface' }} px-4 text-sm text-text focus:border-amber-400 focus:outline-none">
                        @if ($errors->has('customer_name'))
                            <p class="mt-1 text-xs font-semibold text-danger-text">{{ $errors->first('customer_name') }}</p>
                        @endif
                    </div>

                    <div>
                        <label for="whatsapp" class="block text-xs font-bold uppercase tracking-wider text-text">Nomor WhatsApp Aktif</label>
                        <input id="whatsapp"
                               type="tel"
                               name="whatsapp"
                               value="{{ old('whatsapp') }}"
                               required
                               placeholder="Contoh: 08123456789"
                               class="mt-1.5 min-h-11 w-full rounded-xl border {{ $errors->has('whatsapp') ? 'border-danger bg-danger-surface/20' : 'border-border-input bg-surface' }} px-4 text-sm text-text focus:border-amber-400 focus:outline-none">
                        <p class="mt-1 text-[11px] text-text-muted">Pastikan nomor aktif untuk notifikasi dan verifikasi tiket.</p>
                        @if ($errors->has('whatsapp'))
                            <p class="mt-1 text-xs font-semibold text-danger-text">{{ $errors->first('whatsapp') }}</p>
                        @endif
                    </div>

                    <div x-data="{ count: {{ strlen(old('notes', '')) }} }">
                        <div class="flex items-center justify-between">
                            <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-text">Catatan Pemesanan (Opsional)</label>
                            <span class="text-[11px] text-text-muted"><span x-text="count"></span>/300 karakter</span>
                        </div>
                        <textarea id="notes"
                                  name="notes"
                                  rows="2"
                                  maxlength="300"
                                  @input="count = $el.value.length"
                                  placeholder="Permintaan khusus atau catatan lainnya..."
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
                <div class="rounded-xl border border-border bg-surface-2 dark:bg-[#0E131F] p-5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-text">Rincian Pembayaran</h3>
                    <dl class="mt-3 space-y-2 text-xs divide-y divide-border">
                        <div class="flex justify-between py-1.5 text-text-muted">
                            <span>Paket (<span x-text="package === 'private' ? 'Sewa Studio Privat' : (package === 'couple' ? 'Paket Couple (2 orang)' : pax + ' orang reguler')"></span>)</span>
                            <span class="font-tabular font-bold text-text" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(ticketTotal)}`"></span>
                        </div>
                        <div x-show="snackCount > 0" class="flex justify-between py-1.5 text-text-muted">
                            <span>Pesanan Jajan (<span x-text="snackCount"></span> item)</span>
                            <span class="font-tabular font-bold text-text" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(snackTotal)}`"></span>
                        </div>
                        <div x-show="promoDiscount > 0" class="flex justify-between py-1.5 text-emerald-700 dark:text-emerald-400 font-bold">
                            <span>Diskon Voucher (<span x-text="promoApplied"></span>)</span>
                            <span class="font-tabular" x-text="`-Rp ${new Intl.NumberFormat('id-ID').format(promoDiscount)}`"></span>
                        </div>
                        <div class="flex justify-between pt-3 text-base font-black text-text">
                            <span>Total Tagihan</span>
                            <span class="font-tabular text-amber-700 dark:text-amber-400" x-text="`Rp ${new Intl.NumberFormat('id-ID').format(grandTotal)}`"></span>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- LIVE-UPDATE ACTION BAR (STICKY BOTTOM DI MOBILE, INLINE CARD NYAMAN DI DESKTOP) --}}
            <aside class="sticky bottom-0 z-30 -mx-4 border-t border-border bg-surface px-4 py-3 shadow-lg pb-[max(env(safe-area-inset-bottom,0px),0.75rem)] sm:static sm:z-auto sm:mx-0 sm:mt-8 sm:rounded-2xl sm:border sm:border-border sm:bg-surface sm:p-4 sm:shadow-sm sm:pb-4">
                <div class="mx-auto flex max-w-xl items-center justify-between gap-3 sm:gap-4">
                    {{-- Total Price Column (Always 1 line, no wrapping, solid opaque background) --}}
                    <div class="shrink-0 flex flex-col justify-center pr-1 whitespace-nowrap min-w-0">
                        <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-text-muted leading-tight block">TOTAL</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="font-tabular text-base sm:text-2xl font-black text-amber-600 dark:text-amber-400 leading-tight whitespace-nowrap tracking-tight"
                                  x-text="`Rp ${new Intl.NumberFormat('id-ID').format(grandTotal)}`">Rp 0</span>
                            <span x-show="promoDiscount > 0" class="badge-success-soft rounded px-1.5 py-0.5 text-[9px] font-bold shrink-0 whitespace-nowrap">
                                Hemat Rp <span x-text="new Intl.NumberFormat('id-ID').format(promoDiscount)"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Navigation Buttons --}}
                    <div class="flex items-center gap-2 shrink-0">
                        {{-- Tombol Kembali --}}
                        <button type="button"
                                x-show="step > 2"
                                @click="previousStep()"
                                class="inline-flex min-h-11 w-11 sm:w-auto sm:px-4 items-center justify-center gap-1.5 rounded-xl border border-border-input bg-surface-2 hover:bg-surface text-text hover:border-amber-400 transition active:scale-95 shrink-0"
                                title="Kembali ke langkah sebelumnya"
                                aria-label="Kembali ke langkah sebelumnya">
                            <svg class="size-4 shrink-0 text-text" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                            </svg>
                            <span class="hidden sm:inline text-xs font-bold">Kembali</span>
                        </button>

                        {{-- Tombol Langkah 2: Lanjut ke Menu Jajan / Pembayaran --}}
                        <button type="button"
                                x-show="step === 2"
                                @click="nextStep()"
                                class="btn-gold-cinema inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl px-4 sm:px-6 text-xs sm:text-sm font-black shadow-md transition active:scale-95 shrink-0">
                            <span class="sm:hidden" x-text="hasSnacks ? 'Menu Jajan' : 'Pembayaran'"></span>
                            <span class="hidden sm:inline" x-text="hasSnacks ? 'Lanjut ke Menu Jajan' : 'Lanjut ke Pembayaran'"></span>
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </button>

                        {{-- Tombol Langkah 3 (Jajan): Lanjut ke Pembayaran --}}
                        <button type="button"
                                x-show="step === 3 && hasSnacks"
                                @click="nextStep()"
                                class="btn-gold-cinema inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl px-4 sm:px-6 text-xs sm:text-sm font-black shadow-md transition active:scale-95 shrink-0">
                            <span class="sm:hidden">Pembayaran</span>
                            <span class="hidden sm:inline">Lanjut ke Pembayaran</span>
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </button>

                        {{-- Tombol Submit: Booking & Bayar Sekarang --}}
                        <button type="submit"
                                x-show="step === summaryStep"
                                :disabled="isSubmitting"
                                class="btn-gold-cinema inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 sm:px-6 text-xs sm:text-sm font-black shadow-lg disabled:opacity-50 transition active:scale-95 shrink-0">
                            <span x-show="!isSubmitting" x-text="paymentMethod === 'di_tempat' ? 'Konfirmasi' : 'Bayar Sekarang'" class="sm:hidden"></span>
                            <span x-show="!isSubmitting" x-text="paymentMethod === 'di_tempat' ? 'Konfirmasi Booking' : 'Booking & Bayar Sekarang'" class="hidden sm:inline"></span>
                            <span x-show="isSubmitting" x-cloak>Memproses...</span>
                            <svg x-show="!isSubmitting" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </aside>

            {{-- Modal Catatan Jajan --}}
            <div x-show="noteItemId !== null" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                 @click.self="noteItemId = null">
                <div class="w-full max-w-sm rounded-2xl border border-border bg-surface p-5 shadow-2xl">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-sm font-black text-text">Catatan Khusus Jajanan</h3>
                            <p class="mt-1 text-xs text-text-muted">Contoh: es sedikit, pedas dipisah, atau tanpa gula.</p>
                        </div>
                        <span class="text-[11px] text-text-muted"><span x-text="(noteValue || '').length"></span>/250</span>
                    </div>
                    <textarea x-model="noteValue"
                              rows="3"
                              maxlength="250"
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

    {{-- Shared Trailer Modal --}}
    <x-public.trailer-modal />
@endsection
