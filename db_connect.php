<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "finalcaps_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Global Activity Logger Function (Nilagyan ng function_exists para iwas duplicate error)
if (!function_exists('logAction')) {
    function logAction($conn, $userName, $role, $actionDesc) {
        $userName = mysqli_real_escape_string($conn, $userName);
        $role = mysqli_real_escape_string($conn, $role);
        $actionDesc = mysqli_real_escape_string($conn, $actionDesc);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        // Awtomatikong gagawa ng activity_logs table kung wala pa
        $create_table = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(100),
            role VARCHAR(50),
            action_description TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        @mysqli_query($conn, $create_table);

        // I-save ang log sa database
        $sql = "INSERT INTO activity_logs (user_name, role, action_description, ip_address) 
                VALUES ('$userName', '$role', '$actionDesc', '$ipAddress')";
        @mysqli_query($conn, $sql);
    }
}
?>