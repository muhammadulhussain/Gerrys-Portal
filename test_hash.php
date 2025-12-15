<?php
$hash = '$2y$10$yymSdWX3he0N6SVpa1XB7u/73dp36xGX3x6mi8zATkkSek9Nz.6K6'; // from DB
var_dump(password_verify('Employ@123', $hash)); // expected: bool(true)