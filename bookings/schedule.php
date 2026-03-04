<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/automai/config.php'; ?>
<?php
// bookings/schedule.php — Today's Schedule
$pageTitle     = "Today's Schedule";
$activeSection = 'bookings';
$activePage    = 'schedule';
$today         = date('l, F j, Y');

// ── TODO: Replace with DB query filtered to today ────────────────────────
// $bookings = $conn->query("SELECT * FROM bookings WHERE DATE(datetime)=CURDATE() ORDER BY datetime ASC");
// ────────────────────────────────────────────────────────────────────────

$schedule = [
    '08:00' => [
        ['id'=>'#BK-0041','customer'=>'Juan dela Cruz','plate'=>'ABC 1234','service'=>'Full Car Wash','status'=>'inprogress','duration'=>'1 hr'],
    ],
    '09:00' => [],
    '09:30' => [
        ['id'=>'#BK-0044','customer'=>'Maria Santos','plate'=>'XYZ 5678','service'=>'Oil Change','status'=>'confirmed','duration'=>'45 min'],
    ],
    '10:00' => [],
    '10:30' => [
        ['id'=>'#BK-0045','customer'=>'Roberto Lim','plate'=>'DEF 9012','service'=>'Interior Detailing','status'=>'confirmed','duration'=>'2 hrs'],
    ],
    '11:00' => [],
    '11:30' => [],
    '13:00' => [
        ['id'=>'#BK-0046','customer'=>'Ana Reyes','plate'=>'GHI 3456','service'=>'Paint Protection','status'=>'pending','duration'=>'3 hrs'],
        ['id'=>'#BK-0047','customer'=>'Carlo Mendoza','plate'=>'JKL 7890','service'=>'Tire Rotation','status'=>'confirmed','duration'=>'30 min'],
    ],
    '14:00' => [],
    '14:30' => [
        ['id'=>'#BK-0048','customer'=>'Lisa Tan','plate'=>'MNO 1234','service'=>'Full Car Wash','status'=>'confirmed','duration'=>'1 hr'],
    ],
    '15:00' => [],
    '15:30' => [
        ['id'=>'#BK-0049','customer'=>'Paulo Garcia','plate'=>'PQR 5678','service'=>'Engine Check','status'=>'pending','duration'=>'1.5 hrs'],
    ],
    '16:00' => [],
    '16:30' => [],
];

$statusClass = ['confirmed'=>'confirmed','inprogress'=>'inprogress','pending'=>'pending','cancelled'=>'cancelled'];
$statusLabel = ['confirmed'=>'Confirmed','inprogress'=>'In Progress','pending'=>'Pending','cancelled'=>'Cancelled'];
$statusBadge = ['confirmed'=>'badge-confirmed','inprogress'=>'badge-inprogress','pending'=>'badge-pending','cancelled'=>'badge-cancelled'];

$totalToday = 0;
foreach($schedule as $events) $totalToday += count($events);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title><?= $pageTitle ?> — APX AutoMai</title>
    <?php include '../includes/styles.php'; ?>
    <style>
    .now-line {
        display:flex;align-items:center;gap:10px;margin-bottom:4px;
    }
    .now-line::before {
        content:'NOW';font-family:'Barlow Condensed',sans-serif;font-size:.65rem;
        font-weight:800;letter-spacing:.1em;color:var(--red);background:var(--red-glow);
        padding:1px 6px;border-radius:3px;
    }
    .now-line::after {
        content:'';flex:1;height:1px;background:var(--red);opacity:.5;
    }
    .staff-pill {
        display:inline-flex;align-items:center;gap:5px;background:var(--surface-3);
        border:1px solid var(--border);border-radius:20px;padding:2px 10px 2px 4px;
        font-size:.75rem;color:var(--text-muted);
    }
    .staff-avatar {
        width:20px;height:20px;border-radius:50%;background:var(--red);
        display:flex;align-items:center;justify-content:center;
        font-size:.6rem;font-weight:700;color:#fff;
    }
    .date-nav {
        display:flex;align-items:center;gap:8px;
    }
    </style>
</head>
<body>

<?php include '../includes/topnav.php'; ?>

<div class="layout">
<?php include '../includes/sidebar.php'; ?>

