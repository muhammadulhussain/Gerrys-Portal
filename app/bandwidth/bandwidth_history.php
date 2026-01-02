<?php
require_once(__DIR__ . '/../includes/db.php');
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);

// Username for display
$username = $_SESSION['username'] ?? 'System';

// Get Report ID
$report_id = intval($_GET['report_id'] ?? 0);
if ($report_id <= 0) {
  die("<h3 style='color:red;text-align:center;margin-top:40px;'>Invalid Report ID</h3>");
}

// Fetch Main Bandwidth Report Details
$reportQuery = $conn->prepare("
  SELECT br.report_id, br.station_id, br.vendor_id, br.client_type, br.current_bandwidth, 
         br.used_bandwidth, br.description, br.report_date,
         s.name AS station_name, v.vendor_name
  FROM bandwidth_reports br
  LEFT JOIN stations s ON br.station_id = s.id
  LEFT JOIN vendors v ON br.vendor_id = v.id
  WHERE br.report_id = ?
");
$reportQuery->bind_param("i", $report_id);
$reportQuery->execute();
$report = $reportQuery->get_result()->fetch_assoc();

// Fetch History Records
$historyQuery = $conn->prepare("
  SELECT h.action_type, h.current_bandwidth, h.used_bandwidth, h.description, 
         h.report_date, h.changed_by, h.changed_at
  FROM bandwidth_report_history h
  WHERE h.report_id = ?
  ORDER BY h.changed_at DESC
");
$historyQuery->bind_param("i", $report_id);
$historyQuery->execute();
$historyResult = $historyQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bandwidth History - Gerry's</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    body { background-color: #fdfcf8; font-family: 'Poppins', sans-serif; }

    .header-bar {
      background: linear-gradient(90deg, #fff, #ebb41e);
      padding: 15px 25px;
      display: flex; align-items: center; justify-content: space-between;
      border-radius: 0 0 12px 12px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    }

    .header-bar img { height: 70px; }
    .header-bar h3 { margin: 0; font-weight: 700; }

    .btn-back { background-color: #ebb41e; border: none; color: #000; font-weight: 600; }
    .btn-back:hover { background-color: #fff; color: #ebb41e; }

    .table th {
      background-color: #000;
      color: #ebb41e;
      text-align: center;
    }

    .table td {
      text-align: center;
      vertical-align: middle;
    }

    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      margin-top: 40px;
    }

    .info-label { color: #ebb41e; font-weight: 600; }

    .fade-in-up {
      opacity: 0; transform: translateY(20px);
      animation: fadeInUp 0.6s ease-out forwards;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>

<body class="fade-in-up">

<div class="header-bar">
  <div class="d-flex align-items-center">
    <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Gerry's Logo">
    <h3 class="ms-3">Bandwidth Report History</h3>
  </div>

  <a href="bandwidth_report.php" class="btn btn-back">
    <i class="fa-solid fa-arrow-left me-1"></i> Back
  </a>
</div>

<div class="container">

  <div class="card">
    <div class="card-body">

      <!-- Report Info -->
      <div class="text-center mb-4">
        <h5>
          <span class="info-label">Station:</span> <?= htmlspecialchars($report['station_name']) ?> |
          <span class="info-label">Vendor:</span> <?= htmlspecialchars($report['vendor_name'] ?? 'N/A') ?>
        </h5>
        <p><strong class="info-label">Client Type:</strong> <?= htmlspecialchars($report['client_type']) ?></p>
        <p><strong class="info-label">Report Date:</strong> <?= date("d-M-Y", strtotime($report['report_date'])) ?></p>
      </div>

      <!-- History Table -->
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Action</th>
              <th>Current BW</th>
              <th>Used BW</th>
              <th>Description</th>
              <th>Report Date</th>
              <th>Changed By</th>
              <th>Changed At</th>
            </tr>
          </thead>

          <tbody>
            <?php if ($historyResult->num_rows > 0): ?>
              <?php $count = 1; while($row = $historyResult->fetch_assoc()): ?>

                <tr>
                  <td><?= $count++ ?></td>

                  <td>
                    <?php
                      $type = strtolower($row['action_type']);
                      if ($type === 'upgrade') echo '<span class="badge bg-success">Upgrade</span>';
                      elseif ($type === 'downgrade') echo '<span class="badge bg-info text-dark">Downgrade</span>';
                      elseif ($type === 'delete') echo '<span class="badge bg-danger">Delete</span>';
                      else echo '<span class="badge bg-secondary">'.ucfirst($type).'</span>';
                    ?>
                  </td>

                  <td><?= htmlspecialchars($row['current_bandwidth']) ?> Mbps</td>
                  <td><?= htmlspecialchars($row['used_bandwidth']) ?> Mbps</td>
                  <td><?= htmlspecialchars($row['description']) ?></td>
                  <td><?= date("d-M-Y", strtotime($row['report_date'])) ?></td>
                  <td><?= htmlspecialchars($row['changed_by']) ?></td>
                  <td><?= date("Y-m-d H:i", strtotime($row['changed_at'])) ?></td>
                </tr>

              <?php endwhile; ?>

            <?php else: ?>
              <tr>
                <td colspan="8" class="text-muted">No history found for this report.</td>
              </tr>
            <?php endif; ?>
          </tbody>

        </table>
      </div>

    </div>
  </div>

</div>

</body>
</html>
