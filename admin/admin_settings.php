<?php
session_start();
include '../db_connect.php';

// Allow both Admin and Super Admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Super Admin'])) {
    header("Location: login.php");
    exit();
}

$user_id  = $_SESSION['user_id'] ?? 0;
$role     = $_SESSION['role'];
$message  = "";

// Determine which sidebar to include
$sidebar_file = ($role === 'Super Admin') ? 'super_admin_sidebar.php' : 'admin_sidebar.php';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password']     ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($current, $row['password'])) {
        $message = "<div class='alert alert-error'>Current password is incorrect.</div>";
    } elseif (strlen($new_pass) < 6) {
        $message = "<div class='alert alert-error'>New password must be at least 6 characters.</div>";
    } elseif ($new_pass !== $confirm) {
        $message = "<div class='alert alert-error'>New passwords do not match.</div>";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param("si", $hashed, $user_id);
        $upd->execute();
        $upd->close();
        $message = "<div class='alert alert-success'>Password updated successfully!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Alawihao Health Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sage:         #8DAE74;
            --dark-sage:    #6B8E55;
            --light-bg:     #F8FAFC;
            --card-bg:      #FFFFFF;
            --text-main:    #1E293B;
            --text-muted:   #64748B;
            --border-color: #E2E8F0;
            --sidebar-width: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--light-bg);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }

        /* ── Dark mode overrides ── */
        body.dark-mode {
            --light-bg:     #121212;
            --card-bg:      #1e1e1e;
            --text-main:    #e2e2e2;
            --text-muted:   #9e9e9e;
            --border-color: #2e2e2e;
            --sage:         #5aab38;
            --dark-sage:    #78c455;
            background: #121212;
            color: #e2e2e2;
        }
        body.dark-mode .settings-card {
            background: #1e1e1e;
            border-color: #2e2e2e;
            box-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }
        body.dark-mode .section-title { color: #a8d880; border-bottom-color: #2e2e2e; }
        body.dark-mode label { color: #9e9e9e; }
        body.dark-mode input[type="password"] {
            background: #2a2a2a;
            color: #e2e2e2;
            border-color: #444;
        }
        body.dark-mode input[type="password"]:focus { border-color: #5aab38; }
        body.dark-mode .alert-success { background: #1a3a22; color: #86efac; border-color: #166534; }
        body.dark-mode .alert-error   { background: #3a1a1a; color: #fca5a5; border-color: #991b1b; }
        body.dark-mode .dark-mode-label strong { color: #e2e2e2; }
        body.dark-mode .dark-mode-label span   { color: #9e9e9e; }

        #main {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 40px;
            overflow-y: auto;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: margin-left 0.3s;
        }

        /* ── Page header ── */
        .page-header {
            width: 100%;
            max-width: 620px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 14px;
            margin-bottom: 28px;
        }
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-sage);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ── Cards ── */
        .settings-container {
            width: 100%;
            max-width: 620px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .settings-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-top: 4px solid var(--sage);
            border-radius: 12px;
            padding: 28px 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--dark-sage);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Form elements ── */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 6px;
        }
        .form-group input[type="password"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            background: var(--light-bg);
            color: var(--text-main);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: var(--sage);
            box-shadow: 0 0 0 3px rgba(141,174,116,0.15);
        }

        /* ── Buttons ── */
        .btn-save {
            background: var(--dark-sage);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 6px;
        }
        .btn-save:hover { background: var(--sage); }

        /* ── Alerts ── */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-error   { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        /* ── Dark mode toggle row ── */
        .dark-mode-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 0;
        }
        .dark-mode-label {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .dark-mode-label strong { font-size: 0.95rem; color: var(--text-main); }
        .dark-mode-label span   { font-size: 0.82rem; color: var(--text-muted); }

        /* Toggle switch */
        .toggle-switch { position: relative; width: 52px; height: 28px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-track {
            position: absolute;
            inset: 0;
            background: #ccc;
            border-radius: 999px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .toggle-track::before {
            content: '';
            position: absolute;
            width: 20px; height: 20px;
            left: 4px; top: 4px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.25);
        }
        .toggle-switch input:checked + .toggle-track { background: var(--sage); }
        .toggle-switch input:checked + .toggle-track::before { transform: translateX(24px); }

        /* ── Role badge ── */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #EEF2FF;
            color: #3730A3;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 999px;
            margin-left: auto;
        }
        body.dark-mode .role-badge { background: #1e1e3a; color: #a5b4fc; }
    </style>
</head>
<body>

<?php include $sidebar_file; ?>

<div id="main">

    <div class="page-header">
        <h1 style="display:flex;align-items:center;gap:10px;">
            <i class="fa fa-gear"></i> Settings
            <span class="role-badge"><i class="fa fa-shield-halved"></i> <?= htmlspecialchars($role) ?></span>
        </h1>
    </div>

    <div class="settings-container">

        <?php if ($message): ?>
            <?= $message ?>
        <?php endif; ?>

        <!-- 1. APPEARANCE -->
        <div class="settings-card">
            <div class="section-title"><i class="fa fa-moon"></i> Appearance</div>
            <div class="dark-mode-row">
                <div class="dark-mode-label">
                    <strong>Dark Mode</strong>
                    <span>Switch to a darker color scheme to reduce eye strain.</span>
                </div>
                <label class="toggle-switch" title="Toggle Dark Mode">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="toggle-track"></span>
                </label>
            </div>
        </div>

        <!-- 2. SECURITY -->
        <div class="settings-card">
            <div class="section-title"><i class="fa fa-lock"></i> Change Password</div>
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password"
                           placeholder="Enter your current password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password"
                           placeholder="At least 6 characters" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Repeat new password" required>
                </div>
                <button type="submit" name="change_password" class="btn-save">
                    <i class="fa fa-floppy-disk"></i> Update Password
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    // ── Dark Mode ──────────────────────────────────────────────
    const DARK_KEY = 'alawihao_dark_mode';
    const dmToggle = document.getElementById('darkModeToggle');

    function applyDarkMode(isDark) {
        document.body.classList.toggle('dark-mode', isDark);
        if (dmToggle) dmToggle.checked = isDark;
    }

    // Apply saved preference (early-apply script in sidebar already did body class,
    // this syncs the toggle checkbox state)
    applyDarkMode(localStorage.getItem(DARK_KEY) === 'true');

    if (dmToggle) {
        dmToggle.addEventListener('change', () => {
            const enabled = dmToggle.checked;
            localStorage.setItem(DARK_KEY, enabled);
            applyDarkMode(enabled);
        });
    }
</script>
<?php include 'footer.php'; ?>
</body>
</html>
