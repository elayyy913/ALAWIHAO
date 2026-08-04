<?php
session_start();
include 'db_connect.php';

if (isset($_POST['update_submit']) && $_SESSION['role'] === 'Super Admin') {
    $uid = $_POST['uid'];
    $role_type = $_POST['role_type'];
    
    // Only Email, Address, and Contact are accepted now
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $addr = mysqli_real_escape_string($conn, $_POST['address']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);

    if ($role_type === 'Admin') {
        $sql = "UPDATE users SET 
                email='$email', 
                address='$addr', 
                contact_number='$contact' 
                WHERE id='$uid'";
    } else {
        $sql = "UPDATE health_workers SET 
                email='$email', 
                address='$addr', 
                contact_number='$contact' 
                WHERE worker_id='$uid'";
    }

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Account contact details updated!'); window.location.href='admin_health_workers.php';</script>";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
} else {
    header("Location: admin_health_workers.php");
    exit();
}
?>