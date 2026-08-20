import React from 'react';
import { createRoot } from 'react-dom/client';
import Alpine from 'alpinejs';
import { initEcho } from './echo';
import FilmCard from './components/FilmCard';
import HeroBannerCarousel from './components/HeroBannerCarousel';
import EpisodeSelector from './components/EpisodeSelector';
import FilmRequestModal from './components/FilmRequestModal';
import DracinFeed from './components/dracin/DracinFeed';
import DracinCatalog from './components/dracin/DracinCatalog';

import FeatureBannerRotator from './components/FeatureBannerRotator';
import AdminDevDashboard from './components/admin/dashboard/AdminDevDashboard';
import FilmImporter from './components/admin/films/FilmImporter';
import CreateCollectionModal from './components/collections/CreateCollectionModal';
import WatchOrderTimeline from './components/collections/WatchOrderTimeline';
import AdminSmartCollections from './components/admin/collections/AdminSmartCollections';
import VisualSearchModal from './components/search/VisualSearchModal';
import CollectionStudioEditor from './components/collections/CollectionStudioEditor';
import EpisodeComments from './components/EpisodeComments';
import SpoilerText from './components/SpoilerText';
import GlobalModal from './components/GlobalModal';
import { createMorph } from 'morphicons/dom';
import * as LucideIcons from 'lucide';

// Expose Morphicons & Lucide Icons globally
window.createMorph = createMorph;
window.morphIcons = LucideIcons;

