<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Kunin ang mga general at common inputs mula sa form
    $mother_id = (int) ($_POST['mother_id'] ?? 0);
    $schedule_id = (int) ($_POST['schedule_id'] ?? 0);
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

    $schedule_column = mysqli_query($conn, "SHOW COLUMNS FROM maternal_records LIKE 'schedule_id'");
    $has_schedule_column = $schedule_column && mysqli_num_rows($schedule_column) > 0;
    $schedule_transaction_started = false;

    if ($schedule_id > 0) {
        if (!$has_schedule_column) {
            $_SESSION['maternal_record_notice'] = [
                'type' => 'error',
                'message' => 'Run database_maternal_schedule_links.sql in phpMyAdmin before saving an appointment-linked health record.'
            ];
            header("Location: admin_maternal_hr.php");
            exit();
        }

        $conn->begin_transaction();
        $schedule_transaction_started = true;
        $schedule_stmt = $conn->prepare("SELECT id FROM schedules
            WHERE id = ? AND patient_id = ? AND category = 'Maternal'
              AND status IN ('Pending', 'Approved', 'Rescheduled')
            FOR UPDATE");
        if (!$schedule_stmt) {
            $conn->rollback();
            $_SESSION['maternal_record_notice'] = ['type' => 'error', 'message' => 'Could not validate the selected appointment: ' . $conn->error];
            header("Location: admin_maternal_hr.php");
            exit();
        }
        $schedule_stmt->bind_param("ii", $schedule_id, $mother_id);
        if (!$schedule_stmt->execute()) {
            $schedule_error = $schedule_stmt->error;
            $schedule_stmt->close();
            $conn->rollback();
            $_SESSION['maternal_record_notice'] = ['type' => 'error', 'message' => 'Could not validate the selected appointment: ' . $schedule_error];
            header("Location: admin_maternal_hr.php");
            exit();
        }
        $valid_schedule = $schedule_stmt->get_result()->fetch_assoc();
        $schedule_stmt->close();

        if (!$valid_schedule) {
            $conn->rollback();
            $_SESSION['maternal_record_notice'] = ['type' => 'error', 'message' => 'That appointment is no longer active for this patient. Refresh the schedule list and try again.'];
            header("Location: admin_maternal_hr.php");
            exit();
        }
    }

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
        mother_id, " . ($has_schedule_column ? "schedule_id, " : "") . "checkup_date, weight_kg, height_cm, gestational_age_weeks, bp,
        nutritional_status, pagsusuri_kalagayan, mga_payo, birthplan_changes, 
        pagsusuri_ngipin, lab_test_done, hemoglobin_count, urinalysis, cbc, 
        stool_exam, acetic_acid_wash, tetanus_vaccine_date, treatments_given, 
        serbisyong_binigay, next_visit_date, provider_name, hospital_referral
    ) VALUES (
        '$mother_id', " . ($has_schedule_column ? ($schedule_id > 0 ? "'$schedule_id'" : "NULL") . ", " : "") . "'$checkup_date', '$weight_kg', '$height_cm', '$gestational_age_weeks', '$bp',
        '$nutritional_status', '$pagsusuri_kalagayan', '$mga_payo', '$birthplan_changes', 
        '$pagsusuri_ngipin', '$lab_test_done', " . ($hemoglobin_count ? "'$hemoglobin_count'" : "NULL") . ", 
        " . ($urinalysis ? "'$urinalysis'" : "NULL") . ", " . ($cbc ? "'$cbc'" : "NULL") . ", 
        " . ($stool_exam ? "'$stool_exam'" : "NULL") . ", " . ($acetic_acid_wash ? "'$acetic_acid_wash'" : "NULL") . ", 
        " . ($tetanus_vaccine_date ? "'$tetanus_vaccine_date'" : "NULL") . ", '$treatments_given_esc', 
        '$serbisyong_binigay_esc', " . ($next_visit_date ? "'$next_visit_date'" : "NULL") . ", '$provider_name', '$hospital_referral'
    )";

    if (mysqli_query($conn, $sql)) {
        if ($schedule_transaction_started) {
            $complete_stmt = $conn->prepare("UPDATE schedules SET status = 'Completed'
                WHERE id = ? AND patient_id = ? AND category = 'Maternal'
                  AND status IN ('Pending', 'Approved', 'Rescheduled')");
            if (!$complete_stmt) {
                $save_error = $conn->error;
                $conn->rollback();
                $_SESSION['maternal_record_notice'] = ['type' => 'error', 'message' => 'The record was not saved because the appointment could not be completed: ' . $save_error];
                header("Location: admin_maternal_hr.php");
                exit();
            }
            $complete_stmt->bind_param("ii", $schedule_id, $mother_id);
            if (!$complete_stmt->execute() || $complete_stmt->affected_rows !== 1) {
                $save_error = $complete_stmt->error ?: 'The selected appointment status changed before it could be completed.';
                $complete_stmt->close();
                $conn->rollback();
                $_SESSION['maternal_record_notice'] = ['type' => 'error', 'message' => 'The record was not saved because the appointment could not be completed: ' . $save_error];
                header("Location: admin_maternal_hr.php");
                exit();
            }
            $complete_stmt->close();
            $conn->commit();
            logAdminAction($conn, "Completed maternal schedule ID {$schedule_id} while saving a health record for patient ID {$mother_id}.");
        }
        header("Location: admin_maternal_hr.php?success=1");
        exit();
    } else {
        $save_error = mysqli_error($conn);
        if ($schedule_transaction_started) {
            $conn->rollback();
        }
        $_SESSION['maternal_record_notice'] = [
            'type' => 'error',
            'message' => 'Error sa pag-save ng record: ' . $save_error
        ];
        header("Location: admin_maternal_hr.php");
        exit();
    }
}
?>