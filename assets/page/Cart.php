<?php
require('db.php');

$i = 0;
$finalproducttotal = 0.0;

if (isset($_COOKIE['userid'])) {
  $userid = $_COOKIE['userid'];

  $stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM cart WHERE userid = ?");
  $stmt->bind_param("s", $userid);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result) {
    $row = $result->fetch_assoc();
    $i = $row['product_count'];
  }

  $stmt->close();
} else {
  $result = $conn->query("SELECT id FROM users ORDER BY id DESC LIMIT 1");

  if ($result && $result->num_rows > 0) {
    $last_id = $result->fetch_assoc()['id'];
    $newid = $last_id + 1;
  } else {
    $newid = 1;
  }

  $userid = $newid;
  setcookie('userid', $userid, time() + (10 * 365 * 24 * 60 * 60), "/");

  $stmt = $conn->prepare("INSERT INTO users (id, name, email, password) VALUES (?, NULL, NULL, NULL)");
  $stmt->bind_param("i", $userid);
  $stmt->execute();
  $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GLAMORA</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../style/main.css">
</head>

<body>
  <?php require('./loding.php'); ?>
  <?php require('./header.php'); ?>

  <section id="lod_file">
    <main class="layout">
      <div class="checkout-heading-header">
        <div class="back-btn undefined">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g id="Glyph">
              <g id="->">
                <path
                  d="M9.19602 13.8345C9.44079 14.0793 9.8373 14.0804 10.0834 13.837C10.3315 13.5917 10.3326 13.1913 10.0859 12.9446L7.42614 10.2848L14.5852 10.2848C14.9422 10.2848 15.2315 9.99544 15.2315 9.63849C15.2315 9.28155 14.9422 8.99219 14.5852 8.99219L7.42614 8.99219L10.0854 6.33718C10.3326 6.09042 10.3322 5.68981 10.0845 5.44354C9.83798 5.19838 9.43956 5.19894 9.19371 5.44478L5 9.6385L9.19602 13.8345Z"
                  fill="black" fill-opacity="0.6"></path>
              </g>
            </g>
          </svg>
          <a href="#"> Back To Bag</a>
        </div>
        <h1>Cart</h1>
      </div>

      <div class="_main_grid">
        <section class="data_">
          <div class="Customer">
            <div class="Customer_titel">
              <h2 class="stepHeader-title optimizedCheckout-headingPrimary">
                Bag
              </h2>
            </div>

            <?php if ($i == 0) { ?>
              <div class="row">
                <div class="col text-center">
                  <div class="empty-cart">

                    <h3>Dein Warenkorb ist leer</h3>
                    <p class="_1mmswk9g _1mmswk9f _1fragem1y _1fragemkk _1fragemnk _1fragemih">There are special products
                      in GLAMORA that you can buy.</p>
                  </div>
                </div>
              </div>
            <?php } else { ?>
              <div class="loading-skeleton checkout-address">
                <tbody>
                  <?php
                  $stmt = $conn->prepare("SELECT * FROM cart WHERE userid = ?");
                  $stmt->bind_param("s", $userid);
                  $stmt->execute();
                  $getallcartproducts = $stmt->get_result();

                  while ($getcartproducts = $getallcartproducts->fetch_assoc()) {
                    $cartproduct = $getcartproducts['prouductid'];

                    $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                    $productStmt->bind_param("i", $cartproduct);
                    $productStmt->execute();
                    $selectproduct = $productStmt->get_result();
                    $fetchproduct = $selectproduct->fetch_assoc();

                    $getfirstbyfirst = $fetchproduct['total_final_price'] * $getcartproducts['qty'];
                    $finalproducttotal += $getfirstbyfirst;

                    echo '
              <div>
              <div class="product cart-item" data-test="cart-item">
                <figure class="product-column product-figure">
                  <div class="card-image ol-lg-3 viwe_img" style="background-image: url(\'./dashboard/dashboard_shop-main/' . htmlspecialchars($fetchproduct['img']) . '\');"></div>
                </figure>
                <div class="cart-item-content">
                  <div class="_flex">
                    <div class="product-column product-body title-div">
                      <h4 class="product-title optimizedCheckout-contentPrimary" data-test="cart-item-product-title">' . htmlspecialchars($fetchproduct['name']) . '  </h4>
                    </div>
                    <div class="product-column product-actions price-div">
                      <div class="product-price optimizedCheckout-contentPrimary" data-test="cart-item-product-price">
                        EGP ' . htmlspecialchars($getfirstbyfirst) . ' </div>
                    </div>
                  </div>
                  <div class="item-quantity"><span class="item-quantity_span" >Quantity <div class="_flex_int">
                                  <button onclick="removemoreone(' . $getcartproducts['id'] . ')" type="button" class="quantity-left-minus btn btn-light btn-number" data-type="minus">-</button>
                                  <input type="text" id="quantity" name="quantity" class="form-control input-number text-center" value="' . $getcartproducts['qty'] . '">
                                  <button onclick="addmoreone(' . $getcartproducts['id'] . ')" type="button" class="quantity-right-plus btn btn-light btn-number" data-type="plus">+</button>
                                </div>
                  </span>
                  </div>
                  <a href="#" onclick="removecart(' . $getcartproducts['id'] . ')">
                    <div class="cart-item_remove-btn__yNwhA"><div class="CSVG">
                    <svg width="12" height="12" viewBox="0 0 10 11" fill="#000" xmlns="http://www.w3.org/2000/svg"><path d="M0.757359 1.24264L9.24264 9.72792M9.24264 1.24264L0.757359 9.72792" stroke="black" stroke-width="1.5"></path></svg></div><button class="item_remove" role="removeBtn">Remove</button></div>
                  </a>
                </div>
              </div>
            </div>';
                    $productStmt->close();
                  }
                  $stmt->close();
                  ?>
                </tbody>
              </div>

            <?php } ?>
          </div>
        </section>

        <section class="Summary">
          <div class="Customer">
            <div class="Customer_titel">
              <h2 class="stepHeader-title optimizedCheckout-headingPrimary">
                Summary
              </h2>
            </div>
            <div class="loading-skeleton">
              <section class="cart-section optimizedCheckout-orderSummary-cartSection">
                <div data-test="cart-subtotal">
                  <div aria-live="polite" class="cart-priceItem "> <span class="cart-priceItem-label"><span
                        data-test="cart-price-label">Subtotal </span></span><span class="cart-priceItem-value"><span
                        data-test="cart-price-value">EGP <?php echo htmlspecialchars($finalproducttotal); ?>
                      </span></span></div>
                </div>
                <div data-test="cart-shipping" class="cart-shipping">
                  <div aria-live="polite" class="cart-priceItem ">
                    <span class="cart-priceItem-label"><span data-test="cart-price-label">Count </span></span><span
                      class="cart-priceItem-value"><span data-test="cart-price-value">(
                        <?php echo htmlspecialchars($i); ?> )</span></span>
                  </div>
                </div>
              </section>
              <div aria-live="polite" class="cart-priceItem ">
                <span class="cart-priceItem-label"><span data-test="cart-price-label">Total to Pay </span></span><span
                  class="cart-priceItem-value"><span data-test="cart-price-value">EGP
                    <span><?php echo htmlspecialchars($finalproducttotal); ?></span></span>
              </div>
              <button onclick="window.location.href='Checkout.php';" class="btn btn-danger m-t-xs" type="button">Proceed
                to Checkout</button>
            </div>
          </div>
        </section>
      </div>
    </main>
    <?php require('footer.php') ?>
  </section>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://unpkg.com/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function addmoreone(id) {
      $.ajax({
        type: "POST",
        url: "addmoreone.php",
        data: {
          id: id
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.log("AJAX Error:", status, error);
        }
      });
    }

    function removemoreone(id) {
      $.ajax({
        type: "POST",
        url: "removemoreone.php",
        data: {
          id: id
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.log("AJAX Error:", status, error);
        }
      });
    }

    function removecart(id) {
      $.ajax({
        type: "POST",
        url: "removecart.php",
        data: {
          id: id
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.log("AJAX Error:", status, error);
        }
      });
    }

    function loadCart() {
      location.reload();
    }
  </script>

  <script>
    let lod_file = document.getElementById('lod_file');
    let loading = document.getElementById('loading');

    window.onload = function () {
      lod_file.style.display = 'block'
      loading.style.display = 'none'
    }
  </script>
</body>

</html>