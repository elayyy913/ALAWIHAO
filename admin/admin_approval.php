<?php
session_start();
include '../db_connect.php';

$message = "";

// 1. FETCH PENDING PREGNANCIES & NEWBORNS
$pregnancies = mysqli_query($conn, "SELECT *, CONCAT(first_name, ' ', last_name) AS display_name FROM maternal_registration WHERE status = 'Pending' OR status = '' OR status IS NULL");
$newborns = mysqli_query($conn, "SELECT i.*, CONCAT(m.first_name, ' ', m.last_name) as mother_name FROM infant_registration i LEFT JOIN maternal_registration m ON i.mother_id = m.id WHERE i.status = 'Pending' OR i.status = '' OR i.status IS NULL");

// 2. HANDLE MATERNAL CLINICAL VERIFICATION & APPROVAL
if (isset($_POST['submit_maternal_approval'])) {

    // TEMPORARY DEBUG - remove this after testing
    echo "<pre style='background:#eee; padding:20px; font-size:13px;'>";
    print_r($_POST);
    echo "</pre>";
    exit();

    $mother_id = mysqli_real_escape_string($conn, $_POST['mother_id']);

    if (empty($mother_id)) {
        echo "<script>alert('Error: Patient ID is missing!'); window.history.back();</script>";
        exit();
    }

    // ---- Obstetrical History ----
    $gravida = mysqli_real_escape_string($conn, $_POST['gravida'] ?? 0);
    $para = mysqli_real_escape_string($conn, $_POST['para'] ?? 0);
    $full_term = mysqli_real_escape_string($conn, $_POST['full_term'] ?? 0);
    $premature = mysqli_real_escape_string($conn, $_POST['premature'] ?? 0);
    $abortion = mysqli_real_escape_string($conn, $_POST['abortion'] ?? 0);
    $living_children = mysqli_real_escape_string($conn, $_POST['living_children'] ?? 0);

    // ---- Physical Examination & Vital Signs ----
    $physical_exam_vitals = mysqli_real_escape_string($conn, $_POST['physical_exam_vitals'] ?? '');
    $physical_exam_findings = mysqli_real_escape_string($conn, $_POST['physical_exam_findings'] ?? '');
    $tt_status = mysqli_real_escape_string($conn, $_POST['tt_status'] ?? '');
    $muac = mysqli_real_escape_string($conn, $_POST['muac'] ?? '');
    $bmi = mysqli_real_escape_string($conn, $_POST['bmi'] ?? '');

    // ---- System & Organ Findings ----
    $heent_findings = mysqli_real_escape_string($conn, $_POST['heent_findings'] ?? '');
    $neck = mysqli_real_escape_string($conn, $_POST['neck'] ?? '');
    $chest_heart = mysqli_real_escape_string($conn, $_POST['chest_heart'] ?? '');
    $abdomen_med = mysqli_real_escape_string($conn, $_POST['abdomen_med'] ?? '');
    $genital_med = mysqli_real_escape_string($conn, $_POST['genital_med'] ?? '');
    $extremities_med = mysqli_real_escape_string($conn, $_POST['extremities_med'] ?? '');
    $skin_med = mysqli_real_escape_string($conn, $_POST['skin_med'] ?? '');

    // ---- Family, Past Health & Social History ----
    $family_history = mysqli_real_escape_string($conn, $_POST['family_history'] ?? '');
    $past_health_history = mysqli_real_escape_string($conn, $_POST['past_health_history'] ?? '');
    $social_history = mysqli_real_escape_string($conn, $_POST['social_history'] ?? '');
    $ros = mysqli_real_escape_string($conn, $_POST['review_of_systems'] ?? '');

    // ---- Family Planning History ----
    $prev_fp_method = mysqli_real_escape_string($conn, $_POST['prev_fp_method'] ?? '');
    $fp_duration = mysqli_real_escape_string($conn, $_POST['fp_duration'] ?? '');

    // ---- Menstrual & Delivery Details ----
    $past_lmp_raw = trim($_POST['past_lmp'] ?? '');
    $past_lmp = $past_lmp_raw !== '' ? "'" . mysqli_real_escape_string($conn, $past_lmp_raw) . "'" : "NULL";
    $bleeding_duration_days = mysqli_real_escape_string($conn, $_POST['bleeding_duration_days'] ?? '');
    $bleeding_duration_days = $bleeding_duration_days !== '' ? "'$bleeding_duration_days'" : "NULL";
    $last_delivery_attendant = mysqli_real_escape_string($conn, $_POST['last_delivery_attendant'] ?? '');

    // ---- Breast Examination ----
    $breast_arr = isset($_POST['breast']) ? $_POST['breast'] : [];
    $breast_val = mysqli_real_escape_string($conn, implode(', ', $breast_arr));
    $breast_left_size = mysqli_real_escape_string($conn, $_POST['breast_left_size'] ?? '');
    $breast_right_size = mysqli_real_escape_string($conn, $_POST['breast_right_size'] ?? '');

    // Check kung umiiral na sa pregnancy_history (FIXED: table only has patient_id, walang mother_id column)
    $check = mysqli_query($conn, "SELECT id FROM pregnancy_history WHERE patient_id = '$mother_id'");

    if (mysqli_num_rows($check) > 0) {
        $history_sql = "UPDATE pregnancy_history SET 
            gravida = '$gravida', 
            para = '$para', 
            full_term = '$full_term', 
            premature = '$premature', 
            abortion = '$abortion', 
            living_children = '$living_children', 
            review_of_systems = '$ros', 
            family_history = '$family_history', 
            family_history_details = '$family_history', 
            past_health_history = '$past_health_history', 
            past_health_details = '$past_health_history', 
            social_history = '$social_history', 
            social_history_details = '$social_history',
            physical_exam_vitals = '$physical_exam_vitals',
            physical_exam_findings = '$physical_exam_findings',
            tt_status = '$tt_status',
            muac = '$muac',
            bmi = '$bmi',
            heent_findings = '$heent_findings',
            neck = '$neck',
            chest_heart = '$chest_heart',
            abdomen_med = '$abdomen_med',
            genital_med = '$genital_med',
            extremities_med = '$extremities_med',
            skin_med = '$skin_med',
            prev_fp_method = '$prev_fp_method',
            fp_duration = '$fp_duration',
            past_lmp = $past_lmp,
            bleeding_duration_days = $bleeding_duration_days,
            last_delivery_attendant = '$last_delivery_attendant',
            breast = '$breast_val', 
            breast_left_size = '$breast_left_size', 
            breast_right_size = '$breast_right_size'
            WHERE patient_id = '$mother_id'";
    } else {
        $history_sql = "INSERT INTO pregnancy_history 
            (patient_id, pregnancy_no, gravida, para, full_term, premature, abortion, living_children, 
             review_of_systems, family_history, family_history_details, past_health_history, past_health_details, 
             social_history, social_history_details, physical_exam_vitals, physical_exam_findings, tt_status, muac, bmi, 
             heent_findings, neck, chest_heart, abdomen_med, genital_med, extremities_med, skin_med, 
             prev_fp_method, fp_duration, past_lmp, bleeding_duration_days, last_delivery_attendant,
             breast, breast_left_size, breast_right_size) 
            VALUES 
            ('$mother_id', 1, '$gravida', '$para', '$full_term', '$premature', '$abortion', '$living_children', 
             '$ros', '$family_history', '$family_history', '$past_health_history', '$past_health_history', 
             '$social_history', '$social_history', '$physical_exam_vitals', '$physical_exam_findings', '$tt_status', '$muac', '$bmi', 
             '$heent_findings', '$neck', '$chest_heart', '$abdomen_med', '$genital_med', '$extremities_med', '$skin_med', 
             '$prev_fp_method', '$fp_duration', $past_lmp, $bleeding_duration_days, '$last_delivery_attendant',
             '$breast_val', '$breast_left_size', '$breast_right_size')";
    }

    // Execute History Query
    $run_history = mysqli_query($conn, $history_sql);

    // KUNG MAY ERROR SA QUERY, IPAPALABAS NIYA AGAD ANG DAHILAN:
    if (!$run_history) {
        die("<div style='padding:20px; background:#f8d7da; color:#721c24; font-family:sans-serif;'>
                <h2>Database Query Failed!</h2>
                <p><b>MySQL Error:</b> " . mysqli_error($conn) . "</p>
                <p><b>SQL Query:</b> " . $history_sql . "</p>
                <a href='javascript:history.back()'>Go Back</a>
             </div>");
    }

    // Update status sa maternal_registration
    mysqli_query($conn, "UPDATE maternal_registration SET status = 'Approved' WHERE id = '$mother_id'");

    // Alert at auto-refresh
    echo "<script>
            alert('Maternal record clinically verified & approved successfully!');
            window.location.href = window.location.pathname;
          </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Approvals | Alawihao Health</title>
    <style>
        :root { 
            --primary-green: #89936C; 
            --bg: #fcfdfa; 
            --dark-sage: #6b7553;
        }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); padding: 40px; margin: 0; }
        .container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); max-width: 1100px; margin: 0 auto; }
        h2 { color: var(--primary-green); border-bottom: 2px solid var(--primary-green); padding-bottom: 10px; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; }
        th { background: var(--primary-green); color: white; padding: 12px; text-align: left; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #ddd; font-size: 0.9rem; }
        
        .btn-approve { background: #5cb85c; color: white; padding: 6px 14px; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; display: inline-block; }
        .btn-verify { background: var(--primary-green); color: white; padding: 6px 14px; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; }
        .btn-verify:hover { background: var(--dark-sage); }
        
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.danger { background: #f8d7da; color: #721c24; }

        /* MODAL STYLES */
        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
        .modal-content { background: white; margin: 3% auto; padding: 30px; border-radius: 20px; width: 800px; position: relative; max-height: 90vh; overflow-y: auto; }
        
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 10px; }
        .form-group label { font-size: 0.75rem; font-weight: bold; color: #555; margin-bottom: 4px; text-transform: uppercase; }
        .form-group input, .form-group textarea, .form-group select { padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 0.85rem; font-family: inherit; }
        
        .section-tag { background: #f4f6f0; padding: 6px 12px; font-size: 0.8rem; font-weight: bold; color: var(--primary-green); border-radius: 6px; margin: 15px 0 10px 0; border-left: 4px solid var(--primary-green); }
        .checkbox-group { display: flex; flex-direction: column; gap: 6px; background: #fafafa; padding: 10px; border-radius: 6px; border: 1px solid #eee; font-size: 0.85rem; }
    </style>
</head>
<body>

<div class="container">
    <h2>Pending Clinical Approvals</h2>
    <?php echo $message; ?>

    <!-- NEWBORN TABLE -->
    <h3>Newborn Enrollments</h3>
    <table>
        <thead>
            <tr>
                <th>Baby Name</th>
                <th>Mother</th>
                <th>Birth Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if($newborns && $newborns->num_rows > 0): ?>
                <?php while($row = $newborns->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['baby_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['mother_name'] ?? ''); ?></td>
                    <td><?php echo $row['birth_date'] ?? ''; ?></td>
                    <td><a href="?approve_infant=<?php echo $row['id']; ?>" class="btn-approve">Approve</a></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center; color:#888;">No pending infant registrations.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- PREGNANCY TABLE -->
    <h3 style="margin-top:40px;">Maternal Pre-Registrations (Awaiting In-Clinic Verification)</h3>
    <table>
        <thead>
            <tr>
                <th>Mother's Name</th>
                <th>LMP</th>
                <th>EDC (Expected)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if($pregnancies && $pregnancies->num_rows > 0): ?>
                <?php while($row = $pregnancies->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($row['display_name'] ?? ''); ?></td>
                    <td><?php echo !empty($row['lmp']) ? date('M d, Y', strtotime($row['lmp'])) : 'Not set'; ?></td>
                    <td><?php echo !empty($row['edc']) ? date('M d, Y', strtotime($row['edc'])) : 'Not set'; ?></td>
                    <td>
                        <button type="button" class="btn-verify" onclick='openVerifyModal(<?php echo json_encode($row); ?>)'>
                            Review & Verify (In-Clinic)
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center; color:#888;">No pending maternal registrations.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MIDWIFE IN-CLINIC VERIFICATION MODAL -->
<div id="verifyModal" class="modal">
    <div class="modal-content">
        <h2 style="color:var(--primary-green); margin-top:0; border:none; font-size:1.5rem;">Clinical Verification & Baseline Assessment</h2>
        <p style="font-size:0.85rem; color:#666; margin-top:-10px;">Fill up the patient's medical history during their face-to-face clinic visit to finalize enrollment.</p>
        
        <form method="POST">
            <input type="hidden" name="mother_id" id="modal_mother_id">
            
            <div style="background:#fafafa; border:1px solid #eee; padding:10px 15px; border-radius:8px; margin-bottom:15px;">
                <span style="font-size:0.75rem; color:#888; font-weight:bold; text-transform:uppercase;">Patient Name:</span>
                <div id="modal_mother_name" style="font-weight:bold; color:#333; font-size:1.1rem;"></div>
            </div>

            <div class="section-tag">Obstetrical History (G-P)</div>
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

            <div class="section-tag">Physical Examination & Vital Signs</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Physical Exam Vitals (BP, Temp, PR, RR)</label>
                    <input type="text" name="physical_exam_vitals" placeholder="e.g. BP: 110/70, Temp: 36.7, PR: 78, RR: 18">
                </div>
                <div class="form-group">
                    <label>Physical Exam Findings</label>
                    <input type="text" name="physical_exam_findings" placeholder="General findings on physical exam">
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>TT Status</label>
                    <select name="tt_status">
                        <option value="">-- Select --</option>
                        <option value="TT1">TT1</option>
                        <option value="TT2">TT2</option>
                        <option value="TT3">TT3</option>
                        <option value="TT4">TT4</option>
                        <option value="TT5">TT5</option>
                        <option value="Fully Immunized">Fully Immunized</option>
                        <option value="Not Immunized">Not Immunized</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>MUAC</label>
                    <input type="text" name="muac" placeholder="e.g. 24 cm">
                </div>
                <div class="form-group">
                    <label>BMI & Category</label>
                    <input type="text" name="bmi" placeholder="e.g. 22.5 (Normal)">
                </div>
            </div>

            <div class="section-tag">System & Organ Findings</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>HEENT / Conjunctiva</label>
                    <input type="text" name="heent_findings" placeholder="Head, Eyes, Ears, Nose, Throat findings">
                </div>
                <div class="form-group">
                    <label>Neck</label>
                    <input type="text" name="neck" placeholder="e.g. Supple, no masses, no lymphadenopathy">
                </div>
                <div class="form-group">
                    <label>Thorax / Heart</label>
                    <input type="text" name="chest_heart" placeholder="Chest and heart findings">
                </div>
                <div class="form-group">
                    <label>Abdomen (Medical)</label>
                    <input type="text" name="abdomen_med" placeholder="Abdominal exam findings">
                </div>
                <div class="form-group">
                    <label>Genital / Vaginal</label>
                    <input type="text" name="genital_med" placeholder="Genital exam findings">
                </div>
                <div class="form-group">
                    <label>Extremities</label>
                    <input type="text" name="extremities_med" placeholder="Extremities findings">
                </div>
                <div class="form-group">
                    <label>Skin & Others</label>
                    <input type="text" name="skin_med" placeholder="Skin findings and other notes">
                </div>
            </div>

            <div class="section-tag">Breast Examination Findings</div>
            <div class="checkbox-group">
                <label><input type="checkbox" name="breast[]" value="NORMAL"> Normal</label>
                <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                    <label><input type="checkbox" name="breast[]" value="MASS"> Mass:</label>
                    <span>Left Size: <input type="text" name="breast_left_size" placeholder="e.g. 2x2cm" style="padding: 4px; width: 100px;"></span>
                    <span>Right Size: <input type="text" name="breast_right_size" placeholder="e.g. none" style="padding: 4px; width: 100px;"></span>
                </div>
                <label><input type="checkbox" name="breast[]" value="NIPPLE DISCHARGE"> Nipple Discharge</label>
                <label><input type="checkbox" name="breast[]" value="SKIN-ORANGE OR DIMPLING"> Skin-orange or Dimpling</label>
                <label><input type="checkbox" name="breast[]" value="ENLARGED AXILLARY LYMPH NODES"> Enlarged Axillary Lymph Nodes</label>
            </div>

            <div class="section-tag">Clinical History & Review of Systems</div>
            <div class="form-group">
                <label>Review of Systems (ROS)</label>
                <textarea name="review_of_systems" rows="2" placeholder="HEENT, Chest, Abdomen, Genitals, Extremities assessment findings..."></textarea>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Past Health History</label>
                    <textarea name="past_health_history" rows="2" placeholder="Hypertension, Diabetes, Asthma, Allergies, surgeries, etc."></textarea>
                </div>
                <div class="form-group">
                    <label>Family History</label>
                    <textarea name="family_history" rows="2" placeholder="Family history of Hypertension, Diabetes, Twins, etc."></textarea>
                </div>
            </div>

            <div class="form-group">
                <label>Social History</label>
                <input type="text" name="social_history" placeholder="Smoking, Alcohol intake, Occupation, Support system">
            </div>

            <div class="section-tag">Family Planning History</div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Previous FP Method</label>
                    <input type="text" name="prev_fp_method" placeholder="e.g. Pills, IUD, Condom, None">
                </div>
                <div class="form-group">
                    <label>FP Duration</label>
                    <input type="text" name="fp_duration" placeholder="e.g. 6 months">
                </div>
            </div>

            <div class="section-tag">Menstrual & Delivery Details</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Past LMP</label>
                    <input type="date" name="past_lmp">
                </div>
                <div class="form-group">
                    <label>Bleeding Duration (days)</label>
                    <input type="number" name="bleeding_duration_days" min="0" placeholder="e.g. 5">
                </div>
                <div class="form-group">
                    <label>Last Birth Attendant</label>
                    <input type="text" name="last_delivery_attendant" placeholder="e.g. Midwife, Doctor, TBA">
                </div>
            </div>

            <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="closeVerifyModal()" style="background:#eee; border:none; padding:10px 18px; border-radius:8px; cursor:pointer; font-weight:600;">Cancel</button>
                <button type="submit" name="submit_maternal_approval" class="btn-verify" style="padding:10px 20px;">Complete Clinical Assessment & Approve</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openVerifyModal(data) {
        document.getElementById('modal_mother_id').value = data.id;
        document.getElementById('modal_mother_name').innerText = data.display_name || data.full_name;
        document.getElementById('verifyModal').style.display = "block";
    }

    function closeVerifyModal() {
        document.getElementById('verifyModal').style.display = "none";
    }
</script>

</body>
</html>