<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
$is_home = ($current_page == 'main_user.php');
// Kung home, naka-hide, kung hindi, nakalabas.
$sidebar_style = $is_home ? "transform: translateX(-100%);" : "transform: translateX(0%);";
?>

<!-- Floating Hamburger Button -->
<button class="open-sidebar-btn" id="hamBtn" onclick="toggleNav()" style="<?php echo $is_home ? 'display: block;' : 'display: none;'; ?>">&#9776;</button>

<nav class="sidebar" id="mySidebar" style="<?php echo $sidebar_style; ?>">
    <div class="sidebar-header">
        <button class="close-sidebar-x" onclick="closeNav()">&times;</button>
        <div class="brand-name">ALAWIHAO <span>CENTER</span></div>
        <p>Patient Access Portal</p>
    </div>

    <div class="nav-menu">
        <span class="nav-label">(overview)</span>
        <a href="main_user.php" class="nav-link" id="link-home">Home</a>
        <a href="user_profile.php" class="nav-link" id="link-profile">Profile</a>

        <span class="nav-label">(services)</span>
        <button class="dropdown-btn" onclick="toggleDropdown('regDrop', this)">
            Registration <span class="chevron">▼</span>
        </button>
        <div class="dropdown-container" id="regDrop">
            <a href="/FINAL_CAPSTONE/admin/admin_maternal_reg.php" id="link-reg-pregnancy">Maternal Form</a>
            <a href="/FINAL_CAPSTONE/admin/admin_child_reg.php" id="link-reg-newborn">Child Form</a>
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
        <a href="user_history.php" class="nav-link" id="link-history">History</a>
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
        box-shadow: 4px 0 15px rgba(0,0,0,0.03);
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
        padding: 20px 15px;
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
    .dropdown-container a:hover { background-color: var(--soft-sage); color: var(--dark-sage); }
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

    /* DYNAMIC ADJUSTMENT para sa main content */
    .main-content, #main {
        margin-left: var(--sidebar-width);
        transition: margin-left 0.3s ease-in-out;
    }
</style>

<script>
    function toggleNav() {
        const sidebar = document.getElementById("mySidebar");
        const hamBtn = document.getElementById("hamBtn");
        
        if (sidebar.style.transform === "translateX(0%)" || sidebar.style.transform === "") {
            closeNav();
        } else {
            openNav();
        }
    }

    function openNav() {
        document.getElementById("mySidebar").style.transform = "translateX(0%)";
        document.getElementById("hamBtn").style.display = "none";
        const main = document.getElementById("main") || document.querySelector('.main-content');
        if(main) main.style.marginLeft = "280px";
    }

    function closeNav() {
        document.getElementById("mySidebar").style.transform = "translateX(-100%)";
        document.getElementById("hamBtn").style.display = "block";
        const main = document.getElementById("main") || document.querySelector('.main-content');
        if(main) main.style.marginLeft = "0";
    }

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

    // Auto-active link highlight based on current page
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname.split("/").pop();
        const links = {
            'main_user.php': 'link-home',
            'user_profile.php': 'link-profile',
            'admin_maternal_reg.php': 'link-reg-pregnancy',
            'admin_child_reg.php': 'link-reg-newborn',
            'user_maternal_records.php': 'link-maternal-rec',
            'user_child_records.php': 'link-child-rec',
            'user_schedule.php': 'link-schedule',
            'user_history.php': 'link-history'
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