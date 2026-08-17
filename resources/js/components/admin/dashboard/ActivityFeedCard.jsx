import React, { useState } from 'react';
import { 
  History, 
  Search
} from 'lucide-react';

export default function ActivityFeedCard({ activityLogs }) {
  const [search, setSearch] = useState('');
  const [selectedAction, setSelectedAction] = useState('all');

  const logs = activityLogs || [];

  const getActionColor = (action) => {
    const act = (action || '').toLowerCase();
    if (act.includes('sync')) return 'bg-zinc-800 text-amber-300 border-zinc-700';
    if (act.includes('delete') || act.includes('ban') || act.includes('reject')) return 'bg-zinc-800 text-rose-400 border-zinc-700';
    if (act.includes('create') || act.includes('add') || act.includes('resolve')) return 'bg-zinc-800 text-emerald-400 border-zinc-700';
    if (act.includes('update') || act.includes('edit')) return 'bg-zinc-800 text-sky-400 border-zinc-700';
    return 'bg-zinc-800 text-zinc-300 border-zinc-700';
  };

  const filteredLogs = logs.filter((log) => {
    const matchesSearch = 
      !search ||
      log.action?.toLowerCase().includes(search.toLowerCase()) ||
      log.description?.toLowerCase().includes(search.toLowerCase()) ||
      log.admin_name?.toLowerCase().includes(search.toLowerCase());

    const matchesAction = selectedAction === 'all' || (log.action && log.action.toLowerCase().includes(selectedAction.toLowerCase()));

    return matchesSearch && matchesAction;
  });

  return (
    <div className="rounded-2xl bg-zinc-900 border border-zinc-800 p-5 shadow-sm space-y-4">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-800 pb-4">
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-xl bg-zinc-800 border border-zinc-700 text-zinc-300">
            <History className="w-5 h-5" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-[#E4E2DD] font-['Outfit'] tracking-tight">Log Aktivitas Admin</h3>
            <p className="text-xs text-zinc-400">Riwayat aksi moderator & sinkronisasi</p>
          </div>
        </div>

        {/* Search & Action Filter */}
        <div className="flex items-center gap-2">
          <div className="relative">
            <Search className="w-3.5 h-3.5 text-zinc-500 absolute left-2.5 top-1/2 -translate-y-1/2" />
            <input 
              type="text"
              placeholder="Cari log..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-8 pr-3 py-1.5 rounded-lg bg-zinc-950 border border-zinc-800 text-xs text-[#E4E2DD] placeholder-zinc-500 focus:outline-none focus:border-zinc-600 transition-colors w-32 sm:w-40"
            />
          </div>

          <select
            value={selectedAction}
            onChange={(e) => setSelectedAction(e.target.value)}
            className="px-2.5 py-1.5 rounded-lg bg-zinc-950 border border-zinc-800 text-xs text-zinc-300 focus:outline-none focus:border-zinc-600 cursor-pointer"
          >
            <option value="all">Semua Aksi</option>
            <option value="sync">Sync API</option>
            <option value="update">Update</option>
            <option value="delete">Delete</option>
          </select>
        </div>
      </div>

      {/* Activity Log Feed Scroll */}
      <div className="space-y-1.5 max-h-[360px] overflow-y-auto pr-1 admin-scrollbar">
        {filteredLogs.length === 0 ? (
          <div className="p-8 text-center text-zinc-500 text-xs">
            Tidak ada riwayat aktivitas.
          </div>
        ) : (
          filteredLogs.map((log) => (
            <div 
              key={log.id} 
              className="p-3 rounded-xl bg-zinc-950 hover:bg-zinc-900/60 border border-zinc-800 transition-colors flex items-start gap-3"
            >
              {/* Admin Avatar */}
              <img 
                src={log.admin_avatar} 
                alt={log.admin_name}
                className="w-7 h-7 rounded-full bg-zinc-800 border border-zinc-700 object-cover shrink-0 mt-0.5"
                onError={(e) => { e.currentTarget.src = `https://api.dicebear.com/7.x/avataaars/svg?seed=${encodeURIComponent(log.admin_name)}`; }}
              />

              {/* Log Details */}
              <div className="min-w-0 flex-1 space-y-1">
                <div className="flex items-center justify-between gap-2">
                  <div className="flex items-center gap-1.5 flex-wrap">
                    <span className="text-xs font-semibold text-[#E4E2DD]">
                      {log.admin_name}
                    </span>
                    <span className={`px-1.5 py-0.2 rounded text-[9px] font-mono font-bold border ${getActionColor(log.action)}`}>
                      {log.action?.toUpperCase()}
                    </span>
                  </div>

                  <span className="text-[10px] text-zinc-500 font-mono shrink-0">
                    {log.created_at_human}
                  </span>
                </div>

                <p className="text-xs text-zinc-400 break-words line-clamp-2">
                  {log.description}
                </p>

                {log.target_type && (
                  <div className="flex items-center gap-2 pt-0.5 text-[10px] font-mono text-zinc-500">
                    <span>Target: {log.target_type} #{log.target_id || 'N/A'}</span>
                  </div>
                )}
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
