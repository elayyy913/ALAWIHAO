<div class="sidebar" style="width: 280px; background-color: #718355; color: white; height: 100vh; display: flex; flex-direction: column;">
    <div style="padding: 30px 20px; text-align: center; font-weight: bold; font-size: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
        ADMIN PANEL
    </div>
    
    <div class="menu-item">
        <button class="collapsible" onclick="toggleMenu('reg')">Register ▼</button>
        <div id="reg" class="content-collapse" style="display: none; background: rgba(0,0,0,0.1);">
            <a href="maternal_reg.php" class="nav-link">Pregnancy</a>
            <a href="infant_reg.php" class="nav-link">New Baby</a>
        </div>
    </div>

    <div class="menu-item">
        <button class="collapsible" onclick="toggleMenu('rec')">Records ▼</button>
        <div id="rec" class="content-collapse" style="display: none; background: rgba(0,0,0,0.1);">
            <a href="add_vaccination.php" class="nav-link">New Record</a>
        </div>
    </div>

    <div class="menu-item">
        <button class="collapsible" onclick="toggleMenu('sch')">Schedule ▼</button>
        <div id="sch" class="content-collapse" style="display: none; background: rgba(0,0,0,0.1);">
            <a href="set_schedule.php" class="nav-link">Set Schedule</a>
        </div>
    </div>

    <a href="logout.php" style="margin-top: auto; padding: 25px; color: #ff9999; text-decoration: none; font-weight: bold;">Logout</a>
</div>

<style>
    .collapsible { width: 100%; padding: 20px; background: none; border: none; color: white; text-align: left; cursor: pointer; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .nav-link { color: white; text-decoration: none; padding: 12px 40px; display: block; font-size: 0.95rem; }
    .nav-link:hover { background: rgba(255,255,255,0.1); }
</style>

<script>
function toggleMenu(id) {
    var x = document.getElementById(id);
    x.style.display = (x.style.display === "none") ? "block" : "none";
}
</script>