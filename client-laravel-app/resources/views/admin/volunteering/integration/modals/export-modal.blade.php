<!-- Data Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalTitle">Create Data Export</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="exportForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="exportType" class="form-label">Export Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="exportType" name="export_type" required>
                            <option value="">Select export type...</option>
                            <option value="volunteers">Volunteer Data</option>
                            <option value="opportunities">Volunteering Opportunities</option>
                            <option value="applications">Volunteer Applications</option>
                            <option value="time_logs">Time Logs</option>
                            <option value="analytics">Analytics Data</option>
                            <option value="feedback">Feedback & Reviews</option>
                            <option value="community">Community Data</option>
                            <option value="custom">Custom Export</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="exportFormat" class="form-label">Export Format <span class="text-danger">*</span></label>
                        <select class="form-select" id="exportFormat" name="format" required>
                            <option value="csv">CSV (Comma Separated Values)</option>
                            <option value="xlsx">Excel (XLSX)</option>
                            <option value="json">JSON</option>
                            <option value="pdf">PDF Report</option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="exportDateFrom" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="exportDateFrom" name="date_from">
                            </div>
                            <div class="col-md-6">
                                <label for="exportDateTo" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="exportDateTo" name="date_to">
                            </div>
                        </div>
                        <div class="form-text">Leave empty to export all data</div>
                    </div>

                    <!-- Volunteer Data Specific Options -->
                    <div id="volunteerOptions" class="export-options d-none">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Volunteer Data Options</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="basic_info" id="vol_basic_info" name="volunteer_fields[]" checked>
                                            <label class="form-check-label" for="vol_basic_info">
                                                Basic Information
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="contact_info" id="vol_contact_info" name="volunteer_fields[]" checked>
                                            <label class="form-check-label" for="vol_contact_info">
                                                Contact Information
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="skills" id="vol_skills" name="volunteer_fields[]">
                                            <label class="form-check-label" for="vol_skills">
                                                Skills & Interests
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="availability" id="vol_availability" name="volunteer_fields[]">
                                            <label class="form-check-label" for="vol_availability">
                                                Availability
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="applications" id="vol_applications" name="volunteer_fields[]">
                                            <label class="form-check-label" for="vol_applications">
                                                Application History
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="time_logs" id="vol_time_logs" name="volunteer_fields[]">
                                            <label class="form-check-label" for="vol_time_logs">
                                                Time Logs
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="achievements" id="vol_achievements" name="volunteer_fields[]">
                                            <label class="form-check-label" for="vol_achievements">
                                                Achievements & Awards
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="feedback" id="vol_feedback" name="volunteer_fields[]">
                                            <label class="form-check-label" for="vol_feedback">
                                                Feedback & Reviews
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opportunities Data Specific Options -->
                    <div id="opportunityOptions" class="export-options d-none">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Opportunity Data Options</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="basic_info" id="opp_basic_info" name="opportunity_fields[]" checked>
                                            <label class="form-check-label" for="opp_basic_info">
                                                Basic Information
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="requirements" id="opp_requirements" name="opportunity_fields[]">
                                            <label class="form-check-label" for="opp_requirements">
                                                Requirements
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="schedule" id="opp_schedule" name="opportunity_fields[]">
                                            <label class="form-check-label" for="opp_schedule">
                                                Schedule & Duration
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="applications" id="opp_applications" name="opportunity_fields[]">
                                            <label class="form-check-label" for="opp_applications">
                                                Application Statistics
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="assignments" id="opp_assignments" name="opportunity_fields[]">
                                            <label class="form-check-label" for="opp_assignments">
                                                Volunteer Assignments
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="impact" id="opp_impact" name="opportunity_fields[]">
                                            <label class="form-check-label" for="opp_impact">
                                                Impact Metrics
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Data Specific Options -->
                    <div id="analyticsOptions" class="export-options d-none">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Analytics Data Options</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="volunteer_metrics" id="analytics_volunteer_metrics" name="analytics_fields[]" checked>
                                            <label class="form-check-label" for="analytics_volunteer_metrics">
                                                Volunteer Metrics
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="opportunity_metrics" id="analytics_opportunity_metrics" name="analytics_fields[]" checked>
                                            <label class="form-check-label" for="analytics_opportunity_metrics">
                                                Opportunity Metrics
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="engagement_metrics" id="analytics_engagement_metrics" name="analytics_fields[]">
                                            <label class="form-check-label" for="analytics_engagement_metrics">
                                                Engagement Metrics
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="impact_metrics" id="analytics_impact_metrics" name="analytics_fields[]">
                                            <label class="form-check-label" for="analytics_impact_metrics">
                                                Impact Metrics
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="performance_metrics" id="analytics_performance_metrics" name="analytics_fields[]">
                                            <label class="form-check-label" for="analytics_performance_metrics">
                                                Performance Metrics
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="trends" id="analytics_trends" name="analytics_fields[]">
                                            <label class="form-check-label" for="analytics_trends">
                                                Trend Analysis
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Export Options -->
                    <div id="customOptions" class="export-options d-none">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Custom Export Options</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="customQuery" class="form-label">Custom SQL Query</label>
                                    <textarea class="form-control" id="customQuery" name="custom_query" rows="5"
                                              placeholder="SELECT * FROM volunteering_opportunities WHERE..."></textarea>
                                    <div class="form-text">Advanced users only. Write a custom SQL query to export specific data.</div>
                                </div>
                                <div class="alert alert-warning">
                                    <small><i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Custom queries have access to the database. Only use trusted queries.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Options -->
                    <div class="mb-3">
                        <label for="exportName" class="form-label">Export Name</label>
                        <input type="text" class="form-control" id="exportName" name="name" 
                               placeholder="Optional name for this export">
                        <div class="form-text">If not provided, a name will be generated automatically</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="includeHeaders" name="include_headers" checked>
                            <label class="form-check-label" for="includeHeaders">
                                Include Column Headers
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="compressExport" name="compress">
                            <label class="form-check-label" for="compressExport">
                                Compress Export (ZIP)
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="emailWhenReady" name="email_when_ready">
                            <label class="form-check-label" for="emailWhenReady">
                                Email me when export is ready
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Create Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show/hide export options based on export type
$('#exportType').on('change', function() {
    const exportType = $(this).val();
    
    // Hide all export options
    $('.export-options').addClass('d-none');
    
    // Show relevant options
    switch(exportType) {
        case 'volunteers':
            $('#volunteerOptions').removeClass('d-none');
            break;
        case 'opportunities':
            $('#opportunityOptions').removeClass('d-none');
            break;
        case 'analytics':
            $('#analyticsOptions').removeClass('d-none');
            break;
        case 'custom':
            $('#customOptions').removeClass('d-none');
            break;
    }
});

