{{-- Volunteering Overview Widget --}}
<div class="volunteering-widget" data-widget="overview">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Volunteering Overview</h5>
            <button class="btn btn-sm btn-outline-primary" onclick="refreshWidget('overview')">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item text-center">
                        <h3 class="text-primary" id="active-opportunities">-</h3>
                        <small class="text-muted">Active Opportunities</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item text-center">
                        <h3 class="text-success" id="active-volunteers">-</h3>
                        <small class="text-muted">Active Volunteers</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item text-center">
                        <h3 class="text-info" id="total-hours">-</h3>
                        <small class="text-muted">Total Hours</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item text-center">
                        <h3 class="text-warning" id="pending-applications">-</h3>
                        <small class="text-muted">Pending Applications</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Applications Widget --}}
<div class="volunteering-widget" data-widget="recent_applications">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recent Applications</h5>
        </div>
        <div class="card-body">
            <div id="recent-applications-list">
                <div class="text-center text-muted py-3">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Upcoming Opportunities Widget --}}
<div class="volunteering-widget" data-widget="upcoming_opportunities">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Upcoming Opportunities</h5>
        </div>
        <div class="card-body">
            <div id="upcoming-opportunities-list">
                <div class="text-center text-muted py-3">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Volunteer Hours Chart Widget --}}
<div class="volunteering-widget" data-widget="volunteer_hours">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Volunteer Hours (Last 30 Days)</h5>
        </div>
        <div class="card-body">
            <canvas id="volunteer-hours-chart" width="400" height="200"></canvas>
        </div>
    </div>
</div>

<script>
// Widget management functions
function refreshWidget(widgetType) {
    const widget = document.querySelector(`[data-widget="${widgetType}"]`);
    if (!widget) return;

    // Show loading state
    const cardBody = widget.querySelector('.card-body');
    const originalContent = cardBody.innerHTML;
    cardBody.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

    // Fetch widget data
    fetch(`/api/v1/volunteering/widgets/${widgetType}`, {
        headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateWidget(widgetType, data.data);
        } else {
            cardBody.innerHTML = originalContent;
            showNotification('Failed to load widget data', 'error');
        }
    })
    .catch(error => {
        cardBody.innerHTML = originalContent;
        showNotification('Error loading widget', 'error');
    });
}

function updateWidget(widgetType, data) {
    switch (widgetType) {
        case 'overview':
            updateOverviewWidget(data);
            break;
        case 'recent_applications':
            updateRecentApplicationsWidget(data);
            break;
        case 'upcoming_opportunities':
            updateUpcomingOpportunitiesWidget(data);
            break;
        case 'volunteer_hours':
            updateVolunteerHoursWidget(data);
            break;
    }
}

function updateOverviewWidget(data) {
    document.getElementById('active-opportunities').textContent = data.active_opportunities || 0;
    document.getElementById('active-volunteers').textContent = data.active_volunteers || 0;
    document.getElementById('total-hours').textContent = data.total_hours || 0;
    document.getElementById('pending-applications').textContent = data.pending_applications || 0;
}

function updateRecentApplicationsWidget(data) {
    const container = document.getElementById('recent-applications-list');
    
    if (!data || data.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3">No recent applications</div>';
        return;
    }

    let html = '';
    data.forEach(application => {
        const statusClass = application.status === 'pending' ? 'warning' : 
                           application.status === 'accepted' ? 'success' : 'secondary';
        
        html += `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <strong>${application.user.name}</strong><br>
                    <small class="text-muted">${application.opportunity.title}</small>
                </div>
                <span class="badge bg-${statusClass}">${application.status}</span>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function updateUpcomingOpportunitiesWidget(data) {
    const container = document.getElementById('upcoming-opportunities-list');
    
    if (!data || data.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3">No upcoming opportunities</div>';
        return;
    }

    let html = '';
    data.forEach(opportunity => {
        const startDate = new Date(opportunity.start_date).toLocaleDateString();
        
        html += `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <strong>${opportunity.title}</strong><br>
                    <small class="text-muted">${opportunity.location} • ${startDate}</small>
                </div>
                <span class="badge bg-primary">${opportunity.volunteers_needed} needed</span>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function updateVolunteerHoursWidget(data) {
    const ctx = document.getElementById('volunteer-hours-chart').getContext('2d');
    
    if (!data || data.length === 0) {
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        ctx.fillText('No data available', ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }

    // Prepare chart data
    const labels = data.map(item => new Date(item.date).toLocaleDateString());
    const hours = data.map(item => item.total_hours);

    // Create or update chart
    if (window.volunteerHoursChart) {
        window.volunteerHoursChart.destroy();
    }

    window.volunteerHoursChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Volunteer Hours',
                data: hours,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Initialize widgets on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load all widgets
    const widgets = document.querySelectorAll('.volunteering-widget');
    widgets.forEach(widget => {
        const widgetType = widget.getAttribute('data-widget');
        refreshWidget(widgetType);
    });

    // Auto-refresh widgets every 5 minutes
    setInterval(() => {
        widgets.forEach(widget => {
            const widgetType = widget.getAttribute('data-widget');
            refreshWidget(widgetType);
        });
    }, 300000);
});

// Helper function to get auth token
function getAuthToken() {
    return document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
}

// Helper function to show notifications
function showNotification(message, type = 'info') {
    // This would integrate with your notification system
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>

<style>
.volunteering-widget {
    margin-bottom: 1rem;
}

.stat-item h3 {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.stat-item small {
    font-size: 0.875rem;
}

.border-bottom:last-child {
    border-bottom: none !important;
}

#volunteer-hours-chart {
    max-height: 200px;
}
</style>