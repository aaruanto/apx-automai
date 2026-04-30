<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>@yield('title', 'Dashboard') — APX AutoMai</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="{{ asset('assets/css/dashboard.css') }}" rel="stylesheet" />
    @stack('styles')
    <style>
        /* ═══════════════════════════════════════════════════
           LIGHT MODE — default on fresh load
        ═══════════════════════════════════════════════════ */
        :root {
            --black: #f4f5f7;
            --surface: #ffffff;
            --surface-2: #f0f1f3;
            --surface-3: #e6e8ec;
            --border: rgba(0, 0, 0, 0.09);
            --text: #1a1d23;
            --text-muted: #6b7280;
            --red: #E8192C;
            --red-glow: rgba(232, 25, 44, 0.10);
            --success: #22c55e;
            --warning: #f59e0b;
            --info: #3b82f6;
        }

        /* ── Dark mode overrides ── */
        html.dark-mode {
            --black: #0f1117;
            --surface: #1a1d23;
            --surface-2: #20242c;
            --surface-3: #272b35;
            --border: rgba(255, 255, 255, 0.07);
            --text: #e8eaed;
            --text-muted: #6b7280;
            --red-glow: rgba(232, 25, 44, 0.15);
        }

        *,
        *::before,
        *::after {
            transition: background-color 0.22s ease, color 0.15s ease,
                border-color 0.15s ease, box-shadow 0.15s ease;
        }

        body {
            background: var(--black);
            color: var(--text);
            font-family: 'Barlow', sans-serif;
            margin: 0;
        }

        /* ── Topnav ── */
        .topnav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 56px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 16px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, .07);
        }

        html.dark-mode .topnav {
            box-shadow: 0 1px 0 rgba(255, 255, 255, .04);
        }

        #sidebarToggle {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1rem;
            padding: 6px;
            border-radius: 6px;
            line-height: 1;
        }

        #sidebarToggle:hover {
            background: var(--surface-2);
            color: var(--text);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .brand-apx {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--red);
        }

        .brand-auto {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text);
        }

        .brand-badge {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: .12em;
            background: var(--red-glow);
            color: var(--red);
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid rgba(232, 25, 44, .18);
        }

        .topnav-search {
            flex: 1;
            max-width: 380px;
            position: relative;
            margin-left: 8px;
        }

        .topnav-search .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: .75rem;
            pointer-events: none;
        }

        .topnav-search input {
            width: 100%;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 7px 12px 7px 30px;
            font-size: .82rem;
            color: var(--text);
            font-family: 'Barlow', sans-serif;
            outline: none;
        }

        .topnav-search input:focus {
            border-color: rgba(232, 25, 44, .3);
        }

        .topnav-search input::placeholder {
            color: var(--text-muted);
        }

        .topnav-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: none;
            color: var(--text-muted);
            font-size: .9rem;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .icon-btn:hover {
            background: var(--surface-2);
            color: var(--text);
        }

        .notif-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--red);
            border: 2px solid var(--surface);
        }

        /* ── User chip / dropdown ── */
        .dropdown {
            position: relative;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px 5px 5px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .user-chip:hover {
            background: var(--surface-2);
            border-color: var(--border);
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--red-glow);
            color: var(--red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 800;
            font-size: .8rem;
        }

        .user-name {
            font-size: .82rem;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 6px;
            min-width: 180px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
            display: none;
            z-index: 9999;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 6px;
            font-size: .83rem;
            color: var(--text);
            text-decoration: none;
        }

        .dropdown-menu a:hover {
            background: var(--surface-2);
        }

        .dropdown-menu hr {
            border: none;
            border-top: 1px solid var(--border);
            margin: 4px 0;
        }

        .dropdown-menu .logout {
            color: var(--red) !important;
        }

        .dropdown-menu .logout:hover {
            background: var(--red-glow);
        }

        /* ── Sidebar ── */
        .layout {
            display: flex;
            padding-top: 56px;
            min-height: 100vh;
        }

        .sidebar {
            width: 230px;
            flex-shrink: 0;
            background: var(--surface);
            border-right: 1px solid var(--border);
            position: fixed;
            top: 56px;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            transition: width .2s ease;
            z-index: 900;
        }

        .sidebar.collapsed {
            width: 54px;
        }

        .sidebar.collapsed .nav-label,
        .sidebar.collapsed .nav-arrow,
        .sidebar.collapsed .section-label,
        .sidebar.collapsed .sub-nav,
        .sidebar.collapsed .sidebar-footer-info {
            display: none;
        }

        .sidebar-body {
            flex: 1;
            padding: 14px 8px;
        }

        .section-label {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 6px 8px 4px;
            opacity: .6;
        }

        .sidebar-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 8px 4px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: 8px;
            font-size: .84rem;
            color: var(--text-muted);
            text-decoration: none;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .nav-link:hover {
            background: var(--surface-2);
            color: var(--text);
        }

        .nav-link.active,
        .nav-link.active .nav-icon {
            color: var(--red);
        }

        .nav-link.active {
            background: var(--red-glow);
        }

        .nav-icon {
            width: 18px;
            text-align: center;
            flex-shrink: 0;
            font-size: .9rem;
        }

        .nav-label {
            flex: 1;
        }

        .nav-arrow {
            font-size: .6rem;
            color: var(--text-muted);
            margin-left: auto;
            transition: transform .2s;
        }

        .nav-link.open .nav-arrow {
            transform: rotate(90deg);
        }

        .sub-nav {
            display: none;
            padding: 2px 0 4px 36px;
        }

        .sub-nav.open {
            display: block;
        }

        .sub-nav a {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 8px;
            border-radius: 6px;
            font-size: .81rem;
            color: var(--text-muted);
            text-decoration: none;
        }

        .sub-nav a::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--text-muted);
            flex-shrink: 0;
            opacity: .5;
        }

        .sub-nav a:hover {
            color: var(--text);
            background: var(--surface-2);
        }

        .sub-nav a:hover::before {
            background: var(--red);
            opacity: 1;
        }

        .sub-nav a.active-sub {
            color: var(--red);
        }

        .sub-nav a.active-sub::before {
            background: var(--red);
            opacity: 1;
        }

        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid var(--border);
        }

        .sidebar-footer-info .label {
            font-size: .65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .sidebar-footer-info .value {
            font-size: .78rem;
            font-weight: 600;
            color: var(--text);
            margin-top: 2px;
        }

        /* ── Main content ── */
        .main-content {
            margin-left: 230px;
            flex: 1;
            min-height: calc(100vh - 56px);
            display: flex;
            flex-direction: column;
            transition: margin-left .2s ease;
        }

        .main-content.expanded {
            margin-left: 54px;
        }

        main {
            flex: 1;
            padding: 24px;
        }

        /* ── Page header ── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .page-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text);
            margin: 0 0 4px;
        }

        .page-title span {
            color: var(--red);
        }

        .breadcrumb {
            display: flex;
            gap: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .breadcrumb li {
            font-size: .75rem;
            color: var(--text-muted);
        }

        .breadcrumb li.active {
            color: var(--red);
        }

        .breadcrumb li:not(:last-child)::after {
            content: '›';
            margin-left: 8px;
        }

        /* ── Cards ── */
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
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
        }

        .card-header-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header-title i {
            color: var(--red);
        }

        .card-body {
            padding: 18px;
        }

        .card-footer-bar {
            padding: 10px 18px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: .78rem;
            color: var(--text-muted);
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 7px;
            font-size: .82rem;
            font-weight: 600;
            font-family: 'Barlow', sans-serif;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all .15s ease;
            white-space: nowrap;
            line-height: 1.4;
        }

        .btn-primary {
            background: var(--red);
            color: #fff;
            border-color: var(--red);
        }

        .btn-primary:hover {
            background: #c8111f;
        }

        .btn-ghost {
            background: var(--surface-2);
            color: var(--text);
            border-color: var(--border);
        }

        .btn-ghost:hover {
            background: var(--surface-3);
        }

        .btn-danger {
            background: rgba(232, 25, 44, .12);
            color: var(--red);
            border-color: rgba(232, 25, 44, .2);
        }

        .btn-danger:hover {
            background: var(--red);
            color: #fff;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: .78rem;
        }

        .btn-icon {
            padding: 6px;
            width: 30px;
            height: 30px;
            justify-content: center;
        }

        /* ── Table ── */
        .table-wrap {
            overflow-x: auto;
        }

        .apx-table {
            width: 100%;
            border-collapse: collapse;
        }

        .apx-table thead tr {
            background: var(--surface-2);
        }

        .apx-table th {
            padding: 10px 14px;
            text-align: left;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }

        .apx-table td {
            padding: 12px 14px;
            font-size: .83rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
        }

        .apx-table tbody tr:hover {
            background: var(--surface-2);
        }

        .apx-table tbody tr:last-child td {
            border-bottom: none;
        }

        .primary-col {
            font-weight: 600;
            color: var(--text);
            font-size: .85rem;
        }

        /* ── Filters bar ── */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            padding: 12px 18px;
            border-bottom: 1px solid var(--border);
        }

        .filter-input,
        .filter-select {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 6px 10px;
            font-size: .8rem;
            color: var(--text);
            font-family: 'Barlow', sans-serif;
            outline: none;
        }

        .filter-input:focus,
        .filter-select:focus {
            border-color: rgba(232, 25, 44, .3);
        }

        .filter-input {
            width: 220px;
        }

        /* ── Badges ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .badge-confirmed {
            background: rgba(34, 197, 94, .12);
            color: #22c55e;
        }

        .badge-pending {
            background: rgba(245, 158, 11, .12);
            color: #f59e0b;
        }

        .badge-inprogress {
            background: rgba(59, 130, 246, .12);
            color: #3b82f6;
        }

        .badge-cancelled {
            background: rgba(232, 25, 44, .10);
            color: var(--red);
        }

        .badge-gold {
            background: rgba(245, 158, 11, .12);
            color: #f59e0b;
        }

        .badge-silver {
            background: rgba(148, 163, 184, .12);
            color: #94a3b8;
        }

        .badge-bronze {
            background: rgba(205, 124, 79, .12);
            color: #cd7c4f;
        }

        .badge-admin {
            background: rgba(232, 25, 44, .10);
            color: var(--red);
        }

        .badge-staff {
            background: rgba(59, 130, 246, .12);
            color: #3b82f6;
        }

        .badge-active {
            background: rgba(34, 197, 94, .12);
            color: #22c55e;
        }

        .badge-inactive {
            background: rgba(107, 114, 128, .12);
            color: #6b7280;
        }

        /* ── Form controls ── */
        .form-group {
            margin-bottom: 14px;
        }

        .form-label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 5px;
        }

        .form-control {
            width: 100%;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 8px 12px;
            font-size: .84rem;
            color: var(--text);
            font-family: 'Barlow', sans-serif;
            outline: none;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: rgba(232, 25, 44, .35);
            background: var(--surface);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .form-hint {
            font-size: .73rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .form-row {
            display: grid;
            gap: 12px;
        }

        .form-row.cols-2 {
            grid-template-columns: 1fr 1fr;
        }

        .form-row.cols-3 {
            grid-template-columns: 1fr 1fr 1fr;
        }

        /* ── Event blocks (schedule) ── */
        .event-block {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .event-block.confirmed {
            border-left: 3px solid var(--success);
        }

        .event-block.pending {
            border-left: 3px solid var(--warning);
        }

        .event-block.in_progress {
            border-left: 3px solid var(--info);
        }

        .event-block.cancelled {
            border-left: 3px solid var(--red);
            opacity: .6;
        }

        .event-title {
            font-weight: 600;
            font-size: .88rem;
            color: var(--text);
        }

        .event-meta {
            font-size: .76rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ── Modals ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9000;
            background: rgba(0, 0, 0, .5);
            backdrop-filter: blur(3px);
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 100%;
            max-width: 560px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .3);
            animation: modalIn .15s ease;
        }

        @keyframes modalIn {
            from {
                transform: translateY(12px);
                opacity: 0;
            }

            to {
                transform: none;
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
        }

        .modal-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text);
        }

        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: var(--surface-2);
            color: var(--text);
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 14px 20px;
            border-top: 1px solid var(--border);
        }

        /* ── Footer ── */
        footer {
            padding: 14px 24px;
            border-top: 1px solid var(--border);
            background: var(--surface);
            font-size: .75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        footer a {
            color: var(--text-muted);
            text-decoration: none;
        }

        footer a:hover {
            color: var(--red);
        }

        .footer-brand span {
            color: var(--red);
            font-weight: 700;
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--black);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--surface-3);
            border-radius: 3px;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: none;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .form-row.cols-2 {
                grid-template-columns: 1fr;
            }

            .topnav-search {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- TOP NAV -->
    <nav class="topnav">
        <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <a class="brand" href="{{ route('admin.dashboard') }}">
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
            <button id="themeToggle" class="icon-btn" title="Toggle light/dark mode" aria-label="Toggle theme">
                <i class="fas fa-sun" id="themeIcon"></i>
            </button>
            <div class="dropdown">
                <a class="user-chip" href="#!" id="userChipBtn">
                    <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    <i class="fas fa-chevron-down" style="font-size:.6rem;color:var(--text-muted);margin-left:2px;"></i>
                </a>
                <div class="dropdown-menu" id="userDropdown">
                    <a href="{{ route('admin.profile') }}"><i class="fas fa-user" style="width:16px;"></i> My Profile</a>
                    <a href="{{ route('admin.settings') }}"><i class="fas fa-gear" style="width:16px;"></i> Settings</a>
                    <a href="#!"><i class="fas fa-clock-rotate-left" style="width:16px;"></i> Activity Log</a>
                    <hr />
                    <a href="{{ route('logout') }}" class="logout"
                        onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                        <i class="fas fa-right-from-bracket" style="width:16px;"></i> Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
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
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
                    <span class="nav-label">Dashboard</span>
                </a>

                <hr class="sidebar-divider" />
                <div class="section-label">Operations</div>

                <!-- BOOKINGS -->
                <button class="nav-link {{ request()->routeIs('admin.bookings.*') ? 'active open' : '' }}"
                    onclick="toggleSub(event,'sub-bookings',this)">
                    <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
                    <span class="nav-label">Bookings</span>
                    <i class="fas fa-chevron-right nav-arrow"></i>
                </button>
                <div class="sub-nav {{ request()->routeIs('admin.bookings.*') ? 'open' : '' }}" id="sub-bookings">
                    <a href="{{ route('admin.bookings.index') }}" class="{{ request()->routeIs('admin.bookings.index') ? 'active-sub' : '' }}">All Bookings</a>
                    <a href="{{ route('admin.bookings.create') }}" class="{{ request()->routeIs('admin.bookings.create') ? 'active-sub' : '' }}">New Booking</a>
                    <a href="{{ route('admin.bookings.schedule') }}" class="{{ request()->routeIs('admin.bookings.schedule') ? 'active-sub' : '' }}">Today's Schedule</a>
                    <a href="{{ route('admin.bookings.cancelled') }}" class="{{ request()->routeIs('admin.bookings.cancelled') ? 'active-sub' : '' }}">Cancelled</a>
                </div>

                <!-- CUSTOMERS -->
                <button class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active open' : '' }}"
                    onclick="toggleSub(event,'sub-customers',this)">
                    <span class="nav-icon"><i class="fas fa-users"></i></span>
                    <span class="nav-label">Customers</span>
                    <i class="fas fa-chevron-right nav-arrow"></i>
                </button>
                <div class="sub-nav {{ request()->routeIs('admin.customers.*') ? 'open' : '' }}" id="sub-customers">
                    <a href="{{ route('admin.customers.index') }}" class="{{ request()->routeIs('admin.customers.index') ? 'active-sub' : '' }}">All Customers</a>
                    <a href="{{ route('admin.customers.create') }}" class="{{ request()->routeIs('admin.customers.create') ? 'active-sub' : '' }}">Add Customer</a>
                    <a href="{{ route('admin.customers.loyalty') }}" class="{{ request()->routeIs('admin.customers.loyalty') ? 'active-sub' : '' }}">Loyalty Members</a>
                </div>

                <!-- MESSAGE TEMPLATES -->
                <a class="nav-link {{ request()->routeIs('admin.templates.*') ? 'active' : '' }}"
                    href="{{ route('admin.templates.index') }}">
                    <span class="nav-icon"><i class="fas fa-envelope-open-text"></i></span>
                    <span class="nav-label">Message Templates</span>
                </a>

                <hr class="sidebar-divider" />
                <div class="section-label">Insights</div>

                <!-- REPORTS -->
                <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
                    href="{{ route('admin.reports.index') }}">
                    <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                    <span class="nav-label">Reports</span>
                </a>

                <hr class="sidebar-divider" />
                <div class="section-label">System</div>

                <a class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings') }}">
                    <span class="nav-icon"><i class="fas fa-gear"></i></span>
                    <span class="nav-label">Settings</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}" href="{{ route('admin.profile') }}">
                    <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
                    <span class="nav-label">My Profile</span>
                </a>
            </div>

            <div class="sidebar-footer">
                <div class="sidebar-footer-info">
                    <div class="label">Logged in as</div>
                    <div class="value">{{ Auth::user()->name }}</div>
                </div>
            </div>
        </nav>

        <!-- MAIN CONTENT -->
        <div class="main-content" id="mainContent">
            <main>
                @yield('content')
            </main>

            <footer>
                <div class="footer-brand"><span>APX</span> AutoMai &mdash; Admin Portal &copy; {{ date('Y') }}</div>
                <div>
                    <a href="#">Privacy Policy</a> &nbsp;&middot;&nbsp;
                    <a href="#">Terms &amp; Conditions</a>
                </div>
            </footer>
        </div>
    </div>

    @yield('modals')

    <!-- SCRIPTS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script>
        // ── Sidebar toggle ──
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });

        // ── Sub-nav toggle ──
        function toggleSub(e, id, link) {
            e.preventDefault();
            const sub = document.getElementById(id);
            const isOpen = sub.classList.contains('open');
            document.querySelectorAll('.sub-nav').forEach(s => s.classList.remove('open'));
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('open'));
            if (!isOpen) {
                sub.classList.add('open');
                link.classList.add('open');
            }
        }

        // ── User dropdown ──
        document.getElementById('userChipBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('userDropdown').classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown'))
                document.getElementById('userDropdown').classList.remove('show');
        });

        // ── Modal helpers ──
        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }
        document.querySelectorAll('.modal-overlay').forEach(o => {
            o.addEventListener('click', e => {
                if (e.target === o) o.classList.remove('open');
            });
        });

        // ── Light / Dark theme ──
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const htmlEl = document.documentElement;

        function applyTheme(mode) {
            if (mode === 'dark') {
                htmlEl.classList.add('dark-mode');
                themeIcon.className = 'fas fa-moon';
                themeToggle.title = 'Switch to light mode';
            } else {
                htmlEl.classList.remove('dark-mode');
                themeIcon.className = 'fas fa-sun';
                themeToggle.title = 'Switch to dark mode';
            }
        }
        // Default = light
        const savedTheme = localStorage.getItem('apx-theme') || 'light';
        applyTheme(savedTheme);

        themeToggle.addEventListener('click', () => {
            const next = htmlEl.classList.contains('dark-mode') ? 'light' : 'dark';
            localStorage.setItem('apx-theme', next);
            applyTheme(next);
        });
    </script>
    @stack('scripts')
</body>

</html>