<?php
session_start();
include '../db_connect.php';
require_once '../send_schedule_sms.php';

// Check admin access
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";

// Handle manual SMS sending
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_test_sms'])) {
        $mobile = trim($_POST['test_mobile']);
        $test_message = trim($_POST['test_message']);
        
        $result = sendSMS($mobile, $test_message, null, 'manual_test');
        $resp_raw = is_array($result['response']) ? json_encode($result['response']) : strval($result['response']);
        if ($result['success']) {
            $delivery_status = $result['status'] ?? 'pending';
            $status_message = $delivery_status === 'delivered'
                ? '✓ Delivered to recipient'
                : ($delivery_status === 'sent'
                    ? '✓ Sent via gateway'
                    : '✓ Accepted and queued by gateway');
            $msg_id = $result['message_id'] ?? '';
            $msg_id_str = $msg_id ? " <span style='color:#666;font-size:0.82rem;'>Message ID: {$msg_id}</span>" : '';
            $message = "<div class='alert success'><i class='fa fa-check-circle'></i> SMS sent to <strong>" . htmlspecialchars($mobile) . "</strong> — {$status_message}.{$msg_id_str}</div>";
        } else {
            // Extract clean error message from gateway response
            $err_parsed = is_array($result['response']) ? $result['response'] : json_decode($result['response'] ?? '', true);
            $err_msg = '';
            if (is_array($err_parsed)) {
                $err_msg = $err_parsed['error']['message'] ?? $err_parsed['message'] ?? $err_parsed['error'] ?? '';
            }
            if (empty($err_msg)) {
                $err_detail = is_array($result['error']) ? json_encode($result['error']) : strval($result['error']);
                $err_msg = $err_detail ?: 'Unknown error';
            }
            $message = "<div class='alert error'><i class='fa fa-exclamation-triangle'></i> Failed to send SMS to <strong>" . htmlspecialchars($mobile) . "</strong>: " . htmlspecialchars($err_msg) . "</div>";
        }
    }
    
    if (isset($_POST['send_all_reminders'])) {
        $result = sendScheduleReminders(3);
        $message = "<div class='alert success'><i class='fa fa-check-circle'></i> 3-Day Reminders accepted/queued: <strong>{$result['sent']}</strong> | Failed/No Contact: <strong>{$result['failed']}</strong> (Total: {$result['total_processed']})</div>";
    }

    if (isset($_POST['send_1day_reminders'])) {
        $result = sendScheduleReminders(1);
        $message = "<div class='alert success'><i class='fa fa-check-circle'></i> 1-Day Reminders accepted/queued: <strong>{$result['sent']}</strong> | Failed/No Contact: <strong>{$result['failed']}</strong> (Total: {$result['total_processed']})</div>";
    }
}

// ── Target date: 3 Days from Today
$target_date = date('Y-m-d', strtotime('+3 days'));
$target_date_formatted = date('F j, Y', strtotime('+3 days'));

// Get schedules for target date (3-day)
$upcoming_query = "SELECT s.*,
                          (SELECT sl.status
                           FROM sms_logs sl
                           WHERE sl.schedule_id = s.id
                             AND sl.sms_type = 'reminder_3days'
                           ORDER BY sl.id DESC
                           LIMIT 1) AS sms_delivery_status
                   FROM schedules s
                   WHERE DATE(s.schedule_date) = ?
                     AND LOWER(s.status) NOT IN ('cancelled','completed')
                   ORDER BY s.schedule_time ASC";
$upcoming_stmt = $conn->prepare($upcoming_query);
$upcoming_stmt->bind_param("s", $target_date);
$upcoming_stmt->execute();
$upcoming_schedules = $upcoming_stmt->get_result();

// Count stats
$total_upcoming = $upcoming_schedules->num_rows;
$pending_reminders = 0;

$schedules_list = [];
while ($row = $upcoming_schedules->fetch_assoc()) {
    $row['contact_number'] = getPatientContactNumber($conn, $row['patient_name'], $row['category']);
    if (empty($row['sms_sent']) && !empty($row['contact_number'])) {
        $pending_reminders++;
    }
    $schedules_list[] = $row;
}

// ── Target date: 1 Day from Today
$target_date_1day = date('Y-m-d', strtotime('+1 day'));
$target_date_1day_formatted = date('F j, Y', strtotime('+1 day'));

// Ensure sms_sent_1day column exists
$chk1day = $conn->query("SHOW COLUMNS FROM schedules LIKE 'sms_sent_1day'");
if ($chk1day && $chk1day->num_rows == 0) {
    $conn->query("ALTER TABLE schedules ADD COLUMN sms_sent_1day TINYINT(1) DEFAULT 0");
}

