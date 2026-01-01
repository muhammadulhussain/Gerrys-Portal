<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_role(['Admin']); // ONLY ADMIN

require_once($_SERVER['DOCUMENT_ROOT'] . "/gerrys_project/includes/db.php");

/* ---------------- VALIDATE CUSTOMER ID ---------------- */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Customer ID");
}
$customer_id = (int)$_GET['id'];

/* ---------------- FETCH CUSTOMER ---------------- */
$cq = $conn->query("SELECT * FROM customers WHERE id=$customer_id");
if ($cq->num_rows === 0) {
    die("Customer not found");
}
$customer = $cq->fetch_assoc();

/* ---------------- STATIONS ---------------- */
$stations = [];
$stq = $conn->query("SELECT id,name FROM stations ORDER BY name ASC");
while($r = $stq->fetch_assoc()){
    $stations[] = $r;
}

/* ---------------- POPs ---------------- */
$pop_result = $conn->query("
    SELECT pop_id, pop_name 
    FROM pops 
    WHERE station_id = {$customer['station_id']}
    ORDER BY pop_name ASC
");

/* customer pops */
$customer_pops = [];
$cp = $conn->query("SELECT pop_id FROM customer_pops WHERE customer_id=$customer_id");
while($r = $cp->fetch_assoc()){
    $customer_pops[] = $r['pop_id'];
}

/* ---------------- VENDORS ---------------- */
$vendor_result = $conn->query("SELECT id,vendor_name FROM vendors ORDER BY vendor_name ASC");

/* customer vendors */
$customer_vendors = [];
$cv = $conn->query("SELECT vendor_id FROM customer_vendors WHERE customer_id=$customer_id");
while($r = $cv->fetch_assoc()){
    $customer_vendors[] = $r['vendor_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Customer</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

<style>
body{background:#fdfcf8;font-family:Poppins,sans-serif}
.header-bar {
  background: linear-gradient(90deg, #fff, #ebb41e);
  padding: 15px 25px;
  display: flex; align-items: center; justify-content: space-between;
  border-radius: 0 0 12px 12px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.2);
}
.header-bar img { height: 70px; }
.card{border-radius:18px;border:none}
.btn-primary{background:#ebb41e;border:none;color:#000;font-weight:600}
.btn-primary:hover{background:#e0a800;color:#fff}
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

<h3 class="text-center mb-4">Edit Customer</h3>

<form method="POST" action="update_customer.php" id="editCustomerForm">
<input type="hidden" name="customer_id" value="<?= $customer_id ?>">

<div class="row g-3">

<div class="col-md-6">
<label class="form-label">Customer Name</label>
<input type="text" name="customer_name" class="form-control"
value="<?= htmlspecialchars($customer['customer_name']) ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Company POC</label>
<input type="text" name="company_name" class="form-control"
value="<?= htmlspecialchars($customer['company_name']) ?>">
</div>

<div class="col-md-6">
<label class="form-label">CNIC / NTN</label>
<input type="text" name="cnic_no" class="form-control"
value="<?= htmlspecialchars($customer['cnic_no']) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Status</label>
<select name="status" class="form-select">
<?php
foreach(['Active','Temp Off','Suspended','Terminated'] as $s){
echo "<option value='$s' ".($customer['status']==$s?'selected':'').">$s</option>";
}
?>
</select>
</div>

<!-- Emails -->
<div class="col-md-6">
<label class="form-label">Emails</label>
<?php
$emails = explode(',', $customer['email']);
for($i=0;$i<4;$i++){
$val = $emails[$i] ?? '';
echo "<input type='email' name='emails[]' class='form-control mb-2' value='".htmlspecialchars(trim($val))."'>";
}
?>
</div>

<!-- Mobiles -->
<div class="col-md-6">
<label class="form-label">Mobile Numbers</label>
<?php
$mob = explode(',', $customer['mobile_no']);
for($i=0;$i<4;$i++){
$val = $mob[$i] ?? '';
echo "<input type='tel' name='mobile_numbers[]' class='form-control mb-2' value='".htmlspecialchars(trim($val))."'>";
}
?>
</div>

<div class="col-md-6">
<label class="form-label">Location</label>
<input type="text" name="location" class="form-control"
value="<?= htmlspecialchars($customer['location'] ?? '') ?>">
</div>

<div class="col-md-6">
<label class="form-label">Address</label>
<input type="text" name="address" class="form-control"
value="<?= htmlspecialchars($customer['address']) ?>">
</div>

<div class="col-md-6">
<label class="form-label">Connection Type</label>
<select name="connection_type" class="form-select">
<?php
foreach(['Fiber','Radio Frequency','Both'] as $c){
echo "<option value='$c' ".($customer['connection_type']==$c?'selected':'').">$c</option>";
}
?>
</select>
</div>

<div class="col-md-6">
<label class="form-label">Bandwidth</label>
<input type="text" name="bandwidth_mbps" class="form-control"
value="<?= htmlspecialchars($customer['bandwidth_mbps']) ?>">
</div>

<!-- Installed By -->
<div class="col-md-6">
<label class="form-label">Installed By</label>
<select name="installed_by" id="installed_by" class="form-select">
<option value="">Select</option>
<option value="Self Manage" <?= $customer['installed_by']=='Self Manage'?'selected':'' ?>>Self Manage</option>
<option value="Vendor" <?= $customer['installed_by']=='Vendor'?'selected':'' ?>>Vendor</option>
<option value="Self Manage & Vendor" <?= $customer['installed_by']=='Self Manage & Vendor'?'selected':'' ?>>Self Manage & Vendor</option>
</select>
</div>

<!-- Vendors -->
<div class="col-md-6">
<label class="form-label">Vendors</label>
<select name="vendor_id[]" id="vendorSelect" class="form-control" multiple>
<?php while($v = $vendor_result->fetch_assoc()): ?>
<option value="<?= $v['id'] ?>"
<?= in_array($v['id'],$customer_vendors)?'selected':'' ?>
<?= ($v['vendor_name']=='Gerrys')?'data-gerrys="1"':'' ?>>
<?= $v['vendor_name'] ?>
</option>
<?php endwhile; ?>
</select>
<input type="hidden" name="vendor_hidden" id="vendor_hidden">
</div>

<!-- POPs -->
<div class="col-md-6">
<label class="form-label">POPs</label>
<select name="pop_id[]" id="popSelect" class="form-control" multiple>
<?php while($p = $pop_result->fetch_assoc()): ?>
<option value="<?= $p['pop_id'] ?>"
<?= in_array($p['pop_id'],$customer_pops)?'selected':'' ?>>
<?= $p['pop_name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-6">
<label class="form-label">Subscriber Type</label>
<input type="text" name="subscriber_type" id="subscriber_type"
class="form-control" value="<?= htmlspecialchars($customer['subscriber_type']) ?>" readonly>
</div>

<div class="col-md-6">
<label class="form-label">Station</label>
<select name="station_id" class="form-select">
<?php foreach($stations as $s){
echo "<option value='{$s['id']}' ".($customer['station_id']==$s['id']?'selected':'').">{$s['name']}</option>";
} ?>
</select>
</div>

<div class="col-md-6">
<label class="form-label">Install Date</label>
<input type="date" name="install_date" class="form-control"
value="<?= $customer['install_date'] ?>">
</div>

<div class="col-md-12">
<label class="form-label">Remarks</label>
<textarea name="remarks" class="form-control"><?= htmlspecialchars($customer['remarks']) ?></textarea>
</div>

<div class="col-12 text-center mt-3">
<button class="btn btn-primary px-5">Update Customer</button>
</div>

</div>
</form>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function(){

$('#vendorSelect,#popSelect').select2({width:'100%'});

function handleInstalledBy(){
let val = $('#installed_by').val();
let gerrys = $('#vendorSelect option[data-gerrys="1"]').val();

if(val==='Self Manage'){
$('#vendorSelect').val([gerrys]).trigger('change');
$('#subscriber_type').val('FLL');
}
else if(val==='Vendor'){
$('#vendorSelect').val(null).trigger('change');
$('#subscriber_type').val('CVAS');
}
else if(val==='Self Manage & Vendor'){
$('#vendorSelect').val([gerrys]).trigger('change');
$('#subscriber_type').val('FLL & CVAS');
}
}

$('#installed_by').on('change',handleInstalledBy);
handleInstalledBy();
});
</script>

</body>
</html>
