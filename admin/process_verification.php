<?php
session_start();
include '../db_connect.php';

// 1. SECURITY CHECK (Pwede ring i-adjust kung pati regular Admin ay papayagan dito)
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Super Admin' && $_SESSION['role'] !== 'Admin')) {
    header("Location: login.php");
    exit();
}

// Tukuyin kung saan ibabalik ang user pagkatapos mag-proseso (depende kung sino ang nag-click)
$redirect_page = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : (isset($_GET['redirect']) ? $_GET['redirect'] : 'super_admin_dashboard.php');

// --- HANDLE APPROVAL/REMOVE LOGIC ---

// Worker Account Approval (Super Admin lang dapat)
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
            header("Location: $redirect_page?msg=WorkerApprovedAndRecorded");
            exit();
        }
    }
}

// Infant Registration Approval (Naayos na base sa aktwal mong children table columns)
if (isset($_GET['approve_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['approve_id']);
    $fetch_data = mysqli_query($conn, "SELECT * FROM children WHERE id = '$id'");
    $baby = mysqli_fetch_assoc($fetch_data);

    if ($baby) {
        // Update status to Approved directly para sa record na ito, 
        // o kaya kung gusto mong i-update ang status column:
        $update_query = "UPDATE children SET status = 'Approved' WHERE id = '$id'";
        
        if (mysqli_query($conn, $update_query)) {
            header("Location: $redirect_page?msg=ApprovedAndRecorded");
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
    $smoking_sticks = mysqli_real_escape_string($conn, $_POST['smoking_sticks_per_day'] ?? '');
    $alcohol_amount = mysqli_real_escape_string($conn, $_POST['alcohol_amount_per_day'] ?? '');

    $obstetric_findings = isset($_POST['obstetric_findings']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['obstetric_findings'])) : '';
    $past_menstrual_period = mysqli_real_escape_string($conn, $_POST['past_menstrual_period'] ?? '');
    $char_menstrual_bleeding = mysqli_real_escape_string($conn, $_POST['character_menstrual_bleeding_pads'] ?? '');

    $fp_previous_method = mysqli_real_escape_string($conn, $_POST['fp_previous_method'] ?? '');
    $fp_duration = mysqli_real_escape_string($conn, $_POST['fp_duration'] ?? '');

    $vs_bp = mysqli_real_escape_string($conn, $_POST['vs_bp'] ?? '');
    $vs_weight = mysqli_real_escape_string($conn, $_POST['vs_weight'] ?? '');
    $vs_pulse = mysqli_real_escape_string($conn, $_POST['vs_pulse'] ?? '');
    $vs_height = mysqli_real_escape_string($conn, $_POST['vs_height'] ?? '');
    $vs_muac = mysqli_real_escape_string($conn, $_POST['vs_muac'] ?? '');
    $vs_bmi = mysqli_real_escape_string($conn, $_POST['vs_bmi'] ?? '');
    $vs_bmi_category = mysqli_real_escape_string($conn, $_POST['vs_bmi_category'] ?? '');

    $pe_conjunctiva = isset($_POST['conjunctiva_exam']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['conjunctiva_exam'])) : '';
    $pe_neck = isset($_POST['neck_exam']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['neck_exam'])) : '';
    $pe_breast = isset($_POST['breast_exam']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['breast_exam'])) : '';
    $pe_breast_mass_left = mysqli_real_escape_string($conn, $_POST['breast_mass_left'] ?? '');
    $pe_breast_mass_right = mysqli_real_escape_string($conn, $_POST['breast_mass_right'] ?? '');
    $pe_thorax = isset($_POST['thorax_exam']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['thorax_exam'])) : '';
    $pe_abdomen = isset($_POST['abdomen_exam']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['abdomen_exam'])) : '';
    $pe_vaginal = isset($_POST['vaginal_exam']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['vaginal_exam'])) : '';
    $pe_vaginal_others = mysqli_real_escape_string($conn, $_POST['vaginal_others_specify'] ?? '');
    $pe_extremities = isset($_POST['extremities_exam']) ? mysqli_real_escape_string($conn, implode(', ', $_POST['extremities_exam'])) : '';
    $tt_status = mysqli_real_escape_string($conn, $_POST['tt_status'] ?? '');

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
            heent_findings = '$heent', chest_heart = '$chest', abdomen_med = '$abdomen', genital_med = '$genital',
            extremities_med = '$extremities', skin_med = '$skin', family_history = '$fh', past_health_history = '$phh', 
            social_history = '$sh', smoking_sticks_per_day = '$smoking_sticks', alcohol_amount_per_day = '$alcohol_amount',
            gravida = '$gravida', para = '$para', full_term = '$full_term', premature = '$premature', abortion = '$abortion', living_children = '$living_children',
            past_lmp = '$past_lmp', bleeding_duration_days = '$bleeding_duration', last_delivery_attendant = '$last_attendant',
            obstetric_findings = '$obstetric_findings', past_menstrual_period = '$past_menstrual_period', character_menstrual_bleeding_pads = '$char_menstrual_bleeding',
            fp_previous_method = '$fp_previous_method', fp_duration = '$fp_duration', vs_bp = '$vs_bp', vs_weight = '$vs_weight', 
            vs_pulse = '$vs_pulse', vs_height = '$vs_height', vs_muac = '$vs_muac', vs_bmi = '$vs_bmi', vs_bmi_category = '$vs_bmi_category',
            pe_conjunctiva = '$pe_conjunctiva', pe_neck = '$pe_neck', pe_breast = '$pe_breast', pe_breast_mass_left = '$pe_breast_mass_left', 
            pe_breast_mass_right = '$pe_breast_mass_right', pe_thorax = '$pe_thorax', pe_abdomen = '$pe_abdomen', pe_vaginal = '$pe_vaginal', 
            pe_vaginal_others = '$pe_vaginal_others', pe_extremities = '$pe_extremities', tt_status = '$tt_status'
            WHERE patient_id = '$mother_id'";
    } else {
        $history_sql = "INSERT INTO pregnancy_history 
            (patient_id, heent_findings, chest_heart, abdomen_med, genital_med, extremities_med, skin_med, family_history, past_health_history, social_history, smoking_sticks_per_day, alcohol_amount_per_day, gravida, para, full_term, premature, abortion, living_children, past_lmp, bleeding_duration_days, last_delivery_attendant, obstetric_findings, past_menstrual_period, character_menstrual_bleeding_pads, fp_previous_method, fp_duration, vs_bp, vs_weight, vs_pulse, vs_height, vs_muac, vs_bmi, vs_bmi_category, pe_conjunctiva, pe_neck, pe_breast, pe_breast_mass_left, pe_breast_mass_right, pe_thorax, pe_abdomen, pe_vaginal, pe_vaginal_others, pe_extremities, tt_status) 
            VALUES ('$mother_id', '$heent', '$chest', '$abdomen', '$genital', '$extremities', '$skin', '$fh', '$phh', '$sh', '$smoking_sticks', '$alcohol_amount', '$gravida', '$para', '$full_term', '$premature', '$abortion', '$living_children', '$past_lmp', '$bleeding_duration', '$last_attendant', '$obstetric_findings', '$past_menstrual_period', '$char_menstrual_bleeding', '$fp_previous_method', '$fp_duration', '$vs_bp', '$vs_weight', '$vs_pulse', '$vs_height', '$vs_muac', '$vs_bmi', '$vs_bmi_category', '$pe_conjunctiva', '$pe_neck', '$pe_breast', '$pe_breast_mass_left', '$pe_breast_mass_right', '$pe_thorax', '$pe_abdomen', '$pe_vaginal', '$pe_vaginal_others', '$pe_extremities', '$tt_status')";
    }

    if (mysqli_query($conn, $history_sql)) {
        header("Location: $redirect_page?msg=MaternalApproved"); 
        exit();
    } else {
        echo "Database Error: " . mysqli_error($conn);
        exit();
    }
}

