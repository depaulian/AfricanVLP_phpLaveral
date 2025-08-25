@extends('layouts.app')
@section('title', 'Apply: ' . $opportunity->title)
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Apply for: {{ $opportunity->title }}</h1>

  <div class="mb-3">
    <p class="mb-1"><strong>Organization:</strong> {{ $opportunity->organization->name ?? '—' }}</p>
    <p class="mb-1"><strong>Category:</strong> {{ $opportunity->category->name ?? '—' }}</p>
    <p class="mb-0"><strong>Deadline:</strong> {{ optional($opportunity->application_deadline)->format('M d, Y') }}</p>
  </div>

  <form method="POST" action="{{ route('opportunities.apply.store', $opportunity->slug) }}" enctype="multipart/form-data" class="card p-3">
    @csrf
    <div class="mb-3">
      <label class="form-label">Message</label>
      <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="5">{{ old('message') }}</textarea>
      @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Resume (PDF/DOC, max 2MB)</label>
      <input type="file" name="resume" class="form-control @error('resume') is-invalid @enderror" accept=".pdf,.doc,.docx">
      @error('resume')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Cover Letter (optional)</label>
      <input type="file" name="cover_letter" class="form-control @error('cover_letter') is-invalid @enderror" accept=".pdf,.doc,.docx">
      @error('cover_letter')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Additional Documents (optional)</label>
      <input type="file" name="additional_documents[]" class="form-control @error('additional_documents.*') is-invalid @enderror" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
      @error('additional_documents.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary">Submit Application</button>
      <a href="{{ route('opportunities.show', $opportunity->slug) }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
