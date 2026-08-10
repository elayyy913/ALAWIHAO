<?php
session_start();
include '../db_connect.php';

// Check if logged in and if admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle form submission para mag-add ng bagong vaccine na may inventory stocks at details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vaccine'])) {
    $vaccine_name = mysqli_real_escape_string($conn, $_POST['vaccine_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $total_received = intval($_POST['total_received']);
    $available_stock = intval($_POST['available_stock']);
    
    // Safe checking para sa Date at Time
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
                $message = "Vaccine added successfully!";
            } else {
                $message = "Error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Handle form submission para mag-update ng existing vaccine
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
                $message = "Vaccine updated successfully!";
            } else {
                $message = "Error updating: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Handle Delete Request para sa isang partikular na vaccine
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

// Handle Empty Table Request para idelete lahat ng records
if (isset($_POST['empty_inventory'])) {
    $sql = "TRUNCATE TABLE vaccines";
    if ($conn->query($sql) === TRUE) {
        $message = "Vaccine inventory table cleared successfully!";
    } else {
        $message = "Error clearing table: " . $conn->error;
    }
}

// Fetch Baby Vaccines
$sql_baby = "SELECT * FROM vaccines WHERE category = 'Baby' ORDER BY created_at DESC";
$result_baby = $conn->query($sql_baby);

// Fetch Maternal Vaccines
$sql_maternal = "SELECT * FROM vaccines WHERE category = 'Maternal' ORDER BY created_at DESC";
$result_maternal = $conn->query($sql_maternal);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccine Inventory | Alawihao Health</title>
    <style>
        :root {
            --sage-green: #718355;
            --light-beige: #fdfbf7;
            --border-color: #d1d5db;
            --sidebar-width: 280px;
            --danger-red: #dc2626;
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

        .action-btns {
            display: flex;
            gap: 10px;
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

        .btn-empty {
            background-color: var(--danger-red);
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

        /* Tabs Styling */
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

        /* Table Styling */
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

        /* Modal Styling */
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
            padding: 25px;
            border-radius: 4px;
            width: 450px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #4b5563;
            margin-bottom: 5px;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 2px;
            font-family: inherit;
            box-sizing: border-box;
        }

        .btn-submit {
            width: 100%;
            background-color: var(--sage-green);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            text-transform: uppercase;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .success-msg {
            color: #166534; background: #f0fdf4; padding: 10px;
            border-left: 3px solid var(--sage-green); margin-bottom: 15px;
        }

        .action-links a {
            font-weight: bold;
            text-decoration: none;
            margin-right: 10px;
        }

        .action-edit {
            color: var(--sage-green);
        }

        .action-delete {
            color: var(--danger-red);
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
            <h2>Vaccine Inventory</h2>
            <div class="action-btns">
                <form method="POST" onsubmit="return confirm('Sigurado ka bang gusto mong idelete LAHAT ng nakatala sa vaccine inventory? Hindi na ito maibabalik.');" style="display:inline;">
                    <button type="submit" name="empty_inventory" class="btn-empty">Empty Table</button>
                </form>
                <button class="btn-add" onclick="document.getElementById('addModal').style.display='block'">+ Add New Vaccine</button>
            </div>
        </div>

        <?php if(!empty($message)) echo "<div class='success-msg'>$message</div>"; ?>

        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab active" onclick="openTab('babyTab')">Baby Vaccines</button>
            <button class="tab" onclick="openTab('maternalTab')">Maternal Vaccines</button>
        </div>

        <!-- Baby Vaccines Tab Content -->
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

        <!-- Maternal Vaccines Tab Content -->
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

    </div>
</div>

<!-- Add Vaccine Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
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
                <input type="text" name="vaccine_name" placeholder="e.g. BCG or Tetanus Toxoid" required>
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

<!-- Edit Vaccine Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
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
                <input type="text" name="vaccine_name" id="edit_vaccine_name" required>
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
    function openTab(tabName) {
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => content.classList.remove('active'));
        
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        document.getElementById(tabName).classList.add('active');
        event.currentTarget.classList.add('active');
    }

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
</script>

</body>
</html>