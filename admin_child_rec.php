<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'newest';

// QUERY: Nag-join tayo sa infant_records para makuha yung data na nakita natin sa phpMyAdmin
$query = "SELECT c.*, 
          r.weight_kg, r.height, r.vaccine_taken, r.birth_date AS r_dob, r.baby_name
          FROM children c
          LEFT JOIN (
              SELECT * FROM infant_records WHERE id IN (SELECT MAX(id) FROM infant_records GROUP BY child_id)
          ) r ON c.id = r.child_id
          WHERE c.status = 'Approved' AND (c.child_name LIKE '%$search%' OR r.baby_name LIKE '%$search%')";

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
        :root { --sage-green: #89936C; --bg-beige: #fcfdfa; --danger-red: #E53E3E; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-beige); margin: 0; display: flex; }
        #main { width: 100%; padding: 40px; box-sizing: border-box; min-height: 100vh; margin-left: 280px; }
        .records-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h2 { color: var(--sage-green); font-size: 1.8rem; margin: 0; }
        .search-box { padding: 10px; border: 1px solid #ddd; border-radius: 8px; width: 220px; outline: none; }
        .btn-add { background-color: var(--sage-green); color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead { background-color: var(--sage-green); }
        th { color: white; padding: 15px; text-align: left; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        .view-btn { background: transparent; color: var(--sage-green); border: 1.5px solid var(--sage-green); padding: 6px 14px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 35px; border-radius: 25px; width: 550px; position: relative; }
        .info-card { background: #fdfdfd; border: 1px dashed var(--sage-green); padding: 15px; border-radius: 12px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .info-item label { display: block; font-size: 0.7rem; color: #888; text-transform: uppercase; font-weight: bold; }
        .info-item span { font-weight: 600; color: #333; }
        .latest-record-box { background: #fcfdfa; border: 1px solid #f0f0f0; padding: 20px; border-radius: 15px; position: relative; }
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 15px; }
        .stat-box { text-align: center; background: white; padding: 10px; border-radius: 10px; border: 1px solid #f0f0f0; }
        .stat-box small { display: block; color: #999; font-size: 0.65rem; text-transform: uppercase; }
        .stat-box b { font-size: 1.1rem; color: var(--sage-green); }
        .btn-delete { background: none; border: none; color: var(--danger-red); text-decoration: underline; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

    <?php 
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin') { include 'super_admin_sidebar.php'; } 
        else { include 'admin_sidebar.php'; } 
    ?>

    <div id="main">
        <div class="records-card">
            <div class="header-section">
                <h2>Infant Health Records</h2>
                <div style="display:flex; gap:10px;">
                    <form method="GET"><input type="text" name="search" class="search-box" placeholder="Search baby..." value="<?= htmlspecialchars($search); ?>"></form>
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
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr id="row_<?= $row['id']; ?>">
                        <td style="font-weight:600;"><?= htmlspecialchars($row['child_name'] ?? $row['baby_name']); ?></td>
                        <td><?= htmlspecialchars($row['mother_name'] ?? 'N/A'); ?></td>
                        <td><?= $row['birth_date'] ? date('M d, Y', strtotime($row['birth_date'])) : 'N/A'; ?></td>
                        <td><button class="view-btn" onclick='openModal(<?= json_encode($row); ?>)'>View Record</button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="infantModal" class="modal">
        <div class="modal-content">
            <h1 id="m_name" style="color: var(--sage-green); margin: 0 0 15px 0;"></h1>
            <div class="info-card">
                <div class="info-item"><label>Mother</label><span id="m_mother"></span></div>
                <div class="info-item"><label>Birthday</label><span id="m_dob"></span></div>
            </div>
            <div class="latest-record-box">
                <label style="font-size: 0.75rem; font-weight: bold; color: var(--sage-green);">LATEST HEALTH DATA</label>
                <div class="stat-grid">
                    <div class="stat-box"><small>Weight</small><b id="last_weight">--</b><small>kg</small></div>
                    <div class="stat-box"><small>Height</small><b id="last_height">--</b><small>cm</small></div>
                    <div class="stat-box"><small>Vaccine</small><b id="last_vaccine">--</b></div>
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
            document.getElementById('m_mother').innerText = data.mother_name || "N/A";
            document.getElementById('m_dob').innerText = data.birth_date || "N/A";
            
            // Map columns: weight_kg, height, vaccine_taken
            document.getElementById('last_weight').innerText = data.weight_kg || "--";
            document.getElementById('last_height').innerText = data.height || "--";
            document.getElementById('last_vaccine').innerText = data.vaccine_taken || "None";
            
            document.getElementById('infantModal').style.display = "block";
        }
        function closeModal() { document.getElementById('infantModal').style.display = "none"; }

        function deleteRecord() {
            if (confirm("Permanently delete " + document.getElementById('m_name').innerText + "'s record?")) {
                const formData = new FormData();
                formData.append('child_id', currentChildId);
                
                // Fetching sa delete_infant
                fetch('delete_infant.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(result => {
                    if (result.trim() === "success") {
                        // Alisin sa table UI
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