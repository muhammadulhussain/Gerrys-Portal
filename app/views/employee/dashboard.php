<?php
// Variables assumed from EmployeeController:
// $username, $email, $role, $profile_image
// $totalCustomers, $activeCustomers, $fiberCustomers, $rfCustomers, $ethernetCustomers, $fiber_rfCustomers
// $customerStatus, $monthlyCustomers
// $total_upstream, $total_used
// $panelTitle
?>
<div class="main-content">

  <!-- ===== Profile Dropdown ===== -->
  <div class="position-absolute top-0 end-0 m-3 profile-container">
    <div class="dropdown">
      <a class="d-flex align-items-center text-decoration-none dropdown-toggle profile-trigger"
         href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
         <img src="<?= !empty($profile_image) ? BASE_URL . '/uploads/profile_images/' . $profile_image : BASE_URL . '/uploads/profile_images/default.png'; ?>" 
             alt="Profile" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover; border: 1px solid #ddd;">
        <?= $username ?>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow p-3 animated-dropdown" aria-labelledby="profileDropdown">
        <li class="mb-2 px-2">
          <div class="d-flex align-items-center">
            <img src="<?= !empty($profile_image) ? BASE_URL . '/uploads/profile_images/' . $profile_image : BASE_URL . '/uploads/profile_images/default.png'; ?>" 
                 alt="Profile" class="rounded-circle me-3" width="50" height="50" style="object-fit: cover; border: 2px solid #ddd;">
            <div>
              <h6 class="mb-0"><?= $username ?></h6>
              <small class="text-muted"><?= $email ?></small>
            </div>
          </div>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item animated-item" href="<?= BASE_URL ?>/account/account-detail.php"><i class="fa-solid fa-user me-2"></i> Account Details</a></li>
        <li><a class="dropdown-item animated-item" href="<?= BASE_URL ?>/account/change-password.php"><i class="fa-solid fa-gear me-2"></i> Change Password</a></li>
        <!-- Employee cannot create new user -->
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger animated-item" href="<?= BASE_URL ?>/log/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out</a></li>
      </ul>
    </div>
  </div>

  <!-- ===== Dashboard Header ===== -->
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0 fw-bold"><i class="fa-solid fa-chart-line me-2"></i> <?= $panelTitle ?></h2>
      <p class="muted mb-0">Welcome back, <strong><?= $username ?></strong> — Role: <?= $role ?></p>
    </div>
    <div class="muted text-end"><small>Year: <?= date('Y') ?></small></div>
  </div>

  <!-- ===== Dashboard Cards ===== -->
  <div class="cards-row mb-4">
    <!-- Customers Card -->
    <a href="<?= BASE_URL ?>/customers/customer_info.php" class="hover-card" data-aos="fade-up" data-aos-delay="50">
      <div class="card">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="muted">Customers</div>
            <div class="stat" data-target="<?= $totalCustomers ?>">0</div>
            <div class="muted mt-1">Total Active: <strong><?= number_format($activeCustomers) ?></strong></div>
          </div>
          <div style="text-align:right;">
            <div class="muted">Radio Frequency</div>
            <div class="stat" style="font-size:1.4rem;"><?= $rfCustomers ?></div>
            <div class="muted mt-1">Fiber: <strong><?= $fiberCustomers ?></strong></div>
            <div class="muted mt-1">Ethernet: <strong><?= $ethernetCustomers ?></strong></div>
          </div>
        </div>
        <hr style="margin:12px 0 10px;">
        <div class="muted">Total Fiber & Radio-c Frequency Customers</div>
        <div class="d-flex gap-2 mt-2">
          <div class="badge-pill">Fiber & RF: <?= $fiber_rfCustomers ?></div>
        </div>
      </div>
    </a>

    <!-- Bandwidth Card -->
    <a href="<?= BASE_URL ?>/bandwidth/bandwidth_report.php" class="hover-card" data-aos="fade-up" data-aos-delay="120">
      <div class="card">
        <div class="muted">
          Bandwidth (<?= date('F Y') ?>)
        </div>
        <h5 class="fw-bold mt-2">Network Usage</h5>

        <table class="small-table">
          <thead>
            <tr>
              <th>UpStream (Mbps)</th>
              <th>Used (Mbps)</th>
            </tr>
          </thead>

          <tbody>
            <tr>
              <td><?= number_format($total_upstream, 2) ?></td>
              <td><?= number_format($total_used, 2) ?></td>
            </tr>
          </tbody>
        </table>

        <div class="muted mt-3">
          Notes: Showing current month bandwidth. Click to open full report.
        </div>
      </div>
    </a>

    <!-- CDN Card -->
    <a href="<?= BASE_URL ?>/cdn/cdn_report.php" class="hover-card" data-aos="fade-up" data-aos-delay="190">
      <div class="card">
        <div class="muted">CDN</div>
        <h5 class="fw-bold mt-2">CDN Throughput</h5>
        <table class="small-table mt-3">
          <thead><tr><th>Usage Mb</th></tr></thead>
          <tbody><tr><td>24 Mb</td></tr></tbody>
        </table>
        <div class="muted mt-3">Click to view CDN logs & cache stats.</div>
      </div>
    </a>
  </div>

  <!-- ===== Graphs ===== -->
  <div class="row" style="display:flex; gap:20px; flex-wrap:wrap;">
    <div style="flex:1 1 60%; min-width:300px;" data-aos="zoom-in" data-aos-delay="80">
      <div class="card p-3">
        <h6 class="fw-bold mb-3">Yearly New Customers (<?= date('Y') ?>)</h6>
        <canvas id="customersChart" height="160"></canvas>
      </div>
    </div>
    <div style="flex:1 1 30%; min-width:260px;" data-aos="zoom-in" data-aos-delay="160">
      <div class="card p-3">
        <h6 class="fw-bold mb-3">Customers Status</h6>
        <canvas id="customerChart" height="160"></canvas>
      </div>
    </div>
  </div>

</div>
