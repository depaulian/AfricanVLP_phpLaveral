<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title', 'Admin')</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background: #f7f7f9; }
    .navbar-brand { font-weight: 600; }
    .container-narrow { max-width: 1200px; }
    .table th { white-space: nowrap; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid container-narrow">
    <a class="navbar-brand" href="/admin">Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.blogs.ui.index') }}">Blogs</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.opportunities.ui.index') }}">Opportunities</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.events.ui.index') }}">Events</a></li>
      </ul>
    </div>
  </div>
</nav>
<main class="container container-narrow py-4">
  <h1 class="h3 mb-3">@yield('page_title')</h1>
  @includeWhen(session('status'), 'admin.partials.status')
  @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
