import React, { useState, useEffect } from 'react';
import { Sparkles, Wand2, X, Film, Loader2, ArrowRight, Layers, FileEdit, Globe, Lock } from 'lucide-react';

const PRESET_PROMPTS = [
  "Marvel Cinematic Universe Saga Infinity",
  "Film horor psikologis dengan plot twist tak terduga",
  "Aksi luar angkasa era 90an",
  "Romansa manis drama korea latar musim dingin",
  "Petualangan fantasi xianxia kultivasi dewa",
  "Cyberpunk distopia masa depan gelap",
];

export default function CreateCollectionModal({ isOpen: initialOpen = false, csrfToken = '' }) {
  const [isOpen, setIsOpen] = useState(initialOpen);
  const [activeTab, setActiveTab] = useState('manual'); // 'manual' | 'ai'
  
  // Manual form state
  const [manualName, setManualName] = useState('');
  const [manualDescription, setManualDescription] = useState('');
  const [manualStatus, setManualStatus] = useState('published');
  
  // AI form state
  const [prompt, setPrompt] = useState('');
  
  // Loading & error
  const [loading, setLoading] = useState(false);
  const [loadingStep, setLoadingStep] = useState(0);
  const [error, setError] = useState(null);

  useEffect(() => {
    const handleOpen = () => {
      setIsOpen(true);
      setError(null);
    };
    window.addEventListener('open-create-collection-modal', handleOpen);
    return () => window.removeEventListener('open-create-collection-modal', handleOpen);
  }, []);

  useEffect(() => {
    let interval;
    if (loading) {
      interval = setInterval(() => {
        setLoadingStep((prev) => (prev < 3 ? prev + 1 : prev));
      }, 1400);
    } else {
      setLoadingStep(0);
    }
    return () => clearInterval(interval);
  }, [loading]);

  const stepLabels = [
    "Menganalisis prompt & struktur tema...",
    "Menghitung vector similarity embedding...",
    "Memverifikasi film & menyusun urutan nonton...",
    "Membuka Studio Editor kamu...",
  ];

  // Submit Manual Form
  const handleManualSubmit = async (e) => {
    if (e) e.preventDefault();
    if (!manualName.trim() || loading) return;

    setLoading(true);
    setError(null);

    const token = csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    try {
      const response = await fetch('/collections', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({
          name: manualName.trim(),
          description: manualDescription.trim(),
          status: manualStatus,
        }),
      });

      const data = await response.json();

      if (response.ok && data.success && data.redirect_url) {
        window.location.href = data.redirect_url;
      } else {
        setError(data.message || 'Gagal membuat koleksi. Silakan periksa kembali data Anda.');
        setLoading(false);
      }
    } catch (err) {
      setError('Terjadi kendala jaringan saat menghubungi server.');
      setLoading(false);
    }
  };

  // Submit AI Prompt Form
  const handleAiSubmit = async (e) => {
    if (e) e.preventDefault();
    if (!prompt.trim() || loading) return;

    setLoading(true);
    setError(null);

    const token = csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    try {
      const response = await fetch('/collections/from-prompt', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ prompt: prompt.trim() }),
      });

      const data = await response.json();

      if (response.ok && data.success && (data.redirect_url || data.collection?.slug)) {
        window.location.href = data.redirect_url || `/collections/${data.collection.slug}/edit`;
      } else {
        setError(data.message || 'Gagal meracik koleksi AI. Silakan coba prompt lain.');
        setLoading(false);
      }
    } catch (err) {
      setError('Terjadi kendala jaringan saat menghubungi AI Engine.');
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
      {/* Backdrop */}
      <div 
        className="fixed inset-0 bg-black/80 backdrop-blur-xl transition-opacity animate-in fade-in duration-200"
        onClick={() => !loading && setIsOpen(false)}
      />

      {/* Modal Card */}
      <div className="relative w-full max-w-xl bg-zinc-950 border border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl z-10 overflow-hidden animate-in zoom-in-95 duration-200 text-white">
        {/* Close Button */}
        {!loading && (
          <button
            type="button"
            onClick={() => setIsOpen(false)}
            className="absolute top-5 right-5 p-2 rounded-full text-zinc-400 hover:text-white hover:bg-white/10 transition z-20"
          >
            <X className="w-5 h-5" />
          </button>
        )}

        {/* Header */}
        <div className="relative z-10 space-y-2 mb-6">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/10 text-zinc-300 text-xs font-semibold">
            <Layers className="w-3.5 h-3.5 text-zinc-400" />
            <span>Koleksi Pengguna & Studio</span>
          </div>
          <h3 className="font-serif text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            Buat Koleksi Film Baru
          </h3>
          <p className="text-xs sm:text-sm text-zinc-400">
            Rangkai film favoritmu, atur urutan nonton ideal secara drag & drop, dan bagikan ke komunitas.
          </p>
        </div>

        {/* Tab Switcher: Manual vs AI */}
        {!loading && (
          <div className="grid grid-cols-2 gap-2 p-1.5 rounded-2xl bg-zinc-900/80 border border-white/10 mb-6">
            <button
              type="button"
              onClick={() => setActiveTab('manual')}
              className={`flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer ${
                activeTab === 'manual'
                  ? 'bg-white text-zinc-950 shadow-md'
                  : 'text-zinc-400 hover:text-white'
              }`}
            >
              <FileEdit className="w-4 h-4" />
              <span>Mulai dari Nol (Manual)</span>
            </button>

            <button
              type="button"
              onClick={() => setActiveTab('ai')}
              className={`flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer ${
                activeTab === 'ai'
                  ? 'bg-white text-zinc-950 shadow-md'
                  : 'text-zinc-400 hover:text-white'
              }`}
            >
              <Sparkles className="w-4 h-4 text-zinc-950" />
              <span>Bantuan AI Prompt</span>
            </button>
          </div>
        )}

        {/* Loading Indicator State */}
        {loading ? (
          <div className="py-12 flex flex-col items-center justify-center text-center space-y-6 animate-pulse">
            <div className="relative">
              <div className="w-16 h-16 rounded-full border-4 border-white/20 border-t-white animate-spin" />
              <div className="absolute inset-0 flex items-center justify-center">
                <Sparkles className="w-6 h-6 text-white animate-bounce" />
              </div>
            </div>
            <div className="space-y-2">
              <h4 className="text-base font-bold text-white">Sedang Mempersiapkan Studio Koleksi...</h4>
              <p className="text-xs text-zinc-300 font-mono tracking-wide">
                {stepLabels[loadingStep] || stepLabels[0]}
              </p>
            </div>
          </div>
        ) : (
          <>
            {/* Error Message */}
            {error && (
              <div className="mb-5 p-3.5 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs flex items-center gap-2.5">
                <span className="w-2 h-2 rounded-full bg-red-500 flex-shrink-0" />
                <span>{error}</span>
              </div>
            )}

            {/* TAB 1: MANUAL CREATION FORM */}
            {activeTab === 'manual' && (
              <form onSubmit={handleManualSubmit} className="space-y-4">
                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1.5">
                    Nama Koleksi *
                  </label>
                  <input
                    type="text"
                    required
                    value={manualName}
                    onChange={(e) => setManualName(e.target.value)}
                    placeholder="Contoh: Film Sci-Fi Mind-Bending Pilihan"
                    className="w-full px-4 py-3 rounded-2xl bg-zinc-900 border border-white/10 text-white text-sm focus:outline-none focus:border-white/50 transition"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1.5">
                    Deskripsi Singkat (Opsional)
                  </label>
                  <textarea
                    rows={2}
                    value={manualDescription}
                    onChange={(e) => setManualDescription(e.target.value)}
                    placeholder="Panduan atau sinopsis singkat untuk penonton..."
                    className="w-full px-4 py-2.5 rounded-2xl bg-zinc-900 border border-white/10 text-white text-sm focus:outline-none focus:border-white/50 transition resize-none"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1.5">
                    Status Visibilitas Awal
                  </label>
                  <div className="grid grid-cols-3 gap-2">
                    <button
                      type="button"
                      onClick={() => setManualStatus('published')}
                      className={`flex items-center justify-center gap-1.5 py-2.5 rounded-xl border text-xs font-bold transition cursor-pointer ${
                        manualStatus === 'published'
                          ? 'bg-emerald-500/20 border-emerald-500 text-emerald-300'
                          : 'bg-zinc-900 border-white/5 text-zinc-400 hover:border-white/20'
                      }`}
                    >
                      <Globe className="w-3.5 h-3.5" />
                      <span>Published</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => setManualStatus('private')}
                      className={`flex items-center justify-center gap-1.5 py-2.5 rounded-xl border text-xs font-bold transition cursor-pointer ${
                        manualStatus === 'private'
                          ? 'bg-zinc-800 border-white/30 text-white'
                          : 'bg-zinc-900 border-white/5 text-zinc-400 hover:border-white/20'
                      }`}
                    >
                      <Lock className="w-3.5 h-3.5" />
                      <span>Private</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => setManualStatus('draft')}
                      className={`flex items-center justify-center gap-1.5 py-2.5 rounded-xl border text-xs font-bold transition cursor-pointer ${
                        manualStatus === 'draft'
                          ? 'bg-zinc-700/60 border-zinc-500 text-zinc-200'
                          : 'bg-zinc-900 border-white/5 text-zinc-400 hover:border-white/20'
                      }`}
                    >
                      <FileEdit className="w-3.5 h-3.5" />
                      <span>Draft</span>
                    </button>
                  </div>
                </div>

                <div className="pt-2">
                  <button
                    type="submit"
                    disabled={!manualName.trim()}
                    className="w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-extrabold text-sm shadow-lg shadow-white/10 transition active:scale-98 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                  >
                    <span>Buka Studio & Tambah Film</span>
                    <ArrowRight className="w-4 h-4" />
                  </button>
                </div>
              </form>
            )}

            {/* TAB 2: AI PROMPT FORM */}
            {activeTab === 'ai' && (
              <form onSubmit={handleAiSubmit} className="space-y-4">
                <div className="relative">
                  <textarea
                    rows={3}
                    value={prompt}
                    onChange={(e) => setPrompt(e.target.value)}
                    placeholder="Contoh: Urutan nonton film Marvel Cinematic Universe Phase 1 sampai Endgame dengan cerita runtut..."
                    className="w-full px-4 py-3 rounded-2xl bg-zinc-900 border border-white/10 text-white placeholder-zinc-500 text-sm focus:outline-none focus:border-white/40 transition resize-none"
                  />
                  <Sparkles className="w-4 h-4 text-zinc-400 absolute right-3.5 bottom-3.5 opacity-60 pointer-events-none" />
                </div>

                {/* Preset Prompt Pills */}
                <div className="space-y-1.5">
                  <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                    Inspirasi Ide Prompt AI:
                  </span>
                  <div className="flex flex-wrap gap-1.5">
                    {PRESET_PROMPTS.map((p, idx) => (
                      <button
                        key={idx}
                        type="button"
                        onClick={() => setPrompt(p)}
                        className="px-2.5 py-1 rounded-xl text-[11px] bg-zinc-900/90 border border-white/10 hover:border-white/30 text-zinc-300 hover:text-white transition text-left cursor-pointer"
                      >
                        {p}
                      </button>
                    ))}
                  </div>
                </div>

                <div className="pt-2">
                  <button
                    type="submit"
                    disabled={!prompt.trim()}
                    className="w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-2xl bg-white hover:bg-zinc-200 text-zinc-950 font-extrabold text-sm shadow-lg shadow-white/10 transition active:scale-98 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                  >
                    <Wand2 className="w-4 h-4" />
                    <span>Racik Koleksi & Buka di Studio Editor</span>
                  </button>
                </div>
              </form>
            )}
          </>
        )}
      </div>
    </div>
  );
}
