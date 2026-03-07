<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/automai/config.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Dashboard — APX AutoMai</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --red:        #E8192C;
            --red-dark:   #B5101E;
            --red-glow:   rgba(232, 25, 44, 0.18);
            --black:      #0A0A0A;
            --surface:    #111111;
            --surface-2:  #1A1A1A;
            --surface-3:  #222222;
            --border:     rgba(255,255,255,0.07);
            --text:       #F0F0F0;
            --text-muted: #888888;
            --success:    #22C55E;
            --warning:    #F59E0B;
            --info:       #3B82F6;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Barlow', sans-serif;
            background: var(--black);
            color: var(--text);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── TOPNAV ─────────────────────────────── */
        .topnav {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 60px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 1000;
            gap: 16px;
        }

        .brand {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: 0.04em;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }
        .brand-apx { color: var(--red); }
        .brand-auto { color: var(--text); }
        .brand-badge {
            background: var(--red);
            color: #fff;
            font-size: 0.55rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            padding: 2px 6px;
            border-radius: 2px;
        }

        #sidebarToggle {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 4px;
            transition: color 0.2s, background 0.2s;
            margin-right: 4px;
        }
        #sidebarToggle:hover { color: var(--red); background: var(--red-glow); }

        .topnav-search {
            flex: 1;
            max-width: 340px;
            margin-left: auto;
            position: relative;
        }
        .topnav-search input {
            width: 100%;
            background: var(--surface-3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 7px 16px 7px 38px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-family: 'Barlow', sans-serif;
            outline: none;
            transition: border-color 0.2s;
        }
        .topnav-search input:focus { border-color: var(--red); }
        .topnav-search input::placeholder { color: var(--text-muted); }
        .topnav-search .search-icon {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .topnav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 16px;
        }
        .icon-btn {
            background: none;
            border: 1px solid var(--border);
            color: var(--text-muted);
            width: 36px; height: 36px;
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            position: relative;
            transition: color 0.2s, border-color 0.2s, background 0.2s;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .icon-btn:hover { color: var(--red); border-color: var(--red); background: var(--red-glow); }
        .notif-dot {
            position: absolute;
            top: 6px; right: 6px;
            width: 7px; height: 7px;
            background: var(--red);
            border-radius: 50%;
            border: 1.5px solid var(--surface);
        }

        .user-chip {
            display: flex; align-items: center; gap: 8px;
            background: var(--surface-3);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 5px 12px 5px 6px;
            cursor: pointer;
            text-decoration: none;
            color: var(--text);
            position: relative;
            transition: border-color 0.2s;
        }
        .user-chip:hover { border-color: var(--red); }
        .user-avatar {
            width: 28px; height: 28px;
            background: var(--red);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.75rem;
            color: #fff;
        }
        .user-name { font-size: 0.82rem; font-weight: 500; }

        /* dropdown */
        .dropdown { position: relative; }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            min-width: 160px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
            z-index: 999;
        }
        .dropdown:hover .dropdown-menu { display: block; }
        .dropdown-menu a {
            display: block;
            padding: 10px 16px;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .dropdown-menu a:hover { background: var(--surface-3); color: var(--text); }
        .dropdown-menu hr { border: none; border-top: 1px solid var(--border); margin: 4px 0; }
        .dropdown-menu .logout { color: var(--red); }

        /* ── LAYOUT SHELL ───────────────────────── */
        .layout {
            display: flex;
            padding-top: 60px;
            min-height: 100vh;
        }

        /* ── SIDEBAR ────────────────────────────── */
        .sidebar {
            width: 240px;
            min-width: 240px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 60px;
            bottom: 0;
            left: 0;
            overflow-y: auto;
            transition: width 0.25s ease, min-width 0.25s ease;
            z-index: 900;
        }
        .sidebar.collapsed { width: 60px; min-width: 60px; }
        .sidebar.collapsed .nav-label,
        .sidebar.collapsed .section-label,
        .sidebar.collapsed .nav-arrow,
        .sidebar.collapsed .sub-nav { display: none; }
        .sidebar.collapsed .nav-link { justify-content: center; padding: 12px; }
        .sidebar.collapsed .sidebar-footer-info { display: none; }

        .sidebar-body { flex: 1; padding: 16px 0; }
        .section-label {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            color: var(--text-muted);
            padding: 16px 20px 6px;
            text-transform: uppercase;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: color 0.2s, background 0.2s, border-color 0.2s;
            cursor: pointer;
            position: relative;
        }
        .nav-link:hover { color: var(--text); background: var(--surface-2); }
        .nav-link.active {
            color: var(--red);
            background: var(--red-glow);
            border-left-color: var(--red);
        }
        .nav-icon { width: 18px; text-align: center; font-size: 0.9rem; flex-shrink: 0; }
        .nav-label { flex: 1; }
        .nav-arrow { font-size: 0.7rem; transition: transform 0.2s; }
        .nav-link.open .nav-arrow { transform: rotate(90deg); }

        .sub-nav { display: none; background: var(--black); }
        .sub-nav.open { display: block; }
        .sub-nav a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px 8px 50px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.83rem;
            transition: color 0.2s, background 0.2s;
        }
        .sub-nav a:hover { color: var(--text); background: var(--surface-2); }
        .sub-nav a::before {
            content: '';
            width: 4px; height: 4px;
            background: var(--text-muted);
            border-radius: 50%;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
        }
        .sidebar-footer-info .label { font-size: 0.7rem; color: var(--text-muted); }
        .sidebar-footer-info .value { font-size: 0.85rem; font-weight: 600; color: var(--text); }

        /* ── MAIN CONTENT ───────────────────────── */
        .main-content {
            flex: 1;
            margin-left: 240px;
            transition: margin-left 0.25s ease;
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 60px);
        }
        .main-content.expanded { margin-left: 60px; }

        main { flex: 1; padding: 28px 32px; }

        /* ── PAGE HEADER ────────────────────────── */
        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        .page-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            line-height: 1;
        }
        .page-title span { color: var(--red); }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 6px;
            list-style: none;
        }
        .breadcrumb li + li::before { content: '/'; margin-right: 6px; opacity: 0.4; }
        .breadcrumb .active { color: var(--red); }
        .page-date { font-size: 0.8rem; color: var(--text-muted); }

        /* ── STAT CARDS ─────────────────────────── */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.25s, transform 0.2s;
        }
        .stat-card:hover { border-color: rgba(232,25,44,0.4); transform: translateY(-2px); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .stat-card.primary::before { background: var(--red); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.success::before { background: var(--success); }
        .stat-card.info::before { background: var(--info); }

        .stat-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
        }
        .stat-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
        }
        .stat-card.primary .stat-icon { background: var(--red-glow); color: var(--red); }
        .stat-card.warning .stat-icon { background: rgba(245,158,11,0.15); color: var(--warning); }
        .stat-card.success .stat-icon { background: rgba(34,197,94,0.15); color: var(--success); }
        .stat-card.info .stat-icon { background: rgba(59,130,246,0.15); color: var(--info); }

        .stat-value {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.1rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
        }
        .stat-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-footer a {
            font-size: 0.78rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }
        .stat-footer a:hover { color: var(--red); }
        .stat-change {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .stat-change.up { background: rgba(34,197,94,0.15); color: var(--success); }
        .stat-change.down { background: rgba(232,25,44,0.15); color: var(--red); }

        /* ── CHARTS ─────────────────────────────── */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }
        .chart-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
        }
        .card-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .card-header-title i { color: var(--red); font-size: 0.9rem; }
        .card-body { padding: 20px; }
        .card-badge {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            background: var(--surface-3);
            color: var(--text-muted);
        }

        /* ── TABLE CARD ─────────────────────────── */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 28px;
        }

        /* override simple-datatables styles */
        .dataTable-wrapper { padding: 0; }
        .dataTable-container { overflow-x: auto; }
        table#datatablesSimple {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        table#datatablesSimple thead tr {
            background: var(--surface-2);
            border-bottom: 1px solid var(--border);
        }
        table#datatablesSimple thead th {
            padding: 12px 16px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-muted);
            text-align: left;
            white-space: nowrap;
        }
        table#datatablesSimple tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }
        table#datatablesSimple tbody tr:last-child { border-bottom: none; }
        table#datatablesSimple tbody tr:hover { background: var(--surface-2); }
        table#datatablesSimple tbody td {
            padding: 13px 16px;
            color: var(--text-muted);
        }
        table#datatablesSimple tbody td:first-child {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 0.92rem;
            color: var(--text);
            letter-spacing: 0.04em;
        }
        .dataTable-bottom {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border);
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .dataTable-info { font-size: 0.8rem; color: var(--text-muted); }
        .dataTable-pagination li a {
            background: var(--surface-3);
            border: 1px solid var(--border);
            color: var(--text-muted);
            border-radius: 4px;
            padding: 4px 10px;
        }
        .dataTable-pagination li.active a { background: var(--red); border-color: var(--red); color: #fff; }

        /* STATUS BADGES */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.73rem;
            font-weight: 600;
            letter-spacing: 0.04em;
        }
        .badge::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .badge-confirmed { background: rgba(34,197,94,0.12); color: var(--success); }
        .badge-confirmed::before { background: var(--success); }
        .badge-pending { background: rgba(245,158,11,0.12); color: var(--warning); }
        .badge-pending::before { background: var(--warning); }
        .badge-cancelled { background: rgba(232,25,44,0.12); color: var(--red); }
        .badge-cancelled::before { background: var(--red); }
        .badge-inprogress { background: rgba(59,130,246,0.12); color: var(--info); }
        .badge-inprogress::before { background: var(--info); }

        /* ── FOOTER ─────────────────────────────── */
        footer {
            padding: 16px 32px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            color: var(--text-muted);
        }
        footer a { color: var(--text-muted); text-decoration: none; }
        footer a:hover { color: var(--red); }
        .footer-brand { display: flex; align-items: center; gap: 6px; }
        .footer-brand span { color: var(--red); font-weight: 700; }

        /* ── DIVIDER ─────────────────────────────── */
        .sidebar-divider { border: none; border-top: 1px solid var(--border); margin: 8px 0; }

        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--black); }
        ::-webkit-scrollbar-thumb { background: var(--surface-3); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--red); }

        /* Responsive */
        @media (max-width: 1100px) {
            .cards-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 60px; min-width: 60px; }
            .sidebar .nav-label, .sidebar .section-label, .sidebar .nav-arrow, .sidebar .sub-nav, .sidebar .sidebar-footer-info { display: none; }
            .sidebar .nav-link { justify-content: center; padding: 12px; }
            .main-content { margin-left: 60px; }
            main { padding: 20px 16px; }
        }
    </style>
