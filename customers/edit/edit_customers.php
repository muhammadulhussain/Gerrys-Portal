<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_role(['Admin']); // Only admin can edit

include($_SERVER['DOCUMENT_ROOT'] . "/gerrys_project/includes/db.php");

// ----------- VALIDATE CUSTOMER ID ----------
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Customer ID");
}
$customer_id = intval($_GET['id']);

// ----------- FETCH CUSTOMER DATA ----------
$query = $conn->query("SELECT * FROM customers WHERE id = $customer_id");
if ($query->num_rows == 0) {
    die("Customer Not Found!");
}
$customer = $query->fetch_assoc();

// ----------- FETCH STATIONS ----------
$stations = [];
$stationQuery = $conn->query("SELECT id, name FROM stations ORDER BY name ASC");
if ($stationQuery && $stationQuery->num_rows > 0) {
    while ($row = $stationQuery->fetch_assoc()) {
        $stations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Customer</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background-color: #fdfcf8; font-family: 'Poppins', sans-serif; }
  .header-bar {
      background: linear-gradient(90deg, #fff, #ebb41e);
      padding: 15px 25px;
      display: flex; align-items: center; justify-content: space-between;
      border-radius: 0 0 12px 12px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.2);
  }
  .header-bar img { height: 70px; }
  .btn-back { background-color: #ebb41e; border: none; font-weight: 600; }
  .btn-back:hover { background-color: #fff; color: #ebb41e; }
  .card { border-radius: 18px; border: none; }
  .btn-primary {
      background-color: #ebb41e;
      border: none;
      font-weight: 600;
      color: #000;
  }
  .btn-primary:hover { background-color: #e0a800; color: #fff; }
</style>
</head>
<body>

<div class="header-bar">
  <div class="d-flex align-items-center">
    <img src="https://www.gerrys.net/img/index/git_logo.png" alt="Gerry's Logo">
    <h3 class="ms-3">Edit Customer</h3>
  </div>
  <a href="../customer_info.php" class="btn btn-back">
    Back
  </a>
</div>

<div class="container mt-4">
  <div class="card shadow p-4">

    <form method="POST" action="update_customer.php">
      <input type="hidden" name="customer_id" value="<?= $customer_id ?>">

      <div class="row g-3">

        <!-- Customer Name -->
        <div class="col-md-6">
          <label class="form-label">Customer Name</label>
          <input type="text" name="customer_name" value="<?= htmlspecialchars($customer['customer_name']) ?>" class="form-control" required>
        </div>

        <!-- Company Name -->
        <div class="col-md-6">
          <label class="form-label">Company Poc</label>
          <input type="text" name="company_name" value="<?= htmlspecialchars($customer['company_name']) ?>" class="form-control">
        </div>

        <!-- CNIC -->
        <div class="col-md-6">
          <label class="form-label">CNIC / NTN No</label>
          <input type="text" name="cnic_no" value="<?= htmlspecialchars($customer['cnic_no']) ?>" class="form-control">
        </div>

        <!-- IP -->
        <div class="col-md-6">
          <label class="form-label">IP Address</label>
          <input type="text" name="ip" value="<?= htmlspecialchars($customer['ip']) ?>" class="form-control">
        </div>

        <!-- Emails -->
        <div class="col-md-6">
          <label class="form-label">Emails</label>
          <?php 
          $emailList = explode(",", $customer['email']);
          for ($i = 0; $i < 3; $i++) {
              $value = isset($emailList[$i]) ? trim($emailList[$i]) : "";
              echo '<input type="email" name="emails[]" value="'.htmlspecialchars($value).'" class="form-control mb-2" placeholder="Email '.($i+1).'">';
          }
          ?>
        </div>

        <!-- Mobile Numbers -->
        <div class="col-md-6">
          <label class="form-label">Mobile Numbers</label>
          <?php 
          $mobileList = explode(",", $customer['mobile_no']);
          for ($i = 0; $i < 3; $i++) {
              $value = isset($mobileList[$i]) ? trim($mobileList[$i]) : "";
              echo '<input type="tel" name="mobile_numbers[]" value="'.htmlspecialchars($value).'" 
                    class="form-control mb-2" placeholder="3XXXXXXXXX"
                    onblur="addCountryCode(this)">';
          }
          ?>
          <small class="text-muted">Format: Enter without 0 (e.g., 3312345678)</small>
        </div>

        <!-- Address -->
        <div class="col-md-6">
          <label class="form-label">Address</label>
          <input type="text" name="address" value="<?= htmlspecialchars($customer['address']) ?>" class="form-control">
        </div>

        <!-- Connection Type -->
        <div class="col-md-6">
          <label class="form-label">Connection Type</label>
          <select name="connection_type" class="form-select">
            <option value="">Select</option>
            <option value="Fiber" <?= ($customer['connection_type']=="Fiber"?'selected':'') ?>>Fiber</option>
            <option value="Radio Frequency" <?= ($customer['connection_type']=="Radio Frequency"?'selected':'') ?>>Radio Frequency</option>
            <option value="Both" <?= ($customer['connection_type']=="Both"?'selected':'') ?>>Both</option>
          </select>
        </div>

        <!-- Status -->
        <div class="col-md-6">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <?php 
            $opts = ["Active","Temp Off","Suspended","Terminated"];
            foreach($opts as $s){
                echo "<option value='$s' ".($customer['status']==$s?'selected':'').">$s</option>";
            }
            ?>
          </select>
        </div>

        <!-- Bandwidth -->
        <div class="col-md-6">
          <label class="form-label">Bandwidth</label>
          <input type="text" name="bandwidth_mbps" value="<?= htmlspecialchars($customer['bandwidth_mbps']) ?>" class="form-control">
        </div>

        <!-- Installed By -->
        <div class="col-md-6">
          <label class="form-label">Installed By</label>
          <select name="installed_by" id="installed_by" class="form-select" onchange="handleInstalledBy()">
            <option value="">Select</option>
            <option value="Self Manage" <?= ($customer['installed_by']=="Self Manage"?'selected':'') ?>>Self Manage</option>
            <option value="Vendor" <?= ($customer['installed_by']=="Vendor"?'selected':'') ?>>Vendor</option>
          </select>
        </div>

        <!-- Vendor Name -->
        <div class="col-md-6">
          <label class="form-label">Vendor Name</label>
          <select name="vendor_name" id="vendor_name" class="form-select">
            <option value="">Select Vendor</option>
            <?php
            $vendorQuery = $conn->query("SELECT vendor_name FROM vendors");
            while ($row = $vendorQuery->fetch_assoc()) {
                $selected = ($customer['vendor_name'] == $row['vendor_name']) ? "selected" : "";
                echo "<option value='{$row['vendor_name']}' $selected>{$row['vendor_name']}</option>";
            }
            ?>
          </select>
        </div>

        <!-- Subscriber Type -->
        <div class="col-md-6">
          <label class="form-label">Subscriber Type</label>
          <input type="text" id="subscriber_type" name="subscriber_type" 
                 value="<?= htmlspecialchars($customer['subscriber_type']) ?>" 
                 class="form-control" readonly>
        </div>

        <!-- Installation Date -->
        <div class="col-md-6">
          <label class="form-label">Installation Date</label>
          <input type="date" name="install_date" value="<?= $customer['install_date'] ?>" class="form-control">
        </div>

        <!-- VLAN -->
        <div class="col-md-6">
          <label class="form-label">VLAN</label>
          <input type="text" name="vlan" value="<?= htmlspecialchars($customer['vlan']) ?>" class="form-control">
        </div>

        <!-- Station -->
        <div class="col-md-6">
          <label class="form-label">Station</label>
          <select name="station_id" class="form-select" required>
            <?php
            foreach ($stations as $st) {
                $sel = ($customer['station_id'] == $st['id']) ? "selected" : "";
                echo "<option value='{$st['id']}' $sel>{$st['name']}</option>";
            }
            ?>
          </select>
        </div>

        <!-- Remarks -->
        <div class="col-md-12">
          <label class="form-label">Remarks</label>
          <textarea name="remarks" class="form-control"><?= htmlspecialchars($customer['remarks']) ?></textarea>
        </div>

        <!-- SUBMIT -->
        <div class="col-12 text-center mt-3">
          <button type="submit" class="btn btn-primary px-5">Update Customer</button>
        </div>

      </div>
    </form>

  </div>
</div>

<script>
function addCountryCode(input) {
    let val = input.value.trim();
    if (val && !val.startsWith('+92')) {
        val = val.replace(/^0/, '');
        input.value = '+92' + val;
    }
}

function handleInstalledBy() {
    const installedBy = document.getElementById('installed_by').value;
    const vendorInput = document.getElementById('vendor_name');
    const subscriberInput = document.getElementById('subscriber_type');

    if (installedBy === 'Self Manage') {
        vendorInput.value = 'Gerrys';
        vendorInput.readOnly = true;
        subscriberInput.value = 'CVAS';
    } else if (installedBy === 'Vendor') {
        vendorInput.readOnly = false;
        subscriberInput.value = 'FLL';
    } else {
        vendorInput.value = '';
        subscriberInput.value = '';
    }
}
</script> 

</body>
</html>
