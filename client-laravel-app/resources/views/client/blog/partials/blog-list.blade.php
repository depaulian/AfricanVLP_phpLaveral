@if($blogs->count())
    <div class="list-group">
        @foreach($blogs as $blog)
            <a class="list-group-item list-group-item-action" href="{{ route(Route::has('blog.public.show') ? (request()->routeIs('blog.public*') ? 'blog.public.show' : 'blog.show') : 'blog.show', $blog->slug) }}">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">{{ $blog->title }}</h5>
                    <small>{{ optional($blog->published_at)->format('M d, Y') }}</small>
                </div>
                <p class="mb-1">{{ Str::limit(strip_tags($blog->content), 160) }}</p>
            </a>
        @endforeach
    </div>
    @if(method_exists($blogs, 'links'))
        <div class="mt-3">{{ $blogs->links() }}</div>
    @endif
@else
    <div class="alert alert-info">No results found.</div>
@endif
