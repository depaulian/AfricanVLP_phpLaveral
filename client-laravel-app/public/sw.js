/**
 * Service Worker for Mobile Profile System
 * Provides offline capabilities, caching, and background sync
 */

const CACHE_NAME = 'profile-mobile-v1';
const OFFLINE_URL = '/offline.html';

// Files to cache for offline use
const CACHE_URLS = [
    '/',
    '/mobile/profile',
    '/mobile/profile/edit',
    '/mobile/profile/documents',
    '/mobile/profile/skills',
    '/mobile/profile/history',
    '/css/app.css',
    '/css/mobile.css',
    '/js/app.js',
    '/js/mobile.js',
    '/images/default-avatar.png',
    OFFLINE_URL
];

// Install event - cache resources
self.addEventListener('install', event => {
    console.log('Service Worker installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching app shell');
                return cache.addAll(CACHE_URLS);
            })
            .then(() => {
                // Skip waiting to activate immediately
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker activating...');
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // Take control of all pages immediately
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Handle different types of requests
    if (request.method === 'GET') {
        if (url.pathname.startsWith('/mobile/profile')) {
            // Profile pages - cache first, then network
            event.respondWith(handleProfileRequest(request));
        } else if (url.pathname.startsWith('/api/')) {
            // API requests - network first, then cache
            event.respondWith(handleApiRequest(request));
        } else if (request.destination === 'image') {
            // Images - cache first
            event.respondWith(handleImageRequest(request));
        } else {
            // Other requests - network first
            event.respondWith(handleGenericRequest(request));
        }
    } else if (request.method === 'POST') {
        // Handle POST requests for offline sync
        event.respondWith(handlePostRequest(request));
    }
});

// Handle profile page requests
async function handleProfileRequest(request) {
    try {
        // Try network first for fresh content
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache successful responses
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
            return networkResponse;
        }
        
        throw new Error('Network response not ok');
    } catch (error) {
        // Fall back to cache
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline page if no cache
        return caches.match(OFFLINE_URL);
    }
}

// Handle API requests
async function handleApiRequest(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache GET API responses
            if (request.method === 'GET') {
                const cache = await caches.open(CACHE_NAME);
                cache.put(request, networkResponse.clone());
            }
            return networkResponse;
        }
        
        throw new Error('API request failed');
    } catch (error) {
        // For GET requests, try cache
        if (request.method === 'GET') {
            const cachedResponse = await caches.match(request);
            if (cachedResponse) {
                return cachedResponse;
            }
        }
        
        // Return offline response for API requests
        return new Response(
            JSON.stringify({
                error: 'Offline',
                message: 'This request requires an internet connection'
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

// Handle image requests
async function handleImageRequest(request) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
            return networkResponse;
        }
        
        throw new Error('Image request failed');
    } catch (error) {
        // Return placeholder image
        return caches.match('/images/default-avatar.png');
    }
}

// Handle generic requests
async function handleGenericRequest(request) {
    try {
        const networkResponse = await fetch(request);
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || caches.match(OFFLINE_URL);
    }
}

// Handle POST requests for offline sync
async function handlePostRequest(request) {
    try {
        return await fetch(request);
    } catch (error) {
        // Store POST request for background sync
        if (request.url.includes('/mobile/profile/')) {
            await storeForBackgroundSync(request);
            
            return new Response(
                JSON.stringify({
                    success: true,
                    message: 'Request saved for sync when online',
                    offline: true
                }),
                {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' }
                }
            );
        }
        
        return new Response(
            JSON.stringify({
                error: 'Offline',
                message: 'This action requires an internet connection'
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

// Store request for background sync
async function storeForBackgroundSync(request) {
    const db = await openDB();
    const transaction = db.transaction(['syncQueue'], 'readwrite');
    const store = transaction.objectStore('syncQueue');
    
    const requestData = {
        url: request.url,
        method: request.method,
        headers: Object.fromEntries(request.headers.entries()),
        body: await request.text(),
        timestamp: Date.now()
    };
    
    await store.add(requestData);
}

// Open IndexedDB
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ProfileSyncDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            if (!db.objectStoreNames.contains('syncQueue')) {
                db.createObjectStore('syncQueue', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

// Background sync event
self.addEventListener('sync', event => {
    console.log('Background sync triggered:', event.tag);
    
    if (event.tag === 'profile-sync') {
        event.waitUntil(syncOfflineData());
    }
});

// Sync offline data
async function syncOfflineData() {
    try {
        const db = await openDB();
        const transaction = db.transaction(['syncQueue'], 'readonly');
        const store = transaction.objectStore('syncQueue');
        const requests = await store.getAll();
        
        for (const requestData of requests) {
            try {
                const response = await fetch(requestData.url, {
                    method: requestData.method,
                    headers: requestData.headers,
                    body: requestData.body
                });
                
                if (response.ok) {
                    // Remove from sync queue
                    const deleteTransaction = db.transaction(['syncQueue'], 'readwrite');
                    const deleteStore = deleteTransaction.objectStore('syncQueue');
                    await deleteStore.delete(requestData.id);
                    
                    // Notify client of successful sync
                    self.clients.matchAll().then(clients => {
                        clients.forEach(client => {
                            client.postMessage({
                                type: 'SYNC_SUCCESS',
                                data: requestData
                            });
                        });
                    });
                }
            } catch (error) {
                console.error('Sync failed for request:', requestData, error);
            }
        }
    } catch (error) {
        console.error('Background sync error:', error);
    }
}

// Push notification event
self.addEventListener('push', event => {
    console.log('Push notification received:', event);
    
    const options = {
        body: 'You have new profile activity',
        icon: '/images/icon-192x192.png',
        badge: '/images/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View Profile',
                icon: '/images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/images/xmark.png'
            }
        ]
    };
    
    if (event.data) {
        const data = event.data.json();
        options.body = data.body || options.body;
        options.data = { ...options.data, ...data };
    }
    
    event.waitUntil(
        self.registration.showNotification('Profile Update', options)
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/mobile/profile')
        );
    } else if (event.action === 'close') {
        // Just close the notification
        return;
    } else {
        // Default action - open profile
        event.waitUntil(
            clients.openWindow('/mobile/profile')
        );
    }
});

// Message event - handle messages from client
self.addEventListener('message', event => {
    console.log('Service Worker received message:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    } else if (event.data && event.data.type === 'CACHE_PROFILE_DATA') {
        cacheProfileData(event.data.data);
    } else if (event.data && event.data.type === 'REGISTER_SYNC') {
        self.registration.sync.register('profile-sync');
    }
});

// Cache profile data
async function cacheProfileData(data) {
    try {
        const cache = await caches.open(CACHE_NAME);
        const response = new Response(JSON.stringify(data), {
            headers: { 'Content-Type': 'application/json' }
        });
        
        await cache.put('/api/mobile/profile/data', response);
        console.log('Profile data cached successfully');
    } catch (error) {
        console.error('Failed to cache profile data:', error);
    }
}

// Periodic background sync (if supported)
self.addEventListener('periodicsync', event => {
    if (event.tag === 'profile-sync') {
        event.waitUntil(syncOfflineData());
    }
});

// Handle unhandled promise rejections
self.addEventListener('unhandledrejection', event => {
    console.error('Unhandled promise rejection in service worker:', event.reason);
    event.preventDefault();
});

console.log('Service Worker loaded successfully');