<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Login — APX AUTOMAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --red:        #E8192C;
            --red-dark:   #B5101E;
            --red-glow:   rgba(232, 25, 44, 0.15);
            --black:      #0A0A0A;
            --surface:    #111111;
            --surface-2:  #1A1A1A;
            --surface-3:  #222222;
            --border:     rgba(255,255,255,0.07);
            --border-hover: rgba(255,255,255,0.14);
            --text:       #F0F0F0;
            --text-muted: #888888;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Barlow', sans-serif;
            background: var(--black);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* ── BACKGROUND TEXTURE ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 80% 20%, rgba(232,25,44,0.08) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 10% 80%, rgba(232,25,44,0.05) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        /* ── TOPBAR ── */
        .topbar {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            padding: 20px 32px;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }

        .brand {
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 800;
            font-size: 1.4rem;
            letter-spacing: 0.04em;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-apx  { color: var(--red); }
        .brand-auto { color: var(--text); }
        .brand-badge {
            background: var(--red);
            color: #fff;
            font-size: 0.5rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            padding: 2px 6px;
            border-radius: 2px;
        }

        /* ── MAIN CONTENT ── */
        .auth-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 16px;
            position: relative;
            z-index: 1;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.03);
            animation: fadeUp 0.45s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .auth-header {
            padding: 32px 32px 24px;
            border-bottom: 1px solid var(--border);
            position: relative;
        }
        .auth-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 32px; right: 32px;
            height: 1px;
            background: linear-gradient(90deg, var(--red), transparent);
            opacity: 0.5;
        }

        .auth-eyebrow {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: 6px;
        }
        .auth-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: 0.02em;
        }
        .auth-subtitle {
            font-size: 0.83rem;
            color: var(--text-muted);
            margin-top: 4px;
            font-weight: 300;
        }



        /* ── FORM ── */
        .auth-body {
            padding: 28px 32px 32px;
        }

        .form-group {
            margin-bottom: 16px;
        }
        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.8rem;
            pointer-events: none;
            transition: color 0.2s;
        }
        .form-control {
            width: 100%;
            background: var(--surface-3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 11px 40px 11px 38px;
            border-radius: 7px;
            font-size: 0.88rem;
            font-family: 'Barlow', sans-serif;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control::placeholder { color: var(--text-muted); opacity: 0.7; }
        .form-control:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px var(--red-glow);
        }
        .form-control:focus + .input-icon,
        .input-wrap:focus-within .input-icon { color: var(--red); }

        /* password toggle */
        .input-wrap .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.82rem;
            padding: 2px;
            transition: color 0.2s;
        }
        .input-wrap .pw-toggle:hover { color: var(--text); }

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.82rem;
            color: var(--text-muted);
        }
        .form-check input[type="checkbox"] {
            accent-color: var(--red);
            width: 14px; height: 14px;
            cursor: pointer;
        }
        .form-check:hover { color: var(--text); }

        .link-sm {
            font-size: 0.82rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }
        .link-sm:hover { color: var(--red); }

        .btn-primary {
            width: 100%;
            background: var(--red);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 7px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary:hover {
            background: var(--red-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(232,25,44,0.35);
        }
        .btn-primary:active { transform: translateY(0); }

        /* ── FOOTER OF CARD ── */
        .auth-footer {
            padding: 16px 32px;
            border-top: 1px solid var(--border);
            background: var(--surface-2);
            text-align: center;
            font-size: 0.82rem;
            color: var(--text-muted);
        }
        .auth-footer a {
            color: var(--red);
            text-decoration: none;
            font-weight: 500;
        }
        .auth-footer a:hover { text-decoration: underline; }

        /* ── PAGE FOOTER ── */
        footer {
            position: relative;
            z-index: 1;
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

        /* ── DIVIDER ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
    </style>
</head>
<body>

    <!-- TOPBAR -->
    <header class="topbar">
        <a href="#" class="brand">
            <span class="brand-apx">APX</span>
            <span class="brand-auto">AutoMai</span>
            <span class="brand-badge">PORTAL</span>
        </a>
    </header>

    <!-- AUTH WRAPPER -->
    <div class="auth-wrapper">
        <div class="auth-card">

            <!-- HEADER -->
            <div class="auth-header">
                <div class="auth-eyebrow">Portal Access</div>
                <div class="auth-title">Sign In</div>
                <div class="auth-subtitle">Enter your credentials to access the dashboard</div>
            </div>

            <!-- BODY -->
            <div class="auth-body">

                <!-- Error alert -->
                <div id="loginError" style="display:none;background:rgba(232,25,44,0.1);border:1px solid rgba(232,25,44,0.3);border-radius:7px;padding:10px 14px;margin-bottom:16px;font-size:0.83rem;color:#E8192C;align-items:center;gap:8px;">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>Invalid email or password. Please try again.</span>
                </div>

                <form id="loginForm" onsubmit="handleLogin(event)">

                    <div class="form-group">
                        <label class="form-label" for="inputEmail">Email Address</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon"></i>
                            <input class="form-control" id="inputEmail" name="email" type="email" placeholder="you@apxautomai.com" autocomplete="email" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputPassword">Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input class="form-control" id="inputPassword" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required />
                            <button type="button" class="pw-toggle" onclick="togglePw(this)" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="form-check">
                            <input type="checkbox" name="remember" />
                            Remember me
                        </label>
                        <a class="link-sm" href="password.html">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="fas fa-right-to-bracket"></i>
                        Sign In
                    </button>
                </form>

                <!-- Test credentials hint -->
                <div style="margin-top:20px;background:var(--surface-3);border:1px solid var(--border);border-radius:7px;padding:12px 14px;font-size:0.78rem;color:var(--text-muted);">
                    <div style="font-weight:700;color:var(--text-muted);letter-spacing:0.05em;margin-bottom:8px;font-family:'Barlow Condensed',sans-serif;text-transform:uppercase;font-size:0.7rem;">
                        <i class="fas fa-circle-info" style="color:var(--red);margin-right:6px;"></i>Test Credentials
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <div style="display:flex;justify-content:space-between;gap:12px;">
                            <span style="color:var(--text-muted);">Admin:</span>
                            <span style="color:var(--text);font-family:monospace;">admin@apxautomai.com / admin123</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:12px;">
                            <span style="color:var(--text-muted);">Customer:</span>
                            <span style="color:var(--text);font-family:monospace;">juan@email.com / customer123</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- CARD FOOTER -->
            <div class="auth-footer">
                Don't have an account? <a href="register.html">Create one</a>
            </div>
        </div>
    </div>

    <!-- PAGE FOOTER -->
    <footer>
        <div class="footer-brand"><span>APX</span> AutoMai &mdash; Admin Portal &copy; 2025</div>
        <div>
            <a href="#">Privacy Policy</a>
            &nbsp;&middot;&nbsp;
            <a href="#">Terms &amp; Conditions</a>
        </div>
    </footer>

    <script>
        const BASE_URL = "/automai";
        // ── HARDCODED ACCOUNTS ──────────────────────────────────────
        const ACCOUNTS = [
            {
                email:    'admin@apxautomai.com',
                password: 'admin123',
                role:     'admin',
                redirect: BASE_URL + '/dashboard/admin-dashboard.php'
            },
            {
                email:    'juan@email.com',
                password: 'customer123',
                role:     'customer',
                redirect: BASE_URL + '/dashboard/customer-dashboard.php'
            }
        ];
        // ────────────────────────────────────────────────────────────

        function handleLogin(e) {
            e.preventDefault();

            const email    = document.getElementById('inputEmail').value.trim().toLowerCase();
            const password = document.getElementById('inputPassword').value;
            const errorEl  = document.getElementById('loginError');
            const submitBtn = document.getElementById('submitBtn');

            // Hide any previous error
            errorEl.style.display = 'none';

            // Find matching account
            const account = ACCOUNTS.find(a => a.email === email && a.password === password);

            if (account) {
                // Success — show loading state then redirect
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
                submitBtn.disabled = true;
                setTimeout(() => {
                    window.location.href = account.redirect;
                }, 800);
            } else {
                // Fail — show error, shake the card
                errorEl.style.display = 'flex';
                document.querySelector('.auth-card').animate([
                    { transform: 'translateX(-6px)' },
                    { transform: 'translateX(6px)' },
                    { transform: 'translateX(-4px)' },
                    { transform: 'translateX(4px)' },
                    { transform: 'translateX(0)' }
                ], { duration: 300, easing: 'ease-out' });
            }
        }

        function togglePw(btn) {
            const input = btn.previousElementSibling;
            const icon  = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>