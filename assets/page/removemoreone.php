<?php
require('./db.php');

if (isset($_POST['id'])) {
    $id = intval($_POST['id']); // Sanitizing the id

    // Prepared statement to select the cart item
    $stmt = $conn->prepare("SELECT qty FROM cart WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $fetch = $result->fetch_assoc();
        $newcount = $fetch['qty'] - 1;

        // Only update if the new count is greater than zero
        if ($newcount > 0) {
            // Prepared statement to update the quantity
            $updateStmt = $conn->prepare("UPDATE `cart` SET qty = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $newcount, $id);
            $updateStmt->execute();

            if ($updateStmt->affected_rows > 0) {
                echo "Quantity updated successfully.";
            } else {
                echo "Failed to update quantity.";
            }

            $updateStmt->close();
        } else {
            echo "Quantity cannot be less than 1.";
        }
    } else {
        echo "Cart item not found.";
    }

    $stmt->close();
}
?>