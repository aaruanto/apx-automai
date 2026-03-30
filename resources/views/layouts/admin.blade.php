<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Dashboard') — APX AutoMai</title>

    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <style>
    :root {
        --red:        #E8192C;
        --red-dark:   #B5101E;
        --red-glow:   rgba(232,25,44,0.18);
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
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Barlow',sans-serif;background:var(--black);color:var(--text);display:flex;flex-direction:column;min-height:100vh;overflow-x:hidden;}

    /* TOPNAV */
    .topnav{position:fixed;top:0;left:0;right:0;height:60px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 24px;z-index:1000;gap:16px;}
    .brand{font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.35rem;letter-spacing:.04em;text-decoration:none;display:flex;align-items:center;gap:10px;white-space:nowrap;}
    .brand-apx{color:var(--red)}.brand-auto{color:var(--text)}
    .brand-badge{background:var(--red);color:#fff;font-size:.55rem;font-weight:700;letter-spacing:.1em;padding:2px 6px;border-radius:2px;}
    #sidebarToggle{background:none;border:none;color:var(--text-muted);font-size:1.1rem;cursor:pointer;padding:6px 8px;border-radius:4px;transition:color .2s,background .2s;margin-right:4px;}
    #sidebarToggle:hover{color:var(--red);background:var(--red-glow);}
    .topnav-search{flex:1;max-width:340px;margin-left:auto;position:relative;}
    .topnav-search input{width:100%;background:var(--surface-3);border:1px solid var(--border);color:var(--text);padding:7px 16px 7px 38px;border-radius:6px;font-size:.85rem;font-family:'Barlow',sans-serif;outline:none;transition:border-color .2s;}
    .topnav-search input:focus{border-color:var(--red);}
    .topnav-search input::placeholder{color:var(--text-muted);}
    .topnav-search .search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.8rem;}
    .topnav-actions{display:flex;align-items:center;gap:8px;margin-left:16px;}
    .icon-btn{background:none;border:1px solid var(--border);color:var(--text-muted);width:36px;height:36px;border-radius:6px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;transition:color .2s,border-color .2s,background .2s;text-decoration:none;font-size:.9rem;}
    .icon-btn:hover{color:var(--red);border-color:var(--red);background:var(--red-glow);}
    .notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;background:var(--red);border-radius:50%;border:1.5px solid var(--surface);}
    .user-chip{display:flex;align-items:center;gap:8px;background:var(--surface-3);border:1px solid var(--border);border-radius:8px;padding:5px 12px 5px 6px;cursor:pointer;text-decoration:none;color:var(--text);position:relative;transition:border-color .2s;}
    .user-chip:hover{border-color:var(--red);}
    .user-avatar{width:28px;height:28px;background:var(--red);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;color:#fff;}
    .user-name{font-size:.82rem;font-weight:500;}
    .dropdown{position:relative;}
    .dropdown-menu{display:none;position:absolute;top:calc(100% + 8px);right:0;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;min-width:160px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.5);z-index:999;}
    .dropdown-menu.show{display:block;}
    .dropdown-menu a{display:block;padding:10px 16px;font-size:.85rem;color:var(--text-muted);text-decoration:none;transition:background .15s,color .15s;}
    .dropdown-menu a:hover{background:var(--surface-3);color:var(--text);}
    .dropdown-menu hr{border:none;border-top:1px solid var(--border);margin:4px 0;}
    .dropdown-menu .logout{color:var(--red);}

    /* LAYOUT */
    .layout{display:flex;padding-top:60px;min-height:100vh;}

    /* SIDEBAR */
    .sidebar{width:240px;min-width:240px;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:60px;bottom:0;left:0;overflow-y:auto;transition:width .25s ease,min-width .25s ease;z-index:900;}
    .sidebar.collapsed{width:60px;min-width:60px;}
    .sidebar.collapsed .nav-label,.sidebar.collapsed .section-label,.sidebar.collapsed .nav-arrow,.sidebar.collapsed .sub-nav,.sidebar.collapsed .sidebar-footer-info{display:none;}
    .sidebar.collapsed .nav-link{justify-content:center;padding:12px;}
    .sidebar-body{flex:1;padding:16px 0;}
    .section-label{font-family:'Barlow Condensed',sans-serif;font-size:.68rem;font-weight:700;letter-spacing:.12em;color:var(--text-muted);padding:16px 20px 6px;text-transform:uppercase;}
    .nav-link{display:flex;align-items:center;gap:12px;padding:10px 20px;color:var(--text-muted);text-decoration:none;font-size:.88rem;font-weight:500;border-left:3px solid transparent;transition:color .2s,background .2s,border-color .2s;cursor:pointer;position:relative;}
    .nav-link:hover{color:var(--text);background:var(--surface-2);}
    .nav-link.active{color:var(--red);background:var(--red-glow);border-left-color:var(--red);}
    .nav-icon{width:18px;text-align:center;font-size:.9rem;flex-shrink:0;}
    .nav-label{flex:1;}
    .nav-arrow{font-size:.7rem;transition:transform .2s;}
    .nav-link.open .nav-arrow{transform:rotate(90deg);}
    .sub-nav{display:none;background:var(--black);}
    .sub-nav.open{display:block;}
    .sub-nav a{display:flex;align-items:center;gap:8px;padding:8px 20px 8px 50px;color:var(--text-muted);text-decoration:none;font-size:.83rem;transition:color .2s,background .2s;}
    .sub-nav a:hover{color:var(--text);background:var(--surface-2);}
    .sub-nav a.active{color:var(--red);}
    .sub-nav a::before{content:'';width:4px;height:4px;background:var(--text-muted);border-radius:50%;flex-shrink:0;}
    .sub-nav a.active::before{background:var(--red);}
    .sidebar-divider{border:none;border-top:1px solid var(--border);margin:8px 0;}
    .sidebar-footer{padding:16px 20px;border-top:1px solid var(--border);}
    .sidebar-footer-info .label{font-size:.7rem;color:var(--text-muted);}
    .sidebar-footer-info .value{font-size:.85rem;font-weight:600;color:var(--text);}

    /* MAIN */
    .main-content{flex:1;margin-left:240px;transition:margin-left .25s ease;display:flex;flex-direction:column;min-height:calc(100vh - 60px);}
    .main-content.expanded{margin-left:60px;}
    main{flex:1;padding:28px 32px;}

    /* PAGE HEADER */
    .page-header{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:28px;padding-bottom:20px;border-bottom:1px solid var(--border);}
    .page-title{font-family:'Barlow Condensed',sans-serif;font-size:2rem;font-weight:800;letter-spacing:.03em;line-height:1;}
    .page-title span{color:var(--red);}
    .breadcrumb{display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text-muted);margin-top:6px;list-style:none;}
    .breadcrumb li+li::before{content:'/';margin-right:6px;opacity:.4;}
    .breadcrumb .active{color:var(--red);}

    /* CARDS */
    .card{background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;}
    .card-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);}
    .card-header-title{display:flex;align-items:center;gap:10px;font-family:'Barlow Condensed',sans-serif;font-size:1rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;}
    .card-header-title i{color:var(--red);font-size:.9rem;}
    .card-body{padding:20px;}
    .card-footer-bar{padding:12px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:.8rem;color:var(--text-muted);}

    /* BADGES */
    .badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:600;letter-spacing:.04em;}
    .badge::before{content:'';width:6px;height:6px;border-radius:50%;flex-shrink:0;}
    .badge-confirmed{background:rgba(34,197,94,.12);color:var(--success)}.badge-confirmed::before{background:var(--success);}
    .badge-pending{background:rgba(245,158,11,.12);color:var(--warning)}.badge-pending::before{background:var(--warning);}
    .badge-cancelled{background:rgba(232,25,44,.12);color:var(--red)}.badge-cancelled::before{background:var(--red);}
    .badge-inprogress{background:rgba(59,130,246,.12);color:var(--info)}.badge-inprogress::before{background:var(--info);}

    /* BUTTONS */
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:6px;font-size:.85rem;font-weight:600;font-family:'Barlow',sans-serif;cursor:pointer;text-decoration:none;border:none;transition:all .2s;}
    .btn-primary{background:var(--red);color:#fff;}.btn-primary:hover{background:var(--red-dark);}
    .btn-ghost{background:transparent;color:var(--text-muted);border:1px solid var(--border);}.btn-ghost:hover{color:var(--text);border-color:rgba(255,255,255,.2);background:var(--surface-2);}
    .btn-danger{background:rgba(232,25,44,.15);color:var(--red);border:1px solid rgba(232,25,44,.3);}.btn-danger:hover{background:var(--red);color:#fff;}
    .btn-sm{padding:6px 12px;font-size:.78rem;}
    .btn-icon{width:32px;height:32px;padding:0;justify-content:center;}

    /* FORMS */
    .form-group{margin-bottom:20px;}
    .form-label{display:block;font-size:.78rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px;}
    .form-control{width:100%;background:var(--surface-2);border:1px solid var(--border);color:var(--text);padding:10px 14px;border-radius:6px;font-size:.88rem;font-family:'Barlow',sans-serif;outline:none;transition:border-color .2s;}
    .form-control:focus{border-color:var(--red);}
    .form-control::placeholder{color:var(--text-muted);}
    select.form-control option{background:var(--surface-2);}
    textarea.form-control{resize:vertical;min-height:90px;}
    .form-row{display:grid;gap:16px;}
    .form-row.cols-2{grid-template-columns:1fr 1fr;}
    .form-row.cols-3{grid-template-columns:1fr 1fr 1fr;}
    .form-hint{font-size:.75rem;color:var(--text-muted);margin-top:5px;}

    /* TABLE */
    .table-wrap{overflow-x:auto;}
    table.apx-table{width:100%;border-collapse:collapse;font-size:.85rem;}
    table.apx-table thead tr{background:var(--surface-2);border-bottom:1px solid var(--border);}
    table.apx-table thead th{padding:12px 16px;font-family:'Barlow Condensed',sans-serif;font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);text-align:left;white-space:nowrap;}
    table.apx-table tbody tr{border-bottom:1px solid var(--border);transition:background .15s;}
    table.apx-table tbody tr:last-child{border-bottom:none;}
    table.apx-table tbody tr:hover{background:var(--surface-2);}
    table.apx-table tbody td{padding:13px 16px;color:var(--text-muted);}
    .primary-col{font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:.92rem;color:var(--text);letter-spacing:.04em;}

    /* FILTERS */
    .filters-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid var(--border);background:var(--surface-2);}
    .filter-select,.filter-input{background:var(--surface-3);border:1px solid var(--border);color:var(--text);padding:7px 12px;border-radius:6px;font-size:.83rem;font-family:'Barlow',sans-serif;outline:none;transition:border-color .2s;}
    .filter-select:focus,.filter-input:focus{border-color:var(--red);}
    .filter-select option{background:var(--surface-3);}
    .filter-input{min-width:220px;}
    .filter-input::placeholder{color:var(--text-muted);}
    .filters-bar .spacer{flex:1;}

    /* FOOTER */
    footer{padding:16px 32px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:.78rem;color:var(--text-muted);}
    footer a{color:var(--text-muted);text-decoration:none;}
    footer a:hover{color:var(--red);}
    .footer-brand span{color:var(--red);font-weight:700;}

    /* SCROLLBAR */
    ::-webkit-scrollbar{width:6px;height:6px;}
    ::-webkit-scrollbar-track{background:var(--black);}
    ::-webkit-scrollbar-thumb{background:var(--surface-3);border-radius:3px;}
    ::-webkit-scrollbar-thumb:hover{background:var(--red);}

    /* SCHEDULE */
    .timeline{display:flex;flex-direction:column;gap:0;}
    .time-slot{display:grid;grid-template-columns:80px 1fr;gap:0;min-height:64px;}
    .time-label{padding:12px 16px 12px 0;font-family:'Barlow Condensed',sans-serif;font-size:.85rem;font-weight:700;color:var(--text-muted);text-align:right;border-right:2px solid var(--border);white-space:nowrap;position:relative;top:-2px;}
    .time-events{padding:8px 0 8px 16px;display:flex;flex-direction:column;gap:6px;border-bottom:1px solid rgba(255,255,255,.03);}
    .event-block{background:var(--surface-2);border:1px solid var(--border);border-left:3px solid var(--red);border-radius:6px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;gap:12px;transition:border-color .2s,transform .15s;}
    .event-block:hover{border-color:var(--red);transform:translateX(2px);}
    .event-block.confirmed{border-left-color:var(--success);}
    .event-block.pending{border-left-color:var(--warning);}
    .event-block.inprogress{border-left-color:var(--info);}
    .event-title{font-size:.88rem;font-weight:600;color:var(--text);}
    .event-meta{font-size:.78rem;color:var(--text-muted);margin-top:2px;}

    /* MODAL */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:2000;align-items:center;justify-content:center;}
    .modal-overlay.open{display:flex;}
    .modal{background:var(--surface);border:1px solid var(--border);border-radius:12px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.8);}
    .modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border);}
    .modal-title{font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:800;letter-spacing:.04em;}
    .modal-close{background:none;border:none;color:var(--text-muted);font-size:1.1rem;cursor:pointer;padding:4px 8px;border-radius:4px;}
    .modal-close:hover{color:var(--red);}
    .modal-body{padding:24px;}
    .modal-footer{padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px;}

    @media(max-width:768px){
        .sidebar{width:60px;min-width:60px;}
        .sidebar .nav-label,.sidebar .section-label,.sidebar .nav-arrow,.sidebar .sub-nav,.sidebar .sidebar-footer-info{display:none;}
        .sidebar .nav-link{justify-content:center;padding:12px;}
        .main-content{margin-left:60px;}
        main{padding:20px 16px;}
        .form-row.cols-2,.form-row.cols-3{grid-template-columns:1fr;}
    }
    </style>

    @stack('styles')
