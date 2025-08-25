@extends('layouts.app')
@section('title', $category->name . ' • Blogs')
@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Category: {{ $category->name }}</h1>
    @include('client.blog.partials.blog-list', ['blogs' => $blogs])
</div>
@endsection
