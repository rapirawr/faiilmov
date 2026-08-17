import React, { useState, useEffect, useCallback, useRef } from 'react';
import { 
  Search, 
  DownloadCloud, 
  CheckCircle2, 
  AlertTriangle, 
  ExternalLink, 
  RefreshCw, 
  Film, 
  Tv, 
  Smartphone, 
  Star, 
  Check, 
  X, 
  Layers, 
  Filter,
  Eye,
  Info,
  Calendar,
  Sparkles,
  Loader2,
  Clock,
  ShieldCheck
} from 'lucide-react';

export default function FilmImporter({ searchUrl, detailUrl, importUrl, importBatchUrl, csrfToken }) {
  const [query, setQuery] = useState('');
  const [provider, setProvider] = useState('all'); // 'all', 'moviebox', 'dramabox', 'reelshort', 'shortmax'
  const [typeFilter, setTypeFilter] = useState('all'); // 'all', 'movie', 'series', 'dracin'
  const [statusFilter, setStatusFilter] = useState('all'); // 'all', 'unimported', 'imported'
  
  const [results, setResults] = useState([]);
  const [isSearching, setIsSearching] = useState(false);
  const [hasSearched, setHasSearched] = useState(false);
  const [searchError, setSearchError] = useState(null);

  const [importingIds, setImportingIds] = useState(new Set());
  const [selectedIds, setSelectedIds] = useState(new Set());
  const [isBatchImporting, setIsBatchImporting] = useState(false);

  const [previewFilm, setPreviewFilm] = useState(null);
  const [isPreviewLoading, setIsPreviewLoading] = useState(false);
  const [toast, setToast] = useState(null);

  const searchInputRef = useRef(null);

  const showToast = (type, message) => {
    setToast({ type, message });
    setTimeout(() => setToast(null), 4000);
  };

  const openPreviewModal = async (film) => {
    setPreviewFilm(film);

    // If synopsis is missing or empty, fetch full details on-demand
    if (!film.synopsis || film.synopsis.trim() === '') {
      setIsPreviewLoading(true);
      try {
        const targetUrl = detailUrl || '/admin/api/films/external-detail';
        const res = await fetch(targetUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify({
            subject_id: film.subject_id,
            source: film.source,
          }),
        });

        if (res.ok) {
          const json = await res.json();
          if (json.status === 'success' && json.detail) {
            setPreviewFilm((prev) => (prev && prev.subject_id === film.subject_id ? { ...prev, ...json.detail } : prev));
            setResults((prev) =>
              prev.map((it) => (it.subject_id === film.subject_id ? { ...it, ...json.detail } : it))
            );
          }
        }
      } catch (e) {
        console.error('Failed to fetch full film detail:', e);
      } finally {
        setIsPreviewLoading(false);
      }
    }
  };

  const handleSearch = useCallback(async (e) => {
    if (e) e.preventDefault();
    const cleanQ = query.trim();
    if (!cleanQ) return;

    setIsSearching(true);
    setSearchError(null);
    setSelectedIds(new Set());

    try {
      const res = await fetch(searchUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          query: cleanQ,
          provider: provider,
          type: typeFilter,
        }),
      });

      if (res.ok) {
        const json = await res.json();
        setResults(json.results || []);
        setHasSearched(true);
      } else {
        const err = await res.json().catch(() => ({}));
        setSearchError(err.message || `Gagal mencari (HTTP ${res.status})`);
      }
    } catch (err) {
      setSearchError(`Kendala jaringan: ${err.message}`);
    } finally {
      setIsSearching(false);
    }
  }, [query, provider, typeFilter, searchUrl, csrfToken]);

  // Import single item
  const handleImportSingle = async (item) => {
    const subjectId = item.subject_id;
    if (importingIds.has(subjectId)) return;

    setImportingIds((prev) => new Set(prev).add(subjectId));

    try {
      const res = await fetch(importUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          subject_id: subjectId,
          source: item.source,
          title: item.title,
          poster_url: item.poster_url,
          subject_type: item.subject_type,
        }),
      });

      const json = await res.json();

      if (res.ok && json.status === 'success') {
        showToast('success', json.message || `Berhasil mengimpor '${item.title}'!`);
        
        // Update item in results to imported
        setResults((prev) =>
          prev.map((r) =>
            r.subject_id === subjectId
              ? { ...r, is_imported: true, local_film_id: json.film?.id, local_edit_url: json.film?.edit_url }
              : r
          )
        );

        // Deselect if was selected in batch
        setSelectedIds((prev) => {
          const next = new Set(prev);
          next.delete(subjectId);
          return next;
        });
      } else {
        showToast('error', json.message || 'Gagal mengimpor film.');
      }
    } catch (err) {
      showToast('error', `Gagal mengimpor: ${err.message}`);
    } finally {
      setImportingIds((prev) => {
        const next = new Set(prev);
        next.delete(subjectId);
        return next;
      });
    }
  };

  // Import batch
  const handleImportBatch = async () => {
    const itemsToImport = results.filter((r) => selectedIds.has(r.subject_id) && !r.is_imported);
    if (itemsToImport.length === 0) return;

    setIsBatchImporting(true);

    try {
      const res = await fetch(importBatchUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          items: itemsToImport.map((i) => ({
            subject_id: i.subject_id,
            source: i.source,
            title: i.title,
            subject_type: i.subject_type,
          })),
        }),
      });

      const json = await res.json();

      if (res.ok && json.status === 'success') {
        showToast('success', json.message || `Berhasil mengimpor ${json.imported_count} film!`);

        // Update imported films in results
        const importedMap = json.films || {};
        setResults((prev) =>
          prev.map((r) => {
            if (importedMap[r.subject_id]) {
              return {
                ...r,
                is_imported: true,
                local_film_id: importedMap[r.subject_id].id,
                local_edit_url: importedMap[r.subject_id].edit_url,
              };
            }
            return r;
          })
        );

        setSelectedIds(new Set());
      } else {
        showToast('error', json.message || 'Gagal melakukan impor massal.');
      }
    } catch (err) {
      showToast('error', `Gagal impor massal: ${err.message}`);
    } finally {
      setIsBatchImporting(false);
    }
  };

  // Filtered results based on status
  const filteredResults = results.filter((r) => {
    if (statusFilter === 'unimported') return !r.is_imported;
    if (statusFilter === 'imported') return r.is_imported;
    return true;
  });

  const unimportedCount = results.filter((r) => !r.is_imported).length;
  const allUnimportedSelected = unimportedCount > 0 && results.filter((r) => !r.is_imported).every((r) => selectedIds.has(r.subject_id));

  const toggleSelectAllUnimported = () => {
    if (allUnimportedSelected) {
      setSelectedIds(new Set());
    } else {
      const unimported = results.filter((r) => !r.is_imported).map((r) => r.subject_id);
      setSelectedIds(new Set(unimported));
    }
  };

  const toggleSelectItem = (subjectId) => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(subjectId)) next.delete(subjectId);
      else next.add(subjectId);
      return next;
    });
  };

  const getSubjectTypeBadge = (stype) => {
    switch (stype?.toLowerCase()) {
      case 'dracin':
        return { label: 'DRACIN', bg: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30', icon: Smartphone };
      case 'series':
        return { label: 'SERIES', bg: 'bg-sky-500/10 text-sky-400 border-sky-500/30', icon: Tv };
      case 'movie':
      default:
        return { label: 'MOVIE', bg: 'bg-amber-500/10 text-amber-400 border-amber-500/30', icon: Film };
    }
  };

  return (
    <div className="space-y-6">
      {/* Toast Notification */}
      {toast && (
        <div className={`fixed bottom-6 right-6 z-50 p-4 rounded-xl border flex items-center gap-3 shadow-2xl transition-all ${toast.type === 'success' ? 'bg-zinc-900 border-emerald-500/50 text-[#E4E2DD]' : 'bg-zinc-900 border-rose-500/50 text-rose-300'}`}>
          {toast.type === 'success' ? <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0" /> : <AlertTriangle className="w-5 h-5 text-rose-400 shrink-0" />}
          <span className="text-xs font-semibold">{toast.message}</span>
          <button onClick={() => setToast(null)} className="p-1 text-zinc-400 hover:text-[#E4E2DD] transition-colors ml-2">
            <X className="w-3.5 h-3.5" />
          </button>
        </div>
      )}

      {/* Header & Description */}
      <div className="p-5 rounded-2xl bg-zinc-900 border border-zinc-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div className="flex items-center gap-3.5">
          <div className="w-11 h-11 rounded-xl bg-zinc-800 border border-zinc-700 flex items-center justify-center text-[#E4E2DD] shrink-0">
            <DownloadCloud className="w-6 h-6" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">
              Cari & Impor Film Eksternal
            </h2>
            <p className="text-xs text-zinc-400 mt-0.5">
              Cari judul film, serial, atau drama pendek yang belum ada di katalog Faiilmov, lalu impor dengan 1 kali klik.
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2 text-xs text-zinc-400 font-mono">
          <span className="px-2.5 py-1 rounded-lg bg-zinc-950 border border-zinc-800 text-zinc-300">
            MovieBox Mesh
          </span>
          <span className="px-2.5 py-1 rounded-lg bg-zinc-950 border border-zinc-800 text-zinc-300">
            Anichin Dracin
          </span>
        </div>
      </div>

      {/* Search & Filter Deck */}
      <div className="p-5 rounded-2xl bg-zinc-900 border border-zinc-800 shadow-sm space-y-4">
        {/* Search Input Form */}
        <form onSubmit={handleSearch} className="flex flex-col sm:flex-row items-stretch gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              ref={searchInputRef}
              type="text"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Ketik judul film, series, atau drama pendek... (contoh: Spider-Man, Interstellar, My Demon)"
              className="w-full pl-10 pr-10 py-3 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-[#E4E2DD] placeholder-zinc-500 focus:outline-none focus:border-zinc-600 transition-colors"
              autoFocus
            />
            {query && (
              <button
                type="button"
                onClick={() => { setQuery(''); searchInputRef.current?.focus(); }}
                className="absolute right-3.5 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-[#E4E2DD]"
              >
                <X className="w-4 h-4" />
              </button>
            )}
          </div>

          <button
            type="submit"
            disabled={isSearching || !query.trim()}
            className="px-6 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 active:scale-95 text-black text-sm font-bold transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shadow-md"
          >
            {isSearching ? (
              <>
                <RefreshCw className="w-4 h-4 animate-spin" />
                <span>Mencari di API...</span>
              </>
            ) : (
              <>
                <Search className="w-4 h-4" />
                <span>Cari Film</span>
              </>
            )}
          </button>
        </form>

        {/* Filter Controls Row */}
        <div className="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-zinc-800/80 text-xs">
          <div className="flex flex-wrap items-center gap-2">
            {/* Provider Selector */}
            <div className="flex items-center gap-1.5">
              <span className="text-zinc-400 font-medium">Provider:</span>
              <select
                value={provider}
                onChange={(e) => setProvider(e.target.value)}
                className="px-2.5 py-1.5 rounded-lg bg-zinc-950 border border-zinc-800 text-xs text-[#E4E2DD] focus:outline-none focus:border-zinc-600 cursor-pointer"
              >
                <option value="all">Semua Provider (MovieBox + Dracin)</option>
                <option value="moviebox">MovieBox (Film & Series)</option>
                <option value="dramabox">DramaBox (Dracin)</option>
                <option value="reelshort">ReelShort (Dracin)</option>
                <option value="shortmax">ShortMax (Dracin)</option>
              </select>
            </div>

            {/* Type Selector */}
            <div className="flex items-center gap-1.5">
              <span className="text-zinc-400 font-medium">Tipe:</span>
              <select
                value={typeFilter}
                onChange={(e) => setTypeFilter(e.target.value)}
                className="px-2.5 py-1.5 rounded-lg bg-zinc-950 border border-zinc-800 text-xs text-[#E4E2DD] focus:outline-none focus:border-zinc-600 cursor-pointer"
              >
                <option value="all">Semua Tipe</option>
                <option value="movie">Movie</option>
                <option value="series">TV Series</option>
                <option value="dracin">Drama Pendek</option>
              </select>
            </div>
          </div>

          {/* Status Filter Pills (Only visible when results exist) */}
          {results.length > 0 && (
            <div className="flex items-center bg-zinc-950 p-1 rounded-xl border border-zinc-800">
              <button
                type="button"
                onClick={() => setStatusFilter('all')}
                className={`px-2.5 py-1 rounded-lg transition-colors cursor-pointer ${statusFilter === 'all' ? 'bg-zinc-800 text-[#E4E2DD] font-semibold' : 'text-zinc-400 hover:text-zinc-200'}`}
              >
                Semua ({results.length})
              </button>
              <button
                type="button"
                onClick={() => setStatusFilter('unimported')}
                className={`px-2.5 py-1 rounded-lg transition-colors cursor-pointer ${statusFilter === 'unimported' ? 'bg-zinc-800 text-amber-400 font-semibold' : 'text-zinc-400 hover:text-zinc-200'}`}
              >
                Belum Ada ({unimportedCount})
              </button>
              <button
                type="button"
                onClick={() => setStatusFilter('imported')}
                className={`px-2.5 py-1 rounded-lg transition-colors cursor-pointer ${statusFilter === 'imported' ? 'bg-zinc-800 text-emerald-400 font-semibold' : 'text-zinc-400 hover:text-zinc-200'}`}
              >
                Sudah Ada ({results.length - unimportedCount})
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Error Message */}
      {searchError && (
        <div className="p-4 rounded-xl bg-zinc-900 border border-rose-500/40 text-rose-300 text-xs flex items-center gap-2.5">
          <AlertTriangle className="w-4 h-4 text-rose-400 shrink-0" />
          <span>{searchError}</span>
        </div>
      )}

      {/* Batch Actions Bar (Visible when there are unimported items) */}
      {unimportedCount > 0 && (
        <div className="p-3.5 rounded-xl bg-zinc-900 border border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={toggleSelectAllUnimported}
              className="flex items-center gap-2 font-medium text-zinc-300 hover:text-[#E4E2DD] cursor-pointer"
            >
              <div className={`w-4 h-4 rounded border flex items-center justify-center ${allUnimportedSelected ? 'bg-amber-500 border-amber-500 text-black' : 'border-zinc-700 bg-zinc-950'}`}>
                {allUnimportedSelected && <Check className="w-3 h-3 stroke-[3]" />}
              </div>
              <span>{allUnimportedSelected ? 'Batalkan Pilihan Semua' : 'Pilih Semua yang Belum Diimpor'}</span>
            </button>

            <span className="text-zinc-500 font-mono">
              ({selectedIds.size} dipilih)
            </span>
          </div>

          {selectedIds.size > 0 && (
            <button
              type="button"
              onClick={handleImportBatch}
              disabled={isBatchImporting}
              className="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              {isBatchImporting ? (
                <>
                  <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                  <span>Mengimpor Massal...</span>
                </>
              ) : (
                <>
                  <DownloadCloud className="w-3.5 h-3.5" />
                  <span>Impor {selectedIds.size} Film Terpilih</span>
                </>
              )}
            </button>
          )}
        </div>
      )}

      {/* Loading Skeleton */}
      {isSearching && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
            <div key={i} className="p-4 rounded-2xl bg-zinc-900 border border-zinc-800 space-y-3">
              <div className="w-full aspect-[2/3] rounded-xl bg-zinc-950" />
              <div className="h-4 bg-zinc-800 rounded w-3/4" />
              <div className="h-3 bg-zinc-800/60 rounded w-1/2" />
              <div className="h-9 bg-zinc-800 rounded-xl w-full" />
            </div>
          ))}
        </div>
      )}

      {/* Search Results Grid */}
      {!isSearching && hasSearched && (
        <>
          {filteredResults.length === 0 ? (
            <div className="p-12 text-center rounded-2xl bg-zinc-900 border border-zinc-800 space-y-2">
              <Film className="w-10 h-10 text-zinc-600 mx-auto" />
              <h3 className="text-base font-bold text-[#E4E2DD] font-['Outfit']">
                Tidak ada film ditemukan
              </h3>
              <p className="text-xs text-zinc-400 max-w-md mx-auto">
                Coba gunakan kata kunci yang lebih spesifik atau ubah filter provider (MovieBox / Dracin).
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              {filteredResults.map((item) => {
                const isImported = item.is_imported;
                const isImporting = importingIds.has(item.subject_id);
                const isSelected = selectedIds.has(item.subject_id);
                const typeBadge = getSubjectTypeBadge(item.subject_type);

                return (
                  <div
                    key={item.subject_id}
                    className={`p-4 rounded-2xl bg-zinc-900 border transition-all flex flex-col justify-between space-y-3 relative group ${isSelected ? 'border-amber-500/70 bg-zinc-900/90' : isImported ? 'border-zinc-800' : 'border-zinc-800 hover:border-zinc-700'}`}
                  >
                    {/* Top Checkbox & Provider Badge */}
                    <div className="flex items-center justify-between gap-2">
                      {!isImported ? (
                        <button
                          type="button"
                          onClick={() => toggleSelectItem(item.subject_id)}
                          className="flex items-center gap-1.5 text-xs text-zinc-400 hover:text-[#E4E2DD] cursor-pointer"
                        >
                          <div className={`w-4 h-4 rounded border flex items-center justify-center ${isSelected ? 'bg-amber-500 border-amber-500 text-black' : 'border-zinc-700 bg-zinc-950'}`}>
                            {isSelected && <Check className="w-3 h-3 stroke-[3]" />}
                          </div>
                        </button>
                      ) : (
                        <span className="flex items-center gap-1 text-[10px] font-mono font-semibold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-md">
                          <CheckCircle2 className="w-3 h-3" />
                          Di Database
                        </span>
                      )}

                      <span className="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-zinc-950 border border-zinc-800 text-zinc-400">
                        {item.provider_name}
                      </span>
                    </div>

                    {/* Poster Image with Type & Rating Badges */}
                    <div 
                      onClick={() => openPreviewModal(item)}
                      className="relative w-full aspect-[2/3] rounded-xl overflow-hidden bg-zinc-950 border border-zinc-800 cursor-pointer"
                    >
                      <img
                        src={item.poster_url || '/images/placeholder.jpg'}
                        alt={item.title}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        loading="lazy"
                        onError={(e) => { e.currentTarget.src = '/images/placeholder.jpg'; }}
                      />

                      {/* Type Badge on Poster */}
                      <div className="absolute top-2 left-2 flex items-center gap-1">
                        <span className={`px-2 py-0.5 rounded text-[10px] font-mono font-bold border backdrop-blur-md ${typeBadge.bg}`}>
                          {typeBadge.label}
                        </span>
                      </div>

                      {/* Rating Badge */}
                      {item.rating > 0 && (
                        <div className="absolute top-2 right-2 flex items-center gap-1 px-1.5 py-0.5 rounded bg-black/70 backdrop-blur-md border border-zinc-800 text-[10px] font-mono font-bold text-amber-400">
                          <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                          <span>{item.rating.toFixed(1)}</span>
                        </div>
                      )}

                      {/* Quick Info Overlay */}
                      <div className="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center p-3 text-center">
                        <div className="space-y-1">
                          <Info className="w-5 h-5 text-[#E4E2DD] mx-auto" />
                          <span className="text-[11px] font-semibold text-[#E4E2DD] block">Klik untuk Preview & Sinopsis</span>
                        </div>
                      </div>
                    </div>

                    {/* Metadata & Title */}
                    <div className="space-y-1">
                      <div className="flex items-center justify-between text-[11px] text-zinc-400 font-mono">
                        <span>{item.release_year || 'N/A'}</span>
                        {item.genres?.length > 0 && (
                          <span className="truncate max-w-[120px]">{item.genres[0]}</span>
                        )}
                      </div>

                      <h4 
                        onClick={() => openPreviewModal(item)}
                        className="text-sm font-bold text-[#E4E2DD] font-['Outfit'] line-clamp-1 group-hover:text-amber-400 transition-colors cursor-pointer"
                        title={item.title}
                      >
                        {item.title}
                      </h4>

                      <p className="text-xs text-zinc-400 line-clamp-2 leading-relaxed">
                        {item.synopsis || 'Klik untuk memuat sinopsis lengkap dari API provider.'}
                      </p>
                    </div>

                    {/* Action Button */}
                    <div className="pt-2 border-t border-zinc-800">
                      {isImported ? (
                        <a
                          href={item.local_edit_url || `/admin/films`}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="w-full py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-semibold text-zinc-200 hover:text-[#E4E2DD] border border-zinc-700 transition-colors flex items-center justify-center gap-1.5"
                        >
                          <span>Kelola di Katalog</span>
                          <ExternalLink className="w-3.5 h-3.5 text-zinc-400" />
                        </a>
                      ) : (
                        <button
                          type="button"
                          onClick={() => handleImportSingle(item)}
                          disabled={isImporting}
                          className="w-full py-2 rounded-xl bg-amber-500 hover:bg-amber-400 active:scale-95 text-xs font-bold text-black transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50 shadow-sm"
                        >
                          {isImporting ? (
                            <>
                              <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                              <span>Mengimpor...</span>
                            </>
                          ) : (
                            <>
                              <DownloadCloud className="w-3.5 h-3.5" />
                              <span>Impor ke Faiilmov</span>
                            </>
                          )}
                        </button>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </>
      )}

      {/* Initial Empty State before search */}
      {!isSearching && !hasSearched && (
        <div className="p-12 text-center rounded-2xl bg-zinc-900 border border-zinc-800 space-y-3">
          <div className="w-12 h-12 rounded-2xl bg-zinc-800 border border-zinc-700 flex items-center justify-center text-zinc-400 mx-auto">
            <Search className="w-6 h-6" />
          </div>
          <div>
            <h3 className="text-base font-bold text-[#E4E2DD] font-['Outfit']">
              Mulai Pencarian Film Eksternal
            </h3>
            <p className="text-xs text-zinc-400 max-w-md mx-auto mt-1">
              Ketik kata kunci judul film di atas untuk memindai ribuan katalog MovieBox & platform Dracin secara langsung.
            </p>
          </div>
        </div>
      )}

      {/* Film Detail Preview Modal */}
      {previewFilm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div className="w-full max-w-2xl rounded-2xl bg-zinc-900 border border-zinc-800 shadow-2xl p-5 space-y-4 max-h-[90vh] overflow-y-auto">
            {/* Modal Header */}
            <div className="flex items-center justify-between border-b border-zinc-800 pb-3">
              <div className="flex items-center gap-2">
                <Film className="w-5 h-5 text-amber-400" />
                <h4 className="text-sm font-bold text-[#E4E2DD] font-['Outfit']">
                  Detail & Sinopsis Film
                </h4>
              </div>
              <button
                type="button"
                onClick={() => setPreviewFilm(null)}
                className="p-1 rounded-lg text-zinc-400 hover:text-[#E4E2DD] hover:bg-zinc-800 transition-colors cursor-pointer"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Modal Body */}
            <div className="flex flex-col sm:flex-row gap-4">
              <img
                src={previewFilm.poster_url || '/images/placeholder.jpg'}
                alt={previewFilm.title}
                className="w-32 sm:w-44 aspect-[2/3] object-cover rounded-xl bg-zinc-950 border border-zinc-800 shrink-0 shadow-md"
                onError={(e) => { e.currentTarget.src = '/images/placeholder.jpg'; }}
              />

              <div className="space-y-3 flex-1 text-xs">
                {/* Meta Badges */}
                <div className="flex items-center gap-2 flex-wrap">
                  <span className="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500/10 border border-amber-500/30 text-amber-400">
                    {previewFilm.subject_type?.toUpperCase()}
                  </span>
                  <span className="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-zinc-800 border border-zinc-700 text-zinc-300">
                    {previewFilm.provider_name}
                  </span>
                  {previewFilm.release_year && (
                    <span className="text-zinc-400 font-mono flex items-center gap-1">
                      <Calendar className="w-3 h-3 text-zinc-500" />
                      {previewFilm.release_year}
                    </span>
                  )}
                  {previewFilm.duration && (
                    <span className="text-zinc-400 font-mono flex items-center gap-1">
                      <Clock className="w-3 h-3 text-zinc-500" />
                      {previewFilm.duration}
                    </span>
                  )}
                  {previewFilm.content_rating && (
                    <span className="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-zinc-800 text-zinc-400 border border-zinc-700">
                      {previewFilm.content_rating}
                    </span>
                  )}
                  {previewFilm.rating > 0 && (
                    <span className="text-amber-400 font-mono font-bold flex items-center gap-1 bg-amber-500/10 border border-amber-500/20 px-1.5 py-0.5 rounded">
                      <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                      {previewFilm.rating.toFixed(1)}
                    </span>
                  )}
                </div>

                {/* Title */}
                <div>
                  <h3 className="text-base font-bold text-[#E4E2DD] font-['Outfit']">
                    {previewFilm.title}
                  </h3>
                  {previewFilm.original_title && previewFilm.original_title !== previewFilm.title && (
                    <p className="text-zinc-400 text-xs italic mt-0.5">
                      Judul Asli: {previewFilm.original_title}
                    </p>
                  )}
                </div>

                {/* Synopsis Section */}
                <div className="space-y-1.5">
                  <span className="text-zinc-400 font-semibold block text-[11px] uppercase tracking-wider">
                    Sinopsis
                  </span>

                  {isPreviewLoading ? (
                    <div className="p-3 rounded-xl bg-zinc-950 border border-zinc-800 space-y-2">
                      <div className="flex items-center gap-2 text-amber-400 font-mono text-[11px]">
                        <Loader2 className="w-3.5 h-3.5 animate-spin" />
                        <span>Memuat sinopsis lengkap dari provider API...</span>
                      </div>
                      <div className="space-y-1 animate-pulse">
                        <div className="h-2.5 bg-zinc-800 rounded w-full"></div>
                        <div className="h-2.5 bg-zinc-800 rounded w-4/5"></div>
                      </div>
                    </div>
                  ) : (
                    <p className="text-zinc-300 leading-relaxed max-h-48 overflow-y-auto pr-1 bg-zinc-950/50 p-3 rounded-xl border border-zinc-800/80">
                      {previewFilm.synopsis || 'Sinopsis belum tersedia dari provider API.'}
                    </p>
                  )}
                </div>

                {/* Genres */}
                {previewFilm.genres?.length > 0 && (
                  <div>
                    <span className="text-zinc-400 font-semibold block mb-1 text-[11px] uppercase tracking-wider">
                      Genre
                    </span>
                    <div className="flex flex-wrap gap-1">
                      {previewFilm.genres.map((g) => (
                        <span key={g} className="px-2 py-0.5 rounded-md bg-zinc-950 border border-zinc-800 text-[10px] text-zinc-300 font-medium">
                          {g}
                        </span>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Modal Footer */}
            <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-zinc-800">
              <div>
                {previewFilm.is_imported ? (
                  <span className="flex items-center gap-1.5 text-xs text-emerald-400 font-semibold bg-emerald-500/10 border border-emerald-500/30 px-3 py-1.5 rounded-xl">
                    <CheckCircle2 className="w-4 h-4" />
                    <span>Sudah Tersedia di Katalog</span>
                  </span>
                ) : (
                  <span className="text-xs text-zinc-500 font-mono">
                    ID: #{previewFilm.subject_id}
                  </span>
                )}
              </div>

              <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button
                  type="button"
                  onClick={() => setPreviewFilm(null)}
                  className="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-semibold text-[#E4E2DD] transition-colors cursor-pointer"
                >
                  Tutup
                </button>

                {previewFilm.is_imported && previewFilm.local_edit_url ? (
                  <a
                    href={previewFilm.local_edit_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-semibold text-zinc-200 hover:text-[#E4E2DD] border border-zinc-700 transition-colors flex items-center gap-1.5"
                  >
                    <span>Kelola di Katalog</span>
                    <ExternalLink className="w-3.5 h-3.5 text-zinc-400" />
                  </a>
                ) : !previewFilm.is_imported ? (
                  <button
                    type="button"
                    onClick={() => {
                      handleImportSingle(previewFilm);
                      setPreviewFilm(null);
                    }}
                    disabled={importingIds.has(previewFilm.subject_id)}
                    className="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-xs font-bold text-black transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                  >
                    <DownloadCloud className="w-3.5 h-3.5" />
                    <span>Impor Sekarang</span>
                  </button>
                ) : null}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
