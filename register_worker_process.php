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
    
    // Status is 'approved' by default para makapag-login agad
    $status = 'approved';

    // SQL Query para mag-insert sa health_workers table
    // Tandaan: 'worker_username' ang gagamitin natin for the generated_id base sa logic natin
    $sql = "INSERT INTO health_workers (generated_id, first_name, last_name, email, password, address, contact_number, status, created_at) 
            VALUES ('$generated_id', '$first_name', '$last_name', '$email', '$password', '$address', '$contact', '$status', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
                alert('Worker Registered Successfully! ID: $generated_id');
                window.location.href='admin_health_workers.php';
              </script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
} else {
    header("Location: register_worker.php");
}
?>