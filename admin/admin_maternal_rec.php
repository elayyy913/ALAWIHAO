<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Super Admin'], true)) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. DELETE LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo 'Invalid request.';
        exit();
    }

    $id_to_delete = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
    if ($id_to_delete === false || $id_to_delete <= 0) {
        header("Location: admin_maternal_rec.php?msg=invalid");
        exit();
    }

    $patient_stmt = mysqli_prepare($conn, "SELECT client_fname, client_lname FROM maternal_registration WHERE id = ? LIMIT 1");
    if ($patient_stmt) {
        mysqli_stmt_bind_param($patient_stmt, 'i', $id_to_delete);
        mysqli_stmt_execute($patient_stmt);
        $patient_result = mysqli_stmt_get_result($patient_stmt);
        $patient = $patient_result ? mysqli_fetch_assoc($patient_result) : null;
        mysqli_stmt_close($patient_stmt);
    } else {
        $patient = null;
    }

    mysqli_begin_transaction($conn);

    try {
        $delete_records_stmt = mysqli_prepare($conn, "DELETE FROM maternal_records WHERE mother_id = ?");
        if ($delete_records_stmt) {
            mysqli_stmt_bind_param($delete_records_stmt, 'i', $id_to_delete);
            mysqli_stmt_execute($delete_records_stmt);
            mysqli_stmt_close($delete_records_stmt);
        }

        $delete_history_stmt = mysqli_prepare($conn, "DELETE FROM pregnancy_history WHERE patient_id = ?");
        if ($delete_history_stmt) {
            mysqli_stmt_bind_param($delete_history_stmt, 'i', $id_to_delete);
            mysqli_stmt_execute($delete_history_stmt);
            mysqli_stmt_close($delete_history_stmt);
        }

        $delete_registration_stmt = mysqli_prepare($conn, "DELETE FROM maternal_registration WHERE id = ?");
        if ($delete_registration_stmt) {
            mysqli_stmt_bind_param($delete_registration_stmt, 'i', $id_to_delete);
            mysqli_stmt_execute($delete_registration_stmt);
            if (mysqli_affected_rows($conn) === 0) {
                throw new Exception('No patient record deleted.');
            }
            mysqli_stmt_close($delete_registration_stmt);
        }

        mysqli_commit($conn);

        $patientName = trim(($patient['client_fname'] ?? '') . ' ' . ($patient['client_lname'] ?? ''));
        $subject = $patientName !== '' ? " ({$patientName})" : '';
        logAdminAction($conn, "Deleted maternal patient record ID: {$id_to_delete}{$subject}.");
        header("Location: admin_maternal_rec.php?msg=deleted");
        exit();
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        header("Location: admin_maternal_rec.php?msg=delete_failed");
        exit();
    }
}

