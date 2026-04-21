<?php
$current_page = basename($_SERVER['PHP_SELF']);

// LOGIC: Kung Dashboard, '0' ang width. Kung hindi, '280px' para laging bukas.
$sidebar_width = ($current_page == 'admin_dashboard.php') ? '0' : '280px';
$main_margin = ($current_page == 'admin_dashboard.php') ? '0' : '280px';
$ham_display = ($current_page == 'admin_dashboard.php') ? 'block' : 'none';
?>

<div id="mySidenav" class="side-nav" style="width: <?php echo $sidebar_width; ?>;">
    <?php if($current_page == 'admin_dashboard.php'): ?>
        <span class="closebtn" onclick="closeNav()">&times;</span>
    <?php endif; ?>
    
    <div class="sidebar-header">
        <div class="brand">ALAWIHAO <span class="highlight">CENTER</span></div>
        <div class="sub-brand">ADMINISTRATIVE CONTROL</div>
    </div>
    
    <div class="menu-items">
        <div class="menu-label">(OVERVIEW)</div>
        <a href="admin_dashboard.php" class="nav-item">Home</a>
        <a href="admin_profile.php" class="nav-item">Profile</a>

        <div class="menu-label">(MANAGEMENT)</div>
        <button class="dropdown-btn" onclick="toggleDrop('dropReg')">Register <span class="caret">▼</span></button>
        <div id="dropReg" class="dropdown-container">
            <a href="admin_maternal_reg.php" class="nav-item-sub">Maternal Registration</a>
            <a href="admin_child_reg.php" class="nav-item-sub">Child Registration</a>
        </div>

        <button class="dropdown-btn" onclick="toggleDrop('dropRec')">Records <span class="caret">▼</span></button>
        <div id="dropRec" class="dropdown-container">
            <a href="admin_maternal_rec.php" class="nav-item-sub">Maternal Records</a>
            <a href="admin_child_rec.php" class="nav-item-sub">Child Records</a>
        </div>

        <button class="dropdown-btn" onclick="toggleDrop('dropSched')">Schedule <span class="caret">▼</span></button>
        <div id="dropSched" class="dropdown-container">
            <a href="admin_sched_maternal.php" class="nav-item-sub">Maternal Schedule</a>
            <a href="admin_sched_child.php" class="nav-item-sub">Child Schedule</a>
        </div>

        <div class="menu-label">(OTHERS)</div>
        <a href="admin_history.php" class="nav-item">History</a>
    </div>

    <a href="logout.php" class="logout-link">Log out</a>
</div>

<button class="open-btn" id="hamBtn" onclick="openNav()" style="display: <?php echo $ham_display; ?>;">&#9776;</button>

<style>
    .side-nav {
        height: 100%; position: fixed; z-index: 3000;
        top: 0; left: 0; background-color: #FFFFFF;
        overflow-x: hidden; transition: 0.5s; 
        box-shadow: 2px 0 15px rgba(0,0,0,0.05);
        display: flex; flex-direction: column;
    }

    .open-btn {
        font-size: 18px; cursor: pointer; background-color: #95AF7E; 
        color: white; padding: 8px 12px; border: none; border-radius: 8px;
        position: fixed; top: 25px; left: 25px; z-index: 2000;
        transition: 0.3s; box-shadow: 0 4px 10px rgba(149, 175, 126, 0.2);
    }

    #main { 
        margin-left: <?php echo $main_margin; ?>; 
        transition: margin-left .5s; 
        padding: 40px;
    }

    /* Rest of your styling (brand, nav-item, etc.) */
    .sidebar-header { padding: 35px 25px; border-bottom: 1px solid #F1F5F9; }
    .brand { font-size: 1.3rem; font-weight: 800; color: #2D3748; }
    .highlight { color: #95AF7E; }
    .sub-brand { font-size: 0.7rem; color: #A0AEC0; font-weight: 700; margin-top: 5px; }
    .menu-label { font-size: 0.7rem; font-weight: 800; color: #CBD5E0; padding: 20px 25px 5px 25px; text-transform: uppercase; }
    .nav-item, .dropdown-btn { padding: 14px 25px; text-decoration: none; font-size: 16px; color: #4A5568; display: block; transition: 0.3s; width: calc(100% - 30px); margin: 4px 15px; text-align: left; background: none; border: none; cursor: pointer; border-radius: 10px; font-weight: 600; }
    .nav-item:hover, .dropdown-btn:hover { background-color: #95AF7E; color: #FFFFFF; }
    .dropdown-container { display: none; background-color: #F8FAF5; margin: 0 15px 10px 15px; border-radius: 10px; }
    .nav-item-sub { display: block; padding: 12px 25px; color: #718355; text-decoration: none; font-size: 0.9rem; font-weight: 600; }
    .closebtn { position: absolute; top: 15px; right: 20px; font-size: 25px; color: #CBD5E0; cursor: pointer; }
    .logout-link { margin-top: auto; padding: 25px; color: #E53E3E; text-decoration: none; font-weight: 700; border-top: 1px solid #F1F5F9; }
</style>

<script>
    function openNav() {
        document.getElementById("mySidenav").style.width = "280px";
        document.getElementById("main").style.marginLeft = "280px";
        var ham = document.getElementById("hamBtn");
        if(ham) ham.style.visibility = "hidden";
    }

    function closeNav() {
        document.getElementById("mySidenav").style.width = "0";
        document.getElementById("main").style.marginLeft = "0";
        var ham = document.getElementById("hamBtn");
        if(ham) ham.style.visibility = "visible";
    }

    function toggleDrop(id) {
        var dropdown = document.getElementById(id);
        dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
    }
</script>
