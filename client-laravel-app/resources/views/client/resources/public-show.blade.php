@extends('layouts.app')
@section('title', $resource->title)
@section('content')
<div class="container py-4">
  <h1 class="mb-2">{{ $resource->title }}</h1>
  <div class="mb-3">{!! nl2br(e($resource->description)) !!}</div>
  @if($resource->file_url)
    <a class="btn btn-primary" href="{{ $resource->file_url }}" target="_blank" rel="noopener">Open Resource</a>
  @endif
  @if($relatedResources->count())
    <hr>
    <h5>Related Resources</h5>
    <ul>
      @foreach($relatedResources as $rel)
        <li><a class="text-decoration-none" href="{{ route('resources.public.show', $rel->slug) }}">{{ $rel->title }}</a></li>
      @endforeach
    </ul>
  @endif
</div>
@endsection
