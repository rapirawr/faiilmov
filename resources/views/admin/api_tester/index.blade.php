@extends('layouts.admin')

@section('title', 'API Tester & Docs | Admin Faiilmov')
@section('page_title', 'API Tester & Documentation')

@section('content')
<div x-data="apiTesterApp()" class="space-y-6">

    <!-- Header Banner Card -->
    <div class="relative overflow-hidden rounded-3xl bg-zinc-900/90 p-6 md:p-8 border border-zinc-800 shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight font-['Outfit']">
                    Interactive API Tester & Postman Suite
                </h1>
                <p class="text-zinc-400 text-xs md:text-sm leading-relaxed">
                    Uji coba langsung seluruh endpoint API Faiilmov (Katalog, Auth, Season, Episode, Multi-Profile, Watch Party, Notifikasi, dsb) secara interaktif dari browser atau unduh koleksi Postman resmi.
                </p>
            </div>

            <!-- Export Postman Button -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="{{ route('admin.api_tester.export_postman') }}" 
                   class="flex items-center gap-2.5 px-5 py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-extrabold text-xs shadow-lg shadow-amber-500/20 transition-all cursor-pointer">
                    <i data-lucide="download-cloud" class="w-4 h-4"></i>
                    <span>Export Postman Collection</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Tester Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Sidebar: Endpoint Navigator -->
        <div class="lg:col-span-4 bg-zinc-900/90 border border-zinc-800 rounded-3xl p-4 space-y-4 shadow-xl">
            <div class="flex items-center justify-between px-2">
                <h3 class="font-['Outfit'] font-bold text-sm text-white flex items-center gap-2">
                    <i data-lucide="list-tree" class="w-4 h-4 text-sky-400"></i>
                    Daftar Endpoints ({{ count($endpoints) }})
                </h3>
            </div>

            <!-- Quick Filter Input -->
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500 absolute left-3 top-3"></i>
                <input type="text" 
                       x-model="filterQuery"
                       placeholder="Cari endpoint atau kata kunci..." 
                       class="w-full pl-9 pr-4 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition-colors">
            </div>

            <!-- Grouped Endpoints List -->
            <div class="space-y-4 max-h-[600px] overflow-y-auto pr-1 scrollbar-thin">
                <template x-for="(groupItems, groupName) in filteredGroups" :key="groupName">
                    <div class="space-y-1.5" x-show="groupItems.length > 0">
                        <div class="px-2 py-1 text-[11px] font-extrabold tracking-wider text-zinc-400 uppercase font-['Outfit'] flex items-center justify-between">
                            <span x-text="groupName"></span>
                            <span class="px-1.5 py-0.2 rounded-md bg-zinc-950 border border-zinc-800 text-zinc-400 text-[10px]" x-text="groupItems.length"></span>
                        </div>

                        <div class="space-y-1">
                            <template x-for="(ep, idx) in groupItems" :key="ep.name">
                                <button @click="selectEndpoint(ep)" 
                                        :class="selectedEndpoint.name === ep.name ? 'bg-zinc-800 border-amber-500/50 text-white font-bold' : 'bg-zinc-950 border-zinc-800 text-zinc-400 hover:bg-zinc-800/40 hover:text-zinc-200'"
                                        class="w-full text-left px-3 py-2.5 rounded-xl border text-xs transition-all flex items-center justify-between gap-2 group cursor-pointer">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <!-- Method Badge -->
                                        <span :class="getMethodBadgeClass(ep.method)" class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider shrink-0" x-text="ep.method"></span>
                                        <span class="truncate font-medium text-xs text-zinc-200 group-hover:text-white" x-text="ep.name"></span>
                                    </div>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-zinc-500 group-hover:text-zinc-300 shrink-0"></i>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Right Panel: Request & Response -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Request Builder Box -->
            <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 md:p-6 space-y-5 shadow-xl">
                
                <!-- Top URL & Method Bar -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-zinc-400 uppercase tracking-wider font-['Outfit']" x-text="selectedEndpoint.group"></span>
                        <span x-show="selectedEndpoint.auth" class="px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 text-[10px] font-bold border border-amber-500/30 flex items-center gap-1">
                            <i data-lucide="lock" class="w-3 h-3"></i> Require Token Auth
                        </span>
                    </div>

                    <h2 class="text-lg font-bold text-white font-['Outfit']" x-text="selectedEndpoint.name"></h2>
                    <p class="text-xs text-zinc-400" x-text="selectedEndpoint.description"></p>

                    <!-- URL Input Group -->
                    <div class="flex flex-col sm:flex-row items-stretch gap-2 pt-2">
                        <div class="flex items-center gap-0 w-full rounded-2xl border border-zinc-800 bg-zinc-950 overflow-hidden focus-within:border-amber-500 transition-colors">
                            <span :class="getMethodBadgeClass(selectedEndpoint.method)" class="px-4 py-3 text-xs font-black uppercase tracking-wider shrink-0" x-text="selectedEndpoint.method"></span>
                            <input type="text" 
                                   x-model="activeUrl" 
                                   class="w-full bg-transparent px-3 py-2 text-xs font-mono text-white outline-none border-none focus:ring-0">
                        </div>

                        <button @click="executeRequest()" 
                                :disabled="isLoading"
                                class="px-6 py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-extrabold text-xs shadow-lg shadow-amber-500/20 transition-all flex items-center justify-center gap-2 shrink-0 cursor-pointer disabled:opacity-50">
                            <i x-show="!isLoading" data-lucide="send" class="w-4 h-4"></i>
                            <i x-show="isLoading" data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                            <span x-text="isLoading ? 'Sending...' : 'Send Request'"></span>
                        </button>
                    </div>
                </div>

                <!-- Tabs: Query Params / Headers / Body -->
                <div class="space-y-4 pt-2 border-t border-zinc-800">
                    <div class="flex items-center gap-2 border-b border-zinc-800 pb-2">
                        <button @click="activeTab = 'params'" 
                                :class="activeTab === 'params' ? 'text-amber-400 border-amber-400 font-bold' : 'text-zinc-400 border-transparent hover:text-zinc-200'"
                                class="px-3 py-1.5 text-xs border-b-2 transition-colors cursor-pointer">
                            Query Parameters (<span x-text="Object.keys(queryParams).length"></span>)
                        </button>
                        <button @click="activeTab = 'headers'" 
                                :class="activeTab === 'headers' ? 'text-amber-400 border-amber-400 font-bold' : 'text-zinc-400 border-transparent hover:text-zinc-200'"
                                class="px-3 py-1.5 text-xs border-b-2 transition-colors cursor-pointer">
                            Headers (<span x-text="Object.keys(requestHeaders).length"></span>)
                        </button>
                        <button x-show="selectedEndpoint.method !== 'GET'" 
                                @click="activeTab = 'body'" 
                                :class="activeTab === 'body' ? 'text-amber-400 border-amber-400 font-bold' : 'text-zinc-400 border-transparent hover:text-zinc-200'"
                                class="px-3 py-1.5 text-xs border-b-2 transition-colors cursor-pointer">
                            JSON Body
                        </button>
                    </div>

                    <!-- Query Params Tab -->
                    <div x-show="activeTab === 'params'" class="space-y-2">
                        <template x-for="(val, key) in queryParams" :key="key">
                            <div class="flex items-center gap-2">
                                <input type="text" :value="key" readonly class="w-1/3 px-3 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs font-mono text-zinc-400">
                                <input type="text" x-model="queryParams[key]" @input="updateActiveUrl()" class="w-2/3 px-3 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs font-mono text-white focus:outline-none focus:border-amber-500">
                            </div>
                        </template>
                        <p x-show="Object.keys(queryParams).length === 0" class="text-xs text-zinc-500 italic py-2">Tidak ada query parameter untuk endpoint ini.</p>
                    </div>

                    <!-- Headers Tab -->
                    <div x-show="activeTab === 'headers'" class="space-y-2">
                        <template x-for="(val, key) in requestHeaders" :key="key">
                            <div class="flex items-center gap-2">
                                <input type="text" :value="key" readonly class="w-1/3 px-3 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs font-mono text-zinc-400">
                                <input type="text" x-model="requestHeaders[key]" class="w-2/3 px-3 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs font-mono text-white focus:outline-none focus:border-amber-500">
                            </div>
                        </template>
                    </div>

                    <!-- Body Tab -->
                    <div x-show="activeTab === 'body' && selectedEndpoint.method !== 'GET'" class="space-y-2">
                        <textarea x-model="requestBody" 
                                  rows="6" 
                                  class="w-full p-3 rounded-xl bg-zinc-950 border border-zinc-800 font-mono text-xs text-emerald-400 focus:outline-none focus:border-amber-500"></textarea>
                    </div>
                </div>

            </div>

            <!-- Response Console Display -->
            <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 md:p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <h3 class="font-['Outfit'] font-bold text-sm text-white flex items-center gap-2">
                        <i data-lucide="terminal" class="w-4 h-4 text-emerald-400"></i>
                        Response Output
                    </h3>

                    <div class="flex items-center gap-3">
                        <span x-show="responseStatus" 
                              :class="responseStatus >= 200 && responseStatus < 300 ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/30'"
                              class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold border" 
                              x-text="responseStatus + ' ' + responseStatusText"></span>

                        <span x-show="responseTime" class="text-xs text-zinc-400 font-mono" x-text="responseTime + ' ms'"></span>

                        <button x-show="responseBody" 
                                @click="copyResponse()" 
                                class="px-3 py-1.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs text-white font-medium transition-colors flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            <span x-text="copied ? 'Copied!' : 'Copy JSON'"></span>
                        </button>
                    </div>
                </div>

                <!-- Response Display Area -->
                <div>
                    <div x-show="!responseBody && !isLoading" class="py-12 text-center text-zinc-500 space-y-2">
                        <i data-lucide="play-circle" class="w-10 h-10 mx-auto stroke-1 text-zinc-600"></i>
                        <p class="text-xs">Klik tombol <span class="text-white font-bold">Send Request</span> untuk mengeksekusi uji coba API.</p>
                    </div>

                    <div x-show="isLoading" class="py-12 text-center text-zinc-400 space-y-3">
                        <i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto text-amber-400"></i>
                        <p class="text-xs font-mono">Mengirim permintaan HTTP ke server...</p>
                    </div>

                    <div x-show="responseBody && !isLoading" class="space-y-3">
                        <pre class="w-full max-h-96 overflow-auto p-4 rounded-2xl bg-zinc-950 border border-zinc-800 font-mono text-xs text-emerald-400 leading-relaxed scrollbar-thin"><code x-text="responseBody"></code></pre>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<script>
