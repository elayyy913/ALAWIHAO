<?php
session_start();

$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($role, ['Admin', 'Super Admin'], true)) {
    header("Location: ../login.php");
    exit();
}

$settings_page = $role === 'Super Admin' ? 'super_admin_setting.php' : 'admin_settings.php';
$redirect_with_error = static function ($message) use ($settings_page) {
    $_SESSION['error_message'] = $message;
    header("Location: " . $settings_page);
    exit();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $settings_page);
    exit();
}

$session_csrf_token = $_SESSION['recovery_csrf_token'] ?? '';
$request_csrf_token = $_POST['csrf_token'] ?? '';
if ($session_csrf_token === '' || !is_string($request_csrf_token) || !hash_equals($session_csrf_token, $request_csrf_token)) {
    $redirect_with_error("Your session expired. Please try again.");
}

$new_recovery_email = trim($_POST['recovery_email'] ?? '');
$recovery_password = $_POST['recovery_password'] ?? '';
if ($new_recovery_email === '' || $recovery_password === '') {
    $redirect_with_error("Please fill in all required fields.");
}
if (!filter_var($new_recovery_email, FILTER_VALIDATE_EMAIL)) {
    $redirect_with_error("Please enter a valid email address.");
}

$last_sent_at = (int)($_SESSION['recovery_last_sent_at'] ?? 0);
if ($last_sent_at > 0 && time() - $last_sent_at < 60) {
    $redirect_with_error("Please wait one minute before requesting another verification code.");
}

include '../db_connect.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email, password FROM users WHERE id = ? AND role = ?");
if (!$stmt) {
    error_log("Account recovery query preparation failed: " . $conn->error);
    $redirect_with_error("Unable to verify your account right now. Please try again.");
}
$stmt->bind_param("is", $user_id, $role);
if (!$stmt->execute()) {
    error_log("Account recovery account lookup failed: " . $stmt->error);
    $stmt->close();
    $redirect_with_error("Unable to verify your account right now. Please try again.");
}
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$account) {
    $redirect_with_error("Your account could not be found. Please sign in again.");
}

if (strcasecmp($new_recovery_email, $account['email'] ?? '') === 0) {
    $redirect_with_error("The new email is the same as your current email.");
}

$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
if (!$check) {
    error_log("Account recovery email check preparation failed: " . $conn->error);
    $redirect_with_error("Unable to verify that email address right now. Please try again.");
}
$check->bind_param("si", $new_recovery_email, $user_id);
if (!$check->execute()) {
    error_log("Account recovery email check failed: " . $check->error);
    $check->close();
    $redirect_with_error("Unable to verify that email address right now. Please try again.");
}
$email_is_used = $check->get_result()->num_rows > 0;
$check->close();
if ($email_is_used) {
    $redirect_with_error("That email address is already being used by another account.");
}

$stored_password = (string)($account['password'] ?? '');
$password_matches = $stored_password !== ''
    && (password_verify($recovery_password, $stored_password) || hash_equals($stored_password, $recovery_password));
if (!$password_matches) {
    $redirect_with_error("Incorrect current password. Email was not changed.");
}

$smtp_host = getenv('ALAWIHAO_SMTP_HOST') ?: 'smtp.gmail.com';
$smtp_port = (int)(getenv('ALAWIHAO_SMTP_PORT') ?: 587);
$smtp_username = getenv('ALAWIHAO_SMTP_USERNAME') ?: '';
$smtp_password = getenv('ALAWIHAO_SMTP_PASSWORD') ?: '';
$smtp_from_email = getenv('ALAWIHAO_SMTP_FROM_EMAIL') ?: $smtp_username;
$smtp_from_name = getenv('ALAWIHAO_SMTP_FROM_NAME') ?: 'Alawihao Health Center';
if ($smtp_username === '' || $smtp_password === '' || !filter_var($smtp_from_email, FILTER_VALIDATE_EMAIL) || $smtp_port < 1 || $smtp_port > 65535) {
    error_log("Account recovery email is unavailable because SMTP environment settings are incomplete.");
    $redirect_with_error("Email verification is not configured yet. Please contact the system administrator.");
}

$otp_code = (string)random_int(100000, 999999);
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtp_port;
    $mail->Timeout = 15;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($smtp_from_email, $smtp_from_name);
    $mail->addAddress($new_recovery_email);
    $mail->isHTML(true);
    $mail->Subject = 'Your Account Recovery OTP Code';
    $mail->Body = "<h3>Alawihao Health Center Security</h3>
        <p>You requested to change your recovery email.</p>
        <p>Your One-Time Password (OTP) code is:</p>
        <h2 style='color: #2d5016;'>" . htmlspecialchars($otp_code, ENT_QUOTES, 'UTF-8') . "</h2>
        <p>This code will expire in 5 minutes.</p>";
    $mail->AltBody = "Your account recovery OTP code is {$otp_code}. It will expire in 5 minutes.";
    $mail->send();
} catch (Exception $e) {
    error_log("Account recovery OTP email failed: " . $mail->ErrorInfo);
    $redirect_with_error("The verification email could not be sent. Please check the address and try again later.");
}

$_SESSION['recovery_otp_hash'] = hash('sha256', $otp_code);
$_SESSION['recovery_pending_email'] = $new_recovery_email;
$_SESSION['recovery_user_id'] = $user_id;
$_SESSION['recovery_otp_expires_at'] = time() + 300;
$_SESSION['recovery_otp_attempts'] = 0;
$_SESSION['recovery_last_sent_at'] = time();
header("Location: verify_otp.php");
exit();
