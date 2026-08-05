<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WatchPartyMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $roomCode;
    public string $senderName;
    public string $message;
    public bool $isSystem;
    public string $time;

    public function __construct(string $roomCode, string $senderName, string $message, bool $isSystem = false)
    {
        $this->roomCode = $roomCode;
        $this->senderName = $senderName;
        $this->message = $message;
        $this->isSystem = $isSystem;
        $this->time = date('H:i');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('watch-party.' . $this->roomCode),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
