<?php
session_start();
include 'db_connect.php';

// Security: Check if logged in and is Super Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: login.php");
    exit();
}

/** * LOGIC PARA SA AUTO-GENERATED ID (Year + ID)
 * I-che-check natin ang pinaka-latest na ID sa health_workers table
 **/
$currentYear = date("Y");
$query = "SELECT generated_id FROM health_workers WHERE generated_id LIKE '$currentYear-%' ORDER BY generated_id DESC LIMIT 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $lastID = $row['generated_id']; // Halimbawa: "2026-0001"
    $parts = explode('-', $lastID);
    $nextNumber = intval($parts[1]) + 1;
} else {
    $nextNumber = 1; // Magsisimula sa 1 kung wala pang record sa year na ito
}

// I-format ang number para maging 4 digits (e.g., 0001)
$formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
$newGeneratedID = $currentYear . '-' . $formattedNumber;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Worker | Alawihao Center</title>
    <style>
        :root { --sage: #8DAE74; --dark-sage: #6B8E55; --beige: #F5F5DC; --text-gray: #718096; }
        body { font-family: 'Inter', sans-serif; background: #F9F9F4; display: flex; margin: 0; }
        .main-content { flex-grow: 1; padding: 40px; display: flex; justify-content: center; align-items: flex-start; }
        
        .form-card { 
            background: white; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 450px; 
        }

        h2 { color: var(--dark-sage); margin-top: 0; margin-bottom: 5px; }
        p.subtitle { color: var(--text-gray); font-size: 0.85rem; margin-bottom: 25px; }

        .id-container {
            background: #F1F5ED;
            border: 1px dashed var(--sage);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .id-label { display: block; font-size: 0.7rem; color: var(--dark-sage); font-weight: 700; text-transform: uppercase; }
        .id-value { font-family: 'Courier New', monospace; font-size: 1.2rem; font-weight: bold; color: var(--dark-sage); }

        .input-group { margin-bottom: 15px; }
        label { display: block; font-size: 0.8rem; font-weight: 600; color: #4A5568; margin-bottom: 5px; }
        
        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #E2E8F0; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 0.9rem;
            background: #FCFCFC;
        }
        input:focus { outline: none; border-color: var(--sage); background: white; }

        .btn-register { 
            background: var(--sage); 
            color: white; 
            border: none; 
            padding: 14px; 
            width: 100%; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            margin-top: 10px; 
            transition: 0.3s;
        }
        .btn-register:hover { background: var(--dark-sage); transform: translateY(-2px); }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #A0AEC0; text-decoration: none; font-size: 0.85rem; }
    </style>
</head>
<body>

<?php include 'super_admin_sidebar.php'; ?>

<div class="main-content">
    <div class="form-card">
        <h2>Register Worker</h2>
        <p class="subtitle">Create a new account for health center personnel.</p>
        <form action="register_worker_process.php" method="POST">
            <div class="id-container">
                <span class="id-label">System Generated ID</span>
                <span class="id-value"><?php echo $newGeneratedID; ?></span>
                <input type="hidden" name="generated_id" value="<?php echo $newGeneratedID; ?>">
            </div>

            <div style="display: flex; gap: 10px;">
                <div class="input-group" style="flex: 1;">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="Juan" required>
                </div>
                <div class="input-group" style="flex: 1;">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Dela Cruz" required>
                </div>
            </div>

            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="" required>
            </div>

            <div class="input-group">
                <label>Set Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="input-group">
                <label>Home Address</label>
                <input type="text" name="address" placeholder="" required>
            </div>

            <div class="input-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" placeholder="" required>
            </div>

            <button type="submit" class="btn-register">Complete Registration</button>
        </form>
        
        <a href="admin_health_workers.php" class="back-link">← Cancel and Go Back</a>
    </div>
</div>

</body>
</html>