<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\WatchParty;
use App\Models\WatchPartyParticipant;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('watch-party.{roomCode}', function ($user, string $roomCode) {
    $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->first();
    if (!$watchParty || $watchParty->status === 'ended') {
        return false;
    }
    
    // Verify user is an active participant
    $sessionId = session()->getId();
    $participant = WatchPartyParticipant::where('watch_party_id', $watchParty->id)
        ->where(function ($q) use ($user, $sessionId) {
            if ($user) {
                $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
            } else {
                $q->where('session_id', $sessionId);
            }
        })
        ->whereNull('left_at')
        ->first();
    
    if (!$participant) {
        return false;
    }
    
    return [
        'id'      => $participant->id,
        'user_id' => $user->id ?? null,
        'name'    => $participant->display_name,
        'is_host' => (bool)$participant->is_host,
    ];
});
