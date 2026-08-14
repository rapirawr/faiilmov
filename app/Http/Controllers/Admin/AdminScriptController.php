<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminScript;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Exception;
use Throwable;


class AdminScriptController extends Controller
{
    /**
     * Display script runner dashboard & saved scripts list
     */
    public function index()
    {
        // Seed default starter scripts if table is empty
        if (AdminScript::count() === 0) {
            $this->seedStarterScripts();
        }

        $scripts = AdminScript::orderBy('updated_at', 'desc')->get();
        return view('admin.scripts.index', compact('scripts'));
    }

    /**
     * Save or update custom script
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string',
            'description' => 'nullable|string|max:500',
            'script_id' => 'nullable|exists:admin_scripts,id',
        ]);

        $script = AdminScript::updateOrCreate(
            ['id' => $request->input('script_id')],
            [
                'title' => $request->input('title'),
                'code' => $request->input('code'),
                'description' => $request->input('description'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Script PHP berhasil disimpan.',
            'script' => $script,
        ]);
    }

    /**
     * Safely execute PHP code in isolated buffer and capture output & stats
     */
    public function execute(Request $request): JsonResponse
    {
        $code = $request->input('code', '');
        $scriptId = $request->input('script_id');

        if (empty(trim($code))) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'output' => 'Kode PHP tidak boleh kosong.',
                'duration_ms' => 0,
                'memory_kb' => 0,
            ], 400);
        }

        // Clean opening <?php tags if user included it
        $cleanCode = preg_replace('/^\s*<\?(php)?/i', '', $code);
        $cleanCode = preg_replace('/\?>\s*$/', '', $cleanCode);

        // Strip require/bootstrap lines that break eval() context
        // (eval() runs inside already-bootstrapped Laravel - these are no-ops or errors)
        $cleanCode = preg_replace("/^require(?:_once)?\s+['\"].*?(?:vendor\/autoload|bootstrap\/app).*?['\"];\s*\n/m", '', $cleanCode);
        $cleanCode = preg_replace("/^\\\$app\s*=\s*require_once\s+.*?;\s*\n/m", '', $cleanCode);
        $cleanCode = preg_replace("/^\\\$app->make\(.*?Kernel.*?\)->bootstrap\(\);\s*\n/m", '', $cleanCode);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        ob_start();
        $status = 'success';
        $errorMessage = null;

        try {
            // Execute PHP snippet using eval
            eval($cleanCode);
        } catch (Throwable $e) {
            $status = 'error';
            $errorMessage = "EXCEPTION: " . $e->getMessage() . "\nFile: " . $e->getFile() . " (Line " . $e->getLine() . ")\n\nStack Trace:\n" . $e->getTraceAsString();
        }

        $stdout = ob_get_clean();
        $endTime = microtime(true);

        $durationMs = (int)round(($endTime - $startTime) * 1000);
        $memoryKb = (int)round((memory_get_usage() - $startMemory) / 1024);

        $fullOutput = $stdout;
        if ($errorMessage) {
            $fullOutput .= (!empty($stdout) ? "\n\n" : "") . $errorMessage;
        }

        if (empty(trim($fullOutput))) {
            $fullOutput = "(Script selesai dieksekusi tanpa stdout output)";
        }

        // Update last run stats if updating a saved script
        if ($scriptId) {
            $savedScript = AdminScript::find($scriptId);
            if ($savedScript) {
                $savedScript->update([
                    'last_run_at' => now(),
                    'last_run_output' => mb_substr($fullOutput, 0, 10000),
                    'last_run_status' => $status,
                    'execution_time_ms' => $durationMs,
                ]);
            }
        }

        // Log script execution to AdminActivityLog for security audit
        \App\Models\AdminActivityLog::log(
            'executed_php_script',
            "Mengeksekusi Script PHP (" . ($scriptId ? "Saved Script #{$scriptId}" : "Custom Snippet") . "). Status: {$status}. Durasi: {$durationMs}ms.",
            'AdminScript',
            $scriptId
        );

        return response()->json([
            'success' => $status === 'success',
            'status' => $status,
            'output' => $fullOutput,
            'duration_ms' => $durationMs,
            'memory_kb' => max(0, $memoryKb),
            'executed_at' => now()->format('d M Y H:i:s'),
        ]);
    }

    /**
     * Generate PHP script from natural language prompt using NVIDIA AI API
     */
    public function generateScript(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|min:3|max:2000',
        ]);

        $userPrompt = trim($request->input('prompt'));
        $apiKey = \App\Models\Setting::get('nvidia_api_key', '') ?: env('NVIDIA_API_KEY', config('services.nvidia.api_key', ''));
        $apiUrl = env('NVIDIA_API_URL', config('services.nvidia.base_url', 'https://integrate.api.nvidia.com/v1'));

        $systemPrompt = <<<'SYSTEM'
