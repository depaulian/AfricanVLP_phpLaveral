@extends('layouts.app')
@section('title', 'Type: ' . $resourceType->name)
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Type: {{ $resourceType->name }}</h1>
  @include('client.resources.partials.list',['resources'=>$resources])
</div>
@endsection
