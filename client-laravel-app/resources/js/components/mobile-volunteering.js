/**
 * Mobile Volunteering Component
 * Handles GPS-based opportunity discovery, check-in/out, time logging, and offline functionality
 */

class MobileVolunteering {
    constructor() {
        this.config = {};
        this.userLocation = null;
        this.watchId = null;
        this.offlineData = [];
        this.syncQueue = [];
        this.isOnline = navigator.onLine;
        
        this.init();
    }

    async init() {
        try {
            await this.loadConfig();
            this.setupEventListeners();
            this.initializeLocation();
            this.setupOfflineHandling();
            this.initializeVueApp();
        } catch (error) {
            console.error('Failed to initialize mobile volunteering:', error);
            this.showError('Failed to initialize mobile features');
        }
    }

    async loadConfig() {
        try {
            const response = await fetch('/api/mobile/config', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Failed to load config');
            
            const result = await response.json();
            this.config = result.data;
        } catch (error) {
            console.error('Config loading error:', error);
            // Use default config
            this.config = {
                gps: { enabled: true, timeout: 30000, accuracy_threshold: 100 },
                offline: { enabled: true, sync_interval: 300000 },
                check_in: { location_required: true, geofence_radius: 200 }
            };
        }
    }

    setupEventListeners() {
        // Online/offline status
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.syncOfflineData();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
        });

