<?php
session_start();
include '../db_connect.php';

// Check if logged in and if admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    header("Location: login.php");
    exit();
}

$message = "";

// ---------------------------------------------------------
// HANDLE FORM SUBMISSIONS (Add, Update, Delete)
// ---------------------------------------------------------

// Add New Vaccine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vaccine'])) {
    $vaccine_name = mysqli_real_escape_string($conn, $_POST['vaccine_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $total_received = max(0, intval($_POST['total_received']));
    $available_stock = max(0, intval($_POST['available_stock']));
    
    $stock_date = isset($_POST['stock_date']) ? mysqli_real_escape_string($conn, $_POST['stock_date']) : date('Y-m-d');
    $stock_time = isset($_POST['stock_time']) ? mysqli_real_escape_string($conn, $_POST['stock_time']) : date('H:i');
    $stock_in_datetime = $stock_date . ' ' . $stock_time . ':00';

    $received_by = trim($_POST['received_by'] ?? '');
    $provided_by = trim($_POST['provided_by'] ?? '');

    if (empty($received_by) || empty($provided_by) || empty($stock_date) || empty($stock_time)) {
        $message = "Error: All fields including 'Provided By', 'Received By', Date, and Time are required!";
    } else {
        $received_by_esc = mysqli_real_escape_string($conn, $received_by);
        $provided_by_esc = mysqli_real_escape_string($conn, $provided_by);

        $sql = "INSERT INTO vaccines (vaccine_name, description, category, total_received, available_stock, stock_in_date, received_by, provided_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if (!$conn->begin_transaction()) {
            $message = "Error: Could not start the inventory update: " . $conn->error;
        } elseif ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssiiiss", $vaccine_name, $description, $category, $total_received, $available_stock, $stock_in_datetime, $received_by_esc, $provided_by_esc);
            if ($stmt->execute()) {
                $new_vaccine_id = $stmt->insert_id;
                if ($total_received > 0) {
                    $log_sql = "INSERT INTO vaccine_logs (vaccine_id, action_type, quantity, remarks, transaction_date, recorded_by) VALUES (?, 'IN', ?, ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    if (!$log_stmt) {
                        $error = $conn->error;
                        $conn->rollback();
                        $message = "Error recording vaccine stock history: " . $error;
                    } else {
                        $remarks = "New stock received from " . $provided_by;
                        $log_stmt->bind_param("iisis", $new_vaccine_id, $total_received, $remarks, $stock_in_datetime, $received_by_esc);
                        if (!$log_stmt->execute()) {
                            $error = $conn->error;
                            $conn->rollback();
                            $message = "Error recording vaccine stock history: " . $error;
                        } else {
                            if ($conn->commit()) {
                                $message = "Vaccine added successfully!";
                            } else {
                                $error = $conn->error;
                                $conn->rollback();
                                $message = "Error saving vaccine update: " . $error;
                            }
                        }
                        if ($log_stmt) {
                            $log_stmt->close();
                        }
                    }
                } elseif ($conn->commit()) {
                    $message = "Vaccine added successfully! No stock movement was recorded because no stock was received.";
                } else {
                    $error = $conn->error;
                    $conn->rollback();
                    $message = "Error saving vaccine update: " . $error;
                }
            } else {
                $error = $conn->error;
                $conn->rollback();
                $message = "Error adding vaccine: " . $error;
            }
            $stmt->close();
        } else {
            $error = $conn->error;
            $conn->rollback();
            $message = "Error preparing vaccine inventory update: " . $error;
        }
    }
}

