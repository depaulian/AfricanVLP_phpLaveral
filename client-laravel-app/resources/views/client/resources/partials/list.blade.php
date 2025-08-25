@if($resources->count())
  @foreach($resources as $res)
    <div class="card mb-3">
      <div class="card-body d-flex flex-column">
        <h2 class="h5 mb-1">{{ $res->title }}</h2>
        <p class="text-muted small mb-2">Type: {{ $res->type?->name ?? $res->type ?? '—' }} · Category: {{ $res->category?->name ?? '—' }}</p>
        <p class="mb-0">{{ Str::limit($res->description, 160) }}</p>
        <div class="mt-2">
          @auth
            <a class="btn btn-sm btn-outline-primary" href="{{ route('resources.show', $res->slug) }}">View</a>
          @else
            <a class="btn btn-sm btn-outline-primary" href="{{ route('login') }}">Login to view</a>
          @endauth
        </div>
      </div>
    </div>
  @endforeach
  @if(method_exists($resources,'links'))
    <div class="mt-3">{{ $resources->links() }}</div>
  @endif
@else
  <div class="alert alert-info">No results found.</div>
@endif
