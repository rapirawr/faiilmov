import React from 'react';
import { Spoiler } from 'spoiled';

/**
 * Reusable Telegram-style Spoiler component
 * Reveal logic is set to "click" per user requirements.
 */
export default function SpoilerText({ children, className = '', density = 0.16, fps = 24, theme = 'dark', ...props }) {
  return (
    <div className="inline-block max-w-full">
      <Spoiler
        revealOn="click"
        theme={theme}
        density={density}
        fps={fps}
        className={`cursor-pointer inline-block max-w-full rounded-md transition-all break-words whitespace-pre-wrap px-1 py-0.5 ${className}`}
        {...props}
      >
        {children}
      </Spoiler>
    </div>
  );
}

export { Spoiler };
