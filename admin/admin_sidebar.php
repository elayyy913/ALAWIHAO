<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_role = $_SESSION['role'] ?? 'Admin';
$is_super_admin = (strtolower(trim($current_role)) === 'super admin' || strtolower(trim($current_role)) === 'superadmin');

$script_directory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if (basename($script_directory) === 'admin') {
    $script_directory = rtrim(dirname($script_directory), '/');
}
$project_base_url = ($script_directory === '' || $script_directory === '.') ? '/' : $script_directory . '/';
$base_url = $project_base_url . 'admin/';
$dashboard_url = $is_super_admin ? $base_url . 'super_admin_dashboard.php' : $base_url . 'admin_dashboard.php';

// Active states logic para sa mga links at dropdowns
$is_overview_active = in_array($current_page, ['admin_dashboard.php', 'super_admin_dashboard.php']);
$is_workers_active  = in_array($current_page, ['admin_health_workers.php', 'register_worker.php']);
$is_vaccines_active = ($current_page === 'vaccine_inventory.php');
$is_mat_rec_active  = ($current_page === 'admin_maternal_rec.php');
$is_child_rec_active= ($current_page === 'admin_child_rec.php');
$is_mat_reg_active  = ($current_page === 'admin_maternal_reg.php');
$is_child_reg_active = ($current_page === 'admin_child_reg.php');

$is_mat_hr_active   = in_array($current_page, ['admin_maternal_hr.php', 'admin_maternal_history.php']);
$is_maternal_open   = $is_mat_reg_active || $is_mat_hr_active || (!$is_super_admin && $is_mat_rec_active);

$is_child_hr_active = in_array($current_page, ['admin_child_hr.php', 'admin_child_history.php']);
$is_child_open      = $is_child_reg_active || $is_child_rec_active || $is_child_hr_active;
$maternal_management_expanded = $is_mat_reg_active || $is_mat_hr_active;
$child_management_expanded = $is_child_reg_active || $is_child_hr_active;

$is_sched_active    = in_array($current_page, ['schedule_management.php', 'admin_sched_maternal.php', 'admin_sched_child.php']);
$is_logs_active     = ($current_page === 'activity_logs.php');
$is_sms_active      = ($current_page === 'sms_management.php');
$is_settings_active = in_array($current_page, ['admin_settings.php', 'super_admin_setting.php']);
$is_community_active = ($current_page === 'community.php');
?>

<script src="<?php echo htmlspecialchars($project_base_url . 'modern_confirm.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>

<script>
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>

