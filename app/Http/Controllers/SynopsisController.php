<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Services\SynopsisAiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SynopsisController extends Controller
{
    public function __construct(
        protected SynopsisAiService $synopsisService
    ) {}

    /**
     * Translate synopsis for public detail view or general use
     */
    public function translate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'film_id' => 'nullable|integer',
            'text' => 'nullable|string|max:5000',
            'target_lang' => 'nullable|string|in:id,en',
        ]);

        $text = $validated['text'] ?? '';
        $targetLang = $validated['target_lang'] ?? 'id';

        if (empty($text) && !empty($validated['film_id'])) {
            $film = Film::find($validated['film_id']);
            if ($film) {
                $text = $film->synopsis;
            }
        }

        if (empty($text)) {
            return response()->json([
                'success' => false,
                'message' => 'Teks sinopsis tidak ditemukan.',
            ], 422);
        }

        $result = $this->synopsisService->translate($text, $targetLang);

        return response()->json([
            'success' => true,
            'original_text' => $text,
            'translated_text' => $result['translated_text'],
            'target_lang' => $result['target_lang'],
            'provider' => $result['provider'] ?? 'ai',
        ]);
    }

    /**
     * Generate structured AI summary and story highlights for public detail view
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'film_id' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'synopsis' => 'nullable|string|max:5000',
            'genres' => 'nullable|array',
        ]);

        $title = $validated['title'] ?? '';
        $synopsis = $validated['synopsis'] ?? '';
        $genres = $validated['genres'] ?? [];

        if (!empty($validated['film_id'])) {
            $film = Film::with('genres')->find($validated['film_id']);
            if ($film) {
                $title = $film->title;
                $synopsis = $film->synopsis ?: $synopsis;
                $genres = $film->genres->pluck('name')->toArray() ?: $genres;
            }
        }

        if (empty($title) && empty($synopsis)) {
            return response()->json([
                'success' => false,
                'message' => 'Judul atau sinopsis film diperlukan.',
            ], 422);
        }

        $result = $this->synopsisService->summarize($title, $synopsis, $genres);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Admin tools: Generate, translate, or refine synopsis copywriting
     */
    public function adminTools(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:translate,generate,shorten',
            'title' => 'nullable|string|max:255',
            'synopsis' => 'nullable|string|max:5000',
            'genres' => 'nullable|array',
            'tone' => 'nullable|string|in:cinematic,short,catchy',
        ]);

        $action = $validated['action'];
        $title = $validated['title'] ?? 'Film';
        $synopsis = $validated['synopsis'] ?? '';
        $genres = $validated['genres'] ?? [];
        $tone = $validated['tone'] ?? 'cinematic';

        if ($action === 'translate') {
            if (empty($synopsis)) {
                return response()->json(['success' => false, 'message' => 'Kolom sinopsis masih kosong.'], 422);
            }
            $res = $this->synopsisService->translate($synopsis, 'id');
            return response()->json([
                'success' => true,
                'synopsis' => $res['translated_text'],
            ]);
        }

        if ($action === 'shorten') {
            $tone = 'short';
        }

        $res = $this->synopsisService->generateSynopsisCopy($title, $synopsis, $genres, $tone);

        return response()->json([
            'success' => true,
            'synopsis' => $res['synopsis'],
        ]);
    }
}
