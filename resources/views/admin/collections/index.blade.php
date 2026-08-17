@extends('layouts.admin')

@section('title', 'Manajemen Koleksi Film — Faiilmov Admin')

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="font-serif font-extrabold text-2xl text-white tracking-tight">
                    Manajemen Koleksi Film
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-white/10 text-zinc-300 border border-white/10">
                    HUB & MODERASI
                </span>
            </div>
            <p class="text-xs text-zinc-400 mt-1">
                Kelola koleksi resmi, pantau akun pembuat koleksi komunitas, dan moderasi konten (takedown).
            </p>
        </div>
    </div>

    <!-- React Smart Collections Admin Interface -->
    <div 
        id="react-admin-smart-collections"
        data-initial-collections="{{ json_encode($collections->items()) }}"
        data-stats="{{ json_encode($stats) }}"
        data-csrf="{{ csrf_token() }}"
    ></div>

    <!-- Global React Create Collection Modal Mount Container -->
    <div id="react-create-collection-modal" data-csrf="{{ csrf_token() }}"></div>

</div>
@endsection
