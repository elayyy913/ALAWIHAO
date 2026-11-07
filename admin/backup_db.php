<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Super Admin') {
    header("Location: ../login.php");
    exit();
}

include '../db_connect.php';

const BACKUP_EMAIL = 'alawihaohealth@gmail.com';
const BACKUP_OTP_TTL = 300;
const BACKUP_OTP_MAX_ATTEMPTS = 5;
const BACKUP_OTP_RESEND_SECONDS = 60;

$_SESSION['backup_csrf_token'] = $_SESSION['backup_csrf_token'] ?? bin2hex(random_bytes(32));
$message = '';
$messageType = 'error';

function renderBackupVerificationPage($message, $messageType)
{
    $csrfToken = htmlspecialchars($_SESSION['backup_csrf_token'], ENT_QUOTES, 'UTF-8');
    $messageHtml = $message !== ''
        ? '<div class="notice ' . ($messageType === 'success' ? 'success' : 'error') . '">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>'
        : '';
    $verificationPending = isset($_SESSION['backup_otp_hash'])
        && (int)($_SESSION['backup_otp_user_id'] ?? 0) === (int)$_SESSION['user_id']
        && (int)($_SESSION['backup_otp_expires_at'] ?? 0) > time();

    echo '<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Alawihao db | Backup verification</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f4f7f2;color:#263323;margin:0;padding:32px}
        main{max-width:480px;margin:8vh auto;background:#fff;padding:30px;border-radius:12px;box-shadow:0 8px 28px #17330f18}
        h1{color:#2d5016;margin-top:0}p{line-height:1.55;color:#53614e}
        label{display:block;font-weight:600;margin:22px 0 8px}
        input{box-sizing:border-box;width:100%;padding:12px;border:1px solid #cbd5c5;border-radius:7px;font-size:1.1rem}
        button{margin-top:16px;background:#2d5016;color:#fff;border:0;border-radius:7px;padding:12px 18px;font-weight:700;cursor:pointer}
        .notice{padding:12px;border-radius:7px;margin:16px 0;line-height:1.45}
        .error{background:#fff0ef;color:#9b2520}.success{background:#edf7e9;color:#2d5016}
        .secondary{background:#e8eee4;color:#2d5016;margin-left:8px}
        a{color:#2d5016}
    </style>
</head>
<body>
<main>
    <h1>Alawihao db</h1>
    <h2>Verify database backup</h2>
    <p>A one-time code is required before a database backup can be downloaded. The code will be sent to ' . htmlspecialchars(BACKUP_EMAIL, ENT_QUOTES, 'UTF-8') . ' and expires in 5 minutes.</p>
    ' . $messageHtml;

    if ($verificationPending) {
        echo '<form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="' . $csrfToken . '">
        <input type="hidden" name="action" value="verify_code">
        <label for="otp_code">Email verification code</label>
        <input id="otp_code" name="otp_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus>
        <button type="submit">Verify and download backup</button>
    </form>
    <form method="post">
        <input type="hidden" name="csrf_token" value="' . $csrfToken . '">
        <input type="hidden" name="action" value="send_code">
        <button class="secondary" type="submit">Resend code</button>
    </form>';
    } else {
        echo '<form method="post">
        <input type="hidden" name="csrf_token" value="' . $csrfToken . '">
        <input type="hidden" name="action" value="send_code">
        <button type="submit">Email me a verification code</button>
    </form>';
    }

    echo '<p><a href="super_admin_setting.php">Back to Super Admin Settings</a></p>
</main>
</body>
</html>';
    exit();
}

function clearBackupOtp()
{
    unset(
        $_SESSION['backup_otp_hash'],
        $_SESSION['backup_otp_user_id'],
        $_SESSION['backup_otp_expires_at'],
        $_SESSION['backup_otp_attempts']
    );
}

function sendBackupVerificationCode()
{
    $lastSentAt = (int)($_SESSION['backup_otp_last_sent_at'] ?? 0);
    if ($lastSentAt > 0 && time() - $lastSentAt < BACKUP_OTP_RESEND_SECONDS) {
        return 'Please wait one minute before requesting another code.';
    }

    $smtpHost = getenv('ALAWIHAO_SMTP_HOST') ?: 'smtp.gmail.com';
    $smtpPort = (int)(getenv('ALAWIHAO_SMTP_PORT') ?: 587);
    $smtpUsername = getenv('ALAWIHAO_SMTP_USERNAME') ?: '';
    $smtpPassword = getenv('ALAWIHAO_SMTP_PASSWORD') ?: '';
    $smtpFromEmail = getenv('ALAWIHAO_SMTP_FROM_EMAIL') ?: $smtpUsername;
    if ($smtpUsername === '' || $smtpPassword === '' || !filter_var($smtpFromEmail, FILTER_VALIDATE_EMAIL) || $smtpPort < 1 || $smtpPort > 65535) {
        error_log('Database backup verification email is unavailable because SMTP environment settings are incomplete.');
        return 'Email verification is not configured. Please contact the system administrator.';
    }

    require_once '../PHPMailer/src/Exception.php';
    require_once '../PHPMailer/src/PHPMailer.php';
    require_once '../PHPMailer/src/SMTP.php';

    $otpCode = (string)random_int(100000, 999999);
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $_SESSION['backup_otp_last_sent_at'] = time();

    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;
        $mail->Timeout = 15;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($smtpFromEmail, 'Alawihao db');
        $mail->addAddress(BACKUP_EMAIL);
        $mail->isHTML(true);
        $mail->Subject = 'Alawihao db database backup verification';
        $mail->Body = '<h2>Alawihao db security verification</h2>
            <p>Use this one-time code to download a database backup:</p>
            <h1 style="letter-spacing:5px">' . htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8') . '</h1>
            <p>This code expires in 5 minutes. If you did not request a backup, ignore this email.</p>';
        $mail->AltBody = "Your Alawihao db backup verification code is {$otpCode}. It expires in 5 minutes.";
        $mail->send();
    } catch (PHPMailer\PHPMailer\Exception $exception) {
        error_log('Database backup verification email failed: ' . $mail->ErrorInfo);
        return 'The verification email could not be sent. Please try again later.';
    }

    $_SESSION['backup_otp_hash'] = hash('sha256', $otpCode);
    $_SESSION['backup_otp_user_id'] = (int)$_SESSION['user_id'];
    $_SESSION['backup_otp_expires_at'] = time() + BACKUP_OTP_TTL;
    $_SESSION['backup_otp_attempts'] = 0;
    return '';
}

function streamDatabaseBackup($conn)
{
    $tablesResult = mysqli_query($conn, 'SHOW TABLES');
    if (!$tablesResult) {
        error_log('Database backup table listing failed: ' . mysqli_error($conn));
        http_response_code(500);
        exit('Unable to create the database backup. Please try again later.');
    }

    $sqlContent = "-- Alawihao db Database Backup\n";
    $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $sqlContent .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sqlContent .= "SET time_zone = \"+08:00\";\n\n";

    while ($tableRow = mysqli_fetch_row($tablesResult)) {
        $table = $tableRow[0];
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';
        $createResult = mysqli_query($conn, "SHOW CREATE TABLE {$quotedTable}");
        if (!$createResult) {
            error_log('Database backup table schema export failed: ' . mysqli_error($conn));
            http_response_code(500);
            exit('Unable to create the database backup. Please try again later.');
        }
        $createRow = mysqli_fetch_row($createResult);
        $sqlContent .= "\n\n" . $createRow[1] . ";\n\n";
        mysqli_free_result($createResult);

        $dataResult = mysqli_query($conn, "SELECT * FROM {$quotedTable}");
        if (!$dataResult) {
            error_log('Database backup table data export failed: ' . mysqli_error($conn));
            http_response_code(500);
            exit('Unable to create the database backup. Please try again later.');
        }

        $fieldCount = mysqli_num_fields($dataResult);
        while ($row = mysqli_fetch_row($dataResult)) {
            $values = [];
            for ($index = 0; $index < $fieldCount; $index++) {
                $values[] = $row[$index] === null
                    ? 'NULL'
                    : "'" . mysqli_real_escape_string($conn, (string)$row[$index]) . "'";
            }
            $sqlContent .= "INSERT INTO {$quotedTable} VALUES(" . implode(',', $values) . ");\n";
        }
        mysqli_free_result($dataResult);
    }
    mysqli_free_result($tablesResult);

    if (function_exists('logAdminAction')) {
        logAdminAction($conn, 'Downloaded a database backup after email verification.');
    }

    $filename = 'alawihao_db_backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: Binary');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sqlContent));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo $sqlContent;
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestToken = $_POST['csrf_token'] ?? '';
    if (!is_string($requestToken) || !hash_equals($_SESSION['backup_csrf_token'], $requestToken)) {
        $message = 'Your session expired. Please reload this page and try again.';
    } elseif (($_POST['action'] ?? '') === 'send_code') {
        $message = sendBackupVerificationCode();
        if ($message === '') {
            $message = 'A verification code has been sent to ' . BACKUP_EMAIL . '.';
            $messageType = 'success';
        }
    } elseif (($_POST['action'] ?? '') === 'verify_code') {
        $otpExpiresAt = (int)($_SESSION['backup_otp_expires_at'] ?? 0);
        $otpUserId = (int)($_SESSION['backup_otp_user_id'] ?? 0);
        if (!isset($_SESSION['backup_otp_hash']) || $otpUserId !== (int)$_SESSION['user_id']) {
            $message = 'Request a new verification code before downloading a backup.';
            clearBackupOtp();
        } elseif ($otpExpiresAt <= time()) {
            $message = 'The verification code expired. Request a new code.';
            clearBackupOtp();
        } elseif ((int)($_SESSION['backup_otp_attempts'] ?? 0) >= BACKUP_OTP_MAX_ATTEMPTS) {
            $message = 'Too many incorrect attempts. Request a new code.';
            clearBackupOtp();
        } else {
            $submittedCode = $_POST['otp_code'] ?? '';
            $_SESSION['backup_otp_attempts'] = (int)($_SESSION['backup_otp_attempts'] ?? 0) + 1;
            if (!is_string($submittedCode)
                || !preg_match('/^[0-9]{6}$/D', $submittedCode)
                || !hash_equals($_SESSION['backup_otp_hash'], hash('sha256', $submittedCode))) {
                $message = (int)$_SESSION['backup_otp_attempts'] >= BACKUP_OTP_MAX_ATTEMPTS
                    ? 'Too many incorrect attempts. Request a new code.'
                    : 'That verification code is incorrect.';
                if ((int)$_SESSION['backup_otp_attempts'] >= BACKUP_OTP_MAX_ATTEMPTS) {
                    clearBackupOtp();
                }
            } else {
                clearBackupOtp();
                streamDatabaseBackup($conn);
            }
        }
    } else {
        $message = 'Invalid request.';
    }
}

renderBackupVerificationPage($message, $messageType);
