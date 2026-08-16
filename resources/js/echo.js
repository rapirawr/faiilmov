/**
 * Laravel Echo + Pusher Setup for Faiilmov
 *
 * This module initializes real-time WebSocket connectivity for Watch Party.
 * Graceful degradation: if Pusher is not configured or connection fails,
 * the Watch Party will fall back to DB polling (existing behavior).
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher globally available (required by Echo)
window.Pusher = Pusher;

/**
 * Initialize Laravel Echo with Pusher driver.
 * Called explicitly | not auto-executed | so the app can decide when to connect.
 *
 * @returns {Echo|null} Echo instance, or null if Pusher is not configured.
 */
export function initEcho() {
    const appKey     = window.PUSHER_CONFIG?.key || import.meta.env.VITE_PUSHER_APP_KEY || '84a6e3fa24e4374c43b5';
    const appCluster = window.PUSHER_CONFIG?.cluster || import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap1';

    // If Pusher credentials are not set, skip WebSocket initialization
    if (!appKey) {
        console.warn('[Faiilmov Echo] Pusher not configured. Watch Party will use DB polling fallback.');
        return null;
    }

    try {
        const echo = new Echo({
            broadcaster: 'pusher',
            key:         appKey,
            cluster:     appCluster,
            forceTLS:    true,

            // Keep-alive / connection health
            activityTimeout:     30000, // 30s
            pongTimeout:         15000, // 15s

            // Reconnect behavior (graceful degradation on failure)
            enabledTransports: ['ws', 'wss'],
        });

        // Connection event hooks for debugging/monitoring
        echo.connector.pusher.connection.bind('connected', () => {
            if (import.meta.env.DEV) {
                console.info('[Faiilmov Echo] Connected to Pusher WebSocket.');
            }
            // Dispatch event so Watch Party UI can know WS is live
            window.dispatchEvent(new CustomEvent('faiilmov:ws-connected'));
        });

        echo.connector.pusher.connection.bind('disconnected', () => {
            if (import.meta.env.DEV) {
                console.warn('[Faiilmov Echo] Disconnected. Watch Party will resume polling fallback.');
            }
            window.dispatchEvent(new CustomEvent('faiilmov:ws-disconnected'));
        });

        echo.connector.pusher.connection.bind('failed', () => {
            console.error('[Faiilmov Echo] WebSocket connection failed. Staying on polling.');
            window.dispatchEvent(new CustomEvent('faiilmov:ws-failed'));
        });

        // Expose globally so Blade/Alpine.js components can access it
        window.Echo = echo;

        return echo;
    } catch (err) {
        console.error('[Faiilmov Echo] Failed to initialize:', err);
        return null;
    }
}

/**
 * Subscribe to a Watch Party room channel.
 *
 * @param {Echo} echo    - Echo instance from initEcho()
 * @param {string} roomCode  - 6-character room code
 * @param {object} handlers  - Event handler callbacks
 * @param {Function} handlers.onPlaybackUpdate   - PlaybackStateChanged / WatchPartyPlaybackUpdated
 * @param {Function} handlers.onMessageReceived  - WatchPartyMessageSent
 * @param {Function} handlers.onReactionReceived - WatchPartyReactionSent
 * @param {Function} handlers.onParticipantJoin  - WatchPartyParticipantJoined
 * @param {Function} handlers.onParticipantLeave - WatchPartyParticipantLeft
 * @returns {object|null} Echo channel instance
 */
export function subscribeToWatchParty(echo, roomCode, handlers = {}) {
    if (!echo || !roomCode) return null;

    const channel = echo.channel(`watch-party.${roomCode}`);

    if (handlers.onPlaybackUpdate) {
        channel.listen('.WatchPartyPlaybackUpdated', handlers.onPlaybackUpdate);
        channel.listen('.PlaybackStateChanged',      handlers.onPlaybackUpdate);
    }

    if (handlers.onMessageReceived) {
        channel.listen('.WatchPartyMessageSent', handlers.onMessageReceived);
    }

    if (handlers.onReactionReceived) {
        channel.listen('.WatchPartyReactionSent', handlers.onReactionReceived);
    }

    if (handlers.onParticipantJoin) {
        channel.listen('.WatchPartyParticipantJoined', handlers.onParticipantJoin);
    }

    if (handlers.onParticipantLeave) {
        channel.listen('.WatchPartyParticipantLeft', handlers.onParticipantLeave);
    }

    return channel;
}

/**
 * Unsubscribe from a Watch Party channel (cleanup on component unmount / room leave).
 *
 * @param {Echo} echo
 * @param {string} roomCode
 */
export function unsubscribeFromWatchParty(echo, roomCode) {
    if (!echo || !roomCode) return;
    echo.leave(`watch-party.${roomCode}`);
}
