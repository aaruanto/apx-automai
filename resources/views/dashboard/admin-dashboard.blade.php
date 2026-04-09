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
   <link href="{{ asset('assets/css/dashboard.css') }}" rel="stylesheet" />
    <style>
        /* ── Light mode: override ALL CSS variables used in dashboard.css ── */
        html.light-mode {
            --black:      #f4f5f7;
            --surface:    #ffffff;
            --surface-2:  #f0f1f3;
            --surface-3:  #e6e8ec;
            --border:     rgba(0,0,0,0.09);
            --text:       #1a1d23;
            --text-muted: #6b7280;
            --red-glow:   rgba(232,25,44,0.10);
        }

        .theme-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        *, *::before, *::after {
            transition: background-color 0.25s ease, color 0.18s ease,
                        border-color 0.18s ease, box-shadow 0.18s ease;
        }

        html.light-mode .topnav { box-shadow: 0 1px 6px rgba(0,0,0,.07); }
        html.light-mode #sidebarToggle { color: #6b7280; }
        html.light-mode .brand-auto { color: #1a1d23; }
        html.light-mode .sub-nav { background: #e8eaee !important; }
        html.light-mode .sub-nav a { color: #4b5563; }
        html.light-mode .sub-nav a::before { background: #9ca3af; }
        html.light-mode .sub-nav a:hover { color: #1a1d23; background: #dde0e6; }
        html.light-mode .nav-link { color: #6b7280; }
        html.light-mode .nav-link:hover { color: #1a1d23; }
        html.light-mode .nav-link.active { color: #E8192C; }
        html.light-mode .section-label { color: #9ca3af; }
        html.light-mode .sidebar-divider { border-color: rgba(0,0,0,0.08); }
        html.light-mode .sidebar-footer { border-color: rgba(0,0,0,0.08); }
        html.light-mode .sidebar-footer-info .label { color: #9ca3af; }
        html.light-mode .sidebar-footer-info .value { color: #1a1d23; }
        html.light-mode body { background: #f4f5f7; }
        html.light-mode .stat-card { box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        html.light-mode .stat-value { color: #1a1d23; }
        html.light-mode .chart-card,
        html.light-mode .table-card { box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        html.light-mode .card-body { background: #ffffff; }
        html.light-mode table#datatablesSimple thead tr { background: #f0f1f3; }
        html.light-mode table#datatablesSimple thead th { color: #374151; }
        html.light-mode table#datatablesSimple tbody td { color: #6b7280; }
        html.light-mode table#datatablesSimple tbody td:first-child { color: #1a1d23; }
        html.light-mode table#datatablesSimple tbody tr:hover { background: #f0f1f3; }
        html.light-mode .dataTable-bottom { color: #6b7280; }
        html.light-mode .dataTable-info { color: #6b7280; }
        html.light-mode .dataTable-pagination li a {
            background: #f0f1f3;
            border-color: rgba(0,0,0,0.09);
            color: #6b7280;
        }
        html.light-mode .page-header { border-color: rgba(0,0,0,0.08); }
        html.light-mode .page-title { color: #1a1d23; }
        html.light-mode .breadcrumb li { color: #6b7280; }
        html.light-mode .breadcrumb li.active { color: #E8192C; }
        html.light-mode .page-date { color: #6b7280; }
        html.light-mode .dropdown-menu { box-shadow: 0 4px 16px rgba(0,0,0,.10); }
        html.light-mode .dropdown-menu .logout { color: #E8192C; }
        html.light-mode .user-name { color: #1a1d23; }
        html.light-mode .notif-dot { border-color: #ffffff; }
        html.light-mode ::-webkit-scrollbar-track { background: #f4f5f7; }
        html.light-mode ::-webkit-scrollbar-thumb { background: #d1d5db; }
        html.light-mode footer { background: #ffffff; }
    </style>
</head>
<body>

<!-- TOP NAV -->
<nav class="topnav">
    <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <a class="brand" href="{{ url('/') }}">
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
        <button id="themeToggle" class="icon-btn theme-toggle" title="Toggle light/dark mode" aria-label="Toggle theme">
            <i class="fas fa-moon" id="themeIcon"></i>
        </button>
        <a href="#!" class="icon-btn" title="Activity Log">
            <i class="fas fa-clock-rotate-left"></i>
        </a>
        <div class="dropdown">
            <a class="user-chip" href="#!">
                <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                <span class="user-name">{{ Auth::user()->name }}</span>
                <i class="fas fa-chevron-down" style="font-size:0.65rem;color:var(--text-muted);margin-left:4px;"></i>
            </a>
            <div class="dropdown-menu">
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

<!-- LAYOUT -->
<div class="layout">

    <!-- SIDEBAR -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-body">
            <div class="section-label">Main</div>

            <a class="nav-link active" href="{{ route('admin.dashboard') }}">
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
                <a href="{{ route('admin.bookings.index') }}">All Bookings</a>
                <a href="{{ route('admin.bookings.create') }}">New Booking</a>
                <a href="{{ route('admin.bookings.schedule') }}">Today's Schedule</a>
                <a href="{{ route('admin.bookings.cancelled') }}">Cancelled</a>
            </div>

            <a class="nav-link" href="#" onclick="toggleSub(event,'sub-customers',this)">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                <span class="nav-label">Customers</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </a>
            <div class="sub-nav" id="sub-customers">
                <a href="#">All Customers</a>
                <a href="#">Add Customer</a>
                <a href="#">Loyalty Members</a>
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
    </nav>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <main>
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <h1 class="page-title"><span>APX</span> AUTOMAI</h1>
                    <ol class="breadcrumb">
                        <li>Admin</li>
                        <li class="active">Dashboard</li>
                    </ol>
                </div>
                <div class="page-date">
                    <i class="far fa-calendar" style="margin-right:6px;color:var(--red);"></i>
                    {{ now()->format('l, F j, Y') }}
                </div>
            </div>

            <!-- STAT CARDS -->
            <div class="cards-grid">
                <div class="stat-card primary">
                    <div class="stat-card-header">
                        <div class="stat-label">Today's Bookings</div>
                        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    </div>
                    <div class="stat-value">{{ $todayBookings }}</div>
                    <div class="stat-footer">
                        <a href="#">View Details <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-card-header">
                        <div class="stat-label">Pending</div>
                        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                    <div class="stat-value">{{ $pending }}</div>
                    <div class="stat-footer">
                        <a href="#">View Details <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-card-header">
                        <div class="stat-label">This Week</div>
                        <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                    </div>
                    <div class="stat-value">{{ $thisWeek }}</div>
                    <div class="stat-footer">
                        <a href="#">View Details <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-card-header">
                        <div class="stat-label">Completed</div>
                        <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                    </div>
                    <div class="stat-value">{{ $completed }}</div>
                    <div class="stat-footer">
                        <a href="#">View Details <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
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
                            @forelse($recentBookings as $booking)
                            <tr>
                                <td>#BK-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $booking->customer->name ?? 'N/A' }}</td>
                                <td>{{ $booking->service->name ?? 'N/A' }}</td>
                                <td>{{ $booking->booking_date }}</td>
                                <td>{{ $booking->employee->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-{{ strtolower(str_replace(' ', '', $booking->status)) }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);">No bookings yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- FOOTER -->
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
            labels: {!! json_encode($chartLabels) !!},
            datasets: [{
                label: 'Bookings',
                data: {!! json_encode($chartData) !!},
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
            labels: {!! json_encode($revenueLabels) !!},
            datasets: [{
                label: 'Revenue (₱)',
                data: {!! json_encode($revenueData) !!},
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

    // Dark / Light mode toggle
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon   = document.getElementById('themeIcon');
    const htmlEl      = document.documentElement;

    function applyTheme(mode) {
        if (mode === 'light') {
            htmlEl.classList.add('light-mode');
            themeIcon.className = 'fas fa-sun';
            themeToggle.title = 'Switch to dark mode';
        } else {
            htmlEl.classList.remove('light-mode');
            themeIcon.className = 'fas fa-moon';
            themeToggle.title = 'Switch to light mode';
        }
    }

    const savedTheme = localStorage.getItem('apx-theme') || 'dark';
    applyTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const next = htmlEl.classList.contains('light-mode') ? 'dark' : 'light';
        localStorage.setItem('apx-theme', next);
        applyTheme(next);
    });

    // User dropdown toggle
    const userChip = document.querySelector('.user-chip');
    const dropdownMenu = document.querySelector('.dropdown .dropdown-menu');

    userChip.addEventListener('click', function (e) {
        e.preventDefault();
        dropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dropdown')) {
            dropdownMenu.classList.remove('show');
        }
    });
</script>
</body>
</html>