// Register Alpine x-morph directive
Alpine.directive('morph', (el, { expression, modifiers }, { evaluateLater, effect, cleanup }) => {
  let pathEl;

  if (el.tagName.toLowerCase() === 'svg') {
    el.innerHTML = '';
    pathEl = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    el.appendChild(pathEl);
  } else if (el.tagName.toLowerCase() === 'path') {
    pathEl = el;
  } else {
    el.innerHTML = '';
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', el.getAttribute('data-stroke-width') || '2');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('class', 'w-full h-full');
    pathEl = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    svg.appendChild(pathEl);
    el.appendChild(svg);
  }

  const getIconData = (iconVal) => {
    if (!iconVal) return null;
    if (typeof iconVal === 'string') {
      return LucideIcons[iconVal] || null;
    }
    return iconVal;
  };

  // Default spring physics preset to 'bouncy'
  let springPreset = 'bouncy';
  if (modifiers.includes('smooth')) springPreset = 'smooth';
  else if (modifiers.includes('snappy')) springPreset = 'snappy';
  else if (modifiers.includes('bouncy')) springPreset = 'bouncy';
  else if (modifiers.includes('superbounce')) springPreset = { k: 260, c: 10 };

  let morphInstance = null;
  let currentTarget = null;
  const getValue = evaluateLater(expression);

  effect(() => {
    getValue((val) => {
      const targetIcon = getIconData(val);
      if (!targetIcon || !pathEl) return;

      if (!morphInstance) {
        currentTarget = targetIcon;
        morphInstance = createMorph(pathEl, targetIcon);
      } else if (currentTarget !== targetIcon) {
        currentTarget = targetIcon;
        morphInstance.morphTo(targetIcon, springPreset);
      }
    });
  });

  cleanup(() => {
    if (morphInstance) {
      morphInstance.destroy();
      morphInstance = null;
    }
  });
});

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

  // 5. Mount Film Request Modal
  const requestModalEl = document.getElementById('react-film-request-modal');
  if (requestModalEl && !requestModalEl.dataset.mounted) {
    try {
      const initialTitle = requestModalEl.dataset.initialTitle || '';
      const csrfToken = requestModalEl.dataset.csrf || '';
      requestModalEl.dataset.mounted = 'true';
      createRoot(requestModalEl).render(<FilmRequestModal initialTitle={initialTitle} csrfToken={csrfToken} />);
    } catch (e) {
      console.error('Failed to mount FilmRequestModal', e);
    }
  }

  // 6. Mount Feature Banner Rotator (React 3D 4-Sided Cube)
  const featureBannerEl = document.getElementById('react-feature-banner');
  if (featureBannerEl && !featureBannerEl.dataset.mounted) {
    try {
      const banners = safeJsonParse(featureBannerEl.dataset.banners, []);
      if (Array.isArray(banners) && banners.length > 0) {
        featureBannerEl.dataset.mounted = 'true';
        createRoot(featureBannerEl).render(<FeatureBannerRotator banners={banners} />);
      }
    } catch (e) {
      console.error('Failed to mount FeatureBannerRotator', e);
    }
  }

  // 7. Mount Admin Developer & Ops Real-Time Dashboard
  const adminDevDashboardEl = document.getElementById('react-admin-dev-dashboard');
  if (adminDevDashboardEl && !adminDevDashboardEl.dataset.mounted) {
    try {
      const initialSnapshot = safeJsonParse(adminDevDashboardEl.dataset.initialSnapshot, null);
      const csrfToken = adminDevDashboardEl.dataset.csrf || '';
      const actionUrls = {
        importer: adminDevDashboardEl.dataset.importerUrl || '',
        syncMoviebox: adminDevDashboardEl.dataset.syncMovieboxUrl || '',
        syncDracin: adminDevDashboardEl.dataset.syncDracinUrl || '',
        reports: adminDevDashboardEl.dataset.reportsUrl || '',
        requests: adminDevDashboardEl.dataset.requestsUrl || '',
        catalog: adminDevDashboardEl.dataset.catalogUrl || '',
        contentRating: adminDevDashboardEl.dataset.contentRatingUrl || '',
        users: adminDevDashboardEl.dataset.usersUrl || '',
        watchParties: adminDevDashboardEl.dataset.watchPartiesUrl || '',
      };

      adminDevDashboardEl.dataset.mounted = 'true';
      createRoot(adminDevDashboardEl).render(
        <AdminDevDashboard
          initialSnapshot={initialSnapshot}
          csrfToken={csrfToken}
          actionUrls={actionUrls}
        />
      );
    } catch (e) {
      console.error('Failed to mount AdminDevDashboard', e);
    }
  }

  // 8. Mount Admin Film Importer
  const filmImporterEl = document.getElementById('react-film-importer');
  if (filmImporterEl && !filmImporterEl.dataset.mounted) {
    try {
      const searchUrl = filmImporterEl.dataset.searchUrl || '';
      const detailUrl = filmImporterEl.dataset.detailUrl || '';
      const importUrl = filmImporterEl.dataset.importUrl || '';
      const importBatchUrl = filmImporterEl.dataset.importBatchUrl || '';
      const csrfToken = filmImporterEl.dataset.csrf || '';

      filmImporterEl.dataset.mounted = 'true';
      createRoot(filmImporterEl).render(
        <FilmImporter
          searchUrl={searchUrl}
          detailUrl={detailUrl}
          importUrl={importUrl}
          importBatchUrl={importBatchUrl}
          csrfToken={csrfToken}
        />
      );
    } catch (e) {
      console.error('Failed to mount FilmImporter', e);
    }
  }

  // 9. Mount Global Create Collection Modal
  const createColModalEl = document.getElementById('react-create-collection-modal');
  if (createColModalEl && !createColModalEl.dataset.mounted) {
    try {
      const csrfToken = createColModalEl.dataset.csrf || '';
      createColModalEl.dataset.mounted = 'true';
      createRoot(createColModalEl).render(<CreateCollectionModal csrfToken={csrfToken} />);
    } catch (e) {
      console.error('Failed to mount CreateCollectionModal', e);
    }
  }

  // 10. Mount Watch Order Timeline
  const watchOrderEl = document.getElementById('react-watch-order-timeline');
  if (watchOrderEl && !watchOrderEl.dataset.mounted) {
    try {
      const releaseOrders = safeJsonParse(watchOrderEl.dataset.releaseOrders, []);
      const chronologicalOrders = safeJsonParse(watchOrderEl.dataset.chronologicalOrders, []);
      const franchiseName = watchOrderEl.dataset.franchiseName || '';
      watchOrderEl.dataset.mounted = 'true';
      createRoot(watchOrderEl).render(
        <WatchOrderTimeline
          releaseOrders={releaseOrders}
          chronologicalOrders={chronologicalOrders}
          franchiseName={franchiseName}
        />
      );
    } catch (e) {
      console.error('Failed to mount WatchOrderTimeline', e);
    }
  }

  // 11. Mount Admin Smart Collections Dashboard
  const adminCollectionsEl = document.getElementById('react-admin-smart-collections');
  if (adminCollectionsEl && !adminCollectionsEl.dataset.mounted) {
    try {
      const initialCollections = safeJsonParse(adminCollectionsEl.dataset.initialCollections, []);
      const stats = safeJsonParse(adminCollectionsEl.dataset.stats, {});
      const csrfToken = adminCollectionsEl.dataset.csrf || '';
      adminCollectionsEl.dataset.mounted = 'true';
      createRoot(adminCollectionsEl).render(
        <AdminSmartCollections
          initialCollections={initialCollections}
          stats={stats}
          csrfToken={csrfToken}
        />
      );
    } catch (e) {
      console.error('Failed to mount AdminSmartCollections', e);
    }
  }

  // 12. Mount Visual Search Modal
  const visualSearchModalEl = document.getElementById('react-visual-search-modal');
  if (visualSearchModalEl && !visualSearchModalEl.dataset.mounted) {
    try {
      const csrfToken = visualSearchModalEl.dataset.csrf || '';
      visualSearchModalEl.dataset.mounted = 'true';
      createRoot(visualSearchModalEl).render(<VisualSearchModal csrfToken={csrfToken} />);
    } catch (e) {
      console.error('Failed to mount VisualSearchModal', e);
    }
  }

  // 13. Mount Collection Studio Editor
  const studioEditorEl = document.getElementById('react-collection-studio-editor');
  if (studioEditorEl && !studioEditorEl.dataset.mounted) {
    try {
      const collection = safeJsonParse(studioEditorEl.dataset.collection, {});
      const initialFilms = safeJsonParse(studioEditorEl.dataset.initialFilms, []);
      const csrfToken = studioEditorEl.dataset.csrf || '';
      studioEditorEl.dataset.mounted = 'true';
      createRoot(studioEditorEl).render(
        <CollectionStudioEditor
          collection={collection}
          initialFilms={initialFilms}
          csrfToken={csrfToken}
        />
      );
    } catch (e) {
      console.error('Failed to mount CollectionStudioEditor', e);
    }
  }

  // 14. Mount Episode Comments Component (Series Watch Page with spoiled library)
  const episodeCommentsContainer = document.getElementById('react-episode-comments');
  if (episodeCommentsContainer && !episodeCommentsContainer.dataset.mounted) {
    try {
      const filmId = parseInt(episodeCommentsContainer.dataset.filmId, 10);
      const initialSeason = parseInt(episodeCommentsContainer.dataset.season || '1', 10);
      const initialEpisode = parseInt(episodeCommentsContainer.dataset.episode || '1', 10);
      const isLoggedIn = episodeCommentsContainer.dataset.isLoggedIn === 'true';
      const userName = episodeCommentsContainer.dataset.userName || '';
      const loginUrl = episodeCommentsContainer.dataset.loginUrl || '/login';
      const csrfToken = episodeCommentsContainer.dataset.csrf || '';

      episodeCommentsContainer.dataset.mounted = 'true';
      createRoot(episodeCommentsContainer).render(
        <EpisodeComments
          filmId={filmId}
          initialSeason={initialSeason}
          initialEpisode={initialEpisode}
          isLoggedIn={isLoggedIn}
          userName={userName}
          loginUrl={loginUrl}
          csrfToken={csrfToken}
        />
      );
    } catch (e) {
      console.error('Failed to mount EpisodeComments', e);
    }
  }

  // 15. Mount any standalone [data-react-spoiler] elements
  const spoilerElements = document.querySelectorAll('[data-react-spoiler]');
  spoilerElements.forEach((el) => {
    if (el.dataset.mounted) return;
    try {
      el.dataset.mounted = 'true';
      const text = el.textContent || '';
      createRoot(el).render(<SpoilerText>{text}</SpoilerText>);
    } catch (e) {
      console.error('Failed to mount SpoilerText', e);
    }
  });

  // 16. Mount Global React Modal System (Auto-append container if not present)
  let globalModalEl = document.getElementById('react-global-modal');
  if (!globalModalEl && document.body) {
    globalModalEl = document.createElement('div');
    globalModalEl.id = 'react-global-modal';
    document.body.appendChild(globalModalEl);
  }
  if (globalModalEl && !globalModalEl.dataset.mounted) {
    try {
      globalModalEl.dataset.mounted = 'true';
      createRoot(globalModalEl).render(<GlobalModal />);
    } catch (e) {
      console.error('Failed to mount GlobalModal', e);
    }
  }
}

// Auto mount when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initReactComponents);
} else {
  initReactComponents();
}

