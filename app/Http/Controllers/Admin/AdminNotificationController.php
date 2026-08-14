<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Film;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminNotificationController extends Controller
{
    /**
     * Display the push notification broadcast center.
     */
    public function index(Request $request)
    {
        $totalUsers = User::count();
        $totalNotifications = Notification::count();
        $unreadNotifications = Notification::where('is_read', false)->count();

        // Get grouped recent broadcasts with accurate IDs list
        $broadcasts = Notification::select(
                'message',
                'type',
                'url',
                DB::raw('GROUP_CONCAT(id) as notification_ids'),
                DB::raw('MIN(created_at) as created_at'),
                DB::raw('COUNT(id) as recipient_count'),
                DB::raw('SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count')
            )
            ->groupBy('message', 'type', 'url')
            ->orderByDesc(DB::raw('MIN(created_at)'))
            ->paginate(15);

        // List of recent films for quick selection in the composer
        $recentFilms = Film::latest()
            ->take(80)
            ->get(['id', 'title', 'slug', 'poster_url', 'subject_type', 'release_year']);

        // Initial sample users for custom receiver picker
        $initialUsers = User::latest()
            ->take(20)
            ->get(['id', 'name', 'email', 'avatar', 'is_admin']);

        return view('admin.notifications.index', compact(
            'totalUsers',
            'totalNotifications',
            'unreadNotifications',
            'broadcasts',
            'recentFilms',
            'initialUsers'
        ));
    }

    /**
     * Live search users for custom receiver picker.
     */
    public function searchUsers(Request $request)
    {
        $q = trim($request->query('q', ''));
        $query = User::query();

        if ($q !== '') {
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'LIKE', "%{$q}%")
                    ->orWhere('email', 'LIKE', "%{$q}%");
            });
        }

        $users = $query->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'email', 'avatar', 'is_admin'])
            ->map(function($user) {
                return [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'avatar'   => $user->avatar ? asset('storage/' . $user->avatar) : null,
                    'is_admin' => (bool)$user->is_admin,
                ];
            });

        return response()->json($users);
    }

    /**
     * Send push notification broadcast to users in batch or specific custom recipients.
     */
    public function send(Request $request)
    {
        $request->validate([
            'title'            => 'nullable|string|max:150',
            'message'          => 'required|string|max:1000',
            'type'             => 'required|in:system,announcement,new_film,maintenance,promotion,watch_party',
            'target'           => 'required|in:all,active_30d,admin_only,verified_only,custom',
            'custom_user_ids'  => 'nullable',
            'custom_emails'    => 'nullable|string|max:2000',
            'url'              => 'nullable|string|max:500',
        ]);

        // Determine target users
        $query = User::query();
        if ($request->target === 'active_30d') {
            $query->where('updated_at', '>=', now()->subDays(30));
        } elseif ($request->target === 'admin_only') {
            $query->where('is_admin', true);
        } elseif ($request->target === 'verified_only') {
            $query->whereNotNull('email_verified_at');
        } elseif ($request->target === 'custom') {
            $userIdsInput = $request->input('custom_user_ids', []);
            if (is_string($userIdsInput)) {
                $userIdsInput = array_filter(explode(',', $userIdsInput), 'is_numeric');
            } elseif (is_array($userIdsInput)) {
                $userIdsInput = array_filter($userIdsInput, 'is_numeric');
            }

            $customEmails = [];
            if ($request->filled('custom_emails')) {
                $rawEmails = preg_split('/[\s,;]+/', $request->input('custom_emails'));
                $customEmails = array_filter(array_map('trim', $rawEmails), fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
            }

            if (empty($userIdsInput) && empty($customEmails)) {
                return back()->with('error', 'Silakan pilih minimal 1 pengguna atau masukkan email penerima custom.');
            }

            $query->where(function($q) use ($userIdsInput, $customEmails) {
                if (!empty($userIdsInput)) {
                    $q->whereIn('id', $userIdsInput);
                }
                if (!empty($customEmails)) {
                    $q->orWhereIn('email', $customEmails);
                }
            });
        }

        $userIds = $query->pluck('id');

        if ($userIds->isEmpty()) {
            return back()->with('error', 'Tidak ditemukan pengguna dengan kriteria target yang dipilih.');
        }

        $title = trim($request->title ?? '');
        $messageBody = trim($request->message);
        $fullMessage = $title !== '' ? "【{$title}】\n{$messageBody}" : $messageBody;
        $now = now();

        $records = [];
        foreach ($userIds as $uid) {
            $records[] = [
                'user_id'    => $uid,
                'type'       => $request->type,
                'message'    => $fullMessage,
                'url'        => $request->url ? trim($request->url) : null,
                'is_read'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert in chunks of 500 records for performance
        foreach (array_chunk($records, 500) as $chunk) {
            Notification::insert($chunk);
        }

        $recipientCount = count($userIds);

        AdminActivityLog::log(
            'broadcast_notification',
            "Mengirim notifikasi ({$request->type}) ke {$recipientCount} pengguna (Target: {$request->target}). Judul: " . ($title ?: 'Tanpa Judul')
        );

        return back()->with('success', "🎉 Notifikasi berhasil dikirimkan ke {$recipientCount} pengguna.");
    }

    /**
     * Delete / recall a broadcast notification for all recipient users.
     */
    public function destroyBroadcast(Request $request)
    {
        $query = Notification::query();

        if ($request->filled('notification_ids')) {
            $ids = array_filter(explode(',', $request->notification_ids), 'is_numeric');
            $query->whereIn('id', $ids);
        } elseif ($request->filled('message')) {
            $cleanMsg = str_replace("\r\n", "\n", trim($request->message));
            $query->where(function($q) use ($request, $cleanMsg) {
                $q->where('message', $cleanMsg)
                  ->orWhere('message', $request->message)
                  ->orWhere('message', 'LIKE', '%' . substr($cleanMsg, 0, 50) . '%');
            });
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
        } else {
            return back()->with('error', 'Parameter notifikasi tidak valid.');
        }

        $deleted = $query->delete();

        AdminActivityLog::log(
            'delete_broadcast',
            "Menarik/menghapus broadcast notifikasi ({$deleted} notifikasi pengguna terhapus dari database)."
        );

        return back()->with('success', "Berhasil menarik kembali {$deleted} notifikasi dari kotak masuk seluruh pengguna.");
    }

    /**
     * Generate notification title, copy, type and URL via AI.
     */
    public function generateAi(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|min:3|max:1000',
            'tone'   => 'nullable|string|in:enthusiastic,formal,urgent,casual,promo',
        ]);

        $userPrompt = trim($request->input('prompt'));
        $tone = $request->input('tone', 'enthusiastic');

        // Check if user mentioned any film from our database
        $matchedFilm = null;
        $allFilms = Film::latest()->take(100)->get(['id', 'title', 'slug']);
        foreach ($allFilms as $film) {
            if (stripos($userPrompt, $film->title) !== false) {
                $matchedFilm = $film;
                break;
            }
        }

        $apiKey = \App\Models\Setting::get('nvidia_api_key', '') ?: env('NVIDIA_API_KEY', config('services.nvidia.api_key', ''));
        $apiUrl = env('NVIDIA_API_URL', config('services.nvidia.base_url', 'https://integrate.api.nvidia.com/v1'));

        if (!empty($apiKey)) {
            $systemPrompt = <<<SYSTEM
Kamu adalah AI Copywriter profesional untuk aplikasi streaming sinema Faiilmov.
Tugasmu adalah menghasilkan data push notification pengguna dalam format JSON murni.

Instruksi Gaya Bahasa (Tone: {$tone}):
- 'enthusiastic': Bersemangat, mengajak, menggunakan kata seru yang natural (tanpa emoji berlebihan).
- 'formal': Sopan, jelas, terstruktur.
- 'urgent': Mendesak, penting untuk segera dibaca.
- 'casual': Santai, ramah, seperti berbicara dengan teman.
- 'promo': Menggiurkan, menonjolkan keuntungan/spesial event.

FORMAT OUTPUT WAJIB JSON MURNI (tanpa markdown fences, tanpa teks lain):
{
  "title": "Judul Notifikasi Singkat & Menarik (maksimal 60 karakter)",
  "message": "Isi pesan notifikasi yang ringkas, jelas, dan memikat (1-3 kalimat, maksimal 250 karakter)",
  "type": "new_film | announcement | watch_party | system | maintenance | promotion",
  "target": "all | active_30d | admin_only",
  "url": "/film/slug-judul atau /watch-party atau null"
}
SYSTEM;

            $modelsToTry = ['meta/llama-3.1-8b-instruct', 'meta/llama-3.2-3b-instruct'];
            foreach ($modelsToTry as $model) {
                try {
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                    ])->timeout(12)->post($apiUrl . '/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt . ($matchedFilm ? " (Film terkait di sistem: {$matchedFilm->title}, slug: {$matchedFilm->slug})" : "")],
                        ],
                        'temperature' => 0.7,
                        'max_tokens'  => 500,
                        'response_format' => ['type' => 'json_object'],
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $content = $data['choices'][0]['message']['content'] ?? '';
                        $parsed = json_decode($content, true);

                        if ($parsed && isset($parsed['title'], $parsed['message'])) {
                            return response()->json([
                                'success' => true,
                                'title'   => $parsed['title'],
                                'message' => $parsed['message'],
                                'type'    => in_array($parsed['type'] ?? '', ['new_film', 'announcement', 'watch_party', 'system', 'maintenance', 'promotion']) ? $parsed['type'] : 'announcement',
                                'target'  => in_array($parsed['target'] ?? '', ['all', 'active_30d', 'admin_only']) ? $parsed['target'] : 'all',
                                'url'     => $matchedFilm ? ('/film/' . $matchedFilm->slug) : ($parsed['url'] ?? ''),
                                'source'  => 'ai_llm',
                                'model'   => $model,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    // Try next model or fallback
                }
            }
        }

        // Smart Semantic Fallback Generator
        $result = $this->generateFallbackCopy($userPrompt, $tone, $matchedFilm);

        return response()->json([
            'success' => true,
            'title'   => $result['title'],
            'message' => $result['message'],
            'type'    => $result['type'],
            'target'  => $result['target'],
            'url'     => $result['url'],
            'source'  => 'ai_smart_engine',
        ]);
    }

    /**
     * Smart heuristic notification generator when LLM API is unreachable.
     */
    private function generateFallbackCopy(string $prompt, string $tone, ?Film $matchedFilm): array
    {
        $lower = strtolower($prompt);

        if (str_contains($lower, 'maint') || str_contains($lower, 'server') || str_contains($lower, 'perbaikan') || str_contains($lower, 'down')) {
            return [
                'title'   => 'Pemeliharaan Server Terjadwal',
                'message' => 'Kami sedang melakukan peningkatan performa infrastruktur server streaming Faiilmov. Beberapa fitur mungkin mengalami kendala sesaat.',
                'type'    => 'maintenance',
                'target'  => 'all',
                'url'     => '',
            ];
        }

        if (str_contains($lower, 'nobar') || str_contains($lower, 'party') || str_contains($lower, 'nonton bareng')) {
            return [
                'title'   => 'Sesi Nobar Watch Party Dimulai!',
                'message' => 'Komunitas Faiilmov sedang mengadakan nobar seru sekarang. Masuk ke room dan nikmati obrolan live sambil streaming!',
                'type'    => 'watch_party',
                'target'  => 'all',
                'url'     => '/watch-party',
            ];
        }

        if (str_contains($lower, 'update') || str_contains($lower, 'fitur') || str_contains($lower, 'rilis app') || str_contains($lower, 'versi')) {
            return [
                'title'   => 'Pembaruan Fitur Baru Faiilmov',
                'message' => 'Nikmati pengalaman streaming yang lebih cepat, mulus, dan fitur baru yang telah kami siapkan untuk kamu hari ini!',
                'type'    => 'announcement',
                'target'  => 'all',
                'url'     => '',
            ];
        }

        if ($matchedFilm || str_contains($lower, 'film') || str_contains($lower, 'dracin') || str_contains($lower, 'series') || str_contains($lower, 'rilis') || str_contains($lower, 'nonton')) {
            $filmName = $matchedFilm ? $matchedFilm->title : 'Sinema Terbaru';
            return [
                'title'   => "Rilis Baru: {$filmName}",
                'message' => "{$filmName} kini sudah resmi tayang di Faiilmov! Tonton sekarang dengan kualitas Full HD dan subtitle bahasa Indonesia gratis.",
                'type'    => 'new_film',
                'target'  => 'all',
                'url'     => $matchedFilm ? ('/film/' . $matchedFilm->slug) : '',
            ];
        }

        return [
            'title'   => 'Pengumuman Penting Faiilmov',
            'message' => ucfirst(rtrim($prompt, '.')) . '. Simak info lengkapnya sekarang di aplikasi Faiilmov!',
            'type'    => 'announcement',
            'target'  => 'all',
            'url'     => '',
        ];
    }
}
