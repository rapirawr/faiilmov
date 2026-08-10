<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminScript;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
    public function store(Request $request)
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Script berhasil disimpan.',
                'script' => $script,
            ]);
        }

        return redirect()->back()->with('success', 'Script PHP berhasil disimpan!');
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
     * Delete saved script
     */
    public function destroy(AdminScript $script)
    {
        $title = $script->title;
        $script->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Script '{$title}' berhasil dihapus.",
            ]);
        }

        return redirect()->back()->with('success', "Script '{$title}' berhasil dihapus.");
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
