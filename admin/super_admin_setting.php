<?php
session_start();
include '../db_connect.php';

$message = "";
$message_type = "";

// FUNCTION PARA SA ACTIVITY LOGS (Dapat nasa server-side PHP block)
function logAction($conn, $userName, $role, $actionDesc) {
    $userName = mysqli_real_escape_string($conn, $userName);
    $role = mysqli_real_escape_string($conn, $role);
    $actionDesc = mysqli_real_escape_string($conn, $actionDesc);
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    // Siguraduhing may table para sa logs
    $create_table = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_name VARCHAR(100),
        role VARCHAR(50),
        action_description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);

    $sql = "INSERT INTO activity_logs (user_name, role, action_description, ip_address) 
            VALUES ('$userName', '$role', '$actionDesc', '$ipAddress')";
    @mysqli_query($conn, $sql);
}

// 1. HANDLE SYSTEM INFO UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_system_info'])) {
    $sys_name = mysqli_real_escape_string($conn, $_POST['sys_name']);
    $sys_contact = mysqli_real_escape_string($conn, $_POST['sys_contact']);
    $sys_address = mysqli_real_escape_string($conn, $_POST['sys_address']);

    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
    if (mysqli_num_rows($check_table) == 0) {
        mysqli_query($conn, "CREATE TABLE system_settings (id INT PRIMARY KEY, sys_name VARCHAR(255), sys_contact VARCHAR(50), sys_address TEXT)");
        mysqli_query($conn, "INSERT INTO system_settings (id, sys_name, sys_contact, sys_address) VALUES (1, '$sys_name', '$sys_contact', '$sys_address')");
    } else {
        $query = "UPDATE system_settings SET sys_name = '$sys_name', sys_contact = '$sys_contact', sys_address = '$sys_address' WHERE id = 1";
        mysqli_query($conn, $query);
    }

    // RECORD TO ACTIVITY LOG
    logAction($conn, "Super Admin", "Super Admin", "Updated health center system information.");

    $message = "System settings updated successfully!";
    $message_type = "success";
}

