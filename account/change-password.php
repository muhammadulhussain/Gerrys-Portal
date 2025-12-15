<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);


// ✅ Initialize variables to prevent undefined warnings
$error = "";
$success = "";

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

// ✅ Fetch profile image dynamically
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userRow = $result->fetch_assoc();
$stmt->close();

$profile_img = (!empty($userRow['profile_image'])) 
                ? '../uploads/profile_images/' . $userRow['profile_image'] 
                : '../uploads/profile_images/default.png';

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if ($newPassword !== $confirmPassword) {
        $error = "New Password and Confirm Password do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($currentPassword, $user['password_hash'])) {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newHash, $userId);
            if ($updateStmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Error updating password.";
            }
            $updateStmt->close();
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - Gerry's</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../account/account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Gerry's Logo">
        <a href="../<?php echo ($_SESSION['role'] === 'Admin') ? 'admin/admin_dashboard.php' : 'employee/employee_dashboard_.php'; ?>" class="btn-custom">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>

    <!-- Change Password Card -->
    <div class="container-box form-card">
        <h3>Change Password</h3>

        <!-- Profile Info -->
        <div class="text-center mb-4">
            <img src="<?= htmlspecialchars($profile_img) ?>" alt="Profile Photo" class="profile-img mb-3"><br>
            <strong><?= htmlspecialchars($username) ?></strong><br>
            <small><?= htmlspecialchars($email) ?></small>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" id="successMsg"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Password Form -->
        <form method="POST" class="profile-info">
            <div class="row password-wrapper mb-3">
                <label for="current_password">Current Password</label>
                <input type="password" name="current_password" id="current_password" class="input-style" required>
                <span class="toggle-eye" onclick="togglePassword('current_password', 'eyeCurrent')">
                    <i id="eyeCurrent" class="fa-solid fa-eye"></i>
                </span>
            </div>

            <div class="row password-wrapper mb-3">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password" class="input-style" required onkeyup="checkStrength(this.value)">
                <span class="toggle-eye" onclick="togglePassword('new_password', 'eyeNew')">
                    <i id="eyeNew" class="fa-solid fa-eye"></i>
                </span>
                <div id="strengthMsg" class="strength"></div>
            </div>

            <div class="row password-wrapper mb-3">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="input-style" required>
                <span class="toggle-eye" onclick="togglePassword('confirm_password', 'eyeConfirm')">
                    <i id="eyeConfirm" class="fa-solid fa-eye"></i>
                </span>
            </div>

            <button type="submit" class="btn-custom w-100 mt-2">
                <i class="fa-solid fa-key"></i> Change Password
            </button>
        </form>
    </div>

<script>
function togglePassword(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById(eyeId);
    if (input.type === "password") {
        input.type = "text";
        eye.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        eye.classList.replace("fa-eye-slash", "fa-eye");
    }
}

function checkStrength(password) {
    let strengthMsg = document.getElementById("strengthMsg");
    let strength = "Weak", className = "weak";

    if (password.length > 7 && /[A-Z]/.test(password) && /\d/.test(password)) {
        strength = "Strong"; className = "strong";
    } else if (password.length > 5) {
        strength = "Medium"; className = "medium";
    }

    strengthMsg.textContent = "Strength: " + strength;
    strengthMsg.className = "strength " + className;
}

// Fade out success message
setTimeout(() => {
    const msg = document.getElementById("successMsg");
    if (msg) {
        msg.style.opacity = "0";
        msg.style.transition = "opacity 1s";
    }
}, 3000);
</script>
</body>
</html>
