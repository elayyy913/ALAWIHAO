<?php
session_start();
include '../db_connect.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: login.php");
    exit();
}

$dashboardMessages = [
    'RescheduleSuccess' => 'Schedule rescheduled successfully. Notifications were sent to the user.',
    'MarkDoneSuccess' => 'Appointment marked as completed. Notifications were sent to the user.',
    'WorkerApprovedAndRecorded' => 'Worker account approved successfully.',
    'ApprovedAndRecorded' => 'Child registration approved successfully.',
    'MaternalApproved' => 'Maternal registration approved successfully.',
    'ChildApprovalError' => 'The child measurements could not be saved. Check the values and try again.',
    'Rejected' => 'The registration was rejected and kept for reporting.',
    'Removed' => 'The worker account was rejected and removed.'
];
$dashboardNotice = $dashboardMessages[$_GET['msg'] ?? ''] ?? null;
$dashboardNoticeIsError = ($_GET['msg'] ?? '') === 'ChildApprovalError';

function notify_schedule_user($conn, $patient_name, $schedule_id, $title, $message, $type) {
    $user_id = 0;
    $stmt = $conn->prepare("SELECT user_id FROM maternal_registration WHERE LOWER(TRIM(CONCAT(client_fname, ' ', client_lname))) = LOWER(TRIM(?)) LIMIT 1");
    $stmt->bind_param("s", $patient_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_id = (int) $row['user_id'];
    }
    $stmt->close();

    if (!$user_id) {
        $stmt = $conn->prepare("SELECT user_id FROM children WHERE LOWER(TRIM(child_name)) = LOWER(TRIM(?)) LIMIT 1");
        $stmt->bind_param("s", $patient_name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_id = (int) $row['user_id'];
        }
        $stmt->close();
    }

    if (!$user_id) return;

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, target_role, schedule_id, title, message, type, created_at) VALUES (?, 'User', ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisss", $user_id, $schedule_id, $title, $message, $type);
    $stmt->execute();
    $stmt->close();
}

// ── Handle reschedule directly in this file ──────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['reschedule_maternal']) || isset($_POST['reschedule_infant']))) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    
    // Get schedule details
    $sched_query = mysqli_query($conn, "SELECT * FROM schedules WHERE id = '$schedule_id'");
    $sched = mysqli_fetch_assoc($sched_query);
    
    if ($sched) {
        // Update schedule
        if (mysqli_query($conn, "UPDATE schedules SET schedule_date = '$new_date', status = 'Approved' WHERE id = '$schedule_id'")
            && mysqli_affected_rows($conn) > 0) {
            logAdminAction($conn, "Rescheduled schedule ID {$schedule_id} for {$sched['patient_name']} to {$new_date}.");
        }
        
        // Create notification for user
        $date_fmt = date('F j, Y', strtotime($new_date));
        $time_fmt = date('g:i A', strtotime($sched['schedule_time']));
        
        $title = "Schedule Updated";
        $message = isset($_POST['reschedule_maternal']) 
            ? "Your schedule for {$sched['service_type']} has been rescheduled to {$date_fmt} at {$time_fmt}."
            : "Your child's vaccination schedule for {$sched['service_type']} has been rescheduled to {$date_fmt} at {$time_fmt}.";

        notify_schedule_user($conn, $sched['patient_name'], $schedule_id, $title, $message, 'updated_schedule');
        
        // Create admin notification
        $admin_message = isset($_POST['reschedule_maternal']) 
            ? "Maternal schedule for {$sched['patient_name']} ({$sched['service_type']}) was rescheduled to {$date_fmt}."
            : "Child vaccination schedule for {$sched['patient_name']} ({$sched['service_type']}) was rescheduled to {$date_fmt}.";
            
        mysqli_query($conn, "INSERT INTO notifications (user_id, target_role, schedule_id, title, message, type, created_at) 
                           VALUES (0, 'Super Admin', '$schedule_id', 'Schedule Rescheduled', '$admin_message', 'updated_schedule', NOW())");
    }
    
    // Redirect back with success message
    header("Location: super_admin_dashboard.php?msg=RescheduleSuccess");
    exit();
}

// ── Handle mark done directly in this file ──────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['mark_done_maternal']) || isset($_POST['mark_done_infant']))) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    
    // Get schedule details for notification
    $sched_query = mysqli_query($conn, "SELECT * FROM schedules WHERE id = '$schedule_id'");
    $sched = mysqli_fetch_assoc($sched_query);
    
    // Update schedule status to completed
    if (mysqli_query($conn, "UPDATE schedules SET status = 'Completed' WHERE id = '$schedule_id'")
        && mysqli_affected_rows($conn) > 0) {
        $patient_label = $sched['patient_name'] ?? 'unknown patient';
        logAdminAction($conn, "Marked schedule ID {$schedule_id} for {$patient_label} as completed.");
    }
    
    if ($sched) {
        // Create notification for user
        $date_fmt = date('F j, Y', strtotime($sched['schedule_date']));
        $time_fmt = date('g:i A', strtotime($sched['schedule_time']));
        
        $title = "Appointment Completed";
        $message = isset($_POST['mark_done_maternal'])
            ? "Your {$sched['service_type']} appointment on {$date_fmt} at {$time_fmt} has been completed."
            : "Your child's {$sched['service_type']} appointment on {$date_fmt} at {$time_fmt} has been completed.";
            
        notify_schedule_user($conn, $sched['patient_name'], $schedule_id, $title, $message, 'completed_schedule');
        
        // Create admin notification
        $admin_message = isset($_POST['mark_done_maternal'])
            ? "Maternal appointment completed for {$sched['patient_name']} ({$sched['service_type']})."
            : "Child appointment completed for {$sched['patient_name']} ({$sched['service_type']}).";
            
        mysqli_query($conn, "INSERT INTO notifications (user_id, target_role, schedule_id, title, message, type, created_at) 
                           VALUES (0, 'Super Admin', '$schedule_id', 'Appointment Completed', '$admin_message', 'completed_schedule', NOW())");
    }
    
    // Redirect back with success message
    header("Location: super_admin_dashboard.php?msg=MarkDoneSuccess");
    exit();
}

// --- WORKER APPROVAL / REJECTION ---
// Inalis na ang lokal na handler dito. Ang approve_worker_id / remove_worker_id
// ay dapat na pumunta sa process_verification.php (mas kumpleto ang logic doon:
// hinahawakan din nito ang health_workers table, hindi lang ang users table).
// Sa verification pad(s), gawin ang links/buttons na:
//   process_verification.php?approve_worker_id=...&redirect=super_admin_dashboard.php
//   process_verification.php?remove_worker_id=...&redirect=super_admin_dashboard.php

// Para sa notification badge sa sidebar
$pending_workers_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0;

