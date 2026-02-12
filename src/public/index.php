<?php
declare(strict_types=1);

require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/auth.php';
require_login();

// user info for the dropdown
$user_email = $_SESSION['email'] ?? $_SESSION['user_email'] ?? 'user@asu.edu';
$parts = explode('@', (string)$user_email);
$asu_id = $parts[0] ?? 'user';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASU ABET Tools | Arizona State University</title>
    <style>
        :root {
            --asu-maroon: #8C1D40;
            --asu-dark-maroon: #5c132a;
            --asu-gold: #FFC627;
            --asu-dark-gold: #eeb211;
            --asu-black: #000000;
            --asu-white: #FFFFFF;
            --gray-bg: #F4F4F4;
            --text-dark: #222222;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .asu-logo {
            height: 34px;
            width: auto;
            margin-right: 12px;
            display: block;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--gray-bg);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background-color: var(--asu-white);
            border-bottom: 4px solid var(--asu-gold);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo-area {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--asu-maroon);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .logo-sub {
            font-weight: 400;
            color: #666;
            font-size: 0.9rem;
            margin-left: 10px;
            border-left: 1px solid #ccc;
            padding-left: 10px;
        }

        .auth-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .auth-link {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .auth-link:hover {
            color: var(--asu-maroon);
        }

        .auth-btn {
            background-color: var(--asu-maroon);
            color: var(--asu-white);
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s, transform 0.2s;
        }

        .auth-btn:hover {
            background-color: var(--asu-dark-maroon);
            transform: translateY(-1px);
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #ffffff;
            min-width: 240px;
            box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2);
            z-index: 1001;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 8px;
            border: 1px solid #e0e0e0;
        }

        .dropdown-info {
            padding: 15px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
            color: var(--text-dark);
        }

        /* CLICKABLE USER NAME + EMAIL */
        .dropdown-user-link {
            display: block;
            text-decoration: none;
            color: inherit;
            border-radius: 4px;
            transition: background-color 0.2s;
            padding: 2px;
        }

        .dropdown-user-link:hover {
            background-color: #f1f1f1;
        }

        .dropdown-user-link strong {
            display: block;
            font-size: 1rem;
            color: var(--asu-maroon);
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .dropdown-user-link span {
            display: block;
            font-size: 0.8rem;
            color: #666;
            margin-top: 2px;
        }

        .dropdown-content a {
            color: var(--text-dark);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
            color: var(--asu-maroon);
        }

        .dropdown-content a.logout-item {
            border-top: 1px solid #eee;
            font-weight: 600;
        }

        .show { display: block; }

        .hero {
            position: relative;
            background: linear-gradient(-45deg, var(--asu-maroon), #5c132a, #741733, var(--asu-maroon));
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: var(--asu-white);
            padding: 5rem 5% 6rem 5%;
            text-align: center;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.15rem;
            max-width: 650px;
            margin: 0 auto;
            opacity: 0.95;
            line-height: 1.8;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: -4rem auto 3rem auto;
            padding: 0 20px;
            z-index: 10;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            align-items: flex-start;
        }

        .tool-card {
            background: var(--asu-white);
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 6px solid var(--asu-maroon);
            display: flex;
            flex-direction: column;
        }

        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }

        .card-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--asu-maroon);
        }

        .toggle-btn {
            background: transparent;
            border: 2px solid var(--asu-gold);
            color: var(--text-dark);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            padding-bottom: 2px;
        }

        .tool-card:hover .toggle-btn {
            background: var(--asu-gold);
        }

        .toggle-btn.active {
            transform: rotate(45deg);
            background: var(--asu-gold);
            border-color: var(--asu-gold);
        }

        .card-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-out;
            background-color: #fafafa;
        }

        .card-body-inner {
            padding: 0 1.5rem 1.5rem 1.5rem;
            color: #555;
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .action-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--asu-maroon);
            font-weight: 700;
            text-decoration: none;
            border-bottom: 2px solid var(--asu-gold);
            padding-bottom: 2px;
            transition: color 0.2s;
        }

        .action-link:hover {
            color: var(--asu-black);
            border-color: var(--asu-maroon);
        }

        footer {
            margin-top: auto;
            background-color: #1a1a1a;
            color: #888;
            padding: 3rem 5%;
            text-align: center;
            font-size: 0.85rem;
            border-top: 4px solid var(--asu-gold);
        }

        footer p {
            margin-bottom: 0.5rem;
        }

        footer p span {
            color: var(--asu-gold);
            font-weight: bold;
        }

        @media (max-width: 600px) {
            .hero h1 { font-size: 2rem; }
            .logo-sub { display: none; }
            header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            .auth-nav {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="logo-wrapper">
        <img src="https://cms.asuonline.asu.edu/sites/g/files/litvpz1971/files/asu-vertical-logo.png" alt="Arizona State University logo" class="asu-logo">
        <span class="logo-area">Arizona State University</span>
        <span class="logo-sub">Enterprise Technology</span>
    </div>

    <div class="auth-nav">
        <?php if (is_logged_in()): ?>
            <div class="dropdown">
                <button type="button" onclick="toggleProfile(event)" class="auth-btn dropbtn" aria-expanded="false" aria-controls="profileDropdown">
                    My Profile
                    <span style="font-size: 0.8em;">&#9662;</span>
                </button>

                <div id="profileDropdown" class="dropdown-content">
                    <div class="dropdown-info">
                        <a href="/account/me/" class="dropdown-user-link" title="View my profile">
                            <strong><?php echo htmlspecialchars((string)$asu_id, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars((string)$user_email, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </div>

                    <a href="/account/profile/">Edit my profile</a>
                    <a href="/account/settings/">Account settings</a>
                    <a href="/account/privacy/">Privacy</a>
                    <a href="/account/help/">Help</a>
                    <a href="/logout" class="logout-item">Log Out</a>
                </div>
            </div>
        <?php else: ?>
            <a href="/login" class="auth-link">Log In</a>
        <?php endif; ?>
    </div>
</header>

<section class="hero">
    <h1>ABET Tools</h1>
    <p>Access the core tools designed to empower the Sun Devil faculty. Select a tool below to learn more about its capabilities.</p>
</section>

<div class="container">
    <div class="tools-grid">

        <div class="tool-card">
            <div class="card-header" onclick="triggerToggle(this)">
                <div class="card-title">Assigment & Grade Data</div>
                <button class="toggle-btn" aria-label="Toggle Description" type="button">+</button>
            </div>
            <div class="card-body">
                <div class="card-body-inner">
                    <p>Manage course performance data efficiently with a clear, structured view of assignments and outcomes-related grading. Ensure records are complete and ready for review when needed.</p>
                    <a href="#" class="action-link">Launch Tool 1 &rarr;</a>
                </div>
            </div>
        </div>

        <div class="tool-card">
            <div class="card-header" onclick="triggerToggle(this)">
                <div class="card-title">Generate Semester Report</div>
                <button class="toggle-btn" aria-label="Toggle Description" type="button">+</button>
            </div>
            <div class="card-body">
                <div class="card-body-inner">
                    <p>Create a polished semester summary that consolidates key course activity and performance indicators. Produce a consistent report suitable for faculty review and departmental records.</p>
                    <a href="#" class="action-link">Launch Tool 2 &rarr;</a>
                </div>
            </div>
        </div>

        <div class="tool-card">
            <div class="card-header" onclick="triggerToggle(this)">
                <div class="card-title">Generate ABET Report</div>
                <button class="toggle-btn" aria-label="Toggle Description" type="button">+</button>
            </div>
            <div class="card-body">
                <div class="card-body-inner">
                    <p>Generate a comprehensive ABET report that summarizes assessment results and supporting materials. Present information clearly to streamline internal review and accreditation preparation.</p>
                    <a href="#" class="action-link">Launch Tool 3 &rarr;</a>
                </div>
            </div>
        </div>

    </div>
</div>

<footer>
    <p>&copy; 2026 Arizona State University. All rights reserved.</p>
    <p>Inspiring <span>Innovation</span> across the globe.</p>
</footer>

<script>
    function toggleProfile(event) {
        if (event) event.stopPropagation();
        const menu = document.getElementById("profileDropdown");
        if (menu) menu.classList.toggle("show");
    }

    window.addEventListener('click', function(event) {
        if (!event.target.closest('.dropdown')) {
            const menu = document.getElementById("profileDropdown");
            if (menu && menu.classList.contains('show')) {
                menu.classList.remove('show');
            }
        }
    });

    function triggerToggle(headerElement) {
        const button = headerElement.querySelector('.toggle-btn');
        const card = headerElement.closest('.tool-card');
        const content = card.querySelector('.card-body');

        const isAlreadyOpen = !!content.style.maxHeight;

        document.querySelectorAll('.card-body').forEach(el => {
            el.style.maxHeight = null;
        });
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        if (!isAlreadyOpen) {
            content.style.maxHeight = content.scrollHeight + "px";
            button.classList.add('active');
        }
    }
</script>
</body>
</html>