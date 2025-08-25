@extends('admin.layouts.app')

@section('title', 'Edit Event')
@section('page_title', 'Edit Event')

@section('content')
  <div class="card">
    <div class="card-body">
      <form method="post" action="{{ route('admin.events.ui.update', $event) }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $event->title) }}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              @foreach(['draft','scheduled','ongoing','completed','cancelled'] as $s)
                <option value="{{ $s }}" @selected(old('status', $event->status)==$s)>{{ ucfirst($s) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Organization</label>
            <select name="organization_id" class="form-select">
              <option value="">Select organization</option>
              @foreach(($organizations ?? []) as $o)
                <option value="{{ $o->id }}" @selected(old('organization_id', $event->organization_id)==$o->id)>{{ $o->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Region</label>
            <select name="region_id" class="form-select">
              <option value="">Select region</option>
              @foreach(($regions ?? []) as $r)
                <option value="{{ $r->id }}" @selected(old('region_id', $event->region_id)==$r->id)>{{ $r->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Country</label>
            <select name="country_id" class="form-select">
              <option value="">Select country</option>
              @foreach(($countries ?? []) as $c)
                <option value="{{ $c->id }}" @selected(old('country_id', $event->country_id)==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">City</label>
            <select name="city_id" class="form-select">
              <option value="">Select city</option>
              @foreach(($cities ?? []) as $city)
                <option value="{{ $city->id }}" @selected(old('city_id', $event->city_id)==$city->id)>{{ $city->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($event->start_date)->format('Y-m-d')) }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($event->end_date)->format('Y-m-d')) }}">
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="8">{{ old('description', $event->description) }}</textarea>
          </div>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Update</button>
          <a href="{{ route('admin.events.ui.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
@endsection
