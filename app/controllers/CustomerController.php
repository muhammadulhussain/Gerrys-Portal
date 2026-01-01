<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session_check.php';

require_role(['Employee', 'Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

/* =========================
   REQUIRED FIELDS
========================= */
$required = ['customer_name','connection_type','bandwidth','installed_by','status'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        die("Required field missing: {$field}");
    }
}

/* =========================
   COLLECT INPUTS
========================= */
$customer_name   = trim($_POST['customer_name']);
$company_name    = trim($_POST['company_name'] ?? null);
$cnic_no         = trim($_POST['cnic_no'] ?? null);
$ip              = trim($_POST['ip_address'] ?? null);
$address         = trim($_POST['address'] ?? null);
$location        = trim($_POST['location'] ?? null);
$connection_type = trim($_POST['connection_type']);
$bandwidth_mbps  = trim($_POST['bandwidth']);
$installed_by    = trim($_POST['installed_by']);
$status          = trim($_POST['status']);
$install_date    = !empty($_POST['install_date']) ? $_POST['install_date'] : null;
$vlan            = trim($_POST['vlan'] ?? null);
$subscriber_type = trim($_POST['subscriber_type'] ?? null);
$remarks         = trim($_POST['remarks'] ?? null);
$station_id      = (int)($_SESSION['station_id'] ?? 0);

/* =========================
   EMAILS & MOBILES
========================= */
$email = !empty($_POST['emails']) ? implode(',', array_filter($_POST['emails'])) : null;
$mobile_no = !empty($_POST['mobile_numbers']) ? implode(',', array_filter($_POST['mobile_numbers'])) : null;

/* =========================
   INSERT CUSTOMER
========================= */
$sql = "INSERT INTO customers (
    customer_name, company_name, ip, bandwidth_mbps, connection_type,
    station_id, status, cnic_no, email, address, location,
    installed_by, install_date, mobile_no, vlan, subscriber_type, remarks
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);
if (!$stmt) die($conn->error);

$stmt->bind_param(
    "sssssisssssssssss",
    $customer_name, $company_name, $ip, $bandwidth_mbps, $connection_type,
    $station_id, $status, $cnic_no, $email, $address, $location,
    $installed_by, $install_date, $mobile_no, $vlan, $subscriber_type, $remarks
);

$stmt->execute();
$customer_id = $stmt->insert_id;
$stmt->close();

/* =========================
   POPs SAVE
========================= */
if (!empty($_POST['pop_id']) && is_array($_POST['pop_id'])) {
    $pop_stmt = $conn->prepare(
        "INSERT INTO customers_pops (customer_id, pop_id) VALUES (?,?)"
    );
    foreach ($_POST['pop_id'] as $pop_id) {
        $pop_id = (int)$pop_id;
        if ($pop_id > 0) {
            $pop_stmt->bind_param("ii", $customer_id, $pop_id);
            $pop_stmt->execute();
        }
    }
    $pop_stmt->close();
}

/* =========================
   VENDORS SAVE (FIXED)
========================= */
$vendors = [];

if (!empty($_POST['vendor_id']) && is_array($_POST['vendor_id'])) {
    $vendors = $_POST['vendor_id'];
} elseif (!empty($_POST['vendor_hidden'])) {
    $vendors = [$_POST['vendor_hidden']];
}

if (!empty($vendors)) {
    $vendor_stmt = $conn->prepare(
        "INSERT INTO customer_vendors (customer_id, vendor_id) VALUES (?,?)"
    );
    foreach ($vendors as $vendor_id) {
        $vendor_id = (int)$vendor_id;
        if ($vendor_id > 0) {
            $vendor_stmt->bind_param("ii", $customer_id, $vendor_id);
            $vendor_stmt->execute();
        }
    }
    $vendor_stmt->close();
}

echo "<script>
alert('Customer saved successfully');
window.location.href='customer_form.php';
</script>";
