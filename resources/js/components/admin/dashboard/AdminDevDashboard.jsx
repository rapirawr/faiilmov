import React, { useState, useEffect, useRef, useCallback } from 'react';
import { 
  RefreshCw, 
  Activity, 
  Flame, 
  Users, 
  CheckCircle2, 
  AlertTriangle, 
  Clock,
  Server,
  Workflow,
  Smartphone,
  ShieldAlert,
  Film,
  Tv,
  HardDrive,
  AlertCircle,
  DownloadCloud,
  X
} from 'lucide-react';

import ApiStatusCard from './ApiStatusCard';
import QueueMonitorCard from './QueueMonitorCard';
import ContentPerformanceCard from './ContentPerformanceCard';
import GenreViewsDonutCard from './GenreViewsDonutCard';
import UserPulseCard from './UserPulseCard';
import ActivityFeedCard from './ActivityFeedCard';

export default function AdminDevDashboard({ initialSnapshot, csrfToken, actionUrls = {} }) {
  const [data, setData] = useState(initialSnapshot || null);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isPollingPaused, setIsPollingPaused] = useState(false);
  const [activeTab, setActiveTab] = useState('all'); // 'all', 'health', 'content', 'users'
  const [isSyncingMb, setIsSyncingMb] = useState(false);
  const [isSyncingDracin, setIsSyncingDracin] = useState(false);
  const [syncToast, setSyncToast] = useState(null);

  const pollIntervalMs = 3000;
  const pollTimerRef = useRef(null);

  const fetchSnapshot = useCallback(async (isManual = false) => {
    if (isManual) {
      setIsRefreshing(true);
    }

    try {
      const response = await fetch('/admin/api/dashboard/snapshot', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
      });

      if (response.ok) {
        const json = await response.json();
        setData(json);
      } else {
        console.warn('[Faiilmov] Dashboard snapshot returned non-200:', response.status);
      }
    } catch (err) {
      console.error('[Faiilmov] Dashboard snapshot fetch failed:', err);
    } finally {
      setIsRefreshing(false);
    }
  }, [csrfToken]);

  // Polling Lifecycle & Visibility Handler
  useEffect(() => {
    const handleVisibilityChange = () => {
      if (document.visibilityState === 'hidden') {
        setIsPollingPaused(true);
        if (pollTimerRef.current) {
          clearInterval(pollTimerRef.current);
          pollTimerRef.current = null;
        }
      } else {
        setIsPollingPaused(false);
        fetchSnapshot();
        startPolling();
      }
    };

    const startPolling = () => {
      if (pollTimerRef.current) clearInterval(pollTimerRef.current);
      pollTimerRef.current = setInterval(() => {
        if (document.visibilityState === 'visible') {
          fetchSnapshot();
        }
      }, pollIntervalMs);
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);
    startPolling();

    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      if (pollTimerRef.current) clearInterval(pollTimerRef.current);
    };
  }, [fetchSnapshot]);

  // Derived summaries
  const systemHealth = data?.system_health || {};
  const contentPerformance = data?.content_performance || {};
  const userAnalytics = data?.user_analytics || {};
  const activityFeed = data?.activity_feed || [];
  const meta = data?.meta || {};
  const catalog = contentPerformance?.catalog || {};
  const moderation = userAnalytics?.moderation || {};

  const overallHealth = systemHealth?.overall_status || 'up';
  const downCount = systemHealth?.down_count || 0;
  const degradedCount = systemHealth?.degraded_count || 0;

  const handleSync = async (type) => {
    const isMb = type === 'moviebox';
    const targetUrl = isMb ? actionUrls.syncMoviebox : actionUrls.syncDracin;
    if (!targetUrl) return;

    if (isMb) setIsSyncingMb(true);
    else setIsSyncingDracin(true);

    try {
      const res = await fetch(targetUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
      });

      setSyncToast({
        type: res.ok ? 'success' : 'error',
        message: res.ok 
          ? `Sinkronisasi ${isMb ? 'MovieBox' : 'Dracin'} dijadwalkan.`
          : `Gagal memicu sinkronisasi ${isMb ? 'MovieBox' : 'Dracin'}.`,
      });

      setTimeout(() => fetchSnapshot(true), 1000);
    } catch (e) {
      setSyncToast({
        type: 'error',
        message: `Kendala jaringan: ${e.message}`,
      });
    } finally {
      if (isMb) setIsSyncingMb(false);
      else setIsSyncingDracin(false);

      setTimeout(() => setSyncToast(null), 3500);
    }
  };

  return (
    <div className="space-y-6">
      {/* Toast Notification */}
      {syncToast && (
        <div className={`p-3.5 rounded-xl border flex items-center justify-between shadow-lg text-xs font-medium ${syncToast.type === 'success' ? 'bg-zinc-900 border-emerald-500/40 text-emerald-300' : 'bg-zinc-900 border-rose-500/40 text-rose-300'}`}>
          <div className="flex items-center gap-2">
            {syncToast.type === 'success' ? <CheckCircle2 className="w-4 h-4 text-emerald-400" /> : <AlertTriangle className="w-4 h-4 text-rose-400" />}
            <span>{syncToast.message}</span>
          </div>
          <button onClick={() => setSyncToast(null)} className="p-1 text-zinc-400 hover:text-[#E4E2DD] transition-colors">
            <X className="w-3.5 h-3.5" />
          </button>
        </div>
      )}

      {/* 1. Header Bar */}
      <div className="flex flex-col xl:flex-row xl:items-center justify-between gap-4 p-5 rounded-2xl bg-zinc-900 border border-zinc-800 shadow-sm">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-zinc-800 border border-zinc-700 flex items-center justify-center text-[#E4E2DD] shrink-0">
            <Activity className="w-5 h-5" />
          </div>

          <div>
            <div className="flex items-center gap-2.5 flex-wrap">
              <h2 className="text-base font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">
                Developer Dashboard
              </h2>
              <span className="px-2 py-0.5 rounded-md text-[10px] font-mono font-medium bg-zinc-800 text-zinc-300 border border-zinc-700">
                {isPollingPaused ? 'Paused' : 'Auto-sync 3s'}
              </span>
            </div>
            <p className="text-xs text-zinc-400 font-mono mt-0.5 flex items-center gap-1.5 flex-wrap">
              <span>PHP {systemHealth.server?.php_version || '8.x'}</span>
              <span className="text-zinc-600">/</span>
              <span>Laravel v{systemHealth.server?.laravel_version || '13.x'}</span>
              <span className="text-zinc-600">/</span>
              <span>{meta.server_time || 'Syncing...'}</span>
            </p>
          </div>
        </div>

        {/* Action Buttons & Tab Switcher */}
        <div className="flex flex-wrap items-center gap-2.5">
          {/* Quick Action Triggers */}
          <div className="flex items-center gap-2">
            {actionUrls.importer && (
              <a
                href={actionUrls.importer}
                className="px-3 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 hover:text-amber-200 border border-amber-500/30 text-xs font-bold transition-colors flex items-center gap-2"
              >
                <DownloadCloud className="w-3.5 h-3.5 text-amber-400" />
                <span>Cari & Impor Film</span>
              </a>
            )}

            {actionUrls.reports && (
              <a
                href={actionUrls.reports}
                className={`px-3 py-1.5 rounded-lg border text-xs font-semibold transition-colors flex items-center gap-2 ${(moderation.pending_reports || 0) > 0 ? 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border-rose-500/30' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-300 border-zinc-700'}`}
              >
                <ShieldAlert className={`w-3.5 h-3.5 ${(moderation.pending_reports || 0) > 0 ? 'text-rose-400' : 'text-zinc-400'}`} />
                <span>Laporan {(moderation.pending_reports || 0) > 0 && `(${moderation.pending_reports})`}</span>
              </a>
            )}
          </div>

          {/* Tab Filter */}
          <div className="flex items-center bg-zinc-950 p-1 rounded-xl border border-zinc-800 text-xs font-semibold overflow-x-auto no-scrollbar max-w-full">
            <button
              onClick={() => setActiveTab('all')}
              className={`px-3 py-1.5 rounded-lg transition-colors cursor-pointer shrink-0 ${activeTab === 'all' ? 'bg-zinc-800 text-[#E4E2DD]' : 'text-zinc-400 hover:text-zinc-200'}`}
            >
              Semua
            </button>
            <button
              onClick={() => setActiveTab('health')}
              className={`px-3 py-1.5 rounded-lg transition-colors cursor-pointer shrink-0 ${activeTab === 'health' ? 'bg-zinc-800 text-[#E4E2DD]' : 'text-zinc-400 hover:text-zinc-200'}`}
            >
              API & Queue
            </button>
            <button
              onClick={() => setActiveTab('content')}
              className={`px-3 py-1.5 rounded-lg transition-colors cursor-pointer shrink-0 ${activeTab === 'content' ? 'bg-zinc-800 text-[#E4E2DD]' : 'text-zinc-400 hover:text-zinc-200'}`}
            >
              Konten
            </button>
            <button
              onClick={() => setActiveTab('users')}
              className={`px-3 py-1.5 rounded-lg transition-colors cursor-pointer shrink-0 ${activeTab === 'users' ? 'bg-zinc-800 text-[#E4E2DD]' : 'text-zinc-400 hover:text-zinc-200'}`}
            >
              User & Logs
            </button>
          </div>

          {/* Refresh Button */}
          <button
            onClick={() => fetchSnapshot(true)}
            disabled={isRefreshing}
            className="p-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-[#E4E2DD] border border-zinc-700 transition-colors cursor-pointer disabled:opacity-50"
            title="Refresh data"
          >
            <RefreshCw className={`w-4 h-4 ${isRefreshing ? 'animate-spin' : ''}`} />
          </button>
        </div>
      </div>

      {/* 2. Top 4 KPI Metrics */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* 1. API Health Status */}
        <div className="p-5 rounded-2xl bg-zinc-900 border border-zinc-800 space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Status API Services</span>
            {overallHealth === 'up' ? (
              <span className="flex items-center gap-1 text-[10px] font-mono font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-1.5 py-0.5 rounded">
                <CheckCircle2 className="w-3 h-3" /> OK
              </span>
            ) : overallHealth === 'degraded' ? (
              <span className="flex items-center gap-1 text-[10px] font-mono font-bold text-amber-400 bg-amber-500/10 border border-amber-500/30 px-1.5 py-0.5 rounded">
                <AlertTriangle className="w-3 h-3" /> DEGRADED
              </span>
            ) : (
              <span className="flex items-center gap-1 text-[10px] font-mono font-bold text-rose-400 bg-rose-500/10 border border-rose-500/30 px-1.5 py-0.5 rounded">
                <AlertCircle className="w-3 h-3" /> DOWN
              </span>
            )}
          </div>
          <div>
            <h3 className="text-2xl font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">
              {downCount === 0 ? 'Normal' : `${downCount} Host Down`}
            </h3>
            <p className="text-[11px] text-zinc-400 font-mono mt-1">
              {systemHealth.total_monitored || 12} endpoint termonitor
            </p>
          </div>
        </div>

        {/* 2. DAU & User Growth */}
        <a 
          href={actionUrls.users || '#'}
          className="p-5 rounded-2xl bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition-colors space-y-2 block"
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">DAU & Pengguna</span>
            <Users className="w-4 h-4 text-zinc-400" />
          </div>
          <div>
            <div className="flex items-baseline gap-2">
              <h3 className="text-2xl font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">
                {(userAnalytics.dau || 0).toLocaleString()}
              </h3>
              <span className="text-xs font-semibold text-emerald-400 font-mono">
                +{(userAnalytics.signups_today || 0)} baru
              </span>
            </div>
            <p className="text-[11px] text-zinc-400 font-mono mt-1">
              Total {(userAnalytics.total_users || 0).toLocaleString()} user terdaftar
            </p>
          </div>
        </a>

        {/* 3. Views & Streaming Activity */}
        <a 
          href={actionUrls.catalog || '#'}
          className="p-5 rounded-2xl bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition-colors space-y-2 block"
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Tayangan Hari Ini</span>
            <Film className="w-4 h-4 text-zinc-400" />
          </div>
          <div>
            <div className="flex items-baseline gap-2">
              <h3 className="text-2xl font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">
                {(contentPerformance.total_views_today || 0).toLocaleString()}
              </h3>
              <span className="text-xs font-mono text-zinc-400">
                {contentPerformance.total_watch_time_human || '0m'}
              </span>
            </div>
            <p className="text-[11px] text-zinc-400 font-mono mt-1">
              {(catalog.total_films || 0).toLocaleString()} judul film & series
            </p>
          </div>
        </a>

        {/* 4. Active Nobar & Queue Monitor */}
        <a 
          href={actionUrls.watchParties || '#'}
          className="p-5 rounded-2xl bg-zinc-900 border border-zinc-800 hover:border-zinc-700 transition-colors space-y-2 block"
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Watch Party & Queue</span>
            <Tv className="w-4 h-4 text-zinc-400" />
          </div>
          <div>
            <div className="flex items-baseline gap-2">
              <h3 className="text-2xl font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">
                {(userAnalytics.active_watch_parties || 0).toLocaleString()}
              </h3>
              <span className="text-xs font-mono text-zinc-400">
                {(systemHealth.queue?.pending_count || 0)} queue
              </span>
            </div>
            <p className="text-[11px] text-zinc-400 font-mono mt-1">
              RAM: {systemHealth.server?.memory_used_mb || 0} MB
            </p>
          </div>
        </a>
      </div>

      {/* 3. Balanced Main Deck Layout */}
      {activeTab === 'all' && (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
          {/* Left Column (7 cols): Content Performance + Genre Popularity + User Growth */}
          <div className="lg:col-span-7 space-y-6">
            <ContentPerformanceCard contentData={contentPerformance} />
            <GenreViewsDonutCard genreData={contentPerformance?.top_genres} />
            <UserPulseCard userData={userAnalytics} />
            <ActivityFeedCard activityLogs={activityFeed} />
          </div>

          {/* Right Column (5 cols): API Health + Queue + Catalog Summary */}
          <div className="lg:col-span-5 space-y-6">
            <ApiStatusCard 
              systemHealth={systemHealth} 
              isRefreshing={isRefreshing} 
              csrfToken={csrfToken} 
              onRefresh={() => fetchSnapshot(true)} 
            />
            <QueueMonitorCard queueData={systemHealth.queue} serverData={systemHealth.server} />

            {/* Catalog Composition & Sync Gateway Summary Box */}
            <div className="rounded-2xl bg-zinc-900 border border-zinc-800 p-5 shadow-sm space-y-4">
              <div className="flex items-center justify-between border-b border-zinc-800 pb-3">
                <span className="text-sm font-bold text-[#E4E2DD] font-['Outfit']">Katalog & Metadata</span>
                <span className="text-xs font-mono text-zinc-400">{(catalog.total_films || 0).toLocaleString()} total</span>
              </div>

              {/* Format distribution bars */}
              <div className="space-y-2 text-xs">
                <div>
                  <div className="flex justify-between text-xs mb-1">
                    <span className="text-zinc-300">Movies</span>
                    <span className="font-mono text-zinc-400">{(catalog.total_movies || 0).toLocaleString()}</span>
                  </div>
                  <div className="w-full h-1.5 rounded-full bg-zinc-950 overflow-hidden">
                    <div className="h-full bg-zinc-400 rounded-full" style={{ width: `${Math.min(100, Math.round(((catalog.total_movies || 0) / Math.max(1, catalog.total_films || 1)) * 100))}%` }} />
                  </div>
                </div>

                <div>
                  <div className="flex justify-between text-xs mb-1">
                    <span className="text-zinc-300">TV Series</span>
                    <span className="font-mono text-zinc-400">{(catalog.total_series || 0).toLocaleString()}</span>
                  </div>
                  <div className="w-full h-1.5 rounded-full bg-zinc-950 overflow-hidden">
                    <div className="h-full bg-zinc-500 rounded-full" style={{ width: `${Math.min(100, Math.round(((catalog.total_series || 0) / Math.max(1, catalog.total_films || 1)) * 100))}%` }} />
                  </div>
                </div>

                <div>
                  <div className="flex justify-between text-xs mb-1">
                    <span className="text-zinc-300">Drama Pendek (Dracin)</span>
                    <span className="font-mono text-zinc-400">{(catalog.total_dracin || 0).toLocaleString()}</span>
                  </div>
                  <div className="w-full h-1.5 rounded-full bg-zinc-950 overflow-hidden">
                    <div className="h-full bg-amber-500 rounded-full" style={{ width: `${Math.min(100, Math.round(((catalog.total_dracin || 0) / Math.max(1, catalog.total_films || 1)) * 100))}%` }} />
                  </div>
                </div>
              </div>

              {/* Parental rating mini badges */}
              <div className="pt-2 border-t border-zinc-800 space-y-2">
                <div className="flex items-center justify-between text-xs">
                  <span className="text-zinc-400 font-medium">Klasifikasi Usia</span>
                  {actionUrls.contentRating && (
                    <a href={actionUrls.contentRating} className="text-amber-400 hover:underline text-[11px]">
                      Edit Massal &rarr;
                    </a>
                  )}
                </div>

                <div className="grid grid-cols-6 gap-1.5 text-center">
                  {['SU', 'G', 'PG', '13+', '16+', '18+'].map((r) => {
                    const count = catalog.content_ratings?.[r] || 0;
                    return (
                      <div key={r} className="p-1.5 rounded-lg bg-zinc-950 border border-zinc-800">
                        <span className="block font-mono text-[9px] text-zinc-500 font-bold">{r}</span>
                        <span className="block font-mono text-[11px] text-zinc-200 font-semibold">{count}</span>
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* Sync Gateway info */}
              <div className="pt-2 border-t border-zinc-800 flex items-center justify-between text-xs text-zinc-400">
                <span>Sync Status: <span className="text-zinc-200">{systemHealth.sync_gateway?.last_sync_status || 'Aktif'}</span></span>
                <span className="font-mono text-[10px] text-zinc-500">{systemHealth.sync_gateway?.last_sync_human || 'Baru saja'}</span>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Tab: API & Queue */}
      {activeTab === 'health' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
          <ApiStatusCard 
            systemHealth={systemHealth} 
            isRefreshing={isRefreshing} 
            csrfToken={csrfToken} 
            onRefresh={() => fetchSnapshot(true)} 
          />
          <QueueMonitorCard queueData={systemHealth.queue} serverData={systemHealth.server} />
        </div>
      )}

      {/* Tab: Content */}
      {activeTab === 'content' && (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
          <div className="lg:col-span-8 space-y-6">
            <ContentPerformanceCard contentData={contentPerformance} />
            <GenreViewsDonutCard genreData={contentPerformance?.top_genres} />
          </div>
          <div className="lg:col-span-4 rounded-2xl bg-zinc-900 border border-zinc-800 p-5 space-y-4">
            <h3 className="text-sm font-bold text-[#E4E2DD]">Komposisi & Klasifikasi</h3>
            <div className="space-y-2 text-xs">
              <div className="flex justify-between">
                <span className="text-zinc-400">Total Judul</span>
                <span className="font-mono text-[#E4E2DD]">{(catalog.total_films || 0).toLocaleString()}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-zinc-400">Movies</span>
                <span className="font-mono text-[#E4E2DD]">{(catalog.total_movies || 0).toLocaleString()}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-zinc-400">TV Series</span>
                <span className="font-mono text-[#E4E2DD]">{(catalog.total_series || 0).toLocaleString()}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-zinc-400">Drama Pendek</span>
                <span className="font-mono text-[#E4E2DD]">{(catalog.total_dracin || 0).toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Tab: User & Logs */}
      {activeTab === 'users' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
          <UserPulseCard userData={userAnalytics} />
          <ActivityFeedCard activityLogs={activityFeed} />
        </div>
      )}
    </div>
  );
}
