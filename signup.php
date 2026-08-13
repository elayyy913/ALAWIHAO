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
    
    // Kung walang laman ang database, siya ang Super Admin at may generated ID.
    // Kapag may laman na, Regular User na siya at walang generated ID.
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
        $id_value = "NULL"; // Walang ID para sa mga regular users
    }

    $sql = "INSERT INTO users (generated_id, first_name, last_name, email, password, role, status, created_at) 
            VALUES ($id_value, '$fname', '$lname', '$email', '$pass', '$final_role', 'Approved', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('$success_message'); window.location='login.php';</script>";
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
    <!-- Idinagdag ang FontAwesome para sa Eye Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sage: #95AF7E; --dark-sage: #86A16D; --bg: #F7FAFC; --text: #2D3748; --border: #E2E8F0; --muted: #A0AEC0; }
        body { background-color: var(--bg); font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        
        .card { background-color: #FFFFFF; width: 420px; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        h2 { text-align: center; color: #1A202C; margin-bottom: 10px; font-weight: 700; font-size: 1.8rem; }
        p.subtitle { text-align: center; color: #718096; font-size: 0.9rem; margin-bottom: 25px; }
        
        label { display: block; font-size: 13px; font-weight: 700; margin: 15px 0 5px; color: #4A5568; text-transform: uppercase; letter-spacing: 0.5px; }
        
        input { width: 100%; padding: 14px; border-radius: 10px; border: 1px solid var(--border); box-sizing: border-box; font-size: 15px; background: #FAFAFA; transition: all 0.2s; }
        input:focus { border-color: var(--sage); outline: none; background: #FFF; box-shadow: 0 0 0 3px rgba(149, 175, 126, 0.1); }
        
        /* Container para sa password input at eye icon */
        .password-container {
            position: relative;
            width: 100%;
        }
        .password-container input {
            padding-right: 45px; /* Para hindi matakpan ng icon ang text */
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
                <input type="password" name="password_confirmation" id="confirmInput" required placeholder="Repeat your password">
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

        // Toggle function para sa Password visibility
        togglePass.addEventListener('click', function () {
            const type = pass.getAttribute('type') === 'password' ? 'text' : 'password';
            pass.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Toggle function para sa Confirm Password visibility
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

            // Ipakita ang success message kapag kompleto na
            if (checks.len && checks.up && checks.num && checks.spec) {
                successMsg.style.display = 'block';
            } else {
                successMsg.style.display = 'none';
            }
        });

        document.getElementById('regForm').onsubmit = function(e) {
            if (pass.value !== confirmPass.value) {
                e.preventDefault();
                alert("Passwords do not match!");
            }
        };
    </script>
</body>
</html>