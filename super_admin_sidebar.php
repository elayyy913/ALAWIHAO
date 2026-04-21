<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
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
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1500;
        background-color: var(--sage);
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.2rem;
        display: none; /* Default na tago hangga't bukas ang sidebar */
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: var(--transition);
    }
    .open-sidebar-btn:hover { background-color: var(--dark-sage); }

    /* 2. SIDEBAR CORE STYLE */
    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        background-color: var(--pure-white);
        color: var(--text-main);
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        z-index: 2000;
        border-right: 1px solid var(--border-color);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        transition: transform 0.3s ease-in-out;
    }

    /* Slide out animation */
    .sidebar.is-hidden {
        transform: translateX(-100%);
    }

    /* 3. CLOSE BUTTON (X) */
    .close-sidebar-x {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 1.8rem;
        color: var(--text-muted);
        cursor: pointer;
        background: none;
        border: none;
        line-height: 1;
        transition: var(--transition);
    }
    .close-sidebar-x:hover { color: #E53E3E; }

    /* Header styling */
    .sidebar-header {
        padding: 40px 30px;
        border-bottom: 1px solid var(--border-color);
        position: relative;
    }
    .brand-name {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-main);
        letter-spacing: -0.5px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .brand-name span { color: var(--sage); }
    .sidebar-header p {
        font-size: 0.65rem;
        color: var(--text-muted);
        margin: 4px 0 0 0;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-weight: 600;
    }

    /* Menu styling */
    .nav-menu {
        flex-grow: 1;
        padding: 30px 15px;
        overflow-y: auto;
    }
    .nav-label {
        font-size: 0.65rem;
        color: var(--text-muted);
        text-transform: uppercase;
        display: block;
        margin: 20px 0 8px 15px;
        font-weight: 700;
        letter-spacing: 1px;
    }
    .nav-link, .dropdown-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 12px 18px;
        color: var(--text-main);
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 4px;
        transition: var(--transition);
        font-size: 0.9rem;
        font-weight: 500;
        border: none;
        background: none;
        cursor: pointer;
        text-align: left;
    }
    .nav-link:hover, .dropdown-btn:hover {
        background-color: var(--soft-sage);
        color: var(--dark-sage);
    }
    .nav-link.active, .dropdown-btn.active-parent {
        background-color: var(--sage);
        color: var(--pure-white) !important;
        font-weight: 600;
    }

    /* Dropdown container */
    .dropdown-container {
        display: none;
        background-color: #f9fbf7;
        border-radius: 8px;
        margin: 4px 0 10px 0;
        padding: 5px 0;
    }
    .dropdown-container a {
        padding: 10px 18px 10px 40px;
        text-decoration: none;
        font-size: 0.85rem;
        color: var(--text-main);
        display: block;
        transition: var(--transition);
        border-radius: 5px;
        margin: 0 10px;
    }
    .dropdown-container a:hover { background-color: var(--soft-sage); }
    .chevron { font-size: 0.7rem; transition: transform 0.3s; }
    .rotate { transform: rotate(180deg); }

    .sidebar-footer {
        padding: 20px 15px;
        border-top: 1px solid var(--border-color);
    }
    .logout-btn {
        display: block;
        color: #E53E3E;
        text-decoration: none;
        padding: 12px 18px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 8px;
        transition: var(--transition);
    }
    .logout-btn:hover { background: #FFF5F5; }

    /* DYNAMIC ADJUSTMENT: Ito ang magagalaw sa dashboard mo */
    .main-content, #main {
        margin-left: var(--sidebar-width);
        transition: margin-left 0.3s ease-in-out;
    }
    
    /* Kapag ang sidebar ay naka-hide, dapat mag-0 margin ang content */
    .sidebar.is-hidden ~ .main-content,
    .sidebar.is-hidden ~ #main {
        margin-left: 0 !important;
    }
</style>
</head>
<body>

<button class="open-sidebar-btn" id="hamBtn" onclick="showSidebar()">&#9776;</button>

