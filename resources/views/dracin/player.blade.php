@extends('layouts.app')

@section('title', 'Putar Dracin | faiilmov')
@section('meta_description', 'Nonton Dracin (Chinese Short Drama) subtitle Indonesia format vertical scroll feed ala Reels / TikTok di faiilmov. Binge watch gratis tanpa gangguan.')

@section('hide_footer', 'true')
@section('hide_navbar', 'true')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

<div class="h-[100dvh] w-full flex justify-center items-center bg-black overflow-hidden select-none">
    <div id="react-dracin-feed"
         class="h-full w-full flex justify-center items-center bg-black"
         data-initial-source="{{ $initialSource }}"
         data-initial-id="{{ $initialId }}"
         data-has-explicit-id="true"
         data-initial-ep="{{ $initialEp }}"
         data-initial-feed="{{ json_encode($feedItems ?? $initialFeed ?? []) }}"
         data-initial-active-detail="{{ json_encode($initialActiveDetail) }}"
         data-sources-list="{{ json_encode($sourcesList) }}"
         data-csrf="{{ csrf_token() }}">
        
        <!-- Loading fallback skeleton -->
        <div class="flex flex-col items-center justify-center space-y-4 text-center">
            <div class="w-10 h-10 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <div class="font-sans font-bold text-xs uppercase tracking-widest text-zinc-400">Memuat Pemutar Dracin...</div>
        </div>
    </div>
</div>
@endsection
