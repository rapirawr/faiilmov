@extends('layouts.app')

@section('title', 'Profil & Nonton Bareng - faiilmov')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
    <h1 class="font-serif font-bold text-2xl sm:text-3xl text-white mb-8">Profil & Nonton Bareng</h1>

    <!-- Your Profiles -->
    <section class="glass-panel p-6 rounded-3xl border border-white/10 mb-8">
        <h2 class="font-serif font-bold text-lg text-white mb-6 flex items-center gap-2">
            <i data-lucide="users" class="w-5 h-5 text-amber-400"></i>
            <span>Profil Anda</span>
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <!-- Your Profile (Admin) -->
            <div class="glass-card p-4 rounded-2xl border border-amber-500/40 flex flex-col items-center text-center">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white text-xs font-bold mb-3">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <p class="text-xs font-bold text-white mb-1">{{ Auth::user()->name }}</p>
                <span class="text-[10px] text-amber-400 font-semibold uppercase px-2 py-0.5 rounded-lg bg-amber-500/20 border border-amber-500/30">
                    Admin
                </span>
            </div>

            <!-- Add Profile Button -->
            <button @click="showAddProfile = !showAddProfile" 
                    class="glass-card p-4 rounded-2xl border border-white/20 flex flex-col items-center justify-center gap-3 hover:border-amber-400/50 transition-all cursor-pointer">
                <div class="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center">
                    <i data-lucide="plus" class="w-8 h-8 text-white"></i>
                </div>
                <p class="text-xs font-semibold text-white">Tambah Profil</p>
            </button>

            <!-- Existing Profiles -->
            @foreach($profiles as $profile)
                <div class="glass-card p-4 rounded-2xl border border-white/10 flex flex-col items-center text-center group">
                    <div class="w-16 h-16 rounded-full bg-zinc-700 flex items-center justify-center text-white text-xs font-bold mb-3">
                        {{ strtoupper(substr($profile->name, 0, 2)) }}
                    </div>
                    <p class="text-xs font-bold text-white mb-2 truncate w-full">{{ $profile->name }}</p>
                    
                    @if($profile->is_child)
                        <span class="text-[10px] text-zinc-400 font-semibold uppercase px-2 py-0.5 rounded-lg bg-zinc-700 border border-zinc-500">
                            Anak
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <!-- Watch Party Quick Access -->
    <section class="glass-panel p-6 rounded-3xl border border-white/10">
        <h2 class="font-serif font-bold text-lg text-white mb-6 flex items-center gap-2">
            <i data-lucide="video" class="w-5 h-5 text-amber-400"></i>
            <span>Nonton Bareng</span>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="{{ route('watch-party.create') }}" 
               class="glass-card p-5 rounded-2xl border border-white/10 hover:border-amber-500/40 hover:bg-white/5 transition-all flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-full bg-amber-500/20 border border-amber-500/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i data-lucide="users" class="w-6 h-6 text-amber-400"></i>
                </div>
                <p class="text-xs font-bold text-white">Buat Room Baru</p>
                <p class="text-[10px] text-zinc-400 mt-1">Undang teman ke room nonton</p>
            </a>

            <a href="{{ route('browse') }}" 
               class="glass-card p-5 rounded-2xl border border-white/10 hover:border-amber-500/40 hover:bg-white/5 transition-all flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-full bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i data-lucide="film" class="w-6 h-6 text-emerald-400"></i>
                </div>
                <p class="text-xs font-bold text-white">Pilih Film</p>
                <p class="text-[10px] text-zinc-400 mt-1">Cari film yang ingin ditonton</p>
            </a>

            <a href="{{ route('profile') }}" 
               class="glass-card p-5 rounded-2xl border border-white/10 hover:border-amber-500/40 hover:bg-white/5 transition-all flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-full bg-blue-500/20 border border-blue-500/30 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i data-lucide="activity" class="w-6 h-6 text-blue-400"></i>
                </div>
                <p class="text-xs font-bold text-white">History</p>
                <p class="text-[10px] text-zinc-400 mt-1">Lihat riwayat tontonan</p>
            </a>
        </div>
    </section>
</div>
@endsection
