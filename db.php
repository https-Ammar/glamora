<?php
$servername = "localhost";  // Database server (usually localhost)
$username = "root";         // Database username
$password = "root";             // Database password
$dbname = "shop";    // Name of the database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>