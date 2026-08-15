// Faiilmov Progressive Web App & Push Notification Service Worker
const CACHE_NAME = 'faiilmov-sw-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Handle native notification click events from device tray/lock screen
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// Handle incoming background push messages
self.addEventListener('push', (event) => {
    if (!event.data) return;

    try {
        const payload = event.data.json();
        const title = payload.title || 'Pemberitahuan Faiilmov';
        const options = {
            body: payload.body || payload.message || 'Pemberitahuan baru di Faiilmov',
            icon: payload.icon || '/favicon.png',
            badge: '/favicon.png',
            tag: payload.tag || ('faiilmov-' + Date.now()),
            renotify: true,
            vibrate: [200, 100, 200],
            data: {
                url: payload.url || '/'
            }
        };

        event.waitUntil(
            self.registration.showNotification(title, options)
        );
    } catch (e) {
        console.error('Error handling push event:', e);
    }
});