// --- FETCH COUNTS ---
$total_newborns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status='Approved'"))['t'] ?? 0;
$total_pregnant = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status='Approved'"))['t'] ?? 0;
$total_patients = $total_newborns + $total_pregnant;
$total_workers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Approved'"))['t'] ?? 0;

$total_pending = (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status='Pending'"))['t'] ?? 0) + 
                 (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status='Pending'"))['t'] ?? 0) +
                 (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0);

// --- REGISTRATION STATUS DISTRIBUTION ---
$mat_status = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'approved' THEN 1 ELSE 0 END) AS approved,
    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'rejected' THEN 1 ELSE 0 END) AS rejected,
    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) NOT IN ('approved', 'rejected') THEN 1 ELSE 0 END) AS pending
    FROM maternal_registration")) ?: ['total' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0];
$mat_total_reg = (int) $mat_status['total'];
$mat_approved = (int) $mat_status['approved'];
$mat_pending = (int) $mat_status['pending'];
$mat_rejected = (int) $mat_status['rejected'];
$mat_success_pct = $mat_total_reg > 0 ? round(($mat_approved / $mat_total_reg) * 100) : 0;
$mat_pending_pct = $mat_total_reg > 0 ? round(($mat_pending / $mat_total_reg) * 100) : 0;
$mat_rejected_pct = $mat_total_reg > 0 ? round(($mat_rejected / $mat_total_reg) * 100) : 0;

$child_status = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'approved' THEN 1 ELSE 0 END) AS approved,
    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'rejected' THEN 1 ELSE 0 END) AS rejected,
    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) NOT IN ('approved', 'rejected') THEN 1 ELSE 0 END) AS pending
    FROM children")) ?: ['total' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0];
$child_total_reg = (int) $child_status['total'];
$child_approved = (int) $child_status['approved'];
$child_pending = (int) $child_status['pending'];
$child_rejected = (int) $child_status['rejected'];
$child_success_pct = $child_total_reg > 0 ? round(($child_approved / $child_total_reg) * 100) : 0;
$child_pending_pct = $child_total_reg > 0 ? round(($child_pending / $child_total_reg) * 100) : 0;
$child_rejected_pct = $child_total_reg > 0 ? round(($child_rejected / $child_total_reg) * 100) : 0;
// ---------------------------------------------------------------------

// --- COMPUTATION PARA SA SCHEDULE SUCCESS RATES (NEW ADDITION) ---
$sched_mat_total = 0; $sched_mat_completed = 0; $sched_mat_not_success = 0;
$sched_mat_success_deg = 0; $sched_mat_success_pct = 0; $sched_mat_unsuccess_pct = 0;

$sched_child_total = 0; $sched_child_completed = 0; $sched_child_not_success = 0;
$sched_child_success_deg = 0; $sched_child_success_pct = 0; $sched_child_unsuccess_pct = 0;

if (!function_exists('check_table_exists')) {
    function check_table_exists($conn, $table_name) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
        return $result && mysqli_num_rows($result) > 0;
    }
}

if (check_table_exists($conn, 'schedules')) {
    // Maternal Schedules Success Metrics
    $sched_mat_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM schedules WHERE category='Maternal'"))['t'] ?? 0;
    $sched_mat_completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM schedules WHERE category='Maternal' AND LOWER(status)='completed'"))['t'] ?? 0;
    $sched_mat_not_success = $sched_mat_total - $sched_mat_completed;
    $sched_mat_success_deg = ($sched_mat_total > 0) ? (($sched_mat_completed / $sched_mat_total) * 360) : 0;
    $sched_mat_success_pct = ($sched_mat_total > 0) ? round(($sched_mat_completed / $sched_mat_total) * 100) : 0;
    $sched_mat_unsuccess_pct = ($sched_mat_total > 0) ? round(($sched_mat_not_success / $sched_mat_total) * 100) : 0;

    // Child Schedules / Immunization Success Metrics
    $sched_child_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM schedules WHERE category='Child'"))['t'] ?? 0;
    $sched_child_completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM schedules WHERE category='Child' AND LOWER(status)='completed'"))['t'] ?? 0;
    $sched_child_not_success = $sched_child_total - $sched_child_completed;
    $sched_child_success_deg = ($sched_child_total > 0) ? (($sched_child_completed / $sched_child_total) * 360) : 0;
    $sched_child_success_pct = ($sched_child_total > 0) ? round(($sched_child_completed / $sched_child_total) * 100) : 0;
    $sched_child_unsuccess_pct = ($sched_child_total > 0) ? round(($sched_child_not_success / $sched_child_total) * 100) : 0;
}
// ---------------------------------------------------------------------

