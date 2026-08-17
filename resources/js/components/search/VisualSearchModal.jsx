import React, { useState, useEffect, useRef } from 'react';
import { 
  Camera, Upload, Sparkles, X, Image as ImageIcon, Search, 
  Loader2, Film, Layers, ChevronRight, Wand2, RefreshCw, CheckCircle2, AlertCircle 
} from 'lucide-react';

export default function VisualSearchModal({ csrfToken = '' }) {
  const [isOpen, setIsOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('upload'); // 'upload' | 'url'
  const [selectedFile, setSelectedFile] = useState(null);
  const [previewUrl, setPreviewUrl] = useState('');
  const [imageUrlInput, setImageUrlInput] = useState('');
  const [isScanning, setIsScanning] = useState(false);
  const [isCreatingCollection, setIsCreatingCollection] = useState(false);
  const [analysis, setAnalysis] = useState(null);
  const [results, setResults] = useState([]);
  const [error, setError] = useState(null);

  const fileInputRef = useRef(null);

  useEffect(() => {
    const handleOpen = () => {
      setIsOpen(true);
      setError(null);
    };

    window.addEventListener('open-visual-search-modal', handleOpen);
    return () => window.removeEventListener('open-visual-search-modal', handleOpen);
  }, []);

  const handleClose = () => {
    setIsOpen(false);
  };

  const handleFileChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      if (!file.type.startsWith('image/')) {
        setError('File harus berupa gambar (JPG, PNG, WebP).');
        return;
      }
      setSelectedFile(file);
      setPreviewUrl(URL.createObjectURL(file));
      setError(null);
      setAnalysis(null);
      setResults([]);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    const file = e.dataTransfer.files?.[0];
    if (file) {
      if (!file.type.startsWith('image/')) {
        setError('File harus berupa gambar (JPG, PNG, WebP).');
        return;
      }
      setSelectedFile(file);
      setPreviewUrl(URL.createObjectURL(file));
      setError(null);
      setAnalysis(null);
      setResults([]);
    }
  };

  const handleUrlSubmit = (e) => {
    e.preventDefault();
    if (imageUrlInput.trim()) {
      setPreviewUrl(imageUrlInput.trim());
      setSelectedFile(null);
      setError(null);
      setAnalysis(null);
      setResults([]);
    }
  };

  const handleAnalyze = async () => {
    if (!previewUrl) {
      setError('Silakan pilih gambar poster terlebih dahulu.');
      return;
    }

    setIsScanning(true);
    setError(null);
    setAnalysis(null);
    setResults([]);

    try {
      const formData = new FormData();
      if (selectedFile) {
        formData.append('image', selectedFile);
      } else {
        formData.append('image_url', previewUrl);
      }

      const res = await fetch('/search/by-image', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
        body: formData,
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Gagal memproses analisis visual poster.');
      }

      setAnalysis(data.analysis);
      setResults(data.results || []);
    } catch (err) {
      setError(err.message || 'Terjadi kesalahan saat memproses gambar.');
    } finally {
      setIsScanning(false);
    }
  };

  const handleCreateCollectionFromImage = async () => {
    if (!previewUrl) return;

    setIsCreatingCollection(true);
    setError(null);

    try {
      const formData = new FormData();
      if (selectedFile) {
        formData.append('image', selectedFile);
      } else {
        formData.append('image_url', previewUrl);
      }

      const res = await fetch('/collections/from-image', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
        body: formData,
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Gagal membuat koleksi visual.');
      }

      if (data.redirect_url) {
        window.location.href = data.redirect_url;
      }
    } catch (err) {
      setError(err.message || 'Gagal membuat koleksi visual.');
      setIsCreatingCollection(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/85 backdrop-blur-md overflow-y-auto animate-in fade-in duration-200">
      <div className="relative w-full max-w-4xl bg-zinc-950 border border-purple-500/30 rounded-3xl shadow-2xl overflow-hidden my-auto flex flex-col max-h-[90vh]">
        
        {/* Header */}
        <div className="flex items-center justify-between p-5 sm:p-6 border-b border-white/10 bg-gradient-to-r from-zinc-900 via-zinc-950 to-purple-950/40">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-gradient-to-tr from-cyan-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-cyan-500/20">
              <Camera className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="font-serif font-bold text-lg sm:text-xl text-white tracking-tight">
                  Pencarian Visual AI Poster
                </h3>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                  GEMINI VISION
                </span>
              </div>
              <p className="text-xs text-zinc-400 mt-0.5">
                Cari film, identifikasi semesta, atau buat koleksi dari gambar poster/cuplikan
              </p>
            </div>
          </div>

          <button 
            onClick={handleClose}
            className="w-9 h-9 rounded-xl bg-zinc-900/80 hover:bg-zinc-800 text-zinc-400 hover:text-white flex items-center justify-center border border-white/5 transition-all cursor-pointer"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Content Body */}
        <div className="p-5 sm:p-6 overflow-y-auto space-y-6 flex-1">

          {/* Upload / Source Section */}
          <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
            
            {/* Left: Image Selector / Dropzone */}
            <div className="md:col-span-5 space-y-4">
              <div className="flex items-center gap-2 border-b border-white/10 pb-2">
                <button
                  type="button"
                  onClick={() => setActiveTab('upload')}
                  className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                    activeTab === 'upload' ? 'bg-purple-600 text-white' : 'text-zinc-400 hover:text-white'
                  }`}
                >
                  Unggah File
                </button>
                <button
                  type="button"
                  onClick={() => setActiveTab('url')}
                  className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                    activeTab === 'url' ? 'bg-purple-600 text-white' : 'text-zinc-400 hover:text-white'
                  }`}
                >
                  Tautan URL
                </button>
              </div>

              {activeTab === 'upload' ? (
                <div
                  onDragOver={(e) => e.preventDefault()}
                  onDrop={handleDrop}
                  onClick={() => fileInputRef.current?.click()}
                  className={`relative aspect-[3/4] max-h-72 w-full rounded-2xl border-2 border-dashed transition-all flex flex-col items-center justify-center p-4 text-center cursor-pointer overflow-hidden group ${
                    previewUrl ? 'border-purple-500/50 bg-black/40' : 'border-zinc-800 hover:border-purple-500/40 bg-zinc-900/50'
                  }`}
                >
                  <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/*"
                    onChange={handleFileChange}
                    className="hidden"
                  />

                  {previewUrl ? (
                    <div className="relative w-full h-full">
                      <img
                        src={previewUrl}
                        alt="Preview"
                        className="w-full h-full object-contain rounded-xl"
                      />
                      <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-xs gap-1.5">
                        <Upload className="w-5 h-5 text-purple-400" />
                        <span>Klik untuk ganti gambar</span>
                      </div>
                    </div>
                  ) : (
                    <div className="space-y-2 text-zinc-400 group-hover:text-zinc-300">
                      <div className="w-12 h-12 rounded-2xl bg-zinc-800/80 flex items-center justify-center mx-auto text-purple-400 group-hover:scale-110 transition-transform">
                        <Upload className="w-6 h-6" />
                      </div>
                      <p className="text-xs font-semibold text-white">Drag & drop poster di sini</p>
                      <p className="text-[11px] text-zinc-500">atau klik untuk telusuri file</p>
                    </div>
                  )}

                  {/* Scanning Animation Radar Overlay */}
                  {isScanning && (
                    <div className="absolute inset-0 bg-purple-950/60 backdrop-blur-xs flex flex-col items-center justify-center text-cyan-300 pointer-events-none">
                      <div className="w-full h-1 bg-gradient-to-r from-transparent via-cyan-400 to-transparent absolute top-0 animate-[bounce_2s_infinite]"></div>
                      <Loader2 className="w-8 h-8 animate-spin text-cyan-400 mb-2" />
                      <span className="text-xs font-mono font-bold tracking-wider animate-pulse">
                        SCANNING POSTER AI...
                      </span>
                    </div>
                  )}
                </div>
              ) : (
                <div className="space-y-3">
                  <form onSubmit={handleUrlSubmit} className="flex gap-2">
                    <input
                      type="url"
                      placeholder="https://image.tmdb.org/...jpg"
                      value={imageUrlInput}
                      onChange={(e) => setImageUrlInput(e.target.value)}
                      className="flex-1 bg-zinc-900 border border-white/10 rounded-xl px-3 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-purple-500"
                    />
                    <button
                      type="submit"
                      className="px-3 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-xl text-xs font-bold cursor-pointer"
                    >
                      Load
                    </button>
                  </form>

                  {previewUrl && (
                    <div className="relative aspect-[3/4] max-h-56 w-full rounded-2xl border border-purple-500/30 overflow-hidden bg-black">
                      <img src={previewUrl} alt="Preview" className="w-full h-full object-contain" />
                    </div>
                  )}
                </div>
              )}

              {/* Action Buttons */}
              <button
                type="button"
                onClick={handleAnalyze}
                disabled={!previewUrl || isScanning}
                className={`w-full py-3 rounded-2xl font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all cursor-pointer ${
                  !previewUrl || isScanning
                    ? 'bg-zinc-800 text-zinc-500 cursor-not-allowed'
                    : 'bg-gradient-to-r from-cyan-500 to-purple-600 hover:from-cyan-400 hover:to-purple-500 text-white shadow-lg shadow-cyan-500/25 transform hover:-translate-y-0.5'
                }`}
              >
                {isScanning ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    <span>Menganalisis Visual Poster...</span>
                  </>
                ) : (
                  <>
                    <Sparkles className="w-4 h-4" />
                    <span>Scan & Cari Film Serupa</span>
                  </>
                )}
              </button>
            </div>

            {/* Right: Analysis & Results */}
            <div className="md:col-span-7 space-y-4">
              
              {/* Error Box */}
              {error && (
                <div className="p-3.5 rounded-2xl bg-red-950/60 border border-red-500/30 text-red-300 text-xs flex items-center gap-2.5">
                  <AlertCircle className="w-4 h-4 shrink-0 text-red-400" />
                  <span>{error}</span>
                </div>
              )}

              {/* AI Vision Analysis Card */}
              {analysis && (
                <div className="p-4 rounded-2xl bg-zinc-900/80 border border-cyan-500/30 space-y-3 shadow-lg">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Sparkles className="w-4 h-4 text-cyan-400" />
                      <span className="text-xs font-mono font-bold text-cyan-300 uppercase">
                        Hasil Analisis Visual AI
                      </span>
                    </div>
                    {analysis.franchise && (
                      <span className="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                        {analysis.franchise}
                      </span>
                    )}
                  </div>

                  <div className="flex flex-wrap gap-1.5">
                    {analysis.visual_style && (
                      <span className="px-2.5 py-1 rounded-lg text-xs font-bold bg-cyan-950/80 text-cyan-200 border border-cyan-500/40">
                        🎨 {analysis.visual_style}
                      </span>
                    )}
                    {analysis.color_mood && (
                      <span className="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-950/80 text-purple-200 border border-purple-500/40">
                        ✨ {analysis.color_mood}
                      </span>
                    )}
                  </div>

                  {analysis.visual_description && (
                    <p className="text-xs text-zinc-300 leading-relaxed italic bg-zinc-950/50 p-2.5 rounded-xl border border-white/5">
                      "{analysis.visual_description}"
                    </p>
                  )}

                  {/* Create Collection Button */}
                  <button
                    type="button"
                    onClick={handleCreateCollectionFromImage}
                    disabled={isCreatingCollection}
                    className="w-full py-2 px-3 rounded-xl bg-zinc-800 hover:bg-zinc-700 border border-purple-500/30 text-purple-300 hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                  >
                    {isCreatingCollection ? (
                      <>
                        <Loader2 className="w-3.5 h-3.5 animate-spin" />
                        <span>Membuat Koleksi AI...</span>
                      </>
                    ) : (
                      <>
                        <Wand2 className="w-3.5 h-3.5 text-amber-400" />
                        <span>Buat Koleksi dari Nuansa Visual Ini</span>
                      </>
                    )}
                  </button>
                </div>
              )}

              {/* Matching Results List */}
              <div className="space-y-2.5">
                <div className="flex items-center justify-between">
                  <h4 className="font-serif font-bold text-sm text-white flex items-center gap-1.5">
                    <Film className="w-4 h-4 text-purple-400" />
                    <span>Film dengan Visual & Tema Serupa ({results.length})</span>
                  </h4>
                </div>

                {results.length === 0 && !isScanning && !analysis && (
                  <div className="py-12 text-center text-zinc-500 space-y-2 border border-dashed border-zinc-800 rounded-2xl">
                    <ImageIcon className="w-8 h-8 mx-auto opacity-30" />
                    <p className="text-xs">Pilih poster dan klik Scan untuk melihat rekomendasi visual</p>
                  </div>
                )}

                {results.length > 0 && (
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-80 overflow-y-auto p-1">
                    {results.map((film) => (
                      <a
                        key={film.id}
                        href={film.url}
                        className="group relative rounded-xl overflow-hidden bg-zinc-900 border border-white/5 hover:border-cyan-500/50 transition-all transform hover:-translate-y-0.5 shadow-md flex flex-col justify-between"
                      >
                        <div className="relative aspect-[2/3] bg-zinc-950 overflow-hidden">
                          {film.poster_url ? (
                            <img
                              src={film.poster_url}
                              alt={film.title}
                              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                              loading="lazy"
                            />
                          ) : (
                            <div className="w-full h-full flex items-center justify-center text-zinc-700">
                              <Film className="w-6 h-6 opacity-40" />
                            </div>
                          )}

                          <div className="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded-md bg-cyan-950/90 text-cyan-300 border border-cyan-500/40 text-[9px] font-mono font-bold backdrop-blur-md">
                            {film.similarity_score}% Match
                          </div>
                        </div>

                        <div className="p-2 space-y-0.5">
                          <h5 className="font-serif font-bold text-xs text-white group-hover:text-cyan-300 transition-colors truncate">
                            {{ film: film.title }.film}
                          </h5>
                          <div className="flex items-center justify-between text-[10px] text-zinc-400">
                            <span>{film.release_year || 'N/A'}</span>
                            <span className="text-amber-400">★ {film.rating || 'N/A'}</span>
                          </div>
                        </div>
                      </a>
                    ))}
                  </div>
                )}
              </div>

            </div>

          </div>

        </div>

      </div>
    </div>
  );
}
