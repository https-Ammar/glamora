<?php
require('./db.php');

// Get and sanitize the ID from the URL parameter
$id = intval($_GET['id']); // Ensure $id is an integer

// Prepare the SQL statement
$stmt = $conn->prepare("UPDATE orders SET orderstate = ' I dont agree' WHERE id = ?");
if ($stmt === false) {
    die("Failed to prepare statement: " . $conn->error);
}

// Bind the parameter and execute the statement
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo "Record updated successfully.";
} else {
    echo "Error: " . $stmt->error;
}

// Close the statement and connection
$stmt->close();
$conn->close();
header('location: index.php');
