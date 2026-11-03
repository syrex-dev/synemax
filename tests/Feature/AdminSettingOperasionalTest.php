<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingOperasionalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private CinemaSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->settings = app(CinemaSettings::class);
    }

    public function test_admin_can_save_multiple_transfer_bank_and_ewallet_accounts(): void
    {
        $accounts = [
            [
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_holder' => 'Bioskop SynemaX',
            ],
            [
                'bank_name' => 'Bank Mandiri',
                'account_number' => '9876543210',
                'account_holder' => 'SynemaX Cinema Mandiri',
            ],
            [
                'bank_name' => 'Dana',
                'account_number' => '081298765432',
                'account_holder' => 'Admin SynemaX',
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            '_tab' => 'operasional',
            'capacity_per_session' => 6,
            'max_seats_per_booking' => 6,
            'minimum_public_viewers' => 3,
            'low_attendance_warning_window' => 72,
            'booking_cutoff_minutes' => 30,
            'cancellation_cutoff_hours' => 6,
            'admin_whatsapp' => '085189730980',
            'electricity_cost_per_session' => 5000,
            'snacks_enabled' => 1,
            'ticket_arrive_minutes' => 15,
            'ticket_studio_name' => 'Studio 1 Utama',
            'ticket_notes' => 'Tunjukkan e-tiket ini kepada petugas.',
            'payment_methods' => ['di_tempat', 'transfer'],
            'transfer_accounts' => $accounts,
            'payment_time_limit_minutes' => 10,
        ]);

        $response->assertRedirect(route('admin.settings.edit', ['tab' => 'operasional']));
        $response->assertSessionHas('status', 'Perubahan pengaturan berhasil disimpan.');

        $this->settings->clearCache();
        $saved = $this->settings->transferAccounts();

        $this->assertCount(3, $saved);
        $this->assertSame('BCA', $saved[0]['bank_name']);
        $this->assertSame('1234567890', $saved[0]['account_number']);
        $this->assertSame('Bank Mandiri', $saved[1]['bank_name']);
        $this->assertSame('Dana', $saved[2]['bank_name']);
        $this->assertSame('081298765432', $saved[2]['account_number']);

        $this->assertSame(10, $this->settings->paymentTimeLimitMinutes());
        $this->assertSame('6285189730980', $this->settings->string('admin_whatsapp'));
        $this->assertSame('Studio 1 Utama', $this->settings->string('ticket_studio_name'));
        $this->assertSame(5000, $this->settings->integer('electricity_cost_per_session', 0));
    }

    public function test_admin_can_save_operasional_settings_even_if_qris_has_no_image_yet(): void
    {
        // QRIS is enabled in payment_methods, but no QRIS image exists yet
        $this->settings->set('qris_image_path', '', 'operasional');

        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            '_tab' => 'operasional',
            'capacity_per_session' => 8,
            'max_seats_per_booking' => 6,
            'minimum_public_viewers' => 2,
            'low_attendance_warning_window' => 48,
            'booking_cutoff_minutes' => 15,
            'cancellation_cutoff_hours' => 4,
            'admin_whatsapp' => '85189730980', // Starts with 8, normalized to 628...
            'electricity_cost_per_session' => 2500,
            'snacks_enabled' => 1,
            'ticket_arrive_minutes' => 20,
            'ticket_studio_name' => 'Studio Deluxe',
            'ticket_notes' => 'Harap hadir tepat waktu.',
            'payment_methods' => ['di_tempat', 'qris'],
            'payment_time_limit_minutes' => 15,
        ]);

        $response->assertRedirect(route('admin.settings.edit', ['tab' => 'operasional']));
        $response->assertSessionHas('status', 'Perubahan pengaturan berhasil disimpan.');
        $response->assertSessionHas('warning');

        $this->settings->clearCache();
        $this->assertSame(8, $this->settings->capacityPerSession());
        $this->assertSame(15, $this->settings->paymentTimeLimitMinutes());
        $this->assertSame('6285189730980', $this->settings->string('admin_whatsapp'));
        $this->assertSame('Studio Deluxe', $this->settings->string('ticket_studio_name'));
        $this->assertSame(20, $this->settings->ticketArriveMinutes());
    }

    public function test_admin_can_toggle_snacks_and_maintenance_off_successfully(): void
    {
        // First ensure snacks is on
        $this->settings->set('snacks_enabled', '1', 'operasional');
        $this->settings->set('maintenance_mode', '1', 'operasional');

        // Submit form with snacks_enabled unchecked (not present in POST)
        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            '_tab' => 'operasional',
            'capacity_per_session' => 6,
            'max_seats_per_booking' => 6,
            'minimum_public_viewers' => 3,
            'low_attendance_warning_window' => 72,
            'booking_cutoff_minutes' => 30,
            'cancellation_cutoff_hours' => 6,
            'admin_whatsapp' => '6285189730980',
            'electricity_cost_per_session' => 0,
            'ticket_arrive_minutes' => 15,
            'ticket_studio_name' => 'Studio 1',
            'payment_methods' => ['di_tempat'],
            // snacks_enabled is omitted because checkbox is unchecked
            // maintenance_mode is omitted because switch is unchecked
        ]);

        $response->assertRedirect(route('admin.settings.edit', ['tab' => 'operasional']));

        $this->settings->clearCache();
        $this->assertFalse($this->settings->boolean('snacks_enabled'));
        $this->assertFalse($this->settings->maintenanceMode());
    }

    public function test_settings_page_displays_active_tab_and_error_alerts(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.edit', ['tab' => 'operasional']));

        $response->assertOk();
        $response->assertSee('Daftar Rekening Bank');
        $response->assertSee('+ Tambah Bank / E-Wallet');
        $response->assertSee('Hapus Rekening');
        $response->assertSee('WhatsApp Admin Pemesanan');
    }
}
