<?php
session_start();
include 'db_connect.php';

if (isset($_POST['mother_id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['mother_id']);

    // STEP 1: Burahin muna ang medical history/records
    mysqli_query($conn, "DELETE FROM maternal_records WHERE mother_id = '$id'");

    // STEP 2: Burahin ang mismong registration
    $sql = "DELETE FROM maternal_registrations WHERE id = '$id'";
    
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>