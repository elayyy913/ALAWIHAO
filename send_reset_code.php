<?php
// 1. I-set ang timezone sa Manila para mag-match sa oras natin
date_default_timezone_set('Asia/Manila');

session_start();
include 'db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (isset($_POST['reset_request'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $smtp_host = getenv('ALAWIHAO_SMTP_HOST') ?: 'smtp.gmail.com';
        $smtp_port = (int)(getenv('ALAWIHAO_SMTP_PORT') ?: 587);
        $smtp_username = getenv('ALAWIHAO_SMTP_USERNAME') ?: '';
        $smtp_password = getenv('ALAWIHAO_SMTP_PASSWORD') ?: '';
        $smtp_from_email = getenv('ALAWIHAO_SMTP_FROM_EMAIL') ?: $smtp_username;
        $smtp_from_name = getenv('ALAWIHAO_SMTP_FROM_NAME') ?: 'Alawihao db';
        if ($smtp_username === '' || $smtp_password === '' || !filter_var($smtp_from_email, FILTER_VALIDATE_EMAIL) || $smtp_port < 1 || $smtp_port > 65535) {
            error_log('Password reset email is unavailable because SMTP environment settings are incomplete.');
            http_response_code(503);
            exit('Email verification is not configured. Please contact the system administrator.');
        }

        $otp = random_int(100000, 999999);
        
        // Dito natin sinisiguro na ang expiry ay 15 mins base sa Manila time
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        $update = "UPDATE users SET reset_code = '$otp', reset_expiry = '$expiry' WHERE email = '$email'";
        mysqli_query($conn, $update);

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_username;
            $mail->Password   = $smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtp_port;

            $mail->setFrom($smtp_from_email, $smtp_from_name);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; border: 1px solid #e2e8f0; padding: 20px; border-radius: 10px;'>
                    <h2 style='color: #8DAE74;'>Reset Your Password</h2>
                    <p>Gamitin ang code sa ibaba para ma-verify ang iyong account:</p>
                    <h1 style='background: #f1f5f9; padding: 10px; text-align: center; letter-spacing: 5px; color: #2D3748;'>$otp</h1>
                    <p style='color: #718096; font-size: 12px;'>Ang code na ito ay valid lamang hanggang: <b>" . date('h:i A', strtotime($expiry)) . "</b></p>
                </div>";

            $mail->send();

            $_SESSION['reset_email'] = $email;
            header("Location: verify_otp.php");
            exit();

        } catch (Exception $e) {
            error_log('Password reset email failed: ' . $mail->ErrorInfo);
            http_response_code(502);
            exit('The verification email could not be sent. Please try again later.');
        }
    } else {
        echo "<script>alert('Email not found!'); window.location.href='forgot_password.php';</script>";
    }
}
?>