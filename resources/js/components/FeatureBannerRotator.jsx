import React, { useState, useEffect } from 'react';
import { 
  Sparkles, ArrowRight, Search, Send, Film, Play, Star, Flame, 
  Tv, Ticket, Heart, Bookmark, Download, Zap, Gift, Compass, 
  MessageSquare, Share2, Bell, Eye, Plus, CheckCircle2, Clapperboard, 
  BadgePercent, Crown, TrendingUp, Video, Music, Shuffle, ExternalLink 
} from 'lucide-react';

const ICON_MAP = {
  'send': Send,
  'arrow-right': ArrowRight,
  'play': Play,
  'film': Film,
  'clapperboard': Clapperboard,
  'sparkles': Sparkles,
  'search': Search,
  'star': Star,
  'flame': Flame,
  'tv': Tv,
  'ticket': Ticket,
  'heart': Heart,
  'bookmark': Bookmark,
  'download': Download,
  'zap': Zap,
  'gift': Gift,
  'compass': Compass,
  'message-square': MessageSquare,
  'share-2': Share2,
  'bell': Bell,
  'eye': Eye,
  'plus': Plus,
  'check-circle-2': CheckCircle2,
  'badge-percent': BadgePercent,
  'crown': Crown,
  'trending-up': TrendingUp,
  'video': Video,
  'music': Music,
  'shuffle': Shuffle,
  'external-link': ExternalLink,
};

