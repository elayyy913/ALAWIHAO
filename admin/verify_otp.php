<?php
session_start();
include '../db_connect.php';

// I-check kung naka-login ang admin
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = (int)($_SESSION['admin_id'] ?? $_SESSION['user_id']);
$message = "";

// I-check kung may pending email at OTP sa session
if (!isset($_SESSION['otp_code']) || !isset($_SESSION['pending_new_email'])) {
    header("Location: admin_settings.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['verify_otp'])) {
    $user_otp = trim($_POST['otp_code'] ?? '');
    
    // Suriin kung nag-expire na ang OTP (5 minuto)
    if (time() > ($_SESSION['otp_expiry'] ?? 0)) {
        $message = "<div class='alert error'>The OTP code has expired. Please request a new one.</div>";
    } elseif ($user_otp == $_SESSION['otp_code']) {
        // Tamang OTP! I-update na ang email sa database
        $new_email = $_SESSION['pending_new_email'];

        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $new_email, $admin_id);

        if ($stmt->execute()) {
            // Linisin ang OTP sessions
            unset($_SESSION['otp_code']);
            unset($_SESSION['pending_new_email']);
            unset($_SESSION['otp_expiry']);

            // I-redirect pabalik sa settings na may success message (puwede mo ring gamitin ang session flash message)
            $_SESSION['success_message'] = "<div class='alert success'>Recovery email updated successfully! Your new email is now your login and recovery email.</div>";
            header("Location: admin_settings.php");
            exit();
        } else {
            $message = "<div class='alert error'>Database update failed: " . htmlspecialchars($conn->error) . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert error'>Invalid OTP code. Please try again.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="fil">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP | Alawihao Health Center</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --green: #2d5016;
    --accent: #5a7c3a;
    --light: #8fbf5a;
    --bg: #f8fffb;
    --white: #ffffff;
    --text: #333333;
    --muted: #666666;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--text);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.verify-container {
    background: var(--white);
    width: 100%;
    max-width: 450px;
    padding: 35px 40px;
    border-radius: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #eef2ee;
    text-align: center;
}
.verify-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--green);
    margin-bottom: 15px;
}
.verify-text {
    font-size: 0.9rem;
    color: var(--muted);
    margin-bottom: 25px;
    line-height: 1.5;
}
.form-group {
    margin-bottom: 20px;
    text-align: left;
}
label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: var(--green);
    font-size: 0.95rem;
}
.settings-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1.1rem;
    text-align: center;
    letter-spacing: 2px;
}
.settings-input:focus {
    outline: none;
    border-color: var(--green);
}
.btn-primary-action {
    background: var(--green);
    color: white;
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    border: none;
    font-size: 1rem;
    transition: 0.2s;
}
.btn-primary-action:hover {
    background: var(--accent);
}
.alert {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
    font-size: 0.9rem;
}
.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.back-link {
    display: block;
    margin-top: 20px;
    color: var(--muted);
    text-decoration: none;
    font-size: 0.85rem;
}
.back-link:hover { color: var(--green); }
</style>
</head>
<body>

<div class="verify-container">
    <div class="verify-title">
        <i class="fa fa-shield-halved"></i> Enter Verification Code
    </div>
    <p class="verify-text">
        We've sent a 6-digit code to <strong><?php echo htmlspecialchars($_SESSION['pending_new_email']); ?></strong>. Please enter it below to confirm your new email.
    </p>

    <?php echo $message; ?>

    <form method="POST">
        <div class="form-group">
            <label for="otp_code">6-Digit OTP Code</label>
            <input type="text" id="otp_code" name="otp_code" class="settings-input" maxlength="6" placeholder="------" required autofocus>
        </div>

        <button type="submit" name="verify_otp" class="btn-primary-action">
            Verify & Update Email
        </button>
    </form>

    <a href="admin_settings.php" class="back-link">
        <i class="fa fa-arrow-left"></i> Cancel and go back to settings
    </a>
</div>

</body>
</html>