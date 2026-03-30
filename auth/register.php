<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Create Account — APX AutoMai</title>
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
            --text:       #F0F0F0;
            --text-muted: #888888;
            --success:    #22C55E;
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
            max-width: 520px;
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

        .auth-body {
            padding: 28px 32px 32px;
        }

        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
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
        .input-wrap:focus-within .input-icon { color: var(--red); }

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
        .form-control.no-icon { padding-left: 14px; }
        .form-control::placeholder { color: var(--text-muted); opacity: 0.7; }
        .form-control:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px var(--red-glow);
        }

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

        /* password strength */
        .strength-bar {
            height: 3px;
            background: var(--surface-3);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            border-radius: 2px;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }

        .divider-section {
            border: none;
            border-top: 1px solid var(--border);
            margin: 20px 0;
        }

        .section-label {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 14px;
        }

        .terms-check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 0.82rem;
            color: var(--text-muted);
            cursor: pointer;
        }
        .terms-check input[type="checkbox"] {
            accent-color: var(--red);
            width: 14px; height: 14px;
            margin-top: 2px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .terms-check a { color: var(--red); text-decoration: none; }
        .terms-check a:hover { text-decoration: underline; }

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


    </style>
</head>
<body>

    <header class="topbar">
        <a href="login.html" class="brand">
            <span class="brand-apx">APX</span>
            <span class="brand-auto">AutoMai</span>
            <span class="brand-badge">PORTAL</span>
        </a>
    </header>

    <div class="auth-wrapper">
        <div class="auth-card">

            <div class="auth-header">
                <div class="auth-eyebrow">New Account</div>
                <div class="auth-title">Create Account</div>
                <div class="auth-subtitle">Join APX AutoMai to manage or book services</div>
            </div>

            <div class="auth-body">
                <form id="registerForm" action="#" method="POST">

                    <div class="section-label">Personal Information</div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label" for="inputFirstName">First Name</label>
                            <div class="input-wrap">
                                <i class="fas fa-user input-icon"></i>
                                <input class="form-control" id="inputFirstName" name="first_name" type="text" placeholder="Juan" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="inputLastName">Last Name</label>
                            <div class="input-wrap">
                                <i class="fas fa-user input-icon"></i>
                                <input class="form-control" id="inputLastName" name="last_name" type="text" placeholder="dela Cruz" required />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputEmail">Email Address</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon"></i>
                            <input class="form-control" id="inputEmail" name="email" type="email" placeholder="you@example.com" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputPhone">Phone Number</label>
                        <div class="input-wrap">
                            <i class="fas fa-phone input-icon"></i>
                            <input class="form-control" id="inputPhone" name="phone" type="tel" placeholder="+63 9XX XXX XXXX" />
                        </div>
                    </div>

                    <hr class="divider-section" />
                    <div class="section-label">Account Setup</div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label" for="inputPassword">Password</label>
                            <div class="input-wrap">
                                <i class="fas fa-lock input-icon"></i>
                                <input class="form-control" id="inputPassword" name="password" type="password" placeholder="••••••••" required oninput="checkStrength(this.value)" />
                                <button type="button" class="pw-toggle" onclick="togglePw('inputPassword', this)" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="inputPasswordConfirm">Confirm Password</label>
                            <div class="input-wrap">
                                <i class="fas fa-lock input-icon"></i>
                                <input class="form-control" id="inputPasswordConfirm" name="password_confirm" type="password" placeholder="••••••••" required />
                                <button type="button" class="pw-toggle" onclick="togglePw('inputPasswordConfirm', this)" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <label class="terms-check">
                        <input type="checkbox" required />
                        I agree to the <a href="#">Terms &amp; Conditions</a> and <a href="#">Privacy Policy</a>
                    </label>

                    <button type="submit" class="btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                </form>
            </div>

            <div class="auth-footer">
                Already have an account? <a href="login.html">Sign in</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-brand"><span>APX</span> AutoMai &mdash; Portal &copy; 2025</div>
        <div>
            <a href="#">Privacy Policy</a>
            &nbsp;&middot;&nbsp;
            <a href="#">Terms &amp; Conditions</a>
        </div>
    </footer>

    <script>
        function togglePw(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon  = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function checkStrength(val) {
            const fill = document.getElementById('strengthFill');
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            const widths = ['0%', '25%', '50%', '75%', '100%'];
            const colors = ['transparent', '#E8192C', '#F59E0B', '#3B82F6', '#22C55E'];
            fill.style.width  = widths[score];
            fill.style.background = colors[score];
        }
    </script>
</body>
</html>