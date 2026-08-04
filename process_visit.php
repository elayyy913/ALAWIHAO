<?php
include 'db_connect.php';
session_start();

if (isset($_POST['submit_visit'])) {
    $child_id = mysqli_real_escape_string($conn, $_POST['child_id']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);
    $height = mysqli_real_escape_string($conn, $_POST['height']);
    $vaccine = mysqli_real_escape_string($conn, $_POST['vaccine']) ?: 'None';
    $v_status = mysqli_real_escape_string($conn, $_POST['v_status']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    $today = date('Y-m-d');

    // 1. INSERT to health_logs (This goes to the User's HISTORY)
    $history_query = "INSERT INTO health_logs (child_id, weight_kg, height_cm, vaccine_name, remarks, checkup_date) 
                      VALUES ('$child_id', '$weight', '$height', '$vaccine', '$remarks', '$today')";
    
    // 2. UPDATE children table (This updates the ADMIN'S main list)
    $update_query = "UPDATE children SET 
                     weight_kg = '$weight', 
                     height_cm = '$height', 
                     vaccination_status = '$v_status' 
                     WHERE id = '$child_id'";

    if (mysqli_query($conn, $history_query) && mysqli_query($conn, $update_query)) {
        // Redirect back with a success message
        header("Location: admin_infant_records.php?status=updated");
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>