import React, { useState, useEffect } from 'react';
import { Film, Tv, Smartphone } from 'lucide-react';

export default function FilmRequestModal({ isOpen: propIsOpen = false, onClose: propOnClose, initialTitle = '', csrfToken = '' }) {
  const [isOpen, setIsOpen] = useState(propIsOpen);
  const [title, setTitle] = useState(initialTitle);
  const [type, setType] = useState('movie');
  const [year, setYear] = useState('');
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    setIsOpen(propIsOpen);
  }, [propIsOpen]);

  useEffect(() => {
    if (initialTitle) {
      setTitle(initialTitle);
    }
  }, [initialTitle]);

  // Listen for global trigger event window.openFilmRequestModal(data)
  useEffect(() => {
    const handleGlobalOpen = (e) => {
      const detail = e?.detail || e || {};
      if (detail.title) setTitle(detail.title);
      if (detail.type) setType(detail.type);
      if (detail.year) setYear(detail.year);
      setSuccessMessage('');
      setErrorMessage('');
      setIsOpen(true);
    };

    window.addEventListener('open-film-request-modal', handleGlobalOpen);
    window.openFilmRequestModal = (data = {}) => {
      window.dispatchEvent(new CustomEvent('open-film-request-modal', { detail: data }));
    };

    return () => {
      window.removeEventListener('open-film-request-modal', handleGlobalOpen);
      delete window.openFilmRequestModal;
    };
  }, []);

  const handleClose = () => {
    setIsOpen(false);
    setErrorMessage('');
    setSuccessMessage('');
    if (propOnClose) propOnClose();
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!title.trim()) {
      setErrorMessage('Judul film tidak boleh kosong.');
      return;
    }

    setLoading(true);
    setErrorMessage('');
    setSuccessMessage('');

    try {
      const token = csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      const response = await fetch('/film-requests', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({
          title: title.trim(),
          type: type,
          year: year ? parseInt(year, 10) : null,
        }),
      });

      const data = await response.json();

      if (response.status === 429) {
        setErrorMessage('Batas request harian tercapai (Maksimal 5 request per hari). Coba lagi besok.');
        return;
      }

      if (response.status === 401) {
        setErrorMessage('Silakan login terlebih dahulu untuk melakukan request film.');
        setTimeout(() => {
          window.location.href = '/login';
        }, 1500);
        return;
      }

      if (!response.ok || data.status === 'error') {
        setErrorMessage(data.message || 'Gagal mengirim request film. Pastikan input sudah benar.');
        return;
      }

      setSuccessMessage(data.message || 'Request film kamu berhasil dikirim!');
      setTitle('');
      setYear('');
      setTimeout(() => {
        handleClose();
      }, 2000);
    } catch (err) {
      console.error('Request Film Error:', err);
      setErrorMessage('Terjadi kesalahan koneksi. Silakan coba lagi.');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-md p-4 animate-fade-in">
      <div className="w-full max-w-lg bg-zinc-900 border border-white/10 rounded-3xl p-6 sm:p-8 space-y-6 shadow-2xl relative">
        
        {/* Header */}
        <div className="flex items-center justify-between border-b border-white/10 pb-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
              </svg>
            </div>
            <div>
              <h3 className="font-serif text-lg font-bold text-white">Request Film / Series</h3>
              <p className="text-xs text-zinc-400">Minta judul film yang kamu inginkan untuk ditambahkan ke Faiilmov.</p>
            </div>
          </div>
          <button
            onClick={handleClose}
            className="text-zinc-500 hover:text-white p-1 rounded-xl hover:bg-white/5 transition-colors cursor-pointer"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* Alert Error / Success */}
        {errorMessage && (
          <div className="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs font-semibold flex items-center gap-2">
            <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{errorMessage}</span>
          </div>
        )}

        {successMessage && (
          <div className="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs font-semibold flex items-center gap-2">
            <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{successMessage}</span>
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Title */}
          <div>
            <label className="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-2">
              Judul Film / Series <span className="text-amber-400">*</span>
            </label>
            <input
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="Contoh: Spider-Man: No Way Home, Avengers, dll."
              required
              className="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition-colors"
            />
          </div>

          {/* Type Selection */}
          <div>
            <label className="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-2">
              Kategori Tipe <span className="text-amber-400">*</span>
            </label>
            <div className="grid grid-cols-3 gap-2">
              <button
                type="button"
                onClick={() => setType('movie')}
                className={`py-2.5 px-3 rounded-2xl text-xs font-bold transition-all border cursor-pointer flex items-center justify-center gap-1.5 ${
                  type === 'movie'
                    ? 'bg-blue-500/20 text-blue-300 border-blue-500/40 shadow-md'
                    : 'bg-zinc-950 text-zinc-400 border-white/10 hover:text-white'
                }`}
              >
                <Film className="w-3.5 h-3.5" />
                <span>Movie</span>
              </button>
              <button
                type="button"
                onClick={() => setType('tv')}
                className={`py-2.5 px-3 rounded-2xl text-xs font-bold transition-all border cursor-pointer flex items-center justify-center gap-1.5 ${
                  type === 'tv'
                    ? 'bg-purple-500/20 text-purple-300 border-purple-500/40 shadow-md'
                    : 'bg-zinc-950 text-zinc-400 border-white/10 hover:text-white'
                }`}
              >
                <Tv className="w-3.5 h-3.5" />
                <span>Series / TV</span>
              </button>
              <button
                type="button"
                onClick={() => setType('dracin')}
                className={`py-2.5 px-3 rounded-2xl text-xs font-bold transition-all border cursor-pointer flex items-center justify-center gap-1.5 ${
                  type === 'dracin'
                    ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 shadow-md'
                    : 'bg-zinc-950 text-zinc-400 border-white/10 hover:text-white'
                }`}
              >
                <Smartphone className="w-3.5 h-3.5" />
                <span>Dracin</span>
              </button>
            </div>
          </div>

          {/* Year (Optional) */}
          <div>
            <label className="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-2">
              Tahun Rilis <span className="text-zinc-500 font-normal">(Opsional)</span>
            </label>
            <input
              type="number"
              value={year}
              onChange={(e) => setYear(e.target.value)}
              placeholder="Contoh: 2026"
              min="1900"
              max="2099"
              className="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition-colors"
            />
          </div>

          {/* Action Buttons */}
          <div className="flex items-center justify-end gap-3 pt-4 border-t border-white/10">
            <button
              type="button"
              onClick={handleClose}
              className="px-5 py-2.5 rounded-2xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-bold transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-6 py-2.5 rounded-2xl bg-amber-500 hover:bg-amber-400 text-black text-xs font-bold transition-all shadow-md flex items-center gap-2 disabled:opacity-50 cursor-pointer"
            >
              {loading ? (
                <>
                  <svg className="w-4 h-4 animate-spin text-black" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                  </svg>
                  <span>Mengirim...</span>
                </>
              ) : (
                <span>Kirim Request</span>
              )}
            </button>
          </div>
        </form>

      </div>
    </div>
  );
}
