<?php
require_once(__DIR__ . '/../../includes/db.php');
require_once __DIR__ . '/../../includes/session_check.php';
require_role(['Employee', 'Admin']);

// ✅ Access Check
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ✅ Get Customer ID from URL
if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    die("<div style='text-align:center;color:red;margin-top:50px;'>❌ Invalid access — Customer ID missing.</div>");
}

$customer_id = intval($_GET['customer_id']);

// ✅ Fetch Customer Details
$stmt = $conn->prepare("SELECT id, customer_name, company_name, email, mobile_no, bandwidth_mbps, status FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    die("<div style='text-align:center;color:red;margin-top:50px;'>❌ Customer not found.</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Service Request - <?= htmlspecialchars($customer['customer_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; font-family: "Poppins", sans-serif; }
    .header-bar {
      background: linear-gradient(90deg, #fff, #ebb41e);
      padding: 15px 25px;
      display: flex; align-items: center; justify-content: space-between;
      border-radius: 0 0 12px 12px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    }
.header-bar img { height: 70px; }
.header-bar h3 { color: #000; margin: 0; font-weight: 700; }
.card { border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); padding: 30px; }
.gerrys-btn { background-color: #ebb41e; border: none; color: #000; font-weight: 600; transition: 0.3s; }
.gerrys-btn:hover { background-color: #d4a014; color: #fff; }
.hidden { display: none; }
.fade-in-up {opacity: 0; transform: translateY(20px); animation: fadeInUp 0.6s ease-out forwards;}
@keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
</style>
</head>
<body class="fade-in-up">


<div class="header-bar">
    <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Gerry’s Logo" style="height:80px">
    <h3 class="ms-3">Customer Service Request</h3>
    <a href="../customer_info.php" class="btn gerrys-btn">
        <i class="fa-solid fa-arrow-left me-1"></i> Back
    </a>
</div>
<div class="container mt-4">
<div class="card">
<h5 class="text-center mb-3">
  <strong><?= htmlspecialchars($customer['customer_name']) ?></strong> | 
  <?= htmlspecialchars($customer['company_name']) ?>
</h5>
<p class="text-center">
    Current Bandwidth: <strong><?= htmlspecialchars($customer['bandwidth_mbps']) ?> Mbps</strong> |
    Status: <strong><?= htmlspecialchars($customer['status']) ?></strong>
</p>

<form method="POST" action="save_service_request.php">
    <!-- Hidden Customer ID -->
    <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">

<div class="mb-3">
    <label>Service Action Type:</label>
    <select name="event_type" id="eventType" class="form-select" required>
        <option value="">-- Select Action --</option>
        <option value="Upgrade">Upgrade</option>
        <option value="Downgrade">Downgrade</option>
        <option value="Suspend">Suspend</option>
        <option value="Temporary Off">Temporary Off</option>
        <option value="Terminate">Terminate</option>
        <option value="Reactivated">Reactivated</option>
    </select>
</div>

<!-- Bandwidth Fields -->
<div id="bandwidthFields" class="hidden">
    <label>Old Bandwidth (Mbps):</label>
    <input type="text" name="old_value" class="form-control" value="<?= htmlspecialchars($customer['bandwidth_mbps']) ?>" readonly>

    <label>New Bandwidth (Mbps):</label>
    <input type="number" name="new_value" class="form-control" placeholder="Enter new bandwidth in Mbps">
</div>

<!-- Date Fields -->
<div id="dateFields" class="hidden">
    <label>Start Date:</label>
    <input type="date" name="start_date" class="form-control">
    <label>End Date:</label>
    <input type="date" name="end_date" class="form-control">
</div>

<div class="mb-3">
    <label>Notes / Reason:</label>
    <textarea name="notes" class="form-control" rows="3" required></textarea>
</div>

<div class="text-end">
    <button type="submit" class="btn gerrys-btn px-4">
        <i class="fa-solid fa-paper-plane"></i> Submit Request
    </button>
</div>
</form>
</div>
</div>

<script>
document.getElementById('eventType').addEventListener('change', function() {
    let type = this.value;
    let bwFields = document.getElementById('bandwidthFields');
    let dateFields = document.getElementById('dateFields');

    bwFields.classList.add('hidden');
    dateFields.classList.add('hidden');

    if (type === 'Upgrade' || type === 'Downgrade' || type === 'Reactivated') {
        bwFields.classList.remove('hidden');
        dateFields.classList.remove('hidden');
    } 
    else if (type === 'Suspend' || type === 'Temporary Off' || type === 'Terminate') {
        dateFields.classList.remove('hidden');
    }
});
</script>

</body>
</html>
