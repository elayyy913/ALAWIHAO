<?php
session_start();
include 'db_connect.php'; // 1. Kailangan ang connection para ma-update ang status

// 2. REAL-TIME OFFLINE LOGIC
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    
    // I-set ang activity sa 1 hour ago. 
    // Dahil ang dashboard ay naghahanap ng activity within the last 20 seconds,
    // magmumukha ka agad na 'Offline' pagka-click ng logout.
    mysqli_query($conn, "UPDATE users SET last_activity = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE id = '$uid'");
}

// 3. Clear all session variables
session_unset();

// 4. Destroy the session on the server
session_destroy();

// 5. Clear the session cookie in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 6. Force browser to NOT cache the logout transition
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

// 7. Redirect to login
header("Location: login.php");
exit();
?>