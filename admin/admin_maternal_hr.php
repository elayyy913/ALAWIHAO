<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. DYNAMIC SIDEBAR LOGIC
$u_id = $_SESSION['user_id'];
$get_user = mysqli_query($conn, "SELECT role FROM users WHERE id = '$u_id'");
$user_info = mysqli_fetch_assoc($get_user);
$current_role = strtolower(trim($user_info['role'] ?? ''));

$sidebar_file = (in_array($current_role, ['super admin', 'superadmin'])) ? 'super_admin_sidebar.php' : 'admin_sidebar.php';

// 2. FETCH MATERNAL PATIENTS GALING SA REGISTRATION AT RECORDS
$sql = "SELECT r.id as mother_id, r.client_fname as first_name, r.client_lname as last_name, 
        CONCAT(r.client_fname, ' ', r.client_lname) as full_name,
        r.client_mi, r.age, r.dob, r.contact, r.blood_type, r.street, r.barangay,
        r.municipality, r.spouse_fname, r.spouse_lname, r.lmp,
        (SELECT COUNT(*) FROM maternal_records m2 WHERE m2.mother_id = r.id) as checkup_count,
        (SELECT MAX(checkup_date) FROM maternal_records m2 WHERE m2.mother_id = r.id) as last_visit,
        (SELECT MAX(gestational_age_weeks) FROM maternal_records m2 WHERE m2.mother_id = r.id) as current_aog
        FROM maternal_registration r
        WHERE r.status = 'Approved'
        ORDER BY r.client_fname ASC";
$result = mysqli_query($conn, $sql);
$maternalHistoryResult = $conn->query("SELECT * FROM maternal_records ORDER BY checkup_date DESC, id DESC");
$maternalHistoryLoadError = $maternalHistoryResult ? null : $conn->error;
$maternalHistoryMap = [];
if ($maternalHistoryResult) {
    while ($historyRow = $maternalHistoryResult->fetch_assoc()) {
        $motherId = (int) $historyRow['mother_id'];
        $maternalHistoryMap[$motherId][] = $historyRow;
    }
}

$schedulePatientColumn = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'patient_id'");
$recordScheduleColumn = mysqli_query($conn, "SHOW COLUMNS FROM maternal_records LIKE 'schedule_id'");
$recordVaccineColumn = mysqli_query($conn, "SHOW COLUMNS FROM maternal_records LIKE 'vaccine_given'");
$vaccineFieldReady = $recordVaccineColumn && mysqli_num_rows($recordVaccineColumn) > 0;
$maternalVaccineResult = $conn->query("SELECT id, vaccine_name, available_stock FROM vaccines WHERE category = 'Maternal' ORDER BY vaccine_name ASC");
$maternalVaccineLoadError = $maternalVaccineResult ? null : $conn->error;
$maternalVaccineCount = $maternalVaccineResult ? $maternalVaccineResult->num_rows : 0;
$scheduleLinkSchemaReady = $schedulePatientColumn && mysqli_num_rows($schedulePatientColumn) > 0
    && $recordScheduleColumn && mysqli_num_rows($recordScheduleColumn) > 0;
$scheduledResult = false;
$scheduleLoadError = null;
$openScheduledPatient = null;
$openScheduleNotice = null;
$immunizationAppointments = [];
$checkupAppointments = [];

