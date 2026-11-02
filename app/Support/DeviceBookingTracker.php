<?php

namespace App\Support;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeviceBookingTracker
{
    public const COOKIE_NAME = 'synemax_device_bookings';

    public const COOKIE_MINUTES = 525600; // 1 year

    public const MAX_STORED = 20;

    /**
     * Get stored booking entries from device cookie.
     *
     * @return list<array{code: string, token: string, created_at: int}>
     */
    public static function getEntries(?Request $request = null): array
    {
        $request = $request ?? request();
        $raw = $request->cookie(self::COOKIE_NAME);

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $valid = [];
        foreach ($decoded as $item) {
            if (is_array($item) && ! empty($item['code'])) {
                $valid[] = [
                    'code' => strtoupper(trim((string) $item['code'])),
                    'token' => (string) ($item['token'] ?? ''),
                    'created_at' => (int) ($item['created_at'] ?? time()),
                ];
            } elseif (is_string($item) && trim($item) !== '') {
                // Support legacy format where cookie was just an array of codes
                $valid[] = [
                    'code' => strtoupper(trim($item)),
                    'token' => '',
                    'created_at' => time(),
                ];
            }
        }

        return array_slice($valid, 0, self::MAX_STORED);
    }

    /**
     * Get list of booking codes stored on this device.
     *
     * @return list<string>
     */
    public static function getCodes(?Request $request = null): array
    {
        $entries = self::getEntries($request);

        return array_values(array_unique(array_map(fn ($e) => $e['code'], $entries)));
    }

    /**
     * Add or update a booking on the device signed cookie.
     */
    public static function addBooking(Booking $booking, ?string $plainToken = null, ?Request $request = null): string
    {
        $code = strtoupper(trim($booking->code));

        if ($plainToken === null || trim($plainToken) === '') {
            $plainToken = Str::random(40);
            $tokenHash = hash('sha256', $plainToken);

            try {
                if (Schema::hasColumn('bookings', 'access_token_hash')) {
                    $booking->updateQuietly([
                        'access_token_hash' => $tokenHash,
                    ]);
                }
            } catch (\Throwable) {
                // Silently ignore if column has not been migrated yet
            }
        } else {
            $tokenHash = hash('sha256', $plainToken);
            if ($booking->access_token_hash !== $tokenHash) {
                try {
                    if (Schema::hasColumn('bookings', 'access_token_hash')) {
                        $booking->updateQuietly([
                            'access_token_hash' => $tokenHash,
                        ]);
                    }
                } catch (\Throwable) {
                    // Silently ignore if column has not been migrated yet
                }
            }
        }

        $entries = self::getEntries($request);
        $filtered = array_values(array_filter($entries, fn ($e) => $e['code'] !== $code));

        array_unshift($filtered, [
            'code' => $code,
            'token' => $plainToken,
            'created_at' => time(),
        ]);

        $filtered = array_slice($filtered, 0, self::MAX_STORED);

        Cookie::queue(
            Cookie::make(
                name: self::COOKIE_NAME,
                value: json_encode($filtered, JSON_UNESCAPED_SLASHES),
                minutes: self::COOKIE_MINUTES,
                path: '/',
                domain: null,
                secure: app()->isProduction(),
                httpOnly: true,
                sameSite: 'lax'
            )
        );

        return $plainToken;
    }

    /**
     * Remove a booking from the device cookie.
     */
    public static function removeBooking(string $code, ?Request $request = null): void
    {
        $code = strtoupper(trim($code));
        $entries = self::getEntries($request);
        $filtered = array_values(array_filter($entries, fn ($e) => $e['code'] !== $code));

        Cookie::queue(
            Cookie::make(
                name: self::COOKIE_NAME,
                value: json_encode($filtered, JSON_UNESCAPED_SLASHES),
                minutes: self::COOKIE_MINUTES,
                path: '/',
                domain: null,
                secure: app()->isProduction(),
                httpOnly: true,
                sameSite: 'lax'
            )
        );
    }

