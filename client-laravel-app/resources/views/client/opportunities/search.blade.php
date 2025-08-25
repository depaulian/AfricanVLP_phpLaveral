@extends('layouts.app')
@section('title','Search Opportunities')
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Search Opportunities</h1>
  <form method="GET" class="card p-3 mb-3">
    <div class="row g-2">
      <div class="col-md-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Keywords"></div>
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
      <div class="col-md-3">
        <select class="form-select" name="experience_level">
          <option value="">Any Level</option>
          @foreach(['entry','mid','senior'] as $lvl)
          <option value="{{ $lvl }}" @selected(request('experience_level')==$lvl)>{{ ucfirst($lvl) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3"><input class="form-control" name="location" value="{{ request('location') }}" placeholder="Location"></div>
      <div class="col-md-2"><input class="form-control" type="date" name="deadline_from" value="{{ request('deadline_from') }}"></div>
      <div class="col-md-2"><input class="form-control" type="date" name="deadline_to" value="{{ request('deadline_to') }}"></div>
      <div class="col-md-2 d-flex align-items-center gap-2">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remote_allowed" value="1" id="remote" @checked(request('remote_allowed'))>
          <label class="form-check-label" for="remote">Remote</label>
        </div>
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary">Search</button>
        <a href="{{ route('opportunities.search') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </div>
  </form>

  @include('client.opportunities.partials.list', ['opportunities' => $opportunities])
</div>
@endsection
