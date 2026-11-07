<?php
session_start();
include '../db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Kunin ang mga parameters mula sa GET
$search = trim((string) ($_GET['search'] ?? ''));
$filter = ($_GET['filter'] ?? 'newest') === 'oldest' ? 'oldest' : 'newest';
$age_filter = (string) ($_GET['age_filter'] ?? 'all');
$dose_filter = (string) ($_GET['dose_filter'] ?? 'all');
$valid_age_filters = ['all', '0-1', '1-6', '6-12', '1-2', '2_above'];
$valid_dose_filters = ['all', '0', '1-2', '3_plus'];
if (!in_array($age_filter, $valid_age_filters, true)) {
    $age_filter = 'all';
}
if (!in_array($dose_filter, $valid_dose_filters, true)) {
    $dose_filter = 'all';
}

// 2. Query para sa approved child master list at pinakahuling health record
$query = "SELECT c.*,
          c.weight_kg AS weight_kg,
          c.height_cm AS height,
          COALESCE(c.vaccine_taken, 'None') AS vaccine_taken,
          c.birth_date AS r_dob,
          c.administered_by,
          (SELECT MAX(COALESCE(ir.vaccine_date, DATE(ir.created_at)))
             FROM infant_records ir WHERE ir.child_id = c.id) AS last_visit
          FROM children c
          WHERE c.status = 'Approved'
            AND (c.child_name LIKE ? OR c.mother_name LIKE ?)";

// Age Filtering Logic
if ($age_filter == '0-1') {
    $query .= " AND c.birth_date IS NOT NULL AND TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) <= 1";
} elseif ($age_filter == '1-6') {
    $query .= " AND c.birth_date IS NOT NULL AND TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) > 1 AND TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) <= 6";
} elseif ($age_filter == '6-12') {
    $query .= " AND c.birth_date IS NOT NULL AND TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) > 6 AND TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) <= 12";
} elseif ($age_filter == '1-2') {
    $query .= " AND c.birth_date IS NOT NULL AND TIMESTAMPDIFF(YEAR, c.birth_date, CURDATE()) >= 1 AND TIMESTAMPDIFF(YEAR, c.birth_date, CURDATE()) < 2";
} elseif ($age_filter == '2_above') {
    $query .= " AND c.birth_date IS NOT NULL AND TIMESTAMPDIFF(YEAR, c.birth_date, CURDATE()) >= 2";
}

if ($filter === 'newest') {
    $query .= " ORDER BY c.created_at DESC";
} else {
    $query .= " ORDER BY c.created_at ASC";
}

$stmt = mysqli_prepare($conn, $query);
$search_pattern = '%' . $search . '%';
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $search_pattern, $search_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
} else {
    $result = false;
}
$child_load_error = $result ? '' : 'Unable to load child records right now.';
if (!$result) {
    error_log('Child records query failed: ' . mysqli_error($conn));
}

