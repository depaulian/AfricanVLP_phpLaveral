@extends('layouts.app')
@section('title','Edit Event')
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Edit Event - {{ $organization->name }}</h1>
  <form method="POST" action="{{ route('organization.events.update', [$organization->slug, $event->slug]) }}" class="card p-3">
    @csrf @method('PUT')
    <div class="mb-3"><label class="form-label">Title</label><input class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $event->title) }}">@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="6">{{ old('description', $event->description) }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="row g-2">
      <div class="col-md-4 mb-3"><label class="form-label">Start Date</label><input type="date" class="form-control @error('start_date') is-invalid @enderror" name="start_date" value="{{ old('start_date', optional($event->start_date)->format('Y-m-d')) }}">@error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      <div class="col-md-4 mb-3"><label class="form-label">End Date</label><input type="date" class="form-control @error('end_date') is-invalid @enderror" name="end_date" value="{{ old('end_date', optional($event->end_date)->format('Y-m-d')) }}">@error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      <div class="col-md-4 mb-3"><label class="form-label">Location</label><input class="form-control @error('location') is-invalid @enderror" name="location" value="{{ old('location', $event->location) }}">@error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    </div>
    <div class="row g-2">
      <div class="col-md-4 mb-3"><label class="form-label">Country</label><select class="form-select @error('country_id') is-invalid @enderror" name="country_id">@foreach($countries as $c)<option value="{{ $c->id }}" @selected(old('country_id', $event->country_id)==$c->id)>{{ $c->name }}</option>@endforeach</select>@error('country_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      <div class="col-md-4 mb-3"><label class="form-label">City</label><select class="form-select @error('city_id') is-invalid @enderror" name="city_id">@foreach($cities as $c)<option value="{{ $c->id }}" @selected(old('city_id', $event->city_id)==$c->id)>{{ $c->name }}</option>@endforeach</select>@error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
      <div class="col-md-4 mb-3"><label class="form-label">Region</label><select class="form-select @error('region_id') is-invalid @enderror" name="region_id">@foreach($regions as $r)<option value="{{ $r->id }}" @selected(old('region_id', $event->region_id)==$r->id)>{{ $r->name }}</option>@endforeach</select>@error('region_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    </div>
    <div class="d-flex gap-2"><button class="btn btn-primary">Save</button><a href="{{ route('organization.events.show', [$organization->slug, $event->slug]) }}" class="btn btn-outline-secondary">Cancel</a></div>
  </form>
</div>
@endsection
