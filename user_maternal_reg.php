<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maternal Registration | Alawihao Health Center</title>
    <style>
        :root {
            --sage-green: #718355;
            --light-beige: #f8f9fa; 
            --border-color: #f8f9fa; 
            --sidebar-width: 280px;
        }

        body { 
            background-color: var(--light-beige); 
            margin: 0; 
            font-family: 'Times New Roman', serif; 
        }
        
        #main { 
            margin-left: var(--sidebar-width); 
            padding: 20px;
            transition: all 0.3s ease-in-out;
        }

        .form-card {
            background: white; 
            padding: 30px; 
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 1400px;
            margin: 0 auto;
        }

        h2 { 
            border-bottom: 1px solid #333; 
            padding-bottom: 5px; 
            font-size: 1rem;
            margin-top: 0;
            text-transform: uppercase;
        }

        .section-header {
            background: transparent;
            padding: 10px 0;
            margin: 25px 0 15px 0;
            font-weight: bold;
            font-size: 0.85rem;
            text-transform: uppercase;
            border-left: 4px solid var(--sage-green);
            padding-left: 10px;
        }

        .row {
            display: flex;
            gap: 12px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 120px;
        }

        .form-group label {
            font-size: 0.65rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input, .form-group select {
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 2px;
            font-size: 0.8rem;
            background: #fff;
        }

        /* CUSTOM SELECT STYLE */
        .table-select {
            appearance: none;
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            background-color: #fafaf9;
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23718355%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 8px top 50%;
            background-size: 9px auto;
        }

        /* TABLE STYLING */
        .history-container { overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
        th, td { border: 1px solid var(--border-color); padding: 10px; text-align: center; }
        th { background: #f8f9fa; font-weight: bold; }
        .row-label { text-align: left; font-weight: bold; width: 280px; background: #fdfdfb; }
        .tagalog-hint { display: block; font-size: 0.65rem; color: #b35a5a; font-style: italic; }

        /* Multiple Child Number Input */
        .multiple-count {
            width: 40px !important;
            margin-top: 5px;
            padding: 4px !important;
            text-align: center;
            display: none; /* Hidden by default */
        }

        .reg-btn {
            background-color: var(--sage-green); color: white; border: none;
            padding: 15px; width: 100%; font-weight: bold; cursor: pointer;
            margin-top: 25px; text-transform: uppercase; border-radius: 4px;
        }

        @media (max-width: 768px) {
            #main { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php 
    include 'user_sidebar.php'; 
?>

<div id="main">
    <div class="form-card">
        <h2>MATERNAL REGISTRATION</h2>
    <<form method="POST" action="admin/save_maternal.php">
            <div class="form-group" style="width: 250px; margin-bottom: 20px;">
                <label>FAMILY SERIAL NUMBER:</label>
                <input type="text" name="family_serial">
            </div>

            <div class="section-header">PATIENT PERSONAL INFORMATION</div>

            <div class="row">
                <div class="form-group" style="flex: 1.5;">
                    <label>LAST NAME (Apelyido):</label>
                    <input type="text" name="client_lname" required>
                </div>
                <div class="form-group" style="flex: 1.5;">
                    <label>FIRST NAME (Pangalan):</label>
                    <input type="text" name="client_fname" required>
                </div>
                <div class="form-group" style="flex: 0.8;">
                    <label>MIDDLE INITIAL:</label>
                    <input type="text" name="client_mi" maxlength="2">
                </div>
                <div class="form-group" style="flex: 0.5;">
                    <label>EXT. (Jr/Sr):</label>
                    <input type="text" name="client_ext">
                </div>
                <div class="form-group">
                    <label>DATE OF BIRTH:</label>
                    <input type="date" name="dob" id="dob">
                </div>
                <div class="form-group" style="flex: 0.3;">
                    <label>AGE:</label>
                    <input type="text" name="age" id="age" readonly>
                </div>
                <div class="form-group">
                    <label>BLOOD TYPE:</label>
                    <select name="blood_type" class="table-select">
                        <option value="">--</option><option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option>
                        <option value="AB-">AB-</option><option value="O+">O+</option><option value="O-">O-</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <label>LAST MENSTRUAL PERIOD:</label>
                    <input type="date" name="lmp">
                </div>
                <div class="form-group">
                    <label>HIGHEST EDUC:</label>
                    <input type="text" name="highest_educ">
                </div>
                <div class="form-group">
                    <label>OCCUPATION:</label>
                    <input type="text" name="occupation">
                </div>
            </div>

            <div class="section-header">ADDRESS & SPOUSE INFORMATION</div>
            
            <div class="row">
                <div class="form-group" style="flex: 1.5;">
                    <label>SPOUSE LAST NAME:</label>
                    <input type="text" name="spouse_lname">
                </div>
                <div class="form-group" style="flex: 1.5;">
                    <label>SPOUSE FIRST NAME:</label>
                    <input type="text" name="spouse_fname">
                </div>
                <div class="form-group" style="flex: 0.5;">
                    <label>MIDDLE INITIAL:</label>
                    <input type="text" name="spouse_mi" maxlength="2">
                </div>
                <div class="form-group" style="flex: 0.5;">
                    <label>EXT. (Jr/Sr):</label>
                    <input type="text" name="spouse_ext">
                </div>
                <div class="form-group">
                    <label>DATE OF BIRTH:</label>
                    <input type="date" name="spouse_dob">
                </div>
                <div class="form-group">
                    <label>BLOOD TYPE:</label>
                    <select name="spouse_blood_type" class="table-select">
                        <option value="">--</option><option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option>
                        <option value="AB-">AB-</option><option value="O+">O+</option><option value="O-">O-</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="form-group" style="flex: 1.5;">
                    <label>ADDRESS: (NUMBER, STREET, PUROK)</label>
                    <input type="text" name="street">
                </div>
                <div class="form-group"><label>BARANGAY:</label><input type="text" name="barangay" value="Alawihao"></div>
                <div class="form-group"><label>MUNICIPALITY:</label><input type="text" name="municipality" value="Daet"></div>
                <div class="form-group"><label>PROVINCE:</label><input type="text" name="province" value="Camarines Norte"></div>
            </div>

            <div class="section-header">HEALTH & SOCIO-ECONOMIC DETAILS</div>

            <div class="row">
                <div class="form-group"><label>AVERAGE MONTHLY INCOME:</label><input type="text" name="income"></div>
                <div class="form-group"><label>CONTACT NUMBER:</label><input type="text" name="contact"></div>
                <div class="form-group"><label>PHIC CAT:</label><input type="text" name="phic_cat"></div>
                <div class="form-group"><label>PHILHEALTH #:</label><input type="text" name="philhealth"></div>
            </div>

            <div class="row">
                <div class="form-group"><label>NO. OF LIVING CHILDREN:</label><input type="number" name="living_children"></div>
                <div class="form-group" style="flex: 2;">
                    <label>BIRTH PLAN (FACILITY):</label>
                    <div style="display: flex; gap: 20px; padding-top: 5px;">
                        <label style="font-weight:normal; font-size:0.8rem;"><input type="radio" name="plan" value="Hospital"> HOSPITAL</label>
                        <label style="font-weight:normal; font-size:0.8rem;"><input type="radio" name="plan" value="RHU"> RHU</label>
                        <label style="font-weight:normal; font-size:0.8rem;"><input type="radio" name="plan" value="Lying-in"> LYING-IN CLINIC</label>
                    </div>
                </div>
            </div>

            <div class="section-header">KARANASAN SA MGA NAUNANG PAGBUBUNTIS AT PANGANGANAK</div>
            <div class="form-group" style="width: 300px; margin-bottom: 15px;">
                <label>ILANG BESES NA NANGANAK: (DROPDOWN)</label>
                <select id="num_preg" name="num_preg" class="table-select">
                    <option value="0">0</option>
                    <?php for($i=1; $i<=6; $i++) echo "<option value='$i'>$i</option>"; ?>
                </select>
            </div>

            <div class="history-container">
                <table>
                    <thead>
                        <tr>
                            <th>FIELD</th>
                            <?php for($i=1; $i<=6; $i++) echo "<th>$i</th>"; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="row-label">DATE OF DELIVERY:</td>
                            <?php for($i=1; $i<=6; $i++) echo "<td><input type='date' name='h_date[]' class='col-$i' disabled style='width:90%; border:1px solid #eaddca;'></td>"; ?>
                        </tr>
                        <tr>
                            <td class="row-label">TYPE OF DELIVERY: <span class="tagalog-hint">(Uri ng Panganganak)</span></td>
                            <?php for($i=1; $i<=6; $i++): ?>
                                <td>
                                    <select name="h_type[]" class="table-select col-<?php echo $i; ?>" disabled>
                                        <option value="">--</option>
                                        <option value="Normal">Normal</option>
                                        <option value="Cesarean">Cesarean</option>
                                    </select>
                                </td>
                            <?php endfor; ?>
                        </tr>
                        <tr>
                            <td class="row-label">BIRTH OUTCOME: <span class="tagalog-hint">(Kinalabasan ng Panganganak)</span></td>
                            <?php for($i=1; $i<=6; $i++): ?>
                                <td>
                                    <select name="h_outcome[]" class="table-select col-<?php echo $i; ?>" disabled>
                                        <option value="">--</option>
                                        <option value="Alive">Alive (Buhay)</option>
                                        <option value="Miscarriage">Miscarriage (Nakunan)</option>
                                        <option value="Stillbirth">Stillbirth</option>
                                    </select>
                                </td>
                            <?php endfor; ?>
                        </tr>
                        <tr>
                            <td class="row-label">NO. OF CHILD DELIVERED: <span class="tagalog-hint">(Bilang ng naipanganak)</span></td>
                            <?php for($i=1; $i<=6; $i++): ?>
                                <td>
                                    <select name="h_child_count[]" class="table-select col-<?php echo $i; ?> child-count-select" data-col="<?php echo $i; ?>" disabled>
                                        <option value="">--</option>
                                        <option value="Single">Single</option>
                                        <option value="Twins">Twins</option>
                                        <option value="Multiple">Multiple</option>
                                    </select>
                                    <input type="number" name="h_multiple_no[]" placeholder="Qty" class="multiple-count count-box-<?php echo $i; ?>" title="Ilan ang nailabas?">
                                </td>
                            <?php endfor; ?>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="reg-btn">Confirm Registration</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Age Calculation
        const dobInput = document.getElementById('dob');
        if (dobInput) {
            dobInput.addEventListener('change', function() {
                const birthDate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                if (today.getMonth() < birthDate.getMonth() || (today.getMonth() === birthDate.getMonth() && today.getDate() < birthDate.getDate())) age--;
                document.getElementById('age').value = age;
            });
        }

        // Dynamic Table Columns
        const numPreg = document.getElementById('num_preg');
        if (numPreg) {
            numPreg.addEventListener('change', function() {
                let val = parseInt(this.value);
                for(let i=1; i<=6; i++) {
                    let inputs = document.querySelectorAll('.col-' + i);
                    let countBox = document.querySelector('.count-box-' + i);
                    
                    inputs.forEach(el => {
                        el.disabled = (i > val);
                        el.style.background = (i > val) ? "#f0f0f0" : "#fff";
                    });
                    
                    if (i > val && countBox) {
                        countBox.style.display = 'none';
                        countBox.value = '';
                    }
                }
            });
        }

        // Show/Hide Multiple Child Qty Box
        document.querySelectorAll('.child-count-select').forEach(select => {
            select.addEventListener('change', function() {
                let colNum = this.getAttribute('data-col');
                let countBox = document.querySelector('.count-box-' + colNum);
                if (countBox) {
                    if (this.value === 'Multiple') {
                        countBox.style.display = 'inline-block';
                    } else {
                        countBox.style.display = 'none';
                        countBox.value = '';
                    }
                }
            });
        });
    });
</script>
</body>
</html>