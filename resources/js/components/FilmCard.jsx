import React from 'react';
import { motion } from 'framer-motion';
import { Play, Film as FilmIcon, Tv, Clock } from 'lucide-react';

function formatDuration(minutes, type) {
  if (type === 'dracin') return 'Dracin';
  if (type === 'series') return 'TV Series';
  if (!minutes || minutes <= 0) return '1h 30m';
  const hrs = Math.floor(minutes / 60);
  const mins = minutes % 60;
  if (hrs > 0 && mins > 0) return `${hrs}h ${mins}m`;
  if (hrs > 0) return `${hrs}h`;
  return `${mins}m`;
}

function getCustomAgeBadge(rawRating) {
  const globalStyle = (typeof window !== 'undefined' && window.__AGE_RATING_STYLE__) ? window.__AGE_RATING_STYLE__ : null;
  const r = String(rawRating || '').toUpperCase().trim();

  let key = '13+';
  if (!r || r === 'UNRATED') {
    key = 'unrated';
  } else if (['SU', 'G', 'PG', 'TV-Y'].includes(r)) {
    key = 'SU';
  } else if (['13+', 'PG-13', 'TV-14'].includes(r)) {
    key = '13+';
  } else if (['16+', '17+', 'TV-MA'].includes(r)) {
    key = '16+';
  } else if (['18+', '21+', 'R', 'NC-17'].includes(r)) {
    key = '18+';
  }

  const defaultBadges = {
    'SU': { label: 'SU', bg_color: '#064e3b', border_color: '#10b981', text_color: '#ffffff' },
    '13+': { label: '13+', bg_color: '#0c4a6e', border_color: '#0284c7', text_color: '#ffffff' },
    '16+': { label: '16+', bg_color: '#78350f', border_color: '#f59e0b', text_color: '#ffffff' },
    '18+': { label: '18+', bg_color: '#4c0519', border_color: '#f43f5e', text_color: '#ffffff' },
    'unrated': { label: 'UNRATED', bg_color: '#27272a', border_color: '#52525b', text_color: '#d4d4d8' },
  };

  const badges = (globalStyle && globalStyle.badges) ? globalStyle.badges : defaultBadges;
  const badgeCfg = badges[key] || defaultBadges[key] || defaultBadges['13+'];

  const borderRadius = (globalStyle && globalStyle.border_radius) ? globalStyle.border_radius : 'rounded-lg';
  const fontWeight = (globalStyle && globalStyle.font_weight) ? globalStyle.font_weight : 'font-black';
  const borderWidth = (globalStyle && globalStyle.border_width) ? globalStyle.border_width : 'border-2';
  const hasGlow = globalStyle ? Boolean(globalStyle.has_glow) : true;
  const hasShadow = globalStyle ? Boolean(globalStyle.has_shadow) : true;

  const bWidth = borderWidth === 'border-0' ? '0px' : (borderWidth === 'border-[1.5px]' ? '1.5px' : (borderWidth === 'border' ? '1px' : '2px'));
  const shadowStr = hasGlow 
    ? `0 0 8px ${badgeCfg.border_color}50` 
    : (hasShadow ? '0 2px 4px rgba(0,0,0,0.5)' : 'none');

  return {
    label: badgeCfg.label || key,
    className: `${borderRadius} ${fontWeight} inline-flex items-center justify-center transition-all duration-200`,
    style: {
      backgroundColor: badgeCfg.bg_color || '#27272a',
      borderColor: badgeCfg.border_color || '#52525b',
      color: badgeCfg.text_color || '#ffffff',
      borderWidth: bWidth,
      borderStyle: bWidth === '0px' ? 'none' : 'solid',
      boxShadow: shadowStr,
    }
  };
}

