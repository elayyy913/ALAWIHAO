<?php
session_start();
include '../db_connect.php';

// Turn off mysqli strict exceptions for error handling
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the current filter date from URL or default to today
$view_date = isset($_GET['view_date']) ? $_GET['view_date'] : date('Y-m-d');

// --- 1. HANDLE NEW SCHEDULE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sched'])) {
    $m_id = $_POST['maternal_id'];
    $type = isset($_POST['appointment_type']) ? mysqli_real_escape_string($conn, $_POST['appointment_type']) : 'Prenatal Checkup';
    $date = $_POST['appointment_date']; 
    $time = $_POST['appointment_time'];

    // Get mother's full name to sync with patient_name column
    $p_name = "";
    $get_name = $conn->query("SELECT full_name FROM maternal_registrations WHERE id = '$m_id'");
    if ($get_name && $row_n = $get_name->fetch_assoc()) {
        $p_name = $row_n['full_name'];
    }

    $sql = "INSERT INTO maternal_schedules (maternal_id, patient_name, appointment_type, appointment_date, appointment_time, status) 
            VALUES (?, ?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        // Fallback query if patient_name column does not exist
        $sql = "INSERT INTO maternal_schedules (maternal_id, appointment_type, appointment_date, appointment_time, status) 
                VALUES (?, ?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $m_id, $type, $date, $time);
    } else {
        $stmt->bind_param("issss", $m_id, $p_name, $type, $date, $time);
    }

    $stmt->execute();
    header("Location: admin_sched_maternal.php?view_date=$date");
    exit();
}

// --- 2. HANDLE EDIT ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_sched'])) {
    $s_id = $_POST['sched_id'];
    $type = mysqli_real_escape_string($conn, $_POST['appointment_type']);
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];

    $sql = "UPDATE maternal_schedules SET appointment_type = ?, appointment_date = ?, appointment_time = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $type, $date, $time, $s_id);
    $stmt->execute();
    header("Location: admin_sched_maternal.php?view_date=$view_date");
    exit();
}

// --- 3. HANDLE RESCHEDULE STATUS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resched_action'])) {
    $s_id = $_POST['sched_id'];
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];

    $sql = "UPDATE maternal_schedules SET appointment_date = ?, appointment_time = ?, status = 'Rescheduled' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $new_date, $new_time, $s_id);
    $stmt->execute();
    header("Location: admin_sched_maternal.php?view_date=$new_date");
    exit();
}

// --- 4. HANDLE REMOVE ---
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM maternal_schedules WHERE id=$id");
    header("Location: admin_sched_maternal.php?view_date=$view_date");
    exit();
}

// --- 5. HANDLE DONE ---
if (isset($_GET['done_id'])) {
    $id = (int)$_GET['done_id'];
    $conn->query("UPDATE maternal_schedules SET status='Completed' WHERE id=$id");
    header("Location: admin_sched_maternal.php?view_date=$view_date");
    exit();
}

// --- 6. FETCH DATA FOR LISTS ---
$mothers = $conn->query("SELECT id, full_name FROM maternal_registrations ORDER BY full_name ASC");

