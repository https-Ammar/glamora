<?php
session_start();
require('../config/db.php');

if (!isset($_SESSION['userId'])) {
    header('Location: ../auth/signin.php');
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("UPDATE users SET status = 'deleted' WHERE id = $id");
    header("Location: customers.php");
    exit();
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE users SET status = IF(status='active','suspended','active') WHERE id = $id");
    header("Location: customers.php");
    exit();
}

$query = $conn->query("
    SELECT 
        u.id, u.name, u.email, u.phone, u.address, u.city, u.country, 
        u.profile_image, u.created_at, u.status,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count
    FROM users u
    ORDER BY u.id DESC
");
$customers = $query->fetch_all(MYSQLI_ASSOC);

$totalCustomers = count($customers);
$activeCustomers = 0;
$suspendedCustomers = 0;
$deletedCustomers = 0;

foreach ($customers as $customer) {
    if ($customer['status'] == 'active') {
        $activeCustomers++;
    } elseif ($customer['status'] == 'suspended') {
        $suspendedCustomers++;
    } elseif ($customer['status'] == 'deleted') {
        $deletedCustomers++;
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/main.css">
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

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            Overview
                        </h3>
                    </div>
                    <div class="flex gap-x-3.5">
                        <div x-data="{selected: 'weekly'}"
                            class="inline-flex w-full items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                            <button @click="selected = 'weekly'"
                                :class="selected === 'weekly' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                                class="text-theme-sm w-full rounded-md px-3 py-2 font-medium hover:text-gray-900 dark:hover:text-white shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800">
                                Weekly
                            </button>
                            <button @click="selected = 'monthly'"
                                :class="selected === 'monthly' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                                class="text-theme-sm w-full rounded-md px-3 py-2 font-medium hover:text-gray-900 dark:hover:text-white text-gray-500 dark:text-gray-400">
                                Monthly
                            </button>
                            <button @click="selected = 'yearly'"
                                :class="selected === 'yearly' ? 'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400'"
                                class="text-theme-sm w-full rounded-md px-3 py-2 font-medium hover:text-gray-900 dark:hover:text-white text-gray-500 dark:text-gray-400">
                                Yearly
                            </button>
                        </div>
                        <div>
                            <button
                                class="text-theme-sm shadow-theme-xs inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                <svg class="fill-white stroke-current dark:fill-gray-800" width="20" height="20"
                                    viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2.29004 5.90393H17.7067" stroke="" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M17.7075 14.0961H2.29085" stroke="" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path
                                        d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z"
                                        fill="" stroke="" stroke-width="1.5"></path>
                                    <path
                                        d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z"
                                        fill="" stroke="" stroke-width="1.5"></path>
                                </svg>

                                <span class="hidden sm:block">Filter</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div
                    class="grid rounded-2xl border border-gray-200 bg-white sm:grid-cols-2 xl:grid-cols-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-6 py-5 sm:border-r xl:border-b-0 dark:border-gray-800">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Customers</span>
                        <div class="mt-2 flex items-end gap-3">
                            <h4 class="text-title-xs sm:text-title-sm font-bold text-gray-800 dark:text-white/90">
                                <?php echo $totalCustomers; ?>
                            </h4>
                            <div>
                                <span
                                    class="bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500 flex items-center gap-1 rounded-full py-0.5 pr-2.5 pl-2 text-sm font-medium">
                                    +<?php echo round(($totalCustomers / ($totalCustomers + $deletedCustomers)) * 100, 1); ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="border-b border-gray-200 px-6 py-5 xl:border-r xl:border-b-0 dark:border-gray-800">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Active Customers</span>
                        <div class="mt-2 flex items-end gap-3">
                            <h4 class="text-title-xs sm:text-title-sm font-bold text-gray-800 dark:text-white/90">
                                <?php echo $activeCustomers; ?>
                            </h4>
                            <div>
                                <span
                                    class="bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500 flex items-center gap-1 rounded-full py-0.5 pr-2.5 pl-2 text-sm font-medium">
                                    +<?php echo round(($activeCustomers / $totalCustomers) * 100, 1); ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="border-b border-gray-200 px-6 py-5 sm:border-r sm:border-b-0 dark:border-gray-800">
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Suspended Customers</span>
                            <div class="mt-2 flex items-end gap-3">
                                <h4 class="text-title-xs sm:text-title-sm font-bold text-gray-800 dark:text-white/90">
                                    <?php echo $suspendedCustomers; ?>
                                </h4>
                                <div>
                                    <span
                                        class="bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500 flex items-center gap-1 rounded-full py-0.5 pr-2.5 pl-2 text-sm font-medium">
                                        <?php echo round(($suspendedCustomers / $totalCustomers) * 100, 1); ?>%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Deleted Customers</span>
                        <div class="mt-2 flex items-end gap-3">
                            <h4 class="text-title-xs sm:text-title-sm font-bold text-gray-800 dark:text-white/90">
                                <?php echo $deletedCustomers; ?>
                            </h4>
                            <div>
                                <span
                                    class="bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500 flex items-center gap-1 rounded-full py-0.5 pr-2.5 pl-2 text-sm font-medium">
                                    <?php echo round(($deletedCustomers / ($totalCustomers + $deletedCustomers)) * 100, 1); ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 mt-6">
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex flex-col gap-5 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Customer List</h3>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <form id="search-form">
                                <div class="relative">
                                    <span class="absolute -translate-y-1/2 pointer-events-none top-1/2 left-4">
                                        <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20"
                                            viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M3.04199 9.37381C3.04199 5.87712 5.87735 3.04218 9.37533 3.04218C12.8733 3.04218 15.7087 5.87712 15.7087 9.37381C15.7087 12.8705 12.8733 15.7055 9.37533 15.7055C5.87735 15.7055 3.04199 12.8705 3.04199 9.37381ZM9.37533 1.54218C5.04926 1.54218 1.54199 5.04835 1.54199 9.37381C1.54199 13.6993 5.04926 17.2055 9.37533 17.2055C11.2676 17.2055 13.0032 16.5346 14.3572 15.4178L17.1773 18.2381C17.4702 18.531 17.945 18.5311 18.2379 18.2382C18.5308 17.9453 18.5309 17.4704 18.238 17.1775L15.4182 14.3575C16.5367 13.0035 17.2087 11.2671 17.2087 9.37381C17.2087 5.04835 13.7014 1.54218 9.37533 1.54218Z"
                                                fill=""></path>
                                        </svg>
                                    </span>
                                    <input type="text" id="search-input" placeholder="Search..."
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-[42px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[300px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                                </div>
                            </form>
                            <div>
                                <button
                                    class="text-theme-sm shadow-theme-xs inline-flex h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                    <svg class="stroke-current fill-white dark:fill-gray-800" width="20" height="20"
                                        viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2.29004 5.90393H17.7067" stroke="" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path d="M17.7075 14.0961H2.29085" stroke="" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path
                                            d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z"
                                            fill="" stroke="" stroke-width="1.5"></path>
                                        <path
                                            d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z"
                                            fill="" stroke="" stroke-width="1.5"></path>
                                    </svg>
                                    Filter
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="max-w-full overflow-x-auto custom-scrollbar">
                        <table class="min-w-full">
                            <thead class="border-gray-100 border-y bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div x-data="{checked: false}" class="flex items-center gap-3">
                                                <div @click="checked = !checked"
                                                    class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700"
                                                    :class="checked ? 'border-brand-500 dark:border-brand-500 bg-brand-500' : 'bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700'">
                                                    <svg :class="checked ? 'block' : 'hidden'" width="14" height="14"
                                                        viewBox="0 0 14 14" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg" class="hidden">
                                                        <path d="M11.6668 3.5L5.25016 9.91667L2.3335 7" stroke="white"
                                                            stroke-width="1.94437" stroke-linecap="round"
                                                            stroke-linejoin="round"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <span
                                                        class="block font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer
                                                        ID</span>
                                                </div>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Customer
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Phone
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Country
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Address</p>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Joined
                                                Date</p>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Orders
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center justify-center">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Action
                                            </p>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="orders-table-body">
                                <?php if (count($customers) > 0): ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div x-data="{checked: false}" class="flex items-center gap-3">
                                                        <div @click="checked = !checked"
                                                            class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700"
                                                            :class="checked ? 'border-brand-500 dark:border-brand-500 bg-brand-500' : 'bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700'">
                                                            <svg :class="checked ? 'block' : 'hidden'" width="14" height="14"
                                                                viewBox="0 0 14 14" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg" class="hidden">
                                                                <path d="M11.6668 3.5L5.25016 9.91667L2.3335 7" stroke="white"
                                                                    stroke-width="1.94437" stroke-linecap="round"
                                                                    stroke-linejoin="round"></path>
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400"><?php echo $customer['id']; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex items-center gap-3">
                                                        <div
                                                            class="flex items-center justify-center w-10 h-10 rounded-full bg-brand-100">
                                                            <?php if (!empty($customer['profile_image'])): ?>
                                                                <span class="text-xs font-semibold text-brand-500"
                                                                    style="background: url('<?php echo $customer['profile_image']; ?>');"></span>
                                                            <?php else: ?>
                                                                <span
                                                                    class="text-xs font-semibold text-brand-500"><?php echo substr($customer['name'], 0, 2); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="text-theme-sm mb-0.5 block font-medium text-gray-700 dark:text-gray-400"><?php echo htmlspecialchars($customer['name']); ?>
                                                            </span>
                                                            <span
                                                                class="text-gray-500 text-theme-sm dark:text-gray-400"><?php echo htmlspecialchars($customer['email']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                                                        <?php echo htmlspecialchars($customer['phone']); ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                                                        <?php echo htmlspecialchars($customer['country']); ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                                                        <?php echo htmlspecialchars($customer['city']); ?>
                                                        =>
                                                        <?php echo htmlspecialchars($customer['address']); ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                                                        <?php echo date('Y-m-d H:i:s', strtotime($customer['created_at'])); ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                                                        <a class="button orders"
                                                            href="customer_orders.php?id=<?php echo $customer['id']; ?>">
                                                            <?php echo $customer['orders_count']; ?> Orders
                                                        </a>
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <p
                                                        class="bg-success-50 text-theme-xs text-success-600 dark:bg-success-500/15 dark:text-success-500 rounded-full px-2 py-0.5 font-medium bg-danger">
                                                        <?php echo ucfirst($customer['status']); ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a class="button toggle" href="?toggle=<?php echo $customer['id']; ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <?php if ($customer['status'] == 'active'): ?>
                                                                <path d="M18 6L6 18"></path>
                                                                <path d="M6 6l12 12"></path>
                                                            <?php else: ?>
                                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                                <path d="M22 4L12 14.01l-3-3"></path>
                                                            <?php endif; ?>
                                                        </svg>
                                                    </a>
                                                    <a class="button delete" href="?delete=<?php echo $customer['id']; ?>"
                                                        onclick="return confirm('Are you sure you want to delete this customer?')">
                                                        <svg class="cursor-pointer hover:fill-error-500 dark:hover:fill-error-500 fill-gray-700 dark:fill-gray-400"
                                                            width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M6.54142 3.7915C6.54142 2.54886 7.54878 1.5415 8.79142 1.5415H11.2081C12.4507 1.5415 13.4581 2.54886 13.4581 3.7915V4.0415H15.6252H16.666C17.0802 4.0415 17.416 4.37729 17.416 4.7915C17.416 5.20572 17.0802 5.5415 16.666 5.5415H16.3752V8.24638V13.2464V16.2082C16.3752 17.4508 15.3678 18.4582 14.1252 18.4582H5.87516C4.63252 18.4582 3.62516 17.4508 3.62516 16.2082V13.2464V8.24638V5.5415H3.3335C2.91928 5.5415 2.5835 5.20572 2.5835 4.7915C2.5835 4.37729 2.91928 4.0415 3.3335 4.0415H4.37516H6.54142V3.7915ZM14.8752 13.2464V8.24638V5.5415H13.4581H12.7081H7.29142H6.54142H5.12516V8.24638V13.2464V16.2082C5.12516 16.6224 5.46095 16.9582 5.87516 16.9582H14.1252C14.5394 16.9582 14.8752 16.6224 14.8752 16.2082V13.2464ZM8.04142 4.0415H11.9581V3.7915C11.9581 3.37729 11.6223 3.0415 11.2081 3.0415H8.79142C8.37721 3.0415 8.04142 3.37729 8.04142 3.7915V4.0415ZM8.3335 7.99984C8.74771 7.99984 9.0835 8.33562 9.0835 8.74984V13.7498C9.0835 14.1641 8.74771 14.4998 8.3335 14.4998C7.91928 14.4998 7.5835 14.1641 7.5835 13.7498V8.74984C7.5835 8.33562 7.91928 7.99984 8.3335 7.99984ZM12.4168 8.74984C12.4168 8.33562 12.081 7.99984 11.6668 7.99984C11.2526 7.99984 10.9168 8.33562 10.9168 8.74984V13.7498C10.9168 14.1641 11.2526 14.4998 11.6668 14.4998C12.081 14.4998 12.4168 14.1641 12.4168 13.7498V8.74984Z"
                                                                fill=""></path>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No
                                            customers found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bundle.js"></script>

</body>

</html>