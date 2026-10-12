<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db_connect.php';

// Siguraduhing naka-login ang user at may session user_id
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Kunin ang user_id galing sa session
    $user_id = $_SESSION['user_id'];

    // 2. Kunin ang Data mula sa Form (Itakda ang status sa 'Pending' para dumaan sa admin approval)
    $status          = 'Pending'; 
    $family_serial   = $_POST['family_serial'] ?? '';
    $lname           = $_POST['client_lname'] ?? '';
    $fname           = $_POST['client_fname'] ?? '';
    $mi              = $_POST['client_mi'] ?? '';
    $ext             = $_POST['client_ext'] ?? '';
    $dob             = !empty($_POST['dob']) ? $_POST['dob'] : NULL;
    $age             = !empty($_POST['age']) ? $_POST['age'] : 0;
    $blood           = $_POST['blood_type'] ?? '';
    $lmp             = !empty($_POST['lmp']) ? $_POST['lmp'] : NULL;
    $educ            = $_POST['highest_educ'] ?? '';
    $job             = $_POST['occupation'] ?? '';

    $s_lname         = $_POST['spouse_lname'] ?? '';
    $s_fname         = $_POST['spouse_fname'] ?? '';
    $s_mi            = $_POST['spouse_mi'] ?? '';
    $s_ext           = $_POST['spouse_ext'] ?? '';
    $s_dob           = !empty($_POST['spouse_dob']) ? $_POST['spouse_dob'] : NULL;
    $s_blood         = $_POST['spouse_blood'] ?? '';

    $street          = $_POST['street'] ?? '';
    $barangay        = $_POST['barangay'] ?? 'Alawihao';
    $municipality    = $_POST['municipality'] ?? 'Daet';
    $province        = $_POST['province'] ?? 'Camarines Norte';

    $income          = !empty($_POST['income']) ? $_POST['income'] : 0;
    $contact         = $_POST['contact'] ?? '';
    $phic_cat        = $_POST['phic_cat'] ?? '';
    $philhealth      = $_POST['philhealth'] ?? '';
    $living_children = !empty($_POST['living_children']) ? $_POST['living_children'] : 0;
    $plan            = $_POST['plan'] ?? '';
    $num_preg        = !empty($_POST['num_preg']) ? $_POST['num_preg'] : 0;

    // 3. I-save sa main table: maternal_registration kasama ang status na 'Pending'
    $sql_main = "INSERT INTO maternal_registration (
        user_id, family_serial, client_lname, client_fname, client_mi, client_ext, 
        dob, age, blood_type, lmp, highest_educ, occupation, spouse_lname, spouse_fname, 
        spouse_mi, spouse_ext, spouse_dob, spouse_blood, street, barangay, municipality, 
        province, income, contact, phic_cat, philhealth_no, living_children, birth_plan, num_preg, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_main);
    
    if (!$stmt) {
        die("Prepare failed (Main): " . $conn->error);
    }

    // Bind 30 parameters na ngayon (idinagdag ang 's' para sa status)
    $stmt->bind_param("issssssissssssssssssssssssiiss", 
        $user_id, $family_serial, $lname, $fname, $mi, $ext, $dob, $age, $blood, $lmp, $educ, $job, 
        $s_lname, $s_fname, $s_mi, $s_ext, $s_dob, $s_blood, 
        $street, $barangay, $municipality, $province, 
        $income, $contact, $phic_cat, $philhealth, $living_children, $plan, $num_preg, $status
    );

    if ($stmt->execute()) {
        $patient_id = $conn->insert_id; 
        $stmt->close(); // Isara ang main statement bago mag-loop sa history
        logAdminAction($conn, "Registered maternal record (Patient ID: {$patient_id}).");

        // 4. I-save ang Pregnancy History
        if ($num_preg > 0 && isset($_POST['h_date'])) {
            $sql_history = "INSERT INTO pregnancy_history (patient_id, pregnancy_no, delivery_date, delivery_type, birth_outcome, child_count, multiple_qty) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_hist = $conn->prepare($sql_history);

            if ($stmt_hist) {
                foreach ($_POST['h_date'] as $index => $date) {
                    if (!empty($date) && $index < $num_preg) {
                        $p_no      = $index + 1;
                        $p_type    = $_POST['h_type'][$index] ?? '';
                        $p_outcome = $_POST['h_outcome'][$index] ?? '';
                        $p_count   = $_POST['h_child_count'][$index] ?? '';
                        $p_qty     = (!empty($_POST['h_multiple_no'][$index])) ? $_POST['h_multiple_no'][$index] : NULL;

                        $stmt_hist->bind_param("iissssi", $patient_id, $p_no, $date, $p_type, $p_outcome, $p_count, $p_qty);
                        $stmt_hist->execute();
                    }
                }
                $stmt_hist->close();
            }
        }

        // 5. Dynamic Redirection batay sa Role ng nag-login
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'Super Admin') {
                $redirect_page = 'super_admin_dashboard.php'; 
            } elseif ($_SESSION['role'] === 'Admin') {
                $redirect_page = 'admin_dashboard.php'; 
            } else {
                $redirect_page = '../user_maternal_records.php';
            }
        } else {
            $redirect_page = '../user_maternal_records.php';
        }

        $redirect_url = json_encode($redirect_page);
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Registration Complete | Alawihao Health Center</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f4f7f1;
            color: #253321;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .notice {
            width: min(100%, 440px);
            padding: 34px;
            border: 1px solid #e1e9db;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 18px 50px rgba(38, 58, 28, .12);
            text-align: center;
            animation: appear .3s ease-out;
        }
        .notice-mark {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #edf5e8;
            color: #52763c;
        }
        .notice-mark svg { width: 28px; height: 28px; }
        h1 { margin: 0 0 10px; font-size: 1.35rem; color: #2d5016; }
        p { margin: 0; color: #65715f; line-height: 1.6; }
        .continue-link {
            display: inline-flex;
            justify-content: center;
            margin-top: 24px;
            padding: 12px 20px;
            border-radius: 9px;
            background: #5a7c3a;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            transition: background .2s ease;
        }
        .continue-link:hover { background: #45652c; }
        @keyframes appear {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <main class='notice' role='status' aria-live='polite'>
        <div class='notice-mark' aria-hidden='true'>
            <svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                <path d='m5 12 4 4L19 6'></path>
            </svg>
        </div>
        <h1>Registration submitted</h1>
        <p>The maternal record was saved successfully and is pending review.</p>
        <a class='continue-link' href='" . htmlspecialchars($redirect_page, ENT_QUOTES, 'UTF-8') . "'>Continue to dashboard</a>
    </main>
    <script>
        window.setTimeout(function () {
            window.location.replace(" . $redirect_url . ");
        }, 2800);
    </script>
</body>
</html>";
        exit();
    } else {
        echo "Error sa pag-save: " . $stmt->error;
        $stmt->close();
    }

    $conn->close();
}
?>