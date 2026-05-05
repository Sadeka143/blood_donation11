<?php
$servername = "127.0.0.1";
$username   = "root";
$password   = "";
$database   = "blood_donation_db";
$port       = 3306;

$conn = mysqli_connect($servername, $username, $password, $database, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>