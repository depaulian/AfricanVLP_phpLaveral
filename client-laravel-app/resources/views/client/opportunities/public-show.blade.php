@extends('layouts.app')
@section('title', $opportunity->title)
@section('content')
<div class="container py-4">
  <h1 class="mb-1">{{ $opportunity->title }}</h1>
  <p class="text-muted mb-3">{{ $opportunity->organization->name ?? '—' }} · {{ $opportunity->category->name ?? '—' }}</p>
  <div class="content mb-4">{!! $opportunity->description !!}</div>
  <div class="mb-4"><strong>Deadline:</strong> {{ optional($opportunity->application_deadline)->format('M d, Y') }}</div>
  <a href="{{ route('login') }}" class="btn btn-primary">Login to Apply</a>

  @if($relatedOpportunities->count())
    <hr>
    <h5>Related Featured Opportunities</h5>
    <div class="row">
      @foreach($relatedOpportunities as $rel)
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h6 class="mb-1"><a class="text-decoration-none" href="{{ route('opportunities.public.show', $rel->slug) }}">{{ $rel->title }}</a></h6>
            <small class="text-muted mt-auto">Deadline: {{ optional($rel->application_deadline)->format('M d, Y') }}</small>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  @endif
</div>
@endsection
