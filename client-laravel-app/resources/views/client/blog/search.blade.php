@extends('layouts.app')
@section('title', 'Search Blogs')
@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Search Results</h1>

    <form class="row g-2 mb-3" method="GET" action="{{ route('blog.search') }}">
        <div class="col-md-6">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search...">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->slug }}" @selected(request('category')===$cat->slug)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="organization" class="form-select">
                <option value="">All Organizations</option>
                @foreach($organizations as $org)
                    <option value="{{ $org->slug }}" @selected(request('organization')===$org->slug)>{{ $org->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Filter</button>
        </div>
    </form>

    @include('client.blog.partials.blog-list', ['blogs' => $blogs])
</div>
@endsection
