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

require('../config/db.php');

if (empty($_SESSION['cart'])) {
  header('Location: ./profile.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
  header('Content-Type: application/json');
  $coupon_code = trim($_POST['coupon_code']);

  $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expires_at > NOW() OR expires_at IS NULL)");
  $stmt->bind_param("s", $coupon_code);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $coupon = $result->fetch_assoc();

    if ($coupon['used_count'] >= $coupon['max_uses']) {
      echo json_encode(['error' => "This coupon has reached its maximum usage limit"]);
      exit();
    }

    if (isset($_SESSION['user_id'])) {
      $user_id = $_SESSION['user_id'];
      $user_usage_stmt = $conn->prepare("SELECT COUNT(*) as user_usage FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
      $user_usage_stmt->bind_param("ii", $coupon['id'], $user_id);
      $user_usage_stmt->execute();
      $user_usage_result = $user_usage_stmt->get_result();
      $user_usage_data = $user_usage_result->fetch_assoc();

      if ($user_usage_data['user_usage'] >= $coupon['max_uses_per_user']) {
        echo json_encode(['error' => "You have already used this coupon the maximum number of times."]);
        exit();
      }
    }

    $_SESSION['applied_coupon'] = $coupon['code'];
    echo json_encode(['success' => true, 'coupon_code' => $coupon['code']]);
    exit();

  } else {
    echo json_encode(['error' => "Invalid or expired coupon code"]);
    exit();
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_coupon'])) {
  header('Content-Type: application/json');
  unset($_SESSION['applied_coupon']);
  echo json_encode(['success' => true]);
  exit();
}

$coupon = null;
if (isset($_SESSION['applied_coupon'])) {
  $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expires_at > NOW() OR expires_at IS NULL)");
  $stmt->bind_param("s", $_SESSION['applied_coupon']);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $coupon = $result->fetch_assoc();
  } else {
    unset($_SESSION['applied_coupon']);
  }
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

  $required = ['full_name', 'email', 'phone', 'address', 'city'];
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
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expires_at > NOW() OR expires_at IS NULL)");
    $stmt->bind_param("s", $coupon_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $coupon = $result->fetch_assoc();
    $stmt->close();

    if ($coupon) {
      if ($coupon['used_count'] >= $coupon['max_uses']) {
        unset($_SESSION['applied_coupon']);
        header("Location: checkout.php?error=coupon_limit");
        exit();
      }

      if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $user_usage_stmt = $conn->prepare("SELECT COUNT(*) as user_usage FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
        $user_usage_stmt->bind_param("ii", $coupon['id'], $user_id);
        $user_usage_stmt->execute();
        $user_usage_result = $user_usage_stmt->get_result();
        $user_usage_data = $user_usage_result->fetch_assoc();

        if ($user_usage_data['user_usage'] >= $coupon['max_uses_per_user']) {
          unset($_SESSION['applied_coupon']);
          header("Location: checkout.php?error=coupon_user_limit");
          exit();
        }
      }

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
    }
  }

  $final_total = $total - $discount_amount;
  $conn->begin_transaction();

  try {
    $stmt = $conn->prepare("INSERT INTO orders (
            user_id, customer_name, customer_email, phone, city,
            address, orderstate, numberofproducts, finaltotalprice, discount_value,
            coupon_id, coupon_code
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $numberOfProducts = count($_SESSION['cart']);
    $orderstate = 'pending';

    $stmt->bind_param(
      "issssssidiss",
      $userId,
      $_POST['full_name'],
      $_POST['email'],
      $_POST['phone'],
      $_POST['city'],
      $_POST['address'],
      $orderstate,
      $numberOfProducts,
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

    if ($coupon) {
      $coupon_usage_stmt = $conn->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id) VALUES (?, ?, ?)");
      $coupon_usage_stmt->bind_param("iii", $coupon_id, $userId, $orderId);
      if (!$coupon_usage_stmt->execute()) {
        throw new Exception("Failed to log coupon usage: " . $coupon_usage_stmt->error);
      }
      $coupon_usage_stmt->close();

      $update_coupon_stmt = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
      $update_coupon_stmt->bind_param("i", $coupon_id);
      if (!$update_coupon_stmt->execute()) {
        throw new Exception("Failed to update coupon usage count: " . $update_coupon_stmt->error);
      }
      $update_coupon_stmt->close();
    }

    if (isset($_SESSION['user_id'])) {
      $updateStmt = $conn->prepare("UPDATE users SET 
                phone = ?, 
                address = ?, 
                city = ?
                WHERE id = ?");

      $updateStmt->bind_param(
        "sssi",
        $_POST['phone'],
        $_POST['address'],
        $_POST['city'],
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

    header("Location: ../orders/order_confirmation.php?id=$orderId");
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
    <?php require('../includes/link.php'); ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body>

    <?php require('../includes/header.php'); ?>

    <div class="container-fluid">
        <div class="container mt-3 d-md-none">
            <div class="accordion summary-accordion" id="orderAccordion">
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed px-0" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseOrder">
                            <div class="w-100 d-flex justify-content-between align-items-center">
                                <div class="fw-bold d-flex align-items-center">
                                    Order summary
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold" style="font-size:18px;">£ <?= formatPrice($final_total) ?>
                                    </span>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseOrder" class="accordion-collapse collapse" data-bs-parent="#orderAccordion">
                        <div class="accordion-body px-0">
                            <div class="card-body">
                                <?php foreach ($_SESSION['cart'] as $item): ?>
                                      <div class="product-box">
                                          <div class="product-image-wrapper">
                                              <span class="product-qty"><?= $item['quantity'] ?></span>
                                              <div class="product-image"
                                                  style="background-image: url('<?= htmlspecialchars($item['image']) ?>');">
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
                                          <div class="ms-auto fw-bold">
                                              <?= formatPrice(($item['sale_price'] ?? $item['price']) * $item['quantity']) ?>
                                              <sub>EGP</sub>
                                          </div>
                                      </div>
                                <?php endforeach; ?>

                                <div class="mt-3 mb-3">
                                    <?php if (isset($coupon) && $coupon): ?>
                                          <div class="coupon-success">
                                              <span>Coupon Code: <?= htmlspecialchars($coupon['code']) ?></span>
                                              <button type="button" id="remove-coupon-mobile"
                                                  class="btn btn-sm btn-outline-danger">Remove</button>
                                          </div>
                                    <?php else: ?>
                                          <form id="coupon-form-mobile" class="coupon-form input-group mb-3">
                                              <input type="text" id="coupon_code_mobile" name="coupon_code" class="form-control"
                                                  placeholder="Coupon Code" required>
                                              <button type="submit" id="apply-coupon-mobile"
                                                  class="btn btn-outline-secondary">Apply</button>
                                          </form>
                                          <div id="coupon-message-mobile" class="coupon-error"></div>
                                    <?php endif; ?>
                                </div>

                                <div class="summary-item">
                                    <span>Subtotal</span>
                                    <span> <?= formatPrice($total) ?> <sub>EGP</sub></span>
                                </div>
                                <div class="summary-item">
                                    <span>Discount</span>
                                    <span class="text-success"> - <?= formatPrice($discount_amount) ?> <sub>EGP</sub></span>
                                </div>
                                <hr>
                                <div class="summary-item summary-total">
                                    <span>Total</span>
                                    <span> <?= formatPrice($final_total) ?> <sub>EGP</sub></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row min-vh-100 d-flex">
            <div class="col-md-6 form-section">
                <form id="checkout-form" method="POST">
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
                        <select class="form-select" id="country" name="country" required>
                            <option value="">Select...</option>
                            <option value="Egypt"
                                <?= isset($userData['country']) && $userData['country'] == 'Egypt' ? 'selected' : '' ?>>Egypt
                            </option>
                            <option value="Saudi Arabia"
                                <?= isset($userData['country']) && $userData['country'] == 'Saudi Arabia' ? 'selected' : '' ?>>Saudi
                                Arabia</option>
                            <option value="UAE"
                                <?= isset($userData['country']) && $userData['country'] == 'UAE' ? 'selected' : '' ?>>
                                UAE</option>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <input type="text" class="form-control" id="full_name" name="full_name" required
                                value="<?= htmlspecialchars($userData['name'] ?? '') ?>" placeholder="Full Name" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="text" class="form-control" id="address" name="address" required
                            value="<?= htmlspecialchars($userData['address'] ?? '') ?>" placeholder="Address">
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <input type="text" class="form-control" id="city" name="city" required
                                value="<?= htmlspecialchars($userData['city'] ?? '') ?>" placeholder="City">
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" placeholder="Postal code (optional)" id="postal_code"
                                name="postal_code" value="<?= htmlspecialchars($userData['postal_code'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <input type="tel" class="form-control" id="phone" name="phone" required
                            value="<?= htmlspecialchars($userData['phone'] ?? '') ?>"
                            placeholder="Mobile Number (e.g: 0123 xxx xxxx)">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="saveInfo">
                        <label class="form-check-label" for="saveInfo">Save this information for next time</label>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-dark w-100 mt-3 py-2">checkout now</button>
                </form>
            </div>

            <div class="col-md-5 p-0 d-none d-md-block">
                <div class="order-summary h-100 pt-5">
                    <div class="card-body">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                              <div class="product-box">
                                  <div class="product-image-wrapper">
                                      <span class="product-qty"><?= $item['quantity'] ?></span>
                                      <div class="product-image"
                                          style="background-image: url('<?= htmlspecialchars($item['image']) ?>');">
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
                                  <div class="ms-auto fw-bold">
                                      <?= formatPrice(($item['sale_price'] ?? $item['price']) * $item['quantity']) ?>
                                      <sub>EGP</sub>
                                  </div>
                              </div>
                        <?php endforeach; ?>

                        <div class="mt-3 mb-3">
                            <?php if (isset($coupon) && $coupon): ?>
                                  <div class="coupon-success">
                                      <span>Coupon Code: <?= htmlspecialchars($coupon['code']) ?></span>
                                      <button type="button" id="remove-coupon"
                                          class="btn btn-sm btn-outline-danger">Remove</button>
                                  </div>
                            <?php else: ?>
                                  <form id="coupon-form" class="coupon-form input-group mb-3">
                                      <input type="text" id="coupon_code" name="coupon_code" class="form-control"
                                          placeholder="Coupon Code" required>
                                      <button type="submit" id="apply-coupon"
                                          class="btn btn-outline-secondary">Apply</button>
                                  </form>
                                  <div id="coupon-message" class="coupon-error"></div>
                            <?php endif; ?>
                        </div>

                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span> <?= formatPrice($total) ?> <sub>EGP</sub></span>
                        </div>
                        <div class="summary-item">
                            <span>Discount</span>
                            <span class="text-success">- <?= formatPrice($discount_amount) ?> <sub>EGP</sub></span>
                        </div>
                        <hr>
                        <div class="summary-item summary-total">
                            <span>Total</span>
                            <span> <?= formatPrice($final_total) ?> <sub>EGP</sub></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require('../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>






<script>
  $(document).ready(function () {
    function handleCouponForm(formId, inputId, buttonId, messageId) {
      $(formId).on('submit', function (e) {
        e.preventDefault();
        const couponCode = $(inputId).val().trim();

        if (!couponCode) {
          $(messageId).text('Please enter a coupon code').show();
          return;
        }

        $.ajax({
          url: window.location.href,
          type: 'POST',
          data: {
            apply_coupon: true,
            coupon_code: couponCode
          },
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              location.reload();
            } else if (response.error) {
              $(messageId).text(response.error).show();
              setTimeout(function () {
                $(messageId).fadeOut();
              }, 4000);
            }
          },
          error: function () {
            $(messageId).text('An error occurred. Please try again.').show();
            setTimeout(function () {
              $(messageId).fadeOut();
            }, 4000);
          }
        });
      });
    }

    function handleCouponRemoval(buttonId) {
      $(buttonId).on('click', function () {
        $.ajax({
          url: window.location.href,
          type: 'POST',
          data: {
            remove_coupon: true
          },
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              location.reload();
            }
          },
          error: function () {
            alert('An error occurred. Please try again.');
          }
        });
      });
    }

    handleCouponForm('#coupon-form', '#coupon_code', '#apply-coupon', '#coupon-message');
    handleCouponForm('#coupon-form-mobile', '#coupon_code_mobile', '#apply-coupon-mobile', '#coupon-message-mobile');

    handleCouponRemoval('#remove-coupon');
    handleCouponRemoval('#remove-coupon-mobile');

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

    setTimeout(function () {
      const errorElements = document.querySelectorAll('.coupon-error');
      errorElements.forEach(function (el) {
        el.style.display = 'none';
      });
    }, 4000);
  });
</script>
<style>
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
    background-color: white;
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

  .coupon-error {
    color: red;
    margin-top: 5px;
    animation: fadeOut 2s forwards;
    animation-delay: 2s;
  }

  @keyframes fadeOut {
    to {
      opacity: 0;
      height: 0;
      margin: 0;
      padding: 0;
    }
  }

  .summary-accordion .accordion-button:not(.collapsed),
  .summary-accordion .accordion-button {
    background-color: transparent !important;
    color: inherit !important;
  }

  .summary-accordion .accordion-button::after {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23212529'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    transition: transform 0.2s ease-in-out;
    margin-left: 0.5rem;
    position: static;
  }

  .summary-accordion .accordion-button {
    padding: 0.75rem 0;
    display: flex;
    align-items: center;
  }

  .summary-accordion .accordion-button:focus {
    box-shadow: none;
  }

  .summary-accordion .accordion-body {
    padding: 1rem 0;
  }

  .summary-accordion {
    display: none;
  }

  form.coupon-form.input-group.mb-3 {
    gap: 10px;
  }

  form.coupon-form.input-group.mb-3 input {
    border-radius: 10px !important;
  }

  button.btn.btn-outline-secondary {
    border-radius: 10px !important;
    background: #000000;
    color: white;
    border: navajowhite;
  }

  @media (max-width: 991.98px) {
    .summary-accordion {
      display: block;
    }

    button.accordion-button.px-0.collapsed {
      background: #f6f6f6 !important;
    }

    .container.mt-3 {
      padding: 0;
      margin: 0 !important;
      padding: 10px;
      background: #f6f6f6;
      border-top: 1px solid #e8e8e8;
      border-bottom: 1px solid #e8e8e8;
    }

    button.accordion-button.px-0.collapsed {
      padding: 0;
    }
  }

  input#offers,
  input#saveInfo {
    padding: 0;
  }

  button.btn.btn-dark.w-100.mt-3.py-2 {
    padding: 15px !important;
  }

  .form-control,
  .form-select {
    border-radius: 6px;
    padding: 25px;
  }

  select#country {
    padding: 15px;
  }

  @media screen and (max-width:992px) {
    .order-summary.h-100.pt-5 {
      display: none;
    }

    .col-md-5.form-section.border-end {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    button.btn.btn-dark.w-100.mt-3.py-2 {
      padding: 15px !important;
    }

    .form-control,
    .form-select {
      border-radius: 6px;
      padding: 25px;
    }

    select#country {
      padding: 15px;
    }

    .col-md-5.p-0 {
      display: none;
    }
  }
</style>