@extends('layouts.app')
@section('title', $blog->title)
@section('content')
<div class="container py-4">
    <article class="mb-4">
        <h1 class="mb-2">{{ $blog->title }}</h1>
        <p class="text-muted">{{ optional($blog->published_at)->format('M d, Y') }}</p>
        @if($blog->featured_image)
            <img class="img-fluid rounded mb-3" src="{{ asset('storage/blogs/'.$blog->featured_image) }}" alt="{{ $blog->title }}">
        @endif
        <div class="content">{!! $blog->content !!}</div>
    </article>

    @if($relatedBlogs->count())
        <h5 class="mb-3">Related Posts</h5>
        <div class="row">
            @foreach($relatedBlogs as $rel)
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2"><a class="text-decoration-none" href="{{ route('blog.public.show', $rel->slug) }}">{{ $rel->title }}</a></h6>
                            <p class="text-muted small mb-0">{{ optional($rel->published_at)->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
