import React, { useState, useEffect } from 'react';

export default function DracinDrawer({ isOpen, onClose, currentSource, sourcesList, onSelectDrama, csrfToken }) {
  const [activeSource, setActiveSource] = useState(currentSource || 'dramabox');
  const [dramas, setDramas] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [searching, setSearching] = useState(false);

  const debounceTimerRef = React.useRef(null);

  useEffect(() => {
    if (!isOpen) return;

    const trimmed = searchQuery.trim();
    if (!trimmed) {
      setSearchResults([]);
      setSearching(false);
      fetchDramas(activeSource);
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
  }, [isOpen, searchQuery, activeSource]);

  const fetchDramas = async (source) => {
    setLoading(true);
    try {
      const res = await fetch(`/dracin/api/feed?source=${encodeURIComponent(source)}&page=1`);
      const data = await res.json();
      setDramas(data.items || []);
    } catch (err) {
      console.error('Failed to fetch dracin feed:', err);
      setDramas([]);
    } finally {
      setLoading(false);
    }
  };

  const performSearch = async (query, source) => {
    if (!query) return;
    setSearching(true);
    try {
      const res = await fetch(`/dracin/api/search?source=${encodeURIComponent(source)}&query=${encodeURIComponent(query)}`);
      const data = await res.json();
      setSearchResults(data.items || data.results || (Array.isArray(data) ? data : []));
    } catch (err) {
      console.error('Failed to search dracin:', err);
      const localMatches = (dramas || []).filter((d) => {
        const t = (d.title || d.name || '').toLowerCase();
        return t.includes(query.toLowerCase());
      });
      setSearchResults(localMatches);
    } finally {
      setSearching(false);
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    if (debounceTimerRef.current) {
      clearTimeout(debounceTimerRef.current);
    }
    const trimmed = searchQuery.trim();
    if (trimmed) {
      performSearch(trimmed, activeSource);
    }
  };

  if (!isOpen) return null;

  const displayList = searchQuery.trim() ? searchResults : dramas;

  return (
    <div className="fixed inset-0 z-50 flex flex-col justify-end bg-black/80 backdrop-blur-md transition-opacity duration-300">
      {/* Backdrop click to close */}
      <div className="absolute inset-0" onClick={onClose} />

      {/* Drawer Content */}
      <div className="relative w-full max-w-[480px] mx-auto bg-zinc-950 border-t border-zinc-800 rounded-t-3xl max-h-[85vh] flex flex-col shadow-2xl overflow-hidden z-10 text-white">
        
        {/* Drawer Handle & Header */}
        <div className="p-4 pb-2 border-b border-zinc-900 flex flex-col items-center">
          <div className="w-12 h-1 bg-zinc-700 rounded-full mb-3" />
          <div className="w-full flex items-center justify-between px-2">
            <h3 className="font-sans font-extrabold text-sm uppercase tracking-wider text-white">
              Pilih Drama Short
            </h3>
            <button
              onClick={onClose}
              className="p-1.5 rounded-full bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800 transition-colors cursor-pointer"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        {/* Source Selector Pills (Monochrome) */}
        <div className="px-4 py-2.5 border-b border-zinc-900 overflow-x-auto flex gap-2 no-scrollbar">
          {Object.entries(sourcesList || {}).map(([key, label]) => (
            <button
              key={key}
              onClick={() => {
                setActiveSource(key);
                setSearchQuery('');
                setSearchResults([]);
              }}
              className={`px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider whitespace-nowrap transition-all border cursor-pointer ${
                activeSource === key
                  ? 'bg-white text-black border-white'
                  : 'bg-zinc-900 text-zinc-400 border-zinc-800 hover:text-white hover:border-zinc-700'
              }`}
            >
              {label}
            </button>
          ))}
        </div>

        {/* Search Input Bar */}
        <div className="p-4 py-2.5 border-b border-zinc-900">
          <form onSubmit={handleSearch} className="relative flex items-center">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder={`Cari drama di ${sourcesList[activeSource] || activeSource}...`}
              className="w-full bg-zinc-900 text-xs text-white placeholder-zinc-500 pl-9 pr-8 py-2 rounded-xl border border-zinc-800 focus:outline-none focus:border-zinc-600"
            />
            <svg className="w-4 h-4 text-zinc-500 absolute left-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            {searchQuery && (
              <button
                type="button"
                onClick={() => {
                  setSearchQuery('');
                  setSearchResults([]);
                }}
                className="absolute right-2.5 text-zinc-500 hover:text-white"
              >
                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            )}
          </form>
        </div>

        {/* Drama Grid List */}
        <div className="flex-1 overflow-y-auto p-4 space-y-3">
          {(loading || searching) ? (
            <div className="py-12 flex flex-col items-center justify-center text-zinc-500 space-y-2">
              <div className="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin" />
              <span className="text-xs uppercase font-semibold tracking-wider">Memuat...</span>
            </div>
          ) : displayList.length === 0 ? (
            <div className="py-12 text-center text-xs text-zinc-500">
              Tidak ada drama ditemukan.
            </div>
          ) : (
            <div className="grid grid-cols-3 gap-3">
              {displayList.map((item, idx) => {
                const itemId = String(item.id || item.dramaId || '');
                const title = item.title || item.name || 'Untitled';
                const poster = item.cover || item.poster || item.posterImg || item.horizontalCover || '';
                const eps = item.episodes || item.totalEpisodes || '?';

                return (
                  <button
                    key={itemId || idx}
                    onClick={() => {
                      onSelectDrama(activeSource, itemId, item);
                      onClose();
                    }}
                    className="group flex flex-col text-left space-y-1.5 focus:outline-none cursor-pointer"
                  >
                    <div className="relative aspect-[3/4] w-full bg-zinc-900 rounded-xl overflow-hidden border border-zinc-800 group-hover:border-zinc-500 transition-all">
                      {poster ? (
                        <img src={poster} alt={title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center text-zinc-600 text-xs">No Cover</div>
                      )}
                      <div className="absolute top-1.5 right-1.5 px-1.5 py-0.5 bg-black/80 backdrop-blur-sm text-[10px] font-bold text-white rounded border border-white/20">
                        {eps} EP
                      </div>
                      <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2">
                        <span className="text-[10px] uppercase font-bold text-white tracking-wider flex items-center gap-1">
                          <span>Putar</span>
                          <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                          </svg>
                        </span>
                      </div>
                    </div>
                    <h4 className="text-xs font-semibold text-zinc-200 line-clamp-2 leading-tight group-hover:text-white transition-colors">
                      {title}
                    </h4>
                  </button>
                );
              })}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
