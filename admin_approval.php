<?php
session_start();
include 'db_connect.php';

// Check if user is Admin (Optional: Add your admin session check here)

$message = "";

// Handle Approval Action
if (isset($_GET['approve_infant'])) {
    $id = $_GET['approve_infant'];
    $conn->query("UPDATE infant_registrations SET status = 'Approved' WHERE id = '$id'");
    $message = "<div class='alert success'>Infant registration approved!</div>";
}

// Fetch Pending Newborns
$newborns = $conn->query("SELECT * FROM infant_registrations WHERE status = 'Pending'");
// Fetch Pending Pregnancies
$pregnancies = $conn->query("SELECT * FROM maternal_registrations WHERE status = 'Pending'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Approvals | Alawihao Health</title>
    <style>
        :root { --primary-green: #718355; --bg: #f5f5dc; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); padding: 40px; }
        .container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h2 { color: var(--primary-green); border-bottom: 2px solid var(--primary-green); padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th { background: var(--primary-green); color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        .btn-approve { background: #5cb85c; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.9rem; }
        .alert { padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Pending Approvals</h2>
    <?php echo $message; ?>

    <h3>Newborn Enrollments</h3>
    <table>
        <tr>
            <th>Baby Name</th>
            <th>Mother</th>
            <th>Birth Date</th>
            <th>Action</th>
        </tr>
        <?php while($row = $newborns->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['baby_name']; ?></td>
            <td><?php echo $row['mother_name']; ?></td>
            <td><?php echo $row['birth_date']; ?></td>
            <td><a href="?approve_infant=<?php echo $row['id']; ?>" class="btn-approve">Approve</a></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <h3 style="margin-top:40px;">Pregnancy Enrollments</h3>
    <table>
        <tr>
            <th>Mother's Name</th>
            <th>LMP</th>
            <th>EDC (Expected)</th>
            <th>Action</th>
        </tr>
        <?php while($row = $pregnancies->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['full_name']; ?></td>
            <td><?php echo $row['lmp']; ?></td>
            <td><?php echo $row['edc']; ?></td>
            <td><a href="#" class="btn-approve">Approve</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>