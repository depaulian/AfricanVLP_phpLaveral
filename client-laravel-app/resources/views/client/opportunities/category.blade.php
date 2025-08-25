@extends('layouts.app')
@section('title', 'Category: ' . $category->name)
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Category: {{ $category->name }}</h1>
  @include('client.opportunities.partials.list', ['opportunities' => $opportunities])
</div>
@endsection
