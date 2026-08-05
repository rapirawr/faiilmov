<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WatchPartyPlaybackUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $roomCode;
    public string $action; // play, pause, seek, sync
    public float $position;
    public bool $isPlaying;
    public float $speed;
    public string $senderName;

    public function __construct(string $roomCode, string $action, float $position, bool $isPlaying, float $speed = 1.0, string $senderName = 'Host')
    {
        $this->roomCode = $roomCode;
        $this->action = $action;
        $this->position = $position;
        $this->isPlaying = $isPlaying;
        $this->speed = $speed;
        $this->senderName = $senderName;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('watch-party.' . $this->roomCode),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PlaybackUpdated';
    }
}
