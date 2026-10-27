<?php
/**
 * FMCSMS Schedule Notification System
 * Barangay Alawihao Health Center
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Support being included from root directory or admin directory
if (file_exists(__DIR__ . '/db_connect.php')) {
    include_once __DIR__ . '/db_connect.php';
} elseif (file_exists(__DIR__ . '/../db_connect.php')) {
    include_once __DIR__ . '/../db_connect.php';
}

// ── FMCSMS API Configuration 
define('SMS_API_URL', 'https://www.fortmed.org/web/FMCSMS/api/messages.php');
define('SMS_API_KEY', 'fmcsms_348b603a022076be1d8fa824dbdd90d888ac261ceb70e33c');
define('SMS_SENDER_NAME', 'Alawihao HC');
define('SMS_FROM_NUMBER', '+639189876543'); // Optional / default sender number

// ── Auto-ensure Database Columns and Tables Exist 
function ensureSMSDatabaseTables($conn) {
    if (!$conn) return;
    
    // Check and add sms_sent column to schedules
    $check_col = $conn->query("SHOW COLUMNS FROM schedules LIKE 'sms_sent'");
    if ($check_col && $check_col->num_rows == 0) {
        $conn->query("ALTER TABLE schedules ADD COLUMN sms_sent TINYINT(1) DEFAULT 0");
    }
    
    // Check and add sms_sent_at column to schedules
    $check_col2 = $conn->query("SHOW COLUMNS FROM schedules LIKE 'sms_sent_at'");
    if ($check_col2 && $check_col2->num_rows == 0) {
        $conn->query("ALTER TABLE schedules ADD COLUMN sms_sent_at TIMESTAMP NULL");
    }
    
    // Create sms_logs table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS sms_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        schedule_id INT NULL,
        mobile_number VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        sms_type VARCHAR(50) DEFAULT 'reminder',
        status VARCHAR(20) DEFAULT 'pending',
        api_response TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Run table check once
if (isset($conn)) {
    ensureSMSDatabaseTables($conn);
}

//Format Philippine Mobile Number
function formatMobileNumber($mobile) {
    $cleaned = preg_replace('/[^0-9+]/', '', trim($mobile));
    if (substr($cleaned, 0, 2) === '09' && strlen($cleaned) === 11) {
        return '+63' . substr($cleaned, 1);
    }
    if (substr($cleaned, 0, 1) === '9' && strlen($cleaned) === 10) {
        return '+63' . $cleaned;
    }
    if (substr($cleaned, 0, 3) === '639' && strlen($cleaned) === 12) {
        return '+' . $cleaned;
    }
    return $cleaned;
}

// Find Contact Number for Patient Name
function getPatientContactNumber($conn, $patient_name, $category = null) {
    if (empty(trim($patient_name))) return null;
    $patient_name = trim($patient_name);

    // 1. Try maternal_registration
    if ($category === 'Maternal' || empty($category)) {
        $stmt = $conn->prepare("SELECT m.contact, u.contact_number 
                                FROM maternal_registration m 
                                LEFT JOIN users u ON m.user_id = u.id 
                                WHERE LOWER(TRIM(CONCAT(m.client_fname, ' ', m.client_lname))) = LOWER(?) 
                                   OR LOWER(TRIM(CONCAT(m.client_lname, ' ', m.client_fname))) = LOWER(?)
                                   OR LOWER(TRIM(m.client_fname)) = LOWER(?)
                                LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("sss", $patient_name, $patient_name, $patient_name);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res) {
                $num = !empty($res['contact']) ? $res['contact'] : $res['contact_number'];
                if (!empty(trim($num))) return trim($num);
            }
        }
    }

    // 2. Try children table
    if ($category === 'Child' || empty($category)) {
        $stmt = $conn->prepare("SELECT u.contact_number 
                                FROM children c 
                                JOIN users u ON c.user_id = u.id 
                                WHERE LOWER(TRIM(c.child_name)) = LOWER(?) 
                                LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $patient_name);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res && !empty(trim($res['contact_number']))) {
                return trim($res['contact_number']);
            }
        }
    }

    // 3. Fallback: check users table by full name
    $stmt = $conn->prepare("SELECT contact_number FROM users 
                            WHERE LOWER(TRIM(CONCAT(first_name, ' ', last_name))) = LOWER(?) LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $patient_name);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($res && !empty(trim($res['contact_number']))) {
            return trim($res['contact_number']);
        }
    }

    return null;
}

// ── Send SMS Function via FMCSMS API 
function sendSMS($mobile, $message, $schedule_id = null, $type = 'reminder') {
    global $conn;
    
    $formatted_mobile = formatMobileNumber($mobile);
    
    // Prepare JSON payload for FMCSMS API
    $payload = [
        'SenderName'  => SMS_SENDER_NAME,
        'ToNumber'    => $formatted_mobile,
        'MessageBody' => $message,
        'FromNumber'  => SMS_FROM_NUMBER
    ];

    $raw_response = false;
    $http_code = 0;
    $curl_error = '';

    if (function_exists('curl_init')) {
        $ch = curl_init(SMS_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-API-Key: ' . SMS_API_KEY,
            ],
            CURLOPT_POSTFIELDS    => json_encode($payload)
        ]);

        $raw_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
    } else {
        // Native PHP fallback: HTTP POST via stream context (no curl extension required)
        $json_payload = json_encode($payload);
        $options = [
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\n" .
                                   "X-API-Key: " . SMS_API_KEY . "\r\n" .
                                   "Content-Length: " . strlen($json_payload) . "\r\n",
                'content'       => $json_payload,
                'timeout'       => 15,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($options);
        $raw_response = @file_get_contents(SMS_API_URL, false, $context);

        if (isset($http_response_header) && is_array($http_response_header)) {
            if (preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $m)) {
                $http_code = intval($m[1]);
            }
        }
        if ($raw_response === false) {
            $last_err = error_get_last();
            $curl_error = $last_err['message'] ?? 'Failed to connect to SMS gateway';
        }
    }

    $is_success = false;
    $parsed_response = json_decode($raw_response, true);

    if ($http_code >= 200 && $http_code < 300) {
        if (is_array($parsed_response)) {
            if (
                (isset($parsed_response['success']) && ($parsed_response['success'] === false || $parsed_response['success'] === 'false')) ||
                (isset($parsed_response['status']) && in_array(strtolower($parsed_response['status']), ['error', 'failed', 'rejected'])) ||
                (!empty($parsed_response['error']))
            ) {
                $is_success = false;
            } else {
                $is_success = true;
            }
        } else {
            if ($raw_response && (stripos($raw_response, 'error') !== false || stripos($raw_response, 'failed') !== false)) {
                $is_success = false;
            } else {
                $is_success = !empty($raw_response);
            }
        }
    } else {
        $is_success = false;
    }

    $status_str = $is_success ? 'sent' : 'failed';
    $log_response = $raw_response ?: ($curl_error ?: "HTTP Code $http_code");

    // Save to sms_logs table
    if (isset($conn) && $conn) {
        $stmt = $conn->prepare("INSERT INTO sms_logs (schedule_id, mobile_number, message, sms_type, status, api_response) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssss", $schedule_id, $formatted_mobile, $message, $type, $status_str, $log_response);
            $stmt->execute();
            $stmt->close();
        }
    }

    return [
        'success'  => $is_success,
        'http_code' => $http_code,
        'response' => $parsed_response ?: $raw_response,
        'error'    => $is_success ? null : ($curl_error ?: $raw_response)
    ];
}

// ── Send Reminders for Appointments 3 Days Before
function sendScheduleReminders($days_before = 3) {
    global $conn;
    if (!$conn) return ['sent' => 0, 'failed' => 0, 'total_processed' => 0];

    ensureSMSDatabaseTables($conn);

    // Target date: exactly 3 days ahead (e.g. today + 3 days)
    $target_date = date('Y-m-d', strtotime("+$days_before days"));

    // Find all active schedules for target_date not yet notified
    $query = "SELECT * FROM schedules 
              WHERE DATE(schedule_date) = ? 
              AND (sms_sent != 1 OR sms_sent IS NULL)
              AND (status IS NULL OR LOWER(status) NOT IN ('cancelled', 'completed'))";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $target_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $sent_count = 0;
    $failed_count = 0;

    while ($row = $result->fetch_assoc()) {
        $sched_id = $row['id'];
        $patient_name = $row['patient_name'];
        $category = $row['category'];
        $service = $row['service_type'] ?: 'Appointment';
        
        $contact = getPatientContactNumber($conn, $patient_name, $category);
        
        if (empty($contact)) {
            $failed_count++;
            continue;
        }

        $date_fmt = date('M j, Y', strtotime($row['schedule_date']));
        $time_fmt = date('g:i A', strtotime($row['schedule_time']));

        // Kept short (under 150 characters to consume only 1 SMS credit)
        $message = "Alawihao HC: Hi {$patient_name}! Paalala sa appointment para sa {$service} sa {$date_fmt} {$time_fmt}. Dalhin ang health records. Salamat!";

        $res = sendSMS($contact, $message, $sched_id, 'reminder_3days');

        if ($res['success']) {
            $up = $conn->prepare("UPDATE schedules SET sms_sent = 1, sms_sent_at = NOW() WHERE id = ?");
            if ($up) {
                $up->bind_param("i", $sched_id);
                $up->execute();
                $up->close();
            }
            $sent_count++;
        } else {
            $failed_count++;
        }

        usleep(300000); // 0.3 second pause to avoid rate limiting
    }

    $stmt->close();

    return [
        'sent' => $sent_count,
        'failed' => $failed_count,
        'total_processed' => $sent_count + $failed_count
    ];
}

// ── API/AJAX Endpoint Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'send_reminders_3days':
            $result = sendScheduleReminders(3);
            echo json_encode([
                'success' => true,
                'message' => "Sent {$result['sent']} reminders (3 days before). Failed: {$result['failed']}.",
                'data' => $result
            ]);
            break;

        case 'test_sms':
            $mobile = $_POST['mobile'] ?? '';
            $message = $_POST['message'] ?? 'Test message from Alawihao Health Center SMS system.';
            if (!empty($mobile)) {
                $result = sendSMS($mobile, $message, null, 'test');
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => 'Mobile number is required.']);
            }
            break;
    }
    exit;
}
?>
