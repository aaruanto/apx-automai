<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/automai/config.php'; ?>
<?php
// customers/index.php — All Customers
$pageTitle     = 'All Customers';
$activeSection = 'customers';
$activePage    = 'all';

// ── TODO: Replace with DB query ────────────────────────────────────────────
// $customers = $conn->query("SELECT c.*, COUNT(b.id) as total_bookings,
//     MAX(b.datetime) as last_visit FROM customers c
//     LEFT JOIN bookings b ON b.customer_id = c.id
//     GROUP BY c.id ORDER BY c.name ASC");
// ──────────────────────────────────────────────────────────────────────────

$customers = [
    ['id'=>'C-001','name'=>'Juan dela Cruz',   'phone'=>'09171234567','email'=>'juan@email.com',   'plate'=>'ABC 1234','model'=>'Toyota Vios 2021',     'bookings'=>12,'last_visit'=>'2024-01-15'],
    ['id'=>'C-002','name'=>'Maria Santos',      'phone'=>'09282345678','email'=>'maria@email.com',  'plate'=>'XYZ 5678','model'=>'Honda City 2020',      'bookings'=>8, 'last_visit'=>'2024-01-15'],
    ['id'=>'C-003','name'=>'Roberto Lim',       'phone'=>'09393456789','email'=>'roberto@email.com','plate'=>'DEF 9012','model'=>'Mitsubishi Mirage 2022','bookings'=>5, 'last_visit'=>'2024-01-14'],
    ['id'=>'C-004','name'=>'Ana Reyes',          'phone'=>'09504567890','email'=>'ana@email.com',    'plate'=>'GHI 3456','model'=>'Ford Ranger 2019',     'bookings'=>3, 'last_visit'=>'2024-01-14'],
    ['id'=>'C-005','name'=>'Carlo Mendoza',      'phone'=>'09615678901','email'=>'carlo@email.com',  'plate'=>'JKL 7890','model'=>'Nissan Navara 2021',   'bookings'=>7, 'last_visit'=>'2024-01-13'],
    ['id'=>'C-006','name'=>'Lisa Tan',           'phone'=>'09726789012','email'=>'lisa@email.com',   'plate'=>'MNO 1234','model'=>'Suzuki Swift 2023',    'bookings'=>1, 'last_visit'=>'2024-01-13'],
    ['id'=>'C-007','name'=>'Paulo Garcia',       'phone'=>'09837890123','email'=>'paulo@email.com',  'plate'=>'PQR 5678','model'=>'Toyota Fortuner 2022', 'bookings'=>15,'last_visit'=>'2024-01-12'],
    ['id'=>'C-008','name'=>'Diane Uy',           'phone'=>'09948901234','email'=>'diane@email.com',  'plate'=>'STU 9012','model'=>'Honda Jazz 2020',      'bookings'=>4, 'last_visit'=>'2024-01-12'],
    ['id'=>'C-009','name'=>'Ben Cruz',           'phone'=>'09159012345','email'=>'ben@email.com',    'plate'=>'VWX 3456','model'=>'Hyundai Tucson 2021',  'bookings'=>6, 'last_visit'=>'2024-01-11'],
    ['id'=>'C-010','name'=>'Nina Flores',        'phone'=>'09260123456','email'=>'nina@email.com',   'plate'=>'YZA 7890','model'=>'Kia Stonic 2022',      'bookings'=>2, 'last_visit'=>'2024-01-11'],
];

