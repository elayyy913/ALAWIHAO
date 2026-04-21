<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_with_str($conn, $_POST['full_name']);
    $age = $_POST['age'];
    $lmp = $_POST['lmp'];
    $address = mysqli_real_escape_with_str($conn, $_POST['address']);
    $contact = $_POST['contact'];

    /** * 1. SERVER-SIDE CALCULATION (Safety Check)
     * We ignore the 'edc' passed from the form and calculate it ourselves
     * to ensure the user didn't change it using 'Inspect Element'.
     */
    $date = new DateTime($lmp);
    $date->modify('+7 days');
    $date->modify('+9 months');
    $final_edc = $date->format('Y-m-d');

    /** * 2. PENDING STATUS
     * We save the status as 'Pending'. This ensures the data doesn't 
     * show up in main medical reports until an Admin verifies it.
     */
    $sql = "INSERT INTO maternal_registrations (user_id, full_name, age, lmp, edc, address, contact_number, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isissss", $user_id, $full_name, $age, $lmp, $final_edc, $address, $contact);

    if ($stmt->execute()) {
        $message = "<div class='success-msg'>Registration submitted! Our Health Worker will verify your Expected Due Date ($final_edc) before final approval.</div>";
    } else {
        $message = "<div class='alert error'>Error: " . $conn->error . "</div>";
    }
}

function mysqli_real_escape_with_str($conn, $str) {
    return mysqli_real_escape_string($conn, $str);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pregnancy Registration | Alawihao Health</title>
    <style>
        :root {
            --sage-green: #718355;
            --light-beige: #f5f5dc;
            --border-color: #eaddca;
        }

        body { background-color: var(--light-beige); margin: 0; font-family: 'Times New Roman', serif; overflow-x: hidden; }
        
        #main { 
            margin-left: 280px; 
            padding: 20px;
            min-height: 100vh;
            transition: margin-left .5s;
        }

        .health-care-card {
            background: white; 
            padding: 20px 30px; 
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            margin-bottom: 20px;
            border-bottom: 4px solid var(--sage-green); 
            color: var(--sage-green);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-btn {
            font-size: 24px;
            cursor: pointer;
            color: var(--sage-green);
        }

        .form-card {
            background: white; padding: 40px; border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            border-bottom: 4px solid var(--sage-green);
            max-width: 850px; margin: 0 auto;
        }

        .form-card h2 { color: var(--sage-green); margin-bottom: 10px; font-size: 1.8rem; text-align: center; }
        .form-subtitle { text-align: center; color: #777; font-style: italic; margin-bottom: 30px; display: block; }

        .form-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #888; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 5px; font-weight: bold; }
        
        .form-group input {
            width: 100%; padding: 12px; border: 1px solid var(--border-color);
            border-radius: 8px; background: #fafaf9; box-sizing: border-box;
            font-family: 'Times New Roman', serif; font-size: 1rem;
        }

        .reg-btn {
            background-color: var(--sage-green); color: white; border: none;
            padding: 15px 30px; border-radius: 8px; cursor: pointer;
            width: 100%; margin-top: 10px; font-size: 1.1rem; transition: 0.3s;
        }

        .reg-btn:hover { background-color: #5a6b43; }
        .success-msg { color: #2d6a4f; font-weight: bold; margin-bottom: 15px; text-align: center; background: #d8f3dc; padding: 10px; border-radius: 8px; }

        @media (max-width: 1000px) {
            #main { margin-left: 0; }
        }
    </style>
</head>
<body onload="openNav()"> 

<?php include 'user_sidebar.php'; ?>

<div id="main">
    <div class="health-care-card">
        <span class="menu-btn" onclick="openNav()">&#9776;</span>
        <strong>Alawihao Health Care</strong>
    </div>

    <div class="form-card">
        <h2>Enroll Pregnancy</h2>
        <span class="form-subtitle">Information will be verified by a Health Worker.</span>
        
        <?php if($message) echo $message; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name of Mother</label>
                    <input type="text" name="full_name" placeholder="e.g. Maria Clara" required>
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" required>
                </div>
            </div>

            <div class="form-group">
                <label>Current Address</label>
                <input type="text" name="address" placeholder="Barangay, City, Province" required>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact" placeholder="09XXXXXXXXX" required>
                </div>
                <div class="form-group">
                    <label>Last Menstrual Period (LMP)</label>
                    <input type="date" name="lmp" id="lmp" onchange="calculateEDC()" required>
                </div>
            </div>

            <div class="form-group">
                <label>System Estimated Due Date (EDC)</label>
                <input type="date" name="edc" id="edc" readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                <small style="color: #666;">*This is a preliminary calculation subject to medical approval.</small>
            </div>

            <button type="submit" class="reg-btn">Submit for Verification</button>
        </form>
    </div>
</div>

<script>
    function openNav() {
        if(document.getElementById("mySidenav")) {
            document.getElementById("mySidenav").style.width = "280px";
            document.getElementById("main").style.marginLeft = "280px";
        }
        if (typeof toggleDrop === "function") {
            toggleDrop('dropReg');
        }
    }

    function calculateEDC() {
        const lmpValue = document.getElementById('lmp').value;
        if (lmpValue) {
            let lmpDate = new Date(lmpValue);
            lmpDate.setDate(lmpDate.getDate() + 7);
            lmpDate.setMonth(lmpDate.getMonth() + 9);
            const year = lmpDate.getFullYear();
            const month = String(lmpDate.getMonth() + 1).padStart(2, '0');
            const day = String(lmpDate.getDate()).padStart(2, '0');
            document.getElementById('edc').value = `${year}-${month}-${day}`;
        }
    }
</script>

</body>
</html>