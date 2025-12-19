<?php
require_once(__DIR__ . '/../includes/db.php'); 
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);

$role = $_SESSION['role'] ?? 'Employee';
$station_id = $_SESSION['station_id'] ?? null;

if (strcasecmp($role, 'Admin') === 0) {
    $dashboardURL = '/gerrys_project/admin/admin_dashboard.php';
} else {
    $dashboardURL = '/gerrys_project/employee/employee_dashboard_.php';
}
$search = trim($_GET['search'] ?? '');
$searchLike = '%' . $search . '%';

// Fetch customers
if (strcasecmp($role, 'Admin') === 0) {

    if ($search !== '') {
        $sql = "SELECT c.*, s.name AS station_name
                FROM customers c
                LEFT JOIN stations s ON c.station_id = s.id
                WHERE c.customer_name LIKE ?
                   OR c.company_name LIKE ?
                   OR c.ip LIKE ?
                ORDER BY c.id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
    } else {
        $sql = "SELECT c.*, s.name AS station_name
                FROM customers c
                LEFT JOIN stations s ON c.station_id = s.id
                ORDER BY c.id DESC";
        $stmt = $conn->prepare($sql);
    }

} else {

    if ($search !== '') {
        $sql = "SELECT c.*, s.name AS station_name
                FROM customers c
                LEFT JOIN stations s ON c.station_id = s.id
                WHERE c.station_id = ?
                  AND (c.customer_name LIKE ?
                   OR c.company_name LIKE ?
                   OR c.ip LIKE ?)
                ORDER BY c.id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $station_id, $searchLike, $searchLike, $searchLike);
    } else {
        $sql = "SELECT c.*, s.name AS station_name
                FROM customers c
                LEFT JOIN stations s ON c.station_id = s.id
                WHERE c.station_id = ?
                ORDER BY c.id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $station_id);
    }
}

$stmt->execute();
$result = $stmt->get_result();
$customers = $result->fetch_all(MYSQLI_ASSOC);


// Fetch POPs & Vendors
$customerIds = array_column($customers, 'id');
$customerPops = [];
$customerVendors = [];

