<?php
// bootstrap.php

/* =========================
   SESSION
========================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   CONSTANTS
========================= */
define('BASE_PATH', dirname(__DIR__) . '/GERRYS_PROJECT');
define('BASE_URL', '/gerrys_project');

/* =========================
   ROLE HELPER
========================= */
function require_role(array $allowed_roles = []): void
{
    if (!isset($_SESSION['username'], $_SESSION['role'])) {
        header("Location: " . BASE_URL . "/public/index.php?page=login");
        exit;
    }

    if (empty($allowed_roles)) return;

    $current = strtolower($_SESSION['role']);
    $allowed = array_map('strtolower', $allowed_roles);

    if (!in_array($current, $allowed, true)) {
        header("Location: " . BASE_URL . "/public/index.php?page=login");
        exit;
    }
}
