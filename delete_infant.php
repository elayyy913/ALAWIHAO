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
    $child_id = filter_var($_POST['child_id'], FILTER_VALIDATE_INT);
    if ($child_id === false || $child_id < 1) {
        echo "Invalid child ID";
        exit();
    }

    $child_result = mysqli_query($conn, "SELECT child_name FROM children WHERE id = $child_id LIMIT 1");
    $child = $child_result ? mysqli_fetch_assoc($child_result) : null;
    $deleted = mysqli_query($conn, "DELETE FROM children WHERE id = $child_id");

    if ($deleted && mysqli_affected_rows($conn) > 0) {
        $childName = $child['child_name'] ?? '';
        $subject = $childName !== '' ? " ({$childName})" : '';
        logActivity(
            $conn,
            $_SESSION['name'] ?? 'User',
            $_SESSION['role'] ?? 'User',
            "Deleted child patient record ID: {$child_id}{$subject}.",
            $_SESSION['user_id'] ?? null
        );
        echo "success";
    } else {
        echo $deleted ? "Child record not found" : "Database Error: " . mysqli_error($conn);
    }
} else {
    echo "Invalid Request";
}
?>