<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlaybackStateChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $roomCode;
    public string $action; // play, pause, seek, playback_rate_change, episode_change, heartbeat
    public float $timestampVideo;
    public float $serverTimestamp;
    public bool $isPlaying;
    public float $playbackRate;
    public string $senderName;
    public ?int $seasonNumber;
    public ?int $episodeNumber;

    public function __construct(
        string $roomCode,
        string $action,
        float $timestampVideo,
        float $serverTimestamp,
        bool $isPlaying,
        float $playbackRate = 1.0,
        string $senderName = 'Host',
        ?int $seasonNumber = null,
        ?int $episodeNumber = null
    ) {
        $this->roomCode = $roomCode;
        $this->action = $action;
        $this->timestampVideo = $timestampVideo;
        $this->serverTimestamp = $serverTimestamp;
        $this->isPlaying = $isPlaying;
        $this->playbackRate = $playbackRate;
        $this->senderName = $senderName;
        $this->seasonNumber = $seasonNumber;
        $this->episodeNumber = $episodeNumber;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('watch-party.' . $this->roomCode),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PlaybackStateChanged';
    }
}
