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

// Form Error Auto-Styling
function syncFormErrorStates() {
    document.querySelectorAll('.form-group').forEach((group) => {
        const hasError = Array.from(group.querySelectorAll('.form-error')).some((span) => {
            const text = (span.textContent || '').trim();
            return text.length > 0 && span.offsetParent !== null;
        });
        group.classList.toggle('error', hasError);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    syncFormErrorStates();
    new MutationObserver(syncFormErrorStates).observe(document.body, {
        childList: true,
        subtree: true,
        characterData: true,
        attributes: true,
        attributeFilter: ['style'],
    });
});
