<?php
session_start();
include '../db_connect.php';

// 1. SECURITY CHECK (Admin & Super Admin lang ang pwedeng pumasok dito)
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Super Admin' && $_SESSION['role'] !== 'Admin')) {
    header("Location: login.php");
    exit();
}

// 2. UPDATE ADMIN/SUPER ADMIN ACTIVITY (Ginagawang 'Online' ang kasalukuyang nagbubukas ng page)
if (isset($_SESSION['user_id'])) {
    $current_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $current_id);
        $stmt->execute();
        $stmt->close();
    }
}

// 3. FETCH PERSONNEL DATA (Admin at Super Admin lang, na may Online/Offline Status)
$query = "SELECT id AS uid, generated_id, first_name, last_name, email, role AS role_type, 
                 CASE WHEN last_activity IS NOT NULL AND last_activity >= NOW() - INTERVAL 2 MINUTE THEN 'Online' ELSE 'Offline' END AS current_status, 
                 address, contact_number, last_activity 
          FROM users 
          WHERE role IN ('Admin', 'Super Admin') AND status = 'Approved'
          ORDER BY (last_activity IS NOT NULL AND last_activity >= NOW() - INTERVAL 2 MINUTE) DESC, last_activity DESC, first_name ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("SQL Error: " . mysqli_error($conn));
}

