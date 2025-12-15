<?php
// login.php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';

$error = "";

// If user already logged in, redirect
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    if (strcasecmp($_SESSION['role'], 'Admin') === 0) {
        header("Location: /gerrys_project/admin/admin_dashboard.php");
        exit();
    } else {
        header("Location: /gerrys_project/employee/employee_dashboard_.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role_in  = trim($_POST['role'] ?? '');

    if ($username === '' || $email === '' || $password === '' || $role_in === '') {
        $error = "Please fill all fields.";
    } elseif (!preg_match("/@gerrys\.net$/i", $email)) {
        $error = "Only @gerrys.net domain is allowed.";
    } else {
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
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (!empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                    if (strcasecmp($user['role'], $role_in) !== 0) {
                        $error = "Role mismatch. Please select the correct role.";
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['user_id']    = $user['id'];
                        $_SESSION['username']   = $user['username'];
                        $_SESSION['email']      = $user['email'];
                        $_SESSION['role']       = $user['role'];
                        $_SESSION['station']    = $user['station'];
                        $_SESSION['station_id'] = $user['station_id'] ?? null;

                        if (strcasecmp($user['role'], 'Admin') === 0) {
                            header("Location: /gerrys_project/admin/admin_dashboard.php");
                            exit();
                        } else {
                            header("Location: /gerrys_project/employee/employee_dashboard_.php");
                            exit();
                        }
                    }
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "Account not found.";
            }
            $stmt->close();
        } else {
            $error = "Database query error: " . $conn->error;
        }
    }
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Private Dashboard Login - Gerrys</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    /* === Background === */
    body {
      background: linear-gradient(135deg, #8ebee6ff, #c7d8f8ff);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
      animation: fadeBg 6s ease-in-out infinite alternate;
    }

    @keyframes fadeBg {
      0% { background: linear-gradient(135deg, #8ebee6ff, #c7d8f8ff); }
      100% { background: linear-gradient(135deg, #91c9e9, #bcd0f6); }
    }

    /* === Login Card === */
    .login-card {
      background-color: #fff;
      color: #000;
      border-radius: 15px;
      padding: 35px 45px;
      max-width: 450px;
      width: 100%;
      box-shadow: 0 5px 25px rgba(0,0,0,0.2);
      animation: slideIn 1s ease forwards;
      opacity: 0;
      transform: translateY(-40px);
    }

    @keyframes slideIn {
      to { opacity: 1; transform: translateY(0); }
    }

    /* === Logo Animation === */
    .logo img {
      height: 150px;
      display: block;
      margin: 0 auto 15px;
      animation: pulseLogo 3s ease-in-out infinite;
    }

    @keyframes pulseLogo {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.07); }
    }

    .logo h4 {
      font-weight: 700;
      color: #1b4781ff;
    }

    /* === Inputs === */
    input.form-control, select.form-select {
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    input.form-control:focus, select.form-select:focus {
      border-color: #2173c5ff;
      box-shadow: 0 0 10px rgba(33, 115, 197, 0.3);
    }

    /* === Password toggle === */
    .password-wrapper {
      position: relative;
    }

    .password-wrapper .toggle-eye {
      position: absolute;
      right: 12px;
      top: 70%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #777;
      font-size: 1rem;
      transition: color 0.3s;
    }

    .password-wrapper .toggle-eye:hover {
      color: #000;
    }

    /* === Button === */
    .btn-primary {
      background-color: #2173c5ff;
      border: none;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #1b4781ff;
      transform: translateY(-3px);
      box-shadow: 0 4px 10px rgba(33,115,197,0.3);
    }

    /* === Alert animation === */
    .alert {
      animation: fadeAlert 0.5s ease-in-out;
    }

    @keyframes fadeAlert {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    /* === Station dropdown animation === */
    .fade-slide {
      opacity: 0;
      transform: translateY(15px);
      animation: fadeSlide 0.6s ease forwards;
    }

    @keyframes fadeSlide {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body>

<div class="login-card">
  <div class="text-center logo">
    <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Gerrys Logo">
    <h4 class="fw-bold mt-2">Private Dashboard Login</h4>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger text-center mt-3"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="mt-4">
    <!-- Username -->
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" placeholder="Enter username" required>
    </div>

    <!-- Email -->
    <div class="mb-3">
      <label class="form-label">Email (only @gerrys.net)</label>
      <input type="email" name="email" class="form-control" placeholder="Your Email" required>
    </div>

    <!-- Password -->
    <div class="mb-3 password-wrapper">
      <label class="form-label">Password</label>
      <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
      <span class="toggle-eye" onclick="togglePassword()">
        <i id="eyeIcon" class="fa-solid fa-eye"></i>
      </span>
    </div>

    <!-- Role -->
    <div class="mb-3">
      <label class="form-label">Role</label>
      <select class="form-select" name="role" id="roleSelect" required onchange="toggleStationDropdown()">
        <option value="">Select Role</option>
        <option value="Admin">Admin</option>
        <option value="Employee">Employee</option>
      </select>
    </div>

    <!-- Station -->
    <div class="mb-3 d-none" id="stationContainer">
      <label class="form-label">Select Station</label>
      <select class="form-select" name="station">
        <option value="Karachi">Karachi</option>
        <option value="Quetta">Quetta</option>
        <option value="Peshawar">Peshawar</option>
        <option value="Hyderabad">Hyderabad</option>
        <option value="Faisalabad">Faisalabad</option>
        <option value="Islamabad">Islamabad</option>
        <option value="Lahore">Lahore</option>
        <option value="Jhelum">Jhelum</option>
      </select>
    </div>

    <!-- Login Button -->
    <div class="d-grid mt-4">
      <button type="submit" class="btn btn-primary py-2">
        <i class="fa-solid fa-right-to-bracket me-2"></i>Login
      </button>
    </div>
  </form>
</div>

<script>
  // Password toggle
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

  // Animated station dropdown
  function toggleStationDropdown() {
    const role = document.getElementById("roleSelect").value;
    const station = document.getElementById("stationContainer");

    if (role === "Employee") {
      station.classList.remove("d-none");
      station.classList.remove("fade-slide"); // reset animation
      void station.offsetWidth; // trigger reflow for reanimation
      station.classList.add("fade-slide");
    } else {
      station.classList.add("d-none");
    }
  }
</script>

</body>
</html>
