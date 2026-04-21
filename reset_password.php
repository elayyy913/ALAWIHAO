<?php
// 1. Timezone and Session
date_default_timezone_set('Asia/Manila');
session_start();
include 'db_connect.php';

// 2. Security Check
if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    // Kung pinilit pumasok nang hindi nag-verify, balik sa login
    header("Location: login.php");
    exit();
}

$email = $_SESSION['reset_email'];
$error = "";

// 3. Logic kapag pinindot ang Update Password
if (isset($_POST['submit_reset'])) {
    $new_pass = mysqli_real_escape_string($conn, trim($_POST['new_password']));
    $confirm_pass = mysqli_real_escape_string($conn, trim($_POST['confirm_password']));

    if (empty($new_pass) || empty($confirm_pass)) {
        $error = "Please fill in all fields.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Passwords do not match!";
    } elseif (strlen($new_pass) < 6) { // Basic security check
        $error = "Password must be at least 6 characters.";
    } else {
        // UPDATE QUERY: Dito natin papalitan ang password
        // Nililinis din natin ang reset_code para hindi na magamit ulit
        $update_query = "UPDATE users SET password = '$new_pass', reset_code = NULL, reset_expiry = NULL WHERE email = '$email'";
        
        if (mysqli_query($conn, $update_query)) {
            // SUCCESS! Burahin ang traces ng reset session
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_email']);
            
            echo "<script>
                    alert('Success! Your password has been updated.');
                    window.location.href = 'login.php';
                  </script>";
            exit();
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | Alawihao CMS</title>
    <style>
        :root { --sage: #8DAE74; --bg: #F9FBFA; --text: #2D3748; }
        body { background: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); width: 100%; max-width: 400px; text-align: center; }
        .form-group { text-align: left; margin-bottom: 20px; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text); }
        input { width: 100%; padding: 14px; border-radius: 12px; border: 1.5px solid #E2E8F0; background: #FAFAFA; font-size: 1rem; transition: 0.3s; }
        input:focus { border-color: var(--sage); outline: none; box-shadow: 0 0 0 4px rgba(141, 174, 116, 0.1); background: white; }
        .btn-update { width: 100%; padding: 14px; background: var(--sage); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-update:hover { background: #75945D; transform: translateY(-1px); }
        .error-msg { color: #E53E3E; background: #FFF5F5; padding: 10px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 15px; border: 1px solid #FEB2B2; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size: 30px; margin-bottom: 10px;">🔑</div>
        <h2>Create New Password</h2>
        <p style="color: #718096; font-size: 0.9rem; margin-bottom: 25px;">Setting password for:<br><b><?php echo $email; ?></b></p>

        <?php if($error !== ""): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="reset_password.php" method="POST" autocomplete="off">
            <input style="display:none" type="text" name="fake_u"/>
            <input style="display:none" type="password" name="fake_p"/>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="••••••••" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="••••••••" required autocomplete="new-password">
            </div>

            <button type="submit" name="submit_reset" class="btn-update">Update Password</button>
        </form>
    </div>
</body>
</html>