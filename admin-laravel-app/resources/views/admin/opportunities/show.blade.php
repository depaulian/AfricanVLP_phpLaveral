@extends('admin.layouts.app')

@section('title', 'Opportunity Details')
@section('page_title', 'Opportunity Details')

@section('content')
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <div>
          <h2 class="h4 mb-1">{{ $opportunity->title }}</h2>
          <p class="text-muted mb-2">
            {{ $opportunity->organization->name ?? '—' }} · {{ $opportunity->category->name ?? '—' }}
          </p>
          <span class="badge text-bg-light">{{ ucfirst($opportunity->type) }}</span>
          <span class="badge text-bg-secondary">{{ ucfirst($opportunity->status) }}</span>
        </div>
        <div class="text-end">
          <a href="{{ route('admin.opportunities.ui.edit', $opportunity) }}" class="btn btn-primary btn-sm">Edit</a>
          <a href="{{ route('admin.opportunities.ui.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
      </div>
      <hr>
      <dl class="row small mb-4">
        <dt class="col-sm-3">Application Deadline</dt>
        <dd class="col-sm-9">{{ optional($opportunity->application_deadline)->format('M d, Y') ?? '—' }}</dd>
      </dl>
      <div class="mt-3">
        {!! $opportunity->description !!}
      </div>
    </div>
  </div>
@endsection