$upcoming_1day_query = "SELECT s.*,
                          (SELECT sl.status
                           FROM sms_logs sl
                           WHERE sl.schedule_id = s.id
                             AND sl.sms_type = 'reminder_1day'
                           ORDER BY sl.id DESC
                           LIMIT 1) AS sms_delivery_status
                   FROM schedules s
                   WHERE DATE(s.schedule_date) = ?
                     AND (LOWER(s.status) NOT IN ('cancelled','completed'))
                   ORDER BY s.schedule_time ASC";
$stmt_1day = $conn->prepare($upcoming_1day_query);
$stmt_1day->bind_param("s", $target_date_1day);
$stmt_1day->execute();
$upcoming_1day = $stmt_1day->get_result();

$total_1day = $upcoming_1day->num_rows;
$pending_1day = 0;
$schedules_1day_list = [];
while ($row = $upcoming_1day->fetch_assoc()) {
    $row['contact_number'] = getPatientContactNumber($conn, $row['patient_name'], $row['category']);
    if (empty($row['sms_sent_1day']) && !empty($row['contact_number'])) {
        $pending_1day++;
    }
    $schedules_1day_list[] = $row;
}

// Count all SMS attempts today (queued/sent/delivered = accepted by gateway)
$today_str = date('Y-m-d');
$sent_today_res = $conn->query("SELECT COUNT(*) AS total FROM sms_logs WHERE DATE(sent_at) = '$today_str' AND status IN ('sent', 'delivered', 'pending')");
$sent_today = $sent_today_res ? $sent_today_res->fetch_assoc()['total'] : 0;

// ── Patient Contacts List (for admin reference)
$contacts_list = [];

