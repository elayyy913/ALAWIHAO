<?php
session_start();
include '../db_connect.php';

// Check if logged in and if admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle form submission para mag-add ng bagong vaccine na may inventory stocks
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vaccine'])) {
    $vaccine_name = mysqli_real_escape_string($conn, $_POST['vaccine_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $total_received = intval($_POST['total_received']);
    $available_stock = intval($_POST['available_stock']);
    $stock_in_date = mysqli_real_escape_string($conn, $_POST['stock_in_date']);

    $sql = "INSERT INTO vaccines (vaccine_name, description, category, total_received, available_stock, stock_in_date) VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssiiis", $vaccine_name, $description, $category, $total_received, $available_stock, $stock_in_date);
        if ($stmt->execute()) {
            $message = "Vaccine added successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
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
    $stock_in_date = mysqli_real_escape_string($conn, $_POST['stock_in_date']);

    $sql = "UPDATE vaccines SET vaccine_name=?, description=?, category=?, total_received=?, available_stock=?, stock_in_date=? WHERE id=?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssiiisi", $vaccine_name, $description, $category, $total_received, $available_stock, $stock_in_date, $id);
        if ($stmt->execute()) {
            $message = "Vaccine updated successfully!";
        } else {
            $message = "Error updating: " . $conn->error;
        }
        $stmt->close();
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

        /* Modal Styling for Modals */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; 
            width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.5); 
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 25px;
            border-radius: 4px;
            width: 400px;
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
    </style>
</head>
<body>

<?php 
    if ($_SESSION['role'] === 'Super Admin') {
        include 'super_admin_sidebar.php';
    } else {
        include 'admin_sidebar.php'; 
    }
?>

<div id="main">
    <div class="inventory-container">
        
        <div class="page-header">
            <h2>Vaccine Inventory</h2>
            <button class="btn-add" onclick="document.getElementById('addModal').style.display='block'">+ Add New Vaccine</button>
        </div>

        <?php if($message) echo "<div class='success-msg'>$message</div>"; ?>

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
                        <th>Description</th>
                        <th>Total Received</th>
                        <th>Available Stock</th>
                        <th>Date Received</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_baby->num_rows > 0): ?>
                        <?php while($row = $result_baby->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['vaccine_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= isset($row['total_received']) ? $row['total_received'] : 0 ?></td>
                                <td><strong><?= isset($row['available_stock']) ? $row['available_stock'] : 0 ?></strong></td>
                                <td><?= !empty($row['stock_in_date']) ? date('M d, Y', strtotime($row['stock_in_date'])) : date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="#" style="color: var(--sage-green); font-weight: bold; text-decoration: none;" 
                                       onclick="openEditModal(
                                           '<?= $row['id'] ?>', 
                                           '<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>', 
                                           '<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>', 
                                           '<?= $row['category'] ?>', 
                                           '<?= $row['total_received'] ?>', 
                                           '<?= $row['available_stock'] ?>', 
                                           '<?= $row['stock_in_date'] ?? date('Y-m-d', strtotime($row['created_at'])) ?>'
                                       )">Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No baby vaccines in inventory yet.</td></tr>
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
                        <th>Description</th>
                        <th>Total Received</th>
                        <th>Available Stock</th>
                        <th>Date Received</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_maternal->num_rows > 0): ?>
                        <?php while($row = $result_maternal->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['vaccine_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= isset($row['total_received']) ? $row['total_received'] : 0 ?></td>
                                <td><strong><?= isset($row['available_stock']) ? $row['available_stock'] : 0 ?></strong></td>
                                <td><?= !empty($row['stock_in_date']) ? date('M d, Y', strtotime($row['stock_in_date'])) : date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="#" style="color: var(--sage-green); font-weight: bold; text-decoration: none;" 
                                       onclick="openEditModal(
                                           '<?= $row['id'] ?>', 
                                           '<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>', 
                                           '<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>', 
                                           '<?= $row['category'] ?>', 
                                           '<?= $row['total_received'] ?>', 
                                           '<?= $row['available_stock'] ?>', 
                                           '<?= $row['stock_in_date'] ?? date('Y-m-d', strtotime($row['created_at'])) ?>'
                                       )">Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No maternal vaccines in inventory yet.</td></tr>
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
                <label>Date Received</label>
                <input type="date" name="stock_in_date" value="<?= date('Y-m-d') ?>" required>
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
                <label>Date Received</label>
                <input type="date" name="stock_in_date" id="edit_stock_in_date" required>
            </div>
            <button type="submit" name="update_vaccine" class="btn-submit">Update Vaccine</button>
        </form>
    </div>
</div>

<script>
    function openTab(tabName) {
        // Hide all tab contents
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => content.classList.remove('active'));
        
        // Remove active class from all tabs
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        // Show the selected tab content and activate the tab button
        document.getElementById(tabName).classList.add('active');
        event.currentTarget.classList.add('active');
    }

    function openEditModal(id, name, description, category, totalReceived, availableStock, stockInDate) {
        document.getElementById('edit_vaccine_id').value = id;
        document.getElementById('edit_vaccine_name').value = name;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_category').value = category;
        document.getElementById('edit_total_received').value = totalReceived;
        document.getElementById('edit_available_stock').value = availableStock;
        document.getElementById('edit_stock_in_date').value = stockInDate;
        
        document.getElementById('editModal').style.display = 'block';
    }
</script>

</body>
</html>