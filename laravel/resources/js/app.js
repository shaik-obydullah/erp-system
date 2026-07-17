import Alpine from 'alpinejs';
import { createApp } from 'vue';
import './components/customer';

// Start Alpine.js
window.Alpine = Alpine;
Alpine.start();

// Mount Vue components (island architecture)
document.addEventListener('DOMContentLoaded', async () => {
    // Mount Activity Manager
    const activityEl = document.getElementById('activity-manager');
    if (activityEl) {
        const { default: ActivityManager } = await import('./components/ActivityManager.vue');
        createApp(ActivityManager).mount('#activity-manager');
    }

    // Mount Notification Manager
    const notifEl = document.getElementById('notification-manager');
    if (notifEl) {
        const { default: NotificationManager } = await import('./components/NotificationManager.vue');
        createApp(NotificationManager).mount('#notification-manager');
    }
});