<div class="main-content" id="mainContent">
<main>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Today's <span>Schedule</span></h1>
            <ol class="breadcrumb">
                <li>Bookings</li>
                <li class="active">Today's Schedule</li>
            </ol>
        </div>
        <div class="date-nav">
            <button class="btn btn-ghost btn-sm"><i class="fas fa-chevron-left"></i></button>
            <span style="font-family:'Barlow Condensed',sans-serif;font-size:1rem;font-weight:700;color:var(--text);"><?= $today ?></span>
            <button class="btn btn-ghost btn-sm"><i class="fas fa-chevron-right"></i></button>
            <a href="<?= BASE_URL ?>/bookings/new.php" class="btn btn-primary btn-sm" style="margin-left:8px;"><i class="fas fa-plus"></i> Add</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start;">

        <!-- TIMELINE -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-title"><i class="fas fa-clock"></i> Timeline</div>
                <span style="font-size:.8rem;color:var(--text-muted);"><?= $totalToday ?> bookings today</span>
            </div>
            <div class="card-body" style="padding:20px 20px 20px 0;">
                <div class="timeline">
                    <?php
                    $currentHour = (int)date('H');
                    $currentMin  = (int)date('i');
                    $nowInserted = false;
                    foreach($schedule as $time => $events):
                        [$h,$m] = explode(':',$time);
                        $slotMins = (int)$h*60+(int)$m;
                        $nowMins  = $currentHour*60+$currentMin;
                        if(!$nowInserted && $slotMins > $nowMins):
                            $nowInserted = true;
                    ?>
                    <div style="padding:6px 0 6px 96px;"><div class="now-line"></div></div>
                    <?php endif; ?>
                    <div class="time-slot">
                        <div class="time-label"><?= date('g:i A', strtotime($time)) ?></div>
                        <div class="time-events">
                            <?php if(empty($events)): ?>
                            <div style="height:32px;border-radius:4px;background:rgba(255,255,255,0.02);border:1px dashed rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;">
                                <span style="font-size:.72rem;color:rgba(255,255,255,0.1);">available</span>
                            </div>
                            <?php else: foreach($events as $ev): ?>
                            <div class="event-block <?= $statusClass[$ev['status']] ?>">
                                <div>
                                    <div class="event-title"><?= htmlspecialchars($ev['service']) ?></div>
                                    <div class="event-meta">
                                        <?= htmlspecialchars($ev['customer']) ?> &middot;
                                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:700;letter-spacing:.04em;"><?= $ev['plate'] ?></span>
                                    </div>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                                    <span style="font-size:.75rem;color:var(--text-muted);"><?= $ev['duration'] ?></span>
                                    <span class="badge <?= $statusBadge[$ev['status']] ?>"><?= $statusLabel[$ev['status']] ?></span>
                                    <div style="display:flex;gap:4px;">
                                        <button class="btn btn-ghost btn-sm btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                        <a href="<?= BASE_URL ?>/bookings/new.php?edit=<?= urlencode($ev['id']) ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- SIDEBAR PANEL -->
        <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- DAILY SUMMARY -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-chart-pie"></i> Today's Summary</div>
                </div>
                <div class="card-body" style="padding:16px;">
                    <?php
                    $statuses = ['inprogress'=>0,'confirmed'=>0,'pending'=>0,'cancelled'=>0];
                    foreach($schedule as $events)
                        foreach($events as $ev) $statuses[$ev['status']]++;
                    $summaryItems = [
                        ['label'=>'In Progress','key'=>'inprogress','color'=>'var(--info)'],
                        ['label'=>'Confirmed',  'key'=>'confirmed', 'color'=>'var(--success)'],
                        ['label'=>'Pending',    'key'=>'pending',   'color'=>'var(--warning)'],
                        ['label'=>'Cancelled',  'key'=>'cancelled', 'color'=>'var(--red)'],
                    ];
                    foreach($summaryItems as $si): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:8px;height:8px;border-radius:50%;background:<?= $si['color'] ?>;"></div>
                            <span style="font-size:.83rem;"><?= $si['label'] ?></span>
                        </div>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1.1rem;"><?= $statuses[$si['key']] ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding-top:10px;">
                        <span style="font-size:.83rem;font-weight:600;">Total</span>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.3rem;color:var(--red);"><?= $totalToday ?></span>
                    </div>
                </div>
            </div>

            <!-- QUICK ADD -->
            <div class="card" style="border-color:rgba(232,25,44,.2);">
                <div class="card-body" style="padding:16px;">
                    <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:12px;">Need to add a walk-in booking?</p>
                    <a href="<?= BASE_URL ?>/bookings/new.php" class="btn btn-primary" style="width:100%;justify-content:center;">
                        <i class="fas fa-plus"></i> New Booking
                    </a>
                </div>
            </div>

            <!-- LEGEND -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title" style="font-size:.8rem;"><i class="fas fa-circle-info"></i> Legend</div>
                </div>
                <div class="card-body" style="padding:12px 16px;">
                    <?php
                    $legend = [
                        ['color'=>'var(--info)',   'label'=>'In Progress'],
                        ['color'=>'var(--success)','label'=>'Confirmed'],
                        ['color'=>'var(--warning)','label'=>'Pending'],
                        ['color'=>'var(--red)',    'label'=>'Cancelled'],
                    ];
                    foreach($legend as $l): ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:4px 0;">
                        <div style="width:12px;height:12px;border-radius:2px;background:<?= $l['color'] ?>;flex-shrink:0;"></div>
                        <span style="font-size:.8rem;color:var(--text-muted);"><?= $l['label'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>