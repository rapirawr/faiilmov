import React, { useEffect, useRef } from 'react';
import * as LucideIcons from 'lucide-react';

/**
 * Dynamic Lucide Icon Helper
 */
export function DynamicIcon({ name, className = 'w-5 h-5', fallback = null }) {
  if (!name) return fallback;
  
  // Convert kebab-case or snake_case to PascalCase (e.g. 'shield-check' -> 'ShieldCheck')
  const pascalName = name
    .replace(/(^\w|-\w|_\w)/g, (clear) => clear.replace(/[-_]/, '').toUpperCase());

  const IconComponent = LucideIcons[pascalName] || LucideIcons[name] || LucideIcons.HelpCircle;
  return <IconComponent className={className} />;
}

/**
 * Reusable Core Modal Component
 */
export default function Modal({
  isOpen = false,
  onClose,
  title,
  subtitle,
  icon,
  iconBgClass,
  size = 'md', // 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full'
  variant = 'default', // 'default' | 'amber' | 'sky' | 'rose' | 'emerald' | 'purple'
  children,
  footer,
  showCloseButton = true,
  closeOnBackdrop = true,
  closeOnEscape = true,
  className = '',
}) {
  const modalRef = useRef(null);

  // Handle ESC key press
  useEffect(() => {
    if (!isOpen || !closeOnEscape) return;

    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        if (onClose) onClose();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, closeOnEscape, onClose]);

  // Lock body scroll when open
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [isOpen]);

  if (!isOpen) return null;

  // Size mapping
  const sizeClasses = {
    xs: 'max-w-xs',
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    full: 'max-w-[95vw] sm:max-w-[90vw] md:max-w-6xl',
  }[size] || 'max-w-md';

  // Variant themes for icon & border accent
  const variantStyles = {
    amber: {
      iconWrapper: 'bg-amber-500/10 border-amber-500/30 text-amber-400',
      headerBorder: 'border-amber-500/20',
      glow: 'shadow-[0_0_50px_-12px_rgba(245,158,11,0.25)]',
    },
    sky: {
      iconWrapper: 'bg-sky-500/10 border-sky-500/30 text-sky-400',
      headerBorder: 'border-sky-500/20',
      glow: 'shadow-[0_0_50px_-12px_rgba(14,165,233,0.25)]',
    },
    rose: {
      iconWrapper: 'bg-rose-500/10 border-rose-500/30 text-rose-400',
      headerBorder: 'border-rose-500/20',
      glow: 'shadow-[0_0_50px_-12px_rgba(244,63,94,0.25)]',
    },
    emerald: {
      iconWrapper: 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
      headerBorder: 'border-emerald-500/20',
      glow: 'shadow-[0_0_50px_-12px_rgba(16,185,129,0.25)]',
    },
    purple: {
      iconWrapper: 'bg-purple-500/10 border-purple-500/30 text-purple-400',
      headerBorder: 'border-purple-500/20',
      glow: 'shadow-[0_0_50px_-12px_rgba(168,85,247,0.25)]',
    },
    default: {
      iconWrapper: 'bg-zinc-800 border-zinc-700 text-zinc-300',
      headerBorder: 'border-white/10',
      glow: 'shadow-2xl',
    },
  }[variant] || {
    iconWrapper: 'bg-zinc-800 border-zinc-700 text-zinc-300',
    headerBorder: 'border-white/10',
    glow: 'shadow-2xl',
  };

  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-black/80 backdrop-blur-md transition-all duration-300 animate-fade-in"
      onClick={(e) => {
        if (closeOnBackdrop && e.target === e.currentTarget) {
          if (onClose) onClose();
        }
      }}
    >
      <div
        ref={modalRef}
        className={`w-full ${sizeClasses} bg-zinc-900/95 border border-zinc-800/90 rounded-3xl p-6 text-left space-y-4 ${variantStyles.glow} relative flex flex-col max-h-[90vh] transition-all transform duration-200 scale-100 ${className}`}
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        {(title || icon) && (
          <div className={`flex items-start justify-between gap-3 border-b pb-3.5 shrink-0 ${variantStyles.headerBorder}`}>
            <div className="flex items-center gap-3 min-w-0">
              {icon && (
                <div
                  className={`w-10 h-10 rounded-2xl border flex items-center justify-center shrink-0 ${
                    iconBgClass || variantStyles.iconWrapper
                  }`}
                >
                  {typeof icon === 'string' ? (
                    <DynamicIcon name={icon} className="w-5 h-5" />
                  ) : (
                    icon
                  )}
                </div>
              )}
              <div className="min-w-0">
                {title && (
                  <h4 className="font-bold text-white text-sm font-['Outfit'] truncate">
                    {title}
                  </h4>
                )}
                {subtitle && (
                  <p className="text-[11px] text-zinc-400 truncate mt-0.5">
                    {subtitle}
                  </p>
                )}
              </div>
            </div>

            {showCloseButton && (
              <button
                type="button"
                onClick={onClose}
                className="text-zinc-500 hover:text-white p-1 rounded-xl hover:bg-white/5 transition-colors cursor-pointer shrink-0"
                title="Tutup Modal (Esc)"
              >
                <LucideIcons.X className="w-5 h-5" />
              </button>
            )}
          </div>
        )}

        {/* Content Body with customized smooth scroll */}
        <div className="flex-1 overflow-y-auto scrollbar-thin pr-1 text-xs text-zinc-300 leading-relaxed space-y-4">
          {children}
        </div>

        {/* Footer */}
        {footer && (
          <div className="pt-3 border-t border-zinc-800/80 flex items-center justify-end gap-2.5 shrink-0">
            {footer}
          </div>
        )}
      </div>
    </div>
  );
}
