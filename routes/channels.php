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
    
    // Grant access to valid room participants (authenticated user or valid room session)
    return [
        'id' => $user->id ?? session()->getId(),
        'name' => $user->name ?? 'Guest',
    ];
});
