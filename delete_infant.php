<?php
session_start();
// Baguhin ang path depende kung nasaan ang db_connect.php mula sa root
include 'db_connect.php'; // o 'admin/db_connect.php' kung sa loob ng admin folder

if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized";
    exit();
}

// Siguraduhing active ang connection bago mag-escape o mag-query
if (!$conn) {
    echo "Database connection failed.";
    exit();
}

if (isset($_POST['child_id'])) {
    $child_id = mysqli_real_escape_string($conn, $_POST['child_id']);

    mysqli_query($conn, "DELETE FROM children WHERE child_id = '$child_id'");
    $query = "DELETE FROM children WHERE id = '$child_id'";
    
    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
} else {
    echo "Invalid Request";
}
?>