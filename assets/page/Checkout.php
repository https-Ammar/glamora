<?php
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => true,
  'use_strict_mode' => true
]);

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require('./db.php');

if (empty($_SESSION['cart'])) {
  header('Location: ./cart.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
  }

  $required = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'country', 'payment_method'];
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

  $conn->begin_transaction();

  try {
    $stmt = $conn->prepare("INSERT INTO orders (
      user_id, customer_first_name, customer_last_name, customer_email, customer_phone, 
      customer_address, customer_city, customer_country, notes, payment_method, 
      subtotal, tax, shipping, total, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $userId = $_SESSION['user_id'] ?? null;
    $status = 'pending';
    $tax = 0;
    $shipping = 0;

    $stmt->bind_param(
      "isssssssssddddss",
      $userId,
      $_POST['first_name'],
      $_POST['last_name'],
      $_POST['email'],
      $_POST['phone'],
      $_POST['address'],
      $_POST['city'],
      $_POST['country'],
      $_POST['notes'] ?? '',
      $_POST['payment_method'],
      $total,
      $tax,
      $shipping,
      $total,
      $status
    );

    $stmt->execute();
    $orderId = $conn->insert_id;

    $itemStmt = $conn->prepare("INSERT INTO order_items (
      order_id, product_id, product_name, price, 
      quantity, color, size, image
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($_SESSION['cart'] as $item) {
      $price = $item['sale_price'] ?? $item['price'];
      $color = $item['color_name'] ?? 'Not specified';
      $size = $item['size_name'] ?? 'Not specified';

      $itemStmt->bind_param(
        "iisdssss",
        $orderId,
        $item['id'],
        $item['name'],
        $price,
        $item['quantity'],
        $color,
        $size,
        $item['image']
      );

      $itemStmt->execute();
    }

    $conn->commit();
    unset($_SESSION['cart']);
    header("Location: ./thank_you.php?id=$orderId");
    exit();

  } catch (Exception $e) {
    $conn->rollback();
    die("Order failed: " . $e->getMessage());
  }
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/glamora/";

function formatPrice($price)
{
  return number_format((float) $price, 2, '.', '');
}

$total = 0;
foreach ($_SESSION['cart'] as $item) {
  $price = $item['sale_price'] ?? $item['price'];
  $total += $price * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout | GLAMORA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../style/main.css">
  <link rel="stylesheet" href="../style/checkout.css">
  <style>
    .color-circle {
      display: inline-block;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 1px solid #ddd;
    }

    .product-thumbnail {
      width: 60px;
      height: 60px;
      object-fit: cover;
    }

    .size-badge {
      font-size: 0.8rem;
      padding: 0.25rem 0.5rem;
    }

    .color-image {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      object-fit: cover;
      border: 1px solid #ddd;
    }

    .form-control:focus {
      box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
  </style>
</head>

<body>
  <?php require('./header.php'); ?>

  <div class="container py-5">
    <div class="row">
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header bg-dark text-white">
            <h4 class="mb-0">Customer Information</h4>
          </div>
          <div class="card-body">
            <form id="checkout-form" method="POST">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="first_name" class="form-label">First Name*</label>
                  <input type="text" class="form-control" id="first_name" name="first_name" required
                    value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['first_name']) : '' ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="last_name" class="form-label">Last Name*</label>
                  <input type="text" class="form-control" id="last_name" name="last_name" required
                    value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['last_name']) : '' ?>">
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="email" class="form-label">Email*</label>
                  <input type="email" class="form-control" id="email" name="email" required
                    value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['email']) : '' ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="phone" class="form-label">Phone*</label>
                  <input type="tel" class="form-control" id="phone" name="phone" required
                    value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['phone']) : '' ?>">
                </div>
              </div>

              <div class="mb-3">
                <label for="address" class="form-label">Address*</label>
                <input type="text" class="form-control" id="address" name="address" required>
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label for="country" class="form-label">Country*</label>
                  <select class="form-select" id="country" name="country" required>
                    <option value="">Select...</option>
                    <option value="Egypt">Egypt</option>
                    <option value="Saudi Arabia">Saudi Arabia</option>
                    <option value="UAE">UAE</option>
                    <option value="Kuwait">Kuwait</option>
                  </select>
                </div>
                <div class="col-md-4 mb-3">
                  <label for="city" class="form-label">City*</label>
                  <input type="text" class="form-control" id="city" name="city" required>
                </div>
                <div class="col-md-4 mb-3">
                  <label for="postal_code" class="form-label">Postal Code</label>
                  <input type="text" class="form-control" id="postal_code" name="postal_code">
                </div>
              </div>

              <div class="mb-3">
                <label for="notes" class="form-label">Order Notes (optional)</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
              </div>

              <div class="card mt-4">
                <div class="card-header bg-dark text-white">
                  <h4 class="mb-0">Payment Method</h4>
                </div>
                <div class="card-body">
                  <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="cash_on_delivery"
                      value="cash_on_delivery" checked required>
                    <label class="form-check-label" for="cash_on_delivery">
                      Cash on Delivery
                    </label>
                  </div>
                  <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="credit_card"
                      value="credit_card" required>
                    <label class="form-check-label" for="credit_card">
                      Credit Card
                    </label>
                  </div>
                  <div id="credit-card-fields" style="display: none;">
                    <div class="row">
                      <div class="col-md-12 mb-3">
                        <label for="card_number" class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="card_number" name="card_number"
                          placeholder="1234 5678 9012 3456">
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="card_expiry" class="form-label">Expiry Date</label>
                        <input type="text" class="form-control" id="card_expiry" name="card_expiry" placeholder="MM/YY">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="card_cvv" class="form-label">CVV</label>
                        <input type="text" class="form-control" id="card_cvv" name="card_cvv" placeholder="123">
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <button type="submit" name="place_order" class="btn btn-dark w-100 mt-4 py-3">Place Order</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header bg-dark text-white">
            <h4 class="mb-0">Order Summary</h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($_SESSION['cart'] as $item): ?>
                    <?php
                    $price = $item['sale_price'] ?? $item['price'];
                    $item_total = $price * $item['quantity'];
                    ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <img src="<?= htmlspecialchars($item['image']) ?>" class="product-thumbnail me-2">
                          <div>
                            <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                            <?php if (!empty($item['color_name']) && $item['color_name'] !== 'Not specified'): ?>
                              <small>
                                <?php if (!empty($item['color_image'])): ?>
                                  <img src="<?= htmlspecialchars($item['color_image']) ?>" class="color-image me-1">
                                <?php elseif (!empty($item['color_hex'])): ?>
                                  <span class="color-circle me-1"
                                    style="background-color: <?= htmlspecialchars($item['color_hex']) ?>;"></span>
                                <?php endif; ?>
                                <?= htmlspecialchars($item['color_name']) ?>
                              </small>
                            <?php endif; ?>
                            <?php if (!empty($item['size_name']) && $item['size_name'] !== 'Not specified'): ?>
                              <div class="badge bg-secondary size-badge mt-1"><?= htmlspecialchars($item['size_name']) ?>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                      <td><?= $item['quantity'] ?></td>
                      <td>$<?= formatPrice($item_total) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <hr>

            <div class="d-flex justify-content-between mb-2">
              <span>Subtotal</span>
              <span>$<?= formatPrice($total) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Shipping</span>
              <span>Free</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Tax</span>
              <span>$0.00</span>
            </div>

            <hr>

            <div class="d-flex justify-content-between fw-bold fs-5">
              <span>Total</span>
              <span>$<?= formatPrice($total) ?></span>
            </div>

            <a href="./cart.php" class="btn btn-outline-dark w-100 mt-3">Edit Cart</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    $(document).ready(function () {
      $('input[name="payment_method"]').change(function () {
        if ($(this).val() === 'credit_card') {
          $('#credit-card-fields').show();
          $('#card_number, #card_expiry, #card_cvv').prop('required', true);
        } else {
          $('#credit-card-fields').hide();
          $('#card_number, #card_expiry, #card_cvv').prop('required', false);
        }
      });

      $('#checkout-form').submit(function (e) {
        let isValid = true;

        $(this).find('[required]').each(function () {
          if (!$(this).val()) {
            $(this).addClass('is-invalid');
            isValid = false;
          } else {
            $(this).removeClass('is-invalid');
          }
        });

        const email = $('#email').val();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          $('#email').addClass('is-invalid');
          isValid = false;
        }

        if (!isValid) {
          e.preventDefault();
          alert('Please fill all required fields correctly');
        }
      });
    });
  </script>

  <?php require('./footer.php'); ?>
</body>

</html>