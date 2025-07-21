<?php
require('db.php');

$i = 0;
$finalproducttotal = 0.0;

if (isset($_COOKIE['userid'])) {
  $userid = $_COOKIE['userid'];

  // احصل على عدد المنتجات في السلة
  $stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM cart WHERE userid = ?");
  $stmt->bind_param("s", $userid);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result) {
    $row = $result->fetch_assoc();
    $i = $row['product_count'] ?? 0;
  }
  $stmt->close();
} else {
  // لو مفيش كويكي، اعمل يوزر جديد
  $result = $conn->query("SELECT id FROM users ORDER BY id DESC LIMIT 1");
  $newid = ($result && $result->num_rows > 0) ? ($result->fetch_assoc()['id'] + 1) : 1;
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GLAMORA - Cart</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../style/main.css">
  <style>
    .product-options-display {
      margin: 10px 0;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .option-badge {
      background: #f5f5f5;
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 14px;
      display: flex;
      align-items: center;
    }

    .color-badge {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      margin-right: 5px;
      border: 1px solid #ddd;
    }
  </style>
</head>

<body>
  <?php require('./loding.php'); ?>
  <?php require('./header.php'); ?>

  <section id="lod_file">
    <main class="layout">
      <div class="checkout-heading-header">
        <div class="back-btn undefined">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="..." fill="black" fill-opacity="0.6"></path>
          </svg>
          <a href="#">Back To Bag</a>
        </div>
        <h1>Cart</h1>
      </div>

      <div class="_main_grid">
        <!-- CART SECTION -->
        <section class="data_">
          <div class="Customer">
            <div class="Customer_titel">
              <h2 class="stepHeader-title optimizedCheckout-headingPrimary">Bag</h2>
            </div>

            <?php if ($i == 0): ?>
              <div class="row">
                <div class="col text-center">
                  <div class="empty-cart">
                    <h3>Your cart is empty</h3>
                    <p>There are special products in GLAMORA that you can buy.</p>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <div class="loading-skeleton checkout-address">
                <tbody>
                  <?php
                  $stmt = $conn->prepare("SELECT * FROM cart WHERE userid = ?");
                  $stmt->bind_param("s", $userid);
                  $stmt->execute();
                  $getallcartproducts = $stmt->get_result();

                  while ($getcartproducts = $getallcartproducts->fetch_assoc()):
                    $cartproduct = $getcartproducts['productid'] ?? null;
                    if ($cartproduct !== null):
                      $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                      $productStmt->bind_param("i", $cartproduct);
                      $productStmt->execute();
                      $selectproduct = $productStmt->get_result();
                      $fetchproduct = $selectproduct->fetch_assoc();

                      if ($fetchproduct):
                        $price = $fetchproduct['total_final_price'] ?? $fetchproduct['price'] ?? 0;
                        $qty = intval($getcartproducts['qty']);
                        $subtotal = floatval($price) * $qty;
                        $finalproducttotal += $subtotal;
                        $image_path = 'http://localhost:8888/glamora/dashboard/uploads/products/' . basename($fetchproduct['img'] ?? 'default.jpg');

                        // استخراج خيارات الألوان والمقاسات من قاعدة البيانات
                        $size = $getcartproducts['size'] ?? null;
                        $color = $getcartproducts['color'] ?? null;
                        $colorHex = null;

                        // إذا كان المنتج يحتوي على ألوان مخزنة كـ JSON
                        if (!empty($fetchproduct['colors'])) {
                          $colors = json_decode($fetchproduct['colors'], true);
                          foreach ($colors as $c) {
                            if ($c['name'] === $color) {
                              $colorHex = $c['hex'];
                              break;
                            }
                          }
                        }
                        ?>

                        <div class="product cart-item">
                          <figure class="product-column product-figure">
                            <div class="card-image viwe_img" style="background-image: url('<?php echo $image_path; ?>');"></div>
                          </figure>
                          <div class="cart-item-content">
                            <div class="_flex">
                              <div class="product-column product-body title-div">
                                <h4 class="product-title"><?php echo htmlspecialchars($fetchproduct['name'] ?? ''); ?></h4>
                              </div>
                              <div class="product-column product-actions price-div">
                                <div class="product-price">EGP <?php echo number_format($subtotal, 2); ?></div>
                              </div>
                            </div>

                            <!-- عرض خيارات الألوان والمقاسات المختارة -->
                            <div class="product-options-display">
                              <?php if ($size): ?>
                                <div class="option-badge">
                                  Size: <?php echo htmlspecialchars($size); ?>
                                </div>
                              <?php endif; ?>

                              <?php if ($color): ?>
                                <div class="option-badge">
                                  <?php if ($colorHex): ?>
                                    <div class="color-badge" style="background-color: <?php echo $colorHex; ?>"></div>
                                  <?php endif; ?>
                                  Color: <?php echo htmlspecialchars($color); ?>
                                </div>
                              <?php endif; ?>
                            </div>

                            <div class="item-quantity">
                              <span class="item-quantity_span">Quantity
                                <div class="_flex_int">
                                  <button onclick="removemoreone(<?php echo $getcartproducts['id']; ?>)">-</button>
                                  <input type="text" name="quantity" value="<?php echo $qty; ?>"
                                    class="input-number text-center">
                                  <button onclick="addmoreone(<?php echo $getcartproducts['id']; ?>)">+</button>
                                </div>
                              </span>
                            </div>
                            <a href="#" onclick="removecart(<?php echo $getcartproducts['id']; ?>)">
                              <div class="cart-item_remove-btn__yNwhA">
                                <svg width="12" height="12" viewBox="0 0 10 11" fill="#000">
                                  <path d="M0.757359 1.24264L9.24264 9.72792M9.24264 1.24264L0.757359 9.72792" stroke="black"
                                    stroke-width="1.5"></path>
                                </svg>
                                <button class="item_remove" role="removeBtn">Remove</button>
                              </div>
                            </a>
                          </div>
                        </div>

                        <?php
                      endif;
                      $productStmt->close();
                    endif;
                  endwhile;
                  $stmt->close();
                  ?>
                </tbody>
              </div>
            <?php endif; ?>
          </div>
        </section>

        <!-- SUMMARY SECTION -->
        <section class="Summary">
          <div class="Customer">
            <div class="Customer_titel">
              <h2 class="stepHeader-title optimizedCheckout-headingPrimary">Summary</h2>
            </div>
            <div class="loading-skeleton">
              <section class="cart-section optimizedCheckout-orderSummary-cartSection">
                <div data-test="cart-subtotal">
                  <div class="cart-priceItem">
                    <span class="cart-priceItem-label">Subtotal</span>
                    <span class="cart-priceItem-value">EGP <?php echo number_format($finalproducttotal, 2); ?></span>
                  </div>
                </div>
                <div class="cart-shipping">
                  <div class="cart-priceItem">
                    <span class="cart-priceItem-label">Count</span>
                    <span class="cart-priceItem-value">(<?php echo $i; ?>)</span>
                  </div>
                </div>
              </section>
              <div class="cart-priceItem">
                <span class="cart-priceItem-label">Total to Pay</span>
                <span class="cart-priceItem-value">EGP <?php echo number_format($finalproducttotal, 2); ?></span>
              </div>
              <button onclick="window.location.href='Checkout.php';" class="btn btn-danger">Proceed to Checkout</button>
            </div>
          </div>
        </section>
      </div>
    </main>

    <?php require('footer.php'); ?>
  </section>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    function addmoreone(id) {
      $.post("addmoreone.php", { id: id }, function () {
        location.reload();
      });
    }

    function removemoreone(id) {
      $.post("removemoreone.php", { id: id }, function () {
        location.reload();
      });
    }

    function removecart(id) {
      $.post("removecart.php", { id: id }, function () {
        location.reload();
      });
    }

    window.onload = function () {
      document.getElementById('lod_file').style.display = 'block';
      document.getElementById('loading').style.display = 'none';
    };
  </script>
</body>

</html>