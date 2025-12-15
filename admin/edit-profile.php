<?php
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);
require_once __DIR__ . '/../db.php'; // apna DB connection include karna

// Assume login ke time user ka id session me save hota hai
if (!isset($_SESSION['user_id'])) {
    header("Location: /project1/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// User details fetch
$sql = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Update request handle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);

    if (!empty($new_username) && !empty($new_email)) {
        $update_sql = "UPDATE users SET username=?, email=? WHERE id=?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $new_username, $new_email, $user_id);

        if ($update_stmt->execute()) {
            $message = "<div class='alert alert-success'>Account details updated successfully!</div>";
            // refresh data
            $user['username'] = $new_username;
            $user['email'] = $new_email;
        } else {
            $message = "<div class='alert alert-danger'>Error updating account.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Username and Email cannot be empty.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-dark text-white text-center">
          <h4>Account Details</h4>
        </div>
        <div class="card-body">
          <?php echo $message; ?>
          <form method="POST" action="">
            <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input type="text" id="username" name="username" class="form-control" 
                     value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-control" 
                     value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Update Account</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
