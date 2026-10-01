<?php
/**
 * notif_js.php — Include just before </body>.
 * Set $notif_path before including:
 *   $notif_path = '../';  // admin/ subfolder
 *   $notif_path = '';     // root pages
 */
$notif_path = $notif_path ?? '';
$is_admin_role = isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'Super Admin'], true);
?>
<script>
(function() {
    var BASE     = '<?php echo $notif_path; ?>';  // API path ('' or '../')
    var IS_ADMIN = <?php echo $is_admin_role ? 'true' : 'false'; ?>;

    // ── Position fixed dropdown below the bell button ──────────
    function positionDropdown() {
        var btn = document.getElementById('notifBellBtn');
        var d   = document.getElementById('notifDropdown');
        if (!btn || !d) return;
        var rect = btn.getBoundingClientRect();
        var left = rect.right - 320;
        if (left < 8) left = 8;
        d.style.top  = (rect.bottom + 6) + 'px';
        d.style.left = left + 'px';
    }

    // ── Toggle open/close ───────────────────────────────────────
    window.toggleNotifPanel = function(e) {
        if (e) e.stopPropagation();
        var d = document.getElementById('notifDropdown');
        if (!d) return;
        if (d.classList.contains('open')) {
            d.classList.remove('open');
        } else {
            positionDropdown();
            d.classList.add('open');
            fetchNotifications();
        }
    };

    // ── Fetch & render notifications ───────────────────────────
    function fetchNotifications() {
        fetch(BASE + 'get_notifications.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                var badge = document.getElementById('notifBellBadge');
                var list  = document.getElementById('notifList');
                if (!badge || !list) return;

                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }

                if (data.notifications.length === 0) {
                    list.innerHTML = '<div class="notif-empty"><i class="fa fa-bell-slash"></i><p>No notifications yet</p></div>';
                    return;
                }

                // NOTE: stopPropagation on each item so clicking inside
                // dropdown does NOT trigger the document close-listener
                list.innerHTML = data.notifications.map(function(n) {
                    var url = getNotifUrl(n);
                    return '<div class="notif-item ' + (n.is_read ? '' : 'unread') + '" '
                         + 'onclick="event.stopPropagation(); markRead(' + n.id + (url ? ", '" + url + "'" : '') + ')" '
                         + 'style="cursor:pointer;">'
                         + '<div class="notif-title">' + n.title + '</div>'
                         + '<div class="notif-msg">'   + n.message + '</div>'
                         + '<div class="notif-time">'  + n.time_ago + '</div>'
                         + '</div>';
                }).join('');
            })
            .catch(function() {});
    }
    window.fetchNotifications = fetchNotifications;

    // ── Prevent clicks inside the dropdown from closing it ─────
    document.addEventListener('DOMContentLoaded', function() {
        var dropdown = document.getElementById('notifDropdown');
        if (dropdown) {
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });

    // ── Redirect URL per notification type ─────────────────────
    function getNotifUrl(n) {
        var type = String(n.type || '').trim().toLowerCase();
        var title = String(n.title || '').trim().toLowerCase();
        var message = String(n.message || '').trim().toLowerCase();
        var isRescheduleRequest = type === 'reschedule_request'
            || title.indexOf('reschedule request') !== -1
            || message.indexOf('requested to reschedule') !== -1;
        if (IS_ADMIN) {
            if (isRescheduleRequest)
                return new URL('schedule_management.php?tab=reschedTab', window.location.href).href;
            if (type === 'new_registration' || type === 'registration')
                return 'super_admin_dashboard.php';
            if (type === 'updated_schedule')
                return 'schedule_management.php';
        } else {
            if (type === 'reschedule_request' || type === 'updated_schedule'
                || type === 'new_schedule'    || type === 'schedule_approved')
                return 'user_schedule.php';
            if (type === 'registration' || type === 'approved')
                return 'user_dashboard.php';
        }
        return null;
    }

    // ── Mark as read → redirect or refresh list ─────────────────
    window.markRead = function(id, url) {
        var markRequest = fetch(BASE + 'mark_notifications_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(id),
            keepalive: true
        });

        if (url) {
            window.location.assign(url);
        } else {
            markRequest.then(fetchNotifications).catch(fetchNotifications);
        }
    };

    window.markAllRead = function() {
        fetch(BASE + 'mark_notifications_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'mark_all=1'
        }).then(function() { fetchNotifications(); });
    };

    // ── Reposition on scroll/resize ────────────────────────────
    window.addEventListener('scroll', function() {
        var d = document.getElementById('notifDropdown');
        if (d && d.classList.contains('open')
        ) positionDropdown();
    }, true);
    window.addEventListener('resize', function() {
        var d = document.getElementById('notifDropdown');
        if (d && d.classList.contains('open')) positionDropdown();
    });

    // ── Close only when clicking OUTSIDE the wrapper ───────────
    document.addEventListener('click', function(e) {
        var w = document.getElementById('notifWrapper');
        if (w && !w.contains(e.target)) {
            var d = document.getElementById('notifDropdown');
            if (d) d.classList.remove('open');
        }
    });

    // ── Auto-fetch every 30s ───────────────────────────────────
    fetchNotifications();
    setInterval(fetchNotifications, 30000);
})();
</script>