export default function FeatureBannerRotator({ banners = [] }) {
  const [activeStep, setActiveStep] = useState(0);
  const [isInputFocused, setIsInputFocused] = useState(false);
  const [isHovered, setIsHovered] = useState(false);
  const [inputValue, setInputValue] = useState('');

  if (!banners || banners.length === 0) return null;

  const totalBanners = banners.length;

  useEffect(() => {
    if (totalBanners <= 1 || isInputFocused || isHovered) return;

    const timer = setInterval(() => {
      setActiveStep((prev) => prev + 1);
    }, 5500);

    return () => clearInterval(timer);
  }, [totalBanners, isInputFocused, isHovered]);

  const getBannerForFace = (faceIndex) => {
    const cycle = Math.floor((activeStep - faceIndex + 3) / 4);
    let bannerIdx = (cycle * 4 + faceIndex) % totalBanners;
    if (bannerIdx < 0) bannerIdx += totalBanners;
    return banners[bannerIdx] || banners[0];
  };

  const getGradientClass = (bgGradient) => {
    switch (bgGradient) {
      case 'emerald_teal':
        return 'from-emerald-950/95 via-zinc-950 to-teal-950/95 border-emerald-500/30 shadow-emerald-950/40';
      case 'sky_indigo':
        return 'from-sky-950/95 via-zinc-950 to-indigo-950/95 border-sky-500/30 shadow-sky-950/40';
      case 'rose_orange':
        return 'from-rose-950/95 via-zinc-950 to-orange-950/95 border-rose-500/30 shadow-rose-950/40';
      case 'cyber_neon':
        return 'from-fuchsia-950/95 via-zinc-950 to-cyan-950/95 border-fuchsia-500/30 shadow-fuchsia-950/40';
      case 'custom':
        return 'border-amber-500/40 shadow-amber-950/30';
      default:
        return 'from-indigo-950/95 via-zinc-950 to-amber-950/95 border-amber-500/30 shadow-amber-950/40';
    }
  };

  const getBackgroundStyle = (banner) => {
    if (banner && banner.bg_gradient === 'custom' && banner.bg_gradient_from && banner.bg_gradient_to) {
      return {
        background: `linear-gradient(135deg, ${banner.bg_gradient_from} 0%, #09090b 50%, ${banner.bg_gradient_to} 100%)`,
      };
    }
    return {};
  };

  const handleAction = (banner) => {
    const query = inputValue.trim();
    if (banner.action_type === 'request_modal') {
      if (typeof window.openFilmRequestModal === 'function') {
        window.openFilmRequestModal({ title: query });
      } else {
        const modalBtn = document.querySelector('[data-open-request-modal]');
        if (modalBtn) modalBtn.click();
      }
    } else if (banner.action_url) {
      const url = banner.action_url + (query ? `?q=${encodeURIComponent(query)}` : '');
      window.location.href = url;
    }
  };

  const activeFaceIdx = activeStep % 4;

  return (
    <div
      className="relative my-3 py-1 group select-none"
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <style>{`
        .react-cube-scene {
          perspective: 1200px;
        }
        .react-cube-box {
          position: relative;
          width: 100%;
          height: 195px;
          transform-style: preserve-3d;
          transform-origin: 50% 50% -97.5px;
          transition: transform 0.85s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .react-cube-face {
          position: absolute;
          inset: 0;
          width: 100%;
          height: 100%;
          backface-visibility: hidden;
          -webkit-backface-visibility: hidden;
          -webkit-font-smoothing: antialiased;
          -moz-osx-font-smoothing: grayscale;
          text-rendering: optimizeLegibility;
        }

        /* Mobile specific crisp rendering */
        @media (max-width: 639px) {
          .react-cube-scene {
            perspective: none !important;
          }
          .react-cube-box {
            height: 195px;
            transform-style: flat !important;
            transform: none !important;
            transition: none !important;
          }
          .react-cube-face {
            transform: translateY(20px) !important;
            opacity: 0 !important;
            transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out !important;
          }
          .react-cube-face-active-mobile {
            transform: translateY(0) !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            z-index: 30 !important;
          }
        }

        /* Desktop 3D Cube faces */
        @media (min-width: 640px) {
          .react-cube-box {
            height: 135px;
            transform-origin: 50% 50% -67.5px;
          }
          .react-cube-face-0 { transform: rotateX(0deg) translateZ(0px); }
          .react-cube-face-1 { transform: rotateX(-90deg) translateZ(67.5px) translateY(67.5px); }
          .react-cube-face-2 { transform: rotateX(-180deg) translateZ(135px); }
          .react-cube-face-3 { transform: rotateX(90deg) translateZ(67.5px) translateY(-67.5px); }
        }
      `}</style>

      {/* 3D Perspective Container */}
      <div className="react-cube-scene relative w-full h-[195px] sm:h-[135px]">
        {/* 3D Rotating Cube Box */}
        <div
          className="react-cube-box"
          style={{ transform: `rotateX(${activeStep * 90}deg)` }}
        >
          {[0, 1, 2, 3].map((faceIdx) => {
            const banner = getBannerForFace(faceIdx);
            const isActiveFace = activeFaceIdx === faceIdx;
            const inputType = banner.input_type || 'text';
            const showInput = inputType !== 'none';

            return (
              <div
                key={faceIdx}
                className={`react-cube-face overflow-hidden rounded-2xl border p-4 sm:p-6 shadow-2xl bg-gradient-to-r transition-all duration-300 ${
                  `react-cube-face-${faceIdx}`
                } ${getGradientClass(banner.bg_gradient)} ${
                  isActiveFace
                    ? 'pointer-events-auto z-30 opacity-100 ring-1 ring-white/10 react-cube-face-active-mobile'
                    : 'pointer-events-none z-0 opacity-0 sm:opacity-80'
                }`}
                style={getBackgroundStyle(banner)}
              >
                {/* Ambient Decorative Mesh Backlight */}
                <div className="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none" />

                <div className="relative z-10 flex flex-col md:flex-row items-center justify-between gap-3 sm:gap-4 h-full">
                  {/* Left Column: Copywriting */}
                  <div className="space-y-1 sm:space-y-1.5 text-center md:text-left max-w-2xl">
                    {banner.badge_text && (
                      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[9px] sm:text-[10px] font-extrabold uppercase tracking-wider shadow-sm">
                        <Sparkles className="w-3 h-3 text-amber-400" />
                        <span>{banner.badge_text}</span>
                      </span>
                    )}

                    <h2 className="font-serif font-bold text-lg sm:text-2xl text-white tracking-tight leading-snug drop-shadow-sm">
                      {banner.title}
                    </h2>

                    <p className="text-zinc-300 text-[11px] sm:text-xs leading-relaxed line-clamp-2 sm:line-clamp-none font-normal">
                      {banner.description}
                    </p>
                  </div>

                  {/* Right Column: Interactive Quick Action Bar */}
                  <div className="w-full md:w-auto shrink-0 flex items-center gap-2 sm:gap-3">
                    {showInput && (
                      <div className="relative flex-1 sm:w-64">
                        <input
                          type={inputType}
                          value={inputValue}
                          onChange={(e) => setInputValue(e.target.value)}
                          onFocus={() => setIsInputFocused(true)}
                          onBlur={() => setIsInputFocused(false)}
                          onKeyDown={(e) => {
                            if (e.key === 'Enter') handleAction(banner);
                          }}
                          placeholder={banner.placeholder_text || 'Cari atau ketik di sini...'}
                          className="w-full bg-zinc-950/90 border border-white/15 rounded-xl pl-3 pr-8 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400/50 transition-all shadow-inner relative z-30"
                        />
                        <Search className="w-3.5 h-3.5 text-zinc-500 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                      </div>
                    )}

                    <button
                      type="button"
                      onClick={() => handleAction(banner)}
                      className="shrink-0 px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs transition-all shadow-lg hover:shadow-amber-500/20 flex items-center justify-center gap-1.5 cursor-pointer active:scale-95 relative z-30 whitespace-nowrap"
                    >
                      <span>{banner.button_text || (banner.action_type === 'request_modal' ? 'Request' : 'Buka')}</span>
                      {(() => {
                        const iconKey = banner.button_icon || (banner.action_type === 'request_modal' ? 'send' : 'arrow-right');
                        const IconComponent = ICON_MAP[iconKey] || (banner.action_type === 'request_modal' ? Send : ArrowRight);
                        return <IconComponent className="w-3.5 h-3.5 text-zinc-950 shrink-0" />;
                      })()}
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
