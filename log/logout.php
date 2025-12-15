<?php
require_once __DIR__ . '/../includes/session_check.php';

// ✅ Clean and destroy session
session_unset();
session_destroy();

// ✅ Optional: remove cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ✅ Redirect to login
header("Location: /gerrys_project/log/login.php");
exit();
?>
