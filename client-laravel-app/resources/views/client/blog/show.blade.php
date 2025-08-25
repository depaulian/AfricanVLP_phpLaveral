@extends('layouts.app')

@section('title', $blog->title)

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <article class="mb-4">
                <h1 class="mb-2">{{ $blog->title }}</h1>
                <p class="text-muted">{{ optional($blog->published_at)->format('M d, Y') }} @if($blog->author) · by {{ $blog->author->name }} @endif</p>
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
                                    <h6 class="mb-2"><a class="text-decoration-none" href="{{ route('blog.show', $rel->slug) }}">{{ $rel->title }}</a></h6>
                                    <p class="text-muted small mb-0">{{ optional($rel->published_at)->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="d-flex justify-content-between mt-4">
                @if($previousBlog)
                    <a href="{{ route('blog.show', $previousBlog->slug) }}" class="btn btn-outline-secondary">← {{ Str::limit($previousBlog->title, 40) }}</a>
                @else <span></span> @endif
                @if($nextBlog)
                    <a href="{{ route('blog.show', $nextBlog->slug) }}" class="btn btn-outline-secondary">{{ Str::limit($nextBlog->title, 40) }} →</a>
                @endif
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Categories</div>
                <ul class="list-group list-group-flush">
                    @foreach($categories ?? [] as $cat)
                        <li class="list-group-item"><a href="{{ route('blog.category', $cat->slug) }}" class="text-decoration-none">{{ $cat->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
