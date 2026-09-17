<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; 

// Logic para sa Delete/Remove (Puwede lang i-delete ng user ang sarili niyang pending na in-add)
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $del_query = "DELETE FROM children WHERE id = ? AND user_id = ? AND status != 'Approved'";
    $del_stmt = $conn->prepare($del_query);
    $del_stmt->bind_param("ii", $delete_id, $user_id);
    $del_stmt->execute();
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

// FIX: Naka-filter na ngayon sa pamamagitan ng user_id para makita lamang ng kasalukuyang user ang kanyang mga anak (Approved at Pending man).
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

// Kunin ang history mula sa infant_records table lang
    $hist_query = "SELECT * FROM infant_records WHERE child_id = ? ORDER BY created_at DESC";
    $hist_stmt = $conn->prepare($hist_query);
    $hist_stmt->bind_param("i", $child_current_id);
    $hist_stmt->execute();
    $hist_result = $hist_stmt->get_result();

    while($hist = $hist_result->fetch_assoc()) {
        $history_arr[] = [
            'vaccine_name' => $hist['vaccine_taken'] ?? '',
            'date_administered' => $hist['vaccine_date'] ?? ($hist['created_at'] ? date('Y-m-d', strtotime($hist['created_at'])) : '-- / -- / ----'),
            'health_worker_id' => $hist['administered_by'] ?? 'Health Worker',
            'remarks' => $hist['remarks'] ?? 'No notes yet',
            'weight' => $hist['weight'] ?? ($hist['weight_kg'] ?? null),
            'height' => $hist['height'] ?? ($hist['height_cm'] ?? null)
        ];
    }

    // Siguruhing nakasama ang vaccine galing sa main 'children' table kung mayroon man
    if(!empty($row['vaccine_taken']) && $row['vaccine_taken'] != 'None') {
        $found_in_history = false;
        foreach($history_arr as $h) {
            if(isset($h['vaccine_name']) && strtolower($h['vaccine_name']) == strtolower($row['vaccine_taken'])) {
                $found_in_history = true;
                break;
            }
        }
        if(!$found_in_history) {
            $history_arr[] = [
                'vaccine_name' => $row['vaccine_taken'],
                'date_administered' => $row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : date('Y-m-d'),
                'health_worker_id' => $row['administered_by'] ?? 'Health Worker',
                'remarks' => 'Registered record'
            ];
        }
    }
    
    $row['vaccinations'] = $history_arr;
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
            --sage-light: #95AF7E;
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
            transition: all 0.3s ease-in-out;
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
            transition: 0.3s;
        }
        .btn-register:hover {
            background-color: #5a6a44;
        }

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
            transition: 0.3s;
        }
        .details-btn { background: var(--primary-green); color: white; }
        .details-btn:hover { background: #5a6a44; }
        .remove-btn { background: transparent; color: var(--danger-red); border: 1px solid var(--danger-red); }
        .remove-btn:hover { background: #fdf0f0; }

        .modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(45, 52, 54, 0.5);
            backdrop-filter: blur(3px);
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
        .close-modal {
            position: absolute;
            right: 25px; top: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #aaa;
            transition: 0.2s;
        }
        .close-modal:hover { color: var(--primary-green); }
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
                            <span class="vax-badge"><?php echo htmlspecialchars($row['vaccination_status'] ?: ($row['vaccine_taken'] ?: 'None')); ?></span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo ($row['status'] == 'Approved') ? 'status-approved' : 'status-pending'; ?>">
                                <?php echo ($row['status'] == 'Approved') ? 'Verified' : 'Pending'; ?>
                            </span>
                        </td>
                        <td class="action-btns">
                            <button class="btn details-btn" onclick='showDetails(<?php echo json_encode($row); ?>)'>History</button>
                            
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

<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle" style="color: var(--primary-green); border-bottom: 2px solid #f4f7f0; padding-bottom: 12px; margin-bottom: 20px;">Child Full History</h3>
        
        <div id="modalBody">
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; border-top: 1px solid #f2f5ee; padding-top: 15px;">
            <div id="modalFooterDelete"></div>
            <button onclick="closeModal()" style="background: #f1f2f6; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; color: #2d3436; cursor: pointer; text-transform: uppercase; font-size: 0.75rem;">Close</button>
        </div>
    </div>
</div>

<script>
    function showDetails(data) {
        const modal = document.getElementById('detailsModal');
        const body = document.getElementById('modalBody');
        const footerDelete = document.getElementById('modalFooterDelete');
        
        document.getElementById('modalTitle').innerText = (data.child_name || '').toUpperCase();
        
        if(data.status !== 'Approved') {
            footerDelete.innerHTML = `<a href="?delete_id=${data.id}" onclick="return confirm('Remove this pending registration?')" style="color: var(--danger-red); text-decoration: none; font-weight: bold; font-size: 0.8rem; text-transform: uppercase;">Delete Record</a>`;
        } else {
            footerDelete.innerHTML = ``;
        }

        // Kunin ang pinakabagong weight at height kung nakalagay sa history o vaccinations array
        let latestWeight = data.weight_kg || data.weight || '2.50';
        let latestHeight = data.height_cm || data.height || '13.00';

        if (data.vaccinations && data.vaccinations.length > 0) {
            let sortedVax = [...data.vaccinations].sort((a, b) => new Date(b.date_administered) - new Date(a.date_administered));
            for (let v of sortedVax) {
                if (v.weight && parseFloat(v.weight) > 0) { latestWeight = v.weight; break; }
                if (v.height && parseFloat(v.height) > 0) { latestHeight = v.height; break; }
            }
        }

        function getVaccineDetails(vaxKeyword, doseNum) {
            if (!data.vaccinations || data.vaccinations.length === 0) {
                return { date: '-- / -- / ----', worker: '--', notes: 'No notes yet' };
            }
            
            const match = data.vaccinations.find(v => {
                if (!v.vaccine_name) return false;
                let vName = v.vaccine_name.toLowerCase();
                let keyword = vaxKeyword.toLowerCase();
                
                // Special handling for BCG and Hep B at birth
                if (keyword.includes('bcg') && vName.includes('bcg')) return true;
                if (keyword.includes('hep b') && (vName.includes('hep') || vName.includes('hepatitis'))) return true;
                
                let matchesKeyword = vName.includes(keyword);
                let matchesDose = vName.includes('dose ' + doseNum) || vName.includes('d' + doseNum) || vName.includes('dos' + doseNum);
                
                return matchesKeyword && (matchesDose || doseNum === 1);
            });

            if (match) {
                return {
                    date: match.date_administered && match.date_administered !== '0000-00-00' ? match.date_administered : '-- / -- / ----',
                    worker: match.health_worker_id ? match.health_worker_id : 'Health Worker',
                    notes: match.remarks || 'No notes yet'
                };
            }
            return { date: '-- / -- / ----', worker: '--', notes: 'No notes yet' };
        }

        const vaccinesList = [
            { name: 'BCG Vaccine', desc: 'Rec: At birth', dose: 1, keyword: 'bcg' },
            { name: 'Hepatitis B Vaccine', desc: 'Rec: At birth', dose: 1, keyword: 'hep b' },
            { name: 'Pentavalent Vaccine (DPT-Hep B-HIB)', desc: 'Rec: 1½ mos', dose: 1, keyword: 'pentavalent' },
            { name: 'Pentavalent Vaccine (DPT-Hep B-HIB)', desc: 'Rec: 2½ mos', dose: 2, keyword: 'pentavalent' },
            { name: 'Pentavalent Vaccine (DPT-Hep B-HIB)', desc: 'Rec: 3½ mos', dose: 3, keyword: 'pentavalent' },
            { name: 'Oral Polio Vaccine (OPV)', desc: 'Rec: 1½ mos', dose: 1, keyword: 'opv' },
            { name: 'Oral Polio Vaccine (OPV)', desc: 'Rec: 2½ mos', dose: 2, keyword: 'opv' },
            { name: 'Oral Polio Vaccine (OPV)', desc: 'Rec: 3½ mos', dose: 3, keyword: 'opv' },
            { name: 'Inactivated Polio Vaccine (IPV)', desc: 'Rec: 3½ mos', dose: 1, keyword: 'ipv' },
            { name: 'Inactivated Polio Vaccine (IPV)', desc: 'Rec: 9 mos', dose: 2, keyword: 'ipv' },
            { name: 'Pneumococcal Conjugate Vaccine (PCV)', desc: 'Rec: 1½ mos', dose: 1, keyword: 'pcv' },
            { name: 'Pneumococcal Conjugate Vaccine (PCV)', desc: 'Rec: 2½ mos', dose: 2, keyword: 'pcv' },
            { name: 'Pneumococcal Conjugate Vaccine (PCV)', desc: 'Rec: 3½ mos', dose: 3, keyword: 'pcv' },
            { name: 'Measles, Mumps, Rubella Vaccine (MMR)', desc: 'Rec: 9 mos', dose: 1, keyword: 'mmr' },
            { name: 'Measles, Mumps, Rubella Vaccine (MMR)', desc: 'Rec: 1 year', dose: 2, keyword: 'mmr' }
        ];

        let tableRowsHtml = '';
        vaccinesList.forEach(item => {
            let info = getVaccineDetails(item.keyword, item.dose);
            let hasTaken = info.date !== '-- / -- / ----';
            
            let dateColor = hasTaken ? 'color: #2d3436; font-weight: bold;' : 'color: #999;';
            
            tableRowsHtml += `
                <tr style="border-bottom: 1px solid #f2f5ee;">
                    <td style="padding: 12px;"><b>${item.name}</b><br><small style="color:#888; font-size:0.75rem;">${item.desc}</small></td>
                    <td style="padding: 12px;"><span style="background: #f4f7f0; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; color: var(--primary-green); border: 1px solid #d0dbcc;">Dose ${item.dose}</span></td>
                    <td style="padding: 12px; ${dateColor}">${info.date}</td>
                    <td style="padding: 12px; color: #555;">${info.worker}</td>
                    <td style="padding: 12px; color: ${hasTaken ? '#444' : '#b2bec3'}; font-style: ${hasTaken ? 'normal' : 'italic'};">${info.notes}</td>
                </tr>
            `;
        });
        
        body.innerHTML = `
            <div style="font-size: 0.75rem; font-weight: bold; color: var(--primary-green); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Verified Personal Information</div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; background: #fdfdfd; padding: 15px; border-radius: 10px; border: 1px dashed var(--primary-green); margin-bottom: 20px; font-size: 0.85rem;">
                <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Mother's Name</small><br><b>${data.mother_name || 'N/A'}</b></div>
                <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Father's Name</small><br><b>${data.father_name || 'N/A'}</b></div>
                <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Birthday</small><br><b>${data.birth_date}</b></div>
                <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Gender</small><br><b>${data.gender}</b></div>
                <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Blood Type</small><br><b>${data.blood_type || 'O+'}</b></div>
                <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Place of Birth</small><br><b>${data.place_of_birth || 'Alawihao Health Center'}</b></div>
                <div style="grid-column: span 3;"><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Address / Barangay</small><br><b>${data.address || 'Purok 1'}, Alawihao</b></div>
            </div>

            <div style="background: #f8f9fa; border: 1px solid #f0f0f0; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                <div style="font-size: 0.75rem; font-weight: bold; color: var(--primary-green); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">Latest Health Data</div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <div style="border: 1px solid #e5eadc; padding: 12px; border-radius: 8px; text-align: center; background: #fff;">
                        <small style="color: #888; font-size: 0.65rem; text-transform: uppercase; font-weight: bold;">Weight</small>
                        <div style="font-weight: bold; font-size: 1.1rem; color: #444; margin-top: 4px;">${latestWeight} <span style="font-size: 0.75rem; color: #777;">KG</span></div>
                    </div>
                    <div style="border: 1px solid #e5eadc; padding: 12px; border-radius: 8px; text-align: center; background: #fff;">
                        <small style="color: #888; font-size: 0.65rem; text-transform: uppercase; font-weight: bold;">Height</small>
                        <div style="font-weight: bold; font-size: 1.1rem; color: #444; margin-top: 4px;">${latestHeight} <span style="font-size: 0.75rem; color: #777;">CM</span></div>
                    </div>
                    <div style="border: 1px solid #e5eadc; padding: 12px; border-radius: 8px; text-align: center; background: #fff;">
                        <small style="color: #888; font-size: 0.65rem; text-transform: uppercase; font-weight: bold;">Latest Vaccine</small>
                        <div style="font-weight: bold; font-size: 0.9rem; color: var(--primary-green); margin-top: 4px;">
                            ${(() => {
                                if (data.vaccinations && data.vaccinations.length > 0) {
                                    let takenVaxs = data.vaccinations.filter(v => v.date_administered && v.date_administered !== '-- / -- / ----' && v.date_administered !== '0000-00-00');
                                    if (takenVaxs.length > 0) {
                                        takenVaxs.sort((a, b) => new Date(b.date_administered) - new Date(a.date_administered));
                                        return takenVaxs[0].vaccine_name;
                                    }
                                }
                                return data.vaccination_status || data.vaccine_taken || 'None';
                            })()}
                        </div>
                    </div>
                </div>
            </div>

            <h4 style="color: var(--primary-green); margin-bottom: 10px; font-size: 0.9rem; letter-spacing: 1px;">IMMUNIZATION MONITORING TABLE</h4>
            <div style="max-height: 380px; overflow-y: auto; border: 1px solid #e5eadc; border-radius: 8px; position: relative;">
                <table style="width: 100%; border-collapse: collapse; background: white; font-size: 0.85rem; margin-top: 0;">
                    <thead>
                        <tr style="background: #b08d57; text-align: left;">
                            <th style="padding: 12px; font-size: 0.7rem; color: white; width: 35%; position: sticky; top: 0; background: #b08d57; z-index: 2;">BAKUNA</th>
                            <th style="padding: 12px; font-size: 0.7rem; color: white; width: 15%; position: sticky; top: 0; background: #b08d57; z-index: 2;">DOSES</th>
                            <th style="padding: 12px; font-size: 0.7rem; color: white; width: 20%; position: sticky; top: 0; background: #b08d57; z-index: 2;">PETSA NG BAKUNA</th>
                            <th style="padding: 12px; font-size: 0.7rem; color: white; width: 15%; position: sticky; top: 0; background: #b08d57; z-index: 2;">NAGTUROK</th>
                            <th style="padding: 12px; font-size: 0.7rem; color: white; width: 15%; position: sticky; top: 0; background: #b08d57; z-index: 2;">REMARKS / NOTES</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRowsHtml}
                    </tbody>
                </table>
            </div>
        `;
        modal.style.display = "block";
    }

    function closeModal() {
        document.getElementById('detailsModal').style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('detailsModal')) closeModal();
    }
</script>