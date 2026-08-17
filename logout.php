<?php
session_start();
// Gumamit ng tamang path papunta sa db_connect.php depende kung nasaan ang logout.php mo 
include 'db_connect.php'; 

// Sa logout.php
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $uid = $_SESSION['user_id'];
    $role = trim(strtolower($_SESSION['role'])); // Gawing lowercase para ligtas sa typo
    
    // I-update ang tamang table depende sa role
    if ($role === 'worker') {
        mysqli_query($conn, "UPDATE health_workers SET last_activity = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE worker_id = '$uid'");
    } else {
        mysqli_query($conn, "UPDATE users SET last_activity = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE id = '$uid'");
    }
}
// Clear all session variables
session_unset();

// Destroy the session on the server
session_destroy();

// Clear the session cookie in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Force browser to NOT cache the logout transition
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

// Redirect to login
header("Location: login.php");
exit();
?>