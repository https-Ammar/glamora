<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "shop";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Database connection error");
}

$conn->set_charset("utf8mb4");
?>