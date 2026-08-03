<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Kunin ang mga general at common inputs mula sa form
    $mother_id = mysqli_real_escape_string($conn, $_POST['mother_id']);
    $trimester = mysqli_real_escape_string($conn, $_POST['trimester']);
    $checkup_date = mysqli_real_escape_string($conn, $_POST['checkup_date']);
    $weight_kg = mysqli_real_escape_string($conn, $_POST['weight_kg']);
    $height_cm = mysqli_real_escape_string($conn, $_POST['height_cm']);
    $gestational_age_weeks = mysqli_real_escape_string($conn, $_POST['gestational_age_weeks']);
    $bp = mysqli_real_escape_string($conn, $_POST['bp']);
    $nutritional_status = mysqli_real_escape_string($conn, $_POST['nutritional_status'] ?? '');
    
    $pagsusuri_kalagayan = mysqli_real_escape_string($conn, $_POST['pagsusuri_kalagayan']);
    $mga_payo = mysqli_real_escape_string($conn, $_POST['mga_payo']);
    $birthplan_changes = mysqli_real_escape_string($conn, $_POST['birthplan_changes']);
    $pagsusuri_ngipin = mysqli_real_escape_string($conn, $_POST['pagsusuri_ngipin']);
    $lab_test_done = mysqli_real_escape_string($conn, $_POST['lab_test_done']);
    
    $next_visit_date = mysqli_real_escape_string($conn, $_POST['next_visit_date']);
    $provider_name = mysqli_real_escape_string($conn, $_POST['provider_name']);
    $hospital_referral = mysqli_real_escape_string($conn, $_POST['hospital_referral']);

    // Initialize variables para sa mga trimesters
    $hemoglobin_count = null;
    $cbc = null;
    $urinalysis = null;
    $stool_exam = null;
    $acetic_acid_wash = null;
    $tetanus_vaccine_date = null;
    $treatments_given = "";
    $serbisyong_binigay = "";

    // Kunin ang mga partikular na data depende sa napiling Trimester
    if ($trimester == '1') {
        $hemoglobin_count = mysqli_real_escape_string($conn, $_POST['hemoglobin_count'] ?? '');
        $urinalysis = isset($_POST['urinalysis']) ? 'Done' : null;
        $cbc = mysqli_real_escape_string($conn, $_POST['cbc'] ?? '');
        $stool_exam = isset($_POST['stool_exam']) ? 'Done' : null;
        $acetic_acid_wash = isset($_POST['acetic_acid_wash']) ? 'Done' : null;
        
        // Tetanus vaccine date kung naka-check
        if (isset($_POST['tetanus_vaccine_done'])) {
            $tetanus_vaccine_date = mysqli_real_escape_string($conn, $_POST['tetanus_vaccine_date'] ?? null);
        }

        // Treatments array to string
        $treats = [];
        if (isset($_POST['treat_syphilis'])) $treats[] = 'Syphilis';
        if (isset($_POST['treat_arv'])) $treats[] = 'Antiretroviral (ARV)';
        if (isset($_POST['treat_bacteriuria'])) $treats[] = 'Bacteriuria';
        if (isset($_POST['treat_anemia'])) $treats[] = 'Anemia';
        $treatments_given = implode(', ', $treats);

        // Serbisyong binigay array to string
        $srvs = [];
        if (isset($_POST['srv_alcohol_tobacco'])) $srvs[] = 'Pag-iwas sa alcohol, tabacco, at illegal na droga';
        if (isset($_POST['srv_diet'])) $srvs[] = 'Pagpapayo tungkol sa tamang pagkain';
        if (isset($_POST['srv_safesex'])) $srvs[] = 'Pagpapayo sa safe sex';
        if (isset($_POST['srv_mosquito_net'])) $srvs[] = 'Paggamit ng mga insecticide-treated na kulambo';
        if (isset($_POST['srv_birthplan'])) $srvs[] = 'Birthplan';
        $serbisyong_binigay = implode(', ', $srvs);

    } else if ($trimester == '2') {
        $urinalysis = isset($_POST['urinalysis_tri2']) ? 'Done' : null;
        $cbc = mysqli_real_escape_string($conn, $_POST['cbc_tri2'] ?? '');
        
        $treats = [];
        if (isset($_POST['treat_deworming'])) $treats[] = 'Deworming';
        if (isset($_POST['treat_arv_tri2'])) $treats[] = 'Antiretroviral (ARV)';
        if (isset($_POST['treat_bacteriuria_tri2'])) $treats[] = 'Bacteriuria';
        if (isset($_POST['treat_anemia_tri2'])) $treats[] = 'Anemia';
        $treatments_given = implode(', ', $treats);

        $srvs = [];
        if (isset($_POST['srv_previous_discussion_tri2'])) $srvs[] = 'Pagpapaalala ng nakaraang tinalakay';
        $serbisyong_binigay = implode(', ', $srvs);

    } else if ($trimester == '3') {
        $urinalysis = isset($_POST['urinalysis_tri3']) ? 'Done' : null;
        $cbc = mysqli_real_escape_string($conn, $_POST['cbc_tri3'] ?? '');

        $treats = [];
        if (isset($_POST['treat_arv_tri3'])) $treats[] = 'Antiretroviral (ARV)';
        if (isset($_POST['treat_bacteriuria_tri3'])) $treats[] = 'Bacteriuria';
        if (isset($_POST['treat_anemia_tri3'])) $treats[] = 'Anemia';
        $treatments_given = implode(', ', $treats);

        $srvs = [];
        if (isset($_POST['srv_previous_discussion_tri3'])) $srvs[] = 'Pagpapaalala ng nakaraang tinalakay';
        if (isset($_POST['srv_postpartum'])) $srvs[] = 'Pagpapayo sa postpartum at postnatal care';
        if (isset($_POST['srv_spacing'])) $srvs[] = 'Pagpapayo sa pag agwat ng anak';
        if (isset($_POST['srv_tetanus_followup'])) $srvs[] = 'Pag follow up ng tetanus-containing vaccine';
        $serbisyong_binigay = implode(', ', $srvs);
    }

    // Escape string para safe ma-save sa database columns
    $treatments_given_esc = mysqli_real_escape_string($conn, $treatments_given);
    $serbisyong_binigay_esc = mysqli_real_escape_string($conn, $serbisyong_binigay);

    // SQL Insert Query papunta sa maternal_records table gamit ang tamang table columns
    $sql = "INSERT INTO maternal_records (
        mother_id, checkup_date, weight_kg, height_cm, gestational_age_weeks, bp, 
        nutritional_status, pagsusuri_kalagayan, mga_payo, birthplan_changes, 
        pagsusuri_ngipin, lab_test_done, hemoglobin_count, urinalysis, cbc, 
        stool_exam, acetic_acid_wash, tetanus_vaccine_date, treatments_given, 
        serbisyong_binigay, next_visit_date, provider_name, hospital_referral
    ) VALUES (
        '$mother_id', '$checkup_date', '$weight_kg', '$height_cm', '$gestational_age_weeks', '$bp', 
        '$nutritional_status', '$pagsusuri_kalagayan', '$mga_payo', '$birthplan_changes', 
        '$pagsusuri_ngipin', '$lab_test_done', " . ($hemoglobin_count ? "'$hemoglobin_count'" : "NULL") . ", 
        " . ($urinalysis ? "'$urinalysis'" : "NULL") . ", " . ($cbc ? "'$cbc'" : "NULL") . ", 
        " . ($stool_exam ? "'$stool_exam'" : "NULL") . ", " . ($acetic_acid_wash ? "'$acetic_acid_wash'" : "NULL") . ", 
        " . ($tetanus_vaccine_date ? "'$tetanus_vaccine_date'" : "NULL") . ", '$treatments_given_esc', 
        '$serbisyong_binigay_esc', " . ($next_visit_date ? "'$next_visit_date'" : "NULL") . ", '$provider_name', '$hospital_referral'
    )";

    if (mysqli_query($conn, $sql)) {
        header("Location: admin_maternal_hr.php?success=1");
        exit();
    } else {
        echo "Error sa pag-save ng record: " . mysqli_error($conn);
    }
}
?>