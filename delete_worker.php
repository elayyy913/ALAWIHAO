<?php
session_start();
include 'db_connect.php';

// Security: Check if logged in and is Super Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    die("Unauthorized access.");
}

// Ensure we have the ID and the Type (Admin or Worker)
if (isset($_GET['id']) && isset($_GET['type'])) {
    $id = intval($_GET['id']); // Securely convert ID to integer
    $type = $_GET['type'];     // 'Admin' or 'Worker'

    // Use Prepared Statements to prevent SQL Injection
    if ($type === 'Worker') {
        $stmt = $conn->prepare("DELETE FROM health_workers WHERE worker_id = ?");
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    }

    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_health_workers.php?status=deleted");
    exit();
} else {
    header("Location: admin_health_workers.php?error=missing_data");
    exit();
}
?>