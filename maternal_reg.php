<?php
session_start();
include 'db_connect.php';

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$message = "";
if (isset($_POST['register_maternal'])) {
    $mother = mysqli_real_escape_string($conn, $_POST['mother_name']);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $lmp = $_POST['lmp_date'];
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $worker_id = $_SESSION['user_id'];

    $sql = "INSERT INTO maternal_registrations (mother_name, age, lmp_date, contact_number, health_worker_id, status) 
            VALUES ('$mother', '$age', '$lmp', '$contact', '$worker_id', 'Pending')";

    if (mysqli_query($conn, $sql)) {
        $message = "Maternal registration submitted for approval!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pregnancy Registration</title>
    <link rel="stylesheet" href="style.css"> <style>
        .form-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 600px; margin: 20px auto; }
        input, select { width: 100%; padding: 12px; margin: 10px 0 20px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-submit { background-color: #718355; color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: bold; }
    </style>
</head>
<body style="background-color: #F5F5DC; display: flex;">

    <?php include 'sidebar.php'; ?> <div style="flex: 1; padding: 40px;">
        <h2>Pregnant Patient Enrollment</h2>
        
        <?php if($message != "") echo "<p style='color: green;'>$message</p>"; ?>

        <div class="form-container">
            <form method="POST">
                <label>Mother's Full Name</label>
                <input type="text" name="mother_name" required>

                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1;">
                        <label>Age</label>
                        <input type="number" name="age" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Contact Number</label>
                        <input type="text" name="contact" placeholder="09xxxxxxxxx">
                    </div>
                </div>

                <label>Last Menstrual Period (LMP)</label>
                <input type="date" name="lmp_date" required>

                <button type="submit" name="register_maternal" class="btn-submit">Register Patient</button>
            </form>
        </div>
    </div>
</body>
</html>