<?php
session_start();
include 'db_connect.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Super Admin' && $_SESSION['role'] !== 'Admin')) {
    header("Location: login.php");
    exit();
}

// 2. UPDATE ACTIVITY
if (isset($_SESSION['user_id'])) {
    $current_id = $_SESSION['user_id'];
    $stmt = ($_SESSION['role'] === 'Worker') 
        ? $conn->prepare("UPDATE health_workers SET last_activity = NOW() WHERE worker_id = ?")
        : $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->bind_param("i", $current_id);
    $stmt->execute();
}

// 3. FETCH PERSONNEL DATA
$query = "SELECT uid, generated_id, first_name, last_name, email, role_type, current_status, address, contact_number 
          FROM (
            (SELECT id AS uid, generated_id, first_name, last_name, email, 'Admin' as role_type,
             CASE WHEN last_activity >= NOW() - INTERVAL 2 MINUTE THEN 'Online' ELSE 'Offline' END as current_status,
             address, contact_number
             FROM users WHERE role IN ('Admin', 'Super Admin') AND status = 'Approved')
            UNION
            (SELECT worker_id AS uid, generated_id, first_name, last_name, email, 'Worker' as role_type,
             CASE WHEN last_activity >= NOW() - INTERVAL 2 MINUTE THEN 'Online' ELSE 'Offline' END as current_status,
             address, contact_number
             FROM health_workers WHERE status = 'approved')
          ) AS combined_workers 
          GROUP BY email 
          ORDER BY current_status DESC, first_name ASC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Directory | Alawihao Center</title>
    <style>
        :root { 
            --sage: #8DAE74; 
            --dark-sage: #6B8E55; 
            --beige: #F5F5DC; 
            --offline: #CBD5E0; 
            --text-main: #2D3748;
            --text-muted: #718096;
            --bg-light: #F9F9F4;
            --white: #FFFFFF;
            --error: #E53E3E;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg-light); display: flex; margin:0; color: var(--text-main); }
        
        .main-content { flex-grow: 1; padding: 40px; width: 100%; box-sizing: border-box; }
        
        /* Table Card Styling */
        .table-card { background: var(--white); padding: 30px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .btn-add { background: var(--sage); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.2s; }
        .btn-add:hover { background: var(--dark-sage); }

        /* Table Aesthetics */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: var(--dark-sage); color: white; padding: 15px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 15px; border-bottom: 1px solid #F1F5ED; font-size: 0.85rem; vertical-align: middle; }
        .id-badge { background: #F1F5ED; color: var(--dark-sage); padding: 4px 10px; border-radius: 6px; font-family: 'Courier New', monospace; font-weight: bold; border: 1px solid #DCE6D5; }
        
        /* Status Indicators */
        .status-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .online-dot { background-color: #48BB78; }
        .offline-dot { background-color: var(--offline); }

        /* Action Buttons */
        .btn-view { color: var(--dark-sage); font-weight: 600; cursor: pointer; background: none; border: none; font-size: 0.8rem; margin-right: 15px; padding: 0; }
        .btn-delete { color: var(--error); font-weight: 600; cursor: pointer; background: none; border: none; font-size: 0.8rem; padding: 0; }
        .btn-view:hover, .btn-delete:hover { text-decoration: underline; }

        /* --- MODAL SYSTEM --- */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 1000; justify-content: center; align-items: center; }
        .modal-pad { background: white; width: 450px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); position: relative; animation: slideUp 0.3s ease-out; overflow: hidden; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .modal-header { padding: 30px 30px 10px 30px; }
        .modal-header h3 { margin: 0; color: var(--text-main); font-size: 1.25rem; }
        .modal-header p { margin: 5px 0 0 0; color: var(--text-muted); font-size: 0.85rem; }

        .modal-body { padding: 20px 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box; background: #FCFCFC; }
        .form-group input:disabled { background: #F7FAFC; color: #A0AEC0; cursor: not-allowed; }

        .modal-footer { background: #F9F9F4; padding: 20px 30px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #F1F5ED; }

        /* Professional Buttons for Modals */
        .btn-action-main { background: var(--dark-sage); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem; transition: 0.2s; }
        .btn-action-danger { background: var(--error); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem; text-decoration: none; }
        .btn-action-secondary { background: white; color: var(--text-muted); border: 1px solid #E2E8F0; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem; }
        
        .close-x { position: absolute; top: 25px; right: 25px; cursor: pointer; color: #CBD5E0; font-size: 1.2rem; }
    </style>
</head>
<body>

<?php include (strtolower($_SESSION['role']) == 'super admin') ? 'super_admin_sidebar.php' : 'admin_sidebar.php'; ?>

<div class="main-content">
    <div class="table-card">
        <div class="card-header">
            <h2 style="color: var(--dark-sage); margin: 0;">Personnel Directory</h2>
            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                <a href="register_worker.php" class="btn-add">+ Register Worker</a>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>System ID</th>
                    <th>Worker Name</th>
                    <th>Role</th>
                    <th>Live Status</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><span class="id-badge"><?php echo htmlspecialchars($row['generated_id'] ?? 'N/A'); ?></span></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong><br>
                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></small>
                    </td>
                    <td>
                        <span style="background:#EDF2F7; padding:4px 8px; border-radius:5px; font-size:0.7rem; font-weight:600;">
                        <?php echo strtoupper($row['role_type']); ?></span>
                    </td>
                    <td>
                        <span class="status-dot <?php echo ($row['current_status'] === 'Online') ? 'online-dot' : 'offline-dot'; ?>"></span>
                        <span style="font-weight:bold; font-size:0.75rem; color:<?php echo ($row['current_status'] === 'Online') ? '#2F855A' : '#718096'; ?>;">
                            <?php echo $row['current_status']; ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <button class="btn-view" onclick="openProfilePad({
                            id: '<?php echo $row['uid']; ?>',
                            type: '<?php echo $row['role_type']; ?>',
                            gen_id: '<?php echo $row['generated_id']; ?>',
                            fname: '<?php echo addslashes($row['first_name']); ?>',
                            lname: '<?php echo addslashes($row['last_name']); ?>',
                            email: '<?php echo $row['email']; ?>',
                            addr: '<?php echo addslashes($row['address']); ?>',
                            contact: '<?php echo $row['contact_number']; ?>'
                        })">View Profile</button>
                        
                        <button class="btn-delete" onclick="confirmDelete('<?php echo $row['uid']; ?>', '<?php echo $row['role_type']; ?>', '<?php echo addslashes($row['first_name']); ?>')">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="profileModal">
    <div class="modal-pad">
        <span class="close-x" onclick="closeModal('profileModal')">&times;</span>
        <div class="modal-header">
            <h3>Personnel Profile</h3>
            <p>Managing contact details and system access.</p>
        </div>
        <form action="update_personnel_process.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="uid" id="modal_uid">
                <input type="hidden" name="role_type" id="modal_role_type">
                
                <div class="form-group">
                    <label>System Generated ID</label>
                    <input type="text" id="modal_gen_id" disabled>
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>First Name</label>
                        <input type="text" id="modal_fname" disabled>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Last Name</label>
                        <input type="text" id="modal_lname" disabled>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="modal_email" required>
                </div>
                <div class="form-group">
                    <label>Home Address</label>
                    <input type="text" name="address" id="modal_addr" required>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact" id="modal_contact" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action-secondary" onclick="closeModal('profileModal')">Close</button>
                <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                    <button type="submit" name="update_submit" class="btn-action-main">Save Changes</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteConfirmModal">
    <div class="modal-pad" style="width: 400px;">
        <div class="modal-body" style="padding-top: 35px;">
            <h3 style="margin:0; color: #2D3748;">Confirm Deletion</h3>
            <p id="deleteMsg" style="color: #718096; font-size: 0.9rem; line-height: 1.5; margin-top: 10px;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn-action-secondary" onclick="closeModal('deleteConfirmModal')">Cancel</button>
            <a id="confirmDeleteBtn" href="#" class="btn-action-danger">Delete Personnel</a>
        </div>
    </div>
</div>

<div class="modal-overlay" id="alertModal">
    <div class="modal-pad" style="width: 380px;">
        <div class="modal-body" style="padding-top: 35px;">
            <h3 id="alertTitle" style="margin:0; color: #2D3748;">System Message</h3>
            <p id="alertMsg" style="color: #718096; font-size: 0.9rem; line-height: 1.5; margin-top: 10px;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn-action-main" onclick="closeModal('alertModal')">Acknowledge</button>
        </div>
    </div>
</div>

<script>
    function openProfilePad(data) {
        document.getElementById('modal_uid').value = data.id;
        document.getElementById('modal_role_type').value = data.type;
        document.getElementById('modal_gen_id').value = data.gen_id;
        document.getElementById('modal_fname').value = data.fname;
        document.getElementById('modal_lname').value = data.lname;
        document.getElementById('modal_email').value = data.email;
        document.getElementById('modal_addr').value = data.addr;
        document.getElementById('modal_contact').value = data.contact;
        document.getElementById('profileModal').style.display = 'flex';
    }

    function confirmDelete(id, type, name) {
        document.getElementById('deleteMsg').innerHTML = `Are you sure you want to remove <strong>${name}</strong>? This will permanently delete their account and access.`;
        document.getElementById('confirmDeleteBtn').href = `delete_worker.php?id=${id}&type=${type}`;
        document.getElementById('deleteConfirmModal').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // Handle Success Redirects
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('msg')) {
            document.getElementById('alertTitle').innerText = 'Update Successful';
            document.getElementById('alertMsg').innerText = urlParams.get('msg');
            document.getElementById('alertModal').style.display = 'flex';
            // Clean the URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    // Close on overlay click
    window.onclick = function(event) {
        if (event.target.className === 'modal-overlay') {
            event.target.style.display = 'none';
        }
    }
</script>

</body>
</html>