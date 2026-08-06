import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Tv, PlayCircle, Clock } from 'lucide-react';

export default function EpisodeSelector({ seasons = [], initialSeason = 1 }) {
  const [activeSeasonNum, setActiveSeasonNum] = useState(initialSeason);

  if (!seasons || seasons.length === 0) return null;

  const currentSeasonObj = seasons.find((s) => s.season_number === activeSeasonNum) || seasons[0];
  const episodes = currentSeasonObj?.episodes || [];

  return (
    <section className="glass-panel p-6 sm:p-8 rounded-3xl border border-white/10 space-y-6 shadow-2xl">
      {/* Header & Season Tabs */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-4">
        <h3 className="font-serif font-bold text-xl text-white flex items-center gap-2">
          <Tv className="w-5 h-5 text-amber-400" />
          <span>Daftar Season & Episode</span>
          <span className="text-xs font-semibold text-zinc-300 bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full ml-1">
            {episodes.length} Episode
          </span>
        </h3>

        {/* Season Tabs */}
        <div className="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
          {seasons.map((s) => {
            const isActive = s.season_number === activeSeasonNum;
            return (
              <button
                key={s.season_number}
                onClick={() => setActiveSeasonNum(s.season_number)}
                className={`relative px-4 py-2 rounded-2xl text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1.5 font-semibold ${
                  isActive ? 'text-zinc-950 font-bold' : 'text-zinc-300 hover:text-white glass-card border-white/10'
                }`}
              >
                {isActive && (
                  <motion.div
                    layoutId="activeSeasonTab"
                    className="absolute inset-0 bg-white rounded-2xl shadow-md"
                    transition={{ type: 'spring', stiffness: 400, damping: 30 }}
                  />
                )}
                <span className="relative z-10">Season {s.season_number}</span>
                <span className={`relative z-10 text-[10px] opacity-75 ${isActive ? 'text-zinc-950' : 'text-zinc-400'}`}>
                  ({s.episodes ? s.episodes.length : 0} Ep)
                </span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Episode Cards Grid */}
      <motion.div layout className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 max-h-[30rem] overflow-y-auto pr-1">
        <AnimatePresence mode="popLayout">
          {episodes.map((ep, idx) => (
            <motion.a
              key={ep.episode_number || idx}
              href={ep.watch_url}
              initial={{ opacity: 0, scale: 0.96 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.96 }}
              transition={{ duration: 0.25, delay: idx * 0.03 }}
              className="p-3.5 rounded-2xl glass-card border border-white/10 hover:border-amber-400/50 hover:bg-white/10 transition-all flex items-center gap-3.5 group shadow-md"
            >
              <div className="relative w-28 aspect-video rounded-xl overflow-hidden bg-zinc-900 shrink-0 border border-white/10">
                <img
                  src={ep.thumbnail_url || 'https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?q=50&w=300'}
                  alt={ep.title}
                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                <div className="absolute inset-0 bg-black/40 group-hover:bg-amber-500/20 transition-colors flex items-center justify-center">
                  <PlayCircle className="w-6 h-6 text-white group-hover:text-amber-300 transition-colors" />
                </div>
              </div>

              <div className="min-w-0 flex-1 space-y-1">
                <div className="flex items-center gap-2">
                  <span className="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">
                    Eps {ep.episode_number}
                  </span>
                  {ep.duration_minutes && (
                    <span className="text-[11px] text-zinc-400 flex items-center gap-1">
                      <Clock className="w-3 h-3 text-zinc-400" />
                      <span>{ep.duration_minutes}m</span>
                    </span>
                  )}
                </div>

                <h4 className="font-semibold text-xs text-white group-hover:text-amber-300 transition-colors truncate">
                  {ep.title || `Episode ${ep.episode_number}`}
                </h4>

                {ep.synopsis && (
                  <p className="text-[11px] text-zinc-400 line-clamp-1 leading-snug">
                    {ep.synopsis}
                  </p>
                )}
              </div>
            </motion.a>
          ))}
        </AnimatePresence>
      </motion.div>
    </section>
  );
}