$personnel = [];
$onlineStaffCount = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $personnel[] = $row;
    if ($row['current_status'] === 'Online') {
        $onlineStaffCount++;
    }
}
$totalStaffCount = count($personnel);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Directory | Alawihao Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --border: #E4EADF;
            --green-soft: #EAF5EC;
            --green-text: #287044;
            --amber-soft: #FFF5DE;
            --amber-text: #94620D;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg-light); display: flex; margin:0; color: var(--text-main); }
        
        .main-content { flex-grow: 1; min-width: 0; padding: 40px; width: 100%; box-sizing: border-box; }
        
        /* Table Card Styling */
        .table-card { background: var(--white); padding: 30px; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 22px; }
        .card-header h2 { font-size: 1.2rem; }
        .btn-add { display: inline-flex; align-items: center; justify-content: center; gap: 7px; background: var(--sage); color: white; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.2s; white-space: nowrap; }
        .btn-add:hover { background: var(--dark-sage); }
        .directory-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 190px)); gap: 12px; margin-bottom: 20px; }
        .summary-card { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border: 1px solid var(--border); border-radius: 12px; background: #fbfcf9; }
        .summary-icon { display: grid; place-items: center; flex: 0 0 38px; width: 38px; height: 38px; border-radius: 10px; background: #edf3e8; color: var(--dark-sage); }
        .summary-card.is-online .summary-icon { background: var(--green-soft); color: var(--green-text); }
        .summary-label { display: block; color: var(--text-muted); font-size: .7rem; font-weight: 650; }
        .summary-value { display: block; margin-top: 2px; color: var(--text-main); font-size: 1.25rem; line-height: 1.1; font-weight: 750; }

        .directory-toolbar { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(150px, 190px) minmax(150px, 190px); gap: 12px; margin-bottom: 16px; }
        .directory-search { position: relative; }
        .directory-search i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: .85rem; pointer-events: none; }
        .directory-search input, .directory-toolbar select { width: 100%; height: 42px; padding: 0 12px; border: 1px solid #DCE4D8; border-radius: 8px; background: #fff; color: var(--text-main); font: inherit; font-size: .82rem; box-sizing: border-box; }
        .directory-search input { padding-left: 36px; }
        .directory-search input:focus, .directory-toolbar select:focus { outline: 3px solid rgba(141, 174, 116, .2); border-color: var(--sage); }
        .directory-result-count { margin: 0 0 10px; color: var(--text-muted); font-size: .75rem; }
        .directory-table-wrap { width: 100%; overflow-x: auto; border: 1px solid #E8EDE5; border-radius: 10px; }

        /* Table Aesthetics */
        table { width: 100%; min-width: 700px; border-collapse: collapse; }
        th { text-align: left; background: var(--dark-sage); color: white; padding: 13px 15px; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 16px 15px; border-bottom: 1px solid #F1F5ED; font-size: 0.84rem; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr { transition: background .15s ease; }
        tbody tr:hover { background: #f8faf6; }
        .worker-name { color: var(--text-main); font-weight: 700; }
        .worker-email { display: inline-block; margin-top: 4px; color: var(--text-muted); font-size: .76rem; }
        .id-badge { display: inline-block; background: #F1F5ED; color: var(--dark-sage); padding: 5px 10px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: .76rem; font-weight: bold; border: 1px solid #DCE6D5; white-space: nowrap; }
        .role-badge { display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 6px; background: #EDF2F7; color: #4A5568; font-size: .68rem; font-weight: 750; letter-spacing: .02em; }
        
        /* Status Indicators */
        .worker-status { display: inline-flex; align-items: center; gap: 7px; padding: 6px 9px; border-radius: 99px; font-size: .73rem; font-weight: 700; }
        .worker-status.online { background: var(--green-soft); color: var(--green-text); }
        .worker-status.offline { background: #F1F3F5; color: #687384; }
        .status-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; }
        .online-dot { background-color: #36A269; box-shadow: 0 0 0 3px rgba(54, 162, 105, .13); }
        .offline-dot { background-color: var(--offline); }
        .empty-row { padding: 28px; color: var(--text-muted); text-align: center; }

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

        [data-theme="dark"] .btn-add,
        [data-theme="dark"] .btn-action-main,
        [data-theme="dark"] .btn-action-danger {
            color: #fff !important;
        }
        [data-theme="dark"] .btn-view {
            color: var(--dm-sage) !important;
        }
        [data-theme="dark"] .btn-delete {
            color: #ff8585 !important;
        }
        [data-theme="dark"] .btn-action-secondary {
            background: var(--dm-surface2) !important;
            border-color: var(--dm-border) !important;
            color: var(--dm-text) !important;
        }
        [data-theme="dark"] .modal-pad {
            background: var(--dm-surface) !important;
            color: var(--dm-text) !important;
        }
        [data-theme="dark"] .modal-pad h3,
        [data-theme="dark"] .modal-pad p {
            color: var(--dm-text) !important;
        }
        [data-theme="dark"] .modal-footer {
            background: var(--dm-surface2) !important;
            border-color: var(--dm-border) !important;
        }
        [data-theme="dark"] .summary-card { background: var(--dm-surface2) !important; border-color: var(--dm-border) !important; }
        [data-theme="dark"] .summary-label,
        [data-theme="dark"] .worker-email,
        [data-theme="dark"] .directory-result-count { color: var(--dm-text2) !important; }
        [data-theme="dark"] .summary-value,
        [data-theme="dark"] .worker-name { color: var(--dm-text) !important; }
        [data-theme="dark"] .directory-search input,
        [data-theme="dark"] .directory-toolbar select { background: var(--dm-surface2) !important; border-color: var(--dm-border) !important; color: var(--dm-text) !important; }
        [data-theme="dark"] .directory-search i { color: var(--dm-text2); }
        [data-theme="dark"] .directory-table-wrap { border-color: var(--dm-border); }
        [data-theme="dark"] tbody tr:hover { background: var(--dm-surface2); }
        [data-theme="dark"] td { border-color: var(--dm-border); }
        [data-theme="dark"] .role-badge { background: #343b35 !important; color: var(--dm-text) !important; }
        [data-theme="dark"] .worker-status.online { background: #24412d !important; color: #a7d8ae !important; }
        [data-theme="dark"] .worker-status.offline { background: #343b35 !important; color: var(--dm-text2) !important; }
        [data-theme="dark"] .empty-row { color: var(--dm-text2); }

        @media (max-width: 760px) {
            .main-content { padding: 78px 20px 24px; }
            .table-card { padding: 22px; }
        }
        @media (max-width: 560px) {
            .main-content { padding: 78px 14px 20px; }
            .table-card { padding: 17px; border-radius: 14px; }
            .card-header { align-items: flex-start; flex-direction: column; }
            .btn-add { width: 100%; box-sizing: border-box; }
            .directory-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .summary-card { gap: 8px; padding: 11px; }
            .summary-icon { flex-basis: 32px; width: 32px; height: 32px; }
            .summary-label { font-size: .65rem; }
            .directory-toolbar { grid-template-columns: minmax(0, 1fr); gap: 9px; }
        }
    </style>
</head>
<body>

<?php include (strtolower($_SESSION['role']) == 'super admin') ? 'super_admin_sidebar.php' : 'admin_sidebar.php'; ?>

<div class="main-content">
    <div class="table-card">
        <div class="card-header">
            <h2 style="color: var(--dark-sage); margin: 0;">Personnel Directory</h2>
            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                <!-- register_worker.php ay nasa ROOT (FINAL_CAPSTONE/), kaya "../" papunta dito.
                     Ang "redirect" value ay relative sa ROOT din (kaya may "admin/" prefix),
                     dahil doon ie-evaluate ni register_worker.php ang value na ito.
                     Destination: admin/super_admin_dashboard.php diretso sa "Pending Staff
                     Worker Accounts" pad (id="pendingWorkersPad") para doon i-verify/approve. -->
                <a href="../register_worker.php?redirect=admin/super_admin_dashboard.php%23pendingWorkersPad" class="btn-add"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Register Worker</a>
            <?php endif; ?>
        </div>

        <section class="directory-summary" aria-label="Personnel summary">
            <article class="summary-card">
                <span class="summary-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                <span><span class="summary-label">Total staff</span><strong class="summary-value"><?php echo $totalStaffCount; ?></strong></span>
            </article>
            <article class="summary-card is-online">
                <span class="summary-icon"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span>
                <span><span class="summary-label">Online now</span><strong class="summary-value"><?php echo $onlineStaffCount; ?></strong></span>
            </article>
        </section>

        <div class="directory-toolbar" role="search">
            <label class="directory-search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="personnelSearch" placeholder="Search name, email, or system ID" aria-label="Search personnel">
            </label>
            <select id="personnelRoleFilter" aria-label="Filter by role">
                <option value="">All roles</option>
                <option value="admin">Admin</option>
                <option value="super admin">Super Admin</option>
            </select>
            <select id="personnelStatusFilter" aria-label="Filter by live status">
                <option value="">All statuses</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
            </select>
        </div>
        <p class="directory-result-count" id="directoryResultCount" aria-live="polite"><?php echo $totalStaffCount; ?> personnel</p>

        <div class="directory-table-wrap">
        <table id="personnelTable">
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
                <?php foreach ($personnel as $row):
                    $searchText = strtolower(implode(' ', [
                        $row['generated_id'] ?? '',
                        $row['first_name'] ?? '',
                        $row['last_name'] ?? '',
                        $row['email'] ?? '',
                        $row['role_type'] ?? ''
                    ]));
                    $roleFilter = strtolower(trim($row['role_type'] ?? ''));
                    $statusFilter = strtolower($row['current_status'] ?? '');
                ?>
                <tr data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                    data-role="<?php echo htmlspecialchars($roleFilter, ENT_QUOTES, 'UTF-8'); ?>"
                    data-status="<?php echo htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8'); ?>">
                    <td><span class="id-badge"><?php echo htmlspecialchars($row['generated_id'] ?? 'N/A'); ?></span></td>
                    <td>
                        <span class="worker-name"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span><br>
                        <small class="worker-email"><?php echo htmlspecialchars($row['email']); ?></small>
                    </td>
                    <td>
                        <span class="role-badge"><?php echo htmlspecialchars(strtoupper($row['role_type'])); ?></span>
                    </td>
                    <td>
                        <span class="worker-status <?php echo ($row['current_status'] === 'Online') ? 'online' : 'offline'; ?>">
                            <span class="status-dot <?php echo ($row['current_status'] === 'Online') ? 'online-dot' : 'offline-dot'; ?>"></span>
                            <?php echo htmlspecialchars($row['current_status']); ?>
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
                <?php endforeach; ?>
                <tr id="directoryEmptyRow" hidden><td colspan="5" class="empty-row">No personnel match these filters.</td></tr>
            </tbody>
        </table>
        </div>
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
                    <!-- Nilagyan ng maxlength, pattern, at JavaScript restriction para sumunod sa format ng register -->
                    <input type="text" name="contact" id="modal_contact" required 
                           maxlength="11" pattern="09[0-9]{9}" 
                           placeholder="09XXXXXXXXX" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);" 
                           title="Must be 11 digits starting with 09 (e.g., 09123456789)">
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
        <form id="deleteForm" action="delete_worker.php" method="POST">
            <div class="modal-body" style="padding-top: 35px;">
                <h3 style="margin:0; color: #2D3748;">Confirm Deletion</h3>
                <p id="deleteMsg" style="color: #718096; font-size: 0.9rem; line-height: 1.5; margin-top: 10px;"></p>

                <input type="hidden" name="id" id="deleteTargetId">
                <input type="hidden" name="type" id="deleteTargetType">

                <div class="form-group" style="margin-top: 15px;">
                    <label>Enter Your Password to Confirm</label>
                    <input type="password" name="confirm_password" id="deleteConfirmPassword" required autocomplete="current-password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action-secondary" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                <button type="submit" class="btn-action-danger" style="border:none; cursor:pointer;">Delete Personnel</button>
            </div>
        </form>
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

            document.getElementById('deleteTargetId').value = id;
            document.getElementById('deleteTargetType').value = type;
            document.getElementById('deleteConfirmPassword').value = '';

            document.getElementById('deleteConfirmModal').style.display = 'flex';
        }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // Handle Success/Error Redirects
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error')) {
            document.getElementById('alertTitle').innerText = 'Action Failed';
            document.getElementById('alertMsg').innerText = urlParams.get('error');
            document.getElementById('alertModal').style.display = 'flex';
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (urlParams.has('msg')) {
            document.getElementById('alertTitle').innerText = 'Update Successful';
            document.getElementById('alertMsg').innerText = urlParams.get('msg');
            document.getElementById('alertModal').style.display = 'flex';
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    // Close on overlay click
    window.onclick = function(event) {
        if (event.target.className === 'modal-overlay') {
            event.target.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('personnelSearch');
        const roleFilter = document.getElementById('personnelRoleFilter');
        const statusFilter = document.getElementById('personnelStatusFilter');
        const resultCount = document.getElementById('directoryResultCount');
        const emptyRow = document.getElementById('directoryEmptyRow');
        const rows = Array.from(document.querySelectorAll('#personnelTable tbody tr[data-search]'));

        function filterPersonnel() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const selectedRole = roleFilter.value;
            const selectedStatus = statusFilter.value;
            let visibleCount = 0;

            rows.forEach(function(row) {
                const matches = row.dataset.search.includes(searchTerm)
                    && (!selectedRole || row.dataset.role === selectedRole)
                    && (!selectedStatus || row.dataset.status === selectedStatus);
                row.hidden = !matches;
                if (matches) visibleCount++;
            });

            emptyRow.hidden = visibleCount > 0;
            emptyRow.querySelector('td').textContent = rows.length === 0
                ? 'No staff accounts found.'
                : 'No personnel match these filters.';
            resultCount.textContent = 'Showing ' + visibleCount + ' of ' + rows.length + ' personnel';
        }

        searchInput.addEventListener('input', filterPersonnel);
        roleFilter.addEventListener('change', filterPersonnel);
        statusFilter.addEventListener('change', filterPersonnel);
        filterPersonnel();
    });
</script>

</body>
</html>