import React, { useState, useEffect } from 'react';
import { 
  Layers, CheckCircle2, Eye, Plus, Trash2, Search, Filter, 
  Film, ExternalLink, Loader2, Check, ArrowRight, X, AlertCircle, 
  ShieldAlert, ShieldCheck, User as UserIcon, Lock, Globe, FileEdit,
  RotateCcw, RefreshCw, Sparkles, Edit3, MessageSquare, AlertTriangle
} from 'lucide-react';

export default function AdminSmartCollections({ 
  initialCollections = [], 
  stats: initialStats = {}, 
  csrfToken = '' 
}) {
  const [collections, setCollections] = useState(initialCollections);
  const [stats, setStats] = useState(initialStats);
  const [search, setSearch] = useState('');
  const [filterType, setFilterType] = useState('all');
  const [filterStatus, setFilterStatus] = useState('all');
  
  const [rebuilding, setRebuilding] = useState(false);
  const [toastMessage, setToastMessage] = useState(null);

  // Takedown Modal state
  const [takedownModalCol, setTakedownModalCol] = useState(null);
  const [takedownReason, setTakedownReason] = useState('Melanggar Pedoman Komunitas Faiilmov');
  const [customReason, setCustomReason] = useState('');
  const [isSubmittingTakedown, setIsSubmittingTakedown] = useState(false);

  // Restore Modal state
  const [restoreModalCol, setRestoreModalCol] = useState(null);
  const [restoreTargetStatus, setRestoreTargetStatus] = useState('draft');
  const [isSubmittingRestore, setIsSubmittingRestore] = useState(false);

  // Suggestion Drawer state
  const [activeSuggestionCol, setActiveSuggestionCol] = useState(null);
  const [suggestions, setSuggestions] = useState([]);
  const [loadingSuggestions, setLoadingSuggestions] = useState(false);
  const [addingFilmId, setAddingFilmId] = useState(null);

  const token = csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const showToast = (msg, type = 'success') => {
    setToastMessage({ text: msg, type });
    setTimeout(() => setToastMessage(null), 4000);
  };

  const fetchCollectionsList = async () => {
    try {
      const res = await fetch('/admin/collections/api-list', {
        headers: { 'Accept': 'application/json' }
      });
      const data = await res.json();
      if (data.status === 'success' && Array.isArray(data.data)) {
        setCollections(data.data);
      }
    } catch (e) {
      console.error(e);
    }
  };

  // Rebuild Auto Collections
  const handleRebuild = async () => {
    if (rebuilding) return;
    setRebuilding(true);

    try {
      const res = await fetch('/admin/collections/rebuild', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ threshold: 4 }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        showToast(data.message || 'Auto-collections berhasil diperbarui!');
        fetchCollectionsList();
      } else {
        showToast(data.message || 'Gagal rebuild auto-collections', 'error');
      }
    } catch (e) {
      showToast('Terjadi kendala jaringan saat rebuild', 'error');
    } finally {
      setRebuilding(false);
    }
  };

  // Toggle Publish / Draft
  const handleTogglePublish = async (colId) => {
    try {
      const res = await fetch(`/admin/collections/${colId}/toggle-publish`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        }
      });
      const data = await res.json();
      if (data.success) {
        setCollections(prev => prev.map(c => c.id === colId ? { ...c, status: data.status } : c));
        showToast(`Status koleksi diubah menjadi ${data.status.toUpperCase()}`);
      }
    } catch (e) {
      showToast('Gagal mengubah status publikasi', 'error');
    }
  };

  // Open Takedown Modal
  const handleOpenTakedown = (col) => {
    setTakedownModalCol(col);
    setTakedownReason('Melanggar Pedoman Komunitas Faiilmov');
    setCustomReason('');
  };

  // Submit Takedown
  const handleSubmitTakedown = async () => {
    if (!takedownModalCol || isSubmittingTakedown) return;
    setIsSubmittingTakedown(true);

    const finalReason = takedownReason === 'Lainnya' ? customReason.trim() || 'Melanggar Pedoman Komunitas' : takedownReason;

    try {
      const res = await fetch(`/admin/collections/${takedownModalCol.id}/takedown`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ reason: finalReason }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        showToast(`Koleksi '${takedownModalCol.name}' berhasil di-takedown!`);
        setCollections(prev => prev.map(c => c.id === takedownModalCol.id ? { 
          ...c, 
          status: 'takedown', 
          takedown_reason: finalReason, 
          taken_down_at: new Date().toISOString() 
        } : c));
        setTakedownModalCol(null);
      } else {
        showToast(data.message || 'Gagal melakukan takedown', 'error');
      }
    } catch (e) {
      showToast('Terjadi kendala saat takedown', 'error');
    } finally {
      setIsSubmittingTakedown(false);
    }
  };

  // Open Restore Modal
  const handleOpenRestore = (col) => {
    setRestoreModalCol(col);
    setRestoreTargetStatus('draft');
  };

  // Submit Restore
  const handleSubmitRestore = async () => {
    if (!restoreModalCol || isSubmittingRestore) return;
    setIsSubmittingRestore(true);

    try {
      const res = await fetch(`/admin/collections/${restoreModalCol.id}/restore`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ status: restoreTargetStatus }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        showToast(`Koleksi '${restoreModalCol.name}' berhasil dipulihkan!`);
        setCollections(prev => prev.map(c => c.id === restoreModalCol.id ? { 
          ...c, 
          status: restoreTargetStatus, 
          takedown_reason: null, 
          taken_down_at: null 
        } : c));
        setRestoreModalCol(null);
      } else {
        showToast(data.message || 'Gagal memulihkan koleksi', 'error');
      }
    } catch (e) {
      showToast('Terjadi kendala saat memulihkan koleksi', 'error');
    } finally {
      setIsSubmittingRestore(false);
    }
  };

  // Suggestions Drawer
  const handleOpenSuggestions = async (col) => {
    setActiveSuggestionCol(col);
    setSuggestions([]);
    setLoadingSuggestions(true);

    try {
      const res = await fetch(`/admin/collections/${col.id}/suggestions`, {
        headers: { 'Accept': 'application/json' }
      });
      const data = await res.json();
      if (data.success) {
        setSuggestions(data.suggestions || []);
      }
    } catch (e) {
      showToast('Gagal memuat saran film', 'error');
    } finally {
      setLoadingSuggestions(false);
    }
  };

  const handleAddFilmToCollection = async (filmId) => {
    if (!activeSuggestionCol || addingFilmId) return;
    setAddingFilmId(filmId);

    try {
      const res = await fetch(`/admin/collections/${activeSuggestionCol.id}/films`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ film_id: filmId }),
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message);
        setSuggestions(prev => prev.filter(s => s.id !== filmId));
        setCollections(prev => prev.map(c => c.id === activeSuggestionCol.id ? { ...c, films_count: (c.films_count || 0) + 1 } : c));
      }
    } catch (e) {
      showToast('Gagal menambahkan film ke koleksi', 'error');
    } finally {
      setAddingFilmId(null);
    }
  };

  // Delete Collection
  const handleDeleteCollection = async (colId, colName) => {
    if (!window.confirm(`Hapus permanen koleksi "${colName}"? Tindakan ini tidak dapat dibatalkan.`)) return;

    try {
      const res = await fetch(`/admin/collections/${colId}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        }
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message);
        setCollections(prev => prev.filter(c => c.id !== colId));
      }
    } catch (e) {
      showToast('Gagal menghapus koleksi', 'error');
    }
  };

  // Filtered list
  const filteredCollections = collections.filter(c => {
    const q = search.toLowerCase().trim();
    const matchQuery = !q || 
      c.name?.toLowerCase().includes(q) || 
      c.source_tag?.toLowerCase().includes(q) ||
      c.creator?.name?.toLowerCase().includes(q) ||
      c.creator?.email?.toLowerCase().includes(q);

    const isTakenDown = c.status === 'takedown' || Boolean(c.taken_down_at);
    
    let matchStatus = true;
    if (filterStatus === 'takedown') {
      matchStatus = isTakenDown;
    } else if (filterStatus !== 'all') {
      matchStatus = !isTakenDown && c.status === filterStatus;
    }

    const matchType = filterType === 'all' || c.type === filterType;
    return matchQuery && matchType && matchStatus;
  });

  return (
    <div className="space-y-6 text-white">
      {/* Toast Notification */}
      {toastMessage && (
        <div className={`fixed top-20 right-6 z-50 px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border backdrop-blur-xl animate-in fade-in slide-in-from-top-4 ${
          toastMessage.type === 'error'
            ? 'bg-rose-950/95 border-rose-500/50 text-rose-200'
            : 'bg-zinc-900/95 border-emerald-500/50 text-emerald-300'
        }`}>
          {toastMessage.type === 'error' ? <AlertCircle className="w-5 h-5 flex-shrink-0" /> : <CheckCircle2 className="w-5 h-5 flex-shrink-0 text-emerald-400" />}
          <span className="text-xs font-bold">{toastMessage.text}</span>
        </div>
      )}

      {/* Metrics Overview Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div className="p-4 rounded-2xl bg-zinc-900/90 border border-white/10">
          <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-400 block mb-1">Total Koleksi</span>
          <p className="text-2xl font-extrabold text-white">{collections.length}</p>
        </div>

        <div className="p-4 rounded-2xl bg-zinc-900/90 border border-white/10">
          <span className="text-[11px] font-bold uppercase tracking-wider text-emerald-400 block mb-1">Published</span>
          <p className="text-2xl font-extrabold text-emerald-400">{collections.filter(c => c.status === 'published' && !c.taken_down_at).length}</p>
        </div>

        <div className="p-4 rounded-2xl bg-zinc-900/90 border border-white/10">
          <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-300 block mb-1">Draft</span>
          <p className="text-2xl font-extrabold text-zinc-200">{collections.filter(c => c.status === 'draft' && !c.taken_down_at).length}</p>
        </div>

        <div className="p-4 rounded-2xl bg-zinc-900/90 border border-white/10">
          <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-400 block mb-1">Private</span>
          <p className="text-2xl font-extrabold text-zinc-400">{collections.filter(c => c.status === 'private' && !c.taken_down_at).length}</p>
        </div>

        <div className="p-4 rounded-2xl bg-zinc-900/90 border border-red-500/20 bg-red-500/5">
          <span className="text-[11px] font-bold uppercase tracking-wider text-red-400 block mb-1">Takedown</span>
          <p className="text-2xl font-extrabold text-red-400">{collections.filter(c => c.status === 'takedown' || c.taken_down_at).length}</p>
        </div>

        <div className="p-4 rounded-2xl bg-zinc-900/90 border border-white/10">
          <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-300 block mb-1">User Created</span>
          <p className="text-2xl font-extrabold text-white">{collections.filter(c => c.created_by).length}</p>
        </div>
      </div>

      {/* Action Bar: Search, Filters & Buttons */}
      <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-4 rounded-2xl bg-zinc-900/90 border border-white/10">
        
        {/* Search & Filter dropdowns */}
        <div className="flex flex-wrap items-center gap-3 flex-1">
          {/* Search Box */}
          <div className="relative flex-1 min-w-[220px] max-w-md">
            <Search className="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari judul koleksi, creator, atau email..."
              className="w-full pl-9 pr-4 py-2.5 rounded-xl bg-zinc-950 border border-white/10 text-xs text-white placeholder-zinc-500 focus:border-white/40 focus:outline-none transition"
            />
          </div>

          {/* Status Filter */}
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-white/10 text-xs text-zinc-300 focus:border-white/40 focus:outline-none transition cursor-pointer"
          >
            <option value="all">Semua Status</option>
            <option value="published">Published (Publik)</option>
            <option value="draft">Draft (Konsep)</option>
            <option value="private">Private (Pribadi)</option>
            <option value="takedown">🚫 Takedown (Moderasi)</option>
          </select>

          {/* Type Filter */}
          <select
            value={filterType}
            onChange={(e) => setFilterType(e.target.value)}
            className="px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-white/10 text-xs text-zinc-300 focus:border-white/40 focus:outline-none transition cursor-pointer"
          >
            <option value="all">Semua Tipe</option>
            <option value="manual">Koleksi Manual / User</option>
            <option value="auto">Franchise Resmi (Tag Engine)</option>
            <option value="prompt">Kurasi Prompt AI</option>
          </select>
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-2.5 shrink-0">
          <button
            onClick={() => window.dispatchEvent(new CustomEvent('open-create-collection-modal'))}
            className="px-4 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold transition shadow-md shadow-white/10 flex items-center gap-1.5 cursor-pointer"
          >
            <Plus className="w-3.5 h-3.5" />
            <span>Buat Koleksi</span>
          </button>

          <button
            onClick={handleRebuild}
            disabled={rebuilding}
            className="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-bold transition border border-white/10 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <RefreshCw className={`w-3.5 h-3.5 ${rebuilding ? 'animate-spin' : ''}`} />
            <span>{rebuilding ? 'Memproses...' : 'Rebuild Auto Collections'}</span>
          </button>
        </div>
      </div>

      {/* Collections Data Table */}
      <div className="rounded-3xl bg-zinc-900/90 border border-white/10 overflow-hidden shadow-2xl">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-white/10 bg-zinc-950/80 text-[11px] font-mono text-zinc-400 uppercase tracking-wider">
                <th className="py-4 px-5 font-bold">Koleksi</th>
                <th className="py-4 px-5 font-bold">Pembuat (Creator)</th>
                <th className="py-4 px-5 font-bold">Tipe</th>
                <th className="py-4 px-5 font-bold">Jumlah Film</th>
                <th className="py-4 px-5 font-bold">Status</th>
                <th className="py-4 px-5 font-bold text-right">Moderasi & Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5 text-xs">
              {filteredCollections.length === 0 ? (
                <tr>
                  <td colSpan={6} className="py-16 text-center text-zinc-500">
                    Tidak ada koleksi yang sesuai dengan pencarian atau filter.
                  </td>
                </tr>
              ) : (
                filteredCollections.map((col) => {
                  const isTakenDown = col.status === 'takedown' || Boolean(col.taken_down_at);

                  return (
                    <tr key={col.id} className={`hover:bg-zinc-850/60 transition-colors ${isTakenDown ? 'bg-red-950/10' : ''}`}>
                      {/* Collection Title & Cover */}
                      <td className="py-4 px-5">
                        <div className="flex items-center gap-3.5">
                          <div className="w-14 h-9 rounded-xl overflow-hidden bg-zinc-950 border border-white/10 shrink-0">
                            {col.cover_image ? (
                              <img src={col.cover_image} alt={col.name} className="w-full h-full object-cover" />
                            ) : (
                              <div className="w-full h-full flex items-center justify-center text-zinc-600">
                                <Film className="w-4 h-4" />
                              </div>
                            )}
                          </div>
                          <div className="min-w-0 space-y-0.5">
                            <a 
                              href={`/collections/${col.slug}`} 
                              target="_blank" 
                              className="font-bold text-white hover:text-zinc-300 transition-colors flex items-center gap-1.5"
                            >
                              <span className="truncate max-w-[220px]">{col.name}</span>
                              <ExternalLink className="w-3 h-3 text-zinc-500 shrink-0" />
                            </a>
                            {col.source_tag && (
                              <span className="text-[10px] text-zinc-500 truncate block font-mono">
                                Tag: {col.source_tag}
                              </span>
                            )}
                          </div>
                        </div>
                      </td>

                      {/* Akun Pembuat (Creator Account Details) */}
                      <td className="py-4 px-5">
                        {col.creator ? (
                          <div className="flex items-center gap-2.5">
                            <div className="w-7 h-7 rounded-full bg-zinc-800 border border-white/10 flex items-center justify-center text-[11px] font-extrabold text-white shrink-0">
                              {col.creator.name.charAt(0).toUpperCase()}
                            </div>
                            <div className="min-w-0">
                              <div className="flex items-center gap-1.5">
                                <span className="font-bold text-zinc-200 truncate">{col.creator.name}</span>
                                {col.creator.role === 'admin' && (
                                  <span className="px-1.5 py-0.2 rounded text-[9px] font-bold bg-white/10 text-zinc-300 border border-white/10">
                                    ADMIN
                                  </span>
                                )}
                              </div>
                              <span className="text-[10px] text-zinc-500 truncate block">{col.creator.email}</span>
                            </div>
                          </div>
                        ) : (
                          <div className="flex items-center gap-2 text-zinc-400">
                            <div className="w-7 h-7 rounded-full bg-zinc-800 border border-white/10 flex items-center justify-center text-[10px] font-mono text-zinc-400 shrink-0">
                              SYS
                            </div>
                            <div>
                              <span className="font-semibold text-zinc-300 text-xs">Sistem Faiilmov</span>
                              <span className="text-[10px] text-zinc-500 block">Auto Curation</span>
                            </div>
                          </div>
                        )}
                      </td>

                      {/* Tipe Koleksi */}
                      <td className="py-4 px-5">
                        <span className={`px-2.5 py-1 rounded-xl text-[10px] font-mono font-bold uppercase ${
                          col.type === 'auto'
                            ? 'bg-white/10 text-zinc-200 border border-white/10'
                            : (col.type === 'prompt'
                                ? 'bg-white/10 text-zinc-200 border border-white/10'
                                : 'bg-zinc-800 text-zinc-300 border border-white/10')
                        }`}>
                          {col.type === 'auto' ? 'Franchise' : col.type === 'prompt' ? 'AI Prompt' : 'Manual'}
                        </span>
                      </td>

                      {/* Jumlah Film */}
                      <td className="py-4 px-5 font-mono font-bold text-zinc-300">
                        {col.films_count || 0} Film
                      </td>

                      {/* Status Koleksi */}
                      <td className="py-4 px-5">
                        {isTakenDown ? (
                          <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-red-500/20 text-red-400 border border-red-500/30" title={col.takedown_reason || 'Di-takedown oleh admin'}>
                            <ShieldAlert className="w-3.5 h-3.5" />
                            <span>Takedown</span>
                          </div>
                        ) : (
                          <button
                            onClick={() => handleTogglePublish(col.id)}
                            className={`px-3 py-1 rounded-full text-[11px] font-bold transition cursor-pointer flex items-center gap-1.5 ${
                              col.status === 'published'
                                ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30'
                                : col.status === 'private'
                                ? 'bg-zinc-800 text-zinc-400 border border-zinc-700 hover:text-white'
                                : 'bg-zinc-800 text-zinc-400 border border-zinc-700 hover:text-white'
                            }`}
                          >
                            <span className={`w-1.5 h-1.5 rounded-full ${col.status === 'published' ? 'bg-emerald-400' : 'bg-zinc-500'}`} />
                            <span>{col.status === 'published' ? 'Published' : col.status === 'private' ? 'Private' : 'Draft'}</span>
                          </button>
                        )}
                      </td>

                      {/* Moderasi & Aksi */}
                      <td className="py-4 px-5 text-right">
                        <div className="flex items-center justify-end gap-2">
                          
                          {/* Studio Editor Link (Only for system or admin-created collections) */}
                          {(!col.creator || col.creator.role === 'admin') ? (
                            <a
                              href={`/collections/${col.slug}/edit`}
                              title="Buka di Studio Editor (Koleksi Admin/Sistem)"
                              className="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white transition border border-white/5"
                            >
                              <Edit3 className="w-3.5 h-3.5" />
                            </a>
                          ) : (
                            <span 
                              title="Koleksi Pengguna: Admin tidak dapat mengubah isi koleksi user (Gunakan Takedown jika melanggar ketentuan)"
                              className="p-2 rounded-xl bg-zinc-900/50 text-zinc-600 border border-white/5 cursor-not-allowed"
                            >
                              <Lock className="w-3.5 h-3.5" />
                            </span>
                          )}

                          {/* Suggestion Loop Button */}
                          {(!col.creator || col.creator.role === 'admin') && (
                            <button
                              onClick={() => handleOpenSuggestions(col)}
                              title="Saran Film Tambahan"
                              className="p-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white transition border border-white/5 cursor-pointer"
                            >
                              <Sparkles className="w-3.5 h-3.5" />
                            </button>
                          )}

                          {/* TAKEDOWN OR RESTORE BUTTON */}
                          {isTakenDown ? (
                            <button
                              onClick={() => handleOpenRestore(col)}
                              title="Pulihkan Koleksi (Restore)"
                              className="px-3 py-1.5 rounded-xl bg-emerald-500/20 hover:bg-emerald-500 text-emerald-300 hover:text-black border border-emerald-500/40 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                            >
                              <RotateCcw className="w-3.5 h-3.5" />
                              <span>Pulihkan</span>
                            </button>
                          ) : (
                            <button
                              onClick={() => handleOpenTakedown(col)}
                              title="Takedown Koleksi (Pelanggaran / Moderasi)"
                              className="px-3 py-1.5 rounded-xl bg-red-500/10 hover:bg-red-500 text-red-400 hover:text-white border border-red-500/20 hover:border-red-500 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                            >
                              <ShieldAlert className="w-3.5 h-3.5" />
                              <span>Takedown</span>
                            </button>
                          )}

                          {/* Delete Button */}
                          <button
                            onClick={() => handleDeleteCollection(col.id, col.name)}
                            title="Hapus Permanen Koleksi"
                            className="p-2 rounded-xl bg-zinc-800 hover:bg-red-500/20 text-zinc-400 hover:text-red-400 transition border border-white/5 cursor-pointer"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* MODAL 1: TAKEDOWN REASON MODAL */}
      {takedownModalCol && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
          <div className="fixed inset-0 bg-black/80 backdrop-blur-xl" onClick={() => !isSubmittingTakedown && setTakedownModalCol(null)} />
          
          <div className="relative w-full max-w-lg bg-zinc-950 border border-red-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl z-10 space-y-6 text-white animate-in zoom-in-95 duration-200">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-2xl bg-red-500/20 border border-red-500/40 text-red-400 flex items-center justify-center">
                  <ShieldAlert className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-lg font-extrabold text-white">Takedown Koleksi</h3>
                  <p className="text-xs text-zinc-400">Moderasi konten yang melanggar ketentuan</p>
                </div>
              </div>

              <button 
                onClick={() => setTakedownModalCol(null)}
                className="p-1.5 rounded-full text-zinc-400 hover:text-white hover:bg-white/10"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Collection Info Card */}
            <div className="p-4 rounded-2xl bg-zinc-900 border border-white/10 space-y-1">
              <p className="text-xs text-zinc-400 font-medium">Target Koleksi:</p>
              <p className="text-sm font-bold text-white truncate">{takedownModalCol.name}</p>
              <p className="text-xs text-zinc-400">
                Pembuat: <span className="text-zinc-200 font-semibold">{takedownModalCol.creator?.name || 'Sistem Faiilmov'}</span>
                {takedownModalCol.creator?.email && ` (${takedownModalCol.creator.email})`}
              </p>
            </div>

            {/* Reason Presets */}
            <div className="space-y-2">
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400">
                Pilih Alasan Takedown:
              </label>
              <div className="space-y-2">
                {[
                  'Melanggar Pedoman Komunitas Faiilmov',
                  'Konten Tidak Pantas / Spam',
                  'Pelanggaran Hak Cipta / Plagiarisme',
                  'Judul atau Deskripsi Menyesatkan',
                  'Lainnya'
                ].map((reasonOption) => (
                  <label 
                    key={reasonOption} 
                    className={`flex items-center gap-3 p-3 rounded-2xl border text-xs font-medium cursor-pointer transition ${
                      takedownReason === reasonOption 
                        ? 'bg-red-500/15 border-red-500/40 text-red-200' 
                        : 'bg-zinc-900 border-white/5 text-zinc-300 hover:border-white/20'
                    }`}
                  >
                    <input 
                      type="radio" 
                      name="takedown_reason" 
                      value={reasonOption}
                      checked={takedownReason === reasonOption}
                      onChange={(e) => setTakedownReason(e.target.value)}
                      className="text-red-500 focus:ring-0"
                    />
                    <span>{reasonOption}</span>
                  </label>
                ))}
              </div>
            </div>

            {/* Custom Reason Text Area */}
            {takedownReason === 'Lainnya' && (
              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1.5">
                  Tuliskan Alasan Spesifik:
                </label>
                <textarea
                  rows={3}
                  value={customReason}
                  onChange={(e) => setCustomReason(e.target.value)}
                  placeholder="Jelaskan alasan pelanggaran untuk catatan sistem..."
                  className="w-full px-4 py-3 rounded-2xl bg-zinc-900 border border-white/10 text-white text-xs placeholder-zinc-500 focus:outline-none focus:border-red-400 transition resize-none"
                />
              </div>
            )}

            {/* Warning Note */}
            <div className="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-start gap-2.5 text-xs text-amber-300">
              <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
              <p>Koleksi akan langsung disembunyikan dari katalog publik. Pemilik dapat melihat alasan takedown saat membuka studionya.</p>
            </div>

            {/* Modal Actions */}
            <div className="flex items-center justify-end gap-3 pt-2">
              <button
                type="button"
                onClick={() => setTakedownModalCol(null)}
                disabled={isSubmittingTakedown}
                className="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-bold transition"
              >
                Batal
              </button>

              <button
                type="button"
                onClick={handleSubmitTakedown}
                disabled={isSubmittingTakedown}
                className="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-extrabold transition shadow-lg shadow-red-600/30 flex items-center gap-2 cursor-pointer disabled:opacity-50"
              >
                {isSubmittingTakedown ? <Loader2 className="w-4 h-4 animate-spin" /> : <ShieldAlert className="w-4 h-4" />}
                <span>Konfirmasi Takedown</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 2: RESTORE MODAL */}
      {restoreModalCol && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
          <div className="fixed inset-0 bg-black/80 backdrop-blur-xl" onClick={() => !isSubmittingRestore && setRestoreModalCol(null)} />
          
          <div className="relative w-full max-w-md bg-zinc-950 border border-emerald-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl z-10 space-y-6 text-white animate-in zoom-in-95 duration-200">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center">
                <ShieldCheck className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-lg font-extrabold text-white">Pulihkan Koleksi</h3>
                <p className="text-xs text-zinc-400">Batalkan takedown dan kembalikan koleksi</p>
              </div>
            </div>

            <div className="p-4 rounded-2xl bg-zinc-900 border border-white/10 space-y-1 text-xs">
              <p className="text-zinc-400">Judul: <strong className="text-white">{restoreModalCol.name}</strong></p>
              <p className="text-zinc-400">Alasan Takedown Sebelumnya: <span className="text-red-300 italic">{restoreModalCol.takedown_reason || 'Tidak ada catatan'}</span></p>
            </div>

            <div className="space-y-2">
              <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400">
                Pilih Status Setelah Dipulihkan:
              </label>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => setRestoreTargetStatus('draft')}
                  className={`p-3 rounded-2xl border text-xs font-bold transition flex flex-col items-center gap-1 cursor-pointer ${
                    restoreTargetStatus === 'draft'
                      ? 'bg-zinc-700/60 border-zinc-500 text-white'
                      : 'bg-zinc-900 border-white/5 text-zinc-400 hover:border-white/20'
                  }`}
                >
                  <FileEdit className="w-4 h-4" />
                  <span>Draft (Konsep)</span>
                </button>

                <button
                  type="button"
                  onClick={() => setRestoreTargetStatus('published')}
                  className={`p-3 rounded-2xl border text-xs font-bold transition flex flex-col items-center gap-1 cursor-pointer ${
                    restoreTargetStatus === 'published'
                      ? 'bg-emerald-500/20 border-emerald-500 text-emerald-400'
                      : 'bg-zinc-900 border-white/5 text-zinc-400 hover:border-white/20'
                  }`}
                >
                  <Globe className="w-4 h-4" />
                  <span>Published (Publik)</span>
                </button>
              </div>
            </div>

            <div className="flex items-center justify-end gap-3 pt-2">
              <button
                type="button"
                onClick={() => setRestoreModalCol(null)}
                disabled={isSubmittingRestore}
                className="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-bold transition"
              >
                Batal
              </button>

              <button
                type="button"
                onClick={handleSubmitRestore}
                disabled={isSubmittingRestore}
                className="px-5 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-extrabold transition shadow-lg shadow-white/10 flex items-center gap-2 cursor-pointer disabled:opacity-50"
              >
                {isSubmittingRestore ? <Loader2 className="w-4 h-4 animate-spin" /> : <RotateCcw className="w-4 h-4" />}
                <span>Pulihkan Sekarang</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 3: SUGGESTION LOOP DRAWER */}
      {activeSuggestionCol && (
        <div className="fixed inset-0 z-50 flex items-center justify-end">
          <div 
            className="fixed inset-0 bg-black/70 backdrop-blur-sm"
            onClick={() => setActiveSuggestionCol(null)}
          />

          <div className="relative w-full max-w-lg h-full bg-zinc-950 border-l border-zinc-800 p-6 shadow-2xl z-10 flex flex-col justify-between overflow-hidden animate-in slide-in-from-right duration-300">
            <div>
              <div className="flex items-center justify-between pb-4 border-b border-zinc-800">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-xl bg-zinc-800 text-white flex items-center justify-center border border-white/10">
                    <Film className="w-4 h-4" />
                  </div>
                  <div>
                    <h3 className="font-bold text-white text-base truncate max-w-[280px]">
                      {activeSuggestionCol.name}
                    </h3>
                    <p className="text-[11px] text-zinc-400">
                      Saran film tambahan berdasarkan kesamaan katalog
                    </p>
                  </div>
                </div>

                <button
                  onClick={() => setActiveSuggestionCol(null)}
                  className="p-1.5 rounded-lg bg-zinc-900 hover:bg-zinc-800 text-zinc-400 hover:text-white"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              {/* Suggestions List */}
              <div className="mt-4 space-y-3 overflow-y-auto max-h-[calc(100vh-140px)] pr-1">
                {loadingSuggestions ? (
                  <div className="py-16 text-center text-zinc-500 space-y-3">
                    <Loader2 className="w-6 h-6 animate-spin text-white mx-auto" />
                    <p className="text-xs">Menganalisis kemiripan katalog film...</p>
                  </div>
                ) : suggestions.length === 0 ? (
                  <div className="py-16 text-center text-zinc-500">
                    Belum ditemukan kandidat film tambahan yang cocok.
                  </div>
                ) : (
                  suggestions.map((film) => (
                    <div 
                      key={film.id}
                      className="p-3 rounded-2xl bg-zinc-900/90 border border-zinc-800 hover:border-white/20 transition-all flex items-center justify-between gap-3"
                    >
                      <div className="flex items-center gap-3 min-w-0">
                        <img
                          src={film.poster_url || '/placeholder-poster.jpg'}
                          alt={film.title}
                          className="w-10 h-14 object-cover rounded-lg shrink-0 bg-zinc-950"
                        />
                        <div className="min-w-0 space-y-1">
                          <h4 className="font-bold text-white text-xs truncate">
                            {film.title}
                          </h4>
                          <div className="flex items-center gap-2 flex-wrap text-[10px] text-zinc-400">
                            {film.release_year && <span>{film.release_year}</span>}
                            {film.similarity_score && (
                              <span className="px-1.5 py-0.2 rounded bg-white/10 text-zinc-300 font-mono font-bold">
                                {film.similarity_score}% Match
                              </span>
                            )}
                          </div>
                        </div>
                      </div>

                      <button
                        onClick={() => handleAddFilmToCollection(film.id)}
                        disabled={addingFilmId === film.id}
                        className="px-3 py-1.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold transition flex items-center gap-1 shrink-0 cursor-pointer disabled:opacity-50"
                      >
                        {addingFilmId === film.id ? (
                          <Loader2 className="w-3.5 h-3.5 animate-spin" />
                        ) : (
                          <>
                            <Plus className="w-3.5 h-3.5" />
                            <span>Tambah</span>
                          </>
                        )}
                      </button>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
