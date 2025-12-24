<?php
// 1. Database + session protection
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session_check.php';

// 2. Role restriction (adjust roles if needed)
require_role(['Admin', 'Employee']);

// 3. Header (after session is validated)
require_once $_SERVER['DOCUMENT_ROOT'] . '/gerrys_project/includes/header.php';

// 4. Logged-in station info
$station_id = $_SESSION['station_id'] ?? 0;
$station    = $_SESSION['station'] ?? 'Unknown';

// Fetch POPs for this station
$pop_result = $conn->query("SELECT pop_id, pop_name FROM pops WHERE station_id=$station_id ORDER BY pop_name ASC");

// Fetch Vendors for this station
$vendor_result = $conn->query("SELECT id, vendor_name FROM vendors ORDER BY vendor_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add New Customer</title>

<!-- CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
body {
    background-color: #f8f9fa;
    margin-left: 260px;
    padding: 20px;
}
.card { border-radius: 18px; border: none; }
.form-label { font-weight: 500; color: #000; }
.form-control, .form-select {
    border: 1px solid #ced4da; border-radius: 6px; box-shadow: none;
}
.form-control:focus, .form-select:focus {
    border-color: #ebb41e; box-shadow: 0 0 0 0.2rem rgba(235,180,30,0.25);
}
.select2-container--default .select2-selection--multiple {
    border:1px solid #ced4da; border-radius:6px; min-height:40px;
}
.select2-container--default .select2-selection--multiple:focus {
    border-color:#ebb41e; box-shadow:0 0 0 0.2rem rgba(235,180,30,0.25);
}

.btn{
    background-color: #ebb41e;
    border-color: #ebb41e; box-shadow: 0 0 0 0.2rem rgba(235,180,30,0.25);
}

.btn:hover{
    background-color: #918f8cff;
}
</style>
</head>
<body>

<div class="container mt-4">
<div class="card shadow p-4">

<h3 class="text-center mb-4 text-dark fw-bold">
<i class="fa-solid fa-user-plus me-2 text-warning"></i>Add New Customer
</h3>

<form method="POST" action="save_customer.php" id="customerForm">
<div class="row g-3">

<!-- Customer Name -->
<div class="col-md-6">
<label class="form-label">Customer Name</label>
<input type="text" name="customer_name" class="form-control" required>
</div>

<!-- Company -->
<div class="col-md-6">
<label class="form-label">Company POC</label>
<input type="text" name="company_name" class="form-control">
</div>

<!-- CNIC / NTN -->
<div class="col-md-6">
<label class="form-label">CNIC / NTN No</label>
<input type="text" name="cnic_no" class="form-control" id="cnic_no">
</div>

<!-- Status -->
<div class="col-md-6">
<label class="form-label">Status</label>
<select name="status" class="form-select" required>
<option value="Active">Active</option>
<option value="Temp Off">Temp Off</option>
<option value="Suspended">Suspended</option>
<option value="Terminated">Terminated</option>
</select>
</div>

<!-- Emails -->
<div class="col-md-6">
<label class="form-label">Email(s)</label>
<input type="email" name="emails[]" class="form-control mb-2">
<input type="email" name="emails[]" class="form-control mb-2">
<input type="email" name="emails[]" class="form-control mb-2">
<input type="email" name="emails[]" class="form-control">
</div>

<!-- Mobile -->
<div class="col-md-6">
<label class="form-label">Mobile Numbers</label>
<input type="tel" name="mobile_numbers[]" class="form-control mb-2" placeholder="3XXXXXXXXX">
<input type="tel" name="mobile_numbers[]" class="form-control mb-2" placeholder="3XXXXXXXXX">
<input type="tel" name="mobile_numbers[]" class="form-control mb-2" placeholder="3XXXXXXXXX">
<input type="tel" name="mobile_numbers[]" class="form-control" placeholder="3XXXXXXXXX">
</div>

<!-- Location -->
<div class="col-md-6">
<label class="form-label">Location</label>
<input type="text" name="location" class="form-control">
</div>

<!-- Address -->
<div class="col-md-6">
<label class="form-label">Address</label>
<input type="text" name="address" class="form-control">
</div>

<!-- Connection Type -->
<div class="col-md-6">
<label class="form-label">Connection Type</label>
<select name="connection_type" class="form-select" required>
<option value="">Select</option>
<option value="Fiber">Fiber</option>
<option value="Radio Frequency">Radio Frequency</option>
<option value="Fiber & Radio Frequency">Fiber & Radio Frequency</option>
<option value="Ethernet">Ethernet</option>
</select>
</div>

<!-- Bandwidth -->
<div class="col-md-6">
<label class="form-label">Bandwidth (Mbps)</label>
<input type="text" name="bandwidth" class="form-control" id="bandwidth" required placeholder="10">
</div>

<!-- Installed By -->
<div class="col-md-6">
<label class="form-label">Installed By</label>
<select name="installed_by" id="installed_by" class="form-select" required>
<option value="">Select</option>
<option value="Self Manage">Self Manage</option>
<option value="Vendor">Vendor</option>
</select>
</div>

<!-- Vendor -->
<div class="col-md-6">
<label class="form-label">Vendor(s)</label>
<select name="vendor_id[]" id="vendorSelect" class="form-control" multiple>
<?php while($v = $vendor_result->fetch_assoc()): ?>
<option value="<?= $v['id'] ?>" <?= ($v['vendor_name']=='Gerrys')?'data-gerrys="1"':'' ?>>
<?= $v['vendor_name'] ?>
</option>
<?php endwhile; ?>
</select>
<input type="hidden" name="vendor_id_hidden" id="vendor_hidden">
</div>

<!-- POP -->
<div class="col-md-6">
<label class="form-label">POP(s)</label>
<select name="pop_id[]" id="popSelect" class="form-control" multiple>
<?php while($p = $pop_result->fetch_assoc()): ?>
<option value="<?= $p['pop_id'] ?>"><?= $p['pop_name'] ?></option>
<?php endwhile; ?>
</select>
</div>

<!-- IP -->
<div class="col-md-6">
<label class="form-label">IP Address</label>
<input type="text" name="ip_address" class="form-control" id="ip_address">
</div>

<!-- VLAN -->
<div class="col-md-6">
<label class="form-label">VLAN</label>
<input type="text" name="vlan" class="form-control" id="vlan">
</div>

<!-- Subscriber Type -->
<div class="col-md-6">
<label class="form-label">Subscriber Type</label>
<input type="text" name="subscriber_type" id="subscriber_type" class="form-control" readonly>
</div>

<!-- Station -->
<div class="col-md-6">
<label class="form-label">Station</label>
<input type="text" name="station" class="form-control" readonly value="<?= htmlspecialchars($station) ?>">
</div>

<!-- Install Date -->
<div class="col-md-6">
<label class="form-label">Install Date</label>
<input type="date" name="install_date" class="form-control">
</div>


<!-- Remarks -->
<div class="col-md-12">
<label class="form-label">Remarks</label>
<textarea name="remarks" class="form-control" rows="2"></textarea>
</div>

<!-- Submit -->
<div class="col-12 text-center mt-3">
<button type="submit" class="btn btn-primary px-5">Save Customer</button>
<button type="reset" class="btn btn-secondary px-5">Clear</button>
</div>

</div>
</form>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function(){

    $('#popSelect,#vendorSelect').select2({
        placeholder:'Select',
        allowClear:true,
        width:'100%'
    });

    $('#installed_by').on('change', function(){
        let val = $(this).val();
        let vendorSelect = $('#vendorSelect');
        let subscriber = $('#subscriber_type');
        let hiddenVendor = $('#vendor_hidden');

        if(val === 'Self Manage'){
            let gerrysVal = vendorSelect.find('option[data-gerrys="1"]').val();
            vendorSelect.val([gerrysVal]).trigger('change');
            hiddenVendor.val(gerrysVal); // IMPORTANT
            subscriber.val('FLL').prop('readonly', true);
        }
        else if(val === 'Vendor'){
            vendorSelect.prop('disabled', false);
            vendorSelect.val(null).trigger('change');
            hiddenVendor.val('');
            subscriber.val('CVAS').prop('readonly', true);
        }
        else{
            vendorSelect.val(null).trigger('change'); 
            hiddenVendor.val('');
            subscriber.val('').prop('readonly', false);
        }
    });

    $('#customerForm').on('submit', function(){
        if($('#bandwidth').val() === '' || isNaN($('#bandwidth').val())){
            alert('Bandwidth must be numeric');
            return false;
        }
    });

    // Number-only restriction
    $('#cnic_no,#vlan,#mobile_numbers\\[\\],#ip_address').on('input', function(){
        this.value = this.value.replace(/[^0-9., ]/g,'');
    });
});
</script>

</body>
</html>
