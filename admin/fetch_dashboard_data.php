<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

// --- COUNTS ---
$total_newborns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status='Approved'"))['t'] ?? 0;
$total_pregnant = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status='Approved'"))['t'] ?? 0;
$total_patients = $total_newborns + $total_pregnant;
$total_workers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Approved'"))['t'] ?? 0;

$total_pending = (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status='Pending'"))['t'] ?? 0) + 
                 (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status='Pending'"))['t'] ?? 0) +
                 (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0);

// --- MATERNAL METRICS ---
$mat_total_reg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration"))['t'] ?? 0;
$mat_approved = $total_pregnant;
$mat_not_success = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status != 'Approved'"))['t'] ?? 0;
$mat_success_deg = ($mat_total_reg > 0) ? (($mat_approved / $mat_total_reg) * 360) : 0;
$mat_success_pct = ($mat_total_reg > 0) ? round(($mat_approved / $mat_total_reg) * 100) : 0;
$mat_unsuccess_pct = ($mat_total_reg > 0) ? round(($mat_not_success / $mat_total_reg) * 100) : 0;

// --- CHILD METRICS ---
$child_total_reg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children"))['t'] ?? 0;
$child_approved = $total_newborns;
$child_not_success = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status != 'Approved'"))['t'] ?? 0;
$child_success_deg = ($child_total_reg > 0) ? (($child_approved / $child_total_reg) * 360) : 0;
$child_success_pct = ($child_total_reg > 0) ? round(($child_approved / $child_total_reg) * 100) : 0;
$child_unsuccess_pct = ($child_total_reg > 0) ? round(($child_not_success / $child_total_reg) * 100) : 0;

// --- FETCH LISTS HTML BLOCKS (Para maiwasan masira ang tables at schedule items) ---
// 1. Pending Workers
$pending_workers_html = '';
$pw_query = mysqli_query($conn, "SELECT * FROM users WHERE role='Admin' AND status='Pending' ORDER BY created_at DESC");
if (mysqli_num_rows($pw_query) > 0) {
    while($row = mysqli_fetch_assoc($pw_query)) {
        $pending_workers_html .= '<tr>';
        $pending_workers_html .= '<td>' . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . '</td>';
        $pending_workers_html .= '<td>' . htmlspecialchars($row['email']) . '</td>';
        $pending_workers_html .= '<td>';
        $pending_workers_html .= '<a href="super_admin_dashboard.php?approve_worker_id=' . $row['id'] . '" class="btn-approve">APPROVE</a> ';
        $pending_workers_html .= '<a href="super_admin_dashboard.php?remove_worker_id=' . $row['id'] . '" class="btn-reject" onclick="return confirm(\'Reject this worker?\')">REJECT</a>';
        $pending_workers_html .= '</td>';
        $pending_workers_html .= '</tr>';
    }
} else {
    $pending_workers_html = '<tr><td colspan="3" align="center">No pending worker accounts.</td></tr>';
}

// 2. Pending Newborns
$pending_newborns_html = '';
$nb_query = mysqli_query($conn, "SELECT * FROM children WHERE status='Pending' ORDER BY created_at DESC");
if (mysqli_num_rows($nb_query) > 0) {
    while($row = mysqli_fetch_assoc($nb_query)) {
        $json_row = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        $pending_newborns_html .= '<tr>';
        $pending_newborns_html .= '<td>' . htmlspecialchars($row['child_name']) . '</td>';
        $pending_newborns_html .= '<td>' . htmlspecialchars($row['mother_name']) . '</td>';
        $pending_newborns_html .= '<td>';
        $pending_newborns_html .= '<button type="button" class="btn-approve" onclick=\'openNewbornModal(' . $json_row . ')\'>REVIEW</button> ';
        $pending_newborns_html .= '<a href="process_verification.php?remove_id=' . $row['id'] . '&redirect=super_admin_dashboard.php" class="btn-reject" onclick="return confirm(\'Reject this?\')">REJECT</a>';
        $pending_newborns_html .= '</td>';
        $pending_newborns_html .= '</tr>';
    }
} else {
    $pending_newborns_html = '<tr><td colspan="3" align="center">No pending newborn records.</td></tr>';
}

// 3. Pending Maternal
$pending_maternal_html = '';
$pm_query = mysqli_query($conn, "SELECT *, 
    CONCAT(COALESCE(street,''), ' ', COALESCE(barangay,''), ' ', COALESCE(municipality,'')) AS computed_address,
    CONCAT(COALESCE(spouse_fname,''), ' ', COALESCE(spouse_lname,'')) AS computed_spouse 
    FROM maternal_registration WHERE status='Pending' ORDER BY created_at DESC");
if (mysqli_num_rows($pm_query) > 0) {
    while($row = mysqli_fetch_assoc($pm_query)) {
        $row['display_name'] = trim($row['client_fname'] . " " . $row['client_lname']);
        $json_row = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        $pending_maternal_html .= '<tr>';
        $pending_maternal_html .= '<td>' . htmlspecialchars($row['display_name']) . '</td>';
        $pending_maternal_html .= '<td>';
        $pending_maternal_html .= '<button type="button" class="btn-approve" onclick=\'openVerifyModal(' . $json_row . ')\'>VERIFY & ENROLL</button> ';
        $pending_maternal_html .= '<a href="process_verification.php?remove_preg_id=' . $row['id'] . '&redirect=super_admin_dashboard.php" class="btn-reject" onclick="return confirm(\'Reject this registration?\')">REJECT</a>';
        $pending_maternal_html .= '</td>';
        $pending_maternal_html .= '</tr>';
    }
} else {
    $pending_maternal_html = '<tr><td colspan="2" align="center">No pending maternal registration.</td></tr>';
}

// 4. Upcoming Schedules (Maternal & Infant)
// Check tables exists helper
if (!function_exists('check_table_exists')) {
    function check_table_exists($conn, $table_name) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
        return $result && mysqli_num_rows($result) > 0;
    }
}

$upcoming_maternal_html = '';
$upcoming_infant_html = '';

if (check_table_exists($conn, 'schedules')) {
    // Maternal Schedules HTML
    $mat_upcoming_q = mysqli_query($conn, "
        SELECT id, schedule_date, schedule_time, service_type, status, patient_name AS full_name 
        FROM schedules 
        WHERE category = 'Maternal' AND LOWER(status) != 'completed' 
        ORDER BY schedule_date ASC, schedule_time ASC LIMIT 5
    ");
    if ($mat_upcoming_q && mysqli_num_rows($mat_upcoming_q) > 0) {
        while ($sched = mysqli_fetch_assoc($mat_upcoming_q)) {
            $patientName = $sched['full_name'] ?? 'N/A';
            $serviceType = $sched['service_type'] ?? 'N/A';
            $schedDate = $sched['schedule_date'] ?? date('Y-m-d');
            $schedId = $sched['id'] ?? '';

            $upcoming_maternal_html .= '
            <div class="schedule-item maternal-border">
                <div class="sched-header-row">
                    <span class="sched-date">' . htmlspecialchars($schedDate) . '</span>
                    <span class="status-badge">Pending</span>
                </div>
                <div class="sched-patient-name">' . htmlspecialchars($patientName) . '</div>
                <div class="sched-type">Type: ' . htmlspecialchars($serviceType) . '</div>
                
                <form method="POST" action="process_verification.php" class="sched-actions">
                    <input type="hidden" name="schedule_id" value="' . $schedId . '">
                    <input type="hidden" name="redirect_to" value="super_admin_dashboard.php">
                    <button type="submit" name="mark_done_maternal" class="btn-mark-done">Mark Done</button>
                    <div class="resched-group">
                        <input type="date" name="new_date" class="date-input" required>
                        <button type="submit" name="reschedule_maternal" class="btn-resched">Resched</button>
                    </div>
                </form>
            </div>';
        }
    } else {
        $upcoming_maternal_html = '<p style="text-align: center; color: #777; font-size: 0.85rem; margin: 10px 0;">No upcoming maternal check-ups.</p>';
    }

    // Child Schedules HTML
    $inf_upcoming_q = mysqli_query($conn, "
        SELECT id, patient_name as child_name, schedule_date, schedule_time, service_type as vaccine_type, status
        FROM schedules 
        WHERE category = 'Child' AND LOWER(status) != 'completed' 
        ORDER BY schedule_date ASC, schedule_time ASC LIMIT 5
    ");
    if ($inf_upcoming_q && mysqli_num_rows($inf_upcoming_q) > 0) {
        while ($sched = mysqli_fetch_assoc($inf_upcoming_q)) {
            $infantName = $sched['child_name'] ?? 'N/A';
            $vaccineType = $sched['vaccine_type'] ?? 'N/A';
            $schedDateFull = ($sched['schedule_date'] ?? date('Y-m-d')) . (!empty($sched['schedule_time']) ? ' (' . $sched['schedule_time'] . ')' : '');
            $schedId = $sched['id'] ?? '';

            $upcoming_infant_html .= '
            <div class="schedule-item infant-border">
                <div class="sched-header-row">
                    <span class="sched-date">' . htmlspecialchars($schedDateFull) . '</span>
                </div>
                <div class="sched-patient-name">' . htmlspecialchars($infantName) . '</div>
                <div class="sched-type">Vaccine: ' . htmlspecialchars($vaccineType) . '</div>
                
                <form method="POST" action="process_verification.php" class="sched-actions">
                    <input type="hidden" name="schedule_id" value="' . $schedId . '">
                    <input type="hidden" name="redirect_to" value="super_admin_dashboard.php">
                    <button type="submit" name="mark_done_infant" class="btn-mark-done">Mark Done</button>
                    <div class="resched-group">
                        <input type="date" name="new_date" class="date-input" required>
                        <button type="submit" name="reschedule_infant" class="btn-resched">Resched</button>
                    </div>
                </form>
            </div>';
        }
    } else {
        $upcoming_infant_html = '<p style="text-align: center; color: #777; font-size: 0.85rem; margin: 10px 0;">No upcoming infant schedules.</p>';
    }
}

// Return JSON output
echo json_encode([
    'total_patients' => $total_patients,
    'total_newborns' => $total_newborns,
    'total_pregnant' => $total_pregnant,
    'total_pending' => $total_pending,
    'total_workers' => $total_workers,
    
    // Maternal Donut Info
    'mat_success_deg' => $mat_success_deg,
    'mat_success_pct' => $mat_success_pct,
    'mat_approved' => $mat_approved,
    'mat_not_success' => $mat_not_success,
    'mat_unsuccess_pct' => $mat_unsuccess_pct,
    'mat_total_reg' => $mat_total_reg,

    // Child Donut Info
    'child_success_deg' => $child_success_deg,
    'child_success_pct' => $child_success_pct,
    'child_approved' => $child_approved,
    'child_not_success' => $child_not_success,
    'child_unsuccess_pct' => $child_unsuccess_pct,
    'child_total_reg' => $child_total_reg,

    // Tables / Lists HTML
    'pending_workers_html' => $pending_workers_html,
    'pending_newborns_html' => $pending_newborns_html,
    'pending_maternal_html' => $pending_maternal_html,
    'upcoming_maternal_html' => $upcoming_maternal_html,
    'upcoming_infant_html' => $upcoming_infant_html
]);