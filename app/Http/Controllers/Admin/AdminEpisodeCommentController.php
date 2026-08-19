<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EpisodeComment;
use App\Models\EpisodeCommentReport;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;

class AdminEpisodeCommentController extends Controller
{
    public function index(Request $request)
    {
        $query = EpisodeComment::with(['user', 'film'])->withCount(['reports', 'likes']);

        $filter = $request->get('filter', 'latest');

        if ($filter === 'reported') {
            $query->has('reports')->orderBy('reports_count', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('film', fn($f) => $f->where('title', 'like', "%{$search}%"));
            });
        }

        $comments = $query->paginate(15)->withQueryString();

        return view('admin.comments.index', compact('comments'));
    }

    public function destroy(Request $request, EpisodeComment $comment)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $filmTitle = $comment->film->title ?? 'Series';
        $userName = $comment->user->name ?? 'User';
        $commentId = $comment->id;
        $epInfo = "S{$comment->season_number} E{$comment->episode_number}";

        $comment->delete();

        AdminActivityLog::log(
            'deleted_episode_comment',
            "Menghapus komentar episode oleh '{$userName}' pada series '{$filmTitle}' ({$epInfo}). Alasan: {$validated['reason']}",
            'EpisodeComment',
            $commentId
        );

        return redirect()->route('admin.comments.index')->with('success', 'Komentar episode berhasil dihapus dan dicatat di Activity Log.');
    }

    public function dismissReports(EpisodeComment $comment)
    {
        EpisodeCommentReport::where('comment_id', $comment->id)->update(['status' => 'dismissed']);

        AdminActivityLog::log('dismissed_episode_comment_reports', "Mengabaikan laporan komentar episode ID: {$comment->id}", 'EpisodeComment', $comment->id);

        return redirect()->route('admin.comments.index')->with('success', 'Laporan komentar episode berhasil diabaikan.');
    }
}
