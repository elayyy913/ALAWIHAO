<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = ($_GET['status'] ?? '') === 'success'
    ? 'Registration submitted successfully. Your maternal record is now pending review.'
    : '';
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
            width: calc(100% - var(--sidebar-width));
            padding: 20px;
            box-sizing: border-box;
            transition: margin-left 0.3s ease-in-out, width 0.3s ease-in-out;
        }

        /* Kapag naka-close/collapse ang sidebar */
        body.sidebar-closed #main {
            margin-left: 0 !important;
            width: 100% !important;
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
            font-size: 0.75rem;
            font-weight: bold;
            margin-bottom: 6px;
            color: #333;
            letter-spacing: 0.02em;
        }

        .form-group input, .form-group select {
            padding: 9px 10px;
            border: 1px solid #d7dfce;
            border-radius: 8px;
            font-size: 0.88rem;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbf7 100%);
            color: #1f2b1d;
            transition: all 0.22s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .form-group input:hover, .form-group select:hover,
        .form-group input:focus, .form-group select:focus {
            border-color: var(--sage-green);
            background: linear-gradient(180deg, #ffffff 0%, #f3f8ea 100%);
            box-shadow: 0 0 0 3px rgba(113, 131, 85, 0.12), 0 5px 16px rgba(113, 131, 85, 0.12);
            transform: translateY(-1px);
            outline: none;
        }

        .form-group input:focus, .form-group select:focus {
            border-width: 1.6px;
        }

        .phone-field {
            display: flex;
            align-items: center;
            width: 100%;
            border: 1px solid #d7dfce;
            border-radius: 8px;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbf7 100%);
            transition: all 0.22s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .phone-field:focus-within {
            border-color: var(--sage-green);
            box-shadow: 0 0 0 3px rgba(113, 131, 85, 0.12);
        }

        .phone-country-select {
            width: 110px;
            min-width: 110px;
            padding: 9px 26px 9px 10px;
            border: none;
            border-right: 1px solid #d7dfce;
            background: #fff;
            color: #1f2b1d;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M2 4l4 4 4-4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 10px;
            appearance: none;
        }

        .phone-country-select:focus {
            outline: none;
        }

        .phone-input {
            flex: 1;
            min-width: 0;
            border: none;
            background: transparent;
            padding: 9px 10px;
            font-size: 0.88rem;
            color: #1f2b1d;
        }

        .phone-input:focus {
            outline: none;
        }

        .field-hint {
            margin-top: 4px;
            color: #6b7280;
            font-size: 0.68rem;
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

        .form-notification {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
            padding: 14px 16px;
            color: #245b2a;
            background: #eef8ef;
            border: 1px solid #b9dfbd;
            border-left: 4px solid #3f8f4a;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 0.85rem;
            line-height: 1.45;
        }

        .form-notification i {
            color: #3f8f4a;
            font-size: 1rem;
            margin-top: 2px;
        }

        #hamburgerBtn.hamburger-btn {
            color: #2d5016 !important;
        }

        @media (max-width: 768px) {
    #main {
        margin-left: 0;
        width: 100%;
    }
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

        <?php if ($success_message): ?>
            <div class="form-notification" role="status">
                <i class="fa fa-circle-check" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- FIX 1: Ayusin ang form tag mula sa '<<form' patungong '<form' -->
        <form method="POST" action="admin/save_maternal.php">
            
            <!-- FIX 2: I-secure na mapapasa ang status na 'Pending' papunta sa backend processing -->
            <input type="hidden" name="status" value="Pending">

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
                <div class="form-group">
                    <label>CONTACT NUMBER:</label>
                    <div class="phone-field">
                        <select id="countrySelect" class="phone-country-select" name="contact_country" aria-label="Choose country">
                            <option value="PH" data-flag="🇵🇭" data-code="+63" data-pattern="^09\\d{9}$" selected>🇵🇭 +63</option>
                            <option value="US" data-flag="🇺🇸" data-code="+1" data-pattern="^\\d{10}$">🇺🇸 +1</option>
                            <option value="UK" data-flag="🇬🇧" data-code="+44" data-pattern="^\\d{10,11}$">🇬🇧 +44</option>
                            <option value="AU" data-flag="🇦🇺" data-code="+61" data-pattern="^\\d{9}$">🇦🇺 +61</option>
                        </select>
                        <input id="contactInput" class="phone-input" type="tel" name="contact" inputmode="numeric" maxlength="11" pattern="^09\d{9}$" placeholder="09123456789" title="Enter a valid Philippine mobile number (11 digits starting with 09)" required>
                    </div>
                    <small class="field-hint" id="contactHint">Philippine format: 09XXXXXXXXX</small>
                </div>
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

        const countrySelect = document.getElementById('countrySelect');
        const contactInput = document.getElementById('contactInput');
        const contactHint = document.getElementById('contactHint');

        if (countrySelect && contactInput) {
            const countryRules = {
                PH: { maxLength: 11, pattern: /^09\d{9}$/, hint: 'Philippine format: 09XXXXXXXXX', placeholder: 'e.g. 09123456789' },
                US: { maxLength: 10, pattern: /^\d{10}$/, hint: 'US format: 10-digit number', placeholder: 'e.g. 5551234567' },
                UK: { maxLength: 11, pattern: /^\d{10,11}$/, hint: 'UK format: 10-11 digits', placeholder: 'e.g. 7123456789' },
                AU: { maxLength: 9, pattern: /^\d{9}$/, hint: 'Australia format: 9-digit number', placeholder: 'e.g. 412345678' }
            };

            function applyCountryRules() {
                const country = countrySelect.value;
                const rule = countryRules[country] || countryRules.PH;

                contactInput.maxLength = rule.maxLength;
                contactInput.placeholder = rule.placeholder;
                contactInput.setAttribute('pattern', rule.pattern.source);
                contactInput.setAttribute('title', 'Enter a valid ' + country + ' phone number.');
                contactHint.textContent = rule.hint;

                contactInput.value = contactInput.value.replace(/\D/g, '').slice(0, rule.maxLength);
                contactInput.setCustomValidity('');
            }

            countrySelect.addEventListener('change', applyCountryRules);

            contactInput.addEventListener('input', function() {
                const country = countrySelect.value;
                const rule = countryRules[country] || countryRules.PH;
                let digits = this.value.replace(/\D/g, '');

                if (country === 'PH') {
                    if (digits.startsWith('63')) digits = digits.slice(2);
                    if (!digits.startsWith('0')) digits = digits.replace(/^/, '');
                    digits = digits.slice(0, rule.maxLength);
                } else {
                    digits = digits.slice(0, rule.maxLength);
                }

                this.value = digits;
            });

            contactInput.addEventListener('blur', function() {
                const country = countrySelect.value;
                const rule = countryRules[country] || countryRules.PH;
                const trimmed = this.value.trim();

                if (trimmed && !rule.pattern.test(trimmed)) {
                    this.setCustomValidity('Please enter a valid ' + country + ' phone number.');
                } else {
                    this.setCustomValidity('');
                }
            });

            applyCountryRules();
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