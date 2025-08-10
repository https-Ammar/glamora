<?php
session_start();
require('../config/db.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

function uploadFile($file, $subdir, $index = null)
{
    $targetDir = UPLOAD_DIR . $subdir . '/';

    $fileError = $index !== null ? $file['error'][$index] : $file['error'];
    $fileName = $index !== null ? $file['name'][$index] : $file['name'];
    $fileSize = $index !== null ? $file['size'][$index] : $file['size'];
    $fileTmp = $index !== null ? $file['tmp_name'][$index] : $file['tmp_name'];

    if ($fileError !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileInfo = pathinfo($fileName);
    $ext = strtolower($fileInfo['extension'] ?? '');
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return null;
    }

    if ($fileSize > MAX_FILE_SIZE) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileTmp);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];

    if (!in_array($mime, $allowedMimes)) {
        return null;
    }

    $newFileName = uniqid($subdir . '_', true) . '.' . $ext;
    $targetPath = $targetDir . $newFileName;

    if (move_uploaded_file($fileTmp, $targetPath)) {
        return $targetPath;
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid request";
        header("Location: add_product.php");
        exit();
    }

    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $brand = filter_input(INPUT_POST, 'brand', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $tags = filter_input(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $discountPercent = filter_input(INPUT_POST, 'discount_percent', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $stockStatus = in_array($_POST['stock_status'] ?? '', ['in_stock', 'pre_order', 'out_of_stock']) ? $_POST['stock_status'] : 'in_stock';
    $isNew = isset($_POST['is_new']) ? 1 : 0;
    $onSale = isset($_POST['on_sale']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $barcode = !empty($_POST['barcode']) ? preg_replace('/[^A-Za-z0-9-]/', '', $_POST['barcode']) : uniqid('PRD-');
    $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $userId = $_SESSION['user_id'] ?? 1;

    if (empty($name) || empty($price) || $categoryId === false) {
        $_SESSION['error'] = "Please fill all required fields";
        header("Location: add_product.php");
        exit();
    }

    $salePrice = null;
    if ($discountPercent > 0) {
        $salePrice = $price - ($price * ($discountPercent / 100));
        $salePrice = round($salePrice, 2);
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)) . '-' . uniqid());

    $sizes = [];
    if (!empty($_POST['size_name']) && is_array($_POST['size_name'])) {
        foreach ($_POST['size_name'] as $index => $sizeName) {
            $sizeName = filter_var($sizeName, FILTER_SANITIZE_STRING);
            if (!empty($sizeName)) {
                $sizePrice = isset($_POST['size_price'][$index]) ? filter_var($_POST['size_price'][$index], FILTER_VALIDATE_FLOAT) : $price;
                $sizes[] = ['name' => $sizeName, 'price' => $sizePrice];
            }
        }
    }
    $sizesJson = !empty($sizes) ? json_encode($sizes, JSON_UNESCAPED_UNICODE) : null;

    $colors = [];
    if (!empty($_POST['color_name']) && is_array($_POST['color_name'])) {
        foreach ($_POST['color_name'] as $index => $colorName) {
            $colorName = filter_var($colorName, FILTER_SANITIZE_STRING);
            if (!empty($colorName)) {
                $colorHex = isset($_POST['color_hex'][$index]) ? preg_replace('/[^a-fA-F0-9#]/', '', $_POST['color_hex'][$index]) : '#000000';
                $colorData = ['name' => $colorName, 'hex' => $colorHex];

                if (!empty($_FILES['color_image']['name'][$index])) {
                    $colorImage = uploadFile($_FILES['color_image'], 'colors', $index);
                    if ($colorImage) {
                        $colorData['image'] = $colorImage;
                    }
                }

                $colors[] = $colorData;
            }
        }
    }
    $colorsJson = !empty($colors) ? json_encode($colors, JSON_UNESCAPED_UNICODE) : null;

    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadFile($_FILES['image'], 'products');
    }

    $galleryPaths = [];
    if (!empty($_FILES['gallery']['name'][0])) {
        foreach ($_FILES['gallery']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['gallery']['error'][$index] === UPLOAD_ERR_OK) {
                $imgPath = uploadFile($_FILES['gallery'], 'products/gallery', $index);
                if ($imgPath) {
                    $galleryPaths[] = $imgPath;
                }
            }
        }
    }
    $galleryJson = !empty($galleryPaths) ? json_encode($galleryPaths) : null;

    if ($imagePath) {
        try {
            $stmt = $conn->prepare("INSERT INTO products (
                name, slug, brand, description, tags, price, sale_price, discount_percent,
                quantity, stock_status, is_new, on_sale, is_featured, barcode,
                expiry_date, category_id, image, gallery, sizes, colors, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "sssssddddsiiisssssssi",
                $name,
                $slug,
                $brand,
                $description,
                $tags,
                $price,
                $salePrice,
                $discountPercent,
                $quantity,
                $stockStatus,
                $isNew,
                $onSale,
                $isFeatured,
                $barcode,
                $expiryDate,
                $categoryId,
                $imagePath,
                $galleryJson,
                $sizesJson,
                $colorsJson,
                $userId
            );

            if ($stmt->execute()) {
                $_SESSION['success'] = "Product added successfully!";
                header("Location: add_product.php");
                exit();
            } else {
                if ($imagePath && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                foreach ($galleryPaths as $path) {
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
                $_SESSION['error'] = "Error adding product: " . $conn->error;
            }

            $stmt->close();
        } catch (Exception $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Main product image is required";
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$categories = $conn->query("SELECT c1.id, c1.name AS child_name, c2.name AS parent_name 
                          FROM categories c1
                          LEFT JOIN categories c2 ON c1.parent_id = c2.id
                          WHERE c1.parent_id IS NOT NULL
                          ORDER BY c2.name, c1.name");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>add product</title>
    <link rel="stylesheet" href="../assets/css/main.css">
</head>


<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init="
         darkMode = JSON.parse(localStorage.getItem('darkMode'));
         $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
            <div x-data="{ pageName: `Add Product` }">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName">Add Product
                    </h2>
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
                            <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName">Add Product</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="space-y-6">
                <form method="POST" enctype="multipart/form-data" id="productForm">
                    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] ">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                            <h2 class="text-lg font-medium text-gray-800 dark:text-white">
                                Products Images
                            </h2>
                        </div>
                        <div class="p-4 sm:p-6">
                            <label for="image"
                                class="shadow-theme-xs group hover:border-brand-500 block cursor-pointer rounded-lg border-2 border-dashed border-gray-300 transition dark:border-gray-800">
                                <div class="flex justify-center p-10">
                                    <div class="flex max-w-[260px] flex-col items-center gap-4">
                                        <div
                                            class="inline-flex h-13 w-13 items-center justify-center rounded-full border border-gray-200 text-gray-700 transition dark:border-gray-800 dark:text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M20.0004 16V18.5C20.0004 19.3284 19.3288 20 18.5004 20H5.49951C4.67108 20 3.99951 19.3284 3.99951 18.5V16M12.0015 4L12.0015 16M7.37454 8.6246L11.9994 4.00269L16.6245 8.6246"
                                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round"></path>
                                            </svg>
                                        </div>
                                        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-800 dark:text-white/90">Click to
                                                upload</span>
                                            or drag and drop SVG, PNG, JPG or GIF (MAX. 800x400px)
                                        </p>
                                    </div>
                                </div>
                                <input type="file" id="image" name="image" class="hidden" required>
                            </label>
                        </div>
                    </div>

                    <!--  -->

                    <div
                        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mt-6">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                            <h2 class="text-lg font-medium text-gray-800 dark:text-white">
                                Products Description
                            </h2>
                        </div>
                        <div class="p-4 sm:p-6 dark:border-gray-800">


                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <label for="product-name"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Product Name
                                    </label>
                                    <input type="text" id="product-name" name="name"
                                        value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                                        required
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                        placeholder="Enter product name">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Category
                                    </label>
                                    <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                                        <select
                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                            :class="isOptionSelected &amp;&amp; 'text-gray-800 dark:text-white/90'"
                                            @change="isOptionSelected = true" id="category_id" name="category_id"
                                            required>
                                            <option value="">Category </option>
                                            <?php while ($category = $categories->fetch_assoc()): ?>
                                                <option value="<?= $category['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['parent_name'] . ' > ' . $category['child_name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <span
                                            class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                                            <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20"
                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke=""
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                </path>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Brand
                                    </label>
                                    <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                                        <input type="text"
                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                            id="brand" name="brand"
                                            value="<?= isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : '' ?>"
                                            placeholder="Brand">
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Availability Status
                                    </label>
                                    <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                                        <select
                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                            :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
                                            @change="isOptionSelected = true" id="stock_status" name="stock_status"
                                            required>
                                            <option value="in_stock" <?= (isset($_POST['stock_status']) && $_POST['stock_status'] == 'in_stock') ? 'selected' : '' ?>>In Stock
                                            </option>
                                            <option value="out_of_stock" <?= (isset($_POST['stock_status']) && $_POST['stock_status'] == 'out_of_stock') ? 'selected' : '' ?>>Out of
                                                Stock</option>
                                            <option value="pre_order" <?= (isset($_POST['stock_status']) && $_POST['stock_status'] == 'pre_order') ? 'selected' : '' ?>>Pre Order
                                            </option>
                                        </select>
                                        <span
                                            class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                                            <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20"
                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke=""
                                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                </path>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-span-full">
                                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                                        <div>
                                            <label for="price"
                                                class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">price
                                            </label>
                                            <input type="number" step="1" id="price" name="price"
                                                value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>"
                                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                                placeholder="15">
                                        </div>
                                        <div>
                                            <label for="discount_percent"
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">
                                                Discount
                                                ( <small id="salePricePreview" class="text-muted"></small> )
                                            </label>
                                            <input type="number" id="discount_percent" name="discount_percent" min="0"
                                                max="100"
                                                value="<?= isset($_POST['discount_percent']) ? htmlspecialchars($_POST['discount_percent']) : '' ?>"
                                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                                placeholder="0">
                                        </div>
                                        <div>
                                            <label for="quantity"
                                                class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">quantity
                                            </label>
                                            <input type="number" id="quantity" name="quantity"
                                                value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>"
                                                required
                                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                                placeholder="100">
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mt-6">
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div class="flex items-center gap-3">
                                                    <input type="checkbox" id="is_new" name="is_new"
                                                        <?= (isset($_POST['is_new']) && $_POST['is_new']) ? 'checked' : '' ?>
                                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600">
                                                    <label for="is_new"
                                                        class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                        New Product
                                                    </label>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <input type="checkbox" id="on_sale" name="on_sale"
                                                        <?= (isset($_POST['on_sale']) && $_POST['on_sale']) ? 'checked' : '' ?>
                                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600">
                                                    <label for="on_sale"
                                                        class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                        On Sale
                                                    </label>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <input type="checkbox" id="is_featured" name="is_featured"
                                                        <?= (isset($_POST['is_featured']) && $_POST['is_featured']) ? 'checked' : '' ?>
                                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600">
                                                    <label for="is_featured"
                                                        class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                        Featured Product
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label for="barcode"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Barcode
                                    </label>
                                    <div class="flex">
                                        <input type="text" id="barcode" name="barcode"
                                            value="<?= isset($_POST['barcode']) ? htmlspecialchars($_POST['barcode']) : '' ?>"
                                            required
                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-l-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                            placeholder="Enter barcode">
                                        <button type="button" id="generateBarcode"
                                            class="inline-flex items-center px-4 py-2.5 text-sm rounded-r-lg border border-l-0 border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/50">
                                            <i class="fas fa-barcode mr-2"></i> Generate
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="tags"
                                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Tags (separated by commas)
                                        </label>
                                        <div class="flex">
                                            <input type="text" id="tags" name="tags"
                                                value="<?= isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : '' ?>"
                                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                                placeholder="e.g. product, new, special offer">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-full mb-6">
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Description
                                    </label>
                                    <textarea placeholder="Receipt Info (optional)" id="description" name="description"
                                        rows="4" required
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full resize-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                                </div>
                                <input type="hidden" name="add_product" value="1">
                            </div>
                        </div>
                    </div>
                    <div
                        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mt-6">

                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800  flex justify-between">
                            <h2 class="text-lg font-medium text-gray-800 dark:text-white">
                                Color Size
                            </h2>

                            <div class="flex  mt-4 gap-3">
                                <button type="button" class="btn btn-primary" id="addSize">
                                    <!-- أيقونة Plus (للحجم) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>

                                <button type="button" class="btn btn-primary" id="addColor">
                                    <!-- أيقونة Palette (للألوان) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 3C7.03 3 3 7.03 3 12c0 2.67 1.32 5.02 3.34 6.5-.17-.47-.26-.98-.26-1.5 0-2.21 1.79-4 4-4 .52 0 1.03.09 1.5.26C13.98 14.68 16.33 13.36 19 13.36 20.66 13.36 22 11.1 22 9.5 22 5.36 17.52 3 12 3z" />
                                    </svg>
                                </button>
                            </div>
                        </div>


                        <div class="p-4 sm:p-6">



                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div id="sizesContainer">
                                                <?php if (isset($_POST['size_name']) && is_array($_POST['size_name'])): ?>
                                                    <?php foreach ($_POST['size_name'] as $index => $sizeName): ?>
                                                        <?php if (!empty($sizeName)): ?>
                                                            <div class="size-item mb-2">
                                                                <div class="row">
                                                                    <div class="col-md-5">
                                                                        <input type="text" class="form-control" name="size_name[]"
                                                                            placeholder="اسم الحجم"
                                                                            value="<?= htmlspecialchars($sizeName) ?>" required>
                                                                    </div>
                                                                    <div class="col-md-5">
                                                                        <div class="input-group">
                                                                            <input type="number" step="0.01" class="form-control"
                                                                                name="size_price[]" placeholder="السعر"
                                                                                value="<?= isset($_POST['size_price'][$index]) ? htmlspecialchars($_POST['size_price'][$index]) : '' ?>">
                                                                            <span class="input-group-text">ر.س</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <button type="button"
                                                                            class="btn btn-danger btn-sm w-100 remove-size">
                                                                            <i class="fas fa-trash"></i> حذف
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div id="colorsContainer">
                                                <?php
                                                $saved_colors = $_SESSION['product_colors'] ?? [];
                                                foreach ($saved_colors as $index => $color): ?>
                                                    <div class="color-item mb-3 p-3 border rounded">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-3 mb-2 mb-md-0">
                                                                <label class="form-label">اسم اللون</label>
                                                                <input type="text" class="form-control" name="color_name[]"
                                                                    value="<?= htmlspecialchars($color['name']) ?>"
                                                                    required>
                                                            </div>
                                                            <div class="col-md-2 mb-2 mb-md-0">
                                                                <label class="form-label">كود اللون</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text color-preview"
                                                                        style="background-color: <?= $color['hex'] ?>"></span>
                                                                    <input type="color"
                                                                        class="form-control form-control-color p-1"
                                                                        name="color_hex[]" value="<?= $color['hex'] ?>">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4 mb-2 mb-md-0">
                                                                <label class="form-label">صورة اللون</label>
                                                                <div class="d-flex align-items-center">
                                                                    <input type="file" class="form-control"
                                                                        name="color_image[]" accept="image/*">
                                                                    <?php if (!empty($color['image'])): ?>
                                                                        <img src="<?= $color['image'] ?>" width="40" height="40"
                                                                            class="rounded ms-2 border">
                                                                        <input type="hidden" name="existing_image[]"
                                                                            value="<?= $color['image'] ?>">
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2 mb-2 mb-md-0">
                                                                <button type="button"
                                                                    class="btn btn-danger btn-sm w-100 remove-color">
                                                                    <i class="fas fa-trash"></i> حذف
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <!--  -->
                    <!--  -->
                    <!--  -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mt-6">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                            <h2 class="text-lg font-medium text-gray-800 dark:text-white">
                                Exhibition photos
                            </h2>
                        </div>
                        <div class="p-4 sm:p-6">
                            <label for="gallery"
                                class="shadow-theme-xs group hover:border-brand-500 block cursor-pointer rounded-lg border-2 border-dashed border-gray-300 transition dark:border-gray-800">
                                <div class="flex justify-center p-10">
                                    <div class="flex max-w-[260px] flex-col items-center gap-4">
                                        <div
                                            class="inline-flex h-13 w-13 items-center justify-center rounded-full border border-gray-200 text-gray-700 transition dark:border-gray-800 dark:text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M20.0004 16V18.5C20.0004 19.3284 19.3288 20 18.5004 20H5.49951C4.67108 20 3.99951 19.3284 3.99951 18.5V16M12.0015 4L12.0015 16M7.37454 8.6246L11.9994 4.00269L16.6245 8.6246"
                                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round"></path>
                                            </svg>
                                        </div>
                                        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium text-gray-800 dark:text-white/90">Click to
                                                upload</span>
                                            or drag and drop SVG, PNG, JPG or GIF (MAX. 800x400px)
                                        </p>
                                    </div>
                                </div>
                                <input type="file" id="gallery" name="gallery[]" multiple accept="image/*"
                                    class="hidden">
                            </label>
                            <div id="galleryPreview" class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                            </div>
                            <input type="hidden" id="removedImages" name="removedImages" value="">
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mt-6">

                        <div class="p-4 sm:p-6">


                            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                <button type="submit"
                                    class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                                    Draft
                                </button>
                                <button type="submit"
                                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition">
                                    Publish Product
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bundle.js"></script>
    <script>
        document.getElementById('addColor').addEventListener('click', function () {
            const container = document.getElementById('colorsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'color-item mb-3 p-3 border rounded';
            newItem.innerHTML = `


            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                                        <div>
                                            <label for="Color_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Color Name
                                            </label>
                                            <input type="text" name="color_name[]" required  id="Color_name" name="price" value="" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="black">
                                        </div>

                                        <div>
                                            <label for="Color_Code"  class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">
                                     Color Code
                                  
                                            </label>
                                            <input type="color" id="Color_Code"   name="color_hex[]" value="#000000" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="120">
                                        </div>
                                        <div>
                                            <label for="color_image" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">color image
                                            </label>
                                            <input type="file" id="color_image"  name="color_image[]" accept="image/*" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="100">
                                        </div>
                                    </div>





            <div class="row align-items-center">
           
           
         
                <div class="col-md-2 mb-2 mb-md-0">
                    <button type="button" class="btn btn-danger btn-sm w-100 remove-color">
           <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
  <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
</svg>
                    </button>
                </div>
            </div>
        `;

            container.appendChild(newItem);

            newItem.querySelector('input[type="color"]').addEventListener('input', function () {
                this.previousElementSibling.style.backgroundColor = this.value;
            });

            newItem.querySelector('.remove-color').addEventListener('click', function () {
                newItem.remove();
            });
        });

        document.getElementById('image').addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const preview = document.getElementById('imagePreview');
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                }
                reader.readAsDataURL(file);
            }
        });

        const galleryInput = document.getElementById('gallery');
        const galleryPreview = document.getElementById('galleryPreview');
        const removedImagesInput = document.getElementById('removedImages');
        const removedImages = [];

        galleryInput.addEventListener('change', function (event) {
            const files = event.target.files;
            galleryPreview.innerHTML = '';

            if (files) {
                for (let i = 0; i < files.length; i++) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const imgContainer = document.createElement('div');
                        imgContainer.style.position = 'relative';
                        imgContainer.style.display = 'inline-block';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'gallery-thumbnail';

                        const removeBtn = document.createElement('span');
                        removeBtn.className = 'remove-image';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.onclick = function () {
                            removedImages.push(files[i].name);
                            removedImagesInput.value = JSON.stringify(removedImages);
                            imgContainer.remove();
                        };

                        imgContainer.appendChild(img);
                        imgContainer.appendChild(removeBtn);
                        galleryPreview.appendChild(imgContainer);
                    }
                    reader.readAsDataURL(files[i]);
                }
            }
        });

        function calculateSalePrice() {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const discount = parseFloat(document.getElementById('discount_percent').value) || 0;

            if (discount > 0 && discount <= 100) {
                const salePrice = price - (price * (discount / 100));
                document.getElementById('salePricePreview').textContent =
                    `Sale Price: ${salePrice.toFixed(2)} SAR (${discount}% off)`;
            } else {
                document.getElementById('salePricePreview').textContent = '';
            }
        }

        document.getElementById('price').addEventListener('input', calculateSalePrice);
        document.getElementById('discount_percent').addEventListener('input', calculateSalePrice);

        document.getElementById('generateBarcode').addEventListener('click', function () {
            const randomBarcode = 'PRD-' + Math.floor(100000 + Math.random() * 900000);
            document.getElementById('barcode').value = randomBarcode;
        });

        document.getElementById('addSize').addEventListener('click', function () {
            const sizeItem = document.createElement('div');
            sizeItem.className = 'size-item mb-2';
            sizeItem.innerHTML = `


            <div>
                                    <label for="Size" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        Barcode
                                    </label>
                                    <div class="flex">
                                        <input type="text" id="Size" name="size_name[]" placeholder="Size name" required class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-l-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="Enter barcode">
                                        <button type="button"  class=" remove-size inline-flex items-center px-4 py-2.5 text-sm rounded-r-lg border border-l-0 border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/50">
                                                <i class="fas fa-trash"></i>  Remove
                                        </button>
                                    </div>
                                </div>


     
        `;
            document.getElementById('sizesContainer').appendChild(sizeItem);

            sizeItem.querySelector('.remove-size').addEventListener('click', function () {
                sizeItem.remove();
            });
        });

        document.querySelectorAll('.remove-size').forEach(btn => {
            btn.addEventListener('click', function () {
                this.closest('.size-item').remove();
            });
        });

        document.querySelectorAll('.remove-color').forEach(btn => {
            btn.addEventListener('click', function () {
                this.closest('.color-item').remove();
            });
        });

        document.querySelectorAll('input[type="color"]').forEach(input => {
            const preview = input.previousElementSibling;
            if (preview) {
                preview.style.backgroundColor = input.value;
                input.addEventListener('input', function () {
                    preview.style.backgroundColor = this.value;
                });
            }
        });

        document.getElementById('productForm').addEventListener('submit', function (e) {
            const price = parseFloat(document.getElementById('price').value);
            if (price <= 0) {
                alert('Price must be greater than zero');
                e.preventDefault();
                return false;
            }

            const quantity = parseInt(document.getElementById('quantity').value);
            if (quantity < 0) {
                alert('Quantity cannot be negative');
                e.preventDefault();
                return false;
            }
            return true;
        });
    </script>
</body>

</html>