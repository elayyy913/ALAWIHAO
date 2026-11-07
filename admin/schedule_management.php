<?php
session_start();
include '../db_connect.php';

// Check if logged in and if admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    header("Location: login.php");
    exit();
}

if (!ensureRescheduleRequestsTable($conn)) {
    die("Unable to prepare reschedule requests: " . mysqli_error($conn));
}

$scheduleChangeApprovalTable = "CREATE TABLE IF NOT EXISTS schedule_change_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    requested_by INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    schedule_date DATE NOT NULL,
    schedule_time TIME NOT NULL,
    service_type VARCHAR(255) NOT NULL,
    notes TEXT NULL,
    proposed_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    status VARCHAR(30) NOT NULL DEFAULT 'Pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_schedule_change_status (status, created_at),
    INDEX idx_schedule_change_schedule (schedule_id, status)
)";
if (!$conn->query($scheduleChangeApprovalTable)) {
    die("Unable to prepare schedule change approvals: " . $conn->error);
}
$proposedStatusColumn = $conn->query("SHOW COLUMNS FROM schedule_change_approvals LIKE 'proposed_status'");
if (!$proposedStatusColumn) {
    die("Unable to check schedule change approval schema: " . $conn->error);
}
if ($proposedStatusColumn->num_rows === 0 && !$conn->query(
    "ALTER TABLE schedule_change_approvals ADD COLUMN proposed_status VARCHAR(30) NOT NULL DEFAULT 'Pending' AFTER notes"
)) {
    die("Unable to update schedule change approval schema: " . $conn->error);
}

$scheduleChangeCsrfToken = $_SESSION['schedule_change_csrf'] ??= bin2hex(random_bytes(32));

