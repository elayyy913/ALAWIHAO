<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$mommy_name = $_SESSION['name'] ?? "Mikaela";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Hub | Alawihao</title>
    <style>
        :root {
            --primary-green: #718355; 
            --accent-green: #a3c981;
            --bg-color: #f5f5dc; 
        }

        body { 
            font-family: 'Times New Roman', serif; 
            background-color: var(--bg-color); 
            margin: 0; 
            overflow-x: hidden; 
        }

        /* --- THE FIX: MAIN CONTENT WRAPPER --- */
        #main {
            transition: margin-left .5s; /* Ito ang magtutulak sa content */
            width: 100%;
            min-height: 100vh;
            display: block;
        }

        .header { 
            background: white; 
            padding: 15px 30px; 
            display: flex; 
            align-items: center; 
            border-bottom: 3px solid var(--primary-green); 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
        }

        .hamburger { 
            font-size: 28px; 
            cursor: pointer; 
            color: var(--primary-green); 
            margin-right: 20px; 
        }
        
        .container { padding: 40px; }
        .section-title { 
            color: var(--primary-green); 
            border-left: 6px solid var(--primary-green); 
            padding-left: 15px; 
            margin: 40px 0 20px 0; 
            font-size: 1.5rem; 
        }

        /* --- SCROLLING CARDS --- */
        .scroll-wrapper { display: flex; overflow-x: auto; gap: 20px; padding-bottom: 25px; }
        .info-card { min-width: 250px; background: white; border-radius: 20px; overflow: hidden; cursor: pointer; transition: 0.3s; border: 1px solid #eee; }
        .info-card:hover { transform: translateY(-8px); box-shadow: 0 12px 25px rgba(0,0,0,0.1); }
        .card-img { height: 150px; background: #e9edc9; display: flex; align-items: center; justify-content: center; font-size: 55px; }
        .card-footer { background: var(--primary-green); color: white; padding: 15px; text-align: center; font-weight: bold; font-size: 14px; }

        /* --- MODAL --- */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 4000; justify-content: center; align-items: center; backdrop-filter: blur(5px); }
        .modal-content { background: white; padding: 40px; border-radius: 25px; width: 90%; max-width: 600px; position: relative; }
        .info-group { margin-bottom: 20px; }
        .info-group h4 { color: var(--primary-green); margin-bottom: 5px; border-bottom: 1px solid #eee; }

        /* Mobile Fix */
        @media (max-width: 768px) {
            #main { margin-left: 0 !important; }
        }
    </style>
</head>
<body>

<?php include 'user_sidebar.php'; ?>

<div id="main">
    <div class="header">
        <span class="hamburger" onclick="openNav()">☰</span>
        <h3 style="margin:0; color: var(--primary-green);">HEALTH HUB</h3>
    </div>

    <div class="container">
        <h1 style="color: var(--primary-green);">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
        <p><strong>Education First:</strong> Providing care for mothers and children.</p>

        <h2 class="section-title">Maternal Health</h2>
        <div class="scroll-wrapper">
            <div class="info-card" onclick="openPop('Tetanus Toxoid', 'Maternal Protection', 'Vaccine to prevent infection during birth.', 'Protects both mother and baby.', '2 doses during pregnancy.')">
                <div class="card-img">🤱</div>
                <div class="card-footer">MATERNAL VACCINE</div>
            </div>
            </div>
    </div>
</div>

<div id="infoModal" class="modal">
    <div class="modal-content">
        <span onclick="closePop()" style="position:absolute; top:20px; right:20px; font-size:30px; cursor:pointer;">&times;</span>
        <h2 id="popTitle" style="color: var(--primary-green); margin-top:0;"></h2>
        <p id="popSub" style="color: var(--accent-green); font-weight:bold;"></p>
        <hr>
        <div class="info-group"><h4>What is it?</h4><p id="popWhat"></p></div>
        <div class="info-group"><h4>Why is it important?</h4><p id="popWhy"></p></div>
        <div class="info-group"><h4>When is it given?</h4><p id="popWhen"></p></div>
    </div>
</div>

<script>
    function openPop(title, sub, what, why, when) {
        document.getElementById('popTitle').innerText = title;
        document.getElementById('popSub').innerText = sub;
        document.getElementById('popWhat').innerText = what;
        document.getElementById('popWhy').innerText = why;
        document.getElementById('popWhen').innerText = when;
        document.getElementById('infoModal').style.display = "flex";
    }
    function closePop() { document.getElementById('infoModal').style.display = "none"; }
</script>

</body>
</html>