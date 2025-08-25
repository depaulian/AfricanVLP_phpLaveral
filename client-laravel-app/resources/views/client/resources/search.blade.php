@extends('layouts.app')
@section('title','Search Resources')
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Search Resources</h1>
  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Keywords"></div>
    <div class="col-md-3">
      <select class="form-select" name="resource_type_id">
        <option value="">All Types</option>
        @foreach($resourceTypes as $t)
        <option value="{{ $t->id }}" @selected(request('resource_type_id')==$t->id)>{{ $t->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="category_id">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
        <option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-12"><button class="btn btn-primary">Search</button></div>
  </form>
  @include('client.resources.partials.list',['resources'=>$resources])
</div>
@endsection
