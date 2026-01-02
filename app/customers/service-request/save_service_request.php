<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_role(['Employee', 'Admin']);

// ✅ Enable mysqli errors for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ✅ Access Check
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ✅ POST Data
$customer_id = intval($_POST['customer_id'] ?? 0);
$event_type  = $_POST['event_type'] ?? '';
$new_bandwidth = $_POST['new_value'] ?: 0;
$start_date = $_POST['start_date'] ?: NULL;
$end_date   = $_POST['end_date'] ?: NULL;
$notes      = $_POST['notes'] ?? NULL;
$created_by = $_SESSION['user_id'];

if ($customer_id <= 0) {
    die("❌ Invalid Customer ID.");
}

// ✅ Fetch Customer Details
$stmt = $conn->prepare("SELECT id, bandwidth_mbps, status FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
if (!$customer) die("❌ Customer not found.");

// ✅ Map form value to DB ENUM
$event_map = [
    'Upgrade'       => 'Upgrade',
    'Downgrade'     => 'Downgrade',
    'Suspend'       => 'Suspended',
    'Temporary Off' => 'Temporary Off',
    'Terminate'     => 'Terminated',
    'Reactivated'   => 'Reactivated'
];
$event_type_db = $event_map[$event_type] ?? $event_type;

// ✅ Old and New JSON
$old_value_json = json_encode([
    'bandwidth' => $customer['bandwidth_mbps'],
    'status' => $customer['status']
]);

$new_value_json = json_encode([
    'bandwidth' => $new_bandwidth ?: $customer['bandwidth_mbps'],
    'status' => ($event_type_db === 'Reactivated') ? 'Active' : $event_type_db
]);

// ✅ Insert into customer_history
$insert = $conn->prepare("
    INSERT INTO customer_history
    (customer_id, event_type, old_value, new_value, start_date, end_date, notes, created_by, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
$insert->bind_param("issssssi",
    $customer_id,
    $event_type_db,
    $old_value_json,
    $new_value_json,
    $start_date,
    $end_date,
    $notes,
    $created_by
);
$insert->execute();

// ✅ Update customers table
switch($event_type_db) {
    case 'Upgrade':
    case 'Downgrade':
        $update = $conn->prepare("UPDATE customers SET bandwidth_mbps = ? WHERE id = ?");
        $update->bind_param("di", $new_bandwidth, $customer_id);
        $update->execute();
        break;
    case 'Reactivated':
        $update = $conn->prepare("UPDATE customers SET status = 'Active', bandwidth_mbps = ? WHERE id = ?");
        $update->bind_param("di", $new_bandwidth, $customer_id);
        $update->execute();
        break;
    case 'Temporary Off':
    case 'Suspended':
    case 'Terminated':
        $update = $conn->prepare("UPDATE customers SET status = ? WHERE id = ?");
        $update->bind_param("si", $event_type_db, $customer_id);
        $update->execute();
        break;
}

// ✅ Success
echo "<script>alert('✅ Request saved successfully!'); window.location.href='../customer_info.php';</script>";
exit();
?>
