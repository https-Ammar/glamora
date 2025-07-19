<?php
require('./db.php');
session_start();

$finalproducttotal = 0.0;
$i = 0;
$couponDiscount = 0.0; // 👈 مهم: تعريف المتغير لتفادي التحذير
$couponApplied = false;

$userData = [
  'name' => '',
  'phone' => '',
  'address' => '',
  'city' => '',
];

$userid = null;

if (isset($_SESSION['userId'])) {
  $userid = $_SESSION['userId'];

  if (isset($_COOKIE['userid']) && $_COOKIE['userid'] != $userid) {
    $cookieUserId = $_COOKIE['userid'];
    $stmt = $conn->prepare("UPDATE cart SET userid = ? WHERE userid = ?");
    $stmt->bind_param("ss", $userid, $cookieUserId);
    $stmt->execute();
    $stmt->close();
    setcookie('userid', $userid, time() + (10 * 365 * 24 * 60 * 60), "/");
  }

  $stmt = $conn->prepare("SELECT name, phone, address, city FROM users WHERE id = ?");
  $stmt->bind_param("i", $userid);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $userData = $result->fetch_assoc();
  }
  $stmt->close();

} elseif (isset($_COOKIE['userid'])) {
  $userid = $_COOKIE['userid'];
} else {
  $result = $conn->query("SELECT id FROM users ORDER BY id DESC LIMIT 1");
  $newid = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['id'] + 1 : 1;
  $userid = $newid;
  setcookie('userid', $userid, time() + (10 * 365 * 24 * 60 * 60), "/");
  $stmt = $conn->prepare("INSERT INTO users(id, name, email, password) VALUES (?, '', '', '')");
  $stmt->bind_param("i", $userid);
  $stmt->execute();
  $stmt->close();
}

// حساب عدد المنتجات في السلة
$stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM cart WHERE userid = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
  $i = $result->fetch_assoc()['product_count'];
}
$stmt->close();

// حساب المجموع النهائي
if ($i > 0) {
  $stmt = $conn->prepare("SELECT * FROM cart WHERE userid = ?");
  $stmt->bind_param("s", $userid);
  $stmt->execute();
  $getallcartproducts = $stmt->get_result();

  while ($getcartproducts = $getallcartproducts->fetch_assoc()) {
    $productId = $getcartproducts['prouductid'];
    $productStmt = $conn->prepare("SELECT total_final_price FROM products WHERE id = ?");
    $productStmt->bind_param("i", $productId);
    $productStmt->execute();
    $productResult = $productStmt->get_result();
    if ($fetchproduct = $productResult->fetch_assoc()) {
      $total = $fetchproduct['total_final_price'] * $getcartproducts['qty'];
      $finalproducttotal += $total;
    }
    $productStmt->close();
  }
  $stmt->close();
}

// ✅ تطبيق الكوبون
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coupon_code'])) {
  $code = trim($_POST['coupon_code']);

  $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND expires_at > NOW() AND max_uses > 0");
  $stmt->bind_param("s", $code);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows > 0) {
    $coupon = $result->fetch_assoc();

    if ($coupon['discount_type'] === 'percentage') {
      $couponDiscount = $finalproducttotal * ($coupon['discount_value'] / 100);
    } else {
      $couponDiscount = $coupon['discount_value'];
    }

    if ($couponDiscount > $finalproducttotal) {
      $couponDiscount = $finalproducttotal;
    }

    $finalproducttotal -= $couponDiscount;
    $couponApplied = true;
  } else {
    echo "<p style='color:red;'>❌ الكوبون غير صالح أو منتهي.</p>";
  }

  $stmt->close();
}
?>

<!-- ✅ نموذج الكوبون -->
<form method="POST">
  <input type="text" name="coupon_code" placeholder="أدخل كود الخصم" required>
  <button type="submit">تطبيق</button>
</form>

<!-- ✅ عرض الأسعار -->
<p>السعر قبل الخصم: <?php echo number_format($finalproducttotal + $couponDiscount, 2); ?> ج.م</p>
<p>الخصم: <?php echo number_format($couponDiscount, 2); ?> ج.م</p>
<p>السعر بعد الخصم: <?php echo number_format($finalproducttotal, 2); ?> ج.م</p>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>GLAMORA </title>
  <!-- تحميل خط كايرو من Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <!--  -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap" rel="stylesheet">

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/app.BtDbFeTa.css"
    crossorigin fetchPriority="high" />

</head>

