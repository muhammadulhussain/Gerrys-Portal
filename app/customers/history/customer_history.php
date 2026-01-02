<?php
require_once(__DIR__ . '/../../includes/db.php');
require_once __DIR__ . '/../../includes/session_check.php';
require_role(['Employee', 'Admin']);

// ✅ Get username for "Created By"
$username = $_SESSION['username'] ?? 'System';

// ✅ Get customer ID from URL
$customer_id = intval($_GET['id'] ?? 0);
if ($customer_id <= 0) {
  die("<h3 style='color:red;text-align:center;margin-top:40px;'>Invalid Customer ID</h3>");
}

// ✅ Fetch customer basic info
$custQuery = $conn->prepare("SELECT customer_name, company_name, bandwidth_mbps, status, mobile_no FROM customers WHERE id = ?");
$custQuery->bind_param("i", $customer_id);
$custQuery->execute();
$custResult = $custQuery->get_result();
$customer = $custResult->fetch_assoc();

// ✅ Fetch all history records for this customer
$historyQuery = $conn->prepare("
  SELECT ch.event_type, ch.old_value, ch.new_value, ch.start_date, ch.end_date, ch.notes, 
         u.username AS created_by, ch.created_at
  FROM customer_history ch
  LEFT JOIN users u ON ch.created_by = u.id
  WHERE ch.customer_id = ?
  ORDER BY ch.created_at DESC
");
$historyQuery->bind_param("i", $customer_id);
$historyQuery->execute();
$historyResult = $historyQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer History - Gerry's</title>
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
    .table th { background-color: #000; color: #ebb41e; text-align: center; }
    .table td { text-align: center; vertical-align: middle; }
    .card { border: none; border-radius: 12px; box-shadow: 0 0 15px rgba(0,0,0,0.1); margin-top: 40px; }
    .customer-info h5 span.label { color: #ebb41e; font-weight: 600; }
    .customer-info h5 span.value { color: #000; }
    .fade-in-up {opacity: 0; transform: translateY(20px); animation: fadeInUp 0.6s ease-out forwards;}
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
    <h3 class="ms-3">Customer Service History</h3>
  </div>
  <a href="../customer_info.php" class="btn btn-back">
    <i class="fa-solid fa-arrow-left me-1"></i> Back
  </a>
</div>

<div class="container">
  <div class="card">
    <div class="card-body">
      <div class="customer-info mb-4 text-center">
        <h5>
          <span class="label">Customer:</span>
          <span class="value"><?= htmlspecialchars($customer['customer_name']) ?></span> |
          <span class="label">Company:</span>
          <span class="value"><?= htmlspecialchars($customer['company_name']) ?></span>
        </h5>
        <p><strong class="label">Mobile No:</strong> <span class="value"><?= htmlspecialchars($customer['mobile_no']) ?></span></p>
        <p><strong class="label">Current Bandwidth:</strong> <span class="value"><?= htmlspecialchars($customer['bandwidth_mbps']) ?> Mbps</span></p>
        <p><strong class="label">Status:</strong>
          <?php
            $status = strtolower($customer['status'] ?? 'active');
            if ($status === 'active') echo '<span class="badge bg-success">Active</span>';
            elseif ($status === 'suspended') echo '<span class="badge bg-warning text-dark">Suspended</span>';
            elseif ($status === 'offboarded') echo '<span class="badge bg-danger">Terminated</span>';
            else echo '<span class="badge bg-secondary">'.ucfirst($status).'</span>';
          ?>
        </p>
      </div>

      <!-- 🔶 Table -->
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Event Type</th>
              <th>Old Value</th>
              <th>New Value</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Notes</th>
              <th>Created By</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($historyResult->num_rows > 0): ?>
              <?php $count = 1; while($row = $historyResult->fetch_assoc()): ?>
                <tr>
                  <td><?= $count++ ?></td>
                  <td>
                    <?php
                      $type = strtolower($row['event_type']);
                      if ($type === 'upgrade') echo '<span class="badge bg-success">Upgrade</span>';
                      elseif ($type === 'downgrade') echo '<span class="badge bg-info text-dark">Downgrade</span>';
                      elseif ($type === 'suspend') echo '<span class="badge bg-warning text-dark">Suspended</span>';
                      elseif ($type === 'temporary off') echo '<span class="badge bg-secondary">Temporary Off</span>';
                      elseif ($type === 'offboarded') echo '<span class="badge bg-danger">Terminated</span>';
                      else echo '<span class="badge bg-primary">'.ucfirst($type).'</span>';
                    ?>
                  </td>

                  <!-- ✅ Old Value (JSON Decode) -->
                  <td>
                    <?php
                      $old = $row['old_value'];
                      if (!empty($old) && str_starts_with(trim($old), '{')) {
                          $oldData = json_decode($old, true);
                          echo "BW: " . htmlspecialchars($oldData['bandwidth'] ?? '-') . " | Status: " . htmlspecialchars($oldData['status'] ?? '-');
                      } else {
                          echo htmlspecialchars($old ?? '-');
                      }
                    ?>
                  </td>

                  <!-- ✅ New Value (JSON Decode) -->
                  <td>
                    <?php
                      $new = $row['new_value'];
                      if (!empty($new) && str_starts_with(trim($new), '{')) {
                          $newData = json_decode($new, true);
                          echo "BW: " . htmlspecialchars($newData['bandwidth'] ?? '-') . " | Status: " . htmlspecialchars($newData['status'] ?? '-');
                      } else {
                          echo htmlspecialchars($new ?? '-');
                      }
                    ?>
                  </td>

                  <td><?= $row['start_date'] ? date("Y-m-d", strtotime($row['start_date'])) : '-' ?></td>
                  <td><?= $row['end_date'] ? date("Y-m-d", strtotime($row['end_date'])) : '-' ?></td>
                  <td><?= htmlspecialchars($row['notes'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($row['created_by'] ?? 'System') ?></td>
                  <td><?= date("Y-m-d H:i", strtotime($row['created_at'])) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="9" class="text-muted">No service history found for this customer.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
