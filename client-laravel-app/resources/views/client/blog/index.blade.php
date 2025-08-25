@extends('layouts.app')

@section('title', 'Blogs')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <h1 class="h3 mb-4">Latest Blogs</h1>

            @if($blogs->count())
                @foreach($blogs as $blog)
                    <article class="card mb-3">
                        <div class="card-body">
                            <h2 class="h5 mb-2">
                                <a href="{{ route('blog.show', $blog->slug) }}" class="text-decoration-none">{{ $blog->title }}</a>
                            </h2>
                            <p class="text-muted small mb-2">
                                {{ optional($blog->published_at)->format('M d, Y') }}
                                @if($blog->author) · by {{ $blog->author->name }} @endif
                                @if($blog->organization) · {{ $blog->organization->name }} @endif
                            </p>
                            <p class="mb-0">{{ Str::limit(strip_tags($blog->content), 180) }}</p>
                        </div>
                    </article>
                @endforeach

                <div class="mt-3">{{ $blogs->links() }}</div>
            @else
                <div class="alert alert-info">No blog posts found.</div>
            @endif
        </div>
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 90px">
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Categories</div>
                    <ul class="list-group list-group-flush">
                        @foreach($categories as $cat)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="{{ route('blog.category', $cat->slug) }}" class="text-decoration-none">{{ $cat->name }}</a>
                                <span class="badge text-bg-light">{{ $cat->blogs_count ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card mb-3">
                    <div class="card-header fw-semibold">Organizations</div>
                    <ul class="list-group list-group-flush">
                        @foreach($organizations as $org)
                            <li class="list-group-item">
                                <a href="{{ route('blog.organization', $org->slug) }}" class="text-decoration-none">{{ $org->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card mb-3">
                    <div class="card-header fw-semibold">Featured</div>
                    <ul class="list-group list-group-flush">
                        @foreach($featuredBlogs as $f)
                            <li class="list-group-item">
                                <a href="{{ route('blog.show', $f->slug) }}" class="text-decoration-none">{{ $f->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card">
                    <div class="card-header fw-semibold">Recent</div>
                    <ul class="list-group list-group-flush">
                        @foreach($recentBlogs as $r)
                            <li class="list-group-item">
                                <a href="{{ route('blog.show', $r->slug) }}" class="text-decoration-none">{{ $r->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
