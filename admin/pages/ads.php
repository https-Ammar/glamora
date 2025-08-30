<?php
session_start();
require('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $filepath = '../uploads/';
        if (!file_exists($filepath)) {
            mkdir($filepath, 0777, true);
        }
        foreach ($_FILES['photo']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name)) {
                $ext = pathinfo($_FILES['photo']['name'][$key], PATHINFO_EXTENSION);
                $uniqueName = uniqid('', true) . '.' . $ext;
                $photo_path = $filepath . $uniqueName;
                if (move_uploaded_file($tmp_name, $photo_path)) {
                    $category_id = intval($_POST['category'][$key]);
                    $linkaddress = $_POST['linkaddress'][$key];
                    $stmt = $conn->prepare("INSERT INTO ads (categoryid, photo, linkaddress) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $category_id, $photo_path, $linkaddress);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    } elseif (isset($_POST['update'])) {
        $id = intval($_POST['id']);
        $category_id = intval($_POST['edit_category']);
        $linkaddress = $_POST['edit_linkaddress'];
        if (!empty($_FILES['edit_photo']['tmp_name'])) {
            $filepath = '../uploads/';
            $ext = pathinfo($_FILES['edit_photo']['name'], PATHINFO_EXTENSION);
            $uniqueName = uniqid('', true) . '.' . $ext;
            $photo_path = $filepath . $uniqueName;
            if (move_uploaded_file($_FILES['edit_photo']['tmp_name'], $photo_path)) {
                $stmt = $conn->prepare("UPDATE ads SET categoryid=?, photo=?, linkaddress=? WHERE id=?");
                $stmt->bind_param("issi", $category_id, $photo_path, $linkaddress, $id);
            }
        } else {
            $stmt = $conn->prepare("UPDATE ads SET categoryid=?, linkaddress=? WHERE id=?");
            $stmt->bind_param("isi", $category_id, $linkaddress, $id);
        }
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['add_slider'])) {
        $filepath = '../uploads/sliders/';
        if (!file_exists($filepath)) {
            mkdir($filepath, 0777, true);
        }
        if (!empty($_FILES['slider_image']['tmp_name'])) {
            $ext = pathinfo($_FILES['slider_image']['name'], PATHINFO_EXTENSION);
            $uniqueName = uniqid('', true) . '.' . $ext;
            $image_path = $filepath . $uniqueName;
            if (move_uploaded_file($_FILES['slider_image']['tmp_name'], $image_path)) {
                $link_url = $_POST['slider_link'];
                $stmt = $conn->prepare("INSERT INTO sliders (image_url, link_url) VALUES (?, ?)");
                $stmt->bind_param("ss", $image_path, $link_url);
                $stmt->execute();
                $stmt->close();
            }
        }
    } elseif (isset($_POST['update_slider'])) {
        $id = intval($_POST['slider_id']);
        $link_url = $_POST['edit_slider_link'];
        if (!empty($_FILES['edit_slider_image']['tmp_name'])) {
            $filepath = '../uploads/sliders/';
            $ext = pathinfo($_FILES['edit_slider_image']['name'], PATHINFO_EXTENSION);
            $uniqueName = uniqid('', true) . '.' . $ext;
            $image_path = $filepath . $uniqueName;
            if (move_uploaded_file($_FILES['edit_slider_image']['tmp_name'], $image_path)) {
                $stmt = $conn->prepare("UPDATE sliders SET image_url=?, link_url=? WHERE id=?");
                $stmt->bind_param("ssi", $image_path, $link_url, $id);
            }
        } else {
            $stmt = $conn->prepare("UPDATE sliders SET link_url=? WHERE id=?");
            $stmt->bind_param("si", $link_url, $id);
        }
        $stmt->execute();
        $stmt->close();
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM ads WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['delete_slider'])) {
    $id = intval($_GET['delete_slider']);
    $stmt = $conn->prepare("DELETE FROM sliders WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$ad_count = $conn->query("SELECT COUNT(*) as count FROM ads")->fetch_assoc()['count'];
$slider_count = $conn->query("SELECT COUNT(*) as count FROM sliders")->fetch_assoc()['count'];
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ads & Sliders Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .ad-image,
        .slider-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
        }

        .edit-modal-img {
            max-width: 200px;
            max-height: 150px;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold;
        }

        .tab-content {
            background: white;
            padding: 20px;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0 0 5px 5px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <h1 class="mb-4">Content Management</h1>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="ads-tab" data-bs-toggle="tab" data-bs-target="#ads" type="button"
                    role="tab">Ads Management</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sliders-tab" data-bs-toggle="tab" data-bs-target="#sliders" type="button"
                    role="tab">Sliders Management</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- Ads Tab -->
            <div class="tab-pane fade show active" id="ads" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Ads Management</h2>
                    <div class="badge bg-primary fs-6">Total Ads: <?= $ad_count ?></div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Add New Ads</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <div id="ads-container">
                                <div class="row ad-row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Category*</label>
                                            <select name="category[]" class="form-select" required>
                                                <option value="">Select Category</option>
                                                <?php
                                                $categories = $conn->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
                                                while ($category = $categories->fetch_assoc()):
                                                    ?>
                                                    <option value="<?= $category['id'] ?>">
                                                        <?= htmlspecialchars($category['name']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Image*</label>
                                            <input type="file" name="photo[]" class="form-control" accept="image/*"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Link*</label>
                                            <input type="url" name="linkaddress[]" class="form-control"
                                                placeholder="https://example.com" required>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="mb-3">
                                            <label class="form-label d-block">&nbsp;</label>
                                            <button type="button" class="btn btn-danger form-control"
                                                onclick="removeAdRow(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary" onclick="addAdRow()">+ Add Another
                                    Ad</button>
                                <button type="submit" name="add" class="btn btn-primary">Save Ads</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Current Ads</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Category</th>
                                        <th>Image</th>
                                        <th>Link</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $ads = $conn->query("
                                        SELECT ads.id, ads.photo, ads.linkaddress, categories.name AS category_name 
                                        FROM ads 
                                        JOIN categories ON ads.categoryid = categories.id 
                                        WHERE categories.parent_id IS NULL
                                        ORDER BY ads.id DESC
                                    ");
                                    while ($ad = $ads->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?= $ad['id'] ?></td>
                                            <td><?= htmlspecialchars($ad['category_name']) ?></td>
                                            <td>
                                                <img src="<?= htmlspecialchars($ad['photo']) ?>" class="ad-image">
                                            </td>
                                            <td>
                                                <a href="<?= htmlspecialchars($ad['linkaddress']) ?>" target="_blank">
                                                    <?= strlen($ad['linkaddress']) > 30 ? substr($ad['linkaddress'], 0, 30) . '...' : $ad['linkaddress'] ?>
                                                </a>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal"
                                                    data-bs-target="#editModal" data-id="<?= $ad['id'] ?>"
                                                    data-category="<?= htmlspecialchars($ad['category_name']) ?>"
                                                    data-categoryid="<?= $conn->query("SELECT categoryid FROM ads WHERE id = " . $ad['id'])->fetch_assoc()['categoryid'] ?>"
                                                    data-photo="<?= htmlspecialchars($ad['photo']) ?>"
                                                    data-linkaddress="<?= htmlspecialchars($ad['linkaddress']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?delete=<?= $ad['id'] ?>" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this ad?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($ads->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="5">No ads available.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sliders Tab -->
            <div class="tab-pane fade" id="sliders" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Sliders Management</h2>
                    <div class="badge bg-primary fs-6">Total Sliders: <?= $slider_count ?></div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Add New Slider</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Image*</label>
                                        <input type="file" name="slider_image" class="form-control" accept="image/*"
                                            required>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label">Link URL*</label>
                                        <input type="url" name="slider_link" class="form-control"
                                            placeholder="https://example.com" required>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="mb-3">
                                        <label class="form-label d-block">&nbsp;</label>
                                        <button type="submit" name="add_slider" class="btn btn-primary">Add
                                            Slider</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Current Sliders</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Image</th>
                                        <th>Link</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sliders = $conn->query("SELECT * FROM sliders ORDER BY id DESC");
                                    while ($slider = $sliders->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?= $slider['id'] ?></td>
                                            <td>
                                                <img src="<?= htmlspecialchars($slider['image_url']) ?>"
                                                    class="slider-image">
                                            </td>
                                            <td>
                                                <a href="<?= htmlspecialchars($slider['link_url']) ?>" target="_blank">
                                                    <?= strlen($slider['link_url']) > 30 ? substr($slider['link_url'], 0, 30) . '...' : $slider['link_url'] ?>
                                                </a>
                                            </td>
                                            <td><?= $slider['created_at'] ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal"
                                                    data-bs-target="#editSliderModal" data-id="<?= $slider['id'] ?>"
                                                    data-image="<?= htmlspecialchars($slider['image_url']) ?>"
                                                    data-link="<?= htmlspecialchars($slider['link_url']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?delete_slider=<?= $slider['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this slider?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($sliders->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="5">No sliders available.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Ad Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Ad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Category*</label>
                            <select name="edit_category" id="edit_category" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php
                                $categories = $conn->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
                                while ($category = $categories->fetch_assoc()):
                                    ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <img src="" id="current_photo" class="edit-modal-img d-block mb-2">
                            <label class="form-label">New Image (Leave empty to keep current)</label>
                            <input type="file" name="edit_photo" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Link*</label>
                            <input type="url" name="edit_linkaddress" id="edit_linkaddress" class="form-control"
                                placeholder="https://example.com" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Slider Modal -->
    <div class="modal fade" id="editSliderModal" tabindex="-1" aria-labelledby="editSliderModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSliderModalLabel">Edit Slider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="slider_id" id="edit_slider_id">
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <img src="" id="current_slider_image" class="edit-modal-img d-block mb-2">
                            <label class="form-label">New Image (Leave empty to keep current)</label>
                            <input type="file" name="edit_slider_image" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Link URL*</label>
                            <input type="url" name="edit_slider_link" id="edit_slider_link" class="form-control"
                                placeholder="https://example.com" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_slider" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addAdRow() {
            const container = document.getElementById('ads-container');
            const newRow = document.createElement('div');
            newRow.className = 'row ad-row';
            newRow.innerHTML = `
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Category*</label>
                        <select name="category[]" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php
                            $categories = $conn->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
                            while ($category = $categories->fetch_assoc()):
                                ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Image*</label>
                        <input type="file" name="photo[]" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Link*</label>
                        <input type="url" name="linkaddress[]" class="form-control" placeholder="https://example.com" required>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="mb-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" class="btn btn-danger form-control" onclick="removeAdRow(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        }

        function removeAdRow(button) {
            const rows = document.querySelectorAll('.ad-row');
            if (rows.length > 1) {
                button.closest('.ad-row').remove();
            } else {
                alert("Cannot delete the last row.");
            }
        }

        // Ads modal
        const editModal = document.getElementById('editModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const categoryid = button.getAttribute('data-categoryid');
                const photo = button.getAttribute('data-photo');
                const linkaddress = button.getAttribute('data-linkaddress');

                document.getElementById('edit_id').value = id;
                document.getElementById('edit_category').value = categoryid;
                document.getElementById('current_photo').src = photo;
                document.getElementById('edit_linkaddress').value = linkaddress;
            });
        }

        // Sliders modal
        const editSliderModal = document.getElementById('editSliderModal');
        if (editSliderModal) {
            editSliderModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const image = button.getAttribute('data-image');
                const link = button.getAttribute('data-link');

                document.getElementById('edit_slider_id').value = id;
                document.getElementById('current_slider_image').src = image;
                document.getElementById('edit_slider_link').value = link;
            });
        }
    </script>
</body>

</html>