<?php
session_start();
require('../config/db.php');

if (!isset($_SESSION['userId'])) {
    header('Location: ../auth/signin.php');
    exit();
}


$userid = $_SESSION['userId'];
$select = $conn->prepare("SELECT * FROM usersadmin WHERE id = ?");
$select->bind_param("i", $userid);
$select->execute();
$fetchname = $select->get_result()->fetch_assoc();
$select->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $categoryId = intval($_POST['delete_id']);

    $conn->begin_transaction();

    try {
        $stmt1 = $conn->prepare("DELETE FROM products WHERE category_id = ?");
        $stmt1->bind_param("i", $categoryId);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("DELETE FROM ads WHERE categoryid = ?");
        $stmt2->bind_param("i", $categoryId);
        $stmt2->execute();
        $stmt2->close();

        $stmt3 = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt3->bind_param("i", $categoryId);
        $stmt3->execute();
        $stmt3->close();

        $conn->commit();
        $_SESSION['success'] = "Category deleted successfully";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to delete category: " . $e->getMessage();
    }

    header('Location: categories.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null;
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : null;
    
    if (empty($name)) {
        $_SESSION['error'] = "Please enter category name";
        header('Location: categories.php');
        exit();
    }
    
    $imagePath = null;
    if ($parent_id === null) {
        if ($edit_id && empty($_FILES['image']['name'])) {
            $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $imagePath = $result['image'];
            $stmt->close();
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $targetDir = '../uploads/categories/';
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = uniqid('', true) . '.' . $ext;
            $targetPath = $targetDir . $imageName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $_SESSION['error'] = "Failed to upload image";
                header('Location: categories.php');
                exit();
            }
            $imagePath = $targetPath;
        } elseif (!$edit_id) {
            $_SESSION['error'] = "Category image is required for main categories";
            header('Location: categories.php');
            exit();
        }
    }

    if ($edit_id) {
        if ($parent_id === null && $imagePath) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, image = ?, parent_id = NULL WHERE id = ?");
            $stmt->bind_param("ssi", $name, $imagePath, $edit_id);
        } elseif ($parent_id === null) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, parent_id = NULL WHERE id = ?");
            $stmt->bind_param("si", $name, $edit_id);
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, parent_id = ?, image = NULL WHERE id = ?");
            $stmt->bind_param("sii", $name, $parent_id, $edit_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update category";
        }
        $stmt->close();
    } else {
        if ($parent_id === null) {
            $stmt = $conn->prepare("INSERT INTO categories (name, image) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $imagePath);
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $parent_id);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "Category added successfully";
        } else {
            $_SESSION['error'] = "Failed to add category";
        }
        $stmt->close();
    }

    header('Location: categories.php');
    exit();
}

$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_category = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$mainCategoriesCount = $conn->query("SELECT COUNT(*) as count FROM categories WHERE parent_id IS NULL")->fetch_assoc()['count'];
$subCategoriesCount = $conn->query("SELECT COUNT(*) as count FROM categories WHERE parent_id IS NOT NULL")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management</title>

