@extends('layouts.app')
@section('title','Opportunities')
@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-lg-8">
      <h1 class="h3 mb-3">Opportunities</h1>

      <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search"></div>
        <div class="col-md-3">
          <select class="form-select" name="category_id">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <select class="form-select" name="organization_id">
            <option value="">All Organizations</option>
            @foreach($organizations as $org)
            <option value="{{ $org->id }}" @selected(request('organization_id')==$org->id)>{{ $org->name }}</option>
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
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary">Filter</button>
          <a href="{{ route('opportunities.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>

      @forelse($opportunities as $opp)
        <div class="card mb-3">
          <div class="card-body">
            <h2 class="h5 mb-1"><a class="text-decoration-none" href="{{ route('opportunities.show', $opp->slug) }}">{{ $opp->title }}</a></h2>
            <p class="text-muted small mb-2">
              {{ $opp->organization->name ?? '—' }} · {{ $opp->category->name ?? '—' }} · Deadline: {{ optional($opp->application_deadline)->format('M d, Y') }}
            </p>
            <p class="mb-0">{{ Str::limit(strip_tags($opp->description), 180) }}</p>
          </div>
        </div>
      @empty
        <div class="alert alert-info">No opportunities found.</div>
      @endforelse

      <div class="mt-3">{{ $opportunities->links() }}</div>
    </div>
    <div class="col-lg-4">
      <div class="sticky-top" style="top: 90px">
        <div class="card mb-3">
          <div class="card-header fw-semibold">Featured</div>
          <ul class="list-group list-group-flush">
            @foreach($featuredOpportunities as $f)
              <li class="list-group-item"><a class="text-decoration-none" href="{{ route('opportunities.show', $f->slug) }}">{{ $f->title }}</a></li>
            @endforeach
          </ul>
        </div>
        <div class="card">
          <div class="card-header fw-semibold">Urgent (Deadline ≤ 7 days)</div>
          <ul class="list-group list-group-flush">
            @foreach($urgentOpportunities as $u)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <a class="text-decoration-none" href="{{ route('opportunities.show', $u->slug) }}">{{ Str::limit($u->title, 40) }}</a>
                <small class="text-muted">{{ optional($u->application_deadline)->format('M d') }}</small>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
