<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; 

if (isset($_GET['ajax_id'])) {
    header('Content-Type: application/json');
    $reg_id = intval($_GET['ajax_id']);

    $sql_reg = "SELECT *, CONCAT(client_fname, ' ', COALESCE(CONCAT(client_mi, '. '), ''), client_lname) AS full_name,
                lmp AS edc, CONCAT(COALESCE(street, ''), ', Brgy. ', COALESCE(barangay, ''), ', ', COALESCE(municipality, ''), ', ', COALESCE(province, '')) AS current_address 
                FROM maternal_registration WHERE id = ? AND user_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql_reg);
    $stmt->bind_param("ii", $reg_id, $user_id);
    $stmt->execute();
    $res_reg = $stmt->get_result();

    if ($res_reg->num_rows > 0) {
        $record = $res_reg->fetch_assoc();
        $sql_chk = "SELECT * FROM maternal_records WHERE mother_id = ? ORDER BY checkup_date DESC";
        $stmt_chk = $conn->prepare($sql_chk);
        $stmt_chk->bind_param("i", $reg_id);
        $stmt_chk->execute();
        $res_chk = $stmt_chk->get_result();

        $checkups = [];
        while($row_chk = $res_chk->fetch_assoc()) {
            $checkups[] = $row_chk;
        }

        echo json_encode(['success' => true, 'record' => $record, 'checkups' => $checkups]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

$user_fallback_query = "SELECT first_name, last_name, contact_number, address FROM users WHERE id = ? LIMIT 1";
$stmt_fb = $conn->prepare($user_fallback_query);
$stmt_fb->bind_param("i", $user_id);
$stmt_fb->execute();
$user_info = $stmt_fb->get_result()->fetch_assoc();

$profile_query = "SELECT *, 
                         CONCAT(client_fname, ' ', COALESCE(CONCAT(client_mi, '. '), ''), client_lname) AS full_name,
                         contact AS contact_number,
                         CONCAT(COALESCE(street, ''), ', Brgy. ', COALESCE(barangay, ''), ', ', COALESCE(municipality, ''), ', ', COALESCE(province, '')) AS full_address
                  FROM maternal_registration 
                  WHERE user_id = ? 
                  ORDER BY id DESC LIMIT 1";
$stmt_prof = $conn->prepare($profile_query);
$stmt_prof->bind_param("i", $user_id);
$stmt_prof->execute();
$result_prof = $stmt_prof->get_result();

if ($result_prof->num_rows > 0) {
    $user_profile = $result_prof->fetch_assoc();
} else {
    $user_profile = [
        'full_name' => trim(($user_info['first_name'] ?? '') . ' ' . ($user_info['last_name'] ?? '')),
        'contact_number' => $user_info['contact_number'] ?? 'Not provided',
        'full_address' => $user_info['address'] ?? 'Not provided',
        'living_children' => '0',
        'pregnancy_order' => 1
    ];
}

$query = "SELECT reg.*, 
                 CONCAT(reg.client_fname, ' ', COALESCE(CONCAT(reg.client_mi, '. '), ''), reg.client_lname) AS full_name,
                 reg.lmp AS edc,
                 reg.age AS client_age,
                 reg.contact AS contact_number,
                 CONCAT(COALESCE(reg.street, ''), ', Brgy. ', COALESCE(reg.barangay, ''), ', ', COALESCE(reg.municipality, ''), ', ', COALESCE(reg.province, '')) AS current_address,
                 reg.id AS reg_id
          FROM maternal_registration reg
          WHERE reg.user_id = ? 
          ORDER BY reg.id ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_records = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maternal Records | Alawihao</title>
    <style>
        :root { 
            --primary-green: #718355; 
            --bg-color: #f8faf5; 
            --white: #ffffff; 
            --dark-gray: #2d3436;
            --sage-light: #95AF7E;
            --border-color: #e5eadc;
        }

        body { 
            font-family: 'Times New Roman', serif; 
            background-color: var(--bg-color); 
            margin: 0; 
            display: flex; 
        }

/* FLEXIBLE MAIN CONTENT: Naka-open ang sidebar by default */
        #main { 
            margin-left: 260px; /* Tugma sa --sidebar-width ng sidebar mo */
            width: calc(100% - 260px); 
            padding-bottom: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.3s ease-in-out;
        }

        /* KAPAG NAKASARA ANG SIDEBAR: Magiging full-width at mawawala ang margin sa kaliwa */
        body.sidebar-closed #main {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .header { 
            width: 100%; 
            background: var(--white); 
            padding: 25px 40px; 
            border-bottom: 3px solid var(--primary-green); 
            box-sizing: border-box; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 10px rgba(113, 131, 85, 0.05); 
        }

        .profile-card {
            background: var(--white);
            width: 92%;
            padding: 25px 35px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(113, 131, 85, 0.06);
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
            box-sizing: border-box;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .profile-info h3 { margin: 0 0 5px 0; color: var(--primary-green); font-size: 1.2rem; }
        .profile-info p { margin: 0; color: #666; font-size: 0.85rem; }
        .profile-stats { display: flex; gap: 20px; }
        .stat-box {
            background: #f4f7f0;
            padding: 12px 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary-green);
            text-align: center;
        }
        .stat-box small { display: block; color: var(--primary-green); font-size: 0.65rem; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; }
        .stat-box span { font-size: 1rem; font-weight: bold; color: var(--dark-gray); }

        .table-container { 
            background: var(--white); 
            width: 92%; 
            padding: 35px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(113, 131, 85, 0.06); 
            border: 1px solid var(--border-color);
            border-top: 8px solid var(--primary-green); 
            box-sizing: border-box; 
        }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { 
            background: #f4f7f0; 
            color: var(--primary-green); 
            padding: 16px; 
            text-align: left; 
            border-bottom: 2px solid var(--border-color); 
            text-transform: uppercase; 
            font-size: 0.7rem; 
            letter-spacing: 1.5px;
        }
        td { padding: 16px; border-bottom: 1px solid #f2f5ee; color: var(--dark-gray); font-size: 0.9rem; }
        tr:hover { background-color: #fafbf8; }

        .status-badge { 
            padding: 6px 12px; 
            border-radius: 4px; 
            font-size: 0.7rem; 
            font-weight: bold; 
            text-transform: uppercase;
        }
        .status-verified { background: #f0f4e8; color: var(--primary-green); border: 1px solid var(--primary-green); }
        .status-pending { background: #fffcf0; color: #d4a017; border: 1px solid #d4a017; }

        .order-badge {
            background: #e8ede3;
            color: var(--primary-green);
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.75rem;
        }

        .action-btns { display: flex; gap: 8px; }
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            border: none;
            text-transform: uppercase;
            transition: 0.3s;
        }
        .details-btn { background: var(--primary-green); color: white; }
        .details-btn:hover { background: #5a6a44; }

        .modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(45, 52, 54, 0.5);
            backdrop-filter: blur(3px);
            overflow-y: auto;
        }
        .modal-content {
            background-color: var(--white);
            margin: 4% auto;
            padding: 30px 40px;
            width: 750px;
            max-width: 90%;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            border-top: 8px solid var(--primary-green);
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .close-modal {
            position: absolute;
            right: 25px; top: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #aaa;
            transition: 0.2s;
        }
        .close-modal:hover { color: var(--primary-green); }
    </style>
</head>
<body>

<?php include 'user_sidebar.php'; ?>

<div id="main">
    <div class="header">
        <h3 style="margin:0; letter-spacing: 4px; color: var(--primary-green); font-weight: 900;">ALAWIHAO HEALTH CENTER</h3>
    </div>

    <!-- PROFILE CARD -->
    <div class="profile-card">
        <div class="profile-info">
            <h3><?php echo htmlspecialchars($user_profile['full_name'] ?? 'Pangalan ng User'); ?></h3>
            <p>Contact: <?php echo htmlspecialchars($user_profile['contact_number'] ?? 'Not provided'); ?> | Address: <?php echo htmlspecialchars($user_profile['full_address'] ?? 'Not provided'); ?></p>
        </div>
        <div class="profile-stats">
            <div class="stat-box">
                <small>Total Children</small>
                <span><?php echo htmlspecialchars($user_profile['living_children'] ?? '0'); ?></span>
            </div>
            <div class="stat-box">
                <small>Current Status</small>
                <span><?php echo isset($user_profile['pregnancy_order']) ? $user_profile['pregnancy_order'] . getOrdinalSuffix($user_profile['pregnancy_order']) . ' Pregnancy' : 'Active'; ?></span>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-green); margin: 0; letter-spacing: 1px;">MATERNAL HEALTH RECORDS</h2>
            <p style="color: #777; font-size: 0.8rem; margin-top: 5px;">View your pregnancy tracking history sorted by chronological sequence.</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Pregnancy Order</th>
                    <th>Due Date (EDC / LMP)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                function getOrdinalSuffix($num) {
                    if (!in_array(($num % 100), array(11,12,13))) {
                        switch ($num % 10) {
                            case 1:  return 'st';
                            case 2:  return 'nd';
                            case 3:  return 'rd';
                        }
                    }
                    return 'th';
                }

                if ($my_records->num_rows > 0): 
                    while($row = $my_records->fetch_assoc()): 
                        $p_order = $row['pregnancy_order'] ? $row['pregnancy_order'] : 1;
                        $order_label = $p_order . getOrdinalSuffix($p_order) . ' Child / Preg';
                ?>
                    <tr>
                        <td>
                            <span class="order-badge"><?php echo $order_label; ?></span>
                        </td>
                        <td><?php echo $row['edc'] ? date('M d, Y', strtotime($row['edc'])) : '<span style="color:#ccc">--</span>'; ?></td>
                        <td>
                            <span class="status-badge <?php echo (isset($row['status']) && $row['status'] == 'Approved') ? 'status-verified' : 'status-pending'; ?>">
                                <?php echo isset($row['status']) ? $row['status'] : 'Pending'; ?>
                            </span>
                        </td>
                        <td class="action-btns">
                            <button class="btn details-btn" onclick='fetchDetails(<?php echo $row['reg_id']; ?>, "<?php echo $order_label; ?>")'>View Details</button>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <tr><td colspan="4" style="text-align:center; padding: 50px; color: #aaa;">Walang nahanap na maternal registration records para sa account na ito.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PARA SA DETAILS AT MONTHLY CHECK-UPS -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle" style="color: var(--primary-green); border-bottom: 2px solid #f4f7f0; padding-bottom: 15px; margin-bottom: 20px;">Patient Details</h3>
        
        <div id="modalBody">
            <!-- Dynamic Data -->
        </div>
        
        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #f2f5ee; font-size: 0.8rem; color: #888; text-align: right;">
            *System Maternal Monitoring Record
        </div>
    </div>
</div>

<script>
    // UNIVERSAL CLICK LISTENER: Sasabayan nito ang anumang sidebar toggle button sa iyong sidebar file
    document.addEventListener("DOMContentLoaded", function() {
        // Humanap ng kahit anong button sa paligid (kabilang ang hamburger icon sa sidebar)
        document.addEventListener('click', function(event) {
            const target = event.target.closest('button, .toggle-btn, .menu-btn, #sidebarToggle, [onclick*="sidebar"]');
            if (target) {
                setTimeout(() => {
                    // Susuriin kung ang sidebar ay lumiit o nawala base sa lapad o kaya ay i-toggle ang body class
                    const sidebar = document.querySelector('aside, .sidebar, #sidebar, nav');
                    if (sidebar) {
                        const width = sidebar.getBoundingClientRect().width;
                        if (width < 80) {
                            document.body.classList.add('sidebar-collapsed');
                        } else {
                            document.body.classList.remove('sidebar-collapsed');
                        }
                    } else {
                        document.body.classList.toggle('sidebar-collapsed');
                    }
                }, 100);
            }
        });
    });

    function fetchDetails(regId, orderLabel) {
        const modal = document.getElementById('detailsModal');
        const body = document.getElementById('modalBody');
        
        document.getElementById('modalTitle').innerText = "Loading details...";
        body.innerHTML = "<p style='text-align:center; padding:20px; color:#666;'>Loading pregnancy & check-up history...</p>";
        modal.style.display = "block";

        fetch('user_maternal_records.php?ajax_id=' + regId)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    let reg = data.record;
                    let checkups = data.checkups;

                    document.getElementById('modalTitle').innerText = reg.full_name;

                    let checkupRows = '';
                    if(checkups.length > 0) {
                        checkups.forEach((chk) => {
                            checkupRows += `
                                <tr>
                                    <td style="border: 1px solid #e5eadc; padding: 8px; text-align:center;">${chk.checkup_date || '--'}</td>
                                    <td style="border: 1px solid #e5eadc; padding: 8px; text-align:center;">${chk.aog || '--'}</td>
                                    <td style="border: 1px solid #e5eadc; padding: 8px; text-align:center;">${chk.weight_kg || '--'} kg</td>
                                    <td style="border: 1px solid #e5eadc; padding: 8px; text-align:center;">${chk.bp || '--'}</td>
                                    <td style="border: 1px solid #e5eadc; padding: 8px; text-align:center;">${chk.fetal_heart_rate || '--'} bpm</td>
                                    <td style="border: 1px solid #e5eadc; padding: 8px;">${chk.remarks || 'None'}</td>
                                </tr>
                            `;
                        });
                    } else {
                        checkupRows = `<tr><td colspan="6" style="border: 1px solid #e5eadc; padding: 15px; text-align:center; color:#888;">Wala pang naka-record na monthly check-up para sa pagbubuntis na ito.</td></tr>`;
                    }

                    body.innerHTML = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Record Order</small><br><b style="color:var(--primary-green)">${orderLabel}</b></div>
                            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Age</small><br><b>${reg.age || '--'} yrs old</b></div>
                            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Contact Number</small><br><b>${reg.contact || '--'}</b></div>
                            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">LMP (Last Period)</small><br><b>${reg.lmp || '--'}</b></div>
                            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">EDC (Estimated Due Date)</small><br><b style="color:var(--primary-green)">${reg.edc || '--'}</b></div>
                            <div><small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Status</small><br><b>${reg.status || 'Pending'}</b></div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <small style="color:#888; text-transform:uppercase; font-size:0.65rem; font-weight:bold;">Current Address</small><br>
                            <b>${reg.current_address || '--'}</b>
                        </div>

                        <div style="margin-top: 20px;">
                            <h4 style="color: var(--primary-green); border-left: 4px solid var(--primary-green); padding-left: 8px; margin-bottom: 10px; font-size: 0.9rem; text-transform: uppercase;">Monthly Check-up & Prenatal History</h4>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                                    <thead>
                                        <tr style="background: #f4f7f0; color: var(--primary-green);">
                                            <th style="border: 1px solid #e5eadc; padding: 8px;">Date</th>
                                            <th style="border: 1px solid #e5eadc; padding: 8px;">AOG</th>
                                            <th style="border: 1px solid #e5eadc; padding: 8px;">Weight</th>
                                            <th style="border: 1px solid #e5eadc; padding: 8px;">BP</th>
                                            <th style="border: 1px solid #e5eadc; padding: 8px;">FHR</th>
                                            <th style="border: 1px solid #e5eadc; padding: 8px;">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${checkupRows}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                } else {
                    body.innerHTML = "<p style='text-align:center; padding:20px; color:red;'>Hindi makuha ang detalye ng rekord.</p>";
                }
            })
            .catch(() => {
                body.innerHTML = "<p style='text-align:center; padding:20px; color:red;'>May error sa pagkonekta sa server.</p>";
            });
    }

    function closeModal() {
        document.getElementById('detailsModal').style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('detailsModal')) closeModal();
    }
</script>

</body>
</html>