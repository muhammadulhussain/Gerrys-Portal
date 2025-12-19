<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_role(['Admin']); // ONLY ADMIN CAN UPDATE

require_once($_SERVER['DOCUMENT_ROOT'] . "/gerrys_project/includes/db.php");

/* ---------------- BASIC VALIDATION ---------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid Request");
}

$customer_id = (int)($_POST['customer_id'] ?? 0);
if ($customer_id <= 0) {
    die("Invalid Customer ID");
}

/* ---------------- COLLECT DATA ---------------- */
$customer_name   = trim($_POST['customer_name'] ?? '');
$company_name    = trim($_POST['company_name'] ?? '');
$cnic_no         = trim($_POST['cnic_no'] ?? '');
$status          = trim($_POST['status'] ?? '');
$location        = trim($_POST['location'] ?? '');
$address         = trim($_POST['address'] ?? '');
$connection_type = trim($_POST['connection_type'] ?? '');
$bandwidth       = trim($_POST['bandwidth_mbps'] ?? '');
$installed_by    = trim($_POST['installed_by'] ?? '');
$subscriber_type = trim($_POST['subscriber_type'] ?? '');
$station_id      = (int)($_POST['station_id'] ?? 0);
$install_date    = $_POST['install_date'] ?? null;
$remarks         = trim($_POST['remarks'] ?? '');
$ip              = trim($_POST['ip_address'] ?? '');
$vlan            = trim($_POST['vlan'] ?? '');

/* emails */
$emails = array_filter(array_map('trim', $_POST['emails'] ?? []));
$email_str = implode(',', $emails);

/* mobiles */
$mobiles = array_filter(array_map('trim', $_POST['mobile_numbers'] ?? []));
$mobile_str = implode(',', $mobiles);

/* vendors */
$vendors = [];
if (!empty($_POST['vendor_id']) && is_array($_POST['vendor_id'])) {
    $vendors = $_POST['vendor_id'];
} elseif (!empty($_POST['vendor_hidden'])) {
    $vendors = [$_POST['vendor_hidden']];
}

/* pops */
$pops = [];
if (!empty($_POST['pop_id']) && is_array($_POST['pop_id'])) {
    $pops = $_POST['pop_id'];
}

/* ---------------- TRANSACTION START ---------------- */
$conn->begin_transaction();

try {

    /* -------- UPDATE MAIN CUSTOMER TABLE -------- */
    $stmt = $conn->prepare("
        UPDATE customers SET
            customer_name   = ?,
            company_name    = ?,
            cnic_no         = ?,
            status          = ?,
            email           = ?,
            mobile_no       = ?,
            location        = ?,
            address         = ?,
            connection_type = ?,
            bandwidth_mbps  = ?,
            installed_by    = ?,
            subscriber_type = ?,
            station_id      = ?,
            install_date    = ?,
            remarks         = ?,
            ip              = ?,
            vlan            = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssssssssssissssi",
        $customer_name,
        $company_name,
        $cnic_no,
        $status,
        $email_str,
        $mobile_str,
        $location,
        $address,
        $connection_type,
        $bandwidth,
        $installed_by,
        $subscriber_type,
        $station_id,
        $install_date,
        $remarks,
        $ip,
        $vlan,
        $customer_id
    );

    $stmt->execute();
    $stmt->close();

    /* -------- DELETE OLD VENDORS -------- */
    $conn->query("DELETE FROM customer_vendors WHERE customer_id=$customer_id");

    /* -------- INSERT NEW VENDORS -------- */
    if (!empty($vendors)) {
        $vs = $conn->prepare("
            INSERT INTO customer_vendors (customer_id, vendor_id)
            VALUES (?,?)
        ");
        foreach ($vendors as $vid) {
            $vid = (int)$vid;
            if ($vid > 0) {
                $vs->bind_param("ii", $customer_id, $vid);
                $vs->execute();
            }
        }
        $vs->close();
    }

    /* -------- DELETE OLD POPs -------- */
    $conn->query("DELETE FROM customer_pops WHERE customer_id=$customer_id");

    /* -------- INSERT NEW POPs -------- */
    if (!empty($pops)) {
        $ps = $conn->prepare("
            INSERT INTO customer_pops (customer_id, pop_id)
            VALUES (?,?)
        ");
        foreach ($pops as $pid) {
            $pid = (int)$pid;
            if ($pid > 0) {
                $ps->bind_param("ii", $customer_id, $pid);
                $ps->execute();
            }
        }
        $ps->close();
    }

    /* -------- COMMIT -------- */
    $conn->commit();

    header("Location: ../customer_info.php?updated=1");
    exit;

} catch (Exception $e) {

    /* -------- ROLLBACK -------- */
    $conn->rollback();
    die("Update Failed: " . $e->getMessage());
}
