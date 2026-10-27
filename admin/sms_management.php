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
            $message = "<div class='alert success'><i class='fa fa-check-circle'></i> Test SMS sent to <strong>$mobile</strong>.<br><small style='font-family:monospace;margin-top:4px;display:block;'>Gateway Response: " . htmlspecialchars($resp_raw) . "</small></div>";
        } else {
            $err_detail = is_array($result['error']) ? json_encode($result['error']) : strval($result['error']);
            $message = "<div class='alert error'><i class='fa fa-exclamation-triangle'></i> Gateway Error: " . htmlspecialchars($err_detail ?: $resp_raw) . "</div>";
        }
    }
    
    if (isset($_POST['send_all_reminders'])) {
        $result = sendScheduleReminders(3);
        $message = "<div class='alert success'><i class='fa fa-check-circle'></i> Reminders sent: <strong>{$result['sent']}</strong> | Failed/No Contact: <strong>{$result['failed']}</strong> (Total Processed: {$result['total_processed']})</div>";
    }
}

// ── Target date: 3 Days from Today
$target_date = date('Y-m-d', strtotime('+3 days'));
$target_date_formatted = date('F j, Y', strtotime('+3 days'));

// Get schedules for target date
$upcoming_query = "SELECT * FROM schedules 
                   WHERE DATE(schedule_date) = ? 
                   ORDER BY schedule_time ASC";
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

// Today's sent count from sms_logs
$today_str = date('Y-m-d');
$sent_today_res = $conn->query("SELECT COUNT(*) AS total FROM sms_logs WHERE DATE(sent_at) = '$today_str' AND status = 'sent'");
$sent_today = $sent_today_res ? $sent_today_res->fetch_assoc()['total'] : 0;

