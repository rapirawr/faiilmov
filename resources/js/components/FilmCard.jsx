import React from 'react';
import { motion } from 'framer-motion';
import { Star, Play, Bookmark, Film as FilmIcon, Tv } from 'lucide-react';

export default function FilmCard({ film, isWatchlisted = false, csrfToken = '' }) {
  const [watchlisted, setWatchlisted] = React.useState(isWatchlisted);
  const [isHovered, setIsHovered] = React.useState(false);

  const toggleWatchlist = async (e) => {
    e.preventDefault();
    e.stopPropagation();

    try {
      const res = await fetch(`/film/${film.id}/watchlist`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
      });
      if (res.ok) {
        setWatchlisted(!watchlisted);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const showUrl = `/film/${film.slug}`;
  const watchUrl = `/film/${film.slug}/watch`;

  return (
    <motion.div
      initial={{ opacity: 0, y: 15 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3 }}
      className="group relative flex flex-col rounded-3xl glass-card overflow-hidden border border-white/10 hover:border-amber-400/40 transition-all duration-300 shadow-xl hover:shadow-2xl hover:shadow-amber-500/10"
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      {/* Poster Image Container */}
      <div className="relative aspect-[2/3] w-full overflow-hidden bg-zinc-900">
        <motion.img
          src={film.thumbnail_url || film.poster_url}
          alt={film.title}
          className="h-full w-full object-cover"
          animate={{ scale: isHovered ? 1.07 : 1 }}
          transition={{ duration: 0.4, ease: [0.25, 1, 0.5, 1] }}
          loading="lazy"
        />

        {/* Ambient Dark Overlay */}
        <div className="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/20 to-transparent opacity-80 group-hover:opacity-60 transition-opacity" />

        {/* Top Badges */}
        <div className="absolute top-3 left-3 right-3 flex items-center justify-between z-10 pointer-events-none">
          <span className="px-2.5 py-1 rounded-xl glass-chip text-[10px] font-extrabold uppercase text-amber-300 border border-amber-500/30 backdrop-blur-md shadow-md flex items-center gap-1">
            {film.subject_type === 'series' ? <Tv className="w-3 h-3 text-amber-400" /> : <FilmIcon className="w-3 h-3 text-amber-400" />}
            <span>{film.max_resolution || '1080P'}</span>
          </span>

          <button
            onClick={toggleWatchlist}
            type="button"
            className={`pointer-events-auto p-2 rounded-xl transition-all duration-200 backdrop-blur-md cursor-pointer ${
              watchlisted
                ? 'bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/30'
                : 'glass-chip text-zinc-300 hover:text-white hover:bg-white/20'
            }`}
            title={watchlisted ? 'Hapus dari Watchlist' : 'Tambah ke Watchlist'}
          >
            <Bookmark className={`w-3.5 h-3.5 ${watchlisted ? 'fill-zinc-950' : ''}`} />
          </button>
        </div>

        {/* Hover Quick Play Overlay */}
        <motion.div
          initial={false}
          animate={{ opacity: isHovered ? 1 : 0, scale: isHovered ? 1 : 0.9 }}
          transition={{ duration: 0.2 }}
          className="absolute inset-0 flex items-center justify-center bg-black/40 backdrop-blur-[2px] z-10"
        >
          <a
            href={watchUrl}
            className="p-4 rounded-full bg-amber-500 text-zinc-950 shadow-2xl shadow-amber-500/50 hover:scale-110 transition-transform duration-200 flex items-center justify-center cursor-pointer"
          >
            <Play className="w-6 h-6 fill-zinc-950 ml-0.5" />
          </a>
        </motion.div>
      </div>

      {/* Card Info Details */}
      <div className="p-4 flex flex-col flex-1 justify-between gap-2 bg-gradient-to-b from-zinc-900/60 to-zinc-950">
        <div>
          <div className="flex items-center justify-between gap-2 text-[11px] text-zinc-400 mb-1">
            <span className="font-semibold text-zinc-400">{film.release_year}</span>
            <span className="flex items-center gap-1 font-bold text-amber-400">
              <Star className="w-3 h-3 fill-amber-400" />
              <span>{film.rating ? Number(film.rating).toFixed(1) : '0.0'}</span>
            </span>
          </div>

          <a href={showUrl} className="block group-hover:text-amber-300 transition-colors">
            <h3 className="font-serif font-bold text-sm text-white line-clamp-1 leading-snug">
              {film.title}
            </h3>
          </a>
        </div>

        {/* Genres Pill List */}
        {film.genres && film.genres.length > 0 && (
          <div className="flex items-center gap-1.5 overflow-hidden">
            {film.genres.slice(0, 2).map((g) => (
              <span key={g.id || g.slug || g.name} className="px-2 py-0.5 rounded-lg bg-white/5 border border-white/10 text-[9.5px] font-semibold text-zinc-400 truncate">
                {g.name}
              </span>
            ))}
          </div>
        )}
      </div>
    </motion.div>
  );
}
