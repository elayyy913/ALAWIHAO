<?php
session_start();
include '../db_connect.php';

// Check if logged in and if admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    header("Location: login.php");
    exit();
}

$message = "";

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
    } else {
        $success_count = 0;
        $sql = "INSERT INTO schedules (category, patient_name, schedule_date, schedule_time, service_type, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            foreach ($patient_ids as $pid) {
                $patient_name = "";
                if ($category === 'Child') {
                    $p_query = "SELECT child_name AS full_name FROM children WHERE id = ? LIMIT 1";
                } else {
                    $p_query = "SELECT CONCAT(client_fname, ' ', client_lname) AS full_name FROM maternal_registration WHERE id = ? LIMIT 1";
                }

                if ($p_stmt = $conn->prepare($p_query)) {
                    $p_stmt->bind_param("i", $pid);
                    $p_stmt->execute();
                    $p_res = $p_stmt->get_result();
                    if ($p_row = $p_res->fetch_assoc()) {
                        $patient_name = $p_row['full_name'];
                        
                        $stmt->bind_param("sssssss", $category, $patient_name, $schedule_date, $schedule_time, $service_type, $notes, $status);
                        if ($stmt->execute()) {
                            $success_count++;
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

// Handle Update Schedule / Status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_schedule'])) {
    $id = intval($_POST['schedule_id']);
    $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $schedule_date = mysqli_real_escape_string($conn, $_POST['schedule_date']);
    $schedule_time = mysqli_real_escape_string($conn, $_POST['schedule_time']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql = "UPDATE schedules SET patient_name=?, category=?, schedule_date=?, schedule_time=?, service_type=?, notes=?, status=? WHERE id=?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssssssi", $patient_name, $category, $schedule_date, $schedule_time, $service_type, $notes, $status, $id);
        if ($stmt->execute()) {
            $message = "Schedule updated successfully!";
        } else {
            $message = "Error updating: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle Reschedule Confirmation (Approve / Reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['handle_reschedule'])) {
    $id = intval($_POST['schedule_id']);
    $action = $_POST['reschedule_action']; 

    if ($action == 'approve') {
        $new_date = $_POST['proposed_date'];
        $new_time = $_POST['proposed_time'];
        
        $get_orig = $conn->prepare("SELECT schedule_date, notes FROM schedules WHERE id = ?");
        $get_orig->bind_param("i", $id);
        $get_orig->execute();
        $res_orig = $get_orig->get_result();
        $orig_row = $res_orig->fetch_assoc();
        $old_date = $orig_row['schedule_date'] ?? '';
        $current_notes = $orig_row['notes'] ?? '';
        $get_orig->close();

        if (strpos($current_notes, 'Rescheduled (Orig:') === false) {
            $updated_notes = trim($current_notes . ' | Rescheduled (Orig: ' . $old_date . ')');
        } else {
            $updated_notes = $current_notes;
        }

        // I-update ang schedule date/time, gawing 'Approved' ang status, at i-save ang bagong notes
        $sql = "UPDATE schedules SET schedule_date=?, schedule_time=?, status='Approved', notes=? WHERE id=?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $new_date, $new_time, $updated_notes, $id);
            $stmt->execute();
            $stmt->close();
            $message = "Reschedule request approved successfully!";
        }
    } else {
        $sql = "UPDATE schedules SET status='Pending', notes = CONCAT(COALESCE(notes, ''), ' | Reschedule Rejected') WHERE id=?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $message = "Reschedule request rejected.";
        }
    }
}

// Handle Reschedule Request Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_reschedule'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    $new_time = mysqli_real_escape_string($conn, $_POST['new_time']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    $update_query = "UPDATE schedules 
                     SET status = 'Reschedule Requested', 
                         notes = CONCAT(COALESCE(notes, ''), ' | Request: ', ?, ' ', ?, ' | Reason: ', ?)
                     WHERE id = ?";

    if ($stmt = $conn->prepare($update_query)) {
        $stmt->bind_param("sssi", $new_date, $new_time, $reason, $schedule_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert success'><i class='fa fa-check-circle'></i> Tagumpay na naipadala ang iyong request para sa pagbabago ng iskedyul!</div>";
        } else {
            $message = "<div class='alert error'><i class='fa fa-triangle-exclamation'></i> Nabigo ang pag-request: " . $conn->error . "</div>";
        }
        $stmt->close();
    }
}
// SECURED QUERIES 
$result_child = $conn->query("SELECT * FROM schedules WHERE LOWER(category) = 'child' AND (status = 'Pending' OR status = 'Approved') ORDER BY schedule_date ASC, schedule_time ASC");
$result_maternal = $conn->query("SELECT * FROM schedules WHERE LOWER(category) = 'maternal' AND (status = 'Pending' OR status = 'Approved') ORDER BY schedule_date ASC, schedule_time ASC");
$result_reschedule = $conn->query("SELECT * FROM schedules WHERE status = 'Reschedule Requested' ORDER BY schedule_date ASC");
$result_completed = $conn->query("SELECT * FROM schedules WHERE status = 'Completed' ORDER BY schedule_date DESC, schedule_time DESC");

$result_infants = $conn->query("SELECT id, child_name AS full_name, 'Child' AS category FROM children ORDER BY child_name ASC");
$result_maternal_patients = $conn->query("SELECT id, CONCAT(client_fname, ' ', client_lname) AS full_name, 'Maternal' AS category FROM maternal_registration ORDER BY client_lname ASC");
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
            padding: 20px 30px;
            box-sizing: border-box;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
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
        
        <div class="page-header">
            <h2>Schedule Management</h2>
            <button class="btn-add" onclick="document.getElementById('addModal').style.display='block'">+ Add New Batch Schedule</button>
        </div>

        <?php if(!empty($message)) echo "<div class='success-msg'>$message</div>"; ?>

    <div class="tabs">
        <button class="tab active" onclick="openTab(event, 'childTab')">Child Schedule</button>
        <button class="tab" onclick="openTab(event, 'maternalTab')">Maternal Schedule</button>
        <button class="tab" onclick="openTab(event, 'reschedTab')">Reschedule Requests</button>
        <button class="tab" onclick="openTab(event, 'completedTab')">Completed Appointments</button>
    </div>

        <!-- Child Schedule Tab Content -->
        <div id="childTab" class="tab-content active">
            <table>
                <thead>
                    <tr>
                        <th>Patient Name (Child)</th>
                        <th>Service / Check-up</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_child && $result_child->num_rows > 0): ?>
                        <?php while($row = $result_child->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['patient_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['service_type']) ?></td>
                                <td>
                                    <?= date('M d, Y', strtotime($row['schedule_date'])) ?><br><small><?= date('h:i A', strtotime($row['schedule_time'])) ?></small><br>
                                    <small style="color: gray; font-size: 11px;">
                                        <?php 
                                            if (preg_match('/Rescheduled \(Orig: ([^)]+)\)/', $row['notes'], $matches)) {
                                                echo 'Orig: ' . date('M d, Y', strtotime($matches[1]));
                                            }
                                        ?>
                                    </small>
                                </td>
                                <td><span class="badge badge-active"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><small><?= htmlspecialchars($row['notes'] ?? 'None') ?></small></td>
                                <td>
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
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No pending child schedules found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Maternal Schedule Tab Content -->
        <div id="maternalTab" class="tab-content">
            <table>
                <thead>
                    <tr>
                        <th>Patient Name (Maternal)</th>
                        <th>Service / Check-up</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_maternal && $result_maternal->num_rows > 0): ?>
                        <?php while($row = $result_maternal->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['patient_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['service_type']) ?></td>
                                <td>
                                    <?= date('M d, Y', strtotime($row['schedule_date'])) ?><br><small><?= date('h:i A', strtotime($row['schedule_time'])) ?></small><br>
                                    <small style="color: gray; font-size: 11px;">
                                        <?php 
                                            if (preg_match('/Rescheduled \(Orig: ([^)]+)\)/', $row['notes'], $matches)) {
                                                echo 'Orig: ' . date('M d, Y', strtotime($matches[1]));
                                            }
                                        ?>
                                    </small>
                                </td>
                                <td><span class="badge badge-active"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><small><?= htmlspecialchars($row['notes'] ?? 'None') ?></small></td>
                                <td>
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
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No pending maternal schedules found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Reschedule Requests Tab Content -->
        <div id="reschedTab" class="tab-content">
            <table>
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Service / Check-up</th>
                        <th>Current Schedule</th>
                        <th>Requested Details</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_reschedule && $result_reschedule->num_rows > 0): ?>
                        <?php while($row = $result_reschedule->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['patient_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['service_type']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['schedule_date'])) ?> <br><small><?= date('h:i A', strtotime($row['schedule_time'])) ?></small></td>
                                <td><span style="color: #92400e; font-weight: bold;"><?= htmlspecialchars($row['notes']) ?></span></td>
                                <td><span class="badge badge-resched">Resched Requested</span></td>
                                <td>
                                    <button class="btn-add" style="padding: 4px 8px; font-size: 0.75rem;" 
                                        onclick="openReschedModal(
                                            '<?= $row['id'] ?>', 
                                            '<?= htmlspecialchars($row['patient_name'], ENT_QUOTES) ?>', 
                                            '<?= $row['schedule_date'] ?>', 
                                            '<?= $row['schedule_time'] ?>'
                                        )">Review</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No pending reschedule requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Completed Appointments Tab Content -->
        <div id="completedTab" class="tab-content">
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
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                    <option value="Reschedule Requested">Reschedule Requested</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes / Remarks</label>
                <textarea name="notes" id="edit_notes" rows="2"></textarea>
            </div>
            <button type="submit" name="update_schedule" class="btn-submit">Update Schedule</button>
        </form>
    </div>
</div>

<!-- Reschedule Review Modal -->
<div id="reschedModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; text-transform: uppercase; font-size: 1rem;">Confirm Reschedule Request</h3>
            <span style="cursor: pointer; font-weight: bold;" onclick="document.getElementById('reschedModal').style.display='none'">&times;</span>
        </div>
        
        <form method="POST">
            <input type="hidden" name="schedule_id" id="resched_schedule_id">
            <div class="form-group">
                <label>Patient Name</label>
                <input type="text" id="resched_patient_name" readonly style="background: #f3f4f6;">
            </div>
            <div class="form-group">
                <label>New Proposed Date</label>
                <input type="date" name="proposed_date" id="resched_proposed_date" required>
            </div>
            <div class="form-group">
                <label>New Proposed Time</label>
                <input type="time" name="proposed_time" id="resched_proposed_time" required>
            </div>
            <div class="form-group">
                <label>Action</label>
                <select name="reschedule_action" required>
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
        evt.currentTarget.classList.add('active');
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

    window.addEventListener('DOMContentLoaded', () => {
        filterPatientsByCategory();
    });

    function openReschedModal(id, patientName, scheduleDate, scheduleTime) {
        document.getElementById('resched_schedule_id').value = id;
        document.getElementById('resched_patient_name').value = patientName;
        document.getElementById('resched_proposed_date').value = scheduleDate;
        document.getElementById('resched_proposed_time').value = scheduleTime;
        document.getElementById('reschedModal').style.display = 'block';
    }
</script>

</body>
</html>