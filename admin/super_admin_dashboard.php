<?php
session_start();
include '../db_connect.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: login.php");
    exit();
}

// Para sa notification badge sa sidebar
$pending_workers_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0;

// --- HANDLE APPROVAL/REMOVE LOGIC ---

// Worker Account Approval
if (isset($_GET['approve_worker_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_worker_id']);
    $user_data = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id' AND role = 'Admin'");
    $worker = mysqli_fetch_assoc($user_data);

    if ($worker) {
        $fname = $worker['first_name']; $lname = $worker['last_name'];
        $email = $worker['email']; $pass = $worker['password'];

        $insert_worker = "INSERT INTO health_workers (first_name, last_name, email, password, status, created_at) 
                         VALUES ('$fname', '$lname', '$email', '$pass', 'Approved', NOW())";
        
        if (mysqli_query($conn, $insert_worker)) {
            mysqli_query($conn, "UPDATE users SET status = 'Approved' WHERE id = '$id'");
            header("Location: super_admin_dashboard.php?msg=WorkerApprovedAndRecorded");
            exit();
        }
    }
}

// Infant Registration Approval
if (isset($_GET['approve_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_id']);
    $fetch_data = mysqli_query($conn, "SELECT * FROM children WHERE id = '$id'");
    $baby = mysqli_fetch_assoc($fetch_data);

    if ($baby) {
        $baby_name = $baby['child_name']; $birth_date = $baby['birth_date'];
        $gender = $baby['gender']; $weight = $baby['weight'];
        $parent_name = $baby['mother_name']; $parent_id = $baby['user_id']; 
        $address = $baby['place_of_birth'];

        $insert_query = "INSERT INTO infant_records (baby_name, birth_date, gender, weight_kg, parent_guardian, address, parent_id, created_at) 
                         VALUES ('$baby_name', '$birth_date', '$gender', '$weight', '$parent_name', '$address', '$parent_id', NOW())";
        
        if (mysqli_query($conn, $insert_query)) {
            mysqli_query($conn, "UPDATE children SET status = 'Approved' WHERE id = '$id'");
            header("Location: super_admin_dashboard.php?msg=ApprovedAndRecorded");
            exit();
        }
    }
}

