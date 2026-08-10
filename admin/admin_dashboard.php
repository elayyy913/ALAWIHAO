<?php
session_start();
include '../db_connect.php';

// 1. SECURITY
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$message = "";

// 2. HANDLE APPROVALS
if (isset($_GET['approve_infant'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_infant']);
    mysqli_query($conn, "UPDATE children SET status='Approved' WHERE id='$id'");
    $message = "Infant approved successfully!";
}

if (isset($_GET['approve_maternal'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_maternal']);
    mysqli_query($conn, "UPDATE maternal_registrations SET status='Approved' WHERE id='$id'");
    $message = "Maternal record approved successfully!";
}

// 3. HANDLE SCHEDULE ACTIONS (DONE / RESCHEDULE)
if (isset($_POST['action_type']) && isset($_POST['sched_id']) && isset($_POST['sched_table'])) {
    $s_id = mysqli_real_escape_string($conn, $_POST['sched_id']);
    $s_table = ($_POST['sched_table'] === 'Maternal') ? 'maternal_schedules' : 'infant_schedule';

    if ($_POST['action_type'] === 'done') {
        mysqli_query($conn, "UPDATE $s_table SET status='Completed' WHERE id='$s_id'");
        $message = "Schedule marked as Done!";
    } elseif ($_POST['action_type'] === 'resched' && !empty($_POST['new_date'])) {
        $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
        
        // Dynamic column detection para sa reschedule
        if ($_POST['sched_table'] === 'Maternal') {
            $col_check = mysqli_query($conn, "SHOW COLUMNS FROM maternal_schedules LIKE 'appointment_date'");
            $date_col = (mysqli_num_rows($col_check) > 0) ? 'appointment_date' : 'schedule_date';
        } else {
            $col_check = mysqli_query($conn, "SHOW COLUMNS FROM infant_schedule LIKE 'appointment_date'");
            if (mysqli_num_rows($col_check) > 0) {
                $date_col = 'appointment_date';
            } else {
                $col_check2 = mysqli_query($conn, "SHOW COLUMNS FROM infant_schedule LIKE 'next_appointment'");
                $date_col = (mysqli_num_rows($col_check2) > 0) ? 'next_appointment' : 'schedule_date';
            }
        }

        mysqli_query($conn, "UPDATE $s_table SET $date_col='$new_date' WHERE id='$s_id'");
        $message = "Schedule successfully rescheduled!";
    }
}

// 4. FETCH METRICS / COUNTS
$total_maternal_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM maternal_registration");
$total_maternal = $total_maternal_q ? mysqli_fetch_assoc($total_maternal_q)['count'] : 0;

$total_infant_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM children");
$total_infant = $total_infant_q ? mysqli_fetch_assoc($total_infant_q)['count'] : 0;

$infant_pending = mysqli_query($conn, "SELECT * FROM children WHERE status='Pending'");
$maternal_pending = mysqli_query($conn, "SELECT * FROM maternal_registration WHERE status='Pending'");

$pending_infant_count = $infant_pending ? mysqli_num_rows($infant_pending) : 0;
$pending_maternal_count = $maternal_pending ? mysqli_num_rows($maternal_pending) : 0;
$pending_total = ($pending_infant_count + $pending_maternal_count);

// 5. FETCH SCHEDULES USING DYNAMIC COLUMN DETECTION
$today_sched_count = 0;
$upcoming_schedules = [];

// Detection para sa maternal_schedules
$mat_date_col = 'schedule_date';
$mat_col_check = mysqli_query($conn, "SHOW COLUMNS FROM maternal_schedules LIKE 'appointment_date'");
if ($mat_col_check && mysqli_num_rows($mat_col_check) > 0) {
    $mat_date_col = 'appointment_date';
}

$mat_detail_col = 'purpose';
$mat_det_check = mysqli_query($conn, "SHOW COLUMNS FROM maternal_schedules LIKE 'appointment_type'");
if ($mat_det_check && mysqli_num_rows($mat_det_check) > 0) {
    $mat_detail_col = 'appointment_type';
}

// Detection para sa infant_schedule
$inf_date_col = 'schedule_date';
$inf_col_check1 = mysqli_query($conn, "SHOW COLUMNS FROM infant_schedule LIKE 'appointment_date'");
$inf_col_check2 = mysqli_query($conn, "SHOW COLUMNS FROM infant_schedule LIKE 'next_appointment'");
$inf_col_check3 = mysqli_query($conn, "SHOW COLUMNS FROM infant_schedule LIKE 'vaccination_date'");

if ($inf_col_check1 && mysqli_num_rows($inf_col_check1) > 0) {
    $inf_date_col = 'appointment_date';
} elseif ($inf_col_check2 && mysqli_num_rows($inf_col_check2) > 0) {
    $inf_date_col = 'next_appointment';
} elseif ($inf_col_check3 && mysqli_num_rows($inf_col_check3) > 0) {
    $inf_date_col = 'vaccination_date';
}

$inf_detail_col = 'vaccine_name';
$inf_det_check = mysqli_query($conn, "SHOW COLUMNS FROM infant_schedule LIKE 'vaccine_type'");
if ($inf_det_check && mysqli_num_rows($inf_det_check) > 0) {
    $inf_detail_col = 'vaccine_type';
}

// Dynamic Union Query
$union_query = "
    SELECT id, 
           $mat_date_col as sched_date, 
           $mat_detail_col as details, 
           'Maternal' as type 
    FROM maternal_schedules 
    WHERE status = 'Pending'
    UNION ALL
    SELECT id, 
           $inf_date_col as sched_date, 
           $inf_detail_col as details, 
           'Infant' as type 
    FROM infant_schedule 
    WHERE status = 'Pending'
";

// Bilang ng iskedyul ngayong araw
$today_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM ($union_query) as combined WHERE sched_date = CURDATE()");
if ($today_q) {
    $today_sched_count = mysqli_fetch_assoc($today_q)['count'];
}

// Mga susunod na iskedyul (Upcoming)
$upcoming_q = mysqli_query($conn, "SELECT * FROM ($union_query) as combined WHERE sched_date >= CURDATE() ORDER BY sched_date ASC LIMIT 5");
if ($upcoming_q) {
    while ($row = mysqli_fetch_assoc($upcoming_q)) {
        $upcoming_schedules[] = $row;
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

        /* Metric Cards Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: var(--card-bg);
            padding: 20px 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            border-top: 4px solid var(--sage);
        }

        .metric-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Main Grid Layout */
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
            padding: 20px;
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
            border-left: 3px solid var(--sage); 
            background: #FAFAF9; 
            margin-bottom: 12px; 
            border-radius: 0 8px 8px 0; 
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
    </style>
</head>
<body>

    <!-- Minimalist Sidebar Integration -->
    <?php include('admin_sidebar.php'); ?>

    <div id="main">
        <div class="welcome-title">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></div>
        
        <?php if (!empty($message)): ?>
            <div class="success-alert"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Top Overview Metric Cards -->
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
                <div class="metric-title">TODAY'S SCHEDULE</div>
                <div class="metric-value" style="color: #3B82F6; font-size: 1.5rem;"><?php echo $today_sched_count; ?> Patient<?php echo ($today_sched_count > 1) ? 's' : ''; ?></div>
            </div>
        </div>

        <div class="grid-container">
            <!-- Left Column: Pending Approvals -->
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
                                <td><?php echo htmlspecialchars($row['mother_name'] ?? $row['patient_name'] ?? ''); ?></td>
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

            <!-- Right Column: Upcoming Schedule with Done & Resched Actions -->
            <div class="right-column">
                <div class="card">
                    <div class="card-header-title">Upcoming Schedule</div>
                    <?php if(!empty($upcoming_schedules)): ?>
                        <?php foreach($upcoming_schedules as $s): ?>
                        <div class="sched-item">
                            <strong style="color: var(--dark-sage); font-size: 0.85rem;"><?php echo htmlspecialchars($s['sched_date']); ?> (<?php echo htmlspecialchars($s['type']); ?>)</strong><br>
                            <span style="font-size: 0.85rem; font-weight: 500; color: var(--text-main);"><?php echo htmlspecialchars($s['details']); ?></span>
                            
                            <!-- Action Forms for Done / Resched -->
                            <div class="sched-actions">
                                <!-- Done Form -->
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="sched_id" value="<?php echo $s['id']; ?>">
                                    <input type="hidden" name="sched_table" value="<?php echo $s['type']; ?>">
                                    <input type="hidden" name="action_type" value="done">
                                    <button type="submit" class="btn-done" onclick="return confirm('Mark this schedule as completed?')">Mark Done</button>
                                </form>
                            </div>

                            <!-- Reschedule Form -->
                            <form method="POST" class="resched-container">
                                <input type="hidden" name="sched_id" value="<?php echo $s['id']; ?>">
                                <input type="hidden" name="sched_table" value="<?php echo $s['type']; ?>">
                                <input type="hidden" name="action_type" value="resched">
                                <input type="date" name="new_date" class="resched-input" required>
                                <button type="submit" class="btn-resched">Resched</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data" style="margin: 0;">No upcoming appointments.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>