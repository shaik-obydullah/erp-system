<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userMenu = document.getElementById('userMenu');
        if (userMenu) {
            userMenu.addEventListener('click', (e) => { e.stopPropagation(); userMenu.classList.toggle('open'); });
            document.addEventListener('click', () => { userMenu.classList.remove('open'); });
            const userDropdown = document.getElementById('userDropdown');
            if (userDropdown) userDropdown.addEventListener('click', (e) => { e.stopPropagation(); });
        }

        fetch('/api/notifications/unread-count', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                var badge = document.getElementById('notifBadge');
                if (d.count > 0) { badge.textContent = d.count; badge.style.display = ''; }
            })
            .catch(() => {});
    });
</script>
