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
    <link href="/assets/css/design-tokens.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/availability-picker.css" rel="stylesheet">
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

        /* ---- MOBILE BOOKING CTA ----
           The sticky card is display:none below 1200px, and correctly so: a
           300px fixed rail with three selects would swallow a phone screen.
           But that left phones with no call to action at all — nothing
           tappable in the first 986px, and nothing persistent over a 12,000px
           page. This is the same job in the shape a phone can carry.

           Shown at exactly the breakpoint that hides .apx-sticky-card, so the
           two can never appear together. */
        .apx-mobile-cta { display: none; }

        @media (max-width: 1200px) {
            .apx-mobile-cta {
                display: block;
                position: fixed;
                left: 0; right: 0; bottom: 0;
                /* The landing page overflows 12px at phone width (known,
                   from the templates corner badges). A fixed box with
                   left:0;right:0 sizes to that wider document and ends up
                   12px past the screen, shifting the button off-centre.
                   Clamped to the viewport so this bar is right whether or
                   not the overflow is ever fixed. */
                max-width: 100vw;
                /* Under Bootstrap's modal layers (1050+) so the booking dialog
                   covers it, above the page. */
                z-index: 1039;
                background: #fff;
                border-top: 1px solid rgba(0,0,0,.08);
                box-shadow: 0 -4px 16px rgba(0,0,0,.10);
                /* The home indicator occupies the bottom ~34px on a modern
                   iPhone; without this the button sits under it. */
                padding: 10px 16px calc(10px + env(safe-area-inset-bottom, 0px));
            }
            .apx-mobile-cta .btn {
                width: 100%;
                /* 48px: comfortably above the 44px minimum touch target. */
                min-height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }

            /* The bar would otherwise sit on top of the last of the footer,
               and on top of the back-to-top button at bottom-right. */
            body { padding-bottom: 76px; }
            .back-to-top { bottom: calc(86px + env(safe-area-inset-bottom, 0px)); }
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
        {{-- These two were "4.8/5 from 500+ Reviews" and "5,000+ Happy
             Customers", inherited from the template. There is no review
             system, and the figures were invented — sitting beside real
             photographs of the shop, they read as claims about APX Motors
             rather than as filler.

             Both are now counted from the service catalogue at render time,
             so they cannot drift out of true or be contradicted by the page
             below them. --}}
        <div class="apx-sc-trust">
            <div class="apx-sc-trust-item">
                <div class="apx-sc-trust-val">{{ $services->count() }}</div>
                <span class="apx-sc-trust-lbl">Services<br>Available</span>
            </div>
            {{-- "With Any Service" is not decoration. Complimentary work is an
                 add-on and a booking of only free services is refused, so a
                 badge reading just "Free Vehicle Health Check" would promise
                 something the booking form then declines to do. --}}
            <div class="apx-sc-trust-item">
                <div class="apx-sc-trust-val">Free</div>
                <span class="apx-sc-trust-lbl">Health Check<br>With Any Service</span>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var SC_VEHICLES = @json(config('vehicles'));
        /* The vehicles the shop sees, from config/vehicles.php. This list
           used to be written out twice in this file, verbatim, and the
           customer dashboard and the admin forms could not reach either
           copy — so each invented its own idea of a make and a model. */
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
            if (!make) { ApxAlertModal.show({ variant: 'error', title: 'Select a manufacturer', message: 'Please select a manufacturer first.' }); return; }
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
            <h2 class="m-0 text-primary"><img src="/assets/img/apx-black-logo.png" alt="apx logo" width="100" height="120">AUTOMAI</h2>
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

                {{-- Login inside the collapsed menu. The desktop button below
                     is d-none d-lg-block, so below 992px it was hidden and
                     there was no other way in from the landing page.
                     min-height keeps the tap target at 44px.

                     Both this and the desktop button switch on auth state: the
                     page used to show "Login" to everyone, so a signed-in
                     customer arriving here looked logged out, and clicking
                     Login bounced them straight back to their dashboard
                     without asking for anything. Nothing was wrong with the
                     session; the page simply never looked at it. --}}
                @auth
                <a href="{{ route(Auth::user()->dashboardRoute()) }}"
                   class="btn btn-primary d-lg-none mt-3 d-flex align-items-center justify-content-center"
                   style="min-height:48px;">
                    <i class="fa fa-tachometer-alt me-2"></i> My Dashboard
                </a>
                <form method="POST" action="{{ route('logout') }}" class="d-lg-none mt-2">
                    @csrf
                    <button type="submit"
                            class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center"
                            style="min-height:48px;">
                        <i class="fa fa-sign-out-alt me-2"></i> Log out
                    </button>
                </form>
                @else
                <a href="{{ route('login') }}"
                   class="btn btn-primary d-lg-none mt-3 d-flex align-items-center justify-content-center"
                   style="min-height:48px;">
                    <i class="fa fa-sign-in-alt me-2"></i> Login
                </a>
                @endauth
            </div>
            @auth
            <div class="d-none d-lg-flex align-items-center">
                <a href="{{ route(Auth::user()->dashboardRoute()) }}" class="btn btn-primary py-4 px-lg-5">
                    My Dashboard
                </a>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-light py-4 px-4" title="Log out">
                        <i class="fa fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>
            @else
            <a href="{{ route('login') }}" class="btn btn-primary py-4 px-lg-5 d-none d-lg-block">Login</a>
            @endauth
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
                                <h6 class="text-white text-uppercase mb-3 animated slideInDown">APX Automai</h6>
                                <h1 class="display-3 text-white mb-4 pb-3 animated slideInDown">Your Trusted Auto Service Center in Quezon City</h1>
                                <a href="#" class="btn btn-primary py-3 px-5 fw-bold animated slideInDown"
                                   data-bs-toggle="modal" data-bs-target="#bookingModal">
                                    <i class="fas fa-calendar-plus me-2"></i>Book a Service
                                </a>

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
                                <a href="#" class="btn btn-primary py-3 px-5 fw-bold animated slideInDown"
                                   data-bs-toggle="modal" data-bs-target="#bookingModal">
                                    <i class="fas fa-calendar-plus me-2"></i>Book a Service
                                </a>

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
        .apx-svc-search-wrap { position:relative; max-width:360px; margin-bottom:20px; }
        .apx-svc-search-wrap i { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#999; font-size:0.85rem; }
        .apx-svc-search { width:100%; padding:10px 16px 10px 40px; border:2px solid #ddd; border-radius:999px; font-size:0.88rem; outline:none; transition:border-color 0.2s; }
        .apx-svc-search:focus { border-color:#e63946; }
        .apx-svc-empty { display:none; text-align:center; padding:40px 20px; color:#888; grid-column:1/-1; }
        .apx-svc-empty.show { display:block; }
    </style>

    <div id="services" class="apx-section">
        <div class="container">
            <div class="apx-section-header wow fadeInUp mb-4" data-wow-delay="0.1s">
                <h6 class="text-primary text-uppercase mb-2">What We Offer</h6>
                <h1 class="mb-2">Our Services</h1>
                <p class="text-muted">Click any service to book — no account needed</p>
            </div>
            <div class="apx-svc-search-wrap wow fadeInUp" data-wow-delay="0.1s">
                <i class="fa fa-search"></i>
                <input type="text" class="apx-svc-search" id="apx-svc-search" placeholder="Search services...">
            </div>
            <div class="apx-filter-bar wow fadeInUp" data-wow-delay="0.2s">
                <button class="apx-filter-btn active" data-filter="all">All Services</button>
                @foreach($services->pluck('category')->unique()->filter() as $cat)
                <button class="apx-filter-btn" data-filter="{{ \Illuminate\Support\Str::slug($cat) }}">{{ $cat }}</button>
                @endforeach
            </div>
            <div class="apx-cards-grid wow fadeInUp" data-wow-delay="0.3s">
                @php
                    $categoryIcons = [
                        'Engine & Oil'       => 'fa-oil-can',
                        'CVT & Transmission' => 'fa-sync-alt',
                        'Brakes & Pipes'     => 'fa-life-ring',
                        'Inspection'         => 'fa-search',
                        'Free Services'      => 'fa-gift',
                    ];
                @endphp
                @forelse($services as $s)
                <a href="#" class="apx-svc-card" data-cat="{{ \Illuminate\Support\Str::slug($s->category ?? 'general') }}" onclick="apxOpenWithService(event, {{ $s->id }})">
                    <div class="apx-svc-cat">{{ $s->category ?? 'General' }}</div>
                    <div class="apx-svc-icon-wrap"><i class="fa {{ $categoryIcons[$s->category] ?? 'fa-wrench' }}"></i></div>
                    <div class="apx-svc-name">{{ $s->name }}</div>
                    <div class="apx-svc-desc">{{ $s->description }}</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> {{ $s->duration }} min &middot; {{ $s->price > 0 ? '₱'.number_format($s->price, 0) : 'Free' }}</div>
                </a>
                @empty
                <p class="text-muted">No services available right now. Please check back soon.</p>
                @endforelse
                <div class="apx-svc-empty" id="apx-svc-empty"><i class="fa fa-search" style="font-size:1.6rem;margin-bottom:10px;display:block;"></i>No services found. Try a different search.</div>
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
                            <p class="mb-2"><i class="fa fa-clock text-primary me-2"></i>Mon – Fri: 9:00 AM – 6:00 PM &nbsp;|&nbsp; Sat – Sun: 9:00 AM – 12:00 PM</p>
                            <p class="mb-0"><i class="fa fa-phone-alt text-primary me-2"></i>+63 9544 8888 50</p>
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
                        {{-- The shop's own signage. It lives here rather than in
                             the hero because the carousel lays a 70% scrim and
                             a headline over its image, which left the sign
                             unreadable and set two pieces of typography
                             fighting in one frame. This block has neither. --}}
                        <img class="position-absolute img-fluid w-100 h-100" src="/assets/img/storefront.jpg" style="object-fit: cover;" alt="The APX Motors Service Center sign on Tandang Sora, Quezon City">
                        {{-- A "15 Years Experience" badge sat here, from the
                             template. It was overlapping the shop's own sign
                             and asserting a figure nobody had verified. Any
                             replacement number would be the same mistake, so
                             what it says now is true of the shop whatever year
                             it opened: it takes both kinds of vehicle. --}}
                        <div class="position-absolute top-0 end-0 mt-n4 me-n4 py-4 px-5" style="background: rgba(0, 0, 0, .35);">
                            <h4 class="text-white mb-0">Cars &amp;</h4>
                            <h4 class="text-white mb-0">Motorcycles</h4>
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
            /* filter buttons + live search */
            var btns       = document.querySelectorAll(".apx-filter-btn");
            var cards      = document.querySelectorAll(".apx-svc-card");
            var searchBox  = document.getElementById("apx-svc-search");
            var emptyState = document.getElementById("apx-svc-empty");
            var activeFilter = "all";

            function applyFilters() {
                var query = (searchBox.value || "").trim().toLowerCase();
                var visibleCount = 0;
                cards.forEach(function (card) {
                    var cats = card.getAttribute("data-cat") || "";
                    var matchesCategory = activeFilter === "all" || cats.indexOf(activeFilter) !== -1;
                    var name = (card.querySelector(".apx-svc-name") || {}).textContent || "";
                    var desc = (card.querySelector(".apx-svc-desc") || {}).textContent || "";
                    var matchesSearch = query === "" || name.toLowerCase().indexOf(query) !== -1 || desc.toLowerCase().indexOf(query) !== -1;
                    var visible = matchesCategory && matchesSearch;
                    card.classList.toggle("hidden", !visible);
                    if (visible) visibleCount++;
                });
                if (emptyState) emptyState.classList.toggle("show", visibleCount === 0);
            }

            btns.forEach(function (btn) {
                btn.addEventListener("click", function () {
                    btns.forEach(function (b) { b.classList.remove("active"); });
                    btn.classList.add("active");
                    activeFilter = btn.getAttribute("data-filter");
                    applyFilters();
                });
            });

            if (searchBox) {
                searchBox.addEventListener("input", applyFilters);
            }

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
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+63 9544 8888 50</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>apxautomai@gmail.com</p>
                    <div class="d-flex pt-2">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-light mb-4">Opening Hours</h4>
                    <h6 class="text-light">Monday - Friday:</h6>
                    <p class="mb-4">09:00 AM - 06:00 PM</p>
                    <h6 class="text-light">Saturday - Sunday:</h6>
                    <p class="mb-0">09:00 AM - 12:00 PM</p>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; {{ date('Y') }} <a class="border-bottom" href="#home">APX Automai</a>. All Rights Reserved.
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="footer-menu">
                            <a href="#home">Home</a>
                            <a href="#services">Services</a>
                            <a href="#about">About</a>
                            <a href="#location">Contact</a>
                            <a href="{{ route('terms') }}">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    {{-- Phones and tablets only; see .apx-mobile-cta. --}}
    <div class="apx-mobile-cta">
        <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#bookingModal">
            <i class="fas fa-calendar-plus"></i> Book a Service
        </button>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="/assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/lib/wow/wow.min.js"></script>
    <script src="/assets/lib/easing/easing.min.js"></script>
    <script src="/assets/lib/waypoints/waypoints.min.js"></script>
    <script src="/assets/lib/counterup/counterup.min.js"></script>
    <script src="/assets/lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="/assets/lib/tempusdominus/js/moment.min.js"></script>    <script src="/assets/lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="/assets/lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/availability-picker.js"></script>
    <script src="/assets/js/form-validate.js"></script>
    <script src="/assets/js/plate-input.js"></script>
    <script src="/assets/js/alert-modal.js"></script>
    <script src="/assets/js/free-only-guard.js"></script>

    <x-alert-modal id="apx-alert-modal" />

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
        /* Bundles sit above the services and read as a different kind of
           thing: a green saving rather than a category, the list price struck
           through, and the included services spelled out. */
        .apx-bundle-card { border-color:#b8dfc2; background:#f6fdf8; }
        .apx-bundle-card:hover { border-color:#16a34a; }
        .apx-bundle-card.selected { border-color:#16a34a; background:#eafaf0; color:#14532d; }
        .apx-bundle-card.selected .apx-svc-check { background:#16a34a; }
        .apx-bundle-tag { background:#d4edda; color:#155724; font-weight:700; }
        .apx-bundle-card.selected .apx-modal-svc-tag { background:#b8dfc2; color:#0d3d1a; }
        .apx-bundle-includes { display:block; font-size:10.5px; color:#6b7280; margin-top:5px; line-height:1.45; }
        .apx-bundle-card .apx-modal-svc-price s { color:#9ca3af; font-weight:400; margin-right:4px; }
        /* Informational, so blue — this is an offer, not an error. */
        .apx-acct-nudge { margin-top:10px; padding:9px 12px; border-radius:8px; background:rgba(59,130,246,.09); border:1px solid rgba(59,130,246,.22); font-size:0.76rem; color:#334155; line-height:1.5; }
        .apx-acct-nudge i { color:#3B82F6; margin-right:5px; }
        .apx-acct-nudge a { color:#2563EB; font-weight:700; text-decoration:underline; margin-left:4px; }

        /* GUIDED STEP 1
           Built from the modal's own type and spacing rather than the
           mockup's pixel values, so it sits inside the existing design
           instead of beside it. Every card is a real button: the whole
           surface is the tap target, and it is reachable by keyboard
           without any extra handling. */
        .apx-path-heading { font-family:'Barlow',sans-serif; font-weight:700; color:#222; font-size:1rem; margin:0 0 4px; }
        .apx-path-sub { font-size:0.8rem; color:#888; margin:0 0 14px; line-height:1.5; }

        .apx-path-card {
            display:flex; align-items:center; gap:14px; width:100%; text-align:left;
            background:#fff; border:1.5px solid #e8e8e8; border-radius:12px;
            padding:16px 16px; margin-bottom:10px; cursor:pointer;
            transition:border-color .18s, box-shadow .18s;
            font-family:'Ubuntu',sans-serif; min-height:44px;
        }
        .apx-path-card:hover { border-color:#e63946; box-shadow:0 2px 10px rgba(230,57,70,.08); }
        .apx-path-card:focus-visible { outline:2px solid #e63946; outline-offset:2px; }
        .apx-path-icon {
            flex:0 0 auto; width:40px; height:40px; border-radius:10px; background:#f5f5f5;
            display:flex; align-items:center; justify-content:center; color:#444; font-size:.95rem;
        }
        .apx-path-card:hover .apx-path-icon { background:#fff0f1; color:#e63946; }
        .apx-path-text { flex:1 1 auto; min-width:0; display:flex; flex-direction:column; gap:3px; }
        .apx-path-title { font-weight:700; color:#222; font-size:.92rem; line-height:1.3; }
        .apx-path-desc { font-size:.78rem; color:#888; line-height:1.45; }
        .apx-path-chev { flex:0 0 auto; color:#ccc; font-size:.8rem; }

        .apx-path-back {
            background:none; border:none; padding:6px 0; margin:0 0 8px; cursor:pointer;
            color:#888; font-size:.8rem; font-family:'Ubuntu',sans-serif; min-height:44px;
            display:inline-flex; align-items:center; gap:6px;
        }
        .apx-path-back:hover { color:#e63946; }
        .apx-path-back:focus-visible { outline:2px solid #e63946; outline-offset:2px; }

        .apx-chip-row { display:flex; flex-wrap:wrap; gap:7px; margin-bottom:12px; }
        .apx-chip {
            border:1.5px solid #e8e8e8; background:#fff; border-radius:999px;
            padding:8px 14px; font-size:.78rem; color:#555; cursor:pointer;
            font-family:'Ubuntu',sans-serif; min-height:36px;
        }
        .apx-chip:hover { border-color:#e63946; color:#e63946; }
        .apx-chip.selected { background:#fff5f5; border-color:#e63946; color:#c0392b; font-weight:700; }
        .apx-chip:focus-visible { outline:2px solid #e63946; outline-offset:2px; }

        .apx-path-note {
            font-size:.78rem; color:#555; line-height:1.55;
            background:rgba(59,130,246,.07); border:1px solid rgba(59,130,246,.18);
            border-radius:8px; padding:10px 12px;
        }


        /* An inline action inside an informational note, so it reads as a
           link rather than competing with the footer buttons. */
        .apx-path-link {
            background:none; border:none; padding:0; cursor:pointer;
            color:#2563EB; font-weight:700; text-decoration:underline;
            font-size:inherit; font-family:inherit;
        }
        .apx-path-link:focus-visible { outline:2px solid #e63946; outline-offset:2px; }

        .apx-modal-search {
            width:100%; border:1.5px solid #e8e8e8; border-radius:8px; padding:10px 12px;
            font-size:.84rem; font-family:'Ubuntu',sans-serif; margin-bottom:10px; min-height:44px;
        }
        .apx-modal-search:focus { outline:none; border-color:#e63946; }

        /* No horizontal scroll at 320px: the grid drops to one column before
           the cards can force the dialog wider than the screen. */
        @media (max-width: 400px) {
            .apx-modal-svc-grid { grid-template-columns:1fr; }
            .apx-path-card { padding:14px 12px; gap:11px; }
            .apx-path-icon { width:36px; height:36px; }
        }
        .apx-modal-svc-card.selected .apx-modal-svc-tag.free { background:#b8dfc2; color:#0d3d1a; }
        .apx-modal-svc-price { font-size:11px; color:#aaa; display:block; margin-top:3px; }
        .apx-modal-svc-card.selected .apx-modal-svc-price { color:#c0392b; }

        /* ---- estimate bar ---- */
        /* Informational, not an error: the customer has done nothing wrong,
           they are one service away from a booking. Blue per the app's
           error/info convention — red is for failures. */
        .apx-free-only-note { display:flex; align-items:flex-start; gap:9px; background:rgba(59,130,246,.08); border:1px solid rgba(59,130,246,.22); border-radius:8px; padding:10px 14px; margin-top:10px; font-size:0.82rem; color:#1e3a5f; line-height:1.5; }
        .apx-free-only-note i { color:#3B82F6; margin-top:2px; flex-shrink:0; }
        /* Visibly unavailable, never silently dead: the note above says why. */
        .btn-apx-next[disabled] { opacity:0.45; cursor:not-allowed; }
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

        /* ---- membership nudge ----
           Blue, not red. design-tokens.css reserves --red for errors and
           destructive actions; this is a neutral upsell at the end of a
           SUCCESSFUL booking, and it sat in the same #e63946 as the Confirm
           button beside it, so the two competed for the same click.

           Fills use --info-dark rather than --info: white on --info measures
           3.7:1, which fails AA for the 10px badge and the 13px button label.
           On --info-dark it is 5.2:1. The lighter --info is kept for the
           border and the perk dots, where nothing sits on top of it. */
        .apx-member-card { border:2px solid var(--info); border-radius:12px; padding:14px 16px; background:var(--info-surface); margin-bottom:12px; }
        .apx-member-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
        .apx-member-badge { font-size:10px; font-weight:700; background:var(--info-dark); color:#fff; padding:3px 9px; border-radius:4px; letter-spacing:0.03em; }
        .apx-member-title { font-size:0.88rem; font-weight:700; color:var(--info-dark); margin:0 0 2px; }
        .apx-member-sub { font-size:0.75rem; color:var(--info-dark); margin:0; }
        .apx-member-perks { display:grid; grid-template-columns:1fr 1fr; gap:5px 12px; margin-bottom:12px; }
        .apx-member-perk { display:flex; align-items:center; gap:6px; font-size:0.78rem; color:var(--info-dark); }
        .apx-perk-dot { width:5px; height:5px; border-radius:50%; background:var(--info); flex-shrink:0; }
        .apx-btn-member { width:100%; padding:9px; font-size:0.83rem; font-weight:700; background:var(--info-dark); color:#fff; border:none; border-radius:8px; cursor:pointer; font-family:'Ubuntu',sans-serif; letter-spacing:0.02em; transition:filter 0.2s; }
        /* Darkened rather than swapped to another token: --info-dark is already
           the darker of the pair, so the usual base-to-dark hover has nowhere
           left to go without inventing a hex value. */
        .apx-btn-member:hover { filter:brightness(0.9); }
        .apx-btn-skip { display:block; text-align:center; font-size:0.75rem; color:var(--info-dark); margin-top:7px; cursor:pointer; background:none; border:none; width:100%; font-family:'Ubuntu',sans-serif; }

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
                            <div class="apx-step-item active" id="apx-s1"><div class="apx-step-dot">1</div><span class="apx-step-lbl">Choose service</span></div>
                            <div class="apx-step-item" id="apx-s2"><div class="apx-step-dot">2</div><span class="apx-step-lbl">Your details</span></div>
                            <div class="apx-step-item" id="apx-s3"><div class="apx-step-dot">3</div><span class="apx-step-lbl">Confirm</span></div>
                        </div>
                    </div>
                </div>

                <!-- body -->
                <div class="modal-body" style="padding:20px 24px;">

                    <!-- STEP 1: Services -->
                    <div id="apx-view-1">

                        {{-- 1a — THE THREE PATHS
                             Most people booking a car in do not know what
                             "CVT Fluid Change" means, and a wall of service
                             names is where they give up. This asks what is
                             wrong instead, and the reassurance line exists
                             because "I don't know" is the honest answer for
                             most customers and they should not feel stuck. --}}
                        <div id="apx-path-root">
                            <h6 class="apx-path-heading">What do you need help with?</h6>
                            <p class="apx-path-sub">Choose the option that best describes what you need. Not sure? That's okay.</p>

                            <button type="button" class="apx-path-card" onclick="apxChoosePath('checkup')">
                                <span class="apx-path-icon"><i class="fas fa-search"></i></span>
                                <span class="apx-path-text">
                                    <span class="apx-path-title">General Check-up / Inspection</span>
                                    <span class="apx-path-desc">Something seems wrong, or I want my car checked.</span>
                                </span>
                                <span class="apx-path-chev"><i class="fas fa-chevron-right"></i></span>
                            </button>

                            <button type="button" class="apx-path-card" onclick="apxChoosePath('pms')">
                                <span class="apx-path-icon"><i class="fas fa-wrench"></i></span>
                                <span class="apx-path-text">
                                    <span class="apx-path-title">Periodic Maintenance (PMS)</span>
                                    <span class="apx-path-desc">Routine servicing, oil changes, and scheduled maintenance.</span>
                                </span>
                                <span class="apx-path-chev"><i class="fas fa-chevron-right"></i></span>
                            </button>

                            <button type="button" class="apx-path-card" onclick="apxChoosePath('browse')">
                                <span class="apx-path-icon"><i class="fas fa-list"></i></span>
                                <span class="apx-path-text">
                                    <span class="apx-path-title">Browse All Services</span>
                                    <span class="apx-path-desc">I already know which service I want.</span>
                                </span>
                                <span class="apx-path-chev"><i class="fas fa-chevron-right"></i></span>
                            </button>
                        </div>

                        {{-- 1b — CHECK-UP
                             The inspection is already chosen; the chips only
                             write into the notes field the details step
                             already has, so nothing about that step changes
                             and no new column is needed to carry a symptom. --}}
                        <div id="apx-path-checkup" style="display:none;">
                            <button type="button" class="apx-path-back" onclick="apxChoosePath('root')">
                                <i class="fas fa-chevron-left"></i> Back
                            </button>
                            <h6 class="apx-path-heading">What's going on?</h6>
                            <p class="apx-path-sub">Tap anything that sounds right, or skip this — the mechanic will check either way.</p>
                            <div class="apx-chip-row" id="apx-symptom-chips"></div>
                            <div class="apx-path-note" id="apx-checkup-note"></div>
                        </div>

                        {{-- 1c — PERIODIC MAINTENANCE --}}
                        <div id="apx-path-pms" style="display:none;">
                            <button type="button" class="apx-path-back" onclick="apxChoosePath('root')">
                                <i class="fas fa-chevron-left"></i> Back
                            </button>
                            <h6 class="apx-path-heading">Maintenance packages</h6>
                            <p class="apx-path-sub">Each package covers the servicing that's usually done together, so you don't have to pick the parts yourself.</p>
                            <div class="apx-modal-svc-grid" id="apx-pms-grid"></div>
                            <div class="apx-path-note" id="apx-pms-empty" style="display:none;">
                                No maintenance packages are set up right now. You can still
                                <button type="button" class="apx-path-link" onclick="apxChoosePath('browse')">browse the individual services</button>,
                                or choose a general check-up and let the mechanic advise.
                            </div>
                        </div>

                        {{-- 1d — BROWSE ALL --}}
                        <div id="apx-path-browse" style="display:none;">
                            <button type="button" class="apx-path-back" onclick="apxChoosePath('root')">
                                <i class="fas fa-chevron-left"></i> Back
                            </button>
                            {{-- Services only. Packages live on the
                                 maintenance path, which is what they are
                                 for; listing them here as well would ask
                                 the customer to understand the difference
                                 twice. --}}
                            <div id="apx-browse-services">
                                <input type="text" class="apx-modal-search" id="apx-modal-search"
                                       placeholder="Search services…" aria-label="Search services">
                                <div class="apx-chip-row" id="apx-cat-chips"></div>
                            </div>
                            <div class="apx-modal-svc-grid" id="apx-svc-grid"></div>
                            <div class="apx-path-note" id="apx-browse-empty" style="display:none;">
                                No services match that search.
                            </div>
                        </div>

                        <div class="apx-estimate-bar" id="apx-estimate-bar" style="display:none;">
                            <span class="apx-est-count" id="apx-sel-count">No services selected</span>
                            <span class="apx-est-val" id="apx-est-total">₱0</span>
                        </div>
                        {{-- The free-only rule, said while there is still
                             something to do about it. Sits with the selection
                             rather than by the Next button, because the fix is
                             to add a service, not to press something else.
                             Wording comes from BookingPolicy, so it cannot
                             drift from what the server refuses with. --}}
                        {{-- fa-info-circle, not fa-circle-info: this page is on
                             Font Awesome 5.10.0, which does not know the v6
                             names and silently renders nothing. --}}
                        <div id="apx-free-only-note" class="apx-free-only-note" style="display:none;" role="status">
                            <i class="fas fa-info-circle"></i>
                            <span id="apx-free-only-text"></span>
                        </div>
                        {{-- Shown only while the account discount is running
                             and something is selected, so the nudge quotes a
                             real figure rather than an abstract percentage. --}}
                        <div id="apx-acct-nudge" class="apx-acct-nudge" style="display:none;">
                            <i class="fas fa-info-circle"></i>
                            <span id="apx-acct-nudge-text"></span>
                            <a href="{{ route('register') }}">Create an account</a>
                        </div>
                    </div>

                    <!-- STEP 2: Details -->
                    <div id="apx-view-2" style="display:none;">
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label">Full name</label><input type="text" class="form-control" id="apx-f-name" placeholder="Juan dela Cruz"></div>
                            <div class="col-6"><label class="form-label">Email</label><input type="email" class="form-control" id="apx-f-email" placeholder="juan@email.com"></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-12"><label class="form-label">Phone</label><input type="text" class="form-control" id="apx-f-phone" placeholder="09xxxxxxxxx"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preferred date &amp; time</label>
                            <input type="hidden" id="apx-f-date">
                            <input type="hidden" id="apx-f-time">
                            <x-availability-picker id="guestAvp" />
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
                            <div class="col-6"><label class="form-label">Plate number <span style="color:#e63946">*</span></label><input type="text" class="form-control" id="apx-v-plate" placeholder="ABC 1234, 123 ABC or AB 12345"></div>
                        </div>
                        <div class="mt-2"><label class="form-label">Notes / special request</label><textarea class="form-control" id="apx-f-notes" rows="2" placeholder="Anything we should know?"></textarea></div>
                    </div>

                    <!-- STEP 3: Confirm -->
                    <div id="apx-view-3" style="display:none;">
                        <div class="apx-confirm-block">
                            <div class="apx-confirm-row"><span>Name</span><span id="apx-c-name">—</span></div>
                            <div class="apx-confirm-row"><span>Email</span><span id="apx-c-email">—</span></div>
                            <div class="apx-confirm-row"><span>Phone</span><span id="apx-c-phone">—</span></div>
                            <div class="apx-confirm-row"><span>Date &amp; Time</span><span id="apx-c-date">—</span></div>
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
                                <div class="apx-member-perk"><div class="apx-perk-dot"></div>Saved vehicles for faster booking</div>
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

    @php
        $servicesJs = $services->map(function ($s) {
            return [
                'id'       => $s->id,
                'name'     => $s->name,
                'tag'      => \Illuminate\Support\Str::slug($s->category ?? 'general'),
                'category' => $s->category ?? 'General',
                // Null means every vehicle, same convention as bundles.
                'vehicle'  => $s->vehicle_type,
                'price'    => (float) $s->price,
            ];
        })->values();

        $bundlesJs = ($bundles ?? collect())->map(function ($b) {
            return [
                'id'        => $b->id,
                'name'      => $b->name,
                'price'     => (float) $b->price,
                'list'      => $b->listPrice(),
                'saves'     => $b->savings(),
                'percent'   => $b->savingsPercent(),
                'vehicle'   => $b->vehicle_type,
                'includes'  => $b->services->pluck('name')->all(),
            ];
        })->values();

        // Keyed separately so the availability picker can reserve the right
        // length of bay for a bundle without re-deriving it from names.
        $bundleServiceIds = ($bundles ?? collect())
            ->mapWithKeys(fn ($b) => [$b->id => $b->services->pluck('id')->all()]);

        // For the nudge only. The server decides the real discount at booking
        // time; this is just what the figure would be.
        $accountDiscount = [
            'enabled' => (bool) \App\Models\Setting::get('account_discount_enabled'),
            'percent' => (int) \App\Models\Setting::get('account_discount_percent'),
        ];

        // Which services each guided path offers. A view of the existing
        // category data — nothing is re-filed, so the browse filters keep
        // working exactly as they did.
        $paths       = config('apx.booking_paths');
        $checkupSvc  = $services->firstWhere('name', $paths['checkup_service'])
                       ?? $services->firstWhere('category', $paths['checkup_category']);

        $guidedPaths = [
            'checkupId'   => $checkupSvc?->id,
            'checkupName' => $checkupSvc?->name,
            'symptoms'    => $paths['checkup_symptoms'],
            'categories'  => $services->pluck('category')->filter()->unique()->values()->all(),
        ];
    @endphp
    <script>
    (function () {
        var SERVICES = @json($servicesJs);
        var BUNDLES  = @json($bundlesJs);

        // One wording for the rule, taken from the constant the server refuses
        // with. A second copy here would be free to drift from the message the
        // customer eventually sees.
        FreeOnlyGuard.configure(@json(\App\Support\BookingPolicy::FREE_ONLY_MESSAGE));
        var BUNDLE_SERVICE_IDS = @json((object) $bundleServiceIds);
        var ACCOUNT_DISCOUNT   = @json($accountDiscount);
        var GUIDED             = @json($guidedPaths);
        var VEHICLES = @json(config('vehicles'));
        /* The vehicles the shop sees, from config/vehicles.php. This list
           used to be written out twice in this file, verbatim, and the
           customer dashboard and the admin forms could not reach either
           copy — so each invented its own idea of a make and a model. */

        var selected = new Set();
        // A bundle is the whole order, so it is exclusive: picking one clears
        // any services, and picking a service clears the bundle. Allowing
        // both would raise the obvious question of whether the bundle's
        // discount applies to the extras, and the answer is no.
        var selectedBundle = null;
        var vtype = 'car';
        var step = 1;

        var guestPicker = new AvailabilityPicker({
            root: '#guestAvp',
            dateInput: document.getElementById('apx-f-date'),
            timeInput: document.getElementById('apx-f-time'),
            // A bundle reserves the bay for all of its services, so the
            // availability check must see them, not an empty list.
            getServiceIds: function () {
                var b = currentBundle();
                if (b) { return BUNDLE_SERVICE_IDS[b.id] || []; }
                return Array.from(selected).map(function (i) { return SERVICES[i].id; });
            },
        });

        function fmt(price) { return price === 0 ? 'FREE' : '₱'+price.toLocaleString(); }

        /* ── Guided step 1 ──────────────────────────────────────────────
           Four sub-screens inside what used to be a single grid. Steps 2
           and 3 are untouched: whatever is chosen here lands in the same
           `selected` set and `selectedBundle` the old screen filled, so the
           booking that gets posted is identical. */
        var pathView   = 'root';
        var catFilter  = '';
        var symptoms   = new Set();

        window.apxChoosePath = function (which) {
            pathView = which;

            ['root', 'checkup', 'pms', 'browse'].forEach(function (name) {
                var el = document.getElementById('apx-path-' + name);
                if (el) el.style.display = (name === which) ? '' : 'none';
            });

            // The estimate bar and Next button are meaningless on the menu
            // itself — there is nothing chosen yet to total or advance.
            document.getElementById('apx-estimate-bar').style.display = which === 'root' ? 'none' : '';
            document.getElementById('apx-btn-next').style.visibility  = which === 'root' ? 'hidden' : 'visible';
            apxSyncBack();

            if (which === 'checkup') {
                apxStartCheckup();
            } else if (which === 'pms') {
                buildGrid();
            } else if (which === 'browse') {
                buildGrid();
            }

            // Move focus to the new screen so a keyboard user is not left
            // on a button that is now hidden.
            var target = document.querySelector('#apx-path-' + which + ' button, #apx-path-' + which + ' input');
            if (target) target.focus({ preventScroll: true });

            apxUpdateEst();
        };

        /* Back is shown whenever there is somewhere to go back to: a later
           step, or a sub-screen of step 1. */
        function apxSyncBack() {
            document.getElementById('apx-btn-back').style.visibility =
                (step > 1 || pathView !== 'root') ? 'visible' : 'hidden';
        }

        function apxStartCheckup() {
            // The inspection is the booking; the chips only colour in the
            // notes field step 2 already has.
            selected.clear();
            selectedBundle = null;

            var idx = SERVICES.findIndex(function (s) { return s.id === GUIDED.checkupId; });
            if (idx >= 0) selected.add(idx);

            document.getElementById('apx-checkup-note').innerHTML =
                '<i class="fas fa-info-circle" style="color:#3B82F6;margin-right:5px;"></i>'
                + 'We will book a <strong>' + (GUIDED.checkupName || 'general inspection')
                + '</strong>. If the mechanic finds something, they will talk you through it before any work starts.';

            var row = document.getElementById('apx-symptom-chips');
            row.innerHTML = '';
            GUIDED.symptoms.forEach(function (text) {
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'apx-chip' + (symptoms.has(text) ? ' selected' : '');
                chip.textContent = text;
                chip.setAttribute('aria-pressed', symptoms.has(text) ? 'true' : 'false');
                chip.onclick = function () {
                    symptoms.has(text) ? symptoms.delete(text) : symptoms.add(text);
                    chip.classList.toggle('selected');
                    chip.setAttribute('aria-pressed', symptoms.has(text) ? 'true' : 'false');
                    apxWriteSymptomsToNotes();
                };
                row.appendChild(chip);
            });
        }

        /* Writes into the existing notes field rather than anywhere new.
           Anything the customer typed themselves is preserved below the
           line, so tapping a chip never eats their own words. */
        function apxWriteSymptomsToNotes() {
            var field = document.getElementById('apx-f-notes');
            if (!field) return;

            var marker = 'Reported: ';
            var own    = field.value.split('\n').filter(function (line) {
                return line.indexOf(marker) !== 0;
            }).join('\n').trim();

            var line = symptoms.size ? marker + Array.from(symptoms).join(', ') : '';
            field.value = [line, own].filter(Boolean).join('\n');
        }

        function apxBuildCategoryChips() {
            var row = document.getElementById('apx-cat-chips');
            if (!row) return;
            row.innerHTML = '';

            [''].concat(GUIDED.categories).forEach(function (cat) {
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'apx-chip' + (catFilter === cat ? ' selected' : '');
                chip.textContent = cat === '' ? 'All' : cat;
                chip.onclick = function () { catFilter = cat; apxBuildCategoryChips(); buildGrid(); };
                row.appendChild(chip);
            });
        }

        function buildGrid() {
            // Which grid is being filled depends on the sub-screen.
            var grid = document.getElementById(pathView === 'pms' ? 'apx-pms-grid' : 'apx-svc-grid');
            if (!grid) return;
            grid.innerHTML = '';

            // Periodic maintenance IS the packages: most customers cannot
            // name the services their vehicle is due for, which is the whole
            // reason bundles exist. Browse All stays individual services, for
            // the customers who already know what they want.
            var showBundles = (pathView === 'pms');

            (showBundles ? BUNDLES.filter(function (b) { return !b.vehicle || b.vehicle === vtype; }) : [])
                   .forEach(function (b) {
                var card = document.createElement('div');
                card.className = 'apx-modal-svc-card apx-bundle-card'
                    + (selectedBundle === b.id ? ' selected' : '');
                card.dataset.bundle = b.id;
                card.innerHTML = '<div class="apx-svc-check"><svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1.5 4.5l2 2 4-4" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/></svg></div>'
                    + b.name
                    + '<br><span class="apx-modal-svc-tag apx-bundle-tag">bundle · saves ' + fmt(b.saves) + '</span>'
                    + '<span class="apx-bundle-includes">' + b.includes.join(' + ') + '</span>'
                    + '<span class="apx-modal-svc-price"><s>' + fmt(b.list) + '</s> ' + fmt(b.price) + '</span>';
                card.onclick = function () { apxToggleBundle(b.id, card); };
                grid.appendChild(card);
            });

            // Which services this screen offers. PMS shows the routine
            // categories; Browse All shows everything, filtered by the
            // search box and category chips; the Bundles tab shows none.
            var search = (document.getElementById('apx-modal-search')?.value || '').trim().toLowerCase();
            var shown  = 0;

            SERVICES.forEach(function(s, i) {
                // The maintenance screen offers packages only.
                if (showBundles) return;

                // Cars do not need a CVT service and bikes have no aircon.
                // Same rule as the bundle filter above: null means all.
                if (s.vehicle && s.vehicle !== vtype) return;

                if (pathView === 'browse') {
                    if (catFilter && s.category !== catFilter) return;
                    if (search && (s.name + ' ' + s.category).toLowerCase().indexOf(search) === -1) return;
                }

                shown++;
                var isFree = s.price === 0;
                var card = document.createElement('div');
                card.className = 'apx-modal-svc-card' + (selected.has(i) ? ' selected' : '');
                card.dataset.idx = i;
                card.innerHTML = '<div class="apx-svc-check"><svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1.5 4.5l2 2 4-4" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/></svg></div>'
                    + s.name
                    // The tag states the condition rather than repeating the
                    // price, which the line below already gives. "free" alone
                    // read as an offer with no strings, which is what sent
                    // customers all the way to step 3 before being refused.
                    + '<br><span class="apx-modal-svc-tag' + (isFree?' free':'') + '">' + (isFree?'free with paid service':s.category) + '</span>'
                    + '<span class="apx-modal-svc-price">' + fmt(s.price) + '</span>';
                card.onclick = function() { apxToggle(i, card); };
                grid.appendChild(card);
            });

            var empty = document.getElementById('apx-browse-empty');
            if (empty) {
                empty.style.display = (pathView === 'browse' && shown === 0) ? '' : 'none';
            }

            var pmsEmpty = document.getElementById('apx-pms-empty');
            if (pmsEmpty) {
                pmsEmpty.style.display = (pathView === 'pms' && grid.children.length === 0) ? '' : 'none';
            }
        }

        function apxToggle(i, card) {
            // Choosing a service drops any bundle.
            if (selectedBundle !== null) {
                selectedBundle = null;
                buildGrid();
                card = document.querySelector('.apx-modal-svc-card[data-idx="' + i + '"]');
            }

            selected.has(i) ? selected.delete(i) : selected.add(i);
            if (card) card.classList.toggle('selected');
            apxUpdateEst();
            guestPicker.refresh();
        }

        function apxToggleBundle(id, card) {
            var wasSelected = selectedBundle === id;
            selectedBundle = wasSelected ? null : id;
            // A bundle is the whole order, so services clear with it.
            selected.clear();
            buildGrid();
            apxUpdateEst();
            guestPicker.refresh();
        }

        function currentBundle() {
            return BUNDLES.find(function (b) { return b.id === selectedBundle; }) || null;
        }

        function apxUpdateEst() {
            var bundle = currentBundle();
            var total  = 0;

            if (bundle) {
                total = bundle.price;
                document.getElementById('apx-sel-count').textContent =
                    bundle.name + ' · ' + bundle.includes.length + ' services';
                document.getElementById('apx-est-total').textContent = fmt(total);
            } else {
                selected.forEach(function(i){ total += SERVICES[i].price; });
                var n = selected.size;
                document.getElementById('apx-sel-count').textContent = n===0 ? 'No services selected' : n+' service'+(n>1?'s':'')+' selected';
                document.getElementById('apx-est-total').textContent = n===0 ? '₱0' : fmt(total);
            }

            apxUpdateNudge(total);
            apxRefreshFreeOnly();
        }

        /* Tell the customer the moment the selection becomes unbookable, and
           stop them going further while it is.

           Hung off apxUpdateEst because every path that changes the selection
           already calls it — services, bundles, the guided screens and the
           reset on close — so there is no route in that can skip this.

           Feedback only. GuestBookingController runs the same rule on the
           request and is the thing that actually refuses; this just means the
           customer finds out now rather than after picking a date and time. */
        function apxRefreshFreeOnly() {
            var note  = document.getElementById('apx-free-only-note');
            var text  = document.getElementById('apx-free-only-text');
            var next  = document.getElementById('apx-btn-next');
            if (!note || !next) return;

            var prices = Array.from(selected).map(function (i) { return SERVICES[i].price; });
            var state  = FreeOnlyGuard.evaluate(prices, currentBundle() !== null);
            var blocked = FreeOnlyGuard.blocks(state);

            text.textContent = FreeOnlyGuard.message;

            // The note lives in the scrolling part of the modal, under a long
            // list of services. Appearing off-screen would leave a disabled
            // Next button with no visible reason beside it, which is the one
            // thing this must not do — so bring it into view as it appears.
            var wasHidden = note.style.display === 'none';
            note.style.display = blocked ? '' : 'none';
            if (blocked && wasHidden && typeof note.scrollIntoView === 'function') {
                note.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }

            // Only ever disabled on step 1: steps 2 and 3 have their own
            // reasons to stop, and an empty selection keeps the button live so
            // its existing "choose a service" message still fires.
            next.disabled = blocked && step === 1;
        }

        /* The guest nudge. Quotes the actual peso saving on what is selected,
           because "save 5%" is an abstraction and "save ₱292" is a decision.
           The figure here is only ever a preview — the server recomputes the
           discount from its own settings and ignores anything posted. */
        function apxUpdateNudge(total) {
            var box = document.getElementById('apx-acct-nudge');
            if (!box) return;

            if (!ACCOUNT_DISCOUNT.enabled || total <= 0) {
                box.style.display = 'none';
                return;
            }

            var saving = Math.round(total * ACCOUNT_DISCOUNT.percent) / 100;
            document.getElementById('apx-acct-nudge-text').textContent =
                'Booking signed in would save you ' + fmt(saving)
                + ' (' + ACCOUNT_DISCOUNT.percent + '% off).';
            box.style.display = 'block';
        }

        // Open modal and pre-select a service by id
        /* Tapping a specific service on the page already knows what the
           customer wants, so the guided menu would only be in the way. It
           opens straight into Browse All with that service selected.
           Deliberately not auto-advancing to step 2: landing there needs a
           "Change" control on step 2 to be any use, and step 2 is out of
           scope. Back still returns to the three paths. */
        window.apxOpenWithService = function(e, svcId) {
            e.preventDefault();
            // Find matching service index
            var idx = SERVICES.findIndex(function(s) {
                return s.id === svcId;
            });
            // Open modal
            var modal = new bootstrap.Modal(document.getElementById('bookingModal'));
            modal.show();
            // After grid is built, select the card
            setTimeout(function() {
                apxChoosePath('browse');

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
            // Bundles can be restricted to one vehicle type, so a switch may
            // change which are on offer. Drop a selection that no longer
            // applies rather than letting the server refuse it later.
            var b = currentBundle();
            if (b && b.vehicle && b.vehicle !== vtype) { selectedBundle = null; }

            // Services need the same treatment, and more urgently: the type
            // toggle is on step 2 but services are chosen on step 1, so a
            // customer can pick car-only work and then say motorcycle. Drop
            // those rather than letting the server refuse the whole booking
            // after they have filled in everything else.
            var dropped = [];
            Array.from(selected).forEach(function (i) {
                var svc = SERVICES[i];
                if (svc && svc.vehicle && svc.vehicle !== vtype) {
                    selected.delete(i);
                    dropped.push(svc.name);
                }
            });

            if (dropped.length) {
                ApxAlertModal.show({
                    variant: 'info',
                    title:   dropped.length === 1 ? 'One service removed' : 'Some services removed',
                    message: dropped.join(', ') + (dropped.length === 1 ? ' is' : ' are')
                             + ' not offered for a ' + (vtype === 'car' ? 'car' : 'motorcycle')
                             + ', so it has been taken off your booking.',
                });
            }
            buildGrid();
            apxUpdateEst();
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

        // ── Field validation (form-validate.js + plate-input.js) ────────────
        if (window.PlateInput) PlateInput.attach(document.getElementById('apx-v-plate'));
        if (window.FormValidate) {
            FormValidate.register(document.getElementById('apx-f-name'), { rules: [FormValidate.rules.required('Please enter your full name.')] });
            FormValidate.register(document.getElementById('apx-f-email'), { rules: [FormValidate.rules.required('Please enter your email.'), FormValidate.rules.email()] });
            FormValidate.register(document.getElementById('apx-f-phone'), { rules: [FormValidate.rules.required('Please enter your phone number.'), FormValidate.rules.phonePH()] });
            FormValidate.register(document.getElementById('apx-v-plate'), {
                rules: [FormValidate.rules.required('Plate number is required.'), FormValidate.rules.plate()]
            });
        }

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
            apxSyncBack();
            var btn = document.getElementById('apx-btn-next');
            btn.textContent = n===3 ? 'Confirm booking ✓' : 'Next →';
            btn.className = n===3 ? 'btn-apx-next confirm' : 'btn-apx-next';
            // On the path menu there is nothing to advance to yet; the cards
            // are the action. Everywhere else Next behaves as it always did.
            btn.style.visibility = (n === 1 && pathView === 'root') ? 'hidden' : 'visible';
            // Coming back to step 1 with a free-only selection must re-block;
            // leaving step 1 must release the button for its own checks.
            apxRefreshFreeOnly();
        }

        window.apxNext = function() {
            if (step===1) {
                if (selected.size === 0 && selectedBundle === null) {
                    ApxAlertModal.show({ variant: 'error', title: 'Nothing selected', message: 'Please choose a bundle or at least one service.' });
                    return;
                }
                // The disabled button is the visible half of this; this is the
                // half that still holds if the button is re-enabled from the
                // console or the state changes between render and click. The
                // note beside the service list is already explaining it, so
                // there is nothing to pop up here.
                var prices = Array.from(selected).map(function (i) { return SERVICES[i].price; });
                if (FreeOnlyGuard.blocks(FreeOnlyGuard.evaluate(prices, currentBundle() !== null))) {
                    apxRefreshFreeOnly();
                    return;
                }
                apxSetStep(2);
            } else if (step===2) {
                var name  = document.getElementById('apx-f-name').value.trim();
                var email = document.getElementById('apx-f-email').value.trim();
                var date  = document.getElementById('apx-f-date').value;
                var time  = document.getElementById('apx-f-time').value;
                var make  = document.getElementById('apx-v-make').value;
                var model = document.getElementById('apx-v-model').value;
                var year  = document.getElementById('apx-v-year').value;
                var fieldsValid = FormValidate.validateForm([
                    document.getElementById('apx-f-name'),
                    document.getElementById('apx-f-email'),
                    document.getElementById('apx-f-phone'),
                    document.getElementById('apx-v-plate')
                ]);
                if (!fieldsValid) return;
                if (!date||!time) { ApxAlertModal.show({ variant: 'error', title: 'Missing date & time', message: 'Please pick a preferred date and time.' }); return; }
                if (!make||!model||!year) { ApxAlertModal.show({ variant: 'error', title: 'Incomplete vehicle info', message: 'Please complete your vehicle information.' }); return; }
                var names = Array.from(selected).map(function(i){ return SERVICES[i].name; }).join(', ');
                var total = 0;
                selected.forEach(function(i){ total += SERVICES[i].price; });
                var plate = document.getElementById('apx-v-plate').value;
                var vStr  = vtype.charAt(0).toUpperCase()+vtype.slice(1)+' · '+make+' '+model+' '+year+(plate?' · '+plate:'');
                document.getElementById('apx-c-name').textContent     = name;
                document.getElementById('apx-c-email').textContent    = email;
                document.getElementById('apx-c-phone').textContent    = document.getElementById('apx-f-phone').value||'—';
                var timeLabel = (function () {
                    var m = time.match(/^(\d{2}):(\d{2})/);
                    if (!m) return time;
                    var h = parseInt(m[1], 10);
                    var ampm = h >= 12 ? 'PM' : 'AM';
                    var h12 = h % 12 === 0 ? 12 : h % 12;
                    return h12 + ':' + m[2] + ' ' + ampm;
                })();
                document.getElementById('apx-c-date').textContent     = date + ' at ' + timeLabel;
                document.getElementById('apx-c-vehicle').textContent  = vStr;
                document.getElementById('apx-c-services').textContent = names;
                document.getElementById('apx-c-notes').textContent    = document.getElementById('apx-f-notes').value||'—';
                document.getElementById('apx-c-total').textContent    = fmt(total) + ' *';
                apxSetStep(3);
            } else if (step===3) {
                apxSubmitBooking(false);
            }
        };

        // confirm: actual POST to backend
        function apxSubmitBooking(createAccount) {
            var btn = document.getElementById('apx-btn-next');
            btn.disabled = true;
            btn.textContent = 'Submitting…';

            var serviceIds = Array.from(selected).map(function(i){ return SERVICES[i].id; });
            var payload = {
                guest_name:    document.getElementById('apx-f-name').value.trim(),
                guest_email:   document.getElementById('apx-f-email').value.trim(),
                guest_phone:   document.getElementById('apx-f-phone').value.trim(),
                booking_date:  document.getElementById('apx-f-date').value,
                booking_time:  document.getElementById('apx-f-time').value,
                vehicle_type:  vtype,
                vehicle_make:  document.getElementById('apx-v-make').value,
                vehicle_model: document.getElementById('apx-v-model').value,
                vehicle_year:  document.getElementById('apx-v-year').value,
                vehicle_plate: document.getElementById('apx-v-plate').value,
                service_ids:   serviceIds,
                bundle_id:     selectedBundle,
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
                if (!r.ok) return r.json().then(function(e) {
                    var err = new Error(e.message || 'Server error ('+r.status+')');
                    // 422 is the server understanding the booking and declining
                    // it, with something the customer can act on: a policy rule,
                    // a slot that filled up, a field to correct. Marked here so
                    // the catch can tell that apart from the request failing,
                    // which is the only way the two reach the same handler.
                    err.refused = r.status === 422;
                    throw err;
                });
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
                    ApxAlertModal.show({ variant: 'error', title: 'Booking failed', message: data.message || 'Booking failed. Please try again.' });
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.textContent = 'Confirm booking ✓';

                // A refusal is not a fault. Every 422 used to arrive here under
                // "Something went wrong", which told the customer the system
                // had broken while the line underneath told them what to do —
                // so the title contradicted the only useful part of the dialog.
                // Informational, per the app's blue-is-not-red convention, and
                // not logged to the console: nothing went wrong.
                if (err && err.refused) {
                    ApxAlertModal.show({
                        variant: 'info',
                        title: 'Check your booking',
                        message: err.message
                    });
                    return;
                }

                console.error('Booking error:', err);
                ApxAlertModal.show({ variant: 'error', title: 'Something went wrong', message: err.message || 'Could not submit booking. Please try again.' });
            });
        }

        /* Back walks out of a step-1 sub-screen before it walks out of a
           step, so the three paths are reachable again without closing the
           modal. Nothing typed into step 2 is cleared on the way. */
        window.apxBack = function () {
            if (step > 1) { apxSetStep(step - 1); return; }
            if (pathView !== 'root') { apxChoosePath('root'); }
        };

        /* reset modal state when it closes */
        document.getElementById('bookingModal').addEventListener('hidden.bs.modal', function() {
            selected.clear();
            selectedBundle = null;
            symptoms.clear();
            catFilter = '';
            var searchBox = document.getElementById('apx-modal-search');
            if (searchBox) searchBox.value = '';
            step = 1;
            // Back to the three paths, not into whichever sub-screen was
            // open when it closed.
            apxChoosePath('root');
            apxSetStep(1);
            document.getElementById('apx-view-success').style.display = 'none';
            document.getElementById('apx-modal-footer').style.display = '';
            document.querySelectorAll('.apx-modal-svc-card').forEach(function(c){ c.classList.remove('selected'); });
            apxUpdateEst();
            document.getElementById('apx-f-name').value='';
            document.getElementById('apx-f-email').value='';
            document.getElementById('apx-f-phone').value='';
            guestPicker.reset();
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

        // Bound here rather than as an inline attribute: buildGrid lives
        // inside this closure and an onchange attribute cannot see it.
        document.getElementById('apx-modal-search').addEventListener('input', buildGrid);

        apxBuildCategoryChips();
        apxChoosePath('root');
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