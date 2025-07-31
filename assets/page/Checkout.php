<?php
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => true,
  'use_strict_mode' => true
]);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require('./db.php');

if (empty($_SESSION['cart'])) {
  header('Location: ./profile.php');
  exit();
}

$coupon = null;
$coupon_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
  $coupon_code = trim($_POST['coupon_code']);

  $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND (expires_at > NOW() OR expires_at IS NULL)");
  $stmt->bind_param("s", $coupon_code);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $coupon = $result->fetch_assoc();

    $usage_stmt = $conn->prepare("SELECT COUNT(*) as usage_count FROM orders WHERE coupon_code = ?");
    $usage_stmt->bind_param("s", $coupon_code);
    $usage_stmt->execute();
    $usage_result = $usage_stmt->get_result();
    $usage_data = $usage_result->fetch_assoc();

    if ($usage_data['usage_count'] < $coupon['max_uses']) {
      $_SESSION['applied_coupon'] = $coupon['code'];
    } else {
      $coupon_error = "This coupon has reached its maximum usage limit";
      $coupon = null;
    }
    $usage_stmt->close();
  } else {
    $coupon_error = "Invalid or expired coupon code";
  }
  $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_coupon'])) {
  unset($_SESSION['applied_coupon']);
  $coupon = null;
}

if (isset($_SESSION['applied_coupon'])) {
  $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ?");
  $stmt->bind_param("s", $_SESSION['applied_coupon']);
  $stmt->execute();
  $result = $stmt->get_result();
  $coupon = $result->fetch_assoc();
  $stmt->close();
}

