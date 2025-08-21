@extends('layouts.client')

@section('title', 'Record New Impact')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <a href="{{ route('client.volunteering.impact.records') }}" 
               class="text-gray-600 hover:text-gray-800 mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Record New Impact</h1>
        </div>
        <p class="text-gray-600">Document the positive change you've made through your volunteer work</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <form method="POST" action="{{ route('client.volunteering.impact.store') }}" enctype="multipart/form-data">
            @csrf
            
            <!-- Assignment Selection -->
            <div class="mb-6">
                <label for="assignment_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Related Assignment (Optional)
                </label>
                <select id="assignment_id" name="assignment_id" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select an assignment (optional)</option>
                    @foreach($assignments as $assignment)
                        <option value="{{ $assignment->id }}" 
                                data-organization="{{ $assignment->organization->id }}"
                                {{ old('assignment_id', $selectedAssignment?->id) == $assignment->id ? 'selected' : '' }}>
                            {{ $assignment->opportunity->title }} - {{ $assignment->organization->name }}
                        </option>
                    @endforeach
                </select>
                @error('assignment_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Link this impact to a specific volunteer assignment if applicable
                </p>
            </div>

            <!-- Organization Selection -->
            <div class="mb-6">
                <label for="organization_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Organization <span class="text-red-500">*</span>
                </label>
                <select id="organization_id" name="organization_id" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select organization</option>
                    @foreach($assignments->pluck('organization')->unique('id') as $org)
                        <option value="{{ $org->id }}" {{ old('organization_id', $selectedAssignment?->organization->id) == $org->id ? 'selected' : '' }}>
                            {{ $org->name }}
                        </option>
                    @endforeach
                </select>
                @error('organization_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Impact Metric -->
            <div class="mb-6">
                <label for="impact_metric_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Impact Metric <span class="text-red-500">*</span>
                </label>
                <select id="impact_metric_id" name="impact_metric_id" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select impact metric</option>
                    @foreach($metrics->groupBy('category') as $category => $categoryMetrics)
                        <optgroup label="{{ ucfirst($category) }} Impact">
                            @foreach($categoryMetrics as $metric)
                                <option value="{{ $metric->id }}" 
                                        data-unit="{{ $metric->unit }}"
                                        data-type="{{ $metric->type }}"
                                        {{ old('impact_metric_id') == $metric->id ? 'selected' : '' }}>
                                    {{ $metric->name }} ({{ $metric->getFormattedUnit() }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('impact_metric_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Impact Value -->
            <div class="mb-6">
                <label for="value" class="block text-sm font-medium text-gray-700 mb-2">
                    Impact Value <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="value" name="value" step="0.01" min="0" 
                           value="{{ old('value') }}" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-20">
                    <div id="unit-display" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm text-gray-500">
                        <span id="unit-text">units</span>
                    </div>
                </div>
                @error('value')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Enter the measurable impact value (e.g., number of people helped, hours contributed, etc.)
                </p>
            </div>

            <!-- Impact Date -->
            <div class="mb-6">
                <label for="impact_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Impact Date <span class="text-red-500">*</span>
                </label>
                <input type="date" id="impact_date" name="impact_date" 
                       value="{{ old('impact_date', now()->toDateString()) }}" 
                       max="{{ now()->toDateString() }}" required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('impact_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    When did this impact occur?
                </p>
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description (Optional)
                </label>
                <textarea id="description" name="description" rows="4" 
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          placeholder="Provide additional context about this impact...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Share details about how this impact was achieved and its significance
                </p>
            </div>

            <!-- Attachments -->
            <div class="mb-6">
                <label for="attachments" class="block text-sm font-medium text-gray-700 mb-2">
                    Supporting Documents/Photos (Optional)
                </label>
                <input type="file" id="attachments" name="attachments[]" multiple 
                       accept="image/*,.pdf,.doc,.docx"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('attachments.*')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Upload photos, documents, or other evidence of your impact (max 10MB per file)
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4">
                <a href="{{ route('client.volunteering.impact.records') }}" 
                   class="px-6 py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-save mr-2"></i>
                    Record Impact
                </button>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-4">
            <i class="fas fa-info-circle mr-2"></i>
            Tips for Recording Impact
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
            <div>
                <h4 class="font-medium mb-2">Be Specific</h4>
                <p>Use concrete numbers and measurable outcomes. Instead of "helped many people," record "helped 25 people."</p>
            </div>
            <div>
                <h4 class="font-medium mb-2">Provide Context</h4>
                <p>Include details about how the impact was achieved and why it matters to the community.</p>
            </div>
            <div>
                <h4 class="font-medium mb-2">Include Evidence</h4>
                <p>Upload photos, certificates, or documents that support your impact claims.</p>
            </div>
            <div>
                <h4 class="font-medium mb-2">Be Honest</h4>
                <p>All impact records are subject to verification. Only record impacts you can substantiate.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const assignmentSelect = document.getElementById('assignment_id');
    const organizationSelect = document.getElementById('organization_id');
    const metricSelect = document.getElementById('impact_metric_id');
    const unitText = document.getElementById('unit-text');
    const valueInput = document.getElementById('value');

    // Update organization when assignment changes
    assignmentSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const organizationId = selectedOption.dataset.organization;
            organizationSelect.value = organizationId;
        }
    });

    // Update unit display when metric changes
    metricSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const unit = selectedOption.dataset.unit;
            const type = selectedOption.dataset.type;
            
            // Update unit display
            let unitDisplay = unit;
            if (type === 'percentage') {
                unitDisplay = '%';
                valueInput.max = '100';
            } else {
                valueInput.removeAttribute('max');
            }
            
            unitText.textContent = unitDisplay;
        } else {
            unitText.textContent = 'units';
            valueInput.removeAttribute('max');
        }
    });

    // Trigger initial update if metric is pre-selected
    if (metricSelect.value) {
        metricSelect.dispatchEvent(new Event('change'));
    }

    // File upload preview
    const fileInput = document.getElementById('attachments');
    fileInput.addEventListener('change', function() {
        const files = Array.from(this.files);
        const fileCount = files.length;
        
        if (fileCount > 0) {
            const fileNames = files.map(file => file.name).join(', ');
            console.log(`Selected ${fileCount} file(s): ${fileNames}`);
        }
    });

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const value = parseFloat(valueInput.value);
        const metricType = metricSelect.options[metricSelect.selectedIndex]?.dataset.type;
        
        if (metricType === 'percentage' && (value < 0 || value > 100)) {
            e.preventDefault();
            alert('Percentage values must be between 0 and 100.');
            valueInput.focus();
            return;
        }
        
        if (value < 0) {
            e.preventDefault();
            alert('Impact value cannot be negative.');
            valueInput.focus();
            return;
        }
    });
});
</script>
@endpush