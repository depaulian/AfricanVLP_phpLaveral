@extends('admin.layouts.app')

@section('title', 'Events')
@section('page_title', 'Events')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.events.ui.create') }}" class="btn btn-primary">New Event</a>
  </div>

  <form method="get" class="card mb-3 p-3">
    <div class="row g-2">
      <div class="col-md-3"><input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search title/desc/location" /></div>
      <div class="col-md-2">
        <select class="form-select" name="organization_id">
          <option value="">All Orgs</option>
          @foreach(($filters['organizations'] ?? []) as $o)
            <option value="{{ $o->id }}" @selected(request('organization_id')==$o->id)>{{ $o->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" name="region_id">
          <option value="">All Regions</option>
          @foreach(($filters['regions'] ?? []) as $r)
            <option value="{{ $r->id }}" @selected(request('region_id')==$r->id)>{{ $r->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" name="status">
          <option value="">Any Status</option>
          @foreach(['draft','active','cancelled','completed'] as $s)
            <option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="form-select" name="date_filter">
          <option value="">Any Date</option>
          @foreach(['upcoming','ongoing','past'] as $d)
            <option value="{{ $d }}" @selected(request('date_filter')==$d)>{{ ucfirst($d) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-1 d-grid"><button class="btn btn-outline-secondary" type="submit">Filter</button></div>
    </div>
  </form>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Title</th>
            <th>Organization</th>
            <th>Region</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($events as $event)
            <tr>
              <td class="fw-semibold">{{ $event->title }}</td>
              <td>{{ $event->organization->name ?? '—' }}</td>
              <td>{{ $event->region->name ?? '—' }}</td>
              <td>{{ optional($event->start_date)->format('Y-m-d') }}</td>
              <td>{{ optional($event->end_date)->format('Y-m-d') }}</td>
              <td><span class="badge text-bg-light">{{ ucfirst($event->status) }}</span></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.events.ui.show', $event) }}">View</a>
                <a class="btn btn-sm btn-primary" href="{{ route('admin.events.ui.edit', $event) }}">Edit</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No events found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if(method_exists($events,'links'))
      <div class="card-footer">{{ $events->links() }}</div>
    @endif
  </div>
@endsection