</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-12">
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Categories Management</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $mainCategoriesCount; ?></div>
                            <div class="stats-label">Main Categories</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $subCategoriesCount; ?></div>
                            <div class="stats-label">Sub Categories</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php echo isset($edit_category) ? 'Edit Category' : 'Add New Category'; ?>
                                    <?php if (isset($edit_category)): ?>
                                        <a href="categories.php" class="btn btn-sm btn-outline-secondary float-end">Cancel</a>
                                    <?php endif; ?>
                                </h5>
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <?php if (isset($edit_category)): ?>
                                        <input type="hidden" name="edit_id" value="<?= $edit_category['id'] ?>">
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Category Name*</label>
                                        <input type="text" class="form-control" name="name" required
                                            value="<?= isset($edit_category) ? htmlspecialchars($edit_category['name']) : '' ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="parent_id" class="form-label">Parent Category (optional)</label>
                                        <select class="form-select" name="parent_id" id="parent_id">
                                            <option value="">No parent (main category)</option>
                                            <?php
                                            $parents = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
                                            while ($parent = $parents->fetch_assoc()):
                                                ?>
                                                <option value="<?= $parent['id'] ?>"
                                                    <?= (isset($edit_category) && $edit_category['parent_id'] == $parent['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($parent['name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3" id="image-field">
                                        <label for="image" class="form-label">
                                            Category Image*
                                            <?php if (isset($edit_category) && $edit_category['image']): ?>
                                                <small class="text-muted">(leave empty to keep current image)</small>
                                            <?php endif; ?>
                                        </label>
                                        <input type="file" class="form-control" name="image" id="image" accept="image/*" 
                                            <?= (!isset($edit_category) || $edit_category['parent_id'] === null) ? '' : 'disabled' ?>>
                                        
                                        <?php if (isset($edit_category) && $edit_category['image'] && $edit_category['parent_id'] === null): ?>
                                            <div class="mt-2">
                                                <img src="<?= htmlspecialchars($edit_category['image']) ?>" 
                                                     alt="Current category image" 
                                                     style="max-width: 100px; max-height: 100px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo isset($edit_category) ? 'Update Category' : 'Add Category'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="main-tab" data-bs-toggle="tab" data-bs-target="#main-categories" type="button" role="tab">Main Categories</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="sub-tab" data-bs-toggle="tab" data-bs-target="#sub-categories" type="button" role="tab">Sub Categories</button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="categoryTabsContent">
                                    <div class="tab-pane fade show active" id="main-categories" role="tabpanel">
                                        <div class="table-responsive mt-3">
                                            <table class="table table-striped align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Name</th>
                                                        <th>Image</th>
                                                        <th>Subcategories</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $mainCategories = $conn->query("
                                                        SELECT c.*, 
                                                               (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as subcategory_count
                                                        FROM categories c 
                                                        WHERE c.parent_id IS NULL 
                                                        ORDER BY c.name ASC
                                                    ");
                                                    while ($category = $mainCategories->fetch_assoc()):
                                                        ?>
                                                        <tr>
                                                            <td><?= $category['id'] ?></td>
                                                            <td><?= htmlspecialchars($category['name']) ?></td>
                                                            <td>
                                                                <?php if ($category['image']): ?>
                                                                    <img src="<?= htmlspecialchars($category['image']) ?>"
                                                                        alt="<?= htmlspecialchars($category['name']) ?>"
                                                                        class="category-img">
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?= $category['subcategory_count'] ?></td>
                                                            <td>
                                                                <a href="categories.php?edit=<?= $category['id'] ?>" 
                                                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form action="" method="POST" class="d-inline">
                                                                    <input type="hidden" name="delete_id" value="<?= $category['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                            title="Delete" onclick="return confirm('Are you sure you want to delete this category? All related products and subcategories will be deleted!')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="sub-categories" role="tabpanel">
                                        <div class="table-responsive mt-3">
                                            <table class="table table-striped align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Name</th>
                                                        <th>Parent Category</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $subCategories = $conn->query("
                                                        SELECT c1.*, c2.name AS parent_name
                                                        FROM categories c1 
                                                        JOIN categories c2 ON c1.parent_id = c2.id 
                                                        ORDER BY c2.name ASC, c1.name ASC
                                                    ");
                                                    while ($category = $subCategories->fetch_assoc()):
                                                        ?>
                                                        <tr>
                                                            <td><?= $category['id'] ?></td>
                                                            <td><?= htmlspecialchars($category['name']) ?></td>
                                                            <td><?= htmlspecialchars($category['parent_name']) ?></td>
                                                            <td>
                                                                <a href="categories.php?edit=<?= $category['id'] ?>" 
                                                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form action="" method="POST" class="d-inline">
                                                                    <input type="hidden" name="delete_id" value="<?= $category['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                            title="Delete" onclick="return confirm('Are you sure you want to delete this category? All related products will be deleted!')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const parentSelect = document.getElementById('parent_id');
            const imageField = document.getElementById('image-field');
            const imageInput = document.getElementById('image');

            function toggleImageField() {
                if (parentSelect.value === '') {
                    imageInput.disabled = false;
                    imageField.style.display = 'block';
                } else {
                    imageInput.disabled = true;
                    imageField.style.display = 'block';
                }
            }

            parentSelect.addEventListener('change', toggleImageField);
            toggleImageField();
        });
    </script>
</body>
</html>