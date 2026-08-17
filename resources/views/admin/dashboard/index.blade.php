@extends('layouts.admin')

@section('title', 'Developer & Admin Dashboard | faiiladmin')
@section('page_title', 'Dashboard Overview')

@section('content')
<div class="space-y-6">
    <!-- ==================== REACT REAL-TIME DEVELOPER DASHBOARD COCKPIT ==================== -->
    <div id="react-admin-dev-dashboard" 
         data-initial-snapshot="{{ json_encode($initialSnapshot ?? null) }}"
         data-csrf="{{ csrf_token() }}"
         data-importer-url="{{ route('admin.films.importer') }}"
         data-sync-moviebox-url="{{ route('admin.films.sync_api') }}"
         data-sync-dracin-url="{{ route('admin.films.sync_dracin_api') }}"
         data-reports-url="{{ route('admin.reviews.index', ['filter' => 'reported']) }}"
         data-requests-url="{{ route('admin.film-requests.index') }}"
         data-catalog-url="{{ route('admin.films.index') }}"
         data-content-rating-url="{{ route('admin.films.content_rating') }}"
         data-users-url="{{ route('admin.users.index') }}"
         data-watch-parties-url="{{ route('admin.watch_parties.index') }}">
    </div>
</div>
@endsection
