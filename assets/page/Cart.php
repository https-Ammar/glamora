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
    $i = $row['product_count'] ?? 0;
  }
  $stmt->close();
} else {
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

    .cart-item {
      display: flex;
      padding: 20px 0;
      border-bottom: 1px solid #eee;
    }

    .product-figure {
      width: 120px;
      height: 120px;
      margin-right: 20px;
    }

    .viwe_img {
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
    }

    .cart-item-content {
      flex: 1;
    }

    ._flex {
      display: flex;
      justify-content: space-between;
    }

    .item-quantity {
      margin: 15px 0;
    }

    ._flex_int {
      display: inline-flex;
      align-items: center;
      margin-left: 10px;
    }

    ._flex_int button {
      width: 30px;
      height: 30px;
      border: 1px solid #ddd;
      background: #fff;
      cursor: pointer;
    }

    ._flex_int input {
      width: 40px;
      height: 30px;
      text-align: center;
      border: 1px solid #ddd;
      margin: 0 5px;
    }

    .cart-item_remove-btn__yNwhA {
      display: flex;
      align-items: center;
      color: #666;
      cursor: pointer;
    }

    .cart-item_remove-btn__yNwhA svg {
      margin-right: 5px;
    }

    ._main_grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }

    .Summary {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      height: fit-content;
    }

    .cart-priceItem {
      display: flex;
      justify-content: space-between;
      margin-bottom: 15px;
    }

    .btn-danger {
      width: 100%;
      padding: 12px;
      background: #000;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      margin-top: 20px;
    }

    .empty-cart {
      padding: 50px 0;
      text-align: center;
    }

    .empty-cart h3 {
      font-size: 24px;
      margin-bottom: 10px;
    }

    .empty-cart p {
      color: #666;
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
          <a href="index.php">Back To Home</a>
        </div>
        <h1>Cart</h1>
      </div>

      <div class="_main_grid">
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
                    <a href="index.php" class="btn btn-danger"
                      style="width: auto; display: inline-block; margin-top: 20px;">Continue Shopping</a>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <div class="loading-skeleton checkout-address">
                <?php
                $stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image, p.colors FROM cart c JOIN products p ON c.productid = p.id WHERE c.userid = ?");
                $stmt->bind_param("s", $userid);
                $stmt->execute();
                $getallcartproducts = $stmt->get_result();

                while ($getcartproducts = $getallcartproducts->fetch_assoc()):
                  $price = $getcartproducts['price'] ?? 0;
                  $qty = intval($getcartproducts['qty']);
                  $subtotal = floatval($price) * $qty;
                  $finalproducttotal += $subtotal;
                  $image_path = !empty($getcartproducts['image']) ?
                    (strpos($getcartproducts['image'], 'http') === 0 ?
                      $getcartproducts['image'] :
                      'http://localhost:8888/glamora/dashboard/' . ltrim($getcartproducts['image'], './')) :
                    'http://localhost:8888/glamora/assets/images/default.jpg';

                  $size = $getcartproducts['size'] ?? null;
                  $color = $getcartproducts['color'] ?? null;
                  $colorHex = null;

                  if (!empty($getcartproducts['colors'])) {
                    $colors = json_decode($getcartproducts['colors'], true);
                    foreach ($colors as $c) {
                      if (isset($c['name']) && $c['name'] === $color && isset($c['hex'])) {
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
                          <h4 class="product-title"><?php echo htmlspecialchars($getcartproducts['name'] ?? ''); ?></h4>
                          <div class="product-price">EGP <?php echo number_format($price, 2); ?></div>
                        </div>
                        <div class="product-column product-actions price-div">
                          <div class="product-price">EGP <?php echo number_format($subtotal, 2); ?></div>
                        </div>
                      </div>

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
                            <input type="text" name="quantity" value="<?php echo $qty; ?>" class="input-number text-center"
                              readonly>
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
                <?php endwhile;
                $stmt->close();
                ?>
              </div>
            <?php endif; ?>
          </div>
        </section>

        <?php if ($i > 0): ?>
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
        <?php endif; ?>
      </div>
    </main>

    <?php require('footer.php'); ?>
  </section>

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
      if (confirm('Are you sure you want to remove this item from your cart?')) {
        $.post("removecart.php", { id: id }, function () {
          location.reload();
        });
      }
    }

    window.onload = function () {
      document.getElementById('lod_file').style.display = 'block';
      document.getElementById('loading').style.display = 'none';
    };
  </script>
</body>

</html>