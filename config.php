<?php
// Localhost Database Credentials
/*
$host = "localhost";
$username = "root";
$password = "";
$database = "blog_db";
*/

// InfinityFree Hosting Database Credentials
$host = "sql313.infinityfree.com"; 
$username = "if0_42417692";
$password = "Dulmini2004";
$database = "if0_42417692_blog_db";

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

?>
