{{--
    Full notification history.

    Served to admins, staff and customers alike, so it cannot extend the admin
    layout — a customer has no access to it. Standalone page, styled from the
    shared design tokens.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Notifications — APX AutoMai</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/design-tokens.css') }}" rel="stylesheet" />

    <style>
        :root {
            --surface: #fff; --surface-2: #f7f8fa; --border: #e5e7eb;
            --text: #1a1d21; --text-muted: #6b7280;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--surface-2); color: var(--text);
            font-family: 'Barlow', system-ui, sans-serif;
        }
        .wrap { max-width: 760px; margin: 0 auto; padding: 32px 16px 56px; }
        .page-title {
            font-family: 'Barlow Condensed', sans-serif; font-size: 2rem;
            font-weight: 800; margin: 0 0 4px; letter-spacing: .01em;
        }
        .card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; overflow: hidden;
        }
        .n-row {
            display: flex; gap: 12px; padding: 14px 18px;
            border-bottom: 1px solid var(--border); text-decoration: none; color: inherit;
        }
        .n-row:last-child { border-bottom: none; }
        .n-row.unread { background: var(--red-glow, rgba(232,25,44,.06)); }
        .n-row:hover { background: var(--surface-2); }
        .n-title { font-weight: 700; font-size: .9rem; }
        .n-row.unread .n-title::after {
            content: ''; display: inline-block; width: 7px; height: 7px;
            border-radius: 50%; background: var(--red); margin-left: 7px; vertical-align: middle;
        }
        .n-body { font-size: .84rem; color: var(--text-muted); margin-top: 2px; }
        .n-ago  { font-size: .72rem; color: var(--text-muted); margin-top: 4px; }
        .btn {
            font-family: inherit; font-size: .8rem; font-weight: 600; padding: 8px 14px;
            border-radius: 8px; cursor: pointer; border: 1px solid var(--border);
            background: var(--surface); color: var(--text); text-decoration: none;
        }
        .btn-primary { background: var(--red); border-color: var(--red); color: #fff; }
        .bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
        .empty { padding: 48px 20px; text-align: center; color: var(--text-muted); }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --surface: #16181c; --surface-2: #0f1115; --border: #2a2e35;
                --text: #e8eaed; --text-muted: #9aa0a6;
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="bar">
            <div>
                <h1 class="page-title">Notifications</h1>
                <div style="font-size:.82rem;color:var(--text-muted);">
                    {{ $unreadCount }} unread of {{ $notifications->total() }}
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                @if($unreadCount > 0)
                <button type="button" class="btn" id="readAllBtn">Mark all read</button>
                @endif
                <a class="btn btn-primary" href="{{ auth()->user()->dashboardRoute() ? route(auth()->user()->dashboardRoute()) : '/' }}">
                    Back to dashboard
                </a>
            </div>
        </div>

        <div class="card">
            @forelse($notifications as $n)
            <a class="n-row {{ $n->read_at ? '' : 'unread' }}"
               href="{{ $n->data['link'] ?? '#' }}"
               data-id="{{ $n->id }}">
                <i class="fas {{ $n->data['icon'] ?? 'fa-bell' }}" style="color:var(--red);margin-top:3px;width:16px;"></i>
                <div style="flex:1;min-width:0;">
                    <div class="n-title">{{ $n->data['title'] ?? 'Notification' }}</div>
                    <div class="n-body">{{ $n->data['body'] ?? '' }}</div>
                    <div class="n-ago">{{ $n->created_at->diffForHumans() }}</div>
                </div>
            </a>
            @empty
            <div class="empty">
                <i class="fas fa-bell-slash" style="font-size:1.6rem;opacity:.3;display:block;margin-bottom:10px;"></i>
                No notifications yet.
            </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
        <div style="margin-top:18px;">{{ $notifications->links() }}</div>
        @endif
    </div>

    <script>
        // Opening one marks it read on the way through; the link still follows.
        document.querySelectorAll('.n-row[data-id]').forEach(function (row) {
            row.addEventListener('click', function () {
                if (!row.classList.contains('unread')) return;
                // keepalive, not sendBeacon: a beacon cannot carry the CSRF
                // header, so Laravel would reject it with a 419.
                fetch('/notifications/' + row.dataset.id + '/read', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    keepalive: true
                });
            });
        });

        var readAll = document.getElementById('readAllBtn');
        if (readAll) {
            readAll.addEventListener('click', function () {
                fetch('/notifications/read-all', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                }).then(function () { window.location.reload(); });
            });
        }
    </script>
</body>

</html>
