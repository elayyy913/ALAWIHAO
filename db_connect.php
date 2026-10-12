<?php
$host = "127.0.0.1"; // Pinalitan mula 'localhost' para maiwasan ang IPv6 hanging/loading issue
$user = "root";
$pass = "";
$dbname = "capstone_db";

$conn = mysqli_connect($host, $user, $pass, $dbname, 3306);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Global Activity Logger Function
if (!function_exists('ensureActivityLogTable')) {
    function ensureActivityLogTable($conn) {
        $create_table = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(100),
            role VARCHAR(50),
            action_description TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        @mysqli_query($conn, $create_table);
    }
}

if (!function_exists('logActivity')) {
    function logActivity($conn, $userName, $role, $actionDesc, $userId = null) {
        if (!$conn) {
            return false;
        }

        ensureActivityLogTable($conn);

        $userName = $userName !== null ? trim((string) $userName) : 'System';
        $role = $role !== null ? trim((string) $role) : 'System';
        $actionDesc = $actionDesc !== null ? trim((string) $actionDesc) : '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        if ($userName === '') {
            $userName = 'System';
        }
        if ($role === '') {
            $role = 'System';
        }

        if (in_array($role, ['Admin', 'Super Admin'], true) && $userId !== null && $userId !== '') {
            $userName = $userName . ' (ID: ' . trim((string) $userId) . ')';
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_name, role, action_description, ip_address) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ssss", $userName, $role, $actionDesc, $ipAddress);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
}

if (!function_exists('logAdminAction')) {
    function logAdminAction($conn, $actionDesc) {
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['Admin', 'Super Admin'], true)) {
            return false;
        }

        $userName = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';
        $userId = $_SESSION['user_id'] ?? null;

        return logActivity($conn, $userName, $role, $actionDesc, $userId);
    }
}

if (!function_exists('logAction')) {
    function logAction($conn, $userName, $role, $actionDesc) {
        return logActivity($conn, $userName, $role, $actionDesc);
    }
}
?>