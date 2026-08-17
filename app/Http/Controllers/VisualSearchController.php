<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\FilmEmbedding;
use App\Models\Collection;
use App\Services\GeminiVisionService;
use App\Services\GeminiEmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class VisualSearchController extends Controller
{
    public function __construct(
        private GeminiVisionService $vision,
        private GeminiEmbeddingService $gemini
    ) {}

    /**
     * Search films by uploading an image or providing an image URL
     * POST /api/v1/search/by-image
     */
    public function searchByImage(Request $request)
    {
        $request->validate([
            'image' => 'nullable|file|image|max:8192',
            'image_url' => 'nullable|url',
        ]);

        if (!$request->hasFile('image') && empty($request->input('image_url'))) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan unggah file gambar poster atau masukkan tautan URL gambar.',
            ], 422);
        }

        try {
            $imageData = null;
            $mimeType = 'image/jpeg';

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $imageData = file_get_contents($file->getRealPath());
                $mimeType = $file->getMimeType() ?: 'image/jpeg';
            } else {
                $url = $request->input('image_url');
                $res = \Illuminate\Support\Facades\Http::timeout(10)->get($url);
                if ($res->successful()) {
                    $imageData = $res->body();
                    $mimeType = $res->header('Content-Type') ?: 'image/jpeg';
                }
            }

            if (!$imageData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membaca data gambar. Pastikan URL atau file valid.',
                ], 400);
            }

            // 1. Analyze with Gemini Multimodal Vision
            $analysis = $this->vision->identifyFilmFromImage(base64_encode($imageData), $mimeType);

            if (!$analysis) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI Vision belum dapat menganalisis gambar ini. Pastikan gambar jelas.',
                ], 500);
            }

            $detectedTitle = $analysis['detected_title'] ?? null;
            $franchise = $analysis['franchise'] ?? null;
            $visualStyle = $analysis['visual_style'] ?? 'Live-Action Cinematic';
            $colorMood = $analysis['color_mood'] ?? null;
            $keywords = $analysis['search_keywords'] ?? [];
            $visualDesc = $analysis['visual_description'] ?? '';

            // 2. Compute embedding query from visual description + keywords
            $searchQueryText = trim(implode(' | ', array_filter([
                $detectedTitle ? "Title: {$detectedTitle}" : null,
                $franchise ? "Franchise: {$franchise}" : null,
                $visualStyle ? "Style: {$visualStyle}" : null,
                $colorMood ? "Color Mood: {$colorMood}" : null,
                !empty($keywords) ? "Elements: " . implode(', ', (array)$keywords) : null,
                $visualDesc ? "Visual: {$visualDesc}" : null,
            ])));

            $queryVector = $this->gemini->embedText($searchQueryText);

            $scoredFilms = [];
            if (!empty($queryVector)) {
                $allEmbeddings = FilmEmbedding::all();
                foreach ($allEmbeddings as $emb) {
                    if (is_array($emb->embedding) && count($emb->embedding) === count($queryVector)) {
                        $sim = $this->gemini->cosineSimilarity($queryVector, $emb->embedding);
                        if ($sim > 0.35) {
                            $scoredFilms[$emb->film_id] = $sim;
                        }
                    }
                }
            }

            // Fallback / direct title match if title was detected
            if ($detectedTitle && mb_strlen($detectedTitle) > 2) {
                $titleMatches = Film::where('title', 'LIKE', "%{$detectedTitle}%")->pluck('id')->toArray();
                foreach ($titleMatches as $tmId) {
                    $scoredFilms[$tmId] = max($scoredFilms[$tmId] ?? 0.0, 0.95);
                }
            }

            // Keyword, Franchise, and Visual Style Fallback if scoredFilms is low
            if (count($scoredFilms) < 4) {
                $fallbackQuery = Film::query();
                if ($franchise) {
                    $fallbackQuery->whereHas('tags', fn($q) => $q->where('tag_value', 'LIKE', "%{$franchise}%"));
                }
                if (!empty($keywords) && is_array($keywords)) {
                    foreach (array_slice($keywords, 0, 3) as $kw) {
                        $cleanKw = trim((string)$kw);
                        if (mb_strlen($cleanKw) >= 3) {
                            $fallbackQuery->orWhere('title', 'LIKE', "%{$cleanKw}%");
                        }
                    }
                }
                if ($visualStyle) {
                    $fallbackQuery->orWhere('visual_style', $visualStyle);
                }

                $fallbackFilms = $fallbackQuery->orderByDesc('rating')->limit(12)->get();
                foreach ($fallbackFilms as $idx => $ff) {
                    if (!isset($scoredFilms[$ff->id])) {
                        $scoredFilms[$ff->id] = max(0.60, 0.85 - ($idx * 0.02));
                    }
                }
            }

            // If still empty, grab highest rated films of matching subject type
            if (empty($scoredFilms)) {
                $genericFilms = Film::orderByDesc('rating')->limit(8)->get();
                foreach ($genericFilms as $idx => $gf) {
                    $scoredFilms[$gf->id] = 0.70 - ($idx * 0.02);
                }
            }

            // Fetch top 12 matches
            arsort($scoredFilms);
            $topFilmIds = array_slice(array_keys($scoredFilms), 0, 12);

            $films = Film::whereIn('id', $topFilmIds)
                ->with(['genres', 'tags'])
                ->get()
                ->keyBy('id');

            $results = [];
            foreach ($topFilmIds as $id) {
                if (isset($films[$id])) {
                    $f = $films[$id];
                    $simScore = round(($scoredFilms[$id] ?? 0.5) * 100, 1);
                    $results[] = [
                        'id' => $f->id,
                        'title' => $f->title,
                        'slug' => $f->slug,
                        'poster_url' => $f->poster_url,
                        'release_year' => $f->release_year,
                        'rating' => $f->rating,
                        'subject_type' => $f->subject_type,
                        'visual_style' => $f->visual_style,
                        'similarity_score' => $simScore,
                        'genres' => $f->genres->pluck('name')->toArray(),
                        'url' => route('film.show', $f->slug),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'analysis' => [
                    'detected_title' => $detectedTitle,
                    'franchise' => $franchise,
                    'visual_style' => $visualStyle,
                    'color_mood' => $colorMood,
                    'visual_description' => $visualDesc,
                    'search_keywords' => $keywords,
                ],
                'results' => $results,
                'total' => count($results),
            ]);
        } catch (Exception $e) {
            Log::error('Visual search exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses pencarian visual: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create smart collection from uploaded image vibe
     * POST /collections/from-image
     */
    public function createCollectionFromImage(Request $request)
    {
        $searchResponse = $this->searchByImage($request);
        $data = $searchResponse->getData(true);

        if (!$data['success'] || empty($data['results'])) {
            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'Gagal membuat koleksi dari gambar ini.',
            ], 400);
        }

        $analysis = $data['analysis'];
        $style = $analysis['visual_style'] ?? 'Cinematic';
        $mood = $analysis['color_mood'] ?? 'Aesthetic';
        $name = "Koleksi Visual: {$style} ({$mood})";
        $slug = Str::slug($name) . '-' . Str::random(5);

        $collection = Collection::create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'prompt',
            'description' => "Koleksi film yang dikurasi oleh AI Vision berdasarkan estetika poster visual {$style} dengan nuansa {$mood}. " . ($analysis['visual_description'] ?? ''),
            'cover_image' => $data['results'][0]['poster_url'] ?? null,
            'status' => 'published',
            'created_by' => Auth::id(),
        ]);

        $filmIds = array_column($data['results'], 'id');
        $collection->films()->attach($filmIds, ['added_by' => 'system']);

        return response()->json([
            'success' => true,
            'collection' => $collection,
            'redirect_url' => route('collections.show', $collection->slug),
        ]);
    }
}
