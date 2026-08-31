<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// 1. Fetch current user data using 'id'
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// 2. Handle Profile Info Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $update_sql = "UPDATE users SET 
                    first_name='$first_name', 
                    last_name='$last_name', 
                    email='$email', 
                    contact_number='$contact', 
                    address='$address' 
                   WHERE id='$user_id'";
    
    if ($conn->query($update_sql) === TRUE) {
        $message = "<div class='alert success'>Profile updated successfully!</div>";
        // Refresh para makita agad ang bagong data
        $result = $conn->query("SELECT * FROM users WHERE id = '$user_id'");
        $user = $result->fetch_assoc();
    } else {
        $message = "<div class='alert error'>Update failed: " . $conn->error . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings & Profile | Alawihao Health Center</title>
    <style>
        :root {
            --green: #2d5016;
            --accent: #5a7c3a;
            --light: #8fbf5a;
            --bg: #f8fffb;
            --white: #ffffff;
            --text: #333333;
            --muted: #666666;
            --sidebar-width: 260px;
            --border-color: #edf2ed;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); }

        /* PROGRESS BAR */
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

        /* SIDEBAR CONTAINER */
        .sidebar-container {
            width: var(--sidebar-width) !important;
            min-width: var(--sidebar-width) !important;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 300;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        body.sidebar-closed .sidebar-container {
            transform: translateX(-100%);
        }

        /* TOPBAR */
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
        body.sidebar-closed .topbar {
            margin-left: 0;
            width: 100%;
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
            flex-shrink: 0;
        }

        .topbar .hamburger-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--green);
            font-size: 20px;
            padding: 4px 8px;
            border-radius: 8px;
            transition: background 0.2s;
            display: none;
            flex-shrink: 0;
        }
        body.sidebar-closed .topbar .hamburger-btn { display: inline-flex; align-items: center; justify-content: center; }
        .topbar .hamburger-btn:hover { background: #f0f4f0; }

        .topbar .logo-img {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--green);
            background: #eef2ee;
            flex-shrink: 0;
        }

        .topbar .page-label { 
            font-size: 1rem; 
            font-weight: 600; 
            color: var(--green); 
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* MAIN CONTENT */
        #main {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease, width 0.3s ease;
            padding: 30px 24px 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: calc(100vh - 70px);
        }
        body.sidebar-closed #main {
            margin-left: 0;
            width: 100%;
        }

        /* CARDS CONTAINER */
        .settings-container {
            width: 100%;
            max-width: 700px;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .settings-card { 
            background: var(--white); 
            width: 100%; 
            padding: 35px 40px; 
            border-radius: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.06); 
            border: 1px solid #eef2ee;
        }

        .profile-card-header {
            border-top: 6px solid var(--green);
            margin-top: 0;
        }

        .settings-section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--green);
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--border-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: var(--green); font-size: 0.95rem; }
        .info-value { font-size: 1.05rem; color: #333; padding: 8px 0; border-bottom: 1px solid var(--border-color); margin-bottom: 5px; }
        
        .edit-input, .settings-input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ccc; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-family: inherit; 
            font-size: 1rem; 
            transition: border-color 0.2s; 
        }
        .edit-input { display: none; }
        .edit-input:focus, .settings-input:focus { outline: none; border-color: var(--green); }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            margin-bottom: 15px;
            cursor: pointer;
            color: var(--text);
            font-weight: 500;
        }
        .checkbox-group input {
            width: 18px;
            height: 18px;
            accent-color: var(--green);
            cursor: pointer;
        }

        .button-group { display: flex; gap: 12px; margin-top: 25px; }
        .btn { padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; border: none; transition: 0.2s; text-align: center; font-size: 1rem; }
        .btn-edit { background: var(--green); color: white; flex: 1; }
        .btn-edit:hover { background: var(--accent); }
        .btn-save { background: var(--accent); color: white; flex: 1; display: none; }
        .btn-save:hover { background: var(--green); }
        .btn-cancel { background: #e2e8f0; color: #475569; flex: 1; display: none; }
        .btn-cancel:hover { background: #cbd5e1; }
        
        .btn-primary-action { background: var(--green); color: white; width: 100%; }
        .btn-primary-action:hover { background: var(--accent); }

        /* Danger Zone Styles */
        .danger-card { border: 1px solid #FED7D7; background-color: #FFF5F5; }
        .danger-title { color: #E53E3E; border-bottom: 2px solid #FED7D7; }
        .danger-text { font-size: 0.9rem; color: #718096; margin-bottom: 20px; line-height: 1.5; }
        .btn-danger { background-color: #E53E3E; color: #FFFFFF; width: 100%; }
        .btn-danger:hover { background-color: #C53030; }

        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body class="sidebar-closed">

<div id="progressBar" class="progress-bar"></div>

<div class="sidebar-container">
    <?php include 'user_sidebar.php'; ?>
</div>

<div class="topbar">
    <div class="topbar-brand">
        <button class="hamburger-btn" onclick="toggleSidebar()" title="Toggle Sidebar">
            <i class="fa fa-bars"></i>
        </button>
        <img src="images/logo.jpg" alt="Brgy Logo" class="logo-img">
    </div>
    <span class="page-label">Account Settings & Profile</span>
</div>

<div id="main">
    <div class="settings-container">
        
        <?php echo $message; ?>

        <!-- 1. MY PROFILE SECTION -->
        <div class="settings-card profile-card-header">
            <div class="settings-section-title"><i class="fa fa-user-circle"></i> My Profile Information</div>
            <form id="profileForm" method="POST">
                
                <div class="form-group">
                    <label>First Name</label>
                    <div class="info-value"><?php echo htmlspecialchars($user['first_name'] ?? ''); ?></div>
                    <input type="text" name="first_name" class="edit-input" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Last Name</label>
                    <div class="info-value"><?php echo htmlspecialchars($user['last_name'] ?? ''); ?></div>
                    <input type="text" name="last_name" class="edit-input" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="info-value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                    <input type="email" name="email" class="edit-input" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <div class="info-value"><?php echo htmlspecialchars($user['contact_number'] ?? 'Not set'); ?></div>
                    <input type="text" name="contact" class="edit-input" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Home Address</label>
                    <div class="info-value"><?php echo htmlspecialchars($user['address'] ?? 'Not set'); ?></div>
                    <input type="text" name="address" class="edit-input" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                </div>

                <div class="button-group">
                    <button type="button" id="editBtn" class="btn btn-edit" onclick="toggleEdit(true)"><i class="fa fa-pen-to-square"></i> Edit Profile</button>
                    <button type="submit" name="update_profile" id="saveBtn" class="btn btn-save"><i class="fa fa-check"></i> Save Changes</button>
                    <button type="button" id="cancelBtn" class="btn btn-cancel" onclick="toggleEdit(false)"><i class="fa fa-xmark"></i> Cancel</button>
                </div>
            </form>
        </div>

        <!-- 2. SECURITY & NOTIFICATIONS SECTION -->
        <div class="settings-card">
            <form action="update_settings.php" method="POST">
                <div class="settings-section-title"><i class="fa fa-shield-halved"></i> Security Credentials</div>

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="settings-input" placeholder="Enter current password to change password">
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="settings-input" placeholder="Leave blank if you don't wish to change">
                </div>

                <div class="settings-section-title" style="margin-top: 30px;"><i class="fa fa-bell"></i> Notification Preferences</div>

                <label class="checkbox-group">
                    <input type="checkbox" name="sms_reminders" checked>
                    Receive SMS notifications for upcoming check-up schedules
                </label>

                <label class="checkbox-group">
                    <input type="checkbox" name="email_updates">
                    Receive email updates regarding health bulletins and advisories
                </label>

                <div style="margin-top: 25px;">
                    <button type="submit" class="btn btn-primary-action">Save Security & Preferences</button>
                </div>
            </form>
        </div>

        <!-- 3. DANGER ZONE SECTION -->
        <div class="settings-card danger-card">
            <div class="settings-section-title danger-title"><i class="fa fa-triangle-exclamation"></i> Danger Zone</div>
            <p class="danger-text">Once you delete your account, there is no going back. All your saved profile records and history in the portal will be permanently removed.</p>
            
            <form action="delete_account.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete your account? This action cannot be undone.');">
                <div class="form-group">
                    <label for="delete_password" style="color: #E53E3E;">Enter Password to Confirm Deletion</label>
                    <input type="password" id="delete_password" name="delete_password" class="settings-input" placeholder="Type your password here" required style="border-color: #FEB2B2;">
                </div>
                <button type="submit" class="btn btn-danger">Delete My Account</button>
            </form>
        </div>

    </div>
</div>

<script>
    // Reading progress bar
    window.addEventListener('scroll', () => {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = (scrollTop / docHeight) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
    });

    // Toggle Sidebar Function
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-closed');
    }

    // Toggle Edit Mode for Profile
    function toggleEdit(isEditing) {
        const values = document.querySelectorAll('.info-value');
        const inputs = document.querySelectorAll('.edit-input');
        const editBtn = document.getElementById('editBtn');
        const saveBtn = document.getElementById('saveBtn');
        const cancelBtn = document.getElementById('cancelBtn');

        if (isEditing) {
            values.forEach(v => v.style.display = 'none');
            inputs.forEach(i => i.style.display = 'block');
            editBtn.style.display = 'none';
            saveBtn.style.display = 'flex';
            cancelBtn.style.display = 'flex';
        } else {
            values.forEach(v => v.style.display = 'block');
            inputs.forEach(i => i.style.display = 'none');
            editBtn.style.display = 'flex';
            saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
        }
    }
</script>

</body>
</html>