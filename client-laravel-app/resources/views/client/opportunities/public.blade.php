@extends('layouts.app')
@section('title','Featured Opportunities')
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Featured Opportunities</h1>
  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search"></div>
    <div class="col-md-4">
      <select class="form-select" name="category_id">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
        <option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" name="type">
        <option value="">Any Type</option>
        @foreach(['volunteer','internship','job','fellowship','scholarship','grant','competition'] as $t)
        <option value="{{ $t }}" @selected(request('type')==$t)>{{ ucfirst($t) }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-12"><button class="btn btn-primary">Filter</button></div>
  </form>

  <div class="row">
    @forelse($opportunities as $opp)
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h2 class="h6 mb-1"><a class="text-decoration-none" href="{{ route('opportunities.public.show', $opp->slug) }}">{{ $opp->title }}</a></h2>
            <p class="text-muted small mb-2">{{ $opp->organization->name ?? '—' }}</p>
            <p class="mb-3">{{ Str::limit(strip_tags($opp->description), 100) }}</p>
            <div class="mt-auto d-flex justify-content-between">
              <small class="text-muted">Deadline: {{ optional($opp->application_deadline)->format('M d, Y') }}</small>
              <a class="btn btn-sm btn-outline-primary" href="{{ route('opportunities.public.show', $opp->slug) }}">View</a>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12"><div class="alert alert-info">No featured opportunities found.</div></div>
    @endforelse
  </div>

  <div class="mt-3">{{ $opportunities->links() }}</div>
</div>
@endsection
