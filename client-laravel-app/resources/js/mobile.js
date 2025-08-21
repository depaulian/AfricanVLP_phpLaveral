/**
 * Mobile-specific JavaScript for Profile System
 * Handles touch interactions, camera integration, offline capabilities
 */

class MobileProfileManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.offlineData = this.loadOfflineData();
        this.syncQueue = [];
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupOfflineSupport();
        this.setupTouchGestures();
        this.setupCameraIntegration();
        this.setupNotifications();
    }

    setupEventListeners() {
        // Network status
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.syncOfflineData();
            this.showToast('Connection restored', 'success');
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.showToast('You are offline. Changes will be saved locally.', 'warning');
        });

        // Page visibility for battery optimization
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseBackgroundTasks();
            } else {
                this.resumeBackgroundTasks();
            }
        });

        // Form auto-save
        this.setupAutoSave();
    }

    setupOfflineSupport() {
        // Register service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('Service Worker registered:', registration);
                })
                .catch(error => {
                    console.log('Service Worker registration failed:', error);
                });
        }

        // Setup offline data storage
        this.setupOfflineStorage();
    }

    setupOfflineStorage() {
        // Use IndexedDB for offline data storage
        if ('indexedDB' in window) {
            const request = indexedDB.open('ProfileDB', 1);
            
            request.onerror = () => {
                console.log('IndexedDB error');
            };
            
            request.onsuccess = (event) => {
                this.db = event.target.result;
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Create object stores
                if (!db.objectStoreNames.contains('profile')) {
                    db.createObjectStore('profile', { keyPath: 'id' });
                }
                
                if (!db.objectStoreNames.contains('documents')) {
                    db.createObjectStore('documents', { keyPath: 'id' });
                }
                
                if (!db.objectStoreNames.contains('syncQueue')) {
                    db.createObjectStore('syncQueue', { keyPath: 'id', autoIncrement: true });
                }
            };
        }
    }

    setupTouchGestures() {
        let startX, startY, startTime;
        
        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            startTime = Date.now();
        });
        
        document.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            const endTime = Date.now();
            
            const deltaX = endX - startX;
            const deltaY = endY - startY;
            const deltaTime = endTime - startTime;
            
            // Swipe detection
            if (Math.abs(deltaX) > 50 && Math.abs(deltaY) < 100 && deltaTime < 300) {
                if (deltaX > 0) {
                    this.handleSwipeRight();
                } else {
                    this.handleSwipeLeft();
                }
            }
        });
    }

    setupCameraIntegration() {
        this.cameraSupported = 'mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices;
        
        if (this.cameraSupported) {
            this.setupCameraCapture();
        }
    }

    setupCameraCapture() {
        const cameraButtons = document.querySelectorAll('[data-camera-capture]');
        
        cameraButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.openCameraCapture(button.dataset.cameraCapture);
            });
        });
    }

    async openCameraCapture(type = 'image') {
        try {
            const constraints = {
                video: {
                    facingMode: type === 'document' ? 'environment' : 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };
            
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.showCameraInterface(stream, type);
            
        } catch (error) {
            console.error('Camera access error:', error);
            this.showToast('Camera access denied or not available', 'error');
            
            // Fallback to file input
            this.triggerFileInput(type);
        }
    }

    showCameraInterface(stream, type) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black z-50 flex flex-col';
        
        modal.innerHTML = `
            <div class="flex-1 relative">
                <video id="cameraPreview" autoplay playsinline class="w-full h-full object-cover"></video>
                <canvas id="cameraCanvas" class="hidden"></canvas>
                
                <!-- Camera overlay -->
                <div class="absolute inset-0 pointer-events-none">
                    ${type === 'document' ? this.getDocumentOverlay() : this.getProfileOverlay()}
                </div>
                
                <!-- Camera controls -->
                <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black to-transparent">
                    <div class="flex items-center justify-center space-x-8">
                        <button id="cancelCapture" class="text-white p-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        
                        <button id="captureButton" class="bg-white rounded-full p-4">
                            <div class="w-16 h-16 bg-white rounded-full border-4 border-gray-300"></div>
                        </button>
                        
                        <button id="switchCamera" class="text-white p-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const video = document.getElementById('cameraPreview');
        const canvas = document.getElementById('cameraCanvas');
        const captureBtn = document.getElementById('captureButton');
        const cancelBtn = document.getElementById('cancelCapture');
        const switchBtn = document.getElementById('switchCamera');
        
        video.srcObject = stream;
        
        captureBtn.addEventListener('click', () => {
            this.captureImage(video, canvas, type);
            this.closeCameraInterface(modal, stream);
        });
        
        cancelBtn.addEventListener('click', () => {
            this.closeCameraInterface(modal, stream);
        });
        
        switchBtn.addEventListener('click', () => {
            this.switchCamera(stream, video, type);
        });
    }

    captureImage(video, canvas, type) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        canvas.toBlob((blob) => {
            this.handleCapturedImage(blob, type);
        }, 'image/jpeg', 0.8);
    }

    handleCapturedImage(blob, type) {
        const file = new File([blob], `${type}_${Date.now()}.jpg`, { type: 'image/jpeg' });
        
        if (type === 'profile') {
            this.uploadProfileImage(file);
        } else if (type === 'document') {
            this.uploadDocument(file);
        }
    }

    closeCameraInterface(modal, stream) {
        stream.getTracks().forEach(track => track.stop());
        modal.remove();
    }

    getDocumentOverlay() {
        return `
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="border-2 border-white border-dashed rounded-lg w-80 h-60 flex items-center justify-center">
                    <div class="text-white text-center">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm">Position document within frame</p>
                    </div>
                </div>
            </div>
        `;
    }

    getProfileOverlay() {
        return `
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="border-4 border-white rounded-full w-64 h-64 flex items-center justify-center">
                    <div class="text-white text-center">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <p class="text-sm">Center your face</p>
                    </div>
                </div>
            </div>
        `;
    }

    setupNotifications() {
        if ('Notification' in window) {
            this.requestNotificationPermission();
        }
        
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            this.setupPushNotifications();
        }
    }

    async requestNotificationPermission() {
        if (Notification.permission === 'default') {
            const permission = await Notification.requestPermission();
            return permission === 'granted';
        }
        return Notification.permission === 'granted';
    }

    setupPushNotifications() {
        navigator.serviceWorker.ready.then(registration => {
            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(window.vapidPublicKey)
            });
        }).then(subscription => {
            this.sendSubscriptionToServer(subscription);
        }).catch(error => {
            console.log('Push subscription error:', error);
        });
    }

    setupAutoSave() {
        const forms = document.querySelectorAll('form[data-autosave]');
        
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, textarea, select');
            
            inputs.forEach(input => {
                input.addEventListener('input', this.debounce(() => {
                    this.autoSaveForm(form);
                }, 1000));
            });
        });
    }

    autoSaveForm(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Save to local storage
        localStorage.setItem(`autosave_${form.id}`, JSON.stringify({
            data: data,
            timestamp: Date.now()
        }));
        
        // Queue for sync if online
        if (this.isOnline) {
            this.queueForSync('form_update', data);
        }
        
        this.showToast('Changes saved locally', 'info', 1000);
    }

    loadAutoSavedData(formId) {
        const saved = localStorage.getItem(`autosave_${formId}`);
        if (saved) {
            const { data, timestamp } = JSON.parse(saved);
            
            // Only load if less than 24 hours old
            if (Date.now() - timestamp < 24 * 60 * 60 * 1000) {
                return data;
            }
        }
        return null;
    }

    async uploadProfileImage(file) {
        this.showLoading();
        
        try {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            const response = await fetch('/mobile/profile/upload-image', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updateProfileImage(result.url);
                this.showToast('Profile image updated!', 'success');
                this.hapticFeedback('success');
            } else {
                throw new Error(result.message);
            }
            
        } catch (error) {
            console.error('Upload error:', error);
            
            if (!this.isOnline) {
                this.queueForSync('image_upload', { file, type: 'profile' });
                this.showToast('Image saved for upload when online', 'info');
            } else {
                this.showToast('Upload failed. Please try again.', 'error');
            }
        } finally {
            this.hideLoading();
        }
    }

    async uploadDocument(file, type = 'other') {
        this.showLoading();
        
        try {
            const formData = new FormData();
            formData.append('document', file);
            formData.append('document_type', type);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            const response = await fetch('/mobile/profile/documents/upload', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.addDocumentToList(result.document);
                this.showToast('Document uploaded successfully!', 'success');
                this.hapticFeedback('success');
            } else {
                throw new Error(result.message);
            }
            
        } catch (error) {
            console.error('Upload error:', error);
            
            if (!this.isOnline) {
                this.queueForSync('document_upload', { file, type });
                this.showToast('Document saved for upload when online', 'info');
            } else {
                this.showToast('Upload failed. Please try again.', 'error');
            }
        } finally {
            this.hideLoading();
        }
    }

    queueForSync(action, data) {
        const syncItem = {
            id: Date.now(),
            action,
            data,
            timestamp: Date.now()
        };
        
        this.syncQueue.push(syncItem);
        this.saveSyncQueue();
    }

    async syncOfflineData() {
        if (!this.isOnline || this.syncQueue.length === 0) return;
        
        this.showToast('Syncing offline changes...', 'info');
        
        for (const item of this.syncQueue) {
            try {
                await this.processSyncItem(item);
                this.syncQueue = this.syncQueue.filter(i => i.id !== item.id);
            } catch (error) {
                console.error('Sync error:', error);
            }
        }
        
        this.saveSyncQueue();
        
        if (this.syncQueue.length === 0) {
            this.showToast('All changes synced!', 'success');
        }
    }

    // Utility methods
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

    showLoading() {
        document.getElementById('loadingOverlay')?.classList.remove('hidden');
    }

    hideLoading() {
        document.getElementById('loadingOverlay')?.classList.add('hidden');
    }

    showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : 
                       type === 'error' ? 'bg-red-500' : 
                       type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
        
        toast.className = `${bgColor} text-white px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
        toast.textContent = message;
        
        const container = document.getElementById('toastContainer');
        if (container) {
            container.appendChild(toast);
            
            setTimeout(() => toast.classList.remove('translate-x-full'), 100);
            
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    }

    hapticFeedback(type = 'light') {
        if (navigator.vibrate) {
            const patterns = {
                light: [10],
                medium: [20],
                heavy: [30],
                success: [10, 50, 10],
                error: [50, 50, 50]
            };
            navigator.vibrate(patterns[type] || patterns.light);
        }
    }

    handleSwipeRight() {
        // Navigate back or show menu
        const backButton = document.querySelector('[data-back-button]');
        if (backButton) {
            backButton.click();
        }
    }

    handleSwipeLeft() {
        // Navigate forward or show options
        const nextButton = document.querySelector('[data-next-button]');
        if (nextButton) {
            nextButton.click();
        }
    }

    loadOfflineData() {
        const data = localStorage.getItem('offline_profile_data');
        return data ? JSON.parse(data) : {};
    }

    saveOfflineData(data) {
        localStorage.setItem('offline_profile_data', JSON.stringify(data));
    }

    saveSyncQueue() {
        localStorage.setItem('sync_queue', JSON.stringify(this.syncQueue));
    }

    loadSyncQueue() {
        const queue = localStorage.getItem('sync_queue');
        return queue ? JSON.parse(queue) : [];
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    pauseBackgroundTasks() {
        // Pause any background sync or polling
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }
    }

    resumeBackgroundTasks() {
        // Resume background tasks
        this.syncInterval = setInterval(() => {
            if (this.isOnline) {
                this.syncOfflineData();
            }
        }, 30000); // Sync every 30 seconds
    }
}

// Initialize mobile profile manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.mobileProfileManager = new MobileProfileManager();
});

// Export for use in other modules
export default MobileProfileManager;