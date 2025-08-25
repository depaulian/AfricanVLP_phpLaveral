@extends('admin.layouts.app')

@section('title', 'Create Opportunity')
@section('page_title', 'Create Opportunity')

@section('content')
  <div class="card">
    <div class="card-body">
      <form method="post" action="{{ route('admin.opportunities.ui.store') }}">
        @csrf
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
              @foreach(['volunteer','internship','job','fellowship','scholarship','grant','competition'] as $t)
                <option value="{{ $t }}" @selected(old('type')==$t)>{{ ucfirst($t) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              @foreach(['draft','active','paused','closed','archived'] as $s)
                <option value="{{ $s }}" @selected(old('status')==$s)>{{ ucfirst($s) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Organization</label>
            <select name="organization_id" class="form-select">
              <option value="">Select organization</option>
              @foreach(($organizations ?? []) as $o)
                <option value="{{ $o->id }}" @selected(old('organization_id')==$o->id)>{{ $o->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
              <option value="">Select category</option>
              @foreach(($categories ?? []) as $c)
                <option value="{{ $c->id }}" @selected(old('category_id')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Application Deadline</label>
            <input type="date" name="application_deadline" class="form-control" value="{{ old('application_deadline') }}">
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="8">{{ old('description') }}</textarea>
          </div>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Save</button>
          <a href="{{ route('admin.opportunities.ui.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
@endsection
