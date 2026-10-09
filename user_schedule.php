<?php
session_start();
include 'db_connect.php';

// Check kung naka-login ang user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle Reschedule Request Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_reschedule'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    $new_time = mysqli_real_escape_string($conn, $_POST['new_time']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $requester_marker = '[Requester ID: ' . (int) $user_id . ']';

    $update_query = "UPDATE schedules 
                     SET status = 'Reschedule Requested', 
                         notes = CONCAT(COALESCE(notes, ''), ' | ', ?, ' Request: ', ?, ' ', ?, ' | Reason: ', ?)
                     WHERE id = ?";

    if ($stmt = $conn->prepare($update_query)) {
        $stmt->bind_param("ssssi", $requester_marker, $new_date, $new_time, $reason, $schedule_id);
        if ($stmt->execute()) {
            $schedule_info = $conn->prepare("SELECT patient_name, service_type FROM schedules WHERE id = ? LIMIT 1");
            $schedule_info->bind_param("i", $schedule_id);
            $schedule_info->execute();
            $schedule = $schedule_info->get_result()->fetch_assoc();
            $schedule_info->close();

            if ($schedule) {
                $title = "New Reschedule Request";
                $notification_message = "{$schedule['patient_name']} requested to reschedule {$schedule['service_type']} to "
                    . date('F j, Y', strtotime($new_date)) . " at " . date('g:i A', strtotime($new_time)) . ".";
                $notification = $conn->prepare("INSERT INTO notifications
                    (user_id, target_role, schedule_id, title, message, type, created_at)
                    VALUES (0, 'Super Admin', ?, ?, ?, 'reschedule_request', NOW())");
                $notification->bind_param("iss", $schedule_id, $title, $notification_message);
                $notification->execute();
                $notification->close();
            }

            $message = "<div class='alert success'><i class='fa fa-check-circle'></i> Tagumpay na naipadala ang iyong request para sa pagbabago ng iskedyul!</div>";
        } else {
            $message = "<div class='alert error'><i class='fa fa-triangle-exclamation'></i> Nabigo ang pag-request: " . $conn->error . "</div>";
        }
        $stmt->close();
    }
}

$today = date('Y-m-d');
$patient_names = [];

// 1. Kunin ang buong pangalan ng nanay (Maternal) na pagmamay-ari ng kasalukuyang naka-login na user
$stmt_m = $conn->prepare("SELECT CONCAT(client_fname, ' ', client_lname) AS full_name FROM maternal_registration WHERE user_id = ?"); 
$stmt_m->bind_param("i", $user_id);
$stmt_m->execute();
$res_m = $stmt_m->get_result();
while($row_m = $res_m->fetch_assoc()) {
    $patient_names[] = trim($row_m['full_name']);
}
$stmt_m->close();


// 2. Kunin ang pangalan ng mga anak (Child)
$stmt_c = $conn->prepare("SELECT child_name FROM children WHERE user_id = ?"); 
$stmt_c->bind_param("i", $user_id);
$stmt_c->execute();
$res_c = $stmt_c->get_result();
while($row_c = $res_c->fetch_assoc()) {
    $patient_names[] = $row_c['child_name'];
}
$stmt_c->close();


$upcoming_schedules = [];
$history_schedules = [];
$reschedule_history = [];

