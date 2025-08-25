@extends('layouts.app')
@section('title','Organization Events')
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Events for {{ $organization->name }}</h1>
  <div class="d-flex justify-content-between mb-3">
    <div>
      <a href="{{ route('organizations.events.create', $organization) }}" class="btn btn-primary">Create Event</a>
    </div>
    <form class="d-flex gap-2" method="GET">
      <select class="form-select" name="status">
        @foreach($statuses as $key=>$label)
        <option value="{{ $key }}" @selected(request('status')==$key)>{{ $label }}</option>
        @endforeach
      </select>
      <select class="form-select" name="date_filter">
        @foreach($dateFilters as $key=>$label)
        <option value="{{ $key }}" @selected(request('date')==$key)>{{ $label }}</option>
        @endforeach
      </select>
      <button class="btn btn-outline-secondary">Filter</button>
    </form>
  </div>

  @forelse($events as $event)
    <div class="card mb-3">
      <div class="card-body d-flex justify-content-between">
        <div>
          <h5 class="mb-1"><a class="text-decoration-none" href="{{ route('organizations.events.show', [$organization, $event]) }}">{{ $event->title }}</a></h5>
          <p class="text-muted small mb-0">{{ optional($event->start_date)->format('M d, Y') }} @if($event->location) · {{ $event->location }} @endif</p>
        </div>
        <div>
          <a class="btn btn-sm btn-outline-primary" href="{{ route('organizations.events.edit', [$organization, $event]) }}">Edit</a>
        </div>
      </div>
    </div>
  @empty
    <div class="alert alert-info">No events found.</div>
  @endforelse

  <div class="mt-3">{{ $events->links() }}</div>
</div>
@endsection
