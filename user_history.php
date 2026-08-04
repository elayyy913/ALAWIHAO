<?php
session_start();
include 'db_connect.php';

// 1. SECURITY CHECK: Dapat nakalogin ang user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. KUNIN ANG INFO NG USER (Mula sa users table)
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($user_res);

// Pagsamahin ang pangalan para sa display
$fname = isset($user_data['first_name']) ? $user_data['first_name'] : "";
$lname = isset($user_data['last_name']) ? $user_data['last_name'] : "";
$display_name = trim($fname . " " . $lname);

// 3. KUNIN ANG MATERNAL LOGS (History ni Nanay)
$m_query = "SELECT * FROM maternal_logs WHERE mother_id = '$user_id' ORDER BY checkup_date DESC";
$m_result = mysqli_query($conn, $m_query);

// 4. KUNIN ANG MGA ANAK (Infant Records) - Gamit ang parent_id base sa DB structure mo
$c_query = "SELECT * FROM infant_records WHERE parent_id = '$user_id'";
$c_result = mysqli_query($conn, $c_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal | Alawihao Health Center</title>
    <style>
        :root {
            --sage-green: #89936C;
            --sage-light: #A7AD8D;
            --bg-beige: #F8F9F2;
            --white: #ffffff;
            --text-main: #4A4A4A;
            --accent-tan: #D6D0C2;
        }

        body { 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            background-color: var(--bg-beige); 
            margin: 0; 
            display: flex; /* Para sa Sidebar Layout */
            color: var(--text-main);
        }

        /* Layout Positioning */
        #main-content { 
            margin-left: 280px; /* Offset para sa sidebar */
            padding: 50px;
            width: 100%;
            min-height: 100vh;
        }

        .dashboard-header { margin-bottom: 40px; }
        .dashboard-header h1 { 
            color: var(--sage-green); 
            font-size: 2.2rem; 
            margin: 0; 
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .dashboard-header p { color: #888; margin-top: 5px; font-size: 1.1rem; }

        /* Professional Tabs */
        .tab-wrapper { 
            display: flex; 
            gap: 5px; 
            background: #E9EDDC; 
            padding: 6px; 
            border-radius: 14px; 
            width: fit-content;
            margin-bottom: 30px;
        }
        
        .tab-btn { 
            padding: 12px 30px; 
            border: none; 
            border-radius: 10px; 
            background: transparent; 
            color: #71785C; 
            cursor: pointer; 
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .tab-btn.active { 
            background: var(--white); 
            color: var(--sage-green); 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        /* Content Cards */
        .history-card { 
            background: var(--white); 
            padding: 30px; 
            border-radius: 24px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid rgba(137, 147, 108, 0.1);
            margin-bottom: 20px;
        }

        .hidden { display: none; }

        /* Maternal Table Style */
        .maternal-log {
            display: grid;
            grid-template-columns: 1.5fr 3fr;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #F0F2ED;
        }
        .maternal-log:last-child { border: none; }

        .date-badge {
            background: #F4F6F0;
            color: var(--sage-green);
            padding: 15px;
            border-radius: 15px;
            text-align: center;
            height: fit-content;
        }

        .vitals-row { display: flex; gap: 30px; margin-top: 10px; }
        .vitals-row span { font-size: 0.9rem; color: #999; }
        .vitals-row b { color: var(--text-main); font-size: 1.1rem; display: block; }

        /* Infant List Style */
        .child-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px;
            background: var(--white);
            border-radius: 20px;
            margin-bottom: 15px;
            transition: transform 0.2s;
            border: 1px solid #f0f0f0;
        }
        .child-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        .btn-outline {
            text-decoration: none;
            color: var(--sage-green);
            border: 2px solid var(--sage-green);
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            transition: 0.3s;
        }
        .btn-outline:hover { background: var(--sage-green); color: white; }

        @media (max-width: 1024px) {
            #main-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

    <?php include 'user_sidebar.php'; ?>

    <div id="main-content">
        <header class="dashboard-header">
            <h1>Patient Portal</h1>
            <p>Welcome back, <strong><?php echo htmlspecialchars($fname ?: 'User'); ?></strong>.</p>
        </header>

        <nav class="tab-wrapper">
            <button class="tab-btn active" onclick="switchView(event, 'maternal')">Maternal Records</button>
            <button class="tab-btn" onclick="switchView(event, 'infant')">Child Records</button>
        </nav>

        <div id="maternal-view" class="view-content">
            <div class="history-card">
                <h3 style="margin-top:0; color:var(--sage-green);">Prenatal & Health History</h3>
                <?php if(mysqli_num_rows($m_result) > 0): ?>
                    <?php while($m = mysqli_fetch_assoc($m_result)): ?>
                        <div class="maternal-log">
                            <div class="date-badge">
                                <small><?php echo date('Y', strtotime($m['checkup_date'])); ?></small>
                                <div style="font-size:1.2rem; font-weight:bold;"><?php echo date('M d', strtotime($m['checkup_date'])); ?></div>
                            </div>
                            <div>
                                <div class="vitals-row">
                                    <span>BP <b><?php echo $m['blood_pressure']; ?></b></span>
                                    <span>Weight <b><?php echo $m['weight_kg']; ?> kg</b></span>
                                    <span>Fetal Heart <b><?php echo $m['fetal_heart_tone']; ?> bpm</b></span>
                                </div>
                                <p style="margin: 15px 0 0 0; color: #777; font-size: 0.95rem;">
                                    <strong>Notes:</strong> <?php echo $m['remarks']; ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:#bbb; text-align:center; padding:20px;">No records available yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="infant-view" class="view-content hidden">
            <h3 style="margin-bottom:20px; color:var(--sage-green);">Children Health Profiles</h3>
            <?php if(mysqli_num_rows($c_result) > 0): ?>
                <?php while($child = mysqli_fetch_assoc($c_result)): ?>
                    <div class="child-card">
                        <div>
                            <div style="font-size: 0.8rem; color: var(--sage-light); font-weight: bold; text-transform: uppercase;">Infant</div>
                            <h2 style="margin: 5px 0; color: var(--text-main);"><?php echo htmlspecialchars($child['baby_name']); ?></h2>
                            <span style="color: #999; font-size: 0.9rem;">Birth Date: <?php echo date('F d, Y', strtotime($child['birth_date'])); ?></span>
                        </div>
                        <a href="user_view_child.php?id=<?php echo $child['id']; ?>" class="btn-outline">View Growth Logs</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="history-card" style="text-align:center; color:#bbb;">
                    No child records linked to your account.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchView(evt, type) {
            // Hide sections
            document.querySelectorAll('.view-content').forEach(v => v.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

            // Show selected
            document.getElementById(type + '-view').classList.remove('hidden');
            evt.currentTarget.classList.add('active');
        }
    </script>

</body>
</html>