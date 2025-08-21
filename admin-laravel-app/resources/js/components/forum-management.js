/**
 * Forum Management JavaScript Components
 * Handles interactive functionality for forum administration
 */

class ForumManagement {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeComponents();
    }

    bindEvents() {
        // Global event bindings
        $(document).on('click', '[data-action]', this.handleAction.bind(this));
        $(document).on('change', '.bulk-select', this.handleBulkSelect.bind(this));
        $(document).on('submit', '.ajax-form', this.handleAjaxForm.bind(this));
    }

    initializeComponents() {
        this.initializeDataTables();
        this.initializeCharts();
        this.initializeModals();
        this.initializeTooltips();
    }

    handleAction(e) {
        e.preventDefault();
        const $element = $(e.currentTarget);
        const action = $element.data('action');
        const target = $element.data('target');
        const confirm = $element.data('confirm');

        if (confirm && !window.confirm(confirm)) {
            return;
        }

        switch (action) {
            case 'toggle-status':
                this.toggleStatus(target, $element);
                break;
            case 'delete-item':
                this.deleteItem(target, $element);
                break;
            case 'bulk-action':
                this.performBulkAction($element);
                break;
            case 'export-data':
                this.exportData($element);
                break;
            default:
                console.warn('Unknown action:', action);
        }
    }

    handleBulkSelect(e) {
        const $checkbox = $(e.target);
        const isSelectAll = $checkbox.hasClass('select-all');

        if (isSelectAll) {
            const isChecked = $checkbox.is(':checked');
            $('.bulk-select:not(.select-all)').prop('checked', isChecked);
        }

        this.updateBulkActions();
    }

    handleAjaxForm(e) {
        e.preventDefault();
        const $form = $(e.target);
        const url = $form.attr('action');
        const method = $form.attr('method') || 'POST';
        const data = $form.serialize();

        this.submitAjaxForm(url, method, data, $form);
    }

    // Status toggle functionality
    toggleStatus(target, $element) {
        const [type, id] = target.split(':');
        const url = this.getToggleUrl(type, id);

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: () => {
                $element.prop('disabled', true);
                this.showLoading($element);
            },
            success: (response) => {
                if (response.success) {
                    this.updateStatusDisplay($element, response.status);
                    this.showNotification('success', response.message);
                } else {
                    this.showNotification('error', response.message);
                }
            },
            error: (xhr) => {
                this.showNotification('error', 'Failed to update status');
            },
            complete: () => {
                $element.prop('disabled', false);
                this.hideLoading($element);
            }
        });
    }

    getToggleUrl(type, id) {
        const urls = {
            'forum': `/admin/forums/${id}/toggle-status`,
            'thread': `/admin/forums/threads/${id}/toggle-pin`,
            'user': `/admin/forums/users/${id}/toggle-status`
        };
        return urls[type] || '#';
    }

    updateStatusDisplay($element, status) {
        const $statusBadge = $element.closest('tr').find('.status-badge');
        const badgeClass = status === 'active' ? 'badge-success' : 'badge-secondary';
        const badgeText = status === 'active' ? 'Active' : 'Inactive';

        $statusBadge.removeClass('badge-success badge-secondary badge-warning badge-danger')
                   .addClass(badgeClass)
                   .text(badgeText);
    }

    // Delete functionality
    deleteItem(target, $element) {
        const [type, id] = target.split(':');
        const url = this.getDeleteUrl(type, id);

        $.ajax({
            url: url,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: () => {
                $element.prop('disabled', true);
                this.showLoading($element);
            },
            success: (response) => {
                if (response.success) {
                    this.removeItemRow($element);
                    this.showNotification('success', response.message);
                } else {
                    this.showNotification('error', response.message);
                }
            },
            error: (xhr) => {
                this.showNotification('error', 'Failed to delete item');
            },
            complete: () => {
                $element.prop('disabled', false);
                this.hideLoading($element);
            }
        });
    }

    getDeleteUrl(type, id) {
        const urls = {
            'forum': `/admin/forums/${id}`,
            'thread': `/admin/forums/threads/${id}`,
            'post': `/admin/forums/posts/${id}`,
            'user': `/admin/forums/users/${id}`,
            'badge': `/admin/forums/badges/${id}`
        };
        return urls[type] || '#';
    }

    removeItemRow($element) {
        const $row = $element.closest('tr, .card, .badge-item');
        $row.fadeOut(300, function() {
            $(this).remove();
        });
    }

    // Bulk actions
    updateBulkActions() {
        const selectedCount = $('.bulk-select:not(.select-all):checked').length;
        const $bulkActions = $('.bulk-actions');

        if (selectedCount > 0) {
            $bulkActions.removeClass('d-none').find('.selected-count').text(selectedCount);
        } else {
            $bulkActions.addClass('d-none');
        }
    }

    performBulkAction($element) {
        const action = $element.data('bulk-action');
        const selectedIds = this.getSelectedIds();

        if (selectedIds.length === 0) {
            this.showNotification('warning', 'Please select at least one item');
            return;
        }

        const url = $element.data('url') || '/admin/forums/bulk-action';
        const data = {
            action: action,
            ids: selectedIds,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            beforeSend: () => {
                $element.prop('disabled', true);
                this.showLoading($element);
            },
            success: (response) => {
                if (response.success) {
                    this.showNotification('success', response.message);
                    if (response.reload) {
                        location.reload();
                    }
                } else {
                    this.showNotification('error', response.message);
                }
            },
            error: (xhr) => {
                this.showNotification('error', 'Bulk action failed');
            },
            complete: () => {
                $element.prop('disabled', false);
                this.hideLoading($element);
            }
        });
    }

    getSelectedIds() {
        return $('.bulk-select:not(.select-all):checked').map(function() {
            return $(this).val();
        }).get();
    }

    // Export functionality
    exportData($element) {
        const format = $element.data('format') || 'csv';
        const type = $element.data('type') || 'all';
        const filters = this.getCurrentFilters();

        const params = new URLSearchParams({
            export: format,
            type: type,
            ...filters
        });

        window.location.href = `${window.location.pathname}?${params.toString()}`;
    }

    getCurrentFilters() {
        const filters = {};
        $('form input, form select').each(function() {
            const $input = $(this);
            const name = $input.attr('name');
            const value = $input.val();

            if (name && value && name !== '_token') {
                filters[name] = value;
            }
        });
        return filters;
    }

    // AJAX form submission
    submitAjaxForm(url, method, data, $form) {
        const $submitBtn = $form.find('[type="submit"]');
        const originalText = $submitBtn.html();

        $.ajax({
            url: url,
            method: method,
            data: data,
            beforeSend: () => {
                $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            },
            success: (response) => {
                if (response.success) {
                    this.showNotification('success', response.message);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if (response.reload) {
                        location.reload();
                    } else {
                        $form[0].reset();
                        $form.closest('.modal').modal('hide');
                    }
                } else {
                    this.showNotification('error', response.message);
                }
            },
            error: (xhr) => {
                const message = xhr.responseJSON?.message || 'An error occurred';
                this.showNotification('error', message);
            },
            complete: () => {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    // Data tables initialization
    initializeDataTables() {
        if ($.fn.DataTable) {
            $('.data-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: 'no-sort' }
                ],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }
    }

    // Charts initialization
    initializeCharts() {
        if (typeof Chart !== 'undefined') {
            this.initializeActivityChart();
            this.initializeStatsCharts();
        }
    }

    initializeActivityChart() {
        const $canvas = $('#activityChart');
        if ($canvas.length === 0) return;

        const ctx = $canvas[0].getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: [], // Will be populated via AJAX
                datasets: [{
                    label: 'Posts',
                    data: [],
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.3
                }, {
                    label: 'Threads',
                    data: [],
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    tension: 0.3
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

        // Load chart data
        this.loadChartData();
    }

    initializeStatsCharts() {
        // Initialize various stats charts (pie, doughnut, bar)
        this.initializePieChart('#userRankChart', '/admin/forums/analytics/user-ranks');
        this.initializePieChart('#badgeDistributionChart', '/admin/forums/analytics/badge-distribution');
    }

    initializePieChart(selector, dataUrl) {
        const $canvas = $(selector);
        if ($canvas.length === 0) return;

        $.get(dataUrl)
            .done((data) => {
                const ctx = $canvas[0].getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            })
            .fail(() => {
                console.warn('Failed to load chart data for', selector);
            });
    }

    loadChartData() {
        const period = $('#chartPeriod').val() || '7d';
        
        $.get('/admin/forums/analytics/activity', { period })
            .done((data) => {
                // Update chart with new data
                // This would update the existing chart instance
            })
            .fail(() => {
                console.warn('Failed to load activity chart data');
            });
    }

    // Modal initialization
    initializeModals() {
        // Auto-focus first input in modals
        $('.modal').on('shown.bs.modal', function() {
            $(this).find('input:first').focus();
        });

        // Clear forms when modals are hidden
        $('.modal').on('hidden.bs.modal', function() {
            $(this).find('form')[0]?.reset();
        });
    }

    // Tooltip initialization
    initializeTooltips() {
        if ($.fn.tooltip) {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    }

    // Loading states
    showLoading($element) {
        const originalContent = $element.html();
        $element.data('original-content', originalContent);
        $element.html('<i class="fas fa-spinner fa-spin"></i>');
    }

    hideLoading($element) {
        const originalContent = $element.data('original-content');
        if (originalContent) {
            $element.html(originalContent);
        }
    }

    // Notifications
    showNotification(type, message, duration = 5000) {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const $alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;">
                <i class="fas fa-${this.getNotificationIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);

        $('body').append($alert);

        // Auto-dismiss
        if (duration > 0) {
            setTimeout(() => {
                $alert.alert('close');
            }, duration);
        }

        return $alert;
    }

    getNotificationIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    // Utility methods
    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    formatDate(date) {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }).format(new Date(date));
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Real-time updates using WebSockets or Server-Sent Events
class ForumRealTimeUpdates {
    constructor() {
        this.init();
    }

    init() {
        if (typeof EventSource !== 'undefined') {
            this.initializeSSE();
        } else if (typeof WebSocket !== 'undefined') {
            this.initializeWebSocket();
        } else {
            // Fallback to polling
            this.initializePolling();
        }
    }

    initializeSSE() {
        const eventSource = new EventSource('/admin/forums/events');
        
        eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleRealTimeUpdate(data);
        };

        eventSource.onerror = () => {
            console.warn('SSE connection error, falling back to polling');
            eventSource.close();
            this.initializePolling();
        };
    }

    initializeWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const ws = new WebSocket(`${protocol}//${window.location.host}/admin/forums/ws`);

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleRealTimeUpdate(data);
        };

        ws.onerror = () => {
            console.warn('WebSocket connection error, falling back to polling');
            this.initializePolling();
        };
    }

    initializePolling() {
        setInterval(() => {
            this.pollForUpdates();
        }, 30000); // Poll every 30 seconds
    }

    pollForUpdates() {
        $.get('/admin/forums/updates')
            .done((data) => {
                if (data.updates && data.updates.length > 0) {
                    data.updates.forEach(update => {
                        this.handleRealTimeUpdate(update);
                    });
                }
            })
            .fail(() => {
                console.warn('Failed to poll for updates');
            });
    }

    handleRealTimeUpdate(data) {
        switch (data.type) {
            case 'new_report':
                this.updateReportCount(data.count);
                this.showNewReportNotification(data.report);
                break;
            case 'new_post':
                this.updateActivityStats(data.stats);
                break;
            case 'user_online':
                this.updateOnlineUsers(data.users);
                break;
            default:
                console.log('Unknown update type:', data.type);
        }
    }

    updateReportCount(count) {
        $('.report-count').text(count);
        if (count > 0) {
            $('.report-badge').removeClass('d-none');
        }
    }

    showNewReportNotification(report) {
        const message = `New report: ${report.reason} by ${report.reporter.name}`;
        window.forumManagement.showNotification('warning', message);
    }

    updateActivityStats(stats) {
        Object.keys(stats).forEach(key => {
            $(`.stat-${key}`).text(window.forumManagement.formatNumber(stats[key]));
        });
    }

    updateOnlineUsers(users) {
        $('.online-users-count').text(users.length);
    }
}

// Initialize when DOM is ready
$(document).ready(function() {
    window.forumManagement = new ForumManagement();
    window.forumRealTimeUpdates = new ForumRealTimeUpdates();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ForumManagement, ForumRealTimeUpdates };
}