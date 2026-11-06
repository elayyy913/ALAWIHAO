<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../db_connect.php';

// Security: 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$role_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
if (!$role_stmt) {
    die("Hindi ma-verify ang admin role.");
}
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$query_role = $role_stmt->get_result();
$user_data = mysqli_fetch_assoc($query_role);
$role_stmt->close();
$user_role = $user_data['role']; 

// Choose ng page based on role
if ($user_role == 'Super Admin') {
    $sidebar_to_include = 'super_admin_sidebar.php';
    $role_label = "Super Admin";
} else {
    $sidebar_to_include = 'admin_sidebar.php';
    $role_label = "Admin";
}

$show_success_modal = false;
$record_error = '';
$success_message = 'Matagumpay na na-save ang health record.';

// Update Health Record (Isinasave na ngayon sa infant_records table)
if (isset($_POST['update_health'])) {
    $c_id = (int) ($_POST['child_id'] ?? 0);
    $vaccine_id = (int) ($_POST['vaccine'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $dose = trim($_POST['dose'] ?? '');
    $weight = (float) ($_POST['weight'] ?? 0);
    $height = (float) ($_POST['height'] ?? 0);
    $v_date = trim($_POST['vaccine_date'] ?? '');
    $next_date = trim($_POST['next_checkup'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $administered_by = trim($_POST['administered_by'] ?? '');
    $hw_id = (int) $_SESSION['user_id'];

    if ($c_id < 1 || $vaccine_id < 1 || $quantity < 1 || $dose === '') {
        $record_error = 'Pumili ng bakuna at dose, at maglagay ng quantity na hindi bababa sa 1.';
    } elseif (!$conn->begin_transaction()) {
        $record_error = 'Hindi masimulan ang ligtas na pag-save ng health record: ' . $conn->error;
    } else {
        $vaccine_stmt = $conn->prepare("SELECT vaccine_name, available_stock FROM vaccines WHERE id = ? AND category = 'Baby' FOR UPDATE");
        if (!$vaccine_stmt) {
            $record_error = 'Hindi ma-validate ang vaccine inventory: ' . $conn->error;
        } else {
            $vaccine_stmt->bind_param("i", $vaccine_id);
            if (!$vaccine_stmt->execute()) {
                $record_error = 'Hindi ma-validate ang vaccine inventory: ' . $vaccine_stmt->error;
            } else {
                $vaccine = $vaccine_stmt->get_result()->fetch_assoc();
                if (!$vaccine) {
                    $record_error = 'Hindi makita ang napiling baby vaccine sa inventory.';
                } elseif ($quantity > (int) $vaccine['available_stock']) {
                    $record_error = 'Kulang ang stock. Available lamang ang ' . (int) $vaccine['available_stock'] . ' unit(s).';
                }
            }
            $vaccine_stmt->close();
        }

        if ($record_error === '') {
            $known_vaccine_names = [
                'bcg' => 'BCG Vaccine',
                'hepatitis b' => 'Hepatitis B Vaccine',
                'pentavalent' => 'Pentavalent Vaccine (DPT-Hep B-HIB)',
                'oral polio vaccine (opv)' => 'Oral Polio Vaccine (OPV)',
                'inactivated polio vaccine (ipv)' => 'Inactivated Polio Vaccine (IPV)',
                'pneumococcal conjugate vaccine (pcv)' => 'Pneumococcal Conjugate Vaccine (PCV)',
                'measles, mumps, rubella (mmr)' => 'Measles, Mumps, Rubella Vaccine (MMR)'
            ];
            $inventory_name = trim($vaccine['vaccine_name']);
            $record_vaccine_name = $known_vaccine_names[strtolower($inventory_name)] ?? $inventory_name;
            $vaccine_taken = $record_vaccine_name . ' - ' . $dose;

            $record_stmt = $conn->prepare("INSERT INTO infant_records
                (child_id, weight_kg, height, vaccine_taken, vaccine_date, next_checkup, remarks, administered_by, health_worker_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if (!$record_stmt) {
                $record_error = 'Hindi maihanda ang health record: ' . $conn->error;
            } else {
                $record_stmt->bind_param("iddsssssi", $c_id, $weight, $height, $vaccine_taken, $v_date, $next_date, $remarks, $administered_by, $hw_id);
                if (!$record_stmt->execute()) {
                    $record_error = 'Hindi na-save ang health record: ' . $record_stmt->error;
                }
                $record_stmt->close();
            }
        }

        if ($record_error === '') {
            $stock_stmt = $conn->prepare("UPDATE vaccines SET available_stock = available_stock - ? WHERE id = ? AND available_stock >= ?");
            if (!$stock_stmt) {
                $record_error = 'Hindi maihanda ang stock deduction: ' . $conn->error;
            } else {
                $stock_stmt->bind_param("iii", $quantity, $vaccine_id, $quantity);
                if (!$stock_stmt->execute() || $stock_stmt->affected_rows !== 1) {
                    $record_error = 'Hindi nabawasan ang stock. I-refresh ang inventory at subukan muli.';
                }
                $stock_stmt->close();
            }
        }

        if ($record_error === '') {
            $remarks_log = 'Administered to child ID ' . $c_id . ' (' . $vaccine_taken . ')';
            $log_stmt = $conn->prepare("INSERT INTO vaccine_logs (vaccine_id, action_type, quantity, remarks, transaction_date, recorded_by)
                VALUES (?, 'OUT', ?, ?, ?, ?)");
            if (!$log_stmt) {
                $record_error = 'Hindi maihanda ang vaccine history: ' . $conn->error;
            } else {
                $log_stmt->bind_param("iisss", $vaccine_id, $quantity, $remarks_log, $v_date, $administered_by);
                if (!$log_stmt->execute()) {
                    $record_error = 'Hindi naitala ang vaccine history: ' . $log_stmt->error;
                }
                $log_stmt->close();
            }
        }

        if ($record_error === '') {
            if ($conn->commit()) {
                $show_success_modal = true;
                $remaining_stock = (int) $vaccine['available_stock'] - $quantity;
                $success_message = $quantity . ' dose ng ' . $inventory_name . ' ang naitala at nabawas sa stock. '
                    . $remaining_stock . ' dose ang natitira.';
            } else {
                $record_error = 'Hindi makumpirma ang health record at stock update: ' . $conn->error;
                $conn->rollback();
            }
        } else {
            $conn->rollback();
        }
    }
}

// Kunin ang approved children at buod ng kanilang mga naitalang health record.
$query = "SELECT c.*,
        (SELECT COUNT(*) FROM infant_records ir WHERE ir.child_id = c.id) AS checkup_count,
        (SELECT MAX(COALESCE(ir.vaccine_date, ir.created_at))
            FROM infant_records ir WHERE ir.child_id = c.id) AS last_visit
    FROM children c
    WHERE c.status = 'Approved'
    ORDER BY c.child_name ASC";
$result = mysqli_query($conn, $query);
$child_load_error = $result ? '' : mysqli_error($conn);

// KUNIN ANG LAHAT NG KASAYSAYAN NG VAKUNA AT CHECK-UP BAWAT BATA MULA SA infant_records
$history_query = mysqli_query($conn, "SELECT * FROM infant_records ORDER BY vaccine_date DESC, created_at DESC");
$baby_vaccine_query = $conn->query("SELECT id, vaccine_name, available_stock FROM vaccines WHERE category = 'Baby' ORDER BY vaccine_name ASC");
$baby_vaccine_load_error = $baby_vaccine_query ? '' : $conn->error;
$baby_vaccine_count = $baby_vaccine_query ? $baby_vaccine_query->num_rows : 0;
$child_history_map = [];
$child_vaccines_map = [];

while ($row_hist = mysqli_fetch_assoc($history_query)) {
    $cid = $row_hist['child_id'];
    $vac = trim($row_hist['vaccine_taken']);
    
    // Para sa history modal / report card pad
    if (!isset($child_history_map[$cid])) {
        $child_history_map[$cid] = [];
    }
    $child_history_map[$cid][] = $row_hist;

    // Para sa pag-filter ng nakuha nang bakuna
    if (!empty($vac)) {
        if (!isset($child_vaccines_map[$cid])) {
            $child_vaccines_map[$cid] = [];
        }
        if (!in_array($vac, $child_vaccines_map[$cid])) {
            $child_vaccines_map[$cid][] = $vac;
        }
    }
}

$schedulePatientColumn = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'patient_id'");
$scheduleCategoryColumn = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'category'");
$scheduleLinkSchemaReady = $schedulePatientColumn && mysqli_num_rows($schedulePatientColumn) > 0
    && $scheduleCategoryColumn && mysqli_num_rows($scheduleCategoryColumn) > 0;
$scheduleLoadError = null;
$immunizationAppointments = [];
$checkupAppointments = [];

if ($scheduleLinkSchemaReady) {
    $scheduledStmt = $conn->prepare("SELECT s.id AS schedule_id, s.schedule_date, s.schedule_time,
            s.service_type, s.status, c.id AS child_id, c.child_name
        FROM schedules s
        INNER JOIN children c ON c.id = s.patient_id
        WHERE s.category = 'Child' AND c.status = 'Approved'
          AND s.status IN ('Pending', 'Approved', 'Rescheduled')
        ORDER BY s.schedule_date ASC, s.schedule_time ASC");
    if (!$scheduledStmt) {
        $scheduleLoadError = $conn->error;
    } elseif (!$scheduledStmt->execute()) {
        $scheduleLoadError = $scheduledStmt->error;
        $scheduledStmt->close();
    } else {
        $scheduledResult = $scheduledStmt->get_result();
        while ($schedule = $scheduledResult->fetch_assoc()) {
            if (preg_match('/immuniz|vaccin/i', $schedule['service_type'] ?? '')) {
                $immunizationAppointments[] = $schedule;
            } else {
                $checkupAppointments[] = $schedule;
            }
        }
        $scheduledStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $role_label; ?> | Child Records List</title>
    <link rel="stylesheet" href="">
    <style>
        :root {
            --sage: #8DAE74;
            --dark-sage: #6B8E55;
            --soft-sage: #F1F5ED;
            --text-main: #2D3748;
            --sidebar-width: 280px;
            --transition: all 0.3s ease-in-out;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAFC;
            margin: 0;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        #main {
            flex: 1 1 0;
            min-width: 0;
            padding: clamp(16px, 3vw, 30px);
            transition: var(--transition);
            box-sizing: border-box;
        }

        .header-section {
            display: flex;
            align-items: center;
            min-width: 0;
            margin-bottom: 20px;
            padding: clamp(16px, 2vw, 20px);
            background: white;
            border: 1px solid #edf2f7;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .header-section h1 { color: var(--dark-sage); margin: 0; font-size: 1.25rem; }
        
        .table-container {
            min-width: 0;
            background: white;
            padding: clamp(12px, 2.5vw, 25px);
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #edf2f7;
            overflow-x: auto;
        }

        table { width: 100%; min-width: 620px; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: var(--soft-sage); color: var(--dark-sage); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 15px; border-bottom: 1px solid #EDF2F7; font-size: 0.9rem; vertical-align: middle; }
        .patient-panel h3 { margin: 0 0 16px; color: var(--dark-sage); font-size: 1rem; }
        .scheduled-panels {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
            gap: 20px;
            margin-top: 20px;
        }
        .scheduled-table { min-width: 470px; }
        .scheduled-table th, .scheduled-table td { padding: 12px 10px; }
        .scheduled-date { white-space: nowrap; }
        .btn-scheduled-record {
            display: inline-block;
            padding: 8px 10px;
            border: 0;
            border-radius: 7px;
            background: var(--sage);
            color: white;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }
        .btn-scheduled-record:hover { background: var(--dark-sage); }
        .record-notice {
            margin-bottom: 18px;
            padding: 12px 16px;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            background: #f0fdf4;
            color: #166534;
        }
        .record-notice.error {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view { background: var(--soft-sage); color: var(--dark-sage); margin-right: 5px; }
        .btn-edit { background: var(--sage); color: white; }
        .btn-group { display: flex; flex-wrap: wrap; gap: 8px; }

        .modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            overflow-y: auto; 
        }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            width: 480px;
            border-radius: 15px;
            border-top: 8px solid var(--sage);
        }

        /* Success Pop-up Modal Styling */
        .success-modal-content {
            background: white;
            margin: 15% auto;
            padding: 30px;
            width: 380px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: modalPopUp 0.3s ease-in-out;
        }

        @keyframes modalPopUp {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        /* Print Styling para maging responsive at malinis kapag na-print o na-save */
        @media print {
            body * {
                visibility: hidden;
            }
            #printableArea, #printableArea * {
                visibility: visible;
            }
            #printableArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 20px;
                background: white;
            }
            .no-print {
                display: none !important;
            }
        }

        @media (max-width: 1100px) {
            .scheduled-panels { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 520px) {
            .header-section { margin-bottom: 16px; }
            .header-section h1 { font-size: 1.05rem; line-height: 1.4; }
        }
    </style>
</head>
<body>

<?php include $sidebar_to_include; ?>

<div id="main">
    <div class="header-section">
        <h1>Child Health History & Immunization Records</h1>
    </div>
    <?php if ($record_error !== ''): ?>
        <div style="margin-bottom:15px; padding:12px 16px; border:1px solid #fecaca; border-radius:8px; background:#fef2f2; color:#991b1b;">
            <?= htmlspecialchars($record_error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if ($baby_vaccine_load_error !== ''): ?>
        <div style="margin-bottom:15px; padding:12px 16px; border:1px solid #fecaca; border-radius:8px; background:#fef2f2; color:#991b1b;">
            Hindi ma-load ang baby vaccine inventory: <?= htmlspecialchars($baby_vaccine_load_error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if ($child_load_error !== ''): ?>
        <div class="record-notice error" role="alert">
            Hindi ma-load ang child records: <?= htmlspecialchars($child_load_error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!$scheduleLinkSchemaReady): ?>
        <div class="record-notice error" role="alert">
            Hindi maipakita ang child appointments dahil kulang ang patient-link columns sa schedules table.
        </div>
    <?php elseif ($scheduleLoadError !== null): ?>
        <div class="record-notice error" role="alert">
            Hindi ma-load ang scheduled child patients: <?= htmlspecialchars($scheduleLoadError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="table-container patient-panel" style="margin-bottom:20px;">
        <h3>All Child Patients</h3>
        <table>
            <thead>
                <tr>
                    <th>Child Name</th>
                    <th>Age</th>
                    <th>Last Visit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <?php 
                        $c_id = (int) $row['id'];
                        $taken_list = isset($child_vaccines_map[$c_id]) ? $child_vaccines_map[$c_id] : [];
                        $history_list = isset($child_history_map[$c_id]) ? $child_history_map[$c_id] : [];
                        $child_age = 'Not specified';
                        if (!empty($row['birth_date'])) {
                            try {
                                $age_diff = (new DateTimeImmutable($row['birth_date']))->diff(new DateTimeImmutable('today'));
                                $child_age = $age_diff->y > 0
                                    ? $age_diff->y . ' year' . ($age_diff->y === 1 ? '' : 's')
                                    : ($age_diff->m > 0
                                        ? $age_diff->m . ' month' . ($age_diff->m === 1 ? '' : 's')
                                        : 'Under 1 month');
                            } catch (Exception $exception) {
                                $child_age = 'Not specified';
                            }
                        }
                    ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($row['child_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <small style="color:#A0AEC0;"><?php echo (int) ($row['checkup_count'] ?? 0); ?> total health records</small>
                    </td>
                    <td><span style="font-weight:600; color:#2b6cb0;"><?php echo htmlspecialchars($child_age, ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td style="color:#4A5568;">
                        <?php echo !empty($row['last_visit']) ? date('M d, Y', strtotime($row['last_visit'])) : '<span style="color:#CBD5E0">No records</span>'; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-view" onclick='openHistoryModal(<?php echo json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>, <?php echo json_encode($history_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'>View History</button>
                            <button type="button" class="btn btn-edit" onclick='openEditModal(<?php echo $c_id; ?>, <?php echo json_encode($row['child_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>, <?php echo json_encode($taken_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'>Add Record</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#A0AEC0; padding:30px;">No approved child records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <div class="scheduled-panels">
        <?php foreach ([
            'Immunization Appointments' => $immunizationAppointments,
            'Check-up Appointments' => $checkupAppointments
        ] as $appointmentTitle => $appointments): ?>
            <section class="table-container patient-panel">
                <h3><?php echo htmlspecialchars($appointmentTitle, ENT_QUOTES, 'UTF-8'); ?> (<?php echo count($appointments); ?>)</h3>
                <table class="scheduled-table">
                    <thead>
                        <tr>
                            <th>Patient & Appointment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($scheduleLoadError !== null): ?>
                            <tr><td colspan="3" style="text-align:center; color:#991b1b; padding:24px;">Could not load scheduled patients.</td></tr>
                        <?php elseif ($appointments): ?>
                            <?php foreach ($appointments as $schedule):
                                $scheduledChildId = (int) $schedule['child_id'];
                                $takenVaccinesJson = json_encode(
                                    $child_vaccines_map[$scheduledChildId] ?? [],
                                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                                );
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($schedule['child_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                        <span class="scheduled-date"><?php echo date('M d, Y', strtotime($schedule['schedule_date'])); ?></span>
                                        <small><?php echo date('g:i A', strtotime($schedule['schedule_time'])); ?></small><br>
                                        <small><?php echo htmlspecialchars($schedule['service_type'], ENT_QUOTES, 'UTF-8'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($schedule['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <button type="button" class="btn-scheduled-record"
                                            data-child-id="<?php echo $scheduledChildId; ?>"
                                            data-child-name="<?php echo htmlspecialchars($schedule['child_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-taken-vaccines="<?php echo htmlspecialchars($takenVaccinesJson ?: '[]', ENT_QUOTES, 'UTF-8'); ?>">
                                            Add Health Record
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; color:#A0AEC0; padding:24px;">No <?php echo strtolower(htmlspecialchars($appointmentTitle, ENT_QUOTES, 'UTF-8')); ?> found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<!-- SUCCESS NOTIFICATION MODAL -->
<div id="successModal" class="modal" style="<?php echo $show_success_modal ? 'display: block;' : ''; ?>">
    <div class="success-modal-content">
        <div style="font-size: 50px; color: #48BB78; margin-bottom: 10px;">✔</div>
        <h3 style="color: var(--dark-sage); margin: 0 0 10px 0;">Success!</h3>
        <p style="color: #4A5568; font-size: 0.95rem; margin-bottom: 20px;"><?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?></p>
        <button onclick="closeSuccessModal()" class="btn btn-edit" style="width: 100%; padding: 10px;">OK</button>
    </div>
</div>

<!-- REPORT CARD / FULL HISTORY MODAL -->
<div id="historyModal" class="modal">
    <div class="modal-content" style="width: 700px; max-width: 90%; max-height: 85vh; overflow-y: auto;">
        
        <div id="printableArea">
            <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid var(--sage); padding-bottom: 10px;">
                <h2 style="color: var(--dark-sage); margin: 0;">Alawihao Health Center</h2>
                <p style="margin: 2px 0; font-size: 0.85rem; color: #666;">Child Immunization & Health Record Report Card</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; background: var(--soft-sage); padding: 12px; border-radius: 8px; font-size: 0.9rem;">
                <div><strong>Child Name:</strong> <span id="rep_name">--</span></div>
                <div><strong>Gender:</strong> <span id="rep_gender">--</span></div>
                <div><strong>Mother's Name:</strong> <span id="rep_mother">--</span></div>
                <div><strong>Birthdate:</strong> <span id="rep_dob">--</span></div>
            </div>

            <h4 style="color: var(--dark-sage); margin-bottom: 8px; text-transform: uppercase; font-size: 0.8rem;">Monthly Check-up & Vaccination History</h4>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-bottom: 20px;" id="historyTable">
                <thead>
                    <tr>
                        <th style="border: 1px solid #cbd5e0; padding: 8px;">Date</th>
                        <th style="border: 1px solid #cbd5e0; padding: 8px;">Wt (kg) / Ht (cm)</th>
                        <th style="border: 1px solid #cbd5e0; padding: 8px;">Vaccine / Dose</th>
                        <th style="border: 1px solid #cbd5e0; padding: 8px;">Next Appt</th>
                        <th style="border: 1px solid #cbd5e0; padding: 8px;">Remarks / Health Worker</th>
                    </tr>
                </thead>
                <tbody id="rep_history_rows">
                </tbody>
            </table>
        </div>

        <div class="no-print" style="text-align: right; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <button onclick="exportToWord()" class="btn" style="background: #2b6cb0; color: white; margin-right: 5px;"> Download as Word</button>
                <button onclick="printReportCard()" class="btn" style="background: #4a5568; color: white;"> Print / PDF</button>
            </div>
            <button onclick="closeHistoryModal()" class="btn" style="background: #e2e8f0; color: #2d3748;">Close</button>
        </div>
    </div>
</div>

<!-- UPDATE HEALTH MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="color: var(--dark-sage); margin-top: 0;">Update Health Data</h3>
        <form method="POST">
            <input type="hidden" name="child_id" id="modal_id">
            
            <div style="background: #F8FAFC; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #E2E8F0;">
                <label style="display:block; font-size:0.75rem; font-weight:700; color: #4A5568; margin-bottom: 5px; text-transform: uppercase;">Previously Taken Vaccines:</label>
                <div id="modal_previous_vaccines" style="font-size: 0.85rem; color: #2D3748; font-style: italic;">
                    No vaccines recorded yet.
                </div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:0.8rem; font-weight:600;">Weight (kg)</label>
                <input type="number" name="weight" step="0.01" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ddd; box-sizing: border-box;">
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:0.8rem; font-weight:600;">Height (cm)</label>
                <input type="number" name="height" step="0.1" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ddd; box-sizing: border-box;">
            </div>

            <div style="display: flex; gap: 10px; margin-bottom:12px;">
                <div style="flex: 2;">
                    <label style="display:block; font-size:0.8rem; font-weight:600;">Vaccine Administered</label>
                    <select name="vaccine" id="vaccineSelect" onchange="updateDoseOptions()" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ddd; box-sizing: border-box; background: white;">
                        <option value="">-- Select Vaccine --</option>
                        <?php if ($baby_vaccine_query): ?>
                            <?php while ($vaccine_option = $baby_vaccine_query->fetch_assoc()): ?>
                                <?php
                                    $child_vaccine_aliases = [
                                        'bcg' => 'BCG Vaccine',
                                        'hepatitis b' => 'Hepatitis B Vaccine',
                                        'pentavalent' => 'Pentavalent Vaccine (DPT-Hep B-HIB)',
                                        'oral polio vaccine (opv)' => 'Oral Polio Vaccine (OPV)',
                                        'inactivated polio vaccine (ipv)' => 'Inactivated Polio Vaccine (IPV)',
                                        'pneumococcal conjugate vaccine (pcv)' => 'Pneumococcal Conjugate Vaccine (PCV)',
                                        'measles, mumps, rubella (mmr)' => 'Measles, Mumps, Rubella Vaccine (MMR)'
                                    ];
                                    $inventory_vaccine_name = trim($vaccine_option['vaccine_name']);
                                    $record_vaccine_name = $child_vaccine_aliases[strtolower($inventory_vaccine_name)] ?? $inventory_vaccine_name;
                                ?>
                                <option value="<?= (int) $vaccine_option['id'] ?>" data-record-name="<?= htmlspecialchars($record_vaccine_name, ENT_QUOTES, 'UTF-8') ?>" <?= (int) $vaccine_option['available_stock'] < 1 ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($vaccine_option['vaccine_name'], ENT_QUOTES, 'UTF-8') ?> (Stock: <?= (int) $vaccine_option['available_stock'] ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <?php if ($baby_vaccine_query && $baby_vaccine_count === 0): ?>
                        <small style="display:block; margin-top:5px; color:#991b1b;">
                            Wala pang Baby vaccine sa inventory. <a href="vaccine_inventory.php" target="_blank" rel="noopener">Magdagdag ng Baby vaccine at stock</a> muna.
                        </small>
                    <?php endif; ?>
                </div>
                <div style="flex: 1;">
                    <label style="display:block; font-size:0.8rem; font-weight:600;">Dose</label>
                    <select name="dose" id="doseSelect" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ddd; box-sizing: border-box; background: white;">
                        <option value="">-- Dose --</option>
                        <option value="Dose 1">Dose 1</option>
                        <option value="Dose 2">Dose 2</option>
                        <option value="Dose 3">Dose 3</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:0.8rem; font-weight:600;">Quantity Used (stock units)</label>
                <input type="number" name="quantity" min="1" value="1" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ddd; box-sizing: border-box;">
                <small>Kapag na-save ang record, awtomatikong ibabawas ito sa stock.</small>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:0.8rem; font-weight:600;">Administered By (Nagturok)</label>
                <input type="text" name="administered_by" placeholder="Enter health worker or midwife name..." required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ddd; box-sizing: border-box;">
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:0.8rem; font-weight:600;">Date of Vaccination</label>
                <input type="date" name="vaccine_date" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ddd; box-sizing: border-box;">
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:0.8rem; font-weight:600;">Next Check-up Schedule</label>
                <input type="date" name="next_checkup" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ddd; box-sizing: border-box;">
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.8rem; font-weight:600;">Remarks / Notes</label>
                <textarea name="remarks" rows="3" placeholder="Optional notes or observations..." style="width:100%; padding:8px; border-radius:5px; border:1px solid #ddd; box-sizing: border-box; resize: vertical;"></textarea>
            </div>

            <div style="text-align:right;">
                <button type="button" onclick="closeModal()" style="padding:9px 15px; border:none; cursor:pointer; background: #e2e8f0; border-radius: 5px; font-weight: 600;">Cancel</button>
                <button type="submit" name="update_health" class="btn btn-edit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentChildTakenVaccines = [];
let currentChildNameForWord = "Child_Record";

function toggleSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    if (sidebar && sidebar.classList.contains('is-hidden')) {
        if (typeof showSidebar === 'function') showSidebar();
    } else {
        if (typeof hideSidebar === 'function') hideSidebar();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('mainSidebar');
    const content = document.getElementById('main');
    const state = localStorage.getItem('sidebarVisible');

    if (sidebar && content && state === 'true') {
        sidebar.classList.add('show');
        content.classList.add('main-content-active');
    }

    document.querySelectorAll('.btn-scheduled-record').forEach(button => {
        button.addEventListener('click', function() {
            let takenVaccines = [];
            try {
                takenVaccines = JSON.parse(this.dataset.takenVaccines || '[]');
            } catch (error) {
                console.error('Unable to load previously recorded vaccines.', error);
            }
            openEditModal(
                Number(this.dataset.childId),
                this.dataset.childName || 'Child',
                takenVaccines
            );
        });
    });
});

// --- SUCCESS MODAL FUNCTION ---
function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
    // Alisin ang POST data para hindi mag-resubmit kapag ni-refresh ang page
    window.history.replaceState({}, document.title, window.location.pathname);
}

// --- REPORT CARD MODAL FUNCTIONS ---
function openHistoryModal(childData, historyList) {
    document.getElementById('historyModal').style.display = 'block';
    
    currentChildNameForWord = childData.child_name || 'Child_Record';

    document.getElementById('rep_name').innerText = childData.child_name || '--';
    document.getElementById('rep_gender').innerText = childData.gender || '--';
    document.getElementById('rep_mother').innerText = childData.mother_name || '--';
    document.getElementById('rep_dob').innerText = childData.birth_date || '--';

    let tbody = document.getElementById('rep_history_rows');
    tbody.innerHTML = '';

    if (historyList && historyList.length > 0) {
        historyList.forEach(item => {
            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="border: 1px solid #cbd5e0; padding: 8px;">${item.vaccine_date || item.created_at || '--'}</td>
                <td style="border: 1px solid #cbd5e0; padding: 8px;">${item.weight_kg || '--'} kg / ${item.height || '--'} cm</td>
                <td style="border: 1px solid #cbd5e0; padding: 8px; font-weight: 600; color: var(--dark-sage);">${item.vaccine_taken || 'None'}</td>
                <td style="border: 1px solid #cbd5e0; padding: 8px;">${item.next_checkup || '--'}</td>
                <td style="border: 1px solid #cbd5e0; padding: 8px; font-size: 0.75rem;">${item.remarks || '--'}<br><em style="color:#718096;">By: ${item.administered_by || 'N/A'}</em></td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = `<tr><td colspan="5" style="border: 1px solid #cbd5e0; text-align: center; padding: 15px; color: #718096; font-style: italic;">No check-up or vaccination history recorded yet.</td></tr>`;
    }
}

function closeHistoryModal() {
    document.getElementById('historyModal').style.display = 'none';
}

function printReportCard() {
    window.print();
}

function exportToWord() {
    let header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
    header += "<head><meta charset='utf-8'><title>Export HTML to Word Document</title></head><body>";
    let footer = "</body></html>";
    let htmlContent = header + document.getElementById('printableArea').innerHTML + footer;

    let blob = new Blob(['\ufeff' + htmlContent], {
        type: 'application/msword'
    });
    
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = currentChildNameForWord.replace(/\s+/g, '_') + '_Health_Record.doc';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// --- UPDATE HEALTH MODAL FUNCTIONS ---
function openEditModal(id, name, takenVaccinesArray) {
    document.getElementById('editModal').style.display = 'block';
    document.getElementById('modal_id').value = id;
    document.getElementById('modalTitle').innerText = "Update: " + name;

    currentChildTakenVaccines = takenVaccinesArray || [];
    let vaccinesContainer = document.getElementById('modal_previous_vaccines');
    let vaccineSelect = document.getElementById('vaccineSelect');

   if (currentChildTakenVaccines.length > 0) {
        let listHtml = '<ul style="margin: 0; padding-left: 18px; font-weight: 600; color: #2B6CB0;">';
        currentChildTakenVaccines.forEach(vac => {
            listHtml += `<li style="margin-bottom: 3px; font-size: 0.85rem;">${vac}</li>`;
        });
        listHtml += '</ul>';
        
        vaccinesContainer.innerHTML = listHtml;
        vaccinesContainer.style.fontStyle = 'normal';
    } else {
        vaccinesContainer.innerHTML = 'No vaccines recorded yet.';
        vaccinesContainer.style.fontStyle = 'italic';
        vaccinesContainer.style.fontWeight = 'normal';
        vaccinesContainer.style.color = '#718096';
    }

    vaccineSelect.value = "";
    document.getElementById('doseSelect').value = "";
    
    for (let i = 0; i < vaccineSelect.options.length; i++) {
        vaccineSelect.options[i].style.display = 'block';
        vaccineSelect.options[i].disabled = false;
    }

    let maxDosesMap = {
        "BCG Vaccine": 1,
        "Hepatitis B Vaccine": 1,
        "Pentavalent Vaccine (DPT-Hep B-HIB)": 3,
        "Oral Polio Vaccine (OPV)": 3,
        "Inactivated Polio Vaccine (IPV)": 2,
        "Pneumococcal Conjugate Vaccine (PCV)": 3,
        "Measles, Mumps, Rubella Vaccine (MMR)": 2
    };

    for (let vacName in maxDosesMap) {
        let maxDose = maxDosesMap[vacName];
        let takenCount = 0;
        
        currentChildTakenVaccines.forEach(item => {
            if (item.startsWith(vacName)) {
                takenCount++;
            }
        });

        if (takenCount >= maxDose) {
            for (let i = 0; i < vaccineSelect.options.length; i++) {
                if (vaccineSelect.options[i].value === vacName) {
                    vaccineSelect.options[i].style.display = 'none';
                    vaccineSelect.options[i].disabled = true;
                }
            }
        }
    }
}

function updateDoseOptions() {
    let vaccineSelect = document.getElementById('vaccineSelect');
    let selectedOption = vaccineSelect.options[vaccineSelect.selectedIndex];
    let selectedVaccine = selectedOption ? selectedOption.dataset.recordName || '' : '';
    let doseSelect = document.getElementById('doseSelect');
    
    doseSelect.value = "";

    for (let i = 0; i < doseSelect.options.length; i++) {
        let opt = doseSelect.options[i];
        if (opt.value === "") continue;

        let combinationString = selectedVaccine + ' - ' + opt.value;
        
        if (currentChildTakenVaccines.includes(combinationString)) {
            opt.style.display = 'none';
            opt.disabled = true;
        } else {
            opt.style.display = 'block';
            opt.disabled = false;
        }
    }
}

function closeModal() { document.getElementById('editModal').style.display = 'none'; }
</script>

</body>
</html>