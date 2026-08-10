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
$user_role = $user_data['role']; 

// Choose sidebar based on role
$sidebar_to_include = ($user_role == 'Super Admin') ? 'super_admin_sidebar.php' : 'admin_sidebar.php';

// Check if child ID is passed
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_child_list.php");
    exit();
}

$child_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch Child Info
$child_query = mysqli_query($conn, "SELECT * FROM children WHERE id = '$child_id'");
$child = mysqli_fetch_assoc($child_query);

if (!$child) {
    echo "<script>alert('Child record not found!'); window.location='admin_child_list.php';</script>";
    exit();
}

// Fetch Immunization / Check-up History
$history_query = mysqli_query($conn, "SELECT * FROM infant_records WHERE child_id = '$child_id' ORDER BY vaccine_date DESC, created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Child Health Record - <?php echo htmlspecialchars($child['child_name']); ?></title>
    <style>
        :root {
            --sage: #8DAE74;
            --dark-sage: #6B8E55;
            --soft-sage: #F1F5ED;
            --text-main: #2D3748;
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #E2E8F0;
            margin: 0;
            display: flex;
            color: #1A202C;
        }

        #main {
            flex-grow: 1;
            padding: 30px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Top Bar Actions */
        .top-action-bar {
            width: 210mm; /* Match A4 width */
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .btn-back {
            color: #4A5568;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 5px;
            border: none;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-word { background: #D69E2E; color: white; }
        .btn-download { background: #3182CE; color: white; }
        .btn-print { background: #4A5568; color: white; }

        .filter-container {
            width: 210mm; /* Match A4 width */
            margin-bottom: 20px;
            background: #EDF2F7;
            padding: 10px 15px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-sizing: border-box;
        }

        .filter-container label {
            font-weight: 700;
            font-size: 0.85rem;
            color: #2D3748;
        }

        .filter-container select {
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid #CBD5E0;
            font-size: 0.85rem;
        }

        /* EXACT A4 PAGE SIZE STYLING FOR SCREEN */
        .paper-document {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm; /* Standard A4 Margins */
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            box-sizing: border-box;
            border-radius: 2px;
            position: relative;
        }

        .doc-header {
            text-align: center;
            margin-bottom: 15px;
        }

        .doc-header h4 {
            margin: 0;
            font-size: 0.75rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #4A5568;
        }

        .doc-header h2 {
            margin: 4px 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: #000;
        }

        .doc-header p {
            margin: 0;
            font-size: 0.8rem;
            color: #4A5568;
        }

        .doc-divider {
            border: none;
            border-top: 2px solid #000;
            margin: 12px 0 18px 0;
        }

        .doc-title {
            text-align: center;
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 20px;
            text-decoration: underline;
        }

        .section-title {
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            margin-bottom: 12px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px 15px;
            margin-bottom: 20px;
        }

        .info-group label {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #4A5568;
        }

        .info-group span {
            font-size: 0.82rem;
            font-weight: 600;
            color: #000;
        }

        /* History Table inside Paper Document */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 35px;
        }

        .doc-table th, .doc-table td {
            border: 1px solid #000;
            padding: 7px 9px;
            font-size: 0.78rem;
            text-align: left;
        }

        .doc-table th {
            background-color: #F7FAFC;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Signatures Section */
        .signatures-container {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }

        .sig-box {
            width: 220px;
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-bottom: 5px;
        }

        .sig-title {
            font-weight: 800;
            font-size: 0.72rem;
            text-transform: uppercase;
        }

        .sig-subtitle {
            font-size: 0.72rem;
            color: #4A5568;
        }

        /* PRINT MEDIA CONFIGURATION FOR EXACT A4 SETUP */
        @page {
            size: A4 portrait;
            margin: 0; /* Clear browser default print margins */
        }

        @media print {
            body { 
                background: white; 
                margin: 0;
            }
            #mainSidebar, .top-action-bar, .filter-container, .no-print { 
                display: none !important; 
            }
            #main { 
                padding: 0 !important; 
                margin: 0 !important; 
                width: 100% !important; 
            }
            .paper-document {
                box-shadow: none;
                padding: 15mm 20mm;
                width: 210mm;
                min-height: 297mm;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>

<?php include $sidebar_to_include; ?>

<div id="main">
    
    <!-- Top Action Bar -->
    <div class="top-action-bar no-print">
        <a href="javascript:history.back()" class="btn-back">← Back</a>
        <div class="action-buttons">
            <button class="btn-action btn-word" onclick="alert('Exporting to Word...')">Open with Word App</button>
            <button class="btn-action btn-download" onclick="window.print()">Download</button>
            <button class="btn-action btn-print" onclick="window.print()">print</button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-container no-print">
        <label>Filter:</label>
        <select onchange="filterRecords(this.value)">
            <option value="full">Full record</option>
            <option value="latest">Latest Check-up Only</option>
        </select>
    </div>

    <!-- Printable Paper Sheet (A4 Dimensions) -->
    <div class="paper-document">
        
        <!-- Document Header -->
        <div class="doc-header">
            <h4>MUNICIPALITY OF DAET • PROVINCE OF CAMARINES NORTE</h4>
            <h2>ALAWIHAO HEALTH CENTER</h2>
            <p>Brgy. Alawihao, Daet, Camarines Norte</p>
        </div>

        <hr class="doc-divider">

        <div class="doc-title">
            CHILD & INFANT HEALTH RECORD — CHECK-UP HISTORY
        </div>

        <!-- Section 1: Patient Information -->
        <div class="section-title">I. PERSONAL INFORMATION & REGISTRATION DETAILS</div>
        <div class="info-grid">
            <div class="info-group">
                <label>Pangalan ng Bata</label>
                <span><?php echo htmlspecialchars($child['child_name']); ?></span>
            </div>
            <div class="info-group">
                <label>Pangalan ng Ina</label>
                <span><?php echo htmlspecialchars($child['mother_name']); ?></span>
            </div>
            <div class="info-group">
                <label>Kasarian (Gender)</label>
                <span><?php echo htmlspecialchars($child['gender']); ?></span>
            </div>
            <div class="info-group">
                <label>Tirahan</label>
                <span><?php echo htmlspecialchars($child['address'] ?? 'Purok 6, Alawihao, Daet'); ?></span>
            </div>
            <div class="info-group">
                <label>Kaarawan (DOB)</label>
                <span><?php echo !empty($child['dob']) ? date("Y-m-d", strtotime($child['dob'])) : 'N/A'; ?></span>
            </div>
            <div class="info-group">
                <label>Numero ng Kontak</label>
                <span><?php echo htmlspecialchars($child['contact'] ?? 'N/A'); ?></span>
            </div>
        </div>

        <!-- Section 2: Immunization & Growth Records -->
        <div class="section-title">II. CHECK-UP & IMMUNIZATION RECORD DETAILS</div>
        
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Petsa</th>
                    <th>Bakunang Itinurok</th>
                    <th>Timbang (kg)</th>
                    <th>Taas (cm)</th>
                    <th>Nagturok / Health Worker</th>
                    <th>Pansin / Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if($history_query && mysqli_num_rows($history_query) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($history_query)): ?>
                    <tr>
                        <td><strong><?php echo date("Y-m-d", strtotime($row['vaccine_date'])); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['vaccine_taken']); ?></td>
                        <td><?php echo $row['weight_kg']; ?> kg</td>
                        <td><?php echo $row['height']; ?> cm</td>
                        <td><?php echo htmlspecialchars($row['administered_by'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['remarks'] ?? 'Walang naitalang remarks.'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:#718096; padding: 15px;">Walang naitalang record sa kasalukuyan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Section 3: Signatures Footer -->
        <div class="signatures-container">
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-title">NAG-HANDLE NA MIDWIFE / NURSE</div>
                <div class="sig-subtitle">Signature Over Printed Name & License No.</div>
            </div>

            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-title">MUNICIPAL HEALTH OFFICER / OIC</div>
                <div class="sig-subtitle">Alawihao Health Center</div>
            </div>
        </div>

    </div>

</div>

</body>
</html>