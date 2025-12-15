<?php
require_once(__DIR__ . '/../includes/db.php');
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'Employee';

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role_id = ($_POST['role_id'] ?? 2);

// ✅ Station should not be editable
$stmt = $conn->prepare("SELECT station_id, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($station_id, $existingProfile);
$stmt->fetch();
$stmt->close();

// ✅ Email validation (@gerrys.net only)
if (!preg_match("/^[a-zA-Z0-9._%+-]+@gerrys\.net$/", $email)) {
    die("<script>alert('Only @gerrys.net email is allowed!'); window.history.back();</script>");
}

// ✅ File upload handling
$profileImageName = $existingProfile; // default: existing
$uploadDir = __DIR__ . "/../uploads/profile_images/";

if (!empty($_FILES['profile_image']['name'])) {
    $fileName = basename($_FILES["profile_image"]["name"]);
    $fileTmp = $_FILES["profile_image"]["tmp_name"];
    $fileSize = $_FILES["profile_image"]["size"];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExt = ['jpg', 'jpeg', 'png'];

    if (!in_array($fileExt, $allowedExt)) {
        die("<script>alert('Only JPG, JPEG, PNG files allowed!'); window.history.back();</script>");
    }

    if ($fileSize > 2 * 1024 * 1024) { // 2MB limit
        die("<script>alert('Image size must be less than 2MB!'); window.history.back();</script>");
    }

    // ✅ Unique file name
    $profileImageName = time() . '_' . uniqid() . '.' . $fileExt;

    if (!move_uploaded_file($fileTmp, $uploadDir . $profileImageName)) {
        die("<script>alert('Failed to upload image!'); window.history.back();</script>");
    }
}

// ✅ Update Database
$sql = "UPDATE users SET full_name = ?, email = ?, role_id = ?, profile_image = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssisi", $full_name, $email, $role_id, $profileImageName, $user_id);

if ($stmt->execute()) {
    echo "<script>alert('Account Updated Successfully!'); window.location.href = '../account/account-detail.php';</script>";
} else {
    echo "<script>alert('Error updating account!'); window.history.back();</script>";
}
