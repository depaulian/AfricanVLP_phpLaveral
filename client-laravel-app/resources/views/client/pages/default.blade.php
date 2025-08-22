@extends('layouts.app')

@section('title', $page->meta_title ?? $page->title)

@push('styles')
<style>
    .page-hero {
        background-size: cover;
        background-position: center;
    }
</style>
@endpush

@section('content')
    <div class="mb-6">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
            </ol>
        </nav>

        <h1 class="text-3xl font-bold text-gray-800">{{ $page->title }}</h1>
        @if(!empty($page->meta_description))
            <p class="text-gray-600 mt-1">{{ $page->meta_description }}</p>
        @endif
    </div>

    @if(!empty($sliders) && count($sliders))
        <div id="pageCarousel" class="carousel slide mb-6" data-bs-ride="carousel">
            <div class="carousel-inner rounded-md overflow-hidden">
                @foreach($sliders as $index => $slider)
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        @php $img = $slider->getOptimizedImageUrl(1200, 400) ?? $slider->image_url; @endphp
                        @if($img)
                            <img src="{{ $img }}" class="d-block w-100" alt="{{ $slider->title }}">
                        @endif
                        @if($slider->title || $slider->subtitle || $slider->description)
                            <div class="carousel-caption d-none d-md-block bg-black/40 rounded p-3">
                                @if($slider->title)
                                    <h5 class="text-white">{{ $slider->title }}</h5>
                                @endif
                                @if($slider->subtitle)
                                    <p class="text-gray-200">{{ $slider->subtitle }}</p>
                                @endif
                                @if($slider->description)
                                    <p class="text-gray-100">{{ $slider->description }}</p>
                                @endif
                                @if($slider->hasLink())
                                    <a href="{{ $slider->link_url }}" target="{{ $slider->getLinkTarget() }}" class="btn btn-primary btn-sm mt-2">{{ $slider->link_text ?? 'Learn more' }}</a>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#pageCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#pageCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    @endif

    @if(!empty($page->content))
        <div class="prose max-w-none mb-8">
            {!! $page->content !!}
        </div>
    @endif

    @if(!empty($sections) && count($sections))
        <div class="space-y-8">
            @foreach($sections as $section)
                <section class="bg-white shadow-sm rounded-md p-4">
                    @if($section->title)
                        <h2 class="text-2xl font-semibold text-gray-800">{{ $section->title }}</h2>
                    @endif
                    @if($section->subtitle)
                        <p class="text-gray-600 mt-1">{{ $section->subtitle }}</p>
                    @endif

                    @if($section->image_url)
                        <div class="my-3">
                            <img src="{{ $section->getOptimizedImageUrl(1200, 600) ?? $section->image_url }}" alt="{{ $section->title }}" class="w-100 rounded" />
                        </div>
                    @endif

                    @if($section->content)
                        <div class="prose max-w-none mt-2">
                            {!! $section->renderContent() !!}
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
@endsection