// 2. AJAX ENDPOINT FOR PATIENT FULL RECORD HISTORY & REGISTRATION INFO
if (isset($_POST['fetch_patient_details']) || isset($_GET['fetch_patient_details'])) {
    header('Content-Type: application/json');

    $mother_id_raw = $_POST['fetch_patient_details'] ?? $_GET['fetch_patient_details'] ?? null;
    $mother_id = filter_var($mother_id_raw, FILTER_VALIDATE_INT);

    if ($mother_id === false || $mother_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid patient ID']);
        exit();
    }

    $reg_stmt = mysqli_prepare($conn, "SELECT *, CONCAT(COALESCE(street,''), ', ', COALESCE(barangay,''), ', ', COALESCE(municipality,'')) AS full_address FROM maternal_registration WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($reg_stmt, 'i', $mother_id);
    mysqli_stmt_execute($reg_stmt);
    $reg_result = mysqli_stmt_get_result($reg_stmt);
    $reg_data = $reg_result ? mysqli_fetch_assoc($reg_result) : [];
    mysqli_stmt_close($reg_stmt);

    $history_stmt = mysqli_prepare($conn, "SELECT * FROM pregnancy_history WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($history_stmt, 'i', $mother_id);
    mysqli_stmt_execute($history_stmt);
    $history_result = mysqli_stmt_get_result($history_stmt);
    $history_data = $history_result ? mysqli_fetch_assoc($history_result) : [];
    mysqli_stmt_close($history_stmt);

    $visits_stmt = mysqli_prepare($conn, "SELECT * FROM maternal_records WHERE mother_id = ? ORDER BY checkup_date DESC");
    mysqli_stmt_bind_param($visits_stmt, 'i', $mother_id);
    mysqli_stmt_execute($visits_stmt);
    $visits_result = mysqli_stmt_get_result($visits_stmt);

    $visits_data = [];
    if ($visits_result) {
        while ($v = mysqli_fetch_assoc($visits_result)) {
            $ga = intval($v['gestational_age_weeks'] ?? 0);
            if ($ga > 0) {
                $v['current_aog'] = $ga . ' weeks';
                $v['remaining_weeks'] = max(0, 40 - $ga);
            } else {
                $v['current_aog'] = 'N/A';
                $v['remaining_weeks'] = 'N/A';
            }
            $visits_data[] = $v;
        }
    }
    mysqli_stmt_close($visits_stmt);

    $latest_visit_stmt = mysqli_prepare($conn, "SELECT * FROM maternal_records WHERE mother_id = ? ORDER BY checkup_date DESC, id DESC LIMIT 1");
    mysqli_stmt_bind_param($latest_visit_stmt, 'i', $mother_id);
    mysqli_stmt_execute($latest_visit_stmt);
    $latest_visit_result = mysqli_stmt_get_result($latest_visit_stmt);
    $latest_visit_data = $latest_visit_result ? mysqli_fetch_assoc($latest_visit_result) : [];
    mysqli_stmt_close($latest_visit_stmt);

    echo json_encode([
        'registration_info' => $reg_data,
        'medical_history' => $history_data,
        'latest_visit' => $latest_visit_data,
        'checkup_visits' => $visits_data
    ]);
    exit();
}

// 3. FETCH PATIENTS MASTERLIST WITH SEARCH & TRIMESTER FILTER
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$trimester_filter = isset($_GET['trimester_filter']) ? trim((string) $_GET['trimester_filter']) : '';
if (!in_array($trimester_filter, ['1', '2', '3'], true)) {
    $trimester_filter = '';
}
$search_pattern = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';

$query = "SELECT u.*, 
         CONCAT(u.client_fname, ' ', COALESCE(u.client_mi,''), ' ', u.client_lname) AS full_name,
         r.weight_kg, r.bp, r.temperature, r.fetal_heart_rate, r.remarks, r.checkup_date, r.gestational_age_weeks
         FROM maternal_registration u
         LEFT JOIN (
             SELECT mother_id, weight_kg, bp, temperature, fetal_heart_rate, remarks, checkup_date, gestational_age_weeks 
             FROM maternal_records 
             WHERE id IN (SELECT MAX(id) FROM maternal_records GROUP BY mother_id)
         ) r ON u.id = r.mother_id
         WHERE u.status = 'Approved' 
         AND (u.client_fname LIKE ? OR u.client_lname LIKE ?)";

if ($trimester_filter === '1') {
    $query .= " AND r.gestational_age_weeks BETWEEN 1 AND 13";
} elseif ($trimester_filter === '2') {
    $query .= " AND r.gestational_age_weeks BETWEEN 14 AND 27";
} elseif ($trimester_filter === '3') {
    $query .= " AND r.gestational_age_weeks >= 28";
}

$stmt = mysqli_prepare($conn, $query . " ORDER BY u.created_at DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ss', $search_pattern, $search_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}

$patients = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ga = intval($row['gestational_age_weeks'] ?? 0);

        $row['current_aog'] = ($ga > 0) ? $ga . ' weeks' : 'N/A';
        $remaining = ($ga > 0) ? max(0, 40 - $ga) : -1;
        $row['computed_remaining'] = ($remaining >= 0) ? $remaining . ' weeks left' : 'N/A';
        $row['trimester_label'] = 'No data';
        if ($ga >= 1 && $ga <= 13) {
            $row['trimester_label'] = '1st Trimester';
        } elseif ($ga >= 14 && $ga <= 27) {
            $row['trimester_label'] = '2nd Trimester';
        } elseif ($ga >= 28) {
            $row['trimester_label'] = '3rd Trimester';
        }

        $patients[] = $row;
    }
}

$summary = [
    'total' => count($patients),
    'first' => 0,
    'second' => 0,
    'third' => 0,
];
foreach ($patients as $patient) {
    $ga = intval($patient['gestational_age_weeks'] ?? 0);
    if ($ga >= 1 && $ga <= 13) {
        $summary['first']++;
    } elseif ($ga >= 14 && $ga <= 27) {
        $summary['second']++;
    } elseif ($ga >= 28) {
        $summary['third']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maternal Health Records | Alawihao Center</title>
    <style>
        :root { 
            --sage-green: #6B8E55;
            --sage-green-dark: #4d6a3c;
            --sage-soft: #eef4ea;
            --bg-beige: #fcfdfa; 
            --panel-white: #ffffff;
            --panel-border: #e7ecd7;
            --danger-red: #d9534f;
            --danger-soft: #fdeaea;
            --text-dark: #233123;
            --text-muted: #6b726b;
            --shadow-soft: 0 10px 24px rgba(50, 74, 45, 0.08);
        }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-beige); margin: 0; display: flex; color: var(--text-dark); }
        #main { width: 100%; padding: 32px 28px 40px; box-sizing: border-box; min-height: 100vh; margin-left: 280px; }
        .records-card { background: white; padding: 28px; border-radius: 18px; box-shadow: var(--shadow-soft); border: 1px solid var(--panel-border); }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 16px; }
        h2 { color: var(--sage-green-dark); font-size: 1.9rem; margin: 0; }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(150px, 1fr));
            gap: 14px;
            margin: 0 0 20px;
        }
        .summary-card {
            background: linear-gradient(135deg, #ffffff 0%, #f5faf2 100%);
            border: 1px solid var(--panel-border);
            border-left: 5px solid var(--sage-green);
            border-radius: 14px;
            padding: 18px 16px;
            box-shadow: 0 8px 20px rgba(107, 142, 85, 0.06);
        }
        .summary-card .label { display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 10px; }
        .summary-card strong { font-size: 1.9rem; color: var(--sage-green-dark); display: block; }

        .filter-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .search-box, .filter-select { padding: 10px 12px; border: 1px solid #dfe5d9; border-radius: 10px; font-size: 0.9rem; outline: none; background: #fff; color: var(--text-dark); }
        .search-box { width: 220px; }
        .filter-select { min-width: 180px; }
        .search-box:focus, .filter-select:focus { border-color: var(--sage-green); box-shadow: 0 0 0 3px rgba(107, 142, 85, 0.12); }

        .btn-add, .btn-search, .btn-clear { display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.88rem; transition: 0.2s ease; }
        .btn-add, .btn-search { background-color: var(--sage-green); color: white; border: none; padding: 10px 16px; cursor: pointer; }
        .btn-add:hover, .btn-search:hover { background-color: var(--sage-green-dark); }
        .btn-clear { background: #f3f6ef; color: var(--sage-green-dark); border: 1px solid #dfead2; padding: 10px 14px; }
        .btn-clear:hover { background: #edf5e6; }

        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; min-width: 720px; }
        thead { background: linear-gradient(135deg, var(--sage-green) 0%, #7a9a64 100%); }
        th { color: white; padding: 14px 16px; text-align: left; font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.04em; }
        td { padding: 14px 16px; border-bottom: 1px solid #edf1e7; font-size: 0.92rem; }
        tbody tr { transition: background 0.2s ease; }
        tbody tr:hover { background: #fafcf8; }

        .patient-name-wrap { display: flex; align-items: center; gap: 10px; }
        .patient-name { font-weight: 700; color: var(--text-dark); }
        .trimester-badge {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .trimester-1 { background: #eaf8ed; color: #2d7a46; }
        .trimester-2 { background: #fff7e8; color: #9a6c14; }
        .trimester-3 { background: #fef1f1; color: #b84d4d; }
        .trimester-none { background: #f1f3f4; color: #6d757a; }

        .view-btn { background: transparent; color: var(--sage-green-dark); border: 1.5px solid var(--sage-green); padding: 7px 14px; border-radius: 8px; cursor: pointer; font-weight: 700; }
        .view-btn:hover { background: var(--sage-green); color: white; }

        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.45); }
        .modal-content { background: white; margin: 3% auto; padding: 28px; border-radius: 20px; width: 900px; position: relative; max-height: 85vh; overflow-y: auto; box-shadow: 0 15px 35px rgba(0,0,0,0.18); }

        .modal-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 10px; }
        .modal-header h1 { color: var(--sage-green); margin: 0; font-size: 1.9rem; }
        .patient-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            background: var(--sage-soft);
            color: var(--sage-green-dark);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .confirm-modal-content { background: white; margin: 15% auto; padding: 30px; border-radius: 18px; width: 420px; text-align: center; position: relative; box-shadow: 0 15px 35px rgba(0,0,0,0.18); }
        .confirm-modal-content h3 { color: var(--text-dark); margin-top: 0; margin-bottom: 10px; font-size: 1.4rem; }
        .confirm-modal-content p { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 22px; line-height: 1.5; }
        .confirm-actions { display: flex; justify-content: center; gap: 12px; }
        .btn-confirm-yes { background-color: var(--danger-red); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }
        .btn-confirm-yes:hover { opacity: 0.9; }
        .btn-confirm-no { background-color: #eef1ee; color: var(--text-dark); border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }
        .btn-confirm-no:hover { background-color: #e2e7e2; }

        .tab-menu { display: flex; border-bottom: 2px solid #edf1e7; margin: 16px 0 18px; flex-wrap: wrap; gap: 8px; }
        .tab-btn { padding: 10px 16px; cursor: pointer; background: none; border: none; font-weight: 700; color: var(--text-muted); font-size: 0.9rem; border-bottom: 3px solid transparent; }
        .tab-btn.active { color: var(--sage-green-dark); border-bottom-color: var(--sage-green); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .info-card { background: linear-gradient(135deg, #fbfdf9 0%, #f4f8f0 100%); border: 1px dashed var(--sage-green); padding: 15px; border-radius: 14px; margin-bottom: 18px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .info-item label { display: block; font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
        .info-item span { font-weight: 700; color: var(--text-dark); font-size: 0.9rem; }

        .section-title { font-size: 0.94rem; color: var(--sage-green-dark); font-weight: 800; margin-top: 18px; margin-bottom: 8px; border-bottom: 1px solid #edf1e7; padding-bottom: 6px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 18px; font-size: 0.86rem; line-height: 1.6; }
        .history-panel { background: #fafcf9; border: 1px solid #edf1e7; padding: 18px 20px; border-radius: 14px; font-size: 0.85rem; line-height: 1.7; }

        .btn-delete { background: none; border: none; color: var(--danger-red); text-decoration: underline; cursor: pointer; font-weight: 700; }
        .alert { background: #dff2df; color: #235a29; padding: 14px 16px; border-radius: 10px; margin-bottom: 18px; text-align: center; border: 1px solid #c6e7c9; }

        @media (max-width: 768px) {
            #main { margin-left: 0; padding: 20px 16px; }
            .summary-grid { grid-template-columns: repeat(2, minmax(120px, 1fr)); }
            .info-card { grid-template-columns: 1fr 1fr; }
            .grid-2 { grid-template-columns: 1fr; }
            .modal-content { width: 92%; padding: 20px; }
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
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert">Operation successful. Record updated/deleted.</div>
        <?php endif; ?>

        <div class="records-card">
            <div class="header-section">
                <h2>Maternal Health Records</h2>
                <div style="display:flex; gap:10px; align-items: center; flex-wrap: wrap;">
                    <form method="GET" class="filter-form">
                        <select name="trimester_filter" class="filter-select" aria-label="Filter by trimester" onchange="this.form.submit()">
                            <option value="">-- Filter by Trimester --</option>
                            <option value="1" <?= ($trimester_filter == '1') ? 'selected' : '' ?>>1st Trimester (1 - 13 wks)</option>
                            <option value="2" <?= ($trimester_filter == '2') ? 'selected' : '' ?>>2nd Trimester (14 - 27 wks)</option>
                            <option value="3" <?= ($trimester_filter == '3') ? 'selected' : '' ?>>3rd Trimester (28+ wks)</option>
                        </select>
                        <input type="text" name="search" class="search-box" placeholder="Search patient name..." value="<?= htmlspecialchars($search) ?>" aria-label="Search patient name">
                        <button type="submit" class="btn-search">Search</button>
                        <?php if ($search !== '' || $trimester_filter !== ''): ?>
                            <a href="admin_maternal_rec.php" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>
                    <a href="admin_maternal_reg.php" class="btn-add">+ Register Patient</a>
                </div>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <span class="label">Total</span>
                    <strong><?= $summary['total'] ?></strong>
                </div>
                <div class="summary-card">
                    <span class="label">1st Trimester</span>
                    <strong><?= $summary['first'] ?></strong>
                </div>
                <div class="summary-card">
                    <span class="label">2nd Trimester</span>
                    <strong><?= $summary['second'] ?></strong>
                </div>
                <div class="summary-card">
                    <span class="label">3rd Trimester</span>
                    <strong><?= $summary['third'] ?></strong>
                </div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Last Visit</th>
                            <th>Current AOG</th>
                            <th>Remaining to Full-Term</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($patients) > 0): ?>
                            <?php foreach($patients as $row): ?>
                            <tr>
                                <td>
                                    <div class="patient-name-wrap">
                                        <span class="patient-name"><?= htmlspecialchars($row['full_name']) ?></span>
                                        <?php
                                            $badgeClass = 'trimester-none';
                                            $badgeText = 'No data';
                                            if (($row['gestational_age_weeks'] ?? 0) >= 1 && ($row['gestational_age_weeks'] ?? 0) <= 13) {
                                                $badgeClass = 'trimester-1';
                                                $badgeText = '1st';
                                            } elseif (($row['gestational_age_weeks'] ?? 0) >= 14 && ($row['gestational_age_weeks'] ?? 0) <= 27) {
                                                $badgeClass = 'trimester-2';
                                                $badgeText = '2nd';
                                            } elseif (($row['gestational_age_weeks'] ?? 0) >= 28) {
                                                $badgeClass = 'trimester-3';
                                                $badgeText = '3rd';
                                            }
                                        ?>
                                        <span class="trimester-badge <?= $badgeClass ?>"><?= $badgeText ?> Trimester</span>
                                    </div>
                                </td>
                                <td><?= $row['checkup_date'] ? date('M d, Y', strtotime($row['checkup_date'])) : 'No records' ?></td>
                                <td>
                                    <span style="color: #333; font-weight: 700;"><?= htmlspecialchars($row['current_aog']) ?></span>
                                </td>
                                <td>
                                    <?php if($row['computed_remaining'] !== 'N/A'): ?>
                                        <span style="background: #eef4ec; color: var(--sage-green-dark); padding: 5px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 700;">
                                            <?= htmlspecialchars($row['computed_remaining']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><button class="view-btn" onclick='openModal(<?= json_encode($row) ?>)'>View Details</button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #7a7d79; padding: 26px; font-weight: 600;">No patient records found for the selected filter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- READ-ONLY PATIENT DETAILS MODAL -->
    <div id="maternalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h1 id="m_name"></h1>
                <span class="patient-pill">Maternal Profile</span>
            </div>
            
            <div class="info-card">
                <div class="info-item"><label>Age</label><span id="m_age">--</span></div>
                <div class="info-item"><label>Contact</label><span id="m_contact">--</span></div>
                <div class="info-item"><label>Blood Type</label><span id="m_blood">--</span></div>
                <div class="info-item"><label>LMP</label><span id="m_lmp">--</span></div>
            </div>

            <!-- TABS -->
            <div class="tab-menu">
                <button class="tab-btn active" onclick="switchTab(event, 'tab-history')">Medical & Verification History </button>
                <button class="tab-btn" onclick="switchTab(event, 'tab-visits')">Check-up Visits History</button>
            </div>

            <!-- TAB 1: MEDICAL & VERIFICATION HISTORY (DOH FORM) -->
            <div id="tab-history" class="tab-content active">
                <div class="history-panel">
                    
                    <div class="section-title" style="margin-top:0;">Personal & Registration Details</div>
                    <div class="grid-2">
                        <p style="margin:4px 0;"><b>Address:</b> <span id="m_address">--</span></p>
                        <p style="margin:4px 0;"><b>Spouse Name:</b> <span id="m_spouse">--</span></p>
                        <p style="margin:4px 0;"><b>Education:</b> <span id="m_educ">--</span></p>
                        <p style="margin:4px 0;"><b>Occupation:</b> <span id="m_occupation">--</span></p>
                    </div>

                    <div class="section-title">Obstetrical History</div>
                    <p style="margin:5px 0;">Gravida: <span id="m_g">0</span> | Para: <span id="m_p">0</span> (Full-term: <span id="m_ft">0</span>, Premature: <span id="m_pre">0</span>, Abortion: <span id="m_ab">0</span>, Living Children: <span id="m_lc">0</span>)</p>

                    <!-- PHYSICAL EXAMINATION & VITALS -->
                    <div class="section-title">Physical Examination & Vital Signs</div>
                    <div class="grid-2">
                        <p style="margin:4px 0;"><b>Physical Exam Vitals:</b> <span id="m_pe_vitals">None specified</span></p>
                        <p style="margin:4px 0;"><b>Physical Exam Findings:</b> <span id="m_pe_findings">None specified</span></p>
                        <p style="margin:4px 0;"><b>TT Status:</b> <span id="m_tt_status">None specified</span></p>
                        <p style="margin:4px 0;"><b>MUAC:</b> <span id="m_muac">None specified</span></p>
                        <p style="margin:4px 0;"><b>BMI & Category:</b> <span id="m_bmi">None specified</span></p>
                    </div>

                    <!-- SYSTEM FINDINGS -->
                    <div class="section-title">System & Organ Findings</div>
                    <div class="grid-2">
                        <p style="margin:4px 0;"><b>HEENT / Conjunctiva:</b> <span id="m_heent">None specified</span></p>
                        <p style="margin:4px 0;"><b>Neck:</b> <span id="m_neck">None specified</span></p>
                        <p style="margin:4px 0;"><b>Breast:</b> <span id="m_breast">None specified</span></p>
                        <p style="margin:4px 0;"><b>Thorax / Heart:</b> <span id="m_chest">None specified</span></p>
                        <p style="margin:4px 0;"><b>Abdomen (Medical):</b> <span id="m_abdomen">None specified</span></p>
                        <p style="margin:4px 0;"><b>Genital / Vaginal:</b> <span id="m_genital">None specified</span></p>
                        <p style="margin:4px 0;"><b>Extremities:</b> <span id="m_extremities">None specified</span></p>
                        <p style="margin:4px 0;"><b>Skin & Others:</b> <span id="m_skin">None specified</span></p>
                    </div>

                    <!-- MEDICAL & SOCIAL BACKGROUND -->
                    <div class="section-title">Family, Past Health & Social History</div>
                    <p style="margin:4px 0;"><b>Family History:</b> <span id="m_fh">None specified</span></p>
                    <p style="margin:4px 0;"><b>Past Health History:</b> <span id="m_phh">None specified</span></p>
                    <p style="margin:4px 0;"><b>Social History:</b> <span id="m_sh">None specified</span></p>

                    <!-- FAMILY PLANNING HISTORY -->
                    <div class="section-title">Family Planning History</div>
                    <div class="grid-2">
                        <p style="margin:4px 0;"><b>Previous FP Method:</b> <span id="m_prev_fp">None specified</span></p>
                        <p style="margin:4px 0;"><b>FP Duration:</b> <span id="m_fp_dur">None specified</span></p>
                    </div>

                    <!-- MENSTRUAL & DELIVERY DETAILS -->
                    <div class="section-title">Menstrual & Delivery Details</div>
                    <div class="grid-2">
                        <p style="margin:4px 0;"><b>Past LMP:</b> <span id="m_past_lmp">--</span></p>
                        <p style="margin:4px 0;"><b>Bleeding Duration:</b> <span id="m_bleeding">--</span></p>
                        <p style="margin:4px 0;" style="grid-column: span 2;"><b>Last Birth Attendant:</b> <span id="m_attendant">--</span></p>
                    </div>

                </div>
            </div>

            <!-- TAB 2: CHECK-UP VISITS LOG TABLE -->
            <div id="tab-visits" class="tab-content">
                <table style="font-size:0.8rem;">
                    <thead>
                        <tr style="background:#89936C;">
                            <th>Date</th>
                            <th>AOG</th>
                            <th>Remaining Weeks</th>
                            <th>BP</th>
                            <th>Weight</th>
                            <th>FHR</th>
                            <th>Diagnosis / Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="visits_table_body">
                        <!-- Populated via AJAX -->
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 25px; display: flex; justify-content: space-between; align-items:center;">
                <button type="button" class="btn-delete" onclick="confirmDelete()">Delete Patient</button>
                <button onclick="closeModal()" style="background:#eee; border:none; padding:8px 18px; border-radius:8px; cursor:pointer;">Close</button>
            </div>
        </div>
    </div>

    <!-- CUSTOM CONFIRMATION MODAL CARD -->
    <div id="confirmModal" class="modal">
        <div class="confirm-modal-content">
            <form method="post" action="admin_maternal_rec.php" id="deletePatientForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="delete_id" id="delete_id" value="">
                <h3>Confirm Delete</h3>
                <p id="deletePromptText">Are you sure you want to delete this patient record?</p>
                <div class="confirm-actions">
                    <button type="button" class="btn-confirm-no" onclick="closeConfirmModal()">Cancel</button>
                    <button type="submit" class="btn-confirm-yes">Delete</button>
                </div>
            </form>
        </div>
    </div>

 <script>
        let currentMotherId = null;

        function switchTab(evt, tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            evt.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        function openModal(data) {
            currentMotherId = data.id;

            let fullName = `${data.client_fname || ''} ${data.client_mi ? data.client_mi + ' ' : ''}${data.client_lname || ''}`;
            document.getElementById('m_name').innerText = fullName;
            document.getElementById('m_age').innerText = (data.age || "--") + " yrs old";
            document.getElementById('m_contact').innerText = data.contact || "N/A";
            document.getElementById('m_blood').innerText = data.blood_type || "N/A";
            document.getElementById('m_lmp').innerText = data.lmp || "Not set";

            // AJAX call to fetch all records
            fetch('admin_maternal_rec.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: new URLSearchParams({ fetch_patient_details: data.id })
            })
                .then(res => res.json())
                .then(resData => {
                    const reg = resData.registration_info || {};
                    document.getElementById('m_address').innerText = reg.full_address || "N/A";
                    document.getElementById('m_spouse').innerText = ((reg.spouse_fname || '') + ' ' + (reg.spouse_lname || '')).trim() || "N/A";
                    document.getElementById('m_educ').innerText = reg.highest_educ || "N/A";
                    document.getElementById('m_occupation').innerText = reg.occupation || "N/A";

                    const med = resData.medical_history || {};
                    const visit = resData.latest_visit || {};

                    // Pinagsasama ang med (pregnancy_history) at visit (maternal_records) para masigurong may masalo kahit saang table nakasave
                    function getVal(key) {
                        return (med[key] !== undefined && med[key] !== null && med[key] !== "" && med[key] !== "None specified") ? med[key] : 
                               ((visit[key] !== undefined && visit[key] !== null && visit[key] !== "") ? visit[key] : null);
                    }

                    document.getElementById('m_g').innerText = getVal('gravida') || med.gravida || 0;
                    document.getElementById('m_p').innerText = getVal('para') || med.para || 0;
                    document.getElementById('m_ft').innerText = getVal('full_term') || med.full_term || 0;
                    document.getElementById('m_pre').innerText = getVal('premature') || med.premature || 0;
                    document.getElementById('m_ab').innerText = getVal('abortion') || med.abortion || 0;
                    document.getElementById('m_lc').innerText = getVal('living_children') || med.living_children || 0;
                    
                    // Vitals Mapping
                    let bp = getVal('vs_bp') || getVal('bp') || '';
                    let wt = getVal('vs_weight') || getVal('weight_kg') || getVal('weight') || '';
                    let pulse = getVal('vs_pulse') || getVal('pulse') || '';
                    let height = getVal('vs_height') || getVal('height') || '';
                    let muac = getVal('vs_muac') || getVal('muac') || '';
                    let bmi = getVal('vs_bmi') || getVal('bmi') || '';

                    let vitalsArr = [];
                    if (bp) vitalsArr.push(`BP: ${bp}`);
                    if (wt) vitalsArr.push(`Wt: ${wt}kg`);
                    if (pulse) vitalsArr.push(`Pulse: ${pulse}bpm`);
                    if (height) vitalsArr.push(`Ht: ${height}cm`);
                    if (muac) vitalsArr.push(`MUAC: ${muac}`);
                    if (bmi) vitalsArr.push(`BMI: ${bmi}`);

                    document.getElementById('m_pe_vitals').innerText = vitalsArr.length > 0 ? vitalsArr.join(', ') : (getVal('physical_exam_vitals') || getVal('vitals') || "None specified");
                    document.getElementById('m_pe_findings').innerText = getVal('physical_exam_findings') || "None specified";
                    document.getElementById('m_tt_status').innerText = getVal('tt_status') || "None specified";
                    document.getElementById('m_muac').innerText = muac || "None specified";
                    document.getElementById('m_bmi').innerText = bmi || "None specified";

                    // System Findings Mapping (HEENT, Neck, Breast, Thorax, Abdomen, Genital, Extremities, Skin)
                    document.getElementById('m_heent').innerText = getVal('heent_findings') || getVal('pe_conjunctiva') || getVal('heent') || "None specified";
                    document.getElementById('m_neck').innerText = getVal('neck') || getVal('pe_neck') || "None specified";
                    
                    // Breast Finding / Size dynamic handling
                    let breastVal = getVal('breast') || getVal('pe_breast') || getVal('breast_findings');
                    let leftSize = getVal('breast_left_size') || getVal('pe_breast_mass_left');
                    let rightSize = getVal('breast_right_size') || getVal('pe_breast_mass_right');
                    
                    let breastDisplay = breastVal && breastVal !== "None specified" ? breastVal : "";
                    if (leftSize || rightSize) {
                        let sizes = `Left: ${leftSize || 'None'}, Right: ${rightSize || 'None'}`;
                        breastDisplay = breastDisplay ? `${breastDisplay} — ${sizes}` : sizes;
                    }
                    document.getElementById('m_breast').innerText = breastDisplay || "None specified";
                    
                    document.getElementById('m_chest').innerText = getVal('chest_heart') || getVal('pe_thorax') || getVal('chest') || "None specified";
                    document.getElementById('m_abdomen').innerText = getVal('abdomen_med') || getVal('pe_abdomen') || getVal('abdomen') || "None specified";
                    document.getElementById('m_genital').innerText = getVal('genital_med') || getVal('pe_vaginal') || getVal('genital') || "None specified";
                    document.getElementById('m_extremities').innerText = getVal('extremities_med') || getVal('pe_extremities') || getVal('extremities') || "None specified";
                    document.getElementById('m_skin').innerText = getVal('skin_med') || getVal('skin') || "None specified";

                    document.getElementById('m_fh').innerText = getVal('family_history_details') || getVal('family_history') || "None specified";
                    document.getElementById('m_phh').innerText = getVal('past_health_details') || getVal('past_health_history') || "None specified";

                    // Updated Social History Mapping with Sticks and Amount Support
                    let shVal = getVal('social_history') || getVal('social_history_details') || '';
                    let smokingSticks = getVal('smoking_sticks') || getVal('sticks') || getVal('smoking_qty') || '';
                    let alcoholAmount = getVal('alcohol_amount') || getVal('alcohol') || getVal('alcohol_qty') || '';

                    let shParts = [];
                    if (shVal) shParts.push(shVal);
                    if (smokingSticks && !shVal.includes(smokingSticks)) {
                        shParts.push(`Smoking: ${smokingSticks} sticks/day`);
                    }
                    if (alcoholAmount && !shVal.includes(alcoholAmount)) {
                        shParts.push(`Alcohol: ${alcoholAmount}`);
                    }
                    document.getElementById('m_sh').innerText = shParts.length > 0 ? shParts.join(', ') : "None specified";

                    document.getElementById('m_prev_fp').innerText = getVal('prev_fp_method') || getVal('fp_previous_method') || "None specified";
                    document.getElementById('m_fp_dur').innerText = getVal('fp_duration') || "None specified";

                    document.getElementById('m_past_lmp').innerText = getVal('past_lmp') || getVal('past_menstrual_period') || "N/A";
                    let bleeding = getVal('bleeding_duration_days');
                    document.getElementById('m_bleeding').innerText = bleeding ? bleeding + ' days' : "N/A";
                    document.getElementById('m_attendant').innerText = getVal('last_delivery_attendant') || "N/A";

                    const tbody = document.getElementById('visits_table_body');
                    tbody.innerHTML = '';

                    if (!resData.checkup_visits || resData.checkup_visits.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#888;">No check-up visits recorded yet.</td></tr>`;
                    } else {
                        resData.checkup_visits.forEach(visit => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>${visit.checkup_date || '--'}</td>
                                    <td>${visit.current_aog !== 'N/A' ? visit.current_aog : '--'}</td>
                                    <td>${visit.remaining_weeks !== 'N/A' ? visit.remaining_weeks + ' wks left' : '--'}</td>
                                    <td>${visit.bp || '--'}</td>
                                    <td>${visit.weight_kg ? visit.weight_kg + ' kg' : '--'}</td>
                                    <td>${visit.fetal_heart_rate ? visit.fetal_heart_rate + ' bpm' : '--'}</td>
                                    <td>${visit.impression_diagnosis || visit.remarks || '--'}</td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(err => console.error("Error fetching patient details:", err));

            document.getElementById('maternalModal').style.display = "block";
        }

        function closeModal() { document.getElementById('maternalModal').style.display = "none"; }

        function confirmDelete() {
            if (currentMotherId) {
                document.getElementById('delete_id').value = currentMotherId;
                const patientName = document.getElementById('m_name')?.innerText || 'this patient';
                document.getElementById('deletePromptText').innerText = `Are you sure you want to delete the record for ${patientName}?`;
                document.getElementById('confirmModal').style.display = "block";
            }
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = "none";
        }
    </script>
</body>
</html>