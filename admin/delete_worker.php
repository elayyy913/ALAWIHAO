<?php
session_start();
include '../db_connect.php';

// Security: Check if logged in and is Super Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    die("Unauthorized access.");
}

// Ensure we have the ID, Type, and the confirmation password (POST na, hindi GET,
// para hindi lumabas ang password sa URL/browser history/server logs)
if (isset($_POST['id']) && isset($_POST['type']) && isset($_POST['confirm_password'])) {
    $id = intval($_POST['id']);       // Securely convert ID to integer
    $type = $_POST['type'];           // 'Admin' or 'Worker'
    $confirm_password = $_POST['confirm_password'];

    // --- VERIFY THE CURRENTLY LOGGED-IN SUPER ADMIN'S PASSWORD FIRST ---
    if (!isset($_SESSION['user_id'])) {
        header("Location: admin_health_workers.php?error=" . urlencode("Session expired. Please log in again."));
        exit();
    }

    $current_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $current_id);
    $stmt->execute();
    $stmt->bind_result($stored_password);
    $stmt->fetch();
    $stmt->close();

    // Plain-text comparison (walang hashing ang password storage sa ngayon)
    if ($stored_password === null || $confirm_password !== $stored_password) {
        header("Location: admin_health_workers.php?error=" . urlencode("Incorrect password. Deletion cancelled."));
        exit();
    }

    // --- PASSWORD CONFIRMED: PROCEED WITH DELETE ---
    // Use Prepared Statements to prevent SQL Injection
    if ($type === 'Worker') {
        $del_stmt = $conn->prepare("DELETE FROM health_workers WHERE worker_id = ?");
    } else {
        $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    }

    if ($del_stmt) {
        $del_stmt->bind_param("i", $id);
        $del_stmt->execute();
        $del_stmt->close();
    }

    header("Location: admin_health_workers.php?msg=" . urlencode("Personnel deleted successfully."));
    exit();
} else {
    header("Location: admin_health_workers.php?error=" . urlencode("Missing required data."));
    exit();
}
?>