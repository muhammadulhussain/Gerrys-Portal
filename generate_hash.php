<?php
$plain_password = "Employ@123"; // yahan jo password chahte ho likho
$hash = password_hash($plain_password, PASSWORD_DEFAULT);
echo "Hashed Password: " . $hash;
?>