$totalCustomers = count($customers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title><?= $pageTitle ?> — APX AutoMai</title>
    <?php include '../includes/styles.php'; ?>
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
            <h1 class="page-title">All <span>Customers</span></h1>
            <ol class="breadcrumb">
                <li>Customers</li>
                <li class="active">All Customers</li>
            </ol>
        </div>
        <a href="new.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Customer</a>
    </div>

    <!-- TABLE CARD -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-title"><i class="fas fa-users"></i> Customer Records</div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-ghost btn-sm"><i class="fas fa-file-export"></i> Export</button>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="filters-bar">
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.75rem;"></i>
                <input class="filter-input" type="text" id="searchInput" placeholder="Search by name, phone, or plate..." style="padding-left:32px;"/>
            </div>
            <button class="btn btn-ghost btn-sm" onclick="clearFilters()"><i class="fas fa-xmark"></i> Clear</button>
            <div class="spacer"></div>
            <span style="font-size:.8rem;color:var(--text-muted);" id="rowCount"><?= $totalCustomers ?> customers</span>
        </div>

        <!-- TABLE -->
        <div class="table-wrap">
            <table class="apx-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Vehicle / Plate</th>
                        <th>Total Bookings</th>
                        <th>Last Visit</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                <?php foreach($customers as $c): ?>
                <tr data-search="<?= strtolower($c['name'].' '.$c['phone'].' '.$c['plate']) ?>">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:50%;background:var(--surface-3);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:.8rem;color:var(--text-muted);flex-shrink:0;">
                                <?= strtoupper(substr($c['name'],0,1).substr(strrchr($c['name'],' '),1,1)) ?>
                            </div>
                            <div>
                                <div class="primary-col"><?= htmlspecialchars($c['name']) ?></div>
                                <div style="font-size:.75rem;color:var(--text-muted);"><?= $c['id'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= $c['phone'] ?></td>
                    <td>
                        <div style="font-family:'Barlow Condensed',sans-serif;font-weight:700;letter-spacing:.06em;"><?= $c['plate'] ?></div>
                        <div style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($c['model']) ?></div>
                    </td>
                    <td>
                        <span style="font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.1rem;"><?= $c['bookings'] ?></span>
                    </td>
                    <td style="white-space:nowrap;font-size:.83rem;"><?= $c['last_visit'] ?></td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;">
                            <button class="btn btn-ghost btn-sm btn-icon" title="View History" onclick="openModal('viewModal')"><i class="fas fa-eye"></i></button>
                            <a href="new.php?edit=<?= urlencode($c['id']) ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit"><i class="fas fa-pen"></i></a>
                            <button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="openModal('deleteModal')"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer-bar">
            <span id="footerCount"><?= $totalCustomers ?> customers</span>
            <div style="display:flex;gap:6px;">
                <button class="btn btn-ghost btn-sm">&#8249; Prev</button>
                <button class="btn btn-primary btn-sm">1</button>
                <button class="btn btn-ghost btn-sm">Next &#8250;</button>
            </div>
        </div>
    </div>

</main>

<!-- VIEW CUSTOMER MODAL -->
<div class="modal-overlay" id="viewModal">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-user" style="color:var(--red);margin-right:8px;"></i>Customer Profile</div>
            <button class="modal-close" onclick="closeModal('viewModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <!-- Profile top -->
            <div style="display:flex;align-items:center;gap:16px;padding-bottom:20px;border-bottom:1px solid var(--border);margin-bottom:20px;">
                <div style="width:52px;height:52px;border-radius:50%;background:var(--red-glow);border:2px solid var(--red);display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.1rem;color:var(--red);">JC</div>
                <div>
                    <div style="font-family:'Barlow Condensed',sans-serif;font-size:1.2rem;font-weight:800;">Juan dela Cruz</div>
                    <div style="font-size:.8rem;color:var(--text-muted);">C-001</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <?php foreach([
                    ['label'=>'Phone',         'value'=>'09171234567'],
                    ['label'=>'Email',         'value'=>'juan@email.com'],
                    ['label'=>'Plate Number',  'value'=>'ABC 1234'],
                    ['label'=>'Car Model',     'value'=>'Toyota Vios 2021'],
                    ['label'=>'Total Bookings','value'=>'12'],
                    ['label'=>'Last Visit',    'value'=>'2024-01-15'],
                ] as $f): ?>
                <div>
                    <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;"><?= $f['label'] ?></div>
                    <div style="font-size:.88rem;"><?= $f['value'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('viewModal')">Close</button>
            <a href="new.php?edit=C-001" class="btn btn-primary"><i class="fas fa-pen"></i> Edit Customer</a>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-trash" style="margin-right:8px;"></i>Delete Customer?</div>
            <button class="modal-close" onclick="closeModal('deleteModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">This will permanently delete the customer and all associated records. This cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('deleteModal')">Cancel</button>
            <button class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows   = document.querySelectorAll('#tableBody tr');
    let visible  = 0;
    rows.forEach(row => {
        const ms = !search || row.dataset.search.includes(search);
        row.style.display = ms ? '' : 'none';
        if(ms) visible++;
    });
    document.getElementById('rowCount').textContent   = visible + ' customers';
    document.getElementById('footerCount').textContent = visible + ' customers';
}
function clearFilters() {
    document.getElementById('searchInput').value = '';
    applyFilters();
}
document.getElementById('searchInput').addEventListener('input', applyFilters);
</script>
</body>
</html>