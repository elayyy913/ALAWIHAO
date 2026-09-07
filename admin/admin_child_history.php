<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../db_connect.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query_role = mysqli_query($conn, "SELECT role FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($query_role);
$user_role = $user_data['role'] ?? ''; 

// Choose sidebar based on role
$sidebar_to_include = (strtolower(trim($user_role)) == 'super admin' || strtolower(trim($user_role)) == 'superadmin') ? 'super_admin_sidebar.php' : 'admin_sidebar.php';

// Kung walang ID na napasa, i-redirect sa listahan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_child_hr.php");
    exit();
}

$child_id = mysqli_real_escape_string($conn, $_GET['id']);

// 1. Fetch Master Child Info galing sa 'children' table[cite: 4]
$child_query = mysqli_query($conn, "SELECT * FROM children WHERE id = '$child_id'");
$child = mysqli_fetch_assoc($child_query);

if (!$child) {
    echo "<script>alert('Child record not found!'); window.location='admin_child_hr.php';</script>";
    exit();
}

// 2. Fetch Check-up & Immunization History galing sa 'infant_records' table[cite: 4]
$history_query = mysqli_query($conn, "SELECT * FROM infant_records WHERE child_id = '$child_id' ORDER BY created_at DESC");
$history_records = [];
while ($row = mysqli_fetch_assoc($history_query)) {
    $history_records[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Child Health History - <?php echo htmlspecialchars($child['child_name']); ?></title>
    <style>
        :root {
            --sage: #8DAE74;
            --dark-sage: #6B8E55;
            --soft-sage: #F1F5ED;
            --text-main: #2D3748;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #F4F6F9;
            margin: 0;
            display: flex;
            color: var(--text-main);
        }

        #main {
            flex-grow: 1;
            padding: 30px;
            box-sizing: border-box;
            min-height: 100vh;
        }

        .page-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .page-header-bar h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #2D3748;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-back {
            background: #EDF2F7;
            color: #4A5568;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: background 0.2s;
        }

        .btn-back:hover {
            background: #E2E8F0;
        }

        .record-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #E2E8F0;
        }

        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 25px;
            background: #FAFBFC;
            border-bottom: 1px solid #E2E8F0;
        }

        .patient-name-title {
            grid-column: span 3;
            font-size: 1.1rem;
            font-weight: 800;
            color: #1A202C;
            margin-bottom: -10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #718096;
            margin-bottom: 4px;
        }

        .info-item span {
            font-size: 0.9rem;
            font-weight: 600;
            color: #2D3748;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .history-table th {
            background: #F7FAFC;
            color: #4A5568;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 14px 20px;
            border-bottom: 1px solid #E2E8F0;
        }

        .history-table td {
            padding: 15px 20px;
            font-size: 0.85rem;
            color: #2D3748;
            border-bottom: 1px solid #EDF2F7;
            vertical-align: middle;
        }

        .history-table tr:last-child td {
            border-bottom: none;
        }

        .btn-print-modal {
            background-color: var(--sage);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .btn-print-modal:hover {
            background-color: var(--dark-sage);
        }

        /* MODAL STYLING */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: flex-start;
            z-index: 2000;
            overflow-y: auto;
            padding: 30px 20px;
            box-sizing: border-box;
        }

        .modal-container-wrapper {
            background: #E2E8F0;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: auto;
        }

        .modal-top-toolbar {
            width: 210mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #EDF2F7;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        .toolbar-left, .toolbar-right { display: flex; gap: 8px; }

        .btn-close-modal {
            background: #E53E3E; color: white; border: none; padding: 6px 12px;
            border-radius: 4px; font-weight: 700; font-size: 0.75rem; cursor: pointer;
        }

        .btn-tool {
            background: #4A5568; color: white; border: none; padding: 6px 12px;
            border-radius: 4px; font-weight: 600; font-size: 0.75rem; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
        }
        .btn-tool.print-btn { background: #3182CE; }

        .modal-paper-sheet {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            box-sizing: border-box;
            position: relative;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .doc-header { text-align: center; margin-bottom: 15px; }
        .doc-header h4 { margin: 0; font-size: 0.75rem; letter-spacing: 1px; text-transform: uppercase; color: #4A5568; }
        .doc-header h2 { margin: 4px 0; font-size: 1.25rem; font-weight: 800; color: #000; }
        .doc-header p { margin: 0; font-size: 0.8rem; color: #4A5568; }
        .doc-divider { border: none; border-top: 2px solid #000; margin: 12px 0 18px 0; }
        .doc-title { text-align: center; font-weight: 800; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 20px; text-decoration: underline; }
        .section-title { font-weight: 800; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 12px; border-bottom: 1px solid #000; padding-bottom: 3px; }
        .info-grid-modal { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px 15px; margin-bottom: 25px; }
        .info-group-modal label { display: block; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; color: #4A5568; }
        .info-group-modal span { font-size: 0.82rem; font-weight: 600; color: #000; }
        .doc-table-modal { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .doc-table-modal th, .doc-table-modal td { border: 1px solid #000; padding: 8px 10px; font-size: 0.78rem; text-align: left; }
        .doc-table-modal th { background-color: #F7FAFC; font-weight: 700; text-transform: uppercase; }
        .signatures-container { display: flex; justify-content: space-between; margin-top: 60px; }
        .sig-box { width: 220px; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-bottom: 5px; }
        .sig-title { font-weight: 800; font-size: 0.72rem; text-transform: uppercase; }
        .sig-subtitle { font-size: 0.72rem; color: #4A5568; }

        @page { size: A4 portrait; margin: 0; }
        @media print {
            body * { visibility: hidden; }
            .modal-paper-sheet, .modal-paper-sheet * { visibility: visible; }
            .modal-overlay { position: absolute; left: 0; top: 0; background: white; padding: 0; }
            .modal-container-wrapper { background: none; padding: 0; box-shadow: none; }
            .modal-top-toolbar { display: none !important; }
            .modal-paper-sheet { box-shadow: none; width: 210mm; min-height: 297mm; padding: 15mm 20mm; margin: 0; }
        }
    </style>
</head>
<body>

<?php include $sidebar_to_include; ?>

<div id="main">
    
    <!-- Top Header Bar with Print & Back Buttons -->
    <div class="page-header-bar">
        <h2>Child Health History & Immunization Records</h2>
        <div class="header-actions">
            <button class="btn-print-modal" onclick="openPrintModal()">
                🖨️ Print Record
            </button>
            <a href="admin_child_hr.php" class="btn-back">← Back to List</a>
        </div>
    </div>

    <div class="record-card">
        
        <!-- Patient Information Header Grid -->
        <div class="patient-info-grid">
            <div class="patient-name-title">
                <?php echo htmlspecialchars($child['child_name']); ?>
            </div>
            
            <div class="info-item">
                <label>Edad / Kapanganakan</label>
                <span>
                    <?php 
                        if(!empty($child['birth_date'])) {
                            $dob = new DateTime($child['birth_date']);
                            $today = new DateTime();
                            $age = $today->diff($dob);
                            echo $age->y . " yrs old (" . $child['birth_date'] . ")";
                        } else {
                            echo 'N/A';
                        }
                    ?>
                </span>
            </div>

            <div class="info-item">
                <label>Kasarian (Gender)</label>
                <span><?php echo htmlspecialchars($child['gender'] ?? 'N/A'); ?></span>
            </div>

            <div class="info-item">
                <label>Blood Type</label>
                <span><?php echo htmlspecialchars($child['blood_type'] ?? 'N/A'); ?></span>
            </div>

            <div class="info-item">
                <label>Pangalan ng Ina</label>
                <span><?php echo htmlspecialchars($child['mother_name'] ?? 'N/A'); ?></span>
            </div>

            <div class="info-item">
                <label>Pangalan ng Ama</label>
                <span><?php echo htmlspecialchars($child['father_name'] ?? 'N/A'); ?></span>
            </div>

            <div class="info-item">
                <label>Tirahan</label>
                <span><?php echo htmlspecialchars($child['address'] ?? 'N/A'); ?><?php echo !empty($child['barangay']) ? ', ' . htmlspecialchars($child['barangay']) : ''; ?></span>
            </div>
        </div>

        <!-- Check-up & Immunization History Table (Clean layout without action buttons per row) -->
        <div class="table-responsive">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Petsa ng Check-up</th>
                        <th>Bakunang Itinurok</th>
                        <th>Timbang / Taas</th>
                        <th>Remarks / Notes</th>
                        <th>Nagturok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($history_records)): ?>
                        <?php foreach ($history_records as $rec): ?>
                        <tr>
                            <td><strong><?php echo !empty($rec['vaccine_date']) ? $rec['vaccine_date'] : (!empty($rec['created_at']) ? date("Y-m-d", strtotime($rec['created_at'])) : 'N/A'); ?></strong></td>
                            <td><?php echo htmlspecialchars($rec['vaccine_taken'] ?? 'Wala'); ?></td>
                            <td><?php echo htmlspecialchars($rec['weight_kg'] ?? '0'); ?> kg / <?php echo htmlspecialchars($rec['height'] ?? 'N/A'); ?> cm</td>
                            <td><?php echo htmlspecialchars($rec['remarks'] ?? 'None'); ?></td>
                            <td><?php echo htmlspecialchars($rec['administered_by'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td><strong><?php echo !empty($child['created_at']) ? date("Y-m-d", strtotime($child['created_at'])) : 'N/A'; ?></strong></td>
                            <td><?php echo htmlspecialchars($child['vaccine_taken'] ?? 'Wala pang naitalang bakuna'); ?></td>
                            <td><?php echo htmlspecialchars($child['weight_kg'] ?? $child['weight'] ?? '0'); ?> kg / <?php echo htmlspecialchars($child['height_cm'] ?? 'N/A'); ?> cm</td>
                            <td><?php echo htmlspecialchars($child['vaccination_status'] ?? 'Initial Registration'); ?></td>
                            <td><?php echo htmlspecialchars($child['administered_by'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<!-- MODAL POPUP FOR A4 PRINT PREVIEW -->
<div class="modal-overlay" id="printModal">
    <div class="modal-container-wrapper">
        
        <div class="modal-top-toolbar">
            <div class="toolbar-left">
                <button class="btn-close-modal" onclick="closePrintModal()">✕ Close Window</button>
            </div>
            <div class="toolbar-right">
                <button class="btn-tool print-btn" onclick="window.print()">🖨️ Print Document</button>
            </div>
        </div>

        <div class="modal-paper-sheet">
            <div class="doc-header">
                <h4>MUNICIPALITY OF DAET • PROVINCE OF CAMARINES NORTE</h4>
                <h2>ALAWIHAO HEALTH CENTER</h2>
                <p>Brgy. Alawihao, Daet, Camarines Norte</p>
            </div>

            <hr class="doc-divider">

            <div class="doc-title">
                CHILD & INFANT HEALTH RECORD — CHECK-UP HISTORY
            </div>

            <div class="section-title">I. PERSONAL INFORMATION & REGISTRATION DETAILS</div>
            <div class="info-grid-modal">
                <div class="info-group-modal">
                    <label>Pangalan ng Bata</label>
                    <span><?php echo htmlspecialchars($child['child_name']); ?></span>
                </div>
                <div class="info-group-modal">
                    <label>Pangalan ng Ina</label>
                    <span><?php echo htmlspecialchars($child['mother_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-group-modal">
                    <label>Pangalan ng Ama</label>
                    <span><?php echo htmlspecialchars($child['father_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-group-modal">
                    <label>Kasarian (Gender)</label>
                    <span><?php echo htmlspecialchars($child['gender'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-group-modal">
                    <label>Kaarawan (DOB)</label>
                    <span><?php echo !empty($child['birth_date']) ? date("Y-m-d", strtotime($child['birth_date'])) : 'N/A'; ?></span>
                </div>
                <div class="info-group-modal">
                    <label>Blood Type</label>
                    <span><?php echo htmlspecialchars($child['blood_type'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-group-modal">
                    <label>Lugar ng Kapanganakan</label>
                    <span><?php echo htmlspecialchars($child['place_of_birth'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-group-modal">
                    <label>Health Center</label>
                    <span>Alawihao Health Center</span>
                </div>
                <div class="info-group-modal" style="grid-column: span 3;">
                    <label>Tirahan</label>
                    <span><?php echo htmlspecialchars($child['address'] ?? 'N/A'); ?><?php echo !empty($child['barangay']) ? ', ' . htmlspecialchars($child['barangay']) : ''; ?></span>
                </div>
            </div>

            <div class="section-title">II. CHECK-UP & IMMUNIZATION RECORD DETAILS</div>
            
            <table class="doc-table-modal">
                <thead>
                    <tr>
                        <th>Petsa</th>
                        <th>Bakunang Itinurok</th>
                        <th>Timbang</th>
                        <th>Taas</th>
                        <th>Nagturok / Health Worker</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($history_records)): ?>
                        <?php foreach ($history_records as $rec): ?>
                        <tr>
                            <td><strong><?php echo !empty($rec['vaccine_date']) ? $rec['vaccine_date'] : (!empty($rec['created_at']) ? date("Y-m-d", strtotime($rec['created_at'])) : 'N/A'); ?></strong></td>
                            <td><?php echo htmlspecialchars($rec['vaccine_taken'] ?? 'Wala'); ?></td>
                            <td><?php echo htmlspecialchars($rec['weight_kg'] ?? '0'); ?> kg</td>
                            <td><?php echo htmlspecialchars($rec['height'] ?? 'N/A'); ?> cm</td>
                            <td><?php echo htmlspecialchars($rec['administered_by'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($rec['remarks'] ?? 'None'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td><strong><?php echo !empty($child['created_at']) ? date("Y-m-d", strtotime($child['created_at'])) : 'N/A'; ?></strong></td>
                            <td><?php echo htmlspecialchars($child['vaccine_taken'] ?? 'Wala pang naitalang bakuna'); ?></td>
                            <td><?php echo htmlspecialchars($child['weight_kg'] ?? $child['weight'] ?? '0'); ?> kg</td>
                            <td><?php echo htmlspecialchars($child['height_cm'] ?? 'N/A'); ?> cm</td>
                            <td><?php echo htmlspecialchars($child['administered_by'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($child['vaccination_status'] ?? 'None'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="signatures-container">
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-title">NAG-HANDLE NA MIDWIFE / NURSE</div>
                    <div class="sig-subtitle">Signature Over Printed Name</div>
                </div>

                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-title">MUNICIPAL HEALTH OFFICER / OIC</div>
                    <div class="sig-subtitle">Alawihao Health Center</div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function openPrintModal() {
        document.getElementById('printModal').style.display = 'flex';
    }

    function closePrintModal() {
        document.getElementById('printModal').style.display = 'none';
    }
</script>

</body>
</html>