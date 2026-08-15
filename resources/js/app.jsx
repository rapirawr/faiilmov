import React from 'react';
import { createRoot } from 'react-dom/client';
import Alpine from 'alpinejs';
import { initEcho } from './echo';
import FilmCard from './components/FilmCard';
import HeroBannerCarousel from './components/HeroBannerCarousel';
import EpisodeSelector from './components/EpisodeSelector';
import DracinFeed from './components/dracin/DracinFeed';
import DracinCatalog from './components/dracin/DracinCatalog';

// Make initEcho available globally and auto-initialize Echo
window.initEcho = initEcho;
try {
  initEcho();
} catch (e) {
  console.warn('[Faiilmov] Echo auto-init deferred:', e);
}

if (!window.Alpine) {
  window.Alpine = Alpine;
  // Defer start so blade @push('scripts') alpine:init listeners register first
  document.addEventListener('DOMContentLoaded', () => Alpine.start());
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
  // 1. Mount Dracin Vertical Feed Player Page
  const dracinContainer = document.getElementById('react-dracin-feed');
  if (dracinContainer && !dracinContainer.dataset.mounted) {
    try {
      const initialSource = dracinContainer.dataset.initialSource || 'dramabox';
      const initialId = dracinContainer.dataset.initialId || '';
      const hasExplicitId = dracinContainer.dataset.hasExplicitId === 'true';
      const initialEp = parseInt(dracinContainer.dataset.initialEp || '1', 10);
      const initialFeed = safeJsonParse(dracinContainer.dataset.initialFeed, []);
      const initialActiveDetail = safeJsonParse(dracinContainer.dataset.initialActiveDetail, null);
      const sourcesList = safeJsonParse(dracinContainer.dataset.sourcesList, {});
      const csrfToken = dracinContainer.dataset.csrf || '';

      dracinContainer.dataset.mounted = 'true';
      createRoot(dracinContainer).render(
        <DracinFeed
          initialSource={initialSource}
          initialId={initialId}
          hasExplicitId={hasExplicitId}
          initialEp={initialEp}
          initialFeed={initialFeed}
          initialActiveDetail={initialActiveDetail}
          sourcesList={sourcesList}
          csrfToken={csrfToken}
        />
      );
    } catch (e) {
      console.error('Failed to mount DracinFeed', e);
    }
  }

  // 1b. Mount Dracin Catalog Grid Page
  const dracinCatalogContainer = document.getElementById('react-dracin-catalog');
  if (dracinCatalogContainer && !dracinCatalogContainer.dataset.mounted) {
    try {
      const initialSource = dracinCatalogContainer.dataset.initialSource || 'dramabox';
      const initialFeed = safeJsonParse(dracinCatalogContainer.dataset.initialFeed, []);
      const sourcesList = safeJsonParse(dracinCatalogContainer.dataset.sourcesList, {});
      const csrfToken = dracinCatalogContainer.dataset.csrf || '';

      dracinCatalogContainer.dataset.mounted = 'true';
      createRoot(dracinCatalogContainer).render(
        <DracinCatalog
          initialSource={initialSource}
          initialFeed={initialFeed}
          sourcesList={sourcesList}
          csrfToken={csrfToken}
        />
      );
    } catch (e) {
      console.error('Failed to mount DracinCatalog', e);
    }
  }

  // 2. Mount Hero Banner Carousel
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

  // 3. Mount Episode Selector
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

  // 4. Mount Individual Film Cards
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

