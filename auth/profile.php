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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $country = trim($_POST['country']);
    $city = trim($_POST['city']);
    $address = trim($_POST['address']);

    $errors = [];
    if (empty($name) || empty($email) || empty($phone) || empty($country) || empty($city) || empty($address)) {
        $errors[] = 'All fields are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, country = ?, city = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $email, $phone, $country, $city, $address, $userId);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Profile updated successfully.';
        } else {
            $_SESSION['error'] = 'Failed to update profile.';
        }
        $stmt->close();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['errors'] = $errors;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderIdToCancel = (int) $_POST['order_id'];
    $stmt = $conn->prepare("UPDATE orders SET orderstate = 'rejected' WHERE id = ? AND user_id = ? AND orderstate = 'inprogress'");
    $stmt->bind_param("ii", $orderIdToCancel, $userId);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Order cancelled successfully.';
    } else {
        $_SESSION['error'] = 'Failed to cancel the order. It might already be processed.';
    }
    $stmt->close();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

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

$totalSpent = 0;
$acceptedOrdersCount = 0;

$stmtTotalSpent = $conn->prepare("SELECT SUM(finaltotalprice) AS total_spent FROM orders WHERE user_id = ?");
$stmtTotalSpent->bind_param("i", $userId);
$stmtTotalSpent->execute();
$resultTotalSpent = $stmtTotalSpent->get_result();
$totalSpentRow = $resultTotalSpent->fetch_assoc();
$totalSpent = $totalSpentRow['total_spent'] ?? 0;
$stmtTotalSpent->close();

