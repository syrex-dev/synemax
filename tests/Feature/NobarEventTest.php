<?php

namespace Tests\Feature;

use App\Models\NobarEvent;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NobarEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'info_nobar_enabled'], ['value' => '1']);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
        ]);
    }

    public function test_public_can_view_nobar_index_when_enabled(): void
    {
        $published = NobarEvent::create([
            'judul' => 'Nobar Akbar Final Komunitas',
            'slug' => 'nobar-akbar-final-komunitas',
            'kompetisi' => 'Liga Komunitas',
            'tim_a' => 'Tim Garuda',
            'tim_b' => 'Tim Rajawali',
            'kickoff_at' => now()->addDays(2),
            'durasi_menit' => 150,
            'lokasi_nama' => 'SynemaX Studio',
            'alamat_patokan' => 'Jl. Mawar No. 10',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'is_free' => true,
            'status_publikasi' => 'terbit',
            'status_acara' => 'aktif',
        ]);

        $draft = NobarEvent::create([
            'judul' => 'Nobar Rahasia Masih Draft',
            'slug' => 'nobar-rahasia-masih-draft',
            'kompetisi' => 'Uji Coba',
            'tim_a' => 'Tim Merah',
            'tim_b' => 'Tim Putih',
            'kickoff_at' => now()->addDays(3),
            'durasi_menit' => 150,
            'lokasi_nama' => 'SynemaX Studio',
            'alamat_patokan' => 'Jl. Mawar No. 10',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'is_free' => true,
            'status_publikasi' => 'draft',
            'status_acara' => 'aktif',
        ]);

        $response = $this->get(route('nobar.index'));

        $response->assertOk();
        $response->assertSee('Nobar Akbar Final Komunitas');
        $response->assertSee('Tim Garuda');
        $response->assertSee('Tim Rajawali');
        $response->assertDontSee('Nobar Rahasia Masih Draft');
    }

    public function test_nobar_index_aborts_404_when_feature_disabled(): void
    {
        Setting::updateOrCreate(['key' => 'info_nobar_enabled'], ['value' => '0']);

        $this->get(route('nobar.index'))->assertNotFound();
    }

    public function test_public_can_view_published_nobar_detail_with_open_graph_meta_tags(): void
    {
        $event = NobarEvent::create([
            'judul' => 'Nobar Final: Tim Biru vs Tim Merah',
            'slug' => 'nobar-final-tim-biru-vs-merah',
            'kompetisi' => 'Piala Super',
            'tim_a' => 'Tim Biru',
            'tim_b' => 'Tim Merah',
            'kickoff_at' => Carbon::parse('2026-11-20 20:00:00'),
            'durasi_menit' => 150,
            'lokasi_nama' => 'SynemaX Lounge Utama',
            'alamat_patokan' => 'Samping Minimarket Melati',
            'maps_url' => 'https://maps.google.com/?q=-6.200000,106.816666',
            'is_free' => true,
            'status_publikasi' => 'terbit',
            'status_acara' => 'aktif',
            'catatan' => "Harap hadir 15 menit sebelum sepak mula.\n\n- Dilarang merokok di area studio",
        ]);

        $response = $this->get(route('nobar.show', ['slug' => $event->slug]));

        $response->assertOk();
        $response->assertSee('Nobar Final: Tim Biru vs Tim Merah');
        $response->assertSee('SynemaX Lounge Utama');
        $response->assertSee('Samping Minimarket Melati');
        $response->assertSee('https://maps.google.com/?q=-6.200000,106.816666');
        $response->assertSee('Dilarang merokok di area studio');

        // Open Graph Meta
        $response->assertSee('<meta property="og:title" content="Nobar Final: Tim Biru vs Tim Merah · Nobar SynemaX">', false);
        $response->assertSee('<meta property="og:type" content="event">', false);
    }

    public function test_draft_nobar_event_is_404_for_guest_but_accessible_for_admin(): void
    {
        $event = NobarEvent::create([
            'judul' => 'Nobar Draft Hanya Admin',
            'slug' => 'nobar-draft-hanya-admin',
            'tim_a' => 'Tim A',
            'tim_b' => 'Tim B',
            'kickoff_at' => now()->addDays(1),
            'durasi_menit' => 150,
            'lokasi_nama' => 'Studio',
            'alamat_patokan' => 'Jalan',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'is_free' => true,
            'status_publikasi' => 'draft',
            'status_acara' => 'aktif',
        ]);

        // Guest -> 404
        $this->get(route('nobar.show', ['slug' => $event->slug]))->assertNotFound();

        // Admin -> 200 with preview banner
        $admin = $this->createAdminUser();
        $this->actingAs($admin)
            ->get(route('nobar.show', ['slug' => $event->slug]))
            ->assertOk()
            ->assertSee('Mode Pratinjau Admin');
    }

    public function test_calendar_ics_download_returns_valid_icalendar_stream(): void
    {
        $event = NobarEvent::create([
            'judul' => 'Nobar Super League Kalender',
            'slug' => 'nobar-super-league-kalender',
            'tim_a' => 'Tim Elang',
            'tim_b' => 'Tim Harimau',
            'kickoff_at' => Carbon::parse('2026-12-01 19:30:00'),
            'durasi_menit' => 120,
            'lokasi_nama' => 'SynemaX Studio',
            'alamat_patokan' => 'Jl. Melati No. 1',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'is_free' => true,
            'status_publikasi' => 'terbit',
            'status_acara' => 'aktif',
        ]);

        $response = $this->get(route('nobar.calendar', ['slug' => $event->slug]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="nobar-nobar-super-league-kalender.ics"');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $response->getContent());
        $this->assertStringContainsString('SUMMARY:Nobar: Nobar Super League Kalender', $response->getContent());
        $this->assertStringContainsString('END:VCALENDAR', $response->getContent());
    }

    public function test_admin_can_create_nobar_event_with_auto_slug(): void
    {
        $admin = $this->createAdminUser();

        $postData = [
            'judul' => 'Nobar Spesial Pembukaan Musim',
            'kompetisi' => 'Piala Komunitas 2026',
            'tim_a' => 'Tim Singa Laut',
            'tim_b' => 'Tim Banteng Putih',
            'kickoff_at' => '2026-11-15T19:30',
            'durasi_menit' => 150,
            'lokasi_nama' => 'SynemaX Studio B',
            'alamat_patokan' => 'Ruko Plaza Sektor 3',
            'maps_url' => 'https://maps.google.com/?q=-6.2001,106.8167',
            'is_free' => 1,
            'status_publikasi' => 'terbit',
            'is_pinned' => 0,
            'status_acara' => 'aktif',
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.nobar.store'), $postData);

        $response->assertRedirect(route('admin.nobar.index'));

        $this->assertDatabaseHas('nobar_events', [
            'judul' => 'Nobar Spesial Pembukaan Musim',
            'slug' => 'nobar-spesial-pembukaan-musim',
            'tim_a' => 'Tim Singa Laut',
            'tim_b' => 'Tim Banteng Putih',
            'lokasi_nama' => 'SynemaX Studio B',
        ]);
    }

    public function test_admin_validates_maps_url_must_be_valid_https_url(): void
    {
        $admin = $this->createAdminUser();

        $invalidData = [
            'judul' => 'Nobar Link Bahaya',
            'tim_a' => 'Tim A',
            'kickoff_at' => '2026-11-15T19:30',
            'durasi_menit' => 150,
            'lokasi_nama' => 'SynemaX',
            'alamat_patokan' => 'Jalan',
            'maps_url' => 'javascript:alert(1)', // Malicious / non-HTTPS
            'is_free' => 1,
            'status_publikasi' => 'terbit',
            'is_pinned' => 0,
            'status_acara' => 'aktif',
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.nobar.store'), $invalidData);

        $response->assertSessionHasErrors(['maps_url']);
    }

    public function test_admin_can_update_status_and_reason_for_postponement(): void
    {
        $admin = $this->createAdminUser();

        $event = NobarEvent::create([
            'judul' => 'Nobar Cuaca Ekstrem',
            'slug' => 'nobar-cuaca-ekstrem',
            'tim_a' => 'Tim Badai',
            'tim_b' => 'Tim Kilat',
            'kickoff_at' => now()->addDays(1),
            'durasi_menit' => 150,
            'lokasi_nama' => 'SynemaX Studio',
            'alamat_patokan' => 'Jl. Mawar No. 10',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'is_free' => true,
            'status_publikasi' => 'terbit',
            'status_acara' => 'aktif',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.nobar.index'))
            ->patch(route('admin.nobar.status', ['nobar' => $event->id]), [
                'status_acara' => 'ditunda',
                'alasan_status' => 'Pertandingan resmi ditunda karena cuaca buruk',
            ]);

        $response->assertRedirect(route('admin.nobar.index'));

        $this->assertDatabaseHas('nobar_events', [
            'id' => $event->id,
            'status_acara' => 'ditunda',
            'alasan_status' => 'Pertandingan resmi ditunda karena cuaca buruk',
        ]);

        // Public page reflects postponed status and reason
        $this->get(route('nobar.show', ['slug' => $event->slug]))
            ->assertOk()
            ->assertSee('Ditunda')
            ->assertSee('Pertandingan resmi ditunda karena cuaca buruk');
    }
}
