<?php
require __DIR__ . '/../config.php';

$res = mysqli_query($conn, "SHOW COLUMNS FROM user LIKE 'remember_token'");
if ($res && mysqli_num_rows($res) == 0) {
    if (mysqli_query($conn, "ALTER TABLE user ADD COLUMN remember_token VARCHAR(255) NULL DEFAULT NULL;")) {
        echo "Successfully added remember_token column to user table.\n";
    } else {
        echo "Error adding column: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Column remember_token already exists in user table.\n";
}
