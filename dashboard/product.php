<?php
session_start();
require('./db.php');

$userid = $_SESSION['userId'];
$select = $conn->prepare("SELECT * FROM usersadmin WHERE id = ?");
$select->bind_param("i", $userid);
$select->execute();
$fetchname = $select->get_result()->fetch_assoc();
$select->close();

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

        header('Location: products.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = intval($_POST['product_id']);
    $name = $_POST['name'];
    $brand = $_POST['brand'];
    $price = floatval($_POST['price']);
    $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : NULL;
    $quantity = intval($_POST['quantity']);
    $category_id = intval($_POST['category_id']);
    $stock_status = $_POST['stock_status'];
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE products SET name=?, brand=?, price=?, sale_price=?, quantity=?, category_id=?, stock_status=?, is_new=?, is_featured=? WHERE id=?");
    $stmt->bind_param("ssddiisiii", $name, $brand, $price, $sale_price, $quantity, $category_id, $stock_status, $is_new, $is_featured, $product_id);
    $stmt->execute();
    $stmt->close();

    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Tajawal', sans-serif;
        }

        .navbar {
            background-color: #343a40;
        }

        .navbar-brand {
            font-weight: 700;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #6c757d;
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .action-btns .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .edit-form {
            display: none;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Products Management</h5>
                        <a href="add_product.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="productSearch" class="form-control ps-4"
                                placeholder="Search for product...">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>Brand</th>
                                        <th>Price</th>
                                        <th>Sale Price</th>
                                        <th>Quantity</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="productTableBody">
                                    <?php
                                    $products = $conn->query("SELECT p.*, c.name as category_name, 
                                    (SELECT COUNT(*) FROM products WHERE category_id = p.category_id) as category_count 
                                    FROM products p 
                                    LEFT JOIN categories c ON p.category_id = c.id 
                                    ORDER BY p.id DESC");

                                    while ($product = $products->fetch_assoc()):
                                        $stock_status = '';
                                        $badge_class = '';
                                        switch ($product['stock_status']) {
                                            case 'in_stock':
                                                $stock_status = 'In Stock';
                                                $badge_class = 'bg-success';
                                                break;
                                            case 'pre_order':
                                                $stock_status = 'Pre Order';
                                                $badge_class = 'bg-warning';
                                                break;
                                            case 'out_of_stock':
                                                $stock_status = 'Out of Stock';
                                                $badge_class = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td><?= $product['id'] ?></td>
                                            <td>
                                                <img src="<?= htmlspecialchars($product['image']) ?>"
                                                    alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
                                            </td>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td><?= htmlspecialchars($product['brand']) ?></td>
                                            <td><?= number_format($product['price'], 2) ?> EGP</td>
                                            <td>
                                                <?= ($product['sale_price'] !== null && $product['sale_price'] !== '') ?
                                                    number_format($product['sale_price'], 2) . ' EGP' : '-' ?>
                                            </td>
                                            <td><?= (int) $product['quantity'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($product['category_name'] ?? 'No Category') ?>
                                                <span class="badge bg-secondary"><?= $product['category_count'] ?>
                                                    products</span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $badge_class ?>"><?= $stock_status ?></span>
                                                <?php if ($product['is_new']): ?>
                                                    <span class="badge bg-info">New</span>
                                                <?php endif; ?>
                                                <?php if ($product['is_featured']): ?>
                                                    <span class="badge bg-primary">Featured</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-btns">
                                                <button onclick="showEditForm(<?= $product['id'] ?>)"
                                                    class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="products.php?id=<?= $product['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger" title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this product?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr id="edit-form-<?= $product['id'] ?>" class="edit-form">
                                            <td colspan="10">
                                                <form method="POST" action="products.php">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label>Product Name</label>
                                                            <input type="text" name="name" class="form-control"
                                                                value="<?= htmlspecialchars($product['name']) ?>" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label>Brand</label>
                                                            <input type="text" name="brand" class="form-control"
                                                                value="<?= htmlspecialchars($product['brand']) ?>" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label>Price</label>
                                                            <input type="number" step="0.01" name="price"
                                                                class="form-control" value="<?= $product['price'] ?>"
                                                                required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label>Sale Price</label>
                                                            <input type="number" step="0.01" name="sale_price"
                                                                class="form-control" value="<?= $product['sale_price'] ?>">
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label>Quantity</label>
                                                            <input type="number" name="quantity" class="form-control"
                                                                value="<?= $product['quantity'] ?>" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label>Category</label>
                                                            <select name="category_id" class="form-control" required>
                                                                <?php
                                                                $categories = $conn->query("SELECT * FROM categories");
                                                                while ($category = $categories->fetch_assoc()):
                                                                    $selected = $category['id'] == $product['category_id'] ? 'selected' : '';
                                                                    ?>
                                                                    <option value="<?= $category['id'] ?>" <?= $selected ?>>
                                                                        <?= htmlspecialchars($category['name']) ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-3">
                                                        <div class="col-md-3">
                                                            <label>Stock Status</label>
                                                            <select name="stock_status" class="form-control" required>
                                                                <option value="in_stock"
                                                                    <?= $product['stock_status'] == 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                                                                <option value="pre_order"
                                                                    <?= $product['stock_status'] == 'pre_order' ? 'selected' : '' ?>>Pre Order</option>
                                                                <option value="out_of_stock"
                                                                    <?= $product['stock_status'] == 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-check mt-4">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="is_new" id="is_new_<?= $product['id'] ?>"
                                                                    <?= $product['is_new'] ? 'checked' : '' ?>>
                                                                <label class="form-check-label"
                                                                    for="is_new_<?= $product['id'] ?>">New Product</label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="is_featured"
                                                                    id="is_featured_<?= $product['id'] ?>"
                                                                    <?= $product['is_featured'] ? 'checked' : '' ?>>
                                                                <label class="form-check-label"
                                                                    for="is_featured_<?= $product['id'] ?>">Featured
                                                                    Product</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 text-end mt-4">
                                                            <button type="button"
                                                                onclick="hideEditForm(<?= $product['id'] ?>)"
                                                                class="btn btn-secondary">Cancel</button>
                                                            <button type="submit" name="edit_product"
                                                                class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('productSearch').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productTableBody tr:not(.edit-form)');

            rows.forEach(row => {
                const name = row.cells[2].textContent.toLowerCase();
                const brand = row.cells[3].textContent.toLowerCase();

                if (name.includes(filter) || brand.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function showEditForm(productId) {
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
            });
            document.getElementById('edit-form-' + productId).style.display = 'table-row';
        }

        function hideEditForm(productId) {
            document.getElementById('edit-form-' + productId).style.display = 'none';
        }

        function confirmDelete() {
            return confirm('Are you sure you want to delete this product? All related data will be deleted.');
        }
    </script>
</body>

</html>