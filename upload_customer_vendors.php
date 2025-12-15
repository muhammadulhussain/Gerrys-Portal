<?php
require_once(__DIR__ . '/includes/db.php');
require_once __DIR__ . '/includes/session_check.php';
require_role(['Employee', 'Admin']);

if (isset($_POST['upload_csv'])) {

    $file = $_FILES['file']['tmp_name'];
    if (!$file) {
        die("No file uploaded.");
    }

    $handle = fopen($file, "r");
    if ($handle === false) {
        die("Cannot open uploaded file.");
    }

    $inserted = 0;
    $skipped = 0;

    $row_number = 0;

    while (($row = fgetcsv($handle, 1000, ",")) !== false) {
        $row_number++;

        // Skip header
        if ($row_number == 1) continue;

        $customer_id = isset($row[0]) ? (int)$row[0] : null;
        $vendor_id   = isset($row[1]) ? (int)$row[1] : null;

        // Validate IDs
        $valid_customer = $conn->query("SELECT id FROM customers WHERE id = $customer_id")->num_rows > 0;
        $valid_vendor   = $conn->query("SELECT id FROM vendors WHERE id = $vendor_id")->num_rows > 0;

        if (!$valid_customer || !$valid_vendor) {
            $skipped++;
            continue;
        }

        // Avoid duplicate insert
        $exists = $conn->query("SELECT id FROM customer_vendors WHERE customer_id = $customer_id AND vendor_id = $vendor_id")->num_rows > 0;
        if ($exists) {
            $skipped++;
            continue;
        }

        // Insert
        $stmt = $conn->prepare("INSERT INTO customer_vendors (customer_id, vendor_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $customer_id, $vendor_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $inserted++;
        } else {
            $skipped++;
        }
    }

    fclose($handle);

    echo "✔ CSV Upload Complete!<br>";
    echo "Inserted: $inserted<br>";
    echo "Skipped (Invalid or Duplicate IDs): $skipped<br>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Customer Vendors CSV</title>
</head>
<body>
<form method="post" enctype="multipart/form-data">
    <h2>Upload Customer-Vendors CSV</h2>
    <input type="file" name="file" accept=".csv" required>
    <button type="submit" name="upload_csv">Upload CSV</button>
</form>
</body>
</html>
