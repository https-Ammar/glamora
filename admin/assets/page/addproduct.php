<?php
require('./db.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $product_name = $_POST['sku'] ?? '';
    $description = $_POST['barcode-type'] ?? '';
    $price = $_POST['price'] ?? 0;
    $discount = $_POST['discount'] ?? 0;
    $category_id = $_POST['cattype'] ?? 0;

    $product_name = $conn->real_escape_string($product_name);
    $description = $conn->real_escape_string($description);
    $price = (float) $conn->real_escape_string($price);
    $discount = (float) $conn->real_escape_string($discount);
    $category_id = (int) $conn->real_escape_string($category_id);

    $total_final_price = ($discount > 0 && $discount <= 100) ? $price - ($price * ($discount / 100)) : $price;

    if (isset($_FILES['img']) && $_FILES['img']['error'] === 0) {
        $img = $_FILES['img'];
        $imageFileType = strtolower(pathinfo($img["name"], PATHINFO_EXTENSION));
        $newFileName = uniqid() . '.' . $imageFileType;
        $target_dir = __DIR__ . "/uploads/";
        $relative_path = "uploads/" . $newFileName;
        $target_file = $target_dir . $newFileName;

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $check = getimagesize($img["tmp_name"]);
        if ($check === false) {
            die("Not an image.");
        }

        if ($img["size"] > 5000000) {
            die("Image too large.");
        }

        if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
            die("Invalid image type.");
        }

        if (move_uploaded_file($img["tmp_name"], $target_file)) {
            $sql = "INSERT INTO products (name, description, price, discount, total_final_price, category_id, img) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssdddis", $product_name, $description, $price, $discount, $total_final_price, $category_id, $relative_path);
                if ($stmt->execute()) {
                    header('Location: index.php');
                    exit;
                } else {
                    echo "DB Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "Prepare failed: " . $conn->error;
            }
        } else {
            echo "Failed to upload file.";
        }
    } else {
        echo "File error code: " . ($_FILES['img']['error'] ?? 'No file uploaded');
    }
}

$conn->close();
?>