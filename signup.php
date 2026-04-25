<?php
include 'db_connect.php';

if (isset($_POST['signup'])) {
    $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass  = $_POST['password']; 

    $check_email = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        echo "<script>alert('Error: Ang email na ito ay gamit na!'); window.history.back();</script>";
        exit();
    }

    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
    $count = mysqli_fetch_assoc($res)['total'];
    
    $final_role = ($count == 0) ? 'Super Admin' : 'User';
    
    $year = date("Y");
    $new_id_num = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    $generated_id = "ALW-$year-$new_id_num";

    $sql = "INSERT INTO users (generated_id, first_name, last_name, email, password, role, status, created_at) 
            VALUES ('$generated_id', '$fname', '$lname', '$email', '$pass', '$final_role', 'Approved', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Registration Successful! Your System ID is: $generated_id'); window.location='login.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account | Alawihao</title>
    <style>
        :root { --sage: #95AF7E; --dark-sage: #86A16D; --bg: #F7FAFC; --text: #2D3748; --border: #E2E8F0; --muted: #A0AEC0; }
        body { background-color: var(--bg); font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        
        .card { background-color: #FFFFFF; width: 420px; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        h2 { text-align: center; color: #1A202C; margin-bottom: 10px; font-weight: 700; font-size: 1.8rem; }
        p.subtitle { text-align: center; color: #718096; font-size: 0.9rem; margin-bottom: 25px; }
        
        label { display: block; font-size: 13px; font-weight: 700; margin: 15px 0 5px; color: #4A5568; text-transform: uppercase; letter-spacing: 0.5px; }
        
        input { width: 100%; padding: 14px; border-radius: 10px; border: 1px solid var(--border); box-sizing: border-box; font-size: 15px; background: #FAFAFA; transition: all 0.2s; }
        input:focus { border-color: var(--sage); outline: none; background: #FFF; box-shadow: 0 0 0 3px rgba(149, 175, 126, 0.1); }
        
        /* Password Dynamic Feedback */
        .password-policies { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px; min-height: 20px; }
        .policy { font-size: 11px; color: #E53E3E; background: #FFF5F5; padding: 4px 10px; border-radius: 20px; border: 1px solid #FEB2B2; display: flex; align-items: center; transition: all 0.3s ease; }
        
        /* Itatago natin yung policy kapag valid na */
        .policy.valid { display: none; opacity: 0; transform: translateY(-5px); }

        /* Style para sa "All Good" message */
        #success-msg { display: none; color: var(--sage); font-size: 11px; font-weight: 600; }

        .btn { width: 100%; padding: 16px; background: var(--sage); color: #FFFFFF; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; margin-top: 30px; font-size: 16px; transition: 0.3s; }
        .btn:hover { background: var(--dark-sage); transform: translateY(-1px); }
        
        .card-footer { text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--border); }
        .card-footer p { font-size: 14px; color: #718096; }
        .card-footer a { color: var(--sage); text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
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
            <input type="password" name="password" id="passInput" required placeholder="Create a strong password">
            
            <div class="password-policies" id="policyContainer">
                <div class="policy" id="len">8+ characters</div>
                <div class="policy" id="up">Uppercase</div>
                <div class="policy" id="num">Number</div>
                <div class="policy" id="spec">Special char</div>
                <div id="success-msg">✓ Password strength met</div>
            </div>

            <label style="margin-top: 20px;">Confirm Password</label>
            <input type="password" name="password_confirmation" id="confirmInput" required placeholder="Repeat your password">

            <button type="submit" name="signup" class="btn">Register Now</button>
        </form>

        <div class="card-footer">
            <p>Already have an account? <a href="login.php">Log in here</a></p>
        </div>
    </div>

    <script>
        const pass = document.getElementById('passInput');
        const successMsg = document.getElementById('success-msg');
        
        const policies = {
            len: document.getElementById('len'),
            up: document.getElementById('up'),
            num: document.getElementById('num'),
            spec: document.getElementById('spec')
        };

        pass.addEventListener('input', () => {
            const val = pass.value;
            
            // Check validations
            const checks = {
                len: val.length >= 8,
                up: /[A-Z]/.test(val),
                num: /[0-9]/.test(val),
                spec: /[^A-Za-z0-9]/.test(val)
            };

            // Toggle visibility of each tag
            Object.keys(checks).forEach(key => {
                if (checks[key]) {
                    policies[key].classList.add('valid');
                } else {
                    policies[key].classList.remove('valid');
                }
            });

            // Ipakita ang success message kapag empty na ang listahan ng kulang
            if (checks.len && checks.up && checks.num && checks.spec) {
                successMsg.style.display = 'block';
            } else {
                successMsg.style.display = 'none';
            }
        });

        document.getElementById('regForm').onsubmit = function(e) {
            if (pass.value !== document.getElementById('confirmInput').value) {
                e.preventDefault();
                alert("Passwords do not match!");
            }
        };
    </script>
</body>
</html>