// Ipunin muna ang records at i-filter base sa doses kung kinakailangan sa PHP level
$all_records = [];
$normalize_vaccine_name = function ($value) {
    $name = strtolower(trim((string) $value));
    $name = preg_replace('/\s*\(\d{4}-\d{2}-\d{2}\)\s*/', ' ', $name);
    $name = preg_replace('/\b(?:dose\s*\d+|\d+(?:st|nd|rd|th)?\s*dose)\b/i', ' ', $name);
    $name = preg_replace('/\bvaccine\b/i', ' ', $name);
    return trim(preg_replace('/[^a-z0-9]+/i', ' ', $name));
};
while($result && ($row = mysqli_fetch_assoc($result))) {
    $child_current_id = $row['id'];
    
    // Kunin ang history mula sa infant_records table LAMANG
    $history_stmt = mysqli_prepare($conn, "SELECT * FROM infant_records WHERE child_id = ? ORDER BY created_at DESC");
    if (!$history_stmt) {
        error_log('Child immunization history query could not be prepared: ' . mysqli_error($conn));
        $child_load_error = 'Unable to load child immunization history right now.';
        continue;
    }
    mysqli_stmt_bind_param($history_stmt, 'i', $child_current_id);
    mysqli_stmt_execute($history_stmt);
    $history_query = mysqli_stmt_get_result($history_stmt);
    $history_arr = [];
    while($history_query && ($hist = mysqli_fetch_assoc($history_query))) {
        $history_arr[] = $hist;
    }
    mysqli_stmt_close($history_stmt);

    $legacy_vaccine = trim((string) ($row['vaccine_taken'] ?? ''));
    if ($legacy_vaccine !== '' && strtolower($legacy_vaccine) !== 'none') {
        $legacy_vaccine_name = $normalize_vaccine_name($legacy_vaccine);
        $has_history_for_legacy_vaccine = false;
        foreach ($history_arr as $history_record) {
            if ($normalize_vaccine_name($history_record['vaccine_taken'] ?? '') === $legacy_vaccine_name) {
                $has_history_for_legacy_vaccine = true;
                break;
            }
        }

        if (!$has_history_for_legacy_vaccine) {
            $legacy_vaccine_date = null;
            if (preg_match('/\b(20\d{2}-\d{2}-\d{2})\b/', $legacy_vaccine, $date_match)) {
                $legacy_vaccine_date = $date_match[1];
            }
            if ($legacy_vaccine_date !== null
                && (empty($row['last_visit']) || $legacy_vaccine_date > $row['last_visit'])) {
                $row['last_visit'] = $legacy_vaccine_date;
            }
            array_unshift($history_arr, [
                'vaccine_taken' => $legacy_vaccine,
                'vaccine_date' => $legacy_vaccine_date,
                'next_checkup' => null,
                'remarks' => 'Legacy vaccination record',
                'administered_by' => $row['administered_by'] ?? '',
                'weight_kg' => $row['weight_kg'] ?? null,
                'height' => $row['height'] ?? null,
                'record_source' => 'legacy'
            ]);
            usort($history_arr, function ($left, $right) {
                $left_date = ($left['record_source'] ?? '') === 'legacy'
                    ? ($left['vaccine_date'] ?? '')
                    : ($left['created_at'] ?? '');
                $right_date = ($right['record_source'] ?? '') === 'legacy'
                    ? ($right['vaccine_date'] ?? '')
                    : ($right['created_at'] ?? '');
                return strcmp((string) $right_date, (string) $left_date);
            });
        }
    }
    
    $row['history'] = $history_arr;
    $row['record_count'] = count($history_arr);

    // Bilangin ang total unique/valid doses mula sa infant_records
    $valid_doses = array_filter($history_arr, function($h) {
        return !empty($h['vaccine_taken']) && strtolower($h['vaccine_taken']) != 'none';
    });
    
    $unique_vaccines = [];
    foreach ($valid_doses as $d) {
        $v_name = strtolower(trim($d['vaccine_taken']));
        if (!in_array($v_name, $unique_vaccines)) {
            $unique_vaccines[] = $v_name;
        }
    }
    $row['total_doses'] = count($unique_vaccines);
    $row['current_age_months'] = null;
    $row['current_age_label'] = 'Not specified';
    if (!empty($row['birth_date'])) {
        try {
            $age = (new DateTimeImmutable($row['birth_date']))->diff(new DateTimeImmutable('today'));
            $row['current_age_months'] = ($age->y * 12) + $age->m;
            $row['current_age_label'] = $age->y > 0
                ? $age->y . ' year' . ($age->y === 1 ? '' : 's')
                : ($age->m > 0
                    ? $age->m . ' month' . ($age->m === 1 ? '' : 's')
                    : 'Under 1 month');
        } catch (Exception $exception) {
            $row['current_age_months'] = null;
        }
    }

    // Dose Filtering Condition
    $matches_dose = true;
    if ($dose_filter == '0') {
        $matches_dose = ($row['total_doses'] == 0);
    } elseif ($dose_filter == '1-2') {
        $matches_dose = ($row['total_doses'] >= 1 && $row['total_doses'] <= 2);
    } elseif ($dose_filter == '3_plus') {
        $matches_dose = ($row['total_doses'] >= 3);
    }

    if ($matches_dose) {
        $all_records[] = $row;
    }
}

