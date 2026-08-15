<?php
session_start();
include 'db_connect.php';
// if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$name = htmlspecialchars($_SESSION['name'] ?? 'Nanay');
?>
<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Hub | Alawihao Health Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Segoe+UI:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #2d5016;
            --accent: #5a7c3a;
            --light: #8fbf5a;
            --bg: #f4f7f4;
            --white: #ffffff;
            --text: #1a2e0f;
            --muted: #6b7280;
            --blue: #1e5f8e;
            --blue-light: #e8f4fd;
            --orange: #b45309;
            --orange-light: #fef3c7;
            --beige: #f5f2eb;
            --card-border: #e2dcd0;
            --sidebar-width: 260px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); overflow-x: hidden; display: flex; }

        /* SIDEBAR CONTAINER */
        .sidebar-container {
            width: var(--sidebar-width) !important;
            min-width: var(--sidebar-width) !important;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--green);
            color: var(--white);
            transition: transform 0.3s ease;
            z-index: 300;
            overflow-y: auto;
        }

        /* KAPAG NAKA-CLOSE ANG SIDEBAR */
        body.sidebar-closed .sidebar-container {
            transform: translateX(-100%);
        }

        /* MAIN CONTENT WRAPPER */
        #main {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        body.sidebar-closed #main {
            margin-left: 0;
            width: 100%;
        }

        /* TOPBAR - Pinalaki ang padding para mas maging maluwag/makapal */
        .topbar {
            background: var(--white);
            border-bottom: 2px solid var(--card-border);
            padding: 18px 30px;
            display: flex;
            align-items: center;
            gap: 18px;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            width: 100%;
        }

        /* HAMBURGER BUTTON SA TOPBAR */
        .hamburger-btn {
            background: none;
            border: none;
            font-size: 1.4rem;
            color: var(--green);
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 6px;
            transition: background 0.2s;
            display: none;
        }
        .hamburger-btn:hover {
            background: var(--bg);
        }
        body.sidebar-closed .hamburger-btn {
            display: inline-block;
        }

        /* Dynamic margin para sa logo kapag naka-close ang sidebar */
        body.sidebar-closed .topbar img {
            margin-left: 46px; 
            transition: margin 0.3s ease;
        }
        .topbar img {
            transition: margin 0.3s ease;
        }

        .topbar .logo-text { display: flex; flex-direction: column; }
        .topbar .logo-text span { font-size: 0.78rem; color: var(--muted); font-weight: 500; letter-spacing: 0.5px; }
        .topbar .logo-text strong { font-size: 1.15rem; color: var(--green); font-weight: 700; font-family: 'Playfair Display', serif; }

        .container { 
            width: 100%; 
            padding: 24px 30px 0; 
            margin: 0 auto;
        }

        /* WELCOME BANNER */
        .welcome-banner {
            background: var(--white);
            border: 1px solid var(--card-border);
            border-left: 5px solid var(--green);
            border-radius: 16px;
            padding: 24px 28px;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(45,80,22,0.04);
            width: 100%;
        }
        .welcome-banner .text h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: 6px; color: var(--green); font-family: 'Playfair Display', serif; }
        .welcome-banner .text p { font-size: 0.88rem; color: var(--muted); line-height: 1.5; }

        /* SEARCH BAR */
        .search-wrapper {
            position: relative;
            margin-bottom: 28px;
            width: 100%;
        }
        .search-wrapper input {
            width: 100%;
            padding: 14px 20px 14px 48px;
            border-radius: 12px;
            border: 1px solid var(--card-border);
            font-size: 0.95rem;
            background: var(--white);
            color: var(--text);
            outline: none;
            transition: all 0.2s;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .search-wrapper input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(90,124,58,0.1); }
        .search-wrapper .search-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 15px; }
        .no-results { text-align: center; color: var(--muted); padding: 20px; display: none; font-size: 0.9rem; }

        /* SECTION HEADER */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 28px 0 14px;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 8px;
            width: 100%;
        }
        .section-header .left { display: flex; align-items: center; gap: 10px; }
        .section-header h2 { font-size: 1.1rem; font-weight: 700; font-family: 'Playfair Display', serif; }
        .section-header h2.green { color: var(--green); }
        .section-header h2.blue { color: var(--blue); }
        .section-header h2.orange { color: var(--orange); }

        /* SCROLL WRAPPER */
        .scroll-wrapper {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 12px;
            width: 100%;
            scrollbar-width: thin;
            scrollbar-color: var(--light) transparent;
        }
        .scroll-wrapper::-webkit-scrollbar { height: 6px; }
        .scroll-wrapper::-webkit-scrollbar-thumb { background: #d0d7cd; border-radius: 10px; }

        /* CARD */
        .info-card {
            flex: 1 1 240px;
            min-width: 240px;
            max-width: 290px;
            background: var(--white);
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(45, 80, 22, 0.08);
            border: none;
            text-decoration: none;
            display: block;
            position: relative;
            animation: fadeUp 0.4s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .info-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(45, 80, 22, 0.15); }
        .info-card.hidden { display: none; }

        .card-icon {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
            overflow: hidden;
        }
        .card-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .info-card:hover .card-icon img {
            transform: scale(1.05);
        }

        .card-label {
            color: white;
            padding: 12px 10px;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            line-height: 1.3;
        }
        .card-label.green { background: var(--green); }
        .card-label.blue { background: var(--blue); }
        .card-label.orange { background: var(--orange); }

        /* TOOLTIP */
        .tooltip-text {
            visibility: hidden;
            opacity: 0;
            background: #2b2b2b;
            color: white;
            font-size: 0.72rem;
            text-align: center;
            border-radius: 6px;
            padding: 7px 10px;
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            width: 160px;
            z-index: 999;
            transition: opacity 0.2s;
            pointer-events: none;
            line-height: 1.4;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .tooltip-text::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #2b2b2b;
        }
        .info-card:hover .tooltip-text { visibility: visible; opacity: 1; }

        /* FOOTER */
        .site-footer {
            background: var(--white);
            color: var(--text);
            border-top: 1px solid var(--card-border);
            padding: 30px 20px;
            margin-top: 50px;
            text-align: center;
            width: 100%;
        }
        .site-footer h3 { font-size: 0.95rem; font-weight: 700; margin-bottom: 8px; color: var(--green); font-family: 'Playfair Display', serif; }
        .site-footer p { font-size: 0.82rem; color: var(--muted); line-height: 1.8; }
        .site-footer hr { border: none; border-top: 1px solid var(--card-border); margin: 14px auto; width: 40px; }
    </style>
</head>
<body class="sidebar-closed">

<!-- SIDEBAR CONTAINER -->
<div class="sidebar-container">
    <?php include 'user_sidebar.php'; ?>
</div>

<!-- MAIN CONTENT WRAPPER -->
<div id="main">
    <div class="topbar">
        <button class="hamburger-btn" onclick="toggleSidebar()" title="Toggle Sidebar">
            <i class="fa fa-bars"></i>
        </button>

        <!-- Pinalaki ang sukat ng logo (50px) at ginamit ang tamang folder at file name na images/logo.png -->
        <img src="images/logo.png" alt="Brgy Logo" style="width:50px;height:50px;border-radius:50%;object-fit:cover;border:2px solid var(--green);">
        <div class="logo-text">
            <span>Barangay Alawihao Health Center</span>
            <strong>ALAWIHAO HEALTH HUB</strong>
        </div>
    </div>

    <div class="container">

        <!-- WELCOME BANNER -->
        <div class="welcome-banner">
            <div class="text">
                <h1>Kamusta, <?= $name ?>!</h1>
                <p>Maligayang pagdating sa Alawihao Health Center Portal. Piliin ang topic na gusto mong basahin.</p>
            </div>
        </div>

        <!-- SEARCH BAR -->
        <div class="search-wrapper">
            <i class="fa fa-search search-icon"></i>
            <input type="text" id="searchInput" placeholder="Maghanap ng health topic..." oninput="filterCards()">
        </div>
        <p class="no-results" id="noResults">Walang nahanap na topic. Subukan ang ibang keyword.</p>

        <!-- MATERNAL HEALTH -->
        <div class="section-header">
            <div class="left">
                <h2 class="green">Maternal Health</h2>
            </div>
        </div>
        <div class="scroll-wrapper" id="maternal">
            <a class="info-card green" href="health_article.php?topic=warning-signs">
                <span class="tooltip-text">Mga palatandaan na dapat pansinin habang buntis</span>
                <div class="card-icon"><img src="images/pic9.jpg" alt="Mga Babala"></div>
                <div class="card-label green">MGA BABALA HABANG BUNTIS</div>
            </a>
            <a class="info-card green" href="health_article.php?topic=prenatal-checkup">
                <span class="tooltip-text">Schedule at proseso ng pre-natal check-up</span>
                <div class="card-icon"><img src="images/pic10.jpg" alt="Pre-natal"></div>
                <div class="card-label green">PRE-NATAL CHECK-UP</div>
            </a>
            <a class="info-card green" href="health_article.php?topic=pregnancy-dos">
                <span class="tooltip-text">Mga malusog na gawi para sa buntis</span>
                <div class="card-icon"><img src="images/pic11.jpg" alt="Pregnancy Dos"></div>
                <div class="card-label green">PREGNANCY DO'S</div>
            </a>
            <a class="info-card green" href="health_article.php?topic=pregnancy-donts">
                <span class="tooltip-text">Mga dapat iwasan habang buntis</span>
                <div class="card-icon"><img src="images/pic8.jpg" alt="Pregnancy Donts"></div>
                <div class="card-label green">PREGNANCY DON'TS</div>
            </a>
            <a class="info-card green" href="health_article.php?topic=pamahiin">
                <span class="tooltip-text">Totoo o hindi ang mga kilalang pamahiin?</span>
                <div class="card-icon"><img src="images/pic12.webp" alt="Pamahiin"></div>
                <div class="card-label green">SABI-SABI VS. TOTOO</div>
            </a>
            <a class="info-card green" href="health_article.php?topic=baby-growth">
                <span class="tooltip-text">Buwanang paglaki ni baby sa sinapupunan</span>
                <div class="card-icon"><img src="images/pic13.jpg" alt="Baby Growth"></div>
                <div class="card-label green">PAGLAKI NI BABY SA SINAPUPUNAN</div>
            </a>
        </div>

        <!-- NEWBORN CARE -->
        <div class="section-header">
            <div class="left">
                <h2 class="blue">Newborn Care</h2>
            </div>
        </div>
        <div class="scroll-wrapper" id="newborn">
            <a class="info-card blue" href="health_article.php?topic=newborn-care">
                <span class="tooltip-text">Tamang pag-aalaga sa bagong silang na sanggol</span>
                <div class="card-icon"><img src="images/pic6.jpg" alt="Newborn Care"></div>
                <div class="card-label blue">PANGANGALAGA SA BAGONG SILANG</div>
            </a>
            <a class="info-card blue" href="health_article.php?topic=unang-linggo">
                <span class="tooltip-text">Mga pangangailangan ni baby sa unang linggo</span>
                <div class="card-icon"><img src="images/pic3.jpg" alt="Unang Linggo"></div>
                <div class="card-label blue">UNANG LINGGO NG PAGSILANG</div>
            </a>
            <a class="info-card blue" href="health_article.php?topic=breastfeeding">
                <span class="tooltip-text">Benepisyo ng 6 na buwang eksklusibong pagpapasuso</span>
                <div class="card-icon"><img src="images/pic1.jpg" alt="Breastfeeding"></div>
                <div class="card-label blue">EKSKLUSIBONG PAGPAPASUSO</div>
            </a>
            <a class="info-card blue" href="health_article.php?topic=baby-milestones">
                <span class="tooltip-text">Mga bagong kakayahan ni baby sa bawat buwan</span>
                <div class="card-icon"><img src="images/pic7.webp" alt="Milestones"></div>
                <div class="card-label blue">PAGLAKI NG SANGGOL</div>
            </a>
            <a class="info-card blue" href="health_article.php?topic=baby-safety">
                <span class="tooltip-text">Mga gabay para sa kaligtasan ni baby sa tahanan</span>
                <div class="card-icon"><img src="images/pic2.jpg" alt="Baby Safety"></div>
                <div class="card-label blue">KALIGTASAN NI BABY</div>
            </a>
        </div>

        <!-- HEALTH PROGRAMS -->
        <div class="section-header">
            <div class="left">
                <h2 class="orange">Health Programs</h2>
            </div>
        </div>
        <div class="scroll-wrapper" id="programs">
            <a class="info-card orange" href="health_article.php?topic=philhealth">
                <span class="tooltip-text">Mga benepisyo ng PhilHealth para sa ina at sanggol</span>
                <div class="card-icon"><img src="images/pic13.webp" alt="PhilHealth"></div>
                <div class="card-label orange">PHILHEALTH BENEFITS</div>
            </a>
            <a class="info-card orange" href="health_article.php?topic=family-planning">
                <span class="tooltip-text">Iba't ibang pamamaraan ng family planning</span>
                <div class="card-icon"><img src="images/pic14.webp" alt="Family Planning"></div>
                <div class="card-label orange">FAMILY PLANNING</div>
            </a>
        </div>

    </div>

    <div class="site-footer">
        <h3>Barangay Alawihao Health Center</h3>
        <hr>
        <p>
            Alawihao, Daet, Camarines Norte<br>
            Hotline: (054) 000-0000 &nbsp;|&nbsp; alawihaohealth@gmail.com
        </p>
        <p style="margin-top:10px; font-size:0.75rem; opacity:0.6;">(c) <?= date('Y') ?> Alawihao Health Center. All rights reserved.</p>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-closed');
    }

    function filterCards() {
        const query = document.getElementById('searchInput').value.toLowerCase();
        const cards = document.querySelectorAll('.info-card');
        let visibleCount = 0;
        cards.forEach(card => {
            const label = card.querySelector('.card-label').textContent.toLowerCase();
            if (label.includes(query)) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });
        document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
    }
</script>
</body>
</html>