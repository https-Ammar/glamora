<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Document</title>
  <!-- Unicons CSS -->
  <style>
    /* Google Fonts - Poppins */
    @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");

    ul.nav-links li a {
      color: white !important;
      font-size: small;
    }

    form#search-form {
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .nav .nav-links {
      column-gap: 20px;
      list-style: none;
    }

    .nav .nav-links a {
      transition: all 0.2s linear;
    }

    .nav.openSearch .nav-links a {
      opacity: 0;
      pointer-events: none;
    }

    .nav .search-icon {
      color: white;
      font-size: 20px;
      cursor: pointer;
    }

    .nav .search-box {
      height: 45px;
      max-width: 555px;
      width: 100%;
      opacity: 0;
      pointer-events: none;
      transition: all 0.2s linear;
    }

    .nav.openSearch .search-box {
      opacity: 1;
      pointer-events: auto;
    }

    .search-box .search-icon {
      color: white;
    }

    ul.nav-links {
      display: none !important;
    }

    .search-box input {
      height: 100%;
      width: 100%;
      border: none;
      outline: none;
      color: white;
      border-radius: 6px;
      background-color: black;
      border: none;
    }

    .nav .navOpenBtn,
    .nav .navCloseBtn {
      display: none;
    }

    a.a_link {
      display: flex;
      align-items: center;
    }

    .logo_ {
      color: black;
      font-size: x-large;
      letter-spacing: 5px;
    }

    ul._block {
      display: block;
    }

    ul._block li {
      display: block;
      padding: 12px;
      border-bottom: 1px solid #eee;
      font-size: small;
    }

    label#companyInput-label {
      color: black;
      margin-top: 5vh !important;
    }

    .flex {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    nav.nav_bar.nav.openSearch {
      overflow: hidden !important;
      position: relative;
    }

    .container_flex {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2vh;
      padding-bottom: 2vh;
    }

    .container_ p.Footer_footer__heading__bwFl4 {
      margin: 0;
    }

    @media screen and (max-width: 768px) {

      .nav .navOpenBtn,
      .nav .navCloseBtn {
        display: block;
      }

      .nav {
        padding: 5px 20px;
      }

      .nav .nav-links {
        position: fixed;
        top: 0;
        left: -100%;
        height: 100%;
        width: 100%;
        row-gap: 30px;
        flex-direction: column;
        background-color: black;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.4s ease;
        z-index: 100;
      }

      .nav.openNav .nav-links {
        left: 0;
      }

      .nav .navOpenBtn {
        color: white;
        font-size: 20px;
        cursor: pointer;
      }

      .nav .navCloseBtn {
        color: white;
        font-size: xx-large;
        cursor: pointer;
      }

      ul.nav-links {
        display: block !important;
      }

      ul.nav-links li a {
        line-height: 3;
        font-size: unset;
      }

      ul.nav-links {
        padding: 20px;
      }

      ul.nav-links li {
        border-bottom: 1px solid #ffffff3b;
      }

      h3.title {
        color: black;
        font-weight: normal;
        margin-bottom: 20px;
        font-size: large;
      }
    }

    @media screen and (max-width: 992px) {
      form#search-form {
        position: absolute;
        left: 0;
        background: black;
        padding-left: 10px;
      }
    }
  </style>
</head>

