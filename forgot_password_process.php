<?php
session_start();
include 'db_connect.php';

// I-include ang PHPMailer files (Siguraduhin ang tamang path)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (isset($_POST['reset_request'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    // 1. Check kung ang email ay nasa 'users' table
    $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        // 2. Generate 6-digit OTP at Expiry (15 mins)
        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // 3. I-update ang record sa DB gamit ang bagong columns
        $update = "UPDATE users SET reset_code = '$otp', reset_expiry = '$expiry' WHERE email = '$email'";
        mysqli_query($conn, $update);

        // 4. I-send ang Email gamit ang Gmail SMTP
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your-email@gmail.com'; // I-type ang Gmail mo
            $mail->Password   = 'your-app-password';   // I-type ang App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('your-email@gmail.com', 'Alawihao Health Center');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code';
            $mail->Body    = "Ang iyong password reset code ay: <b>$otp</b>. Valid ito sa loob ng 15 minuto.";

            $mail->send();

            // I-save sa session para sa next page
            $_SESSION['reset_email'] = $email;
            header("Location: verify_otp.php");
            exit();

        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "<script>alert('Email not found!'); window.location.href='forgot_password.php';</script>";
    }
}
?>