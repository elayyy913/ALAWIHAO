<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Alawihao CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .card {
            background: var(--white);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            text-align: center;
        }

        .icon-box {
            width: 64px;
            height: 64px;
            background: rgba(141, 174, 116, 0.1);
            color: var(--sage);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 28px;
        }

        h2 { color: var(--text-main); font-size: 1.5rem; font-weight: 700; margin-bottom: 12px; }
        p { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 30px; }

        .form-group { text-align: left; margin-bottom: 20px; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 8px; margin-left: 4px; }
        
        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: #FAFAFA;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--sage);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(141, 174, 116, 0.1);
        }

        .btn-send {
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
            box-shadow: 0 4px 12px rgba(141, 174, 116, 0.15);
        }

        .btn-send:hover {
            background: var(--dark-sage);
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(141, 174, 116, 0.2);
        }

        .back-to-login {
            margin-top: 24px;
            display: inline-block;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .back-to-login:hover { color: var(--sage); }
    </style>
</head>
<body>

    <div class="card">
        <div class="icon-box">✉️</div>
        <h2>Forgot Password?</h2>
        <p>Enter your email address and we'll send you a 6-digit code to reset your password.</p>

        <form action="send_reset_code.php" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="example@gmail.com" required autocomplete="off">
            </div>

            <button type="submit" name="reset_request" class="btn-send">Send Reset Code</button>
        </form>

        <a href="login.php" class="back-to-login">← Back to Login</a>
    </div>

</body>
</html>