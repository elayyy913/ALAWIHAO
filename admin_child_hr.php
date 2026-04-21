<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

// Security: 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['user_id'];
$query_role = mysqli_query($conn, "SELECT role FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($query_role);
$user_role = $user_data['role']; 

// Choose ng page based on role
if ($user_role == 'Super Admin') {
    $sidebar_to_include = 'super_admin_sidebar.php';
    $role_label = "Super Admin";
} else {
    $sidebar_to_include = 'admin_sidebar.php';
    $role_label = "Admin";
}

// Update Health Record
if (isset($_POST['update_health'])) {
    $c_id = mysqli_real_escape_string($conn, $_POST['child_id']);
    $w = mysqli_real_escape_string($conn, $_POST['weight']);
    $h = mysqli_real_escape_string($conn, $_POST['height']);
    $v = mysqli_real_escape_string($conn, $_POST['vaccine']);
    $hw_id = $_SESSION['user_id']; 

    $sql = "INSERT INTO infant_records (child_id, weight_kg, height, vaccine_taken, health_worker_id, created_at) 
            VALUES ('$c_id', '$w', '$h', '$v', '$hw_id', NOW())";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Health Record Updated!'); window.location='admin_child_list.php';</script>";
    }
}

// Fetching child data
$query = "SELECT * FROM children ORDER BY child_name ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $role_label; ?> | Child Records List</title>
    <link rel="stylesheet" href="">
    <style>
        :root {
            --sage: #8DAE74;
            --dark-sage: #6B8E55;
            --soft-sage: #F1F5ED;
            --text-main: #2D3748;
            --sidebar-width: 280px;
            --transition: all 0.3s ease-in-out;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAFC;
            margin: 0;
            display: flex;
        }

        #main {
            flex-grow: 1;
            padding: 40px;
            transition: var(--transition);
            margin-left: 0;
        }

        #main.main-content-active {
            margin-left: var(--sidebar-width);
        }

        .header-section { margin-bottom: 30px; }
        .header-section h1 { color: var(--dark-sage); margin: 0; }
        
        /* Role Badge Style */
        .role-badge {
            display: inline-block;
            background: var(--soft-sage);
            color: var(--dark-sage);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 10px;
            border: 1px solid var(--sage);
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: var(--soft-sage); color: var(--dark-sage); font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #EDF2F7; font-size: 0.9rem; }

        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view { background: var(--soft-sage); color: var(--dark-sage); margin-right: 5px; }
        .btn-edit { background: var(--sage); color: white; }

        .modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
        }
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            width: 400px;
            border-radius: 15px;
            border-top: 8px solid var(--sage);
        }
    </style>
</head>
<body>

<?php include $sidebar_to_include; ?>

<div id="main">
    <div class="header-section">
        <button onclick="toggleSidebar()" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--dark-sage); margin-bottom:10px;"></button>
        <div class="role-badge"><?php echo $role_label; ?> Access</div>
        <h1>Child & Infant Records</h1>
        <p style="color: #A0AEC0;">Alawihao Health Center Management System</p>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Child Name</th>
                    <th>Mother's Name</th>
                    <th>Gender</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['child_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['mother_name']); ?></td>
                    <td><?php echo $row['gender']; ?></td>
                    <td>
                        <a href="admin_child_record_view.php?id=<?php echo $row['id']; ?>" class="btn btn-view">Full History</a>
                        <button class="btn btn-edit" onclick="openEditModal('<?php echo $row['id']; ?>', '<?php echo $row['child_name']; ?>')">Update Health</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="color: var(--dark-sage);">Update Health Data</h3>
        <form method="POST">
            <input type="hidden" name="child_id" id="modal_id">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.8rem; font-weight:600;">Weight (kg)</label>
                <input type="number" name="weight" step="0.01" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.8rem; font-weight:600;">Height (cm)</label>
                <input type="number" name="height" step="0.1" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.8rem; font-weight:600;">Vaccine</label>
                <input type="text" name="vaccine" placeholder="Optional" style="width:100%; padding:10px; border-radius:5px; border:1px solid #ddd;">
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="closeModal()" style="padding:10px; border:none; cursor:pointer;">Cancel</button>
                <button type="submit" name="update_health" class="btn btn-edit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const content = document.getElementById('main');
    const isShowing = sidebar.classList.toggle('show');
    
    if (isShowing) {
        content.classList.add('main-content-active');
        localStorage.setItem('sidebarVisible', 'true');
    } else {
        content.classList.remove('main-content-active');
        localStorage.setItem('sidebarVisible', 'false');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('mainSidebar');
    const content = document.getElementById('main');
    const state = localStorage.getItem('sidebarVisible');

    if (state === 'true') {
        sidebar.classList.add('show');
        content.classList.add('main-content-active');
    }
});

function openEditModal(id, name) {
    document.getElementById('editModal').style.display = 'block';
    document.getElementById('modal_id').value = id;
    document.getElementById('modalTitle').innerText = "Update: " + name;
}
function closeModal() { document.getElementById('editModal').style.display = 'none'; }
</script>

</body>
</html>