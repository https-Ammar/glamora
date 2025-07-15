<?php
require('../../db.php');

// Check if id is provided in URL for editing
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the current product data from the database
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id); // Bind the id as an integer
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if product exists
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        echo "Product not found!";
        exit;
    }

    // If the form is submitted, handle the update
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
        $new_image = null;

        // Check if a new image is uploaded
        if ($img["name"]) {
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
                    $new_image = $target_file;  // Set the new image path
                } else {
                    echo "Sorry, there was an error uploading your file.";
                }
            }
        } else {
            // If no new image, use the existing image
            $new_image = $product['img'];
        }

        // Update the product in the database
        $sql_update = "UPDATE products SET name = ?, description = ?, price = ?, discount = ?, total_final_price = ?, category_id = ?, img = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update) {
            // Bind parameters and update product
            $stmt_update->bind_param("ssdddisi", $product_name, $description, $price, $discount, $total_final_price, $category_id, $new_image, $id);

            if ($stmt_update->execute()) {
                echo "Product updated successfully with a final price of: " . $total_final_price;
                // Redirect after successful update
                header('Location: index.php');
                exit;
            } else {
                echo "Error: " . $stmt_update->error;
            }

            $stmt_update->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    }
} else {
    echo "No product id provided!";
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="/dashboard/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="./style/style.css">
    <link rel="stylesheet" href="./style/main.css">
    <link rel="stylesheet" href="./style/king.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" type="text/css" href="./style/vendor.css">
    <link rel="stylesheet" type="text/css" href="./style/all.css">
    <link rel="stylesheet" href="./style/next.css">



    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,700,800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />


    <!-- ######################################################################################################################## -->
    <!-- ######################################################################################################################## -->
    <link rel="stylesheet" href="./style/css/bootstrap.min.css">
    <link rel="stylesheet" href="./style/css/all.min.css">
    <link rel="stylesheet" href="./style/css/perfect-scrollbar.css">
    <link rel="stylesheet" href="./style/css/pace.css">
    <link rel="stylesheet" href="./style/css/apexcharts.css">
    <link rel="stylesheet" href="./style/css/main.min.css">

    <link rel="stylesheet" href="./style/css/custom.css">
    <!-- ######################################################################################################################## -->






</head>

<body>




    <!--  -->


    <div class="Div_Hid" id="div4" style="display: block;">
        <div class="main-wrapper">



            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-body">




                            <h5 class="card-title">Validation</h5>
                            <p>
                                Provide valuable, actionable feedback to your users with
                                HTML5 form validation.
                            </p>

                            <form method="POST" enctype="multipart/form-data" class="row g-3 needs-validation">

                                <div class="col-md-4">
                                    <label for="validationCustom01" class="form-label">Product name
                                    </label>
                                    <!-- edit -->

                                    <input type="text" id="sku" name="sku" placeholder="Product name"
                                        class="form-control" required=""
                                        value="<?= htmlspecialchars($product['name']) ?>">

                                </div>
                                <div class="col-md-4">
                                    <label for="validationCustom02" class="form-label">price</label>
                                    <input type="text" id="price" step="0.01" name="price" placeholder="price"
                                        class="form-control" required=""
                                        value="<?= htmlspecialchars($product['price']) ?>">


                                </div>
                                <div class="col-md-4">
                                    <label for="validationCustomUsername" class="form-label">discount by %
                                        (optional)</label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text" id="inputGroupPrepend">@</span>

                                        <input type="text" id="discount" name="discount" class="form-control"
                                            placeholder="discount by %"
                                            value="<?= htmlspecialchars($product['discount']) ?>" min="0" max="100">


                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="validationCustom03" class="form-label">description</label>
                                    <input type="text" id="barcode-type" name="barcode-type"
                                        value="<?= htmlspecialchars($product['description']) ?>" class="form-control"
                                        placeholder="description" required="">


                                </div>
                                <div class="col-md-3">
                                    <label for="validationCustom04" class="form-label">select Catgory
                                    </label>
                                    <!--  -->

                                    <select id="cattype" name="cattype" class="form-select" required="">
                                        <?php
                                        // Fetch categories
                                        $cat_sql = "SELECT * FROM catageories";
                                        $cat_result = $conn->query($cat_sql);
                                        while ($category = $cat_result->fetch_assoc()) {
                                            $selected = ($category['id'] == $product['category_id']) ? "selected" : "";
                                            echo "<option value='" . $category['id'] . "' $selected>" . htmlspecialchars($category['name']) . "</option>";
                                        }
                                        ?>

                                    </select>





                                    <input type="hidden" name="cattype" id="inputoption">


                                    <!--  -->





                                </div>
                                <div class="col-md-3">
                                    <label for="validationCustom05" class="form-label">img cover</label>
                                    <input type="file" id="img" name="img" placeholder="img" class="form-control"
                                        required="">




                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="invalidCheck"
                                            required="">
                                        <label class="form-check-label" for="invalidCheck">
                                            Agree to terms and conditions
                                        </label>
                                        <div class="invalid-feedback">
                                            You must agree before submitting.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">


                                    <button type="submit" class="up_lo btn btn-secondary">Update Product</button>

                                </div>
                            </form>





                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
    <!--  -->



    <style>
        .row.g-3.needs-validation {
            margin: 20px auto;
        }

        .star.goldstar {
            width: 35px;
            height: 35px;
            border-radius: 3px;
            background-size: cover;
        }

        input[type="file"]::file-selector-button {
            visibility: hidden;
        }

        input#img {
            color: #6c757d !important;
            font-size: small;
        }

        div#div4 {
            display: flex !important;

            align-items: center;
            justify-content: center;
            height: 100vh;
        }
    </style>

</body>

</html>