<?php
// db_connect.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerrys_portal";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set UTF-8 encoding (for security & international characters)
$conn->set_charset("utf8mb4");
?>
