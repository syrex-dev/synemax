<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FormRequestAttributesAndEmptySnacksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'seats_capacity'], ['value' => '6']);
        Setting::updateOrCreate(['key' => 'minimum_public_viewers'], ['value' => '4']);
        Setting::updateOrCreate(['key' => 'weekday_price'], ['value' => '20000']);
        Setting::updateOrCreate(['key' => 'weekend_price'], ['value' => '24000']);
        Setting::updateOrCreate(['key' => 'payment_methods'], ['value' => 'di_tempat,transfer,qris']);
        app(CinemaSettings::class)->clearCache();
    }

    /**
     * Test that all FormRequest classes have attributes() defined for their rules.
     */
    public function test_all_form_requests_have_indonesian_attributes_for_all_rules(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $requestFiles = File::allFiles(app_path('Http/Requests'));
        $this->assertNotEmpty($requestFiles, 'No FormRequests found to test.');

        foreach ($requestFiles as $file) {
            $class = 'App\\Http\\Requests\\'.str_replace(
                ['/', '\\', '.php'],
                ['\\', '\\', ''],
                $file->getRelativePathname()
            );

            if (! class_exists($class)) {
                continue;
            }

            $ref = new \ReflectionClass($class);
            if (! $ref->isSubclassOf(FormRequest::class) || $ref->isAbstract()) {
                continue;
            }

            $instance = new $class;
            $this->assertTrue(
                method_exists($instance, 'attributes'),
                "FormRequest {$class} must implement attributes() method."
            );

            $attributes = $instance->attributes();
            $this->assertIsArray($attributes, "FormRequest {$class} attributes() must return an array.");

            if (method_exists($instance, 'rules')) {
                $instance->setUserResolver(fn () => $user);
                $instance->initialize([], [], ['package' => 'reguler']);
                $rules = $instance->rules();

                foreach (array_keys($rules) as $field) {
                    $hasAttribute = isset($attributes[$field])
                        || isset($attributes[preg_replace('/\.\d+\./', '.*.', $field)])
                        || str_starts_with($field, 'snacks.');

                    $this->assertTrue(
                        $hasAttribute,
                        "FormRequest [{$class}] is missing an Indonesian attribute for field [{$field}]."
                    );
                }
            }
        }
    }

    /**
     * Test that sending a payload with 10 empty/0 snacks succeeds without qty errors and creates booking.
     */
    public function test_booking_payload_with_ten_empty_snacks_succeeds_without_snack_errors(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Test',
            'slug' => 'film-qa-test-'.uniqid(),
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'tersedia',
        ]);

        $category = MenuCategory::query()->create([
            'name' => 'Minuman',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            MenuItem::query()->create([
                'category_id' => $category->id,
                'name' => "Snack Item {$i}",
                'price' => 10000,
                'is_active' => true,
            ]);
        }

        $payload = [
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'people_count' => 2,
            'film_id' => $film->id,
            'payment_method' => 'di_tempat',
            'notes' => 'Tanpa jajan',
            'snacks' => [
                1 => ['qty' => '', 'note' => ''],
                2 => ['qty' => '0', 'note' => ''],
                3 => ['qty' => null, 'note' => ''],
                4 => ['qty' => '', 'note' => ''],
                5 => ['qty' => '0', 'note' => ''],
                6 => ['qty' => '', 'note' => ''],
                7 => ['qty' => '0', 'note' => ''],
                8 => ['qty' => '', 'note' => ''],
                9 => ['qty' => '0', 'note' => ''],
                10 => ['qty' => '', 'note' => ''],
            ],
        ];

        $response = $this->post(route('booking.store', ['session' => $session->id]), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', [
            'session_id' => $session->id,
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '6281234567890',
        ]);

        $booking = Booking::where('session_id', $session->id)->first();
        $this->assertNotNull($booking);
        $this->assertCount(0, $booking->items, 'Booking should not have any snack items.');
    }
}
