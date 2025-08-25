@extends('layouts.app')
@section('title', 'My Applications')
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">My Applications</h1>

  <form class="row g-2 mb-3" method="GET">
    <div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search opportunity title"></div>
    <div class="col-md-3">
      <select class="form-select" name="status">
        <option value="">Any Status</option>
        @foreach(['pending','reviewed','accepted','rejected','withdrawn'] as $s)
        <option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="sort">
        @foreach(['applied_at'=>'Applied Date','status'=>'Status'] as $k=>$v)
        <option value="{{ $k }}" @selected(request('sort',$k)==$k)>{{ $v }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-12"><button class="btn btn-primary">Filter</button></div>
  </form>

  @forelse($applications as $app)
    <div class="card mb-3">
      <div class="card-body d-flex justify-content-between">
        <div>
          <h5 class="mb-1"><a class="text-decoration-none" href="{{ route('opportunities.show', $app->opportunity->slug) }}">{{ $app->opportunity->title }}</a></h5>
          <p class="text-muted small mb-0">{{ $app->opportunity->organization->name ?? '—' }} · Applied: {{ optional($app->applied_at)->format('M d, Y') }}</p>
        </div>
        <div class="text-end">
          <span class="badge text-bg-secondary">{{ ucfirst($app->status) }}</span>
          @if($app->status==='pending')
            <div class="mt-2">
              <form method="POST" action="{{ route('opportunities.applications.withdraw', $app->id) }}" onsubmit="return confirm('Withdraw this application?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Withdraw</button>
              </form>
            </div>
          @endif
        </div>
      </div>
    </div>
  @empty
    <div class="alert alert-info">No applications yet.</div>
  @endforelse

  <div class="mt-3">{{ $applications->links() }}</div>
</div>
@endsection
