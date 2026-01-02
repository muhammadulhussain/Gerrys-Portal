<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role        = $_SESSION['role'] ?? '';
$username    = $_SESSION['username'] ?? 'User';
?>

<div id="sidebar">
    <div class="logo-section">
        <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Logo">
        <h5><?php echo ($role === 'Admin') ? 'Admin Portal' : 'Employee Portal'; ?></h5>
    </div>

    <nav class="mt-3">
        <a href="/GERRYS_PROJECT/app/admin/admin_dashboard.php" class="nav-link active">
            <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>

        <a href="/GERRYS_PROJECT/app/customers/customer_form.php" class="nav-link">
            <i class="fa-solid fa-users"></i> Customers Form
        </a>

        <a href="/GERRYS_PROJECT/app/customers/customer_info.php" class="nav-link">
            <i class="fa-solid fa-address-card"></i> Customers Info
        </a>

        <a href="/GERRYS_PROJECT/app/bandwidth/create_bandwidth_report.php" class="nav-link">
            <i class="fa-solid fa-network-wired"></i> Bandwidth
        </a>

        <a href="/GERRYS_PROJECT/app/cdn/cdn_report.php" class="nav-link">
            <i class="fa-solid fa-database"></i> CDN Utilization
        </a>
    </nav>
</div>
