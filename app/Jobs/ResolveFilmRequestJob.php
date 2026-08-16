<?php

namespace App\Jobs;

use App\Models\FilmRequest;
use App\Services\FilmRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResolveFilmRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public FilmRequest $request
    ) {}

    public function handle(FilmRequestService $service): void
    {
        Log::info("Running ResolveFilmRequestJob for request #{$this->request->id}: {$this->request->title}");

        $success = $service->tryAutoResolve($this->request);

        if ($success) {
            $service->notifyRequesters($this->request);
            Log::info("ResolveFilmRequestJob succeeded for request #{$this->request->id}");
        } else {
            Log::info("ResolveFilmRequestJob auto-resolve unfulfilled for request #{$this->request->id}, left as pending.");
        }
    }
}
