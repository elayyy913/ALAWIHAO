<?php
/**
 * Telerivet SMS Notification System
 * Barangay Alawihao Health Center
 * Gateway: Android Phone via Telerivet REST API
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

// ── Telerivet API Configuration (Android Phone Gateway)
define('TELERIVET_API_KEY',    'ZAeug_gHXy2SdXarT8zHEdE9eTHpUXN8wNwg');
define('TELERIVET_PROJECT_ID', 'PJ174060f7f4b04698');
define('TELERIVET_PHONE_ID',   'PN1906610bde13a27b');
define('TELERIVET_API_URL',    'https://api.telerivet.com/v1/projects/PJ174060f7f4b04698/messages/send');

// ── Auto-ensure Database Columns and Tables Exist 
function normalizeGatewayStatus($status) {
    $status = strtolower(trim((string) ($status ?? '')));
    if ($status === '') return 'pending';
    if (in_array($status, ['delivered', 'received', 'successfully_delivered'], true)) return 'delivered';
    if (in_array($status, ['sent', 'accepted', 'queued', 'pending', 'in_queue', 'processing'], true)) return 'pending';
    if (in_array($status, ['failed', 'rejected', 'error', 'undelivered'], true)) return 'failed';
    return 'pending';
}

function extractGatewayMessageId($payload) {
    if (!is_array($payload)) return null;
    foreach (['message_id', 'msg_id', 'sms_id', 'id', 'messageId', 'smsId', 'request_id', 'requestId', 'delivery_id', 'deliveryId'] as $key) {
        if (isset($payload[$key]) && trim((string) $payload[$key]) !== '') {
            return trim((string) $payload[$key]);
        }
    }
    return null;
}

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
        gateway_message_id VARCHAR(120) NULL,
        api_response TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $check_msg_col = $conn->query("SHOW COLUMNS FROM sms_logs LIKE 'gateway_message_id'");
    if ($check_msg_col && $check_msg_col->num_rows == 0) {
        $conn->query("ALTER TABLE sms_logs ADD COLUMN gateway_message_id VARCHAR(120) NULL AFTER status");
    }
}

// Run table check once
if (isset($conn)) {
    ensureSMSDatabaseTables($conn);
}

//Format Philippine Mobile Number
function formatMobileNumber($mobile) {
    // Strip everything except digits and leading +
    $cleaned = preg_replace('/[^0-9+]/', '', trim($mobile));

    // Remove leading + for easier processing
    $digits = ltrim($cleaned, '+');

    // Remove country code prefixes so we're left with the 10-digit local number
    // +63xxxxxxxxxx or 63xxxxxxxxxx -> 9xxxxxxxxx
    if (substr($digits, 0, 2) === '63' && strlen($digits) === 12) {
        $digits = substr($digits, 2); // strip 63 -> 10 digits starting with 9
    }
    // 09xxxxxxxxx (11 digits) -> strip leading 0 -> 10 digits starting with 9
    if (substr($digits, 0, 1) === '0' && strlen($digits) === 11) {
        $digits = substr($digits, 1);
    }

    // At this point we expect exactly 10 digits starting with 9
    if (strlen($digits) === 10 && substr($digits, 0, 1) === '9') {
        return '+63' . $digits;
    }

    // Fallback: return as-is (let the gateway report the error)
    return $cleaned ?: $mobile;
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

// ── Send SMS Function via Telerivet Android Gateway
function sendSMS($mobile, $message, $schedule_id = null, $type = 'reminder') {
    global $conn;

    $formatted_mobile = formatMobileNumber($mobile);

    // Telerivet REST API payload
    $payload = [
        'to_number'    => $formatted_mobile,
        'content'      => $message,
        'route_id'     => TELERIVET_PHONE_ID,
        'message_type' => 'sms',
    ];

    $raw_response = false;
    $http_code    = 0;
    $curl_error   = '';

    if (function_exists('curl_init')) {
        $ch = curl_init(TELERIVET_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERPWD        => TELERIVET_API_KEY . ':',   // Basic Auth: apikey as username, empty password
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $raw_response = curl_exec($ch);
        $http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error   = curl_error($ch);
        curl_close($ch);
    } else {
        // stream_context fallback when cURL is unavailable
        $json_payload = json_encode($payload);
        $auth_header  = 'Authorization: Basic ' . base64_encode(TELERIVET_API_KEY . ':');
        $options = [
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\n" .
                                   $auth_header . "\r\n" .
                                   "Content-Length: " . strlen($json_payload) . "\r\n",
                'content'       => $json_payload,
                'timeout'       => 15,
                'ignore_errors' => true,
            ],
        ];
        $context      = stream_context_create($options);
        $raw_response = @file_get_contents(TELERIVET_API_URL, false, $context);

        if (isset($http_response_header) && is_array($http_response_header)) {
            if (preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $m)) {
                $http_code = intval($m[1]);
            }
        }
        if ($raw_response === false) {
            $last_err   = error_get_last();
            $curl_error = $last_err['message'] ?? 'Failed to connect to Telerivet gateway';
        }
    }

    $is_success          = false;
    $gw_status           = '';
    $gateway_message_id  = null;
    $parsed_response     = json_decode($raw_response, true);

    if ($http_code >= 200 && $http_code < 300 && is_array($parsed_response)) {
        // Telerivet returns message object with "id" on success
        // status can be: queued, sent, delivered, not_delivered, failed, cancelled
        $gateway_message_id = $parsed_response['id'] ?? null;
        $gw_status          = strtolower($parsed_response['status'] ?? '');
        $gw_error           = $parsed_response['error']['message'] ?? '';

        if (!empty($gw_error) || in_array($gw_status, ['failed', 'not_delivered', 'cancelled'])) {
            $is_success = false;
        } else {
            // queued/sent/delivered all mean the gateway accepted it
            $is_success = !empty($gateway_message_id);
        }
    } else {
        $is_success = false;
        // Extract API-level error message if present
        if (is_array($parsed_response) && isset($parsed_response['error']['message'])) {
            $curl_error = $parsed_response['error']['message'];
        }
    }

    $status_str = normalizeGatewayStatus($gw_status);
    if (!$is_success && $status_str === 'pending') {
        $status_str = 'failed';
    }
    $log_response = $raw_response ?: ($curl_error ?: "HTTP Code $http_code");

    // Save to sms_logs table
    if (isset($conn) && $conn) {
        $stmt = $conn->prepare("INSERT INTO sms_logs (schedule_id, mobile_number, message, sms_type, status, gateway_message_id, api_response) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issssss", $schedule_id, $formatted_mobile, $message, $type, $status_str, $gateway_message_id, $log_response);
            $stmt->execute();
            $stmt->close();
        }
    }

    return [
        'success'    => $is_success,
        'status'     => $status_str,
        'message_id' => $gateway_message_id,
        'http_code'  => $http_code,
        'response'   => $parsed_response ?: $raw_response,
        'error'      => $is_success ? null : ($curl_error ?: $raw_response),
    ];
}

// ── Send Reminders for Appointments (3 days or 1 day before)
function sendScheduleReminders($days_before = 3) {
    global $conn;
    if (!$conn) return ['sent' => 0, 'failed' => 0, 'total_processed' => 0];

    ensureSMSDatabaseTables($conn);

    $target_date = date('Y-m-d', strtotime("+$days_before days"));

    // Use separate sms_sent columns per reminder type
    $sms_col = ($days_before == 1) ? 'sms_sent_1day' : 'sms_sent';

    // Ensure 1-day column exists
    if ($days_before == 1) {
        $chk = $conn->query("SHOW COLUMNS FROM schedules LIKE 'sms_sent_1day'");
        if ($chk && $chk->num_rows == 0) {
            $conn->query("ALTER TABLE schedules ADD COLUMN sms_sent_1day TINYINT(1) DEFAULT 0");
        }
    }

    $query = "SELECT * FROM schedules 
              WHERE DATE(schedule_date) = ? 
              AND ($sms_col != 1 OR $sms_col IS NULL)
              AND (status IS NULL OR LOWER(status) NOT IN ('cancelled', 'completed'))";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $target_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $sent_count   = 0;
    $failed_count = 0;

    while ($row = $result->fetch_assoc()) {
        $sched_id     = $row['id'];
        $patient_name = $row['patient_name'];
        $category     = $row['category'] ?: 'Appointment';
        $service      = $row['service_type'] ?: 'Appointment';
        $status       = $row['status'] ?: 'Pending';

        $contact = getPatientContactNumber($conn, $patient_name, $category);
        if (empty($contact)) {
            $failed_count++;
            continue;
        }

        $date_fmt = date('F j, Y', strtotime($row['schedule_date']));
        $time_fmt = date('g:i A',  strtotime($row['schedule_time']));
        $type_str = ($days_before == 1) ? 'reminder_1day' : 'reminder_3days';

        if ($days_before == 1) {
            $message = "Alawihao HC: Reminder, {$patient_name}! Bukas na ang iyong {$service} ({$category}) appointment sa {$date_fmt} ng {$time_fmt}. Status: {$status}. Huwag kalimutang magdala ng health records. - Alawihao Health Center";
        } else {
            $message = "Alawihao HC: Magandang araw, {$patient_name}! Paalala na mayroon kang {$service} ({$category}) appointment na naka-schedule sa {$date_fmt} ng {$time_fmt}. Status: {$status}. Mangyaring magdala ng iyong health records. - Alawihao Health Center";
        }

        $res = sendSMS($contact, $message, $sched_id, $type_str);

        if ($res['success']) {
            $up = $conn->prepare("UPDATE schedules SET {$sms_col} = 1, sms_sent_at = NOW() WHERE id = ?");
            if ($up) {
                $up->bind_param("i", $sched_id);
                $up->execute();
                $up->close();
            }
            $sent_count++;
        } else {
            $failed_count++;
        }

        usleep(300000);
    }

    $stmt->close();

    return [
        'sent'            => $sent_count,
        'failed'          => $failed_count,
        'total_processed' => $sent_count + $failed_count
    ];
}

function updateSmsDeliveryStatus($conn, $gateway_message_id, $status, $raw_response = null) {
    if (!$conn || empty($gateway_message_id)) {
        return false;
    }

    $status = normalizeGatewayStatus($status);
    $raw_response = is_string($raw_response) ? $raw_response : json_encode($raw_response);

    $stmt = $conn->prepare("UPDATE sms_logs SET status = ?, api_response = COALESCE(?, api_response) WHERE gateway_message_id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("sss", $status, $raw_response, $gateway_message_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// ── API/AJAX Endpoint Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'send_reminders_3days':
            $result = sendScheduleReminders(3);
            echo json_encode([
                'success' => true,
                'message' => "Accepted/queued {$result['sent']} reminders (3 days before). Failed: {$result['failed']}.",
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
