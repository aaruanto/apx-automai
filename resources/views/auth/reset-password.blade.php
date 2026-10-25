<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Set a Password — APX AUTOMAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" rel="stylesheet" />
    <link href="{{ asset('assets/css/design-tokens.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/auth.css') }}" rel="stylesheet" />
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

            {{-- "Set a password", not "reset" — two journeys arrive here. One is
                 a customer who forgot theirs. The other is someone who booked as
                 a guest and is finishing setting up the account that booking
                 created: following this link is what clears is_guest, so for
                 them there is no password to reset, only one to choose. --}}
            <div class="auth-header">
                <div class="auth-eyebrow">Account Recovery</div>
                <div class="auth-title">Set a Password</div>
                <div class="auth-subtitle">Choose the password you'll sign in with</div>
            </div>

            <div class="auth-body">

                @if ($errors->any())
                <div style="display:flex;background:rgba(232,25,44,0.1);border:1px solid rgba(232,25,44,0.3);border-radius:7px;padding:10px 14px;margin-bottom:16px;font-size:0.83rem;color:#E8192C;align-items:center;gap:8px;">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                <form method="POST" id="resetForm" action="{{ route('password.store') }}">
                    @csrf

                    <input type="hidden" name="token" value="{{ $request->route('token') }}" />

                    <div class="form-group">
                        <label class="form-label" for="inputEmail">Email Address</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon"></i>
                            <input class="form-control" id="inputEmail" name="email" type="email"
                                value="{{ old('email', $request->email) }}" required autocomplete="username" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputPassword">New Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            {{-- data-pw-group pairs this with the confirmation, so one
                                 eye reveals both: it is the same secret typed twice. --}}
                            <input class="form-control" id="inputPassword" name="password" type="password"
                                data-pw-group="new" placeholder="••••••••" required autocomplete="new-password" />
                            <button type="button" class="pw-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputPasswordConfirm">Confirm Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input class="form-control" id="inputPasswordConfirm" name="password_confirmation"
                                type="password" data-pw-group="new" placeholder="••••••••" required
                                autocomplete="new-password" />
                            <button type="button" class="pw-toggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="resetSubmitBtn">
                        <i class="fas fa-key"></i>
                        Save Password
                    </button>
                </form>
            </div>

            <div class="auth-footer">
                Already know it? <a href="{{ route('login') }}">Back to sign in</a>
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
    <script src="{{ asset('assets/js/password-toggle.js') }}"></script>
    <script>
        FormValidate.register(document.getElementById('inputEmail'), {
            rules: [FormValidate.rules.required('Email is required.'), FormValidate.rules.email()],
        });
        FormValidate.register(document.getElementById('inputPassword'), {
            rules: [FormValidate.rules.required('Password is required.'), FormValidate.rules.minLen(8)],
        });
        FormValidate.register(document.getElementById('inputPasswordConfirm'), {
            rules: [
                FormValidate.rules.required('Please confirm your password.'),
                {
                    test: function (v) { return v === document.getElementById('inputPassword').value; },
                    message: 'The two passwords do not match.',
                },
            ],
        });
        FormValidate.bindSubmit(document.getElementById('resetForm'), document.getElementById('resetSubmitBtn'));
    </script>
</body>
</html>
