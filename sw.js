// sw.js - Service Worker for Push Notifications
// CIBIL Repair - Admin Dashboard

const CACHE_NAME = 'cibil-repair-v1';

// Install event - cache essential files
self.addEventListener('install', function(event) {
    console.log('Service Worker installed');
    event.waitUntil(self.skipWaiting());
});

// Activate event - clean up old caches
self.addEventListener('activate', function(event) {
    console.log('Service Worker activated');
    event.waitUntil(self.clients.claim());
});

// Push event - display notification
self.addEventListener('push', function(event) {
    console.log('Push notification received');
    
    // Default notification data
    let title = 'CIBIL Repair';
    let options = {
        body: 'You have a new notification from your CRM',
        icon: '/assets/images/icon-192x192.png',
        badge: '/assets/images/badge-72x72.png',
        vibrate: [200, 100, 200],
        silent: false,
        data: {
            url: '/admin-dashboard.php',
            dateOfArrival: Date.now()
        },
        actions: [
            { action: 'open', title: 'View Dashboard', icon: '/assets/images/checkmark.png' },
            { action: 'close', title: 'Dismiss', icon: '/assets/images/xmark.png' }
        ]
    };
    
    // Parse notification data if available
    if (event.data) {
        try {
            const data = event.data.json();
            title = data.title || title;
            options.body = data.body || options.body;
            options.data.url = data.url || options.data.url;
            options.icon = data.icon || options.icon;
            options.badge = data.badge || options.badge;
        } catch (e) {
            console.error('Failed to parse notification data:', e);
        }
    }
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', function(event) {
    console.log('Notification clicked:', event.action);
    event.notification.close();
    
    let urlToOpen = '/admin-dashboard.php';
    
    // Check if notification has custom URL
    if (event.notification.data && event.notification.data.url) {
        urlToOpen = event.notification.data.url;
    }
    
    // Handle action buttons
    if (event.action === 'open') {
        // Open the URL
        event.waitUntil(
            clients.matchAll({ type: 'window', includeUncontrolled: true })
                .then(windowClients => {
                    // Check if there's already a window/tab open with the target URL
                    for (let client of windowClients) {
                        if (client.url === urlToOpen && 'focus' in client) {
                            return client.focus();
                        }
                    }
                    // If not, open a new window
                    if (clients.openWindow) {
                        return clients.openWindow(urlToOpen);
                    }
                })
        );
    } else if (event.action === 'close') {
        // Just close the notification (already closed)
        console.log('Notification dismissed');
    } else {
        // Default: open the URL
        event.waitUntil(
            clients.openWindow(urlToOpen)
        );
    }
});

// Fetch event - for offline support (optional)
self.addEventListener('fetch', function(event) {
    // You can add offline caching here if needed
    // For now, just fetch from network
    event.respondWith(fetch(event.request));
});

// Push subscription change event
self.addEventListener('pushsubscriptionchange', function(event) {
    console.log('Push subscription changed');
    // Re-subscribe on the server
    event.waitUntil(
        fetch('/api/refresh-subscription.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ old: event.oldSubscription, new: event.newSubscription })
        })
    );
});