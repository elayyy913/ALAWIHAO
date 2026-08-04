<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; 

// Logic para sa Delete/Remove
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    // Siguraduhin na sa kanya lang ang idedelete niya at 'Pending' lang ang pwede idelete
    $del_query = "DELETE FROM children WHERE id = ? AND user_id = ? AND status != 'Approved'";
    $del_stmt = $conn->prepare($del_query);
    $del_stmt->bind_param("ss", $delete_id, $user_id);
    $del_stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$query = "SELECT *, 
          TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) AS age_years, 
          TIMESTAMPDIFF(MONTH, birth_date, CURDATE()) % 12 AS age_months 
          FROM children 
          WHERE user_id = ? 
          ORDER BY id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$my_records = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records | Alawihao</title>
    <style>
        :root { 
            --primary-green: #718355; 
            --bg-color: #f5f5dc; 
            --white: #ffffff; 
            --dark-gray: #2d3436;
            --danger-red: #b33939;
        }

        body { 
            font-family: 'Times New Roman', serif; 
            background-color: var(--bg-color); 
            margin: 0; 
            display: flex; 
        }

        #main { 
            margin-left: 280px; 
            width: calc(100% - 280px); 
            padding-bottom: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header { 
            width: 100%; 
            background: var(--white); 
            padding: 25px 40px; 
            border-bottom: 5px solid var(--primary-green); 
            box-sizing: border-box; 
            margin-bottom: 40px; 
            box-shadow: 0 2px 15px rgba(0,0,0,0.05); 
        }

        .table-container { 
            background: var(--white); 
            width: 92%; 
            padding: 35px; 
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.08); 
            border-top: 10px solid var(--primary-green); 
            box-sizing: border-box; 
        }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { 
            background: #f9fafb; 
            color: var(--primary-green); 
            padding: 18px; 
            text-align: left; 
            border-bottom: 3px solid #eee; 
            text-transform: uppercase; 
            font-size: 0.7rem; 
            letter-spacing: 1.5px;
        }
        td { padding: 18px; border-bottom: 1px solid #f0f0f0; color: var(--dark-gray); font-size: 0.9rem; }
        tr:hover { background-color: #fcfdfa; }

        .btn-register {
            background-color: var(--primary-green);
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        /* STATUS BADGES */
        .status-badge { 
            padding: 4px 10px; 
            border-radius: 2px; 
            font-size: 0.65rem; 
            font-weight: bold; 
            text-transform: uppercase;
        }
        .status-approved { background: #f0f4e8; color: var(--primary-green); border: 1px solid var(--primary-green); }
        .status-pending { background: #fffcf0; color: #d4a017; border: 1px solid #d4a017; }

        /* ACTION BUTTONS */
        .action-btns { display: flex; gap: 5px; }
        .btn {
            padding: 7px 12px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            border: none;
            text-transform: uppercase;
        }
        .details-btn { background: var(--primary-green); color: white; }
        .print-btn { background: #57606f; color: white; }
        .remove-btn { background: transparent; color: var(--danger-red); border: 1px solid var(--danger-red); }
        .disabled { background: #eee; color: #aaa; cursor: not-allowed; }

        /* MODAL STYLE */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 40px;
            width: 500px;
            border-radius: 15px;
            border-top: 8px solid var(--primary-green);
            position: relative;
        }
        .close-modal {
            position: absolute;
            right: 20px; top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }
    </style>
</head>
<body>

<?php include 'user_sidebar.php'; ?>

<div id="main">
    <div class="header">
        <h3 style="margin:0; letter-spacing: 4px; color: var(--primary-green); font-weight: 900;">ALAWIHAO HEALTH CENTER</h3>
    </div>

    <div class="table-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="color: var(--primary-green); margin: 0; letter-spacing: 1px;">HEALTH RECORDS</h2>
                <p style="color: #888; font-size: 0.8rem; margin-top: 5px;">Family health document management system.</p>
            </div>
            <a href="user_reg_newborn.php" class="btn-register">ADD NEW REGISTRATION</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>FullName</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($my_records->num_rows > 0): ?>
                    <?php while($row = $my_records->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight: bold; color: #444;"><?php echo htmlspecialchars($row['child_name']); ?></td>
                        <td><?php echo "{$row['age_years']}Y, {$row['age_months']}M"; ?></td>
                        <td><?php echo $row['gender']; ?></td>
                        <td>
                            <span class="status-badge <?php echo ($row['status'] == 'Approved') ? 'status-approved' : 'status-pending'; ?>">
                                <?php echo ($row['status'] == 'Approved') ? 'Verified' : 'Pending'; ?>
                            </span>
                        </td>
                        <td class="action-btns">
                            <button class="btn details-btn" onclick="showDetails(<?php echo htmlspecialchars(json_encode($row)); ?>)">Details</button>
                            
                            <?php if($row['status'] == 'Approved'): ?>
                                <a href="print_record.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn print-btn">Print</a>
                            <?php else: ?>
                                <a href="?delete_id=<?php echo $row['id']; ?>" class="btn remove-btn" onclick="return confirm('Remove this pending registration?')">Remove</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 50px; color: #ccc;">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle" style="color: var(--primary-green); border-bottom: 1px solid #eee; padding-bottom: 10px;">Record Details</h3>
        <div id="modalBody" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            </div>
    </div>
</div>

<script>
    function showDetails(data) {
        const modal = document.getElementById('detailsModal');
        const body = document.getElementById('modalBody');
        document.getElementById('modalTitle').innerText = data.child_name;
        
        body.innerHTML = `
            <div><small style="color:#aaa">MOTHER</small><br><b>${data.mother_name}</b></div>
            <div><small style="color:#aaa">BIRTH DATE</small><br><b>${data.birth_date}</b></div>
            <div><small style="color:#aaa">BLOOD TYPE</small><br><b>${data.blood_type || 'N/A'}</b></div>
            <div><small style="color:#aaa">PLACE OF BIRTH</small><br><b>${data.place_of_birth}</b></div>
            <div style="grid-column: span 2"><small style="color:#aaa">STATUS</small><br><b style="color:var(--primary-green)">${data.status.toUpperCase()}</b></div>
        `;
        modal.style.display = "block";
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