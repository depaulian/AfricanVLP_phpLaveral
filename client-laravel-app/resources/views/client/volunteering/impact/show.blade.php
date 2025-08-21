@extends('layouts.client')

@section('title', 'Impact Record Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <a href="{{ route('client.volunteering.impact.records') }}" 
                   class="text-gray-600 hover:text-gray-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Impact Record Details</h1>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex space-x-3">
                @if(in_array($record->verification_status, ['pending', 'rejected']))
                    <a href="{{ route('client.volunteering.impact.edit', $record) }}" 
                       class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Record
                    </a>
                @endif
                
                <button onclick="window.print()" 
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    <i class="fas fa-print mr-2"></i>
                    Print
                </button>
            </div>
        </div>
    </div>

    <!-- Impact Record Card -->
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-8">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold mb-2">{{ $record->impactMetric->name }}</h2>
                    <p class="text-blue-100">{{ $record->organization->name }}</p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold">{{ $record->formatted_value }}</div>
                    <div class="text-blue-100">{{ $record->impact_date->format('M j, Y') }}</div>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Impact Details</h3>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <span class="w-24 text-sm font-medium text-gray-600">Metric:</span>
                                <div class="flex items-center">
                                    <span style="color: {{ $record->impactMetric->color }}" class="mr-2">
                                        {!! $record->impactMetric->getIconHtml() !!}
                                    </span>
                                    <span class="text-gray-900">{{ $record->impactMetric->name }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="w-24 text-sm font-medium text-gray-600">Category:</span>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">
                                    {{ $record->impactMetric->category_display }}
                                </span>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="w-24 text-sm font-medium text-gray-600">Value:</span>
                                <span class="text-lg font-semibold text-gray-900">{{ $record->formatted_value }}</span>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="w-24 text-sm font-medium text-gray-600">Date:</span>
                                <span class="text-gray-900">{{ $record->impact_date->format('F j, Y') }}</span>
                                <span class="text-gray-500 ml-2">({{ $record->time_since_impact }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment Information -->
                    @if($record->assignment)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Assignment</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-2">{{ $record->assignment->opportunity->title }}</h4>
                                <p class="text-sm text-gray-600 mb-2">{{ $record->assignment->opportunity->description }}</p>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-calendar mr-2"></i>
                                    {{ $record->assignment->start_date->format('M j, Y') }} - 
                                    {{ $record->assignment->end_date ? $record->assignment->end_date->format('M j, Y') : 'Ongoing' }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Description -->
                    @if($record->description)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Description</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-700 leading-relaxed">{{ $record->description }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Verification Status -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Verification Status</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-gray-600">Status:</span>
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full {{ $record->verification_badge_class }}">
                                    {{ $record->verification_status_display }}
                                </span>
                            </div>
                            
                            @if($record->verified_at)
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium text-gray-600">Verified:</span>
                                    <span class="text-sm text-gray-900">{{ $record->verified_at->format('M j, Y H:i') }}</span>
                                </div>
                            @endif
                            
                            @if($record->verifier)
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium text-gray-600">Verified by:</span>
                                    <span class="text-sm text-gray-900">{{ $record->verifier->full_name }}</span>
                                </div>
                            @endif
                            
                            @if($record->verification_notes)
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Notes:</span>
                                    <p class="text-sm text-gray-700 mt-1">{{ $record->verification_notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Attachments -->
                    @if($record->hasAttachments())
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Supporting Documents</h3>
                            <div class="space-y-3">
                                @foreach($record->getAttachments() as $attachment)
                                    <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                                        <div class="flex items-center">
                                            <i class="fas fa-file text-gray-400 mr-3"></i>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $attachment['original_name'] ?? 'Attachment' }}</p>
                                                <p class="text-xs text-gray-500">{{ $attachment['mime_type'] ?? 'Unknown type' }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ asset('storage/' . $attachment['path']) }}" 
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Record Metadata -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Record Information</h3>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600">Created:</span>
                                <span class="text-sm text-gray-900">{{ $record->created_at->format('M j, Y H:i') }}</span>
                            </div>
                            
                            @if($record->updated_at != $record->created_at)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Last Updated:</span>
                                    <span class="text-sm text-gray-900">{{ $record->updated_at->format('M j, Y H:i') }}</span>
                                </div>
                            @endif
                            
                            @if($record->is_featured)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">Featured:</span>
                                    <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                        <i class="fas fa-star mr-1"></i>
                                        Yes
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Impact Stories -->
    @php
        $relatedStories = $record->getRelatedStories();
    @endphp
    @if($relatedStories->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Impact Stories</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($relatedStories as $story)
                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                        <h4 class="font-medium text-gray-900 mb-2">{{ $story->title }}</h4>
                        <p class="text-sm text-gray-600 mb-3">{{ $story->excerpt }}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">{{ $story->story_date->format('M j, Y') }}</span>
                            <a href="{{ route('client.volunteering.impact.story', $story) }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm">
                                Read Story →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    .container {
        max-width: none !important;
        padding: 0 !important;
    }
    
    .bg-gradient-to-r {
        background: #3B82F6 !important;
        -webkit-print-color-adjust: exact;
    }
}
</style>
@endpush