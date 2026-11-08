<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicMemberController;
use App\Models\Member;
use App\Models\MemberReward;
use App\Models\MemberVisit;
use App\Models\Session;
use App\Services\CinemaSettings;
use App\Services\LoyaltyRules;
use App\Services\MemberCodeGenerator;
use App\Services\WebpImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberController extends Controller
{
    public function index(Request $request, CinemaSettings $settings): View
    {
        $query = Member::query()->withCount(['validVisits']);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $normSearch = MemberCodeGenerator::normalize($search);
            $query->where(function ($q) use ($search, $normSearch): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$normSearch}%")
                    ->orWhere('member_code', 'like', "%{$normSearch}%");
            });
        }

        $status = $request->query('status');
        if (in_array($status, ['aktif', 'nonaktif', 'diblokir'], true)) {
            $query->where('status', $status);
        }

        $members = $query->latest('id')->paginate(25)->withQueryString();

        $stats = [
            'total' => Member::count(),
            'aktif' => Member::where('status', 'aktif')->count(),
            'nonaktif' => Member::where('status', 'nonaktif')->count(),
            'diblokir' => Member::where('status', 'diblokir')->count(),
            'pending_rewards' => MemberReward::where('status', 'pending')->count(),
        ];

        return view('admin.members.index', [
            'members' => $members,
            'stats' => $stats,
            'search' => $search,
            'selectedStatus' => $status,
            'settings' => $settings,
        ]);
    }

    public function scan(Request $request, CinemaSettings $settings): View
    {
        $todayDate = now()->toDateString();
        $todaySessions = Session::query()
            ->with(['film', 'occupiedBookings'])
            ->where('session_date', $todayDate)
            ->orderBy('start_time')
            ->get();

        $selectedSessionId = $request->query('session_id');
        $selectedSession = null;

        if ($selectedSessionId && $selectedSessionId !== 'all') {
            $selectedSession = $todaySessions->firstWhere('id', (int) $selectedSessionId);
        }

        if (! $selectedSession && $todaySessions->isNotEmpty()) {
            $now = now();
            $selectedSession = $todaySessions->first(fn (Session $s) => $now->greaterThanOrEqualTo($s->startsAt()) && $now->lessThan($s->endsAt()));
            if (! $selectedSession) {
                $selectedSession = $todaySessions->first(fn (Session $s) => $now->lessThan($s->startsAt()));
            }
            if (! $selectedSession) {
                $selectedSession = $todaySessions->last();
            }
        }

        return view('admin.members.scan', [
            'todaySessions' => $todaySessions,
            'selectedSession' => $selectedSession,
            'selectedSessionId' => $selectedSession?->id ?? 'all',
            'soundEnabled' => $settings->checkInSoundEnabled(),
            'settings' => $settings,
        ]);
    }

    public function create(): View
    {
        return view('admin.members.create');
    }

    public function store(Request $request, WebpImageProcessor $images): RedirectResponse
    {
        if ($request->has('opening_visits')) {
            $raw = $request->input('opening_visits');
            if (is_string($raw) && preg_match('/^\s*(\d+)/', $raw, $m)) {
                $request->merge(['opening_visits' => (int) $m[1]]);
            } elseif ($raw === '' || $raw === null) {
                $request->merge(['opening_visits' => 0]);
            }
        } else {
            $request->merge(['opening_visits' => 0]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:300'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,webp,jpg', 'max:2048'],
            'opening_visits' => ['nullable', 'integer', 'min:0', 'max:9'],
            'status' => ['required', 'string', 'in:aktif,nonaktif,diblokir'],
            'ignore_duplicate_name' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama member wajib diisi.',
            'name.max' => 'Nama member maksimal 80 karakter.',
            'photo.image' => 'Berkas foto harus berupa gambar yang valid.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'status.required' => 'Status member wajib dipilih.',
            'opening_visits.integer' => 'Saldo awal kunjungan harus berupa angka bulat.',
            'opening_visits.min' => 'Saldo awal kunjungan minimal 0.',
            'opening_visits.max' => 'Saldo awal kunjungan tidak boleh lebih dari 9.',
        ], [
            'opening_visits' => 'saldo awal kunjungan',
            'name' => 'nama member',
            'status' => 'status member',
        ]);

        // Duplicate name warning
        if (! $request->boolean('ignore_duplicate_name')) {
            $existing = Member::where('name', $validated['name'])->first();
            if ($existing) {
                $codeSuffix = substr($existing->code, -4);

                return back()
                    ->withInput()
                    ->with('duplicate_name_warning', [
                        'existing_id' => "...-{$codeSuffix}",
                        'name' => $existing->name,
                    ]);
            }
        }

        // Generate unique Crockford base32 code with check digit
        $code = null;
        for ($i = 0; $i < 5; $i++) {
            $candidate = MemberCodeGenerator::generate();
            if (! Member::where('code', $candidate)->orWhere('member_code', $candidate)->exists()) {
                $code = $candidate;
                break;
            }
        }

        if (! $code) {
            throw ValidationException::withMessages([
                'name' => 'Gagal menghasilkan ID unik member yang valid. Silakan coba lagi.',
            ]);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $images->storeSquare($request->file('photo'), 'members', 400);
        }

        $member = Member::create([
            'code' => $code,
            'member_code' => $code,
            'name' => trim($validated['name']),
            'nickname' => filled($validated['nickname'] ?? null) ? trim($validated['nickname']) : null,
            'notes' => filled($validated['notes'] ?? null) ? trim($validated['notes']) : null,
            'photo_path' => $photoPath,
            'status' => $validated['status'],
            'is_active' => ($validated['status'] === 'aktif'),
            'opening_visits' => (int) ($validated['opening_visits'] ?? 0),
            'link_version' => 1,
            'created_by' => $request->user()?->id,
        ]);

        $member->events()->create([
            'user_id' => $request->user()?->id,
            'event' => 'created',
            'event_type' => 'created',
            'meta' => [
                'code' => $code,
                'name' => $member->name,
                'opening_visits' => $member->opening_visits,
            ],
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        // Prefill WhatsApp text without phone number
        $siteName = setting('site_name', 'SynemaX');
        $cardUrl = $member->digitalCardSignedUrl();
        $waText = rawurlencode("Halo {$member->name}, kartu member {$siteName} Anda telah aktif dengan ID {$member->code}. Buka kartu digital Anda di: {$cardUrl}");

        return redirect()->route('admin.members.show', $member)
            ->with('member_created_success', true)
            ->with('member_wa_text', $waText);
    }

    public function show(Member $member, CinemaSettings $settings): View
    {
        $member->load([
            'validVisits.session.film',
            'validVisits.recordedByUser',
            'validVisits.reward',
            'rewards.visit.session',
            'events.user',
        ]);

        $visits = $member->visits()
            ->with(['session.film', 'recordedByUser', 'reward'])
            ->latest('id')
            ->get();

        $rewards = $member->rewards()
            ->with(['visit.session', 'booking', 'givenByUser'])
            ->latest('id')
            ->get();

        $events = $member->events()
            ->with('user')
            ->latest('id')
            ->get();

        $lastValidVisit = $member->lastValidVisit();

        return view('admin.members.show', [
            'member' => $member,
            'visits' => $visits,
            'rewards' => $rewards,
            'events' => $events,
            'lastValidVisit' => $lastValidVisit,
            'settings' => $settings,
        ]);
    }

    public function edit(Member $member): View
    {
        return view('admin.members.edit', [
            'member' => $member,
        ]);
    }

    public function update(Request $request, Member $member, WebpImageProcessor $images): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:300'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,webp,jpg', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'in:aktif,nonaktif,diblokir'],
        ], [
            'name.required' => 'Nama member wajib diisi.',
            'name.max' => 'Nama member maksimal 80 karakter.',
        ]);

        $oldValues = $member->only(['name', 'nickname', 'notes', 'status', 'photo_path']);

        $photoPath = $member->photo_path;
        if ($request->boolean('remove_photo')) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = null;
        } elseif ($request->hasFile('photo')) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $images->storeSquare($request->file('photo'), 'members', 400);
        }

        $member->update([
            'name' => trim($validated['name']),
            'nickname' => filled($validated['nickname'] ?? null) ? trim($validated['nickname']) : null,
            'notes' => filled($validated['notes'] ?? null) ? trim($validated['notes']) : null,
            'photo_path' => $photoPath,
            'status' => $validated['status'],
            'is_active' => ($validated['status'] === 'aktif'),
        ]);

        $member->events()->create([
            'user_id' => $request->user()?->id,
            'event' => 'updated',
            'event_type' => 'updated',
            'meta' => [
                'before' => $oldValues,
                'after' => $member->only(['name', 'nickname', 'notes', 'status', 'photo_path']),
            ],
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.members.show', $member)
            ->with('status', 'Data member berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:aktif,nonaktif,diblokir'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldStatus = $member->status;
        $newStatus = $validated['status'];

        $member->update([
            'status' => $newStatus,
            'is_active' => ($newStatus === 'aktif'),
        ]);

        $member->events()->create([
            'user_id' => $request->user()?->id,
            'event' => 'status_changed',
            'event_type' => 'status_changed',
            'meta' => [
                'from' => $oldStatus,
                'to' => $newStatus,
                'reason' => $validated['reason'] ?? null,
            ],
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('status', "Status member diubah menjadi {$newStatus}.");
    }

    public function regenerateLink(Request $request, Member $member): RedirectResponse
    {
        $member->increment('link_version');

        $member->events()->create([
            'user_id' => $request->user()?->id,
            'event' => 'link_regenerated',
            'event_type' => 'link_regenerated',
            'meta' => [
                'new_version' => $member->link_version,
            ],
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Tautan kartu digital lama telah dicabut dan tautan baru berhasil dibuat.');
    }

    public function updateOpeningVisits(Request $request, Member $member): RedirectResponse
    {
        if ($request->has('opening_visits')) {
            $raw = $request->input('opening_visits');
            if (is_string($raw) && preg_match('/^\s*(\d+)/', $raw, $m)) {
                $request->merge(['opening_visits' => (int) $m[1]]);
            }
        }

        $validated = $request->validate([
            'opening_visits' => ['required', 'integer', 'min:0', 'max:9'],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan koreksi saldo awal wajib diisi.',
            'opening_visits.required' => 'Saldo awal kunjungan wajib dipilih.',
            'opening_visits.integer' => 'Saldo awal kunjungan harus berupa angka bulat.',
            'opening_visits.min' => 'Saldo awal kunjungan minimal 0.',
            'opening_visits.max' => 'Saldo awal kunjungan tidak boleh lebih dari 9.',
        ], [
            'opening_visits' => 'saldo awal kunjungan',
            'reason' => 'alasan koreksi',
        ]);

        $oldVal = $member->opening_visits;
        $newVal = (int) $validated['opening_visits'];

        $member->update(['opening_visits' => $newVal]);

        $member->events()->create([
            'user_id' => $request->user()?->id,
            'event' => 'opening_changed',
            'event_type' => 'opening_changed',
            'meta' => [
                'from' => $oldVal,
                'to' => $newVal,
                'reason' => $validated['reason'],
            ],
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Saldo awal kunjungan berhasil diperbarui.');
    }

    public function voidVisit(Request $request, Member $member, MemberVisit $visit, LoyaltyRules $rules): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan pembatalan kunjungan wajib diisi.',
        ]);

        $result = $rules->voidVisit($member, $visit, (int) $request->user()->id, $validated['reason']);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('status', $result['message']);
    }

    public function settleReward(Request $request, Member $member, MemberReward $reward): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:given,skipped'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $reward->update([
            'status' => $validated['action'],
            'given_by' => $request->user()?->id,
            'given_at' => now(),
            'note' => filled($validated['note'] ?? null) ? trim($validated['note']) : $reward->note,
        ]);

        $member->events()->create([
            'user_id' => $request->user()?->id,
            'event' => $validated['action'] === 'given' ? 'reward_given' : 'reward_skipped',
            'event_type' => $validated['action'] === 'given' ? 'reward_given' : 'reward_skipped',
            'meta' => [
                'reward_id' => $reward->id,
                'action' => $validated['action'],
                'note' => $validated['note'] ?? null,
            ],
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        $msg = $validated['action'] === 'given'
            ? 'Reward berhasil diselesaikan dan ditandai sudah diberikan.'
            : 'Reward dilewati.';

        return back()->with('status', $msg);
    }

    public function destroy(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan penghapusan member wajib diisi.',
        ]);

        $member->events()->create([
            'user_id' => $request->user()?->id,
            'event' => 'deleted',
            'event_type' => 'deleted',
            'meta' => [
                'reason' => $validated['reason'],
            ],
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        $member->delete();

        return redirect()->route('admin.members.index')
            ->with('status', "Member {$member->name} berhasil dihapus (soft delete).");
    }

    public function printSingle(Request $request, Member $member, CinemaSettings $settings): View|Response
    {
        if ($request->query('format') === 'svg') {
            $svg = app(PublicMemberController::class)->renderCardSvg($member, $settings);
            $filename = 'kartu-member-synemax-'.strtolower((string) preg_replace('/[^A-Za-z0-9]/', '-', $member->formattedCode())).'.svg';

            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        return view('admin.members.print-single', [
            'member' => $member,
        ]);
    }

    public function printBatch(Request $request): View
    {
        $ids = $request->query('ids');
        $query = Member::query()->where('status', 'aktif');

        $hasSpecificIds = false;
        if (is_array($ids)) {
            $query->whereIn('id', $ids);
            $hasSpecificIds = true;
        } elseif (is_string($ids) && filled($ids)) {
            $idArray = array_filter(array_map('intval', explode(',', $ids)));
            if (! empty($idArray)) {
                $query->whereIn('id', $idArray);
                $hasSpecificIds = true;
            }
        }

        $members = $hasSpecificIds ? $query->take(200)->get() : $query->take(8)->get();

        return view('admin.members.print-batch', [
            'members' => $members,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filename = 'members_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            // Write BOM for UTF-8 Excel support
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Nama',
                'ID Member',
                'Status',
                'Total Kunjungan Sah',
                'Posisi Siklus',
                'Kunjungan Terakhir',
            ]);

            Member::query()->withCount('validVisits')->with('validVisits')->chunk(100, function ($members) use ($handle): void {
                foreach ($members as $m) {
                    $last = $m->lastValidVisit();
                    $lastDate = $last && $last->created_at ? $last->created_at->format('d/m/Y H:i') : '-';

                    // Formula injection protection: prefix with single quote if starts with dangerous char
                    $safeName = $m->name;
                    if (in_array(substr($safeName, 0, 1), ['=', '+', '-', '@'], true)) {
                        $safeName = "'".$safeName;
                    }
                    $safeCode = $m->code;
                    if (in_array(substr($safeCode, 0, 1), ['=', '+', '-', '@'], true)) {
                        $safeCode = "'".$safeCode;
                    }

                    fputcsv($handle, [
                        $safeName,
                        $safeCode,
                        $m->status,
                        $m->valid_visits_count,
                        $m->nextVisitPosition() - 1,
                        $lastDate,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
