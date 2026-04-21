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
    // 1. Capture data from form with basic security
    $name = mysqli_real_escape_string($conn, $_POST['patient_name']);
    $age = $_POST['age'];
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $blood_type = $_POST['blood_type'];
    $allergies = mysqli_real_escape_string($conn, $_POST['allergies']);
    $lmp = $_POST['lmp'];
    $edc = $_POST['edd']; 
    
    // BINAGO: Ginawang "Pending" para lumabas sa Super Admin dashboard
    $status = "Pending"; 

    // 2. SQL Prepare
    $sql = "INSERT INTO maternal_registrations (full_name, age, address, contact_number, height, weight, blood_type, allergies, lmp, edc, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // 3. Bind parameters
    $stmt->bind_param("sisssddssss", $name, $age, $address, $contact, $height, $weight, $blood_type, $allergies, $lmp, $edc, $status);

    if ($stmt->execute()) {
        // BINAGO: Update message para alam ng user na for approval pa
        $message = "Maternal record registered successfully! Pending for admin review.";
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maternal Registration | Alawihao Health</title>
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

        /* Sidebar dynamic margin fix */
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
        
        .form-group input, .form-group textarea, .form-group select {
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

        .form-group input:focus, .form-group textarea:focus { 
            border-color: var(--sage-green); 
        }

        .reg-btn {
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
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .reg-btn:hover { background-color: #5a6b43; }
        
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
            .main-content-active { margin-left: 0 !important; width: 100%; }
        }
    </style>
</head>
<body>

<?php 
    // DYNAMIC SIDEBAR LOADING
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
        <strong>Alawihao Health Center | Maternal Registration</strong>
    </div>

    <div class="form-card">
        <h2>Maternal Registration Form</h2>
        
        <?php if($message) echo "<div class='success-msg'>$message</div>"; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name of Mother</label>
                    <input type="text" name="patient_name" placeholder="e.g. Maria Clara" required>
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>Home Address</label>
                <textarea name="address" rows="2" placeholder="Street, Barangay, City" required></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Height (cm)</label>
                    <input type="number" step="0.01" name="height" placeholder="e.g. 160" required>
                </div>
                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" step="0.01" name="weight" placeholder="e.g. 60" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Blood Type</label>
                    <select name="blood_type" required>
                        <option value="">-- Select --</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Known Allergies</label>
                    <input type="text" name="allergies" placeholder="e.g. None or specific medications">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" placeholder="09xxxxxxxxx" required>
                </div>
                <div class="form-group">
                    <label>Last Menstrual Period (LMP)</label>
                    <input type="date" name="lmp" id="lmp" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label>Expected Date of Delivery (EDD)</label>
                <input type="date" name="edd" id="edd" required>
            </div>

            <button type="submit" class="reg-btn">Confirm Registration</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Detect sidebar visibility to adjust layout
        const sidebar = document.getElementById('mainSidebar') || document.querySelector('.sidebar');
        const mainContent = document.getElementById('main');
        
        // Auto-apply margin for desktop view if sidebar is present
        if (sidebar && window.innerWidth > 768) {
            mainContent.classList.add('main-content-active');
        }
    });
</script>

</body>
</html>