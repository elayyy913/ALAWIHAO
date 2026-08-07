<?php
session_start();
include '../db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'newest';
$age_filter = isset($_GET['age_filter']) ? $_GET['age_filter'] : 'all';

$query = "SELECT c.*, 
         COALESCE(NULLIF(r.weight_kg, 0), c.weight_kg, 0) AS weight_kg, 
         COALESCE(NULLIF(r.height, 0), c.height_cm, 0) AS height, 
         COALESCE(r.vaccine_taken, c.vaccine_taken, 'None') AS vaccine_taken,
         r.vaccine_date, r.next_checkup, r.remarks, r.birth_date AS r_dob, r.baby_name, r.administered_by
         FROM children c
         LEFT JOIN (
             SELECT * FROM infant_records WHERE id IN (SELECT MAX(id) FROM infant_records GROUP BY child_id)
         ) r ON c.id = r.child_id
         WHERE c.status = 'Approved' AND (c.child_name LIKE '%$search%' OR r.baby_name LIKE '%$search%')";
// Age Filtering Logic gamit ang TIMESTAMPDIFF sa MySQL
if ($age_filter == '0-1') {
    $query .= " AND TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) <= 1";
} elseif ($age_filter == '1-6') {
    $query .= " AND TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) > 1 AND TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) <= 6";
} elseif ($age_filter == '6-12') {
    $query .= " AND TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) > 6 AND TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) <= 12";
} elseif ($age_filter == '1-2') {
    $query .= " AND TIMESTAMPDIFF(YEAR, c.birth_date, CURDATE()) >= 1 AND TIMESTAMPDIFF(YEAR, c.birth_date, CURDATE()) < 2";
} elseif ($age_filter == '2_above') {
    $query .= " AND TIMESTAMPDIFF(YEAR, c.birth_date, CURDATE()) >= 2";
}

