<!--  -->
<?php
require('./db.php');
if (isset($_GET['id'])) {
  $id = $_GET['id'];
} else {
  header('location:./index.php');
}
$selectcat = mysqli_query($conn, "SELECT * FROM catageories WHERE id = $id");
$fetchassoc = mysqli_fetch_assoc($selectcat);



?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GLAMORA</title>
  <!-- link -->

  <link rel="stylesheet" type="text/css" href="../style/main.css" />

  <!--  -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap" rel="stylesheet">
</head>

<body>
  <?php require('./loding.php'); ?>



  <section id="lod_file">
    <?php require('./header.php') ?>


    <main>

      <section class="container_" style="display:none">
        <div class="container-fluid_">






          <div class="row">
            <?php
            // Use prepared statements for the `ads` query
            $stmtAds = $conn->prepare("SELECT * FROM `ads` WHERE categoryid = ?");
            $stmtAds->bind_param("i", $id);
            $stmtAds->execute();
            $selectad = $stmtAds->get_result();

            while ($fetchad = $selectad->fetch_assoc()) {
              echo '<div class="col-md-6_">
                    <a href="' . $fetchad['linkaddress'] . '">
                        <div class="banner-content p-5 add_link" style="background-image: url(./dashboard/dashboard_shop-main/' . $fetchad['photo'] . ');"></div>
                    </a>
                </div>';
            }
            ?>
          </div>
        </div>
      </section>


      <!--  -->



      <section>
        <div class="codntainer_-flui_ .swiper-wrapper_">




          <div class="row">
            <div class="col-md-12">
              <div class="section-header d-flex justify-content-between">


                <div class="panel-block-row  panel-block col-des-12 block96 col-tb-12 col-mb-12 sectionhead ">
                  <div>
                    <div class="content-heading">

                      <a href="#" class="btn-link text-decoration-none">
                        <h3 class="title"><?php echo $fetchassoc['name'] ?></h3>
                      </a>



                    </div>
                  </div>
                </div>



              </div>
            </div>
          </div>




          <div class="row">
            <div class="col-md-12">


              <div class="Menu_list">


                <ul id="accordion" class="accordion">




                  <?php
                  $sqlcat = mysqli_query($conn, "SELECT * FROM catageories"); // Fixed the function
                  
                  while ($fetchcat = mysqli_fetch_assoc($sqlcat)) {
                    echo '<li  class="link">
<a   href="./Categories.php?id=' . $fetchcat['id'] . '" class="nav-link">' . $fetchcat['name'] . '
</a>
</li>';
                  }
                  ?>





                </ul>




              </div>


              <div class="product-grid ">
                <?php
                // Use prepared statements for products query
                $stmtProducts = $conn->prepare("SELECT * FROM products WHERE category_id = ?");
                $stmtProducts->bind_param("i", $id);
                $stmtProducts->execute();
                $selectproduct = $stmtProducts->get_result();

                while ($fetchproducts = $selectproduct->fetch_assoc()) {
                  $productName = htmlspecialchars($fetchproducts['name']);
                  $productImage = './dashboard/dashboard_shop-main/' . htmlspecialchars($fetchproducts['img']); // Ensure correct image path
                  $productfinalprice = htmlspecialchars($fetchproducts['total_final_price']);
                  $productDiscount = htmlspecialchars($fetchproducts['discount']); // Assuming there's a discount field
                  ?>
                  <div class="product-item swiper-slide">


                    <a href="view.php?id=<?php echo $fetchproducts['id'] ?>" title="<?php echo $productName; ?>">
                      <figure class="bg_img" style="background-image: url('<?php echo $productImage; ?>');">

                        <?php if ($fetchproducts['discount'] != 0) { ?>
                          <span class="badge bg-success  text"><?php echo $productDiscount; ?> %</span>
                        <?php } ?>

                      </figure>
                    </a>







                    <span class="snize-attribute"><span class="snize-attribute-title"></span> Source Beauty</span>
                    <span class="snize-title"
                      style="max-height: 2.8em;-webkit-line-clamp: 2;"><?php echo $productName; ?></span>






                    <div class="flex_pric playSound" onclick='addcart(<?php echo $fetchproducts["id"] ?>)'>

                      <button class="d-flex align-items-center nav-link click"> Add to Cart





                      </button>
                      <div class="block_P">
                        <span class="price text"><?php echo $productfinalprice; ?> </span>
                        <span>EGP</span>
                      </div>


                    </div>





                    <div class="ptn_" style="display: none;">
                      <div class="input-group product-qty">
                        <span class="input-group-btn">
                          <button type="button" class="quantity-left-minus btn btn-danger btn-number" data-type="minus">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                              class="bi bi-dash" viewBox="0 0 16 16">
                              <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8"></path>
                            </svg>
                          </button>
                        </span>
                        <input type="text" id="quantity" name="quantity"
                          class="form-control input-number quantity<?php echo $fetchproducts["id"] ?>" value="1">
                        <span class="input-group-btn">
                          <button type="button" class="quantity-right-plus btn btn-success btn-number" data-type="plus">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                              class="bi bi-plus" viewBox="0 0 16 16">
                              <path
                                d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4">
                              </path>
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
        </div>
      </section>











      <!---->

    </main>
    <?php require('footer.php') ?>
  </section>



  <audio id="audio" src="./like.mp3"></audio>



  <script src="./js/plugins.js"></script>
  <script src="./js/script.js"></script>



  <script>
    document.querySelectorAll(".playSound").forEach(function (button) {
      button.addEventListener("click", function () {
        var audio = document.getElementById("audio");
        audio.currentTime = 0; // لإعادة تشغيل الصوت من البداية
        audio.play();

        // إضافة اهتزاز
        if (navigator.vibrate) {
          navigator.vibrate(200); // يهتز لمدة 200 مللي ثانية
        }
      });
    });
  </script>





  <script>
    function loadCart() {
      $.ajax({
        type: "GET",
        url: "showcart.php",
        success: function (response) {
          $('#offcanvasCart').html(response);
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    // Load the cart initially
    loadCart();

    function addcart(productid) {
      var quantity = $('.quantity' + productid).val(); // Get the quantity value

      $.ajax({
        type: "POST",
        url: "./addcartproduct.php",
        data: {
          productid: productid,
          qty: quantity // Pass the quantity value correctly
        },
        success: function (response) {
          loadCart()
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function addmoreone(id) {
      $.ajax({
        type: "POST",
        url: "./addmoreone.php",
        data: {
          id: id,
        },
        success: function (response) {
          loadCart()
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removemoreone(id) {
      $.ajax({
        type: "POST",
        url: "./removemoreone.php",
        data: {
          id: id,
        },
        success: function (response) {
          loadCart()
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removecart(id) {
      $.ajax({
        type: "POST",
        url: "./removecart.php",
        data: {
          id: id,
        },
        success: function (response) {
          loadCart()
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }
  </script>




  <script>
    function loadCart() {
      $.ajax({
        type: "GET",
        url: "showcart.php",
        success: function (response) {
          $('#offcanvasCart').html(response);
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    // Load the cart initially
    loadCart();

    function addcart(productid) {
      var quantity = $('.quantity' + productid).val(); // Get the quantity value

      $.ajax({
        type: "POST",
        url: "addcartproduct.php",
        data: {
          productid: productid,
          qty: quantity // Pass the quantity value correctly
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function addmoreone(id) {
      $.ajax({
        type: "POST",
        url: "addmoreone.php",
        data: {
          id: id,
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removemoreone(id) {
      $.ajax({
        type: "POST",
        url: "removemoreone.php",
        data: {
          id: id,
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function removecart(id) {
      $.ajax({
        type: "POST",
        url: "removecart.php",
        data: {
          id: id,
        },
        success: function (response) {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }
  </script>







  <script>
    let lod_file = document.getElementById('lod_file');


    let loading = document.getElementById('loading');




    window.onload = function () {
      lod_file.style.display = 'block'
      loading.style.display = 'none'

    }
  </script>






</body>

</html>