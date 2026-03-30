<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/automai/config.php'; ?>
<?php
// includes/topnav.php
// Usage: include at top of every page after setting $pageTitle
?>
<nav class="topnav">
    <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <a class="brand" href="<?= BASE_URL ?>/dashboard/admin-dashboard.php">
        <span class="brand-apx">APX</span>
        <span class="brand-auto">AutoMai</span>
        <span class="brand-badge">ADMIN</span>
    </a>
    <div class="topnav-search">
        <i class="fas fa-search search-icon"></i>
        <input type="text" placeholder="Search bookings, customers..." />
    </div>
    <div class="topnav-actions">
        <a href="#!" class="icon-btn" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="notif-dot"></span>
        </a>
        <a href="#!" class="icon-btn" title="Activity Log">
            <i class="fas fa-clock-rotate-left"></i>
        </a>
        <div class="dropdown">
            <a class="user-chip" href="#!">
                <div class="user-avatar">AD</div>
                <span class="user-name">Admin</span>
                <i class="fas fa-chevron-down" style="font-size:.65rem;color:var(--text-muted);margin-left:4px;"></i>
            </a>
            <div class="dropdown-menu">
                <a href="#!"><i class="fas fa-user-cog" style="width:16px;margin-right:8px;"></i>Settings</a>
                <a href="#!"><i class="fas fa-history" style="width:16px;margin-right:8px;"></i>Activity Log</a>
                <hr />
                <a href="<?= BASE_URL ?>/auth/login.php" class="logout"><i class="fas fa-right-from-bracket" style="width:16px;margin-right:8px;"></i>Logout</a>
            </div>
        </div>
    </div>
</nav>