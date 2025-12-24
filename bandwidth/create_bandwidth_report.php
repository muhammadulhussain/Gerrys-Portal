<?php
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);

require_once __DIR__ . '/../includes/db.php';

$success = false;
$error = "";

// Get station name for logged in user
$userStation = $_SESSION['station_id'] ?? null;
$stationName = '';

if ($userStation) {
    $stmt = $conn->prepare("SELECT name FROM stations WHERE id = ?");
    $stmt->bind_param("i", $userStation);
    $stmt->execute();
    $stmt->bind_result($stationName);
    $stmt->fetch();
    $stmt->close();
}

// Handle form submission
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $station_id  = $userStation; // auto locked
    $client_type = $_POST['client_type'] ?? null;
    $vendor_id   = $_POST['vendor_id'] ?? null;
    $current     = $_POST['current_bandwidth'];
    $used        = $_POST['used_bandwidth'];
    $desc        = trim($_POST['description']);

    // Normalize vendor
    if ($vendor_id === '' || $client_type === 'Gerrys Region') {
        $vendor_id = null;
    }

    // Validation
    if ($client_type === 'Vendor' && $vendor_id === null) {
        $error = "Vendor must be selected when Client Type is Vendor.";
    } elseif ($current < $used) {
        $error = "Used bandwidth cannot exceed current bandwidth.";
    } else {

        $sql = "INSERT INTO bandwidth_reports
                (station_id, vendor_id, client_type, current_bandwidth, used_bandwidth, description)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issdds",
            $station_id,
            $vendor_id,
            $client_type,
            $current,
            $used,
            $desc
        );

        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = "Database error: " . $conn->error;
        }

        $stmt->close();
    }
}


// Fetch Vendors
$vendors  = $conn->query("SELECT id, vendor_name FROM vendors ORDER BY vendor_name");

// Fetch Reports for this station only
$reports = $conn->prepare("
    SELECT br.report_date,
           s.name AS station_name,
           COALESCE(v.vendor_name, br.client_type) AS type,
           br.current_bandwidth,
           br.used_bandwidth,
           br.description
    FROM bandwidth_reports br
    JOIN stations s ON br.station_id = s.id
    LEFT JOIN vendors v ON br.vendor_id = v.id
    WHERE br.station_id = ?
    ORDER BY br.report_date DESC
");
$reports->bind_param("i", $userStation);
$reports->execute();
$result = $reports->get_result();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Bandwidth Report — Gerry's</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  body {
    background: #eef2f4;
    font-family: 'Poppins', sans-serif;
  }

  .wrapper {
    max-width: 900px;
    margin: 30px auto;
    background: #fff;
    padding: 25px;
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
  }

  .gerrys-btn { 
    background-color: #ebb41e; 
    border: none; 
    color: #000; 
    font-weight: 600; 
    transition: 0.3s; 
  }
  .gerrys-btn:hover { 
    background-color: #d4a014; 
    color: #fff; 
    box-shadow: 0 0 10px rgba(235, 180, 30, 0.6); 
    transform: translateY(-2px); 
  }

  table thead {
    background: #003366;
    color: #fff;
  }

  .header-area img {
    height: 65px;
  }
</style>

</head>
<body>

<div class="wrapper">

  <div class="d-flex justify-content-between align-items-center mb-3 header-area">
      <div class="d-flex align-items-center gap-3">
          <img src="https://www.gerrys.net/img/index/git_logo.png">
          <h3 class="m-0 fw-bold">Create Bandwidth Report</h3>
      </div>

      <a href="/gerrys_project/employee/employee_dashboard_.php" class="btn btn-secondary gerrys-btn">⬅ Back</a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">✅ Report added successfully!</div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger">⚠ <?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="row g-3">

    <div class="col-md-6">
      <label>Report Month</label>
      <input type="month" name="report_month" class="form-control" required>
    </div>          

    <div class="col-md-6">
      <label>Station (Auto Locked)</label>
      <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($stationName) ?>" disabled>
      <input type="hidden" name="station_id" value="<?= $userStation ?>">
    </div>

    <div class="col-md-6">
      <label>Client Type</label>
      <select name="client_type" id="client_type" class="form-select" onchange="toggleVendor()">
        <option value="">-- Select --</option>
        <option value="Gerrys Region">Gerrys Region</option>
        <option value="Vendor">Vendor</option>
      </select>
    </div>

    <div class="col-md-6" id="vendor_box" style="display:none;">
      <label>Vendor</label>
      <select name="vendor_id" id="vendor_id" class="form-select" onchange="clientTypeNull()">
        <option value="">-- Select Vendor --</option>
        <?php while ($v = $vendors->fetch_assoc()): ?>
        <option value="<?= $v['id'] ?>"><?= $v['vendor_name'] ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label>Current Bandwidth (Mbps)</label>
      <input type="number" step="0.01" name="current_bandwidth" class="form-control" required>
    </div>

    <div class="col-md-6">
      <label>Used Bandwidth (Mbps)</label>
      <input type="number" step="0.01" name="used_bandwidth" class="form-control" required>
    </div>

    <div class="col-12">
      <label>Description</label>
      <input type="text" name="description" class="form-control" placeholder="e.g. CIR-0048 Link">
    </div>

    <div class="text-end mt-3">
      <button class="btn btn-secondary gerrys-btn px-4">+ Add Report</button>
    </div>
  </form>

  <hr class="my-4">

  <h5 class="fw-bold mb-3">Recent Reports (Your Station)</h5>

  <table class="table table-bordered table-hover">
    <thead>
      <tr>
        <th>Date</th>
        <th>Station</th>
        <th>Type</th>
        <th>Current</th>
        <th>Used</th>
        <th>Description</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= date("d-F-Y", strtotime($row['report_date'])) ?></td>
          <td><?= $row['station_name'] ?></td>
          <td><?= $row['type'] ?></td>
          <td><?= $row['current_bandwidth'] ?> Mbps</td>
          <td><?= $row['used_bandwidth'] ?> Mbps</td>
          <td><?= htmlspecialchars($row['description']) ?></td>
        </tr>
      <?php endwhile; ?>
      <?php if($result->num_rows === 0): ?>
        <tr>
          <td colspan="6" class="text-center">No reports found for your station.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

</div>

<script>
function toggleVendor() {
    let ct = document.getElementById("client_type").value;

    document.getElementById("vendor_box").style.display =
        (ct === "Vendor") ? "block" : "none";

    if (ct !== "Vendor") {
        document.getElementById("vendor_id").value = "";
    }
}
</script>


</body>
</html>
