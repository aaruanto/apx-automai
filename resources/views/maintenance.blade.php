<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Back Soon — APX Automai</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Barlow:wght@400;500&display=swap" rel="stylesheet" />
    <link href="{{ asset('assets/css/design-tokens.css') }}" rel="stylesheet" />
    <style>
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #0f1117; color: #e8eaed; font-family: 'Barlow', sans-serif; padding: 24px;
        }
        .card {
            max-width: 460px; text-align: center; background: #1a1d23;
            border: 1px solid rgba(255,255,255,.07); border-radius: 14px; padding: 40px 32px;
        }
        .brand { font-family: 'Barlow Condensed', sans-serif; font-size: 1.6rem; font-weight: 800;
                 letter-spacing: .04em; margin-bottom: 18px; }
        .brand span { color: var(--red); }
        h1 { font-family: 'Barlow Condensed', sans-serif; font-size: 1.5rem; margin: 0 0 12px; }
        p  { color: #9aa0a6; font-size: .92rem; line-height: 1.6; margin: 0 0 20px; }
        a  { color: var(--red); text-decoration: none; font-weight: 600; font-size: .9rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand"><span>APX</span> AutoMai</div>
        <h1>We'll be back shortly</h1>
        <p>{{ $message ?? 'The booking portal is temporarily unavailable while we carry out maintenance.' }}</p>
        <a href="tel:+639544888850">Call us: +63 9544 8888 50</a>
    </div>
</body>
</html>
