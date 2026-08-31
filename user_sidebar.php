<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
$is_home = ($current_page == 'user_dashboard.php');
?>

<!-- HAMBURGER BUTTON -->
<button id="hamburgerBtn" class="hamburger-btn" onclick="toggleSidebar()">
    &#9776;
</button>

<nav class="sidebar" id="mySidebar">
    <div class="sidebar-header">
        <button class="close-sidebar-x" onclick="toggleSidebar()">&times;</button>
        <div class="brand-name">ALAWIHAO <span>CENTER</span></div>
        <p>Patient Access Portal</p>
    </div>

    <div class="nav-menu">
        <span class="nav-label">(overview)</span>
        <a href="user_dashboard.php" class="nav-link" id="link-home">Home</a>

        <span class="nav-label">(services)</span>
        <button class="dropdown-btn" onclick="toggleDropdown('regDrop', this)">
            Registration <span class="chevron">▼</span>
        </button>
        <div class="dropdown-container" id="regDrop">
            <a href="user_maternal_reg.php" id="link-reg-pregnancy">Maternal Form</a>
            <a href="user_infant_reg.php" id="link-reg-newborn">Child Form</a>
        </div>

        <button class="dropdown-btn" onclick="toggleDropdown('recordsDrop', this)">
            My Records <span class="chevron">▼</span>
        </button>
        <div class="dropdown-container" id="recordsDrop">
            <a href="user_maternal_records.php" id="link-maternal-rec">Maternal Health</a>
            <a href="user_child_records.php" id="link-child-rec">Child Health</a>
        </div>

        <span class="nav-label">(others)</span>
        <a href="user_schedule.php" class="nav-link" id="link-schedule">Schedule</a>
        <a href="user_settings.php" class="nav-link" id="link-settings">Settings</a>
    </div>

    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">Log out</a>
    </div>
</nav>

<style>
    :root {
        --sage: #8DAE74;
        --dark-sage: #6B8E55;
        --soft-sage: #F1F5ED;
        --pure-white: #FFFFFF;
        --text-main: #2D3748;
        --text-muted: #A0AEC0;
        --sidebar-width: 260px;
        --border-color: #EDF2F7;
        --transition: all 0.3s ease-in-out;
    }

    /* HAMBURGER BUTTON STYLE */
    .hamburger-btn {
        position: fixed;
        top: 15px;
        left: 20px;
        z-index: 1000;
        font-size: 1.5rem;
        background: none;
        border: none;
        cursor: pointer;
        display: none; 
        color: var(--text-main);
    }

    @media (max-width: 768px) {
        .hamburger-btn { display: block; }
    }

    .hamburger-btn.show-hamburger {
        display: block !important;
    }

    /* SIDEBAR CORE STYLE - Naka-open by default */
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
        box-shadow: 4px 0 15px rgba(0,0,0,0.03);
        transform: translateX(0%);
    }

    /* Kapag ang body ay may class na sidebar-closed, dito siya papasok para magsara */
    body.sidebar-closed .sidebar {
        transform: translateX(-100%) !important;
    }

    /* CLOSE BUTTON (X) SA LOOB NG SIDEBAR */
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
        padding: 30px 20px;
        border-bottom: 1px solid var(--border-color);
        position: relative;
    }
    .brand-name {
        font-size: 1.1rem;
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
        padding: 15px 10px;
        overflow-y: auto;
    }
    .nav-label {
        font-size: 0.65rem;
        color: var(--text-muted);
        text-transform: uppercase;
        display: block;
        margin: 15px 0 6px 15px;
        font-weight: 700;
        letter-spacing: 1px;
    }
    .nav-link, .dropdown-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 10px 15px;
        color: var(--text-main);
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 4px;
        transition: var(--transition);
        font-size: 0.88rem;
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
        margin: 4px 0 8px 0;
        padding: 5px 0;
    }
    .dropdown-container a {
        padding: 8px 15px 8px 35px;
        text-decoration: none;
        font-size: 0.82rem;
        color: var(--text-main);
        display: block;
        transition: var(--transition);
        border-radius: 5px;
        margin: 0 8px;
    }
    .dropdown-container a:hover { background-color: var(--soft-sage); color: var(--dark-sage); }
    .chevron { font-size: 0.7rem; transition: transform 0.3s; }
    .rotate { transform: rotate(180deg); }

    .sidebar-footer {
        padding: 15px 10px;
        border-top: 1px solid var(--border-color);
    }
    .logout-btn {
        display: block;
        color: #E53E3E;
        text-decoration: none;
        padding: 10px 15px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 8px;
        transition: var(--transition);
    }
    .logout-btn:hover { background: #FFF5F5; }
</style>

<script>
    function toggleDropdown(id, btn) {
        const dropdown = document.getElementById(id);
        if(dropdown) {
            dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
        }
        if(btn) {
            const chevron = btn.querySelector('.chevron');
            if(chevron) chevron.classList.toggle('rotate');
        }
    }

    function toggleSidebar() {
        // Dinadagdag/Tinatanggal ang class sa <body> para sumunod ang CSS
        document.body.classList.toggle('sidebar-closed');
        
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        if (hamburgerBtn) {
            hamburgerBtn.classList.toggle('show-hamburger');
        }
    }

    // Auto-active link highlight based on current page
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname.split("/").pop();
        const links = {
            'user_dashboard.php': 'link-home',
            'user_maternal_reg.php': 'link-reg-pregnancy',
            'user_infant_reg.php': 'link-reg-newborn',
            'user_maternal_records.php': 'link-maternal-rec',
            'user_child_records.php': 'link-child-rec',
            'user_schedule.php': 'link-schedule',
            'user_settings.php': 'link-settings'
        };

        if (links[currentPath]) {
            const activeLink = document.getElementById(links[currentPath]);
            if (activeLink) {
                activeLink.classList.add('active');
                const parentDropdown = activeLink.closest('.dropdown-container');
                if (parentDropdown) {
                    parentDropdown.style.display = 'block';
                    const btn = parentDropdown.previousElementSibling;
                    if(btn) {
                        btn.classList.add('active-parent');
                        const chevron = btn.querySelector('.chevron');
                        if(chevron) chevron.classList.add('rotate');
                    }
                }
            }
        }
    });
</script>