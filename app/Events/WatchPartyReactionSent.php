<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WatchPartyReactionSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $roomCode;
    public string $emoji;
    public string $senderName;
    public string $id;

    public function __construct(string $roomCode, string $emoji, string $senderName = 'Guest')
    {
        $this->roomCode = $roomCode;
        $this->emoji = $emoji;
        $this->senderName = $senderName;
        $this->id = uniqid('rx_');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('watch-party.' . $this->roomCode),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ReactionSent';
    }
}
