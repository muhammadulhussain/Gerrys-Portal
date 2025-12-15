<?php
// ===============================
// create_account.php
// Only Admin can access this page
// ===============================

session_start();
require_once __DIR__ . '/../includes/db.php';

// ✅ Access Control
if (!isset($_SESSION['username']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    header("Location: ../login.php");
    exit();
}

$error = "";
$success = "";

// ✅ Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role_id = (int)$_POST['role_id'];
    $station_id = !empty($_POST['station_id']) ? (int)$_POST['station_id'] : NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // ✅ Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // ✅ Check username or email already exists
        $checkQuery = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkQuery->bind_param("ss", $username, $email);
        $checkQuery->execute();
        $checkQuery->store_result();

        if ($checkQuery->num_rows > 0) {
            $error = "Username or Email already exists!";
        } else {
            // ✅ Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // ✅ Handle profile image upload
            $profile_image = NULL;
            if (!empty($_FILES['profile_image']['name'])) {
                $target_dir = __DIR__ . "/../uploads/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

                $file_name = time() . "_" . basename($_FILES['profile_image']['name']);
                $target_file = $target_dir . $file_name;
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($imageFileType, $allowed_types)) {
                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                        $profile_image = "uploads/" . $file_name;
                    }
                }
            }

            // ✅ Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password_hash, role_id, station_id, is_active, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiiis", $username, $full_name, $email, $password_hash, $role_id, $station_id, $is_active, $profile_image);

            if ($stmt->execute()) {
                $success = "User account created successfully!";
            } else {
                $error = "Error creating account. Please try again.";
            }
        }
        $checkQuery->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account | Admin Panel</title>
    <link rel="stylesheet" href="account.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Logo">
        <a href="../<?php echo ($_SESSION['role'] === 'Admin') ? 'admin/admin_dashboard.php' : 'employee/employee_dashboard_.php'; ?>" class="btn-custom">
            
        <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>

    <!-- Form Card -->
    <div class="form-card">
        <h3>Create New Account</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <label class="form-label">Username *</label>
            <input type="text" name="username" class="input-style" required>

            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="input-style">

            <label class="form-label">Email *</label>
            <input type="email" name="email" class="input-style" required>

            <div class="password-wrapper">
                <label class="form-label">Password *</label>
                <input type="password" id="password" name="password" class="input-style" required>
                <i class="fas fa-eye toggle-eye" onclick="togglePassword('password')"></i>
            </div>

            <div class="password-wrapper">
                <label class="form-label">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="input-style" required>
                <i class="fas fa-eye toggle-eye" onclick="togglePassword('confirm_password')"></i>
            </div>

            <label class="form-label">Role ID *</label>
            <input type="number" name="role_id" class="input-style" required>

            <label class="form-label">Station ID</label>
            <input type="number" name="station_id" class="input-style">

            <label class="form-label">Profile Image</label><br>
            <img id="preview" class="profile-img-preview" src="../assets/default-avatar.png" alt="Preview">
            <input type="file" name="profile_image" accept="image/*" class="input-style" onchange="previewImage(event)">

            <label style="display:flex;align-items:center;gap:8px;margin-top:10px;">
                <input type="checkbox" name="is_active" checked> Active User
            </label>

            <br>
            <button type="submit" class="btn-custom">Create Account</button>
        </form>
    </div>

    <script>
        // ✅ Toggle Password Visibility
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === "password" ? "text" : "password";
        }

        // ✅ Preview Uploaded Image
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                document.getElementById('preview').src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

</body>
</html>