if (count($patient_names) > 0) {
    $placeholders = implode(',', array_fill(0, count($patient_names), '?'));
    $types = str_repeat('s', count($patient_names));
    
    $sched_query = "SELECT * FROM schedules WHERE patient_name IN ($placeholders) ORDER BY schedule_date ASC, schedule_time ASC";
    if ($stmt = $conn->prepare($sched_query)) {
        $bind_params = array_merge([$types], $patient_names);
        $tmp = [];
        foreach($bind_params as $key => $value) { $tmp[$key] = &$bind_params[$key]; }
        call_user_func_array([$stmt, 'bind_param'], $tmp);

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $status = strtolower($row['status'] ?? '');
            $notes = (string) ($row['notes'] ?? '');
            $has_reschedule_marker = preg_match('/Request:/i', $notes)
                || preg_match('/Rescheduled \(Orig:/i', $notes);
            $is_approved_reschedule = in_array($status, ['approved', 'rescheduled'], true)
                && $has_reschedule_marker;
            $row['display_status'] = $is_approved_reschedule
                ? 'Request Approved'
                : ($row['status'] ?? 'Pending');
            if ($status == 'completed' || $row['schedule_date'] < $today) {
                $history_schedules[] = $row;
            } else {
                $upcoming_schedules[] = $row;
            }

            if (preg_match('/Request:/i', $notes)
                || preg_match('/Rescheduled \(Orig:/i', $notes)
                || preg_match('/Reschedule Rejected/i', $notes)
                || in_array($status, ['reschedule requested', 'rescheduled'], true)) {
                preg_match_all('/Request:\s*(\d{4}-\d{2}-\d{2})(?:\s+(\d{2}:\d{2}(?::\d{2})?))?/i', $notes, $requested_matches, PREG_SET_ORDER);
                preg_match_all('/Rescheduled \(Orig:\s*(\d{4}-\d{2}-\d{2})\)/i', $notes, $original_matches, PREG_SET_ORDER);

                if ($status === 'reschedule requested') {
                    $row['reschedule_state'] = 'Reschedule Requested';
                } elseif (preg_match('/Reschedule Rejected/i', $notes)) {
                    $row['reschedule_state'] = 'Reschedule Rejected';
                } elseif ($is_approved_reschedule) {
                    $row['reschedule_state'] = 'Request Approved';
                } elseif ($status === 'rescheduled' || preg_match('/Rescheduled \(Orig:/i', $notes)) {
                    $row['reschedule_state'] = 'Rescheduled';
                } else {
                    $row['reschedule_state'] = 'Reschedule Approved';
                }

                $row['requested_dates'] = [];
                foreach ($requested_matches as $request_match) {
                    $requested_date = date('F j, Y', strtotime($request_match[1]));
                    if (!empty($request_match[2])) {
                        $requested_date .= ' at ' . date('g:i A', strtotime($request_match[2]));
                    }
                    $row['requested_dates'][] = $requested_date;
                }

                $row['original_dates'] = [];
                foreach ($original_matches as $original_match) {
                    $row['original_dates'][] = date('F j, Y', strtotime($original_match[1]));
                }

                $reschedule_history[] = $row;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments | Alawihao Health Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
        :root { 
            --green: #2d5016; 
            --sage: #718355; 
            --accent: #5a7c3a;
            --light: #8fbf5a;
            --bg: #f8fffb; 
            --white: #ffffff; 
            --text: #333333;
            --muted: #666666;
            --sidebar-width: 260px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg); font-family: 'Segoe UI', sans-serif; color: var(--text); }
        
        /* Sidebar: Nakabukas by default (transform: translateX(0)) */
        .sidebar-container {
            width: var(--sidebar-width) !important;
            min-width: var(--sidebar-width) !important;
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            z-index: 300;
            overflow-y: auto;
            transition: transform 0.3s ease;
            transform: translateX(0); 
        }
        /* Kapag sinara gamit ang hamburger button (nagdagdag ng .sidebar-closed sa body) */
        body.sidebar-closed .sidebar-container { transform: translateX(-100%); }

        /* Topbar: Naka-indent by default dahil nakabukas ang sidebar */
        .topbar {
            background: var(--white);
            border-bottom: 3px solid var(--green);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: sticky;
            top: 0; z-index: 200;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        /* Kapag naka-close ang sidebar, lumalawak ang topbar nang buo */
        body.sidebar-closed .topbar { margin-left: 0; width: 100%; }

        .topbar-brand { display: flex; align-items: center; gap: 15px; flex-shrink: 0; }
        
        #hamburgerBtn { display: none !important; }

        .topbar .hamburger-btn {
            background: none; border: none; cursor: pointer; color: var(--green);
            font-size: 20px; padding: 4px 8px; border-radius: 8px; 
            display: inline-flex; align-items: center; justify-content: center;
            position: static; z-index: auto; flex-shrink: 0;
        }
        body:not(.sidebar-closed) .topbar .hamburger-btn { display: none; }

        .topbar .logo-img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid var(--green); background: #eef2ee; }
        .topbar .page-label { font-size: 1rem; font-weight: 600; color: var(--green); }

        /* Main Content: Naka-indent din by default para hindi matakpan ng sidebar */
        #main { 
            margin-left: var(--sidebar-width); 
            width: calc(100% - var(--sidebar-width)); 
            transition: margin-left 0.3s ease, width 0.3s ease;
            padding: 30px 24px 60px;
            min-height: calc(100vh - 70px);
        }
        /* Kapag naka-close ang sidebar, sasakop sa buong screen ang main */
        body.sidebar-closed #main { margin-left: 0; width: 100%; }

        .content-container { width: 100%; margin: 0 auto; }
        .header-box { 
            background: var(--white); padding: 25px; border-radius: 15px; 
            border-left: 6px solid var(--green); margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }

        .alert { padding: 12px 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; text-align: center; transition: opacity 0.4s ease; }
        .alert.is-dismissing { opacity: 0; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eef2ee; padding-bottom: 10px; }
        .tab-btn { background: #e2e8f0; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; color: var(--muted); }
        .tab-btn.active { background: var(--green); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .sched-card { 
            background: var(--white); padding: 20px; border-radius: 15px; margin-bottom: 15px; 
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.04); border-left: 6px solid var(--sage);
            border: 1px solid #eef2ee; gap: 15px;
        }
        .sched-left { display: flex; align-items: center; gap: 20px; }
        .sched-right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
        .date-badge { background: #f1f4ea; padding: 10px; border-radius: 10px; text-align: center; min-width: 75px; border: 1px solid #e2e8f0; }

        .status-pill { padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .pending { background: #fffcf0; color: #b7791f; border: 1px solid #ecc94b; }
        .approved, .request-approved { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .completed { background: #f0f4e8; color: var(--green); border: 1px solid var(--light); }
        .reschedule-requested { background: #fef2f2; color: #b91c1c; border: 1px solid #f87171; }

        .btn-resched {
            background: #f1f5f9; color: var(--green); border: 1px solid var(--sage);
            padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 0.8rem; font-weight: 600;
        }
        .btn-resched:hover { background: var(--sage); color: white; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: var(--white); padding: 30px; border-radius: 15px; width: 100%; max-width: 450px; border-top: 6px solid var(--green); position: relative; }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 20px; cursor: pointer; color: var(--muted); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: var(--green); font-size: 0.9rem; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; }
        .btn-submit { background: var(--green); color: white; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .no-schedule { background: var(--white); padding: 40px; text-align: center; border-radius: 15px; color: var(--muted); border: 1px solid #eef2ee; }
    </style>
</head>
<body>

<?php include 'user_sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-brand">
        <button class="hamburger-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
        <img src="images/logo.jpg" alt="Alawihao.jpg" class="logo-img">
    </div>
    <span class="page-label">My Appointments</span>
</div>

<div id="main">
    <div class="content-container">
        <div class="header-box">
            <h2 style="margin:0; color: var(--green); font-size: 1.3rem;"><i class="fa fa-calendar-check"></i> HEALTH APPOINTMENTS</h2>
            <p style="margin: 5px 0 0; color: var(--muted); font-size: 0.95rem;">Subaybayan ang iyong mga naka-schedule na bakuna at check-up.</p>
        </div>

        <?php echo $message; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('upcoming', event)">Upcoming (<?= count($upcoming_schedules) ?>)</button>
            <button class="tab-btn" onclick="switchTab('history', event)">History (<?= count($history_schedules) + count($reschedule_history) ?>)</button>
        </div>

        <!-- UPCOMING TAB -->
        <div id="upcoming-tab" class="tab-content active">
            <?php if(count($upcoming_schedules) > 0): ?>
                <?php foreach($upcoming_schedules as $row): ?>
                    <div class="sched-card">
                        <div class="sched-left">
                            <div class="date-badge">
                                <span style="font-size: 0.75rem; color: #888; text-transform: uppercase; font-weight: 600;"><?= date('M', strtotime($row['schedule_date'])) ?></span><br>
                                <b style="font-size: 1.4rem; color: var(--green);"><?= date('d', strtotime($row['schedule_date'])) ?></b>
                            </div>
                            <div>
                                <h3 style="margin:0; color: #333; font-size: 1.1rem;"><?= htmlspecialchars($row['patient_name']) ?></h3>
                                <p style="margin:3px 0; color: var(--muted); font-size: 0.9rem;">
                                    <i class="fa fa-shield-halved" style="color: var(--sage);"></i> <?= htmlspecialchars($row['service_type']) ?>
                                </p>
                                <small style="color: #888;"><i class="fa fa-clock"></i> Oras: <?= !empty($row['schedule_time']) ? date('h:i A', strtotime($row['schedule_time'])) : 'Naka-schedule' ?></small>
                            </div>
                        </div>
                        
                        <div class="sched-right">
                            <?php 
                                $status_raw = strtolower(trim($row['status'] ?? 'pending'));
                                $display_status = strtoupper($row['display_status'] ?? $row['status'] ?? 'Pending');
                                $status_class = str_replace(' ', '-', $status_raw);
                            ?>
                            <div class="status-pill <?= $status_class ?>"><?= $display_status ?></div>
                            <button class="btn-resched" onclick="openReschedModal('<?= $row['id'] ?>', '<?= $row['schedule_date'] ?>', '<?= htmlspecialchars($row['patient_name']) ?>')">
                                <i class="fa fa-calendar-days"></i> Request Resched
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-schedule">
                    <p>Wala pang kasalukuyang appointment o schedule para sa inyo o sa inyong mga nakarehistrong anak.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- HISTORY TAB -->
        <div id="history-tab" class="tab-content">
            <?php if(count($history_schedules) > 0 || count($reschedule_history) > 0): ?>
                <?php foreach($history_schedules as $row): ?>
                    <div class="sched-card" style="border-left-color: #cbd5e1;">
                        <div class="sched-left">
                            <div class="date-badge" style="background: #f8fafc;">
                                <span style="font-size: 0.75rem; color: #888; text-transform: uppercase; font-weight: 600;"><?= date('M', strtotime($row['schedule_date'])) ?></span><br>
                                <b style="font-size: 1.4rem; color: #64748b;"><?= date('d', strtotime($row['schedule_date'])) ?></b>
                            </div>
                            <div>
                                <h3 style="margin:0; color: #333; font-size: 1.1rem;"><?= htmlspecialchars($row['patient_name']) ?></h3>
                                <p style="margin:3px 0; color: var(--muted); font-size: 0.9rem;"><?= htmlspecialchars($row['service_type']) ?></p>
                                <small style="color: #888;">Petsa: <?= date('F j, Y', strtotime($row['schedule_date'])) ?></small>
                            </div>
                        </div>
                        <div class="sched-right">
                            <?php 
                                $status_raw_h = strtolower(trim($row['status'] ?? 'completed'));
                                $status_class_h = str_replace(' ', '-', $status_raw_h);
                            ?>
                            <div class="status-pill <?= $status_class_h ?>"><?= strtoupper($row['status']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php foreach($reschedule_history as $row): ?>
                    <div class="sched-card" style="border-left-color: #d97706;">
                        <div class="sched-left">
                            <div class="date-badge" style="background: #fff7ed;">
                                <span style="font-size: 0.75rem; color: #888; text-transform: uppercase; font-weight: 600;">Appointment</span><br>
                                <b style="font-size: 1.4rem; color: #b45309;"><?= date('d', strtotime($row['schedule_date'])) ?></b>
                            </div>
                            <div>
                                <h3 style="margin:0; color: #333; font-size: 1.1rem;"><?= htmlspecialchars($row['patient_name']) ?></h3>
                                <p style="margin:3px 0; color: var(--muted); font-size: 0.9rem;"><?= htmlspecialchars($row['service_type']) ?></p>
                                <small style="color: #888;">Current date: <?= date('F j, Y', strtotime($row['schedule_date'])) ?></small>
                                <?php foreach ($row['original_dates'] as $original_date): ?>
                                    <small style="display:block; color: #888;">Previous date: <?= htmlspecialchars($original_date) ?></small>
                                <?php endforeach; ?>
                                <?php foreach ($row['requested_dates'] as $requested_date): ?>
                                    <small style="display:block; color: #888;">Requested date: <?= htmlspecialchars($requested_date) ?></small>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="sched-right">
                            <?php $reschedule_status_class = str_replace(' ', '-', strtolower($row['reschedule_state'])); ?>
                            <div class="status-pill <?= htmlspecialchars($reschedule_status_class) ?>"><?= htmlspecialchars(strtoupper($row['reschedule_state'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-schedule">
                    <p>Wala pang nakatalang history ng appointment o reschedule.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- RESCHEDULE MODAL -->
<div id="reschedModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeReschedModal()">&times;</span>
        <h3 style="color: var(--green); margin-bottom: 5px;"><i class="fa fa-calendar-plus"></i> Request Reschedule</h3>
        <p id="modalChildInfo" style="color: var(--muted); font-size: 0.9rem; margin-bottom: 20px;"></p>
        
        <form method="POST">
            <input type="hidden" name="schedule_id" id="modalScheduleId">
            <div class="form-group">
                <label>Gusto mong ilipat sa anong Petsa?</label>
                <input type="date" name="new_date" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Oras (Opsyonal)</label>
                <input type="time" name="new_time">
            </div>
            <div class="form-group">
                <label>Dahilan ng pagpapalit ng iskedyul:</label>
                <textarea name="reason" rows="3" required></textarea>
            </div>
            <button type="submit" name="request_reschedule" class="btn-submit">Isumite ang Request</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.querySelector('.alert.success');
        if (!successAlert) return;

        window.setTimeout(function() {
            successAlert.classList.add('is-dismissing');
            window.setTimeout(function() { successAlert.remove(); }, 400);
        }, 3000);
    });

    function toggleSidebar() { document.body.classList.toggle('sidebar-closed'); }
    function switchTab(tabName, event) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById(tabName + '-tab').classList.add('active');
        event.currentTarget.classList.add('active');
    }
    function openReschedModal(id, currentDate, childName) {
        document.getElementById('modalScheduleId').value = id;
        document.getElementById('modalChildInfo').innerText = "Pasyente: " + childName + " (Kasalukuyang Petsa: " + currentDate + ")";
        document.getElementById('reschedModal').style.display = 'flex';
    }
    function closeReschedModal() { document.getElementById('reschedModal').style.display = 'none'; }
    window.onclick = function(event) {
        let modal = document.getElementById('reschedModal');
        if (event.target == modal) { modal.style.display = 'none'; }
    }
</script>

</body>
</html>