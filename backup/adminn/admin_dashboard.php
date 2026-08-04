<?php
session_start();
include 'db_connect.php';

// 1. SECURITY CHECK - Basic Session Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

/** * LIVE AUTH CHECK (KICK-OUT LOGIC)
 * Chine-check natin kung ang user_id sa session ay exist pa sa database.
 * Kapag binura mo sa database, dito sila mahuhuli at madi-disconnect.
 */
$session_user_id = $_SESSION['user_id'];
$session_role = $_SESSION['role'];

if ($session_role === 'Worker') {
    $check_db = mysqli_query($conn, "SELECT worker_id FROM health_workers WHERE worker_id = '$session_user_id'");
} else {
    // Para sa Admin at Super Admin
    $check_db = mysqli_query($conn, "SELECT id FROM users WHERE id = '$session_user_id'");
}

if (mysqli_num_rows($check_db) == 0) {
    // Wala na sa DB! Linisin ang session at itapon sa login.
    session_unset();
    session_destroy();
    header("Location: login.php?error=account_deleted");
    exit();
}

// Admin only access for this specific page
if ($session_role !== 'Admin' && $session_role !== 'Super Admin') {
    header("Location: login.php");
    exit();
}

// --- APPROVAL LOGIC ---

// Infant Approval
if (isset($_GET['approve_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_id']);
    $fetch_data = mysqli_query($conn, "SELECT * FROM children WHERE id = '$id'");
    $baby = mysqli_fetch_assoc($fetch_data);

    if ($baby) {
        $baby_name = $baby['child_name']; 
        $birth_date = $baby['birth_date'];
        $gender = $baby['gender']; 
        $weight = $baby['weight'];
        $parent_name = $baby['mother_name']; 
        $parent_id = $baby['user_id']; 
        $address = $baby['place_of_birth'];

        $insert_query = "INSERT INTO infant_records (baby_name, birth_date, gender, weight_kg, parent_guardian, address, parent_id, created_at) 
                         VALUES ('$baby_name', '$birth_date', '$gender', '$weight', '$parent_name', '$address', '$parent_id', NOW())";
        
        if (mysqli_query($conn, $insert_query)) {
            mysqli_query($conn, "UPDATE children SET status = 'Approved' WHERE id = '$id'");
            header("Location: admin_dashboard.php?msg=Approved");
            exit();
        }
    }
}

// Reject/Remove Infant
if (isset($_GET['remove_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['remove_id']);
    mysqli_query($conn, "DELETE FROM children WHERE id = '$id'");
    header("Location: admin_dashboard.php?msg=Removed");
    exit();
}

// Maternal Approval
if (isset($_GET['approve_preg_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_preg_id']);
    mysqli_query($conn, "UPDATE maternal_registrations SET status = 'Approved' WHERE id = '$id'");
    header("Location: admin_dashboard.php?msg=Approved"); 
    exit();
}

// 2. FETCH DATA FOR CARDS
$total_newborns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM infant_records"))['t'] ?? 0;
$total_pregnant = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registrations WHERE status='Approved'"))['t'] ?? 0;
$total_patients = $total_newborns + $total_pregnant;
$total_pending = (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status='Pending'"))['t'] ?? 0) + 
                  (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registrations WHERE status='Pending'"))['t'] ?? 0);

// 3. FETCH PENDING LISTS
$pending_infants = mysqli_query($conn, "SELECT * FROM children WHERE status='Pending' ORDER BY created_at DESC");
$pending_maternal = mysqli_query($conn, "SELECT mr.*, u.first_name, u.last_name, u.email FROM maternal_registrations mr JOIN users u ON mr.user_id = u.id WHERE mr.status='Pending' ORDER BY mr.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Alawihao Center</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        
        body { background-color: #F8FAF5; font-family: 'Inter', sans-serif; margin: 0; color: #2D3748; }
        #main { padding: 40px; margin-left: 280px; transition: 0.5s; }

        /* Dashboard Header */
        .dashboard-header { margin-bottom: 30px; }
        .dashboard-header h1 { font-size: 1.8rem; font-weight: 800; margin: 0; color: #2D3748; }
        .dashboard-header p { color: #A0AEC0; font-size: 0.9rem; margin-top: 5px; }

        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-top: 5px solid #95AF7E; }
        .stat-card h3 { color: #718096; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        .stat-card p { font-size: 2.2rem; font-weight: 700; margin: 10px 0 0 0; color: #2D3748; }
        .pending-card { border-top-color: #E9C46A; }

        /* Sections */
        .approval-section { background: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .section-title { border-left: 5px solid #95AF7E; padding-left: 15px; margin-bottom: 25px; font-size: 1.1rem; font-weight: 700; color: #4A5568; display: flex; align-items: center; }
        
        /* Tables */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #F8FAF5; color: #718096; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 18px 15px; border-bottom: 1px solid #F1F5F9; font-size: 0.95rem; }
        
        /* Action Buttons */
        .btn { padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 0.8rem; font-weight: 700; transition: 0.3s; display: inline-block; text-transform: uppercase; }
        .btn-confirm { background: #95AF7E; color: white; border: none; cursor: pointer; }
        .btn-confirm:hover { background: #829a6d; transform: translateY(-2px); }
        .btn-reject { background: #E53E3E; color: white; margin-left: 8px; }
        .btn-reject:hover { background: #c53030; }

        @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>

<?php include 'admin_sidebar.php'; ?>

<div id="main">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p>Alawihao Health Center Management System</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Patients</h3>
            <p><?php echo $total_patients; ?></p>
        </div>
        <div class="stat-card">
            <h3>Approved Infants</h3>
            <p><?php echo $total_newborns; ?></p>
        </div>
        <div class="stat-card">
            <h3>Approved Maternal</h3>
            <p><?php echo $total_pregnant; ?></p>
        </div>
        <div class="stat-card pending-card">
            <h3>For Approval</h3>
            <p><?php echo $total_pending; ?></p>
        </div>
    </div>

    <div class="approval-section">
        <div class="section-title">Newborn Registration Approval</div>
        <table>
            <thead>
                <tr>
                    <th>Infant Name</th>
                    <th>Mother's Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($pending_infants)): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['child_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['mother_name']); ?></td>
                    <td>
                        <a href="admin_dashboard.php?approve_id=<?php echo $row['id']; ?>" class="btn btn-confirm">Confirm</a>
                        <a href="admin_dashboard.php?remove_id=<?php echo $row['id']; ?>" class="btn btn-reject" onclick="return confirm('Reject this registration?')">Reject</a>
                    </td>
                </tr>
                <?php endwhile; if(mysqli_num_rows($pending_infants) == 0) echo "<tr><td colspan='3' style='text-align:center; color:#A0AEC0;'>No pending newborn registrations.</td></tr>"; ?>
            </tbody>
        </table>
    </div>

    <div class="approval-section">
        <div class="section-title">Maternal Registration Approval</div>
        <table>
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Email Address</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($pending_maternal)): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <a href="admin_dashboard.php?approve_preg_id=<?php echo $row['id']; ?>" class="btn btn-confirm">Approve</a>
                    </td>
                </tr>
                <?php endwhile; if(mysqli_num_rows($pending_maternal) == 0) echo "<tr><td colspan='3' style='text-align:center; color:#A0AEC0;'>No pending maternal registrations.</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>