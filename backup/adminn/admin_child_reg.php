<?php
session_start();
include 'db_connect.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id']; 
    $baby_name = mysqli_real_escape_string($conn, $_POST['baby_name']);
    $gender = $_POST['gender'];
    $blood_type = $_POST['blood_type']; 
    $dob = $_POST['birth_date'];
    $weight = $_POST['birth_weight'];
    $pob = mysqli_real_escape_string($conn, $_POST['place_of_birth']);
    $mother = mysqli_real_escape_string($conn, $_POST['mother_name']);
    
    // BINAGO: Kahit Admin ang nag-register, dapat "Pending" muna para lumabas sa approval dashboard
    $status = "Pending";
    $vaccines = isset($_POST['vaccines']) ? implode(", ", $_POST['vaccines']) : "None";

    $sql = "INSERT INTO children (user_id, child_name, gender, blood_type, birth_date, weight_kg, place_of_birth, mother_name, status, vaccine_taken) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("issssdssss", $user_id, $baby_name, $gender, $blood_type, $dob, $weight, $pob, $mother, $status, $vaccines);
        if ($stmt->execute()) {
            $message = "Baby enrolled successfully! " . ($status === "Pending" ? "Pending for admin review." : "");
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Database error: Could not prepare statement.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Baby | Alawihao Health</title>
    <style>
        :root {
            --sage-green: #718355;
            --light-beige: #f5f5dc;
            --border-color: #eaddca;
            --sidebar-width: 280px;
        }

        body { 
            background-color: var(--light-beige); 
            margin: 0; 
            font-family: 'Times New Roman', serif; 
            overflow-x: hidden;
        }
        
        #main { 
            margin-left: 0; 
            padding: 40px;
            transition: all 0.3s ease-in-out;
            min-height: 100vh;
            box-sizing: border-box;
        }

        /* FIX: Push content when sidebar is active to avoid overlap (Ref: image_d1cee9.png) */
        .main-content-active {
            margin-left: var(--sidebar-width) !important;
            width: calc(100% - var(--sidebar-width));
        }

        .health-care-card {
            background: white; 
            padding: 20px 30px; 
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            margin-bottom: 25px;
            border-bottom: 4px solid var(--sage-green); 
            color: var(--sage-green);
            letter-spacing: 2px; 
            text-transform: uppercase; 
            font-size: 0.9rem;
        }

        .form-card {
            background: white; 
            padding: 40px 50px; 
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            max-width: 900px;
            margin: 0 auto;
        }

        .form-card h2 { 
            color: var(--sage-green); 
            margin-bottom: 30px; 
            font-size: 2rem; 
            font-weight: normal;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            text-align: left;
        }

        .form-grid {
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group label { 
            display: block; 
            color: #888; 
            font-size: 0.8rem; 
            text-transform: uppercase; 
            margin-bottom: 8px; 
            letter-spacing: 0.5px;
        }
        
        .form-group input, .form-group select {
            width: 100%; 
            padding: 14px; 
            border: 1px solid var(--border-color);
            border-radius: 8px; 
            background: #fafaf9; 
            box-sizing: border-box;
            font-family: inherit;
            color: #555;
            outline: none;
            transition: border 0.3s;
        }

        .form-group input:focus { border-color: var(--sage-green); }

        /* FIX: Better vaccine checklist alignment */
        .vaccine-checklist {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 20px;
            background: #fafaf9;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-top: 8px;
        }

        .vax-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: #666;
            cursor: pointer;
        }

        .vax-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--sage-green);
        }

        .enroll-btn {
            background-color: var(--sage-green); 
            color: white; 
            border: none;
            padding: 18px; 
            border-radius: 12px; 
            cursor: pointer;
            width: 100%; 
            font-size: 1.1rem; 
            font-weight: bold;
            transition: background 0.3s;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .enroll-btn:hover { background-color: #5a6b43; }
        
        .success-msg { 
            color: #2d5a27; 
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
            border-left: 5px solid var(--sage-green);
        }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .vaccine-checklist { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<?php 
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'Super Admin') {
            include 'super_admin_sidebar.php'; 
        } elseif ($_SESSION['role'] === 'Admin') {
            include 'admin_sidebar.php'; 
        } else {
            include 'user_sidebar.php';
        }
    }
?>

<div id="main">
    <div class="health-care-card">
        <strong>Alawihao Health Center | Patient Enrollment</strong>
    </div>

    <div class="form-card">
        <h2>Infant Registration Form</h2>
        
        <?php if($message) echo "<div class='success-msg'>$message</div>"; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name of Baby</label>
                    <input type="text" name="baby_name" placeholder="Enter Full Name" required>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">-- Select --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Birth Date</label>
                    <input type="date" name="birth_date" required>
                </div>
                <div class="form-group">
                    <label>Birth Weight (kg)</label>
                    <input type="number" step="0.1" name="birth_weight" placeholder="e.g. 3.2" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label>Vaccines Taken</label>
                <div class="vaccine-checklist">
                    <label class="vax-item"><input type="checkbox" name="vaccines[]" value="BCG"> BCG</label>
                    <label class="vax-item"><input type="checkbox" name="vaccines[]" value="Hepa B"> Hepa B</label>
                    <label class="vax-item"><input type="checkbox" name="vaccines[]" value="Pentavalent"> Pentavalent</label>
                    <label class="vax-item"><input type="checkbox" name="vaccines[]" value="OPV"> OPV</label>
                    <label class="vax-item"><input type="checkbox" name="vaccines[]" value="IPV"> IPV</label>
                    <label class="vax-item"><input type="checkbox" name="vaccines[]" value="PCV"> PCV</label>
                    <label class="vax-item"><input type="checkbox" name="vaccines[]" value="MMR"> MMR</label>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Blood Type</label>
                    <select name="blood_type" required>
                        <option value="N/A">Unknown / N/A</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Place of Birth</label>
                    <input type="text" name="place_of_birth" placeholder="Hospital or Clinic Name" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label>Mother's Full Name</label>
                <input type="text" name="mother_name" placeholder="Full Name of Mother" required>
            </div>

            <button type="submit" class="enroll-btn">Confirm Registration</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('mainSidebar') || document.querySelector('.sidebar') || document.querySelector('.sidebar-container');
        const mainContent = document.getElementById('main');
        
        if (sidebar) {
            mainContent.classList.add('main-content-active');
        }
    });
</script>

</body>
</html>