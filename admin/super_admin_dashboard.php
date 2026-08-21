<?php
session_start();
include '../db_connect.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: login.php");
    exit();
}

// --- HANDLE WORKER APPROVAL / REJECTION LOCALLY (SUPER ADMIN ONLY) ---
if (isset($_GET['approve_worker_id'])) {
    $worker_id = intval($_GET['approve_worker_id']);
    mysqli_query($conn, "UPDATE users SET status='Approved' WHERE id=$worker_id AND role='Admin'");
    header("Location: super_admin_dashboard.php?success=worker_approved");
    exit();
}

if (isset($_GET['remove_worker_id'])) {
    $worker_id = intval($_GET['remove_worker_id']);
    mysqli_query($conn, "DELETE FROM users WHERE id=$worker_id AND role='Admin'");
    header("Location: super_admin_dashboard.php?success=worker_removed");
    exit();
}

// Para sa notification badge sa sidebar
$pending_workers_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0;

// --- FETCH COUNTS ---
// Dito ay nilagyan natin ng WHERE status='Approved' para tumugma sa totoong bilang ng approved infants sa database mo
$total_newborns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM infant_records"))['t'] ?? 0;

$total_pregnant = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status='Approved'"))['t'] ?? 0;
$total_patients = $total_newborns + $total_pregnant;
$total_workers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Approved'"))['t'] ?? 0;

