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
    </head>
<body>
    <h1>Content Management</h1>

    <h2>Ads Management</h2>
    <div>Total Ads: <?= $ad_count ?></div>

    <h3>Add New Ads</h3>
    <form method="POST" enctype="multipart/form-data">
        <div id="ads-container">
            <div>
                <label>Category*</label>
                <select name="category[]" required>
                    <option value="">Select Category</option>
                    <?php
                    $categories = $conn->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
                    while ($category = $categories->fetch_assoc()):
                        ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <label>Image*</label>
                <input type="file" name="photo[]" accept="image/*" required>
                <label>Link*</label>
                <input type="url" name="linkaddress[]" placeholder="https://example.com" required>
                <button type="button" onclick="removeAdRow(this)">Delete</button>
            </div>
        </div>
        <div>
            <button type="button" onclick="addAdRow()">+ Add Another Ad</button>
            <button type="submit" name="add">Save Ads</button>
        </div>
    </form>

    <h3>Current Ads</h3>
    <table border="1">
        <thead>
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
                            <td><img src="<?= htmlspecialchars($ad['photo']) ?>" style="width:100px;height:60px;"></td>
                            <td><a href="<?= htmlspecialchars($ad['linkaddress']) ?>" target="_blank"><?= htmlspecialchars($ad['linkaddress']) ?></a></td>
                            <td>
                                <button onclick="editAd(<?= $ad['id'] ?>, '<?= htmlspecialchars($ad['category_name']) ?>', '<?= htmlspecialchars($ad['photo']) ?>', '<?= htmlspecialchars($ad['linkaddress']) ?>')">Edit</button>
                                <a href="?delete=<?= $ad['id'] ?>" onclick="return confirm('Are you sure you want to delete this ad?')">Delete</a>
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

    <hr>

    <h2>Sliders Management</h2>
    <div>Total Sliders: <?= $slider_count ?></div>

    <h3>Add New Slider</h3>
    <form method="POST" enctype="multipart/form-data">
        <label>Image*</label>
        <input type="file" name="slider_image" accept="image/*" required>
        <label>Link URL*</label>
        <input type="url" name="slider_link" placeholder="https://example.com" required>
        <button type="submit" name="add_slider">Add Slider</button>
    </form>

    <h3>Current Sliders</h3>
    <table border="1">
        <thead>
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
                            <td><img src="<?= htmlspecialchars($slider['image_url']) ?>" style="width:100px;height:60px;"></td>
                            <td><a href="<?= htmlspecialchars($slider['link_url']) ?>" target="_blank"><?= htmlspecialchars($slider['link_url']) ?></a></td>
                            <td><?= $slider['created_at'] ?></td>
                            <td>
                                <button onclick="editSlider(<?= $slider['id'] ?>, '<?= htmlspecialchars($slider['image_url']) ?>', '<?= htmlspecialchars($slider['link_url']) ?>')">Edit</button>
                                <a href="?delete_slider=<?= $slider['id'] ?>" onclick="return confirm('Are you sure you want to delete this slider?')">Delete</a>
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

    <div id="editAdForm" style="display:none; margin-top: 20px;">
        <h3>Edit Ad</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="edit_id">
            <label>Category*</label>
            <select name="edit_category" id="edit_category" required>
                <option value="">Select Category</option>
                <?php
                $categories->data_seek(0); // Reset pointer
                while ($category = $categories->fetch_assoc()):
                    ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <br>
            <label>Current Image</label>
            <img src="" id="current_photo" style="max-width:200px;max-height:150px;">
            <br>
            <label>New Image (Leave empty to keep current)</label>
            <input type="file" name="edit_photo" accept="image/*">
            <br>
            <label>Link*</label>
            <input type="url" name="edit_linkaddress" id="edit_linkaddress" placeholder="https://example.com" required>
            <br>
            <button type="submit" name="update">Save Changes</button>
            <button type="button" onclick="document.getElementById('editAdForm').style.display='none';">Cancel</button>
        </form>
    </div>

    <div id="editSliderForm" style="display:none; margin-top: 20px;">
        <h3>Edit Slider</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="slider_id" id="edit_slider_id">
            <label>Current Image</label>
            <img src="" id="current_slider_image" style="max-width:200px;max-height:150px;">
            <br>
            <label>New Image (Leave empty to keep current)</label>
            <input type="file" name="edit_slider_image" accept="image/*">
            <br>
            <label>Link URL*</label>
            <input type="url" name="edit_slider_link" id="edit_slider_link" placeholder="https://example.com" required>
            <br>
            <button type="submit" name="update_slider">Save Changes</button>
            <button type="button" onclick="document.getElementById('editSliderForm').style.display='none';">Cancel</button>
        </form>
    </div>

    <script>
        function addAdRow() {
            const container = document.getElementById('ads-container');
            const newRow = document.createElement('div');
            newRow.innerHTML = `
                <label>Category*</label>
                <select name="category[]" required>
                    <option value="">Select Category</option>
                    <?php
                    $categories->data_seek(0);
                    while ($category = $categories->fetch_assoc()):
                        ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <label>Image*</label>
                <input type="file" name="photo[]" accept="image/*" required>
                <label>Link*</label>
                <input type="url" name="linkaddress[]" placeholder="https://example.com" required>
                <button type="button" onclick="removeAdRow(this)">Delete</button>
            `;
            container.appendChild(newRow);
        }

        function removeAdRow(button) {
            const container = document.getElementById('ads-container');
            if (container.children.length > 1) {
                container.removeChild(button.parentElement);
            } else {
                alert("Cannot delete the last row.");
            }
        }

        function editAd(id, category_name, photo, linkaddress) {
            document.getElementById('edit_id').value = id;
            document.getElementById('current_photo').src = photo;
            document.getElementById('edit_linkaddress').value = linkaddress;

            const categorySelect = document.getElementById('edit_category');
            for (let i = 0; i < categorySelect.options.length; i++) {
                if (categorySelect.options[i].text === category_name) {
                    categorySelect.value = categorySelect.options[i].value;
                    break;
                }
            }

            document.getElementById('editAdForm').style.display = 'block';
        }

        function editSlider(id, image, link) {
            document.getElementById('edit_slider_id').value = id;
            document.getElementById('current_slider_image').src = image;
            document.getElementById('edit_slider_link').value = link;
            document.getElementById('editSliderForm').style.display = 'block';
        }
    </script>
</body>
</html>