<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use App\Services\BookingService;
use App\Services\CinemaSettings;
use App\Services\ScheduleResolver;
use App\Services\SessionQuorumService;
use App\Services\SnackOrderService;
use App\Support\WhatsAppNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicBookingController extends Controller
{
    public function create(
        Session $session,
        CinemaSettings $settings,
        SnackOrderService $snackOrders,
    ): View|RedirectResponse {
        app(SessionQuorumService::class)->resolveAll();
        $session->refresh();

        $session->load('film');
        $session->loadSum('occupiedBookings as occupied_seats', 'seats_reserved');
        $deadline = $session->startsAt()->subMinutes($settings->integer('booking_cutoff_minutes', 30));

        if (
            $session->status !== 'tersedia'
            || $session->isPrivateActive()
            || $session->remainingSeats() === 0
            || now()->greaterThanOrEqualTo($deadline)
        ) {
            return redirect()
                ->route('schedule.date', ['date' => $session->session_date])
                ->with('error', 'Sesi ini sudah tidak bisa dibooking. Pilih jadwal lain, ya.');
        }

        $dayType = app(ScheduleResolver::class)->dayTypeFor($session->startsAt());
        $unitPrice = $settings->ticketPriceForDayType($dayType);
        $couplePrice = $settings->couplePrice($dayType);
        $privatePrice = $settings->privatePriceForDayType($dayType);

        $tallies = $session->candidateFilmTallies()->keyBy('film_id');
        $allFilms = Film::query()->where('is_active', true)->orderBy('title')->get();

        $filmList = $allFilms->map(function (Film $film) use ($session, $tallies) {
            $tally = $tallies->get($film->id);
            $seats = $tally ? $tally['seats'] : 0;
            $deadlockCheck = $session->canAcceptRegularBooking($film->id, 1);

            return [
                'id' => $film->id,
                'title' => $film->title,
                'genre' => $film->genre,
                'year' => $film->release_year,
                'duration' => $film->duration_minutes,
                'age_rating' => $film->age_rating,
                'synopsis' => $film->synopsis,
                'poster_url' => $film->poster_url,
                'seats' => $seats,
                'can_pick' => $session->isConfirmed() ? ($session->film_id === $film->id) : $deadlockCheck['allowed'],
                'disabled_reason' => $session->isConfirmed()
                    ? ($session->film_id !== $film->id ? 'Sesi sudah terkunci untuk film lain' : null)
                    : ($deadlockCheck['allowed'] ? null : $deadlockCheck['reason']),
            ];
        });

        $coupleCheck = $session->canAcceptCoupleBooking();
        $privateCheck = $session->canAcceptPrivateBooking();
        $canReguler = ! $session->isPrivateActive() && $session->remainingSeats() > 0;
        $regulerDisabledReason = $session->isPrivateActive()
            ? 'Sesi ini sudah disewa private.'
            : ($session->remainingSeats() === 0 ? 'Kursi pada sesi ini sudah penuh.' : null);

        return view('public.booking-form', [
            'session' => $session,
            'films' => $session->isConfirmed()
                ? collect([$session->film])->filter()
                : $allFilms,
            'filmList' => $filmList,
            'candidateTallies' => $tallies,
            'quorum' => $session->quorum(),
            'unitPrice' => $unitPrice,
            'couplePrice' => $couplePrice,
            'privatePrice' => $privatePrice,
            'canCouple' => $coupleCheck['allowed'],
            'coupleDisabledReason' => $coupleCheck['reason'],
            'canPrivate' => $privateCheck['allowed'],
            'privateDisabledReason' => $privateCheck['reason'],
            'canReguler' => $canReguler,
            'regulerDisabledReason' => $regulerDisabledReason,
            'paymentMethods' => $settings->availablePaymentMethodsForSession($session),
            'paymentInstructions' => $settings->string('payment_instructions'),
            'paymentTimeLimit' => $settings->paymentTimeLimitMinutes(),
            'onlinePaymentCutoff' => $settings->onlinePaymentCutoffMinutes(),
            'occupiedSeats' => $session->seatCapacity() - $session->remainingSeats(),
            'snackCategories' => $this->snackMenu($snackOrders, $session),
            'snacksEnabled' => $snackOrders->enabled(),
            'snackMaxQty' => $settings->integer('snack_max_qty_per_item', 10),
        ]);
    }

    public function availabilityJson(
        Session $session,
        CinemaSettings $settings,
    ): JsonResponse {
        $startsAt = $session->startsAt();
        $cutoffMinutes = $settings->integer('booking_cutoff_minutes', 30);
        $deadline = $startsAt->subMinutes($cutoffMinutes);

        $isPast = $startsAt->lessThanOrEqualTo(now());
        $isClosed = $session->status !== 'tersedia'
            || now()->greaterThanOrEqualTo($deadline);

        $occupiedSeats = (int) $session->occupiedBookings()->sum('seats_reserved');
        $seatCapacity = $session->seatCapacity();
        $remainingSeats = $session->remainingSeats();

        $isPrivateActive = $session->isPrivateActive();
        $hasActivePerPerson = $session->hasActivePerPersonBookings();

        $coupleCheck = $session->canAcceptCoupleBooking();
        $privateCheck = $session->canAcceptPrivateBooking();

        $canPrivate = ! $isPast
            && ! $isClosed
            && $privateCheck['allowed'];

        $canCouple = ! $isPast
            && ! $isClosed
            && $coupleCheck['allowed'];

        $canPerPerson = ! $isPast
            && ! $isClosed
            && ! $isPrivateActive
            && $remainingSeats > 0;

        $privateDisabledReason = match (true) {
            $isPast => 'Sesi sudah lewat.',
            $isClosed => 'Booking untuk sesi ini sudah ditutup.',
            default => $privateCheck['reason'],
        };

        $coupleDisabledReason = match (true) {
            $isPast => 'Sesi sudah lewat.',
            $isClosed => 'Booking untuk sesi ini sudah ditutup.',
            default => $coupleCheck['reason'],
        };

        $perPersonDisabledReason = match (true) {
            $isPast => 'Sesi sudah lewat.',
            $isClosed => 'Booking untuk sesi ini sudah ditutup.',
            $isPrivateActive => 'Sesi ini sudah disewa private.',
            $remainingSeats === 0 => 'Kursi pada sesi ini sudah penuh.',
            default => null,
        };

        return response()->json([
            'session_id' => $session->id,
            'status' => $session->status,
            'is_available' => ! $isPast && ! $isClosed && ($canPrivate || $canCouple || $canPerPerson),
            'seat_capacity' => $seatCapacity,
            'occupied_seats' => $occupiedSeats,
            'remaining_seats' => $remainingSeats,
            'is_private_active' => $isPrivateActive,
            'has_active_per_person' => $hasActivePerPerson,
            'can_private' => $canPrivate,
            'can_couple' => $canCouple,
            'can_per_orang' => $canPerPerson,
            'can_reguler' => $canPerPerson,
            'private_disabled_reason' => $privateDisabledReason,
            'couple_disabled_reason' => $coupleDisabledReason,
            'per_orang_disabled_reason' => $perPersonDisabledReason,
            'reguler_disabled_reason' => $perPersonDisabledReason,
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function store(
        StoreBookingRequest $request,
        Session $session,
        BookingService $bookingService,
    ): RedirectResponse {
        $booking = $bookingService->create(
            $session,
            $request->validated(),
        );

        $request->session()->put('verified_booking_'.$booking->code, true);

        if ($booking->payment_method === 'di_tempat') {
            return redirect()->route('booking.ticket', ['code' => $booking->code]);
        }

        // Jika metode online tapi sesi belum pasti tayang (menunggu kuorum), tahan dan arahkan ke tiket
        if ($booking->payment_expires_at === null) {
            return redirect()->route('booking.ticket', ['code' => $booking->code]);
        }

        return redirect()->route('booking.payment', ['code' => $booking->code]);
    }

    public function paymentView(
        Request $request,
        string $code,
        CinemaSettings $settings,
        BookingService $bookingService,
    ): Response|RedirectResponse {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->with(['session', 'film', 'items' => fn ($query) => $query->where('status', '!=', 'dibatalkan')])
            ->firstOrFail();

        if ($redirect = $this->authorizeBookingAccess($request, $booking, redirectIfQueried: true)) {
            return $redirect;
        }

        $booking = $bookingService->expireIfOverdue($booking);

        if ($booking->payment_method === 'di_tempat') {
            return redirect()->route('booking.ticket', ['code' => $booking->code]);
        }

        // Jika sesi belum Pasti Tayang, jangan tampilkan halaman transfer/QRIS
        if (! $booking->session->isConfirmed() && $booking->payment_expires_at === null) {
            return redirect()->route('booking.ticket', ['code' => $booking->code]);
        }

        if ($booking->status !== 'menunggu_pembayaran') {
            return redirect()->route('booking.ticket', ['code' => $booking->code]);
        }

        return response()
            ->view('public.payment', [
                'booking' => $booking,
                'whatsAppUrl' => $this->paymentWhatsAppUrl($booking, $settings),
                'hasQrisImage' => $settings->hasValidQrisImage(),
                'qrisMerchantName' => $settings->qrisMerchantName(),
                'qrisPaymentNotes' => $settings->qrisPaymentNotes(),
                'qrisVersion' => $settings->qrisImageVersion(),
                'transferAccounts' => $settings->transferAccounts(),
                'transferPaymentNotes' => $settings->transferPaymentNotes(),
                'maxPaymentProofKb' => $settings->maxPaymentProofKb(),
            ])
            ->header('Referrer-Policy', 'same-origin');
    }

    public function ticketView(
        Request $request,
        string $code,
        CinemaSettings $settings,
        BookingService $bookingService,
    ): Response|RedirectResponse {
        app(SessionQuorumService::class)->resolveAll();

        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->with([
                'session.film',
                'session.occupiedBookings.film',
                'film',
                'items.menuItem',
            ])
            ->firstOrFail();

        if ($redirect = $this->authorizeBookingAccess($request, $booking, redirectIfQueried: true)) {
            return $redirect;
        }

        $booking = $bookingService->expireIfOverdue($booking);

        if ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran' && $booking->payment_expires_at !== null && ! $booking->isExpired()) {
            return redirect()->route('booking.payment', ['code' => $booking->code]);
        }

        $session = $booking->session;
        $dayType = app(ScheduleResolver::class)->dayTypeFor($session->startsAt());
        $couplePrice = $settings->couplePrice($dayType);
        $privatePrice = $settings->privatePriceForDayType($dayType);

        $activeItems = $booking->items->where('status', '!=', 'dibatalkan')->values();
        $items = $booking->status === 'dibatalkan'
            ? $booking->items
            : $activeItems;
        $booking->setRelation('items', $items);
        $longestPrep = (int) $activeItems
            ->map(fn ($item): int => (int) ($item->menuItem?->prep_minutes ?? 0))
            ->max();
        $deadline = $activeItems->isNotEmpty()
            ? app(SnackOrderService::class)->deadlineFor($session, $longestPrep)
            : null;
        $snackLocked = $booking->status === 'dibatalkan'
            || ($deadline !== null && now()->greaterThanOrEqualTo($deadline));

        $cancellationCutoff = $settings->cancellationCutoffHours();
        $canCancel = ! in_array($booking->status, ['dibatalkan', 'kedaluwarsa', 'hadir', 'tidak_hadir'], true)
            && (! $session->isConfirmed() || now()->lessThanOrEqualTo($session->startsAt()->subHours($cancellationCutoff)));

        $candidateTallies = $session->candidateFilmTallies();
        $allFilms = Film::query()->where('is_active', true)->orderBy('title')->get();

        $alternativeSessions = Session::query()
            ->where('status', 'tersedia')
            ->where('id', '!=', $session->id)
            ->whereDate('session_date', '>=', now()->toDateString())
            ->with(['film', 'occupiedBookings'])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->take(10)
            ->get();

        return response()
            ->view('public.ticket', [
                'booking' => $booking,
                'session' => $session,
                'sessionStatus' => $session->statusWaktu(),
                'candidateTallies' => $candidateTallies,
                'allFilms' => $allFilms,
                'alternativeSessions' => $alternativeSessions,
                'couplePrice' => $couplePrice,
                'privatePrice' => $privatePrice,
                'coupleUpgradeDiff' => max(0, $couplePrice - $booking->ticket_total),
                'privateUpgradeDiff' => max(0, $privatePrice - $booking->ticket_total),
                'canUpgradeCouple' => ! $session->isConfirmed() && $booking->seats_reserved <= 2 && $session->canAcceptCoupleBooking($booking->film_id)['allowed'],
                'canUpgradePrivate' => ! $session->isConfirmed() && ($session->occupiedBookings()->where('id', '!=', $booking->id)->count() === 0),
                'ajakTemanUrl' => $this->ajakTemanWhatsAppUrl($booking),
                'whatsAppUrl' => $this->ticketWhatsAppUrl($booking, $settings),
                'snackWhatsAppUrl' => $this->snackWhatsAppUrl($booking, $settings),
                'snackLocked' => $snackLocked,
                'canCancel' => $canCancel,
                'cancellationCutoffHours' => $cancellationCutoff,
            ])
            ->header('Referrer-Policy', 'same-origin');
    }

    public function changeFilm(
        Request $request,
        string $code,
        BookingService $bookingService,
    ): RedirectResponse {
        $booking = Booking::query()->where('code', strtoupper(trim($code)))->firstOrFail();
        $this->authorizeBookingAccess($request, $booking);

        $data = $request->validate([
            'film_id' => ['required', 'integer', 'exists:films,id'],
        ], [
            'film_id.required' => 'Pilih film pengganti.',
            'film_id.exists' => 'Film tidak ditemukan.',
        ]);

        $bookingService->changeFilm($booking, (int) $data['film_id']);

        return redirect()->route('booking.ticket', ['code' => $booking->code])
            ->with('status', 'Pilihan film berhasil diubah.');
    }

    public function reschedule(
        Request $request,
        string $code,
        BookingService $bookingService,
    ): RedirectResponse {
        $booking = Booking::query()->where('code', strtoupper(trim($code)))->firstOrFail();
        $this->authorizeBookingAccess($request, $booking);

        $data = $request->validate([
            'target_session_id' => ['required', 'integer', 'exists:screening_sessions,id'],
            'film_id' => ['nullable', 'integer', 'exists:films,id'],
        ], [
            'target_session_id.required' => 'Pilih sesi tujuan.',
            'target_session_id.exists' => 'Sesi tujuan tidak ditemukan.',
        ]);

        $targetSession = Session::query()->findOrFail((int) $data['target_session_id']);
        $bookingService->reschedule($booking, $targetSession, ! empty($data['film_id']) ? (int) $data['film_id'] : null);

        return redirect()->route('booking.ticket', ['code' => $booking->code])
            ->with('status', 'Jadwal sesi berhasil dipindahkan.');
    }

    public function upgrade(
        Request $request,
        string $code,
        BookingService $bookingService,
    ): RedirectResponse {
        $booking = Booking::query()->where('code', strtoupper(trim($code)))->firstOrFail();
        $this->authorizeBookingAccess($request, $booking);

        $data = $request->validate([
            'target_package' => ['required', 'in:couple,private'],
        ], [
            'target_package.required' => 'Pilih paket upgrade.',
            'target_package.in' => 'Paket upgrade tidak valid.',
        ]);

        $bookingService->upgrade($booking, $data['target_package']);

        return redirect()->route('booking.ticket', ['code' => $booking->code])
            ->with('status', 'Paket berhasil di-upgrade.');
    }

    public function statusJson(
        Request $request,
        string $code,
        BookingService $bookingService,
    ): JsonResponse {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->with(['session', 'film'])
            ->firstOrFail();

        $this->authorizeBookingAccess($request, $booking);
        $booking = $bookingService->expireIfOverdue($booking);

        $session = $booking->session;
        $quorum = $session->quorum();
        $occupiedForFilm = (int) $session->occupiedBookings()->where('film_id', $booking->film_id)->sum('seats_reserved');
        $neededForMyFilm = max(0, $quorum - $occupiedForFilm);

        $statusLabels = [
            'menunggu_pembayaran' => 'Menunggu pembayaran',
            'menunggu_konfirmasi' => 'Menunggu konfirmasi',
            'terkonfirmasi' => 'Terkonfirmasi',
            'kedaluwarsa' => 'Kedaluwarsa',
            'hadir' => 'Tiket sudah dipakai',
            'tidak_hadir' => 'Tidak hadir',
            'dibatalkan' => 'Dibatalkan',
        ];

        $bannerText = match (true) {
            $booking->status === 'dibatalkan' => $booking->notes === 'Dibatalkan: kuorum tidak terpenuhi'
                ? 'Sesi dibatalkan karena kuorum tidak terpenuhi hingga batas konfirmasi.'
                : 'Booking ini telah dibatalkan.',
            $booking->status === 'kedaluwarsa' => 'Batas waktu pembayaran habis dan kursi telah dilepaskan kembali.',
            $booking->needsReselect() => 'Sesi telah Pasti Tayang dengan film lain. Silakan pilih film yang tayang atau pindah ke sesi lain.',
            $session->isConfirmed() => 'Sesi Pasti Tayang: '.($session->film?->title ?? 'Film').'. Tunjukkan kode ini saat datang.',
            $session->isInAgreementWindow() => 'Butuh kesepakatan: Sesi belum mencapai kuorum menjelang jam tayang. Silakan sepakati film atau pindah jadwal.',
            default => 'Menunggu kuorum: butuh '.$neededForMyFilm.' penonton lagi untuk film '.($booking->film?->title ?? 'pilihanmu').'.',
        };

        return response()->json([
            'code' => $booking->code,
            'status' => $booking->status,
            'status_label' => $statusLabels[$booking->status] ?? $booking->status,
            'is_final' => in_array($booking->status, ['terkonfirmasi', 'dibatalkan', 'kedaluwarsa', 'hadir', 'tidak_hadir'], true),
            'banner_text' => $bannerText,
            'needs_payment' => $booking->status === 'menunggu_pembayaran' && ! $booking->isExpired(),
            'is_confirmed' => $session->isConfirmed(),
            'needs_reselect' => $booking->needsReselect(),
            'needed_seats' => $neededForMyFilm,
        ]);
    }

    public function success(
        Request $request,
        string $code,
        BookingService $bookingService,
    ): RedirectResponse {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->firstOrFail();

        $booking = $bookingService->expireIfOverdue($booking);
        $request->session()->put('verified_booking_'.$booking->code, true);

        if ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran' && ! $booking->isExpired()) {
            return redirect()->route('booking.payment', ['code' => $booking->code]);
        }

        return redirect()->route('booking.ticket', ['code' => $booking->code]);
    }

    public function lookup(
        Request $request,
        BookingService $bookingService,
    ): RedirectResponse {
        $data = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:20'],
            'whatsapp' => ['required', 'string', 'max:24'],
        ], [
            'code.required' => 'Kode booking wajib diisi.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
        ])->validate();

        $whatsapp = WhatsAppNumber::normalize($data['whatsapp']);

        if (! WhatsAppNumber::isValid($whatsapp)) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Masukkan nomor WhatsApp Indonesia yang valid.',
            ]);
        }

        $booking = Booking::query()
            ->where('code', strtoupper(trim($data['code'])))
            ->where('whatsapp', $whatsapp)
            ->first();

        if ($booking === null) {
            throw ValidationException::withMessages([
                'code' => 'Kode booking atau nomor WhatsApp tidak cocok.',
            ]);
        }

        $booking = $bookingService->expireIfOverdue($booking);
        $request->session()->put('verified_booking_'.$booking->code, true);

        if ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran' && ! $booking->isExpired()) {
            return redirect()->route('booking.payment', ['code' => $booking->code]);
        }

        return redirect()->route('booking.ticket', ['code' => $booking->code]);
    }

    public function cancel(
        Request $request,
        string $code,
        BookingService $bookingService,
    ): RedirectResponse {
        $data = $request->validate([
            'whatsapp' => ['required', 'string', 'max:24'],
        ], [
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi untuk membatalkan booking.',
        ]);

        $bookingService->cancel($code, $data['whatsapp']);

        return redirect()
            ->route('booking.lookup.form')
            ->with('status', 'Booking berhasil dibatalkan.');
    }

    public function lookupForm(): View
    {
        return view('public.booking-status');
    }

    public function uploadProof(
        Request $request,
        string $code,
        CinemaSettings $settings,
    ): RedirectResponse {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->firstOrFail();

        if ($booking->payment_method === 'di_tempat') {
            throw ValidationException::withMessages([
                'payment_proof' => 'Metode bayar di tempat tidak memerlukan bukti transfer.',
            ]);
        }

        if ($booking->status === 'kedaluwarsa' || $booking->status === 'dibatalkan') {
            throw ValidationException::withMessages([
                'payment_proof' => 'Booking sudah kedaluwarsa atau dibatalkan.',
            ]);
        }

        $maxKb = $settings->maxPaymentProofKb();
        $maxLabel = $maxKb >= 1024 ? round($maxKb / 1024, 1).' MB' : $maxKb.' KB';

        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            if (! $file->isValid()) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'Ukuran file bukti pembayaran melebihi batas unggahan server (upload_max_filesize / post_max_size). Silakan unggah file dengan ukuran lebih kecil.',
                ]);
            }
        }

        $data = $request->validate([
            'whatsapp' => ['required', 'string', 'max:24'],
            'payment_proof' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
        ], [
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi untuk verifikasi.',
            'payment_proof.required' => 'Pilih file bukti pembayaran terlebih dahulu.',
            'payment_proof.image' => 'File bukti pembayaran harus berupa gambar.',
            'payment_proof.mimes' => 'Format file bukti pembayaran harus JPG, JPEG, PNG, atau WEBP.',
            'payment_proof.max' => 'Ukuran file bukti pembayaran maksimal '.$maxLabel.'.',
        ]);

        $normalized = WhatsAppNumber::normalize($data['whatsapp']);

        if ($normalized !== $booking->whatsapp) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Nomor WhatsApp tidak cocok dengan nomor pemesan booking ini.',
            ]);
        }

        $disk = Storage::disk('local');
        if ($booking->payment_proof_path && $disk->exists($booking->payment_proof_path)) {
            $disk->delete($booking->payment_proof_path);
        }

        $path = $request->file('payment_proof')->store('payment-proofs', 'local');
        $booking->update([
            'payment_proof_path' => $path,
            'payment_proof_uploaded_at' => now(),
            'status' => 'menunggu_konfirmasi',
            'proof_rejection_note' => null,
        ]);

        $request->session()->put('verified_booking_'.$booking->code, true);

        return redirect()
            ->route('booking.ticket', ['code' => $booking->code])
            ->with('status', 'Bukti bayar terkirim. Admin akan memeriksa dan mengonfirmasi tiketmu.');
    }

    public function showProof(Request $request, string $code): Response
    {
        $booking = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->firstOrFail();

        $this->authorizeBookingAccess($request, $booking);

        if (! $booking->payment_proof_path || ! Storage::disk('local')->exists($booking->payment_proof_path)) {
            abort(404, 'Bukti pembayaran tidak ditemukan.');
        }

        return Storage::disk('local')->response($booking->payment_proof_path);
    }

    public function qrisImage(Request $request, CinemaSettings $settings): Response
    {
        $path = $settings->string('qris_image_path');
        $disk = Storage::disk('local');

        if ($path === '' || ! $disk->exists($path)) {
            abort(404, 'Gambar QRIS belum tersedia.');
        }

        $mime = $disk->mimeType($path) ?? 'image/png';
        $content = $disk->get($path);
        $headers = [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ];

        if ($request->boolean('download')) {
            $extension = match ($mime) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $filename = 'QRIS-SynemaX-'.$settings->qrisMerchantName().'.'.$extension;
            $headers['Content-Disposition'] = 'attachment; filename="'.$filename.'"';
        }

        return response($content, 200, $headers);
    }

    private function authorizeBookingAccess(Request $request, Booking $booking, bool $redirectIfQueried = false): ?RedirectResponse
    {
        $hasWaQuery = $request->query->has('whatsapp') || $request->query->has('wa');

        if ($hasWaQuery) {
            $waParam = $request->query('whatsapp') ?? $request->query('wa');
            $normalizedWa = $waParam ? WhatsAppNumber::normalize((string) $waParam) : null;

            if ($normalizedWa !== null && $normalizedWa === $booking->whatsapp) {
                $request->session()->put('verified_booking_'.$booking->code, true);

                if ($redirectIfQueried) {
                    return redirect()->to($request->url());
                }

                return null;
            }

            abort(403, 'Akses ditolak. Silakan cek booking dengan nomor WhatsApp pemesan.');
        }

        $isVerifiedCustomer = $request->session()->get('verified_booking_'.$booking->code) === true;
        $isAdmin = (bool) $request->user()?->is_admin;

        if (! $isAdmin && ! $isVerifiedCustomer) {
            abort(403, 'Akses ditolak. Silakan cek booking dengan nomor WhatsApp pemesan.');
        }

        return null;
    }

    private function paymentWhatsAppUrl(Booking $booking, CinemaSettings $settings): ?string
    {
        $adminWhatsApp = preg_replace('/\D+/', '', $settings->string('admin_whatsapp'));

        if ($adminWhatsApp === '') {
            return null;
        }

        $paymentMethodLabel = match ($booking->payment_method) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            default => 'Bayar di tempat',
        };

        $lines = [
            'Halo admin SynemaX, saya ingin kirim bukti bayar:',
            'Kode: '.$booking->code,
            'Nama: '.$booking->customer_name,
            'Film: '.($booking->film?->title ?? 'Film sesi'),
            'Jadwal: '.$booking->session->session_date.' · '.substr((string) $booking->session->start_time, 0, 5).' WIB',
            'Total: Rp '.number_format($booking->total_price, 0, ',', '.'),
            'Metode bayar: '.$paymentMethodLabel,
            'Bukti pembayaran saya lampirkan di pesan WhatsApp ini.',
        ];

        return 'https://wa.me/'.$adminWhatsApp.'?text='.rawurlencode(implode("\n", $lines));
    }

    private function ticketWhatsAppUrl(Booking $booking, CinemaSettings $settings): ?string
    {
        $adminWhatsApp = preg_replace('/\D+/', '', $settings->string('admin_whatsapp'));

        if ($adminWhatsApp === '') {
            return null;
        }

        $paymentMethodLabel = match ($booking->payment_method) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            default => 'Bayar di tempat',
        };

        $statusLabel = match ($booking->status) {
            'menunggu_pembayaran' => 'Menunggu pembayaran',
            'menunggu_konfirmasi' => 'Menunggu konfirmasi admin',
            'terkonfirmasi' => 'Terkonfirmasi',
            'kedaluwarsa' => 'Kedaluwarsa',
            'hadir' => 'Hadir',
            'tidak_hadir' => 'Tidak hadir',
            'dibatalkan' => 'Dibatalkan',
            default => $booking->status,
        };

        $lines = [
            'Halo admin SynemaX, saya pemesan tiket:',
            'Kode: '.$booking->code,
            'Nama: '.$booking->customer_name,
            'Film: '.($booking->film?->title ?? 'Film sesi'),
            'Jadwal: '.$booking->session->session_date.' · '.substr((string) $booking->session->start_time, 0, 5).' WIB',
            $booking->booking_type === 'private'
                ? 'Jumlah: Sewa Private (6 kursi)'
                : 'Jumlah penonton: '.$booking->pax.' orang',
            'Total: Rp '.number_format($booking->total_price, 0, ',', '.'),
            'Metode: '.$paymentMethodLabel,
            'Status saat ini: '.$statusLabel,
        ];

        return 'https://wa.me/'.$adminWhatsApp.'?text='.rawurlencode(implode("\n", $lines));
    }

    private function ajakTemanWhatsAppUrl(Booking $booking): string
    {
        $filmTitle = $booking->film?->title ?? 'film seru';
        $session = $booking->session;
        $startsAt = $session->startsAt();
        $formattedDate = $startsAt->locale('id')->translatedFormat('l, j M').' jam '.substr((string) $session->start_time, 0, 5).' WIB';

        $occupiedForFilm = (int) $session->occupiedBookings()->where('film_id', $booking->film_id)->sum('seats_reserved');
        $needed = max(1, $session->quorum() - $occupiedForFilm);

        $sessionUrl = route('booking.create', ['session' => $session->id]);
        $message = "Nonton {$filmTitle}, {$formattedDate} di SynemaX, kurang {$needed} orang lagi! Yuk gabung pesan di sini: {$sessionUrl}";

        return 'https://wa.me/?text='.rawurlencode($message);
    }

    private function snackWhatsAppUrl(Booking $booking, CinemaSettings $settings): ?string
    {
        $adminWhatsApp = preg_replace('/\D+/', '', $settings->string('admin_whatsapp'));

        if ($adminWhatsApp === '' || ! WhatsAppNumber::isValid($adminWhatsApp)) {
            return null;
        }

        $items = $booking->items
            ->where('status', '!=', 'dibatalkan')
            ->map(fn ($item): string => $item->name_snapshot.' × '.$item->qty.($item->note ? ' ('.$item->note.')' : ''));
        $message = implode("\n", [
            'Halo admin SynemaX, saya ingin mengajukan perubahan pesanan jajan.',
            'Kode booking: '.$booking->code,
            'Nama: '.$booking->customer_name,
            'Jadwal: '.$booking->session->session_date.' · '.substr((string) $booking->session->start_time, 0, 5).' WIB',
            'Pesanan jajan saat ini: '.($items->isNotEmpty() ? $items->implode(', ') : 'Belum ada jajan'),
            'Saya ingin mengubah pesanan jajan: [tuliskan perubahan yang diinginkan].',
        ]);

        return 'https://wa.me/'.$adminWhatsApp.'?text='.rawurlencode($message);
    }

    private function snackMenu(SnackOrderService $snackOrders, Session $session)
    {
        return MenuCategory::query()
            ->where('is_active', true)
            ->whereHas('items', fn ($query) => $query->where('is_active', true))
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (MenuCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'items' => $category->items->map(fn (MenuItem $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'prep_minutes' => $item->prep_minutes,
                    'photo' => $item->photo_path ? asset('storage/'.$item->photo_path) : null,
                    'available' => $item->canBeOrdered()
                        && now()->lessThan($snackOrders->deadlineFor($session, $item->prep_minutes)),
                    'unavailable_reason' => ! $item->is_available
                        ? 'Habis'
                        : (now()->greaterThanOrEqualTo($snackOrders->deadlineFor($session, $item->prep_minutes))
                            ? 'Batas pesan lewat'
                            : 'Menu tidak aktif'),
                ])->values(),
            ])->values();
    }
}
