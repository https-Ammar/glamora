<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);
require('../config/db.php');

$imagePath = '../admin/';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmtOrders = $conn->prepare("
    SELECT 
        orders.*, 
        coupons.code AS coupon_code, 
        coupons.discount_type, 
        coupons.discount_value
    FROM 
        orders 
    LEFT JOIN 
        coupons ON orders.coupon_id = coupons.id 
    WHERE 
        orders.user_id = ? 
    ORDER BY 
        orders.created_at DESC
");
$stmtOrders->bind_param("i", $userId);
$stmtOrders->execute();
$orders = $stmtOrders->get_result();
$stmtOrders->close();

$stmtOrderItems = $conn->prepare("
    SELECT 
        order_items.*,
        products.name AS product_name,
        products.image AS product_image,
        products.price AS product_price
    FROM 
        order_items 
    JOIN 
        products ON order_items.product_id = products.id 
    WHERE 
        order_items.order_id = ?
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require('../includes/link.php'); ?>

    <link rel="stylesheet" href="/admin/assets/css/main.css">

    <style>
        span.badge.bg-dark {
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: small;
            font-weight: normal;
        }

        h2.text-center.mb-4 {
            display: flex;
            /* align-items: center; */
            justify-content: center;
            gap: 10px;
        }

        span.order-status.bg-secondary {
            background: none !important;
        }

        a#ordersTab {
            background: no-repeat;
            color: black;
        }

        div#orderModal13 {
            background: white !important;
        }


        .modal.fade.show {
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999999999999999;
        }

        .modal-header {
            border: navajowhite;
        }

        .modal-content {
            border: navajowhite;
        }

        span.product-qty {
            background: red;
            width: 20px !important;
            height: 20px !important;
            /* padding: 0px 10px; */
            border-radius: 50%;
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            top: -10px;
            left: -10px;
            background: black;
            color: white;
            font-size: x-small;
        }

        .modal-dialog.modal-lg {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-body {
            border-bottom: 1px solid #e9e9e9;
        }

        .position-relative.me-3.rounded.border {
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center center;
        }
    </style>
    <style>
        body {
            background-color: #0d1b2a;
            color: white;
            font-family: 'Arial', sans-serif;
        }

        .profile-card {
            background: url('https://via.placeholder.com/1000x250') no-repeat center center/cover;
            border-radius: 20px;
            padding: 40px 0;
            position: relative;
            color: white;
            overflow: hidden;
        }

        .profile-content {
            position: relative;
            z-index: 2;
        }

        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
        }

        .social-icons .btn {
            border-radius: 50%;
            margin-right: 8px;
        }

        .modal-header {
            border-bottom: none;
        }

        .modal-footer {
            border-top: none;
        }
    </style>
</head>

