<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);
require('./db.php');

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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/main.css">
    <link rel="stylesheet" href="../style/viwe.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <style>
        body {
            font-family: system-ui, sans-serif;
            background-color: #fff;
        }

        .container.py-5 {
            max-width: 1200px;
        }

        .nav-link.active {
            border-bottom: 2px solid black;
        }

        h2.text-center.mb-4 {
            line-height: 1.1666666667;
            font-size: 22px;
        }

        .order-table th {
            font-weight: normal;
            color: #555;
            font-size: 11px;
            text-transform: uppercase;
            line-height: 1.466666666;
            letter-spacing: 1px;
            color: #000000b3;
            text-align: left;
            font-weight: 600;
        }

        td {
            font-weight: normal;
            color: #555;
            font-size: 14px;
            line-height: 1.466666666;
            letter-spacing: 1px;
            color: #000000b3;
            text-align: left;
            vertical-align: middle;
        }

        .order-table td {
            padding-top: 12px;
        }

        .bg-dark {
            border-radius: 50%;
        }

        span.order-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .status-inprogress {
            background-color: #fff3cd !important;
            color: #856404;
        }

        .status-accepted {
            background-color: #d4edda !important;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da !important;
            color: #721c24;
        }

        .status-delivered {
            background-color: #cce5ff !important;
            color: #004085;
        }

        .btn-cancel {
            background-color: #f8d7da;
            color: #721c24;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .btn-cancel:hover {
            background-color: #f5c6cb;
        }

        .order-details-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            padding: 20px;
            overflow-y: auto;
        }

        .popup-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 800px;
            margin: 20px auto;
        }

        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-popup {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 15px;
        }

        .order-summary {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .total-row {
            font-weight: bold;
            font-size: 18px;
        }

        .discount-row {
            color: #28a745;
        }

        .highlight-order {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .order-table thead {
                display: none;
            }

            .order-table tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                padding: 10px;
            }

            .order-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }

            .order-table td:before {
                content: attr(data-label);
                font-weight: bold;
                margin-right: 10px;
                color: #000;
            }

            .order-table td:last-child {
                border-bottom: none;
            }

            .popup-content {
                width: 95%;
                padding: 15px;
            }

            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-item img {
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>


    <?php require('./header.php'); ?>
    <nav class="d-flex justify-content-center py-3 border-bottom">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link active text-dark" href="#" onclick="showDiv('ordersDiv')">Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark" href="#" onclick="showDiv('addressesDiv')">Addresses</a>
            </li>
            <li class="nav-item">
                <form method="POST" class="d-grid">
                    <button type="submit" name="logout" class="nav-link text-dark">Logout</button>
                </form>
            </li>
        </ul>
    </nav>

    <div id="addressesDiv" style="display: none;">
        <div class="container py-5">
            <div class="table-responsive">
                <div>
                    <h2 class="text-center mb-4">Addresses</h2>
                    <table class="table order-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>City</th>
                                <th>Country</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= htmlspecialchars($user['name'] ?? $user['email']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone'] ?? 'Not available') ?></td>
                                <td><?= htmlspecialchars($user['address'] ?? 'Not available') ?></td>
                                <td><?= htmlspecialchars($user['city'] ?? 'Not available') ?></td>
                                <td><?= htmlspecialchars($user['country'] ?? 'Not available') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php if (!empty($user['profile_image'])): ?>
            <div class="container text-center mb-4">
                <img src="<?= htmlspecialchars($user['profile_image']) ?>" class="profile-img rounded-circle"
                    style="width: 100px; height: 100px; object-fit: cover;">
            </div>
        <?php else: ?>
            <div class="container text-center mb-4">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mx-auto"
                    style="width: 100px; height: 100px;">
                    <i class="bi bi-person text-white" style="font-size: 40px;"></i>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="container py-5">
        <div class="table-responsive">
            <div id="ordersDiv">
                <h2 class="text-center mb-4">Orders <span class="badge bg-dark"><?= $orders->num_rows ?></span></h2>
                <?php if ($orders->num_rows > 0): ?>
                    <table class="table order-table">
                        <thead>
                            <tr>
                                <th>View</th>
                                <th>Order Number</th>
                                <th>Date</th>
                                <th>Payment Status</th>
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

                                <div class="order-details-popup" id="orderPopup<?= $order['id'] ?>">
                                    <div class="popup-content">
                                        <div class="popup-header">
                                            <h3>Order Details #<?= $order['id'] ?></h3>
                                            <button class="close-popup"
                                                onclick="document.getElementById('orderPopup<?= $order['id'] ?>').style.display='none'">×</button>
                                        </div>

                                        <div class="order-items">
                                            <?php while ($item = $orderItems->fetch_assoc()): ?>
                                                <div class="order-item">
                                                    <img src="<?= htmlspecialchars($item['product_image']) ?>">
                                                    <div style="flex-grow:1;">
                                                        <h5><?= htmlspecialchars($item['product_name']) ?></h5>
                                                        <div style="display:flex; gap:15px; flex-wrap: wrap;">
                                                            <span><strong>Qty:</strong> <?= $item['qty'] ?></span>
                                                            <span><strong>Price:</strong>
                                                                <?= number_format($item['product_price'], 2) ?> EGP</span>
                                                            <span><strong>Total:</strong>
                                                                <?= number_format($item['total_price'], 2) ?> EGP</span>
                                                        </div>
                                                        <?php if (!empty($item['color']) && $item['color'] !== 'Not specified'): ?>
                                                            <span
                                                                style="background:#f1f1f1;padding:2px 5px;border-radius:3px;margin-right:5px;">Color:
                                                                <?= htmlspecialchars($item['color']) ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($item['size']) && $item['size'] !== 'Not specified'): ?>
                                                            <span style="background:#f1f1f1;padding:2px 5px;border-radius:3px;">Size:
                                                                <?= htmlspecialchars($item['size']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>

                                        <div class="order-summary">
                                            <div class="summary-row">
                                                <span>Subtotal:</span>
                                                <span><?= number_format($priceBeforeDiscount, 2) ?> EGP</span>
                                            </div>
                                            <?php if ($discountAmount > 0): ?>
                                                <div class="summary-row discount-row">
                                                    <span>Discount:</span>
                                                    <span>-<?= number_format($discountAmount, 2) ?> EGP</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="summary-row total-row">
                                                <span>Total:</span>
                                                <span><?= number_format($finalPrice, 2) ?> EGP</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <tr <?= ($order['id'] == $orderId) ? 'class="highlight-order"' : '' ?>>
                                    <td data-label="View">
                                        <button
                                            onclick="document.getElementById('orderPopup<?= $order['id'] ?>').style.display='block'"
                                            style="background:none;border:none;color:#0d6efd;cursor:pointer;">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                    <td data-label="Order Number"><?= $order['id'] ?></td>
                                    <td data-label="Date"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                    <td data-label="Payment Status">
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
                                    <td data-label="Actions">
                                        <?php if ($order['orderstate'] === 'inprogress'): ?>
                                            <form method="POST" action="cancel_order.php"
                                                onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-cancel" title="Cancel Order">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Total"><?= number_format($finalPrice, 2) ?> EGP</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        No orders yet. <a href="products.php" class="alert-link">Browse products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require('./footer.php'); ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showDiv(divId) {
            document.querySelectorAll('div[id$="Div"]').forEach(div => {
                div.style.display = 'none';
            });
            document.getElementById(divId).style.display = 'block';

            // Update active nav link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');

            event.preventDefault();
        }

        // Show orders div by default
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('ordersDiv').style.display = 'block';

            <?php if ($orderId > 0): ?>
                const orderElement = document.querySelector('.highlight-order');
                if (orderElement) {
                    orderElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            <?php endif; ?>
        });
    </script>
    <?php $stmtOrderItems->close(); ?>
</body>

</html>