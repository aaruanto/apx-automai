<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/automai/config.php'; ?>
<?php
// bookings/new.php — New / Edit Booking
$isEdit        = isset($_GET['edit']);
$pageTitle     = $isEdit ? 'Edit Booking' : 'New Booking';
$activeSection = 'bookings';
$activePage    = 'new';

// ── TODO: If editing, fetch booking from DB by $_GET['edit'] ──────────────
// $booking = $conn->query("SELECT * FROM bookings WHERE id=?" ...)->fetch();
// ─────────────────────────────────────────────────────────────────────────
$booking = $isEdit ? [
    'customer_name'  => 'Juan dela Cruz',
    'customer_phone' => '09171234567',
    'customer_email' => 'juan@email.com',
    'plate'          => 'ABC 1234',
    'car_model'      => 'Toyota Vios 2021',
    'service'        => 'Full Car Wash',
    'notes'          => 'Please focus on the interior.',
    'date'           => '2024-01-15',
    'time'           => '09:00',
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
            <h1 class="page-title"><?= $isEdit ? '<span>Edit</span> Booking' : 'New <span>Booking</span>' ?></h1>
            <ol class="breadcrumb">
                <li>Bookings</li>
                <li class="active"><?= $pageTitle ?></li>
            </ol>
        </div>
        <a href="<?= BASE_URL ?>/bookings/index.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back to All Bookings</a>
    </div>

    <!-- FORM -->
    <?php
    // ── TODO: Handle POST submission ────────────────────────────────────────
    // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //     $stmt = $conn->prepare("INSERT INTO bookings (...) VALUES (...)");
    //     $stmt->bind_param(...);
    //     $stmt->execute();
    //     header('Location: index.php'); exit;
    // }
    // ──────────────────────────────────────────────────────────────────────
    ?>
    <form method="POST" action="<?= BASE_URL ?>/bookings/new.php">

        <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start;">

            <!-- LEFT COLUMN -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- CUSTOMER INFO -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-user"></i> Customer Information</div>
                    </div>
                    <div class="card-body">
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="text" name="customer_name"
                                       value="<?= htmlspecialchars($booking['customer_name'] ?? '') ?>"
                                       placeholder="e.g. Juan dela Cruz" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="tel" name="customer_phone"
                                       value="<?= htmlspecialchars($booking['customer_phone'] ?? '') ?>"
                                       placeholder="09XXXXXXXXX" required />
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Email Address</label>
                            <input class="form-control" type="email" name="customer_email"
                                   value="<?= htmlspecialchars($booking['customer_email'] ?? '') ?>"
                                   placeholder="optional" />
                        </div>
                    </div>
                </div>

                <!-- VEHICLE INFO -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-car"></i> Vehicle Details</div>
                    </div>
                    <div class="card-body">
                        <div class="form-row cols-2">
                            <div class="form-group">
                                <label class="form-label">Plate Number <span style="color:var(--red)">*</span></label>
                                <input class="form-control" type="text" name="plate"
                                       value="<?= htmlspecialchars($booking['plate'] ?? '') ?>"
                                       placeholder="e.g. ABC 1234" required
                                       style="text-transform:uppercase;letter-spacing:.08em;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Car Model</label>
                                <input class="form-control" type="text" name="car_model"
                                       value="<?= htmlspecialchars($booking['car_model'] ?? '') ?>"
                                       placeholder="e.g. Toyota Vios 2021" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SERVICE INFO -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-wrench"></i> Service &amp; Notes</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Service Type <span style="color:var(--red)">*</span></label>
                            <select class="form-control" name="service" required>
                                <option value="">— Select a service —</option>
                                <?php
                                $services = ['Full Car Wash','Interior Detailing','Exterior Detailing',
                                             'Oil Change','Tire Rotation','Engine Check','Paint Protection','Ceramic Coating'];
                                foreach($services as $svc):
                                    $sel = ($booking['service'] ?? '') === $svc ? 'selected' : '';
                                ?>
                                <option value="<?= $svc ?>" <?= $sel ?>><?= $svc ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Additional Notes</label>
                            <textarea class="form-control" name="notes" placeholder="Special instructions, concerns, or requests..."><?= htmlspecialchars($booking['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- SCHEDULE -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-calendar"></i> Schedule</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Preferred Date <span style="color:var(--red)">*</span></label>
                            <input class="form-control" type="date" name="date"
                                   value="<?= $booking['date'] ?? '' ?>" required />
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Preferred Time <span style="color:var(--red)">*</span></label>
                            <select class="form-control" name="time" required>
                                <option value="">— Select time slot —</option>
                                <?php
                                $times = ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30',
                                          '13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30'];
                                foreach($times as $t):
                                    $sel = ($booking['time'] ?? '') === $t ? 'selected' : '';
                                    $disp = date('g:i A', strtotime($t));
                                ?>
                                <option value="<?= $t ?>" <?= $sel ?>><?= $disp ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-hint">Operating hours: 8:00 AM – 5:00 PM</div>
                        </div>
                    </div>
                </div>

                <!-- STATUS (edit only) -->
                <?php if($isEdit): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title"><i class="fas fa-tag"></i> Status</div>
                    </div>
                    <div class="card-body">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Booking Status</label>
                            <select class="form-control" name="status">
                                <option value="pending">Pending</option>
                                <option value="confirmed" selected>Confirmed</option>
                                <option value="inprogress">In Progress</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- SUBMIT -->
                <div class="card" style="background:var(--surface-2);">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
                            <i class="fas fa-<?= $isEdit ? 'floppy-disk' : 'plus' ?>"></i>
                            <?= $isEdit ? 'Save Changes' : 'Create Booking' ?>
                        </button>
                        <a href="<?= BASE_URL ?>/bookings/index.php" class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:8px;">
                            Cancel
                        </a>
                        <?php if($isEdit): ?>
                        <hr style="border-color:var(--border);margin:12px 0;" />
                        <button type="button" class="btn btn-danger" style="width:100%;justify-content:center;" onclick="openModal('cancelModal')">
                            <i class="fas fa-ban"></i> Cancel This Booking
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

    </form>

</main>

<!-- CANCEL CONFIRM MODAL -->
<div class="modal-overlay" id="cancelModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--red);"><i class="fas fa-triangle-exclamation" style="margin-right:8px;"></i>Cancel Booking?</div>
            <button class="modal-close" onclick="closeModal('cancelModal')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);font-size:.9rem;">This will mark the booking as <strong style="color:var(--red)">Cancelled</strong>. This action cannot be undone. Are you sure?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('cancelModal')">Go Back</button>
            <button class="btn btn-danger">Yes, Cancel Booking</button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>