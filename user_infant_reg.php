<?php
session_start();
include 'db_connect.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$childProfile = [];
$maternalProfile = [];
$prefillError = '';

$childStmt = $conn->prepare(
    "SELECT family_no, address, barangay, health_center, mother_name, father_name, contact
     FROM children
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 1"
);
if (!$childStmt) {
    error_log('Infant registration child prefill prepare failed: ' . $conn->error);
    $prefillError = 'Previous personal information could not be loaded. You can still complete the form manually.';
} else {
    $childStmt->bind_param('i', $user_id);
    if (!$childStmt->execute()) {
        error_log('Infant registration child prefill query failed: ' . $childStmt->error);
        $prefillError = 'Previous personal information could not be loaded. You can still complete the form manually.';
    } else {
        $childProfile = $childStmt->get_result()->fetch_assoc() ?: [];
    }
    $childStmt->close();
}

$maternalStmt = $conn->prepare(
    "SELECT family_serial, client_lname, client_fname, client_mi, client_ext,
            spouse_lname, spouse_fname, spouse_mi, spouse_ext,
            street, barangay, contact
     FROM maternal_registration
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 1"
);
if (!$maternalStmt) {
    error_log('Infant registration maternal prefill prepare failed: ' . $conn->error);
    $prefillError = 'Previous personal information could not be loaded. You can still complete the form manually.';
} else {
    $maternalStmt->bind_param('i', $user_id);
    if (!$maternalStmt->execute()) {
        error_log('Infant registration maternal prefill query failed: ' . $maternalStmt->error);
        $prefillError = 'Previous personal information could not be loaded. You can still complete the form manually.';
    } else {
        $maternalProfile = $maternalStmt->get_result()->fetch_assoc() ?: [];
    }
    $maternalStmt->close();
}

