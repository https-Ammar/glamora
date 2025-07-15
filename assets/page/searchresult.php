<?php
require('db.php'); // Make sure db.php correctly sets up $conn

// Check if the 'searchinput' parameter is set
if (isset($_POST['searchinput'])) {
    $searchTerm = $_POST['searchinput'];

    // Check for connection errors
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare the SQL query to search for products containing the search term
    $stmt = $conn->prepare("SELECT name FROM products WHERE name LIKE ?");
    $likeTerm = "%" . $searchTerm . "%"; // Add % for partial matching
    $stmt->bind_param("s", $likeTerm); // Bind the search term to prevent SQL injection

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any results are found
    if ($result->num_rows > 0) {
        // Output the results in a loop as <option> elements
        while ($row = $result->fetch_assoc()) {
            echo "<option value=\"" . htmlspecialchars($row['name']) . "\">" . htmlspecialchars($row['name']) . "</option>";
        }
    } else {
        echo "<option>No results found.</option>";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
