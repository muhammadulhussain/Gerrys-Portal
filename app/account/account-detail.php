<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u.*, r.role_name 
                        FROM users u 
                        LEFT JOIN roles r ON u.role_id = r.id 
                        WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ✅ Profile Image
$profile_img = (!empty($user['profile_image'])) 
                ? '../uploads/profile_images/' . $user['profile_image'] 
                : '../uploads/profile_images/default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details - Gerry's</title>
    <link rel="stylesheet" href="/GERRYS_PROJECT/public/assets/css/account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Gerry's Logo">
    <a href="../<?php echo ($_SESSION['role'] === 'Admin') ? 'admin/admin_dashboard.php' : 'employee/employee_dashboard_.php'; ?>" class="btn-custom">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<!-- Profile Container -->
<div class="container-box text-center">
    <!-- ✅ Profile Header -->
    <div class="profile-header mb-4">
        <img src="<?= htmlspecialchars($profile_img) ?>" alt="Profile Photo" class="profile-img mb-3" style="width:140px; height:140px; object-fit:cover; border-radius:50%; border:3px solid var(--primary-color);">
        <h2><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h2>
        <p><?= htmlspecialchars($user['role_name'] ?? 'N/A') ?></p>
    </div>

    <!-- Profile Info -->
    <div class="profile-info text-start">
        <div class="row mb-2"><label>Full Name:</label><span><?= htmlspecialchars($user['full_name'] ?: '-') ?></span></div>
        <div class="row mb-2"><label>Username:</label><span><?= htmlspecialchars($user['username']) ?></span></div>
        <div class="row mb-2"><label>Email:</label><span><?= htmlspecialchars($user['email']) ?></span></div>
        <div class="row mb-2"><label>Role:</label><span><?= htmlspecialchars($user['role_name'] ?? 'N/A') ?></span></div>
        <div class="row mb-2"><label>Station ID:</label><span><?= htmlspecialchars($user['station_id'] ?? '-') ?></span></div>
        <div class="row mb-2"><label>Status:</label><span><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span></div>
        <div class="row mb-2"><label>Created At:</label><span><?= $user['created_at'] ?></span></div>
        <div class="row mb-2"><label>Updated At:</label><span><?= $user['updated_at'] ?? '-' ?></span></div>
    </div>

    <!-- Edit Button -->
    <div class="mt-4">
        <button class="btn-custom" onclick="window.location.href='edit_account.php'">
            <i class="fa-solid fa-pen-to-square"></i> Edit Account Details
        </button>
    </div>
</div>

</body>
</html>
