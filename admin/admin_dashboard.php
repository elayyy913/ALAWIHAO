<?php
session_start();
include '../db_connect.php';

mysqli_report(MYSQLI_REPORT_OFF);

// Mag-update ng activity para sa Admin/Super Admin kapag nagbubukas ng dashboard
if (isset($_SESSION['user_id'])) {
    $current_id = $_SESSION['user_id'];
    mysqli_query($conn, "UPDATE users SET last_activity = NOW() WHERE id = '$current_id'");
}
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

// 2. HANDLE SCHEDULE ACTIONS (DONE / RESCHEDULE)
if (isset($_POST['action_type']) && isset($_POST['sched_id'])) {
    $s_id = mysqli_real_escape_string($conn, $_POST['sched_id']);

    if ($_POST['action_type'] === 'done') {
        mysqli_query($conn, "UPDATE schedules SET status='Completed' WHERE id='$s_id'");
        $message = "Schedule marked as Completed!";
    } elseif ($_POST['action_type'] === 'resched' && !empty($_POST['new_date'])) {
        $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
        mysqli_query($conn, "UPDATE schedules SET schedule_date='$new_date', status='Rescheduled' WHERE id='$s_id'");
        $message = "Schedule successfully rescheduled!";
    }
}

// 4. METRICS / COUNTS
$total_maternal_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM $mat_reg_table");
$total_maternal = $total_maternal_q ? mysqli_fetch_assoc($total_maternal_q)['count'] : 0;

$total_infant_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM $inf_reg_table");
$total_infant = $total_infant_q ? mysqli_fetch_assoc($total_infant_q)['count'] : 0;

