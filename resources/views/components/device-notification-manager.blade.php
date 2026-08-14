<!-- Device Push & In-App Notification Manager Component -->
<div x-data="deviceNotificationManager()" x-init="init()" class="relative z-[9999]">

    <!-- Floating In-App Push Notification Card (Head-Up Display) -->
    <div class="fixed top-20 sm:top-24 right-4 sm:right-6 z-[9999] max-w-sm w-full pointer-events-none space-y-3">
        <template x-for="notif in activeFloatingNotifications" :key="notif.id">
            <div x-show="notif.visible"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                 class="pointer-events-auto p-4 rounded-2xl bg-zinc-900/95 border border-amber-500/30 text-white shadow-2xl backdrop-blur-2xl space-y-3">
                
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-inner mt-0.5"
                             :class="{
                                 'bg-amber-500/20 text-amber-400 border border-amber-500/30': notif.type === 'new_film',
                                 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30': notif.type === 'watch_party',
                                 'bg-rose-500/20 text-rose-400 border border-rose-500/30': notif.type === 'maintenance',
                                 'bg-sky-500/20 text-sky-400 border border-sky-500/30': notif.type !== 'new_film' && notif.type !== 'watch_party' && notif.type !== 'maintenance'
                             }">
                            <template x-if="notif.type === 'new_film'">
                                <i data-lucide="film" class="w-4 h-4"></i>
                            </template>
                            <template x-if="notif.type === 'watch_party'">
                                <i data-lucide="tv" class="w-4 h-4"></i>
                            </template>
                            <template x-if="notif.type === 'maintenance'">
                                <i data-lucide="wrench" class="w-4 h-4"></i>
                            </template>
                            <template x-if="notif.type !== 'new_film' && notif.type !== 'watch_party' && notif.type !== 'maintenance'">
                                <i data-lucide="bell" class="w-4 h-4"></i>
                            </template>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-mono font-bold uppercase px-1.5 py-0.5 rounded bg-zinc-800 text-amber-300 border border-zinc-700">Pemberitahuan</span>
                                <span class="text-[10px] text-zinc-500 font-mono" x-text="notif.time_ago || 'Baru Saja'"></span>
                            </div>
                            <h4 class="text-xs font-bold text-white font-['Outfit'] mt-1 truncate" x-text="notif.title || 'Pemberitahuan Faiilmov'"></h4>
                            <p class="text-[11px] text-zinc-300 line-clamp-2 mt-0.5 leading-relaxed" x-text="notif.body || notif.message"></p>
                        </div>
                    </div>

                    <button type="button" @click="dismissFloating(notif.id)" class="text-zinc-500 hover:text-white p-1 transition-colors cursor-pointer shrink-0">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>

                <!-- Action Button -->
                <div class="flex items-center justify-end gap-2 pt-1 border-t border-zinc-800/80">
                    <template x-if="notif.url">
                        <a :href="notif.url" 
                           @click="dismissFloating(notif.id)"
                           class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs transition-colors flex items-center gap-1.5 shadow-md shadow-amber-500/20">
                            <span>Buka Sekarang</span>
                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    </template>
                    <template x-if="!notif.url">
                        <button type="button" @click="dismissFloating(notif.id)" class="px-3 py-1 rounded-xl bg-zinc-800 text-zinc-300 text-xs font-medium hover:text-white">
                            Tutup
                        </button>
                    </template>
                </div>

            </div>
        </template>
    </div>

    <!-- Soft Device Push Permission Banner (Shows once if permission is default) -->
    <div x-show="showPermissionBanner" 
         x-cloak
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-6"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-6"
         class="fixed bottom-6 left-4 sm:left-6 z-50 max-w-sm p-4 rounded-2xl bg-zinc-900/95 border border-zinc-700/80 shadow-2xl backdrop-blur-xl text-white space-y-3">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                <i data-lucide="bell-ring" class="w-4 h-4"></i>
            </div>
            <div class="space-y-1">
                <h5 class="text-xs font-bold text-white font-['Outfit']">Aktifkan Notifikasi Perangkat</h5>
                <p class="text-[11px] text-zinc-400 leading-relaxed">Dapatkan info instan saat film baru dirilis atau sesi nobar dimulai langsung di layar HP/PC Anda.</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-1 border-t border-zinc-800">
            <button type="button" @click="dismissPermissionBanner()" class="px-3 py-1.5 rounded-xl text-xs text-zinc-400 hover:text-white transition-colors cursor-pointer font-medium">Nanti Saja</button>
            <button type="button" @click="requestDevicePermission()" class="px-4 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 text-xs font-bold transition-all shadow-md shadow-amber-500/20 flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                <span>Izinkan Notifikasi</span>
            </button>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('deviceNotificationManager', () => ({
        permissionStatus: 'default',
        showPermissionBanner: false,
        activeFloatingNotifications: [],
        swRegistration: null,
        pollingInterval: null,

        init() {
            this.initServiceWorker();
            this.checkPermissionStatus();
            this.startNotificationPoller();

            // Re-check when user focuses or tab becomes visible
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.fetchAndProcessNotifications();
                }
            });
        },

        async initServiceWorker() {
            if ('serviceWorker' in navigator) {
                try {
                    this.swRegistration = await navigator.serviceWorker.register('/sw.js');
                } catch (e) {
                    console.debug('ServiceWorker registration not supported/failed:', e);
                }
            }
        },

        checkPermissionStatus() {
            if ('Notification' in window) {
                this.permissionStatus = Notification.permission;
                const dismissed = localStorage.getItem('faiilmov_notif_prompt_dismissed');
                if (this.permissionStatus === 'default' && !dismissed) {
                    // Show soft banner after 5 seconds of initial user browsing
                    setTimeout(() => {
                        this.showPermissionBanner = true;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    }, 5000);
                }
            }
        },

        dismissPermissionBanner() {
            this.showPermissionBanner = false;
            localStorage.setItem('faiilmov_notif_prompt_dismissed', 'true');
        },

        async requestDevicePermission() {
            if (!('Notification' in window)) {
                alert('Browser Anda tidak mendukung Web Notification API.');
                return;
            }

            try {
                const permission = await Notification.requestPermission();
                this.permissionStatus = permission;
                this.showPermissionBanner = false;
                localStorage.setItem('faiilmov_notif_prompt_dismissed', 'true');

                if (permission === 'granted') {
                    this.pushNativeNotification({
                        title: '🎉 Notifikasi Perangkat Aktif!',
                        body: 'Anda akan menerima pemberitahuan film terbaru dan update sistem secara langsung.',
                        url: '/'
                    });
                }
            } catch (e) {
                console.error('Error requesting permission:', e);
            }
        },

        startNotificationPoller() {
            // Initial fetch
            this.fetchAndProcessNotifications();

            // Poll every 20 seconds for instant broadcast reception
            this.pollingInterval = setInterval(() => {
                this.fetchAndProcessNotifications();
            }, 20000);
        },

        async fetchAndProcessNotifications() {
            try {
                const res = await fetch('{{ route('notifications.recent') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) return;
                const data = await res.json();
                const items = data.notifications || [];

                if (items.length === 0) return;

                // Load seen notification IDs from localStorage
                let seenIds = [];
                try {
                    const raw = localStorage.getItem('faiilmov_seen_notif_ids');
                    seenIds = raw ? JSON.parse(raw) : [];
                } catch (e) {
                    seenIds = [];
                }

                // If first time visit on device, initialize with current notifications so we don't spam historical alerts
                if (!localStorage.getItem('faiilmov_seen_notif_initialized')) {
                    const initialIds = items.map(n => n.id);
                    localStorage.setItem('faiilmov_seen_notif_ids', JSON.stringify(initialIds));
                    localStorage.setItem('faiilmov_seen_notif_initialized', 'true');
                    return;
                }

                // Find fresh notifications that this device has not seen yet
                const freshNotifs = items.filter(n => !seenIds.includes(n.id) && !n.is_read);

                if (freshNotifs.length > 0) {
                    freshNotifs.forEach(notif => {
                        // 1. Trigger Native OS / Device Push Notification
                        this.pushNativeNotification(notif);

                        // 2. Trigger In-App Floating Card
                        this.showFloatingNotification(notif);

                        // 3. Mobile Haptic Vibration
                        if ('vibrate' in navigator) {
                            navigator.vibrate([150, 50, 150]);
                        }

                        // Mark as seen
                        seenIds.push(notif.id);
                    });

                    // Keep seenIds bounded to last 100 entries
                    if (seenIds.length > 100) seenIds = seenIds.slice(-100);
                    localStorage.setItem('faiilmov_seen_notif_ids', JSON.stringify(seenIds));

                    // Dispatch global event for navbar update
                    window.dispatchEvent(new CustomEvent('faiilmov:notification-received', {
                        detail: { count: data.unread_count, notifications: items }
                    }));
                }

            } catch (e) {
                // Silently handle network errors
            }
        },

        pushNativeNotification(notif) {
            if (!('Notification' in window) || Notification.permission !== 'granted') {
                return;
            }

            const title = notif.title || 'Pemberitahuan Faiilmov';
            const body = notif.body || notif.message || '';
            const targetUrl = notif.url || '/';

            if (this.swRegistration && 'showNotification' in this.swRegistration) {
                this.swRegistration.showNotification(title, {
                    body: body,
                    icon: '/favicon.png',
                    badge: '/favicon.png',
                    tag: 'faiilmov-' + (notif.id || Date.now()),
                    renotify: true,
                    data: { url: targetUrl }
                });
            } else {
                const n = new Notification(title, {
                    body: body,
                    icon: '/favicon.png',
                    data: { url: targetUrl }
                });
                n.onclick = function() {
                    window.focus();
                    if (targetUrl) window.location.href = targetUrl;
                    n.close();
                };
            }
        },

        showFloatingNotification(notif) {
            const notifObj = {
                id: notif.id || Date.now(),
                type: notif.type || 'announcement',
                title: notif.title || 'Pemberitahuan Faiilmov',
                body: notif.body || notif.message || '',
                url: notif.url || null,
                time_ago: notif.time_ago || 'Baru Saja',
                visible: true
            };

            this.activeFloatingNotifications.unshift(notifObj);
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });

            // Auto dismiss floating after 9 seconds
            setTimeout(() => {
                this.dismissFloating(notifObj.id);
            }, 9000);
        },

        dismissFloating(id) {
            const found = this.activeFloatingNotifications.find(n => n.id === id);
            if (found) {
                found.visible = false;
                setTimeout(() => {
                    this.activeFloatingNotifications = this.activeFloatingNotifications.filter(n => n.id !== id);
                }, 300);
            }
        }
    }));
});
</script>
