@props([
  'type' => session('success') ? 'success' : (session('error') ? 'danger' : (session('warning') ? 'warning' : (session('info') ? 'info' : ''))),
  'message' => session('success') ?? session('error') ?? session('warning') ?? session('info') ?? null,
])
@if($message)
  <div class="alert alert-{{ $type }}" role="alert">
    {{ $message }}
  </div>
@endif
