<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Create Account — APX AutoMai</title>
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
                <div class="auth-eyebrow">New Account</div>
                <div class="auth-title">Create Account</div>
                <div class="auth-subtitle">Join APX AutoMai to manage or book services</div>
            </div>

            <div class="auth-body">

                {{-- Show validation errors --}}
                @if ($errors->any())
                <div style="display:flex;background:rgba(232,25,44,0.1);border:1px solid rgba(232,25,44,0.3);border-radius:7px;padding:10px 14px;margin-bottom:16px;font-size:0.83rem;color:#E8192C;align-items:center;gap:8px;">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="section-label">Personal Information</div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label" for="inputFirstName">First Name</label>
                            <div class="input-wrap">
                                <i class="fas fa-user input-icon"></i>
                                <input class="form-control" id="inputFirstName" name="first_name" type="text" 
                                    placeholder="Juan" value="{{ old('first_name') }}" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="inputLastName">Last Name</label>
                            <div class="input-wrap">
                                <i class="fas fa-user input-icon"></i>
                                <input class="form-control" id="inputLastName" name="last_name" type="text" 
                                    placeholder="dela Cruz" value="{{ old('last_name') }}" required />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputEmail">Email Address</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon"></i>
                            <input class="form-control" id="inputEmail" name="email" type="email" 
                                placeholder="you@example.com" value="{{ old('email') }}" required />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputPhone">Phone Number</label>
                        <div class="input-wrap">
                            <i class="fas fa-phone input-icon"></i>
                            <input class="form-control" id="inputPhone" name="phone" type="tel" 
                                placeholder="+63 9XX XXX XXXX" value="{{ old('phone') }}" />
                        </div>
                    </div>

                    <hr class="divider-section" />
                    <div class="section-label">Account Setup</div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label" for="inputPassword">Password</label>
                            <div class="input-wrap">
                                <i class="fas fa-lock input-icon"></i>
                                <input class="form-control" id="inputPassword" name="password" type="password" 
                                    placeholder="••••••••" required oninput="checkStrength(this.value)" />
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
                                <input class="form-control" id="inputPasswordConfirm" name="password_confirmation" 
                                    type="password" placeholder="••••••••" required />
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
                Already have an account? <a href="{{ route('login') }}">Sign in</a>
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