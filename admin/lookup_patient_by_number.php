<?php
/**
 * lookup_patient_by_number.php
 * AJAX endpoint: given a mobile number, find the patient's
 * upcoming appointment and return a pre-filled SMS message.
 */
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['found' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../send_schedule_sms.php';

$raw    = trim($_GET['mobile'] ?? '');
$mobile = formatMobileNumber($raw);

if (empty($mobile)) {
    echo json_encode(['found' => false]);
    exit;
}

// ── 1. Find patient name by mobile number ─────────────────────────
$patient_name = null;
$category     = null;

// Check maternal_registration
$stmt = $conn->prepare(
    "SELECT CONCAT(client_fname,' ',client_lname) AS name, 'Maternal' AS cat
     FROM maternal_registration
     WHERE REPLACE(REPLACE(REPLACE(contact,'-',''),' ',''),'+63','0') = ?
        OR contact = ?
     LIMIT 1"
);
// normalise the stored number for comparison
$local = '0' . substr($mobile, 3); // +639xx -> 09xx
if ($stmt) {
    $stmt->bind_param("ss", $local, $mobile);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) { $patient_name = $row['name']; $category = $row['cat']; }
}

// Check users table (linked to children)
if (!$patient_name) {
    $stmt = $conn->prepare(
        "SELECT CONCAT(first_name,' ',last_name) AS name FROM users
         WHERE REPLACE(REPLACE(REPLACE(contact_number,'-',''),' ',''),'+63','0') = ?
            OR contact_number = ?
         LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param("ss", $local, $mobile);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) { $patient_name = $row['name']; }
    }
}

if (!$patient_name) {
    echo json_encode(['found' => false]);
    exit;
}

// ── 2. Find upcoming appointment for that patient ─────────────────
$stmt = $conn->prepare(
    "SELECT patient_name, service_type, category, schedule_date, schedule_time, status
     FROM schedules
     WHERE LOWER(TRIM(patient_name)) = LOWER(TRIM(?))
       AND schedule_date >= CURDATE()
       AND LOWER(status) NOT IN ('cancelled','completed')
     ORDER BY schedule_date ASC, schedule_time ASC
     LIMIT 1"
);

if (!$stmt) {
    echo json_encode(['found' => false]);
    exit;
}

$stmt->bind_param("s", $patient_name);
$stmt->execute();
$sched = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sched) {
    // Patient found but no upcoming appointment
    echo json_encode([
        'found'        => true,
        'patient_name' => $patient_name,
        'message'      => "Alawihao HC: Test message from Alawihao Health Center SMS system. System is operational.",
    ]);
    exit;
}

// ── 3. Build pre-filled message ───────────────────────────────────
$name      = $sched['patient_name'];
$service   = $sched['service_type'] ?: 'Appointment';
$cat       = $sched['category']     ?: 'Appointment';
$date_fmt  = date('F j, Y', strtotime($sched['schedule_date']));
$time_fmt  = date('g:i A',  strtotime($sched['schedule_time']));
$status    = $sched['status'] ?: 'Pending';

$message = "Alawihao HC: Magandang araw, {$name}! Paalala na mayroon kang {$service} ({$cat}) appointment na naka-schedule sa {$date_fmt} ng {$time_fmt}. Status: {$status}. Mangyaring magdala ng iyong health records. - Alawihao Health Center";

echo json_encode([
    'found'        => true,
    'patient_name' => $name,
    'service'      => $service,
    'category'     => $cat,
    'date'         => $date_fmt,
    'time'         => $time_fmt,
    'status'       => $status,
    'message'      => $message,
]);