// Maternal patients
$mat_contacts = $conn->query("
    SELECT CONCAT(m.client_fname,' ',m.client_lname) AS patient_name,
           'Maternal' AS category,
           COALESCE(NULLIF(TRIM(m.contact),''), NULLIF(TRIM(u.contact_number),'')) AS contact_number
    FROM maternal_registration m
    LEFT JOIN users u ON m.user_id = u.id
    HAVING contact_number IS NOT NULL AND contact_number != ''
    ORDER BY m.client_lname ASC
");
if ($mat_contacts) {
    while ($r = $mat_contacts->fetch_assoc()) {
        // Get next appointment separately
        $pname = $conn->real_escape_string($r['patient_name']);
        $nxt = $conn->query("SELECT service_type, schedule_date FROM schedules WHERE LOWER(TRIM(patient_name)) = LOWER(TRIM('$pname')) AND schedule_date >= CURDATE() AND LOWER(status) NOT IN ('cancelled','completed') ORDER BY schedule_date ASC LIMIT 1");
        if ($nxt && $nxt->num_rows > 0) {
            $nd = $nxt->fetch_assoc();
            $r['next_service'] = $nd['service_type'];
            $r['next_date']    = $nd['schedule_date'];
        } else {
            $r['next_service'] = '';
            $r['next_date']    = '';
        }
        $contacts_list[] = $r;
    }
}

// Child patients
$child_contacts = $conn->query("
    SELECT c.child_name AS patient_name,
           'Child' AS category,
           NULLIF(TRIM(u.contact_number),'') AS contact_number
    FROM children c
    JOIN users u ON c.user_id = u.id
    WHERE u.contact_number IS NOT NULL AND TRIM(u.contact_number) != ''
    ORDER BY c.child_name ASC
");
if ($child_contacts) {
    while ($r = $child_contacts->fetch_assoc()) {
        $pname = $conn->real_escape_string($r['patient_name']);
        $nxt = $conn->query("SELECT service_type, schedule_date FROM schedules WHERE LOWER(TRIM(patient_name)) = LOWER(TRIM('$pname')) AND schedule_date >= CURDATE() AND LOWER(status) NOT IN ('cancelled','completed') ORDER BY schedule_date ASC LIMIT 1");
        if ($nxt && $nxt->num_rows > 0) {
            $nd = $nxt->fetch_assoc();
            $r['next_service'] = $nd['service_type'];
            $r['next_date']    = $nd['schedule_date'];
        } else {
            $r['next_service'] = '';
            $r['next_date']    = '';
        }
        $contacts_list[] = $r;
    }
}

// Get recent SMS logs
$log_result = $conn->query("SELECT * FROM sms_logs ORDER BY sent_at DESC LIMIT 20");

function getSmsStatusPresentation($status) {
    $status = strtolower($status ?? 'pending');
    if ($status === 'delivered') {
        return ['label' => 'Delivered', 'class' => 'badge-success', 'icon' => 'fa-check'];
    }
    if ($status === 'sent') {
        return ['label' => 'Sent by Gateway', 'class' => 'badge-success', 'icon' => 'fa-check'];
    }
    if ($status === 'failed' || $status === 'rejected' || $status === 'error') {
        return ['label' => 'Failed', 'class' => 'badge-danger', 'icon' => 'fa-exclamation-circle'];
    }
    return ['label' => 'Queued', 'class' => 'badge-warning', 'icon' => 'fa-clock'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Management | Alawihao Health Center</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $notif_path='../'; include '../notif_assets.php'; ?>
    
    <style>
        :root {
            --primary: #5A6B47;
            --accent: #6B8E55;
            --light: #8DAE74;
            --bg: #f8fffb;
            --white: #ffffff;
            --text: #333333;
            --muted: #666666;
            --border: #edf2ed;
        }
        
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); margin: 0; padding: 0; color: var(--text); }
        #main { padding: 20px 30px; box-sizing: border-box; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header { 
            background: var(--white); 
            padding: 25px 30px; 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.06); 
            margin-bottom: 25px; 
            border-top: 6px solid var(--primary); 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { margin: 0 0 5px 0; font-size: 1.6rem; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        .header p { margin: 0; color: var(--muted); font-size: 0.95rem; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        .stat-number { font-size: 2.2rem; font-weight: 700; color: var(--primary); }
        .stat-label { color: var(--muted); font-size: 0.9rem; margin-top: 5px; font-weight: 600; }
        
        .sms-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .sms-card { background: var(--white); padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid var(--border); margin-bottom: 20px; }
        .sms-card h3 { margin-top: 0; color: var(--primary); display: flex; align-items: center; gap: 10px; font-size: 1.2rem; margin-bottom: 15px; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary); font-size: 0.9rem; }
        .form-group input, .form-group textarea { width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid #dcdcdc; border-radius: 8px; font-size: 0.95rem; font-family: inherit; }
        .form-group textarea { height: 90px; resize: vertical; }
        .mobile-format { font-size: 0.8rem; color: var(--muted); margin-top: 5px; }
        
        .btn { padding: 12px 22px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--accent); }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #2ecc71; }
        .btn:disabled { background: #bdc3c7; cursor: not-allowed; }
        
        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .schedule-table th, .schedule-table td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .schedule-table th { background: #f9fafb; color: var(--primary); font-weight: 600; }
        .schedule-table tr:hover { background: #fcfdfa; }
        
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .api-badge { background: #e8f5e9; color: #2e7d32; padding: 6px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .target-box { background: #f4f8f3; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #e1ede0; }

        /* ── DARK MODE OVERRIDES (Alawihao Theme) ── */
        [data-theme="dark"] body {
            background-color: #1A1D1B !important;
            color: #E8EDE5 !important;
        }
        [data-theme="dark"] #main {
            background-color: #1A1D1B !important;
        }
        [data-theme="dark"] .header,
        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .sms-card {
            background-color: #242826 !important;
            border-color: #3A403A !important;
            color: #E8EDE5 !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.35) !important;
        }
        [data-theme="dark"] .header h1,
        [data-theme="dark"] .sms-card h3,
        [data-theme="dark"] .stat-number {
            color: #8DAE74 !important;
        }
        [data-theme="dark"] .header p,
        [data-theme="dark"] .stat-label,
        [data-theme="dark"] .mobile-format,
        [data-theme="dark"] .sms-card p {
            color: #A0AEC0 !important;
        }
        [data-theme="dark"] .form-group label {
            color: #E8EDE5 !important;
        }
        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group textarea {
            background-color: #2C302C !important;
            border-color: #3A403A !important;
            color: #FFFFFF !important;
        }
        [data-theme="dark"] .form-group input::placeholder,
        [data-theme="dark"] .form-group textarea::placeholder {
            color: #718096 !important;
        }
        [data-theme="dark"] .target-box {
            background-color: #2C302C !important;
            border-color: #3A403A !important;
            color: #E8EDE5 !important;
        }
        [data-theme="dark"] .schedule-table th {
            background-color: #2C302C !important;
            color: #8DAE74 !important;
            border-color: #3A403A !important;
        }
        [data-theme="dark"] .schedule-table td {
            border-color: #3A403A !important;
            color: #E8EDE5 !important;
        }
        [data-theme="dark"] .schedule-table tr:hover {
            background-color: #2E3530 !important;
        }
        [data-theme="dark"] .api-badge {
            background-color: #1c3523 !important;
            color: #81c784 !important;
            border: 1px solid #2d5a38;
        }

        @media (max-width: 768px) {
            .sms-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; align-items: flex-start; gap: 15px; }
        }
    </style>
</head>
<body>

<?php 
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin') {
        include 'super_admin_sidebar.php';
    } else {
        include 'admin_sidebar.php'; 
    }
?>

<div id="main">
<div class="container">
    <div class="header">
        <div>
            <h1><i class="fa fa-comment-sms"></i> SMS Management System</h1>
        </div>
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="schedule_management.php" class="btn btn-primary" style="padding: 8px 14px; font-size: 0.85rem;">
                <i class="fa fa-calendar-alt"></i> Schedule List
            </a>
            <?php include '../notif_bell.php'; ?>
        </div>
    </div>
    
    <?= $message ?>
    
    <!-- SMS Management Grid -->
    <div class="sms-grid">
        <!-- Test SMS Card -->
        <div class="sms-card" style="grid-column: 1 / -1;">
            <h3><i class="fa fa-paper-plane"></i> Send Test SMS</h3>
            
            <form method="POST">
                <div class="form-group">
                    <label for="test_mobile">Mobile Number</label>
                    <input type="text" id="test_mobile" name="test_mobile" placeholder="09xxxxxxxxx" required autocomplete="off">
                    <div class="mobile-format">Format: 09xxxxxxxxx (Globe, TM, Smart, TNT)</div>
                    <div id="lookup_status" style="font-size:0.82rem;margin-top:5px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="test_message">Message</label>
                    <textarea id="test_message" name="test_message" required>Alawihao HC: Test message from Alawihao Health Center SMS system. System is operational.</textarea>
                    <div class="mobile-format" id="char_count">Characters: 0</div>
                </div>

                <script>
                window.addEventListener('load', function(){
                    const mobileInput = document.getElementById('test_mobile');
                    const msgArea     = document.getElementById('test_message');
                    const statusDiv   = document.getElementById('lookup_status');
                    const charCount   = document.getElementById('char_count');
                    let lookupTimer   = null;

                    function updateCount() {
                        charCount.textContent = 'Characters: ' + msgArea.value.length;
                    }
                    msgArea.addEventListener('input', updateCount);
                    updateCount();

                    mobileInput.addEventListener('input', function(){
                        clearTimeout(lookupTimer);
                        const num = this.value.trim();
                        if (num.length < 10) {
                            statusDiv.innerHTML = '';
                            return;
                        }
                        statusDiv.innerHTML = '<span style="color:#888;"><i class="fa fa-spinner fa-spin"></i> Looking up patient...</span>';
                        lookupTimer = setTimeout(function(){
                            const url = window.location.pathname.replace('sms_management.php','') + 'lookup_patient_by_number.php?mobile=' + encodeURIComponent(num);
                            fetch(url)
                                .then(function(r){ return r.json(); })
                                .then(function(data){
                                    if (data.found) {
                                        statusDiv.innerHTML = '<span style="color:#27ae60;"><i class="fa fa-check-circle"></i> Patient found: <strong>' + data.patient_name + '</strong></span>';
                                        msgArea.value = data.message;
                                        updateCount();
                                    } else {
                                        statusDiv.innerHTML = '<span style="color:#e67e22;"><i class="fa fa-exclamation-circle"></i> No appointment found for this number.</span>';
                                        msgArea.value = 'Alawihao HC: Test message from Alawihao Health Center SMS system. System is operational.';
                                        updateCount();
                                    }
                                })
                                .catch(function(err){
                                    statusDiv.innerHTML = '<span style="color:#e74c3c;"><i class="fa fa-times-circle"></i> Lookup failed: ' + err.message + '</span>';
                                });
                        }, 600);
                    });
                });
                </script>
                
                <button type="submit" name="send_test_sms" class="btn btn-primary">
                    <i class="fa fa-paper-plane"></i> Send Test SMS
                </button>
            </form>
        </div>
    </div><!-- end sms-grid -->

    <!-- Patient Contacts List -->
    <div class="sms-card">
        <h3><i class="fa fa-address-book"></i> Patient Contact Numbers</h3>
        <p style="color:var(--muted);font-size:0.85rem;margin-bottom:15px;">
            I-click ang number para ma-auto-fill sa Send Test SMS field.
        </p>
        <?php if (!empty($contacts_list)): ?>
        <div style="overflow-x:auto;">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Category</th>
                        <th>Contact Number</th>
                        <th>Next Appointment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts_list as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['patient_name']) ?></strong></td>
                            <td>
                                <span class="badge <?= $c['category'] === 'Maternal' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $c['category'] ?>
                                </span>
                            </td>
                            <td style="font-weight:600;"><?= htmlspecialchars($c['contact_number']) ?></td>
                            <td style="font-size:0.85rem;color:var(--muted);">
                                <?php if (!empty($c['next_service']) && !empty($c['next_date'])): ?>
                                    <?= htmlspecialchars($c['next_service']) ?><br>
                                    <small><?= date('M j, Y', strtotime($c['next_date'])) ?></small>
                                <?php else: ?>
                                    <span style="color:#bbb;">No upcoming</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="fillSmsForm('<?= htmlspecialchars($c['contact_number']) ?>','<?= htmlspecialchars($c['patient_name']) ?>')"
                                    class="btn btn-primary" style="padding:6px 12px;font-size:0.82rem;">
                                    <i class="fa fa-paper-plane"></i> Send SMS
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p style="color:var(--muted);text-align:center;padding:20px;">No patients with contact numbers found.</p>
        <?php endif; ?>
    </div>

    <script>
    function fillSmsForm(number, name) {
        document.getElementById('test_mobile').value = number;
        // Trigger input event so lookup fires
        document.getElementById('test_mobile').dispatchEvent(new Event('input'));
        // Scroll to form
        document.getElementById('test_mobile').scrollIntoView({behavior:'smooth', block:'center'});
        document.getElementById('test_mobile').focus();
    }
    </script>

    <!-- Recent SMS Logs Table -->
    <div class="sms-card">
        <h3><i class="fa fa-history"></i> Recent SMS History (Logs)</h3>
        
        
        <?php if ($log_result && $log_result->num_rows > 0): ?>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Message</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Message ID</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $log_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($log['mobile_number']) ?></strong></td>
                            <td style="max-width: 280px; font-size:0.88rem;"><?= htmlspecialchars($log['message']) ?></td>
                            <td>
                                <?php
                                    $type_labels = [
                                        'reminder_3days'     => ['label'=>'3-Day Reminder',  'color'=>'#5A6B47'],
                                        'reminder_1day'      => ['label'=>'1-Day Reminder',  'color'=>'#6B8E55'],
                                        'reschedule_approved'=> ['label'=>'Resched Approved','color'=>'#27ae60'],
                                        'reschedule_rejected'=> ['label'=>'Resched Rejected','color'=>'#e74c3c'],
                                        'rescheduled'        => ['label'=>'Rescheduled',     'color'=>'#e67e22'],
                                        'manual_test'        => ['label'=>'Test SMS',         'color'=>'#3498db'],
                                        'test'               => ['label'=>'Test SMS',         'color'=>'#3498db'],
                                    ];
                                    $type_info = $type_labels[$log['sms_type']] ?? ['label'=> ucfirst(str_replace('_',' ',$log['sms_type'])), 'color'=>'#666'];
                                ?>
                                <span style="background:<?= $type_info['color'] ?>22;color:<?= $type_info['color'] ?>;padding:3px 8px;border-radius:10px;font-size:0.78rem;font-weight:600;">
                                    <?= $type_info['label'] ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                    $log_status = strtolower($log['status'] ?? 'pending');
                                    $gateway_response = json_decode($log['api_response'] ?? '', true);
                                    if (is_array($gateway_response)) {
                                        $reported_status = strtolower($gateway_response['status'] ?? '');
                                        if (in_array($reported_status, ['queued','sent','delivered','failed','not_delivered','cancelled'], true)) {
                                            $log_status = $reported_status;
                                        }
                                    }
                                    $sms_presentation = getSmsStatusPresentation($log_status);
                                ?>
                                <span class="badge <?= $sms_presentation['class'] ?>">
                                    <i class="fa <?= $sms_presentation['icon'] ?>"></i> <?= $sms_presentation['label'] ?>
                                </span>
                            </td>
                            <td style="font-size:0.8rem; color:var(--muted); font-family:monospace;">
                                <?php
                                    $msg_id = $log['gateway_message_id'] ?? '';
                                    if (empty($msg_id) && is_array($gateway_response)) {
                                        $msg_id = $gateway_response['id'] ?? '';
                                    }
                                    echo $msg_id ? htmlspecialchars(substr($msg_id, 0, 20)).'...' : '—';
                                ?>
                            </td>
                            <td style="font-size:0.85rem; white-space:nowrap;"><?= date('M j, Y g:i A', strtotime($log['sent_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: var(--muted); padding: 30px;">No SMS activity recorded yet.</p>
        <?php endif; ?>
    </div>
</div> <!-- .container -->
</div> <!-- #main -->

<?php include 'footer.php'; ?>
<?php $notif_path='../'; include '../notif_js.php'; ?>

</body>
</html>