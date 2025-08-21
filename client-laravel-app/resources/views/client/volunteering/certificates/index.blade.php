@extends('layouts.client')

@section('title', 'My Certificates')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">My Certificates</h1>
                <p class="text-gray-600">Your volunteer service certificates and recognitions</p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <a href="{{ route('client.volunteering.portfolio') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-user mr-2"></i>
                    View Portfolio
                </a>
                @if($certificates->count() > 0)
                    <a href="{{ route('client.volunteering.portfolio.export') }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Export All
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Certificate Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-certificate text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Certificates</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_certificates'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-clock text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Hours Certified</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_hours_certified'], 1) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-building text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Organizations</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['certificates_by_organization']->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-calendar text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">This Month</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['recent_certificates'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificates Grid -->
    @if($certificates->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($certificates as $certificate)
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-shadow">
                    <!-- Certificate Header -->
                    <div class="p-6 border-b bg-gradient-to-r from-blue-50 to-indigo-50">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-certificate text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $certificate->title }}</h3>
                                    <p class="text-sm text-gray-600">{{ $certificate->organization->name }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $certificate->badge_color }}">
                                {{ $certificate->type_display }}
                            </span>
                        </div>
                        
                        <p class="text-sm text-gray-700">{{ Str::limit($certificate->description, 100) }}</p>
                    </div>

                    <!-- Certificate Details -->
                    <div class="p-6">
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Certificate Number:</span>
                                <span class="font-medium text-gray-900">{{ $certificate->certificate_number }}</span>
                            </div>
                            
                            @if($certificate->hours_completed)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Hours Completed:</span>
                                    <span class="font-medium text-gray-900">{{ $certificate->formatted_hours }}</span>
                                </div>
                            @endif
                            
                            @if($certificate->duration)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Duration:</span>
                                    <span class="font-medium text-gray-900">{{ $certificate->duration }}</span>
                                </div>
                            @endif
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Issued:</span>
                                <span class="font-medium text-gray-900">{{ $certificate->issued_at->format('M j, Y') }}</span>
                            </div>
                        </div>

                        <!-- Certificate Actions -->
                        <div class="flex space-x-2">
                            <a href="{{ route('client.certificates.show', $certificate) }}" 
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-eye mr-2"></i>
                                View
                            </a>
                            
                            <a href="{{ route('client.certificates.download', $certificate) }}" 
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>
                                Download
                            </a>
                            
                            <button class="toggle-certificate-public-btn px-3 py-2 text-gray-600 hover:text-blue-600 transition-colors"
                                    data-certificate-id="{{ $certificate->id }}"
                                    data-public="{{ $certificate->is_public ? 'true' : 'false' }}"
                                    title="{{ $certificate->is_public ? 'Make private' : 'Make public' }}">
                                <i class="fas {{ $certificate->is_public ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Certificate Footer -->
                    @if($certificate->isRecentlyIssued())
                        <div class="px-6 py-3 bg-green-50 border-t">
                            <div class="flex items-center text-sm text-green-700">
                                <i class="fas fa-sparkles mr-2"></i>
                                Recently issued
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $certificates->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-certificate text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No certificates yet</h3>
            <p class="text-gray-500 mb-6">Complete volunteer assignments to earn certificates!</p>
            <a href="{{ route('client.volunteering.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search mr-2"></i>
                Find Opportunities
            </a>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle certificate public status
    document.querySelectorAll('.toggle-certificate-public-btn').forEach(button => {
        button.addEventListener('click', function() {
            const certificateId = this.dataset.certificateId;
            const isPublic = this.dataset.public === 'true';
            
            fetch(`/client/volunteering/certificates/${certificateId}/toggle-public`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const icon = this.querySelector('i');
                    if (data.is_public) {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                        this.dataset.public = 'true';
                        this.title = 'Make private';
                    } else {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                        this.dataset.public = 'false';
                        this.title = 'Make public';
                    }
                    
                    // Show notification
                    showNotification(data.message, 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        });
    });
    
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});
</script>
@endpush
@endsection