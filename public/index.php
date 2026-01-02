<?php
// Start session
require_once __DIR__ . '/../app/includes/session_start.php';

// Default page
$page = $_GET['page'] ?? 'login';

// If user already logged in, redirect by role
if (isset($_SESSION['user_role'])) {

    if ($page === 'login') {
        if ($_SESSION['user_role'] === 'admin') {
            header("Location: index.php?page=admin-dashboard");
            exit;
        } elseif ($_SESSION['user_role'] === 'employee') {
            header("Location: index.php?page=employee-dashboard");
            exit;
        }
    }
}

// Routing
switch ($page) {

    /* =====================
       AUTH ROUTES
    ====================== */
    case 'login':
        require_once __DIR__ . '/../app/log/login.php';
        break;

    case 'logout':
        require_once __DIR__ . '/../app/log/logout.php';
        break;

    /* =====================
       ADMIN ROUTES
    ====================== */
    case 'admin-dashboard':
        require_once __DIR__ . '/../app/includes/session_check.php';
        require_once __DIR__ . '/../app/admin/admin_dashboard.php';
        break;

    /* =====================
       EMPLOYEE ROUTES
    ====================== */
    case 'employee-dashboard':
    require_once __DIR__ . '/../app/includes/session_check.php';
    require_once __DIR__ . '/../app/employee/employee_dashboard_.php';
    break;

    case 'customer-info':
        require_once __DIR__ . '/../app/customers/customer_info.php';
        break;

    case 'bandwidth-report':
        require_once __DIR__ . '/../app/bandwidth/bandwidth_report.php';
        break;

    case 'cdn-report':
        require_once __DIR__ . '/../app/cdn/cdn_report.php';
        break;

    case 'account-detail':
        require_once __DIR__ . '/../app/account/account-detail.php';
        break;

    case 'change-password':
        require_once __DIR__ . '/../app/account/change-password.php';
        break;


    /* =====================
       DEFAULT (404)
    ====================== */
    default:
        http_response_code(404);
        echo "<h2>404 - Page Not Found</h2>";
        break;
}