    /**
     * Determine whether the current request is authorized to view this booking.
     */
    public static function isAuthorized(Request $request, Booking $booking): bool
    {
        // 1. Admin is always authorized
        if ((bool) $request->user()?->is_admin) {
            return true;
        }

        $code = strtoupper(trim($booking->code));

        // 2. Session authorization
        if ($request->session()->get('verified_booking_'.$code) === true) {
            return true;
        }

        // 3. Query token ?t=
        $t = $request->query('t');
        if (is_string($t) && trim($t) !== '' && ! empty($booking->access_token_hash)) {
            if (hash_equals($booking->access_token_hash, hash('sha256', trim($t)))) {
                $request->session()->put('verified_booking_'.$code, true);
                self::addBooking($booking, trim($t), $request);

                return true;
            }
        }

        // 4. Device cookie match
        $entries = self::getEntries($request);
        foreach ($entries as $entry) {
            if ($entry['code'] === $code) {
                // If hash exists on booking and token exists in cookie, verify hash
                if (! empty($booking->access_token_hash) && ! empty($entry['token'])) {
                    if (hash_equals($booking->access_token_hash, hash('sha256', $entry['token']))) {
                        $request->session()->put('verified_booking_'.$code, true);

                        return true;
                    }
                } else {
                    // Legacy match
                    $request->session()->put('verified_booking_'.$code, true);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get active unpaid bookings for the current device, with lazy expiration check.
     *
     * @return Collection<int, Booking>
     */
    public static function getUnpaidBookings(?Request $request = null): Collection
    {
        $codes = self::getCodes($request);

        if (empty($codes)) {
            return collect();
        }

        $bookings = Booking::query()
            ->whereIn('code', $codes)
            ->where('status', 'menunggu_pembayaran')
            ->with(['session.film'])
            ->get();

        if ($bookings->isEmpty()) {
            return collect();
        }

        $bookingService = app(BookingService::class);
        $active = collect();

        foreach ($bookings as $booking) {
            // Lazy expiration evaluation
            if ($booking->payment_expires_at !== null && $booking->payment_expires_at->isPast()) {
                $bookingService->expireIfOverdue($booking);

                continue;
            }

            $active->push($booking);
        }

        return $active;
    }

    /**
     * Get all bookings for this device grouped into the 4 tabs.
     *
     * @return array{
     *     belum_bayar: Collection<int, Booking>,
     *     diproses: Collection<int, Booking>,
     *     aktif: Collection<int, Booking>,
     *     riwayat: Collection<int, Booking>
     * }
     */
    public static function getCategorizedBookings(?Request $request = null): array
    {
        $codes = self::getCodes($request);

        if (empty($codes)) {
            return [
                'belum_bayar' => collect(),
                'diproses' => collect(),
                'aktif' => collect(),
                'riwayat' => collect(),
            ];
        }

        $bookingService = app(BookingService::class);

        $all = Booking::query()
            ->whereIn('code', $codes)
            ->with(['session.film'])
            ->orderByDesc('id')
            ->get();

        $belumBayar = collect();
        $diproses = collect();
        $aktif = collect();
        $riwayat = collect();

        foreach ($all as $booking) {
            // Lazy check overdue
            if ($booking->status === 'menunggu_pembayaran' && $booking->payment_expires_at !== null && $booking->payment_expires_at->isPast()) {
                $booking = $bookingService->expireIfOverdue($booking);
            }

            $session = $booking->session;
            $sessionFinished = $session ? $session->endsAt()->isPast() : false;

            if ($booking->status === 'menunggu_pembayaran' && (! $booking->payment_expires_at || $booking->payment_expires_at->isFuture())) {
                $belumBayar->push($booking);
            } elseif ($booking->status === 'menunggu_konfirmasi' || ($booking->status === 'menunggu_pembayaran' && $session && ! $session->isConfirmed())) {
                $diproses->push($booking);
            } elseif ($booking->status === 'terkonfirmasi' && ! $sessionFinished) {
                $aktif->push($booking);
            } else {
                $riwayat->push($booking);
            }
        }

        return [
            'belum_bayar' => $belumBayar,
            'diproses' => $diproses,
            'aktif' => $aktif,
            'riwayat' => $riwayat,
        ];
    }
}