// Maternal Registration Approval + Editable Personal Info + History saving
if (isset($_POST['submit_maternal_approval'])) {
    $mother_id = mysqli_real_escape_string($conn, $_POST['mother_id']);
    
    $client_lname = mysqli_real_escape_string($conn, $_POST['client_lname']);
    $client_fname = mysqli_real_escape_string($conn, $_POST['client_fname']);
    $client_mi = mysqli_real_escape_string($conn, $_POST['client_mi'] ?? $_POST['client_mname'] ?? '');
    $birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $blood_type = mysqli_real_escape_string($conn, $_POST['blood_type']);
    $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    mysqli_query($conn, "UPDATE maternal_registration SET 
        client_lname = '$client_lname', 
        client_fname = '$client_fname', 
        client_mi = '$client_mi', 
        dob = '$birthdate', 
        age = '$age', 
        blood_type = '$blood_type', 
        contact = '$contact_no', 
        street = '$address', 
        status = 'Approved' 
        WHERE id = '$mother_id'");

    $heent = isset($_POST['heent_findings']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['heent_findings'])) : '';
    $chest = isset($_POST['chest_heart']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['chest_heart'])) : '';
    $abdomen = isset($_POST['abdomen_med']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['abdomen_med'])) : '';
    $genital = isset($_POST['genital_med']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['genital_med'])) : '';
    $extremities = isset($_POST['extremities_med']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['extremities_med'])) : '';
    $skin = isset($_POST['skin_med']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['skin_med'])) : '';
    
    $fh = isset($_POST['family_history_details']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['family_history_details'])) : '';
    $phh = isset($_POST['past_health_details']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['past_health_details'])) : '';
    $sh = isset($_POST['social_history_details']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['social_history_details'])) : '';

    $gravida = mysqli_real_escape_string($conn, $_POST['gravida']);
    $para = mysqli_real_escape_string($conn, $_POST['para']);
    $full_term = mysqli_real_escape_string($conn, $_POST['full_term']);
    $premature = mysqli_real_escape_string($conn, $_POST['premature']);
    $abortion = mysqli_real_escape_string($conn, $_POST['abortion']);
    $living_children = mysqli_real_escape_string($conn, $_POST['living_children']);

    $past_lmp = mysqli_real_escape_string($conn, $_POST['lmp'] ?? $_POST['past_lmp'] ?? '');
    $bleeding_duration = mysqli_real_escape_string($conn, $_POST['duration_menstrual_bleeding'] ?? '');
    $last_attendant = mysqli_real_escape_string($conn, $_POST['birth_attendant'] ?? '');

    $check = mysqli_query($conn, "SELECT id FROM pregnancy_history WHERE patient_id = '$mother_id'");
    if (mysqli_num_rows($check) > 0) {
        $history_sql = "UPDATE pregnancy_history SET 
            heent_findings = '$heent', 
            chest_heart = '$chest', 
            abdomen_med = '$abdomen', 
            genital_med = '$genital',
            extremities_med = '$extremities', 
            skin_med = '$skin',
            family_history = '$fh', 
            past_health_history = '$phh', 
            social_history = '$sh',
            gravida = '$gravida', 
            para = '$para', 
            full_term = '$full_term', 
            premature = '$premature', 
            abortion = '$abortion', 
            living_children = '$living_children',
            past_lmp = '$past_lmp',
            bleeding_duration_days = '$bleeding_duration',
            last_delivery_attendant = '$last_attendant'
            WHERE patient_id = '$mother_id'";
    } else {
        $history_sql = "INSERT INTO pregnancy_history 
            (patient_id, heent_findings, chest_heart, abdomen_med, genital_med, extremities_med, skin_med, family_history, past_health_history, social_history, gravida, para, full_term, premature, abortion, living_children, past_lmp, bleeding_duration_days, last_delivery_attendant) 
            VALUES ('$mother_id', '$heent', '$chest', '$abdomen', '$genital', '$extremities', '$skin', '$fh', '$phh', '$sh', '$gravida', '$para', '$full_term', '$premature', '$abortion', '$living_children', '$past_lmp', '$bleeding_duration', '$last_attendant')";
    }

    if (mysqli_query($conn, $history_sql)) {
        header("Location: super_admin_dashboard.php?msg=MaternalApproved"); 
        exit();
    }
}

