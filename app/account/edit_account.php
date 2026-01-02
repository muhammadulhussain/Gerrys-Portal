<?php
require_once(__DIR__ . '/../includes/db.php');
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);

// ✅ Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'Employee';

// ✅ Fetch user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ✅ Fetch station list only for Admins
if ($role === 'Admin') {
    $stations = $conn->query("SELECT * FROM stations ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="account.css">
</head>

<body>

    <!-- Navbar -->
    <div class="navbar">
        <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Gerry's Logo">
        <a href="../<?php echo ($_SESSION['role'] === 'Admin') ? 'admin/admin_dashboard.php' : 'employee/employee_dashboard_.php'; ?>" class="btn-custom">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
    <div class="container py-4">
        <div class="card shadow-lg p-4 form-card">

                <!-- ✅ Title -->
                <h3 class="text-center fw-bold mb-4">Edit Account</h3>

<form method="POST" action="update_account.php" enctype="multipart/form-data">

    <!-- Profile Image -->
    <div class="mb-3 text-center">
        <label class="form-label d-block">Profile Image</label>
        <img src="<?= !empty($user['profile_image']) ? '../uploads/profile_images/' . $user['profile_image'] : '../uploads/profile_images/default.png'; ?>" 
            alt="Profile" class="rounded-circle mb-3" width="120" height="120" style="object-fit: cover; border: 3px solid #ddd;">
        <input type="file" name="profile_image" class="form-control input-style" accept="image/png, image/jpeg">
        <small class="text-muted">Only JPG, JPEG, PNG (Max 2MB)</small>
    </div>

    <!-- Full Name -->
    <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" class="form-control input-style" required>
    </div>

    <!-- Username (Read-only) -->
    <div class="mb-3">
        <label class="form-label">Username (Not Editable)</label>
        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" class="form-control input-style" readonly>
    </div>

    <!-- Email -->
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control input-style" required
               pattern="^[a-zA-Z0-9._%+-]+@gerrys\.net$" title="Only @gerrys.net email allowed">
    </div>

    <!-- Admin Only Fields: Role + Station (Station Disabled) -->
    <?php if ($role === 'Admin'): ?>
    <div class="mb-4">
        <label class="form-label">Role</label>
        <select name="role_id" class="form-select input-style">
            <option value="1" <?= ($user['role_id'] == 1) ? 'selected' : '' ?>>Admin</option>
            <option value="2" <?= ($user['role_id'] == 2) ? 'selected' : '' ?>>Employee</option>
        </select>
    </div>

    <div class="mb-4">
        <label class="form-label">Station (Not Editable)</label>
        <select name="station_id" class="form-select input-style" disabled>
            <?php foreach ($stations as $st): ?>
            <option value="<?= $st['id'] ?>" <?= ($user['station_id'] == $st['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($st['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <!-- Save Button -->
    <button type="submit" class="btn-custom w-100">
        <i class="fa-solid fa-floppy-disk"></i> Save Changes
    </button>

</form>
        </div>
    </div>
</body>

</html>