<body>
  <header class="_nav_header">
    <nav class="nav_bar nav">
      <ul class="nav-links">
        <div class="container_flex">
          <li>
            <div class="container_">
              <p class="Footer_footer__heading__bwFl4">GLAMORA</p>
            </div>
          </li>
          <i class="uil uil-times navCloseBtn"></i>
        </div>

        <li>
          <?php
          $sqlcat = mysqli_query($conn, "SELECT * FROM categories");
          while ($fetchcat = mysqli_fetch_assoc($sqlcat)) {
            echo '<li>
              <a href="Categories.php?id=' . $fetchcat['id'] . '" class="nav-link">' . $fetchcat['name'] . '</a>
            </li>';
          }
          ?>
        </li>
      </ul>

      <div class="flex">
        <section class="icons_">
          <ul>
            <li>
              <i class="uil uil-bars navOpenBtn"></i>
            </li>
            <li>
              <button class="border-0 bg-transparent d-flex flex-column gap-2 lh-1" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart" aria-controls="offcanvasCart">
                <a class="action showcart" href="Cart.php" data-bind="scope: 'minicart_content'">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="#fff" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M16.6666 5.00008H12.4999V3.33341C12.4999 2.41675 11.7499 1.66675 10.8333 1.66675H9.16658C8.24992 1.66675 7.49992 2.41675 7.49992 3.33341V5.00008H3.33325V18.3334H16.6666V5.00008ZM15.8333 17.5001H4.16659V5.83342H15.8333V17.5001ZM8.33325 3.33341C8.33325 2.87383 8.707 2.50008 9.16658 2.50008H10.8333C11.2928 2.50008 11.6666 2.87383 11.6666 3.33341V5.00008H8.33325V3.33341Z"
                      fill="#fff" stroke="#fff" stroke-width="0.3"></path>
                    <rect x="7.5" y="3.33594" width="0.833333" height="3.33333" fill="#fff"></rect>
                    <rect x="11.668" y="3.33594" width="0.833333" height="3.33333" fill="#fff"></rect>
                  </svg>
                  <span class="counter-number count_cart" id="count_cart"></span>
                </a>
              </button>
            </li>
            <li>|</li>
            <li>
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M10.3334 2C5.73086 2 2 5.73086 2 10.3334C2 14.9359 5.73086 18.6668 10.3334 18.6668C14.9359 18.6668 18.6668 14.9359 18.6668 10.3334C18.6668 5.73086 14.9359 2 10.3334 2ZM2.85459 10.7501H7.00878C7.06795 13.7363 7.7517 16.3118 8.74421 17.6601C5.50211 16.9576 3.04292 14.153 2.85459 10.7501ZM17.8122 9.91672H13.658C13.5988 6.93045 12.9151 4.35501 11.9226 3.00667C15.1647 3.70918 17.6238 6.51378 17.8122 9.91672ZM10.3334 17.8334C9.34588 17.8334 7.9292 15.0855 7.8417 10.7501H12.8251C12.7376 15.0855 11.3209 17.8334 10.3334 17.8334ZM10.3334 2.83334C11.3209 2.83334 12.7376 5.58127 12.8251 9.91672H7.8417C7.9292 5.58127 9.34588 2.83334 10.3334 2.83334ZM8.74421 3.00667C7.75129 4.35501 7.06753 6.93045 7.00836 9.91672H2.85417C3.04292 6.51378 5.50211 3.70918 8.74421 3.00667ZM11.9226 17.6601C12.9151 16.3113 13.5988 13.7363 13.658 10.7501H17.8122C17.6238 14.153 15.1647 16.9576 11.9226 17.6601Z"
                  fill="white" stroke="white" stroke-width="0.3"></path>
              </svg>
            </li>
            <li>
              <a href="profile.php">
                <svg focusable="false" width="18" height="17" class="icon icon--header-customer" viewBox="0 0 18 17">
                  <circle cx="9" cy="5" r="4" fill="none" stroke="currentColor" stroke-width="1.6"
                    stroke-linejoin="round"></circle>
                  <path d="M1 17v0a4 4 0 014-4h8a4 4 0 014 4v0" fill="none" stroke="currentColor" stroke-width="1.6">
                  </path>
                </svg>
              </a>
            </li>
            <i class="uil uil-search search-icon" id="searchIcon2"></i>
          </ul>
        </section>

        <form id="search-form" action="searchresulte.php" method="POST" class="search-box">
          <i class="uil uil-search search-icon" id="searchIcon1"></i>
          <input id="search-input" name="search" type="text" placeholder="Search here..." list="suggestions">
          <datalist id="suggestions"></datalist>
        </form>

        <section class="logo_">
          <ul>
            <li>
              <a href="index.php" class="a_link">
                <svg width="103" height="40" viewBox="0 0 81 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M34.5976 12.2996V3.81839C34.5976 3.75781 34.5365 3.75781 34.4753 3.75781L31.0524 4.96942C30.9912 4.96942 30.9912 5.03 31.0524 5.09058L31.7247 5.6358V5.69638V12.2996C31.7247 12.3602 31.7859 12.3602 31.7859 12.3602H34.5365C34.5365 12.3602 34.5976 12.3602 34.5976 12.2996Z"
                    fill="white"></path>
                  <path
                    d="M44.0719 9.69243C44.0719 8.60199 43.3996 7.87502 42.1771 7.20864L40.8323 6.48168C39.8544 5.93646 39.3042 5.33066 39.3042 4.60369C39.3042 4.17963 39.5487 3.75557 39.7321 3.51325C39.7932 3.45267 39.7321 3.39209 39.671 3.39209C38.0817 3.69499 37.1038 4.66427 37.1038 6.1182C37.1038 7.39038 37.7761 8.05676 39.3042 8.90489L40.4656 9.57127C41.1991 9.93475 41.7492 10.4194 41.7492 11.1464C41.7492 11.5098 41.5047 11.9339 41.2602 12.2368C41.1991 12.2974 41.2602 12.358 41.3213 12.358C43.0328 12.0551 44.0719 11.0252 44.0719 9.69243Z"
                    fill="white"></path>
                  <path
                    d="M41.3202 3.45312C41.259 3.45312 41.1368 3.45312 41.0145 3.45312C40.9534 3.45312 40.8923 3.57429 40.9534 3.57429C41.4424 3.87719 42.6038 4.60415 43.3984 5.63401C43.4595 5.69459 43.5206 5.63401 43.5206 5.57343V3.93777C43.5206 3.93777 43.5206 3.87719 43.4595 3.87719C42.7872 3.63487 42.0537 3.45312 41.3202 3.45312Z"
                    fill="white"></path>
                  <path
                    d="M39.9763 12.2975C39.4873 11.9946 38.3871 11.3282 37.5314 10.1772C37.4702 10.1166 37.4091 10.1772 37.4091 10.2378V11.9946C37.4091 12.0552 37.4091 12.0552 37.4702 12.0552C38.3871 12.2975 39.304 12.4187 39.9152 12.4187C40.0375 12.4187 40.0986 12.3581 39.9763 12.2975Z"
                    fill="white"></path>
                  <path
                    d="M52.7516 9.63164C52.7516 8.54119 52.0792 7.75365 50.8567 7.08727L49.512 6.36031C48.4729 5.81509 47.9839 5.20928 47.9839 4.48232C47.9839 4.05826 48.2284 3.69478 48.4117 3.45246C48.4729 3.39188 48.4117 3.3313 48.3506 3.3313C46.7614 3.6342 45.7834 4.66406 45.7834 6.11799C45.7834 7.39017 46.4558 8.11713 47.9839 8.96525L49.1452 9.63164C49.8787 9.99512 50.4288 10.4798 50.4288 11.2067C50.4288 11.5702 50.1843 11.9943 49.9398 12.2366C49.8787 12.2972 49.9398 12.3577 50.001 12.3577C51.7125 12.0548 52.7516 11.025 52.7516 9.63164Z"
                    fill="white"></path>
                  <path
                    d="M49.9404 3.39209C49.8793 3.39209 49.757 3.39209 49.6348 3.39209C49.5737 3.39209 49.5125 3.51325 49.5737 3.51325C50.0627 3.81615 51.224 4.54311 52.0186 5.57298C52.0798 5.63356 52.1409 5.57298 52.1409 5.5124V3.87673C52.1409 3.87673 52.1409 3.81615 52.0798 3.81615C51.4074 3.57383 50.6739 3.45267 49.9404 3.39209Z"
                    fill="white"></path>
                  <path
                    d="M48.656 12.3585C48.167 12.0556 47.0667 11.3893 46.211 10.2382C46.1499 10.1776 46.0887 10.2382 46.0887 10.2988V12.0556C46.0887 12.1162 46.0887 12.1162 46.1499 12.1162C47.0667 12.3585 47.9836 12.4797 48.5948 12.4797C48.656 12.4797 48.7171 12.3585 48.656 12.3585Z"
                    fill="white"></path>
                  <path
                    d="M16.5042 0.0610352L13.0813 1.27264C13.0201 1.27264 13.0201 1.33322 13.0201 1.3938L13.6925 1.93902V1.9996V12.2982C13.6925 12.3588 13.7536 12.3588 13.7536 12.3588H16.5042C16.5654 12.3588 16.5654 12.2982 16.5654 12.2982V0.121615C16.5654 0.0610352 16.5654 0.0610352 16.5042 0.0610352Z"
                    fill="white"></path>
                  <path
                    d="M25.6113 0.0610352C24.8167 0.0610352 24.0832 0.363936 23.5942 0.848577C23.5331 0.909158 23.5942 0.969738 23.6554 0.969738C23.8387 0.909158 24.0221 0.848577 24.2055 0.848577C25.0612 0.848577 25.7947 1.51496 25.7947 2.42366V12.2982C25.7947 12.3588 25.8558 12.3588 25.8558 12.3588H28.6064C28.6675 12.3588 28.6675 12.2982 28.6675 12.2982V3.02946C28.6064 1.3938 27.2617 0.0610352 25.6113 0.0610352Z"
                    fill="white"></path>
                  <path
                    d="M19.5612 0.0610352C18.8277 0.0610352 18.0942 0.363936 17.5441 0.848577C17.483 0.909158 17.5441 0.969738 17.6052 0.969738C17.7886 0.909158 17.972 0.848577 18.1554 0.848577C19.0111 0.848577 19.7446 1.51496 19.7446 2.42366V12.2982C19.7446 12.3588 19.8057 12.3588 19.8057 12.3588H22.5563C22.6174 12.3588 22.6174 12.2982 22.6174 12.2982V3.02946C22.5563 1.3938 21.2727 0.0610352 19.5612 0.0610352Z"
                    fill="white"></path>
                  <path
                    d="M57.8251 1.9996V12.2982C57.8251 12.3588 57.8862 12.3588 57.8862 12.3588H60.6368C60.6979 12.3588 60.6979 12.2982 60.6979 12.2982V0.121615C60.6368 0.0610352 60.6368 0.0610352 60.5757 0.0610352L57.1527 1.27264C57.0916 1.27264 57.0916 1.33322 57.1527 1.3938L57.8251 1.9996C57.8251 1.93902 57.764 1.93902 57.8251 1.9996Z"
                    fill="white"></path>
                  <path
                    d="M65.7694 8.78388C64.9137 10.4801 63.6301 11.7523 62.4076 12.237C62.3465 12.237 62.3465 12.3581 62.4076 12.3581H65.8306C65.8917 12.3581 65.8917 12.2975 65.8917 12.2975V8.84446C65.8917 8.78388 65.7694 8.7233 65.7694 8.78388Z"
                    fill="white"></path>
                  <path
                    d="M33.131 3.02901C33.9868 3.02901 34.6591 2.36263 34.6591 1.5145C34.6591 0.666382 33.9868 0 33.131 0C32.2753 0 31.6029 0.666382 31.6029 1.5145C31.664 2.36263 32.3364 3.02901 33.131 3.02901Z"
                    fill="white"></path>
                  <path
                    d="M67.1152 1.39337C66.9929 0.605831 66.2594 2.8871e-05 65.4648 0.121189C64.7925 0.181769 64.2423 0.726991 64.1812 1.39337L63.7534 3.81658C63.7534 3.87716 63.8145 3.93774 63.8756 3.87716L66.1983 2.90788C66.8096 2.66556 67.1763 2.05976 67.1152 1.39337Z"
                    fill="white"></path>
                </svg>
              </a>
            </li>
            <li class="Lazord">
              <p class="logo_">GLAMORA</p>
            </li>
          </ul>
        </section>
      </div>
    </nav>

    <section class="_main_logo">
      <div class="Categories">
        <ul>
          <?php
          $sqlcat = mysqli_query($conn, "SELECT * FROM categories WHERE parent_id IS NULL LIMIT 8");
          $count = 0;
          while ($fetchcat = mysqli_fetch_assoc($sqlcat)) {
            if ($count >= 8)
              break;
            echo '<li><a href="Categories.php?id=' . $fetchcat['id'] . '" class="nav-link">' . $fetchcat['name'] . '</a></li>';
            $count++;
          }
          ?>
        </ul>
      </div>
    </section>
  </header>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    // Search functionality
    let searchinput = document.getElementById('search-input');

    searchinput.oninput = () => {
      $.ajax({
        type: "POST",
        url: "searchresult.php",
        data: { searchinput: searchinput.value },
        success: function (response) {
          $('#suggestions').html(response);
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    };

    // Cart count refresh
    function refreshCartCount() {
      $.ajax({
        type: "GET",
        url: "count_cart.php", // Adjust the URL to your actual cart count endpoint
        success: function (response) {
          $('.count_cart').html(response);
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        }
      });
    }

    setInterval(refreshCartCount, 1000);

    // Search history
    document.getElementById('search-input').addEventListener('input', function () {
      localStorage.setItem('lastSearch', this.value);
    });

    window.addEventListener('load', function () {
      const lastSearch = localStorage.getItem('lastSearch');
      if (lastSearch) {
        document.getElementById('search-input').value = lastSearch;
      }
    });

    // Mobile menu toggle
    const nav = document.querySelector(".nav"),
      searchIcon1 = document.querySelector("#searchIcon1"),
      searchIcon2 = document.querySelector("#searchIcon2"),
      navOpenBtn = document.querySelector(".navOpenBtn"),
      navCloseBtn = document.querySelector(".navCloseBtn");

    function toggleSearch(icon) {
      nav.classList.toggle("openSearch");
      nav.classList.remove("openNav");
      if (nav.classList.contains("openSearch")) {
        return icon.classList.replace("uil-search", "uil-times");
      }
      icon.classList.replace("uil-times", "uil-search");
    }

    searchIcon1.addEventListener("click", () => toggleSearch(searchIcon1));
    searchIcon2.addEventListener("click", () => toggleSearch(searchIcon2));

    navOpenBtn.addEventListener("click", () => {
      nav.classList.add("openNav");
      nav.classList.remove("openSearch");
      searchIcon1.classList.replace("uil-times", "uil-search");
      searchIcon2.classList.replace("uil-times", "uil-search");
    });

    navCloseBtn.addEventListener("click", () => {
      nav.classList.remove("openNav");
    });
  </script>
</body>

</html>