<?php ?>
<?php if (isset($_SESSION['user_id'])): ?>
    <div class="notif-wrapper" id="notifWrapper">
        <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifPanel(event)" title="Notifications" aria-label="Notifications">
            <svg class="notif-bell-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                <path d="M10 21h4"></path>
            </svg>
            <span id="notifBellBadge" style="display:none;">0</span>
        </button>
        <div class="notif-dropdown" id="notifDropdown">
            <div class="notif-dropdown-header">
                <span><i class="fa fa-bell"></i> Notifications</span>
                <button class="notif-mark-all" onclick="markAllRead()">
                    <i class="fa fa-check-double"></i> Mark all read
                </button>
            </div>
            <div class="notif-list" id="notifList">
                <div class="notif-empty">
                    <i class="fa fa-bell-slash"></i>
                    <p>No notifications yet</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
