<?php

require('./db.php'); // Ensure this file sets up the $conn variable for database connection

// Directory to save uploaded files
$filepath = 'uploads/'; // Make sure this directory exists and is writable

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $categories = $_POST['category'];
    $linkaddresses = $_POST['linkaddress'];

    // Check if the 'photo' field is an array and has files uploaded
    if (isset($_FILES['photo']['tmp_name']) && is_array($_FILES['photo']['tmp_name'])) {
        // Process each uploaded file
        foreach ($_FILES['photo']['tmp_name'] as $key => $tmp_name) {
            $photo = $_FILES['photo']['name'][$key];
            $photo_tmp = $_FILES['photo']['tmp_name'][$key];
            $photo_path = $filepath . basename($photo);

            // Ensure the target directory exists
            if (!file_exists($filepath)) {
                mkdir($filepath, 0777, true);
            }

            // Move uploaded file to the target directory
            if (move_uploaded_file($photo_tmp, $photo_path)) {
                // Prepare and escape data for the SQL query
                $category_id = mysqli_real_escape_string($conn, $categories[$key]);
                $photo_path_escaped = mysqli_real_escape_string($conn, $photo_path);
                $linkaddress_escaped = mysqli_real_escape_string($conn, $linkaddresses[$key]);

                // Insert data into the database
                $sql = "INSERT INTO ads (categoryid, photo, linkaddress) VALUES ('$category_id', '$photo_path_escaped', '$linkaddress_escaped')";

                if (!mysqli_query($conn, $sql)) {
                    echo "Error: " . mysqli_error($conn);
                }
            } else {
                echo "Failed to upload file: " . htmlspecialchars($photo) . "<br>";
            }
        }
    } else {
        echo "No files were uploaded.";
    }

    mysqli_close($conn);
}
header('location:index.php');
?>