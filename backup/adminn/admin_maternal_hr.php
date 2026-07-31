<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. DYNAMIC SIDEBAR LOGIC
$u_id = $_SESSION['user_id'];
$get_user = mysqli_query($conn, "SELECT role FROM users WHERE id = '$u_id'");
$user_info = mysqli_fetch_assoc($get_user);
$current_role = strtolower(trim($user_info['role'] ?? ''));

$sidebar_file = (in_array($current_role, ['super admin', 'superadmin'])) ? 'super_admin_sidebar.php' : 'admin_sidebar.php';

// 2. FETCH MOTHERS DATA
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM maternal_records WHERE mother_id = u.id) as checkup_count,
        (SELECT MAX(checkup_date) FROM maternal_records WHERE mother_id = u.id) as last_visit
        FROM users u WHERE u.role = 'user' ORDER BY u.first_name ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maternal Health Records | Alawihao Center</title>
    <link rel="stylesheet" href="">
    <style>
        :root { 
            --sage: #8DAE74; 
            --dark-sage: #6B8E55; 
            --soft-sage: #F1F5ED; 
            --sidebar-width: 280px; 
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            background: #F8FAFC; 
            display: flex; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* DYNAMIC WRAPPER - Eto ang mag-aadjust */
        #main-wrapper { 
            flex-grow: 1; 
            padding: 30px; 
            margin-left: var(--sidebar-width); /* Default: Nakabukas ang sidebar */
            transition: var(--transition); 
            width: 100%;
            box-sizing: border-box;
        }

        /* Kapag may 'full-width' class, didikit sa kaliwa */
        #main-wrapper.full-width { 
            margin-left: 0 !important; 
        }

        /* UI Styling */
        .page-header {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border: 1px solid #edf2f7;
        }

        .hamburger-btn {
            background: var(--soft-sage);
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
            color: var(--dark-sage);
            font-size: 20px;
            margin-right: 15px;
            transition: 0.2s;
        }
        .hamburger-btn:hover { background: #e2eadb; }

        .table-container { 
            background: white; 
            padding: 25px; 
            border-radius: 15px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            border: 1px solid #edf2f7;
        }

        table { width: 100%; border-collapse: collapse; }
        th { 
            text-align: left; 
            padding: 15px; 
            background: var(--soft-sage); 
            color: var(--dark-sage); 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
        }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; }

        .btn-group { display: flex; gap: 10px; }
        .btn-history { 
            background: var(--soft-sage); 
            color: var(--dark-sage); 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.8rem; 
        }
        .btn-update { 
            background: var(--sage); 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 0.8rem;
        }

        .role-badge { background: #FEFCBF; color: #744210; padding: 6px 14px; border-radius: 8px; font-size: 11px; font-weight: 700; }
        
        /* Modal */
        .modal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter: blur(2px); }
        .modal-content { background:white; margin:5% auto; padding:30px; width:400px; border-radius:15px; border-top:8px solid var(--sage); box-shadow: 0 20px 25px rgba(0,0,0,0.1); }
        
        input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; }
        label { font-size: 0.85rem; font-weight: 600; color: #4A5568; margin-bottom: 5px; display: block; }
    </style>
</head>
<body>

    <?php include $sidebar_file; ?>

    <div id="main-wrapper">
        <div class="page-header">
            <div style="display: flex; align-items: center;">
                <h2 style="color: var(--dark-sage); margin: 0;">Maternal Health History</h2>
            </div>
            <div class="role-badge">LOGGED AS: <?php echo strtoupper($current_role); ?></div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Mother Name</th>
                        <th>Last Visit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): 
                        $full_name = htmlspecialchars(($row['first_name'] ?? 'Unknown') . ' ' . ($row['last_name'] ?? ''));
                    ?>
                    <tr>
                        <td>
                            <span style="font-weight: 600; color: #2D3748;"><?php echo $full_name; ?></span><br>
                            <small style="color: #A0AEC0;"><?php echo $row['checkup_count'] ?? 0; ?> total visits</small>
                        </td>
                        <td style="color: #4A5568;">
                            <?php echo ($row['last_visit']) ? date("M d, Y", strtotime($row['last_visit'])) : '<span style="color:#CBD5E0">No records</span>'; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="admin_maternal_view.php?id=<?php echo $row['id']; ?>" class="btn-history">View History</a>
                                <button class="btn-update" onclick="openUpdateModal('<?php echo $row['id']; ?>', '<?php echo addslashes($full_name); ?>')">Add Record</button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="updateModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle" style="color:var(--dark-sage); margin-top:0;">Add Health Data</h3>
            <form method="POST" action="admin_save_maternal_rec.php">
                <input type="hidden" name="mother_id" id="modal_mother_id">
                <label>Weight (kg)</label>
                <input type="number" name="weight" step="0.1" required>
                <label>Blood Pressure</label>
                <input type="text" name="bp" placeholder="120/80" required>
                <label>Temperature (°C)</label>
                <input type="number" name="temp" step="0.1" required>
                
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                    <button type="button" onclick="closeModal()" style="background:none; border:none; color:#A0AEC0; cursor:pointer; font-weight:600;">Cancel</button>
                    <button type="submit" class="btn-update">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // ETO ANG FUNCTION PARA MAG-SYNC ANG SIDEBAR AT DASHBOARD
    function toggleInterface() {
        const sidebar = document.getElementById('mainSidebar');
        const wrapper = document.getElementById('main-wrapper');
        
        if(sidebar) {
            // I-toggle ang 'is-hidden' sa sidebar
            const isHidden = sidebar.classList.toggle('is-hidden');
            
            // I-toggle ang 'full-width' sa dashboard wrapper
            if(isHidden) {
                wrapper.classList.add('full-width');
                localStorage.setItem('sidebarState', 'hidden');
            } else {
                wrapper.classList.remove('full-width');
                localStorage.setItem('sidebarState', 'visible');
            }
        }
    }

    // Pag-load ng page, i-check ang huling state
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('mainSidebar');
        const wrapper = document.getElementById('main-wrapper');
        const savedState = localStorage.getItem('sidebarState');

        if(savedState === 'hidden' && sidebar) {
            sidebar.classList.add('is-hidden');
            wrapper.classList.add('full-width');
        }
    });

    function openUpdateModal(id, name) {
        document.getElementById('updateModal').style.display = 'block';
        document.getElementById('modal_mother_id').value = id;
        document.getElementById('modalTitle').innerText = "Record for: " + name;
    }

    function closeModal() { document.getElementById('updateModal').style.display = 'none'; }
    window.onclick = function(event) { if (event.target == document.getElementById('updateModal')) closeModal(); }
    </script>
</body>
</html>