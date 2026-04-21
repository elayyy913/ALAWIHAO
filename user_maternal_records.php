<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; 

/**
 * SQL Logic:
 * Kinukuha natin ang basic info mula sa 'maternal_registrations'
 * at ang pinakabagong medical records mula sa 'maternal_records'
 */
$query = "SELECT reg.*, 
                 rec.bp, rec.weight_kg, rec.temperature, rec.fetal_heart_rate, rec.checkup_date,
                 reg.id AS reg_id
          FROM maternal_registrations reg
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
            --bg-color: #f1f4ea; 
            --white: #ffffff; 
            --dark-gray: #2d3436;
            --sage-light: #95AF7E;
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
            border-bottom: 5px solid var(--primary-green); 
            box-sizing: border-box; 
            margin-bottom: 40px; 
            box-shadow: 0 2px 15px rgba(0,0,0,0.05); 
        }

        .table-container { 
            background: var(--white); 
            width: 92%; 
            padding: 35px; 
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.08); 
            border-top: 10px solid var(--primary-green); 
            box-sizing: border-box; 
        }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { 
            background: #f9fafb; 
            color: var(--primary-green); 
            padding: 18px; 
            text-align: left; 
            border-bottom: 3px solid #eee; 
            text-transform: uppercase; 
            font-size: 0.7rem; 
            letter-spacing: 1.5px;
        }
        td { padding: 18px; border-bottom: 1px solid #f0f0f0; color: var(--dark-gray); font-size: 0.9rem; }
        tr:hover { background-color: #fcfdfa; }

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
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(3px);
        }
        .modal-content {
            background-color: var(--white);
            margin: 8% auto;
            padding: 40px;
            width: 550px;
            border-radius: 20px;
            border-top: 10px solid var(--primary-green);
            position: relative;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }
        .close-modal {
            position: absolute;
            right: 25px; top: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #ccc;
        }
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
            <p style="color: #888; font-size: 0.8rem; margin-top: 5px;">View your pregnancy tracking and clinical checkup history.</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Mother's Name</th>
                    <th>Due Date (EDC)</th>
                    <th>Latest BP</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($my_records->num_rows > 0): ?>
                    <?php while($row = $my_records->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight: bold; color: #444;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['edc'])); ?></td>
                        <td><?php echo $row['bp'] ? $row['bp'] : '<span style="color:#ccc">--</span>'; ?></td>
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
                    <tr><td colspan="5" style="text-align:center; padding: 50px; color: #ccc;">Walang nahanap na records.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle" style="color: var(--primary-green); border-bottom: 2px solid #f8faf5; padding-bottom: 15px; margin-bottom: 20px;">Patient Details</h3>
        
        <div id="modalBody" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
            </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 0.8rem; color: #aaa;">
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
        
        // I-set ang modal content base sa columns ng DB mo
        body.innerHTML = `
            <div><small style="color:#aaa; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Age</small><br><b>${data.age} yrs old</b></div>
            <div><small style="color:#aaa; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Contact Number</small><br><b>${data.contact_number}</b></div>
            <div><small style="color:#aaa; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">LMP (Last Period)</small><br><b>${data.last_menstrual_period}</b></div>
            <div><small style="color:#aaa; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">EDC (Estimated Due Date)</small><br><b style="color:var(--primary-green)">${data.edc}</b></div>
            
            <div style="grid-column: span 2; background: #fcfdfa; padding: 15px; border-radius: 10px; border-left: 4px solid var(--primary-green);">
                <small style="color:var(--primary-green); font-weight:bold;">LATEST CLINICAL Vitals</small>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                    <div><small>Weight:</small> <b>${data.weight_kg || '--'} kg</b></div>
                    <div><small>BP:</small> <b>${data.bp || '--'}</b></div>
                    <div><small>Temp:</small> <b>${data.temperature || '--'} °C</b></div>
                    <div><small>Fetal Heart Rate:</small> <b>${data.fetal_heart_rate || '--'} bpm</b></div>
                </div>
            </div>
            
            <div style="grid-column: span 2"><small style="color:#aaa; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Current Address</small><br><b>${data.current_address}</b></div>
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