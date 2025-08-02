<?php
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => true,
  'use_strict_mode' => true
]);

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require('../config/db.php');

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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/cart.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
    rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"
    integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

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

    button.btn.btn-outline-secondary.update-quantity {
      background: black;
      color: white;
      width: 25px;
      height: 25px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50% !important;
      border: navajowhite;
    }

    input.form-control.quantity-input {
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50% !important;
      width: 3px;
      padding: 0 !important;
      margin: 0 !important;
    }

    .input-group {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    * {
      margin: 0;
      padding: 0;
    }

    .product {
      display: flex;
      justify-content: space-between;
      padding: 20px;
    }

    .img_product {
      width: 75px;
      height: 75px;
      border-radius: 10px;
      background: #f5f5f5;
      position: relative;
      background-position: center center;
      background-size: cover;
      border: 1px solid #dfd7d7;
    }

    button.btn.btn-sm.btn-outline-danger.remove-item {
      border: navajowhite;
      background: #212529;
      border-radius: 50%;
      color: white;
    }

    button.btn.btn-outline-secondary.update-quantity {
      color: black;
      background: white;
    }

    input.form-control.quantity-input {
      height: 20px;
      border-radius: 30px !important;
      width: 20px;
    }

    .delet_product {
      position: absolute;
      width: 20px;
      height: 20px;
      background: black;
      border-radius: 50%;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      top: -10px;
      left: -10px;
    }

    .flex {
      display: flex;
      gap: 10px;
    }


    .product {
      border-bottom: 1px solid #00000014;
    }

    .price h2 {
      margin: 0;
      padding: 0;
      font-size: larger;
    }

    p.p-0.m-0 {
      margin: 5px 0 !important;
    }




    .price h2 {
      margin-top: 5px;
    }

    a.btn.btn-dark.w-100.mt-3.py-2 {
      padding: 10px !important;
      margin-top: 3vh !important;
    }

    a.btn.btn-outline-dark.w-100.mt-2.py-2 {
      padding: 10px !important;
    }

    @media screen and (max-width:992px) {
      .img_product {
        width: 90px;
        height: 90px;
      }

      .col-lg-4.mt-4.mt-lg-0 {
        padding: 0;
      }

      .col-lg-8 {
        padding: 0;
      }

      a.btn.btn-dark.w-100.mt-3.py-2 {
        padding: 15px !important;
        margin-top: 3vh !important;
      }

      a.btn.btn-outline-dark.w-100.mt-2.py-2 {
        padding: 15px !important;
      }

    }

    button.btn.btn-sm.btn-outline-danger.remove-item {
      border: navajowhite;
      background: #212529;
      border-radius: 50%;
      color: white;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>

<body>
  <?php require('../includes/header.php'); ?>

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
                <?php foreach ($cart_items as $key => $item): ?>
                  <?php
                  $item_price = $item['sale_price'] ?? $item['price'];
                  $item_quantity = $item['quantity'] ?? 1;
                  $item_total = $item_price * $item_quantity;
                  ?>
                  <div class="product">
                    <div class="flex">
                      <div class="img_product" style="background-image: url(<?= htmlspecialchars($item['image']) ?>"
                        alt="<?= htmlspecialchars($item['name']) ?>);">
                        <div class="delet_product">
                          <button class="btn btn-sm btn-outline-danger remove-item" data-key="<?= $key ?>">
                            <i class="bi bi-x"></i>
                          </button>
                        </div>
                      </div>
                      <div class="text_product">
                        <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                        <p class="p-0 m-0">
                          <?php if (isset($item['sale_price']) && $item['sale_price'] < $item['price']): ?>
                            <span class="text-danger">$<?= formatPrice($item['sale_price']) ?></span>
                            <small class="text-decoration-line-through text-muted">$<?= formatPrice($item['price']) ?></small>
                          <?php else: ?>
                            <?= formatPrice($item['price']) ?>
                          <?php endif; ?>

                          <sub>egp</sub>
                        </p>
                        <?php if (
                          (!empty($item['color_name']) && $item['color_name'] !== 'Not specified') ||
                          (!empty($item['size_name']) && $item['size_name'] !== 'Not specified')
                        ): ?>
                          <div class="d-flex align-items-center mb-1">
                            <?php if (!empty($item['color_name']) && $item['color_name'] !== 'Not specified'): ?>
                              <?php if (!empty($item['color_image'])): ?>
                              <?php elseif (!empty($item['color_hex'])): ?>
                                <span class="color-circle me-2"
                                  style="background-color: <?= htmlspecialchars($item['color_hex']) ?>;"></span>
                              <?php endif; ?>
                              <span><?= htmlspecialchars($item['color_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['color_name']) && $item['color_name'] !== 'Not specified' && !empty($item['size_name']) && $item['size_name'] !== 'Not specified'): ?>
                              <span class="mx-1">&</span>
                            <?php endif; ?>
                            <?php if (!empty($item['size_name']) && $item['size_name'] !== 'Not specified'): ?>
                              <span><?= htmlspecialchars($item['size_name']) ?></span>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                        <div class="conub">
                          <div class="flex">
                            <div class="input-group" style="max-width: 120px;">
                              <button class="btn btn-outline-secondary update-quantity" type="button" data-action="decrease"
                                data-key="<?= $key ?>">-</button>
                              <input type="text" class="form-control quantity-input" value="<?= $item_quantity ?>"
                                data-key="<?= $key ?>">
                              <button class="btn btn-outline-secondary update-quantity" type="button" data-action="increase"
                                data-key="<?= $key ?>">+</button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="price">
                      <h2><?= formatPrice($item_total) ?> <sub>egp</sub></h2>
                      <small class="text-muted">SKU: <?= $item['id'] ?? 'N/A' ?></small>
                    </div>
                  </div>
                <?php endforeach; ?>
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
                <span><?= formatPrice($cart_total) ?> <sub>egp</sub></span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Shipping</span>
                <span>Free</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Tax</span>
                <span>0.00 <sub>egp</sub></span>
              </div>
              <hr>
              <div class="d-flex justify-content-between fw-bold fs-5">
                <span>Total</span>
                <span><?= formatPrice($cart_total) ?> <sub>egp</sub></span>
              </div>
              <a href="./checkout.php" class="btn btn-dark w-100 mt-3 py-2">Proceed to Checkout</a>
              <a href="./index.php" class="btn btn-outline-dark w-100 mt-2 py-2">Continue Shopping</a>
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
  <?php require('../includes/footer.php'); ?>
</body>

</html>