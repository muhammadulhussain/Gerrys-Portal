<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);

if (!isset($_SESSION['username'])) {
    die("Unauthorized access. Please log in again.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Required fields
    $required_fields = ['customer_name', 'company_name', 'cnic_no', 'branch_link', 'connection_type', 'bandwidth', 'address', 'installation_date'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            die("Required field missing: " . htmlspecialchars($field));
        }
    }

    // Collect form values
    $customer_name   = trim($_POST['customer_name']);
    $company_name    = trim($_POST['company_name']);
    $cnic_no         = trim($_POST['cnic_no']);
    $branch_link     = trim($_POST['branch_link']);
    $ip              = trim($_POST['ip_address'] ?? '');
    $address         = trim($_POST['address']);
    $connection_type = trim($_POST['connection_type']);
    $bandwidth_mbps  = trim($_POST['bandwidth']);
    $installed_by    = trim($_POST['installed_by']);
    $install_date    = $_POST['installation_date'];
    $vlan            = trim($_POST['vlan'] ?? '');
    $subscriber_type = trim($_POST['subscriber_type'] ?? '');

    $status = $_POST['status'] ?? 'Active';
    $client_type = ($installed_by === 'Self Manage') ? 'Direct' : 'Indirect';
    $station_id = $_SESSION['station_id'] ?? 1;

    // Emails & Mobile
    $email = !empty($_POST['emails']) ? implode(',', array_filter($_POST['emails'])) : NULL;
    $mobile_no = !empty($_POST['mobile_numbers']) ? implode(',', array_filter($_POST['mobile_numbers'])) : NULL;

    // Insert main customer
    $sql = "INSERT INTO customers (
        customer_name, company_name, ip, bandwidth_mbps,
        connection_type, station_id, status, cnic_no,
        email, address, installed_by, install_date,
        branch_link, mobile_no, vlan, client_type, subscriber_type
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssissssssssssss",
        $customer_name,
        $company_name,
        $ip,
        $bandwidth_mbps,
        $connection_type,
        $station_id,
        $status,
        $cnic_no,
        $email,
        $address,
        $installed_by,
        $install_date,
        $branch_link,
        $mobile_no,
        $vlan,
        $client_type,
        $subscriber_type
    );

    if (!$stmt->execute()) {
        die("Error saving customer: " . $stmt->error);
    }

    $customer_id = $stmt->insert_id; // Last inserted customer ID
    $stmt->close();

    // ---------------------------
    // Save Multiple POPs
    // ---------------------------
    if (!empty($_POST['pop_id'])) {
        $pop_ids = $_POST['pop_id']; // array from multiple select
        $pop_stmt = $conn->prepare("INSERT INTO customer_pops (customer_id, pop_id) VALUES (?, ?)");
        foreach ($pop_ids as $pop_id) {
            $pop_stmt->bind_param("ii", $customer_id, $pop_id);
            $pop_stmt->execute();
        }
        $pop_stmt->close();
    }

    // ---------------------------
    // Save Multiple Vendors
    // ---------------------------
    if (!empty($_POST['vendor_id'])) {
        $vendor_ids = $_POST['vendor_id']; // array from multiple select
        $vendor_stmt = $conn->prepare("INSERT INTO customer_vendors (customer_id, vendor_id) VALUES (?, ?)");
        foreach ($vendor_ids as $vendor_id) {
            $vendor_stmt->bind_param("ii", $customer_id, $vendor_id);
            $vendor_stmt->execute();
        }
        $vendor_stmt->close();
    }

    echo "<script>alert('✅ Customer saved successfully!'); window.location.href='customer_form.php';</script>";
}
        