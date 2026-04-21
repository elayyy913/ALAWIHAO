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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | Alawihao</title>
    <style>
        :root { --primary-green: #718355; --bg-color: #f5f5dc; --dark-green: #4f5d3d; }
        body { font-family: 'Times New Roman', serif; background-color: var(--bg-color); margin: 0; }
        
        /* FORCE SIDEBAR OPEN */
        .side-nav { width: 280px !important; }
        .closebtn { display: none !important; }
        
        #main { 
            margin-left: 280px !important; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            min-height: 100vh; 
            width: calc(100% - 280px);
        }

        /* HEADER STYLING */
        .header { 
            width: 100%; 
            background: white; 
            padding: 20px 30px; 
            border-bottom: 3px solid var(--primary-green); 
            box-sizing: border-box; 
        }
        .header h3 { margin: 0; color: var(--primary-green); letter-spacing: 1px; }
        
        /* PROFILE CARD */
        .profile-card { 
            background: white; 
            width: 90%; 
            max-width: 600px; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            border-top: 10px solid var(--primary-green); 
            margin-top: 40px; 
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: var(--primary-green); font-size: 0.9rem; }
        .info-value { font-size: 1.1rem; color: #333; padding: 8px 0; border-bottom: 1px solid #eee; margin-bottom: 10px; }
        .edit-input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; display: none; box-sizing: border-box; font-family: inherit; }

        .button-group { display: flex; gap: 10px; margin-top: 20px; }
        .btn { padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; border: none; transition: 0.3s; flex: 1; text-align: center; }
        .btn-edit { background: var(--primary-green); color: white; }
        .btn-save { background: var(--dark-green); color: white; display: none; }
        .btn-cancel { background: #ccc; color: #333; display: none; }

        .alert { padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<?php include 'user_sidebar.php'; ?>

<div id="main">
    <div class="header">
        <h3>PROFILE INFO</h3>
    </div>

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
                <button type="button" id="editBtn" class="btn btn-edit" onclick="toggleEdit(true)">Edit Profile</button>
                <button type="submit" name="update_profile" id="saveBtn" class="btn btn-save">Save Changes</button>
                <button type="button" id="cancelBtn" class="btn btn-cancel" onclick="toggleEdit(false)">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
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