<style>
    :root {
        --sage: #8DAE74;
        --dark-sage: #6B8E55;
        --soft-sage: #F1F5ED;
        --pure-white: #FFFFFF;
        --text-main: #2D3748;
        --text-muted: #A0AEC0;
        --sidebar-width: 280px;
        --border-color: #EDF2F7;
        --transition: all 0.3s ease-in-out;
    }

    /* 1. HAMBURGER BUTTON - Floating sa labas */
    .open-sidebar-btn {
        position: fixed !important;
        top: 20px !important;
        left: 20px !important;
        z-index: 1500 !important;
        background-color: var(--sage) !important;
        color: white !important;
        border: none !important;
        padding: 10px 15px !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        font-size: 1.2rem !important;
        display: none;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1) !important;
        transition: var(--transition) !important;
    }
    .open-sidebar-btn:hover { background-color: var(--dark-sage) !important; }

    /* 2. SIDEBAR CORE STYLE */
    .sidebar {
        width: var(--sidebar-width) !important;
        height: 100vh !important;
        overflow-x: hidden !important;
        background-color: var(--pure-white) !important;
        color: var(--text-main) !important;
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        z-index: 2000 !important;
        border-right: 1px solid var(--border-color) !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-sizing: border-box !important;
    }

    .sidebar.is-hidden {
        transform: translateX(-100%) !important;
    }

    /* 3. CLOSE BUTTON (X) */
    .close-sidebar-x {
        position: absolute !important;
        top: 15px !important;
        right: 20px !important;
        font-size: 1.8rem !important;
        color: var(--text-muted) !important;
        cursor: pointer !important;
        background: none !important;
        border: none !important;
        line-height: 1 !important;
        transition: var(--transition) !important;
    }
    .close-sidebar-x:hover { color: #E53E3E !important; }

    /* Header styling with Side-by-Side Logo & Text */
    .sidebar-header {
        padding: 30px 20px !important;
        border-bottom: 1px solid var(--border-color) !important;
        position: relative !important;
    }
    .sidebar-header-content {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
    }
    .sidebar-logo {
        width: 45px !important;
        height: 45px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        border: 1.5px solid var(--sage) !important;
        flex-shrink: 0 !important;
    }
    .brand-name {
        font-size: 1.05rem !important;
        font-weight: 800 !important;
        color: var(--text-main) !important;
        letter-spacing: -0.5px !important;
        text-transform: uppercase !important;
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 4px !important;
        line-height: 1.2 !important;
    }
    .brand-name span { color: var(--sage) !important; }

    .sidebar-header p {
        font-size: 0.65rem !important;
        color: var(--text-muted) !important;
        margin: 4px 0 0 0 !important;
        text-transform: uppercase !important;
        letter-spacing: 1.5px !important;
        font-weight: 600 !important;
    }

    /* Menu styling */
    .nav-menu {
        flex-grow: 1 !important;
        min-width: 0 !important;
        padding: 24px 15px !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        box-sizing: border-box !important;
    }
    .nav-label {
        font-size: 0.65rem !important;
        color: var(--text-muted) !important;
        text-transform: uppercase !important;
        display: block !important;
        margin: 18px 0 6px 15px !important;
        font-weight: 700 !important;
        letter-spacing: 1px !important;
    }
    .nav-link, .dropdown-btn, .management-heading {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 100% !important;
        padding: 11px 16px !important;
        color: var(--text-main) !important;
        text-decoration: none !important;
        border-radius: 8px !important;
        margin-bottom: 4px !important;
        transition: var(--transition) !important;
        font-size: 0.88rem !important;
        font-weight: 500 !important;
        border: none !important;
        background: none !important;
        cursor: pointer !important;
        text-align: left !important;
        box-sizing: border-box !important;
    }
    .community-unread-dot {
        width: 9px !important;
        height: 9px !important;
        flex: 0 0 9px !important;
        margin-left: auto !important;
        margin-right: 4px !important;
        border-radius: 50% !important;
        background: #2f9e44 !important;
        box-shadow: 0 0 0 3px rgba(47, 158, 68, 0.15) !important;
    }
    .community-unread-dot[hidden] { display: none !important; }
    .vaccine-expired-dot {
        width: 9px !important;
        height: 9px !important;
        flex: 0 0 9px !important;
        margin-left: auto !important;
        margin-right: 4px !important;
        border-radius: 50% !important;
        background: #d93025 !important;
        box-shadow: 0 0 0 3px rgba(217, 48, 37, 0.15) !important;
    }
    .management-heading {
        cursor: default !important;
        margin-top: 2px !important;
        margin-bottom: 0 !important;
        padding-top: 9px !important;
        padding-bottom: 7px !important;
    }
    .management-toggle {
        cursor: pointer !important;
    }
    .nav-link:hover, .dropdown-btn:hover {
        background-color: var(--soft-sage) !important;
        color: var(--dark-sage) !important;
    }
    .nav-link.active, .dropdown-btn.active-parent {
        background-color: var(--sage) !important;
        color: var(--pure-white) !important;
        font-weight: 600 !important;
    }

    /* Dropdown container */
    .dropdown-container {
        display: none;
        background-color: #f9fbf7 !important;
        border-radius: 8px !important;
        margin: 4px 0 8px 0 !important;
        padding: 4px 0 !important;
    }
    .dropdown-container.show {
        display: block !important;
    }
    .dropdown-container a {
        padding: 9px 16px 9px 36px !important;
        text-decoration: none !important;
        font-size: 0.84rem !important;
        color: var(--text-main) !important;
        display: block !important;
        transition: var(--transition) !important;
        border-radius: 6px !important;
        margin: 2px 8px !important;
        font-weight: 500 !important;
    }
    .dropdown-container a:hover {
        background-color: var(--soft-sage) !important;
        color: var(--dark-sage) !important;
    }
    .dropdown-container a.active {
        background-color: var(--soft-sage) !important;
        color: var(--dark-sage) !important;
        font-weight: 700 !important;
    }
    .chevron { font-size: 0.7rem !important; transition: transform 0.3s !important; }
    .rotate { transform: rotate(180deg) !important; }

    .sidebar-footer {
        padding: 18px 15px !important;
        border-top: 1px solid var(--border-color) !important;
    }
    .logout-btn {
        display: block !important;
        color: #E53E3E !important;
        text-decoration: none !important;
        padding: 11px 16px !important;
        font-size: 0.88rem !important;
        font-weight: 600 !important;
        border-radius: 8px !important;
        transition: var(--transition) !important;
    }
    .logout-btn:hover { background: #FFF5F5 !important; }













    /* DYNAMIC ADJUSTMENT FOR MAIN CONTENT */
    .main-content, #main, #main-wrapper {
        margin-left: var(--sidebar-width) !important;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-sizing: border-box !important;
    }

    .sidebar.is-hidden ~ .main-content,
    .sidebar.is-hidden ~ #main,
    .sidebar.is-hidden ~ #main-wrapper {
        margin-left: 0 !important;
    }
