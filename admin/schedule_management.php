<?php
session_start();
include '../db_connect.php';

// Check if logged in and if admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle Add Schedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_schedule'])) {
    $category = mysqli_real_escape_string($conn, $_POST['category']); // 'Child' or 'Maternal'
    $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
    $schedule_date = mysqli_real_escape_string($conn, $_POST['schedule_date']);
    $schedule_time = mysqli_real_escape_string($conn, $_POST['schedule_time']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']); // e.g. Immunization / Prenatal Checkup
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = 'Pending'; // Default status

    if (empty($patient_name) || empty($schedule_date) || empty($schedule_time) || empty($service_type)) {
        $message = "Error: All required fields must be filled out!";
    } else {
        $sql = "INSERT INTO schedules (category, patient_name, schedule_date, schedule_time, service_type, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssss", $category, $patient_name, $schedule_date, $schedule_time, $service_type, $notes, $status);
            if ($stmt->execute()) {
                $message = "Schedule added successfully!";
            } else {
                $message = "Error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Handle Update Schedule / Status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_schedule'])) {
    $id = intval($_POST['schedule_id']);
    $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
    $schedule_date = mysqli_real_escape_string($conn, $_POST['schedule_date']);
    $schedule_time = mysqli_real_escape_string($conn, $_POST['schedule_time']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql = "UPDATE schedules SET patient_name=?, schedule_date=?, schedule_time=?, service_type=?, notes=?, status=? WHERE id=?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssssi", $patient_name, $schedule_date, $schedule_time, $service_type, $notes, $status, $id);
        if ($stmt->execute()) {
            $message = "Schedule updated successfully!";
        } else {
            $message = "Error updating: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch Child Schedules
$sql_child = "SELECT * FROM schedules WHERE category = 'Child' ORDER BY schedule_date ASC, schedule_time ASC";
$result_child = $conn->query($sql_child);

// Fetch Maternal Schedules
$sql_maternal = "SELECT * FROM schedules WHERE category = 'Maternal' ORDER BY schedule_date ASC, schedule_time ASC";
$result_maternal = $conn->query($sql_maternal);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management | Alawihao Health</title>
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

        /* Tabs Styling */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 0.9rem;
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

        /* Table Styling */
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
        .badge-pending { background: #fef9c3; color: #854d0e; }
        .badge-completed { background: #f0fdf4; color: #166534; }
        .badge-cancelled { background: #ffeeef; color: #991b1b; }

        /* Modal Styling */
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
            width: 450px;
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
            <button class="btn-add" onclick="document.getElementById('addModal').style.display='block'">+ Add New Schedule</button>
        </div>

        <?php if(!empty($message)) echo "<div class='success-msg'>$message</div>"; ?>

        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab active" onclick="openTab(event, 'childTab')">Child Schedule</button>
            <button class="tab" onclick="openTab(event, 'maternalTab')">Maternal Schedule</button>
        </div>

        <!-- Child Schedule Tab Content -->
        <div id="childTab" class="tab-content active">
            <table>
                <thead>
                    <tr>
                        <th>Patient Name (Child)</th>
                        <th>Service / Type</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_child && $result_child->num_rows > 0): ?>
                        <?php while($row = $result_child->fetch_assoc()): 
                            $status_class = 'badge-pending';
                            if($row['status'] == 'Completed') $status_class = 'badge-completed';
                            if($row['status'] == 'Cancelled') $status_class = 'badge-cancelled';
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['patient_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['service_type']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['schedule_date'])) ?><br><small><?= date('h:i A', strtotime($row['schedule_time'])) ?></small></td>
                                <td><span class="badge <?= $status_class ?>"><?= $row['status'] ?></span></td>
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
                        <tr><td colspan="6" style="text-align:center;">No child schedules found.</td></tr>
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
                        <th>Service / Type</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_maternal && $result_maternal->num_rows > 0): ?>
                        <?php while($row = $result_maternal->fetch_assoc()): 
                            $status_class = 'badge-pending';
                            if($row['status'] == 'Completed') $status_class = 'badge-completed';
                            if($row['status'] == 'Cancelled') $status_class = 'badge-cancelled';
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['patient_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['service_type']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['schedule_date'])) ?><br><small><?= date('h:i A', strtotime($row['schedule_time'])) ?></small></td>
                                <td><span class="badge <?= $status_class ?>"><?= $row['status'] ?></span></td>
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
                        <tr><td colspan="6" style="text-align:center;">No maternal schedules found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Add Schedule Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; text-transform: uppercase; font-size: 1rem;">Add Schedule</h3>
            <span style="cursor: pointer; font-weight: bold;" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Category</label>
                <select name="category" required>
                    <option value="Child">Child Schedule</option>
                    <option value="Maternal">Maternal Schedule</option>
                </select>
            </div>
            <div class="form-group">
                <label>Patient Name</label>
                <input type="text" name="patient_name" placeholder="Full name of patient" required>
            </div>
            <div class="form-group">
                <label>Service / Type</label>
                <input type="text" name="service_type" placeholder="e.g. Immunization / Prenatal Checkup" required>
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
                <label>Notes / Remarks</label>
                <textarea name="notes" rows="2" placeholder="Optional notes..."></textarea>
            </div>
            <button type="submit" name="add_schedule" class="btn-submit">Save Schedule</button>
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
            <input type="hidden" name="category" id="edit_category_hidden">
            <div class="form-group">
                <label>Patient Name</label>
                <input type="text" name="patient_name" id="edit_patient_name" required>
            </div>
            <div class="form-group">
                <label>Service / Type</label>
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

<script>
    function openTab(evt, tabName) {
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => content.classList.remove('active'));
        
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        document.getElementById(tabName).classList.add('active');
        evt.currentTarget.classList.add('active');
    }

    function openEditModal(id, patientName, category, serviceType, scheduleDate, scheduleTime, status, notes) {
        document.getElementById('edit_schedule_id').value = id;
        document.getElementById('edit_patient_name').value = patientName;
        document.getElementById('edit_category_hidden').value = category;
        document.getElementById('edit_service_type').value = serviceType;
        document.getElementById('edit_schedule_date').value = scheduleDate;
        document.getElementById('edit_schedule_time').value = scheduleTime;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_notes').value = notes;
        
        document.getElementById('editModal').style.display = 'block';
    }
</script>

</body>
</html>