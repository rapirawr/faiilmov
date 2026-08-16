import React, { useRef, useState, useEffect } from 'react';

export default function DracinSlide({
  episodeIndex,
  totalEpisodes,
  source,
  dramaId,
  dramaTitle,
  posterUrl,
  isActive,
  isMuted,
  onToggleMute,
  onEnded,
  onOpenDrawer,
  onNext,
  onPrev,
  onProgressUpdate,
}) {
  const videoRef = useRef(null);
  const hlsRef = useRef(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [progress, setProgress] = useState(0);
  const [currentTime, setCurrentTime] = useState('00:00');
  const [duration, setDuration] = useState('00:00');
  const [isWatchlisted, setIsWatchlisted] = useState(false);
  const [showPlayPulse, setShowPlayPulse] = useState(null); // 'play' | 'pause' | null
  const [copied, setCopied] = useState(false);
  const [loadingStream, setLoadingStream] = useState(true);
  const [hasError, setHasError] = useState(false);

  const epNumber = episodeIndex + 1;
  const streamUrl = `/anichin/hls?source=${encodeURIComponent(source || 'dramabox')}&id=${encodeURIComponent(dramaId)}&ep=${epNumber}`;

  // Initialize & Manage HLS Video Player based on isActive
  useEffect(() => {
    const video = videoRef.current;
    if (!video) return;

    if (!isActive) {
      if (video) {
        video.pause();
      }
      if (hlsRef.current) {
        hlsRef.current.destroy();
        hlsRef.current = null;
      }
      setIsPlaying(false);
      return;
    }

    setLoadingStream(true);
    setHasError(false);

    // HLS.js supported browsers (Chrome, Edge, Firefox, etc.)
    if (window.Hls && window.Hls.isSupported()) {
      if (hlsRef.current) {
        hlsRef.current.destroy();
      }

      const hls = new window.Hls({
        enableWorker: true,
        lowLatencyMode: true,
        manifestLoadingTimeOut: 15000,
        manifestLoadingMaxRetry: 3,
        levelLoadingTimeOut: 15000,
        fragLoadingTimeOut: 20000,
      });

      hlsRef.current = hls;
      hls.loadSource(streamUrl);
      hls.attachMedia(video);

      hls.on(window.Hls.Events.MANIFEST_PARSED, () => {
        setLoadingStream(false);
        video.muted = isMuted;
        const playPromise = video.play();
        if (playPromise !== undefined) {
          playPromise
            .then(() => {
              setIsPlaying(true);
              setLoadingStream(false);
            })
            .catch(() => {
              video.muted = true;
              video.play()
                .then(() => {
                  setIsPlaying(true);
                  setLoadingStream(false);
                })
                .catch(() => setIsPlaying(false));
            });
        }
      });

      hls.on(window.Hls.Events.FRAG_PARSING_INIT_SEGMENT, () => {
        setLoadingStream(false);
      });

      hls.on(window.Hls.Events.FRAG_BUFFERED, () => {
        setLoadingStream(false);
      });

      hls.on(window.Hls.Events.ERROR, (event, data) => {
        if (data.fatal) {
          switch (data.type) {
            case window.Hls.ErrorTypes.NETWORK_ERROR:
              console.warn('Fatal HLS network error, attempting recovery...', data);
              hls.startLoad();
              break;
            case window.Hls.ErrorTypes.MEDIA_ERROR:
              console.warn('Fatal HLS media error, attempting recovery...', data);
              hls.recoverMediaError();
              break;
            default:
              console.error('Fatal unrecoverable HLS error:', data);
              hls.destroy();
              setHasError(true);
              setLoadingStream(false);
              break;
          }
        }
      });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
      // Native HLS fallback (iOS Safari)
      video.src = streamUrl;
      video.muted = isMuted;
      const playPromise = video.play();
      if (playPromise !== undefined) {
        playPromise
          .then(() => {
            setIsPlaying(true);
            setLoadingStream(false);
          })
          .catch((err) => {
            console.warn('Native HLS play blocked or failed:', err);
            video.muted = true;
            video.play().then(() => setIsPlaying(true)).catch(() => setIsPlaying(false));
            setLoadingStream(false);
          });
      }
    } else {
      // Fallback standard video source
      video.src = streamUrl;
      video.muted = isMuted;
      video.play().then(() => setIsPlaying(true)).catch(() => setIsPlaying(false));
      setLoadingStream(false);
    }

    return () => {
      if (hlsRef.current) {
        hlsRef.current.destroy();
        hlsRef.current = null;
      }
    };
  }, [isActive, source, dramaId, epNumber]);

  // Sync Audio Mute State
  useEffect(() => {
    if (videoRef.current) {
      videoRef.current.muted = isMuted;
    }
  }, [isMuted]);

  // Format Seconds to MM:SS
  const formatTime = (secs) => {
    if (!secs || isNaN(secs)) return '00:00';
    const m = Math.floor(secs / 60);
    const s = Math.floor(secs % 60);
    return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
  };

  const handleTimeUpdate = () => {
    const video = videoRef.current;
    if (!video || !video.duration) return;

    if (loadingStream) {
      setLoadingStream(false);
    }

    const curr = video.currentTime;
    const dur = video.duration;
    const pct = (curr / dur) * 100;

    setProgress(pct);
    setCurrentTime(formatTime(curr));
    setDuration(formatTime(dur));

    if (onProgressUpdate) {
      onProgressUpdate(epNumber, curr, dur);
    }
  };

  const handleVideoEnded = () => {
    setIsPlaying(false);
    if (onEnded) {
      onEnded(episodeIndex);
    }
  };

  const togglePlay = () => {
    const video = videoRef.current;
    if (!video) return;

    if (video.paused) {
      video.play().then(() => {
        setIsPlaying(true);
        triggerPulse('play');
      }).catch(err => console.error(err));
    } else {
      video.pause();
      setIsPlaying(false);
      triggerPulse('pause');
    }
  };

  const triggerPulse = (type) => {
    setShowPlayPulse(type);
    setTimeout(() => setShowPlayPulse(null), 700);
  };

  const handleContainerClick = () => {
    setShowControls(prev => !prev);
  };

  const handleProgressBarClick = (e) => {
    e.stopPropagation();
    const video = videoRef.current;
    if (!video || !video.duration) return;

    const rect = e.currentTarget.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const width = rect.width;
    const seekTime = (clickX / width) * video.duration;

    video.currentTime = seekTime;
  };

  const handleShare = (e) => {
    e.stopPropagation();
    const shareUrl = `${window.location.origin}/dracin/${source}/${dramaId}?ep=${epNumber}`;
    if (navigator.clipboard) {
      navigator.clipboard.writeText(shareUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  return (
    <div
      className="relative w-full h-[100dvh] snap-start snap-always flex items-center justify-center bg-black overflow-hidden select-none"
      onClick={handleContainerClick}
    >
      {/* Background Poster Image Fallback */}
      {posterUrl && (
        <img
          src={posterUrl}
          alt={dramaTitle}
          className="absolute inset-0 w-full h-full object-cover filter blur-lg opacity-30 scale-110"
        />
      )}

      {/* Main Video Component */}
      <video
        ref={videoRef}
        playsInline
        webkit-playsinline="true"
        onTimeUpdate={handleTimeUpdate}
        onEnded={handleVideoEnded}
        onPlay={() => setIsPlaying(true)}
        onPlaying={() => {
          setIsPlaying(true);
          setLoadingStream(false);
        }}
        onCanPlay={() => setLoadingStream(false)}
        onLoadedData={() => setLoadingStream(false)}
        onWaiting={() => setLoadingStream(true)}
        onPause={() => setIsPlaying(false)}
        onError={() => {
          setHasError(true);
          setLoadingStream(false);
        }}
        className="w-full h-full object-contain relative z-0"
      />

      {/* Loading Stream Spinner (Monochrome) */}
      {loadingStream && (
        <div className="absolute inset-0 z-10 flex flex-col items-center justify-center bg-black/60 backdrop-blur-sm space-y-3">
          <div className="w-10 h-10 border-2 border-white border-t-transparent rounded-full animate-spin" />
          <span className="text-xs uppercase font-extrabold tracking-widest text-zinc-300">
            MEMUAT EPISODE {epNumber}...
          </span>
        </div>
      )}

      {/* Error Fallback */}
      {hasError && (
        <div className="absolute inset-0 z-10 flex flex-col items-center justify-center bg-black/90 p-6 text-center space-y-3">
          <svg className="w-10 h-10 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <div className="text-sm font-bold text-white uppercase tracking-wider">Video Gagal Memuat</div>
          <p className="text-xs text-zinc-400 max-w-xs">Stream episode ini tidak dapat diputar. Coba skip ke episode berikutnya.</p>
          <button
            onClick={(e) => {
              e.stopPropagation();
              onNext && onNext();
            }}
            className="px-4 py-2 bg-white text-black text-xs font-bold uppercase rounded-full tracking-wider hover:bg-zinc-200 transition-colors flex items-center gap-1.5 cursor-pointer"
          >
            <span>Lanjut Episode {epNumber + 1}</span>
            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </button>
        </div>
      )}

      {/* Center Screen Play / Pause Animation Pulse */}
      {showPlayPulse && (
        <div className="absolute inset-0 z-20 pointer-events-none flex items-center justify-center">
          <div className="w-16 h-16 rounded-full bg-black/70 backdrop-blur-md border border-white/20 flex items-center justify-center text-white animate-ping">
            {showPlayPulse === 'play' ? (
              <svg className="w-8 h-8 fill-current ml-1" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
            ) : (
              <svg className="w-8 h-8 fill-current" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" /></svg>
            )}
          </div>
        </div>
      )}

      {/* Right Side Action Column (Monochrome Icons) */}
      <div className="absolute right-3 bottom-24 z-20 flex flex-col items-center space-y-5 pointer-events-auto">

        {/* Watchlist / Bookmark Button */}
        <button
          onClick={(e) => {
            e.stopPropagation();
            setIsWatchlisted((prev) => !prev);
          }}
          className="group flex flex-col items-center text-center space-y-1 focus:outline-none"
        >
          <div className={`p-3 rounded-full bg-black/60 backdrop-blur-md border border-white/20 transition-all ${
            isWatchlisted ? 'bg-white text-black border-white scale-110' : 'text-white hover:bg-black/90'
          }`}>
            <svg className={`w-5 h-5 ${isWatchlisted ? 'fill-current' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
          </div>
          <span className="text-[10px] font-bold text-zinc-300 tracking-wider">SAVED</span>
        </button>


        {/* Share Link Button */}
        <button
          onClick={handleShare}
          className="group flex flex-col items-center text-center space-y-1 focus:outline-none"
        >
          <div className="p-3 rounded-full bg-black/60 backdrop-blur-md border border-white/20 text-white hover:bg-black/90 transition-all">
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 100-2.684 3 3 0 000 2.684zm0 9a3 3 0 100-2.684 3 3 0 000 2.684z" />
            </svg>
          </div>
          <span className="text-[10px] font-bold text-zinc-300 tracking-wider">
            {copied ? 'COPIED!' : 'SHARE'}
          </span>
        </button>

        {/* Manual Skip Next Button */}
        {epNumber < totalEpisodes && (
          <button
            onClick={(e) => {
              e.stopPropagation();
              onNext && onNext();
            }}
            className="p-3 rounded-full bg-white text-black border border-white hover:bg-zinc-200 transition-all shadow-xl"
            title="Next Episode"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        )}
      </div>

      {/* Bottom Info & Progress Bar Overlay (Strict Monochrome) */}
      <div className="absolute bottom-0 left-0 right-0 z-20 p-5 pt-12 bg-gradient-to-t from-black via-black/60 to-transparent flex flex-col space-y-3 pointer-events-auto">
        
        {/* Title & Episode Badge */}
        <div className="flex flex-col space-y-1 max-w-[80%]">
          <div className="flex items-center gap-2">
            <span className="px-2 py-0.5 bg-white text-black text-[11px] font-extrabold uppercase tracking-widest rounded border border-white">
              EP {epNumber} / {totalEpisodes}
            </span>
            <span className="text-[10px] uppercase font-bold text-zinc-400 tracking-wider">
              {source}
            </span>
          </div>

          <h2 className="text-base font-extrabold text-white leading-tight uppercase tracking-wide line-clamp-2">
            {dramaTitle}
          </h2>
        </div>

        {/* Thin Monochrome Custom Progress Bar */}
        <div className="w-full flex items-center gap-2">
          <span className="text-[10px] font-mono text-zinc-400">{currentTime}</span>
          <div
            onClick={handleProgressBarClick}
            className="flex-1 h-1.5 bg-zinc-800 rounded-full cursor-pointer overflow-hidden relative group"
          >
            <div
              className="h-full bg-white transition-all duration-100 relative"
              style={{ width: `${progress}%` }}
            />
          </div>
          <span className="text-[10px] font-mono text-zinc-400">{duration}</span>
        </div>
      </div>
    </div>
  );
}
