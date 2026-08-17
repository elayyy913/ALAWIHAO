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

// 2. Handle the Update form
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
        header("Refresh:1"); 
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
    <title>My Profile | Alawihao Health Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* SIDEBAR CONTAINER: naka-fixed, nakatago by default */
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

        /* PROFILE CARD */
        .profile-card { 
            background: var(--white); 
            width: 100%; 
            max-width: 700px; 
            padding: 35px 40px; 
            border-radius: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.06); 
            border-top: 6px solid var(--green); 
            border: 1px solid #eef2ee;
            margin-top: 20px;
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: var(--green); font-size: 0.95rem; }
        .info-value { font-size: 1.05rem; color: #333; padding: 10px 0; border-bottom: 1px solid #edf2ed; margin-bottom: 5px; }
        .edit-input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; display: none; box-sizing: border-box; font-family: inherit; font-size: 1rem; transition: border-color 0.2s; }
        .edit-input:focus { outline: none; border-color: var(--green); }

        .button-group { display: flex; gap: 12px; margin-top: 30px; }
        .btn { padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; border: none; transition: 0.2s; flex: 1; text-align: center; font-size: 1rem; }
        .btn-edit { background: var(--green); color: white; }
        .btn-edit:hover { background: var(--accent); }
        .btn-save { background: var(--accent); color: white; display: none; }
        .btn-save:hover { background: var(--green); }
        .btn-cancel { background: #e2e8f0; color: #475569; display: none; }
        .btn-cancel:hover { background: #cbd5e1; }

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
        <img src="images/logo.png" alt="Brgy Logo" class="logo-img">
    </div>
    <span class="page-label">My Profile</span>
</div>

<div id="main">
    <div class="profile-card">
        <?php echo $message; ?>
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

    // Toggle Edit Mode
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
            saveBtn.style.display = 'block';
            cancelBtn.style.display = 'block';
        } else {
            values.forEach(v => v.style.display = 'block');
            inputs.forEach(i => i.style.display = 'none');
            editBtn.style.display = 'block';
            saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
        }
    }
</script>

</body>
</html>