<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NobarEvent;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NobarController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $query = NobarEvent::query();

        if ($status === 'draft') {
            $query->where('status_publikasi', 'draft');
        } elseif ($status === 'terbit') {
            $query->where('status_publikasi', 'terbit');
        } elseif ($status === 'ditunda') {
            $query->where('status_acara', 'ditunda');
        } elseif ($status === 'dibatalkan') {
            $query->where('status_acara', 'dibatalkan');
        }

        $events = $query->orderByDesc('is_pinned')
            ->orderByDesc('kickoff_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.nobar.index', [
            'events' => $events,
            'currentFilter' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.nobar.form', [
            'event' => new NobarEvent([
                'durasi_menit' => 150,
                'is_free' => true,
                'status_publikasi' => 'terbit',
                'status_acara' => 'aktif',
                'lokasi_nama' => 'SynemaX Home Cinema Lounge',
                'alamat_patokan' => 'Jl. Sinema No. 12, Lantai 2 (Seberang Taman Kota)',
                'maps_url' => 'https://maps.google.com/?q=SynemaX',
            ]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request, MediaUploadService $media): RedirectResponse
    {
        $data = $this->validateEvent($request);

        if ($request->hasFile('poster')) {
            $data['poster_path'] = $media->uploadImage($request->file('poster'), 'nobar');
        }

        $event = NobarEvent::create($data);

        return redirect()->route('admin.nobar.index')
            ->with('status', 'Acara nobar "'.$event->judul.'" berhasil ditambahkan.');
    }

    public function edit(NobarEvent $nobar): View
    {
        return view('admin.nobar.form', [
            'event' => $nobar,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, NobarEvent $nobar, MediaUploadService $media): RedirectResponse
    {
        $data = $this->validateEvent($request, $nobar->id);

        if ($request->boolean('delete_poster') && $nobar->poster_path) {
            $media->deleteImage($nobar->poster_path);
            $data['poster_path'] = null;
        } elseif ($request->hasFile('poster')) {
            $media->deleteImage($nobar->poster_path);
            $data['poster_path'] = $media->uploadImage($request->file('poster'), 'nobar');
        }

        $nobar->update($data);

        return redirect()->route('admin.nobar.index')
            ->with('status', 'Acara nobar "'.$nobar->judul.'" berhasil diperbarui.');
    }

    public function destroy(NobarEvent $nobar, MediaUploadService $media): RedirectResponse
    {
        $media->deleteImage($nobar->poster_path);
        $nobar->delete();

        return redirect()->route('admin.nobar.index')
            ->with('status', 'Acara nobar berhasil dihapus.');
    }

    public function updateStatus(Request $request, NobarEvent $nobar): RedirectResponse
    {
        $data = $request->validate([
            'status_acara' => ['required', 'in:aktif,ditunda,dibatalkan'],
            'alasan_status' => ['nullable', 'string', 'max:255'],
        ]);

        if (in_array($data['status_acara'], ['ditunda', 'dibatalkan'], true) && blank($data['alasan_status'])) {
            $data['alasan_status'] = $data['status_acara'] === 'ditunda'
                ? 'Jadwal pertandingan ditunda oleh pihak penyelenggara.'
                : 'Acara dibatalkan karena kendala operasional.';
        }

        if ($data['status_acara'] === 'aktif') {
            $data['alasan_status'] = null;
        }

        $nobar->update($data);

        return redirect()->back(fallback: route('admin.nobar.index'))
            ->with('status', 'Status acara nobar berhasil diubah menjadi '.ucfirst($data['status_acara']).'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEvent(Request $request, ?int $id = null): array
    {
        $slug = trim((string) $request->input('slug'));
        if ($slug === '') {
            $slug = Str::slug((string) $request->input('judul'));
        } else {
            $slug = Str::slug($slug);
        }

        $uniqueSlug = $slug;
        $counter = 1;
        while (NobarEvent::where('slug', $uniqueSlug)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
            $uniqueSlug = $slug.'-'.$counter;
            $counter++;
        }

        $request->merge(['slug' => $uniqueSlug]);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'kompetisi' => ['nullable', 'string', 'max:100'],
            'tim_a' => ['required', 'string', 'max:100'],
            'tim_b' => ['nullable', 'string', 'max:100'],
            'kickoff_at' => ['required', 'date'],
            'durasi_menit' => ['required', 'integer', 'between:30,360'],
            'lokasi_nama' => ['required', 'string', 'max:150'],
            'alamat_patokan' => ['required', 'string', 'max:500'],
            'maps_url' => ['required', 'url', 'starts_with:https://'],
            'is_free' => ['required', 'boolean'],
            'harga_info' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:5000'],
            'poster' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
            'status_publikasi' => ['required', 'in:draft,terbit'],
            'is_pinned' => ['required', 'boolean'],
            'status_acara' => ['required', 'in:aktif,ditunda,dibatalkan'],
            'alasan_status' => ['nullable', 'string', 'max:255'],
        ], [
            'judul.required' => 'Judul acara nobar wajib diisi.',
            'tim_a.required' => 'Nama tim A / tuan rumah wajib diisi.',
            'kickoff_at.required' => 'Waktu kickoff wajib ditentukan.',
            'maps_url.starts_with' => 'URL Google Maps harus menggunakan protokol HTTPS yang aman (https://).',
            'poster.max' => 'Ukuran file poster maksimal 3 MB.',
        ]);

        unset($data['poster']);

        return $data;
    }
}