$('#exportForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    // Convert form data to object
    for (let [key, value] of formData.entries()) {
        if (key.endsWith('[]')) {
            const fieldName = key.replace('[]', '');
            if (!data[fieldName]) data[fieldName] = [];
            data[fieldName].push(value);
        } else {
            data[key] = value;
        }
    }
    
    // Convert checkboxes
    data.include_headers = $('#includeHeaders').is(':checked');
    data.compress = $('#compressExport').is(':checked');
    data.email_when_ready = $('#emailWhenReady').is(':checked');
    
    // Validate required fields
    if (!data.export_type) {
        showNotification('Please select an export type', 'warning');
        return;
    }
    
    if (!data.format) {
        showNotification('Please select an export format', 'warning');
        return;
    }
    
    const submitBtn = $(this).find('button[type="submit"]');
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating Export...');
    
    $.ajax({
        url: '/api/v1/exports',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            $('#exportModal').modal('hide');
            showNotification('Export created successfully. You will be notified when it\'s ready.', 'success');
            
            // Reload exports list if function exists
            if (typeof loadExportHistory === 'function') {
                loadExportHistory();
            }
        } else {
            showNotification(response.message || 'Failed to create export', 'error');
        }
    })
    .fail(function(xhr) {
        const response = xhr.responseJSON;
        if (response && response.errors) {
            let errorMessage = 'Validation errors:\n';
            for (let field in response.errors) {
                errorMessage += `- ${response.errors[field].join(', ')}\n`;
            }
            showNotification(errorMessage, 'error');
        } else {
            showNotification('Failed to create export', 'error');
        }
    })
    .always(function() {
        submitBtn.prop('disabled', false).html('<i class="fas fa-download"></i> Create Export');
    });
});

// Reset modal when closed
$('#exportModal').on('hidden.bs.modal', function() {
    $('#exportForm')[0].reset();
    $('.export-options').addClass('d-none');
});

// Set default dates
$('#exportModal').on('show.bs.modal', function() {
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    $('#exportDateTo').val(today.toISOString().split('T')[0]);
    $('#exportDateFrom').val(thirtyDaysAgo.toISOString().split('T')[0]);
});
</script>