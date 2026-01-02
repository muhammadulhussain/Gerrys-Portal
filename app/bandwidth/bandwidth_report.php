<?php
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);
require_once __DIR__ . '/../includes/db.php';

// Role & station
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Employee';
$userStationId = isset($_SESSION['station_id']) ? intval($_SESSION['station_id']) : null;

// Report generation date
$reportDate = date("l, d-M-Y");

// Dashboard URL
$dashboardURL = strcasecmp($role, 'Admin') === 0
    ? '/gerrys_project/admin/admin_dashboard.php'
    : '/gerrys_project/employee/employee_dashboard_.php';

// Month selection
$selectedMonth = isset($_GET['month']) ? trim($_GET['month']) : date("Y-m");
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date("Y-m");
}
$startDate = $selectedMonth . "-01";
$endDate = date("Y-m-t", strtotime($startDate));

// Sections (frontend labels unchanged)
$sections = [
    "Gerrys Region" => [],
    "GERRY'S Vendor" => []
]; 

// WHERE clause
$where = " WHERE b.report_date BETWEEN '" . $conn->real_escape_string($startDate) . "' 
           AND '" . $conn->real_escape_string($endDate) . "' ";

if (strcasecmp($role, 'Admin') !== 0 && $userStationId) {
    $where .= " AND b.station_id = " . intval($userStationId) . " ";
}

// Updated query (BACKEND ONLY)
$query = "
SELECT 
    b.report_id,
    s.name AS station,
    v.vendor_name AS vendor,
    b.client_type,
    b.current_bandwidth,
    b.used_bandwidth,
    (b.current_bandwidth - b.used_bandwidth) AS remaining_bandwidth,
    b.description,
    b.station_id,
    CASE
        WHEN b.client_type = 'Gerrys Region' THEN 'Gerrys Region'
        WHEN b.client_type = 'Vendor' THEN 'GERRY''S Vendor'
        ELSE 'Gerrys Region'
    END AS section_type
FROM bandwidth_reports b
JOIN stations s ON b.station_id = s.id
LEFT JOIN vendors v ON b.vendor_id = v.id
" . $where . "
ORDER BY section_type, s.name
";

// Execute
$result = $conn->query($query);
if ($result !== false) {
    while ($row = $result->fetch_assoc()) {
        $sec = $row['section_type'];

        if (!isset($sections[$sec])) {
            continue;
        }

        $sections[$sec][] = [
            'report_id' => $row['report_id'],
            'station'   => strtoupper($row['station']),
            'vendor'    => $row['vendor'] ?? '',
            'current'   => (float)$row['current_bandwidth'],
            'used'      => (float)$row['used_bandwidth'],
            'remaining' => (float)$row['remaining_bandwidth'],
            'desc'      => $row['description']
        ];
    }
}

// Utility
function format_num($n) {
    return number_format((float)$n, 2);
}

