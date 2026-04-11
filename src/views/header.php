<?php
// src/views/header.php
// Shared top-of-page markup. Included by the router before every view.
// Pulls auth.php transitively through the router so session helpers work.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SU: Clubs and Organizations</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Lifted directly from SUClubs1.php so v2 matches the admin panel. */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --maroon: #4a1a2e; --maroon-deep: #3a1022; --gold: #c4a535;
            --gold-light: #f5ecc7; --gold-pale: #faf6e6; --bg: #f7f5f0;
            --surface: #ffffff; --ink: #1e1e1e; --ink-light: #6b645d;
            --border: #e4ded5; --danger: #b91c1c; --danger-bg: #fef2f2;
            --success: #15803d; --success-bg: #f0fdf4; --radius: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.05), 0 4px 16px rgba(0,0,0,0.04);
        }
        body { font-family: 'Source Sans 3', sans-serif; background: var(--bg); color: var(--ink); min-height: 100vh; line-height: 1.6; }

        .header { background: var(--maroon); padding: 1.6rem 1rem; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ""; position: absolute; top: -40%; left: -10%; width: 120%; height: 180%; background: radial-gradient(ellipse at 30% 50%, rgba(196,165,53,0.12) 0%, transparent 60%); pointer-events: none; }
        .header-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.15em; color: var(--gold); position: relative; }
        .header h1 { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 700; color: #fff; position: relative; }
        .header p { color: rgba(255,255,255,0.55); font-size: 0.88rem; margin-top: 0.2rem; position: relative; }

        .nav-bar { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0.6rem 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        .nav-links { display: flex; gap: 0.3rem; flex-wrap: wrap; }
        .nav-link { padding: 0.35rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500; color: var(--ink-light); text-decoration: none; transition: all 0.2s; }
        .nav-link:hover { background: var(--gold-pale); color: var(--ink); }
        .nav-link.active { background: var(--maroon); color: #fff; }
        .nav-user { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--ink-light); }
        .role-badge { display: inline-block; padding: 0.1rem 0.5rem; background: var(--gold-pale); color: #8a7520; border: 1px solid var(--gold-light); border-radius: 20px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

        .container { max-width: 980px; margin: 0 auto; padding: 1.5rem 1.25rem 4rem; }

        .banner { padding: 0.7rem 1rem; border-radius: var(--radius); font-size: 0.85rem; font-weight: 500; margin-bottom: 1.2rem; }
        .banner.success { background: var(--success-bg); color: var(--success); border: 1px solid #bbf7d0; }
        .banner.error { background: var(--danger-bg); color: var(--danger); border: 1px solid #fecaca; }

        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.2rem 1.3rem; box-shadow: var(--shadow); margin-bottom: 1.2rem; }
        .card-title { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 600; margin-bottom: 0.8rem; color: var(--maroon); }

        .form-row { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 0.5rem; }
        .form-group { flex: 1; min-width: 110px; }
        label { display: block; font-size: 0.67rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-light); margin-bottom: 0.2rem; }
        input[type="text"], input[type="password"], input[type="email"], select, textarea {
            width: 100%; padding: 0.45rem 0.65rem; border: 1px solid var(--border); border-radius: 5px; font-family: inherit; font-size: 0.88rem; color: var(--ink); background: var(--bg);
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(196,165,53,0.15); }

        .btn { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.45rem 0.9rem; border: none; border-radius: 5px; font-family: inherit; font-size: 0.8rem; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn-primary { background: var(--maroon); color: #fff; }
        .btn-primary:hover { background: var(--maroon-deep); }
        .btn-gold { background: var(--gold); color: #fff; }
        .btn-gold:hover { background: #a88c28; }
        .btn-outline { background: transparent; color: var(--ink-light); border: 1px solid var(--border); }

        .name-cell { font-weight: 600; color: var(--maroon); }
        .type-tag { display: inline-block; padding: 0.1rem 0.45rem; background: var(--gold-pale); color: #8a7520; border: 1px solid var(--gold-light); border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .meta { font-size: 0.78rem; color: var(--ink-light); }

        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-light); padding: 0.5rem 0.6rem; border-bottom: 2px solid var(--border); }
        tbody td { padding: 0.55rem 0.6rem; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
        tbody tr:hover { background: #faf8f4; }

        .footer { text-align: center; padding: 1.5rem 1rem; font-size: 0.75rem; color: var(--ink-light); }
        .footer a { color: var(--maroon); text-decoration: none; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-label">Salisbury University</div>
    <h1>Clubs &amp; Organizations</h1>
    <p>Student organizations portal</p>
</div>

<div class="nav-bar">
    <div class="nav-links">
        <a href="SUClubsApp.php?page=dashboard" class="nav-link">Dashboard</a>
        <?php if (current_role() === "eboard"): ?>
            <a href="SUClubsApp.php?page=finances" class="nav-link">Finances</a>
        <?php endif; ?>
        <?php if (current_role() === "sga_admin"): ?>
            <a href="SUClubsSGA.php" class="nav-link">Admin Panel</a>
        <?php endif; ?>
    </div>
    <div class="nav-user">
        <?php if (is_logged_in()): ?>
            <span><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
            <span class="role-badge"><?php echo htmlspecialchars(current_role()); ?></span>
            <a href="SUClubsApp.php?page=logout" class="nav-link">Logout</a>
        <?php else: ?>
            <a href="SUClubsApp.php?page=login" class="nav-link">Login</a>
            <a href="SUClubsApp.php?page=signup" class="nav-link">Sign Up</a>
        <?php endif; ?>
    </div>
</div>

<div class="container">