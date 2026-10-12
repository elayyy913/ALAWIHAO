<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Super Admin'], true)) {
    header("Location: login.php");
    exit();
}

ensureActivityLogTable($conn);

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
            --muted: #5d6b63;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .topbar {
            background: var(--white);
            border-bottom: 3px solid var(--green);
            padding: 18px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        body.sidebar-collapsed .topbar {
            margin-left: 0;
            width: 100%;
            padding-left: 76px;
        }
        .topbar .page-label {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--green);
            letter-spacing: 0.02em;
        }

        #main {
            margin-left: var(--sidebar-width);
            padding: 44px 36px 80px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            min-height: calc(100vh - 70px);
            transition: margin-left 0.3s ease, padding 0.3s ease;
        }
        body.sidebar-collapsed #main {
            margin-left: 0 !important;
        }

        .logs-container {
            width: 100%;
            background: var(--white);
            padding: 40px 36px 30px;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.05);
            border: 1px solid #eef2ee;
        }

        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            padding-bottom: 12px;
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

        .logs-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .logs-table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.95rem;
            table-layout: fixed;
        }

        .logs-table th {
            background: var(--soft-sage);
            color: var(--green);
            padding: 18px 20px;
            font-weight: 700;
            border-bottom: 1px solid #d5e1cc;
            vertical-align: middle;
        }

        .logs-table td {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text);
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        .logs-table tr:hover {
            background-color: #fafdf8;
        }

        .badge-role {
            background: var(--soft-sage);
            color: var(--green);
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-block;
        }

        .user-cell {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .user-name {
            font-weight: 700;
            color: var(--text);
            overflow-wrap: anywhere;
        }

        .user-id-tag {
            display: inline-block;
            font-size: 0.76rem;
            color: var(--muted);
            background: #f3f7f1;
            border: 1px solid #e4ebdf;
            padding: 5px 9px;
            border-radius: 999px;
            width: fit-content;
            white-space: nowrap;
        }

        .action-cell {
            line-height: 1.5;
            color: #3c4442;
        }

        .ip-cell {
            color: #777; font-size: 0.82rem; white-space: nowrap;
        }

        .no-logs {
            text-align: center;
            padding: 32px 18px;
            color: #666;
            font-style: italic;
        }

        @media (max-width: 900px) {
            .topbar {
                margin-left: 0;
                width: 100%;
                padding: 16px 20px;
            }
            #main {
                margin-left: 0;
                padding: 24px 20px 60px;
            }
            .logs-container {
                padding: 24px 20px 18px;
            }
        }

        @media (max-width: 560px) {
            #main {
                padding: 18px 12px 40px;
            }
            .logs-container {
                padding: 20px 14px 14px;
                border-radius: 12px;
            }
            .logs-header {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<?php if ($_SESSION['role'] === 'Super Admin'): ?>
    <?php include 'super_admin_sidebar.php'; ?>
<?php else: ?>
    <?php include 'admin_sidebar.php'; ?>
<?php endif; ?>

<div class="topbar">
    <span class="page-label">System Activity Audit Logs</span>
</div>

<div id="main">
    <div class="logs-container">
        <div class="logs-header">
            <div class="logs-title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Admin, User & Schedule Activities</div>
        </div>

        <div class="logs-table-wrap">
            <table class="logs-table">
                <colgroup>
                    <col style="width: 18%;">
                    <col style="width: 18%;">
                    <col style="width: 13%;">
                    <col style="width: 37%;">
                    <col style="width: 14%;">
                </colgroup>
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
                                <td>
                                    <div class="user-cell">
                                        <?php
                                            $loggedUserName = (string) $row['user_name'];
                                            $actorId = null;
                                            if (preg_match('/\s*\(ID:\s*(\d+)\)\s*$/', $loggedUserName, $idMatch)) {
                                                $actorId = $idMatch[1];
                                                $loggedUserName = preg_replace('/\s*\(ID:\s*\d+\)\s*$/', '', $loggedUserName);
                                            }
                                        ?>
                                        <span class="user-name"><?php echo htmlspecialchars($loggedUserName); ?></span>
                                        <?php if ($actorId !== null): ?>
                                            <span class="user-id-tag">ID: <?php echo htmlspecialchars($actorId); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><span class="badge-role"><?php echo htmlspecialchars($row['role']); ?></span></td>
                                <td class="action-cell"><?php echo htmlspecialchars($row['action_description']); ?></td>
                                <td class="ip-cell"><?php echo $row['ip_address']; ?></td>
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
            const superAdminSidebar = document.getElementById('mainSidebar');
            const adminSidebar = document.getElementById('mySidenav');
            const toggleBodySidebarState = () => {
                if (superAdminSidebar) {
                    document.body.classList.toggle('sidebar-collapsed', superAdminSidebar.classList.contains('is-hidden'));
                } else if (adminSidebar) {
                    document.body.classList.toggle('sidebar-collapsed', adminSidebar.style.width === '0px');
                }
            };

            const sidebar = superAdminSidebar || adminSidebar;
            if (sidebar) {
                const observer = new MutationObserver(toggleBodySidebarState);
                observer.observe(sidebar, { attributes: true, attributeFilter: superAdminSidebar ? ['class'] : ['style'] });
                toggleBodySidebarState();
            }
    });
</script>
</body>
</html>
