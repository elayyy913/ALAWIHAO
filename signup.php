<?php
session_start();
include 'db_connect.php';

$signupAlert = null;

if (isset($_POST['signup'])) {
    $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = $_POST['password']; 

    $check_email = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $signupAlert = ['type' => 'error', 'title' => 'Email already registered', 'message' => 'May account na gamit ang email na ito. Subukan ang ibang email o mag-log in sa account mo.'];
    } else {
        $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
        $count = mysqli_fetch_assoc($res)['total'];

        if ($count == 0) {
            $final_role = 'Super Admin';
            $year = date("Y");
            $new_id_num = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $generated_id = "ALW-$year-$new_id_num";
            $success_message = "Registration Successful! Your System ID is: $generated_id";
            $id_value = "'$generated_id'";
        } else {
            $final_role = 'User';
            $success_message = "Registration Successful! You can now log in using your email.";
            $id_value = "NULL";
        }

        $sql = "INSERT INTO users (generated_id, first_name, last_name, email, password, role, status, created_at)
                VALUES ($id_value, '$fname', '$lname', '$email', '$pass', '$final_role', 'Approved', NOW())";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['auth_alert'] = ['type' => 'success', 'title' => 'Registration successful', 'message' => $success_message];
            header('Location: login.php');
            exit();
        } else {
            $signupAlert = ['type' => 'error', 'title' => 'Registration failed', 'message' => 'We could not create your account. ' . mysqli_error($conn)];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account | Alawihao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sage: #95AF7E; --dark-sage: #86A16D; --bg: #F7FAFC; --text: #2D3748; --border: #E2E8F0; --muted: #A0AEC0; }
        
        body { 
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('images/alawihao.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: 'Inter', sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px; 
        }
        
        .card { 
            /* Mas transparent (0.60) at naka-align na sa kaliwa ang mga laman */
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
                
        h2 { text-align: center; color: #1A202C; margin-bottom: 10px; font-weight: 700; font-size: 1.8rem; }
        p.subtitle { text-align: center; color: #4A5568; font-size: 0.9rem; margin-bottom: 25px; font-weight: 500; }
        
        label { display: block; font-size: 13px; font-weight: 700; margin: 15px 0 5px; color: #2D3748; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        
        input { width: 100%; padding: 14px; border-radius: 10px; border: 1px solid var(--border); box-sizing: border-box; font-size: 15px; background: rgba(255, 255, 255, 0.8); transition: all 0.2s; }
        input:focus { border-color: var(--sage); outline: none; background: #FFF; box-shadow: 0 0 0 3px rgba(149, 175, 126, 0.2); }
        
        .password-container {
            position: relative;
            width: 100%;
        }
        .password-container input {
            padding-right: 45px;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--muted);
            font-size: 16px;
        }
        .toggle-password:hover {
            color: var(--text);
        }

        .password-policies { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px; min-height: 20px; }
        .policy { font-size: 11px; color: #E53E3E; background: #FFF5F5; padding: 4px 10px; border-radius: 20px; border: 1px solid #FEB2B2; display: flex; align-items: center; transition: all 0.3s ease; }
        .policy.valid { display: none; opacity: 0; transform: translateY(-5px); }
        #success-msg { display: none; color: #2F855A; font-size: 11px; font-weight: 600; }

        .btn { width: 100%; padding: 16px; background: var(--sage); color: #FFFFFF; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; margin-top: 30px; font-size: 16px; transition: 0.3s; }
        .btn:hover { background: var(--dark-sage); transform: translateY(-1px); }
        
        .card-footer { text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1); }
        .card-footer p { font-size: 14px; color: #4A5568; }
        .card-footer a { color: #2F855A; text-decoration: none; font-weight: bold; }

        .auth-alert-overlay { position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 20px; background: rgba(15, 23, 42, 0.55); -webkit-backdrop-filter: blur(5px); backdrop-filter: blur(5px); animation: alert-fade-in 0.18s ease-out; }
        .auth-alert-overlay[hidden] { display: none; }
        .auth-alert-card { position: relative; width: min(100%, 390px); padding: 32px; border: 1px solid rgba(255, 255, 255, 0.8); border-radius: 20px; background: #fff; box-shadow: 0 24px 70px rgba(15, 23, 42, 0.25); text-align: center; animation: alert-pop-in 0.22s ease-out; }
        .auth-alert-close { position: absolute; top: 12px; right: 14px; border: 0; background: transparent; color: #718096; font-size: 24px; line-height: 1; cursor: pointer; }
        .auth-alert-icon { display: grid; place-items: center; width: 58px; height: 58px; margin: 0 auto 16px; border-radius: 50%; background: #FEF2F2; color: #DC2626; font-size: 28px; font-weight: 700; }
        .auth-alert-overlay.success .auth-alert-icon { background: #ECFDF3; color: #15803D; }
        .auth-alert-card h2 { margin: 0 0 8px; color: #1A202C; font-size: 1.3rem; }
        .auth-alert-card p { margin: 0; color: #64748B; font-size: 0.95rem; line-height: 1.6; overflow-wrap: anywhere; }
        .auth-alert-button { width: 100%; margin-top: 24px; padding: 12px 18px; border: 0; border-radius: 10px; background: var(--sage); color: #fff; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: background 0.2s, transform 0.2s; }
        .auth-alert-button:hover { background: var(--dark-sage); transform: translateY(-1px); }
        @keyframes alert-fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes alert-pop-in { from { opacity: 0; transform: translateY(8px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
    </style>
</head>
<body>
    <div class="auth-alert-overlay" id="authAlert" role="alertdialog" aria-modal="true" aria-labelledby="authAlertTitle" aria-describedby="authAlertMessage" hidden>
        <div class="auth-alert-card">
            <button type="button" class="auth-alert-close" aria-label="Close alert" onclick="closeAuthAlert()">&times;</button>
            <div class="auth-alert-icon" id="authAlertIcon" aria-hidden="true">!</div>
            <h2 id="authAlertTitle"></h2>
            <p id="authAlertMessage"></p>
            <button type="button" class="auth-alert-button" id="authAlertButton" onclick="closeAuthAlert()">Got it</button>
        </div>
    </div>

    <div class="card">
        <h2>Create Account</h2>
        <p class="subtitle">Join Alawihao Health Center System</p>
        
        <form method="POST" id="regForm">
            <label>First Name</label>
            <input type="text" name="first_name" required>
            
            <label>Last Name</label>
            <input type="text" name="last_name" required>

            <label>Email Address</label>
            <input type="email" name="email" required placeholder="example@email.com">

            <label>Password</label>
            <div class="password-container">
                <input type="password" name="password" id="passInput" required placeholder="Create a strong password">
                <i class="fa fa-eye toggle-password" id="togglePass"></i>
            </div>
            
            <div class="password-policies" id="policyContainer">
                <div class="policy" id="len">8+ characters</div>
                <div class="policy" id="up">Uppercase</div>
                <div class="policy" id="num">Number</div>
                <div class="policy" id="spec">Special char</div>
                <div id="success-msg">✓ Password strength met</div>
            </div>

            <label style="margin-top: 20px;">Confirm Password</label>
            <div class="password-container">
                <input type="password" name="password_confirmation" id="confirmInput" required placeholder="Repeat your password" onpaste="return false;">
                <i class="fa fa-eye toggle-password" id="toggleConfirm"></i>
            </div>

            <button type="submit" name="signup" class="btn">Register Now</button>
        </form>

        <div class="card-footer">
            <p>Already have an account? <a href="login.php">Log in here</a></p>
        </div>
    </div>

    <script>
        const pass = document.getElementById('passInput');
        const confirmPass = document.getElementById('confirmInput');
        const successMsg = document.getElementById('success-msg');
        
        const togglePass = document.getElementById('togglePass');
        const toggleConfirm = document.getElementById('toggleConfirm');

        function showAuthAlert(alert) {
            const overlay = document.getElementById('authAlert');
            overlay.classList.toggle('success', alert.type === 'success');
            document.getElementById('authAlertIcon').textContent = alert.type === 'success' ? '✓' : '×';
            document.getElementById('authAlertTitle').textContent = alert.title;
            document.getElementById('authAlertMessage').textContent = alert.message;
            overlay.hidden = false;
            document.getElementById('authAlertButton').focus();
        }

        function closeAuthAlert() {
            document.getElementById('authAlert').hidden = true;
        }

        document.getElementById('authAlert').addEventListener('click', function(event) {
            if (event.target === this) closeAuthAlert();
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeAuthAlert();
        });

        <?php if ($signupAlert !== null): ?>
        showAuthAlert(<?php echo json_encode($signupAlert, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>);
        <?php endif; ?>

        togglePass.addEventListener('click', function () {
            const type = pass.getAttribute('type') === 'password' ? 'text' : 'password';
            pass.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        toggleConfirm.addEventListener('click', function () {
            const type = confirmPass.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPass.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        const policies = {
            len: document.getElementById('len'),
            up: document.getElementById('up'),
            num: document.getElementById('num'),
            spec: document.getElementById('spec')
        };

        pass.addEventListener('input', () => {
            const val = pass.value;
            const checks = {
                len: val.length >= 8,
                up: /[A-Z]/.test(val),
                num: /[0-9]/.test(val),
                spec: /[^A-Za-z0-9]/.test(val)
            };

            Object.keys(checks).forEach(key => {
                if (checks[key]) {
                    policies[key].classList.add('valid');
                } else {
                    policies[key].classList.remove('valid');
                }
            });

            if (checks.len && checks.up && checks.num && checks.spec) {
                successMsg.style.display = 'block';
            } else {
                successMsg.style.display = 'none';
            }
        });

        document.getElementById('regForm').onsubmit = function(e) {
            if (pass.value !== confirmPass.value) {
                e.preventDefault();
                showAuthAlert({type: 'error', title: 'Passwords do not match', message: 'Please make sure both password fields are identical.'});
            }
        };
    </script>
</body>
</html>