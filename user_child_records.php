<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; 

// Logic para sa Delete/Remove
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $del_query = "DELETE FROM children WHERE id = ? AND user_id = ? AND status != 'Approved'";
    $del_stmt = $conn->prepare($del_query);
    $del_stmt->bind_param("ii", $delete_id, $user_id);
    $del_stmt->execute();
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

$query = "SELECT c.*, 
                 c.weight_kg AS weight_kg, 
                 c.height_cm AS height, 
                 COALESCE(c.vaccine_taken, 'None') AS vaccine_taken,
                 c.birth_date AS r_dob, c.administered_by,
                 TIMESTAMPDIFF(YEAR, c.birth_date, CURDATE()) AS age_years, 
                 TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) % 12 AS age_months 
          FROM children c
          WHERE c.user_id = ?
          ORDER BY c.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_children = $stmt->get_result();

$my_records = [];
while ($row = $result_children->fetch_assoc()) {
    $child_current_id = $row['id'];
    $history_arr = [];

    $hist_query = "SELECT * FROM infant_records WHERE child_id = ? ORDER BY created_at DESC";
    $hist_stmt = $conn->prepare($hist_query);
    $hist_stmt->bind_param("i", $child_current_id);
    $hist_stmt->execute();
    $hist_result = $hist_stmt->get_result();

    while($hist = $hist_result->fetch_assoc()) {
        $history_arr[] = [
            'vaccine_taken' => $hist['vaccine_taken'] ?? ($hist['vaccine_name'] ?? ''),
            'vaccine_date' => $hist['vaccine_date'] ?? ($hist['created_at'] ? date('Y-m-d', strtotime($hist['created_at'])) : ''),
            'administered_by' => $hist['administered_by'] ?? 'Health Worker',
            'remarks' => $hist['remarks'] ?? 'No notes yet',
            'weight_kg' => $hist['weight'] ?? ($hist['weight_kg'] ?? null),
            'height' => $hist['height'] ?? ($hist['height_cm'] ?? ($hist['length'] ?? null))
        ];
    }

    if(!empty($row['vaccine_taken']) && $row['vaccine_taken'] != 'None') {
        $found_in_history = false;
        foreach($history_arr as $h) {
            if(isset($h['vaccine_taken']) && strtolower($h['vaccine_taken']) == strtolower($row['vaccine_taken'])) {
                $found_in_history = true;
                break;
            }
        }
        if(!$found_in_history) {
            $history_arr[] = [
                'vaccine_taken' => $row['vaccine_taken'],
                'vaccine_date' => $row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : date('Y-m-d'),
                'administered_by' => $row['administered_by'] ?? 'Health Worker',
                'remarks' => 'Registered record',
                'weight_kg' => $row['weight_kg'],
                'height' => $row['height']
            ];
        }
    }
    
    $row['history'] = $history_arr;

    // Bilangin kung ilan ang total doses na nakuha base sa history
    $valid_doses = array_filter($history_arr, function($h) {
        return !empty($h['vaccine_taken']) && strtolower($h['vaccine_taken']) != 'none';
    });
    $row['total_doses'] = count($valid_doses);

    $my_records[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Health Records | Alawihao</title>
    <style>
        :root { 
            --primary-green: #718355; 
            --bg-color: #f8faf5; 
            --white: #ffffff; 
            --dark-gray: #2d3436;
            --border-color: #e5eadc;
            --danger-red: #b33939;
        }

        body { 
            font-family: 'Times New Roman', serif; 
            background-color: var(--bg-color); 
            margin: 0; 
            display: flex; 
        }

        #main { 
            margin-left: 260px; 
            width: calc(100% - 260px); 
            padding-bottom: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header { 
            width: 100%; 
            background: var(--white); 
            padding: 25px 40px; 
            border-bottom: 3px solid var(--primary-green); 
            box-sizing: border-box; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 10px rgba(113, 131, 85, 0.05); 
        }

        .table-container { 
            background: var(--white); 
            width: 92%; 
            padding: 35px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(113, 131, 85, 0.06); 
            border: 1px solid var(--border-color);
            border-top: 8px solid var(--primary-green); 
            box-sizing: border-box; 
        }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { 
            background: #f4f7f0; 
            color: var(--primary-green); 
            padding: 16px; 
            text-align: left; 
            border-bottom: 2px solid var(--border-color); 
            text-transform: uppercase; 
            font-size: 0.7rem; 
            letter-spacing: 1.5px;
        }
        td { padding: 16px; border-bottom: 1px solid #f2f5ee; color: var(--dark-gray); font-size: 0.9rem; }
        tr:hover { background-color: #fafbf8; }

        .btn-register {
            background-color: var(--primary-green);
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .btn-register:hover { background-color: #5a6a44; }

        .status-badge { 
            padding: 6px 12px; 
            border-radius: 4px; 
            font-size: 0.7rem; 
            font-weight: bold; 
            text-transform: uppercase;
        }
        .status-approved { background: #f0f4e8; color: var(--primary-green); border: 1px solid var(--primary-green); }
        .status-pending { background: #fffcf0; color: #d4a017; border: 1px solid #d4a017; }
        
        .vax-badge {
            background: #eef3ec;
            color: var(--primary-green);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .action-btns { display: flex; gap: 8px; align-items: center; }
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            border: none;
            text-transform: uppercase;
        }
        .details-btn { background: var(--primary-green); color: white; }
        .details-btn:hover { background: #5a6a44; }
        .remove-btn { background: transparent; color: var(--danger-red); border: 1px solid var(--danger-red); }
        .remove-btn:hover { background: #fdf0f0; }

        /* Main Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(45, 52, 54, 0.5);
            backdrop-filter: blur(3px);
            overflow-y: auto;
        }
        .modal-content {
            background-color: var(--white);
            margin: 3% auto;
            padding: 35px;
            width: 900px;
            max-height: 88vh;
            overflow-y: auto;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            border-top: 8px solid var(--primary-green);
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        /* Sub-Modal styles para sa Health Record details galing sa admin */
        .sub-modal { 
            display: none; 
            position: fixed; 
            z-index: 4000; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.4); 
        }
        .sub-modal-content { 
            background: white; 
            margin: 10% auto; 
            padding: 25px; 
            border-radius: 12px; 
            width: 400px; 
            position: relative; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
        }
        .btn-health-record { 
            background-color: #2b6cb0; 
            color: white; 
            border: none; 
            padding: 5px 10px; 
            border-radius: 5px; 
            font-size: 0.75rem; 
            font-weight: 600; 
            cursor: pointer; 
            text-transform: uppercase;
        }
        .btn-health-record:hover { background-color: #2c5282; }

        /* Immunization Monitoring Table Style */
        .immunization-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-top: 8px; border: 1px solid #e2e8f0; }
        .immunization-table th { background-color: #b08d57; color: white; padding: 10px; font-size: 0.75rem; text-align: center; border: 1px solid #c89664; }
        .immunization-table td { padding: 10px; border: 1px solid #e2e8f0; color: #2d3748; text-align: center; vertical-align: middle; }
        .immunization-table td:first-child { text-align: left; font-weight: 600; }
    </style>
</head>
<body>

<?php include 'user_sidebar.php'; ?>

<div id="main">
    <div class="header">
        <h3 style="margin:0; letter-spacing: 4px; color: var(--primary-green); font-weight: 900;">ALAWIHAO HEALTH CENTER</h3>
    </div>

    <div class="table-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="color: var(--primary-green); margin: 0; letter-spacing: 1px;">CHILD & INFANT RECORDS</h2>
                <p style="color: #777; font-size: 0.8rem; margin-top: 5px;">Manage your children's profiles, age tracking, and vaccination history.</p>
            </div>
            <a href="user_reg_newborn.php" class="btn-register">ADD NEW CHILD</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Child Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Vaccination Status</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($my_records)): ?>
                    <?php foreach($my_records as $row): ?>
                    <tr>
                        <td style="font-weight: bold; color: #444;"><?php echo htmlspecialchars($row['child_name']); ?></td>
                        <td><?php echo "{$row['age_years']} yrs, {$row['age_months']} mos"; ?></td>
                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
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
                        <td>
                            <span class="status-badge <?php echo ($row['status'] == 'Approved') ? 'status-approved' : 'status-pending'; ?>">
                                <?php echo ($row['status'] == 'Approved') ? 'Verified' : 'Pending'; ?>
                            </span>
                        </td>
                        <td class="action-btns">
                            <button class="btn details-btn" onclick='openModal(<?php echo json_encode($row); ?>)'>History</button>
                            
                            <?php if($row['status'] !== 'Approved'): ?>
                                <a href="?delete_id=<?php echo $row['id']; ?>" class="btn remove-btn" onclick="return confirm('Remove this pending registration?')">Remove</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding: 50px; color: #aaa;">Walang nahanap na record ng mga bata.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Main Modal for Detailed Info & Immunization Monitoring -->
<div id="infantModal" class="modal">
    <div class="modal-content">
        <h3 id="m_name" style="color: var(--primary-green); border-bottom: 2px solid #f4f7f0; padding-bottom: 12px; margin-bottom: 20px;">Child Full History</h3>
        
        <!-- Personal Information Section -->
        <p style="font-size: 0.75rem; font-weight: bold; color: var(--primary-green); margin-bottom: 8px; text-transform: uppercase;">Verified Personal Information</p>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; background: #fdfdfd; padding: 15px; border-radius: 10px; border: 1px dashed var(--primary-green); margin-bottom: 20px; font-size: 0.85rem;">
            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Mother's Name</small><br><span id="m_mother">--</span></div>
            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Father's Name</small><br><span id="m_father">--</span></div>
            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Birthday</small><br><span id="m_dob">--</span></div>
            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Gender</small><br><span id="m_gender">--</span></div>
            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Blood Type</small><br><span id="m_blood">--</span></div>
            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Place of Birth</small><br><span id="m_pob">--</span></div>
            <div style="grid-column: span 3;"><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Address / Barangay</small><br><span id="m_address">--</span></div>
        </div>

        <!-- Latest Health Data Section -->
        <div style="background: #f8f9fa; border: 1px solid #f0f0f0; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
            <label style="font-size: 0.75rem; font-weight: bold; color: var(--primary-green);">LATEST HEALTH DATA</label>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px;">
                <div style="text-align: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #eaeaea;"><small style="display:block; color:#999; font-size:0.65rem; text-transform:uppercase;">Weight</small><b id="last_weight" style="font-size:1rem; color:var(--primary-green);">--</b><small>kg</small></div>
                <div style="text-align: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #eaeaea;"><small style="display:block; color:#999; font-size:0.65rem; text-transform:uppercase;">Height</small><b id="last_height" style="font-size:1rem; color:var(--primary-green);">--</b><small>cm</small></div>
                <div style="text-align: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #eaeaea;"><small style="display:block; color:#999; font-size:0.65rem; text-transform:uppercase;">Latest Vaccine</small><b id="last_vaccine" style="font-size: 0.85rem; color:var(--primary-green);">--</b></div>
            </div>
        </div>

        <!-- Immunization Monitoring Table -->
        <div style="margin-top: 20px;">
            <label style="font-size: 0.75rem; font-weight: bold; color: var(--primary-green); text-transform: uppercase;">Immunization Monitoring Table</label>
            <div style="overflow-x: auto; margin-top: 5px;">
                <table class="immunization-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Bakuna</th>
                            <th style="width: 12%;">Doses</th>
                            <th style="width: 20%;">Petsa ng bakuna</th>
                            <th style="width: 15%;">Nagturok</th>
                            <th style="width: 18%;">Remarks / Notes</th>
                        </tr>
                    </thead>
                    <tbody id="immunization_rows">
                        <!-- Dynamic rows loaded via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; border-top: 1px solid #f2f5ee; padding-top: 15px;">
            <div id="modalFooterDelete"></div>
            <button onclick="closeModal()" style="background: #f1f2f6; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; color: #2d3436; cursor: pointer; text-transform: uppercase; font-size: 0.75rem;">Close</button>
        </div>
    </div>
</div>

<!-- Sub-Modal para sa Timbang, Tangkad, at Notes (Gaya ng sa Admin) -->
<div id="healthRecordModal" class="sub-modal">
    <div class="sub-modal-content">
        <h3 style="color: #2b6cb0; margin-top: 0; font-size: 1.1rem;">Vaccination Health Record</h3>
        <hr style="border:0; border-top:1px solid #eee; margin-bottom:15px;">
        <p><strong>Timbang (Weight):</strong> <span id="sub_weight">--</span> kg</p>
        <p><strong>Tangkad (Height):</strong> <span id="sub_height">--</span> cm</p>
        <p><strong>Medical Notes / Remarks:</strong></p>
        <div id="sub_remarks" style="background: #f7fafc; padding: 10px; border-radius: 6px; font-size: 0.85rem; color: #2d3748; border: 1px solid #e2e8f0; min-height: 40px;">--</div>
        <div style="text-align: right; margin-top: 20px;">
            <button type="button" onclick="closeHealthRecordModal()" style="background:#cbd5e0; border:none; padding: 6px 14px; border-radius:6px; cursor:pointer; font-weight:600; font-size:0.8rem;">Close</button>
        </div>
    </div>
</div>

<script>
    function openModal(data) {
        document.getElementById('m_name').innerText = (data.child_name || '').toUpperCase();
        
        const footerDelete = document.getElementById('modalFooterDelete');
        if(data.status !== 'Approved') {
            footerDelete.innerHTML = `<a href="?delete_id=${data.id}" onclick="return confirm('Remove this pending registration?')" style="color: var(--danger-red); text-decoration: none; font-weight: bold; font-size: 0.8rem; text-transform: uppercase;">Delete Record</a>`;
        } else {
            footerDelete.innerHTML = ``;
        }

        // Personal Info mapping
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

        // Standard Vaccines list
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

        let immHtml = '';
        standardVaccines.forEach(vac => {
            let matchedRecord = null;
            
            let allSources = [...(data.history || [])];
            if (data.vaccine_taken && data.vaccine_taken !== 'None') {
                allSources.push({
                    vaccine_taken: data.vaccine_taken,
                    vaccine_date: data.created_at ? data.created_at.split(' ')[0] : '--',
                    administered_by: data.administered_by || 'Health Worker',
                    remarks: 'Initial record',
                    weight_kg: data.weight_kg || '--',
                    height: data.height || '--'
                });
            }

            if (allSources.length > 0) {
                matchedRecord = allSources.find(h => {
                    let vTaken = h.vaccine_taken ? h.vaccine_taken.toLowerCase() : '';
                    let remarks = h.remarks ? h.remarks.toLowerCase() : '';
                    
                    let matchesKeyword = vac.keywords.some(kw => vTaken.includes(kw));
                    let matchesDose = vTaken.includes(vac.doseNum) || remarks.includes(vac.doseNum) || (vac.doseNum === "1" && !vTaken.includes("2") && !vTaken.includes("3"));
                    
                    return matchesKeyword && matchesDose;
                });
            }

            let dateTaken = '--';
            let administeredBy = '--';
            let recordBtnHtml = '<span style="color:#a0aec0; font-style:italic; font-size:0.75rem;">No record</span>';

            if (matchedRecord) {
                let rawDate = matchedRecord.vaccine_date || data.created_at;
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

    function closeModal() {
        document.getElementById('infantModal').style.display = "none";
    }

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

    window.onclick = function(event) {
        if (event.target == document.getElementById('infantModal')) closeModal();
        if (event.target == document.getElementById('healthRecordModal')) closeHealthRecordModal();
    }
</script>
</body>
</html>