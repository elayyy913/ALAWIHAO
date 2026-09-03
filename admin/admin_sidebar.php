<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Active states para sa mga dropdown
$is_reg_active   = in_array($current_page, ['admin_maternal_reg.php', 'admin_child_reg.php']);
$is_rec_active   = in_array($current_page, ['admin_maternal_hr.php', 'admin_child_hr.php']);
?>

<div id="mySidenav" class="side-nav-new">
    <span class="closebtn-new" onclick="closeNav()">&times;</span>
    
    <div class="sidebar-header-new">
        <!-- BRAND CONTAINER (Logo + Text Side-by-Side) -->
        <div class="sidebar-brand-wrapper">
            <img src="../images/logo.jpg" alt="Barangay Alawihao Logo" class="sidebar-logo">
            <div class="brand-text-group">
                <div class="brand-new">ALAWIHAO <span class="highlight-new">CENTER</span></div>
                <div class="sub-brand-new">ADMINISTRATIVE CONTROL</div>
            </div>
        </div>
    </div>
    
    <div class="menu-items-new">
        <div class="menu-label-new">Overview</div>
        <a href="admin_dashboard.php" class="nav-item-new <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">Home</a>
        <a href="admin_profile.php" class="nav-item-new <?php echo ($current_page == 'admin_profile.php') ? 'active' : ''; ?>">Profile</a>

        <div class="menu-label-new">Management</div>
        
        <!-- Register Dropdown -->
        <button class="dropdown-btn-new <?php echo $is_reg_active ? 'active-parent' : ''; ?>" onclick="toggleDrop('dropReg')">
            <span>Register</span> <span class="caret-new <?php echo $is_reg_active ? 'rotate' : ''; ?>">▼</span>
        </button>
        <div id="dropReg" class="dropdown-container-new <?php echo $is_reg_active ? 'show' : ''; ?>">
            <a href="admin_maternal_reg.php" class="nav-item-sub-new <?php echo ($current_page == 'admin_maternal_reg.php') ? 'active-sub' : ''; ?>">Maternal Registration</a>
            <a href="admin_child_reg.php" class="nav-item-sub-new <?php echo ($current_page == 'admin_child_reg.php') ? 'active-sub' : ''; ?>">Child Registration</a>
        </div>

        <!-- Records Dropdown -->
        <button class="dropdown-btn-new <?php echo $is_rec_active ? 'active-parent' : ''; ?>" onclick="toggleDrop('dropRec')">
            <span>Records</span> <span class="caret-new <?php echo $is_rec_active ? 'rotate' : ''; ?>">▼</span>
        </button>
        <div id="dropRec" class="dropdown-container-new <?php echo $is_rec_active ? 'show' : ''; ?>">
            <a href="admin_maternal_hr.php" class="nav-item-sub-new <?php echo ($current_page == 'admin_maternal_hr.php') ? 'active-sub' : ''; ?>">Maternal Records</a>
            <a href="admin_child_hr.php" class="nav-item-sub-new <?php echo ($current_page == 'admin_child_hr.php') ? 'active-sub' : ''; ?>">Child Records</a>
        </div>

        <!-- Direct Schedule Management Link -->
        <a href="schedule_management.php" class="nav-item-new <?php echo ($current_page == 'schedule_management.php') ? 'active' : ''; ?>">Schedule Management</a>
        <div class="menu-label-new">Others</div>
        <a href="admin_history.php" class="nav-item-new <?php echo ($current_page == 'admin_history.php') ? 'active' : ''; ?>">History</a>
        <a href="admin_settings.php" class="nav-item-new <?php echo ($current_page == 'admin_settings.php') ? 'active' : ''; ?>">Settings</a>
    </div>
    <a href="/FINAL_CAPSTONE/logout.php" class="logout-link-new">Log out</a>
</div>

<!-- Hamburger Button -->
<button class="open-btn-new" id="hamBtn" onclick="openNav()">&#9776;</button>

