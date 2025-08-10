<?php
require('../config/db.php');
if (!isset($_GET['id'])) {
    die("Customer ID is required");
}

$customer_id = intval($_GET['id']);

$customer = $conn->query("
    SELECT name, email 
    FROM users 
    WHERE id = $customer_id
")->fetch_assoc();

if (!$customer) {
    die("Customer not found");
}

$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';

$where_clause = "WHERE o.user_id = $customer_id";
if ($search_query) {
    $where_clause .= " AND (o.id LIKE '%$search_query%' OR o.orderstate LIKE '%$search_query%')";
}
if ($status_filter !== 'all') {
    $where_clause .= " AND o.orderstate = '$status_filter'";
}

$orders = $conn->query("
    SELECT 
        o.id, 
        IFNULL(SUM(oi.price * oi.qty), 0) AS total_amount, 
        o.created_at,
        o.orderstate
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    $where_clause
    GROUP BY o.id, o.created_at, o.orderstate
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$all_states = ['pending', 'processing', 'delivered', 'canceled', 'returned'];

$status_counts_result = $conn->query("
    SELECT orderstate, COUNT(*) AS count
    FROM orders
    WHERE user_id = $customer_id
    GROUP BY orderstate
");

$status_counts = [];
while ($row = $status_counts_result->fetch_assoc()) {
    $status_counts[strtolower($row['orderstate'])] = intval($row['count']);
}

foreach ($all_states as $state) {
    if (!isset($status_counts[$state])) {
        $status_counts[$state] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - <?php echo htmlspecialchars($customer['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init="
         darkMode = JSON.parse(localStorage.getItem('darkMode'));
         $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}">
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
            <div x-data="{ pageName: `Customers` }">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName">Customers</h2>
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
                            <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName">Customers</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="col-span-12">
                <div class="grid grid-cols-5 gap-4">
                    <?php foreach ($all_states as $state): ?>
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] flex flex-col justify-between">
                            <p class="text-theme-sm text-gray-500 dark:text-gray-400 flex items-center gap-2 mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 dark:text-gray-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <?php echo ucfirst($state); ?>
                            </p>
                            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90 mb-3">
                                <?php echo $status_counts[$state]; ?>
                            </h4>
                            <div class="flex items-center gap-1 text-theme-xs text-success-600 dark:text-success-500">
                                <span
                                    class="flex items-center gap-1 rounded-full bg-success-50 px-2 py-0.5 font-medium dark:bg-success-500/15">
                                    +0%
                                </span>
                                <span class="text-gray-500 dark:text-gray-400">
                                    Vs last month
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div
                class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div
                    class="flex flex-col gap-4 border-b border-gray-200 px-4 py-4 sm:px-5 lg:flex-row lg:items-center lg:justify-between dark:border-gray-800">
                    <div class="flex-shrink-0">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                            Orders for <?php echo htmlspecialchars($customer['name']); ?>
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?php echo htmlspecialchars($customer['email']); ?>
                        </p>
                    </div>

                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <form method="GET" action="" class="flex items-center gap-2">
                            <input type="hidden" name="id" value="<?php echo $customer_id; ?>">
                            <div class="relative">
                                <input type="text" name="search" placeholder="Search orders..."
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                                <button type="submit" class="absolute right-2 top-2 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </button>
                            </div>
                        </form>

                        <div x-data="{selected: '<?php echo $status_filter; ?>'}"
                            class="inline-flex h-11 w-full gap-0.5 overflow-x-auto rounded-lg bg-gray-100 p-0.5 sm:w-auto lg:min-w-fit dark:bg-gray-900">
                            <button @click="selected = 'all'" type="button"
                                :class="selected === 'all' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                                class="h-10 flex-1 rounded-md px-2 py-2 text-xs font-medium hover:text-gray-900 sm:px-3 sm:text-sm lg:flex-initial dark:hover:text-white"
                                onclick="window.location.href='?id=<?php echo $customer_id; ?>&status=all<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>'">
                                All
                            </button>
                            <?php foreach ($all_states as $state): ?>
                                <button @click="selected = '<?php echo $state; ?>'" type="button"
                                    :class="selected === '<?php echo $state; ?>' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                                    class="h-10 flex-1 rounded-md px-2 py-2 text-xs font-medium hover:text-gray-900 sm:px-3 sm:text-sm lg:flex-initial dark:hover:text-white"
                                    onclick="window.location.href='?id=<?php echo $customer_id; ?>&status=<?php echo $state; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>'">
                                    <?php echo ucfirst($state); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="custom-scrollbar overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                                <th class="p-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Order ID
                                </th>
                                <th class="p-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Date</th>
                                <th class="p-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Time</th>
                                <th class="p-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Amount
                                </th>
                                <th class="p-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status
                                </th>
                                <th class="p-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <?php if (count($orders) > 0): ?>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                    $datetime = new DateTime($order['created_at']);
                                    $date = $datetime->format('Y-m-d');
                                    $time = $datetime->format('H:i:s');
                                    $status = htmlspecialchars($order['orderstate']);
                                    $status_class = '';
                                    switch ($status) {
                                        case 'pending':
                                            $status_class = 'bg-warning-50 dark:bg-warning-500/15 text-warning-700 dark:text-warning-500';
                                            break;
                                        case 'processing':
                                            $status_class = 'bg-info-50 dark:bg-info-500/15 text-info-700 dark:text-info-500';
                                            break;
                                        case 'delivered':
                                            $status_class = 'bg-success-50 dark:bg-success-500/15 text-success-700 dark:text-success-500';
                                            break;
                                        case 'canceled':
                                        case 'returned':
                                            $status_class = 'bg-danger-50 dark:bg-danger-500/15 text-danger-700 dark:text-danger-500';
                                            break;
                                        default:
                                            $status_class = 'bg-gray-50 dark:bg-gray-500/15 text-gray-700 dark:text-gray-500';
                                    }
                                    ?>
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-900">
                                        <td class="p-4 text-sm font-normal text-gray-800 dark:text-white/90">
                                            <?php echo $order['id']; ?></td>
                                        <td class="p-4 text-sm font-normal text-gray-700 dark:text-white/90">
                                            <?php echo $date; ?></td>
                                        <td class="p-4 text-sm font-normal text-gray-700 dark:text-white/90">
                                            <?php echo $time; ?></td>
                                        <td class="p-4 text-sm font-normal text-gray-700 dark:text-white/90">
                                            <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td class="p-4 whitespace-nowrap">
                                            <span
                                                class="<?php echo $status_class; ?> text-theme-xs rounded-full px-2 py-0.5 font-medium">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <a href="invoice.php?order_id=<?php echo $order['id']; ?>"
                                                class="text-brand-500 hover:text-brand-600 text-sm font-medium">
                                                View Invoice
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-gray-500 dark:text-gray-400">No orders
                                        found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <style>
        .grid.grid-cols-5.gap-4 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
        }
    </style>
</body>

</html>