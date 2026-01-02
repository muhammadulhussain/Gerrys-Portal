<?php
// includes/session_check.php
require_once __DIR__ . '/session_start.php';

// If this script is included on a public page like login.php by mistake,
// avoid redirect loop by allowing the login page itself.
$script = basename($_SERVER['PHP_SELF']);

// Define absolute (root-based) login URL for safe redirect:
$loginUrl = '/gerrys_project/app/log/login.php';

// If user not logged in -> redirect to login
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    // If current script is already login.php, do NOT redirect to avoid loop.
    if ($script === 'login.php' || $script === 'register.php') {
        // allow login page to render
        return;
    }
    header("Location: $loginUrl");
    exit();
}

// Role helper function
function require_role(array $allowed_roles = []) {
    if (empty($allowed_roles)) return; // no restriction
    $current = strtolower($_SESSION['role'] ?? '');
    $allowed = array_map('strtolower', $allowed_roles);
    if (!in_array($current, $allowed, true)) {
        header("Location: /gerrys_project/app/log/login.php");
        exit();
    }
}
?>