<nav class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <button class="close-sidebar-x" onclick="hideSidebar()">&times;</button>
        <div class="brand-name">ALAWIHAO <span>CENTER</span></div>
        <p>Administrative Control</p>
    </div>

    <div class="nav-menu">
        <a href="super_admin_dashboard.php" class="nav-link" id="link-overview">Overview</a>
        
        <span class="nav-label">(inventory)</span>
        <a href="admin_health_workers.php" class="nav-link" id="link-workers">Health Worker</a>
        <a href="admin_vaccines.php" class="nav-link" id="link-vaccines">Vaccines</a>
        <a href="admin_maternal_rec.php" class="nav-link" id="link-maternal-rec">Maternal Records</a>
        <a href="admin_child_rec.php" class="nav-link" id="link-child-rec">Child/Infant Records</a>

        <span class="nav-label">(management)</span>
        <button class="dropdown-btn" onclick="toggleDropdown('maternalDrop', this)">
            Maternal Management <span class="chevron">▼</span>
        </button>
        <div class="dropdown-container" id="maternalDrop">
            <a href="admin_maternal_reg.php" id="link-maternal-reg">Registration Form</a>
            <a href="admin_maternal_hr.php" id="id-maternal-health">Maternal Health Record</a>
        </div>

        <button class="dropdown-btn" onclick="toggleDropdown('childDrop', this)">
            Child Management <span class="chevron">▼</span>
        </button>
        <div class="dropdown-container" id="childDrop">
            <a href="admin_child_reg.php" id="link-child-reg">Registration Form</a>
            <a href="admin_child_hr.php" id="id-child-health">Child Health Record</a>
        </div>

        <button class="dropdown-btn" onclick="toggleDropdown('schedDrop', this)">
            Schedule Management <span class="chevron">▼</span>
        </button>
        <div class="dropdown-container" id="schedDrop">
            <a href="admin_sched_maternal.php" id="link-sched-maternal">Maternal Schedule</a>
            <a href="admin_sched_child.php" id="link-sched-child">Child Schedule</a>
        </div>

        <span class="nav-label">(others)</span>
        <a href="admin_history.php" class="nav-link" id="link-history">History</a>
        <a href="admin_settings.php" class="nav-link" id="link-settings">Setting</a>
    </div>

    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">Log out</a>
    </div>
</nav>

<script>
// Function para itago ang sidebar at i-adjust ang dashboard space
function hideSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const hamBtn = document.getElementById('hamBtn');
    const main = document.getElementById('main') || document.querySelector('.main-content');

    sidebar.classList.add('is-hidden');
    hamBtn.style.display = 'block';
    
    if(main) {
        main.style.marginLeft = "0";
    }
}

// Function para ibalik ang sidebar at ibalik ang margin ng dashboard
function showSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const hamBtn = document.getElementById('hamBtn');
    const main = document.getElementById('main') || document.querySelector('.main-content');

    sidebar.classList.remove('is-hidden');
    hamBtn.style.display = 'none';
    
    if(main) {
        main.style.marginLeft = "280px";
    }
}

function toggleDropdown(id, btn) {
    const dropdown = document.getElementById(id);
    const chevron = btn.querySelector('.chevron');
    dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
    chevron.classList.toggle('rotate');
}

// Active link logic
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname.split("/").pop();
    const links = {
        'super_admin_dashboard.php': 'link-overview',
        'admin_health_workers.php': 'link-workers',
        'admin_vaccines.php': 'link-vaccines',
        'admin_maternal_rec.php': 'link-maternal-rec',
        'admin_child_rec.php': 'link-child-rec',
        'admin_maternal_reg.php': 'link-maternal-reg',
        'admin_maternal_hr.php': 'id-maternal-health',
        'admin_child_reg.php': 'link-child-reg',
        'admin_child_hr.php': 'id-child-health',
        'admin_sched_maternal.php': 'link-sched-maternal',
        'admin_sched_child.php': 'link-sched-child',
        'admin_history.php': 'link-history',
        'admin_settings.php': 'link-settings'
    };

    if (links[currentPath]) {
        const activeLink = document.getElementById(links[currentPath]);
        if (activeLink) {
            activeLink.classList.add('active');
            const parentDropdown = activeLink.closest('.dropdown-container');
            if (parentDropdown) {
                parentDropdown.style.display = 'block';
                const btn = parentDropdown.previousElementSibling;
                btn.classList.add('active-parent');
                const chevron = btn.querySelector('.chevron');
                if(chevron) chevron.classList.add('rotate');
            }
        }
    }
});
</script>
</body>
</html>