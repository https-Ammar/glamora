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

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/glamora/";

function formatPrice($price)
{
  return number_format((float) $price, 2, '.', '');
}

$cart_count = 0;
$cart_items = [];
$cart_total = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
  $cart_items = $_SESSION['cart'];
  $cart_count = array_sum(array_column($cart_items, 'quantity'));

  foreach ($cart_items as $item) {
    $price = isset($item['sale_price']) ? $item['sale_price'] : $item['price'];
    $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
    $cart_total += $price * $quantity;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shopping Cart | GLAMORA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../style/main.css">
  <link rel="stylesheet" href="../style/cart.css">
  <style>
    .color-circle {
      display: inline-block;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 1px solid #ddd;
    }

    .quantity-input {
      max-width: 50px;
      text-align: center;
    }

    .product-thumbnail {
      width: 80px;
      height: 80px;
      object-fit: cover;
    }

    .empty-cart {
      min-height: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
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
  </style>
</head>

<body>
  <?php require('./header.php'); ?>

  <div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Your Shopping Cart</h2>
      <span class="text-muted"><?= $cart_count ?> <?= $cart_count === 1 ? 'item' : 'items' ?></span>
    </div>

    <?php if ($cart_count === 0): ?>
      <div class="card empty-cart">
        <div class="card-body text-center">
          <i class="bi bi-cart-x" style="font-size: 3rem; color: #6c757d;"></i>
          <h4 class="mt-3">Your cart is empty</h4>
          <p class="text-muted">Start shopping to add items to your cart</p>
          <a href="./index.php" class="btn btn-dark mt-3">Continue Shopping</a>
        </div>
      </div>
    <?php else: ?>
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th style="width: 40%">Product</th>
                      <th>Color & Size</th>
                      <th>Price</th>
                      <th>Quantity</th>
                      <th>Total</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($cart_items as $key => $item): ?>
                      <?php
                      $item_price = $item['sale_price'] ?? $item['price'];
                      $item_quantity = $item['quantity'] ?? 1;
                      $item_total = $item_price * $item_quantity;
                      ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                              class="img-thumbnail product-thumbnail me-3">
                            <div>
                              <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                              <small class="text-muted">SKU: <?= $item['id'] ?? 'N/A' ?></small>
                            </div>
                          </div>
                        </td>
                        <td>
                          <?php if (!empty($item['color_name']) && $item['color_name'] !== 'Not specified'): ?>
                            <div class="d-flex align-items-center mb-1">
                              <?php if (!empty($item['color_image'])): ?>
                                <img src="<?= htmlspecialchars($item['color_image']) ?>" class="color-image me-2">
                              <?php elseif (!empty($item['color_hex'])): ?>
                                <span class="color-circle me-2"
                                  style="background-color: <?= htmlspecialchars($item['color_hex']) ?>;"></span>
                              <?php endif; ?>
                              <span><?= htmlspecialchars($item['color_name']) ?></span>
                            </div>
                          <?php endif; ?>
                          <?php if (!empty($item['size_name']) && $item['size_name'] !== 'Not specified'): ?>
                            <div class="badge bg-secondary size-badge"><?= htmlspecialchars($item['size_name']) ?></div>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if (isset($item['sale_price']) && $item['sale_price'] < $item['price']): ?>
                            <span class="text-danger">$<?= formatPrice($item['sale_price']) ?></span>
                            <small class="text-decoration-line-through text-muted">$<?= formatPrice($item['price']) ?></small>
                          <?php else: ?>
                            $<?= formatPrice($item['price']) ?>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="input-group" style="max-width: 120px;">
                            <button class="btn btn-outline-secondary update-quantity" type="button" data-action="decrease"
                              data-key="<?= $key ?>">-</button>
                            <input type="text" class="form-control quantity-input" value="<?= $item_quantity ?>"
                              data-key="<?= $key ?>">
                            <button class="btn btn-outline-secondary update-quantity" type="button" data-action="increase"
                              data-key="<?= $key ?>">+</button>
                          </div>
                        </td>
                        <td>$<?= formatPrice($item_total) ?></td>
                        <td>
                          <button class="btn btn-sm btn-outline-danger remove-item" data-key="<?= $key ?>">
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title mb-4">Order Summary</h5>
              <div class="d-flex justify-content-between mb-2">
                <span>Subtotal</span>
                <span>$<?= formatPrice($cart_total) ?></span>
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
                <span>$<?= formatPrice($cart_total) ?></span>
              </div>
              <a href="./checkout.php" class="btn btn-dark w-100 mt-3 py-2">Proceed to Checkout</a>
              <a href="./index.php" class="btn btn-outline-dark w-100 mt-2 py-2">Continue Shopping</a>

              <div class="mt-4">
                <div class="input-group">
                  <input type="text" class="form-control" placeholder="Coupon code" id="coupon-code">
                  <button class="btn btn-outline-secondary" type="button" id="apply-coupon">Apply</button>
                </div>
                <div id="coupon-message" class="mt-2 small"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    $(document).ready(function () {
      $('.update-quantity').click(function () {
        const key = $(this).data('key');
        const action = $(this).data('action');
        const input = $(`.quantity-input[data-key="${key}"]`);
        let quantity = parseInt(input.val()) || 1;

        if (action === 'increase') {
          quantity += 1;
        } else if (action === 'decrease' && quantity > 1) {
          quantity -= 1;
        }

        input.val(quantity);
        updateCartItem(key, quantity);
      });

      $('.quantity-input').on('change input', function () {
        const key = $(this).data('key');
        let quantity = parseInt($(this).val()) || 1;
        if (quantity < 1) quantity = 1;
        $(this).val(quantity);
        updateCartItem(key, quantity);
      });

      $('.remove-item').click(function () {
        const key = $(this).data('key');
        if (confirm('Are you sure you want to remove this item from your cart?')) {
          updateCartItem(key, 0);
        }
      });

      $('#apply-coupon').click(function () {
        const couponCode = $('#coupon-code').val();
        if (!couponCode) {
          $('#coupon-message').html('<span class="text-danger">Please enter a coupon code</span>');
          return;
        }

        $.ajax({
          url: 'apply_coupon.php',
          method: 'POST',
          data: {
            csrf_token: '<?= $_SESSION['csrf_token'] ?>',
            coupon_code: couponCode
          },
          success: function (response) {
            if (response.success) {
              $('#coupon-message').html('<span class="text-success">' + response.message + '</span>');
              if (response.discount) {
                $('.fw-bold.fs-5 span:last').text('$' + response.new_total.toFixed(2));
              }
            } else {
              $('#coupon-message').html('<span class="text-danger">' + response.message + '</span>');
            }
          },
          error: function () {
            $('#coupon-message').html('<span class="text-danger">Error applying coupon. Please try again.</span>');
          }
        });
      });

      function updateCartItem(key, quantity) {
        $.ajax({
          url: 'update_cart.php',
          method: 'POST',
          dataType: 'json',
          data: {
            csrf_token: '<?= $_SESSION['csrf_token'] ?>',
            key: key,
            quantity: quantity
          },
          success: function (response) {
            if (response.success) {
              window.location.reload();
            } else {
              alert('Error: ' + (response.message || 'Failed to update cart'));
              window.location.reload();
            }
          },
          error: function (xhr, status, error) {
            alert('Error: Could not update cart. Please try again.');
            console.error(error);
          }
        });
      }
    });
  </script>
  <?php require('./footer.php'); ?>
</body>

</html>