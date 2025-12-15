<?php
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);

require_once __DIR__ . '/../includes/db.php';

// Get report_id from GET
$report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 0;

if ($report_id <= 0) {
    die("Invalid report ID.");
}

// Fetch existing record along with report_date
$stmt = $conn->prepare("
    SELECT report_id, station_id, vendor_id, client_type, current_bandwidth, used_bandwidth, description, report_date
    FROM bandwidth_reports
    WHERE report_id = ?
");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Record not found.");
}

$row = $result->fetch_assoc();

// Handle POST update
$successMsg = $errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_bandwidth = isset($_POST['current_bandwidth']) ? floatval($_POST['current_bandwidth']) : 0;
    $used_bandwidth    = isset($_POST['used_bandwidth']) ? floatval($_POST['used_bandwidth']) : 0;
    $description       = $_POST['description'] ?? '';
    $action_type       = $_POST['action_type'] ?? 'Upgrade';
    $changed_by        = $_SESSION['username'] ?? 'Unknown';

    $conn->begin_transaction();
    try {
        // Insert history before update
        $hist_stmt = $conn->prepare("
            INSERT INTO bandwidth_report_history
            (report_id, station_id, vendor_id, client_type, report_date, current_bandwidth, used_bandwidth, description, action_type, changed_by)
            SELECT report_id, station_id, vendor_id, client_type, report_date, current_bandwidth, used_bandwidth, description, ?, ?
            FROM bandwidth_reports
            WHERE report_id = ?
        ");
        $hist_stmt->bind_param("ssi", $action_type, $changed_by, $report_id);
        $hist_stmt->execute();

        // Update main record
        $update_stmt = $conn->prepare("
            UPDATE bandwidth_reports
            SET current_bandwidth = ?, used_bandwidth = ?, description = ?
            WHERE report_id = ?
        ");
        $update_stmt->bind_param("ddsi", $current_bandwidth, $used_bandwidth, $description, $report_id);
        $update_stmt->execute();

        $conn->commit();
        $successMsg = "Record updated successfully!";
        $row['current_bandwidth'] = $current_bandwidth;
        $row['used_bandwidth']    = $used_bandwidth;
        $row['description']       = $description;

    } catch (Exception $e) {
        $conn->rollback();
        $errorMsg = "Update failed: " . $e->getMessage();
    }
}

// Format report date
$reportDate = date("l, d-M-Y", strtotime($row['report_date']));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Update Bandwidth — Gerry's</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; }
.card { border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
.gerrys-btn { background-color:#ebb41e; border:none; color:#000; font-weight:600; transition:.3s; }
.gerrys-btn:hover { background-color:#d4a014; color:#fff; box-shadow:0 0 10px rgba(235,180,30,.6); transform:translateY(-2px);}
.brand { display:flex; align-items:center; gap:12px; margin-bottom:20px; }
.brand img { height:80px; }
.small-muted { color:#6c757d; font-size:.9rem }
</style>
</head>
<body>
<div class="container py-4">
    <div class="brand">
        <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Gerry's Logo">
        <div>
            <h4 class="mb-0">Update Bandwidth Report</h4>
            <div class="small-muted">Report Date: <?php echo $reportDate; ?></div>
        </div>
    </div>

    <div class="card p-4">
        <?php if($successMsg): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        <?php if($errorMsg): ?>
            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Current Bandwidth (Mbps)</label>
                <input type="number" step="0.01" name="current_bandwidth" class="form-control" required value="<?php echo htmlspecialchars($row['current_bandwidth']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Used Bandwidth (Mbps)</label>
                <input type="number" step="0.01" name="used_bandwidth" class="form-control" required value="<?php echo htmlspecialchars($row['used_bandwidth']); ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($row['description']); ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Action Type</label>
                <select name="action_type" class="form-select">
                    <option value="Upgrade">Upgrade</option>
                    <option value="Downgrade">Downgrade</option>
                    <option value="Delete">Delete</option>
                </select>
            </div>
            <div class="col-12 text-end">
                <a href="bandwidth_report.php" class="btn btn-secondary gerrys-btn">Cancel</a>
                <button type="submit" class="btn btn-primary gerrys-btn">Update</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
