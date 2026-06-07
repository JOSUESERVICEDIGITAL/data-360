<script>
function updateUserNotificationBadge() {
    fetch('/api/notifications/unread')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('userNotifBadge');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(err => console.log('Erreur:', err));
}

// Appeler au chargement et toutes les 30 secondes
if (document.getElementById('userNotifBadge')) {
    updateUserNotificationBadge();
    setInterval(updateUserNotificationBadge, 30000);
}</script>
