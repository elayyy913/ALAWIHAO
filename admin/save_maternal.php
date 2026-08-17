<?php
session_start();
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

    // 2. Kunin ang Data mula sa Form[cite: 4]
    $family_serial = $_POST['family_serial'] ?? '';
    $lname = $_POST['client_lname'] ?? '';
    $fname = $_POST['client_fname'] ?? '';
    $mi = $_POST['client_mi'] ?? '';
    $ext = $_POST['client_ext'] ?? '';
    $dob = $_POST['dob'] ?? NULL;
    $age = $_POST['age'] ?? 0;
    $blood = $_POST['blood_type'] ?? '';
    $lmp = $_POST['lmp'] ?? NULL;
    $educ = $_POST['highest_educ'] ?? '';
    $job = $_POST['occupation'] ?? '';

    $s_lname = $_POST['spouse_lname'] ?? '';
    $s_fname = $_POST['spouse_fname'] ?? '';
    $s_mi = $_POST['spouse_mi'] ?? '';
    $s_ext = $_POST['spouse_ext'] ?? '';
    $s_dob = $_POST['spouse_dob'] ?? NULL;
    $s_blood = $_POST['spouse_blood'] ?? '';

    $street = $_POST['street'] ?? '';
    $barangay = $_POST['barangay'] ?? 'Alawihao';
    $municipality = $_POST['municipality'] ?? 'Daet';
    $province = $_POST['province'] ?? 'Camarines Norte';

    $income = $_POST['income'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $phic_cat = $_POST['phic_cat'] ?? '';
    $philhealth = $_POST['philhealth'] ?? '';
    $living_children = $_POST['living_children'] ?? 0;
    $plan = $_POST['plan'] ?? '';
    $num_preg = $_POST['num_preg'] ?? 0;

    // 3. I-save sa main table: maternal_registration (Idinagdag ang user_id)
    $sql_main = "INSERT INTO maternal_registration (user_id, family_serial, client_lname, client_fname, client_mi, client_ext, dob, age, blood_type, lmp, highest_educ, occupation, spouse_lname, spouse_fname, spouse_mi, spouse_ext, spouse_dob, spouse_blood, street, barangay, municipality, province, income, contact, phic_cat, philhealth_no, living_children, birth_plan, num_preg) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_main);
    
    // Bind 29 parameters na ngayon (Unang 'i' ay para sa user_id)
    $stmt->bind_param("issssssissssssssssssssssssiis", 
        $user_id, $family_serial, $lname, $fname, $mi, $ext, $dob, $age, $blood, $lmp, $educ, $job, 
        $s_lname, $s_fname, $s_mi, $s_ext, $s_dob, $s_blood, 
        $street, $barangay, $municipality, $province, 
        $income, $contact, $phic_cat, $philhealth, $living_children, $plan, $num_preg
    );

    if ($stmt->execute()) {
        $patient_id = $conn->insert_id; 

        // 4. I-save ang Pregnancy History (Columns 1-6)[cite: 4]
        if (isset($_POST['h_date'])) {
            foreach ($_POST['h_date'] as $index => $date) {
                if (!empty($date) && $index < $num_preg) {
                    $p_no = $index + 1;
                    $p_type = $_POST['h_type'][$index] ?? '';
                    $p_outcome = $_POST['h_outcome'][$index] ?? '';
                    $p_count = $_POST['h_child_count'][$index] ?? '';
                    $p_qty = (!empty($_POST['h_multiple_no'][$index])) ? $_POST['h_multiple_no'][$index] : NULL;

                    $sql_history = "INSERT INTO pregnancy_history (patient_id, pregnancy_no, delivery_date, delivery_type, birth_outcome, child_count, multiple_qty) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt_hist = $conn->prepare($sql_history);
                    $stmt_hist->bind_param("iissssi", $patient_id, $p_no, $date, $p_type, $p_outcome, $p_count, $p_qty);
                    $stmt_hist->execute();
                }
            }
        }
        echo "<script>
                alert('Maternal record registered successfully! Pending for admin review.');
                window.location.href = '../user_dashboard.php';
            </script>";
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>