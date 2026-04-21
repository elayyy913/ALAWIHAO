<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

// 1. Siguraduhin na may ID na pinasa at naka-login ang user
if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header("Location: user_child_records.php");
    exit();
}

$record_id = mysqli_real_escape_string($conn, $_GET['id']);
$user_id = $_SESSION['user_id'];

// 2. FETCH REAL DATA: Kinukuha natin lahat ng info sa infant_records table
// Kasama na ang age calculation para accurate
$query = "SELECT *, 
          TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) AS age_years, 
          TIMESTAMPDIFF(MONTH, birth_date, CURDATE()) % 12 AS age_months,
          TIMESTAMPDIFF(DAY, birth_date, CURDATE()) % 30 AS age_days
          FROM infant_records 
          WHERE id = '$record_id' AND parent_id = '$user_id'";

$res = mysqli_query($conn, $query);
$baby = mysqli_fetch_assoc($res);

// 3. Security: Kung walang nahanap o hindi kay user ang record, balik sa listahan
if (!$baby) {
    echo "<script>alert('Access Denied or Record Not Found.'); window.location='user_child_records.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Health Profile | <?php echo htmlspecialchars($baby['baby_name']); ?></title>
    <style>
        :root { --primary-green: #718355; --bg-color: #f5f5dc; --white: #ffffff; }
        body { font-family: 'Times New Roman', serif; background-color: var(--bg-color); margin: 0; display: flex; }
        #main { margin-left: 280px; width: calc(100% - 280px); padding: 40px; box-sizing: border-box; }
        
        .profile-card { background: var(--white); width: 100%; max-width: 900px; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border-top: 10px solid var(--primary-green); margin: 0 auto; }
        
        .back-link { text-decoration: none; color: var(--primary-green); font-weight: bold; margin-bottom: 25px; display: inline-block; transition: 0.3s; }
        .back-link:hover { transform: translateX(-5px); }

        /* Real Data Grid */
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px; }
        .info-item { background: #f9fafb; padding: 20px; border-radius: 12px; border: 1px solid #edf2f7; }
        .info-label { font-size: 0.7rem; color: #718096; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
        .info-value { font-size: 1.1rem; color: #2d3748; font-weight: bold; display: block; margin-top: 5px; }

        .section-title { color: var(--primary-green); border-bottom: 2px solid #edf2f7; padding-bottom: 10px; margin-top: 40px; font-size: 1.3rem; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .data-table th { text-align: left; padding: 12px; background: #f7fafc; color: var(--primary-green); font-size: 0.8rem; border-bottom: 2px solid #edf2f7; }
        .data-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; }

        @media print { .back-link, .print-btn { display: none; } #main { margin-left: 0; width: 100%; } }
        .print-btn { float: right; background: var(--primary-green); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

<?php include 'user_sidebar.php'; ?>

<div id="main">
    <div style="width: 100%; max-width: 900px; margin: 0 auto;">
        <a href="user_child_records.php" class="back-link">← Return to Records List</a>
        
        <div class="profile-card">
            <button class="print-btn" onclick="window.print()">Print Official Record</button>
            <h1 style="color: var(--primary-green); margin: 0;">Infant Health Profile</h1>
            <p style="color: #a0aec0; margin-top: 5px;">Official Document - Alawihao Health Center</p>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Baby's Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($baby['baby_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Current Age</span>
                    <span class="info-value"><?php echo "{$baby['age_years']}y {$baby['age_months']}m {$baby['age_days']}d"; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Gender</span>
                    <span class="info-value"><?php echo htmlspecialchars($baby['gender']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Current Weight</span>
                    <span class="info-value"><?php echo $baby['weight_kg']; ?> kg</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Birthday</span>
                    <span class="info-value"><?php echo date('F d, Y', strtotime($baby['birth_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Registered Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($baby['address']); ?></span>
                </div>
            </div>

            <h3 class="section-title">Medical History & Immunization</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Health Event / Vaccine</th>
                        <th>Administered Date</th>
                        <th>Health Worker ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>BCG (Anti-TB Vaccine)</strong></td>
                        <td><?php echo date('M d, Y', strtotime($baby['birth_date'])); ?></td>
                        <td>HW-<?php echo $baby['health_worker_id'] ?? 'N/A'; ?></td>
                        <td><span style="color: #38a169; font-weight: bold;">✔ ADMINISTERED</span></td>
                    </tr>
                    
                    <?php if(!empty($baby['contact_number'])): ?>
                    <tr>
                        <td><strong>Follow-up Contact</strong></td>
                        <td>---</td>
                        <td>---</td>
                        <td><?php echo $baby['contact_number']; ?></td>
                    </tr>
                    <?php endif; ?>

                    <tr>
                        <td colspan="4" style="text-align: center; color: #cbd5e0; padding: 30px;">
                            <i>The Health Center is updating the digital immunization records. Please visit the center for physical booklet updates.</i>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>