if ($scheduleLinkSchemaReady) {
    $scheduledStmt = $conn->prepare("SELECT s.id AS schedule_id, s.schedule_date, s.schedule_time,
            s.service_type, s.status, r.id AS mother_id,
            CONCAT(r.client_fname, ' ', r.client_lname) AS full_name
        FROM schedules s
        INNER JOIN maternal_registration r ON r.id = s.patient_id
        WHERE s.category = 'Maternal' AND r.status = 'Approved'
          AND s.status IN ('Pending', 'Approved', 'Rescheduled')
        ORDER BY s.schedule_date ASC, s.schedule_time ASC");
    if (!$scheduledStmt) {
        $scheduleLoadError = $conn->error;
    } elseif ($scheduledStmt->execute()) {
        $scheduledResult = $scheduledStmt->get_result();
        while ($schedule = $scheduledResult->fetch_assoc()) {
            if (preg_match('/immuniz|vaccin/i', $schedule['service_type'] ?? '')) {
                $immunizationAppointments[] = $schedule;
            } else {
                $checkupAppointments[] = $schedule;
            }
        }
    } else {
        $scheduleLoadError = $scheduledStmt->error;
    }

    $requestedPatientId = (int) ($_GET['patient_id'] ?? 0);
    $requestedScheduleId = (int) ($_GET['schedule_id'] ?? 0);
    if ($requestedPatientId > 0 && $requestedScheduleId > 0) {
        $openStmt = $conn->prepare("SELECT s.id AS schedule_id, s.schedule_date,
                r.id AS mother_id, CONCAT(r.client_fname, ' ', r.client_lname) AS full_name
            FROM schedules s
            INNER JOIN maternal_registration r ON r.id = s.patient_id
            WHERE s.id = ? AND s.patient_id = ? AND s.category = 'Maternal'
              AND r.status = 'Approved'
              AND s.status IN ('Pending', 'Approved', 'Rescheduled')
            LIMIT 1");
        if (!$openStmt) {
            $openScheduleNotice = 'Could not verify the selected appointment: ' . $conn->error;
        } else {
            $openStmt->bind_param("ii", $requestedScheduleId, $requestedPatientId);
            if ($openStmt->execute()) {
                $openScheduledPatient = $openStmt->get_result()->fetch_assoc() ?: null;
            } else {
                $openScheduleNotice = 'Could not verify the selected appointment: ' . $openStmt->error;
            }
            $openStmt->close();
            if (!$openScheduledPatient && $openScheduleNotice === null) {
                $openScheduleNotice = 'That appointment is no longer active for this patient. Choose an active appointment or add a walk-in record from All Maternal Patients.';
            }
        }
    }
}

$recordNotice = $_SESSION['maternal_record_notice'] ?? null;
unset($_SESSION['maternal_record_notice']);
if (!$recordNotice && $openScheduleNotice !== null) {
    $recordNotice = ['type' => 'error', 'message' => $openScheduleNotice];
}
if (!$recordNotice && $maternalVaccineLoadError !== null) {
    $recordNotice = ['type' => 'error', 'message' => 'Could not load maternal vaccine inventory: ' . $maternalVaccineLoadError];
}
if (!$recordNotice && $maternalHistoryLoadError !== null) {
    $recordNotice = ['type' => 'error', 'message' => 'Could not load maternal health history: ' . $maternalHistoryLoadError];
}
if (!$recordNotice && isset($_GET['success'])) {
    $recordNotice = ['type' => 'success', 'message' => 'Matagumpay na na-save ang maternal health record.'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maternal Health Records | Alawihao Center</title>
    <style>
        :root { 
            --sage: #8DAE74; 
            --dark-sage: #6B8E55; 
            --soft-sage: #F1F5ED; 
            --sidebar-width: 280px; 
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            background: #F8FAFC; 
            display: flex; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        #main-wrapper { 
            flex: 1 1 0;
            min-width: 0;
            padding: clamp(16px, 3vw, 30px);
            margin-left: var(--sidebar-width); 
            transition: var(--transition); 
            width: auto;
            box-sizing: border-box;
        }

        .page-header {
            display: flex; 
            justify-content: space-between;
            align-items: center; 
            min-width: 0;
            margin-bottom: 30px;
            background: white;
            padding: clamp(16px, 2vw, 20px);
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border: 1px solid #edf2f7;
        }

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
        .scheduled-panels {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
            gap: 20px;
            margin-bottom: 20px;
        }
        .patient-panel h3 {
            margin: 0 0 16px;
            color: var(--dark-sage);
            font-size: 1rem;
        }
        .scheduled-table { min-width: 470px; }
        .scheduled-table th, .scheduled-table td { padding: 12px 10px; }
        .scheduled-date { white-space: nowrap; }
        .btn-scheduled-record {
            display: inline-block;
            padding: 8px 10px;
            border-radius: 7px;
            background: var(--sage);
            color: white;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
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
        th { 
            text-align: left; 
            padding: 15px; 
            background: var(--soft-sage); 
            color: var(--dark-sage); 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
        }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; }

        .btn-group { display: flex; gap: 10px; }
        .btn-history { 
            background: var(--soft-sage); 
            color: var(--dark-sage); 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.8rem; 
            border: none;
            cursor: pointer;
        }
        .btn-update { 
            background: var(--sage); 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 0.8rem;
        }
        .btn-update:hover { background: var(--dark-sage); }

        .role-badge { background: #FEFCBF; color: #744210; padding: 6px 14px; border-radius: 8px; font-size: 11px; font-weight: 700; }
        
        /* Modal Styling */
        .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter: blur(3px); overflow-y: auto; }
        .modal-content { background:white; margin:3% auto; padding:clamp(16px, 4vw, 30px); width:min(750px, calc(100% - 32px)); border-radius:15px; border-top:8px solid var(--sage); box-shadow: 0 25px 30px rgba(0,0,0,0.15); max-height: 85vh; display: flex; flex-direction: column; box-sizing: border-box; }
        .maternal-report-modal { display:none; position:fixed; z-index:10000; inset:0; padding:20px; background:rgba(0,0,0,.42); align-items:center; justify-content:center; overflow-y:auto; }
        .maternal-report-card { width:min(700px, 100%); max-height:90vh; overflow-y:auto; padding:20px; border-radius:12px; border-top:6px solid var(--sage); background:#fff; box-shadow:0 20px 35px rgba(0,0,0,.2); }
        .maternal-report-header { margin-bottom:14px; padding-bottom:10px; text-align:center; border-bottom:2px solid var(--sage); }
        .maternal-report-header h2 { margin:0; color:var(--dark-sage); font-size:1.15rem; }
        .maternal-report-header p { margin:3px 0 0; color:#666; font-size:.78rem; }
        .maternal-report-patient { display:grid; grid-template-columns:1fr 1fr; gap:8px 14px; margin-bottom:16px; padding:10px; border-radius:7px; background:var(--soft-sage); font-size:.8rem; }
        .maternal-report-section { margin:0 0 7px; color:var(--dark-sage); font-size:.76rem; text-transform:uppercase; }
        .maternal-report-table { width:100%; border-collapse:collapse; font-size:.76rem; }
        .maternal-report-table th, .maternal-report-table td { padding:7px 6px; border:1px solid #cbd5e0; text-align:left; vertical-align:top; }
        .maternal-report-table th { background:#f0f4ec; color:var(--dark-sage); font-size:.68rem; }
        .maternal-report-empty { padding:14px !important; color:#718096; font-style:italic; text-align:center !important; }
        .maternal-report-actions { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-top:12px; }
        .maternal-report-actions-group { display:flex; flex-wrap:wrap; gap:7px; }
        .maternal-report-actions button, .maternal-report-actions a { display:inline-block; padding:7px 11px; border:0; border-radius:5px; color:#fff; font-size:.75rem; font-weight:600; text-decoration:none; cursor:pointer; }
        .maternal-word-button { background:#4267ad; }
        .maternal-print-button { background:#4a5568; }
        .maternal-full-history { background:#718096; }
        .maternal-close-button { background:#e2e8f0; color:#2d3748 !important; }
        @media print {
            body * { visibility:hidden !important; }
            .maternal-report-modal { display:block !important; position:absolute; inset:0; padding:0; overflow:visible; background:#fff; }
            .maternal-report-card { width:100%; max-height:none; overflow:visible; padding:0; border:0; border-radius:0; box-shadow:none; }
            #maternalPrintable, #maternalPrintable * { visibility:visible !important; }
            #maternalPrintable { position:absolute; inset:0; width:100%; padding:20px; background:#fff; }
            .maternal-report-actions { display:none !important; }
        }
        
        .trimester-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tri-btn { flex: 1; padding: 10px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; color: #4A5568; text-align: center; transition: all 0.2s; }
        .tri-btn.active { background: var(--sage); color: white; border-color: var(--dark-sage); box-shadow: 0 2px 6px rgba(141, 174, 116, 0.4); }

        input[type="text"], input[type="number"], input[type="date"], select, textarea { 
            width: 100%; padding: 10px 12px; margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-family: inherit; font-size: 0.9rem; 
        }
        textarea { resize: vertical; height: 75px; }
        label { font-size: 0.85rem; font-weight: 600; color: #4A5568; margin-bottom: 5px; display: block; }
        .section-box { background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #edf2f7; }
        .checkbox-group { display: flex; flex-direction: column; gap: 8px; margin-top: 5px; }
        .checkbox-label { display: flex; align-items: center; gap: 8px; font-weight: normal; font-size: 0.88rem; color: #2D3748; cursor: pointer; margin-bottom: 0; }
        .checkbox-label input { width: auto; margin-bottom: 0; cursor: pointer; }
        
        .trimester-section { display: none; }
        .trimester-section.active-section { display: block; }

        @media (max-width: 520px) {
            .page-header { margin: 0 0 16px; }
            .page-header h2 { font-size: 1.05rem; line-height: 1.4; }
            .trimester-tabs { gap: 6px; }
            .tri-btn { padding: 9px 5px; font-size: 0.72rem; }
        }
        @media (max-width: 1100px) {
            .scheduled-panels { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
</head>
<body>

    <?php include ($sidebar_file); ?>
    <style>
        @media (max-width: 900px) {
            #main-wrapper {
                width: 100% !important;
                margin-left: 0 !important;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .open-sidebar-btn {
                display: block;
            }
        }
    </style>
    <div id="main-wrapper" class="main-content">
        <div class="page-header">
            <h2 style="color: var(--dark-sage); margin: 0;">Maternal Health History & ANC Records</h2>
        </div>

        <?php if ($recordNotice): ?>
            <div class="record-notice <?php echo ($recordNotice['type'] ?? '') === 'error' ? 'error' : ''; ?>" role="status">
                <?php echo htmlspecialchars($recordNotice['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if (!$scheduleLinkSchemaReady): ?>
            <div class="record-notice error" role="alert">
                Run <code>database_maternal_schedule_links.sql</code> in phpMyAdmin to enable schedule-to-patient linking.
            </div>
        <?php endif; ?>

        <?php if (!$vaccineFieldReady): ?>
            <div class="record-notice error" role="alert">
                Run <code>database_maternal_vaccine_field.sql</code> in phpMyAdmin to save vaccine names in maternal health records.
            </div>
        <?php endif; ?>

        <?php if ($scheduleLinkSchemaReady && $scheduleLoadError !== null): ?>
            <div class="record-notice error" role="alert">
                Could not load scheduled maternal patients: <?php echo htmlspecialchars($scheduleLoadError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <section class="table-container patient-panel" style="margin-bottom:20px;">
            <h3>All Maternal Patients</h3>
            <table>
                <thead>
                    <tr>
                        <th>Mother Name</th>
                        <th>Current Gestational Age</th>
                        <th>Last Visit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)):
                            $patientId = (int) $row['mother_id'];
                            $aog = $row['current_aog'] ?? 0;
                        ?>
                            <tr>
                                <td>
                                    <span style="font-weight: 600; color: #2D3748;"><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></span><br>
                                    <small style="color: #A0AEC0;"><?php echo (int) ($row['checkup_count'] ?? 0); ?> total check-ups</small>
                                </td>
                                <td><span style="font-weight: 600; color: #2b6cb0;"><?php echo $aog ? (int) $aog . ' Weeks' : 'Not specified'; ?></span></td>
                                <td style="color: #4A5568;">
                                    <?php echo ($row['last_visit']) ? date("M d, Y", strtotime($row['last_visit'])) : '<span style="color:#CBD5E0">No records</span>'; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn-history maternal-view-history"
                                            data-maternal-report="<?php echo htmlspecialchars(json_encode([
                                                'patient' => $row,
                                                'history' => $maternalHistoryMap[$patientId] ?? []
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>">
                                            View History
                                        </button>
                                        <button type="button" class="btn-update" onclick='openUpdateModal(<?php echo $patientId; ?>, <?php echo json_encode($row['full_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'>Add Record</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: #A0AEC0; padding: 30px;">No verified maternal records found.</td></tr>
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
                            <?php foreach ($appointments as $schedule): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($schedule['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                        <span class="scheduled-date"><?php echo date('M d, Y', strtotime($schedule['schedule_date'])); ?></span>
                                        <small><?php echo date('g:i A', strtotime($schedule['schedule_time'])); ?></small><br>
                                        <small><?php echo htmlspecialchars($schedule['service_type'], ENT_QUOTES, 'UTF-8'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($schedule['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a class="btn-scheduled-record" href="admin_maternal_hr.php?patient_id=<?php echo (int) $schedule['mother_id']; ?>&amp;schedule_id=<?php echo (int) $schedule['schedule_id']; ?>">
                                            Add Health Record
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; color:#A0AEC0; padding:24px;">No <?php echo strtolower($appointmentTitle); ?> found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="maternal-report-modal" id="maternalHistoryModal" role="dialog" aria-modal="true" aria-labelledby="maternalReportTitle">
        <div class="maternal-report-card">
            <div id="maternalPrintable">
                <header class="maternal-report-header">
                    <h2>Alawihao Health Center</h2>
                    <p>Maternal Health Record Report Card</p>
                </header>
                <div class="maternal-report-patient" id="maternalReportPatient"></div>
                <h3 class="maternal-report-section" id="maternalReportTitle">Maternal Check-up History</h3>
                <table class="maternal-report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Trimester / AOG</th>
                            <th>Wt / BP</th>
                            <th>Vaccine</th>
                            <th>Next Visit</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="maternalReportRows"></tbody>
                </table>
            </div>
            <div class="maternal-report-actions">
                <div class="maternal-report-actions-group">
                    <button type="button" class="maternal-word-button" onclick="downloadMaternalReportWord()">Download as Word</button>
                    <button type="button" class="maternal-print-button" onclick="window.print()">Print / PDF</button>
                    <a class="maternal-full-history" id="maternalFullHistoryLink" href="#">Full History</a>
                </div>
                <button type="button" class="maternal-close-button" onclick="closeMaternalHistoryModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- MATERNAL RECORD FORM MODAL -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 id="modalTitle" style="color:var(--dark-sage); margin:0;">Add Maternal Record</h3>
                <button type="button" onclick="closeModal()" style="background:none; border:none; font-size:18px; cursor:pointer; color:#A0AEC0;">✕</button>
            </div>

            <div class="trimester-tabs">
                <button type="button" class="tri-btn active" id="btnTri1" onclick="setTrimester(1)">1st Trimester</button>
                <button type="button" class="tri-btn" id="btnTri2" onclick="setTrimester(2)">2nd Trimester</button>
                <button type="button" class="tri-btn" id="btnTri3" onclick="setTrimester(3)">3rd Trimester</button>
            </div>

            <form id="maternalRecordForm" method="POST" action="admin_save_maternal_rec.php" style="overflow-y: auto; max-height: 60vh; padding-right: 5px;">
                <input type="hidden" name="mother_id" id="modal_mother_id">
                <input type="hidden" name="schedule_id" id="modal_schedule_id" value="">
                <input type="hidden" name="trimester" id="selected_trimester" value="1">

                <div>
                    <label>Petsa:</label>
                    <input type="date" name="checkup_date" id="modal_checkup_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label>Timbang: (kg)</label>
                        <input type="number" name="weight_kg" step="0.1" required>
                    </div>
                    <div>
                        <label>Taas: (cm)</label>
                        <input type="number" name="height_cm" step="0.1">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label>Age of Gestation (weeks):</label>
                        <input type="number" name="gestational_age_weeks" id="aog_input" min="1" max="42" placeholder="e.g. 12" required>
                        <!-- Added Indicator for Gestational Week & Status -->
                        <div id="week_indicator" style="font-size: 0.8rem; font-weight: 600; color: var(--dark-sage); margin-top: -8px; margin-bottom: 10px;"></div>
                    </div>
                    <div>
                        <label>Blood Pressure:</label>
                        <input type="text" name="bp" placeholder="120/80" required>
                    </div>
                </div>

                <div class="section-box">
                    <label style="margin-bottom: 8px;">Nutritional Status:</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label"><input type="radio" name="nutritional_status" value="Normal" required> Normal</label>
                        <label class="checkbox-label"><input type="radio" name="nutritional_status" value="Underweight"> Underweight</label>
                        <label class="checkbox-label"><input type="radio" name="nutritional_status" value="Overweight"> Overweight</label>
                    </div>
                </div>

                <label>Pagsusuri ng kalagayan ng buntis:</label>
                <textarea name="pagsusuri_kalagayan" placeholder="Ilagay ang mga natuklasan sa pagsusuri..."></textarea>

                <label>Mga payong binigay:</label>
                <textarea name="mga_payo" placeholder="Mga payong ibinigay sa pasyente..."></textarea>

                <label>Mga pagbabago sa birthplan:</label>
                <textarea name="birthplan_changes" placeholder="Mga update o pagbabago sa birth plan..."></textarea>

                <label>Pagsusuri ng ngipin:</label>
                <textarea name="pagsusuri_ngipin" placeholder="Resulta o detalye ng pagsusuri ng ngipin..."></textarea>

                <label>Laboratory test done:</label>
                <input type="text" name="lab_test_done" placeholder="Uri ng laboratory test">

                <label>Bakunang ibinigay (kung mayroon):</label>
                <select name="vaccine_id" id="maternalVaccineSelect" onchange="updateMaternalVaccineFields()">
                    <option value="">-- Walang bakuna --</option>
                    <?php if ($maternalVaccineResult): ?>
                        <?php while ($vaccineOption = $maternalVaccineResult->fetch_assoc()): ?>
                            <option value="<?php echo (int) $vaccineOption['id']; ?>" <?php echo (int) $vaccineOption['available_stock'] < 1 ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($vaccineOption['vaccine_name'], ENT_QUOTES, 'UTF-8'); ?>
                                (Stock: <?php echo (int) $vaccineOption['available_stock']; ?>)
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <?php if ($maternalVaccineResult && $maternalVaccineCount === 0): ?>
                    <small style="display:block; margin-top:-8px; margin-bottom:12px; color:#991b1b;">
                        Wala pang Maternal vaccine sa inventory. <a href="vaccine_inventory.php" target="_blank" rel="noopener">Magdagdag ng Maternal vaccine at stock</a> muna.
                    </small>
                <?php endif; ?>
                <label for="maternalVaccineQuantity">Quantity Used (stock units):</label>
                <input type="number" name="vaccine_quantity" id="maternalVaccineQuantity" min="1" value="1" disabled>
                <small style="display:block; margin-top:-8px; margin-bottom:12px;">Awtomatikong mababawas ang quantity kapag na-save ang record.</small>

                <!-- ================= 1ST TRIMESTER SPECIFIC FIELDS ================= -->
                <div id="section_tri1" class="trimester-section active-section">
                    <label>Hemoglobin count:</label>
                    <input type="text" name="hemoglobin_count" placeholder="Hemoglobin count">

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Urinalysis:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="urinalysis" value="Done"> Urinalysis Done</label>
                        </div>
                    </div>

                    <label>Complete Blood Count (CBC):</label>
                    <input type="text" name="cbc" placeholder="Resulta ng CBC">

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">STIs gamit ang syndromic approach:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="sti_syphilis" value="1"> Syphilis</label>
                            <label class="checkbox-label"><input type="checkbox" name="sti_hiv" value="1"> HIV</label>
                            <label class="checkbox-label"><input type="checkbox" name="sti_hepatitis_b" value="1"> Hepatitis B (HBsAg)</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Stool Examination:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="stool_exam" value="Done"> Stool Examination Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Acetic Acid Wash:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="acetic_acid_wash" value="Done"> Acetic Acid Wash Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Tetanus-containing vaccine:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="tetanus_vaccine_done" id="tetanusCheckbox" value="1" onchange="toggleTetanusDate(); updateMaternalVaccineFields()"> Tetanus-containing vaccine given
                            </label>
                        </div>
                        
                        <div id="tetanusDateContainer" style="display: none; margin-top: 10px;">
                            <label style="font-weight:normal; font-size:0.8rem;">Date given:</label>
                            <input type="date" name="tetanus_vaccine_date">
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Treatments (1st Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="treat_syphilis" value="1"> Syphilis</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_arv" value="1"> Antiretroviral (ARV)</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_bacteriuria" value="1"> Bacteriuria</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_anemia" value="1"> Anemia</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Pinag-usapan / Serbisyong binigay (1st Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="srv_alcohol_tobacco" value="1"> Pag-iwas sa alcohol, tabacco, at illegal na droga</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_diet" value="1"> Pagpapayo tungkol sa tamang pagkain</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_safesex" value="1"> Pagpapayo sa safe sex</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_mosquito_net" value="1"> Paggamit ng mga insecticide-treated na kulambo</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_birthplan" value="1"> Birthplan</label>
                        </div>
                    </div>
                </div>

                <!-- ================= 2ND TRIMESTER SPECIFIC FIELDS ================= -->
                <div id="section_tri2" class="trimester-section">
                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Urinalysis:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="urinalysis_tri2" value="Done"> Urinalysis Done</label>
                        </div>
                    </div>

                    <label>Complete Blood Count (CBC):</label>
                    <input type="text" name="cbc_tri2" placeholder="Resulta ng CBC">

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Etiological tests para sa STIs (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="etiological_stis" value="1"> Etiological test done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Pap smear (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="pap_smear" value="1"> Pap Smear Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Gestational diabetes (oral glucose challenge test), (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="gestational_diabetes" value="1"> Oral Glucose Challenge Test Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Bacteriuria (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="bacteriuria_tri2" value="1"> Bacteriuria Test Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Treatments (2nd Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="treat_deworming" value="1"> Deworming</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_arv_tri2" value="1"> Antiretroviral (ARV)</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_bacteriuria_tri2" value="1"> Bacteriuria</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_anemia_tri2" value="1"> Anemia</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Pinag-usapan / Serbisyong binigay (2nd Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="srv_previous_discussion_tri2" value="1"> Pagpapaalala ng nakaraang tinalakay</label>
                        </div>
                    </div>
                </div>

                <!-- ================= 3RD TRIMESTER SPECIFIC FIELDS ================= -->
                <div id="section_tri3" class="trimester-section">
                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Urinalysis:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="urinalysis_tri3" value="Done"> Urinalysis Done</label>
                        </div>
                    </div>

                    <label>Complete Blood Count (CBC):</label>
                    <input type="text" name="cbc_tri3" placeholder="Resulta ng CBC">

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Bacteriuria (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="bacteriuria_tri3" value="1"> Bacteriuria Test Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Blood/RH group (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="blood_rh_group" value="1"> Blood/RH group determined</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Treatments (3rd Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="treat_arv_tri3" value="1"> Antiretroviral (ARV)</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_bacteriuria_tri3" value="1"> Bacteriuria</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_anemia_tri3" value="1"> Anemia</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Pinag-usapan / Serbisyong binigay (3rd Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="srv_previous_discussion_tri3" value="1"> Pagpapaalala ng nakaraang tinalakay</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_postpartum" value="1"> Pagpapayo sa postpartum at postnatal care</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_spacing" value="1"> Pagpapayo sa pag agwat ng anak</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_tetanus_followup" value="1"> Pag follow up ng tetanus-containing vaccine</label>
                        </div>
                    </div>
                </div>

                <!-- COMMON FIELDS FOR ALL TRIMESTERS -->
                <div style="margin-top: 15px;">
                    <label>Petsa ng pagbalik:</label>
                    <input type="date" name="next_visit_date">
                </div>

                <div>
                    <label>Pangalan ng health service provider:</label>
                    <input type="text" name="provider_name" placeholder="Pangalan ng Midwife / Doctor">
                </div>

                <label>Referral sa ospital:</label>
                <input type="text" name="hospital_referral" placeholder="Pangalan ng ospital o dahilan ng referral">

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                    <button type="button" onclick="closeModal()" style="background:none; border:none; color:#A0AEC0; cursor:pointer; font-weight:600;">Cancel</button>
                    <button type="submit" class="btn-update">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let maternalReportFileName = 'Maternal_Health_Record';

    function escapeMaternalReportValue(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        })[character]);
    }

    function openMaternalHistoryModal(reportData) {
        const patient = reportData.patient || {};
        const history = Array.isArray(reportData.history) ? reportData.history : [];
        const fullName = [patient.first_name, patient.client_mi, patient.last_name].filter(Boolean).join(' ').trim() || 'N/A';
        const safeName = fullName.replace(/[<>:"/\\|?*\x00-\x1F]/g, '_').trim().replace(/\s+/g, '_');
        maternalReportFileName = (safeName || 'Maternal') + '_Health_Record';

        document.getElementById('maternalReportPatient').innerHTML = `
            <div><strong>Mother's Name:</strong> ${escapeMaternalReportValue(fullName)}</div>
            <div><strong>Age / Birthdate:</strong> ${escapeMaternalReportValue(patient.age || 'N/A')} yrs old (${escapeMaternalReportValue(patient.dob || 'N/A')})</div>
            <div><strong>Spouse:</strong> ${escapeMaternalReportValue([patient.spouse_fname, patient.spouse_lname].filter(Boolean).join(' ') || 'N/A')}</div>
            <div><strong>Contact:</strong> ${escapeMaternalReportValue(patient.contact || 'N/A')}</div>
            <div><strong>LMP:</strong> ${escapeMaternalReportValue(patient.lmp || 'N/A')}</div>
            <div><strong>Blood Type:</strong> ${escapeMaternalReportValue(patient.blood_type || 'N/A')}</div>
        `;

        const reportRows = history.length
            ? history.map(record => {
                const weeks = Number(record.gestational_age_weeks) || 0;
                const trimester = weeks > 0
                    ? (weeks <= 13 ? '1st Trimester' : (weeks <= 27 ? '2nd Trimester' : '3rd Trimester'))
                    : 'N/A';
                const remarks = [record.remarks, record.impression_diagnosis, record.mga_payo]
                    .filter(Boolean)
                    .join(' — ') || 'None';
                return `
                    <tr>
                        <td>${escapeMaternalReportValue(record.checkup_date || record.created_at || 'N/A')}</td>
                        <td>${trimester}<br>${weeks > 0 ? escapeMaternalReportValue(weeks) + ' weeks' : 'N/A'}</td>
                        <td>${escapeMaternalReportValue(record.weight_kg || 'N/A')} kg<br>${escapeMaternalReportValue(record.bp || 'N/A')}</td>
                        <td>${escapeMaternalReportValue(record.vaccine_given || record.tt_status || 'None')}</td>
                        <td>${escapeMaternalReportValue(record.next_visit_date || 'N/A')}</td>
                        <td>${escapeMaternalReportValue(remarks)}</td>
                    </tr>`;
            }).join('')
            : '<tr><td class="maternal-report-empty" colspan="6">Wala pang naitalang maternal check-up history.</td></tr>';

        document.getElementById('maternalReportRows').innerHTML = reportRows;
        document.getElementById('maternalFullHistoryLink').href = 'admin_maternal_history.php?id=' + encodeURIComponent(patient.mother_id);
        document.getElementById('maternalHistoryModal').style.display = 'flex';
    }

    function closeMaternalHistoryModal() {
        document.getElementById('maternalHistoryModal').style.display = 'none';
    }

    function downloadMaternalReportWord() {
        const report = document.getElementById('maternalPrintable');
        if (!report) return;

        const documentHtml = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office"
                  xmlns:w="urn:schemas-microsoft-com:office:word"
                  xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="utf-8">
                <title>Maternal Health Record</title>
                <style>
                    body { font-family: Arial, sans-serif; color: #222; }
                    .maternal-report-header { margin-bottom:14px; padding-bottom:10px; text-align:center; border-bottom:2px solid #8dae74; }
                    .maternal-report-header h2 { margin:0; color:#6b8e55; font-size:16pt; }
                    .maternal-report-header p { margin:3px 0 0; color:#666; font-size:10pt; }
                    .maternal-report-patient { display:table; width:100%; margin-bottom:16px; padding:10px; background:#f1f5ed; font-size:10pt; }
                    .maternal-report-patient div { display:inline-block; width:48%; padding:3px 0; }
                    .maternal-report-section { color:#6b8e55; font-size:10pt; text-transform:uppercase; }
                    .maternal-report-table { width:100%; border-collapse:collapse; font-size:9pt; }
                    .maternal-report-table th, .maternal-report-table td { padding:7px; border:1px solid #cbd5e0; text-align:left; vertical-align:top; }
                    .maternal-report-table th { background:#f0f4ec; color:#6b8e55; }
                </style>
            </head>
            <body>${report.innerHTML}</body>
            </html>`;
        const blob = new Blob(['\ufeff', documentHtml], { type: 'application/msword' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = maternalReportFileName + '.doc';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    document.querySelectorAll('.maternal-view-history').forEach(button => {
        button.addEventListener('click', () => {
            try {
                openMaternalHistoryModal(JSON.parse(button.dataset.maternalReport || '{}'));
            } catch (error) {
                console.error('Unable to open maternal health history report.', error);
            }
        });
    });

    document.getElementById('maternalHistoryModal').addEventListener('click', event => {
        if (event.target.id === 'maternalHistoryModal') closeMaternalHistoryModal();
    });

    function setTrimester(trimesterNum) {
        document.getElementById('selected_trimester').value = trimesterNum;
        
        // Tab buttons UI
        document.getElementById('btnTri1').classList.remove('active');
        document.getElementById('btnTri2').classList.remove('active');
        document.getElementById('btnTri3').classList.remove('active');
        document.getElementById('btnTri' + trimesterNum).classList.add('active');

        // Sections visibility
        document.getElementById('section_tri1').classList.remove('active-section');
        document.getElementById('section_tri2').classList.remove('active-section');
        document.getElementById('section_tri3').classList.remove('active-section');
        document.getElementById('section_tri' + trimesterNum).classList.add('active-section');
    }

    // Auto switch tab and show current week status based on Age of Gestation input
    document.getElementById('aog_input').addEventListener('input', function() {
        let weeks = parseInt(this.value);
        let indicator = document.getElementById('week_indicator');
        
        if (!isNaN(weeks) && weeks > 0) {
            if (weeks >= 1 && weeks <= 13) {
                setTrimester(1);
                indicator.innerText = "📍 Currently at Week " + weeks + " (1st Trimester)";
            } else if (weeks >= 14 && weeks <= 27) {
                setTrimester(2);
                indicator.innerText = "📍 Currently at Week " + weeks + " (2nd Trimester)";
            } else if (weeks >= 28) {
                setTrimester(3);
                indicator.innerText = "📍 Currently at Week " + weeks + " (3rd Trimester)";
            }
        } else {
            indicator.innerText = "";
        }
    });

    function openUpdateModal(id, name, scheduleId = '', scheduleDate = '') {
        document.getElementById('maternalRecordForm').reset();
        setTrimester(1);
        toggleTetanusDate();
        updateMaternalVaccineFields();
        document.getElementById('updateModal').style.display = 'block';
        document.getElementById('modal_mother_id').value = id;
        document.getElementById('modal_schedule_id').value = scheduleId;
        document.getElementById('modalTitle').innerText = scheduleId
            ? "Add Scheduled Health Record for: " + name
            : "Add Maternal Record for: " + name;
        if (scheduleDate) {
            document.getElementById('modal_checkup_date').value = scheduleDate;
        }
    }

    function closeModal() { 
        document.getElementById('updateModal').style.display = 'none'; 
    }

    window.onclick = function(event) { 
        if (event.target == document.getElementById('updateModal')) { 
            closeModal(); 
        } 
    }
    
    function toggleTetanusDate() {
        let checkbox = document.getElementById('tetanusCheckbox');
        let dateContainer = document.getElementById('tetanusDateContainer');
        
        if (checkbox.checked) {
            dateContainer.style.display = 'block';
        } else {
            dateContainer.style.display = 'none';
        }
    }

    function updateMaternalVaccineFields() {
        let vaccineSelect = document.getElementById('maternalVaccineSelect');
        let quantityInput = document.getElementById('maternalVaccineQuantity');
        let tetanusCheckbox = document.getElementById('tetanusCheckbox');
        let vaccineSelected = vaccineSelect.value !== '';

        vaccineSelect.required = tetanusCheckbox.checked;
        quantityInput.disabled = !vaccineSelected;
        quantityInput.required = vaccineSelected;
    }

    <?php if ($openScheduledPatient): ?>
    openUpdateModal(
        <?php echo (int) $openScheduledPatient['mother_id']; ?>,
        <?php echo json_encode($openScheduledPatient['full_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
        <?php echo (int) $openScheduledPatient['schedule_id']; ?>,
        <?php echo json_encode($openScheduledPatient['schedule_date']); ?>
    );
    <?php endif; ?>
    </script>
</body>
</html>