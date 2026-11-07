<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Super Admin') {
    header("Location: ../login.php");
    exit();
}

include '../db_connect.php';
require_once '../profile_picture_helpers.php';

$message = "";
$message_type = "";
$recovery_csrf_token = $_SESSION['recovery_csrf_token'] ??= bin2hex(random_bytes(32));
$recovery_email_configured = (bool)getenv('ALAWIHAO_SMTP_USERNAME') && (bool)getenv('ALAWIHAO_SMTP_PASSWORD');
$profile_picture_csrf_token = $_SESSION['profile_picture_csrf_token'] ??= bin2hex(random_bytes(32));
$profile_picture_message = $_SESSION['profile_picture_message'] ?? '';
$profile_picture_message_type = $_SESSION['profile_picture_message_type'] ?? 'success';
unset($_SESSION['profile_picture_message'], $_SESSION['profile_picture_message_type']);

$user_id = (int) $_SESSION['user_id'];
$account_stmt = $conn->prepare("SELECT email, profile_picture, first_name, last_name, password FROM users WHERE id = ? AND role = 'Super Admin'");
if (!$account_stmt) {
    die("Unable to load the Super Admin account.");
}
$account_stmt->bind_param("i", $user_id);
if (!$account_stmt->execute()) {
    die("Unable to load the Super Admin account.");
}
$account_result = $account_stmt->get_result();
$account = $account_result->fetch_assoc();
$account_stmt->close();
if (!$account) {
    die("Super Admin account not found.");
}

$recovery_message_type = isset($_SESSION['error_message']) ? 'error' : 'success';
$recovery_message = $_SESSION['error_message'] ?? $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);


// 1. HANDLE SYSTEM INFO UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_system_info'])) {
    $reauth_password = $_POST['reauth_password'] ?? '';
    $stored_password = (string)($account['password'] ?? '');
    $password_matches = $reauth_password !== '' && $stored_password !== '' && (
        password_verify($reauth_password, $stored_password)
        || hash_equals($stored_password, $reauth_password)
    );

    if (!$password_matches) {
        $message = "Incorrect current password. No system settings were changed.";
        $message_type = "error";
    } else {
        $sys_name = mysqli_real_escape_string($conn, $_POST['sys_name'] ?? '');
        $sys_contact = mysqli_real_escape_string($conn, $_POST['sys_contact'] ?? '');
        $sys_address = mysqli_real_escape_string($conn, $_POST['sys_address'] ?? '');

        $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
        if (mysqli_num_rows($check_table) == 0) {
            mysqli_query($conn, "CREATE TABLE system_settings (id INT PRIMARY KEY, sys_name VARCHAR(255), sys_contact VARCHAR(50), sys_address TEXT)");
            mysqli_query($conn, "INSERT INTO system_settings (id, sys_name, sys_contact, sys_address) VALUES (1, '$sys_name', '$sys_contact', '$sys_address')");
        } else {
            $query = "UPDATE system_settings SET sys_name = '$sys_name', sys_contact = '$sys_contact', sys_address = '$sys_address' WHERE id = 1";
            mysqli_query($conn, $query);
        }

        logAdminAction($conn, "Updated health center system information.");
        $message = "System settings updated successfully!";
        $message_type = "success";
    }
}