</style>

<!-- Hamburger Button -->
<button class="open-sidebar-btn" id="hamBtn" onclick="showSidebar()" title="Open Navigation">&#9776;</button>

<!-- Unified Sidebar Navigation -->
<nav class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <button class="close-sidebar-x" onclick="hideSidebar()" title="Close Navigation">&times;</button>
        <div class="sidebar-header-content">
            <img src="<?php echo $project_base_url; ?>images/logo.jpg" alt="Barangay Alawihao Logo" class="sidebar-logo">
            <div>
                <div class="brand-name">ALAWIHAO <span>CENTER</span></div>
                <p>Administrative Control</p>
            </div>
        </div>
    </div>

    <div class="nav-menu">
        <a href="<?php echo $dashboard_url; ?>" class="nav-link <?php echo $is_overview_active ? 'active' : ''; ?>" id="link-overview">Home</a>

        <?php if ($is_super_admin): ?>
        <span class="nav-label">(inventory)</span>
        <a href="<?php echo $base_url; ?>admin_health_workers.php" class="nav-link <?php echo $is_workers_active ? 'active' : ''; ?>" id="link-workers">Health Worker</a>
        <a href="<?php echo $base_url; ?>vaccine_inventory.php" class="nav-link <?php echo $is_vaccines_active ? 'active' : ''; ?>" id="link-vaccines">
            Vaccines
            <?php if ($is_super_admin && !empty($expired_vaccine_batch_count)): ?>
                <span class="vaccine-expired-dot" aria-label="<?php echo (int) $expired_vaccine_batch_count; ?> expired vaccine batches" title="<?php echo (int) $expired_vaccine_batch_count; ?> expired vaccine batch(es)"></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo $base_url; ?>admin_maternal_rec.php" class="nav-link <?php echo $is_mat_rec_active ? 'active' : ''; ?>" id="link-maternal-rec">Maternal Records</a>
        <a href="<?php echo $base_url; ?>admin_child_rec.php" class="nav-link <?php echo $is_child_rec_active ? 'active' : ''; ?>" id="link-child-rec">Child/Infant Records</a>
        <?php endif; ?>

        <span class="nav-label">(managements)</span>
        <?php if ($is_super_admin): ?>
        <button type="button" class="management-heading management-toggle dropdown-btn <?php echo $maternal_management_expanded ? 'active-parent' : ''; ?>" onclick="toggleDropdown('maternalDrop', this)" aria-controls="maternalDrop" aria-expanded="<?php echo $maternal_management_expanded ? 'true' : 'false'; ?>">
            Maternal Management <span class="chevron <?php echo $maternal_management_expanded ? 'rotate' : ''; ?>" aria-hidden="true">▼</span>
        </button>
        <?php else: ?>
        <div class="management-heading">Maternal Management</div>
        <?php endif; ?>
        <div class="dropdown-container management-links <?php echo (!$is_super_admin || $maternal_management_expanded) ? 'show' : ''; ?>" id="maternalDrop"<?php echo $is_super_admin ? ' style="display: ' . ($maternal_management_expanded ? 'block' : 'none') . ';"' : ''; ?>>
            <a href="<?php echo $base_url; ?>admin_maternal_reg.php" class="<?php echo $is_mat_reg_active ? 'active' : ''; ?>" id="link-maternal-reg">Registration Form</a>
            <?php if (!$is_super_admin): ?>
            <a href="<?php echo $base_url; ?>admin_maternal_rec.php" class="<?php echo $is_mat_rec_active ? 'active' : ''; ?>" id="link-maternal-rec">Maternal Records</a>
            <?php endif; ?>
            <?php if ($is_super_admin): ?>
            <a href="<?php echo $base_url; ?>admin_maternal_hr.php" class="<?php echo $is_mat_hr_active ? 'active' : ''; ?>" id="id-maternal-health">Maternal Health Record</a>
            <?php endif; ?>
        </div>

        <?php if ($is_super_admin): ?>
        <button type="button" class="management-heading management-toggle dropdown-btn <?php echo $child_management_expanded ? 'active-parent' : ''; ?>" onclick="toggleDropdown('childDrop', this)" aria-controls="childDrop" aria-expanded="<?php echo $child_management_expanded ? 'true' : 'false'; ?>">
            Child Management <span class="chevron <?php echo $child_management_expanded ? 'rotate' : ''; ?>" aria-hidden="true">▼</span>
        </button>
        <?php else: ?>
        <div class="management-heading">Child Management</div>
        <?php endif; ?>
        <div class="dropdown-container management-links <?php echo (!$is_super_admin || $child_management_expanded) ? 'show' : ''; ?>" id="childDrop"<?php echo $is_super_admin ? ' style="display: ' . ($child_management_expanded ? 'block' : 'none') . ';"' : ''; ?>>
            <a href="<?php echo $base_url; ?>admin_child_reg.php" class="<?php echo $is_child_reg_active ? 'active' : ''; ?>" id="link-child-reg">Registration Form</a>
            <?php if (!$is_super_admin): ?>
            <a href="<?php echo $base_url; ?>admin_child_rec.php" class="<?php echo $is_child_rec_active ? 'active' : ''; ?>" id="link-child-rec">Child/Infant Records</a>
            <?php endif; ?>
            <?php if ($is_super_admin): ?>
            <a href="<?php echo $base_url; ?>admin_child_hr.php" class="<?php echo $is_child_hr_active ? 'active' : ''; ?>" id="id-child-health">Child Health Record</a>
            <?php endif; ?>
        </div>

        <a href="<?php echo $base_url; ?>schedule_management.php" class="nav-link <?php echo $is_sched_active ? 'active' : ''; ?>" id="link-schedule">Schedule Management</a>
        <a href="<?php echo $project_base_url; ?>community.php" class="nav-link <?php echo $is_community_active ? 'active' : ''; ?>" id="link-community" data-user-id="<?php echo (int) ($_SESSION['user_id'] ?? 0); ?>">
            Community <span class="community-unread-dot" data-community-unread-dot aria-label="May bagong mensahe" hidden></span>
        </a>
        <script src="<?php echo $project_base_url; ?>community_chat_notifications.js" defer></script>

        <?php if ($is_super_admin): ?>
        <span class="nav-label">(others)</span>
        <a href="<?php echo $base_url; ?>sms_management.php" class="nav-link <?php echo $is_sms_active ? 'active' : ''; ?>" id="link-sms">SMS Management</a>
        <a href="<?php echo $base_url; ?>activity_logs.php" class="nav-link <?php echo $is_logs_active ? 'active' : ''; ?>" id="link-history">History</a>
        <?php endif; ?>
        <span class="nav-label">(settings)</span>
        <?php if ($is_super_admin): ?>
        <a href="<?php echo $base_url; ?>super_admin_setting.php" class="nav-link <?php echo $is_settings_active ? 'active' : ''; ?>" id="link-settings">Super Admin Settings</a>
        <?php else: ?>
        <a href="<?php echo $base_url; ?>admin_settings.php" class="nav-link <?php echo $is_settings_active ? 'active' : ''; ?>" id="link-settings">Settings</a>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <a href="<?php echo $project_base_url; ?>logout.php" class="logout-btn">Log out</a>
    </div>