$infant_pending = mysqli_query($conn, "SELECT * FROM $inf_reg_table WHERE status='Pending'");
$maternal_pending = mysqli_query($conn, "SELECT *, 
    CONCAT(COALESCE(street,''), ' ', COALESCE(barangay,''), ' ', COALESCE(municipality,'')) AS computed_address,
    CONCAT(COALESCE(spouse_fname,''), ' ', COALESCE(spouse_lname,'')) AS computed_spouse 
    FROM $mat_reg_table WHERE status='Pending' ORDER BY created_at DESC");

$pending_infant_count = $infant_pending ? mysqli_num_rows($infant_pending) : 0;
$pending_maternal_count = $maternal_pending ? mysqli_num_rows($maternal_pending) : 0;
$pending_total = ($pending_infant_count + $pending_maternal_count);

// 5. FETCH MATERNAL SCHEDULES
$today_maternal_count = 0;
$upcoming_maternal = [];

if (check_table_exists($conn, 'schedules')) {
    $today_mat_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM schedules WHERE category='Maternal' AND LOWER(status) != 'completed' AND schedule_date = CURDATE()");
    if ($today_mat_q) { $today_maternal_count = mysqli_fetch_assoc($today_mat_q)['count']; }

    $mat_upcoming_q = mysqli_query($conn, "
        SELECT id, schedule_date, schedule_time, service_type AS appointment_type, status, patient_name AS full_name 
        FROM schedules 
        WHERE category = 'Maternal' AND LOWER(status) != 'completed' 
        ORDER BY schedule_date ASC, schedule_time ASC LIMIT 10
    ");

    if ($mat_upcoming_q) {
        while ($row = mysqli_fetch_assoc($mat_upcoming_q)) {
            $upcoming_maternal[] = $row;
        }
    }
}

// 6. FETCH INFANT / CHILD SCHEDULES
$today_infant_count = 0;
$upcoming_infant = [];

if (check_table_exists($conn, 'schedules')) {
    $today_inf_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM schedules WHERE category='Child' AND LOWER(status) != 'completed'");
    if ($today_inf_q) { 
        $today_infant_count = mysqli_fetch_assoc($today_inf_q)['count']; 
    }

    $inf_upcoming_q = mysqli_query($conn, "
        SELECT id, 
               patient_name as child_name, 
               schedule_date, 
               schedule_time, 
               service_type as vaccine_type,
               status
        FROM schedules 
        WHERE category = 'Child' AND LOWER(status) != 'completed' 
        ORDER BY schedule_date ASC, schedule_time ASC LIMIT 5
    ");

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
            vertical-align: middle;
        }

        td { 
            padding: 14px 12px; 
            border-bottom: 1px solid #F1F5F9; 
            font-size: 0.85rem; 
            color: var(--text-main);
            vertical-align: middle;
        }

        .no-data {
            text-align: center;
            color: var(--text-muted);
            padding: 15px;
            font-size: 0.85rem;
        }

        .btn-approve { 
            background: var(--sage); 
            color: white; 
            padding: 6px 12px; 
            border-radius: 4px; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 0.7rem; 
            display: inline-block; 
            border: none; 
            cursor: pointer;
        }
        .btn-approve:hover { background: var(--dark-sage); }

        .btn-reject { 
            background: #e74c3c; 
            color: white; 
            padding: 6px 12px; 
            border-radius: 4px; 
            text-decoration: none; 
            font-size: 0.7rem; 
            margin-left: 5px; 
            display: inline-block; 
            border: none; 
            cursor: pointer; 
        }
        .btn-reject:hover { background: #c0392b; }

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

        /* MODAL STYLES GAYA NG SA SUPER ADMIN */
        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
        .modal-content { background: white; margin: 3% auto; padding: 30px; border-radius: 8px; width: 850px; position: relative; max-height: 90vh; overflow-y: auto; box-sizing: border-box; }
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 10px; }
        .form-group label { font-size: 0.75rem; font-weight: bold; color: #555; margin-bottom: 4px; text-transform: uppercase; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="date"], .form-group select, .form-group textarea { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85rem; font-family: inherit; box-sizing: border-box; background: white; }
        
        .patient-info-box { background: #F4F4ED; border: 1px solid var(--border-color); padding: 15px; border-radius: 6px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .checkbox-group { background: #FAFAFA; border: 1px solid #E1E1D7; padding: 10px; border-radius: 4px; max-height: 140px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
        .checkbox-label { font-size: 0.8rem; display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-main); font-weight: normal; text-transform: none; }
        .checkbox-label input { width: 15px; height: 15px; cursor: pointer; }
        .section-tag { background: #F4F4ED; padding: 6px 12px; font-size: 0.8rem; font-weight: bold; color: var(--dark-sage); border-radius: 4px; margin: 15px 0 10px 0; border-left: 4px solid var(--sage); }
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
                <div class="metric-title">APPROVED MATERNAL</div>
                <div class="metric-value"><?php echo $total_maternal; ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-title">APPROVED INFANTS</div>
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
                <!-- NEWBORN REGISTRATION TABLE -->
                <div class="card">
                    <div class="card-header-title">Pending Newborn Enrollments</div>
                    <table>
                        <tr><th>Baby Name</th><th>Mother</th><th>Action</th></tr>
                        <?php if($infant_pending && mysqli_num_rows($infant_pending) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($infant_pending)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['baby_name'] ?? $row['child_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['mother_name'] ?? ''); ?></td>
                                <td>
                                    <button type="button" class="btn-approve" onclick='openNewbornModal(<?php echo json_encode($row); ?>)'>REVIEW</button>
                                    <a href="process_verification.php?remove_id=<?php echo $row['id']; ?>&redirect=admin_dashboard.php" class="btn-reject" onclick="return confirm('Reject this?')">REJECT</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="no-data">No pending newborn enrollments.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- MATERNAL REGISTRATION TABLE -->
                <div class="card">
                    <div class="card-header-title">Pending Maternal Enrollments</div>
                    <table>
                        <tr><th>Patient Name</th><th>Action</th></tr>
                        <?php if($maternal_pending && mysqli_num_rows($maternal_pending) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($maternal_pending)): 
                                $row['display_name'] = trim($row['client_fname'] . " " . $row['client_lname']);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['display_name'] !== '' ? $row['display_name'] : ($row['full_name'] ?? $row['mother_name'] ?? '')); ?></td>
                                <td>
                                    <button type="button" class="btn-approve" onclick='openVerifyModal(<?php echo json_encode($row); ?>)'>VERIFY & ENROLL</button>
                                    <a href="process_verification.php?remove_preg_id=<?php echo $row['id']; ?>&redirect=admin_dashboard.php" class="btn-reject" onclick="return confirm('Reject this registration?')">REJECT</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="no-data">No pending maternal enrollments.</td></tr>
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
                                <?php echo htmlspecialchars($s['schedule_date'] )?> 
                                <?php if(!empty($s['appointment_time'])) echo ' (' . htmlspecialchars($s['appointment_time']) . ')'; ?>
                            </strong>
                            <span class="status-badge"><?php echo htmlspecialchars($s['status']); ?></span><br>
                            
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-main);">
                                <?php echo htmlspecialchars($s['full_name']); ?>
                            </span><br>
                            
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                Type: <?php echo htmlspecialchars($s['appointment_type']); ?>
                            </span>
                            
                            <form method="POST" action="process_verification.php" class="sched-actions">
                                <input type="hidden" name="schedule_id" value="<?php echo $s['id']; ?>">
                                <input type="hidden" name="redirect_to" value="admin_dashboard.php">
                                <button type="submit" name="mark_done_maternal" class="btn-done">Mark Done</button>
                                <div class="resched-container" style="margin-top:0;">
                                    <input type="date" name="new_date" class="resched-input" required>
                                    <button type="submit" name="reschedule_maternal" class="btn-resched">Resched</button>
                                </div>
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
                            
                            <form method="POST" action="process_verification.php" class="sched-actions">
                                <input type="hidden" name="schedule_id" value="<?php echo $s['id']; ?>">
                                <input type="hidden" name="redirect_to" value="admin_dashboard.php">
                                <button type="submit" name="mark_done_infant" class="btn-done">Mark Done</button>
                                <div class="resched-container" style="margin-top:0;">
                                    <input type="date" name="new_date" class="resched-input" required>
                                    <button type="submit" name="reschedule_infant" class="btn-resched">Resched</button>
                                </div>
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

    <!-- NEWBORN VERIFICATION MODAL -->
    <div id="newbornVerifyModal" class="modal">
        <div class="modal-content" style="width: 750px;">
            <h2 style="color:var(--dark-sage); margin-top:0; border-bottom:2px solid var(--border-color); padding-bottom:10px; font-size:1.2rem;">Infant Registration Review</h2>
            
            <div class="section-tag" style="margin-top:0;">ADMINISTRATIVE DETAILS</div>
            <div class="patient-info-box" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Health Center</label>
                    <input type="text" id="nb_health_center" readonly style="background: #e9e9e1;" value="Alawihao Health Center">
                </div>
                <div class="form-group">
                    <label>Family Serial / No.</label>
                    <input type="text" id="nb_family_serial" readonly style="background: #e9e9e1;" placeholder="N/A">
                </div>
            </div>

            <div class="section-tag">PATIENT PERSONAL INFORMATION</div>
            <div class="patient-info-box" style="grid-template-columns: repeat(3, 1fr);">
                <div class="form-group" style="grid-column: span 3;">
                    <label>Full Name of Baby</label>
                    <input type="text" id="nb_child_name" readonly style="background: #e9e9e1;">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <input type="text" id="nb_gender" readonly style="background: #e9e9e1;">
                </div>
                <div class="form-group">
                    <label>Blood Type</label>
                    <input type="text" id="nb_blood_type" readonly style="background: #e9e9e1;" value="Unknown / N/A">
                </div>
                <div class="form-group">
                    <label>Birth Date</label>
                    <input type="text" id="nb_birth_date" readonly style="background: #e9e9e1;">
                </div>
                <div class="form-group">
                    <label>Birth Weight (kg)</label>
                    <input type="text" id="nb_weight" readonly style="background: #e9e9e1;">
                </div>
                <div class="form-group">
                    <label>Birth Height (cm)</label>
                    <input type="text" id="nb_height" readonly style="background: #e9e9e1;" placeholder="N/A">
                </div>
                <div class="form-group">
                    <label>Place of Birth</label>
                    <input type="text" id="nb_place_of_birth" readonly style="background: #e9e9e1;">
                </div>
            </div>

            <div class="section-tag">ADDRESS INFORMATION</div>
            <div class="patient-info-box" style="grid-template-columns: 2fr 1fr;">
                <div class="form-group">
                    <label>Address (Number, Street, Purok)</label>
                    <input type="text" id="nb_address_details" readonly style="background: #e9e9e1;" placeholder="N/A">
                </div>
                <div class="form-group">
                    <label>Barangay</label>
                    <input type="text" id="nb_barangay" readonly style="background: #e9e9e1;" value="Alawihao">
                </div>
            </div>

            <div class="section-tag">PARENT / GUARDIAN INFORMATION</div>
            <div class="patient-info-box" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Mother's Full Name</label>
                    <input type="text" id="nb_mother_name" readonly style="background: #e9e9e1;">
                </div>
                <div class="form-group">
                    <label>Father's Full Name</label>
                    <input type="text" id="nb_father_name" readonly style="background: #e9e9e1;" placeholder="N/A">
                </div>
            </div>

            <div class="section-tag">IMMUNIZATION RECORDS</div>
            <div id="nb_vaccines_container" class="checkbox-group" style="background: #F4F4ED; pointer-events: none;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn-reject" style="background: #95a5a6; padding: 8px 15px; cursor:pointer;" onclick="closeNewbornModal()">Cancel</button>
                <a href="#" id="confirmNewbornBtn" class="btn-approve" style="padding: 8px 15px; text-align: center; text-decoration:none;">CONFIRM / ACCEPT</a>
            </div>
        </div>
    </div>

    <!-- MATERNAL CLINICAL VERIFICATION MODAL -->
    <div id="verifyModal" class="modal">
        <div class="modal-content">
            <h2 style="color:var(--dark-sage); margin-top:0; border-bottom:2px solid var(--border-color); padding-bottom:10px; font-size:1.2rem;">Maternal Client Record & Clinical Verification</h2>
            
            <form method="POST" action="process_verification.php">
                <input type="hidden" name="mother_id" id="modal_mother_id">
                <input type="hidden" name="redirect_to" value="admin_dashboard.php">
                
                <div class="section-tag" style="margin-top:0;">PATIENT PERSONAL INFORMATION (EDITABLE)</div>
                <div class="patient-info-box">
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="client_lname" id="p_lname" required>
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="client_fname" id="p_fname" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Initial / Name</label>
                        <input type="text" name="client_mname" id="p_mname">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="birthdate" id="p_birthdate">
                    </div>
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" name="age" id="p_age">
                    </div>
                    <div class="form-group">
                        <label>Blood Type</label>
                        <input type="text" name="blood_type" id="p_blood">
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_no" id="p_contact" placeholder="e.g. 09123456789">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Address</label>
                        <input type="text" name="address" id="p_address">
                    </div>
                    <div class="form-group" style="grid-column: span 3;">
                        <label>Spouse Name</label>
                        <input type="text" name="spouse_name" id="p_spouse">
                    </div>
                </div>

                <div class="section-tag">II. REVIEW OF SYSTEMS</div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>HEENT</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Epilepsy / Convulsions / Seizures"> Epilepsy / Convulsions / Seizures</label>
                            <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Severe headache / dizziness"> Severe headache / dizziness</label>
                            <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Visual disturbance Blurring of Vision"> Visual disturbance Blurring of Vision</label>
                            <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Yellowish Conjunctiva"> Yellowish Conjunctiva</label>
                            <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Enlarge Thyroid"> Enlarge Thyroid</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Chest / Heart</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="chest_heart[]" value="Severe chest pain"> Severe chest pain</label>
                            <label class="checkbox-label"><input type="checkbox" name="chest_heart[]" value="Shortness of breath and easy fatigability"> Shortness of breath and easy fatigability</label>
                            <label class="checkbox-label"><input type="checkbox" name="chest_heart[]" value="Breast and axillary masses"> Breast and axillary masses</label>
                            <label class="checkbox-label" style="flex-wrap: wrap;">
                                <input type="checkbox" name="chest_heart[]" value="Nipple discharges"> Nipple discharges (specify if blood or pus)
                            </label>
                            <input type="text" name="nipple_discharge_specify" placeholder="Specify: blood or pus" style="padding:6px; border:1px solid #ccc; border-radius:4px; font-size:0.8rem;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Abdomen</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="abdomen_med[]" value="Mass in the abdomen"> Mass in the abdomen</label>
                            <label class="checkbox-label"><input type="checkbox" name="abdomen_med[]" value="History of Gall Bladder disease"> History of Gall Bladder disease</label>
                            <label class="checkbox-label"><input type="checkbox" name="abdomen_med[]" value="History of Liver disease"> History of Liver disease</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Genital</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="genital_med[]" value="Vaginal discharge"> Vaginal discharge</label>
                            <label class="checkbox-label"><input type="checkbox" name="genital_med[]" value="Intermenstrual bleeding"> Intermenstrual bleeding</label>
                            <label class="checkbox-label"><input type="checkbox" name="genital_med[]" value="Postcoital bleeding"> Postcoital bleeding</label>
                            <label class="checkbox-label"><input type="checkbox" name="genital_med[]" value="Mass in the uterus"> Mass in the uterus</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Extremities</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="extremities_med[]" value="Severe varicosities"> Severe varicosities</label>
                            <label class="checkbox-label"><input type="checkbox" name="extremities_med[]" value="Swelling or severe pain in the legs not related to injuries"> Swelling or severe pain in the legs not related to injuries</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Skin</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="skin_med[]" value="Yellowish"> Yellowish</label>
                        </div>
                    </div>
                </div>

                <div class="section-tag">III. FAMILY HISTORY</div>
                <div class="checkbox-group" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:6px; max-height:none;">
                    <label class="checkbox-label"><input type="checkbox" name="family_history_details[]" value="CVA (stroke)"> CVA (stroke)</label>
                    <label class="checkbox-label"><input type="checkbox" name="family_history_details[]" value="Hypertension"> Hypertension</label>
                    <label class="checkbox-label"><input type="checkbox" name="family_history_details[]" value="Asthma"> Asthma</label>
                    <label class="checkbox-label"><input type="checkbox" name="family_history_details[]" value="Heart Disease"> Heart Disease</label>
                    <label class="checkbox-label"><input type="checkbox" name="family_history_details[]" value="Diabetes"> Diabetes</label>
                </div>

                <div class="section-tag">IV. PAST HEALTH HISTORY</div>
                <div class="checkbox-group" style="display:grid; grid-template-columns: repeat(2, 1fr); gap:6px; max-height:none;">
                    <label class="checkbox-label"><input type="checkbox" name="past_health_details[]" value="Allergies"> Allergies</label>
                    <label class="checkbox-label"><input type="checkbox" name="past_health_details[]" value="Drug intake (anti-TB, anti-diabetic, anti-convulsant)"> Drug intake (anti-TB, anti-diabetic, anti-convulsant)</label>
                    <label class="checkbox-label"><input type="checkbox" name="past_health_details[]" value="Bleeding tendencies (nose, gums, etc.)"> Bleeding tendencies (nose, gums, etc.)</label>
                    <label class="checkbox-label"><input type="checkbox" name="past_health_details[]" value="Anemia"> Anemia</label>
                    <label class="checkbox-label"><input type="checkbox" name="past_health_details[]" value="Diabetes"> Diabetes</label>
                    <label class="checkbox-label"><input type="checkbox" name="past_health_details[]" value="Itching or sores in or around the vagina"> Itching or sores in or around the vagina</label>
                    <label class="checkbox-label"><input type="checkbox" name="past_health_details[]" value="Pain or burning sensation on urination"> Pain or burning sensation on urination</label>
                </div>

                <div class="section-tag">V. SOCIAL HISTORY</div>
                <div class="checkbox-group" style="display:grid; grid-template-columns: repeat(2, 1fr); gap:8px; max-height:none;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="social_history_details[]" value="Smoking"> Smoking
                        <input type="text" name="smoking_sticks_per_day" placeholder="# of sticks/day" style="width:110px; margin-left:6px; padding:4px 6px; border:1px solid #ccc; border-radius:4px; font-size:0.75rem;">
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="social_history_details[]" value="Alcohol beverage"> Alcohol beverage
                        <input type="text" name="alcohol_amount_per_day" placeholder="amount/day" style="width:110px; margin-left:6px; padding:4px 6px; border:1px solid #ccc; border-radius:4px; font-size:0.75rem;">
                    </label>
                    <label class="checkbox-label"><input type="checkbox" name="social_history_details[]" value="Obesity"> Obesity</label>
                    <label class="checkbox-label"><input type="checkbox" name="social_history_details[]" value="History of domestic violence or VAW"> History of domestic violence or VAW</label>
                    <label class="checkbox-label"><input type="checkbox" name="social_history_details[]" value="Unpleasant relationship with partner"> Unpleasant relationship with partner</label>
                    <label class="checkbox-label"><input type="checkbox" name="social_history_details[]" value="Treated STIs in the past"> Treated STIs in the past</label>
                </div>

                <div class="section-tag">I. OBSTETRICAL & MENSTRUAL HISTORY</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Gravida (G)</label>
                        <input type="number" name="gravida" value="1" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Para (P)</label>
                        <input type="number" name="para" value="0" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Full-term</label>
                        <input type="number" name="full_term" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Premature</label>
                        <input type="number" name="premature" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Abortion</label>
                        <input type="number" name="abortion" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Living Children</label>
                        <input type="number" name="living_children" value="0" min="0">
                    </div>
                </div>

                <div class="checkbox-group" style="display:flex; flex-direction:row; gap:20px; margin-top:8px; max-height:none;">
                    <label class="checkbox-label"><input type="checkbox" name="obstetric_findings[]" value="History of Ectopic Pregnancy"> History of Ectopic Pregnancy</label>
                    <label class="checkbox-label"><input type="checkbox" name="obstetric_findings[]" value="Hydatidiform mole (within the last 12 months)"> Hydatidiform mole (within the last 12 months)</label>
                </div>

                <div class="form-grid-2" style="margin-top: 10px;">
                    <div class="form-group" style="grid-column: span 2;">
                        <label>History of Previous Delivery</label>
                    </div>
                    <div class="form-group">
                        <label>Date of Last Delivery</label>
                        <input type="date" name="date_last_delivery" id="p_date_last_delivery">
                    </div>
                    <div class="form-group">
                        <label>Type of Last Delivery</label>
                        <select name="type_last_delivery" id="p_type_last_delivery">
                            <option value="">Select Type</option>
                            <option value="Normal Spontaneous Delivery (NSD)">Normal Spontaneous Delivery (NSD)</option>
                            <option value="Cesarean Section (CS)">Cesarean Section (CS)</option>
                            <option value="Forceps / Vacuum Extraction">Forceps / Vacuum Extraction</option>
                            <option value="Breech Delivery">Breech Delivery</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Birth Attendant in Last Delivery</label>
                        <input type="text" name="birth_attendant" id="p_birth_attendant" placeholder="e.g. Midwife, Physician, etc.">
                    </div>
                </div>

                <div class="form-grid-2" style="margin-top: 10px;">
                    <div class="form-group">
                        <label>LMP (Last Menstrual Period)</label>
                        <input type="date" name="lmp" id="p_lmp">
                    </div>
                    <div class="form-group">
                        <label>EDC (Expected Date of Confinement)</label>
                        <input type="date" name="edc" id="p_edc">
                    </div>
                </div>

                <div class="form-grid" style="margin-top: 10px;">
                    <div class="form-group">
                        <label>Past Menstrual Period</label>
                        <input type="date" name="past_menstrual_period">
                    </div>
                    <div class="form-group">
                        <label>Duration of Menstrual Bleeding</label>
                        <input type="text" name="duration_menstrual_bleeding" placeholder="e.g. 3-4 days">
                    </div>
                    <div class="form-group">
                        <label>Character of Menstrual Bleeding (# of pads)</label>
                        <input type="text" name="character_menstrual_bleeding_pads" placeholder="e.g. 3 pads/day">
                    </div>
                </div>

                <div class="section-tag">VII. FAMILY PLANNING HISTORY</div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Previously Used Method</label>
                        <input type="text" name="fp_previous_method" placeholder="e.g. Pills, IUD, Condom, None">
                    </div>
                    <div class="form-group">
                        <label>Duration</label>
                        <input type="text" name="fp_duration" placeholder="e.g. 2 years">
                    </div>
                </div>
                <p style="font-size: 0.75rem; color: #777; font-style: italic; margin: 4px 0 5px;">Kindly refer to PHYSICIAN for any checked (&#10003;) findings for further evaluation.</p>

                <div class="section-tag">VIII. PHYSICAL EXAMINATION (CHECK-UP & UPDATES)</div>

                <div class="form-group"><label>Vital Signs</label></div>
                <div class="form-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="form-group">
                        <label>Blood Pressure (mm/Hg)</label>
                        <input type="text" name="vs_bp" placeholder="e.g. 110/70">
                    </div>
                    <div class="form-group">
                        <label>Weight (kgs)</label>
                        <input type="number" step="0.1" name="vs_weight">
                    </div>
                    <div class="form-group">
                        <label>Pulse (bpm)</label>
                        <input type="number" name="vs_pulse">
                    </div>
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input type="number" step="0.1" name="vs_height">
                    </div>
                    <div class="form-group">
                        <label>MUAC</label>
                        <input type="text" name="vs_muac">
                    </div>
                    <div class="form-group">
                        <label>BMI</label>
                        <input type="text" name="vs_bmi">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="vs_bmi_category" placeholder="e.g. Normal, Underweight">
                    </div>
                </div>

                <div class="form-grid-2" style="margin-top: 10px;">
                    <div class="form-group">
                        <label>Conjunctiva</label>
                        <div class="checkbox-group" style="display:flex; flex-direction:row; gap:15px; max-height:none;">
                            <label class="checkbox-label"><input type="checkbox" name="conjunctiva_exam[]" value="Pale"> Pale</label>
                            <label class="checkbox-label"><input type="checkbox" name="conjunctiva_exam[]" value="Yellowish"> Yellowish</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Neck</label>
                        <div class="checkbox-group" style="display:flex; flex-direction:row; gap:15px; max-height:none;">
                            <label class="checkbox-label"><input type="checkbox" name="neck_exam[]" value="Enlarged Thyroid"> Enlarged Thyroid</label>
                            <label class="checkbox-label"><input type="checkbox" name="neck_exam[]" value="Enlarged Lymph Node"> Enlarged Lymph Node</label>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 10px;">
                    <label>Breast</label>
                    <div class="checkbox-group" style="max-height:none;">
                        <label class="checkbox-label" style="flex-wrap: wrap; gap: 8px;">
                            <input type="checkbox" name="breast_exam[]" value="Mass"> Mass —
                            Left: <input type="text" name="breast_mass_left" placeholder="size/notes" style="width:110px; padding:4px 6px; border:1px solid #ccc; border-radius:4px; font-size:0.75rem;">
                            Right: <input type="text" name="breast_mass_right" placeholder="size/notes" style="width:110px; padding:4px 6px; border:1px solid #ccc; border-radius:4px; font-size:0.75rem;">
                        </label>
                        <label class="checkbox-label"><input type="checkbox" name="breast_exam[]" value="Nipple discharge"> Nipple discharge</label>
                        <label class="checkbox-label"><input type="checkbox" name="breast_exam[]" value="Skin - orange peel or dimpling"> Skin - orange peel or dimpling</label>
                        <label class="checkbox-label"><input type="checkbox" name="breast_exam[]" value="Enlarged axillary lymph nodes"> Enlarged axillary lymph nodes</label>
                    </div>
                </div>

                <div class="form-grid-2" style="margin-top: 10px;">
                    <div class="form-group">
                        <label>Thorax</label>
                        <div class="checkbox-group" style="max-height:none;">
                            <label class="checkbox-label"><input type="checkbox" name="thorax_exam[]" value="Abnormal heart sound / cardiac rate"> Abnormal heart sound / cardiac rate</label>
                            <label class="checkbox-label"><input type="checkbox" name="thorax_exam[]" value="Abnormal breath sound / respiratory rate"> Abnormal breath sound / respiratory rate</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Abdomen</label>
                        <div class="checkbox-group" style="max-height:none;">
                            <label class="checkbox-label"><input type="checkbox" name="abdomen_exam[]" value="Enlarge Liver"> Enlarge Liver</label>
                            <label class="checkbox-label"><input type="checkbox" name="abdomen_exam[]" value="Mass"> Mass</label>
                            <label class="checkbox-label"><input type="checkbox" name="abdomen_exam[]" value="Tenderness"> Tenderness</label>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 10px;">
                    <label>Vaginal Examination</label>
                    <div class="checkbox-group" style="display:grid; grid-template-columns: repeat(2, 1fr); gap:6px; max-height:none;">
                        <label class="checkbox-label"><input type="checkbox" name="vaginal_exam[]" value="Bleeding"> Bleeding</label>
                        <label class="checkbox-label"><input type="checkbox" name="vaginal_exam[]" value="Discharges"> Discharges</label>
                        <label class="checkbox-label"><input type="checkbox" name="vaginal_exam[]" value="Cysts / mass"> Cysts / mass</label>
                        <label class="checkbox-label"><input type="checkbox" name="vaginal_exam[]" value="Scars"> Scars</label>
                        <label class="checkbox-label"><input type="checkbox" name="vaginal_exam[]" value="Warts"> Warts</label>
                        <label class="checkbox-label"><input type="checkbox" name="vaginal_exam[]" value="Lacerations"> Lacerations</label>
                    </div>
                    <input type="text" name="vaginal_others_specify" placeholder="Others (specify)" style="margin-top:6px; padding:6px; border:1px solid #ccc; border-radius:4px; font-size:0.8rem;">
                </div>

                <div class="form-grid-2" style="margin-top: 10px;">
                    <div class="form-group">
                        <label>Extremities</label>
                        <div class="checkbox-group" style="max-height:none;">
                            <label class="checkbox-label"><input type="checkbox" name="extremities_exam[]" value="Edema"> Edema</label>
                            <label class="checkbox-label"><input type="checkbox" name="extremities_exam[]" value="Varicosities"> Varicosities</label>
                            <label class="checkbox-label"><input type="checkbox" name="extremities_exam[]" value="Pain on forced dorsiflexion"> Pain on forced dorsiflexion</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>TT Status</label>
                        <input type="text" name="tt_status" placeholder="e.g. TT2, Complete">
                    </div>
                </div>

                <div style="margin-top: 25px; text-align: right;">
                    <button type="button" class="btn-reject" style="background: #95a5a6; padding: 10px 20px;" onclick="closeVerifyModal()">Cancel</button>
                    <button type="submit" name="submit_maternal_approval" class="btn-approve" style="padding: 10px 20px; font-size: 0.85rem;">APPROVE & ENROLL</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openNewbornModal(data) {
            document.getElementById('nb_child_name').value = data.baby_name || data.child_name || '';
            document.getElementById('nb_birth_date').value = data.birth_date || '';
            document.getElementById('nb_gender').value = data.gender || '';
            document.getElementById('nb_weight').value = data.weight_kg || data.weight || data.baby_weight || '';
            document.getElementById('nb_mother_name').value = data.mother_name || '';
            document.getElementById('nb_place_of_birth').value = data.place_of_birth || '';
            document.getElementById('nb_family_serial').value = data.family_no || data.family_serial || data.serial_no || '';
            document.getElementById('nb_blood_type').value = data.blood_type || 'Unknown / N/A';
            document.getElementById('nb_height').value = data.height_cm || data.height || data.birth_height || '';
            document.getElementById('nb_address_details').value = data.address || data.street || '';
            document.getElementById('nb_barangay').value = data.barangay || 'Alawihao';
            document.getElementById('nb_father_name').value = data.father_name || '';

            let vaccinesStr = data.vaccine_taken || data.immunization_records || data.vaccines || '';
            let vaccinesArray = vaccinesStr ? vaccinesStr.split(',').map(v => v.trim()) : [];
            let container = document.getElementById('nb_vaccines_container');
            container.innerHTML = '';

            if (vaccinesArray.length > 0 && vaccinesArray[0] !== '') {
                vaccinesArray.forEach(vac => {
                    let label = document.createElement('label');
                    label.className = 'checkbox-label';
                    label.innerHTML = `<input type="checkbox" checked disabled> ${vac}`;
                    container.appendChild(label);
                });
            } else {
                container.innerHTML = '<span style="font-size: 0.80rem; color: #777; font-style: italic;">No immunization records checked/provided.</span>';
            }

            document.getElementById('confirmNewbornBtn').href = "process_verification.php?approve_id=" + data.id + "&redirect=admin_dashboard.php";
            document.getElementById('newbornVerifyModal').style.display = 'block';
        }

        function closeNewbornModal() {
            document.getElementById('newbornVerifyModal').style.display = 'none';
        }

        function openVerifyModal(data) {
            document.getElementById('modal_mother_id').value = data.id || '';
            document.getElementById('p_lname').value = data.client_lname || '';
            document.getElementById('p_fname').value = data.client_fname || '';
            document.getElementById('p_mname').value = data.client_mname || data.client_mi || '';
            document.getElementById('p_birthdate').value = data.birthdate || data.dob || '';
            document.getElementById('p_age').value = data.age || '';
            document.getElementById('p_blood').value = data.blood_type || '';
            document.getElementById('p_contact').value = data.contact_no || data.contact || '';
            document.getElementById('p_address').value = data.address || data.computed_address || data.street || '';
            document.getElementById('p_spouse').value = data.spouse_name || data.computed_spouse || ((data.spouse_fname || '') + ' ' + (data.spouse_lname || '')).trim();
            
            if(document.getElementById('p_date_last_delivery')) document.getElementById('p_date_last_delivery').value = data.date_last_delivery || '';
            if(document.getElementById('p_type_last_delivery')) document.getElementById('p_type_last_delivery').value = data.type_last_delivery || '';
            if(document.getElementById('p_birth_attendant')) document.getElementById('p_birth_attendant').value = data.birth_attendant || '';
            if(document.getElementById('p_lmp')) document.getElementById('p_lmp').value = data.lmp || '';
            if(document.getElementById('p_edc')) document.getElementById('p_edc').value = data.edc || '';

            document.getElementById('verifyModal').style.display = 'block';
        }

        function closeVerifyModal() {
            document.getElementById('verifyModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var nbModal = document.getElementById('newbornVerifyModal');
            var matModal = document.getElementById('verifyModal');
            if (event.target == nbModal) nbModal.style.display = "none";
            if (event.target == matModal) matModal.style.display = "none";
        }
    </script>
</body>
</html>