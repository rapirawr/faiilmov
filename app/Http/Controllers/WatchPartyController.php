<?php

namespace App\Http\Controllers;

use App\Models\Film;
use App\Models\WatchParty;
use App\Models\WatchPartyParticipant;
use Illuminate\Support\Facades\DB;
use App\Models\WatchPartyMessage;
use App\Models\WatchPartyReaction;
use App\Services\MovieBoxService;
use App\Events\WatchPartyPlaybackUpdated;
use App\Events\WatchPartyMessageSent;
use App\Events\WatchPartyReactionSent;
use App\Events\WatchPartyParticipantJoined;
use App\Events\WatchPartyParticipantLeft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class WatchPartyController extends Controller
{
    protected MovieBoxService $movieBox;

    public function __construct(MovieBoxService $movieBox)
    {
        $this->movieBox = $movieBox;
        $this->movieBox->init();
    }

    /**
     * Create a new Watch Party Room
     */
    public function create(Request $request)
    {
        $request->validate([
            'film_id'        => 'required|exists:films,id',
            'season_number'  => 'nullable|integer',
            'episode_number' => 'nullable|integer',
            'guest_name'     => 'nullable|string|max:50',
        ]);

        $film = Film::findOrFail($request->film_id);
        $hostUser = Auth::user();
        $guestName = $request->input('guest_name') ?: ($hostUser ? $hostUser->name : 'Host-' . rand(100, 999));

        $watchParty = WatchParty::create([
            'film_id'                  => $film->id,
            'season_number'            => $request->input('season_number', 1),
            'episode_number'           => $request->input('episode_number', 1),
            'host_user_id'             => $hostUser ? $hostUser->id : null,
            'host_guest_name'          => $guestName,
            'status'                   => 'waiting',
            'current_position_seconds' => 0,
            'is_playing'               => false,
            'is_locked'                => false,
        ]);

        // Register host as first participant
        WatchPartyParticipant::create([
            'watch_party_id' => $watchParty->id,
            'user_id'        => $hostUser ? $hostUser->id : null,
            'guest_name'     => $guestName,
            'session_id'     => session()->getId(),
            'is_host'        => true,
        ]);

        // System message for room creation
        WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'user_id'        => $hostUser ? $hostUser->id : null,
            'sender_name'    => 'System',
            'message'        => "Room Nonton Bareng dibuat oleh {$guestName}",
            'is_system'      => true,
        ]);

        return redirect()->route('watch-party.show', $watchParty->room_code);
    }

    /**
     * Display Watch Party Room
     */
    public function show(Request $request, string $roomCode)
    {
        $watchParty = WatchParty::with(['film', 'participants.user'])
            ->where('room_code', strtoupper($roomCode))
            ->firstOrFail();

        $film = $watchParty->film;
        if ($film->isEpisodic()) {
            $this->syncSeriesStructure($film);
            $film->load('seasons.episodes');
        }
        $sessionId = session()->getId();
        $user = Auth::user();

        // Guest name processing
        $requestedName = trim($request->query('name', ''));
        $defaultGuestName = $user ? $user->name : ($requestedName ?: 'Guest-' . rand(1000, 9999));

        // Find existing participant
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

        // If room is ended
        if ($watchParty->status === 'ended') {
            return response()->view('errors.custom', [
                'message' => 'Room Nonton Bareng ini telah diakhiri oleh Host.'
            ], 403);
        }

        // If room is locked and user is not a participant yet
        if ($watchParty->is_locked && !$participant) {
            return response()->view('errors.custom', [
                'message' => 'Room ini sedang dikunci oleh Host. Anda tidak dapat bergabung saat ini.'
            ], 403);
        }

        $isHost = false;
        if (!$participant) {
            // Check if room has active host
            $activeHostExists = WatchPartyParticipant::where('watch_party_id', $watchParty->id)
                ->where('is_host', true)
                ->whereNull('left_at')
                ->exists();

            $isHost = !$activeHostExists || ($user && $user->id === $watchParty->host_user_id);

            $participant = WatchPartyParticipant::create([
                'watch_party_id' => $watchParty->id,
                'user_id'        => $user ? $user->id : null,
                'guest_name'     => $defaultGuestName,
                'session_id'     => $sessionId,
                'is_host'        => $isHost,
            ]);

            // Save system join message
            WatchPartyMessage::create([
                'watch_party_id' => $watchParty->id,
                'user_id'        => $user ? $user->id : null,
                'sender_name'    => 'System',
                'message'        => "{$participant->display_name} bergabung ke room",
                'is_system'      => true,
            ]);
        } else {
            $isHost = (bool)$participant->is_host;
        }

        // Fetch stream resources
        $resourcesData = [];
        if ($film->moviebox_subject_id) {
            try {
                $resourcesData = $this->movieBox->getResources(
                    $film->moviebox_subject_id,
                    $watchParty->season_number,
                    $watchParty->episode_number,
                    1
                );
            } catch (Exception $e) {}
        }

        $resourceList = $resourcesData['list'] ?? (is_array($resourcesData) ? $resourcesData : []);
        $activeStream = null;

        if (!empty($resourceList)) {
            $h264Item = null;
            foreach ($resourceList as $resItem) {
                $codec = strtolower($resItem['codecName'] ?? '');
                if ($codec === 'h264' || $codec === 'avc') {
                    $h264Item = $resItem;
                    break;
                }
            }
            $selectedItem = $h264Item ?? $resourceList[0];
            $activeStream = $selectedItem['resourceLink'] ?? $selectedItem['url'] ?? $selectedItem['playUrl'] ?? null;
        }

        $proxyActiveStream = $activeStream ? url('/moviebox/proxy-stream') . '?url=' . urlencode($activeStream) : '';

        // Fetch initial saved chat messages from DB
        $initialMessages = WatchPartyMessage::where('watch_party_id', $watchParty->id)
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'isSystem'   => (bool)$m->is_system,
                'senderName' => $m->sender_name,
                'message'    => $m->message,
                'time'       => $m->created_at->format('H:i'),
            ])->toArray();

        // Broadcast participant joined event
        $activeParticipants = $watchParty->participants()->get()->map(fn($p) => [
            'id'       => $p->id,
            'name'     => $p->display_name,
            'is_host'  => (bool)$p->is_host,
            'is_muted' => (bool)$p->is_muted,
        ])->toArray();

        try {
            broadcast(new WatchPartyParticipantJoined(
                $watchParty->room_code,
                $participant->display_name,
                $isHost,
                $activeParticipants
            ))->toOthers();
        } catch (Exception $e) {}

        $subtitles = $film->moviebox_subject_id ? $this->movieBox->getCaptions($film->moviebox_subject_id, $watchParty->season_number, $watchParty->episode_number) : [];
        $audioTracks = $film->moviebox_subject_id ? $this->movieBox->getAudioDubs($film->moviebox_subject_id) : [];

        return view('watch-party', compact(
            'watchParty',
            'film',
            'participant',
            'isHost',
            'resourceList',
            'activeStream',
            'proxyActiveStream',
            'subtitles',
            'audioTracks',
            'activeParticipants',
            'initialMessages'
        ));
    }

    /**
     * Update Playback State (Host Driven)
     */
    public function updatePlayback(Request $request, string $roomCode)
    {
        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))
            ->lockForUpdate()
            ->firstOrFail();
        $sessionId = session()->getId();
        $user = Auth::user();

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

        // Only Host can update playback state
        if (!$participant || !$participant->is_host) {
            return response()->json(['error' => 'Hanya Host yang dapat mengontrol video.'], 403);
        }

        $action = $request->input('action', 'sync');
        $position = (float)$request->input('position', 0);
        $isPlaying = (bool)$request->input('is_playing', false);
        $speed = (float)$request->input('speed', 1.0);
        $serverTime = microtime(true);

        DB::transaction(function () use ($watchParty, $position, $isPlaying, $speed) {
            $watchParty->update([
                'current_position_seconds' => $position,
                'is_playing'               => $isPlaying,
                'playback_speed'           => $speed,
                'status'                   => $isPlaying ? 'playing' : 'waiting',
            ]);
        });

        try {
            broadcast(new \App\Events\PlaybackStateChanged(
                $watchParty->room_code,
                $action,
                $position,
                $serverTime,
                $isPlaying,
                $speed,
                $participant->display_name,
                $watchParty->season_number,
                $watchParty->episode_number
            ))->toOthers();

            broadcast(new WatchPartyPlaybackUpdated(
                $watchParty->room_code,
                $action,
                $position,
                $isPlaying,
                $speed,
                $participant->display_name
            ))->toOthers();
        } catch (\Throwable $e) {}

        return response()->json([
            'status'           => 'ok',
            'action'           => $action,
            'position'         => $position,
            'is_playing'       => $isPlaying,
            'speed'            => $speed,
            'server_timestamp' => $serverTime,
        ]);
    }

    /**
     * Send Real-time Chat Message (Persisted in DB)
     */
    public function sendMessage(Request $request, string $roomCode)
    {
        $request->validate([
            'message'     => 'required|string|max:500',
            'sender_name' => 'required|string|max:50',
        ]);

        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();
        $sessionId = session()->getId();
        $user = Auth::user();

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
            return response()->json(['error' => 'Anda bukan peserta room ini.'], 403);
        }

        if ($participant->is_muted) {
            return response()->json(['error' => 'Anda sedang di-mute oleh Host.'], 403);
        }

        $senderName = $participant->display_name;
        $message = e($request->message);

        $savedMsg = WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'user_id'        => Auth::id(),
            'sender_name'    => $senderName,
            'message'        => $message,
            'is_system'      => false,
        ]);

        try {
            broadcast(new WatchPartyMessageSent(
                $watchParty->room_code,
                $senderName,
                $message,
                false
            ));
        } catch (Exception $e) {}

        return response()->json([
            'status' => 'ok',
            'data'   => [
                'id'         => $savedMsg->id,
                'isSystem'   => false,
                'senderName' => $savedMsg->sender_name,
                'message'    => $savedMsg->message,
                'time'       => $savedMsg->created_at->format('H:i'),
            ]
        ]);
    }

    /**
     * Send Floating Emoji Reaction (Persisted in DB)
     */
    public function sendReaction(Request $request, string $roomCode)
    {
        $request->validate([
            'emoji'       => 'required|string|max:10',
            'sender_name' => 'nullable|string|max:50',
        ]);

        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();
        $user = Auth::user();
        $sessionId = session()->getId();
        
        // Verify participant exists and not kicked
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
            return response()->json(['error' => 'Anda bukan peserta room ini.'], 403);
        }
        
        if ($participant->is_muted) {
            return response()->json(['error' => 'Anda sedang di-mute oleh Host.'], 403);
        }
        
        $emoji = $request->emoji;
        $senderName = $participant->display_name;

        $savedRx = WatchPartyReaction::create([
            'watch_party_id' => $watchParty->id,
            'sender_name'    => $senderName,
            'emoji'          => $emoji,
        ]);

        try {
            broadcast(new WatchPartyReactionSent(
                $watchParty->room_code,
                $emoji,
                $senderName
            ));
        } catch (Exception $e) {}

        return response()->json([
            'status' => 'ok',
            'data'   => [
                'id'         => $savedRx->id,
                'emoji'      => $savedRx->emoji,
                'senderName' => $savedRx->sender_name,
            ]
        ]);
    }

    /**
     * HOST ACTION: Kick Participant
     */
    public function kickParticipant(Request $request, string $roomCode)
    {
        $request->validate(['participant_id' => 'required|exists:watch_party_participants,id']);
        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();

        $this->verifyHostAccess($watchParty);

        $target = WatchPartyParticipant::where('watch_party_id', $watchParty->id)->findOrFail($request->participant_id);
        if ($target->is_host) {
            return response()->json(['error' => 'Tidak dapat mengeluarkan Host.'], 422);
        }

        $target->update(['left_at' => now()]);

        WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'sender_name'    => 'System',
            'message'        => "{$target->display_name} telah dikeluarkan dari room oleh Host.",
            'is_system'      => true,
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * HOST ACTION: Toggle Mute Participant Chat
     */
    public function toggleMuteParticipant(Request $request, string $roomCode)
    {
        $request->validate(['participant_id' => 'required|exists:watch_party_participants,id']);
        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();

        $this->verifyHostAccess($watchParty);

        $target = WatchPartyParticipant::where('watch_party_id', $watchParty->id)->findOrFail($request->participant_id);
        $target->update(['is_muted' => !$target->is_muted]);

        $actionText = $target->is_muted ? 'di-mute' : 'di-unmute';
        WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'sender_name'    => 'System',
            'message'        => "{$target->display_name} telah {$actionText} oleh Host.",
            'is_system'      => true,
        ]);

        return response()->json(['status' => 'ok', 'is_muted' => $target->is_muted]);
    }

    /**
     * HOST ACTION: Transfer Host Role
     */
    public function transferHost(Request $request, string $roomCode)
    {
        $request->validate(['participant_id' => 'required|exists:watch_party_participants,id']);
        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();

        $currentHost = $this->verifyHostAccess($watchParty);

        $newHost = WatchPartyParticipant::where('watch_party_id', $watchParty->id)
            ->whereNull('left_at')
            ->findOrFail($request->participant_id);

        $currentHost->update(['is_host' => false]);
        $newHost->update(['is_host' => true]);

        $watchParty->update([
            'host_user_id'    => $newHost->user_id,
            'host_guest_name' => $newHost->display_name,
        ]);

        WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'sender_name'    => 'System',
            'message'        => "Peran Host telah ditransfer ke {$newHost->display_name}.",
            'is_system'      => true,
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * HOST ACTION: Toggle Room Lock
     */
    public function toggleLock(Request $request, string $roomCode)
    {
        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();
        $this->verifyHostAccess($watchParty);

        $watchParty->update(['is_locked' => !$watchParty->is_locked]);

        $lockText = $watchParty->is_locked ? 'dikunci' : 'dibuka';
        WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'sender_name'    => 'System',
            'message'        => "Room telah {$lockText} oleh Host.",
            'is_system'      => true,
        ]);

        return response()->json(['status' => 'ok', 'is_locked' => $watchParty->is_locked]);
    }

    /**
     * HOST ACTION: End Watch Party Room
     */
    public function endRoom(Request $request, string $roomCode)
    {
        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();
        $this->verifyHostAccess($watchParty);

        $watchParty->update(['status' => 'ended', 'is_playing' => false]);

        WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'sender_name'    => 'System',
            'message'        => "Room Nonton Bareng telah diakhiri oleh Host.",
            'is_system'      => true,
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Get Current Room State (Synchronizes Playback, Messages, Reactions & Host Controls via DB Polling)
     */
    public function syncState(Request $request, string $roomCode)
    {
        $watchParty = WatchParty::with(['film', 'participants.user'])
            ->where('room_code', strtoupper($roomCode))
            ->firstOrFail();

        $film = $watchParty->film;

        $lastMsgId = (int)$request->query('last_msg_id', 0);
        $lastRxId  = (int)$request->query('last_rx_id', 0);

        $sessionId = session()->getId();
        $user = Auth::user();

        // Check if current participant is kicked
        $currentParticipant = WatchPartyParticipant::where('watch_party_id', $watchParty->id)
            ->where(function ($q) use ($user, $sessionId) {
                if ($user) {
                    $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
                } else {
                    $q->where('session_id', $sessionId);
                }
            })
            ->first();

        $isKicked = $currentParticipant ? (!is_null($currentParticipant->left_at)) : false;
        $amIHost = $currentParticipant ? (bool)$currentParticipant->is_host : false;
        $amIMuted = $currentParticipant ? (bool)$currentParticipant->is_muted : false;

        // Active participants
        $activeParticipants = $watchParty->participants()->get()->map(fn($p) => [
            'id'       => $p->id,
            'name'     => $p->display_name,
            'is_host'  => (bool)$p->is_host,
            'is_muted' => (bool)$p->is_muted,
        ])->toArray();

        // New messages from DB
        $newMessages = WatchPartyMessage::where('watch_party_id', $watchParty->id)
            ->where('id', '>', $lastMsgId)
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'isSystem'   => (bool)$m->is_system,
                'senderName' => $m->sender_name,
                'message'    => $m->message,
                'time'       => $m->created_at->format('H:i'),
            ])->toArray();

        // New reactions from DB (created in last 30s)
        $newReactions = WatchPartyReaction::where('watch_party_id', $watchParty->id)
            ->where('id', '>', $lastRxId)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn($r) => [
                'id'         => $r->id,
                'emoji'      => $r->emoji,
                'senderName' => $r->sender_name,
            ])->toArray();

        $latestMsgId = !empty($newMessages) ? end($newMessages)['id'] : $lastMsgId;
        $latestRxId  = !empty($newReactions) ? end($newReactions)['id'] : $lastRxId;

        // Only fetch stream & captions if season or episode changed
        $reqSeason = (int)$request->query('season', 0);
        $reqEpisode = (int)$request->query('episode', 0);
        
        $proxyActiveStream = null;
        $subtitles = null;

        if ($reqSeason === 0 || $reqSeason !== (int)$watchParty->season_number || $reqEpisode !== (int)$watchParty->episode_number) {
            $activeStream = null;
            if ($film && $film->moviebox_subject_id) {
                try {
                    $resourcesData = $this->movieBox->getResources(
                        $film->moviebox_subject_id,
                        $watchParty->season_number,
                        $watchParty->episode_number,
                        1
                    );
                    $resourceList = $resourcesData['list'] ?? (is_array($resourcesData) ? $resourcesData : []);
                    if (!empty($resourceList)) {
                        $h264Item = null;
                        foreach ($resourceList as $resItem) {
                            $codec = strtolower($resItem['codecName'] ?? '');
                            if ($codec === 'h264' || $codec === 'avc') {
                                $h264Item = $resItem;
                                break;
                            }
                        }
                        $selectedItem = $h264Item ?? $resourceList[0];
                        $activeStream = $selectedItem['resourceLink'] ?? $selectedItem['url'] ?? $selectedItem['playUrl'] ?? null;
                    }
                } catch (Exception $e) {}
            }
            $subtitles = ($film && $film->moviebox_subject_id) ? $this->movieBox->getCaptions($film->moviebox_subject_id, $watchParty->season_number, $watchParty->episode_number) : [];
            $proxyActiveStream = $activeStream ? url('/moviebox/proxy-stream') . '?url=' . urlencode($activeStream) . '&id=' . urlencode($film->moviebox_subject_id) . '&se=' . $watchParty->season_number . '&ep=' . $watchParty->episode_number : '';
        }

        $calcPosition = (float)$watchParty->current_position_seconds;
        if ($watchParty->is_playing && $watchParty->updated_at) {
            $elapsedSeconds = max(0, time() - $watchParty->updated_at->timestamp);
            $calcPosition += $elapsedSeconds * (float)($watchParty->playback_speed ?: 1.0);
        }

        return response()->json([
            'room_code'           => $watchParty->room_code,
            'current_action'      => $watchParty->is_playing ? 'play' : 'pause',
            'position'            => $calcPosition,
            'is_playing'          => (bool)$watchParty->is_playing,
            'speed'               => (float)$watchParty->playback_speed,
            'server_timestamp'    => microtime(true),
            'last_updated_at'     => $watchParty->updated_at ? $watchParty->updated_at->toIso8601String() : null,
            'status'              => $watchParty->status,
            'is_locked'           => (bool)$watchParty->is_locked,
            'season_number'       => (int)$watchParty->season_number,
            'episode_number'      => (int)$watchParty->episode_number,
            'proxy_active_stream' => $proxyActiveStream,
            'subtitles'           => $subtitles,
            'is_kicked'           => $isKicked,
            'am_i_host'           => $amIHost,
            'am_i_muted'          => $amIMuted,
            'participants'        => $activeParticipants,
            'new_messages'        => $newMessages,
            'new_reactions'       => $newReactions,
            'latest_msg_id'       => $latestMsgId,
            'latest_rx_id'        => $latestRxId,
        ]);
    }

    /**
     * Helper to verify host privileges for current request
     */
    protected function verifyHostAccess(WatchParty $watchParty): WatchPartyParticipant
    {
        $sessionId = session()->getId();
        $user = Auth::user();

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

        if (!$participant || !$participant->is_host) {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh Host.');
        }

        return $participant;
    }

    /**
     * HOST ACTION: Switch Season / Episode
     */
    public function switchEpisode(Request $request, string $roomCode)
    {
        $request->validate([
            'season_number'  => 'required|integer|min:1',
            'episode_number' => 'required|integer|min:1',
        ]);

        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();
        $currentHost = $this->verifyHostAccess($watchParty);

        $seasonNum = (int)$request->season_number;
        $epNum     = (int)$request->episode_number;

        $watchParty->update([
            'season_number'            => $seasonNum,
            'episode_number'           => $epNum,
            'current_position_seconds' => 0,
            'is_playing'               => true,
        ]);

        $film = $watchParty->film;
        $activeStream = null;
        if ($film->moviebox_subject_id) {
            try {
                $resourcesData = $this->movieBox->getResources(
                    $film->moviebox_subject_id,
                    $seasonNum,
                    $epNum,
                    1
                );
                $resourceList = $resourcesData['list'] ?? (is_array($resourcesData) ? $resourcesData : []);
                if (!empty($resourceList)) {
                    $h264Item = null;
                    foreach ($resourceList as $resItem) {
                        $codec = strtolower($resItem['codecName'] ?? '');
                        if ($codec === 'h264' || $codec === 'avc') {
                            $h264Item = $resItem;
                            break;
                        }
                    }
                    $selectedItem = $h264Item ?? $resourceList[0];
                    $activeStream = $selectedItem['resourceLink'] ?? $selectedItem['url'] ?? $selectedItem['playUrl'] ?? null;
                }
            } catch (Exception $e) {}
        }

        $proxyActiveStream = $activeStream ? url('/moviebox/proxy-stream') . '?url=' . urlencode($activeStream) : '';

        WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'sender_name'    => 'System',
            'message'        => "Host mengganti tayangan ke Season {$seasonNum} Episode {$epNum}",
            'is_system'      => true,
        ]);

        try {
            broadcast(new WatchPartyPlaybackUpdated(
                $watchParty->room_code,
                'switch_episode',
                0,
                true,
                1.0,
                $currentHost->display_name
            ))->toOthers();
        } catch (Exception $e) {}

        $subtitles = $film->moviebox_subject_id ? $this->movieBox->getCaptions($film->moviebox_subject_id, $seasonNum, $epNum) : [];

        return response()->json([
            'status'              => 'ok',
            'season_number'       => $seasonNum,
            'episode_number'      => $epNum,
            'proxy_active_stream' => $proxyActiveStream,
            'subtitles'           => $subtitles,
        ]);
    }

    /**
     * Update participant's nickname (for guests and logged in users)
     */
    public function updateNickname(Request $request, string $roomCode)
    {
        $request->validate([
            'nickname' => 'required|string|max:50',
        ]);

        $watchParty = WatchParty::where('room_code', strtoupper($roomCode))->firstOrFail();
        $user = Auth::user();
        $sessionId = session()->getId();
        $newNickname = trim(e($request->nickname));

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
            return response()->json(['error' => 'Peserta tidak ditemukan.'], 404);
        }

        $oldName = $participant->display_name;
        $participant->update([
            'guest_name' => $newNickname,
        ]);

        if ($participant->is_host) {
            $watchParty->update(['host_guest_name' => $newNickname]);
        }

        $systemMsg = WatchPartyMessage::create([
            'watch_party_id' => $watchParty->id,
            'user_id'        => $user ? $user->id : null,
            'sender_name'    => 'System',
            'message'        => "{$oldName} mengubah nama menjadi {$newNickname}",
            'is_system'      => true,
        ]);

        try {
            broadcast(new WatchPartyMessageSent(
                $watchParty->room_code,
                'System',
                "{$oldName} mengubah nama menjadi {$newNickname}",
                true
            ));

            $activeParticipants = $watchParty->participants()->whereNull('left_at')->get()->map(fn($p) => [
                'id'          => $p->id,
                'userId'      => $p->user_id,
                'displayName' => $p->display_name,
                'isHost'      => (bool)$p->is_host,
                'isMuted'     => (bool)$p->is_muted,
            ])->toArray();

            broadcast(new WatchPartyParticipantJoined(
                $watchParty->room_code,
                $newNickname,
                (bool)$participant->is_host,
                $activeParticipants
            ));
        } catch (\Throwable $e) {}

        return response()->json([
            'status'     => 'ok',
            'nickname'   => $newNickname,
            'systemMsg'  => [
                'id'         => $systemMsg->id,
                'isSystem'   => true,
                'senderName' => 'System',
                'message'    => $systemMsg->message,
                'time'       => $systemMsg->created_at->format('H:i'),
            ]
        ]);
    }

    /**
     * Helper to sync seasons & episodes structure from MovieBox API for TV series
     */
    protected function syncSeriesStructure(Film $film): void
    {
        if (!$film->isEpisodic() || !$film->moviebox_subject_id) return;

        try {
            $details = $this->movieBox->getDetails($film->moviebox_subject_id);
            $seasonsData = $details['seasons']['seasons'] ?? [];

            if (empty($seasonsData)) {
                $seasonInfo = $this->movieBox->get('/wefeed-mobile-bff/subject-api/season-info?subjectId=' . $film->moviebox_subject_id);
                $seasonsData = $seasonInfo['seasons'] ?? [];
            }

            foreach ($seasonsData as $sData) {
                $seNum = (int)($sData['se'] ?? 1);
                $maxEp = (int)($sData['maxEp'] ?? 1);

                $season = \App\Models\Season::firstOrCreate(
                    ['film_id' => $film->id, 'season_number' => $seNum],
                    ['title' => "Season {$seNum}", 'poster_url' => $film->poster_url, 'release_year' => $film->release_year]
                );

                // Fetch real episode durations from MovieBox resources API
                $epDurations = [];
                try {
                    $resData = $this->movieBox->getResources($film->moviebox_subject_id, $seNum, 0);
                    $resList = $resData['list'] ?? [];
                    foreach ($resList as $rItem) {
                        $eNum = (int)($rItem['ep'] ?? 0);
                        $dSec = (int)($rItem['duration'] ?? 0);
                        if ($eNum > 0 && $dSec > 0) {
                            $epDurations[$eNum] = (int)round($dSec / 60);
                        }
                    }
                } catch (Exception $e) {}

                for ($epNum = 1; $epNum <= $maxEp; $epNum++) {
                    $realDuration = $epDurations[$epNum] ?? ($film->duration_minutes > 0 && $film->duration_minutes != 120 ? $film->duration_minutes : 45);

                    $episode = \App\Models\Episode::firstOrCreate(
                        ['season_id' => $season->id, 'episode_number' => $epNum],
                        [
                            'title'            => "Episode {$epNum}",
                            'synopsis'         => "Episode {$epNum} of Season {$seNum}",
                            'duration_minutes' => $realDuration,
                            'thumbnail_url'    => $film->backdrop_url ?: $film->poster_url,
                        ]
                    );

                    if (isset($epDurations[$epNum]) && $episode->duration_minutes != $epDurations[$epNum]) {
                        $episode->update(['duration_minutes' => $epDurations[$epNum]]);
                    }
                }
            }
        } catch (Exception $e) {}
    }
}