// Get recent SMS logs
$log_result = $conn->query("SELECT * FROM sms_logs ORDER BY sent_at DESC LIMIT 20");
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
        
        .sms-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px; }
        .sms-card { background: var(--white); padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid var(--border); margin-bottom: 25px; }
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
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $total_upcoming ?></div>
            <div class="stat-label">Appointments in 3 Days</div>
            <small style="color: var(--muted);"><?= $target_date_formatted ?></small>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $pending_reminders ?></div>
            <div class="stat-label">Pending SMS Reminders</div>
            <small style="color: var(--muted);">Ready to send</small>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $sent_today ?></div>
            <div class="stat-label">SMS Sent Today</div>
            <small style="color: var(--muted);"><?= date('F j, Y') ?></small>
        </div>
    </div>
    
    <!-- SMS Management Grid -->
    <div class="sms-grid">
        <!-- Test SMS Card -->
        <div class="sms-card">
            <h3><i class="fa fa-paper-plane"></i> Send Test SMS</h3>
            
            <form method="POST">
                <div class="form-group">
                    <label for="test_mobile">Mobile Number</label>
                    <input type="text" id="test_mobile" name="test_mobile" placeholder="" required>
                    <div class="mobile-format">Format: 09xxxxxxxxx (Globe, TM, Smart, TNT)</div>
                </div>
                
                <div class="form-group">
                    <label for="test_message">Message</label>
                    <textarea id="test_message" name="test_message" maxlength="150" required>Alawihao HC: Test message from Alawihao Health Center SMS system. System is operational.</textarea>
                    <div class="mobile-format">Keep under 150 characters to use exactly 1 SMS credit.</div>
                </div>
                
                <button type="submit" name="send_test_sms" class="btn btn-primary">
                    <i class="fa fa-paper-plane"></i> Send Test SMS
                </button>
            </form>
        </div>
        
        <!-- Batch 3-Days Reminder Card -->
        <div class="sms-card">
            <h3><i class="fa fa-bell"></i> Send 3-Day Reminders</h3>
            
            <div class="target-box">
                <div><i class="fa fa-calendar-day" style="color:var(--primary);margin-right:6px;"></i> <strong>Target Date:</strong> <?= $target_date_formatted ?></div>
                <div style="margin-top: 6px;"><i class="fa fa-users" style="color:var(--primary);margin-right:6px;"></i> <strong>Ready to notify:</strong> <?= $pending_reminders ?> patient(s)</div>
            </div>

            <!-- Recipient List Preview in UI -->
            <div style="margin: 15px 0;">
                <label style="font-weight:600;font-size:0.85rem;color:var(--primary);display:block;margin-bottom:8px;">
                    <i class="fa fa-address-book"></i> Scheduled Recipients for this Date:
                </label>
                <?php if (!empty($schedules_list)): ?>
                    <div style="max-height: 180px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; padding: 10px; background: var(--bg);">
                        <?php foreach ($schedules_list as $sched): ?>
                            <div style="padding: 8px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem;">
                                <div>
                                    <strong><?= htmlspecialchars($sched['patient_name']) ?></strong>
                                    <span style="color: var(--muted); font-size: 0.82rem;">(<?= htmlspecialchars($sched['service_type']) ?>)</span>
                                    <div style="font-size: 0.78rem; color: var(--muted);">
                                        Time: <?= date('g:i A', strtotime($sched['schedule_time'])) ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <?php if (!empty($sched['contact_number'])): ?>
                                        <div style="font-weight: 600; color: #27ae60; font-size: 0.82rem;"><i class="fa fa-phone"></i> <?= htmlspecialchars($sched['contact_number']) ?></div>
                                    <?php else: ?>
                                        <div style="font-weight: 600; color: #e74c3c; font-size: 0.82rem;"><i class="fa fa-circle-exclamation"></i> No Number</div>
                                    <?php endif; ?>
                                    <div style="margin-top: 3px;">
                                        <?php if (!empty($sched['sms_sent'])): ?>
                                            <span class="badge badge-success" style="font-size: 0.7rem;">Sent</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning" style="font-size: 0.7rem;">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 10px; border-radius: 6px; background: var(--bg); color: var(--muted); font-size: 0.85rem;">
                        No patients scheduled for <?= $target_date_formatted ?>.
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="POST" onsubmit="return confirm('Are you sure you want to send SMS reminders to <?= $pending_reminders ?> patient(s)?')">
                <button type="submit" name="send_all_reminders" class="btn btn-success" <?= $pending_reminders == 0 ? 'disabled' : '' ?> style="width: 100%; justify-content: center;">
                    <i class="fa fa-bullhorn"></i> Send 3-Day Reminders (<?= $pending_reminders ?>)
                </button>
            </form>
        </div>
    </div>
    
    <!-- Appointments 3 Days Ahead Table -->
    <div class="sms-card">
        <h3><i class="fa fa-calendar-check"></i> Appointments 3 Days Ahead (<?= $target_date_formatted ?>)</h3>
        
        <?php if (!empty($schedules_list)): ?>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Category</th>
                        <th>Service Type</th>
                        <th>Time</th>
                        <th>Contact Number</th>
                        <th>Status</th>
                        <th>SMS Notification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules_list as $sched): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sched['patient_name']) ?></strong></td>
                            <td><?= htmlspecialchars($sched['category']) ?></td>
                            <td><?= htmlspecialchars($sched['service_type']) ?></td>
                            <td><?= date('g:i A', strtotime($sched['schedule_time'])) ?></td>
                            <td>
                                <?php if (!empty($sched['contact_number'])): ?>
                                    <span><?= htmlspecialchars($sched['contact_number']) ?></span>
                                <?php else: ?>
                                    <span style="color: #e74c3c;"><i class="fa fa-times-circle"></i> No Contact</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-warning"><?= htmlspecialchars($sched['status'] ?: 'Pending') ?></span>
                            </td>
                            <td>
                                <?php if (!empty($sched['sms_sent'])): ?>
                                    <span class="badge badge-success"><i class="fa fa-check"></i> Sent</span>
                                    <br><small style="color: var(--muted);"><?= date('M j, g:i A', strtotime($sched['sms_sent_at'])) ?></small>
                                <?php else: ?>
                                    <?php if (!empty($sched['contact_number'])): ?>
                                        <span class="badge badge-warning"><i class="fa fa-clock"></i> Pending (3 Days)</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Cannot Send</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: var(--muted); padding: 30px;">No appointments scheduled for <?= $target_date_formatted ?>.</p>
        <?php endif; ?>
    </div>
    
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
                        <th>Gateway Response</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $log_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($log['mobile_number']) ?></strong></td>
                            <td style="max-width: 320px;"><?= htmlspecialchars($log['message']) ?></td>
                            <td><span class="badge badge-warning"><?= htmlspecialchars($log['sms_type']) ?></span></td>
                            <td>
                                <?php if ($log['status'] === 'sent'): ?>
                                    <span class="badge badge-success"><i class="fa fa-check"></i> Sent</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fa fa-exclamation-circle"></i> Failed</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 250px; font-family: monospace; font-size: 0.78rem; word-break: break-all;">
                                <?= htmlspecialchars($log['api_response']) ?>
                            </td>
                            <td><?= date('M j, Y g:i A', strtotime($log['sent_at'])) ?></td>
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