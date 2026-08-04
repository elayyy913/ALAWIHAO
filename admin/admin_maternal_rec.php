<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. DELETE LOGIC
if (isset($_GET['delete_id'])) {
    $id_to_delete = mysqli_real_escape_string($conn, $_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM maternal_records WHERE mother_id = '$id_to_delete'");
    mysqli_query($conn, "DELETE FROM pregnancy_history WHERE patient_id = '$id_to_delete'");
    if (mysqli_query($conn, "DELETE FROM maternal_registration WHERE id = '$id_to_delete'")) {
        header("Location: admin_maternal_rec.php?msg=deleted");
        exit();
    }
}

// 2. AJAX ENDPOINT FOR PATIENT FULL RECORD HISTORY & REGISTRATION INFO
if (isset($_GET['fetch_patient_details'])) {
    header('Content-Type: application/json');

    $mother_id = mysqli_real_escape_string($conn, $_GET['fetch_patient_details']);

    $reg_q = mysqli_query($conn, "SELECT *, CONCAT(COALESCE(street,''), ', ', COALESCE(barangay,''), ', ', COALESCE(municipality,'')) AS full_address FROM maternal_registration WHERE id = '$mother_id' LIMIT 1");
    $reg_data = mysqli_fetch_assoc($reg_q) ?: [];

    $history_q = mysqli_query($conn, "SELECT * FROM pregnancy_history WHERE patient_id = '$mother_id' ORDER BY id DESC LIMIT 1");
    $history_data = mysqli_fetch_assoc($history_q) ?: [];

    $visits_q = mysqli_query($conn, "SELECT * FROM maternal_records WHERE mother_id = '$mother_id' ORDER BY checkup_date DESC");
    $visits_data = [];
    while ($v = mysqli_fetch_assoc($visits_q)) {
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

    echo json_encode([
        'registration_info' => $reg_data,
        'medical_history' => $history_data,
        'checkup_visits' => $visits_data
    ]);
    exit();
}

// 3. FETCH PATIENTS MASTERLIST WITH SEARCH & TRIMESTER FILTER
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$trimester_filter = isset($_GET['trimester_filter']) ? mysqli_real_escape_string($conn, $_GET['trimester_filter']) : '';

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
         AND (u.client_fname LIKE '%$search%' OR u.client_lname LIKE '%$search%')";

$query .= " ORDER BY u.created_at DESC";

$result = mysqli_query($conn, $query);

$patients = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ga = intval($row['gestational_age_weeks'] ?? 0);
    
    // Compute current AOG and Remaining to full-term
    $row['current_aog'] = ($ga > 0) ? $ga . ' weeks' : 'N/A';
    $remaining = ($ga > 0) ? max(0, 40 - $ga) : -1;
    $row['computed_remaining'] = ($remaining >= 0) ? $remaining . ' weeks left' : 'N/A';

    // Trimester Filter Logic
    if ($trimester_filter !== '') {
        if ($ga > 0) {
            if ($trimester_filter === '1' && $ga >= 1 && $ga <= 13) {
                $patients[] = $row;
            } elseif ($trimester_filter === '2' && $ga >= 14 && $ga <= 27) {
                $patients[] = $row;
            } elseif ($trimester_filter === '3' && $ga >= 28) {
                $patients[] = $row;
            }
        }
    } else {
        $patients[] = $row;
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
            --bg-beige: #fcfdfa; 
            --danger-red: #d9534f; 
        }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-beige); margin: 0; display: flex; }
        #main { width: 100%; padding: 40px; box-sizing: border-box; min-height: 100vh; margin-left: 280px; }
        .records-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        h2 { color: var(--sage-green); font-size: 1.8rem; margin: 0; }
        
        .filter-form { display: flex; gap: 10px; align-items: center; }
        .search-box, .filter-select { padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem; outline: none; }
        .search-box { width: 200px; }
        .filter-select { background: white; cursor: pointer; }
        
        .btn-add { background-color: var(--sage-green); color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .btn-add:hover { background-color: #5a7b45; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead { background-color: var(--sage-green); }
        th { color: white; padding: 12px 15px; text-align: left; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        
        .view-btn { background: transparent; color: var(--sage-green); border: 1.5px solid var(--sage-green); padding: 6px 14px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .view-btn:hover { background: var(--sage-green); color: white; }

        /* MODAL STYLES */
        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 3% auto; padding: 35px; border-radius: 20px; width: 850px; position: relative; max-height: 85vh; overflow-y: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        
        /* CONFIRMATION MODAL CARD STYLES */
        .confirm-modal-content { background: white; margin: 15% auto; padding: 30px; border-radius: 16px; width: 400px; text-align: center; position: relative; box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
        .confirm-modal-content h3 { color: #333; margin-top: 0; margin-bottom: 10px; font-size: 1.3rem; }
        .confirm-modal-content p { color: #666; font-size: 0.9rem; margin-bottom: 25px; line-height: 1.4; }
        .confirm-actions { display: flex; justify-content: center; gap: 12px; }
        .btn-confirm-yes { background-color: var(--danger-red); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-confirm-yes:hover { opacity: 0.9; }
        .btn-confirm-no { background-color: #eee; color: #333; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-confirm-no:hover { background-color: #ddd; }

        /* TABS */
        .tab-menu { display: flex; border-bottom: 2px solid #eee; margin-top: 20px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; cursor: pointer; background: none; border: none; font-weight: 600; color: #888; font-size: 0.95rem; border-bottom: 3px solid transparent; }
        .tab-btn.active { color: var(--sage-green); border-bottom-color: var(--sage-green); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .info-card { background: #fdfdfd; border: 1px dashed var(--sage-green); padding: 15px; border-radius: 12px; margin-bottom: 15px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .info-item label { display: block; font-size: 0.68rem; color: #888; text-transform: uppercase; font-weight: bold; }
        .info-item span { font-weight: 600; color: #333; font-size: 0.9rem; }
        
        .section-title { font-size: 0.95rem; color: var(--sage-green); font-weight: bold; margin-top: 15px; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.85rem; line-height: 1.5; }

        .btn-delete { background: none; border: none; color: var(--danger-red); text-decoration: underline; cursor: pointer; font-weight: 600; }
        .alert { background: #dff0d8; color: #3c763d; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
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
                <div style="display:flex; gap:10px; align-items: center;">
                    <form method="GET" class="filter-form">
                        <select name="trimester_filter" class="filter-select" onchange="this.form.submit()">
                            <option value="">-- Filter by Trimester --</option>
                            <option value="1" <?= ($trimester_filter == '1') ? 'selected' : '' ?>>1st Trimester (1 - 13 wks)</option>
                            <option value="2" <?= ($trimester_filter == '2') ? 'selected' : '' ?>>2nd Trimester (14 - 27 wks)</option>
                            <option value="3" <?= ($trimester_filter == '3') ? 'selected' : '' ?>>3rd Trimester (28+ wks)</option>
                        </select>
                        <input type="text" name="search" class="search-box" placeholder="Search name..." value="<?= htmlspecialchars($search) ?>">
                    </form>
                    <a href="admin_maternal_reg.php" class="btn-add">+ Register Patient</a>
                </div>
            </div>

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
                            <td style="font-weight:600;"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= $row['checkup_date'] ? date('M d, Y', strtotime($row['checkup_date'])) : 'No records' ?></td>
                            <td>
                                <span style="color: #555; font-weight: 600;"><?= htmlspecialchars($row['current_aog']) ?></span>
                            </td>
                            <td>
                                <?php if($row['computed_remaining'] !== 'N/A'): ?>
                                    <span style="background: #eef4ec; color: var(--sage-green); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
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
                            <td colspan="5" style="text-align: center; color: #888; padding: 20px;">Walang nakitang rekord ng pasyente.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- READ-ONLY PATIENT DETAILS MODAL -->
    <div id="maternalModal" class="modal">
        <div class="modal-content">
            <h1 id="m_name" style="color: var(--sage-green); margin: 0 0 10px 0; font-size: 1.8rem;"></h1>
            
            <div class="info-card">
                <div class="info-item"><label>Age</label><span id="m_age">--</span></div>
                <div class="info-item"><label>Contact</label><span id="m_contact">--</span></div>
                <div class="info-item"><label>Blood Type</label><span id="m_blood">--</span></div>
                <div class="info-item"><label>LMP</label><span id="m_lmp">--</span></div>
            </div>

            <!-- TABS -->
            <div class="tab-menu">
                <button class="tab-btn active" onclick="switchTab(event, 'tab-history')">Medical & Verification History (DOH Form)</button>
                <button class="tab-btn" onclick="switchTab(event, 'tab-visits')">Check-up Visits History</button>
            </div>

            <!-- TAB 1: MEDICAL & VERIFICATION HISTORY (DOH FORM) -->
            <div id="tab-history" class="tab-content active">
                <div style="background:#fafafa; border:1px solid #eee; padding:20px; border-radius:10px; font-size:0.85rem; line-height:1.6;">
                    
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
            <h3>Kumpirmahin ang Pag-delete</h3>
            <p>Sigurado ka ba na gusto mong tanggalin ang rekord na ito? Ang aksyong ito ay hindi na maibabalik.</p>
            <div class="confirm-actions">
                <button type="button" class="btn-confirm-no" onclick="closeConfirmModal()">I-cancel</button>
                <button type="button" class="btn-confirm-yes" onclick="executeDelete()">Oo, I-delete</button>
            </div>
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
            fetch(`admin_maternal_rec.php?fetch_patient_details=${data.id}`)
                .then(res => res.json())
                .then(resData => {
                    const reg = resData.registration_info || {};
                    document.getElementById('m_address').innerText = reg.full_address || "N/A";
                    document.getElementById('m_spouse').innerText = ((reg.spouse_fname || '') + ' ' + (reg.spouse_lname || '')).trim() || "N/A";
                    document.getElementById('m_educ').innerText = reg.highest_educ || "N/A";
                    document.getElementById('m_occupation').innerText = reg.occupation || "N/A";

                    const med = resData.medical_history || {};
                    document.getElementById('m_g').innerText = med.gravida || 0;
                    document.getElementById('m_p').innerText = med.para || 0;
                    document.getElementById('m_ft').innerText = med.full_term || 0;
                    document.getElementById('m_pre').innerText = med.premature || 0;
                    document.getElementById('m_ab').innerText = med.abortion || 0;
                    document.getElementById('m_lc').innerText = med.living_children || 0;
                    
                    document.getElementById('m_pe_vitals').innerText = med.physical_exam_vitals || med.vitals || "None specified";
                    document.getElementById('m_pe_findings').innerText = med.physical_exam_findings || "None specified";
                    document.getElementById('m_tt_status').innerText = med.tt_status || "None specified";
                    document.getElementById('m_muac').innerText = med.muac || "None specified";
                    document.getElementById('m_bmi').innerText = med.bmi || "None specified";

                    document.getElementById('m_heent').innerText = med.heent_findings || med.heent || "None specified";
                    document.getElementById('m_neck').innerText = med.neck || "None specified";
                    
                    let breastFindings = med.breast || "None specified";
                    let breastDisplay = breastFindings;
                    if (med.breast_left_size || med.breast_right_size) {
                        breastDisplay += ` — Left: ${med.breast_left_size || 'None'}, Right: ${med.breast_right_size || 'None'}`;
                    }
                    document.getElementById('m_breast').innerText = breastDisplay;
                    
                    document.getElementById('m_chest').innerText = med.chest_heart || med.chest || "None specified";
                    document.getElementById('m_abdomen').innerText = med.abdomen_med || med.abdomen || "None specified";
                    document.getElementById('m_genital').innerText = med.genital_med || med.genital || "None specified";
                    document.getElementById('m_extremities').innerText = med.extremities_med || med.extremities || "None specified";
                    document.getElementById('m_skin').innerText = med.skin_med || med.skin || "None specified";

                    document.getElementById('m_fh').innerText = med.family_history_details || med.family_history || "None specified";
                    document.getElementById('m_phh').innerText = med.past_health_details || med.past_health_history || "None specified";
                    document.getElementById('m_sh').innerText = med.social_history_details || med.social_history || "None specified";

                    document.getElementById('m_prev_fp').innerText = med.prev_fp_method || "None specified";
                    document.getElementById('m_fp_dur').innerText = med.fp_duration || "None specified";

                    document.getElementById('m_past_lmp').innerText = med.past_lmp || "N/A";
                    document.getElementById('m_bleeding').innerText = med.bleeding_duration_days ? med.bleeding_duration_days + ' days' : "N/A";
                    document.getElementById('m_attendant').innerText = med.last_delivery_attendant || "N/A";

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
                document.getElementById('confirmModal').style.display = "block";
            }
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = "none";
        }

        function executeDelete() {
            if (currentMotherId) {
                window.location.href = `admin_maternal_rec.php?delete_id=${currentMotherId}`;
            }
        }
    </script>
</body>
</html>