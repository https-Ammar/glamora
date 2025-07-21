<?php
session_start();
require('./db.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);



// معالجة إضافة المنتج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // تنظيف المدخلات
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $description = trim($_POST['description']);
    $tags = trim($_POST['tags']);
    $price = floatval($_POST['price']);
    $discountPercent = isset($_POST['discount_percent']) ? floatval($_POST['discount_percent']) : 0;
    $salePrice = ($discountPercent > 0 && $discountPercent <= 100) ? $price - ($price * ($discountPercent / 100)) : null;
    $quantity = intval($_POST['quantity']);
    $stockStatus = in_array($_POST['stock_status'], ['in_stock', 'pre_order', 'out_of_stock']) ? $_POST['stock_status'] : 'in_stock';
    $isNew = isset($_POST['is_new']) ? 1 : 0;
    $onSale = isset($_POST['on_sale']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $barcode = !empty($_POST['barcode']) ? $_POST['barcode'] : uniqid('PRD-');
    $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $categoryId = intval($_POST['category_id']);
    
    // معالجة الأحجام
    $sizes = [];
    if (!empty($_POST['size_name']) && is_array($_POST['size_name'])) {
        foreach ($_POST['size_name'] as $index => $sizeName) {
            if (!empty($sizeName)) {
                $sizes[] = [
                    'name' => $sizeName,
                    'price' => isset($_POST['size_price'][$index]) ? floatval($_POST['size_price'][$index]) : $price
                ];
            }
        }
    }
    $sizesJson = !empty($sizes) ? json_encode($sizes, JSON_UNESCAPED_UNICODE) : null;
    
    // معالجة الألوان
    $colors = [];
    if (!empty($_POST['color_name']) && is_array($_POST['color_name'])) {
        foreach ($_POST['color_name'] as $index => $colorName) {
            if (!empty($colorName)) {
                $colors[] = [
                    'name' => $colorName,
                    'hex' => isset($_POST['color_hex'][$index]) ? $_POST['color_hex'][$index] : '#000000'
                ];
            }
        }
    }
    $colorsJson = !empty($colors) ? json_encode($colors, JSON_UNESCAPED_UNICODE) : null;

    // معالجة تحميل الصور
    $imagePath = null;
    $galleryPaths = [];
    $targetDir = 'uploads/products/';
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB

    // إنشاء مجلد التحميل إذا لم يكن موجوداً
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // معالجة الصورة الرئيسية
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = pathinfo($_FILES['image']['name']);
        $ext = strtolower($fileInfo['extension']);
        
        // التحقق من صحة الملف
        if (in_array($ext, $allowedExtensions) && $_FILES['image']['size'] <= $maxFileSize) {
            $imageName = uniqid('img_', true) . '.' . $ext;
            $targetPath = $targetDir . $imageName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = $targetPath;
            } else {
                $error = "حدث خطأ أثناء تحميل الصورة الرئيسية";
            }
        } else {
            $error = "صيغة الملف غير مدعومة أو حجم الملف كبير جداً";
        }
    } else {
        $error = "يجب تحميل صورة رئيسية للمنتج";
    }

    // معالجة صور المعرض
    if (empty($error) && !empty($_FILES['gallery']['name'][0])) {
        foreach ($_FILES['gallery']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['gallery']['error'][$index] === UPLOAD_ERR_OK) {
                $fileInfo = pathinfo($_FILES['gallery']['name'][$index]);
                $ext = strtolower($fileInfo['extension']);
                
                if (in_array($ext, $allowedExtensions) && $_FILES['gallery']['size'][$index] <= $maxFileSize) {
                    $imgName = uniqid('gallery_', true) . '.' . $ext;
                    $imgPath = $targetDir . $imgName;
                    
                    if (move_uploaded_file($tmpName, $imgPath)) {
                        $galleryPaths[] = $imgPath;
                    }
                }
            }
        }
    }

    $galleryJson = !empty($galleryPaths) ? json_encode($galleryPaths) : null;

    // إدخال المنتج في قاعدة البيانات
    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO products (
            name, brand, description, tags, price, sale_price, discount_percent,
            quantity, stock_status, is_new, on_sale, is_featured, barcode,
            expiry_date, category_id, image, sizes, colors, gallery, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "ssssddddsiiisssssssi",
            $name,
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
            $sizesJson,
            $colorsJson,
            $galleryJson,
            $userId
        );

        if ($stmt->execute()) {
            $success = "تمت إضافة المنتج بنجاح!";
            // إعادة تعيين الحقول بعد الإضافة الناجحة
            $_POST = array();
        } else {
            $error = "حدث خطأ أثناء إضافة المنتج: " . $conn->error;
            // حذف الصور التي تم تحميلها في حالة فشل الإدراج
            if ($imagePath && file_exists($imagePath)) {
                unlink($imagePath);
            }
            foreach ($galleryPaths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }

        $stmt->close();
    }
}

