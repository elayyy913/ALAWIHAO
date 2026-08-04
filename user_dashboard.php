<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; 

/** * BAGONG QUERY:
 * 1. status != 'Approved' - para Pending lang ang makita dito.
 * 2. ORDER BY id DESC LIMIT 1 - para yung pinaka-latest lang na niregister ang lalabas.
 */
$query = "SELECT * FROM children WHERE user_id = '$user_id' AND status != 'Approved' ORDER BY id DESC LIMIT 1";
$my_records = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Status | Alawihao</title>
    <style>
        :root { 
            --primary-green: #718355; 
            --bg-color: #f5f5dc; 
            --white: #ffffff; 
            --sage: #718355;
        }
        
        body { 
            background-color: var(--bg-color); 
            font-family: 'Times New Roman', serif; 
            display: flex; 
            margin: 0; 
        }

        #main { 
            margin-left: 280px; 
            padding: 40px; 
            width: calc(100% - 280px); 
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header-title {
            color: var(--primary-green);
            text-align: center;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }

        .register-container {
            width: 100%;
            max-width: 800px;
            text-align: right;
            margin-bottom: 20px;
        }

        .btn-register {
            background-color: var(--primary-green);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(113, 131, 85, 0.2);
        }

        .btn-register:hover { background-color: #5a6b43; transform: translateY(-2px); }

        /* ID CARD STYLE - SAME SA SUPERADMIN VIBE */
        .id-card {
            background: var(--white); 
            padding: 40px; 
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 800px; 
            width: 100%;
            border-top: 10px solid var(--primary-green);
            box-sizing: border-box;
        }

        .id-card h2 { 
            color: var(--primary-green); 
            margin-top: 0;
            margin-bottom: 25px; 
            border-bottom: 2px solid #f0f0f0; 
            padding-bottom: 10px;
            font-style: italic;
        }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        
        .label { 
            color: #888; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 1.2px; 
            display: block; 
            margin-bottom: 5px; 
        }

        .data-value { 
            font-size: 1rem; 
            color: #333; 
            background: #fafafa; 
            padding: 12px; 
            border-radius: 8px; 
            border: 1px solid #eee;
            display: block;
            font-weight: bold;
        }

        /* ONGOING STATUS STYLE */
        .status-container {
            margin-top: 35px;
            padding: 25px;
            text-align: center;
            border-radius: 15px;
            background-color: #fffaf0; 
            color: #e67e22; 
            border: 2px dashed #ffcc80;
        }

        .status-icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 10px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .empty-state {
            text-align: center;
            background: white;
            padding: 60px;
            border-radius: 20px;
            max-width: 600px;
            border: 1px solid #eee;
        }
    </style>
</head>
<body>

<?php include 'user_sidebar.php'; ?>

<div id="main">
    <h2 class="header-title">REGISTRATION PROGRESS</h2>

    <div class="register-container">
        <a href="user_reg_newborn.php" class="btn-register">+ Register New Baby</a>
    </div>

    <?php if (mysqli_num_rows($my_records) > 0): ?>
        <?php $row = mysqli_fetch_assoc($my_records); ?>
            
            <div class="id-card">
                <h2>Pending Registration: <?php echo htmlspecialchars($row['child_name']); ?></h2>
                
                <div class="info-grid">
                    <div class="info-group">
                        <span class="label">Full Name of Baby</span>
                        <span class="data-value"><?php echo htmlspecialchars($row['child_name']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="label">Mother's Full Name</span>
                        <span class="data-value"><?php echo htmlspecialchars($row['mother_name']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="label">Gender</span>
                        <span class="data-value"><?php echo $row['gender']; ?></span>
                    </div>
                    <div class="info-group">
                        <span class="label">Birth Date</span>
                        <span class="data-value"><?php echo date('F d, Y', strtotime($row['birth_date'])); ?></span>
                    </div>
                </div>

                <div class="status-container">
                    <span class="status-icon">⏳</span>
                    <span style="font-size: 1.1rem; font-weight: 900;">ON-GOING APPROVAL</span>
                    <p style="font-size: 0.9rem; font-weight: normal; margin-top: 10px; color: #666;">
                        Your recent registration for <strong><?php echo htmlspecialchars($row['child_name']); ?></strong> is being reviewed.<br>
                        Once approved, it will be moved to your <strong>Official Records</strong>.
                    </p>
                </div>
            </div>

    <?php else: ?>
        <div class="empty-state">
            <i style="font-size: 3rem; color: #ccc; display: block; margin-bottom: 15px;">✅</i>
            <h3 style="color: var(--primary-green); margin: 0;">No Pending Registrations</h3>
            <p style="color: #888;">All your registrations are approved or you haven't submitted one yet.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>