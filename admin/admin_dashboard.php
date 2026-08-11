<?php
session_start();
include '../db_connect.php';

mysqli_report(MYSQLI_REPORT_OFF);

// 1. SECURITY
if (!isset($_SESSION['role']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// HELPER FUNCTION
if (!function_exists('check_table_exists')) {
    function check_table_exists($conn, $tableName) {
        $res = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
        return ($res && mysqli_num_rows($res) > 0);
    }
}

// EXACT REGISTRATION TABLES
$mat_reg_table = check_table_exists($conn, 'maternal_registrations') ? 'maternal_registrations' : 'maternal_registration';
$inf_reg_table = check_table_exists($conn, 'children') ? 'children' : 'infant_records';

// 2. HANDLE APPROVALS
if (isset($_GET['approve_infant'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_infant']);
    mysqli_query($conn, "UPDATE $inf_reg_table SET status='Approved' WHERE id='$id'");
    $message = "Infant approved successfully!";
}

if (isset($_GET['approve_maternal'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_maternal']);
    mysqli_query($conn, "UPDATE $mat_reg_table SET status='Approved' WHERE id='$id'");
    $message = "Maternal record approved successfully!";
}

// 3. HANDLE SCHEDULE ACTIONS (DONE / RESCHEDULE)
if (isset($_POST['action_type']) && isset($_POST['sched_id']) && isset($_POST['sched_table'])) {
    $s_id = mysqli_real_escape_string($conn, $_POST['sched_id']);

    if ($_POST['sched_table'] === 'Maternal') {
        if ($_POST['action_type'] === 'done') {
            mysqli_query($conn, "UPDATE maternal_schedules SET status='Completed' WHERE id='$s_id'");
            $message = "Maternal schedule marked as Completed!";
        } elseif ($_POST['action_type'] === 'resched' && !empty($_POST['new_date'])) {
            $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
            mysqli_query($conn, "UPDATE maternal_schedules SET appointment_date='$new_date', status='Rescheduled' WHERE id='$s_id'");
            $message = "Maternal schedule successfully rescheduled!";
        }
    } else {
        if ($_POST['action_type'] === 'done') {
            mysqli_query($conn, "UPDATE infant_schedule SET status='Completed' WHERE id='$s_id'");
            $message = "Child schedule marked as Completed!";
        } elseif ($_POST['action_type'] === 'resched' && !empty($_POST['new_date'])) {
            $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
            mysqli_query($conn, "UPDATE infant_schedule SET schedule_date='$new_date', status='Rescheduled' WHERE id='$s_id'");
            $message = "Child schedule successfully rescheduled!";
        }
    }
}

// 4. METRICS / COUNTS
$total_maternal_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM $mat_reg_table");
$total_maternal = $total_maternal_q ? mysqli_fetch_assoc($total_maternal_q)['count'] : 0;

$total_infant_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM $inf_reg_table");
$total_infant = $total_infant_q ? mysqli_fetch_assoc($total_infant_q)['count'] : 0;

$infant_pending = mysqli_query($conn, "SELECT * FROM $inf_reg_table WHERE status='Pending'");
$maternal_pending = mysqli_query($conn, "SELECT * FROM $mat_reg_table WHERE status='Pending'");

$pending_infant_count = $infant_pending ? mysqli_num_rows($infant_pending) : 0;
$pending_maternal_count = $maternal_pending ? mysqli_num_rows($maternal_pending) : 0;
$pending_total = ($pending_infant_count + $pending_maternal_count);

// 5. FETCH MATERNAL SCHEDULES (MATCHING admin_sched_maternal.php STRUCTURE)
$today_maternal_count = 0;
$upcoming_maternal = [];

if (check_table_exists($conn, 'maternal_schedules')) {
    // Today's Count (Case-insensitive status check)
    $today_mat_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM maternal_schedules WHERE LOWER(status) != 'completed' AND appointment_date = CURDATE()");
    if ($today_mat_q) { $today_maternal_count = mysqli_fetch_assoc($today_mat_q)['count']; }

    // Upcoming & Active List:
    // Gumamit ng LEFT JOIN at COALESCE para kahit may issue sa ID match, 
    // gagamitin pa rin ang 'patient_name' column o 'Maternal Patient' bilang fallback at HINDI mawawala sa listahan!
    $mat_upcoming_q = mysqli_query($conn, "
        SELECT s.id, s.appointment_date, s.appointment_time, s.appointment_type, s.status, 
               COALESCE(m.full_name, s.patient_name, 'Maternal Patient') AS full_name 
        FROM maternal_schedules s 
        LEFT JOIN $mat_reg_table m ON s.maternal_id = m.id 
        WHERE LOWER(s.status) != 'completed' AND s.appointment_date >= CURDATE() 
        ORDER BY s.appointment_date ASC, s.appointment_time ASC LIMIT 10
    ");

    if ($mat_upcoming_q) {
        while ($row = mysqli_fetch_assoc($mat_upcoming_q)) {
            $upcoming_maternal[] = $row;
        }
    }
}
// 6. FETCH INFANT SCHEDULES
$today_infant_count = 0;
$upcoming_infant = [];

if (check_table_exists($conn, 'infant_schedule')) {
    $today_inf_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM infant_schedule WHERE status != 'Completed' AND schedule_date = CURDATE()");
    if ($today_inf_q) { $today_infant_count = mysqli_fetch_assoc($today_inf_q)['count']; }

    $inf_upcoming_q = mysqli_query($conn, "SELECT id, child_name, schedule_date, schedule_time, vaccine_type FROM infant_schedule WHERE status != 'Completed' AND schedule_date >= CURDATE() ORDER BY schedule_date ASC, schedule_time ASC LIMIT 5");
    if ($inf_upcoming_q) {
        while ($row = mysqli_fetch_assoc($inf_upcoming_q)) {
            $upcoming_infant[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Alawihao Center</title>
    <style>
        :root { 
            --sage: #8DAE74; 
            --dark-sage: #6B8E55; 
            --light-bg: #F8FAFC; 
            --card-bg: #FFFFFF; 
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            background-color: var(--light-bg); 
            color: var(--text-main);
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
        }

        #main { 
            flex: 1; 
            margin-left: 0; 
            transition: margin-left .3s cubic-bezier(0.4, 0, 0.2, 1); 
            padding: 30px 40px; 
            overflow-y: auto; 
            box-sizing: border-box;
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 24px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: var(--card-bg);
            padding: 18px 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            border-top: 4px solid var(--sage);
        }

        .metric-title {
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .grid-container { 
            display: grid; 
            grid-template-columns: 2fr 1fr; 
            gap: 24px; 
        }

        .card { 
            background: var(--card-bg); 
            padding: 24px; 
            border-radius: 8px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05); 
            margin-bottom: 24px; 
            border: 1px solid var(--border-color);
        }

        .card-header-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--dark-sage);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }

        .card-header-title::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 14px;
            background-color: var(--dark-sage);
            margin-right: 10px;
            border-radius: 2px;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
        }

        th { 
            text-align: left; 
            padding: 10px 12px; 
            background-color: #F8FAFC;
            border-bottom: 1px solid var(--border-color); 
            color: var(--text-muted); 
            font-size: 0.7rem; 
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
        }

        td { 
            padding: 14px 12px; 
            border-bottom: 1px solid #F1F5F9; 
            font-size: 0.85rem; 
            color: var(--text-main);
        }

        .no-data {
            text-align: center;
            color: var(--text-muted);
            padding: 15px;
            font-size: 0.85rem;
        }

        .btn-approve { 
            background: #F1F5ED; 
            color: var(--dark-sage); 
            padding: 6px 14px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 700; 
            font-size: 0.75rem; 
            transition: all 0.2s;
            display: inline-block;
        }

        .btn-approve:hover {
            background: var(--dark-sage);
            color: white;
        }

        .sched-item { 
            padding: 12px; 
            border-left: 3px solid #3B82F6; 
            background: #FAFAF9; 
            margin-bottom: 12px; 
            border-radius: 0 8px 8px 0; 
        }

        .sched-item.child-sched {
            border-left-color: #EF4444;
        }

        .sched-actions {
            margin-top: 8px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-done {
            background: #DCFCE7;
            color: #16A34A;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-done:hover { background: #16A34A; color: white; }

        .resched-container {
            display: flex;
            gap: 5px;
            align-items: center;
            margin-top: 5px;
        }

        .resched-input {
            padding: 3px 6px;
            font-size: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .btn-resched {
            background: #FEF3C7;
            color: #D97706;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-resched:hover { background: #D97706; color: white; }

        .success-alert {
            background: #F0FDF4;
            color: #16A34A;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 1px solid #DCFCE7;
        }

        .status-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            background: #E2E8F0;
            color: #475569;
        }
    </style>
</head>
<body>

    <?php include('admin_sidebar.php'); ?>

    <div id="main">
        <div class="welcome-title">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></div>
        
        <?php if (!empty($message)): ?>
            <div class="success-alert"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- METRICS -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-title">TOTAL MATERNAL</div>
                <div class="metric-value"><?php echo $total_maternal; ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-title">REGISTERED INFANTS</div>
                <div class="metric-value"><?php echo $total_infant; ?></div>
            </div>
            <div class="metric-card" style="border-top-color: #D97706;">
                <div class="metric-title">PENDING APPROVALS</div>
                <div class="metric-value" style="color: #D97706;"><?php echo $pending_total; ?></div>
            </div>
            <div class="metric-card" style="border-top-color: #3B82F6;">
                <div class="metric-title">TODAY'S MATERNAL</div>
                <div class="metric-value" style="color: #3B82F6;"><?php echo $today_maternal_count; ?></div>
            </div>
            <div class="metric-card" style="border-top-color: #EF4444;">
                <div class="metric-title">TODAY'S CHILD VACCINE</div>
                <div class="metric-value" style="color: #EF4444;"><?php echo $today_infant_count; ?></div>
            </div>
        </div>

        <!-- MAIN CONTENT GRID -->
        <div class="grid-container">
            
            <div class="left-column">
                <div class="card">
                    <div class="card-header-title">Pending Newborn Enrollments</div>
                    <table>
                        <tr><th>Baby Name</th><th>Mother</th><th>Action</th></tr>
                        <?php if($infant_pending && mysqli_num_rows($infant_pending) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($infant_pending)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['baby_name'] ?? $row['child_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['mother_name'] ?? ''); ?></td>
                                <td><a href="?approve_infant=<?php echo $row['id']; ?>" class="btn-approve">Approve</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="no-data">No pending newborn enrollments.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <div class="card">
                    <div class="card-header-title">Pending Maternal Enrollments</div>
                    <table>
                        <tr><th>Mother's Name</th><th>LMP Date</th><th>Action</th></tr>
                        <?php if($maternal_pending && mysqli_num_rows($maternal_pending) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($maternal_pending)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['full_name'] ?? $row['mother_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['lmp_date'] ?? ''); ?></td>
                                <td><a href="?approve_maternal=<?php echo $row['id']; ?>" class="btn-approve">Approve</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="no-data">No pending maternal enrollments.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="right-column">
                
                <!-- UPCOMING MATERNAL CHECK-UPS -->
                <div class="card">
                    <div class="card-header-title">Upcoming Maternal Check-ups</div>
                    <?php if(!empty($upcoming_maternal)): ?>
                        <?php foreach($upcoming_maternal as $s): ?>
                        <div class="sched-item">
                            <strong style="color: #2563EB; font-size: 0.85rem;">
                                <?php echo htmlspecialchars($s['appointment_date']); ?> 
                                <?php if(!empty($s['appointment_time'])) echo ' (' . htmlspecialchars($s['appointment_time']) . ')'; ?>
                            </strong>
                            <span class="status-badge"><?php echo htmlspecialchars($s['status']); ?></span><br>
                            
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-main);">
                                <?php echo htmlspecialchars($s['full_name']); ?>
                            </span><br>
                            
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                Type: <?php echo htmlspecialchars($s['appointment_type']); ?>
                            </span>
                            
                            <div class="sched-actions">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="sched_id" value="<?php echo $s['id']; ?>">
                                    <input type="hidden" name="sched_table" value="Maternal">
                                    <input type="hidden" name="action_type" value="done">
                                    <button type="submit" class="btn-done" onclick="return confirm('Mark as completed?')">Mark Done</button>
                                </form>
                            </div>

                            <form method="POST" class="resched-container">
                                <input type="hidden" name="sched_id" value="<?php echo $s['id']; ?>">
                                <input type="hidden" name="sched_table" value="Maternal">
                                <input type="hidden" name="action_type" value="resched">
                                <input type="date" name="new_date" class="resched-input" required>
                                <button type="submit" class="btn-resched">Resched</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data" style="margin: 0;">No upcoming maternal check-ups.</p>
                    <?php endif; ?>
                </div>

                <!-- UPCOMING CHILD VACCINATIONS -->
                <div class="card">
                    <div class="card-header-title" style="color: #DC2626;">Upcoming Child Vaccinations</div>
                    <?php if(!empty($upcoming_infant)): ?>
                        <?php foreach($upcoming_infant as $s): ?>
                        <div class="sched-item child-sched">
                            <strong style="color: #DC2626; font-size: 0.85rem;">
                                <?php echo htmlspecialchars($s['schedule_date']); ?>
                                <?php if(!empty($s['schedule_time'])) echo ' (' . htmlspecialchars($s['schedule_time']) . ')'; ?>
                            </strong><br>

                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-main);">
                                <?php echo htmlspecialchars($s['child_name'] ?? 'Child'); ?>
                            </span><br>

                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                Vaccine: <?php echo htmlspecialchars($s['vaccine_type'] ?? 'General Vaccine'); ?>
                            </span>
                            
                            <div class="sched-actions">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="sched_id" value="<?php echo $s['id']; ?>">
                                    <input type="hidden" name="sched_table" value="Infant">
                                    <input type="hidden" name="action_type" value="done">
                                    <button type="submit" class="btn-done" onclick="return confirm('Mark as completed?')">Mark Done</button>
                                </form>
                            </div>

                            <form method="POST" class="resched-container">
                                <input type="hidden" name="sched_id" value="<?php echo $s['id']; ?>">
                                <input type="hidden" name="sched_table" value="Infant">
                                <input type="hidden" name="action_type" value="resched">
                                <input type="date" name="new_date" class="resched-input" required>
                                <button type="submit" class="btn-resched">Resched</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data" style="margin: 0;">No upcoming child vaccinations.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

</body>
</html>