</nav>

<script>
function hideSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const hamBtn = document.getElementById('hamBtn');
    const main = document.getElementById('main') || document.querySelector('.main-content') || document.getElementById('main-wrapper');
    const footer = document.querySelector('.site-footer-wrap');
    const topbar = document.querySelector('.topbar');

    if (sidebar) sidebar.classList.add('is-hidden');
    if (hamBtn) hamBtn.style.display = 'block';
    if (main) main.style.setProperty('margin-left', '0', 'important');
    if (topbar) {
        topbar.style.setProperty('margin-left', '0', 'important');
        topbar.style.setProperty('width', '100%', 'important');
    }
    syncSidebarToggleSpacing();
    if (footer) {
        footer.style.setProperty('margin-left', '0', 'important');
        footer.style.setProperty('width', '100%', 'important');
    }
}

function showSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const hamBtn = document.getElementById('hamBtn');
    const main = document.getElementById('main') || document.querySelector('.main-content') || document.getElementById('main-wrapper');
    const footer = document.querySelector('.site-footer-wrap');
    const topbar = document.querySelector('.topbar');

    if (sidebar) sidebar.classList.remove('is-hidden');
    if (hamBtn) hamBtn.style.display = 'none';
    if (main) main.style.setProperty('margin-left', '280px', 'important');
    if (topbar) {
        topbar.style.setProperty('margin-left', '280px', 'important');
        topbar.style.setProperty('width', 'calc(100% - 280px)', 'important');
    }
    syncSidebarToggleSpacing();
    if (footer) {
        footer.style.setProperty('margin-left', '280px', 'important');
        footer.style.setProperty('width', 'calc(100% - 280px)', 'important');
    }
}

