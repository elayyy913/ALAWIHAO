<?php
session_start();
include 'db_connect.php';

if (isset($_POST['child_id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['child_id']);

    // 1. Kunin muna ang pangalan ng bata bago i-delete sa 'children' table
    $getName = mysqli_query($conn, "SELECT child_name FROM children WHERE id = '$id'");
    $nameData = mysqli_fetch_assoc($getName);
    $babyName = isset($nameData['child_name']) ? mysqli_real_escape_string($conn, $nameData['child_name']) : '';

    // 2. Burahin sa 'children' table 
    mysqli_query($conn, "DELETE FROM children WHERE id = '$id'");

    $sql_infant = "DELETE FROM infant_records WHERE id = '$id' OR child_id = '$id'";
    
    if ($babyName != '') {
        $sql_infant .= " OR baby_name = '$babyName'";
    }
    
    if (mysqli_query($conn, $sql_infant)) {
        mysqli_query($conn, "DELETE FROM health_logs WHERE child_id = '$id'");
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "No ID provided";
}
?>