$summary = [
    'total' => count($all_records),
    'newborn' => 0,
    'infant' => 0,
    'older_infant' => 0,
];
foreach ($all_records as $child) {
    $age_months = $child['current_age_months'];
    if ($age_months === null) {
        continue;
    }
    if ($age_months <= 1) {
        $summary['newborn']++;
    } elseif ($age_months <= 6) {
        $summary['infant']++;
    } elseif ($age_months <= 12) {
        $summary['older_infant']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Child Health Records | Alawihao Health Center</title>
    <style>
        :root { --sage-green: #6B8E55; --sage-green-dark: #4F6F3E; --bg-beige: #f8f9fa; --danger-red: #E53E3E; --text-dark: #30382d; --text-muted: #788174; --panel-border: #e6ecdf; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-beige); margin: 0; display: flex; color: var(--text-dark); }
        #main { width: 100%; padding: 32px 28px 40px; box-sizing: border-box; min-height: 100vh; margin-left: 280px; }
        .records-card { background: white; padding: 28px; border-radius: 18px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid var(--panel-border); }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 16px; }
        h2 { color: var(--sage-green-dark); font-size: 1.9rem; margin: 0; }
        .page-description { color: var(--text-muted); margin: 6px 0 0; font-size: 0.9rem; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(150px, 1fr)); gap: 14px; margin: 0 0 20px; }
        .summary-card { background: linear-gradient(135deg, #fff 0%, #f5faf2 100%); border: 1px solid var(--panel-border); border-left: 5px solid var(--sage-green); border-radius: 14px; padding: 18px 16px; box-shadow: 0 8px 20px rgba(107,142,85,0.06); }
        .summary-card .label { display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 10px; }
        .summary-card strong { font-size: 1.9rem; color: var(--sage-green-dark); display: block; }
        .filter-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .search-box, .filter-select { padding: 10px 12px; border: 1px solid #dfe5d9; border-radius: 10px; font-size: 0.9rem; outline: none; background: #fff; color: var(--text-dark); }
        .search-box { width: 210px; }
        .filter-select { min-width: 150px; }
        .search-box:focus, .filter-select:focus { border-color: var(--sage-green); box-shadow: 0 0 0 3px rgba(107,142,85,0.12); }
        .btn-add, .btn-search, .btn-clear { display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.88rem; }
        .btn-add, .btn-search { background-color: var(--sage-green); color: white; border: none; padding: 10px 16px; cursor: pointer; }
        .btn-add:hover, .btn-search:hover { background-color: var(--sage-green-dark); }
        .btn-clear { background: #f3f6ef; color: var(--sage-green-dark); border: 1px solid #dfead2; padding: 10px 14px; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; min-width: 720px; }
        thead { background: linear-gradient(135deg, var(--sage-green) 0%, #7a9a64 100%); }
        th { color: white; padding: 14px 16px; text-align: left; font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.04em; }
        td { padding: 14px 16px; border-bottom: 1px solid #edf1e7; font-size: 0.92rem; }
        tbody tr:hover { background: #fafcf8; }
        .patient-name { font-weight: 700; color: var(--text-dark); }
        .patient-subtext { display: block; margin-top: 4px; color: var(--text-muted); font-size: 0.78rem; }
        .age-badge { display: inline-flex; padding: 5px 8px; border-radius: 999px; background: #eaf8ed; color: #2d7a46; font-size: 0.75rem; font-weight: 700; }
        .view-btn { background: transparent; color: var(--sage-green-dark); border: 1.5px solid var(--sage-green); padding: 7px 14px; border-radius: 8px; cursor: pointer; font-weight: 700; }
        .view-btn:hover { background: var(--sage-green); color: white; }
        .load-error { color: #9b2c2c; background: #fff5f5; border: 1px solid #fed7d7; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; }
        
        .vax-badge {
            background: #eef3ec;
            color: var(--sage-green);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
        .modal-content { background: white; margin: 2% auto; padding: 30px; border-radius: 20px; width: 750px; position: relative; max-height: 90vh; overflow-y: auto; }
        
        /* Sub-Modal styles para sa Health Record details */
        .sub-modal { display: none; position: fixed; z-index: 4000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); }
        .sub-modal-content { background: white; margin: 10% auto; padding: 25px; border-radius: 12px; width: 400px; position: relative; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .btn-health-record { background-color: #2b6cb0; color: white; border: none; padding: 5px 10px; border-radius: 5px; font-size: 0.75rem; font-weight: 600; cursor: pointer; }
        .btn-health-record:hover { background-color: #2c5282; }

        .info-card { background: #fdfdfd; border: 1px dashed var(--sage-green); padding: 15px; border-radius: 12px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .info-item label { display: block; font-size: 0.65rem; color: #888; text-transform: uppercase; font-weight: bold; }
        .info-item span { font-weight: 600; color: #333; font-size: 0.9rem; }
        
        .latest-record-box { background: #f8f9fa; border: 1px solid #f0f0f0; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 10px; }
        .stat-box { text-align: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #eaeaea; }
        .stat-box small { display: block; color: #999; font-size: 0.65rem; text-transform: uppercase; }
        .stat-box b { font-size: 1rem; color: var(--sage-green); }

        .immunization-box { margin-top: 20px; }
        .immunization-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-top: 8px; border: 1px solid #e2e8f0; }
        .immunization-table th { background-color: #d4a373; color: white; padding: 10px; font-size: 0.75rem; text-align: center; border: 1px solid #c89664; }
        .immunization-table td { padding: 10px; border: 1px solid #e2e8f0; color: #2d3748; text-align: center; vertical-align: middle; }
        .immunization-table td:first-child { text-align: left; font-weight: 600; }

        .btn-delete { background: none; border: none; color: var(--danger-red); text-decoration: underline; cursor: pointer; font-weight: bold; }
        @media (max-width: 900px) { .summary-grid { grid-template-columns: repeat(2, minmax(140px, 1fr)); } }
        @media (max-width: 768px) {
            #main { margin-left: 0; padding: 20px 16px; }
            .modal-content { width: 92%; padding: 20px; }
        }

        /* Custom Popup Alert / Notification Modal Styling */
        .custom-alert-overlay {
            display: none;
            position: fixed;
            z-index: 5000;
            left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
        }
        .custom-alert-box {
            background: white;
            margin: 15% auto;
            padding: 25px;
            width: 350px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: modalPopUp 0.25s ease-in-out;
        }
        @keyframes modalPopUp {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }



















    </style>
</head>
<body>

<?php 
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin') { 
        include 'super_admin_sidebar.php'; 
    } 
    else { 
        include 'admin_sidebar.php'; 
    } 
?>

    <div id="main">
        <div class="records-card">
            <div class="header-section">
                <div>
                    <h2>Child Health Records</h2>
                    <p class="page-description">Child profiles and immunization history</p>
                </div>
                <div style="display:flex; gap:10px; align-items: center; flex-wrap: wrap;">
                    <form method="GET" class="filter-form">
                        <select name="age_filter" class="filter-select" aria-label="Filter by age">
                            <option value="all" <?= $age_filter == 'all' ? 'selected' : ''; ?>>All Ages</option>
                            <option value="0-1" <?= $age_filter == '0-1' ? 'selected' : ''; ?>>0 - 1 Month Old</option>
                            <option value="1-6" <?= $age_filter == '1-6' ? 'selected' : ''; ?>>1 - 6 Months Old</option>
                            <option value="6-12" <?= $age_filter == '6-12' ? 'selected' : ''; ?>>6 - 12 Months Old</option>
                            <option value="1-2" <?= $age_filter == '1-2' ? 'selected' : ''; ?>>1 - 2 Years Old</option>
                            <option value="2_above" <?= $age_filter == '2_above' ? 'selected' : ''; ?>>2 Years Old & Above</option>
                        </select>

                        <select name="dose_filter" class="filter-select" aria-label="Filter by vaccine doses">
                            <option value="all" <?= $dose_filter == 'all' ? 'selected' : ''; ?>>All Doses</option>
                            <option value="0" <?= $dose_filter == '0' ? 'selected' : ''; ?>>0 Dose (None)</option>
                            <option value="1-2" <?= $dose_filter == '1-2' ? 'selected' : ''; ?>>1 - 2 Doses</option>
                            <option value="3_plus" <?= $dose_filter == '3_plus' ? 'selected' : ''; ?>>3+ Doses</option>
                        </select>
                        
                        <input type="text" name="search" class="search-box" placeholder="Search child or mother..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Search child or mother">
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter); ?>">
                        <button type="submit" class="btn-search">Search</button>
                        <?php if ($search !== '' || $age_filter !== 'all' || $dose_filter !== 'all'): ?>
                            <a href="admin_child_rec.php" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>
                    <a href="admin_child_reg.php" class="btn-add">+ Register Child</a>
                </div>
            </div>

            <?php if ($child_load_error !== ''): ?>
                <div class="load-error"><?= htmlspecialchars($child_load_error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="summary-grid">
                <div class="summary-card"><span class="label">Total Children</span><strong><?= $summary['total']; ?></strong></div>
                <div class="summary-card"><span class="label">0 - 1 Month</span><strong><?= $summary['newborn']; ?></strong></div>
                <div class="summary-card"><span class="label">2 - 6 Months</span><strong><?= $summary['infant']; ?></strong></div>
                <div class="summary-card"><span class="label">7 - 12 Months</span><strong><?= $summary['older_infant']; ?></strong></div>
            </div>

            <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Child</th>
                        <th>Mother</th>
                        <th>Last Visit</th>
                        <th>Current Age</th>
                        <th>Vaccination Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($all_records)): ?>
                        <?php foreach($all_records as $row): ?>
                        <tr id="row_<?= $row['id']; ?>">
                            <td><span class="patient-name"><?= htmlspecialchars($row['child_name'] ?? $row['baby_name'] ?? 'Unnamed child', ENT_QUOTES, 'UTF-8'); ?></span><span class="patient-subtext">Born <?= !empty($row['birth_date']) ? date('M d, Y', strtotime($row['birth_date'])) : 'date not specified'; ?></span></td>
                            <td><?= htmlspecialchars($row['mother_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= !empty($row['last_visit']) ? date('M d, Y', strtotime($row['last_visit'])) : 'No visit recorded'; ?></td>
                            <td><span class="age-badge"><?= htmlspecialchars($row['current_age_label'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td>
                                <?php 
                                    $doses = $row['total_doses'] ?? 0;
                                    if ($doses > 0) {
                                        echo '<span class="vax-badge">Vaccinated (' . $doses . ' dose' . ($doses > 1 ? 's' : '') . ')</span>';
                                    } else {
                                        echo '<span class="vax-badge" style="background: #f1f2f6; color: #718096;">None (0 dose)</span>';
                                    }
                                ?>
                            </td>
                            <td><button class="view-btn" onclick='openModal(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'>View Details</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #888; padding: 30px;">No child records found matching the criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- Modal for Detailed Info & Immunization Monitoring -->
    <div id="infantModal" class="modal">
        <div class="modal-content">
            <h1 id="m_name" style="color: var(--sage-green); margin: 0 0 15px 0; font-size: 1.5rem;"></h1>
            
            <p style="font-size: 0.75rem; font-weight: bold; color: var(--sage-green); margin-bottom: 8px; text-transform: uppercase;">Verified Personal Information</p>
            <div class="info-card">
                <div class="info-item"><label>Mother's Name</label><span id="m_mother">--</span></div>
                <div class="info-item"><label>Father's Name</label><span id="m_father">--</span></div>
                <div class="info-item"><label>Birthday</label><span id="m_dob">--</span></div>
                <div class="info-item"><label>Gender</label><span id="m_gender">--</span></div>
                <div class="info-item"><label>Blood Type</label><span id="m_blood">--</span></div>
                <div class="info-item"><label>Place of Birth</label><span id="m_pob">--</span></div>
                <div class="info-item" style="grid-column: span 2;"><label>Address / Barangay</label><span id="m_address">--</span></div>
            </div>

            <div class="latest-record-box">
                <label style="font-size: 0.75rem; font-weight: bold; color: var(--sage-green);">LATEST HEALTH DATA</label>
                <div class="stat-grid">
                    <div class="stat-box"><small>Weight</small><b id="last_weight">--</b><small>kg</small></div>
                    <div class="stat-box"><small>Height</small><b id="last_height">--</b><small>cm</small></div>
                    <div class="stat-box"><small>Latest Vaccine</small><b id="last_vaccine" style="font-size: 0.85rem;">--</b></div>
                </div>
            </div>

            <div class="immunization-box">
                <label style="font-size: 0.75rem; font-weight: bold; color: var(--sage-green); text-transform: uppercase;">Immunization Monitoring Table</label>
                <div style="overflow-x: auto; margin-top: 5px;">
                    <table class="immunization-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Bakuna</th>
                                <th style="width: 12%;">Doses</th>
                                <th style="width: 20%;">Petsa ng bakuna</th>
                                <th style="width: 20%;">Nagturok</th>
                                <th style="width: 18%;">Remarks / Notes</th>
                            </tr>
                        </thead>
                        <tbody id="immunization_rows">
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-top: 25px; display: flex; justify-content: space-between; align-items: center;">
                <button type="button" onclick="confirmDelete()" class="btn-delete">Delete Record</button>
                <button onclick="closeModal()" style="background:#eee; border:none; padding: 10px 20px; border-radius:10px; cursor:pointer; font-weight:600;">Close</button>
            </div>
        </div>
    </div>

    <!-- Sub-Modal para sa Timbang, Tangkad, at Notes -->
    <div id="healthRecordModal" class="sub-modal">
        <div class="sub-modal-content">
            <h3 style="color: #2b6cb0; margin-top: 0; font-size: 1.1rem;">Vaccination Health Record</h3>
            <hr style="border:0; border-top:1px solid #eee; margin-bottom:15px;">
            <p><strong>Timbang (Weight):</strong> <span id="sub_weight">--</span> kg</p>
            <p><strong>Tangkad (Height):</strong> <span id="sub_height">--</span> cm</p>
            <p><strong>Medical Notes / Remarks:</strong></p>
            <div id="sub_remarks" style="background: #f7fafc; padding: 10px; border-radius: 6px; font-size: 0.85rem; color: #2d3748; border: 1px solid #e2e8f0; min-height: 40px;">--</div>
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeHealthRecordModal()" style="background:#cbd5e0; border:none; padding: 6px 14px; border-radius:6px; cursor:pointer; font-weight:600; font-size:0.8rem;">Isara</button>
            </div>
        </div>
    </div>

    <!-- CUSTOM CONFIRMATION MODAL PARA SA DELETE -->
    <div id="confirmDeleteModal" class="custom-alert-overlay">
        <div class="custom-alert-box">
            <div style="font-size: 40px; color: #DD6B20; margin-bottom: 10px;">⚠️</div>
            <h3 style="color: #2D3748; margin: 0 0 10px 0;">Confirm Delete</h3>
            <p id="confirmDeleteText" style="color: #4A5568; font-size: 0.9rem; margin-bottom: 20px;">Permanently delete this record?</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button onclick="closeConfirmModal()" style="background: #CBD5E0; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button onclick="executeDelete()" style="background: #E53E3E; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: 600; cursor: pointer;">Delete</button>
            </div>
        </div>
    </div>

    <!-- CUSTOM NOTIFICATION / ALERT MODAL -->
    <div id="customAlertModal" class="custom-alert-overlay">
        <div class="custom-alert-box">
            <div id="alertIcon" style="font-size: 45px; margin-bottom: 10px;">ℹ️</div>
            <h3 id="alertTitle" style="color: var(--sage-green); margin: 0 0 10px 0;">Notice</h3>
            <p id="alertMessage" style="color: #4A5568; font-size: 0.9rem; margin-bottom: 20px;">Message text here...</p>
            <button onclick="closeCustomAlert()" style="background: var(--sage-green); color: white; border: none; width: 100%; padding: 10px; border-radius: 6px; font-weight: 600; cursor: pointer;">OK</button>
        </div>
    </div>

<script>
    let currentChildId = null;

    // Helper function para sa custom alert modal
    function showCustomAlert(title, message, isError = false) {
        document.getElementById('alertTitle').innerText = title;
        document.getElementById('alertMessage').innerText = message;
        document.getElementById('alertIcon').innerText = isError ? "❌" : "✔";
        document.getElementById('alertTitle').style.color = isError ? "#E53E3E" : "var(--sage-green)";
        document.getElementById('customAlertModal').style.display = 'block';
    }

    function closeCustomAlert() {
        document.getElementById('customAlertModal').style.display = 'none';
    }

    function openModal(data) {
        currentChildId = data.id;
        document.getElementById('m_name').innerText = data.child_name || data.baby_name;
        
        document.getElementById('m_mother').innerText = data.mother_name || "N/A";
        document.getElementById('m_father').innerText = data.father_name || "N/A";
        document.getElementById('m_dob').innerText = data.birth_date ? new Date(data.birth_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'}) : "N/A";
        document.getElementById('m_gender').innerText = data.gender || "N/A";
        document.getElementById('m_blood').innerText = data.blood_type || "N/A";
        document.getElementById('m_pob').innerText = data.place_of_birth || "N/A";
        
        let fullAddress = (data.address ? data.address + ", " : "") + (data.barangay || "");
        document.getElementById('m_address').innerText = fullAddress !== "" ? fullAddress : "N/A";
        
        let latestWeight = data.weight_kg || "--";
        let latestHeight = data.height || "--";
        let latestVaccine = data.vaccine_taken || "None";

        if (data.history && data.history.length > 0) {
            let latestRec = data.history[0];
            if (latestRec.weight_kg) latestWeight = latestRec.weight_kg;
            if (latestRec.height) latestHeight = latestRec.height;
            if (latestRec.vaccine_taken) latestVaccine = latestRec.vaccine_taken;
        }

        document.getElementById('last_weight').innerText = latestWeight;
        document.getElementById('last_height').innerText = latestHeight;
        document.getElementById('last_vaccine').innerText = latestVaccine;
        
        const standardVaccines = [
            { name: "BCG Vaccine", keywords: ["bcg"], doseNum: "1", schedule: "At birth" },
            { name: "Hepatitis B Vaccine", keywords: ["hepatitis", "hep b"], doseNum: "1", schedule: "At birth" },
            { name: "Pentavalent Vaccine (DPT-Hep B-HIB)", keywords: ["pentavalent", "penta"], doseNum: "1", schedule: "1½ mos" },
            { name: "Pentavalent Vaccine (DPT-Hep B-HIB)", keywords: ["pentavalent", "penta"], doseNum: "2", schedule: "2½ mos" },
            { name: "Pentavalent Vaccine (DPT-Hep B-HIB)", keywords: ["pentavalent", "penta"], doseNum: "3", schedule: "3½ mos" },
            { name: "Oral Polio Vaccine (OPV)", keywords: ["opv", "polio"], doseNum: "1", schedule: "1½ mos" },
            { name: "Oral Polio Vaccine (OPV)", keywords: ["opv", "polio"], doseNum: "2", schedule: "2½ mos" },
            { name: "Oral Polio Vaccine (OPV)", keywords: ["opv", "polio"], doseNum: "3", schedule: "3½ mos" },
            { name: "Inactivated Polio Vaccine (IPV)", keywords: ["ipv"], doseNum: "1", schedule: "3½ mos" },
            { name: "Inactivated Polio Vaccine (IPV)", keywords: ["ipv"], doseNum: "2", schedule: "9 mos" },
            { name: "Pneumococcal Conjugate Vaccine (PCV)", keywords: ["pcv"], doseNum: "1", schedule: "1½ mos" },
            { name: "Pneumococcal Conjugate Vaccine (PCV)", keywords: ["pcv"], doseNum: "2", schedule: "2½ mos" },
            { name: "Pneumococcal Conjugate Vaccine (PCV)", keywords: ["pcv"], doseNum: "3", schedule: "3½ mos" },
            { name: "Measles, Mumps, Rubella Vaccine (MMR)", keywords: ["mmr", "measles"], doseNum: "1", schedule: "9 mos" },
            { name: "Measles, Mumps, Rubella Vaccine (MMR)", keywords: ["mmr", "measles"], doseNum: "2", schedule: "1 year" }
        ];

        let allSources = [...(data.history || [])];

        let immHtml = '';
        standardVaccines.forEach(vac => {
            let matchedRecord = null;

            if (allSources.length > 0) {
                matchedRecord = allSources.find(h => {
                    let vTaken = h.vaccine_taken ? h.vaccine_taken.toLowerCase() : '';
                    let remarks = h.remarks ? h.remarks.toLowerCase() : '';
                    
                    let matchesKeyword = vac.keywords.some(kw => vTaken.includes(kw));
                    let doseMatch = vTaken.match(/\bdose\s*(\d+)\b/i)
                        || vTaken.match(/\b(\d+)(?:st|nd|rd|th)?\s*dose\b/i)
                        || remarks.match(/\bdose\s*(\d+)\b/i)
                        || remarks.match(/\b(\d+)(?:st|nd|rd|th)?\s*dose\b/i);
                    let vaccineDoseOptions = standardVaccines.filter(candidate =>
                        candidate.keywords.some(kw => vTaken.includes(kw))
                    );
                    let matchesDose = doseMatch
                        ? doseMatch[1] === vac.doseNum
                        : vaccineDoseOptions.length === 1 && vaccineDoseOptions[0] === vac;
                    
                    return matchesKeyword && matchesDose;
                });
            }

            let dateTaken = '--';
            let administeredBy = '--';
            let recordBtnHtml = '<span style="color:#a0aec0; font-style:italic; font-size:0.75rem;">No record</span>';

            if (matchedRecord) {
                let rawDate = matchedRecord.vaccine_date
                    || (matchedRecord.record_source === 'legacy' ? '' : matchedRecord.created_at);
                if (rawDate) {
                    dateTaken = rawDate.split(' ')[0];
                }
                administeredBy = matchedRecord.administered_by || data.administered_by || 'Health Worker';
                
                let encodedRecord = encodeURIComponent(JSON.stringify(matchedRecord));
                recordBtnHtml = `<button type="button" class="btn-health-record" onclick='openHealthRecordModal("${encodedRecord}")'>Health Record</button>`;
            }

            immHtml += `<tr>
                <td>${vac.name} <br><small style="color:#718096; font-size:0.65rem;">Rec: ${vac.schedule}</small></td>
                <td><span style="background:#edf2f7; padding:2px 6px; border-radius:4px; font-weight:600; font-size:0.75rem;">Dose ${vac.doseNum}</span></td>
                <td>${dateTaken !== '--' ? dateTaken : '<span style="color:#cbd5e0;">-- / -- / ----</span>'}</td>
                <td style="font-size: 0.75rem; font-weight: 500; color: #4a5568;">${administeredBy}</td>
                <td style="text-align: center;">${recordBtnHtml}</td>
            </tr>`;
        });

        document.getElementById('immunization_rows').innerHTML = immHtml;
        document.getElementById('infantModal').style.display = "block";
    }

    function closeModal() { document.getElementById('infantModal').style.display = "none"; }

    function openHealthRecordModal(encodedData) {
        let record = JSON.parse(decodeURIComponent(encodedData));
        document.getElementById('sub_weight').innerText = record.weight_kg || record.weight || '--';
        document.getElementById('sub_height').innerText = record.height || '--';
        document.getElementById('sub_remarks').innerText = record.remarks || 'No notes available for this immunization date.';
        document.getElementById('healthRecordModal').style.display = "block";
    }

    function closeHealthRecordModal() {
        document.getElementById('healthRecordModal').style.display = "none";
    }

    // Modal Delete Trigger
    function confirmDelete() {
        let childName = document.getElementById('m_name').innerText;
        document.getElementById('confirmDeleteText').innerText = "Permanently delete " + childName + "'s record?";
        document.getElementById('confirmDeleteModal').style.display = "block";
    }

    function closeConfirmModal() {
        document.getElementById('confirmDeleteModal').style.display = "none";
    }

    // Actual AJAX Delete Execution
    function executeDelete() {
        closeConfirmModal();
        const formData = new FormData();
        formData.append('child_id', currentChildId);
        
        fetch('../delete_infant.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(result => {
            if (result.trim() === "success") {
                const row = document.getElementById('row_' + currentChildId);
                if(row) row.remove();
                closeModal();
                showCustomAlert("Success", "Record successfully deleted!");
            } else { 
                showCustomAlert("Error", result, true);
            }
        }).catch(err => {
            showCustomAlert("Connection Error", "Make sure delete_infant.php exists.", true);
        });
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('infantModal')) closeModal();
        if (event.target == document.getElementById('healthRecordModal')) closeHealthRecordModal();
        if (event.target == document.getElementById('confirmDeleteModal')) closeConfirmModal();
        if (event.target == document.getElementById('customAlertModal')) closeCustomAlert();
    }
</script>
</body>
</html>