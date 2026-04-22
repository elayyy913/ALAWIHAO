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
        
        /* Password Guidelines Styling */
        .password-policies { margin-top: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
        .policy { font-size: 11px; color: var(--muted); display: flex; align-items: center; transition: color 0.3s; }
        .policy i { margin-right: 5px; }
        .policy.valid { color: var(--sage); font-weight: 600; }

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
            
            <div class="password-policies">
                <div class="policy" id="len"><span class="dot">•</span> 8+ char,Uppercase,Number,Special char </div>
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
        
        const policies = {
            len: document.getElementById('len'),
            up: document.getElementById('up'),
            num: document.getElementById('num'),
            spec: document.getElementById('spec')
        };

        pass.addEventListener('input', () => {
            const val = pass.value;
            
            // Validate Length
            val.length >= 8 ? policies.len.classList.add('valid') : policies.len.classList.remove('valid');
            // Validate Uppercase
            /[A-Z]/.test(val) ? policies.up.classList.add('valid') : policies.up.classList.remove('valid');
            // Validate Number
            /[0-9]/.test(val) ? policies.num.classList.add('valid') : policies.num.classList.remove('valid');
            // Validate Special Char
            /[^A-Za-z0-9]/.test(val) ? policies.spec.classList.add('valid') : policies.spec.classList.remove('valid');
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