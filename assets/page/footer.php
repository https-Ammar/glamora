<?php
require('db.php');
?>






<footer class="footer_">
  <!--<p class="Footer_heading__9RHrL"> Choose from the most requested categories </p>-->


  <!---->

  <div class="footer_grid">



    <div class="Footer_footer__sub-container_left__EJ4Bh">

      <p class="Footer_footer__heading__bwFl4"> GLAMORA</p>
      <p data-testid="" role="" class="label-c Footer_footer__label__NS_7T Footer_footer__sub-heading__Aroae">
        Online store specialized in
        <br />
        selling beauty products and accessories
      </p>

    </div>






    <div role="footerLinks" class="Footer_footer-link-div__9a5M_ undefined">

      <p class="style_menu__heading__0WKNN"> Categories</p>

      <ul class="style_menu__links__84qJF">



        <?php
        $select = mysqli_query($conn, "SELECT * FROM `categories` LIMIT 5");
        if ($select && mysqli_num_rows($select) > 0) {
          while ($fetch = mysqli_fetch_assoc($select)) {
            $category_id = htmlspecialchars($fetch['id'], ENT_QUOTES, 'UTF-8');
            $category_name = htmlspecialchars($fetch['name'], ENT_QUOTES, 'UTF-8');
            echo '
              
              <li><a class="c-opacity-60"  href="Categories.php?id=' . $category_id . '" >' . $category_name . '</a></li>';
          }
        } else {
          echo '<li>No categories found.</li>';
        }
        ?>

      </ul>
    </div>










    <div role="footerLinks" class="Footer_footer-link-div__9a5M_ undefined">

      <p class="style_menu__heading__0WKNN"> GLAMORA world</p>

      <ul class="style_menu__links__84qJF">
        <li><a class="c-opacity-60" href="/ar-eg/lazurde-investor-relations">Investor Relations</a></li>
        <li><a class="c-opacity-60" href="/ar-eg/lazurde-policies">Terms and Policies</a></li>
      </ul>
    </div>
    <div role="footerLinks" class="Footer_footer-link-div__9a5M_ undefined">
      <p class="style_menu__heading__0WKNN"> Customer Service</p>

      <ul class="style_menu__links__84qJF">
        <li><a class="c-opacity-60" href="/ar-eg/contact-us">Contact Us</a></li>
        <li><a class="c-opacity-60" href="/ar-eg/help-centre/order">Frequently Asked Questions</a></li>
        <li><a class="c-opacity-60" href="/ar-eg/store-locations">Store Locations</a></li>
      </ul>
    </div>


  </div>


  <!---->
  <div class="row mb-4">
    <div class="col-12 pb-4">
      <div class="line"></div>
    </div>
    <div class="col-md-6 text-md-left">
      <ul class="list-unstyled link-menu nav-left ll">
        <li><a href="#">Privacy Policy</a></li>
        <li><a href="#">Terms &amp; Conditions</a></li>
        <li><a href="#">Code of Conduct</a></li>
      </ul>

      <ul class="list-unstyled link-menu nav-left">
        <li><a href="#"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
              class="bi bi-twitter-x" viewBox="0 0 16 16">
              <path
                d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z" />
            </svg></a></li>
        <li><a href="#"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
              class="bi bi-instagram" viewBox="0 0 16 16">
              <path
                d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334" />
            </svg></a></li>
        <li><a href="#"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
              class="bi bi-facebook" viewBox="0 0 16 16">
              <path
                d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951" />
            </svg></a></li>
      </ul>
    </div>

  </div>



  <div class="row">
    <div class="col-md-7">
      <p data-testid="" role="" class="label-c Footer_footer__label__NS_7T Footer_footer__sub-heading__Aroae">
        Online store specialized in

        selling beauty products and accessories </p>
    </div>
  </div>

</footer>





<style>
  .col-12.pb-4 {}

  .row.mb-4 {
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding: 10px 0;
    margin-top: 5vh;
  }

  ul.list-unstyled.link-menu.nav-left {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .col-12.pb-4 {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .col-md-6.text-md-right {
    background: red !important;
    !i;
    !;
    display: block;
  }

  ul.list-unstyled.social.nav-right {
    background: red;
  }





  .footer_grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
  }

  ul.list-unstyled.link-menu.nav-left li a {
    color: #fff;
    display: inline-block;
    padding: 10px;
  }


  .col-md-6.text-md-left {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
</style>


<style>
  @media screen and (max-width: 992px) {
    footer.footer_ {
      padding: 25px;
    }

    .footer_grid {
      display: block;
    }

    .Footer_footer__sub-container_left__EJ4Bh {}

    .Footer_footer-link-div__9a5M_.undefined {
      margin: 3vh 0;
    }

    ul.list-unstyled.link-menu.nav-left.ll {
      display: none;
    }
  }
</style>





<!-- foooter -->