</head>
<body>

{{-- TOPNAV --}}
<nav class="topnav">
    <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <a class="brand" href="{{ route('admin.dashboard') }}">
        <span class="brand-apx">APX</span>
        <span class="brand-auto">AutoMai</span>
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
            <a class="user-chip" href="#!" id="userChip">
                <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                <span class="user-name">{{ Auth::user()->name }}</span>
                <i class="fas fa-chevron-down" style="font-size:.65rem;color:var(--text-muted);margin-left:4px;"></i>
            </a>
            <div class="dropdown-menu" id="userDropdown">
                <a href="#!"><i class="fas fa-user-cog" style="width:16px;margin-right:8px;"></i>Settings</a>
                <a href="#!"><i class="fas fa-history" style="width:16px;margin-right:8px;"></i>Activity Log</a>
                <hr />
                <a href="{{ route('logout') }}" class="logout"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-right-from-bracket" style="width:16px;margin-right:8px;"></i>Logout
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</nav>

{{-- LAYOUT --}}
<div class="layout">

    {{-- SIDEBAR --}}
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-body">

            <div class="section-label">Main</div>
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}">
                <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
                <span class="nav-label">Dashboard</span>
            </a>

            <hr class="sidebar-divider" />
            <div class="section-label">Operations</div>

            <a class="nav-link {{ request()->routeIs('admin.bookings.*') ? 'open' : '' }}"
               href="#" onclick="toggleSub(event,'sub-bookings',this)">
                <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
                <span class="nav-label">Bookings</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </a>
            <div class="sub-nav {{ request()->routeIs('admin.bookings.*') ? 'open' : '' }}" id="sub-bookings">
                <a href="{{ route('admin.bookings.index') }}"    class="{{ request()->routeIs('admin.bookings.index')    ? 'active' : '' }}">All Bookings</a>
                <a href="{{ route('admin.bookings.create') }}"   class="{{ request()->routeIs('admin.bookings.create')   ? 'active' : '' }}">New Booking</a>
                <a href="{{ route('admin.bookings.schedule') }}" class="{{ request()->routeIs('admin.bookings.schedule') ? 'active' : '' }}">Today's Schedule</a>
                <a href="{{ route('admin.bookings.cancelled') }}" class="{{ request()->routeIs('admin.bookings.cancelled') ? 'active' : '' }}">Cancelled</a>
            </div>

            <a class="nav-link {{ request()->routeIs('admin.customers.*') ? 'open' : '' }}"
               href="#" onclick="toggleSub(event,'sub-customers',this)">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                <span class="nav-label">Customers</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </a>
            <div class="sub-nav {{ request()->routeIs('admin.customers.*') ? 'open' : '' }}" id="sub-customers">
                <a href="#">All Customers</a>
                <a href="#">Add Customer</a>
                <a href="#">Loyalty Members</a>
            </div>

            <hr class="sidebar-divider" />
            <div class="section-label">Insights</div>

            <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'open' : '' }}"
               href="#" onclick="toggleSub(event,'sub-reports',this)">
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

            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'open' : '' }}"
               href="#" onclick="toggleSub(event,'sub-settings',this)">
                <span class="nav-icon"><i class="fas fa-gear"></i></span>
                <span class="nav-label">Settings</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </a>
            <div class="sub-nav" id="sub-settings">
                <a href="#">General</a>
                <a href="#">Staff Accounts</a>
                <a href="#">Services &amp; Pricing</a>
                <a href="#">Notifications</a>
            </div>

        </div>
    </nav>

    {{-- MAIN CONTENT --}}
    <div class="main-content" id="mainContent">
        <main>
            @yield('content')
        </main>

        <footer>
            <div class="footer-brand">
                <span>APX</span> AutoMai &mdash; Admin Portal &copy; {{ date('Y') }}
            </div>
            <div>
                <a href="#">Privacy Policy</a>
                &nbsp;&middot;&nbsp;
                <a href="#">Terms &amp; Conditions</a>
            </div>
        </footer>
    </div>

</div>{{-- /.layout --}}

{{-- MODALS --}}
@yield('modals')

{{-- SCRIPTS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script>
// Sidebar toggle
document.getElementById('sidebarToggle').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('mainContent').classList.toggle('expanded');
});

// Sub-nav toggle
function toggleSub(e, id, link) {
    e.preventDefault();
    const sub  = document.getElementById(id);
    const isOpen = sub.classList.contains('open');
    document.querySelectorAll('.sub-nav').forEach(s => s.classList.remove('open'));
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('open'));
    if (!isOpen) { sub.classList.add('open'); link.classList.add('open'); }
}

// Modal helpers
function openModal(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});

// User dropdown
const userChip     = document.getElementById('userChip');
const userDropdown = document.getElementById('userDropdown');
userChip.addEventListener('click', e => {
    e.preventDefault();
    userDropdown.classList.toggle('show');
});
document.addEventListener('click', e => {
    if (!e.target.closest('.dropdown')) userDropdown.classList.remove('show');
});
</script>

@stack('scripts')

</body>
</html>