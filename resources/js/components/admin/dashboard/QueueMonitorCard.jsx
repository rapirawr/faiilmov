import React, { useState } from 'react';
import { 
  Workflow, 
  CheckCircle2, 
  AlertOctagon, 
  Clock, 
  HardDrive, 
  AlertCircle,
  ChevronRight,
  X
} from 'lucide-react';

export default function QueueMonitorCard({ queueData, serverData }) {
  const [selectedJob, setSelectedJob] = useState(null);

  const pendingCount = queueData?.pending_count || 0;
  const failedCount = queueData?.failed_count || 0;
  const recentFailed = queueData?.recent_failed || [];

  const memoryUsed = serverData?.memory_used_mb || 0;
  const memoryPeak = serverData?.memory_peak_mb || 0;

  return (
    <div className="rounded-2xl bg-zinc-900 border border-zinc-800 p-5 shadow-sm space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between gap-3 border-b border-zinc-800 pb-4">
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-xl bg-zinc-800 border border-zinc-700 text-zinc-300">
            <Workflow className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">Queue & Background Jobs</h3>
            <p className="text-xs text-zinc-400">Antrean background workers & sync jobs</p>
          </div>
        </div>

        <div>
          {failedCount > 0 ? (
            <span className="px-2.5 py-1 rounded-md text-[11px] font-mono font-medium bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center gap-1.5">
              <AlertCircle className="w-3.5 h-3.5 text-rose-400" />
              {failedCount} Gagal
            </span>
          ) : (
            <span className="px-2.5 py-1 rounded-md text-[11px] font-mono font-medium bg-zinc-800 border border-zinc-700 text-emerald-400 flex items-center gap-1.5">
              <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" />
              Bersih
            </span>
          )}
        </div>
      </div>

      {/* KPI Counters (2 Grid) */}
      <div className="grid grid-cols-2 gap-3">
        {/* Pending Jobs */}
        <div className="p-3.5 rounded-xl bg-zinc-950 border border-zinc-800 space-y-1">
          <div className="flex items-center justify-between text-zinc-400 text-xs">
            <span>Pending Jobs</span>
            <Clock className="w-3.5 h-3.5 text-zinc-500" />
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-2xl font-bold font-['Outfit'] text-[#E4E2DD]">
              {pendingCount.toLocaleString()}
            </span>
            <span className="text-[10px] text-zinc-400 font-mono">antrean</span>
          </div>
          <div className="w-full bg-zinc-800 h-1.5 rounded-full overflow-hidden mt-1">
            <div 
              className="h-full bg-zinc-400"
              style={{ width: `${Math.min(100, Math.max(5, pendingCount * 2))}%` }}
            />
          </div>
        </div>

        {/* Failed Jobs */}
        <div className={`p-3.5 rounded-xl border space-y-1 ${failedCount > 0 ? 'bg-rose-950/10 border-rose-800/40' : 'bg-zinc-950 border-zinc-800'}`}>
          <div className="flex items-center justify-between text-zinc-400 text-xs">
            <span>Failed Jobs</span>
            <AlertOctagon className={`w-3.5 h-3.5 ${failedCount > 0 ? 'text-rose-400' : 'text-zinc-500'}`} />
          </div>
          <div className="flex items-baseline gap-2">
            <span className={`text-2xl font-bold font-['Outfit'] ${failedCount > 0 ? 'text-rose-400' : 'text-[#E4E2DD]'}`}>
              {failedCount.toLocaleString()}
            </span>
            <span className="text-[10px] text-zinc-400 font-mono">gagal</span>
          </div>
          <div className="w-full bg-zinc-800 h-1.5 rounded-full overflow-hidden mt-1">
            <div 
              className={`h-full ${failedCount > 0 ? 'bg-rose-500' : 'bg-zinc-600'}`}
              style={{ width: `${failedCount > 0 ? 100 : 0}%` }}
            />
          </div>
        </div>
      </div>

      {/* Memory & Worker Stats */}
      <div className="p-3 rounded-xl bg-zinc-950 border border-zinc-800/80 flex items-center justify-between text-xs font-mono text-zinc-300">
        <div className="flex items-center gap-2">
          <HardDrive className="w-3.5 h-3.5 text-zinc-500" />
          <span>RAM: {memoryUsed} MB <span className="text-zinc-400">(Peak {memoryPeak} MB)</span></span>
        </div>
        <div className="text-zinc-400">
          Driver: <span className="text-zinc-300 font-semibold">{serverData?.queue_driver || 'sync'}</span>
        </div>
      </div>

      {/* Failed Jobs Preview (If any) */}
      {failedCount > 0 && (
        <div className="space-y-2">
          <div className="flex items-center justify-between text-xs">
            <span className="text-rose-400 font-bold flex items-center gap-1.5">
              <AlertCircle className="w-3.5 h-3.5" />
              Job Gagal Terbaru
            </span>
            <span className="text-[10px] text-zinc-400 font-mono">5 logs</span>
          </div>

          <div className="space-y-1.5">
            {recentFailed.slice(0, 3).map((job) => (
              <div 
                key={job.id} 
                onClick={() => setSelectedJob(job)}
                className="p-2.5 rounded-lg bg-rose-500/5 border border-rose-500/20 hover:border-rose-500/40 transition-colors cursor-pointer flex items-center justify-between"
              >
                <div className="min-w-0 pr-2">
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-xs font-bold text-rose-300 truncate">
                      {job.job_name}
                    </span>
                    <span className="px-1.5 py-0.2 rounded text-[9px] font-mono bg-zinc-900 text-zinc-400">
                      {job.queue}
                    </span>
                  </div>
                  <p className="text-[11px] text-zinc-400 truncate mt-0.5 font-mono">
                    {job.exception_preview}
                  </p>
                </div>
                <div className="flex items-center gap-2 shrink-0">
                  <span className="text-[10px] text-zinc-400 font-mono">{job.failed_at_human}</span>
                  <ChevronRight className="w-3.5 h-3.5 text-zinc-400" />
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Modal for Failed Job Inspector */}
      {selectedJob && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div className="w-full max-w-xl rounded-2xl bg-zinc-900 border border-zinc-800 shadow-2xl p-5 space-y-4">
            <div className="flex items-center justify-between border-b border-zinc-800 pb-3">
              <div className="flex items-center gap-2">
                <AlertOctagon className="w-5 h-5 text-rose-400" />
                <h4 className="text-sm font-bold text-[#E4E2DD] font-mono">Detail Job #{selectedJob.id}</h4>
              </div>
              <button 
                onClick={() => setSelectedJob(null)}
                className="p-1 rounded-lg text-zinc-400 hover:text-[#E4E2DD] hover:bg-zinc-800 transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <div>
                <span className="text-zinc-400 font-semibold">Job Class:</span>
                <p className="font-mono text-zinc-200 mt-0.5">{selectedJob.job_name}</p>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <div>
                  <span className="text-zinc-400 font-semibold">Queue:</span>
                  <p className="font-mono text-zinc-300 mt-0.5">{selectedJob.queue}</p>
                </div>
                <div>
                  <span className="text-zinc-400 font-semibold">Failed At:</span>
                  <p className="font-mono text-zinc-300 mt-0.5">{selectedJob.failed_at}</p>
                </div>
              </div>
              <div>
                <span className="text-zinc-400 font-semibold">Exception Details:</span>
                <pre className="mt-1 p-3 rounded-xl bg-black/60 border border-zinc-800 font-mono text-[11px] text-rose-300 overflow-x-auto max-h-48 whitespace-pre-wrap">
                  {selectedJob.exception_preview}
                </pre>
              </div>
            </div>

            <div className="flex justify-end pt-2">
              <button 
                onClick={() => setSelectedJob(null)}
                className="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-semibold text-[#E4E2DD] transition-colors"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
