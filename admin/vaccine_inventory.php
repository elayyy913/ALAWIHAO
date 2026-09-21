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
    $total_received = intval($_POST['total_received']);
    $available_stock = intval($_POST['available_stock']);
    
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
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssiiiss", $vaccine_name, $description, $category, $total_received, $available_stock, $stock_in_datetime, $received_by_esc, $provided_by_esc);
            if ($stmt->execute()) {
                $new_vaccine_id = $stmt->insert_id;
                
                // Record to vaccine_logs (IN)
                $log_sql = "INSERT INTO vaccine_logs (vaccine_id, action_type, quantity, remarks, transaction_date, recorded_by) VALUES (?, 'IN', ?, ?, ?, ?)";
                if ($log_stmt = $conn->prepare($log_sql)) {
                    $remarks = "New stock received from " . $provided_by;
                    $log_stmt->bind_param("iisis", $new_vaccine_id, $total_received, $remarks, $stock_in_datetime, $received_by_esc);
                    $log_stmt->execute();
                    $log_stmt->close();
                }

                $message = "Vaccine added successfully!";
            } else {
                $message = "Error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Update Existing Vaccine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_vaccine'])) {
    $id = intval($_POST['vaccine_id']);
    $vaccine_name = mysqli_real_escape_string($conn, $_POST['vaccine_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $total_received = intval($_POST['total_received']);
    $available_stock = intval($_POST['available_stock']);
    
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

        $sql = "UPDATE vaccines SET vaccine_name=?, description=?, category=?, total_received=?, available_stock=?, stock_in_date=?, received_by=?, provided_by=? WHERE id=?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssiiissi", $vaccine_name, $description, $category, $total_received, $available_stock, $stock_in_datetime, $received_by_esc, $provided_by_esc, $id);
            if ($stmt->execute()) {
                
                // Log adjustment
                $log_sql = "INSERT INTO vaccine_logs (vaccine_id, action_type, quantity, remarks, transaction_date, recorded_by) VALUES (?, 'IN', ?, ?, ?, ?)";
                if ($log_stmt = $conn->prepare($log_sql)) {
                    $remarks = "Stock updated/adjusted";
                    $log_stmt->bind_param("iisis", $id, $total_received, $remarks, $stock_in_datetime, $received_by_esc);
                    $log_stmt->execute();
                    $log_stmt->close();
                }

                $message = "Vaccine updated successfully!";
            } else {
                $message = "Error updating: " . $conn->error;
            }
            $stmt->close();
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

$current_month = date('Y-m');
$monthly_out_query = $conn->query("SELECT SUM(quantity) as total_out FROM vaccine_logs WHERE action_type = 'OUT' AND DATE_FORMAT(transaction_date, '%Y-%m') = '$current_month'");
$monthly_out_row = $monthly_out_query->fetch_assoc();
$total_out_month = $monthly_out_row['total_out'] ?? 0;

$low_stock_query = $conn->query("SELECT COUNT(*) as low_count FROM vaccines WHERE available_stock <= 10");
$low_stock_row = $low_stock_query->fetch_assoc();
$low_stock_count = $low_stock_row['low_count'] ?? 0;

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccine Inventory | Alawihao Health</title>
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
            font-family: 'Times New Roman', serif; 
            overflow-x: hidden;
            color: #111;
        }

        #main { 
            margin-left: var(--sidebar-width); 
            padding: 20px 30px;
            box-sizing: border-box;
            min-height: 100vh;
        }

        .inventory-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #111;
            padding-bottom: 10px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-add {
            background-color: var(--sage-green);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-family: inherit;
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: bold;
        }

        /* ---------------- Dashboard Cards & Chart Styles ---------------- */
        .dashboard-top {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-cards {
            display: flex;
            flex-direction: column;
            gap: 15px;
            flex: 1; /* Takes smaller width */
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            text-align: center;
        }

        .card h3 {
            margin: 0 0 10px 0;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 0.5px;
        }

        .card .value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--sage-green);
            margin: 0;
        }
        
        .card.alert-card .value {
            color: var(--danger-red);
        }

        .chart-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            flex: 2; /* Takes larger width */
            position: relative;
            min-height: 250px;
        }

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
            <h2>Vaccine Inventory</h2>
            <div class="action-btns">
                <button class="btn-add" onclick="document.getElementById('addModal').style.display='block'">+ Add New Vaccine</button>
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
                <div class="card alert-card">
                    <h3>Low Stock Alert</h3>
                    <p class="value"><?= $low_stock_count ?></p>
                </div>
            </div>
            
            <div class="chart-container">
                <canvas id="inventoryChart"></canvas>
            </div>
        </div>

        <!-- TABS NAVIGATION -->
        <div class="tabs">
            <button class="tab active" onclick="openTab('babyTab')">Baby Vaccines</button>
            <button class="tab" onclick="openTab('maternalTab')">Maternal Vaccines</button>
            <button class="tab" onclick="openTab('historyTab')">Inventory History</button>
        </div>

        <!-- BABY VACCINES TAB -->
        <div id="babyTab" class="tab-content active">
            <table>
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
                                    <small style="color: #666;"><?= htmlspecialchars($row['description']) ?></small>
                                </td>
                                <td>
                                    Total: <?= $row['total_received'] ?? 0 ?><br>
                                    Available: <strong><?= $row['available_stock'] ?? 0 ?></strong>
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
                        <tr><td colspan="5" style="text-align:center;">No baby vaccines in inventory yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- MATERNAL VACCINES TAB -->
        <div id="maternalTab" class="tab-content">
            <table>
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
                                    <small style="color: #666;"><?= htmlspecialchars($row['description']) ?></small>
                                </td>
                                <td>
                                    Total: <?= $row['total_received'] ?? 0 ?><br>
                                    Available: <strong><?= $row['available_stock'] ?? 0 ?></strong>
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
                        <tr><td colspan="5" style="text-align:center;">No maternal vaccines in inventory yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- HISTORY TAB -->
        <div id="historyTab" class="tab-content">
            <table>
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
                        <?php while($log = $result_logs->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('M d, Y h:i A', strtotime($log['transaction_date'])) ?></td>
                                <td><strong><?= htmlspecialchars($log['vaccine_name']) ?></strong></td>
                                <td>
                                    <span style="color: <?= ($log['action_type'] == 'IN') ? '#166534' : '#dc2626' ?>; font-weight: bold;">
                                        <?= $log['action_type'] ?>
                                    </span>
                                </td>
                                <td><?= $log['quantity'] ?></td>
                                <td><?= htmlspecialchars($log['remarks']) ?></td>
                                <td><?= htmlspecialchars($log['recorded_by']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No inventory history recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
                <select name="category" required>
                    <option value="Baby">Baby Vaccine</option>
                    <option value="Maternal">Maternal Vaccine (Pregnant)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Vaccine Name</label>
                <select name="vaccine_name" required>
                    <option value="">-- Select Vaccine Name --</option>
                    <option value="BCG">BCG</option>
                    <option value="Hepatitis B">Hepatitis B</option>
                    <option value="Pentavalent">Pentavalent</option>
                    <option value="Oral Polio Vaccine (OPV)">Oral Polio Vaccine (OPV)</option>
                    <option value="Inactivated Polio Vaccine (IPV)">Inactivated Polio Vaccine (IPV)</option>
                    <option value="Pneumococcal Conjugate Vaccine (PCV)">Pneumococcal Conjugate Vaccine (PCV)</option>
                    <option value="Measles, Mumps, Rubella (MMR)">Measles, Mumps, Rubella (MMR)</option>
                    <option value="Tetanus Toxoid">Tetanus Toxoid</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description / Notes</label>
                <textarea name="description" rows="2" placeholder="Target disease or notes..."></textarea>
            </div>
            <div class="form-group">
                <label>Total Received (Quantity)</label>
                <input type="number" name="total_received" value="0" min="0" required>
            </div>
            <div class="form-group">
                <label>Available Stock</label>
                <input type="number" name="available_stock" value="0" min="0" required>
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
                <select name="category" id="edit_category" required>
                    <option value="Baby">Baby Vaccine</option>
                    <option value="Maternal">Maternal Vaccine (Pregnant)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Vaccine Name</label>
                <select name="vaccine_name" id="edit_vaccine_name" required>
                    <option value="">-- Select Vaccine Name --</option>
                    <option value="BCG">BCG</option>
                    <option value="Hepatitis B">Hepatitis B</option>
                    <option value="Pentavalent">Pentavalent</option>
                    <option value="Oral Polio Vaccine (OPV)">Oral Polio Vaccine (OPV)</option>
                    <option value="Inactivated Polio Vaccine (IPV)">Inactivated Polio Vaccine (IPV)</option>
                    <option value="Pneumococcal Conjugate Vaccine (PCV)">Pneumococcal Conjugate Vaccine (PCV)</option>
                    <option value="Measles, Mumps, Rubella (MMR)">Measles, Mumps, Rubella (MMR)</option>
                    <option value="Tetanus Toxoid">Tetanus Toxoid</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description / Notes</label>
                <textarea name="description" id="edit_description" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Total Received (Quantity)</label>
                <input type="number" name="total_received" id="edit_total_received" min="0" required>
            </div>
            <div class="form-group">
                <label>Available Stock</label>
                <input type="number" name="available_stock" id="edit_available_stock" min="0" required>
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
            </div>
            <button type="submit" name="update_vaccine" class="btn-submit">Update Vaccine</button>
        </form>
    </div>
</div>

<script>
    // Tab Switching Logic
    function openTab(tabName) {
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => content.classList.remove('active'));
        
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        document.getElementById(tabName).classList.add('active');
        event.currentTarget.classList.add('active');
    }

    // Modal Edit Logic
    function openEditModal(id, name, description, category, totalReceived, availableStock, stockDate, stockTime, receivedBy, providedBy) {
        document.getElementById('edit_vaccine_id').value = id;
        document.getElementById('edit_vaccine_name').value = name;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_category').value = category;
        document.getElementById('edit_total_received').value = totalReceived;
        document.getElementById('edit_available_stock').value = availableStock;
        document.getElementById('edit_stock_date').value = stockDate;
        document.getElementById('edit_stock_time').value = stockTime;
        document.getElementById('edit_received_by').value = receivedBy;
        document.getElementById('edit_provided_by').value = providedBy;
        
        document.getElementById('editModal').style.display = 'block';
    }

    // Chart.js Setup
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('inventoryChart').getContext('2d');
        
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