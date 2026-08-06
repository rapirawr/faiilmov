<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount(['reviews', 'watchlists']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'banned') {
                $query->where('is_banned', true);
            } elseif ($request->status === 'active') {
                $query->where('is_banned', false);
            } elseif ($request->status === 'admin') {
                $query->where('is_admin', true);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load([
            'reviews.film',
            'watchlists.film',
            'watchHistories.film',
        ]);

        return view('admin.users.show', compact('user'));
    }

    public function ban(Request $request, User $user)
    {
        if ($user->isAdmin()) {
            return redirect()->back()->with('error', 'Tidak dapat mem-ban sesama Administrator.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'duration' => 'required|in:1_day,7_days,30_days,permanent',
        ]);

        $bannedUntil = match ($validated['duration']) {
            '1_day' => now()->addDay(),
            '7_days' => now()->addDays(7),
            '30_days' => now()->addDays(30),
            'permanent' => null,
        };

        $user->update([
            'is_banned' => true,
            'banned_reason' => $validated['reason'],
            'banned_until' => $bannedUntil,
        ]);

        $durationText = $bannedUntil ? "sampai " . $bannedUntil->format('d M Y H:i') : "secara permanen";
        AdminActivityLog::log('banned_user', "Mem-ban user '{$user->name}' ({$user->email}) {$durationText}. Alasan: {$validated['reason']}", 'User', $user->id);

        return redirect()->back()->with('success', "User '{$user->name}' berhasil dibanned {$durationText}.");
    }

    public function unban(User $user)
    {
        $user->update([
            'is_banned' => false,
            'banned_reason' => null,
            'banned_until' => null,
        ]);

        AdminActivityLog::log('unbanned_user', "Membuka supen/unban user '{$user->name}' ({$user->email})", 'User', $user->id);

        return redirect()->back()->with('success', "Status ban user '{$user->name}' berhasil dicabut.");
    }
}
