<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$message = "";

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_profile'])) {
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $email = $_POST['email'];
    
    $update_sql = "UPDATE users SET first_name=?, last_name=?, email=? WHERE id=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssi", $fname, $lname, $email, $admin_id);
    
    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
    } else {
        $message = "Error updating profile.";
    }
}

// Fetch current data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile | Alawihao Health</title>
    <style>
        body { background-color: #f5f5dc; margin: 0; font-family: 'Times New Roman', serif; transition: margin-left .5s; }
        #main { transition: margin-left .5s; padding: 60px 40px; display: flex; justify-content: center; }
        
        .profile-card {
            background: white; width: 100%; max-width: 500px; padding: 40px;
            border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-bottom: 5px solid #718355; position: relative;
        }

        .profile-card h2 { color: #718355; margin-bottom: 30px; text-align: center; letter-spacing: 1px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #888; margin-bottom: 5px; font-size: 0.8rem; text-transform: uppercase; }
        
        .form-group input {
            width: 100%; padding: 12px; border: 1px solid #eee; border-radius: 8px;
            background-color: #f9f9f9; font-family: 'Times New Roman', serif;
            box-sizing: border-box; color: #555;
        }

        /* When editable */
        .form-group input:not([readonly]) {
            background-color: #fff; border: 1px solid #718355; color: #000;
        }

        .btn-container { display: flex; gap: 10px; margin-top: 20px; }
        
        .edit-btn, .save-btn {
            background-color: #718355; color: white; border: none;
            padding: 12px; width: 100%; border-radius: 8px; cursor: pointer; transition: 0.3s;
        }

        .cancel-btn {
            background-color: #bc8f8f; color: white; border: none;
            padding: 12px; width: 100%; border-radius: 8px; cursor: pointer; transition: 0.3s;
            display: none; /* Hidden by default */
        }

        .save-btn { display: none; } /* Hidden by default */

        .edit-btn:hover { background-color: #5a6b43; }
        .cancel-btn:hover { background-color: #a07a7a; }
        .msg { text-align: center; color: #718355; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

<?php include 'admin_sidebar.php'; ?>
<button class="open-btn" onclick="openNav()">&#9776;</button>

<div id="main">
    <div class="profile-card">
        <h2>Admin Profile</h2>
        
        <?php if($message) echo "<p class='msg'>$message</p>"; ?>

        <form id="profileForm" method="POST">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" id="fname" value="<?php echo $admin['first_name']; ?>" readonly>
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" id="lname" value="<?php echo $admin['last_name']; ?>" readonly>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" id="email" value="<?php echo $admin['email']; ?>" readonly>
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

        // Reset values (optional, or just refresh)
        // location.reload(); 

        // Toggle Buttons back
        document.getElementById("editBtn").style.display = "block";
        document.getElementById("saveBtn").style.display = "none";
        document.getElementById("cancelBtn").style.display = "none";
    }
</script>

</body>
</html>