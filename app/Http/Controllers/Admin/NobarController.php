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
        $this->ensureUploadWithinPhpLimits($request);
        $data = $this->validateEvent($request);

        if ($request->hasFile('poster')) {
            $posterData = $media->uploadNobarPoster($request->file('poster'));
            $data['poster_path'] = $posterData['path'];
            $data['poster_width'] = $posterData['width'];
            $data['poster_height'] = $posterData['height'];
            $data['poster_og_path'] = $posterData['og_path'];
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
        $this->ensureUploadWithinPhpLimits($request);
        $data = $this->validateEvent($request, $nobar->id);

        if ($request->boolean('delete_poster') && $nobar->poster_path) {
            $media->deletePosterAndOg($nobar->poster_path, $nobar->poster_og_path);
            $data['poster_path'] = null;
            $data['poster_width'] = null;
            $data['poster_height'] = null;
            $data['poster_alt'] = null;
            $data['poster_og_path'] = null;
        } elseif ($request->hasFile('poster')) {
            $media->deletePosterAndOg($nobar->poster_path, $nobar->poster_og_path);
            $posterData = $media->uploadNobarPoster($request->file('poster'));
            $data['poster_path'] = $posterData['path'];
            $data['poster_width'] = $posterData['width'];
            $data['poster_height'] = $posterData['height'];
            $data['poster_og_path'] = $posterData['og_path'];
        }

        $nobar->update($data);

        return redirect()->route('admin.nobar.index')
            ->with('status', 'Acara nobar "'.$nobar->judul.'" berhasil diperbarui.');
    }

    public function duplicate(NobarEvent $nobar, MediaUploadService $media): RedirectResponse
    {
        $posterCopies = $media->copyPoster($nobar->poster_path, $nobar->poster_og_path);

        $baseSlug = Str::slug($nobar->slug.'-salinan');
        $uniqueSlug = $baseSlug;
        $counter = 1;
        while (NobarEvent::where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $duplicate = NobarEvent::create([
            'judul' => $nobar->judul.' (Salinan)',
            'slug' => $uniqueSlug,
            'kompetisi' => $nobar->kompetisi,
            'tim_a' => $nobar->tim_a,
            'tim_b' => $nobar->tim_b,
            'kickoff_at' => $nobar->kickoff_at,
            'durasi_menit' => $nobar->durasi_menit,
            'lokasi_nama' => $nobar->lokasi_nama,
            'alamat_patokan' => $nobar->alamat_patokan,
            'maps_url' => $nobar->maps_url,
            'is_free' => $nobar->is_free,
            'harga_info' => $nobar->harga_info,
            'catatan' => $nobar->catatan,
            'poster_path' => $posterCopies['poster_path'],
            'poster_width' => $nobar->poster_width,
            'poster_height' => $nobar->poster_height,
            'poster_alt' => $nobar->poster_alt,
            'poster_og_path' => $posterCopies['poster_og_path'],
            'status_publikasi' => 'draft',
            'is_pinned' => false,
            'status_acara' => 'aktif',
            'alasan_status' => null,
        ]);

        return redirect()->route('admin.nobar.edit', ['nobar' => $duplicate->id])
            ->with('status', 'Acara nobar berhasil diduplikasi sebagai draft baru dengan salinan poster independen.');
    }

    public function destroy(NobarEvent $nobar, MediaUploadService $media): RedirectResponse
    {
        $media->deletePosterAndOg($nobar->poster_path, $nobar->poster_og_path);
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
     * Check php.ini limits (upload_max_filesize / post_max_size) before validation.
     */
    private function ensureUploadWithinPhpLimits(Request $request): void
    {
        if ($request->hasFile('poster')) {
            $file = $request->file('poster');
            if (! $file->isValid()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'poster' => 'Ukuran file melebihi batas unggahan server (upload_max_filesize / post_max_size). Silakan unggah file di bawah 3 MB.',
                ]);
            }
        }
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
            'poster_alt' => ['nullable', 'string', 'max:255'],
            'poster' => [
                'nullable',
                'file',
                'max:3072',
                function ($attribute, $value, $fail) {
                    if (! $value instanceof \Illuminate\Http\UploadedFile) {
                        return;
                    }

                    $mime = $value->getMimeType();
                    if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                        $fail('Format file poster harus berupa gambar JPG, PNG, atau WebP (bukan SVG).');

                        return;
                    }

                    $info = @getimagesize($value->getPathname());
                    if (! $info || $info[0] === 0 || $info[1] === 0) {
                        $fail('Berkas gambar poster tidak valid atau rusak.');

                        return;
                    }

                    [$width, $height] = $info;

                    if (($width * $height) > 40_000_000) {
                        $fail('Resolusi gambar terlalu besar (maksimal 40 megapiksel).');

                        return;
                    }

                    if (min($width, $height) < 600) {
                        $fail('Dimensi poster terlalu kecil. Sisi terpendek minimal 600 piksel (saat ini: '.min($width, $height).' piksel).');

                        return;
                    }
                },
            ],
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
