import React, { useState, useEffect, useRef } from 'react';
import { 
    GripVertical, 
    Plus, 
    Trash2, 
    Save, 
    Eye, 
    Sparkles, 
    Search, 
    Globe, 
    Lock, 
    FileEdit, 
    ArrowUp, 
    ArrowDown, 
    Check, 
    AlertCircle, 
    Film as FilmIcon, 
    Layers, 
    Clock, 
    Star,
    Calendar,
    ChevronRight,
    Loader2,
    X,
    PlusCircle
} from 'lucide-react';

export default function CollectionStudioEditor({ collection: initialCollection, initialFilms = [], csrfToken }) {
    const [collection, setCollection] = useState(initialCollection);
    const [films, setFilms] = useState(initialFilms);
    const [name, setName] = useState(initialCollection.name || '');
    const [description, setDescription] = useState(initialCollection.description || '');
    const [status, setStatus] = useState(initialCollection.status || 'published');
    const [coverImage, setCoverImage] = useState(initialCollection.cover_image || '');

    // Search Modal State
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const searchInputRef = useRef(null);

    // In-studio local film filter search
    const [filterQuery, setFilterQuery] = useState('');
    const inStudioSearchRef = useRef(null);

    // Auto-scroll on drag refs
    const dragClientYRef = useRef(null);
    const autoScrollAnimationRef = useRef(null);

    // Save & loading states
    const [isSaving, setIsSaving] = useState(false);
    const [toast, setToast] = useState(null);
    const [draggedIndex, setDraggedIndex] = useState(null);
    const [dragOverIndex, setDragOverIndex] = useState(null);

    // Focus input when modal opens
    useEffect(() => {
        if (isAddModalOpen) {
            setTimeout(() => {
                searchInputRef.current?.focus();
            }, 100);
        } else {
            setSearchQuery('');
            setSearchResults([]);
        }
    }, [isAddModalOpen]);

    // Debounce search catalog
    useEffect(() => {
        if (!searchQuery.trim() || searchQuery.trim().length < 2) {
            setSearchResults([]);
            return;
        }

        const timer = setTimeout(async () => {
            setIsSearching(true);
            try {
                const res = await fetch(`/collections/api/search-films?q=${encodeURIComponent(searchQuery)}`);
                if (res.ok) {
                    const data = await res.json();
                    setSearchResults(data);
                }
            } catch (err) {
                console.error('Error searching catalog:', err);
            } finally {
                setIsSearching(false);
            }
        }, 250);

        return () => clearTimeout(timer);
    }, [searchQuery]);

    // Handle opening add modal with pre-filled query
    const handleOpenAddModal = (initialQuery = '') => {
        setIsAddModalOpen(true);
        if (typeof initialQuery === 'string' && initialQuery.trim()) {
            setSearchQuery(initialQuery.trim());
        }
    };

    // Keyboard shortcut (Escape to clear in-studio search filter)
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && filterQuery) {
                setFilterQuery('');
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [filterQuery]);

    const showToast = (message, type = 'success') => {
        setToast({ message, type });
        setTimeout(() => setToast(null), 4000);
    };

    // Add Film to Collection
    const handleAddFilm = async (film) => {
        if (films.some(f => f.id === film.id)) {
            showToast('Film ini sudah ada di dalam koleksi Anda.', 'error');
            return;
        }

        try {
            const res = await fetch(`/collections/${collection.id}/films`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    film_id: film.id,
                    note: '',
                }),
            });

            const data = await res.json();
            if (res.ok && data.success) {
                setFilms(prev => [...prev, data.film]);
                showToast(`'${film.title}' berhasil ditambahkan ke urutan nonton!`);
                if (!coverImage && film.poster_url) {
                    setCoverImage(film.poster_url);
                }
            } else {
                showToast(data.message || 'Gagal menambahkan film', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Terjadi kesalahan koneksi', 'error');
        }
    };

    // Remove Film from Collection
    const handleRemoveFilm = async (filmId) => {
        if (!confirm('Hapus film ini dari koleksi dan urutan nonton?')) return;

        try {
            const res = await fetch(`/collections/${collection.id}/films/${filmId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            const data = await res.json();
            if (res.ok && data.success) {
                setFilms(prev => {
                    const filtered = prev.filter(f => f.id !== filmId);
                    return filtered.map((f, idx) => ({ ...f, sequence: idx + 1 }));
                });
                showToast('Film berhasil dihapus dari koleksi.');
            }
        } catch (err) {
            console.error(err);
            showToast('Gagal menghapus film', 'error');
        }
    };

    // Update Note for a Film
    const handleNoteChange = (filmId, newNote) => {
        setFilms(prev => prev.map(f => f.id === filmId ? { ...f, note: newNote } : f));
    };

    // Move Film Up/Down
    const moveFilm = (index, direction) => {
        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= films.length) return;

        const updated = [...films];
        const temp = updated[index];
        updated[index] = updated[targetIndex];
        updated[targetIndex] = temp;

        const reindexed = updated.map((f, idx) => ({ ...f, sequence: idx + 1 }));
        setFilms(reindexed);
    };

    // Auto-scroll loop engine during Drag & Drop
    const startAutoScroll = () => {
        if (autoScrollAnimationRef.current) return;

        const SCROLL_ZONE = 140; // Pixels from top/bottom window edge to trigger scroll
        const MAX_SPEED = 24;    // Max pixels to scroll per frame

        const scrollLoop = () => {
            const clientY = dragClientYRef.current;
            if (clientY !== null && clientY !== undefined) {
                const viewportHeight = window.innerHeight;

                if (clientY < SCROLL_ZONE && clientY >= 0) {
                    // Scroll UP: faster the closer to top edge
                    const ratio = Math.max(0, (SCROLL_ZONE - clientY) / SCROLL_ZONE);
                    const speed = Math.max(3, Math.round(ratio * MAX_SPEED));
                    window.scrollBy(0, -speed);
                } else if (clientY > viewportHeight - SCROLL_ZONE) {
                    // Scroll DOWN: faster the closer to bottom edge
                    const ratio = Math.max(0, (clientY - (viewportHeight - SCROLL_ZONE)) / SCROLL_ZONE);
                    const speed = Math.max(3, Math.round(ratio * MAX_SPEED));
                    window.scrollBy(0, speed);
                }
            }
            autoScrollAnimationRef.current = requestAnimationFrame(scrollLoop);
        };

        autoScrollAnimationRef.current = requestAnimationFrame(scrollLoop);
    };

    const stopAutoScroll = () => {
        if (autoScrollAnimationRef.current) {
            cancelAnimationFrame(autoScrollAnimationRef.current);
            autoScrollAnimationRef.current = null;
        }
        dragClientYRef.current = null;
    };

    // Auto-scroll listener during active drag
    useEffect(() => {
        if (draggedIndex === null) {
            stopAutoScroll();
            return;
        }

        startAutoScroll();

        const handleWindowDragOver = (e) => {
            dragClientYRef.current = e.clientY;
        };

        const handleWindowDragEnd = () => {
            stopAutoScroll();
            setDraggedIndex(null);
            setDragOverIndex(null);
        };

        window.addEventListener('dragover', handleWindowDragOver);
        window.addEventListener('dragend', handleWindowDragEnd);
        window.addEventListener('drop', handleWindowDragEnd);

        return () => {
            stopAutoScroll();
            window.removeEventListener('dragover', handleWindowDragOver);
            window.removeEventListener('dragend', handleWindowDragEnd);
            window.removeEventListener('drop', handleWindowDragEnd);
        };
    }, [draggedIndex]);

    // Drag and Drop Handlers
    const handleDragStart = (e, index) => {
        setDraggedIndex(index);
        dragClientYRef.current = e.clientY;
        e.dataTransfer.effectAllowed = 'move';
    };

    const handleDragOver = (e, index) => {
        e.preventDefault();
        dragClientYRef.current = e.clientY;
        e.dataTransfer.dropEffect = 'move';
        if (dragOverIndex !== index) {
            setDragOverIndex(index);
        }
    };

    const handleDrop = (e, targetIndex) => {
        e.preventDefault();
        stopAutoScroll();
        if (draggedIndex === null || draggedIndex === targetIndex) {
            setDraggedIndex(null);
            setDragOverIndex(null);
            return;
        }

        const updated = [...films];
        const [movedItem] = updated.splice(draggedIndex, 1);
        updated.splice(targetIndex, 0, movedItem);

        const reindexed = updated.map((f, idx) => ({ ...f, sequence: idx + 1 }));
        setFilms(reindexed);
        setDraggedIndex(null);
        setDragOverIndex(null);
    };

    const handleDragEnd = () => {
        stopAutoScroll();
        setDraggedIndex(null);
        setDragOverIndex(null);
    };

    // Auto Sort by Release Year
    const handleSortByYear = () => {
        const sorted = [...films].sort((a, b) => {
            const yA = parseInt(a.release_year) || 9999;
            const yB = parseInt(b.release_year) || 9999;
            return yA - yB;
        }).map((f, idx) => ({ ...f, sequence: idx + 1 }));
        setFilms(sorted);
        showToast('Urutan disesuaikan berdasarkan tahun rilis film.');
    };

    // Save All Changes (Metadata + Reordered Films)
    const handleSaveAll = async () => {
        setIsSaving(true);
        try {
            // 1. Update Metadata
            const metaRes = await fetch(`/collections/${collection.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    name,
                    description,
                    status,
                    cover_image: coverImage,
                }),
            });

            // 2. Update Reordered Watch Order
            const items = films.map((f, idx) => ({
                film_id: f.id,
                sequence: idx + 1,
                note: f.note || '',
            }));

            const reorderRes = await fetch(`/collections/${collection.id}/reorder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ items }),
            });

            if (metaRes.ok && reorderRes.ok) {
                showToast('✨ Semua perubahan koleksi dan urutan nonton berhasil disimpan!');
            } else {
                showToast('Terjadi kendala saat menyimpan perubahan.', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Gagal menyimpan perubahan.', 'error');
        } finally {
            setIsSaving(false);
        }
    };

    const handleDeleteCollection = async () => {
        if (!confirm(`Apakah Anda yakin ingin menghapus koleksi '${collection.name}'? Tindakan ini tidak dapat dibatalkan.`)) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/collections/${collection.id}`;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';

        form.appendChild(csrfInput);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        form.submit();
    };

    return (
        <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 animate-fade-in text-white">
            {/* Toast Notification */}
            {toast && (
                <div className={`fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl backdrop-blur-xl border transition-all duration-300 ${
                    toast.type === 'error' 
                        ? 'bg-red-500/20 border-red-500/40 text-red-200' 
                        : 'bg-emerald-500/20 border-emerald-500/40 text-emerald-200'
                }`}>
                    {toast.type === 'error' ? <AlertCircle className="w-5 h-5 flex-shrink-0" /> : <Check className="w-5 h-5 flex-shrink-0" />}
                    <span className="text-sm font-medium">{toast.message}</span>
                </div>
            )}

            {/* Top Workspace Header */}
            <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-6 border-b border-white/10">
                <div>
                    <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-amber-400/90 mb-2">
                        <Layers className="w-4 h-4" />
                        <span>Koleksi Saya</span>
                        <ChevronRight className="w-3.5 h-3.5 text-zinc-500" />
                        <span className="text-zinc-400">Studio Editor</span>
                    </div>
                    <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3 flex-wrap">
                        <span>{name || 'Koleksi Tanpa Judul'}</span>
                        <span className={`text-xs px-2.5 py-1 rounded-full font-bold uppercase tracking-wider ${
                            status === 'takedown' || collection.taken_down_at ? 'bg-red-500/20 text-red-400 border border-red-500/30' :
                            status === 'published' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' :
                            status === 'private' ? 'bg-zinc-800 text-zinc-300 border border-white/10' :
                            'bg-zinc-700/50 text-zinc-400 border border-zinc-600/50'
                        }`}>
                            {status === 'takedown' || collection.taken_down_at ? '🚫 Takedown' : status === 'published' ? '🌐 Published' : status === 'private' ? '🔒 Private' : '📝 Draft'}
                        </span>
                    </h1>
                    <p className="text-sm text-zinc-400 mt-1">
                        Atur detail koleksi, tambah film, dan geser (drag & drop) untuk mengatur urutan nonton timeline yang ideal.
                    </p>

                    {(status === 'takedown' || collection.taken_down_at) && (
                        <div className="mt-3 p-3.5 rounded-2xl bg-red-500/15 border border-red-500/30 text-red-300 text-xs flex items-start gap-2.5">
                            <AlertCircle className="w-4 h-4 text-red-400 shrink-0 mt-0.5" />
                            <div>
                                <p className="font-bold text-white">Koleksi ini sedang di-takedown oleh Admin Faiilmov</p>
                                <p className="mt-0.5">Alasan: {collection.takedown_reason || 'Melanggar Pedoman Komunitas'}. Koleksi ini tidak dapat diakses oleh penonton umum.</p>
                            </div>
                        </div>
                    )}
                </div>

                {/* Header Action Buttons */}
                <div className="flex items-center gap-3">
                    <a
                        href={`/collections/${collection.slug}`}
                        target="_blank"
                        rel="noreferrer"
                        className="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-zinc-800/80 hover:bg-zinc-700/80 border border-white/10 text-zinc-200 text-sm font-semibold transition"
                    >
                        <Eye className="w-4 h-4" />
                        <span>Lihat Publik</span>
                    </a>

                    <button
                        type="button"
                        onClick={handleSaveAll}
                        disabled={isSaving}
                        className="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-sm font-extrabold shadow-lg shadow-white/10 transition active:scale-95 disabled:opacity-50 cursor-pointer"
                    >
                        {isSaving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                        <span>{isSaving ? 'Menyimpan...' : 'Simpan Perubahan'}</span>
                    </button>
                </div>
            </div>

            {/* Main Studio Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                {/* Left Column: Metadata & Privacy Settings (4 Cols) */}
                <div className="lg:col-span-4 space-y-6">
                    
                    {/* Metadata Card */}
                    <div className="p-6 rounded-3xl bg-zinc-900/80 border border-white/10 backdrop-blur-xl shadow-xl space-y-5">
                        <div className="flex items-center gap-2 text-sm font-bold text-amber-400">
                            <FileEdit className="w-4 h-4" />
                            <span>Pengaturan Koleksi</span>
                        </div>

                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1.5">
                                Judul Koleksi *
                            </label>
                            <input
                                type="text"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder="Contoh: Marvel Cinematic Universe Phase 1"
                                className="w-full px-4 py-3 rounded-2xl bg-zinc-800/80 border border-white/10 text-white text-sm font-medium focus:outline-none focus:border-amber-400/80 transition"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1.5">
                                Deskripsi Sinematik
                            </label>
                            <textarea
                                rows={4}
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder="Tuliskan latar belakang atau panduan singkat tentang koleksi ini..."
                                className="w-full px-4 py-3 rounded-2xl bg-zinc-800/80 border border-white/10 text-white text-sm font-medium focus:outline-none focus:border-amber-400/80 transition resize-none"
                            />
                        </div>

                        {/* Privacy Switcher */}
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">
                                Status Visibilitas
                            </label>
                            <div className="grid grid-cols-3 gap-2.5">
                                <button
                                    type="button"
                                    onClick={() => setStatus('published')}
                                    className={`flex flex-col items-center justify-center p-3 rounded-2xl border text-xs font-bold transition cursor-pointer ${
                                        status === 'published'
                                            ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-400 shadow-md shadow-emerald-500/10'
                                            : 'bg-zinc-800/50 border-white/5 text-zinc-400 hover:border-white/20'
                                    }`}
                                >
                                    <Globe className="w-4 h-4 mb-1" />
                                    <span>Published</span>
                                    <span className="text-[10px] font-normal text-zinc-400 mt-0.5">Semua Orang</span>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setStatus('private')}
                                    className={`flex flex-col items-center justify-center p-3 rounded-2xl border text-xs font-bold transition cursor-pointer ${
                                        status === 'private'
                                            ? 'bg-amber-500/20 border-amber-500/50 text-amber-400 shadow-md shadow-amber-500/10'
                                            : 'bg-zinc-800/50 border-white/5 text-zinc-400 hover:border-white/20'
                                    }`}
                                >
                                    <Lock className="w-4 h-4 mb-1" />
                                    <span>Private</span>
                                    <span className="text-[10px] font-normal text-zinc-400 mt-0.5">Hanya Saya</span>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setStatus('draft')}
                                    className={`flex flex-col items-center justify-center p-3 rounded-2xl border text-xs font-bold transition cursor-pointer ${
                                        status === 'draft'
                                            ? 'bg-zinc-700/60 border-zinc-500 text-zinc-200'
                                            : 'bg-zinc-800/50 border-white/5 text-zinc-400 hover:border-white/20'
                                    }`}
                                >
                                    <FileEdit className="w-4 h-4 mb-1" />
                                    <span>Draft</span>
                                    <span className="text-[10px] font-normal text-zinc-400 mt-0.5">Konsep</span>
                                </button>
                            </div>
                        </div>

                        {/* Cover Image URL */}
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1.5">
                                URL Cover Gambar (Otomatis dari film jika kosong)
                            </label>
                            <input
                                type="url"
                                value={coverImage}
                                onChange={(e) => setCoverImage(e.target.value)}
                                placeholder="https://..."
                                className="w-full px-4 py-2.5 rounded-2xl bg-zinc-800/80 border border-white/10 text-white text-xs font-medium focus:outline-none focus:border-amber-400/80 transition"
                            />
                        </div>
                    </div>

                    {/* Danger Zone: Delete Collection */}
                    <div className="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-between">
                        <div>
                            <p className="text-xs font-bold text-red-400">Hapus Koleksi Ini</p>
                            <p className="text-[11px] text-zinc-400">Menghapus koleksi dari profil dan sistem</p>
                        </div>
                        <button
                            type="button"
                            onClick={handleDeleteCollection}
                            className="px-3.5 py-1.5 rounded-xl bg-red-500/20 hover:bg-red-500 text-red-300 hover:text-white border border-red-500/30 text-xs font-bold transition cursor-pointer"
                        >
                            Hapus
                        </button>
                    </div>
                </div>

                {/* Right Column: Prominent Add Film Button & Watch Order Drag & Drop (8 Cols) */}
                <div className="lg:col-span-8 space-y-5">
                    
                    {/* Top Action Bar & Studio Film Search */}
                    <div className="p-4 sm:p-5 rounded-3xl bg-zinc-900/80 border border-white/10 backdrop-blur-xl shadow-lg space-y-4">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400">
                                    <Clock className="w-5 h-5" />
                                </div>
                                <div>
                                    <h2 className="text-base font-extrabold text-white">Urutan Nonton / Timeline</h2>
                                    <p className="text-xs text-zinc-400">
                                        {films.length} Film dalam Koleksi {filterQuery.trim() ? `• ${filteredFilms.length} cocok` : '• Drag & drop untuk atur kronologi'}
                                    </p>
                                </div>
                            </div>

                            {/* Action Buttons: Add Film & Auto-Sort */}
                            <div className="flex items-center gap-2.5 flex-wrap">
                                <button
                                    type="button"
                                    onClick={() => handleOpenAddModal(filterQuery)}
                                    className="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-extrabold shadow-lg shadow-white/10 transition active:scale-95 cursor-pointer"
                                >
                                    <PlusCircle className="w-4 h-4" />
                                    <span>Tambah Film</span>
                                </button>

                                {films.length > 1 && (
                                    <button
                                        type="button"
                                        onClick={handleSortByYear}
                                        className="flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 border border-white/10 text-xs font-semibold text-zinc-300 transition cursor-pointer"
                                        title="Urutkan otomatis dari tahun rilis terlama ke terbaru"
                                    >
                                        <Calendar className="w-3.5 h-3.5 text-zinc-400" />
                                        <span>Urutkan Thn Rilis</span>
                                    </button>
                                )}
                            </div>
                        </div>

                        {/* Search & Filter Bar Inside Studio */}
                        {films.length > 0 && (
                            <div className="flex items-center gap-2.5 pt-3 border-t border-white/5">
                                <div className="relative flex-1">
                                    <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-500 pointer-events-none" />
                                    <input
                                        ref={inStudioSearchRef}
                                        type="text"
                                        value={filterQuery}
                                        onChange={(e) => setFilterQuery(e.target.value)}
                                        placeholder="Cari film di koleksi ini (judul, tahun, genre, catatan)..."
                                        className="w-full pl-10 pr-24 py-2.5 rounded-2xl bg-zinc-800/80 hover:bg-zinc-800 focus:bg-zinc-800 border border-white/10 hover:border-white/20 focus:border-amber-400/80 text-white placeholder-zinc-500 text-xs font-medium focus:outline-none transition"
                                    />
                                    {filterQuery && (
                                        <div className="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                                            <span className="text-[10px] font-mono text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">
                                                {filteredFilms.length} / {films.length}
                                            </span>
                                            <button
                                                type="button"
                                                onClick={() => setFilterQuery('')}
                                                className="p-1 rounded-md text-zinc-400 hover:text-white hover:bg-zinc-700 transition cursor-pointer"
                                                title="Hapus filter (Esc)"
                                            >
                                                <X className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    )}
                                </div>

                                <button
                                    type="button"
                                    onClick={() => handleOpenAddModal(filterQuery)}
                                    className="hidden sm:flex items-center gap-1.5 px-3.5 py-2.5 rounded-2xl bg-zinc-800 hover:bg-zinc-700 border border-white/10 text-zinc-300 hover:text-white text-xs font-semibold transition shrink-0 cursor-pointer"
                                    title="Cari film baru di seluruh katalog Faiilmov"
                                >
                                    <Plus className="w-3.5 h-3.5 text-amber-400" />
                                    <span>Cari di Katalog</span>
                                </button>
                            </div>
                        )}
                    </div>

                    {/* Filter Active Notice Banner */}
                    {filterQuery.trim() && filteredFilms.length > 0 && (
                        <div className="px-4 py-2.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-between text-xs text-amber-300">
                            <div className="flex items-center gap-2">
                                <Search className="w-3.5 h-3.5" />
                                <span>Menampilkan <strong>{filteredFilms.length}</strong> film yang cocok dengan "<strong>{filterQuery}</strong>" (urutan <strong>#</strong> tetap sesuai timeline asli).</span>
                            </div>
                            <button
                                type="button"
                                onClick={() => setFilterQuery('')}
                                className="underline hover:text-white text-[11px] font-bold cursor-pointer"
                            >
                                Reset Filter
                            </button>
                        </div>
                    )}

                    {/* Film Draggable List */}
                    {films.length === 0 ? (
                        <div className="p-12 text-center rounded-3xl bg-zinc-900/40 border border-dashed border-white/10 space-y-4">
                            <div className="w-16 h-16 rounded-3xl bg-zinc-900 border border-white/10 flex items-center justify-center mx-auto text-zinc-600 shadow-inner">
                                <FilmIcon className="w-8 h-8" />
                            </div>
                            <div className="space-y-1">
                                <h3 className="text-base font-bold text-zinc-200">Belum Ada Film di Koleksi Ini</h3>
                                <p className="text-xs text-zinc-400 max-w-sm mx-auto">
                                    Klik tombol Tambah Film di atas untuk mencari dan menyusun film ke dalam timeline urutan nonton.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => handleOpenAddModal()}
                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold transition shadow-lg shadow-white/10 cursor-pointer"
                            >
                                <Plus className="w-4 h-4" />
                                <span>Tambah Film Sekarang</span>
                            </button>
                        </div>
                    ) : filteredFilms.length === 0 ? (
                        <div className="p-10 text-center rounded-3xl bg-zinc-900/40 border border-dashed border-white/10 space-y-4 animate-in fade-in duration-200">
                            <div className="w-14 h-14 rounded-2xl bg-zinc-900 border border-white/10 flex items-center justify-center mx-auto text-zinc-600">
                                <Search className="w-6 h-6" />
                            </div>
                            <div className="space-y-1">
                                <h3 className="text-sm font-bold text-zinc-200">
                                    Tidak ada film di koleksi ini yang cocok dengan "{filterQuery}"
                                </h3>
                                <p className="text-xs text-zinc-400 max-w-sm mx-auto">
                                    Film tersebut belum dimasukkan ke koleksi ini. Anda dapat mencarinya di katalog lengkap Faiilmov dan menambahkannya.
                                </p>
                            </div>
                            <div className="flex items-center justify-center gap-3 pt-2">
                                <button
                                    type="button"
                                    onClick={() => handleOpenAddModal(filterQuery)}
                                    className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold transition shadow-lg shadow-white/10 cursor-pointer"
                                >
                                    <Plus className="w-4 h-4" />
                                    <span>Cari "{filterQuery}" di Katalog Faiilmov</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setFilterQuery('')}
                                    className="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-medium transition cursor-pointer"
                                >
                                    Hapus Pencarian
                                </button>
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {filteredFilms.map((f) => {
                                const index = f.originalIndex;
                                const isDragging = draggedIndex === index;
                                const isOver = dragOverIndex === index;
                                const isFiltered = Boolean(filterQuery.trim());

                                return (
                                    <div
                                        key={f.id}
                                        draggable={!isFiltered}
                                        onDragStart={(e) => handleDragStart(e, index)}
                                        onDragOver={(e) => handleDragOver(e, index)}
                                        onDrop={(e) => handleDrop(e, index)}
                                        onDragEnd={handleDragEnd}
                                        className={`group relative p-3.5 sm:p-4 rounded-3xl bg-zinc-900/90 border transition-all duration-200 shadow-lg ${
                                            isDragging 
                                                ? 'opacity-40 scale-98 border-amber-500/60 bg-amber-500/5' 
                                                : isOver 
                                                ? 'border-amber-400 bg-amber-400/10 scale-101' 
                                                : 'border-white/10 hover:border-white/20'
                                        }`}
                                    >
                                        <div className="flex items-start gap-3 sm:gap-4">
                                            
                                            {/* Drag Grip Handle */}
                                            <div 
                                                className={`p-2 rounded-xl text-zinc-500 transition self-center ${
                                                    isFiltered 
                                                        ? 'opacity-30 cursor-not-allowed' 
                                                        : 'cursor-grab active:cursor-grabbing hover:bg-white/10 hover:text-amber-400'
                                                }`}
                                                title={isFiltered ? 'Nonaktifkan filter untuk drag & drop susunan bebas' : 'Tahan dan geser (drag & drop) untuk mengubah urutan'}
                                            >
                                                <GripVertical className="w-5 h-5" />
                                            </div>

                                            {/* Sequence Badge */}
                                            <div className="w-8 h-8 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-400 font-extrabold text-xs flex items-center justify-center flex-shrink-0 self-center">
                                                #{index + 1}
                                            </div>

                                            {/* Poster Thumbnail */}
                                            <img
                                                src={f.poster_url || '/placeholder-poster.jpg'}
                                                alt={f.title}
                                                className="w-12 h-16 sm:w-14 sm:h-20 rounded-xl object-cover flex-shrink-0 bg-zinc-950 shadow-md"
                                                onError={(e) => { e.target.src = 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=100&q=80'; }}
                                            />

                                            {/* Film Info & Note Field */}
                                            <div className="flex-1 min-w-0 space-y-2">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div>
                                                        <h4 className="text-sm sm:text-base font-bold text-white truncate group-hover:text-amber-400 transition">
                                                            {f.title}
                                                        </h4>
                                                        <div className="flex items-center gap-2 text-xs text-zinc-400 mt-0.5">
                                                            <span>{f.release_year || 'N/A'}</span>
                                                            {f.genres && f.genres.length > 0 && (
                                                                <>
                                                                    <span>•</span>
                                                                    <span className="text-zinc-500 truncate max-w-[150px]">{f.genres.slice(0, 2).join(', ')}</span>
                                                                </>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Up / Down Arrow & Delete Action */}
                                                    <div className="flex items-center gap-1 flex-shrink-0">
                                                        <button
                                                            type="button"
                                                            onClick={() => moveFilm(index, -1)}
                                                            disabled={index === 0}
                                                            className="p-1.5 rounded-lg hover:bg-white/10 text-zinc-400 hover:text-white disabled:opacity-20 transition cursor-pointer"
                                                            title="Geser Naik"
                                                        >
                                                            <ArrowUp className="w-3.5 h-3.5" />
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => moveFilm(index, 1)}
                                                            disabled={index === films.length - 1}
                                                            className="p-1.5 rounded-lg hover:bg-white/10 text-zinc-400 hover:text-white disabled:opacity-20 transition cursor-pointer"
                                                            title="Geser Turun"
                                                        >
                                                            <ArrowDown className="w-3.5 h-3.5" />
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleRemoveFilm(f.id)}
                                                            className="p-1.5 rounded-lg hover:bg-red-500/20 text-zinc-500 hover:text-red-400 transition cursor-pointer"
                                                            title="Hapus dari koleksi"
                                                        >
                                                            <Trash2 className="w-3.5 h-3.5" />
                                                        </button>
                                                    </div>
                                                </div>

                                                {/* Inline Note / Lore Input */}
                                                <input
                                                    type="text"
                                                    value={f.note || ''}
                                                    onChange={(e) => handleNoteChange(f.id, e.target.value)}
                                                    placeholder="Tambahkan catatan urutan (misal: 'Tonton setelah Episode 4' atau 'Prekuel')..."
                                                    className="w-full px-3 py-1.5 rounded-xl bg-zinc-800/60 border border-white/5 hover:border-white/10 focus:border-amber-400 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none transition"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>

            {/* DEDICATED SEARCH & ADD FILM MODAL */}
            {isAddModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
                    {/* Backdrop */}
                    <div 
                        className="fixed inset-0 bg-black/80 backdrop-blur-xl transition-opacity animate-in fade-in duration-200"
                        onClick={() => setIsAddModalOpen(false)}
                    />

                    {/* Modal Content */}
                    <div className="relative w-full max-w-2xl bg-zinc-950 border border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl z-10 overflow-hidden animate-in zoom-in-95 duration-200 space-y-6">
                        {/* Modal Header */}
                        <div className="flex items-center justify-between relative z-10">
                            <div>
                                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/10 text-zinc-300 text-xs font-semibold mb-2">
                                    <FilmIcon className="w-3.5 h-3.5 text-zinc-400" />
                                    <span>Katalog Faiilmov</span>
                                </div>
                                <h3 className="text-xl sm:text-2xl font-extrabold text-white">Cari & Tambah Film</h3>
                                <p className="text-xs text-zinc-400 mt-0.5">Ketik judul film untuk dimasukkan ke urutan tontonan</p>
                            </div>

                            <button
                                type="button"
                                onClick={() => setIsAddModalOpen(false)}
                                className="p-2 rounded-full text-zinc-400 hover:text-white hover:bg-white/10 transition cursor-pointer"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Search Input Bar */}
                        <div className="relative z-10">
                            <Search className="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500" />
                            <input
                                ref={searchInputRef}
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Ketik judul film (misal: Spider-Man, Avengers, Inception)..."
                                className="w-full pl-12 pr-12 py-3.5 rounded-2xl bg-zinc-900 border border-white/10 text-white placeholder-zinc-500 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400/50 transition"
                            />
                            {isSearching && (
                                <Loader2 className="w-5 h-5 animate-spin absolute right-4 top-1/2 -translate-y-1/2 text-amber-400" />
                            )}
                            {!isSearching && searchQuery && (
                                <button
                                    type="button"
                                    onClick={() => setSearchQuery('')}
                                    className="p-1 rounded-full text-zinc-500 hover:text-white absolute right-4 top-1/2 -translate-y-1/2"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            )}
                        </div>

                        {/* Search Results List */}
                        <div className="relative z-10 max-h-96 overflow-y-auto pr-1 space-y-2.5">
                            {searchResults.length > 0 ? (
                                searchResults.map((f) => {
                                    const isAlreadyAdded = films.some(item => item.id === f.id);

                                    return (
                                        <div
                                            key={f.id}
                                            className="flex items-center justify-between p-3 rounded-2xl bg-zinc-900/80 hover:bg-zinc-850 border border-white/5 transition"
                                        >
                                            <div className="flex items-center gap-3.5 min-w-0">
                                                <img
                                                    src={f.poster_url || '/placeholder-poster.jpg'}
                                                    alt={f.title}
                                                    className="w-11 h-15 rounded-xl object-cover flex-shrink-0 bg-zinc-950 shadow-md"
                                                    onError={(e) => { e.target.src = 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=100&q=80'; }}
                                                />
                                                <div className="min-w-0">
                                                    <p className="text-sm font-bold text-white truncate">{f.title}</p>
                                                    <div className="flex items-center gap-2 text-xs text-zinc-400 mt-1">
                                                        <span>{f.release_year || 'N/A'}</span>
                                                        <span>•</span>
                                                        <span className="flex items-center gap-1 text-amber-400 font-semibold">
                                                            <Star className="w-3 h-3 fill-current" />
                                                            {f.rating ? parseFloat(f.rating).toFixed(1) : '-'}
                                                        </span>
                                                        {f.genres && f.genres.length > 0 && (
                                                            <>
                                                                <span>•</span>
                                                                <span className="text-zinc-500 truncate max-w-[120px]">{f.genres.slice(0, 2).join(', ')}</span>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                onClick={() => handleAddFilm(f)}
                                                disabled={isAlreadyAdded}
                                                className={`flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition flex-shrink-0 cursor-pointer ${
                                                    isAlreadyAdded
                                                        ? 'bg-zinc-800 text-zinc-500 border border-white/5 cursor-not-allowed'
                                                        : 'bg-white hover:bg-zinc-200 text-zinc-950 shadow-md shadow-white/10 active:scale-95'
                                                }`}
                                            >
                                                {isAlreadyAdded ? (
                                                    <>
                                                        <Check className="w-3.5 h-3.5 text-emerald-400" />
                                                        <span>Sudah Ada</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <Plus className="w-3.5 h-3.5" />
                                                        <span>Tambah</span>
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    );
                                })
                            ) : searchQuery.trim().length >= 2 ? (
                                !isSearching && (
                                    <div className="py-12 text-center space-y-2">
                                        <p className="text-sm font-bold text-zinc-400">Tidak ada film yang cocok dengan "{searchQuery}"</p>
                                        <p className="text-xs text-zinc-500">Coba kata kunci lain atau periksa ejaan judul.</p>
                                    </div>
                                )
                            ) : (
                                <div className="py-10 text-center space-y-2 text-zinc-500">
                                    <Search className="w-8 h-8 mx-auto opacity-30" />
                                    <p className="text-xs">Ketik minimal 2 huruf untuk mencari film di katalog Faiilmov.</p>
                                </div>
                            )}
                        </div>

                        {/* Modal Footer */}
                        <div className="pt-4 border-t border-white/10 flex items-center justify-between relative z-10">
                            <span className="text-xs text-zinc-400">
                                {films.length} film di koleksi
                            </span>
                            <button
                                type="button"
                                onClick={() => setIsAddModalOpen(false)}
                                className="px-5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-bold transition cursor-pointer"
                            >
                                Selesai & Tutup
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