// Pending List
$pending_res = $conn->query("SELECT s.*, m.full_name FROM maternal_schedules s 
    JOIN maternal_registrations m ON s.maternal_id = m.id 
    WHERE s.appointment_date = '$view_date' AND s.status = 'Pending' 
    ORDER BY s.appointment_time ASC");

// Rescheduled List
$resched_res = $conn->query("SELECT s.*, m.full_name FROM maternal_schedules s 
    JOIN maternal_registrations m ON s.maternal_id = m.id 
    WHERE s.appointment_date = '$view_date' AND s.status = 'Rescheduled' 
    ORDER BY s.appointment_time ASC");

// Completed List
$completed_res = $conn->query("SELECT s.*, m.full_name FROM maternal_schedules s 
    JOIN maternal_registrations m ON s.maternal_id = m.id 
    WHERE s.appointment_date = '$view_date' AND s.status = 'Completed' 
    ORDER BY s.appointment_time ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maternal Operations | Alawihao Health</title>
    <style>
        :root { --sage: #718355; --bg: #f5f5dc; --tan: #d4a373; --white: #ffffff; --dark: #4a5d3f; }
        body { background-color: var(--bg); font-family: 'Times New Roman', serif; margin: 0; }
        #main { margin-left: 260px; padding: 40px; }
        
        .dashboard-container { display: grid; grid-template-columns: 1fr 360px; gap: 30px; align-items: start; }

        .todo-section { background: rgba(255,255,255,0.6); padding: 30px; border-radius: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .patient-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: white; border-radius: 12px; margin-bottom: 12px; }

        .activity-pad { background: var(--dark); color: white; padding: 25px; border-radius: 20px; position: sticky; top: 40px; }
        .pad-box { background: rgba(255,255,255,0.08); border: 1px dashed rgba(255,255,255,0.3); padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .pad-box h4 { margin: 0 0 10px 0; font-size: 0.8rem; color: rgba(255,255,255,0.6); text-transform: uppercase; }

        .mini-entry { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem; }
        .action-links { display: flex; gap: 10px; }
        .btn-edit-small { color: #d1ffbd; cursor: pointer; text-decoration: none; font-size: 0.75rem; }
        .btn-del-small { color: #ffbaba; cursor: pointer; text-decoration: none; font-size: 0.75rem; }

        .btn-add { background: var(--sage); color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: bold; cursor: pointer; }
        .btn-done { background: var(--sage); color: white; padding: 6px 12px; border-radius: 5px; text-decoration: none; font-size: 0.8rem; font-weight: bold; }
        .btn-resched { background: var(--tan); color: white; padding: 6px 12px; border: none; border-radius: 5px; font-size: 0.8rem; cursor: pointer; margin-left: 5px; font-weight: bold; }

        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); }
        .modal-content { background: white; margin: 8% auto; padding: 35px; width: 420px; border-radius: 20px; color: #333; position: relative; }
        .close-btn { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #888; }
        input, select { width: 100%; height: 45px; margin: 10px 0; border: 1px solid #ddd; border-radius: 10px; padding: 0 10px; box-sizing: border-box; }
        .btn-save { width: 100%; background: var(--sage); color: white; border: none; height: 50px; border-radius: 10px; font-weight: bold; cursor: pointer; margin-top: 15px; }
    </style>
</head>
<body>

<?php 
    // FIXED FILE EXTENSION TYPO HERE
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'Super Admin') {
        include 'super_admin_sidebar.php';
    } else {
        include 'admin_sidebar.php';
    }
?>

<div id="main">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
        <h1 style="color: var(--dark); margin: 0;">Maternal Operations</h1>
        <div style="background: white; padding: 8px 15px; border-radius: 10px;">
            <label style="font-size: 0.9rem; color: #777;">Date Filter:</label>
            <input type="date" value="<?= $view_date ?>" onchange="location.href='admin_sched_maternal.php?view_date='+this.value">
        </div>
    </div>

    <div class="dashboard-container">
        <div class="todo-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="margin:0; color: #444;">📅 Today's Waiting List</h3>
                <button class="btn-add" onclick="openModal('schedModal')">+ New Entry</button>
            </div>
            
            <?php if($pending_res && $pending_res->num_rows > 0): ?>
                <?php while($row = $pending_res->fetch_assoc()): ?>
                    <div class="patient-item">
                        <div>
                            <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                            <small style="color:#666;"><?= htmlspecialchars($row['appointment_type']) ?> | <?= date('h:i A', strtotime($row['appointment_time'])) ?></small>
                        </div>
                        <div>
                            <a href="admin_sched_maternal.php?done_id=<?= $row['id'] ?>&view_date=<?= $view_date ?>" class="btn-done">Done</a>
                            <button class="btn-resched" onclick="openReschedModal('<?= $row['id'] ?>', '<?= addslashes($row['full_name']) ?>')">Resched</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; color:#aaa; margin-top: 50px;">No pending patients.</p>
            <?php endif; ?>
        </div>

        <div class="activity-pad">
            <h3>Activity Pad</h3>
            <div class="pad-box">
                <h4>🔄 RESCHEDULED</h4>
                <?php if($resched_res): ?>
                    <?php while($row = $resched_res->fetch_assoc()): ?>
                        <div class="mini-entry">
                            <span><?= htmlspecialchars($row['full_name']) ?></span>
                            <div class="action-links">
                                <span class="btn-edit-small" onclick="openEditModal('<?= $row['id'] ?>', '<?= addslashes($row['full_name']) ?>', '<?= $row['appointment_type'] ?>', '<?= $row['appointment_date'] ?>', '<?= $row['appointment_time'] ?>')">Edit</span>
                                <a href="admin_sched_maternal.php?delete_id=<?= $row['id'] ?>&view_date=<?= $view_date ?>" class="btn-del-small" onclick="return confirm('Remove?')">Remove</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <div class="pad-box">
                <h4>✅ COMPLETED</h4>
                <?php if($completed_res): ?>
                    <?php while($row = $completed_res->fetch_assoc()): ?>
                        <div class="mini-entry">
                            <span><?= htmlspecialchars($row['full_name']) ?></span>
                            <div class="action-links">
                                <span class="btn-edit-small" onclick="openEditModal('<?= $row['id'] ?>', '<?= addslashes($row['full_name']) ?>', '<?= $row['appointment_type'] ?>', '<?= $row['appointment_date'] ?>', '<?= $row['appointment_time'] ?>')">Edit</span>
                                <a href="admin_sched_maternal.php?delete_id=<?= $row['id'] ?>&view_date=<?= $view_date ?>" class="btn-del-small" onclick="return confirm('Remove?')">Remove</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- NEW ENTRY MODAL -->
<div id="schedModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('schedModal')">&times;</span>
        <h3>New Entry</h3>
        <form method="POST">
            <input type="hidden" name="add_sched" value="1">
            
            <label>Patient</label>
            <select name="maternal_id" required>
                <option value="">Select Patient</option>
                <?php if($mothers): $mothers->data_seek(0); while($m = $mothers->fetch_assoc()): ?>
                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?></option>
                <?php endwhile; endif; ?>
            </select>

            <!-- ADDED MISSING TYPE FIELD -->
            <label>Type / Purpose</label>
            <select name="appointment_type" required>
                <option value="Prenatal Checkup">Prenatal Checkup</option>
                <option value="Physical Examination">Physical Examination</option>
                <option value="Post-partum">Post-partum</option>
                <option value="Consultation">Consultation</option>
            </select>

            <label>Date</label>
            <input type="date" name="appointment_date" value="<?= $view_date ?>" required>

            <label>Time</label>
            <input type="time" name="appointment_time" value="09:00" required>

            <button type="submit" class="btn-save">Save Schedule</button>
        </form>
    </div>
</div>

<!-- RESCHEDULE MODAL -->
<div id="reschedModal" class="modal">
    <div class="modal-content" style="border-top: 8px solid var(--tan);">
        <span class="close-btn" onclick="closeModal('reschedModal')">&times;</span>
        <h3>Reschedule Patient</h3>
        <p id="resName" style="font-weight:bold;"></p>
        <form method="POST">
            <input type="hidden" name="resched_action" value="1">
            <input type="hidden" name="sched_id" id="resId">
            <label>New Date</label><input type="date" name="new_date" required>
            <label>New Time</label><input type="time" name="new_time" required>
            <button type="submit" class="btn-save" style="background:var(--tan);">Confirm Reschedule</button>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content" style="border-top: 8px solid #666;">
        <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
        <h3>Edit Details</h3>
        <p id="editNameDisplay" style="font-weight:bold;"></p>
        <form method="POST">
            <input type="hidden" name="edit_sched" value="1">
            <input type="hidden" name="sched_id" id="editId">
            <label>Type</label>
            <select name="appointment_type" id="editType">
                <option value="Prenatal Checkup">Prenatal Checkup</option>
                <option value="Physical Examination">Physical Examination</option>
                <option value="Post-partum">Post-partum</option>
                <option value="Consultation">Consultation</option>
            </select>
            <label>Date</label><input type="date" name="appointment_date" id="editDate" required>
            <label>Time</label><input type="time" name="appointment_time" id="editTime" required>
            <button type="submit" class="btn-save" style="background:#666;">Update Record</button>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).style.display = "block"; }
    function closeModal(id) { document.getElementById(id).style.display = "none"; }
    
    function openReschedModal(id, name) {
        document.getElementById('resId').value = id;
        document.getElementById('resName').innerText = "Rescheduling: " + name;
        openModal('reschedModal');
    }

    function openEditModal(id, name, type, date, time) {
        document.getElementById('editId').value = id;
        document.getElementById('editNameDisplay').innerText = "Editing: " + name;
        document.getElementById('editType').value = type;
        document.getElementById('editDate').value = date;
        document.getElementById('editTime').value = time;
        openModal('editModal');
    }

    window.onclick = function(e) { if(e.target.className == 'modal') closeModal(e.target.id); }
</script>
</body>
</html>