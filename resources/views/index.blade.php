<!DOCTYPE html>
<html lang="en">
<!-- smooth scroll for anchor nav -->
<style>html { scroll-behavior: smooth; }</style>

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>APX AUTOMAI</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="/assets/img/favicon.ico">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@600;700&family=Ubuntu:wght@400;500&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="/assets/lib/animate/animate.min.css" rel="stylesheet">
    <link href="/assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="/assets/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="/assets/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->


    <!-- Sticky Book CTA Start -->
    <style>
        .apx-sticky-card {
            position: fixed;
            right: 24px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1040;
            width: 300px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
            padding: 28px 24px 24px;
            border-top: 4px solid #e63946;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }
        .apx-sticky-card h5 {
            font-family: 'Barlow', sans-serif;
            font-weight: 700;
            font-size: 1.15rem;
            color: #1a1a1a;
            margin-bottom: 6px;
            line-height: 1.3;
        }
        .apx-sticky-card p {
            font-size: 0.8rem;
            color: #888;
            margin-bottom: 16px;
        }
        .apx-sticky-card .form-select,
        .apx-sticky-card .form-control {
            font-size: 0.82rem;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            margin-bottom: 10px;
            padding: 9px 12px;
            color: #333;
            font-family: 'Ubuntu', sans-serif;
        }
        .apx-sticky-card .form-select:focus,
        .apx-sticky-card .form-control:focus {
            border-color: #e63946;
            box-shadow: 0 0 0 0.15rem rgba(230,57,70,0.15);
        }
        .apx-sticky-card .apx-sc-btn {
            width: 100%;
            background: #e63946;
            color: #fff;
            font-family: 'Barlow', sans-serif;
            font-weight: 700;
            font-size: 0.88rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: none;
            border-radius: 6px;
            padding: 12px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 4px;
        }
        .apx-sticky-card .apx-sc-btn:hover { background: #c0392b; }
        .apx-sticky-card .apx-sc-trust {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #f0f0f0;
        }
        .apx-sticky-card .apx-sc-trust-item {
            text-align: center;
            flex: 1;
        }
        .apx-sticky-card .apx-sc-trust-item:first-child {
            border-right: 1px solid #eee;
        }
        .apx-sticky-card .apx-sc-trust-val {
            font-family: 'Barlow', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            color: #1a1a1a;
        }
        .apx-sticky-card .apx-sc-trust-val i { color: #28a745; font-size: 0.75rem; }
        .apx-sticky-card .apx-sc-trust-lbl {
            font-size: 0.68rem;
            color: #aaa;
            display: block;
            margin-top: 2px;
        }
        @media (max-width: 1200px) {
            .apx-sticky-card { display: none; }
        }
    </style>
    <div class="apx-sticky-card">
        <h5>Experience The Best<br>Auto Service In QC</h5>
        <p>Get instant quotes for your vehicle</p>
        <select class="form-select" id="apx-sc-vtype" onchange="apxStickyVtype(this.value)">
            <option value="car">Car</option>
            <option value="motorcycle">Motorcycle</option>
        </select>
        <select class="form-select" id="apx-sc-make">
            <option value="">Select manufacturer</option>
        </select>
        <select class="form-select" id="apx-sc-model" disabled>
            <option value="">Select model</option>
        </select>
        <button class="apx-sc-btn" onclick="apxStickyBook()">
            Check Prices For Free &nbsp;›
        </button>
        <div class="apx-sc-trust">
            <div class="apx-sc-trust-item">
                <div class="apx-sc-trust-val"><i class="fa fa-star"></i> 4.8<span style="font-size:0.7rem;color:#aaa;">/5</span></div>
                <span class="apx-sc-trust-lbl">Based on 500+<br>Reviews</span>
            </div>
            <div class="apx-sc-trust-item">
                <div class="apx-sc-trust-val">5,000+</div>
                <span class="apx-sc-trust-lbl">Happy<br>Customers</span>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var SC_VEHICLES = {
            car: {
                Toyota:['Vios','Innova','Fortuner','Hiace','Camry','Corolla Cross','Rush'],
                Honda:['City','Civic','BR-V','CR-V','HR-V','Jazz','Brio'],
                Mitsubishi:['Mirage','Mirage G4','Montero Sport','Xpander','L300','Strada'],
                Suzuki:['Alto','Celerio','Ertiga','Jimny','Swift','Dzire'],
                Ford:['EcoSport','Everest','Explorer','Ranger','Territory'],
                Hyundai:['Accent','Tucson','Santa Fe','Reina','Stargazer'],
                Nissan:['Almera','Navara','Terra','X-Trail'],
                Kia:['Soluto','Stonic','Sportage','Carnival']
            },
            motorcycle: {
                Honda:['Click 125i','Click 150i','BeAT','PCX 160','ADV 160','CBR150R','XRM 125'],
                Yamaha:['Mio i 125','Mio Gear','NMAX','Aerox','Sniper 150','FZ-S'],
                Kawasaki:['Barako II','CT100B','Rouser NS 200','Ninja 400'],
                Suzuki:['Skydrive 125','Raider R150','GSX-R150'],
                TVS:['Apache RTR 160','Ntorq 125','King Duramax'],
                Kymco:['Agility 125','Like 150i']
            }
        };
        var scVtype = 'car';

        function scPopMakes() {
            var sel = document.getElementById('apx-sc-make');
            sel.innerHTML = '<option value="">Select manufacturer</option>';
            Object.keys(SC_VEHICLES[scVtype]).forEach(function(m){
                var o = document.createElement('option'); o.value = m; o.textContent = m; sel.appendChild(o);
            });
            document.getElementById('apx-sc-model').innerHTML = '<option value="">Select model</option>';
            document.getElementById('apx-sc-model').disabled = true;
        }

        window.apxStickyVtype = function(t) {
            scVtype = t;
            scPopMakes();
        };

        document.getElementById('apx-sc-make').addEventListener('change', function() {
            var make = this.value;
            var mSel = document.getElementById('apx-sc-model');
            mSel.innerHTML = '<option value="">Select model</option>';
            mSel.disabled = !make;
            if (make && SC_VEHICLES[scVtype][make]) {
                SC_VEHICLES[scVtype][make].forEach(function(m){
                    var o = document.createElement('option'); o.value = m; o.textContent = m; mSel.appendChild(o);
                });
            }
        });

        window.apxStickyBook = function() {
            var vt    = document.getElementById('apx-sc-vtype').value;
            var make  = document.getElementById('apx-sc-make').value;
            var model = document.getElementById('apx-sc-model').value;
            if (!make) { alert('Please select a manufacturer first.'); return; }
            // Open modal then pre-fill vehicle on step 2
            var modal = new bootstrap.Modal(document.getElementById('bookingModal'));
            modal.show();
            // Pre-fill vehicle after modal opens and grid renders
            setTimeout(function() {
                if (typeof apxPreFillVehicle === 'function') apxPreFillVehicle(vt, make, model);
                // Jump straight to step 1 (services) — vehicle will be pre-filled when they reach step 2
            }, 200);
        };

        scPopMakes();
    })();
    </script>
    <!-- Sticky Book CTA End -->

    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <a href="#home" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <h2 class="m-0 text-primary"><img src="/assets/img\apx-black-logo.png" alt="apx logo" width="100" height="120">AUTOMAI</h2>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="#home" class="nav-item nav-link active">Home</a>
                <a href="#services" class="nav-item nav-link">Services</a>
                <a href="#location" class="nav-item nav-link">Location</a>
                <a href="#how-it-works" class="nav-item nav-link">How It Works</a>
                <a href="#testimonials" class="nav-item nav-link">Reviews</a>
                <a href="#about" class="nav-item nav-link">About</a>
            </div>
            <a href="{{ route('login') }}" class="btn btn-primary py-4 px-lg-5 d-none d-lg-block">Login</a>
        </div>
    </nav>
    <!-- Navbar End -->


<!-- Carousel Start -->
<div id="home" class="container-fluid p-0 mb-5">
    <div id="header-carousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img class="w-100" src="/assets/img/carousel-bg-1.jpg" alt="Image">
                <div class="carousel-caption d-flex align-items-center">
                    <div class="container">
                        <div class="row align-items-center justify-content-center justify-content-lg-start">
                            <div class="col-10 col-lg-7 text-center text-lg-start">
                                <h6 class="text-white text-uppercase mb-3 animated slideInDown">// APX Automai //</h6>
                                <h1 class="display-3 text-white mb-4 pb-3 animated slideInDown">Your Trusted Auto Service Center in Quezon City</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <img class="w-100" src="/assets/img/carousel-bg-2.jpg" alt="Image">
                <div class="carousel-caption d-flex align-items-center">
                    <div class="container">
                        <div class="row align-items-center justify-content-center justify-content-lg-start">
                            <div class="col-10 col-lg-7 text-center text-lg-start">
                                <h6 class="text-white text-uppercase mb-3 animated slideInDown">APX Automai</h6>
                                <h1 class="display-3 text-white mb-4 pb-3 animated slideInDown">Engine Care, CVT Service & More — Done Right, Every Time</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Carousel End -->


    <!-- Page + Sticky Card Layout Wrapper Start -->
    <style>
        @media (min-width: 1200px) {
            .apx-page-wrap {
                display: grid;
                grid-template-columns: 1fr 348px;
                align-items: start;
                overflow-x: hidden;
            }
            .apx-page-content {
                grid-column: 1 / 2;
                min-width: 0;
                overflow: hidden;
            }
            .apx-full-bleed {
                grid-column: 1 / 2; /* stays within left column — the container inside constrains it */
            }
            .apx-page-sidebar {
                grid-column: 2 / 3;
                grid-row: 1 / 999;
                min-height: 100px;
            }
        }
        @media (max-width: 1199px) {
            .apx-page-sidebar { display: none !important; }
        }
        /* Left-align all section headers site-wide */
        .apx-section-header { text-align: left !important; }
        .apx-section-header * { text-align: left !important; }
    </style>
    <div class="apx-page-wrap">
    <div class="apx-page-content">

    <!-- ============================================================
         1. SERVICES
    ============================================================ -->
    <style>
        .apx-section { background: #fff; padding: 60px 0; }
        .apx-filter-bar { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:36px; justify-content: flex-start; }
        .apx-filter-btn { background:#fff; color:#444; border:2px solid #ddd; border-radius:999px; padding:7px 20px; font-size:0.85rem; font-weight:600; cursor:pointer; transition:all 0.25s ease; }
        .apx-filter-btn:hover, .apx-filter-btn.active { background:#e63946; color:#fff; border-color:#e63946; }
        .apx-cards-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:20px; }
        .apx-svc-card { background:#fff; border:1px solid #e8e8e8; border-radius:14px; padding:24px 22px 20px; cursor:pointer; transition:transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease; display:flex; flex-direction:column; text-decoration:none; color:inherit; box-shadow:0 2px 10px rgba(0,0,0,0.06); }
        .apx-svc-card:hover { transform:translateY(-5px); box-shadow:0 12px 32px rgba(230,57,70,0.18); border-color:#e63946; text-decoration:none; color:inherit; }
        .apx-svc-cat { font-size:0.68rem; font-weight:700; letter-spacing:0.08em; color:#999; text-transform:uppercase; margin-bottom:18px; }
        .apx-svc-icon-wrap { font-size:2.4rem; color:#e63946; margin-bottom:18px; }
        .apx-svc-name { font-size:1rem; font-weight:700; color:#e63946; margin-bottom:10px; }
        .apx-svc-desc { font-size:0.82rem; color:#666; line-height:1.6; flex-grow:1; }
        .apx-svc-meta { display:flex; align-items:center; gap:6px; margin-top:18px; font-size:0.78rem; color:#999; border-top:1px solid #f0f0f0; padding-top:14px; }
        .apx-svc-card.hidden { display:none; }
    </style>

    <div id="services" class="apx-section">
        <div class="container">
            <div class="apx-section-header wow fadeInUp mb-4" data-wow-delay="0.1s">
                <h6 class="text-primary text-uppercase mb-2">What We Offer</h6>
                <h1 class="mb-2">Our Services</h1>
                <p class="text-muted">Click any service to book — no account needed</p>
            </div>
            <div class="apx-filter-bar wow fadeInUp" data-wow-delay="0.2s">
                <button class="apx-filter-btn active" data-filter="all">All Services</button>
                <button class="apx-filter-btn" data-filter="engine">Engine &amp; Oil</button>
                <button class="apx-filter-btn" data-filter="cvt">CVT &amp; Transmission</button>
                <button class="apx-filter-btn" data-filter="inspection">Inspection</button>
                <button class="apx-filter-btn" data-filter="cleaning">Cleaning</button>
            </div>
            <div class="apx-cards-grid wow fadeInUp" data-wow-delay="0.3s">

                <a href="#" class="apx-svc-card" data-cat="engine" onclick="apxOpenWithService(event, 'Change Oil &amp; Filter')">
                    <div class="apx-svc-cat">Engine &amp; Oil</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-oil-can"></i></div>
                    <div class="apx-svc-name">Change Oil &amp; Filter</div>
                    <div class="apx-svc-desc">Complete engine oil drain and refill with high-quality oil and a fresh filter for optimal engine performance.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 30–45 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="engine" onclick="apxOpenWithService(event, 'Fuel Injection Cleaning')">
                    <div class="apx-svc-cat">Engine &amp; Oil</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-gas-pump"></i></div>
                    <div class="apx-svc-name">Fuel Injection Cleaning</div>
                    <div class="apx-svc-desc">Deep cleaning of fuel injectors to restore proper fuel atomization, improving throttle response and fuel economy.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 45–60 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="engine cleaning" onclick="apxOpenWithService(event, 'Throttle Body Cleaning')">
                    <div class="apx-svc-cat">Engine &amp; Oil</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-wind"></i></div>
                    <div class="apx-svc-name">Throttle Body Cleaning</div>
                    <div class="apx-svc-desc">Remove carbon buildup and deposits from the throttle body for smoother idling and improved acceleration.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 30–45 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="engine" onclick="apxOpenWithService(event, 'Throttle Idle Adjustment')">
                    <div class="apx-svc-cat">Engine &amp; Oil</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-sliders-h"></i></div>
                    <div class="apx-svc-name">Throttle Idle Adjustment</div>
                    <div class="apx-svc-desc">Fine-tune idle speed to manufacturer specs, eliminating rough idle and stalling at traffic stops.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="engine" onclick="apxOpenWithService(event, 'Valve Clearance Adjustment / Tune-up')">
                    <div class="apx-svc-cat">Engine &amp; Oil</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-cogs"></i></div>
                    <div class="apx-svc-name">Valve Clearance Adjustment / Tune-up</div>
                    <div class="apx-svc-desc">Inspect and adjust valve clearances to ensure proper engine breathing, reducing noise and wear.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 60–90 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="cvt inspection cleaning" onclick="apxOpenWithService(event, 'CVT Cleaning and Inspection')">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-sync-alt"></i></div>
                    <div class="apx-svc-name">CVT Cleaning and Inspection</div>
                    <div class="apx-svc-desc">Full CVT belt and pulley inspection with cleaning to maintain smooth, efficient power transfer.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 60–90 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="inspection" onclick="apxOpenWithService(event, 'Airfilter Inspection')">
                    <div class="apx-svc-cat">Inspection</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-search"></i></div>
                    <div class="apx-svc-name">Airfilter Inspection</div>
                    <div class="apx-svc-desc">Check air filter condition and airflow restriction to ensure the engine receives clean, unrestricted air.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 10–15 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="inspection" onclick="apxOpenWithService(event, 'Airfilter Installation')">
                    <div class="apx-svc-cat">Inspection</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-tools"></i></div>
                    <div class="apx-svc-name">Airfilter Installation</div>
                    <div class="apx-svc-desc">Replacement and installation of a new air filter to restore optimal engine airflow and protect internal components.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 10–20 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="cvt inspection" onclick="apxOpenWithService(event, 'Flyball Inspection')">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-circle-notch"></i></div>
                    <div class="apx-svc-name">Flyball Inspection</div>
                    <div class="apx-svc-desc">Inspect flyball weights for wear and deformation that can affect CVT engagement and acceleration response.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="cvt cleaning" onclick="apxOpenWithService(event, 'Flyball Cleaning')">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-broom"></i></div>
                    <div class="apx-svc-name">Flyball Cleaning</div>
                    <div class="apx-svc-desc">Remove dirt and grease buildup from flyball components to restore precise CVT engagement and smooth power delivery.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="cvt inspection" onclick="apxOpenWithService(event, 'V-belt Inspection')">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-ruler-combined"></i></div>
                    <div class="apx-svc-name">V-belt Inspection</div>
                    <div class="apx-svc-desc">Measure V-belt width and check for cracks or fraying to prevent slippage and unexpected CVT failure.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 15–25 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="cvt cleaning" onclick="apxOpenWithService(event, 'V-belt Cleaning')">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-magic"></i></div>
                    <div class="apx-svc-name">V-belt Cleaning</div>
                    <div class="apx-svc-desc">Deep clean the V-belt and surrounding components to remove residue that causes slipping and reduces belt lifespan.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="cvt inspection" onclick="apxOpenWithService(event, 'Pulley Set Inspection')">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-cog"></i></div>
                    <div class="apx-svc-name">Pulley Set Inspection</div>
                    <div class="apx-svc-desc">Check primary and secondary pulleys for wear and proper movement to ensure efficient power transmission.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="cvt cleaning" onclick="apxOpenWithService(event, 'Pulley Set Cleaning')">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-spray-can"></i></div>
                    <div class="apx-svc-name">Pulley Set Cleaning</div>
                    <div class="apx-svc-desc">Thorough cleaning of pulley surfaces and grooves to remove metal dust and contaminants affecting CVT performance.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 25–35 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="cvt inspection" onclick="apxOpenWithService(event, 'Torque Drive Assy Inspection')">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-car-side"></i></div>
                    <div class="apx-svc-name">Torque Drive Assy Inspection</div>
                    <div class="apx-svc-desc">Inspect the torque drive assembly for wear, proper engagement, and signs of damage that affect drivetrain efficiency.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 25–35 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="cvt cleaning" onclick="apxOpenWithService(event, 'Torque Drive Assy Cleaning')">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-shower"></i></div>
                    <div class="apx-svc-name">Torque Drive Assy Cleaning</div>
                    <div class="apx-svc-desc">Remove built-up grime and contaminants from the torque drive assembly to maintain reliable drivetrain operation.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 30–40 min</div>
                </a>

                <a href="#" class="apx-svc-card" data-cat="cvt" onclick="apxOpenWithService(event, 'Torque Drive Assy Greasing')">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-tint"></i></div>
                    <div class="apx-svc-name">Torque Drive Assy Greasing</div>
                    <div class="apx-svc-desc">Apply fresh grease to torque drive assembly components to reduce friction, heat, and premature wear.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

            </div>
        </div>
    </div>
    <!-- Services (cards) End -->


    <!-- ============================================================
         2. LOCATION — Google Maps + Shop Photos
    ============================================================ -->
    <div id="location" class="apx-section" style="background:#f8f9fa;">
        <div class="container">
            <div class="apx-section-header mb-5">
                <h6 class="text-primary text-uppercase mb-2">Find Us</h6>
                <h1>Our Location</h1>
                <p class="text-muted">Come visit us or book online — we'll take care of the rest.</p>
            </div>
            <div class="row g-4 align-items-stretch">
                <!-- Map -->
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="h-100" style="min-height:320px; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.10);">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.4!2d121.0244!3d14.6760!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTTCsDQwJzMzLjYiTiAxMjHCsDAxJzI3LjgiRQ!5e0!3m2!1sen!2sph!4v1"
                            width="100%" height="100%" style="border:0; min-height:320px;" allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
                <!-- Shop Info + Photo Placeholders -->
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="d-flex flex-column h-100 gap-3">
                        <!-- Address card -->
                        <div class="bg-light p-4" style="border-radius:12px; border-left:4px solid #e63946;">
                            <h5 class="fw-bold mb-3" style="font-family:'Barlow',sans-serif;">APX Automai Service Center</h5>
                            <p class="mb-2"><i class="fa fa-map-marker-alt text-primary me-2"></i>5 Glenn St., Quezon City</p>
                            <p class="mb-2"><i class="fa fa-clock text-primary me-2"></i>Mon – Fri: 9:00 AM – 9:00 PM &nbsp;|&nbsp; Sat – Sun: 9:00 AM – 12:00 PM</p>
                            <p class="mb-0"><i class="fa fa-phone-alt text-primary me-2"></i>+012 345 6789</p>
                        </div>
                        <!-- Shop photo thumbnails -->
                        <div class="row g-2 flex-grow-1">
                            <div class="col-6">
                                <div class="w-100 h-100 bg-secondary" style="min-height:120px; border-radius:10px; overflow:hidden;">
                                    <img src="/assets/img/about.jpg" alt="Shop photo 1" class="w-100 h-100" style="object-fit:cover;">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="w-100 h-100 bg-secondary" style="min-height:120px; border-radius:10px; overflow:hidden;">
                                    <img src="/assets/img/carousel-bg-1.jpg" alt="Shop photo 2" class="w-100 h-100" style="object-fit:cover;">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="w-100 h-100 bg-secondary" style="min-height:120px; border-radius:10px; overflow:hidden;">
                                    <img src="/assets/img/carousel-bg-2.jpg" alt="Shop photo 3" class="w-100 h-100" style="object-fit:cover;">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="min-height:120px; border-radius:10px; border:2px dashed #ddd;">
                                    <a href="https://maps.google.com" target="_blank" class="text-center text-muted text-decoration-none p-2">
                                        <i class="fa fa-map-marked-alt fa-2x text-primary mb-2 d-block"></i>
                                        <small class="fw-bold">Get Directions</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Location End -->


    <!-- ============================================================
         3. HOW APX AUTOMAI WORKS
    ============================================================ -->
    <div id="how-it-works" class="apx-full-bleed apx-section wow fadeInUp" style="background:#f8f9fa;" data-wow-delay="0.1s">
        <div class="container">
            <div class="apx-section-header mb-5">
                <h6 class="text-primary text-uppercase mb-2">Simple &amp; Transparent</h6>
                <h1>How APX Automai Works</h1>
                <p class="text-muted">Four easy steps from booking to getting your vehicle back in top shape.</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-3 col-md-6 text-center wow fadeInUp" data-wow-delay="0.1s">
                    <div class="position-relative mb-4">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:72px;height:72px;">
                            <i class="fa fa-calendar-check fa-2x text-white"></i>
                        </div>
                        <div class="position-absolute top-0 end-0 me-n2" style="transform:translate(30%,-20%);">
                            <span class="badge rounded-pill" style="background:#e63946; font-family:'Barlow',sans-serif; font-size:0.85rem;">01</span>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2" style="font-family:'Barlow',sans-serif;">Book Online</h5>
                    <p class="text-muted" style="font-size:0.88rem;">Select your vehicle type, choose your services, pick a date — done in under a minute. No account required.</p>
                </div>
                <div class="col-lg-3 col-md-6 text-center wow fadeInUp" data-wow-delay="0.2s">
                    <div class="position-relative mb-4">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:72px;height:72px;">
                            <i class="fa fa-car fa-2x text-white"></i>
                        </div>
                        <div class="position-absolute top-0 end-0 me-n2" style="transform:translate(30%,-20%);">
                            <span class="badge rounded-pill" style="background:#e63946; font-family:'Barlow',sans-serif; font-size:0.85rem;">02</span>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2" style="font-family:'Barlow',sans-serif;">Bring Your Vehicle</h5>
                    <p class="text-muted" style="font-size:0.88rem;">Drive to our shop at your scheduled time. Our team will be ready and waiting — no long queues or waiting around.</p>
                </div>
                <div class="col-lg-3 col-md-6 text-center wow fadeInUp" data-wow-delay="0.3s">
                    <div class="position-relative mb-4">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:72px;height:72px;">
                            <i class="fa fa-tools fa-2x text-white"></i>
                        </div>
                        <div class="position-absolute top-0 end-0 me-n2" style="transform:translate(30%,-20%);">
                            <span class="badge rounded-pill" style="background:#e63946; font-family:'Barlow',sans-serif; font-size:0.85rem;">03</span>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2" style="font-family:'Barlow',sans-serif;">We Service It</h5>
                    <p class="text-muted" style="font-size:0.88rem;">Our certified technicians perform your selected services with quality parts and full transparency on what's being done.</p>
                </div>
                <div class="col-lg-3 col-md-6 text-center wow fadeInUp" data-wow-delay="0.4s">
                    <div class="position-relative mb-4">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:72px;height:72px;">
                            <i class="fa fa-check-circle fa-2x text-white"></i>
                        </div>
                        <div class="position-absolute top-0 end-0 me-n2" style="transform:translate(30%,-20%);">
                            <span class="badge rounded-pill" style="background:#e63946; font-family:'Barlow',sans-serif; font-size:0.85rem;">04</span>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2" style="font-family:'Barlow',sans-serif;">Drive Away Happy</h5>
                    <p class="text-muted" style="font-size:0.88rem;">Pick up your vehicle running at its best. We'll walk you through everything that was done before you go.</p>
                </div>
            </div>
            <div class="text-center mt-5">
                <button class="btn btn-primary py-3 px-5 fw-bold" data-bs-toggle="modal" data-bs-target="#bookingModal">
                    Book a Service Now &nbsp;<i class="fa fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- How It Works End -->


    <!-- ============================================================
         4. TESTIMONIALS — Owl Carousel
    ============================================================ -->
    <div id="testimonials" class="apx-section wow fadeInUp" data-wow-delay="0.1s">
        <div class="container">
            <div class="apx-section-header mb-5">
                <h6 class="text-primary text-uppercase mb-2">Reviews</h6>
                <h1>What Car &amp; Motor Owners Say</h1>
            </div>
            <div class="owl-carousel testimonial-carousel wow fadeInUp" data-wow-delay="0.2s">
                <div class="testimonial-item bg-light p-4" style="border-radius:10px; border-left:4px solid #e63946;">
                    <div class="d-flex mb-3">
                        <i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning"></i>
                    </div>
                    <p class="mb-4" style="font-style:italic; color:#555;">"Very thorough CVT cleaning — they explained every step. My scooter runs smoother than when I bought it. Highly recommend APX!"</p>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;flex-shrink:0;"><span class="text-white fw-bold">JR</span></div>
                        <div><h6 class="mb-0">Jose Reyes</h6><small class="text-muted">Honda Click 150i</small></div>
                    </div>
                </div>
                <div class="testimonial-item bg-light p-4" style="border-radius:10px; border-left:4px solid #e63946;">
                    <div class="d-flex mb-3">
                        <i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning"></i>
                    </div>
                    <p class="mb-4" style="font-style:italic; color:#555;">"Brought my Vios in for an oil change and throttle cleaning. Fast, affordable, and no upselling. The free ECU diagnose was a great bonus."</p>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;flex-shrink:0;"><span class="text-white fw-bold">MC</span></div>
                        <div><h6 class="mb-0">Maria Cruz</h6><small class="text-muted">Toyota Vios</small></div>
                    </div>
                </div>
                <div class="testimonial-item bg-light p-4" style="border-radius:10px; border-left:4px solid #e63946;">
                    <div class="d-flex mb-3">
                        <i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star-half-alt text-warning"></i>
                    </div>
                    <p class="mb-4" style="font-style:italic; color:#555;">"Online booking was seamless. Showed up, they were ready. Valve clearance done in under an hour. Will definitely come back."</p>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;flex-shrink:0;"><span class="text-white fw-bold">AL</span></div>
                        <div><h6 class="mb-0">Angelo Lim</h6><small class="text-muted">Yamaha NMAX</small></div>
                    </div>
                </div>
                <div class="testimonial-item bg-light p-4" style="border-radius:10px; border-left:4px solid #e63946;">
                    <div class="d-flex mb-3">
                        <i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning"></i>
                    </div>
                    <p class="mb-4" style="font-style:italic; color:#555;">"I had my Montero's fuel injection cleaned. Car feels brand new. The technicians were honest about what was needed and what wasn't."</p>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;flex-shrink:0;"><span class="text-white fw-bold">RD</span></div>
                        <div><h6 class="mb-0">Ramon Dela Cruz</h6><small class="text-muted">Mitsubishi Montero Sport</small></div>
                    </div>
                </div>
                <div class="testimonial-item bg-light p-4" style="border-radius:10px; border-left:4px solid #e63946;">
                    <div class="d-flex mb-3">
                        <i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning me-1"></i><i class="fa fa-star text-warning"></i>
                    </div>
                    <p class="mb-4" style="font-style:italic; color:#555;">"Free basic inspection caught an issue I didn't even know about. Saved me from a bigger problem later. Great service, great team."</p>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;flex-shrink:0;"><span class="text-white fw-bold">SS</span></div>
                        <div><h6 class="mb-0">Sarah Santos</h6><small class="text-muted">Honda City</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Testimonials End -->


    <!-- ============================================================
         5. WHY CHOOSE APX AUTOMAI
    ============================================================ -->
    <div id="why-choose" class="apx-full-bleed apx-section wow fadeInUp" style="background:#1a1a2e;" data-wow-delay="0.1s">
        <div class="container">
            <div class="apx-section-header mb-5">
                <h6 class="text-primary text-uppercase mb-2">Our Edge</h6>
                <h1 class="text-white">Why Choose APX Automai</h1>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="d-flex p-4 h-100" style="background:rgba(255,255,255,0.05); border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <div class="bg-primary rounded d-flex align-items-center justify-content-center flex-shrink-0 me-4" style="width:54px;height:54px;">
                            <i class="fa fa-certificate fa-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-white fw-bold mb-2" style="font-family:'Barlow',sans-serif;">Quality Servicing</h5>
                            <p class="mb-0" style="color:rgba(255,255,255,0.6); font-size:0.88rem;">We use quality parts and follow manufacturer-recommended procedures for every service, no shortcuts.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.2s">
                    <div class="d-flex p-4 h-100" style="background:rgba(255,255,255,0.05); border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <div class="bg-primary rounded d-flex align-items-center justify-content-center flex-shrink-0 me-4" style="width:54px;height:54px;">
                            <i class="fa fa-users-cog fa-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-white fw-bold mb-2" style="font-family:'Barlow',sans-serif;">Expert Technicians</h5>
                            <p class="mb-0" style="color:rgba(255,255,255,0.6); font-size:0.88rem;">Our mechanics are trained and experienced across a wide range of car and motorcycle makes and models.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="d-flex p-4 h-100" style="background:rgba(255,255,255,0.05); border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <div class="bg-primary rounded d-flex align-items-center justify-content-center flex-shrink-0 me-4" style="width:54px;height:54px;">
                            <i class="fa fa-tools fa-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-white fw-bold mb-2" style="font-family:'Barlow',sans-serif;">Modern Equipment</h5>
                            <p class="mb-0" style="color:rgba(255,255,255,0.6); font-size:0.88rem;">From ECU diagnostics to precision CVT tools — we invest in equipment so your vehicle gets accurate, reliable care.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.4s">
                    <div class="d-flex p-4 h-100" style="background:rgba(255,255,255,0.05); border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <div class="bg-primary rounded d-flex align-items-center justify-content-center flex-shrink-0 me-4" style="width:54px;height:54px;">
                            <i class="fa fa-tags fa-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-white fw-bold mb-2" style="font-family:'Barlow',sans-serif;">Transparent Pricing</h5>
                            <p class="mb-0" style="color:rgba(255,255,255,0.6); font-size:0.88rem;">Upfront price estimates before any work starts. No hidden charges, no surprise bills when you pick up.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="d-flex p-4 h-100" style="background:rgba(255,255,255,0.05); border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <div class="bg-primary rounded d-flex align-items-center justify-content-center flex-shrink-0 me-4" style="width:54px;height:54px;">
                            <i class="fa fa-calendar-alt fa-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-white fw-bold mb-2" style="font-family:'Barlow',sans-serif;">Easy Online Booking</h5>
                            <p class="mb-0" style="color:rgba(255,255,255,0.6); font-size:0.88rem;">Book in under a minute with no account needed. Choose your services, pick your date, and we handle the rest.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.6s">
                    <div class="d-flex p-4 h-100" style="background:rgba(255,255,255,0.05); border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <div class="bg-primary rounded d-flex align-items-center justify-content-center flex-shrink-0 me-4" style="width:54px;height:54px;">
                            <i class="fa fa-search fa-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-white fw-bold mb-2" style="font-family:'Barlow',sans-serif;">Free ECU Diagnose</h5>
                            <p class="mb-0" style="color:rgba(255,255,255,0.6); font-size:0.88rem;">Every vehicle gets a free ECU diagnosis and basic inspection — so you know exactly what your vehicle needs.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Why Choose End -->


    <!-- ============================================================
         6. ABOUT
    ============================================================ -->
    <div id="about" class="apx-section wow fadeInUp" data-wow-delay="0.1s">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 pt-4" style="min-height: 400px;">
                    <div class="position-relative h-100 wow fadeIn" data-wow-delay="0.1s">
                        <img class="position-absolute img-fluid w-100 h-100" src="/assets/img/about.jpg" style="object-fit: cover;" alt="">
                        <div class="position-absolute top-0 end-0 mt-n4 me-n4 py-4 px-5" style="background: rgba(0, 0, 0, .08);">
                            <h1 class="display-4 text-white mb-0">15 <span class="fs-4">Years</span></h1>
                            <h4 class="text-white">Experience</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-primary text-uppercase mb-2">About Us</h6>
                    <h1 class="mb-4"><span class="text-primary">APX Automai</span> — Built Around Your Vehicle's Best Care</h1>
                    <p class="mb-4">APX Automai is a Quezon City-based auto service center dedicated to keeping your car and motorcycle in peak condition. We combine certified technical expertise with honest, transparent service — so you always know what's being done and why.</p>
                    <div class="row g-4 mb-3 pb-3">
                        <div class="col-12 wow fadeIn" data-wow-delay="0.1s">
                            <div class="d-flex">
                                <div class="bg-light d-flex flex-shrink-0 align-items-center justify-content-center mt-1" style="width: 45px; height: 45px;">
                                    <span class="fw-bold text-secondary">01</span>
                                </div>
                                <div class="ps-3">
                                    <h6>Certified & Professional</h6>
                                    <span>Our technicians are trained across a wide range of car and motorcycle makes and models.</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 wow fadeIn" data-wow-delay="0.3s">
                            <div class="d-flex">
                                <div class="bg-light d-flex flex-shrink-0 align-items-center justify-content-center mt-1" style="width: 45px; height: 45px;">
                                    <span class="fw-bold text-secondary">02</span>
                                </div>
                                <div class="ps-3">
                                    <h6>Quality Parts & Equipment</h6>
                                    <span>We use quality OEM-grade parts and modern diagnostic tools for every service we perform.</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 wow fadeIn" data-wow-delay="0.5s">
                            <div class="d-flex">
                                <div class="bg-light d-flex flex-shrink-0 align-items-center justify-content-center mt-1" style="width: 45px; height: 45px;">
                                    <span class="fw-bold text-secondary">03</span>
                                </div>
                                <div class="ps-3">
                                    <h6>Transparent & Honest Pricing</h6>
                                    <span>No hidden charges, no surprise bills. You get a clear estimate before any work begins.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="#services" class="btn btn-primary py-3 px-5">View Our Services<i class="fa fa-arrow-right ms-3"></i></a>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->


    <!-- Testimonials carousel init -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            /* filter buttons */
            var btns  = document.querySelectorAll(".apx-filter-btn");
            var cards = document.querySelectorAll(".apx-svc-card");
            btns.forEach(function (btn) {
                btn.addEventListener("click", function () {
                    btns.forEach(function (b) { b.classList.remove("active"); });
                    btn.classList.add("active");
                    var filter = btn.getAttribute("data-filter");
                    cards.forEach(function (card) {
                        var cats = card.getAttribute("data-cat") || "";
                        card.classList.toggle("hidden", filter !== "all" && cats.indexOf(filter) === -1);
                    });
                });
            });

            /* testimonials owl carousel */
            if (typeof $.fn.owlCarousel !== 'undefined') {
                $(".testimonial-carousel").owlCarousel({
                    autoplay: true,
                    smartSpeed: 1000,
                    loop: true,
                    dots: true,
                    nav: false,
                    autoplayHoverPause: true,
                    responsive: {
                        0:    { items: 1 },
                        768:  { items: 2 },
                        992:  { items: 3 }
                    }
                });
            }
        });
    </script>

    </div><!-- /apx-page-content -->
    <div class="apx-page-sidebar d-none d-xl-block"></div>
    </div><!-- /apx-page-wrap -->


    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-light mb-4">APX Automai</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>5 Glenn St., Quezon City</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+012 345 6789</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>hello@apxautomai.com</p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-instagram"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-light mb-4">Opening Hours</h4>
                    <h6 class="text-light">Monday - Friday:</h6>
                    <p class="mb-4">09:00 AM - 09:00 PM</p>
                    <h6 class="text-light">Saturday - Sunday:</h6>
                    <p class="mb-0">09:00 AM - 12:00 PM</p>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; 2025 <a class="border-bottom" href="#home">APX Automai</a>. All Rights Reserved.
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="footer-menu">
                            <a href="#home">Home</a>
                            <a href="#services">Services</a>
                            <a href="#about">About</a>
                            <a href="#location">Contact</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/lib/wow/wow.min.js"></script>
    <script src="/assets/lib/easing/easing.min.js"></script>
    <script src="/assets/lib/waypoints/waypoints.min.js"></script>
    <script src="/assets/lib/counterup/counterup.min.js"></script>
    <script src="/assets/lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="/assets/lib/tempusdominus/js/moment.min.js"></script>    <script src="/assets/lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="/assets/lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <script src="/assets/js/main.js"></script>

    <!-- Spinner Fix: hide spinner once page is ready, with a 3s fallback -->
    <script>
        (function () {
            function hideSpinner() {
                var s = document.getElementById("spinner");
                if (s) { s.classList.remove("show"); setTimeout(function(){ s.style.display="none"; }, 400); }
            }
            window.addEventListener("load", hideSpinner);
            setTimeout(hideSpinner, 3000);
        })();
    </script>


    <!-- ============================================================
         APX BOOKING MODAL
    ============================================================ -->
    <style>
        /* ---- step bar ---- */
        .apx-step-bar { display:flex; gap:0; margin-top:14px; }
        .apx-step-item { flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; position:relative; }
        .apx-step-item:not(:last-child)::after { content:''; position:absolute; top:10px; left:60%; right:-40%; height:1px; background:#dee2e6; }
        .apx-step-dot { width:22px; height:22px; border-radius:50%; border:2px solid #dee2e6; background:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#999; position:relative; z-index:1; }
        .apx-step-item.active .apx-step-dot { background:#e63946; border-color:#e63946; color:#fff; }
        .apx-step-item.done .apx-step-dot { background:#28a745; border-color:#28a745; color:#fff; }
        .apx-step-lbl { font-size:11px; color:#aaa; font-family:'Ubuntu', sans-serif; }
        .apx-step-item.active .apx-step-lbl { color:#e63946; font-weight:600; }
        .apx-step-item.done .apx-step-lbl { color:#28a745; }

        /* ---- vehicle type toggle ---- */
        .apx-vtype-toggle { display:flex; gap:8px; margin-bottom:14px; }
        .apx-vtype-btn { flex:1; padding:8px; font-size:0.83rem; font-weight:600; border:2px solid #ddd; border-radius:8px; background:#fff; color:#555; cursor:pointer; font-family:'Ubuntu',sans-serif; transition:all 0.2s; }
        .apx-vtype-btn.active { background:#e63946; border-color:#e63946; color:#fff; }

        /* ---- service grid ---- */
        .apx-modal-svc-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; max-height:280px; overflow-y:auto; padding-right:2px; }
        .apx-modal-svc-card { border:1.5px solid #e8e8e8; border-radius:10px; padding:10px 12px; cursor:pointer; font-size:0.8rem; color:#333; background:#fff; line-height:1.4; position:relative; transition:border-color 0.2s; font-family:'Ubuntu',sans-serif; }
        .apx-modal-svc-card:hover { border-color:#e63946; }
        .apx-modal-svc-card.selected { border-color:#e63946; background:#fff5f5; color:#c0392b; }
        .apx-modal-svc-card .apx-svc-check { display:none; position:absolute; top:7px; right:7px; width:16px; height:16px; border-radius:50%; background:#e63946; align-items:center; justify-content:center; }
        .apx-modal-svc-card.selected .apx-svc-check { display:flex; }
        .apx-modal-svc-tag { display:inline-block; font-size:10px; padding:2px 7px; border-radius:4px; margin-top:5px; background:#f0f0f0; color:#777; }
        .apx-modal-svc-card.selected .apx-modal-svc-tag { background:#ffd6d8; color:#c0392b; }
        .apx-modal-svc-tag.free { background:#d4edda; color:#155724; }
        .apx-modal-svc-card.selected .apx-modal-svc-tag.free { background:#b8dfc2; color:#0d3d1a; }
        .apx-modal-svc-price { font-size:11px; color:#aaa; display:block; margin-top:3px; }
        .apx-modal-svc-card.selected .apx-modal-svc-price { color:#c0392b; }

        /* ---- estimate bar ---- */
        .apx-estimate-bar { display:flex; justify-content:space-between; align-items:center; background:#f8f9fa; border-radius:8px; padding:9px 14px; margin-top:10px; font-size:0.83rem; }
        .apx-estimate-bar .apx-est-count { color:#777; }
        .apx-estimate-bar .apx-est-val { font-weight:700; color:#e63946; }

        /* ---- form fields ---- */
        .apx-modal .form-label { font-size:0.8rem; font-weight:600; color:#555; margin-bottom:4px; }
        .apx-modal .form-control, .apx-modal .form-select { font-size:0.85rem; border-radius:8px; border:1.5px solid #ddd; font-family:'Ubuntu',sans-serif; }
        .apx-modal .form-control:focus, .apx-modal .form-select:focus { border-color:#e63946; box-shadow:0 0 0 0.2rem rgba(230,57,70,0.15); }
        .apx-modal .form-select:disabled { opacity:0.45; }

        /* ---- confirm summary ---- */
        .apx-confirm-block { background:#f8f9fa; border-radius:10px; padding:14px 16px; margin-bottom:12px; }
        .apx-confirm-row { display:flex; justify-content:space-between; font-size:0.83rem; padding:5px 0; border-bottom:1px solid #eee; }
        .apx-confirm-row:last-child { border-bottom:none; }
        .apx-confirm-row span:first-child { color:#888; min-width:80px; }
        .apx-confirm-row span:last-child { font-weight:600; text-align:right; color:#333; }
        .apx-confirm-total { display:flex; justify-content:space-between; font-size:0.85rem; padding:8px 0 0; border-top:1.5px solid #ddd; margin-top:4px; }
        .apx-confirm-total span:last-child { font-weight:700; color:#e63946; }

        /* ---- membership nudge ---- */
        .apx-member-card { border:2px solid #e63946; border-radius:12px; padding:14px 16px; background:#fff5f5; margin-bottom:12px; }
        .apx-member-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
        .apx-member-badge { font-size:10px; font-weight:700; background:#e63946; color:#fff; padding:3px 9px; border-radius:4px; letter-spacing:0.03em; }
        .apx-member-title { font-size:0.88rem; font-weight:700; color:#c0392b; margin:0 0 2px; }
        .apx-member-sub { font-size:0.75rem; color:#e63946; margin:0; }
        .apx-member-perks { display:grid; grid-template-columns:1fr 1fr; gap:5px 12px; margin-bottom:12px; }
        .apx-member-perk { display:flex; align-items:center; gap:6px; font-size:0.78rem; color:#c0392b; }
        .apx-perk-dot { width:5px; height:5px; border-radius:50%; background:#e63946; flex-shrink:0; }
        .apx-btn-member { width:100%; padding:9px; font-size:0.83rem; font-weight:700; background:#e63946; color:#fff; border:none; border-radius:8px; cursor:pointer; font-family:'Ubuntu',sans-serif; letter-spacing:0.02em; transition:background 0.2s; }
        .apx-btn-member:hover { background:#c0392b; }
        .apx-btn-skip { display:block; text-align:center; font-size:0.75rem; color:#e63946; margin-top:7px; cursor:pointer; background:none; border:none; width:100%; font-family:'Ubuntu',sans-serif; }

        /* ---- modal footer buttons ---- */
        .apx-modal .btn-apx-back { background:none; border:none; color:#888; font-size:0.85rem; cursor:pointer; padding:0; font-family:'Ubuntu',sans-serif; }
        .apx-modal .btn-apx-next { background:#e63946; border:none; color:#fff; font-size:0.85rem; font-weight:700; border-radius:8px; padding:9px 24px; cursor:pointer; font-family:'Ubuntu',sans-serif; transition:background 0.2s; }
        .apx-modal .btn-apx-next:hover { background:#c0392b; }
        .apx-modal .btn-apx-next.confirm { background:#28a745; }
        .apx-modal .btn-apx-next.confirm:hover { background:#1e7e34; }

        /* ---- success screen ---- */
        .apx-success-icon { width:52px; height:52px; border-radius:50%; background:#d4edda; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
        .apx-ref-badge { display:inline-block; font-family:monospace; font-size:1rem; font-weight:700; background:#f8f9fa; border:1.5px solid #ddd; border-radius:8px; padding:7px 18px; margin:10px 0 5px; color:#333; letter-spacing:0.05em; }
    </style>

    <!-- Modal markup -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:560px;">
            <div class="modal-content apx-modal" style="border-radius:14px; border:none;">

                <!-- header -->
                <div class="modal-header border-bottom" style="padding:18px 24px 14px;">
                    <div style="width:100%;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="modal-title mb-0" id="bookingModalLabel" style="font-family:'Barlow',sans-serif; font-weight:700; color:#222;">Book a service</h5>
                            <div class="d-flex align-items-center gap-3">
                                <span id="apx-step-label" style="font-size:0.78rem; color:#aaa;">Step 1 of 3</span>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                        </div>
                        <div class="apx-step-bar">
                            <div class="apx-step-item active" id="apx-s1"><div class="apx-step-dot">1</div><span class="apx-step-lbl">Services</span></div>
                            <div class="apx-step-item" id="apx-s2"><div class="apx-step-dot">2</div><span class="apx-step-lbl">Your details</span></div>
                            <div class="apx-step-item" id="apx-s3"><div class="apx-step-dot">3</div><span class="apx-step-lbl">Confirm</span></div>
                        </div>
                    </div>
                </div>

                <!-- body -->
                <div class="modal-body" style="padding:20px 24px;">

                    <!-- STEP 1: Services -->
                    <div id="apx-view-1">
                        <p style="font-size:0.8rem; color:#888; margin:0 0 10px;">Select one or more services. Prices are estimates.</p>
                        <div class="apx-modal-svc-grid" id="apx-svc-grid"></div>
                        <div class="apx-estimate-bar">
                            <span class="apx-est-count" id="apx-sel-count">No services selected</span>
                            <span class="apx-est-val" id="apx-est-total">₱0</span>
                        </div>
                    </div>

                    <!-- STEP 2: Details -->
                    <div id="apx-view-2" style="display:none;">
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label">Full name</label><input type="text" class="form-control" id="apx-f-name" placeholder="Juan dela Cruz"></div>
                            <div class="col-6"><label class="form-label">Email</label><input type="email" class="form-control" id="apx-f-email" placeholder="juan@email.com"></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label class="form-label">Phone</label><input type="text" class="form-control" id="apx-f-phone" placeholder="09xxxxxxxxx"></div>
                            <div class="col-6"><label class="form-label">Preferred date</label><input type="date" class="form-control" id="apx-f-date"></div>
                        </div>
                        <p style="font-size:0.82rem; font-weight:700; color:#333; margin:0 0 10px; font-family:'Ubuntu',sans-serif;">Vehicle information</p>
                        <div class="apx-vtype-toggle">
                            <button class="apx-vtype-btn active" id="apx-vtype-car" onclick="apxSetVtype('car')">Car</button>
                            <button class="apx-vtype-btn" id="apx-vtype-moto" onclick="apxSetVtype('motorcycle')">Motorcycle</button>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label">Manufacturer</label><select class="form-select" id="apx-v-make" onchange="apxOnMake()"><option value="">Select manufacturer</option></select></div>
                            <div class="col-6"><label class="form-label">Model</label><select class="form-select" id="apx-v-model" disabled onchange="apxOnModel()"><option value="">Select model</option></select></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label">Year</label><select class="form-select" id="apx-v-year" disabled><option value="">Select year</option></select></div>
                            <div class="col-6"><label class="form-label">Plate number</label><input type="text" class="form-control" id="apx-v-plate" placeholder="e.g. ABC-1234"></div>
                        </div>
                        <div class="mt-2"><label class="form-label">Notes / special request</label><textarea class="form-control" id="apx-f-notes" rows="2" placeholder="Anything we should know?"></textarea></div>
                    </div>

                    <!-- STEP 3: Confirm -->
                    <div id="apx-view-3" style="display:none;">
                        <div class="apx-confirm-block">
                            <div class="apx-confirm-row"><span>Name</span><span id="apx-c-name">—</span></div>
                            <div class="apx-confirm-row"><span>Email</span><span id="apx-c-email">—</span></div>
                            <div class="apx-confirm-row"><span>Phone</span><span id="apx-c-phone">—</span></div>
                            <div class="apx-confirm-row"><span>Date</span><span id="apx-c-date">—</span></div>
                            <div class="apx-confirm-row"><span>Vehicle</span><span id="apx-c-vehicle">—</span></div>
                            <div class="apx-confirm-row"><span>Services</span><span id="apx-c-services">—</span></div>
                            <div class="apx-confirm-row"><span>Notes</span><span id="apx-c-notes">—</span></div>
                            <div class="apx-confirm-total"><span>Estimated total</span><span id="apx-c-total">—</span></div>
                        </div>
                        <p style="font-size:0.75rem; color:#aaa; margin:0 0 12px;">* Estimate only. Final price confirmed at the shop.</p>
                        <!-- membership nudge -->
                        <div class="apx-member-card">
                            <div class="apx-member-top">
                                <div>
                                    <p class="apx-member-title">Get more with an APX account</p>
                                    <p class="apx-member-sub">Free to join. Takes 30 seconds.</p>
                                </div>
                                <span class="apx-member-badge">Members only</span>
                            </div>
                            <div class="apx-member-perks">
                                <div class="apx-member-perk"><div class="apx-perk-dot"></div>Priority booking slots</div>
                                <div class="apx-member-perk"><div class="apx-perk-dot"></div>Loyalty points per visit</div>
                                <div class="apx-member-perk"><div class="apx-perk-dot"></div>Exclusive discounts</div>
                                <div class="apx-member-perk"><div class="apx-perk-dot"></div>Track &amp; manage bookings</div>
                            </div>
                            <button class="apx-btn-member" onclick="window.location='{{ route('register') }}'">Create a free account</button>
                            <button class="apx-btn-skip" id="apx-btn-skip">No thanks, just book as guest</button>
                        </div>
                    </div>

                    <!-- SUCCESS -->
                    <div id="apx-view-success" style="display:none; text-align:center; padding:24px 0;">
                        <div class="apx-success-icon">
                            <svg width="26" height="26" viewBox="0 0 26 26" fill="none"><path d="M5 13l5 5L21 7" stroke="#28a745" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <h5 style="font-family:'Barlow',sans-serif; font-weight:700; margin:0 0 6px;">Booking received!</h5>
                        <p style="font-size:0.85rem; color:#777; margin:0 0 12px;">A confirmation email is on its way. Your reference number:</p>
                        <div class="apx-ref-badge" id="apx-ref-number">APX-XXXXXX</div>
                        <p style="font-size:0.78rem; color:#aaa; margin:10px 0 0;">Bring this number when you arrive.</p>
                    </div>

                </div>

                <!-- footer -->
                <div class="modal-footer border-top justify-content-between" id="apx-modal-footer" style="padding:14px 24px;">
                    <button class="btn-apx-back" id="apx-btn-back" onclick="apxBack()" style="visibility:hidden;">← Back</button>
                    <button class="btn-apx-next" id="apx-btn-next" onclick="apxNext()">Next →</button>
                </div>

            </div>
        </div>
    </div>

    <script>
    (function () {
        var SERVICES = [
            {name:'Change Oil & Filter',tag:'maintenance',lo:300,hi:600},
            {name:'Fuel Injection Cleaning',tag:'cleaning',lo:400,hi:800},
            {name:'Throttle Body Cleaning',tag:'cleaning',lo:300,hi:600},
            {name:'Throttle Idle Adjustment',tag:'adjustment',lo:150,hi:300},
            {name:'Valve Clearance / Tune-up',tag:'tune-up',lo:500,hi:1000},
            {name:'CVT Cleaning & Inspection',tag:'cleaning',lo:400,hi:700},
            {name:'Airfilter Inspection',tag:'inspection',lo:100,hi:200},
            {name:'Airfilter Installation',tag:'installation',lo:150,hi:300},
            {name:'Flyball Inspection',tag:'inspection',lo:100,hi:200},
            {name:'Flyball Cleaning',tag:'cleaning',lo:150,hi:350},
            {name:'V-belt Inspection',tag:'inspection',lo:100,hi:200},
            {name:'V-belt Cleaning',tag:'cleaning',lo:150,hi:300},
            {name:'Pulley Set Inspection',tag:'inspection',lo:100,hi:200},
            {name:'Pulley Set Cleaning',tag:'cleaning',lo:200,hi:400},
            {name:'Torque Drive Assy Inspection',tag:'inspection',lo:100,hi:200},
            {name:'Torque Drive Assy Cleaning',tag:'cleaning',lo:200,hi:400},
            {name:'Torque Drive Assy Greasing',tag:'maintenance',lo:150,hi:300},
            {name:'Clutch Lining Inspection',tag:'inspection',lo:100,hi:200},
            {name:'Clutch Lining Cleaning',tag:'cleaning',lo:150,hi:300},
            {name:'Kick Starter Inspection',tag:'inspection',lo:100,hi:200},
            {name:'Pulley Shaving & Re-angle',tag:'speciality',lo:800,hi:1500},
            {name:'Pulley Drive Face Shaving & Re-angle',tag:'speciality',lo:800,hi:1500},
            {name:'Sprocket/Chain Cleaning & Regreasing',tag:'maintenance',lo:200,hi:400},
            {name:'Pipe Cleaning',tag:'cleaning',lo:150,hi:300},
            {name:'Brake Cleaning',tag:'cleaning',lo:150,hi:300},
            {name:'Brake Adjustment',tag:'adjustment',lo:100,hi:200},
            {name:'FREE ECU Diagnose',tag:'free',lo:0,hi:0},
            {name:'FREE Basic Inspection',tag:'free',lo:0,hi:0}
        ];
        var VEHICLES = {
            car: {
                Toyota:['Vios','Innova','Fortuner','Hiace','Camry','Corolla Cross','Rush'],
                Honda:['City','Civic','BR-V','CR-V','HR-V','Jazz','Brio'],
                Mitsubishi:['Mirage','Mirage G4','Montero Sport','Xpander','L300','Strada'],
                Suzuki:['Alto','Celerio','Ertiga','Jimny','Swift','Dzire'],
                Ford:['EcoSport','Everest','Explorer','Ranger','Territory'],
                Hyundai:['Accent','Tucson','Santa Fe','Reina','Stargazer'],
                Nissan:['Almera','Navara','Terra','X-Trail'],
                Kia:['Soluto','Stonic','Sportage','Carnival']
            },
            motorcycle: {
                Honda:['Click 125i','Click 150i','BeAT','PCX 160','ADV 160','CBR150R','XRM 125'],
                Yamaha:['Mio i 125','Mio Gear','NMAX','Aerox','Sniper 150','FZ-S'],
                Kawasaki:['Barako II','CT100B','Rouser NS 200','Ninja 400'],
                Suzuki:['Skydrive 125','Raider R150','GSX-R150'],
                TVS:['Apache RTR 160','Ntorq 125','King Duramax'],
                Kymco:['Agility 125','Like 150i']
            }
        };

        var selected = new Set();
        var vtype = 'car';
        var step = 1;

        function fmt(lo, hi) { return (lo===0&&hi===0) ? 'FREE' : '₱'+lo.toLocaleString()+' – ₱'+hi.toLocaleString(); }

        function buildGrid() {
            var grid = document.getElementById('apx-svc-grid');
            grid.innerHTML = '';
            SERVICES.forEach(function(s, i) {
                var isFree = s.tag === 'free';
                var card = document.createElement('div');
                card.className = 'apx-modal-svc-card';
                card.dataset.idx = i;
                card.innerHTML = '<div class="apx-svc-check"><svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1.5 4.5l2 2 4-4" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/></svg></div>'
                    + s.name
                    + '<br><span class="apx-modal-svc-tag' + (isFree?' free':'') + '">' + (isFree?'free':s.tag) + '</span>'
                    + '<span class="apx-modal-svc-price">' + fmt(s.lo, s.hi) + '</span>';
                card.onclick = function() { apxToggle(i, card); };
                grid.appendChild(card);
            });
        }

        function apxToggle(i, card) {
            selected.has(i) ? selected.delete(i) : selected.add(i);
            card.classList.toggle('selected');
            apxUpdateEst();
        }

        function apxUpdateEst() {
            var lo=0, hi=0;
            selected.forEach(function(i){ lo+=SERVICES[i].lo; hi+=SERVICES[i].hi; });
            var n = selected.size;
            document.getElementById('apx-sel-count').textContent = n===0 ? 'No services selected' : n+' service'+(n>1?'s':'')+' selected';
            document.getElementById('apx-est-total').textContent = n===0 ? '₱0' : (lo===0&&hi===0 ? 'FREE' : '₱'+lo.toLocaleString()+' – ₱'+hi.toLocaleString());
        }

        // FIX 1: Open modal and pre-select a service by name
        window.apxOpenWithService = function(e, svcName) {
            e.preventDefault();
            // Find matching service index
            var idx = SERVICES.findIndex(function(s) {
                return s.name.toLowerCase().replace(/[^a-z0-9]/g,'') === svcName.toLowerCase().replace(/[^a-z0-9]/g,'');
            });
            // Open modal
            var modal = new bootstrap.Modal(document.getElementById('bookingModal'));
            modal.show();
            // After grid is built, select the card
            setTimeout(function() {
                if (idx >= 0 && !selected.has(idx)) {
                    var card = document.querySelector('.apx-modal-svc-card[data-idx="'+idx+'"]');
                    if (card) apxToggle(idx, card);
                    // Scroll card into view
                    if (card) card.scrollIntoView({block:'nearest'});
                }
            }, 80);
        };

        window.apxSetVtype = function(t) {
            vtype = t;
            document.getElementById('apx-vtype-car').className = 'apx-vtype-btn'+(t==='car'?' active':'');
            document.getElementById('apx-vtype-moto').className = 'apx-vtype-btn'+(t==='motorcycle'?' active':'');
            apxPopMakes();
        };

        function apxPopMakes() {
            var sel = document.getElementById('apx-v-make');
            sel.innerHTML = '<option value="">Select manufacturer</option>';
            Object.keys(VEHICLES[vtype]).forEach(function(m){ var o=document.createElement('option');o.value=m;o.textContent=m;sel.appendChild(o); });
            document.getElementById('apx-v-model').innerHTML = '<option value="">Select model</option>';
            document.getElementById('apx-v-model').disabled = true;
            document.getElementById('apx-v-year').innerHTML = '<option value="">Select year</option>';
            document.getElementById('apx-v-year').disabled = true;
        }

        window.apxOnMake = function() {
            var make = document.getElementById('apx-v-make').value;
            var mSel = document.getElementById('apx-v-model');
            mSel.innerHTML = '<option value="">Select model</option>';
            mSel.disabled = !make;
            document.getElementById('apx-v-year').innerHTML = '<option value="">Select year</option>';
            document.getElementById('apx-v-year').disabled = true;
            if (make && VEHICLES[vtype][make]) {
                VEHICLES[vtype][make].forEach(function(m){ var o=document.createElement('option');o.value=m;o.textContent=m;mSel.appendChild(o); });
            }
        };

        window.apxOnModel = function() {
            var model = document.getElementById('apx-v-model').value;
            var ySel = document.getElementById('apx-v-year');
            ySel.innerHTML = '<option value="">Select year</option>';
            ySel.disabled = !model;
            if (model) {
                var now = new Date().getFullYear();
                for (var y=now; y>=2000; y--) { var o=document.createElement('option');o.value=y;o.textContent=y;ySel.appendChild(o); }
            }
        };

        // FIX 3: sticky card passes vehicle data into step 2
        window.apxPreFillVehicle = function(vt, make, model) {
            apxSetVtype(vt);
            setTimeout(function() {
                var makeEl = document.getElementById('apx-v-make');
                if (makeEl && make) {
                    makeEl.value = make;
                    apxOnMake();
                    setTimeout(function() {
                        var modelEl = document.getElementById('apx-v-model');
                        if (modelEl && model) {
                            modelEl.value = model;
                            apxOnModel();
                        }
                    }, 50);
                }
            }, 100);
        };

        function apxSetStep(n) {
            step = n;
            ['apx-s1','apx-s2','apx-s3'].forEach(function(id, i) {
                var el = document.getElementById(id);
                el.className = 'apx-step-item';
                if (i+1 < n) el.classList.add('done');
                if (i+1 === n) el.classList.add('active');
            });
            document.getElementById('apx-step-label').textContent = 'Step '+n+' of 3';
            ['apx-view-1','apx-view-2','apx-view-3'].forEach(function(id, i){
                document.getElementById(id).style.display = (i+1===n) ? '' : 'none';
            });
            document.getElementById('apx-btn-back').style.visibility = n>1 ? 'visible' : 'hidden';
            var btn = document.getElementById('apx-btn-next');
            btn.textContent = n===3 ? 'Confirm booking ✓' : 'Next →';
            btn.className = n===3 ? 'btn-apx-next confirm' : 'btn-apx-next';
        }

        window.apxNext = function() {
            if (step===1) {
                if (selected.size===0) { alert('Please select at least one service.'); return; }
                apxSetStep(2);
            } else if (step===2) {
                var name  = document.getElementById('apx-f-name').value.trim();
                var email = document.getElementById('apx-f-email').value.trim();
                var date  = document.getElementById('apx-f-date').value;
                var make  = document.getElementById('apx-v-make').value;
                var model = document.getElementById('apx-v-model').value;
                var year  = document.getElementById('apx-v-year').value;
                if (!name||!email||!date) { alert('Please fill in name, email, and date.'); return; }
                if (!make||!model||!year) { alert('Please complete your vehicle information.'); return; }
                var names = Array.from(selected).map(function(i){ return SERVICES[i].name; }).join(', ');
                var lo=0, hi=0;
                selected.forEach(function(i){ lo+=SERVICES[i].lo; hi+=SERVICES[i].hi; });
                var plate = document.getElementById('apx-v-plate').value;
                var vStr  = vtype.charAt(0).toUpperCase()+vtype.slice(1)+' · '+make+' '+model+' '+year+(plate?' · '+plate:'');
                document.getElementById('apx-c-name').textContent     = name;
                document.getElementById('apx-c-email').textContent    = email;
                document.getElementById('apx-c-phone').textContent    = document.getElementById('apx-f-phone').value||'—';
                document.getElementById('apx-c-date').textContent     = date;
                document.getElementById('apx-c-vehicle').textContent  = vStr;
                document.getElementById('apx-c-services').textContent = names;
                document.getElementById('apx-c-notes').textContent    = document.getElementById('apx-f-notes').value||'—';
                document.getElementById('apx-c-total').textContent    = (lo===0&&hi===0?'FREE':'₱'+lo.toLocaleString()+' – ₱'+hi.toLocaleString())+' *';
                apxSetStep(3);
            } else if (step===3) {
                apxSubmitBooking(false);
            }
        };

        // FIX 2 + confirm: actual POST to backend
        function apxSubmitBooking(createAccount) {
            var btn = document.getElementById('apx-btn-next');
            btn.disabled = true;
            btn.textContent = 'Submitting…';

            var serviceNames = Array.from(selected).map(function(i){ return SERVICES[i].name; });
            var payload = {
                guest_name:    document.getElementById('apx-f-name').value.trim(),
                guest_email:   document.getElementById('apx-f-email').value.trim(),
                guest_phone:   document.getElementById('apx-f-phone').value.trim(),
                booking_date:  document.getElementById('apx-f-date').value,
                booking_time:  document.getElementById('apx-f-time') ? document.getElementById('apx-f-time').value : '09:00',
                vehicle_type:  vtype,
                vehicle_make:  document.getElementById('apx-v-make').value,
                vehicle_model: document.getElementById('apx-v-model').value,
                vehicle_year:  document.getElementById('apx-v-year').value,
                vehicle_plate: document.getElementById('apx-v-plate').value,
                services:      serviceNames,
                notes:         document.getElementById('apx-f-notes').value,
                create_account: createAccount
            };

            fetch('/booking/guest', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ?
                        document.querySelector('meta[name="csrf-token"]').content :
                        document.querySelector('input[name="_token"]') ?
                        document.querySelector('input[name="_token"]').value : ''
                },
                body: JSON.stringify(payload)
            })
            .then(function(r) {
                if (!r.ok) return r.json().then(function(e) { throw new Error(e.message || 'Server error ('+r.status+')'); });
                return r.json();
            })
            .then(function(data) {
                btn.disabled = false;
                btn.textContent = 'Confirm booking ✓';
                if (data.success) {
                    document.getElementById('apx-ref-number').textContent = data.reference;
                    ['apx-view-1','apx-view-2','apx-view-3'].forEach(function(id){ document.getElementById(id).style.display='none'; });
                    document.getElementById('apx-view-success').style.display = '';
                    document.getElementById('apx-modal-footer').style.display = 'none';
                    document.getElementById('apx-step-label').textContent = 'Done';
                    ['apx-s1','apx-s2','apx-s3'].forEach(function(id){ document.getElementById(id).className='apx-step-item done'; });
                } else {
                    alert(data.message || 'Booking failed. Please try again.');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.textContent = 'Confirm booking ✓';
                console.error('Booking error:', err);
                alert(err.message || 'Could not submit booking. Please try again.');
            });
        }

        window.apxBack = function() { if (step>1) apxSetStep(step-1); };

        /* reset modal state when it closes */
        document.getElementById('bookingModal').addEventListener('hidden.bs.modal', function() {
            selected.clear();
            step = 1;
            apxSetStep(1);
            document.getElementById('apx-view-success').style.display = 'none';
            document.getElementById('apx-modal-footer').style.display = '';
            document.querySelectorAll('.apx-modal-svc-card').forEach(function(c){ c.classList.remove('selected'); });
            apxUpdateEst();
            document.getElementById('apx-f-name').value='';
            document.getElementById('apx-f-email').value='';
            document.getElementById('apx-f-phone').value='';
            document.getElementById('apx-f-date').value='';
            document.getElementById('apx-f-notes').value='';
            document.getElementById('apx-v-plate').value='';
            vtype='car';
            apxSetVtype('car');
        });

        /* skip nudge — submit as guest */
        document.getElementById('apx-btn-skip').addEventListener('click', function() {
            apxSubmitBooking(false);
        });

        /* create account button — submit then redirect to register */
        document.querySelector('.apx-btn-member').addEventListener('click', function() {
            apxSubmitBooking(true);
        });

        buildGrid();
        apxPopMakes();
    })();
    </script>

    <!-- Active nav link on scroll -->
    <script>
    (function() {
        var sections = ['home','services','location','how-it-works','testimonials','why-choose','about'];
        var navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        window.addEventListener('scroll', function() {
            var scrollY = window.pageYOffset + 80;
            var current = 'home';
            sections.forEach(function(id) {
                var el = document.getElementById(id);
                if (el && el.offsetTop <= scrollY) current = id;
            });
            navLinks.forEach(function(link) {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) link.classList.add('active');
            });
        });
    })();
    </script>

</html>