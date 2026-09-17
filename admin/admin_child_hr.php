<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../db_connect.php';

// Security: 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query_role = mysqli_query($conn, "SELECT role FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($query_role);
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

// Update Health Record (Isinasave na ngayon sa infant_records table)
if (isset($_POST['update_health'])) {
    $c_id = mysqli_real_escape_string($conn, $_POST['child_id']);
    $w = mysqli_real_escape_string($conn, $_POST['weight']);
    $h = mysqli_real_escape_string($conn, $_POST['height']);
    $base_v = mysqli_real_escape_string($conn, $_POST['vaccine']);
    $dose = mysqli_real_escape_string($conn, $_POST['dose']);
    
    // Pinagsama ang pangalan ng bakuna at ang dose para unique
    $v = $base_v . ' - ' . $dose;

    $v_date = mysqli_real_escape_string($conn, $_POST['vaccine_date']); 
    $next_date = mysqli_real_escape_string($conn, $_POST['next_checkup']); 
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']); 
    $administered_by = mysqli_real_escape_string($conn, $_POST['administered_by']); 
    $hw_id = $_SESSION['user_id']; 

    // Itinatapon na ang bagong check-up data sa infant_records table gamit ang child_id
    $sql = "INSERT INTO infant_records (child_id, weight_kg, height, vaccine_taken, vaccine_date, next_checkup, remarks, administered_by, health_worker_id, created_at) 
            VALUES ('$c_id', '$w', '$h', '$v', '$v_date', '$next_date', '$remarks', '$administered_by', '$hw_id', NOW())";
    
    if (mysqli_query($conn, $sql)) {
        // Sa halip na alert(), it-trigger natin ang flag para sa modal
        $show_success_modal = true;
    }
}

// Fetching child data galing sa master list (children table)
$query = "SELECT * FROM children ORDER BY child_name ASC";
$result = mysqli_query($conn, $query);

// KUNIN ANG LAHAT NG KASAYSAYAN NG VAKUNA AT CHECK-UP BAWAT BATA MULA SA infant_records
$history_query = mysqli_query($conn, "SELECT * FROM infant_records ORDER BY vaccine_date DESC, created_at DESC");
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
        }

        #main {
            flex-grow: 1;
            padding: 40px;
            transition: var(--transition);
            margin-left: 0;
        }

        #main.main-content-active {
            margin-left: var(--sidebar-width);
        }

        .header-section { margin-bottom: 30px; }
        .header-section h1 { color: var(--dark-sage); margin: 0; }
        
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: var(--soft-sage); color: var(--dark-sage); font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #EDF2F7; font-size: 0.9rem; }

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
    </style>
</head>
<body>

<?php include $sidebar_to_include; ?>

<div id="main">
    <div class="header-section">
        <button onclick="toggleSidebar()" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--dark-sage); margin-bottom:10px;"></button>
        <h1>Child & Infant Records</h1>
        <p style="color: #A0AEC0;">Alawihao Health Center Management System</p>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Child Name</th>
                    <th>Mother's Name</th>
                    <th>Gender</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <?php 
                        $c_id = $row['id'];
                        $taken_list = isset($child_vaccines_map[$c_id]) ? $child_vaccines_map[$c_id] : [];
                        $history_list = isset($child_history_map[$c_id]) ? $child_history_map[$c_id] : [];
                    ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['child_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['mother_name']); ?></td>
                    <td><?php echo $row['gender']; ?></td>
                    <td>
                        <button class="btn btn-view" onclick='openHistoryModal(<?php echo json_encode($row); ?>, <?php echo json_encode($history_list); ?>)'>Full History</button>
                        <button class="btn btn-edit" onclick="openEditModal('<?php echo $c_id; ?>', '<?php echo htmlspecialchars($row['child_name'], ENT_QUOTES); ?>', <?php echo htmlspecialchars(json_encode($taken_list), ENT_QUOTES); ?>)">Update Health</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SUCCESS NOTIFICATION MODAL -->
<div id="successModal" class="modal" style="<?php echo $show_success_modal ? 'display: block;' : ''; ?>">
    <div class="success-modal-content">
        <div style="font-size: 50px; color: #48BB78; margin-bottom: 10px;">✔</div>
        <h3 style="color: var(--dark-sage); margin: 0 0 10px 0;">Success!</h3>
        <p style="color: #4A5568; font-size: 0.95rem; margin-bottom: 20px;">Health Record Updated Successfully!</p>
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
                        <option value="BCG Vaccine">BCG Vaccine</option>
                        <option value="Hepatitis B Vaccine">Hepatitis B Vaccine</option>
                        <option value="Pentavalent Vaccine (DPT-Hep B-HIB)">Pentavalent Vaccine (DPT-Hep B-HIB)</option>
                        <option value="Oral Polio Vaccine (OPV)">Oral Polio Vaccine (OPV)</option>
                        <option value="Inactivated Polio Vaccine (IPV)">Inactivated Polio Vaccine (IPV)</option>
                        <option value="Pneumococcal Conjugate Vaccine (PCV)">Pneumococcal Conjugate Vaccine (PCV)</option>
                        <option value="Measles, Mumps, Rubella Vaccine (MMR)">Measles, Mumps, Rubella Vaccine (MMR)</option>
                    </select>
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
    const content = document.getElementById('main');
    const isShowing = sidebar.classList.toggle('show');
    
    if (isShowing) {
        content.classList.add('main-content-active');
        localStorage.setItem('sidebarVisible', 'true');
    } else {
        content.classList.remove('main-content-active');
        localStorage.setItem('sidebarVisible', 'false');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('mainSidebar');
    const content = document.getElementById('main');
    const state = localStorage.getItem('sidebarVisible');

    if (state === 'true') {
        sidebar.classList.add('show');
        content.classList.add('main-content-active');
    }
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
    let selectedVaccine = document.getElementById('vaccineSelect').value;
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