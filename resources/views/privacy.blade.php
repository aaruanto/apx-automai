<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Privacy Policy — APX Automai</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

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

    <!-- Customized Bootstrap Stylesheet -->
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="/assets/css/design-tokens.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">

    <style>
        /* Marks a passage the team still has to write. Deliberately loud so it
           cannot be mistaken for finished copy or shipped by accident. */
        .todo {
            background: #fff8e1; border: 1px dashed #d8a500; border-radius: 6px;
            padding: 10px 14px; margin: 0 0 18px; font-size: .88rem; color: #6b4e00;
        }
        .todo strong { color: #8a6500; }
    </style>
</head>

<body>

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
                    <small>Mon - Fri : 09.00 AM - 06.00 PM</small>
                </div>
            </div>
            <div class="col-lg-5 px-5 text-end">
                <div class="h-100 d-inline-flex align-items-center py-3">
                    <small class="fa fa-phone-alt text-primary me-2"></small>
                    <small>+63 9544 8888 50</small>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <a href="/" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <h2 class="m-0 text-primary"><img src="/assets/img/apx-black-logo.png" alt="apx logo" width="100" height="120">AUTOMAI</h2>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="/" class="nav-item nav-link">Home</a>
                <a href="/about" class="nav-item nav-link">About</a>
                <a href="/service" class="nav-item nav-link">Services</a>
                <a href="/contact" class="nav-item nav-link">Contact</a>
                <a href="{{ route('terms') }}" class="nav-item nav-link">Terms &amp; Conditions</a>
                <a href="{{ route('privacy') }}" class="nav-item nav-link active">Privacy Policy</a>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->

    <!-- Privacy Content Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <h1 class="mb-4">Privacy Policy</h1>

                    <p class="mb-4" style="color:#6b7280;">Last updated: {{ now()->format('F j, Y') }}</p>

                    <div class="todo">
                        <strong>For the team:</strong> the sections below describe what the system
                        actually does today, so they are accurate rather than invented. Every
                        passage still needing a decision only your group can make is marked
                        <strong>[TO CONFIRM]</strong>. Work through those, then delete this box and
                        the <code>.todo</code> style block in the page head.
                    </div>

                    <p class="mb-5">This policy explains what personal information APX Motors and Services
                        collects through the APX AutoMai booking system, why we collect it, and what you
                        can ask us to do with it. It is written to align with the Data Privacy Act of 2012
                        (Republic Act No. 10173).</p>

                    <h4 class="mb-3">1. What We Collect</h4>
                    <p class="mb-2">We only collect what the booking system needs in order to work:</p>
                    <ul class="mb-4">
                        <li><strong>Your details</strong> — name, email address and mobile number.</li>
                        <li><strong>Your vehicle</strong> — make, model, year, colour, plate number and
                            whether it is a car or motorcycle.</li>
                        <li><strong>Your bookings</strong> — the services chosen, the date and time, the
                            mechanic assigned, any notes you add, and the status of the work.</li>
                        <li><strong>Optional profile details</strong> — date of birth and address, only if
                            you choose to fill them in.</li>
                        <li><strong>Your password</strong>, stored only as a one-way hash (see section 4).</li>
                    </ul>
                    <p class="mb-4">We do <strong>not</strong> collect or store payment card details.
                        Payment is taken at the shop, and no card information passes through this website.</p>

                    <h4 class="mb-3">2. Why We Collect It</h4>
                    <ul class="mb-4">
                        <li>To create and manage your bookings, and to prepare the right service and parts
                            for your vehicle.</li>
                        <li>To contact you about a booking — confirming it, rescheduling it, or telling you
                            it is ready.</li>
                        <li>To keep a service history for your vehicle, so past work can inform future work.</li>
                        <li>To run the shop day to day: seeing the schedule, assigning mechanics, and
                            producing internal reports on booking volumes.</li>
                    </ul>
                    <p class="mb-4">We do not sell your information, and we do not use it for advertising.</p>

                    <h4 class="mb-3">3. Who Can See It</h4>
                    <ul class="mb-4">
                        <li><strong>You</strong>, through your own account. You only ever see your own
                            bookings and vehicles.</li>
                        <li><strong>APX staff and administrators</strong>, through the admin area, as needed
                            to carry out and manage your service.</li>
                        <li><strong>Our hosting provider.</strong> The site and its database run on Render,
                            a third-party cloud platform, which necessarily stores the data on our behalf.</li>
                    </ul>
                    <div class="todo">
                        <strong>[TO CONFIRM]</strong> List any other party that receives customer data —
                        for example an SMS gateway, an email provider, or an accounting system — or state
                        plainly that there are none.
                    </div>

                    <h4 class="mb-3">4. How It Is Stored and Protected</h4>
                    <ul class="mb-4">
                        <li>The site is served over HTTPS, so information sent between your device and us
                            is encrypted in transit.</li>
                        <li>Passwords are stored as one-way bcrypt hashes. They cannot be read or recovered
                            by anyone, including our own staff, and are never shown or emailed to you.</li>
                        <li>Access to the admin area requires a staff or administrator account, and each
                            account only sees what its role allows.</li>
                        <li>Customer records are visible to administrators in read-only form; personal
                            details are not edited by staff on your behalf.</li>
                    </ul>
                    <div class="todo">
                        <strong>[TO CONFIRM]</strong> Describe any additional safeguards the shop applies
                        off the system — who holds admin accounts, how those are issued and revoked, and
                        what happens to printed records if any are kept.
                    </div>

                    <h4 class="mb-3">5. How Long We Keep It</h4>
                    <ul class="mb-4">
                        <li>Your account and its data are kept for as long as the account is open.</li>
                        <li>If you delete your account, it is deactivated immediately and hidden, and there
                            is a <strong>30-day window</strong> in which it can be restored. After that your
                            personal details are permanently anonymised and cannot be recovered.</li>
                        <li>Booking records may be kept after anonymisation, with the personal details
                            removed, as part of the shop's own service history.</li>
                    </ul>
                    <div class="todo">
                        <strong>[TO CONFIRM]</strong> State how long anonymised booking and service records
                        are kept — for example a fixed number of years — and whether anything is required by
                        your accounting or warranty obligations.
                    </div>

                    <h4 class="mb-3">6. Cookies</h4>
                    <p class="mb-4">We use a session cookie to keep you signed in and to protect forms
                        against cross-site request forgery. It is required for the site to work and is not
                        used for tracking or advertising.</p>
                    <div class="todo">
                        <strong>[TO CONFIRM]</strong> If you later add analytics, embedded maps or any
                        third-party script that sets its own cookies, list them here.
                    </div>

                    <h4 class="mb-3">7. Your Rights Under the Data Privacy Act</h4>
                    <p class="mb-2">Under Republic Act No. 10173, you have the right to:</p>
                    <ul class="mb-4">
                        <li><strong>Be informed</strong> that your personal information is being collected
                            and processed — which is the purpose of this page.</li>
                        <li><strong>Access</strong> the personal information we hold about you.</li>
                        <li><strong>Object</strong> to processing, or withdraw consent, where processing
                            relies on your consent.</li>
                        <li><strong>Correct</strong> information that is inaccurate or out of date. You can
                            update your own name, contact number and vehicles from your dashboard.</li>
                        <li><strong>Erasure or blocking</strong> — you can delete your account yourself from
                            your dashboard, as described in section 5.</li>
                        <li><strong>Damages</strong>, where you have suffered because your rights were
                            violated.</li>
                        <li><strong>Data portability</strong> — to obtain a copy of your data in a
                            commonly used electronic format.</li>
                        <li><strong>Complain</strong> to the National Privacy Commission if you believe your
                            rights have not been respected.</li>
                    </ul>
                    <div class="todo">
                        <strong>[TO CONFIRM]</strong> Say how someone exercises these rights in practice —
                        which address or number to use, who handles the request, and how long you will take
                        to respond. A named Data Protection Officer is expected of an organisation that
                        processes personal data.
                    </div>

                    <h4 class="mb-3">8. Children</h4>
                    <div class="todo">
                        <strong>[TO CONFIRM]</strong> State whether accounts may be created by minors, and
                        if so whether a parent or guardian must consent. The system does not currently ask
                        for age at registration.
                    </div>

                    <h4 class="mb-3">9. Changes to This Policy</h4>
                    <p class="mb-4">We may update this policy as the service changes. The revision date at
                        the top of this page always shows when it was last updated, and significant changes
                        will be announced on the website.</p>

                    <h4 class="mb-3">10. Contact Us</h4>
                    <p class="mb-0">For any question about this policy, or to exercise any of the rights
                        above, contact APX Motors and Services at 5 Glenn St., Quezon City, by phone on
                        <a href="tel:+639544888850">+63 9544 8888 50</a>, or by email at
                        <a href="mailto:apxautomai@gmail.com">apxautomai@gmail.com</a>.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Privacy Content End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-light mb-4">APX Automai</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>5 Glenn St., Quezon City</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+63 9544 8888 50</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>apxautomai@gmail.com</p>
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
                        &copy; {{ date('Y') }} <a class="border-bottom" href="/">APX Automai</a>. All Rights Reserved.
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="footer-menu">
                            <a href="/">Home</a>
                            <a href="/service">Services</a>
                            <a href="/about">About</a>
                            <a href="/contact">Contact</a>
                            <a href="{{ route('terms') }}">Terms &amp; Conditions</a>
                            <a href="{{ route('privacy') }}">Privacy Policy</a>
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
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="/assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/lib/wow/wow.min.js"></script>
    <script src="/assets/lib/easing/easing.min.js"></script>

    <!-- Template Javascript -->
    <script src="/assets/js/main.js"></script>
</body>

</html>
