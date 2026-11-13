<?php
session_start();
include '../db_connect.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id']; 
    $baby_name = mysqli_real_escape_string($conn, $_POST['baby_name']);
    $gender = $_POST['gender'];
    $blood_type = $_POST['blood_type']; 
    $dob = $_POST['birth_date'];
    $weight = $_POST['birth_weight'];
    $height = $_POST['birth_height']; 
    $pob = mysqli_real_escape_string($conn, $_POST['place_of_birth']);
    
    $family_no = mysqli_real_escape_string($conn, $_POST['family_no']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $barangay = mysqli_real_escape_string($conn, $_POST['barangay']);
    $health_center = mysqli_real_escape_string($conn, $_POST['health_center']);
    
    $mother = mysqli_real_escape_string($conn, $_POST['mother_name']);
    $father = mysqli_real_escape_string($conn, $_POST['father_name']); 
    $contact = mysqli_real_escape_string($conn, $_POST['contact'] ?? '');
    
    $status = "Pending";
    $vaccines = "None";
    $administered_by = "";

    $sql = "INSERT INTO children (user_id, child_name, gender, blood_type, birth_date, weight_kg, height_cm, place_of_birth, family_no, address, barangay, health_center, mother_name, father_name, contact, status, vaccine_taken, administered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("issssdssssssssssss", $user_id, $baby_name, $gender, $blood_type, $dob, $weight, $height, $pob, $family_no, $address, $barangay, $health_center, $mother, $father, $contact, $status, $vaccines, $administered_by);
        if ($stmt->execute()) {
            $child_id = $conn->insert_id;
            logAdminAction($conn, "Registered child record (Child ID: {$child_id}).");
            $message = "Baby enrolled successfully! " . ($status === "Pending" ? "Pending for admin review." : "");
            $message_type = "success";
        } else {
            $message = "Error: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Database error: Could not prepare statement.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Baby | Alawihao Health</title>
    <style>
        :root {
            --sage-green: #718355;
            --light-beige: #fdfbf7;  
            --border-color: #d1d5db;
            --sidebar-width: 280px;
        }

        body { 
            background-color: var(--light-beige); 
            margin: 0; 
            font-family: 'Times New Roman', serif; 
            overflow-x: hidden;
            color: #111;
        }
        
        #main { 
            margin-left: 0; 
            padding: 20px 30px;
            transition: all 0.3s ease-in-out;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .main-content-active {
            margin-left: var(--sidebar-width) !important;
            width: calc(100% - var(--sidebar-width));
        }

        .form-card {
            background: #ffffff; 
            padding: 35px 45px; 
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            max-width: 1200px; 
            margin: 0 auto;
            border: 1px solid #e5e7eb;
        }

        .form-card h2 { 
            color: #111; 
            margin-bottom: 25px; 
            font-size: 1.1rem; 
            font-weight: bold;
            border-bottom: 1px solid #111;
            padding-bottom: 8px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-section-title {
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111;
            margin-top: 25px;
            margin-bottom: 12px;
            border-left: 3px solid var(--sage-green);
            padding-left: 8px;
        }

        .form-grid {
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 15px;
            margin-bottom: 12px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label { 
            color: #4b5563; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            margin-bottom: 5px; 
            letter-spacing: 0.3px;
        }
        
        .form-group input, .form-group select {
            width: 100%; 
            padding: 8px 10px; 
            border: 1px solid var(--border-color);
            border-radius: 2px; 
            background: #fff; 
            box-sizing: border-box;
            font-family: inherit;
            font-size: 0.95rem;
            color: #111;
            outline: none;
            transition: border 0.2s;
        }

        .form-group input:focus, .form-group select:focus { 
            border-color: var(--sage-green); 
        }

        .enroll-btn {
            background-color: var(--sage-green); 
            color: white; 
            border: none;
            padding: 12px 20px; 
            border-radius: 3px; 
            cursor: pointer;
            width: 100%; 
            font-size: 0.95rem; 
            font-weight: bold;
            transition: background 0.2s;
            margin-top: 25px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: inherit;
        }

        .enroll-btn:hover { background-color: #5c6c44; }
        
        .registration-toast {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 5000;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            width: min(440px, calc(100vw - 32px));
            padding: 18px 48px 18px 18px;
            border: 1px solid #dce9d5;
            border-left: 4px solid #5c7c43;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 14px 38px rgba(31, 45, 26, .16);
            color: #253321;
            animation: toast-in .28s ease-out;
        }
        .registration-toast.error {
            border-color: #f1d5d2;
            border-left-color: #b5473c;
        }
        .toast-mark {
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #edf5e8;
            color: #52763c;
        }
        .registration-toast.error .toast-mark {
            background: #fff0ee;
            color: #a63b32;
        }
        .toast-mark svg { width: 18px; height: 18px; }
        .toast-copy { min-width: 0; }
        .toast-title {
            margin: 0 0 4px;
            font-size: .94rem;
            font-weight: 700;
            color: #2d5016;
        }
        .registration-toast.error .toast-title { color: #9f342c; }
        .toast-message {
            margin: 0;
            color: #586356;
            font-size: .88rem;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }
        .toast-close {
            position: absolute;
            top: 10px;
            right: 12px;
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #687365;
            font-size: 1.25rem;
            cursor: pointer;
        }
        .toast-close:hover { background: #f3f5f1; }
        .registration-toast.is-hiding {
            animation: toast-out .2s ease-in forwards;
        }
        @keyframes toast-in {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes toast-out {
            to { opacity: 0; transform: translateY(-6px); }
        }
        @media (max-width: 560px) {
            .registration-toast { top: 12px; right: 12px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .registration-toast, .registration-toast.is-hiding {
                animation-duration: .01ms;
            }
        }

        @media (max-width: 992px) {
            .form-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }

        .phone-row {
            display: grid;
            grid-template-columns: 160px minmax(0, 1fr);
            gap: 10px;
            align-items: end;
        }
        .phone-code-group, .phone-number-group { display: flex; flex-direction: column; gap: 4px; }
        .phone-country-select {
            width: 100%; padding: 8px 10px;
            border: 1px solid #d1d5db; border-radius: 4px;
            background: #fff; font-size: 0.88rem; font-family: inherit; cursor: pointer;
        }
        .phone-country-select:hover, .phone-country-select:focus,
        .phone-input:hover, .phone-input:focus {
            outline: none;
            border-color: var(--sage-green);
            box-shadow: 0 0 0 3px rgba(113, 131, 85, 0.12);
        }
        .phone-input {
            width: 100%; padding: 8px 10px;
            border: 1px solid #d1d5db; border-radius: 4px;
            font-size: 0.88rem; font-family: inherit; box-sizing: border-box;
        }
        .field-hint { font-size: 0.75rem; color: #6b7280; margin-top: 4px; display: block; }
    </style>
</head>
<body>

<?php 
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'Super Admin') {
            include 'super_admin_sidebar.php'; 
        } elseif ($_SESSION['role'] === 'Admin') {
            include 'admin_sidebar.php'; 
        } else {
            include('../user_sidebar.php');
        }
    }
?>

<div id="main">
    <div class="form-card">
        <h2>Infant Registration Form</h2>
        
        <?php if ($message): ?>
            <div class="registration-toast <?php echo htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>" id="registrationToast" role="<?php echo $message_type === 'error' ? 'alert' : 'status'; ?>" aria-live="<?php echo $message_type === 'error' ? 'assertive' : 'polite'; ?>">
                <span class="toast-mark" aria-hidden="true">
                    <?php if ($message_type === 'error'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"></path><path d="M12 17h.01"></path><circle cx="12" cy="12" r="9"></circle></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"></path></svg>
                    <?php endif; ?>
                </span>
                <div class="toast-copy">
                    <p class="toast-title"><?php echo $message_type === 'error' ? 'Registration could not be completed' : 'Registration submitted'; ?></p>
                    <p class="toast-message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <button type="button" class="toast-close" aria-label="Dismiss notification">&times;</button>
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <div class="form-section-title">Administrative Details</div>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Health Center</label>
                    <input type="text" name="health_center" value="Alawihao Health Center" required>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Family Serial / No.</label>
                    <input type="text" name="family_no" placeholder="Enter Family No." required>
                </div>
            </div>

            <div class="form-section-title">Patient Personal Information</div>
            
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Full Name of Baby (Apelyido, Pangalan, M.I.)</label>
                    <input type="text" name="baby_name" placeholder="Enter Full Name" required>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">-- Select --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Blood Type</label>
                    <select name="blood_type" required>
                        <option value="N/A">Unknown / N/A</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Birth Date</label>
                    <input type="date" name="birth_date" required>
                </div>
                <div class="form-group">
                    <label>Birth Weight (kg)</label>
                    <input type="number" step="0.1" name="birth_weight" placeholder="e.g. 3.2" required>
                </div>
                <div class="form-group">
                    <label>Birth Height (cm)</label>
                    <input type="number" step="0.1" name="birth_height" placeholder="e.g. 50" required>
                </div>
                <div class="form-group">
                    <label>Place of Birth</label>
                    <input type="text" name="place_of_birth" placeholder="Hospital or Clinic" required>
                </div>
            </div>

            <div class="form-section-title">Address Information</div>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Address (Number, Street, Purok)</label>
                    <input type="text" name="address" placeholder="Enter Address" required>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Barangay</label>
                    <input type="text" name="barangay" value="Alawihao" required>
                </div>
            </div>

            <div class="form-section-title">Parent / Guardian Information</div>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Mother's Full Name</label>
                    <input type="text" name="mother_name" placeholder="Full Name of Mother" required>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Father's Full Name</label>
                    <input type="text" name="father_name" placeholder="Full Name of Father" required>
                </div>
            </div>

            <div class="form-grid" style="margin-top: 12px;">
                <div class="form-group" style="grid-column: span 2;">
                    <div class="phone-row">
                        <div class="phone-code-group">
                            <label>Country code</label>
                            <select id="countrySelect" class="phone-country-select" name="contact_country" aria-label="Choose country">
                                <option value="PH" selected>🇵🇭 +63</option>
                                <option value="US">🇺🇸 +1</option>
                                <option value="UK">🇬🇧 +44</option>
                                <option value="AU">🇦🇺 +61</option>
                            </select>
                        </div>
                        <div class="phone-number-group">
                            <label>Phone number</label>
                            <input id="contactInput" class="phone-input" type="tel" name="contact" inputmode="numeric" maxlength="11" pattern="^09\d{9}$" placeholder="09123456789" title="Enter a valid Philippine mobile number" required>
                        </div>
                    </div>
                    <small class="field-hint" id="contactHint">Philippine format: 09XXXXXXXXX</small>
                </div>
            </div>

            <button type="submit" class="enroll-btn">Confirm Registration</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('mainSidebar') || document.querySelector('.sidebar') || document.querySelector('.sidebar-container') || document.getElementById('mySidenav');
        const mainContent = document.getElementById('main');
        
        if (sidebar) {
            mainContent.classList.add('main-content-active');
        }

        // ── Phone number country-aware validation ──
        const countrySelect = document.getElementById('countrySelect');
        const contactInput  = document.getElementById('contactInput');
        const contactHint   = document.getElementById('contactHint');

        const countryRules = {
            PH: { maxLength: 11, pattern: /^09\d{9}$/, hint: 'Philippine format: 09XXXXXXXXX', placeholder: '09123456789' },
            US: { maxLength: 10, pattern: /^\d{10}$/, hint: 'US format: 10-digit number', placeholder: '5551234567' },
            UK: { maxLength: 11, pattern: /^\d{10,11}$/, hint: 'UK format: 10-11 digit number', placeholder: '07911123456' },
            AU: { maxLength: 9,  pattern: /^\d{9}$/,  hint: 'AU format: 9-digit number', placeholder: '412345678' },
        };

        function applyCountryRules() {
            const rule = countryRules[countrySelect.value] || countryRules.PH;
            contactInput.maxLength   = rule.maxLength;
            contactInput.placeholder = rule.placeholder;
            contactInput.setAttribute('pattern', rule.pattern.source);
            contactInput.setAttribute('title', 'Enter a valid phone number.');
            contactHint.textContent  = rule.hint;
            contactInput.value = contactInput.value.replace(/\D/g, '').slice(0, rule.maxLength);
        }

        countrySelect.addEventListener('change', applyCountryRules);
        contactInput.addEventListener('input', function () {
            const rule = countryRules[countrySelect.value] || countryRules.PH;
            this.value = this.value.replace(/\D/g, '').slice(0, rule.maxLength);
        });
        contactInput.addEventListener('blur', function () {
            const rule = countryRules[countrySelect.value] || countryRules.PH;
            if (this.value && !rule.pattern.test(this.value)) {
                this.setCustomValidity('Please enter a valid phone number.');
            } else {
                this.setCustomValidity('');
            }
        });
        applyCountryRules();

        const toast = document.getElementById('registrationToast');
        if (toast) {
            const dismissToast = function() {
                toast.classList.add('is-hiding');
                window.setTimeout(function() {
                    toast.remove();
                }, 220);
            };
            toast.querySelector('.toast-close').addEventListener('click', dismissToast);
            window.setTimeout(dismissToast, 6000);
        }
    });
</script>

</body>
</html>