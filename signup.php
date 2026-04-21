<?php
include 'db_connect.php';

if (isset($_POST['signup'])) {
    $fname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role  = mysqli_real_escape_string($conn, $_POST['role']);
    $pass  = $_POST['password']; 

    $check_email = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        echo "<script>alert('Error: Ang email na ito ay gamit na!'); window.history.back();</script>";
        exit();
    }

    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
    $count = mysqli_fetch_assoc($res)['total'];
    
    $final_role = ($count == 0) ? 'Super Admin' : $role;
    $status = ($final_role === 'Admin') ? 'Pending' : 'Approved';

    if ($final_role === 'Admin') {
        $entered_code = $_POST['admin_code'];
        $code_query = mysqli_query($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'hw_access_code'");
        $db_code_row = mysqli_fetch_assoc($code_query);
        $db_code = $db_code_row['setting_value'] ?? '';

        if ($entered_code !== $db_code) {
            echo "<script>alert('Invalid Health Worker Access Code!'); window.history.back();</script>";
            exit();
        }
    }

    $sql = "INSERT INTO users (first_name, last_name, email, password, role, status) 
            VALUES ('$fname', '$lname', '$email', '$pass', '$final_role', '$status')";

    if (mysqli_query($conn, $sql)) {
        if ($status === 'Pending') {
            echo "<script>alert('Success! Your account is pending for approval. Please wait for the Super Admin to approve your access.'); window.location='login.php';</script>";
        } else {
            echo "<script>alert('Success! Registered as $final_role'); window.location='login.php';</script>";
        }
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
        /* 1. Ginawang modern gray-ish ang background, imbis na dilaw. Sakto sa Zenbook aesthetics mo. */
        body { background-color: #F7FAFC; font-family: 'Inter', 'Helvetica', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        
        /* 2. CARD: Ginawang puti ang background, modern radius, at malinis na shadow. */
        .card { background-color: #FFFFFF; width: 420px; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: none; }
        
        h2 { text-align: center; color: #1A202C; margin-bottom: 25px; font-weight: 700; font-size: 1.8rem; }
        
        /* 3. LABELS: Kulay black-gray, minimalist. */
        label { display: block; font-size: 14px; font-weight: 700; margin: 15px 0 5px; color: #4A5568; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* 4. INPUTS/SELECT: Malinis na border, puting background. */
        input, select { width: 100%; padding: 14px; border-radius: 10px; border: 1px solid #E2E8F0; box-sizing: border-box; background: #FFFFFF; color: #2D3748; font-size: 15px; }
        input:focus, select:focus { border-color: #95AF7E; outline: none; box-shadow: 0 0 0 3px rgba(149, 175, 126, 0.2); }
        
        /* 5. ADMIN CODE GROUP: Light sage-green dashed box. */
        #adminCodeGroup { display: none; margin-top: 15px; padding: 20px; background: rgba(149, 175, 126, 0.05); border-radius: 10px; border: 1px dashed #95AF7E; }
        
        /* 6. BUTTON: Exact sage-green mula sa image mo, minimalist text. */
        .btn { width: 100%; padding: 16px; background: #95AF7E; color: #FFFFFF; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; margin-top: 30px; font-size: 16px; transition: 0.3s; }
        .btn:hover { background: #86A16D; box-shadow: 0 4px 12px rgba(149, 175, 126, 0.3); }
        
        /* 7. PROGRESS METER: Kulay sage-green din ang bar. */
        .meter { height: 6px; width: 100%; background: #E2E8F0; margin-top: 8px; border-radius: 3px; overflow: hidden; }
        .meter-bar { height: 100%; width: 0%; transition: 0.4s; }

        /* 8. FOOTER LOGIN LINK: Ang "Log in here" ay sage-green din, 'No account' ay gray-black. */
        .card-footer { text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #E2E8F0; }
        .card-footer p { font-size: 14px; color: #718096; margin: 0; }
        .card-footer a { color: #95AF7E; text-decoration: none; font-weight: bold; margin-left: 5px; }
        .card-footer a:hover { text-decoration: underline; color: #86A16D; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Create Account</h2>
        <form method="POST" id="regForm">
            <label>First Name</label>
            <input type="text" name="first_name" required>
            
            <label>Last Name</label>
            <input type="text" name="last_name" required>

            <label>Email Address</label>
            <input type="email" name="email" required>

            <label>Register as:</label>
            <select name="role" id="roleSelect" onchange="toggleCode()">
                <option value="User">Patient / Guardian</option>
                <option value="Admin">Health Center Worker</option>
            </select>

            <div id="adminCodeGroup">
                <label style="color: #4a5d32;">Health Worker Access Code</label>
                <input type="password" name="admin_code" id="adminCodeInput">
            </div>

            <label>Password</label>
            <input type="password" name="password" id="passInput" required>
            <div class="meter"><div id="meterBar" class="meter-bar"></div></div>
            <span id="strengthText" style="font-size: 12px;"></span>

            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" id="confirmInput" required>

            <button type="submit" name="signup" class="btn">Register</button>
        </form>

        <div class="card-footer">
            <p>Already have an account? <a href="login.php">Log in here</a></p>
        </div>
    </div>

    <script>
        function toggleCode() {
            const role = document.getElementById('roleSelect').value;
            const group = document.getElementById('adminCodeGroup');
            const input = document.getElementById('adminCodeInput');
            group.style.display = (role === 'Admin') ? 'block' : 'none';
            input.required = (role === 'Admin');
        }

        const pass = document.getElementById('passInput');
        const bar = document.getElementById('meterBar');
        const text = document.getElementById('strengthText');

        pass.addEventListener('input', () => {
            let val = pass.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const colors = ['#ff4d4d', '#ff4d4d', '#ffd966', '#ffd966', '#2ecc71'];
            const labels = ['', 'Weak', 'Medium', 'Medium', 'Strong'];
            
            bar.style.width = (val.length > 0) ? (score + 1) * 20 + "%" : "0%";
            bar.style.backgroundColor = colors[score];
            text.innerText = labels[score];
            text.style.color = colors[score];
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