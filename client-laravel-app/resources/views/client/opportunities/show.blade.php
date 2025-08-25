@extends('layouts.app')
@section('title', $opportunity->title)
@section('content')
<div class="container py-4">
  <div class="row">
    <div class="col-lg-8">
      <h1 class="mb-1">{{ $opportunity->title }}</h1>
      <p class="text-muted mb-3">{{ $opportunity->organization->name ?? '—' }} · {{ $opportunity->category->name ?? '—' }}</p>
      <div class="mb-3">
        <span class="badge text-bg-secondary">Type: {{ ucfirst($opportunity->type) }}</span>
        <span class="badge text-bg-secondary">Experience: {{ ucfirst($opportunity->experience_level) }}</span>
        <span class="badge text-bg-secondary">Location: {{ $opportunity->location ?? '—' }}</span>
      </div>

      <div class="content mb-4">{!! $opportunity->description !!}</div>

      <div class="mb-4">
        <strong>Application Deadline:</strong> {{ optional($opportunity->application_deadline)->format('M d, Y') }}
      </div>

      @auth
        @if(!$hasApplied && $opportunity->isAcceptingApplications())
          <a href="{{ route('opportunities.apply', $opportunity->slug) }}" class="btn btn-primary">Apply Now</a>
        @elseif($hasApplied)
          <div class="alert alert-success">You have already applied.</div>
        @endif
      @else
        <a href="{{ route('login') }}" class="btn btn-outline-primary">Login to Apply</a>
      @endauth

      @if($relatedOpportunities->count())
        <hr>
        <h5>Related Opportunities</h5>
        <div class="row">
          @foreach($relatedOpportunities as $rel)
          <div class="col-md-6 mb-3">
            <div class="card h-100">
              <div class="card-body">
                <h6 class="mb-1"><a class="text-decoration-none" href="{{ route('opportunities.show', $rel->slug) }}">{{ $rel->title }}</a></h6>
                <p class="text-muted small mb-0">Deadline: {{ optional($rel->application_deadline)->format('M d, Y') }}</p>
              </div>
            </div>
          </div>
          @endforeach
        </div>
      @endif
    </div>
    <div class="col-lg-4">
      @if(isset($userApplication) && $userApplication)
        <div class="card mb-3">
          <div class="card-header fw-semibold">Your Application</div>
          <div class="card-body">
            <p class="mb-1"><strong>Status:</strong> {{ ucfirst($userApplication->status) }}</p>
            <p class="mb-0"><strong>Applied At:</strong> {{ optional($userApplication->applied_at)->format('M d, Y H:i') }}</p>
          </div>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
