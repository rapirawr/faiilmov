import React from 'react';
import { 
  TrendingUp, 
  Eye, 
  Clock, 
  Film
} from 'lucide-react';

export default function ContentPerformanceCard({ contentData }) {
  const topFilms = contentData?.top_films || [];
  const totalViews = contentData?.total_views_today || 0;
  const totalWatchTime = contentData?.total_watch_time_human || '0m';
  const viewsTrend = contentData?.views_trend_7d || [];

  const maxViews = Math.max(...topFilms.map((f) => f.views || 0), 1);
  const maxTrendViews = Math.max(...viewsTrend.map((t) => t.views || 0), 1);

  const getSubjectTypeBadge = (stype) => {
    switch (stype?.toLowerCase()) {
      case 'dracin':
        return { label: 'DRACIN', bg: 'bg-zinc-800 text-emerald-400 border-zinc-700' };
      case 'series':
        return { label: 'SERIES', bg: 'bg-zinc-800 text-sky-400 border-zinc-700' };
      case 'movie':
      default:
        return { label: 'MOVIE', bg: 'bg-zinc-800 text-amber-400 border-zinc-700' };
    }
  };

  return (
    <div className="rounded-2xl bg-zinc-900 border border-zinc-800 p-5 shadow-sm space-y-4">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-800 pb-4">
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-xl bg-zinc-800 border border-zinc-700 text-zinc-300">
            <Film className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">Performa Konten Hari Ini</h3>
            <p className="text-xs text-zinc-400">Top film & dracin dengan views & watch-time</p>
          </div>
        </div>

        {/* Total Views & Watch Time Pill */}
        <div className="flex items-center gap-2">
          <div className="px-3 py-1 rounded-lg bg-zinc-950 border border-zinc-800 flex items-center gap-2">
            <Eye className="w-3.5 h-3.5 text-zinc-400" />
            <span className="text-xs font-mono font-bold text-[#E4E2DD]">{totalViews.toLocaleString()}</span>
            <span className="text-[10px] text-zinc-400">views</span>
          </div>

          <div className="px-3 py-1 rounded-lg bg-zinc-950 border border-zinc-800 flex items-center gap-2">
            <Clock className="w-3.5 h-3.5 text-zinc-400" />
            <span className="text-xs font-mono font-bold text-[#E4E2DD]">{totalWatchTime}</span>
            <span className="text-[10px] text-zinc-400">watch time</span>
          </div>
        </div>
      </div>

      {/* 7-Day Views Mini Trend Bar Chart */}
      <div className="p-3.5 rounded-xl bg-zinc-950 border border-zinc-800 space-y-2">
        <div className="flex items-center justify-between text-xs">
          <span className="text-zinc-400 font-medium flex items-center gap-1.5">
            <TrendingUp className="w-3.5 h-3.5 text-zinc-400" />
            Tren Views 7 Hari
          </span>
          <span className="text-[10px] text-zinc-400 font-mono">Rollup Harian</span>
        </div>

        <div className="grid grid-cols-7 gap-2 items-end h-16 pt-2">
          {viewsTrend.map((t, idx) => {
            const barHeight = Math.max(8, Math.round((t.views / maxTrendViews) * 100));
            const isToday = idx === viewsTrend.length - 1;
            return (
              <div key={t.date} className="flex flex-col items-center gap-1 group relative">
                <div className="w-full bg-zinc-900 rounded-t h-12 flex items-end overflow-hidden">
                  <div 
                    className={`w-full ${isToday ? 'bg-amber-400' : 'bg-zinc-700 group-hover:bg-zinc-600'}`}
                    style={{ height: `${barHeight}%` }}
                  />
                </div>
                <span className={`text-[10px] font-mono ${isToday ? 'text-amber-400 font-bold' : 'text-zinc-400'}`}>
                  {t.short_day}
                </span>

                {/* Tooltip on hover */}
                <div className="absolute bottom-full mb-1 hidden group-hover:flex flex-col items-center bg-zinc-900 text-[10px] text-[#E4E2DD] px-2 py-1 rounded shadow-lg border border-zinc-700 z-10 whitespace-nowrap pointer-events-none">
                  <span className="font-bold">{t.views.toLocaleString()} views</span>
                  <span className="text-zinc-400">{t.watch_time_hours} jam</span>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Top Content Ranked List */}
      <div className="space-y-1.5 max-h-[360px] overflow-y-auto pr-1 admin-scrollbar">
        {topFilms.length === 0 ? (
          <div className="p-8 text-center text-zinc-500 text-xs">
            Belum ada data tontonan tercatat hari ini.
          </div>
        ) : (
          topFilms.map((film, index) => {
            const stypeBadge = getSubjectTypeBadge(film.subject_type);
            const percentage = Math.max(5, Math.round((film.views / maxViews) * 100));

            return (
              <div 
                key={film.film_id || index}
                className="p-2.5 rounded-xl bg-zinc-950 hover:bg-zinc-900/60 border border-zinc-800 transition-colors flex items-center justify-between gap-3 group"
              >
                {/* Rank & Poster & Title */}
                <div className="flex items-center gap-2.5 min-w-0 flex-1">
                  <span className="w-5 h-5 rounded flex items-center justify-center text-[11px] font-mono font-bold text-zinc-400 bg-zinc-900 border border-zinc-800 shrink-0">
                    {film.rank || index + 1}
                  </span>

                  <img 
                    src={film.poster_url} 
                    alt={film.title} 
                    className="w-8 h-11 object-cover rounded bg-zinc-900 border border-zinc-800 shrink-0"
                    loading="lazy"
                    onError={(e) => { e.currentTarget.src = '/images/placeholder.jpg'; }}
                  />

                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-1.5">
                      <span className={`px-1.5 py-0.2 rounded text-[9px] font-bold border ${stypeBadge.bg}`}>
                        {stypeBadge.label}
                      </span>
                      <h4 className="text-xs font-semibold text-[#E4E2DD] truncate group-hover:text-amber-400 transition-colors">
                        {film.title}
                      </h4>
                    </div>

                    <div className="w-full bg-zinc-800 h-1 rounded-full overflow-hidden mt-1.5">
                      <div 
                        className="bg-zinc-400 h-full rounded-full" 
                        style={{ width: `${percentage}%` }}
                      />
                    </div>
                  </div>
                </div>

                {/* Metrics */}
                <div className="flex items-center gap-3 shrink-0 text-right">
                  <div>
                    <div className="flex items-center justify-end gap-1 text-xs font-mono font-bold text-[#E4E2DD]">
                      <Eye className="w-3 h-3 text-zinc-500" />
                      <span>{film.views.toLocaleString()}</span>
                    </div>
                    <span className="text-[10px] text-zinc-400 font-mono">
                      {film.unique_viewers} unik
                    </span>
                  </div>

                  <div className="hidden xs:block min-w-[60px] font-mono">
                    <div className="flex items-center justify-end gap-1 text-xs font-semibold text-zinc-300">
                      <Clock className="w-3 h-3 text-zinc-500" />
                      <span>{film.watch_time_formatted || '0m'}</span>
                    </div>
                    {film.completion_rate && (
                      <span className="text-[10px] text-zinc-400">
                        {film.completion_rate}% cr
                      </span>
                    )}
                  </div>
                </div>
              </div>
            );
          })
        )}
      </div>
    </div>
  );
}
