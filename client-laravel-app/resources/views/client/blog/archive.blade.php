@extends('layouts.app')
@section('title', 'Archive')
@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Archive @if($month) for {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }} @else {{ $year }} @endif</h1>
    @include('client.blog.partials.blog-list', ['blogs' => $blogs])
</div>
@endsection
