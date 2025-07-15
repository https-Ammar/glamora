<?php
require('./db.php');

// التحقق من وجود كوكي المستخدم
if (!isset($_COOKIE['userid'])) {
  // الحصول على آخر ID في جدول المستخدمين
  $stmt = $conn->prepare("SELECT id FROM users ORDER BY id DESC LIMIT 1");
  $stmt->execute();
  $result = $stmt->get_result();

  $newid = 1;
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $newid = (int) $row['id'] + 1;
  }

  // إدراج مستخدم جديد زائر
  $insert = $conn->prepare("INSERT INTO users (id) VALUES (?)");
  $insert->bind_param("i", $newid);
  $insert->execute();
  $insert->close();

  // تعيين الكوكيز لمدة 10 سنوات
  setcookie('userid', $newid, time() + (10 * 365 * 24 * 60 * 60), "/", "", false, true);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GLAMORA</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
    rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" type="text/css"
    href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
  <link rel="stylesheet" type="text/css"
    href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" type="text/css" href="./assets/style/main.css">
</head>

<body>
  <canvas id="message"></canvas>

  <?php require('./assets/page/loding.php'); ?>

  <div class="_Tiket">
    <p>GLAMORA</p>
  </div>

  <section id="lod_file">
    <?php require('./assets/page/header.php'); ?>

    <div class="slider">
      <?php
      if (!$conn) {
        die("Connection failed");
      }

      $catidneed = isset($_GET['catid']) ? intval($_GET['catid']) : 1;

      $stmtAds = $conn->prepare("SELECT * FROM `ads` WHERE categoryid = ? LIMIT 1");
      if ($stmtAds === false) {
        die("Error preparing statement");
      }

      $stmtAds->bind_param("i", $catidneed);
      $stmtAds->execute();
      $selectad = $stmtAds->get_result();

      if ($selectad->num_rows > 0) {
        $fetchad = $selectad->fetch_assoc();
        echo '<a class="slider" href="' . htmlspecialchars($fetchad['linkaddress'], ENT_QUOTES, 'UTF-8') . '">
                    <div class="banner-content p-5 add_link first" style="background-image: url(./admin/uploads/' . htmlspecialchars($fetchad['photo'], ENT_QUOTES, 'UTF-8') . ');">
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
        $sqlcat = $conn->prepare("SELECT * FROM catageories LIMIT 8");
        $sqlcat->execute();
        $result = $sqlcat->get_result();

        while ($fetchcat = $result->fetch_assoc()) {
          $image = htmlspecialchars($fetchcat['image'] ?? '', ENT_QUOTES, 'UTF-8');
          $name = htmlspecialchars($fetchcat['name'] ?? '', ENT_QUOTES, 'UTF-8');
          $id = (int) $fetchcat['id'];
          echo '<div class="main_cat item">
                        <a href="Categories.php?id=' . $id . '">
                            <div class="_Categories_img" style="background-image: url(\'./admin/' . $image . '\');"></div>
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

        $stmt = $conn->prepare("SELECT * FROM catageories WHERE id = ?");
        $stmt->bind_param("i", $catidneed);
        $stmt->execute();
        $selectcat = $stmt->get_result();
        $fetchcat = $selectcat->fetch_assoc();

        if ($fetchcat) {
          $namecat = htmlspecialchars($fetchcat['name'], ENT_QUOTES, 'UTF-8');
          ?>
          <div class="slider">
            <?php
            $stmtAds = $conn->prepare("SELECT * FROM `ads` WHERE categoryid = ?");
            $stmtAds->bind_param("i", $catidneed);
            $stmtAds->execute();
            $selectad = $stmtAds->get_result();

            $counter = 0;
            if ($selectad->num_rows > 0) {
              while ($fetchad = $selectad->fetch_assoc()) {
                $counter++;
                $firstAdClass = $counter == 1 ? 'first-ad' : '';
                echo '<a class="slider ' . $firstAdClass . '" href="' . htmlspecialchars($fetchad['linkaddress'], ENT_QUOTES, 'UTF-8') . '">
                                    <div class="banner-content p-5 add_link" style="background-image: url(./admin/' . htmlspecialchars($fetchad['photo'], ENT_QUOTES, 'UTF-8') . ');"></div>
                                </a>';
              }
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
          $stmtProducts = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
          $stmtProducts->bind_param("i", $catidneed);
          $stmtProducts->execute();
          $resultCount = $stmtProducts->get_result();
          $countData = $resultCount->fetch_assoc();

          if ($countData['count'] > 0) {
            ?>
            <section class="container__">
              <div class="row">
                <a href="Categories.php?id=<?php echo $i + 1 ?>" class="btn-link text-decoration-none">
                  <h3 class="title"><?php echo $namecat; ?></h3>
                </a>

                <div class="slider-container">
                  <div class="owl-carousel js-home-products">
                    <?php
                    $stmtProducts = $conn->prepare("SELECT * FROM products WHERE category_id = ?");
                    $stmtProducts->bind_param("i", $catidneed);
                    $stmtProducts->execute();
                    $selectproduct = $stmtProducts->get_result();

                    while ($fetchproducts = $selectproduct->fetch_assoc()) {
                      $productName = htmlspecialchars($fetchproducts['name'], ENT_QUOTES, 'UTF-8');
                      $productImage = './admin' . htmlspecialchars($fetchproducts['img'], ENT_QUOTES, 'UTF-8');
                      $productfinalprice = htmlspecialchars($fetchproducts['total_final_price'], ENT_QUOTES, 'UTF-8');
                      $productDiscount = htmlspecialchars($fetchproducts['discount'], ENT_QUOTES, 'UTF-8');
                      ?>
                      <div class="item">
                        <a href="./assets/page/view.php?id=<?php echo (int) $fetchproducts['id'] ?>"
                          title="<?php echo $productName; ?>">
                          <figure class="bg_img" style="background-image: url('<?php echo $productImage; ?>');">
                            <?php if ($fetchproducts['discount'] != 0) { ?>
                              <span class="badge bg-success text"><?php echo $productDiscount; ?> %</span>
                            <?php } ?>
                          </figure>
                        </a>

                        <span class="snize-attribute"><span class="snize-attribute-title"></span> Source Beauty</span>
                        <span class="snize-title"
                          style="max-height: 2.8em;-webkit-line-clamp: 2;"><?php echo $productName; ?></span>

                        <div class="flex_pric playSound" onclick='addcart(<?php echo (int) $fetchproducts["id"] ?>)'>
                          <button class="d-flex align-items-center nav-link click">Add to Cart</button>
                          <div class="block_P">
                            <span class="price text"><?php echo $productfinalprice; ?></span>
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
                              class="form-control input-number quantity<?php echo (int) $fetchproducts["id"] ?>" value="1">
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
            </section>
            <?php
          }
        }
      }
      ?>

      <script>
        window.onload = function () {
          let elements = document.querySelectorAll('.text');
          elements.forEach(element => {
            let text = element.textContent;
            let updatedText = text.split('.')[0];
            element.textContent = updatedText;
          });
        };
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

        loadCart();

        function addcart(productid) {
          var quantity = $('.quantity' + productid).val();

          $.ajax({
            type: "POST",
            url: "addcartproduct.php",
            data: {
              productid: productid,
              qty: quantity
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

      <script src="js/jquery-1.11.0.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
      <script src="./assets/app/plugins.js"></script>
      <script src="./assets/app/script.js"></script>
      <audio id="audio" src="./like.mp3"></audio>
    </main>

    <?php require('./assets/page/footer.php') ?>
  </section>
</body>

</html>




















<!--  -->




<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Owl Carousel JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

<!-- Custom JavaScript -->
<script>
  $(".js-home-products").owlCarousel({
    loop: false,
    margin: 10,
    nav: true,
    autoplay: true,
    autoplayTimeout: 3000,
    responsive: {
      0: {
        items: 2
      },
      600: {
        items: 2
      },
      1000: {
        items: 5
      },
    },
  });
</script>


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
  // تحقق إذا كان المستخدم زار الموقع من قبل
  if (!localStorage.getItem("messageDisplayed")) {
    // أضف المفتاح إلى localStorage لتحديد أنه تم عرض الرسالة
    localStorage.setItem("messageDisplayed", "true");

    // اجعل العنصر يختفي بعد 3 ثوانٍ
    setTimeout(() => {
      const canvas = document.getElementById("message");
      canvas.style.opacity = "0"; // إضافة تأثير التلاشي
      setTimeout(() => {
        canvas.style.display = "none"; // الإخفاء الكامل بعد انتهاء التلاشي
      }, 500); // وقت التلاشي (مطابق للمدة في CSS)
    }, 10000);
  } else {
    // إذا تم العرض مسبقًا، إخفاء العنصر فورًا
    document.getElementById("message").style.display = "none";
  }
</script>

<script>
  const canvas = document.querySelector("canvas");
  const gl = canvas.getContext("webgl");

  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
  gl.viewport(0, 0, canvas.width, canvas.height);

  // Configurable parameters
  const config = {
    particleCount: 5000,
    textArray: ["Welcome.", "TO.", "GLAMORA."],
    mouseRadius: 0.1,
    particleSize: 2,
    forceMultiplier: 0.001,
    returnSpeed: 0.005,
    velocityDamping: 0.95,
    colorMultiplier: 40000,
    saturationMultiplier: 1000,
    textChangeInterval: 3000,
    rotationForceMultiplier: 0.5,
  };

  let currentTextIndex = 0;
  let nextTextTimeout;
  let textCoordinates = [];

  const mouse = {
    x: -500,
    y: -500,
    radius: config.mouseRadius,
  };

  const particles = [];
  for (let i = 0; i < config.particleCount; i++) {
    particles.push({ x: 0, y: 0, baseX: 0, baseY: 0, vx: 0, vy: 0 });
  }

  const vertexShaderSource = `
          attribute vec2 a_position;
          attribute float a_hue;
          attribute float a_saturation;
          varying float v_hue;
          varying float v_saturation;
          void main() {
              gl_PointSize = ${config.particleSize.toFixed(1)};
              gl_Position = vec4(a_position, 0.0, 1.0);
              v_hue = a_hue;
              v_saturation = a_saturation;
          }
      `;

  const fragmentShaderSource = `
          precision mediump float;
          varying float v_hue;
          varying float v_saturation;
          void main() {
              float c = v_hue * 6.0;
              float x = 1.0 - abs(mod(c, 2.0) - 1.0);
              vec3 color;
              if (c < 1.0) color = vec3(1.0, x, 0.0);
              else if (c < 2.0) color = vec3(x, 1.0, 0.0);
              else if (c < 3.0) color = vec3(0.0, 1.0, x);
              else if (c < 4.0) color = vec3(0.0, x, 1.0);
              else if (c < 5.0) color = vec3(x, 0.0, 1.0);
              else color = vec3(1.0, 0.0, x);
              vec3 finalColor = mix(vec3(1.0), color, v_saturation);
              gl_FragColor = vec4(finalColor, 1.0);
          }
      `;

  function createShader(gl, type, source) {
    const shader = gl.createShader(type);
    gl.shaderSource(shader, source);
    gl.compileShader(shader);
    if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
      console.error(gl.getShaderInfoLog(shader));
      gl.deleteShader(shader);
      return null;
    }
    return shader;
  }

  function createProgram(gl, vertexShader, fragmentShader) {
    const program = gl.createProgram();
    gl.attachShader(program, vertexShader);
    gl.attachShader(program, fragmentShader);
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
      console.error(gl.getProgramInfoLog(program));
      gl.deleteProgram(program);
      return null;
    }
    return program;
  }

  const vertexShader = createShader(
    gl,
    gl.VERTEX_SHADER,
    vertexShaderSource
  );
  const fragmentShader = createShader(
    gl,
    gl.FRAGMENT_SHADER,
    fragmentShaderSource
  );
  const program = createProgram(gl, vertexShader, fragmentShader);

  const positionAttributeLocation = gl.getAttribLocation(
    program,
    "a_position"
  );
  const hueAttributeLocation = gl.getAttribLocation(program, "a_hue");
  const saturationAttributeLocation = gl.getAttribLocation(
    program,
    "a_saturation"
  );

  const positionBuffer = gl.createBuffer();
  const hueBuffer = gl.createBuffer();
  const saturationBuffer = gl.createBuffer();

  const positions = new Float32Array(config.particleCount * 2);
  const hues = new Float32Array(config.particleCount);
  const saturations = new Float32Array(config.particleCount);

  function getTextCoordinates(text) {
    const ctx = document.createElement("canvas").getContext("2d");
    ctx.canvas.width = canvas.width;
    ctx.canvas.height = canvas.height;
    const fontSize = Math.min(canvas.width / 6, canvas.height / 6);
    ctx.font = `900 ${fontSize}px Arial`;
    ctx.fillStyle = "white";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(text, canvas.width / 2, canvas.height / 2);
    const imageData = ctx.getImageData(
      0,
      0,
      canvas.width,
      canvas.height
    ).data;
    const coordinates = [];
    for (let y = 0; y < canvas.height; y += 4) {
      for (let x = 0; x < canvas.width; x += 4) {
        const index = (y * canvas.width + x) * 4;
        if (imageData[index + 3] > 128) {
          coordinates.push({
            x: (x / canvas.width) * 2 - 1,
            y: (y / canvas.height) * -2 + 1,
          });
        }
      }
    }
    return coordinates;
  }

  function createParticles() {
    textCoordinates = getTextCoordinates(
      config.textArray[currentTextIndex]
    );
    for (let i = 0; i < config.particleCount; i++) {
      const randomIndex = Math.floor(
        Math.random() * textCoordinates.length
      );
      const { x, y } = textCoordinates[randomIndex];
      particles[i].x = particles[i].baseX = x;
      particles[i].y = particles[i].baseY = y;
    }
  }
  function updateParticles() {
    for (let i = 0; i < config.particleCount; i++) {
      const particle = particles[i];
      const dx = mouse.x - particle.x;
      const dy = mouse.y - particle.y;
      const distance = Math.sqrt(dx * dx + dy * dy);
      const forceDirectionX = dx / distance;
      const forceDirectionY = dy / distance;
      const maxDistance = mouse.radius;
      const force = (maxDistance - distance) / maxDistance;
      const directionX = forceDirectionX * force * config.forceMultiplier;
      const directionY = forceDirectionY * force * config.forceMultiplier;

      const angle = Math.atan2(dy, dx);

      const rotationForceX = Math.sin(
        -Math.cos(angle * -1) *
        Math.sin(config.rotationForceMultiplier * Math.cos(force)) *
        Math.sin(distance * distance) *
        Math.sin(angle * distance)
      );

      const rotationForceY = Math.sin(
        Math.cos(angle * 1) *
        Math.sin(config.rotationForceMultiplier * Math.sin(force)) *
        Math.sin(distance * distance) *
        Math.cos(angle * distance)
      );

      if (distance < mouse.radius) {
        particle.vx -= directionX + rotationForceX;
        particle.vy -= directionY + rotationForceY;
      } else {
        particle.vx += (particle.baseX - particle.x) * config.returnSpeed;
        particle.vy += (particle.baseY - particle.y) * config.returnSpeed;
      }

      particle.x += particle.vx;
      particle.y += particle.vy;
      particle.vx *= config.velocityDamping;
      particle.vy *= config.velocityDamping;

      const speed = Math.sqrt(
        particle.vx * particle.vx + particle.vy * particle.vy
      );
      const hue = (speed * config.colorMultiplier) % 360;

      hues[i] = hue / 360;
      saturations[i] = Math.min(speed * config.saturationMultiplier, 1);
      positions[i * 2] = particle.x;
      positions[i * 2 + 1] = particle.y;
    }
    gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
    gl.bufferData(gl.ARRAY_BUFFER, positions, gl.DYNAMIC_DRAW);
    gl.bindBuffer(gl.ARRAY_BUFFER, hueBuffer);
    gl.bufferData(gl.ARRAY_BUFFER, hues, gl.DYNAMIC_DRAW);
    gl.bindBuffer(gl.ARRAY_BUFFER, saturationBuffer);
    gl.bufferData(gl.ARRAY_BUFFER, saturations, gl.DYNAMIC_DRAW);
  }

  function animate() {
    updateParticles();

    gl.clear(gl.COLOR_BUFFER_BIT);
    gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
    gl.vertexAttribPointer(
      positionAttributeLocation,
      2,
      gl.FLOAT,
      false,
      0,
      0
    );
    gl.enableVertexAttribArray(positionAttributeLocation);
    gl.bindBuffer(gl.ARRAY_BUFFER, hueBuffer);
    gl.vertexAttribPointer(hueAttributeLocation, 1, gl.FLOAT, false, 0, 0);
    gl.enableVertexAttribArray(hueAttributeLocation);
    gl.bindBuffer(gl.ARRAY_BUFFER, saturationBuffer);
    gl.vertexAttribPointer(
      saturationAttributeLocation,
      1,
      gl.FLOAT,
      false,
      0,
      0
    );
    gl.enableVertexAttribArray(saturationAttributeLocation);
    gl.useProgram(program);
    gl.drawArrays(gl.POINTS, 0, config.particleCount);
    requestAnimationFrame(animate);
  }

  canvas.addEventListener("mousemove", (event) => {
    mouse.x = (event.clientX / canvas.width) * 2 - 1;
    mouse.y = (event.clientY / canvas.height) * -2 + 1;
  });

  canvas.addEventListener("mouseleave", () => {
    mouse.x = -500;
    mouse.y = -500;
  });

  window.addEventListener("resize", () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    gl.viewport(0, 0, canvas.width, canvas.height);
    createParticles();
  });

  function changeText() {
    currentTextIndex = (currentTextIndex + 1) % config.textArray.length;
    const newCoordinates = getTextCoordinates(
      config.textArray[currentTextIndex]
    );
    for (let i = 0; i < config.particleCount; i++) {
      const randomIndex = Math.floor(Math.random() * newCoordinates.length);
      const { x, y } = newCoordinates[randomIndex];
      particles[i].baseX = x;
      particles[i].baseY = y;
    }
    nextTextTimeout = setTimeout(changeText, config.textChangeInterval);
  }

  gl.clearColor(0, 0, 0, 1);
  createParticles();
  animate();
  nextTextTimeout = setTimeout(changeText, config.textChangeInterval);
</script>

<style>
  canvas {
    z-index: 999999999999;
  }
</style>

<style>
  .Categories_ads {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-top: 10vh;
  }

  .main_cat {
    display: flex;
    align-items: center;
    text-align: center;
    flex-direction: column;

  }

  ._Categories_img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background-position: center bottom;
    background-size: cover;
    background-repeat: no-repeat;
    border: 5px solid #de4558;
  }

  .main_cat h2 {
    font-size: 16px;
  }

  @media screen and (max-width: 992px) {
    ._Categories_img {
      width: 75px;
      height: 75px;
    }

    .main_cat h2 {
      font-size: smaller;
    }

    .Categories_ads {

      gap: 10px;
      margin-top: 5vh;
    }
  }
</style>