// --- REMOVE LOGIC ---
if (isset($_GET['remove_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['remove_id']);
    $get_name = mysqli_query($conn, "SELECT child_name FROM children WHERE id = '$id'");
    $child = mysqli_fetch_assoc($get_name);
    if ($child) {
        $c_name = mysqli_real_escape_string($conn, $child['child_name']);
        mysqli_query($conn, "DELETE FROM infant_records WHERE baby_name = '$c_name'");
    }
    mysqli_query($conn, "DELETE FROM children WHERE id = '$id'");
    header("Location: super_admin_dashboard.php?msg=Removed"); 
    exit();
}

if (isset($_GET['remove_preg_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['remove_preg_id']);
    mysqli_query($conn, "DELETE FROM maternal_registration WHERE id = '$id'");
    header("Location: super_admin_dashboard.php?msg=Removed"); 
    exit();
}

if (isset($_GET['remove_worker_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['remove_worker_id']);
    $get_email = mysqli_query($conn, "SELECT email FROM users WHERE id = '$id'");
    $user = mysqli_fetch_assoc($get_email);
    if ($user) {
        $email = mysqli_real_escape_string($conn, $user['email']);
        mysqli_query($conn, "DELETE FROM health_workers WHERE email = '$email'");
    }
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id'");
    header("Location: super_admin_dashboard.php?msg=Removed"); 
    exit();
}

// --- FETCH COUNTS ---
$total_newborns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM infant_records"))['t'] ?? 0;
$total_pregnant = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status='Approved'"))['t'] ?? 0;
$total_patients = $total_newborns + $total_pregnant;
$total_workers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Approved'"))['t'] ?? 0;

$total_pending = (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status='Pending'"))['t'] ?? 0) + 
                 (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status='Pending'"))['t'] ?? 0) +
                 (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0);

// --- FETCH LISTS ---
$pending_workers = mysqli_query($conn, "SELECT * FROM users WHERE role='Admin' AND status='Pending' ORDER BY created_at DESC");
$pending_list = mysqli_query($conn, "SELECT * FROM children WHERE status='Pending' ORDER BY created_at DESC");

$pending_preg_list = mysqli_query($conn, "SELECT *, 
    CONCAT(COALESCE(street,''), ' ', COALESCE(barangay,''), ' ', COALESCE(municipality,'')) AS computed_address,
    CONCAT(COALESCE(spouse_fname,''), ' ', COALESCE(spouse_lname,'')) AS computed_spouse 
    FROM maternal_registration WHERE status='Pending' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard | Alawihao</title>
    <style>
        :root { --sage: #8DAE74; --dark-sage: #5A6B47; --beige: #F9F9F4; --white: #FFFFFF; --text: #2D2D2D; --border: #E1E1D7; }
        body { font-family: 'Inter', sans-serif; margin: 0; background-color: var(--beige); color: var(--text); display: flex; }
        
        .main-content { flex-grow: 1; padding: 40px; box-sizing: border-box; width: 100%; margin-left: 280px; transition: margin-left 0.3s ease-in-out; }
        .page-header { border-bottom: 2px solid var(--border); padding-bottom: 15px; margin-bottom: 30px; }
        .page-header h1 { color: var(--dark-sage); font-size: 1.8rem; margin: 0; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 4px; border-top: 5px solid var(--sage); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .stat-card h4 { font-size: 0.7rem; color: #7f8c8d; margin-bottom: 10px; text-transform: uppercase; }
        .stat-card h2 { margin: 0; font-size: 1.6rem; }
        
        .table-container { background: var(--white); padding: 25px; border-radius: 4px; border: 1px solid var(--border); margin-bottom: 25px; width: 100%; box-sizing: border-box; }
        .table-container h3 { font-size: 1rem; color: var(--dark-sage); border-left: 4px solid var(--sage); padding-left: 10px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #F4F4ED; padding: 12px; font-size: 0.75rem; text-transform: uppercase; color: #666; }
        td { padding: 12px; border-bottom: 1px solid #F0F0F0; font-size: 0.85rem; }
        
        .btn-approve { background: var(--sage); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.7rem; display: inline-block; border: none; cursor: pointer; }
        .btn-reject { background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.7rem; margin-left: 5px; display: inline-block; border: none; cursor: pointer; }

        /* MODAL STYLES */
        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
        .modal-content { background: white; margin: 3% auto; padding: 30px; border-radius: 8px; width: 850px; position: relative; max-height: 90vh; overflow-y: auto; box-sizing: border-box; }
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 10px; }
        .form-group label { font-size: 0.75rem; font-weight: bold; color: #555; margin-bottom: 4px; text-transform: uppercase; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="date"], .form-group select, .form-group textarea { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85rem; font-family: inherit; box-sizing: border-box; background: white; }
        
        .patient-info-box { background: #F4F4ED; border: 1px solid var(--border); padding: 15px; border-radius: 6px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

        .checkbox-group { background: #FAFAFA; border: 1px solid #E1E1D7; padding: 10px; border-radius: 4px; max-height: 140px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
        .checkbox-label { font-size: 0.8rem; display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text); font-weight: normal; text-transform: none; }
        .checkbox-label input { width: 15px; height: 15px; cursor: pointer; }

        .section-tag { background: #F4F4ED; padding: 6px 12px; font-size: 0.8rem; font-weight: bold; color: var(--dark-sage); border-radius: 4px; margin: 15px 0 10px 0; border-left: 4px solid var(--sage); }
    </style>
</head>
<body>

<?php include 'super_admin_sidebar.php'; ?>

<div class="main-content" id="mainDashboard">
    <div class="page-header">
        <h1>Super Admin Dashboard</h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><h4>TOTAL PATIENTS</h4><h2><?php echo $total_patients; ?></h2></div>
        <div class="stat-card"><h4>APPROVED INFANTS</h4><h2><?php echo $total_newborns; ?></h2></div>
        <div class="stat-card"><h4>APPROVED MATERNAL</h4><h2><?php echo $total_pregnant; ?></h2></div>
        <div class="stat-card" style="border-top-color:#f39c12;"><h4>FOR APPROVAL</h4><h2 style="color:#f39c12;"><?php echo $total_pending; ?></h2></div>
        <div class="stat-card"><h4>STAFF WORKERS</h4><h2><?php echo $total_workers; ?></h2></div>
    </div>

    <!-- PENDING WORKERS TABLE -->
    <div class="table-container">
        <h3>Pending Staff Worker Accounts</h3>
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead>
            <tbody>
                <?php if (mysqli_num_rows($pending_workers) > 0): while($row = mysqli_fetch_assoc($pending_workers)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <a href="?approve_worker_id=<?php echo $row['id']; ?>" class="btn-approve">APPROVE</a>
                        <a href="?remove_worker_id=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('Reject this worker?')">REJECT</a>
                    </td>
                </tr>
                <?php endwhile; else: echo "<tr><td colspan='3' align='center'>No pending worker accounts.</td></tr>"; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- NEWBORN REGISTRATION TABLE -->
    <div class="table-container">
        <h3>Newborn Registration Approval</h3>
        <table>
            <thead><tr><th>Infant Name</th><th>Mother's Name</th><th>Action</th></tr></thead>
            <tbody>
                <?php if (mysqli_num_rows($pending_list) > 0): while($row = mysqli_fetch_assoc($pending_list)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['child_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['mother_name']); ?></td>
                    <td>
                        <button type="button" class="btn-approve" onclick='openNewbornModal(<?php echo json_encode($row); ?>)'>REVIEW</button>
                        <a href="?remove_id=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('Reject this?')">REJECT</a>
                    </td>
                </tr>
                <?php endwhile; else: echo "<tr><td colspan='3' align='center'>No pending newborn records.</td></tr>"; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- MATERNAL REGISTRATION TABLE -->
    <div class="table-container">
        <h3>Maternal Registration Approval</h3>
        <table>
            <thead><tr><th>Patient Name</th><th>Action</th></tr></thead>
            <tbody>
                <?php if (mysqli_num_rows($pending_preg_list) > 0): while($row = mysqli_fetch_assoc($pending_preg_list)): 
                    $row['display_name'] = trim($row['client_fname'] . " " . $row['client_lname']);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['display_name']); ?></td>
                    <td>
                        <button type="button" class="btn-approve" onclick='openVerifyModal(<?php echo json_encode($row); ?>)'>VERIFY & ENROLL</button>
                        <a href="?remove_preg_id=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('Reject this registration?')">REJECT</a>
                    </td>
                </tr>
                <?php endwhile; else: echo "<tr><td colspan='2' align='center'>No pending maternal registration.</td></tr>"; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- NEWBORN VERIFICATION MODAL (UPDATED WITH FULL FORM FIELDS) -->
<div id="newbornVerifyModal" class="modal">
    <div class="modal-content" style="width: 750px;">
        <h2 style="color:var(--dark-sage); margin-top:0; border-bottom:2px solid var(--border); padding-bottom:10px; font-size:1.2rem;">Infant Registration Review</h2>
        
        <div class="section-tag" style="margin-top:0;">ADMINISTRATIVE DETAILS</div>
        <div class="patient-info-box" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label>Health Center</label>
                <input type="text" id="nb_health_center" readonly style="background: #e9e9e1;" value="Alawihao Health Center">
            </div>
            <div class="form-group">
                <label>Family Serial / No.</label>
                <input type="text" id="nb_family_serial" readonly style="background: #e9e9e1;" placeholder="N/A">
            </div>
        </div>

        <div class="section-tag">PATIENT PERSONAL INFORMATION</div>
        <div class="patient-info-box" style="grid-template-columns: repeat(3, 1fr);">
            <div class="form-group" style="grid-column: span 3;">
                <label>Full Name of Baby</label>
                <input type="text" id="nb_child_name" readonly style="background: #e9e9e1;">
            </div>
            <div class="form-group">
                <label>Gender</label>
                <input type="text" id="nb_gender" readonly style="background: #e9e9e1;">
            </div>
            <div class="form-group">
                <label>Blood Type</label>
                <input type="text" id="nb_blood_type" readonly style="background: #e9e9e1;" value="Unknown / N/A">
            </div>
            <div class="form-group">
                <label>Birth Date</label>
                <input type="text" id="nb_birth_date" readonly style="background: #e9e9e1;">
            </div>
            <div class="form-group">
                <label>Birth Weight (kg)</label>
                <input type="text" id="nb_weight" readonly style="background: #e9e9e1;">
            </div>
            <div class="form-group">
                <label>Birth Height (cm)</label>
                <input type="text" id="nb_height" readonly style="background: #e9e9e1;" placeholder="N/A">
            </div>
            <div class="form-group">
                <label>Place of Birth</label>
                <input type="text" id="nb_place_of_birth" readonly style="background: #e9e9e1;">
            </div>
        </div>

        <div class="section-tag">ADDRESS INFORMATION</div>
        <div class="patient-info-box" style="grid-template-columns: 2fr 1fr;">
            <div class="form-group">
                <label>Address (Number, Street, Purok)</label>
                <input type="text" id="nb_address_details" readonly style="background: #e9e9e1;" placeholder="N/A">
            </div>
            <div class="form-group">
                <label>Barangay</label>
                <input type="text" id="nb_barangay" readonly style="background: #e9e9e1;" value="Alawihao">
            </div>
        </div>

        <div class="section-tag">PARENT / GUARDIAN INFORMATION</div>
        <div class="patient-info-box" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label>Mother's Full Name</label>
                <input type="text" id="nb_mother_name" readonly style="background: #e9e9e1;">
            </div>
            <div class="form-group">
                <label>Father's Full Name</label>
                <input type="text" id="nb_father_name" readonly style="background: #e9e9e1;" placeholder="N/A">
            </div>
        </div>

        <div class="section-tag">IMMUNIZATION RECORDS</div>
        <div id="nb_vaccines_container" class="checkbox-group" style="background: #F4F4ED; pointer-events: none;">
            <!-- Dynamically populated to show only checked vaccines -->
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <button type="button" class="btn-reject" style="background: #95a5a6; padding: 8px 15px; cursor:pointer;" onclick="closeNewbornModal()">Cancel</button>
            <a href="#" id="confirmNewbornBtn" class="btn-approve" style="padding: 8px 15px; text-align: center; text-decoration:none;">CONFIRM / ACCEPT</a>
        </div>
    </div>
</div>

<!-- MATERNAL CLINICAL VERIFICATION MODAL -->
<div id="verifyModal" class="modal">
    <div class="modal-content">
        <h2 style="color:var(--dark-sage); margin-top:0; border-bottom:2px solid var(--border); padding-bottom:10px; font-size:1.2rem;">Maternal Client Record & Clinical Verification</h2>
        
        <form method="POST">
            <input type="hidden" name="mother_id" id="modal_mother_id">
            
            <div class="section-tag" style="margin-top:0;">PATIENT PERSONAL INFORMATION (EDITABLE)</div>
            <div class="patient-info-box">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="client_lname" id="p_lname" required>
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="client_fname" id="p_fname" required>
                </div>
                <div class="form-group">
                    <label>Middle Initial / Name</label>
                    <input type="text" name="client_mname" id="p_mname">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="birthdate" id="p_birthdate">
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" id="p_age">
                </div>
                <div class="form-group">
                    <label>Blood Type</label>
                    <input type="text" name="blood_type" id="p_blood">
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_no" id="p_contact" placeholder="e.g. 09123456789">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Address</label>
                    <input type="text" name="address" id="p_address">
                </div>
                <div class="form-group" style="grid-column: span 3;">
                    <label>Spouse Name</label>
                    <input type="text" name="spouse_name" id="p_spouse">
                </div>
            </div>

            <div class="section-tag">I. OBSTETRICAL & MENSTRUAL HISTORY</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Gravida (G)</label>
                    <input type="number" name="gravida" value="1" min="0" required>
                </div>
                <div class="form-group">
                    <label>Para (P)</label>
                    <input type="number" name="para" value="0" min="0" required>
                </div>
                <div class="form-group">
                    <label>Full-term</label>
                    <input type="number" name="full_term" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Premature</label>
                    <input type="number" name="premature" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Abortion</label>
                    <input type="number" name="abortion" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Living Children</label>
                    <input type="number" name="living_children" value="0" min="0">
                </div>
            </div>

            <div class="form-grid-2" style="margin-top: 10px;">
                <div class="form-group" style="grid-column: span 2;">
                    <label>History of Previous Delivery</label>
                </div>
                <div class="form-group">
                    <label>Date of Last Delivery</label>
                    <input type="date" name="date_last_delivery" id="p_date_last_delivery">
                </div>
                <div class="form-group">
                    <label>Type of Last Delivery</label>
                    <select name="type_last_delivery" id="p_type_last_delivery">
                        <option value="">Select Type</option>
                        <option value="Normal Spontaneous Delivery (NSD)">Normal Spontaneous Delivery (NSD)</option>
                        <option value="Cesarean Section (CS)">Cesarean Section (CS)</option>
                        <option value="Forceps / Vacuum Extraction">Forceps / Vacuum Extraction</option>
                        <option value="Breech Delivery">Breech Delivery</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Birth Attendant in Last Delivery</label>
                    <input type="text" name="birth_attendant" id="p_birth_attendant" placeholder="e.g. Midwife, Physician, Hilot, etc.">
                </div>
            </div>

            <div class="form-grid-2" style="margin-top: 10px;">
                <div class="form-group">
                    <label>LMP (Last Menstrual Period)</label>
                    <input type="date" name="lmp" id="p_lmp">
                </div>
                <div class="form-group">
                    <label>EDC (Expected Date of Confinement)</label>
                    <input type="date" name="edc" id="p_edc">
                </div>
            </div>

            <div class="form-grid-2" style="margin-top: 10px;">
                <div class="form-group">
                    <label>Past Menstrual Period</label>
                    <input type="text" name="past_menstrual_period" placeholder="Past history details">
                </div>
                <div class="form-group">
                    <label>Duration of Menstrual Bleeding</label>
                    <input type="text" name="duration_menstrual_bleeding" placeholder="e.g. 3-5 days">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Character of Menstrual Bleeding</label>
                    <input type="text" name="character_menstrual_bleeding" placeholder="e.g. heavy">
                </div>
            </div>
            <div class="checkbox-group" style="margin-top: 10px; max-height: 80px;">
                <label class="checkbox-label"><input type="checkbox" name="history_ectopic" value="1"> History of Ectopic Pregnancy</label>
                <label class="checkbox-label"><input type="checkbox" name="history_hydatidiform" value="1"> Hydatidiform mole (within the last 12 months)</label>
            </div>

            <div class="section-tag">II. MEDICAL HISTORY (REVIEW OF SYSTEM)</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>HEENT Findings</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Normal"> Normal / No significant findings</label>
                        <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Epilepsy / Convulsions / Seizures"> Epilepsy / Convulsions / Seizures</label>
                        <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Severe headache / dizziness"> Severe headache / dizziness</label>
                        <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Visual disturbance / Blurring of Vision"> Visual disturbance / Blurring of Vision</label>
                        <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Yellowish Conjunctiva"> Yellowish Conjunctiva / Pale</label>
                        <label class="checkbox-label"><input type="checkbox" name="heent_findings[]" value="Enlarge Thyroid"> Enlarge Thyroid / Lymph Node</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Chest / Heart / Thorax</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label"><input type="checkbox" name="chest_heart[]" value="Normal"> Normal / No significant findings</label>
                        <label class="checkbox-label"><input type="checkbox" name="chest_heart[]" value="Severe chest pain"> Severe chest pain</label>
                        <label class="checkbox-label"><input type="checkbox" name="chest_heart[]" value="Shortness of breath"> Shortness of breath and easy fatigability</label>
                        <label class="checkbox-label"><input type="checkbox" name="chest_heart[]" value="Breast and axillary masses"> Breast and axillary masses / Masses</label>
                        <label class="checkbox-label"><input type="checkbox" name="chest_heart[]" value="Nipple discharges"> Nipple discharges</label>
                        <label class="checkbox-label"><input type="checkbox" name="chest_heart[]" value="Abnormal heart / breath sound"> Abnormal heart sound / breath sound</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Abdomen (Medical)</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label"><input type="checkbox" name="abdomen_med[]" value="Normal"> Normal</label>
                        <label class="checkbox-label"><input type="checkbox" name="abdomen_med[]" value="Mass in the abdomen"> Mass in the abdomen / Tenderness</label>
                        <label class="checkbox-label"><input type="checkbox" name="abdomen_med[]" value="Enlarge Liver"> Enlarge Liver</label>
                        <label class="checkbox-label"><input type="checkbox" name="abdomen_med[]" value="History of Gall Bladder disease"> History of Gall Bladder disease</label>
                        <label class="checkbox-label"><input type="checkbox" name="abdomen_med[]" value="History of Liver disease"> History of Liver disease</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Genital / Vaginal Examination</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label"><input type="checkbox" name="genital_med[]" value="Normal"> Normal</label>
                        <label class="checkbox-label"><input type="checkbox" name="genital_med[]" value="Vaginal discharge"> Vaginal discharge / Discharges</label>
                        <label class="checkbox-label"><input type="checkbox" name="genital_med[]" value="Intermenstrual bleeding"> Intermenstrual / Postcoital bleeding</label>
                        <label class="checkbox-label"><input type="checkbox" name="genital_med[]" value="Cysts / mass / warts"> Cysts / mass / warts / Scars / Lacerations</label>
                        <label class="checkbox-label"><input type="checkbox" name="genital_med[]" value="Mass in the uterus"> Mass in the uterus</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Extremities</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label"><input type="checkbox" name="extremities_med[]" value="Normal"> Normal</label>
                        <label class="checkbox-label"><input type="checkbox" name="extremities_med[]" value="Edema"> Edema</label>
                        <label class="checkbox-label"><input type="checkbox" name="extremities_med[]" value="Varicosities"> Varicosities</label>
                        <label class="checkbox-label"><input type="checkbox" name="extremities_med[]" value="Joint pains"> Joint pains / Pain on forced dorsiflexion</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Skin & Others</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label"><input type="checkbox" name="skin_med[]" value="Normal"> Normal</label>
                        <label class="checkbox-label"><input type="checkbox" name="skin_med[]" value="Skin rashes / Lesions"> Skin rashes / Lesions</label>
                        <label class="checkbox-label"><input type="checkbox" name="skin_med[]" value="Pallor"> Pallor (Paleness)</label>
                    </div>
                </div>
            </div>

            <div style="margin-top: 25px; text-align: right;">
                <button type="button" class="btn-reject" style="background: #95a5a6; padding: 10px 20px;" onclick="closeVerifyModal()">Cancel</button>
                <button type="submit" name="submit_maternal_approval" class="btn-approve" style="padding: 10px 20px; font-size: 0.85rem;">APPROVE & ENROLL</button>
            </div>
        </form>
    </div>
</div>

<script>
    // JS para sa Newborn Modal (Updated mapping)
    function openNewbornModal(data) {
        document.getElementById('nb_child_name').value = data.child_name || '';
        document.getElementById('nb_birth_date').value = data.birth_date || '';
        document.getElementById('nb_gender').value = data.gender || '';
        
        document.getElementById('nb_weight').value = data.weight_kg || data.weight || data.baby_weight || '';
        
        document.getElementById('nb_mother_name').value = data.mother_name || '';
        document.getElementById('nb_place_of_birth').value = data.place_of_birth || '';
        
        // Itugma ang family serial/no galing sa database column mo
        document.getElementById('nb_family_serial').value = data.family_no || data.family_serial || data.serial_no || '';
        
        document.getElementById('nb_blood_type').value = data.blood_type || 'Unknown / N/A';
        
        // Itugma ang height galing sa database column mo (height_cm)
        document.getElementById('nb_height').value = data.height_cm || data.height || data.birth_height || '';
        
        document.getElementById('nb_address_details').value = data.address || data.street || '';
        document.getElementById('nb_barangay').value = data.barangay || 'Alawihao';
        document.getElementById('nb_father_name').value = data.father_name || '';

        // Dynamic Immunization Records display (Sinasalo ang vaccine_taken o immunization_records)
        let vaccinesStr = data.vaccine_taken || data.immunization_records || data.vaccines || '';
        let vaccinesArray = vaccinesStr ? vaccinesStr.split(',').map(v => v.trim()) : [];
        let container = document.getElementById('nb_vaccines_container');
        container.innerHTML = '';

        if (vaccinesArray.length > 0 && vaccinesArray[0] !== '') {
            vaccinesArray.forEach(vac => {
                let label = document.createElement('label');
                label.className = 'checkbox-label';
                label.innerHTML = `<input type="checkbox" checked disabled> ${vac}`;
                container.appendChild(label);
            });
        } else {
            container.innerHTML = '<span style="font-size: 0.8rem; color: #777; font-style: italic;">No immunization records checked/provided.</span>';
        }

        document.getElementById('confirmNewbornBtn').href = "?approve_id=" + data.id;
        document.getElementById('newbornVerifyModal').style.display = 'block';
    }

    function closeNewbornModal() {
        document.getElementById('newbornVerifyModal').style.display = 'none';
    }

    // JS para sa Maternal Modal
    function openVerifyModal(data) {
        document.getElementById('modal_mother_id').value = data.id || '';
        document.getElementById('p_lname').value = data.client_lname || '';
        document.getElementById('p_fname').value = data.client_fname || '';
        document.getElementById('p_mname').value = data.client_mi || '';
        document.getElementById('p_birthdate').value = data.dob || '';
        document.getElementById('p_age').value = data.age || '';
        document.getElementById('p_blood').value = data.blood_type || '';
        document.getElementById('p_contact').value = data.contact || '';
        document.getElementById('p_address').value = data.street || '';
        document.getElementById('p_spouse').value = (data.spouse_fname || '') + ' ' + (data.spouse_lname || '');

        document.getElementById('verifyModal').style.display = 'block';
    }

    function closeVerifyModal() {
        document.getElementById('verifyModal').style.display = 'none';
    }

    // Isara ang modals kapag na-click sa labas ng box
    window.onclick = function(event) {
        var nbModal = document.getElementById('newbornVerifyModal');
        var matModal = document.getElementById('verifyModal');
        if (event.target == nbModal) {
            nbModal.style.display = "none";
        }
        if (event.target == matModal) {
            matModal.style.display = "none";
        }
    }
</script>

</body>
</html>