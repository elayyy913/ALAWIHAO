<?php
/**
 * notif_assets.php — Notification CSS + Theme engine.
 * Include before </head>. Set $notif_path before including.
 *   $notif_path = '../';  // admin/ subfolder
 *   $notif_path = '';     // root pages
 */
$notif_path = $notif_path ?? '';
?>
<?php /* theme.js must load FIRST — before any CSS renders — to avoid flash of wrong theme */ ?>
<script src="<?php echo $notif_path; ?>theme.js"></script>
<link rel="stylesheet" href="<?php echo $notif_path; ?>theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ── NOTIFICATION BELL ─────────────────────────────────────── */
.notif-wrapper { position: relative; display: inline-block; }
.notif-bell-btn {
    background: none; border: none; cursor: pointer;
    font-size: 1.3rem; color: #6B8E55; position: relative;
    padding: 6px 10px; border-radius: 8px; transition: background 0.2s;
    display: inline-flex; align-items: center;
}
.notif-bell-btn:hover { background: #F1F5ED; }
#notifBellBadge {
    position: absolute; top: 2px; right: 4px;
    background: #e53e3e; color: white;
    font-size: 0.6rem; font-weight: 700;
    width: 17px; height: 17px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}
/* Dropdown is FIXED so it always appears on top of everything */
.notif-dropdown {
    display: none;
    position: fixed;
    width: 320px;
    background: white;
    border: 1px solid #E1E1D7;
    border-radius: 12px;
    box-shadow: 0 8px 28px rgba(0,0,0,0.14);
    z-index: 99999;
    overflow: hidden;
}
.notif-dropdown.open { display: block; }
.notif-dropdown-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 16px; border-bottom: 1px solid #E1E1D7;
    font-weight: 700; font-size: 0.9rem; color: #6B8E55;
}
.notif-mark-all {
    background: none; border: none; font-size: 0.75rem;
    color: #888; cursor: pointer; font-weight: 600;
}
.notif-mark-all:hover { color: #6B8E55; }
.notif-list { max-height: 320px; overflow-y: auto; }
.notif-item {
    padding: 12px 16px; border-bottom: 1px solid #f0f0f0;
    cursor: pointer; transition: background 0.15s;
}
.notif-item:hover { background: #F9F9F4; }
.notif-item.unread { background: #f0f5eb; }
.notif-item .notif-title { font-size: 0.85rem; font-weight: 700; color: #2D2D2D; margin-bottom: 3px; }
.notif-item .notif-msg  { font-size: 0.78rem; color: #666; line-height: 1.4; }
.notif-item .notif-time { font-size: 0.72rem; color: #aaa; margin-top: 4px; }
.notif-empty { text-align: center; padding: 30px 16px; color: #aaa; font-size: 0.85rem; }
.notif-empty i { font-size: 1.8rem; margin-bottom: 8px; display: block; }
</style>
