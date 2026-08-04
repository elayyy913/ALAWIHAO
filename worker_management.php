<?php
// 1. DATABASE CONNECTION & LOGIC
include 'db_connect.php'; 
session_start();

// Bilangin ang 'pending' para sa sidebar badge
$notif_sql = "SELECT COUNT(*) as total FROM health_workers WHERE status = 'pending'";
$notif_res = mysqli_query($conn, $notif_sql);
$notif_row = mysqli_fetch_assoc($notif_res);
$pending_workers_count = $notif_row['total'];

// Manual set para sa active highlight ng sidebar
$current_page = 'workers';

// 2. PROCESSING ACTION (Approve or Disapprove)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $action = $_GET['action'];
    $new_status = ($action == 'approve') ? 'approved' : 'disapproved';

    $update_query = "UPDATE health_workers SET status = '$new_status' WHERE worker_id = '$id'";
    if (mysqli_query($conn, $update_query)) {
        header("Location: worker_management.php?msg=updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Management | Alawihao CMS</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        :root {
            --sage: #8DAE74;
            --dark-sage: #6B8E55;
            --soft-sage: #F1F5ED;
            --pure-white: #FFFFFF;
            --bg-body: #F9F9F4; /* Light beige/earthy tone */
            --text-main: #2D3748;
            --text-muted: #718096;
            --border: #EDF2F7;
            --danger: #E53E3E;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            margin: 0;
            color: var(--text-main);
        }

        /* MAIN CONTENT AREA */
        .main-content {
            margin-left: 280px; /* Space for the sidebar */
            padding: 50px;
            transition: all 0.3s ease;
        }

        .page-header {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
            letter-spacing: -0.5px;
        }

        /* TABLE STYLING */
        .table-card {
            background: var(--pure-white);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #FAFBFC;
            padding: 18px 24px;
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 700;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 20px 24px;
            font-size: 0.95rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }

        /* STATUS BADGES */
        .status-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pending { background: #FEFCBF; color: #B7791F; }
        .status-approved { background: var(--soft-sage); color: var(--dark-sage); }
        .status-disapproved { background: #FED7D7; color: var(--danger); }

        /* ACTION BUTTONS */
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .btn-approve {
            background-color: var(--sage);
            color: white;
            margin-right: 8px;
        }

        .btn-approve:hover {
            background-color: var(--dark-sage);
            box-shadow: 0 4px 12px rgba(141, 174, 116, 0.25);
        }

        .btn-disapprove {
            background-color: transparent;
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .btn-disapprove:hover {
            background-color: var(--danger);
            color: white;
        }

        .no-action {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-style: italic;
        }
    </style>
</head>
<body>

    <?php include 'super_admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Health Worker Directory</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Manage accounts and verification status for BHWs.</p>
            </div>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Date Registered</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $fetch_sql = "SELECT * FROM health_workers ORDER BY created_at DESC";
                    $fetch_res = mysqli_query($conn, $fetch_sql);

                    if (mysqli_num_rows($fetch_res) > 0) {
                        while ($row = mysqli_fetch_assoc($fetch_res)) {
                            $status = $row['status'];
                            $date = date('M d, Y', strtotime($row['created_at']));
                            ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['contact_number'] ? $row['contact_number'] : 'N/A'; ?></td>
                                <td>
                                    <span class="status-pill status-<?php echo $status; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-muted);"><?php echo $date; ?></td>
                                <td style="text-align: right;">
                                    <?php if ($status == 'pending'): ?>
                                        <a href="worker_management.php?action=approve&id=<?php echo $row['worker_id']; ?>" class="btn btn-approve">Approve</a>
                                        <a href="worker_management.php?action=disapprove&id=<?php echo $row['worker_id']; ?>" class="btn btn-disapprove">Disapprove</a>
                                    <?php else: ?>
                                        <span class="no-action">Verified</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center; padding: 40px; color: var(--text-muted);'>No health workers found in the database.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>