// --- HANDLE SCHEDULE ACTIONS ---
if (isset($_POST['mark_done_maternal'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    mysqli_query($conn, "UPDATE schedules SET status = 'Completed' WHERE id = '$schedule_id'");
    header("Location: $redirect_page?msg=MaternalMarkedDone");
    exit();
}

if (isset($_POST['reschedule_maternal'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    mysqli_query($conn, "UPDATE schedules SET schedule_date = '$new_date', status = 'Rescheduled' WHERE id = '$schedule_id'");
    header("Location: $redirect_page?msg=MaternalRescheduled");
    exit();
}

if (isset($_POST['mark_done_infant'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    mysqli_query($conn, "UPDATE schedules SET status = 'Completed' WHERE id = '$schedule_id'");
    header("Location: $redirect_page?msg=MaternalMarkedDone");
    exit();
}

if (isset($_POST['reschedule_infant'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    mysqli_query($conn, "UPDATE schedules SET schedule_date = '$new_date', status = 'Rescheduled' WHERE id = '$schedule_id'");
    header("Location: $redirect_page?msg=MaternalRescheduled");
    exit();
}

// --- REMOVE LOGIC ---
if (isset($_GET['remove_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['remove_id']);
    // Diretang ide-delete gamit ang tamang table id at child_name column
    mysqli_query($conn, "DELETE FROM children WHERE id = '$id'");
    header("Location: $redirect_page?msg=Removed"); 
    exit();
}

if (isset($_GET['remove_preg_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['remove_preg_id']);
    mysqli_query($conn, "DELETE FROM maternal_registration WHERE id = '$id'");
    header("Location: $redirect_page?msg=Removed"); 
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
    header("Location: $redirect_page?msg=Removed"); 
    exit();
}
?>