// 2. HANDLE ADMIN PROFILE & PASSWORD UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_admin_profile'])) {
    $admin_name = mysqli_real_escape_string($conn, $_POST['admin_name']);
    $admin_email = mysqli_real_escape_string($conn, $_POST['admin_email']);
    $current_pwd = $_POST['current_pwd'];
    $new_pwd = $_POST['new_pwd'];

    $update_info = "UPDATE admin_users SET full_name = '$admin_name', email = '$admin_email' WHERE id = 1";
    @mysqli_query($conn, $update_info);

    if (!empty($new_pwd)) {
        if (!empty($current_pwd)) {
            $hashed_password = password_hash($new_pwd, PASSWORD_DEFAULT);
            $update_pwd = "UPDATE admin_users SET password = '$hashed_password' WHERE id = 1";
            @mysqli_query($conn, $update_pwd);
        }
    }

    // RECORD TO ACTIVITY LOG
    logAction($conn, "Super Admin", "Super Admin", "Updated super admin profile & security credentials.");

    $message = "Admin profile & security credentials updated successfully!";
    $message_type = "success";
}
?>
<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Settings | Alawihao Health Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green: #2d5016;
            --accent: #5a7c3a;
            --light: #8fbf5a;
            --soft-sage: #F1F5ED;
            --bg: #f8fffb;
            --white: #ffffff;
            --text: #333333;
            --sidebar-width: 280px;
            --border-color: #edf2ed;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); }

        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            background: var(--light);
            width: 0%;
            z-index: 9999;
            transition: width 0.1s;
        }

        .topbar {
            background: var(--white);
            border-bottom: 3px solid var(--green);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .topbar .page-label { 
            font-size: 1.1rem; 
            font-weight: 600; 
            color: var(--green); 
        }

        #main {
            margin-left: var(--sidebar-width);
            padding: 30px 24px 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: calc(100vh - 70px);
            transition: margin-left 0.3s ease;
        }

        .settings-container {
            width: 100%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .settings-card { 
            background: var(--white); 
            width: 100%; 
            padding: 30px 35px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.04); 
            border: 1px solid #eef2ee;
        }

        .card-header-accent {
            border-top: 5px solid var(--green);
        }

        .settings-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--green);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group { flex: 1; }
        
        label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--green); font-size: 0.9rem; }
        .settings-input { 
            width: 100%; 
            padding: 10px 15px; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px; 
            font-family: inherit; 
            font-size: 0.95rem; 
            transition: all 0.2s; 
            background-color: #f8fafc;
        }
        .settings-input:disabled { background-color: #e2e8f0; cursor: not-allowed; }
        .settings-input:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(45, 80, 22, 0.1); }

        .btn { padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; border: none; transition: 0.2s; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--green); color: white; }
        .btn-primary:hover { background: var(--accent); }
        .btn-primary:disabled { background: #cbd5e1; cursor: not-allowed; }

        .btn-edit { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 6px 14px; border-radius: 6px; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; transition: 0.2s; }
        .btn-edit:hover { background: #bae6fd; }

        .btn-backup {
            background: #EDF2F7;
            color: #2D3748;
            border: 1px solid #cbd5e1;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-backup:hover { background: #E2E8F0; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        /* CUSTOM SECURITY MODAL STYLING */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(3px);
        }
        .modal-box {
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-top: 5px solid var(--green);
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--green);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-desc {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 20px;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-cancel {
            background: #e2e8f0;
            color: #334155;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-cancel:hover { background: #cbd5e1; }
    </style>
</head>
<body>

<div id="progressBar" class="progress-bar"></div>

<?php include 'super_admin_sidebar.php'; ?>

<div class="topbar">
    <span class="page-label">Administrative Settings</span>
</div>

<!-- CUSTOM SECURITY PIN MODAL -->
<div id="securityModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-title"><i class="fa-solid fa-lock"></i> Security Verification</div>
        <div class="modal-desc">Enter your Security PIN or Password to unlock and modify these settings.</div>
        <input type="password" id="modalPinInput" class="settings-input" placeholder="Enter PIN/Password" style="margin-bottom: 0;">
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeSecurityModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="verifySecurityPin()">Verify</button>
        </div>
    </div>
</div>

<div id="main">
    <div class="settings-container">
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fa-solid fa-circle-check"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- 1. SYSTEM INFORMATION -->
        <div class="settings-card card-header-accent">
            <div class="settings-section-title">
                <span><i class="fa-solid fa-building-user"></i> System & Health Center Info</span>
                <button type="button" class="btn-edit" onclick="openSecurityModal('systemForm', this)"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
            </div>
            <form id="systemForm" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>System Name</label>
                        <input type="text" name="sys_name" class="settings-input" value="Alawihao Center Admin Control" disabled required>
                    </div>
                    <div class="form-group">
                        <label>Health Center Contact Number</label>
                        <input type="text" name="sys_contact" class="settings-input" value="0912-345-6789" disabled required>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Official Address</label>
                    <input type="text" name="sys_address" class="settings-input" value="Brgy. Alawihao, Daet, Camarines Norte" disabled required>
                </div>
                <button type="submit" name="update_system_info" id="saveSystemBtn" class="btn btn-primary" disabled><i class="fa-solid fa-floppy-disk"></i> Save System Info</button>
            </form>
        </div>

        <!-- 2. SUPER ADMIN CREDENTIALS -->
        <div class="settings-card">
            <div class="settings-section-title">
                <span><i class="fa-solid fa-user-shield"></i> Admin Profile & Security</span>
                <button type="button" class="btn-edit" onclick="openSecurityModal('adminForm', this)"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
            </div>
            <form id="adminForm" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="admin_name" class="settings-input" value="Super Admin" disabled required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="admin_email" class="settings-input" value="admin@alawihaocenter.com" disabled required>
                    </div>
                </div>
                
                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 25px 0;">
                
                <label style="margin-bottom: 15px; display: block; color: var(--text);"><strong>Change Password</strong> (Leave blank to keep current password)</label>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_pwd" class="settings-input" placeholder="••••••••" disabled>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_pwd" class="settings-input" placeholder="Enter new password" disabled>
                    </div>
                </div>
                <button type="submit" name="update_admin_profile" id="saveAdminBtn" class="btn btn-primary" disabled><i class="fa-solid fa-shield-halved"></i> Update Credentials</button>
            </form>
        </div>

        <!-- 3. DATABASE & SYSTEM MAINTENANCE -->
        <div class="settings-card">
            <div class="settings-section-title"><i class="fa-solid fa-database"></i> System Maintenance & Backup</div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Database Export / Backup</label>
                <p style="font-size: 0.85rem; color: #666; margin-top: 0; margin-bottom: 15px;">
                    Download a secure SQL backup copy of all health center records, schedules, and user accounts.
                </p>
                <a href="backup_db.php" class="btn-backup"><i class="fa-solid fa-download"></i> Download SQL Backup</a>
            </div>
        </div>

    </div>
</div>

<script>
    window.addEventListener('scroll', () => {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = (scrollTop / docHeight) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
    });

    let currentTargetFormId = '';
    let currentEditBtn = null;

    function openSecurityModal(formId, editBtn) {
        currentTargetFormId = formId;
        currentEditBtn = editBtn;
        document.getElementById('modalPinInput').value = '';
        document.getElementById('securityModal').style.display = 'flex';
        document.getElementById('modalPinInput').focus();
    }

    function closeSecurityModal() {
        document.getElementById('securityModal').style.display = 'none';
    }

    function verifySecurityPin() {
        let pin = document.getElementById('modalPinInput').value;
        
        // Pwede mong palitan ang PIN dito (Halimbawa: '1234' o 'admin123')
        if (pin === "1234" || pin === "admin123") {
            closeSecurityModal();
            const form = document.getElementById(currentTargetFormId);
            const inputs = form.querySelectorAll('input');
            const submitBtn = form.querySelector('button[type="submit"]');

            inputs.forEach(input => {
                input.removeAttribute('disabled');
            });
            
            if(submitBtn) {
                submitBtn.removeAttribute('disabled');
            }

            currentEditBtn.innerHTML = '<i class="fa-solid fa-lock-open"></i> Unlocked';
            currentEditBtn.style.background = '#dcfce7';
            currentEditBtn.style.color = '#166534';
            currentEditBtn.style.borderColor = '#bbf7d0';
        } else {
            alert("Incorrect Security PIN or Password!");
            document.getElementById('modalPinInput').value = '';
            document.getElementById('modalPinInput').focus();
        }
    }
</script>

</body>
</html>