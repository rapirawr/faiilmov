import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Play, Info, Star, ChevronLeft, ChevronRight, Tv, Film as FilmIcon } from 'lucide-react';

export default function HeroBannerCarousel({ films = [] }) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isAutoPlaying, setIsAutoPlaying] = useState(true);

  if (!films || films.length === 0) return null;

  const currentFilm = films[currentIndex];

  useEffect(() => {
    if (!isAutoPlaying || films.length <= 1) return;

    const timer = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % films.length);
    }, 7000);

    return () => clearInterval(timer);
  }, [currentIndex, isAutoPlaying, films.length]);

  const handleNext = () => {
    setCurrentIndex((prev) => (prev + 1) % films.length);
    setIsAutoPlaying(false);
  };

  const handlePrev = () => {
    setCurrentIndex((prev) => (prev - 1 + films.length) % films.length);
    setIsAutoPlaying(false);
  };

  return (
    <div
      className="relative w-full min-h-[500px] sm:min-h-[580px] rounded-3xl overflow-hidden glass-panel border border-white/10 shadow-2xl mb-12 flex items-end group"
      onMouseEnter={() => setIsAutoPlaying(false)}
      onMouseLeave={() => setIsAutoPlaying(true)}
    >
      {/* Background Backdrop Image Slider */}
      <AnimatePresence mode="wait">
        <motion.div
          key={currentFilm.id || currentIndex}
          initial={{ opacity: 0, scale: 1.05 }}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.7, ease: 'easeOut' }}
          className="absolute inset-0 z-0 bg-dark-950"
        >
          <img
            src={currentFilm.backdrop_url || currentFilm.poster_url}
            alt={currentFilm.title}
            className="w-full h-full object-cover filter brightness-90 contrast-105"
          />
          {/* Subtle Ambient Vignette & Dark Gradients */}
          <div className="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/60 to-zinc-950/20" />
          <div className="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/50 to-transparent" />
        </motion.div>
      </AnimatePresence>

      {/* Content Container */}
      <div className="relative z-10 p-6 sm:p-12 max-w-3xl space-y-4">
        {/* Badges */}
        <motion.div
          key={`badge-${currentIndex}`}
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.4, delay: 0.1 }}
          className="flex items-center gap-2 flex-wrap"
        >
          <span className="px-3 py-1 rounded-xl glass-chip text-xs font-extrabold text-amber-300 border border-amber-500/30 shadow-lg flex items-center gap-1.5 uppercase">
            {currentFilm.subject_type === 'series' ? <Tv className="w-3.5 h-3.5 text-amber-400" /> : <FilmIcon className="w-3.5 h-3.5 text-amber-400" />}
            <span>{currentFilm.subject_type === 'series' ? 'Series' : 'Movie'}</span>
          </span>

          <span className="px-3 py-1 rounded-xl glass-chip text-xs font-bold text-amber-400 border border-amber-400/20 flex items-center gap-1">
            <Star className="w-3.5 h-3.5 fill-amber-400" />
            <span>{currentFilm.rating ? Number(currentFilm.rating).toFixed(1) : '0.0'} / 5.0</span>
          </span>

          <span className="px-3 py-1 rounded-xl glass-chip text-xs font-semibold text-zinc-300 border border-white/10">
            {currentFilm.release_year}
          </span>
        </motion.div>

        {/* Title */}
        <motion.h1
          key={`title-${currentIndex}`}
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, delay: 0.2 }}
          className="font-serif font-extrabold text-3xl sm:text-5xl text-white tracking-tight leading-tight drop-shadow-lg"
        >
          {currentFilm.title}
        </motion.h1>

        {/* Synopsis */}
        {currentFilm.synopsis && (
          <motion.p
            key={`synopsis-${currentIndex}`}
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, delay: 0.3 }}
            className="text-sm sm:text-base text-zinc-300 line-clamp-3 max-w-2xl leading-relaxed drop-shadow"
          >
            {currentFilm.synopsis}
          </motion.p>
        )}

        {/* Action Buttons */}
        <motion.div
          key={`actions-${currentIndex}`}
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, delay: 0.4 }}
          className="flex items-center gap-2.5 sm:gap-4 pt-2 flex-wrap"
        >
          <a
            href={`/film/${currentFilm.slug}/watch`}
            className="px-5 py-2.5 sm:px-7 sm:py-3.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs sm:text-sm transition-all duration-200 flex items-center gap-2 shadow-xl hover:shadow-2xl hover:scale-105 cursor-pointer shrink-0"
          >
            <Play className="w-4 h-4 fill-zinc-950 ml-0.5" />
            <span>Tonton Sekarang</span>
          </a>

          <a
            href={`/film/${currentFilm.slug}`}
            className="px-4 py-2.5 sm:px-6 sm:py-3.5 rounded-2xl glass-card hover:bg-white/15 text-white font-semibold text-xs sm:text-sm border border-white/15 transition-all duration-200 flex items-center gap-1.5 cursor-pointer hover:border-amber-400/40 shrink-0"
          >
            <Info className="w-4 h-4 text-amber-400" />
            <span>Detail Lengkap</span>
          </a>
        </motion.div>
      </div>

      {/* Navigation Arrows */}
      {films.length > 1 && (
        <>

          <button
            onClick={handleNext}
            className="absolute right-2.5 sm:right-4 top-1/2 -translate-y-1/2 z-20 p-2.5 sm:p-3 rounded-2xl glass-chip border border-white/15 text-white hover:bg-white/20 transition-all opacity-80 sm:opacity-0 group-hover:opacity-100 cursor-pointer flex items-center justify-center"
            aria-label="Berikutnya"
          >
            <ChevronRight className="w-4 h-4 sm:w-5 sm:h-5" />
          </button>

          {/* Dots Indicator */}
          <div className="absolute top-4 right-4 sm:top-auto sm:bottom-6 sm:right-6 z-20 flex items-center gap-1.5 p-1.5 rounded-full bg-black/40 backdrop-blur-md border border-white/10 sm:border-none sm:bg-transparent">
            {films.map((_, idx) => (
              <button
                key={idx}
                onClick={() => {
                  setCurrentIndex(idx);
                  setIsAutoPlaying(false);
                }}
                className={`h-1.5 sm:h-2 rounded-full transition-all duration-300 cursor-pointer ${
                  currentIndex === idx ? 'w-5 sm:w-7 bg-amber-400 shadow-md shadow-amber-400/50' : 'w-1.5 sm:w-2 bg-white/40 hover:bg-white/70'
                }`}
                aria-label={`Slide ${idx + 1}`}
              />
            ))}
          </div>
        </>
      )}
    </div>
  );
}
