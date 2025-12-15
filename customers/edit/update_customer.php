<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_role(['Admin']); // Only Admin

include($_SERVER['DOCUMENT_ROOT'] . "/gerrys_project/includes/db.php");

// ----- Validate Customer ID -----
$customer_id = intval($_POST['customer_id'] ?? 0);
if (!$customer_id) {
    die("Invalid Request!");
}

// ----- Fetch Form Inputs -----
$customer_name      = $_POST['customer_name'] ?? NULL;
$company_name       = $_POST['company_name'] ?? NULL;
$cnic_no            = $_POST['cnic_no'] ?? NULL;

// Multiple Emails
$email = !empty($_POST['emails']) 
    ? implode(",", array_filter($_POST['emails'])) 
    : NULL;

// Multiple Mobile Numbers
$mobile_no = !empty($_POST['mobile_numbers']) 
    ? implode(",", array_filter($_POST['mobile_numbers'])) 
    : NULL;

$ip                 = $_POST['ip'] ?? NULL;
$address            = $_POST['address'] ?? NULL;
$connection_type    = $_POST['connection_type'] ?? NULL;
$status             = $_POST['status'] ?? NULL;
$bandwidth_mbps     = $_POST['bandwidth_mbps'] ?? NULL;
$installed_by       = $_POST['installed_by'] ?? NULL;

$vlan               = $_POST['vlan'] ?? NULL;
$subscriber_type    = $_POST['subscriber_type'] ?? NULL;
$install_date       = $_POST['install_date'] ?? NULL;

// FIXED: Branch Link
$branch_link        = $_POST['branch_link'] ?? NULL;

$station_id         = intval($_POST['station_id'] ?? 0);

// ----- Validate Station ID -----
$checkStation = $conn->query("SELECT id FROM stations WHERE id = $station_id");
if ($checkStation->num_rows == 0) {
    die("Invalid station selected!");
}

// ----- Prepare SQL Update Query -----
$stmt = $conn->prepare("UPDATE customers SET
    customer_name = ?, 
    company_name = ?, 
    cnic_no = ?, 
    email = ?, 
    mobile_no = ?, 
    ip = ?, 
    address = ?, 
    connection_type = ?, 
    status = ?, 
    bandwidth_mbps = ?, 
    installed_by = ?, 
    vlan = ?, 
    subscriber_type = ?, 
    install_date = ?, 
    branch_link = ?, 
    station_id = ?
    WHERE id = ?");

// ----- Bind Parameters -----
$stmt->bind_param(
    "sssssssssssssssii",
    $customer_name, 
    $company_name, 
    $cnic_no, 
    $email, 
    $mobile_no, 
    $ip,
    $address, 
    $connection_type, 
    $status, 
    $bandwidth_mbps, 
    $installed_by,
    $vlan, 
    $subscriber_type, 
    $install_date, 
    $branch_link,
    $station_id, 
    $customer_id
);

// ----- Execute -----
if ($stmt->execute()) {
    header("Location: /gerrys_project/customers/customer_info.php?id=" . $customer_id);
    exit();
} else {
    echo "Error: " . $stmt->error;
}
?>
