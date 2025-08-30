<?php
require('../config/db.php');
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GLAMORA - Search Results</title>
  <?php require('../includes/link.php'); ?>

</head>

<body>
  <main>
    <section>
      <div class="container-fluid">
        <div class="row">
          <div class="product-grid">
            <?php
            $search = isset($_POST['search']) ? trim($_POST['search']) : '';

            if (!empty($search)) {
              $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ?");
              $likeTerm = "%" . $search . "%";
              $stmt->bind_param("s", $likeTerm);
              $stmt->execute();
              $result = $stmt->get_result();

              if ($result->num_rows > 0) {
                while ($fetchproducts = $result->fetch_assoc()) {
                  $productName = htmlspecialchars($fetchproducts['name']);
                  $productId = htmlspecialchars($fetchproducts['id']);

                  $productImage = '';
                  if (!empty($fetchproducts['img'])) {
                    if (strpos($fetchproducts['img'], 'http') === 0) {
                      $productImage = $fetchproducts['img'];
                    } else {
                      $imagePath = ltrim($fetchproducts['img'], './');
                      $productImage = $base_url . 'admin/admin_shop-main/' . $imagePath;
                    }
                  } else {
                    $productImage = $base_url . 'assets/images/default.jpg';
                  }

                  $productPrice = (float) $fetchproducts['price'];
                  $productSale = isset($fetchproducts['sale_price']) ? (float) $fetchproducts['sale_price'] : null;
                  $onSale = !empty($fetchproducts['on_sale']) && $productSale;
                  $finalPrice = $onSale ? $productSale : $productPrice;
                  $discount = $onSale ? round((($productPrice - $productSale) / $productPrice) * 100) : 0;

                  echo '
                  <div class="product-item swiper-slide">
                    <a href="view.php?id=' . $productId . '" title="' . $productName . '">
                      <figure class="bg_img" style="background-image: url(\'' . $productImage . '\');">
                        ' . ($discount > 0 ? '<span class="badge bg-success">' . $discount . '%</span>' : '') . '
                      </figure>
                    </a>
                    <span class="snize-attribute"><span class="snize-attribute-title"></span> Source Beauty</span>
                    <span class="snize-title">' . $productName . '</span>
                    <div class="flex_pric">
                      <button class="d-flex align-items-center nav-link click" onclick="addcart(' . $productId . ')">Add to Cart</button>
                      <div class="block_P">
                        <span class="price text">' . number_format($finalPrice, 2) . '</span>
                        <span>EGP</span>
                      </div>
                    </div>
                    <div class="input-group product-qty" style="display: none;">
                      <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">_</button>
                      <input type="text" id="quantity" name="quantity" class="form-control input-number quantity' . $productId . '" value="1">
                      <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">+</button>
                    </div>
                  </div>';
                }
              } else {
                echo '<div class="col-12 text-center py-5"><h4>No products found matching your search</h4></div>';
              }
              $stmt->close();
            } else {
              echo '<div class="col-12 text-center py-5"><h4>Please enter a search term</h4></div>';
            }
            $conn->close();
            ?>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="./js/plugins.js"></script>
  <script src="./js/script.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
    function loadCart() {
      $.ajax({
        type: "GET",
        url: "show_cart.php",
        success: function (response) {
          $('#offcanvasCart').html(response);
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function addcart(productid) {
      var quantity = $('.quantity' + productid).val();

      $.ajax({
        type: "POST",
        url: "./add_cart.php",
        data: {
          productid: productid,
          qty: quantity
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function addmoreone(id) {
      $.ajax({
        type: "POST",
        url: "./add_more_one.php",
        data: {
          id: id,
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removemoreone(id) {
      $.ajax({
        type: "POST",
        url: "./remove_more_one.php",
        data: {
          id: id,
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removecart(id) {
      $.ajax({
        type: "POST",
        url: "./remove_cart.php",
        data: {
          id: id,
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }
  </script>
</body>

</html>