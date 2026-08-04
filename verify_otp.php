<?php
// 1. I-set ang timezone sa Manila para mag-match ang oras
date_default_timezone_set('Asia/Manila');

session_start();
include 'db_connect.php';

// Security: Kung walang session ng email, ibalik sa simula
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

if (isset($_POST['verify_otp'])) {
    // Pagsamahin ang numbers mula sa 6 inputs
    $user_otp = implode('', $_POST['otp']);
    $email = $_SESSION['reset_email'];

    // I-check sa DB kung match ang OTP
    // Gumamit tayo ng PHP comparison para sa expiry para mas accurate sa timezone
    $query = "SELECT * FROM users WHERE email = '$email' AND reset_code = '$user_otp' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $current_time = date("Y-m-d H:i:s");

        if ($current_time <= $row['reset_expiry']) {
            // Correct OTP and not expired!
            $_SESSION['otp_verified'] = true;
            header("Location: reset_password.php");
            exit();
        } else {
            $error = "Expired na ang code. Mag-request po ng bago.";
        }
    } else {
        $error = "Maling code. Pakicheck ulit ang email mo.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | Alawihao CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sage: #8DAE74; --bg: #F9FBFA; --text: #2D3748; }
        body { background: var(--bg); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); width: 100%; max-width: 400px; text-align: center; }
        .otp-inputs { display: flex; justify-content: center; gap: 8px; margin: 25px 0; }
        .otp-inputs input { width: 45px; height: 55px; text-align: center; font-size: 1.5rem; font-weight: 700; border: 1.5px solid #E2E8F0; border-radius: 12px; background: #FAFAFA; transition: 0.2s; }
        .otp-inputs input:focus { border-color: var(--sage); outline: none; box-shadow: 0 0 0 4px rgba(141, 174, 116, 0.1); background: white; }
        .btn-verify { width: 100%; padding: 14px; background: var(--sage); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-verify:hover { background: #75945D; transform: translateY(-1px); }
        .error-msg { color: #E53E3E; font-size: 0.85rem; margin-bottom: 15px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Enter Verification Code</h2>
        <p style="color: #718096; font-size: 0.9rem;">We sent a 6-digit code to:<br><b><?php echo $_SESSION['reset_email']; ?></b></p>

        <?php if(isset($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="otp-inputs">
                <?php for($i=0; $i<6; $i++): ?>
                    <input type="text" name="otp[]" maxlength="1" required 
                           onkeyup="moveNext(this, <?php echo $i; ?>)" 
                           autocomplete="one-time-code"
                           pattern="\d*">
                <?php endfor; ?>
            </div>
            <button type="submit" name="verify_otp" class="btn-verify">Verify OTP</button>
        </form>

        <p style="margin-top: 20px; font-size: 0.85rem; color: #718096;">
            Didn't get the code? <a href="forgot_password.php" style="color: var(--sage); font-weight: 600; text-decoration: none;">Try again</a>
        </p>
    </div>

    <script>
        function moveNext(current, index) {
            // Auto-tab to next input
            if (current.value.length >= 1 && index < 5) {
                document.getElementsByName('otp[]')[index + 1].focus();
            }
        }

        // Handle Backspace
        document.querySelectorAll('input[name="otp[]"]').forEach((input, index) => {
            input.addEventListener('keydown', (e) => {
                if (e.key === "Backspace" && input.value === "" && index > 0) {
                    document.getElementsByName('otp[]')[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>