<?php
session_start();
include 'db_connect.php';

// Check kung naka-login ang user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/** * PRIVACY LOGIC:
 * Kukunin lang natin ang schedules kung saan ang 'user_id' sa table 
 * ay kapareho ng ID ng taong naka-login ngayon.
 */
$query = "SELECT s.*, i.baby_name 
          FROM infant_schedule s 
          JOIN infant_records i ON s.infant_id = i.id 
          WHERE s.user_id = ? 
          ORDER BY s.vaccination_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_schedules = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Child's Schedule | Alawihao</title>
    <style>
        :root { --sage: #718355; --bg: #f5f5dc; --white: #ffffff; }
        body { background-color: var(--bg); font-family: 'Times New Roman', serif; margin: 0; display: flex; }
        
        #main { margin-left: 280px; padding: 40px; width: calc(100% - 280px); }
        
        .header-box { 
            background: var(--white); 
            padding: 25px; 
            border-radius: 15px; 
            border-bottom: 5px solid var(--sage); 
            margin-bottom: 30px; 
        }

        .sched-card { 
            background: var(--white); 
            padding: 20px; 
            border-radius: 15px; 
            margin-bottom: 15px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-left: 8px solid var(--sage);
        }

        .date-badge {
            background: #f1f4ea;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            min-width: 80px;
        }

        .status-pill {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .pending { background: #fffcf0; color: #d4a017; border: 1px solid #d4a017; }
        .rescheduled { background: #fef2f2; color: #b91c1c; border: 1px solid #b91c1c; }
        .completed { background: #f0f4e8; color: var(--sage); border: 1px solid var(--sage); }
    </style>
</head>
<body>

<?php include 'user_sidebar.php'; ?>

<div id="main">
    <div class="header-box">
        <h2 style="margin:0; color: var(--sage); letter-spacing: 2px;">VACCINATION APPOINTMENTS</h2>
    </div>

    <?php if($my_schedules->num_rows > 0): ?>
        <?php while($row = $my_schedules->fetch_assoc()): ?>
            <div class="sched-card">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="date-badge">
                        <span style="font-size: 0.8rem; color: #888; text-transform: uppercase;">
                            <?= date('M', strtotime($row['vaccination_date'])) ?>
                        </span><br>
                        <b style="font-size: 1.5rem; color: var(--sage);">
                            <?= date('d', strtotime($row['vaccination_date'])) ?>
                        </b>
                    </div>
                    <div>
                        <h3 style="margin:0; color: #333;"><?= htmlspecialchars($row['baby_name']) ?></h3>
                        <p style="margin:3px 0; color: #666; font-size: 0.9rem;">
                            <?= htmlspecialchars($row['vaccine_name']) ?> (<?= $row['dose_number'] ?>)
                        </p>
                        <small style="color: #aaa;">Time: <?= date('h:i A', strtotime($row['next_appointment'])) ?></small>
                    </div>
                </div>
                
                <div class="status-pill <?= strtolower($row['status']) ?>">
                    <?= strtoupper($row['status']) ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="background: white; padding: 50px; text-align: center; border-radius: 20px; color: #ccc;">
            <p>No Scheduled Appointment </p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>