export default function FilmCard({ film }) {
  const [isHovered, setIsHovered] = React.useState(false);

  const showUrl = `/film/${film.slug}`;
  const watchUrl = `/film/${film.slug}/watch`;

  const ageBadge = getCustomAgeBadge(film.content_rating);
  const formattedDur = formatDuration(film.duration_minutes, film.subject_type);

  const isComingSoon = Boolean(
    film.is_coming_soon ||
    (film.available_from && new Date(film.available_from) > new Date()) ||
    (film.release_year && parseInt(film.release_year, 10) > new Date().getFullYear())
  );

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
          animate={{ 
            scale: isHovered ? 1.08 : 1,
            filter: isHovered ? 'blur(4px)' : 'blur(0px)'
          }}
          transition={{ duration: 0.35, ease: [0.25, 1, 0.5, 1] }}
          loading="lazy"
        />

        {/* Ambient Dark Overlay (Disembunyikan saat hover agar poster tetap cerah) */}
        <div className="absolute inset-0 bg-gradient-to-t from-zinc-950/50 via-transparent to-transparent opacity-60 group-hover:opacity-0 transition-opacity pointer-events-none" />

        {/* Resolution Badge */}
        {film.max_resolution && (
          <div className="absolute top-3 left-3 z-10 pointer-events-none">
            <span className="px-2.5 py-1 rounded-xl glass-chip text-[10px] font-extrabold uppercase text-amber-300 border border-amber-500/30 backdrop-blur-md shadow-md flex items-center gap-1">
              {film.subject_type === 'series' ? <Tv className="w-3 h-3 text-amber-400" /> : <FilmIcon className="w-3 h-3 text-amber-400" />}
              <span>{film.max_resolution}</span>
            </span>
          </div>
        )}

        {/* Hover Action Overlay: Coming Soon centered text or Quick Play button */}
        {isComingSoon ? (
          <motion.div
            initial={false}
            animate={{ opacity: isHovered ? 1 : 0, scale: isHovered ? 1 : 0.9 }}
            transition={{ duration: 0.2 }}
            className="absolute inset-0 flex items-center justify-center z-10 pointer-events-auto p-3"
          >
            <a
              href={showUrl}
              className="px-4 py-2 rounded-full bg-amber-500 hover:bg-amber-400 text-zinc-950 font-black text-xs uppercase tracking-wider shadow-2xl backdrop-blur-md border border-amber-300/80 hover:scale-105 transition-all duration-200 flex items-center gap-1.5 cursor-pointer text-center"
            >
              <Clock className="w-3.5 h-3.5 shrink-0" />
              <span>Coming Soon</span>
            </a>
          </motion.div>
        ) : (
          <motion.div
            initial={false}
            animate={{ opacity: isHovered ? 1 : 0, scale: isHovered ? 1 : 0.9 }}
            transition={{ duration: 0.2 }}
            className="absolute inset-0 flex items-center justify-center z-10 pointer-events-auto"
          >
            <a
              href={watchUrl}
              className="p-3.5 sm:p-4 rounded-full bg-white text-zinc-950 shadow-2xl hover:scale-110 transition-transform duration-200 flex items-center justify-center cursor-pointer"
            >
              <Play className="w-6 h-6 fill-zinc-950 ml-0.5" />
            </a>
          </motion.div>
        )}
      </div>

      {/* Card Info Details */}
      <div className="p-3.5 flex flex-col flex-1 justify-between gap-2 bg-gradient-to-b from-zinc-900/60 to-zinc-950">
        <div className="space-y-1">
          <a href={showUrl} className="block group-hover:text-amber-300 transition-colors">
            <h3 className="font-serif font-bold text-sm text-white line-clamp-1 leading-snug">
              {film.title}
            </h3>
          </a>
          
          {/* Metadata Row with Colored Age Rating Badge */}
          <div className="flex items-center gap-1.5 text-[10.5px] text-zinc-400 font-medium flex-wrap">
            <span 
              className={`px-1.5 py-0.5 text-[9.5px] tracking-wider font-mono ${ageBadge.className}`}
              style={ageBadge.style}
            >
              {ageBadge.label}
            </span>
            <span className="text-zinc-500">•</span>
            <span>{formattedDur}</span>
            <span className="text-zinc-500">•</span>
            <span>{film.release_year || '2024'}</span>
          </div>
        </div>

        {/* Genres Pill List with Fixed Height Reserve */}
        <div className="flex items-center gap-1.5 overflow-hidden h-[22px]">
          {film.genres && film.genres.length > 0 ? (
            film.genres.slice(0, 2).map((g) => (
              <span key={g.id || g.slug || g.name} className="px-2 py-0.5 rounded-lg bg-white/5 border border-white/10 text-[9.5px] font-semibold text-zinc-400 truncate">
                {g.name}
              </span>
            ))
          ) : (
            <span className="h-[22px] block" aria-hidden="true"></span>
          )}
        </div>
      </div>
    </motion.div>
  );
}
