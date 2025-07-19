<?php
require('./db.php');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Collect form data
    $product_name = $_POST['sku'];
    $description = $_POST['barcode-type'];
    $price = $_POST['price'];
    $discount = $_POST['discount'];
    $category_id = $_POST['cattype'];

    // Sanitize form data
    $product_name = $conn->real_escape_string($product_name);
    $description = $conn->real_escape_string($description);
    $price = $conn->real_escape_string($price);
    $discount = $conn->real_escape_string($discount);
    $category_id = $conn->real_escape_string($category_id);

    // Calculate the final price after applying the discount
    if ($discount > 0 && $discount <= 100) {
        $total_final_price = $price - ($price * ($discount / 100));
    } else {
        $total_final_price = $price; // No discount applied
    }

    // Handle file upload (for the image)
    $img = $_FILES['img'];
    $target_dir = "uploads/"; // Directory to save uploaded files
    $target_file = $target_dir . basename($img["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file is an actual image
    $check = getimagesize($img["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size (5MB max)
    if ($img["size"] > 5000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats (jpg, png, jpeg, gif)
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if everything is OK for file upload
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    } else {
        if (move_uploaded_file($img["tmp_name"], $target_file)) {
            echo "The file " . basename($img["name"]) . " has been uploaded.";

            // Insert product into the database
            $sql = "INSERT INTO products (name, description, price, discount, total_final_price, category_id, img) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // Bind parameters and insert product including the final price
                $stmt->bind_param("ssdddis", $product_name, $description, $price, $discount, $total_final_price, $category_id, $target_file);

                if ($stmt->execute()) {
                    echo "New product added successfully with a final price of: " . $total_final_price;
                    // Redirect after successful insert
                    header('Location: index.php');
                    exit;
                } else {
                    echo "Error: " . $stmt->error;
                }

                $stmt->close();
            } else {
                echo "Error preparing statement: " . $conn->error;
            }
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

$conn->close();
?>