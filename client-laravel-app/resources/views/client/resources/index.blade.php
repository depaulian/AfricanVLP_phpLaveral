@extends('layouts.app')
@section('title','Resources')
@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-lg-8">
      <h1 class="h3 mb-3">Resources</h1>

      <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search resources"></div>
        <div class="col-md-3">
          <select class="form-select" name="resource_type_id">
            <option value="">All Types</option>
            @foreach($resourceTypes as $t)
            <option value="{{ $t->id }}" @selected(request('resource_type_id')==$t->id)>{{ $t->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <select class="form-select" name="category_id">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary">Filter</button>
          <a href="{{ route('resources.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>

      @forelse($resources as $res)
        <div class="card mb-3">
          <div class="card-body d-flex flex-column">
            <h2 class="h5 mb-1">{{ $res->title }}</h2>
            <p class="text-muted small mb-2">Type: {{ $res->type?->name ?? $res->type ?? '—' }} · Category: {{ $res->category?->name ?? '—' }}</p>
            <p class="mb-0">{{ Str::limit($res->description, 180) }}</p>
            <div class="mt-2">
              @auth
                <a class="btn btn-sm btn-outline-primary" href="{{ route('resources.show', $res->slug) }}">View</a>
              @else
                <a class="btn btn-sm btn-outline-primary" href="{{ route('login') }}">Login to view</a>
              @endauth
            </div>
          </div>
        </div>
      @empty
        <div class="alert alert-info">No resources found.</div>
      @endforelse

      <div class="mt-3">{{ $resources->links() }}</div>
    </div>
    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header fw-semibold">Recent</div>
        <ul class="list-group list-group-flush">
          @foreach($recentResources as $r)
            <li class="list-group-item">{{ $r->title }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
