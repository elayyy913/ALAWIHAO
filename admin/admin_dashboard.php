<?php
session_start();
include 'db_connect.php';

// 1. SECURITY
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$worker_id = $_SESSION['user_id'];
$message = "";

// 2. HANDLE APPROVALS
if (isset($_GET['approve_infant'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_infant']);
    mysqli_query($conn, "UPDATE infant_registrations SET status='Approved' WHERE id='$id'");
    $message = "Infant approved!";
}

if (isset($_GET['approve_maternal'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_maternal']);
    mysqli_query($conn, "UPDATE maternal_registrations SET status='Approved' WHERE id='$id'");
    $message = "Maternal record approved!";
}

// 3. FETCH DATA (Safely)
$infant_pending = mysqli_query($conn, "SELECT * FROM infant_registrations WHERE status='Pending'");
$maternal_pending = mysqli_query($conn, "SELECT * FROM maternal_registrations WHERE status='Pending'");

// 4. FETCH UPCOMING SCHEDULE (Checks if column exists first to prevent crash)
$upcoming_schedules = false;
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM vaccination_records LIKE 'next_appointment'");
if(mysqli_num_rows($check_col) > 0) {
    $upcoming_schedules = mysqli_query($conn, "SELECT * FROM vaccination_records WHERE next_appointment >= CURDATE() ORDER BY next_appointment ASC LIMIT 5");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | Health Worker</title>
    <style>
        :root { --dark-sage: #718355; --sage: #A3C981; --beige: #F5F5DC; --white: #FFFFFF; }
        body { font-family: 'Times New Roman', serif; margin: 0; background-color: var(--beige); display: flex; height: 100vh; overflow: hidden; }

        /* Sidebar - Matching your screenshot */
        .sidebar { width: 280px; background-color: var(--dark-sage); color: white; display: flex; flex-direction: column; }
        .sidebar-header { padding: 30px 20px; text-align: center; font-weight: bold; font-size: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu-item { border-bottom: 1px solid rgba(255,255,255,0.05); }
        .collapsible { width: 100%; padding: 20px 25px; background: none; border: none; color: white; text-align: left; font-size: 1.1rem; cursor: pointer; display: flex; justify-content: space-between; font-family: serif; }
        .content-collapse { display: none; background-color: rgba(0,0,0,0.15); }
        .nav-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 12px 45px; display: block; }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .logout { margin-top: auto; padding: 25px; color: #ff9999; text-decoration: none; font-weight: bold; }

        /* Content Area */
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .card { background: var(--white); padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .grid-container { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid var(--sage); color: var(--dark-sage); font-size: 0.85rem; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        
        .btn-approve { background: var(--sage); color: #333; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.8rem; }
        .sched-item { padding: 10px; border-left: 4px solid var(--dark-sage); background: #f9f9f9; margin-bottom: 10px; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">ADMIN PANEL</div>
        <div class="menu-item">
            <button class="collapsible">Register ▼</button>
            <div class="content-collapse">
                <a href="maternal_reg.php" class="nav-link">Pregnancy</a>
                <a href="infant_reg.php" class="nav-link">New Baby</a>
            </div>
        </div>
        <div class="menu-item">
            <button class="collapsible">Records ▼</button>
            <div class="content-collapse"><a href="#" class="nav-link">New Record</a></div>
        </div>
        <div class="menu-item">
            <button class="collapsible">Schedule ▼</button>
            <div class="content-collapse"><a href="#" class="nav-link">Set Schedule</a></div>
        </div>
        <div class="menu-item">
            <button class="collapsible">History ▼</button>
            <div class="content-collapse"><a href="#" class="nav-link">View History</a></div>
        </div>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="main-content">
        <h1 style="color: var(--dark-sage);">Welcome, <?php echo $_SESSION['name']; ?></h1>
        
        <div class="grid-container">
            <div class="left-column">
                <div class="card">
                    <h3>Pending Newborn Enrollments</h3>
                    <table>
                        <tr><th>Baby Name</th><th>Mother</th><th>Action</th></tr>
                        <?php while($row = mysqli_fetch_assoc($infant_pending)): ?>
                        <tr>
                            <td><?php echo $row['baby_name']; ?></td>
                            <td><?php echo $row['mother_name']; ?></td>
                            <td><a href="?approve_infant=<?php echo $row['id']; ?>" class="btn-approve">Approve</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>

                <div class="card">
                    <h3>Pending Maternal Enrollments</h3>
                    <table>
                        <tr><th>Mother's Name</th><th>LMP Date</th><th>Action</th></tr>
                        <?php while($row = mysqli_fetch_assoc($maternal_pending)): ?>
                        <tr>
                            <td><?php echo $row['mother_name']; ?></td>
                            <td><?php echo $row['lmp_date']; ?></td>
                            <td><a href="?approve_maternal=<?php echo $row['id']; ?>" class="btn-approve">Approve</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </div>

            <div class="right-column">
                <div class="card">
                    <h3>Upcoming Schedule</h3>
                    <?php if($upcoming_schedules && mysqli_num_rows($upcoming_schedules) > 0): ?>
                        <?php while($s = mysqli_fetch_assoc($upcoming_schedules)): ?>
                        <div class="sched-item">
                            <strong style="color:var(--dark-sage);"><?php echo $s['next_appointment']; ?></strong><br>
                            <small><?php echo $s['baby_name']; ?></small>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color:#999;">No upcoming appointments.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        var coll = document.getElementsByClassName("collapsible");
        for (var i = 0; i < coll.length; i++) {
            coll[i].addEventListener("click", function() {
                var content = this.nextElementSibling;
                content.style.display = (content.style.display === "block") ? "none" : "block";
            });
        }
    </script>
</body>
</html>