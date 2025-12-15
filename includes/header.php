<?php

$role        = $_SESSION['role'] ?? '';
$stationName = $_SESSION['station'] ?? '';
$stationId   = $_SESSION['station_id'] ?? 0;
$username    = $_SESSION['username'] ?? 'User';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
  /* Sidebar styling */
  #sidebar {
    width: 250px;
    height: 100vh;
    background-color: #1f2227;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    transition: all 0.3s ease;
  }

  /* Logo section */
  #sidebar .logo-section {
    background: #fff;
    margin: 12px;
    padding-top: 14px;
    padding-bottom: 8px;
    text-align: center;
    border-radius: 18px;
  }

  #sidebar .logo-section img {
    height: 120px;
    width: auto;
  }

  #sidebar .logo-section h5 {
    margin-top: 10px;
    font-weight: 600;
    color: #000;
  }

  /* Menu links */
  #sidebar .nav-link {
    color: #ddd;
    font-size: 15px;
    font-weight: 500;
    padding: 12px 20px;
    border-radius: 8px;
    margin: 4px 10px;
    display: flex;
    align-items: center;
    transition: 0.3s;
  }

  #sidebar .nav-link i {
    margin-right: 10px;
    font-size: 16px;
  }

  #sidebar .nav-link:hover,
  #sidebar .nav-link.active {
    background-color: #ebb41e;
    color: #000;
    font-size: 17px;
  }

  /* Hide scrollbar */
  #sidebar::-webkit-scrollbar {
    width: 0;
  }
</style>
      
<div id="sidebar">
  <div class="logo-section">
    <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Logo">
    <h5><?php echo ($role === 'Admin') ? 'Admin Portal' : 'Employee Portal'; ?></h5>
  </div>

  <!-- ✅ ADMIN MENU -->
  <?php if (strcasecmp($role, 'Admin') === 0): ?>
      <a href="/gerrys_project/admin/admin_dashboard.php" class="nav-link active">
        <i class="fa-solid fa-chart-line"></i> Dashboard
      </a>

      <!-- ✅ Station-wise Dashboard Links (Admin Only) -->
      <a href="/gerrys_project/employee/employee_dashboard_.php?station_id=1" class="nav-link">
          <i class="fa-solid fa-building"></i> Karachi
      </a>
      <a href="/gerrys_project/employee/employee_dashboard_.php?station_id=5" class="nav-link">
          <i class="fa-solid fa-building"></i> Lahore
      </a>
      <a href="/gerrys_project/employee/employee_dashboard_.php?station_id=3" class="nav-link">
          <i class="fa-solid fa-building"></i> Quetta
      </a>
      <a href="/gerrys_project/employee/employee_dashboard_.php?station_id=7" class="nav-link">
          <i class="fa-solid fa-building"></i> Islamabad
      </a>
      <a href="/gerrys_project/employee/employee_dashboard_.php?station_id=2" class="nav-link">
          <i class="fa-solid fa-building"></i> Hyderabad
      </a>
      <a href="/gerrys_project/employee/employee_dashboard_.php?station_id=4" class="nav-link">
          <i class="fa-solid fa-building"></i> Faisalabad
      </a>
      <a href="/gerrys_project/employee/employee_dashboard_.php?station_id=6" class="nav-link">
          <i class="fa-solid fa-building"></i> Peshawar
      </a>
      <a href="/gerrys_project/employee/employee_dashboard_.php?station_id=10" class="nav-link">
          <i class="fa-solid fa-building"></i> Multan
      </a>
      <!-- <a href="/gerrys_project/employee/employee_dashboard_.php?station_id=13" class="nav-link">
          <i class="fa-solid fa-building"></i> Layyah
      </a> -->
      

  <!-- Employee Menu -->
  <?php else: ?>
    <nav class="mt-3">
      <a href="/gerrys_project/employee/employee_dashboard_.php" class="nav-link active">
        <i class="fa-solid fa-chart-line"></i> Dashboard
      </a>
      <a href="/gerrys_project/customers/customer_form.php" class="nav-link">
        <i class="fa-solid fa-users"></i> Customers Form
      </a>
      <a href="/gerrys_project/customers/customer_info.php" class="nav-link">
        <i class="fa-solid fa-address-card"></i> Customers Info
      </a>
      <a href="/gerrys_project/bandwidth/create_bandwidth_report.php" class="nav-link">
        <i class="fa-solid fa-network-wired"></i> Bandwidth
      </a>
      <a href="/gerrys_project/cdn/cdn_report.php" class="nav-link">
        <i class="fa-solid fa-database"></i> CDN Utilization
      </a>
    </nav>
  <?php endif; ?>
</div>