function apiTesterApp() {
    return {
        endpoints: @json($endpoints),
        groupedEndpoints: @json($groupedEndpoints),
        baseUrl: "{{ $baseUrl }}",
        filterQuery: '',
        selectedEndpoint: {},
        activeUrl: '',
        activeTab: 'params',
        queryParams: {},
        requestHeaders: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        requestBody: '',
        isLoading: false,
        responseStatus: null,
        responseStatusText: '',
        responseTime: null,
        responseBody: null,
        copied: false,

        init() {
            if (this.endpoints.length > 0) {
                this.selectEndpoint(this.endpoints[0]);
            }
        },

        get filteredGroups() {
            if (!this.filterQuery.trim()) {
                return this.groupedEndpoints;
            }
            const q = this.filterQuery.toLowerCase();
            const result = {};
            for (const [group, items] of Object.entries(this.groupedEndpoints)) {
                const matched = items.filter(ep => 
                    ep.name.toLowerCase().includes(q) || 
                    ep.path.toLowerCase().includes(q) || 
                    ep.description.toLowerCase().includes(q)
                );
                if (matched.length > 0) {
                    result[group] = matched;
                }
            }
            return result;
        },

        selectEndpoint(ep) {
            this.selectedEndpoint = ep;
            this.activeUrl = this.baseUrl + ep.path;
            this.queryParams = Object.assign({}, ep.queryParams || {});
            this.requestBody = ep.body || '';
            this.activeTab = ep.method === 'GET' ? 'params' : 'body';
            
            this.requestHeaders = {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };

            this.updateActiveUrl();
            this.responseBody = null;
            this.responseStatus = null;
        },

        updateActiveUrl() {
            let url = this.baseUrl + this.selectedEndpoint.path;
            const keys = Object.keys(this.queryParams);
            if (keys.length > 0) {
                const params = new URLSearchParams();
                keys.forEach(k => {
                    if (this.queryParams[k] !== '') {
                        params.append(k, this.queryParams[k]);
                    }
                });
                const qString = params.toString();
                if (qString) url += '?' + qString;
            }
            this.activeUrl = url;
        },

        getMethodBadgeClass(method) {
            switch(method) {
                case 'GET': return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30';
                case 'POST': return 'bg-sky-500/10 text-sky-400 border border-sky-500/30';
                case 'PUT': return 'bg-amber-500/10 text-amber-400 border border-amber-500/30';
                case 'DELETE': return 'bg-rose-500/10 text-rose-400 border border-rose-500/30';
                default: return 'bg-zinc-800 text-zinc-300';
            }
        },

        async executeRequest() {
            this.isLoading = true;
            this.responseBody = null;
            this.responseStatus = null;

            const startTime = performance.now();

            try {
                const options = {
                    method: this.selectedEndpoint.method,
                    headers: Object.assign({}, this.requestHeaders)
                };

                if (this.selectedEndpoint.method !== 'GET' && this.requestBody) {
                    options.body = this.requestBody;
                }

                const res = await fetch(this.activeUrl, options);
                const endTime = performance.now();
                this.responseTime = Math.round(endTime - startTime);

                this.responseStatus = res.status;
                this.responseStatusText = res.statusText || (res.status === 200 ? 'OK' : 'Response');

                const text = await res.text();
                try {
                    const parsed = JSON.parse(text);
                    this.responseBody = JSON.stringify(parsed, null, 2);
                } catch(e) {
                    this.responseBody = text;
                }
            } catch (error) {
                this.responseStatus = 500;
                this.responseStatusText = 'Error';
                this.responseBody = JSON.stringify({ error: error.message }, null, 2);
            } finally {
                this.isLoading = false;
            }
        },

        copyResponse() {
            if (!this.responseBody) return;
            navigator.clipboard.writeText(this.responseBody);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        }
    }
}
</script>
@endsection
