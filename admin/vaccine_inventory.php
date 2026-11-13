<?php
session_start();
include '../db_connect.php';

// Check if logged in and if admin/super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    header("Location: login.php");
    exit();
}

$inventory_report_mode = '';
if (($_GET['print_inventory'] ?? '') === '1') {
    $inventory_report_mode = 'print';
} elseif (($_GET['export_inventory'] ?? '') === 'word') {
    $inventory_report_mode = 'word';
}

if ($inventory_report_mode !== '') {
    $report_result = $conn->query("SELECT vaccine_name, category, total_received, available_stock
        FROM vaccines
        ORDER BY category ASC, vaccine_name ASC");
    if (!$report_result) {
        http_response_code(500);
        die("Could not generate vaccine inventory report: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
    }

    $report_logo_path = __DIR__ . '/../images/logo.jpg';
    $report_logo_contents = is_readable($report_logo_path) ? file_get_contents($report_logo_path) : false;
    if ($report_logo_contents === false) {
        http_response_code(500);
        die("Could not load the Alawihao logo for the vaccine inventory report.");
    }
    $report_logo_data = 'data:image/jpeg;base64,' . base64_encode($report_logo_contents);
    $report_date = date('F j, Y g:i A');
    if ($inventory_report_mode === 'word') {
        header('Content-Type: application/msword; charset=UTF-8');
        header('Content-Disposition: attachment; filename="vaccine-inventory-' . date('Y-m-d') . '.doc"');
    } else {
        header('Content-Type: text/html; charset=UTF-8');
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Vaccine Inventory Report</title>
        <style>
            body { font-family: Arial, sans-serif; color: #222; margin: 32px; }
            .report-header { width: 100%; border-bottom: 2px solid #43533a; margin-bottom: 22px; padding-bottom: 14px; }
            .report-header td { border: 0; padding: 0; vertical-align: middle; }
            .report-logo-cell { width: 105px; }
            .report-logo { display: block; width: 88px; height: 88px; object-fit: contain; }
            .report-identity { text-align: center; }
            .report-identity p { margin: 0 0 5px; font-size: 11px; }
            .report-identity h1 { margin: 0 0 4px; color: #26351f; font-size: 19px; letter-spacing: .6px; }
            .report-identity h2 { margin: 0; color: #43533a; font-size: 14px; }
            .report-title { margin: 20px 0 5px; text-align: center; font-size: 17px; }
            .report-date { margin: 0 0 18px; color: #555; font-size: 12px; text-align: center; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #aab2a4; padding: 9px 10px; text-align: left; }
            th { background: #e9eee4; }
            .status-low { color: #805411; font-weight: bold; }
            .status-out { color: #a52a26; font-weight: bold; }
            .status-in { color: #27633a; font-weight: bold; }
            .midwife-signature { width: 100%; margin-top: 34px; }
            .midwife-signature td { width: 50%; border: 0; padding: 0; vertical-align: top; }
            .midwife-signature-box { width: 280px; margin-left: auto; text-align: center; }
            .midwife-signature-label { margin: 0 0 7px; text-align: left; font-size: 12px; font-weight: bold; }
            .midwife-signature-line { height: 48px; border-bottom: 1px solid #333; }
            .midwife-signature-caption { margin: 4px 0 0; font-size: 11px; }
            .report-actions { margin-bottom: 18px; }
            .report-actions button { padding: 8px 14px; cursor: pointer; }
            @page { margin: 0; }
            @media print {
                body { margin: 12mm; }
                .report-actions { display: none; }
                tr { page-break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <?php if ($inventory_report_mode === 'print'): ?>
            <div class="report-actions">
                <button type="button" onclick="window.print()">Print Inventory</button>
                <button type="button" onclick="window.close()">Close</button>
            </div>
        <?php endif; ?>
        <table class="report-header">
            <tr>
                <td class="report-logo-cell">
                    <img class="report-logo" src="<?= $report_logo_data ?>" alt="Barangay Alawihao seal">
                </td>
                <td class="report-identity">
                    <p>Municipality of Daet &bull; Province of Camarines Norte</p>
                    <h1>ALAWIHAO HEALTH CENTER</h1>
                    <h2>Brgy. Alawihao, Daet, Camarines Norte</h2>
                </td>
                <td class="report-logo-cell"></td>
            </tr>
        </table>
        <h2 class="report-title">Vaccine Inventory Report</h2>
        <p class="report-date">Current stock | Generated <?= htmlspecialchars($report_date, ENT_QUOTES, 'UTF-8') ?></p>
        <table>
            <thead>
                <tr>
                    <th>Vaccine</th>
                    <th>Category</th>
                    <th>Total Received</th>
                    <th>Available Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($report_result->num_rows > 0): ?>
                    <?php while ($report_row = $report_result->fetch_assoc()):
                        $report_stock = (int) $report_row['available_stock'];
                        $report_status = $report_stock <= 0 ? 'Out of stock' : ($report_stock <= 10 ? 'Low stock' : 'In stock');
                        $report_status_class = $report_stock <= 0 ? 'status-out' : ($report_stock <= 10 ? 'status-low' : 'status-in');
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($report_row['vaccine_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($report_row['category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $report_row['total_received'] ?></td>
                            <td><?= $report_stock ?></td>
                            <td class="<?= $report_status_class ?>"><?= $report_status ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No vaccines in inventory.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <table class="midwife-signature">
            <tr>
                <td></td>
                <td>
                    <div class="midwife-signature-box">
                        <p class="midwife-signature-label">Prepared by:</p>
                        <div class="midwife-signature-line"></div>
                        <p class="midwife-signature-caption">Signature over Printed Name of Midwife</p>
                    </div>
                </td>
            </tr>
        </table>
        <?php if ($inventory_report_mode === 'print'): ?>
            <script>
                window.addEventListener('load', function() {
                    window.print();
                });
            </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit();
}

$create_vaccine_batches_table = "CREATE TABLE IF NOT EXISTS vaccine_stock_batches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    vaccine_id INT NOT NULL,
    batch_number VARCHAR(100) NOT NULL,
    quantity_received INT NOT NULL,
    manufactured_date DATE NULL,
    expiration_date DATE NULL,
    stock_in_date DATETIME NOT NULL,
    received_by VARCHAR(255) NOT NULL,
    provided_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vaccine_stock_batches_vaccine_expiry (vaccine_id, expiration_date),
    INDEX idx_vaccine_stock_batches_expiration (expiration_date)
) ENGINE=InnoDB";
if (!$conn->query($create_vaccine_batches_table)) {
    die("Could not prepare vaccine batch tracking: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}

$create_vaccine_returns_table = "CREATE TABLE IF NOT EXISTS vaccine_returns (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    vaccine_id INT NOT NULL,
    batch_id BIGINT UNSIGNED NOT NULL,
    vaccine_name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    batch_number VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    returned_by VARCHAR(255) NOT NULL,
    reason TEXT NULL,
    returned_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vaccine_returns_date (returned_at),
    INDEX idx_vaccine_returns_batch (batch_id)
) ENGINE=InnoDB";
if (!$conn->query($create_vaccine_returns_table)) {
    die("Could not prepare returned vaccine tracking: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}

$batch_number_column = $conn->query("SHOW COLUMNS FROM vaccine_stock_batches LIKE 'batch_number'");
if (!$batch_number_column) {
    die("Could not check vaccine batch number schema: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
if ($batch_number_column->num_rows === 0 && !$conn->query(
    "ALTER TABLE vaccine_stock_batches ADD COLUMN batch_number VARCHAR(100) NOT NULL DEFAULT 'Legacy batch' AFTER vaccine_id"
)) {
    die("Could not update vaccine batch number schema: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}

$seed_legacy_batches = "INSERT INTO vaccine_stock_batches
    (vaccine_id, batch_number, quantity_received, manufactured_date, expiration_date, stock_in_date, received_by, provided_by)
    SELECT v.id, 'Legacy batch', GREATEST(COALESCE(v.total_received, 0), COALESCE(v.available_stock, 0)),
        NULL, NULL, COALESCE(v.stock_in_date, v.created_at, NOW()),
        COALESCE(NULLIF(v.received_by, ''), 'Legacy stock'),
        COALESCE(NULLIF(v.provided_by, ''), 'Previously recorded stock')
    FROM vaccines v
    WHERE v.available_stock > 0
      AND NOT EXISTS (
          SELECT 1 FROM vaccine_stock_batches b WHERE b.vaccine_id = v.id
      )";
if (!$conn->query($seed_legacy_batches)) {
    die("Could not initialize vaccine batch tracking: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}

$message = "";

// ---------------------------------------------------------
// HANDLE FORM SUBMISSIONS (Add, Update, Delete)
// ---------------------------------------------------------

// Add new stock to an existing vaccine or create its inventory entry
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vaccine'])) {
    $vaccine_name = trim($_POST['vaccine_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $batch_number = trim($_POST['batch_number'] ?? '');
    $quantity_received = filter_var($_POST['quantity_received'] ?? null, FILTER_VALIDATE_INT);
    $stock_date = trim($_POST['stock_date'] ?? date('Y-m-d'));
    $stock_time = trim($_POST['stock_time'] ?? date('H:i'));
    $manufactured_date = trim($_POST['manufactured_date'] ?? '');
    $expiration_date = trim($_POST['expiration_date'] ?? '');
    $stock_in_datetime = $stock_date . ' ' . $stock_time . ':00';

    $received_by = trim($_POST['received_by'] ?? '');
    $provided_by = trim($_POST['provided_by'] ?? '');
    $valid_stock_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $stock_date)
        && checkdate((int) substr($stock_date, 5, 2), (int) substr($stock_date, 8, 2), (int) substr($stock_date, 0, 4));
    $valid_stock_time = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $stock_time);
    $valid_manufactured_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $manufactured_date)
        && checkdate((int) substr($manufactured_date, 5, 2), (int) substr($manufactured_date, 8, 2), (int) substr($manufactured_date, 0, 4));
    $valid_expiration_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiration_date)
        && checkdate((int) substr($expiration_date, 5, 2), (int) substr($expiration_date, 8, 2), (int) substr($expiration_date, 0, 4));

    if ($quantity_received === false || $quantity_received < 1) {
        $message = "Error: Enter a quantity greater than zero.";
    } elseif ($vaccine_name === '' || !in_array($category, ['Baby', 'Maternal'], true)) {
        $message = "Error: Select a valid vaccine and category.";
    } elseif ($batch_number === '' || strlen($batch_number) > 100) {
        $message = "Error: Enter a batch number with no more than 100 characters.";
    } elseif (empty($received_by) || empty($provided_by) || !$valid_stock_date || !$valid_stock_time) {
        $message = "Error: Source, receiver, date, and time are required.";
    } elseif (!$valid_manufactured_date || !$valid_expiration_date
        || $manufactured_date > $stock_date || $expiration_date <= $stock_date
        || $expiration_date <= $manufactured_date) {
        $message = "Error: Enter valid manufacture and expiration dates. Manufacture must be on or before stock receipt, and expiration must be after both.";
    } else {
        if (!$conn->begin_transaction()) {
            $message = "Error: Could not start the inventory update: " . $conn->error;
        } else {
            $existing_stmt = $conn->prepare("SELECT id, total_received, available_stock FROM vaccines WHERE vaccine_name = ? AND category = ? ORDER BY id ASC FOR UPDATE");
            if (!$existing_stmt) {
                $error = $conn->error;
                $conn->rollback();
                $message = "Error checking vaccine inventory: " . $error;
            } else {
                $existing_stmt->bind_param("ss", $vaccine_name, $category);
                if (!$existing_stmt->execute()) {
                    $error = $conn->error;
                    $existing_stmt->close();
                    $conn->rollback();
                    $message = "Error checking vaccine inventory: " . $error;
                } else {
                    $existing_rows = [];
                    $existing_result = $existing_stmt->get_result();
                    while ($existing_row = $existing_result->fetch_assoc()) {
                        $existing_rows[] = $existing_row;
                    }
                    $existing_stmt->close();

                    $save_ok = true;
                    $save_error = '';
                    $is_existing_vaccine = count($existing_rows) > 0;

                    if (!$is_existing_vaccine) {
                        $total_received = $quantity_received;
                        $available_stock = $quantity_received;
                        $insert_stmt = $conn->prepare("INSERT INTO vaccines (vaccine_name, description, category, total_received, available_stock, stock_in_date, received_by, provided_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        if (!$insert_stmt) {
                            $save_ok = false;
                            $save_error = "Error preparing vaccine inventory update: " . $conn->error;
                        } else {
                            $insert_stmt->bind_param("sssiisss", $vaccine_name, $description, $category, $total_received, $available_stock, $stock_in_datetime, $received_by, $provided_by);
                            if (!$insert_stmt->execute()) {
                                $save_ok = false;
                                $save_error = "Error adding vaccine: " . $conn->error;
                            } else {
                                $vaccine_id = $insert_stmt->insert_id;
                            }
                            $insert_stmt->close();
                        }
                    } else {
                        $vaccine_id = (int) $existing_rows[0]['id'];
                        $total_received = 0;
                        $available_stock = 0;
                        foreach ($existing_rows as $existing_row) {
                            $total_received += (int) $existing_row['total_received'];
                            $available_stock += (int) $existing_row['available_stock'];
                        }
                        $total_received += $quantity_received;
                        $available_stock += $quantity_received;

                        foreach (array_slice($existing_rows, 1) as $duplicate_row) {
                            $duplicate_id = (int) $duplicate_row['id'];
                            $move_logs_stmt = $conn->prepare("UPDATE vaccine_logs SET vaccine_id = ? WHERE vaccine_id = ?");
                            if (!$move_logs_stmt) {
                                $save_ok = false;
                                $save_error = "Error consolidating vaccine history: " . $conn->error;
                                break;
                            }
                            $move_logs_stmt->bind_param("ii", $vaccine_id, $duplicate_id);
                            if (!$move_logs_stmt->execute()) {
                                $save_ok = false;
                                $save_error = "Error consolidating vaccine history: " . $conn->error;
                            }
                            $move_logs_stmt->close();
                            if (!$save_ok) {
                                break;
                            }

                            $move_batches_stmt = $conn->prepare("UPDATE vaccine_stock_batches SET vaccine_id = ? WHERE vaccine_id = ?");
                            if (!$move_batches_stmt) {
                                $save_ok = false;
                                $save_error = "Error consolidating vaccine batch tracking: " . $conn->error;
                                break;
                            }
                            $move_batches_stmt->bind_param("ii", $vaccine_id, $duplicate_id);
                            if (!$move_batches_stmt->execute()) {
                                $save_ok = false;
                                $save_error = "Error consolidating vaccine batch tracking: " . $move_batches_stmt->error;
                            }
                            $move_batches_stmt->close();
                            if (!$save_ok) {
                                break;
                            }

                            $delete_duplicate_stmt = $conn->prepare("DELETE FROM vaccines WHERE id = ?");
                            if (!$delete_duplicate_stmt) {
                                $save_ok = false;
                                $save_error = "Error consolidating duplicate vaccine entries: " . $conn->error;
                                break;
                            }
                            $delete_duplicate_stmt->bind_param("i", $duplicate_id);
                            if (!$delete_duplicate_stmt->execute()) {
                                $save_ok = false;
                                $save_error = "Error consolidating duplicate vaccine entries: " . $conn->error;
                            }
                            $delete_duplicate_stmt->close();
                            if (!$save_ok) {
                                break;
                            }
                        }

                        if ($save_ok) {
                            $batch_stmt = $conn->prepare("INSERT INTO vaccine_stock_batches
                                (vaccine_id, batch_number, quantity_received, manufactured_date, expiration_date, stock_in_date, received_by, provided_by)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            if (!$batch_stmt) {
                                $save_ok = false;
                                $save_error = "Error preparing vaccine batch tracking: " . $conn->error;
                            } else {
                                $batch_stmt->bind_param(
                                    "isisssss",
                                    $vaccine_id,
                                    $batch_number,
                                    $quantity_received,
                                    $manufactured_date,
                                    $expiration_date,
                                    $stock_in_datetime,
                                    $received_by,
                                    $provided_by
                                );
                                if (!$batch_stmt->execute()) {
                                    $save_ok = false;
                                    $save_error = "Error saving vaccine batch dates: " . $batch_stmt->error;
                                }
                                $batch_stmt->close();
                            }
                        }

                        if ($save_ok) {
                            $update_stock_sql = "UPDATE vaccines SET description = COALESCE(NULLIF(?, ''), description), total_received = ?, available_stock = ?, stock_in_date = ?, received_by = ?, provided_by = ? WHERE id = ?";
                            $update_stock_stmt = $conn->prepare($update_stock_sql);
                            if (!$update_stock_stmt) {
                                $save_ok = false;
                                $save_error = "Error preparing stock update: " . $conn->error;
                            } else {
                                $update_stock_stmt->bind_param("siisssi", $description, $total_received, $available_stock, $stock_in_datetime, $received_by, $provided_by, $vaccine_id);
                                if (!$update_stock_stmt->execute()) {
                                    $save_ok = false;
                                    $save_error = "Error updating vaccine stock: " . $conn->error;
                                }
                                $update_stock_stmt->close();
                            }
                        }
                    }

                    if ($save_ok) {
                        $remarks = "Stock received from " . $provided_by;
                        $log_stmt = $conn->prepare("INSERT INTO vaccine_logs (vaccine_id, action_type, quantity, remarks, transaction_date, recorded_by) VALUES (?, 'IN', ?, ?, ?, ?)");
                        if (!$log_stmt) {
                            $save_ok = false;
                            $save_error = "Error preparing vaccine stock history: " . $conn->error;
                        } else {
                            $log_stmt->bind_param("iisss", $vaccine_id, $quantity_received, $remarks, $stock_in_datetime, $received_by);
                            if (!$log_stmt->execute()) {
                                $save_ok = false;
                                $save_error = "Error recording vaccine stock history: " . $conn->error;
                            }
                            $log_stmt->close();
                        }
                    }

                    if ($save_ok && $conn->commit()) {
                        if ($is_existing_vaccine) {
                            $message = "Stock added successfully.";
                            if (count($existing_rows) > 1) {
                                $message .= " Duplicate entries were combined.";
                            }
                        } else {
                            $message = "Vaccine and stock added successfully.";
                        }
                    } else {
                        if ($save_ok) {
                            $save_error = "Error saving vaccine update: " . $conn->error;
                        }
                        $conn->rollback();
                        $message = $save_error;
                    }
                }
            }
        }
    }
}

// Update vaccine details, current stock, and selected batch tracking fields.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_vaccine'])) {
    $id = (int) ($_POST['vaccine_id'] ?? 0);
    $batch_id = filter_var($_POST['batch_id'] ?? 0, FILTER_VALIDATE_INT);
    $vaccine_name = trim($_POST['vaccine_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $new_available_stock = filter_var($_POST['available_stock'] ?? null, FILTER_VALIDATE_INT);
    $batch_number = trim($_POST['batch_number'] ?? '');
    $manufactured_date = trim($_POST['manufactured_date'] ?? '');
    $expiration_date = trim($_POST['expiration_date'] ?? '');
    $stock_in_datetime_input = trim($_POST['stock_in_datetime'] ?? '');
    $batch_received_by = trim($_POST['batch_received_by'] ?? '');
    $batch_provided_by = trim($_POST['batch_provided_by'] ?? '');
    $stock_in_datetime = '';
    $valid_manufactured_date = $manufactured_date === '' || (
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $manufactured_date)
        && checkdate((int) substr($manufactured_date, 5, 2), (int) substr($manufactured_date, 8, 2), (int) substr($manufactured_date, 0, 4))
    );
    $valid_expiration_date = $expiration_date === '' || (
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiration_date)
        && checkdate((int) substr($expiration_date, 5, 2), (int) substr($expiration_date, 8, 2), (int) substr($expiration_date, 0, 4))
    );
    $stock_datetime = DateTime::createFromFormat('!Y-m-d\TH:i', $stock_in_datetime_input);
    $valid_stock_datetime = $stock_datetime && $stock_datetime->format('Y-m-d\TH:i') === $stock_in_datetime_input;
    if ($valid_stock_datetime) {
        $stock_in_datetime = $stock_datetime->format('Y-m-d H:i:s');
    }

    if ($id < 1 || $new_available_stock === false || $new_available_stock < 0 || $batch_id === false || $batch_id < 0) {
        $message = "Error: Enter a valid vaccine, batch, and stock quantity.";
    } elseif ($vaccine_name === '' || !in_array($category, ['Baby', 'Maternal'], true)) {
        $message = "Error: Select a valid vaccine and category.";
    } elseif ($batch_id > 0 && (
        $batch_number === '' || strlen($batch_number) > 100
        || !$valid_manufactured_date || !$valid_expiration_date
        || ($manufactured_date !== '' && $expiration_date !== '' && $manufactured_date > $expiration_date)
        || !$valid_stock_datetime
        || $batch_received_by === '' || strlen($batch_received_by) > 255
        || $batch_provided_by === '' || strlen($batch_provided_by) > 255
    )) {
        $message = "Error: Enter valid details for the selected vaccine batch.";
    } elseif (!$conn->begin_transaction()) {
        $message = "Error: Could not start the vaccine update: " . $conn->error;
    } else {
        $save_ok = true;
        $save_error = '';
        $current_stmt = $conn->prepare("SELECT available_stock FROM vaccines WHERE id = ? FOR UPDATE");
        if (!$current_stmt) {
            $save_ok = false;
            $save_error = "Error checking current vaccine stock: " . $conn->error;
        } else {
            $current_stmt->bind_param("i", $id);
            if (!$current_stmt->execute()) {
                $save_ok = false;
                $save_error = "Error checking current vaccine stock: " . $current_stmt->error;
            } else {
                $current_vaccine = $current_stmt->get_result()->fetch_assoc();
                if (!$current_vaccine) {
                    $save_ok = false;
                    $save_error = "Error: Vaccine not found.";
                }
            }
            $current_stmt->close();
        }

        if ($save_ok && $batch_id > 0) {
            $batch_check_stmt = $conn->prepare("SELECT id FROM vaccine_stock_batches WHERE id = ? AND vaccine_id = ? FOR UPDATE");
            if (!$batch_check_stmt) {
                $save_ok = false;
                $save_error = "Error checking vaccine batch: " . $conn->error;
            } else {
                $batch_check_stmt->bind_param("ii", $batch_id, $id);
                if (!$batch_check_stmt->execute()) {
                    $save_ok = false;
                    $save_error = "Error checking vaccine batch: " . $batch_check_stmt->error;
                } elseif (!$batch_check_stmt->get_result()->fetch_assoc()) {
                    $save_ok = false;
                    $save_error = "Error: The selected batch does not belong to this vaccine.";
                }
                $batch_check_stmt->close();
            }
        }

        if ($save_ok) {
            $duplicate_stmt = $conn->prepare("SELECT id FROM vaccines WHERE vaccine_name = ? AND category = ? AND id <> ? LIMIT 1");
            if (!$duplicate_stmt) {
                $save_ok = false;
                $save_error = "Error checking for duplicate vaccines: " . $conn->error;
            } else {
                $duplicate_stmt->bind_param("ssi", $vaccine_name, $category, $id);
                if (!$duplicate_stmt->execute()) {
                    $save_ok = false;
                    $save_error = "Error checking for duplicate vaccines: " . $duplicate_stmt->error;
                } elseif ($duplicate_stmt->get_result()->num_rows > 0) {
                    $save_ok = false;
                    $save_error = "Error: This vaccine already exists in the selected category. Use Add / Receive Stock to combine its stock.";
                }
                $duplicate_stmt->close();
            }
        }

        if ($save_ok) {
            $old_available_stock = (int) $current_vaccine['available_stock'];
            $stock_difference = $new_available_stock - $old_available_stock;
            $update_stmt = $conn->prepare("UPDATE vaccines SET vaccine_name = ?, description = ?, category = ?, available_stock = ? WHERE id = ?");
            if (!$update_stmt) {
                $save_ok = false;
                $save_error = "Error preparing vaccine update: " . $conn->error;
            } else {
                $update_stmt->bind_param("sssii", $vaccine_name, $description, $category, $new_available_stock, $id);
                if (!$update_stmt->execute()) {
                    $save_ok = false;
                    $save_error = "Error updating vaccine details: " . $update_stmt->error;
                }
                $update_stmt->close();
            }
        }

        if ($save_ok && $batch_id > 0) {
            $update_batch_stmt = $conn->prepare("UPDATE vaccine_stock_batches
                SET batch_number = ?, manufactured_date = NULLIF(?, ''), expiration_date = NULLIF(?, ''),
                    stock_in_date = ?, received_by = ?, provided_by = ?
                WHERE id = ? AND vaccine_id = ?");
            if (!$update_batch_stmt) {
                $save_ok = false;
                $save_error = "Error preparing vaccine batch update: " . $conn->error;
            } else {
                $update_batch_stmt->bind_param(
                    "ssssssii",
                    $batch_number,
                    $manufactured_date,
                    $expiration_date,
                    $stock_in_datetime,
                    $batch_received_by,
                    $batch_provided_by,
                    $batch_id,
                    $id
                );
                if (!$update_batch_stmt->execute()) {
                    $save_ok = false;
                    $save_error = "Error updating vaccine batch details: " . $update_batch_stmt->error;
                }
                $update_batch_stmt->close();
            }
        }

        if ($save_ok && $stock_difference !== 0) {
            $action_type = $stock_difference > 0 ? 'IN' : 'OUT';
            $adjustment_quantity = abs($stock_difference);
            $adjustment_remarks = "Manual stock quantity edit";
            $adjustment_datetime = date('Y-m-d H:i:s');
            $recorded_by = 'Admin ID ' . (int) $_SESSION['user_id'];
            $log_stmt = $conn->prepare("INSERT INTO vaccine_logs (vaccine_id, action_type, quantity, remarks, transaction_date, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$log_stmt) {
                $save_ok = false;
                $save_error = "Error preparing stock quantity history: " . $conn->error;
            } else {
                $log_stmt->bind_param("isisss", $id, $action_type, $adjustment_quantity, $adjustment_remarks, $adjustment_datetime, $recorded_by);
                if (!$log_stmt->execute()) {
                    $save_ok = false;
                    $save_error = "Error recording stock quantity change: " . $log_stmt->error;
                }
                $log_stmt->close();
            }
        }

        if ($save_ok && $conn->commit()) {
            $message = $stock_difference === 0
                ? "Vaccine and batch details updated successfully."
                : "Vaccine, batch details, and current stock updated successfully.";
        } else {
            if ($save_ok) {
                $save_error = "Error saving vaccine update: " . $conn->error;
            }
            $conn->rollback();
            $message = $save_error;
        }
    }
}

// Delete Vaccine
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if (!$conn->begin_transaction()) {
        $message = "Error starting vaccine deletion: " . $conn->error;
    } else {
        $batch_delete_stmt = $conn->prepare("DELETE FROM vaccine_stock_batches WHERE vaccine_id = ?");
        $vaccine_delete_stmt = $conn->prepare("DELETE FROM vaccines WHERE id = ?");
        if (!$batch_delete_stmt || !$vaccine_delete_stmt) {
            $delete_error = $conn->error;
            if ($batch_delete_stmt) $batch_delete_stmt->close();
            if ($vaccine_delete_stmt) $vaccine_delete_stmt->close();
            $conn->rollback();
            $message = "Error preparing vaccine deletion: " . $delete_error;
        } else {
            $batch_delete_stmt->bind_param("i", $delete_id);
            $delete_ok = $batch_delete_stmt->execute();
            $delete_error = $batch_delete_stmt->error;
            $batch_delete_stmt->close();

            if ($delete_ok) {
                $vaccine_delete_stmt->bind_param("i", $delete_id);
                $delete_ok = $vaccine_delete_stmt->execute();
                $delete_error = $vaccine_delete_stmt->error;
            }
            $vaccine_delete_stmt->close();

            if ($delete_ok && $conn->commit()) {
                $message = "Vaccine deleted successfully!";
            } else {
                $conn->rollback();
                $message = "Error deleting record: " . ($delete_error ?: $conn->error);
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['record_vaccine_return'])) {
    $batch_id = filter_var($_POST['return_batch_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $return_quantity = filter_var($_POST['return_quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $returned_by = trim((string) ($_POST['returned_by'] ?? ''));
    $return_reason = trim((string) ($_POST['return_reason'] ?? ''));

    if (!$batch_id || !$return_quantity || $returned_by === '' || strlen($returned_by) > 255) {
        $message = "Error: Select a valid vaccine batch, quantity, and returned-by name.";
    } elseif (!$conn->begin_transaction()) {
        $message = "Error starting returned vaccine transaction: " . $conn->error;
    } else {
        $batch_stmt = $conn->prepare("SELECT v.id AS vaccine_id, v.vaccine_name, v.category,
                b.batch_number, b.expiration_date
            FROM vaccine_stock_batches b
            INNER JOIN vaccines v ON v.id = b.vaccine_id
            WHERE b.id = ?
                AND (b.expiration_date IS NULL OR b.expiration_date >= CURDATE())
                AND v.available_stock > 0
            FOR UPDATE");
        if (!$batch_stmt) {
            $batch_error = $conn->error;
            $conn->rollback();
            $message = "Error preparing vaccine batch validation: " . $batch_error;
        } else {
            $batch_stmt->bind_param("i", $batch_id);
            if (!$batch_stmt->execute()) {
                $batch_error = $batch_stmt->error;
                $batch_stmt->close();
                $conn->rollback();
                $message = "Error validating vaccine batch: " . $batch_error;
            } else {
                $return_batch = $batch_stmt->get_result()->fetch_assoc();
                $batch_stmt->close();

                if (!$return_batch) {
                    $conn->rollback();
                    $message = "Error: Only batches with existing vaccine stock and no recorded or unexpired expiration date can be returned.";
                } else {
                    $return_datetime = date('Y-m-d H:i:s');
                    $insert_return = $conn->prepare("INSERT INTO vaccine_returns
                        (vaccine_id, batch_id, vaccine_name, category, batch_number, quantity, returned_by, reason, returned_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $update_stock = $conn->prepare("UPDATE vaccines SET available_stock = available_stock + ? WHERE id = ?");
                    $insert_log = $conn->prepare("INSERT INTO vaccine_logs
                        (vaccine_id, action_type, quantity, remarks, transaction_date, recorded_by)
                        VALUES (?, 'IN', ?, ?, ?, ?)");

                    if (!$insert_return || !$update_stock || !$insert_log) {
                        $return_error = $conn->error;
                        if ($insert_return) $insert_return->close();
                        if ($update_stock) $update_stock->close();
                        if ($insert_log) $insert_log->close();
                        $conn->rollback();
                        $message = "Error preparing returned vaccine record: " . $return_error;
                    } else {
                        $return_vaccine_id = (int) $return_batch['vaccine_id'];
                        $return_vaccine_name = (string) $return_batch['vaccine_name'];
                        $return_category = (string) $return_batch['category'];
                        $return_batch_number = (string) $return_batch['batch_number'];
                        $insert_return->bind_param(
                            "iisssisss",
                            $return_vaccine_id,
                            $batch_id,
                            $return_vaccine_name,
                            $return_category,
                            $return_batch_number,
                            $return_quantity,
                            $returned_by,
                            $return_reason,
                            $return_datetime
                        );
                        $return_ok = $insert_return->execute();
                        $return_error = $insert_return->error;

                        if ($return_ok) {
                            $update_stock->bind_param("ii", $return_quantity, $return_vaccine_id);
                            $return_ok = $update_stock->execute();
                            $return_error = $update_stock->error;
                        }

                        if ($return_ok) {
                            $return_remarks = "Returned to inventory; batch " . $return_batch_number
                                . ($return_reason !== '' ? " | " . $return_reason : '');
                            $insert_log->bind_param(
                                "iisss",
                                $return_vaccine_id,
                                $return_quantity,
                                $return_remarks,
                                $return_datetime,
                                $returned_by
                            );
                            $return_ok = $insert_log->execute();
                            $return_error = $insert_log->error;
                        }

                        $insert_return->close();
                        $update_stock->close();
                        $insert_log->close();

                        if ($return_ok && $conn->commit()) {
                            $message = "Returned vaccine quantity added back to available stock.";
                        } else {
                            $conn->rollback();
                            $message = "Error saving returned vaccine: " . ($return_error ?: $conn->error);
                        }
                    }
                }
            }
        }
    }
}

// ---------------------------------------------------------
// FETCH DATA FOR TABLES & CHARTS
// ---------------------------------------------------------

// Keep child and maternal inventory views separate.
$result_baby = $conn->query("SELECT * FROM vaccines WHERE category = 'Baby' ORDER BY created_at DESC");
$result_maternal = $conn->query("SELECT * FROM vaccines WHERE category = 'Maternal' ORDER BY created_at DESC");
$result_all_vaccines = $conn->query("SELECT category, vaccine_name, SUM(total_received) AS total_received,
        SUM(available_stock) AS available_stock
    FROM vaccines
    GROUP BY category, vaccine_name
    ORDER BY category ASC, vaccine_name ASC");
if (!$result_all_vaccines) {
    die("Could not load complete vaccine inventory: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$result_stock_batches = $conn->query("SELECT b.batch_number, b.quantity_received, b.manufactured_date, b.expiration_date,
        b.stock_in_date, b.received_by, b.provided_by, v.vaccine_name, v.category
    FROM vaccine_stock_batches b
    INNER JOIN vaccines v ON v.id = b.vaccine_id
    ORDER BY (b.expiration_date IS NULL) ASC, b.expiration_date ASC, b.stock_in_date DESC");
if (!$result_stock_batches) {
    die("Could not load vaccine expiration tracking: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$edit_batches_query = $conn->query("SELECT id, vaccine_id, batch_number, manufactured_date, expiration_date,
        stock_in_date, received_by, provided_by
    FROM vaccine_stock_batches
    ORDER BY stock_in_date DESC, id DESC");
if (!$edit_batches_query) {
    die("Could not load vaccine batches for editing: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$edit_batches = [];
while ($edit_batch = $edit_batches_query->fetch_assoc()) {
    $edit_batches[] = $edit_batch;
}
$expiry_summary_query = $conn->query("SELECT v.category, v.vaccine_name,
        MIN(CASE WHEN b.expiration_date >= CURDATE() AND b.quantity_received > 0 THEN b.expiration_date END) AS next_expiration,
        SUM(CASE WHEN b.expiration_date < CURDATE() AND b.quantity_received > 0 THEN 1 ELSE 0 END) AS expired_batches
    FROM vaccines v
    LEFT JOIN vaccine_stock_batches b ON b.vaccine_id = v.id
    GROUP BY v.category, v.vaccine_name");
if (!$expiry_summary_query) {
    die("Could not load vaccine expiration summary: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$vaccine_expiry_summary = [];
while ($expiry_row = $expiry_summary_query->fetch_assoc()) {
    $expiry_key = $expiry_row['category'] . '|' . $expiry_row['vaccine_name'];
    $vaccine_expiry_summary[$expiry_key] = [
        'next_expiration' => $expiry_row['next_expiration'],
        'expired_batches' => (int) $expiry_row['expired_batches']
    ];
}
$batch_summary_query = $conn->query("SELECT v.category, v.vaccine_name,
        GROUP_CONCAT(DISTINCT CASE
            WHEN b.batch_number REGEXP '^[0-9]+$' THEN b.batch_number
            ELSE NULL
        END ORDER BY CAST(b.batch_number AS UNSIGNED) ASC SEPARATOR ', ') AS batch_numbers
    FROM vaccines v
    LEFT JOIN vaccine_stock_batches b ON b.vaccine_id = v.id
    GROUP BY v.category, v.vaccine_name");
if (!$batch_summary_query) {
    die("Could not load vaccine batch number summary: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$vaccine_batch_summary = [];
while ($batch_summary_row = $batch_summary_query->fetch_assoc()) {
    $batch_summary_key = $batch_summary_row['category'] . '|' . $batch_summary_row['vaccine_name'];
    $vaccine_batch_summary[$batch_summary_key] = $batch_summary_row['batch_numbers'];
}
$expired_batch_count_query = $conn->query("SELECT COUNT(*) AS expired_batch_count
    FROM vaccine_stock_batches
    WHERE expiration_date < CURDATE() AND quantity_received > 0");
if (!$expired_batch_count_query) {
    die("Could not count expired vaccine batches: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$expired_batch_count = (int) ($expired_batch_count_query->fetch_assoc()['expired_batch_count'] ?? 0);
$expired_vaccine_batch_count = $expired_batch_count;
$result_expired_batches = $conn->query("SELECT b.batch_number, b.quantity_received, b.expiration_date,
        v.vaccine_name, v.category
    FROM vaccine_stock_batches b
    INNER JOIN vaccines v ON v.id = b.vaccine_id
    WHERE b.expiration_date < CURDATE() AND b.quantity_received > 0
    ORDER BY b.expiration_date ASC, v.vaccine_name ASC");
if (!$result_expired_batches) {
    die("Could not load expired vaccine batches: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}

$stock_lookup_query = $conn->query("SELECT vaccine_name, category, SUM(available_stock) AS current_stock FROM vaccines GROUP BY vaccine_name, category");
if (!$stock_lookup_query) {
    die("Could not load vaccine stock preview: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$vaccine_stock_lookup = [];
while ($stock_lookup_row = $stock_lookup_query->fetch_assoc()) {
    $stock_key = $stock_lookup_row['category'] . '|' . $stock_lookup_row['vaccine_name'];
    $vaccine_stock_lookup[$stock_key] = (int) $stock_lookup_row['current_stock'];
}
$batch_number_lookup_query = $conn->query("SELECT v.category, v.vaccine_name,
        MAX(CASE WHEN b.batch_number REGEXP '^[0-9]+$' THEN CAST(b.batch_number AS UNSIGNED) ELSE 0 END) AS latest_batch_number
    FROM vaccine_stock_batches b
    INNER JOIN vaccines v ON v.id = b.vaccine_id
    GROUP BY v.category, v.vaccine_name");
if (!$batch_number_lookup_query) {
    die("Could not load vaccine batch number preview: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$next_batch_number_lookup = [];
while ($batch_number_row = $batch_number_lookup_query->fetch_assoc()) {
    $batch_key = $batch_number_row['category'] . '|' . $batch_number_row['vaccine_name'];
    $next_batch_number_lookup[$batch_key] = (int) $batch_number_row['latest_batch_number'] + 1;
}

$sql_logs = "SELECT l.*, v.vaccine_name, v.category FROM vaccine_logs l JOIN vaccines v ON l.vaccine_id = v.id ORDER BY l.transaction_date DESC";
$result_logs = $conn->query($sql_logs);
$returnable_batches_query = $conn->query("SELECT b.id, b.batch_number, b.expiration_date, v.id AS vaccine_id, v.vaccine_name, v.category
    FROM vaccine_stock_batches b
    INNER JOIN vaccines v ON v.id = b.vaccine_id
    WHERE (b.expiration_date IS NULL OR b.expiration_date >= CURDATE())
        AND v.available_stock > 0
    ORDER BY v.category ASC, v.vaccine_name ASC, CAST(b.batch_number AS UNSIGNED) ASC, b.batch_number ASC");
if (!$returnable_batches_query) {
    die("Could not load eligible vaccine batches: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}
$returnable_batches = [];
$returnable_vaccines = [];
while ($returnable_batch = $returnable_batches_query->fetch_assoc()) {
    $returnable_batch['vaccine_id'] = (int) $returnable_batch['vaccine_id'];
    $returnable_batches[] = $returnable_batch;
    $returnable_vaccines[$returnable_batch['vaccine_id']] = [
        'name' => $returnable_batch['vaccine_name'],
        'category' => $returnable_batch['category']
    ];
}
$result_vaccine_returns = $conn->query("SELECT vaccine_name, category, batch_number, quantity, returned_by, reason, returned_at
    FROM vaccine_returns
    ORDER BY returned_at DESC, id DESC");
if (!$result_vaccine_returns) {
    die("Could not load returned vaccine records: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}

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
$monthly_out_query = $conn->query("SELECT SUM(quantity) as total_out FROM vaccine_logs WHERE action_type = 'OUT' AND (remarks IS NULL OR remarks <> 'Manual stock quantity edit') AND DATE_FORMAT(transaction_date, '%Y-%m') = '$current_month'");
$monthly_out_row = $monthly_out_query->fetch_assoc();
$total_out_month = $monthly_out_row['total_out'] ?? 0;

$low_stock_query = $conn->query("SELECT vaccine_name, category, available_stock FROM vaccines WHERE available_stock <= 10 ORDER BY available_stock ASC, vaccine_name ASC, category ASC");
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
            width: 100%;
            max-width: none;
            margin: 0;
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
        .action-btns { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 9px; }
        .btn-report {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 38px;
            padding: 9px 13px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--card-bg);
            color: #43533a;
            font-family: inherit;
            font-size: .8rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: background .2s ease, border-color .2s ease;
        }
        .btn-report:hover { background: #f0f5ec; border-color: var(--sage-green); }

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
        button.card-button {
            width: 100%;
            color: inherit;
            font: inherit;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        button.card-button:hover,
        button.card-button:focus-visible {
            border-color: var(--sage-green);
            box-shadow: 0 4px 14px rgba(34, 48, 28, .12);
        }
        button.card-button:focus-visible {
            outline: 2px solid var(--sage-green);
            outline-offset: 2px;
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
        button.expired-vaccine-card {
            text-align: left;
            border-color: <?= $expired_batch_count > 0 ? 'var(--danger-red)' : 'var(--border-color)' ?>;
        }
        button.expired-vaccine-card .value { color: var(--danger-red); }
        button.expired-vaccine-card.is-empty .value { color: var(--sage-green); }

        .low-stock-heading {
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .low-stock-heading i { color: var(--danger-red); }

        .low-stock-list {
            margin: 10px 0 0;
            padding: 0;
            list-style: none;
            text-align: left;
            font-size: 0.78rem;
            max-height: 190px;
            overflow-y: auto;
        }

        .low-stock-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 0;
            border-top: 1px solid var(--border-color);
        }

        .low-stock-name {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
            color: #414a3c;
            font-weight: 650;
            overflow-wrap: anywhere;
        }
        .low-stock-category {
            color: #6b7564;
            font-size: .67rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .low-stock-quantity {
            flex: 0 0 auto;
            padding: 4px 7px;
            border-radius: 999px;
            background: #fff3d6;
            color: #805411;
            font-size: .67rem;
            font-weight: 750;
            white-space: nowrap;
        }
        .low-stock-quantity.is-out {
            background: #fee7e5;
            color: #a52a26;
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
        .return-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px; }
        .return-form-grid .form-group { margin: 0; }
        .return-form-grid input, .return-form-grid select, .return-form-grid textarea { width: 100%; min-height: 40px; padding: 8px 11px; border: 1px solid #d9dfd4; border-radius: 8px; background: #fff; color: #293126; font: inherit; font-size: .8rem; box-sizing: border-box; }
        .return-form-grid textarea { min-height: 40px; resize: vertical; }
        .return-form-submit { margin: 0 0 18px; padding: 9px 14px; border: 0; border-radius: 8px; background: var(--sage-green); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .vaccine-search-toolbar { margin-bottom: 12px; }
        .vaccine-search-toolbar input {
            width: 100%;
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid #d9dfd4;
            border-radius: 8px;
            background: #fff;
            color: #293126;
            font: inherit;
            font-size: .84rem;
            box-sizing: border-box;
        }
        .vaccine-search-toolbar input:focus { outline: 2px solid rgba(113, 131, 85, .25); border-color: var(--sage-green); }
        .vaccine-search-count { display: none; }
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
            width: min(450px, calc(100% - 40px));
            box-sizing: border-box;
        }
        .stock-breakdown-modal-content {
            width: min(760px, calc(100% - 32px));
        }
        .stock-breakdown-table-wrap {
            max-height: min(60vh, 520px);
            overflow: auto;
            border: 1px solid #e7ebe3;
            border-radius: 8px;
        }
        .stock-breakdown-table { min-width: 700px; }
        .stock-breakdown-table th,
        .stock-breakdown-table td { padding: 10px 12px; }
        .stock-breakdown-empty { padding: 22px 14px; color: #71806b; text-align: center; }

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
            display: flex;
            align-items: center;
            gap: 12px;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1200;
            width: min(420px, calc(100vw - 40px));
            box-sizing: border-box;
            color: #205b35;
            background: #fff;
            padding: 15px 16px;
            border: 1px solid #d5e8d9;
            border-left: 4px solid #31834b;
            border-radius: 12px;
            box-shadow: 0 12px 34px rgba(28, 42, 24, .16);
            transition: opacity .25s ease, transform .25s ease;
        }
        .error-msg {
            display: flex;
            align-items: center;
            gap: 12px;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1200;
            width: min(420px, calc(100vw - 40px));
            box-sizing: border-box;
            color: #8d2925;
            background: #fff;
            padding: 15px 16px;
            border: 1px solid #f1d5d3;
            border-left: 4px solid var(--danger-red);
            border-radius: 12px;
            box-shadow: 0 12px 34px rgba(42, 25, 24, .16);
            transition: opacity .25s ease, transform .25s ease;
        }
        .alert-message.is-dismissing { opacity: 0; transform: translateY(-8px); }
        .alert-icon { flex: 0 0 auto; font-size: 1.05rem; }
        .alert-text { flex: 1 1 auto; min-width: 0; font-size: .88rem; line-height: 1.45; }
        .alert-dismiss {
            flex: 0 0 auto;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            font-size: 1.1rem;
            line-height: 1;
            padding: 2px 5px;
            cursor: pointer;
            opacity: .75;
        }
        .alert-dismiss:hover { opacity: 1; }
        .confirm-delete-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1300;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(18, 27, 16, .48);
            backdrop-filter: blur(3px);
        }
        .confirm-delete-modal.is-open { display: flex; }
        .confirm-delete-content {
            width: min(420px, 100%);
            padding: 24px;
            border: 1px solid #e8ebe4;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(20, 30, 18, .24);
            animation: confirmModalIn .18s ease-out;
        }
        @keyframes confirmModalIn {
            from { opacity: 0; transform: translateY(8px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .confirm-delete-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            margin-bottom: 15px;
            border-radius: 13px;
            color: #a52a26;
            background: #fff0ef;
            font-size: 1.05rem;
        }
        .confirm-delete-content h2 { margin: 0 0 7px; color: #273124; font-size: 1.1rem; }
        .confirm-delete-content p { margin: 0; color: #687365; font-size: .87rem; line-height: 1.5; }
        .confirm-delete-actions { display: flex; justify-content: flex-end; gap: 9px; margin-top: 22px; }
        .confirm-delete-actions button {
            min-height: 39px;
            padding: 8px 14px;
            border: 1px solid #dce2d8;
            border-radius: 8px;
            background: #fff;
            color: #475142;
            font: inherit;
            font-size: .84rem;
            font-weight: 650;
            cursor: pointer;
        }
        .confirm-delete-actions button:hover { background: #f6f8f4; }
        .confirm-delete-actions .confirm-delete-button {
            border-color: #b52e28;
            background: #b52e28;
            color: #fff;
        }
        .confirm-delete-actions .confirm-delete-button:hover { background: #98251f; }
        @media (max-width: 520px) {
            .success-msg, .error-msg { top: 12px; right: 12px; width: calc(100vw - 24px); }
            .confirm-delete-content { padding: 20px; }
        }

        .action-links a { font-weight: bold; text-decoration: none; margin-right: 10px; }
        .action-edit { color: var(--sage-green); }
        .action-delete { color: var(--danger-red); }



























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
            .action-btns { display: grid; grid-template-columns: 1fr; }
            .action-btns .btn-report { width: 100%; box-sizing: border-box; }
            .summary-cards { grid-template-columns: minmax(0, 1fr); }
            .card { padding: 14px 16px; }
            .card .value { font-size: 1.55rem; }
            .chart-container { min-height: 240px; padding: 14px; }
            .tabs { overflow-x: auto; }
            .tab { flex: 0 0 auto; padding: 10px 13px; font-size: .77rem; }
            .tab-content { padding: 12px; }
            .history-toolbar { grid-template-columns: minmax(0, 1fr); }
            .return-form-grid { grid-template-columns: minmax(0, 1fr); }
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
                <a class="btn-report" href="?print_inventory=1" target="_blank" rel="noopener">
                    <i class="fa-solid fa-print" aria-hidden="true"></i> Print Inventory
                </a>
                <a class="btn-report" href="?export_inventory=word">
                    <i class="fa-solid fa-file-word" aria-hidden="true"></i> Export to Word
                </a>
                <button class="btn-add" type="button" onclick="document.getElementById('addModal').style.display='block'"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add / Receive Stock</button>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <?php $isErrorMessage = strpos($message, 'Error') === 0; ?>
            <div class="alert-message <?= $isErrorMessage ? 'error-msg' : 'success-msg' ?>"
                 role="<?= $isErrorMessage ? 'alert' : 'status' ?>"
                 aria-live="<?= $isErrorMessage ? 'assertive' : 'polite' ?>"
                 <?= $isErrorMessage ? '' : 'data-autodismiss="5000"' ?>>
                <i class="alert-icon fa-solid <?= $isErrorMessage ? 'fa-circle-exclamation' : 'fa-circle-check' ?>" aria-hidden="true"></i>
                <span class="alert-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
                <button class="alert-dismiss" type="button" aria-label="Isara ang mensahe">&times;</button>
            </div>
        <?php endif; ?>

        <!-- DASHBOARD SUMMARY & CHART -->
        <div class="dashboard-top">
            <div class="summary-cards">
                <button class="card card-button" type="button" onclick="openStockBreakdown()" aria-haspopup="dialog" aria-controls="stockBreakdownModal">
                    <h3>Total Available Stock</h3>
                    <p class="value"><?= $total_available ?></p>
                </button>
                <div class="card">
                    <h3>Used This Month</h3>
                    <p class="value" style="color: #666;"><?= $total_out_month ?></p>
                </div>
                <div class="card alert-card<?= $vaccine_item_count === 0 ? ' is-empty' : '' ?>">
                    <h3 class="low-stock-heading">
                        <?php if ($vaccine_item_count > 0): ?><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><?php endif; ?>
                        <?= $vaccine_item_count === 0 ? 'Inventory Status' : 'Low Stock Alert' ?>
                    </h3>
                    <p class="value"><?= $low_stock_count ?></p>
                    <?php if (!$low_stock_query): ?>
                        <p class="inventory-state is-error">Could not load low-stock vaccines: <?= htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php elseif ($vaccine_item_count === 0): ?>
                        <p class="inventory-state is-empty">No vaccines are registered in inventory yet. Add a vaccine to start tracking stock.</p>
                    <?php elseif ($low_stock_count > 0): ?>
                        <p class="inventory-state">10 doses or fewer — check these vaccines:</p>
                        <ul class="low-stock-list" aria-label="Vaccines with 10 or fewer doses remaining">
                            <?php foreach ($low_stock_items as $low_stock): ?>
                                <li>
                                    <span class="low-stock-name">
                                        <?= htmlspecialchars($low_stock['vaccine_name'], ENT_QUOTES, 'UTF-8') ?>
                                        <small class="low-stock-category"><?= htmlspecialchars($low_stock['category'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </span>
                                    <span class="low-stock-quantity<?= (int) $low_stock['available_stock'] <= 0 ? ' is-out' : '' ?>">
                                        <?= (int) $low_stock['available_stock'] <= 0 ? 'Out of stock' : (int) $low_stock['available_stock'] . ' left' ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="inventory-state">All <?= $vaccine_item_count ?> vaccine types are above the low-stock threshold (10 doses).</p>
                    <?php endif; ?>
                </div>
                <button class="card card-button alert-card expired-vaccine-card<?= $expired_batch_count === 0 ? ' is-empty' : '' ?>"
                        type="button" onclick="openExpiredVaccineModal()" aria-haspopup="dialog" aria-controls="expiredVaccineModal">
                    <h3 class="low-stock-heading">Expired Vaccine Batches</h3>
                    <p class="value"><?= $expired_batch_count ?></p>
                    <p class="inventory-state"><?= $expired_batch_count > 0 ? 'Click to review expired batches.' : 'No expired vaccine batches.' ?></p>
                </button>
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
                            <button class="btn-add" type="button" onclick="document.getElementById('addModal').style.display='block'"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add / Receive Stock</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="stockBreakdownModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="stockBreakdownTitle">
            <div class="modal-content stock-breakdown-modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px;">
                    <div>
                        <h3 id="stockBreakdownTitle" style="margin:0; color:#394532;">Total Available Stock</h3>
                        <p style="margin:5px 0 0; color:#71806b; font-size:.82rem;">Quantity breakdown for infant and maternal vaccines.</p>
                    </div>
                    <button type="button" class="alert-dismiss" aria-label="Close stock breakdown" onclick="closeStockBreakdown()">&times;</button>
                </div>
                <div class="stock-breakdown-table-wrap">
                    <table class="stock-breakdown-table">
                        <thead>
                            <tr>
                                <th>Vaccine</th>
                                <th>Batch Number</th>
                                <th>Category</th>
                                <th>Total Received</th>
                                <th>Available Quantity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_all_vaccines->num_rows > 0): ?>
                                <?php while ($stock_vaccine = $result_all_vaccines->fetch_assoc()):
                                    $stock_quantity = (int) ($stock_vaccine['available_stock'] ?? 0);
                                    $stock_status = $stock_quantity <= 0 ? 'Out of stock' : ($stock_quantity <= 10 ? 'Low stock' : 'In stock');
                                    $stock_status_class = $stock_quantity <= 0 ? 'stock-badge-out' : ($stock_quantity <= 10 ? 'stock-badge-low' : 'stock-badge-in');
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($stock_vaccine['vaccine_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td><?= ($stock_vaccine['category'] ?? '') === 'Baby' ? 'Infant' : htmlspecialchars($stock_vaccine['category'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int) ($stock_vaccine['total_received'] ?? 0) ?></td>
                                        <td><strong><?= $stock_quantity ?></strong></td>
                                        <td><span class="stock-badge <?= $stock_status_class ?>"><?= $stock_status ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td class="stock-breakdown-empty" colspan="5">No vaccines are registered in the inventory yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p style="margin:12px 0 0; text-align:right; color:#43533a; font-size:.84rem; font-weight:700;">
                    Total available doses: <?= (int) $total_available ?>
                </p>
                <h4 style="margin:22px 0 9px; color:#394532;">Received Batches &amp; Expiration Tracking</h4>
                <p style="margin:0 0 10px; color:#71806b; font-size:.78rem;">Each row records the quantity received for a delivery; total available stock is shown above. Previously recorded stock has no manufacture or expiry date available.</p>
                <div class="stock-breakdown-table-wrap">
                    <table class="stock-breakdown-table">
                        <thead>
                            <tr>
                                <th>Vaccine</th>
                                <th>Category</th>
                                <th>Quantity Received</th>
                                <th>Manufactured</th>
                                <th>Expires</th>
                                <th>Supplier / Received By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_stock_batches->num_rows > 0): ?>
                                <?php while ($stock_batch = $result_stock_batches->fetch_assoc()):
                                    $batch_expiration = $stock_batch['expiration_date'];
                                    $batch_expiration_status = 'Dates not recorded';
                                    $batch_expiration_class = 'stock-badge-low';
                                    if ($batch_expiration) {
                                        $days_until_expiration = (int) floor((strtotime($batch_expiration) - strtotime(date('Y-m-d'))) / 86400);
                                        if ($days_until_expiration < 0) {
                                            $batch_expiration_status = 'Expired';
                                            $batch_expiration_class = 'stock-badge-out';
                                        } elseif ($days_until_expiration <= 90) {
                                            $batch_expiration_status = $days_until_expiration === 0 ? 'Expires today' : 'Expires in ' . $days_until_expiration . ' days';
                                            $batch_expiration_class = 'stock-badge-low';
                                        } else {
                                            $batch_expiration_status = 'Not expired';
                                            $batch_expiration_class = 'stock-badge-in';
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($stock_batch['vaccine_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td><?= htmlspecialchars($stock_batch['batch_number'] ?? 'Legacy batch', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= ($stock_batch['category'] ?? '') === 'Baby' ? 'Infant' : htmlspecialchars($stock_batch['category'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int) $stock_batch['quantity_received'] ?></td>
                                        <td><?= $stock_batch['manufactured_date'] ? date('M d, Y', strtotime($stock_batch['manufactured_date'])) : 'Not recorded' ?></td>
                                        <td>
                                            <?= $batch_expiration ? date('M d, Y', strtotime($batch_expiration)) : 'Not recorded' ?><br>
                                            <span class="stock-badge <?= $batch_expiration_class ?>"><?= htmlspecialchars($batch_expiration_status, ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td>
                                            <small><strong>From:</strong> <?= htmlspecialchars($stock_batch['provided_by'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></small><br>
                                            <small><strong>By:</strong> <?= htmlspecialchars($stock_batch['received_by'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td class="stock-breakdown-empty" colspan="7">No stock batches recorded.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="expiredVaccineModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="expiredVaccineTitle">
            <div class="modal-content stock-breakdown-modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px;">
                    <div>
                        <h3 id="expiredVaccineTitle" style="margin:0; color:#a52a26;">Expired Vaccine Batches</h3>
                        <p style="margin:5px 0 0; color:#71806b; font-size:.82rem;"><?= $expired_batch_count ?> batch(es) with expiration dates before today.</p>
                    </div>
                    <button type="button" class="alert-dismiss" aria-label="Close expired vaccine batches" onclick="closeExpiredVaccineModal()">&times;</button>
                </div>
                <div class="stock-breakdown-table-wrap">
                    <table class="stock-breakdown-table">
                        <thead>
                            <tr>
                                <th>Vaccine</th>
                                <th>Batch Number</th>
                                <th>Category</th>
                                <th>Quantity Received</th>
                                <th>Expiration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_expired_batches->num_rows > 0): ?>
                                <?php while ($expired_batch = $result_expired_batches->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($expired_batch['vaccine_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td><?= htmlspecialchars($expired_batch['batch_number'] ?? 'Legacy batch', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= ($expired_batch['category'] ?? '') === 'Baby' ? 'Infant' : htmlspecialchars($expired_batch['category'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int) $expired_batch['quantity_received'] ?></td>
                                        <td><span class="stock-badge stock-badge-out"><?= date('M d, Y', strtotime($expired_batch['expiration_date'])) ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td class="stock-breakdown-empty" colspan="5">No expired vaccine batches.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABS NAVIGATION -->
        <div class="tabs">
            <button class="tab active" type="button" onclick="openTab('babyTab', this)">Baby Vaccines</button>
            <button class="tab" type="button" onclick="openTab('maternalTab', this)">Maternal Vaccines</button>
            <button class="tab" type="button" onclick="openTab('historyTab', this)">Inventory History</button>
            <button class="tab" type="button" onclick="openTab('returnedTab', this)">Returned Vaccines</button>
        </div>

        <!-- BABY VACCINES TAB -->
        <div id="babyTab" class="tab-content active">
            <div class="vaccine-search-toolbar">
                <input type="search" class="vaccine-search" data-table="babyVaccineTable" data-count="babyVaccineCount"
                       placeholder="Hanapin ang baby vaccine..." aria-label="Hanapin ang baby vaccine">
            </div>
            <p class="vaccine-search-count" id="babyVaccineCount" aria-live="polite"></p>
            <div class="table-scroll">
            <table class="inventory-table" id="babyVaccineTable">
                <thead>
                    <tr>
                        <th>Batch #</th>
                        <th>Vaccine Name</th>
                        <th>Received / Available</th>
                        <th>Received From / By</th>
                        <th>Expiration Date</th>
                        <th>Last Stock Entry</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_baby && $result_baby->num_rows > 0): ?>
                        <?php while($row = $result_baby->fetch_assoc()):
                            $datetime_val = !empty($row['stock_in_date']) ? $row['stock_in_date'] : $row['created_at'];
                            $datetime_timestamp = strtotime($datetime_val);
                            $availableStock = (int) ($row['available_stock'] ?? 0);
                            $expiryKey = ($row['category'] ?? 'Baby') . '|' . ($row['vaccine_name'] ?? '');
                            $expirySummary = $vaccine_expiry_summary[$expiryKey] ?? ['next_expiration' => null, 'expired_batches' => 0];
                            $batchNumbers = $vaccine_batch_summary[$expiryKey] ?? 'Not recorded';
                            $vaccineSearchText = strtolower(implode(' ', [
                                $row['vaccine_name'] ?? '',
                                $row['description'] ?? '',
                                $row['provided_by'] ?? '',
                                $row['received_by'] ?? '',
                                $batchNumbers,
                                $expirySummary['next_expiration'] ?? '',
                                $expirySummary['expired_batches'] > 0 ? 'expired' : '',
                                $availableStock <= 0 ? 'out of stock' : ($availableStock <= 10 ? 'low stock' : 'in stock'),
                                (string) $availableStock
                            ]));
                        ?>
                            <tr data-vaccine-search="<?= htmlspecialchars($vaccineSearchText, ENT_QUOTES, 'UTF-8') ?>">
                                <td><?= htmlspecialchars($batchNumbers, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                    <small class="vaccine-description"><?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    Received to date: <?= (int) ($row['total_received'] ?? 0) ?><br>
                                    <span class="stock-badge <?= $availableStock <= 0 ? 'stock-badge-out' : ($availableStock <= 10 ? 'stock-badge-low' : 'stock-badge-in') ?>">
                                        <?= $availableStock <= 0 ? 'Out of stock' : ($availableStock <= 10 ? 'Low stock' : 'In stock') ?>
                                    </span>
                                    <span class="stock-count"><?= $availableStock ?> available</span>
                                </td>
                                <td>
                                    <small><strong>From:</strong> <?= htmlspecialchars($row['provided_by'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></small><br>
                                    <small><strong>By:</strong> <?= htmlspecialchars($row['received_by'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <?php if ($expirySummary['next_expiration']): ?>
                                        <?php
                                            $daysUntilExpiry = (int) floor((strtotime($expirySummary['next_expiration']) - strtotime(date('Y-m-d'))) / 86400);
                                            $expiryBadgeClass = $daysUntilExpiry <= 90 ? 'stock-badge-low' : 'stock-badge-in';
                                            $expiryLabel = $daysUntilExpiry === 0 ? 'Expires today' : ($daysUntilExpiry <= 90 ? 'In ' . $daysUntilExpiry . ' days' : 'Upcoming');
                                        ?>
                                        <?= date('M d, Y', strtotime($expirySummary['next_expiration'])) ?><br>
                                        <span class="stock-badge <?= $expiryBadgeClass ?>"><?= $expiryLabel ?></span>
                                    <?php elseif ($expirySummary['expired_batches'] > 0): ?>
                                        <span class="stock-badge stock-badge-out">Expired</span>
                                    <?php else: ?>
                                        <span class="table-muted">Not recorded</span>
                                    <?php endif; ?>
                                    <?php if ($expirySummary['expired_batches'] > 0): ?>
                                        <br><span class="stock-badge stock-badge-out"><?= $expirySummary['expired_batches'] ?> expired</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $datetime_timestamp && date('Y', $datetime_timestamp) > 1 ? date('M d, Y h:i A', $datetime_timestamp) : 'N/A' ?></td>
                                <td class="action-links">
                                    <a href="#" class="action-edit" 
                                       onclick="openEditModal(
                                           '<?= $row['id'] ?>',
                                           '<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES, 'UTF-8') ?>',
                                           '<?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
                                           '<?= $row['category'] ?>',
                                           '<?= $availableStock ?>'
                                       )">Edit details</a>
                                    <a href="?delete_id=<?= (int) $row['id'] ?>" class="action-delete" data-confirm-delete>Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="vaccine-inventory-empty"><td colspan="7" class="table-empty">No baby vaccines in inventory yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
            <div class="history-empty vaccine-filter-empty">Walang baby vaccine na tumugma sa search.</div>
        </div>

        <!-- MATERNAL VACCINES TAB -->
        <div id="maternalTab" class="tab-content">
            <div class="vaccine-search-toolbar">
                <input type="search" class="vaccine-search" data-table="maternalVaccineTable" data-count="maternalVaccineCount"
                       placeholder="Hanapin ang maternal vaccine..." aria-label="Hanapin ang maternal vaccine">
            </div>
            <p class="vaccine-search-count" id="maternalVaccineCount" aria-live="polite"></p>
            <div class="table-scroll">
            <table class="inventory-table" id="maternalVaccineTable">
                <thead>
                    <tr>
                        <th>Batch #</th>
                        <th>Vaccine Name</th>
                        <th>Received / Available</th>
                        <th>Received From / By</th>
                        <th>Expiration Date</th>
                        <th>Last Stock Entry</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_maternal && $result_maternal->num_rows > 0): ?>
                        <?php while($row = $result_maternal->fetch_assoc()):
                            $datetime_val = !empty($row['stock_in_date']) ? $row['stock_in_date'] : $row['created_at'];
                            $datetime_timestamp = strtotime($datetime_val);
                            $availableStock = (int) ($row['available_stock'] ?? 0);
                            $expiryKey = ($row['category'] ?? 'Maternal') . '|' . ($row['vaccine_name'] ?? '');
                            $expirySummary = $vaccine_expiry_summary[$expiryKey] ?? ['next_expiration' => null, 'expired_batches' => 0];
                            $batchNumbers = $vaccine_batch_summary[$expiryKey] ?? 'Not recorded';
                            $vaccineSearchText = strtolower(implode(' ', [
                                $row['vaccine_name'] ?? '',
                                $row['description'] ?? '',
                                $row['provided_by'] ?? '',
                                $row['received_by'] ?? '',
                                $batchNumbers,
                                $expirySummary['next_expiration'] ?? '',
                                $expirySummary['expired_batches'] > 0 ? 'expired' : '',
                                $availableStock <= 0 ? 'out of stock' : ($availableStock <= 10 ? 'low stock' : 'in stock'),
                                (string) $availableStock
                            ]));
                        ?>
                            <tr data-vaccine-search="<?= htmlspecialchars($vaccineSearchText, ENT_QUOTES, 'UTF-8') ?>">
                                <td><?= htmlspecialchars($batchNumbers, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                    <small class="vaccine-description"><?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    Received to date: <?= (int) ($row['total_received'] ?? 0) ?><br>
                                    <span class="stock-badge <?= $availableStock <= 0 ? 'stock-badge-out' : ($availableStock <= 10 ? 'stock-badge-low' : 'stock-badge-in') ?>">
                                        <?= $availableStock <= 0 ? 'Out of stock' : ($availableStock <= 10 ? 'Low stock' : 'In stock') ?>
                                    </span>
                                    <span class="stock-count"><?= $availableStock ?> available</span>
                                </td>
                                <td>
                                    <small><strong>From:</strong> <?= htmlspecialchars($row['provided_by'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></small><br>
                                    <small><strong>By:</strong> <?= htmlspecialchars($row['received_by'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <?php if ($expirySummary['next_expiration']): ?>
                                        <?php
                                            $daysUntilExpiry = (int) floor((strtotime($expirySummary['next_expiration']) - strtotime(date('Y-m-d'))) / 86400);
                                            $expiryBadgeClass = $daysUntilExpiry <= 90 ? 'stock-badge-low' : 'stock-badge-in';
                                            $expiryLabel = $daysUntilExpiry === 0 ? 'Expires today' : ($daysUntilExpiry <= 90 ? 'In ' . $daysUntilExpiry . ' days' : 'Upcoming');
                                        ?>
                                        <?= date('M d, Y', strtotime($expirySummary['next_expiration'])) ?><br>
                                        <span class="stock-badge <?= $expiryBadgeClass ?>"><?= $expiryLabel ?></span>
                                    <?php elseif ($expirySummary['expired_batches'] > 0): ?>
                                        <span class="stock-badge stock-badge-out">Expired</span>
                                    <?php else: ?>
                                        <span class="table-muted">Not recorded</span>
                                    <?php endif; ?>
                                    <?php if ($expirySummary['expired_batches'] > 0): ?>
                                        <br><span class="stock-badge stock-badge-out"><?= $expirySummary['expired_batches'] ?> expired</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $datetime_timestamp && date('Y', $datetime_timestamp) > 1 ? date('M d, Y h:i A', $datetime_timestamp) : 'N/A' ?></td>
                                <td class="action-links">
                                    <a href="#" class="action-edit"
                                       onclick="openEditModal(
                                           '<?= $row['id'] ?>',
                                           '<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES, 'UTF-8') ?>',
                                           '<?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
                                           '<?= $row['category'] ?>',
                                           '<?= $availableStock ?>'
                                       )">Edit details</a>
                                    <a href="?delete_id=<?= (int) $row['id'] ?>" class="action-delete" data-confirm-delete>Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="vaccine-inventory-empty"><td colspan="7" class="table-empty">No maternal vaccines in inventory yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
            <div class="history-empty vaccine-filter-empty">Walang maternal vaccine na tumugma sa search.</div>
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
                        <th>Category</th>
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
                                $log['category'] ?? '',
                                $log['remarks'] ?? '',
                                $log['recorded_by'] ?? '',
                                $log['action_type'] ?? '',
                                $log['transaction_date'] ?? ''
                            ]));
                            $historyAction = strtolower($log['action_type'] ?? '');
                        ?>
                            <tr data-history-search="<?= htmlspecialchars($historySearchText, ENT_QUOTES, 'UTF-8') ?>" data-history-action="<?= htmlspecialchars($historyAction, ENT_QUOTES, 'UTF-8') ?>">
                                <?php $logTimestamp = strtotime($log['transaction_date']); ?>
                                <td><?= $logTimestamp && date('Y', $logTimestamp) > 1 ? date('M d, Y h:i A', $logTimestamp) : 'N/A' ?></td>
                                <td><strong><?= htmlspecialchars($log['vaccine_name']) ?></strong></td>
                                <td><?= htmlspecialchars($log['category'], ENT_QUOTES, 'UTF-8') ?></td>
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
                        <tr><td colspan="7" class="table-empty">No inventory history recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
            <div class="history-empty" id="historyFilterEmpty">No history entries match your search and filter.</div>
        </div>

        <div id="returnedTab" class="tab-content">
            <h3 style="margin:0 0 14px; color:#43533a;">Record Returned Vaccines</h3>
            <form method="POST">
                <div class="return-form-grid">
                    <div class="form-group">
                        <label for="return_category">Category *</label>
                        <select id="return_category" required>
                            <option value="">-- Select category --</option>
                            <option value="Baby">Baby Vaccine</option>
                            <option value="Maternal">Maternal Vaccine (Pregnant)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="return_vaccine_id">Vaccine Name *</label>
                        <select id="return_vaccine_id" required disabled>
                            <option value="">-- Select category first --</option>
                            <?php foreach ($returnable_vaccines as $vaccine_id => $returnable_vaccine): ?>
                                <option value="<?= (int) $vaccine_id ?>" data-category="<?= htmlspecialchars($returnable_vaccine['category'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($returnable_vaccine['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="return_batch_id">Batch Number *</label>
                        <small id="return_batch_count" aria-live="polite">Choose a vaccine name to view recorded batches.</small>
                        <select name="return_batch_id" id="return_batch_id" required disabled>
                            <option value="">-- Select category first --</option>
                            <?php foreach ($returnable_batches as $returnable_batch): ?>
                                <option value="<?= (int) $returnable_batch['id'] ?>"
                                        data-vaccine-id="<?= (int) $returnable_batch['vaccine_id'] ?>"
                                        data-category="<?= htmlspecialchars($returnable_batch['category'], ENT_QUOTES, 'UTF-8') ?>">
                                    Batch <?= htmlspecialchars($returnable_batch['batch_number'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="return_quantity">Quantity Returned *</label>
                        <input type="number" name="return_quantity" id="return_quantity" min="1" step="1" required>
                    </div>
                    <div class="form-group">
                        <label for="returned_by">Returned By *</label>
                        <input type="text" name="returned_by" id="returned_by" maxlength="255" required>
                    </div>
                    <div class="form-group">
                        <label for="return_reason">Reason / Notes</label>
                        <textarea name="return_reason" id="return_reason" rows="1" maxlength="1000"></textarea>
                    </div>
                </div>
                <button class="return-form-submit" type="submit" name="record_vaccine_return" <?= $returnable_batches_query->num_rows === 0 ? 'disabled' : '' ?>>
                    Record Return &amp; Add to Stock
                </button>
                <?php if ($returnable_batches_query->num_rows === 0): ?>
                    <p class="inventory-state">No in-stock vaccine batches without a past expiration date are eligible for return.</p>
                <?php endif; ?>
            </form>

            <h3 style="margin:18px 0 10px; color:#43533a;">Return History</h3>
            <div class="table-scroll">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Vaccine</th>
                            <th>Category</th>
                            <th>Batch #</th>
                            <th>Quantity Returned</th>
                            <th>Returned By</th>
                            <th>Reason / Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_vaccine_returns->num_rows > 0): ?>
                            <?php while ($returned_vaccine = $result_vaccine_returns->fetch_assoc()):
                                $return_timestamp = strtotime($returned_vaccine['returned_at']);
                            ?>
                                <tr>
                                    <td><?= $return_timestamp ? date('M d, Y h:i A', $return_timestamp) : 'N/A' ?></td>
                                    <td><strong><?= htmlspecialchars($returned_vaccine['vaccine_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><?= ($returned_vaccine['category'] ?? '') === 'Baby' ? 'Infant' : htmlspecialchars($returned_vaccine['category'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($returned_vaccine['batch_number'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><strong><?= (int) $returned_vaccine['quantity'] ?></strong></td>
                                    <td><?= htmlspecialchars($returned_vaccine['returned_by'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($returned_vaccine['reason'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="table-empty">No returned vaccines recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ADD MODAL -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; text-transform: uppercase; font-size: 1rem;">Add Vaccine / Receive Stock</h3>
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
                <select name="vaccine_name" id="add_vaccine_name" required onchange="updateStockPreview(); updateNextBatchNumber()">
                    <option value="">-- Select Vaccine Name --</option>
                    <?php foreach ([
                        'Baby' => [
                            'BCG',
                            'Hepatitis B',
                            'Pentavalent',
                            'Oral Polio Vaccine (OPV)',
                            'Inactivated Polio Vaccine (IPV)',
                            'Pneumococcal Conjugate Vaccine (PCV)',
                            'Measles, Mumps, Rubella (MMR)'
                        ],
                        'Maternal' => ['Tetanus Toxoid']
                    ] as $stockCategory => $stockVaccineNames): ?>
                        <?php foreach ($stockVaccineNames as $stockVaccineName):
                            $stockKey = $stockCategory . '|' . $stockVaccineName;
                            $currentVaccineStock = $vaccine_stock_lookup[$stockKey] ?? 0;
                            $nextBatchNumber = $next_batch_number_lookup[$stockKey] ?? 1;
                        ?>
                            <option
                                value="<?= htmlspecialchars($stockVaccineName, ENT_QUOTES, 'UTF-8') ?>"
                                data-category="<?= htmlspecialchars($stockCategory, ENT_QUOTES, 'UTF-8') ?>"
                                data-current-stock="<?= $currentVaccineStock ?>"
                                data-next-batch="<?= $nextBatchNumber ?>"
                            ><?= htmlspecialchars($stockVaccineName, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Batch Number *</label>
                <input type="text" name="batch_number" id="batch_number" maxlength="100" required placeholder="Auto-filled for selected vaccine; editable">
            </div>
            <div class="form-group">
                <label>Description / Notes</label>
                <textarea name="description" rows="2" placeholder="Target disease or notes..."></textarea>
            </div>
            <div class="form-group">
                <label>Quantity Received</label>
                <input type="number" name="quantity_received" id="quantity_received" value="0" min="1" required oninput="updateStockPreview()">
            </div>
            <div class="form-group">
                <label>Current Stock</label>
                <input type="number" id="current_stock" value="0" readonly>
            </div>
            <div class="form-group">
                <label>New Total Stock</label>
                <input type="number" id="stock_after_receipt" value="0" readonly>
            </div>
            <div class="form-group">
                <label>Provided By *</label>
                <input type="text" name="provided_by" placeholder="e.g. Provincial Health Office" required>
            </div>
            <div class="form-group">
                <label>Received By *</label>
                <input type="text" name="received_by" placeholder="e.g. Juan Dela Cruz" required>
            </div>
            <div class="form-group">
                <label>Petsa ng stock entry *</label>
                <input type="date" name="stock_date" id="stock_date" value="<?= date('Y-m-d') ?>" required onchange="updateVaccineDateLimits()">
            </div>
            <div class="form-group">
                <label>Manufactured Date *</label>
                <input type="date" name="manufactured_date" id="manufactured_date" max="<?= date('Y-m-d') ?>" required onchange="updateVaccineDateLimits()">
            </div>
            <div class="form-group">
                <label>Expiration Date *</label>
                <input type="date" name="expiration_date" id="expiration_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required onchange="updateVaccineDateLimits()">
            </div>
            <div class="form-group">
                <label>Oras ng stock entry *</label>
                <input type="time" name="stock_time" value="<?= date('H:i') ?>" required>
            </div>
            <button type="submit" name="add_vaccine" class="btn-submit">I-save ang Stock</button>
        </form>
    </div>
</div>

<div id="confirmDeleteModal" class="confirm-delete-modal" role="alertdialog" aria-modal="true" aria-labelledby="confirmDeleteTitle" aria-describedby="confirmDeleteDescription">
    <div class="confirm-delete-content">
        <div class="confirm-delete-icon" aria-hidden="true"><i class="fa-solid fa-trash-can"></i></div>
        <h2 id="confirmDeleteTitle">Delete this vaccine?</h2>
        <p id="confirmDeleteDescription">This will permanently remove the vaccine and its recorded batch details. This action cannot be undone.</p>
        <div class="confirm-delete-actions">
            <button type="button" id="cancelDeleteButton">Cancel</button>
            <button type="button" class="confirm-delete-button" id="confirmDeleteButton">Delete vaccine</button>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; text-transform: uppercase; font-size: 1rem;">Edit Vaccine / Batch Details</h3>
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
                <label>Current Stock</label>
                <input type="number" name="available_stock" id="edit_available_stock" min="0" required>
                <small>Dagdag = IN; bawas = OUT.</small>
            </div>
            <div class="form-group">
                <label for="edit_batch_id">Batch to Edit</label>
                <select name="batch_id" id="edit_batch_id" onchange="loadEditBatchDetails()">
                    <option value="">-- No batch selected --</option>
                    <?php foreach ($edit_batches as $edit_batch): ?>
                        <option value="<?= (int) $edit_batch['id'] ?>"
                                data-vaccine-id="<?= (int) $edit_batch['vaccine_id'] ?>"
                                data-batch-number="<?= htmlspecialchars($edit_batch['batch_number'], ENT_QUOTES, 'UTF-8') ?>"
                                data-manufactured-date="<?= htmlspecialchars($edit_batch['manufactured_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-expiration-date="<?= htmlspecialchars($edit_batch['expiration_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-stock-in-date="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($edit_batch['stock_in_date'])), ENT_QUOTES, 'UTF-8') ?>"
                                data-received-by="<?= htmlspecialchars($edit_batch['received_by'], ENT_QUOTES, 'UTF-8') ?>"
                                data-provided-by="<?= htmlspecialchars($edit_batch['provided_by'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($edit_batch['batch_number'] . ' | ' . date('M d, Y', strtotime($edit_batch['stock_in_date'])), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Select a recorded batch to edit its tracking details.</small>
            </div>
            <div class="form-group">
                <label for="edit_batch_number">Batch Number</label>
                <input type="text" name="batch_number" id="edit_batch_number" maxlength="100">
            </div>
            <div class="form-group">
                <label for="edit_manufactured_date">Manufactured Date</label>
                <input type="date" name="manufactured_date" id="edit_manufactured_date">
            </div>
            <div class="form-group">
                <label for="edit_expiration_date">Expiration Date</label>
                <input type="date" name="expiration_date" id="edit_expiration_date">
            </div>
            <div class="form-group">
                <label for="edit_stock_in_datetime">Stock Entry Date and Time</label>
                <input type="datetime-local" name="stock_in_datetime" id="edit_stock_in_datetime">
            </div>
            <div class="form-group">
                <label for="edit_batch_provided_by">Provided By</label>
                <input type="text" name="batch_provided_by" id="edit_batch_provided_by" maxlength="255">
            </div>
            <div class="form-group">
                <label for="edit_batch_received_by">Received By</label>
                <input type="text" name="batch_received_by" id="edit_batch_received_by" maxlength="255">
            </div>
            <button type="submit" name="update_vaccine" class="btn-submit">I-save ang Detalye</button>
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

    (function initializeReturnBatchFields() {
        const vaccineSelect = document.getElementById('return_vaccine_id');
        const categorySelect = document.getElementById('return_category');
        const batchSelect = document.getElementById('return_batch_id');
        const batchCount = document.getElementById('return_batch_count');
        if (!vaccineSelect || !categorySelect || !batchSelect || !batchCount) return;

        const batchOptions = Array.from(batchSelect.querySelectorAll('option[data-vaccine-id]'))
            .map(option => ({
                value: option.value,
                vaccineId: option.dataset.vaccineId,
                category: option.dataset.category,
                label: option.textContent.trim()
            }));
        const vaccineOptions = Array.from(vaccineSelect.querySelectorAll('option[data-category]'));

        function resetSelect(select, placeholder) {
            select.innerHTML = '';
            const option = document.createElement('option');
            option.value = '';
            option.textContent = placeholder;
            select.appendChild(option);
        }

        categorySelect.addEventListener('change', function() {
            vaccineSelect.value = '';
            resetSelect(batchSelect, '-- Select batch number --');
            vaccineSelect.disabled = !categorySelect.value;
            batchSelect.disabled = true;
            batchCount.textContent = 'Choose a vaccine name to view recorded batches.';

            vaccineOptions.forEach(option => {
                const matchesCategory = option.dataset.category === categorySelect.value;
                option.hidden = !matchesCategory;
                option.disabled = !matchesCategory;
            });
        });

        vaccineSelect.addEventListener('change', function() {
            resetSelect(batchSelect, '-- Select batch number --');
            const matches = batchOptions.filter(batch =>
                batch.vaccineId === vaccineSelect.value && batch.category === categorySelect.value
            );
            batchCount.textContent = matches.length === 1
                ? '1 in-stock batch available.'
                : matches.length + ' in-stock batches available.';

            matches.forEach(batch => {
                const option = document.createElement('option');
                option.value = batch.value;
                option.textContent = batch.label;
                batchSelect.appendChild(option);
            });
            batchSelect.disabled = !categorySelect.value || matches.length === 0;
        });

        categorySelect.dispatchEvent(new Event('change'));
    })();

    function openStockBreakdown() {
        document.getElementById('stockBreakdownModal').style.display = 'block';
    }

    function closeStockBreakdown() {
        document.getElementById('stockBreakdownModal').style.display = 'none';
    }

    function openExpiredVaccineModal() {
        document.getElementById('expiredVaccineModal').style.display = 'block';
    }

    function closeExpiredVaccineModal() {
        document.getElementById('expiredVaccineModal').style.display = 'none';
    }

    function updateVaccineDateLimits() {
        const stockDate = document.getElementById('stock_date');
        const manufacturedDate = document.getElementById('manufactured_date');
        const expirationDate = document.getElementById('expiration_date');
        const latestManufactureDate = stockDate.value || stockDate.max;
        manufacturedDate.max = latestManufactureDate;
        if (manufacturedDate.value && manufacturedDate.value > latestManufactureDate) {
            manufacturedDate.value = '';
        }

        const latestRelevantDate = manufacturedDate.value > stockDate.value
            ? manufacturedDate.value
            : stockDate.value;
        if (latestRelevantDate) {
            const nextDate = new Date(latestRelevantDate + 'T00:00:00Z');
            nextDate.setUTCDate(nextDate.getUTCDate() + 1);
            expirationDate.min = nextDate.toISOString().slice(0, 10);
        }

        if (expirationDate.value && expirationDate.value < expirationDate.min) {
            expirationDate.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateVaccineDateLimits();

        const stockBreakdownModal = document.getElementById('stockBreakdownModal');
        stockBreakdownModal.addEventListener('click', function(event) {
            if (event.target === stockBreakdownModal) {
                closeStockBreakdown();
            }
        });
        const expiredVaccineModal = document.getElementById('expiredVaccineModal');
        expiredVaccineModal.addEventListener('click', function(event) {
            if (event.target === expiredVaccineModal) {
                closeExpiredVaccineModal();
            }
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && stockBreakdownModal.style.display === 'block') {
                closeStockBreakdown();
            }
            if (event.key === 'Escape' && expiredVaccineModal.style.display === 'block') {
                closeExpiredVaccineModal();
            }
        });

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
    function openEditModal(id, name, description, category, availableStock) {
        document.getElementById('edit_vaccine_id').value = id;
        document.getElementById('edit_category').value = category;
        filterVaccineOptions('edit_category', 'edit_vaccine_name');
        document.getElementById('edit_vaccine_name').value = name;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_available_stock').value = availableStock;

        const batchSelect = document.getElementById('edit_batch_id');
        const batchOptions = Array.from(batchSelect.options).filter(option => option.dataset.vaccineId);
        const matchingBatches = batchOptions.filter(option => option.dataset.vaccineId === String(id));
        batchOptions.forEach(option => {
            const matchesVaccine = option.dataset.vaccineId === String(id);
            option.hidden = !matchesVaccine;
            option.disabled = !matchesVaccine;
        });
        batchSelect.value = matchingBatches.length ? matchingBatches[0].value : '';
        loadEditBatchDetails();
        
        document.getElementById('editModal').style.display = 'block';
    }

    function loadEditBatchDetails() {
        const batchSelect = document.getElementById('edit_batch_id');
        const selectedBatch = batchSelect.selectedOptions[0];
        const hasBatch = Boolean(selectedBatch && selectedBatch.dataset.vaccineId);
        const fields = {
            batch_number: document.getElementById('edit_batch_number'),
            manufactured_date: document.getElementById('edit_manufactured_date'),
            expiration_date: document.getElementById('edit_expiration_date'),
            stock_in_datetime: document.getElementById('edit_stock_in_datetime'),
            provided_by: document.getElementById('edit_batch_provided_by'),
            received_by: document.getElementById('edit_batch_received_by')
        };
        const datasetKeys = {
            batch_number: 'batchNumber',
            manufactured_date: 'manufacturedDate',
            expiration_date: 'expirationDate',
            stock_in_datetime: 'stockInDate',
            provided_by: 'providedBy',
            received_by: 'receivedBy'
        };

        Object.entries(fields).forEach(([fieldName, input]) => {
            input.disabled = !hasBatch;
            input.required = hasBatch && ['batch_number', 'stock_in_datetime', 'provided_by', 'received_by'].includes(fieldName);
            input.value = hasBatch ? (selectedBatch.dataset[datasetKeys[fieldName]] || '') : '';
        });
    }

    function updateStockPreview() {
        const vaccineSelect = document.getElementById('add_vaccine_name');
        const quantityInput = document.getElementById('quantity_received');
        const currentStockInput = document.getElementById('current_stock');
        const stockAfterInput = document.getElementById('stock_after_receipt');
        const selectedOption = vaccineSelect.selectedOptions[0];
        const currentStock = selectedOption && selectedOption.dataset.category
            ? Number(selectedOption.dataset.currentStock || 0)
            : 0;
        const receivedQuantity = Math.max(0, Number.parseInt(quantityInput.value, 10) || 0);

        currentStockInput.value = currentStock;
        stockAfterInput.value = currentStock + receivedQuantity;
    }

    function updateNextBatchNumber() {
        const vaccineSelect = document.getElementById('add_vaccine_name');
        const batchNumberInput = document.getElementById('batch_number');
        const selectedOption = vaccineSelect.selectedOptions[0];

        batchNumberInput.value = selectedOption && selectedOption.dataset.category
            ? selectedOption.dataset.nextBatch
            : '';
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

        if (vaccineId === 'add_vaccine_name') {
            updateStockPreview();
            updateNextBatchNumber();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.vaccine-search').forEach(function(searchInput) {
            const table = document.getElementById(searchInput.dataset.table);
            const count = document.getElementById(searchInput.dataset.count);
            const tabContent = searchInput.closest('.tab-content');
            const filterEmpty = tabContent.querySelector('.vaccine-filter-empty');
            const rows = Array.from(table.querySelectorAll('tbody tr[data-vaccine-search]'));
            const hasInventoryRows = rows.length > 0;

            function filterVaccines() {
                const searchTerm = searchInput.value.trim().toLowerCase();
                let visibleCount = 0;

                rows.forEach(function(row) {
                    const matches = row.dataset.vaccineSearch.includes(searchTerm);
                    row.hidden = !matches;
                    if (matches) visibleCount++;
                });

                filterEmpty.style.display = hasInventoryRows && visibleCount === 0 ? 'block' : 'none';
                count.textContent = hasInventoryRows
                    ? 'Ipinapakita ang ' + visibleCount + ' sa ' + rows.length + ' bakuna'
                    : '';
            }

            searchInput.addEventListener('input', filterVaccines);
            filterVaccines();
        });

        document.querySelectorAll('.alert-message').forEach(function(alertMessage) {
            const dismissButton = alertMessage.querySelector('.alert-dismiss');
            const dismiss = function() {
                if (alertMessage.classList.contains('is-dismissing')) return;
                alertMessage.classList.add('is-dismissing');
                window.setTimeout(function() {
                    alertMessage.remove();
                }, 300);
            };

            dismissButton.addEventListener('click', dismiss);
            const autoDismissDelay = Number.parseInt(alertMessage.dataset.autodismiss, 10);
            if (autoDismissDelay > 0) {
                window.setTimeout(dismiss, autoDismissDelay);
            }
        });

        const confirmDeleteModal = document.getElementById('confirmDeleteModal');
        const cancelDeleteButton = document.getElementById('cancelDeleteButton');
        const confirmDeleteButton = document.getElementById('confirmDeleteButton');
        let pendingDeleteUrl = '';
        let lastDeleteTrigger = null;

        function closeDeleteConfirmation() {
            confirmDeleteModal.classList.remove('is-open');
            pendingDeleteUrl = '';
            if (lastDeleteTrigger) lastDeleteTrigger.focus();
        }

        document.querySelectorAll('[data-confirm-delete]').forEach(function(deleteLink) {
            deleteLink.addEventListener('click', function(event) {
                event.preventDefault();
                pendingDeleteUrl = deleteLink.href;
                lastDeleteTrigger = deleteLink;
                confirmDeleteModal.classList.add('is-open');
                confirmDeleteButton.focus();
            });
        });

        cancelDeleteButton.addEventListener('click', closeDeleteConfirmation);
        confirmDeleteModal.addEventListener('click', function(event) {
            if (event.target === confirmDeleteModal) closeDeleteConfirmation();
        });
        confirmDeleteButton.addEventListener('click', function() {
            if (pendingDeleteUrl) window.location.assign(pendingDeleteUrl);
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && confirmDeleteModal.classList.contains('is-open')) {
                closeDeleteConfirmation();
            }
        });

        filterVaccineOptions('add_category', 'add_vaccine_name');
        filterVaccineOptions('edit_category', 'edit_vaccine_name');
        updateStockPreview();
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
                        label: 'Stock IN (Received / Correction)',
                        data: dataIn,
                        backgroundColor: '#718355', // Sage Green
                        borderRadius: 3
                    },
                    {
                        label: 'Stock OUT (Used / Correction)',
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