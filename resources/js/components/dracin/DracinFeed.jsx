import React, { useState, useEffect, useRef } from 'react';
import DracinSlide from './DracinSlide';
import DracinDrawer from './DracinDrawer';

export default function DracinFeed({
  initialSource,
  initialId,
  hasExplicitId = false,
  initialEp,
  initialFeed,
  initialActiveDetail,
  sourcesList,
  csrfToken,
}) {
  const containerRef = useRef(null);
  const [activeSource, setActiveSource] = useState(initialSource || 'dramabox');
  const [currentDrama, setCurrentDrama] = useState(null);
  const [dramaFeed, setDramaFeed] = useState(initialFeed || []);
  const [totalEpisodes, setTotalEpisodes] = useState(1);
  const initialTargetIndex = Math.max(0, (parseInt(initialEp, 10) || 1) - 1);
  const [activeIndex, setActiveIndex] = useState(initialTargetIndex);
  const [isMuted, setIsMuted] = useState(false);
  const [isDrawerOpen, setIsDrawerOpen] = useState(!hasExplicitId);
  const [loading, setLoading] = useState(false);
  const [showEndModal, setShowEndModal] = useState(false);

  // Initialize Active Drama & Detail
  useEffect(() => {
    const firstFeedItem = initialFeed && initialFeed.length > 0 ? initialFeed[0] : null;
    if (initialActiveDetail) {
      processDramaDetail(initialSource, initialId, initialActiveDetail, firstFeedItem, initialTargetIndex);
    } else if (firstFeedItem) {
      const firstId = String(firstFeedItem.id || firstFeedItem.dramaId || '');
      if (firstId) {
        processDramaDetail(initialSource, firstId, firstFeedItem, firstFeedItem, initialTargetIndex);
        loadDramaDetail(initialSource, firstId, firstFeedItem, initialTargetIndex);
      }
    }
  }, []);

  const processDramaDetail = (source, id, detailData, fallbackItem = null, targetIndex = null) => {
    const rawId = String(id || detailData.id || detailData.dramaId || fallbackItem?.id || fallbackItem?.dramaId || '');
    const title = detailData.title || detailData.name || fallbackItem?.title || fallbackItem?.name || 'Untitled Dracin';
    const poster = detailData.posterImg || detailData.cover || detailData.poster || detailData.horizontalCover || fallbackItem?.cover || fallbackItem?.posterImg || '';

    // Check all potential episode fields in detailData first, then fallbackItem
    let epsRaw = detailData.totalEpisodes ?? detailData.episodes ?? detailData.chapterCount ?? detailData.chapter_count ?? detailData.totalChapters ?? detailData.chapters ?? detailData.total_episodes;
    if ((epsRaw === null || epsRaw === undefined || isNaN(parseInt(epsRaw, 10))) && fallbackItem) {
      epsRaw = fallbackItem.totalEpisodes ?? fallbackItem.episodes ?? fallbackItem.chapterCount ?? fallbackItem.total_episodes;
    }

    let validEps = parseInt(epsRaw, 10);
    if (isNaN(validEps) || validEps < 1) {
      validEps = 50; // Realistic default episode count for short dramas
    }

    setCurrentDrama({
      id: rawId,
      source: source,
      title: title,
      posterUrl: poster,
      synopsis: detailData.synopsis || detailData.intro || fallbackItem?.synopsis || '',
    });
    setTotalEpisodes(validEps);
    setActiveSource(source);

    if (targetIndex !== null && targetIndex >= 0) {
      const clampedIndex = Math.min(targetIndex, validEps - 1);
      setActiveIndex(clampedIndex);
      setTimeout(() => {
        if (containerRef.current) {
          const slideHeight = containerRef.current.clientHeight || window.innerHeight;
          containerRef.current.scrollTop = clampedIndex * slideHeight;
        }
      }, 50);
    }
  };

  const loadDramaDetail = async (source, id, fallbackItem = null, preserveIndex = null) => {
    setLoading(true);
    setShowEndModal(false);
    try {
      const res = await fetch(`/dracin/api/detail/${source}/${id}`);
      if (res.ok) {
        const data = await res.json();
        if (data && !data.error) {
          processDramaDetail(source, id, data, fallbackItem, preserveIndex !== null ? preserveIndex : 0);
          setLoading(false);
          return;
        }
      }
    } catch (err) {
      console.warn('Failed to load drama detail from API, using fallback item if available:', err);
    }

    if (fallbackItem) {
      processDramaDetail(source, id, fallbackItem, fallbackItem, preserveIndex !== null ? preserveIndex : 0);
    }
    setLoading(false);
  };

  const handleSelectDrama = (source, id, dramaItem) => {
    if (source && id) {
      if (dramaItem) {
        processDramaDetail(source, id, dramaItem, dramaItem);
        setActiveIndex(0);
        if (containerRef.current) {
          containerRef.current.scrollTop = 0;
        }
      }
      loadDramaDetail(source, id, dramaItem);
    }
  };

  // Scroll Event listener for Snap Scroll active slide index detection
  const handleScroll = () => {
    const container = containerRef.current;
    if (!container) return;

    const slideHeight = container.clientHeight;
    if (!slideHeight) return;

    const newIndex = Math.round(container.scrollTop / slideHeight);
    if (newIndex !== activeIndex && newIndex >= 0 && newIndex < totalEpisodes) {
      setActiveIndex(newIndex);
      if (newIndex < totalEpisodes - 1) {
        setShowEndModal(false);
      }
    }
  };

  const scrollToSlide = (index) => {
    const container = containerRef.current;
    if (!container) return;

    const slideHeight = container.clientHeight;
    container.scrollTo({
      top: index * slideHeight,
      behavior: 'smooth',
    });
    setActiveIndex(index);
  };

  const handleNextEpisode = () => {
    if (activeIndex < totalEpisodes - 1) {
      scrollToSlide(activeIndex + 1);
    } else {
      setShowEndModal(true);
    }
  };

  const handlePrevEpisode = () => {
    if (activeIndex > 0) {
      scrollToSlide(activeIndex - 1);
    }
  };

  const handleEpisodeEnded = (endedEpIndex) => {
    if (endedEpIndex < totalEpisodes - 1) {
      // Auto-scroll to next episode on video end
      scrollToSlide(endedEpIndex + 1);
    } else {
      // Last episode reached -> show series completed recommendation modal
      setShowEndModal(true);
    }
  };

  const lastRecordedRef = useRef({});
  const handleProgressUpdate = (ep, curr, dur) => {
    if (curr > 5 && currentDrama) {
      const key = `${currentDrama.source}-${currentDrama.id}-${ep}`;
      const now = Date.now();
      if (!lastRecordedRef.current[key] || now - lastRecordedRef.current[key] > 10000) {
        lastRecordedRef.current[key] = now;
        fetch('/dracin/api/watch-progress', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
          },
          body: JSON.stringify({
            source: currentDrama.source,
            id: currentDrama.id,
            episode: ep,
            progress_seconds: Math.floor(curr),
            title: currentDrama.title,
            posterUrl: currentDrama.posterUrl,
            totalEpisodes: totalEpisodes,
          }),
        }).catch(() => {});
      }
    }
  };

  if (loading || !currentDrama) {
    return (
      <div className="w-full max-w-[480px] h-[100dvh] bg-black flex flex-col items-center justify-center space-y-4 text-white border-x border-zinc-900">
        <div className="w-10 h-10 border-2 border-white border-t-transparent rounded-full animate-spin" />
        <span className="text-xs uppercase font-extrabold tracking-widest text-zinc-400">MEMUAT DRACIN...</span>
      </div>
    );
  }

  // Generate array of slide indices
  const episodeIndices = Array.from({ length: totalEpisodes }, (_, i) => i);

  return (
    <div className="relative w-full h-[100dvh] bg-black flex justify-center items-center overflow-hidden">
      
      {/* Centered Mobile/Desktop Container */}
      <div className="relative w-full max-w-[480px] h-[100dvh] bg-black border-x border-zinc-900 shadow-2xl flex flex-col overflow-hidden">
        
        {/* Navigation Bar Header (Unified Top Bar) */}
        <div className="absolute top-0 left-0 right-0 z-30 px-3.5 py-3 pt-3.5 bg-gradient-to-b from-black/90 via-black/50 to-transparent flex items-center justify-between pointer-events-auto">
          {/* Left Side: Back Home */}
          <div className="flex items-center gap-2">
            <a
              href="/dracin"
              className="p-2 rounded-full bg-black/60 backdrop-blur-md border border-white/20 text-zinc-300 hover:text-white transition-colors flex items-center justify-center"
              title="Kembali ke Katalog Dracin"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" />
              </svg>
            </a>
          </div>

          {/* Center: Brand Badge */}
          <div className="hidden xs:flex items-center gap-2">
            <span className="text-[10px] font-extrabold uppercase tracking-widest text-white bg-black/60 backdrop-blur-md border border-white/20 px-2.5 py-1 rounded-full">
              FAIILLMOV FEED
            </span>
          </div>

          {/* Right Side: Sound Mute Toggle & Drama Drawer Menu */}
          <div className="flex items-center gap-2">
            <button
              onClick={() => setIsMuted((prev) => !prev)}
              className="p-2 rounded-full bg-black/60 backdrop-blur-md border border-white/20 text-white hover:bg-black/90 transition-colors flex items-center justify-center"
              title={isMuted ? "Aktifkan Suara" : "Matikan Suara"}
            >
              {isMuted ? (
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                </svg>
              ) : (
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                </svg>
              )}
            </button>

            <button
              onClick={() => setIsDrawerOpen(true)}
              className="p-2 rounded-full bg-black/60 backdrop-blur-md border border-white/20 text-white hover:bg-black/90 transition-colors flex items-center justify-center"
              title="Daftar Drama"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>

        {/* Snap Scroll Vertical Feed Window Container */}
        <div
          ref={containerRef}
          onScroll={handleScroll}
          className="w-full h-full snap-y snap-mandatory overflow-y-scroll no-scrollbar scroll-smooth relative z-10"
        >
          {episodeIndices.map((index) => {
            // WINDOW LOADING: Only render full video slide if within activeIndex ± 1
            const isMounted = Math.abs(index - activeIndex) <= 1;
            const isCurrentActive = index === activeIndex && !isDrawerOpen;

            if (!isMounted) {
              return (
                <div
                  key={index}
                  className="w-full h-[100dvh] snap-start snap-always bg-black flex items-center justify-center"
                >
                  <span className="text-[10px] font-mono uppercase text-zinc-800 tracking-widest">
                    EPISODE {index + 1}
                  </span>
                </div>
              );
            }

            return (
              <DracinSlide
                key={`${currentDrama.id}-ep-${index + 1}`}
                episodeIndex={index}
                totalEpisodes={totalEpisodes}
                source={currentDrama.source}
                dramaId={currentDrama.id}
                dramaTitle={currentDrama.title}
                posterUrl={currentDrama.posterUrl}
                isActive={isCurrentActive}
                isMuted={isMuted}
                onToggleMute={() => setIsMuted((prev) => !prev)}
                onEnded={handleEpisodeEnded}
                onOpenDrawer={() => setIsDrawerOpen(true)}
                onNext={handleNextEpisode}
                onPrev={handlePrevEpisode}
                onProgressUpdate={handleProgressUpdate}
              />
            );
          })}
        </div>

        {/* End of Series Recommendation Overlay Modal (Monochrome) */}
        {showEndModal && (
          <div className="absolute inset-0 z-40 bg-black/90 backdrop-blur-md p-6 flex flex-col items-center justify-center text-center text-white space-y-5 animate-fade-in">
            <div className="w-12 h-12 rounded-full border border-white flex items-center justify-center text-white">
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            
            <div className="space-y-1">
              <span className="text-[10px] font-extrabold uppercase tracking-widest text-zinc-400">BINGE WATCH SELESAI</span>
              <h3 className="text-lg font-extrabold uppercase tracking-wide leading-tight">
                {currentDrama.title}
              </h3>
              <p className="text-xs text-zinc-400 max-w-xs">
                Kamu telah menyelesaikan seluruh {totalEpisodes} episode drama ini!
              </p>
            </div>

            <div className="flex flex-col w-full space-y-2 max-w-xs pt-2">
              <button
                onClick={() => {
                  setShowEndModal(false);
                  scrollToSlide(0);
                }}
                className="w-full py-3 bg-white text-black text-xs font-extrabold uppercase tracking-wider rounded-xl hover:bg-zinc-200 transition-colors flex items-center justify-center gap-2 cursor-pointer"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Ulangi Dari Episode 1</span>
              </button>

              <button
                onClick={() => {
                  setShowEndModal(false);
                  setIsDrawerOpen(true);
                }}
                className="w-full py-3 bg-zinc-900 text-white text-xs font-extrabold uppercase tracking-wider rounded-xl border border-zinc-800 hover:bg-zinc-800 transition-colors flex items-center justify-center gap-2 cursor-pointer"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <span>Pilih Drama Lain</span>
              </button>
            </div>
          </div>
        )}

        {/* Monochrome Drawer Component */}
        <DracinDrawer
          isOpen={isDrawerOpen}
          onClose={() => setIsDrawerOpen(false)}
          currentSource={activeSource}
          sourcesList={sourcesList || {}}
          onSelectDrama={handleSelectDrama}
          csrfToken={csrfToken}
        />
      </div>
    </div>
  );
}
