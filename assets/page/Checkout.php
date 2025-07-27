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
  header('Location: ./profile.php');
  exit();
}

// جلب بيانات المستخدم إذا كان مسجلاً
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

  $conn->begin_transaction();

  try {
    $stmt = $conn->prepare("INSERT INTO orders (
      user_id, customer_first_name, customer_last_name, name, phoneone, 
      city, address, orderstate, finaltotalprice, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $customerName = $_POST['full_name'];
    $status = 'inprogress';

    $stmt->bind_param(
      "isssssssd",
      $userId,
      $_POST['full_name'],
      $_POST['full_name'],
      $customerName,
      $_POST['phone'],
      $_POST['city'],
      $_POST['address'],
      $status,
      $total
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
    header("Location: ./profile.php?id=$orderId");
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
?>

<!DOCTYPE html>
<html lang="ar">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الدفع | GLAMORA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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

    body {
      font-family: Arial, sans-serif;
      background-color: #f8f9fa;
    }

    .card-header {
      background-color: #343a40;
      color: white;
    }

    .btn-dark {
      background-color: #343a40;
      border-color: #343a40;
    }

    .btn-dark:hover {
      background-color: #23272b;
      border-color: #1d2124;
    }
  </style>
</head>

<body>


  <div class="container py-5">
    <div class="row">
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header">
            <h4 class="mb-0">معلومات العميل</h4>
          </div>
          <div class="card-body">
            <form id="checkout-form" method="POST">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

              <div class="mb-3">
                <label for="full_name" class="form-label">الاسم الكامل*</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required
                  value="<?= !empty($userData['name']) ? htmlspecialchars($userData['name']) :
                    (!empty($userData['first_name']) ? htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) : '') ?>">
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="email" class="form-label">البريد الإلكتروني*</label>
                  <input type="email" class="form-control" id="email" name="email" required
                    value="<?= !empty($userData['email']) ? htmlspecialchars($userData['email']) : '' ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="phone" class="form-label">رقم الهاتف*</label>
                  <input type="tel" class="form-control" id="phone" name="phone" required
                    value="<?= !empty($userData['phone']) ? htmlspecialchars($userData['phone']) : '' ?>">
                </div>
              </div>

              <div class="mb-3">
                <label for="address" class="form-label">العنوان*</label>
                <input type="text" class="form-control" id="address" name="address" required
                  value="<?= !empty($userData['address']) ? htmlspecialchars($userData['address']) : '' ?>">
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label for="country" class="form-label">الدولة*</label>
                  <select class="form-select" id="country" name="country" required>
                    <option value="">اختر...</option>
                    <option value="مصر" <?= (!empty($userData['country']) && $userData['country'] == 'مصر') ? 'selected' : '' ?>>مصر</option>
                    <option value="السعودية" <?= (!empty($userData['country']) && $userData['country'] == 'السعودية') ? 'selected' : '' ?>>السعودية</option>
                    <option value="الإمارات" <?= (!empty($userData['country']) && $userData['country'] == 'الإمارات') ? 'selected' : '' ?>>الإمارات</option>
                    <option value="الكويت" <?= (!empty($userData['country']) && $userData['country'] == 'الكويت') ? 'selected' : '' ?>>الكويت</option>
                  </select>
                </div>
                <div class="col-md-4 mb-3">
                  <label for="city" class="form-label">المدينة*</label>
                  <input type="text" class="form-control" id="city" name="city" required
                    value="<?= !empty($userData['city']) ? htmlspecialchars($userData['city']) : '' ?>">
                </div>
                <div class="col-md-4 mb-3">
                  <label for="postal_code" class="form-label">الرمز البريدي</label>
                  <input type="text" class="form-control" id="postal_code" name="postal_code"
                    value="<?= !empty($userData['postal_code']) ? htmlspecialchars($userData['postal_code']) : '' ?>">
                </div>
              </div>

              <button type="submit" name="place_order" class="btn btn-dark w-100 mt-4 py-3">تأكيد الطلب</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header">
            <h4 class="mb-0">ملخص الطلب</h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>المجموع</th>
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
                      <td><?= formatPrice($item_total) ?> جنيه</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <hr>

            <div class="d-flex justify-content-between mb-2">
              <span>المجموع الفرعي</span>
              <span><?= formatPrice($total) ?> جنيه</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>الشحن</span>
              <span>مجاني</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>الضرائب</span>
              <span>0.00 جنيه</span>
            </div>

            <hr>

            <div class="d-flex justify-content-between fw-bold fs-5">
              <span>الإجمالي</span>
              <span><?= formatPrice($total) ?> جنيه</span>
            </div>

            <a href="./cart.php" class="btn btn-outline-dark w-100 mt-3">تعديل السلة</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    $(document).ready(function () {
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
          alert('الرجاء ملء جميع الحقول المطلوبة بشكل صحيح');
        }
      });
    });
  </script>


</body>

</html>