if (count($customerIds) > 0) {
    $ids = implode(',', $customerIds);

    // POPs
    $popQuery = "SELECT cp.customer_id, p.pop_name
                 FROM customer_pops cp
                 JOIN pops p ON cp.pop_id = p.pop_id
                 WHERE cp.customer_id IN ($ids)";
    $popResult = $conn->query($popQuery);
    while ($row = $popResult->fetch_assoc()) {
        $customerPops[$row['customer_id']][] = $row['pop_name'];
    }

    // Vendors
    $vendorQuery = "SELECT cv.customer_id, v.vendor_name
                    FROM customer_vendors cv
                    JOIN vendors v ON cv.vendor_id = v.id
                    WHERE cv.customer_id IN ($ids)";
    $vendorResult = $conn->query($vendorQuery);
    while ($row = $vendorResult->fetch_assoc()) {
        $customerVendors[$row['customer_id']][] = $row['vendor_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Information</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; }
.table th { background-color: #212529; color: white; }
.gerrys-btn { background-color: #ebb41e; border: none; color: #000; font-weight: 600; transition: 0.3s; }
.gerrys-btn:hover { background-color: #d4a014; color: #fff; box-shadow: 0 0 10px rgba(235,180,30,0.6); transform: translateY(-2px); }
#searchCount { font-size: 16px; padding: 4px 10px; background: #0d6efd; color: #fff; border-radius: 20px; display: inline-block; opacity: 0; transform: scale(0.8); transition: 0.3s ease-in-out; }
#searchCount.show { opacity: 1; transform: scale(1); }
#scrollBtn { position: fixed; right: 50%; bottom: 20px; transform: translateX(50%); z-index: 9999; padding: 10px 20px; background: #ebb41e; color: #000; font-weight: bold; border-radius: 30px; border: none; box-shadow: 0 0 10px rgba(0,0,0,0.3); cursor: pointer; transition: 0.3s; }
#scrollBtn:hover { background: #d4a014; color:#fff; }
.page-animate { opacity: 0; transform: translateY(20px); transition: opacity 0.8s ease, transform 0.8s ease; }
.page-animate.show { opacity: 1; transform: translateY(0); }
</style>
</head>
<body>
<div class="container-fluid py-4">
  <div class="card shadow p-4 page-animate" id="animatePage">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center">
        <img src="https://www.gerrys.net/img/index/git_logo.png" style="height:80px;margin-right:10px;">
        <h4 class="fw-bold mb-0">Customers Information</h4>
      </div>
      <div class="d-flex align-items-center">
        <form method="GET" class="d-flex align-items-center">
            <input type="text"
                  name="search"
                  value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                  class="form-control me-2"
                  placeholder="Search Customer..."
                  style="width:250px;">
        </form>
        <span id="searchCount"></span>
        <button class="btn gerrys-btn ms-3" onclick="window.location.href='<?= $dashboardURL ?>'">
          <i class="fa-solid fa-arrow-left"></i> Back
        </button>
      </div>
    </div>
    <div class="table-responsive">
      <table id="customersTable" class="table table-bordered table-hover text-center align-middle">
        <thead>
          <tr>
            <th>F.ID</th>
            <th>Customer Name</th>
            <th>Company POC</th>
            <th>IP</th>
            <th>Bandwidth</th>
            <th>Connection</th>
            <th>POP(s)</th>
            <th>Station</th>
            <th>Status</th>
            <th>Detail</th>
          </tr>
        </thead>
        <tbody>
        <?php if (count($customers) > 0): ?>
          <?php $counter = 1; ?>
          <?php foreach ($customers as $row): ?>
          <tr>
            <td><?= $counter ?></td>
            <td><?= $row['customer_name'] ?></td>
            <td><?= $row['company_name'] ?></td>
            <td><?= $row['ip'] ?></td>
            <td><?= $row['bandwidth_mbps'] ?> Mbps</td>
            <td><?= $row['connection_type'] ?></td>
            <td><?= isset($customerPops[$row['id']]) ? implode(', ', $customerPops[$row['id']]) : '-' ?></td>
            <td><?= $row['station_name'] ?? 'N/A' ?></td>
            <td>
              <?php
                $status = strtolower($row['status']);
                if ($status === 'active') echo '<span class="badge bg-success">Active</span>';
                elseif ($status === 'terminated') echo '<span class="badge bg-danger">Terminated</span>';
                elseif ($status === 'suspended') echo '<span class="badge" style="background:#092664;color:white;">Suspended</span>';
                elseif ($status === 'temp off') echo '<span class="badge bg-info text-dark">Temp Off</span>';
                else echo '<span class="badge bg-secondary">'.ucfirst($status).'</span>';
              ?>
            </td>
            <td>
              <button class="btn btn-sm gerrys-btn" data-bs-toggle="modal" 
              data-bs-target="#detailsModal<?= $row['id'] ?>">See More</button>
            </td>
          </tr>
          <?php $counter++; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="11">No customers found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modals -->
<?php foreach ($customers as $row): ?>
<div class="modal fade" id="detailsModal<?= $row['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Customer Full Details</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><strong>CNIC / NTN No:</strong> <?= $row['cnic_no'] ?></div>
          <div class="col-md-6"><strong>Email:</strong> <?= $row['email'] ?></div>
          <div class="col-md-6"><strong>Mobile:</strong> <?= $row['mobile_no'] ?></div>
          <div class="col-md-12"><strong>Location:</strong> <?= $row['location'] ?></div>
          <div class="col-md-12"><strong>Address:</strong> <?= $row['address'] ?></div>
          <div class="col-md-6"><strong>Installed By:</strong> <?= $row['installed_by'] ?></div>
          <div class="col-md-6"><strong>Installed Date:</strong> <?= $row['install_date'] ?></div>
          <div class="col-md-6"><strong>POP(s):</strong> <?= isset($customerPops[$row['id']]) ? implode(', ', $customerPops[$row['id']]) : 'N/A' ?></div>
          <div class="col-md-6"><strong>Vendor(s):</strong> <?= isset($customerVendors[$row['id']]) ? implode(', ', $customerVendors[$row['id']]) : 'N/A' ?></div>
          <div class="col-md-6"><strong>Subscriber Type:</strong> <?= $row['subscriber_type'] ?></div>
          <div class="col-md-6"><strong>VLAN:</strong> <?= $row['vlan'] ?></div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="/gerrys_project/customers/history/customer_history.php?id=<?= $row['id'] ?>" class="btn gerrys-btn">
          <i class="fa-solid fa-clock-rotate-left"></i> History
        </a>
        <a href="/gerrys_project/customers/service-request/service_request.php?customer_id=<?= $row['id'] ?>" class="btn gerrys-btn">
          <i class="fa-solid fa-headset"></i> Service
        </a>
        <a href="/gerrys_project/customers/edit/edit_customers.php?id=<?= $row['id'] ?>" class="btn gerrys-btn">Edit</a>
      </div>
    </div>
  </div>
</div>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     
<?php endforeach; ?>

<button id="scrollBtn" onclick="scrollPage()">↓ Scroll</button>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function searchTable() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll("#customersTable tbody tr");
    let count = 0;
    rows.forEach(row => {
        if (row.innerText.toLowerCase().includes(input)) { row.style.display = ""; count++; }
        else row.style.display = "none";
    }); 
    let countBox = document.getElementById("searchCount");
    if (input.length > 0) { countBox.innerHTML = count > 0 ? `Found ${count} customer(s)` : "No matching customers"; countBox.classList.add("show"); }
    else { countBox.classList.remove("show"); countBox.innerHTML = ""; }
}
window.addEventListener("load", () => { document.getElementById("animatePage").classList.add("show"); });
let atBottom = false;
function scrollPage() {
    if (!atBottom) { window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }); atBottom = true; document.getElementById("scrollBtn").innerText = "↑ Top"; }
    else { window.scrollTo({ top: 0, behavior: 'smooth' }); atBottom = false; document.getElementById("scrollBtn").innerText = "↓ Scroll"; }
}
</script>
</body>
</html>
