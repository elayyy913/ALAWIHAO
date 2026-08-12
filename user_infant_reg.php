<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php'; 

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $child_name = $_POST['child_name'] ?? '';
    $mother_name = $_POST['mother_name'] ?? ''; 
    $gender = $_POST['gender'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $birth_weight = $_POST['birth_weight'] ?? 0;
    $blood_type = $_POST['blood_type'] ?? ''; 
    $place_of_birth = $_POST['place_of_birth'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0; 
    $status = 'Pending';

    try {
        $stmt = $conn->prepare("INSERT INTO children (user_id, child_name, mother_name, gender, birth_date, weight, blood_type, place_of_birth, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssdsss", $user_id, $child_name, $mother_name, $gender, $birth_date, $birth_weight, $blood_type, $place_of_birth, $status);

        if ($stmt->execute()) {
            header("Location: user_dashboard.php?msg=RegistrationSubmitted");
            exit(); 
        }
    } catch (mysqli_sql_exception $e) {
        $message = "Database Error: " . $e->getMessage();
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
            --light-beige: #f1f4ea;
        }

        body { 
            background-color: var(--light-beige); 
            margin: 0; 
            font-family: 'Times New Roman', serif; 
        }
        
        #main { 
            margin-left: 280px; /* Space para sa sidebar */
            padding: 30px;
            transition: margin-left .5s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* FLEXIBLE CONTAINER: Ito ang magpapatakbo sa width */
        .content-container {
            width: 90%; /* flexible width */
            max-width: 1100px; /* para hindi naman sobrang haba sa ultra-wide monitors */
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .health-care-card {
            background: white; 
            padding: 20px 30px; 
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            border-bottom: 4px solid var(--sage-green); 
            color: var(--sage-green);
            font-weight: bold;
            font-size: 1.1rem;
            width: 100%;
            box-sizing: border-box;
        }

        .form-card {
            background: white; 
            padding: 50px; 
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            width: 100%;
            box-sizing: border-box;
            text-align: center;
        }

        .form-card h2 { 
            color: var(--sage-green); 
            margin-bottom: 5px; 
            font-size: 2.2rem; 
            font-weight: normal; 
        }

        .subtitle {
            color: #a0a0a0;
            font-style: italic;
            font-size: 0.9rem;
            margin-bottom: 40px;
            display: block;
        }

        .form-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 25px; 
            margin-bottom: 20px; 
            text-align: left;
        }

        .form-group { margin-bottom: 20px; }
        
        .form-group label { 
            display: block; 
            color: #b0b0b0; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            margin-bottom: 8px; 
            letter-spacing: 0.5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group select {
            width: 100%; 
            padding: 15px; 
            border: 1px solid #eee;
            border-radius: 10px; 
            background: #fafafa; 
            box-sizing: border-box;
            font-family: 'Times New Roman', serif; 
            font-size: 1rem; 
            color: #555;
        }

        .full-width { grid-column: span 2; }

        .enroll-btn {
            background-color: var(--sage-green); 
            color: white; 
            border: none;
            padding: 18px; 
            border-radius: 12px; 
            cursor: pointer;
            width: 100%; 
            font-size: 1.2rem; 
            font-weight: bold;
            transition: 0.3s; 
            margin-top: 20px;
        }

        .enroll-btn:hover { background-color: #5a6b43; }

        /* Responsive adjustments */
        @media (max-width: 1000px) {
            #main { margin-left: 0; padding: 20px; }
            .content-container { width: 100%; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

<?php include 'user_sidebar.php'; ?>

<div id="main">
    <div class="content-container">
        <div class="health-care-card">
            Alawihao Health Care
        </div>

        <div class="form-card">
            <h2>Enroll Baby</h2>
            <span class="subtitle">Information will be verified by a Health Worker.</span>
            
            <?php if($message) echo "<p style='color:red;'>$message</p>"; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name of Baby</label>
                        <input type="text" name="child_name" placeholder="e.g. Baby Boy/Girl Dela Cruz" required>
                    </div>
                    <div class="form-group">
                        <label>Mother's Full Name</label>
                        <input type="text" name="mother_name" placeholder="Enter Mother's Full Name" required>
                    </div>

                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Birth Date</label>
                        <input type="date" name="birth_date" required>
                    </div>

                    <div class="form-group">
                        <label>Birth Weight (kg)</label>
                        <input type="number" step="0.1" name="birth_weight" placeholder="e.g. 3.2" required>
                    </div>
                    <div class="form-group">
                        <label>Blood Type</label>
                        <select name="blood_type">
                            <option value="">Select (Optional)</option>
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

                    <div class="form-group full-width">
                        <label>Place of Birth</label>
                        <input type="text" name="place_of_birth" placeholder="e.g. Alawihao Health Center" required>
                    </div>
                </div>

                <button type="submit" class="enroll-btn">Register Baby</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>