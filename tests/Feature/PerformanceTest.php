<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Film;
use App\Models\Genre;
use App\Models\WatchHistory;
use App\Services\MovieBoxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock MovieBoxService to prevent external API calls
        $mockMovieBox = $this->createMock(MovieBoxService::class);
        $mockMovieBox->method('search')->willReturn(['subjects' => []]);
        $mockMovieBox->method('getHomepage')->willReturn(['items' => []]);
        $mockMovieBox->method('init')->willReturn(true);
        $this->app->instance(MovieBoxService::class, $mockMovieBox);
    }

    private function countQueries(callable $action): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        
        $action();
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        return count($queries);
    }

    public function test_browse_page_query_count()
    {
        Genre::factory()->count(10)->create();
        Film::factory()->count(30)->create();

        $queryCount = $this->countQueries(function() {
            $response = $this->get('/browse');
            $response->assertSuccessful();
        });

        $this->assertLessThan(10, $queryCount, "Browse page queries: {$queryCount}");
    }

    public function test_search_autocomplete_query_count()
    {
        Film::factory()->count(20)->create();

        $queryCount = $this->countQueries(function() {
            $response = $this->get('/search/autocomplete?q=test&popular=1');
            $response->assertSuccessful();
        });

        $this->assertLessThan(5, $queryCount, "Autocomplete queries: {$queryCount}");
    }

    public function test_watchlist_toggle_query_count()
    {
        $user = User::factory()->create();
        $film = Film::factory()->create();

        $queryCount = $this->countQueries(function() use ($user, $film) {
            $response = $this->actingAs($user)->postJson(route('watchlist.toggle', $film), [
                'status' => 'plan_to_watch',
            ]);
            $response->assertSuccessful();
        });

        $this->assertLessThan(10, $queryCount, "Watchlist toggle queries: {$queryCount}");
    }

    public function test_profile_page_query_count()
    {
        $user = User::factory()->create();
        $films = Film::factory()->count(10)->create();

        foreach ($films->take(5) as $film) {
            $user->watchHistories()->create([
                'film_id' => $film->id,
                'progress_seconds' => 120,
                'last_position' => 120,
                'completed' => false,
            ]);
            $user->watchlists()->create([
                'film_id' => $film->id,
                'status' => 'plan_to_watch',
            ]);
        }

        $queryCount = $this->countQueries(function() use ($user) {
            $response = $this->actingAs($user)->get('/profile');
            $response->assertSuccessful();
        });

        $this->assertLessThan(10, $queryCount, "Profile page queries: {$queryCount}");
    }

    public function test_movie_detail_query_count()
    {
        $film = Film::factory()->create();
        $genres = Genre::factory()->count(5)->create();
        $film->genres()->attach($genres->pluck('id')->toArray());

        $queryCount = $this->countQueries(function() use ($film) {
            $response = $this->get(route('film.show', $film->slug));
            $response->assertSuccessful();
        });

        $this->assertLessThan(10, $queryCount, "Film detail queries: {$queryCount}");
    }

    public function test_homepage_with_continue_watching_query_count()
    {
        $user = User::factory()->create();
        $films = Film::factory()->count(15)->create();

        foreach ($films->take(10) as $film) {
            $user->watchHistories()->create([
                'film_id' => $film->id,
                'progress_seconds' => 120,
                'last_position' => 120,
                'completed' => false,
            ]);
        }

        $queryCount = $this->countQueries(function() use ($user) {
            $response = $this->actingAs($user)->get('/');
            $response->assertSuccessful();
        });

        $this->assertLessThan(15, $queryCount, "Homepage with continue watching queries: {$queryCount}");
    }
}
