@extends('admin.layouts.app')

@section('title', 'Blog Details')
@section('page_title', 'Blog Details')

@section('content')
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div>
          <h2 class="h4 mb-1">{{ $blog->title }}</h2>
          <p class="text-muted mb-2">
            {{ $blog->author->name ?? '—' }} · {{ $blog->organization->name ?? '—' }} · {{ $blog->category->name ?? '—' }}
          </p>
          <span class="badge text-bg-light">{{ ucfirst($blog->status) }}</span>
          @if($blog->featured)
            <span class="badge text-bg-success">Featured</span>
          @endif
        </div>
        <div class="text-end">
          <a href="{{ route('admin.blogs.ui.edit', $blog) }}" class="btn btn-primary btn-sm">Edit</a>
          <a href="{{ route('admin.blogs.ui.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
      </div>
      <hr>
      <div class="mt-3">
        {!! $blog->content !!}
      </div>
    </div>
  </div>
@endsection
