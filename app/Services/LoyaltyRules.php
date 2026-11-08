<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Member;
use App\Models\MemberReward;
use App\Models\MemberVisit;
use App\Models\Session;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoyaltyRules
{
    public const REWARD_NONE = 'none';

    public const REWARD_DISCOUNT = 'discount';

    public const REWARD_FREE = 'free';

    public const REWARD_LABELS = [
        self::REWARD_NONE => 'Tanpa Reward',
        self::REWARD_DISCOUNT => 'Potongan Rp 2.000',
        'discount_2000' => 'Potongan Rp 2.000',
        self::REWARD_FREE => 'Gratis 1 Tiket Nonton',
        'free_ticket' => 'Gratis 1 Tiket Nonton',
    ];

    public function __construct(
        protected CinemaSettings $settings
    ) {}

    /**
     * Evaluate check-in verdict for a member and session.
     *
     * @return array{
     *     verdict: string,
     *     can_record: bool,
     *     requires_override: bool,
     *     message: string,
     *     member: Member,
     *     session: ?Session,
     *     next_position: int,
     *     cycle_number: int,
     *     potential_reward: string,
     *     reward_amount: int
     * }
     */
    public function evaluateCheckIn(Member $member, ?Session $session = null): array
    {
        $cycleMax = $this->settings->memberFreeVisit();
        $discountVisit = $this->settings->memberDiscountVisit();
        $discountAmount = $this->settings->memberDiscountAmount();

        $nextPos = $member->nextVisitPosition($cycleMax);
        $cycleNum = $member->currentCycle($cycleMax);

        $potentialReward = self::REWARD_NONE;
        $ticketPrice = $this->determineTicketPrice($session);
        $rewardAmount = 0;

        if ($nextPos === $discountVisit) {
            $potentialReward = self::REWARD_DISCOUNT;
            $rewardAmount = min($discountAmount, $ticketPrice);
        } elseif ($nextPos === $cycleMax) {
            $potentialReward = self::REWARD_FREE;
            $rewardAmount = $ticketPrice;
        }

        // 1. Check member status
        if ($member->status === 'diblokir') {
            return [
                'verdict' => 'member_diblokir',
                'can_record' => false,
                'requires_override' => false,
                'message' => 'Member ini sedang diblokir dan tidak dapat dicatat.',
                'member' => $member,
                'session' => $session,
                'next_position' => $nextPos,
                'cycle_number' => $cycleNum,
                'potential_reward' => $potentialReward,
                'reward_amount' => $rewardAmount,
            ];
        }

        if ($member->status === 'nonaktif' || ! $member->is_active) {
            return [
                'verdict' => 'member_nonaktif',
                'can_record' => false,
                'requires_override' => false,
                'message' => 'Status member tidak aktif.',
                'member' => $member,
                'session' => $session,
                'next_position' => $nextPos,
                'cycle_number' => $cycleNum,
                'potential_reward' => $potentialReward,
                'reward_amount' => $rewardAmount,
            ];
        }

        // 2. Check daily limit
        $maxPerDay = $this->settings->memberMaxVisitsPerDay();
        $todayVisits = MemberVisit::where('member_id', $member->id)
            ->where('active', 1)
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($todayVisits >= $maxPerDay) {
            return [
                'verdict' => 'batas_harian',
                'can_record' => false,
                'requires_override' => false,
                'message' => "Batas maksimal {$maxPerDay} kunjungan per hari untuk member ini telah tercapai.",
                'member' => $member,
                'session' => $session,
                'next_position' => $nextPos,
                'cycle_number' => $cycleNum,
                'potential_reward' => $potentialReward,
                'reward_amount' => $rewardAmount,
            ];
        }

        if (! $session) {
            return [
                'verdict' => 'siap_dicatat',
                'can_record' => true,
                'requires_override' => false,
                'message' => 'Member siap dicatat.',
                'member' => $member,
                'session' => null,
                'next_position' => $nextPos,
                'cycle_number' => $cycleNum,
                'potential_reward' => $potentialReward,
                'reward_amount' => $rewardAmount,
            ];
        }

        // 3. Check duplicate visit for this session
        $existing = MemberVisit::where('member_id', $member->id)
            ->where('session_id', $session->id)
            ->where('active', 1)
            ->first();

        if ($existing) {
            $timeFormatted = $existing->created_at ? $existing->created_at->format('H.i').' WIB' : '';

            return [
                'verdict' => 'sudah_tercatat_sesi_ini',
                'can_record' => false,
                'requires_override' => false,
                'message' => "Member sudah tercatat pada sesi ini ({$timeFormatted}).",
                'member' => $member,
                'session' => $session,
                'next_position' => $nextPos,
                'cycle_number' => $cycleNum,
                'potential_reward' => $potentialReward,
                'reward_amount' => $rewardAmount,
            ];
        }

        // 4. Check session status
        if ($session->status === 'cancelled') {
            return [
                'verdict' => 'sesi_dibatalkan',
                'can_record' => false,
                'requires_override' => false,
                'message' => 'Sesi penayangan ini telah dibatalkan.',
                'member' => $member,
                'session' => $session,
                'next_position' => $nextPos,
                'cycle_number' => $cycleNum,
                'potential_reward' => $potentialReward,
                'reward_amount' => $rewardAmount,
            ];
        }

        if ($session->status === 'completed') {
            return [
                'verdict' => 'sesi_selesai',
                'can_record' => false,
                'requires_override' => false,
                'message' => 'Sesi penayangan ini sudah selesai.',
                'member' => $member,
                'session' => $session,
                'next_position' => $nextPos,
                'cycle_number' => $cycleNum,
                'potential_reward' => $potentialReward,
                'reward_amount' => $rewardAmount,
            ];
        }

        if ($session->status === 'tentative' || ! $session->isConfirmed()) {
            return [
                'verdict' => 'sesi_belum_pasti_tayang',
                'can_record' => true,
                'requires_override' => true,
                'message' => 'Sesi belum pasti tayang. Memerlukan konfirmasi petugas.',
                'member' => $member,
                'session' => $session,
                'next_position' => $nextPos,
                'cycle_number' => $cycleNum,
                'potential_reward' => $potentialReward,
                'reward_amount' => $rewardAmount,
            ];
        }

        // 5. Check check-in window
        $now = Carbon::now();
        $dateStr = is_string($session->session_date) ? $session->session_date : $session->session_date->format('Y-m-d');
        $sessionStart = Carbon::parse($dateStr.' '.$session->start_time);
        $openMinutes = $this->settings->checkInOpenMinutesBefore();
        $openTime = $sessionStart->copy()->subMinutes($openMinutes);

        $closeAfter = $this->settings->checkInCloseOption();
        if ($closeAfter === 'session_end' && $session->end_time) {
            $closeTime = Carbon::parse($dateStr.' '.$session->end_time);
        } else {
            $closeTime = $sessionStart->copy()->addMinutes(60);
        }

        if ($now->lt($openTime) || $now->gt($closeTime)) {
            return [
                'verdict' => 'di_luar_jendela',
                'can_record' => true,
                'requires_override' => true,
                'message' => 'Di luar jendela waktu pemindaian normal sesi ini. Memerlukan konfirmasi petugas.',
                'member' => $member,
                'session' => $session,
                'next_position' => $nextPos,
                'cycle_number' => $cycleNum,
                'potential_reward' => $potentialReward,
                'reward_amount' => $rewardAmount,
            ];
        }

        return [
            'verdict' => 'siap_dicatat',
            'can_record' => true,
            'requires_override' => false,
            'message' => 'Member siap dicatat.',
            'member' => $member,
            'session' => $session,
            'next_position' => $nextPos,
            'cycle_number' => $cycleNum,
            'potential_reward' => $potentialReward,
            'reward_amount' => $rewardAmount,
        ];
    }

    /**
     * Record a member check-in visit with concurrency locking.
     *
     * @return array{
     *     success: bool,
     *     verdict: string,
     *     visit: ?MemberVisit,
     *     reward: ?MemberReward,
     *     message: string
     * }
     */
    public function recordVisit(
        Member $member,
        Session $session,
        ?Booking $booking = null,
        ?int $userId = null,
        string $method = 'qr',
        ?string $overrideReason = null
    ): array {
        return DB::transaction(function () use ($member, $session, $booking, $userId, $method, $overrideReason) {
            // Lock the member row
            $lockedMember = Member::where('id', $member->id)->lockForUpdate()->firstOrFail();

            // Evaluate rules inside the transaction
            $eval = $this->evaluateCheckIn($lockedMember, $session);

            if (! $eval['can_record']) {
                return [
                    'success' => false,
                    'verdict' => $eval['verdict'],
                    'visit' => null,
                    'reward' => null,
                    'message' => $eval['message'],
                ];
            }

            // Check duplicate again under lock
            $existing = MemberVisit::where('member_id', $lockedMember->id)
                ->where('session_id', $session->id)
                ->where('active', 1)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'verdict' => 'sudah_tercatat_sesi_ini',
                    'visit' => $existing,
                    'reward' => null,
                    'message' => 'Member sudah tercatat pada sesi ini.',
                ];
            }

            $cycleMax = $this->settings->memberFreeVisit();
            $discountVisit = $this->settings->memberDiscountVisit();
            $discountAmount = $this->settings->memberDiscountAmount();

            $totalValid = $lockedMember->totalValidVisits();
            $position = (((int) $lockedMember->opening_visits + $totalValid) % $cycleMax) + 1;
            $cycleNumber = intdiv((int) $lockedMember->opening_visits + $totalValid, $cycleMax) + 1;

            $ticketPrice = $this->determineTicketPrice($session, $booking);
            $rewardType = self::REWARD_NONE;
            $rewardAmount = 0;

            if ($position === $discountVisit) {
                $rewardType = self::REWARD_DISCOUNT;
                $rewardAmount = min($discountAmount, $ticketPrice);
            } elseif ($position === $cycleMax) {
                $rewardType = self::REWARD_FREE;
                $rewardAmount = $ticketPrice;
            }

            // Determine settlement according to section 4.5
            $settlement = 'counter_discount';
            if ($booking) {
                if ($booking->isOnlinePayment() && $booking->isPaid()) {
                    $settlement = 'refund_cash';
                } else {
                    $settlement = 'deducted_bill';
                }
            }

            // Create MemberVisit
            $visit = MemberVisit::create([
                'member_id' => $lockedMember->id,
                'session_id' => $session->id,
                'booking_id' => $booking?->id,
                'position' => $position,
                'visit_number' => $position,
                'cycle_number' => $cycleNumber,
                'reward_type' => $rewardType,
                'reward_amount' => $rewardAmount,
                'reward_applied' => $rewardType !== self::REWARD_NONE ? $rewardType : null,
                'active' => 1,
                'method' => $method,
                'recorded_by' => $userId,
            ]);

            // Create MemberReward if eligible
            $reward = null;
            if ($rewardType !== self::REWARD_NONE) {
                $reward = MemberReward::create([
                    'member_visit_id' => $visit->id,
                    'member_id' => $lockedMember->id,
                    'type' => $rewardType,
                    'reward_type' => $rewardType,
                    'amount' => $rewardAmount,
                    'settlement' => $settlement,
                    'booking_id' => $booking?->id,
                    'cycle_number' => $cycleNumber,
                    'status' => 'pending',
                    'granted_at' => now(),
                ]);

                $lockedMember->events()->create([
                    'user_id' => $userId,
                    'event' => 'reward_given',
                    'event_type' => 'reward_given',
                    'meta' => [
                        'reward_id' => $reward->id,
                        'type' => $rewardType,
                        'amount' => $rewardAmount,
                        'settlement' => $settlement,
                        'session_id' => $session->id,
                    ],
                    'created_at' => now(),
                ]);
            }

            $lockedMember->events()->create([
                'user_id' => $userId,
                'event' => 'visit_added',
                'event_type' => 'visit_added',
                'meta' => [
                    'visit_id' => $visit->id,
                    'session_id' => $session->id,
                    'booking_id' => $booking?->id,
                    'position' => $position,
                    'cycle_number' => $cycleNumber,
                    'override_reason' => $overrideReason,
                ],
                'created_at' => now(),
            ]);

            return [
                'success' => true,
                'verdict' => 'siap_dicatat',
                'visit' => $visit,
                'reward' => $reward,
                'message' => "Kunjungan ke-{$position} tercatat.",
            ];
        });
    }

    /**
     * Void the last valid visit of a member.
     *
     * @return array{success: bool, message: string}
     */
    public function voidVisit(Member $member, MemberVisit $visit, int $userId, string $reason): array
    {
        return DB::transaction(function () use ($member, $visit, $userId, $reason) {
            $lockedMember = Member::where('id', $member->id)->lockForUpdate()->firstOrFail();

            // Must be the LAST valid visit
            $lastValid = $lockedMember->lastValidVisit();
            if (! $lastValid || $lastValid->id !== $visit->id) {
                return [
                    'success' => false,
                    'message' => 'Hanya kunjungan sah terakhir yang dapat dibatalkan.',
                ];
            }

            $visit->update([
                'active' => null,
                'void_reason' => $reason,
                'voided_by' => $userId,
                'voided_at' => now(),
            ]);

            // If a reward was created for this visit
            if ($visit->reward) {
                $reward = $visit->reward;
                if ($reward->status === 'given') {
                    $reward->update([
                        'note' => trim($reward->note.' [Dibatalkan setelah diberikan: '.$reason.']'),
                    ]);
                } else {
                    $reward->update([
                        'status' => 'skipped',
                        'note' => 'Kunjungan dibatalkan: '.$reason,
                    ]);
                }
            }

            $lockedMember->events()->create([
                'user_id' => $userId,
                'event' => 'visit_voided',
                'event_type' => 'visit_voided',
                'meta' => [
                    'visit_id' => $visit->id,
                    'session_id' => $visit->session_id,
                    'reason' => $reason,
                ],
                'created_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Kunjungan berhasil dibatalkan.',
            ];
        });
    }

    /**
     * Resolve single-person ticket price for a session or booking.
     */
    public function determineTicketPrice(?Session $session, ?Booking $booking = null): int
    {
        if ($booking && $booking->ticket_total > 0) {
            $people = max(1, (int) ($booking->people_count ?: $booking->pax ?: 1));

            return (int) round($booking->ticket_total / $people);
        }

        if ($session) {
            $dayType = $session->session_date ? (new Carbon($session->session_date))->dayOfWeekIso : 1;
            $typeString = match ($dayType) {
                5 => 'jumat',
                6, 7 => 'weekend',
                default => 'weekday',
            };

            return $this->settings->ticketPriceForDayType($typeString);
        }

        return $this->settings->weekdayPrice();
    }

    /**
     * Get reward label in Indonesian.
     */
    public static function rewardLabel(?string $type): string
    {
        return self::REWARD_LABELS[$type] ?? ($type ?: '-');
    }
}
