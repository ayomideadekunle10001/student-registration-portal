<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "library_borrowing_db";

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>