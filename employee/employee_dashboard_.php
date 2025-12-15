<?php
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee','Admin']);
require_once __DIR__ . '/../includes/db.php';

// ---------------- Logged-in User Info ----------------
$user_id  = $_SESSION['user_id'] ?? 0;
$stmt     = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user     = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_image = $user['profile_image'] ?? 'default.png';
$username      = htmlspecialchars($user['username'] ?? 'User');
$email         = htmlspecialchars($user['email'] ?? ''); // ✅ FIX
$role          = htmlspecialchars($_SESSION['role'] ?? '');


// ---------------- Station Determination ----------------
$station_id   = 0;
$station_name = 'Unassigned';

if(strcasecmp($role,'Admin') === 0){
    // Admin can select station via GET parameter
    if(isset($_GET['station_id']) && intval($_GET['station_id'])>0){
        $station_id = intval($_GET['station_id']);
        $stmt = $conn->prepare("SELECT name FROM stations WHERE id=?");
        $stmt->bind_param("i",$station_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if($row=$res->fetch_assoc()) $station_name = htmlspecialchars($row['name']);
        $stmt->close();
    }
} else {
    // Employee must have assigned station
    $station_id = intval($_SESSION['station_id'] ?? 0);
    if($station_id <= 0) die("Station not assigned. Contact admin.");
    $stmt = $conn->prepare("SELECT name FROM stations WHERE id=?");
    $stmt->bind_param("i",$station_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row=$res->fetch_assoc()) $station_name = htmlspecialchars($row['name']);
    $stmt->close();
}

// ---------------- Current Year & Month ----------------
$currentYear  = date('Y');
$currentMonth = date('m');

// ---------------- Dashboard Metrics ----------------
$where_station = $station_id>0 ? "WHERE station_id=?" : "";
$params        = $station_id>0 ? [$station_id] : [];

function fetch_count($conn,$query,$params=[]){
    $stmt = $conn->prepare($query);
    if(!empty($params)){
        $types = str_repeat("i",count($params));
        $stmt->bind_param($types,...$params);
    }
    $stmt->execute();
    $res   = $stmt->get_result();
    $count = $res->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    return (int)$count;
}

// Customers
$totalCustomersStation = fetch_count($conn,"SELECT COUNT(*) AS total FROM customers $where_station",$params);
$totalActive           = fetch_count($conn,"SELECT COUNT(*) AS total FROM customers WHERE status='Active' ".($station_id>0?" AND station_id=?":""),$params);
$fiberCount            = fetch_count($conn,"SELECT COUNT(*) AS total FROM customers WHERE connection_type='Fiber' ".($station_id>0?" AND station_id=?":""),$params);
$rfCount               = fetch_count($conn,"SELECT COUNT(*) AS total FROM customers WHERE connection_type='Radio Frequency' ".($station_id>0?" AND station_id=?":""),$params);
$ethernetCount         = fetch_count($conn,"SELECT COUNT(*) AS total FROM customers WHERE connection_type='Ethernet' ".($station_id>0?" AND station_id=?":""),$params);
$rfFiberCount          = fetch_count($conn,"SELECT COUNT(*) AS total FROM customers WHERE connection_type='Fiber & Radio Frequency' ".($station_id>0?" AND station_id=?":""),$params);

// Bandwidth totals (current month)
$total_upstream = 0.0; $total_used = 0.0;
$bw_query = $conn->prepare("SELECT COALESCE(SUM(current_bandwidth),0) AS total_up, COALESCE(SUM(used_bandwidth),0) AS total_used FROM bandwidth_reports ".($station_id>0?"WHERE station_id=? AND MONTH(report_date)=? AND YEAR(report_date)=?":"WHERE MONTH(report_date)=? AND YEAR(report_date)=?"));
if($station_id>0){
    $bw_query->bind_param("iii",$station_id,$currentMonth,$currentYear);
}else{
    $bw_query->bind_param("ii",$currentMonth,$currentYear);
}
$bw_query->execute();
$bw_result = $bw_query->get_result()->fetch_assoc();
$total_upstream = (float)($bw_result['total_up'] ?? 0);
$total_used     = (float)($bw_result['total_used'] ?? 0);
$bw_query->close();

// Station source for display
$station_source = $station_name;

// Panel title
$panelTitle = strcasecmp($role,'Admin')===0 ? "Employee Dashboard — $station_source" : "Employee Dashboard — $station_source";

// ---------------- Monthly Customers (for chart) ----------------
$monthlyCustomers = [];
for($m=1; $m<=12; $m++){
    $query = "SELECT COUNT(*) AS total FROM customers WHERE MONTH(install_date)=? AND YEAR(install_date)=?";
    if($station_id>0) $query .= " AND station_id=?";
    $stmt = $conn->prepare($query);
    if($station_id>0){
        $stmt->bind_param("iii",$m,$currentYear,$station_id);
    } else {
        $stmt->bind_param("ii",$m,$currentYear);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $monthlyCustomers[$m-1] = (int)($res->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

// ---------------- Customer Status Counts ----------------
$statuses = ['Active','Suspended','Temp Off','Terminated'];
$customerStatus = [];
foreach($statuses as $s){
    $query = "SELECT COUNT(*) AS total FROM customers WHERE status=?".($station_id>0?" AND station_id=?":"");
    $stmt = $conn->prepare($query);
    if($station_id>0){
        $stmt->bind_param("si",$s,$station_id);
    } else {
        $stmt->bind_param("s",$s);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $customerStatus[$s] = (int)($res->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}


// ---------------- Include header ----------------
include(__DIR__ . '/../includes/header.php');
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($station_name) ?> Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../admin/style.css">
  <style>
    /* small inline fix to avoid chart container jump */
    canvas { display:block !important; }
    /* =================================================
   FORCE ADMIN SIDEBAR TO STAY VISIBLE ON MOBILE
   (NO HTML / PHP CHANGE)
================================================= */

/* Default (desktop already OK) */
.main-content{
  margin-left:250px;
}

/* Tablets & Mobile */
@media (max-width: 991px){

  /* Sidebar NEVER hide */
  #sidebar{
    position: fixed;
    top: 0;
    left: 0;
    transform: translateX(0) !important;   /* IMPORTANT */
    width: 210px;
    z-index: 1500;
  }

  /* Content adjusts instead of hiding header */
  .main-content{
    margin-left: 210px !important;
    width: calc(100% - 210px) !important;
    padding: 16px !important;
  }
}

/* Small mobiles */
@media (max-width: 576px){

  #sidebar{
    width: 190px;
  }

  .main-content{
    margin-left: 190px !important;
    width: calc(100% - 190px) !important;
  }

  /* Text & cards spacing */
  .stat{
    font-size:1.25rem;
  }
}

/* =================================================
   PREVENT ANY HORIZONTAL SCROLL
================================================= */
html, body{
  overflow-x: hidden !important;
}
  </style>
</head>
<body>
<div class="main-content">

  <!-- Profile Dropdown -->
  <div class="position-absolute top-0 end-0 m-3 profile-container">
    <div class="dropdown">
      <a class="d-flex align-items-center text-decoration-none dropdown-toggle profile-trigger"
         href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
         <img src="<?= !empty($profile_image) ? '../uploads/profile_images/' . $profile_image : '../uploads/profile_images/default.png'; ?>" 
             alt="Profile" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover; border: 1px solid #ddd;">
        <?= $username ?>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow p-3 animated-dropdown" aria-labelledby="profileDropdown">
        <li class="mb-2 px-2">
          <div class="d-flex align-items-center">
            <img src="<?= !empty($profile_image) ? '../uploads/profile_images/' . $profile_image : '../uploads/profile_images/default.png'; ?>" 
                 alt="Profile" class="rounded-circle me-3" width="50" height="50" style="object-fit: cover; border: 2px solid #ddd;">
            <div>
              <h6 class="mb-0"><?= $username ?></h6>
              <small class="text-muted"><?= $email ?></small>
            </div>
          </div>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item animated-item" href="/gerrys_project/account/account-detail.php"><i class="fa-solid fa-user me-2"></i> Account Details</a></li>
        <li><a class="dropdown-item animated-item" href="/gerrys_project/account/change-password.php"><i class="fa-solid fa-gear me-2"></i> Change Password</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger animated-item" href="/gerrys_project/log/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out</a></li>
      </ul>
    </div>
  </div>

  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h2 class="mb-0" style="font-weight:700;"><i class="fa-solid fa-chart-line me-2"></i> <?= htmlspecialchars($station_name) ?> Dashboard</h2>
      <p class="muted mb-0">Welcome back, <strong><?= $username ?></strong> — Role: <?= $role ?></p>
      <p class="small text-muted mb-0"><?= $station_source ?></p>
    </div>
    <div class="muted text-end">
      <small>Year: <?= $currentYear ?></small>
    </div>
  </div>

  <?php if ($station_id <= 0): ?>
    <div class="alert alert-warning">Station not assigned or selected. Please contact admin.</div>
  <?php else: ?>

  <!-- Cards Row -->
  <div class="cards-row mb-4">

    <!-- Customers Card -->
    <a href="../customers/customer_info.php?station_id=<?= urlencode($station_id) ?>" class="hover-card" data-aos="fade-up" data-aos-delay="50">
      <div class="card">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="muted">Customers (<?= htmlspecialchars($station_name) ?>)</div>
            <div class="stat" data-target="<?= $totalCustomersStation ?>"><?= number_format($totalCustomersStation) ?></div>
            <div class="muted mt-1">Total Active: <strong><?= number_format($totalActive) ?></strong></div>
          </div>
          <div style="text-align:right;">
            <div class="muted">Fiber</div>
            <div class="stat" style="font-size:1.4rem;"><?= number_format($fiberCount) ?></div>
            <div class="muted mt-1">R.F: <strong><?= number_format($rfCount) ?></strong></div>
            <div class="muted mt-1">Ethernet: <strong><?= number_format($ethernetCount) ?></strong></div>
          </div>
        </div>
        <hr style="margin:12px 0 10px;">
        <div class="muted">Station totals — click to open station customers list.</div>
        <div class="d-flex gap-2 mt-2">
          <div class="badge-pill">Fiber & RF: <?= number_format($rfFiberCount) ?></div>
        </div>
      </div>
    </a>

    <!-- Bandwidth Card -->
<a href="../bandwidth/bandwidth_report.php" class="hover-card" data-aos="fade-up" data-aos-delay="120">
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


    <!-- CDN Card (placeholder) -->
    <a href="../cdn/cdn_report.php" class="hover-card" data-aos="fade-up" data-aos-delay="190">
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

  </div> <!-- /cards-row -->

  <!-- Graphs -->
  <div class="row" style="display:flex; gap:20px; flex-wrap:wrap;">
    <div style="flex:1 1 60%; min-width:300px;" data-aos="zoom-in" data-aos-delay="80">
      <div class="card p-3">
        <h6 class="fw-bold mb-3">Monthly New Customers (<?= $currentYear ?>) — <?= htmlspecialchars($station_name) ?></h6>
        <canvas id="customersChart" height="160"></canvas>
      </div>
    </div>

    <div style="flex:1 1 34%; min-width:260px;" data-aos="zoom-in" data-aos-delay="160">
      <div class="card p-3">
        <h6 class="fw-bold mb-3">Customers Status — <?= htmlspecialchars($station_name) ?></h6>
        <canvas id="customerChart" height="160"></canvas>
      </div>
    </div>
  </div>

  <?php endif; // end station check ?>

</div> <!-- /main-content -->

<!-- ---------- Scripts: AOS init, CountUp, Charts (fixed) ---------- -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/* Initialize after DOM ready. We wait for load event to avoid flicker/glitch. */
document.addEventListener('DOMContentLoaded', function () {

  // Init AOS
  AOS.init({
    duration: 700,
    easing: 'ease-out-cubic',
    once: true
  });

  // Ensure AOS refresh & mark page ready when everything (images/charts) loaded
  window.addEventListener('load', function () {
    AOS.refresh();
    document.body.classList.add('aos-animate');
    startCountUp();
    initCharts();
  });

  // Count-up animation (runs after window load)
  function startCountUp() {
    const els = document.querySelectorAll('[data-target]');
    els.forEach(el => {
      const target = parseInt(el.getAttribute('data-target')) || 0;
      const duration = 900;
      let start = null;
      function step(timestamp) {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        const val = Math.min(Math.floor((progress / duration) * target), target);
        el.textContent = val.toLocaleString();
        if (progress < duration) {
          requestAnimationFrame(step);
        } else {
          el.textContent = target.toLocaleString();
        }
      }
      requestAnimationFrame(step);
    });
  }

  // Chart initialization separated to avoid blocking render
  function initCharts() {
    // Monthly customers
    (function(){
      const ctx = document.getElementById('customersChart')?.getContext('2d');
      if (!ctx) return;
      const gradient = ctx.createLinearGradient(0,0,0,220);
      gradient.addColorStop(0, "rgba(54,162,235,0.35)");
      gradient.addColorStop(1, "rgba(54,162,235,0)");
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
          datasets: [{
            label: "New Customers",
            data: <?= json_encode(array_values($monthlyCustomers)) ?>,
            borderColor: "rgba(54, 162, 235, 1)",
            backgroundColor: gradient,
            borderWidth: 2.5,
            fill: true,
            tension: 0.36,
            pointRadius: 3
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
          scales: { y: { beginAtZero: true, grid: { color: 'rgba(200,200,200,0.06)' } }, x: { grid: { display: false } } }
        }
      });
    })();

    // Status donut
    (function(){
      const ctx2 = document.getElementById('customerChart')?.getContext('2d');
      if (!ctx2) return;
      new Chart(ctx2, {
        type: 'doughnut',
        data: {
          labels: ['Active','Suspended','Temp Off','Terminated'],
          datasets: [{
            data: [
              <?= (int)$customerStatus['Active'] ?>,
              <?= (int)$customerStatus['Suspended'] ?>,
              <?= (int)$customerStatus['Temp Off'] ?>,
              <?= (int)$customerStatus['Terminated'] ?>
            ],
            backgroundColor: ['#16a34a','#0b5bd7','#06b6d4','#ef4444'],
            borderColor: '#fff',
            borderWidth: 2
          }]
        },
        options: { plugins: { legend: { position: 'bottom' } }, responsive: true }
      });
    })();
  }

});
</script>
</body>
</html>
