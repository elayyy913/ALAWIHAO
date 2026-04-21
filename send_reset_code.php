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
        $otp = rand(100000, 999999);
        
        // Dito natin sinisiguro na ang expiry ay 15 mins base sa Manila time
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        $update = "UPDATE users SET reset_code = '$otp', reset_expiry = '$expiry' WHERE email = '$email'";
        mysqli_query($conn, $update);

        $mail = new PHPMailer(true);

        try {
            // Localhost SSL Fix
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'alawihaohealth@gmail.com'; 
            $mail->Password   = 'hjabqddphcwdxrrx'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Inayos ko ang setFrom para tumugma sa Username mo
            $mail->setFrom('alawihaohealth@gmail.com', 'Alawihao Health Center');
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
            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "<script>alert('Email not found!'); window.location.href='forgot_password.php';</script>";
    }
}
?>