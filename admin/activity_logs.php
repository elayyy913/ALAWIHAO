<?php
session_start();
include '../db_connect.php';

// Function para mag-record ng activity logs
function logAction($conn, $userName, $role, $actionDesc) {
    $userName = mysqli_real_escape_string($conn, $userName);
    $role = mysqli_real_escape_string($conn, $role);
    $actionDesc = mysqli_real_escape_string($conn, $actionDesc);
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    // Siguraduhing may table para sa logs
    $create_table = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_name VARCHAR(100),
        role VARCHAR(50),
        action_description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);

    $sql = "INSERT INTO activity_logs (user_name, role, action_description, ip_address) 
            VALUES ('$userName', '$role', '$actionDesc', '$ipAddress')";
    @mysqli_query($conn, $sql);
}
// Siguraduhing may table para sa logs kung sakaling wala pa
$create_table = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100),
    role VARCHAR(50),
    action_description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_table);

// Kunin ang mga logs mula sa database (pinakabago sa itaas)
$logs_result = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs | Super Admin Dashboard</title>
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
        }

        .logs-container {
            width: 100%;
            max-width: 1000px;
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            border: 1px solid #eef2ee;
        }

        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }

        .logs-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--green);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        .logs-table th {
            background: var(--soft-sage);
            color: var(--green);
            padding: 12px 15px;
            font-weight: 600;
            border-bottom: 1px solid #d5e1cc;
        }

        .logs-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text);
        }

        .logs-table tr:hover {
            background-color: #fafdf8;
        }

        .badge-role {
            background: var(--soft-sage);
            color: var(--green);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .no-logs {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>

<?php include 'super_admin_sidebar.php'; ?>

<div class="topbar">
    <span class="page-label">System Activity Audit Logs</span>
</div>

<div id="main">
    <div class="logs-container">
        <div class="logs-header">
            <div class="logs-title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Admin & System Activities</div>
        </div>

        <table class="logs-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action Performed</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($logs_result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($logs_result)): ?>
                        <tr>
                            <td style="white-space: nowrap; color: #555;"><?php echo $row['created_at']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['user_name']); ?></strong></td>
                            <td><span class="badge-role"><?php echo htmlspecialchars($row['role']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['action_description']); ?></td>
                            <td style="color: #777; font-size: 0.85rem;"><?php echo $row['ip_address']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-logs">No activity logs recorded yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
