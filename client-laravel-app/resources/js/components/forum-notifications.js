/**
 * Forum Notifications Component
 * Handles real-time notifications, subscription management, and notification display
 */

class ForumNotifications {
    constructor() {
        this.notificationCount = 0;
        this.notifications = [];
        this.isDropdownOpen = false;
        this.pollInterval = null;
        this.init();
    }

    init() {
        this.createNotificationBell();
        this.bindEvents();
        this.startPolling();
        this.loadInitialNotifications();
    }

    createNotificationBell() {
        // Create notification bell in header if it doesn't exist
        const header = document.querySelector('header nav');
        if (!header || document.getElementById('notification-bell')) return;

        const bellContainer = document.createElement('div');
        bellContainer.className = 'relative';
        bellContainer.innerHTML = `
            <button id="notification-bell" 
                    class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg">
                <i class="fas fa-bell text-xl"></i>
                <span id="notification-badge" 
                      class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">
                    0
                </span>
            </button>
            
            <!-- Notification Dropdown -->
            <div id="notification-dropdown" 
                 class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 hidden">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-gray-900">Notifications</h3>
                        <button id="mark-all-read" 
                                class="text-sm text-blue-600 hover:text-blue-800">
                            Mark all read
                        </button>
                    </div>
                </div>
                
                <div id="notification-list" class="max-h-96 overflow-y-auto">
                    <div class="p-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin mb-2"></i>
                        <div>Loading notifications...</div>
                    </div>
                </div>
                
                <div class="p-4 border-t border-gray-200">
                    <a href="/forums/notifications" 
                       class="block text-center text-blue-600 hover:text-blue-800 text-sm">
                        View all notifications
                    </a>
                </div>
            </div>
        `;

        // Insert before user menu or at the end
        const userMenu = header.querySelector('.user-menu');
        if (userMenu) {
            header.insertBefore(bellContainer, userMenu);
        } else {
            header.appendChild(bellContainer);
        }
    }