<body>
    <?php require('../includes/header.php'); ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Profile</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            .profile-card {
                background: url('https://via.placeholder.com/1000x250') no-repeat center center/cover;
                border-radius: 20px;
                padding: 40px 0;
                position: relative;
                color: white;
                overflow: hidden;
            }

            .profile-content.d-flex.align-items-center.justify-content-between {
                color: black !important;
            }

            a.btn.btn-outline-light.btn-sm {
                color: black;
            }

            .profile-content {
                position: relative;
                z-index: 2;
            }

            .profile-img {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                border: 3px solid black;
                object-fit: cover;
            }

            .social-icons .btn {
                border-radius: 50%;
                margin-right: 8px;
            }

            .modal-header {
                border-bottom: none;
            }

            .modal-footer {
                border-top: none;
            }
        </style>
    </head>

    <body>

        <div class="container py-5">
            <h4 class="mb-4">Profile</h4>

                                <h2 class="text-center mb-4">Orders <span class="badge bg-dark"><?= $orders->num_rows ?></span></h2>


            <div class="profile-card">
                <div class="profile-overlay"></div>
                <div class="profile-content d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">



                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="" class="profile-img me-3">
                        <?php else: ?>
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                style="width: 100px; height: 100px;">
                                <i class="bi bi-person text-white fs-1"></i>
                            </div>
                        <?php endif; ?>


                        <div>
                            <h4 class="mb-1"><?= htmlspecialchars($user['name'] ?? $user['email']) ?> </h4>
                            <p class="mb-0"><?= htmlspecialchars($user['country'] ?? 'Not available') ?> |
                                <?= htmlspecialchars($user['city'] ?? 'Not available') ?>
                            </p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="social-icons">
                            <a href="tel:<?= htmlspecialchars($user['phone'] ?? 'Not available') ?>"
                                class="btn btn-outline-light btn-sm"><i class="bi bi-phone"></i></a>
                            <a href="mailto:<?= htmlspecialchars($user['email']) ?>"
                                class="btn btn-outline-light btn-sm">
                                <i class="bi bi-envelope"></i>
                            </a>


                        </div>

                        <form method="POST" class="d-flex align-items-center">
                            <button type="submit" name="logout" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>

                        </form>


                    </div>
                </div>
            </div>
                <div class="">
                    
                    <?php if ($orders->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead  >
                                        <tr>
                                            <th>View</th>
                                            <th>Order Number</th>
                                            <th>Date & Time</th>
                                            <th>Fulfillment Status</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = $orders->fetch_assoc()): ?>
                                                <?php
                                                $finalPrice = (float) $order['finaltotalprice'];
                                                $discountValue = (float) $order['discount_value'];
                                                $discountType = $order['discount_type'];
                                                $priceBeforeDiscount = $finalPrice;
                                                $discountAmount = 0;

                                                if (!empty($order['coupon_code']) && $discountValue > 0) {
                                                    if ($discountType === 'percentage') {
                                                        $priceBeforeDiscount = $finalPrice / (1 - ($discountValue / 100));
                                                        $discountAmount = $priceBeforeDiscount - $finalPrice;
                                                    } else {
                                                        $priceBeforeDiscount = $finalPrice + $discountValue;
                                                        $discountAmount = $discountValue;
                                                    }
                                                }

                                                $stmtOrderItems->bind_param("i", $order['id']);
                                                $stmtOrderItems->execute();
                                                $orderItems = $stmtOrderItems->get_result();
                                                ?>

                                                <div class="modal fade" id="orderModal<?= $order['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Order Details #<?= $order['id'] ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <?php while ($item = $orderItems->fetch_assoc()): ?>
                                                                            <div class="col-12 mb-3">
                                                                                <div class="card">
                                                                                    <div class="card-body">
                                                                                        <div class="d-flex align-items-center">
                                                                                            <div class="position-relative me-3 rounded border" style="width: 80px; height: 80px; background-image: url('<?= htmlspecialchars($item['product_image']) ?>');" >
                                                                                                <span class="product-qty"><?= $item['qty'] ?></span>

                                                                                    
                                                                                            </div>
                                                                                            <div class="flex-grow-1">
                                                                                                <h6 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h6>
                                                                                                <p class="mb-1 text-muted small">
                                                                                                    <?php if (!empty($item['color']) && $item['color'] !== 'Not specified'): ?>
                                                                                                            <?= htmlspecialchars($item['color']) ?>
                                                                                                    <?php endif; ?>
                                                                                                    <?php if (!empty($item['size']) && $item['size'] !== 'Not specified'): ?>
                                                                                                            <?= htmlspecialchars($item['size']) ?>
                                                                                                    <?php endif; ?>
                                                                                                </p>
                                                                                            </div>
                                                                                            <div class="fw-bold">
                                                                                                <?= number_format($item['total_price'], 2) ?> <small>EGP</small>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                    <?php endwhile; ?>
                                                            
                                                                    <div class="col-12 mt-3">
                                                                        <div class="card">
                                                                            <div class="card-body" style="border: none ;">
                                                                                <?php if ($discountAmount > 0): ?>
                                                                                        <div class="d-flex justify-content-between mb-2">
                                                                                            <span>Subtotal</span>
                                                                                            <span><?= number_format($priceBeforeDiscount, 2) ?> <small>EGP</small></span>
                                                                                        </div>
                                                                                        <div class="d-flex justify-content-between mb-2 text-success">
                                                                                            <span>Discount</span>
                                                                                            <span>- <?= number_format($discountAmount, 2) ?> <small>EGP</small></span>
                                                                                        </div>
                                                                                <?php endif; ?>
                                                                                <div class="d-flex justify-content-between fw-bold fs-5">
                                                                                    <span>Total</span>
                                                                                    <span><?= number_format($finalPrice, 2) ?> <small>EGP</small></span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                 
                                                        </div>
                                                    </div>
                                                </div>

                                                <tr <?= ($order['id'] == $orderId) ? 'class="highlight-order"' : '' ?>>
                                                    <td data-label="View">
                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?= $order['id'] ?>">
                                                            <i class="bi bi-eye"></i> View
                                                        </button>
                                                    </td>
                                                    <td data-label="Order Number"><?= $order['id'] ?></td>
                                                    <td data-label="Date"><?= date('M j, Y H:i', strtotime($order['created_at'])) ?></td>
                                                    <td data-label="Fulfillment Status">
                                                        <span class="order-status 
                                                <?php
                                                switch ($order['orderstate']) {
                                                    case 'inprogress':
                                                        echo 'status-inprogress';
                                                        break;
                                                    case 'accepted':
                                                        echo 'status-accepted';
                                                        break;
                                                    case 'rejected':
                                                        echo 'status-rejected';
                                                        break;
                                                    case 'delivered':
                                                        echo 'status-delivered';
                                                        break;
                                                    default:
                                                        echo 'bg-secondary';
                                                }
                                                ?>">
                                                            <?php
                                                            switch ($order['orderstate']) {
                                                                case 'inprogress':
                                                                    echo 'Processing';
                                                                    break;
                                                                case 'accepted':
                                                                    echo 'Accepted';
                                                                    break;
                                                                case 'rejected':
                                                                    echo 'Rejected';
                                                                    break;
                                                                case 'delivered':
                                                                    echo 'Delivered';
                                                                    break;
                                                                default:
                                                                    echo htmlspecialchars($order['orderstate']);
                                                            }
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td data-label="Total"><?= number_format($finalPrice, 2) ?> EGP</td>
                                                </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                    <?php else: ?>
                            <div class="alert alert-info text-center">
                                No orders yet. <a href="products.php" class="alert-link">Browse products</a>
                            </div>
                    <?php endif; ?>
                </div>
        </div>



        <?php require('../includes/footer.php'); ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const activeTab = localStorage.getItem('activeTab') || 'orders';

                showTab(activeTab);

                document.getElementById('ordersTab').addEventListener('click', function (e) {
                    e.preventDefault();
                    showTab('orders');
                });

                document.getElementById('addressesTab').addEventListener('click', function (e) {
                    e.preventDefault();
                    showTab('addresses');
                });

                <?php if ($orderId > 0): ?>
                    const orderElement = document.querySelector('.highlight-order');
                    if (orderElement) {
                        orderElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

                        const modalId = '#orderModal<?= $orderId ?>';
                        const modal = new bootstrap.Modal(document.querySelector(modalId));
                        modal.show();
                    }
                <?php endif; ?>
            });

            function showTab(tabName) {
                if (tabName === 'orders') {
                    document.getElementById('ordersSection').classList.remove('d-none');
                    document.getElementById('addressesSection').classList.add('d-none');
                    document.getElementById('ordersTab').classList.add('active');
                    document.getElementById('addressesTab').classList.remove('active');
                } else {
                    document.getElementById('ordersSection').classList.add('d-none');
                    document.getElementById('addressesSection').classList.remove('d-none');
                    document.getElementById('ordersTab').classList.remove('active');
                    document.getElementById('addressesTab').classList.add('active');
                }

                localStorage.setItem('activeTab', tabName);
            }
        </script>
        <?php $stmtOrderItems->close(); ?>
    </body>

    </html>