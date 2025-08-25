@extends('admin.layouts.app')

@section('title', 'Blogs')
@section('page_title', 'Blogs')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.blogs.ui.create') }}" class="btn btn-primary">New Blog</a>
  </div>

  <form method="get" class="card mb-3 p-3">
    <div class="row g-2">
      <div class="col-md-3">
        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search title/content" />
      </div>
      <div class="col-md-2">
        <select class="form-select" name="author_id">
          <option value="">All Authors</option>
          @foreach(($filters['authors'] ?? []) as $a)
            <option value="{{ $a->id }}" @selected(request('author_id')==$a->id)>{{ $a->name }}</option>
          @endforeach
        </select>
      </div>
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
        <select class="form-select" name="status">
          <option value="">Any Status</option>
          @foreach(['draft','published','archived'] as $s)
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
            <th>Author</th>
            <th>Organization</th>
            <th>Category</th>
            <th>Status</th>
            <th>Featured</th>
            <th>Created</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($blogs as $blog)
            <tr>
              <td class="fw-semibold">{{ $blog->title }}</td>
              <td>{{ $blog->author->name ?? '—' }}</td>
              <td>{{ $blog->organization->name ?? '—' }}</td>
              <td>{{ $blog->category->name ?? '—' }}</td>
              <td><span class="badge text-bg-light">{{ ucfirst($blog->status) }}</span></td>
              <td>{!! $blog->featured ? '<span class="badge text-bg-success">Yes</span>' : 'No' !!}</td>
              <td>{{ optional($blog->created_at)->format('Y-m-d') }}</td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.blogs.ui.show', $blog) }}">View</a>
                <a class="btn btn-sm btn-primary" href="{{ route('admin.blogs.ui.edit', $blog) }}">Edit</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">No blogs found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if(method_exists($blogs,'links'))
      <div class="card-footer">{{ $blogs->links() }}</div>
    @endif
  </div>
@endsection
