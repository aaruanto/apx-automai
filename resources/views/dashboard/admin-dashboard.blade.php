
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
                <a href="/bookings/index.php">All Bookings</a>
                <a href="/bookings/new.php">New Booking</a>
                <a href="/bookings/schedule.php">Today's Schedule</a>
                <a href="/bookings/cancelled.php">Cancelled</a>
            </div>

            <a class="nav-link" href="#" onclick="toggleSub(event,'sub-customers',this)">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                <span class="nav-label">Customers</span>
                <i class="fas fa-chevron-right nav-arrow"></i>
            </a>
            <div class="sub-nav" id="sub-customers">
                <a href="/customers/index.php">All Customers</a>
                <a href="/customers/new.php">Add Customer</a>
                <a href="/customers/loyalty.php">Loyalty Members</a>
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
                <div class="value">{{ Auth::user()->name }}</div>
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
