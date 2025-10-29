<?php
session_start();
require('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $filepath = '../Uploads/';
        if (!file_exists($filepath)) mkdir($filepath, 0777, true);
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
        if (!empty($_FILES['edit_photo']['tmp_name'][0])) {
            $filepath = '../Uploads/';
            $ext = pathinfo($_FILES['edit_photo']['name'][0], PATHINFO_EXTENSION);
            $uniqueName = uniqid('', true) . '.' . $ext;
            $photo_path = $filepath . $uniqueName;
            if (move_uploaded_file($_FILES['edit_photo']['tmp_name'][0], $photo_path)) {
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
        $filepath = '../Uploads/sliders/';
        if (!file_exists($filepath)) mkdir($filepath, 0777, true);
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
            $filepath = '../Uploads/sliders/';
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
$categories = $conn->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ads & Sliders Management</title>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <main x-data="{ activeTab: 'ads' }">
        <div class="mx-auto max-w-7xl p-4 pb-20 md:p-6 md:pb-6">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Ads & Sliders Management</h2>
                <nav>
                    <ol class="flex items-center gap-1.5">
                        <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="index.html">Home <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path></svg></a></li>
                        <li class="text-sm text-gray-800 dark:text-white/90">Ads & Sliders Management</li>
                    </ol>
                </nav>
            </div>

            <div class="flex gap-3 mb-6">
                <button @click="activeTab = 'ads'" :class="activeTab === 'ads' ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300'" class="px-5 py-2.5 rounded-lg text-sm font-medium transition">Ads Management</button>
                <button @click="activeTab = 'sliders'" :class="activeTab === 'sliders' ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300'" class="px-5 py-2.5 rounded-lg text-sm font-medium transition">Sliders Management</button>
            </div>

            <div x-show="activeTab === 'ads'">
                <div class="rounded-2xl border border-gray-200 bg-white px-6 pl-5 dark:border-gray-800 dark:bg-white/3">
                    <div class="flex flex-col justify-between gap-5 border-b border-gray-100 py-4 sm:flex-row sm:items-center dark:border-gray-800">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Ads Management</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Ads: <?= $ad_count ?></p>
                        </div>
                        <div x-data="{ isModalOpen: false }">
                            <button class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition" @click="isModalOpen = true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>Add Ad
                            </button>
                            <div x-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-5 overflow-y-auto" style="display: none;">
                                <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]"></div>
                                <div @click.outside="isModalOpen = false" class="relative w-full max-w-[600px] rounded-3xl bg-white p-6 lg:p-10 dark:bg-gray-900">
                                    <button @click="isModalOpen = false" class="absolute top-3 right-3 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""></path></svg>
                                    </button>
                                    <h4 class="text-title-sm mb-4 font-semibold text-gray-800 dark:text-white/90">Add New Ad</h4>
                                    <form method="POST" enctype="multipart/form-data">
                                        <div id="ads-container">
                                            <div class="grid grid-cols-1 gap-4 mb-4 border-b pb-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Category</label>
                                                    <select name="category[]" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                                        <option value="">Select Category</option>
                                                        <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                                                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Image</label>
                                                    <input type="file" name="photo[]" accept="image/*" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Link</label>
                                                    <input type="url" name="linkaddress[]" placeholder="https://example.com" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                                </div>
                                                <button type="button" onclick="removeAdRow(this)" class="text-error-500 hover:text-error-600">Delete</button>
                                            </div>
                                        </div>
                                        <button type="button" onclick="addAdRow()" class="text-brand-500 hover:text-brand-600 text-sm">+ Add Another Ad</button>
                                        <div class="mt-6 flex gap-3">
                                            <button type="button" @click="isModalOpen = false" class="flex-1 py-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Close</button>
                                            <button type="submit" name="add" class="flex-1 py-3 rounded-lg bg-brand-500 text-sm font-medium text-white hover:bg-brand-600">Save Ads</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto custom-scrollbar px-1 pb-4">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <th class="py-3 pr-5 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ID</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Category</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Image</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Link</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <?php
                                $ads = $conn->query("SELECT ads.id, ads.photo, ads.linkaddress, categories.name AS category_name FROM ads JOIN categories ON ads.categoryid = categories.id WHERE categories.parent_id IS NULL ORDER BY ads.id DESC");
                                while ($ad = $ads->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td class="py-3 pr-5 text-sm text-gray-700 dark:text-gray-400"><?= $ad['id'] ?></td>
                                        <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-400"><?= htmlspecialchars($ad['category_name']) ?></td>
                                        <td class="px-5 py-3"><img src="<?= htmlspecialchars($ad['photo']) ?>" class="h-10 w-16 object-cover rounded"></td>
                                        <td class="px-5 py-3"><a href="<?= htmlspecialchars($ad['linkaddress']) ?>" target="_blank" class="text-brand-500 hover:text-brand-600 text-sm"><?= htmlspecialchars($ad['linkaddress']) ?></a></td>
                                        <td class="px-5 py-3">
                                            <div class="flex gap-2">
                                                <button @click="$dispatch('open-edit-ad', {id: <?= $ad['id'] ?>, category: '<?= $ad['category_name'] ?>', photo: '<?= htmlspecialchars($ad['photo']) ?>', link: '<?= htmlspecialchars($ad['linkaddress']) ?>'})" class="text-gray-500 hover:text-gray-800 dark:hover:text-white">
                                                    <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.0911 3.53206C16.2124 2.65338 14.7878 2.65338 13.9091 3.53206L5.6074 11.8337C5.29899 12.1421 5.08687 12.5335 4.99684 12.9603L4.26177 16.445C4.20943 16.6931 4.286 16.9508 4.46529 17.1301C4.64458 17.3094 4.90232 17.3859 5.15042 17.3336L8.63507 16.5985C9.06184 16.5085 9.45324 16.2964 9.76165 15.988L18.0633 7.68631C18.942 6.80763 18.942 5.38301 18.0633 4.50433L17.0911 3.53206ZM14.9697 4.59272C15.2626 4.29982 15.7375 4.29982 16.0304 4.59272L17.0027 5.56499C17.2956 5.85788 17.2956 6.33276 17.0027 6.62565L16.1043 7.52402L14.0714 5.49109L14.9697 4.59272ZM13.0107 6.55175L6.66806 12.8944C6.56526 12.9972 6.49455 13.1277 6.46454 13.2699L5.96704 15.6283L8.32547 15.1308C8.46772 15.1008 8.59819 15.0301 8.70099 14.9273L15.0436 8.58468L13.0107 6.55175Z" fill=""></path></svg>
                                                </button>
                                                <a href="?delete=<?= $ad['id'] ?>" onclick="return confirm('Are you sure?')" class="text-gray-500 hover:text-error-500 dark:hover:text-error-500">
                                                    <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.04142 4.29199C7.04142 3.04935 8.04878 2.04199 9.29142 2.04199H11.7081C12.9507 2.04199 13.9581 3.04935 13.9581 4.29199V4.54199H16.1252H17.166C17.5802 4.54199 17.916 4.87778 17.916 5.29199C17.916 5.70621 17.5802 6.04199 17.166 6.04199H16.8752V8.74687V13.7469V16.7087C16.8752 17.9513 15.8678 18.9587 14.6252 18.9587H6.37516C5.13252 18.9587 4.12516 17.9513 4.12516 16.7087V13.7469V8.74687V6.04199H3.8335C3.41928 6.04199 3.0835 5.70621 3.0835 5.29199C3.0835 4.87778 3.41928 4.54199 3.8335 4.54199H4.87516H7.04142V4.29199ZM15.3752 13.7469V8.74687V6.04199H13.9581H13.2081H7.79142H7.04142H5.62516V8.74687V13.7469V16.7087C5.62516 17.1229 5.96095 17.4587 6.37516 17.4587H14.6252C15.0394 17.4587 15.3752 17.1229 15.3752 16.7087V13.7469ZM8.54142 4.54199H12.4581V4.29199C12.4581 3.87778 12.1223 3.54199 11.7081 3.54199H9.29142C8.87721 3.54199 8.54142 3.87778 8.54142 4.29199V4.54199ZM8.8335 8.50033C9.24771 8.50033 9.5835 8.83611 9.5835 9.25033V14.2503C9.5835 14.6645 9.24771 15.0003 8.8335 15.0003C8.41928 15.0003 8.0835 14.6645 8.0835 14.2503V9.25033C8.0835 8.83611 8.41928 8.50033 8.8335 8.50033ZM12.9168 9.25033C12.9168 8.83611 12.581 8.50033 12.1668 8.50033C11.7526 8.50033 11.4168 8.83611 11.4168 9.25033V14.2503C11.4168 14.6645 11.7526 15.0003 12.1668 15.0003C12.581 15.0003 12.9168 14.6645 12.9168 14.2503V9.25033Z" fill=""></path></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if ($ads->num_rows === 0): ?>
                                    <tr><td colspan="5" class="py-3 text-sm text-gray-500 dark:text-gray-400 text-center">No ads available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'sliders'">
                <div class="rounded-2xl border border-gray-200 bg-white px-6 pl-5 dark:border-gray-800 dark:bg-white/3">
                    <div class="flex flex-col justify-between gap-5 border-b border-gray-100 py-4 sm:flex-row sm:items-center dark:border-gray-800">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Sliders Management</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Sliders: <?= $slider_count ?></p>
                        </div>
                        <div x-data="{ isSliderModalOpen: false }">
                            <button class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition" @click="isSliderModalOpen = true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>Add Slider
                            </button>
                            <div x-show="isSliderModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-5 overflow-y-auto" style="display: none;">
                                <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]"></div>
                                <div @click.outside="isSliderModalOpen = false" class="relative w-full max-w-[600px] rounded-3xl bg-white p-6 lg:p-10 dark:bg-gray-900">
                                    <button @click="isSliderModalOpen = false" class="absolute top-3 right-3 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""></path></svg>
                                    </button>
                                    <h4 class="text-title-sm mb-4 font-semibold text-gray-800 dark:text-white/90">Add New Slider</h4>
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Image</label>
                                            <input type="file" name="slider_image" accept="image/*" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Link URL</label>
                                            <input type="url" name="slider_link" placeholder="https://example.com" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                                        </div>
                                        <div class="flex gap-3">
                                            <button type="button" @click="isSliderModalOpen = false" class="flex-1 py-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Close</button>
                                            <button type="submit" name="add_slider" class="flex-1 py-3 rounded-lg bg-brand-500 text-sm font-medium text-white hover:bg-brand-600">Add Slider</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto custom-scrollbar px-1 pb-4">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <th class="py-3 pr-5 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ID</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Image</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Link</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Created At</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <?php
                                $sliders = $conn->query("SELECT * FROM sliders ORDER BY id DESC");
                                while ($slider = $sliders->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td class="py-3 pr-5 text-sm text-gray-700 dark:text-gray-400"><?= $slider['id'] ?></td>
                                        <td class="px-5 py-3"><img src="<?= htmlspecialchars($slider['image_url']) ?>" class="h-10 w-16 object-cover rounded"></td>
                                        <td class="px-5 py-3"><a href="<?= htmlspecialchars($slider['link_url']) ?>" target="_blank" class="text-brand-500 hover:text-brand-600 text-sm"><?= htmlspecialchars($slider['link_url']) ?></a></td>
                                        <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400"><?= $slider['created_at'] ?></td>
                                        <td class="px-5 py-3">
                                            <div class="flex gap-2">
                                                <button @click="$dispatch('open-edit-slider', {id: <?= $slider['id'] ?>, image: '<?= htmlspecialchars($slider['image_url']) ?>', link: '<?= htmlspecialchars($slider['link_url']) ?>'})" class="text-gray-500 hover:text-gray-800 dark:hover:text-white">
                                                    <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.0911 3.53206C16.2124 2.65338 14.7878 2.65338 13.9091 3.53206L5.6074 11.8337C5.29899 12.1421 5.08687 12.5335 4.99684 12.9603L4.26177 16.445C4.20943 16.6931 4.286 16.9508 4.46529 17.1301C4.64458 17.3094 4.90232 17.3859 5.15042 17.3336L8.63507 16.5985C9.06184 16.5085 9.45324 16.2964 9.76165 15.988L18.0633 7.68631C18.942 6.80763 18.942 5.38301 18.0633 4.50433L17.0911 3.53206ZM14.9697 4.59272C15.2626 4.29982 15.7375 4.29982 16.0304 4.59272L17.0027 5.56499C17.2956 5.85788 17.2956 6.33276 17.0027 6.62565L16.1043 7.52402L14.0714 5.49109L14.9697 4.59272ZM13.0107 6.55175L6.66806 12.8944C6.56526 12.9972 6.49455 13.1277 6.46454 13.2699L5.96704 15.6283L8.32547 15.1308C8.46772 15.1008 8.59819 15.0301 8.70099 14.9273L15.0436 8.58468L13.0107 6.55175Z" fill=""></path></svg>
                                                </button>
                                                <a href="?delete_slider=<?= $slider['id'] ?>" onclick="return confirm('Are you sure?')" class="text-gray-500 hover:text-error-500 dark:hover:text-error-500">
                                                    <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.04142 4.29199C7.04142 3.04935 8.04878 2.04199 9.29142 2.04199H11.7081C12.9507 2.04199 13.9581 3.04935 13.9581 4.29199V4.54199H16.1252H17.166C17.5802 4.54199 17.916 4.87778 17.916 5.29199C17.916 5.70621 17.5802 6.04199 17.166 6.04199H16.8752V8.74687V13.7469V16.7087C16.8752 17.9513 15.8678 18.9587 14.6252 18.9587H6.37516C5.13252 18.9587 4.12516 17.9513 4.12516 16.7087V13.7469V8.74687V6.04199H3.8335C3.41928 6.04199 3.0835 5.70621 3.0835 5.29199C3.0835 4.87778 3.41928 4.54199 3.8335 4.54199H4.87516H7.04142V4.29199ZM15.3752 13.7469V8.74687V6.04199H13.9581H13.2081H7.79142H7.04142H5.62516V8.74687V13.7469V16.7087C5.62516 17.1229 5.96095 17.4587 6.37516 17.4587H14.6252C15.0394 17.4587 15.3752 17.1229 15.3752 16.7087V13.7469ZM8.54142 4.54199H12.4581V4.29199C12.4581 3.87778 12.1223 3.54199 11.7081 3.54199H9.29142C8.87721 3.54199 8.54142 3.87778 8.54142 4.29199V4.54199ZM8.8335 8.50033C9.24771 8.50033 9.5835 8.83611 9.5835 9.25033V14.2503C9.5835 14.6645 9.24771 15.0003 8.8335 15.0003C8.41928 15.0003 8.0835 14.6645 8.0835 14.2503V9.25033C8.0835 8.83611 8.41928 8.50033 8.8335 8.50033ZM12.9168 9.25033C12.9168 8.83611 12.581 8.50033 12.1668 8.50033C11.7526 8.50033 11.4168 8.83611 11.4168 9.25033V14.2503C11.4168 14.6645 11.7526 15.0003 12.1668 15.0003C12.581 15.0003 12.9168 14.6645 12.9168 14.2503V9.25033Z" fill=""></path></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if ($sliders->num_rows === 0): ?>
                                    <tr><td colspan="5" class="py-3 text-sm text-gray-500 dark:text-gray-400 text-center">No sliders available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div x-data="{ editAdId: '', editCategoryId: '', editPhoto: '', editLink: '' }" x-show="$store.editAdModal" class="fixed inset-0 z-50 flex items-center justify-center p-5 overflow-y-auto" style="display: none;">
            <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]"></div>
            <div @click.outside="$store.editAdModal = false" class="relative w-full max-w-[600px] rounded-3xl bg-white p-6 lg:p-10 dark:bg-gray-900">
                <button @click="$store.editAdModal = false" class="absolute top-3 right-3 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""></path></svg>
                </button>
                <h4 class="text-title-sm mb-4 font-semibold text-gray-800 dark:text-white/90">Edit Ad</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" :value="editAdId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Category</label>
                        <select name="edit_category" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                            <option value="">Select Category</option>
                            <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                                <option :selected="editCategoryId == <?= $c['id'] ?>" value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Current Image</label>
                        <img :src="editPhoto" class="h-24 w-32 object-cover rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">New Image</label>
                        <input type="file" name="edit_photo[]" accept="image/*" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Link</label>
                        <input type="url" name="edit_linkaddress" :value="editLink" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="$store.editAdModal = false" class="flex-1 py-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Close</button>
                        <button type="submit" name="update" class="flex-1 py-3 rounded-lg bg-brand-500 text-sm font-medium text-white hover:bg-brand-600">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-data="{ editSliderId: '', editSliderImage: '', editSliderLink: '' }" x-show="$store.editSliderModal" class="fixed inset-0 z-50 flex items-center justify-center p-5 overflow-y-auto" style="display: none;">
            <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]"></div>
            <div @click.outside="$store.editSliderModal = false" class="relative w-full max-w-[600px] rounded-3xl bg-white p-6 lg:p-10 dark:bg-gray-900">
                <button @click="$store.editSliderModal = false" class="absolute top-3 right-3 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""></path></svg>
                </button>
                <h4 class="text-title-sm mb-4 font-semibold text-gray-800 dark:text-white/90">Edit Slider</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="slider_id" :value="editSliderId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Current Image</label>
                        <img :src="editSliderImage" class="h-24 w-32 object-cover rounded">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">New Image</label>
                        <input type="file" name="edit_slider_image" accept="image/*" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Link URL</label>
                        <input type="url" name="edit_slider_link" :value="editSliderLink" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="$store.editSliderModal = false" class="flex-1 py-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Close</button>
                        <button type="submit" name="update_slider" class="flex-1 py-3 rounded-lg bg-brand-500 text-sm font-medium text-white hover:bg-brand-600">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function addAdRow() {
            const container = document.getElementById('ads-container');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 gap-4 mb-4 border-b pb-4';
            row.innerHTML = `
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Category</label>
                    <select name="category[]" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                        <option value="">Select Category</option>
                        <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Image</label>
                    <input type="file" name="photo[]" accept="image/*" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Link</label>
                    <input type="url" name="linkaddress[]" placeholder="https://example.com" class="w-full h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>
                </div>
                <button type="button" onclick="removeAdRow(this)" class="text-error-500 hover:text-error-600">Delete</button>
            `;
            container.appendChild(row);
        }

        function removeAdRow(btn) {
            const container = document.getElementById('ads-container');
            if (container.children.length > 1) container.removeChild(btn.parentElement);
            else alert("Cannot delete the last row.");
        }

        document.addEventListener('alpine:init', () => {
            Alpine.store('editAdModal', false);
            Alpine.store('editSliderModal', false);

            document.addEventListener('open-edit-ad', (e) => {
                const data = e.detail;
                Alpine.store('editAdModal', true);
                const modal = document.querySelector('[x-data="{ editAdId: \\'\\', editCategoryId: \\'\\', editPhoto: \\'\\', editLink: \\'\\' }"]');
                modal.__x.$data.editAdId = data.id;
                modal.__x.$data.editCategoryId = data.category;
                modal.__x.$data.editPhoto = data.photo;
                modal.__x.$data.editLink = data.link;
                setTimeout(() => {
                    const select = modal.querySelector('select[name="edit_category"]');
                    const cat = <?= json_encode(array_column($categories->fetch_all(MYSQLI_ASSOC), 'name', 'id')) ?>;
                    select.value = Object.keys(cat).find(id => cat[id] === data.category) || '';
                }, 0);
            });

            document.addEventListener('open-edit-slider', (e) => {
                const data = e.detail;
                Alpine.store('editSliderModal', true);
                const modal = document.querySelector('[x-data="{ editSliderId: \\'\\', editSliderImage: \\'\\', editSliderLink: \\'\\' }"]');
                modal.__x.$data.editSliderId = data.id;
                modal.__x.$data.editSliderImage = data.image;
                modal.__x.$data.editSliderLink = data.link;
            });
        });
    </script>
</body>
</html>