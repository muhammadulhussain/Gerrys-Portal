<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Access Control
if (!isset($_SESSION['username']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    header("Location: ../login.php");
    exit();
}

$error = "";
$success = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username   = trim($_POST['username']);
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    $role_id    = (int)$_POST['role_id'];
    $station_id = !empty($_POST['station_id']) ? (int)$_POST['station_id'] : NULL;

    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm) || empty($role_id)) {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {

        // Check duplicate user
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {

            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Profile Image Upload
            $profile_image = NULL;

            if (!empty($_FILES['profile_image']['name'])) {
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileName = time() . '_' . basename($_FILES['profile_image']['name']);
                $target   = $uploadDir . $fileName;
                $ext      = strtolower(pathinfo($target, PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
                        $profile_image = 'uploads/' . $fileName;
                    }
                }
            }

            // Insert User
            $stmt = $conn->prepare("
                INSERT INTO users 
                (username, full_name, email, password_hash, role_id, station_id, is_active, profile_image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssssiiis",
                $username,
                $full_name,
                $email,
                $password_hash,
                $role_id,
                $station_id,
                $is_active,
                $profile_image
            );

            if ($stmt->execute()) {
                $success = "User account created successfully.";
            } else {
                $error = "Error creating account.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

// Fetch Roles & Stations (Dropdown)
$rolesQuery = mysqli_query(
    $conn,
    "SELECT id, role_name FROM roles ORDER BY role_name"
);

$stationsQuery = mysqli_query(
    $conn,
    "SELECT id, name FROM stations ORDER BY name"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account</title>
    <link rel="stylesheet" href="account.css">
</head>
<body>
<!-- Navbar --> 
 <div class="navbar"> 
    <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Logo"> 
    <a href="../<?php echo ($_SESSION['role'] === 'Admin') ? 'admin/admin_dashboard.php' : 'employee/employee_dashboard_.php'; ?>" class="btn-custom"> 
        <i class="fa-solid fa-arrow-left"></i> Back 
    </a> 
 </div>
<div class="form-card">
    <h3>Create New Account</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Username *</label>
        <input type="text" name="username" class="input-style" required>

        <label>Full Name</label>
        <input type="text" name="full_name" class="input-style">

        <label>Email *</label>
        <input type="email" name="email" class="input-style" required>

        <label>Password *</label>
        <input type="password" name="password" class="input-style" required>

        <label>Confirm Password *</label>
        <input type="password" name="confirm_password" class="input-style" required>

        <!-- ROLE DROPDOWN -->
        <label>Role *</label>
        <select name="role_id" class="input-style" required>
            <option value="">-- Select Role --</option>
            <?php while ($r = mysqli_fetch_assoc($rolesQuery)) { ?>
                <option value="<?= $r['id'] ?>">
                    <?= htmlspecialchars($r['role_name']) ?>
                </option>
            <?php } ?>
        </select>

        <!-- STATION DROPDOWN -->
        <label>Station</label>
        <select name="station_id" class="input-style">
            <option value="">-- Select Station --</option>
            <?php while ($s = mysqli_fetch_assoc($stationsQuery)) { ?>
                <option value="<?= $s['id'] ?>">
                    <?= htmlspecialchars($s['name']) ?>
                </option>
            <?php } ?>
        </select>

        <label>Profile Image</label>
        <input type="file" name="profile_image" class="input-style">

        <label>
            <input type="checkbox" name="is_active" checked> Active User
        </label>

        <br><br>
        <button type="submit" class="btn-custom">Create Account</button>

    </form>
</div>

</body>
</html>
