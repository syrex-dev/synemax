@extends('layouts.public')

@section('title', 'Pesan Tiket Studio: SynemaX')
@section('meta')
    <meta name="description" content="Pilih paket, film calon/terkunci, denah kursi studio, dan jajan untuk sesi studio mini SynemaX dengan standar bioskop modern.">
@endsection

@section('content')
    @php
        $occupiedCount = 6 - $session->remainingSeats();
        $wizardSteps = [
            1 => 'Jadwal',
            2 => 'Paket / Tipe',
            3 => 'Film',
            4 => 'Jajan',
            5 => 'Ringkasan',
        ];
        $lockedFilm = $session->activeFilm();
        $isConfirmed = $session->isConfirmed();
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

        <form method="POST" action="{{ route('booking.store', ['session' => $session->id]) }}" enctype="multipart/form-data" novalidate
              x-data="{
                  sessionId: @js((string) $session->id),
                  step: @js((int) old('wizard_step', 2)),
                  package: @js(old('package', old('booking_type') === 'private' ? 'private' : (old('booking_type') === 'couple' ? 'couple' : 'reguler'))),
                  type: @js(old('booking_type', 'per_orang')),
                  pax: @js((int) old('pax', 1)),
                  paymentMethod: @js(old('payment_method', $paymentMethods[0] ?? '')),
                  filmId: @js((string) old('film_id', $isConfirmed && $lockedFilm ? $lockedFilm->id : request('film', ''))),
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
                  filmSearch: '',
                  genreFilter: 'Semua',
                  yearFilter: 'Semua',
                  filmDetail: null,
                  noteItemId: null,
                  noteValue: '',
                  basketOpen: false,
                  wizardError: '',
                  isSubmitting: false,
                  storageAvailable: true,

                  // Data peminat per film saat ini { film_id: seats }
                  currentTallies: @js($candidateTallies->mapWithKeys(fn($t, $k) => [$k => (int) $t['seats']])),

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
                      if (this.package === 'private') return; // Private otomatis kunci semua kursi

                      const idx = this.selectedSeats.indexOf(seatId);
                      if (idx > -1) {
                          if (this.package === 'couple') {
                              // Couple wajib 2 kursi
                              return;
                          }
                          if (this.selectedSeats.length > 1) {
                              this.selectedSeats.splice(idx, 1);
                          }
                      } else {
                          if (this.package === 'couple') {
                              if (this.selectedSeats.length >= 2) {
                                  this.selectedSeats.shift(); // Geser kursi
                              }
                              this.selectedSeats.push(seatId);
                          } else {
                              if (this.selectedSeats.length < this.remaining) {
                                  this.selectedSeats.push(seatId);
                              }
                          }
                      }
                      this.pax = Math.max(1, this.selectedSeats.length);
                      this.persist();
                  },

                  syncSeatsWithPackage() {
                      const available = this.seats.filter((_, i) => !this.isSeatOccupied(i)).map(s => s.id);
                      if (this.package === 'private') {
                          this.selectedSeats = [...available];
                          this.pax = 6;
                          this.type = 'private';
                      } else if (this.package === 'couple') {
                          this.selectedSeats = available.slice(0, 2);
                          this.pax = 2;
                          this.type = 'couple';
                      } else {
                          this.type = 'per_orang';
                          this.pax = Math.max(1, Math.min(this.pax || 1, available.length));
                          this.selectedSeats = available.slice(0, this.pax);
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
                          // Couple mensyaratkan tidak ada kursi aktif film lain
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

                      if (totalSeats > 6) {
                          return {
                              allowed: false,
                              isDeadlock: false,
                              reason: 'Kursi tersisa tidak cukup untuk rombongan ini.'
                          };
                      }

                      // (total - max) > (kapasitas - kuorum) = 6 - 4 = 2
                      if ((totalSeats - maxSeats) > (6 - this.quorum)) {
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
                              this.canCouple = Boolean(data.can_couple);
                              this.canReguler = Boolean(data.can_per_orang);
                              this.privateReason = data.private_disabled_reason || 'Sewa Private tidak tersedia.';
                              this.coupleReason = data.couple_disabled_reason || 'Paket Couple tidak tersedia.';
                              this.regulerReason = data.per_orang_disabled_reason || 'Kursi pada sesi ini sudah penuh.';

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

                  matchesFilm(title, genre, year) {
                      const query = this.filmSearch.trim().toLocaleLowerCase('id');
                      const matchesQuery = !query || `${title} ${genre}`.toLocaleLowerCase('id').includes(query);
                      const matchesGenre = this.genreFilter === 'Semua' || this.genreFilter === genre;
                      const matchesYear = this.yearFilter === 'Semua' || String(year || '') === String(this.yearFilter);
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
                      this.filmDetail = null;
                      this.wizardError = '';
                      this.persist();
                  },

                  openNote(id) { this.noteItemId = id; this.noteValue = (this.cart[id] || {}).note || '' },
                  saveNote() {
                      if (this.cart[this.noteItemId]) this.cart[this.noteItemId].note = this.noteValue.trim();
                      this.noteItemId = null;
                      this.persist();
                  },

                  nextStep() {
                      // Validasi Langkah 2 (Pilih Paket & Kursi)
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
                              this.wizardError = this.regulerReason || 'Jumlah kursi tidak valid atau kursi sudah penuh.';
                              return;
                          }
                          this.refreshAvailability();
                          this.step = 3;
                          this.wizardError = '';
                          this.persist();
                          this.scrollToTop();
                          return;
                      }

                      // Validasi Langkah 3 (Pilih Film)
                      if (this.step === 3) {
                          if (!this.filmId) {
                              this.wizardError = 'Silakan pilih salah satu judul film sebelum melanjutkan.';
                              return;
                          }
                          const check = this.getFilmStatus(this.filmId);
                          if (!check.allowed) {
                              this.wizardError = check.reason || 'Pilihan film ini tidak memenuhi syarat sesi.';
                              return;
                          }
                          this.step = this.hasSnacks ? 4 : 5;
                          this.wizardError = '';
                          this.persist();
                          this.scrollToTop();
                          return;
                      }

                      // Validasi Langkah 4 (Jajan)
                      if (this.step === 4) {
                          this.step = 5;
                          this.wizardError = '';
                          this.persist();
                          this.scrollToTop();
                          return;
                      }
                  },

                  previousStep() {
                      if (this.step === 5) {
                          this.step = this.hasSnacks ? 4 : 3;
                      } else if (this.step === 4) {
                          this.step = 3;
                      } else if (this.step === 3) {
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

                  skipSnacks() { this.nextStep() },

                  persist() {
                      if (!this.storageAvailable) return;
                      try {
                          sessionStorage.setItem('synemax:booking-wizard', JSON.stringify({
                              schema: 4,
                              sessionId: this.sessionId,
                              step: this.step,
                              package: this.package,
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
                                  if (['reguler', 'couple', 'private'].includes(saved.package)) {
                                      this.package = saved.package;
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
            <input type="hidden" name="pax" :value="package === 'private' ? 6 : (package === 'couple' ? 2 : pax)">
            <input type="hidden" name="people_count" :value="package === 'private' ? 6 : (package === 'couple' ? 2 : pax)">
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

            {{-- ==================== LANGKAH 2: PILIH PAKET & KURSI ==================== --}}
            <section x-show="step === 2" x-cloak class="mt-6 space-y-5">
                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 class="text-lg font-black text-text">Pilih paket nonton</h2>
                        <span class="text-xs text-text-muted">Langkah 2 dari 5</span>
                    </div>
                    <p class="mt-0.5 text-xs text-text-muted">Tentukan jalur penayangan: kumpulkan kuorum 4 penonton (Reguler), atau langsung pasti tayang (Couple & Private).</p>
                </div>

                {{-- Penjelasan Singkat Aturan Kuorum & Paket --}}
                <div class="rounded-xl border border-amber-400/30 bg-amber-500/5 p-3.5 text-xs text-text-muted flex items-start gap-2.5">
                    <span class="text-amber-400 font-black text-base shrink-0">ℹ️</span>
                    <div>
                        <span class="font-bold text-text">Aturan Tayang Studio:</span>
                        <p class="mt-0.5">Sesi tayang bila satu film mencapai kuorum 4 penonton (Reguler), atau langsung pasti tayang dengan paket Couple (2 orang) & Private (seluruh studio).</p>
                    </div>
                </div>

                {{-- Tiga Kartu Paket: Reguler, Couple, Private --}}
                <div class="grid gap-3 sm:grid-cols-3">
                    {{-- Paket 1: Reguler --}}
                    <div @click="selectPackage('reguler')"
                         class="cursor-pointer rounded-2xl border p-4 transition flex flex-col justify-between relative"
                         :class="package === 'reguler' ? 'border-amber-400 bg-amber-500/10 shadow-sm' : (!canReguler ? 'opacity-40 cursor-not-allowed border-border bg-surface' : 'border-border bg-surface hover:border-amber-400/50')">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="rounded bg-slate-800 border border-slate-700 px-2 py-0.5 text-[10px] font-bold text-slate-300">
                                    Perlu 4 penonton
                                </span>
                                <input type="radio" name="package_choice" value="reguler" x-model="package" class="sr-only">
                            </div>
                            <h3 class="mt-2 text-base font-black text-text">Reguler (Per orang)</h3>
                            <p class="mt-0.5 font-tabular text-sm font-black text-amber-400">
                                Rp {{ number_format($unitPrice, 0, ',', '.') }}<span class="text-[11px] font-normal text-text-muted">/kursi</span>
                            </p>
                            <p class="mt-2 text-[11px] leading-relaxed text-text-muted">
                                1–6 orang. Tayang jika film pilihan mencapai kuorum 4 kursi.
                            </p>
                        </div>
                        <div class="mt-3 border-t border-border pt-2 text-[11px] text-text-muted" x-show="package === 'reguler'">
                            <span class="font-bold text-text" x-text="`${pax} Kursi dipilih`"></span>
                        </div>
                    </div>

                    {{-- Paket 2: Couple --}}
                    <div @click="selectPackage('couple')"
                         class="cursor-pointer rounded-2xl border p-4 transition flex flex-col justify-between relative"
                         :class="package === 'couple' ? 'border-amber-400 bg-amber-500/10 shadow-sm' : (!canCouple ? 'opacity-40 cursor-not-allowed border-border bg-surface' : 'border-border bg-surface hover:border-amber-400/50')">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="rounded bg-emerald-500/20 border border-emerald-500/40 px-2 py-0.5 text-[10px] font-black text-emerald-400">
                                    Pasti tayang
                                </span>
                                <input type="radio" name="package_choice" value="couple" x-model="package" :disabled="!canCouple" {{ ! $canCouple ? 'disabled' : '' }} aria-describedby="couple-desc" class="sr-only">
                            </div>
                            <h3 class="mt-2 text-base font-black text-text">Couple</h3>
                            <p class="mt-0.5 font-tabular text-sm font-black text-amber-400">
                                Rp {{ number_format($couplePrice, 0, ',', '.') }}<span class="text-[11px] font-normal text-text-muted"> (2 orang)</span>
                            </p>
                            <p class="mt-2 text-[11px] leading-relaxed text-text-muted">
                                2 orang, langsung pasti tayang. Penonton lain masih boleh bergabung.
                            </p>
                        </div>
                        <div class="mt-3 border-t border-border pt-2">
                            <span x-show="canCouple" class="text-[11px] font-bold text-emerald-400">✓ Tersedia</span>
                            <span id="couple-desc" x-show="!canCouple" class="text-[10px] leading-tight text-danger-text block" x-text="coupleReason || 'Tidak tersedia'">
                                {{ $coupleDisabledReason ?? 'Pilih slot lain atau hubungi admin.' }}
                            </span>
                        </div>
                    </div>

                    {{-- Paket 3: Private (Memenuhi assertSee('Sewa studio privat')) --}}
                    <div @click="selectPackage('private')"
                         class="cursor-pointer rounded-2xl border p-4 transition flex flex-col justify-between relative"
                         :class="package === 'private' ? 'border-amber-400 bg-amber-500/10 shadow-sm' : (!canPrivate ? 'opacity-40 cursor-not-allowed border-border bg-surface' : 'border-border bg-surface hover:border-amber-400/50')">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="rounded bg-amber-500/20 border border-amber-500/40 px-2 py-0.5 text-[10px] font-black text-amber-400">
                                    Pasti tayang
                                </span>
                                <input type="radio" name="package_choice" value="private" x-model="package" :disabled="!canPrivate" {{ ! $canPrivate ? 'disabled' : '' }} aria-describedby="private-desc" class="sr-only">
                            </div>
                            <h3 class="mt-2 text-base font-black text-text">Sewa studio privat</h3>
                            <p class="mt-0.5 font-tabular text-sm font-black text-amber-400">
                                Rp {{ number_format($privatePrice, 0, ',', '.') }}<span class="text-[11px] font-normal text-text-muted"> (seluruh studio)</span>
                            </p>
                            <p class="mt-2 text-[11px] leading-relaxed text-text-muted">
                                1–6 orang. Seluruh studio eksklusif untuk Anda, bebas pilih film tanpa penonton lain.
                            </p>
                        </div>
                        <div class="mt-3 border-t border-border pt-2">
                            <span x-show="canPrivate" class="text-[11px] font-bold text-emerald-400">✓ Studio kosong</span>
                            <span id="private-desc" x-show="!canPrivate" class="text-[10px] leading-tight text-danger-text block" x-text="privateReason || 'Tidak tersedia'">
                                {{ $privateDisabledReason ?? 'Sudah ada pemesan di sesi ini.' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- DENAH KURSI MINI BIOSKOP DENGAN LAYAR MELENGKUNG --}}
                <div class="rounded-2xl border border-white/10 bg-gradient-to-b from-[#111726] to-[#0A0E18] p-5 shadow-inner">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Denah Kursi Studio Mini</span>
                        <span class="text-xs text-slate-300 font-medium"
                              x-text="package === 'private' ? 'Seluruh 6 kursi studio otomatis terkunci' : (package === 'couple' ? 'Pilih 2 kursi bersebelahan' : `Pilih ${pax} kursi`)"></span>
                    </div>

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
                                        :disabled="isSeatOccupied(idx) || package === 'private'"
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
                                        :disabled="isSeatOccupied(idx + 3) || package === 'private'"
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
                            <span class="text-slate-500">Terisi</span>
                        </div>
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

            {{-- ==================== LANGKAH 3: PILIH FILM ==================== --}}
            <section x-show="step === 3" x-cloak class="mt-6 space-y-4">
                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 class="text-lg font-black text-text">Pilih film tontonan</h2>
                        <span class="text-xs text-text-muted">Langkah 3 dari 5</span>
                    </div>
                    <p class="mt-0.5 text-xs text-text-muted">
                        @if ($isConfirmed)
                            Sesi ini telah terkonfirmasi pasti tayang. Semua penonton bergabung menonton film terkunci.
                        @else
                            Pilih film calon yang Anda inginkan. Perhatikan jumlah peminat saat ini untuk mempercepat kuorum!
                        @endif
                    </p>
                </div>

                @if ($isConfirmed && $lockedFilm)
                    {{-- Banner Film Terkunci --}}
                    <div class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-5">
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-emerald-400 text-black px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider">
                                Film Terkunci · Pasti Tayang
                            </span>
                        </div>
                        <h3 class="mt-2 text-xl font-black text-text">{{ $lockedFilm->title }}</h3>
                        <p class="mt-1 text-xs text-text-muted">
                            Sesi ini telah memenuhi syarat dan pasti diputar. Kursi baru yang dipesan bergabung menonton film ini.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-xs font-bold text-emerald-400">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Film ini otomatis terpilih untuk pesanan Anda.</span>
                        </div>
                    </div>
                @else
                    {{-- Search & Filter Genre/Year --}}
                    <div class="space-y-3">
                        <input id="film-search"
                               x-model="filmSearch"
                               type="search"
                               autocomplete="off"
                               placeholder="Cari judul atau genre film..."
                               class="min-h-11 w-full rounded-xl border border-border-input bg-surface px-4 text-sm text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400">

                        <div class="flex gap-1.5 overflow-x-auto pb-1 scrollbar-none" aria-label="Filter genre film">
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

                        @php($years = $films->pluck('release_year')->filter()->unique()->sortDesc()->values())
                        @if ($years->count() > 1)
                            <div class="flex gap-1.5 overflow-x-auto pb-1 scrollbar-none" aria-label="Filter tahun rilis film">
                                <button type="button"
                                        @click="yearFilter = 'Semua'"
                                        class="min-h-7 shrink-0 rounded-md border px-2.5 text-[11px] font-semibold transition"
                                        :class="yearFilter === 'Semua' ? 'border-amber-400 bg-amber-500/20 text-amber-500' : 'border-border bg-surface text-text-muted hover:border-amber-400/60'">
                                    Semua Tahun
                                </button>
                                @foreach ($years as $yr)
                                    <button type="button"
                                            @click="yearFilter = @js((string) $yr)"
                                            class="min-h-7 shrink-0 rounded-md border px-2.5 text-[11px] font-semibold transition"
                                            :class="yearFilter === @js((string) $yr) ? 'border-amber-400 bg-amber-500/20 text-amber-500' : 'border-border bg-surface text-text-muted hover:border-amber-400/60'">
                                        {{ $yr }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        {{-- Grid Pilihan Film --}}
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($films as $film)
                                @php($tally = $candidateTallies->get($film->id)['seats'] ?? 0)
                                <article x-show="matchesFilm(@js($film->title), @js($film->genre), @js($film->release_year))"
                                         class="flex flex-col justify-between rounded-xl border bg-surface p-2.5 transition"
                                         :class="filmId === '{{ $film->id }}'
                                             ? 'border-amber-400 shadow-md ring-1 ring-amber-400'
                                             : (!getFilmStatus({{ $film->id }}).allowed ? 'border-border opacity-70' : 'border-border hover:border-amber-400/50')">
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

                                            {{-- Chip Peminat Film Calon --}}
                                            <div class="absolute bottom-1.5 inset-x-1.5">
                                                @if ($tally > 0)
                                                    <span class="inline-flex w-full items-center justify-center rounded bg-amber-500/90 px-1.5 py-0.5 text-[10px] font-black text-black shadow">
                                                        🔥 {{ $tally }} peminat
                                                    </span>
                                                @else
                                                    <span class="inline-flex w-full items-center justify-center rounded bg-black/70 px-1.5 py-0.5 text-[10px] font-medium text-slate-300">
                                                        Belum ada peminat
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <h3 class="line-clamp-2 text-xs font-bold text-text sm:text-sm">{{ $film->title }}</h3>
                                            <p class="mt-0.5 line-clamp-1 text-[11px] text-text-muted">{{ $film->genre }}, {{ $film->duration_minutes }}m</p>
                                        </div>
                                    </button>

                                    {{-- Keterangan Pagar Anti Buntu / Penolakan --}}
                                    <div class="mt-2 pt-2 border-t border-border">
                                        <template x-if="!getFilmStatus({{ $film->id }}).allowed">
                                            <p class="text-[10px] leading-tight text-danger-text font-semibold mb-2"
                                               x-text="getFilmStatus({{ $film->id }}).reason"></p>
                                        </template>

                                        <button type="button"
                                                @click="chooseFilm({{ $film->id }})"
                                                :disabled="!getFilmStatus({{ $film->id }}).allowed"
                                                class="inline-flex min-h-10 w-full items-center justify-center rounded-lg px-2 text-xs font-bold transition disabled:opacity-40 disabled:cursor-not-allowed"
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
                            <span>Paket (<span x-text="package === 'private' ? 'Sewa Studio Privat' : (package === 'couple' ? 'Paket Couple (2 Kursi)' : pax + ' Kursi Reguler')"></span>)</span>
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
                                class="btn-gold-cinema inline-flex min-h-11 items-center justify-center rounded-xl px-5 text-xs font-black shadow-md">
                            Lanjut ke Pilih Film
                        </button>

                        <button type="button"
                                x-show="step === 3"
                                @click="nextStep()"
                                :disabled="!filmId || !getFilmStatus(filmId).allowed"
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

            {{-- Modal Detail Film --}}
            <div x-show="filmDetail !== null" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                 @click.self="filmDetail = null">
                <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="rounded bg-amber-400 text-black px-2 py-0.5 text-[10px] font-black uppercase" x-text="filmDetail?.rating"></span>
                            <h3 class="mt-2 text-lg font-black text-text" x-text="filmDetail?.title"></h3>
                            <p class="text-xs text-text-muted" x-text="`${filmDetail?.genre} · ${filmDetail?.duration} Menit`"></p>
                        </div>
                        <button type="button" @click="filmDetail = null" class="text-text-muted hover:text-text p-1">✕</button>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-text-muted" x-text="filmDetail?.synopsis || 'Sinopsis belum tersedia.'"></p>
                    <div class="mt-5 flex gap-2">
                        <button type="button" @click="filmDetail = null"
                                class="flex-1 rounded-xl border border-border p-2.5 text-xs font-bold text-text">
                            Tutup
                        </button>
                        <button type="button"
                                @click="chooseFilm(filmDetail.id)"
                                :disabled="filmDetail && !getFilmStatus(filmDetail.id).allowed"
                                class="btn-gold-cinema flex-1 rounded-xl p-2.5 text-xs font-bold disabled:opacity-40">
                            Pilih Film Ini
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
