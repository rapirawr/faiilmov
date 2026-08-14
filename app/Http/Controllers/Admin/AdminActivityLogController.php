<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminActivityLog::with('admin');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('admin', fn($a) => $a->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('category')) {
            $cat = $request->category;
            $query->where('action', 'like', "%{$cat}%");
        }

        if ($request->filled('timeframe')) {
            if ($request->timeframe === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($request->timeframe === '7d') {
                $query->where('created_at', '>=', now()->subDays(7));
            } elseif ($request->timeframe === '30d') {
                $query->where('created_at', '>=', now()->subDays(30));
            }
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();
        $admins = \App\Models\User::where('is_admin', true)->get(['id', 'name']);

        return view('admin.activity_logs.index', compact('logs', 'admins'));
    }

    public function clearOldLogs(Request $request)
    {
        $days = (int)$request->input('days', 30);
        $threshold = now()->subDays($days);
        $deleted = AdminActivityLog::where('created_at', '<', $threshold)->delete();

        AdminActivityLog::log('cleared_old_logs', "Membersihkan {$deleted} catatan activity log yang lebih lama dari {$days} hari.");

        return redirect()->route('admin.activity_logs.index')->with('success', "Berhasil membersihkan {$deleted} log aktivitas lama.");
    }
}
