<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WatchPartyParticipantLeft implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $roomCode;
    public string $displayName;
    public string $newHostName;

    public function __construct(string $roomCode, string $displayName, string $newHostName = '')
    {
        $this->roomCode = $roomCode;
        $this->displayName = $displayName;
        $this->newHostName = $newHostName;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('watch-party.' . $this->roomCode),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ParticipantLeft';
    }
}
