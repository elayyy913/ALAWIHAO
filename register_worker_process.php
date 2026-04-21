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

    // 1. I-INSERT MUNA SA HEALTH_WORKERS TABLE (Full Profile)
    $sql_worker = "INSERT INTO health_workers (generated_id, first_name, last_name, email, password, address, contact_number, status, created_at) 
                   VALUES ('$generated_id', '$first_name', '$last_name', '$email', '$password', '$address', '$contact', '$status', NOW())";

    if (mysqli_query($conn, $sql_worker)) {
        
        // 2. I-INSERT DIN SA USERS TABLE (Para sa Inventory mo)
        // Dito natin sineset ang role as 'Admin' or 'Worker'
        $sql_user = "INSERT INTO users (generated_id, first_name, last_name, email, password, role, status) 
                     VALUES ('$generated_id', '$first_name', '$last_name', '$email', '$password', 'Admin', 'Approved')";
        
        if (mysqli_query($conn, $sql_user)) {
            // Success sa dalawang table!
            echo "<script>
                    alert('Worker Registered Successfully and added to Users Inventory! ID: $generated_id');
                    window.location.href='admin_health_workers.php';
                  </script>";
        } else {
            // Kung nag-fail sa users table pero pumasok sa workers
            echo "Error adding to Users Inventory: " . mysqli_error($conn);
        }

    } else {
        // Kung nag-fail sa health_workers table
        echo "Error: " . $sql_worker . "<br>" . mysqli_error($conn);
    }
} else {
    header("Location: register_worker.php");
}
?>