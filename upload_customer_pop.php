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

        // Skip header row
        if ($row_number == 1) continue;

        $customer_id = isset($row[0]) ? (int)$row[0] : null;
        $pop_id      = isset($row[1]) ? (int)$row[1] : null;

        // Validate customer
        $valid_customer = $conn->query("SELECT id FROM customers WHERE id = $customer_id")->num_rows > 0;

        // Validate pop
        $valid_pop = $conn->query("SELECT pop_id FROM pops WHERE pop_id = $pop_id")->num_rows > 0;

        if (!$valid_customer || !$valid_pop) {
            $skipped++;
            continue;
        }

        // Avoid duplicate
        $exists = $conn->query("SELECT id FROM customer_pops 
                                WHERE customer_id = $customer_id 
                                AND pop_id = $pop_id")->num_rows > 0;

        if ($exists) {
            $skipped++;
            continue;
        }

        // Insert into customer_pops
        $stmt = $conn->prepare("INSERT INTO customer_pops (customer_id, pop_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $customer_id, $pop_id);
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
    <title>Upload Customer-Pops CSV</title>
</head>
<body>
<form method="post" enctype="multipart/form-data">
    <h2>Upload Customer-POPS CSV</h2>
    <input type="file" name="file" accept=".csv" required>
    <button type="submit" name="upload_csv">Upload CSV</button>
</form>
</body>
</html>
