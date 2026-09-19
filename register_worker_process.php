<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Kunin ang data mula sa form
    $generated_id = mysqli_real_escape_string($conn, $_POST['generated_id']);
    $first_name   = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name    = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $password     = mysqli_real_escape_string($conn, $_POST['password']);
    $address      = mysqli_real_escape_string($conn, $_POST['address']);
    $contact      = mysqli_real_escape_string($conn, $_POST['contact_number']);
    
    // Status is 'approved'
    $status = 'approved';

    // 1. I-INSERT SA HEALTH_WORKERS TABLE (Nilagyan ng last_activity = NULL)
    $sql_worker = "INSERT INTO health_workers (generated_id, first_name, last_name, email, password, address, contact_number, status, created_at, last_activity) 
                   VALUES ('$generated_id', '$first_name', '$last_name', '$email', '$password', '$address', '$contact', '$status', NOW(), NULL)";

    if (mysqli_query($conn, $sql_worker)) {
        
        // 2. I-INSERT DIN SA USERS TABLE (Nilagyan din ng last_activity = NULL)
        $sql_user = "INSERT INTO users (generated_id, first_name, last_name, email, password, role, status, last_activity) 
                     VALUES ('$generated_id', '$first_name', '$last_name', '$email', '$password', 'Admin', 'Approved', NULL)";
        
        if (mysqli_query($conn, $sql_user)) {
            // Success sa dalawang table! 
            echo "<script>
                    alert('Worker Registered Successfully and added to Users Inventory! ID: $generated_id');
                    window.location.href='admin/admin_health_workers.php'; 
                  </script>";
        } else {
            echo "Error adding to Users Inventory: " . mysqli_error($conn);
        }

    } else {
        echo "Error: " . $sql_worker . "<br>" . mysqli_error($conn);
    }
} else {
    header("Location: register_worker.php");
}
?>