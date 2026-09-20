<?php
session_start();
include '../db_connect.php'; // Siguraduhing tama ang path patungo sa db_connect.php mo

// Manu-manong i-load ang PHPMailer gamit ang nakitang folder structure
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// I-check kung naka-login ang admin
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = (int)($_SESSION['admin_id'] ?? $_SESSION['user_id']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_recovery_email = trim($_POST['recovery_email'] ?? '');
    $recovery_password = $_POST['recovery_password'] ?? '';

    // 1. Validations
    if ($new_recovery_email === '' || $recovery_password === '') {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        header("Location: admin_settings.php");
        exit();
    }

    if (!filter_var($new_recovery_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
        header("Location: admin_settings.php");
        exit();
    }

    // Kunin ang kasalukuyang detalye ng admin mula sa database
    $stmt = $conn->prepare("SELECT email, password FROM users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if (strcasecmp($new_recovery_email, $admin['email'] ?? '') === 0) {
        $_SESSION['error_message'] = "The new email is the same as your current email.";
        header("Location: admin_settings.php");
        exit();
    }

    // 2. I-check kung ginagamit na ng iba ang email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->bind_param("si", $new_recovery_email, $admin_id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['error_message'] = "That email address is already being used by another account.";
        $check->close();
        header("Location: admin_settings.php");
        exit();
    }
    $check->close();

    // 3. I-verify ang Current Password
    $db_pass = $admin['password'] ?? '';
    $is_match = false;

    if (!empty($db_pass) && password_verify($recovery_password, $db_pass)) {
        $is_match = true;
    } elseif (!empty($db_pass) && hash_equals($db_pass, $recovery_password)) {
        $is_match = true;
    }

    if (!$is_match) {
        $_SESSION['error_message'] = "Incorrect current password. Email was not changed.";
        header("Location: admin_settings.php");
        exit();
    }

    // 4. Gumawa ng 6-digit OTP at i-save sa Session
    $otp_code = rand(100000, 999999);
    $_SESSION['otp_code'] = $otp_code;
    $_SESSION['pending_new_email'] = $new_recovery_email;
    $_SESSION['otp_expiry'] = time() + 300; // 5 minutes expiration

    // 5. Ipadala ang OTP gamit ang PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Ilagay ang SMTP host ninyo
        $mail->SMTPAuth   = true;
        $mail->Username   = 'alawihaohealth@gmail.com'; // Ilagay ang email ng health center/system
        $mail->Password   = 'shlycwzyckzszsen'; // Ang iyong bagong Google App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('alawihaohealth@gmail.com', 'Alawihao Health Center');
        $mail->addAddress($new_recovery_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Account Recovery OTP Code';
        $mail->Body    = "<h3>Alawihao Health Center Security</h3>
                          <p>You requested to change your recovery email.</p>
                          <p>Your One-Time Password (OTP) code is:</p>
                          <h2 style='color: #2d5016;'>$otp_code</h2>
                          <p>This code will expire in 5 minutes.</p>";

        $mail->send();

        // Kapag matagumpay na naipadala, dalhin sa verify page
        header("Location: verify_otp.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        header("Location: admin_settings.php");
        exit();
    }
} else {
    header("Location: admin_settings.php");
    exit();
}