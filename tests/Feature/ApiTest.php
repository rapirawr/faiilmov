<?php

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\AdminActivityLog;
use App\Models\AppLaunchNotification;
use App\Models\Changelog;
use App\Models\Episode;
use App\Models\Film;
use App\Models\Genre;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\SearchLog;
use App\Models\Season;
use App\Models\Setting;
use App\Models\User;
use App\Models\WatchHistory;
use App\Models\WatchParty;
use App\Models\Watchlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_and_user_endpoints()
    {
        $user = User::factory()->create([
            'email' => 'apitest@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'apitest@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['token', 'user']);

        $token = $response->json('token');

        $userRes = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/v1/user');
        $userRes->assertStatus(200)->assertJson(['success' => true]);

        $usersRes = $this->getJson('/api/v1/users');
        $usersRes->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_movies_seasons_episodes_endpoints()
    {
        $film = Film::create([
            'title' => 'Test Series',
            'slug' => 'test-series',
            'subject_type' => 'series',
            'synopsis' => 'A great test series',
        ]);

        $season = Season::create([
            'film_id' => $film->id,
            'season_number' => 1,
            'title' => 'Season 1',
        ]);

        $episode = Episode::create([
            'season_id' => $season->id,
            'episode_number' => 1,
            'title' => 'Pilot Episode',
            'duration_minutes' => 45,
        ]);

        $this->getJson('/api/v1/movies')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/movies/' . $film->id)->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/movies/' . $film->id . '/seasons')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/seasons/' . $season->id)->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/seasons/' . $season->id . '/episodes')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/episodes/' . $episode->id)->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_genres_and_actors_endpoints()
    {
        $genre = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $actor = Actor::create(['name' => 'John Doe', 'slug' => 'john-doe']);

        $this->getJson('/api/v1/genres')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/genres/' . $genre->id)->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/actors')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/actors/' . $actor->id)->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_profiles_endpoints()
    {
        $user = User::factory()->create();

        $createRes = $this->actingAs($user)->postJson('/api/v1/profiles', [
            'name' => 'Kids Profile',
            'is_child' => true,
        ]);
        $createRes->assertStatus(201)->assertJson(['success' => true]);

        $profileId = $createRes->json('data.id');

        $this->actingAs($user)->getJson('/api/v1/profiles')->assertStatus(200);
        $this->actingAs($user)->getJson('/api/v1/profiles/' . $profileId)->assertStatus(200);

        $this->actingAs($user)->putJson('/api/v1/profiles/' . $profileId, [
            'name' => 'Updated Kids Profile',
        ])->assertStatus(200);

        $this->actingAs($user)->deleteJson('/api/v1/profiles/' . $profileId)->assertStatus(200);
    }

    public function test_settings_changelogs_and_notifications_endpoints()
    {
        Setting::set('site_name', 'Faiilmov');
        Changelog::create([
            'version' => '1.0.0',
            'title' => 'Initial Release',
            'type' => 'major',
            'summary' => 'Initial release',
            'is_published' => true,
        ]);

        $this->getJson('/api/v1/settings')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/changelogs')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/changelogs/latest')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/v1/search/popular')->assertStatus(200)->assertJson(['success' => true]);

        $user = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'message' => 'Welcome to Faiilmov!',
        ]);

        $this->actingAs($user)->getJson('/api/v1/notifications')->assertStatus(200);
        $this->actingAs($user)->postJson('/api/v1/notifications/' . $notification->id . '/read')->assertStatus(200);
    }
}
