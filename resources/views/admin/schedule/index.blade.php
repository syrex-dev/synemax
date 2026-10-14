@extends('layouts.admin')

@section('title', 'Kelola jadwal')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="text-3xl font-black">Kelola jadwal</h1><p class="mt-2 text-sm text-text-muted">Atur jam sesi, hari libur, dan tanggal yang ditutup.</p></div>
        <form method="POST" action="{{ route('admin.schedule.generate') }}">@csrf<button class="inline-flex min-h-11 items-center justify-center rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast hover:bg-accent-hover">Generate 14 hari</button></form>
    </div>

    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-danger/50 bg-danger/10 p-4 text-sm text-danger">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif

    <section class="mt-8">
        <div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-xl font-bold">Template jam sesi</h2><p class="mt-1 text-sm text-text-muted">Durasi adalah lama sesi sejak jam mulai. Durasi awal 150 menit.</p></div></div>
        <div class="mt-4 grid gap-4">
            @foreach ($dayTypes as $type => $label)
                <article class="min-w-0 overflow-hidden rounded-lg border border-border bg-surface">
                    <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3"><h3 class="font-bold">{{ $label }}</h3><span class="text-xs text-text-muted">{{ $templates->where('day_type', $type)->count() }} jam</span></div>
                    <div class="hidden xl:block">
                        <x-admin.table class="rounded-none" table-class="text-sm">
                            <x-slot:columns>
                                <col style="width: 29%">
                                <col style="width: 27%">
                                <col style="width: 19%">
                                <col style="width: 25%">
                            </x-slot:columns>
                            <x-slot:head>
                                <th scope="col" class="px-3 py-2 font-semibold">Jam mulai</th>
                                <th scope="col" class="px-3 py-2 font-semibold">Durasi</th>
                                <th scope="col" class="px-2 py-2 font-semibold">Status</th>
                                <th scope="col" class="px-2 py-2 text-right font-semibold">Aksi</th>
                            </x-slot:head>
                                @forelse ($templates->where('day_type', $type) as $template)
                                    <tr x-data="{ initial: ['{{ substr((string) $template->start_time, 0, 5) }}', {{ (int) $template->duration_minutes }}, {{ $template->is_active ? 'true' : 'false' }}], durationValue: {{ (int) $template->duration_minutes }}, dirty: false, saving: false, activeLabel: '{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}', formatDuration(value) { const hours = Math.floor(Number(value) / 60); const remainder = Number(value) % 60; return '= ' + (hours ? hours + ' jam' : '') + (hours && remainder ? ' ' : '') + (remainder ? remainder + ' menit' : '') }, sync() { this.dirty = this.$refs.start.value !== this.initial[0] || Number(this.durationValue) !== this.initial[1] || this.$refs.active.checked !== this.initial[2]; this.activeLabel = this.$refs.active.checked ? 'Aktif' : 'Nonaktif' } }" @input="sync()" @change="sync()" class="transition-colors hover:bg-surface-2/60">
                                        <td class="px-3 py-3">
                                            <form id="template-update-desktop-{{ $template->id }}" method="POST" action="{{ route('admin.schedule.templates.update', $template) }}" @submit="saving = true">@csrf @method('PATCH')<input type="hidden" name="day_type" value="{{ $type }}"></form>
                                            <label for="start-desktop-{{ $template->id }}" class="sr-only">Jam mulai</label><input id="start-desktop-{{ $template->id }}" x-ref="start" form="template-update-desktop-{{ $template->id }}" name="start_time" type="time" value="{{ substr((string) $template->start_time, 0, 5) }}" required class="min-h-10 min-w-[7.5rem] w-full rounded-lg border border-border bg-bg px-2 text-base text-text">
                                            @error('start_time')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                                        </td>
                                        <td class="px-3 py-3">
                                            <div>
                                                <label for="duration-desktop-{{ $template->id }}" class="sr-only">Durasi</label>
                                                <div class="flex min-h-10 items-center rounded-lg border border-border bg-bg focus-within:ring-2 focus-within:ring-focus"><input id="duration-desktop-{{ $template->id }}" x-ref="duration" x-model.number="durationValue" form="template-update-desktop-{{ $template->id }}" name="duration_minutes" type="number" min="30" max="600" required class="min-h-9 min-w-0 flex-1 border-0 bg-transparent px-2 text-base text-text focus:ring-0"><span class="px-2 text-xs text-text-muted">menit</span></div>
                                                <p class="mt-1 text-xs text-text-muted" x-text="formatDuration(durationValue)"></p>
                                            </div>
                                        </td>
                                        <td class="px-2 py-3">
                                            <input type="hidden" form="template-update-desktop-{{ $template->id }}" name="is_active" value="0">
                                            <label class="flex min-h-10 cursor-pointer items-center gap-2">
                                                <input x-ref="active" form="template-update-desktop-{{ $template->id }}" name="is_active" type="checkbox" value="1" @checked($template->is_active) class="peer sr-only">
                                                <span aria-hidden="true" class="relative h-6 w-10 shrink-0 rounded-full bg-border transition-colors peer-checked:bg-accent after:absolute after:left-1 after:top-1 after:size-4 after:rounded-full after:bg-surface after:transition-transform peer-checked:after:translate-x-4"></span>
                                                <span class="text-xs text-text" x-text="activeLabel">{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                            </label>
                                        </td>
                                        <td class="px-2 py-3">
                                            <span x-show="dirty" x-cloak class="mb-1 block text-right text-xs font-semibold text-warning">Belum disimpan</span>
                                            <div class="flex flex-wrap items-center justify-end gap-2">
                                                <button form="template-update-desktop-{{ $template->id }}" :disabled="!dirty || saving" class="min-h-10 min-w-20 rounded-lg bg-accent px-2 text-xs font-bold text-accent-contrast disabled:cursor-not-allowed disabled:opacity-45"><span x-show="!saving">Simpan</span><span x-show="saving" x-cloak>…</span></button>
                                                <form method="POST" action="{{ route('admin.schedule.templates.destroy', $template) }}" onsubmit="return confirm('Yakin hapus jam {{ substr((string) $template->start_time, 0, 5) }}?')">@csrf @method('DELETE')<button aria-label="Hapus jam {{ substr((string) $template->start_time, 0, 5) }}" title="Hapus jam {{ substr((string) $template->start_time, 0, 5) }}" class="grid size-10 place-items-center rounded-lg border border-danger/40 text-danger hover:bg-danger-surface"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg></button></form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-7 text-center text-sm text-text-muted">Belum ada jam untuk {{ $label }}.</td></tr>
                                @endforelse
                        </x-admin.table>
                    </div>
                    <div class="space-y-3 p-3 xl:hidden">
                        @forelse ($templates->where('day_type', $type) as $template)
                            <div x-data="{ initial: ['{{ substr((string) $template->start_time, 0, 5) }}', {{ (int) $template->duration_minutes }}, {{ $template->is_active ? 'true' : 'false' }}], durationValue: {{ (int) $template->duration_minutes }}, dirty: false, saving: false, activeLabel: '{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}', formatDuration(value) { const hours = Math.floor(Number(value) / 60); const remainder = Number(value) % 60; return '= ' + (hours ? hours + ' jam' : '') + (hours && remainder ? ' ' : '') + (remainder ? remainder + ' menit' : '') }, sync() { this.dirty = this.$refs.start.value !== this.initial[0] || Number(this.durationValue) !== this.initial[1] || this.$refs.active.checked !== this.initial[2]; this.activeLabel = this.$refs.active.checked ? 'Aktif' : 'Nonaktif' } }" @input="sync()" @change="sync()" class="rounded-lg border border-border bg-bg p-4">
                                <form id="template-update-mobile-{{ $template->id }}" method="POST" action="{{ route('admin.schedule.templates.update', $template) }}" @submit="saving = true">@csrf @method('PATCH')<input type="hidden" name="day_type" value="{{ $type }}"></form>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div><label for="start-mobile-{{ $template->id }}" class="mb-1 block text-sm font-semibold">Jam mulai (24 jam)</label><input id="start-mobile-{{ $template->id }}" x-ref="start" form="template-update-mobile-{{ $template->id }}" name="start_time" type="time" value="{{ substr((string) $template->start_time, 0, 5) }}" required class="min-h-11 w-full rounded-lg border border-border bg-surface px-3 text-base text-text">@error('start_time')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror</div>
                                    <div>
                                        <label for="duration-mobile-{{ $template->id }}" class="mb-1 block text-sm font-semibold">Durasi sesi</label>
                                        <div class="flex min-h-11 items-center rounded-lg border border-border bg-surface focus-within:ring-2 focus-within:ring-focus"><input id="duration-mobile-{{ $template->id }}" x-ref="duration" x-model.number="durationValue" form="template-update-mobile-{{ $template->id }}" name="duration_minutes" type="number" min="30" max="600" required class="min-h-10 min-w-0 flex-1 border-0 bg-transparent px-3 text-base text-text focus:ring-0"><span class="px-3 text-sm text-text-muted">menit</span></div><p class="mt-1 text-xs text-text-muted" x-text="formatDuration(durationValue)"></p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <input type="hidden" form="template-update-mobile-{{ $template->id }}" name="is_active" value="0">
                                        <label class="flex min-h-11 cursor-pointer items-center gap-2"><input x-ref="active" form="template-update-mobile-{{ $template->id }}" name="is_active" type="checkbox" value="1" @checked($template->is_active) class="peer sr-only"><span aria-hidden="true" class="relative h-6 w-10 rounded-full bg-border transition-colors peer-checked:bg-accent after:absolute after:left-1 after:top-1 after:size-4 after:rounded-full after:bg-surface after:transition-transform peer-checked:after:translate-x-4"></span><span class="text-sm text-text" x-text="activeLabel">{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</span></label>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-3 border-t border-border pt-3">
                                    <span x-show="dirty" x-cloak class="text-xs font-semibold text-warning">Belum disimpan</span><span x-show="!dirty" class="text-xs text-text-muted">Tersimpan</span>
                                    <div class="flex gap-2"><button form="template-update-mobile-{{ $template->id }}" :disabled="!dirty || saving" class="min-h-11 rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast disabled:cursor-not-allowed disabled:opacity-45"><span x-show="!saving">Simpan</span><span x-show="saving" x-cloak>Menyimpan…</span></button>
                                        <form method="POST" action="{{ route('admin.schedule.templates.destroy', $template) }}" onsubmit="return confirm('Yakin hapus jam {{ substr((string) $template->start_time, 0, 5) }}?')">@csrf @method('DELETE')<button aria-label="Hapus jam {{ substr((string) $template->start_time, 0, 5) }}" class="grid size-11 place-items-center rounded-lg border border-danger/40 text-danger hover:bg-danger-surface"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg></button></form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-lg border border-border bg-bg p-4 text-center text-sm text-text-muted">Belum ada jam untuk {{ $label }}.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('admin.schedule.templates.store') }}" class="grid gap-3 border-t border-border bg-surface-2/50 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                        @csrf<input type="hidden" name="day_type" value="{{ $type }}">
                        <div><label for="new-time-{{ $type }}" class="mb-1 block text-sm font-semibold">Jam mulai baru</label><input id="new-time-{{ $type }}" name="start_time" type="time" required class="min-h-11 w-full rounded-lg border border-border bg-surface px-3 text-base text-text"><p class="mt-1 text-xs text-text-muted">Format 24 jam, contoh 19:30.</p></div>
                        <div x-data="{ minutes: 150, format(value) { const hours = Math.floor(Number(value) / 60); const remainder = Number(value) % 60; return '= ' + (hours ? hours + ' jam' : '') + (hours && remainder ? ' ' : '') + (remainder ? remainder + ' menit' : '') } }">
                            <label for="new-duration-{{ $type }}" class="mb-1 block text-sm font-semibold">Durasi sesi</label>
                            <div class="flex min-h-11 items-center rounded-lg border border-border bg-surface focus-within:ring-2 focus-within:ring-focus"><input id="new-duration-{{ $type }}" x-model.number="minutes" name="duration_minutes" type="number" min="30" max="600" value="150" required class="min-h-10 min-w-0 flex-1 border-0 bg-transparent px-3 text-base text-text focus:ring-0"><span class="px-3 text-sm text-text-muted">menit</span></div><p class="mt-1 text-xs text-text-muted" x-text="format(minutes)">= 2 jam 30 menit</p>
                        </div>
                        <button class="min-h-11 rounded-lg border border-border bg-surface px-4 text-sm font-semibold text-text hover:bg-surface-2">Tambah jam baru</button>
                    </form>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mt-10 grid gap-4 lg:grid-cols-2">
        <article class="rounded-2xl border border-border bg-surface p-4 sm:p-5">
            <h2 class="text-lg font-bold">Hari libur</h2>
            <form method="POST" action="{{ route('admin.schedule.holidays.store') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">@csrf
                <div><label class="mb-1 block text-sm font-semibold" for="holiday_date">Tanggal libur</label><input id="holiday_date" name="holiday_date" type="date" required class="min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-base text-text"></div>
                <div><label class="mb-1 block text-sm font-semibold" for="holiday_name">Nama hari libur</label><input id="holiday_name" name="name" maxlength="120" required placeholder="Contoh: Hari Desa" class="min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-base text-text placeholder:text-text-muted"></div>
                <button class="min-h-11 rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast">Tambah</button>
            </form>
            <div class="mt-4 space-y-2">
                @forelse ($holidays as $holiday)
                    <div class="flex items-center justify-between gap-2 rounded-lg bg-surface-2 px-3 py-2 text-sm"><span>{{ $holiday->holiday_date }} · {{ $holiday->name }}</span><form method="POST" action="{{ route('admin.schedule.holidays.destroy', $holiday) }}" onsubmit="return confirm('Hapus hari libur ini?')">@csrf @method('DELETE')<button class="text-xs text-danger hover:underline">Hapus</button></form></div>
                @empty<p class="text-xs text-text-muted">Belum ada hari libur tambahan.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-border bg-surface p-4 sm:p-5">
            <h2 class="text-lg font-bold">Tutup tanggal atau sesi</h2>
            <form method="POST" action="{{ route('admin.schedule.blocked.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf
                <div><label for="blocked_date" class="mb-1 block text-sm font-semibold">Tanggal ditutup</label><input id="blocked_date" name="blocked_date" type="date" required class="min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-base text-text"></div>
                <div><label for="blocked_time" class="mb-1 block text-sm font-semibold">Jam sesi (opsional)</label><input id="blocked_time" name="start_time" type="time" class="min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-base text-text"><p class="mt-1 text-xs text-text-muted">Kosongkan untuk menutup seharian.</p></div>
                <div class="sm:col-span-2"><label for="blocked_reason" class="mb-1 block text-sm font-semibold">Alasan penutupan</label><input id="blocked_reason" name="reason" required maxlength="180" placeholder="Contoh: maintenance" class="min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-base text-text placeholder:text-text-muted"></div>
                <button class="min-h-11 rounded-lg border border-danger/40 px-4 text-sm font-bold text-danger hover:bg-danger-surface sm:col-span-2">Tutup tanggal/sesi</button>
            </form>
            <div class="mt-4 space-y-2">
                @forelse ($blockedDates as $blocked)
                    <div class="flex items-center justify-between gap-2 rounded-lg bg-surface-2 px-3 py-2 text-sm"><span>{{ $blocked->blocked_date }} · {{ $blocked->scope_key === 'ALL' ? 'Seharian' : $blocked->scope_key }} — {{ $blocked->reason }}</span><form method="POST" action="{{ route('admin.schedule.blocked.destroy', $blocked) }}" onsubmit="return confirm('Buka kembali tanggal atau sesi ini?')">@csrf @method('DELETE')<button class="text-xs text-danger hover:underline">Buka</button></form></div>
                @empty<p class="text-xs text-text-muted">Tidak ada tanggal atau sesi yang sedang ditutup.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-10">
        <h2 class="text-xl font-bold">Sesi 14 hari ke depan</h2>
        <div class="mt-4 space-y-3">
            @forelse ($sessions as $session)
                <form method="POST" action="{{ route('admin.schedule.sessions.update', $session) }}" class="grid gap-3 rounded-lg border border-border bg-surface p-4 sm:grid-cols-[minmax(8rem,1fr)_minmax(11rem,1.5fr)_minmax(8rem,1fr)_auto] sm:items-end">@csrf @method('PATCH')
                    <div><p class="font-semibold">{{ $session->session_date }} · {{ substr((string) $session->start_time, 0, 5) }}</p><p class="mt-1 text-xs text-text-muted">{{ $session->is_private ? 'Private' : 'Publik' }} · {{ $session->seatCapacity() }} kursi</p></div>
                    <div><label class="mb-1 block text-sm font-semibold" for="film-session-{{ $session->id }}">Film sesi</label><select id="film-session-{{ $session->id }}" name="film_id" class="min-h-11 w-full rounded-lg border border-border bg-bg px-2 text-base text-text"><option value="">Film belum dipilih</option>@foreach ($films as $film)<option value="{{ $film->id }}" @selected($session->film_id === $film->id)>{{ $film->title }}</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-sm font-semibold" for="status-session-{{ $session->id }}">Status sesi</label><select id="status-session-{{ $session->id }}" name="status" class="min-h-11 w-full rounded-lg border border-border bg-bg px-2 text-base text-text"><option value="tersedia" @selected($session->status === 'tersedia')>Tersedia</option><option value="ditutup" @selected($session->status === 'ditutup')>Tutup</option></select></div>
                    <button class="min-h-11 rounded-lg border border-border px-4 text-sm font-semibold hover:bg-surface-2">Simpan</button>
                </form>
            @empty<p class="rounded-xl border border-border bg-surface p-4 text-sm text-text-muted">Belum ada sesi. Tekan “Generate 14 hari” untuk membuat jadwal.</p>
            @endforelse
        </div>
    </section>
@endsection
