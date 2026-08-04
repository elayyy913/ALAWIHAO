<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. DYNAMIC SIDEBAR LOGIC
$u_id = $_SESSION['user_id'];
$get_user = mysqli_query($conn, "SELECT role FROM users WHERE id = '$u_id'");
$user_info = mysqli_fetch_assoc($get_user);
$current_role = strtolower(trim($user_info['role'] ?? ''));

$sidebar_file = (in_array($current_role, ['super admin', 'superadmin'])) ? 'super_admin_sidebar.php' : 'admin_sidebar.php';

// 2. FETCH MATERNAL PATIENTS GALING SA REGISTRATION AT RECORDS
$sql = "SELECT r.id as mother_id, r.client_fname as first_name, r.client_lname as last_name, 
        CONCAT(r.client_fname, ' ', r.client_lname) as full_name,
        (SELECT COUNT(*) FROM maternal_records m2 WHERE m2.mother_id = r.id) as checkup_count,
        (SELECT MAX(checkup_date) FROM maternal_records m2 WHERE m2.mother_id = r.id) as last_visit,
        (SELECT MAX(gestational_age_weeks) FROM maternal_records m2 WHERE m2.mother_id = r.id) as current_aog
        FROM maternal_registration r
        WHERE r.status = 'Approved'
        ORDER BY r.client_fname ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maternal Health Records | Alawihao Center</title>
    <style>
        :root { 
            --sage: #8DAE74; 
            --dark-sage: #6B8E55; 
            --soft-sage: #F1F5ED; 
            --sidebar-width: 280px; 
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            background: #F8FAFC; 
            display: flex; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        #main-wrapper { 
            flex-grow: 1; 
            padding: 30px; 
            margin-left: var(--sidebar-width); 
            transition: var(--transition); 
            width: 100%;
            box-sizing: border-box;
        }

        .page-header {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border: 1px solid #edf2f7;
        }

        .table-container { 
            background: white; 
            padding: 25px; 
            border-radius: 15px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            border: 1px solid #edf2f7;
        }

        table { width: 100%; border-collapse: collapse; }
        th { 
            text-align: left; 
            padding: 15px; 
            background: var(--soft-sage); 
            color: var(--dark-sage); 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
        }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; }

        .btn-group { display: flex; gap: 10px; }
        .btn-history { 
            background: var(--soft-sage); 
            color: var(--dark-sage); 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.8rem; 
        }
        .btn-update { 
            background: var(--sage); 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 0.8rem;
        }
        .btn-update:hover { background: var(--dark-sage); }

        .role-badge { background: #FEFCBF; color: #744210; padding: 6px 14px; border-radius: 8px; font-size: 11px; font-weight: 700; }
        
        /* Modal Styling */
        .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter: blur(3px); overflow-y: auto; }
        .modal-content { background:white; margin:3% auto; padding:30px; width:750px; border-radius:15px; border-top:8px solid var(--sage); box-shadow: 0 25px 30px rgba(0,0,0,0.15); max-height: 85vh; display: flex; flex-direction: column; box-sizing: border-box; }
        
        .trimester-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tri-btn { flex: 1; padding: 10px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; color: #4A5568; text-align: center; transition: all 0.2s; }
        .tri-btn.active { background: var(--sage); color: white; border-color: var(--dark-sage); box-shadow: 0 2px 6px rgba(141, 174, 116, 0.4); }

        input[type="text"], input[type="number"], input[type="date"], select, textarea { 
            width: 100%; padding: 10px 12px; margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-family: inherit; font-size: 0.9rem; 
        }
        textarea { resize: vertical; height: 75px; }
        label { font-size: 0.85rem; font-weight: 600; color: #4A5568; margin-bottom: 5px; display: block; }
        .section-box { background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #edf2f7; }
        .checkbox-group { display: flex; flex-direction: column; gap: 8px; margin-top: 5px; }
        .checkbox-label { display: flex; align-items: center; gap: 8px; font-weight: normal; font-size: 0.88rem; color: #2D3748; cursor: pointer; margin-bottom: 0; }
        .checkbox-label input { width: auto; margin-bottom: 0; cursor: pointer; }
        
        .trimester-section { display: none; }
        .trimester-section.active-section { display: block; }
    </style>
</head>
<body>

    <?php include ($sidebar_file); ?>
    <div id="main-wrapper">
        <div class="page-header">
            <h2 style="color: var(--dark-sage); margin: 0;">Maternal Health History & ANC Records</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Mother Name</th>
                        <th>Current Gestational Age</th>
                        <th>Last Visit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): 
                            $full_name = htmlspecialchars($row['full_name']);
                            $patient_id = $row['mother_id'];
                            $aog = $row['current_aog'] ?? 0;
                        ?>
                        <tr>
                            <td>
                                <span style="font-weight: 600; color: #2D3748;"><?php echo $full_name; ?></span><br>
                                <small style="color: #A0AEC0;"><?php echo $row['checkup_count'] ?? 0; ?> total check-ups</small>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #2b6cb0;"><?php echo $aog ? $aog . ' Weeks' : 'Not specified'; ?></span>
                            </td>
                            <td style="color: #4A5568;">
                                <?php echo ($row['last_visit']) ? date("M d, Y", strtotime($row['last_visit'])) : '<span style="color:#CBD5E0">No records</span>'; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="admin_maternal_history.php?id=<?php echo $patient_id; ?>" class="btn-history">View History</a>
                                    <button type="button" class="btn-update" onclick="openUpdateModal('<?php echo $patient_id; ?>', '<?php echo addslashes($row['full_name']); ?>')">Add Record</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #A0AEC0; padding: 30px;">No verified maternal records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MATERNAL RECORD FORM MODAL -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 id="modalTitle" style="color:var(--dark-sage); margin:0;">Add Maternal Record</h3>
                <button type="button" onclick="closeModal()" style="background:none; border:none; font-size:18px; cursor:pointer; color:#A0AEC0;">✕</button>
            </div>

            <div class="trimester-tabs">
                <button type="button" class="tri-btn active" id="btnTri1" onclick="setTrimester(1)">1st Trimester</button>
                <button type="button" class="tri-btn" id="btnTri2" onclick="setTrimester(2)">2nd Trimester</button>
                <button type="button" class="tri-btn" id="btnTri3" onclick="setTrimester(3)">3rd Trimester</button>
            </div>

            <form method="POST" action="admin_save_maternal_rec.php" style="overflow-y: auto; max-height: 60vh; padding-right: 5px;">
                <input type="hidden" name="mother_id" id="modal_mother_id">
                <input type="hidden" name="trimester" id="selected_trimester" value="1">

                <div>
                    <label>Petsa:</label>
                    <input type="date" name="checkup_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label>Timbang: (kg)</label>
                        <input type="number" name="weight_kg" step="0.1" required>
                    </div>
                    <div>
                        <label>Taas: (cm)</label>
                        <input type="number" name="height_cm" step="0.1">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label>Age of Gestation (weeks):</label>
                        <input type="number" name="gestational_age_weeks" id="aog_input" min="1" max="42" placeholder="e.g. 12" required>
                    </div>
                    <div>
                        <label>Blood Pressure:</label>
                        <input type="text" name="bp" placeholder="120/80" required>
                    </div>
                </div>

                <div class="section-box">
                    <label style="margin-bottom: 8px;">Nutritional Status:</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label"><input type="radio" name="nutritional_status" value="Normal" required> Normal</label>
                        <label class="checkbox-label"><input type="radio" name="nutritional_status" value="Underweight"> Underweight</label>
                        <label class="checkbox-label"><input type="radio" name="nutritional_status" value="Overweight"> Overweight</label>
                    </div>
                </div>

                <label>Pagsusuri ng kalagayan ng buntis:</label>
                <textarea name="pagsusuri_kalagayan" placeholder="Ilagay ang mga natuklasan sa pagsusuri..."></textarea>

                <label>Mga payong binigay:</label>
                <textarea name="mga_payo" placeholder="Mga payong ibinigay sa pasyente..."></textarea>

                <label>Mga pagbabago sa birthplan:</label>
                <textarea name="birthplan_changes" placeholder="Mga update o pagbabago sa birth plan..."></textarea>

                <label>Pagsusuri ng ngipin:</label>
                <textarea name="pagsusuri_ngipin" placeholder="Resulta o detalye ng pagsusuri ng ngipin..."></textarea>

                <label>Laboratory test done:</label>
                <input type="text" name="lab_test_done" placeholder="Uri ng laboratory test">

                <!-- ================= 1ST TRIMESTER SPECIFIC FIELDS ================= -->
                <div id="section_tri1" class="trimester-section active-section">
                    <label>Hemoglobin count:</label>
                    <input type="text" name="hemoglobin_count" placeholder="Hemoglobin count">

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Urinalysis:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="urinalysis" value="Done"> Urinalysis Done</label>
                        </div>
                    </div>

                    <label>Complete Blood Count (CBC):</label>
                    <input type="text" name="cbc" placeholder="Resulta ng CBC">

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">STIs gamit ang syndromic approach:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="sti_syphilis" value="1"> Syphilis</label>
                            <label class="checkbox-label"><input type="checkbox" name="sti_hiv" value="1"> HIV</label>
                            <label class="checkbox-label"><input type="checkbox" name="sti_hepatitis_b" value="1"> Hepatitis B (HBsAg)</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Stool Examination:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="stool_exam" value="Done"> Stool Examination Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Acetic Acid Wash:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="acetic_acid_wash" value="Done"> Acetic Acid Wash Done</label>
                        </div>
                    </div>

                <div class="section-box">
                    <label style="margin-bottom: 8px;">Tetanus-containing vaccine:</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="tetanus_vaccine_done" id="tetanusCheckbox" value="1" onchange="toggleTetanusDate()"> Tetanus-containing vaccine given
                        </label>
                    </div>
                    
                    <div id="tetanusDateContainer" style="display: none; margin-top: 10px;">
                        <label style="font-weight:normal; font-size:0.8rem;">Date given:</label>
                        <input type="date" name="tetanus_vaccine_date">
                    </div>
                </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Treatments (1st Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="treat_syphilis" value="1"> Syphilis</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_arv" value="1"> Antiretroviral (ARV)</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_bacteriuria" value="1"> Bacteriuria</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_anemia" value="1"> Anemia</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Pinag-usapan / Serbisyong binigay (1st Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="srv_alcohol_tobacco" value="1"> Pag-iwas sa alcohol, tabacco, at illegal na droga</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_diet" value="1"> Pagpapayo tungkol sa tamang pagkain</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_safesex" value="1"> Pagpapayo sa safe sex</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_mosquito_net" value="1"> Paggamit ng mga insecticide-treated na kulambo</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_birthplan" value="1"> Birthplan</label>
                        </div>
                    </div>
                </div>

                <!-- ================= 2ND TRIMESTER SPECIFIC FIELDS ================= -->
                <div id="section_tri2" class="trimester-section">
                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Urinalysis:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="urinalysis_tri2" value="Done"> Urinalysis Done</label>
                        </div>
                    </div>

                    <label>Complete Blood Count (CBC):</label>
                    <input type="text" name="cbc_tri2" placeholder="Resulta ng CBC">

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Etiological tests para sa STIs (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="etiological_stis" value="1"> Etiological test done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Pap smear (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="pap_smear" value="1"> Pap Smear Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Gestational diabetes (oral glucose challenge test), (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="gestational_diabetes" value="1"> Oral Glucose Challenge Test Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Bacteriuria (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="bacteriuria_tri2" value="1"> Bacteriuria Test Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Treatments (2nd Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="treat_deworming" value="1"> Deworming</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_arv_tri2" value="1"> Antiretroviral (ARV)</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_bacteriuria_tri2" value="1"> Bacteriuria</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_anemia_tri2" value="1"> Anemia</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Pinag-usapan / Serbisyong binigay (2nd Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="srv_previous_discussion_tri2" value="1"> Pagpapaalala ng nakaraang tinalakay</label>
                        </div>
                    </div>
                </div>

                <!-- ================= 3RD TRIMESTER SPECIFIC FIELDS ================= -->
                <div id="section_tri3" class="trimester-section">
                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Urinalysis:</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="urinalysis_tri3" value="Done"> Urinalysis Done</label>
                        </div>
                    </div>

                    <label>Complete Blood Count (CBC):</label>
                    <input type="text" name="cbc_tri3" placeholder="Resulta ng CBC">

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Bacteriuria (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="bacteriuria_tri3" value="1"> Bacteriuria Test Done</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Blood/RH group (kung kinakailangan):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="blood_rh_group" value="1"> Blood/RH group determined</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Treatments (3rd Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="treat_arv_tri3" value="1"> Antiretroviral (ARV)</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_bacteriuria_tri3" value="1"> Bacteriuria</label>
                            <label class="checkbox-label"><input type="checkbox" name="treat_anemia_tri3" value="1"> Anemia</label>
                        </div>
                    </div>

                    <div class="section-box">
                        <label style="margin-bottom: 8px;">Pinag-usapan / Serbisyong binigay (3rd Tri):</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label"><input type="checkbox" name="srv_previous_discussion_tri3" value="1"> Pagpapaalala ng nakaraang tinalakay</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_postpartum" value="1"> Pagpapayo sa postpartum at postnatal care</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_spacing" value="1"> Pagpapayo sa pag agwat ng anak</label>
                            <label class="checkbox-label"><input type="checkbox" name="srv_tetanus_followup" value="1"> Pag follow up ng tetanus-containing vaccine</label>
                        </div>
                    </div>
                </div>

                <!-- COMMON FIELDS FOR ALL TRIMESTERS -->
                <div style="margin-top: 15px;">
                    <label>Petsa ng pagbalik:</label>
                    <input type="date" name="next_visit_date">
                </div>

                <div>
                    <label>Pangalan ng health service provider:</label>
                    <input type="text" name="provider_name" placeholder="Pangalan ng Midwife / Doctor">
                </div>

                <label>Referral sa ospital:</label>
                <input type="text" name="hospital_referral" placeholder="Pangalan ng ospital o dahilan ng referral">

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                    <button type="button" onclick="closeModal()" style="background:none; border:none; color:#A0AEC0; cursor:pointer; font-weight:600;">Cancel</button>
                    <button type="submit" class="btn-update">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function setTrimester(trimesterNum) {
        document.getElementById('selected_trimester').value = trimesterNum;
        
        // Tab buttons UI
        document.getElementById('btnTri1').classList.remove('active');
        document.getElementById('btnTri2').classList.remove('active');
        document.getElementById('btnTri3').classList.remove('active');
        document.getElementById('btnTri' + trimesterNum).classList.add('active');

        // Sections visibility
        document.getElementById('section_tri1').classList.remove('active-section');
        document.getElementById('section_tri2').classList.remove('active-section');
        document.getElementById('section_tri3').classList.remove('active-section');
        document.getElementById('section_tri' + trimesterNum).classList.add('active-section');
    }

    // Auto switch tab based on Age of Gestation weeks input
    document.getElementById('aog_input').addEventListener('input', function() {
        let weeks = parseInt(this.value);
        if (!isNaN(weeks)) {
            if (weeks >= 1 && weeks <= 13) {
                setTrimester(1);
            } else if (weeks >= 14 && weeks <= 27) {
                setTrimester(2);
            } else if (weeks >= 28) {
                setTrimester(3);
            }
        }
    });

    function openUpdateModal(id, name) {
        document.getElementById('updateModal').style.display = 'block';
        document.getElementById('modal_mother_id').value = id;
        document.getElementById('modalTitle').innerText = "Add Maternal Record for: " + name;
    }

    function closeModal() { 
        document.getElementById('updateModal').style.display = 'none'; 
    }

    window.onclick = function(event) { 
        if (event.target == document.getElementById('updateModal')) { 
            closeModal(); 
        } 
    }
    function toggleTetanusDate() {
    let checkbox = document.getElementById('tetanusCheckbox');
    let dateContainer = document.getElementById('tetanusDateContainer');
    
    if (checkbox.checked) {
        dateContainer.style.display = 'block';
    } else {
        dateContainer.style.display = 'none';
    }
}
    </script>
</body>
</html>