<?php
require_once BASE_PATH . '/app/controllers/AuthController.php';

$error = "";

// Handle login
$response = AuthController::login();
$error = $response['error'] ?? '';
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Private Dashboard Login - Gerrys</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/login.css">

</head>
<body>

<div class="login-card">
  <div class="text-center logo">
    <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Gerrys Logo">
    <h4 class="fw-bold mt-2">Private Dashboard Login</h4>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger text-center mt-3">
        <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="mt-4">

    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Email (only @gerrys.net)</label>
      <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3 password-wrapper">
      <label class="form-label">Password</label>
      <input type="password" name="password" id="password" class="form-control" required>
      <span class="toggle-eye" onclick="togglePassword()">
        <i id="eyeIcon" class="fa-solid fa-eye"></i>
      </span>
    </div>

    <div class="mb-3">
      <label class="form-label">Role</label>
      <select class="form-select" name="role" id="roleSelect" required onchange="toggleStationDropdown()">
        <option value="">Select Role</option>
        <option value="Admin">Admin</option>
        <option value="Employee">Employee</option>
      </select>
    </div>

    <div class="d-grid mt-4">
      <button type="submit" class="btn btn-primary py-2">
        <i class="fa-solid fa-right-to-bracket me-2"></i>Login
      </button>
    </div>

  </form>
</div>

<script>
function togglePassword() {
  const pass = document.getElementById("password");
  const icon = document.getElementById("eyeIcon");
  if (pass.type === "password") {
    pass.type = "text";
    icon.classList.replace("fa-eye", "fa-eye-slash");
  } else {
    pass.type = "password";
    icon.classList.replace("fa-eye-slash", "fa-eye");
  }
}
</script>

</body>
</html>
