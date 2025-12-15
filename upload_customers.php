<?php
require_once(__DIR__ . '../includes/db.php');
require_once __DIR__ . '../includes/session_check.php';
require_role(['Employee', 'Admin']);
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['upload_excel'])) {

    $file = $_FILES['file']['tmp_name'];
    if (!$file) {
        die("No file uploaded.");
    }

    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    // Loop rows (skip header)
    for ($i = 1; $i < count($rows); $i++) {

        $row = $rows[$i];

        // Excel Mapping
        $customer_name   = trim($row[1]); // Column B
        $bandwidth_mbps  = trim($row[2]); // Column C
        $ip              = trim($row[3]); // Column D
        $company_name    = trim($row[4]); // Column E
        $mobile_no       = trim($row[5]); // Column F
        $email           = trim($row[6]); // Column G
        $address         = trim($row[7]); // Column H
        $connection_type = trim($row[9]); // Column J

        // Fixed Values
        $station_id = 7;
        $status = "Active";

        // Prepare and Insert
        $stmt = $conn->prepare("
            INSERT INTO customers 
            (customer_name, company_name, ip, bandwidth_mbps, mobile_no, email, address, connection_type, station_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // All string binding is SAFE and avoids FK issue
        $stmt->bind_param(
            "ssssssssss",
            $customer_name,
            $company_name,
            $ip,
            $bandwidth_mbps,
            $mobile_no,
            $email,
            $address,
            $connection_type,
            $station_id, 
            $status
        );

        $stmt->execute();
    }

    echo "✔ Customers uploaded successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Customers</title>
</head>
<body>

<form method="post" enctype="multipart/form-data">
    <h2>Upload Customers Excel</h2>
    <input type="file" name="file" required>
    <button type="submit" name="upload_excel">Upload</button>
</form>

</body>
</html>