// Update Existing Vaccine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_vaccine'])) {
    $id = intval($_POST['vaccine_id']);
    $vaccine_name = mysqli_real_escape_string($conn, $_POST['vaccine_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $total_received = max(0, intval($_POST['total_received']));
    $available_stock = max(0, intval($_POST['available_stock']));
    
    $stock_date = isset($_POST['stock_date']) ? mysqli_real_escape_string($conn, $_POST['stock_date']) : date('Y-m-d');
    $stock_time = isset($_POST['stock_time']) ? mysqli_real_escape_string($conn, $_POST['stock_time']) : date('H:i');
    $stock_in_datetime = $stock_date . ' ' . $stock_time . ':00';

    $received_by = trim($_POST['received_by'] ?? '');
    $provided_by = trim($_POST['provided_by'] ?? '');

    if (empty($received_by) || empty($provided_by) || empty($stock_date) || empty($stock_time)) {
        $message = "Error: All fields including 'Provided By', 'Received By', Date, and Time are required!";
    } else {
        $received_by_esc = mysqli_real_escape_string($conn, $received_by);
        $provided_by_esc = mysqli_real_escape_string($conn, $provided_by);

        if (!$conn->begin_transaction()) {
            $message = "Error: Could not start the inventory update: " . $conn->error;
        } else {
            $current_stmt = $conn->prepare("SELECT total_received, available_stock FROM vaccines WHERE id = ? FOR UPDATE");
            if (!$current_stmt) {
                $error = $conn->error;
                $conn->rollback();
                $message = "Error checking current vaccine stock: " . $error;
            } else {
                $current_stmt->bind_param("i", $id);
                if (!$current_stmt->execute()) {
                    $error = $conn->error;
                    $current_stmt->close();
                    $conn->rollback();
                    $message = "Error checking current vaccine stock: " . $error;
                } else {
                    $current_vaccine = $current_stmt->get_result()->fetch_assoc();
                    $current_stmt->close();
                    if (!$current_vaccine) {
                        $conn->rollback();
                        $message = "Error: Vaccine not found.";
                    } else {
                        $old_total_received = (int) $current_vaccine['total_received'];
                        $old_available_stock = (int) $current_vaccine['available_stock'];
                        $movement_type = '';
                        $movement_quantity = 0;

                        if ($total_received > $old_total_received) {
                            $movement_type = 'IN';
                            $movement_quantity = $total_received - $old_total_received;
                        } elseif ($available_stock !== $old_available_stock) {
                            $movement_type = $available_stock > $old_available_stock ? 'IN' : 'OUT';
                            $movement_quantity = abs($available_stock - $old_available_stock);
                        }

                        $sql = "UPDATE vaccines SET vaccine_name=?, description=?, category=?, total_received=?, available_stock=?, stock_in_date=?, received_by=?, provided_by=? WHERE id=?";
                        $stmt = $conn->prepare($sql);
                        if (!$stmt) {
                            $error = $conn->error;
                            $conn->rollback();
                            $message = "Error preparing vaccine update: " . $error;
                        } else {
                            $stmt->bind_param("sssiiissi", $vaccine_name, $description, $category, $total_received, $available_stock, $stock_in_datetime, $received_by_esc, $provided_by_esc, $id);
                            if (!$stmt->execute()) {
                                $error = $conn->error;
                                $stmt->close();
                                $conn->rollback();
                                $message = "Error updating vaccine: " . $error;
                            } else {
                                $stmt->close();
                                $log_ok = true;
                                if ($movement_quantity > 0) {
                                    $log_sql = "INSERT INTO vaccine_logs (vaccine_id, action_type, quantity, remarks, transaction_date, recorded_by) VALUES (?, ?, ?, ?, ?, ?)";
                                    $log_stmt = $conn->prepare($log_sql);
                                    if (!$log_stmt) {
                                        $error = $conn->error;
                                        $log_ok = false;
                                    } else {
                                        $remarks = $movement_type === 'IN' ? "Additional stock received from " . $provided_by : "Stock adjustment";
                                        $log_stmt->bind_param("isisss", $id, $movement_type, $movement_quantity, $remarks, $stock_in_datetime, $received_by_esc);
                                        $log_ok = $log_stmt->execute();
                                        if (!$log_ok) {
                                            $error = $conn->error;
                                        }
                                        $log_stmt->close();
                                    }
                                }

                                if (!$log_ok) {
                                    $conn->rollback();
                                    $message = "Error recording vaccine stock history: " . $error;
                                } elseif ($conn->commit()) {
                                    $message = "Vaccine updated successfully!";
                                } else {
                                    $error = $conn->error;
                                    $conn->rollback();
                                    $message = "Error saving vaccine update: " . $error;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Delete Vaccine
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql = "DELETE FROM vaccines WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "Vaccine deleted successfully!";
        } else {
            $message = "Error deleting record: " . $conn->error;
        }
        $stmt->close();
    }
}

// ---------------------------------------------------------
// FETCH DATA FOR TABLES & CHARTS
// ---------------------------------------------------------

// Tables Fetch
$sql_baby = "SELECT * FROM vaccines WHERE category = 'Baby' ORDER BY created_at DESC";
$result_baby = $conn->query($sql_baby);

$sql_maternal = "SELECT * FROM vaccines WHERE category = 'Maternal' ORDER BY created_at DESC";
$result_maternal = $conn->query($sql_maternal);

$sql_logs = "SELECT l.*, v.vaccine_name FROM vaccine_logs l JOIN vaccines v ON l.vaccine_id = v.id ORDER BY l.transaction_date DESC";
$result_logs = $conn->query($sql_logs);

// Fetch Data for Summary Cards
$total_available_query = $conn->query("SELECT SUM(available_stock) as total FROM vaccines");
$total_available_row = $total_available_query->fetch_assoc();
$total_available = $total_available_row['total'] ?? 0;
$vaccine_count_query = $conn->query("SELECT COUNT(*) AS total FROM vaccines");
if (!$vaccine_count_query) {
    die("Could not load vaccine inventory summary: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$vaccine_count_row = $vaccine_count_query->fetch_assoc();
$vaccine_item_count = (int) ($vaccine_count_row['total'] ?? 0);

$current_month = date('Y-m');
$monthly_out_query = $conn->query("SELECT SUM(quantity) as total_out FROM vaccine_logs WHERE action_type = 'OUT' AND DATE_FORMAT(transaction_date, '%Y-%m') = '$current_month'");
$monthly_out_row = $monthly_out_query->fetch_assoc();
$total_out_month = $monthly_out_row['total_out'] ?? 0;

$low_stock_query = $conn->query("SELECT vaccine_name, available_stock FROM vaccines WHERE available_stock <= 10 ORDER BY available_stock ASC, vaccine_name ASC");
$low_stock_count = $low_stock_query ? $low_stock_query->num_rows : 0;
$low_stock_items = [];
if ($low_stock_query) {
    while ($low_stock = $low_stock_query->fetch_assoc()) {
        $low_stock_items[] = $low_stock;
    }
}

// Fetch Data for Chart.js (Monthly IN vs OUT for current year)
$current_year = date('Y');
$chart_data = array_fill(1, 12, ['IN' => 0, 'OUT' => 0]); 

$chart_query = $conn->query("SELECT MONTH(transaction_date) as month, action_type, SUM(quantity) as total 
                             FROM vaccine_logs 
                             WHERE YEAR(transaction_date) = '$current_year' 
                             GROUP BY month, action_type");

while ($row = $chart_query->fetch_assoc()) {
    $month = (int)$row['month'];
    $type = $row['action_type'];
    if(isset($chart_data[$month][$type])) {
        $chart_data[$month][$type] = (int)$row['total'];
    }
}

$chart_in_array = [];
$chart_out_array = [];
for ($i = 1; $i <= 12; $i++) {
    $chart_in_array[] = $chart_data[$i]['IN'];
    $chart_out_array[] = $chart_data[$i]['OUT'];
}
$has_chart_activity = array_sum($chart_in_array) + array_sum($chart_out_array) > 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccine Inventory | Alawihao Health</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sage-green: #718355;
            --light-beige: #fdfbf7;
            --border-color: #d1d5db;
            --sidebar-width: 280px;
            --danger-red: #dc2626;
            --card-bg: #ffffff;
        }

        body { 
            background-color: var(--light-beige); 
            margin: 0; 
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: hidden;
            color: #111;
        }

        #main { 
            margin-left: var(--sidebar-width); 
            padding: 34px clamp(18px, 3vw, 40px);
            box-sizing: border-box;
            min-height: 100vh;
            transition: margin-left .3s ease-in-out;
        }

        .inventory-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 17px;
        }

        .page-header h2 {
            margin: 0;
            color: #43533a;
            font-size: clamp(1.45rem, 2vw, 1.8rem);
            text-transform: none;
            letter-spacing: -0.03em;
        }

        .page-subtitle {
            margin: 5px 0 0;
            color: #71806b;
            font-size: .86rem;
        }

        .btn-add {
            background-color: var(--sage-green);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.84rem;
            font-weight: bold;
            box-shadow: 0 3px 8px rgba(65, 85, 52, .16);
            transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
        }
        .btn-add:hover {
            background-color: #5f7048;
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(65, 85, 52, .2);
        }

        /* ---------------- Dashboard Cards & Chart Styles ---------------- */
        .dashboard-top {
            display: flex;
            gap: 18px;
            margin-bottom: 26px;
            align-items: stretch;
        }

        .summary-cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
            flex: 0 0 290px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 17px 19px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(34, 48, 28, .045);
            text-align: left;
        }

        .card h3 {
            margin: 0 0 6px;
            font-size: 0.75rem;
            text-transform: none;
            color: #666;
            letter-spacing: .01em;
        }

        .card .value {
            font-size: 1.85rem;
            font-weight: bold;
            color: var(--sage-green);
            margin: 0;
            line-height: 1.15;
        }
        
        .card.alert-card .value {
            color: var(--danger-red);
        }
        .card.alert-card.is-empty .value { color: var(--sage-green); }

        .low-stock-list {
            margin: 12px 0 0;
            padding: 0;
            list-style: none;
            text-align: left;
            font-size: 0.76rem;
            max-height: 125px;
            overflow-y: auto;
        }

        .low-stock-list li {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 7px 0;
            border-top: 1px solid var(--border-color);
        }

        .low-stock-name { color: #414a3c; font-weight: 600; }
        .low-stock-quantity {
            color: var(--danger-red);
            font-weight: bold;
            white-space: nowrap;
        }
        .inventory-state { margin: 8px 0 0; color: #6b7564; font-size: .76rem; line-height: 1.45; }
        .inventory-state.is-empty { color: #8a5b15; }
        .inventory-state.is-error { color: var(--danger-red); }

        .chart-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(34, 48, 28, .045);
            flex: 1 1 auto;
            position: relative;
            min-width: 0;
            min-height: 270px;
        }
        .chart-container.has-no-data { display: grid; place-items: center; }
        .chart-container canvas { max-height: 300px; }
        .chart-empty-state { max-width: 340px; padding: 22px; text-align: center; }
        .chart-empty-icon { display: grid; place-items: center; width: 48px; height: 48px; margin: 0 auto 12px; border-radius: 14px; background: #f0f5ec; color: var(--sage-green); font-size: 1.1rem; }
        .chart-empty-state h3 { margin: 0 0 6px; color: #394532; font-size: .98rem; }
        .chart-empty-state p { margin: 0 0 14px; color: #71806b; font-size: .8rem; line-height: 1.5; }
        .chart-empty-state .btn-add { display: inline-block; text-decoration: none; }

        .history-toolbar { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(150px, 200px); gap: 10px; margin-bottom: 14px; }
        .history-toolbar input, .history-toolbar select { width: 100%; min-height: 40px; padding: 8px 11px; border: 1px solid #d9dfd4; border-radius: 8px; background: #fff; color: #293126; font: inherit; font-size: .8rem; box-sizing: border-box; }
        .history-result-count { margin: 0 0 9px; color: #74806d; font-size: .74rem; }
        .table-scroll { width: 100%; overflow-x: auto; border: 1px solid #e7ebe3; border-radius: 9px; }
        .inventory-table { min-width: 720px; }
        .inventory-table tbody tr { transition: background .15s ease; }
        .inventory-table tbody tr:hover { background: #f8faf6; }
        .inventory-table td { line-height: 1.45; }
        .vaccine-description, .table-muted { color: #71806b; font-size: .76rem; }
        .stock-count { display: inline-block; margin-top: 4px; font-weight: 700; }
        .stock-badge, .movement-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 8px; border-radius: 99px; font-size: .68rem; font-weight: 750; white-space: nowrap; }
        .stock-badge-in { background: #e7f4e9; color: #27633a; }
        .stock-badge-low { background: #fff3d6; color: #8a5b15; }
        .stock-badge-out { background: #fee7e5; color: #a52a26; }
        .movement-badge-in { background: #e7f4e9; color: #27633a; }
        .movement-badge-out { background: #fee7e5; color: #a52a26; }
        .table-empty { padding: 28px 16px !important; color: #71806b; text-align: center !important; }
        .history-empty { display: none; padding: 22px 12px; color: #71806b; text-align: center; font-size: .82rem; }

        /* ---------------- Tabs & Tables Styles ---------------- */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 0.9rem;
            font-weight: bold;
            color: #666;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            font-family: inherit;
        }

        .tab.active {
            color: var(--sage-green);
            border-bottom-color: var(--sage-green);
        }

        .tab-content {
            display: none;
            background: #fff;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .tab-content.active {
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            text-transform: uppercase;
            font-size: 0.8rem;
            color: #4b5563;
            background-color: #fafaf9;
        }

        /* ---------------- Modal Styles ---------------- */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.5); 
            overflow-y: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 3% auto;
            padding: 20px;
            border-radius: 4px;
            width: 450px;
        }

        .form-group { margin-bottom: 10px; }
        .form-group label {
            display: block; font-size: 0.75rem; text-transform: uppercase;
            color: #4b5563; margin-bottom: 3px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 6px 8px; border: 1px solid var(--border-color);
            border-radius: 2px; font-family: inherit; box-sizing: border-box; font-size: 0.9rem;
        }

        .btn-submit {
            width: 100%; background-color: var(--sage-green); color: white;
            border: none; padding: 8px; cursor: pointer; text-transform: uppercase;
            font-weight: bold; margin-top: 5px;
        }
        
        .success-msg {
            color: #166534; background: #f0fdf4; padding: 10px;
            border-left: 3px solid var(--sage-green); margin-bottom: 15px;
        }

        .action-links a { font-weight: bold; text-decoration: none; margin-right: 10px; }
        .action-edit { color: var(--sage-green); }
        .action-delete { color: var(--danger-red); }

        [data-theme="dark"] .page-header { border-color: var(--dm-border); }
        [data-theme="dark"] .page-header h2,
        [data-theme="dark"] .chart-empty-state h3 { color: var(--dm-text) !important; }
        [data-theme="dark"] .page-subtitle,
        [data-theme="dark"] .card h3,
        [data-theme="dark"] .inventory-state,
        [data-theme="dark"] .chart-empty-state p,
        [data-theme="dark"] .history-result-count,
        [data-theme="dark"] .vaccine-description,
        [data-theme="dark"] .table-muted,
        [data-theme="dark"] .table-empty,
        [data-theme="dark"] .history-empty { color: var(--dm-text2) !important; }
        [data-theme="dark"] .card,
        [data-theme="dark"] .chart-container,
        [data-theme="dark"] .tab-content { background: var(--dm-surface) !important; border-color: var(--dm-border) !important; }
        [data-theme="dark"] .low-stock-name { color: var(--dm-text); }
        [data-theme="dark"] .low-stock-list li { border-color: var(--dm-border); }
        [data-theme="dark"] .chart-empty-icon { background: var(--dm-surface2); color: var(--dm-sage); }
        [data-theme="dark"] .history-toolbar input,
        [data-theme="dark"] .history-toolbar select { background: var(--dm-surface2); border-color: var(--dm-border); color: var(--dm-text); }
        [data-theme="dark"] .table-scroll { border-color: var(--dm-border); }
        [data-theme="dark"] .inventory-table th { background: var(--dm-surface2); color: var(--dm-text); }
        [data-theme="dark"] .inventory-table td { border-color: var(--dm-border); color: var(--dm-text); }
        [data-theme="dark"] .inventory-table tbody tr:hover { background: var(--dm-surface2); }
        [data-theme="dark"] .stock-badge-in,
        [data-theme="dark"] .movement-badge-in { background: #24412d; color: #a7d8ae; }
        [data-theme="dark"] .stock-badge-low { background: #51411f; color: #f4d58c; }
        [data-theme="dark"] .stock-badge-out,
        [data-theme="dark"] .movement-badge-out { background: #512b2a; color: #f3aaaa; }
        [data-theme="dark"] .success-msg { background: #24412d; color: #a7d8ae; border-color: #5f9b63; }

        @media (max-width: 900px) {
            #main { padding-top: 78px; }
            .dashboard-top { flex-direction: column; }
            .summary-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); flex: initial; }
            .card { padding: 15px; }
            .chart-container { min-height: 260px; }
        }
        @media (max-width: 600px) {
            #main { padding: 78px 14px 22px; }
            .page-header { align-items: flex-start; flex-direction: column; }
            .action-btns, .btn-add { width: 100%; box-sizing: border-box; text-align: center; }
            .summary-cards { grid-template-columns: minmax(0, 1fr); }
            .card { padding: 14px 16px; }
            .card .value { font-size: 1.55rem; }
            .chart-container { min-height: 240px; padding: 14px; }
            .tabs { overflow-x: auto; }
            .tab { flex: 0 0 auto; padding: 10px 13px; font-size: .77rem; }
            .tab-content { padding: 12px; }
            .history-toolbar { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
</head>
<body>

<?php 
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin') {
        include 'super_admin_sidebar.php';
    } else {
        include 'admin_sidebar.php'; 
    }
?>

<div id="main">
    <div class="inventory-container">
        
        <div class="page-header">
            <div>
                <h2>Vaccine Inventory</h2>
                <p class="page-subtitle">Monitor available stock, replenishment, and vaccine usage.</p>
            </div>
            <div class="action-btns">
                <button class="btn-add" type="button" onclick="document.getElementById('addModal').style.display='block'"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add New Vaccine</button>
            </div>
        </div>

        <?php if(!empty($message)) echo "<div class='success-msg'>$message</div>"; ?>

        <!-- DASHBOARD SUMMARY & CHART -->
        <div class="dashboard-top">
            <div class="summary-cards">
                <div class="card">
                    <h3>Total Available Stock</h3>
                    <p class="value"><?= $total_available ?></p>
                </div>
                <div class="card">
                    <h3>Used This Month</h3>
                    <p class="value" style="color: #666;"><?= $total_out_month ?></p>
                </div>
                <div class="card alert-card<?= $vaccine_item_count === 0 ? ' is-empty' : '' ?>">
                    <h3><?= $vaccine_item_count === 0 ? 'Inventory Status' : 'Low Stock Alert' ?></h3>
                    <p class="value"><?= $low_stock_count ?></p>
                    <?php if (!$low_stock_query): ?>
                        <p class="inventory-state is-error">Could not load low-stock vaccines: <?= htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php elseif ($vaccine_item_count === 0): ?>
                        <p class="inventory-state is-empty">No vaccines are registered in inventory yet. Add a vaccine to start tracking stock.</p>
                    <?php elseif ($low_stock_count > 0): ?>
                        <ul class="low-stock-list">
                            <?php foreach ($low_stock_items as $low_stock): ?>
                                <li>
                                    <span class="low-stock-name"><?= htmlspecialchars($low_stock['vaccine_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="low-stock-quantity"><?= (int) $low_stock['available_stock'] <= 0 ? 'Out of stock' : (int) $low_stock['available_stock'] . ' left' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="inventory-state">All <?= $vaccine_item_count ?> vaccine types are above the low-stock threshold (10 doses).</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="chart-container<?= $has_chart_activity ? '' : ' has-no-data' ?>">
                <?php if ($has_chart_activity): ?>
                    <canvas id="inventoryChart" aria-label="Monthly vaccine stock received and used"></canvas>
                <?php else: ?>
                    <div class="chart-empty-state">
                        <span class="chart-empty-icon"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span>
                        <h3>No stock movement this year</h3>
                        <p><?= $vaccine_item_count === 0 ? 'Add vaccines to begin tracking stock received and used.' : 'Vaccine inventory exists, but no IN or OUT transactions have been recorded this year.' ?></p>
                        <?php if ($vaccine_item_count === 0): ?>
                            <button class="btn-add" type="button" onclick="document.getElementById('addModal').style.display='block'"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add New Vaccine</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TABS NAVIGATION -->
        <div class="tabs">
            <button class="tab active" type="button" onclick="openTab('babyTab', this)">Baby Vaccines</button>
            <button class="tab" type="button" onclick="openTab('maternalTab', this)">Maternal Vaccines</button>
            <button class="tab" type="button" onclick="openTab('historyTab', this)">Inventory History</button>
        </div>

        <!-- BABY VACCINES TAB -->
        <div id="babyTab" class="tab-content active">
            <div class="table-scroll">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Vaccine Name</th>
                        <th>Total / Stock</th>
                        <th>Received From / By</th>
                        <th>Date & Time Received</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_baby && $result_baby->num_rows > 0): ?>
                        <?php while($row = $result_baby->fetch_assoc()): 
                            $datetime_val = !empty($row['stock_in_date']) ? $row['stock_in_date'] : $row['created_at'];
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['vaccine_name']) ?></strong><br>
                                    <small class="vaccine-description"><?= htmlspecialchars($row['description']) ?></small>
                                </td>
                                <td>
                                    Total received: <?= (int) ($row['total_received'] ?? 0) ?><br>
                                    <?php $availableStock = (int) ($row['available_stock'] ?? 0); ?>
                                    <span class="stock-badge <?= $availableStock <= 0 ? 'stock-badge-out' : ($availableStock <= 10 ? 'stock-badge-low' : 'stock-badge-in') ?>">
                                        <?= $availableStock <= 0 ? 'Out of stock' : ($availableStock <= 10 ? 'Low stock' : 'In stock') ?>
                                    </span>
                                    <span class="stock-count"><?= $availableStock ?> available</span>
                                </td>
                                <td>
                                    <small><strong>From:</strong> <?= htmlspecialchars($row['provided_by'] ?? 'N/A') ?></small><br>
                                    <small><strong>By:</strong> <?= htmlspecialchars($row['received_by'] ?? 'N/A') ?></small>
                                </td>
                                <td><?= date('M d, Y h:i A', strtotime($datetime_val)) ?></td>
                                <td class="action-links">
                                    <a href="#" class="action-edit" 
                                       onclick="openEditModal(
                                           '<?= $row['id'] ?>', 
                                           '<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>', 
                                           '<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>', 
                                           '<?= $row['category'] ?>', 
                                           '<?= $row['total_received'] ?>', 
                                           '<?= $row['available_stock'] ?>', 
                                           '<?= date('Y-m-d', strtotime($datetime_val)) ?>',
                                           '<?= date('H:i', strtotime($datetime_val)) ?>',
                                           '<?= htmlspecialchars($row['received_by'] ?? '', ENT_QUOTES) ?>',
                                           '<?= htmlspecialchars($row['provided_by'] ?? '', ENT_QUOTES) ?>'
                                       )">Edit</a>
                                    <a href="?delete_id=<?= $row['id'] ?>" class="action-delete" onclick="return confirm('Sigurado ka bang gusto mong idelete ang vaccine na ito?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="table-empty">No baby vaccines in inventory yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- MATERNAL VACCINES TAB -->
        <div id="maternalTab" class="tab-content">
            <div class="table-scroll">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Vaccine Name</th>
                        <th>Total / Stock</th>
                        <th>Received From / By</th>
                        <th>Date & Time Received</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_maternal && $result_maternal->num_rows > 0): ?>
                        <?php while($row = $result_maternal->fetch_assoc()): 
                            $datetime_val = !empty($row['stock_in_date']) ? $row['stock_in_date'] : $row['created_at'];
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['vaccine_name']) ?></strong><br>
                                    <small class="vaccine-description"><?= htmlspecialchars($row['description']) ?></small>
                                </td>
                                <td>
                                    Total received: <?= (int) ($row['total_received'] ?? 0) ?><br>
                                    <?php $availableStock = (int) ($row['available_stock'] ?? 0); ?>
                                    <span class="stock-badge <?= $availableStock <= 0 ? 'stock-badge-out' : ($availableStock <= 10 ? 'stock-badge-low' : 'stock-badge-in') ?>">
                                        <?= $availableStock <= 0 ? 'Out of stock' : ($availableStock <= 10 ? 'Low stock' : 'In stock') ?>
                                    </span>
                                    <span class="stock-count"><?= $availableStock ?> available</span>
                                </td>
                                <td>
                                    <small><strong>From:</strong> <?= htmlspecialchars($row['provided_by'] ?? 'N/A') ?></small><br>
                                    <small><strong>By:</strong> <?= htmlspecialchars($row['received_by'] ?? 'N/A') ?></small>
                                </td>
                                <td><?= date('M d, Y h:i A', strtotime($datetime_val)) ?></td>
                                <td class="action-links">
                                    <a href="#" class="action-edit" 
                                       onclick="openEditModal(
                                           '<?= $row['id'] ?>', 
                                           '<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>', 
                                           '<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>', 
                                           '<?= $row['category'] ?>', 
                                           '<?= $row['total_received'] ?>', 
                                           '<?= $row['available_stock'] ?>', 
                                           '<?= date('Y-m-d', strtotime($datetime_val)) ?>',
                                           '<?= date('H:i', strtotime($datetime_val)) ?>',
                                           '<?= htmlspecialchars($row['received_by'] ?? '', ENT_QUOTES) ?>',
                                           '<?= htmlspecialchars($row['provided_by'] ?? '', ENT_QUOTES) ?>'
                                       )">Edit</a>
                                    <a href="?delete_id=<?= $row['id'] ?>" class="action-delete" onclick="return confirm('Sigurado ka bang gusto mong idelete ang vaccine na ito?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="table-empty">No maternal vaccines in inventory yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- HISTORY TAB -->
        <div id="historyTab" class="tab-content">
            <div class="history-toolbar">
                <input type="search" id="historySearch" placeholder="Search vaccine, remarks, or staff" aria-label="Search inventory history">
                <select id="historyActionFilter" aria-label="Filter history by action">
                    <option value="">All stock movements</option>
                    <option value="in">Stock received (IN)</option>
                    <option value="out">Stock used (OUT)</option>
                </select>
            </div>
            <p class="history-result-count" id="historyResultCount" aria-live="polite"></p>
            <div class="table-scroll">
            <table class="inventory-table" id="historyTable">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Vaccine Name</th>
                        <th>Action Type</th>
                        <th>Quantity</th>
                        <th>Remarks</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_logs && $result_logs->num_rows > 0): ?>
                        <?php while($log = $result_logs->fetch_assoc()):
                            $historySearchText = strtolower(implode(' ', [
                                $log['vaccine_name'] ?? '',
                                $log['remarks'] ?? '',
                                $log['recorded_by'] ?? '',
                                $log['action_type'] ?? '',
                                $log['transaction_date'] ?? ''
                            ]));
                            $historyAction = strtolower($log['action_type'] ?? '');
                        ?>
                            <tr data-history-search="<?= htmlspecialchars($historySearchText, ENT_QUOTES, 'UTF-8') ?>" data-history-action="<?= htmlspecialchars($historyAction, ENT_QUOTES, 'UTF-8') ?>">
                                <td><?= date('M d, Y h:i A', strtotime($log['transaction_date'])) ?></td>
                                <td><strong><?= htmlspecialchars($log['vaccine_name']) ?></strong></td>
                                <td>
                                    <span class="movement-badge <?= (strtoupper($log['action_type'] ?? '') === 'IN') ? 'movement-badge-in' : 'movement-badge-out' ?>">
                                        <?= strtoupper($log['action_type'] ?? '') === 'IN' ? 'Stock IN' : 'Stock OUT' ?>
                                    </span>
                                </td>
                                <td><strong><?= (int) $log['quantity'] ?></strong></td>
                                <td><?= htmlspecialchars($log['remarks']) ?></td>
                                <td><?= htmlspecialchars($log['recorded_by']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="table-empty">No inventory history recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
            <div class="history-empty" id="historyFilterEmpty">No history entries match your search and filter.</div>
        </div>

    </div>
</div>

<!-- ADD MODAL -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; text-transform: uppercase; font-size: 1rem;">Add Vaccine & Stock</h3>
            <span style="cursor: pointer; font-weight: bold;" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Category</label>
                <select name="category" id="add_category" onchange="filterVaccineOptions('add_category', 'add_vaccine_name')" required>
                    <option value="Baby">Baby Vaccine</option>
                    <option value="Maternal">Maternal Vaccine (Pregnant)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Vaccine Name</label>
                <select name="vaccine_name" id="add_vaccine_name" required>
                    <option value="">-- Select Vaccine Name --</option>
                    <option value="BCG" data-category="Baby">BCG</option>
                    <option value="Hepatitis B" data-category="Baby">Hepatitis B</option>
                    <option value="Pentavalent" data-category="Baby">Pentavalent</option>
                    <option value="Oral Polio Vaccine (OPV)" data-category="Baby">Oral Polio Vaccine (OPV)</option>
                    <option value="Inactivated Polio Vaccine (IPV)" data-category="Baby">Inactivated Polio Vaccine (IPV)</option>
                    <option value="Pneumococcal Conjugate Vaccine (PCV)" data-category="Baby">Pneumococcal Conjugate Vaccine (PCV)</option>
                    <option value="Measles, Mumps, Rubella (MMR)" data-category="Baby">Measles, Mumps, Rubella (MMR)</option>
                    <option value="Tetanus Toxoid" data-category="Maternal">Tetanus Toxoid</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description / Notes</label>
                <textarea name="description" rows="2" placeholder="Target disease or notes..."></textarea>
            </div>
            <div class="form-group">
                <label>Total Received (Quantity)</label>
                <input type="number" name="total_received" value="0" min="0" required>
                <small>Ilagay ang dami ng bagong dating na bakuna.</small>
            </div>
            <div class="form-group">
                <label>Available Stock</label>
                <input type="number" name="available_stock" value="0" min="0" required>
                <small>Kabuuang stock na hawak ngayon, kasama ang mga natira.</small>
            </div>
            <div class="form-group">
                <label>Provided By (Nagbigay / Supplier / Source) *</label>
                <input type="text" name="provided_by" placeholder="e.g. Provincial Health Office" required>
            </div>
            <div class="form-group">
                <label>Received By (Sino ang tumanggap) *</label>
                <input type="text" name="received_by" placeholder="e.g. Juan Dela Cruz" required>
            </div>
            <div class="form-group">
                <label>Date Received *</label>
                <input type="date" name="stock_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Time Received *</label>
                <input type="time" name="stock_time" value="<?= date('H:i') ?>" required>
            </div>
            <button type="submit" name="add_vaccine" class="btn-submit">Save Vaccine</button>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; text-transform: uppercase; font-size: 1rem;">Edit Vaccine & Stock</h3>
            <span style="cursor: pointer; font-weight: bold;" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
        </div>
        
        <form method="POST">
            <input type="hidden" name="vaccine_id" id="edit_vaccine_id">
            <div class="form-group">
                <label>Category</label>
                <select name="category" id="edit_category" onchange="filterVaccineOptions('edit_category', 'edit_vaccine_name')" required>
                    <option value="Baby">Baby Vaccine</option>
                    <option value="Maternal">Maternal Vaccine (Pregnant)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Vaccine Name</label>
                <select name="vaccine_name" id="edit_vaccine_name" required>
                    <option value="">-- Select Vaccine Name --</option>
                    <option value="BCG" data-category="Baby">BCG</option>
                    <option value="Hepatitis B" data-category="Baby">Hepatitis B</option>
                    <option value="Pentavalent" data-category="Baby">Pentavalent</option>
                    <option value="Oral Polio Vaccine (OPV)" data-category="Baby">Oral Polio Vaccine (OPV)</option>
                    <option value="Inactivated Polio Vaccine (IPV)" data-category="Baby">Inactivated Polio Vaccine (IPV)</option>
                    <option value="Pneumococcal Conjugate Vaccine (PCV)" data-category="Baby">Pneumococcal Conjugate Vaccine (PCV)</option>
                    <option value="Measles, Mumps, Rubella (MMR)" data-category="Baby">Measles, Mumps, Rubella (MMR)</option>
                    <option value="Tetanus Toxoid" data-category="Maternal">Tetanus Toxoid</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description / Notes</label>
                <textarea name="description" id="edit_description" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Total Received (Quantity)</label>
                <input type="number" name="total_received" id="edit_total_received" min="0" required>
                <small>Idagdag lang ang bagong dating para maitala sa chart.</small>
            </div>
            <div class="form-group">
                <label>Available Stock</label>
                <input type="number" name="available_stock" id="edit_available_stock" min="0" required>
                <small>Kabuuang stock na hawak ngayon, kasama ang mga natira.</small>
            </div>
            <div class="form-group">
                <label>Provided By (Nagbigay / Supplier / Source) *</label>
                <input type="text" name="provided_by" id="edit_provided_by" required>
            </div>
            <div class="form-group">
                <label>Received By (Sino ang tumanggap) *</label>
                <input type="text" name="received_by" id="edit_received_by" required>
            </div>
            <div class="form-group">
                <label>Date Received *</label>
                <input type="date" name="stock_date" id="edit_stock_date" required>
            </div>
            <div class="form-group">
                <label>Time Received *</label>
                <input type="time" name="stock_time" id="edit_stock_time" required>
                <small>Tamang petsa ang kailangan para lumabas sa chart.</small>
            </div>
            <button type="submit" name="update_vaccine" class="btn-submit">Update Vaccine</button>
        </form>
    </div>
</div>

<script>
    // Tab Switching Logic
    function openTab(tabName, tabButton) {
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => content.classList.remove('active'));
        
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        document.getElementById(tabName).classList.add('active');
        tabButton.classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('historySearch');
        const actionFilter = document.getElementById('historyActionFilter');
        const resultCount = document.getElementById('historyResultCount');
        const filterEmpty = document.getElementById('historyFilterEmpty');
        const historyRows = Array.from(document.querySelectorAll('#historyTable tbody tr[data-history-search]'));

        function filterHistory() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const selectedAction = actionFilter.value;
            let visibleCount = 0;

            historyRows.forEach(function(row) {
                const matches = row.dataset.historySearch.includes(searchTerm)
                    && (!selectedAction || row.dataset.historyAction === selectedAction);
                row.hidden = !matches;
                if (matches) visibleCount++;
            });

            filterEmpty.style.display = historyRows.length > 0 && visibleCount === 0 ? 'block' : 'none';
            resultCount.textContent = historyRows.length === 0
                ? '0 history entries'
                : 'Showing ' + visibleCount + ' of ' + historyRows.length + ' history entries';
        }

        searchInput.addEventListener('input', filterHistory);
        actionFilter.addEventListener('change', filterHistory);
        filterHistory();
    });

    // Modal Edit Logic
    function openEditModal(id, name, description, category, totalReceived, availableStock, stockDate, stockTime, receivedBy, providedBy) {
        document.getElementById('edit_vaccine_id').value = id;
        document.getElementById('edit_category').value = category;
        filterVaccineOptions('edit_category', 'edit_vaccine_name');
        document.getElementById('edit_vaccine_name').value = name;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_total_received').value = totalReceived;
        document.getElementById('edit_available_stock').value = availableStock;
        document.getElementById('edit_stock_date').value = stockDate;
        document.getElementById('edit_stock_time').value = stockTime;
        document.getElementById('edit_received_by').value = receivedBy;
        document.getElementById('edit_provided_by').value = providedBy;
        
        document.getElementById('editModal').style.display = 'block';
    }

    function filterVaccineOptions(categoryId, vaccineId) {
        const category = document.getElementById(categoryId).value;
        const vaccineSelect = document.getElementById(vaccineId);
        const currentValue = vaccineSelect.value;

        Array.from(vaccineSelect.options).forEach(option => {
            if (option.dataset.category) {
                option.hidden = option.dataset.category !== category;
                option.disabled = option.dataset.category !== category;
            }
        });

        if (currentValue && vaccineSelect.selectedOptions[0]?.disabled) {
            vaccineSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        filterVaccineOptions('add_category', 'add_vaccine_name');
        filterVaccineOptions('edit_category', 'edit_vaccine_name');
    });

    // Chart.js Setup
    document.addEventListener("DOMContentLoaded", function() {
        const canvas = document.getElementById('inventoryChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        // Data injected from PHP
        const dataIn = <?= json_encode($chart_in_array) ?>;
        const dataOut = <?= json_encode($chart_out_array) ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Stock IN (Received)',
                        data: dataIn,
                        backgroundColor: '#718355', // Sage Green
                        borderRadius: 3
                    },
                    {
                        label: 'Stock OUT (Used)',
                        data: dataOut,
                        backgroundColor: '#d1d5db', // Grayish
                        borderRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: "'Times New Roman', serif" }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Monthly Vaccine Flow (<?= $current_year ?>)',
                        font: { family: "'Times New Roman', serif", size: 14 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 10 }
                    }
                }
            }
        });
    });
</script>

</body>
</html>