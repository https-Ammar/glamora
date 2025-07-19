<?php
require('./db.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = $_POST['name'];

    // Sanitize the input
    $name = $conn->real_escape_string($name);

    // Query to get the last inserted ID from the 'categories' table
    $sql = "SELECT id FROM categories ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Fetch the last ID
        $row = $result->fetch_assoc();
        $last_id = $row['id'];
        $thisid = $last_id + 1;
    } else {
        // If no record exists, set the ID to 1
        $thisid = 1;
    }

    // Prepare and execute the insert query
    $insert_sql = "INSERT INTO categories (id, name) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_sql);

    if ($stmt) {
        $stmt->bind_param("is", $thisid, $name);
        if ($stmt->execute()) {
            header('Location: index.php');
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}
?>