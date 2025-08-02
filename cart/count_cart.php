<?php
require('db.php');
if (isset($_COOKIE['userid'])) {
  $userid = $_COOKIE['userid'];

  // Initialize the product count variable
  $i = 0;

  // Check if the $conn object exists
  if (isset($conn)) {
    // Prepared statement to count the number of products in the cart
    $stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM cart WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $i = intval($row['product_count']); // Ensure it's an integer
    }

    $stmt->close();
  } else {
    echo "Database connection error.";
  }

  // Output the product count
  echo $i;
} else {
  // Handle case where userid cookie is not set
  echo "User not logged in.";
}
