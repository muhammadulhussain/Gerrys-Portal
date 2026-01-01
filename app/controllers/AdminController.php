<?php
// admin/adminController.php
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Admin']);
require_once __DIR__ . '/../includes/db.php';

// Fetch Admin User Details
$user_id = $_SESSION['user_id'] ?? 0;
$user = [];
if($user_id){
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
}

$profile_image = $user['profile_image'] ?? 'default.png';
$username = htmlspecialchars($user['username'] ?? '');
$email    = htmlspecialchars($user['email'] ?? '');
$role     = htmlspecialchars($_SESSION['role'] ?? '');
$panelTitle = "Admin Dashboard Overview";

// Dashboard Stats
$totalCustomers    = (int)($conn->query("SELECT COUNT(*) AS total FROM customers")->fetch_assoc()['total'] ?? 0);
$activeCustomers   = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE status='Active'")->fetch_assoc()['total'] ?? 0);
$fiberCustomers    = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE connection_type='Fiber'")->fetch_assoc()['total'] ?? 0);
$rfCustomers       = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE connection_type='Radio Frequency'")->fetch_assoc()['total'] ?? 0);
$ethernetCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE connection_type='Ethernet'")->fetch_assoc()['total'] ?? 0);
$fiber_rfCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE connection_type='Fiber & Radio Frequency'")->fetch_assoc()['total'] ?? 0);

// Monthly Customers
$currentYear = date('Y');
$monthlyCustomers = array_fill(1,12,0);
if($stmt = $conn->prepare("SELECT MONTH(install_date) AS month, COUNT(*) AS total FROM customers WHERE install_date IS NOT NULL AND YEAR(install_date)=? GROUP BY MONTH(install_date)")){
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $monthlyCustomers[(int)$row['month']] = (int)$row['total'];
    }
    $stmt->close();
}

// Region-wise Customers
function getCustomerCountByRegion($conn, $regionName){
    $stmt = $conn->prepare("
        SELECT st.name AS station_name, COUNT(c.id) AS total_customers
        FROM stations st
        LEFT JOIN customers c ON st.id = c.station_id
        LEFT JOIN regions r ON st.region_id = r.id
        WHERE r.name = ?
        GROUP BY st.name
        ORDER BY total_customers DESC
    ");
    $stmt->bind_param("s", $regionName);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    return $res;
}

$south   = getCustomerCountByRegion($conn,'South');
$central = getCustomerCountByRegion($conn,'Central');
$north   = getCustomerCountByRegion($conn,'North');

// Bandwidth totals
$total_upstream = 0; $total_used = 0;
if($stmt = $conn->prepare("SELECT COALESCE(SUM(current_bandwidth),0) AS total_up, COALESCE(SUM(used_bandwidth),0) AS total_used FROM bandwidth_reports")){
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        $total_upstream = (float)$row['total_up'];
        $total_used     = (float)$row['total_used'];
    }
    $stmt->close();
}

// Load view
include __DIR__ . '/admin_dashboard.php';
