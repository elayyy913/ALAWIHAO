<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kunin ang data mula sa modal form
    $mother_id = mysqli_real_escape_string($conn, $_POST['mother_id']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);
    $bp = mysqli_real_escape_string($conn, $_POST['bp']);
    $temp = mysqli_real_escape_string($conn, $_POST['temp']);
    $checkup_date = date('Y-m-d'); // Kunin ang date ngayon

    // I-insert sa maternal_records table
$query = "INSERT INTO maternal_records (mother_id, weight_kg, bp, temperature, checkup_date) 
          VALUES ('$mother_id', '$weight', '$bp', '$temp', '$checkup_date')";

    if (mysqli_query($conn, $query)) {
        // Kapag success, balik sa main page
        header("Location: admin_maternal_hr.php?msg=success");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>