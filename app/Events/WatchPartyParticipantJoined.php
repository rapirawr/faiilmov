<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WatchPartyParticipantJoined implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $roomCode;
    public string $displayName;
    public bool $isHost;
    public array $participants;

    public function __construct(string $roomCode, string $displayName, bool $isHost = false, array $participants = [])
    {
        $this->roomCode = $roomCode;
        $this->displayName = $displayName;
        $this->isHost = $isHost;
        $this->participants = $participants;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('watch-party.' . $this->roomCode),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ParticipantJoined';
    }
}
