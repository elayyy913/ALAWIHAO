<?php
// DAPAT ITO LANG ANG session_start() SA PINAKATAAS. Burahin mo yung nasa line 54 dati.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db_connect.php'; 

if (isset($_POST['login'])) {
    // 1. Clean the input to prevent SQL Injection
    $login_id = mysqli_real_escape_string($conn, trim($_POST['login_id']));
    $pass     = trim($_POST['password']); 

    // 2. Check muna sa 'users' table (Super Admin / User)
    $query_user = "SELECT * FROM users WHERE (email = '$login_id' OR generated_id = '$login_id') LIMIT 1";
    $result_user = mysqli_query($conn, $query_user);

    if ($row = mysqli_fetch_assoc($result_user)) {
        if ($pass == $row['password']) {
            
            // Check status for standard users
            if ($row['role'] !== 'Super Admin' && strtolower($row['status']) !== 'approved') {
                echo "<script>alert('Your account is still pending approval.'); window.location.href='login.php';</script>";
                exit();
            }

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role']    = $row['role'];
            $_SESSION['name']    = $row['first_name'];

            // Update activity
            $uid = $row['id'];
            mysqli_query($conn, "UPDATE users SET last_activity = NOW() WHERE id = '$uid'");

            header("Location: admin_dashboard.php");
            exit();
        }
    } 

    // 3. Check sa 'health_workers' table (Admin / Workers)
    // Dito natin gagamitin ang worker_id gaya ng nasa screenshot mo
    $query_worker = "SELECT * FROM health_workers WHERE (email = '$login_id' OR generated_id = '$login_id') LIMIT 1";
    $result_worker = mysqli_query($conn, $query_worker);

    if ($worker = mysqli_fetch_assoc($result_worker)) {
        if ($pass == $worker['password']) {
            
            if (strtolower($worker['status']) == 'pending') {
                echo "<script>alert('Your worker account is still pending.'); window.location.href='login.php';</script>";
                exit();
            }

            // SUCCESS: Gamitin ang worker_id column
            $_SESSION['user_id'] = $worker['worker_id']; 
            $_SESSION['role']    = 'Admin'; 
            $_SESSION['name']    = $worker['first_name'];

            $wid = $worker['worker_id'];
            mysqli_query($conn, "UPDATE health_workers SET last_activity = NOW() WHERE worker_id = '$wid'");

            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // Kung hindi nag-match sa kahit anong table
    echo "<script>alert('Invalid ID/Email or Password.'); window.location.href='login.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Alawihao Health Center</title>
    <style>
        :root {
            --sage: #8DAE74;
            --dark-sage: #6B8E55;
            --bg: #F8FAFC;
            --white: #FFFFFF;
            --text: #2D3748;
            --border: #E2E8F0;
        }

        body { 
            background-color: var(--bg); 
            font-family: 'Inter', -apple-system, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }

        .card { 
            background-color: var(--white); 
            width: 100%;
            max-width: 380px; 
            padding: 50px 40px; 
            border-radius: 24px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.04); 
            border: 1px solid var(--border);
            text-align: center; 
        }

        h2 { color: var(--text); margin-bottom: 8px; font-weight: 700; }
        p.desc { color: #718096; font-size: 0.9rem; margin-bottom: 35px; }

        .input-box { text-align: left; margin-bottom: 20px; }
        .input-box label { display: block; font-size: 0.7rem; font-weight: 800; color: #A0AEC0; margin-bottom: 8px; text-transform: uppercase; }

        input { 
            width: 100%; 
            padding: 14px; 
            border-radius: 12px; 
            border: 1px solid var(--border); 
            box-sizing: border-box; 
            font-size: 0.95rem;
            background: #FDFDFD;
            transition: all 0.3s ease;
        }

        input:focus { outline: none; border-color: var(--sage); box-shadow: 0 0 0 4px rgba(141, 174, 116, 0.1); background: #fff; }

        .btn { 
            width: 100%; 
            padding: 14px; 
            background: var(--sage); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-weight: 600; 
            font-size: 1rem;
            cursor: pointer; 
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn:hover { background: var(--dark-sage); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(107, 142, 85, 0.2); }

        .signup-link { font-size: 0.85rem; margin-top: 25px; color: #718096; }
        .signup-link a { color: var(--sage); text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Welcome Back</h2>
        <p class="desc">Sign in with your System ID or Email</p>
        
        <form method="POST" autocomplete="off">
            <div class="input-box">
                <label>System ID or Email</label>
                <input type="text" name="login_id" placeholder="e.g. 2026-0001" required autocomplete="off">
            </div>
            
            <div class="input-box">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required autocomplete="off">
            </div>
            
            <button type="submit" name="login" class="btn">Sign In</button>
        </form>
        
        <div class="signup-link">
            Need help? Contact the Super Admin.
        </div>
    </div>
</body>
</html>