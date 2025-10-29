<?php
session_start();
require('../config/db.php');

if (!isset($_SESSION['userId'])) {
    header('Location: ../auth/signin.php');
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM cart WHERE productid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header('Location: products.php?success=deleted');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link rel="stylesheet" href="../assets/css/main.css">
</head>

<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init="
         darkMode = JSON.parse(localStorage.getItem('darkMode'));
         $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}">

    
    <main>



        <div class="mx-auto max-w-(--breakpoint-2xl) px-5 py-4 md:p-6">

            <div>
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-6">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Products</h2>
                        <nav>
                            <ol class="flex items-center gap-1.5">
                                <li>
                                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                                        href="index.html">
                                        Home
                                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16"
                                            fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke=""
                                                stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                                            </path>
                                        </svg>
                                    </a>
                                </li>
                                <li class="text-sm text-gray-800 dark:text-white/90">Products</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <div>
                    <div
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                        <div
                            class="flex flex-col justify-between gap-5 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center dark:border-gray-800">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                    Products List
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Track your store's progress to boost your sales.
                                </p>
                            </div>
                            <div class="flex gap-3">
                                <a href="./add_product.php">
                                    <button
                                        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            viewBox="0 0 20 20" fill="none">
                                            <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor"
                                                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            </path>
                                        </svg>
                                        Add Product
                                    </button>
                                </a>
                            </div>
                        </div>

                        <div class="custom-scrollbar overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                                        <th class="w-14 px-5 py-4 text-left">
                                            <label
                                                class="cursor-pointer text-sm font-medium text-gray-700 select-none dark:text-gray-400">
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
                                            </label>
                                        </th>
                                        <th
                                            class="cursor-pointer px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <div class="flex items-center gap-3">
                                                <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Products
                                                </p>
                                                <span class="flex flex-col gap-0.5">
                                                    <svg width="8" height="5" viewBox="0 0 8 5" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="text-gray-500 dark:text-gray-400">
                                                        <path
                                                            d="M4.40962 0.585167C4.21057 0.300808 3.78943 0.300807 3.59038 0.585166L1.05071 4.21327C0.81874 4.54466 1.05582 5 1.46033 5H6.53967C6.94418 5 7.18126 4.54466 6.94929 4.21327L4.40962 0.585167Z"
                                                            fill="currentColor"></path>
                                                    </svg>
                                                    <svg width="8" height="5" viewBox="0 0 8 5" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="text-gray-300 dark:text-gray-400/50">
                                                        <path
                                                            d="M4.40962 4.41483C4.21057 4.69919 3.78943 4.69919 3.59038 4.41483L1.05071 0.786732C0.81874 0.455343 1.05582 0 1.46033 0H6.53967C6.94418 0 7.18126 0.455342 6.94929 0.786731L4.40962 4.41483Z"
                                                            fill="currentColor"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                        </th>
                                        <th
                                            class="cursor-pointer px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <div class="flex items-center gap-3">
                                                <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Category
                                                </p>
                                            </div>
                                        </th>
                                        <th
                                            class="cursor-pointer px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <div class="flex items-center gap-3">
                                                <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Count
                                                </p>
                                            </div>
                                        </th>
                                        <th
                                            class="cursor-pointer px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <div class="flex items-center gap-3">
                                                <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                    discount
                                                </p>
                                            </div>
                                        </th>
                                        <th
                                            class="cursor-pointer px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <div class="flex items-center gap-3">
                                                <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Price
                                                </p>
                                            </div>
                                        </th>
                                        <th
                                            class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                            Stock
                                        </th>
                                        <th
                                            class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                            Created At
                                        </th>
                                        <th
                                            class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <div class="relative">
                                                <span class="sr-only">Action</span>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-x divide-y divide-gray-200 dark:divide-gray-800">
                                    <?php
                                    $products = $conn->query("
                                SELECT p.*, c.name as category_name 
                                FROM products p
                                LEFT JOIN categories c ON p.category_id = c.id
                                ORDER BY p.id DESC
                            ");
                                    while ($product = $products->fetch_assoc()) {
                                        $createdAt = new DateTime($product['created_at']);
                                        $formattedDate = $createdAt->format('Y-m-d H:i:s');
                                        ?>
                                        <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-900">
                                            <td class="w-14 px-5 py-4 whitespace-nowrap">
                                                <label
                                                    class="cursor-pointer text-sm font-medium text-gray-700 select-none dark:text-gray-400">
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
                                                </label>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-12 w-12">
                                                        <img src="<?= htmlspecialchars($product['image']) ?>"
                                                            class="h-12 w-12 rounded-md" alt="">
                                                    </div>
                                                    <span
                                                        class="text-sm font-medium text-gray-700 dark:text-gray-400"><?= htmlspecialchars($product['name']) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    <?= htmlspecialchars($product['category_name'] ?? 'No Category') ?>
                                                </p>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    <?= (int) $product['quantity'] ?>
                                                </p>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <p class="text-sm text-gray-700 dark:text-gray-400"> <?= ($product['discount_percent'] > 0) ?
                                                    $product['discount_percent'] . '%' : '-' ?></p>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <p class="text-sm text-gray-700 dark:text-gray-400">
                                                    $ <?= number_format($product['price'], 2) ?></p>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <span class="text-theme-xs rounded-full px-2 py-0.5 font-medium 
        <?= $product['stock_status'] === 'in_stock' ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500' :
            ($product['stock_status'] === 'pre_order' ? 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-500' :
                'bg-danger-50 text-danger-700 dark:bg-danger-500/15 dark:text-danger-500') ?>">
                                                    <?= $product['stock_status'] === 'in_stock' ? 'In Stock' :
                                                        ($product['stock_status'] === 'pre_order' ? 'Pre Order' : 'Out of Stock') ?>
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <p class="text-sm text-gray-700 dark:text-gray-400">
                                                    <?= date('Y-m-d', strtotime($formattedDate)) ?>
                                                </p>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <div class="relative flex justify-center">
                                                    <a href="?id=<?= $product['id'] ?>"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Are you sure you want to delete this product?')">
                                                        <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M5.99902 10.245C6.96552 10.245 7.74902 11.0285 7.74902 11.995V12.005C7.74902 12.9715 6.96552 13.755 5.99902 13.755C5.03253 13.755 4.24902 12.9715 4.24902 12.005V11.995C4.24902 11.0285 5.03253 10.245 5.99902 10.245ZM17.999 10.245C18.9655 10.245 19.749 11.0285 19.749 11.995V12.005C19.749 12.9715 18.9655 13.755 17.999 13.755C17.0325 13.755 16.249 12.9715 16.249 12.005V11.995C16.249 11.0285 17.0325 10.245 17.999 10.245ZM13.749 11.995C13.749 11.0285 12.9655 10.245 11.999 10.245C11.0325 10.245 10.249 11.0285 10.249 11.995V12.005C10.249 12.9715 11.0325 13.755 11.999 13.755C12.9655 13.755 13.749 12.9715 13.749 12.005V11.995Z"
                                                                fill=""></path>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div
                            class="flex flex-col items-center justify-between border-t border-gray-200 px-5 py-4 sm:flex-row dark:border-gray-800">
                            <div class="pb-3 sm:pb-0">
                                <span class="block text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Showing
                                    <span class="text-gray-800 dark:text-white/90">1</span>
                                    to
                                    <span class="text-gray-800 dark:text-white/90">2</span>
                                    of
                                    <span class="text-gray-800 dark:text-white/90">2</span>
                                </span>
                            </div>
                            <div
                                class="flex w-full items-center justify-between gap-2 rounded-lg bg-gray-50 p-4 sm:w-auto sm:justify-normal sm:rounded-none sm:bg-transparent sm:p-0 dark:bg-gray-900 dark:sm:bg-transparent">
                                <button
                                    class="shadow-theme-xs flex items-center gap-2 rounded-lg border border-gray-300 bg-white p-2 text-gray-700 hover:bg-gray-50 hover:text-gray-800 disabled:cursor-not-allowed disabled:opacity-50 sm:p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
                                    disabled="disabled">
                                    <span>
                                        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M2.58203 9.99868C2.58174 10.1909 2.6549 10.3833 2.80152 10.53L7.79818 15.5301C8.09097 15.8231 8.56584 15.8233 8.85883 15.5305C9.15183 15.2377 9.152 14.7629 8.85921 14.4699L5.13911 10.7472L16.6665 10.7472C17.0807 10.7472 17.4165 10.4114 17.4165 9.99715C17.4165 9.58294 17.0807 9.24715 16.6665 9.24715L5.14456 9.24715L8.85919 5.53016C9.15199 5.23717 9.15184 4.7623 8.85885 4.4695C8.56587 4.1767 8.09099 4.17685 7.79819 4.46984L2.84069 9.43049C2.68224 9.568 2.58203 9.77087 2.58203 9.99715C2.58203 9.99766 2.58203 9.99817 2.58203 9.99868Z">
                                            </path>
                                        </svg>
                                    </span>
                                </button>
                                <span class="block text-sm font-medium text-gray-700 sm:hidden dark:text-gray-400">
                                    Page <span>1</span> of
                                    <span>1</span>
                                </span>
                                <ul class="hidden items-center gap-0.5 sm:flex">
                                    <li>
                                        <a href="#"
                                            class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium bg-brand-500 text-white">
                                            <span>1</span>
                                        </a>
                                    </li>
                                </ul>
                                <button
                                    class="shadow-theme-xs flex items-center gap-2 rounded-lg border border-gray-300 bg-white p-2 text-gray-700 hover:bg-gray-50 hover:text-gray-800 disabled:cursor-not-allowed disabled:opacity-50 sm:p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
                                    disabled="disabled">
                                    <span>
                                        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M17.4165 9.9986C17.4168 10.1909 17.3437 10.3832 17.197 10.53L12.2004 15.5301C11.9076 15.8231 11.4327 15.8233 11.1397 15.5305C10.8467 15.2377 10.8465 14.7629 11.1393 14.4699L14.8594 10.7472L3.33203 10.7472C2.91782 10.7472 2.58203 10.4114 2.58203 9.99715C2.58203 9.58294 2.91782 9.24715 3.33203 9.24715L14.854 9.24715L11.1393 5.53016C10.8465 5.23717 10.8467 4.7623 11.1397 4.4695C11.4327 4.1767 11.9075 4.17685 12.2003 4.46984L17.1578 9.43049C17.3163 9.568 17.4165 9.77087 17.4165 9.99715C17.4165 9.99763 17.4165 9.99812 17.4165 9.9986Z">
                                            </path>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bundle.js"></script>

    <script>
        $('#searchBtn').click(function () {
            searchProducts();
        });

        $('#searchInput').keypress(function (e) {
            if (e.which == 13) {
                searchProducts();
            }
        });

        function searchProducts() {
            const searchTerm = $('#searchInput').val().trim();
            if (searchTerm.length > 0) {
                $.ajax({
                    url: 'search_products.php',
                    type: 'GET',
                    data: { q: searchTerm },
                    success: function (data) {
                        $('#productTableBody').html(data);
                    },
                    error: function (xhr, status, error) {
                        console.error('Search error:', error);
                    }
                });
            }
        }
    </script>
</body>

</html>