    bindEvents() {
        // Notification bell click
        document.addEventListener('click', (e) => {
            const bell = document.getElementById('notification-bell');
            const dropdown = document.getElementById('notification-dropdown');
            
            if (!bell || !dropdown) return;

            if (bell.contains(e.target)) {
                e.preventDefault();
                this.toggleDropdown();
            } else if (!dropdown.contains(e.target)) {
                this.closeDropdown();
            }
        });

        // Mark all as read
        document.addEventListener('click', (e) => {
            if (e.target.id === 'mark-all-read') {
                e.preventDefault();
                this.markAllAsRead();
            }
        });

        // Individual notification actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.notification-item')) {
                const notificationItem = e.target.closest('.notification-item');
                const notificationId = notificationItem.dataset.notificationId;
                
                if (e.target.classList.contains('mark-read-btn')) {
                    e.preventDefault();
                    this.markAsRead(notificationId);
                } else if (e.target.classList.contains('notification-link')) {
                    // Mark as read when clicking the notification link
                    this.markAsRead(notificationId);
                }
            }
        });

        // Subscription buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('subscribe-btn')) {
                e.preventDefault();
                this.handleSubscription(e.target);
            }
        });
    }

    async loadInitialNotifications() {
        try {
            const response = await fetch('/forums/notifications/unread');
            const data = await response.json();
            
            this.notifications = data.notifications;
            this.updateNotificationCount(data.count);
            this.renderNotifications();
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    startPolling() {
        // Poll for new notifications every 30 seconds
        this.pollInterval = setInterval(() => {
            this.loadInitialNotifications();
        }, 30000);
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    toggleDropdown() {
        const dropdown = document.getElementById('notification-dropdown');
        if (!dropdown) return;

        this.isDropdownOpen = !this.isDropdownOpen;
        
        if (this.isDropdownOpen) {
            dropdown.classList.remove('hidden');
            this.loadInitialNotifications();
        } else {
            dropdown.classList.add('hidden');
        }
    }

    closeDropdown() {
        const dropdown = document.getElementById('notification-dropdown');
        if (dropdown) {
            dropdown.classList.add('hidden');
            this.isDropdownOpen = false;
        }
    }

    updateNotificationCount(count) {
        this.notificationCount = count;
        const badge = document.getElementById('notification-badge');
        
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }

    renderNotifications() {
        const list = document.getElementById('notification-list');
        if (!list) return;

        if (this.notifications.length === 0) {
            list.innerHTML = `
                <div class="p-4 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-2xl mb-2"></i>
                    <div>No new notifications</div>
                </div>
            `;
            return;
        }

        list.innerHTML = this.notifications.map(notification => `
            <div class="notification-item border-b border-gray-100 p-4 hover:bg-gray-50 transition-colors"
                 data-notification-id="${notification.id}">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center ${notification.color_class}">
                            <i class="${notification.icon} text-sm"></i>
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="text-sm font-medium text-gray-900 truncate">
                                ${notification.title}
                            </h4>
                            <button class="mark-read-btn text-blue-600 hover:text-blue-800 text-xs"
                                    title="Mark as read">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-2">${notification.message}</p>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">${notification.time_ago}</span>
                            <a href="${notification.url}" 
                               class="notification-link text-xs text-blue-600 hover:text-blue-800">
                                View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/forums/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                // Remove notification from list
                this.notifications = this.notifications.filter(n => n.id != notificationId);
                this.updateNotificationCount(this.notifications.length);
                this.renderNotifications();
                
                // Remove from DOM if on notifications page
                const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.remove();
                }
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/forums/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                this.notifications = [];
                this.updateNotificationCount(0);
                this.renderNotifications();
                
                // Reload page if on notifications page
                if (window.location.pathname.includes('/notifications')) {
                    location.reload();
                }
            }
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    }

    async handleSubscription(button) {
        const action = button.dataset.action; // 'subscribe' or 'unsubscribe'
        const subscribableType = button.dataset.subscribableType;
        const subscribableId = button.dataset.subscribableId;
        const type = button.dataset.type;

        try {
            const url = action === 'subscribe' ? '/forums/notifications/subscribe' : '/forums/notifications/unsubscribe';
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    subscribable_type: subscribableType,
                    subscribable_id: subscribableId,
                    type: type
                })
            });

            const data = await response.json();

            if (data.success) {
                // Update button state
                if (action === 'subscribe') {
                    button.textContent = 'Unsubscribe';
                    button.dataset.action = 'unsubscribe';
                    button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    button.classList.add('bg-gray-600', 'hover:bg-gray-700');
                } else {
                    button.textContent = 'Subscribe';
                    button.dataset.action = 'subscribe';
                    button.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                    button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }

                // Show success message
                this.showMessage(data.message, 'success');
            } else {
                this.showMessage(data.message || 'Operation failed', 'error');
            }
        } catch (error) {
            console.error('Subscription operation failed:', error);
            this.showMessage('Operation failed', 'error');
        }
    }

    showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
            type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        
        messageDiv.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${
                    type === 'success' ? 'fa-check-circle' :
                    type === 'error' ? 'fa-exclamation-circle' :
                    'fa-info-circle'
                } mr-2"></i>
                ${message}
            </div>
        `;

        document.body.appendChild(messageDiv);

        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }

    // Utility method to add subscription buttons to forum pages
    addSubscriptionButton(subscribableType, subscribableId, type, isSubscribed = false) {
        const container = document.querySelector('.subscription-container');
        if (!container) return;

        const button = document.createElement('button');
        button.className = `subscribe-btn px-4 py-2 rounded-lg text-white transition-colors ${
            isSubscribed ? 'bg-gray-600 hover:bg-gray-700' : 'bg-blue-600 hover:bg-blue-700'
        }`;
        button.textContent = isSubscribed ? 'Unsubscribe' : 'Subscribe';
        button.dataset.action = isSubscribed ? 'unsubscribe' : 'subscribe';
        button.dataset.subscribableType = subscribableType;
        button.dataset.subscribableId = subscribableId;
        button.dataset.type = type;

        container.appendChild(button);
    }

    // Clean up when component is destroyed
    destroy() {
        this.stopPolling();
        // Remove event listeners if needed
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('meta[name="user-authenticated"]')) {
        window.forumNotifications = new ForumNotifications();
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ForumNotifications;
}