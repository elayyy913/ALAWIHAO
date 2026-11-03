<?php
session_start();
$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($role, ['Admin', 'Super Admin'], true)) {
    header("Location: ../login.php");
    exit();
}

$settings_page = ($_SESSION['role'] ?? '') === 'Super Admin'
    ? 'super_admin_setting.php'
    : 'admin_settings.php';
$user_id = (int)$_SESSION['user_id'];
$message = "";

$clear_recovery = static function () {
    unset(
        $_SESSION['recovery_otp_hash'],
        $_SESSION['recovery_pending_email'],
        $_SESSION['recovery_user_id'],
        $_SESSION['recovery_otp_expires_at'],
        $_SESSION['recovery_otp_attempts']
    );
};

if (
    !isset($_SESSION['recovery_otp_hash'], $_SESSION['recovery_pending_email'], $_SESSION['recovery_user_id'], $_SESSION['recovery_otp_expires_at'])
    || (int)$_SESSION['recovery_user_id'] !== $user_id
) {
    $_SESSION['error_message'] = "There is no active email verification. Please request a new code.";
    header("Location: " . $settings_page);
    exit();
}

if (time() >= (int)$_SESSION['recovery_otp_expires_at']) {
    $clear_recovery();
    $_SESSION['error_message'] = "The OTP code has expired. Please request a new one.";
    header("Location: " . $settings_page);
    exit();
}

$csrf_token = $_SESSION['recovery_csrf_token'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $request_csrf_token = $_POST['csrf_token'] ?? '';
    if ($csrf_token === '' || !is_string($request_csrf_token) || !hash_equals($csrf_token, $request_csrf_token)) {
        $message = "Your session expired. Please return to settings and request a new code.";
    } else {
        $user_otp = trim($_POST['otp_code'] ?? '');
        if (!preg_match('/^\d{6}$/D', $user_otp) || !hash_equals($_SESSION['recovery_otp_hash'], hash('sha256', $user_otp))) {
            $_SESSION['recovery_otp_attempts'] = (int)($_SESSION['recovery_otp_attempts'] ?? 0) + 1;
            if ($_SESSION['recovery_otp_attempts'] >= 5) {
                $clear_recovery();
                $_SESSION['error_message'] = "Too many incorrect attempts. Please request a new verification code.";
                header("Location: " . $settings_page);
                exit();
            } else {
                $message = "Invalid OTP code. Please try again.";
            }
        } else {
            include '../db_connect.php';
            $new_email = (string)$_SESSION['recovery_pending_email'];
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            if (!$check) {
                error_log("Account recovery email verification query failed: " . $conn->error);
                $message = "Unable to update your email right now. Please try again.";
            } else {
                $check->bind_param("si", $new_email, $user_id);
                if (!$check->execute()) {
                    error_log("Account recovery email verification query failed: " . $check->error);
                    $message = "Unable to update your email right now. Please try again.";
                } elseif ($check->get_result()->num_rows > 0) {
                    $clear_recovery();
                    $_SESSION['error_message'] = "That email address is now being used by another account. Please request a new code with a different address.";
                    $check->close();
                    header("Location: " . $settings_page);
                    exit();
                } else {
                    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ? AND role = ?");
                    if (!$stmt) {
                        error_log("Account recovery email update preparation failed: " . $conn->error);
                        $message = "Unable to update your email right now. Please try again.";
                    } else {
                        $stmt->bind_param("sis", $new_email, $user_id, $role);
                        if ($stmt->execute() && $stmt->affected_rows === 1) {
                            $clear_recovery();
                            unset($_SESSION['recovery_last_sent_at']);
                            $_SESSION['success_message'] = "Recovery email updated successfully! Your new email is now your login and recovery email.";
                            header("Location: " . $settings_page);
                            exit();
                        }

                        error_log("Account recovery email update failed: " . $stmt->error);
                        $message = "Unable to update your email right now. Please try again.";
                        $stmt->close();
                    }
                }
                $check->close();
            }
        }
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
        We've sent a 6-digit code to <strong><?php echo htmlspecialchars($_SESSION['recovery_pending_email'], ENT_QUOTES, 'UTF-8'); ?></strong>. Please enter it below to confirm your new email.
    </p>

    <?php if ($message !== ''): ?>
        <div class="alert error"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
            <label for="otp_code">6-Digit OTP Code</label>
            <input type="text" id="otp_code" name="otp_code" class="settings-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="------" required autofocus>
        </div>

        <button type="submit" name="verify_otp" class="btn-primary-action">
            Verify & Update Email
        </button>
    </form>

    <a href="<?php echo htmlspecialchars($settings_page, ENT_QUOTES, 'UTF-8'); ?>" class="back-link">
        <i class="fa fa-arrow-left"></i> Cancel and go back to settings
    </a>
</div>

</body>
</html>