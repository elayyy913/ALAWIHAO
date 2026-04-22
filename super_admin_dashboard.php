<?php
session_start();
include 'db_connect.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: login.php");
    exit();
}

// Para sa notification badge sa sidebar
$pending_workers_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0;

// --- HANDLE APPROVAL/REMOVE LOGIC ---

// Worker Account Approval
if (isset($_GET['approve_worker_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_worker_id']);
    $user_data = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id' AND role = 'Admin'");
    $worker = mysqli_fetch_assoc($user_data);

    if ($worker) {
        $fname = $worker['first_name']; $lname = $worker['last_name'];
        $email = $worker['email']; $pass = $worker['password'];

        $insert_worker = "INSERT INTO health_workers (first_name, last_name, email, password, status, created_at) 
                          VALUES ('$fname', '$lname', '$email', '$pass', 'Approved', NOW())";
        
        if (mysqli_query($conn, $insert_worker)) {
            mysqli_query($conn, "UPDATE users SET status = 'Approved' WHERE id = '$id'");
            header("Location: super_admin_dashboard.php?msg=WorkerApprovedAndRecorded");
            exit();
        }
    }
}

// Infant Registration Approval
if (isset($_GET['approve_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_id']);
    $fetch_data = mysqli_query($conn, "SELECT * FROM children WHERE id = '$id'");
    $baby = mysqli_fetch_assoc($fetch_data);

    if ($baby) {
        $baby_name = $baby['child_name']; $birth_date = $baby['birth_date'];
        $gender = $baby['gender']; $weight = $baby['weight'];
        $parent_name = $baby['mother_name']; $parent_id = $baby['user_id']; 
        $address = $baby['place_of_birth'];

        $insert_query = "INSERT INTO infant_records (baby_name, birth_date, gender, weight_kg, parent_guardian, address, parent_id, created_at) 
                         VALUES ('$baby_name', '$birth_date', '$gender', '$weight', '$parent_name', '$address', '$parent_id', NOW())";
        
        if (mysqli_query($conn, $insert_query)) {
            mysqli_query($conn, "UPDATE children SET status = 'Approved' WHERE id = '$id'");
            header("Location: super_admin_dashboard.php?msg=ApprovedAndRecorded");
            exit();
        }
    }
}

// Maternal Registration Approval
if (isset($_GET['approve_preg_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_preg_id']);
    $update_status = "UPDATE maternal_registrations SET status = 'Approved' WHERE id = '$id'";
    
    if (mysqli_query($conn, $update_status)) {
        header("Location: super_admin_dashboard.php?msg=MaternalApproved"); 
        exit();
    }
}

