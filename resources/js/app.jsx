import React from 'react';
import { createRoot } from 'react-dom/client';
import Alpine from 'alpinejs';
import FilmCard from './components/FilmCard';
import HeroBannerCarousel from './components/HeroBannerCarousel';
import EpisodeSelector from './components/EpisodeSelector';

if (!window.Alpine) {
  window.Alpine = Alpine;
  Alpine.start();
}

function safeJsonParse(rawString, fallback = null) {
  if (!rawString) return fallback;
  try {
    return JSON.parse(rawString);
  } catch (e) {
    try {
      const textarea = document.createElement('textarea');
      textarea.innerHTML = rawString;
      return JSON.parse(textarea.value);
    } catch (err) {
      console.error('Failed to parse JSON attribute:', err);
      return fallback;
    }
  }
}

function initReactComponents() {
  // 1. Mount Hero Banner Carousel
  const heroContainer = document.getElementById('react-hero-banner');
  if (heroContainer && !heroContainer.dataset.mounted) {
    try {
      const films = safeJsonParse(heroContainer.dataset.films, []);
      if (Array.isArray(films) && films.length > 0) {
        heroContainer.dataset.mounted = 'true';
        createRoot(heroContainer).render(<HeroBannerCarousel films={films} />);
      }
    } catch (e) {
      console.error('Failed to mount HeroBannerCarousel', e);
    }
  }

  // 2. Mount Episode Selector
  const epContainer = document.getElementById('react-episode-selector');
  if (epContainer && !epContainer.dataset.mounted) {
    try {
      const seasons = safeJsonParse(epContainer.dataset.seasons, []);
      const initialSeason = parseInt(epContainer.dataset.initialSeason || '1', 10);
      if (Array.isArray(seasons) && seasons.length > 0) {
        epContainer.dataset.mounted = 'true';
        createRoot(epContainer).render(<EpisodeSelector seasons={seasons} initialSeason={initialSeason} />);
      }
    } catch (e) {
      console.error('Failed to mount EpisodeSelector', e);
    }
  }

  // 3. Mount Individual Film Cards
  const cardElements = document.querySelectorAll('.react-film-card');
  cardElements.forEach((el) => {
    if (el.dataset.mounted) return;
    try {
      const film = safeJsonParse(el.dataset.film, {});
      const isWatchlisted = el.dataset.watchlisted === 'true';
      const csrfToken = el.dataset.csrf || '';

      if (film && (film.id || film.slug)) {
        el.dataset.mounted = 'true';
        createRoot(el).render(<FilmCard film={film} isWatchlisted={isWatchlisted} csrfToken={csrfToken} />);
      }
    } catch (e) {
      console.error('Failed to mount FilmCard', e);
    }
  });
}

// Auto mount when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initReactComponents);
} else {
  initReactComponents();
}
