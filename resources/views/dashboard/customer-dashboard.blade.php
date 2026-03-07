
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>My Dashboard — APX AutoMai</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="{{ asset('assets/css/customer-dashboard.css') }}" rel="stylesheet" />
</head>
<body>

<!-- TOPNAV -->
<nav class="topnav">
    <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
   <a href="{{ url('/') }}" class="brand">
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
                <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                <span class="user-name">{{ Auth::user()->name }}</span>
                <i class="fas fa-chevron-down" style="font-size:0.65rem;color:var(--text-muted);margin-left:4px;"></i>
            </a>
            <div class="dropdown-menu">
                <a href="#!"><i class="fas fa-user" style="width:16px;margin-right:8px;"></i>My Profile</a>
                <a href="#!"><i class="fas fa-car" style="width:16px;margin-right:8px;"></i>My Vehicles</a>
                <a href="#!"><i class="fas fa-gear" style="width:16px;margin-right:8px;"></i>Settings</a>
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
                        <h2>Welcome back, <span>{{ Auth::user()->name }}</span>!</h2>
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
