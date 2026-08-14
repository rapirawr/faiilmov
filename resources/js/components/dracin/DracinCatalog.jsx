import React, { useState, useEffect, useRef } from 'react';

export default function DracinCatalog({
  initialSource = 'dramabox',
  initialFeed = [],
  sourcesList = {},
  csrfToken = '',
}) {
  const [activeSource, setActiveSource] = useState(initialSource);
  const [dramas, setDramas] = useState(initialFeed);
  const [loading, setLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searching, setSearching] = useState(false);
  const debounceTimerRef = useRef(null);

  // Debounced search when user types or switches source with an active query
  useEffect(() => {
    const trimmed = searchQuery.trim();
    if (!trimmed) {
      setSearchResults([]);
      setSearching(false);
      return;
    }

    setSearching(true);
    if (debounceTimerRef.current) {
      clearTimeout(debounceTimerRef.current);
    }

    debounceTimerRef.current = setTimeout(() => {
      performSearch(trimmed, activeSource);
    }, 350);

    return () => {
      if (debounceTimerRef.current) {
        clearTimeout(debounceTimerRef.current);
      }
    };
  }, [searchQuery, activeSource]);

  // Fetch all dramas for active source when NOT searching
  useEffect(() => {
    if (!searchQuery.trim()) {
      fetchDramas(activeSource);
    }
  }, [activeSource]);

  const fetchDramas = async (source) => {
    setLoading(true);
    try {
      const res = await fetch(`/dracin/api/feed?source=${encodeURIComponent(source)}&page=1`);
      if (res.ok) {
        const data = await res.json();
        setDramas(data.items || []);
      }
    } catch (err) {
      console.error('Failed to fetch dracin catalog feed:', err);
      setDramas([]);
    } finally {
      setLoading(false);
    }
  };

  const performSearch = async (query, source) => {
    if (!query) {
      setSearchResults([]);
      setSearching(false);
      return;
    }

    setSearching(true);
    try {
      const res = await fetch(`/dracin/api/search?source=${encodeURIComponent(source)}&query=${encodeURIComponent(query)}`);
      if (res.ok) {
        const data = await res.json();
        const items = data.items || data.results || (Array.isArray(data) ? data : []);
        setSearchResults(items);
      } else {
        // Fallback to client-side filtering on current dramas list
        const localMatches = (dramas || []).filter((d) => {
          const t = (d.title || d.name || '').toLowerCase();
          return t.includes(query.toLowerCase());
        });
        setSearchResults(localMatches);
      }
    } catch (err) {
      console.warn('Search API error, using client-side fallback:', err);
      const localMatches = (dramas || []).filter((d) => {
        const t = (d.title || d.name || '').toLowerCase();
        return t.includes(query.toLowerCase());
      });
      setSearchResults(localMatches);
    } finally {
      setSearching(false);
    }
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    if (debounceTimerRef.current) {
      clearTimeout(debounceTimerRef.current);
    }
    const trimmed = searchQuery.trim();
    if (trimmed) {
      performSearch(trimmed, activeSource);
    }
  };

  const isSearchingState = searchQuery.trim().length > 0;
  const displayList = isSearchingState ? searchResults : dramas;

  return (
    <div className="space-y-8 select-none">
      
      {/* Page Title & Search Bar Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/10 pb-5">
        <div>
          <div className="flex items-center gap-2.5">
            <h1 className="font-serif font-bold text-2xl sm:text-4xl text-white tracking-tight">
              Katalog Dracin
            </h1>
            <span className="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30">
              Short Drama
            </span>
          </div>
          <p className="text-zinc-400 text-xs sm:text-sm mt-1">
            Koleksi serial drama pendek China vertikal gratis & subtitle Indonesia.
          </p>
        </div>

        {/* Inline Search Bar */}
        <form onSubmit={handleSearchSubmit} className="relative w-full sm:w-80">
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Cari judul drama pendek..."
            className="w-full pl-9 pr-8 py-2.5 bg-dark-900/90 border border-white/15 rounded-2xl text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-white/40 transition-all shadow-inner"
          />
          <svg
            className="w-4 h-4 text-zinc-400 absolute left-3 top-3"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          {searchQuery && (
            <button
              type="button"
              onClick={() => {
                setSearchQuery('');
                setSearchResults([]);
              }}
              className="absolute right-3 top-3 text-zinc-400 hover:text-white cursor-pointer"
            >
              <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          )}
        </form>
      </div>

      {/* Source / Provider Pills Row */}
      <div className="flex flex-col space-y-2.5">
        <div className="flex items-center justify-between">
          <span className="text-[11px] font-extrabold uppercase tracking-widest text-zinc-400">
            Pilih Provider / Platform
          </span>
          <span className="text-[11px] font-semibold text-zinc-500">
            {displayList.length} Drama Tersedia
          </span>
        </div>

        <div className="flex gap-2 overflow-x-auto no-scrollbar pb-1">
          {Object.entries(sourcesList || {}).map(([key, label]) => (
            <button
              key={key}
              onClick={() => {
                setActiveSource(key);
                setSearchQuery('');
                setSearchResults([]);
              }}
              className={`px-4 py-2 rounded-2xl text-xs font-extrabold uppercase tracking-wider whitespace-nowrap transition-all border cursor-pointer ${
                activeSource === key
                  ? 'bg-white text-zinc-950 border-white shadow-md scale-[1.02]'
                  : 'bg-dark-900 text-zinc-400 border-white/10 hover:text-white hover:border-white/20'
              }`}
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      {/* Search State Info */}
      {isSearchingState && (
        <div className="flex items-center justify-between text-xs text-zinc-400 border-b border-white/10 pb-3">
          <div className="flex items-center gap-2">
            {searching ? (
              <span className="flex items-center gap-2 text-zinc-400">
                <div className="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" />
                Mencari: <strong className="text-white">"{searchQuery}"</strong>...
              </span>
            ) : (
              <span>
                Hasil pencarian untuk: <strong className="text-white">"{searchQuery}"</strong> ({searchResults.length} drama ditemukan)
              </span>
            )}
          </div>
          <button
            onClick={() => {
              setSearchQuery('');
              setSearchResults([]);
            }}
            className="text-xs text-rose-400 hover:text-rose-300 underline font-semibold cursor-pointer"
          >
            Kembali ke katalog
          </button>
        </div>
      )}

      {/* Loading Spinner / Skeletons */}
      {(loading || searching) ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          {Array.from({ length: 12 }).map((_, i) => (
            <div
              key={i}
              className="flex flex-col bg-dark-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl animate-pulse"
            >
              {/* Poster Skeleton */}
              <div className="relative aspect-[2/3] w-full bg-white/5 overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent animate-pulse" />
                {/* EP Badge Skeleton */}
                <div className="absolute top-2.5 left-2.5 w-12 h-4 rounded bg-white/10" />
                {/* Source Badge Skeleton */}
                <div className="absolute top-2.5 right-2.5 w-10 h-4 rounded bg-white/10" />
              </div>

              {/* Info Skeleton */}
              <div className="p-3 flex flex-col space-y-2">
                <div className="h-3.5 bg-white/10 rounded w-11/12" />
                <div className="h-3.5 bg-white/5 rounded w-3/4" />
                <div className="flex items-center justify-between pt-1">
                  <div className="h-2.5 bg-white/10 rounded w-10" />
                  <div className="h-2.5 bg-white/5 rounded w-6" />
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : displayList.length > 0 ? (
        /* Grid of Drama Short Cards */
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          {displayList.map((drama) => {
            const dramaId = String(drama.id || drama.dramaId || '');
            if (!dramaId) return null;

            const title = drama.title || drama.name || 'Untitled Dracin';
            const poster = drama.posterImg || drama.cover || drama.poster || drama.horizontalCover || '';
            const epsCount = drama.episodes || drama.totalEpisodes || drama.chapterCount || 50;

            return (
              <a
                key={`${activeSource}-${dramaId}`}
                href={`/dracin/${activeSource}/${dramaId}`}
                className="group relative flex flex-col bg-dark-950/80 border border-white/10 rounded-2xl overflow-hidden hover:border-rose-500/50 hover:shadow-2xl hover:shadow-rose-500/10 transition-all duration-300"
              >
                {/* Poster Image Container */}
                <div className="relative aspect-[2/3] w-full overflow-hidden bg-dark-900">
                  {poster ? (
                    <img
                      src={poster}
                      alt={title}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                      loading="lazy"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-zinc-700">
                      <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                      </svg>
                    </div>
                  )}

                  {/* Gradient Overlay */}
                  <div className="absolute inset-0 bg-gradient-to-t from-dark-950 via-dark-950/20 to-transparent opacity-80 group-hover:opacity-90 transition-opacity" />

                  {/* Episode Count Badge */}
                  <div className="absolute top-2.5 left-2.5 z-10 px-2 py-0.5 rounded-lg bg-black/80 backdrop-blur-md border border-white/20 text-[10px] font-extrabold uppercase text-white shadow-md">
                    {epsCount} EP
                  </div>

                  {/* Source Provider Badge */}
                  <div className="absolute top-2.5 right-2.5 z-10 px-2 py-0.5 rounded-lg bg-white text-zinc-950 text-[9px] font-extrabold uppercase shadow-md">
                    {activeSource}
                  </div>

                  {/* Play Button Overlay on Hover */}
                  <div className="absolute inset-0 z-20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40 backdrop-blur-xs">
                    <div className="px-3 py-2 rounded-full bg-white text-zinc-950 font-extrabold text-xs uppercase tracking-wider flex items-center gap-1.5 shadow-2xl transform scale-90 group-hover:scale-100 transition-transform">
                      <svg className="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                      </svg>
                      <span>Putar</span>
                    </div>
                  </div>
                </div>

                {/* Title & Info Footer */}
                <div className="p-3 flex flex-col space-y-1">
                  <h3 className="text-xs font-bold text-white uppercase tracking-wide line-clamp-2 leading-tight group-hover:text-rose-300 transition-colors">
                    {title}
                  </h3>
                  <div className="flex items-center justify-between text-[10px] text-zinc-500 font-mono pt-1">
                    <span>Eksklusif</span>
                    <span className="text-rose-400/80 font-bold">HD</span>
                  </div>
                </div>
              </a>
            );
          })}
        </div>
      ) : (
        /* Empty State */
        <div className="text-center py-20 bg-dark-900/40 border border-white/10 rounded-3xl p-8 space-y-3">
          <svg className="w-12 h-12 text-zinc-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
          </svg>
          <h3 className="text-sm font-extrabold text-white uppercase tracking-wider">
            Tidak Ada Drama Ditemukan
          </h3>
          <p className="text-xs text-zinc-500 max-w-xs mx-auto">
            Coba ganti provider platform atau gunakan kata kunci pencarian yang lain.
          </p>
        </div>
      )}
    </div>
  );
}
