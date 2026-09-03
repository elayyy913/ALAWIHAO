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

// 2. KUNIN ANG DATA GAMIT ANG TAMANG COLUMN NAMES SA MATERNAL_REGISTRATION
$reg_query = mysqli_query($conn, "SELECT *, CONCAT(COALESCE(street,''), ', ', COALESCE(barangay,''), ', ', COALESCE(municipality,'')) AS full_address FROM maternal_registration WHERE id = '$mother_id' LIMIT 1");
$patient = mysqli_fetch_assoc($reg_query);

if (!$patient) {
    header("Location: admin_maternal_hr.php");
    exit();
}

// 3. KUNIN ANG LAHAT NG KASAYSAYAN MULA SA maternal_records
$visits_query = mysqli_query($conn, "SELECT * FROM maternal_records WHERE mother_id = '$mother_id' ORDER BY checkup_date DESC");
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
    <title>Maternal Check-up History - Alawihao Health Center</title>
    <style>
        body {
            background-color: #F4F6F3;
            color: #2D3748;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .main-container {
            padding: 24px;
            max-width: 1300px;
            margin: 0 auto;
        }

        /* Page Title Header Card */
        .page-title-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title-card h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #2D3748;
            font-weight: bold;
        }

        .btn-back {
            text-decoration: none;
            color: #4A5568;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #EDF2F7;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .btn-back:hover { background: #E2E8F0; color: #1A202C; }

        /* Table Container Style */
        .table-container {
            background: #FFFFFF;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            overflow: hidden;
            border: 1px solid #E2E8F0;
        }

        /* Expanded Patient Info Banner */
        .patient-info-banner {
            background: #FAFAFA;
            padding: 20px 24px;
            border-bottom: 1px solid #E2E8F0;
        }

        .patient-info-banner h3 {
            margin: 0 0 14px 0;
            color: #2D3748;
            font-size: 1.1rem;
            font-weight: bold;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 8px;
        }

        .patient-details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .banner-item label {
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: bold;
            color: #718096;
            margin-bottom: 3px;
        }

        .banner-item span {
            font-size: 0.9rem;
            color: #2D3748;
            font-weight: 500;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.85rem;
        }

        th {
            background-color: #F0F4EC;
            color: #3F5238;
            font-weight: bold;
            padding: 14px 20px;
            border-bottom: 1px solid #DCE3D6;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid #EDF2F7;
            color: #2D3748;
        }

        tr:hover {
            background-color: #FBFBFA;
        }

        .btn-print-record {
            background-color: #7A9A70;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            transition: background-color 0.2s;
        }
        .btn-print-record:hover {
            background-color: #63805B;
        }

        .no-records {
            text-align: center;
            padding: 40px;
            color: #A0AEC0;
            font-style: italic;
            font-size: 0.9rem;
        }

        /* MODAL POPUP STYLING */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            padding: 20px 0;
        }

        .modal-content-box {
            background: #F4F6F3;
            width: fit-content;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
        }

        .modal-close-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            width: 210mm;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-close-modal {
            background-color: #E53E3E;
            color: white;
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background-color 0.2s;
        }
        .btn-close-modal:hover { background-color: #C53030; }

        .action-buttons-right {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-top {
            background-color: #4A5568;
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            transition: background-color 0.2s;
        }
        .btn-top:hover { background-color: #2D3748; }
        .btn-word-edit { background-color: #D69E2E; }
        .btn-word-edit:hover { background-color: #B7791F; }
        .btn-download-dev { background-color: #3182CE; }
        .btn-download-dev:hover { background-color: #2B6CB0; }

        /* A4 Paper Layout inside Modal */
        .report-page {
            background: #FFFFFF;
            border: 1px solid #CBD5E0;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 0 auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            font-family: "Times New Roman", Times, serif;
            box-sizing: border-box;
        }

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
            margin: 0;
        }

        .report-title-heading {
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 22px;
            text-decoration: underline;
        }

        .section-block { margin-bottom: 18px; }

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

        .info-item span { font-size: 10pt; }

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

        .signatures-section {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .sig-box { text-align: center; }
        .sig-line { border-bottom: 1px solid #000000; margin-bottom: 5px; height: 40px; }
        .sig-box p { font-size: 9pt; font-weight: bold; font-family: Arial, sans-serif; margin: 0; }
        .sig-box span { font-size: 8pt; font-family: Arial, sans-serif; }

        @media print {
            body * { visibility: hidden; }
            .modal-overlay, .modal-overlay * { visibility: visible; }
            .modal-overlay { position: absolute; left: 0; top: 0; background: white; padding: 0; }
            .modal-close-bar { display: none !important; }
            .modal-content-box { background: white; box-shadow: none; padding: 0; width: 100%; max-height: none; }
            .report-page { border: none !important; box-shadow: none !important; margin: 0 !important; width: 210mm !important; }
        }
    </style>
</head>
<body>

    <?php include $sidebar_file; ?>

    <div class="main-container">
        <!-- Page Header Card -->
        <div class="page-title-card">
            <h2>Maternal Health History & ANC Records</h2>
            <a href="admin_maternal_hr.php" class="btn-back">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                Back to List
            </a>
        </div>

        <div class="table-container">
            <!-- Patient Summary Banner with Balanced Positions -->
            <div class="patient-info-banner">
                <h3><?php echo htmlspecialchars(trim(($patient['client_fname'] ?? '') . ' ' . ($patient['client_mi'] ?? '') . ' ' . ($patient['client_lname'] ?? ''))); ?></h3>
                <div class="patient-details-grid">
                    <div class="banner-item">
                        <label>Edad / Kapanganakan</label>
                        <span><?php echo htmlspecialchars($patient['age'] ?? 'N/A'); ?> yrs old (<?php echo htmlspecialchars($patient['dob'] ?? 'N/A'); ?>)</span>
                    </div>
                    <div class="banner-item">
                        <label>Numero ng Kontak</label>
                        <span><?php echo htmlspecialchars($patient['contact'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="banner-item">
                        <label>Blood Type</label>
                        <span><?php echo htmlspecialchars($patient['blood_type'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="banner-item">
                        <label>Tirahan</label>
                        <span><?php echo htmlspecialchars($patient['full_address'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="banner-item">
                        <label>Pangalan ng Asawa</label>
                        <span><?php echo htmlspecialchars(trim(($patient['spouse_fname'] ?? '') . ' ' . ($patient['spouse_lname'] ?? '')) ?: 'N/A'); ?></span>
                    </div>
                    <div class="banner-item">
                        <label>LMP (Last Menstrual Period)</label>
                        <span><?php echo htmlspecialchars($patient['lmp'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Check-up Date</th>
                        <th>Trimester</th>
                        <th>AOG (Weeks)</th>
                        <th>Weight / BP</th>
                        <th>Remarks / Details</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($checkups)): ?>
                        <?php foreach($checkups as $index => $chk): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($chk['checkup_date']); ?></strong></td>
                                <td><?php echo htmlspecialchars($chk['trimester'] ?? 'N/A'); ?></td>
                                <td><span style="color: #3182CE; font-weight: bold;"><?php echo htmlspecialchars($chk['gestational_age_weeks'] ?? 'Not specified'); ?> Weeks</span></td>
                                <td><?php echo htmlspecialchars($chk['weight_kg'] ?? '0'); ?> kg / <?php echo htmlspecialchars($chk['bp'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(substr($chk['remarks'] ?? 'Walang remarks', 0, 40)) . '...'; ?></td>
                                <td style="text-align: right;">
                                    <button class="btn-print-record" onclick="openPrintModal(<?php echo $index; ?>)">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"></path></svg>
                                        Print Record
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-records">Wala pang naitalang session o check-up record ang pasyenteng ito mula sa Maternal Records.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL CONTAINER PARA SA PRINT VIEW -->
    <div class="modal-overlay" id="printModalOverlay">
        <div class="modal-content-box">
            <div class="modal-close-bar">
                <button class="btn-close-modal" onclick="closePrintModal()">✕ Close Window</button>
                <div class="action-buttons-right">
                    <button class="btn-top btn-word-edit" onclick="openWithWordApp()">
                        &#9998; Open with Word App
                    </button>
                    <button class="btn-top btn-download-dev" onclick="downloadModalWord()">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Download Word
                    </button>
                    <button class="btn-top" onclick="window.print()">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"></path></svg>
                        Print
                    </button>
                </div>
            </div>

            <!-- Dito ilalagay ang dynamic template ng bawat check-up -->
            <div id="modalReportContainer"></div>
        </div>
    </div>

    <!-- JavaScript Data and Modal Handlers -->
    <script>
        const checkupsData = <?php echo json_encode($checkups); ?>;
        const patientData = <?php echo json_encode($patient); ?>;

        function openPrintModal(index) {
            const chk = checkupsData[index];
            if (!chk) return;

            const fullName = ((patientData.client_fname || '') + ' ' + (patientData.client_mi || '') + ' ' + (patientData.client_lname || '')).trim();
            const fullAddress = patientData.full_address || 'N/A';
            const spouseFullName = ((patientData.spouse_fname || '') + ' ' + (patientData.spouse_lname || '')).trim() || 'N/A';
            const trimesterName = chk.trimester || 'Check-up Record';

            let htmlContent = `
                <div class="report-page" id="activeReportPage" data-filename="Maternal_Record_${trimesterName}">
                    <div class="print-header">
                        <h4>Municipality of Daet &bull; Province of Camarines Norte</h4>
                        <h2>ALAWIHAO HEALTH CENTER</h2>
                        <p>Brgy. Alawihao, Daet, Camarines Norte</p>
                    </div>

                    <div class="report-title-heading">
                        Maternal Medical Record &mdash; ${trimesterName}
                    </div>

                    <div class="section-block">
                        <div class="section-title">I. Personal Information & Registration Details</div>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Pangalan ng Ina</label>
                                <span>${fullName}</span>
                            </div>
                            <div class="info-item">
                                <label>Edad / Kapanganakan</label>
                                <span>${patientData.age || 'N/A'} yrs old (${patientData.dob || 'N/A'})</span>
                            </div>
                            <div class="info-item">
                                <label>Numero ng Kontak</label>
                                <span>${patientData.contact || 'N/A'}</span>
                            </div>
                            <div class="info-item">
                                <label>Tirahan</label>
                                <span>${fullAddress}</span>
                            </div>
                            <div class="info-item">
                                <label>Pangalan ng Asawa</label>
                                <span>${spouseFullName}</span>
                            </div>
                            <div class="info-item">
                                <label>Blood Type</label>
                                <span>${patientData.blood_type || 'N/A'}</span>
                            </div>
                            <div class="info-item" style="grid-column: span 3;">
                                <label>LMP (Last Menstrual Period)</label>
                                <span>${patientData.lmp || 'N/A'}</span>
                            </div>
                        </div>
                    </div>

                    <div class="section-block">
                        <div class="section-title">II. Check-up & Health Record Details (Petsa: ${chk.checkup_date || 'N/A'})</div>
                        
                        <div class="vitals-text-row">
                            <div><strong>Timbang:</strong> ${chk.weight_kg || 'N/A'} kg</div>
                            <div><strong>BP:</strong> ${chk.bp || 'N/A'}</div>
                            <div><strong>Temp:</strong> ${chk.temperature || 'N/A'} °C</div>
                            <div><strong>AOG:</strong> ${chk.gestational_age_weeks || 'N/A'} Weeks</div>
                        </div>

                        <div class="details-text-row">
                            <div>
                                <p style="font-size: 8pt; text-transform: uppercase; font-weight: bold; color: #2D3748; font-family: Arial, sans-serif; margin-bottom: 5px;">Pagsusuri / Remarks:</p>
                                <p style="margin: 0;">${(chk.remarks || 'Walang naitalang remarks.').replace(/\n/g, '<br>')}</p>
                            </div>
                            <div>
                                <p style="font-size: 8pt; text-transform: uppercase; font-weight: bold; color: #2D3748; font-family: Arial, sans-serif; margin-bottom: 5px;">Iba pang Detalye:</p>
                                <p style="margin: 0;"><strong>Fetal Heart Rate:</strong> ${chk.fetal_heart_rate || 'N/A'}<br>
                                   <strong>Trimester:</strong> ${chk.trimester || 'Standard Check-up'}</p>
                            </div>
                        </div>
                    </div>

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
            `;

            document.getElementById('modalReportContainer').innerHTML = htmlContent;
            document.getElementById('printModalOverlay').style.display = 'flex';
        }

        function closePrintModal() {
            document.getElementById('printModalOverlay').style.display = 'none';
        }

        function downloadModalWord() {
            const targetElement = document.getElementById('activeReportPage');
            if (!targetElement) return;
            const fileName = targetElement.getAttribute('data-filename') || "Maternal_Medical_Record";

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
            setTimeout(() => {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 1000);
        }

        function openWithWordApp() {
            const targetElement = document.getElementById('activeReportPage');
            if (!targetElement) return;
            const fileName = targetElement.getAttribute('data-filename') || "Maternal_Medical_Record";

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
            
            setTimeout(() => {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 1000);
        }
    </script>

</body>
</html>