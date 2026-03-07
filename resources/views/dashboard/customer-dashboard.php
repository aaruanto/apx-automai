
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>My Dashboard — APX AutoMai</title>
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

        /* ── TOPNAV ── */
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
        .brand-apx  { color: var(--red); }
        .brand-auto { color: var(--text); }
        .brand-badge {
            background: var(--surface-3);
            color: var(--text-muted);
            border: 1px solid var(--border);
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
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.75rem;
            color: var(--text);
        }
        .user-name { font-size: 0.82rem; font-weight: 500; }

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

        /* ── LAYOUT ── */
        .layout {
            display: flex;
            padding-top: 60px;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
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

        .sidebar-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 8px 20px;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
        }
        .sidebar-footer-info .label { font-size: 0.7rem; color: var(--text-muted); }
        .sidebar-footer-info .value { font-size: 0.85rem; font-weight: 600; color: var(--text); }

        /* ── MAIN CONTENT ── */
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

        /* ── PAGE HEADER ── */
        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 12px;
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

        /* ── WELCOME BANNER ── */
        .welcome-banner {
            background: linear-gradient(135deg, var(--surface) 0%, var(--surface-2) 100%);
            border: 1px solid var(--border);
            border-left: 3px solid var(--red);
            border-radius: 10px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .welcome-text h2 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .welcome-text h2 span { color: var(--red); }
        .welcome-text p { font-size: 0.83rem; color: var(--text-muted); }
        .btn-book {
            background: var(--red);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 7px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        }
        .btn-book:hover {
            background: var(--red-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(232,25,44,0.35);
        }

        /* ── STAT CARDS ── */
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
        .stat-card.info::before    { background: var(--info); }

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
        .stat-card.primary .stat-icon { background: var(--red-glow);                    color: var(--red); }
        .stat-card.warning .stat-icon { background: rgba(245,158,11,0.15);               color: var(--warning); }
        .stat-card.success .stat-icon { background: rgba(34,197,94,0.15);                color: var(--success); }
        .stat-card.info    .stat-icon { background: rgba(59,130,246,0.15);               color: var(--info); }

        .stat-value {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.1rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 10px;
        }
        .stat-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
        }
        .stat-footer a { color: var(--text-muted); text-decoration: none; transition: color 0.2s; }
        .stat-footer a:hover { color: var(--red); }

        /* ── CONTENT GRID ── */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            margin-bottom: 28px;
        }

        /* ── CARDS ── */
        .card {
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
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-header-title i { color: var(--red); }
        .card-badge {
            background: var(--surface-3);
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .card-body { padding: 20px; }

        /* ── TABLE ── */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 28px;
        }

        /* simple-datatables dark override */
        .dataTable-wrapper { color: var(--text); }
        .dataTable-container { border: none !important; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        thead th {
            background: var(--surface-2);
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }
        tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--surface-2); }
        tbody td { padding: 12px 16px; color: var(--text); vertical-align: middle; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .badge-confirmed  { background: rgba(34,197,94,0.15);  color: #22C55E; }
        .badge-pending    { background: rgba(245,158,11,0.15);  color: #F59E0B; }
        .badge-inprogress { background: rgba(59,130,246,0.15);  color: #3B82F6; }
        .badge-cancelled  { background: rgba(232,25,44,0.15);   color: #E8192C; }
        .badge-completed  { background: rgba(255,255,255,0.07); color: var(--text-muted); }

        .dataTable-pagination a,
        .dataTable-selector,
        .dataTable-input {
            background: var(--surface-3) !important;
            border: 1px solid var(--border) !important;
            color: var(--text) !important;
            border-radius: 5px !important;
        }
        .dataTable-pagination li.active a { background: var(--red) !important; border-color: var(--red) !important; color: #fff !important; }

        /* ── UPCOMING BOOKING ITEM ── */
        .booking-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }
        .booking-item:last-child { border-bottom: none; padding-bottom: 0; }
        .booking-item:first-child { padding-top: 0; }
        .booking-date-box {
            min-width: 46px;
            height: 46px;
            background: var(--surface-3);
            border: 1px solid var(--border);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .booking-date-box .day {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--red);
            line-height: 1;
        }
        .booking-date-box .mon {
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .booking-info { flex: 1; }
        .booking-service {
            font-weight: 600;
            font-size: 0.88rem;
            margin-bottom: 2px;
        }
        .booking-meta {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .booking-meta i { color: var(--red); margin-right: 3px; }

        /* ── LOYALTY CARD ── */
        .loyalty-card {
            background: linear-gradient(135deg, #1a0a0b 0%, #2a0e10 50%, #1a0a0b 100%);
            border: 1px solid rgba(232,25,44,0.3);
            border-radius: 10px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .loyalty-card::before {
            content: 'APX';
            position: absolute;
            right: -10px; top: -10px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 5rem;
            font-weight: 800;
            color: rgba(232,25,44,0.06);
            line-height: 1;
            pointer-events: none;
        }
        .loyalty-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: 4px;
        }
        .loyalty-points {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1;
            margin-bottom: 2px;
        }
        .loyalty-sub {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 16px;
        }
        .loyalty-bar-wrap {
            background: rgba(255,255,255,0.06);
            border-radius: 4px;
            height: 6px;
            margin-bottom: 6px;
            overflow: hidden;
        }
        .loyalty-bar-fill {
            height: 100%;
            background: var(--red);
            border-radius: 4px;
            width: 62%;
            transition: width 0.6s ease;
        }
        .loyalty-bar-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        /* ── QUICK ACTIONS ── */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .quick-action-btn {
            background: var(--surface-3);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px 12px;
            text-align: center;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 500;
            transition: color 0.2s, border-color 0.2s, background 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .quick-action-btn i { font-size: 1.1rem; color: var(--red); }
        .quick-action-btn:hover {
            color: var(--text);
            border-color: rgba(232,25,44,0.4);
            background: var(--surface-2);
        }

        /* ── SECTION TABS (Dashboard / My Bookings / etc.) ── */
        .section-tabs {
            display: flex;
            gap: 4px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 28px;
            overflow-x: auto;
        }
        .section-tab {
            padding: 10px 20px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-muted);
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: color 0.2s, border-color 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: -1px;
        }
        .section-tab:hover { color: var(--text); }
        .section-tab.active {
            color: var(--red);
            border-bottom-color: var(--red);
        }
        .tab-count {
            background: var(--surface-3);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 1px 7px;
            color: var(--text-muted);
            font-family: 'Barlow', sans-serif;
        }
        .section-tab.active .tab-count {
            background: var(--red-glow);
            border-color: rgba(232,25,44,0.3);
            color: var(--red);
        }

        /* ── TAB PANELS ── */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── MY BOOKINGS — FILTER BAR ── */
        .filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 6px 14px;
            background: var(--surface-3);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .filter-tab:hover { color: var(--text); border-color: var(--border-hover, rgba(255,255,255,0.14)); }
        .filter-tab.active {
            background: var(--red);
            border-color: var(--red);
            color: #fff;
        }
        .filter-tab .dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .filter-tab.active .dot { background: rgba(255,255,255,0.6); }
        .dot-all        { background: var(--text-muted); }
        .dot-upcoming   { background: var(--info); }
        .dot-inprogress { background: var(--warning); }
        .dot-completed  { background: var(--success); }
        .dot-cancelled  { background: var(--red); }

        .filter-search {
            position: relative;
            flex-shrink: 0;
        }
        .filter-search input {
            background: var(--surface-3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 7px 14px 7px 34px;
            border-radius: 7px;
            font-size: 0.82rem;
            font-family: 'Barlow', sans-serif;
            outline: none;
            width: 200px;
            transition: border-color 0.2s;
        }
        .filter-search input:focus { border-color: var(--red); }
        .filter-search input::placeholder { color: var(--text-muted); }
        .filter-search i {
            position: absolute;
            left: 11px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.78rem;
            pointer-events: none;
        }

        /* ── BOOKING CARDS ── */
        .booking-cards { display: flex; flex-direction: column; gap: 12px; }

        .bk-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            transition: border-color 0.2s, transform 0.15s;
        }
        .bk-card:hover { border-color: rgba(232,25,44,0.3); transform: translateY(-1px); }

        .bk-card-inner {
            display: grid;
            grid-template-columns: 64px 1fr auto;
            gap: 0;
        }

        /* date column */
        .bk-date-col {
            background: var(--surface-2);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 16px 8px;
            min-height: 90px;
        }
        .bk-date-col .bk-day {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--red);
            line-height: 1;
        }
        .bk-date-col .bk-mon {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        .bk-date-col .bk-yr {
            font-size: 0.62rem;
            color: var(--text-muted);
            margin-top: 1px;
        }

        /* main body */
        .bk-body {
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
        }
        .bk-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .bk-service {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: var(--text);
        }
        .bk-id {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-family: monospace;
        }
        .bk-meta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .bk-meta span { display: flex; align-items: center; gap: 5px; }
        .bk-meta i { color: var(--red); font-size: 0.72rem; }

        /* status column */
        .bk-status-col {
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: 8px;
            border-left: 1px solid var(--border);
        }
        .bk-amount {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text);
        }

        /* empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 2.5rem;
            color: var(--surface-3);
            margin-bottom: 16px;
            display: block;
        }
        .empty-state p { font-size: 0.88rem; }

        /* ── BOOK A SERVICE — SHOP GRID ── */
        .shop-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .shop-search {
            position: relative;
            flex: 1;
            max-width: 300px;
        }
        .shop-search input {
            width: 100%;
            background: var(--surface-3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 8px 14px 8px 34px;
            border-radius: 7px;
            font-size: 0.83rem;
            font-family: 'Barlow', sans-serif;
            outline: none;
            transition: border-color 0.2s;
        }
        .shop-search input:focus { border-color: var(--red); }
        .shop-search input::placeholder { color: var(--text-muted); }
        .shop-search i {
            position: absolute; left: 11px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted); font-size: 0.78rem; pointer-events: none;
        }
        .shop-result-count {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .shop-result-count span { color: var(--text); font-weight: 600; }

        .category-filters {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .cat-filter {
            padding: 5px 14px;
            background: var(--surface-3);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 0.77rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
        }
        .cat-filter:hover { color: var(--text); border-color: rgba(255,255,255,0.14); }
        .cat-filter.active { background: var(--red); border-color: var(--red); color: #fff; }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }

        .service-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: border-color 0.2s, transform 0.18s, box-shadow 0.2s;
            cursor: pointer;
        }
        .service-card:hover {
            border-color: rgba(232,25,44,0.45);
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(0,0,0,0.45);
        }

        .service-img {
            width: 100%;
            aspect-ratio: 16/9;
            background: var(--surface-2);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .service-img-icon {
            font-size: 2.4rem;
            color: var(--red);
            opacity: 0.55;
            transition: opacity 0.2s, transform 0.2s;
        }
        .service-card:hover .service-img-icon { opacity: 0.9; transform: scale(1.1); }
        .service-img-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(160deg, rgba(232,25,44,0.08) 0%, transparent 60%);
        }
        .service-cat-badge {
            position: absolute;
            top: 8px; left: 8px;
            background: rgba(0,0,0,0.7);
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 20px;
            backdrop-filter: blur(4px);
        }
        .service-free-badge {
            position: absolute;
            top: 8px; right: 8px;
            background: rgba(34,197,94,0.2);
            border: 1px solid rgba(34,197,94,0.4);
            color: #22C55E;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 20px;
        }

        .service-info {
            padding: 14px 16px 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .service-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: var(--text);
            line-height: 1.25;
        }
        .service-desc {
            font-size: 0.77rem;
            color: var(--text-muted);
            line-height: 1.5;
            flex: 1;
        }
        .service-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
        }
        .service-duration {
            font-size: 0.74rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .service-duration i { color: var(--red); font-size: 0.7rem; }
        .btn-book-service {
            background: var(--red);
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-book-service:hover { background: var(--red-dark); transform: scale(1.03); }

        .services-empty {
            grid-column: 1/-1;
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .services-empty i { font-size: 2.5rem; display: block; margin-bottom: 12px; opacity: 0.3; }

        /* ── BOOKING MODAL ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.open { display: flex; }

        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 100%;
            max-width: 540px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 32px 80px rgba(0,0,0,0.8);
            animation: modalIn 0.25s ease both;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(16px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 24px 24px 20px;
            border-bottom: 1px solid var(--border);
            position: relative;
        }
        .modal-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 24px; right: 24px;
            height: 1px;
            background: linear-gradient(90deg, var(--red), transparent);
            opacity: 0.4;
        }
        .modal-header-info {}
        .modal-eyebrow {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: 3px;
        }
        .modal-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: var(--text);
        }
        .modal-close {
            background: none;
            border: 1px solid var(--border);
            color: var(--text-muted);
            width: 32px; height: 32px;
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 0.8rem;
            transition: color 0.2s, border-color 0.2s, background 0.2s;
            flex-shrink: 0;
        }
        .modal-close:hover { color: var(--red); border-color: var(--red); background: var(--red-glow); }

        .modal-body { padding: 24px; display: flex; flex-direction: column; gap: 16px; }

        .modal-service-chip {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-left: 3px solid var(--red);
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .modal-service-chip i { color: var(--red); font-size: 1rem; flex-shrink: 0; }
        .modal-service-chip .chip-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
        }
        .modal-service-chip .chip-cat {
            font-size: 0.74rem;
            color: var(--text-muted);
        }

        .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .m-form-group { display: flex; flex-direction: column; gap: 5px; }
        .m-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        .m-input-wrap { position: relative; }
        .m-input-wrap i {
            position: absolute; left: 11px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted); font-size: 0.78rem; pointer-events: none;
            transition: color 0.2s;
        }
        .m-input-wrap:focus-within i { color: var(--red); }
        .m-control {
            width: 100%;
            background: var(--surface-3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 12px 10px 32px;
            border-radius: 7px;
            font-size: 0.85rem;
            font-family: 'Barlow', sans-serif;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .m-control:focus { border-color: var(--red); box-shadow: 0 0 0 3px var(--red-glow); }
        .m-control::placeholder { color: var(--text-muted); opacity: 0.6; }
        textarea.m-control { padding-left: 12px; resize: vertical; min-height: 80px; }

        .modal-footer {
            padding: 16px 24px 24px;
            display: flex;
            gap: 10px;
        }
        .btn-modal-cancel {
            flex: 1;
            background: none;
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 11px;
            border-radius: 7px;
            font-family: 'Barlow', sans-serif;
            font-size: 0.85rem;
            cursor: pointer;
            transition: color 0.2s, border-color 0.2s, background 0.2s;
        }
        .btn-modal-cancel:hover { color: var(--text); border-color: rgba(255,255,255,0.14); background: var(--surface-3); }
        .btn-modal-submit {
            flex: 2;
            background: var(--red);
            color: #fff;
            border: none;
            padding: 11px;
            border-radius: 7px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-modal-submit:hover { background: var(--red-dark); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(232,25,44,0.35); }

        /* success screen inside modal */
        .modal-success {
            display: none;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 40px 32px;
            gap: 12px;
        }
        .modal-success.show { display: flex; }
        .success-circle {
            width: 64px; height: 64px;
            background: rgba(34,197,94,0.15);
            border: 1px solid rgba(34,197,94,0.3);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #22C55E;
            font-size: 1.6rem;
            margin-bottom: 4px;
        }
        .modal-success h3 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.3rem; font-weight: 800;
        }
        .modal-success p { font-size: 0.83rem; color: var(--text-muted); line-height: 1.5; }
        .modal-success .ref {
            font-family: monospace;
            font-size: 0.85rem;
            background: var(--surface-3);
            border: 1px solid var(--border);
            padding: 6px 16px;
            border-radius: 6px;
            color: var(--text);
        }

        /* ── FOOTER ── */
        footer {
            border-top: 1px solid var(--border);
            padding: 18px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            color: var(--text-muted);
            background: var(--surface);
        }
        footer a { color: var(--text-muted); text-decoration: none; transition: color 0.2s; }
        footer a:hover { color: var(--red); }
        .footer-brand span { color: var(--red); font-weight: 700; }
    </style>
</head>
<body>

<!-- TOPNAV -->
<nav class="topnav">
    <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <a href="#" class="brand">
        <span class="brand-apx">APX</span>
        <span class="brand-auto">AutoMai</span>
        <span class="brand-badge">CUSTOMER</span>
    </a>
    <div class="topnav-search">
        <i class="fas fa-search search-icon"></i>
        <input type="text" placeholder="Search your bookings..." />
    </div>
    <div class="topnav-actions">
        <a href="#!" class="icon-btn" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="notif-dot"></span>
        </a>
        <div class="dropdown">
            <a class="user-chip" href="#!">
                <div class="user-avatar">JD</div>
                <span class="user-name">Juan dela Cruz</span>
                <i class="fas fa-chevron-down" style="font-size:0.65rem;color:var(--text-muted);margin-left:4px;"></i>
            </a>
            <div class="dropdown-menu">
                <a href="#!"><i class="fas fa-user" style="width:16px;margin-right:8px;"></i>My Profile</a>
                <a href="#!"><i class="fas fa-car" style="width:16px;margin-right:8px;"></i>My Vehicles</a>
                <a href="#!"><i class="fas fa-gear" style="width:16px;margin-right:8px;"></i>Settings</a>
                <hr />
                <a href="/auth/login.php" class="logout"><i class="fas fa-right-from-bracket" style="width:16px;margin-right:8px;"></i>Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- LAYOUT -->
<div class="layout">

    <!-- SIDEBAR -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-body">
            <div class="section-label">My Account</div>

            <a class="nav-link active" id="nav-dashboard" href="#" onclick="switchSection(event, 'dashboard')">
                <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
                <span class="nav-label">Dashboard</span>
            </a>

            <hr class="sidebar-divider" />
            <div class="section-label">Services</div>

            <a class="nav-link" href="#" onclick="switchSection(event, 'bookings'); filterBookings('all')">
                <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
                <span class="nav-label">My Bookings</span>
            </a>

            <a class="nav-link" id="nav-services" href="#" onclick="switchSection(event,'services')">
                <span class="nav-icon"><i class="fas fa-circle-plus"></i></span>
                <span class="nav-label">Book a Service</span>
            </a>

            <a class="nav-link" href="#">
                <span class="nav-icon"><i class="fas fa-car"></i></span>
                <span class="nav-label">My Vehicles</span>
            </a>

            <hr class="sidebar-divider" />
            <div class="section-label">Rewards</div>

            <a class="nav-link" href="#">
                <span class="nav-icon"><i class="fas fa-star"></i></span>
                <span class="nav-label">Loyalty Points</span>
            </a>

            <a class="nav-link" href="#">
                <span class="nav-icon"><i class="fas fa-tag"></i></span>
                <span class="nav-label">Promos & Offers</span>
            </a>

            <hr class="sidebar-divider" />
            <div class="section-label">Account</div>

            <a class="nav-link" href="#">
                <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
                <span class="nav-label">My Profile</span>
            </a>

            <a class="nav-link" href="#">
                <span class="nav-icon"><i class="fas fa-bell"></i></span>
                <span class="nav-label">Notifications</span>
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-footer-info">
                <div class="label">Logged in as</div>
                <div class="value">Juan dela Cruz</div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <main>

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <h1 class="page-title" id="pageTitle">MY <span>DASHBOARD</span></h1>
                    <ol class="breadcrumb">
                        <li>Customer Portal</li>
                        <li class="active" id="pageBreadcrumb">Dashboard</li>
                    </ol>
                </div>
                <div class="page-date">
                    <i class="far fa-calendar" style="margin-right:6px;color:var(--red);"></i>
                    <span id="currentDate"></span>
                </div>
            </div>

            <!-- SECTION TABS -->
            <div class="section-tabs">
                <button class="section-tab active" id="tab-dashboard" onclick="switchSection(event,'dashboard')">
                    <i class="fas fa-gauge-high"></i> Dashboard
                </button>
                <button class="section-tab" id="tab-bookings" onclick="switchSection(event,'bookings'); filterBookings('all')">
                    <i class="fas fa-calendar-check"></i> My Bookings
                    <span class="tab-count" id="tabCountBookings">11</span>
                </button>
                <button class="section-tab" id="tab-services" onclick="switchSection(event,'services')">
                    <i class="fas fa-wrench"></i> Book a Service
                </button>
            </div>

            <!-- ═══════════════════════════════════════════
                 TAB PANEL: DASHBOARD
            ═══════════════════════════════════════════ -->
            <div class="tab-panel active" id="panel-dashboard">

                <!-- WELCOME BANNER -->
                <div class="welcome-banner">
                    <div class="welcome-text">
                        <h2>Welcome back, <span>Juan</span>!</h2>
                        <p>You have 2 upcoming bookings. Your car is in good hands.</p>
                    </div>
                    <a href="#" class="btn-book" onclick="switchSection(event,'bookings'); filterBookings('all')">
                        <i class="fas fa-calendar-check"></i>
                        View My Bookings
                    </a>
                </div>

                <!-- STAT CARDS -->
                <div class="cards-grid">
                    <div class="stat-card primary">
                        <div class="stat-card-header">
                            <div class="stat-label">Upcoming</div>
                            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                        </div>
                        <div class="stat-value">2</div>
                        <div class="stat-footer">
                            <a href="#" onclick="switchSection(event,'bookings'); filterBookings('upcoming')">View Bookings <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-card-header">
                            <div class="stat-label">Completed</div>
                            <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                        </div>
                        <div class="stat-value">7</div>
                        <div class="stat-footer">
                            <a href="#" onclick="switchSection(event,'bookings'); filterBookings('completed')">View History <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-card-header">
                            <div class="stat-label">Loyalty Points</div>
                            <div class="stat-icon"><i class="fas fa-star"></i></div>
                        </div>
                        <div class="stat-value">620</div>
                        <div class="stat-footer">
                            <a href="#">Redeem Points <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-card-header">
                            <div class="stat-label">Total Spent</div>
                            <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                        </div>
                        <div class="stat-value">₱9.4k</div>
                        <div class="stat-footer">
                            <a href="#">View Invoices <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                </div>

                <!-- CONTENT GRID -->
                <div class="content-grid">
                    <!-- UPCOMING BOOKINGS PREVIEW -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-title"><i class="fas fa-calendar-check"></i> Upcoming Bookings</div>
                            <a href="#" onclick="switchSection(event,'bookings'); filterBookings('upcoming')" style="font-size:0.8rem;color:var(--red);text-decoration:none;">View All <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                        <div class="card-body">
                            <div class="booking-item">
                                <div class="booking-date-box">
                                    <span class="day">20</span>
                                    <span class="mon">Jan</span>
                                </div>
                                <div class="booking-info">
                                    <div class="booking-service">Full Car Wash + Interior Detailing</div>
                                    <div class="booking-meta">
                                        <span><i class="fas fa-clock"></i>10:00 AM</span>
                                        <span><i class="fas fa-user"></i>Marco R.</span>
                                        <span><i class="fas fa-car"></i>Toyota Vios (ABC 123)</span>
                                    </div>
                                </div>
                                <span class="badge badge-confirmed">Confirmed</span>
                            </div>
                            <div class="booking-item">
                                <div class="booking-date-box">
                                    <span class="day">28</span>
                                    <span class="mon">Jan</span>
                                </div>
                                <div class="booking-info">
                                    <div class="booking-service">Oil Change</div>
                                    <div class="booking-meta">
                                        <span><i class="fas fa-clock"></i>2:00 PM</span>
                                        <span><i class="fas fa-user"></i>Leo T.</span>
                                        <span><i class="fas fa-car"></i>Toyota Vios (ABC 123)</span>
                                    </div>
                                </div>
                                <span class="badge badge-pending">Pending</span>
                            </div>
                        </div>
                    </div>

                    <!-- SIDEBAR COLUMN -->
                    <div>
                        <div class="loyalty-card">
                            <div class="loyalty-label">Loyalty Rewards</div>
                            <div class="loyalty-points">620 pts</div>
                            <div class="loyalty-sub">380 pts away from Silver tier</div>
                            <div class="loyalty-bar-wrap">
                                <div class="loyalty-bar-fill"></div>
                            </div>
                            <div class="loyalty-bar-label">
                                <span>Bronze</span>
                                <span>Silver (1,000 pts)</span>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-title"><i class="fas fa-bolt"></i> Quick Actions</div>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions">
                                    <a href="#" class="quick-action-btn"><i class="fas fa-circle-plus"></i>Book Service</a>
                                    <a href="#" class="quick-action-btn"><i class="fas fa-car"></i>My Vehicles</a>
                                    <a href="#" class="quick-action-btn"><i class="fas fa-file-invoice"></i>Invoices</a>
                                    <a href="#" class="quick-action-btn"><i class="fas fa-headset"></i>Support</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /panel-dashboard -->


            <!-- ═══════════════════════════════════════════
                 TAB PANEL: MY BOOKINGS
            ═══════════════════════════════════════════ -->
            <div class="tab-panel" id="panel-bookings">

                <!-- FILTER BAR -->
                <div class="filter-bar">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all" onclick="filterBookings('all')">
                            <span class="dot dot-all"></span> All
                            <span class="tab-count" id="cnt-all">11</span>
                        </button>
                        <button class="filter-tab" data-filter="upcoming" onclick="filterBookings('upcoming')">
                            <span class="dot dot-upcoming"></span> Upcoming
                            <span class="tab-count" id="cnt-upcoming">2</span>
                        </button>
                        <button class="filter-tab" data-filter="inprogress" onclick="filterBookings('inprogress')">
                            <span class="dot dot-inprogress"></span> In Progress
                            <span class="tab-count" id="cnt-inprogress">1</span>
                        </button>
                        <button class="filter-tab" data-filter="completed" onclick="filterBookings('completed')">
                            <span class="dot dot-completed"></span> Completed
                            <span class="tab-count" id="cnt-completed">7</span>
                        </button>
                        <button class="filter-tab" data-filter="cancelled" onclick="filterBookings('cancelled')">
                            <span class="dot dot-cancelled"></span> Cancelled
                            <span class="tab-count" id="cnt-cancelled">1</span>
                        </button>
                    </div>
                    <div class="filter-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="bookingSearch" placeholder="Search bookings..." oninput="searchBookings(this.value)" />
                    </div>
                </div>

                <!-- BOOKING CARDS LIST -->
                <div class="booking-cards" id="bookingCardsList"></div>

                <!-- EMPTY STATE -->
                <div class="empty-state" id="bookingEmpty" style="display:none;">
                    <i class="fas fa-calendar-xmark"></i>
                    <p>No bookings found for this filter.</p>
                </div>

            </div><!-- /panel-bookings -->

            <!-- ═══════════════════════════════════════════
                 TAB PANEL: BOOK A SERVICE
            ═══════════════════════════════════════════ -->
            <div class="tab-panel" id="panel-services">

                <!-- TOOLBAR -->
                <div class="shop-toolbar">
                    <div class="shop-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="serviceSearch" placeholder="Search services..." oninput="filterServices()" />
                    </div>
                    <div class="shop-result-count">
                        Showing <span id="serviceCount">28</span> services
                    </div>
                </div>

                <!-- CATEGORY FILTERS -->
                <div class="category-filters">
                    <button class="cat-filter active" data-cat="all"        onclick="setCat(this)">All Services</button>
                    <button class="cat-filter"         data-cat="engine"    onclick="setCat(this)">Engine & Oil</button>
                    <button class="cat-filter"         data-cat="cvt"       onclick="setCat(this)">CVT & Transmission</button>
                    <button class="cat-filter"         data-cat="brakes"    onclick="setCat(this)">Brakes & Pipes</button>
                    <button class="cat-filter"         data-cat="inspection" onclick="setCat(this)">Inspection</button>
                    <button class="cat-filter"         data-cat="free"      onclick="setCat(this)">Free Services</button>
                </div>

                <!-- SERVICES GRID -->
                <div class="services-grid" id="servicesGrid"></div>

            </div><!-- /panel-services -->

        </main>

        <!-- BOOKING MODAL -->
        <div class="modal-overlay" id="bookingModal" onclick="handleOverlayClick(event)">
            <div class="modal" id="modalBox">

                <!-- FORM VIEW -->
                <div id="modalFormView">
                    <div class="modal-header">
                        <div class="modal-header-info">
                            <div class="modal-eyebrow">New Booking</div>
                            <div class="modal-title" id="modalServiceName">Service Name</div>
                        </div>
                        <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
                    </div>

                    <div class="modal-body">
                        <!-- Service chip -->
                        <div class="modal-service-chip">
                            <i class="fas fa-wrench" id="modalServiceIcon"></i>
                            <div>
                                <div class="chip-name" id="modalServiceNameChip">—</div>
                                <div class="chip-cat" id="modalServiceCat">—</div>
                            </div>
                        </div>

                        <!-- Date & Time -->
                        <div class="form-row-2">
                            <div class="m-form-group">
                                <label class="m-label">Preferred Date</label>
                                <div class="m-input-wrap">
                                    <i class="fas fa-calendar"></i>
                                    <input class="m-control" id="mDate" type="date" required />
                                </div>
                            </div>
                            <div class="m-form-group">
                                <label class="m-label">Preferred Time</label>
                                <div class="m-input-wrap">
                                    <i class="fas fa-clock"></i>
                                    <input class="m-control" id="mTime" type="time" required />
                                </div>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div class="m-form-group">
                            <label class="m-label">Contact Number</label>
                            <div class="m-input-wrap">
                                <i class="fas fa-phone"></i>
                                <input class="m-control" id="mPhone" type="tel" placeholder="+63 9XX XXX XXXX" />
                            </div>
                        </div>

                        <!-- Vehicle Details -->
                        <div class="form-row-2">
                            <div class="m-form-group">
                                <label class="m-label">Make & Model</label>
                                <div class="m-input-wrap">
                                    <i class="fas fa-car"></i>
                                    <input class="m-control" id="mVehicleMake" type="text" placeholder="e.g. Honda Click" />
                                </div>
                            </div>
                            <div class="m-form-group">
                                <label class="m-label">Plate / Unit No.</label>
                                <div class="m-input-wrap">
                                    <i class="fas fa-id-card"></i>
                                    <input class="m-control" id="mVehiclePlate" type="text" placeholder="e.g. ABC 1234" />
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="m-form-group">
                            <label class="m-label">Special Notes / Instructions</label>
                            <textarea class="m-control" id="mNotes" placeholder="Any specific concerns or instructions for the technician…"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                        <button class="btn-modal-submit" onclick="submitBooking()">
                            <i class="fas fa-calendar-check"></i>
                            Confirm Booking
                        </button>
                    </div>
                </div>

                <!-- SUCCESS VIEW -->
                <div class="modal-success" id="modalSuccessView">
                    <div class="success-circle"><i class="fas fa-check"></i></div>
                    <h3>Booking Confirmed!</h3>
                    <p>Your appointment has been submitted. We'll send a confirmation once it's approved.</p>
                    <div class="ref" id="modalRefNo">—</div>
                    <p style="font-size:0.78rem;">
                        <i class="fas fa-calendar" style="color:var(--red);margin-right:4px;"></i>
                        <span id="modalSuccessDate">—</span>
                        &nbsp;·&nbsp;
                        <i class="fas fa-wrench" style="color:var(--red);margin-right:4px;"></i>
                        <span id="modalSuccessService">—</span>
                    </p>
                    <div style="display:flex;gap:10px;margin-top:8px;width:100%;">
                        <button class="btn-modal-cancel" style="flex:1;" onclick="closeModal()">Close</button>
                        <button class="btn-modal-submit" style="flex:2;" onclick="switchSection(null,'bookings'); filterBookings('upcoming'); closeModal()">
                            <i class="fas fa-list"></i> View My Bookings
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- FOOTER -->
        <footer>
            <div class="footer-brand"><span>APX</span> AutoMai &mdash; Customer Portal &copy; 2025</div>
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
<script>
    // ══════════════════════════════════════════════════════════════
    //  DATA
    // ══════════════════════════════════════════════════════════════
    const SERVICES = [
        { id:'svc-01',  name:'Change Oil & Filter',                        cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-oil-can',        desc:'Complete engine oil drain and refill with high-quality oil and a fresh filter for optimal engine performance.',                         duration:'30–45 min',  free:false },
        { id:'svc-02',  name:'Fuel Injection Cleaning',                    cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-gas-pump',        desc:'Deep cleaning of fuel injectors to restore proper fuel atomization, improving throttle response and fuel economy.',                     duration:'45–60 min',  free:false },
        { id:'svc-03',  name:'Throttle Body Cleaning',                     cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-wind',            desc:'Remove carbon buildup and deposits from the throttle body for smoother idling and improved acceleration.',                              duration:'30–45 min',  free:false },
        { id:'svc-04',  name:'Throttle Idle Adjustment',                   cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-sliders',         desc:'Fine-tune idle speed to manufacturer specs, eliminating rough idle and stalling at traffic stops.',                                    duration:'20–30 min',  free:false },
        { id:'svc-05',  name:'Valve Clearance Adjustment / Tune-up',       cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-screwdriver-wrench', desc:'Inspect and adjust valve clearances to ensure proper engine breathing, reducing noise and wear.',                                  duration:'60–90 min',  free:false },
        { id:'svc-06',  name:'CVT Cleaning and Inspection',                cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-gear',            desc:'Full inspection and cleaning of the Continuously Variable Transmission components to extend drivetrain life and smooth power delivery.',  duration:'60–90 min',  free:false },
        { id:'svc-07',  name:'Air Filter Inspection',                      cat:'inspection', catLabel:'Inspection',         icon:'fa-filter',          desc:'Visual and performance check of the air filter element to ensure clean airflow to the engine.',                                         duration:'15 min',     free:false },
        { id:'svc-08',  name:'Air Filter Installation',                    cat:'inspection', catLabel:'Inspection',         icon:'fa-filter',          desc:'Supply and installation of a new OEM-spec air filter for peak engine breathing.',                                                        duration:'20 min',     free:false },
        { id:'svc-09',  name:'Flyball Inspection',                         cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-dot',      desc:'Check flyball condition and wear for proper CVT engagement and smooth take-off.',                                                        duration:'30 min',     free:false },
        { id:'svc-10',  name:'Flyball Cleaning',                           cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-dot',      desc:'Thorough cleaning of flyball weights to remove grease and grime affecting CVT performance.',                                            duration:'30–45 min',  free:false },
        { id:'svc-11',  name:'V-belt Inspection',                          cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-bezier-curve',    desc:'Inspect V-belt for cracks, glazing, and wear to prevent unexpected belt failure.',                                                       duration:'20 min',     free:false },
        { id:'svc-12',  name:'V-belt Cleaning',                            cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-bezier-curve',    desc:'Clean V-belt surfaces and housing to remove debris that causes slipping and noise.',                                                     duration:'20–30 min',  free:false },
        { id:'svc-13',  name:'Pulley Set Inspection',                      cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-half-stroke', desc:'Full inspection of drive and driven pulley sets for wear, scoring, and proper movement.',                                           duration:'30 min',     free:false },
        { id:'svc-14',  name:'Pulley Set Cleaning',                        cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-half-stroke', desc:'Detailed cleaning of pulley faces and grooves to restore smooth belt travel and consistent CVT ratio changes.',                   duration:'30–45 min',  free:false },
        { id:'svc-15',  name:'Torque Drive Assy Inspection',               cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-rotate',          desc:'Inspect the torque drive assembly for wear and proper spring tension.',                                                                 duration:'30 min',     free:false },
        { id:'svc-16',  name:'Torque Drive Assy Cleaning',                 cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-rotate',          desc:'Clean all torque drive assembly components to remove built-up grease and contaminants.',                                               duration:'30–45 min',  free:false },
        { id:'svc-17',  name:'Torque Drive Assy Greasing',                 cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-rotate',          desc:'Apply fresh high-temperature grease to torque drive components for quiet, smooth operation.',                                          duration:'20–30 min',  free:false },
        { id:'svc-18',  name:'Clutch Lining Set / Assy Inspection',        cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-xmark',    desc:'Measure clutch lining thickness and check assembly condition to ensure positive engagement.',                                          duration:'30 min',     free:false },
        { id:'svc-19',  name:'Clutch Lining Set / Assy Cleaning',          cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-xmark',    desc:'Clean clutch lining components and housing to restore grip and prevent glazing.',                                                      duration:'30–45 min',  free:false },
        { id:'svc-20',  name:'Kick Starter Inspection (if applicable)',    cat:'inspection', catLabel:'Inspection',         icon:'fa-person-walking',  desc:'Check kick starter mechanism for wear, proper engagement, and return spring condition.',                                               duration:'20 min',     free:false },
        { id:'svc-21',  name:'Pulley Shaving & Re-Angle',                  cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-screwdriver',     desc:'Precision machining of drive pulley face to optimize belt contact angle for improved performance.',                                    duration:'60–90 min',  free:false },
        { id:'svc-22',  name:'Pulley Drive Face Shaving & Re-Angle',       cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-screwdriver',     desc:'Re-angle and resurface the drive face pulley for maximum power transfer and acceleration.',                                            duration:'60–90 min',  free:false },
        { id:'svc-23',  name:'Sprocket/Chain Cleaning & Regreasing',       cat:'inspection', catLabel:'Inspection',         icon:'fa-link',            desc:'Clean and relube sprocket and chain drive components on chain-type motorcycles to reduce wear and noise.',                              duration:'30–45 min',  free:false },
        { id:'svc-24',  name:'Pipe Cleaning',                              cat:'brakes',     catLabel:'Brakes & Pipes',     icon:'fa-pipe',            desc:'Flush and clean fuel and coolant pipes to remove blockages, corrosion, and sediment buildup.',                                         duration:'30 min',     free:false },
        { id:'svc-25',  name:'Brake Cleaning',                             cat:'brakes',     catLabel:'Brakes & Pipes',     icon:'fa-circle-stop',     desc:'Degrease brake pads, discs, and drums to restore maximum braking bite and eliminate brake squeal.',                                    duration:'30–45 min',  free:false },
        { id:'svc-26',  name:'Brake Adjustment',                           cat:'brakes',     catLabel:'Brakes & Pipes',     icon:'fa-circle-stop',     desc:'Adjust brake cable tension and drum/disc clearance for firm, consistent brake feel.',                                                 duration:'20–30 min',  free:false },
        { id:'svc-27',  name:'FREE ECU Diagnose (Reset if Applicable)',    cat:'free',       catLabel:'Free Service',       icon:'fa-microchip',       desc:'Complimentary ECU scan using professional diagnostic tools. Error codes reset where applicable.',                                       duration:'15–30 min',  free:true  },
        { id:'svc-28',  name:'FREE Basic Inspection',                      cat:'free',       catLabel:'Free Service',       icon:'fa-clipboard-check', desc:'Complimentary 20-point visual inspection of your motorcycle covering engine, brakes, CVT, and electrical.',                            duration:'20–30 min',  free:true  },
    ];

    const BOOKINGS = [
        { id:'#BK-0043', service:'Full Car Wash + Interior Detailing', date:'2024-01-20', day:'20', mon:'Jan', yr:'2024', time:'10:00 AM', staff:'Marco R.', vehicle:'Toyota Vios (ABC 123)', amount:'₱1,550', status:'upcoming' },
        { id:'#BK-0042', service:'Oil Change',                         date:'2024-01-28', day:'28', mon:'Jan', yr:'2024', time:'2:00 PM',  staff:'Leo T.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱800',   status:'upcoming' },
        { id:'#BK-0041', service:'Engine Check',                       date:'2024-01-15', day:'15', mon:'Jan', yr:'2024', time:'9:00 AM',  staff:'Jay M.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱600',   status:'inprogress' },
        { id:'#BK-0038', service:'Full Car Wash',                      date:'2024-01-10', day:'10', mon:'Jan', yr:'2024', time:'11:00 AM', staff:'Marco R.', vehicle:'Toyota Vios (ABC 123)', amount:'₱350',   status:'completed' },
        { id:'#BK-0034', service:'Interior Detailing',                 date:'2024-01-05', day:'05', mon:'Jan', yr:'2024', time:'1:00 PM',  staff:'Jay M.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱1,200', status:'completed' },
        { id:'#BK-0030', service:'Oil Change',                         date:'2023-12-28', day:'28', mon:'Dec', yr:'2023', time:'3:00 PM',  staff:'Leo T.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱800',   status:'completed' },
        { id:'#BK-0026', service:'Paint Protection',                   date:'2023-12-20', day:'20', mon:'Dec', yr:'2023', time:'9:00 AM',  staff:'Marco R.', vehicle:'Toyota Vios (ABC 123)', amount:'₱2,500', status:'completed' },
        { id:'#BK-0022', service:'Engine Check',                       date:'2023-12-14', day:'14', mon:'Dec', yr:'2023', time:'10:00 AM', staff:'Jay M.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱600',   status:'completed' },
        { id:'#BK-0018', service:'Tire Rotation',                      date:'2023-12-08', day:'08', mon:'Dec', yr:'2023', time:'2:00 PM',  staff:'Leo T.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱400',   status:'completed' },
        { id:'#BK-0014', service:'Full Car Wash',                      date:'2023-11-30', day:'30', mon:'Nov', yr:'2023', time:'11:00 AM', staff:'Marco R.', vehicle:'Toyota Vios (ABC 123)', amount:'₱350',   status:'completed' },
        { id:'#BK-0010', service:'Interior Detailing',                 date:'2023-11-20', day:'20', mon:'Nov', yr:'2023', time:'1:00 PM',  staff:'Jay M.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱1,200', status:'cancelled' },
    ];

    const STATUS_META = {
        upcoming:   { label:'Upcoming',    cls:'badge-confirmed',  icon:'fa-clock' },
        inprogress: { label:'In Progress', cls:'badge-inprogress', icon:'fa-rotate' },
        completed:  { label:'Completed',   cls:'badge-completed',  icon:'fa-circle-check' },
        cancelled:  { label:'Cancelled',   cls:'badge-cancelled',  icon:'fa-ban' },
    };

    // ══════════════════════════════════════════════════════════════
    //  STATE
    // ══════════════════════════════════════════════════════════════
    let currentFilter  = 'all';
    let currentSearch  = '';
    let currentCat     = 'all';
    let currentSvcSearch = '';
    let bookingCounter = 44;

    // ══════════════════════════════════════════════════════════════
    //  SECTION TABS
    // ══════════════════════════════════════════════════════════════
    const PAGE_TITLES = {
        dashboard: ['MY <span>DASHBOARD</span>',  'Dashboard'],
        bookings:  ['MY <span>BOOKINGS</span>',    'My Bookings'],
        services:  ['BOOK A <span>SERVICE</span>', 'Book a Service'],
    };

    function switchSection(e, section) {
        if (e) e.preventDefault();
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('panel-' + section).classList.add('active');
        document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
        const secTab = document.getElementById('tab-' + section);
        if (secTab) secTab.classList.add('active');
        document.querySelectorAll('.nav-link[id^="nav-"]').forEach(l => l.classList.remove('active'));
        const navLink = document.getElementById('nav-' + section);
        if (navLink) navLink.classList.add('active');
        document.getElementById('pageTitle').innerHTML   = PAGE_TITLES[section][0];
        document.getElementById('pageBreadcrumb').textContent = PAGE_TITLES[section][1];
    }

    // ══════════════════════════════════════════════════════════════
    //  MY BOOKINGS
    // ══════════════════════════════════════════════════════════════
    function filterBookings(filter) {
        currentFilter = filter;
        document.querySelectorAll('.filter-tab').forEach(t =>
            t.classList.toggle('active', t.dataset.filter === filter));
        renderBookings();
    }

    function searchBookings(val) {
        currentSearch = val.toLowerCase();
        renderBookings();
    }

    function renderBookings() {
        const list  = document.getElementById('bookingCardsList');
        const empty = document.getElementById('bookingEmpty');
        let filtered = BOOKINGS.filter(b => {
            const mf = currentFilter === 'all' || b.status === currentFilter;
            const ms = !currentSearch ||
                b.service.toLowerCase().includes(currentSearch) ||
                b.id.toLowerCase().includes(currentSearch) ||
                b.staff.toLowerCase().includes(currentSearch) ||
                b.vehicle.toLowerCase().includes(currentSearch);
            return mf && ms;
        });
        if (!filtered.length) { list.innerHTML = ''; empty.style.display = 'block'; return; }
        empty.style.display = 'none';
        list.innerHTML = filtered.map(b => {
            const meta = STATUS_META[b.status];
            return `<div class="bk-card">
                <div class="bk-card-inner">
                    <div class="bk-date-col">
                        <span class="bk-day">${b.day}</span>
                        <span class="bk-mon">${b.mon}</span>
                        <span class="bk-yr">${b.yr}</span>
                    </div>
                    <div class="bk-body">
                        <div class="bk-title-row">
                            <span class="bk-service">${b.service}</span>
                            <span class="bk-id">${b.id}</span>
                        </div>
                        <div class="bk-meta">
                            <span><i class="fas fa-clock"></i>${b.time}</span>
                            <span><i class="fas fa-user"></i>${b.staff}</span>
                            <span><i class="fas fa-car"></i>${b.vehicle}</span>
                        </div>
                    </div>
                    <div class="bk-status-col">
                        <span class="badge ${meta.cls}"><i class="fas ${meta.icon}"></i>${meta.label}</span>
                        <span class="bk-amount">${b.amount}</span>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    // ══════════════════════════════════════════════════════════════
    //  SERVICES GRID
    // ══════════════════════════════════════════════════════════════
    function setCat(btn) {
        document.querySelectorAll('.cat-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCat = btn.dataset.cat;
        filterServices();
    }

    function filterServices() {
        currentSvcSearch = document.getElementById('serviceSearch').value.toLowerCase();
        const grid = document.getElementById('servicesGrid');
        const filtered = SERVICES.filter(s => {
            const matchCat    = currentCat === 'all' || s.cat === currentCat;
            const matchSearch = !currentSvcSearch ||
                s.name.toLowerCase().includes(currentSvcSearch) ||
                s.desc.toLowerCase().includes(currentSvcSearch) ||
                s.catLabel.toLowerCase().includes(currentSvcSearch);
            return matchCat && matchSearch;
        });
        document.getElementById('serviceCount').textContent = filtered.length;
        if (!filtered.length) {
            grid.innerHTML = `<div class="services-empty"><i class="fas fa-magnifying-glass"></i><p>No services match your search.</p></div>`;
            return;
        }
        grid.innerHTML = filtered.map(s => `
            <div class="service-card" onclick="openModal('${s.id}')">
                <div class="service-img">
                    <div class="service-img-overlay"></div>
                    <i class="fas ${s.icon} service-img-icon"></i>
                    <span class="service-cat-badge">${s.catLabel}</span>
                    ${s.free ? '<span class="service-free-badge">FREE</span>' : ''}
                </div>
                <div class="service-info">
                    <div class="service-name">${s.name}</div>
                    <div class="service-desc">${s.desc}</div>
                    <div class="service-card-footer">
                        <span class="service-duration"><i class="fas fa-clock"></i>${s.duration}</span>
                        <button class="btn-book-service" onclick="event.stopPropagation(); openModal('${s.id}')">
                            <i class="fas fa-calendar-plus"></i> Book
                        </button>
                    </div>
                </div>
            </div>`).join('');
    }

    // ══════════════════════════════════════════════════════════════
    //  BOOKING MODAL
    // ══════════════════════════════════════════════════════════════
    let selectedService = null;

    function openModal(svcId) {
        selectedService = SERVICES.find(s => s.id === svcId);
        if (!selectedService) return;

        // Reset to form view
        document.getElementById('modalFormView').style.display  = 'block';
        document.getElementById('modalSuccessView').classList.remove('show');

        // Populate
        document.getElementById('modalServiceName').textContent      = selectedService.name;
        document.getElementById('modalServiceNameChip').textContent  = selectedService.name;
        document.getElementById('modalServiceCat').textContent       = selectedService.catLabel + ' · ' + selectedService.duration;
        document.getElementById('modalServiceIcon').className        = 'fas ' + selectedService.icon;

        // Default date = tomorrow
        const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('mDate').value  = tomorrow.toISOString().split('T')[0];
        document.getElementById('mTime').value  = '09:00';
        document.getElementById('mPhone').value = '';
        document.getElementById('mVehicleMake').value  = '';
        document.getElementById('mVehiclePlate').value = '';
        document.getElementById('mNotes').value = '';

        document.getElementById('bookingModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('bookingModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    function handleOverlayClick(e) {
        if (e.target === document.getElementById('bookingModal')) closeModal();
    }

    function submitBooking() {
        const date  = document.getElementById('mDate').value;
        const time  = document.getElementById('mTime').value;
        const phone = document.getElementById('mPhone').value;
        const make  = document.getElementById('mVehicleMake').value;
        const plate = document.getElementById('mVehiclePlate').value;

        if (!date || !time) {
            document.getElementById('mDate').focus();
            document.getElementById('mDate').style.borderColor = 'var(--red)';
            setTimeout(() => document.getElementById('mDate').style.borderColor = '', 1500);
            return;
        }

        // Generate a booking reference
        const refNo = '#BK-' + String(bookingCounter++).padStart(4, '0');
        const formattedDate = new Date(date).toLocaleDateString('en-US', { weekday:'short', month:'long', day:'numeric', year:'numeric' });
        const formattedTime = new Date('1970-01-01T' + time).toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit' });

        // Add to BOOKINGS array (simulated)
        const [d, m, y] = [
            new Date(date).getDate().toString().padStart(2,'0'),
            new Date(date).toLocaleString('en-US',{month:'short'}),
            new Date(date).getFullYear().toString()
        ];
        BOOKINGS.unshift({
            id: refNo,
            service: selectedService.name,
            date, day: d, mon: m, yr: y,
            time: formattedTime,
            staff: 'TBA',
            vehicle: (make || 'N/A') + (plate ? ' (' + plate + ')' : ''),
            amount: selectedService.free ? '₱0 (Free)' : 'TBA',
            status: 'upcoming'
        });

        // Show success screen
        document.getElementById('modalFormView').style.display   = 'none';
        document.getElementById('modalSuccessView').classList.add('show');
        document.getElementById('modalRefNo').textContent        = refNo;
        document.getElementById('modalSuccessDate').textContent  = formattedDate + ' at ' + formattedTime;
        document.getElementById('modalSuccessService').textContent = selectedService.name;
    }

    // ══════════════════════════════════════════════════════════════
    //  INIT
    // ══════════════════════════════════════════════════════════════
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });

    document.getElementById('sidebarToggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('collapsed');
        document.getElementById('mainContent').classList.toggle('expanded');
    });

    // Pre-render
    renderBookings();
    filterServices();
</script>
</body>
</html>
