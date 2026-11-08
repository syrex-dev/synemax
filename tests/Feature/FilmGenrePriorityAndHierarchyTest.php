<?php

namespace Tests\Feature;

use App\Models\AgeRating;
use App\Models\Film;
use App\Models\Genre;
use App\Models\User;
use App\Queries\FilmCatalogQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FilmGenrePriorityAndHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        AgeRating::firstOrCreate(['code' => '13+'], ['name' => 'Remaja 13+', 'sort_order' => 2]);
        AgeRating::firstOrCreate(['code' => '17+'], ['name' => 'Dewasa 17+', 'sort_order' => 3]);
        AgeRating::firstOrCreate(['code' => 'SU'], ['name' => 'Semua Umur', 'sort_order' => 1]);
    }

    public function test_film_stores_ordered_genres_in_pivot_and_syncs_text_column(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.films.store'), [
            'title' => 'Film Drama Komedi Utama',
            'duration_minutes' => 105,
            'release_year' => 2024,
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis film drama komedi yang hangat dan menghibur.',
            'genres' => ['Drama', 'Komedi', 'Romantis'],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.films.index'));

        $film = Film::where('title', 'Film Drama Komedi Utama')->first();
        $this->assertNotNull($film);

        // Denormalized text matches exact order
        $this->assertSame('Drama, Komedi, Romantis', $film->genre);

        // Pivot table verification
        $pivots = DB::table('film_genre')
            ->where('film_id', $film->id)
            ->orderBy('position')
            ->get();

        $this->assertCount(3, $pivots);
        $this->assertSame(0, $pivots[0]->position);
        $this->assertSame(1, $pivots[1]->position);
        $this->assertSame(2, $pivots[2]->position);

        $genre0 = Genre::find($pivots[0]->genre_id);
        $genre1 = Genre::find($pivots[1]->genre_id);
        $genre2 = Genre::find($pivots[2]->genre_id);

        $this->assertSame('Drama', $genre0->name);
        $this->assertSame('Komedi', $genre1->name);
        $this->assertSame('Romantis', $genre2->name);

        $this->assertSame('Drama', $film->primaryGenre()?->name);
    }

    public function test_reordering_genres_updates_pivot_positions(): void
    {
        $film = Film::create([
            'title' => 'Film Reorder Test',
            'slug' => 'film-reorder-test',
            'duration_minutes' => 90,
            'release_year' => 2024,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis film reorder.',
            'is_active' => true,
        ]);

        $film->syncOrderedGenres(['Drama', 'Aksi']);
        $this->assertSame('Drama', $film->primaryGenre()?->name);

        // Update with swapped order
        $response = $this->actingAs($this->admin)->put(route('admin.films.update', $film), [
            'title' => 'Film Reorder Test',
            'duration_minutes' => 90,
            'release_year' => 2024,
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis film reorder.',
            'genres' => ['Aksi', 'Drama'],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.films.index'));

        $film->refresh();
        $this->assertSame('Aksi, Drama', $film->genre);
        $this->assertSame('Aksi', $film->primaryGenre()?->name);

        $firstPivot = DB::table('film_genre')
            ->where('film_id', $film->id)
            ->where('position', 0)
            ->first();

        $aksiGenre = Genre::where('name', 'Aksi')->first();
        $this->assertSame($aksiGenre->id, $firstPivot->genre_id);
    }

    public function test_similar_films_prioritizes_primary_genre_and_never_recommends_unrelated_genres(): void
    {
        $drama = Genre::findOrCreateByName('Drama');
        $komedi = Genre::findOrCreateByName('Komedi');
        $horor = Genre::findOrCreateByName('Horor');

        // Film A: Target (Drama = Utama, Komedi = Terkait)
        $filmA = Film::create([
            'title' => 'Film A Target',
            'slug' => 'film-a-target',
            'duration_minutes' => 100,
            'release_year' => 2024,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis A',
            'is_active' => true,
        ]);
        $filmA->syncOrderedGenres(['Drama', 'Komedi']);

        // Film B: Shares PRIMARY genre Drama as primary (position = 0)
        $filmB = Film::create([
            'title' => 'Film B Drama Utama',
            'slug' => 'film-b-drama-utama',
            'duration_minutes' => 105,
            'release_year' => 2024,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis B',
            'is_active' => true,
        ]);
        $filmB->syncOrderedGenres(['Drama']);

        // Film C: Shares SECONDARY genre Komedi as primary
        $filmC = Film::create([
            'title' => 'Film C Komedi Murni',
            'slug' => 'film-c-komedi-murni',
            'duration_minutes' => 95,
            'release_year' => 2023,
            'genre' => 'Komedi',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis C',
            'is_active' => true,
        ]);
        $filmC->syncOrderedGenres(['Komedi']);

        // Film D: Unrelated Pure Horor (NO Drama, NO Komedi)
        $filmD = Film::create([
            'title' => 'Film D Horor Murni',
            'slug' => 'film-d-horor-murni',
            'duration_minutes' => 100,
            'release_year' => 2024,
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis D',
            'is_active' => true,
        ]);
        $filmD->syncOrderedGenres(['Horor']);

        $query = new FilmCatalogQuery;
        $recommendations = $query->similarFilms($filmA, 4);

        // Must recommend Film B (primary match) and Film C (secondary match)
        $recIds = $recommendations->pluck('id')->all();

        $this->assertContains($filmB->id, $recIds);
        $this->assertContains($filmC->id, $recIds);

        // Film B (Primary Drama match) must appear BEFORE Film C (Secondary Komedi match)
        $this->assertSame($filmB->id, $recommendations->first()->id);

        // Pure Horor film D must NEVER be recommended for a Drama film
        $this->assertNotContains($filmD->id, $recIds);
    }

    public function test_genre_hierarchy_max_depth_and_cycle_prevention(): void
    {
        $horor = Genre::findOrCreateByName('Horor');

        // Create child genre "Horor Religi" under "Horor"
        $response = $this->actingAs($this->admin)->post(route('admin.genres.store'), [
            'name' => 'Horor Religi',
            'parent_id' => $horor->id,
            'is_active' => '1',
        ]);
        $response->assertRedirect(route('admin.genres.index'));

        $hororReligi = Genre::where('name', 'Horor Religi')->first();
        $this->assertNotNull($hororReligi);
        $this->assertSame($horor->id, $hororReligi->parent_id);
        $this->assertTrue($horor->isParent());
        $this->assertTrue($hororReligi->isChild());

        // Attempt depth 3: child under "Horor Religi" must fail
        $failResponse = $this->actingAs($this->admin)->post(route('admin.genres.store'), [
            'name' => 'Horor Religi Ekstrem',
            'parent_id' => $hororReligi->id,
            'is_active' => '1',
        ]);
        $failResponse->assertSessionHas('error');

        // Attempt cycle: try to make "Horor" a child of "Horor Religi"
        $cycleResponse = $this->actingAs($this->admin)->put(route('admin.genres.update', $horor), [
            'name' => 'Horor',
            'parent_id' => $hororReligi->id,
            'is_active' => '1',
        ]);
        $cycleResponse->assertSessionHas('error');
    }

    public function test_public_filter_parent_genre_includes_child_genres(): void
    {
        $horor = Genre::findOrCreateByName('Horor');
        $hororReligi = Genre::create([
            'name' => 'Horor Religi',
            'normalized_name' => 'horor religi',
            'parent_id' => $horor->id,
            'sort_order' => 50,
            'is_active' => true,
        ]);
        $komedi = Genre::findOrCreateByName('Komedi');

        $film1 = Film::create([
            'title' => 'Film Horor Klasik',
            'slug' => 'film-horor-klasik',
            'duration_minutes' => 90,
            'release_year' => 2024,
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis 1',
            'is_active' => true,
        ]);
        $film1->syncOrderedGenres(['Horor']);

        $film2 = Film::create([
            'title' => 'Film Horor Religi Santri',
            'slug' => 'film-horor-religi-santri',
            'duration_minutes' => 95,
            'release_year' => 2024,
            'genre' => 'Horor Religi',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis 2',
            'is_active' => true,
        ]);
        $film2->syncOrderedGenres(['Horor Religi']);

        $film3 = Film::create([
            'title' => 'Film Komedi Murni',
            'slug' => 'film-komedi-murni',
            'duration_minutes' => 90,
            'release_year' => 2024,
            'genre' => 'Komedi',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis 3',
            'is_active' => true,
        ]);
        $film3->syncOrderedGenres(['Komedi']);

        $query = new FilmCatalogQuery;
        $results = $query->paginateCatalog(['genre' => 'Horor'], 20);

        $resultIds = collect($results->items())->pluck('id')->all();

        // Both Parent (Horor) and Child (Horor Religi) are included
        $this->assertContains($film1->id, $resultIds);
        $this->assertContains($film2->id, $resultIds);

        // Komedi is excluded
        $this->assertNotContains($film3->id, $resultIds);

        // Filtering specifically by child returns only child
        $childResults = $query->paginateCatalog(['genre' => 'Horor Religi'], 20);
        $childIds = collect($childResults->items())->pluck('id')->all();

        $this->assertContains($film2->id, $childIds);
        $this->assertNotContains($film1->id, $childIds);
    }
}
