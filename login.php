<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php'; 

if (isset($_POST['login'])) {
    // 1. Linisin ang input para sa System ID o Email
    $login_input = mysqli_real_escape_string($conn, trim($_POST['login_id']));
    $pass        = trim($_POST['password']); 

    // 2. SQL: Hahanapin natin ang input sa 'email' OR 'generated_id'
    // Ito ang logic na mag-a-allow sa System ID login
    $query = "SELECT * FROM users WHERE (email = '$login_input' OR generated_id = '$login_input') LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        // 3. I-check kung tama ang password
        if ($pass == $row['password']) {
            
            // Check status (approved) muna maliban sa Super Admin
            if ($row['role'] !== 'Super Admin' && strtolower($row['status']) !== 'approved') {
                echo "<script>alert('Your account is still pending approval.'); window.location.href='login.php';</script>";
                exit();
            }

            // 4. I-set ang mga Sessions para ma-identify ang user sa ibang pages
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role']    = $row['role']; 
            $_SESSION['name']    = $row['first_name'];

            // 5. ROLE-BASED REDIRECTION (Dito sila pupunta sa tamang pinto)
            if ($row['role'] == 'Super Admin') {
                header("Location: super_admin_dashboard.php");
                exit();
            } 
            elseif ($row['role'] == 'Admin') {
                // Ito yung para sa mga Workers/Admins na may System ID
                header("Location: admin_dashboard.php");
                exit();
            } 
            elseif ($row['role'] == 'User') {
                // Para sa regular users/pasyente
                header("Location: user_dashboard.php");
                exit();
            } 
            else {
                // Fallback kung walang match na role
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
    <title>Login | Alawihao CMS</title>
    <style>
        /* Manatili ang minimalist design mo dito */
        :root { --sage: #8DAE74; --bg: #F8FAFC; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 380px; text-align: center; }
        .input-box { text-align: left; margin-bottom: 15px; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; }
        .btn { background: var(--sage); color: white; border: none; padding: 12px; width: 100%; border-radius: 10px; cursor: pointer; font-weight: 600; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Sign In</h2>
        <p style="color: #718096; font-size: 0.9rem;">Use your System ID (e.g. 2026-0001) or Email</p>
        <form method="POST">
            <div class="input-box">
                <input type="text" name="login_id" placeholder="System ID or Email" required autocomplete="off">
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="btn">Login</button>
        </form>
    </div>
</body>
</html>