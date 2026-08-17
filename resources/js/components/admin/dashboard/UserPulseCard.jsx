import React, { useState } from 'react';
import { 
  Users, 
  UserPlus, 
  Tv, 
  Activity, 
  TrendingUp
} from 'lucide-react';

export default function UserPulseCard({ userData }) {
  const [hoveredDay, setHoveredDay] = useState(null);

  const dau = userData?.dau || 0;
  const signupsToday = userData?.signups_today || 0;
  const activeWatchParties = userData?.active_watch_parties || 0;
  const totalUsers = userData?.total_users || 0;
  const signupTrend = userData?.signup_trend_7d || [];

  const maxSignup = Math.max(...signupTrend.map((d) => d.signups || 0), 1);

  // SVG Sparkline calculation for 7 days
  const svgWidth = 320;
  const svgHeight = 60;
  const padding = 10;
  const effectiveWidth = svgWidth - padding * 2;
  const effectiveHeight = svgHeight - padding * 2;

  const points = signupTrend.map((d, index) => {
    const x = padding + (index / Math.max(1, signupTrend.length - 1)) * effectiveWidth;
    const y = svgHeight - padding - (d.signups / Math.max(1, maxSignup)) * effectiveHeight;
    return { x, y, data: d };
  });

  const pathD = points.length > 0 
    ? `M ${points.map((p) => `${p.x},${p.y}`).join(' L ')}`
    : '';

  const areaD = points.length > 0
    ? `${pathD} L ${points[points.length - 1].x},${svgHeight} L ${points[0].x},${svgHeight} Z`
    : '';

  return (
    <div className="rounded-2xl bg-zinc-900 border border-zinc-800 p-5 shadow-sm space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between gap-3 border-b border-zinc-800 pb-4">
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-xl bg-zinc-800 border border-zinc-700 text-zinc-300">
            <Users className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">Aktivitas & Pertumbuhan Pengguna</h3>
            <p className="text-xs text-zinc-400">DAU hari ini, registrasi baru, & sesi nobar</p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <span className="px-2.5 py-1 rounded-md text-[11px] font-mono font-medium bg-zinc-800 border border-zinc-700 text-zinc-300">
            Total {totalUsers.toLocaleString()} User
          </span>
        </div>
      </div>

      {/* 3 Metric Cards Grid */}
      <div className="grid grid-cols-3 gap-2.5">
        {/* DAU */}
        <div className="p-3 rounded-xl bg-zinc-950 border border-zinc-800 space-y-1">
          <div className="flex items-center justify-between text-zinc-400 text-xs">
            <span>DAU</span>
            <Activity className="w-3.5 h-3.5 text-zinc-500" />
          </div>
          <div className="text-xl sm:text-2xl font-bold font-['Outfit'] text-[#E4E2DD]">
            {dau.toLocaleString()}
          </div>
          <p className="text-[10px] text-zinc-400 font-mono">aktif dlm 24j</p>
        </div>

        {/* Signups Today */}
        <div className="p-3 rounded-xl bg-zinc-950 border border-zinc-800 space-y-1">
          <div className="flex items-center justify-between text-zinc-400 text-xs">
            <span>Signup Baru</span>
            <UserPlus className="w-3.5 h-3.5 text-zinc-500" />
          </div>
          <div className="text-xl sm:text-2xl font-bold font-['Outfit'] text-emerald-400">
            +{signupsToday.toLocaleString()}
          </div>
          <p className="text-[10px] text-zinc-400 font-mono">hari ini</p>
        </div>

        {/* Active Watch Parties */}
        <div className="p-3 rounded-xl bg-zinc-950 border border-zinc-800 space-y-1">
          <div className="flex items-center justify-between text-zinc-400 text-xs">
            <span>Nobar Aktif</span>
            <Tv className="w-3.5 h-3.5 text-zinc-500" />
          </div>
          <div className="text-xl sm:text-2xl font-bold font-['Outfit'] text-[#E4E2DD]">
            {activeWatchParties.toLocaleString()}
          </div>
          <p className="text-[10px] text-zinc-400 font-mono">room streaming</p>
        </div>
      </div>

      {/* 7-Day Signup Sparkline SVG Chart */}
      <div className="p-3.5 rounded-xl bg-zinc-950 border border-zinc-800 space-y-2">
        <div className="flex items-center justify-between text-xs">
          <span className="text-zinc-400 font-medium flex items-center gap-1.5">
            <TrendingUp className="w-3.5 h-3.5 text-zinc-400" />
            Grafik Pertumbuhan Akun (7 Hari)
          </span>
          <span className="text-[10px] text-zinc-400 font-mono">
            {hoveredDay ? `${hoveredDay.label}: +${hoveredDay.signups} signup` : 'Hover titik'}
          </span>
        </div>

        {/* SVG Sparkline */}
        <div className="relative w-full pt-1">
          <svg 
            viewBox={`0 0 ${svgWidth} ${svgHeight}`} 
            className="w-full h-16 overflow-visible"
          >
            <defs>
              <linearGradient id="userSignupGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stopColor="#10b981" stopOpacity="0.2" />
                <stop offset="100%" stopColor="#10b981" stopOpacity="0.0" />
              </linearGradient>
            </defs>

            {/* Gradient Fill Area */}
            {areaD && <path d={areaD} fill="url(#userSignupGradient)" />}

            {/* Line Path */}
            {pathD && (
              <path 
                d={pathD} 
                fill="none" 
                stroke="#10b981" 
                strokeWidth="2" 
                strokeLinecap="round" 
                strokeLinejoin="round" 
              />
            )}

            {/* Points */}
            {points.map((p, idx) => {
              const isHovered = hoveredDay?.date === p.data.date;
              return (
                <g key={p.data.date || idx}>
                  <circle
                    cx={p.x}
                    cy={p.y}
                    r={isHovered ? 4.5 : 3}
                    className={`transition-all ${isHovered ? 'fill-emerald-300 stroke-zinc-900 stroke-2' : 'fill-emerald-500 stroke-zinc-950 stroke-1'}`}
                    onMouseEnter={() => setHoveredDay(p.data)}
                    onMouseLeave={() => setHoveredDay(null)}
                  />
                </g>
              );
            })}
          </svg>

          {/* Date labels */}
          <div className="flex justify-between items-center text-[10px] font-mono text-zinc-400 mt-1 px-1">
            {signupTrend.map((d) => (
              <span key={d.date} className={hoveredDay?.date === d.date ? 'text-emerald-400 font-bold' : ''}>
                {d.short_day}
              </span>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