<body>



  <div id="app">
    <div class="g9gqqf1 g9gqqf0 _1fragemnw g9gqqfc g9gqqfa _1fragemt0 g9gqqf6 g9gqqf2 _1fragemn6 _1fragemna">

      <div class="cm5pp U3Rye FeQiM oYrwu _1fragemna _1fragemn6 _1fragemt0">
        <div class="nMPKH iYA3J">
          <button aria-controls="disclosure_details" aria-expanded="false" class="WtpiW">
            <span class="smIFm"><span class="_4ptW6" id="_PTW"><span class="fCEli">Order summary</span><span
                  class="a8x1wu2 a8x1wu1 _1fragemof _1fragem1t _1fragemkk _1fragemka a8x1wug a8x1wuk a8x1wui _1fragem2i _1fragemst a8x1wum a8x1wul a8x1wuy"><svg
                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14" focusable="false" aria-hidden="true"
                    class="a8x1wu10 a8x1wuz _1fragem1y _1fragemof _1fragemkk _1fragemka _1fragemnm">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="m11.9 5.6-4.653 4.653a.35.35 0 0 1-.495 0L2.1 5.6"></path>
                  </svg></span></span><span>
                <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem4v _1fragem6o _1fragem2s _1fragemoi">
                  <span class="_19gi7yt1h _1fragems3">Original price</span>
                  <p
                    class="_1x52f9s1 _1x52f9s0 _1fragemlj _1x52f9sz _1x52f9sy _1fragemny _1x52f9s3 _1x52f9so notranslate">
                    EGP <?php echo $finalproducttotal; ?>
                  </p>
                </div>
              </span></span>
          </button>
        </div>
        <div class="Sxi8I">






          <div class="i4DWM _1fragemna _1fragemn7 _1fragemt0" id="HID_DIV">

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
              <div class="_4QenE">
                <aside>
                  <div>
                    <section class="_1fragem1y _1fragemlj">
                      <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem46 _1fragem5z _1fragem2s">
                        <h2 class="n8k95wf _1fragems3">Order summary</h2>
                        <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem46 _1fragem5z _1fragem2s">
                          <section class="_1fragem1y _1fragemlj">
                            <div class="_6zbcq51o _1fragems3">
                              <h3 id="ResourceList22" class="n8k95w1 n8k95w0 _1fragemlj n8k95w4">
                                Shopping cart
                              </h3>
                            </div>
                            <div role="table" aria-labelledby="ResourceList22"
                              class="_6zbcq54 _6zbcq53 _1fragem28 _1fragemnn _6zbcq5m _6zbcq5a _1fragem3w _6zbcq5s">



                              <!---->
                              <!---->
                              <!---->



                              <section class="data_">
                                <div class="Customer">
                                  <div class="Customer_titel">
                                    <h2 class="stepHeader-title optimizedCheckout-headingPrimary">
                                      Bag
                                    </h2>
                                  </div>



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

                                        // جلب تفاصيل المنتج
                                        $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                                        $productStmt->bind_param("i", $cartproduct);
                                        $productStmt->execute();
                                        $selectproduct = $productStmt->get_result();
                                        $fetchproduct = $selectproduct->fetch_assoc();

                                        // حساب السعر الإجمالي
                                        $getfirstbyfirst = $fetchproduct['total_final_price'] * $getcartproducts['qty'];
                                        $finalproducttotal += $getfirstbyfirst;

                                        // عرض المنتج في السلة مع الصورة والكميّة
                                        echo '
                        
                        
                        
                                             <div
                                role="rowgroup"
                                class="_6zbcq513 _6zbcq512 _1fragem28 _1fragemnn _6zbcq5m _6zbcq5a _1fragem3w"
                              >
                                <div
                                  role="row"
                                  class="_6zbcq516 _6zbcq515 _1fragem28 _1fragem1t _6zbcq519 _6zbcq518"
                                >
                                  <div
                                    role="cell"
                                    class="_6zbcq51n _6zbcq51m _1fragem28 _1fragemnn _6zbcq51h _6zbcq51e _1fragem78 _6zbcq51c"
                                  >
                                    <div
                                      style="--_16s97g746: 6.4rem"
                                      class="_1fragem1y _1fragemlj _16s97g74b"
                                    >
                                      <div
                                        class="_5uqybw0 _1fragemlj _1fragem28 _1fragem73"
                                      >
                                        <div
                                          class="_5uqybw1 _1fragem28 _1fragemkp _1fragemnt _1fragem3w _1fragem5p _1fragemma _1fragemmf _1fragem73"
                                        >
                                          <div
                                            style="--_1m6j2n30: 1"
                                            class="_1m6j2n34 _1m6j2n33 _1fragemlj _1fragemt4 _1m6j2n3a _1m6j2n39 _1m6j2n35"
                                          >
                                     
                                              
                                              
                                                <div      class="_1h3po424 _1h3po427 _1h3po425 _1fragem1y _1fragemkk _1fragem8r _1fragem87 _1fragem9b _1fragem7n _1fragemb4 _1fragemaf _1fragembt _1fragem9q _1fragemkz _1m6j2n3c _1fragemof _1fragem1t _1m6j2n35"  style="background-image: url(\'./dashboard/dashboard_shop-main/' . htmlspecialchars($fetchproduct['img']) . '\');"></div>
                                              
                                        
                                            </picture>
                                            <div
                                              class="_1m6j2n3m _1m6j2n3l _1frageml9"
                                            >
                                              <div
                                                class="_99ss3s1 _99ss3s0 _1fragem2n _1fragemmc _1fragem6t _99ss3s7 _99ss3s4 _99ss3s2 _1fragemi7 _1fragemge _99ss3sk _99ss3sf _1fragemoy _1fragemp3 _1fragempd _1fragemp8"
                                              >
                                                <span
                                                  class="_99ss3sm _1fragems3"
                                                  >Quantity</span
                                                ><span>' . htmlspecialchars($getcartproducts['qty']) . '</span>
                                              </div>
                                            </div>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                  <div
                                    role="cell"
                                    style="--_16s97g73w: 6.4rem"
                                    class="_6zbcq51n _6zbcq51m _1fragem28 _1fragemnn _6zbcq51i _6zbcq51f _1fragem6t _6zbcq51d _6zbcq51b _1fragemmh _6zbcq51k _1fragemnq _16s97g741"
                                  >
                                    <div class="_1fragem1y _1fragemlj dDm6x">
                                      <p
                                        class="_1x52f9s1 _1x52f9s0 _1fragemlj _1x52f9sv _1x52f9su _1fragemnw"
                                      >
                                ' . htmlspecialchars($fetchproduct['name']) . '
                                      </p>
                                      <div
                                        class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem4v _1fragem6o _1fragem2s"
                                      ></div>
                                    </div>
                                  </div>
                                  <div
                                    role="cell"
                                    class="_6zbcq51n _6zbcq51m _1fragem28 _1fragemnn _6zbcq51i _6zbcq51f _1fragem6t _6zbcq51c _6zbcq51l"
                                  >
                                    <div class="_6zbcq51o _1fragems3">
                                      <span
                                        class="_19gi7yt0 _19gi7ytw _19gi7ytv _1fragemnw"
                                        >1</span
                                      >
                                    </div>
                                  </div>
                                  <div
                                    role="cell"
                                    class="_6zbcq51n _6zbcq51m _1fragem28 _1fragemnn _6zbcq51i _6zbcq51f _1fragem6t _6zbcq51d _6zbcq51b _1fragemmh"
                                  >
                                    <div
                                      class="_197l2oft _1fragemnn _1fragemme _1fragem28 _1fragemlj Byb5s"
                                    >
                                      <span
                                        class="_19gi7yt0 _19gi7ytw _19gi7ytv _1fragemnw notranslate"
                                        > EGP ' . htmlspecialchars($getfirstbyfirst) . ' </span
                                      >
                                    </div>
                                  </div>
                                </div>
                              </div>
                        
                        
                        
                        
                        
                        
                        
                        
                            <div class="product cart-item" data-test="cart-item">
                           
                                                               <div class="card-image ol-lg-3 viwe_img" style="background-image: url(\'./dashboard/dashboard_shop-main/' . htmlspecialchars($fetchproduct['img']) . '\');"></div>


                         
                         
                            </div>';
                                        $productStmt->close();
                                      }
                                      $stmt->close();
                                      ?>
                                    </tbody>
                                  </div>
                                </div>

                                <!-- Shipping -->

                                <br>
                                <div class="Customer Shipping">
                                  <div class="stepHeader-figure stepHeader-column">

                                    <h2 class="stepHeader-title optimizedCheckout-headingPrimary">
                                      Shipping
                                    </h2>
                                  </div>
                                </div>
                              </section>

                              <!---->




                              <!---->
                              <!---->
                              <!---->
                            </div>
                          </section>
                        </div>
                        <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem46 _1fragem5z _1fragem2s">
                          <section class="_1fragem1y _1fragemlj">
                            <h3 class="n8k95wf _1fragems3">
                              Discount code or gift card
                            </h3>
                            <div id="gift-card-field" style="height: auto; overflow: visible"
                              class="_94sxtb1 _94sxtb0 _1fragemjv _1fragemk5 _1fragemlj _1fragemso _94sxtbb _94sxtb4 _1fragemsb">
                              <div>
                                <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem3w _1fragem5p _1fragem2s">


                                  <form action method="POST" novalidate id="Form40" class="km09ry0 _1fragem23">
                                    <div class="km09ry1 _1fragemlj">
                                      <div style="
                                          --_16s97g7a: 1fr;
                                          --_16s97g7k: minmax(
                                            auto,
                                            max-content
                                          );
                                          --_16s97g71e: minmax(0, 1fr)
                                            minmax(auto, max-content);
                                          --_16s97g71o: minmax(
                                            auto,
                                            max-content
                                          );
                                        "
                                        class="_1mrl40q0 _1fragemlj _1fragem2s _1fragem3c _1fragem5p _1fragemm3 _16s97g7f _16s97g7p _16s97g71j _16s97g71t">
                                        <div
                                          class="_7ozb2u2 _7ozb2u1 _1fragem3c _1fragem55 _1fragemlj _1fragem2s _10vrn9p1 _10vrn9p0 _10vrn9p4 _7ozb2u4 _7ozb2u3 _1fragemnb">
                                          <div class="cektnc0 _1fragemlj cektnc5">
                                            <label id="ReductionsInput22-label" for="ReductionsInput22"
                                              class="cektnc3 cektnc1 _1frageml9 _1fragems2 _1fragemsv _1fragemsh _1fragemsc _1fragemsr _1fragemss"><span
                                                class="cektnca"><span
                                                  class="rermvf1 rermvf0 _1fragemjv _1fragemk5 _1fragem1y">Discount code
                                                  or gift
                                                  card</span></span></label>
                                            <div
                                              class="_7ozb2u6 _7ozb2u5 _1fragemlj _1fragem2s _1fragemnl _1fragemsh _1fragemsc _1fragemsr _1fragemsu _7ozb2uc _7ozb2ua _1fragemnb _1fragemt0 _7ozb2ul _7ozb2uh">
                                              <input id="ReductionsInput22" name="reductions"
                                                placeholder="Discount code or gift card" type="text"
                                                aria-labelledby="ReductionsInput22-label" value
                                                class="_7ozb2uq _7ozb2up _1fragemlj _1fragemsv _1fragemof _1fragems1 _7ozb2ut _7ozb2us _1fragemsh _1fragemsc _1fragemsr _7ozb2u11 _7ozb2u1h _7ozb2ur" />
                                            </div>
                                          </div>
                                        </div>
                                        <button type="submit" disabled aria-busy="false" aria-live="polite"
                                          aria-label="Apply Discount Code"
                                          class="_1m2hr9ge _1m2hr9gd _1fragemss _1fragemlj _1fragemnk _1fragem2i _1fragems6 _1fragemsl _1fragemsn _1fragemsc _1m2hr9g18 _1m2hr9g15 _1fragemsb _1fragems0 _1m2hr9g1s _1m2hr9g1q _1m2hr9g12 _1m2hr9gz _1m2hr9g2b _1m2hr9g2a _1fragems2 _1m2hr9g1d _1m2hr9g1b _1fragems7 _1m2hr9g27">
                                          <span
                                            class="_1m2hr9gr _1m2hr9gq _1fragems2 _1fragemsh _1fragemsb _1fragemso _1m2hr9gn _1m2hr9gl _1fragem28 _1fragem6t _1fragems4">Apply</span>
                                        </button>
                                      </div>
                                    </div>

                                  </form>
                                </div>
                              </div>
                            </div>
                          </section>
                        </div>
                        <section class="_1fragem1y _1fragemlj">
                          <div class="nfgb6p2 _1fragems3">
                            <h3 id="MoneyLine-Heading22" class="n8k95w1 n8k95w0 _1fragemlj n8k95w4">
                              Cost summary
                            </h3>
                          </div>
                          <div role="table" aria-labelledby="MoneyLine-Heading22">
                            <div role="rowgroup" class="nfgb6p2 _1fragems3">
                              <div role="row">
                                <div role="columnheader">Item</div>
                                <div role="columnheader">Value</div>
                              </div>
                            </div>
                            <div role="rowgroup" class="nfgb6p1 nfgb6p0 _1fragem2s nfgb6p3">
                              <div role="row"
                                class="_1qy6ue60 _1qy6ue69 _1qy6ue61 _1qy6ue67 _1qy6ue65 _1fragem3h _1fragem5a _1fragem2s">
                                <div role="rowheader" class="_1qy6ue6b">
                                  <span class="_19gi7yt0 _19gi7ytw _19gi7ytv _1fragemnw">Subtotal</span>
                                </div>
                                <div role="cell" class="_1qy6ue6c">
                                  <span class="_19gi7yt0 _19gi7ytw _19gi7ytv _1fragemnw notranslate"> EGP
                                    <?php echo $finalproducttotal / 2; ?></span>
                                </div>
                              </div>
                              <div role="row"
                                class="_1qy6ue60 _1qy6ue6a _1qy6ue61 _1qy6ue67 _1qy6ue65 _1fragem3h _1fragem5a _1fragem2s">
                                <div role="rowheader" class="_1qy6ue6b">
                                  <div class="_5uqybw0 _1fragemlj _1fragem28 _1fragem78">
                                    <div
                                      class="_5uqybw1 _1fragem28 _1fragemkp _1fragemnt _1fragem3c _1fragem55 _1fragemm8 _1fragemmc _1fragem78">
                                      <span class="_19gi7yt0 _19gi7ytw _19gi7ytv _1fragemnw">Count</span>
                                    </div>
                                  </div>
                                </div>
                                <div role="cell" class="_1qy6ue6c">
                                  <span class="_19gi7yt0 _19gi7ytw _19gi7ytv _1fragemnw _19gi7ytn _19gi7ytj notranslate">
                                    ( <?php echo $i ?> ) </span>
                                </div>
                              </div>
                              <div role="row" class="_1x41w3p1 _1x41w3p0 _1fragem2s _1fragemmc _1x41w3p2">
                                <div role="rowheader" class="_1x41w3p6">
                                  <span class="_19gi7yt0 _19gi7yt10 _19gi7ytz _1fragemny _19gi7yt2">Total</span>
                                </div>
                                <div role="cell" class="_1x41w3p7">
                                  <div class="_5uqybw0 _1fragemlj _1fragem28 _1fragem78">
                                    <div
                                      class="_5uqybw1 _1fragem28 _1fragemkp _1fragemnt _1fragem3h _1fragem5a _1fragemmb _1fragem78">
                                      <abbr
                                        class="_19gi7yt0 _19gi7ytu _19gi7ytt _1fragemnv _19gi7ytj notranslate _19gi7yt1c _19gi7yt19 _1fragems7">EGP</abbr><strong
                                        class="_19gi7yt0 _19gi7yt10 _19gi7ytz _1fragemny _19gi7yt2 notranslate">
                                        <?php echo $finalproducttotal / 2; ?></strong>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </section>
                      </div>
                    </section>
                  </div>
                </aside>
              </div>
            <?php } ?>
          </div>


          <div class="_9F1Rf GI5Fn _1fragemna _1fragemn5 _1fragemt8">
            <div class="gdtca">
              <main id="checkout-main" class="djSdi">
                <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem4v _1fragem47 _1fragem6o _1fragem60 _1fragem2s">


                  <form method='POST' action='finalcheckout.php' novalidate id="checkout-form"
                    class="km09ry0 _1fragem23">


                    <div class="BGGdy Geu8c" style="">
                      <div class="M4bqA Geu8c" style="">
                        <div class="_1fragem1y _1fragemlj">
                          <div>
                            <div role="status"
                              class="sdr03s1 sdr03s0 _1fragempn _1fragempj _1fragempr _1fragempf _1fragemf5 _1fragemgy _1fragemdc _1fragemir _1fragemlj _1fragem2s sdr03s4 sdr03s2">


                              <div class="flex">
                                <div class="sdr03s7"><span
                                    class="a8x1wu2 a8x1wu1 _1fragemof _1fragem1t _1fragemkk _1fragemka a8x1wug a8x1wuj a8x1wuh _1fragem1y a8x1wuq a8x1wul a8x1wuy"><svg
                                      xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14" focusable="false"
                                      aria-hidden="true"
                                      class="a8x1wu10 a8x1wuz _1fragem1y _1fragemof _1fragemkk _1fragemka _1fragemnm">
                                      <circle cx="7" cy="7" r="5.6"></circle>
                                      <path stroke-linecap="round" d="M7 10.111V7.1a.1.1 0 0 0-.1-.1h-.678"></path>
                                      <circle cx="7" cy="4.2" r="0.4"></circle>
                                      <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7.002 4.198h-.005v.005h.005z"></path>
                                    </svg></span></div>
                                <div class="sdr03sb">
                                  <h2 class="n8k95w1 n8k95w0 _1fragemlj n8k95w4">Shipping Disclaimer</h2>
                                </div>

                              </div>

                              <div class="sdr03sd sdr03sc _1fragemmh"><button type="button" aria-expanded="false"
                                  aria-controls="Banner0-collapsible-area"
                                  class="sdr03s9 sdr03s8 _1fragem28 _1fragemmc _1fragem6t"><span
                                    class="a8x1wu2 a8x1wu1 _1fragemof _1fragem1t _1fragemkk _1fragemka a8x1wug a8x1wuj a8x1wuh _1fragem1y a8x1wun a8x1wul a8x1wuy"><svg
                                      xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14" focusable="false"
                                      aria-label="View more"
                                      class="a8x1wu10 a8x1wuz _1fragem1y _1fragemof _1fragemkk _1fragemka _1fragemnm">
                                      <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m11.9 5.6-4.653 4.653a.35.35 0 0 1-.495 0L2.1 5.6"></path>
                                    </svg></span></button></div>


                            </div>
                          </div>


                          <div class="_1fragem1y _1fragemf5 _1fragemhs _1fragemdm _1fragemjl _1fragemlj">
                            <div
                              class="mg7oix2 mg7oix0 _1fragemlj mg7oix1 mg7oix9 mg7oix7 _1fragemof mg7oixe mg7oixc mg7oixb _1fragem28 _1fragemmc _1fragemm8 mg7oixg mg7oix3">
                              <div class="mg7oixl">
                                <p
                                  class="_1x52f9s1 _1x52f9s0 _1fragemlj _1x52f9sv _1x52f9su _1fragemnw _1x52f9s9 _1x52f9s6 _1fragems4">
                                  <span class="_19gi7yt0 _19gi7ytw _19gi7ytv _1fragemnw _19gi7ytj">OR</span>
                                </p><span role="separator"></span>
                              </div>
                            </div>
                          </div>


                          <div class="km09ry1 _1fragemlj">

                            <section
                              class="_197l2ofi _197l2ofg _1fragemna _197l2ofp _197l2ofk _1fragemn6 _1fragemt0 _1fragem1y _1fragemf0 _1fragemg0 _1fragemh3 _1fragemht _1fragemd7 _1frageme7 _1fragemiw _1fragemjm _1fragemlj">

                              <div class="BGGdy Geu8c" style="">
                                <div class="M4bqA Geu8c" style="">
                                  <div class="_1fragem1y _1fragemlj">
                                    <div>
                                      <div
                                        class="_1mrl40q0 _1fragemlj _1fragem4v _1fragem6o _1fragemm8 _1fragemmc _1fragem2s _1fragemly _1fragem78 _1fragemoj _16s97g7f _16s97g7p _16s97g71j _16s97g71t"
                                        style="--_16s97g7a: 1fr; --_16s97g7k: minmax(0, 1fr); --_16s97g71e: minmax(auto, max-content) minmax(auto, max-content) minmax(auto, max-content); --_16s97g71o: minmax(0, 1fr);">
                                        <div class="_1fragem1y _1fragemlj"><span
                                            class="_19gi7yt0 _19gi7ytw _19gi7ytv _1fragemnw _19gi7yt2 _19gi7ytc _19gi7yt4 _1fragemmz _19gi7yt5">Earn
                                            $230.30 </span> <span class="_19gi7yt0 _19gi7ytw _19gi7ytv _1fragemnw">by
                                            paying with</span></div>
                                        <div class="_1fragem1y _1fragemlj _16s97g73r" style="--_16s97g73m: 5.65rem;">
                                          <img src="https://assets.getcatch.com/static_assets/logos/catch_dark.png"
                                            alt="Catch" loading="eager"
                                            class="_1h3po424 _1h3po427 _1h3po425 _1fragem1y _1fragemkk">
                                        </div>
                                        <div class="_1fragemeg _1fragemg9 _1fragemcn _1fragemi2 _1fragem1y _1fragemlj">
                                          <button type="button" aria-label="Open how Catch works modal"
                                            class="_1xqelvi1 _1xqelvi0 _1fragemnk _1fragemlj _1fragems6 _1fragemsh _1fragemsc _1fragemsr _1fragems0 _1fragem8m _1fragem82 _1fragem96 _1fragem7i _1fragem1y _1xqelvi4 _1xqelvi3 _1fragem28 _1fragemmg _1xqelvi8"
                                            aria-haspopup="dialog"><span class="_1xqelvi2">ⓘ</span></button>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>


                              <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem3w _1fragem5p _1fragem2s">
                                <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem3c _1fragem55 _1fragem2s">
                                  <h2 class="n8k95w1 n8k95w0 _1fragemlj n8k95w2">
                                    Delivery
                                  </h2>
                                </div>
                                <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem4b _1fragem64 _1fragem2s">
                                  <section aria-label="Shipping address" class="_1fragem1y _1fragemlj">
                                    <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem3w _1fragem5p _1fragem2s">
                                      <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem4b _1fragem64 _1fragem2s">
                                        <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem3w _1fragem5p _1fragem2s">
                                          <div>
                                            <div id="shippingAddressForm">
                                              <div aria-hidden="false" class="r62YW">
                                                <div
                                                  class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem3w _1fragem5p _1fragem2s">
                                                  <div style="
                                                --_16s97g7a: minmax(
                                                  0,
                                                  1fr
                                                );
                                                --_16s97g7k: minmax(
                                                  auto,
                                                  max-content
                                                );
                                                --_16s97g71e: minmax(
                                                  0,
                                                  1fr
                                                );
                                                --_16s97g71o: minmax(
                                                  auto,
                                                  max-content
                                                );
                                              " class="_1mrl40q0 _1fragemlj _1fragem3w _1fragem5p _1fragem2s _1fragemm3 _1fragemlz _16s97g7f _16s97g7p _16s97g71j _16s97g71t">
                                                    <div
                                                      class="_7ozb2u2 _7ozb2u1 _1fragem3c _1fragem55 _1fragemlj _1fragem2s _10vrn9p1 _10vrn9p0 _10vrn9p4 _7ozb2u4 _7ozb2u3 _1fragemnb">
                                                      <div class="cektnc0 _1fragemlj cektnc5">
                                                        <label id="TextField140-label" for="TextField140"
                                                          class="cektnc3 cektnc1 _1frageml9 _1fragems2 _1fragemsv _1fragemsh _1fragemsc _1fragemsr _1fragemss"><span
                                                            class="cektnca"><span
                                                              class="rermvf1 rermvf0 _1fragemjv _1fragemk5 _1fragem1y">First
                                                              Name
                                                            </span></span></label>
                                                        <div
                                                          class="_7ozb2u6 _7ozb2u5 _1fragemlj _1fragem2s _1fragemnl _1fragemsh _1fragemsc _1fragemsr _1fragemsu _7ozb2uc _7ozb2ua _1fragemnb _1fragemt0 _7ozb2ul _7ozb2uh">
                                                          <input id="TextField140" name='cleintname'
                                                            value="<?= htmlspecialchars($userData['name']) ?>" required
                                                            type="text" placeholder="First Name" required
                                                            aria-required="true" aria-labelledby="TextField140-label"
                                                            value autocomplete="shipping given-name"
                                                            class="_7ozb2uq _7ozb2up _1fragemlj _1fragemsv _1fragemof _1fragems1 _7ozb2ut _7ozb2us _1fragemsh _1fragemsc _1fragemsr _7ozb2u11 _7ozb2u1h _7ozb2ur" />
                                                        </div>
                                                      </div>
                                                    </div>
                                                    <div
                                                      class="_7ozb2u2 _7ozb2u1 _1fragem3c _1fragem55 _1fragemlj _1fragem2s _10vrn9p1 _10vrn9p0 _10vrn9p4 _7ozb2u4 _7ozb2u3 _1fragemnb">
                                                      <div class="cektnc0 _1fragemlj cektnc5">
                                                        <label id="TextField141-label" for="TextField141"
                                                          class="cektnc3 cektnc1 _1frageml9 _1fragems2 _1fragemsv _1fragemsh _1fragemsc _1fragemsr _1fragemss"><span
                                                            class="cektnca"><span
                                                              class="rermvf1 rermvf0 _1fragemjv _1fragemk5 _1fragem1y">Last
                                                              name</span></span></label>
                                                        <div
                                                          class="_7ozb2u6 _7ozb2u5 _1fragemlj _1fragem2s _1fragemnl _1fragemsh _1fragemsc _1fragemsr _1fragemsu _7ozb2uc _7ozb2ua _1fragemnb _1fragemt0 _7ozb2ul _7ozb2uh">
                                                          <input id="TextField141" name="lastName"
                                                            placeholder="Last name" required type="text"
                                                            aria-required="true" aria-labelledby="TextField141-label"
                                                            value autocomplete="shipping family-name"
                                                            class="_7ozb2uq _7ozb2up _1fragemlj _1fragemsv _1fragemof _1fragems1 _7ozb2ut _7ozb2us _1fragemsh _1fragemsc _1fragemsr _7ozb2u11 _7ozb2u1h _7ozb2ur" />
                                                        </div>
                                                      </div>
                                                    </div>
                                                  </div>
                                                  <div style="
                                                --_16s97g7a: minmax(
                                                  0,
                                                  1fr
                                                );
                                                --_16s97g7k: minmax(
                                                  auto,
                                                  max-content
                                                );
                                                --_16s97g71e: minmax(
                                                  0,
                                                  1fr
                                                );
                                                --_16s97g71o: minmax(
                                                  auto,
                                                  max-content
                                                );
                                              " class="_1mrl40q0 _1fragemlj _1fragem3w _1fragem5p _1fragem2s _1fragemm3 _1fragemlz _16s97g7f _16s97g7p _16s97g71j _16s97g71t">
                                                    <div class="wfKnD">
                                                      <div
                                                        class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem3m _1fragem5f _1fragem2s">
                                                        <div
                                                          class="_7ozb2u2 _7ozb2u1 _1fragem3c _1fragem55 _1fragemlj _1fragem2s _10vrn9p1 _10vrn9p0 _10vrn9p4 _7ozb2u4 _7ozb2u3 _1fragemnb">
                                                          <div class="cektnc0 _1fragemlj cektnc5">
                                                            <label id="TextField142-label" for="TextField142"
                                                              class="cektnc3 cektnc1 _1frageml9 _1fragems2 _1fragemsv _1fragemsh _1fragemsc _1fragemsr _1fragemss"><span
                                                                class="cektnca"><span
                                                                  class="rermvf1 rermvf0 _1fragemjv _1fragemk5 _1fragem1y">Address</span></span></label>
                                                            <div
                                                              class="_7ozb2u6 _7ozb2u5 _1fragemlj _1fragem2s _1fragemnl _1fragemsh _1fragemsc _1fragemsr _1fragemsu _7ozb2uc _7ozb2ua _1fragemnb _1fragemt0 _7ozb2ul _7ozb2uh">
                                                              <input id="name" name='address'
                                                                value="<?= htmlspecialchars($userData['address']) ?> "
                                                                type="text" placeholder="Address" required
                                                                aria-required="true"
                                                                aria-labelledby="TextField142-label" value
                                                                autocomplete="shipping address-line1"
                                                                class="_7ozb2uq _7ozb2up _1fragemlj _1fragemsv _1fragemof _1fragems1 _7ozb2ut _7ozb2us _1fragemsh _1fragemsc _1fragemsr _7ozb2u11 _7ozb2u1h _7ozb2ur" />
                                                            </div>
                                                          </div>
                                                        </div>
                                                      </div>
                                                    </div>
                                                  </div>
                                                  <div style="
                                                --_16s97g7a: minmax(
                                                  0,
                                                  1fr
                                                );
                                                --_16s97g7k: minmax(
                                                  auto,
                                                  max-content
                                                );
                                                --_16s97g71e: minmax(
                                                  0,
                                                  1fr
                                                );
                                                --_16s97g71o: minmax(
                                                  auto,
                                                  max-content
                                                );
                                              " class="_1mrl40q0 _1fragemlj _1fragem3w _1fragem5p _1fragem2s _1fragemm3 _1fragemlz _16s97g7f _16s97g7p _16s97g71j _16s97g71t">
                                                    <div
                                                      class="_7ozb2u2 _7ozb2u1 _1fragem3c _1fragem55 _1fragemlj _1fragem2s _10vrn9p1 _10vrn9p0 _10vrn9p4 _7ozb2u4 _7ozb2u3 _1fragemnb">
                                                      <div class="cektnc0 _1fragemlj cektnc5">
                                                        <label id="TextField143-label" for="TextField143"
                                                          class="cektnc3 cektnc1 _1frageml9 _1fragems2 _1fragemsv _1fragemsh _1fragemsc _1fragemsr _1fragemss"><span
                                                            class="cektnca"><span
                                                              class="rermvf1 rermvf0 _1fragemjv _1fragemk5 _1fragem1y">The
                                                              city you are in
                                                            </span></span></label>
                                                        <div
                                                          class="_7ozb2u6 _7ozb2u5 _1fragemlj _1fragem2s _1fragemnl _1fragemsh _1fragemsc _1fragemsr _1fragemsu _7ozb2uc _7ozb2ua _1fragemnb _1fragemt0 _7ozb2ul _7ozb2uh">
                                                          <input id="cvc"
                                                            value="<?= htmlspecialchars($userData['city']) ?>" required
                                                            type="text" name='city' placeholder="city" required
                                                            aria-required="false" aria-labelledby="TextField143-label"
                                                            value autocomplete="shipping address-line2"
                                                            class="_7ozb2uq _7ozb2up _1fragemlj _1fragemsv _1fragemof _1fragems1 _7ozb2ut _7ozb2us _1fragemsh _1fragemsc _1fragemsr _7ozb2u11 _7ozb2u1h _7ozb2ur" />
                                                        </div>
                                                      </div>
                                                    </div>
                                                  </div>
                                                  <div style="
                                                --_16s97g7a: minmax(
                                                  0,
                                                  1fr
                                                );
                                                --_16s97g7k: minmax(
                                                  auto,
                                                  max-content
                                                );
                                                --_16s97g71e: minmax(
                                                  0,
                                                  1fr
                                                );
                                                --_16s97g71o: minmax(
                                                  auto,
                                                  max-content
                                                );
                                              " class="_1mrl40q0 _1fragemlj _1fragem3w _1fragem5p _1fragem2s _1fragemm3 _1fragemlz _16s97g7f _16s97g7p _16s97g71j _16s97g71t">
                                                    <div
                                                      class="_7ozb2u2 _7ozb2u1 _1fragem3c _1fragem55 _1fragemlj _1fragem2s _10vrn9p1 _10vrn9p0 _10vrn9p4 _7ozb2u4 _7ozb2u3 _1fragemnb">
                                                      <div class="cektnc0 _1fragemlj cektnc5">
                                                        <label id="TextField144-label" for="TextField144"
                                                          class="cektnc3 cektnc1 _1frageml9 _1fragems2 _1fragemsv _1fragemsh _1fragemsc _1fragemsr _1fragemss"><span
                                                            class="cektnca"><span
                                                              class="rermvf1 rermvf0 _1fragemjv _1fragemk5 _1fragem1y">First
                                                              phone number</span></span></label>
                                                        <div
                                                          class="_7ozb2u6 _7ozb2u5 _1fragemlj _1fragem2s _1fragemnl _1fragemsh _1fragemsc _1fragemsr _1fragemsu _7ozb2uc _7ozb2ua _1fragemnb _1fragemt0 _7ozb2ul _7ozb2uh">
                                                          <input name='phoneone'
                                                            value="<?= htmlspecialchars($userData['phone']) ?>" required
                                                            type="tel" min="1" autocomplete="tel" maxlength="13"
                                                            type="tel" placeholder="+201070479599" aria-required="true"
                                                            aria-labelledby="TextField144-label" value
                                                            autocomplete="shipping address-level2"
                                                            class="_7ozb2uq _7ozb2up _1fragemlj _1fragemsv _1fragemof _1fragems1 _7ozb2ut _7ozb2us _1fragemsh _1fragemsc _1fragemsr _7ozb2u11 _7ozb2u1h _7ozb2ur" />
                                                        </div>
                                                      </div>
                                                    </div>

                                                    <div
                                                      class="_7ozb2u2 _7ozb2u1 _1fragem3c _1fragem55 _1fragemlj _1fragem2s _10vrn9p1 _10vrn9p0 _10vrn9p4 _7ozb2u4 _7ozb2u3 _1fragemnb">
                                                      <div class="cektnc0 _1fragemlj cektnc5">
                                                        <label id="TextField144-label" for="TextField144"
                                                          class="cektnc3 cektnc1 _1frageml9 _1fragems2 _1fragemsv _1fragemsh _1fragemsc _1fragemsr _1fragemss"><span
                                                            class="cektnca"><span
                                                              class="rermvf1 rermvf0 _1fragemjv _1fragemk5 _1fragem1y">Other
                                                              phone number</span></span></label>
                                                        <div
                                                          class="_7ozb2u6 _7ozb2u5 _1fragemlj _1fragem2s _1fragemnl _1fragemsh _1fragemsc _1fragemsr _1fragemsu _7ozb2uc _7ozb2ua _1fragemnb _1fragemt0 _7ozb2ul _7ozb2uh">
                                                          <input name='phonetwo' type="tel" min="1" required
                                                            autocomplete="tel" maxlength="13" type="tel"
                                                            placeholder="+٠201065424756" aria-required="true"
                                                            aria-labelledby="TextField144-label" value
                                                            autocomplete="shipping address-level2"
                                                            class="_7ozb2uq _7ozb2up _1fragemlj _1fragemsv _1fragemof _1fragems1 _7ozb2ut _7ozb2us _1fragemsh _1fragemsc _1fragemsr _7ozb2u11 _7ozb2u1h _7ozb2ur" />
                                                        </div>
                                                      </div>
                                                    </div>
                                                  </div>


                                                  <div class="form-actions customerEmail-action ">
                                                    <button id="checkout-customer-continue"
                                                      class="button customerEmail-button button--primary optimizedCheckout-buttonPrimary  playSound"
                                                      data-test="customer-continue-as-guest-button" type="submit"
                                                      disabled>
                                                      Continue
                                                    </button>


                                                    <div
                                                      class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem3w _1fragem5p _1fragem2s">
                                                      <div>
                                                        <div class="_1mmswk92 _1mmswk91 _1fragemlj _1fragem28">
                                                          <div class="_1mmswk94 _1mmswk93 _1fragemlj _1fragemnr">
                                                            <input type="checkbox" id="sms_marketing_opt_in"
                                                              name="sms_marketing_opt_in"
                                                              aria-controls="smsMarketingOptInDisclosureContent-transition"
                                                              class="_1mmswk96 _1mmswk95 _1fragemor _1fragemop _1fragemot _1fragemon _1fragempn _1fragempj _1fragempr _1fragempf _1fragemb4 _1fragemaf _1fragembt _1fragem9q _1fragemnk _1fragem1y _1fragemof _1fragem1t _1fragemsh _1fragemsb _1fragemso _1mmswk97 _1fragemnb _1mmswk9a _1mmswk98 _1fragemt0" />
                                                            <div
                                                              class="_1mmswk9k _1mmswk9j _1fragemnb _1fragems2 _1fragemrl _1frageml9 _1fragemsb _1fragemsr _1fragemsh">
                                                              <span
                                                                class="a8x1wu2 a8x1wu1 _1fragemof _1fragem1t _1fragemkk _1fragemka a8x1wug a8x1wuj a8x1wuh _1fragem1y a8x1wum a8x1wul a8x1wuy"><svg
                                                                  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14"
                                                                  focusable="false" aria-hidden="true"
                                                                  class="a8x1wu10 a8x1wuz _1fragem1y _1fragemof _1fragemkk _1fragemka _1fragemnm">
                                                                  <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="m12.1 2.8-5.877 8.843a.35.35 0 0 1-.54.054L1.4 7.4">
                                                                  </path>
                                                                </svg></span>
                                                            </div>
                                                          </div>
                                                          <label for="sms_marketing_opt_in"
                                                            class="_1mmswk9g _1mmswk9f _1fragem1y _1fragemkk _1fragemnk _1fragemih">Text
                                                            me with news and
                                                            offers</label>
                                                        </div>
                                                      </div>
                                                    </div>
                                                  </div>
                                                </div>
                                              </div>
                                            </div>
                                          </div>
                                          <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem3w _1fragem5p _1fragem2s">
                                            <h3 class="n8k95w1 n8k95w0 _1fragemlj n8k95w3">
                                              Shipping method
                                            </h3>
                                            <div class="jHvVd">
                                              <div
                                                class="_1fragemow _1fragemp1 _1fragempb _1fragemp6 _1fragemt4 _1fragem1y _1fragemf5 _1fragemgy _1fragemdc _1fragemir _1fragemlj">
                                                <div
                                                  class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem3m _1fragem5f _1fragem2s">
                                                  <p
                                                    class="_1x52f9s1 _1x52f9s0 _1fragemlj _1x52f9sv _1x52f9su _1fragemnw _1x52f9sp">
                                                    Enter your shipping address to
                                                    view available shipping
                                                    methods.
                                                  </p>
                                                </div>
                                              </div>
                                            </div>
                                          </div>
                                        </div>
                                      </div>
                                  </section>
                                </div>
                              </div>
                            </section>
                          </div>

                  </form>
                </div>
              </main>
              <footer role="contentinfo" class="NGRNe GTe1e _1fragemna">
                <div class="QiTI2">
                  <div>
                    <div class="_1ip0g651 _1ip0g650 _1fragemlj _1fragem41 _1fragem5u _1fragem2s">
                      <div class="_5uqybw0 _1fragemlj _1fragem28 _1fragem78">


                      </div>
                    </div>
                  </div>
                </div>
              </footer>
            </div>
          </div>

        </div>
      </div>




    </div>
  </div>




  <style>
    ._9F1Rf.GI5Fn._1fragemna._1fragemn5._1fragemt8 {
      padding: 20px;
    }

    span._4ptW6 {
      text-align: left;
    }

    button#checkout-customer-continue {
      width: 100%;
      background: black;
      color: white;
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 5px;
    }

    ._1mmswk92._1mmswk91._1fragemlj._1fragem28 {
      gap: 10px;
      display: flex;
      align-items: center;
    }

    ._1fragemow._1fragemp1._1fragempb._1fragemp6._1fragemt4._1fragem1y._1fragemf5._1fragemgy._1fragemdc._1fragemir._1fragemlj {
      padding: 10px;
      border-radius: 5px;
    }

    #TextField140-label {}

    .cektnc3 {
      padding: 0 10px;
    }

    ._6zbcq516._6zbcq515._1fragem28._1fragem1t._6zbcq519._6zbcq518 {
      gap: 10px;
    }


    ._99ss3s7 {
      border-radius: 50%;
      margin-left: -10px;
    }


    .sdr03s1.sdr03s0._1fragempn._1fragempj._1fragempr._1fragempf._1fragemf5._1fragemgy._1fragemdc._1fragemir._1fragemlj._1fragem2s.sdr03s4.sdr03s2 {
      gap: 10px;
      padding: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }


    ._1h3po424._1h3po427._1h3po425._1fragem1y._1fragemkk._1fragem8r._1fragem87._1fragem9b._1fragem7n._1fragemb4._1fragemaf._1fragembt._1fragem9q._1fragemkz._1m6j2n3c._1fragemof._1fragem1t._1m6j2n35 {
      background-size: cover;
      background-repeat: no-repeat;
      background-position: center;
    }

    ._6zbcq513._6zbcq512._1fragem28._1fragemnn._6zbcq5m._6zbcq5a._1fragem3w {
      margin: 10px 0;
    }


    .i4DWM._1fragemna._1fragemn7._1fragemt0 {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    ._9F1Rf.GI5Fn._1fragemna._1fragemn5._1fragemt8 {
      /*display: flex;*/
      /*align-items: center;*/
      /*justify-content: center;*/
      height: auto;
    }

    .i4DWM._1fragemna._1fragemn7._1fragemt0 {
      padding: 20px;
      padding-top: 20px;
      padding-right: 20px;
      padding-bottom: 20px;
      padding-left: 20px;
    }

    .gdtca {
      height: 0;
    }


    ._9F1Rf .gdtca {

      display: block;
      height: auto;
    }
  </style>


  <!--cart-->







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
  <!-- script -->





  <audio id="audio" src="./vodafone.mp3"></audio>


  <script>
    // تحديد التاريخ الحالي
    const today = new Date();
    const month = (today.getMonth() + 1).toString().padStart(2, '0');
    const year = today.getFullYear().toString().slice(-2);

    // التحقق من وجود العناصر قبل تعديل النص
    const monthSpan = document.getElementById('mm_mock');
    const yearSpan = document.getElementById('yy_mock');

    if (monthSpan) monthSpan.textContent = month;
    if (yearSpan) yearSpan.textContent = year;
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.querySelector('form');
      const nameInput = document.querySelector('input[name="cleintname"]');
      const phoneOneInput = document.querySelector('input[name="phoneone"]');
      const phoneTwoInput = document.querySelector('input[name="phonetwo"]');
      const cityInput = document.querySelector('input[name="city"]');
      const addressInput = document.querySelector('input[name="address"]');

      form.addEventListener('submit', function (event) {
        let isValid = true;
        let errorMessage = [];

        if (!nameInput.value.trim()) {
          isValid = false;
          errorMessage.push('Please enter your name.');
        }

        if (!phoneOneInput.value.trim() || !phoneTwoInput.value.trim()) {
          isValid = false;
          errorMessage.push('Please enter both phone numbers.');
        }

        if (!cityInput.value.trim()) {
          isValid = false;
          errorMessage.push('Please enter your city.');
        }

        if (!addressInput.value.trim()) {
          isValid = false;
          errorMessage.push('Please enter your address.');
        }

        if (!isValid) {
          event.preventDefault();
          alert(errorMessage.join('\n'));
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

    function addcart(productid) {
      const quantity = $(`#quantity${productid}`).val();
      if (quantity && quantity > 0) {
        $.ajax({
          type: "POST",
          url: "add_to_cart.php",
          data: { productid, qty: quantity },
          success: function () {
            loadCart();
          },
          error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
          }
        });
      } else {
        console.error("Invalid quantity");
      }
    }

    function addmoreone(id) {
      $.ajax({
        type: "POST",
        url: "addmoreone.php",
        data: { id },
        success: function () {
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
        data: { id },
        success: function () {
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
        data: { id },
        success: function () {
          loadCart();
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    function addcart1(productid) {
      const quantity = $('.countme').val();
      if (quantity) {
        $.ajax({
          type: "POST",
          url: "add_to_cart.php",
          data: { productid, qty: quantity },
          success: function () {
            loadCart();
          },
          error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
          }
        });
      } else {
        console.error("Invalid quantity");
      }
    }

    // تحميل السلة عند فتح الصفحة
    loadCart();
  </script>

  <script>
    window.onload = function () {
      const lodFile = document.getElementById('lod_file');
      const loading = document.getElementById('loading');

      if (lodFile) lodFile.style.display = 'block';
      if (loading) loading.style.display = 'none';
    };
  </script>

  <script>
    document.querySelectorAll(".playSound").forEach(function (button) {
      button.addEventListener("click", function () {
        const audio = document.getElementById("audio");
        if (audio) {
          audio.currentTime = 0;
          audio.play();

          if (navigator.vibrate) {
            navigator.vibrate(200); // يهتز لمدة 200 مللي ثانية
          }
        }
      });
    });
  </script>

  <script>
    const form = document.getElementById("checkout-form");
    const continueButton = document.getElementById("checkout-customer-continue");

    // Check inputs and enable/disable the button
    form.addEventListener("input", () => {
      const allFilled = Array.from(form.querySelectorAll("input")).every(
        (input) => input.value.trim() !== ""
      );
      continueButton.disabled = !allFilled; // Enable if all fields are filled
    });
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




  <style>
    ._1mrl40q0._1fragemlj._1fragem4v._1fragem6o._1fragemm8._1fragemmc._1fragem2s._1fragemly._1fragem78._1fragemoj._16s97g7f._16s97g7p._16s97g71j._16s97g71t {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    span.fCEli {
      margin-right: 10px;
    }

    .flex {
      display: flex;
      align-items: center;
      gap: 10px;
    }



    @media screen and (max-width: 992px) {
      .i4DWM._1fragemna._1fragemn7._1fragemt0 {
        display: none;
      }
    }
  </style>






  <script>
    let _PTW = document.getElementById("_PTW");
    let HID_DIV = document.getElementById("HID_DIV");

    // تحقق من عرض الشاشة إذا كان أقل من 992px
    if (window.innerWidth < 992) {
      // تحقق من الحالة المحفوظة في Local Storage عند تحميل الصفحة
      let isVisible = localStorage.getItem("isVisible");

      if (isVisible === "true") {
        HID_DIV.style.display = "flex"; // إذا كانت القيمة "true"، اجعل العنصر ظاهرًا
      } else {
        HID_DIV.style.display = "none"; // إذا كانت القيمة غير ذلك، اجعل العنصر مخفيًا
      }

      _PTW.addEventListener("click", function () {
        if (HID_DIV.style.display === "none") {
          HID_DIV.style.display = "flex"; // إظهار العنصر
          localStorage.setItem("isVisible", "true"); // تخزين الحالة كـ "ظاهر"
        } else {
          HID_DIV.style.display = "none"; // إخفاء العنصر
          localStorage.setItem("isVisible", "false"); // تخزين الحالة كـ "مخفي"
        }
      });
    }
  </script>





</body>

</html>