$userData = [];
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
  }
  $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
  }

  $required = ['full_name', 'email', 'phone', 'address', 'city', 'country'];
  foreach ($required as $field) {
    if (empty($_POST[$field])) {
      die('Please fill all required fields');
    }
  }

  if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    die('Invalid email address');
  }

  $total = 0;
  foreach ($_SESSION['cart'] as $item) {
    $price = $item['sale_price'] ?? $item['price'];
    $total += $price * $item['quantity'];
  }

  $discount_amount = 0;
  $coupon_id = null;
  $coupon_code = null;

  if (isset($_SESSION['applied_coupon'])) {
    $coupon_code = $_SESSION['applied_coupon'];
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ?");
    $stmt->bind_param("s", $coupon_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $coupon = $result->fetch_assoc();
    $stmt->close();

    if ($coupon) {
      $usage_stmt = $conn->prepare("SELECT COUNT(*) as usage_count FROM orders WHERE coupon_code = ?");
      $usage_stmt->bind_param("s", $coupon_code);
      $usage_stmt->execute();
      $usage_result = $usage_stmt->get_result();
      $usage_data = $usage_result->fetch_assoc();

      if ($usage_data['usage_count'] < $coupon['max_uses']) {
        $coupon_id = $coupon['id'];
        if ($coupon['discount_type'] === 'percentage') {
          $discount_amount = $total * ($coupon['discount_value'] / 100);
          if (isset($coupon['maximum_discount']) && $coupon['maximum_discount'] > 0) {
            $discount_amount = min($discount_amount, $coupon['maximum_discount']);
          }
        } else {
          $discount_amount = min($coupon['discount_value'], $total);
        }
      } else {
        unset($_SESSION['applied_coupon']);
        header("Location: checkout.php?error=coupon_limit");
        exit();
      }
      $usage_stmt->close();
    }
  }

  $final_total = $total - $discount_amount;

  $conn->begin_transaction();

  try {
    $stmt = $conn->prepare("INSERT INTO orders (
      user_id, customer_first_name, customer_last_name, name, phoneone,
      city, address, orderstate, finaltotalprice, discount_value,
      coupon_id, coupon_code, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $customerName = $_POST['full_name'];
    $status = 'pending';

    $stmt->bind_param(
      "isssssssdiss",
      $userId,
      $_POST['full_name'],
      $_POST['full_name'],
      $customerName,
      $_POST['phone'],
      $_POST['city'],
      $_POST['address'],
      $status,
      $final_total,
      $discount_amount,
      $coupon_id,
      $coupon_code
    );

    if (!$stmt->execute()) {
      throw new Exception("Failed to create order: " . $stmt->error);
    }

    $orderId = $conn->insert_id;

    $itemStmt = $conn->prepare("INSERT INTO order_items (
      order_id, product_id, qty, price, total_price, color, size
    ) VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($_SESSION['cart'] as $item) {
      $price = $item['sale_price'] ?? $item['price'];
      $totalPrice = $price * $item['quantity'];
      $color = $item['color_name'] ?? 'Not specified';
      $size = $item['size_name'] ?? 'Not specified';

      $itemStmt->bind_param(
        "iiiddss",
        $orderId,
        $item['id'],
        $item['quantity'],
        $price,
        $totalPrice,
        $color,
        $size
      );

      if (!$itemStmt->execute()) {
        throw new Exception("Failed to add order items: " . $itemStmt->error);
      }
    }

    if (isset($_SESSION['user_id'])) {
      $updateStmt = $conn->prepare("UPDATE users SET 
        phone = ?, 
        address = ?, 
        city = ?, 
        country = ? 
        WHERE id = ?");

      $updateStmt->bind_param(
        "ssssi",
        $_POST['phone'],
        $_POST['address'],
        $_POST['city'],
        $_POST['country'],
        $_SESSION['user_id']
      );

      if (!$updateStmt->execute()) {
        throw new Exception("Failed to update user info: " . $updateStmt->error);
      }
      $updateStmt->close();
    }

    $conn->commit();

    unset($_SESSION['cart']);
    unset($_SESSION['applied_coupon']);

    header("Location: ./order_confirmation.php?id=$orderId");
    exit();

  } catch (Exception $e) {
    $conn->rollback();
    die("Order failed: " . $e->getMessage());
  }
}

function formatPrice($price)
{
  return number_format((float) $price, 2, '.', '');
}

$total = 0;
foreach ($_SESSION['cart'] as $item) {
  $price = $item['sale_price'] ?? $item['price'];
  $total += $price * $item['quantity'];
}

$discount_amount = 0;
$final_total = $total;

if ($coupon) {
  if ($coupon['discount_type'] === 'percentage') {
    $discount_amount = $total * ($coupon['discount_value'] / 100);
    if (isset($coupon['maximum_discount']) && $coupon['maximum_discount'] > 0) {
      $discount_amount = min($discount_amount, $coupon['maximum_discount']);
    }
  } else {
    $discount_amount = min($coupon['discount_value'], $total);
  }

  $final_total = $total - $discount_amount;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #fff;
      font-family: system-ui, sans-serif;
    }

    .form-section {
      padding: 30px;
    }

    .order-summary {
      padding: 30px;
      background: #f5f5f5;
      height: 100%;
    }

    .product-box {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .product-image-wrapper {
      position: relative;
      width: 60px;
      height: 60px;
      flex-shrink: 0;
    }

    .product-image {
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
      border-radius: 8px;
      background-color: red;
    }

    .product-qty {
      position: absolute;
      top: -8px;
      right: -8px;
      background-color: black;
      color: white;
      font-size: x-small;
      font-weight: bold;
      padding: 2px 6px;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2;
    }

    .product-info {
      margin-left: 15px;
    }

    .input-group .form-control {
      border-radius: 0;
    }

    .summary-item {
      display: flex;
      justify-content: space-between;
      margin: 8px 0;
    }

    .summary-total {
      font-weight: bold;
      font-size: 20px;
    }

    .form-control,
    .form-select {
      border-radius: 6px;
      padding: 12px;
    }

    .form-check {
      margin-top: 10px;
    }

    .order-summary .form-control {
      border-radius: 6px 0 0 6px;
    }

    .order-summary .btn {
      border-radius: 0 6px 6px 0;
    }

    @media (max-width: 768px) {
      .form-section {
        padding: 20px;
      }

      .order-summary {
        margin-top: 30px;
      }
    }

    .row.min-vh-100.d-flex {
      justify-content: center;
    }
  </style>
</head>

<body>
  <div class="container-fluid">
    <div class="row min-vh-100 d-flex">
      <div class="col-md-5 form-section border-end">
        <form id="checkout-form" method="POST">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="country" class="form-label">Country*</label>
              <select class="form-select" id="country" name="country" required>
                <option value="">Select...</option>
                <option value="Egypt" <?= isset($userData['country']) && $userData['country'] == 'Egypt' ? 'selected' : '' ?>>Egypt</option>
                <option value="Saudi Arabia" <?= isset($userData['country']) && $userData['country'] == 'Saudi Arabia' ? 'selected' : '' ?>>Saudi Arabia</option>
                <option value="UAE" <?= isset($userData['country']) && $userData['country'] == 'UAE' ? 'selected' : '' ?>>
                  UAE</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="city" class="form-label">City*</label>
              <input type="text" class="form-control" id="city" name="city" required
                value="<?= htmlspecialchars($userData['city'] ?? '') ?>">
            </div>
          </div>

          <button type="submit" name="place_order" class="btn btn-checkout mt-3">Confirm Order</button>

          <h5>Contact</h5>
          <input type="email" class="form-control mb-3" id="email" name="email"
            value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required />
          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" checked id="offers">
            <label class="form-check-label" for="offers">Email me with news and offers</label>
          </div>

          <h5>Delivery</h5>
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <div class="mb-3">
            <select class="form-select">
              <option selected>Egypt</option>
            </select>
          </div>

          <div class="row mb-3">
            <div class="col">
              <input type="text" class="form-control" id="full_name" name="full_name" required
                value="<?= htmlspecialchars($userData['name'] ?? '') ?>" placeholder="Full Name" />
            </div>
            <div class="col">
              <input type="text" class="form-control" placeholder="Last name">
            </div>
          </div>
          <div class="mb-3">
            <input type="tel" class="form-control" id="phone" name="phone" required
              value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" placeholder="Phone">
          </div>
          <div class="mb-3">
            <input type="text" class="form-control" id="address" name="address" required
              value="<?= htmlspecialchars($userData['address'] ?? '') ?>" placeholder="Address">
          </div>
          <div class="row mb-3">
            <div class="col">
              <input type="text" class="form-control" placeholder="City">
            </div>
            <div class="col">
              <select class="form-select">
                <option selected>Sohag</option>
              </select>
            </div>
            <div class="col">
              <input type="text" class="form-control" placeholder="Postal code (optional)" id="postal_code"
                name="postal_code" value="<?= htmlspecialchars($userData['postal_code'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <input type="text" class="form-control" placeholder="Mobile Number (e.g: 0123 xxx xxxx)">
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="saveInfo">
            <label class="form-check-label" for="saveInfo">Save this information for next time</label>
          </div>
        </form>
      </div>

      <div class="col-md-5 p-0">
        <div class="order-summary h-100">
          <div class="card-body">
            <?php foreach ($_SESSION['cart'] as $item): ?>
              <div class="product-box">
                <div class="product-image-wrapper">
                  <span class="product-qty"><?= $item['quantity'] ?></span>
                  <div class="product-image" style="background-image: url('<?= htmlspecialchars($item['image']) ?>');">
                  </div>
                </div>
                <div class="product-info">
                  <p class="m-0"><?= htmlspecialchars($item['name']) ?></p>
                  <span>
                    <?php if (!empty($item['color_name'])): ?>
                      <?= htmlspecialchars($item['color_name']) ?>
                    <?php endif; ?>
                    <?php if (!empty($item['size_name'])): ?>
                      / <?= htmlspecialchars($item['size_name']) ?>
                    <?php endif; ?>
                  </span>
                </div>
                <div class="ms-auto fw-bold">EGP
                  <?= formatPrice(($item['sale_price'] ?? $item['price']) * $item['quantity']) ?>
                </div>
              </div>
            <?php endforeach; ?>

            <div class="mt-3 mb-3">
              <?php if (isset($coupon) && $coupon): ?>
                <div class="coupon-success">
                  <span>Coupon Code: <?= htmlspecialchars($coupon['code']) ?></span>
                  <form method="POST" style="display:inline;">
                    <button type="submit" name="remove_coupon" class="btn btn-sm btn-outline-danger">Remove</button>
                  </form>
                </div>
              <?php else: ?>
                <form method="POST" class="coupon-form input-group mb-3">
                  <input type="text" name="coupon_code" class="form-control" placeholder="Coupon Code" required>
                  <button type="submit" name="apply_coupon" class="btn btn-outline-secondary">Apply</button>
                </form>
                <?php if ($coupon_error): ?>
                  <div class="coupon-error"><?= $coupon_error ?></div>
                <?php endif; ?>
              <?php endif; ?>
            </div>

            <div class="summary-item">
              <span>Subtotal</span>
              <span>EGP <?= formatPrice($total) ?></span>
            </div>
            <div class="summary-item">
              <span>Discount</span>
              <span>-EGP <?= formatPrice($discount_amount) ?></span>
            </div>
            <hr>
            <div class="summary-item summary-total">
              <span>Total</span>
              <span>EGP <?= formatPrice($final_total) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      document.getElementById('checkout-form').addEventListener('submit', function (e) {
        let isValid = true;

        this.querySelectorAll('[required]').forEach(function (field) {
          if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
          } else {
            field.classList.remove('is-invalid');
          }
        });

        const emailField = document.getElementById('email');
        if (emailField.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
          emailField.classList.add('is-invalid');
          isValid = false;
        }

        if (!isValid) {
          e.preventDefault();
          alert('Please fill all required fields correctly');
        }
      });
    </script>
</body>

</html>