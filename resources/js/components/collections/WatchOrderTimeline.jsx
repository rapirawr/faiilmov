import React, { useState } from 'react';
import { Calendar, Compass, Film, Play, Star, Sparkles, Clock, ArrowRight } from 'lucide-react';

export default function WatchOrderTimeline({ 
  releaseOrders = [], 
  chronologicalOrders = [], 
  franchiseName = '' 
}) {
  const hasRelease = releaseOrders && releaseOrders.length > 0;
  const hasChronological = chronologicalOrders && chronologicalOrders.length > 0;

  const [activeTab, setActiveTab] = useState(hasChronological ? 'chronological' : 'release');

  const currentList = activeTab === 'chronological' ? chronologicalOrders : releaseOrders;

  if (!hasRelease && !hasChronological) {
    return null;
  }

  return (
    <div className="space-y-6 text-white">
      {/* Header & Mode Switcher Pill */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-zinc-900/80 border border-white/10 backdrop-blur-md">
        <div>
          <h3 className="font-serif font-bold text-lg text-white flex items-center gap-2">
            <Compass className="w-5 h-5 text-white" />
            <span>Panduan Urutan Nonton</span>
          </h3>
          <p className="text-xs text-zinc-400 mt-0.5">
            {activeTab === 'chronological' 
              ? 'Urutan alur cerita berdasarkan garis waktu in-universe franchise'
              : 'Urutan rilis resmi film di bioskop berdasarkan tahun rilis'}
          </p>
        </div>

        {/* Switcher Toggle */}
        <div className="inline-flex p-1 rounded-xl bg-zinc-950 border border-white/10 shrink-0">
          {hasChronological && (
            <button
              onClick={() => setActiveTab('chronological')}
              className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer ${
                activeTab === 'chronological'
                  ? 'bg-white text-zinc-950 shadow-md'
                  : 'text-zinc-400 hover:text-white'
              }`}
            >
              <Sparkles className="w-3.5 h-3.5" />
              <span>Kronologis Cerita</span>
            </button>
          )}

          {hasRelease && (
            <button
              onClick={() => setActiveTab('release')}
              className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer ${
                activeTab === 'release'
                  ? 'bg-white text-zinc-950 shadow-md'
                  : 'text-zinc-400 hover:text-white'
              }`}
            >
              <Calendar className="w-3.5 h-3.5" />
              <span>Urutan Rilis</span>
            </button>
          )}
        </div>
      </div>

      {/* Vertical Timeline List */}
      <div className="relative pl-6 sm:pl-8 space-y-6 before:absolute before:left-3 sm:before:left-4 before:top-4 before:bottom-4 before:w-[2px] before:bg-white/20">
        {currentList.map((item, index) => {
          const film = item.film;
          if (!film) return null;

          return (
            <div key={item.id || index} className="relative group">
              {/* Timeline Sequence Badge Indicator */}
              <div className="absolute -left-6 sm:-left-8 top-3 -translate-x-1/2 w-7 h-7 rounded-full bg-zinc-950 border-2 border-white text-white font-mono font-bold text-xs flex items-center justify-center shadow-md transition-all z-10">
                {item.sequence || index + 1}
              </div>

              {/* Timeline Card */}
              <div className="p-3.5 sm:p-4 rounded-2xl bg-zinc-900/80 hover:bg-zinc-850/90 border border-white/10 hover:border-white/30 transition-colors duration-200 shadow-lg flex flex-col sm:flex-row sm:items-center gap-4 group">
                {/* Poster Thumbnail */}
                <a 
                  href={`/film/${film.slug}`} 
                  className="relative w-20 sm:w-24 aspect-[2/3] rounded-xl overflow-hidden shrink-0 bg-zinc-950 border border-white/10 shadow-md group-hover:border-white/30 transition-colors"
                >
                  <img
                    src={film.poster_url || '/placeholder-poster.jpg'}
                    alt={film.title}
                    className="w-full h-full object-cover"
                    loading="lazy"
                  />
                  {film.rating > 0 && (
                    <div className="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded-md bg-black/80 backdrop-blur-md text-[10px] font-bold text-amber-400 flex items-center gap-0.5">
                      <Star className="w-2.5 h-2.5 fill-amber-400" />
                      {film.rating}
                    </div>
                  )}
                </a>

                {/* Film Info & In-Universe Lore Note */}
                <div className="flex-1 min-w-0 space-y-1.5">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-white/10 text-zinc-300 border border-white/10">
                      Step #{item.sequence || index + 1}
                    </span>
                    {film.release_year && (
                      <span className="text-xs text-zinc-400 font-medium">
                        {film.release_year}
                      </span>
                    )}
                    {film.duration_minutes > 0 && (
                      <span className="text-xs text-zinc-500 flex items-center gap-1">
                        <Clock className="w-3 h-3" />
                        {film.duration_minutes}m
                      </span>
                    )}
                  </div>

                  <a href={`/film/${film.slug}`} className="block">
                    <h4 className="font-serif font-bold text-base sm:text-lg text-white group-hover:text-zinc-300 transition-colors truncate">
                      {film.title}
                    </h4>
                  </a>

                  {/* Note / In-universe contextual lore explanation */}
                  {item.note && (
                    <p className="text-xs text-zinc-300 bg-white/5 border border-white/10 px-2.5 py-1.5 rounded-xl inline-block mt-1 font-medium">
                      💡 {item.note}
                    </p>
                  )}

                  {film.synopsis && !item.note && (
                    <p className="text-xs text-zinc-400 line-clamp-2 mt-1">
                      {film.synopsis.replace(/<[^>]*>?/gm, '')}
                    </p>
                  )}
                </div>

                {/* Watch Action Button */}
                <div className="shrink-0 flex items-center gap-2 pt-2 sm:pt-0 border-t sm:border-t-0 border-white/5">
                  <a
                    href={`/film/${film.slug}/watch`}
                    className="w-full sm:w-auto px-4 py-2 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold transition shadow-md flex items-center justify-center gap-1.5"
                  >
                    <Play className="w-3.5 h-3.5 fill-current" />
                    <span>Nonton</span>
                  </a>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
