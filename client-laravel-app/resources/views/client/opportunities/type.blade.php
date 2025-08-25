@extends('layouts.app')
@section('title', ucfirst($type) . ' Opportunities')
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">{{ ucfirst($type) }} Opportunities</h1>
  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search"></div>
    <div class="col-md-4">
      <select class="form-select" name="category_id">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
        <option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <select class="form-select" name="organization_id">
        <option value="">All Organizations</option>
        @foreach($organizations as $org)
        <option value="{{ $org->id }}" @selected(request('organization_id')==$org->id)>{{ $org->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-12"><button class="btn btn-primary">Filter</button></div>
  </form>
  @include('client.opportunities.partials.list', ['opportunities' => $opportunities])
</div>
@endsection