// --- FETCH LISTS ---
$pending_workers = mysqli_query($conn, "SELECT u.*, COALESCE(u.created_at, hw.created_at) AS registration_date
    FROM users u
    LEFT JOIN health_workers hw ON hw.email = u.email
    WHERE u.role='Admin' AND u.status='Pending'
    ORDER BY registration_date DESC");
$pending_list = mysqli_query($conn, "SELECT * FROM children WHERE status='Pending' ORDER BY created_at DESC");

$pending_preg_list = mysqli_query($conn, "SELECT *, 
    CONCAT(COALESCE(street,''), ' ', COALESCE(barangay,''), ' ', COALESCE(municipality,'')) AS computed_address,
    CONCAT(COALESCE(spouse_fname,''), ' ', COALESCE(spouse_lname,'')) AS computed_spouse 
    FROM maternal_registration WHERE status='Pending' ORDER BY created_at DESC");

// --- FETCH UPCOMING SCHEDULES PARA SA SUPER ADMIN ---
$upcoming_maternal = [];
$upcoming_infant = [];

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
    <script src="../theme.js?v=<?= time() ?>"></script>
    <link rel="stylesheet" href="../theme.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $notif_path='../'; include '../notif_assets.php'; ?>
    <style>
        :root { --sage: #8DAE74; --dark-sage: #5A6B47; --beige: #F9F9F4; --white: #FFFFFF; --text: #2D2D2D; --border: #E1E1D7; --status-approved: #5f9b63; --status-approved-text: #27633a; --status-approved-soft: #dcfce7; --status-pending: #e2a12d; --status-pending-text: #92400e; --status-pending-soft: #fef3c7; --status-rejected: #d65a52; --status-rejected-text: #a52a26; --status-rejected-soft: #fee2e2; }
        body { font-family: 'Inter', sans-serif; margin: 0; background-color: var(--beige); color: var(--text); display: flex; }
        
        .main-content { flex: 1 1 0; min-width: 0; padding: 38px clamp(20px, 3vw, 40px); box-sizing: border-box; width: auto; margin-left: 280px; transition: margin-left 0.3s ease-in-out; }
        .page-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; border-bottom: 2px solid var(--border); padding-bottom: 18px; margin: 0 0 24px; }
        .page-heading-copy { min-width: 0; }
        .page-header h1 { color: var(--dark-sage); font-size: clamp(1.55rem, 2.4vw, 2rem); margin: 0; }
        .page-header p { margin: 7px 0 0; color: #687365; font-size: .92rem; }
        .page-header-tools { display: flex; align-items: center; gap: 14px; flex: 0 0 auto; }
        .dashboard-date { color: #687365; font-size: .82rem; white-space: nowrap; }
        .dashboard-sync { display: inline-flex; align-items: center; gap: 6px; color: #687365; font-size: .72rem; white-space: nowrap; }
        .dashboard-sync i { color: var(--dark-sage); }
        .quick-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin: 0 0 24px; }
        .quick-actions-label { color: #687365; font-size: .8rem; font-weight: 700; margin-right: 2px; }
        .quick-action { display: inline-flex; align-items: center; gap: 8px; min-height: 38px; padding: 8px 13px; border: 1px solid #dce5d6; border-radius: 8px; background: #fff; color: #465736; font-size: .82rem; font-weight: 650; text-decoration: none; transition: background .2s ease, border-color .2s ease, transform .2s ease; }
        .quick-action:hover { background: #f0f5ec; border-color: var(--sage); transform: translateY(-1px); }
        .quick-action-primary { background: #5f7f49; border-color: #5f7f49; color: #fff; }
        .quick-action-primary:hover { background: #4f6d3c; color: #fff; border-color: #4f6d3c; }
        .quick-action-dropdown { position: relative; }
        .quick-action-dropdown > summary { list-style: none; cursor: pointer; }
        .quick-action-dropdown > summary::-webkit-details-marker { display: none; }
        .quick-action-dropdown > summary::marker { content: ""; }
        .quick-action-chevron { margin-left: 2px; font-size: .68rem; transition: transform .2s ease; }
        .quick-action-dropdown[open] .quick-action-chevron { transform: rotate(180deg); }
        .quick-action-menu { position: absolute; top: calc(100% + 7px); left: 0; z-index: 20; display: grid; min-width: 190px; padding: 6px; border: 1px solid #dce5d6; border-radius: 9px; background: #fff; box-shadow: 0 8px 20px rgba(34, 48, 28, .14); }
        .quick-action-menu a { display: flex; align-items: center; gap: 9px; padding: 10px; border-radius: 6px; color: #465736; font-size: .8rem; font-weight: 600; text-decoration: none; white-space: nowrap; }
        .quick-action-menu a:hover, .quick-action-menu a:focus-visible { background: #f0f5ec; outline: none; }
        .quick-action-menu a i { width: 16px; color: #5f7f49; text-align: center; }
        
.stats-grid { 
        display: grid; 
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .stat-card { 
        background: var(--white); 
        padding: 19px;
        border-radius: 12px;
        border: 1px solid var(--border);
        border-top: 4px solid var(--sage); 
        box-shadow: 0 3px 12px rgba(34, 48, 28, .045);
        transition: all 0.3s ease-in-out;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #FFFFFF 0%, #F7FAF4 100%);
        border-color: #d6e2ce;
        border-top-color: var(--dark-sage);
        box-shadow: 0 8px 20px rgba(90, 107, 71, 0.1);
    }
    .stat-card-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 16px; }
    .stat-card h4 { margin: 0; font-size: .72rem; line-height: 1.35; letter-spacing: .045em; color: #687365; font-weight: 750; }
    .stat-card-icon { display: grid; place-items: center; flex: 0 0 34px; width: 34px; height: 34px; border-radius: 9px; background: #f0f5ec; color: #5f7f49; font-size: .9rem; }
    .stat-card h2 { 
        margin: 0; 
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text);
    }
    .stat-card-note { display: block; margin-top: 5px; color: #7a8475; font-size: .74rem; }
    .stat-card.is-attention { border: 1px solid #edcf8c; border-top: 4px solid var(--status-pending); background: linear-gradient(145deg, #fffdf7, #fff8e8); box-shadow: 0 5px 16px rgba(170, 108, 9, .10); }
    .stat-card.is-attention .stat-card-icon { background: var(--status-pending-soft); color: var(--status-pending-text); }
    .stat-card.is-attention h2 { color: var(--status-pending-text); font-size: 2rem; }
    .stat-card.is-attention:hover { border-color: #e4c578; border-top-color: var(--status-pending); background: linear-gradient(145deg, #fffdf7, #fff5dc); }
    .stat-card.is-clear { border-color: #cfe3d0; border-top-color: var(--status-approved); background: linear-gradient(145deg, #fbfefb, #f1f8f1); box-shadow: 0 4px 14px rgba(62, 122, 75, .08); }
    .stat-card.is-clear .stat-card-icon { background: var(--status-approved-soft); color: var(--status-approved-text); }
    .stat-card.is-clear h2 { color: var(--status-approved-text); }
    .stat-card.is-clear .stat-card-note { color: var(--status-approved-text); font-weight: 650; }
    .analytic-card { 
        background: #FAFAF7;
        border: 1px solid #EBEBE3; 
        border-radius: 10px;
        padding: 17px 18px;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .analytic-card:hover { border-color: #cbd9c1; box-shadow: 0 5px 14px rgba(90, 107, 71, .08); }
    .analytic-card h5 { margin: 0; font-size: .88rem; color: #465736; }
    .metric-progress-heading { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 11px; }
    .metric-percent { color: #5f7f49; font-size: 1.05rem; font-weight: 750; white-space: nowrap; }
    .metric-percent-label { display: block; color: #74806d; font-size: .68rem; font-weight: 600; text-align: right; }
    .metric-progress-track { display: flex; width: 100%; height: 10px; overflow: hidden; border-radius: 99px; background: #e9ede5; }
    .metric-segment { display: block; height: 100%; min-width: 0; transition: width .35s ease; }
    .metric-segment-approved { background: var(--status-approved); }
    .metric-segment-pending { background: var(--status-pending); }
    .metric-segment-rejected { background: var(--status-rejected); }
    .metric-counts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 9px; margin-top: 13px; color: #66705f; font-size: .73rem; }
    .metric-count-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; column-gap: 6px; row-gap: 3px; min-width: 0; }
    .metric-count-item strong { grid-column: 1; color: #33402d; font-size: .85rem; }
    .metric-status-label { display: flex; align-items: center; gap: 5px; white-space: nowrap; }
    .metric-count-item .metric-status-label { grid-column: 1 / -1; }
    .metric-status-dot { flex: 0 0 8px; width: 8px; height: 8px; border-radius: 50%; }
    .metric-status-dot-approved { background: var(--status-approved); }
    .metric-status-dot-pending { background: var(--status-pending); }
    .metric-status-dot-rejected { background: var(--status-rejected); }
    .metric-status-percent { grid-column: 2; color: #818a7d; font-size: .69rem; text-align: right; }
    .metric-total { margin-top: 7px; color: #818a7d; font-size: .73rem; }
    .metric-empty { color: #687365; font-size: .76rem; margin-top: 8px; }
        .analytics-section { background: var(--white); padding: 20px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 20px; box-shadow: 0 3px 12px rgba(34,48,28,.04); }
        .analytics-section h3 { font-size: 1rem; color: var(--dark-sage); border-left: 4px solid var(--sage); padding-left: 10px; margin-top: 0; margin-bottom: 15px; }
        .analytics-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }

        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; align-items: start; }
        .left-column, .right-column { display: flex; flex-direction: column; gap: 25px; width: 100%; min-width: 0; }

        .table-container { background: var(--white); padding: 25px; border-radius: 8px; border: 1px solid var(--border); width: 100%; box-sizing: border-box; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .table-container h3 { font-size: 1.1rem; color: var(--dark-sage); border-left: 4px solid var(--sage); padding-left: 10px; margin-top: 0; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #F4F4ED; padding: 12px; font-size: 0.75rem; text-transform: uppercase; color: #666; }
        td { padding: 12px; border-bottom: 1px solid #F0F0F0; font-size: 0.85rem; }
        
        .btn-approve { background: var(--status-approved); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.7rem; display: inline-block; border: none; cursor: pointer; }
        .btn-reject { background: var(--status-rejected); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.7rem; margin-left: 5px; display: inline-block; border: none; cursor: pointer; }

        .schedule-card-list { display: flex; flex-direction: column; gap: 15px; }
        .schedule-item { background: #FAFAF7; border: 1px solid #EBEBE3; border-radius: 6px; padding: 15px; position: relative; }
        .schedule-item.maternal-border { border-left: 4px solid var(--sage); }
        .schedule-item.infant-border { border-left: 4px solid #e74c3c; }
        
        .sched-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .sched-date { font-weight: bold; font-size: 0.95rem; color: #2C3E50; }
        .status-badge { background: #E2E8F0; color: #4A5568; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase; }
        .pending { background: var(--status-pending-soft); color: var(--status-pending-text); }
        .approved { background: var(--status-approved-soft); color: var(--status-approved-text); }
        .rejected, .declined { background: var(--status-rejected-soft); color: var(--status-rejected-text); }
        .completed { background: #f0f4f8; color: #2563eb; }
        .reschedule-requested { background: #fef2f2; color: #dc2626; }
        .cancelled { background: #f3f4f6; color: #6b7280; }
        
        .sched-patient-name { font-size: 1rem; font-weight: bold; color: #2D2D2D; margin-bottom: 4px; text-transform: capitalize; }
        .sched-type { font-size: 0.85rem; color: #666; margin-bottom: 12px; }
        
        .sched-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .btn-mark-done { background: #D1E7DD; color: #0F5132; border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; cursor: pointer; }
        .btn-mark-done:hover { background: #badbcc; }
        
        .resched-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .date-input { 
            padding: 5px 8px; 
            border: 1px solid #CCC; 
            border-radius: 4px; 
            font-size: 0.8rem; 
            font-family: inherit; 
            background: white; 
        }
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

        .dashboard-notice {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 5000;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            width: min(460px, calc(100vw - 32px));
            padding: 18px 48px 18px 18px;
            border: 1px solid #dce9d5;
            border-left: 4px solid #5c7c43;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 14px 38px rgba(31, 45, 26, .16);
            color: #253321;
            animation: dashboard-notice-in .28s ease-out;
        }
        .dashboard-notice.is-error {
            border-color: #f1d5d2;
            border-left-color: #b5473c;
        }
        .dashboard-notice-mark {
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #edf5e8;
            color: #52763c;
        }
        .dashboard-notice.is-error .dashboard-notice-mark {
            background: #fff0ee;
            color: #a63b32;
        }
        .dashboard-notice-mark svg { width: 18px; height: 18px; }
        .dashboard-notice-copy { min-width: 0; }
        .dashboard-notice-title {
            margin: 0 0 4px;
            color: #2d5016;
            font-size: .94rem;
            font-weight: 700;
        }
        .dashboard-notice.is-error .dashboard-notice-title { color: #9f342c; }
        .dashboard-notice-message {
            margin: 0;
            color: #586356;
            font-size: .88rem;
            line-height: 1.45;
        }
        .dashboard-notice-close {
            position: absolute;
            top: 10px;
            right: 12px;
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #687365;
            font-size: 1.25rem;
            cursor: pointer;
        }
        .dashboard-notice-close:hover { background: #f3f5f1; }
        .dashboard-notice.is-hiding { animation: dashboard-notice-out .2s ease-in forwards; }
        @keyframes dashboard-notice-in {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes dashboard-notice-out {
            to { opacity: 0; transform: translateY(-6px); }
        }
        @media (max-width: 560px) {
            .dashboard-notice { top: 12px; right: 12px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .dashboard-notice, .dashboard-notice.is-hiding { animation-duration: .01ms; }
        }
        .reject-confirm-backdrop {
            position: fixed;
            inset: 0;
            z-index: 6000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(23, 32, 21, .48);
            backdrop-filter: blur(3px);
        }
        .reject-confirm-backdrop.is-open { display: flex; }
        .reject-confirm-dialog {
            width: min(100%, 420px);
            padding: 28px;
            border: 1px solid #e5e9e1;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(20, 30, 18, .24);
            animation: dashboard-notice-in .2s ease-out;
        }
        .reject-confirm-dialog h2 {
            margin: 0 0 9px;
            color: #2d3a29;
            font-size: 1.18rem;
        }
        .reject-confirm-dialog p {
            margin: 0;
            color: #647060;
            font-size: .92rem;
            line-height: 1.55;
        }
        .reject-confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }
        .reject-confirm-actions button {
            min-height: 40px;
            padding: 9px 16px;
            border: 1px solid transparent;
            border-radius: 8px;
            font: inherit;
            font-size: .85rem;
            font-weight: 650;
            cursor: pointer;
        }
        .reject-confirm-cancel {
            border-color: #dce2d8 !important;
            background: #fff;
            color: #53604d;
        }
        .reject-confirm-cancel:hover { background: #f5f7f3; }
        .reject-confirm-submit {
            background: #b5473c;
            color: #fff;
        }
        .reject-confirm-submit:hover { background: #96392f; }
        .dashboard-empty-state {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 54px;
            padding: 12px;
            color: #74806d;
            font-size: .82rem;
            text-align: center;
        }
        .dashboard-empty-state i { color: #8dae74; font-size: 1rem; }
        .table-empty-cell { padding: 8px !important; }
        .section-link {
            float: right;
            color: #5f7f49;
            font-size: .76rem;
            font-weight: 650;
            text-decoration: none;
        }
        .section-link:hover { text-decoration: underline; }
        [data-theme="dark"] .quick-action {
            background: var(--dm-surface) !important;
            border-color: var(--dm-border) !important;
            color: var(--dm-text2) !important;
        }
        [data-theme="dark"] .quick-action-primary {
            background: var(--dm-sage) !important;
            border-color: var(--dm-sage) !important;
            color: #1A1D1B !important;
        }
        [data-theme="dark"] .quick-action-menu { background: var(--dm-surface) !important; border-color: var(--dm-border) !important; }
        [data-theme="dark"] .quick-action-menu a { color: var(--dm-text) !important; }
        [data-theme="dark"] .quick-action-menu a:hover,
        [data-theme="dark"] .quick-action-menu a:focus-visible { background: var(--dm-surface2) !important; }
        [data-theme="dark"] .quick-action-menu a i { color: var(--dm-sage) !important; }
        [data-theme="dark"] .dashboard-sync { color: var(--dm-text2); }
        [data-theme="dark"] .dashboard-sync i { color: var(--dm-sage); }
        [data-theme="dark"] .stat-card.is-attention { background: linear-gradient(145deg, #302d22, #332d1d) !important; border-color: #66532b !important; border-top-color: #e2a12d !important; }
        [data-theme="dark"] .stat-card.is-attention h2,
        [data-theme="dark"] .stat-card.is-attention .stat-card-note { color: #f4d58c; }
        [data-theme="dark"] .stat-card.is-clear { background: linear-gradient(145deg, #242d26, #253329) !important; border-color: #38563e !important; border-top-color: #70b27a !important; }
        [data-theme="dark"] .stat-card.is-clear h2,
        [data-theme="dark"] .stat-card.is-clear .stat-card-note { color: #a7d8ae; }
        [data-theme="dark"] .pending { background: #51411f; color: #f4d58c; }
        [data-theme="dark"] .approved { background: #24412d; color: #a7d8ae; }
        [data-theme="dark"] .rejected,
        [data-theme="dark"] .declined { background: #512b2a; color: #f3aaaa; }
        [data-theme="dark"] .btn-approve { background: #5f9b63; color: #fff !important; }
        [data-theme="dark"] .btn-reject { background: #bf514c; color: #fff !important; }
        [data-theme="dark"] .metric-progress-heading h5,
        [data-theme="dark"] .metric-counts,
        [data-theme="dark"] .metric-count-item strong,
        [data-theme="dark"] .metric-status-percent,
        [data-theme="dark"] .metric-total,
        [data-theme="dark"] .metric-empty,
        [data-theme="dark"] .metric-percent-label { color: var(--dm-text2) !important; }
        [data-theme="dark"] .metric-progress-track { background: #3A403A; }
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 1100px) {
            .dashboard-grid { grid-template-columns: minmax(0, 1fr); }
            .left-column, .right-column { gap: 18px; }
        }
        @media (max-width: 760px) {
            .main-content { padding: 28px 20px; padding-top: 78px; }
            .page-header { align-items: flex-start; }
            .page-header-tools { gap: 8px; }
            .dashboard-date { display: none; }
            .dashboard-sync { font-size: .66rem; }
            .analytics-grid { grid-template-columns: minmax(0, 1fr); }
            .dashboard-grid { gap: 18px; }
            .table-container, .analytics-section { padding: 17px; }
        }
        @media (max-width: 520px) {
            .main-content { padding: 78px 14px 24px; }
            .page-header { margin-bottom: 18px; padding-bottom: 13px; }
            .page-header h1 { font-size: 1.4rem; }
            .page-header p { font-size: .82rem; }
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 18px; }
            .stat-card { padding: 14px; }
            .stat-card h4 { font-size: .65rem; }
            .stat-card h2 { font-size: 1.5rem; }
            .stat-card-icon { width: 30px; height: 30px; flex-basis: 30px; font-size: .8rem; }
            .stat-card-note { font-size: .68rem; }
            .quick-actions { gap: 8px; margin-bottom: 18px; }
            .quick-actions-label { flex-basis: 100%; }
            .quick-action { flex: 1 1 auto; justify-content: center; padding: 8px 9px; font-size: .75rem; }
            .analytic-card { padding: 14px; }
            .metric-counts { gap: 6px 12px; }
            .table-container { padding: 14px; overflow-x: auto; }
            .table-container table { min-width: 480px; }
        }
        @media (max-width: 380px) {
            .metric-counts { grid-template-columns: minmax(0, 1fr); }
            .metric-count-item { grid-template-columns: minmax(0, 1fr) auto auto; }
            .metric-count-item .metric-status-label { grid-column: 1; }
            .metric-count-item strong { grid-column: 2; }
            .metric-status-percent { grid-column: 3; }
        }
        @media (max-width: 360px) {
            .stats-grid { grid-template-columns: minmax(0, 1fr); }
        }
        @media (prefers-reduced-motion: reduce) {
            .reject-confirm-dialog { animation-duration: .01ms; }
        }
        </style>
    
</head>
<body>

<?php include 'super_admin_sidebar.php'; ?>

<div class="main-content" id="mainDashboard">
    <div class="page-header">
        <div class="page-heading-copy">
            <h1>Good day, Super Admin</h1>
            <p>Your health center overview at a glance.</p>
        </div>
        <div class="page-header-tools">
            <span class="dashboard-date"><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?php echo date('l, F j, Y'); ?></span>
            <?php include '../notif_bell.php'; ?>
        </div>
    </div>

    <?php if ($dashboardNotice !== null): ?>
    <div class="dashboard-notice<?php echo $dashboardNoticeIsError ? ' is-error' : ''; ?>" id="dashboardNotice" role="<?php echo $dashboardNoticeIsError ? 'alert' : 'status'; ?>" aria-live="<?php echo $dashboardNoticeIsError ? 'assertive' : 'polite'; ?>">
        <span class="dashboard-notice-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m5 12 4 4L19 6"></path>
            </svg>
        </span>
        <div class="dashboard-notice-copy">
            <p class="dashboard-notice-title">Action completed</p>
            <p class="dashboard-notice-message"><?php echo htmlspecialchars($dashboardNotice, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <button type="button" class="dashboard-notice-close" aria-label="Dismiss notification">&times;</button>
    </div>
    <?php endif; ?>

    <div class="reject-confirm-backdrop" id="rejectConfirm" aria-hidden="true">
        <section class="reject-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="rejectConfirmTitle" aria-describedby="rejectConfirmDescription">
            <h2 id="rejectConfirmTitle">Confirm rejection</h2>
                <p id="rejectConfirmDescription">This will reject the selected pending item. Maternal and child registrations are kept for reporting; worker accounts are removed.</p>
            <div class="reject-confirm-actions">
                <button type="button" class="reject-confirm-cancel" id="cancelReject">Cancel</button>
                <button type="button" class="reject-confirm-submit" id="confirmReject">Reject record</button>
            </div>
        </section>
    </div>

    <nav class="quick-actions" aria-label="Dashboard quick actions">
        <span class="quick-actions-label">Quick actions</span>
        <a class="quick-action quick-action-primary" href="#pendingApprovals"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Review approvals</a>
        <details class="quick-action-dropdown">
            <summary class="quick-action quick-action-primary"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Register <i class="fa-solid fa-chevron-down quick-action-chevron" aria-hidden="true"></i></summary>
            <div class="quick-action-menu">
                <a href="admin_maternal_reg.php"><i class="fa-solid fa-person-pregnant" aria-hidden="true"></i> Maternal patient</a>
                <a href="admin_child_reg.php"><i class="fa-solid fa-baby" aria-hidden="true"></i> Child patient</a>
                <a href="../register_worker.php?redirect=admin/super_admin_dashboard.php%23pendingWorkersPad"><i class="fa-solid fa-user-nurse" aria-hidden="true"></i> Health worker</a>
            </div>
        </details>
        <a class="quick-action" href="vaccine_inventory.php"><i class="fa-solid fa-syringe" aria-hidden="true"></i> Vaccine inventory</a>
        <a class="quick-action" href="schedule_management.php"><i class="fa-regular fa-calendar-plus" aria-hidden="true"></i> Manage schedules</a>
        <span class="dashboard-sync" role="status" aria-live="polite"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Updated <time id="dashboardLastUpdated" datetime="<?php echo date(DATE_ATOM); ?>"><?php echo date('g:i:s A'); ?></time></span>
    </nav>

    <div class="stats-grid" aria-label="Health center summary">
        <article class="stat-card">
            <div class="stat-card-head"><h4>TOTAL PATIENTS</h4><span class="stat-card-icon"><i class="fa-solid fa-people-group" aria-hidden="true"></i></span></div>
            <h2 id="statTotalPatients"><?php echo $total_patients; ?></h2>
            <span class="stat-card-note">Approved patient records</span>
        </article>
        <article class="stat-card">
            <div class="stat-card-head"><h4>APPROVED INFANTS</h4><span class="stat-card-icon"><i class="fa-solid fa-baby" aria-hidden="true"></i></span></div>
            <h2 id="statInfants"><?php echo $total_newborns; ?></h2>
            <span class="stat-card-note">Child health records</span>
        </article>
        <article class="stat-card">
            <div class="stat-card-head"><h4>APPROVED MATERNAL</h4><span class="stat-card-icon"><i class="fa-solid fa-person-pregnant" aria-hidden="true"></i></span></div>
            <h2 id="statMaternal"><?php echo $total_pregnant; ?></h2>
            <span class="stat-card-note">Maternal health records</span>
        </article>
        <article class="stat-card <?php echo (int) $total_pending > 0 ? 'is-attention' : 'is-clear'; ?>" id="pendingApprovalCard">
            <div class="stat-card-head"><h4>FOR APPROVAL</h4><span class="stat-card-icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span></div>
            <h2 id="statPending"><?php echo $total_pending; ?></h2>
            <span class="stat-card-note" id="pendingApprovalNote"><?php echo (int) $total_pending > 0 ? htmlspecialchars($total_pending . ' ' . ((int) $total_pending === 1 ? 'item needs' : 'items need') . ' review', ENT_QUOTES, 'UTF-8') : 'All caught up — no pending approvals'; ?></span>
        </article>
        <article class="stat-card">
            <div class="stat-card-head"><h4>HEALTH WORKERS</h4><span class="stat-card-icon"><i class="fa-solid fa-user-nurse" aria-hidden="true"></i></span></div>
            <h2 id="statWorkers"><?php echo $total_workers; ?></h2>
            <span class="stat-card-note">Approved staff accounts</span>
        </article>
    </div>

    <section class="analytics-section" aria-labelledby="registrationMetricsTitle">
        <h3 id="registrationMetricsTitle">Registration &amp; Verification</h3>
        <div class="analytics-grid">
            <div class="analytic-card" id="maternalRegistrationMetric">
                <div class="metric-progress-heading">
                    <h5>Maternal registration</h5>
                    <div><span class="metric-percent" id="matRegistrationPercent"><?php echo $mat_success_pct; ?>%</span><span class="metric-percent-label">approved rate</span></div>
                </div>
                <div class="metric-progress-track" id="matRegistrationTrack" role="img" aria-label="Maternal registrations: <?php echo $mat_success_pct; ?>% approved, <?php echo $mat_pending_pct; ?>% pending, <?php echo $mat_rejected_pct; ?>% rejected.">
                    <span class="metric-segment metric-segment-approved" id="matRegistrationApprovedBar" style="width: <?php echo $mat_total_reg > 0 ? ($mat_approved / $mat_total_reg * 100) : 0; ?>%;"></span>
                    <span class="metric-segment metric-segment-pending" id="matRegistrationPendingBar" style="width: <?php echo $mat_total_reg > 0 ? ($mat_pending / $mat_total_reg * 100) : 0; ?>%;"></span>
                    <span class="metric-segment metric-segment-rejected" id="matRegistrationRejectedBar" style="width: <?php echo $mat_total_reg > 0 ? ($mat_rejected / $mat_total_reg * 100) : 0; ?>%;"></span>
                </div>
                <div class="metric-counts">
                    <div class="metric-count-item"><span class="metric-status-label"><i class="metric-status-dot metric-status-dot-approved"></i>Approved</span><strong id="matRegistrationApproved"><?php echo $mat_approved; ?></strong><span class="metric-status-percent" id="matRegistrationApprovedPct"><?php echo $mat_success_pct; ?>%</span></div>
                    <div class="metric-count-item"><span class="metric-status-label"><i class="metric-status-dot metric-status-dot-pending"></i>Pending</span><strong id="matRegistrationPending"><?php echo $mat_pending; ?></strong><span class="metric-status-percent" id="matRegistrationPendingPct"><?php echo $mat_pending_pct; ?>%</span></div>
                    <div class="metric-count-item"><span class="metric-status-label"><i class="metric-status-dot metric-status-dot-rejected"></i>Rejected</span><strong id="matRegistrationRejected"><?php echo $mat_rejected; ?></strong><span class="metric-status-percent" id="matRegistrationRejectedPct"><?php echo $mat_rejected_pct; ?>%</span></div>
                </div>
                <div class="metric-total" id="matRegistrationTotal"><?php echo $mat_total_reg; ?> total registrations</div>
                <div class="metric-empty" id="matRegistrationEmpty" <?php echo (int) $mat_total_reg > 0 ? 'hidden' : ''; ?>>No maternal registrations yet.</div>
            </div>
            <div class="analytic-card" id="childRegistrationMetric">
                <div class="metric-progress-heading">
                    <h5>Child / infant registration</h5>
                    <div><span class="metric-percent" id="childRegistrationPercent"><?php echo $child_success_pct; ?>%</span><span class="metric-percent-label">approved rate</span></div>
                </div>
                <div class="metric-progress-track" id="childRegistrationTrack" role="img" aria-label="Child registrations: <?php echo $child_success_pct; ?>% approved, <?php echo $child_pending_pct; ?>% pending, <?php echo $child_rejected_pct; ?>% rejected.">
                    <span class="metric-segment metric-segment-approved" id="childRegistrationApprovedBar" style="width: <?php echo $child_total_reg > 0 ? ($child_approved / $child_total_reg * 100) : 0; ?>%;"></span>
                    <span class="metric-segment metric-segment-pending" id="childRegistrationPendingBar" style="width: <?php echo $child_total_reg > 0 ? ($child_pending / $child_total_reg * 100) : 0; ?>%;"></span>
                    <span class="metric-segment metric-segment-rejected" id="childRegistrationRejectedBar" style="width: <?php echo $child_total_reg > 0 ? ($child_rejected / $child_total_reg * 100) : 0; ?>%;"></span>
                </div>
                <div class="metric-counts">
                    <div class="metric-count-item"><span class="metric-status-label"><i class="metric-status-dot metric-status-dot-approved"></i>Approved</span><strong id="childRegistrationApproved"><?php echo $child_approved; ?></strong><span class="metric-status-percent" id="childRegistrationApprovedPct"><?php echo $child_success_pct; ?>%</span></div>
                    <div class="metric-count-item"><span class="metric-status-label"><i class="metric-status-dot metric-status-dot-pending"></i>Pending</span><strong id="childRegistrationPending"><?php echo $child_pending; ?></strong><span class="metric-status-percent" id="childRegistrationPendingPct"><?php echo $child_pending_pct; ?>%</span></div>
                    <div class="metric-count-item"><span class="metric-status-label"><i class="metric-status-dot metric-status-dot-rejected"></i>Rejected</span><strong id="childRegistrationRejected"><?php echo $child_rejected; ?></strong><span class="metric-status-percent" id="childRegistrationRejectedPct"><?php echo $child_rejected_pct; ?>%</span></div>
                </div>
                <div class="metric-total" id="childRegistrationTotal"><?php echo $child_total_reg; ?> total registrations</div>
                <div class="metric-empty" id="childRegistrationEmpty" <?php echo (int) $child_total_reg > 0 ? 'hidden' : ''; ?>>No child registrations yet.</div>
            </div>
        </div>
    </section>

    <section class="analytics-section" aria-labelledby="scheduleMetricsTitle">
        <h3 id="scheduleMetricsTitle">Schedule &amp; Vaccination Progress</h3>
        <div class="analytics-grid">
            <div class="analytic-card">
                <div class="metric-progress-heading">
                    <h5>Maternal appointments completed</h5>
                    <span class="metric-percent"><?php echo $sched_mat_success_pct; ?>%</span>
                </div>
                <div class="metric-progress-track" role="progressbar" aria-label="Maternal appointments completed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $sched_mat_success_pct; ?>">
                    <span class="metric-progress-fill" style="width: <?php echo $sched_mat_success_pct; ?>%;"></span>
                </div>
                <div class="metric-counts">
                    <span>Completed <strong><?php echo $sched_mat_completed; ?></strong></span>
                    <span>Not completed <strong><?php echo $sched_mat_not_success; ?></strong></span>
                </div>
                <div class="metric-total"><?php echo $sched_mat_total; ?> total appointments</div>
                <?php if ((int) $sched_mat_total === 0): ?><div class="metric-empty">No maternal appointments to report yet.</div><?php endif; ?>
            </div>
            <div class="analytic-card">
                <div class="metric-progress-heading">
                    <h5>Child immunizations completed</h5>
                    <span class="metric-percent"><?php echo $sched_child_success_pct; ?>%</span>
                </div>
                <div class="metric-progress-track" role="progressbar" aria-label="Child immunizations completed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $sched_child_success_pct; ?>">
                    <span class="metric-progress-fill" style="width: <?php echo $sched_child_success_pct; ?>%;"></span>
                </div>
                <div class="metric-counts">
                    <span>Completed <strong><?php echo $sched_child_completed; ?></strong></span>
                    <span>Not completed <strong><?php echo $sched_child_not_success; ?></strong></span>
                </div>
                <div class="metric-total"><?php echo $sched_child_total; ?> total appointments</div>
                <?php if ((int) $sched_child_total === 0): ?><div class="metric-empty">No child immunizations to report yet.</div><?php endif; ?>
            </div>
        </div>
    </section>

    <div class="dashboard-grid">
        
        <!-- LEFT COLUMN -->
        <!-- Ang Worker, Newborn, at Maternal pending/verification tables ay kasalukuyang
             nasa loob pa ng maternal_verification_pad.php (TODO: ililipat pa ang Newborn
             pad papunta sa sarili nitong file, hal. infant_verification_pad.php).
             Umaasa ang $conn, $pending_workers, $pending_list, at $pending_preg_list
             (na naka-define sa itaas ng file na ito) sa loob ng mga include na ito. -->
        <?php include 'maternal_verification_pad.php'; ?>
        <?php // include 'infant_verification_pad.php'; -- idadagdag pagkatapos ma-split ?>

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
                        <?php 
                        $schedStatus = $sched['status'] ?? 'Pending';
                        $statusClass = strtolower(str_replace(' ', '-', $schedStatus));
                        ?>
                        <div class="sched-header-row">
                            <span class="sched-date"><?php echo htmlspecialchars($schedDate); ?></span>
                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($schedStatus); ?></span>
                        </div>
                        <div class="sched-patient-name"><?php echo htmlspecialchars($patientName); ?></div>
                        <div class="sched-type">Type: <?php echo htmlspecialchars($serviceType); ?></div>
                        
                        <form method="POST" action="" class="sched-actions">
                            <input type="hidden" name="schedule_id" value="<?php echo $schedId; ?>">
                            <input type="hidden" name="redirect_to" value="admin/super_admin_dashboard.php">
                            <button type="submit" name="mark_done_maternal" class="btn-mark-done">Mark Done</button>
                            <div class="resched-group">
                                <input type="date" name="new_date" class="date-input" required>
                                <button type="submit" name="reschedule_maternal" class="btn-resched">Resched</button>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; else: ?>
                        <div class="dashboard-empty-state"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i><span>No upcoming maternal appointments.</span></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Child Vaccinations -->
            <div class="table-container">
                <h3>Upcoming Child Vaccinations</h3>
                <div class="schedule-card-list">
                    <?php if (!empty($upcoming_infant)): foreach($upcoming_infant as $sched): 
                        $infantName = $sched['full_name'] ?? $sched['child_name'] ?? 'N/A';
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
                        
                        <form method="POST" action="" class="sched-actions">
                            <input type="hidden" name="schedule_id" value="<?php echo $schedId; ?>">
                            <input type="hidden" name="redirect_to" value="admin/super_admin_dashboard.php">
                            <button type="submit" name="mark_done_infant" class="btn-mark-done">Mark Done</button>
                            <div class="resched-group">
                                <input type="date" name="new_date" class="date-input" required>
                                <button type="submit" name="reschedule_infant" class="btn-resched">Resched</button>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; else: ?>
                        <div class="dashboard-empty-state"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i><span>No upcoming child immunizations.</span></div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const rejectDialog = document.getElementById("rejectConfirm");
        const cancelReject = document.getElementById("cancelReject");
        const confirmReject = document.getElementById("confirmReject");
        let pendingRejectUrl = null;
        let rejectTrigger = null;

        const closeRejectDialog = () => {
            rejectDialog.classList.remove("is-open");
            rejectDialog.setAttribute("aria-hidden", "true");
            pendingRejectUrl = null;
            if (rejectTrigger) rejectTrigger.focus();
        };

        document.addEventListener("click", event => {
            const rejectLink = event.target.closest("a.btn-reject");
            if (rejectLink && rejectLink.href && !rejectLink.href.endsWith("#")) {
                event.preventDefault();
                pendingRejectUrl = rejectLink.href;
                rejectTrigger = rejectLink;
                rejectDialog.classList.add("is-open");
                rejectDialog.setAttribute("aria-hidden", "false");
                confirmReject.focus();
                return;
            }

            if (event.target === rejectDialog) closeRejectDialog();
        });

        cancelReject.addEventListener("click", closeRejectDialog);
        confirmReject.addEventListener("click", () => {
            if (pendingRejectUrl) window.location.assign(pendingRejectUrl);
        });
        document.addEventListener("keydown", event => {
            if (event.key === "Escape" && rejectDialog.classList.contains("is-open")) {
                closeRejectDialog();
            }
        });

        const notice = document.getElementById("dashboardNotice");
        if (notice) {
            const dismissNotice = () => {
                notice.classList.add("is-hiding");
                window.setTimeout(() => notice.remove(), 220);
            };
            notice.querySelector(".dashboard-notice-close").addEventListener("click", dismissNotice);
            window.setTimeout(dismissNotice, 6000);

            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete("msg");
            window.history.replaceState({}, document.title, currentUrl.pathname + currentUrl.search + currentUrl.hash);
        }

    });

    // LIVE STREAMING / POLLING INTERVAL (Bawat 4 segundo)
    setInterval(function() {
        var nbModal = document.getElementById('newbornVerifyModal');
        var matModal = document.getElementById('verifyModal');
        if ((nbModal && nbModal.style.display === 'block') || (matModal && matModal.style.display === 'block')) {
            return; 
        }

        // Check if any date inputs have values - don't update if user is typing
        var dateInputs = document.querySelectorAll('input[type="date"]');
        var hasActiveInput = false;
        dateInputs.forEach(function(input) {
            if (input.value !== '' || input === document.activeElement) {
                hasActiveInput = true;
            }
        });
        
        if (hasActiveInput) {
            return; // Skip update if user is working with date fields
        }

        fetch('fetch_dashboard_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) return;

                // 1. Update Stat Cards
                document.getElementById('statTotalPatients').innerText = data.total_patients;
                document.getElementById('statInfants').innerText = data.total_newborns;
                document.getElementById('statMaternal').innerText = data.total_pregnant;
                document.getElementById('statPending').innerText = data.total_pending;
                document.getElementById('statWorkers').innerText = data.total_workers;
                const pendingCount = Math.max(0, Number(data.total_pending) || 0);
                const pendingCard = document.getElementById('pendingApprovalCard');
                pendingCard.classList.toggle('is-attention', pendingCount > 0);
                pendingCard.classList.toggle('is-clear', pendingCount === 0);
                document.getElementById('pendingApprovalNote').innerText = pendingCount > 0
                    ? pendingCount + (pendingCount === 1 ? ' item needs review' : ' items need review')
                    : 'All caught up — no pending approvals';

                const updatedAt = new Date();
                const lastUpdated = document.getElementById('dashboardLastUpdated');
                lastUpdated.dateTime = updatedAt.toISOString();
                lastUpdated.innerText = updatedAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', second: '2-digit' });

                // Keep registration progress and counts synchronized with the live summary.
                        const updateRegistrationMetric = (prefix, approved, pending, rejected, total, label) => {
                            const counts = [approved, pending, rejected].map(value => Math.max(0, Number(value) || 0));
                            const registrationTotal = Math.max(0, Number(total) || 0);
                            const percentages = counts.map(count => registrationTotal > 0 ? Math.round((count / registrationTotal) * 100) : 0);
                            const barIds = ['ApprovedBar', 'PendingBar', 'RejectedBar'];
                            const statusIds = ['Approved', 'Pending', 'Rejected'];
                            document.getElementById(prefix + 'Percent').innerText = percentages[0] + '%';
                            document.getElementById(prefix + 'Total').innerText = registrationTotal + ' total registrations';
                            document.getElementById(prefix + 'Empty').hidden = registrationTotal > 0;
                            document.getElementById(prefix + 'Track').setAttribute(
                                'aria-label',
                                label + ' registrations: ' + percentages[0] + '% approved, ' +
                                    percentages[1] + '% pending, ' + percentages[2] + '% rejected.'
                            );

                            counts.forEach((count, index) => {
                                const share = registrationTotal > 0 ? (count / registrationTotal) * 100 : 0;
                                document.getElementById(prefix + barIds[index]).style.width = share + '%';
                                document.getElementById(prefix + statusIds[index]).innerText = count;
                                document.getElementById(prefix + statusIds[index] + 'Pct').innerText = percentages[index] + '%';
                            });
                        };
                        updateRegistrationMetric('matRegistration', data.mat_approved, data.mat_pending, data.mat_rejected, data.mat_total_reg, 'Maternal');
                        updateRegistrationMetric('childRegistration', data.child_approved, data.child_pending, data.child_rejected, data.child_total_reg, 'Child');

                // 3. Update Tables and Schedule Lists ONLY if no date inputs are active
                const tables = document.querySelectorAll('.table-container table tbody');
                if(tables.length >= 3) {
                    tables[0].innerHTML = data.pending_workers_html;
                    tables[1].innerHTML = data.pending_newborns_html;
                    tables[2].innerHTML = data.pending_maternal_html;
                }

                const schedLists = document.querySelectorAll('.schedule-card-list');
                if(schedLists.length >= 2) {
                    schedLists[0].innerHTML = data.upcoming_maternal_html;
                    schedLists[1].innerHTML = data.upcoming_infant_html;
                }
            })
            .catch(error => console.log('Live sync paused or offline error:', error));
    }, 4000);
</script>

</script>

</script>

<?php $notif_path = '../'; include '../notif_js.php'; ?>
</body>
</html>