<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SnackPreparationController extends Controller
{
    private const STATUSES = ['baru', 'disiapkan', 'siap', 'selesai'];

    public function index(): View
    {
        $sessions = Session::query()
            ->with('film')
            ->where('session_date', '>=', now(config('app.timezone'))->toDateString())
            ->whereHas('bookings.items', fn ($query) => $query->where('status', '!=', 'dibatalkan'))
            ->withCount([
                'bookings as snack_booking_count' => fn ($query) => $query->whereHas(
                    'items',
                    fn ($items) => $items->where('status', '!=', 'dibatalkan'),
                ),
            ])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->paginate(20);

        return view('admin.snacks.preparation-index', compact('sessions'));
    }

    public function show(Session $session): View
    {
        $session->load([
            'film',
            'bookings' => fn ($query) => $query->where('status', '!=', 'dibatalkan')->orderBy('customer_name'),
            'bookings.items' => fn ($query) => $query->where('status', '!=', 'dibatalkan')->orderBy('name_snapshot'),
        ]);
        $items = $session->bookings->flatMap->items;
        $aggregates = $items
            ->groupBy('name_snapshot')
            ->map(fn ($group): array => [
                'qty' => (int) $group->sum('qty'),
                'status' => $group->groupBy('status')->map->sum('qty')->all(),
            ])
            ->sortKeys();

        return view('admin.snacks.preparation', compact('session', 'aggregates'));
    }

    public function updateItemStatus(Request $request, BookingItem $bookingItem): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:baru,disiapkan,siap,selesai'],
        ]);

        DB::transaction(function () use ($bookingItem, $data): void {
            $reference = BookingItem::query()->findOrFail($bookingItem->id);
            $booking = Booking::query()->lockForUpdate()->findOrFail($reference->booking_id);
            $lockedItem = BookingItem::query()->lockForUpdate()->findOrFail($bookingItem->id);

            if ($booking->status === 'dibatalkan' || $lockedItem->status === 'dibatalkan') {
                throw ValidationException::withMessages(['status' => 'Pesanan booking ini sudah dibatalkan.']);
            }

            $lockedItem->update(['status' => $data['status']]);
        });

        return back()->with('status', 'Status jajan berhasil diperbarui.');
    }

    public function updateSessionStatuses(Request $request, Session $session): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:baru,disiapkan,siap,selesai'],
        ]);

        DB::transaction(function () use ($session, $data): void {
            $lockedSession = Session::query()->lockForUpdate()->findOrFail($session->id);
            $bookingIds = Booking::query()
                ->where('session_id', $lockedSession->id)
                ->where('status', '!=', 'dibatalkan')
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            BookingItem::query()
                ->whereIn('booking_id', $bookingIds)
                ->where('status', '!=', 'dibatalkan')
                ->update(['status' => $data['status'], 'updated_at' => now()]);
        }, attempts: 3);

        return back()->with('status', 'Status semua jajan di sesi ini berhasil diperbarui.');
    }
}
