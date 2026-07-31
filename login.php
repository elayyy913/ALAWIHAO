<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

if (isset($_POST['login'])) {
    $login_input = mysqli_real_escape_string($conn, trim($_POST['field_user_id']));
    $pass        = trim($_POST['field_user_pass']); 

    $query = "SELECT * FROM users WHERE (email = '$login_input' OR generated_id = '$login_input') LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        if ($pass == $row['password']) {
            
            if ($row['role'] !== 'Super Admin' && strtolower($row['status']) !== 'approved') {
                echo "<script>alert('Your account is still pending approval.'); window.location.href='login.php';</script>";
                exit();
            }

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role']    = $row['role']; 
            $_SESSION['name']    = $row['first_name'];

            // FIX: Idinagdag ang 'admin/' path folder para hindi na mag-404 Not Found!
            if ($row['role'] == 'Super Admin') {
                header("Location: admin/super_admin_dashboard.php");
                exit();
            } 
            elseif ($row['role'] == 'Admin') {
                header("Location: admin/admin_dashboard.php");
                exit();
            } 
            elseif ($row['role'] == 'User') {
                header("Location: user_dashboard.php");
                exit();
            } 
            else {
                header("Location: main_user.php");
                exit();
            }

        } else {
            echo "<script>alert('Maling password. Pakisuri ulit.'); window.location.href='login.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Hindi mahanap ang Email o System ID na ito.'); window.location.href='login.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Alawihao Health Center</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --sage: #8DAE74;
            --dark-sage: #75945D;
            --bg: #F9FBFA;
            --text-main: #2D3748;
            --text-muted: #718096;
            --white: #FFFFFF;
            --border: #E2E8F0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background-color: var(--bg);
            background-image: radial-gradient(circle at 2px 2px, #e2e8f0 1px, transparent 0);
            background-size: 40px 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-card {
            background: var(--white);
            width: 100%;
            max-width: 420px;
            padding: 48px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03), 0 4px 10px rgba(0, 0, 0, 0.02);
            text-align: center;
        }

        .logo-area { margin-bottom: 32px; }
        .logo-area h2 { color: var(--text-main); font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em; }
        .logo-area p { color: var(--text-muted); font-size: 0.95rem; margin-top: 8px; }

        .form-group { text-align: left; margin-bottom: 20px; position: relative; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 8px; margin-left: 4px; }

        .input-wrapper { position: relative; display: flex; align-items: center; }

        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: #FAFAFA;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .pass-input { padding-right: 50px; }

        input:focus {
            outline: none;
            border-color: var(--sage);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(141, 174, 116, 0.1);
        }

        .toggle-pass {
            position: absolute;
            right: 14px;
            cursor: pointer;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            padding: 4px; /* Space para madaling mapindot */
            transition: color 0.2s;
            z-index: 10;
        }

        /* Prevent double icons sizing issues */
        .toggle-pass svg { width: 20px; height: 20px; }

        .toggle-pass:hover { color: var(--sage); }

        .forgot-pass {
            display: block;
            text-align: right;
            font-size: 0.85rem;
            color: var(--sage);
            text-decoration: none;
            font-weight: 600;
            margin-top: -10px;
            margin-bottom: 24px;
            transition: color 0.2s;
        }

        .forgot-pass:hover { color: var(--dark-sage); text-decoration: underline; }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--sage);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(141, 174, 116, 0.2);
        }

        .btn-login:hover {
            background: var(--dark-sage);
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(141, 174, 116, 0.3);
        }

        .footer-text { margin-top: 32px; font-size: 0.9rem; color: var(--text-muted); }
        .footer-text a { color: var(--sage); text-decoration: none; font-weight: 700; }
        .footer-text a:hover { text-decoration: underline; }

        @media (max-width: 480px) { .login-card { padding: 32px 24px; } }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo-area">
            <h2>Welcome Back</h2>
            <p>Access your health center account</p>
        </div>

        <form method="POST" autocomplete="off">
            <input style="display:none" type="text" name="fake_user_field"/>
            <input style="display:none" type="password" name="fake_pass_field"/>

            <div class="form-group">
                <label>Email or System ID</label>
                <input type="text" name="field_user_id" required 
                       placeholder="name@email.com or ALW-XXXX" 
                       autocomplete="off">
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <input type="password" name="field_user_pass" id="loginPass" 
                           class="pass-input" placeholder="••••••••" required 
                           autocomplete="new-password">
                    <button type="button" class="toggle-pass" id="toggleBtn" onclick="togglePassword()">
                        <i data-lucide="eye"></i>
                    </button>
                </div>
            </div>

            <a href="forgot_password.php" class="forgot-pass">Forgot password?</a>

            <button type="submit" name="login" class="btn-login">Sign In</button>
        </form>

        <div class="footer-text">
            Don't have an account? <a href="signup.php">Create Account</a>
        </div>
    </div>

    <script>
        // Initial render of icons
        lucide.createIcons();

        function togglePassword() {
            const passInput = document.getElementById('loginPass');
            const toggleBtn = document.getElementById('toggleBtn');
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                // Clear the old icon content and replace it
                toggleBtn.innerHTML = '<i data-lucide="eye-off"></i>';
            } else {
                passInput.type = 'password';
                // Clear the old icon content and replace it
                toggleBtn.innerHTML = '<i data-lucide="eye"></i>';
            }
            
            // Re-initialize Lucide for the newly added HTML
            lucide.createIcons();
        }
    </script>
</body>
</html>