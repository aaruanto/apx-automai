<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/automai/config.php'; ?>
<?php
// customers/loyalty.php — Loyalty Members
$pageTitle     = 'Loyalty Members';
$activeSection = 'customers';
$activePage    = 'loyalty';

// ── TODO: Replace with DB query ────────────────────────────────────────────
// $members = $conn->query("SELECT * FROM customers WHERE loyalty IN ('bronze','silver','gold') ORDER BY loyalty DESC, bookings DESC");
// ──────────────────────────────────────────────────────────────────────────

$members = [
    ['id'=>'C-001','name'=>'Juan dela Cruz', 'phone'=>'09171234567','plate'=>'ABC 1234','bookings'=>12,'last_visit'=>'2024-01-15','loyalty'=>'gold'],
    ['id'=>'C-007','name'=>'Paulo Garcia',   'phone'=>'09837890123','plate'=>'PQR 5678','bookings'=>15,'last_visit'=>'2024-01-12','loyalty'=>'gold'],
    ['id'=>'C-002','name'=>'Maria Santos',   'phone'=>'09282345678','plate'=>'XYZ 5678','bookings'=>8, 'last_visit'=>'2024-01-15','loyalty'=>'silver'],
    ['id'=>'C-003','name'=>'Roberto Lim',    'phone'=>'09393456789','plate'=>'DEF 9012','bookings'=>5, 'last_visit'=>'2024-01-14','loyalty'=>'silver'],
    ['id'=>'C-005','name'=>'Carlo Mendoza',  'phone'=>'09615678901','plate'=>'JKL 7890','bookings'=>7, 'last_visit'=>'2024-01-13','loyalty'=>'silver'],
    ['id'=>'C-009','name'=>'Ben Cruz',       'phone'=>'09159012345','plate'=>'VWX 3456','bookings'=>6, 'last_visit'=>'2024-01-11','loyalty'=>'silver'],
    ['id'=>'C-004','name'=>'Ana Reyes',      'phone'=>'09504567890','plate'=>'GHI 3456','bookings'=>3, 'last_visit'=>'2024-01-14','loyalty'=>'bronze'],
    ['id'=>'C-006','name'=>'Lisa Tan',       'phone'=>'09726789012','plate'=>'MNO 1234','bookings'=>1, 'last_visit'=>'2024-01-13','loyalty'=>'bronze'],
    ['id'=>'C-008','name'=>'Diane Uy',       'phone'=>'09948901234','plate'=>'STU 9012','bookings'=>4, 'last_visit'=>'2024-01-12','loyalty'=>'bronze'],
    ['id'=>'C-010','name'=>'Nina Flores',    'phone'=>'09260123456','plate'=>'YZA 7890','bookings'=>2, 'last_visit'=>'2024-01-11','loyalty'=>'bronze'],
];

$tierConfig = [
    'gold'   => ['label'=>'Gold',  'color'=>'#F59E0B','bg'=>'rgba(245,158,11,0.12)','border'=>'rgba(245,158,11,0.3)','icon'=>'fa-star',           'min'=>10,'desc'=>'10+ bookings'],
    'silver' => ['label'=>'Silver','color'=>'#94A3B8','bg'=>'rgba(148,163,184,0.12)','border'=>'rgba(148,163,184,0.3)','icon'=>'fa-star-half-stroke','min'=>5, 'desc'=>'5–9 bookings'],
    'bronze' => ['label'=>'Bronze','color'=>'#CD7C4F','bg'=>'rgba(205,124,79,0.12)', 'border'=>'rgba(205,124,79,0.3)', 'icon'=>'fa-circle',          'min'=>1, 'desc'=>'1–4 bookings'],
];

