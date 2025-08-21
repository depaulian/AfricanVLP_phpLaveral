<!-- API Key Generation Modal -->
<div class="modal fade" id="apiKeyModal" tabindex="-1" aria-labelledby="apiKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="apiKeyModalTitle">Generate API Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="apiKeyForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="apiKeyName" class="form-label">API Key Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="apiKeyName" name="name" required
                               placeholder="e.g., Mobile App Integration">
                        <div class="form-text">A descriptive name to identify this API key</div>
                    </div>

                    <div class="mb-3">
                        <label for="apiKeyDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="apiKeyDescription" name="description" rows="3"
                                  placeholder="Optional description of what this API key will be used for"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Permissions <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Volunteering</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="volunteering:read" id="perm_volunteering_read" name="permissions[]">
                                            <label class="form-check-label" for="perm_volunteering_read">
                                                Read Opportunities
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="volunteering:write" id="perm_volunteering_write" name="permissions[]">
                                            <label class="form-check-label" for="perm_volunteering_write">
                                                Create/Update Opportunities
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="applications:read" id="perm_applications_read" name="permissions[]">
                                            <label class="form-check-label" for="perm_applications_read">
                                                Read Applications
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="applications:write" id="perm_applications_write" name="permissions[]">
                                            <label class="form-check-label" for="perm_applications_write">
                                                Manage Applications
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="time-logs:read" id="perm_time_logs_read" name="permissions[]">
                                            <label class="form-check-label" for="perm_time_logs_read">
                                                Read Time Logs
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="time-logs:write" id="perm_time_logs_write" name="permissions[]">
                                            <label class="form-check-label" for="perm_time_logs_write">
                                                Manage Time Logs
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Integration</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="webhooks:read" id="perm_webhooks_read" name="permissions[]">
                                            <label class="form-check-label" for="perm_webhooks_read">
                                                Read Webhooks
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="webhooks:write" id="perm_webhooks_write" name="permissions[]">
                                            <label class="form-check-label" for="perm_webhooks_write">
                                                Manage Webhooks
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="analytics:read" id="perm_analytics_read" name="permissions[]">
                                            <label class="form-check-label" for="perm_analytics_read">
                                                Read Analytics
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="exports:read" id="perm_exports_read" name="permissions[]">
                                            <label class="form-check-label" for="perm_exports_read">
                                                Read Exports
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="exports:write" id="perm_exports_write" name="permissions[]">
                                            <label class="form-check-label" for="perm_exports_write">
                                                Create Exports
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="apiKeyRateLimit" class="form-label">Rate Limit (requests/hour)</label>
                                <select class="form-select" id="apiKeyRateLimit" name="rate_limit">
                                    <option value="100">100 requests/hour</option>
                                    <option value="500" selected>500 requests/hour</option>
                                    <option value="1000">1,000 requests/hour</option>
                                    <option value="5000">5,000 requests/hour</option>
                                    <option value="10000">10,000 requests/hour</option>
                                    <option value="0">Unlimited</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="apiKeyExpiry" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="apiKeyExpiry" name="expires_at"
                                       min="{{ date('Y-m-d') }}">
                                <div class="form-text">Leave empty for no expiration</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="apiKeyIpWhitelist" class="form-label">IP Whitelist</label>
                        <textarea class="form-control" id="apiKeyIpWhitelist" name="ip_whitelist" rows="3"
                                  placeholder="Enter IP addresses or CIDR blocks, one per line (optional)"></textarea>
                        <div class="form-text">Leave empty to allow access from any IP address</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="apiKeyActive" name="active" checked>
                            <label class="form-check-label" for="apiKeyActive">
                                Active
                            </label>
                        </div>
                    </div>

                    <!-- Generated API Key Display -->
                    <div id="generatedApiKeySection" class="d-none">
                        <hr>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle"></i> API Key Generated Successfully!</h6>
                            <p class="mb-2">Your new API key has been generated. Please copy it now as it won't be shown again.</p>
                            
                            <div class="mb-3">
                                <label class="form-label">API Key:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="generatedApiKey" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyApiKey()">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Usage Example:</label>
                                <pre class="bg-light p-2 rounded"><code>curl -H "Authorization: Bearer <span id="apiKeyExample"></span>" \
     -H "Content-Type: application/json" \
     https://your-domain.com/api/v1/volunteering/opportunities</code></pre>
                            </div>

                            <div class="alert alert-warning">
                                <small><i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Store this API key securely. It provides access to your volunteering data according to the permissions you've granted.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="generateApiKeyBtn">
                        <i class="fas fa-key"></i> Generate API Key
                    </button>
                    <button type="button" class="btn btn-success d-none" id="closeApiKeyBtn" data-bs-dismiss="modal">
                        <i class="fas fa-check"></i> Done
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#apiKeyForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    // Convert form data to object
    for (let [key, value] of formData.entries()) {
        if (key === 'permissions[]') {
            if (!data.permissions) data.permissions = [];
            data.permissions.push(value);
        } else if (key === 'ip_whitelist' && value) {
            data[key] = value.split('\n').filter(ip => ip.trim()).map(ip => ip.trim());
        } else {
            data[key] = value;
        }
    }
    
    // Convert checkboxes
    data.active = $('#apiKeyActive').is(':checked');
    
    // Validate permissions
    if (!data.permissions || data.permissions.length === 0) {
        showNotification('Please select at least one permission', 'warning');
        return;
    }
    
    $('#generateApiKeyBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
    
    $.ajax({
        url: '/api/v1/api-keys',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            // Show the generated API key
            $('#generatedApiKey').val(response.data.key);
            $('#apiKeyExample').text(response.data.key);
            $('#generatedApiKeySection').removeClass('d-none');
            
            // Hide generate button, show done button
            $('#generateApiKeyBtn').addClass('d-none');
            $('#closeApiKeyBtn').removeClass('d-none');
            
            showNotification('API key generated successfully', 'success');
            
            // Reload API keys list if function exists
            if (typeof loadApiKeys === 'function') {
                loadApiKeys();
            }
        } else {
            showNotification(response.message || 'Failed to generate API key', 'error');
            $('#generateApiKeyBtn').prop('disabled', false).html('<i class="fas fa-key"></i> Generate API Key');
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
            showNotification('Failed to generate API key', 'error');
        }
        $('#generateApiKeyBtn').prop('disabled', false).html('<i class="fas fa-key"></i> Generate API Key');
    });
});

// Reset modal when closed
$('#apiKeyModal').on('hidden.bs.modal', function() {
    $('#apiKeyForm')[0].reset();
    $('#generatedApiKeySection').addClass('d-none');
    $('#generateApiKeyBtn').removeClass('d-none').prop('disabled', false).html('<i class="fas fa-key"></i> Generate API Key');
    $('#closeApiKeyBtn').addClass('d-none');
});

function copyApiKey() {
    const apiKeyInput = document.getElementById('generatedApiKey');
    apiKeyInput.select();
    apiKeyInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        showNotification('API key copied to clipboard', 'success');
    } catch (err) {
        showNotification('Failed to copy API key', 'error');
    }
}
</script>