$firstNonEmpty = static function (...$values) {
    foreach ($values as $value) {
        if ($value !== null && trim((string) $value) !== '') {
            return $value;
        }
    }
    return '';
};
$maternalFatherName = trim(implode(' ', array_filter([
    $maternalProfile['spouse_fname'] ?? '',
    $maternalProfile['spouse_mi'] ?? '',
    $maternalProfile['spouse_lname'] ?? '',
    $maternalProfile['spouse_ext'] ?? '',
], static function ($value) {
    return trim((string) $value) !== '';
})));
$maternalMotherName = trim(implode(' ', array_filter([
    $maternalProfile['client_fname'] ?? '',
    $maternalProfile['client_mi'] ?? '',
    $maternalProfile['client_lname'] ?? '',
    $maternalProfile['client_ext'] ?? '',
], static function ($value) {
    return trim((string) $value) !== '';
})));
$infantDefaults = [
    'family_no' => $firstNonEmpty($childProfile['family_no'] ?? '', $maternalProfile['family_serial'] ?? ''),
    'address' => $firstNonEmpty($childProfile['address'] ?? '', $maternalProfile['street'] ?? ''),
    'barangay' => $firstNonEmpty($childProfile['barangay'] ?? '', $maternalProfile['barangay'] ?? '', 'Alawihao'),
    'mother_name' => $firstNonEmpty($childProfile['mother_name'] ?? '', $maternalMotherName),
    'father_name' => $firstNonEmpty($childProfile['father_name'] ?? '', $maternalFatherName),
    'contact' => $firstNonEmpty($childProfile['contact'] ?? '', $maternalProfile['contact'] ?? ''),
    'health_center' => $firstNonEmpty($childProfile['health_center'] ?? '', 'Alawihao Health Center'),
];
$infantDefaults['contact'] = preg_replace('/\D+/', '', (string) $infantDefaults['contact']);
if (preg_match('/^63\d{10}$/', $infantDefaults['contact'])) {
    $infantDefaults['contact'] = '0' . substr($infantDefaults['contact'], 2);
}
$hasInfantProfile = !empty($childProfile) || !empty($maternalProfile);
$escapeValue = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
                echo "<script>
                    alert('Baby enrolled successfully! Pending for admin review.');
                    window.location.href = 'user_dashboard.php';
                </script>";
                exit();
            } else {
                $message = "Error: " . $conn->error;
            }
            $stmt->close();
        } else {
            $message = "Database error: Could not prepare statement.";
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

        body.sidebar-closed #main {
            margin-left: 0 !important;
            width: 100% !important;
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
            padding: 9px 10px; 
            border: 1px solid #d7dfce;
            border-radius: 8px; 
            background: linear-gradient(180deg, #ffffff 0%, #f9fbf7 100%);
            box-sizing: border-box;
            font-family: inherit;
            font-size: 0.88rem;
            color: #1f2b1d;
            outline: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            transition: all 0.22s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .form-group input:hover, .form-group select:hover,
        .form-group input:focus, .form-group select:focus {
            border-color: var(--sage-green);
            background: linear-gradient(180deg, #ffffff 0%, #f3f8ea 100%);
            box-shadow: 0 0 0 3px rgba(113, 131, 85, 0.12), 0 5px 16px rgba(113, 131, 85, 0.12);
            transform: translateY(-1px);
            outline: none;
        }

        .form-group input:focus, .form-group select:focus {
            border-width: 1.6px;
        }

        .phone-row {
            display: grid;
            grid-template-columns: 160px minmax(0, 1fr);
            gap: 12px;
            width: 100%;
        }

        .phone-code-group,
        .phone-number-group {
            display: flex;
            flex-direction: column;
        }

        .phone-code-group label,
        .phone-number-group label {
            font-size: 0.7rem;
            color: #4b5563;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 0.3px;
        }

        .phone-country-select {
            width: 100%;
            padding: 9px 30px 9px 10px;
            border: 1px solid #d7dfce;
            border-radius: 8px;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbf7 100%);
            color: #1f2b1d;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M2 4l4 4 4-4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 10px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            transition: all 0.22s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .phone-country-select:hover,
        .phone-country-select:focus,
        .phone-input:hover,
        .phone-input:focus {
            border-color: var(--sage-green);
            box-shadow: 0 0 0 3px rgba(113, 131, 85, 0.12);
            outline: none;
        }

        .phone-input {
            width: 100%;
            min-width: 0;
            border: 1px solid #d7dfce;
            border-radius: 8px;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbf7 100%);
            padding: 9px 10px;
            font-size: 0.88rem;
            color: #1f2b1d;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            transition: all 0.22s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .field-hint {
            margin-top: 4px;
            color: #6b7280;
            font-size: 0.68rem;
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
        
        .success-msg { 
            color: #166534; 
            background: #f0fdf4;
            padding: 10px 15px;
            border-radius: 2px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            border-left: 3px solid var(--sage-green);
        }

        .saved-profile {
            margin-bottom: 24px;
            padding: 18px;
            border: 1px solid #d7dfce;
            border-radius: 10px;
            background: #f8fbf5;
        }

        .saved-profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .saved-profile-title {
            margin: 0;
            color: #35452b;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .profile-edit-btn {
            padding: 8px 14px;
            border: 1px solid var(--sage-green);
            border-radius: 6px;
            background: #fff;
            color: #52653b;
            font: inherit;
            font-weight: bold;
            cursor: pointer;
        }

        .profile-edit-btn:hover { background: #edf3e7; }
        .saved-profile-section + .saved-profile-section { margin-top: 18px; }

        .saved-profile-section h4 {
            margin: 0 0 10px;
            color: #52653b;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .saved-profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 10px;
        }

        .saved-profile-item {
            min-width: 0;
            padding: 10px 12px;
            border-radius: 7px;
            background: #fff;
        }

        .saved-profile-item span {
            display: block;
            margin-bottom: 4px;
            color: #687260;
            font-size: 0.68rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .saved-profile-item strong {
            display: block;
            color: #263321;
            font-size: 0.85rem;
            overflow-wrap: anywhere;
        }

        .profile-fields-collapsed { display: none; }

        @media (max-width: 992px) {
            .form-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php 
    // Palitan ang pagtawag para direktang mag-load ng user sidebar
    include('user_sidebar.php');
?>

<div id="main">
    <div class="form-card">
        <h2>Infant Registration Form</h2>
        
        <?php if($message) echo "<div class='success-msg'>$message</div>"; ?>
        <?php if ($prefillError !== ''): ?>
            <div class="success-msg"><?php echo $escapeValue($prefillError); ?></div>
        <?php endif; ?>

        <form method="POST">

            <?php if ($hasInfantProfile): ?>
                <section class="saved-profile" aria-labelledby="savedInfantProfileTitle">
                    <div class="saved-profile-header">
                        <h3 class="saved-profile-title" id="savedInfantProfileTitle">Saved child and family information</h3>
                        <button type="button" class="profile-edit-btn" id="toggleInfantProfileEdit" aria-expanded="false" aria-controls="infantProfileFields infantProfileAddressFields">Edit</button>
                    </div>
                    <div class="saved-profile-section">
                        <h4>Administrative Details</h4>
                        <div class="saved-profile-grid">
                            <div class="saved-profile-item"><span>Health center</span><strong><?php echo $escapeValue($infantDefaults['health_center']) ?: 'Not provided'; ?></strong></div>
                            <div class="saved-profile-item"><span>Family serial / no.</span><strong><?php echo $escapeValue($infantDefaults['family_no']) ?: 'Not provided'; ?></strong></div>
                        </div>
                    </div>
                    <div class="saved-profile-section">
                        <h4>Address Information</h4>
                        <div class="saved-profile-grid">
                            <div class="saved-profile-item"><span>Address</span><strong><?php echo $escapeValue($infantDefaults['address']) ?: 'Not provided'; ?></strong></div>
                            <div class="saved-profile-item"><span>Barangay</span><strong><?php echo $escapeValue($infantDefaults['barangay']) ?: 'Not provided'; ?></strong></div>
                        </div>
                    </div>
                    <div class="saved-profile-section">
                        <h4>Parent / Guardian Information</h4>
                        <div class="saved-profile-grid">
                            <div class="saved-profile-item"><span>Mother</span><strong><?php echo $escapeValue($infantDefaults['mother_name']) ?: 'Not provided'; ?></strong></div>
                            <div class="saved-profile-item"><span>Father</span><strong><?php echo $escapeValue($infantDefaults['father_name']) ?: 'Not provided'; ?></strong></div>
                            <div class="saved-profile-item"><span>Contact</span><strong><?php echo $escapeValue($infantDefaults['contact']) ?: 'Not provided'; ?></strong></div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <div id="infantProfileFields"<?php echo $hasInfantProfile ? ' class="profile-fields-collapsed" hidden' : ''; ?>>
            <div class="form-section-title">Administrative Details</div>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Health Center</label>
                    <input type="text" name="health_center" value="<?php echo $escapeValue($infantDefaults['health_center']); ?>" data-required="true"<?php echo $hasInfantProfile ? '' : ' required'; ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Family Serial / No.</label>
                    <input type="text" name="family_no" value="<?php echo $escapeValue($infantDefaults['family_no']); ?>" placeholder="Enter Family No." data-required="true"<?php echo $hasInfantProfile ? '' : ' required'; ?>>
                </div>
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

            <div id="infantProfileAddressFields"<?php echo $hasInfantProfile ? ' class="profile-fields-collapsed" hidden' : ''; ?>>
            <div class="form-section-title">Address Information</div>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Address (Number, Street, Purok)</label>
                    <input type="text" name="address" value="<?php echo $escapeValue($infantDefaults['address']); ?>" placeholder="Enter Address" data-required="true"<?php echo $hasInfantProfile ? '' : ' required'; ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Barangay</label>
                    <input type="text" name="barangay" value="<?php echo $escapeValue($infantDefaults['barangay']); ?>" data-required="true"<?php echo $hasInfantProfile ? '' : ' required'; ?>>
                </div>
            </div>

            <div class="form-section-title">Parent / Guardian Information</div>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Mother's Full Name</label>
                    <input type="text" name="mother_name" value="<?php echo $escapeValue($infantDefaults['mother_name']); ?>" placeholder="Full Name of Mother" data-required="true"<?php echo $hasInfantProfile ? '' : ' required'; ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Father's Full Name</label>
                    <input type="text" name="father_name" value="<?php echo $escapeValue($infantDefaults['father_name']); ?>" placeholder="Full Name of Father" data-required="true"<?php echo $hasInfantProfile ? '' : ' required'; ?>>
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
                            <input id="contactInput" class="phone-input" type="tel" name="contact" value="<?php echo $escapeValue($infantDefaults['contact']); ?>" data-required="true"<?php echo $hasInfantProfile ? '' : ' required'; ?> inputmode="numeric" maxlength="11" pattern="^09\d{9}$" placeholder="09123456789" title="Enter a valid Philippine mobile number">
                        </div>
                    </div>
                    <small class="field-hint" id="contactHint">Philippine format: 09XXXXXXXXX</small>
                </div>
            </div>
            </div>

            <button type="submit" class="enroll-btn">Confirm Registration</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('mySidebar') || document.querySelector('.sidebar');
        const mainContent = document.getElementById('main');
        
        if (sidebar && mainContent) {
            mainContent.classList.add('main-content-active');
        }

        const profileSections = [
            document.getElementById('infantProfileFields'),
            document.getElementById('infantProfileAddressFields')
        ].filter(Boolean);
        const profileEditButton = document.getElementById('toggleInfantProfileEdit');
        if (profileEditButton && profileSections.length) {
            const requiredProfileFields = profileSections
                .flatMap(function(section) {
                    return Array.from(section.querySelectorAll('[data-required="true"]'));
                });
            requiredProfileFields.forEach(function(field) {
                field.removeAttribute('required');
            });

            profileEditButton.addEventListener('click', function() {
                const isEditing = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', String(!isEditing));
                this.textContent = isEditing ? 'Edit' : 'Done editing';
                profileSections.forEach(function(section) {
                    section.hidden = isEditing;
                    section.classList.toggle('profile-fields-collapsed', isEditing);
                });
                requiredProfileFields.forEach(function(field) {
                    if (isEditing) {
                        field.removeAttribute('required');
                    } else {
                        field.setAttribute('required', '');
                    }
                });
            });
        }

        const countrySelect = document.getElementById('countrySelect');
        const contactInput = document.getElementById('contactInput');
        const contactHint = document.getElementById('contactHint');

        const countryRules = {
            PH: { maxLength: 11, pattern: /^09\d{9}$/, hint: 'Philippine format: 09XXXXXXXXX', placeholder: '09123456789' },
            US: { maxLength: 10, pattern: /^\d{10}$/, hint: 'US format: 10-digit number', placeholder: '5551234567' },
            UK: { maxLength: 11, pattern: /^\d{10,11}$/, hint: 'UK format: 10-11 digits', placeholder: '7123456789' },
            AU: { maxLength: 9, pattern: /^\d{9}$/, hint: 'Australia format: 9-digit number', placeholder: '412345678' }
        };

        function applyCountryRules() {
            const country = countrySelect.value;
            const rule = countryRules[country] || countryRules.PH;
            contactInput.maxLength = rule.maxLength;
            contactInput.placeholder = rule.placeholder;
            contactInput.setAttribute('pattern', rule.pattern.source);
            contactInput.setAttribute('title', 'Enter a valid ' + country + ' phone number.');
            contactHint.textContent = rule.hint;
            contactInput.value = contactInput.value.replace(/\D/g, '').slice(0, rule.maxLength);
        }

        countrySelect.addEventListener('change', applyCountryRules);

        contactInput.addEventListener('input', function() {
            const country = countrySelect.value;
            const rule = countryRules[country] || countryRules.PH;
            let digits = this.value.replace(/\D/g, '').slice(0, rule.maxLength);
            this.value = digits;
        });

        contactInput.addEventListener('blur', function() {
            const country = countrySelect.value;
            const rule = countryRules[country] || countryRules.PH;
            if (this.value && !rule.pattern.test(this.value)) {
                this.setCustomValidity('Please enter a valid ' + country + ' phone number.');
            } else {
                this.setCustomValidity('');
            }
        });

        applyCountryRules();
    });
</script>

<?php include 'admin/footer.php'; ?>

</body>
</html>