</head>
<body>

<!-- TOP NAV -->
<nav class="topnav">
    <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <a class="brand" href="<?= BASE_URL ?>/public/index.php">
        <span class="brand-apx">APX</span>
        <span class="brand-auto">AUTOMAI</span>
        <span class="brand-badge">ADMIN</span>
    </a>
    <div class="topnav-search">
        <i class="fas fa-search search-icon"></i>
        <input type="text" placeholder="Search bookings, customers..." />
    </div>
    <div class="topnav-actions">
        <a href="#!" class="icon-btn" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="notif-dot"></span>
        </a>
        <a href="#!" class="icon-btn" title="Activity Log">
            <i class="fas fa-clock-rotate-left"></i>
        </a>
        <div class="dropdown">
            <a class="user-chip" href="#!">
                <div class="user-avatar">AD</div>
                <span class="user-name">Admin</span>
                <i class="fas fa-chevron-down" style="font-size:0.65rem;color:var(--text-muted);margin-left:4px;"></i>
            </a>
            <div class="dropdown-menu">
                <a href="#!"><i class="fas fa-user-cog" style="width:16px;margin-right:8px;"></i>Settings</a>
                <a href="#!"><i class="fas fa-history" style="width:16px;margin-right:8px;"></i>Activity Log</a>
                <hr />
                <a href="<?= BASE_URL ?>/auth/login.php" class="logout"><i class="fas fa-right-from-bracket" style="width:16px;margin-right:8px;"></i>Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- LAYOUT -->
