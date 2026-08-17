<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; 

$query = "SELECT reg.*, 
                 CONCAT(reg.client_fname, ' ', COALESCE(CONCAT(reg.client_mi, '. '), ''), reg.client_lname) AS full_name,
                 reg.lmp AS edc,
                 CONCAT(COALESCE(reg.street, ''), ', Brgy. ', COALESCE(reg.barangay, ''), ', ', COALESCE(reg.municipality, ''), ', ', COALESCE(reg.province, '')) AS current_address,
                 rec.bp, rec.weight_kg, rec.temperature, rec.fetal_heart_rate, rec.checkup_date,
                 reg.id AS reg_id
          FROM maternal_registration reg
          LEFT JOIN (
              SELECT * FROM maternal_records 
              WHERE id IN (SELECT MAX(id) FROM maternal_records GROUP BY mother_id)
          ) rec ON reg.id = rec.mother_id
          WHERE reg.user_id = ? 
          ORDER BY reg.id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$my_records = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maternal Records | Alawihao</title>
    <style>
        :root { 
            --primary-green: #718355; 
            --bg-color: #f8faf5; 
            --white: #ffffff; 
            --dark-gray: #2d3436;
            --sage-light: #95AF7E;
            --border-color: #e5eadc;
        }

        body { 
            font-family: 'Times New Roman', serif; 
            background-color: var(--bg-color); 
            margin: 0; 
            display: flex; 
        }

        #main { 
            margin-left: 280px; 
            width: calc(100% - 280px); 
            padding-bottom: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: 0.5s;
        }

        .header { 
            width: 100%; 
            background: var(--white); 
            padding: 25px 40px; 
            border-bottom: 3px solid var(--primary-green); 
            box-sizing: border-box; 
            margin-bottom: 40px; 
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

        /* STATUS BADGES */
        .status-badge { 
            padding: 6px 12px; 
            border-radius: 4px; 
            font-size: 0.7rem; 
            font-weight: bold; 
            text-transform: uppercase;
        }
        .status-verified { background: #f0f4e8; color: var(--primary-green); border: 1px solid var(--primary-green); }
        .status-pending { background: #fffcf0; color: #d4a017; border: 1px solid #d4a017; }

        /* ACTION BUTTONS */
        .action-btns { display: flex; gap: 8px; }
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

        /* MODAL STYLE */
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
            margin: 6% auto;
            padding: 40px;
            width: 500px;
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
        <div style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-green); margin: 0; letter-spacing: 1px;">MATERNAL HEALTH RECORDS</h2>
            <p style="color: #777; font-size: 0.8rem; margin-top: 5px;">View your pregnancy tracking and clinical checkup history.</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Mother's Name</th>
                    <th>Due Date (EDC / LMP)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($my_records->num_rows > 0): ?>
                    <?php while($row = $my_records->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight: bold; color: #444;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo $row['edc'] ? date('M d, Y', strtotime($row['edc'])) : '<span style="color:#ccc">--</span>'; ?></td>
                        <td>
                            <span class="status-badge <?php echo ($row['status'] == 'Approved') ? 'status-verified' : 'status-pending'; ?>">
                                <?php echo ($row['status'] == 'Approved') ? 'Verified' : 'Pending'; ?>
                            </span>
                        </td>
                        <td class="action-btns">
                            <button class="btn details-btn" onclick='showDetails(<?php echo json_encode($row); ?>)'>View Details</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding: 50px; color: #aaa;">Walang nahanap na records.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle" style="color: var(--primary-green); border-bottom: 2px solid #f4f7f0; padding-bottom: 15px; margin-bottom: 20px;">Patient Details</h3>
        
        <div id="modalBody" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            </div>
        
        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #f2f5ee; font-size: 0.8rem; color: #888;">
            *Ang impormasyong ito ay huling na-update noong <span id="lastUpdate">--</span>.
        </div>
    </div>
</div>

<script>
    function showDetails(data) {
        const modal = document.getElementById('detailsModal');
        const body = document.getElementById('modalBody');
        const updateSpan = document.getElementById('lastUpdate');
        
        document.getElementById('modalTitle').innerText = data.full_name;
        
        body.innerHTML = `
            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Age</small><br><b>${data.age || '--'} yrs old</b></div>
            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Contact Number</small><br><b>${data.contact || '--'}</b></div>
            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">LMP (Last Period)</small><br><b>${data.lmp || '--'}</b></div>
            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">EDC (Estimated Due Date)</small><br><b style="color:var(--primary-green)">${data.edc || '--'}</b></div>
            
            <div style="grid-column: span 2; background: #f4f7f0; padding: 15px; border-radius: 10px; border-left: 4px solid var(--primary-green);">
                <small style="color:var(--primary-green); font-weight:bold; font-size:0.7rem; letter-spacing:1px;">LATEST CLINICAL VITALS</small>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                    <div><small style="color:#888;">Weight:</small> <b>${data.weight_kg || '--'} kg</b></div>
                    <div><small style="color:#888;">BP:</small> <b>${data.bp || '--'}</b></div>
                    <div><small style="color:#888;">Temp:</small> <b>${data.temperature || '--'} °C</b></div>
                    <div><small style="color:#888;">Fetal Heart Rate:</small> <b>${data.fetal_heart_rate || '--'} bpm</b></div>
                </div>
            </div>
            
            <div style="grid-column: span 2"><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Current Address</small><br><b>${data.current_address || '--'}</b></div>
        `;
        
        updateSpan.innerText = data.checkup_date || 'N/A';
        modal.style.display = "block";
    }

    function closeModal() {
        document.getElementById('detailsModal').style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('detailsModal')) closeModal();
    }
</script>

</body>
</html>