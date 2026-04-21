<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. DELETE LOGIC (GET METHOD - Backup logic para sa direct links)
if (isset($_GET['delete_id'])) {
    $id_to_delete = mysqli_real_escape_string($conn, $_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM maternal_records WHERE mother_id = '$id_to_delete'");
    if (mysqli_query($conn, "DELETE FROM maternal_registrations WHERE id = '$id_to_delete'")) {
        header("Location: admin_maternal_rec.php?msg=deleted");
        exit();
    }
}

// 2. FETCH PATIENTS (FILTERED TO APPROVED ONLY)
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT u.*, 
          r.weight_kg, r.bp, r.temperature, r.fetal_heart_rate, r.remarks, r.checkup_date
          FROM maternal_registrations u
          LEFT JOIN (
              SELECT * FROM maternal_records WHERE id IN (SELECT MAX(id) FROM maternal_records GROUP BY mother_id)
          ) r ON u.id = r.mother_id
          WHERE u.status = 'Approved' 
          AND u.full_name LIKE '%$search%' 
          ORDER BY u.created_at DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maternal Health Records | Alawihao Center</title>
    <style>
        :root { --sage-green: #89936C; --bg-beige: #fcfdfa; --danger-red: #d9534f; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-beige); margin: 0; display: flex; }
        
        #main { width: 100%; padding: 40px; box-sizing: border-box; min-height: 100vh; margin-left: 280px; }
        .records-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }

        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h2 { color: var(--sage-green); font-size: 1.8rem; margin: 0; }
        .search-box { padding: 10px; border: 1px solid #ddd; border-radius: 8px; width: 220px; outline: none; }
        .btn-add { background-color: var(--sage-green); color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: var(--sage-green); }
        th { color: white; padding: 15px; text-align: left; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        .view-btn { background: transparent; color: var(--sage-green); border: 1.5px solid var(--sage-green); padding: 6px 14px; border-radius: 6px; cursor: pointer; font-weight: 600; }

        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 35px; border-radius: 25px; width: 550px; position: relative; }
        
        .info-card { background: #fdfdfd; border: 1px dashed var(--sage-green); padding: 15px; border-radius: 12px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .info-item label { display: block; font-size: 0.7rem; color: #888; text-transform: uppercase; font-weight: bold; }
        .info-item span { font-weight: 600; color: #333; }

        .latest-record-box { background: #fcfdfa; border: 1px solid #e9edc9; padding: 20px; border-radius: 15px; position: relative; }
        .update-tag { position: absolute; top: 15px; right: 20px; font-size: 0.7rem; color: #888; font-weight: bold; background: #f0f0f0; padding: 4px 10px; border-radius: 20px; }
        
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 15px; }
        .stat-box { text-align: center; background: white; padding: 10px; border-radius: 10px; border: 1px solid #f0f0f0; }
        .stat-box small { display: block; color: #999; font-size: 0.65rem; }
        .stat-box b { font-size: 1.1rem; color: var(--sage-green); }

        .btn-delete { background: none; border: none; color: var(--danger-red); text-decoration: underline; cursor: pointer; font-weight: 600; }
        .alert { background: #dff0d8; color: #3c763d; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

    <?php 
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin') { include 'super_admin_sidebar.php'; } 
        else { include 'admin_sidebar.php'; } 
    ?>

    <div id="main">
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert">Operation successful. Record updated/deleted.</div>
        <?php endif; ?>

        <div class="records-card">
            <div class="header-section">
                <h2>Maternal Health Records</h2>
                <div style="display:flex; gap:10px;">
                    <form method="GET"><input type="text" name="search" class="search-box" placeholder="Search..." value="<?= htmlspecialchars($search) ?>"></form>
                    <a href="admin_maternal_reg.php" class="btn-add">+ Register Patient</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Last Visit</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= $row['checkup_date'] ? date('M d, Y', strtotime($row['checkup_date'])) : 'No records' ?></td>
                        <td><button class="view-btn" onclick='openModal(<?= json_encode($row) ?>)'>View Details</button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="maternalModal" class="modal">
        <div class="modal-content">
            <h1 id="m_name" style="color: var(--sage-green); margin: 0 0 15px 0; font-size: 2rem;"></h1>
            
            <div class="info-card">
                <div class="info-item"><label>Age</label><span id="m_age"></span></div>
                <div class="info-item"><label>Contact</label><span id="m_contact"></span></div>
                <div class="info-item"><label>LMP</label><span id="m_lmp"></span></div>
                <div class="info-item"><label>EDC</label><span id="m_edc"></span></div>
            </div>

            <div class="latest-record-box">
                <div class="update-tag">UPDATED: <span id="last_update"></span></div>
                <label style="font-size: 0.75rem; font-weight: bold; color: var(--sage-green);">LATEST MEDICAL DATA</label>
                <div class="stat-grid">
                    <div class="stat-box"><small>Weight</small><b id="last_weight">--</b><small>kg</small></div>
                    <div class="stat-box"><small>BP</small><b id="last_bp">--</b></div>
                    <div class="stat-box"><small>Temp</small><b id="last_temp">--</b><small>°C</small></div>
                </div>
                <div style="margin-top:15px; font-size: 0.85rem; color: #666;">
                    <strong>Heart Rate:</strong> <span id="last_fhr">--</span><br>
                    <strong>Remarks:</strong> <p id="last_remarks" style="margin:5px 0 0 0;"></p>
                </div>
            </div>

            <div style="margin-top: 25px; display: flex; justify-content: space-between; align-items: center;">
                <button type="button" class="btn-delete" id="del_btn">Delete Patient</button>
                <button onclick="closeModal()" style="background:#eee; border:none; padding:10px 20px; border-radius:10px; cursor:pointer; font-weight:600;">Close</button>
            </div>
        </div>
    </div>

    <script>
        let currentMotherId = null; // Defined globally para ma-access ng delete function

        function openModal(data) {
            currentMotherId = data.id; // I-set ang ID pagbukas ng modal
            
            // Populate Modal Fields
            document.getElementById('m_name').innerText = data.full_name;
            document.getElementById('m_age').innerText = (data.age || "--") + " yrs old";
            document.getElementById('m_contact').innerText = data.contact_number || "N/A";
            document.getElementById('m_lmp').innerText = data.lmp || "Not set";
            document.getElementById('m_edc').innerText = data.edc || "Not set";

            // Latest Health Record Fields
            document.getElementById('last_update').innerText = data.checkup_date ? new Date(data.checkup_date).toLocaleDateString() : "NEVER";
            document.getElementById('last_weight').innerText = data.weight_kg || "--";
            document.getElementById('last_bp').innerText = data.bp || "--";
            document.getElementById('last_temp').innerText = data.temperature || "--";
            document.getElementById('last_fhr').innerText = data.fetal_heart_rate || "N/A";
            document.getElementById('last_remarks').innerText = data.remarks || "No remarks.";

            // Ipakita ang modal
            document.getElementById('maternalModal').style.display = "block";
        }

        // Separate logic para sa Delete Button
        document.getElementById('del_btn').onclick = function() {
            if(currentMotherId && confirm("PERMANENTLY DELETE " + document.getElementById('m_name').innerText + "?")) {
                const formData = new FormData();
                formData.append('mother_id', currentMotherId);

                fetch('delete_maternal.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(result => {
                    if (result.trim() === "success") {
                        location.reload(); 
                    } else {
                        alert("Error: " + result);
                    }
                })
                .catch(err => console.error("Fetch error:", err));
            }
        };

        function closeModal() { 
            document.getElementById('maternalModal').style.display = "none"; 
        }

        // Close modal pag clinick sa labas
        window.onclick = function(e) { 
            if(e.target == document.getElementById('maternalModal')) closeModal(); 
        }
    </script>
</body>
</html>