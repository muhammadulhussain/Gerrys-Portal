<?php
// public/index.php
require_once __DIR__ . '/../bootstrap.php';

$page = $_GET['page'] ?? 'login';

switch ($page) {

    /* =====================
       LOGIN (PUBLIC)
    ===================== */
    case 'login':
        // already logged in? → dashboard
        if (isset($_SESSION['username'], $_SESSION['role'])) {
            if (strcasecmp($_SESSION['role'], 'Admin') === 0) {
                header("Location: " . BASE_URL . "/public/index.php?page=admin");
            } else {
                header("Location: " . BASE_URL . "/public/index.php?page=employee");
            }
            exit;
        }

        require BASE_PATH . '/app/views/auth/login.php';
        break;

    case 'logout':
        require BASE_PATH . '/app/controllers/AuthController.php';
        AuthController::logout();
        break;

    /* =====================
       PROTECTED ROUTES
    ===================== */
    case 'admin':
        require_role(['admin']);
        require BASE_PATH . '/app/views/admin/dashboard.php';
        break;

    case 'employee':
        require_role(['employee']);
        require BASE_PATH . '/app/views/employee/dashboard.php';
        break;

    default:
        header("Location: " . BASE_URL . "/public/index.php?page=login");
        exit;
}
