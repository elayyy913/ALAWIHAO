<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
$is_home = ($current_page == 'main_user.php');
// Kung home, naka-hide (translated), kung hindi, nakalabas.
$sidebar_style = $is_home ? "transform: translateX(-100%);" : "transform: translateX(0%);";
?>

<div class="hamburger-trigger" onclick="toggleNav()">☰</div>

<div class="sidebar" id="mySidebar" style="<?php echo $sidebar_style; ?>">
    <div class="sidebar-header">
        <div class="brand">
            ALAWIHAO <span class="highlight">CENTER</span>
        </div>
        <div class="sub-brand">PATIENT ACCESS PORTAL</div>
        <span class="close-icon" onclick="closeNav()">☰</span>
    </div>
    
    <div class="menu-items">
        <div class="menu-label">(OVERVIEW)</div>
        <a href="main_user.php" class="nav-link-main">Home</a>
        <a href="user_profile.php" class="nav-link-main">Profile</a>

        <div class="menu-label">(SERVICES)</div>
        <div class="menu-group">
            <button class="collapsible">Registration <span class="arrow">▼</span></button>
            <div class="content-collapse">
                <a href="user_reg_pregnancy.php" class="nav-link-sub">Maternal Form</a>
                <a href="user_reg_newborn.php" class="nav-link-sub">Child Form</a>
            </div>
        </div>

        <div class="menu-group">
            <button class="collapsible">My Records <span class="arrow">▼</span></button>
            <div class="content-collapse">
                <a href="user_maternal_records.php" class="nav-link-sub">Maternal Health</a>
                <a href="user_child_records.php" class="nav-link-sub">Child Health</a>
            </div>
        </div>

        <div class="menu-label">(OTHERS)</div>
        <a href="user_schedule.php" class="nav-link-main">Schedule</a>
        <a href="user_history.php" class="nav-link-main">History</a>
    </div>

    <a href="logout.php" class="logout-link">Log out</a>
</div>

<style>
    /* Floating Hamburger Button */
    .hamburger-trigger {
        position: fixed;
        top: 20px;
        left: 20px;
        font-size: 24px;
        background-color: #95AF7E; /* Sage Green */
        color: white;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        z-index: 1500; /* Mas mababa sa sidebar pero mataas sa content */
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: 0.3s ease;
    }

    .hamburger-trigger:hover {
        background-color: #718355;
        transform: scale(1.05);
    }

    .sidebar {
        width: 280px;
        background-color: #FFFFFF;
        height: 100vh;
        color: #2D3748; 
        position: fixed;
        top: 0;
        left: 0;
        transition: 0.5s;
        z-index: 2000;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        overflow-x: hidden;
        box-shadow: 4px 0 15px rgba(0,0,0,0.03);
    }

    .sidebar-header {
        padding: 35px 25px;
        border-bottom: 1px solid #F1F5F9;
        background-color: #FFFFFF;
    }

    .brand {
        font-size: 1.4rem;
        font-weight: 800;
        color: #2D3748;
        letter-spacing: -0.5px;
    }

    .brand .highlight {
        color: #95AF7E;
    }

    .sub-brand {
        font-size: 0.7rem;
        color: #A0AEC0;
        letter-spacing: 2px;
        font-weight: 700;
        margin-top: 5px;
    }

    .close-icon {
        position: absolute;
        right: 20px;
        top: 25px;
        cursor: pointer;
        font-size: 1.5rem;
        color: #95AF7E; /* Kulay sage na rin para maganda tingnan */
    }

    .menu-label {
        font-size: 0.7rem;
        font-weight: 800;
        color: #CBD5E0;
        padding: 25px 25px 10px 30px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    .nav-link-main, .collapsible {
        width: calc(100% - 30px);
        margin: 4px 15px;
        padding: 14px 18px;
        background: none;
        border: none;
        color: #4A5568;
        text-align: left;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        border-radius: 12px;
        transition: 0.3s;
    }

    .nav-link-main:hover, .collapsible:hover, .collapsible.active {
        background-color: #95AF7E;
        color: #FFFFFF;
    }

    .content-collapse {
        display: none;
        background-color: #F8FAF5; 
        margin: 0 15px 10px 15px;
        border-radius: 12px;
    }

    .nav-link-sub {
        display: block;
        padding: 12px 25px;
        color: #718355;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .nav-link-sub:hover {
        background-color: #E9F0E1;
        border-radius: 10px;
    }

    .logout-link {
        margin-top: auto;
        padding: 30px 25px;
        color: #E53E3E;
        text-decoration: none;
        font-weight: 700;
        border-top: 1px solid #F1F5F9;
    }

    .logout-link:hover {
        background-color: #FFF5F5;
    }
</style>

<script>
    function toggleNav() {
        const sidebar = document.getElementById("mySidebar");
        if (sidebar.style.transform === "translateX(0%)") {
            closeNav();
        } else {
            openNav();
        }
    }

    function openNav() {
        document.getElementById("mySidebar").style.transform = "translateX(0%)";
        const main = document.getElementById("main");
        if(main) main.style.marginLeft = "280px";
    }

    function closeNav() {
        document.getElementById("mySidebar").style.transform = "translateX(-100%)";
        const main = document.getElementById("main");
        if(main) main.style.marginLeft = "0";
    }

    document.querySelectorAll('.collapsible').forEach(button => {
        button.addEventListener('click', function() {
            this.classList.toggle('active');
            const content = this.nextElementSibling;
            const arrow = this.querySelector('.arrow');
            
            if (content.style.display === "block") {
                content.style.display = "none";
                arrow.innerHTML = "▼";
            } else {
                content.style.display = "block";
                arrow.innerHTML = "▲";
            }
        });
    });
</script>