import React, { useState } from 'react';
import { PieChart as PieChartIcon, Eye, Sparkles } from 'lucide-react';
import { PieChart, Pie, Cell } from 'recharts';

export default function GenreViewsDonutCard({ genreData }) {
  const items = genreData?.items || [];
  const totalViews = genreData?.total_views || 0;
  const topGenre = genreData?.top_genre || (items[0]?.name ?? 'N/A');

  const [activeIndex, setActiveIndex] = useState(null);

  const chartData = items.map((item, idx) => ({
    name: item.name,
    value: item.views || 1,
    percentage: item.percentage,
    color: item.color,
    index: idx,
  }));

  const activeItem = activeIndex !== null ? items[activeIndex] : items[0] || null;

  return (
    <div className="rounded-2xl bg-zinc-900 border border-zinc-800 p-5 shadow-sm space-y-5">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-800 pb-4">
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-xl bg-zinc-800 border border-zinc-700 text-amber-400">
            <PieChartIcon className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight flex items-center gap-2">
              <span>Genre Paling Sering Ditonton</span>
              <span className="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30 uppercase">
                Statistik
              </span>
            </h3>
            <p className="text-xs text-zinc-400">Distribusi proporsi minat genre berdasarkan total penayangan & aktivitas tontonan</p>
          </div>
        </div>

        {/* Top Genre & Total Views Badge */}
        <div className="flex items-center gap-2">
          <div className="px-3 py-1 rounded-lg bg-zinc-950 border border-zinc-800 flex items-center gap-1.5">
            <Sparkles className="w-3.5 h-3.5 text-amber-400" />
            <span className="text-xs font-bold text-amber-300">#1 {topGenre}</span>
          </div>

          <div className="px-3 py-1 rounded-lg bg-zinc-950 border border-zinc-800 flex items-center gap-2">
            <Eye className="w-3.5 h-3.5 text-zinc-400" />
            <span className="text-xs font-mono font-bold text-[#E4E2DD]">{totalViews.toLocaleString()}</span>
            <span className="text-[10px] text-zinc-400">tontonan</span>
          </div>
        </div>
      </div>

      {items.length === 0 ? (
        <div className="p-8 text-center text-zinc-500 text-xs">
          Belum ada data penayangan genre yang tercatat.
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
          {/* Left: Recharts Donut Chart with paddingAngle & cornerRadius */}
          <div className="md:col-span-5 flex flex-col items-center justify-center p-4 rounded-2xl bg-zinc-950/60 border border-zinc-800/80">
            <div className="relative w-[210px] h-[210px] flex items-center justify-center">
              <PieChart width={210} height={210}>
                <Pie
                  data={chartData}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={84}
                  paddingAngle={5}
                  cornerRadius={6}
                  dataKey="value"
                  stroke="none"
                  onMouseEnter={(_, index) => setActiveIndex(index)}
                  onMouseLeave={() => setActiveIndex(null)}
                >
                  {chartData.map((entry, index) => (
                    <Cell
                      key={`cell-${index}`}
                      fill={entry.color}
                      className="cursor-pointer transition-all duration-200 outline-none"
                      style={{
                        filter: activeIndex === index ? `drop-shadow(0 0 8px ${entry.color})` : 'none',
                        opacity: activeIndex === null || activeIndex === index ? 1 : 0.4,
                      }}
                    />
                  ))}
                </Pie>
              </PieChart>

              {/* Inner Center Callout */}
              <div className="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none p-3">
                {activeItem ? (
                  <div className="space-y-0.5 transition-all duration-200">
                    <span 
                      className="text-[11px] font-bold uppercase tracking-wider block font-mono px-2 py-0.5 rounded-full truncate max-w-[110px]"
                      style={{ color: activeItem.color }}
                    >
                      {activeItem.name}
                    </span>
                    <span className="text-2xl font-mono font-black text-white block tracking-tight">
                      {activeItem.percentage}%
                    </span>
                    <span className="text-[10px] font-mono text-zinc-400 flex items-center justify-center gap-1">
                      <Eye className="w-3 h-3 text-zinc-500" />
                      {activeItem.views.toLocaleString()} views
                    </span>
                  </div>
                ) : (
                  <div className="space-y-0.5">
                    <span className="text-[10px] font-bold uppercase text-zinc-400 block font-mono">Total</span>
                    <span className="text-xl font-mono font-black text-white block">{totalViews.toLocaleString()}</span>
                    <span className="text-[10px] text-zinc-500">tontonan</span>
                  </div>
                )}
              </div>
            </div>

            <p className="text-[11px] text-zinc-400 font-mono mt-3 text-center">
              {activeIndex !== null ? 'Arahkan kursor ke segmen lain untuk detail' : 'Arahkan kursor ke grafik atau daftar'}
            </p>
          </div>

          {/* Right: Detailed Ranked List & Progress Bars */}
          <div className="md:col-span-7 space-y-2.5">
            {items.map((item, idx) => {
              const isHovered = activeIndex === idx;
              const isDimmed = activeIndex !== null && !isHovered;

              return (
                <div
                  key={item.id || item.name}
                  onMouseEnter={() => setActiveIndex(idx)}
                  onMouseLeave={() => setActiveIndex(null)}
                  className={`p-2.5 rounded-2xl border transition-all duration-200 cursor-pointer flex flex-col gap-1.5 ${
                    isHovered
                      ? 'bg-zinc-800/90 border-zinc-600 shadow-lg scale-[1.015]'
                      : isDimmed
                      ? 'bg-zinc-950/40 border-zinc-900 opacity-50'
                      : 'bg-zinc-950 border-zinc-800/80 hover:bg-zinc-900/80 hover:border-zinc-700'
                  }`}
                >
                  {/* Genre Label & Metrics Row */}
                  <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2.5 min-w-0">
                      <span
                        className="w-3.5 h-3.5 rounded-full shrink-0 shadow-sm transition-transform duration-200"
                        style={{
                          backgroundColor: item.color,
                          boxShadow: isHovered ? `0 0 10px ${item.color}` : 'none',
                          transform: isHovered ? 'scale(1.2)' : 'scale(1)',
                        }}
                      />
                      <span className="text-xs font-bold text-[#E4E2DD] truncate">
                        {item.name}
                      </span>
                    </div>

                    <div className="flex items-center gap-3 shrink-0 font-mono text-xs">
                      <div className="flex items-center gap-1 text-zinc-400">
                        <Eye className="w-3 h-3 text-zinc-500" />
                        <span>{item.views.toLocaleString()}</span>
                      </div>
                      <span 
                        className="font-black text-xs min-w-[45px] text-right"
                        style={{ color: item.color }}
                      >
                        {item.percentage}%
                      </span>
                    </div>
                  </div>

                  {/* Horizontal Bar Share */}
                  <div className="w-full bg-zinc-900 h-2 rounded-full overflow-hidden p-0.5">
                    <div
                      className="h-full rounded-full transition-all duration-300"
                      style={{
                        width: `${item.percentage}%`,
                        backgroundColor: item.color,
                        boxShadow: isHovered ? `0 0 6px ${item.color}80` : 'none',
                      }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
