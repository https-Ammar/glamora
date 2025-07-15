<?php
require('db.php');

$i = 0;
$finalproducttotal = 0.0;

// التحقق من وجود userid في الكوكيز
if (isset($_COOKIE['userid'])) {
  $userid = $_COOKIE['userid'];

  $stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM cart WHERE userid = ?");
  $stmt->bind_param("s", $userid);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result) {
    $row = $result->fetch_assoc();
    $i = $row['product_count'];
  }

  $stmt->close();
} else {
  // جلب آخر ID مستخدم
  $result = $conn->query("SELECT id FROM users ORDER BY id DESC LIMIT 1");

  if ($result->num_rows > 0) {
    $last_id = $result->fetch_assoc()['id'];
    $newid = $last_id + 1;
  } else {
    $newid = 1;
  }

  // تعيين الكوكي
  setcookie('userid', $newid, time() + (10 * 365 * 24 * 60 * 60), "/");

  // إدخال المستخدم الجديد مع بيانات فارغة (يجب تعديل قاعدة البيانات لقبول null)
  $stmt = $conn->prepare("INSERT INTO users (id, name, email, password) VALUES (?, NULL, NULL, NULL)");
  $stmt->bind_param("i", $newid);
  $stmt->execute();
  $stmt->close();

  $userid = $newid;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GLAMORA</title>
  <!-- تحميل خط كايرو من Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="./style/style.css">

</head>


<body>
  <?php require('./loding.php'); ?>



  <?php require('./header.php'); ?>


  <section id="lod_file">
    <main class="layout">
      <div class="checkout-heading-header">
        <div class="back-btn undefined">

          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g id="Glyph">
              <g id="->">
                <path
                  d="M9.19602 13.8345C9.44079 14.0793 9.8373 14.0804 10.0834 13.837C10.3315 13.5917 10.3326 13.1913 10.0859 12.9446L7.42614 10.2848L14.5852 10.2848C14.9422 10.2848 15.2315 9.99544 15.2315 9.63849C15.2315 9.28155 14.9422 8.99219 14.5852 8.99219L7.42614 8.99219L10.0854 6.33718C10.3326 6.09042 10.3322 5.68981 10.0845 5.44354C9.83798 5.19838 9.43956 5.19894 9.19371 5.44478L5 9.6385L9.19602 13.8345Z"
                  fill="black" fill-opacity="0.6"></path>
              </g>
            </g>
          </svg>

          <a href="#"> Back To Bag</a>
        </div>

        <h1>Cart</h1>
      </div>




      <div class="_main_grid">


        <section class="data_">
          <div class="Customer">
            <div class="Customer_titel">



              <h2 class="stepHeader-title optimizedCheckout-headingPrimary">
                Bag
              </h2>
            </div>


            <!-- <img alt="LDR3095 - Teardrop Diamond &amp; Colored Stones Two-Headed Ring in 18K Gold" data-test="cart-item-image" src="https://cdn11.bigcommerce.com/s-t4k1ukevvr/products/5528/images/24524/144102200421151-2__81782.1732435883.220.290.jpg?c=1"> -->



            <?php if ($i == 0) { ?>
              <!-- إذا كانت السلة فارغة -->
              <div class="row">
                <div class="col text-center">

                  <div class="empty-cart">

                    <svg viewBox="656 573 264 182" version="1.1" xmlns="http://www.w3.org/2000/svg"
                      xmlns:xlink="http://www.w3.org/1999/xlink">
                      <rect id="bg-line" stroke="none" fill-opacity="0.2" fill="#FFE100" fill-rule="evenodd" x="656"
                        y="624" width="206" height="38" rx="19"></rect>
                      <rect id="bg-line" stroke="none" fill-opacity="0.2" fill="#FFE100" fill-rule="evenodd" x="692"
                        y="665" width="192" height="29" rx="14.5"></rect>
                      <rect id="bg-line" stroke="none" fill-opacity="0.2" fill="#FFE100" fill-rule="evenodd" x="678"
                        y="696" width="192" height="33" rx="16.5"></rect>
                      <g id="shopping-bag" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"
                        transform="translate(721.000000, 630.000000)">
                        <polygon id="Fill-10" fill="#FFA800" points="4 29 120 29 120 0 4 0"></polygon>
                        <polygon id="Fill-14" fill="#FFE100" points="120 29 120 0 115.75 0 103 12.4285714 115.75 29">
                        </polygon>
                        <polygon id="Fill-15" fill="#FFE100" points="4 29 4 0 8.25 0 21 12.4285714 8.25 29"></polygon>
                        <polygon id="Fill-33" fill="#FFA800" points="110 112 121.573723 109.059187 122 29 110 29">
                        </polygon>
                        <polygon id="Fill-35" fill-opacity="0.5" fill="#FFFFFF" points="2 107.846154 10 112 10 31 2 31">
                        </polygon>
                        <path
                          d="M107.709596,112 L15.2883462,112 C11.2635,112 8,108.70905 8,104.648275 L8,29 L115,29 L115,104.648275 C115,108.70905 111.7365,112 107.709596,112"
                          id="Fill-36" fill="#FFE100"></path>
                        <path
                          d="M122,97.4615385 L122,104.230231 C122,108.521154 118.534483,112 114.257931,112 L9.74206897,112 C5.46551724,112 2,108.521154 2,104.230231 L2,58"
                          id="Stroke-4916" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
                        <polyline id="Stroke-4917" stroke="#000000" stroke-width="3" stroke-linecap="round"
                          stroke-linejoin="round" points="2 41.5 2 29 122 29 122 79"></polyline>
                        <path
                          d="M4,50 C4,51.104 3.104,52 2,52 C0.896,52 0,51.104 0,50 C0,48.896 0.896,48 2,48 C3.104,48 4,48.896 4,50"
                          id="Fill-4918" fill="#000000"></path>
                        <path d="M122,87 L122,89" id="Stroke-4919" stroke="#000000" stroke-width="3"
                          stroke-linecap="round"></path>
                        <polygon id="Stroke-4922" stroke="#000000" stroke-width="3" stroke-linecap="round"
                          stroke-linejoin="round" points="4 29 120 29 120 0 4 0"></polygon>
                        <path
                          d="M87,46 L87,58.3333333 C87,71.9 75.75,83 62,83 L62,83 C48.25,83 37,71.9 37,58.3333333 L37,46"
                          id="Stroke-4923" stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
                        <path d="M31,45 C31,41.686 33.686,39 37,39 C40.314,39 43,41.686 43,45" id="Stroke-4924"
                          stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
                        <path d="M81,45 C81,41.686 83.686,39 87,39 C90.314,39 93,41.686 93,45" id="Stroke-4925"
                          stroke="#000000" stroke-width="3" stroke-linecap="round"></path>
                        <path d="M8,0 L20,12" id="Stroke-4928" stroke="#000000" stroke-width="3" stroke-linecap="round">
                        </path>
                        <path d="M20,12 L8,29" id="Stroke-4929" stroke="#000000" stroke-width="3" stroke-linecap="round">
                        </path>
                        <path d="M20,12 L20,29" id="Stroke-4930" stroke="#000000" stroke-width="3" stroke-linecap="round">
                        </path>
                        <path d="M115,0 L103,12" id="Stroke-4931" stroke="#000000" stroke-width="3"
                          stroke-linecap="round"></path>
                        <path d="M103,12 L115,29" id="Stroke-4932" stroke="#000000" stroke-width="3"
                          stroke-linecap="round"></path>
                        <path d="M103,12 L103,29" id="Stroke-4933" stroke="#000000" stroke-width="3"
                          stroke-linecap="round"></path>
                      </g>
                      <g id="glow" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"
                        transform="translate(768.000000, 615.000000)">
                        <rect id="Rectangle-2" fill="#000000" x="14" y="0" width="2" height="9" rx="1"></rect>
                        <rect fill="#000000"
                          transform="translate(7.601883, 6.142354) rotate(-12.000000) translate(-7.601883, -6.142354) "
                          x="6.60188267" y="3.14235449" width="2" height="6" rx="1"></rect>
                        <rect fill="#000000"
                          transform="translate(1.540235, 7.782080) rotate(-25.000000) translate(-1.540235, -7.782080) "
                          x="0.54023518" y="6.28207994" width="2" height="3" rx="1"></rect>
                        <rect fill="#000000"
                          transform="translate(29.540235, 7.782080) scale(-1, 1) rotate(-25.000000) translate(-29.540235, -7.782080) "
                          x="28.5402352" y="6.28207994" width="2" height="3" rx="1"></rect>
                        <rect fill="#000000"
                          transform="translate(22.601883, 6.142354) scale(-1, 1) rotate(-12.000000) translate(-22.601883, -6.142354) "
                          x="21.6018827" y="3.14235449" width="2" height="6" rx="1"></rect>
                      </g>
                      <polygon id="plus" stroke="none" fill="#7DBFEB" fill-rule="evenodd"
                        points="689.681239 597.614697 689.681239 596 690.771974 596 690.771974 597.614697 692.408077 597.614697 692.408077 598.691161 690.771974 598.691161 690.771974 600.350404 689.681239 600.350404 689.681239 598.691161 688 598.691161 688 597.614697">
                      </polygon>
                      <polygon id="plus" stroke="none" fill="#EEE332" fill-rule="evenodd"
                        points="913.288398 701.226961 913.288398 699 914.773039 699 914.773039 701.226961 917 701.226961 917 702.711602 914.773039 702.711602 914.773039 705 913.288398 705 913.288398 702.711602 911 702.711602 911 701.226961">
                      </polygon>
                      <polygon id="plus" stroke="none" fill="#FFA800" fill-rule="evenodd"
                        points="662.288398 736.226961 662.288398 734 663.773039 734 663.773039 736.226961 666 736.226961 666 737.711602 663.773039 737.711602 663.773039 740 662.288398 740 662.288398 737.711602 660 737.711602 660 736.226961">
                      </polygon>
                      <circle id="oval" stroke="none" fill="#A5D6D3" fill-rule="evenodd" cx="699.5" cy="579.5" r="1.5">
                      </circle>
                      <circle id="oval" stroke="none" fill="#CFC94E" fill-rule="evenodd" cx="712.5" cy="617.5" r="1.5">
                      </circle>
                      <circle id="oval" stroke="none" fill="#8CC8C8" fill-rule="evenodd" cx="692.5" cy="738.5" r="1.5">
                      </circle>
                      <circle id="oval" stroke="none" fill="#3EC08D" fill-rule="evenodd" cx="884.5" cy="657.5" r="1.5">
                      </circle>
                      <circle id="oval" stroke="none" fill="#66739F" fill-rule="evenodd" cx="918.5" cy="681.5" r="1.5">
                      </circle>
                      <circle id="oval" stroke="none" fill="#C48C47" fill-rule="evenodd" cx="903.5" cy="723.5" r="1.5">
                      </circle>
                      <circle id="oval" stroke="none" fill="#A24C65" fill-rule="evenodd" cx="760.5" cy="587.5" r="1.5">
                      </circle>
                      <circle id="oval" stroke="#66739F" stroke-width="2" fill="none" cx="745" cy="603" r="3"></circle>
                      <circle id="oval" stroke="#EFB549" stroke-width="2" fill="none" cx="716" cy="597" r="3"></circle>
                      <circle id="oval" stroke="#FFE100" stroke-width="2" fill="none" cx="681" cy="751" r="3"></circle>
                      <circle id="oval" stroke="#3CBC83" stroke-width="2" fill="none" cx="896" cy="680" r="3"></circle>
                      <polygon id="diamond" stroke="#C46F82" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" fill="none" points="886 705 889 708 886 711 883 708"></polygon>
                      <path
                        d="M736,577 C737.65825,577 739,578.34175 739,580 C739,578.34175 740.34175,577 742,577 C740.34175,577 739,575.65825 739,574 C739,575.65825 737.65825,577 736,577 Z"
                        id="bubble-rounded" stroke="#3CBC83" stroke-width="1" stroke-linecap="round"
                        stroke-linejoin="round" fill="none"></path>
                    </svg>

                    <h3>Dein Warenkorb ist leer</h3>
                    <p class="_1mmswk9g _1mmswk9f _1fragem1y _1fragemkk _1fragemnk _1fragemih">There are special products
                      in GLAMORA that you can buy.</p>
                  </div>
                </div>
              </div>
            <?php } else { ?>



              <div class="loading-skeleton checkout-address">
                <tbody>
                  <?php
                  // الكود الخاص بعرض المنتجات في السلة
                  $stmt = $conn->prepare("SELECT * FROM cart WHERE userid = ?");
                  $stmt->bind_param("s", $userid);
                  $stmt->execute();
                  $getallcartproducts = $stmt->get_result();

                  while ($getcartproducts = $getallcartproducts->fetch_assoc()) {
                    $cartproduct = $getcartproducts['prouductid'];

                    $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                    $productStmt->bind_param("i", $cartproduct);
                    $productStmt->execute();
                    $selectproduct = $productStmt->get_result();
                    $fetchproduct = $selectproduct->fetch_assoc();

                    $getfirstbyfirst = $fetchproduct['total_final_price'] * $getcartproducts['qty'];
                    $finalproducttotal += $getfirstbyfirst;

                    echo '









              <div>

              <div class="product cart-item" data-test="cart-item">


                <figure class="product-column product-figure">
                                                              <div class="card-image ol-lg-3 viwe_img" style="background-image: url(\'./dashboard/dashboard_shop-main/' . htmlspecialchars($fetchproduct['img']) . '\');"></div>

                </figure>
                <div class="cart-item-content">
              

                <div class="_flex">
                    <div class="product-column product-body title-div">
                    <h4 class="product-title optimizedCheckout-contentPrimary" data-test="cart-item-product-title">' . htmlspecialchars($fetchproduct['name']) . '  </h4>
                  </div>
                  <div class="product-column product-actions price-div">
                    <div class="product-price optimizedCheckout-contentPrimary" data-test="cart-item-product-price">
                      EGP ' . htmlspecialchars($getfirstbyfirst) . ' </div>
                  </div></div>

                  <div class="item-quantity"><span class="item-quantity_span" >Quantity <div class="_flex_int">
                                  <button onclick="removemoreone(' . $getcartproducts['id'] . ')" type="button" class="quantity-left-minus btn btn-light btn-number" data-type="minus">-</button>
                                                                  <input type="text" id="quantity" name="quantity" class="form-control input-number text-center" value="' . $getcartproducts['qty'] . '">

                                  <button onclick="addmoreone(' . $getcartproducts['id'] . ')" type="button" class="quantity-right-plus btn btn-light btn-number" data-type="plus">+</button>
                                </div>
                                
 </span>

 </div>


                      <a href="#" onclick="removecart(' . $getcartproducts['id'] . ')">
                                            <div class="cart-item_remove-btn__yNwhA"><div class="CSVG">
                                            
                                            
                                            
                                            <svg width="12" height="12" viewBox="0 0 10 11" fill="#000" xmlns="http://www.w3.org/2000/svg"><path d="M0.757359 1.24264L9.24264 9.72792M9.24264 1.24264L0.757359 9.72792" stroke="black" stroke-width="1.5"></path></svg></div><button class="item_remove" role="removeBtn">Remove</button></div>

                                
                                </a>
                </div>
              </div>
            </div>





            


                            
                            
                            
                            
                            
                            
                            
                            
                            ';
                    $productStmt->close();
                  }
                  $stmt->close();
                  ?>
                </tbody>


              </div>
            </div>





            <!-- Shipping -->
            <div class="Customer Shipping">

              <div class="stepHeader-figure stepHeader-column">
                <div class="stepHeader-counter--complete">
                  <svg height="16" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path>
                  </svg>
                </div>
                <h2 class="stepHeader-title optimizedCheckout-headingPrimary">
                  Shipping
                </h2>
              </div>



            </div>
          </section>







          <!--  -->

          <section class="Summary">
            <div class="Customer">
              <div class="Customer_titel">


                <h2 class="stepHeader-title optimizedCheckout-headingPrimary">
                  Summary
                </h2>
              </div>
              <div class="loading-skeleton">



                <section class="cart-section optimizedCheckout-orderSummary-cartSection">
                  <div data-test="cart-subtotal">
                    <div aria-live="polite" class="cart-priceItem "> <span class="cart-priceItem-label"><span
                          data-test="cart-price-label">Subtotal </span></span><span class="cart-priceItem-value"><span
                          data-test="cart-price-value">EGP <?php echo htmlspecialchars($finalproducttotal); ?>
                        </span></span></div>
                  </div>
                  <div data-test="cart-shipping" class="cart-shipping">
                    <div aria-live="polite" class="cart-priceItem ">
                      <span class="cart-priceItem-label"><span data-test="cart-price-label">Count </span></span><span
                        class="cart-priceItem-value"><span data-test="cart-price-value">(
                          <?php echo htmlspecialchars($i); ?> )</span></span>
                    </div>
                  </div>




                </section>






                <div aria-live="polite" class="cart-priceItem ">
                  <span class="cart-priceItem-label"><span data-test="cart-price-label">Total to Pay </span></span><span
                    class="cart-priceItem-value"><span data-test="cart-price-value">EGP
                      <span><?php echo htmlspecialchars($finalproducttotal); ?></span></span>
                </div>
                <button onclick="window.location.href='Checkout.php';" class="btn btn-danger m-t-xs" type="button">Proceed
                  to Checkout</button>

              </div>



            </div>
          </section>
        </div>

      <?php } ?>
    </main>











    <?php require('footer.php') ?>
  </section>




  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://unpkg.com/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function addmoreone(id) {
      $.ajax({
        type: "POST",
        url: "addmoreone.php",
        data: {
          id: id
        },
        success: function (response) {
          loadCart(); // تحديث السلة
        },
        error: function (xhr, status, error) {
          console.log("AJAX Error:", status, error);
        }
      });
    }

    function removemoreone(id) {
      $.ajax({
        type: "POST",
        url: "removemoreone.php",
        data: {
          id: id
        },
        success: function (response) {
          loadCart(); // تحديث السلة
        },
        error: function (xhr, status, error) {
          console.log("AJAX Error:", status, error);
        }
      });
    }

    function removecart(id) {
      $.ajax({
        type: "POST",
        url: "removecart.php",
        data: {
          id: id
        },
        success: function (response) {
          loadCart(); // تحديث السلة
        },
        error: function (xhr, status, error) {
          console.log("AJAX Error:", status, error);
        }
      });
    }

    function loadCart() {
      // وظيفة لتحميل السلة من جديد
      location.reload();
    }
  </script>



  <style>
    .checkout-heading-header {
      display: none;
    }

    .Customer_titel {
      display: none;
    }

    .stepHeader-figure.stepHeader-column {
      display: none;
    }

    .checkout-address {
      padding: 0;
    }


    .CSVG {
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: smaller;
    }

    .CSVG svg {
      color: white !important;

      fill: white !important;
      width: 8px;
      height: 8px;
    }
  </style>


  <script>
    let lod_file = document.getElementById('lod_file');


    let loading = document.getElementById('loading');




    window.onload = function () {
      lod_file.style.display = 'block'
      loading.style.display = 'none'

    }
  </script>




  <style>
    /**/

    .empty-cart {

      margin: 0 auto;
      text-align: center;

    }

    svg #oval,
    svg #plus,
    svg #diamond,
    svg #bubble-rounded {
      -webkit-animation: plopp 4s ease-out infinite;
      animation: plopp 4s ease-out infinite;
    }

    svg #oval:nth-child(1),
    svg #plus:nth-child(1),
    svg #diamond:nth-child(1),
    svg #bubble-rounded:nth-child(1) {
      -webkit-animation-delay: -240ms;
      animation-delay: -240ms;
    }

    svg #oval:nth-child(2),
    svg #plus:nth-child(2),
    svg #diamond:nth-child(2),
    svg #bubble-rounded:nth-child(2) {
      -webkit-animation-delay: -480ms;
      animation-delay: -480ms;
    }

    svg #oval:nth-child(3),
    svg #plus:nth-child(3),
    svg #diamond:nth-child(3),
    svg #bubble-rounded:nth-child(3) {
      -webkit-animation-delay: -720ms;
      animation-delay: -720ms;
    }

    svg #oval:nth-child(4),
    svg #plus:nth-child(4),
    svg #diamond:nth-child(4),
    svg #bubble-rounded:nth-child(4) {
      -webkit-animation-delay: -960ms;
      animation-delay: -960ms;
    }

    svg #oval:nth-child(5),
    svg #plus:nth-child(5),
    svg #diamond:nth-child(5),
    svg #bubble-rounded:nth-child(5) {
      -webkit-animation-delay: -1200ms;
      animation-delay: -1200ms;
    }

    svg #oval:nth-child(6),
    svg #plus:nth-child(6),
    svg #diamond:nth-child(6),
    svg #bubble-rounded:nth-child(6) {
      -webkit-animation-delay: -1440ms;
      animation-delay: -1440ms;
    }

    svg #oval:nth-child(7),
    svg #plus:nth-child(7),
    svg #diamond:nth-child(7),
    svg #bubble-rounded:nth-child(7) {
      -webkit-animation-delay: -1680ms;
      animation-delay: -1680ms;
    }

    svg #oval:nth-child(8),
    svg #plus:nth-child(8),
    svg #diamond:nth-child(8),
    svg #bubble-rounded:nth-child(8) {
      -webkit-animation-delay: -1920ms;
      animation-delay: -1920ms;
    }

    svg #oval:nth-child(9),
    svg #plus:nth-child(9),
    svg #diamond:nth-child(9),
    svg #bubble-rounded:nth-child(9) {
      -webkit-animation-delay: -2160ms;
      animation-delay: -2160ms;
    }

    svg #oval:nth-child(10),
    svg #plus:nth-child(10),
    svg #diamond:nth-child(10),
    svg #bubble-rounded:nth-child(10) {
      -webkit-animation-delay: -2400ms;
      animation-delay: -2400ms;
    }

    svg #oval:nth-child(11),
    svg #plus:nth-child(11),
    svg #diamond:nth-child(11),
    svg #bubble-rounded:nth-child(11) {
      -webkit-animation-delay: -2640ms;
      animation-delay: -2640ms;
    }

    svg #oval:nth-child(12),
    svg #plus:nth-child(12),
    svg #diamond:nth-child(12),
    svg #bubble-rounded:nth-child(12) {
      -webkit-animation-delay: -2880ms;
      animation-delay: -2880ms;
    }

    svg #oval:nth-child(13),
    svg #plus:nth-child(13),
    svg #diamond:nth-child(13),
    svg #bubble-rounded:nth-child(13) {
      -webkit-animation-delay: -3120ms;
      animation-delay: -3120ms;
    }

    svg #oval:nth-child(14),
    svg #plus:nth-child(14),
    svg #diamond:nth-child(14),
    svg #bubble-rounded:nth-child(14) {
      -webkit-animation-delay: -3360ms;
      animation-delay: -3360ms;
    }

    svg #oval:nth-child(15),
    svg #plus:nth-child(15),
    svg #diamond:nth-child(15),
    svg #bubble-rounded:nth-child(15) {
      -webkit-animation-delay: -3600ms;
      animation-delay: -3600ms;
    }

    svg #oval:nth-child(16),
    svg #plus:nth-child(16),
    svg #diamond:nth-child(16),
    svg #bubble-rounded:nth-child(16) {
      -webkit-animation-delay: -3840ms;
      animation-delay: -3840ms;
    }

    svg #bg-line:nth-child(2) {
      fill-opacity: 0.3;
    }

    svg #bg-line:nth-child(3) {
      fill-opacity: 0.4;
    }

    @-webkit-keyframes plopp {
      0% {
        transform: translate(0, 0);
        opacity: 1;
      }

      100% {
        transform: translate(0, -10px);
        opacity: 0;
      }
    }

    @keyframes plopp {
      0% {
        transform: translate(0, 0);
        opacity: 1;
      }

      100% {
        transform: translate(0, -10px);
        opacity: 0;
      }
    }

    /**/
  </style>

</body>

</html>