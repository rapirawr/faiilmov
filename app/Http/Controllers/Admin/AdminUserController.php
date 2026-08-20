<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status;
        
        if ($status === 'trashed') {
            $query = User::onlyTrashed();
        } elseif ($status === 'all_with_trashed') {
            $query = User::withTrashed();
        } else {
            $query = User::query();
        }

        $query->withCount(['reviews', 'watchlists', 'profiles']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status && !in_array($status, ['trashed', 'all_with_trashed'])) {
            if ($request->status === 'banned') {
                $query->where('is_banned', true);
            } elseif ($request->status === 'active') {
                $query->where('is_banned', false);
            } elseif ($request->status === 'administrator') {
                $query->where(function ($q) {
                    $q->where('role', 'administrator')
                      ->orWhere(function ($sub) {
                          $sub->where('is_admin', true)->where('role', '!=', 'admin');
                      });
                });
            } elseif ($request->status === 'admin') {
                $query->where('role', 'admin');
            } elseif ($request->status === 'user') {
                $query->where(function ($q) {
                    $q->where('role', 'user')->orWhereNull('role');
                })->where('is_admin', false);
            } elseif ($request->status === 'ad_free') {
                $query->where('is_ad_free', true);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Statistical counts for the shortcut buttons
        $stats = [
            'total'         => User::count(),
            'active'        => User::where('is_banned', false)->count(),
            'banned'        => User::where('is_banned', true)->count(),
            'administrator' => User::where('role', 'administrator')->orWhere(fn($q) => $q->where('is_admin', true)->where('role', '!=', 'admin'))->count(),
            'admin'         => User::where('role', 'admin')->count(),
            'user'          => User::where(fn($q) => $q->where('role', 'user')->orWhereNull('role'))->where('is_admin', false)->count(),
            'ad_free'       => User::where('is_ad_free', true)->count(),
            'trashed'       => User::onlyTrashed()->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Update user role (user, admin, administrator). Only Superadmin/Administrator can perform this.
     */
    public function updateRole(Request $request, User $user)
    {
        if (!auth()->user()->isAdministrator()) {
            return redirect()->back()->with('error', 'Hanya Administrator Utama yang berhak mengubah role pengguna.');
        }

        $validated = $request->validate([
            'role' => 'required|in:user,admin,administrator',
        ]);

        $newRole = $validated['role'];

        if ($user->id === auth()->id() && $newRole !== 'administrator') {
            $otherAdminCount = User::where('role', 'administrator')->where('id', '!=', $user->id)->count();
            if ($otherAdminCount === 0) {
                return redirect()->back()->with('error', 'Tidak dapat mencabut hak Administrator dari satu-satunya Administrator Utama di sistem.');
            }
        }

        $user->update([
            'role' => $newRole,
            'is_admin' => in_array($newRole, ['admin', 'administrator'], true),
        ]);

        $roleLabel = match ($newRole) {
            'administrator' => 'Administrator Utama',
            'admin' => 'Admin Konten',
            default => 'Pengguna Biasa',
        };

        AdminActivityLog::log('updated_user_role', "Mengubah role user '{$user->name}' ({$user->email}) menjadi {$roleLabel}.", 'User', $user->id);

        return redirect()->back()->with('success', "Role pengguna '{$user->name}' berhasil diubah menjadi {$roleLabel}.");
    }

    /**
     * Toggle ad-free status for a specific user.
     */
    public function toggleAdFree(User $user)
    {
        $user->update([
            'is_ad_free' => !$user->is_ad_free,
        ]);

        $statusText = $user->is_ad_free ? 'diaktifkan (Bebas Iklan)' : 'dinonaktifkan (Iklan Ditampilkan)';
        AdminActivityLog::log('updated_user_ad_free', "Status bebas iklan untuk user '{$user->name}' ({$user->email}) {$statusText}.", 'User', $user->id);

        return redirect()->back()->with('success', "Status bebas iklan untuk user '{$user->name}' berhasil {$statusText}.");
    }

    /**
     * Toggle admin role for backward compatibility.
     */
    public function toggleAdmin(User $user)
    {
        if (!auth()->user()->isAdministrator()) {
            return redirect()->back()->with('error', 'Hanya Administrator Utama yang berhak mengubah role pengguna.');
        }

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat mencabut hak akses Administrator pada akun Anda sendiri.');
        }

        $newRole = $user->isAdmin() ? 'user' : 'admin';
        $user->update([
            'role' => $newRole,
            'is_admin' => ($newRole !== 'user'),
        ]);

        $roleText = ($newRole === 'admin') ? 'Admin Konten' : 'Pengguna Biasa';
        AdminActivityLog::log('updated_user_role', "Mengubah role user '{$user->name}' ({$user->email}) menjadi {$roleText}.", 'User', $user->id);

        return redirect()->back()->with('success', "Role pengguna '{$user->name}' berhasil diubah menjadi {$roleText}.");
    }

    public function show($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $user->load([
            'profiles',
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

        DB::transaction(function () use ($user, $validated, $bannedUntil) {
            $user->update([
                'is_banned' => true,
                'banned_reason' => $validated['reason'],
                'banned_until' => $bannedUntil,
            ]);

            // Invalidate ALL active sessions for this user
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
        });

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

    /**
     * Soft delete a user.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->isAdmin() && !auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', 'Tidak memiliki wewenang untuk menghapus akun Administrator.');
        }

        DB::transaction(function () use ($user) {
            // Invalidate user sessions
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        AdminActivityLog::log('soft_deleted_user', "Menghapus akun (Soft Delete) '{$user->name}' ({$user->email})", 'User', $user->id);

        return redirect()->back()->with('success', "Akun pengguna '{$user->name}' berhasil dipindahkan ke sampah (Soft Delete).");
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        AdminActivityLog::log('restored_user', "Memulihkan akun pengguna '{$user->name}' ({$user->email}) dari sampah", 'User', $user->id);

        return redirect()->back()->with('success', "Akun pengguna '{$user->name}' berhasil dipulihkan.");
    }

    /**
     * Permanently delete a user from the database.
     */
    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $userId = $user->id;

        DB::transaction(function () use ($user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->forceDelete();
        });

        AdminActivityLog::log('force_deleted_user', "Menghapus akun secara permanen: '{$userName}' ({$userEmail})", 'User', $userId);

        return redirect()->back()->with('success', "Akun pengguna '{$userName}' telah dihapus secara permanen dari sistem.");
    }
}