// --- REMOVE LOGIC ---
if (isset($_GET['remove_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['remove_id']);
    $get_name = mysqli_query($conn, "SELECT child_name FROM children WHERE id = '$id'");
    $child = mysqli_fetch_assoc($get_name);
    if ($child) {
        $c_name = mysqli_real_escape_string($conn, $child['child_name']);
        mysqli_query($conn, "DELETE FROM infant_records WHERE baby_name = '$c_name'");
    }
    mysqli_query($conn, "DELETE FROM children WHERE id = '$id'");
    header("Location: super_admin_dashboard.php?msg=Removed"); 
    exit();
}

if (isset($_GET['remove_preg_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['remove_preg_id']);
    mysqli_query($conn, "DELETE FROM maternal_registrations WHERE id = '$id'");
    header("Location: super_admin_dashboard.php?msg=Removed"); 
    exit();
}

if (isset($_GET['remove_worker_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['remove_worker_id']);
    $get_email = mysqli_query($conn, "SELECT email FROM users WHERE id = '$id'");
    $user = mysqli_fetch_assoc($get_email);
    if ($user) {
        $email = mysqli_real_escape_string($conn, $user['email']);
        mysqli_query($conn, "DELETE FROM health_workers WHERE email = '$email'");
    }
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id'");
    header("Location: super_admin_dashboard.php?msg=Removed"); 
    exit();
}

// --- FETCH COUNTS ---
$total_newborns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM infant_records"))['t'] ?? 0;
$total_pregnant = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registrations WHERE status='Approved'"))['t'] ?? 0;
$total_patients = $total_newborns + $total_pregnant;
$total_workers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Approved'"))['t'] ?? 0;

$total_pending = (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status='Pending'"))['t'] ?? 0) + 
                  (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registrations WHERE status='Pending'"))['t'] ?? 0) +
                  (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0);

// --- FETCH LISTS ---
$pending_workers = mysqli_query($conn, "SELECT * FROM users WHERE role='Admin' AND status='Pending' ORDER BY created_at DESC");
$pending_list = mysqli_query($conn, "SELECT * FROM children WHERE status='Pending' ORDER BY created_at DESC");
$pending_preg_list = mysqli_query($conn, "SELECT * FROM maternal_registrations WHERE status='Pending' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard | Alawihao</title>
    <style>
        :root { --sage: #8DAE74; --dark-sage: #5A6B47; --beige: #F9F9F4; --white: #FFFFFF; --text: #2D2D2D; --border: #E1E1D7; }
        body { font-family: 'Inter', sans-serif; margin: 0; background-color: var(--beige); color: var(--text); display: flex; }
        
        .main-content { flex-grow: 1; padding: 40px; box-sizing: border-box; width: 100%; margin-left: 280px; transition: margin-left 0.3s ease-in-out; }
        .page-header { border-bottom: 2px solid var(--border); padding-bottom: 15px; margin-bottom: 30px; }
        .page-header h1 { color: var(--dark-sage); font-size: 1.8rem; margin: 0; }
        
        /* Stats Grid - Original Style */
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 4px; border-top: 5px solid var(--sage); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .stat-card h4 { font-size: 0.7rem; color: #7f8c8d; margin-bottom: 10px; text-transform: uppercase; }
        .stat-card h2 { margin: 0; font-size: 1.6rem; }
        
        /* Expanded Tables Layout */
        .dashboard-flex { display: block; width: 100%; } /* Changed from flex to block for full width */
        
        .table-container { background: var(--white); padding: 25px; border-radius: 4px; border: 1px solid var(--border); margin-bottom: 25px; width: 100%; box-sizing: border-box; }
        .table-container h3 { font-size: 1rem; color: var(--dark-sage); border-left: 4px solid var(--sage); padding-left: 10px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #F4F4ED; padding: 12px; font-size: 0.75rem; text-transform: uppercase; color: #666; }
        td { padding: 12px; border-bottom: 1px solid #F0F0F0; font-size: 0.85rem; }
        
        .btn-approve { background: var(--sage); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.7rem; }
        .btn-reject { background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.7rem; margin-left: 5px; }
    </style>
</head>
<body>

<?php include 'super_admin_sidebar.php'; ?>

<div class="main-content" id="mainDashboard">
    <div class="page-header">
        <h1>Super Admin Dashboard</h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><h4>TOTAL PATIENTS</h4><h2><?php echo $total_patients; ?></h2></div>
        <div class="stat-card"><h4>APPROVED INFANTS</h4><h2><?php echo $total_newborns; ?></h2></div>
        <div class="stat-card"><h4>APPROVED MATERNAL</h4><h2><?php echo $total_pregnant; ?></h2></div>
        <div class="stat-card" style="border-top-color:#f39c12;"><h4>FOR APPROVAL</h4><h2 style="color:#f39c12;"><?php echo $total_pending; ?></h2></div>
        <div class="stat-card"><h4>STAFF WORKERS</h4><h2><?php echo $total_workers; ?></h2></div>
    </div>

        <div class="table-container">
            <h3>Newborn Registration Approval</h3>
            <table>
                <thead><tr><th>Infant Name</th><th>Mother's Name</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (mysqli_num_rows($pending_list) > 0): while($row = mysqli_fetch_assoc($pending_list)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['child_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['mother_name']); ?></td>
                        <td>
                            <a href="?approve_id=<?php echo $row['id']; ?>" class="btn-approve">CONFIRM</a>
                            <a href="?remove_id=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('Reject this?')">REJECT</a>
                        </td>
                    </tr>
                    <?php endwhile; else: echo "<tr><td colspan='3' align='center'>No pending newborn records.</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3>Maternal Registration Approval</h3>
            <table>
                <thead><tr><th>Patient Name</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (mysqli_num_rows($pending_preg_list) > 0): while($row = mysqli_fetch_assoc($pending_preg_list)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td>
                            <a href="?approve_preg_id=<?php echo $row['id']; ?>" class="btn-approve">APPROVE</a>
                            <a href="?remove_preg_id=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('Reject this registration?')">REJECT</a>
                        </td>
                    </tr>
                    <?php endwhile; else: echo "<tr><td colspan='2' align='center'>No pending maternal registration.</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>