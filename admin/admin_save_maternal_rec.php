<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kunin ang lahat ng data mula sa modal form nang may proper escaping laban sa SQL injection
    $mother_id             = mysqli_real_escape_string($conn, $_POST['mother_id'] ?? '');
    $checkup_date          = mysqli_real_escape_string($conn, $_POST['checkup_date'] ?? date('Y-m-d'));
    $weight_kg             = mysqli_real_escape_string($conn, $_POST['weight_kg'] ?? $_POST['weight'] ?? '');
    $height_cm             = mysqli_real_escape_string($conn, $_POST['height_cm'] ?? '');
    $gestational_age_weeks = mysqli_real_escape_string($conn, $_POST['gestational_age_weeks'] ?? '');
    $bp                    = mysqli_real_escape_string($conn, $_POST['bp'] ?? '');
    $temperature           = mysqli_real_escape_string($conn, $_POST['temperature'] ?? $_POST['temp'] ?? '');
    $nutritional_status    = mysqli_real_escape_string($conn, $_POST['nutritional_status'] ?? '');
    
    $pagsusuri_kalagayan   = mysqli_real_escape_string($conn, $_POST['pagsusuri_kalagayan'] ?? '');
    $mga_payo              = mysqli_real_escape_string($conn, $_POST['mga_payo'] ?? '');
    $birthplan_changes     = mysqli_real_escape_string($conn, $_POST['birthplan_changes'] ?? '');
    $pagsusuri_ngipin      = mysqli_real_escape_string($conn, $_POST['pagsusuri_ngipin'] ?? '');
    $lab_test_done         = mysqli_real_escape_string($conn, $_POST['lab_test_done'] ?? '');
    $cbc                   = mysqli_real_escape_string($conn, $_POST['cbc'] ?? '');
    
    // Checkboxes / Toggles (1 kung naka-check, 0 kung hindi)
    $urinalysis            = isset($_POST['urinalysis_done']) ? '1' : '0';
    $treat_bacteriuria     = isset($_POST['treat_bacteriuria']) ? '1' : '0';
    $blood_rh_group_done   = isset($_POST['blood_rh_group_done']) ? '1' : '0';
    
    $srv_previous_discussion = isset($_POST['srv_previous_discussion']) ? '1' : '0';
    $srv_postpartum        = isset($_POST['srv_postpartum']) ? '1' : '0';
    $srv_spacing           = isset($_POST['srv_spacing']) ? '1' : '0';
    $srv_tetanus_followup  = isset($_POST['srv_tetanus_followup']) ? '1' : '0';

    $next_visit_date       = mysqli_real_escape_string($conn, $_POST['next_visit_date'] ?? '');
    $provider_name         = mysqli_real_escape_string($conn, $_POST['provider_name'] ?? '');
    $hospital_referral     = mysqli_real_escape_string($conn, $_POST['hospital_referral'] ?? '');

    if (empty($mother_id)) {
        echo "<script>alert('Error: Missing Mother ID!'); window.history.back();</script>";
        exit();
    }

    // I-insert ang buong detalye sa maternal_records table na tugma sa mga kolum ng database mo
    $query = "INSERT INTO maternal_records (
                mother_id, checkup_date, weight_kg, height_cm, gestational_age_weeks, 
                bp, temperature, nutritional_status, pagsusuri_kalagayan, mga_payo, 
                birthplan_changes, pagsusuri_ngipin, lab_test_done, cbc, urinalysis, 
                treat_bacteriuria, blood_rh_group_done, srv_previous_discussion, 
                srv_postpartum, srv_spacing, srv_tetanus_followup, next_visit_date, 
                provider_name, hospital_referral
              ) VALUES (
                '$mother_id', '$checkup_date', '$weight_kg', '$height_cm', '$gestational_age_weeks', 
                '$bp', '$temperature', '$nutritional_status', '$pagsusuri_kalagayan', '$mga_payo', 
                '$birthplan_changes', '$pagsusuri_ngipin', '$lab_test_done', '$cbc', '$urinalysis', 
                '$treat_bacteriuria', '$blood_rh_group_done', '$srv_previous_discussion', 
                '$srv_postpartum', '$srv_spacing', '$srv_tetanus_followup', '$next_visit_date', 
                '$provider_name', '$hospital_referral'
              )";

    if (mysqli_query($conn, $query)) {
        header("Location: admin_maternal_hr.php?msg=success");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>