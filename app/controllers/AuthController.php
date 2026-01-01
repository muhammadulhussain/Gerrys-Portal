<?php
// app/controllers/AuthController.php

require_once BASE_PATH . '/config/database.php';

class AuthController
{
    public static function login(): array
    {
        global $conn;
        $error = "";

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['error' => $error];
        }

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role_in  = trim($_POST['role'] ?? '');

        if ($username === '' || $email === '' || $password === '' || $role_in === '') {
            return ['error' => "Please fill all fields."];
        }

        if (!preg_match("/@gerrys\.net$/i", $email)) {
            return ['error' => "Only @gerrys.net domain is allowed."];
        }

        $stmt = $conn->prepare("
            SELECT 
                u.id, u.username, u.email, u.password_hash,
                r.role_name AS role,
                s.name AS station, s.id AS station_id
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN stations s ON u.station_id = s.id
            WHERE u.email = ?
        ");

        if (!$stmt) {
            return ['error' => "Database query error."];
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            return ['error' => "Account not found."];
        }

        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password_hash'])) {
            return ['error' => "Incorrect password."];
        }

        if (strcasecmp($user['role'], $role_in) !== 0) {
            return ['error' => "Role mismatch. Please select the correct role."];
        }

        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['email']      = $user['email'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['station']    = $user['station'];
        $_SESSION['station_id'] = $user['station_id'] ?? null;

        if (strcasecmp($user['role'], 'Admin') === 0) {
            header("Location: " . BASE_URL . "/public/index.php?page=admin");
        } else {
            header("Location: " . BASE_URL . "/public/index.php?page=employee");
        }
        exit;
    }

    public static function logout(): void
    {
        session_unset();
        session_destroy();

        header("Location: " . BASE_URL . "/public/index.php?page=login");
        exit;
    }
}