if ($filter == 'newest') { 
    $query .= " ORDER BY c.created_at DESC"; 
} else { 
    $query .= " ORDER BY c.created_at ASC"; 
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Infant Records | Alawihao Health Center</title>
    <style>
        :root { --sage-green: #6B8E55; --bg-beige: #f8f9fa;  --danger-red: #E53E3E; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-beige); margin: 0; display: flex; }
        #main { width: 100%; padding: 40px; box-sizing: border-box; min-height: 100vh; margin-left: 280px; }
        .records-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        h2 { color: var(--sage-green); font-size: 1.8rem; margin: 0; }
        .search-box, .filter-select { padding: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none; background: white; font-size: 0.9rem; }
        .search-box { width: 200px; }
        .btn-add { background-color: var(--sage-green); color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead { background-color: var(--sage-green); }
        th { color: white; padding: 15px; text-align: left; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        .view-btn { background: transparent; color: var(--sage-green); border: 1.5px solid var(--sage-green); padding: 6px 14px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
        .modal-content { background: white; margin: 2% auto; padding: 30px; border-radius: 20px; width: 750px; position: relative; max-height: 90vh; overflow-y: auto; }
        
        .info-card { background: #fdfdfd; border: 1px dashed var(--sage-green); padding: 15px; border-radius: 12px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .info-item label { display: block; font-size: 0.65rem; color: #888; text-transform: uppercase; font-weight: bold; }
        .info-item span { font-weight: 600; color: #333; font-size: 0.9rem; }
        
        .latest-record-box { background: #f8f9fa; border: 1px solid #f0f0f0; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 10px; }
        .stat-box { text-align: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #eaeaea; }
        .stat-box small { display: block; color: #999; font-size: 0.65rem; text-transform: uppercase; }
        .stat-box b { font-size: 1rem; color: var(--sage-green); }

        /* Immunization Monitoring Table Style */
        .immunization-box { margin-top: 20px; }
        .immunization-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-top: 8px; border: 1px solid #e2e8f0; }
        .immunization-table th { background-color: #d4a373; color: white; padding: 10px; font-size: 0.75rem; text-align: center; border: 1px solid #c89664; }
        .immunization-table td { padding: 10px; border: 1px solid #e2e8f0; color: #2d3748; text-align: center; vertical-align: middle; }
        .immunization-table td:first-child { text-align: left; font-weight: 600; }

        .btn-delete { background: none; border: none; color: var(--danger-red); text-decoration: underline; cursor: pointer; font-weight: bold; }
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
                <h2>Infant Health Records</h2>
                <div style="display:flex; gap:10px; align-items: center; flex-wrap: wrap;">
                    <form method="GET" style="display:flex; gap:10px; align-items:center;">
                        <!-- Age Filter Dropdown -->
                        <select name="age_filter" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?= $age_filter == 'all' ? 'selected' : ''; ?>>All Ages</option>
                            <option value="0-1" <?= $age_filter == '0-1' ? 'selected' : ''; ?>>0 - 1 Month Old</option>
                            <option value="1-6" <?= $age_filter == '1-6' ? 'selected' : ''; ?>>1 - 6 Months Old</option>
                            <option value="6-12" <?= $age_filter == '6-12' ? 'selected' : ''; ?>>6 - 12 Months Old</option>
                            <option value="1-2" <?= $age_filter == '1-2' ? 'selected' : ''; ?>>1 - 2 Years Old</option>
                            <option value="2_above" <?= $age_filter == '2_above' ? 'selected' : ''; ?>>2 Years Old & Above</option>
                        </select>
                        
                        <!-- Search Box -->
                        <input type="text" name="search" class="search-box" placeholder="Search baby..." value="<?= htmlspecialchars($search); ?>">
                        
                        <!-- Panatilihin ang sorting filter kung meron man -->
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter); ?>">
                    </form>
                    
                    <a href="admin_child_reg.php" class="btn-add">+ New Baby</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Baby Name</th>
                        <th>Mother</th>
                        <th>Birthday</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <?php 
                            $child_current_id = $row['id'];
                            $history_query = mysqli_query($conn, "SELECT * FROM infant_records WHERE child_id = '$child_current_id' ORDER BY created_at DESC");
                            $history_arr = [];
                            while($hist = mysqli_fetch_assoc($history_query)) {
                                $history_arr[] = $hist;
                            }
                            $row['history'] = $history_arr;
                        ?>
                        <tr id="row_<?= $row['id']; ?>">
                            <td style="font-weight:600;"><?= htmlspecialchars($row['child_name'] ?? $row['baby_name']); ?></td>
                            <td><?= htmlspecialchars($row['mother_name'] ?? 'N/A'); ?></td>
                            <td><?= $row['birth_date'] ? date('M d, Y', strtotime($row['birth_date'])) : 'N/A'; ?></td>
                            <td><button class="view-btn" onclick='openModal(<?= json_encode($row); ?>)'>View Record</button></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #888; padding: 30px;">No infant records found matching the criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Detailed Info & Immunization Monitoring -->
    <div id="infantModal" class="modal">
        <div class="modal-content">
            <h1 id="m_name" style="color: var(--sage-green); margin: 0 0 15px 0; font-size: 1.5rem;"></h1>
            
            <!-- Personal Information Section -->
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

            <!-- Latest Health Data Section -->
            <div class="latest-record-box">
                <label style="font-size: 0.75rem; font-weight: bold; color: var(--sage-green);">LATEST HEALTH DATA</label>
                <div class="stat-grid">
                    <div class="stat-box"><small>Weight</small><b id="last_weight">--</b><small>kg</small></div>
                    <div class="stat-box"><small>Height</small><b id="last_height">--</b><small>cm</small></div>
                    <div class="stat-box"><small>Latest Vaccine</small><b id="last_vaccine" style="font-size: 0.85rem;">--</b></div>
                </div>
            </div>

            <!-- Immunization Monitoring Table -->
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
                            <!-- Dynamic rows loaded via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-top: 25px; display: flex; justify-content: space-between; align-items: center;">
                <button type="button" onclick="deleteRecord()" class="btn-delete">Delete Record</button>
                <button onclick="closeModal()" style="background:#eee; border:none; padding: 10px 20px; border-radius:10px; cursor:pointer; font-weight:600;">Close</button>
            </div>
        </div>
    </div>

    <script>
        let currentChildId = null;
        function openModal(data) {
            currentChildId = data.id;
            document.getElementById('m_name').innerText = data.child_name || data.baby_name;
            
            // Personal Info mapping
            document.getElementById('m_mother').innerText = data.mother_name || "N/A";
            document.getElementById('m_father').innerText = data.father_name || "N/A";
            document.getElementById('m_dob').innerText = data.birth_date ? new Date(data.birth_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'}) : "N/A";
            document.getElementById('m_gender').innerText = data.gender || "N/A";
            document.getElementById('m_blood').innerText = data.blood_type || "N/A";
            document.getElementById('m_pob').innerText = data.place_of_birth || "N/A";
            
            let fullAddress = (data.address ? data.address + ", " : "") + (data.barangay || "");
            document.getElementById('m_address').innerText = fullAddress !== "" ? fullAddress : "N/A";
            
            // Latest Health Data mapping
            document.getElementById('last_weight').innerText = data.weight_kg || "--";
            document.getElementById('last_height').innerText = data.height || "--";
            document.getElementById('last_vaccine').innerText = data.vaccine_taken || "None";
            
            // Standard Vaccines list with individual breakdown for multiple doses
            const standardVaccines = [
                { name: "BCG Vaccine", keyword: "bcg", doseNum: "1", schedule: "At birth" },
                { name: "Hepatitis B Vaccine", keyword: "hepatitis", doseNum: "1", schedule: "At birth" },
                { name: "Pentavalent Vaccine (DPT-Hep B-HIB)", keyword: "penta", doseNum: "1", schedule: "1½ mos" },
                { name: "Pentavalent Vaccine (DPT-Hep B-HIB)", keyword: "penta", doseNum: "2", schedule: "2½ mos" },
                { name: "Pentavalent Vaccine (DPT-Hep B-HIB)", keyword: "penta", doseNum: "3", schedule: "3½ mos" },
                { name: "Oral Polio Vaccine (OPV)", keyword: "opv", doseNum: "1", schedule: "1½ mos" },
                { name: "Oral Polio Vaccine (OPV)", keyword: "opv", doseNum: "2", schedule: "2½ mos" },
                { name: "Oral Polio Vaccine (OPV)", keyword: "opv", doseNum: "3", schedule: "3½ mos" },
                { name: "Inactivated Polio Vaccine (IPV)", keyword: "ipv", doseNum: "1", schedule: "3½ mos" },
                { name: "Inactivated Polio Vaccine (IPV)", keyword: "ipv", doseNum: "2", schedule: "9 mos" },
                { name: "Pneumococcal Conjugate Vaccine (PCV)", keyword: "pcv", doseNum: "1", schedule: "1½ mos" },
                { name: "Pneumococcal Conjugate Vaccine (PCV)", keyword: "pcv", doseNum: "2", schedule: "2½ mos" },
                { name: "Pneumococcal Conjugate Vaccine (PCV)", keyword: "pcv", doseNum: "3", schedule: "3½ mos" },
                { name: "Measles, Mumps, Rubella Vaccine (MMR)", keyword: "mmr", doseNum: "1", schedule: "9 mos" },
                { name: "Measles, Mumps, Rubella Vaccine (MMR)", keyword: "mmr", doseNum: "2", schedule: "1 year" }
            ];

            let immHtml = '';
            standardVaccines.forEach(vac => {
                let matchedRecord = null;
                if (data.history && data.history.length > 0) {
                    matchedRecord = data.history.find(h => {
                        let vTaken = h.vaccine_taken ? h.vaccine_taken.toLowerCase() : '';
                        let remarks = h.remarks ? h.remarks.toLowerCase() : '';
                        let matchesKeyword = vTaken.includes(vac.keyword);
                        let matchesDose = vTaken.includes(vac.doseNum) || remarks.includes(vac.doseNum) || vac.doseNum === "1";
                        return matchesKeyword && matchesDose;
                    });
                }

                let dateTaken = '--';
                let administeredBy = '--';
                let remarksText = '<span style="color:#a0aec0; font-style:italic;">No notes yet</span>';

                if (matchedRecord) {
                    dateTaken = matchedRecord.vaccine_date || (matchedRecord.created_at ? matchedRecord.created_at.split(' ')[0] : '--');
                    administeredBy = matchedRecord.administered_by || matchedRecord.staff_name || 'Health Worker';
                    remarksText = matchedRecord.remarks ? matchedRecord.remarks : '<span style="color:#a0aec0; font-style:italic;">No notes</span>';
                } else if (vac.doseNum === "1" && data.vaccine_taken && data.vaccine_taken.toLowerCase().includes(vac.keyword)) {
                    dateTaken = data.birth_date || '--';
                    administeredBy = data.administered_by || 'Hospital / Registration';
                    remarksText = data.remarks ? data.remarks : 'Given (Hospital)';
                }

                immHtml += `<tr>
                    <td>${vac.name} <br><small style="color:#718096; font-size:0.65rem;">Rec: ${vac.schedule}</small></td>
                    <td><span style="background:#edf2f7; padding:2px 6px; border-radius:4px; font-weight:600; font-size:0.75rem;">Dose ${vac.doseNum}</span></td>
                    <td>${dateTaken !== '--' ? dateTaken : '<span style="color:#cbd5e0;">-- / -- / ----</span>'}</td>
                    <td style="font-size: 0.75rem; font-weight: 500; color: #4a5568;">${administeredBy}</td>
                    <td style="font-size: 0.75rem;">${remarksText}</td>
                </tr>`;
            });

            document.getElementById('immunization_rows').innerHTML = immHtml;
            document.getElementById('infantModal').style.display = "block";
        }

        function closeModal() { document.getElementById('infantModal').style.display = "none"; }

        function deleteRecord() {
            if (confirm("Permanently delete " + document.getElementById('m_name').innerText + "'s record?")) {
                const formData = new FormData();
                formData.append('child_id', currentChildId);
                
                fetch('../delete_infant.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(result => {
                    if (result.trim() === "success") {
                        const row = document.getElementById('row_' + currentChildId);
                        if(row) row.remove();
                        closeModal();
                    } else { 
                        alert("Error: " + result); 
                    }
                }).catch(err => {
                    alert("Connection Error. Make sure delete_infant.php exists.");
                });
            }
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('infantModal')) closeModal();
        }
    </script>
</body>
</html>