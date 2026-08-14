@extends('layouts.app')

@section('title', 'Katalog Dracin (Chinese Short Drama) | faiilmov')
@section('meta_description', 'Jelajahi dan nonton katalog lengkap Dracin (Chinese Short Drama) subtitle Indonesia dari DramaBox, ReelShort, ShortMax, GoodShort gratis di faiilmov.')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <div id="react-dracin-catalog"
         data-initial-source="{{ $currentSource }}"
         data-initial-feed="{{ json_encode($feedItems) }}"
         data-sources-list="{{ json_encode($sourcesList) }}"
         data-csrf="{{ csrf_token() }}">
        
        <!-- Loading fallback skeleton for Dracin Catalog Grid -->
        <div class="w-full space-y-6 animate-pulse">
            <!-- Header skeleton -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-4 border-b border-white/10">
                <div class="space-y-2">
                    <div class="h-8 w-48 bg-white/10 rounded-xl"></div>
                    <div class="h-4 w-72 bg-white/5 rounded-lg"></div>
                </div>
                <div class="h-10 w-64 bg-white/10 rounded-2xl"></div>
            </div>

            <!-- Provider pills skeleton -->
            <div class="flex gap-2 overflow-hidden py-2">
                <div class="w-24 h-9 rounded-full bg-white/20"></div>
                <div class="w-24 h-9 rounded-full bg-white/5"></div>
                <div class="w-24 h-9 rounded-full bg-white/5"></div>
                <div class="w-24 h-9 rounded-full bg-white/5"></div>
                <div class="w-24 h-9 rounded-full bg-white/5"></div>
            </div>

            <!-- Grid Skeletons -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 pt-2">
                @for ($i = 0; $i < 12; $i++)
                    <div class="flex flex-col bg-dark-900/60 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
                        <div class="relative aspect-[2/3] w-full bg-white/5 overflow-hidden">
                            <div class="absolute top-2.5 left-2.5 w-12 h-4 rounded bg-white/10"></div>
                            <div class="absolute top-2.5 right-2.5 w-10 h-4 rounded bg-white/10"></div>
                        </div>
                        <div class="p-3 flex flex-col space-y-2">
                            <div class="h-3.5 bg-white/10 rounded w-11/12"></div>
                            <div class="h-3.5 bg-white/5 rounded w-3/4"></div>
                            <div class="flex items-center justify-between pt-1">
                                <div class="h-2.5 bg-white/10 rounded w-10"></div>
                                <div class="h-2.5 bg-white/5 rounded w-6"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>
@endsection
