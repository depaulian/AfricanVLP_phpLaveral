@extends('layouts.client')

@section('title', 'Impact Records')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Impact Records</h1>
            <p class="text-gray-600">View and manage your volunteer impact records</p>
        </div>
        <a href="{{ route('client.volunteering.impact.create') }}" 
           class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fas fa-plus mr-2"></i>
            Record New Impact
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                    <option value="disputed" {{ request('status') == 'disputed' ? 'selected' : '' }}>Disputed</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            
            <div>
                <label for="metric_id" class="block text-sm font-medium text-gray-700 mb-1">Metric</label>
                <select id="metric_id" name="metric_id" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Metrics</option>
                    @foreach($metrics as $metric)
                        <option value="{{ $metric->id }}" {{ request('metric_id') == $metric->id ? 'selected' : '' }}>
                            {{ $metric->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="organization_id" class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                <select id="organization_id" name="organization_id" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Organizations</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>
                            {{ $org->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Records List -->
    @if($records->count() > 0)
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Metric
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Organization
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Impact Value
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($records as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $record->impact_date->format('M j, Y') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <span class="inline-flex items-center" style="color: {{ $record->impactMetric->color }}">
                                                {!! $record->impactMetric->getIconHtml() !!}
                                                <span class="ml-1">{{ $record->impactMetric->name }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $record->organization->name }}</div>
                                    @if($record->assignment)
                                        <div class="text-sm text-gray-500">{{ $record->assignment->opportunity->title }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $record->formatted_value }}
                                    </div>
                                    @if($record->description)
                                        <div class="text-sm text-gray-500 max-w-xs truncate">
                                            {{ $record->description }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $record->verification_badge_class }}">
                                        {{ $record->verification_status_display }}
                                    </span>
                                    @if($record->verified_at)
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $record->verified_at->format('M j, Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('client.volunteering.impact.show', $record) }}" 
                                           class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(in_array($record->verification_status, ['pending', 'rejected']))
                                            <a href="{{ route('client.volunteering.impact.edit', $record) }}" 
                                               class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if($record->hasAttachments())
                                            <span class="text-gray-400" title="Has attachments">
                                                <i class="fas fa-paperclip"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $records->withQueryString()->links() }}
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
            <i class="fas fa-chart-line text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Impact Records Found</h3>
            <p class="text-gray-500 mb-6">
                @if(request()->hasAny(['status', 'metric_id', 'organization_id', 'start_date', 'end_date']))
                    No records match your current filters. Try adjusting your search criteria.
                @else
                    You haven't recorded any impact yet. Start by recording your first impact!
                @endif
            </p>
            <div class="flex justify-center space-x-4">
                <a href="{{ route('client.volunteering.impact.create') }}" 
                   class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i>
                    Record Impact
                </a>
                @if(request()->hasAny(['status', 'metric_id', 'organization_id', 'start_date', 'end_date']))
                    <a href="{{ route('client.volunteering.impact.records') }}" 
                       class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Clear Filters
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filters change (with debounce)
    const filterForm = document.querySelector('form');
    const filterInputs = filterForm.querySelectorAll('select, input[type="date"]');
    let submitTimeout;
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            clearTimeout(submitTimeout);
            submitTimeout = setTimeout(() => {
                filterForm.submit();
            }, 300);
        });
    });
});
</script>
@endpush