$stmtAcceptedOrdersCount = $conn->prepare("SELECT COUNT(*) AS accepted_count FROM orders WHERE user_id = ? AND orderstate = 'accepted'");
$stmtAcceptedOrdersCount->bind_param("i", $userId);
$stmtAcceptedOrdersCount->execute();
$resultAcceptedOrdersCount = $stmtAcceptedOrdersCount->get_result();
$acceptedOrdersCountRow = $resultAcceptedOrdersCount->fetch_assoc();
$acceptedOrdersCount = $acceptedOrdersCountRow['accepted_count'] ?? 0;
$stmtAcceptedOrdersCount->close();

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Beauty Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php require('../includes/link.php'); ?>
    <link rel="stylesheet" href="./pro.css">
    <style>
        .input-group-text {
            background-color: transparent;
            border-right: 0;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
    </style>
</head>

<body>
    <?php require('../includes/header.php'); ?>

    <div class="container main-content">
        <div class="row">
            <div class="col-lg-8">
                <div class="fade-in-up">
                    <h1 class="dashboard-title">Profile</h1>
                    <p class="welcome-subtitle">Welcome back, <?= htmlspecialchars($user['name'] ?? $user['email']) ?>
                    </p>
                </div>

                <div id="dashboard-page" class="page-content active">
                    <div class="row">
                        <div class="col-md-4 fade-in-up-delay-1">
                            <div class="stats-card">

                                <div class="d-flex gap-3 align-items-center">
                                    <div class="stats-icon gift">
                                        <i class="fas fa-sack-dollar"></i>
                                    </div>
                                    <div class="stats-number  fs-4 "><?= number_format($totalSpent, 2) ?> </div>
                                </div>

                                <div class="stats-label">Total Spent</div>
                            </div>
                        </div>
                        <div class="col-md-4 fade-in-up-delay-2">
                            <div class="stats-card">

                                <div class="d-flex gap-3 align-items-center">

                                    <div class="stats-icon heart">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="stats-number  fs-4"><?= $acceptedOrdersCount ?></div>
                                </div>


                                <div class="stats-label">Accepted Orders</div>
                            </div>
                        </div>
                        <div class="col-md-4 fade-in-up-delay-3">




                            <div class="stats-card">
                                <div class="d-flex gap-3 align-items-center">

                                    <div class="stats-icon box">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div class="stats-number  fs-4"><?= $orders->num_rows ?></div>

                                </div>
                                <div class="stats-label">Total Orders</div>
                            </div>
                        </div>
                    </div>

                    <?php $orders->data_seek(0); ?>

                    <?php if ($orders->num_rows > 0): ?>
                        <div class="content-section fade-in-up">
                            <h3 class="section-title">Recent Orders</h3>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <?php
                                $finalPrice = (float) $order['finaltotalprice'];
                                $discountValue = (float) $order['discount_value'];
                                $discountType = $order['discount_type'];
                                $priceBeforeDiscount = $finalPrice;
                                $discountAmount = 0;
                                $orderStatus = $order['orderstate'];

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
                                $firstItem = $orderItems->fetch_assoc();
                                $productCount = $orderItems->num_rows + ($firstItem ? 1 : 0);
                                ?>
                                <div class="order-item" data-bs-toggle="modal"
                                    data-bs-target="#orderDetailsModal<?= $order['id'] ?>" style="cursor: pointer;">
                                    <div class="flex-grow-1">
                                        <div class="order-status 
                                            <?php
                                            switch ($orderStatus) {
                                                case 'inprogress':
                                                    echo 'status-processing';
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
                                            switch ($orderStatus) {
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
                                                    echo htmlspecialchars($orderStatus);
                                            }
                                            ?>
                                        </div>
                                        <div class="order-number">Order - #<?= $order['id'] ?></div>
                                        <div class="order-date"> <?= date('Y-m-d', strtotime($order['created_at'])) ?> •
                                            <?= $productCount ?> products
                                        </div>
                                    </div>
                                    <div class="order-date"><?= number_format($finalPrice, 2) ?> EGP</div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            No orders yet. <a href="products.php" class="alert-link">Browse products</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="orders-page" class="page-content">
                    <div class="content-section fade-in-up">
                        <h3 class="section-title">My Orders</h3>
                        <?php $orders->data_seek(0); ?>
                        <?php if ($orders->num_rows > 0): ?>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <?php
                                $finalPrice = (float) $order['finaltotalprice'];
                                $discountValue = (float) $order['discount_value'];
                                $discountType = $order['discount_type'];
                                $priceBeforeDiscount = $finalPrice;
                                $discountAmount = 0;
                                $orderStatus = $order['orderstate'];

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
                                $firstItem = $orderItems->fetch_assoc();
                                $productCount = $orderItems->num_rows + ($firstItem ? 1 : 0);
                                ?>
                                <div class="order-item" data-bs-toggle="modal"
                                    data-bs-target="#orderDetailsModal<?= $order['id'] ?>" style="cursor: pointer;">
                                    <div class="flex-grow-1">
                                        <div class="order-status 
                                            <?php
                                            switch ($orderStatus) {
                                                case 'inprogress':
                                                    echo 'status-processing';
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
                                            switch ($orderStatus) {
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
                                                    echo htmlspecialchars($orderStatus);
                                            }
                                            ?>
                                        </div>
                                        <div class="order-number">Order - #<?= $order['id'] ?></div>
                                        <div class="order-date">
                                            <?= date('Y-m-d', strtotime($order['created_at'])) ?> • <?= $productCount ?>
                                            products
                                        </div>
                                    </div>
                                    <div class="order-price"><?= number_format($finalPrice, 2) ?> EGP</div>
                                </div>
                                <div class="modal fade" id="orderDetailsModal<?= $order['id'] ?>" tabindex="-1"
                                    aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Order Details #<?= $order['id'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Order Date:</strong>
                                                    <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></p>
                                                <p><strong>Status:</strong>
                                                    <span class="badge 
                                                        <?php
                                                        switch ($orderStatus) {
                                                            case 'inprogress':
                                                                echo 'bg-info';
                                                                break;
                                                            case 'accepted':
                                                                echo 'bg-success';
                                                                break;
                                                            case 'rejected':
                                                                echo 'bg-danger';
                                                                break;
                                                            case 'delivered':
                                                                echo 'bg-primary';
                                                                break;
                                                            default:
                                                                echo 'bg-secondary';
                                                        }
                                                        ?>">
                                                        <?= htmlspecialchars($orderStatus) ?>
                                                    </span>
                                                </p>
                                                <?php if ($orderStatus === 'inprogress'): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="cancel_order" value="1">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm my-2">
                                                            <i class="fas fa-ban"></i> Cancel Order
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <h6>Products:</h6>
                                                <ul class="list-group">
                                                    <?php $orderItems->data_seek(0); ?>
                                                    <?php while ($item = $orderItems->fetch_assoc()): ?>
                                                        <li
                                                            class="list-group-item d-flex justify-content-between align-items-center">
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?= htmlspecialchars($imagePath . $item['product_image']) ?>"
                                                                    alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                                    style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                                                <div>
                                                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                                                    <br>
                                                                    <span>Quantity:
                                                                        <?= isset($item['quantity']) ? $item['quantity'] : 'N/A' ?></span>
                                                                    <br>
                                                                    <span>Price: <?= number_format($item['price'], 2) ?> EGP</span>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    <?php endwhile; ?>
                                                </ul>
                                                <hr>
                                                <p><strong>Total Price:</strong>
                                                    <?= number_format($order['totalprice'] ?? 0, 2) ?> EGP</p>
                                                <?php if (!empty($order['coupon_code'])): ?>
                                                    <p><strong>Coupon Code:</strong> <?= htmlspecialchars($order['coupon_code']) ?>
                                                    </p>
                                                    <p><strong>Discount:</strong> <?= number_format($discountAmount, 2) ?> EGP</p>
                                                <?php endif; ?>
                                                <p><strong>Final Price:</strong>
                                                    <?= number_format($order['finaltotalprice'] ?? 0, 2) ?> EGP</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                No orders yet. <a href="products.php" class="alert-link">Browse products</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="addresses-page" class="page-content">
                    <div class="content-section fade-in-up">
                        <h3 class="section-title">My Addresses</h3>
                        <div class="address-card default">
                            <div class="address-type">Default Address</div>
                            <div class="address-details">
                                <p><strong>Country:</strong> <?= htmlspecialchars($user['country'] ?? 'N/A') ?></p>
                                <p><strong>City:</strong> <?= htmlspecialchars($user['city'] ?? 'N/A') ?></p>
                                <p><strong>Street Address:</strong> <?= htmlspecialchars($user['address'] ?? 'N/A') ?>
                                </p>
                            </div>
                            <button class="btn btn-edit-address" data-bs-toggle="modal"
                                data-bs-target="#editAddressModal">
                                <i class="fas fa-edit"></i> Edit Address
                            </button>
                        </div>
                    </div>
                </div>

                <div id="settings-page" class="page-content">
                    <div class="settings-form fade-in-up">
                        <h3 class="section-title">Settings</h3>
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                            <?php unset($_SESSION['message']); ?>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['errors'])): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($_SESSION['errors'] as $error): ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php unset($_SESSION['errors']); ?>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="form-section">
                                <h4 class="form-section-title">Personal Information</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Full Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="text" class="form-control" name="name"
                                                    value="<?= htmlspecialchars($user['name']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Email Address</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                <input type="email" class="form-control" name="email"
                                                    value="<?= htmlspecialchars($user['email']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Phone Number</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                <input type="tel" class="form-control" name="phone"
                                                    value="<?= htmlspecialchars($user['phone']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Country</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                                <input type="text" class="form-control" name="country"
                                                    value="<?= htmlspecialchars($user['country']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">City</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-city"></i></span>
                                                <input type="text" class="form-control" name="city"
                                                    value="<?= htmlspecialchars($user['city']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Address</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i
                                                        class="fas fa-map-marked-alt"></i></span>
                                                <input type="text" class="form-control" name="address"
                                                    value="<?= htmlspecialchars($user['address']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-save-settings">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sidebar fade-in-up">
                    <div class="text-center">
                        <div class="user-avatar mx-auto">
                            <?= htmlspecialchars(substr($user['name'] ?? $user['email'], 0, 2)) ?>
                        </div>
                        <div class="user-name"><?= htmlspecialchars($user['name'] ?? $user['email']) ?></div>
                        <div class="user-status"><?= htmlspecialchars($user['country'] ?? 'Not available') ?> /
                            <?= htmlspecialchars($user['city'] ?? 'Not available') ?>
                        </div>
                    </div>
                    <ul class="sidebar-menu">
                        <li>
                            <a href="#" class="menu-link active" data-page="dashboard">
                                <i class="fas fa-chart-pie"></i>
                                Overview
                            </a>
                        </li>
                        <li>
                            <a href="#" class="menu-link" data-page="orders">
                                <i class="fas fa-box"></i>
                                My Orders
                            </a>
                        </li>
                        <li>
                            <a href="#" class="menu-link" data-page="addresses">
                                <i class="fas fa-map-marker-alt"></i>
                                Addresses
                            </a>
                        </li>
                        <li>
                            <a href="#" class="menu-link" data-page="settings">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php require('../includes/footer.php'); ?>

    <?php $stmtOrderItems->close(); ?>

    <div class="modal fade" id="editAddressModal" tabindex="-1" aria-labelledby="editAddressModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAddressModalLabel">Edit Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-group mb-3">
                            <label for="country" class="form-label">Country</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                <input type="text" class="form-control" id="country" name="country"
                                    value="<?= htmlspecialchars($user['country']) ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="city" class="form-label">City</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-city"></i></span>
                                <input type="text" class="form-control" id="city" name="city"
                                    value="<?= htmlspecialchars($user['city']) ?>">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="address" class="form-label">Street Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                                <input type="text" class="form-control" id="address" name="address"
                                    value="<?= htmlspecialchars($user['address']) ?>">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuLinks = document.querySelectorAll('.menu-link');
            const pages = document.querySelectorAll('.page-content');

            function showPage(pageId) {
                pages.forEach(page => {
                    if (page.id === pageId + '-page') {
                        page.classList.add('active');
                    } else {
                        page.classList.remove('active');
                    }
                });
                menuLinks.forEach(link => {
                    if (link.dataset.page === pageId) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            }

            menuLinks.forEach(link => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    const pageId = this.dataset.page;
                    showPage(pageId);
                });
            });

            const initialPage = 'dashboard';
            showPage(initialPage);
        });
    </script>
</body>

</html>