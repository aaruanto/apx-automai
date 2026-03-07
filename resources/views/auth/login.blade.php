<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Login — APX AUTOMAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="{{ asset('assets/css/auth.css') }}" rel="stylesheet" />
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
                <div class="auth-eyebrow">Portal Access</div>
                <div class="auth-title">Sign In</div>
                <div class="auth-subtitle">Enter your credentials to access the dashboard</div>
            </div>

            <div class="auth-body">

                {{-- Show validation errors --}}
                @if ($errors->any())
                <div style="display:flex;background:rgba(232,25,44,0.1);border:1px solid rgba(232,25,44,0.3);border-radius:7px;padding:10px 14px;margin-bottom:16px;font-size:0.83rem;color:#E8192C;align-items:center;gap:8px;">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="inputEmail">Email Address</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon"></i>
                            <input class="form-control" id="inputEmail" name="email" type="email" 
                                placeholder="you@apxautomai.com" value="{{ old('email') }}" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputPassword">Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input class="form-control" id="inputPassword" name="password" type="password" 
                                placeholder="••••••••" required />
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
                        @if (Route::has('password.request'))
                        <a class="link-sm" href="{{ route('password.request') }}">Forgot password?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-primary">
                        <i class="fas fa-right-to-bracket"></i>
                        Sign In
                    </button>
                </form>
            </div>

            <div class="auth-footer">
                Don't have an account? <a href="{{ route('register') }}">Create one</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-brand"><span>APX</span> AutoMai &mdash; Admin Portal &copy; 2025</div>
        <div>
            <a href="#">Privacy Policy</a>
            &nbsp;&middot;&nbsp;
            <a href="#">Terms &amp; Conditions</a>
        </div>
    </footer>

    <script>
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