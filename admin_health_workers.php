<?php
session_start();
include 'db_connect.php';

// Security: Only Super Admin and Admin allowed
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Super Admin' && $_SESSION['role'] !== 'Admin')) {
    header("Location: login.php");
    exit();
}

// 1. UPDATE ACTIVITY
if (isset($_SESSION['user_id'])) {
    $current_id = $_SESSION['user_id'];
    $stmt = ($_SESSION['role'] === 'Worker') 
        ? $conn->prepare("UPDATE health_workers SET last_activity = NOW() WHERE worker_id = ?")
        : $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->bind_param("i", $current_id);
    $stmt->execute();
}

// 2. FETCH WORKERS
$query = "SELECT uid, generated_id, first_name, last_name, email, role_type, current_status 
          FROM (
            (SELECT id AS uid, generated_id, first_name, last_name, email, 'Admin' as role_type,
             CASE WHEN last_activity >= NOW() - INTERVAL 2 MINUTE THEN 'Online' ELSE 'Offline' END as current_status 
             FROM users WHERE role IN ('Admin', 'Super Admin') AND status = 'Approved')
            UNION
            (SELECT worker_id AS uid, generated_id, first_name, last_name, email, 'Worker' as role_type,
             CASE WHEN last_activity >= NOW() - INTERVAL 2 MINUTE THEN 'Online' ELSE 'Offline' END as current_status 
             FROM health_workers WHERE status = 'approved')
          ) AS combined_workers 
          ORDER BY current_status DESC, first_name ASC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Health Worker Management | Alawihao Center</title>
    <style>
        :root { --sage: #8DAE74; --dark-sage: #6B8E55; --beige: #F5F5DC; --offline: #CBD5E0; }
        body { font-family: 'Inter', sans-serif; background: #F9F9F4; display: flex; margin:0; }
        .main-content { flex-grow: 1; padding: 40px; width: 100%; box-sizing: border-box; }
        .table-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .btn-add { background: var(--sage); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600; }
        .btn-view { color: var(--dark-sage); font-weight: 600; text-decoration: none; margin-right: 15px; font-size: 0.8rem; }
        .btn-delete { color: #E53E3E; font-weight: 600; text-decoration: none; font-size: 0.8rem; }
        .id-badge { background: #F1F5ED; color: var(--dark-sage); padding: 4px 10px; border-radius: 6px; font-family: 'Courier New', monospace; font-weight: bold; border: 1px solid #DCE6D5; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: var(--dark-sage); color: white; padding: 15px; font-size: 0.75rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #F1F5ED; font-size: 0.85rem; }
        .status-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .online-dot { background-color: #48BB78; }
        .offline-dot { background-color: var(--offline); }
    </style>
</head>
<body>

<?php include (strtolower($_SESSION['role']) == 'super admin') ? 'super_admin_sidebar.php' : 'admin_sidebar.php'; ?>

<div class="main-content">
    <div class="table-card">
        <div class="card-header">
            <h2 style="color: var(--dark-sage); margin: 0;">Personnel Directory</h2>
            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                <a href="register_worker.php" class="btn-add">+ Register Worker</a>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>System ID</th>
                    <th>Worker Name</th>
                    <th>Role</th>
                    <th>Live Status</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><span class="id-badge"><?php echo htmlspecialchars($row['generated_id'] ?? 'N/A'); ?></span></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong><br>
                        <small style="color: #A0AEC0;"><?php echo htmlspecialchars($row['email']); ?></small>
                    </td>
                    <td><span style="background:#EDF2F7; padding:4px 8px; border-radius:5px; font-size:0.7rem; font-weight:600;">
                        <?php echo strtoupper($row['role_type']); ?></span>
                    </td>
                    <td>
                        <span class="status-dot <?php echo ($row['current_status'] === 'Online') ? 'online-dot' : 'offline-dot'; ?>"></span>
                        <span style="font-weight:bold; font-size:0.75rem; color:<?php echo ($row['current_status'] === 'Online') ? '#2F855A' : '#718096'; ?>;">
                            <?php echo $row['current_status']; ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <a href="worker_details.php?id=<?php echo $row['uid']; ?>&type=<?php echo $row['role_type']; ?>" class="btn-view">View Profile</a>
                        <a href="delete_worker.php?id=<?php echo $row['uid']; ?>&type=<?php echo $row['role_type']; ?>" class="btn-delete">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>