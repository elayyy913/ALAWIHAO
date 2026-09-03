<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

if (isset($_POST['login'])) {
    $login_input = trim($_POST['field_user_id']);
    $pass        = trim($_POST['field_user_pass']); 

    // Hanapin muna sa database kung may tumutugmang email o generated_id
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? OR generated_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $role = $row['role'];
        $db_email = $row['email'];
        $db_generated_id = $row['generated_id'];

        // Strict validation base sa role:
        if ($role == 'Super Admin' || $role == 'User') {
            if ($login_input !== $db_email) {
                echo "<script>alert('Please use your Email.'); window.location.href='login.php';</script>";
                exit();
            }
        } else {
            if ($login_input !== $db_generated_id) {
                echo "<script>alert('Please use your code(ex.2026-001).'); window.location.href='login.php';</script>";
                exit();
            }
        }

        // Pagkatapos masigurong tama ang uri ng input, i-check ang password:
        if ($pass == $row['password']) {
            
            if ($row['role'] !== 'Super Admin' && strtolower($row['status']) !== 'approved') {
                echo "<script>alert('Your account is still pending approval.'); window.location.href='login.php';</script>";
                exit();
            }

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role']    = $row['role']; 
            $_SESSION['name']    = $row['first_name'];

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
            echo "<script>alert('Wrong Password.'); window.location.href='login.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Account not Found.'); window.location.href='login.php';</script>";
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
    --sage: #75945D;
    --sage-hover: #617C4C;
    --text-dark: #1A202C;
    --text-muted: #4A5568;
    --border-light: rgba(255, 255, 255, 0.6);
}

* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
    font-family: 'Plus Jakarta Sans', sans-serif; 
}

body {
    /* Subtle overlay para manatiling buhay at malinaw ang background photo */
    background-image: linear-gradient(rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.2)), url('images/alawihao.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    width: 100vw;
    padding: 20px;
}

.login-card {
    /* White semi-transparent card (Walang blur para kitang-kita ang litrato sa likod) */
    background: rgba(255, 255, 255, 0.70); 
    width: 100%;
    max-width: 420px;
    padding: 44px 40px;
    border-radius: 16px;
    border: 1px solid var(--border-light);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.18);
    text-align: center;
    -webkit-backdrop-filter: blur(8px);
}

.logo-area { 
    margin-bottom: 28px; 
}

/* Dark text contrast para madaling basahin sa puting pad */
.logo-area h2 { 
    color: var(--text-dark); 
    font-size: 1.8rem; 
    font-weight: 700; 
    letter-spacing: -0.02em; 
}

.logo-area p { 
    color: var(--text-muted); 
    font-size: 0.95rem; 
    margin-top: 6px; 
    font-weight: 500;
}

.form-group { 
    text-align: left; 
    margin-bottom: 18px; 
    position: relative; 
}

.form-group label { 
    display: block; 
    font-size: 0.85rem; 
    font-weight: 600; 
    color: var(--text-dark); 
    margin-bottom: 8px; 
    margin-left: 2px; 
}

.input-wrapper { 
    position: relative; 
    display: flex; 
    align-items: center; 
}

/* Input fields with slight transparency */
input {
    width: 100%;
    padding: 13px 16px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: rgba(255, 255, 255, 0.85);
    font-size: 0.95rem;
    color: var(--text-dark);
    font-weight: 500;
    transition: all 0.2s ease;
}

input::placeholder {
    color: #A0AEC0;
    font-weight: 400;
}

.pass-input { 
    padding-right: 48px; 
}

input:focus {
    outline: none;
    border-color: var(--sage);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(117, 148, 93, 0.25);
}

.toggle-pass {
    position: absolute;
    right: 14px;
    cursor: pointer;
    color: #718096;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    padding: 4px;
    transition: color 0.2s;
    z-index: 10;
}

.toggle-pass svg { 
    width: 20px; 
    height: 20px; 
}

.toggle-pass:hover { 
    color: var(--sage); 
}

.forgot-pass {
    display: block;
    text-align: right;
    font-size: 0.85rem;
    color: var(--sage);
    text-decoration: none;
    font-weight: 600;
    margin-top: -6px;
    margin-bottom: 22px;
    transition: color 0.2s;
}

.forgot-pass:hover { 
    color: var(--sage-hover); 
    text-decoration: underline; 
}

.btn-login {
    width: 100%;
    padding: 14px;
    background: var(--sage);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 12px rgba(117, 148, 93, 0.3);
}

.btn-login:hover {
    background: var(--sage-hover);
    transform: translateY(-1px);
}

.footer-text { 
    margin-top: 28px; 
    font-size: 0.9rem; 
    color: var(--text-muted); 
    font-weight: 500; 
}

.footer-text a { 
    color: var(--sage); 
    text-decoration: none; 
    font-weight: 700; 
}

.footer-text a:hover { 
    text-decoration: underline; 
}

@media (max-width: 480px) { 
    .login-card { 
        padding: 32px 20px; 
    } 
}
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
                       placeholder="name@email.com or worker code" 
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
        lucide.createIcons();

        function togglePassword() {
            const passInput = document.getElementById('loginPass');
            const toggleBtn = document.getElementById('toggleBtn');
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                toggleBtn.innerHTML = '<i data-lucide="eye-off"></i>';
            } else {
                passInput.type = 'password';
                toggleBtn.innerHTML = '<i data-lucide="eye"></i>';
            }
            
            lucide.createIcons();
        }
    </script>
</body>
</html>