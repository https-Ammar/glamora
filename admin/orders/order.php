<?php
session_start();
require('../config/db.php');

if (!isset($_SESSION['userId'])) {
    header('Location: ./login.php');
    exit();
}

$statusCounts = [];
$allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
$filterStatus = isset($_GET['status']) && in_array($_GET['status'], $allowedStatuses) ? $_GET['status'] : '';

foreach ($allowedStatuses as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE orderstate = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $statusCounts[$status] = $row['count'];
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$result = $stmt->get_result();
$totalOrders = $result->fetch_assoc()['total'];
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['order_id'], $_POST['status'])) {
        $orderId = intval($_POST['order_id']);
        $status = $_POST['status'];

        if (!in_array($status, $allowedStatuses)) {
            $status = 'pending';
        }

        $stmt = $conn->prepare("UPDATE orders SET orderstate = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $orderId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO order_status_logs (order_id, status) VALUES (?, ?)");
        $stmt->bind_param("is", $orderId, $status);
        $stmt->execute();
        $stmt->close();

        header('Location: order.php?success=1');
        exit();
    }

    if (isset($_POST['delete_id'])) {
        $deleteId = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        $stmt->close();

        header('Location: order.php?success=2');
        exit();
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "
    SELECT o.*, u.name as customer_name, u.email as customer_email 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE (o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR o.address LIKE ?)
";
$params = ["%$search%", "%$search%", "%$search%", "%$search%"];

if (!empty($filterStatus)) {
    $query .= " AND o.orderstate = ?";
    $params[] = $filterStatus;
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .inline-flex.h-16.w-16.items-center.justify-center.rounded-xl.bg-blue-100.text-blue-800.dark\:bg-blue-800.dark\:text-blue-100,
        select.text-sm.rounded-lg.px-3.py-1.status-pending.border-none.focus\:ring-2.focus\:ring-opacity-50 {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .inline-flex.h-16.w-16.items-center.justify-center.rounded-xl.bg-yellow-100.text-yellow-800.dark\:bg-yellow-800.dark\:text-yellow-100,
        select.text-sm.rounded-lg.px-3.py-1.status-processing.border-none.focus\:ring-2.focus\:ring-opacity-50 {
            background-color: #fef3c7;
            color: #92400e;
        }

        .inline-flex.h-16.w-16.items-center.justify-center.rounded-xl.bg-purple-100.text-purple-800.dark\:bg-purple-800.dark\:text-purple-100,
        select.text-sm.rounded-lg.px-3.py-1.status-shipped.border-none.focus\:ring-2.focus\:ring-opacity-50 {
            background-color: #f3e8ff;
            color: #6b21a8;
        }

        .inline-flex.h-16.w-16.items-center.justify-center.rounded-xl.bg-red-100.text-red-800.dark\:bg-red-800.dark\:text-red-100 {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .inline-flex.h-16.w-16.items-center.justify-center.rounded-xl.bg-orange-100.text-orange-800.dark\:bg-orange-800.dark\:text-orange-100 {
            background-color: #1e3b8a7d;
            color: #bfdbfe;
        }

        .active-filter {
            box-shadow: 0 0 0 2px #3b82f6;
        }
    </style>
</head>

<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}">

    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) px-5 py-4 md:p-6">
            <div x-data="{ pageName: `Orders Management` }">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName">Orders
                        Management</h2>
                    <nav>
                        <ol class="flex items-center gap-1.5">
                            <li>
                                <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                                    href="index.html">
                                    Home
                                    <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke=""
                                            stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </a>
                            </li>
                            <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName">Orders</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    class="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/3 <?= empty($filterStatus) ? 'active-filter' : '' ?>">
                    <a href="order.php" class="flex items-center gap-5 w-full">
                        <div
                            class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-white/90">
                            <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                viewBox="0 0 28 28" fill="none">
                                <path
                                    d="M14.0003 24.5898V24.5863M14.0003 12.8684V24.5863M9.06478 16.3657V10.6082M18.9341 5.67497C18.9341 5.67497 12.9204 8.68175 9.06706 10.6084M23.5913 8.27989C23.7686 8.55655 23.8679 8.88278 23.8679 9.2241V18.7779C23.8679 19.4407 23.4934 20.0467 22.9005 20.3431L14.7834 24.4015C14.537 24.5248 14.2686 24.5864 14.0003 24.5863M23.5913 8.27989L14.7834 12.6837C14.2908 12.93 13.7109 12.93 13.2182 12.6837L4.41037 8.27989M23.5913 8.27989C23.4243 8.01927 23.1881 7.80264 22.9005 7.65884L14.7834 3.60044C14.2908 3.35411 13.7109 3.35411 13.2182 3.60044L5.10118 7.65884C4.81359 7.80264 4.57737 8.01927 4.41037 8.27989M4.41037 8.27989C4.23309 8.55655 4.13379 8.88278 4.13379 9.2241V18.7779C4.13379 19.4407 4.5083 20.0467 5.10118 20.3431L13.2182 24.4015C13.4644 24.5246 13.7324 24.5862 14.0003 24.5863"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90"><?= $totalOrders ?></h3>
                            <p class="text-gray-500 dark:text-gray-400">Total Orders</p>
                        </div>
                    </a>
                </article>

                <article
                    class="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/3 <?= $filterStatus === 'pending' ? 'active-filter' : '' ?>">
                    <a href="order.php?status=pending" class="flex items-center gap-5 w-full">
                        <div
                            class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                                <?= $statusCounts['pending'] ?>
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400">Pending Orders</p>
                        </div>
                    </a>
                </article>

                <article
                    class="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/3 <?= $filterStatus === 'processing' ? 'active-filter' : '' ?>">
                    <a href="order.php?status=processing" class="flex items-center gap-5 w-full">
                        <div
                            class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                                <?= $statusCounts['processing'] ?>
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400">Processing Orders</p>
                        </div>
                    </a>
                </article>

                <article
                    class="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/3 <?= $filterStatus === 'shipped' ? 'active-filter' : '' ?>">
                    <a href="order.php?status=shipped" class="flex items-center gap-5 w-full">
                        <div
                            class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M3 6h18"></path>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                                <?= $statusCounts['shipped'] ?>
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400">Shipped Orders</p>
                        </div>
                    </a>
                </article>

                <article
                    class="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/3 <?= $filterStatus === 'delivered' ? 'active-filter' : '' ?>">
                    <a href="order.php?status=delivered" class="flex items-center gap-5 w-full">
                        <div
                            class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                                <?= $statusCounts['delivered'] ?>
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400">Delivered Orders</p>
                        </div>
                    </a>
                </article>

                <article
                    class="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/3 <?= $filterStatus === 'cancelled' ? 'active-filter' : '' ?>">
                    <a href="order.php?status=cancelled" class="flex items-center gap-5 w-full">
                        <div
                            class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                                <?= $statusCounts['cancelled'] ?>
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400">Cancelled Orders</p>
                        </div>
                    </a>
                </article>

                <article
                    class="flex items-center gap-5 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/3 <?= $filterStatus === 'refunded' ? 'active-filter' : '' ?>">
                    <a href="order.php?status=refunded" class="flex items-center gap-5 w-full">
                        <div
                            class="inline-flex h-16 w-16 items-center justify-center rounded-xl bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 12h-4l-4 9-4-9H4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                                <?= $statusCounts['refunded'] ?>
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400">Refunded Orders</p>
                        </div>
                    </a>
                </article>
            </div>

            <div
                class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div
                    class="flex flex-col justify-between gap-5 border-b border-gray-200 px-5 py-4 sm:flex-row lg:items-center dark:border-gray-800">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Orders</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">List of all orders with their current status
                        </p>
                    </div>

                    <div class="hidden flex-col gap-3 sm:flex sm:flex-row sm:items-center">
                        <!-- <?php if (!empty($filterStatus)): ?>
                            <a href="order.php" class="text-sm text-blue-500 hover:underline">Clear Filter</a>
                        <?php endif; ?> -->
                        <form method="GET" class="relative">
                            <input type="hidden" name="status" value="<?= $filterStatus ?>">
                            <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z"
                                        fill=""></path>
                                </svg>
                            </span>
                            <input type="text" name="search" placeholder="Search..."
                                value="<?= htmlspecialchars($search) ?>"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[300px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </form>
                    </div>
                </div>
                <div class="custom-scrollbar overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                                <th class="p-4 whitespace-nowrap">
                                    <div class="flex w-full items-center gap-3">
                                        <label
                                            class="flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                                            <span class="relative">
                                                <input type="checkbox" class="sr-only">
                                                <span
                                                    class="flex h-4 w-4 items-center justify-center rounded-sm border-[1.25px] bg-transparent border-gray-300 dark:border-gray-700">
                                                    <span class="opacity-0">
                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M10 3L4.5 8.5L2 6" stroke="white"
                                                                stroke-width="1.6666" stroke-linecap="round"
                                                                stroke-linejoin="round"></path>
                                                        </svg>
                                                    </span>
                                                </span>
                                            </span>
                                        </label>
                                        <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">Order ID
                                        </p>
                                    </div>
                                </th>
                                <th
                                    class="p-4 text-left text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    Customer</th>
                                <th
                                    class="p-4 text-left text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    Email</th>
                                <th
                                    class="p-4 text-left text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    Total Amount</th>
                                <th
                                    class="p-4 text-left text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    Address</th>
                                <th
                                    class="p-4 text-left text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    Date</th>
                                <th
                                    class="p-4 text-left text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    Time</th>
                                <th
                                    class="p-4 text-left text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    Status</th>
                                <th
                                    class="p-4 text-left text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-x divide-y divide-gray-200 dark:divide-gray-800">
                            <?php if (isset($_GET['success'])): ?>
                                <tr>
                                    <td colspan="9" class="p-4">
                                        <div
                                            class="<?= $_GET['success'] == '1' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> p-3 rounded-lg">
                                            <?= $_GET['success'] == '1' ? 'Order status updated successfully' : 'Order deleted successfully' ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php if ($orders->num_rows > 0): ?>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-900">
                                        <td class="p-4 whitespace-nowrap">
                                            <div class="group flex items-center gap-3">
                                                <label
                                                    class="flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                                                    <span class="relative">
                                                        <input type="checkbox" class="sr-only">
                                                        <span
                                                            class="flex h-4 w-4 items-center justify-center rounded-sm border-[1.25px] bg-transparent border-gray-300 dark:border-gray-700">
                                                            <span class="opacity-0">
                                                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M10 3L4.5 8.5L2 6" stroke="white"
                                                                        stroke-width="1.6666" stroke-linecap="round"
                                                                        stroke-linejoin="round"></path>
                                                                </svg>
                                                            </span>
                                                        </span>
                                                    </span>
                                                </label>
                                                <a href="order_details.php?id=<?= $order['id'] ?>"
                                                    class="text-theme-xs font-medium text-gray-700 group-hover:underline dark:text-gray-400"><?= $order['id'] ?></a>
                                            </div>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-gray-400"><?= htmlspecialchars($order['name'] ?? $order['customer_name'] ?? 'Guest') ?></span>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <?= htmlspecialchars($order['customer_email'] ?? '') ?>
                                            </p>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <p class="text-sm text-gray-700 dark:text-gray-400">
                                                <?= number_format($order['finaltotalprice'], 2) ?> <sub>EG</sub>
                                            </p>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <p class="text-sm text-gray-700 dark:text-gray-400">
                                                <?= htmlspecialchars($order['address']) ?>
                                            </p>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <p class="text-sm text-gray-700 dark:text-gray-400">
                                                <?= date('Y-m-d', strtotime($order['created_at'])) ?>
                                            </p>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <p class="text-sm text-gray-700 dark:text-gray-400">
                                                <?= date('h:i A', strtotime($order['created_at'])) ?>
                                            </p>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <select name="status" onchange="this.form.submit()"
                                                    class="text-sm rounded-lg px-3 py-1 status-<?= $order['orderstate'] ?> border-none focus:ring-2 focus:ring-opacity-50">
                                                    <option value="pending" <?= $order['orderstate'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="processing" <?= $order['orderstate'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                                    <option value="shipped" <?= $order['orderstate'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                    <option value="delivered" <?= $order['orderstate'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                    <option value="cancelled" <?= $order['orderstate'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                    <option value="refunded" <?= $order['orderstate'] == 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <div x-data="{ open: false }" class="relative flex justify-center">
                                                <button @click="open = !open" class="text-gray-500 dark:text-gray-400">
                                                    <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M5.99902 10.245C6.96552 10.245 7.74902 11.0285 7.74902 11.995V12.005C7.74902 12.9715 6.96552 13.755 5.99902 13.755C5.03253 13.755 4.24902 12.9715 4.24902 12.005V11.995C4.24902 11.0285 5.03253 10.245 5.99902 10.245ZM17.999 10.245C18.9655 10.245 19.749 11.0285 19.749 11.995V12.005C19.749 12.9715 18.9655 13.755 17.999 13.755C17.0325 13.755 16.249 12.9715 16.249 12.005V11.995C16.249 11.0285 17.0325 10.245 17.999 10.245ZM13.749 11.995C13.749 11.0285 12.9655 10.245 11.999 10.245C11.0325 10.245 10.249 11.0285 10.249 11.995V12.005C10.249 12.9715 11.0325 13.755 11.999 13.755C12.9655 13.755 13.749 12.9715 13.749 12.005V11.995Z"
                                                            fill=""></path>
                                                    </svg>
                                                </button>
                                                <div x-show="open" @click.outside="open = false"
                                                    class="shadow-theme-lg dark:bg-gray-dark fixed w-40 space-y-1 rounded-2xl border border-gray-200 bg-white p-2 dark:border-gray-800"
                                                    style="display: none;">
                                                    <a href="order_details.php?order_id=<?= $order['id'] ?>"
                                                        class="text-theme-xs flex w-full rounded-lg px-3 py-2 text-left font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                                                        View Details
                                                    </a>
                                                    <form method="POST" class="w-full">
                                                        <input type="hidden" name="delete_id" value="<?= $order['id'] ?>">
                                                        <button type="submit"
                                                            class="text-theme-xs flex w-full rounded-lg px-3 py-2 text-left font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                        No orders found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bundle.js"></script>
</body>

</html>