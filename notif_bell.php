<?php ?>
<?php if (isset($_SESSION['user_id'])): ?>
    <div class="notif-wrapper" id="notifWrapper">
        <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifPanel(event)" title="Notifications">
            <i class="fa fa-bell"></i>
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