// جلب التصنيفات لعرضها في القائمة المنسدلة
$categories = $conn->query("SELECT c1.id, c1.name AS child_name, c2.name AS parent_name 
                          FROM categories c1
                          LEFT JOIN categories c2 ON c1.parent_id = c2.id
                          WHERE c1.parent_id IS NOT NULL
                          ORDER BY c2.name, c1.name");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة منتج جديد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Tajawal', sans-serif;
        }
        .card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
        }
        .form-label {
            font-weight: 600;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: none;
        }
        .gallery-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .gallery-thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
            position: relative;
        }
        .remove-image {
            position: absolute;
            top: -5px;
            right: -5px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
        }
        .size-item, .color-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
        }
        .color-preview {
            width: 20px;
            height: 20px;
            display: inline-block;
            border: 1px solid #ddd;
            margin-right: 5px;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>إضافة منتج جديد</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" id="productForm">
                            <input type="hidden" name="add_product" value="1">

                            <div class="row">
                                <!-- المعلومات الأساسية -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label required-field">اسم المنتج</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="brand" class="form-label">العلامة التجارية</label>
                                        <input type="text" class="form-control" id="brand" name="brand"
                                               value="<?= isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : '' ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="price" class="form-label required-field">السعر الأساسي</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" id="price" name="price"
                                                   value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>" required>
                                            <span class="input-group-text">ر.س</span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="discount_percent" class="form-label">نسبة الخصم %</label>
                                        <input type="number" class="form-control" id="discount_percent"
                                               name="discount_percent" min="0" max="100"
                                               value="<?= isset($_POST['discount_percent']) ? htmlspecialchars($_POST['discount_percent']) : '' ?>">
                                        <small id="salePricePreview" class="text-muted"></small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="quantity" class="form-label required-field">الكمية</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity"
                                               value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="stock_status" class="form-label required-field">حالة المخزون</label>
                                        <select class="form-select" id="stock_status" name="stock_status" required>
                                            <option value="in_stock" <?= (isset($_POST['stock_status']) && $_POST['stock_status'] == 'in_stock') ? 'selected' : '' ?>>متوفر</option>
                                            <option value="out_of_stock" <?= (isset($_POST['stock_status']) && $_POST['stock_status'] == 'out_of_stock') ? 'selected' : '' ?>>غير متوفر</option>
                                            <option value="pre_order" <?= (isset($_POST['stock_status']) && $_POST['stock_status'] == 'pre_order') ? 'selected' : '' ?>>طلب مسبق</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category_id" class="form-label required-field">التصنيف</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">اختر التصنيف</option>
                                            <?php while ($category = $categories->fetch_assoc()): ?>
                                                <option value="<?= $category['id'] ?>" 
                                                    <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['parent_name'] . ' > ' . $category['child_name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="expiry_date" class="form-label">تاريخ الانتهاء</label>
                                        <input type="date" class="form-control" id="expiry_date" name="expiry_date"
                                               value="<?= isset($_POST['expiry_date']) ? htmlspecialchars($_POST['expiry_date']) : '' ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="barcode" class="form-label">باركود المنتج</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="barcode" name="barcode"
                                                   value="<?= isset($_POST['barcode']) ? htmlspecialchars($_POST['barcode']) : '' ?>">
                                            <button class="btn btn-outline-secondary" type="button" id="generateBarcode">
                                                <i class="fas fa-barcode"></i> توليد
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="description" class="form-label required-field">الوصف</label>
                                        <textarea class="form-control" id="description" name="description" rows="4" required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="tags" class="form-label">الكلمات الدلالية (افصلها بفواصل)</label>
                                        <input type="text" class="form-control" id="tags" name="tags"
                                               value="<?= isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : '' ?>">
                                        <small class="text-muted">مثال: منتج, جديد, عرض خاص</small>
                                    </div>
                                </div>
                            </div>

                            <!-- خيارات المنتج -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">خيارات المنتج</label>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_new"
                                                       name="is_new" <?= (isset($_POST['is_new']) && $_POST['is_new']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_new">منتج جديد</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="on_sale"
                                                       name="on_sale" <?= (isset($_POST['on_sale']) && $_POST['on_sale']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="on_sale">عرض خاص</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_featured"
                                                       name="is_featured" <?= (isset($_POST['is_featured']) && $_POST['is_featured']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_featured">منتج مميز</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- صورة المنتج -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="image" class="form-label required-field">صورة المنتج الرئيسية</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                        <small class="text-muted">الصيغ المسموحة: JPG, PNG, GIF. الحجم الأقصى: 2MB</small>
                                        <img id="imagePreview" src="#" alt="معاينة الصورة" class="preview-image img-thumbnail">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="gallery" class="form-label">صور المعرض</label>
                                        <input type="file" class="form-control" id="gallery" name="gallery[]" multiple accept="image/*">
                                        <small class="text-muted">يمكنك اختيار أكثر من صورة</small>
                                        <div id="galleryPreview" class="gallery-preview"></div>
                                        <input type="hidden" id="removedImages" name="removedImages" value="">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- أحجام المنتج -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0">الأحجام المتاحة</h5>
                                        </div>
                                        <div class="card-body">
                                            <div id="sizesContainer">
                                                <!-- سيتم إضافة الأحجام هنا ديناميكياً -->
                                                <?php if (isset($_POST['size_name']) && is_array($_POST['size_name'])): ?>
                                                    <?php foreach ($_POST['size_name'] as $index => $sizeName): ?>
                                                        <?php if (!empty($sizeName)): ?>
                                                            <div class="size-item mb-2">
                                                                <div class="row">
                                                                    <div class="col-md-5">
                                                                        <input type="text" class="form-control" name="size_name[]" placeholder="اسم الحجم" value="<?= htmlspecialchars($sizeName) ?>" required>
                                                                    </div>
                                                                    <div class="col-md-5">
                                                                        <div class="input-group">
                                                                            <input type="number" step="0.01" class="form-control" name="size_price[]" placeholder="السعر" value="<?= isset($_POST['size_price'][$index]) ? htmlspecialchars($_POST['size_price'][$index]) : '' ?>">
                                                                            <span class="input-group-text">ر.س</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <button type="button" class="btn btn-danger btn-sm w-100 remove-size">
                                                                            <i class="fas fa-trash"></i> حذف
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="btn btn-primary mt-2" id="addSize">
                                                <i class="fas fa-plus"></i> إضافة حجم جديد
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ألوان المنتج -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0">الألوان المتاحة</h5>
                                        </div>
                                        <div class="card-body">
                                            <div id="colorsContainer">
                                                <!-- سيتم إضافة الألوان هنا ديناميكياً -->
                                                <?php if (isset($_POST['color_name']) && is_array($_POST['color_name'])): ?>
                                                    <?php foreach ($_POST['color_name'] as $index => $colorName): ?>
                                                        <?php if (!empty($colorName)): ?>
                                                            <div class="color-item mb-2">
                                                                <div class="row">
                                                                    <div class="col-md-5">
                                                                        <input type="text" class="form-control" name="color_name[]" placeholder="اسم اللون" value="<?= htmlspecialchars($colorName) ?>" required>
                                                                    </div>
                                                                    <div class="col-md-5">
                                                                        <div class="input-group">
                                                                            <span class="input-group-text color-preview" style="background-color: <?= isset($_POST['color_hex'][$index]) ? htmlspecialchars($_POST['color_hex'][$index]) : '#000000' ?>"></span>
                                                                            <input type="color" class="form-control form-control-color" name="color_hex[]" value="<?= isset($_POST['color_hex'][$index]) ? htmlspecialchars($_POST['color_hex'][$index]) : '#000000' ?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <button type="button" class="btn btn-danger btn-sm w-100 remove-color">
                                                                            <i class="fas fa-trash"></i> حذف
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="btn btn-primary mt-2" id="addColor">
                                                <i class="fas fa-plus"></i> إضافة لون جديد
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="submit" class="btn btn-primary btn-lg me-md-2">
                                    <i class="fas fa-save me-1"></i> حفظ المنتج
                                </button>
                                <a href="index.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times me-1"></i> إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // معاينة الصورة الرئيسية
        document.getElementById('image').addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // معاينة صور المعرض وإمكانية حذفها قبل الرفع
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
                        removeBtn.onclick = function() {
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

        // حساب سعر الخصم تلقائياً
        function calculateSalePrice() {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const discount = parseFloat(document.getElementById('discount_percent').value) || 0;
            
            if (discount > 0 && discount <= 100) {
                const salePrice = price - (price * (discount / 100));
                document.getElementById('salePricePreview').textContent = 
                    `سعر الخصم: ${salePrice.toFixed(2)} ر.س (${discount}% خصم)`;
            } else {
                document.getElementById('salePricePreview').textContent = '';
            }
        }
        
        document.getElementById('price').addEventListener('input', calculateSalePrice);
        document.getElementById('discount_percent').addEventListener('input', calculateSalePrice);

        // توليد باركود تلقائي
        document.getElementById('generateBarcode').addEventListener('click', function() {
            const randomBarcode = 'PRD-' + Math.floor(100000 + Math.random() * 900000);
            document.getElementById('barcode').value = randomBarcode;
        });

        // إدارة الأحجام
        document.getElementById('addSize').addEventListener('click', function() {
            const sizeItem = document.createElement('div');
            sizeItem.className = 'size-item mb-2';
            sizeItem.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="size_name[]" placeholder="اسم الحجم" required>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" name="size_price[]" placeholder="السعر">
                            <span class="input-group-text">ر.س</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 remove-size">
                            <i class="fas fa-trash"></i> حذف
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('sizesContainer').appendChild(sizeItem);
            
            // إضافة حدث للحذف
            sizeItem.querySelector('.remove-size').addEventListener('click', function() {
                sizeItem.remove();
            });
        });

        // إدارة الألوان
        document.getElementById('addColor').addEventListener('click', function() {
            const colorItem = document.createElement('div');
            colorItem.className = 'color-item mb-2';
            colorItem.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="color_name[]" placeholder="اسم اللون" required>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text color-preview" style="background-color: #000000"></span>
                            <input type="color" class="form-control form-control-color" name="color_hex[]" value="#000000">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 remove-color">
                            <i class="fas fa-trash"></i> حذف
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('colorsContainer').appendChild(colorItem);
            
            // تحديث معاينة اللون عند التغيير
            const colorInput = colorItem.querySelector('input[type="color"]');
            const colorPreview = colorItem.querySelector('.color-preview');
            
            colorInput.addEventListener('input', function() {
                colorPreview.style.backgroundColor = this.value;
            });
            
            // إضافة حدث للحذف
            colorItem.querySelector('.remove-color').addEventListener('click', function() {
                colorItem.remove();
            });
        });

        // إضافة أحداث الحذف للأحجام والألوان الموجودة مسبقاً
        document.querySelectorAll('.remove-size').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.size-item').remove();
            });
        });
        
        document.querySelectorAll('.remove-color').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.color-item').remove();
            });
        });

        // تحديث معاينة الألوان الموجودة مسبقاً
        document.querySelectorAll('input[type="color"]').forEach(input => {
            const preview = input.previousElementSibling;
            preview.style.backgroundColor = input.value;
            
            input.addEventListener('input', function() {
                preview.style.backgroundColor = this.value;
            });
        });

        // التحقق من الصحة قبل الإرسال
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const price = parseFloat(document.getElementById('price').value);
            if (price <= 0) {
                alert('السعر يجب أن يكون أكبر من الصفر');
                e.preventDefault();
                return false;
            }
            
            const quantity = parseInt(document.getElementById('quantity').value);
            if (quantity < 0) {
                alert('الكمية لا يمكن أن تكون سالبة');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>

</html>