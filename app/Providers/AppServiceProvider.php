<?php

namespace App\Providers;

use App\Services\NvidiaAiService;
use App\Events\FilmCreated;
use App\Events\FilmUpdated;
use App\Listeners\GenerateFilmEmbeddingOnUpdate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NvidiaAiService::class, function ($app) {
            return new NvidiaAiService();
        });
    }

    public function boot(): void
    {
        if ($this->app->bound('events')) {
            $this->app['events']->listen(FilmCreated::class, GenerateFilmEmbeddingOnUpdate::class);
            $this->app['events']->listen(FilmUpdated::class, GenerateFilmEmbeddingOnUpdate::class);
        }
    }
}