$legacy_pattern = '/(?:\[Requester ID:\s*(\d+)\]\s*)?Request:\s*(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}(?::\d{2})?)\s*\|\s*Reason:\s*(.*?)(?=\s*\|\s*(?:\[Requester ID:\s*\d+\]\s*)?Request:|\s*\|\s*Rescheduled \(Orig:|$)/s';
$legacy_result = $conn->query("SELECT id, notes FROM schedules WHERE status = 'Reschedule Requested'");
if (!$legacy_result) {
    die("Unable to load legacy reschedule requests: " . $conn->error);
}
while ($legacy_schedule = $legacy_result->fetch_assoc()) {
    $legacy_notes = $legacy_schedule['notes'] ?? '';
    if (!preg_match_all($legacy_pattern, $legacy_notes, $legacy_matches, PREG_SET_ORDER)) {
        continue;
    }

    $legacy_schedule_id = (int) $legacy_schedule['id'];
    $legacy_seen = [];
    $legacy_existing_counts = [];
    foreach ($legacy_matches as $legacy_match) {
        $legacy_requester_id = isset($legacy_match[1]) ? (int) $legacy_match[1] : 0;
        $legacy_date = $legacy_match[2];
        $legacy_time = $legacy_match[3];
        $legacy_reason = trim($legacy_match[4]);

        $legacy_key = hash('sha256', json_encode([
            $legacy_schedule_id,
            $legacy_requester_id,
            $legacy_date,
            $legacy_time,
            $legacy_reason
        ]));
        $legacy_seen[$legacy_key] = ($legacy_seen[$legacy_key] ?? 0) + 1;

        if (!array_key_exists($legacy_key, $legacy_existing_counts)) {
            $count_request = $conn->prepare("SELECT COUNT(*) AS request_count
                FROM schedule_reschedule_requests
                WHERE schedule_id = ? AND requester_id = ? AND requested_date = ?
                    AND requested_time = ? AND reason = ?");
            if (!$count_request) {
                die("Unable to check legacy reschedule request: " . $conn->error);
            }
            $count_request->bind_param(
                "iisss",
                $legacy_schedule_id,
                $legacy_requester_id,
                $legacy_date,
                $legacy_time,
                $legacy_reason
            );
            if (!$count_request->execute()) {
                $count_error = $count_request->error;
                $count_request->close();
                die("Unable to check legacy reschedule request: " . $count_error);
            }
            $legacy_existing_counts[$legacy_key] = (int) $count_request->get_result()->fetch_assoc()['request_count'];
            $count_request->close();
        }

        if ($legacy_seen[$legacy_key] <= $legacy_existing_counts[$legacy_key]) {
            continue;
        }

        $legacy_insert = $conn->prepare("INSERT INTO schedule_reschedule_requests
            (schedule_id, requester_id, requested_date, requested_time, reason)
            VALUES (?, ?, ?, ?, ?)");
        if (!$legacy_insert) {
            die("Unable to import legacy reschedule request: " . $conn->error);
        }
        $legacy_insert->bind_param(
            "iisss",
            $legacy_schedule_id,
            $legacy_requester_id,
            $legacy_date,
            $legacy_time,
            $legacy_reason
        );
        if (!$legacy_insert->execute()) {
            $legacy_error = $legacy_insert->error;
            $legacy_insert->close();
            die("Unable to import legacy reschedule request: " . $legacy_error);
        }
        $legacy_insert->close();
        $legacy_existing_counts[$legacy_key]++;
    }
}
$legacy_result->free();

// ── Notification helper ───────────────────────────────────────────────────────
function notify_user_id($conn, $user_id, $schedule_id, $title, $message, $type) {
    $user_id = (int) $user_id;
    if ($user_id < 1) {
        return true;
    }

    $ins = $conn->prepare("INSERT INTO notifications
        (user_id, target_role, schedule_id, title, message, type)
        VALUES (?, 'User', ?, ?, ?, ?)");
    if (!$ins) {
        error_log('Unable to prepare schedule notification: ' . $conn->error);
        return false;
    }
    $ins->bind_param("iisss", $user_id, $schedule_id, $title, $message, $type);
    $saved = $ins->execute();
    if (!$saved) {
        error_log('Unable to create schedule notification: ' . $ins->error);
    }
    $ins->close();
    return $saved;
}

function notify_user_by_schedule($conn, $schedule_id, $patient_name, $title, $message, $type) {
    $schedule_id = (int) $schedule_id;
    $stmt = $conn->prepare("SELECT patient_id, category, patient_name, notes FROM schedules WHERE id = ? LIMIT 1");
    if (!$stmt) {
        error_log('Unable to load schedule notification recipient: ' . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $schedule_id);
    if (!$stmt->execute()) {
        error_log('Unable to load schedule notification recipient: ' . $stmt->error);
        $stmt->close();
        return false;
    }
    $schedule = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$schedule) {
        error_log('Unable to find schedule for user notification.');
        return false;
    }

    $user_id = 0;
    $patient_id = (int) ($schedule['patient_id'] ?? 0);
    if ($patient_id > 0 && $schedule['category'] === 'Maternal') {
        $patient_stmt = $conn->prepare("SELECT user_id FROM maternal_registration WHERE id = ? LIMIT 1");
        if ($patient_stmt) {
            $patient_stmt->bind_param("i", $patient_id);
            if ($patient_stmt->execute()) {
                $patient = $patient_stmt->get_result()->fetch_assoc();
                $user_id = (int) ($patient['user_id'] ?? 0);
            } else {
                error_log('Unable to resolve maternal schedule notification recipient: ' . $patient_stmt->error);
                $patient_stmt->close();
                return false;
            }
            $patient_stmt->close();
        } else {
            error_log('Unable to prepare maternal schedule notification recipient lookup: ' . $conn->error);
            return false;
        }
    } elseif ($patient_id > 0 && $schedule['category'] === 'Child') {
        $patient_stmt = $conn->prepare("SELECT user_id FROM children WHERE id = ? LIMIT 1");
        if ($patient_stmt) {
            $patient_stmt->bind_param("i", $patient_id);
            if ($patient_stmt->execute()) {
                $patient = $patient_stmt->get_result()->fetch_assoc();
                $user_id = (int) ($patient['user_id'] ?? 0);
            } else {
                error_log('Unable to resolve child schedule notification recipient: ' . $patient_stmt->error);
                $patient_stmt->close();
                return false;
            }
            $patient_stmt->close();
        } else {
            error_log('Unable to prepare child schedule notification recipient lookup: ' . $conn->error);
            return false;
        }
    }

    if ($user_id < 1 && preg_match('/\[Requester ID:\s*(\d+)\]/', $schedule['notes'] ?? '', $matches)) {
        $user_id = (int) $matches[1];
    }
    if ($user_id > 0) {
        return notify_user_id($conn, $user_id, $schedule_id, $title, $message, $type);
    }

    if ($patient_id > 0) {
        return true;
    }

    $patient_name = $schedule['patient_name'] ?? $patient_name;
    if ($schedule['category'] === 'Maternal') {
        $lookup = $conn->prepare("SELECT user_id FROM maternal_registration
            WHERE LOWER(TRIM(CONCAT_WS(' ', TRIM(client_fname), TRIM(client_lname)))) = LOWER(TRIM(?))
            LIMIT 2");
    } elseif ($schedule['category'] === 'Child') {
        $lookup = $conn->prepare("SELECT user_id FROM children
            WHERE LOWER(TRIM(child_name)) = LOWER(TRIM(?))
            LIMIT 2");
    } else {
        return true;
    }

    if (!$lookup) {
        error_log('Unable to prepare schedule notification recipient lookup: ' . $conn->error);
        return false;
    }
    $lookup->bind_param("s", $patient_name);
    if (!$lookup->execute()) {
        error_log('Unable to look up schedule notification recipient: ' . $lookup->error);
        $lookup->close();
        return false;
    }
    $recipients = $lookup->get_result();
    if ($recipients->num_rows !== 1) {
        $lookup->close();
        return true;
    }
    $recipient = $recipients->fetch_assoc();
    $lookup->close();
    return notify_user_id($conn, (int) ($recipient['user_id'] ?? 0), $schedule_id, $title, $message, $type);
}

function apply_schedule_change($conn, array $change, array $old_schedule): bool {
    $category = $change['category'];
    $patient_name = trim($change['patient_name']);
    $patient_id = null;

    if ($category === 'Maternal') {
        $patient_lookup = $conn->prepare("SELECT id FROM maternal_registration
            WHERE status = 'Approved'
              AND LOWER(TRIM(CONCAT_WS(' ', TRIM(client_fname), TRIM(client_lname)))) = LOWER(TRIM(?))
            LIMIT 2");
    } elseif ($category === 'Child') {
        $patient_lookup = $conn->prepare("SELECT id FROM children
            WHERE LOWER(TRIM(child_name)) = LOWER(TRIM(?))
            LIMIT 2");
    } else {
        error_log('Unable to resolve schedule patient: unsupported category.');
        return false;
    }

    if (!$patient_lookup) {
        error_log('Unable to prepare schedule patient lookup: ' . $conn->error);
        return false;
    }

    $patient_lookup->bind_param("s", $patient_name);
    if ($patient_lookup->execute()) {
        $matches = $patient_lookup->get_result();
        if ($matches->num_rows === 1) {
            $patient_id = (int) $matches->fetch_assoc()['id'];
        }
    } else {
        error_log('Unable to resolve schedule patient: ' . $patient_lookup->error);
        $patient_lookup->close();
        return false;
    }
    $patient_lookup->close();

    if ($patient_id === null
        && $old_schedule['category'] === $category
        && strcasecmp(trim($old_schedule['patient_name']), $patient_name) === 0
        && !empty($old_schedule['patient_id'])) {
        $patient_id = (int) $old_schedule['patient_id'];
    }
    if ($patient_id === null
        && ($old_schedule['category'] !== $category
            || strcasecmp(trim($old_schedule['patient_name']), $patient_name) !== 0)) {
        error_log('Schedule update references an unknown or ambiguous patient.');
        return false;
    }

    $stmt = $conn->prepare("UPDATE schedules
        SET patient_id = ?, patient_name = ?, category = ?, schedule_date = ?,
            schedule_time = ?, service_type = ?, notes = ?, status = ?
        WHERE id = ?");
    if (!$stmt) {
        error_log('Unable to prepare schedule update: ' . $conn->error);
        return false;
    }
    $schedule_id = (int) $change['schedule_id'];
    $stmt->bind_param(
        "isssssssi",
        $patient_id,
        $patient_name,
        $category,
        $change['schedule_date'],
        $change['schedule_time'],
        $change['service_type'],
        $change['notes'],
        $change['status'],
        $schedule_id
    );
    $updated = $stmt->execute();
    if (!$updated) {
        error_log('Unable to update schedule: ' . $stmt->error);
        $stmt->close();
        return false;
    }
    $stmt->close();

    $changed = $old_schedule['patient_name'] !== $patient_name
        || $old_schedule['category'] !== $category
        || $old_schedule['service_type'] !== $change['service_type']
        || $old_schedule['schedule_date'] !== $change['schedule_date']
        || substr((string) $old_schedule['schedule_time'], 0, 5) !== substr((string) $change['schedule_time'], 0, 5)
        || (string) ($old_schedule['notes'] ?? '') !== (string) ($change['notes'] ?? '')
        || $old_schedule['status'] !== $change['status'];
    if (!$changed) {
        return true;
    }

    $date_fmt = date('F j, Y', strtotime($change['schedule_date']));
    $time_fmt = date('g:i A', strtotime($change['schedule_time']));
    $title = $change['status'] === 'Cancelled' ? 'Schedule Cancelled' : 'Schedule Updated';
    $notification_message = $change['status'] === 'Cancelled'
        ? "Your {$change['service_type']} schedule on {$date_fmt} at {$time_fmt} has been cancelled by the health center."
        : "Your schedule has been updated. Service: {$change['service_type']}; date: {$date_fmt}; time: {$time_fmt}; status: {$change['status']}.";
    if (!notify_user_by_schedule(
        $conn,
        $schedule_id,
        $patient_name,
        $title,
        $notification_message,
        $change['status'] === 'Cancelled' ? 'cancelled_schedule' : 'updated_schedule'
    )) {
        return false;
    }

    logAdminAction(
        $conn,
        "Updated schedule ID {$schedule_id} for {$patient_name} ({$change['service_type']}) to {$change['schedule_date']} at {$change['schedule_time']}, status: {$change['status']}."
    );
    return true;
}
// ─────────────────────────────────────────────────────────────────────────────

$message = "";
$patientLinkColumn = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'patient_id'");
$schedulePatientLinkReady = $patientLinkColumn && mysqli_num_rows($patientLinkColumn) > 0;

// Handle Batch Add Schedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_schedule_batch'])) {
    $category = mysqli_real_escape_string($conn, $_POST['category']); 
    $schedule_date = mysqli_real_escape_string($conn, $_POST['schedule_date']);
    $schedule_time = mysqli_real_escape_string($conn, $_POST['schedule_time']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']); 
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = 'Pending';
    $patient_ids = isset($_POST['patient_ids']) ? $_POST['patient_ids'] : [];

    if (empty($schedule_date) || empty($schedule_time) || empty($service_type) || empty($patient_ids)) {
        $message = "Error: Please select a date, time, service type, and at least one patient!";
    } elseif (!$schedulePatientLinkReady) {
        $message = "Run database_maternal_schedule_links.sql in phpMyAdmin before creating schedules.";
    } else {
        $success_count = 0;
        $sql = "INSERT INTO schedules (category, patient_id, patient_name, schedule_date, schedule_time, service_type, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            foreach ($patient_ids as $pid) {
                $patient_id = (int) $pid;
                $patient_name = "";
                if ($category === 'Child') {
                    $p_query = "SELECT child_name AS full_name FROM children WHERE id = ? LIMIT 1";
                } else {
                    $p_query = "SELECT CONCAT(client_fname, ' ', client_lname) AS full_name FROM maternal_registration WHERE id = ? LIMIT 1";
                }

                if ($p_stmt = $conn->prepare($p_query)) {
                    $p_stmt->bind_param("i", $patient_id);
                    $p_stmt->execute();
                    $p_res = $p_stmt->get_result();
                    if ($p_row = $p_res->fetch_assoc()) {
                        $patient_name = $p_row['full_name'];
                        
                        $stmt->bind_param("sissssss", $category, $patient_id, $patient_name, $schedule_date, $schedule_time, $service_type, $notes, $status);
                        if ($stmt->execute()) {
                            $success_count++;
                            // ── Notify the patient's user ──
                            $new_schedule_id = $conn->insert_id;
                            logAdminAction(
                                $conn,
                                "Created {$category} schedule ID {$new_schedule_id} for {$patient_name} ({$service_type}) on {$schedule_date} at {$schedule_time}."
                            );
                            $date_fmt = date('F j, Y', strtotime($schedule_date));
                            $time_fmt = date('g:i A', strtotime($schedule_time));
                            notify_user_by_schedule(
                                $conn,
                                $new_schedule_id,
                                $patient_name,
                                "New Schedule: {$service_type}",
                                "You have a new schedule for {$patient_name} — {$service_type} on {$date_fmt} at {$time_fmt}.",
                                'new_schedule'
                            );
                        }
                    }
                    $p_stmt->close();
                }
            }
            $stmt->close();
            $message = "Successfully created schedules for " . $success_count . " patient(s)!";
        } else {
            $message = "Error preparing statement: " . $conn->error;
        }
    }
}

// Handle Super Admin approval or rejection of an Admin's schedule change.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['review_schedule_change'])) {
    $request_id = filter_var($_POST['change_request_id'] ?? null, FILTER_VALIDATE_INT);
    $action = $_POST['schedule_change_action'] ?? '';
    $csrf_valid = hash_equals($scheduleChangeCsrfToken, (string) ($_POST['schedule_change_csrf'] ?? ''));

    if (($_SESSION['role'] ?? '') !== 'Super Admin') {
        http_response_code(403);
        $message = "Only a Super Admin can review schedule changes.";
    } elseif (!$csrf_valid || !$request_id || !in_array($action, ['approve', 'reject'], true)) {
        http_response_code($csrf_valid ? 400 : 403);
        $message = "Invalid schedule change review request.";
    } else {
        $conn->begin_transaction();
        $approval_stmt = $conn->prepare("SELECT * FROM schedule_change_approvals
            WHERE id = ? AND status = 'Pending' FOR UPDATE");
        if (!$approval_stmt) {
            $conn->rollback();
            $message = "Unable to load schedule change request: " . $conn->error;
        } else {
            $approval_stmt->bind_param("i", $request_id);
            if (!$approval_stmt->execute()) {
                $approval_error = $approval_stmt->error;
                $approval_stmt->close();
                $conn->rollback();
                $message = "Unable to load schedule change request: " . $approval_error;
            } else {
                $approval = $approval_stmt->get_result()->fetch_assoc();
                $approval_stmt->close();
                if (!$approval) {
                    $conn->rollback();
                    $message = "This schedule change has already been reviewed.";
                } else {
                    $schedule_id = (int) $approval['schedule_id'];
                    $old_stmt = $conn->prepare("SELECT patient_id, patient_name, category, service_type,
                            schedule_date, schedule_time, notes, status
                        FROM schedules WHERE id = ? FOR UPDATE");
                    if (!$old_stmt) {
                        $conn->rollback();
                        $message = "Unable to load the scheduled appointment: " . $conn->error;
                    } else {
                        $old_stmt->bind_param("i", $schedule_id);
                        if (!$old_stmt->execute()) {
                            $old_error = $old_stmt->error;
                            $old_stmt->close();
                            $conn->rollback();
                            $message = "Unable to load the scheduled appointment: " . $old_error;
                        } else {
                            $old_schedule = $old_stmt->get_result()->fetch_assoc();
                            $old_stmt->close();
                            if (!$old_schedule) {
                                if ($action === 'reject') {
                                    $review_stmt = $conn->prepare("UPDATE schedule_change_approvals
                                        SET status = 'Rejected', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP
                                        WHERE id = ? AND status = 'Pending'");
                                    if (!$review_stmt) {
                                        $conn->rollback();
                                        $message = "Unable to reject the stale schedule change: " . $conn->error;
                                    } else {
                                        $reviewer_id = (int) $_SESSION['user_id'];
                                        $review_stmt->bind_param("ii", $reviewer_id, $request_id);
                                        $review_saved = $review_stmt->execute() && $review_stmt->affected_rows === 1;
                                        $review_error = $review_stmt->error;
                                        $review_stmt->close();
                                        if (!$review_saved) {
                                            $conn->rollback();
                                            $message = "Unable to reject the stale schedule change: " . $review_error;
                                        } else {
                                            $conn->commit();
                                            $message = "Schedule change rejected because the original appointment no longer exists.";
                                        }
                                    }
                                } else {
                                    $conn->rollback();
                                    $message = "The scheduled appointment no longer exists; this change cannot be approved.";
                                }
                            } else {
                                $updated = true;
                                if ($action === 'approve') {
                                    $change = $approval;
                                    $change['schedule_id'] = $schedule_id;
                                    $change['status'] = $approval['proposed_status'];
                                    $updated = apply_schedule_change($conn, $change, $old_schedule);
                                }

                                if (!$updated) {
                                    $conn->rollback();
                                    $message = "Unable to apply the schedule change. No changes were saved.";
                                } else {
                                    $new_status = $action === 'approve' ? 'Approved' : 'Rejected';
                                    $review_stmt = $conn->prepare("UPDATE schedule_change_approvals
                                        SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP
                                        WHERE id = ? AND status = 'Pending'");
                                    if (!$review_stmt) {
                                        $conn->rollback();
                                        $message = "Unable to save the review result: " . $conn->error;
                                    } else {
                                        $reviewer_id = (int) $_SESSION['user_id'];
                                        $review_stmt->bind_param("sii", $new_status, $reviewer_id, $request_id);
                                        $review_saved = $review_stmt->execute() && $review_stmt->affected_rows === 1;
                                        $review_error = $review_stmt->error;
                                        $review_stmt->close();
                                        if (!$review_saved) {
                                            $conn->rollback();
                                            $message = "Unable to save the review result: " . $review_error;
                                        } else {
                                            $conn->commit();
                                            $message = $action === 'approve'
                                                ? "Schedule change approved and applied. The scheduled account was notified if linked."
                                                : "Schedule change rejected. The original schedule remains unchanged.";
                                            logAdminAction(
                                                $conn,
                                                "{$new_status} schedule change request ID {$request_id} for schedule ID {$schedule_id}."
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Admin edits are queued for Super Admin approval. Super Admin edits apply immediately.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_schedule'])) {
    $id = filter_var($_POST['schedule_id'] ?? null, FILTER_VALIDATE_INT);
    $category = trim((string) ($_POST['category'] ?? ''));
    $patient_name = trim((string) ($_POST['patient_name'] ?? ''));
    $schedule_date = trim((string) ($_POST['schedule_date'] ?? ''));
    $schedule_time = trim((string) ($_POST['schedule_time'] ?? ''));
    $service_type = trim((string) ($_POST['service_type'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? ''));
    $csrf_valid = hash_equals($scheduleChangeCsrfToken, (string) ($_POST['schedule_change_csrf'] ?? ''));
    $valid_date = DateTime::createFromFormat('!Y-m-d', $schedule_date);
    $valid_date = $valid_date && $valid_date->format('Y-m-d') === $schedule_date;
    $valid_time_minute = DateTime::createFromFormat('!H:i', $schedule_time);
    $valid_time_second = DateTime::createFromFormat('!H:i:s', $schedule_time);
    $valid_time = ($valid_time_minute && $valid_time_minute->format('H:i') === $schedule_time)
        || ($valid_time_second && $valid_time_second->format('H:i:s') === $schedule_time);

    if (!$csrf_valid || !$id || !in_array($category, ['Child', 'Maternal'], true)
        || $patient_name === '' || $service_type === '' || !$valid_date || !$valid_time
        || !in_array($status, ['Pending', 'Completed', 'Cancelled', 'Reschedule Requested'], true)) {
        http_response_code($csrf_valid ? 400 : 403);
        $message = "Invalid schedule details. Please refresh the page and try again.";
    } else {
        $change = [
            'schedule_id' => (int) $id,
            'patient_name' => $patient_name,
            'category' => $category,
            'schedule_date' => $schedule_date,
            'schedule_time' => $schedule_time,
            'service_type' => $service_type,
            'notes' => $notes,
            'status' => $status,
        ];
        $conn->begin_transaction();
        $old_stmt = $conn->prepare("SELECT patient_id, patient_name, category, service_type,
                schedule_date, schedule_time, notes, status
            FROM schedules WHERE id = ? FOR UPDATE");
        if (!$old_stmt) {
            $conn->rollback();
            $message = "Unable to load the scheduled appointment: " . $conn->error;
        } else {
            $old_stmt->bind_param("i", $id);
            if (!$old_stmt->execute()) {
                $old_error = $old_stmt->error;
                $old_stmt->close();
                $conn->rollback();
                $message = "Unable to load the scheduled appointment: " . $old_error;
            } else {
                $old_schedule = $old_stmt->get_result()->fetch_assoc();
                $old_stmt->close();
                if (!$old_schedule) {
                    $conn->rollback();
                    $message = "The scheduled appointment no longer exists.";
                } elseif (($_SESSION['role'] ?? '') === 'Admin') {
                    $pending_stmt = $conn->prepare("SELECT id FROM schedule_change_approvals
                        WHERE schedule_id = ? AND status = 'Pending' LIMIT 1");
                    if (!$pending_stmt) {
                        $conn->rollback();
                        $message = "Unable to check pending approvals: " . $conn->error;
                    } else {
                        $pending_stmt->bind_param("i", $id);
                        if (!$pending_stmt->execute()) {
                            $pending_error = $pending_stmt->error;
                            $pending_stmt->close();
                            $conn->rollback();
                            $message = "Unable to check pending approvals: " . $pending_error;
                        } elseif ($pending_stmt->get_result()->num_rows > 0) {
                            $pending_stmt->close();
                            $conn->rollback();
                            $message = "This schedule already has a pending Super Admin approval.";
                        } else {
                            $pending_stmt->close();
                            $request_stmt = $conn->prepare("INSERT INTO schedule_change_approvals
                                (schedule_id, requested_by, patient_name, category, schedule_date,
                                    schedule_time, service_type, notes, proposed_status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            if (!$request_stmt) {
                                $conn->rollback();
                                $message = "Unable to submit the schedule change for approval: " . $conn->error;
                            } else {
                                $requester_id = (int) $_SESSION['user_id'];
                                $request_stmt->bind_param(
                                    "iisssssss",
                                    $id,
                                    $requester_id,
                                    $patient_name,
                                    $category,
                                    $schedule_date,
                                    $schedule_time,
                                    $service_type,
                                    $notes,
                                    $status
                                );
                                if ($request_stmt->execute()) {
                                    $request_id = (int) $conn->insert_id;
                                    $request_stmt->close();
                                    $admin_notification = $conn->prepare("INSERT INTO notifications
                                        (user_id, target_role, schedule_id, title, message, type)
                                        VALUES (0, 'Super Admin', ?, 'Schedule Change Approval Needed', ?, 'updated_schedule')");
                                    if (!$admin_notification) {
                                        $conn->rollback();
                                        $message = "Unable to notify Super Admin about this change: " . $conn->error;
                                    } else {
                                        $admin_notice = "Admin #{$requester_id} submitted a schedule edit for {$patient_name} ({$service_type}) for review.";
                                        $admin_notification->bind_param("is", $id, $admin_notice);
                                        if (!$admin_notification->execute()) {
                                            $notification_error = $admin_notification->error;
                                            $admin_notification->close();
                                            $conn->rollback();
                                            $message = "Unable to notify Super Admin about this change: " . $notification_error;
                                        } else {
                                            $admin_notification->close();
                                            $conn->commit();
                                            $message = "Schedule change submitted for Super Admin approval. The current schedule remains active until it is approved.";
                                            logAdminAction(
                                                $conn,
                                                "Submitted schedule change request ID {$request_id} for schedule ID {$id} ({$patient_name})."
                                            );
                                        }
                                    }
                                } else {
                                    $request_error = $request_stmt->error;
                                    $request_stmt->close();
                                    $conn->rollback();
                                    $message = "Unable to submit the schedule change for approval: " . $request_error;
                                }
                            }
                        }
                    }
                } elseif (($_SESSION['role'] ?? '') === 'Super Admin') {
                    if (apply_schedule_change($conn, $change, $old_schedule)) {
                        $conn->commit();
                        $message = "Schedule updated successfully. The scheduled account was notified if linked.";
                    } else {
                        $conn->rollback();
                        $message = "Unable to update the schedule. No changes were saved.";
                    }
                } else {
                    $conn->rollback();
                    http_response_code(403);
                    $message = "You are not allowed to edit schedules.";
                }
            }
        }
    }
}

// Handle Delete Schedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_schedule'])) {
    $id = intval($_POST['schedule_id']);
    $sched_stmt = $conn->prepare("SELECT patient_name, service_type, schedule_date FROM schedules WHERE id = ?");
    $sched = null;
    if ($sched_stmt) {
        $sched_stmt->bind_param("i", $id);
        if ($sched_stmt->execute()) {
            $sched = $sched_stmt->get_result()->fetch_assoc();
        }
        $sched_stmt->close();
    }

    if ($sched) {
        $date_fmt = date('F j, Y', strtotime($sched['schedule_date']));
        notify_user_by_schedule($conn, $id, $sched['patient_name'],
            "Schedule Cancelled",
            "Your {$sched['service_type']} schedule on {$date_fmt} was cancelled by the health center.",
            'cancelled_schedule'
        );
    }

    $sql = "DELETE FROM schedules WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Schedule deleted successfully!";
            if ($sched) {
                logAdminAction(
                    $conn,
                    "Deleted schedule ID {$id} for {$sched['patient_name']} ({$sched['service_type']}) dated {$sched['schedule_date']}."
                );
            } else {
                logAdminAction($conn, "Deleted schedule ID {$id}.");
            }
            $return_tab = preg_replace('/[^a-zA-Z]/', '', $_POST['return_tab'] ?? 'childTab');
            header("Location: schedule_management.php?tab=" . urlencode($return_tab) . "&msg=deleted");
            exit();
        } else {
            $message = "Error deleting schedule: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Reschedule Confirmation (Approve / Reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['handle_reschedule'])) {
    $request_id = (int) ($_POST['reschedule_request_id'] ?? 0);
    $action = $_POST['reschedule_action'] ?? '';
    if ($request_id < 1 || !in_array($action, ['approve', 'reject'], true)) {
        $message = "Invalid reschedule request.";
    } else {
        $conn->begin_transaction();
        $request_stmt = $conn->prepare("SELECT r.schedule_id, r.requester_id, r.requested_date,
                r.requested_time, s.patient_name, s.service_type, s.schedule_date, s.schedule_time, s.notes
            FROM schedule_reschedule_requests r
            JOIN schedules s ON s.id = r.schedule_id
            WHERE r.id = ? AND r.status = 'Pending'
            FOR UPDATE");
        if (!$request_stmt) {
            $conn->rollback();
            $message = "Unable to load reschedule request: " . $conn->error;
        } else {
            $request_stmt->bind_param("i", $request_id);
            if (!$request_stmt->execute()) {
                $request_error = $request_stmt->error;
                $request_stmt->close();
                $conn->rollback();
                $message = "Unable to load reschedule request: " . $request_error;
            } else {
                $request = $request_stmt->get_result()->fetch_assoc();
                $request_stmt->close();

                if (!$request) {
                    $conn->rollback();
                    $message = "This reschedule request has already been processed.";
                } else {
                    $new_request_status = $action === 'approve' ? 'Approved' : 'Rejected';
                    $update_request = $conn->prepare("UPDATE schedule_reschedule_requests
                        SET status = ? WHERE id = ? AND status = 'Pending'");
                    $update_request->bind_param("si", $new_request_status, $request_id);
                    $request_updated = $update_request->execute() && $update_request->affected_rows === 1;
                    $update_error = $update_request->error;
                    $update_request->close();

                    if (!$request_updated) {
                        $conn->rollback();
                        $message = "Unable to process reschedule request: " . $update_error;
                    } else {
                        $schedule_id = (int) $request['schedule_id'];

                        // On approve: always set to Approved.
                        // On reject: check if any other pending requests remain; if not, revert to Pending.
                        if ($action === 'approve') {
                            $schedule_status = 'Approved';
                            // Also close out any other stale pending requests for this schedule
                            $conn->query("UPDATE schedule_reschedule_requests
                                SET status = 'Superseded'
                                WHERE schedule_id = $schedule_id
                                  AND id != $request_id
                                  AND LOWER(status) = 'pending'");
                        } else {
                            $remaining = $conn->query("SELECT COUNT(*) AS c
                                FROM schedule_reschedule_requests
                                WHERE schedule_id = $schedule_id AND LOWER(status) = 'pending'");
                            $rem_count = $remaining ? (int)$remaining->fetch_assoc()['c'] : 0;
                            $schedule_status = $rem_count > 0 ? 'Reschedule Requested' : 'Pending';
                        }

                        $old_date = $request['schedule_date'];
                        $notes = $request['notes'] ?? '';

                        // Worker may override the requested date/time from the modal
                        $override_date = trim($_POST['override_date'] ?? '');
                        $override_time = trim($_POST['override_time'] ?? '');
                        $worker_note   = trim($_POST['worker_note']   ?? '');

                        $final_date = ($action === 'approve' && !empty($override_date))
                            ? $override_date
                            : $request['requested_date'];
                        $final_time = ($action === 'approve' && !empty($override_time))
                            ? $override_time
                            : $request['requested_time'];

                        if ($action === 'approve') {
                            $notes = trim($notes . ' | Rescheduled (Orig: ' . $old_date . ')');
                            if (!empty($override_date) && $override_date !== $request['requested_date']) {
                                $notes = trim($notes . ' | Worker adjusted to: ' . $override_date);
                            }
                        }
                        if (!empty($worker_note)) {
                            $notes = trim($notes . ' | Worker note: ' . $worker_note);
                        }

                        if ($action === 'approve') {
                            $update_schedule = $conn->prepare("UPDATE schedules
                                SET schedule_date = ?, schedule_time = ?, status = ?, notes = ? WHERE id = ?");
                            $update_schedule->bind_param(
                                "ssssi",
                                $final_date,
                                $final_time,
                                $schedule_status,
                                $notes,
                                $schedule_id
                            );
                        } else {
                            // On reject, still save the worker note to the schedule notes if provided
                            if (!empty($worker_note)) {
                                $update_schedule = $conn->prepare("UPDATE schedules SET status = ?, notes = ? WHERE id = ?");
                                $update_schedule->bind_param("ssi", $schedule_status, $notes, $schedule_id);
                            } else {
                                $update_schedule = $conn->prepare("UPDATE schedules SET status = ? WHERE id = ?");
                                $update_schedule->bind_param("si", $schedule_status, $schedule_id);
                            }
                        }

                        if (!$update_schedule->execute()) {
                            $schedule_error = $update_schedule->error;
                            $update_schedule->close();
                            $conn->rollback();
                            $message = "Unable to update schedule: " . $schedule_error;
                        } else {
                            $update_schedule->close();
                            $conn->commit();
                            $message = $action === 'approve'
                                ? "Reschedule request approved successfully!"
                                : "Reschedule request rejected.";
                            $reschedule_action_label = $action === 'approve' ? 'Approved' : 'Rejected';
                            $reschedule_action_detail = $action === 'approve'
                                ? "to {$request['requested_date']} at {$request['requested_time']}"
                                : "requested for {$request['requested_date']} at {$request['requested_time']}";
                            logAdminAction(
                                $conn,
                                "{$reschedule_action_label} reschedule request ID {$request_id} for schedule ID {$schedule_id} ({$request['patient_name']}) {$reschedule_action_detail}."
                            );

                            $requester_id = (int) $request['requester_id'];
                            if ($requester_id > 0) {
                                $date = $action === 'approve' ? $final_date : $request['schedule_date'];
                                $time = $action === 'approve' ? $final_time : $request['schedule_time'];
                                $date_fmt = date('F j, Y', strtotime($date));
                                $time_fmt = date('g:i A', strtotime($time));
                                $title = $action === 'approve'
                                    ? "Reschedule Request Approved"
                                    : "Reschedule Request Rejected";
                                $notification_message = $action === 'approve'
                                    ? "Your reschedule request for {$request['service_type']} has been approved. New date: {$date_fmt} at {$time_fmt}."
                                    : "Your reschedule request for {$request['service_type']} was not approved. The current schedule on {$date_fmt} at {$time_fmt} remains.";
                                $notification = $conn->prepare("INSERT INTO notifications
                                    (user_id, target_role, schedule_id, title, message, type)
                                    VALUES (?, 'User', ?, ?, ?, 'updated_schedule')");
                                if ($notification) {
                                    $notification->bind_param(
                                        "iiss",
                                        $requester_id,
                                        $schedule_id,
                                        $title,
                                        $notification_message
                                    );
                                    $notification->execute();
                                    $notification->close();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Handle Reschedule Request Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_reschedule'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $new_date = trim($_POST['new_date'] ?? '');
    $new_time = trim($_POST['new_time'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    $update_query = "UPDATE schedules 
                     SET status = 'Reschedule Requested', 
                         notes = CONCAT(COALESCE(notes, ''), ' | Request: ', ?, ' ', ?, ' | Reason: ', ?)
                     WHERE id = ?";

    if ($stmt = $conn->prepare($update_query)) {
        $stmt->bind_param("sssi", $new_date, $new_time, $reason, $schedule_id);
        
        if ($stmt->execute()) {
            $request_insert = $conn->prepare("INSERT INTO schedule_reschedule_requests
                (schedule_id, requester_id, requested_date, requested_time, reason)
                VALUES (?, 0, ?, ?, ?)");
            if ($request_insert) {
                $request_insert->bind_param("isss", $schedule_id, $new_date, $new_time, $reason);
                if ($request_insert->execute()) {
                    $message = "<div class='alert success'><i class='fa fa-check-circle'></i> Tagumpay na naipadala ang iyong request para sa pagbabago ng iskedyul!</div>";
                    logAdminAction(
                        $conn,
                        "Submitted a reschedule request for schedule ID {$schedule_id}: {$new_date} at {$new_time}. Reason: {$reason}"
                    );
                } else {
                    $message = "<div class='alert error'><i class='fa fa-triangle-exclamation'></i> Nabigo ang pag-save ng request: " . htmlspecialchars($request_insert->error) . "</div>";
                }
                $request_insert->close();
            } else {
                $message = "<div class='alert error'><i class='fa fa-triangle-exclamation'></i> Nabigo ang pag-save ng request: " . htmlspecialchars($conn->error) . "</div>";
            }
        } else {
            $message = "<div class='alert error'><i class='fa fa-triangle-exclamation'></i> Nabigo ang pag-request: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    }
}
// SECURED QUERIES
// Deduplicate: for each patient+service combination, prefer the latest Approved row
// over any older Pending row. We pick the row with the highest id per patient+service.
$result_child = $conn->query("
    SELECT s.*
    FROM schedules s
    WHERE LOWER(s.category) = 'child'
      AND s.status IN ('Pending', 'Approved')
      AND s.id = (
          SELECT s2.id FROM schedules s2
          WHERE LOWER(s2.category) = 'child'
            AND s2.status IN ('Pending', 'Approved')
            AND LOWER(TRIM(s2.patient_name)) = LOWER(TRIM(s.patient_name))
            AND LOWER(TRIM(s2.service_type))  = LOWER(TRIM(s.service_type))
          ORDER BY
              CASE WHEN LOWER(s2.status) = 'approved' THEN 0 ELSE 1 END ASC,
              s2.id DESC
          LIMIT 1
      )
    ORDER BY s.schedule_date ASC, s.schedule_time ASC");
$result_maternal = $conn->query("
    SELECT s.*
    FROM schedules s
    WHERE LOWER(s.category) = 'maternal'
      AND s.status IN ('Pending', 'Approved')
      AND s.id = (
          SELECT s2.id FROM schedules s2
          WHERE LOWER(s2.category) = 'maternal'
            AND s2.status IN ('Pending', 'Approved')
            AND LOWER(TRIM(s2.patient_name)) = LOWER(TRIM(s.patient_name))
            AND LOWER(TRIM(s2.service_type))  = LOWER(TRIM(s.service_type))
          ORDER BY
              CASE WHEN LOWER(s2.status) = 'approved' THEN 0 ELSE 1 END ASC,
              s2.id DESC
          LIMIT 1
      )
    ORDER BY s.schedule_date ASC, s.schedule_time ASC");
$resched_base_sql = "SELECT r.id AS request_id, r.requester_id, r.requested_date,
        r.requested_time, r.reason, r.created_at, s.id AS schedule_id, s.patient_name, s.service_type,
        s.schedule_date, s.schedule_time, s.category
    FROM schedule_reschedule_requests r
    JOIN schedules s ON s.id = r.schedule_id
    WHERE r.status = 'Pending' AND s.status = 'Reschedule Requested'
      AND NOT EXISTS (
          SELECT 1
          FROM schedule_reschedule_requests approved
          WHERE approved.schedule_id = r.schedule_id
            AND LOWER(approved.status) = 'approved'
            AND approved.requested_date = r.requested_date
            AND approved.requested_time = r.requested_time
            AND approved.requested_date = s.schedule_date
            AND approved.requested_time = s.schedule_time
      )";
$result_reschedule_child    = $conn->query($resched_base_sql . " AND LOWER(s.category) = 'child'    ORDER BY r.created_at ASC, r.id ASC");
$result_reschedule_maternal = $conn->query($resched_base_sql . " AND LOWER(s.category) = 'maternal' ORDER BY r.created_at ASC, r.id ASC");
// keep combined for backwards-compat (badge count on tab)
$result_reschedule_count = $conn->query("SELECT COUNT(*) AS c FROM schedule_reschedule_requests r
    JOIN schedules s ON s.id = r.schedule_id
    WHERE r.status = 'Pending' AND s.status = 'Reschedule Requested'");
$resched_total = $result_reschedule_count ? (int)$result_reschedule_count->fetch_assoc()['c'] : 0;
$result_completed = $conn->query("SELECT * FROM schedules WHERE status = 'Completed' ORDER BY schedule_date DESC, schedule_time DESC");
$result_schedule_change_approvals = false;
if (($_SESSION['role'] ?? '') === 'Super Admin') {
    $result_schedule_change_approvals = $conn->query("SELECT a.*, s.patient_name AS current_patient_name,
            s.category AS current_category, s.schedule_date AS current_schedule_date,
            s.schedule_time AS current_schedule_time, s.service_type AS current_service_type,
            s.status AS current_status
        FROM schedule_change_approvals a
        LEFT JOIN schedules s ON s.id = a.schedule_id
        WHERE a.status = 'Pending'
        ORDER BY a.created_at ASC, a.id ASC");
    if (!$result_schedule_change_approvals) {
        die("Unable to load schedule change approvals: " . $conn->error);
    }
}

$result_infants = $conn->query("SELECT id, child_name AS full_name, 'Child' AS category FROM children ORDER BY child_name ASC");
$result_maternal_patients = $conn->query("SELECT id, CONCAT(client_fname, ' ', client_lname) AS full_name, 'Maternal' AS category FROM maternal_registration ORDER BY client_lname ASC");

function isImmunizationService($service_type) {
    return preg_match(
        '/\b(immuni[sz]ation|vaccin(?:e|ation)|bakuna|bcg|opv|ipv|pcv|mmr|pentavalent|penta|hepa?\s*b|hepatitis\s*b|hib|dpt|tdap|rotavirus|influenza|tetanus|pneumococcal|oral polio|inactivated polio)\b/i',
        (string) $service_type
    ) === 1;
}

function renderScheduleSection($title, $category, $schedules) {
    ?>
    <section class="schedule-section">
        <h3><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> Appointments (<?= count($schedules) ?>)</h3>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Patient Name (<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>)</th>
                        <th>Service / Check-up</th>
                        <th>Date &amp; Time</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($schedules): ?>
                        <?php foreach ($schedules as $row):
                            $total_req = (int) ($row['total_resched_count'] ?? 0);
                            $orig_date = '';
                            if (preg_match('/Rescheduled \(Orig: ([^)]+)\)/', $row['notes'] ?? '', $match)) {
                                $orig_date = date('M d, Y', strtotime($match[1]));
                            }
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['patient_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= htmlspecialchars($row['service_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= date('M d, Y', strtotime($row['schedule_date'])) ?><br>
                                    <small><?= date('h:i A', strtotime($row['schedule_time'])) ?></small><br>
                                    <?php if ($orig_date): ?>
                                        <small style="color:#888;font-size:11px;">Orig: <?= htmlspecialchars($orig_date, ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-active"><?= htmlspecialchars($row['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <?php if ($orig_date): ?>
                                        <small style="color:#6b7280;">Rescheduled from <?= htmlspecialchars($orig_date, ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php else: ?>
                                        <small style="color:#aaa;">—</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:5px;align-items:center;">
                                        <a href="#" style="color:var(--sage-green);font-weight:bold;text-decoration:none;"
                                           onclick="openEditModal(
                                               '<?= (int) $row['id'] ?>',
                                               '<?= htmlspecialchars($row['patient_name'] ?? '', ENT_QUOTES) ?>',
                                               '<?= htmlspecialchars($row['category'] ?? '', ENT_QUOTES) ?>',
                                               '<?= htmlspecialchars($row['service_type'] ?? '', ENT_QUOTES) ?>',
                                               '<?= htmlspecialchars($row['schedule_date'] ?? '', ENT_QUOTES) ?>',
                                               '<?= htmlspecialchars($row['schedule_time'] ?? '', ENT_QUOTES) ?>',
                                               '<?= htmlspecialchars($row['status'] ?? '', ENT_QUOTES) ?>',
                                               '<?= htmlspecialchars($row['notes'] ?? '', ENT_QUOTES) ?>'
                                           )">Edit</a>
                                        <span>|</span>
                                        <form method="POST" data-delete-form style="margin:0;">
                                            <input type="hidden" name="schedule_id" value="<?= (int) $row['id'] ?>">
                                            <button type="button" class="btn-delete" onclick="openDeleteModal(this.closest('form'))">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="empty-schedule">No <?= strtolower(htmlspecialchars($title, ENT_QUOTES, 'UTF-8')) ?> appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
}

$childSchedules = $result_child ? $result_child->fetch_all(MYSQLI_ASSOC) : [];
$maternalSchedules = $result_maternal ? $result_maternal->fetch_all(MYSQLI_ASSOC) : [];
$childImmunizationSchedules = array_values(array_filter($childSchedules, function ($schedule) {
    return isImmunizationService($schedule['service_type'] ?? '');
}));
$childCheckupSchedules = array_values(array_filter($childSchedules, function ($schedule) {
    return !isImmunizationService($schedule['service_type'] ?? '');
}));
$maternalImmunizationSchedules = array_values(array_filter($maternalSchedules, function ($schedule) {
    return isImmunizationService($schedule['service_type'] ?? '');
}));
$maternalCheckupSchedules = array_values(array_filter($maternalSchedules, function ($schedule) {
    return !isImmunizationService($schedule['service_type'] ?? '');
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management | Alawihao Health Center</title>
    <style>
        :root {
            --sage-green: #718355;
            --light-beige: #fdfbf7;
            --border-color: #d1d5db;
            --sidebar-width: 280px;
        }

        body { 
            background-color: var(--light-beige); 
            margin: 0; 
            font-family: 'Times New Roman', serif; 
            overflow-x: hidden;
            color: #111;
        }

        #main { 
            margin-left: var(--sidebar-width); 
            width: calc(100% - var(--sidebar-width));
            padding: 20px 30px;
            box-sizing: border-box;
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        body.sidebar-collapsed #main {
            margin-left: 0 !important;
            width: 100%;
        }

        .container {
            width: 100%;
            max-width: none;
            margin: 0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #111;
            padding-bottom: 10px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* The sidebar's fixed bell handles notifications */

        .btn-add {
            background-color: var(--sage-green);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-family: inherit;
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: bold;
            margin-right: 56px; /* clear the fixed notification bell */
        }

        .btn-delete {
            background-color: #991b1b;
            color: white;
            border: none;
            padding: 4px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-family: inherit;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: bold;
            text-decoration: none;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 5px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: bold;
            color: #666;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            font-family: inherit;
        }

        .tab.active {
            color: var(--sage-green);
            border-bottom-color: var(--sage-green);
        }

        .tab-content {
            display: none;
            background: #fff;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .tab-content.active {
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .schedule-section {
            margin-bottom: 18px;
            padding: 16px 18px;
            background: #fff;
            border: 1px solid #edf0eb;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(31, 41, 55, 0.08);
        }

        .schedule-section:last-child {
            margin-bottom: 0;
        }

        .schedule-section h3 {
            margin: 0 0 12px;
            color: var(--sage-green);
            font-size: 1rem;
        }

        .empty-schedule {
            padding: 24px 12px;
            color: #94a3b8;
            text-align: center;
        }

        .reschedule-requests-table {
            min-width: 1000px;
        }

        .reschedule-requests-table th:nth-child(1) { min-width: 145px; }
        .reschedule-requests-table th:nth-child(4) { min-width: 150px; }
        .reschedule-requests-table th:nth-child(5) { min-width: 240px; }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            text-transform: uppercase;
            font-size: 0.8rem;
            color: #4b5563;
            background-color: #fafaf9;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 2px;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: bold;
        }
        .badge-active { background: #e0f2fe; color: #0369a1; }
        .badge-completed { background: #f0fdf4; color: #166534; }
        .badge-cancelled { background: #ffeeef; color: #991b1b; }
        .badge-resched { background: #fef3c7; color: #92400e; }
        .btn-record-link {
            display: inline-block;
            padding: 7px 10px;
            border-radius: 6px;
            background: #718355;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-record-link:hover { background: #5e7044; }

        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.5); 
            overflow-y: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 3% auto;
            padding: 25px;
            border-radius: 4px;
            width: 480px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #4b5563;
            margin-bottom: 5px;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 2px;
            font-family: inherit;
            box-sizing: border-box;
        }

        .patient-checklist-box {
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            padding: 8px;
            background: #fafaf9;
            border-radius: 2px;
        }

        .patient-checkbox-item {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }

        .patient-checkbox-item input {
            width: auto;
            margin-right: 8px;
        }

        .btn-submit {
            width: 100%;
            background-color: var(--sage-green);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            text-transform: uppercase;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .success-msg {
            color: #166534; background: #f0fdf4; padding: 10px;
            border-left: 3px solid var(--sage-green); margin-bottom: 15px;
        }

        @media (max-width: 900px) {
            #main {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .page-header {
                align-items: flex-start;
                gap: 12px;
                flex-wrap: wrap;
            }
            .page-header > div {
                flex-wrap: wrap;
            }
            .btn-add {
                margin-right: 0;
            }
            .tab-content {
                padding: 14px;
            }
        }

        @media (max-width: 560px) {
            #main { padding: 14px 12px; }
            .page-header h2 { font-size: 1rem; }
            .tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                white-space: nowrap;
            }
            .tab { padding: 10px 13px; font-size: 0.75rem; }
            th, td { padding: 10px 12px; }
        }
    </style>

    <!-- ── Delete Confirmation Modal ── -->
    <style>
        .delete-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .delete-modal-overlay.is-open { display: flex; }
        .delete-modal {
            background: #fff;
            border-radius: 14px;
            padding: 32px 28px 24px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.18);
            text-align: center;
            animation: deleteModalIn 0.18s ease;
        }
        @keyframes deleteModalIn {
            from { transform: scale(0.92); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }
        .delete-modal-icon {
            width: 56px; height: 56px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.5rem;
            color: #991b1b;
        }
        .delete-modal h3 {
            margin: 0 0 8px;
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
        }
        .delete-modal p {
            margin: 0 0 24px;
            font-size: 0.85rem;
            color: #6b7280;
            line-height: 1.55;
        }
        .delete-modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .delete-modal-cancel {
            flex: 1;
            padding: 10px 18px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .delete-modal-cancel:hover { background: #f3f4f6; }
        .delete-modal-confirm {
            flex: 1;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            background: #991b1b;
            color: #fff;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
        }
        .delete-modal-confirm:hover { background: #7f1d1d; }
    </style>

<?php $notif_path='../'; include '../notif_assets.php'; ?>
</head>
<body>

<!-- ── Delete Confirmation Modal ── -->
<div class="delete-modal-overlay" id="deleteModalOverlay" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="delete-modal">
        <div class="delete-modal-icon"><i class="fa fa-trash-can"></i></div>
        <h3 id="deleteModalTitle">Delete Schedule</h3>
        <p>Sigurado ka bang gusto mong idelete ang schedule na ito?<br>Hindi na ito mababawi.</p>
        <div class="delete-modal-actions">
            <button class="delete-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="delete-modal-confirm" id="deleteModalConfirm">Delete</button>
        </div>
    </div>
</div>

<?php 
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin') {
        include 'super_admin_sidebar.php';
    } else {
        include 'admin_sidebar.php'; 
    }
?>

<div id="main">
    <div class="container">
        
        <div class="page-header">
            <h2>Schedule Management</h2>
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="btn-add" onclick="document.getElementById('addModal').style.display='block'">+ Add New Batch Schedule</button>
                <?php include '../notif_bell.php'; ?>
            </div>
        </div>

        <?php if(!empty($message)) echo "<div class='success-msg'>$message</div>"; ?>
        <?php if (!$schedulePatientLinkReady): ?>
            <div class="success-msg" style="color:#92400e;background:#fffbeb;border-left-color:#f59e0b;">
                Run <code>database_maternal_schedule_links.sql</code> in phpMyAdmin to enable reliable patient links for schedules.
            </div>
        <?php endif; ?>

    <div class="tabs">
        <button class="tab active" onclick="openTab(event, 'childTab')">Child Schedule</button>
        <button class="tab" onclick="openTab(event, 'maternalTab')">Maternal Schedule</button>
        <button class="tab" onclick="openTab(event, 'reschedTab')">Reschedule Requests<?php if ($resched_total > 0): ?> (<?= $resched_total ?>)<?php endif; ?></button>
        <button class="tab" onclick="openTab(event, 'completedTab')">Completed Appointments</button>
        <?php if (($_SESSION['role'] ?? '') === 'Super Admin'): ?>
            <button class="tab" onclick="openTab(event, 'scheduleChangeApprovalsTab')">
                Admin Edit Approvals<?php if ($result_schedule_change_approvals->num_rows > 0): ?> (<?= (int) $result_schedule_change_approvals->num_rows ?>)<?php endif; ?>
            </button>
        <?php endif; ?>
    </div>

        <!-- Child Schedule Tab Content -->
        <div id="childTab" class="tab-content active">
            <?php renderScheduleSection('Immunization', 'Child', $childImmunizationSchedules); ?>
            <?php renderScheduleSection('Check-up', 'Child', $childCheckupSchedules); ?>
        </div>

        <!-- Maternal Schedule Tab Content -->
        <div id="maternalTab" class="tab-content">
            <?php renderScheduleSection('Immunization', 'Maternal', $maternalImmunizationSchedules); ?>
            <?php renderScheduleSection('Check-up', 'Maternal', $maternalCheckupSchedules); ?>
        </div>

        <!-- Reschedule Requests Tab Content -->
        <div id="reschedTab" class="tab-content">

            <?php
            // Helper to render one reschedule requests table
            function renderReschedTable($result, $label) { ?>
            <section class="schedule-section">
                <h3><?= htmlspecialchars($label) ?> Reschedule Requests
                    (<?= $result ? $result->num_rows : 0 ?>)
                </h3>
                <div class="table-scroll">
                <table class="reschedule-requests-table">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Service / Check-up</th>
                            <th>Current Schedule</th>
                            <th>Requested Date &amp; Time</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['patient_name'] ?? '') ?></strong><br><small>Request #<?= (int) $row['request_id'] ?></small></td>
                                    <td><?= htmlspecialchars($row['service_type'] ?? '') ?></td>
                                    <td><?= date('M d, Y', strtotime($row['schedule_date'])) ?><br><small><?= date('h:i A', strtotime($row['schedule_time'])) ?></small></td>
                                    <td><?= date('M d, Y', strtotime($row['requested_date'])) ?><br><small><?= date('h:i A', strtotime($row['requested_time'])) ?></small></td>
                                    <td><?= nl2br(htmlspecialchars($row['reason'] ?? 'No reason provided')) ?></td>
                                    <td><span class="badge badge-resched">Resched Requested</span></td>
                                    <td>
                                        <button class="btn-add" style="padding: 4px 8px; font-size: 0.75rem;"
                                            onclick='openReschedModal(
                                                <?= (int) $row["request_id"] ?>,
                                                <?= json_encode($row["patient_name"] ?? "", JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                                                <?= json_encode($row["requested_date"], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                                                <?= json_encode(substr($row["requested_time"], 0, 5), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                                                <?= json_encode($row["reason"] ?? "", JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>
                                            )'>Review</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center;">No pending <?= strtolower(htmlspecialchars($label)) ?> reschedule requests.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </section>
            <?php } ?>

            <?php renderReschedTable($result_reschedule_maternal, 'Maternal'); ?>
            <?php renderReschedTable($result_reschedule_child,    'Child'); ?>

        </div>

        <!-- Completed Appointments Tab Content -->
        <div id="completedTab" class="tab-content">
            <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Category</th>
                        <th>Service / Check-up</th>
                        <th>Date & Time Completed</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_completed && $result_completed->num_rows > 0): ?>
                        <?php while($row = $result_completed->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['patient_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['service_type']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['schedule_date'])) ?><br><small><?= date('h:i A', strtotime($row['schedule_time'])) ?></small></td>
                                <td><span class="badge badge-completed"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><small><?= htmlspecialchars($row['notes'] ?? 'None') ?></small></td>
                                <td>
                                    <div style="display: flex; gap: 5px; align-items: center;">
                                        <a href="#" style="color: var(--sage-green); font-weight: bold; text-decoration: none;" 
                                           onclick="openEditModal(
                                               '<?= $row['id'] ?>', 
                                               '<?= htmlspecialchars($row['patient_name'], ENT_QUOTES) ?>', 
                                               '<?= $row['category'] ?>', 
                                               '<?= htmlspecialchars($row['service_type'], ENT_QUOTES) ?>', 
                                               '<?= $row['schedule_date'] ?>', 
                                               '<?= $row['schedule_time'] ?>', 
                                               '<?= $row['status'] ?>',
                                               '<?= htmlspecialchars($row['notes'] ?? '', ENT_QUOTES) ?>'
                                           )">Edit</a>
                                        <span>|</span>
                                        <form method="POST" data-delete-form style="margin:0;">
                                            <input type="hidden" name="schedule_id" value="<?= $row['id'] ?>">
                                            <button type="button" class="btn-delete" onclick="openDeleteModal(this.closest('form'))">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;">No completed appointments records yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <?php if (($_SESSION['role'] ?? '') === 'Super Admin'): ?>
        <div id="scheduleChangeApprovalsTab" class="tab-content">
            <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Requested By</th>
                        <th>Patient</th>
                        <th>Current Schedule</th>
                        <th>Proposed Schedule</th>
                        <th>Service / Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_schedule_change_approvals->num_rows > 0): ?>
                        <?php while ($change_request = $result_schedule_change_approvals->fetch_assoc()): ?>
                            <tr>
                                <td>Admin #<?= (int) $change_request['requested_by'] ?><br><small><?= htmlspecialchars($change_request['created_at'], ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td>
                                    <?= htmlspecialchars($change_request['patient_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <br><small><?= htmlspecialchars($change_request['category'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php if ($change_request['current_patient_name'] !== null && $change_request['current_patient_name'] !== $change_request['patient_name']): ?>
                                        <br><small>Current: <?= htmlspecialchars($change_request['current_patient_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($change_request['current_schedule_date'] !== null): ?>
                                        <?= htmlspecialchars(date('M d, Y', strtotime($change_request['current_schedule_date'])), ENT_QUOTES, 'UTF-8') ?>
                                        <br><small><?= htmlspecialchars(date('h:i A', strtotime($change_request['current_schedule_time'])), ENT_QUOTES, 'UTF-8') ?>
                                        · <?= htmlspecialchars($change_request['current_service_type'], ENT_QUOTES, 'UTF-8') ?>
                                        · <?= htmlspecialchars($change_request['current_status'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php else: ?>
                                        <span>Appointment no longer exists</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(date('M d, Y', strtotime($change_request['schedule_date'])), ENT_QUOTES, 'UTF-8') ?>
                                    <br><small><?= htmlspecialchars(date('h:i A', strtotime($change_request['schedule_time'])), ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($change_request['service_type'], ENT_QUOTES, 'UTF-8') ?>
                                    <br><small><?= htmlspecialchars($change_request['proposed_status'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td><?= nl2br(htmlspecialchars($change_request['notes'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td>
                                <td>
                                    <form method="POST" action="?tab=scheduleChangeApprovalsTab" style="display:flex;flex-direction:column;gap:6px;min-width:110px;">
                                        <input type="hidden" name="schedule_change_csrf" value="<?= htmlspecialchars($scheduleChangeCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="change_request_id" value="<?= (int) $change_request['id'] ?>">
                                        <?php if ($change_request['current_schedule_date'] !== null): ?>
                                            <button type="submit" name="schedule_change_action" value="approve" class="btn-add" style="margin:0;">Approve</button>
                                        <?php endif; ?>
                                        <button type="submit" name="schedule_change_action" value="reject" class="btn-delete">Reject</button>
                                        <input type="hidden" name="review_schedule_change" value="1">
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;">No schedule edits awaiting approval.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Add Batch Schedule Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; text-transform: uppercase; font-size: 1rem;">Add Batch Schedule</h3>
            <span style="cursor: pointer; font-weight: bold;" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Category</label>
                <select name="category" id="batch_category" required onchange="filterPatientsByCategory()">
                    <option value="Child">Child Schedule</option>
                    <option value="Maternal">Maternal Schedule</option>
                </select>
            </div>
            <div class="form-group">
                <label>Schedule Date</label>
                <input type="date" name="schedule_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Schedule Time</label>
                <input type="time" name="schedule_time" value="08:00" required>
            </div>
            <div class="form-group">
                <label>Service / Vaccine Type</label>
                <input type="text" name="service_type" placeholder="e.g. Immunization / Prenatal Check-up" required>
            </div>
            <div class="form-group">
                <label>Select Patients for this Schedule Date & Service</label>
                <div class="patient-checklist-box" id="patientChecklistContainer">
                    <?php if ($result_infants && $result_infants->num_rows > 0): ?>
                        <?php while($p = $result_infants->fetch_assoc()): ?>
                            <div class="patient-checkbox-item patient-item-row" data-category="Child">
                                <input type="checkbox" name="patient_ids[]" value="<?= $p['id'] ?>" id="pat_child_<?= $p['id'] ?>">
                                <label for="pat_child_<?= $p['id'] ?>" style="display:inline; margin:0; text-transform:none; cursor:pointer;"><?= htmlspecialchars($p['full_name']) ?> <small style="color:#666;">(Child)</small></label>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <?php if ($result_maternal_patients && $result_maternal_patients->num_rows > 0): ?>
                        <?php while($p = $result_maternal_patients->fetch_assoc()): ?>
                            <div class="patient-checkbox-item patient-item-row" data-category="Maternal" style="display:none;">
                                <input type="checkbox" name="patient_ids[]" value="<?= $p['id'] ?>" id="pat_mat_<?= $p['id'] ?>">
                                <label for="pat_mat_<?= $p['id'] ?>" style="display:inline; margin:0; text-transform:none; cursor:pointer;"><?= htmlspecialchars($p['full_name'] ?? 'Unnamed Mother') ?> <small style="color:#666;">(Maternal)</small></label>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Notes / Remarks</label>
                <textarea name="notes" rows="2" placeholder="Optional notes..."></textarea>
            </div>
            <button type="submit" name="add_schedule_batch" class="btn-submit">Save Batch Schedule</button>
        </form>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; text-transform: uppercase; font-size: 1rem;">Edit Schedule & Status</h3>
            <span style="cursor: pointer; font-weight: bold;" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
        </div>
        
        <form method="POST">
            <input type="hidden" name="schedule_change_csrf" value="<?= htmlspecialchars($scheduleChangeCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="schedule_id" id="edit_schedule_id">
            <div class="form-group">
                <label>Patient Name</label>
                <input type="text" name="patient_name" id="edit_patient_name" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category" id="edit_category" required>
                    <option value="Child">Child Schedule</option>
                    <option value="Maternal">Maternal Schedule</option>
                </select>
            </div>
            <div class="form-group">
                <label>Service / Check-up</label>
                <input type="text" name="service_type" id="edit_service_type" required>
            </div>
            <div class="form-group">
                <label>Schedule Date</label>
                <input type="date" name="schedule_date" id="edit_schedule_date" required>
            </div>
            <div class="form-group">
                <label>Schedule Time</label>
                <input type="time" name="schedule_time" id="edit_schedule_time" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status" required>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                    <option value="Reschedule Requested">Reschedule Requested</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes / Remarks</label>
                <textarea name="notes" id="edit_notes" rows="2"></textarea>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" name="update_schedule" class="btn-submit" style="margin-top: 0; flex: 2;">Update Schedule</button>
                <button type="button" class="btn-delete" style="flex: 1; padding: 10px; font-size: 0.8rem;" onclick="openDeleteModal(this.closest('form'))">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Reschedule Review Modal -->
<div id="reschedModal" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;text-transform:uppercase;font-size:1rem;">Review Reschedule Request</h3>
            <span style="cursor:pointer;font-weight:bold;font-size:1.3rem;" onclick="document.getElementById('reschedModal').style.display='none'">&times;</span>
        </div>

        <form method="POST">
            <input type="hidden" name="reschedule_request_id" id="resched_request_id">

            <!-- Read-only info -->
            <div class="form-group">
                <label>Patient Name</label>
                <input type="text" id="resched_patient_name" readonly style="background:#f3f4f6;">
            </div>

            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:14px 16px;margin-bottom:14px;">
                <p style="margin:0 0 8px;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Patient's Requested Schedule</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.72rem;">Date</label>
                        <input type="date" id="resched_proposed_date" readonly style="background:#fff;">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.72rem;">Time</label>
                        <input type="time" id="resched_proposed_time" readonly style="background:#fff;">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Reason from Patient</label>
                <textarea id="resched_reason" rows="2" readonly style="background:#f3f4f6;resize:none;"></textarea>
            </div>

            <!-- Worker override section -->
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:14px 16px;margin-bottom:14px;">
                <p style="margin:0 0 8px;font-size:0.75rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.04em;">Worker Override <span style="font-weight:400;text-transform:none;color:#b45309;">(optional — fill if you want a different date)</span></p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.72rem;">Override Date</label>
                        <input type="date" name="override_date" id="resched_override_date">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.72rem;">Override Time</label>
                        <input type="time" name="override_time" id="resched_override_time">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Worker Note <span style="font-weight:400;color:#6b7280;">(optional)</span></label>
                <textarea name="worker_note" rows="2" placeholder="Add a note for the patient (e.g. please bring your health card)..." style="resize:vertical;"></textarea>
            </div>

            <div class="form-group">
                <label>Action</label>
                <select name="reschedule_action" id="resched_action" required onchange="toggleOverride(this.value)">
                    <option value="approve">Approve Reschedule</option>
                    <option value="reject">Reject Reschedule</option>
                </select>
            </div>

            <button type="submit" name="handle_reschedule" class="btn-submit">Process Request</button>
        </form>
    </div>
</div>

<script>
    function openTab(evt, tabName) {
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => content.classList.remove('active'));
        
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        document.getElementById(tabName).classList.add('active');
        if (evt && evt.currentTarget) evt.currentTarget.classList.add('active');
    }

    function openTabFromUrl(tabName) {
        var targetTab = document.getElementById(tabName);
        var targetBtn = document.querySelector('[onclick*="' + tabName + '"]');
        if (!targetTab) return;

        document.querySelectorAll('.tab-content').forEach(function(content) { content.classList.remove('active'); });
        document.querySelectorAll('.tab').forEach(function(button) { button.classList.remove('active'); });
        targetTab.classList.add('active');
        if (targetBtn) targetBtn.classList.add('active');
    }

    function filterPatientsByCategory() {
        const selectedCategory = document.getElementById('batch_category').value;
        const items = document.querySelectorAll('.patient-item-row');
        
        items.forEach(item => {
            const cat = item.getAttribute('data-category');
            if (cat === selectedCategory) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
                const checkbox = item.querySelector('input[type="checkbox"]');
                if(checkbox) checkbox.checked = false;
            }
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        filterPatientsByCategory();

        const main = document.getElementById('main');
        const sidebar = document.getElementById('mainSidebar') || document.getElementById('mySidenav');
        if (main && sidebar) {
            const syncMainWidth = () => {
                const sidebarClosed = sidebar.classList.contains('is-hidden')
                    || sidebar.getBoundingClientRect().width === 0;
                document.body.classList.toggle('sidebar-collapsed', sidebarClosed);
            };
            const sidebarObserver = new MutationObserver(syncMainWidth);
            sidebarObserver.observe(sidebar, { attributes: true, attributeFilter: ['class', 'style'] });
            syncMainWidth();
        }

        // Auto-open tab if ?tab= is in the URL (e.g. from notification redirect)
        var urlParams = new URLSearchParams(window.location.search);
        var tabParam  = urlParams.get('tab');
        if (tabParam) {
            openTabFromUrl(tabParam);
        }
    });

    function openEditModal(id, patientName, category, serviceType, scheduleDate, scheduleTime, status, notes) {
        document.getElementById('edit_schedule_id').value = id;
        document.getElementById('edit_patient_name').value = patientName;
        document.getElementById('edit_category').value = category;
        document.getElementById('edit_service_type').value = serviceType;
        document.getElementById('edit_schedule_date').value = scheduleDate;
        document.getElementById('edit_schedule_time').value = scheduleTime;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_notes').value = notes;
        
        document.getElementById('editModal').style.display = 'block';
    }

    function openReschedModal(requestId, patientName, scheduleDate, scheduleTime, reason) {
        document.getElementById('resched_request_id').value   = requestId;
        document.getElementById('resched_patient_name').value = patientName;
        document.getElementById('resched_proposed_date').value = scheduleDate;
        document.getElementById('resched_proposed_time').value = scheduleTime;
        document.getElementById('resched_reason').value       = reason;
        // Clear override fields and worker note each time modal opens
        document.getElementById('resched_override_date').value = '';
        document.getElementById('resched_override_time').value = scheduleTime; // default to same time
        document.querySelector('[name="worker_note"]').value    = '';
        document.getElementById('resched_action').value        = 'approve';
        toggleOverride('approve');
        document.getElementById('reschedModal').style.display = 'block';
    }

    function toggleOverride(action) {
        // Hide the override / worker-note section when rejecting (still present but less prominent)
        const overrideBox = document.getElementById('resched_override_date')
            ?.closest('[style*="background:#fffbeb"]');
        if (overrideBox) {
            overrideBox.style.opacity = action === 'reject' ? '0.4' : '1';
            overrideBox.style.pointerEvents = action === 'reject' ? 'none' : '';
        }
    }
</script>

<?php include 'footer.php'; ?>
<?php $notif_path='../'; include '../notif_js.php'; ?>

<script>
(function () {
    let _pendingForm = null;

    window.openDeleteModal = function (form) {
        _pendingForm = form;
        document.getElementById('deleteModalOverlay').classList.add('is-open');
        document.getElementById('deleteModalConfirm').focus();
    };

    window.closeDeleteModal = function () {
        _pendingForm = null;
        document.getElementById('deleteModalOverlay').classList.remove('is-open');
    };

    document.getElementById('deleteModalConfirm').addEventListener('click', function () {
        if (!_pendingForm) return;

        // Ensure delete_schedule hidden input exists
        if (!_pendingForm.querySelector('[name="delete_schedule"]')) {
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = 'delete_schedule'; h.value = '1';
            _pendingForm.appendChild(h);
        }

        // Detect which tab this form lives in and send it back as return_tab
        if (!_pendingForm.querySelector('[name="return_tab"]')) {
            const tabContent = _pendingForm.closest('.tab-content');
            const tabId = tabContent ? tabContent.id : 'childTab';
            const rt = document.createElement('input');
            rt.type = 'hidden'; rt.name = 'return_tab'; rt.value = tabId;
            _pendingForm.appendChild(rt);
        }

        _pendingForm.submit();
    });

    // Close on overlay click
    document.getElementById('deleteModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeDeleteModal();
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
})();
</script>
</body>
</html>