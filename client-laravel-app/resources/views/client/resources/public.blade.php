@extends('layouts.app')
@section('title','Public Resources')
@section('content')
<div class="container py-4">
  <h1 class="h4 mb-3">Public Resources</h1>
  @include('client.resources.partials.list',['resources'=>$resources])
</div>
@endsection
