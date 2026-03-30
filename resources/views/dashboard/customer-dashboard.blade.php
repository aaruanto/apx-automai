<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>My Dashboard — APX AutoMai</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="{{ asset('assets/css/customer-dashboard.css') }}" rel="stylesheet" />
    <style>
        /* ── Theme toggle button ──────────────────────────────── */
        .theme-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        /* Smooth transitions page-wide */
        *, *::before, *::after {
            transition: background-color 0.25s ease, color 0.18s ease,
                        border-color 0.18s ease, box-shadow 0.18s ease;
        }

        /* ══════════════════════════════════════════════════════
           LIGHT MODE — override ALL variables used in
           customer-dashboard.css so every var(--x) flips
        ══════════════════════════════════════════════════════ */
        html.light-mode {
            --black:      #f4f5f7;   /* body bg */
            --surface:    #ffffff;   /* topnav, sidebar, cards, modal */
            --surface-2:  #f0f1f3;   /* hover states, bk-date-col, service-img */
            --surface-3:  #e6e8ec;   /* inputs, filter-tabs, cat-filters, quick-actions */
            --border:     rgba(0,0,0,0.09);
            --text:       #1a1d23;
            --text-muted: #6b7280;
            --red-glow:   rgba(232,25,44,0.10);
        }

        /* ── Body ─────────────────────────────────────────── */
        html.light-mode body { background: #f4f5f7; }

        /* ── Topnav ───────────────────────────────────────── */
        html.light-mode .topnav { box-shadow: 0 1px 6px rgba(0,0,0,.07); }
        html.light-mode #sidebarToggle { color: #6b7280; }
        html.light-mode .brand-auto { color: #1a1d23; }
        html.light-mode .brand-badge {
            background: #e6e8ec;
            color: #6b7280;
            border-color: rgba(0,0,0,0.09);
        }

        /* ── Sidebar (stays using variables — no extra needed) */
        html.light-mode .nav-link { color: #6b7280; }
        html.light-mode .nav-link:hover { color: #1a1d23; }
        html.light-mode .nav-link.active { color: #E8192C; }
        html.light-mode .section-label { color: #9ca3af; }
        html.light-mode .sidebar-footer-info .label { color: #9ca3af; }
        html.light-mode .sidebar-footer-info .value { color: #1a1d23; }

        /* ── Section tabs ─────────────────────────────────── */
        /* .section-tabs has no bg in CSS — it shows body bg, which
           is now #f4f5f7. Border uses var(--border) — already fixed. */
        html.light-mode .section-tab { color: #6b7280; }
        html.light-mode .section-tab:hover { color: #1a1d23; }
        html.light-mode .section-tab.active { color: #E8192C; border-bottom-color: #E8192C; }
        /* tab-count uses var(--surface-3) + var(--border) — already overridden via variables */
        html.light-mode .section-tab.active .tab-count {
            background: rgba(232,25,44,0.10);
            border-color: rgba(232,25,44,0.3);
            color: #E8192C;
        }

        /* ── Welcome banner ───────────────────────────────── */
        /* In light mode: clean white card with red left accent instead of dark gradient */
        html.light-mode .welcome-banner {
            background: #ffffff !important;
            border: 1px solid rgba(0,0,0,0.08) !important;
            border-left: 3px solid #E8192C !important;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        html.light-mode .welcome-text h2 { color: #1a1d23; }
        html.light-mode .welcome-text h2 span { color: #E8192C; }
        html.light-mode .welcome-text p { color: #6b7280; }

        /* ── Stat cards ───────────────────────────────────── */
        /* background uses var(--surface) = #fff ✓, border uses var(--border) ✓ */
        html.light-mode .stat-card { box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        html.light-mode .stat-value { color: #1a1d23; }
        html.light-mode .stat-footer a { color: #6b7280; }

        /* ── Content cards ────────────────────────────────── */
        html.light-mode .card { box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        html.light-mode .card-header-title { color: #1a1d23; }
        html.light-mode .card-badge { background: #e6e8ec; color: #6b7280; }

        /* ── Booking items (dashboard preview) ────────────── */
        /* .booking-date-box uses var(--surface-3) — overridden via variable ✓ */
        html.light-mode .booking-date-box .day { color: #E8192C; } /* keep red */
        html.light-mode .booking-service { color: #1a1d23; }

        /* ── Loyalty card (keep dark — hardcoded gradient in CSS) */
        /* Already hardcoded to #1a0a0b → #2a0e10 — no override needed */

        /* ── Quick actions ────────────────────────────────── */
        /* Uses var(--surface-3) + var(--border) — overridden via variables ✓ */
        html.light-mode .quick-action-btn { color: #374151; }
        html.light-mode .quick-action-btn:hover { color: #374151; background: #dde0e6; }

        /* ── Filter bar (My Bookings) ─────────────────────── */
        /* .filter-tab uses var(--surface-3) — overridden via variables ✓ */
        html.light-mode .filter-tab { color: #6b7280; }
        html.light-mode .filter-tab:hover {
            color: #1a1d23;
            border-color: rgba(0,0,0,0.18);
        }
        html.light-mode .filter-tab.active {
            background: #E8192C;
            border-color: #E8192C;
            color: #fff;
        }
        html.light-mode .dot-all { background: #9ca3af; }

        /* ── Booking cards (bk-card) ──────────────────────── */
        /* .bk-card uses var(--surface) = #fff ✓
           .bk-date-col uses var(--surface-2) = #f0f1f3 ✓ */
        html.light-mode .bk-card { box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        html.light-mode .bk-service { color: #1a1d23; }
        html.light-mode .bk-amount { color: #1a1d23; }
        html.light-mode .bk-status-col { border-left-color: rgba(0,0,0,0.07); }

        /* ── Services: shop toolbar & search ──────────────── */
        /* .shop-search input uses var(--surface-3) ✓ */
        html.light-mode .shop-result-count { color: #6b7280; }
        html.light-mode .shop-result-count span { color: #1a1d23; }

        /* ── Category filters ─────────────────────────────── */
        /* Uses var(--surface-3) + var(--border) — overridden via variables ✓ */
        html.light-mode .cat-filter { color: #374151; }
        html.light-mode .cat-filter:hover {
            color: #1a1d23;
            border-color: rgba(0,0,0,0.18);
        }
        html.light-mode .cat-filter.active {
            background: #E8192C;
            border-color: #E8192C;
            color: #fff;
        }

        /* ── Service cards ────────────────────────────────── */
        /* .service-card uses var(--surface) = #fff ✓
           .service-img uses var(--surface-2) = #f0f1f3 ✓ — this was the dark box */
        html.light-mode .service-card { box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        html.light-mode .service-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.12); }
        html.light-mode .service-name { color: #1a1d23; }
        html.light-mode .service-cat-badge {
            background: rgba(255,255,255,0.85);
            color: #374151;
            border-color: rgba(0,0,0,0.1);
        }
        html.light-mode .service-card-footer { border-top-color: rgba(0,0,0,0.07); }

        /* ── Booking modal ────────────────────────────────── */
        /* .modal uses var(--surface) = #fff ✓
           .modal-service-chip uses var(--surface-2) ✓
           .m-control uses var(--surface-3) ✓ */
        html.light-mode .modal { box-shadow: 0 16px 48px rgba(0,0,0,.18); }
        html.light-mode .modal-title { color: #1a1d23; }
        html.light-mode .modal-close:hover { background: #f0f1f3; color: #1a1d23; }
        html.light-mode .modal-service-chip .chip-name { color: #1a1d23; }
        html.light-mode .m-label { color: #374151; }
        html.light-mode .btn-modal-cancel {
            color: #374151;
            border-color: rgba(0,0,0,0.09);
        }
        html.light-mode .btn-modal-cancel:hover {
            background: #e6e8ec;
            color: #1a1d23;
            border-color: rgba(0,0,0,0.14);
        }
        html.light-mode .modal-success .ref {
            background: #f0f1f3;
            color: #1a1d23;
        }

        /* ── Page header ──────────────────────────────────── */
        html.light-mode .page-title { color: #1a1d23; }
        html.light-mode .breadcrumb li { color: #6b7280; }
        html.light-mode .breadcrumb li.active { color: #E8192C; }
        html.light-mode .page-date { color: #6b7280; }

        /* ── User chip / dropdown ─────────────────────────── */
        html.light-mode .user-name { color: #1a1d23; }
        html.light-mode .notif-dot { border-color: #ffffff; }

        /* ── Empty states ─────────────────────────────────── */
        html.light-mode .empty-state i { color: #d1d5db; }
        html.light-mode .services-empty i { color: #d1d5db; }

        /* ── Scrollbar ────────────────────────────────────── */
        html.light-mode ::-webkit-scrollbar-track { background: #f4f5f7; }
        html.light-mode ::-webkit-scrollbar-thumb { background: #d1d5db; }

        /* ── Book a Service — hover-reveal button ─────────── */
        .btn-book-service {
            background: var(--red);
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            opacity: 0;
            transform: translateY(4px);
            transition: background 0.2s, transform 0.2s, opacity 0.2s;
            pointer-events: none;
        }
        .service-card:hover .btn-book-service {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .btn-book-service:hover { background: var(--red-dark); transform: scale(1.03); }

        /* ── Vehicle select in modal ──────────────────────── */
        .m-control.m-select {
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        .m-control.m-select option { background: var(--surface-3); color: var(--text); }
        .m-vehicle-hint {
            font-size: 0.72rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 4px;
        }
        .m-vehicle-hint a { color: var(--red); text-decoration: none; cursor: pointer; }
        .m-vehicle-hint a:hover { text-decoration: underline; }

        /* ── My Vehicles panel ────────────────────────────── */
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

        /* ── REWARDS PANEL ────────────────────────────────── */
        .rewards-top-row {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 20px;
            margin-bottom: 32px;
            align-items: start;
        }
        @media (max-width: 820px) { .rewards-top-row { grid-template-columns: 1fr; } }

        /* Points card */
        .rw-points-card {
            background: linear-gradient(135deg, #1a0a0b 0%, #2a0e10 60%, #1a0a0b 100%);
            border: 1px solid rgba(232,25,44,0.3);
            border-radius: 12px;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }
        .rw-points-card::before {
            content: 'APX';
            position: absolute;
            right: -8px; top: -8px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 5.5rem;
            font-weight: 800;
            color: rgba(232,25,44,0.06);
            line-height: 1;
            pointer-events: none;
        }
        .rw-points-eyebrow {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .rw-points-value {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 3rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1;
            margin-bottom: 4px;
        }
        .rw-points-value span { font-size: 1.2rem; color: var(--text-muted); font-weight: 600; }
        .rw-points-sub { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 18px; }
        .rw-tier-bar-wrap {
            background: rgba(255,255,255,0.07);
            border-radius: 4px;
            height: 7px;
            margin-bottom: 8px;
            overflow: hidden;
        }
        .rw-tier-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--red-dark), var(--red));
            border-radius: 4px;
            transition: width 0.6s ease;
        }
        .rw-tier-bar-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: var(--text-muted);
            gap: 4px;
        }

        /* Tier cards */
        .rw-tiers {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        @media (max-width: 600px) { .rw-tiers { grid-template-columns: 1fr; } }
        .rw-tier-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 18px 16px 14px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
            transition: border-color 0.2s, transform 0.15s;
        }
        .rw-tier-card:hover { transform: translateY(-2px); border-color: rgba(232,25,44,0.3); }
        .rw-tier-card.rw-tier-active { border-color: rgba(232,25,44,0.45); background: var(--surface-2); }
        .rw-tier-icon { font-size: 1.4rem; margin-bottom: 4px; }
        .rw-tier-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: var(--text);
        }
        .rw-tier-range { font-size: 0.72rem; color: var(--text-muted); margin-bottom: 2px; }
        .rw-tier-perk { font-size: 0.78rem; color: var(--text-muted); line-height: 1.4; flex: 1; }
        .rw-tier-badge {
            display: inline-block;
            margin-top: 10px;
            background: var(--red);
            color: #fff;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 2px 9px;
            border-radius: 20px;
            align-self: flex-start;
        }

        /* Promos section header */
        .rw-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .rw-section-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rw-section-title i { color: var(--red); }

        /* Promo cards grid */
        .promos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 14px;
        }
        .promo-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            transition: border-color 0.2s, transform 0.15s;
        }
        .promo-card:hover { border-color: rgba(232,25,44,0.35); transform: translateY(-2px); }
        .promo-card-accent {
            height: 4px;
        }
        .promo-card-body { padding: 16px 18px 14px; }
        .promo-tag {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .promo-tag i { font-size: 0.6rem; }
        .promo-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: var(--text);
            margin-bottom: 4px;
            line-height: 1.25;
        }
        .promo-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 14px;
        }
        .promo-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }
        .promo-expiry {
            font-size: 0.72rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .promo-expiry i { color: var(--red); font-size: 0.66rem; }
        .promo-code-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .promo-code {
            font-family: monospace;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            background: var(--surface-3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 4px 10px;
            border-radius: 5px;
        }
        .btn-copy-code {
            background: none;
            border: 1px solid var(--border);
            color: var(--text-muted);
            width: 28px; height: 28px;
            border-radius: 5px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 0.72rem;
            transition: color 0.2s, border-color 0.2s, background 0.2s;
            flex-shrink: 0;
        }
        .btn-copy-code:hover { color: var(--red); border-color: var(--red); background: var(--red-glow); }
        .btn-copy-code.copied { color: var(--success); border-color: var(--success); }
    </style>
</head>
<body>

<!-- TOPNAV -->
<nav class="topnav">
    <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
   <a href="{{ url('/') }}" class="brand">
        <span class="brand-apx">APX</span>
        <span class="brand-auto">AutoMai</span>
        <span class="brand-badge">CUSTOMER</span>
    </a>
    <div class="topnav-search">
        <i class="fas fa-search search-icon"></i>
        <input type="text" placeholder="Search your bookings..." />
    </div>
    <div class="topnav-actions">
        <a href="#!" class="icon-btn" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="notif-dot"></span>
        </a>
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
                <a href="#!"><i class="fas fa-user" style="width:16px;margin-right:8px;"></i>My Profile</a>
                <a href="#!"><i class="fas fa-car" style="width:16px;margin-right:8px;"></i>My Vehicles</a>
                <a href="#!"><i class="fas fa-gear" style="width:16px;margin-right:8px;"></i>Settings</a>
                <hr />
                <a href="{{ route('logout') }}" class="logout"
                 onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                 <i class="fas fa-right-from-bracket" style="width:16px;margin-right:8px;"></i>Logout
                 </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                     @csrf
                    </form>
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
            <div class="section-label">Rewards</div>

            <a class="nav-link" id="nav-rewards" href="#" onclick="switchSection(event,'rewards')">
                <span class="nav-icon"><i class="fas fa-gift"></i></span>
                <span class="nav-label">Rewards</span>
            </a>

            <hr class="sidebar-divider" />
            <div class="section-label">Account</div>

            <a class="nav-link" href="#">
                <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
                <span class="nav-label">My Profile</span>
            </a>

            <a class="nav-link" href="#">
                <span class="nav-icon"><i class="fas fa-bell"></i></span>
                <span class="nav-label">Notifications</span>
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-footer-info">
                <div class="label">Logged in as</div>
                <div class="value">{{ Auth::user()->name }}</div>
            </div>
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
                    <span class="tab-count" id="tabCountBookings">11</span>
                </button>
                <button class="section-tab" id="tab-services" onclick="switchSection(event,'services')">
                    <i class="fas fa-wrench"></i> Book a Service
                </button>
                <button class="section-tab" id="tab-vehicles" onclick="switchSection(event,'vehicles')">
                    <i class="fas fa-car"></i> My Vehicles
                    <span class="tab-count" id="tabCountVehicles">2</span>
                </button>
                <button class="section-tab" id="tab-rewards" onclick="switchSection(event,'rewards')">
                    <i class="fas fa-gift"></i> Rewards
                </button>
            </div>

            <!-- ═══════════════════════════════════════════
                 TAB PANEL: DASHBOARD
            ═══════════════════════════════════════════ -->
            <div class="tab-panel active" id="panel-dashboard">

                <!-- WELCOME BANNER -->
                <div class="welcome-banner">
                    <div class="welcome-text">
                        <h2>Welcome back, <span>{{ Auth::user()->name }}</span>!</h2>
                        <p>You have 2 upcoming bookings. Your car is in good hands.</p>
                    </div>
                    <a href="#" class="btn-book" onclick="switchSection(event,'bookings'); filterBookings('all')">
                        <i class="fas fa-calendar-check"></i>
                        View My Bookings
                    </a>
                </div>

                <!-- STAT CARDS -->
                <div class="cards-grid">
                    <div class="stat-card primary">
                        <div class="stat-card-header">
                            <div class="stat-label">Upcoming</div>
                            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                        </div>
                        <div class="stat-value">2</div>
                        <div class="stat-footer">
                            <a href="#" onclick="switchSection(event,'bookings'); filterBookings('upcoming')">View Bookings <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-card-header">
                            <div class="stat-label">Completed</div>
                            <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                        </div>
                        <div class="stat-value">7</div>
                        <div class="stat-footer">
                            <a href="#" onclick="switchSection(event,'bookings'); filterBookings('completed')">View History <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-card-header">
                            <div class="stat-label">Loyalty Points</div>
                            <div class="stat-icon"><i class="fas fa-star"></i></div>
                        </div>
                        <div class="stat-value">620</div>
                        <div class="stat-footer">
                            <a href="#">Redeem Points <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-card-header">
                            <div class="stat-label">Total Spent</div>
                            <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                        </div>
                        <div class="stat-value">₱9.4k</div>
                        <div class="stat-footer">
                            <a href="#">View Invoices <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                    </div>
                </div>

                <!-- CONTENT GRID -->
                <div class="content-grid">
                    <!-- UPCOMING BOOKINGS PREVIEW -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-title"><i class="fas fa-calendar-check"></i> Upcoming Bookings</div>
                            <a href="#" onclick="switchSection(event,'bookings'); filterBookings('upcoming')" style="font-size:0.8rem;color:var(--red);text-decoration:none;">View All <i class="fas fa-arrow-right" style="font-size:0.7rem;"></i></a>
                        </div>
                        <div class="card-body">
                            <div class="booking-item">
                                <div class="booking-date-box">
                                    <span class="day">20</span>
                                    <span class="mon">Jan</span>
                                </div>
                                <div class="booking-info">
                                    <div class="booking-service">Full Car Wash + Interior Detailing</div>
                                    <div class="booking-meta">
                                        <span><i class="fas fa-clock"></i>10:00 AM</span>
                                        <span><i class="fas fa-user"></i>Marco R.</span>
                                        <span><i class="fas fa-car"></i>Toyota Vios (ABC 123)</span>
                                    </div>
                                </div>
                                <span class="badge badge-confirmed">Confirmed</span>
                            </div>
                            <div class="booking-item">
                                <div class="booking-date-box">
                                    <span class="day">28</span>
                                    <span class="mon">Jan</span>
                                </div>
                                <div class="booking-info">
                                    <div class="booking-service">Oil Change</div>
                                    <div class="booking-meta">
                                        <span><i class="fas fa-clock"></i>2:00 PM</span>
                                        <span><i class="fas fa-user"></i>Leo T.</span>
                                        <span><i class="fas fa-car"></i>Toyota Vios (ABC 123)</span>
                                    </div>
                                </div>
                                <span class="badge badge-pending">Pending</span>
                            </div>
                        </div>
                    </div>

                    <!-- SIDEBAR COLUMN -->
                    <div>
                        <div class="loyalty-card">
                            <div class="loyalty-label">Loyalty Rewards</div>
                            <div class="loyalty-points">620 pts</div>
                            <div class="loyalty-sub">380 pts away from Silver tier</div>
                            <div class="loyalty-bar-wrap">
                                <div class="loyalty-bar-fill"></div>
                            </div>
                            <div class="loyalty-bar-label">
                                <span>Bronze</span>
                                <span>Silver (1,000 pts)</span>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-title"><i class="fas fa-bolt"></i> Quick Actions</div>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions">
                                    <a href="#" class="quick-action-btn"><i class="fas fa-circle-plus"></i>Book Service</a>
                                    <a href="#" class="quick-action-btn"><i class="fas fa-car"></i>My Vehicles</a>
                                    <a href="#" class="quick-action-btn"><i class="fas fa-file-invoice"></i>Invoices</a>
                                    <a href="#" class="quick-action-btn"><i class="fas fa-headset"></i>Support</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /panel-dashboard -->


            <!-- ═══════════════════════════════════════════
                 TAB PANEL: MY BOOKINGS
            ═══════════════════════════════════════════ -->
            <div class="tab-panel" id="panel-bookings">

                <!-- FILTER BAR -->
                <div class="filter-bar">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all" onclick="filterBookings('all')">
                            <span class="dot dot-all"></span> All
                            <span class="tab-count" id="cnt-all">11</span>
                        </button>
                        <button class="filter-tab" data-filter="upcoming" onclick="filterBookings('upcoming')">
                            <span class="dot dot-upcoming"></span> Upcoming
                            <span class="tab-count" id="cnt-upcoming">2</span>
                        </button>
                        <button class="filter-tab" data-filter="inprogress" onclick="filterBookings('inprogress')">
                            <span class="dot dot-inprogress"></span> In Progress
                            <span class="tab-count" id="cnt-inprogress">1</span>
                        </button>
                        <button class="filter-tab" data-filter="completed" onclick="filterBookings('completed')">
                            <span class="dot dot-completed"></span> Completed
                            <span class="tab-count" id="cnt-completed">7</span>
                        </button>
                        <button class="filter-tab" data-filter="cancelled" onclick="filterBookings('cancelled')">
                            <span class="dot dot-cancelled"></span> Cancelled
                            <span class="tab-count" id="cnt-cancelled">1</span>
                        </button>
                    </div>
                    <div class="filter-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="bookingSearch" placeholder="Search bookings..." oninput="searchBookings(this.value)" />
                    </div>
                </div>

                <!-- BOOKING CARDS LIST -->
                <div class="booking-cards" id="bookingCardsList"></div>

                <!-- EMPTY STATE -->
                <div class="empty-state" id="bookingEmpty" style="display:none;">
                    <i class="fas fa-calendar-xmark"></i>
                    <p>No bookings found for this filter.</p>
                </div>

            </div><!-- /panel-bookings -->

            <!-- ═══════════════════════════════════════════
                 TAB PANEL: BOOK A SERVICE
            ═══════════════════════════════════════════ -->
            <div class="tab-panel" id="panel-services">

                <!-- TOOLBAR -->
                <div class="shop-toolbar">
                    <div class="shop-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="serviceSearch" placeholder="Search services..." oninput="filterServices()" />
                    </div>
                    <div class="shop-result-count">
                        Showing <span id="serviceCount">28</span> services
                    </div>
                </div>

                <!-- CATEGORY FILTERS -->
                <div class="category-filters">
                    <button class="cat-filter active" data-cat="all"        onclick="setCat(this)">All Services</button>
                    <button class="cat-filter"         data-cat="engine"    onclick="setCat(this)">Engine & Oil</button>
                    <button class="cat-filter"         data-cat="cvt"       onclick="setCat(this)">CVT & Transmission</button>
                    <button class="cat-filter"         data-cat="brakes"    onclick="setCat(this)">Brakes & Pipes</button>
                    <button class="cat-filter"         data-cat="inspection" onclick="setCat(this)">Inspection</button>
                    <button class="cat-filter"         data-cat="free"      onclick="setCat(this)">Free Services</button>
                </div>

                <!-- SERVICES GRID -->
                <div class="services-grid" id="servicesGrid"></div>

            </div><!-- /panel-services -->

            <!-- ═══════════════════════════════════════════
                 TAB PANEL: MY VEHICLES
            ═══════════════════════════════════════════ -->
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

                <!-- Add Vehicle Inline Form -->
                <div class="add-vehicle-form" id="addVehicleForm">
                    <div class="add-vehicle-form-title">
                        <i class="fas fa-circle-plus"></i> Register a New Vehicle
                    </div>
                    <div class="avf-grid">
                        <div class="m-form-group">
                            <label class="m-label">Make & Model</label>
                            <div class="m-input-wrap">
                                <i class="fas fa-motorcycle"></i>
                                <input class="m-control" id="avfMake" type="text" placeholder="e.g. Honda Click 125i" />
                            </div>
                        </div>
                        <div class="m-form-group">
                            <label class="m-label">Year</label>
                            <div class="m-input-wrap">
                                <i class="fas fa-calendar"></i>
                                <input class="m-control" id="avfYear" type="number" placeholder="e.g. 2022" min="1990" max="2030" />
                            </div>
                        </div>
                        <div class="m-form-group">
                            <label class="m-label">Plate / Unit No.</label>
                            <div class="m-input-wrap">
                                <i class="fas fa-id-card"></i>
                                <input class="m-control" id="avfPlate" type="text" placeholder="e.g. ABC 1234" />
                            </div>
                        </div>
                        <div class="m-form-group">
                            <label class="m-label">Color</label>
                            <div class="m-input-wrap">
                                <i class="fas fa-palette"></i>
                                <input class="m-control" id="avfColor" type="text" placeholder="e.g. Matte Black" />
                            </div>
                        </div>
                    </div>
                    <div class="avf-actions">
                        <button class="btn-avf-cancel" onclick="toggleAddVehicleForm()">Cancel</button>
                        <button class="btn-avf-save" onclick="saveNewVehicle()">
                            <i class="fas fa-floppy-disk"></i> Save Vehicle
                        </button>
                    </div>
                </div>

                <!-- Vehicles Grid -->
                <div class="vehicles-grid" id="vehiclesGrid"></div>

                <div class="vehicles-empty-state" id="vehiclesEmpty" style="display:none;">
                    <i class="fas fa-car"></i>
                    <p>No vehicles registered yet. Add your first vehicle to enable faster booking.</p>
                    <button class="btn-add-vehicle" onclick="toggleAddVehicleForm()" style="margin:0 auto;">
                        <i class="fas fa-plus"></i> Add Vehicle
                    </button>
                </div>

            </div><!-- /panel-vehicles -->


            <!-- ═══════════════════════════════════════════
                 TAB PANEL: REWARDS
            ═══════════════════════════════════════════ -->
            <div class="tab-panel" id="panel-rewards">

                <!-- TOP ROW: Loyalty card + tier progress -->
                <div class="rewards-top-row">

                    <!-- Loyalty Points Card -->
                    <div class="rw-points-card">
                        <div class="rw-points-eyebrow"><i class="fas fa-star"></i> Loyalty Rewards</div>
                        <div class="rw-points-value">620 <span>pts</span></div>
                        <div class="rw-points-sub">380 pts away from Silver tier</div>

                        <div class="rw-tier-bar-wrap">
                            <div class="rw-tier-bar-fill" style="width:62%;"></div>
                        </div>
                        <div class="rw-tier-bar-labels">
                            <span><i class="fas fa-circle" style="color:#cd7f32;font-size:0.55rem;"></i> Bronze</span>
                            <span style="color:var(--text-muted);">1,000 pts</span>
                            <span><i class="fas fa-circle" style="color:#9ca3af;font-size:0.55rem;"></i> Silver</span>
                        </div>
                    </div>

                    <!-- Tier Cards -->
                    <div class="rw-tiers">
                        <div class="rw-tier-card rw-tier-active">
                            <div class="rw-tier-icon" style="color:#cd7f32;"><i class="fas fa-medal"></i></div>
                            <div class="rw-tier-name">Bronze</div>
                            <div class="rw-tier-range">0 – 999 pts</div>
                            <div class="rw-tier-perk">5% off every booking</div>
                            <span class="rw-tier-badge">Current</span>
                        </div>
                        <div class="rw-tier-card">
                            <div class="rw-tier-icon" style="color:#9ca3af;"><i class="fas fa-medal"></i></div>
                            <div class="rw-tier-name">Silver</div>
                            <div class="rw-tier-range">1,000 – 2,499 pts</div>
                            <div class="rw-tier-perk">10% off + priority booking</div>
                        </div>
                        <div class="rw-tier-card">
                            <div class="rw-tier-icon" style="color:#f59e0b;"><i class="fas fa-crown"></i></div>
                            <div class="rw-tier-name">Gold</div>
                            <div class="rw-tier-range">2,500+ pts</div>
                            <div class="rw-tier-perk">15% off + free inspection</div>
                        </div>
                    </div>

                </div>

                <!-- PROMOS SECTION -->
                <div class="rw-section-header">
                    <div class="rw-section-title"><i class="fas fa-tag"></i> Promos & Offers</div>
                    <span class="card-badge" id="promoCount">4 active</span>
                </div>

                <div class="promos-grid" id="promosGrid"></div>

            </div><!-- /panel-rewards -->

        <!-- BOOKING MODAL -->
        <div class="modal-overlay" id="bookingModal" onclick="handleOverlayClick(event)">
            <div class="modal" id="modalBox">

                <!-- FORM VIEW -->
                <div id="modalFormView">
                    <div class="modal-header">
                        <div class="modal-header-info">
                            <div class="modal-eyebrow">New Booking</div>
                            <div class="modal-title" id="modalServiceName">Service Name</div>
                        </div>
                        <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
                    </div>

                    <div class="modal-body">
                        <!-- Service chip -->
                        <div class="modal-service-chip">
                            <i class="fas fa-wrench" id="modalServiceIcon"></i>
                            <div>
                                <div class="chip-name" id="modalServiceNameChip">—</div>
                                <div class="chip-cat" id="modalServiceCat">—</div>
                            </div>
                        </div>

                        <!-- Date & Time -->
                        <div class="form-row-2">
                            <div class="m-form-group">
                                <label class="m-label">Preferred Date</label>
                                <div class="m-input-wrap">
                                    <i class="fas fa-calendar"></i>
                                    <input class="m-control" id="mDate" type="date" required />
                                </div>
                            </div>
                            <div class="m-form-group">
                                <label class="m-label">Preferred Time</label>
                                <div class="m-input-wrap">
                                    <i class="fas fa-clock"></i>
                                    <input class="m-control" id="mTime" type="time" required />
                                </div>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div class="m-form-group">
                            <label class="m-label">Contact Number</label>
                            <div class="m-input-wrap">
                                <i class="fas fa-phone"></i>
                                <input class="m-control" id="mPhone" type="tel" placeholder="+63 9XX XXX XXXX" />
                            </div>
                        </div>

                        <!-- Vehicle Select -->
                        <div class="m-form-group">
                            <label class="m-label">Select Vehicle</label>
                            <div class="m-input-wrap">
                                <i class="fas fa-car"></i>
                                <select class="m-control m-select" id="mVehicleSelect">
                                    <option value="">— Choose a registered vehicle —</option>
                                </select>
                            </div>
                            <div class="m-vehicle-hint">
                                <i class="fas fa-circle-info" style="font-size:0.68rem;color:var(--red);"></i>
                                Vehicle not listed? <a onclick="switchSection(null,'vehicles'); closeModal()">Add it in My Vehicles</a>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="m-form-group">
                            <label class="m-label">Special Notes / Instructions</label>
                            <textarea class="m-control" id="mNotes" placeholder="Any specific concerns or instructions for the technician…"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                        <button class="btn-modal-submit" onclick="submitBooking()">
                            <i class="fas fa-calendar-check"></i>
                            Confirm Booking
                        </button>
                    </div>
                </div>

                <!-- SUCCESS VIEW -->
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
            <div class="footer-brand"><span>APX</span> AutoMai &mdash; Customer Portal &copy; 2025</div>
            <div>
                <a href="#">Privacy Policy</a>
                &nbsp;&middot;&nbsp;
                <a href="#">Terms &amp; Conditions</a>
            </div>
        </footer>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
    // ══════════════════════════════════════════════════════════════
    //  DATA
    // ══════════════════════════════════════════════════════════════
    let MY_VEHICLES = [
        { id: 'v-001', make: 'Honda Click 125i', year: '2022', plate: 'ABC 1234', color: 'Matte Black',   primary: true  },
        { id: 'v-002', make: 'Yamaha NMAX 155',  year: '2021', plate: 'XYZ 5678', color: 'Metallic Blue', primary: false },
    ];
    let vehicleIdCounter = 3;

    const SERVICES = [
        { id:'svc-01',  name:'Change Oil & Filter',                        cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-oil-can',        desc:'Complete engine oil drain and refill with high-quality oil and a fresh filter for optimal engine performance.',                         duration:'30–45 min',  free:false },
        { id:'svc-02',  name:'Fuel Injection Cleaning',                    cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-gas-pump',        desc:'Deep cleaning of fuel injectors to restore proper fuel atomization, improving throttle response and fuel economy.',                     duration:'45–60 min',  free:false },
        { id:'svc-03',  name:'Throttle Body Cleaning',                     cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-wind',            desc:'Remove carbon buildup and deposits from the throttle body for smoother idling and improved acceleration.',                              duration:'30–45 min',  free:false },
        { id:'svc-04',  name:'Throttle Idle Adjustment',                   cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-sliders',         desc:'Fine-tune idle speed to manufacturer specs, eliminating rough idle and stalling at traffic stops.',                                    duration:'20–30 min',  free:false },
        { id:'svc-05',  name:'Valve Clearance Adjustment / Tune-up',       cat:'engine',     catLabel:'Engine & Oil',       icon:'fa-screwdriver-wrench', desc:'Inspect and adjust valve clearances to ensure proper engine breathing, reducing noise and wear.',                                  duration:'60–90 min',  free:false },
        { id:'svc-06',  name:'CVT Cleaning and Inspection',                cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-gear',            desc:'Full inspection and cleaning of the Continuously Variable Transmission components to extend drivetrain life and smooth power delivery.',  duration:'60–90 min',  free:false },
        { id:'svc-07',  name:'Air Filter Inspection',                      cat:'inspection', catLabel:'Inspection',         icon:'fa-filter',          desc:'Visual and performance check of the air filter element to ensure clean airflow to the engine.',                                         duration:'15 min',     free:false },
        { id:'svc-08',  name:'Air Filter Installation',                    cat:'inspection', catLabel:'Inspection',         icon:'fa-filter',          desc:'Supply and installation of a new OEM-spec air filter for peak engine breathing.',                                                        duration:'20 min',     free:false },
        { id:'svc-09',  name:'Flyball Inspection',                         cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-dot',      desc:'Check flyball condition and wear for proper CVT engagement and smooth take-off.',                                                        duration:'30 min',     free:false },
        { id:'svc-10',  name:'Flyball Cleaning',                           cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-dot',      desc:'Thorough cleaning of flyball weights to remove grease and grime affecting CVT performance.',                                            duration:'30–45 min',  free:false },
        { id:'svc-11',  name:'V-belt Inspection',                          cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-bezier-curve',    desc:'Inspect V-belt for cracks, glazing, and wear to prevent unexpected belt failure.',                                                       duration:'20 min',     free:false },
        { id:'svc-12',  name:'V-belt Cleaning',                            cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-bezier-curve',    desc:'Clean V-belt surfaces and housing to remove debris that causes slipping and noise.',                                                     duration:'20–30 min',  free:false },
        { id:'svc-13',  name:'Pulley Set Inspection',                      cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-half-stroke', desc:'Full inspection of drive and driven pulley sets for wear, scoring, and proper movement.',                                           duration:'30 min',     free:false },
        { id:'svc-14',  name:'Pulley Set Cleaning',                        cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-half-stroke', desc:'Detailed cleaning of pulley faces and grooves to restore smooth belt travel and consistent CVT ratio changes.',                   duration:'30–45 min',  free:false },
        { id:'svc-15',  name:'Torque Drive Assy Inspection',               cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-rotate',          desc:'Inspect the torque drive assembly for wear and proper spring tension.',                                                                 duration:'30 min',     free:false },
        { id:'svc-16',  name:'Torque Drive Assy Cleaning',                 cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-rotate',          desc:'Clean all torque drive assembly components to remove built-up grease and contaminants.',                                               duration:'30–45 min',  free:false },
        { id:'svc-17',  name:'Torque Drive Assy Greasing',                 cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-rotate',          desc:'Apply fresh high-temperature grease to torque drive components for quiet, smooth operation.',                                          duration:'20–30 min',  free:false },
        { id:'svc-18',  name:'Clutch Lining Set / Assy Inspection',        cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-xmark',    desc:'Measure clutch lining thickness and check assembly condition to ensure positive engagement.',                                          duration:'30 min',     free:false },
        { id:'svc-19',  name:'Clutch Lining Set / Assy Cleaning',          cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-circle-xmark',    desc:'Clean clutch lining components and housing to restore grip and prevent glazing.',                                                      duration:'30–45 min',  free:false },
        { id:'svc-20',  name:'Kick Starter Inspection (if applicable)',    cat:'inspection', catLabel:'Inspection',         icon:'fa-person-walking',  desc:'Check kick starter mechanism for wear, proper engagement, and return spring condition.',                                               duration:'20 min',     free:false },
        { id:'svc-21',  name:'Pulley Shaving & Re-Angle',                  cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-screwdriver',     desc:'Precision machining of drive pulley face to optimize belt contact angle for improved performance.',                                    duration:'60–90 min',  free:false },
        { id:'svc-22',  name:'Pulley Drive Face Shaving & Re-Angle',       cat:'cvt',        catLabel:'CVT & Transmission', icon:'fa-screwdriver',     desc:'Re-angle and resurface the drive face pulley for maximum power transfer and acceleration.',                                            duration:'60–90 min',  free:false },
        { id:'svc-23',  name:'Sprocket/Chain Cleaning & Regreasing',       cat:'inspection', catLabel:'Inspection',         icon:'fa-link',            desc:'Clean and relube sprocket and chain drive components on chain-type motorcycles to reduce wear and noise.',                              duration:'30–45 min',  free:false },
        { id:'svc-24',  name:'Pipe Cleaning',                              cat:'brakes',     catLabel:'Brakes & Pipes',     icon:'fa-pipe',            desc:'Flush and clean fuel and coolant pipes to remove blockages, corrosion, and sediment buildup.',                                         duration:'30 min',     free:false },
        { id:'svc-25',  name:'Brake Cleaning',                             cat:'brakes',     catLabel:'Brakes & Pipes',     icon:'fa-circle-stop',     desc:'Degrease brake pads, discs, and drums to restore maximum braking bite and eliminate brake squeal.',                                    duration:'30–45 min',  free:false },
        { id:'svc-26',  name:'Brake Adjustment',                           cat:'brakes',     catLabel:'Brakes & Pipes',     icon:'fa-circle-stop',     desc:'Adjust brake cable tension and drum/disc clearance for firm, consistent brake feel.',                                                 duration:'20–30 min',  free:false },
        { id:'svc-27',  name:'FREE ECU Diagnose (Reset if Applicable)',    cat:'free',       catLabel:'Free Service',       icon:'fa-microchip',       desc:'Complimentary ECU scan using professional diagnostic tools. Error codes reset where applicable.',                                       duration:'15–30 min',  free:true  },
        { id:'svc-28',  name:'FREE Basic Inspection',                      cat:'free',       catLabel:'Free Service',       icon:'fa-clipboard-check', desc:'Complimentary 20-point visual inspection of your motorcycle covering engine, brakes, CVT, and electrical.',                            duration:'20–30 min',  free:true  },
    ];

    const BOOKINGS = [
        { id:'#BK-0043', service:'Full Car Wash + Interior Detailing', date:'2024-01-20', day:'20', mon:'Jan', yr:'2024', time:'10:00 AM', staff:'Marco R.', vehicle:'Toyota Vios (ABC 123)', amount:'₱1,550', status:'upcoming' },
        { id:'#BK-0042', service:'Oil Change',                         date:'2024-01-28', day:'28', mon:'Jan', yr:'2024', time:'2:00 PM',  staff:'Leo T.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱800',   status:'upcoming' },
        { id:'#BK-0041', service:'Engine Check',                       date:'2024-01-15', day:'15', mon:'Jan', yr:'2024', time:'9:00 AM',  staff:'Jay M.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱600',   status:'inprogress' },
        { id:'#BK-0038', service:'Full Car Wash',                      date:'2024-01-10', day:'10', mon:'Jan', yr:'2024', time:'11:00 AM', staff:'Marco R.', vehicle:'Toyota Vios (ABC 123)', amount:'₱350',   status:'completed' },
        { id:'#BK-0034', service:'Interior Detailing',                 date:'2024-01-05', day:'05', mon:'Jan', yr:'2024', time:'1:00 PM',  staff:'Jay M.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱1,200', status:'completed' },
        { id:'#BK-0030', service:'Oil Change',                         date:'2023-12-28', day:'28', mon:'Dec', yr:'2023', time:'3:00 PM',  staff:'Leo T.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱800',   status:'completed' },
        { id:'#BK-0026', service:'Paint Protection',                   date:'2023-12-20', day:'20', mon:'Dec', yr:'2023', time:'9:00 AM',  staff:'Marco R.', vehicle:'Toyota Vios (ABC 123)', amount:'₱2,500', status:'completed' },
        { id:'#BK-0022', service:'Engine Check',                       date:'2023-12-14', day:'14', mon:'Dec', yr:'2023', time:'10:00 AM', staff:'Jay M.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱600',   status:'completed' },
        { id:'#BK-0018', service:'Tire Rotation',                      date:'2023-12-08', day:'08', mon:'Dec', yr:'2023', time:'2:00 PM',  staff:'Leo T.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱400',   status:'completed' },
        { id:'#BK-0014', service:'Full Car Wash',                      date:'2023-11-30', day:'30', mon:'Nov', yr:'2023', time:'11:00 AM', staff:'Marco R.', vehicle:'Toyota Vios (ABC 123)', amount:'₱350',   status:'completed' },
        { id:'#BK-0010', service:'Interior Detailing',                 date:'2023-11-20', day:'20', mon:'Nov', yr:'2023', time:'1:00 PM',  staff:'Jay M.',   vehicle:'Toyota Vios (ABC 123)', amount:'₱1,200', status:'cancelled' },
    ];

    const STATUS_META = {
        upcoming:   { label:'Upcoming',    cls:'badge-confirmed',  icon:'fa-clock' },
        inprogress: { label:'In Progress', cls:'badge-inprogress', icon:'fa-rotate' },
        completed:  { label:'Completed',   cls:'badge-completed',  icon:'fa-circle-check' },
        cancelled:  { label:'Cancelled',   cls:'badge-cancelled',  icon:'fa-ban' },
    };

    // ══════════════════════════════════════════════════════════════
    //  STATE
    // ══════════════════════════════════════════════════════════════
    let currentFilter  = 'all';
    let currentSearch  = '';
    let currentCat     = 'all';
    let currentSvcSearch = '';
    let bookingCounter = 44;

    // ══════════════════════════════════════════════════════════════
    //  SECTION TABS
    // ══════════════════════════════════════════════════════════════
    const PAGE_TITLES = {
        dashboard: ['MY <span>DASHBOARD</span>',  'Dashboard'],
        bookings:  ['MY <span>BOOKINGS</span>',    'My Bookings'],
        services:  ['BOOK A <span>SERVICE</span>', 'Book a Service'],
        vehicles:  ['MY <span>VEHICLES</span>',    'My Vehicles'],
        rewards:   ['MY <span>REWARDS</span>',      'Rewards'],
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
        document.getElementById('pageTitle').innerHTML   = PAGE_TITLES[section][0];
        document.getElementById('pageBreadcrumb').textContent = PAGE_TITLES[section][1];
        if (section === 'vehicles') renderVehicles();
    }

    // ══════════════════════════════════════════════════════════════
    //  MY BOOKINGS
    // ══════════════════════════════════════════════════════════════
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
        list.innerHTML = filtered.map(b => {
            const meta = STATUS_META[b.status];
            return `<div class="bk-card">
                <div class="bk-card-inner">
                    <div class="bk-date-col">
                        <span class="bk-day">${b.day}</span>
                        <span class="bk-mon">${b.mon}</span>
                        <span class="bk-yr">${b.yr}</span>
                    </div>
                    <div class="bk-body">
                        <div class="bk-title-row">
                            <span class="bk-service">${b.service}</span>
                            <span class="bk-id">${b.id}</span>
                        </div>
                        <div class="bk-meta">
                            <span><i class="fas fa-clock"></i>${b.time}</span>
                            <span><i class="fas fa-user"></i>${b.staff}</span>
                            <span><i class="fas fa-car"></i>${b.vehicle}</span>
                        </div>
                    </div>
                    <div class="bk-status-col">
                        <span class="badge ${meta.cls}"><i class="fas ${meta.icon}"></i>${meta.label}</span>
                        <span class="bk-amount">${b.amount}</span>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    // ══════════════════════════════════════════════════════════════
    //  SERVICES GRID
    // ══════════════════════════════════════════════════════════════
    function setCat(btn) {
        document.querySelectorAll('.cat-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCat = btn.dataset.cat;
        filterServices();
    }

    function filterServices() {
        currentSvcSearch = document.getElementById('serviceSearch').value.toLowerCase();
        const grid = document.getElementById('servicesGrid');
        const filtered = SERVICES.filter(s => {
            const matchCat    = currentCat === 'all' || s.cat === currentCat;
            const matchSearch = !currentSvcSearch ||
                s.name.toLowerCase().includes(currentSvcSearch) ||
                s.desc.toLowerCase().includes(currentSvcSearch) ||
                s.catLabel.toLowerCase().includes(currentSvcSearch);
            return matchCat && matchSearch;
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

    // ══════════════════════════════════════════════════════════════
    //  BOOKING MODAL
    // ══════════════════════════════════════════════════════════════
    let selectedService = null;

    function openModal(svcId) {
        selectedService = SERVICES.find(s => s.id === svcId);
        if (!selectedService) return;

        // Reset to form view
        document.getElementById('modalFormView').style.display  = 'block';
        document.getElementById('modalSuccessView').classList.remove('show');

        // Populate
        document.getElementById('modalServiceName').textContent      = selectedService.name;
        document.getElementById('modalServiceNameChip').textContent  = selectedService.name;
        document.getElementById('modalServiceCat').textContent       = selectedService.catLabel + ' · ' + selectedService.duration;
        document.getElementById('modalServiceIcon').className        = 'fas ' + selectedService.icon;

        // Default date = tomorrow
        const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('mDate').value  = tomorrow.toISOString().split('T')[0];
        document.getElementById('mTime').value  = '09:00';
        document.getElementById('mPhone').value = '';
        document.getElementById('mNotes').value = '';

        // Populate vehicle dropdown
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
        const date  = document.getElementById('mDate').value;
        const time  = document.getElementById('mTime').value;
        const phone = document.getElementById('mPhone').value;
        const vehicleId = document.getElementById('mVehicleSelect').value;

        if (!date || !time) {
            document.getElementById('mDate').focus();
            document.getElementById('mDate').style.borderColor = 'var(--red)';
            setTimeout(() => document.getElementById('mDate').style.borderColor = '', 1500);
            return;
        }

        // Resolve vehicle label from registered vehicles
        const vehicle = MY_VEHICLES.find(v => v.id === vehicleId);
        const vehicleLabel = vehicle
            ? vehicle.make + (vehicle.plate ? ' (' + vehicle.plate + ')' : '')
            : 'N/A';

        // Generate a booking reference
        const refNo = '#BK-' + String(bookingCounter++).padStart(4, '0');
        const formattedDate = new Date(date).toLocaleDateString('en-US', { weekday:'short', month:'long', day:'numeric', year:'numeric' });
        const formattedTime = new Date('1970-01-01T' + time).toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit' });

        // Add to BOOKINGS array (simulated)
        const [d, m, y] = [
            new Date(date).getDate().toString().padStart(2,'0'),
            new Date(date).toLocaleString('en-US',{month:'short'}),
            new Date(date).getFullYear().toString()
        ];
        BOOKINGS.unshift({
            id: refNo,
            service: selectedService.name,
            date, day: d, mon: m, yr: y,
            time: formattedTime,
            staff: 'TBA',
            vehicle: vehicleLabel,
            amount: selectedService.free ? '₱0 (Free)' : 'TBA',
            status: 'upcoming'
        });

        // Show success screen
        document.getElementById('modalFormView').style.display   = 'none';
        document.getElementById('modalSuccessView').classList.add('show');
        document.getElementById('modalRefNo').textContent        = refNo;
        document.getElementById('modalSuccessDate').textContent  = formattedDate + ' at ' + formattedTime;
        document.getElementById('modalSuccessService').textContent = selectedService.name;
    }

    // ══════════════════════════════════════════════════════════════
    //  REWARDS
    // ══════════════════════════════════════════════════════════════
    const PROMOS = [
        {
            id: 'promo-01',
            tag: 'Oil Service',
            tagIcon: 'fa-oil-can',
            accentColor: '#E8192C',
            title: '₱100 Off Oil Change',
            desc: 'Get ₱100 off your next Change Oil & Filter service. Valid for all vehicle types.',
            code: 'OIL100',
            expiry: 'Jan 31, 2025',
        },
        {
            id: 'promo-02',
            tag: 'CVT Service',
            tagIcon: 'fa-gear',
            accentColor: '#3B82F6',
            title: 'Free CVT Inspection',
            desc: 'Book any CVT cleaning service and get a complimentary CVT inspection at no extra cost.',
            code: 'CVTFREE',
            expiry: 'Feb 15, 2025',
        },
        {
            id: 'promo-03',
            tag: 'New Customer',
            tagIcon: 'fa-user-plus',
            accentColor: '#22C55E',
            title: '10% Off First Booking',
            desc: 'First-time customers enjoy 10% off any single service booking. One-time use only.',
            code: 'WELCOME10',
            expiry: 'Mar 31, 2025',
        },
        {
            id: 'promo-04',
            tag: 'Bundle Deal',
            tagIcon: 'fa-boxes-stacked',
            accentColor: '#F59E0B',
            title: 'Brake + Pipe Bundle',
            desc: 'Book Brake Cleaning and Pipe Cleaning together and save ₱150 off the combined price.',
            code: 'BUNDLE150',
            expiry: 'Feb 28, 2025',
        },
    ];

    function renderPromos() {
        const grid = document.getElementById('promosGrid');
        if (!grid) return;
        document.getElementById('promoCount').textContent = PROMOS.length + ' active';
        grid.innerHTML = PROMOS.map(p => `
            <div class="promo-card">
                <div class="promo-card-accent" style="background:${p.accentColor};"></div>
                <div class="promo-card-body">
                    <div class="promo-tag" style="color:${p.accentColor};"><i class="fas ${p.tagIcon}"></i>${p.tag}</div>
                    <div class="promo-title">${p.title}</div>
                    <div class="promo-desc">${p.desc}</div>
                    <div class="promo-card-footer">
                        <span class="promo-expiry"><i class="fas fa-clock"></i>Expires ${p.expiry}</span>
                        <div class="promo-code-wrap">
                            <span class="promo-code">${p.code}</span>
                            <button class="btn-copy-code" onclick="copyCode(this, '${p.code}')" title="Copy code">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`).join('');
    }

    function copyCode(btn, code) {
        navigator.clipboard.writeText(code).then(() => {
            btn.classList.add('copied');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.innerHTML = '<i class="fas fa-copy"></i>';
            }, 1800);
        });
    }

    // ══════════════════════════════════════════════════════════════
    //  MY VEHICLES
    // ══════════════════════════════════════════════════════════════
    function renderVehicles() {
        const grid  = document.getElementById('vehiclesGrid');
        const empty = document.getElementById('vehiclesEmpty');
        document.getElementById('tabCountVehicles').textContent = MY_VEHICLES.length;

        if (!MY_VEHICLES.length) {
            grid.innerHTML = '';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
        grid.innerHTML = MY_VEHICLES.map(v => `
            <div class="vehicle-card ${v.primary ? 'primary-vehicle' : ''}" id="vcard-${v.id}">
                <div class="vehicle-card-banner">
                    ${v.primary ? '<span class="vehicle-primary-badge"><i class="fas fa-star" style="margin-right:3px;font-size:0.55rem;"></i>Primary</span>' : ''}
                    <i class="fas fa-motorcycle"></i>
                </div>
                <div class="vehicle-card-body">
                    <div class="vehicle-name">${v.make}</div>
                    <div class="vehicle-plate">${v.plate || 'No plate'}</div>
                    <div class="vehicle-meta-row">
                        ${v.year  ? `<span><i class="fas fa-calendar"></i>${v.year}</span>` : ''}
                        ${v.color ? `<span><i class="fas fa-palette"></i>${v.color}</span>` : ''}
                    </div>
                    <div class="vehicle-card-actions">
                        ${!v.primary ? `<button class="btn-veh-action" onclick="setPrimaryVehicle('${v.id}')"><i class="fas fa-star"></i> Set Primary</button>` : '<button class="btn-veh-action" disabled style="opacity:0.4;cursor:default;"><i class="fas fa-star" style="color:var(--warning);"></i> Primary</button>'}
                        <button class="btn-veh-action danger" onclick="deleteVehicle('${v.id}')"><i class="fas fa-trash"></i> Remove</button>
                    </div>
                </div>
            </div>`).join('');
    }

    function toggleAddVehicleForm() {
        const form = document.getElementById('addVehicleForm');
        form.classList.toggle('open');
        if (form.classList.contains('open')) {
            document.getElementById('avfMake').value  = '';
            document.getElementById('avfYear').value  = '';
            document.getElementById('avfPlate').value = '';
            document.getElementById('avfColor').value = '';
            setTimeout(() => document.getElementById('avfMake').focus(), 100);
        }
    }

    function saveNewVehicle() {
        const make  = document.getElementById('avfMake').value.trim();
        const year  = document.getElementById('avfYear').value.trim();
        const plate = document.getElementById('avfPlate').value.trim();
        const color = document.getElementById('avfColor').value.trim();

        if (!make) {
            document.getElementById('avfMake').focus();
            document.getElementById('avfMake').style.borderColor = 'var(--red)';
            setTimeout(() => document.getElementById('avfMake').style.borderColor = '', 1500);
            return;
        }

        MY_VEHICLES.push({
            id: 'v-' + String(vehicleIdCounter++).padStart(3, '0'),
            make, year, plate, color,
            primary: MY_VEHICLES.length === 0
        });

        toggleAddVehicleForm();
        renderVehicles();
    }

    function setPrimaryVehicle(id) {
        MY_VEHICLES = MY_VEHICLES.map(v => ({ ...v, primary: v.id === id }));
        renderVehicles();
    }

    function deleteVehicle(id) {
        const wasPrimary = MY_VEHICLES.find(v => v.id === id)?.primary;
        MY_VEHICLES = MY_VEHICLES.filter(v => v.id !== id);
        if (wasPrimary && MY_VEHICLES.length) MY_VEHICLES[0].primary = true;
        renderVehicles();
    }

    // ══════════════════════════════════════════════════════════════
    //  INIT
    // ══════════════════════════════════════════════════════════════
    document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });

    document.getElementById('sidebarToggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('collapsed');
        document.getElementById('mainContent').classList.toggle('expanded');
    });

    // Pre-render
    renderBookings();
    filterServices();
    renderPromos();

    // ── Dark / Light mode toggle ──────────────────────────────
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

    // Use shared preference key so admin & customer portals stay in sync
    const savedTheme = localStorage.getItem('apx-theme') || 'dark';
    applyTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const next = htmlEl.classList.contains('light-mode') ? 'dark' : 'light';
        localStorage.setItem('apx-theme', next);
        applyTheme(next);
    });
</script>
</body>
</html>