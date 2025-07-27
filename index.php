<?php
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => true,
  'use_strict_mode' => true
]);

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once('./db.php');
$imagePath = './dashboard/';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($conn) {
  $check_stmt = $conn->prepare("SELECT id FROM site_visits WHERE ip_address = ? AND DATE(visit_time) = CURDATE()");
  if ($check_stmt) {
    $check_stmt->bind_param("s", $ip);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows === 0) {
      $country = "Unknown";
      $context = stream_context_create(['http' => ['timeout' => 2]]);
      $api_url = "http://ip-api.com/json/" . urlencode($ip);
      $api_response = @file_get_contents($api_url, false, $context);

      if ($api_response !== false) {
        $data = json_decode($api_response, true);
        if (!empty($data['country'])) {
          $country = (string) $data['country'];
        }
      }
      $insert_stmt = $conn->prepare("INSERT INTO site_visits (ip_address, country) VALUES (?, ?)");
      if ($insert_stmt) {
        $insert_stmt->bind_param("ss", $ip, $country);
        $insert_stmt->execute();
        $insert_stmt->close();
      }
    }
    $check_stmt->close();
  }
} else {
  die("Database connection failed");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
  <title>GLAMORA</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
    rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="./assets/style/main.css" />
</head>

<body>
  <canvas id="message"></canvas>
  <?php require('./assets/page/loding.php'); ?>
  <div class="_Tiket">
    <p>GLAMORA</p>
  </div>
  <section id="lod_file">
    <?php require('./header.php'); ?>
    <div class="slider">
      <?php
      if (!$conn) {
        die("Connection failed");
      }

      $catidneed = isset($_GET['catid']) ? intval($_GET['catid']) : 1;

      $stmtAds = $conn->prepare("SELECT * FROM ads WHERE categoryid = ? LIMIT 1");
      if ($stmtAds === false) {
        die("Error preparing statement");
      }

      $stmtAds->bind_param("i", $catidneed);
      $stmtAds->execute();
      $selectad = $stmtAds->get_result();

      if ($selectad->num_rows > 0) {
        $fetchad = $selectad->fetch_assoc();
        echo '<a class="slider" href="' . htmlspecialchars($fetchad['linkaddress'], ENT_QUOTES, 'UTF-8') . '">
                      <div class="banner-content p-5 add_link first" style="background-image: url(' . $imagePath . htmlspecialchars($fetchad['photo'], ENT_QUOTES, 'UTF-8') . ');">
                      <div class="put_first ">q</div>
                      </div>
                  </a>';
      } else {
        echo '<p></p>';
      }

      $stmtAds->close();
      ?>
    </div>

    <div class="slider-container">
      <div class="Categories_ads owl-carousel">
        <?php
        $sqlcat = $conn->prepare("SELECT * FROM categories WHERE parent_id = 0 OR parent_id IS NULL LIMIT 8");
        $sqlcat->execute();
        $result = $sqlcat->get_result();

        while ($fetchcat = $result->fetch_assoc()) {
          $image = htmlspecialchars($fetchcat['image'] ?? 'default.jpg', ENT_QUOTES, 'UTF-8');
          $name = htmlspecialchars($fetchcat['name'] ?? '', ENT_QUOTES, 'UTF-8');
          $id = (int) $fetchcat['id'];
          echo '<div class="main_cat item">
                    <a href="./assets/page/categories.php?id=' . $id . '">
                        <div class="_Categories_img" style="background-image: url(\'' . $imagePath . $image . '\');"></div>
                        <h2>' . $name . '</h2>
                    </a>
                </div>';
        }
        $sqlcat->close();
        ?>
      </div>
    </div>
    <main>
      <?php
      for ($i = 0; $i < 100; $i++) {
        $catidneed = $i + 1;

        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? AND parent_id IS NULL");
        $stmt->bind_param("i", $catidneed);
        $stmt->execute();
        $selectcat = $stmt->get_result();
        $fetchcat = $selectcat->fetch_assoc();

        if ($fetchcat) {
          $namecat = htmlspecialchars($fetchcat['name'], ENT_QUOTES, 'UTF-8');

          $stmtSub = $conn->prepare("SELECT id FROM categories WHERE parent_id = ?");
          $stmtSub->bind_param("i", $catidneed);
          $stmtSub->execute();
          $resSub = $stmtSub->get_result();

          $subcatIds = [];
          while ($row = $resSub->fetch_assoc()) {
            $subcatIds[] = $row['id'];
          }

          if (!empty($subcatIds)) {
            ?>
            <div class="slider">
              <?php
              $stmtAds = $conn->prepare("SELECT * FROM ads WHERE categoryid = ?");
              $stmtAds->bind_param("i", $catidneed);
              $stmtAds->execute();
              $selectad = $stmtAds->get_result();

              $counter = 0;
              while ($fetchad = $selectad->fetch_assoc()) {
                $counter++;
                $firstAdClass = ($counter == 1) ? 'first-ad' : '';
                $photo = ltrim($fetchad['photo'] ?? '', './');
                $photoPath = htmlspecialchars($imagePath . $photo, ENT_QUOTES, 'UTF-8');
                $link = htmlspecialchars($fetchad['linkaddress'] ?? '#', ENT_QUOTES, 'UTF-8');
                echo '<a class="slider ' . $firstAdClass . '" href="' . $link . '">
                            <div class="banner-content p-5 add_link" style="background-image: url(\'' . $photoPath . '\');"></div>
                          </a>';
              }
              ?>
            </div>
            <script>
              document.addEventListener("DOMContentLoaded", function () {
                var firstAd = document.querySelector('.first-ad');
                if (firstAd) {
                  firstAd.style.display = 'none';
                }
              });
            </script>

            <?php
            $placeholders = implode(',', array_fill(0, count($subcatIds), '?'));
            $types = str_repeat('i', count($subcatIds));

            $countQuery = "SELECT COUNT(*) as count FROM products WHERE category_id IN ($placeholders)";
            $stmtCount = $conn->prepare($countQuery);
            $stmtCount->bind_param($types, ...$subcatIds);
            $stmtCount->execute();
            $countRes = $stmtCount->get_result();
            $countData = $countRes->fetch_assoc();

            if ($countData['count'] > 0) {
              ?>
              <section class="container__">
                <div class="row">
                  <a href="./assets/page/categories.php?id=<?php echo $catidneed; ?>" class="btn-link text-decoration-none">
                    <h3 class="title"><?php echo $namecat; ?></h3>
                  </a>
                  <div class="slider-container">
                    <div class="owl-carousel js-home-products">
                      <?php
                      $productQuery = "SELECT * FROM products WHERE category_id IN ($placeholders)";
                      $stmtProducts = $conn->prepare($productQuery);
                      $stmtProducts->bind_param($types, ...$subcatIds);
                      $stmtProducts->execute();
                      $selectproduct = $stmtProducts->get_result();

                      while ($fetchproducts = $selectproduct->fetch_assoc()) {
                        $productId = (int) $fetchproducts['id'];
                        $productName = htmlspecialchars($fetchproducts['name'] ?? '', ENT_QUOTES, 'UTF-8');
                        $productImage = htmlspecialchars($imagePath . ltrim($fetchproducts['image'] ?? 'default.jpg', './'), ENT_QUOTES, 'UTF-8');
                        $price = (float) ($fetchproducts['price'] ?? 0);
                        $salePrice = isset($fetchproducts['sale_price']) ? (float) $fetchproducts['sale_price'] : null;
                        $discountPercent = (int) ($fetchproducts['discount_percent'] ?? 0);
                        $finalPrice = $salePrice ?? ($price - ($price * $discountPercent / 100));
                        ?>
                        <div class="item">
                          <a href="./assets/page/view.php?id=<?php echo $productId; ?>" title="<?php echo $productName; ?>">
                            <figure class="bg_img" style="background-image: url('<?php echo $productImage; ?>');">
                              <?php if ($discountPercent > 0): ?>
                                <span class="badge bg-success text"><?php echo $discountPercent; ?> %</span>
                              <?php endif; ?>
                              <?php if (!empty($fetchproducts['is_featured'])): ?>
                                <span class="badge bg-success text">Featured</span>
                              <?php endif; ?>
                              <?php if (!empty($fetchproducts['is_new'])): ?>
                                <span class="badge bg-success text">New</span>
                              <?php endif; ?>
                            </figure>
                          </a>

                          <div class="product-info">
                            <span
                              class="text-muted small"><?php echo htmlspecialchars($fetchproducts['brand'] ?? ''); ?></span><br>
                            <span class="snize-title" style="max-height: 2.8em; -webkit-line-clamp: 2;">
                              <?php echo $productName; ?>
                            </span>
                          </div>

                          <div class="flex_pric playSound" onclick="addcart(<?php echo $productId; ?>)">
                            <button class="d-flex align-items-center nav-link click">Add to Cart</button>
                            <div class="block_P">
                              <span class="price text"><?php echo number_format($finalPrice, 2); ?></span><span>EGP</span>
                            </div>
                          </div>

                          <div class="ptn_" style="display: none;">
                            <div class="input-group product-qty">
                              <span class="input-group-btn">
                                <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                                  <svg width="16" height="16" fill="currentColor" class="bi bi-dash">
                                    <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8" />
                                  </svg>
                                </button>
                              </span>
                              <input type="text" name="quantity"
                                class="form-control input-number quantity<?php echo $productId; ?>" value="1">
                              <span class="input-group-btn">
                                <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                                  <svg width="16" height="16" fill="currentColor" class="bi bi-plus">
                                    <path
                                      d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
                                  </svg>
                                </button>
                              </span>
                            </div>
                          </div>
                        </div>
                        <?php
                      }
                      ?>
                    </div>
                  </div>
                </div>
              </section>
              <?php
            }
          }
        }
      }
      ?>
    </main>
    <?php require('./assets/page/footer.php'); ?>
  </section>

  <script>
    window.addEventListener("DOMContentLoaded", () => {
      document.querySelectorAll('.text').forEach(el => {
        const text = el.textContent;
        el.textContent = text.split('.')[0];
      });

      document.querySelectorAll(".playSound").forEach(button => {
        button.addEventListener("click", () => {
          const audio = document.getElementById("audio");
          audio.currentTime = 0;
          audio.play();
          if (navigator.vibrate) navigator.vibrate(200);
        });
      });

      const message = document.getElementById("message");
      if (!localStorage.getItem("messageDisplayed")) {
        localStorage.setItem("messageDisplayed", "true");
        setTimeout(() => {
          message.style.opacity = "0";
          setTimeout(() => {
            message.style.display = "none";
          }, 500);
        }, 10000);
      } else {
        message.style.display = "none";
      }

      const lod_file = document.getElementById('lod_file');
      const loading = document.getElementById('loading');
      if (lod_file && loading) {
        lod_file.style.display = 'block';
        loading.style.display = 'none';
      }
    });

    function loadCart() {
      $.ajax({
        type: "GET",
        url: "./assets/page/showcart.php",
        success: function (response) {
          $('#offcanvasCart').html(response);
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function addcart(productid) {
      const quantity = $('.quantity' + productid).val() || 1;
      const csrfToken = $('meta[name="csrf-token"]').attr('content');

      $.ajax({
        type: "POST",
        url: "./assets/page/add_to_cart.php",
        data: {
          product_id: productid,
          quantity: quantity,
          csrf_token: csrfToken
        },
        success: function (response) {
          try {
            const data = JSON.parse(response);
            if (data.success) {
              loadCart();
            } else {
              console.error(data.message);
            }
          } catch (e) {
            console.error("Error parsing response:", e);
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function addmoreone(id) {
      $.ajax({
        type: "POST",
        url: "./assets/page/addmoreone.php",
        data: { id },
        success: loadCart,
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removemoreone(id) {
      $.ajax({
        type: "POST",
        url: "./assets/page/removemoreone.php",
        data: { id },
        success: loadCart,
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removecart(id) {
      $.ajax({
        type: "POST",
        url: "./assets/page/removecart.php",
        data: { id },
        success: loadCart,
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    window.addEventListener("DOMContentLoaded", loadCart);

    $(document).ready(function () {
      $(".js-home-products").owlCarousel({
        loop: false,
        margin: 10,
        nav: true,
        autoplay: true,
        autoplayTimeout: 3000,
        responsive: {
          0: { items: 2 },
          600: { items: 2 },
          1000: { items: 5 },
        },
      });
    });
  </script>
  <audio id="audio" src="./assets/page/like.mp3"></audio>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
</body>

</html>