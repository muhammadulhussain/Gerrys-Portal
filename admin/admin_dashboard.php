<?php
// admin_dashboard.php
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Admin']);

require_once __DIR__ . '/../includes/db.php';

// ---------------------- Fetch Admin User Details ---------------------- //
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_image = $user['profile_image'] ?? 'default.png';
$username = htmlspecialchars($user['username']);
$email = htmlspecialchars($user['email']);
$role = htmlspecialchars($_SESSION['role'] ?? '');

// ---------------------- Dashboard Stats ---------------------- //
$totalCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers")->fetch_assoc()['total'] ?? 0);
$activeCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE status = 'Active'")->fetch_assoc()['total'] ?? 0);
$fiberCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE connection_type = 'Fiber'")->fetch_assoc()['total'] ?? 0);
$rfCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE connection_type IN ('Radio Frequency')")->fetch_assoc()['total'] ?? 0);
$ethernetCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE connection_type IN ('Ethernet')")->fetch_assoc()['total'] ?? 0);
$fiber_rfCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE connection_type IN ('FIber & Radio Frequency')")->fetch_assoc()['total'] ?? 0);

// ---------------------- Status-wise Counts ---------------------- //
$customerStatus = ['Active'=>0,'Suspended'=>0,'Temp Off'=>0,'Terminated'=>0];
$statusQuery = $conn->query("SELECT status, COUNT(*) AS total FROM customers GROUP BY status");
if ($statusQuery) {
    while ($row = $statusQuery->fetch_assoc()) {
        if (isset($customerStatus[$row['status']])) {
            $customerStatus[$row['status']] = (int)$row['total'];
        }
    }
}

// ---------------------- Monthly Customers ---------------------- //
$currentYear = date('Y');
$monthlyCustomers = array_fill(1, 12, 0);
$query = "SELECT MONTH(install_date) AS month, COUNT(*) AS total FROM customers WHERE install_date IS NOT NULL AND YEAR(install_date)=? GROUP BY MONTH(install_date)";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $monthlyCustomers[(int)$row['month']] = (int)$row['total'];
    }
    $stmt->close();
}

// ---------------------- Region-wise Customer Counts ---------------------- //
function getCustomerCountByRegion($conn, $regionName) {
    $stmt = $conn->prepare("
        SELECT st.name AS station_name, COUNT(c.id) AS total_customers
        FROM stations st
        LEFT JOIN customers c ON st.id = c.station_id
        LEFT JOIN regions r ON st.region_id = r.id
        WHERE r.name = ?
        GROUP BY st.name
        ORDER BY total_customers DESC
    ");
    $stmt->bind_param("s", $regionName);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    return $res;
}

$south = getCustomerCountByRegion($conn, 'South');
$central = getCustomerCountByRegion($conn, 'Central');
$north = getCustomerCountByRegion($conn, 'North');

// ---------------------- Bandwidth Totals ---------------------- //
$total_upstream = 0.0; $total_used = 0.0;
$sql = "SELECT COALESCE(SUM(current_bandwidth),0) AS total_up, COALESCE(SUM(used_bandwidth),0) AS total_used FROM bandwidth_reports";
if ($stmt = $conn->prepare($sql)) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $total_upstream = (float)$row['total_up'];
        $total_used = (float)$row['total_used'];
    }
    $stmt->close();
}

// Optional station filter
$stationFilter = isset($_GET['station']) ? trim($_GET['station']) : '';
$panelTitle = $stationFilter ? htmlspecialchars($stationFilter) . " Dashboard Overview" : "Admin Dashboard Overview";

// Get current month and year
$current_month = date('m');
$current_year  = date('Y');