<div class="layout">

    <!-- SIDEBAR -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-body">
            <div class="section-label">Main</div>

            <a class="nav-link active" href="admin-dashboard.php">
                <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
                <span class="nav-label">Dashboard</span>
            </a>

            <hr class="sidebar-divider" />
            <div class="section-label">Operations</div>

            <a class="nav-link" href="#" onclick="toggleSub(event,'sub-bookings',this)">
                <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
                <span class="nav-label">Bookings</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </a>
            <div class="sub-nav" id="sub-bookings">
                <a href="<?= BASE_URL ?>/bookings/index.php">All Bookings</a>
                <a href="<?= BASE_URL ?>/bookings/new.php">New Booking</a>
                <a href="<?= BASE_URL ?>/bookings/schedule.php">Today's Schedule</a>
                <a href="<?= BASE_URL ?>/bookings/cancelled.php">Cancelled</a>
            </div>

            <a class="nav-link" href="#" onclick="toggleSub(event,'sub-customers',this)">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                <span class="nav-label">Customers</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </a>
            <div class="sub-nav" id="sub-customers">
                <a href="<?= BASE_URL ?>/customers/index.php">All Customers</a>
                <a href="<?= BASE_URL ?>/customers/new.php">Add Customer</a>
                <a href="<?= BASE_URL ?>/customers/loyalty.php">Loyalty Members</a>
            </div>

            <hr class="sidebar-divider" />
            <div class="section-label">Insights</div>

            <a class="nav-link" href="#" onclick="toggleSub(event,'sub-reports',this)">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                <span class="nav-label">Reports</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </a>
            <div class="sub-nav" id="sub-reports">
                <a href="#">Revenue Report</a>
                <a href="#">Booking Summary</a>
                <a href="#">Staff Performance</a>
            </div>

            <hr class="sidebar-divider" />
            <div class="section-label">System</div>

            <a class="nav-link" href="#" onclick="toggleSub(event,'sub-settings',this)">
                <span class="nav-icon"><i class="fas fa-gear"></i></span>
                <span class="nav-label">Settings</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </a>
            <div class="sub-nav" id="sub-settings">
                <a href="#">General</a>
                <a href="#">Staff Accounts</a>
                <a href="#">Services & Pricing</a>
                <a href="#">Notifications</a>
            </div>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-footer-info">
                <div class="label">Logged in as</div>
                <div class="value">Administrator</div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <main>
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <h1 class="page-title"><?php echo "<span>APX</span> AUTOMAI"; ?></h1>
                    <ol class="breadcrumb">
                        <li>Admin</li>
                        <li class="active"><?php echo "Dashboard"; ?></li>
                    </ol>
                </div>
                <div class="page-date">
                    <i class="far fa-calendar" style="margin-right:6px;color:var(--red);"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>

            <!-- STAT CARDS -->
            <div class="cards-grid">
                <div class="stat-card primary">
                    <div class="stat-card-header">
                        <div class="stat-label">Today's Bookings</div>
                        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    </div>
                    <div class="stat-value">24</div>
                    <div class="stat-footer">
                        <a href="#">View Details <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        <span class="stat-change up">+8%</span>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-card-header">
                        <div class="stat-label">Pending</div>
                        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                    <div class="stat-value">7</div>
                    <div class="stat-footer">
                        <a href="#">View Details <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        <span class="stat-change down">+2</span>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-card-header">
                        <div class="stat-label">This Week</div>
                        <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                    </div>
                    <div class="stat-value">138</div>
                    <div class="stat-footer">
                        <a href="#">View Details <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        <span class="stat-change up">+14%</span>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-card-header">
                        <div class="stat-label">Completed</div>
                        <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                    </div>
                    <div class="stat-value">512</div>
                    <div class="stat-footer">
                        <a href="#">View Details <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        <span class="stat-change up">+22%</span>
                    </div>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-chart-area"></i> Bookings Over Time</div>
                        <span class="card-badge">Last 7 days</span>
                    </div>
                    <div class="card-body">
                        <canvas id="myAreaChart" width="100%" height="50"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-chart-bar"></i> Revenue by Service</div>
                        <span class="card-badge">This month</span>
                    </div>
                    <div class="card-body">
                        <canvas id="myBarChart" width="100%" height="50"></canvas>
                    </div>
                </div>
            </div>

            <!-- BOOKINGS TABLE -->
            <div class="table-card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-table-list"></i> Recent Bookings</div>
                    <a href="#" style="font-size:0.8rem;color:var(--red);text-decoration:none;">View All <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                </div>
                <div class="card-body" style="padding:0;">
                    <table id="datatablesSimple">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Service Type</th>
                                <th>Date</th>
                                <th>Assigned Staff</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Service Type</th>
                                <th>Date</th>
                                <th>Assigned Staff</th>
                                <th>Status</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <td>#BK-0041</td><td>Juan dela Cruz</td><td>Full Car Wash</td><td>2024-01-15</td><td>Marco R.</td>
                                <td><span class="badge badge-confirmed">Confirmed</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0040</td><td>Maria Santos</td><td>Oil Change</td><td>2024-01-15</td><td>Leo T.</td>
                                <td><span class="badge badge-inprogress">In Progress</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0039</td><td>Roberto Lim</td><td>Paint Protection</td><td>2024-01-14</td><td>Marco R.</td>
                                <td><span class="badge badge-confirmed">Confirmed</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0038</td><td>Ana Reyes</td><td>Interior Detailing</td><td>2024-01-14</td><td>Jay M.</td>
                                <td><span class="badge badge-pending">Pending</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0037</td><td>Carlo Mendoza</td><td>Tire Rotation</td><td>2024-01-13</td><td>Leo T.</td>
                                <td><span class="badge badge-confirmed">Confirmed</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0036</td><td>Lisa Tan</td><td>Engine Check</td><td>2024-01-13</td><td>Jay M.</td>
                                <td><span class="badge badge-cancelled">Cancelled</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0035</td><td>Paulo Garcia</td><td>Full Car Wash</td><td>2024-01-12</td><td>Marco R.</td>
                                <td><span class="badge badge-confirmed">Confirmed</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0034</td><td>Diane Uy</td><td>Oil Change</td><td>2024-01-12</td><td>Leo T.</td>
                                <td><span class="badge badge-confirmed">Confirmed</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0033</td><td>Ben Cruz</td><td>Paint Protection</td><td>2024-01-11</td><td>Jay M.</td>
                                <td><span class="badge badge-pending">Pending</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0032</td><td>Nina Flores</td><td>Interior Detailing</td><td>2024-01-11</td><td>Marco R.</td>
                                <td><span class="badge badge-confirmed">Confirmed</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0031</td><td>Kevin Sy</td><td>Tire Rotation</td><td>2024-01-10</td><td>Leo T.</td>
                                <td><span class="badge badge-confirmed">Confirmed</span></td>
                            </tr>
                            <tr>
                                <td>#BK-0030</td><td>Rose Villanueva</td><td>Engine Check</td><td>2024-01-10</td><td>Jay M.</td>
                                <td><span class="badge badge-inprogress">In Progress</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- FOOTER -->
        <footer>
            <div class="footer-brand">
                <span>APX</span> AutoMai &mdash; Admin Portal &copy; <?php echo date('Y'); ?>
            </div>
            <div>
                <a href="#">Privacy Policy</a>
                &nbsp;&middot;&nbsp;
                <a href="#">Terms &amp; Conditions</a>
            </div>
        </footer>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script>
    // Sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    document.getElementById('sidebarToggle').addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    });

    // Sub-nav toggle
    function toggleSub(e, id, link) {
        e.preventDefault();
        const sub = document.getElementById(id);
        const isOpen = sub.classList.contains('open');
        // close all
        document.querySelectorAll('.sub-nav').forEach(s => s.classList.remove('open'));
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('open'));
        if (!isOpen) {
            sub.classList.add('open');
            link.classList.add('open');
        }
    }

    // DataTable
    if (document.getElementById('datatablesSimple')) {
        const dt = new simpleDatatables.DataTable('#datatablesSimple', {
            searchable: true,
            fixedHeight: false,
            perPage: 8,
            labels: {
                placeholder: 'Search bookings...',
                perPage: '{select} per page',
                noRows: 'No bookings found',
                info: 'Showing {start} to {end} of {rows} bookings',
            }
        });
    }

    // Chart defaults
    Chart.defaults.global.defaultFontColor = '#888';
    Chart.defaults.global.defaultFontFamily = "'Barlow', sans-serif";

    // Area Chart
    const areaCtx = document.getElementById('myAreaChart').getContext('2d');
    const areaGrad = areaCtx.createLinearGradient(0, 0, 0, 200);
    areaGrad.addColorStop(0, 'rgba(232,25,44,0.35)');
    areaGrad.addColorStop(1, 'rgba(232,25,44,0.0)');
    new Chart(areaCtx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Bookings',
                data: [18, 22, 17, 28, 24, 35, 30],
                borderColor: '#E8192C',
                backgroundColor: areaGrad,
                borderWidth: 2,
                pointBackgroundColor: '#E8192C',
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                lineTension: 0.4,
            }]
        },
        options: {
            responsive: true,
            legend: { display: false },
            scales: {
                xAxes: [{ gridLines: { color: 'rgba(255,255,255,0.05)', zeroLineColor: 'rgba(255,255,255,0.05)' }, ticks: { fontColor: '#666' } }],
                yAxes: [{ gridLines: { color: 'rgba(255,255,255,0.05)', zeroLineColor: 'rgba(255,255,255,0.05)' }, ticks: { fontColor: '#666', beginAtZero: true } }]
            }
        }
    });

    // Bar Chart
    const barCtx = document.getElementById('myBarChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: ['Car Wash', 'Oil Change', 'Detailing', 'Paint', 'Tires', 'Engine'],
            datasets: [{
                label: 'Revenue (₱)',
                data: [42000, 35000, 58000, 21000, 18000, 29000],
                backgroundColor: ['#E8192C','#B5101E','#E8192C','#B5101E','#E8192C','#B5101E'],
                borderRadius: 4,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            legend: { display: false },
            scales: {
                xAxes: [{ gridLines: { display: false }, ticks: { fontColor: '#666' } }],
                yAxes: [{ gridLines: { color: 'rgba(255,255,255,0.05)', zeroLineColor: 'rgba(255,255,255,0.05)' }, ticks: { fontColor: '#666', beginAtZero: true, callback: v => '₱' + (v/1000).toFixed(0) + 'k' } }]
            }
        }
    });
</script>
</body>
</html>