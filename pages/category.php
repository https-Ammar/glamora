<?php
require('./db.php');

// التحقق من رقم التصنيف
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('Location: ./categories.php');
  exit();
}

$id = intval($_GET['id']);

// جلب بيانات التصنيف
$stmtCat = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmtCat->bind_param("i", $id);
$stmtCat->execute();
$resultCat = $stmtCat->get_result();
$category = $resultCat->fetch_assoc();

if (!$category) {
  header('Location: ./categories.php');
  exit();
}

// إذا كان التصنيف أساسي، اجلب منتجات التصنيفات الفرعية أيضًا
$categoryIds = [$id];
if ((int) $category['parent_id'] === 0) {
  $stmtSubs = $conn->prepare("SELECT id FROM categories WHERE parent_id = ?");
  $stmtSubs->bind_param("i", $id);
  $stmtSubs->execute();
  $resultSubs = $stmtSubs->get_result();
  while ($sub = $resultSubs->fetch_assoc()) {
    $categoryIds[] = $sub['id'];
  }
}
$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
$types = str_repeat('i', count($categoryIds));
?>

<!DOCTYPE html>
<html lang="ar">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GLAMORA | <?php echo htmlspecialchars($category['name']); ?></title>
  <link rel="stylesheet" href="../style/main.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
  <?php require('./loding.php'); ?>

  <section id="lod_file">
    <?php require('./header.php'); ?>

    <main>
      <!-- عرض الإعلانات -->
      <section class="container_" style="display:none">
        <div class="container-fluid_">
          <div class="row">
            <?php
            $stmtAds = $conn->prepare("SELECT * FROM ads WHERE categoryid = ?");
            $stmtAds->bind_param("i", $id);
            $stmtAds->execute();
            $adsResult = $stmtAds->get_result();
            while ($ad = $adsResult->fetch_assoc()) {
              $adLink = htmlspecialchars($ad['linkaddress']);
              $adImage = htmlspecialchars('./dashboard/dashboard_shop-main/' . ltrim($ad['photo'], './'));
              echo '
              <div class="col-md-6_">
                <a href="' . $adLink . '">
                  <div class="banner-content p-5 add_link" style="background-image: url(' . $adImage . ');"></div>
                </a>
              </div>';
            }
            ?>
          </div>
        </div>
      </section>

      <!-- عنوان القسم -->
      <section>
        <div class="codntainer_-flui_ swiper-wrapper_">
          <div class="row">
            <div class="col-md-12">
              <div class="section-header d-flex justify-content-between">
                <div class="panel-block-row col-12 sectionhead">
                  <div class="content-heading">
                    <a href="#" class="btn-link text-decoration-none">
                      <h3 class="title"><?php echo htmlspecialchars($category['name']); ?></h3>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- عرض قائمة التصنيفات -->
          <div class="row">
            <div class="col-md-12">
              <div class="Menu_list">
                <ul id="accordion" class="accordion">
                  <?php
                  $allCats = $conn->query("SELECT * FROM categories");
                  while ($cat = $allCats->fetch_assoc()) {
                    echo '<li class="link">
                      <a href="./Categories.php?id=' . intval($cat['id']) . '" class="nav-link">'
                      . htmlspecialchars($cat['name']) . '</a>
                    </li>';
                  }
                  ?>
                </ul>
              </div>

              <!-- عرض المنتجات -->
              <div class="product-grid">
                <?php
                $stmtProducts = $conn->prepare("SELECT * FROM products WHERE category_id IN ($placeholders)");
                $stmtProducts->bind_param($types, ...$categoryIds);
                $stmtProducts->execute();
                $productsResult = $stmtProducts->get_result();

                while ($product = $productsResult->fetch_assoc()) {
                  $productName = htmlspecialchars($product['name']);
                  $productImage = 'http://localhost:8888/glamora/dashboard/' . htmlspecialchars($product['image']);
                  $productPrice = number_format($product['price'], 2);
                  $productDiscount = (int) $product['discount_percent'];
                  $finalPrice = $product['sale_price'] ?? $product['price'];

                  echo '
                  <div class="product-item swiper-slide">
                    <a href="view.php?id=' . intval($product['id']) . '" title="' . $productName . '">
                      <figure class="bg_img" style="background-image: url(\'' . $productImage . '\');">';
                  if ($productDiscount > 0) {
                    echo '<span class="badge bg-success text">' . $productDiscount . '%</span>';
                  }
                  echo '</figure></a>
                    <span class="snize-attribute">Source Beauty</span>
                    <span class="snize-title" style="max-height: 2.8em;-webkit-line-clamp: 2;">' . $productName . '</span>
                    <div class="flex_pric playSound" onclick="addcart(' . $product['id'] . ')">
                      <button class="d-flex align-items-center nav-link click">Add to Cart</button>
                      <div class="block_P">
                        <span class="price text">' . number_format($finalPrice, 2) . '</span><span>EGP</span>
                      </div>
                    </div>
                    <div class="ptn_" style="display: none;">
                      <div class="input-group product-qty">
                        <span class="input-group-btn">
                          <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">-</button>
                        </span>
                        <input type="text" id="quantity" name="quantity" class="form-control input-number quantity' . $product['id'] . '" value="1">
                        <span class="input-group-btn">
                          <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">+</button>
                        </span>
                      </div>
                    </div>
                  </div>';
                }
                ?>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <?php require('footer.php'); ?>
  </section>

  <audio id="audio" src="./like.mp3"></audio>

  <script src="./js/plugins.js"></script>
  <script src="./js/script.js"></script>
  <script>
    document.querySelectorAll(".playSound").forEach(button => {
      button.addEventListener("click", () => {
        const audio = document.getElementById("audio");
        audio.currentTime = 0;
        audio.play();
        if (navigator.vibrate) navigator.vibrate(200);
      });
    });

    function loadCart() {
      $.ajax({
        type: "GET",
        url: "show_cart.php",
        success: function (response) {
          $('#offcanvasCart').html(response);
        }
      });
    }

    loadCart();

    function addcart(productid) {
      const quantity = $('.quantity' + productid).val();
      $.post("add_cart.php", { productid: productid, qty: quantity }, loadCart);
    }

    function addmoreone(id) {
      $.post("addmoreone.php", { id: id }, loadCart);
    }

    function removemoreone(id) {
      $.post("removemoreone.php", { id: id }, loadCart);
    }

    function removecart(id) {
      $.post("remove_cart.php", { id: id }, loadCart);
    }

    window.onload = function () {
      document.getElementById('lod_file').style.display = 'block';
      document.getElementById('loading').style.display = 'none';
    }
  </script>
</body>

</html>