// Last 12 months
$months = [];
for ($i = 0; $i < 12; $i++) {
    $mVal = date("Y-m", strtotime("-$i months"));
    $months[$mVal] = date("F Y", strtotime($mVal));
}
?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bandwidth Report — Gerry's</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .report-card { border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
    .brand { display:flex; align-items:center; gap:12px; }
    .gerrys-btn { background-color: #ebb41e; border: none; color: #000; font-weight: 600; transition: 0.3s; }
    .gerrys-btn:hover { background-color: #d4a014; color: #fff; box-shadow: 0 0 10px rgba(235, 180, 30, 0.6); transform: translateY(-2px); }
    .brand img { height:76px; }
    .small-muted { color:#6c757d; font-size:.9rem }
    .table thead th { border-bottom:2px solid #dee2e6; }
    .totals { font-weight:700 }
    @media print { .no-print { display:none !important; } }
    .whole-git { position: relative; border: 3px solid #ebb41e; border-radius: 10px; overflow: hidden; background: linear-gradient(180deg, rgba(255, 239, 180, 0.9), rgba(255, 248, 220, 0.95)); box-shadow: 0 4px 20px rgba(235, 180, 30, 0.3); }
    .whole-git::before { content: ""; background: url('https://www.gerrys.net/img/index/git_logo.png') center/120px no-repeat; opacity: 0.08; position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 0; }
    .whole-git-content { position: relative; z-index: 1; padding: 1.2rem; }
    .whole-git h5 { color: #000; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    /* Existing media queries improve karte hain */
@media (max-width: 768px) {
  .brand {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }

  .brand img {
    height: 50px;
  }

  .whole-git-content h5 {
    font-size: 1rem;
  }

  .table th, .table td {
    font-size: 0.85rem;
    padding: 0.35rem 0.5rem;
  }

  .gerrys-btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    margin-top: 2px;
  }

  .d-flex.justify-content-between.align-items-center.mb-3 {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }

  .text-end {
    text-align: left !important;
  }
}

@media (max-width: 576px) {
  .table-responsive {
    overflow-x: auto;
  }

  .small-muted {
    font-size: 0.75rem;
  }

  .report-card, .card, .whole-git {
    padding: 0.75rem;
  }
}
  </style>
</head>
<body>
<div class="container py-4">
  <!-- Month selector -->
  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
      <form method="GET" class="d-flex align-items-center gap-2">
        <label for="month" class="mb-0 fw-bold">Select Month:</label>
        <select id="month" name="month" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
          <?php foreach ($months as $val => $label): ?>
            <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($val === $selectedMonth) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($label); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <!-- keep a manual submit as fallback for non-js -->
        <noscript><button type="submit" class="btn gerrys-btn btn-sm">Go</button></noscript>
      </form>
    </div>

    <div class="text-end">
      <div class="brand">
        <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Gerry's Logo">
        <div>
          <h4 class="mb-0">Gerry's Bandwidth Report</h4>
          <div class="small-muted">Date: <?php echo $reportDate; ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small-muted">Viewing: <strong><?php echo htmlspecialchars(date("F Y", strtotime($startDate))); ?></strong></div>
    <div class="text-end no-print">
      <button class="btn btn-secondary gerrys-btn" onclick="window.location.href='<?php echo htmlspecialchars($dashboardURL); ?>'">
        <i class="fa-solid fa-arrow-left"></i> Go Back
      </button>

      <form action="../includes/export_csv.php" method="POST" style="display: inline-block;">
        <input type="hidden" name="month" value="<?php echo htmlspecialchars($selectedMonth); ?>">
        <button type="submit" class="btn gerrys-btn">Export CSV</button>
      </form>

      <button class="btn btn-primary gerrys-btn" onclick="window.print()">Print</button>
    </div>
  </div>

  <div class="report-card bg-white p-3 mb-4">
    <div class="row">
      <div class="col-md-8">
        <p class="mb-0 small-muted">Professional bandwidth overview across stations. Use Export CSV to download data.</p>
      </div>
      <div class="col-md-4 text-end small-muted">
        Generated by: <strong>Network Operations Center</strong>
      </div>
    </div>
  </div>

  <?php
  $totalCurrentAll = 0;
  $totalUsedAll = 0;
  $totalRemainingAll = 0;
  ?>

  <?php foreach ($sections as $sectionName => $rows): ?>
    <?php
      $sumCurrent = 0; $sumUsed = 0; $sumRemaining = 0;
      foreach ($rows as $row) {
          $sumCurrent += $row['current'];
          $sumUsed += $row['used'];
          $sumRemaining += $row['remaining'];
      }
      $totalCurrentAll += $sumCurrent;
      $totalUsedAll += $sumUsed;
      $totalRemainingAll += $sumRemaining;
    ?>
    <div class="card mb-4">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo htmlspecialchars($sectionName); ?></h5>
        <div class="small-muted">Summary</div>
      </div>
      <div class="card-body p-0">
        <?php if (empty($rows)): ?>
          <div class="p-3 small-muted">No records to show in this section.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead class="table-light">
              <tr>
                <th>Station / Description</th>
                <th class="text-end">Current Bandwidth (Mbps)</th>
                <th class="text-end">Used Bandwidth (Mbps)</th>
                <th class="text-end">Remaining (Mbps)</th>
                <th class="text-center no-print">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <tr>
                  <td>
                    <strong>
                        <?php echo htmlspecialchars($row['station']); ?>
                        <?php if ($sectionName === "GERRY'S Vendor" && !empty($row['vendor'])): ?>
                            — <span class="text-primary"><?php echo htmlspecialchars($row['vendor']); ?></span>
                        <?php endif; ?>
                    </strong>
                    <div class="small-muted"><?php echo htmlspecialchars($row['desc']); ?></div>
                  </td>
                  <td class="text-end"><?php echo format_num($row['current']); ?></td>
                  <td class="text-end"><?php echo format_num($row['used']); ?></td>
                  <td class="text-end"><?php echo format_num($row['remaining']); ?></td>
                  <td class="text-center no-print">
                    <a href="update_bandwidth.php?report_id=<?php echo $row['report_id']; ?>" class="btn btn-sm btn-warning">Update</a>
                    <a href="bandwidth_history.php?report_id=<?php echo $row['report_id']; ?>" class="btn btn-sm btn-warning">
                      <i class="fa-solid fa-clock-rotate-left"></i> History
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
              <tr>
                <td class="totals">TOTAL</td>
                <td class="text-end totals"><?php echo format_num($sumCurrent); ?></td>
                <td class="text-end totals"><?php echo format_num($sumUsed); ?></td>
                <td class="text-end totals"><?php echo format_num($sumRemaining); ?></td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- WHOLE GIT TOTAL -->
  <div class="whole-git my-4">
    <div class="whole-git-content">
      <h5 class="mb-3"><i class="fa-solid fa-network-wired me-2"></i> WHOLE GIT TOTAL — All Sections Combined</h5>
      <table class="table mb-0">
        <thead>
          <tr>
            <th></th>
            <th class="text-end">Total Current Bandwidth (Mbps)</th>
            <th class="text-end">Total Used Bandwidth (Mbps)</th>
            <th class="text-end">Total Remaining (Mbps)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="totals">Overall Total</td>
            <td class="text-end totals"><?php echo format_num($totalCurrentAll); ?></td>
            <td class="text-end totals"><?php echo format_num($totalUsedAll); ?></td>
            <td class="text-end totals"><?php echo format_num($totalRemainingAll); ?></td>
          </tr>
        </tbody>
      </table>
      <div class="text-end small-muted fst-italic mt-2">
        <strong>Official Report — Gerry’s Information & Technology (GIT) Bandwidth Summary</strong>
      </div>
    </div>
  </div>
</div>
</body>
</html>
