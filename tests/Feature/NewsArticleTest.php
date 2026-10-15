<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsArticleTest extends TestCase
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

    public function test_public_can_view_news_index_with_published_articles(): void
    {
        $berita = NewsArticle::create([
            'judul' => 'Renovasi Studio Home Cinema Selesai',
            'slug' => 'renovasi-studio-home-cinema-selesai',
            'kategori' => 'Berita',
            'ringkasan' => 'Studio kini dilengkapi proyektor baru dan sofa empuk.',
            'konten' => 'Isi berita lengkap di sini.',
            'status_publikasi' => 'terbit',
            'tanggal_terbit' => now()->subDay(),
            'is_pinned' => false,
        ]);

        $promo = NewsArticle::create([
            'judul' => 'Promo Popcorn Akhir Pekan',
            'slug' => 'promo-popcorn-akhir-pekan',
            'kategori' => 'Promo',
            'ringkasan' => 'Dapatkan popcorn gratis setiap pembelian 4 tiket.',
            'konten' => 'Isi promo lengkap di sini.',
            'status_publikasi' => 'terbit',
            'tanggal_terbit' => now()->subHours(2),
            'is_pinned' => true,
        ]);

        $draft = NewsArticle::create([
            'judul' => 'Rencana Tarif Baru Masih Draft',
            'slug' => 'rencana-tarif-baru-masih-draft',
            'kategori' => 'Pengumuman',
            'ringkasan' => 'Draft pengumuman rahasia.',
            'konten' => 'Draft.',
            'status_publikasi' => 'draft',
            'tanggal_terbit' => now(),
            'is_pinned' => false,
        ]);

        // General list
        $response = $this->get(route('articles.index'));
        $response->assertOk();
        $response->assertSee('Renovasi Studio Home Cinema Selesai');
        $response->assertSee('Promo Popcorn Akhir Pekan');
        $response->assertDontSee('Rencana Tarif Baru Masih Draft');

        // Category filter
        $promoFilter = $this->get(route('articles.index', ['kategori' => 'Promo']));
        $promoFilter->assertOk();
        $promoFilter->assertSee('Promo Popcorn Akhir Pekan');
        $promoFilter->assertDontSee('Renovasi Studio Home Cinema Selesai');
    }

    public function test_news_index_aborts_404_when_feature_disabled(): void
    {
        Setting::updateOrCreate(['key' => 'info_nobar_enabled'], ['value' => '0']);

        $this->get(route('articles.index'))->assertNotFound();
    }

    public function test_lazy_scheduling_future_article_is_hidden_from_guest_until_published_time(): void
    {
        Carbon::setTestNow('2026-10-15 10:00:00');

        $futureArticle = NewsArticle::create([
            'judul' => 'Pengumuman Terjadwal Masa Depan',
            'slug' => 'pengumuman-terjadwal-masa-depan',
            'kategori' => 'Pengumuman',
            'ringkasan' => 'Artikel ini dijadwalkan terbit jam 14:00.',
            'konten' => 'Isi terjadwal lengkap.',
            'status_publikasi' => 'terbit',
            'tanggal_terbit' => Carbon::parse('2026-10-15 14:00:00'),
            'is_pinned' => false,
        ]);

        // At 10:00, guest cannot see it
        $this->get(route('articles.index'))
            ->assertOk()
            ->assertDontSee('Pengumuman Terjadwal Masa Depan');

        $this->get(route('articles.show', ['slug' => $futureArticle->slug]))
            ->assertNotFound();

        // Admin can see it with preview badge
        $admin = $this->createAdminUser();
        $this->actingAs($admin)
            ->get(route('articles.show', ['slug' => $futureArticle->slug]))
            ->assertOk()
            ->assertSee('Mode Pratinjau Admin');

        // Move clock to 14:05 (past publish time)
        Carbon::setTestNow('2026-10-15 14:05:00');

        // Now guest can see it
        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee('Pengumuman Terjadwal Masa Depan');

        $this->get(route('articles.show', ['slug' => $futureArticle->slug]))
            ->assertOk()
            ->assertSee('Pengumuman Terjadwal Masa Depan');

        Carbon::setTestNow();
    }

    public function test_public_can_view_published_article_detail_with_open_graph_meta_tags(): void
    {
        $article = NewsArticle::create([
            'judul' => 'Tips Nonton Bioskop Lebih Hemat Bareng Teman',
            'slug' => 'tips-nonton-bioskop-lebih-hemat',
            'kategori' => 'Berita',
            'ringkasan' => 'Panduan lengkap cara pesan tiket dan snack dengan harga promo.',
            'konten' => "## Tips Hemat\n\nPesan rombongan minimal **4 orang** untuk harga spesial.",
            'status_publikasi' => 'terbit',
            'tanggal_terbit' => now()->subDay(),
            'is_pinned' => true,
        ]);

        $response = $this->get(route('articles.show', ['slug' => $article->slug]));

        $response->assertOk();
        $response->assertSee('Tips Nonton Bioskop Lebih Hemat Bareng Teman');
        $response->assertSee('Pesan rombongan minimal');
        $response->assertSee('4 orang');

        // Open Graph Meta
        $response->assertSee('<meta property="og:type" content="article">', false);
        $response->assertSee('<meta property="og:title" content="Tips Nonton Bioskop Lebih Hemat Bareng Teman">', false);
        $response->assertSee('<meta property="og:description" content="Panduan lengkap cara pesan tiket dan snack dengan harga promo.">', false);
    }

    public function test_admin_can_create_article_with_auto_slug(): void
    {
        $admin = $this->createAdminUser();

        $postData = [
            'judul' => 'Judul Berita Baru Keren Sekali',
            'kategori' => 'Berita',
            'ringkasan' => 'Ringkasan berita baru.',
            'konten' => '## Konten Berita Baru',
            'status_publikasi' => 'terbit',
            'tanggal_terbit' => '2026-10-15T12:00',
            'is_pinned' => 0,
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.articles.store'), $postData);

        $response->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseHas('news_articles', [
            'judul' => 'Judul Berita Baru Keren Sekali',
            'slug' => 'judul-berita-baru-keren-sekali',
            'kategori' => 'Berita',
        ]);
    }

    public function test_admin_can_update_article_and_pin_status(): void
    {
        $admin = $this->createAdminUser();

        $article = NewsArticle::create([
            'judul' => 'Judul Awal',
            'slug' => 'judul-awal',
            'kategori' => 'Pengumuman',
            'ringkasan' => 'Ringkasan awal',
            'konten' => 'Konten awal',
            'status_publikasi' => 'draft',
            'is_pinned' => false,
        ]);

        $updateData = [
            'judul' => 'Judul Telah Direvisi',
            'slug' => 'judul-telah-direvisi',
            'kategori' => 'Pengumuman',
            'ringkasan' => 'Ringkasan telah direvisi',
            'konten' => 'Konten telah direvisi lengkap',
            'status_publikasi' => 'terbit',
            'tanggal_terbit' => '2026-10-15T15:00',
            'is_pinned' => 1,
        ];

        $response = $this->actingAs($admin)
            ->put(route('admin.articles.update', ['article' => $article->id]), $updateData);

        $response->assertRedirect(route('admin.articles.index'));

        $this->assertDatabaseHas('news_articles', [
            'id' => $article->id,
            'judul' => 'Judul Telah Direvisi',
            'is_pinned' => 1,
            'status_publikasi' => 'terbit',
        ]);
    }

    public function test_admin_can_delete_article(): void
    {
        $admin = $this->createAdminUser();

        $article = NewsArticle::create([
            'judul' => 'Artikel Akan Dihapus',
            'slug' => 'artikel-akan-dihapus',
            'kategori' => 'Berita',
            'ringkasan' => 'Ringkasan artikel',
            'konten' => 'Konten artikel',
            'status_publikasi' => 'draft',
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.articles.destroy', ['article' => $article->id]));

        $response->assertRedirect(route('admin.articles.index'));

        $this->assertSoftDeleted('news_articles', [
            'id' => $article->id,
        ]);
    }
}
