<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>CarServ - Car Repair HTML Template</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="/assets/img/favicon.ico" rel="icon">

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


    <!-- Topbar Start -->
    <div class="container-fluid bg-light p-0">
        <div class="row gx-0 d-none d-lg-flex">
            <div class="col-lg-7 px-5 text-start">
                <div class="h-100 d-inline-flex align-items-center py-3 me-4">
                    <small class="fa fa-map-marker-alt text-primary me-2"></small>
                    <small>5 Glenn, Quezon City</small>
                </div>
                <div class="h-100 d-inline-flex align-items-center py-3">
                    <small class="far fa-clock text-primary me-2"></small>
                    <small>Mon - Fri : 09.00 AM - 09.00 PM</small>
                </div>
            </div>
            <div class="col-lg-5 px-5 text-end">
                <div class="h-100 d-inline-flex align-items-center py-3 me-4">
                    <small class="fa fa-phone-alt text-primary me-2"></small>
                    <small>+012 345 6789</small>
                </div>
                <div class="h-100 d-inline-flex align-items-center">
                    <a class="btn btn-sm-square bg-white text-primary me-1" href=""><i class="fab fa-facebook-f"></i></a>
                    <a class="btn btn-sm-square bg-white text-primary me-1" href=""><i class="fab fa-twitter"></i></a>
                    <a class="btn btn-sm-square bg-white text-primary me-1" href=""><i class="fab fa-linkedin-in"></i></a>
                    <a class="btn btn-sm-square bg-white text-primary me-0" href=""><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->


    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <a href="/" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <h2 class="m-0 text-primary"><img src="/assets/img\apx-black-logo.png" alt="apx logo" width="100" height="120">AUTOMAI</h2>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="/" class="nav-item nav-link">Home</a>
                <a href="/about" class="nav-item nav-link">About</a>
                <a href="/service" class="nav-item nav-link active">Services</a>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                    <div class="dropdown-menu fade-up m-0">
                        <a href="/booking" class="dropdown-item">Booking</a>
                        <a href="/team" class="dropdown-item">Technicians</a>
                        <a href="/testimonial" class="dropdown-item">Testimonial</a>
                        <a href="/automai/public/404.php" class="dropdown-item">404 Page</a>
                    </div>
                </div>
                <a href="/contact" class="nav-item nav-link">Contact</a>
            </div>
            <a href="/automai/auth/login.php" class="btn btn-primary py-4 px-lg-5 d-none d-lg-block">Login</a>
        </div>
    </nav>
    <!-- Navbar End -->



        <!-- APX Motors Services Start -->
    <style>
        .apx-services-section {
            background: #fff;
            padding: 60px 0;
        }
        .apx-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 36px;
            justify-content: center;
        }
        .apx-filter-btn {
            background: #fff;
            color: #444;
            border: 2px solid #ddd;
            border-radius: 999px;
            padding: 7px 20px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .apx-filter-btn:hover,
        .apx-filter-btn.active {
            background: #e63946;
            color: #fff;
            border-color: #e63946;
        }
        .apx-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .apx-svc-card {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 14px;
            padding: 24px 22px 20px;
            cursor: pointer;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .apx-svc-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(230,57,70,0.18);
            border-color: #e63946;
            text-decoration: none;
            color: inherit;
        }
        .apx-svc-cat {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 18px;
        }
        .apx-svc-icon-wrap {
            font-size: 2.4rem;
            color: #e63946;
            margin-bottom: 18px;
        }
        .apx-svc-name {
            font-size: 1rem;
            font-weight: 700;
            color: #e63946;
            margin-bottom: 10px;
        }
        .apx-svc-desc {
            font-size: 0.82rem;
            color: #666;
            line-height: 1.6;
            flex-grow: 1;
        }
        .apx-svc-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 18px;
            font-size: 0.78rem;
            color: #999;
            border-top: 1px solid #f0f0f0;
            padding-top: 14px;
        }
        .apx-svc-card.hidden { display: none; }
    </style>

    <div class="apx-services-section">
        <div class="container">
            <div class="text-center wow fadeInUp mb-4" data-wow-delay="0.1s">
                <h1 class="mb-2">APX Motors Services</h1>
                <p class="text-muted">Click any service to get started — registration required</p>
            </div>

            <!-- Filter Bar -->
            <div class="apx-filter-bar wow fadeInUp" data-wow-delay="0.2s">
                <button class="apx-filter-btn active" data-filter="all">All Services</button>
                <button class="apx-filter-btn" data-filter="engine">Engine &amp; Oil</button>
                <button class="apx-filter-btn" data-filter="cvt">CVT &amp; Transmission</button>
                <button class="apx-filter-btn" data-filter="inspection">Inspection</button>
                <button class="apx-filter-btn" data-filter="cleaning">Cleaning</button>
            </div>

            <!-- Cards -->
            <div class="apx-cards-grid wow fadeInUp" data-wow-delay="0.3s">

                <a href="/register" class="apx-svc-card" data-cat="engine">
                    <div class="apx-svc-cat">Engine &amp; Oil</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-oil-can"></i></div>
                    <div class="apx-svc-name">Change Oil &amp; Filter</div>
                    <div class="apx-svc-desc">Complete engine oil drain and refill with high-quality oil and a fresh filter for optimal engine performance.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 30–45 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="engine">
                    <div class="apx-svc-cat">Engine &amp; Oil</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-gas-pump"></i></div>
                    <div class="apx-svc-name">Fuel Injection Cleaning</div>
                    <div class="apx-svc-desc">Deep cleaning of fuel injectors to restore proper fuel atomization, improving throttle response and fuel economy.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 45–60 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="engine cleaning">
                    <div class="apx-svc-cat">Engine &amp; Oil</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-wind"></i></div>
                    <div class="apx-svc-name">Throttle Body Cleaning</div>
                    <div class="apx-svc-desc">Remove carbon buildup and deposits from the throttle body for smoother idling and improved acceleration.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 30–45 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="engine">
                    <div class="apx-svc-cat">Engine &amp; Oil</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-sliders-h"></i></div>
                    <div class="apx-svc-name">Throttle Idle Adjustment</div>
                    <div class="apx-svc-desc">Fine-tune idle speed to manufacturer specs, eliminating rough idle and stalling at traffic stops.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="engine">
                    <div class="apx-svc-cat">Engine &amp; Oil</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-cogs"></i></div>
                    <div class="apx-svc-name">Valve Clearance Adjustment / Tune-up</div>
                    <div class="apx-svc-desc">Inspect and adjust valve clearances to ensure proper engine breathing, reducing noise and wear.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 60–90 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="cvt inspection cleaning">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-sync-alt"></i></div>
                    <div class="apx-svc-name">CVT Cleaning and Inspection</div>
                    <div class="apx-svc-desc">Full CVT belt and pulley inspection with cleaning to maintain smooth, efficient power transfer.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 60–90 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="inspection">
                    <div class="apx-svc-cat">Inspection</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-search"></i></div>
                    <div class="apx-svc-name">Airfilter Inspection</div>
                    <div class="apx-svc-desc">Check air filter condition and airflow restriction to ensure the engine receives clean, unrestricted air.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 10–15 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="inspection">
                    <div class="apx-svc-cat">Inspection</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-tools"></i></div>
                    <div class="apx-svc-name">Airfilter Installation</div>
                    <div class="apx-svc-desc">Replacement and installation of a new air filter to restore optimal engine airflow and protect internal components.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 10–20 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="cvt inspection">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-circle-notch"></i></div>
                    <div class="apx-svc-name">Flyball Inspection</div>
                    <div class="apx-svc-desc">Inspect flyball weights for wear and deformation that can affect CVT engagement and acceleration response.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="cvt cleaning">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-broom"></i></div>
                    <div class="apx-svc-name">Flyball Cleaning</div>
                    <div class="apx-svc-desc">Remove dirt and grease buildup from flyball components to restore precise CVT engagement and smooth power delivery.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="cvt inspection">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-ruler-combined"></i></div>
                    <div class="apx-svc-name">V-belt Inspection</div>
                    <div class="apx-svc-desc">Measure V-belt width and check for cracks or fraying to prevent slippage and unexpected CVT failure.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 15–25 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="cvt cleaning">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-magic"></i></div>
                    <div class="apx-svc-name">V-belt Cleaning</div>
                    <div class="apx-svc-desc">Deep clean the V-belt and surrounding components to remove residue that causes slipping and reduces belt lifespan.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="cvt inspection">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-cog"></i></div>
                    <div class="apx-svc-name">Pulley Set Inspection</div>
                    <div class="apx-svc-desc">Check primary and secondary pulleys for wear and proper movement to ensure efficient power transmission.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="cvt cleaning">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-spray-can"></i></div>
                    <div class="apx-svc-name">Pulley Set Cleaning</div>
                    <div class="apx-svc-desc">Thorough cleaning of pulley surfaces and grooves to remove metal dust and contaminants affecting CVT performance.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 25–35 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="cvt inspection">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-car-side"></i></div>
                    <div class="apx-svc-name">Torque Drive Assy Inspection</div>
                    <div class="apx-svc-desc">Inspect the torque drive assembly for wear, proper engagement, and signs of damage that affect drivetrain efficiency.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 25–35 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="cvt cleaning">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-shower"></i></div>
                    <div class="apx-svc-name">Torque Drive Assy Cleaning</div>
                    <div class="apx-svc-desc">Remove built-up grime and contaminants from the torque drive assembly to maintain reliable drivetrain operation.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 30–40 min</div>
                </a>

                <a href="/register" class="apx-svc-card" data-cat="cvt">
                    <div class="apx-svc-cat">CVT &amp; Transmission</div>
                    <div class="apx-svc-icon-wrap"><i class="fa fa-tint"></i></div>
                    <div class="apx-svc-name">Torque Drive Assy Greasing</div>
                    <div class="apx-svc-desc">Apply fresh grease to torque drive assembly components to reduce friction, heat, and premature wear.</div>
                    <div class="apx-svc-meta"><i class="fa fa-clock"></i> 20–30 min</div>
                </a>

            </div>
        </div>
    </div>

    <!-- Filter Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var btns  = document.querySelectorAll(".apx-filter-btn");
            var cards = document.querySelectorAll(".apx-svc-card");
            btns.forEach(function (btn) {
                btn.addEventListener("click", function () {
                    btns.forEach(function (b) { b.classList.remove("active"); });
                    btn.classList.add("active");
                    var filter = btn.getAttribute("data-filter");
                    cards.forEach(function (card) {
                        var cats = card.getAttribute("data-cat") || "";
                        if (filter === "all" || cats.indexOf(filter) !== -1) {
                            card.classList.remove("hidden");
                        } else {
                            card.classList.add("hidden");
                        }
                    });
                });
            });
        });
    </script>
    <!-- APX Motors Services End -->


    <!-- Booking Start -->
    <div class="container-fluid bg-secondary booking my-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container">
            <div class="row gx-5">
                <div class="col-lg-6 py-5">
                    <div class="py-5">
                        <h1 class="text-white mb-4">Certified and Award Winning Car Repair Service Provider</h1>
                        <p class="text-white mb-0">Eirmod sed tempor lorem ut dolores. Aliquyam sit sadipscing kasd ipsum. Dolor ea et dolore et at sea ea at dolor, justo ipsum duo rebum sea invidunt voluptua. Eos vero eos vero ea et dolore eirmod et. Dolores diam duo invidunt lorem. Elitr ut dolores magna sit. Sea dolore sanctus sed et. Takimata takimata sanctus sed.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="bg-primary h-100 d-flex flex-column justify-content-center text-center p-5 wow zoomIn" data-wow-delay="0.6s">
                        <h1 class="text-white mb-4">Book For A Service</h1>
                        <form>
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <input type="text" class="form-control border-0" placeholder="Your Name" style="height: 55px;">
                                </div>
                                <div class="col-12 col-sm-6">
                                    <input type="email" class="form-control border-0" placeholder="Your Email" style="height: 55px;">
                                </div>
                                <div class="col-12 col-sm-6">
                                    <select class="form-select border-0" style="height: 55px;">
                                        <option selected>Select A Service</option>
                                        <option value="1">Service 1</option>
                                        <option value="2">Service 2</option>
                                        <option value="3">Service 3</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="date" id="date1" data-target-input="nearest">
                                        <input type="text"
                                            class="form-control border-0 datetimepicker-input"
                                            placeholder="Service Date" data-target="#date1" data-toggle="datetimepicker" style="height: 55px;">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <textarea class="form-control border-0" placeholder="Special Request"></textarea>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-secondary w-100 py-3" type="submit">Book Now</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Booking End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-light mb-4">Address</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>5 Glenn, Quezon City</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+012 345 67890</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i><a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="ed84838b82ad88958c809d8188c38e8280">[email&#160;protected]</a></p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-light mb-4">Opening Hours</h4>
                    <h6 class="text-light">Monday - Friday:</h6>
                    <p class="mb-4">09.00 AM - 09.00 PM</p>
                    <h6 class="text-light">Saturday - Sunday:</h6>
                    <p class="mb-0">09.00 AM - 12.00 PM</p>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-light mb-4">Services</h4>
                    <a class="btn btn-link" href="/automai/auth/login.php">Change Oil &amp; Filter</a>
                    <a class="btn btn-link" href="/automai/auth/login.php">Fuel Injection Cleaning</a>
                    <a class="btn btn-link" href="/automai/auth/login.php">Throttle Body Cleaning</a>
                    <a class="btn btn-link" href="/automai/auth/login.php">CVT Cleaning &amp; Inspection</a>
                    <a class="btn btn-link" href="/automai/auth/login.php">Valve Clearance / Tune-up</a>
                </div>

            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="border-bottom" href="#">Your Site Name</a>, All Right Reserved.

                        <!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. If you'd like to use the template without the footer author’s credit link/attribution link/backlink, you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". Thank you for your support. ***/-->
                        Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="footer-menu">
                            <a href="">Home</a>
                            <a href="">Cookies</a>
                            <a href="">Help</a>
                            <a href="">FQAs</a>
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

</body>
</html>