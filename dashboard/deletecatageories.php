<?php
// Include your database connection file
require('./db.php');

// Check if the form is submitted and if 'id' is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Get the category ID from the form
    $categoryId = intval($_POST['id']);

    // Prepare the DELETE SQL query
    $sql = "DELETE FROM categories WHERE id = ?";
    $product = mysqli_query($conn, "DELETE FROM products WHERE category_id = $categoryId");
    $product1 = mysqli_query($conn, "DELETE FROM ads WHERE categoryid = $categoryId");
    // Prepare and execute the query
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind the parameter to the query
        mysqli_stmt_bind_param($stmt, "i", $categoryId);

        // Execute the query
        if (mysqli_stmt_execute($stmt)) {
            // Redirect to a success or listing page after deletion
            header("Location: index.php");
            exit();
        } else {
            echo "Error: Could not execute query. " . mysqli_error($conn);
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        echo "Error: Could not prepare query. " . mysqli_error($conn);
    }
} else {
    // If 'id' is not set or invalid request, redirect back
    header("Location: categories_list.php?message=Invalid+Request");
    exit();
}

// Close the database connection
mysqli_close($conn);
?>