function syncSidebarToggleSpacing() {
    const hamBtn = document.getElementById('hamBtn');
    const main = document.getElementById('main') || document.querySelector('.main-content') || document.getElementById('main-wrapper');
    if (!hamBtn || !main) return;

    const toggleIsVisible = window.getComputedStyle(hamBtn).display !== 'none'
        && window.getComputedStyle(hamBtn).visibility !== 'hidden';
    if (toggleIsVisible) {
        main.style.setProperty('padding-top', '78px', 'important');
    } else {
        main.style.removeProperty('padding-top');
    }
}

function toggleDropdown(id, btn) {
    const dropdown = document.getElementById(id);
    if (dropdown) {
        const isShown = dropdown.classList.toggle('show');
        dropdown.style.display = isShown ? 'block' : 'none';
        if (btn) {
            btn.setAttribute('aria-expanded', isShown ? 'true' : 'false');
            btn.classList.toggle('active-parent', isShown);
        }
    }
    if (btn) {
        const chevron = btn.querySelector('.chevron');
        if (chevron) chevron.classList.toggle('rotate');
    }
}

/* Backward compatibility aliases */
function openNav() { showSidebar(); }
function closeNav() { hideSidebar(); }
function toggleSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    if (sidebar && sidebar.classList.contains('is-hidden')) {
        showSidebar();
    } else {
        hideSidebar();
    }
}
function toggleDrop(id) {
    const dropdown = document.getElementById(id);
    const btn = dropdown ? dropdown.previousElementSibling : null;
    toggleDropdown(id, btn);
}

document.addEventListener('DOMContentLoaded', function() {
    syncSidebarToggleSpacing();
    const currentPath = window.location.pathname.split("/").pop();
    const links = {
        'super_admin_dashboard.php': 'link-overview',
        'admin_dashboard.php': 'link-overview',
        'admin_health_workers.php': 'link-workers',
        'vaccine_inventory.php': 'link-vaccines',
        'admin_maternal_rec.php': 'link-maternal-rec',
        'admin_child_rec.php': 'link-child-rec',
        'admin_maternal_reg.php': 'link-maternal-reg',
        'admin_maternal_hr.php': 'id-maternal-health',
        'admin_maternal_history.php': 'id-maternal-health',
        'admin_child_reg.php': 'link-child-reg',
        'admin_child_hr.php': 'id-child-health',
        'admin_child_history.php': 'id-child-health',
        'schedule_management.php': 'link-schedule',
        'admin_sched_maternal.php': 'link-schedule',
        'admin_sched_child.php': 'link-schedule',
        'sms_management.php': 'link-sms',
        'activity_logs.php': 'link-history',
        'admin_settings.php': 'link-settings',
        'super_admin_setting.php': 'link-settings',
        'register_worker.php': 'link-workers'
    };

    if (links[currentPath]) {
        const activeLink = document.getElementById(links[currentPath]);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
});
window.addEventListener('resize', syncSidebarToggleSpacing);
</script>
<?php include __DIR__ . '/admin_help_chat.php'; ?>