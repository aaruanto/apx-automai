<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Forgot Password — APX AUTOMAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" rel="stylesheet" />
    <link href="{{ asset('assets/css/design-tokens.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/auth.css') }}" rel="stylesheet" />
    {{-- Same pre-paint block, same key, same order as login and register, so a
         customer sent here by a failed sign-in does not change theme on the way. --}}
    <script>
        (function () {
            // The shop-wide default, from Settings. A stored choice wins.
            var theme = '{{ $defaultTheme ?? 'light' }}';
            try { theme = localStorage.getItem('apx-theme') || theme; } catch (e) {}
            if (theme !== 'dark') document.documentElement.classList.add('light-mode');
        })();
    </script>
</head>
<body>

    <header class="topbar">
        <a href="{{ url('/') }}" class="brand">
            <span class="brand-apx">APX</span>
            <span class="brand-auto">AutoMai</span>
            <span class="brand-badge">PORTAL</span>
        </a>
    </header>

    <div class="auth-wrapper">
        <div class="auth-card">

            <div class="auth-header">
                <div class="auth-eyebrow">Account Recovery</div>
                <div class="auth-title">Forgot Password</div>
                <div class="auth-subtitle">We'll email you a link to set a new one</div>
            </div>

            <div class="auth-body">

                {{-- The broker's own confirmation, e.g. "We have emailed your
                     password reset link". Deliberately the same green flash
                     login uses, so the two pages report success identically. --}}
                @if (session('status'))
                <div style="display:flex;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.35);border-radius:7px;padding:10px 14px;margin-bottom:16px;font-size:0.83rem;color:#16A34A;align-items:center;gap:8px;">
                    <i class="fas fa-circle-check"></i>
                    <span>{{ session('status') }}</span>
                </div>
                @endif

                @if ($errors->any())
                <div style="display:flex;background:rgba(232,25,44,0.1);border:1px solid rgba(232,25,44,0.3);border-radius:7px;padding:10px 14px;margin-bottom:16px;font-size:0.83rem;color:#E8192C;align-items:center;gap:8px;">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <p style="font-size:0.83rem;color:var(--text-muted);line-height:1.6;margin:0 0 18px;">
                    Enter the email address on your account and we'll send you a link to choose a new password.
                    If you booked with us as a guest, the same link finishes setting up your account.
                </p>

                <form method="POST" id="forgotForm" action="{{ route('password.email') }}">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="inputEmail">Email Address</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon"></i>
                            <input class="form-control" id="inputEmail" name="email" type="email"
                                placeholder="you@example.com" value="{{ old('email') }}" required autofocus />
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="forgotSubmitBtn">
                        <i class="fas fa-paper-plane"></i>
                        Email a Reset Link
                    </button>
                </form>
            </div>

            <div class="auth-footer">
                Remembered it? <a href="{{ route('login') }}">Back to sign in</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-brand"><span>APX</span> AutoMai &mdash; Portal &copy; {{ date('Y') }}</div>
        <div>
            <a href="{{ route('privacy') }}" target="_blank">Privacy Policy</a>
            &nbsp;&middot;&nbsp;
            <a href="{{ route('terms') }}" target="_blank">Terms &amp; Conditions</a>
        </div>
    </footer>

    <script src="{{ asset('assets/js/form-validate.js') }}"></script>
    <script>
        FormValidate.register(document.getElementById('inputEmail'), {
            rules: [FormValidate.rules.required('Email is required.'), FormValidate.rules.email()],
        });
        FormValidate.bindSubmit(document.getElementById('forgotForm'), document.getElementById('forgotSubmitBtn'));
    </script>
</body>
</html>