        // Page visibility for background sync
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.isOnline) {
                this.syncOfflineData();
            }
        });

        // Periodic sync
        if (this.config.offline?.enabled) {
            setInterval(() => {
                if (this.isOnline) this.syncOfflineData();
            }, this.config.offline.sync_interval || 300000);
        }
    }

    initializeLocation() {
        if (!this.config.gps?.enabled || !navigator.geolocation) {
            console.warn('GPS not available or disabled');
            return;
        }

        const options = {
            enableHighAccuracy: true,
            timeout: this.config.gps.timeout || 30000,
            maximumAge: this.config.gps.max_age || 300000
        };

        // Get initial position
        navigator.geolocation.getCurrentPosition(
            (position) => this.handleLocationUpdate(position),
            (error) => this.handleLocationError(error),
            options
        );

        // Watch position changes
        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.handleLocationUpdate(position),
            (error) => this.handleLocationError(error),
            options
        );
    }

    handleLocationUpdate(position) {
        const { latitude, longitude, accuracy } = position.coords;
        
        this.userLocation = {
            latitude,
            longitude,
            accuracy,
            timestamp: Date.now()
        };

        // Update Vue app if available
        if (window.mobileVolunteeringApp) {
            window.mobileVolunteeringApp.userLocation = this.userLocation;
            window.mobileVolunteeringApp.locationLoading = false;
        }

        // Fetch nearby opportunities if accuracy is good enough
        if (accuracy <= (this.config.gps.accuracy_threshold || 100)) {
            this.fetchNearbyOpportunities();
        }
    }

    handleLocationError(error) {
        console.error('Location error:', error);
        
        let message = 'Location access denied';
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Location access denied. Please enable location services.';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Location information unavailable.';
                break;
            case error.TIMEOUT:
                message = 'Location request timed out.';
                break;
        }

        if (window.mobileVolunteeringApp) {
            window.mobileVolunteeringApp.locationError = message;
            window.mobileVolunteeringApp.locationLoading = false;
        }
    }

    async fetchNearbyOpportunities() {
        if (!this.userLocation) return;

        try {
            const params = new URLSearchParams({
                latitude: this.userLocation.latitude,
                longitude: this.userLocation.longitude,
                radius: 10,
                limit: 20
            });

            const response = await fetch(`/api/mobile/nearby-opportunities?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to fetch opportunities');

            const result = await response.json();
            
            if (window.mobileVolunteeringApp) {
                window.mobileVolunteeringApp.nearbyOpportunities = result.data;
                window.mobileVolunteeringApp.nearbyCount = result.data.length;
            }

            // Cache for offline use
            this.cacheData('nearby_opportunities', result.data);
            
        } catch (error) {
            console.error('Error fetching nearby opportunities:', error);
            
            // Try to load from cache
            const cached = this.getCachedData('nearby_opportunities');
            if (cached && window.mobileVolunteeringApp) {
                window.mobileVolunteeringApp.nearbyOpportunities = cached;
                window.mobileVolunteeringApp.nearbyCount = cached.length;
            }
        }
    }

    async checkIn(assignmentId, notes = '', photo = null) {
        if (!this.userLocation && this.config.check_in?.location_required) {
            throw new Error('Location is required for check-in');
        }

        const formData = new FormData();
        formData.append('assignment_id', assignmentId);
        formData.append('latitude', this.userLocation?.latitude || '');
        formData.append('longitude', this.userLocation?.longitude || '');
        formData.append('location_accuracy', this.userLocation?.accuracy || '');
        formData.append('notes', notes);
        formData.append('device_info', JSON.stringify(this.getDeviceInfo()));
        
        if (photo) {
            formData.append('photo', photo);
        }

        try {
            if (this.isOnline) {
                const response = await fetch('/api/mobile/check-in', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Check-in failed');

                const result = await response.json();
                
                if (result.success) {
                    this.showSuccess('Checked in successfully!');
                    this.fetchActiveCheckIns();
                    return result;
                } else {
                    throw new Error(result.message || 'Check-in failed');
                }
            } else {
                // Queue for offline sync
                const checkInData = {
                    type: 'check_in',
                    assignment_id: assignmentId,
                    latitude: this.userLocation?.latitude,
                    longitude: this.userLocation?.longitude,
                    location_accuracy: this.userLocation?.accuracy,
                    notes,
                    device_info: this.getDeviceInfo(),
                    timestamp: Date.now(),
                    local_id: this.generateLocalId()
                };

                this.addToSyncQueue(checkInData);
                this.showInfo('Check-in saved offline. Will sync when connection is restored.');
                
                return { success: true, offline: true };
            }
        } catch (error) {
            console.error('Check-in error:', error);
            throw error;
        }
    }

    async checkOut(checkInId, notes = '', photo = null) {
        const formData = new FormData();
        formData.append('assignment_id', checkInId);
        formData.append('latitude', this.userLocation?.latitude || '');
        formData.append('longitude', this.userLocation?.longitude || '');
        formData.append('location_accuracy', this.userLocation?.accuracy || '');
        formData.append('notes', notes);
        
        if (photo) {
            formData.append('photo', photo);
        }

        try {
            if (this.isOnline) {
                const response = await fetch('/api/mobile/check-out', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Check-out failed');

                const result = await response.json();
                
                if (result.success) {
                    this.showSuccess('Checked out successfully!');
                    this.fetchActiveCheckIns();
                    return result;
                } else {
                    throw new Error(result.message || 'Check-out failed');
                }
            } else {
                // Queue for offline sync
                const checkOutData = {
                    type: 'check_out',
                    check_in_id: checkInId,
                    latitude: this.userLocation?.latitude,
                    longitude: this.userLocation?.longitude,
                    location_accuracy: this.userLocation?.accuracy,
                    notes,
                    timestamp: Date.now(),
                    local_id: this.generateLocalId()
                };

                this.addToSyncQueue(checkOutData);
                this.showInfo('Check-out saved offline. Will sync when connection is restored.');
                
                return { success: true, offline: true };
            }
        } catch (error) {
            console.error('Check-out error:', error);
            throw error;
        }
    }

    async submitTimeLog(timeLogData, photo = null) {
        const formData = new FormData();
        
        Object.keys(timeLogData).forEach(key => {
            if (timeLogData[key] !== null && timeLogData[key] !== undefined) {
                formData.append(key, timeLogData[key]);
            }
        });

        // Add location data
        if (this.userLocation) {
            formData.append('latitude', this.userLocation.latitude);
            formData.append('longitude', this.userLocation.longitude);
            formData.append('location_accuracy', this.userLocation.accuracy);
        }

        formData.append('device_info', JSON.stringify(this.getDeviceInfo()));
        
        if (photo) {
            formData.append('photo', photo);
        }

        try {
            if (this.isOnline) {
                const response = await fetch('/api/mobile/time-log', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Time log submission failed');

                const result = await response.json();
                
                if (result.success) {
                    this.showSuccess('Time log submitted successfully!');
                    return result;
                } else {
                    throw new Error(result.message || 'Time log submission failed');
                }
            } else {
                // Queue for offline sync
                const timeLogOfflineData = {
                    type: 'time_log',
                    ...timeLogData,
                    latitude: this.userLocation?.latitude,
                    longitude: this.userLocation?.longitude,
                    location_accuracy: this.userLocation?.accuracy,
                    device_info: this.getDeviceInfo(),
                    timestamp: Date.now(),
                    local_id: this.generateLocalId()
                };

                this.addToSyncQueue(timeLogOfflineData);
                this.showInfo('Time log saved offline. Will sync when connection is restored.');
                
                return { success: true, offline: true };
            }
        } catch (error) {
            console.error('Time log error:', error);
            throw error;
        }
    }

    async fetchActiveCheckIns() {
        try {
            const response = await fetch('/api/mobile/active-check-ins', {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Failed to fetch active check-ins');

            const result = await response.json();
            
            if (window.mobileVolunteeringApp) {
                window.mobileVolunteeringApp.activeCheckIns = result.data;
            }

            return result.data;
        } catch (error) {
            console.error('Error fetching active check-ins:', error);
            return [];
        }
    }

    async syncOfflineData() {
        if (!this.isOnline || this.syncQueue.length === 0) return;

        try {
            const syncData = {
                time_logs: this.syncQueue.filter(item => item.type === 'time_log'),
                check_ins: this.syncQueue.filter(item => item.type === 'check_in' || item.type === 'check_out')
            };

            const response = await fetch('/api/mobile/sync', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(syncData)
            });

            if (!response.ok) throw new Error('Sync failed');

            const result = await response.json();
            
            if (result.success) {
                // Remove successfully synced items
                result.data.synced.forEach(synced => {
                    const index = this.syncQueue.findIndex(item => item.local_id === synced.local_id);
                    if (index > -1) {
                        this.syncQueue.splice(index, 1);
                    }
                });

                this.saveSyncQueue();
                
                if (result.data.synced.length > 0) {
                    this.showSuccess(`Synced ${result.data.synced.length} items successfully`);
                }

                if (result.data.errors.length > 0) {
                    console.warn('Sync errors:', result.data.errors);
                }
            }
        } catch (error) {
            console.error('Sync error:', error);
        }
    }

    setupOfflineHandling() {
        // Load sync queue from localStorage
        const saved = localStorage.getItem('mobile_volunteering_sync_queue');
        if (saved) {
            try {
                this.syncQueue = JSON.parse(saved);
            } catch (error) {
                console.error('Failed to load sync queue:', error);
                this.syncQueue = [];
            }
        }
    }

    addToSyncQueue(data) {
        this.syncQueue.push(data);
        this.saveSyncQueue();
    }

    saveSyncQueue() {
        localStorage.setItem('mobile_volunteering_sync_queue', JSON.stringify(this.syncQueue));
    }

    cacheData(key, data) {
        const cacheData = {
            data,
            timestamp: Date.now(),
            expires: Date.now() + (this.config.offline?.cache_duration || 86400000) // 24 hours
        };
        
        localStorage.setItem(`mobile_cache_${key}`, JSON.stringify(cacheData));
    }

    getCachedData(key) {
        const cached = localStorage.getItem(`mobile_cache_${key}`);
        if (!cached) return null;

        try {
            const cacheData = JSON.parse(cached);
            if (Date.now() > cacheData.expires) {
                localStorage.removeItem(`mobile_cache_${key}`);
                return null;
            }
            return cacheData.data;
        } catch (error) {
            console.error('Cache read error:', error);
            return null;
        }
    }

    getDeviceInfo() {
        return {
            user_agent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            screen: {
                width: screen.width,
                height: screen.height,
                pixel_ratio: window.devicePixelRatio
            },
            timestamp: Date.now()
        };
    }

    generateLocalId() {
        return `local_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    getAuthToken() {
        return document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showInfo(message) {
        this.showNotification(message, 'info');
    }

    showNotification(message, type = 'info') {
        // Use existing notification system or create simple toast
        if (window.showNotification) {
            window.showNotification(message, type);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
            
            // Simple toast implementation
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff'};
                color: white;
                border-radius: 4px;
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }

    initializeVueApp() {
        // Initialize Vue app for mobile volunteering dashboard
        if (document.getElementById('mobileVolunteeringApp')) {
            window.mobileVolunteeringApp = new Vue({
                el: '#mobileVolunteeringApp',
                data: {
                    userLocation: this.userLocation,
                    locationLoading: true,
                    locationError: null,
                    nearbyOpportunities: [],
                    nearbyCount: 0,
                    activeCheckIns: [],
                    activeAssignments: [],
                    userAssignments: [],
                    recentActivity: [],
                    offlineMode: !this.isOnline,
                    offlineDataCount: this.syncQueue.length,
                    syncing: false,
                    config: this.config,
                    
                    // Forms
                    checkInForm: {
                        assignment_id: '',
                        notes: '',
                        photo: null,
                        photoPreview: null
                    },
                    timeLogForm: {
                        assignment_id: '',
                        log_date: new Date().toISOString().split('T')[0],
                        start_time: '',
                        end_time: '',
                        hours_logged: '',
                        description: '',
                        photo: null,
                        photoPreview: null
                    },
                    
                    // Loading states
                    checkingIn: false,
                    submittingTimeLog: false
                },
                
                mounted() {
                    this.loadInitialData();
                },
                
                methods: {
                    async loadInitialData() {
                        await Promise.all([
                            this.fetchActiveCheckIns(),
                            this.fetchUserAssignments(),
                            this.fetchRecentActivity()
                        ]);
                    },
                    
                    async fetchActiveCheckIns() {
                        this.activeCheckIns = await window.mobileVolunteering.fetchActiveCheckIns();
                    },
                    
                    async fetchUserAssignments() {
                        // Implement API call to get user assignments
                        try {
                            const response = await fetch('/api/user/assignments', {
                                headers: {
                                    'Authorization': `Bearer ${window.mobileVolunteering.getAuthToken()}`,
                                    'Content-Type': 'application/json'
                                }
                            });
                            
                            if (response.ok) {
                                const result = await response.json();
                                this.activeAssignments = result.data.filter(a => a.status === 'active');
                                this.userAssignments = result.data;
                            }
                        } catch (error) {
                            console.error('Error fetching assignments:', error);
                        }
                    },
                    
                    async fetchRecentActivity() {
                        // Implement API call to get recent activity
                        // This would include recent time logs, check-ins, applications, etc.
                    },
                    
                    refreshLocation() {
                        this.locationLoading = true;
                        this.locationError = null;
                        window.mobileVolunteering.initializeLocation();
                    },
                    
                    toggleOfflineMode() {
                        this.offlineMode = !this.offlineMode;
                        if (!this.offlineMode && navigator.onLine) {
                            this.syncOfflineData();
                        }
                    },
                    
                    showNearbyOpportunities() {
                        if (this.nearbyOpportunities.length === 0) {
                            window.mobileVolunteering.fetchNearbyOpportunities();
                        }
                        // Scroll to nearby opportunities section
                        document.querySelector('.nearby-opportunities')?.scrollIntoView({ behavior: 'smooth' });
                    },
                    
                    showCheckInOptions() {
                        const modal = new bootstrap.Modal(document.getElementById('checkInModal'));
                        modal.show();
                    },
                    
                    showTimeLogForm() {
                        const modal = new bootstrap.Modal(document.getElementById('timeLogModal'));
                        modal.show();
                    },
                    
                    showOfflineData() {
                        // Show offline data management interface
                        alert(`You have ${this.offlineDataCount} items queued for sync`);
                    },
                    
                    async performCheckIn() {
                        if (!this.checkInForm.assignment_id) return;
                        
                        this.checkingIn = true;
                        try {
                            await window.mobileVolunteering.checkIn(
                                this.checkInForm.assignment_id,
                                this.checkInForm.notes,
                                this.checkInForm.photo
                            );
                            
                            // Reset form
                            this.checkInForm = {
                                assignment_id: '',
                                notes: '',
                                photo: null,
                                photoPreview: null
                            };
                            
                            // Close modal
                            bootstrap.Modal.getInstance(document.getElementById('checkInModal')).hide();
                            
                            // Refresh data
                            this.fetchActiveCheckIns();
                        } catch (error) {
                            window.mobileVolunteering.showError(error.message);
                        } finally {
                            this.checkingIn = false;
                        }
                    },
                    
                    async checkOut(checkIn) {
                        try {
                            await window.mobileVolunteering.checkOut(checkIn.id);
                            this.fetchActiveCheckIns();
                        } catch (error) {
                            window.mobileVolunteering.showError(error.message);
                        }
                    },
                    
                    async submitTimeLog() {
                        if (!this.timeLogForm.assignment_id) return;
                        
                        this.submittingTimeLog = true;
                        try {
                            await window.mobileVolunteering.submitTimeLog(
                                this.timeLogForm,
                                this.timeLogForm.photo
                            );
                            
                            // Reset form
                            this.timeLogForm = {
                                assignment_id: '',
                                log_date: new Date().toISOString().split('T')[0],
                                start_time: '',
                                end_time: '',
                                hours_logged: '',
                                description: '',
                                photo: null,
                                photoPreview: null
                            };
                            
                            // Close modal
                            bootstrap.Modal.getInstance(document.getElementById('timeLogModal')).hide();
                        } catch (error) {
                            window.mobileVolunteering.showError(error.message);
                        } finally {
                            this.submittingTimeLog = false;
                        }
                    },
                    
                    handlePhotoUpload(event) {
                        const file = event.target.files[0];
                        if (file) {
                            this.checkInForm.photo = file;
                            
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.checkInForm.photoPreview = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    },
                    
                    handleTimeLogPhoto(event) {
                        const file = event.target.files[0];
                        if (file) {
                            this.timeLogForm.photo = file;
                            
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.timeLogForm.photoPreview = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    },
                    
                    refreshNearby() {
                        window.mobileVolunteering.fetchNearbyOpportunities();
                    },
                    
                    viewOpportunity(opportunity) {
                        window.location.href = `/volunteering/${opportunity.slug}`;
                    },
                    
                    getDirections(opportunity) {
                        if (this.userLocation && opportunity.location.latitude && opportunity.location.longitude) {
                            const url = `https://www.google.com/maps/dir/${this.userLocation.latitude},${this.userLocation.longitude}/${opportunity.location.latitude},${opportunity.location.longitude}`;
                            window.open(url, '_blank');
                        }
                    },
                    
                    async syncOfflineData() {
                        this.syncing = true;
                        try {
                            await window.mobileVolunteering.syncOfflineData();
                            this.offlineDataCount = window.mobileVolunteering.syncQueue.length;
                        } finally {
                            this.syncing = false;
                        }
                    },
                    
                    formatDate(dateString) {
                        return new Date(dateString).toLocaleDateString();
                    }
                }
            });
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.mobileVolunteering = new MobileVolunteering();
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);