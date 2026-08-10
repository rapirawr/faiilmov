<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Film;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_search_returns_matches()
    {
        $film = Film::factory()->create([
            'title' => 'Inception',
            'slug' => 'inception-123456',
        ]);

        $response = $this->get('/browse?q=Inception');

        $response->assertStatus(200);
        $response->assertSee('Inception');
    }

    public function test_fuzzy_search_handles_typos()
    {
        $film = Film::factory()->create([
            'title' => 'Avatar: The Way of Water',
            'slug' => 'avatar-way-of-water',
        ]);

        // Search with typo "avatr"
        $response = $this->get('/browse?q=avatr');

        $response->assertStatus(200);
        $response->assertSee('Avatar: The Way of Water');
    }

    public function test_multi_token_search()
    {
        $film = Film::factory()->create([
            'title' => 'Spider-Man: No Way Home',
            'slug' => 'spiderman-no-way-home',
        ]);

        // Search multi-word query
        $response = $this->get('/browse?q=spider+man+way');

        $response->assertStatus(200);
        $response->assertSee('Spider-Man: No Way Home');
    }

    public function test_autocomplete_endpoint_with_typo()
    {
        $film = Film::factory()->create([
            'title' => 'Interstellar',
            'slug' => 'interstellar-123',
        ]);

        $response = $this->get('/search/autocomplete?q=interstelr');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'title' => 'Interstellar',
        ]);
    }

    public function test_mobile_api_search_endpoint()
    {
        $film = Film::factory()->create([
            'title' => 'The Dark Knight',
            'slug' => 'the-dark-knight',
        ]);

        $response = $this->getJson('/api/v1/search?q=dark+kniht');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonFragment([
            'title' => 'The Dark Knight',
        ]);
    }
}
