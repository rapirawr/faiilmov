<?php

namespace App\Services;

use App\Models\Film;
use App\Models\FilmRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FilmRequestService
{
    public function __construct(
        private MovieBoxService $movieBox,
        private AnichinService $anichin
    ) {}

    /**
     * Submit a film request, merging fuzzy duplicates if found.
     */
    public function submit(string $title, string $type, ?int $year, User $user): FilmRequest
    {
        $cleanTitle = trim($title);
        $normalizedSlug = Str::slug($cleanTitle);

        // Find existing pending/searching request with fuzzy match
        $existingRequests = FilmRequest::pending()
            ->where('type', $type)
            ->get();

        $matchedRequest = null;
        foreach ($existingRequests as $req) {
            $reqSlug = Str::slug($req->title);

            // Exact slug match or high similarity
            if ($reqSlug === $normalizedSlug) {
                $matchedRequest = $req;
                break;
            }

            // Levenshtein / similar_text check
            similar_text(strtolower($req->title), strtolower($cleanTitle), $percent);
            if ($percent >= 85) {
                $matchedRequest = $req;
                break;
            }

            if (levenshtein($reqSlug, $normalizedSlug) <= 3 && strlen($normalizedSlug) > 5) {
                $matchedRequest = $req;
                break;
            }
        }

        if ($matchedRequest) {
            // Attach user if not already attached
            if (!$matchedRequest->users()->where('users.id', $user->id)->exists()) {
                $matchedRequest->users()->attach($user->id);
                $matchedRequest->increment('request_count');
            }
            return $matchedRequest->fresh(['users', 'matchedFilm']);
        }

        // Create new request
        $newRequest = FilmRequest::create([
            'title' => $cleanTitle,
            'type' => $type,
            'year' => $year,
            'status' => 'pending',
            'request_count' => 1,
        ]);

        $newRequest->users()->attach($user->id);

        return $newRequest->fresh(['users', 'matchedFilm']);
    }

    /**
     * Attempt automatic resolution via MovieBox API or Anichin API.
     */
    public function tryAutoResolve(FilmRequest $request): bool
    {
        if (in_array($request->status, ['added', 'rejected'])) {
            return false;
        }

        $request->update(['status' => 'searching']);

        try {
            if (in_array($request->type, ['movie', 'tv', 'series'])) {
                $searchRes = $this->movieBox->search($request->title);
                $items = is_array($searchRes) ? ($searchRes['data'] ?? $searchRes['items'] ?? $searchRes) : [];

                if (empty($items) && is_object($searchRes) && isset($searchRes->data)) {
                    $items = (array)$searchRes->data;
                }

                if (!empty($items)) {
                    $bestMatch = null;
                    $reqSlug = Str::slug($request->title);

                    foreach ($items as $item) {
                        $itemArray = is_array($item) ? $item : (array)$item;
                        $itemTitle = $itemArray['title'] ?? $itemArray['name'] ?? '';
                        $itemSlug = Str::slug($itemTitle);

                        if ($itemSlug === $reqSlug || levenshtein($itemSlug, $reqSlug) <= 3) {
                            $bestMatch = $itemArray;
                            break;
                        }
                    }

                    if (!$bestMatch && !empty($items[0])) {
                        $bestMatch = is_array($items[0]) ? $items[0] : (array)$items[0];
                    }

                    if ($bestMatch && isset($bestMatch['subjectId'])) {
                        $subjectId = (string)$bestMatch['subjectId'];
                        $details = $this->movieBox->getDetails($subjectId);

                        if (!empty($details)) {
                            $film = Film::fromApiData($details);

                            if ($film) {
                                $request->update([
                                    'matched_film_id' => $film->id,
                                    'status' => 'added',
                                ]);
                                return true;
                            }
                        }
                    }
                }
            } elseif ($request->type === 'dracin') {
                $results = $this->anichin->search($request->title, 'dramabox');
                if (!empty($results) && isset($results[0])) {
                    $item = $results[0];
                    $dramaId = $item['id'] ?? $item['dramaId'] ?? null;
                    if ($dramaId) {
                        $details = $this->anichin->getDetail('dramabox', $dramaId);
                        if (!empty($details)) {
                            $subjectId = 'anichin:dramabox:' . $dramaId;
                            $film = Film::fromApiData([
                                'subjectId' => $subjectId,
                                'title' => $details['title'] ?? $item['title'] ?? $request->title,
                                'description' => $details['synopsis'] ?? '',
                                'cover' => $details['cover'] ?? $item['cover'] ?? '',
                                'subject_type' => 'dracin',
                                'stype' => 2,
                            ]);

                            if ($film) {
                                $request->update([
                                    'matched_film_id' => $film->id,
                                    'status' => 'added',
                                ]);
                                return true;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Auto-resolve failed for FilmRequest #{$request->id}: " . $e->getMessage());
        }

        // Revert status to pending if auto-resolve fails
        $request->update(['status' => 'pending']);
        return false;
    }

    /**
     * Notify all users attached to a film request.
     */
    public function notifyRequesters(FilmRequest $request): void
    {
        $request->loadMissing(['users', 'matchedFilm']);

        foreach ($request->users as $user) {
            if ($request->status === 'added') {
                $url = $request->matchedFilm 
                    ? route('film.show', $request->matchedFilm->slug, false) 
                    : '/browse';

                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'film_request_added',
                    'message' => "Request film \"{$request->title}\" yang kamu minta sudah tersedia dan siap ditonton!",
                    'url' => $url,
                    'is_read' => false,
                ]);
            } elseif ($request->status === 'rejected') {
                $reason = $request->rejection_reason ? ": {$request->rejection_reason}" : ".";
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'film_request_rejected',
                    'message' => "Request film \"{$request->title}\" belum bisa dipenuhi{$reason}",
                    'url' => '/profile#requests',
                    'is_read' => false,
                ]);
            }
        }
    }
}
