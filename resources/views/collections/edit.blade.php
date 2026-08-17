@extends('layouts.app')

@section('title', 'Studio Editor - ' . $collection->name)

@section('content')
<div class="min-h-screen pb-16">
    <div id="react-collection-studio-editor" 
         data-collection="{{ json_encode($collection) }}"
         data-initial-films="{{ json_encode($initialFilms) }}"
         data-csrf="{{ csrf_token() }}">
    </div>
</div>
@endsection