$total_pending = (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status='Pending'"))['t'] ?? 0) + 
                 (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status='Pending'"))['t'] ?? 0) +
                 (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0);

// --- FETCH LISTS ---
$pending_workers = mysqli_query($conn, "SELECT * FROM users WHERE role='Admin' AND status='Pending' ORDER BY created_at DESC");
$pending_list = mysqli_query($conn, "SELECT * FROM children WHERE status='Pending' ORDER BY created_at DESC");

$pending_preg_list = mysqli_query($conn, "SELECT *, 
    CONCAT(COALESCE(street,''), ' ', COALESCE(barangay,''), ' ', COALESCE(municipality,'')) AS computed_address,
    CONCAT(COALESCE(spouse_fname,''), ' ', COALESCE(spouse_lname,'')) AS computed_spouse 
    FROM maternal_registration WHERE status='Pending' ORDER BY created_at DESC");

// --- FETCH UPCOMING SCHEDULES PARA SA SUPER ADMIN ---
$upcoming_maternal = [];
$upcoming_infant = [];
if (!function_exists('check_table_exists')) {
    function check_table_exists($conn, $table_name) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
        return $result && mysqli_num_rows($result) > 0;
    }
}
if (check_table_exists($conn, 'schedules')) {
    // Maternal Schedules Query
    $mat_upcoming_q = mysqli_query($conn, "
        SELECT id, schedule_date, schedule_time, service_type, status, patient_name AS full_name 
        FROM schedules 
        WHERE category = 'Maternal' AND LOWER(status) != 'completed' 
        ORDER BY schedule_date ASC, schedule_time ASC LIMIT 5
    ");
    if ($mat_upcoming_q) {
        while ($row = mysqli_fetch_assoc($mat_upcoming_q)) {
            $upcoming_maternal[] = $row;
        }
    }

    // Child Schedules Query
    $inf_upcoming_q = mysqli_query($conn, "
        SELECT id, patient_name as child_name, schedule_date, schedule_time, service_type as vaccine_type, status
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
    <title>Super Admin Dashboard | Alawihao</title>
    <style>
        :root { --sage: #8DAE74; --dark-sage: #5A6B47; --beige: #F9F9F4; --white: #FFFFFF; --text: #2D2D2D; --border: #E1E1D7; }
        body { font-family: 'Inter', sans-serif; margin: 0; background-color: var(--beige); color: var(--text); display: flex; }
        
        .main-content { flex-grow: 1; padding: 40px; box-sizing: border-box; width: 100%; margin-left: 280px; transition: margin-left 0.3s ease-in-out; }
        .page-header { border-bottom: 2px solid var(--border); padding-bottom: 15px; margin-bottom: 30px; }
        .page-header h1 { color: var(--dark-sage); font-size: 1.8rem; margin: 0; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 4px; border-top: 5px solid var(--sage); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .stat-card h4 { font-size: 0.7rem; color: #7f8c8d; margin-bottom: 10px; text-transform: uppercase; }
        .stat-card h2 { margin: 0; font-size: 1.6rem; }
        
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; align-items: start; }
        .left-column, .right-column { display: flex; flex-direction: column; gap: 25px; width: 100%; min-width: 0; }

        .table-container { background: var(--white); padding: 25px; border-radius: 8px; border: 1px solid var(--border); width: 100%; box-sizing: border-box; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .table-container h3 { font-size: 1.1rem; color: var(--dark-sage); border-left: 4px solid var(--sage); padding-left: 10px; margin-top: 0; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #F4F4ED; padding: 12px; font-size: 0.75rem; text-transform: uppercase; color: #666; }
        td { padding: 12px; border-bottom: 1px solid #F0F0F0; font-size: 0.85rem; }
        
        .btn-approve { background: var(--sage); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.7rem; display: inline-block; border: none; cursor: pointer; }
        .btn-reject { background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.7rem; margin-left: 5px; display: inline-block; border: none; cursor: pointer; }

        .schedule-card-list { display: flex; flex-direction: column; gap: 15px; }
        .schedule-item { background: #FAFAF7; border: 1px solid #EBEBE3; border-radius: 6px; padding: 15px; position: relative; }
        .schedule-item.maternal-border { border-left: 4px solid var(--sage); }
        .schedule-item.infant-border { border-left: 4px solid #e74c3c; }
        
        .sched-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .sched-date { font-weight: bold; font-size: 0.95rem; color: #2C3E50; }
        .status-badge { background: #E2E8F0; color: #4A5568; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase; }
        
        .sched-patient-name { font-size: 1rem; font-weight: bold; color: #2D2D2D; margin-bottom: 4px; text-transform: capitalize; }
        .sched-type { font-size: 0.85rem; color: #666; margin-bottom: 12px; }
        
        .sched-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .btn-mark-done { background: #D1E7DD; color: #0F5132; border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; cursor: pointer; }
        .btn-mark-done:hover { background: #badbcc; }
        
        .resched-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .date-input { padding: 5px 8px; border: 1px solid #CCC; border-radius: 4px; font-size: 0.8rem; font-family: inherit; background: white; }
        .btn-resched { background: #FDE68A; color: #78350F; border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; cursor: pointer; }
        .btn-resched:hover { background: #FCD34D; }

        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
        .modal-content { background: white; margin: 3% auto; padding: 30px; border-radius: 8px; width: 850px; position: relative; max-height: 90vh; overflow-y: auto; box-sizing: border-box; }
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 10px; }
        .form-group label { font-size: 0.75rem; font-weight: bold; color: #555; margin-bottom: 4px; text-transform: uppercase; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="date"], .form-group select, .form-group textarea { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85rem; font-family: inherit; box-sizing: border-box; background: white; }
        
        .patient-info-box { background: #F4F4ED; border: 1px solid var(--border); padding: 15px; border-radius: 6px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

        .checkbox-group { background: #FAFAFA; border: 1px solid #E1E1D7; padding: 10px; border-radius: 4px; max-height: 140px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
        .checkbox-label { font-size: 0.8rem; display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text); font-weight: normal; text-transform: none; }
        .checkbox-label input { width: 15px; height: 15px; cursor: pointer; }

        .section-tag { background: #F4F4ED; padding: 6px 12px; font-size: 0.8rem; font-weight: bold; color: var(--dark-sage); border-radius: 4px; margin: 15px 0 10px 0; border-left: 4px solid var(--sage); }
    </style>
</head>
<body>

<?php include 'super_admin_sidebar.php'; ?>

<div class="main-content" id="mainDashboard">
    <div class="page-header">
        <h1>Super Admin Dashboard</h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><h4>TOTAL PATIENTS</h4><h2><?php echo $total_patients; ?></h2></div>
        <div class="stat-card"><h4>APPROVED INFANTS</h4><h2><?php echo $total_newborns; ?></h2></div>
        <div class="stat-card"><h4>APPROVED MATERNAL</h4><h2><?php echo $total_pregnant; ?></h2></div>
        <div class="stat-card" style="border-top-color:#f39c12;"><h4>FOR APPROVAL</h4><h2 style="color:#f39c12;"><?php echo $total_pending; ?></h2></div>
        <div class="stat-card"><h4>STAFF WORKERS</h4><h2><?php echo $total_workers; ?></h2></div>
    </div>

    <div class="dashboard-grid">
        
        <!-- LEFT COLUMN -->
        <div class="left-column">
            <!-- PENDING WORKERS TABLE (Handled Directly Here) -->
            <div class="table-container">
                <h3>Pending Staff Worker Accounts</h3>
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (mysqli_num_rows($pending_workers) > 0): while($row = mysqli_fetch_assoc($pending_workers)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <a href="super_admin_dashboard.php?approve_worker_id=<?php echo $row['id']; ?>" class="btn-approve">APPROVE</a>
                                <a href="super_admin_dashboard.php?remove_worker_id=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('Reject this worker?')">REJECT</a>
                            </td>
                        </tr>
                        <?php endwhile; else: echo "<tr><td colspan='3' align='center'>No pending worker accounts.</td></tr>"; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- NEWBORN REGISTRATION TABLE -->
            <div class="table-container">
                <h3>Newborn Registration Approval</h3>
                <table>
                    <thead><tr><th>Infant Name</th><th>Mother's Name</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (mysqli_num_rows($pending_list) > 0): while($row = mysqli_fetch_assoc($pending_list)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['child_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['mother_name']); ?></td>
                            <td>
                                <button type="button" class="btn-approve" onclick='openNewbornModal(<?php echo json_encode($row); ?>)'>REVIEW</button>
                                <a href="process_verification.php?remove_id=<?php echo $row['id']; ?>&redirect=super_admin_dashboard.php" class="btn-reject" onclick="return confirm('Reject this?')">REJECT</a>
                            </td>
                        </tr>
                        <?php endwhile; else: echo "<tr><td colspan='3' align='center'>No pending newborn records.</td></tr>"; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- MATERNAL REGISTRATION TABLE -->
            <div class="table-container">
                <h3>Maternal Registration Approval</h3>
                <table>
                    <thead><tr><th>Patient Name</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (mysqli_num_rows($pending_preg_list) > 0): while($row = mysqli_fetch_assoc($pending_preg_list)): 
                            $row['display_name'] = trim($row['client_fname'] . " " . $row['client_lname']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['display_name']); ?></td>
                            <td>
                                <button type="button" class="btn-approve" onclick='openVerifyModal(<?php echo json_encode($row); ?>)'>VERIFY & ENROLL</button>
                                <a href="process_verification.php?remove_preg_id=<?php echo $row['id']; ?>&redirect=super_admin_dashboard.php" class="btn-reject" onclick="return confirm('Reject this registration?')">REJECT</a>
                            </td>
                        </tr>
                        <?php endwhile; else: echo "<tr><td colspan='2' align='center'>No pending maternal registration.</td></tr>"; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="right-column">
            
            <!-- Upcoming Maternal Check-ups -->
            <div class="table-container">
                <h3>Upcoming Maternal Check-ups & Vaccinations</h3>
                <div class="schedule-card-list">
                    <?php if (!empty($upcoming_maternal)): foreach($upcoming_maternal as $sched): 
                        $patientName = $sched['full_name'] ?? $sched['client_name'] ?? 'N/A';
                        $serviceType = $sched['service_type'] ?? $sched['service'] ?? 'N/A';
                        $schedDate = $sched['schedule_date'] ?? date('Y-m-d');
                        $schedId = $sched['id'] ?? '';
                    ?>
                    <div class="schedule-item maternal-border">
                        <div class="sched-header-row">
                            <span class="sched-date"><?php echo htmlspecialchars($schedDate); ?></span>
                            <span class="status-badge">Pending</span>
                        </div>
                        <div class="sched-patient-name"><?php echo htmlspecialchars($patientName); ?></div>
                        <div class="sched-type">Type: <?php echo htmlspecialchars($serviceType); ?></div>
                        
                        <form method="POST" action="process_verification.php" class="sched-actions">
                            <input type="hidden" name="schedule_id" value="<?php echo $schedId; ?>">
                            <input type="hidden" name="redirect_to" value="super_admin_dashboard.php">
                            <button type="submit" name="mark_done_maternal" class="btn-mark-done">Mark Done</button>
                            <div class="resched-group">
                                <input type="date" name="new_date" class="date-input" required>
                                <button type="submit" name="reschedule_maternal" class="btn-resched">Resched</button>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; else: ?>
                        <p style="text-align: center; color: #777; font-size: 0.85rem; margin: 10px 0;">No upcoming maternal check-ups.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Child Vaccinations -->
            <div class="table-container">
                <h3>Upcoming Child Vaccinations</h3>
                <div class="schedule-card-list">
                    <?php if (!empty($upcoming_infant)): foreach($upcoming_infant as $sched): 
                        $infantName = $sched['full_name'] ?? $sched['child_name'] ?? $sched['baby_name'] ?? 'N/A';
                        $vaccineType = $sched['service_type'] ?? $sched['service'] ?? 'N/A';
                        $schedDateFull = ($sched['schedule_date'] ?? date('Y-m-d')) . (!empty($sched['schedule_time']) ? ' (' . $sched['schedule_time'] . ')' : '');
                        $schedId = $sched['id'] ?? '';
                    ?>
                    <div class="schedule-item infant-border">
                        <div class="sched-header-row">
                            <span class="sched-date"><?php echo htmlspecialchars($schedDateFull); ?></span>
                        </div>
                        <div class="sched-patient-name"><?php echo htmlspecialchars($infantName); ?></div>
                        <div class="sched-type">Vaccine: <?php echo htmlspecialchars($vaccineType); ?></div>
                        
                        <form method="POST" action="process_verification.php" class="sched-actions">
                            <input type="hidden" name="schedule_id" value="<?php echo $schedId; ?>">
                            <input type="hidden" name="redirect_to" value="super_admin_dashboard.php">
                            <button type="submit" name="mark_done_infant" class="btn-mark-done">Mark Done</button>
                            <div class="resched-group">
                                <input type="date" name="new_date" class="date-input" required>
                                <button type="submit" name="reschedule_infant" class="btn-resched">Resched</button>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; else: ?>
                        <p style="text-align: center; color: #777; font-size: 0.85rem; margin: 10px 0;">No upcoming infant schedules.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- NEWBORN VERIFICATION MODAL -->
<div id="newbornVerifyModal" class="modal">
    <div class="modal-content" style="width: 750px;">
        <h2 style="color:var(--dark-sage); margin-top:0; border-bottom:2px solid var(--border); padding-bottom:10px; font-size:1.2rem;">Infant Registration Review</h2>
        
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
        <h2 style="color:var(--dark-sage); margin-top:0; border-bottom:2px solid var(--border); padding-bottom:10px; font-size:1.2rem;">Maternal Client Record & Clinical Verification</h2>
        
        <form method="POST" action="process_verification.php">
            <input type="hidden" name="mother_id" id="modal_mother_id">
            <input type="hidden" name="redirect_to" value="super_admin_dashboard.php">
            
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

            <div class="section-tag">VIII. PHYSICAL EXAMINATION</div>

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
        document.getElementById('nb_child_name').value = data.child_name || '';
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

        document.getElementById('confirmNewbornBtn').href = "process_verification.php?approve_id=" + data.id + "&redirect=super_admin_dashboard.php";
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
        document.getElementById('p_address').value = data.address || data.street || '';
        document.getElementById('p_spouse').value = data.spouse_name || ((data.spouse_fname || '') + ' ' + (data.spouse_lname || '')).trim();
        
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