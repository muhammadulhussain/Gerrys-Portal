<?php
require_once __DIR__ . '/session_check.php'; // if you use session auth for exports as well
require_role(['Employee', 'Admin']);
require_once __DIR__ . '/db.php';

// Validate incoming month (YYYY-MM)
$month = $_POST['month'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    // fallback to current
    $month = date("Y-m");
}

$startDate = $month . "-01";
$endDate = date("Y-m-t", strtotime($startDate));

// Build query similar to the report page
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'Employee';
$userStationId = isset($_SESSION['station_id']) ? intval($_SESSION['station_id']) : null;

$where = " WHERE b.report_date BETWEEN '" . $conn->real_escape_string($startDate) . "' AND '" . $conn->real_escape_string($endDate) . "' ";

if (strcasecmp($role, 'Admin') !== 0 && $userStationId) {
    $where .= " AND b.station_id = " . intval($userStationId) . " ";
}

$query = "
SELECT 
    b.report_id,
    b.report_date,
    s.name AS station,
    IFNULL(v.vendor_name, '') AS vendor,
    b.client_type,
    b.current_bandwidth,
    b.used_bandwidth,
    (b.current_bandwidth - b.used_bandwidth) AS remaining_bandwidth,
    b.description
FROM bandwidth_reports b
JOIN stations s ON b.station_id = s.id
LEFT JOIN vendors v ON b.vendor_id = v.id
" . $where . "
ORDER BY b.report_date, s.name
;";

$result = $conn->query($query);

$filename = "bandwidth_report_" . $month . ".csv";

// Send headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Open output stream
$out = fopen('php://output', 'w');

// Output column headers
fputcsv($out, ['Report ID', 'Report Date', 'Station', 'Vendor', 'Client Type', 'Current Bandwidth (Mbps)', 'Used Bandwidth (Mbps)', 'Remaining (Mbps)', 'Description']);

// Output rows
if ($result !== false) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row['report_id'],
            $row['report_date'],
            $row['station'],
            $row['vendor'],
            $row['client_type'],
            number_format((float)$row['current_bandwidth'], 2, '.', ''),
            number_format((float)$row['used_bandwidth'], 2, '.', ''),
            number_format((float)$row['remaining_bandwidth'], 2, '.', ''),
            $row['description']
        ]);
    }
}

// Close stream and exit
fclose($out);
exit;
