<?php
require('./db.php');
session_start();

$finalproducttotal = 0.0;
$i = 0;
$couponDiscount = 0.0;
$couponApplied = false;
$userData = ['name' => '', 'phone' => '', 'address' => '', 'city' => ''];
$userid = null;

// Session or Cookie-Based User Identification
if (isset($_SESSION['userId'])) {
  $userid = $_SESSION['userId'];
  if (isset($_COOKIE['userid']) && $_COOKIE['userid'] != $userid) {
    $stmt = $conn->prepare("UPDATE cart SET userid = ? WHERE userid = ?");
    $stmt->bind_param("ss", $userid, $_COOKIE['userid']);
    $stmt->execute();
    $stmt->close();
    setcookie('userid', $userid, time() + (10 * 365 * 24 * 60 * 60), "/");
  }
  $stmt = $conn->prepare("SELECT name, phone, address, city FROM users WHERE id = ?");
  $stmt->bind_param("i", $userid);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $userData = $result->fetch_assoc();
  }
  $stmt->close();
} elseif (isset($_COOKIE['userid'])) {
  $userid = $_COOKIE['userid'];
} else {
  $result = $conn->query("SELECT id FROM users ORDER BY id DESC LIMIT 1");
  $newid = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['id'] + 1 : 1;
  $userid = $newid;
  setcookie('userid', $userid, time() + (10 * 365 * 24 * 60 * 60), "/");
  $stmt = $conn->prepare("INSERT INTO users(id, name, email, password) VALUES (?, '', '', '')");
  $stmt->bind_param("i", $userid);
  $stmt->execute();
  $stmt->close();
}

// Count Products in Cart
$stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM cart WHERE userid = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
  $i = $result->fetch_assoc()['product_count'];
}
$stmt->close();

// Calculate Cart Total
if ($i > 0) {
  $stmt = $conn->prepare("SELECT * FROM cart WHERE userid = ?");
  $stmt->bind_param("s", $userid);
  $stmt->execute();
  $getallcartproducts = $stmt->get_result();
  while ($getcartproducts = $getallcartproducts->fetch_assoc()) {
    $productId = $getcartproducts['productid'];
    $productStmt = $conn->prepare("SELECT price, sale_price FROM products WHERE id = ?");
    $productStmt->bind_param("i", $productId);
    $productStmt->execute();
    $productResult = $productStmt->get_result();
    if ($fetchproduct = $productResult->fetch_assoc()) {
      $unitPrice = $fetchproduct['sale_price'] ?: $fetchproduct['price'];
      $total = $unitPrice * $getcartproducts['qty'];
      $finalproducttotal += $total;
    }
    $productStmt->close();
  }
  $stmt->close();
}

// Apply Coupon Code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coupon_code'])) {
  $code = trim($_POST['coupon_code']);
  $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND expires_at > NOW() AND max_uses > 0");
  $stmt->bind_param("s", $code);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $coupon = $result->fetch_assoc();
    if ($coupon['discount_type'] === 'percentage') {
      $couponDiscount = $finalproducttotal * ($coupon['discount_value'] / 100);
    } else {
      $couponDiscount = $coupon['discount_value'];
    }
    if ($couponDiscount > $finalproducttotal) {
      $couponDiscount = $finalproducttotal;
    }
    $finalproducttotal -= $couponDiscount;
    $couponApplied = true;

    // Log applied discount to dashboard (discount_logs table assumed)
    $logStmt = $conn->prepare("INSERT INTO discount_logs (user_id, coupon_code, discount_amount, final_price, created_at) VALUES (?, ?, ?, ?, NOW())");
    $logStmt->bind_param("isdd", $userid, $code, $couponDiscount, $finalproducttotal);
    $logStmt->execute();
    $logStmt->close();
  } else {
    echo "<p style='color:red;'>❌ Invalid or expired coupon.</p>";
  }
  $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Checkout</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body {
      font-family: 'Cairo', sans-serif;
    }

    .cart-item {
      display: flex;
      align-items: center;
      margin: 10px 0;
    }

    .cart-item img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      margin-right: 10px;
    }

    .checkout-form {
      max-width: 600px;
      margin: 20px auto;
    }

    .checkout-form input {
      width: 100%;
      margin: 5px 0;
      padding: 10px;
    }

    .checkout-form button {
      width: 100%;
      padding: 10px;
      background: black;
      color: white;
      border: none;
      border-radius: 5px;
    }

    .coupon-form {
      margin: 20px 0;
    }

    .coupon-form input {
      padding: 10px;
      width: 70%;
    }

    .coupon-form button {
      padding: 10px;
      background: #333;
      color: white;
      border: none;
    }
  </style>
