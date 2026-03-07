<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Password Recovery — APX AutoMai</title>
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
            --success-glow: rgba(34,197,94,0.15);
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

        .auth-body {
            padding: 28px 32px 32px;
        }

        /* Icon callout */
        .icon-callout {
            background: var(--surface-3);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 24px;
        }
        .icon-callout .callout-icon {
            width: 36px; height: 36px;
            background: var(--red-glow);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: var(--red);
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .icon-callout p {
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
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
            padding: 11px 14px 11px 38px;
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
            margin-bottom: 14px;
        }
        .btn-primary:hover {
            background: var(--red-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(232,25,44,0.35);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-ghost {
            width: 100%;
            background: none;
            color: var(--text-muted);
            border: 1px solid var(--border);
            padding: 11px;
            border-radius: 7px;
            font-family: 'Barlow', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s, border-color 0.2s, background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-ghost:hover {
            color: var(--text);
            border-color: var(--border-hover, rgba(255,255,255,0.14));
            background: var(--surface-3);
        }

        /* Success state */
        .success-state {
            display: none;
            text-align: center;
            padding: 8px 0 4px;
        }
        .success-icon {
            width: 60px; height: 60px;
            background: var(--success-glow);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--success);
            font-size: 1.4rem;
            margin: 0 auto 16px;
            border: 1px solid rgba(34,197,94,0.25);
        }
        .success-state h3 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .success-state p {
            font-size: 0.83rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 24px;
        }

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
                <div class="auth-eyebrow">Account Recovery</div>
                <div class="auth-title">Reset Password</div>
                <div class="auth-subtitle">We'll send a reset link to your email</div>
            </div>

            <div class="auth-body">

                <!-- FORM STATE -->
                <div id="formState">
                    <div class="icon-callout">
                        <div class="callout-icon"><i class="fas fa-circle-info"></i></div>
                        <p>Enter the email address associated with your account and we'll send you a link to reset your password.</p>
                    </div>

                    <form id="resetForm" onsubmit="handleSubmit(event)">
                        <div class="form-group">
                            <label class="form-label" for="inputEmail">Email Address</label>
                            <div class="input-wrap">
                                <i class="fas fa-envelope input-icon"></i>
                                <input class="form-control" id="inputEmail" name="email" type="email" placeholder="you@example.com" required />
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Send Reset Link
                        </button>
                        <a href="login.html" class="btn-ghost">
                            <i class="fas fa-arrow-left"></i>
                            Back to Sign In
                        </a>
                    </form>
                </div>

                <!-- SUCCESS STATE -->
                <div class="success-state" id="successState">
                    <div class="success-icon"><i class="fas fa-check"></i></div>
                    <h3>Check Your Email</h3>
                    <p>We sent a password reset link to <strong id="sentEmail"></strong>. It may take a few minutes to arrive.</p>
                    <a href="login.html" class="btn-ghost">
                        <i class="fas fa-arrow-left"></i>
                        Back to Sign In
                    </a>
                </div>
            </div>

            <div class="auth-footer">
                Remember your password? <a href="login.html">Sign in</a>
                &nbsp;&middot;&nbsp;
                <a href="register.html">Create account</a>
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
        function handleSubmit(e) {
            e.preventDefault();
            const email = document.getElementById('inputEmail').value;
            document.getElementById('sentEmail').textContent = email;
            document.getElementById('formState').style.display = 'none';
            document.getElementById('successState').style.display = 'block';
        }
    </script>
</body>
</html>