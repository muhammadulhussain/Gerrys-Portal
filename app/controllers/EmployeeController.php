<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once BASE_PATH . '/includes/db.php';

class EmployeeController {

    public static function dashboard() {
        require_role(['employee']);

        $user_id = $_SESSION['user_id'];
        $station_id = $_SESSION['station_id'];

        $stmt = $GLOBALS['conn']->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $profile_image = $user['profile_image'] ?? 'default.png';
        $username = htmlspecialchars($user['username']);
        $email = htmlspecialchars($user['email']);
        $role = htmlspecialchars($_SESSION['role'] ?? '');

        $conn = $GLOBALS['conn'];

        // Only station-specific customers
        $totalCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE station_id=$station_id")->fetch_assoc()['total'] ?? 0);
        $activeCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE station_id=$station_id AND status='Active'")->fetch_assoc()['total'] ?? 0);
        $fiberCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE station_id=$station_id AND connection_type='Fiber'")->fetch_assoc()['total'] ?? 0);
        $rfCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE station_id=$station_id AND connection_type='Radio Frequency'")->fetch_assoc()['total'] ?? 0);
        $ethernetCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE station_id=$station_id AND connection_type='Ethernet'")->fetch_assoc()['total'] ?? 0);
        $fiber_rfCustomers = (int)($conn->query("SELECT COUNT(*) AS total FROM customers WHERE station_id=$station_id AND connection_type='FIber & Radio Frequency'")->fetch_assoc()['total'] ?? 0);

        $customerStatus = ['Active'=>0,'Suspended'=>0,'Temp Off'=>0,'Terminated'=>0];
        $statusQuery = $conn->query("SELECT status, COUNT(*) AS total FROM customers WHERE station_id=$station_id GROUP BY status");
        while ($row = $statusQuery->fetch_assoc()) {
            if(isset($customerStatus[$row['status']])) $customerStatus[$row['status']] = (int)$row['total'];
        }

        // Monthly customers
        $currentYear = date('Y');
        $monthlyCustomers = array_fill(1,12,0);
        $query = "SELECT MONTH(install_date) AS month, COUNT(*) AS total FROM customers WHERE station_id=$station_id AND install_date IS NOT NULL AND YEAR(install_date)=? GROUP BY MONTH(install_date)";
        if($stmt = $conn->prepare($query)){
            $stmt->bind_param("i",$currentYear);
            $stmt->execute();
            $res = $stmt->get_result();
            while($row=$res->fetch_assoc()) $monthlyCustomers[(int)$row['month']] = (int)$row['total'];
            $stmt->close();
        }

        // Bandwidth for station
        $current_month = date('m');
        $current_year = date('Y');
        $bw_query = $conn->prepare("
            SELECT COALESCE(SUM(current_bandwidth),0) AS total_upstream,
                   COALESCE(SUM(used_bandwidth),0) AS total_used
            FROM bandwidth_reports
            WHERE station_id=? AND MONTH(report_date)=? AND YEAR(report_date)=?
        ");
        $bw_query->bind_param("iii",$station_id,$current_month,$current_year);
        $bw_query->execute();
        $bw_result = $bw_query->get_result()->fetch_assoc();
        $total_upstream = $bw_result['total_upstream'];
        $total_used = $bw_result['total_used'];

        $panelTitle = "Employee Dashboard Overview";

        require BASE_PATH . '/app/views/employee/dashboard.php';
    }

}