</head>

<body>

  <form method="POST" class="coupon-form">
    <input type="text" name="coupon_code" placeholder="Enter discount code" required />
    <button type="submit">Apply</button>
  </form>

  <p>Price before discount: <?php echo number_format($finalproducttotal + $couponDiscount, 2); ?> EGP</p>
  <p>Discount: <?php echo number_format($couponDiscount, 2); ?> EGP</p>
  <p>Price after discount: <?php echo number_format($finalproducttotal, 2); ?> EGP</p>

  <?php if ($i == 0): ?>
    <p>Your cart is empty.</p>
  <?php else: ?>
    <div>
      <?php
      $stmt = $conn->prepare("SELECT * FROM cart WHERE userid = ?");
      $stmt->bind_param("s", $userid);
      $stmt->execute();
      $getallcartproducts = $stmt->get_result();
      while ($getcartproducts = $getallcartproducts->fetch_assoc()) {
        $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $productStmt->bind_param("i", $getcartproducts['productid']);
        $productStmt->execute();
        $fetchproduct = $productStmt->get_result()->fetch_assoc();
        if ($fetchproduct):
          $productImage = htmlspecialchars($fetchproduct['img'] ?? 'default.jpg');
          $productName = htmlspecialchars($fetchproduct['name'] ?? 'Unnamed');
          $quantity = (int) ($getcartproducts['qty'] ?? 1);
          $price = (float) ($fetchproduct['sale_price'] ?: $fetchproduct['price']);
          $total = $price * $quantity;
          ?>
          <div class="cart-item">
            <img src="./dashboard/dashboard_shop-main/<?php echo $productImage; ?>" alt="<?php echo $productName; ?>" />
            <div>
              <p><?php echo $productName; ?></p>
              <p>Quantity: <?php echo $quantity; ?></p>
              <p>Price: EGP <?php echo number_format($total, 2); ?></p>
              <button type="button" onclick="addmoreone(<?php echo $getcartproducts['productid']; ?>)">+</button>
              <button type="button" onclick="removemoreone(<?php echo $getcartproducts['productid']; ?>)">-</button>
              <button type="button" onclick="removecart(<?php echo $getcartproducts['productid']; ?>)">Remove</button>
            </div>
          </div>
          <?php
        endif;
        $productStmt->close();
      }
      $stmt->close();
      ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="finalcheckout.php" class="checkout-form" id="checkout-form">
    <input type="text" name="cleintname" placeholder="First Name"
      value="<?php echo htmlspecialchars($userData['name'] ?? ''); ?>" required />
    <input type="text" name="lastName" placeholder="Last Name" required />
    <input type="text" name="address" placeholder="Address"
      value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>" required />
    <input type="text" name="city" placeholder="City" value="<?php echo htmlspecialchars($userData['city'] ?? ''); ?>"
      required />
    <input type="tel" name="phoneone" placeholder="First Phone Number"
      value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" required />
    <input type="tel" name="phonetwo" placeholder="Other Phone Number" required />
    <button type="submit" id="checkout-customer-continue">Continue</button>
  </form>

  <script>
    function addmoreone(id) {
      $.post("addmoreone.php", { id: id }, function () {
        location.reload();
      }).fail(function (xhr) {
        console.log("Error:", xhr.responseText);
      });
    }
    function removemoreone(id) {
      $.post("removemoreone.php", { id: id }, function () {
        location.reload();
      }).fail(function (xhr) {
        console.log("Error:", xhr.responseText);
      });
    }
    function removecart(id) {
      $.post("removecart.php", { id: id }, function () {
        location.reload();
      }).fail(function (xhr) {
        console.log("Error:", xhr.responseText);
      });
    }
    const form = document.getElementById("checkout-form");
    const continueButton = document.getElementById("checkout-customer-continue");
    form.addEventListener("input", () => {
      const allFilled = [...form.querySelectorAll("input")].every(input => input.value.trim() !== "");
      continueButton.disabled = !allFilled;
    });
  </script>

</body>

</html>