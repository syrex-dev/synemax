<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use App\Models\SnackOrderEdit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SnackOrderService
{
    public function __construct(private readonly CinemaSettings $settings) {}

    /**
     * @param  array<int|string, array{qty?: int|string, note?: string|null}>  $selection
     */
    public function replaceItems(
        Booking $booking,
        array $selection,
        bool $enforceDeadline = true,
        ?User $auditActor = null,
    ): Booking {
        return DB::transaction(function () use ($booking, $selection, $enforceDeadline, $auditActor): Booking {
            $reference = Booking::query()->findOrFail($booking->getKey());
            $session = Session::query()->lockForUpdate()->findOrFail($reference->session_id);
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if ($lockedBooking->status === 'dibatalkan') {
                $this->reject('snacks', 'Jajan tidak bisa diubah karena booking sudah dibatalkan.');
            }

            if ($enforceDeadline && ! $this->enabled() && $selection !== []) {
                $this->reject('snacks', 'Pesanan jajan sedang tidak tersedia.');
            }

            $maxQty = $this->settings->integer('snack_max_qty_per_item', 10);
            $desired = [];

            foreach ($selection as $itemId => $line) {
                if (! is_array($line) || ! is_numeric($itemId)) {
                    $this->reject('snacks', 'Data pilihan jajan tidak valid.');
                }

                $qty = filter_var($line['qty'] ?? null, FILTER_VALIDATE_INT);

                if ($qty === false || $qty < 0 || $qty > $maxQty) {
                    $this->reject('snacks', 'Jumlah jajan per menu maksimal '.$maxQty.' buah.');
                }

                if ($qty === 0) {
                    continue;
                }

                $desired[(int) $itemId] = [
                    'qty' => $qty,
                    'note' => trim((string) ($line['note'] ?? '')),
                ];
            }

            $existingItems = BookingItem::query()
                ->where('booking_id', $lockedBooking->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $activeExisting = $existingItems->where('status', '!=', 'dibatalkan');
            $itemIds = $activeExisting->pluck('menu_item_id')
                ->merge(array_keys($desired))
                ->filter()
                ->unique()
                ->sort()
                ->values();
            $menuItems = $this->lockMenuItems($itemIds);
            $existingByMenuItem = $activeExisting
                ->filter(fn (BookingItem $item): bool => $item->menu_item_id !== null)
                ->keyBy('menu_item_id');

            if ($enforceDeadline) {
                $this->assertBeforeOrderDeadline($session, $activeExisting, $menuItems, $desired);
            }

            $beforeDetails = $this->orderDetails($activeExisting);

            foreach ($desired as $itemId => $line) {
                $menuItem = $menuItems->get($itemId);
                $existing = $existingByMenuItem->get($itemId);

                if ($menuItem === null) {
                    $this->reject('snacks', 'Salah satu menu sudah tidak tersedia. Perbarui pilihan jajanmu.');
                }

                if (! $this->isOrderable($menuItem) && $existing === null) {
                    $this->reject('snacks', 'Menu '.$menuItem->name.' sedang habis atau tidak tersedia.');
                }

                if (
                    ! $this->isOrderable($menuItem)
                    && $existing !== null
                    && $line['qty'] > $existing->qty
                ) {
                    $this->reject('snacks', 'Menu '.$menuItem->name.' sedang habis, jadi jumlahnya tidak bisa ditambah.');
                }
            }

            foreach ($activeExisting as $existing) {
                $itemId = $existing->menu_item_id;
                $line = $itemId === null ? null : ($desired[$itemId] ?? null);

                if ($line === null) {
                    if ($itemId !== null && $menuItems->has($itemId)) {
                        $this->reconcileStock($menuItems->get($itemId), $existing, 0, $lockedBooking);
                    }

                    $existing->update(['status' => 'dibatalkan']);

                    continue;
                }

                $existing->update([
                    'qty' => $line['qty'],
                    'note' => $line['note'] !== '' ? $line['note'] : null,
                ]);

                if ($itemId !== null && $menuItems->has($itemId)) {
                    $this->reconcileStock(
                        $menuItems->get($itemId),
                        $existing,
                        $line['qty'],
                        $lockedBooking,
                    );
                }

                unset($desired[$itemId]);
            }

            foreach ($desired as $itemId => $line) {
                $menuItem = $menuItems->get($itemId);
                $bookingItem = $lockedBooking->items()->create([
                    'menu_item_id' => $menuItem->id,
                    'name_snapshot' => $menuItem->name,
                    'price_snapshot' => $menuItem->price,
                    'cost_snapshot' => $menuItem->cost_price,
                    'qty' => $line['qty'],
                    'note' => $line['note'] !== '' ? $line['note'] : null,
                    'status' => 'baru',
                ]);

                $this->reconcileStock($menuItem, $bookingItem, $line['qty'], $lockedBooking);
            }

            $snackTotal = (int) $lockedBooking->items()
                ->where('status', '!=', 'dibatalkan')
                ->selectRaw('COALESCE(SUM(price_snapshot * qty), 0) as aggregate')
                ->value('aggregate');
            $ticketTotal = (int) ($lockedBooking->ticket_total ?: $lockedBooking->total_price);
            $lockedBooking->update([
                'ticket_total' => $ticketTotal,
                'snack_total' => $snackTotal,
                'total_price' => $ticketTotal + $snackTotal,
            ]);

            $afterDetails = $this->orderDetails(
                $lockedBooking->items()->where('status', '!=', 'dibatalkan')->orderBy('id')->get(),
            );

            if ($auditActor !== null && $beforeDetails !== $afterDetails) {
                SnackOrderEdit::query()->create([
                    'booking_id' => $lockedBooking->id,
                    'actor_id' => $auditActor->id,
                    'summary' => $this->summarizeChanges($beforeDetails, $afterDetails),
                    'before_details' => $beforeDetails,
                    'after_details' => $afterDetails,
                ]);
            }

            return $lockedBooking->fresh(['items.menuItem', 'session', 'film']);
        }, attempts: 3);
    }

    /**
     * @param  Collection<int, BookingItem>  $items
     * @return array<int, array{menu_item_id: int|null, name_snapshot: string, price_snapshot: int, qty: int, note: string|null}>
     */
    private function orderDetails(Collection $items): array
    {
        $details = $items->map(fn (BookingItem $item): array => [
            'menu_item_id' => $item->menu_item_id === null ? null : (int) $item->menu_item_id,
            'name_snapshot' => $item->name_snapshot,
            'price_snapshot' => (int) $item->price_snapshot,
            'qty' => (int) $item->qty,
            'note' => $item->note,
        ])->all();

        usort($details, fn (array $left, array $right): int => [
            $left['menu_item_id'] ?? 0,
            $left['name_snapshot'],
            $left['price_snapshot'],
            $left['qty'],
            $left['note'] ?? '',
        ] <=> [
            $right['menu_item_id'] ?? 0,
            $right['name_snapshot'],
            $right['price_snapshot'],
            $right['qty'],
            $right['note'] ?? '',
        ]);

        return $details;
    }

    /**
     * @param  array<int, array{menu_item_id: int|null, name_snapshot: string, price_snapshot: int, qty: int, note: string|null}>  $before
     * @param  array<int, array{menu_item_id: int|null, name_snapshot: string, price_snapshot: int, qty: int, note: string|null}>  $after
     */
    private function summarizeChanges(array $before, array $after): string
    {
        $identify = fn (array $item): string => $item['menu_item_id'] === null
            ? 'snapshot:'.$item['name_snapshot']
            : 'menu:'.$item['menu_item_id'];
        $beforeByItem = collect($before)->keyBy($identify);
        $afterByItem = collect($after)->keyBy($identify);
        $changes = [];

        foreach ($beforeByItem->keys()->merge($afterByItem->keys())->unique() as $key) {
            $oldItem = $beforeByItem->get($key);
            $newItem = $afterByItem->get($key);
            $name = $newItem['name_snapshot'] ?? $oldItem['name_snapshot'];

            if (($oldItem['qty'] ?? 0) !== ($newItem['qty'] ?? 0)) {
                $changes[] = $name.': '.($oldItem['qty'] ?? 0).'× → '.($newItem['qty'] ?? 0).'×';

                continue;
            }

            if (($oldItem['note'] ?? null) !== ($newItem['note'] ?? null)) {
                $changes[] = $name.': catatan diperbarui';
            }
        }

        return $changes === []
            ? 'Pesanan jajan diperbarui.'
            : 'Pesanan jajan diperbarui: '.implode('; ', $changes);
    }

    public function confirmStock(Booking $booking): void
    {
        $items = BookingItem::query()
            ->where('booking_id', $booking->id)
            ->where('status', '!=', 'dibatalkan')
            ->whereNotNull('menu_item_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $menuItems = $this->lockMenuItems($items);

        foreach ($items as $item) {
            $menuItem = $menuItems->get($item->menu_item_id);

            if ($menuItem?->stock_mode !== 'angka') {
                continue;
            }

            $needed = max(0, $item->qty - $item->stock_deducted_qty + $item->stock_restored_qty);

            if ($menuItem->stock_qty === null || $menuItem->stock_qty < $needed) {
                $this->reject(
                    'status',
                    'Stok '.$item->name_snapshot.' tidak cukup untuk mengonfirmasi pesanan. Koreksi stok atau hubungi pemesan.',
                );
            }
        }

        foreach ($items as $item) {
            $menuItem = $menuItems->get($item->menu_item_id);

            if ($menuItem !== null) {
                $this->reconcileStock($menuItem, $item, $item->qty, $booking, forceDeduct: true);
            }
        }
    }

    public function cancelItems(Booking $booking): void
    {
        $items = BookingItem::query()
            ->where('booking_id', $booking->id)
            ->where('status', '!=', 'dibatalkan')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $menuItems = $this->lockMenuItems($items);

        foreach ($items as $item) {
            if ($item->menu_item_id !== null && $menuItems->has($item->menu_item_id)) {
                $menuItem = $menuItems->get($item->menu_item_id);

                if ($item->stock_deducted_qty > $item->stock_restored_qty) {
                    $this->reconcileStock($menuItem, $item, 0, $booking, forceRelease: true);
                }
            }

            $item->update(['status' => 'dibatalkan']);
        }
    }

    public function enabled(): bool
    {
        return $this->settings->integer('snacks_enabled', 1) === 1;
    }

    public function deadlineFor(Session $session, int $longestPrepMinutes): CarbonImmutable
    {
        return $session->startsAt()->subMinutes(
            max(0, $longestPrepMinutes) + $this->settings->integer('snack_prep_buffer_minutes', 15),
        );
    }

    private function assertBeforeOrderDeadline(
        Session $session,
        $activeExisting,
        $menuItems,
        array $desired,
    ): void {
        $longestExisting = (int) $activeExisting->map(
            fn (BookingItem $item): int => (int) ($menuItems->get($item->menu_item_id)?->prep_minutes ?? 0),
        )->max();

        if (
            $activeExisting->isNotEmpty()
            && now()->greaterThanOrEqualTo($this->deadlineFor($session, $longestExisting))
        ) {
            $this->reject('snacks', 'Batas waktu mengubah pesanan jajan sudah lewat.');
        }

        $longestDesired = (int) collect(array_keys($desired))
            ->map(fn (int $itemId): int => (int) ($menuItems->get($itemId)?->prep_minutes ?? 0))
            ->max();

        if (
            $desired !== []
            && now()->greaterThanOrEqualTo($this->deadlineFor($session, $longestDesired))
        ) {
            $this->reject('snacks', 'Waktu pesan untuk salah satu jajan sudah lewat. Coba kurangi pilihan atau hubungi admin.');
        }
    }

    private function lockMenuItems($bookingItems)
    {
        $ids = $bookingItems instanceof Collection
            && $bookingItems->first() instanceof BookingItem
                ? $bookingItems->pluck('menu_item_id')->filter()->unique()->sort()->values()
                : collect($bookingItems)->filter()->unique()->sort()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $items = MenuItem::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        $categoryIds = $items->pluck('category_id')->unique()->sort()->values();
        $categories = MenuCategory::query()
            ->whereIn('id', $categoryIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $item->setRelation('category', $categories->get($item->category_id));
        }

        return $items->keyBy('id');
    }

    private function isOrderable(MenuItem $menuItem): bool
    {
        return $menuItem->canBeOrdered() && $menuItem->category?->is_active === true;
    }

    private function reconcileStock(
        MenuItem $menuItem,
        BookingItem $bookingItem,
        int $desiredQty,
        Booking $booking,
        bool $forceDeduct = false,
        bool $forceRelease = false,
    ): void {
        $deducted = (int) $bookingItem->stock_deducted_qty;
        $restored = (int) $bookingItem->stock_restored_qty;
        $currentNet = max(0, $deducted - $restored);
        $shouldDeduct = $menuItem->stock_mode === 'angka'
            && ($forceDeduct || in_array($booking->status, ['terkonfirmasi', 'hadir', 'tidak_hadir'], true));
        $targetNet = $forceRelease
            ? 0
            : ($shouldDeduct ? $desiredQty : min($currentNet, $desiredQty));

        if ($targetNet === $currentNet) {
            return;
        }

        if ($targetNet > $currentNet) {
            $additional = $targetNet - $currentNet;

            if ($menuItem->stock_qty === null || $menuItem->stock_qty < $additional) {
                $this->reject('snacks', 'Stok '.$menuItem->name.' tidak mencukupi. Coba kurangi jumlahnya.');
            }

            $menuItem->decrement('stock_qty', $additional);
            $bookingItem->update([
                'stock_deducted_qty' => $deducted + $additional,
                'stock_deducted_at' => now(),
            ]);
        } elseif ($targetNet < $currentNet) {
            $restock = $currentNet - $targetNet;
            $menuItem->increment('stock_qty', $restock);
            $bookingItem->update([
                'stock_restored_qty' => $restored + $restock,
                'stock_restored_at' => now(),
            ]);
        }
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
