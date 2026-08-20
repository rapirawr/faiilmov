<?php

namespace App\Http\Controllers;

use App\Models\EpisodeComment;
use App\Models\EpisodeCommentLike;
use App\Models\EpisodeCommentReport;
use App\Models\Film;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EpisodeCommentController extends Controller
{
    /**
     * Get list of comments for a specific episode of a series
     */
    public function index(Request $request)
    {
        $request->validate([
            'film_id' => 'required|integer|exists:films,id',
            'season' => 'required|integer|min:1',
            'episode' => 'required|integer|min:1',
        ]);

        $filmId = (int)$request->film_id;
        $season = (int)$request->season;
        $episode = (int)$request->episode;
        $currentUserId = Auth::id();

        $comments = EpisodeComment::with([
                'user:id,name,avatar',
                'replies' => function ($query) {
                    $query->with(['user:id,name,avatar', 'likes'])->withCount('likes')->oldest();
                },
                'likes'
            ])
            ->withCount('likes')
            ->where('film_id', $filmId)
            ->where('season_number', $season)
            ->where('episode_number', $episode)
            ->whereNull('parent_id')
            ->latest()
            ->get();

        $formatted = $comments->map(function ($c) use ($currentUserId) {
            return $this->formatComment($c, $currentUserId);
        });

        $totalCount = EpisodeComment::where('film_id', $filmId)
            ->where('season_number', $season)
            ->where('episode_number', $episode)
            ->count();

        return response()->json([
            'success' => true,
            'total' => $totalCount,
            'comments' => $formatted,
            'auth_user' => Auth::check() ? [
                'id' => Auth::id(),
                'name' => Auth::user()->name,
                'avatar' => Auth::user()->avatar,
                'is_admin' => Auth::user()->isAdmin(),
            ] : null,
        ]);
    }

    /**
     * Store a new episode comment or reply
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan masuk ke akun Anda untuk menulis komentar.',
            ], 401);
        }

        $validated = $request->validate([
            'film_id' => 'required|integer|exists:films,id',
            'season_number' => 'required|integer|min:1',
            'episode_number' => 'required|integer|min:1',
            'parent_id' => 'nullable|integer|exists:episode_comments,id',
            'comment' => 'required|string|min:2|max:2000',
            'is_spoiler' => 'nullable|boolean',
        ]);

        $film = Film::findOrFail($validated['film_id']);

        // Check if parent comment exists and prevent deep nesting (> 1 level)
        $parentId = $validated['parent_id'] ?? null;
        if ($parentId) {
            $parent = EpisodeComment::find($parentId);
            if ($parent && $parent->parent_id) {
                // Attach to top-level parent instead of nesting deeper
                $parentId = $parent->parent_id;
            }
        }

        $comment = EpisodeComment::create([
            'film_id' => $film->id,
            'user_id' => Auth::id(),
            'season_number' => $validated['season_number'],
            'episode_number' => $validated['episode_number'],
            'parent_id' => $parentId,
            'comment' => trim($validated['comment']),
            'is_spoiler' => (bool)($validated['is_spoiler'] ?? false),
            'likes_count' => 0,
        ]);

        // Award XP
        try {
            app(\App\Services\GamificationService::class)->awardXp(
                Auth::user(),
                15,
                'comment',
                null,
                ['film_id' => $film->id, 'comment_id' => $comment->id]
            );
        } catch (\Exception $e) {
            \Log::error('Gamification comment XP error: ' . $e->getMessage());
        }

        $comment->load(['user:id,name,avatar', 'replies.user:id,name,avatar', 'likes']);

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil dipublikasikan!',
            'comment' => $this->formatComment($comment, Auth::id()),
        ], 201);
    }

    /**
     * Toggle like on a comment
     */
    public function toggleLike(EpisodeComment $comment)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu untuk menyukai komentar.',
            ], 401);
        }

        $userId = Auth::id();
        $existingLike = EpisodeCommentLike::where('comment_id', $comment->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $comment->decrement('likes_count');
            $liked = false;
        } else {
            EpisodeCommentLike::create([
                'comment_id' => $comment->id,
                'user_id' => $userId,
            ]);
            $comment->increment('likes_count');
            $liked = true;
        }

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => max(0, (int)$comment->fresh()->likes_count),
        ]);
    }

    /**
     * Report an episode comment
     */
    public function report(Request $request, EpisodeComment $comment)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login untuk melaporkan komentar.',
            ], 401);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);

        EpisodeCommentReport::create([
            'comment_id' => $comment->id,
            'user_id' => Auth::id(),
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih, laporan Anda telah diterima untuk ditinjau oleh tim moderator.',
        ]);
    }

    /**
     * Delete an episode comment
     */
    public function destroy(EpisodeComment $comment)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();
        if ($comment->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak untuk menghapus komentar ini.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil dihapus.',
        ]);
    }

    /**
     * Helper to format comment object for JSON output
     */
    private function formatComment(EpisodeComment $c, ?int $currentUserId): array
    {
        $isLiked = $currentUserId ? $c->likes->contains('user_id', $currentUserId) : false;

        $replies = ($c->relationLoaded('replies') ? $c->replies : collect())->map(function ($r) use ($currentUserId) {
            $rIsLiked = $currentUserId ? $r->likes->contains('user_id', $currentUserId) : false;
            return [
                'id' => $r->id,
                'parent_id' => $r->parent_id,
                'comment' => $r->comment,
                'is_spoiler' => (bool)$r->is_spoiler,
                'likes_count' => (int)$r->likes_count,
                'is_liked' => $rIsLiked,
                'created_at' => $r->created_at ? $r->created_at->diffForHumans() : '',
                'user' => [
                    'id' => $r->user->id ?? 0,
                    'name' => $r->user->name ?? 'Pengguna',
                    'avatar' => $r->user->avatar ?? null,
                    'initial' => strtoupper(substr($r->user->name ?? 'P', 0, 2)),
                ],
                'can_delete' => $currentUserId && ($r->user_id === $currentUserId || (Auth::check() && Auth::user()->isAdmin())),
            ];
        });

        return [
            'id' => $c->id,
            'parent_id' => $c->parent_id,
            'comment' => $c->comment,
            'is_spoiler' => (bool)$c->is_spoiler,
            'likes_count' => (int)$c->likes_count,
            'is_liked' => $isLiked,
            'created_at' => $c->created_at ? $c->created_at->diffForHumans() : '',
            'user' => [
                'id' => $c->user->id ?? 0,
                'name' => $c->user->name ?? 'Pengguna',
                'avatar' => $c->user->avatar ?? null,
                'initial' => strtoupper(substr($c->user->name ?? 'P', 0, 2)),
            ],
            'replies' => $replies,
            'can_delete' => $currentUserId && ($c->user_id === $currentUserId || (Auth::check() && Auth::user()->isAdmin())),
        ];
    }
}
