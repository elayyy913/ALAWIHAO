<?php
session_start();
include('db_connect.php');
// Security: Check if logged in and is Super Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: admin/super_admin_dashboard.php?msg=Worker+successfully+registered");
    exit(); 
}

/** * LOGIC PARA SA AUTO-GENERATED ID **/
$currentYear = date("Y");
$query = "SELECT generated_id FROM health_workers WHERE generated_id LIKE '$currentYear-%' ORDER BY generated_id DESC LIMIT 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $lastID = $row['generated_id']; 
    $parts = explode('-', $lastID);
    $nextNumber = intval($parts[1]) + 1;
} else {
    $nextNumber = 1; 
}

$formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
$newGeneratedID = $currentYear . '-' . $formattedNumber;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Worker | Alawihao Center</title>
    <style>
        :root { --sage: #8DAE74; --dark-sage: #6B8E55; --beige: #F5F5DC; --text-gray: #718096; --error: #E53E3E; }
        body { font-family: 'Inter', sans-serif; background: #F9F9F4; display: flex; margin: 0; }
        .main-content { flex-grow: 1; padding: 40px; display: flex; justify-content: center; align-items: flex-start; }
        
        .form-card { 
            background: white; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 450px; 
        }

        h2 { color: var(--dark-sage); margin-top: 0; margin-bottom: 5px; }
        p.subtitle { color: var(--text-gray); font-size: 0.85rem; margin-bottom: 25px; }

        .id-container {
            background: #F1F5ED;
            border: 1px dashed var(--sage);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .id-label { display: block; font-size: 0.7rem; color: var(--dark-sage); font-weight: 700; text-transform: uppercase; }
        .id-value { font-family: 'Courier New', monospace; font-size: 1.2rem; font-weight: bold; color: var(--dark-sage); }

        .input-group { margin-bottom: 15px; position: relative; }
        label { display: block; font-size: 0.8rem; font-weight: 600; color: #4A5568; margin-bottom: 5px; }
        
        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #E2E8F0; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 0.9rem;
            background: #FCFCFC;
            transition: 0.3s;
        }
        input:focus { outline: none; border-color: var(--sage); background: white; }

        /* Style para sa error message */
        .match-error { 
            color: var(--error); 
            font-size: 0.75rem; 
            margin-top: 5px; 
            display: none; /* Hidden by default */
        }

        .btn-register { 
            background: var(--sage); 
            color: white; 
            border: none; 
            padding: 14px; 
            width: 100%; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            margin-top: 10px; 
            transition: 0.3s;
        }
        .btn-register:disabled { background: #CBD5E0; cursor: not-allowed; transform: none; }
        .btn-register:hover:not(:disabled) { background: var(--dark-sage); transform: translateY(-2px); }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #A0AEC0; text-decoration: none; font-size: 0.85rem; }
    </style>
</head>
<body>

<?php include(strtolower($_SESSION['role']) == 'super admin' ? 'admin/super_admin_sidebar.php' : 'admin/admin_sidebar.php'); ?>

<div class="main-content">
    <div class="form-card">
        <h2>Register Worker</h2>
        <p class="subtitle">Create a new account for health center personnel.</p>
        
        <form action="register_worker_process.php" method="POST" id="registrationForm">
            <div class="id-container">
                <span class="id-label">System Generated ID</span>
                <span class="id-value"><?php echo $newGeneratedID; ?></span>
                <input type="hidden" name="generated_id" value="<?php echo $newGeneratedID; ?>">
            </div>

            <div style="display: flex; gap: 10px;">
                <div class="input-group" style="flex: 1;">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="Juan" required>
                </div>
                <div class="input-group" style="flex: 1;">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Dela Cruz" required>
                </div>
            </div>

            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>

        <div class="input-group">
                    <label>Set Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" minlength="8" placeholder="Min. 8 characters" oncopy="return false" onpaste="return false" oncut="return false" required style="padding-right: 40px;">
                        <span id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #718096; font-size: 0.85rem; user-select: none;"></span>
                    </div>
                </div>

                <div class="input-group">
                    <label>Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat password" oncopy="return false" onpaste="return false" oncut="return false" required style="padding-right: 40px;">
                        <span id="toggleConfirmPassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #718096; font-size: 0.85rem; user-select: none;"></span>
                    </div>
                    <div id="passwordError" class="match-error">Passwords do not match!</div>
                </div>

            <div class="input-group">
                <label>Home Address</label>
                <input type="text" name="address" required>
            </div>

            <div class="input-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" id="contact_number" placeholder="09123456789" maxlength="11" pattern="09[0-9]{9}" required>
                <div id="contactError" class="match-error"></div>
            </div>

            <button type="submit" class="btn-register" id="submitBtn">Complete Registration</button>
        </form>
        
        <a href="admin_health_workers.php" class="back-link">← Cancel and Go Back</a>
    </div>
</div>

<script>
    const password = document.getElementById('password');
    const confirm_password = document.getElementById('confirm_password');
    const passwordErrorMsg = document.getElementById('passwordError');
    
    const contactInput = document.getElementById('contact_number');
    const contactErrorMsg = document.getElementById('contactError');
    const submitBtn = document.getElementById('submitBtn');

    function validateForm() {
        let isPasswordValid = true;
        let isContactValid = true;

        // Password matching check
        if (confirm_password.value !== "" && password.value !== confirm_password.value) {
            passwordErrorMsg.style.display = "block";
            confirm_password.style.borderColor = "#E53E3E";
            isPasswordValid = false;
        } else {
            passwordErrorMsg.style.display = "none";
            if(confirm_password.value !== "") confirm_password.style.borderColor = "#8DAE74";
        }

        // Contact number format check (must start with 09 and be exactly 11 digits)
        const contactVal = contactInput.value;
        if (contactVal.length > 0) {
            if (!contactVal.startsWith('09') || contactVal.length !== 11) {
                contactErrorMsg.style.display = "block";
                contactInput.style.borderColor = "#E53E3E";
                isContactValid = false;
            } else {
                contactErrorMsg.style.display = "none";
                contactInput.style.borderColor = "#8DAE74";
            }
        } else {
            contactErrorMsg.style.display = "none";
            isContactValid = false;
        }

        // Enable or disable submit button based on both validations
        if (isPasswordValid && isContactValid && password.value === confirm_password.value && confirm_password.value !== "") {
            submitBtn.disabled = false;
        } else {
            // Keep disabled if requirements aren't fully met
        }
    }

    // Auto-filter contact input to numbers only and restrict length
    contactInput.addEventListener('input', function (e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        validateForm();
    });

    password.addEventListener('keyup', validateForm);
    confirm_password.addEventListener('keyup', validateForm);
</script>

</body>
</html>