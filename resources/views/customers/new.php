<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/automai/config.php'; ?>
<?php
// customers/new.php — Add / Edit Customer
$isEdit        = isset($_GET['edit']);
$pageTitle     = $isEdit ? 'Edit Customer' : 'Add Customer';
$activeSection = 'customers';
$activePage    = 'new';

// ── TODO: If editing, fetch customer from DB ─────────────────────────────
// $customer = $conn->query("SELECT * FROM customers WHERE id=?" ...)->fetch();
// ────────────────────────────────────────────────────────────────────────
$customer = $isEdit ? [
    'name'    => 'Juan dela Cruz',
    'phone'   => '09171234567',
    'email'   => 'juan@email.com',
    'address' => '123 Maharlika St., Quezon City',
    'plate'   => 'ABC 1234',
    'model'   => 'Toyota Vios 2021',
    'color'   => 'White',
    'loyalty' => 'gold',
] : [];
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
            <h1 class="page-title"><?= $isEdit ? '<span>Edit</span> Customer' : 'Add <span>Customer</span>' ?></h1>
            <ol class="breadcrumb">
                <li>Customers</li>
                <li class="active"><?= $pageTitle ?></li>
            </ol>
        </div>
        <a href="<?= BASE_URL ?>/customers/index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back to Customers</a>
    </div>

    <?php
    // ── TODO: Handle POST ────────────────────────────────────────────────────
    // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //     if ($isEdit) {
    //         $stmt = $conn->prepare("UPDATE customers SET name=?,phone=?,email=?,address=?,plate=?,model=?,color=?,loyalty=? WHERE id=?");
    //     } else {
    //         $stmt = $conn->prepare("INSERT INTO customers (name,phone,email,address,plate,model,color,loyalty) VALUES (?,?,?,?,?,?,?,?)");
    //     }
    //     $stmt->execute([...]);
    //     header('Location: index.php'); exit;
    // }
    // ────────────────────────────────────────────────────────────────────────
    ?>

    <form method="POST" action="<?= BASE_URL ?>/customers/new.php">
        <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

            <!-- LEFT -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- PERSONAL INFO -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-user"></i> Personal Information</div>
                    </div>
                    <div class="card-body">
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="text" name="name"
                                       value="<?= htmlspecialchars($customer['name'] ?? '') ?>"
                                       placeholder="e.g. Juan dela Cruz" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="tel" name="phone"
                                       value="<?= htmlspecialchars($customer['phone'] ?? '') ?>"
                                       placeholder="09XXXXXXXXX" required />
                            </div>
                        </div>
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input class="form-control" type="email" name="email"
                                       value="<?= htmlspecialchars($customer['email'] ?? '') ?>"
                                       placeholder="optional" />
                            </div>
                            <div class="form-group">
                                <!-- spacer -->
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Address</label>
                            <input class="form-control" type="text" name="address"
                                   value="<?= htmlspecialchars($customer['address'] ?? '') ?>"
                                   placeholder="Street, Barangay, City" />
                        </div>
                    </div>
                </div>

                <!-- VEHICLE INFO -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-car"></i> Vehicle Details</div>
                        <span style="font-size:.75rem;color:var(--text-muted);">Primary vehicle</span>
                    </div>
                    <div class="card-body">
                        <div class="form-row cols-3">
                            <div class="form-group">
                                <label class="form-label">Plate Number <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="text" name="plate"
                                       value="<?= htmlspecialchars($customer['plate'] ?? '') ?>"
                                       placeholder="ABC 1234" required
                                       style="text-transform:uppercase;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;letter-spacing:.08em;" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Car Model</label>
                                <input class="form-control" type="text" name="model"
                                       value="<?= htmlspecialchars($customer['model'] ?? '') ?>"
                                       placeholder="e.g. Toyota Vios 2021" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Color</label>
                                <input class="form-control" type="text" name="color"
                                       value="<?= htmlspecialchars($customer['color'] ?? '') ?>"
                                       placeholder="e.g. White" />
                            </div>
                        </div>
                        <!-- Additional vehicle note -->
                        <div style="background:var(--surface-3);border:1px dashed var(--border);border-radius:6px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
                            <i class="fas fa-circle-info" style="color:var(--text-muted);font-size:.8rem;"></i>
                            <span style="font-size:.78rem;color:var(--text-muted);">Additional vehicles can be added after saving the customer profile.</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- LOYALTY -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-star"></i> Loyalty Tier</div>
                    </div>
                    <div class="card-body" style="padding:16px;">
                        <?php
                        $tiers = [
                            ['value'=>'bronze','label'=>'Bronze','desc'=>'1–4 bookings', 'color'=>'#CD7C4F','bg'=>'rgba(205,124,79,0.12)','icon'=>'fa-circle'],
                            ['value'=>'silver','label'=>'Silver','desc'=>'5–9 bookings', 'color'=>'#94A3B8','bg'=>'rgba(148,163,184,0.12)','icon'=>'fa-star-half-stroke'],
                            ['value'=>'gold',  'label'=>'Gold',  'desc'=>'10+ bookings', 'color'=>'#F59E0B','bg'=>'rgba(245,158,11,0.12)','icon'=>'fa-star'],
                        ];
                        foreach($tiers as $tier):
                            $checked = ($customer['loyalty'] ?? 'bronze') === $tier['value'];
                        ?>
                        <label style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:7px;border:1px solid <?= $checked ? $tier['color'] : 'var(--border)' ?>;background:<?= $checked ? $tier['bg'] : 'transparent' ?>;cursor:pointer;margin-bottom:8px;transition:all .2s;" class="tier-label">
                            <input type="radio" name="loyalty" value="<?= $tier['value'] ?>" <?= $checked?'checked':'' ?> style="display:none;" onchange="updateTier(this)" />
                            <div style="width:32px;height:32px;border-radius:50%;background:<?= $tier['bg'] ?>;display:flex;align-items:center;justify-content:center;color:<?= $tier['color'] ?>;font-size:.8rem;flex-shrink:0;">
                                <i class="fas <?= $tier['icon'] ?>"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:600;font-size:.88rem;color:<?= $checked ? $tier['color'] : 'var(--text)' ?>;"><?= $tier['label'] ?></div>
                                <div style="font-size:.74rem;color:var(--text-muted);"><?= $tier['desc'] ?></div>
                            </div>
                            <?php if($checked): ?>
                            <i class="fas fa-circle-check" style="color:<?= $tier['color'] ?>;font-size:.9rem;"></i>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SUBMIT -->
                <div class="card" style="background:var(--surface-2);">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
                            <i class="fas fa-<?= $isEdit ? 'floppy-disk' : 'plus' ?>"></i>
                            <?= $isEdit ? 'Save Changes' : 'Add Customer' ?>
                        </button>
                        <a href="<?= BASE_URL ?>/customers/index.php" class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:8px;">Cancel</a>
                        <?php if($isEdit): ?>
                        <hr style="border-color:var(--border);margin:12px 0;" />
                        <button type="button" class="btn btn-danger" style="width:100%;justify-content:center;" onclick="openModal('deleteModal')">
                            <i class="fas fa-trash"></i> Delete Customer
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- BOOKING HISTORY (edit only) -->
                <?php if($isEdit): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title" style="font-size:.85rem;"><i class="fas fa-clock-rotate-left"></i> Recent Bookings</div>
                        <a href="<?= BASE_URL ?>/bookings/index.php" style="font-size:.75rem;color:var(--red);text-decoration:none;">View All</a>
                    </div>
                    <div style="padding:0 4px;">
                        <?php
                        $recentBookings = [
                            ['id'=>'#BK-0041','service'=>'Full Car Wash',   'date'=>'2024-01-15','status'=>'confirmed'],
                            ['id'=>'#BK-0035','service'=>'Interior Detailing','date'=>'2024-01-08','status'=>'confirmed'],
                            ['id'=>'#BK-0028','service'=>'Oil Change',      'date'=>'2023-12-20','status'=>'confirmed'],
                        ];
                        $badgeMap = ['confirmed'=>'badge-confirmed','pending'=>'badge-pending','cancelled'=>'badge-cancelled','inprogress'=>'badge-inprogress'];
                        $labelMap = ['confirmed'=>'Confirmed','pending'=>'Pending','cancelled'=>'Cancelled','inprogress'=>'In Progress'];
                        foreach($recentBookings as $rb): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid var(--border);">
                            <div>
                                <div style="font-size:.83rem;font-weight:500;"><?= $rb['service'] ?></div>
                                <div style="font-size:.73rem;color:var(--text-muted);"><?= $rb['id'] ?> · <?= $rb['date'] ?></div>
                            </div>
                            <span class="badge <?= $badgeMap[$rb['status']] ?>"><?= $labelMap[$rb['status']] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </form>

</main>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-trash" style="margin-right:8px;"></i>Delete Customer?</div>
            <button class="modal-close" onclick="closeModal('deleteModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">This will permanently delete <strong style="color:var(--text);">Juan dela Cruz</strong> and all their records. This cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('deleteModal')">Cancel</button>
            <button class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function updateTier(radio) {
    document.querySelectorAll('.tier-label').forEach(label => {
        label.style.borderColor = 'var(--border)';
        label.style.background  = 'transparent';
    });
    const colors = { bronze:'#CD7C4F', silver:'#94A3B8', gold:'#F59E0B' };
    const bgs    = { bronze:'rgba(205,124,79,0.12)', silver:'rgba(148,163,184,0.12)', gold:'rgba(245,158,11,0.12)' };
    const parent = radio.closest('.tier-label');
    parent.style.borderColor = colors[radio.value];
    parent.style.background  = bgs[radio.value];
}
</script>
</body>
</html>