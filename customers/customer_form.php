<?php
session_start();
include '../includes/db.php';
include($_SERVER['DOCUMENT_ROOT'] . "/gerrys_project/includes/header.php");

// Logged-in station ID
$station_id = $_SESSION['station_id'] ?? 0;

// Customer ID
$customer_id = $_GET['id'] ?? 0;

// Fetch customer info
$customer_query = $conn->query("SELECT * FROM customers WHERE id = $customer_id");
$customer = $customer_query->fetch_assoc();

// Fetch POPs only for this station
$pop_result = $conn->query("
    SELECT pop_id, pop_name 
    FROM pops 
    WHERE station_id = $station_id
    ORDER BY pop_name ASC
");

// Fetch vendors (filtered by station if column exists)
$vendor_result = $conn->query("
    SELECT id, vendor_name 
    FROM vendors
    ORDER BY vendor_name ASC
");

// Fetch already assigned POPs for this customer
$assigned_pop_result = $conn->query("
    SELECT pop_id 
    FROM customer_pops 
    WHERE customer_id = $customer_id
");

$assigned_pops = [];
while ($row = $assigned_pop_result->fetch_assoc()) {
    $assigned_pops[] = $row['pop_id'];
}

$station = $_SESSION['station'] ?? 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add New Customer</title>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
body {
    background-color: #f8f9fa;
    margin-left: 260px;
    padding: 20px;
}
.card {
    border-radius: 18px;
    border: none;
}
.form-label {
    font-weight: 500;
}

/* Input, Select, Textarea Borders */
.form-control, .form-select {
    border: 1px solid #ced4da !important;
    border-radius: 6px;
    box-shadow: none;
}

/* Focus state */
.form-control:focus, .form-select:focus {
    border-color: #ebb41e !important;
    box-shadow: 0 0 0 0.2rem rgba(235, 180, 30, 0.25) !important;
}

/* Select2 styling for multiple select dropdowns */
.select2-container--default .select2-selection--multiple {
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 6px;
    min-height: 40px;
}

.select2-container--default .select2-selection--multiple:focus {
    border-color: #ebb41e;
    box-shadow: 0 0 0 0.2rem rgba(235, 180, 30, 0.25);
}
</style>
</head>

<body>

<div class="container mt-4">
<div class="card shadow p-4">

<h3 class="text-center mb-4 text-dark fw-bold">
<i class="fa-solid fa-user-plus me-2 text-warning"></i>Add New Customer In Gerry's
</h3>

<form method="POST" action="save_customer.php">
<div class="row g-3">

<!-- Customer Name -->
<div class="col-md-6">
<label class="form-label">Customer Name</label>
<input type="text" name="customer_name" class="form-control" required>
</div>

<!-- Company -->
<div class="col-md-6">
<label class="form-label">Company Name</label>
<input type="text" name="company_name" class="form-control" required>
</div>

<!-- CNIC -->
<div class="col-md-6">
<label class="form-label">CNIC / NTN No</label>
<input type="text" name="cnic_no" class="form-control" required>
</div>

<!-- Status -->
<div class="col-md-6">
<label class="form-label">Status</label>
<select name="status" class="form-control" required>
<option value="Active">Active</option>
<option value="Temp Off">Temp Off</option>
<option value="Suspended">Suspended</option>
<option value="Terminated">Terminated</option>
</select>
</div>

<!-- Email -->
<div class="col-md-6">
<label class="form-label">Email</label>
<input type="email" name="emails[]" class="form-control mb-2">
<input type="email" name="emails[]" class="form-control mb-2">
<input type="email" name="emails[]" class="form-control">
</div>

<!-- Mobile -->
<div class="col-md-6">
<label class="form-label">Mobile Numbers</label>
<input type="tel" name="mobile_numbers[]" class="form-control mb-2" placeholder="3XXXXXXXXX">
<input type="tel" name="mobile_numbers[]" class="form-control mb-2" placeholder="3XXXXXXXXX">
<input type="tel" name="mobile_numbers[]" class="form-control" placeholder="3XXXXXXXXX">
</div>

<!-- Location -->
<div class="col-md-6">
<label class="form-label">Location</label>
<input type="text" name="location" class="form-control" required>
</div>

<!-- Address -->
<div class="col-md-6">
<label class="form-label">Address</label>
<input type="text" name="address" class="form-control" required>
</div>

<!-- Connection Type -->
<div class="col-md-6">
<label class="form-label">Connection Type</label>
<select name="connection_type" class="form-select" required>
<option value="">Select</option>
<option value="Radio Frequency">Radio Frequency</option>
<option value="Fiber">Fiber</option>
</select>
</div>

<!-- Bandwidth -->
<div class="col-md-6">
<label class="form-label">Bandwidth</label>
<input type="text" name="bandwidth" class="form-control" required>
</div>

<!-- Installed By -->
<div class="col-md-6">
    <label class="form-label">Installed By</label>
    <select name="installed_by" id="installed_by" class="form-select">
        <option value="">Select Option</option>
        <option value="Self Manage">Self Manage</option>
        <option value="Vendor">Vendor</option>
    </select>
</div>

<!-- Vendors -->
<div class="col-md-6">
    <label class="form-label">Vendor(s)</label>
    <select name="vendor_id[]" id="vendorSelect" multiple class="form-control">
        <?php while ($vendor = $vendor_result->fetch_assoc()): ?>
            <option 
                value="<?= $vendor['id']; ?>" 
                <?= ($vendor['vendor_name'] == 'Gerrys') ? 'data-gerrys="1"' : '' ?>
            >
                <?= $vendor['vendor_name']; ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<!-- POPs -->
<div class="col-md-6">
<label class="form-label">POP(s)</label>
<select name="pop_id[]" id="popSelect" multiple class="form-control">
<?php while ($pop = $pop_result->fetch_assoc()): ?>
<option value="<?= $pop['pop_id']; ?>" 
<?= in_array($pop['pop_id'], $assigned_pops) ? "selected" : "" ?>>
<?= $pop['pop_name']; ?>
</option>
<?php endwhile; ?>
</select>
</div>

<!-- IP -->
<div class="col-md-6">
<label class="form-label">IP Address</label>
<input type="text" name="ip_address" class="form-control" placeholder="192.168.1.1">
</div>

<!-- VLAN -->
<div class="col-md-6">
<label class="form-label">VLAN</label>
<input type="text" name="vlan" class="form-control">
</div>

<!-- Subscriber Type -->
<div class="col-md-6">
    <label class="form-label">Subscriber Type</label>
    <input type="text" name="subscriber_type" id="subscriber_type" class="form-control">
</div>

<!-- Station -->
<div class="col-md-6">
<label class="form-label">Station</label>
<input type="text" name="station" class="form-control" readonly value="<?= $station ?>">
</div>

<!-- Remarks -->
<div class="col-md-12">
<label class="form-label">Remarks</label>
<textarea name="remarks" class="form-control" rows="2"></textarea>
</div>

<!-- Submit -->
<div class="col-12 text-center">
<button type="submit" class="btn btn-primary px-5">Save Customer</button>
</div>

</div>
</form>

</div>
</div>

<script>
$(document).ready(function () {

    // POP Select
    $('#popSelect').select2({
        placeholder: "Select POP(s)",
        allowClear: true,
        width: "100%"
    });

    // Vendor Select2
    $('#vendorSelect').select2({
        placeholder: "Select Vendor(s)",
        allowClear: true,
        width: "100%"
    });

    $("#installed_by").on("change", function () {
        let installedBy = $(this).val();
        let subscriberTypeInput = $("#subscriber_type");

        if (installedBy === "Self Manage") {

            // Subscriber Type = FLL
            subscriberTypeInput.val("FLL").prop("readonly", true);

            // Auto select Gerrys vendor
            let gerrysVendor = $('#vendorSelect option[data-gerrys="1"]').val();
            $("#vendorSelect").val([gerrysVendor]).trigger("change");

            // Lock vendor
            $("#vendorSelect").prop("disabled", true);

        } 
        else if (installedBy === "Vendor") {

            // Subscriber Type = CVAS
            subscriberTypeInput.val("CVAS").prop("readonly", true);

            // Vendor field enabled
            $("#vendorSelect").prop("disabled", false);

            // Clear vendor selection
            $("#vendorSelect").val(null).trigger("change");
        } 
        else {

            // Reset all
            subscriberTypeInput.val("").prop("readonly", false);
            $("#vendorSelect").prop("disabled", false);
            $("#vendorSelect").val(null).trigger("change");
        }
    });

});

</script>

</body>
</html>