$grouped = ['gold'=>[],'silver'=>[],'bronze'=>[]];
foreach($members as $m) $grouped[$m['loyalty']][] = $m;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title><?= $pageTitle ?> — APX AutoMai</title>
    <?php include '../includes/styles.php'; ?>
    <style>
    .member-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: border-color .2s, transform .15s;
        text-decoration: none;
        color: inherit;
    }
    .member-card:hover { transform: translateY(-2px); }
    .member-card.gold   { border-color: rgba(245,158,11,0.2); }
    .member-card.silver { border-color: rgba(148,163,184,0.2); }
    .member-card.bronze { border-color: rgba(205,124,79,0.15); }
    .member-avatar {
        width: 42px; height: 42px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Barlow Condensed', sans-serif;
        font-weight: 800; font-size: .9rem;
        flex-shrink: 0;
    }
    .tier-section-header {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 14px; margin-top: 8px;
    }
    .tier-section-header .tier-icon {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
    }
    .tier-section-header .tier-title {
        font-family: 'Barlow Condensed', sans-serif;
        font-size: 1.3rem; font-weight: 800; letter-spacing: .04em;
    }
    .tier-section-header .tier-desc { font-size: .78rem; color: var(--text-muted); }
    .tier-section-header .tier-count {
        margin-left: auto;
        font-family: 'Barlow Condensed', sans-serif;
        font-size: 1.6rem; font-weight: 800; opacity: .5;
    }
    .progress-bar-wrap {
        height: 4px; background: var(--surface-3); border-radius: 2px; margin-top: 6px; width: 100%;
    }
    .progress-bar-fill { height: 100%; border-radius: 2px; }
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
            <h1 class="page-title">Loyalty <span>Members</span></h1>
            <ol class="breadcrumb">
                <li>Customers</li>
                <li class="active">Loyalty Members</li>
            </ol>
        </div>
        <a href="<?= BASE_URL ?>/customers/index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> All Customers</a>
    </div>

    <!-- TIER SUMMARY CARDS -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;">
        <?php foreach($tierConfig as $key => $tc):
            $count = count($grouped[$key]);
        ?>
        <div style="background:var(--surface);border:1px solid <?= $tc['border'] ?>;border-radius:10px;padding:20px;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-10px;right:-10px;font-size:4rem;opacity:.04;color:<?= $tc['color'] ?>;"><i class="fas <?= $tc['icon'] ?>"></i></div>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <div style="width:40px;height:40px;border-radius:10px;background:<?= $tc['bg'] ?>;display:flex;align-items:center;justify-content:center;color:<?= $tc['color'] ?>;">
                    <i class="fas <?= $tc['icon'] ?>"></i>
                </div>
                <div>
                    <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:800;color:<?= $tc['color'] ?>;"><?= $tc['label'] ?></div>
                    <div style="font-size:.73rem;color:var(--text-muted);"><?= $tc['desc'] ?></div>
                </div>
            </div>
            <div style="font-family:'Barlow Condensed',sans-serif;font-size:2.2rem;font-weight:800;line-height:1;"><?= $count ?></div>
            <div style="font-size:.73rem;color:var(--text-muted);">members</div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- TIER SECTIONS -->
    <?php foreach($tierConfig as $key => $tc):
        if(empty($grouped[$key])) continue;
        $maxBookings = max(array_column($grouped[$key], 'bookings'));
    ?>
    <div style="margin-bottom:32px;">
        <div class="tier-section-header">
            <div class="tier-icon" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;"><i class="fas <?= $tc['icon'] ?>"></i></div>
            <div>
                <div class="tier-title" style="color:<?= $tc['color'] ?>;"><?= $tc['label'] ?> Members</div>
                <div class="tier-desc"><?= $tc['desc'] ?></div>
            </div>
            <div class="tier-count"><?= count($grouped[$key]) ?></div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;">
            <?php foreach($grouped[$key] as $i => $m):
                $initials = strtoupper(substr($m['name'],0,1).substr(strrchr($m['name'],' '),1,1));
                $pct      = $maxBookings > 0 ? round(($m['bookings']/$maxBookings)*100) : 0;
                // bookings needed to next tier
                $nextTier = ['bronze'=>5,'silver'=>10,'gold'=>null];
                $needed   = $nextTier[$key] ? max(0,$nextTier[$key]-$m['bookings']) : 0;
            ?>
            <div class="member-card <?= $key ?>">
                <div class="member-avatar" style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;"><?= $initials ?></div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($m['name']) ?></div>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1rem;flex-shrink:0;color:<?= $tc['color'] ?>;"><?= $m['bookings'] ?>x</span>
                    </div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-top:1px;"><?= $m['phone'] ?> &middot; <?= $m['plate'] ?></div>
                    <!-- progress bar -->
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $tc['color'] ?>;"></div>
                    </div>
                    <?php if($needed > 0): ?>
                    <div style="font-size:.7rem;color:var(--text-muted);margin-top:3px;"><?= $needed ?> more to next tier</div>
                    <?php else: ?>
                    <div style="font-size:.7rem;color:<?= $tc['color'] ?>;margin-top:3px;"><i class="fas fa-trophy" style="font-size:.65rem;"></i> Top tier</div>
                    <?php endif; ?>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;">
                    <a href="<?= BASE_URL ?>/customers/new.php?edit=<?= urlencode($m['id']) ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>