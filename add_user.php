<?php
// Start Session (Optional for future login use)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include Database Connection
require_once __DIR__ . '/includes/db.php';  // Path सही रखें

// Initialize variables
$success = "";
$error = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $password   = trim($_POST['password']);
    $role_id    = intval($_POST['role_id']);
    $station_id = !empty($_POST['station_id']) ? intval($_POST['station_id']) : NULL;

    // Basic Validation
    if (empty($username) || empty($email) || empty($password) || empty($role_id)) {
        $error = "⚠️ Please fill all required fields.";
    } else {
        // Password Hashing
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert Query
        $sql = "INSERT INTO users (username, email, password_hash, role_id, station_id, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sssii", $username, $email, $password_hash, $role_id, $station_id);

            if ($stmt->execute()) {
                $success = "✅ User added successfully!";
            } else {
                $error = "❌ Database Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "❌ Query Prepare Failed!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
</head>
<body>
    <h2>Add New User</h2>

    <!-- Show Success or Error Message -->
    <?php if ($success) echo "<p style='color: green;'>$success</p>"; ?>
    <?php if ($error) echo "<p style='color: red;'>$error</p>"; ?>

    <form method="POST" action="">
        <label>Username:</label>
        <input type="text" name="username" required><br><br>

        <label>Email:</label>
        <input type="email" name="email" required><br><br>

        <label>Password:</label>
        <input type="password" name="password" required><br><br>

        <label>Role ID:</label>
        <input type="number" name="role_id" required><br><br>

        <label>Station ID (Optional):</label>
        <input type="number" name="station_id"><br><br>

        <button type="submit">Add User</button>
    </form>
</body>
</html>
