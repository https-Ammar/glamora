<?php
require('../config/db.php');

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Prepare the DELETE query
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
    $stmt->bind_param("i", $id);

    // Execute the query and check if successful
    if ($stmt->execute()) {
        echo "Item removed from cart.";
    } else {
        echo "Error: Could not delete item.";
    }

    // Close the statement
    $stmt->close();
}
?>