// Calculate Bandwidth for Current Month
$bw_query = $conn->prepare("
    SELECT 
        COALESCE(SUM(current_bandwidth), 0) AS total_upstream,
        COALESCE(SUM(used_bandwidth), 0) AS total_used
    FROM bandwidth_reports
    WHERE MONTH(report_date) = ? 
    AND YEAR(report_date) = ?
");
$bw_query->bind_param("ii", $current_month, $current_year);
$bw_query->execute();
$bw_result = $bw_query->get_result()->fetch_assoc();

$total_upstream = $bw_result['total_upstream'];
$total_used     = $bw_result['total_used'];

include(__DIR__ . '/../includes/header.php'); 
?>

<!-- ===== External Libraries ===== -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
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
<div class="main-content">

  <!-- ===== Profile Dropdown ===== -->
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
        <li><a class="dropdown-item animated-item text-success" href="/gerrys_project/account/create_account.php"><i class="fa-solid fa-user-plus me-2"></i> Create New User</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger animated-item" href="/gerrys_project/log/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out</a></li>
      </ul>
    </div>
  </div>

  <!-- ===== Dashboard Header ===== -->
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0 fw-bold"><i class="fa-solid fa-chart-line me-2"></i> <?= $panelTitle ?></h2>
      <p class="muted mb-0">Welcome back, <strong><?= $username ?></strong> — Role: <?= $role ?></p>
    </div>
    <div class="muted text-end"><small>Year: <?= $currentYear ?></small></div>
  </div>

  <!-- ===== Dashboard Cards ===== -->
  <div class="cards-row mb-4">
    <!-- Customers Card -->
    <a href="../customers/customer_info.php" class="hover-card" data-aos="fade-up" data-aos-delay="50">
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


    <!-- CDN Card -->
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
  </div>

  <!-- ===== Graphs & Region-wise Stats ===== -->
  <div class="row" style="display:flex; gap:20px; flex-wrap:wrap;">
    <div style="flex:1 1 60%; min-width:300px;" data-aos="zoom-in" data-aos-delay="80">
      <div class="card p-3">
        <h6 class="fw-bold mb-3">Yearly New Customers (<?= $currentYear ?>)</h6>
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

  <!-- Region-wise Cards -->
  <div class="row mt-4" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:18px;">
    <?php foreach (['South'=>$south,'Central'=>$central,'North'=>$north] as $region => $result): ?>
      <div class="card region-card" data-aos="fade-up" data-aos-delay="60">
        <h5 class="fw-bold mb-3 text-center"><?= $region ?> Region</h5>
        <ul class="list-group list-group-flush">
          <?php if ($result && $result->num_rows): while ($row = $result->fetch_assoc()): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?= htmlspecialchars($row['station_name']) ?>
              <span class="badge-pill"><?= (int)$row['total_customers'] ?: '—' ?></span>
            </li>
          <?php endwhile; else: ?>
            <li class="list-group-item">No stations found.</li>
          <?php endif; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </div>

</div> <!-- /main-content -->

<!-- ===== Scripts ===== -->
<script>
AOS.init({ duration:700, easing:'ease-out-cubic', once:true });

function animateCounts(){
  const elements = document.querySelectorAll('[data-target]');
  elements.forEach(el=>{
    const target = parseInt(el.getAttribute('data-target'))||0;
    const duration = 900;
    let start=null;
    const initial=0;
    function step(timestamp){
      if(!start) start=timestamp;
      const progress = timestamp-start;
      const val = Math.min(Math.floor((progress/duration)*(target-initial)+initial), target);
      el.textContent = val.toLocaleString();
      if(progress<duration){ window.requestAnimationFrame(step); } else { el.textContent = target.toLocaleString(); }
    }
    window.requestAnimationFrame(step);
  });
}
document.addEventListener('DOMContentLoaded', animateCounts);

// ----- Chart.js: Monthly Line -----
(function(){
  const ctx = document.getElementById('customersChart')?.getContext('2d');
  if(!ctx) return;
  const gradient = ctx.createLinearGradient(0,0,0,220);
  gradient.addColorStop(0,"rgba(54,162,235,0.35)");
  gradient.addColorStop(1,"rgba(54,162,235,0)");
  new Chart(ctx,{
    type:'line',
    data:{
      labels:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
      datasets:[{
        label:"New Customers",
        data: <?= json_encode(array_values($monthlyCustomers)) ?>,
        borderColor:"rgba(54, 162, 235, 1)",
        backgroundColor: gradient,
        borderWidth:2.5,
        fill:true,
        tension:0.36,
        pointRadius:3
      }]
    },
    options:{ responsive:true, plugins:{legend:{display:false},tooltip:{mode:'index',intersect:false}}, scales:{y:{beginAtZero:true,grid:{color:'rgba(200,200,200,0.06)'}},x:{grid:{display:false}}}}
  });
})();

// ----- Chart.js: Status Doughnut -----
(function(){
  const ctx2 = document.getElementById('customerChart')?.getContext('2d');
  if(!ctx2) return;
  new Chart(ctx2,{
    type:'doughnut',
    data:{
      labels:['Active','Suspended','Temp Off','Terminated'],
      datasets:[{
        data:[
          <?= (int)$customerStatus['Active'] ?>,
          <?= (int)$customerStatus['Suspended'] ?>,
          <?= (int)$customerStatus['Temp Off'] ?>,
          <?= (int)$customerStatus['Terminated'] ?>
        ],
        backgroundColor:['#16a34a','#0b5bd7','#06b6d4','#ef4444'],
        borderColor:'#fff',borderWidth:2
      }]
    },
    options:{ plugins:{legend:{position:'bottom'}}, responsive:true }
  });
})();
</script>
