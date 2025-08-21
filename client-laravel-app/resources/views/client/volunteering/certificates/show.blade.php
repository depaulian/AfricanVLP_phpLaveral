@extends('layouts.client')

@section('title', 'Certificate - ' . $certificate->title)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('client.volunteering.certificates') }}" 
                   class="text-gray-600 hover:text-gray-900 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $certificate->title }}</h1>
                    <p class="text-gray-600">{{ $certificate->organization->name }}</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('client.certificates.download', $certificate) }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Download PDF
                </a>
                <button id="shareCertificate" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-share mr-2"></i>
                    Share
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Certificate Preview -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-lg border overflow-hidden">
                <!-- Certificate Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white p-8 text-center">
                    <div class="mb-4">
                        @if($certificate->organization->logo)
                            <img src="{{ $certificate->organization->logo }}" 
                                 alt="{{ $certificate->organization->name }}" 
                                 class="h-16 mx-auto mb-4">
                        @endif
                        <h2 class="text-2xl font-bold">{{ $certificate->organization->name }}</h2>
                    </div>
                    
                    <div class="border-t border-blue-400 pt-4">
                        <h1 class="text-3xl font-bold mb-2">{{ $certificate->type_display }}</h1>
                        <p class="text-blue-100">This is to certify that</p>
                    </div>
                </div>

                <!-- Certificate Body -->
                <div class="p-8 text-center">
                    <div class="mb-8">
                        <h2 class="text-4xl font-bold text-gray-900 mb-4">{{ $certificate->user->name }}</h2>
                        <div class="w-32 h-1 bg-gradient-to-r from-blue-600 to-indigo-600 mx-auto mb-6"></div>
                    </div>

                    <div class="mb-8">
                        <p class="text-lg text-gray-700 leading-relaxed">{{ $certificate->description }}</p>
                    </div>

                    @if($certificate->hours_completed || $certificate->start_date)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            @if($certificate->hours_completed)
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 mb-1">Hours Completed</p>
                                    <p class="text-2xl font-bold text-blue-600">{{ $certificate->formatted_hours }}</p>
                                </div>
                            @endif
                            
                            @if($certificate->start_date && $certificate->end_date)
                                <div class="text-center">
                                    <p class="text-sm text-gray-600 mb-1">Service Period</p>
                                    <p class="text-lg font-semibold text-gray-900">
                                        {{ $certificate->start_date->format('M j, Y') }} - {{ $certificate->end_date->format('M j, Y') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Signature Section -->
                    <div class="flex justify-between items-end mt-12 pt-8 border-t">
                        <div class="text-center">
                            <div class="w-48 border-b border-gray-300 mb-2"></div>
                            <p class="text-sm text-gray-600">Date Issued</p>
                            <p class="font-semibold">{{ $certificate->issued_at->format('M j, Y') }}</p>
                        </div>
                        
                        @if($certificate->signature_name)
                            <div class="text-center">
                                @if($certificate->signature_image)
                                    <img src="{{ $certificate->signature_image }}" 
                                         alt="Signature" 
                                         class="h-12 mx-auto mb-2">
                                @else
                                    <div class="w-48 border-b border-gray-300 mb-2"></div>
                                @endif
                                <p class="font-semibold">{{ $certificate->signature_name }}</p>
                                <p class="text-sm text-gray-600">{{ $certificate->signature_title }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Certificate Footer -->
                <div class="bg-gray-50 px-8 py-4 border-t">
                    <div class="flex justify-between items-center text-sm text-gray-600">
                        <span>Certificate Number: {{ $certificate->certificate_number }}</span>
                        <span>Verification Code: {{ $certificate->verification_code }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificate Details Sidebar -->
        <div class="space-y-6">
            <!-- Certificate Info -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Certificate Details</h3>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Type</p>
                        <p class="font-medium">{{ $certificate->type_display }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Organization</p>
                        <p class="font-medium">{{ $certificate->organization->name }}</p>
                    </div>
                    
                    @if($certificate->assignment)
                        <div>
                            <p class="text-sm text-gray-600">Assignment</p>
                            <p class="font-medium">{{ $certificate->assignment->opportunity->title }}</p>
                        </div>
                    @endif
                    
                    <div>
                        <p class="text-sm text-gray-600">Issued Date</p>
                        <p class="font-medium">{{ $certificate->issued_at->format('F j, Y') }}</p>
                    </div>
                    
                    @if($certificate->hours_completed)
                        <div>
                            <p class="text-sm text-gray-600">Hours Certified</p>
                            <p class="font-medium">{{ $certificate->formatted_hours }}</p>
                        </div>
                    @endif
                    
                    @if($certificate->duration)
                        <div>
                            <p class="text-sm text-gray-600">Service Duration</p>
                            <p class="font-medium">{{ $certificate->duration }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Verification -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Verification</h3>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Certificate Number</p>
                        <p class="font-mono text-sm bg-gray-100 p-2 rounded">{{ $certificate->certificate_number }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Verification Code</p>
                        <p class="font-mono text-sm bg-gray-100 p-2 rounded">{{ $certificate->verification_code }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Verification URL</p>
                        <a href="{{ $certificate->verification_url }}" 
                           target="_blank"
                           class="text-blue-600 hover:text-blue-800 text-sm break-all">
                            {{ $certificate->verification_url }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                
                <div class="space-y-3">
                    <a href="{{ route('client.certificates.download', $certificate) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Download PDF
                    </a>
                    
                    <button id="copyVerificationLink" 
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-copy mr-2"></i>
                        Copy Verification Link
                    </button>
                    
                    <button class="toggle-certificate-public-btn w-full inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                            data-certificate-id="{{ $certificate->id }}"
                            data-public="{{ $certificate->is_public ? 'true' : 'false' }}">
                        <i class="fas {{ $certificate->is_public ? 'fa-eye-slash' : 'fa-eye' }} mr-2"></i>
                        {{ $certificate->is_public ? 'Make Private' : 'Make Public' }}
                    </button>
                </div>
            </div>

            <!-- Status -->
            @if($certificate->isRecentlyIssued())
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-sparkles text-green-600 mr-2"></i>
                        <span class="text-green-800 font-medium">Recently Issued</span>
                    </div>
                    <p class="text-green-700 text-sm mt-1">This certificate was issued {{ $certificate->issued_at->diffForHumans() }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="shareModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Share Certificate</h3>
                    <button id="closeShareModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="text-center mb-4">
                        <h4 class="font-medium text-gray-900">{{ $certificate->title }}</h4>
                        <p class="text-sm text-gray-600 mt-1">{{ $certificate->organization->name }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="shareToFacebook()" 
                                class="flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fab fa-facebook-f mr-2"></i>
                            Facebook
                        </button>
                        
                        <button onclick="shareToTwitter()" 
                                class="flex items-center justify-center px-4 py-2 bg-blue-400 text-white rounded-lg hover:bg-blue-500 transition-colors">
                            <i class="fab fa-twitter mr-2"></i>
                            Twitter
                        </button>
                        
                        <button onclick="shareToLinkedIn()" 
                                class="flex items-center justify-center px-4 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 transition-colors">
                            <i class="fab fa-linkedin-in mr-2"></i>
                            LinkedIn
                        </button>
                        
                        <button onclick="copyShareLink()" 
                                class="flex items-center justify-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-copy mr-2"></i>
                            Copy Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const shareModal = document.getElementById('shareModal');
    const shareCertificateBtn = document.getElementById('shareCertificate');
    const closeShareModal = document.getElementById('closeShareModal');
    const copyVerificationBtn = document.getElementById('copyVerificationLink');
    
    // Share certificate
    shareCertificateBtn.addEventListener('click', function() {
        shareModal.classList.remove('hidden');
    });
    
    // Close share modal
    closeShareModal.addEventListener('click', function() {
        shareModal.classList.add('hidden');
    });
    
    shareModal.addEventListener('click', function(e) {
        if (e.target === shareModal) {
            shareModal.classList.add('hidden');
        }
    });
    
    // Copy verification link
    copyVerificationBtn.addEventListener('click', function() {
        const verificationUrl = '{{ $certificate->verification_url }}';
        navigator.clipboard.writeText(verificationUrl).then(function() {
            showNotification('Verification link copied to clipboard!', 'success');
        });
    });
    
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
                    const text = this.querySelector('span') || this;
                    
                    if (data.is_public) {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                        text.textContent = text.textContent.replace('Make Public', 'Make Private');
                        this.dataset.public = 'true';
                    } else {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                        text.textContent = text.textContent.replace('Make Private', 'Make Public');
                        this.dataset.public = 'false';
                    }
                    
                    showNotification(data.message, 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        });
    });
    
    // Social sharing functions
    window.shareToFacebook = function() {
        const url = '{{ $certificate->share_url }}';
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank', 'width=600,height=400');
    };
    
    window.shareToTwitter = function() {
        const url = '{{ $certificate->share_url }}';
        const text = `I earned a certificate from {{ $certificate->organization->name }}! #volunteer #certificate #community`;
        window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`, '_blank', 'width=600,height=400');
    };
    
    window.shareToLinkedIn = function() {
        const url = '{{ $certificate->share_url }}';
        window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`, '_blank', 'width=600,height=400');
    };
    
    window.copyShareLink = function() {
        const url = '{{ $certificate->share_url }}';
        navigator.clipboard.writeText(url).then(function() {
            showNotification('Share link copied to clipboard!', 'success');
            shareModal.classList.add('hidden');
        });
    };
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});
</script>
@endpush
@endsection