import React from 'react';
import { motion } from 'framer-motion';
import { Play, Film as FilmIcon, Tv } from 'lucide-react';

function formatDuration(minutes, type) {
  if (!minutes || minutes <= 0) {
    return type === 'series' ? 'TV Series' : '1h 30m';
  }
  const hrs = Math.floor(minutes / 60);
  const mins = minutes % 60;
  if (hrs > 0 && mins > 0) return `${hrs}h ${mins}m`;
  if (hrs > 0) return `${hrs}h`;
  return `${mins}m`;
}

function formatAgeRating(rating) {
  if (!rating) return '13+';
  const upper = String(rating).toUpperCase();
  if (upper === 'R' || upper === 'NC-17' || upper === '18+') return '18+';
  if (upper === 'PG-13' || upper === '13+') return '13+';
  if (upper === 'PG' || upper === 'G' || upper === 'SU') return 'SU';
  return rating;
}

function getAgeBadgeStyle(rating) {
  const upper = String(rating || '').toUpperCase();
  if (upper === '18+' || upper === 'R' || upper === 'NC-17') {
    return 'bg-rose-500/20 text-rose-300 border-rose-500/40';
  }
  if (upper === '16+') {
    return 'bg-orange-500/20 text-orange-300 border-orange-500/40';
  }
  if (upper === '13+' || upper === 'PG-13') {
    return 'bg-sky-500/20 text-sky-300 border-sky-500/40';
  }
  if (upper === 'SU' || upper === 'G' || upper === 'PG') {
    return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40';
  }
  return 'bg-zinc-800 text-zinc-300 border-white/20';
}

export default function FilmCard({ film }) {
  const [isHovered, setIsHovered] = React.useState(false);

  const showUrl = `/film/${film.slug}`;
  const watchUrl = `/film/${film.slug}/watch`;

  const formattedAge = formatAgeRating(film.content_rating);
  const formattedDur = formatDuration(film.duration_minutes, film.subject_type);

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

        {/* Resolution Badge */}
        {film.max_resolution && (
          <div className="absolute top-3 left-3 z-10 pointer-events-none">
            <span className="px-2.5 py-1 rounded-xl glass-chip text-[10px] font-extrabold uppercase text-amber-300 border border-amber-500/30 backdrop-blur-md shadow-md flex items-center gap-1">
              {film.subject_type === 'series' ? <Tv className="w-3 h-3 text-amber-400" /> : <FilmIcon className="w-3 h-3 text-amber-400" />}
              <span>{film.max_resolution}</span>
            </span>
          </div>
        )}

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
      <div className="p-3.5 flex flex-col flex-1 justify-between gap-1.5 bg-gradient-to-b from-zinc-900/60 to-zinc-950">
        <div>
          <a href={showUrl} className="block group-hover:text-amber-300 transition-colors mb-1">
            <h3 className="font-serif font-bold text-sm text-white line-clamp-1 leading-snug">
              {film.title}
            </h3>
          </a>
          
          {/* Metadata Row with Colored Age Rating Badge */}
          <div className="flex items-center gap-1.5 text-[10.5px] text-zinc-400 font-medium flex-wrap">
            <span className={`px-1.5 py-0.5 rounded-md border text-[9.5px] font-extrabold uppercase tracking-wider ${getAgeBadgeStyle(formattedAge)}`}>
              {formattedAge}
            </span>
            <span className="text-zinc-500">•</span>
            <span>{formattedDur}</span>
            <span className="text-zinc-500">•</span>
            <span>{film.release_year || '2024'}</span>
          </div>
        </div>

        {/* Genres Pill List */}
        {film.genres && film.genres.length > 0 && (
          <div className="flex items-center gap-1.5 overflow-hidden mt-1">
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
