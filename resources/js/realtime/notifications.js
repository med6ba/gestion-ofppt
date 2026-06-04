export function initNotificationsRealtime(userId) {
    if (!window.Echo) return;

    window.Echo.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            // Ensure Livewire/Alpine components listening for this event are updated
            window.dispatchEvent(new CustomEvent('notification-received', {
                detail: notification
            }));

            // Optional: Update a global notification counter dynamically
            const badge = document.querySelector('.menu-badge');
            if (badge) {
                badge.textContent = parseInt(badge.textContent || 0) + 1;
            } else {
                // If there was no badge, we might need to create it (depends on UI structure)
            }
            
            // We can also trigger a toast notification here if needed
        });
}
