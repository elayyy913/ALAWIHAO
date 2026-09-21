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
    // NOTE: health_workers.status ay hiwalay sa users.status (baba). Nilagyan pa rin
    // itong 'approved' dahil hindi ko alam kung may ibang page na umaasa dito. Kung
    // pareho dapat ang pag-uugali (Pending muna hangga't hindi na-vverify), sabihin mo
    // lang para maisama ko rin ito.
    $status = 'approved';

    // 1. I-INSERT SA HEALTH_WORKERS TABLE (Nilagyan ng last_activity = NULL)
    $sql_worker = "INSERT INTO health_workers (generated_id, first_name, last_name, email, password, address, contact_number, status, created_at, last_activity) 
                   VALUES ('$generated_id', '$first_name', '$last_name', '$email', '$password', '$address', '$contact', '$status', NOW(), NULL)";

    if (mysqli_query($conn, $sql_worker)) {
        
        // 2. I-INSERT DIN SA USERS TABLE (Nilagyan din ng last_activity = NULL)
        // NOTE: 'Pending' (hindi 'Approved') dapat dito - ito yung ginagamit ng
        // super_admin_dashboard.php sa query nito (WHERE role='Admin' AND status='Pending')
        // para lumabas ang bagong worker sa "Pending Staff Worker Accounts" pad.
        // Kapag 'Approved' agad, ma-sskip yung buong verification step at diretso na
        // itong lalabas sa Personnel Directory (admin_health_workers.php) bilang approved.
        $sql_user = "INSERT INTO users (generated_id, first_name, last_name, email, password, role, status, last_activity) 
                     VALUES ('$generated_id', '$first_name', '$last_name', '$email', '$password', 'Admin', 'Pending', NULL)";
        
        if (mysqli_query($conn, $sql_user)) {
            // Success sa dalawang table!
            echo "SUCCESS:$generated_id";
        } else {
            echo "ERROR: Failed to add to Users table - " . mysqli_error($conn);
        }

    } else {
        echo "ERROR: Failed to add to Health Workers table - " . mysqli_error($conn);
    }
} else {
    header("Location: register_worker.php");
}
?>