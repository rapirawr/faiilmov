@extends('layouts.admin')

@section('title', 'Cari & Impor Film | faiiladmin')
@section('page_title', 'Cari & Impor Film Eksternal')

@section('content')
<div class="space-y-6">
    <!-- React Film Importer Container -->
    <div id="react-film-importer"
         data-search-url="{{ route('admin.films.external_search') }}"
         data-detail-url="{{ route('admin.films.external_detail') }}"
         data-import-url="{{ route('admin.films.import_item') }}"
         data-import-batch-url="{{ route('admin.films.import_batch') }}"
         data-csrf="{{ csrf_token() }}">
    </div>
</div>
@endsection
