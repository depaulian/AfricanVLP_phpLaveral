@extends('admin.layouts.app')

@section('title', 'Opportunities')
@section('page_title', 'Opportunities')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.opportunities.ui.create') }}" class="btn btn-primary">New Opportunity</a>
  </div>

  <form method="get" class="card mb-3 p-3">
    <div class="row g-2">
      <div class="col-md-3"><input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search title/desc" /></div>
      <div class="col-md-2">
        <select class="form-select" name="organization_id">
          <option value="">All Orgs</option>
          @foreach(($filters['organizations'] ?? []) as $o)
            <option value="{{ $o->id }}" @selected(request('organization_id')==$o->id)>{{ $o->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" name="category_id">
          <option value="">All Categories</option>
          @foreach(($filters['categories'] ?? []) as $c)
            <option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" name="type">
          <option value="">Any Type</option>
          @foreach(['volunteer','internship','job','fellowship','scholarship','grant','competition'] as $t)
            <option value="{{ $t }}" @selected(request('type')==$t)>{{ ucfirst($t) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" name="status">
          <option value="">Any Status</option>
          @foreach(['draft','active','paused','closed','archived'] as $s)
            <option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-outline-secondary" type="submit">Filter</button>
      </div>
    </div>
  </form>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Title</th>
            <th>Organization</th>
            <th>Category</th>
            <th>Type</th>
            <th>Status</th>
            <th>Deadline</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($opportunities as $opp)
            <tr>
              <td class="fw-semibold">{{ $opp->title }}</td>
              <td>{{ $opp->organization->name ?? '—' }}</td>
              <td>{{ $opp->category->name ?? '—' }}</td>
              <td><span class="badge text-bg-light">{{ ucfirst($opp->type) }}</span></td>
              <td>{{ ucfirst($opp->status) }}</td>
              <td>{{ optional($opp->application_deadline)->format('Y-m-d') }}</td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.opportunities.ui.show', $opp) }}">View</a>
                <a class="btn btn-sm btn-primary" href="{{ route('admin.opportunities.ui.edit', $opp) }}">Edit</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No opportunities found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if(method_exists($opportunities,'links'))
      <div class="card-footer">{{ $opportunities->links() }}</div>
    @endif
  </div>
@endsection
