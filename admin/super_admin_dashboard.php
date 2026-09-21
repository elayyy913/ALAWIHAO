<?php
session_start();
include '../db_connect.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: login.php");
    exit();
}

// --- WORKER APPROVAL / REJECTION ---
// Inalis na ang lokal na handler dito. Ang approve_worker_id / remove_worker_id
// ay dapat na pumunta sa process_verification.php (mas kumpleto ang logic doon:
// hinahawakan din nito ang health_workers table, hindi lang ang users table).
// Sa verification pad(s), gawin ang links/buttons na:
//   process_verification.php?approve_worker_id=...&redirect=super_admin_dashboard.php
//   process_verification.php?remove_worker_id=...&redirect=super_admin_dashboard.php

// Para sa notification badge sa sidebar
$pending_workers_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0;

// --- FETCH COUNTS ---
$total_newborns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status='Approved'"))['t'] ?? 0;
$total_pregnant = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status='Approved'"))['t'] ?? 0;
$total_patients = $total_newborns + $total_pregnant;
$total_workers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Approved'"))['t'] ?? 0;

$total_pending = (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status='Pending'"))['t'] ?? 0) + 
                 (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status='Pending'"))['t'] ?? 0) +
                 (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users WHERE role='Admin' AND status='Pending'"))['t'] ?? 0);

// --- COMPUTATION PARA SA VISUAL PERCENTAGE RATES (MATERNAL & CHILD REGISTRATION) ---
// Maternal Metrics
$mat_total_reg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration"))['t'] ?? 0;
$mat_approved = $total_pregnant;
$mat_not_success = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM maternal_registration WHERE status != 'Approved'"))['t'] ?? 0;
$mat_success_deg = ($mat_total_reg > 0) ? (($mat_approved / $mat_total_reg) * 360) : 0;
$mat_success_pct = ($mat_total_reg > 0) ? round(($mat_approved / $mat_total_reg) * 100) : 0;
$mat_unsuccess_pct = ($mat_total_reg > 0) ? round(($mat_not_success / $mat_total_reg) * 100) : 0;

// Child/Infant Metrics
$child_total_reg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children"))['t'] ?? 0;
$child_approved = $total_newborns;
$child_not_success = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM children WHERE status != 'Approved'"))['t'] ?? 0;
$child_success_deg = ($child_total_reg > 0) ? (($child_approved / $child_total_reg) * 360) : 0;
$child_success_pct = ($child_total_reg > 0) ? round(($child_approved / $child_total_reg) * 100) : 0;
$child_unsuccess_pct = ($child_total_reg > 0) ? round(($child_not_success / $child_total_reg) * 100) : 0;
// ---------------------------------------------------------------------

// --- COMPUTATION PARA SA SCHEDULE SUCCESS RATES (NEW ADDITION) ---
$sched_mat_total = 0; $sched_mat_completed = 0; $sched_mat_not_success = 0;
$sched_mat_success_deg = 0; $sched_mat_success_pct = 0; $sched_mat_unsuccess_pct = 0;

$sched_child_total = 0; $sched_child_completed = 0; $sched_child_not_success = 0;
$sched_child_success_deg = 0; $sched_child_success_pct = 0; $sched_child_unsuccess_pct = 0;

if (!function_exists('check_table_exists')) {
    function check_table_exists($conn, $table_name) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
        return $result && mysqli_num_rows($result) > 0;
    }
}

if (check_table_exists($conn, 'schedules')) {
    // Maternal Schedules Success Metrics
    $sched_mat_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM schedules WHERE category='Maternal'"))['t'] ?? 0;
    $sched_mat_completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM schedules WHERE category='Maternal' AND LOWER(status)='completed'"))['t'] ?? 0;
    $sched_mat_not_success = $sched_mat_total - $sched_mat_completed;
    $sched_mat_success_deg = ($sched_mat_total > 0) ? (($sched_mat_completed / $sched_mat_total) * 360) : 0;
    $sched_mat_success_pct = ($sched_mat_total > 0) ? round(($sched_mat_completed / $sched_mat_total) * 100) : 0;
    $sched_mat_unsuccess_pct = ($sched_mat_total > 0) ? round(($sched_mat_not_success / $sched_mat_total) * 100) : 0;

    // Child Schedules / Immunization Success Metrics
    $sched_child_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM schedules WHERE category='Child'"))['t'] ?? 0;
    $sched_child_completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM schedules WHERE category='Child' AND LOWER(status)='completed'"))['t'] ?? 0;
    $sched_child_not_success = $sched_child_total - $sched_child_completed;
    $sched_child_success_deg = ($sched_child_total > 0) ? (($sched_child_completed / $sched_child_total) * 360) : 0;
    $sched_child_success_pct = ($sched_child_total > 0) ? round(($sched_child_completed / $sched_child_total) * 100) : 0;
    $sched_child_unsuccess_pct = ($sched_child_total > 0) ? round(($sched_child_not_success / $sched_child_total) * 100) : 0;
}
// ---------------------------------------------------------------------

// --- FETCH LISTS ---
$pending_workers = mysqli_query($conn, "SELECT * FROM users WHERE role='Admin' AND status='Pending' ORDER BY created_at DESC");
$pending_list = mysqli_query($conn, "SELECT * FROM children WHERE status='Pending' ORDER BY created_at DESC");

$pending_preg_list = mysqli_query($conn, "SELECT *, 
    CONCAT(COALESCE(street,''), ' ', COALESCE(barangay,''), ' ', COALESCE(municipality,'')) AS computed_address,
    CONCAT(COALESCE(spouse_fname,''), ' ', COALESCE(spouse_lname,'')) AS computed_spouse 
    FROM maternal_registration WHERE status='Pending' ORDER BY created_at DESC");

// --- FETCH UPCOMING SCHEDULES PARA SA SUPER ADMIN ---
$upcoming_maternal = [];
$upcoming_infant = [];

if (check_table_exists($conn, 'schedules')) {
    // Maternal Schedules Query
    $mat_upcoming_q = mysqli_query($conn, "
        SELECT id, schedule_date, schedule_time, service_type, status, patient_name AS full_name 
        FROM schedules 
        WHERE category = 'Maternal' AND LOWER(status) != 'completed' 
        ORDER BY schedule_date ASC, schedule_time ASC LIMIT 5
    ");
    if ($mat_upcoming_q) {
        while ($row = mysqli_fetch_assoc($mat_upcoming_q)) {
            $upcoming_maternal[] = $row;
        }
    }

    // Child Schedules Query
    $inf_upcoming_q = mysqli_query($conn, "
        SELECT id, patient_name as child_name, schedule_date, schedule_time, service_type as vaccine_type, status
        FROM schedules 
        WHERE category = 'Child' AND LOWER(status) != 'completed' 
        ORDER BY schedule_date ASC, schedule_time ASC LIMIT 5
    ");
    if ($inf_upcoming_q) {
        while ($row = mysqli_fetch_assoc($inf_upcoming_q)) {
            $upcoming_infant[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard | Alawihao</title>
    <style>
        :root { --sage: #8DAE74; --dark-sage: #5A6B47; --beige: #F9F9F4; --white: #FFFFFF; --text: #2D2D2D; --border: #E1E1D7; }
        body { font-family: 'Inter', sans-serif; margin: 0; background-color: var(--beige); color: var(--text); display: flex; }
        
        .main-content { flex-grow: 1; padding: 40px; padding-top: 70px; box-sizing: border-box; width: 100%; margin-left: 280px; transition: margin-left 0.3s ease-in-out; }
        .page-header { border-bottom: 2px solid var(--border); padding-bottom: 15px; margin-bottom: 30px; }
        .page-header h1 { color: var(--dark-sage); font-size: 1.8rem; margin: 0; }
        
.stats-grid { 
        display: grid; 
        grid-template-columns: repeat(5, 1fr); 
        gap: 20px; 
        margin-bottom: 30px; 
        width: 100%;
        box-sizing: border-box;
    }
    
    .stat-card { 
        background: var(--white); 
        padding: 22px; 
        border-radius: 10px; 
        border: 1px solid var(--border);
        border-top: 4px solid var(--sage); 
        box-shadow: var(--shadow-subtle); 
        transition: all 0.3s ease-in-out;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card { 
        background: var(--white); 
        padding: 22px; 
        border-radius: 10px; 
        border: 1px solid var(--border);
        border-top: 4px solid var(--sage); 
        box-shadow: var(--shadow-subtle); 
        transition: all 0.25s ease-in-out;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-4px); /* Mag-aabang o mag-aangat pataas nang konti (pop-up effect) */
        background: linear-gradient(135deg, #FFFFFF 0%, #F0F5EC 100%);
        border: 2px solid var(--dark-sage); /* Mas makapal (2px) at mas matingkad na kulay sa lahat ng gilid */
        box-shadow: 0 10px 25px rgba(90, 107, 71, 0.15);
    }
    
    .stat-card h2 { 
        margin: 0; 
        font-size: 1.5rem; 
        font-weight: 700;
        color: var(--text);
    }
    .analytic-card { 
        background: #FAFAF7; 
        border: 1px solid #EBEBE3; 
        border-radius: 8px; 
        padding: 20px; 
        display: flex; 
        align-items: center; 
        gap: 20px; 
        position: relative;
        overflow: hidden; /* Kailangan para hindi lumampas ang glow */
        transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.25s ease-in-out;
    }
    .analytic-card::before {
        content: '';
        position: absolute;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, rgba(141, 174, 116, 0.25) 0%, rgba(141, 174, 116, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
        transform: translate(-50%, -50%);
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 1;
    }
    .analytic-card:hover {
        transform: translateY(-3px);
        border-color: var(--sage);
        box-shadow: 0 8px 20px rgba(141, 174, 116, 0.12);
    }
    .analytic-card { 
        background: #FAFAF7; 
        border: 1px solid #EBEBE3; 
        border-radius: 8px; 
        padding: 20px; 
        display: flex; 
        align-items: center; 
        gap: 20px; 
        position: relative;
        overflow: hidden; 
        transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.25s ease-in-out;
    }

    .analytic-card:hover {
        transform: translateY(-3px);
        border-color: var(--sage);
        box-shadow: 0 8px 22px rgba(141, 174, 116, 0.2);
    }

    .analytic-card > * {
        position: relative;
        z-index: 2;
    }

    .analytic-card:hover::before {
        opacity: 1;
    }

    .analytic-card > * {
        position: relative;
        z-index: 2;
    }
        
        /* DONUT GRAPHIC VISUAL PERCENTAGE SECTION */
        .analytics-section { background: var(--white); padding: 25px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .analytics-section h3 { font-size: 1.1rem; color: var(--dark-sage); border-left: 4px solid var(--sage); padding-left: 10px; margin-top: 0; margin-bottom: 20px; }
        .analytics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        
        /* DONUT CHART STYLING */
        .donut-chart {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-shrink: 0;
        }
        .donut-hole {
            width: 75px;
            height: 75px;
            background: #FAFAF7;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            color: #2D2D2D;
        }
        .donut-hole span { font-size: 0.65rem; color: #666; font-weight: normal; }

        .analytic-info { display: flex; flex-direction: column; gap: 6px; flex-grow: 1; }
        .analytic-info h5 { margin: 0; font-size: 0.9rem; color: var(--dark-sage); text-transform: uppercase; }
        .analytic-legend { display: flex; flex-direction: column; font-size: 0.8rem; gap: 4px; color: #555; }
        .legend-item { display: flex; align-items: center; gap: 6px; }
        .color-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; align-items: start; }
        .left-column, .right-column { display: flex; flex-direction: column; gap: 25px; width: 100%; min-width: 0; }

        .table-container { background: var(--white); padding: 25px; border-radius: 8px; border: 1px solid var(--border); width: 100%; box-sizing: border-box; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .table-container h3 { font-size: 1.1rem; color: var(--dark-sage); border-left: 4px solid var(--sage); padding-left: 10px; margin-top: 0; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #F4F4ED; padding: 12px; font-size: 0.75rem; text-transform: uppercase; color: #666; }
        td { padding: 12px; border-bottom: 1px solid #F0F0F0; font-size: 0.85rem; }
        
        .btn-approve { background: var(--sage); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.7rem; display: inline-block; border: none; cursor: pointer; }
        .btn-reject { background: #e74c3c; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.7rem; margin-left: 5px; display: inline-block; border: none; cursor: pointer; }

        .schedule-card-list { display: flex; flex-direction: column; gap: 15px; }
        .schedule-item { background: #FAFAF7; border: 1px solid #EBEBE3; border-radius: 6px; padding: 15px; position: relative; }
        .schedule-item.maternal-border { border-left: 4px solid var(--sage); }
        .schedule-item.infant-border { border-left: 4px solid #e74c3c; }
        
        .sched-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .sched-date { font-weight: bold; font-size: 0.95rem; color: #2C3E50; }
        .status-badge { background: #E2E8F0; color: #4A5568; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase; }
        
        .sched-patient-name { font-size: 1rem; font-weight: bold; color: #2D2D2D; margin-bottom: 4px; text-transform: capitalize; }
        .sched-type { font-size: 0.85rem; color: #666; margin-bottom: 12px; }
        
        .sched-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .btn-mark-done { background: #D1E7DD; color: #0F5132; border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; cursor: pointer; }
        .btn-mark-done:hover { background: #badbcc; }
        
        .resched-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .date-input { padding: 5px 8px; border: 1px solid #CCC; border-radius: 4px; font-size: 0.8rem; font-family: inherit; background: white; }
        .btn-resched { background: #FDE68A; color: #78350F; border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; cursor: pointer; }
        .btn-resched:hover { background: #FCD34D; }

        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
        .modal-content { background: white; margin: 3% auto; padding: 30px; border-radius: 8px; width: 850px; position: relative; max-height: 90vh; overflow-y: auto; box-sizing: border-box; }
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 10px; }
        .form-group label { font-size: 0.75rem; font-weight: bold; color: #555; margin-bottom: 4px; text-transform: uppercase; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="date"], .form-group select, .form-group textarea { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85rem; font-family: inherit; box-sizing: border-box; background: white; }
        
        .patient-info-box { background: #F4F4ED; border: 1px solid var(--border); padding: 15px; border-radius: 6px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

        .checkbox-group { background: #FAFAFA; border: 1px solid #E1E1D7; padding: 10px; border-radius: 4px; max-height: 140px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
        .checkbox-label { font-size: 0.8rem; display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text); font-weight: normal; text-transform: none; }
        .checkbox-label input { width: 15px; height: 15px; cursor: pointer; }

        .section-tag { background: #F4F4ED; padding: 6px 12px; font-size: 0.8rem; font-weight: bold; color: var(--dark-sage); border-radius: 4px; margin: 15px 0 10px 0; border-left: 4px solid var(--sage); }

        .page-header {
        margin-left: 50px; 
        border-bottom: 2px solid var(--border);
        padding-bottom: 15px;
        margin-bottom: 30px;
    }
    </style>
    
</head>
<body>

<?php include 'super_admin_sidebar.php'; ?>

<div class="main-content" id="mainDashboard">
    <div class="page-header">
        <h1>Super Admin Dashboard</h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><h4>TOTAL PATIENTS</h4><h2><?php echo $total_patients; ?></h2></div>
        <div class="stat-card"><h4>APPROVED INFANTS</h4><h2><?php echo $total_newborns; ?></h2></div>
        <div class="stat-card"><h4>APPROVED MATERNAL</h4><h2><?php echo $total_pregnant; ?></h2></div>
        <div class="stat-card" style="border-top-color:#f39c12;"><h4>FOR APPROVAL</h4><h2 style="color:#f39c12;"><?php echo $total_pending; ?></h2></div>
        <div class="stat-card"><h4>STAFF WORKERS</h4><h2><?php echo $total_workers; ?></h2></div>
    </div>

    <!-- DONUT GRAPHIC VISUAL PERCENTAGE RATE SECTION (REGISTRATION) -->
    <div class="analytics-section">
        <h3>Registration & Verification Performance Rate</h3>
        <div class="analytics-grid">
            
            <!-- Maternal Donut Card -->
            <div class="analytic-card">
                <div class="donut-chart" style="background: conic-gradient(#27ae60 0deg <?php echo $mat_success_deg; ?>deg, #e74c3c <?php echo $mat_success_deg; ?>deg 360deg);">
                    <div class="donut-hole">
                        <?php echo $mat_success_pct; ?>%
                        <span>Verified</span>
                    </div>
                </div>
                <div class="analytic-info">
                    <h5>Maternal Registration</h5>
                    <div class="analytic-legend">
                        <span class="legend-item"><i class="color-dot" style="background:#27ae60;"></i> Verified: <strong><?php echo $mat_approved; ?></strong> (<?php echo $mat_success_pct; ?>%)</span>
                        <span class="legend-item"><i class="color-dot" style="background:#e74c3c;"></i> Unsuccessful/Pending: <strong><?php echo $mat_not_success; ?></strong> (<?php echo $mat_unsuccess_pct; ?>%)</span>
                        <span style="font-size: 0.75rem; color: #888; margin-top:2px;">Total Reg: <?php echo $mat_total_reg; ?></span>
                    </div>
                </div>
            </div>

            <!-- Child Donut Card -->
            <div class="analytic-card">
                <div class="donut-chart" style="background: conic-gradient(#27ae60 0deg <?php echo $child_success_deg; ?>deg, #e74c3c <?php echo $child_success_deg; ?>deg 360deg);">
                    <div class="donut-hole">
                        <?php echo $child_success_pct; ?>%
                        <span>Verified</span>
                    </div>
                </div>
                <div class="analytic-info">
                    <h5>Child / Infant Registration</h5>
                    <div class="analytic-legend">
                        <span class="legend-item"><i class="color-dot" style="background:#27ae60;"></i> Verified: <strong><?php echo $child_approved; ?></strong> (<?php echo $child_success_pct; ?>%)</span>
                        <span class="legend-item"><i class="color-dot" style="background:#e74c3c;"></i> Unsuccessful/Pending: <strong><?php echo $child_not_success; ?></strong> (<?php echo $child_unsuccess_pct; ?>%)</span>
                        <span style="font-size: 0.75rem; color: #888; margin-top:2px;">Total Reg: <?php echo $child_total_reg; ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- SCHEDULE & VACCINATION SUCCESS RATE DONUT SECTION (NEW ADDITION) -->
    <div class="analytics-section">
        <h3>Schedule & Vaccination Success Rate</h3>
        <div class="analytics-grid">
            
            <!-- Maternal Schedules Donut Card -->
            <div class="analytic-card">
                <div class="donut-chart" style="background: conic-gradient(#27ae60 0deg <?php echo $sched_mat_success_deg; ?>deg, #e74c3c <?php echo $sched_mat_success_deg; ?>deg 360deg);">
                    <div class="donut-hole">
                        <?php echo $sched_mat_success_pct; ?>%
                        <span>Success</span>
                    </div>
                </div>
                <div class="analytic-info">
                    <h5>Maternal Check-up Schedules</h5>
                    <div class="analytic-legend">
                        <span class="legend-item"><i class="color-dot" style="background:#27ae60;"></i> Completed: <strong><?php echo $sched_mat_completed; ?></strong> (<?php echo $sched_mat_success_pct; ?>%)</span>
                        <span class="legend-item"><i class="color-dot" style="background:#e74c3c;"></i> Pending/Unsuccessful: <strong><?php echo $sched_mat_not_success; ?></strong> (<?php echo $sched_mat_unsuccess_pct; ?>%)</span>
                        <span style="font-size: 0.75rem; color: #888; margin-top:2px;">Total Sched: <?php echo $sched_mat_total; ?></span>
                    </div>
                </div>
            </div>

            <!-- Child Immunization Schedules Donut Card -->
            <div class="analytic-card">
                <div class="donut-chart" style="background: conic-gradient(#27ae60 0deg <?php echo $sched_child_success_deg; ?>deg, #e74c3c <?php echo $sched_child_success_deg; ?>deg 360deg);">
                    <div class="donut-hole">
                        <?php echo $sched_child_success_pct; ?>%
                        <span>Success</span>
                    </div>
                </div>
                <div class="analytic-info">
                    <h5>Child Immunization Schedules</h5>
                    <div class="analytic-legend">
                        <span class="legend-item"><i class="color-dot" style="background:#27ae60;"></i> Vaccinated / Completed: <strong><?php echo $sched_child_completed; ?></strong> (<?php echo $sched_child_success_pct; ?>%)</span>
                        <span class="legend-item"><i class="color-dot" style="background:#e74c3c;"></i> Pending/Unsuccessful: <strong><?php echo $sched_child_not_success; ?></strong> (<?php echo $sched_child_unsuccess_pct; ?>%)</span>
                        <span style="font-size: 0.75rem; color: #888; margin-top:2px;">Total Sched: <?php echo $sched_child_total; ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="dashboard-grid">
        
        <!-- LEFT COLUMN -->
        <!-- Ang Worker, Newborn, at Maternal pending/verification tables ay kasalukuyang
             nasa loob pa ng maternal_verification_pad.php (TODO: ililipat pa ang Newborn
             pad papunta sa sarili nitong file, hal. infant_verification_pad.php).
             Umaasa ang $conn, $pending_workers, $pending_list, at $pending_preg_list
             (na naka-define sa itaas ng file na ito) sa loob ng mga include na ito. -->
        <?php include 'maternal_verification_pad.php'; ?>
        <?php // include 'infant_verification_pad.php'; -- idadagdag pagkatapos ma-split ?>

        <!-- RIGHT COLUMN -->
        <div class="right-column">s
            
            <!-- Upcoming Maternal Check-ups -->
            <div class="table-container">
                <h3>Upcoming Maternal Check-ups & Vaccinations</h3>
                <div class="schedule-card-list">
                    <?php if (!empty($upcoming_maternal)): foreach($upcoming_maternal as $sched): 
                        $patientName = $sched['full_name'] ?? $sched['client_name'] ?? 'N/A';
                        $serviceType = $sched['service_type'] ?? $sched['service'] ?? 'N/A';
                        $schedDate = $sched['schedule_date'] ?? date('Y-m-d');
                        $schedId = $sched['id'] ?? '';
                    ?>
                    <div class="schedule-item maternal-border">
                        <div class="sched-header-row">
                            <span class="sched-date"><?php echo htmlspecialchars($schedDate); ?></span>
                            <span class="status-badge">Pending</span>
                        </div>
                        <div class="sched-patient-name"><?php echo htmlspecialchars($patientName); ?></div>
                        <div class="sched-type">Type: <?php echo htmlspecialchars($serviceType); ?></div>
                        
                        <form method="POST" action="process_verification.php" class="sched-actions">
                            <input type="hidden" name="schedule_id" value="<?php echo $schedId; ?>">
                            <input type="hidden" name="redirect_to" value="super_admin_dashboard.php">
                            <button type="submit" name="mark_done_maternal" class="btn-mark-done">Mark Done</button>
                            <div class="resched-group">
                                <input type="date" name="new_date" class="date-input" required>
                                <button type="submit" name="reschedule_maternal" class="btn-resched">Resched</button>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; else: ?>
                        <p style="text-align: center; color: #777; font-size: 0.85rem; margin: 10px 0;">No upcoming maternal check-ups.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Child Vaccinations -->
            <div class="table-container">
                <h3>Upcoming Child Vaccinations</h3>
                <div class="schedule-card-list">
                    <?php if (!empty($upcoming_infant)): foreach($upcoming_infant as $sched): 
                        $infantName = $sched['full_name'] ?? $sched['child_name'] ?? 'N/A';
                        $vaccineType = $sched['service_type'] ?? $sched['service'] ?? 'N/A';
                        $schedDateFull = ($sched['schedule_date'] ?? date('Y-m-d')) . (!empty($sched['schedule_time']) ? ' (' . $sched['schedule_time'] . ')' : '');
                        $schedId = $sched['id'] ?? '';
                    ?>
                    <div class="schedule-item infant-border">
                        <div class="sched-header-row">
                            <span class="sched-date"><?php echo htmlspecialchars($schedDateFull); ?></span>
                        </div>
                        <div class="sched-patient-name"><?php echo htmlspecialchars($infantName); ?></div>
                        <div class="sched-type">Vaccine: <?php echo htmlspecialchars($vaccineType); ?></div>
                        
                        <form method="POST" action="process_verification.php" class="sched-actions">
                            <input type="hidden" name="schedule_id" value="<?php echo $schedId; ?>">
                            <input type="hidden" name="redirect_to" value="super_admin_dashboard.php">
                            <button type="submit" name="mark_done_infant" class="btn-mark-done">Mark Done</button>
                            <div class="resched-group">
                                <input type="date" name="new_date" class="date-input" required>
                                <button type="submit" name="reschedule_infant" class="btn-resched">Resched</button>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; else: ?>
                        <p style="text-align: center; color: #777; font-size: 0.85rem; margin: 10px 0;">No upcoming infant schedules.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const cards = document.querySelectorAll(".analytic-card");

        cards.forEach(card => {
            card.addEventListener("mousemove", (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                // Mas matingkad na sage spotlight effect (pinalakas ang opacity at radius)
               card.style.background = `radial-gradient(circle 170px at ${x}px ${y}px, rgba(141, 174, 116, 0.38), #FAFAF7 70%)`;
            });

            card.addEventListener("mouseleave", () => {
                card.style.background = "#FAFAF7"; // Babalik sa normal kapag lumabas ang cursor
            });
        });
    });

    // LIVE STREAMING / POLLING INTERVAL (Bawat 4 segundo)
    setInterval(function() {
        var nbModal = document.getElementById('newbornVerifyModal');
        var matModal = document.getElementById('verifyModal');
        if ((nbModal && nbModal.style.display === 'block') || (matModal && matModal.style.display === 'block')) {
            return; 
        }

        fetch('fetch_dashboard_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) return;

                // 1. Update Stat Cards
                document.querySelectorAll('.stat-card h2')[0].innerText = data.total_patients;
                document.querySelectorAll('.stat-card h2')[1].innerText = data.total_newborns;
                document.querySelectorAll('.stat-card h2')[2].innerText = data.total_pregnant;
                document.querySelectorAll('.stat-card h2')[3].innerText = data.total_pending;
                document.querySelectorAll('.stat-card h2')[4].innerText = data.total_workers;

                // 2. Update Registration Donut Graphics & Legends
                const matDonut = document.querySelectorAll('.donut-chart')[0];
                if(matDonut) {
                    matDonut.style.background = `conic-gradient(#27ae60 0deg ${data.mat_success_deg}deg, #e74c3c ${data.mat_success_deg}deg 360deg)`;
                    matDonut.querySelector('.donut-hole').innerHTML = `${data.mat_success_pct}%<span>Verified</span>`;
                }
                const matLegend = document.querySelectorAll('.analytic-info')[0];
                if(matLegend) {
                    matLegend.querySelector('.analytic-legend').innerHTML = `
                        <span class="legend-item"><i class="color-dot" style="background:#27ae60;"></i> Verified: <strong>${data.mat_approved}</strong> (${data.mat_success_pct}%)</span>
                        <span class="legend-item"><i class="color-dot" style="background:#e74c3c;"></i> Unsuccessful/Pending: <strong>${data.mat_not_success}</strong> (${data.mat_unsuccess_pct}%)</span>
                        <span style="font-size: 0.75rem; color: #888; margin-top:2px;">Total Reg: ${data.mat_total_reg}</span>
                    `;
                }

                const childDonut = document.querySelectorAll('.donut-chart')[1];
                if(childDonut) {
                    childDonut.style.background = `conic-gradient(#27ae60 0deg ${data.child_success_deg}deg, #e74c3c ${data.child_success_deg}deg 360deg)`;
                    childDonut.querySelector('.donut-hole').innerHTML = `${data.child_success_pct}%<span>Verified</span>`;
                }
                const childLegend = document.querySelectorAll('.analytic-info')[1];
                if(childLegend) {
                    childLegend.querySelector('.analytic-legend').innerHTML = `
                        <span class="legend-item"><i class="color-dot" style="background:#27ae60;"></i> Verified: <strong>${data.child_approved}</strong> (${data.child_success_pct}%)</span>
                        <span class="legend-item"><i class="color-dot" style="background:#e74c3c;"></i> Unsuccessful/Pending: <strong>${data.child_not_success}</strong> (${data.child_unsuccess_pct}%)</span>
                        <span style="font-size: 0.75rem; color: #888; margin-top:2px;">Total Reg: ${data.child_total_reg}</span>
                    `;
                }

                // 3. Update Tables and Schedule Lists smoothly
                const tables = document.querySelectorAll('.table-container table tbody');
                if(tables.length >= 3) {
                    tables[0].innerHTML = data.pending_workers_html;
                    tables[1].innerHTML = data.pending_newborns_html;
                    tables[2].innerHTML = data.pending_maternal_html;
                }

                const schedLists = document.querySelectorAll('.schedule-card-list');
                if(schedLists.length >= 2) {
                    schedLists[0].innerHTML = data.upcoming_maternal_html;
                    schedLists[1].innerHTML = data.upcoming_infant_html;
                }
            })
            .catch(error => console.log('Live sync paused or offline error:', error));
    }, 4000);
</script>

</body>
</html>