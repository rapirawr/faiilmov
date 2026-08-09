@extends('layouts.app')

@section('title', 'Siapa yang Menonton? - faiilmov')

@section('content')
@php
    $activeProfile = Auth::user()->activeProfile();
@endphp

<script>
function profilesPicker() {
    return {
        manageMode: false,
        showAddModal: false,
        newProfileName: '',
        newProfileIsChild: false,
        newProfilePin: '',
        selectedAvatar: 'https://api.dicebear.com/7.x/bottts/svg?seed=Main',
        avatarPresets: [
            'https://api.dicebear.com/7.x/bottts/svg?seed=Movie1',
            'https://api.dicebear.com/7.x/bottts/svg?seed=Chill',
            'https://api.dicebear.com/7.x/bottts/svg?seed=Kiddo',
            'https://api.dicebear.com/7.x/bottts/svg?seed=Panda',
            'https://api.dicebear.com/7.x/bottts/svg?seed=Cyber',
            'https://api.dicebear.com/7.x/bottts/svg?seed=Gamer'
        ]
    };
}
</script>

<div x-data="profilesPicker()" class="min-h-[80vh] flex flex-col items-center justify-center px-4 sm:px-6 py-12">

    <div class="max-w-4xl w-full text-center space-y-8">
        
        <!-- Header Title -->
        <div class="space-y-2">
            <h1 class="font-serif font-extrabold text-3xl sm:text-5xl text-white tracking-tight">
                Siapa yang menonton?
            </h1>
            <p class="text-xs sm:text-sm text-zinc-400">Pilih profil Anda untuk mempersonalisasi tontonan, riwayat, dan daftar putar</p>
        </div>

        <!-- Profiles Grid (Netflix Style) -->
        <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-10 pt-4">
            
            <!-- Main Account Profile Card -->
            <div class="group relative flex flex-col items-center space-y-3">
                <form action="{{ route('profiles.switch-main') }}" method="POST" id="form-main-account">
                    @csrf
                </form>

                <div @click="if(!manageMode) document.getElementById('form-main-account').submit()" 
                     class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-gradient-to-br from-amber-500 to-amber-600 p-1 transition-all duration-300 transform group-hover:scale-105 group-hover:shadow-2xl group-hover:shadow-amber-500/20 relative cursor-pointer {{ !$activeProfile ? 'ring-4 ring-amber-400 scale-105 shadow-xl shadow-amber-500/30' : '' }}">
                    
                    <div class="w-full h-full rounded-[22px] bg-dark-900 flex items-center justify-center overflow-hidden pointer-events-none">
                        @if(Auth::user()->avatar)
                            <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                        @else
                            <span class="font-extrabold text-2xl sm:text-4xl text-amber-400">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </span>
                        @endif
                    </div>

                    <!-- Active Indicator Badge -->
                    @if(!$activeProfile)
                        <div class="absolute -top-2 -right-2 px-2 py-0.5 rounded-full bg-amber-400 text-black text-[9px] font-extrabold shadow-lg flex items-center gap-1 pointer-events-none">
                            <i data-lucide="check" class="w-3 h-3"></i>
                            <span>Aktif</span>
                        </div>
                    @endif
                </div>

                <div class="text-center">
                    <p class="text-sm font-bold text-white group-hover:text-amber-300 transition-colors truncate max-w-[120px]">
                        {{ Auth::user()->name }}
                    </p>
                    <span class="text-[10px] text-amber-400 font-semibold uppercase px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20 inline-block mt-1">
                        Akun Utama
                    </span>
                </div>
            </div>

            <!-- Sub Profiles Cards -->
            @foreach($profiles as $profile)
                <div class="group relative flex flex-col items-center space-y-3">
                    <form action="{{ route('profiles.switch', $profile->id) }}" method="POST" id="form-profile-{{ $profile->id }}">
                        @csrf
                    </form>

                    <div @click="if(!manageMode) document.getElementById('form-profile-{{ $profile->id }}').submit()" 
                         class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-zinc-800 p-1 border border-white/10 transition-all duration-300 transform group-hover:scale-105 group-hover:border-amber-400/50 group-hover:shadow-2xl group-hover:shadow-amber-500/10 relative cursor-pointer {{ $activeProfile && $activeProfile->id == $profile->id ? 'ring-4 ring-amber-400 scale-105 shadow-xl shadow-amber-500/30' : '' }}">
                        
                        <div class="w-full h-full rounded-[22px] bg-dark-900 flex items-center justify-center overflow-hidden pointer-events-none">
                            @if($profile->avatar)
                                <img src="{{ $profile->avatar }}" alt="{{ $profile->name }}" class="w-full h-full object-cover">
                            @else
                                <span class="font-extrabold text-2xl sm:text-4xl text-zinc-300 group-hover:text-white">
                                    {{ strtoupper(substr($profile->name, 0, 2)) }}
                                </span>
                            @endif
                        </div>

                        <!-- Active Indicator Badge -->
                        @if($activeProfile && $activeProfile->id == $profile->id)
                            <div class="absolute -top-2 -right-2 px-2 py-0.5 rounded-full bg-amber-400 text-black text-[9px] font-extrabold shadow-lg flex items-center gap-1 pointer-events-none">
                                <i data-lucide="check" class="w-3 h-3"></i>
                                <span>Aktif</span>
                            </div>
                        @endif

                        <!-- Manage Mode Delete Button -->
                        <div x-show="manageMode" 
                             x-cloak
                             @click.stop
                             class="absolute inset-0 bg-black/70 rounded-3xl backdrop-blur-xs flex items-center justify-center z-10 transition-opacity">
                            <form action="{{ route('profiles.destroy', $profile->id) }}" method="POST" onsubmit="return confirm('Hapus profil {{ $profile->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-10 h-10 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center shadow-lg transition-transform hover:scale-110 cursor-pointer">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="text-center">
                        <p class="text-sm font-bold text-white group-hover:text-amber-300 transition-colors truncate max-w-[120px]">
                            {{ $profile->name }}
                        </p>
                        @if($profile->is_child)
                            <span class="text-[10px] text-purple-300 font-semibold uppercase px-2 py-0.5 rounded-full bg-purple-500/20 border border-purple-500/30 inline-block mt-1">
                                Anak
                            </span>
                        @else
                            <span class="text-[10px] text-zinc-400 font-semibold uppercase px-2 py-0.5 rounded-full bg-zinc-800 border border-white/10 inline-block mt-1">
                                Profil Sub
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Add Profile Card Button -->
            <div class="group flex flex-col items-center space-y-3">
                <button type="button"
                        @click="showAddModal = true" 
                        class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl border-2 border-dashed border-white/20 hover:border-amber-400/80 bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all duration-300 transform group-hover:scale-105 cursor-pointer group">
                    <div class="w-12 h-12 rounded-full bg-white/10 group-hover:bg-amber-400 group-hover:text-black flex items-center justify-center text-zinc-400 transition-all">
                        <i data-lucide="plus" class="w-6 h-6"></i>
                    </div>
                </button>
                <p class="text-sm font-medium text-zinc-400 group-hover:text-white transition-colors">Tambah Profil</p>
            </div>

        </div>

        <!-- Manage Profiles Button -->
        <div class="pt-6">
            <button type="button"
                    @click="manageMode = !manageMode" 
                    class="px-6 py-2.5 rounded-full border text-xs uppercase tracking-widest font-extrabold transition-all duration-200 cursor-pointer shadow-lg"
                    :class="manageMode ? 'bg-amber-500 text-black border-amber-400 shadow-amber-500/20' : 'border-white/20 text-zinc-400 hover:text-white hover:border-white/50 bg-white/5'">
                <span x-text="manageMode ? 'Selesai Mengelola' : 'Kelola Profil'"></span>
            </button>
        </div>

    </div>

    <!-- Modal Add Profile -->
    <div x-show="showAddModal" 
         x-cloak
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
        
        <div @click.outside="showAddModal = false" 
             class="bg-zinc-900 border border-white/15 rounded-3xl max-w-md w-full p-6 space-y-6 shadow-2xl relative text-left">
            
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-bold text-white text-base">Tambah Profil Baru</h3>
                </div>
                <button type="button" @click="showAddModal = false" class="p-1 rounded-lg text-zinc-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form action="{{ route('profiles.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Profile Avatar Selector -->
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-2">Pilih Avatar</label>
                    <input type="hidden" name="avatar" :value="selectedAvatar">
                    <div class="flex items-center gap-3 overflow-x-auto pb-2 no-scrollbar">
                        <template x-for="preset in avatarPresets" :key="preset">
                            <button type="button" 
                                    @click="selectedAvatar = preset"
                                    class="w-12 h-12 rounded-2xl p-0.5 transition-transform shrink-0 border cursor-pointer"
                                    :class="selectedAvatar === preset ? 'border-amber-400 ring-2 ring-amber-400 scale-110 bg-amber-400/20' : 'border-white/10 hover:border-white/30 bg-zinc-800'">
                                <img :src="preset" class="w-full h-full object-cover rounded-xl">
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Profile Name Input -->
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">Nama Profil</label>
                    <input type="text" 
                           name="name" 
                           required 
                           placeholder="Contoh: Adik, Kakak, atau Ruang Tamu" 
                           class="w-full bg-zinc-950 border border-white/15 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400">
                </div>

                <!-- Child Profile Toggle -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/10">
                    <div>
                        <p class="text-xs font-bold text-white">Profil Anak?</p>
                        <p class="text-[10px] text-zinc-400">Hanya menampilkan film dengan rating ramah anak</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_child" value="1" class="sr-only peer">
                        <div class="w-9 h-5 bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                </div>

                <!-- PIN 4-digit Option -->
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">PIN Pengaman (Opsional, 4-digit)</label>
                    <input type="password" 
                           name="pin" 
                           maxlength="4" 
                           placeholder="****" 
                           class="w-full bg-zinc-950 border border-white/15 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-400 font-mono tracking-widest">
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showAddModal = false" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 active:scale-95 text-xs font-bold text-zinc-300 transition-all duration-150 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 active:scale-95 text-black text-xs font-extrabold shadow-lg shadow-amber-500/20 hover:shadow-amber-500/40 transition-all duration-150 flex items-center gap-2 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4 text-black"></i>
                        <span>Simpan Profil</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
