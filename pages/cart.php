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

function formatPrice($price) {
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
  <?php require('../includes/link.php'); ?>
</head>

<body>
  <?php require('../includes/header.php'); ?>
  
  <div class="container py-5 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Your Shopping Cart</h2>
      <span class="text-muted"><?= $cart_count ?> <?= $cart_count === 1 ? 'item' : 'items' ?></span>
    </div>

    <?php if ($cart_count === 0): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bi bi-cart-x fs-1 text-secondary"></i>
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
              <div class="bg-light p-3 row text-center fw-bold d-none d-md-flex">
                <div class="col-md-3 text-start">Product</div>
                <div class="col-md-3">Color & Size</div>
                <div class="col-md-2">Price</div>
                <div class="col-md-2">Quantity</div>
                <div class="col-md-1">Total</div>
                <div class="col-md-1"></div>
              </div>

              <div class="cart-items">
                <?php foreach ($cart_items as $key => $item): ?>
                  <?php
                  $item_price = $item['sale_price'] ?? $item['price'];
                  $item_quantity = $item['quantity'] ?? 1;
                  $item_total = $item_price * $item_quantity;
                  ?>
                  <div class="border-bottom py-3 row align-items-center text-center text-md-start g-2">
                    <div class="col-12 col-md-3 d-flex align-items-center position-relative">
                      <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                        class="img-thumbnail me-3" style="width: 60px; height: 60px; object-fit: cover;">
                      <div class="flex-grow-1">
                        <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                                <small class="text-muted d-block">SKU: <?= $item['id'] ?? 'N/A' ?></small>

                        
                        <div class="d-block d-md-none w-100 mt-2">
                          <?php if (!empty($item['color_name']) || !empty($item['size_name'])): ?>
                            <div class="text-muted small mb-1">
                              <?php if (!empty($item['color_name']) && $item['color_name'] !== 'Not specified'): ?>
                                <?= htmlspecialchars($item['color_name']) ?>
                              <?php endif; ?>
                              <?php if (!empty($item['color_name']) && !empty($item['size_name']) && $item['color_name'] !== 'Not specified' && $item['size_name'] !== 'Not specified'): ?>
                                &nbsp;/&nbsp;
                              <?php endif; ?>
                              <?php if (!empty($item['size_name']) && $item['size_name'] !== 'Not specified'): ?>
                                <?= htmlspecialchars($item['size_name']) ?>
                              <?php endif; ?>
                            </div>
                          <?php endif; ?>

                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="input-group" style="width: 120px">
                              <button class="btn btn-outline-secondary update-quantity" type="button" data-action="decrease"
                                data-key="<?= $key ?>">-</button>
                              <input type="text" class="form-control text-center"
                                value="<?= $item_quantity ?>" data-key="<?= $key ?>">
                              <button class="btn btn-outline-secondary update-quantity" type="button" data-action="increase"
                                data-key="<?= $key ?>">+</button>
                            </div>

                            <div class="ms-2 fw-bold">
                              $<?= formatPrice($item_total) ?>
                            </div>
                          </div>

                          <div>
                            <button class="btn btn-sm btn-dark rounded-circle remove-item" data-key="<?= $key ?>">
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-6 col-md-3 d-none d-md-block">
                      <?php if (
                        (!empty($item['color_name']) && $item['color_name'] !== 'Not specified') ||
                        (!empty($item['size_name']) && $item['size_name'] !== 'Not specified')
                      ): ?>
                        <div>
                          <?php if (!empty($item['color_name']) && $item['color_name'] !== 'Not specified'): ?>
                            <span><?= htmlspecialchars($item['color_name']) ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item['color_name']) && $item['color_name'] !== 'Not specified' && !empty($item['size_name']) && $item['size_name'] !== 'Not specified'): ?>
                            <span class="mx-2">&</span>
                          <?php endif; ?>
                          <?php if (!empty($item['size_name']) && $item['size_name'] !== 'Not specified'): ?>
                            <span><?= htmlspecialchars($item['size_name']) ?></span>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </div>

                    <div class="col-6 col-md-2 d-none d-md-block">
                      <?php if (isset($item['sale_price']) && $item['sale_price'] < $item['price']): ?>
                        <span class="text-danger d-block">$<?= formatPrice($item['sale_price']) ?></span>
                        <small class="text-decoration-line-through text-muted d-block">
                          $<?= formatPrice($item['price']) ?>
                        </small>
                      <?php else: ?>
                        $<?= formatPrice($item['price']) ?>
                      <?php endif; ?>
                    </div>

                    <div class="col-6 col-md-2 d-none d-md-block">
                      <div class="input-group" style="max-width: 120px">
                        <button class="btn btn-outline-secondary update-quantity" type="button" data-action="decrease"
                          data-key="<?= $key ?>">-</button>
                        <input type="text" class="form-control text-center" value="<?= $item_quantity ?>"
                          data-key="<?= $key ?>">
                        <button class="btn btn-outline-secondary update-quantity" type="button" data-action="increase"
                          data-key="<?= $key ?>">+</button>
                      </div>
                    </div>

                    <div class="col-3 col-md-1 fw-bold d-none d-md-block">
                      $<?= formatPrice($item_total) ?>
                    </div>

                    <div class="col-3 col-md-1 d-none d-md-block">
                      <button class="btn btn-sm btn-outline-danger remove-item" data-key="<?= $key ?>">
                        <i class="bi bi-trash"></i>
                      </button>
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
                <span>$<?= formatPrice($cart_total) ?></span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Shipping</span>
                <span>Free</span>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span>Tax</span>
                <span>$0.00</span>
              </div>
              <hr>
              <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                <span>Total</span>
                <span>$<?= formatPrice($cart_total) ?></span>
              </div>
              <a href="./checkout.php" class="btn btn-dark w-100 py-2">Proceed to Checkout</a>
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
    $(document).ready(function() {
      $('.update-quantity').click(function() {
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

      $('.quantity-input').on('change', function() {
        const key = $(this).data('key');
        let quantity = parseInt($(this).val()) || 1;
        if (quantity < 1) quantity = 1;
        $(this).val(quantity);
        updateCartItem(key, quantity);
      });

      $('.remove-item').click(function() {
        const key = $(this).data('key');
        if (confirm('Are you sure you want to remove this item from your cart?')) {
          updateCartItem(key, 0);
        }
      });

      function updateCartItem(key, quantity) {
        $.ajax({
          url: '../cart/update_cart.php',
          method: 'POST',
          dataType: 'json',
          data: {
            csrf_token: '<?= $_SESSION['csrf_token'] ?>',
            key: key,
            quantity: quantity
          },
          success: function(response) {
            if (response.success) {
              window.location.reload();
            } else {
              alert('Error: ' + (response.message || 'Failed to update cart'));
              window.location.reload();
            }
          },
          error: function(xhr, status, error) {
            alert('Error: Could not update cart. Please try again.');
            console.error(error);
          }
        });
      }
    });
  </script>

  <style>
    .quantity-input {
      max-width: 50px;
      text-align: center;
    }
    
    .cart-item .img-thumbnail {
      width: 80px;
      height: 80px;
      object-fit: cover;
    }
    
    @media (max-width: 767.98px) {
      .cart-item{
        position: relative;
      }
      
      .remove-item {
        position: absolute;
        top: 10px;
        left: 10px;
      }
      
      .cart-item .img-thumbnail {
        width: 85px;
        height: 85px;
      }
      
      .flex-grow-1 {
        text-align: left !important;
      }
      
      .input-group {
        justify-content: start;
      }
    }
  </style>

    <style>
    @media screen and (max-width:992px) {
      img.img-thumbnail.me-3 {
        width: 85px !important;
        height: 85px !important;
      }

      .flex-grow-1 {
        text-align: left !important;
      }

      .input-group.w-50 {
        justify-content: start;
      }

      .d-flex.justify-content-between.align-items-center.mb-2 {
        display: flex !important;
        align-items: center !important;
      }

      .ms-2.fw-bold.text-nowrap {
        font-weight: 400;
      }

      small.text-muted.d-block {
        display: none !important;
      }

      .col-12.col-md-3.d-flex.align-items-start.position-relative {
        align-items: center !important;
      }

      button.btn.btn-sm.btn-outline-danger.remove-item {
        background: black;
        color: white;
        border-radius: 50%;
        top: -10px !important;
        left: -2px;
        /* width: 10px; */
        /* height: 10px; */
        /* display: block; */
        border: navajowhite;
      }
    }
  </style>
  
                <style>
                @media (max-width: 767.98px) {
                  .cart-item {
                    position: relative;
                  }

                  .remove-item {
                    position: absolute;
                    top: 0;
                    left: 0;
                  }
                }
              </style>
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
      border: navajowhite;
    }

    input.form-control.quantity-input {
      border-radius: 30px !important;
      padding: 0 !important;
      margin: 0 !important;
      height: 25px;
    }

    button.btn.btn-outline-secondary.update-quantity:hover {
      background: none !important;
      border: none;
      color: black;
    }

    .input-group {
      display: flex;
      align-items: center;
      justify-content: center;
    }
    input.form-control.text-center {
    height: 25px;
    border-radius: 30px !important;
}
  </style>
  <?php require('../includes/footer.php'); ?>
</body>
</html>