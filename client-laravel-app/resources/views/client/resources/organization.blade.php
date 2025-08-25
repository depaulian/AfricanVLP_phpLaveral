@extends('layouts.app')
@section('title', $organization->name . ' • Resources')
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Organization: {{ $organization->name }}</h1>
  @include('client.resources.partials.list',['resources'=>$resources])
</div>
@endsection
