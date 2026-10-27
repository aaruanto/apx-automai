<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>My Dashboard — APX AutoMai</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" rel="stylesheet" />
    <link href="{{ asset('assets/css/design-tokens.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/customer-dashboard.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/availability-picker.css') }}" rel="stylesheet" />
    {{-- Before first paint. The theme used to be applied by the script at the
         bottom of this file, which was invisible only because the stylesheet's
         own default — dark — matched the fallback. Now that the fallback is
         light, waiting until the end of the body would paint the page dark and
         repaint it light on every first visit.

         The block below only adds the class; the script at the bottom still
         owns the toggle, the stored value and the icon. --}}
    <script>
        (function () {
            // The shop-wide default, from Settings. A stored choice wins.
            var theme = '{{ $defaultTheme ?? 'light' }}';
            try { theme = localStorage.getItem('apx-theme') || theme; } catch (e) {}
            if (theme !== 'dark') document.documentElement.classList.add('light-mode');
        })();
    </script>
    <style>
        .theme-toggle { background: none; border: none; cursor: pointer; padding: 0; line-height: 1; }
        *, *::before, *::after { transition: background-color 0.25s ease, color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease; }
        /* Sidebar collapse */
        .sidebar.collapsed { width: 0 !important; min-width: 0 !important; overflow: hidden !important; padding: 0 !important; }
        .main-content.expanded { margin-left: 0 !important; }
        /* Fix time/date picker icons showing through dark theme */
        input[type="time"].m-control, input[type="date"].m-control { color-scheme: dark; }
        html.light-mode input[type="time"].m-control, html.light-mode input[type="date"].m-control { color-scheme: light; }
        html.light-mode { --black: #f4f5f7; --surface: #ffffff; --surface-2: #f0f1f3; --surface-3: #e6e8ec; --border: rgba(0,0,0,0.09); --text: #1a1d23; --text-muted: #6b7280; --red-glow: rgba(232,25,44,0.10); }
        html.light-mode body { background: #f4f5f7; }
        html.light-mode .topnav { box-shadow: 0 1px 6px rgba(0,0,0,.07); }
        html.light-mode #sidebarToggle { color: #6b7280; }
        html.light-mode .brand-auto { color: #1a1d23; }
        html.light-mode .brand-badge { background: #e6e8ec; color: #6b7280; border-color: rgba(0,0,0,0.09); }
        html.light-mode .nav-link { color: #6b7280; }
        html.light-mode .nav-link:hover { color: #1a1d23; }
        html.light-mode .nav-link.active { color: #E8192C; }
        html.light-mode .section-label { color: #9ca3af; }
        html.light-mode .sidebar-footer-info .label { color: #9ca3af; }
        html.light-mode .sidebar-footer-info .value { color: #1a1d23; }
        html.light-mode .section-tab { color: #6b7280; }
        html.light-mode .section-tab:hover { color: #1a1d23; }
        html.light-mode .section-tab.active { color: #E8192C; border-bottom-color: #E8192C; }
        html.light-mode .section-tab.active .tab-count { background: rgba(232,25,44,0.10); border-color: rgba(232,25,44,0.3); color: #E8192C; }
        html.light-mode .welcome-banner { background: #ffffff !important; border: 1px solid rgba(0,0,0,0.08) !important; border-left: 3px solid #E8192C !important; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        html.light-mode .welcome-text h2 { color: #1a1d23; }
        html.light-mode .welcome-text h2 span { color: #E8192C; }
        html.light-mode .welcome-text p { color: #6b7280; }
        html.light-mode .stat-card { box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        html.light-mode .stat-value { color: #1a1d23; }
        html.light-mode .stat-footer a { color: #6b7280; }
        html.light-mode .card { box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        html.light-mode .card-header-title { color: #1a1d23; }
        html.light-mode .card-badge { background: #e6e8ec; color: #6b7280; }
        html.light-mode .booking-date-box .day { color: #E8192C; }
        html.light-mode .booking-service { color: #1a1d23; }
        html.light-mode .quick-action-btn { color: #374151; }
        html.light-mode .quick-action-btn:hover { color: #374151; background: #dde0e6; }
        html.light-mode .filter-tab { color: #6b7280; }
        html.light-mode .filter-tab:hover { color: #1a1d23; border-color: rgba(0,0,0,0.18); }
        html.light-mode .filter-tab.active { background: #E8192C; border-color: #E8192C; color: #fff; }
        html.light-mode .dot-all { background: #9ca3af; }
        html.light-mode .bk-card { box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        html.light-mode .bk-service { color: #1a1d23; }
        html.light-mode .bk-amount { color: #1a1d23; }
        html.light-mode .bk-status-col { border-left-color: rgba(0,0,0,0.07); }
        html.light-mode .shop-result-count { color: #6b7280; }
        html.light-mode .shop-result-count span { color: #1a1d23; }
        html.light-mode .cat-filter { color: #374151; }
        html.light-mode .cat-filter:hover { color: #1a1d23; border-color: rgba(0,0,0,0.18); }
        html.light-mode .cat-filter.active { background: #E8192C; border-color: #E8192C; color: #fff; }
        html.light-mode .service-card { box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        html.light-mode .service-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.12); }
        html.light-mode .service-name { color: #1a1d23; }
        html.light-mode .service-cat-badge { background: rgba(255,255,255,0.85); color: #374151; border-color: rgba(0,0,0,0.1); }
        html.light-mode .service-card-footer { border-top-color: rgba(0,0,0,0.07); }
        html.light-mode .modal { box-shadow: 0 16px 48px rgba(0,0,0,.18); }
        html.light-mode .modal-title { color: #1a1d23; }
        html.light-mode .modal-close:hover { background: #f0f1f3; color: #1a1d23; }
        html.light-mode .modal-service-chip .chip-name { color: #1a1d23; }
        html.light-mode .m-label { color: #374151; }
        html.light-mode .btn-modal-cancel { color: #374151; border-color: rgba(0,0,0,0.09); }
        html.light-mode .btn-modal-cancel:hover { background: #e6e8ec; color: #1a1d23; border-color: rgba(0,0,0,0.14); }
        html.light-mode .modal-success .ref { background: #f0f1f3; color: #1a1d23; }
        html.light-mode .page-title { color: #1a1d23; }
        html.light-mode .breadcrumb li { color: #6b7280; }
        html.light-mode .breadcrumb li.active { color: #E8192C; }
        html.light-mode .page-date { color: #6b7280; }
        html.light-mode .user-name { color: #1a1d23; }
        html.light-mode .notif-dot { border-color: #ffffff; }
        html.light-mode .empty-state i { color: #d1d5db; }
        html.light-mode .services-empty i { color: #d1d5db; }
        html.light-mode ::-webkit-scrollbar-track { background: #f4f5f7; }
        html.light-mode ::-webkit-scrollbar-thumb { background: #d1d5db; }
        .btn-book-service { background: var(--red); color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-family: 'Barlow Condensed', sans-serif; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; gap: 5px; opacity: 0; transform: translateY(4px); transition: background 0.2s, transform 0.2s, opacity 0.2s; pointer-events: none; }
        .service-card:hover .btn-book-service { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .btn-book-service:hover { background: var(--red-dark); transform: scale(1.03); }
        /* Bundle cards reuse the service Book button so the two read as the
           same action, but they have no hover-to-reveal parent — a bundle is
           the whole offer, not a row you hover to inspect — so it stays
           visible. It was a generic .btn btn-primary before, which rendered
           dark and lowercase beside the red uppercase one on the next tab. */
        .promo-card .btn-book-service { opacity: 1; transform: none; pointer-events: auto; }
        .m-control.m-select { appearance: none; -webkit-appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; }
        .m-control.m-select option { background: var(--surface-3); color: var(--text); }
        .m-vehicle-hint { font-size: 0.72rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px; margin-top: 4px; }
        .m-vehicle-hint a { color: var(--red); text-decoration: none; cursor: pointer; }
        .m-vehicle-hint a:hover { text-decoration: underline; }
        .vehicles-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .vehicles-header-text h3 { font-family: 'Barlow Condensed', sans-serif; font-size: 1.1rem; font-weight: 800; letter-spacing: 0.03em; }
        .vehicles-header-text p { font-size: 0.82rem; color: var(--text-muted); margin-top: 3px; }
        .btn-add-vehicle { background: var(--red); color: #fff; border: none; padding: 9px 18px; border-radius: 7px; font-family: 'Barlow Condensed', sans-serif; font-size: 0.88rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background 0.2s, transform 0.15s, box-shadow 0.2s; white-space: nowrap; }
        .btn-add-vehicle:hover { background: var(--red-dark); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(232,25,44,0.35); }
        .vehicles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 32px; }
        .vehicle-card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; transition: border-color 0.2s, transform 0.15s; position: relative; }
        .vehicle-card:hover { border-color: rgba(232,25,44,0.35); transform: translateY(-2px); }
        .vehicle-card.primary-vehicle { border-color: rgba(232,25,44,0.5); }
        .vehicle-card-banner { background: var(--surface-2); height: 80px; display: flex; align-items: center; justify-content: center; position: relative; border-bottom: 1px solid var(--border); overflow: hidden; }
        .vehicle-card-banner::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(232,25,44,0.08) 0%, transparent 60%); }
        .vehicle-card-banner i { font-size: 2.2rem; color: var(--surface-3); }
        .vehicle-primary-badge { position: absolute; top: 8px; left: 8px; background: var(--red); color: #fff; font-size: 0.6rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; padding: 2px 8px; border-radius: 20px; }
        .vehicle-card-body { padding: 16px 18px 14px; }
        .vehicle-name { font-family: 'Barlow Condensed', sans-serif; font-size: 1.1rem; font-weight: 800; letter-spacing: 0.02em; color: var(--text); margin-bottom: 4px; }
        .vehicle-plate { font-family: monospace; font-size: 0.8rem; color: var(--text-muted); background: var(--surface-3); border: 1px solid var(--border); display: inline-block; padding: 2px 10px; border-radius: 4px; letter-spacing: 0.08em; margin-bottom: 10px; }
        .vehicle-meta-row { display: flex; gap: 14px; flex-wrap: wrap; font-size: 0.77rem; color: var(--text-muted); margin-bottom: 14px; }
        .vehicle-meta-row span { display: flex; align-items: center; gap: 4px; }
        .vehicle-meta-row i { color: var(--red); font-size: 0.7rem; }

        /* Service-due strip. Three states, and the colour is the whole point:
           red is reserved for errors and things genuinely wrong, so "due next
           month" is informational blue and only "overdue" earns the red. A
           vehicle with nothing outstanding gets the quiet grey variant. */
        .vehicle-service-note {
            display: flex; align-items: flex-start; gap: 7px;
            font-size: 0.75rem; line-height: 1.45;
            padding: 8px 10px; border-radius: 7px; margin-bottom: 14px;
            background: var(--surface-2); color: var(--text-muted);
            border: 1px solid var(--border);
        }
        .vehicle-service-note i { margin-top: 2px; font-size: 0.72rem; flex-shrink: 0; }
        .vehicle-service-note.is-due {
            background: var(--info-glow, rgba(59,130,246,.12));
            border-color: var(--info, #3B82F6);
            color: var(--text);
        }
        .vehicle-service-note.is-due i { color: var(--info, #3B82F6); }
        .vehicle-service-note.is-overdue {
            background: var(--red-glow); border-color: var(--red); color: var(--text);
        }
        .vehicle-service-note.is-overdue i { color: var(--red); }
        .vehicle-card-actions { display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid var(--border); }
        .btn-veh-action { flex: 1; background: var(--surface-3); border: 1px solid var(--border); color: var(--text-muted); padding: 7px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; transition: color 0.2s, border-color 0.2s, background 0.2s; }
        .btn-veh-action:hover { color: var(--text); border-color: rgba(255,255,255,0.14); }
        .btn-veh-action.danger:hover { color: var(--red); border-color: rgba(232,25,44,0.4); background: var(--red-glow); }
        .btn-veh-action i { font-size: 0.72rem; }
        .add-vehicle-form { background: var(--surface); border: 1px solid rgba(232,25,44,0.3); border-radius: 10px; padding: 24px; margin-bottom: 28px; display: none; animation: modalIn 0.2s ease both; }
        .add-vehicle-form.open { display: block; }
        .add-vehicle-form-title { font-family: 'Barlow Condensed', sans-serif; font-size: 1rem; font-weight: 800; letter-spacing: 0.04em; color: var(--text); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .add-vehicle-form-title i { color: var(--red); }
        .avf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
        @media (max-width: 500px) { .avf-grid { grid-template-columns: 1fr; } }
        .avf-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 4px; }
        .btn-avf-cancel { background: none; border: 1px solid var(--border); color: var(--text-muted); padding: 9px 20px; border-radius: 7px; font-size: 0.85rem; cursor: pointer; transition: color 0.2s, border-color 0.2s; }
        .btn-avf-cancel:hover { color: var(--text); border-color: rgba(255,255,255,0.14); }
        .btn-avf-save { background: var(--red); color: #fff; border: none; padding: 9px 24px; border-radius: 7px; font-family: 'Barlow Condensed', sans-serif; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; gap: 7px; transition: background 0.2s, transform 0.15s; }
        .btn-avf-save:hover { background: var(--red-dark); transform: translateY(-1px); }
        .vehicles-empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .vehicles-empty-state i { font-size: 3rem; opacity: 0.15; display: block; margin-bottom: 16px; }
        .vehicles-empty-state p { font-size: 0.88rem; margin-bottom: 20px; }
        .rw-section-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
        .rw-section-title { font-family: 'Barlow Condensed', sans-serif; font-size: 0.95rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text); display: flex; align-items: center; gap: 8px; }
        .rw-section-title i { color: var(--red); }
        .promos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
        /* Column, so the footer can be pushed to the bottom. The grid already
           stretches every card in a row to the same height, but the body was
           block-flow, so the price row sat wherever the description happened to
           end. One description wrapping to an extra line dropped that card's
           price 19px below its neighbours' — measured at 1180px, where
           Standard Service's longer text turns three lines into four. */
        .promo-card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; transition: border-color 0.2s, transform 0.15s; display: flex; flex-direction: column; }
        .promo-card:hover { border-color: rgba(232,25,44,0.35); transform: translateY(-2px); }
        .promo-card-accent { height: 4px; }
        .promo-card-body { padding: 16px 18px 14px; display: flex; flex-direction: column; flex: 1; }
        .promo-tag { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; display: flex; align-items: center; gap: 5px; }
        .promo-tag i { font-size: 0.6rem; }
        .promo-title { font-family: 'Barlow Condensed', sans-serif; font-size: 1rem; font-weight: 800; letter-spacing: 0.02em; color: var(--text); margin-bottom: 4px; line-height: 1.25; }
        .promo-desc { font-size: 0.78rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 14px; }
        /* margin-top:auto is what actually levels the footers; everything above
           it takes its natural height and the gap lands in the middle. */
        .promo-card-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-top: 12px; border-top: 1px solid var(--border); margin-top: auto; }
        .promo-expiry { font-size: 0.72rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; }
        .promo-expiry i { color: var(--red); font-size: 0.66rem; }

        /* ── BUNDLE STRIP (Book a Service) ──
           A rail of the same .promo-card used in Promos & Offers, in its
           compact form. Same builder, same classes: the two panels cannot
           show different prices for the same bundle. */
        .bundle-strip { margin-bottom: 18px; }
        .bundle-strip[hidden] { display: none; }
        .bundle-strip-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .bundle-strip-title { font-family: 'Barlow Condensed', sans-serif; font-size: 0.95rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text); display: flex; align-items: center; gap: 8px; }
        .bundle-strip-title i { color: #16a34a; }
        .bundle-strip-all { font-size: 0.76rem; color: var(--text-muted); text-decoration: none; white-space: nowrap; cursor: pointer; }
        .bundle-strip-all:hover { color: var(--red); }

        /* Scrolls sideways rather than wrapping, so the strip stays one row
           tall however many bundles the shop is running and never pushes the
           service grid off the first screen. */
        .bundle-strip-rail {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            scroll-snap-type: x proximity;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
        }
        .bundle-strip-rail > .promo-card { flex: 0 0 272px; scroll-snap-align: start; }
        .bundle-strip-rail::-webkit-scrollbar { height: 6px; }
        .bundle-strip-rail::-webkit-scrollbar-thumb { background: var(--border-hover, rgba(128,128,128,0.35)); border-radius: 3px; }

        .promo-card--compact .promo-card-body { padding: 13px 15px 12px; }
        .promo-card--compact .promo-desc { margin-bottom: 12px; }

        /* A phone has no room for a 272px card plus a peek of the next one,
           so the cards take most of the width and the rail snaps card to card. */
        @media (max-width: 520px) {
            .bundle-strip-rail { scroll-snap-type: x mandatory; }
            .bundle-strip-rail > .promo-card { flex: 0 0 85%; }
        }
        .promo-code-wrap { display: flex; align-items: center; gap: 6px; }
        .promo-code { font-family: monospace; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.08em; background: var(--surface-3); border: 1px solid var(--border); color: var(--text); padding: 4px 10px; border-radius: 5px; }
        .btn-copy-code { background: none; border: 1px solid var(--border); color: var(--text-muted); width: 28px; height: 28px; border-radius: 5px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.72rem; transition: color 0.2s, border-color 0.2s, background 0.2s; flex-shrink: 0; }
        .btn-copy-code:hover { color: var(--red); border-color: var(--red); background: var(--red-glow); }
        .btn-copy-code.copied { color: var(--success); border-color: var(--success); }
        .dropdown { position: relative; padding-bottom: 8px; }
        .dropdown-menu { position: absolute; top: calc(100% + 0px); right: 0; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 6px; min-width: 180px; box-shadow: 0 8px 24px rgba(0,0,0,.12); display: none; z-index: 9999; }
        .dropdown-menu.show { display: block; }
        .dropdown-menu a { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: .83rem; color: var(--text); text-decoration: none; }
        .dropdown-menu a:hover { background: var(--surface-2); }
        .dropdown-menu hr { border: none; border-top: 1px solid var(--border); margin: 4px 0; }
        .dropdown-menu .logout { color: var(--red) !important; }
        .dropdown-menu .logout:hover { background: var(--red-glow); }
        .dropdown:hover .dropdown-menu { display: block; }
    </style>
</head>
<body>

<!-- TOPNAV -->
<nav class="topnav">
    <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <a href="{{ route('customer.dashboard') }}" class="brand">
        <span class="brand-apx">APX</span>
        <span class="brand-auto">AutoMai</span>
        <span class="brand-badge">CUSTOMER</span>
    </a>
    <div class="topnav-actions">
        <x-notification-bell />
        <button id="themeToggle" class="icon-btn theme-toggle" title="Switch to light mode" aria-label="Toggle theme">
            <i class="fas fa-moon" id="themeIcon"></i>
        </button>
        <div class="dropdown">
            <a class="user-chip" href="#!">
                <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                <span class="user-name">{{ Auth::user()->name }}</span>
                <i class="fas fa-chevron-down" style="font-size:0.65rem;color:var(--text-muted);margin-left:4px;"></i>
            </a>
            <div class="dropdown-menu">
                <a href="#" onclick="event.preventDefault(); switchSection(null,'profile')"><i class="fas fa-user" style="width:16px;margin-right:8px;"></i>My Profile</a>
                <a href="#" onclick="event.preventDefault(); switchSection(null,'vehicles'); renderVehicles()"><i class="fas fa-car" style="width:16px;margin-right:8px;"></i>My Vehicles</a>
                <a href="#" onclick="event.preventDefault(); switchSection(null,'settings')"><i class="fas fa-gear" style="width:16px;margin-right:8px;"></i>Settings</a>
                <hr />
                <a href="{{ route('logout') }}" class="logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-right-from-bracket" style="width:16px;margin-right:8px;"></i>Logout
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
            </div>
        </div>
    </div>
</nav>

<!-- LAYOUT -->
<div class="layout">

    <!-- SIDEBAR -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-body">
            <div class="section-label">My Account</div>
            <a class="nav-link active" id="nav-dashboard" href="#" onclick="switchSection(event, 'dashboard')">
                <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
                <span class="nav-label">Dashboard</span>
            </a>
            <hr class="sidebar-divider" />
            <div class="section-label">Services</div>
            <a class="nav-link" href="#" onclick="switchSection(event, 'bookings'); filterBookings('all')">
                <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
                <span class="nav-label">My Bookings</span>
            </a>
            <a class="nav-link" id="nav-services" href="#" onclick="switchSection(event,'services')">
                <span class="nav-icon"><i class="fas fa-circle-plus"></i></span>
                <span class="nav-label">Book a Service</span>
            </a>
            <a class="nav-link" id="nav-vehicles" href="#" onclick="switchSection(event,'vehicles')">
                <span class="nav-icon"><i class="fas fa-car"></i></span>
                <span class="nav-label">My Vehicles</span>
            </a>
            <hr class="sidebar-divider" />
            <div class="section-label">Offers</div>
            <a class="nav-link" id="nav-promos" href="#" onclick="switchSection(event,'promos')">
                <span class="nav-icon"><i class="fas fa-gift"></i></span>
                <span class="nav-label">Promos &amp; Offers</span>
            </a>
            <hr class="sidebar-divider" />
            <div class="section-label">Account</div>
            <a class="nav-link" id="nav-profile" href="#" onclick="switchSection(event,'profile')">
                <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
                <span class="nav-label">My Profile</span>
            </a>
            <a class="nav-link" id="nav-settings" href="#" onclick="switchSection(event,'settings')">
                <span class="nav-icon"><i class="fas fa-gear"></i></span>
                <span class="nav-label">Settings</span>
            </a>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <main>

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <h1 class="page-title" id="pageTitle">MY <span>DASHBOARD</span></h1>
                    <ol class="breadcrumb">
                        <li>Customer Portal</li>
                        <li class="active" id="pageBreadcrumb">Dashboard</li>
                    </ol>
                </div>
                <div class="page-date">
                    <i class="far fa-calendar" style="margin-right:6px;color:var(--red);"></i>
                    <span id="currentDate"></span>
                </div>
            </div>

            <!-- SECTION TABS -->
            <div class="section-tabs">
                <button class="section-tab active" id="tab-dashboard" onclick="switchSection(event,'dashboard')">
                    <i class="fas fa-gauge-high"></i> Dashboard
                </button>
                <button class="section-tab" id="tab-bookings" onclick="switchSection(event,'bookings'); filterBookings('all')">
                    <i class="fas fa-calendar-check"></i> My Bookings
                    <span class="tab-count" id="tabCountBookings">{{ $bookings->count() }}</span>
                </button>
                <button class="section-tab" id="tab-services" onclick="switchSection(event,'services')">
                    <i class="fas fa-wrench"></i> Book a Service
                </button>
                <button class="section-tab" id="tab-vehicles" onclick="switchSection(event,'vehicles')">
                    <i class="fas fa-car"></i> My Vehicles
                    <span class="tab-count" id="tabCountVehicles">{{ $vehicles->count() }}</span>
                </button>
                <button class="section-tab" id="tab-promos" onclick="switchSection(event,'promos')">
                    <i class="fas fa-gift"></i> Promos &amp; Offers
                </button>
            </div>

            <!-- DASHBOARD PANEL -->
            <div class="tab-panel active" id="panel-dashboard">

                <!-- WELCOME BANNER -->
                <div class="welcome-banner">
                    <div class="welcome-text">
                        <x-greeting :name="Auth::user()->name" />
                        <p>You have {{ $upcoming }} upcoming booking{{ $upcoming !== 1 ? 's' : '' }}. Your car is in good hands.</p>
                    </div>
                    <a href="#" class="btn-book" onclick="switchSection(event,'bookings'); filterBookings('all')">
                        <i class="fas fa-calendar-check"></i> View My Bookings
                    </a>
                </div>

                <!-- STAT CARDS -->
                <div style="display:flex;justify-content:flex-end;align-items:center;gap:10px;margin-bottom:8px;">
                    <span id="cLiveUpdated" style="font-size:0.72rem;color:var(--text-muted);"></span>
                    <button type="button" id="cLiveRefreshBtn" onclick="ApxLiveRefresh.refreshNow()" title="Refresh now"
                            style="background:none;border:1px solid var(--border);color:var(--text-muted);width:30px;height:30px;border-radius:7px;cursor:pointer;">
                        <i class="fas fa-rotate"></i>
                    </button>
                </div>
                <div class="cards-grid">
                    <div class="stat-card primary">
                        <div class="stat-card-header">
                            <div class="stat-label">Upcoming</div>
                            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                        </div>
                        <div class="stat-value" id="cstatUpcoming">{{ $upcoming }}</div>
                        <div class="stat-footer">
                            <a href="#" onclick="switchSection(event,'bookings'); filterBookings('upcoming')">View Bookings <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-card-header">
                            <div class="stat-label">Completed</div>
                            <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                        </div>
                        <div class="stat-value" id="cstatCompleted">{{ $completed }}</div>
                        <div class="stat-footer">
                            <a href="#" onclick="switchSection(event,'bookings'); filterBookings('completed')">View History <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-card-header">
                            <div class="stat-label">Total Bookings</div>
                            <div class="stat-icon"><i class="fas fa-list-check"></i></div>
                        </div>
                        <div class="stat-value" id="cstatTotal">{{ $totalBookings }}</div>
                        <div class="stat-footer">
                            <a href="#" onclick="switchSection(event,'bookings'); filterBookings('all')">View All <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-card-header">
                            <div class="stat-label">Total Spent</div>
                            <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                        </div>
                        <div class="stat-value" id="cstatSpent">₱{{ number_format($totalSpent ?? 0, 0) }}</div>
                        <div class="stat-footer">
                            <a href="#">View Invoices <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                </div>

                <!-- CONTENT GRID -->
                <div class="content-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-title"><i class="fas fa-calendar-check"></i> Upcoming Bookings</div>
                            <a href="#" onclick="switchSection(event,'bookings'); filterBookings('upcoming')" style="font-size:0.8rem;color:var(--red);text-decoration:none;">View All <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                        <div class="card-body">
                            @forelse($bookings->whereIn('status', ['confirmed', 'pending'])->take(3) as $b)
                            <div class="booking-item">
                                <div class="booking-date-box">
                                    <span class="day">{{ date('d', strtotime($b->booking_date)) }}</span>
                                    <span class="mon">{{ date('M', strtotime($b->booking_date)) }}</span>
                                </div>
                                <div class="booking-info">
                                    <div class="booking-service">{{ $b->service_list }}</div>
                                    <div class="booking-meta">
                                        <span><i class="fas fa-clock"></i>{{ date('g:i A', strtotime($b->booking_time)) }}</span>
                                        <span><i class="fas fa-user"></i>{{ $b->staff->name ?? 'TBA' }}</span>
                                        <span><i class="fas fa-car"></i>{{ ($b->vehicle->make ?? '') . ' (' . ($b->vehicle?->display_plate ?? 'Not provided') . ')' }}</span>
                                    </div>
                                </div>
                                <span class="badge badge-{{ $b->status === 'confirmed' ? 'confirmed' : 'pending' }}">{{ ucfirst($b->status) }}</span>
                            </div>
                            @empty
                            <p style="color:var(--text-muted);font-size:0.85rem;text-align:center;padding:20px 0;">No upcoming bookings.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-title"><i class="fas fa-bolt"></i> Quick Actions</div>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions">
                                    <a href="#" class="quick-action-btn" onclick="switchSection(event,'services')"><i class="fas fa-circle-plus"></i>Book Service</a>
                                    <a href="#" class="quick-action-btn" onclick="switchSection(event,'vehicles')"><i class="fas fa-car"></i>My Vehicles</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /panel-dashboard -->

            <!-- MY BOOKINGS PANEL -->
            <div class="tab-panel" id="panel-bookings">
                <div class="filter-bar">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all" onclick="filterBookings('all')">
                            <span class="dot dot-all"></span> All
                            <span class="tab-count" id="cnt-all">{{ $bookings->count() }}</span>
                        </button>
                        <button class="filter-tab" data-filter="upcoming" onclick="filterBookings('upcoming')">
                            <span class="dot dot-upcoming"></span> Upcoming
                            <span class="tab-count" id="cnt-upcoming">{{ $upcoming }}</span>
                        </button>
                        <button class="filter-tab" data-filter="in_progress" onclick="filterBookings('in_progress')">
                            <span class="dot dot-inprogress"></span> In Progress
                            <span class="tab-count" id="cnt-inprogress">{{ $bookings->where('status','in_progress')->count() }}</span>
                        </button>
                        <button class="filter-tab" data-filter="completed" onclick="filterBookings('completed')">
                            <span class="dot dot-completed"></span> Completed
                            <span class="tab-count" id="cnt-completed">{{ $completed }}</span>
                        </button>
                        <button class="filter-tab" data-filter="cancelled" onclick="filterBookings('cancelled')">
                            <span class="dot dot-cancelled"></span> Cancelled
                            <span class="tab-count" id="cnt-cancelled">{{ $bookings->where('status','cancelled')->count() }}</span>
                        </button>
                    </div>
                    <div class="filter-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="bookingSearch" placeholder="Search bookings..." oninput="searchBookings(this.value)" />
                    </div>
                </div>
                <div class="booking-cards" id="bookingCardsList"></div>
                <div class="empty-state" id="bookingEmpty" style="display:none;">
                    <i class="fas fa-calendar-xmark"></i>
                    <p>No bookings found for this filter.</p>
                </div>
            </div><!-- /panel-bookings -->

            <!-- BOOK A SERVICE PANEL -->
            <div class="tab-panel" id="panel-services">
                <div class="shop-toolbar">
                    <div class="shop-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="serviceSearch" placeholder="Search services..." oninput="filterServices()" />
                    </div>
                    <div class="shop-result-count">Showing <span id="serviceCount">28</span> services</div>
                    <div class="view-toggle-group">
                        <button type="button" class="view-toggle-btn" id="viewToggleTiles" title="Tile view" onclick="setServicesView('tiles')"><i class="fas fa-grip"></i></button>
                        <button type="button" class="view-toggle-btn" id="viewToggleList" title="List view" onclick="setServicesView('list')"><i class="fas fa-list"></i></button>
                    </div>
                </div>

                {{-- Bundles, where a customer is actually choosing what to book.
                     They were only ever shown under Promos & Offers, a tab you
                     reach after you have already picked a service. Rendered by
                     the same builder as that panel, and hidden while a search or
                     a category filter is narrowing the list, because a bundle
                     that ignores the filter reads like a bug. --}}
                <div class="bundle-strip" id="bundleStrip" hidden>
                    <div class="bundle-strip-head">
                        <div class="bundle-strip-title"><i class="fas fa-boxes-stacked"></i> Save with a bundle</div>
                        <a class="bundle-strip-all" onclick="switchSection(event, 'promos')">See all bundles</a>
                    </div>
                    <div class="bundle-strip-rail" id="bundleStripRail"></div>
                </div>

                <div class="category-filters">
                    <button class="cat-filter active" data-cat="all" onclick="setCat(this)">All Services</button>
                    <button class="cat-filter" data-cat="engine" onclick="setCat(this)">Engine & Oil</button>
                    <button class="cat-filter" data-cat="cvt" onclick="setCat(this)">CVT & Transmission</button>
                    <button class="cat-filter" data-cat="brakes" onclick="setCat(this)">Brakes & Pipes</button>
                    <button class="cat-filter" data-cat="inspection" onclick="setCat(this)">Inspection</button>
                    <button class="cat-filter" data-cat="free" onclick="setCat(this)">Free Services</button>
                </div>
                <div class="services-grid" id="servicesGrid"></div>
            </div><!-- /panel-services -->

            <!-- MY VEHICLES PANEL -->
            <div class="tab-panel" id="panel-vehicles">
                <div class="vehicles-header">
                    <div class="vehicles-header-text">
                        <h3>MY REGISTERED <span style="color:var(--red);">VEHICLES</span></h3>
                        <p>Manage your motorcycles and vehicles for faster booking.</p>
                    </div>
                    <button class="btn-add-vehicle" onclick="toggleAddVehicleForm()">
                        <i class="fas fa-plus"></i> Add Vehicle
                    </button>
                </div>
                <div class="add-vehicle-form" id="addVehicleForm">
                    <div class="add-vehicle-form-title"><i class="fas fa-circle-plus"></i> Register a New Vehicle</div>
                    <div class="avf-grid">
                        <div class="m-form-group">
                            <label class="m-label">Vehicle Type</label>
                            <div class="m-input-wrap"><i class="fas fa-car"></i>
                                <select class="m-control" id="avfType">
                                    <option value="car">Car</option>
                                    <option value="motorcycle">Motorcycle</option>
                                </select>
                            </div>
                        </div>
                        <div class="m-form-group">
                            <label class="m-label">Make & Model</label>
                            <div class="m-input-wrap"><i class="fas fa-motorcycle"></i><input class="m-control" id="avfMake" type="text" placeholder="e.g. Honda Click 125i" /></div>
                        </div>
                        <div class="m-form-group">
                            <label class="m-label">Year</label>
                            <div class="m-input-wrap"><i class="fas fa-calendar"></i><input class="m-control" id="avfYear" type="number" placeholder="e.g. 2022" min="1990" max="2030" /></div>
                        </div>
                        <div class="m-form-group">
                            <label class="m-label">Plate / Unit No.</label>
                            <div class="m-input-wrap"><i class="fas fa-id-card"></i><input class="m-control" id="avfPlate" type="text" placeholder="ABC 1234, 123 ABC or AB 12345" /></div>
                        </div>
                        <div class="m-form-group">
                            <label class="m-label">Color</label>
                            <div class="m-input-wrap"><i class="fas fa-palette"></i><input class="m-control" id="avfColor" type="text" placeholder="e.g. Matte Black" /></div>
                        </div>
                        {{-- Optional, and said so on the label. Plenty of
                             customers do not know their odometer reading.
                             It is a record for the shop and nothing more:
                             reminders are time-only, so this changes nothing
                             about when anyone is told. The hint used to
                             promise "sooner when a service is due", which
                             stopped being true when distance rules went. --}}
                        <div class="m-form-group">
                            <label class="m-label">Mileage <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label>
                            <div class="m-input-wrap"><i class="fas fa-gauge"></i><input class="m-control" id="avfMileage" type="number" min="0" max="2000000" placeholder="e.g. 42000" /></div>
                            <div class="m-hint">Kept with your vehicle's record for the shop. Leave blank if you are not sure.</div>
                        </div>
                    </div>
                    <div class="avf-actions">
                        <button class="btn-avf-cancel" onclick="toggleAddVehicleForm()">Cancel</button>
                        <button class="btn-avf-save" onclick="saveNewVehicle()"><i class="fas fa-floppy-disk"></i> Save Vehicle</button>
                    </div>
                </div>
                <div class="vehicles-grid" id="vehiclesGrid"></div>
                <div class="vehicles-empty-state" id="vehiclesEmpty" style="display:none;">
                    <i class="fas fa-car"></i>
                    <p>No vehicles registered yet. Add your first vehicle to enable faster booking.</p>
                    <button class="btn-add-vehicle" onclick="toggleAddVehicleForm()" style="margin:0 auto;"><i class="fas fa-plus"></i> Add Vehicle</button>
                </div>
            </div><!-- /panel-vehicles -->

            <!-- PROMOS PANEL -->
            <div class="tab-panel" id="panel-promos">

                @if ($loyalty['enabled'])
                {{-- LOYALTY
                     Balance and every movement behind it. The history is the
                     point: a balance on its own is a number a customer has
                     to take on trust. --}}
                <div class="rw-section-header">
                    <div class="rw-section-title"><i class="fas fa-star"></i> My Points</div>
                    <span class="card-badge">{{ number_format($loyalty['balance']) }} points</span>
                </div>

                <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px 22px;margin-bottom:24px;">
                    <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:baseline;">
                        <div>
                            <div style="font-family:'Barlow Condensed',sans-serif;font-size:2.4rem;font-weight:800;line-height:1;color:var(--text);">
                                {{ number_format($loyalty['balance']) }}
                            </div>
                            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-top:2px;">
                                points
                            </div>
                        </div>
                        <div style="font-size:.86rem;color:var(--text-muted);line-height:1.6;">
                            Worth <strong style="color:var(--text);">₱{{ number_format($loyalty['balance'] * $loyalty['value'], 2) }}</strong>
                            off a future booking.<br>
                            You earn 1 point for every ₱{{ number_format($loyalty['perPeso']) }} spent on completed work.
                            @if ($loyalty['balance'] < $loyalty['minRedeem'])
                            <br><span style="color:var(--text-muted);">{{ number_format($loyalty['minRedeem'] - $loyalty['balance']) }} more points until you can redeem.</span>
                            @else
                            <br>Points can cover up to {{ $loyalty['maxPercent'] }}% of a booking.
                            @endif
                        </div>
                    </div>

                    @if ($loyalty['history']->isNotEmpty())
                    <div style="margin-top:20px;border-top:1px solid var(--border);padding-top:14px;">
                        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:10px;">
                            History
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="apx-table" style="min-width:420px;font-size:.82rem;">
                                <tbody>
                                    @foreach ($loyalty['history'] as $row)
                                    <tr>
                                        <td style="white-space:nowrap;color:var(--text-muted);">{{ $row['when'] }}</td>
                                        <td>
                                            {{ $row['what'] }}
                                            @if ($row['expires'])
                                            <div style="font-size:.72rem;color:var(--text-muted);">Expires {{ $row['expires'] }}</div>
                                            @endif
                                        </td>
                                        <td style="text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;font-weight:700;color:{{ $row['points'] >= 0 ? '#16a34a' : 'var(--text-muted)' }};">
                                            {{ $row['points'] >= 0 ? '+' : '' }}{{ number_format($row['points']) }}
                                        </td>
                                        <td style="text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;color:var(--text-muted);">
                                            {{ number_format($row['balance']) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                <div class="rw-section-header">
                    <div class="rw-section-title"><i class="fas fa-tag"></i> Bundles</div>
                    <span class="card-badge" id="promoCount">—</span>
                </div>
                <div class="promos-grid" id="promosGrid"></div>
            </div><!-- /panel-promos -->


            <!-- MY PROFILE PANEL -->
            <div class="tab-panel" id="panel-profile">
                <style>
                    .profile-layout { display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: start; }
                    @media (max-width: 820px) { .profile-layout { grid-template-columns: 1fr; } }
                    .profile-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 28px 24px; text-align: center; position: relative; overflow: hidden; }
                    .profile-card::before { content: 'APX'; position: absolute; right: -10px; top: -10px; font-family: 'Barlow Condensed', sans-serif; font-size: 5.5rem; font-weight: 800; color: rgba(232,25,44,0.05); line-height: 1; pointer-events: none; }
                    .profile-avatar-ring { width: 88px; height: 88px; border-radius: 50%; background: linear-gradient(135deg, var(--red-dark), var(--red)); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; font-family: 'Barlow Condensed', sans-serif; font-size: 2.2rem; font-weight: 800; color: #fff; letter-spacing: 0.04em; }
                    .profile-name { font-family: 'Barlow Condensed', sans-serif; font-size: 1.35rem; font-weight: 800; letter-spacing: 0.03em; color: var(--text); margin-bottom: 4px; }
                    .profile-email { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 16px; word-break: break-all; }
                    .profile-stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border-top: 1px solid var(--border); margin-top: 20px; padding-top: 18px; }
                    .profile-stat-item { display: flex; flex-direction: column; gap: 2px; }
                    .profile-stat-val { font-family: 'Barlow Condensed', sans-serif; font-size: 1.4rem; font-weight: 800; color: var(--text); }
                    .profile-stat-lbl { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; }
                    .profile-since { font-size: 0.72rem; color: var(--text-muted); margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: center; gap: 5px; }
                    .profile-section-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 16px; }
                    .profile-section-title { font-family: 'Barlow Condensed', sans-serif; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
                    .profile-section-title i { color: var(--red); }
                    .profile-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
                    @media (max-width: 600px) { .profile-field-grid { grid-template-columns: 1fr; } }
                    .profile-field { display: flex; flex-direction: column; gap: 4px; }
                    .profile-field label { font-size: 0.72rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); }
                    .profile-field input, .profile-field select { background: var(--surface-2); border: 1px solid var(--border); color: var(--text); padding: 9px 12px; border-radius: 7px; font-size: 0.875rem; font-family: 'Barlow', sans-serif; outline: none; transition: border-color 0.2s; }
                    .profile-field input:focus, .profile-field select:focus { border-color: var(--red); }
                    .profile-field input[readonly] { opacity: 0.6; cursor: not-allowed; }
                    .profile-field-full { grid-column: 1 / -1; }
                    .profile-edit-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
                    .btn-profile-cancel { background: none; border: 1px solid var(--border); color: var(--text-muted); padding: 9px 20px; border-radius: 7px; font-size: 0.85rem; cursor: pointer; transition: color 0.2s, border-color 0.2s; }
                    .btn-profile-cancel:hover { color: var(--text); }
                    .btn-profile-save { background: var(--red); color: #fff; border: none; padding: 9px 24px; border-radius: 7px; font-family: 'Barlow Condensed', sans-serif; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; gap: 7px; transition: background 0.2s, transform 0.15s; }
                    .btn-profile-save:hover { background: var(--red-dark); transform: translateY(-1px); }
                    .profile-read-val { font-size: 0.9rem; color: var(--text); padding: 9px 0; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; }
                    .profile-read-val i { color: var(--red); font-size: 0.8rem; width: 14px; }
                    .pv-plate { font-family: monospace; font-size: 0.8rem; background: var(--surface-3); padding: 1px 7px; border-radius: 4px; }
                    .primary-badge { margin-left: 6px; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--red); white-space: nowrap; }
                    .primary-badge i { font-size: 0.55rem; color: var(--red); width: auto; }
                </style>

                <div class="profile-layout">
                    <!-- LEFT: Identity Card -->
                    <div>
                        <div class="profile-card">
                            <div class="profile-avatar-ring">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <div class="profile-name">{{ Auth::user()->name }}</div>
                            <div class="profile-email">{{ Auth::user()->email }}</div>
                            <div class="profile-stats-row">
                                <div class="profile-stat-item">
                                    <span class="profile-stat-val">{{ $completed }}</span>
                                    <span class="profile-stat-lbl">Services Done</span>
                                </div>
                                <div class="profile-stat-item">
                                    <span class="profile-stat-val">{{ $totalBookings }}</span>
                                    <span class="profile-stat-lbl">Total Bookings</span>
                                </div>
                                <div class="profile-stat-item">
                                    <span class="profile-stat-val">{{ $vehicles->count() }}</span>
                                    <span class="profile-stat-lbl">Vehicles</span>
                                </div>
                                <div class="profile-stat-item">
                                    <span class="profile-stat-val">{{ $upcoming }}</span>
                                    <span class="profile-stat-lbl">Upcoming</span>
                                </div>
                            </div>
                            <div class="profile-since"><i class="fas fa-calendar-plus" style="color:var(--red);font-size:0.7rem;"></i> Member since {{ Auth::user()->created_at->format('M Y') }}</div>
                        </div>
                    </div>

                    <!-- RIGHT: Info Forms -->
                    <div>
                        <!-- Personal Information -->
                        <div class="profile-section-card" id="profileInfoSection">
                            <div class="profile-section-title"><i class="fas fa-id-card"></i> Personal Information
                                <button onclick="toggleProfileEdit()" id="profileEditBtn" style="margin-left:auto;background:none;border:1px solid var(--border);color:var(--text-muted);padding:5px 14px;border-radius:6px;font-size:0.75rem;cursor:pointer;display:flex;align-items:center;gap:5px;transition:color 0.2s,border-color 0.2s;">
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                            </div>
                            <!-- Read view -->
                            <div id="profileReadView">
                                <div class="profile-field-grid">
                                    <div class="profile-field">
                                        <label>Full Name</label>
                                        <div class="profile-read-val" id="prv-name"><i class="fas fa-user"></i> {{ Auth::user()->name }}</div>
                                    </div>
                                    <div class="profile-field">
                                        <label>Email Address</label>
                                        <div class="profile-read-val"><i class="fas fa-envelope"></i> {{ Auth::user()->email }}</div>
                                    </div>
                                    <div class="profile-field">
                                        <label>Contact Number</label>
                                        <div class="profile-read-val" id="prv-phone"><i class="fas fa-phone"></i>
                                            @if($customer->phone ?? Auth::user()->phone)
                                                {{ $customer->phone ?? Auth::user()->phone }}
                                            @else
                                                <span style="color:var(--text-muted);font-style:italic;">Not set</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="profile-field">
                                        <label>Date of Birth</label>
                                        <div class="profile-read-val" id="prv-dob"><i class="fas fa-cake-candles"></i>
                                            @if($customer->dob ?? null)
                                                {{ $customer->dob }}
                                            @else
                                                <span style="color:var(--text-muted);font-style:italic;">Not set</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="profile-field profile-field-full">
                                        <label>Address</label>
                                        <div class="profile-read-val" id="prv-address"><i class="fas fa-location-dot"></i>
                                            @if($customer->address ?? null)
                                                {{ $customer->address }}
                                            @else
                                                <span style="color:var(--text-muted);font-style:italic;">Not set</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Edit view -->
                            <div id="profileEditView" style="display:none;">
                                <div class="profile-field-grid">
                                    <div class="profile-field">
                                        <label>Full Name</label>
                                        <input type="text" id="pef-name" value="{{ Auth::user()->name }}" />
                                    </div>
                                    <div class="profile-field">
                                        <label>Email Address</label>
                                        <input type="email" id="pef-email" value="{{ Auth::user()->email }}" readonly />
                                    </div>
                                    <div class="profile-field">
                                        <label>Contact Number</label>
                                        <input type="tel" id="pef-phone" placeholder="+63 9XX XXX XXXX" value="{{ $customer->phone ?? Auth::user()->phone ?? '' }}" />
                                    </div>
                                    <div class="profile-field">
                                        <label>Date of Birth</label>
                                        <input type="date" id="pef-dob" value="{{ $customer->dob ?? '' }}" />
                                    </div>
                                    <div class="profile-field profile-field-full">
                                        <label>Address</label>
                                        <input type="text" id="pef-address" placeholder="e.g. 123 Street, Quezon City" value="{{ $customer->address ?? '' }}" />
                                    </div>
                                </div>
                                <div class="profile-edit-actions">
                                    <button class="btn-profile-cancel" onclick="cancelProfileEdit()">Cancel</button>
                                    <button class="btn-profile-save" onclick="saveProfileInfo()"><i class="fas fa-floppy-disk"></i> Save Changes</button>
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Summary -->
                        <div class="profile-section-card">
                            <div class="profile-section-title"><i class="fas fa-car"></i> Registered Vehicles
                                <button onclick="switchSection(null,'vehicles'); renderVehicles()" style="margin-left:auto;background:none;border:1px solid var(--border);color:var(--text-muted);padding:5px 14px;border-radius:6px;font-size:0.75rem;cursor:pointer;display:flex;align-items:center;gap:5px;">
                                    <i class="fas fa-arrow-up-right-from-square"></i> Manage
                                </button>
                            </div>
                            <div id="profileVehicleSummary" style="font-size:0.85rem;color:var(--text-muted);">
                                @forelse($vehicles as $v)
                                    <div class="profile-read-val">
                                        <i class="fas fa-motorcycle"></i>
                                        {{ $v->make }} {{ $v->model }}{{ $v->year ? ' (' . $v->year . ')' : '' }} &mdash;
                                        <span class="pv-plate">{{ $v->display_plate }}</span>
                                        @if($v->is_primary)
                                            <span class="primary-badge"><i class="fas fa-star"></i> Primary</span>
                                        @endif
                                    </div>
                                @empty
                                    <div style="color:var(--text-muted);font-style:italic;font-size:0.85rem;padding:8px 0;">No vehicles registered yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /panel-profile -->

            <!-- SETTINGS PANEL -->
            <div class="tab-panel" id="panel-settings">
                <style>
                    .settings-layout { max-width: 680px; }
                    .settings-section { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 16px; }
                    .settings-section-title { font-family: 'Barlow Condensed', sans-serif; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
                    .settings-section-title i { color: var(--red); }
                    .settings-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 0; border-bottom: 1px solid var(--border); }
                    .settings-row:last-child { border-bottom: none; padding-bottom: 0; }
                    .settings-row-info { flex: 1; }
                    .settings-row-label { font-size: 0.875rem; font-weight: 600; color: var(--text); margin-bottom: 2px; }
                    .settings-row-desc { font-size: 0.77rem; color: var(--text-muted); line-height: 1.4; }
                    .settings-toggle { position: relative; width: 42px; height: 24px; flex-shrink: 0; }
                    .settings-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
                    .settings-toggle-slider { position: absolute; inset: 0; background: var(--surface-3); border: 1px solid var(--border); border-radius: 12px; cursor: pointer; transition: background 0.2s, border-color 0.2s; }
                    .settings-toggle-slider::before { content: ''; position: absolute; width: 16px; height: 16px; left: 3px; top: 3px; background: var(--text-muted); border-radius: 50%; transition: transform 0.2s, background 0.2s; }
                    .settings-toggle input:checked + .settings-toggle-slider { background: rgba(232,25,44,0.18); border-color: rgba(232,25,44,0.4); }
                    .settings-toggle input:checked + .settings-toggle-slider::before { transform: translateX(18px); background: var(--red); }
                    .settings-danger-btn { background: none; border: 1px solid rgba(232,25,44,0.35); color: var(--red); padding: 9px 20px; border-radius: 7px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 7px; transition: background 0.2s, border-color 0.2s; }
                    .settings-danger-btn:hover { background: var(--red-glow); border-color: var(--red); }
                </style>

                <div class="settings-layout">

                    <!-- Notifications -->
                    <div class="settings-section">
                        <div class="settings-section-title"><i class="fas fa-bell"></i> Notifications</div>
                        <div class="settings-row">
                            <div class="settings-row-info">
                                <div class="settings-row-label">Booking Confirmations</div>
                                <div class="settings-row-desc">Receive a notification when your booking is confirmed or updated.</div>
                            </div>
                            <label class="settings-toggle"><input type="checkbox" id="stg-notif-booking" checked /><span class="settings-toggle-slider"></span></label>
                        </div>
                        <div class="settings-row">
                            <div class="settings-row-info">
                                <div class="settings-row-label">Service Reminders</div>
                                <div class="settings-row-desc">Get reminded 24 hours before your scheduled service appointment.</div>
                            </div>
                            <label class="settings-toggle"><input type="checkbox" id="stg-notif-reminder" checked /><span class="settings-toggle-slider"></span></label>
                        </div>
                        <div class="settings-row">
                            <div class="settings-row-info">
                                <div class="settings-row-label">Promos & Offers</div>
                                <div class="settings-row-desc">Stay updated on discounts and seasonal promos.</div>
                            </div>
                            <label class="settings-toggle"><input type="checkbox" id="stg-notif-promos" /><span class="settings-toggle-slider"></span></label>
                        </div>
                    </div>

                    <!-- Security -->
                    <div class="settings-section">
                        <div class="settings-section-title"><i class="fas fa-lock"></i> Security</div>
                        <div class="settings-row">
                            <div class="settings-row-info">
                                <div class="settings-row-label">Change Password</div>
                                <div class="settings-row-desc">Update your account password regularly for security.</div>
                            </div>
                            {{-- This used to link to /forgot-password, which emails a
                                 reset link; with mail unconfigured nothing arrived, so
                                 there was no way to change a password at all. --}}
                            <button type="button" id="pwToggleBtn" onclick="togglePasswordForm()"
                                    style="background:none;border:1px solid var(--border);color:var(--text-muted);padding:7px 16px;border-radius:6px;font-size:0.8rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;">
                                <i class="fas fa-key"></i> Change
                            </button>
                        </div>

                        <div id="changePasswordForm" style="display:none;padding:4px 0 14px;">
                            <div class="m-form-group">
                                <label class="m-label">Current password</label>
                                <div class="m-input-wrap"><i class="fas fa-lock"></i>
                                    <input class="m-control" id="cpCurrent" type="password" autocomplete="current-password" />
                                </div>
                            </div>
                            <div class="m-form-group">
                                <label class="m-label">New password</label>
                                <div class="m-input-wrap"><i class="fas fa-key"></i>
                                    <input class="m-control" id="cpNew" type="password" data-pw-group="new" autocomplete="new-password" />
                                </div>
                                <div class="m-hint">At least 8 characters, with upper and lower case, a number and a symbol.</div>
                            </div>
                            <div class="m-form-group">
                                <label class="m-label">Confirm new password</label>
                                <div class="m-input-wrap"><i class="fas fa-key"></i>
                                    <input class="m-control" id="cpConfirm" type="password" data-pw-group="new" autocomplete="new-password" />
                                </div>
                            </div>
                            <button class="btn-avf-save" onclick="submitPasswordChange()">
                                <i class="fas fa-floppy-disk"></i> Update Password
                            </button>
                        </div>
                        <div class="settings-row">
                            <div class="settings-row-info">
                                <div class="settings-row-label">Active Sessions</div>
                                <div class="settings-row-desc">You are currently logged in on this device.</div>
                            </div>
                            <span style="font-size:0.75rem;color:var(--success);display:flex;align-items:center;gap:4px;"><i class="fas fa-circle" style="font-size:0.5rem;"></i> 1 active</span>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="settings-section" style="border-color:rgba(232,25,44,0.2);">
                        <div class="settings-section-title" style="color:var(--red);"><i class="fas fa-triangle-exclamation"></i> Danger Zone</div>
                        <div class="settings-row">
                            <div class="settings-row-info">
                                <div class="settings-row-label">Delete My Account</div>
                                <div class="settings-row-desc">Permanently remove your account and all associated data. This cannot be undone.</div>
                            </div>
                            <button class="settings-danger-btn" onclick="confirmDeleteAccount()"><i class="fas fa-trash"></i> Delete</button>
                        </div>
                    </div>
                </div>
            </div><!-- /panel-settings -->

            <!-- BOOKING MODAL -->
            <div class="modal-overlay" id="bookingModal" onclick="handleOverlayClick(event)">
                <div class="modal" id="modalBox">
                    <div id="modalFormView">
                        <div class="modal-header">
                            <div class="modal-header-info">
                                <div class="modal-eyebrow">New Booking</div>
                                <div class="modal-title" id="modalServiceName">Service Name</div>
                            </div>
                            <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
                        </div>
                        <div class="modal-body">
                            <div class="modal-service-chip">
                                <i class="fas fa-wrench" id="modalServiceIcon"></i>
                                <div>
                                    <div class="chip-name" id="modalServiceNameChip">—</div>
                                    <div class="chip-cat" id="modalServiceCat">—</div>
                                </div>
                            </div>

                            {{-- One booking can cover several services. The one
                                 clicked stays primary; these are added to it. --}}
                            <div class="m-form-group">
                                <label class="m-label" style="display:flex;justify-content:space-between;align-items:center;">
                                    <span>Add more services <span style="color:var(--text-muted);font-weight:400;">(optional)</span></span>
                                    <button type="button" id="extrasToggle" style="background:none;border:none;color:var(--red);font-size:.78rem;font-weight:600;cursor:pointer;">Show</button>
                                </label>
                                <div id="extrasList" style="display:none;border:1px solid var(--border);border-radius:8px;max-height:190px;overflow-y:auto;padding:4px;margin-top:6px;"></div>
                            </div>

                            <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 12px;border-radius:8px;background:var(--surface-2,rgba(0,0,0,.03));margin-bottom:14px;font-size:.84rem;">
                                <span style="color:var(--text-muted);">Total</span>
                                <span><strong id="bkTotalPrice">—</strong> <span style="color:var(--text-muted);">&middot; <span id="bkTotalDuration">—</span></span></span>
                            </div>

                            {{-- The free-only rule, said while the customer can
                                 still act on it rather than after they have
                                 picked a date and a time.

                                 Same decision and same wording as the landing
                                 page (FreeOnlyGuard + BookingPolicy), different
                                 markup: this panel is a primary service plus
                                 extras, not a multi-select grid.

                                 Styled like the points box above — translucent
                                 blue over whatever surface, text in
                                 var(--text-muted) — so it reads correctly in
                                 both themes. fa-circle-info is the v6 name,
                                 which is right here: the portal is on Font
                                 Awesome 6.3.0 while the landing page is on 5. --}}
                            <div id="bkFreeOnlyNote" role="status" style="display:none;margin-bottom:14px;padding:10px 12px;border-radius:8px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);font-size:.8rem;line-height:1.55;color:var(--text-muted);">
                                <i class="fas fa-circle-info" style="color:var(--info,#3B82F6);margin-right:6px;"></i>
                                <span id="bkFreeOnlyText"></span>
                            </div>

                            @if ($loyalty['enabled'])
                            {{-- POINTS
                                 Redemption is opt-in and the slider is only a
                                 request: the server caps it against the real
                                 balance and the maximum share of a booking
                                 points may cover, so nothing here can be
                                 nudged into spending points that do not
                                 exist. Informational, so blue. --}}
                            <div id="bkPointsBox" style="margin-top:10px;padding:10px 12px;border-radius:8px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);font-size:.8rem;line-height:1.55;color:var(--text-muted);">
                                @if ($loyalty['balance'] >= $loyalty['minRedeem'] && $loyalty['minRedeem'] > 0)
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                    <input type="checkbox" id="bkUsePoints" onchange="refreshBookingTotal()" />
                                    <span>Use my points — <strong id="bkPointsApplied">0</strong> available</span>
                                </label>
                                <div id="bkPointsDetail" style="margin-top:4px;"></div>
                                @else
                                <i class="fas fa-circle-info" style="color:var(--info,#3B82F6);"></i>
                                <span id="bkPointsEarn"></span>
                                @endif
                            </div>
                            @endif
                            <div class="m-form-group">
                                <label class="m-label">Preferred Date &amp; Time</label>
                                <input type="hidden" id="mDate" required />
                                <input type="hidden" id="mTime" required />
                                <x-availability-picker id="cdAvp" />
                            </div>
                            <div class="m-form-group">
                                <label class="m-label">Contact Number</label>
                                <div class="m-input-wrap"><i class="fas fa-phone"></i><input class="m-control" id="mPhone" type="tel" placeholder="+63 9XX XXX XXXX" /></div>
                                <div class="m-vehicle-hint" id="mPhoneHint" style="display:none;">
                                    <i class="fas fa-circle-info" style="font-size:0.68rem;color:var(--red);"></i>
                                    No number saved. <a onclick="switchSection(null,'profile'); closeModal()">Edit number in My Profile</a>
                                </div>
                            </div>
                            <div class="m-form-group">
                                <label class="m-label">Select Vehicle</label>
                                <div class="m-input-wrap"><i class="fas fa-car"></i>
                                    <select class="m-control m-select" id="mVehicleSelect">
                                        <option value="">— Choose a registered vehicle —</option>
                                    </select>
                                </div>
                                <div class="m-vehicle-hint">
                                    <i class="fas fa-circle-info" style="font-size:0.68rem;color:var(--red);"></i>
                                    Vehicle not listed? <a onclick="switchSection(null,'vehicles'); closeModal()">Add it in My Vehicles</a>
                                </div>
                            </div>
                            <div class="m-form-group">
                                <label class="m-label">Special Notes / Instructions</label>
                                <textarea class="m-control" id="mNotes" placeholder="Any specific concerns or instructions for the technician…"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                            <button class="btn-modal-submit" onclick="submitBooking()"><i class="fas fa-calendar-check"></i> Confirm Booking</button>
                        </div>
                    </div>
                    <div class="modal-success" id="modalSuccessView">
                        <div class="success-circle"><i class="fas fa-check"></i></div>
                        <h3>Booking Confirmed!</h3>
                        <p>Your appointment has been submitted. We'll send a confirmation once it's approved.</p>
                        <div class="ref" id="modalRefNo">—</div>
                        <p style="font-size:0.78rem;">
                            <i class="fas fa-calendar" style="color:var(--red);margin-right:4px;"></i>
                            <span id="modalSuccessDate">—</span>
                            &nbsp;·&nbsp;
                            <i class="fas fa-wrench" style="color:var(--red);margin-right:4px;"></i>
                            <span id="modalSuccessService">—</span>
                        </p>
                        <div style="display:flex;gap:10px;margin-top:8px;width:100%;">
                            <button class="btn-modal-cancel" style="flex:1;" onclick="closeModal()">Close</button>
                            <button class="btn-modal-submit" style="flex:2;" onclick="switchSection(null,'bookings'); filterBookings('upcoming'); closeModal()">
                                <i class="fas fa-list"></i> View My Bookings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <footer>
                <div class="footer-brand"><span>APX</span> AutoMai &mdash; Customer Portal &copy; {{ date('Y') }}</div>
                <div><a href="{{ route('privacy') }}" target="_blank">Privacy Policy</a> &nbsp;·&nbsp; <a href="{{ route('terms') }}" target="_blank">Terms &amp; Conditions</a></div>
            </footer>
        </main>
    </div>
</div>

<!-- DELETE ACCOUNT CONFIRMATION -->
<div class="modal-overlay" id="deleteAccountModal" onclick="if(event.target===this) closeDeleteAccountModal()">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);">
                <i class="fas fa-triangle-exclamation" style="margin-right:8px;"></i>Confirm Deletion
            </div>
            <button class="modal-close" type="button" onclick="closeDeleteAccountModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:0.86rem;line-height:1.55;margin:0 0 14px;">
                Enter your password to confirm. Your account will be deactivated and you'll be signed out &mdash; you can restore it by logging in again within 30 days.
            </p>
            <div class="m-form-group">
                <label class="m-label">Password</label>
                <div class="m-input-wrap">
                    <i class="fas fa-lock"></i>
                    <input class="m-control" id="deleteAccountPassword" type="password" placeholder="Your password" />
                </div>
            </div>
        </div>
        <div class="modal-footer" style="display:flex;gap:10px;justify-content:flex-end;padding:14px 20px;border-top:1px solid var(--border);">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteAccountModal()">Cancel</button>
            <button type="button" class="settings-danger-btn" id="deleteAccountConfirmBtn" onclick="submitDeleteAccount()">
                <i class="fas fa-trash"></i> Delete My Account
            </button>
        </div>
    </div>
</div>
<!-- APX CUSTOM DIALOG MODAL -->
<div id="apxDialog" style="display:none;position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.65);backdrop-filter:blur(4px);">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:32px 28px 24px;max-width:420px;width:90%;box-shadow:0 24px 64px rgba(0,0,0,0.4);animation:modalIn 0.2s ease both;position:relative;">
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;">
            <div id="apxDialogIcon" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;"></div>
            <div style="flex:1;">
                <div id="apxDialogTitle" style="font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:800;letter-spacing:0.03em;color:var(--text);margin-bottom:6px;"></div>
                <div id="apxDialogMsg" style="font-size:0.85rem;color:var(--text-muted);line-height:1.5;"></div>
            </div>
        </div>
        <div id="apxDialogBtns" style="display:flex;gap:10px;justify-content:flex-end;"></div>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="{{ asset('assets/js/availability-picker.js') }}"></script>
<script src="{{ asset('assets/js/form-validate.js') }}"></script>
<script src="{{ asset('assets/js/plate-input.js') }}"></script>
<script src="{{ asset('assets/js/password-toggle.js') }}"></script>
<script src="{{ asset('assets/js/live-refresh.js') }}"></script>
<script src="{{ asset('assets/js/notification-bell.js') }}"></script>
<script src="{{ asset('assets/js/free-only-guard.js') }}"></script>
<script>
    // Vehicle and booking fields are user-supplied and get interpolated into
    // innerHTML below, so escape them first.
    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
    }
    // ── APX CUSTOM DIALOG ──────────────────────────────────────────────────
    function apxDialog({ title, msg, type = 'info', confirmLabel = 'OK', cancelLabel = null, onConfirm = null, onCancel = null }) {
        const el      = document.getElementById('apxDialog');
        const iconEl  = document.getElementById('apxDialogIcon');
        const titleEl = document.getElementById('apxDialogTitle');
        const msgEl   = document.getElementById('apxDialogMsg');
        const btnsEl  = document.getElementById('apxDialogBtns');

        const styles = {
            danger:  { bg: 'rgba(232,25,44,0.12)',  border: 'rgba(232,25,44,0.3)',  color: '#E8192C',  icon: 'fa-triangle-exclamation' },
            warning: { bg: 'rgba(245,158,11,0.12)', border: 'rgba(245,158,11,0.3)', color: '#f59e0b',  icon: 'fa-circle-exclamation' },
            success: { bg: 'rgba(34,197,94,0.12)',  border: 'rgba(34,197,94,0.3)',  color: '#22c55e',  icon: 'fa-circle-check' },
            info:    { bg: 'rgba(59,130,246,0.12)', border: 'rgba(59,130,246,0.3)', color: '#3b82f6',  icon: 'fa-circle-info' },
        };
        const s = styles[type] || styles.info;

        iconEl.style.background   = s.bg;
        iconEl.style.border       = '1px solid ' + s.border;
        iconEl.style.color        = s.color;
        iconEl.innerHTML          = `<i class="fas ${s.icon}"></i>`;
        titleEl.textContent       = title;
        msgEl.innerHTML           = msg;

        btnsEl.innerHTML = '';

        if (cancelLabel) {
            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = cancelLabel;
            cancelBtn.style.cssText = 'background:none;border:1px solid var(--border);color:var(--text-muted);padding:9px 20px;border-radius:7px;font-size:0.85rem;cursor:pointer;transition:color 0.2s;';
            cancelBtn.onmouseover = () => cancelBtn.style.color = 'var(--text)';
            cancelBtn.onmouseout  = () => cancelBtn.style.color = 'var(--text-muted)';
            cancelBtn.onclick = () => { closeApxDialog(); if (onCancel) onCancel(); };
            btnsEl.appendChild(cancelBtn);
        }

        const confirmBtn = document.createElement('button');
        confirmBtn.innerHTML = confirmLabel;
        const iDanger = type === 'danger' || type === 'warning';
        confirmBtn.style.cssText = `background:${iDanger ? '#E8192C' : s.color};color:#fff;border:none;padding:9px 22px;border-radius:7px;font-family:'Barlow Condensed',sans-serif;font-size:0.9rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;cursor:pointer;transition:opacity 0.2s;`;
        confirmBtn.onmouseover = () => confirmBtn.style.opacity = '0.88';
        confirmBtn.onmouseout  = () => confirmBtn.style.opacity = '1';
        confirmBtn.onclick = () => { closeApxDialog(); if (onConfirm) onConfirm(); };
        btnsEl.appendChild(confirmBtn);

        el.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeApxDialog() {
        document.getElementById('apxDialog').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Click-outside to dismiss info/success dialogs only
    document.getElementById('apxDialog').addEventListener('click', function(e) {
        if (e.target === this) closeApxDialog();
    });

    // DATA FROM DB
    let MY_VEHICLES = {!! json_encode($vehiclesJs) !!};
const BOOKINGS = {!! json_encode($bookingsJs) !!};

    {{-- One wording for the free-only rule, taken from the constant the server
         refuses with, exactly as the landing page does. A second copy here
         would be free to drift from the message the customer ends up seeing. --}}
    FreeOnlyGuard.configure(@json(\App\Support\BookingPolicy::FREE_ONLY_MESSAGE));

    {{-- SERVICES built from the database — dbId is the real services.id so bookings always use the correct service --}}
    const SERVICES = (function() {
        const META_MAP = [
            { match: /change oil/i,                   cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-oil-can',            duration:'30–45 min', free:false },
            { match: /fuel injection/i,               cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-gas-pump',           duration:'45–60 min', free:false },
            { match: /throttle body/i,                cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-wind',               duration:'30–45 min', free:false },
            { match: /throttle idle/i,                cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-sliders',            duration:'20–30 min', free:false },
            { match: /valve clearance|tune.?up/i,     cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-screwdriver-wrench', duration:'60–90 min', free:false },
            { match: /cvt.*clean|cvt.*inspect/i,      cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-gear',               duration:'60–90 min', free:false },
            { match: /air filter.*install/i,          cat:'inspection', catLabel:'Inspection',         icon:'fa-filter',             duration:'20 min',    free:false },
            { match: /air filter/i,                   cat:'inspection', catLabel:'Inspection',         icon:'fa-filter',             duration:'15 min',    free:false },
            { match: /flyball.*clean/i,               cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-dot',         duration:'30–45 min', free:false },
            { match: /flyball/i,                      cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-dot',         duration:'30 min',    free:false },
            { match: /v.?belt.*clean/i,               cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-bezier-curve',       duration:'20–30 min', free:false },
            { match: /v.?belt/i,                      cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-bezier-curve',       duration:'20 min',    free:false },
            { match: /pulley.*shav/i,                 cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-screwdriver',        duration:'60–90 min', free:false },
            { match: /pulley.*clean/i,                cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-half-stroke', duration:'30–45 min', free:false },
            { match: /pulley/i,                       cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-half-stroke', duration:'30 min',    free:false },
            { match: /torque.*greas/i,                cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-rotate',             duration:'20–30 min', free:false },
            { match: /torque.*clean/i,                cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-rotate',             duration:'30–45 min', free:false },
            { match: /torque/i,                       cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-rotate',             duration:'30 min',    free:false },
            { match: /clutch.*clean/i,                cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-xmark',       duration:'30–45 min', free:false },
            { match: /clutch/i,                       cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-xmark',       duration:'30 min',    free:false },
            { match: /kick.?start/i,                  cat:'inspection', catLabel:'Inspection',         icon:'fa-person-walking',     duration:'20 min',    free:false },
            { match: /sprocket|chain/i,               cat:'inspection', catLabel:'Inspection',         icon:'fa-link',               duration:'30–45 min', free:false },
            { match: /pipe/i,                         cat:'brakes',     catLabel:'Brakes & Pipes',     icon:'fa-pipe',               duration:'30 min',    free:false },
            { match: /brake.*adjust/i,                cat:'brakes',     catLabel:'Brakes & Pipes',     icon:'fa-circle-stop',        duration:'20–30 min', free:false },
            { match: /brake/i,                        cat:'brakes',     catLabel:'Brakes & Pipes',     icon:'fa-circle-stop',        duration:'30–45 min', free:false },
            { match: /ecu|diagnos/i,                  cat:'free',       catLabel:'Free Service',       icon:'fa-microchip',          duration:'15–30 min', free:true  },
            { match: /free.*inspect|basic.*inspect/i, cat:'free',       catLabel:'Free Service',       icon:'fa-clipboard-check',    duration:'20–30 min', free:true  },
        ];
        const defaults = { cat:'inspection', catLabel:'Inspection', icon:'fa-wrench', duration:'30–60 min', free:false };
        const dbServices = {!! json_encode($services->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'desc'=>$s->description??'','duration'=>$s->duration,'price'=>(float)$s->price,'vehicle'=>$s->vehicle_type])->values()) !!};
        return dbServices.map((s, i) => {
            const meta = META_MAP.find(m => m.match.test(s.name)) || defaults;
            let dur = meta.duration;
            if (s.duration) {
                const d = parseInt(s.duration);
                dur = d < 60 ? d + ' min' : (Math.floor(d/60) + 'h' + (d%60 ? ' ' + d%60 + 'min' : ''));
            }
            return { id:'svc-'+String(i+1).padStart(2,'0'), dbId:s.id, name:s.name, cat:meta.cat, catLabel:meta.catLabel, icon:meta.icon, desc:s.desc||meta.catLabel+' service.', duration:dur, free:meta.free,
                     // Raw values, for summing across several services.
                     // null means every vehicle, same convention as bundles.
                     vehicle:s.vehicle ?? null,
                     priceValue:s.price||0, durationValue:parseInt(s.duration)||0,
                     price:(s.price ? '₱'+Number(s.price).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}) : 'Free') };
        });
    })();

    const STATUS_META = {
        upcoming:    { label:'Upcoming',    cls:'badge-confirmed',  icon:'fa-clock' },
        in_progress: { label:'In Progress', cls:'badge-inprogress', icon:'fa-rotate' },
        completed:   { label:'Completed',   cls:'badge-completed',  icon:'fa-circle-check' },
        cancelled:   { label:'Cancelled',   cls:'badge-cancelled',  icon:'fa-ban' },
    };

    let currentFilter    = 'all';
    let currentSearch    = '';
    let currentCat       = 'all';
    let currentSvcSearch = '';

    // ── Services view toggle (tiles / list), persisted per-browser ──────────
    function setServicesView(mode) {
        const grid = document.getElementById('servicesGrid');
        grid.classList.toggle('view-list', mode === 'list');
        document.getElementById('viewToggleTiles').classList.toggle('active', mode !== 'list');
        document.getElementById('viewToggleList').classList.toggle('active', mode === 'list');
        try { localStorage.setItem('apx-services-view', mode); } catch (e) {}
    }
    let storedServicesView = 'tiles';
    try { storedServicesView = localStorage.getItem('apx-services-view') || 'tiles'; } catch (e) {}
    setServicesView(storedServicesView);

    const PAGE_TITLES = {
        dashboard: ['MY <span>DASHBOARD</span>',  'Dashboard'],
        bookings:  ['MY <span>BOOKINGS</span>',    'My Bookings'],
        services:  ['BOOK A <span>SERVICE</span>', 'Book a Service'],
        vehicles:  ['MY <span>VEHICLES</span>',    'My Vehicles'],
        promos:    ['PROMOS &amp; <span>OFFERS</span>', 'Promos & Offers'],
        profile:   ['MY <span>PROFILE</span>',     'My Profile'],
        settings:  ['ACCOUNT <span>SETTINGS</span>', 'Settings'],
    };

    function switchSection(e, section) {
        if (e) e.preventDefault();
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('panel-' + section).classList.add('active');
        document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
        const secTab = document.getElementById('tab-' + section);
        if (secTab) secTab.classList.add('active');
        document.querySelectorAll('.nav-link[id^="nav-"]').forEach(l => l.classList.remove('active'));
        const navLink = document.getElementById('nav-' + section);
        if (navLink) navLink.classList.add('active');
        document.getElementById('pageTitle').innerHTML = PAGE_TITLES[section][0];
        document.getElementById('pageBreadcrumb').textContent = PAGE_TITLES[section][1];
        if (section === 'vehicles') renderVehicles();
    }

    function filterBookings(filter) {
        currentFilter = filter;
        document.querySelectorAll('.filter-tab').forEach(t =>
            t.classList.toggle('active', t.dataset.filter === filter));
        renderBookings();
    }

    function searchBookings(val) {
        currentSearch = val.toLowerCase();
        renderBookings();
    }

    function renderBookings() {
        const list  = document.getElementById('bookingCardsList');
        const empty = document.getElementById('bookingEmpty');
        let filtered = BOOKINGS.filter(b => {
            const mf = currentFilter === 'all' || b.status === currentFilter;
            const ms = !currentSearch ||
                b.service.toLowerCase().includes(currentSearch) ||
                b.id.toLowerCase().includes(currentSearch) ||
                b.staff.toLowerCase().includes(currentSearch) ||
                b.vehicle.toLowerCase().includes(currentSearch);
            return mf && ms;
        });
        if (!filtered.length) { list.innerHTML = ''; empty.style.display = 'block'; return; }
        empty.style.display = 'none';
        // Fix 2: Show Cancel Booking button for upcoming/in-progress bookings
        list.innerHTML = filtered.map(b => {
            const meta = STATUS_META[b.status] || STATUS_META['upcoming'];
            const canCancel = b.status === 'upcoming' || b.status === 'in_progress';
            const cancelBtn = canCancel
                ? `<div style="margin-top:8px;"><button onclick="cancelBooking(${b.dbId})" style="background:none;border:1px solid rgba(232,25,44,0.4);color:var(--red);padding:5px 14px;border-radius:6px;font-size:0.75rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:background 0.2s;" onmouseover="this.style.background='var(--red-glow)'" onmouseout="this.style.background='none'"><i class="fas fa-ban"></i> Cancel Booking</button></div>`
                : '';

            // Cancelled bookings keep all their details; this adds why.
            const cancelNote = (b.status === 'cancelled' && (b.cancelReason || b.cancelledAt))
                ? `<div style="margin-top:8px;padding:8px 11px;border-radius:7px;border:1px solid rgba(232,25,44,0.35);border-left-width:3px;background:var(--red-glow);">
                       <div style="font-size:.7rem;font-weight:700;color:var(--red);text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Cancellation</div>
                       <div style="font-size:.78rem;">${esc(b.cancelReason || 'No reason recorded')}</div>
                       ${b.cancelledAt ? `<div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">${esc(b.cancelledAt)}</div>` : ''}
                   </div>`
                : '';

            return `<div class="bk-card">
                <div class="bk-card-inner">
                    <div class="bk-date-col">
                        <span class="bk-day">${b.day}</span>
                        <span class="bk-mon">${b.mon}</span>
                        <span class="bk-yr">${b.yr}</span>
                    </div>
                    <div class="bk-body">
                        <div class="bk-title-row">
                            <span class="bk-service">${esc(b.service)}</span>
                            <span class="bk-id">${b.id}</span>
                        </div>
                        <div class="bk-meta">
                            <span><i class="fas fa-clock"></i>${b.time}</span>
                            <span><i class="fas fa-user"></i>${esc(b.staff)}</span>
                            <span><i class="fas fa-car"></i>${esc(b.vehicle)}</span>
                        </div>
                        ${cancelBtn}
                        ${cancelNote}
                    </div>
                    <div class="bk-status-col">
                        <span class="badge ${meta.cls}"><i class="fas ${meta.icon}"></i>${meta.label}</span>
                        <span class="bk-amount">${b.amount}</span>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function cancelBooking(bookingDbId) {
        apxDialog({
            type: 'danger', title: 'Cancel Booking',
            msg: 'Are you sure you want to cancel this booking? This action <strong>cannot be undone</strong>.',
            confirmLabel: '<i class="fas fa-ban" style="margin-right:5px;"></i> Yes, Cancel It',
            cancelLabel: 'Keep Booking',
            onConfirm: () => {
                fetch(`/customer/bookings/${bookingDbId}/cancel`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const booking = BOOKINGS.find(b => b.dbId === bookingDbId);
                        if (booking) booking.status = 'cancelled';
                        const cancelledCount = BOOKINGS.filter(b => b.status === 'cancelled').length;
                        const cntEl = document.getElementById('cnt-cancelled');
                        if (cntEl) cntEl.textContent = cancelledCount;
                        renderBookings();
                        apxDialog({ type: 'success', title: 'Booking Cancelled', msg: 'Your booking has been cancelled successfully.', confirmLabel: 'OK' });
                    } else {
                        apxDialog({ type: 'danger', title: 'Error', msg: data.message || 'Failed to cancel booking. Please try again.', confirmLabel: 'OK' });
                    }
                })
                .catch(() => apxDialog({ type: 'danger', title: 'Error', msg: 'Could not connect to the server. Please try again.', confirmLabel: 'OK' }));
            }
        });
    }

    function setCat(btn) {
        document.querySelectorAll('.cat-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCat = btn.dataset.cat;
        filterServices();
    }

    function filterServices() {
        currentSvcSearch = document.getElementById('serviceSearch').value.toLowerCase();
        renderBundleStrip();
        const grid = document.getElementById('servicesGrid');
        const filtered = SERVICES.filter(s => {
            // Only what at least one of their own vehicles could actually have.
            // A customer with nothing but motorcycles is not offered an aircon
            // service. With no vehicles registered yet nothing is narrowed —
            // they are browsing, and the server checks again at booking.
            const matchVehicle = !s.vehicle
                || !MY_VEHICLES.length
                || MY_VEHICLES.some(v => (v.type || 'car') === s.vehicle);
            const matchCat    = currentCat === 'all' || s.cat === currentCat;
            const matchSearch = !currentSvcSearch ||
                s.name.toLowerCase().includes(currentSvcSearch) ||
                s.desc.toLowerCase().includes(currentSvcSearch) ||
                s.catLabel.toLowerCase().includes(currentSvcSearch);
            return matchVehicle && matchCat && matchSearch;
        });
        document.getElementById('serviceCount').textContent = filtered.length;
        if (!filtered.length) {
            grid.innerHTML = `<div class="services-empty"><i class="fas fa-magnifying-glass"></i><p>No services match your search.</p></div>`;
            return;
        }
        grid.innerHTML = filtered.map(s => `
            <div class="service-card" onclick="openModal('${s.id}')">
                <div class="service-img">
                    <div class="service-img-overlay"></div>
                    <i class="fas ${s.icon} service-img-icon"></i>
                    <span class="service-cat-badge">${s.catLabel}</span>
                    ${s.free ? '<span class="service-free-badge">FREE</span>' : ''}
                </div>
                <div class="service-info">
                    <div class="service-name">${s.name}</div>
                    <div class="service-desc">${s.desc}</div>
                    <div class="service-card-footer">
                        <span class="service-duration"><i class="fas fa-clock"></i>${s.duration}</span>
                        <button class="btn-book-service" onclick="event.stopPropagation(); openModal('${s.id}')">
                            <i class="fas fa-calendar-plus"></i> Book
                        </button>
                    </div>
                </div>
            </div>`).join('');
    }

    let selectedService = null;
    // Set while booking a bundle; cleared whenever a plain service modal
    // opens, so the two can never be sent together.
    let selectedBundle  = null;

    // Extra services ticked alongside the primary one.
    function extraBoxes() {
        return Array.from(document.querySelectorAll('#extrasList input[type="checkbox"]'));
    }

    function chosenServiceIds() {
        if (!selectedService) return [];
        return [selectedService.dbId].concat(
            extraBoxes().filter(b => b.checked).map(b => parseInt(b.value, 10))
        );
    }

    function renderExtras() {
        const list = document.getElementById('extrasList');
        if (!list || !selectedService) return;

        list.innerHTML = SERVICES
            .filter(s => s.dbId !== selectedService.dbId)
            // Same rule the Services tab applies: only work at least one of
            // their own vehicles could actually have. Without this the tab
            // narrowed by vehicle but this list did not, so a customer with
            // nothing but motorcycles could tick a car-only extra here, fill
            // in a date and a time, and be refused at submit — the exact late
            // failure the rest of this panel now avoids.
            .filter(s => !s.vehicle
                || !MY_VEHICLES.length
                || MY_VEHICLES.some(v => (v.type || 'car') === s.vehicle))
            .map(s => `<label style="display:flex;align-items:center;gap:9px;padding:6px 9px;border-radius:6px;cursor:pointer;font-size:.82rem;">
                    <input type="checkbox" value="${s.dbId}" data-price="${s.priceValue ?? 0}" data-duration="${s.durationValue ?? 0}">
                    <span style="flex:1;">${esc(s.name)}</span>
                    <span style="color:var(--text-muted);white-space:nowrap;">${esc(s.price)}</span>
                </label>`)
            .join('');

        extraBoxes().forEach(b => b.addEventListener('change', () => {
            refreshBookingTotal();
            if (typeof bookingPicker !== 'undefined') bookingPicker.refresh();
        }));
    }

    document.addEventListener('DOMContentLoaded', function () {
        const t = document.getElementById('extrasToggle');
        if (!t) return;
        t.addEventListener('click', function () {
            const list = document.getElementById('extrasList');
            const open = list.style.display !== 'none';
            list.style.display = open ? 'none' : 'block';
            t.textContent = open ? 'Show' : 'Hide';
        });
    });

    const LOYALTY = @json($loyalty ?? ['enabled' => false]);

    const peso = n => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    /* How many points this booking could take, mirroring the server's rules.
       Only ever a preview: BookingPricing recomputes all of it and caps the
       request against the real balance, so a tampered page cannot spend
       points that are not there. */
    /* The booking's price before points, which is what the usable-points
       cap is calculated against. */
    function currentBookingTotal() {
        if (selectedBundle) return Number(selectedBundle.price);
        if (!selectedService) return 0;

        return (selectedService.priceValue ?? 0)
            + extraBoxes().filter(b => b.checked).reduce((t, b) => t + parseFloat(b.dataset.price || 0), 0);
    }

    function pointsUsable(total) {
        if (!LOYALTY.enabled || LOYALTY.balance < LOYALTY.minRedeem || LOYALTY.value <= 0) return 0;
        const cashCap = total * LOYALTY.maxPercent / 100;
        return Math.max(0, Math.min(LOYALTY.balance, Math.floor(cashCap / LOYALTY.value)));
    }

    function applyPointsPreview(total) {
        if (!LOYALTY.enabled) return total;

        const usingBox = document.getElementById('bkUsePoints');
        const detail   = document.getElementById('bkPointsDetail');
        const earnLine = document.getElementById('bkPointsEarn');
        const usable   = pointsUsable(total);

        if (document.getElementById('bkPointsApplied')) {
            document.getElementById('bkPointsApplied').textContent = Number(LOYALTY.balance).toLocaleString();
        }

        let final = total;

        if (usingBox && usingBox.checked && usable > 0) {
            final = Math.max(0, total - usable * LOYALTY.value);
            if (detail) {
                detail.innerHTML = 'Using <strong>' + usable.toLocaleString() + '</strong> points — '
                    + peso(usable * LOYALTY.value) + ' off.';
            }
        } else if (detail) {
            detail.textContent = usable > 0
                ? 'Up to ' + usable.toLocaleString() + ' points can be used on this booking ('
                  + peso(usable * LOYALTY.value) + ' off).'
                : '';
        }

        // What this booking will earn once it is completed — on what is
        // actually paid, which is what the server awards.
        const willEarn = LOYALTY.perPeso > 0 ? Math.floor(final / LOYALTY.perPeso) : 0;
        const earnText = 'You will earn ' + willEarn.toLocaleString() + ' point'
            + (willEarn === 1 ? '' : 's') + ' when this booking is completed.';

        if (earnLine) earnLine.textContent = earnText;
        if (detail && detail.innerHTML) detail.innerHTML += '<br>' + earnText;
        else if (detail) detail.textContent = earnText;

        return final;
    }

    /* Tell the customer the moment the selection becomes unbookable.

       Shares FreeOnlyGuard and BookingPolicy::FREE_ONLY_MESSAGE with the
       landing page; the markup is this panel's own, because the two screens
       are nothing alike.

       Feedback only. Customer\DashboardController runs the same rule on the
       request and is what actually refuses — this only means the customer
       finds out now instead of after choosing a date and a time. */
    function refreshFreeOnly() {
        const note = document.getElementById('bkFreeOnlyNote');
        const text = document.getElementById('bkFreeOnlyText');
        const submit = document.querySelector('#bookingModal .btn-modal-submit');
        if (!note || !submit) return;

        // A bundle is sold at its own price, so it is paid work whatever it
        // contains — the guard is told about it and lets it through, matching
        // the server, which skips this rule entirely for a bundle.
        let prices = [];
        if (!selectedBundle && selectedService) {
            prices = [selectedService.priceValue ?? 0].concat(
                extraBoxes().filter(b => b.checked).map(b => parseFloat(b.dataset.price || 0))
            );
        }

        const blocked = FreeOnlyGuard.blocks(
            FreeOnlyGuard.evaluate(prices, !!selectedBundle)
        );

        text.textContent = FreeOnlyGuard.message;
        note.style.display = blocked ? '' : 'none';
        submit.disabled = blocked;
        submit.style.opacity = blocked ? '0.45' : '';
        submit.style.cursor = blocked ? 'not-allowed' : '';

        // The instruction is "add another service", and the list to do that
        // with is collapsed behind a Show toggle. Open it, so the fix is one
        // click away rather than a hunt.
        if (blocked) {
            const list = document.getElementById('extrasList');
            const toggle = document.getElementById('extrasToggle');
            if (list && list.style.display === 'none') {
                list.style.display = 'block';
                if (toggle) toggle.textContent = 'Hide';
            }
        }
    }

    function refreshBookingTotal() {
        // Before the bundle branch below returns, so a bundle still clears a
        // notice left over from a previous selection.
        refreshFreeOnly();

        if (selectedBundle) {
            const total = applyPointsPreview(Number(selectedBundle.price));
            document.getElementById('bkTotalPrice').textContent = peso(total);
            document.getElementById('bkTotalDuration').textContent = selectedBundle.duration + ' min';
            return;
        }

        if (!selectedService) return;

        const price = (selectedService.priceValue ?? 0)
            + extraBoxes().filter(b => b.checked).reduce((t, b) => t + parseFloat(b.dataset.price || 0), 0);
        const mins = (selectedService.durationValue ?? 0)
            + extraBoxes().filter(b => b.checked).reduce((t, b) => t + parseInt(b.dataset.duration || 0, 10), 0);

        document.getElementById('bkTotalPrice').textContent = peso(applyPointsPreview(price));
        document.getElementById('bkTotalDuration').textContent = mins + ' min';
    }

    const bookingPicker = new AvailabilityPicker({
        root: '#cdAvp',
        dateInput: document.getElementById('mDate'),
        timeInput: document.getElementById('mTime'),
        // Every chosen service counts toward how long the bay is held.
        getServiceIds: () => chosenServiceIds(),
    });

    function openModal(svcId, keepBundle) {
        selectedService = SERVICES.find(s => s.id === svcId);
        if (!selectedService) return;
        // Opening a plain service drops any bundle, unless bookBundle is the
        // caller and is about to recast this modal as one.
        if (!keepBundle) {
            selectedBundle = null;
            var ew = document.getElementById('extrasList');
            if (ew && ew.parentElement) { ew.parentElement.style.display = ''; }
        }
        document.getElementById('modalFormView').style.display = 'block';
        document.getElementById('modalSuccessView').classList.remove('show');
        document.getElementById('modalServiceName').textContent     = selectedService.name;
        document.getElementById('modalServiceNameChip').textContent = selectedService.name;
        document.getElementById('modalServiceCat').textContent      = selectedService.catLabel + ' · ' + selectedService.duration;
        document.getElementById('modalServiceIcon').className       = 'fas ' + selectedService.icon;

        renderExtras();
        refreshBookingTotal();
        document.getElementById('extrasList').style.display = 'none';
        document.getElementById('extrasToggle').textContent = 'Show';
        bookingPicker.reset();
        document.getElementById('mPhone').value = '';
        document.getElementById('mNotes').value = '';
        // Auto-fill phone from profile; show hint if not set
        const savedPhone = '{{ $customer->phone ?? Auth::user()->phone ?? '' }}';
        document.getElementById('mPhone').value = savedPhone;
        const phoneHint = document.getElementById('mPhoneHint');
        if (phoneHint) phoneHint.style.display = savedPhone ? 'none' : 'flex';
        const sel = document.getElementById('mVehicleSelect');
        sel.innerHTML = '<option value="">— Choose a registered vehicle —</option>';
        MY_VEHICLES.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.id;
            opt.textContent = v.make + (v.year ? ' (' + v.year + ')' : '') + (v.plate ? ' — ' + v.plate : '');
            if (v.primary) opt.selected = true;
            sel.appendChild(opt);
        });
        if (!MY_VEHICLES.length) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No vehicles registered — add one in My Vehicles';
            sel.appendChild(opt);
        }

        // The service is chosen before the vehicle here, so a customer with
        // both a car and a bike can pick car-only work and then select the
        // bike. Say so at that moment rather than letting them fill in the
        // date, the time and the notes first and be refused at the end.
        sel.onchange = function () {
            const chosen = MY_VEHICLES.find(v => String(v.id) === String(sel.value));
            if (!chosen) return;

            const type = chosen.type || 'car';

            // The extras list is filtered to what SOME vehicle of theirs could
            // have, which is all it can know before a vehicle is picked. A
            // customer with both a car and a bike is still offered car-only
            // work, so the check has to run again here, over everything that
            // is actually on the booking — not just the service they opened.
            const picked = [selectedService].concat(
                extraBoxes()
                    .filter(b => b.checked)
                    .map(b => SERVICES.find(s => String(s.dbId) === String(b.value)))
            ).filter(Boolean);

            const offending = picked.filter(s => s.vehicle && s.vehicle !== type);
            if (!offending.length) return;

            // Names the work rather than the rule, so they know what to drop —
            // the same shape as BookingPolicy::wrongVehicleMessage, which is
            // what the server would say if this were posted anyway.
            const names = offending.map(s => s.name).join(', ');

            apxDialog({
                type: 'warning',
                title: 'Not offered for that vehicle',
                msg: names + (offending.length === 1 ? ' is' : ' are')
                     + ' only offered for '
                     + (offending[0].vehicle === 'motorcycle' ? 'motorcycles' : 'cars')
                     + '. Please choose a different vehicle, or remove '
                     + (offending.length === 1 ? 'it' : 'them') + '.',
                confirmLabel: 'OK',
            });
        };
        document.getElementById('bookingModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('bookingModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    function handleOverlayClick(e) {
        if (e.target === document.getElementById('bookingModal')) closeModal();
    }

    function submitBooking() {
        // The disabled button is the visible half; this is the half that still
        // holds if it is re-enabled from the console or the selection changes
        // between render and click. The notice above is already explaining it,
        // so nothing is popped up here.
        refreshFreeOnly();
        if (document.querySelector('#bookingModal .btn-modal-submit')?.disabled) {
            return;
        }

        const date       = document.getElementById('mDate').value;
        const time       = document.getElementById('mTime').value;
        const vehicleRaw = document.getElementById('mVehicleSelect').value;
        const vehicleId  = vehicleRaw ? parseInt(vehicleRaw) : null;
        const notes      = document.getElementById('mNotes').value;

        if (!date || !time) {
            const avp = document.getElementById('cdAvp');
            avp.style.outline = '2px solid var(--red)';
            setTimeout(() => avp.style.outline = '', 1500);
            return;
        }

        const submitBtn = document.querySelector('.btn-modal-submit');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…'; }

        fetch('/customer/bookings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                service_ids:  chosenServiceIds(),
                bundle_id:    selectedBundle ? selectedBundle.id : null,
                // A request, not an instruction: the server caps it.
                points:       (document.getElementById('bkUsePoints')?.checked)
                                  ? pointsUsable(currentBookingTotal()) : 0,
                vehicle_id:   vehicleId,
                booking_date: date,
                booking_time: time,
                notes:        notes,
            })
        })
        .then(r => {
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                throw new Error('Server returned non-JSON (status ' + r.status + '). Ensure controller returns response()->json(...).');
            }
            if (r.status === 422) {
                // Marked so the catch can tell a deliberate refusal — a policy
                // rule, a full slot — from the request itself failing. Both
                // land in the same handler otherwise.
                return r.json().then(body => { body.refused = true; throw body; });
            }
            return r.json();
        })
        .then(data => {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirm Booking'; }
           if (data.success) {
    // Add new booking to array instantly
    const d = new Date(date);
    BOOKINGS.unshift({
        id:        '#' + data.reference,
        dbId:      data.booking_id,
        service:   selectedService.name,
        serviceId: selectedService.dbId,
        date:      date,
        day:       String(d.getDate()).padStart(2, '0'),
        mon:       d.toLocaleString('en', { month: 'short' }),
        yr:        String(d.getFullYear()),
        time:      time,
        staff:     'TBA',
        vehicle:   (() => { const v = MY_VEHICLES.find(v => v.id == vehicleId); return v ? v.make + ' (' + v.plate + ')' : 'N/A'; })(),
        amount:    'TBA',
        status:    'upcoming',
    });

    // Update counts
    const cnt = document.getElementById('tabCountBookings');
    if (cnt) cnt.textContent = BOOKINGS.length;
    document.getElementById('cnt-all').textContent      = BOOKINGS.length;
    document.getElementById('cnt-upcoming').textContent = BOOKINGS.filter(b => b.status === 'upcoming').length;

    // Re-render bookings list
    renderBookings();

    // Show success screen
    document.getElementById('modalFormView').style.display = 'none';
    document.getElementById('modalSuccessView').classList.add('show');
    document.getElementById('modalRefNo').textContent          = data.reference;
    document.getElementById('modalSuccessDate').textContent    = date + ' at ' + time;
    document.getElementById('modalSuccessService').textContent = selectedService.name;
} else {
    apxDialog({ type: 'danger', title: 'Booking Failed', msg: data.message || 'Booking failed. Please try again.', confirmLabel: 'OK' });
}
        })
        .catch(err => {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-calendar-check"></i> Confirm Booking'; }
            if (err && err.errors) {
                const msgs = Object.values(err.errors).flat().join('<br>');
                apxDialog({ type: 'warning', title: 'Validation Errors', msg: msgs, confirmLabel: 'OK' });
            } else if (err && err.refused) {
                // The booking was understood and declined, with something the
                // customer can act on. Calling that "Error" in red said the
                // system had broken, while the message underneath explained
                // what to change — the title contradicted the useful part.
                apxDialog({
                    type: 'info',
                    title: 'Check your booking',
                    msg: err.message || 'This booking could not be accepted as it stands.',
                    confirmLabel: 'OK',
                });
            } else {
                apxDialog({ type: 'danger', title: 'Error', msg: err.message || 'Could not submit booking. Please try again.', confirmLabel: 'OK' });
            }
        });
    }

    /* Real bundles from the database.
       This panel used to list four invented promos with discount codes that
       nothing in the app would honour and expiry dates already in the past.
       A customer copying one of those codes had nowhere to use it. */
    const BUNDLES = @json($bundlesJs ?? []);

    /* One card, two panels.
       Promos & Offers shows the full card; the strip above the service grid
       shows a compact one. Both are built here so the saving, the price and
       the vehicle restriction can never read differently in the two places
       for the same bundle — the earlier strip-shaped duplicate of this markup
       is exactly how that drift starts.

       Vehicle restriction is on both: it decides whether the customer can
       book the bundle at all, so a card that leaves it out is a card that
       sends them to a modal that rejects them. */
    function bundleCard(b, opts) {
        const compact = !!(opts && opts.compact);

        const count   = b.includes.length + (b.includes.length === 1 ? ' service' : ' services');
        const vehicle = b.vehicle ? esc(b.vehicle.charAt(0).toUpperCase() + b.vehicle.slice(1)) + 's only' : '';

        const detail = compact
            ? `${count}${vehicle ? ' · <em>' + vehicle + '</em>' : ''}`
            : `${b.description ? esc(b.description) + '<br>' : ''}` +
              `<strong>Includes:</strong> ${b.includes.map(esc).join(' + ')}` +
              `${vehicle ? '<br><em>' + vehicle + '</em>' : ''}`;

        // Above the footer, not below it: anything after the footer would sit
        // at a different height on a card that has no end date, which undoes
        // the levelling margin-top:auto does.
        const ends = b.ends
            ? `<div style="font-size:.72rem;color:var(--text-muted);margin-bottom:10px;"><i class="fas fa-clock"></i> Until ${esc(b.ends)}</div>`
            : '';

        return `
            <div class="promo-card${compact ? ' promo-card--compact' : ''}">
                <div class="promo-card-accent" style="background:#16a34a;"></div>
                <div class="promo-card-body">
                    <div class="promo-tag" style="color:#16a34a;">
                        <i class="fas fa-boxes-stacked"></i>Save ${peso(b.saves)} (${b.percent}%)
                    </div>
                    <div class="promo-title">${esc(b.name)}</div>
                    <div class="promo-desc">${detail}</div>
                    ${ends}
                    <div class="promo-card-footer">
                        <span class="promo-expiry">
                            <s style="color:var(--text-muted);">${peso(b.list)}</s>
                            <strong style="color:var(--text);margin-left:6px;font-size:1rem;">${peso(b.price)}</strong>
                        </span>
                        <button class="btn-book-service" onclick="bookBundle(${b.id})">
                            <i class="fas fa-calendar-plus"></i> Book
                        </button>
                    </div>
                </div>
            </div>`;
    }

    function renderPromos() {
        const grid = document.getElementById('promosGrid');
        if (!grid) return;

        document.getElementById('promoCount').textContent =
            BUNDLES.length + (BUNDLES.length === 1 ? ' bundle' : ' bundles');

        if (!BUNDLES.length) {
            grid.innerHTML = `
                <div style="grid-column:1/-1;text-align:center;padding:36px 16px;color:var(--text-muted);font-size:.86rem;">
                    <i class="fas fa-box-open" style="font-size:1.6rem;display:block;margin-bottom:10px;opacity:.5;"></i>
                    No bundles are running at the moment. Check back soon.
                </div>`;
            return;
        }

        grid.innerHTML = BUNDLES.map(b => bundleCard(b)).join('');
    }

    /* The bundle strip above the service grid.
       Only while the customer is looking at the whole list: a bundle sitting
       under "Brakes & Pipes", or above "no services match your search",
       looks like the filter is broken. */
    function renderBundleStrip() {
        const strip = document.getElementById('bundleStrip');
        const rail  = document.getElementById('bundleStripRail');
        if (!strip || !rail) return;

        const show = BUNDLES.length && currentCat === 'all' && !currentSvcSearch;
        strip.hidden = !show;
        if (!show) { rail.innerHTML = ''; return; }

        rail.innerHTML = BUNDLES.map(b => bundleCard(b, { compact: true })).join('');
    }

    /* Booking a bundle reuses the service modal: the bundle's first service
       stands in for the header, and the server ignores whatever service_ids
       the page sends once bundle_id is set, so the contents cannot drift
       from what the bundle actually contains. */
    function bookBundle(id) {
        const bundle = BUNDLES.find(b => b.id === id);
        if (!bundle) return;

        const primary = SERVICES.find(s => s.dbId === bundle.serviceIds[0]);
        if (!primary) {
            apxDialog({
                variant: 'danger', title: 'Bundle unavailable',
                message: 'This bundle refers to a service that is no longer offered. Please contact the shop.',
            });
            return;
        }

        selectedBundle = bundle;
        openModal(primary.id, true);

        // Recast the modal for the bundle: its own name, its contents, its
        // price — and no extras, because adding to a bundle is not offered.
        document.getElementById('modalServiceName').textContent     = bundle.name;
        document.getElementById('modalServiceNameChip').textContent = bundle.name;
        document.getElementById('modalServiceCat').textContent =
            'Bundle · ' + bundle.includes.length + ' services · ' + bundle.duration + ' min';
        document.getElementById('modalServiceIcon').className = 'fas fa-boxes-stacked';

        const extrasWrap = document.getElementById('extrasList')?.parentElement;
        if (extrasWrap) extrasWrap.style.display = 'none';

        refreshBookingTotal();
    }

    function copyCode(btn, code) {
        navigator.clipboard.writeText(code).then(() => {
            btn.classList.add('copied');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 1800);
        });
    }

    // Fix 5: Sync profile vehicle list with live MY_VEHICLES data
    function refreshProfileVehicleSummary() {
        const container = document.getElementById('profileVehicleSummary');
        if (!container) return;
        if (!MY_VEHICLES.length) {
            container.innerHTML = '<div style="color:var(--text-muted);font-style:italic;font-size:0.85rem;padding:8px 0;">No vehicles registered yet.</div>';
            return;
        }
        container.innerHTML = MY_VEHICLES.map(v =>
            `<div class="profile-read-val">
                <i class="fas fa-motorcycle"></i>
                ${esc(v.make)}${v.year ? ' (' + v.year + ')' : ''} &mdash;
                <span class="pv-plate">${esc(v.plate || 'No plate')}</span>
                ${v.primary ? '<span class="primary-badge"><i class="fas fa-star"></i> Primary</span>' : ''}
            </div>`
        ).join('');
    }

    /* The service-due strip on a vehicle card.
       Drawn from the same calculation that decides whether to email the
       customer — the controller runs MaintenanceDue and sends the answer
       down — so the dashboard and the inbox cannot disagree about whether a
       car is due. Nothing is drawn in the common case of nothing being due. */
    function serviceDueNotice(v) {
        if (!v.due) {
            return v.lastService
                ? `<div class="vehicle-service-note"><i class="fas fa-circle-check"></i> Last serviced ${esc(v.lastService)}</div>`
                : '';
        }

        const label = v.due.overdue ? 'Overdue' : 'Due ' + esc(v.due.dueDate);
        const suggest = v.due.recommendation
            ? ` &middot; we suggest ${esc(v.due.recommendation)}`
            : '';

        return `
            <div class="vehicle-service-note ${v.due.overdue ? 'is-overdue' : 'is-due'}">
                <i class="fas fa-screwdriver-wrench"></i>
                <span><strong>${label}</strong>: ${esc(v.due.name)}${suggest}</span>
            </div>`;
    }

    function renderVehicles() {
        const grid  = document.getElementById('vehiclesGrid');
        const empty = document.getElementById('vehiclesEmpty');
        document.getElementById('tabCountVehicles').textContent = MY_VEHICLES.length;
        if (!MY_VEHICLES.length) { grid.innerHTML = ''; empty.style.display = 'block'; return; }
        empty.style.display = 'none';
        grid.innerHTML = MY_VEHICLES.map(v => `
            <div class="vehicle-card ${v.primary ? 'primary-vehicle' : ''}" id="vcard-${v.id}">
                <div class="vehicle-card-banner">
                    ${v.primary ? '<span class="vehicle-primary-badge"><i class="fas fa-star" style="margin-right:3px;font-size:0.55rem;"></i>Primary</span>' : ''}
                    <i class="fas ${v.type === 'motorcycle' ? 'fa-motorcycle' : 'fa-car'}"></i>
                </div>
                <div class="vehicle-card-body">
                    <div class="vehicle-name">${esc(v.make)}</div>
                    <div class="vehicle-plate">${esc(v.plate || 'No plate')}</div>
                    <div class="vehicle-meta-row">
                        <span><i class="fas ${v.type === 'motorcycle' ? 'fa-motorcycle' : 'fa-car'}"></i>${v.type === 'motorcycle' ? 'Motorcycle' : 'Car'}</span>
                        ${v.year  ? `<span><i class="fas fa-calendar"></i>${esc(v.year)}</span>` : ''}
                        ${v.color ? `<span><i class="fas fa-palette"></i>${esc(v.color)}</span>` : ''}
                        ${v.mileage ? `<span><i class="fas fa-gauge"></i>${Number(v.mileage).toLocaleString()} km</span>` : ''}
                    </div>
                    ${serviceDueNotice(v)}
                    <div class="vehicle-card-actions">
                        <button class="btn-veh-action" onclick="editVehicle(${v.id})"><i class="fas fa-pen"></i> Edit</button>
                        <button class="btn-veh-action danger" onclick="deleteVehicle(${v.id})"><i class="fas fa-trash"></i> Remove</button>
                    </div>
                </div>
            </div>`).join('');
        refreshProfileVehicleSummary();
    }

    // Null when adding, a vehicle id when editing. The same form serves both
    // rather than maintaining a second near-identical one.
    let editingVehicleId = null;

    function setVehicleFormMode(mode) {
        const btn   = document.querySelector('.btn-avf-save');
        const title = document.querySelector('#addVehicleForm .add-vehicle-form-title');
        if (btn)   btn.innerHTML = mode === 'edit'
            ? '<i class="fas fa-floppy-disk"></i> Save Changes'
            : '<i class="fas fa-floppy-disk"></i> Save Vehicle';
        if (title) title.innerHTML = mode === 'edit'
            ? '<i class="fas fa-pen"></i> Edit Vehicle'
            : '<i class="fas fa-circle-plus"></i> Register a New Vehicle';
    }

    function toggleAddVehicleForm() {
        const form = document.getElementById('addVehicleForm');
        form.classList.toggle('open');
        if (form.classList.contains('open')) {
            editingVehicleId = null;
            setVehicleFormMode('add');
            ['avfMake','avfYear','avfPlate','avfColor','avfMileage'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('avfType').value = 'car';
            setTimeout(() => document.getElementById('avfMake').focus(), 100);
        }
    }

    function editVehicle(id) {
        const v = MY_VEHICLES.find(x => x.id == id);
        if (!v) return;

        editingVehicleId = v.id;

        const form = document.getElementById('addVehicleForm');
        form.classList.add('open');
        setVehicleFormMode('edit');

        document.getElementById('avfMake').value  = v.make  || '';
        document.getElementById('avfYear').value  = v.year  || '';
        // "Not provided" is the placeholder a blank plate displays as; it is
        // not a real plate, so it must not be typed back into the field.
        document.getElementById('avfPlate').value = (v.plate && v.plate !== 'Not provided') ? v.plate : '';
        document.getElementById('avfColor').value = v.color || '';
        document.getElementById('avfType').value  = v.type  || 'car';
        document.getElementById('avfMileage').value = v.mileage || '';

        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => document.getElementById('avfMake').focus(), 200);
    }

    // ── Add-vehicle field validation ────────────────────────────────────────
    PlateInput.attach(document.getElementById('avfPlate'));
    FormValidate.register(document.getElementById('pef-name'), { rules: [FormValidate.rules.required('Your full name is required.')] });
    FormValidate.register(document.getElementById('avfMake'), { rules: [FormValidate.rules.required('Make / model is required.')] });
    FormValidate.register(document.getElementById('avfYear'), { rules: [FormValidate.rules.required('Year is required.')] });
    FormValidate.register(document.getElementById('avfPlate'), { rules: [FormValidate.rules.required('Plate number is required.'), FormValidate.rules.plate()] });

    function saveNewVehicle() {
    const vtype = document.getElementById('avfType').value;
    const make  = document.getElementById('avfMake').value.trim();
    const year  = document.getElementById('avfYear').value.trim();
    const plate = document.getElementById('avfPlate').value.trim();
    const color = document.getElementById('avfColor').value.trim();

    // Blank means "I do not know", which must reach the server as null and
    // not as 0 — a vehicle recorded at zero kilometres reads as a brand new
    // one to anyone at the shop looking at its history.
    const mileageRaw = document.getElementById('avfMileage').value.trim();
    const mileage    = mileageRaw === '' ? null : parseInt(mileageRaw, 10);

    if (!FormValidate.validateForm([document.getElementById('avfMake'), document.getElementById('avfYear'), document.getElementById('avfPlate')])) {
        return;
    }

    // The same form adds and edits, so the request differs only in verb and URL.
    const editing = editingVehicleId !== null;

    apxDialog({
        type: 'info',
        title: editing ? 'Confirm Vehicle Changes' : 'Confirm Vehicle Registration',
        msg: `Please confirm the vehicle details:<br><br>
              <strong>Make / Model:</strong> ${make}<br>
              <strong>Year:</strong> ${year}<br>
              <strong>Plate:</strong> ${plate}${color ? '<br><strong>Color:</strong> ' + color : ''}`,
        confirmLabel: editing
            ? '<i class="fas fa-floppy-disk" style="margin-right:5px;"></i> Save Changes'
            : '<i class="fas fa-floppy-disk" style="margin-right:5px;"></i> Register',
        cancelLabel: 'Go Back',
        onConfirm: () => {
            fetch(editing ? `/customer/vehicles/${editingVehicleId}` : '/customer/vehicles', {
                method: editing ? 'PATCH' : 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ brand: make, model: '', plate, year: parseInt(year), color, vehicle_type: vtype, mileage })
            })
            .then(r => r.json().catch(() => ({})).then(d => ({ ok: r.ok, data: d })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    // Laravel returns 422 validation errors under errors{}.
                    const first = data.errors ? Object.values(data.errors)[0][0] : null;
                    apxDialog({
                        type: 'danger',
                        title: editing ? 'Update Failed' : 'Registration Failed',
                        msg: first || data.message || 'Failed to save vehicle. Please try again.',
                        confirmLabel: 'OK'
                    });
                    return;
                }

                if (editing) {
                    MY_VEHICLES = MY_VEHICLES.map(v => v.id == editingVehicleId ? data.vehicle : v);
                } else {
                    MY_VEHICLES.push(data.vehicle);
                }

                editingVehicleId = null;
                toggleAddVehicleForm();
                renderVehicles();

                apxDialog({
                    type: 'success',
                    title: editing ? 'Vehicle Updated' : 'Vehicle Registered',
                    msg: `<strong>${make}</strong> has been ${editing ? 'updated' : 'added to your account'}.`,
                    confirmLabel: 'OK'
                });
            })
            .catch(() => apxDialog({ type: 'danger', title: 'Error', msg: 'Could not connect to the server. Please try again.', confirmLabel: 'OK' }));
        }
    });
}

    // Fix 4: Persist primary vehicle change to the backend
    function setPrimaryVehicle(id) {
        fetch(`/customer/vehicles/${id}/primary`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                MY_VEHICLES = MY_VEHICLES.map(v => ({ ...v, primary: v.id == id }));
                renderVehicles();
                apxDialog({ type: 'success', title: 'Primary Vehicle Set', msg: 'Your primary vehicle has been updated. It will be pre-selected when booking a service.', confirmLabel: 'OK' });
            } else {
                apxDialog({ type: 'danger', title: 'Error', msg: data.message || 'Could not set primary vehicle. Please try again.', confirmLabel: 'OK' });
            }
        })
        .catch(() => apxDialog({ type: 'danger', title: 'Error', msg: 'Could not connect to the server. Please try again.', confirmLabel: 'OK' }));
    }

    function deleteVehicle(id) {
        // Fix 1: Block removal if the vehicle has an active booking
        const vehicle = MY_VEHICLES.find(v => v.id === id);
        if (vehicle) {
            const activeStatuses = ['upcoming', 'in_progress'];
            const hasActiveBooking = BOOKINGS.some(b =>
                activeStatuses.includes(b.status) &&
                (b.vehicle_id === id || b.vehicle.toLowerCase().includes(vehicle.make.toLowerCase()))
            );
            if (hasActiveBooking) {
                apxDialog({
                    type: 'warning', title: 'Vehicle Has Active Booking',
                    msg: 'This vehicle cannot be removed because it has an active or upcoming booking. Please cancel the booking first.',
                    confirmLabel: 'Got It'
                });
                return;
            }
        }

        apxDialog({
            type: 'danger', title: 'Remove Vehicle',
            msg: `Are you sure you want to remove <strong>${vehicle ? vehicle.make : 'this vehicle'}</strong>? This action cannot be undone.`,
            confirmLabel: '<i class="fas fa-trash" style="margin-right:5px;"></i> Remove',
            cancelLabel: 'Cancel',
            onConfirm: () => {
                fetch(`/customer/vehicles/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const wasPrimary = MY_VEHICLES.find(v => v.id === id)?.primary;
                        MY_VEHICLES = MY_VEHICLES.filter(v => v.id !== id);
                        if (wasPrimary && MY_VEHICLES.length) MY_VEHICLES[0].primary = true;
                        renderVehicles();
                        apxDialog({ type: 'success', title: 'Vehicle Removed', msg: 'The vehicle has been removed from your account.', confirmLabel: 'OK' });
                    } else {
                        apxDialog({ type: 'danger', title: 'Error', msg: data.message || 'Failed to remove vehicle. Please try again.', confirmLabel: 'OK' });
                    }
                })
                .catch(() => apxDialog({ type: 'danger', title: 'Error', msg: 'Could not connect to the server. Please try again.', confirmLabel: 'OK' }));
            }
        });
    }


    // ── PROFILE ────────────────────────────────────────────────
    function toggleProfileEdit() {
        const editing = document.getElementById('profileEditView').style.display !== 'none';
        document.getElementById('profileReadView').style.display = editing ? '' : 'none';
        document.getElementById('profileEditView').style.display = editing ? 'none' : '';
        document.getElementById('profileEditBtn').innerHTML = editing
            ? '<i class="fas fa-pen"></i> Edit'
            : '<i class="fas fa-xmark"></i> Cancel';
    }

    function cancelProfileEdit() {
        document.getElementById('profileReadView').style.display = '';
        document.getElementById('profileEditView').style.display = 'none';
        document.getElementById('profileEditBtn').innerHTML = '<i class="fas fa-pen"></i> Edit';
    }

    FormValidate.register(document.getElementById('pef-phone'), {
        rules: [{ test: v => v.trim() === '' || /^09\d{9}$|^\+639\d{9}$/.test(v.trim()), message: 'Enter a valid PH mobile number (e.g. 09171234567), or leave blank.' }]
    });

    // Fix 6: Save profile info to backend, then update read view on success
    function saveProfileInfo() {
        // name was collected by the form but never read here, so it never
        // reached the server and "Save Changes" silently discarded it.
        const name    = document.getElementById('pef-name').value.trim();
        const phone   = document.getElementById('pef-phone').value.trim();
        const dob     = document.getElementById('pef-dob').value;
        const address = document.getElementById('pef-address').value.trim();

        if (!FormValidate.validateForm([
            document.getElementById('pef-name'),
            document.getElementById('pef-phone'),
        ])) return;

        const saveBtn = document.querySelector('#profileEditView .btn-profile-save');
        if (saveBtn) { saveBtn.disabled = true; saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…'; }

        fetch('/customer/profile', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ name, phone, dob, address })
        })
        .then(r => r.json().catch(() => ({})).then(d => { d._ok = r.ok; return d; }))
        .then(data => {
            if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fas fa-floppy-disk"></i> Save Changes'; }

            // A 422 nests its text under errors{}; without this the real
            // reason was replaced by a generic "failed, please try again".
            if (!data._ok && data.errors) {
                data.message = Object.values(data.errors)[0][0];
            }
            if (data.success) {
                // Patch every place the name appears, so it is consistent
                // without a reload. It lives in one column, so everywhere else
                // picks it up on the next page load.
                const freshName = data.name || name;
                document.getElementById('prv-name').innerHTML = '<i class="fas fa-user"></i> ' + freshName;

                const navName = document.querySelector('.user-name');
                if (navName) navName.textContent = freshName;

                const avatar = document.querySelector('.user-avatar');
                if (avatar && data.initials) avatar.textContent = data.initials;

                if (phone)   document.getElementById('prv-phone').innerHTML   = '<i class="fas fa-phone"></i> ' + phone;
                if (dob)     document.getElementById('prv-dob').innerHTML     = '<i class="fas fa-cake-candles"></i> ' + dob;
                if (address) document.getElementById('prv-address').innerHTML = '<i class="fas fa-location-dot"></i> ' + address;
                cancelProfileEdit();
                apxDialog({ type: 'success', title: 'Profile Updated', msg: 'Your personal information has been saved successfully.', confirmLabel: 'OK' });
            } else {
                apxDialog({ type: 'danger', title: 'Save Failed', msg: data.message || 'Failed to save profile. Please try again.', confirmLabel: 'OK' });
            }
        })
        .catch(() => {
            if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fas fa-floppy-disk"></i> Save Changes'; }
            apxDialog({ type: 'danger', title: 'Error', msg: 'Could not connect to the server. Please try again.', confirmLabel: 'OK' });
        });
    }

    // ── Account deletion ───────────────────────────────────────────────────
    // Self-service: no admin approval, no queue. Checks what's standing in the
    // way first so the consequences are spelled out before we ask for a password.
    function confirmDeleteAccount() {
        fetch('/customer/account/deletion-status', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(status => {
                if (status.blocked) {
                    apxDialog({
                        type: 'warning',
                        title: 'Service in progress',
                        msg: 'Your vehicle is currently being serviced and we may need to contact you about it. You can delete your account once the service is completed.',
                        confirmLabel: 'OK'
                    });
                    return;
                }

                let warning = 'Your account will be deactivated and you\'ll be signed out. You have <strong>30 days</strong> to change your mind \u2014 just log back in with the same email and password to restore it. After that your personal details are permanently erased.';

                if (status.upcoming.length) {
                    const list = status.upcoming.map(b =>
                        `<li><strong>${esc(b.service)}</strong> — ${b.date} at ${b.time}</li>`
                    ).join('');
                    warning = `You have ${status.upcoming.length} upcoming booking${status.upcoming.length > 1 ? 's' : ''}:`
                        + `<ul style="margin:8px 0 10px 18px;">${list}</ul>`
                        + `Deleting your account will <strong>cancel ${status.upcoming.length > 1 ? 'them' : 'it'}</strong>. ${warning}`;
                }

                apxDialog({
                    type: 'danger',
                    title: 'Delete Account',
                    msg: warning,
                    confirmLabel: status.upcoming.length
                        ? '<i class="fas fa-trash" style="margin-right:5px;"></i> Cancel booking & continue'
                        : '<i class="fas fa-trash" style="margin-right:5px;"></i> Continue',
                    cancelLabel: 'Keep my account',
                    onConfirm: () => openDeleteAccountModal()
                });
            })
            .catch(() => apxDialog({ type: 'danger', title: 'Error', msg: 'Could not check your account status. Please try again.', confirmLabel: 'OK' }));
    }

    function openDeleteAccountModal() {
        document.getElementById('deleteAccountPassword').value = '';
        FormValidate.validateField(document.getElementById('deleteAccountPassword'));
        document.getElementById('deleteAccountModal').classList.add('open');
        setTimeout(() => document.getElementById('deleteAccountPassword').focus(), 80);
    }

    function closeDeleteAccountModal() {
        document.getElementById('deleteAccountModal').classList.remove('open');
    }

    function submitDeleteAccount() {
        const input = document.getElementById('deleteAccountPassword');
        if (!FormValidate.validateForm([input])) return;

        const btn = document.getElementById('deleteAccountConfirmBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';

        fetch('/customer/account', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ password: input.value })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeDeleteAccountModal();
                apxDialog({
                    type: 'success', title: 'Account Deactivated',
                    msg: `Your account has been deactivated and you've been signed out.<br><br>`
                       + `Changed your mind? Log back in with the same email and password before `
                       + `<strong>${data.purge_at}</strong> to restore it. After that date your personal `
                       + `details are permanently erased.`,
                    confirmLabel: 'OK',
                    onConfirm: () => window.location.href = data.redirect || '/'
                });
                setTimeout(() => window.location.href = data.redirect || '/', 4000);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash"></i> Delete My Account';
                apxDialog({ type: 'danger', title: 'Could not delete account', msg: data.message || 'Please try again.', confirmLabel: 'OK' });
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash"></i> Delete My Account';
            apxDialog({ type: 'danger', title: 'Error', msg: 'Could not connect to the server. Please try again.', confirmLabel: 'OK' });
        });
    }

    FormValidate.register(document.getElementById('deleteAccountPassword'), {
        rules: [FormValidate.rules.required('Enter your password to confirm.')]
    });

    @if (session('account_restored'))
    // Logging in during the grace period cancels a pending deletion.
    apxDialog({
        type: 'success',
        title: 'Welcome back',
        msg: 'Your account deletion has been cancelled and your account is active again. Any bookings you had cancelled will need to be rebooked.',
        confirmLabel: 'OK'
    });
    @endif
    // INIT
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });

    // Sidebar toggle — below 992px the sidebar is off-canvas, so the hamburger
    // slides it over the content with a backdrop instead of shrinking it.
    (function () {
        const MOBILE = '(max-width: 992px)';
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        const backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);

        function closeMobileSidebar() {
            sidebar.classList.remove('mobile-open');
            backdrop.classList.remove('show');
        }

        document.getElementById('sidebarToggle').addEventListener('click', function () {
            if (window.matchMedia(MOBILE).matches) {
                const open = sidebar.classList.toggle('mobile-open');
                backdrop.classList.toggle('show', open);
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        });

        backdrop.addEventListener('click', closeMobileSidebar);

        // Tapping a nav item should close the drawer, not leave it covering the page.
        sidebar.addEventListener('click', function (e) {
            if (e.target.closest('.nav-link') && window.matchMedia(MOBILE).matches) {
                closeMobileSidebar();
            }
        });

        // Leaving mobile width resets the drawer state.
        window.matchMedia(MOBILE).addEventListener('change', function (e) {
            if (!e.matches) closeMobileSidebar();
        });
    })();

    // Dropdown toggle
    let dropdownTimeout;
    document.querySelector('.user-chip').addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelector('.dropdown-menu').classList.toggle('show');
});
document.querySelector('.dropdown').addEventListener('mouseenter', function() {
    clearTimeout(dropdownTimeout);
    document.querySelector('.dropdown-menu').classList.add('show');
});
document.querySelector('.dropdown').addEventListener('mouseleave', function() {
    dropdownTimeout = setTimeout(() => {
        document.querySelector('.dropdown-menu').classList.remove('show');
    }, 300);
});
document.querySelector('.dropdown-menu').addEventListener('mouseenter', function() {
    clearTimeout(dropdownTimeout);
});
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown'))
        document.querySelector('.dropdown-menu').classList.remove('show');
});
    renderBookings();
    filterServices();
    renderPromos();
    renderBundleStrip();

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon   = document.getElementById('themeIcon');
    const htmlEl      = document.documentElement;

    function applyTheme(mode) {
        if (mode === 'light') {
            htmlEl.classList.add('light-mode');
            themeIcon.className = 'fas fa-sun';
            themeToggle.title = 'Switch to dark mode';
        } else {
            htmlEl.classList.remove('light-mode');
            themeIcon.className = 'fas fa-moon';
            themeToggle.title = 'Switch to light mode';
        }
    }

    // Light, matching the admin panel and the sign-in pages. A customer used
    // to cross from a light landing page, through sign-in, into a dark
    // dashboard. Anyone who has actually chosen dark keeps it — only a first
    // visit is affected.
    const savedTheme = localStorage.getItem('apx-theme') || '{{ $defaultTheme ?? 'light' }}';
    applyTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const next = htmlEl.classList.contains('light-mode') ? 'dark' : 'light';
        localStorage.setItem('apx-theme', next);
        applyTheme(next);
    });

    // ── Keep the figures current without a reload ───────────────────────
    (function () {
        function setStat(id, value) {
            const el = document.getElementById(id);
            if (el && el.textContent !== String(value)) el.textContent = value;
        }

        ApxLiveRefresh.start({
            url:    '{{ route('customer.dashboard.live') }}',
            stamp:  '#cLiveUpdated',
            button: '#cLiveRefreshBtn',
            apply(data) {
                setStat('cstatUpcoming',  data.stats.upcoming);
                setStat('cstatCompleted', data.stats.completed);
                setStat('cstatTotal',     data.stats.total);
                setStat('cstatSpent', '₱' + Number(data.stats.spent || 0).toLocaleString('en-PH'));

                // One poll serves both: no second timer for the bell.
                if (window.ApxBell && typeof data.unread !== 'undefined') {
                    ApxBell.setCount(data.unread);
                }

                // Keep the booking list in step with the server, so a booking
                // the shop confirms or cancels shows up here on its own.
                if (Array.isArray(data.bookings)) {
                    const byRef = new Map(data.bookings.map(b => ['#' + b.reference, b]));
                    let changed = false;

                    BOOKINGS.forEach(b => {
                        const fresh = byRef.get(b.id);
                        if (!fresh) return;
                        const status = ['confirmed', 'pending'].includes(fresh.status) ? 'upcoming' : fresh.status;
                        if (b.status !== status || b.staff !== fresh.staff) {
                            b.status = status;
                            b.staff  = fresh.staff;
                            changed  = true;
                        }
                    });

                    if (changed && typeof renderBookings === 'function') renderBookings();
                }
            }
        });
    })();

    // ── Change password, in-app ─────────────────────────────────────────
    function togglePasswordForm() {
        const form = document.getElementById('changePasswordForm');
        const open = form.style.display !== 'none';
        form.style.display = open ? 'none' : 'block';
        document.getElementById('pwToggleBtn').innerHTML = open
            ? '<i class="fas fa-key"></i> Change'
            : '<i class="fas fa-xmark"></i> Cancel';
        if (!open) {
            ['cpCurrent', 'cpNew', 'cpConfirm'].forEach(id => document.getElementById(id).value = '');
            setTimeout(() => document.getElementById('cpCurrent').focus(), 80);
        }
    }

    function submitPasswordChange() {
        const current = document.getElementById('cpCurrent').value;
        const next    = document.getElementById('cpNew').value;
        const confirm = document.getElementById('cpConfirm').value;

        if (!current || !next || !confirm) {
            apxDialog({ type: 'danger', title: 'Missing details', msg: 'Please fill in all three password fields.', confirmLabel: 'OK' });
            return;
        }
        if (next !== confirm) {
            apxDialog({ type: 'danger', title: 'Passwords do not match', msg: 'The new password and its confirmation are different.', confirmLabel: 'OK' });
            return;
        }

        fetch('/customer/password', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                current_password: current,
                password: next,
                password_confirmation: confirm
            })
        })
        .then(r => r.json().catch(() => ({})).then(d => { d._ok = r.ok; return d; }))
        .then(data => {
            if (!data._ok || !data.success) {
                // A 422 nests its text under errors{}; show the real reason.
                const first = data.errors ? Object.values(data.errors)[0][0] : null;
                apxDialog({
                    type: 'danger', title: 'Could not update password',
                    msg: first || data.message || 'Please check the details and try again.',
                    confirmLabel: 'OK'
                });
                return;
            }

            // Never echo the values back; just clear them.
            ['cpCurrent', 'cpNew', 'cpConfirm'].forEach(id => document.getElementById(id).value = '');
            togglePasswordForm();
            apxDialog({
                type: 'success', title: 'Password Updated',
                msg: data.message || 'Your password has been changed.',
                confirmLabel: 'OK'
            });
        })
        .catch(() => apxDialog({ type: 'danger', title: 'Error', msg: 'Could not connect to the server. Please try again.', confirmLabel: 'OK' }));
    }
</script>
</body>
</html>