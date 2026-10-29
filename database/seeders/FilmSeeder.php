<?php

namespace Database\Seeders;

use App\Models\Film;
use Illuminate\Database\Seeder;

class FilmSeeder extends Seeder
{
    public function run(): void
    {
        $films = [
            [
                'title' => 'Malam di Bukit Bintang',
                'duration_minutes' => 105,
                'genre' => 'Drama keluarga',
                'age_rating' => 'SU',
                'synopsis' => 'Kisah keluarga kecil yang menemukan kembali kebersamaan di sebuah desa pegunungan.',
                'is_coming_soon' => false,
                'release_date' => now()->subDays(10)->toDateString(),
            ],
            [
                'title' => 'Misi Terakhir Pak Pos',
                'duration_minutes' => 98,
                'genre' => 'Komedi petualangan',
                'age_rating' => '13+',
                'synopsis' => 'Pak pos dan cucunya berkejaran dengan waktu untuk mengantar surat penting.',
                'is_coming_soon' => false,
                'release_date' => now()->subDays(5)->toDateString(),
            ],
            [
                'title' => 'Rahasia Rumah Tua',
                'duration_minutes' => 112,
                'genre' => 'Misteri',
                'age_rating' => '17+',
                'synopsis' => 'Tiga sahabat menyelidiki cerita lama yang tersimpan di rumah kosong dekat sawah.',
                'is_coming_soon' => false,
                'release_date' => now()->subDays(2)->toDateString(),
            ],
            [
                'title' => 'Laskar Bahari',
                'duration_minutes' => 110,
                'genre' => 'Petualangan Maritim',
                'age_rating' => 'SU',
                'synopsis' => 'Kisah inspiratif para pemuda pesisir menjaga warisan laut nusantara.',
                'is_coming_soon' => true,
                'release_date' => now()->addDays(5)->toDateString(),
            ],
            [
                'title' => 'Detektif Cilik & Misteri Jam Gadang',
                'duration_minutes' => 95,
                'genre' => 'Animasi Keluarga',
                'age_rating' => 'SU',
                'synopsis' => 'Petualangan seru anak-anak memecahkan teka-teki kuno di pusat kota tua.',
                'is_coming_soon' => true,
                'release_date' => now()->addDays(12)->toDateString(),
            ],
            [
                'title' => 'Pusaka Rimba Terlarang',
                'duration_minutes' => 108,
                'genre' => 'Aksi Fantasi',
                'age_rating' => '13+',
                'synopsis' => 'Perjalanan menegangkan menelusuri hutan purba untuk mencari pusaka pelindung desa.',
                'is_coming_soon' => true,
                'release_date' => now()->addDays(18)->toDateString(),
            ],
        ];

        foreach ($films as $film) {
            $model = Film::withTrashed()->updateOrCreate(
                ['title' => $film['title']],
                [
                    ...$film,
                    'license_notes' => 'Judul dan sinopsis ini hanya data demo. Pastikan hak pemutaran film tersedia sebelum digunakan.',
                    'is_active' => true,
                ],
            );

            if ($model->trashed()) {
                $model->restore();
            }
        }
    }
}
