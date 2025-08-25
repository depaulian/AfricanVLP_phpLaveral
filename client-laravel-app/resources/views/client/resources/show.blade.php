@extends('layouts.app')
@section('title', $resource->title)
@section('content')
<div class="container py-4">
  <h1 class="mb-2">{{ $resource->title }}</h1>
  <p class="text-muted">Type: {{ $resource->type?->name ?? $resource->type ?? '—' }} · Category: {{ $resource->category?->name ?? '—' }}</p>
  <div class="mb-3">{!! nl2br(e($resource->description)) !!}</div>
  @if($resource->file_url)
    <a class="btn btn-primary" href="{{ $resource->file_url }}" target="_blank" rel="noopener">Open Resource</a>
  @endif

  @if($relatedResources->count())
    <hr>
    <h5>Related Resources</h5>
    <ul>
      @foreach($relatedResources as $rel)
        <li>{{ $rel->title }}</li>
      @endforeach
    </ul>
  @endif
</div>
@endsection
