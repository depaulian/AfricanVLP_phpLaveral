@extends('layouts.app')
@section('title', $event->title)
@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h1 class="mb-1">{{ $event->title }}</h1>
      <p class="text-muted mb-0">{{ optional($event->start_date)->format('M d, Y') }} @if($event->location) · {{ $event->location }} @endif</p>
    </div>
    @if($isAdmin)
      <a class="btn btn-outline-primary" href="{{ route('organization.events.edit', [$organization->slug, $event->slug]) }}">Edit</a>
    @endif
  </div>
  <div class="content">{!! nl2br(e($event->description)) !!}</div>
</div>
@endsection
