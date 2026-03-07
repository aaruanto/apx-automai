<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/automai/config.php';

// Fallback in case config didn't load
if (!defined('BASE_URL')) {
    define('BASE_URL', '/automai');
}

$activeSection = $activeSection ?? '';
$activePage    = $activePage    ?? '';
?>

<?php
// includes/sidebar.php
// Usage: include after topnav. Set $activeSection and $activePage before including.
// $activeSection: 'dashboard' | 'bookings' | 'customers' | 'reports' | 'settings'
// $activePage: 'all' | 'new' | 'schedule' | 'cancelled' | etc.
$activeSection = $activeSection ?? '';
$activePage    = $activePage    ?? '';
?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-body">

        <div class="section-label">Main</div>
        <a class="nav-link <?= $activeSection==='dashboard' ? 'active' : '' ?>" href="<?= BASE_URL ?>/dashboard/admin-dashboard.php">
            <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
            <span class="nav-label">Dashboard</span>
        </a>

        <hr class="sidebar-divider" />
        <div class="section-label">Operations</div>

        <a class="nav-link <?= $activeSection==='bookings' ? 'open' : '' ?>"
           href="#" onclick="toggleSub(event,'sub-bookings',this)">
            <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
            <span class="nav-label">Bookings</span>
            <i class="fas fa-chevron-right nav-arrow"></i>
        </a>
        <div class="sub-nav <?= $activeSection==='bookings' ? 'open' : '' ?>" id="sub-bookings">
            <a href="<?= BASE_URL ?>/bookings/index.php"     class="<?= $activePage==='all'       ? 'active' : '' ?>">All Bookings</a>
            <a href="<?= BASE_URL ?>/bookings/new.php"       class="<?= $activePage==='new'       ? 'active' : '' ?>">New Booking</a>
            <a href="<?= BASE_URL ?>/bookings/schedule.php"  class="<?= $activePage==='schedule'  ? 'active' : '' ?>">Today's Schedule</a>
            <a href="<?= BASE_URL ?>/bookings/cancelled.php" class="<?= $activePage==='cancelled' ? 'active' : '' ?>">Cancelled</a>
        </div>

        <a class="nav-link <?= $activeSection==='customers' ? 'open' : '' ?>"
           href="#" onclick="toggleSub(event,'sub-customers',this)">
            <span class="nav-icon"><i class="fas fa-users"></i></span>
            <span class="nav-label">Customers</span>
            <i class="fas fa-chevron-right nav-arrow"></i>
        </a>
        <div class="sub-nav <?= $activeSection==='customers' ? 'open' : '' ?>" id="sub-customers">
            <a href="<?= BASE_URL ?>/customers/index.php">All Customers</a>
            <a href="<?= BASE_URL ?>/customers/new.php">Add Customer</a>
            <a href="<?= BASE_URL ?>/customers/loyalty.php">Loyalty Members</a>
        </div>

        <hr class="sidebar-divider" />
        <div class="section-label">Insights</div>

        <a class="nav-link <?= $activeSection==='reports' ? 'open' : '' ?>"
           href="#" onclick="toggleSub(event,'sub-reports',this)">
            <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
            <span class="nav-label">Reports</span>
            <i class="fas fa-chevron-right nav-arrow"></i>
        </a>
        <div class="sub-nav <?= $activeSection==='reports' ? 'open' : '' ?>" id="sub-reports">
            <a href="<?= BASE_URL ?>/reports/revenue.php">Revenue Report</a>
            <a href="<?= BASE_URL ?>/reports/summary.php">Booking Summary</a>
            <a href="<?= BASE_URL ?>/reports/staff.php">Staff Performance</a>
        </div>

        <hr class="sidebar-divider" />
        <div class="section-label">System</div>

        <a class="nav-link <?= $activeSection==='settings' ? 'open' : '' ?>"
           href="#" onclick="toggleSub(event,'sub-settings',this)">
            <span class="nav-icon"><i class="fas fa-gear"></i></span>
            <span class="nav-label">Settings</span>
            <i class="fas fa-chevron-right nav-arrow"></i>
        </a>
        <div class="sub-nav <?= $activeSection==='settings' ? 'open' : '' ?>" id="sub-settings">
            <a href="<?= BASE_URL ?>/settings/general.php">General</a>
            <a href="<?= BASE_URL ?>/settings/staff.php">Staff Accounts</a>
            <a href="<?= BASE_URL ?>/settings/services.php">Services &amp; Pricing</a>
            <a href="<?= BASE_URL ?>/settings/notifications.php">Notifications</a>
        </div>

    </div>
    <div class="sidebar-footer">
        <div class="sidebar-footer-info">
            <div class="label">Logged in as</div>
            <div class="value">Administrator</div>
        </div>
    </div>
</nav>