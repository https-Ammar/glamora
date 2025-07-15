<?php
require('./db.php');


if (isset($_COOKIE['userid'])) {
    $userid = $_COOKIE['userid'];
    $finalproducttotal = 0.0; // Initialize this variable
    $getalltage = ''; // Initialize the variable for accumulating HTML
    $i = 0; // Initialize product count

    // Prepared statement to count the number of products in the cart
    $stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM cart WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $row = $result->fetch_assoc();
        $i = $row['product_count'];
    }
    $stmt->close();

    if ($i > 0) {
        // Prepare statement for getting cart products
        $stmt = $conn->prepare("SELECT * FROM cart WHERE userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $getallcartproducts = $stmt->get_result();

        // Fetch cart products and their details
        while ($getcartproducts = $getallcartproducts->fetch_assoc()) {
            $cartproduct = $getcartproducts['prouductid']; // Fixed typo

            // Prepare statement to fetch product details
            $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $productStmt->bind_param("i", $cartproduct);
            $productStmt->execute();
            $selectproduct = $productStmt->get_result();
            $fetchproduct = $selectproduct->fetch_assoc();
            $getfirstbyfirst = $fetchproduct['total_final_price'] * $getcartproducts['qty'];

            // Accumulate total price
            $finalproducttotal += $getfirstbyfirst;

            // Construct HTML for the table row

            $tage = '<tr>

            <td scope="row" class="py-4">
                <div class="cart-info d-flex flex-wrap align-items-center">
                    <img src="../' . $fetchproduct['img'] . '"
                        class="col-lg-3 viwe_img"
                        
                      ></img>
                    <div class="col-lg-9"></div>
                </div>
            </td>
            <td class="py-4">
                <p>' . htmlspecialchars($fetchproduct["name"]) . '</p>
            </td>
            <td class="py-4">
                <p>count ( ' . htmlspecialchars($getcartproducts["qty"]) . ' )</p>
            </td>
            <td class="py-4">
                <p>' . htmlspecialchars($getfirstbyfirst) . '</p>
            </td>
            <td class="py-4">
                
            </td>
        </tr>';


            $getalltage .= $tage; // Concatenate HTML

            // Ensure to close the product statement
            $productStmt->close();
        }
        $stmt->close();

        // Debugging line: Check final total price before inserting
        echo "Final Total Price: $finalproducttotal"; // Remove after debugging

        // Handle POST request data
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $name = isset($_POST['cleintname']) ? mysqli_real_escape_string($conn, $_POST['cleintname']) : '';
            $phoneone = isset($_POST['phoneone']) ? mysqli_real_escape_string($conn, $_POST['phoneone']) : '';
            $phonetwo = isset($_POST['phonetwo']) ? mysqli_real_escape_string($conn, $_POST['phonetwo']) : '';
            $city = isset($_POST['city']) ? mysqli_real_escape_string($conn, $_POST['city']) : '';
            $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : '';

            // Ensure `data(y-m-d)` is correctly formatted in SQL (use CURDATE() for current date)
            $sql = "INSERT INTO orders1 (name, phoneone, phonetwo, city, address, htmltage, orderstate, data, numberofproducts, finaltotalprice) 
                    VALUES ('$name', '$phoneone', '$phonetwo', '$city', '$address', '$getalltage', 'inprogress', CURDATE(), '$i', '$finalproducttotal')";
            mysqli_query($conn, $sql);
        }

        // Fix DELETE query
        mysqli_query($conn, "DELETE FROM cart WHERE userid = '$userid'");
        header('location: ./index.php');
        exit(); // Ensure exit after redirection

    } else {
        // Redirect if the cart is empty
        header('Location: ./index.php');
        exit(); // Make sure to call exit after header redirection
    }
}
?>