<style>
    .side-nav-new {
        height: 100% !important; 
        position: fixed !important; 
        z-index: 3000 !important;
        top: 0 !important; 
        left: 0 !important; 
        width: 280px !important;
        background-color: #FFFFFF !important;
        overflow-x: hidden !important; 
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 2px 0 20px rgba(0,0,0,0.03) !important;
        display: flex !important; 
        flex-direction: column !important;
        border-right: 1px solid #F1F5F9 !important;
    }

    #main, .main-content, #main-wrapper {
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        margin-left: 280px !important;
        box-sizing: border-box !important;
    }

    .open-btn-new {
        font-size: 16px !important; 
        cursor: pointer !important; 
        background-color: #8DAE74 !important; 
        color: white !important; 
        padding: 10px 14px !important; 
        border: none !important; 
        border-radius: 8px !important;
        position: fixed !important; 
        top: 20px !important; 
        left: 20px !important; 
        z-index: 2000 !important;
        box-shadow: 0 4px 12px rgba(141, 174, 116, 0.25) !important;
        visibility: hidden;
    }
    .open-btn-new:hover { background-color: #6B8E55 !important; }

    /* Header styling with Side-by-Side Logo & Text */
    .sidebar-header-new { 
        padding: 20px 20px 15px 20px !important; 
        border-bottom: 1px solid #F1F5F9 !important;
    }

    .sidebar-brand-wrapper {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
    }

    .sidebar-logo {
        width: 42px !important;
        height: 42px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        border: 1.5px solid #8DAE74 !important;
        flex-shrink: 0 !important;
    }

    .brand-text-group {
        display: flex !important;
        flex-direction: column !important;
    }

    .brand-new { 
        font-size: 0.95rem !important; 
        font-weight: 800 !important; 
        color: #1E293B !important; 
        letter-spacing: -0.5px !important; 
        line-height: 1.2 !important;
    }
    .highlight-new { color: #8DAE74 !important; }
    .sub-brand-new { 
        font-size: 0.58rem !important; 
        color: #94A3B8 !important; 
        font-weight: 700 !important; 
        margin-top: 2px !important; 
        letter-spacing: 0.5px !important; 
    }
    
    .menu-items-new { padding: 0 12px !important; flex-grow: 1 !important; overflow-y: auto !important; }
    .menu-label-new { font-size: 0.68rem !important; font-weight: 700 !important; color: #94A3B8 !important; padding: 18px 15px 8px 15px !important; text-transform: uppercase !important; letter-spacing: 0.8px !important; }
    
    .nav-item-new, .dropdown-btn-new { 
        padding: 12px 16px !important; text-decoration: none !important; font-size: 0.9rem !important; color: #475569 !important; 
        display: flex !important; justify-content: space-between !important; align-items: center !important; 
        width: 100% !important; margin: 2px 0 !important; text-align: left !important; background: none !important; border: none !important; 
        cursor: pointer !important; border-radius: 8px !important; font-weight: 600 !important; box-sizing: border-box !important;
    }
    .nav-item-new:hover, .dropdown-btn-new:hover { background-color: #F8FAFC !important; color: #1E293B !important; }
    
    .nav-item-new.active { background-color: #F1F5ED !important; color: #6B8E55 !important; font-weight: 700 !important; }
    .dropdown-btn-new.active-parent { color: #6B8E55 !important; background-color: #F8FAFC !important; font-weight: 700 !important; }
    
    .dropdown-container-new { 
        display: none !important; 
        background-color: #FAFAF9 !important; 
        margin: 4px 0 8px 0 !important; 
        border-radius: 8px !important; 
        padding: 4px 0 !important; 
        border: 1px solid #F1F5F9 !important; 
    }
    .dropdown-container-new.show { 
        display: block !important; 
    }
    
    .nav-item-sub-new { 
        display: block !important; padding: 10px 16px 10px 24px !important; color: #64748B !important;  
        text-decoration: none !important; font-size: 0.85rem !important; font-weight: 500 !important;
        border-radius: 6px !important; margin: 2px 6px !important;
    }
    .nav-item-sub-new:hover { color: #1E293B !important; background-color: #F1F5ED !important; }
    .nav-item-sub-new.active-sub { color: #6B8E55 !important; font-weight: 700 !important; background-color: #F1F5ED !important; }

    .caret-new { 
        font-size: 0.7rem !important; 
        color: #94A3B8 !important; 
        transition: transform 0.2s ease !important;
    }
    .caret-new.rotate { 
        transform: rotate(180deg) !important; 
    }
    
    .closebtn-new { 
        position: absolute !important; 
        top: 12px !important; 
        right: 15px !important; 
        font-size: 1.5rem !important; 
        color: #94A3B8 !important; 
        cursor: pointer !important; 
    }
    .closebtn-new:hover { color: #1E293B !important; }
    
    .logout-link-new { 
        margin-top: auto !important; padding: 20px 25px !important; color: #EF4444 !important; text-decoration: none !important; 
        font-weight: 700 !important; font-size: 0.9rem !important; border-top: 1px solid #F1F5F9 !important; display: block !important;
    }
    .logout-link-new:hover { background-color: #FEF2F2 !important; }
</style>

<script>
    function openNav() {
        document.getElementById("mySidenav").style.setProperty("width", "280px", "important");
        var mainEl = document.getElementById("main") || document.getElementById("main-wrapper") || document.querySelector(".main-content");
        if(mainEl) mainEl.style.setProperty("margin-left", "280px", "important");
        var ham = document.getElementById("hamBtn");
        if(ham) ham.style.visibility = "hidden";
    }

    function closeNav() {
        document.getElementById("mySidenav").style.setProperty("width", "0", "important");
        var mainEl = document.getElementById("main") || document.getElementById("main-wrapper") || document.querySelector(".main-content");
        if(mainEl) mainEl.style.setProperty("margin-left", "0", "important");
        var ham = document.getElementById("hamBtn");
        if(ham) ham.style.visibility = "visible";
    }

    function toggleDrop(id) {
        var dropdown = document.getElementById(id);
        var button = dropdown.previousElementSibling;
        var caret = button.querySelector('.caret-new');

        dropdown.classList.toggle('show');
        if (caret) {
            caret.classList.toggle('rotate');
        }
    }
</script>