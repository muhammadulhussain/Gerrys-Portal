<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $inputFile = __DIR__ . '/hyderabad_customers (2).xlsx'; // test file
    $spreadsheet = IOFactory::load($inputFile);
    $sheet = $spreadsheet->getActiveSheet();

    echo "<h3>✅ File loaded successfully!</h3>";
    echo "Sheet title: " . $sheet->getTitle() . "<br><br>";

    // Print first 5 rows
    $data = $sheet->toArray();
    for ($i = 0; $i < 5 && $i < count($data); $i++) {
        echo implode(' | ', $data[$i]) . "<br>";
    }

} catch (Exception $e) {
    echo "<h3 style='color:red;'>❌ Error:</h3> " . $e->getMessage();
}
