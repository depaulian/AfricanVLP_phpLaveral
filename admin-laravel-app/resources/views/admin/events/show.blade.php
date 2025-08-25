@extends('admin.layouts.app')

@section('title', 'Event Details')
@section('page_title', 'Event Details')

@section('content')
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div>
          <h2 class="h4 mb-1">{{ $event->title }}</h2>
          <p class="text-muted mb-2">
            {{ $event->organization->name ?? '—' }}
            · {{ $event->region->name ?? '—' }}
            · {{ $event->country->name ?? '—' }}
            · {{ $event->city->name ?? '—' }}
          </p>
          <span class="badge text-bg-secondary">{{ ucfirst($event->status) }}</span>
        </div>
        <div class="text-end">
          <a href="{{ route('admin.events.ui.edit', $event) }}" class="btn btn-primary btn-sm">Edit</a>
          <a href="{{ route('admin.events.ui.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
      </div>
      <hr>
      <dl class="row small mb-4">
        <dt class="col-sm-3">Dates</dt>
        <dd class="col-sm-9">
          {{ optional($event->start_date)->format('M d, Y') ?? '—' }}
          –
          {{ optional($event->end_date)->format('M d, Y') ?? '—' }}
        </dd>
      </dl>
      <div class="mt-3">
        {!! $event->description !!}
      </div>
    </div>
  </div>
@endsection
