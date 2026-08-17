import React, { useState } from 'react';
import { 
  Activity, 
  Server, 
  Layers, 
  Cpu, 
  Music, 
  UserCircle,
  ChevronDown,
  CheckCircle2,
  AlertTriangle,
  AlertCircle,
  Zap,
  Radio,
  Loader2
} from 'lucide-react';

export default function ApiStatusCard({ systemHealth, isRefreshing, csrfToken, onRefresh }) {
  const [expandedService, setExpandedService] = useState('moviebox');
  const [isPinging, setIsPinging] = useState(false);
  const [pingingKey, setPingingKey] = useState(null);
  const [pingFeedback, setPingFeedback] = useState(null);

  const services = systemHealth?.services || [];
  const overallStatus = systemHealth?.overall_status || 'up';
  const downCount = systemHealth?.down_count || 0;
  const degradedCount = systemHealth?.degraded_count || 0;

  // Group services by service name
  const grouped = services.reduce((acc, item) => {
    if (!acc[item.service]) {
      acc[item.service] = [];
    }
    acc[item.service].push(item);
    return acc;
  }, {});

  const serviceMeta = {
    moviebox: {
      name: 'MovieBox Stream Engine',
      icon: Server,
      desc: '7-Host failover pool (video & subtitle)',
    },
    anichin: {
      name: 'Anichin / Dracin API',
      icon: Layers,
      desc: 'Short drama & anime feed aggregator',
    },
    nvidia: {
      name: 'NVIDIA AI Inference',
      icon: Cpu,
      desc: 'Llama-3.1 8B Instruct & NV-Embed-V2',
    },
    itunes: {
      name: 'Apple iTunes Music API',
      icon: Music,
      desc: 'OST & soundtrack preview metadata',
    },
    dicebear: {
      name: 'Dicebear Avatars',
      icon: UserCircle,
      desc: 'Dynamic SVG user avatar generator',
    },
  };

  const handlePingAll = async () => {
    if (isPinging) return;
    setIsPinging(true);
    setPingFeedback(null);

    try {
      const res = await fetch('/admin/api/dashboard/ping', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: JSON.stringify({}),
      });

      if (res.ok) {
        const data = await res.json();
        const r = data.result || {};
        setPingFeedback({
          type: 'success',
          message: `Ping selesai: ${r.up_count || 0}/${r.total_tested || 0} host aktif (rata-rata ${r.avg_latency_ms || 0}ms)`,
        });
        if (onRefresh) onRefresh();
      } else {
        setPingFeedback({
          type: 'error',
          message: `Gagal menjalankan tes ping (HTTP ${res.status}).`,
        });
      }
    } catch (e) {
      setPingFeedback({
        type: 'error',
        message: `Kendala jaringan saat tes ping: ${e.message}`,
      });
    } finally {
      setIsPinging(false);
      setTimeout(() => setPingFeedback(null), 4000);
    }
  };

  const handlePingSingle = async (service, host = null) => {
    const key = host || service;
    if (isPinging || pingingKey) return;
    setPingingKey(key);

    try {
      const res = await fetch('/admin/api/dashboard/ping', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: JSON.stringify({ service, host }),
      });

      if (res.ok) {
        const data = await res.json();
        const r = data.result || {};
        const isUp = r.status === 'up';
        setPingFeedback({
          type: isUp ? 'success' : 'error',
          message: `Ping ${service}${host ? ` (${host})` : ''}: ${isUp ? 'ONLINE' : 'GAGAL'} (${r.latency_ms}ms)`,
        });
        if (onRefresh) onRefresh();
      }
    } catch (e) {
      setPingFeedback({
        type: 'error',
        message: `Ping gagal: ${e.message}`,
      });
    } finally {
      setPingingKey(null);
      setTimeout(() => setPingFeedback(null), 3500);
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case 'up':
        return {
          label: 'UP',
          bg: 'bg-zinc-800 text-emerald-400 border-zinc-700',
          dot: 'bg-emerald-500',
        };
      case 'degraded':
        return {
          label: 'DEGRADED',
          bg: 'bg-zinc-800 text-amber-300 border-zinc-700',
          dot: 'bg-amber-500',
        };
      case 'down':
      default:
        return {
          label: 'DOWN',
          bg: 'bg-zinc-800 text-rose-400 border-zinc-700',
          dot: 'bg-rose-500',
        };
    }
  };

  const getLatencyColor = (ms) => {
    if (!ms && ms !== 0) return 'text-zinc-500';
    if (ms < 400) return 'text-emerald-400';
    if (ms < 1200) return 'text-amber-400';
    return 'text-rose-400';
  };

  return (
    <div className="rounded-2xl bg-zinc-900 border border-zinc-800 p-5 shadow-sm space-y-4">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-800 pb-4">
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-xl bg-zinc-800 border border-zinc-700 text-zinc-300 shrink-0">
            <Activity className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">Kesehatan API & Services</h3>
            <p className="text-xs text-zinc-400">Monitoring latency & failover host eksternal</p>
          </div>
        </div>

        <div className="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          {/* Active Ping Button */}
          <button
            type="button"
            onClick={handlePingAll}
            disabled={isPinging || pingingKey}
            className="px-2.5 py-1.5 rounded-xl bg-zinc-800 hover:bg-zinc-750 active:scale-95 text-[#E4E2DD] border border-zinc-700 text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50 shadow-sm"
            title="Tes Ping ke seluruh 5 API & 7 Failover Host"
          >
            {isPinging ? (
              <>
                <Loader2 className="w-3.5 h-3.5 animate-spin text-amber-400" />
                <span className="text-[11px] font-mono">Pinging...</span>
              </>
            ) : (
              <>
                <Zap className="w-3.5 h-3.5 text-amber-400" />
                <span className="text-[11px]">Tes Ping Semua</span>
              </>
            )}
          </button>

          {downCount > 0 ? (
            <span className="px-2.5 py-1 rounded-md text-[11px] font-mono font-medium bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center gap-1.5">
              <AlertCircle className="w-3.5 h-3.5 text-rose-400" />
              {downCount} Host Down
            </span>
          ) : degradedCount > 0 ? (
            <span className="px-2.5 py-1 rounded-md text-[11px] font-mono font-medium bg-amber-500/10 border border-amber-500/30 text-amber-300 flex items-center gap-1.5">
              <AlertTriangle className="w-3.5 h-3.5 text-amber-400" />
              {degradedCount} Degraded
            </span>
          ) : (
            <span className="px-2.5 py-1 rounded-md text-[11px] font-mono font-medium bg-zinc-800 border border-zinc-700 text-emerald-400 flex items-center gap-1.5">
              <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" />
              Normal
            </span>
          )}
        </div>
      </div>

      {/* Ping Feedback Banner */}
      {pingFeedback && (
        <div className={`p-2.5 rounded-xl border text-xs font-medium flex items-center gap-2 transition-all ${pingFeedback.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300' : 'bg-rose-500/10 border-rose-500/30 text-rose-300'}`}>
          {pingFeedback.type === 'success' ? (
            <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400 shrink-0" />
          ) : (
            <AlertCircle className="w-3.5 h-3.5 text-rose-400 shrink-0" />
          )}
          <span className="truncate">{pingFeedback.message}</span>
        </div>
      )}

      {/* Services List / Accordion */}
      <div className="space-y-2.5">
        {Object.entries(grouped).map(([serviceKey, hostList]) => {
          const meta = serviceMeta[serviceKey] || {
            name: serviceKey.toUpperCase(),
            icon: Server,
            desc: 'External microservice',
          };
          const IconComp = meta.icon;

          const hasDown = hostList.some((h) => h.current_status === 'down');
          const hasDegraded = hostList.some((h) => h.current_status === 'degraded');
          const groupStatus = hasDown ? 'down' : hasDegraded ? 'degraded' : 'up';
          const badge = getStatusBadge(groupStatus);
          const isExpanded = expandedService === serviceKey || Object.keys(grouped).length <= 2;

          const validLatencies = hostList.map((h) => h.avg_latency_ms).filter((l) => l !== null);
          const groupAvgLatency = validLatencies.length ? Math.round(validLatencies.reduce((a, b) => a + b, 0) / validLatencies.length) : null;
          const groupAvgUptime = (hostList.reduce((acc, h) => acc + (h.uptime_24h || 100), 0) / hostList.length).toFixed(1);

          return (
            <div key={serviceKey} className="rounded-xl bg-zinc-950 border border-zinc-800 overflow-hidden">
              {/* Group Summary Row */}
              <div 
                onClick={() => setExpandedService(isExpanded ? null : serviceKey)}
                className="p-3 flex items-center justify-between cursor-pointer hover:bg-zinc-900/50 transition-colors select-none"
              >
                <div className="flex items-center gap-2.5">
                  <div className="w-7 h-7 rounded-lg bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 shrink-0">
                    <IconComp className="w-3.5 h-3.5" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-xs font-bold text-[#E4E2DD] font-['Outfit']">{meta.name}</span>
                      {hostList.length > 1 && (
                        <span className="px-1.5 py-0.2 rounded text-[10px] font-mono text-zinc-400 bg-zinc-900">
                          {hostList.length} hosts
                        </span>
                      )}
                    </div>
                    <p className="text-[11px] text-zinc-400 truncate max-w-[170px] sm:max-w-xs">{meta.desc}</p>
                  </div>
                </div>

                <div className="flex items-center gap-2 sm:gap-3">
                  <div className="hidden sm:flex flex-col items-end text-right font-mono">
                    <span className={`text-xs font-bold ${getLatencyColor(groupAvgLatency)}`}>
                      {groupAvgLatency !== null ? `${groupAvgLatency}ms` : '—'}
                    </span>
                    <span className="text-[10px] text-zinc-400">{groupAvgUptime}% uptime</span>
                  </div>

                  {/* Single service ping trigger */}
                  <button
                    type="button"
                    onClick={(e) => {
                      e.stopPropagation();
                      handlePingSingle(serviceKey);
                    }}
                    disabled={isPinging || pingingKey === serviceKey}
                    className="p-1.5 rounded-lg bg-zinc-900 hover:bg-zinc-800 text-zinc-400 hover:text-amber-400 border border-zinc-800 transition-colors cursor-pointer disabled:opacity-50"
                    title={`Tes ping ${meta.name}`}
                  >
                    {pingingKey === serviceKey ? (
                      <Loader2 className="w-3 h-3 animate-spin text-amber-400" />
                    ) : (
                      <Zap className="w-3 h-3" />
                    )}
                  </button>

                  <span className={`px-2 py-0.5 rounded text-[10px] font-mono font-bold border ${badge.bg}`}>
                    {badge.label}
                  </span>

                  <ChevronDown className={`w-4 h-4 text-zinc-500 transition-transform duration-200 ${isExpanded ? 'rotate-180' : ''}`} />
                </div>
              </div>

              {/* Host Breakdown Items */}
              {isExpanded && (
                <div className="border-t border-zinc-800/80 bg-zinc-900/20 p-2.5 space-y-1 divide-y divide-zinc-800/40">
                  {hostList.map((hostItem) => {
                    const hostBadge = getStatusBadge(hostItem.current_status);
                    const isItemPinging = pingingKey === hostItem.host;
                    return (
                      <div key={hostItem.id || hostItem.host_display} className="pt-1.5 first:pt-0 flex items-center justify-between text-xs">
                        <div className="flex items-center gap-2 min-w-0 pr-2">
                          {hostItem.current_status === 'up' ? (
                            <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                          ) : hostItem.current_status === 'degraded' ? (
                            <AlertTriangle className="w-3.5 h-3.5 text-amber-400 shrink-0" />
                          ) : (
                            <AlertCircle className="w-3.5 h-3.5 text-rose-400 shrink-0" />
                          )}
                          <span className="font-mono text-[11px] text-zinc-300 truncate">
                            {hostItem.host_display}
                          </span>
                          {hostItem.consecutive_failures > 0 && (
                            <span className="px-1.5 py-0.2 rounded text-[9px] font-mono font-medium bg-rose-500/20 text-rose-300 border border-rose-500/30">
                              {hostItem.consecutive_failures}x fail
                            </span>
                          )}
                        </div>

                        <div className="flex items-center gap-2 sm:gap-3 shrink-0 font-mono">
                          <span className={`text-[11px] font-semibold ${getLatencyColor(hostItem.avg_latency_ms)}`}>
                            {hostItem.avg_latency_ms !== null ? `${hostItem.avg_latency_ms}ms` : '—'}
                          </span>
                          <span className="text-[10px] text-zinc-400 hidden xs:inline">
                            {hostItem.uptime_24h?.toFixed(1)}%
                          </span>
                          <span className="text-[10px] text-zinc-400 font-sans">
                            {hostItem.last_checked_human}
                          </span>

                          {/* Individual Host Ping Trigger */}
                          {hostItem.host && (
                            <button
                              type="button"
                              onClick={(e) => {
                                e.stopPropagation();
                                handlePingSingle(serviceKey, hostItem.host);
                              }}
                              disabled={isPinging || isItemPinging}
                              className="p-1 rounded bg-zinc-950 hover:bg-zinc-800 text-zinc-400 hover:text-amber-400 border border-zinc-800 transition-colors cursor-pointer disabled:opacity-50"
                              title={`Tes ping host ${hostItem.host_display}`}
                            >
                              {isItemPinging ? (
                                <Loader2 className="w-2.5 h-2.5 animate-spin text-amber-400" />
                              ) : (
                                <Radio className="w-2.5 h-2.5" />
                              )}
                            </button>
                          )}
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}

