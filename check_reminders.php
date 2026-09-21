<?php
/**
 * check_reminders.php
 * Called on every user page load (via user_sidebar.php).
 * Inserts a reminder notification if a schedule is 1–3 days away
 * and a reminder hasn't been sent yet today for that schedule.
 *
 * Lightweight: runs one quick EXISTS check first; skips entirely
 * if there are no upcoming schedules for this user.
 */

if (!isset($conn) || !isset($_SESSION['user_id'])) return;

$uid  = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

// ── 1. Collect patient names for this user ─────────────────────────
$names = [];

$sm = $conn->prepare("SELECT CONCAT(client_fname,' ',client_lname) AS n FROM maternal_registration WHERE user_id=?");
$sm->bind_param("i", $uid); $sm->execute();
$sr = $sm->get_result();
while ($r = $sr->fetch_assoc()) $names[] = trim($r['n']);
$sm->close();

$sc = $conn->prepare("SELECT child_name FROM children WHERE user_id=?");
$sc->bind_param("i", $uid); $sc->execute();
$sr2 = $sc->get_result();
while ($r2 = $sr2->fetch_assoc()) $names[] = $r2['child_name'];
$sc->close();

if (empty($names)) return;

// ── 2. Find schedules 1–3 days away ───────────────────────────────
$ph   = implode(',', array_fill(0, count($names), '?'));
$tp   = str_repeat('s', count($names));
$d1   = date('Y-m-d', strtotime('+1 day'));
$d3   = date('Y-m-d', strtotime('+3 days'));

$sql  = "SELECT id, patient_name, service_type, schedule_date, schedule_time
         FROM schedules
         WHERE patient_name IN ($ph)
           AND schedule_date BETWEEN ? AND ?
           AND status IN ('Pending','Approved')";

$args = array_merge([$tp . 'ss'], $names, [$d1, $d3]);
$refs = [];
foreach ($args as $k => $v) $refs[$k] = &$args[$k];

$sq = $conn->prepare($sql);
call_user_func_array([$sq, 'bind_param'], $refs);
$sq->execute();
$upcoming = $sq->get_result()->fetch_all(MYSQLI_ASSOC);
$sq->close();

if (empty($upcoming)) return;

// ── 3. For each upcoming schedule, insert reminder if not yet done today ──
foreach ($upcoming as $sched) {
    $sid       = (int) $sched['id'];
    $date_fmt  = date('F j, Y', strtotime($sched['schedule_date']));
    $time_fmt  = date('g:i A',  strtotime($sched['schedule_time']));
    $days_left = (int) ceil((strtotime($sched['schedule_date']) - strtotime($today)) / 86400);
    $days_txt  = $days_left === 1 ? 'bukas' : "sa loob ng {$days_left} araw";

    // Check if a reminder was already inserted today for this schedule & user
    $chk = $conn->prepare(
        "SELECT id FROM notifications
         WHERE user_id=? AND schedule_id=? AND type='reminder'
           AND DATE(created_at)=CURDATE()
         LIMIT 1"
    );
    $chk->bind_param("ii", $uid, $sid);
    $chk->execute();
    $exists = $chk->get_result()->num_rows > 0;
    $chk->close();

    if ($exists) continue;

    // Insert reminder notification
    $days_txt2 = $days_left === 1 ? 'tomorrow' : "in {$days_left} days";
    $title = "Reminder: Appointment {$days_txt2}";
    $msg   = "You have a {$sched['service_type']} appointment for {$sched['patient_name']} on {$date_fmt} at {$time_fmt}. Please make sure to attend.";

    $ins = $conn->prepare(
        "INSERT INTO notifications (user_id, target_role, schedule_id, title, message, type)
         VALUES (?, 'User', ?, ?, ?, 'reminder')"
    );
    $ins->bind_param("iiss", $uid, $sid, $title, $msg);
    $ins->execute();
    $ins->close();
}
