<?php
session_start();
include 'db_connect.php';

// Check kung may naka-login na user
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $current_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    // Update base sa role 
    if ($role === 'Worker') {
        $query = "UPDATE health_workers SET last_activity = NOW() WHERE worker_id = '$current_id'";
    } else {
        $query = "UPDATE users SET last_activity = NOW() WHERE id = '$current_id'";
    }

    if (mysqli_query($conn, $query)) {
        echo "Activity Updated";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "No session active";
}
?>