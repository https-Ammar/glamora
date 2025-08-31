<?php
require('../config/db.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("Order ID is required");
}

$order_id = intval($_GET['order_id']);

$stmt = $conn->prepare("
    SELECT 
        o.id, o.created_at, o.orderstate, o.coupon_code, o.finaltotalprice,
        o.customer_name AS guest_name, o.customer_email AS guest_email, o.city AS guest_city, o.address AS guest_address, o.phone AS guest_phone,
        u.name AS registered_name, u.email AS registered_email, u.city AS registered_city, u.address AS registered_address, u.phone AS registered_phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order not found");
}

$customer_name = $order['registered_name'] ?? $order['guest_name'];
$customer_email = $order['registered_email'] ?? $order['guest_email'];
$city = $order['registered_city'] ?? $order['guest_city'];
$address = $order['registered_address'] ?? $order['guest_address'];
$phone = $order['registered_phone'] ?? $order['guest_phone'];

$stmt_items = $conn->prepare("
    SELECT p.name, p.image, oi.price, oi.qty, oi.color, oi.size
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();

$total_before_discount = 0;
foreach ($items as $item) {
    $total_before_discount += $item['price'] * $item['qty'];
}

$coupon = null;
if (!empty($order['coupon_code'])) {
    $stmt_coupon = $conn->prepare("
        SELECT code, discount_type, discount_value
        FROM coupons
        WHERE code = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt_coupon->bind_param("s", $order['coupon_code']);
    $stmt_coupon->execute();
    $coupon_result = $stmt_coupon->get_result();
    $coupon = $coupon_result->fetch_assoc();
    $stmt_coupon->close();
}

$total_after_discount = $order['finaltotalprice'] ?? $total_before_discount;

function formatPrice($value) {
    return '$' . number_format($value, 2);
}

$current_time = date('h:i A');

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Invoice - #<?php echo htmlspecialchars($order['id']); ?></title>
    <link rel="stylesheet" href="../assets/css/main.css" />
</head>
<body x-data="{ page: 'ecommerce', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: false, scrollTop: false }"
    x-init="
         darkMode = JSON.parse(localStorage.getItem('darkMode'));
         $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}">
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) px-5 py-4 md:p-6">
            <div x-data="{ pageName: 'Invoice'}">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName">Invoice</h2>
                    <nav>
                        <ol class="flex items-center gap-1.5">
                            <li>
                                <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="index.html">
                                    Home
                                    <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke=""
                                            stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </a>
                            </li>
                            <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName">Invoice</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <div>
                <div class="w-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                        <h3 class="text-theme-xl font-medium text-gray-800 dark:text-white/90">Invoice</h3>
                        <h4 class="text-base font-medium text-gray-700 dark:text-gray-400">ID : #<?php echo htmlspecialchars($order['id']); ?></h4>
                    </div>
                    <div class="p-5 xl:p-8">
                        <div class="mb-9 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-400">Customer Info</span>
                                <h5 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90"><?php echo htmlspecialchars($customer_name); ?></h5>
                                <p class="mb-1 text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($customer_email); ?></p>
                                <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">City: <?php echo htmlspecialchars($city); ?></p>
                                <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">Address: <?php echo htmlspecialchars($address); ?></p>
                                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Phone: <?php echo htmlspecialchars($phone); ?></p>
                                <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Issued On:</span>
                                <span class="block text-sm text-gray-500 dark:text-gray-400"><?php echo date('d M, Y', strtotime($order['created_at'])) . ' at ' . $current_time; ?></span>
                            </div>
                            <div class="h-px w-full bg-gray-200 sm:h-[158px] sm:w-px dark:bg-gray-800"></div>
                            <div class="sm:text-right">
                                <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-400">Order Status</span>
                                <h5 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">
                                    <?php echo htmlspecialchars(ucfirst($order['orderstate'])); ?>
                                </h5>
                            </div>
                        </div>
                        <div>
                            <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
                                <table class="min-w-full text-left text-gray-700 dark:text-gray-400">
                                    <thead class="bg-gray-50 dark:bg-gray-900">
                                        <tr class="border-b border-gray-100 dark:border-gray-800">
                                            <th class="px-5 py-3 text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">#</th>
                                            <th class="px-5 py-3 text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">Product</th>
                                            <th class="px-5 py-3 text-center text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">Color</th>
                                            <th class="px-5 py-3 text-center text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">Size</th>
                                            <th class="px-5 py-3 text-center text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">Quantity</th>
                                            <th class="px-5 py-3 text-center text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">Unit Price</th>
                                            <th class="px-5 py-3 text-center text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        <?php $i = 1; foreach ($items as $item): ?>
                                            <tr>
                                                <td class="px-5 py-3 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"><?php echo $i++; ?></td>
                                                <td class="px-5 py-3 text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90">
                                                    <div class="flex items-center gap-3">
                                                        <?php if ($item['image']): ?>
                                                            <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="Product Image" class="w-10 h-10 object-cover rounded">
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-3 text-center text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($item['color'] ?? 'N/A'); ?></td>
                                                <td class="px-5 py-3 text-center text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($item['size'] ?? 'N/A'); ?></td>
                                                <td class="px-5 py-3 text-center text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($item['qty']); ?></td>
                                                <td class="px-5 py-3 text-center text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"><?php echo formatPrice($item['price']); ?></td>
                                                <td class="px-5 py-3 text-center text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"><?php echo formatPrice($item['price'] * $item['qty']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="my-6 flex flex-col items-end border-b border-gray-100 pb-6 dark:border-gray-800 gap-2">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Subtotal: <?php echo formatPrice($total_before_discount); ?></p>
                            <?php if ($coupon): ?>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Coupon Code: <?php echo htmlspecialchars($coupon['code']); ?></p>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Discount Type: 
                                    <?php echo $coupon['discount_type'] === 'percentage' ? 'Percentage' : 'Fixed Amount'; ?>
                                </p>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Discount Value: 
                                    <?php 
                                        echo $coupon['discount_type'] === 'percentage' 
                                            ? htmlspecialchars($coupon['discount_value']) . '%' 
                                            : formatPrice($coupon['discount_value']);
                                    ?>
                                </p>
                            <?php endif; ?>
                            <p class="text-lg font-semibold text-gray-800 dark:text-white/90">Total: <?php echo formatPrice($total_after_discount); ?></p>
                        </div>
                        <div class="flex items-center justify-end gap-3">
                            <button
                                class="shadow-theme-xs flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
                                onclick="window.history.back()">
                                Back to Orders
                            </button>
                            <button
                                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white"
                                onclick="window.print()">
                                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M6.99578 4.08398C6.58156 4.08398 6.24578 4.41977 6.24578 4.83398V6.36733H13.7542V5.62451C13.7542 5.42154 13.672 5.22724 13.5262 5.08598L12.7107 4.29545C12.5707 4.15983 12.3835 4.08398 12.1887 4.08398H6.99578ZM15.2542 6.36902V5.62451C15.2542 5.01561 15.0074 4.43271 14.5702 4.00891L13.7547 3.21839C13.3349 2.81151 12.7733 2.58398 12.1887 2.58398H6.99578C5.75314 2.58398 4.74578 3.59134 4.74578 4.83398V6.36902C3.54391 6.41522 2.58374 7.40415 2.58374 8.61733V11.3827C2.58374 12.5959 3.54382 13.5848 4.74561 13.631V15.1665C4.74561 16.4091 5.75297 17.4165 6.99561 17.4165H13.0041C14.2467 17.4165 15.2541 16.4091 15.2541 15.1665V13.6311C16.456 13.585 17.4163 12.596 17.4163 11.3827V8.61733C17.4163 7.40414 16.4561 6.41521 15.2542 6.36902ZM4.74561 11.6217V12.1276C4.37292 12.084 4.08374 11.7671 4.08374 11.3827V8.61733C4.08374 8.20312 4.41953 7.86733 4.83374 7.86733H15.1663C15.5805 7.86733 15.9163 8.20312 15.9163 8.61733V11.3827C15.9163 11.7673 15.6269 12.0842 15.2541 12.1277V11.6217C15.2541 11.2075 14.9183 10.8717 14.5041 10.8717H5.49561C5.08139 10.8717 4.74561 11.2075 4.74561 11.6217ZM6.24561 12.3717V15.1665C6.24561 15.5807 6.58139 15.9165 6.99561 15.9165H13.0041C13.4183 15.9165 13.7541 15.5807 13.7541 15.1665V12.3717H6.24561Z"
                                        fill=""></path>
                                </svg>
                                Print
                            </button>
                        </div>
                    </div>  
                </div>
            </div>
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bundle.js"></script>
</body>
</html>