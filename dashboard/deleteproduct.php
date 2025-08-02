<?php
require('./db.php');

// Get the id parameter from the URL and sanitize it
$id = intval($_GET['id']); // Using intval to ensure $id is an integer

// Check if $id is valid
if ($id > 0) {
    // Prepare the DELETE queries
    $deleteProductQuery = "DELETE FROM products WHERE id = ?";
    $deleteCartQuery = "DELETE FROM cart WHERE prouductid = ?";

    // Prepare and execute the statements for 'products'
    if ($stmt = mysqli_prepare($conn, $deleteProductQuery)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Prepare and execute the statements for 'cart'
    if ($stmt = mysqli_prepare($conn, $deleteCartQuery)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Redirect back to the index page
header('Location: index.php');
exit; // Make sure to exit after the redirect
