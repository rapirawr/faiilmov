<?php

namespace Tests\Feature;

use App\Jobs\ResolveFilmRequestJob;
use App\Models\Film;
use App\Models\FilmRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\FilmRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FilmRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_film_request()
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/film-requests', [
            'title' => 'Avatar: Fire and Ash',
            'type' => 'movie',
            'year' => 2025,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'Avatar: Fire and Ash')
            ->assertJsonPath('data.request_count', 1);

        $this->assertDatabaseHas('film_requests', [
            'title' => 'Avatar: Fire and Ash',
            'type' => 'movie',
            'year' => 2025,
            'request_count' => 1,
            'status' => 'pending',
        ]);

        Queue::assertPushed(ResolveFilmRequestJob::class);
    }

    public function test_fuzzy_duplicate_request_merges_and_increments_count()
    {
        Queue::fake();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 submits
        $this->actingAs($user1)->postJson('/api/v1/film-requests', [
            'title' => 'Spider-Man No Way Home',
            'type' => 'movie',
        ]);

        // User 2 submits similar title
        $response = $this->actingAs($user2)->postJson('/api/v1/film-requests', [
            'title' => 'Spider-Man: No Way Home',
            'type' => 'movie',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.request_count', 2);

        $this->assertDatabaseCount('film_requests', 1);
        $this->assertDatabaseHas('film_requests', [
            'request_count' => 2,
        ]);
    }

    public function test_user_can_fetch_own_film_requests()
    {
        $user = User::factory()->create();
        $service = app(FilmRequestService::class);
        $service->submit('Inception 2', 'movie', 2026, $user);

        $response = $this->actingAs($user)->getJson('/api/v1/film-requests/mine');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_resolve_and_notify_requesters()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $request = FilmRequest::create([
            'title' => 'Test Movie',
            'type' => 'movie',
            'status' => 'pending',
            'request_count' => 1,
        ]);
        $request->users()->attach($user->id);

        $film = Film::create([
            'title' => 'Test Movie',
            'slug' => 'test-movie-' . rand(100, 999),
            'subject_type' => 'movie',
            'release_year' => 2025,
            'duration_minutes' => 120,
            'rating' => 8.0,
        ]);

        $request->update([
            'status' => 'added',
            'matched_film_id' => $film->id,
        ]);

        $service = app(FilmRequestService::class);
        $service->notifyRequesters($request);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'film_request_added',
        ]);
    }

    public function test_admin_can_reject_film_request_with_reason()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $request = FilmRequest::create([
            'title' => 'Unreleased Movie',
            'type' => 'movie',
            'status' => 'pending',
            'request_count' => 1,
        ]);
        $request->users()->attach($user->id);

        $response = $this->actingAs($admin)->post("/admin/film-requests/{$request->id}/reject", [
            'rejection_reason' => 'Film tidak tersedia di server rilis.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('film_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'rejection_reason' => 'Film tidak tersedia di server rilis.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'film_request_rejected',
        ]);
    }
}
