<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WatchParty;
use App\Models\WatchPartyMessage;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWatchPartyController extends Controller
{
    public function index(Request $request)
    {
        $query = WatchParty::with(['film', 'hostUser', 'participants']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('room_code', 'like', "%{$search}%")
                  ->orWhere('host_guest_name', 'like', "%{$search}%")
                  ->orWhereHas('film', function ($fq) use ($search) {
                      $fq->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('hostUser', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $watchParties = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $activeCount = WatchParty::where('status', 'active')->count();
        $endedCount = WatchParty::where('status', 'ended')->count();

        return view('admin.watch-parties.index', compact('watchParties', 'activeCount', 'endedCount'));
    }

    public function show(WatchParty $watchParty)
    {
        $watchParty->load([
            'film',
            'hostUser',
            'participants.user',
        ]);

        $messages = WatchPartyMessage::where('watch_party_id', $watchParty->id)
            ->orderBy('created_at', 'asc')
            ->take(100)
            ->get();

        return view('admin.watch-parties.show', compact('watchParty', 'messages'));
    }

    public function forceClose(WatchParty $watchParty)
    {
        $roomCode = $watchParty->room_code;
        $watchParty->update(['status' => 'ended']);

        // Send system message in chat
        WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'user_id' => Auth::id(),
            'sender_name' => 'SYSTEM ADMIN',
            'message' => 'Ruangan Nobar ini telah ditutup oleh Administrator.',
            'is_system' => true,
        ]);

        AdminActivityLog::log('force_closed_watch_party', "Menutup paksa ruang Nobar #{$roomCode}", 'WatchParty', $watchParty->id);

        return back()->with('success', "Ruangan Nobar #{$roomCode} telah ditutup secara paksa.");
    }

    public function sendMessage(Request $request, WatchParty $watchParty)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'user_id' => Auth::id(),
            'sender_name' => '📢 ADMIN ANNOUNCEMENT',
            'message' => $validated['message'],
            'is_system' => true,
        ]);

        AdminActivityLog::log('sent_watch_party_announcement', "Mengirim pengumuman admin ke ruang Nobar #{$watchParty->room_code}", 'WatchParty', $watchParty->id);

        return back()->with('success', "Pengumuman berhasil dikirim ke dalam obrolan Nobar.");
    }
}
