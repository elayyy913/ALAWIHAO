<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mother_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. DYNAMIC SIDEBAR LOGIC
$u_id = $_SESSION['user_id'];
$get_user = mysqli_query($conn, "SELECT role FROM users WHERE id = '$u_id'");
$user_info = mysqli_fetch_assoc($get_user);
$current_role = strtolower(trim($user_info['role'] ?? ''));
$sidebar_file = (in_array($current_role, ['super admin', 'superadmin'])) ? 'super_admin_sidebar.php' : 'admin_sidebar.php';

// 2. HILAIN ANG REGISTRATION DATA NG PASYENTE
$reg_query = mysqli_query($conn, "SELECT *, CONCAT(COALESCE(street,''), ', ', COALESCE(barangay,''), ', ', COALESCE(municipality,'')) AS full_address FROM maternal_registration WHERE id = '$mother_id' LIMIT 1");
$patient = mysqli_fetch_assoc($reg_query);

if (!$patient) {
    header("Location: admin_maternal_rec.php");
    exit();
}

// 3. HILAIN ANG LAHAT NG KASAYSAYAN NG CHECK-UP
$visits_query = mysqli_query($conn, "SELECT * FROM maternal_records WHERE mother_id = '$mother_id' ORDER BY checkup_date ASC");
$checkups = [];
while ($row = mysqli_fetch_assoc($visits_query)) {
    $checkups[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Maternal Health Record - Alawihao Health Center</title>
    <style>
        /* Hindi na gagamit ng global reset para hindi masira ang sidebar layout */
        body {
            background-color: #F4F6F3;
            color: #000000;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        /* Top Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            width: 210mm;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-back {
            text-decoration: none;
            color: #4A5568;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-back:hover { color: #000; }

        .action-buttons-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-top {
            background-color: #4A5568;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            text-decoration: none;
        }
        .btn-top:hover { background-color: #2D3748; }
        .btn-word-edit { background-color: #D69E2E; }
        .btn-word-edit:hover { background-color: #B7791F; }
        .btn-download-dev { background-color: #3182CE; }
        .btn-download-dev:hover { background-color: #2B6CB0; }

        /* Print Filter Selector Bar */
        .print-filter-bar {
            background: #EDF2F7;
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 210mm;
            margin: 0 auto 20px auto;
        }

        .print-filter-bar select {
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #CBD5E0;
            background: white;
            font-weight: 600;
        }

        /* EXACT A4 SIZE PAPER CONTAINER */
        .report-page {
            background: #FFFFFF;
            border: 1px solid #A0AEC0;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 0 auto 30px auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            page-break-after: always;
            position: relative;
            font-family: "Times New Roman", Times, serif;
        }

        /* Official Letterhead */
        .print-header {
            text-align: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .print-header h4 {
            font-size: 9pt;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            font-family: Arial, sans-serif;
        }

        .print-header h2 {
            font-size: 14pt;
            font-weight: bold;
            margin: 4px 0;
            font-family: Arial, sans-serif;
        }

        .print-header p {
            font-size: 9pt;
            font-family: Arial, sans-serif;
        }

        .report-title-heading {
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 22px;
            text-decoration: underline;
        }

        /* Section Styling */
        .section-block {
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 10pt;
            text-transform: uppercase;
            font-weight: bold;
            border-bottom: 1px solid #000000;
            padding-bottom: 3px;
            margin-bottom: 10px;
            font-family: Arial, sans-serif;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .info-item label {
            display: block;
            font-size: 8pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #2D3748;
            font-family: Arial, sans-serif;
        }

        .info-item span {
            font-size: 10pt;
        }

        /* Checkup Details Layout */
        .vitals-text-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            font-size: 10pt;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px dotted #CBD5E0;
        }

        .details-text-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            font-size: 10pt;
            margin-bottom: 15px;
        }

        .details-text-row p {
            margin-bottom: 5px;
        }

        /* Signatures Section */
        .signatures-section {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .sig-box {
            text-align: center;
        }

        .sig-line {
            border-bottom: 1px solid #000000;
            margin-bottom: 5px;
            height: 40px;
        }

        .sig-box p {
            font-size: 9pt;
            font-weight: bold;
            font-family: Arial, sans-serif;
        }

        .sig-box span {
            font-size: 8pt;
            font-family: Arial, sans-serif;
        }

        @media print {
            @page { size: A4; margin: 0; }
            body { background: white; }
            .sidebar, .open-sidebar-btn, .action-bar, .print-filter-bar { display: none !important; }
            .report-page { border: none !important; box-shadow: none !important; margin: 0 auto !important; width: 210mm !important; min-height: 297mm !important; page-break-after: always; }
            .report-page.hidden-print { display: none !important; }
        }
    </style>
</head>
<body>

    <?php include $sidebar_file; ?>

    <div style="padding: 30px;">
        <!-- Action & Navigation Bar -->
        <div class="action-bar">
            <a href="admin_maternal_hr.php" class="btn-back">&#8592; Back</a>
            <div class="action-buttons-right">
                <button class="btn-top btn-word-edit" onclick="downloadCurrentActiveWord()">
                    &#9998; Open with Word App 
                </button>
                <button class="btn-top btn-download-dev" onclick="downloadWordDoc()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download
                </button>
                <button class="btn-top" onclick="window.print()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"></path></svg>
                    print
                </button>
            </div>
        </div>

        <!-- Print Filter Option Bar -->
        <div class="print-filter-bar">
            <label for="printSelect"><strong>Filter:</strong></label>
            <select id="printSelect" onchange="filterPrintPages(this.value)">
                <option value="all">Full record</option>
                <?php foreach($checkups as $index => $c): ?>
                    <option value="page-<?php echo $index; ?>">Record #<?php echo ($index + 1); ?> (<?php echo htmlspecialchars($c['checkup_date']); ?> - <?php echo htmlspecialchars($c['trimester'] ?? 'Check-up'); ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if(!empty($checkups)): ?>
            <?php foreach($checkups as $index => $chk): ?>
                <!-- BAWAT CHECK-UP AY HIWALAY NA A4 PAGE -->
                <div class="report-page" id="report-page-<?php echo $index; ?>" data-trimester-name="<?php echo htmlspecialchars($chk['trimester'] ?? 'Record_' . ($index + 1)); ?>">
                    
                    <!-- Official Letterhead -->
                    <div class="print-header">
                        <h4>Municipality of Daet &bull; Province of Camarines Norte</h4>
                        <h2>ALAWIHAO HEALTH CENTER</h2>
                        <p>Brgy. Alawihao, Daet, Camarines Norte</p>
                    </div>

                    <div class="report-title-heading">
                        Maternal Medical Record &mdash; <?php echo htmlspecialchars($chk['trimester'] ?? 'Check-up Record #' . ($index + 1)); ?>
                    </div>

                    <!-- 1. Personal & Registration Information -->
                    <div class="section-block">
                        <div class="section-title">I. Personal Information & Registration Details</div>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Pangalan ng Ina</label>
                                <span><?php echo htmlspecialchars(trim(($patient['client_fname'] ?? '') . ' ' . ($patient['client_mi'] ?? '') . ' ' . ($patient['client_lname'] ?? ''))); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Edad / Kapanganakan</label>
                                <span><?php echo htmlspecialchars($patient['client_age'] ?? 'N/A'); ?> yrs old (<?php echo htmlspecialchars($patient['client_bday'] ?? 'N/A'); ?>)</span>
                            </div>
                            <div class="info-item">
                                <label>Numero ng Kontak</label>
                                <span><?php echo htmlspecialchars($patient['client_contact'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item" style="grid-column: span 2;">
                                <label>Tirahan</label>
                                <span><?php echo htmlspecialchars($patient['full_address'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Civil Status</label>
                                <span><?php echo htmlspecialchars($patient['client_civil_status'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Pangalan ng Asawa</label>
                                <span><?php echo htmlspecialchars($patient['husband_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Blood Type</label>
                                <span><?php echo htmlspecialchars($patient['blood_type'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>LMP (Last Menstrual Period)</label>
                                <span><?php echo htmlspecialchars($patient['lmp'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Specific Checkup Record Details -->
                    <div class="section-block">
                        <div class="section-title">II. Check-up & Health Record Details (Petsa: <?php echo htmlspecialchars($chk['checkup_date']); ?>)</div>
                        
                        <div class="vitals-text-row">
                            <div><strong>Timbang:</strong> <?php echo htmlspecialchars($chk['weight_kg'] ?? 'N/A'); ?> kg</div>
                            <div><strong>BP:</strong> <?php echo htmlspecialchars($chk['bp'] ?? 'N/A'); ?></div>
                            <div><strong>Temp:</strong> <?php echo htmlspecialchars($chk['temperature'] ?? 'N/A'); ?> °C</div>
                            <div><strong>AOG:</strong> <?php echo htmlspecialchars($chk['gestational_age_weeks'] ?? 'N/A'); ?> Weeks</div>
                        </div>

                        <div class="details-text-row">
                            <div>
                                <p style="font-size: 8pt; text-transform: uppercase; font-weight: bold; color: #2D3748; font-family: Arial, sans-serif;">Pagsusuri / Remarks:</p>
                                <p><?php echo nl2br(htmlspecialchars($chk['remarks'] ?? 'Walang naitalang remarks.')); ?></p>
                            </div>
                            <div>
                                <p style="font-size: 8pt; text-transform: uppercase; font-weight: bold; color: #2D3748; font-family: Arial, sans-serif;">Iba pang Detalye:</p>
                                <p><strong>Fetal Heart Rate:</strong> <?php echo htmlspecialchars($chk['fetal_heart_rate'] ?? 'N/A'); ?><br>
                                   <strong>Trimester:</strong> <?php echo htmlspecialchars($chk['trimester'] ?? 'Standard Check-up'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Official Signatures Section -->
                    <div class="signatures-section">
                        <div class="sig-box">
                            <div class="sig-line"></div>
                            <p>NAG-HANDLE NA MIDWIFE / NURSE</p>
                            <span>Signature Over Printed Name & License No.</span>
                        </div>
                        <div class="sig-box">
                            <div class="sig-line"></div>
                            <p>MUNICIPAL HEALTH OFFICER / OIC</p>
                            <span>Alawihao Health Center</span>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="report-page" style="text-align: center; padding-top: 50px;">
                <p style="font-size: 10pt;">Wala pang naitalang check-up history o records ang pasyenteng ito.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterPrintPages(val) {
            const pages = document.querySelectorAll('.report-page');
            pages.forEach((page) => {
                if (val === 'all' || page.id === 'report-' + val) {
                    page.classList.remove('hidden-print');
                    page.style.display = 'block';
                } else {
                    page.classList.add('hidden-print');
                    page.style.display = 'none';
                }
            });
        }

        function downloadCurrentActiveWord() {
            const activeSelect = document.getElementById('printSelect').value;
            let targetElement = null;
            let fileName = "Maternal_Medical_Record";

            if (activeSelect === 'all') {
                targetElement = document.querySelector('.report-page');
            } else {
                targetElement = document.getElementById('report-' + activeSelect);
            }

            if (!targetElement) return;
            fileName = "Maternal_Record_" + targetElement.getAttribute('data-trimester-name');

            let htmlContent = `
                <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                <head>
                    <meta charset='utf-8'>
                    <title>${fileName}</title>
                    <style>
                        body { font-family: 'Times New Roman', Times, serif; color: #000000; line-height: 1.5; font-size: 11pt; }
                        h2 { text-align: center; font-size: 14pt; font-weight: bold; font-family: Arial, sans-serif; }
                        h4 { text-align: center; font-size: 10pt; font-weight: bold; text-transform: uppercase; font-family: Arial, sans-serif; }
                        p { font-size: 10pt; }
                        .print-header { text-align: center; border-bottom: 2px solid #000000; padding-bottom: 10px; margin-bottom: 15px; }
                        .report-title-heading { font-size: 11pt; font-weight: bold; text-align: center; text-transform: uppercase; margin-bottom: 20px; text-decoration: underline; }
                        .section-title { font-size: 10pt; font-weight: bold; border-bottom: 1px solid #000000; margin-top: 15px; margin-bottom: 8px; text-transform: uppercase; font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
                        td { padding: 4px; font-size: 10pt; vertical-align: top; }
                    </style>
                </head>
                <body>
                    ${targetElement.innerHTML}
                </body>
                </html>
            `;

            let blob = new Blob(['\ufeff' + htmlContent], { type: 'application/msword' });
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = fileName + '.doc';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        function downloadWordDoc() {
            const activeSelect = document.getElementById('printSelect').value;
            let contentHTML = "";

            if (activeSelect === 'all') {
                document.querySelectorAll('.report-page').forEach(p => {
                    contentHTML += p.innerHTML + "<br clear=all style='page-break-before:always'>";
                });
            } else {
                const selectedPage = document.getElementById('report-' + activeSelect);
                if (selectedPage) {
                    contentHTML = selectedPage.innerHTML;
                }
            }

            let htmlContent = `
                <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                <head><meta charset='utf-8'><title>Maternal Health Record</title></head>
                <body style='font-family: Times New Roman, serif;'>
                    ${contentHTML}
                </body>
                </html>
            `;

            let blob = new Blob(['\ufeff' + htmlContent], { type: 'application/msword' });
            let url = URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'Maternal_Health_Record_Alawihao.doc';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>

</body>
</html>