// 2. HANDLE ADMIN PROFILE & PASSWORD UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_admin_profile'])) {
    $admin_first_name = trim($_POST['admin_first_name'] ?? '');
    $admin_last_name = trim($_POST['admin_last_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $reauth_password = $_POST['reauth_password'] ?? '';
    $current_pwd = $_POST['current_pwd'] ?? '';
    $new_pwd = $_POST['new_pwd'] ?? '';
    $stored_password = (string)($account['password'] ?? '');
    $reauth_password_matches = $reauth_password !== '' && $stored_password !== '' && (
        password_verify($reauth_password, $stored_password)
        || hash_equals($stored_password, $reauth_password)
    );

    if (!$reauth_password_matches) {
        $message = "Incorrect current password. No profile changes were saved.";
        $message_type = "error";
    } elseif ($admin_first_name === '' || $admin_last_name === '' || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter a first name, last name, and valid email address.";
        $message_type = "error";
    } elseif ($new_pwd !== '' && $current_pwd === '') {
        $message = "Enter your current password before setting a new password.";
        $message_type = "error";
    } elseif ($new_pwd !== '' && strlen($new_pwd) < 8) {
        $message = "The new password must be at least 8 characters long.";
        $message_type = "error";
    } else {
        $email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
        if (!$email_check) {
            error_log("Super Admin email check preparation failed: " . $conn->error);
            $message = "Unable to update your profile right now. Please try again.";
            $message_type = "error";
        } else {
            $email_check->bind_param("si", $admin_email, $user_id);
            $email_check->execute();
            $email_in_use = $email_check->get_result()->num_rows > 0;
            $email_check->close();

            if ($email_in_use) {
                $message = "That email address is already being used by another account.";
                $message_type = "error";
            } else {
                $password_matches = $current_pwd !== ''
                    && (
                        password_verify($current_pwd, (string) $account['password'])
                        || hash_equals((string) $account['password'], $current_pwd)
                    );

                if ($new_pwd !== '' && !$password_matches) {
                    $message = "Your current password is incorrect. No changes were saved.";
                    $message_type = "error";
                } else {
                    if ($new_pwd !== '') {
                        $password_hash = password_hash($new_pwd, PASSWORD_DEFAULT);
                        $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ? WHERE id = ? AND role = 'Super Admin'");
                        if ($update_stmt) {
                            $update_stmt->bind_param("ssssi", $admin_first_name, $admin_last_name, $admin_email, $password_hash, $user_id);
                        }
                    } else {
                        $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ? AND role = 'Super Admin'");
                        if ($update_stmt) {
                            $update_stmt->bind_param("sssi", $admin_first_name, $admin_last_name, $admin_email, $user_id);
                        }
                    }

                    if (!$update_stmt) {
                        error_log("Super Admin profile update preparation failed: " . $conn->error);
                        $message = "Unable to update your profile right now. Please try again.";
                        $message_type = "error";
                    } elseif (!$update_stmt->execute()) {
                        error_log("Super Admin profile update failed: " . $update_stmt->error);
                        $message = "Unable to update your profile right now. Please try again.";
                        $message_type = "error";
                        $update_stmt->close();
                    } else {
                        $update_stmt->close();
                        $account['first_name'] = $admin_first_name;
                        $account['last_name'] = $admin_last_name;
                        $account['email'] = $admin_email;
                        if ($new_pwd !== '') {
                            $account['password'] = $password_hash;
                        }
                        $_SESSION['name'] = $admin_first_name;
                        logAdminAction($conn, "Updated super admin profile & security credentials.");
                        $message = "Admin profile & security credentials updated successfully!";
                        $message_type = "success";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Settings | Alawihao Health Center</title>
    <link rel="stylesheet" href="../profile_picture.css">
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
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .recovery-email-box, .recovery-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        .recovery-email-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: var(--text);
        }
        .recovery-info {
            background: var(--soft-sage);
            color: var(--green);
            font-size: 0.85rem;
            line-height: 1.5;
        }

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
    <span class="page-label">Super Admin Settings</span>
</div>

<!-- CUSTOM SECURITY PIN MODAL -->
<div id="securityModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-title"><i class="fa-solid fa-lock"></i> Security Verification</div>
        <div class="modal-desc">Enter your current account password to continue. It will be verified before changes are saved.</div>
        <input type="password" id="modalPasswordInput" class="settings-input" placeholder="Enter current password" autocomplete="current-password" style="margin-bottom: 0;">
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeSecurityModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="continueWithPassword()">Continue</button>
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
        <?php if ($recovery_message !== ''): ?>
            <div class="alert <?php echo $recovery_message_type; ?>">
                <i class="fa-solid <?php echo $recovery_message_type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>
                <?php echo htmlspecialchars($recovery_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        <?php if ($profile_picture_message !== ''): ?>
            <div class="alert <?php echo $profile_picture_message_type === 'error' ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($profile_picture_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- 1. SYSTEM INFORMATION -->
        <div class="settings-card card-header-accent">
            <div class="settings-section-title">
                <span><i class="fa-solid fa-building-user"></i> System & Health Center Info</span>
                <button type="button" class="btn-edit" onclick="openSecurityModal('systemForm', this)"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
            </div>
            <form id="systemForm" method="POST">
                <input type="hidden" name="reauth_password" value="">
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
            <form action="update_profile_picture.php" method="POST" enctype="multipart/form-data" style="margin-bottom: 24px;">
                <div style="font-weight: 600; color: var(--green); margin-bottom: 14px;"><i class="fa-solid fa-camera"></i> Profile Picture</div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($profile_picture_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="picture_current_password">Current Password</label>
                    <input type="password" id="picture_current_password" name="current_password" class="settings-input" autocomplete="current-password" required>
                </div>
                <?php $profile_picture_url = profilePictureUrl($account['profile_picture'] ?? ''); ?>
                <?php if ($profile_picture_url !== ''): ?>
                    <img src="<?php echo htmlspecialchars($profile_picture_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Current profile picture" class="profile-avatar" style="width: 84px; height: 84px; margin-bottom: 14px;">
                <?php else: ?>
                    <span class="profile-avatar profile-avatar-fallback" aria-hidden="true" style="width: 84px; height: 84px; margin-bottom: 14px;"><?php echo htmlspecialchars(strtoupper(substr($account['first_name'] ?? 'S', 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="profile_picture">Choose a profile picture (JPG, PNG, or WEBP; max 5MB)</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> Upload Picture</button>
            </form>
            <form id="adminForm" method="POST">
                <input type="hidden" name="reauth_password" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="admin_first_name" class="settings-input" value="<?php echo htmlspecialchars($account['first_name'], ENT_QUOTES, 'UTF-8'); ?>" disabled required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="admin_last_name" class="settings-input" value="<?php echo htmlspecialchars($account['last_name'], ENT_QUOTES, 'UTF-8'); ?>" disabled required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="admin_email" class="settings-input" value="<?php echo htmlspecialchars($account['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled required>
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

        <!-- ACCOUNT RECOVERY -->
        <div class="settings-card">
            <div class="settings-section-title">
                <span><i class="fa-solid fa-envelope-circle-check"></i> Account Recovery</span>
            </div>
            <p style="font-size: 0.9rem; color: #666; line-height: 1.5; margin-bottom: 20px;">
                Your account email is also your recovery email. Once updated, the new email will be used for login and password recovery.
            </p>
            <?php if (!$recovery_email_configured): ?>
                <div class="recovery-info" style="margin-bottom: 16px; background: #fff8e1; color: #7a4c00;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>Email verification is not configured on the server yet. Please configure SMTP before requesting a code.</span>
                </div>
            <?php endif; ?>
            <form action="send_otp.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($recovery_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Current Email</label>
                    <div class="recovery-email-box">
                        <i class="fa-solid fa-envelope"></i>
                        <span><?php echo htmlspecialchars($account['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="recovery_email">New Email Address</label>
                    <input type="email" id="recovery_email" name="recovery_email" class="settings-input" placeholder="Enter your new email address" required>
                    <small style="display: block; margin-top: 6px; color: #666;">This will become your new login and recovery email.</small>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="recovery_password">Current Password</label>
                    <input type="password" id="recovery_password" name="recovery_password" class="settings-input" placeholder="Enter your current password" required>
                </div>
                <button type="submit" name="update_recovery" class="btn btn-primary">
                    <i class="fa-solid fa-envelope-circle-check"></i> Send OTP Verification
                </button>
            </form>
            <div class="recovery-info" style="margin-top: 20px;">
                <i class="fa-solid fa-circle-info"></i>
                <span>A verification code will be sent to your new email address. Your password will remain unchanged.</span>
            </div>
        </div>

        <!-- 3. DATABASE & SYSTEM MAINTENANCE -->
        <div class="settings-card">
            <!-- Naka-align na ang icon at ang title gamit ang span at flex -->
            <div class="settings-section-title">
                <span><i class="fa-solid fa-database"></i> System Maintenance & Backup</span>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Database Export / Backup</label>
                <p style="font-size: 0.85rem; color: #666; margin-top: 0; margin-bottom: 15px;">
                    Verify a one-time code sent to alawihaohealth@gmail.com before downloading a SQL backup of health center records, schedules, and user accounts.
                </p>
                <?php if (!$recovery_email_configured): ?>
                    <p class="recovery-info" style="margin-bottom: 15px;">
                        Email delivery is not configured on the server. Set the ALAWAIHAO_SMTP_USERNAME and ALAWAIHAO_SMTP_PASSWORD environment variables before requesting a backup.
                    </p>
                <?php endif; ?>
                <a href="backup_db.php" class="btn-backup"><i class="fa-solid fa-shield-halved"></i> Verify &amp; Download SQL Backup</a>
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
        document.getElementById('modalPasswordInput').value = '';
        document.getElementById('securityModal').style.display = 'flex';
        document.getElementById('modalPasswordInput').focus();
    }

    function closeSecurityModal() {
        document.getElementById('securityModal').style.display = 'none';
        document.getElementById('modalPasswordInput').value = '';
    }

    function continueWithPassword() {
        const passwordInput = document.getElementById('modalPasswordInput');
        const password = passwordInput.value;
        if (password === '') {
            passwordInput.focus();
            return;
        }

        const form = document.getElementById(currentTargetFormId);
        form.querySelector('[name="reauth_password"]').value = password;
        const currentPasswordInput = form.querySelector('[name="current_pwd"]');
        if (currentPasswordInput) currentPasswordInput.value = password;
        closeSecurityModal();
        form.querySelectorAll('input').forEach(input => input.removeAttribute('disabled'));
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.removeAttribute('disabled');

        currentEditBtn.innerHTML = '<i class="fa-solid fa-lock-open"></i> Ready to Save';
        currentEditBtn.style.background = '#dcfce7';
        currentEditBtn.style.color = '#166534';
        currentEditBtn.style.borderColor = '#bbf7d0';
    }
</script>

</body>
</html>