Kamu adalah AI expert pembuat script PHP murni untuk runtime eval() Laravel admin panel platform streaming FAIILMOV.

ATURAN STRICT & WAJIB:
1. Output HANYA kode PHP murni tanpa pembuka <?php, tanpa tag markdown ```php, tanpa penjelasan apapun.
2. JANGAN gunakan require, require_once, include, atau use statement.
3. Selalu gunakan namespace penuh (FQN), contoh: \App\Models\Film::count()
4. Gunakan echo untuk mencetak output informasi terminal yang rapi dan informatif.
5. Model yang tersedia:
   - \App\Models\Film
   - \App\Models\Actor
   - \App\Models\User
   - \App\Models\Review
   - \App\Models\WatchParty
   - \App\Models\Genre
   - \App\Models\SearchLog
6. Services & Facades:
   - app(\App\Services\MovieBoxService::class)
   - app(\App\Services\FilmSearchService::class)
   - \Illuminate\Support\Facades\DB
   - \Illuminate\Support\Facades\Cache
   - \Illuminate\Support\Facades\Log
SYSTEM;

        if (!empty($apiKey)) {
            $modelsToTry = ['meta/llama-3.1-8b-instruct', 'meta/llama-3.2-3b-instruct', 'meta/llama-3.3-70b-instruct'];

            foreach ($modelsToTry as $model) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                    ])->timeout(15)->post($apiUrl . '/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt],
                        ],
                        'temperature' => 0.1,
                        'max_tokens'  => 1500,
                        'stream'      => false,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $raw  = $data['choices'][0]['message']['content'] ?? '';

                        // Strip markdown fences & PHP tags if present
                        $code = preg_replace('/^```(?:php)?\s*/m', '', $raw);
                        $code = preg_replace('/^```\s*$/m', '', $code);
                        $code = preg_replace('/^\s*<\?(?:php)?\s*/m', '', $code);
                        $code = preg_replace('/\?>\s*$/', '', $code);
                        $code = trim($code);

                        if (!empty($code)) {
                            return response()->json([
                                'success' => true,
                                'code'    => $code,
                                'model'   => $model,
                                'source'  => 'ai_llm',
                                'tokens'  => $data['usage']['total_tokens'] ?? 0,
                            ]);
                        }
                    }
                } catch (Throwable $e) {
                    // Try next model or fallback
                }
            }
        }

        // Smart Semantic Fallback Generator when API is unreachable
        $fallbackCode = $this->generateFallbackScript($userPrompt);

        return response()->json([
            'success' => true,
            'code'    => $fallbackCode,
            'model'   => 'smart-heuristic-engine',
            'source'  => 'ai_smart_engine',
            'tokens'  => 0,
        ]);
    }

    /**
     * Heuristic fallback script generator based on prompt keywords.
     */
    private function generateFallbackScript(string $prompt): string
    {
        $p = strtolower($prompt);

        if (str_contains($p, 'sync') || str_contains($p, 'moviebox') || str_contains($p, 'import')) {
            preg_match('/(?:sync|import|film|movie)\s+["\']?([^"\'\n,]+)["\']?/i', $prompt, $matches);
            $query = !empty($matches[1]) ? trim($matches[1]) : 'Spider-Man';
            return <<<PHP
\$keyword = '{$query}';
echo "=== MEMULAI SINKRONISASI FILM API MOVIEBOX: '{\$keyword}' ===\n\n";

\$searchService = app(\\App\\Services\\FilmSearchService::class);
\$searchService->fetchAndSyncFromMovieBox(\$keyword);

\$films = \\App\\Models\\Film::where('title', 'LIKE', "%\$keyword%")->get();
echo "Total hasil tersinkron di database lokal: " . \$films->count() . " film\n\n";
foreach (\$films as \$idx => \$f) {
    \$num = \$idx + 1;
    echo "  {\$num}. [{\$f->subject_type}] {\$f->title} ({\$f->release_year}) ★ {\$f->rating} | Slug: {\$f->slug}\n";
}
PHP;
        }

        if (str_contains($p, 'rating') || str_contains($p, 'top') || str_contains($p, 'populer')) {
            return <<<'PHP'
echo "=== TOP 10 FILM BERDASARKAN RATING TERTINGGI ===\n\n";
$topFilms = \App\Models\Film::where('rating', '>', 0)
    ->orderByDesc('rating')
    ->orderByDesc('release_year')
    ->limit(10)
    ->get();

foreach ($topFilms as $idx => $f) {
    $num = $idx + 1;
    echo sprintf("  %2d. ★ %-4.1f | %-40s (%d) [%s]\n", $num, $f->rating, $f->title, $f->release_year, $f->subject_type);
}
PHP;
        }

        if (str_contains($p, 'user') || str_contains($p, 'pengguna')) {
            return <<<'PHP'
echo "=== DAFTAR 10 PENGGUNA TERBARU TERDAFTAR ===\n\n";
$users = \App\Models\User::latest()->limit(10)->get();
foreach ($users as $idx => $u) {
    $num = $idx + 1;
    $status = $u->is_banned ? '⛔ BANNED' : '✅ ACTIVE';
    echo "  {$num}. [{$status}] {$u->name} ({$u->email}) - Terdaftar: {$u->created_at->format('d M Y H:i')}\n";
}
PHP;
        }

        // Default General Stats
        return <<<'PHP'
echo "=======================================================\n";
echo "       RINGKASAN STATISTIK DATABASE FAIILMOV           \n";
echo "=======================================================\n\n";

echo "• Total Film: " . \App\Models\Film::where('subject_type', 'movie')->count() . "\n";
echo "• Total Series: " . \App\Models\Film::where('subject_type', 'series')->count() . "\n";
echo "• Total Dracin: " . \App\Models\Film::where('subject_type', 'dracin')->count() . "\n";
echo "• Total Aktor: " . \App\Models\Actor::count() . "\n";
echo "• Total Pengguna: " . \App\Models\User::count() . "\n";
echo "• Total Ulasan: " . \App\Models\Review::count() . "\n";
echo "• Total Watch Party: " . \App\Models\WatchParty::count() . "\n";
PHP;
    }

    /**
     * Delete saved script
     */
    public function destroy(AdminScript $script): JsonResponse
    {
        $title = $script->title;
        $script->delete();

        return response()->json([
            'success' => true,
            'message' => "Script '{$title}' berhasil dihapus.",
        ]);
    }

    /**
     * Seed starter templates for quick administrative tasks
     */
    private function seedStarterScripts(): void
    {
        $templates = [
            [
                'title' => 'Statistik Database Film & User',
                'description' => 'Menampilkan ringkasan statistik total film, aktor, review, dan user terdaftar.',
                'code' => <<<'PHP'
echo "=======================================================\n";
echo "       RINGKASAN STATISTIK DATABASE FAIIILMOV          \n";
echo "=======================================================\n\n";

echo "• Total Film: " . \App\Models\Film::count() . "\n";
echo "• Total Series: " . \App\Models\Film::where('subject_type', 'series')->count() . "\n";
echo "• Total Aktor: " . \App\Models\Actor::count() . "\n";
echo "• Total User Terdaftar: " . \App\Models\User::count() . "\n";
echo "• Total Ulasan Review: " . \App\Models\Review::count() . "\n";
echo "• Total Watch Party Active: " . \App\Models\WatchParty::where('status', 'active')->count() . "\n\n";

echo "Top 5 Film Rating Tertinggi:\n";
$topFilms = \App\Models\Film::orderByDesc('rating')->limit(5)->get();
foreach ($topFilms as $idx => $f) {
    $num = $idx + 1;
    echo "  {$num}. {$f->title} ({$f->release_year}) ★ {$f->rating}\n";
}
PHP,
            ],
            [
                'title' => 'Sync Live Film by Keyword',
                'description' => 'Memicu sinkronisasi film secara live dari API MovieBox berdasarkan kata kunci.',
                'code' => <<<'PHP'
$keyword = 'Spider-Man';

echo "Memulai Live Sync MovieBox untuk kata kunci: '{$keyword}'...\n";
app(\App\Services\FilmSearchService::class)->fetchAndSyncFromMovieBox($keyword);

$matches = \App\Models\Film::where('title', 'LIKE', "%{$keyword}%")->get();
echo "\nHasil sinkronisasi di database lokal (" . $matches->count() . " film):\n";
foreach ($matches as $idx => $film) {
    $num = $idx + 1;
    echo "  {$num}. [{$film->subject_type}] {$film->title} ({$film->release_year}) ID={$film->moviebox_subject_id}\n";
}
PHP,
            ],
            [
                'title' => 'Verifikasi Integritas Sinkronisasi (Self-Check)',
                'description' => 'Mengecek apakah sampel film dari API eksternal sudah ke-sync dengan lengkap di DB lokal.',
                'code' => <<<'PHP'
use App\Models\Film;
use App\Services\MovieBoxService;

$movieBox = app(MovieBoxService::class);
$samples = ['The Boys', 'Spider-Man', 'Avatar', 'Inception', 'Batman'];

echo "=== VERIFIKASI INTEGRITAS DATABASE vs API ===\n\n";
foreach ($samples as $title) {
    $localMatches = Film::where('title', 'LIKE', "%{$title}%")->count();
    $apiFound = 0;
    try {
        $apiData = $movieBox->search($title, 1);
        $extracted = Film::extractSearchSubjects($apiData);
        $apiFound = count($extracted);
    } catch (\Exception $e) {}

    $status = ($localMatches >= 1) ? "✅ OK" : "❌ MISSING";
    echo sprintf("• %-12s | DB Lokal: %2d film | API Upstream: %2d items | %s\n", $title, $localMatches, $apiFound, $status);
}
PHP,
            ],
            [
                'title' => 'Update Direct Trailer Video URL',
                'description' => 'Mengubah atau mengatur URL trailer video direct MP4 untuk film tertentu.',
                'code' => <<<'PHP'
$targetTitle = 'Spider-Man: Across the Spider-Verse';
$newTrailerUrl = 'https://macdn.aoneroom.com/media/vone/2023/06/09/04ae1be74a74853ce26b0fadec981d70-sd.mp4';

$film = \App\Models\Film::where('title', 'LIKE', "%{$targetTitle}%")->first();
if ($film) {
    $film->update(['trailer_url' => $newTrailerUrl]);
    echo "SUKSES: Trailer untuk film '{$film->title}' berhasil diperbarui!\n";
    echo "New Trailer URL: {$film->trailer_url}\n";
    echo "Detected Provider: {$film->trailer_provider}\n";
    echo "Embed URL: {$film->embed_trailer_url}\n";
} else {
    echo "ERROR: Film '{$targetTitle}' tidak ditemukan di database.\n";
}
PHP,
            ],
        ];

        foreach ($templates as $t) {
            AdminScript::create($t);
        }
    }
}
