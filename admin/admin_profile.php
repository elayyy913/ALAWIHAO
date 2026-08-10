<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$message = "";

// Dynamic column detection para sa contact number (contact_number o phone)
$contact_col = 'contact_number';
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'phone'");
if ($col_check && mysqli_num_rows($col_check) > 0) {
    $contact_col = 'phone';
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_profile'])) {
    $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact_number']);
    
    $update_sql = "UPDATE users SET first_name=?, last_name=?, email=?, $contact_col=? WHERE id=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssi", $fname, $lname, $email, $contact, $admin_id);
    
    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
    } else {
        $message = "Error updating profile.";
    }
}

// Fetch current data
$sql = "SELECT *, COALESCE($contact_col, '') as contact_val FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Alawihao Health</title>
    <style>
        :root { 
            --sage: #8DAE74; 
            --dark-sage: #6B8E55; 
            --light-bg: #F8FAFC; 
            --card-bg: #FFFFFF; 
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            background-color: var(--light-bg); 
            color: var(--text-main);
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
        }

        #main { 
            flex: 1; 
            padding: 40px; 
            overflow-y: auto; 
            box-sizing: border-box;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .profile-card {
            background: var(--card-bg);
            width: 100%;
            max-width: 520px;
            padding: 32px 36px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            border-top: 4px solid var(--sage);
        }

        .profile-card h2 { 
            color: var(--dark-sage); 
            margin-top: 0;
            margin-bottom: 24px; 
            font-size: 1.35rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .profile-card h2::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 18px;
            background-color: var(--dark-sage);
            margin-right: 10px;
            border-radius: 2px;
        }

        .form-group { 
            margin-bottom: 18px; 
        }
        
        .form-group label { 
            display: block; 
            color: var(--text-muted); 
            margin-bottom: 6px; 
            font-size: 0.75rem; 
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
        }
        
        .form-group input {
            width: 100%; 
            padding: 10px 14px; 
            border: 1px solid var(--border-color); 
            border-radius: 6px;
            background-color: #F8FAFC; 
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            box-sizing: border-box; 
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        /* When editable */
        .form-group input:not([readonly]) {
            background-color: #FFFFFF; 
            border: 1px solid var(--sage); 
            outline: none;
            box-shadow: 0 0 0 3px rgba(141, 174, 116, 0.15);
        }

        .btn-container { 
            display: flex; 
            gap: 12px; 
            margin-top: 28px; 
        }
        
        .edit-btn, .save-btn {
            background-color: var(--dark-sage); 
            color: white; 
            border: none;
            padding: 10px 16px; 
            width: 100%; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 600;
            font-size: 0.85rem;
            transition: background-color 0.2s ease;
        }

        .cancel-btn {
            background-color: #E2E8F0; 
            color: #475569; 
            border: none;
            padding: 10px 16px; 
            width: 100%; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 600;
            font-size: 0.85rem;
            transition: background-color 0.2s ease;
            display: none; /* Hidden by default */
        }

        .save-btn { display: none; } /* Hidden by default */

        .edit-btn:hover { background-color: #587645; }
        .save-btn:hover { background-color: #587645; }
        .cancel-btn:hover { background-color: #CBD5E1; }

        .success-alert {
            background: #F0FDF4;
            color: #16A34A;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 1px solid #DCFCE7;
        }
    </style>
</head>
<body>

<?php include 'admin_sidebar.php'; ?>

<div id="main">
    <div class="profile-card">
        <h2>Admin Profile</h2>
        
        <?php if($message): ?>
            <div class="success-alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form id="profileForm" method="POST">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" id="fname" value="<?php echo htmlspecialchars($admin['first_name'] ?? ''); ?>" readonly required>
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" id="lname" value="<?php echo htmlspecialchars($admin['last_name'] ?? ''); ?>" readonly required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" readonly required>
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" id="contact" value="<?php echo htmlspecialchars($admin['contact_val'] ?? ''); ?>" readonly placeholder="e.g. 09123456789">
            </div>

            <div class="btn-container">
                <button type="button" id="editBtn" class="edit-btn" onclick="enableEdit()">Edit Profile</button>
                <button type="submit" name="save_profile" id="saveBtn" class="save-btn">Save Changes</button>
                <button type="button" id="cancelBtn" class="cancel-btn" onclick="disableEdit()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function enableEdit() {
        // Remove readonly
        document.getElementById("fname").readOnly = false;
        document.getElementById("lname").readOnly = false;
        document.getElementById("email").readOnly = false;
        document.getElementById("contact").readOnly = false;

        // Focus on first input
        document.getElementById("fname").focus();

        // Toggle Buttons
        document.getElementById("editBtn").style.display = "none";
        document.getElementById("saveBtn").style.display = "block";
        document.getElementById("cancelBtn").style.display = "block";
    }

    function disableEdit() {
        // Restore readonly
        document.getElementById("fname").readOnly = true;
        document.getElementById("lname").readOnly = true;
        document.getElementById("email").readOnly = true;
        document.getElementById("contact").readOnly = true;

        // Reset values by reloading or restoring form
        document.getElementById("profileForm").reset();

        // Toggle Buttons back
        document.getElementById("editBtn").style.display = "block";
        document.getElementById("saveBtn").style.display = "none";
        document.getElementById("cancelBtn").style.display = "none";
    }
</script>

</body>
</html>