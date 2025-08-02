<?php
$id = $_GET['id'];
require('./db.php');
$select = mysqli_query($conn, "SELECT * FROM `orders` WHERE id = $id");
$fetchsql = mysqli_fetch_assoc($select);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../style/order.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,700,800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    <link rel="stylesheet" href="../style/all.css" />

    <link rel="stylesheet" href="../style/css/pace.css" />
    <link rel="stylesheet" href="../style/css/perfect-scrollbar.css" />
    <!-- ######################################################################################################################## -->
    <!-- ######################################################################################################################## -->
    <link rel="stylesheet" href="../style/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../style/css/all.min.css" />
    <link rel="stylesheet" href="../style/css/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../style/css/pace.css" />
    <link rel="stylesheet" href="../style/css/apexcharts.css" />
    <link rel="stylesheet" href="../style/css/main.min.css" />

    <link rel="stylesheet" href="../style/css/custom.css" />
    <!-- ######################################################################################################################## -->

</head>

<body>




    <body class="page-sidebar-collapsed">
        <div class="page-container">
            <div class="page-sidebar">
                <a class="logo" href="index.html">Neo</a>
                <ul class="list-unstyled accordion-menu">
                    <li>
                        <a href="#"><i data-feather="activity"></i>Dashboard<i
                                class="fas fa-chevron-right dropdown-icon"></i></a>
                        <ul class="">
                            <li>
                                <a href="index.html"><i class="far fa-circle"></i>eCommerce</a>
                            </li>
                            <li>
                                <a href="crypto.html"><i class="far fa-circle"></i>Crypto</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="#"><i data-feather="aperture"></i>Apps<i
                                class="fas fa-chevron-right dropdown-icon"></i></a>
                        <ul class="">
                            <li>
                                <a href="email.html"><i class="far fa-circle"></i>Email</a>
                            </li>
                            <li>
                                <a href="contact.html"><i class="far fa-circle"></i>Contact</a>
                            </li>
                            <li>
                                <a href="calendar.html"><i class="far fa-circle"></i>Calendar</a>
                            </li>
                            <li>
                                <a href="social.html"><i class="far fa-circle"></i>Social</a>
                            </li>
                            <li>
                                <a href="file-manager.html"><i class="far fa-circle"></i>File Manager</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="#"><i data-feather="code"></i>Components<i
                                class="fas fa-chevron-right dropdown-icon"></i></a>
                        <ul class="">
                            <li>
                                <a href="alerts.html"><i class="far fa-circle"></i>Alerts</a>
                            </li>
                            <li>
                                <a href="typography.html"><i class="far fa-circle"></i>Typography</a>
                            </li>
                            <li>
                                <a href="icons.html"><i class="far fa-circle"></i>Icons</a>
                            </li>
                            <li>
                                <a href="badge.html"><i class="far fa-circle"></i>Badge</a>
                            </li>
                            <li>
                                <a href="buttons.html"><i class="far fa-circle"></i>Buttons</a>
                            </li>
                            <li>
                                <a href="cards.html"><i class="far fa-circle"></i>Cards</a>
                            </li>
                            <li>
                                <a href="dropdowns.html"><i class="far fa-circle"></i>Dropdowns</a>
                            </li>
                            <li>
                                <a href="list-group.html"><i class="far fa-circle"></i>List Group</a>
                            </li>
                            <li>
                                <a href="toasts.html"><i class="far fa-circle"></i>Toasts</a>
                            </li>
                            <li>
                                <a href="modal.html"><i class="far fa-circle"></i>Modal</a>
                            </li>
                            <li>
                                <a href="pagination.html"><i class="far fa-circle"></i>Pagination</a>
                            </li>
                            <li>
                                <a href="popovers.html"><i class="far fa-circle"></i>Popovers</a>
                            </li>
                            <li>
                                <a href="progress.html"><i class="far fa-circle"></i>Progress</a>
                            </li>
                            <li>
                                <a href="spinners.html"><i class="far fa-circle"></i>Spinners</a>
                            </li>
                            <li>
                                <a href="accordion.html"><i class="far fa-circle"></i>Accordion</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="#"><i data-feather="box"></i>Plugins<i
                                class="fas fa-chevron-right dropdown-icon"></i></a>
                        <ul class="">
                            <li>
                                <a href="block-ui.html"><i class="far fa-circle"></i>Block UI</a>
                            </li>
                            <li>
                                <a href="session-timeout.html"><i class="far fa-circle"></i>Session Timeout</a>
                            </li>
                            <li>
                                <a href="tree-view.html"><i class="far fa-circle"></i>Tree View</a>
                            </li>
                            <li>
                                <a href="select2.html"><i class="far fa-circle"></i>Select2</a>
                            </li>
                        </ul>
                    </li>
                    <li class="active-page">
                        <a href="#" class="active"><i data-feather="star"></i>Pages<i
                                class="fas fa-chevron-right dropdown-icon"></i></a>
                        <ul class="">
                            <li>
                                <a href="invoice.html" class="active"><i class="far fa-circle"></i>Invoice</a>
                            </li>
                            <li>
                                <a href="404.html"><i class="far fa-circle"></i>404 Page</a>
                            </li>
                            <li>
                                <a href="500.html"><i class="far fa-circle"></i>500 Page</a>
                            </li>
                            <li>
                                <a href="blank-page.html"><i class="far fa-circle"></i>Blank Page</a>
                            </li>
                            <li>
                                <a href="login.html"><i class="far fa-circle"></i>Login</a>
                            </li>
                            <li>
                                <a href="register.html"><i class="far fa-circle"></i>Register</a>
                            </li>
                            <li>
                                <a href="lockscreen.html"><i class="far fa-circle"></i>Lockscreen</a>
                            </li>
                            <li>
                                <a href="price.html"><i class="far fa-circle"></i>Price</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="#"><i data-feather="droplet"></i>Form<i
                                class="fas fa-chevron-right dropdown-icon"></i></a>
                        <ul class="">
                            <li>
                                <a href="form-elements.html"><i class="far fa-circle"></i>Form Elements</a>
                            </li>
                            <li>
                                <a href="form-layout.html"><i class="far fa-circle"></i>Form Layout</a>
                            </li>
                            <li>
                                <a href="form-validation.html"><i class="far fa-circle"></i>Form Validation</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="#"><i data-feather="grid"></i>Tables<i
                                class="fas fa-chevron-right dropdown-icon"></i></a>
                        <ul class="">
                            <li>
                                <a href="tables.html"><i class="far fa-circle"></i>Basic Tables</a>
                            </li>
                            <li>
                                <a href="data-tables.html"><i class="far fa-circle"></i>Data Tables</a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="charts.html"><i data-feather="pie-chart"></i>Charts</a>
                    </li>
                </ul>
                <a href="#" id="sidebar-collapsed-toggle"><i data-feather="arrow-right"></i></a>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <nav class="navbar navbar-expand-lg d-flex justify-content-between">
                        <div class="header-title flex-fill">
                            <a href="#" id="sidebar-toggle"><i data-feather="arrow-left"></i></a>
                            <h5>Invoice</h5>
                        </div>


                        <div class="flex-fill" id="headerNav">
                            <ul class="navbar-nav">
                                <li class="nav-item d-md-block d-lg-none">
                                    <a class="nav-link" href="#" id="toggle-search"><i data-feather="search"></i></a>
                                </li>

                                <li class="nav-item dropdown">
                                    <a class="nav-link notifications-dropdown" href="#" id="notificationsDropDown"
                                        role="button" data-bs-toggle="dropdown"
                                        aria-expanded="false"><?php echo $fetchsql['numberofproducts'] ?>
                                        <div class="spinner-grow text-danger" role="status"></div>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end notif-drop-menu"
                                        aria-labelledby="notificationsDropDown">
                                        <h6 class="dropdown-header">Notifications</h6>
                                        <a href="#">
                                            <div class="header-notif">
                                                <div class="notif-image">
                                                    <span class="notification-badge bg-info text-white">
                                                        <i class="fas fa-bullhorn"></i>
                                                    </span>
                                                </div>
                                                <div class="notif-text">
                                                    <p class="bold-notif-text">
                                                        faucibus dolor in commodo lectus mattis
                                                    </p>
                                                    <small>19:00</small>
                                                </div>
                                            </div>
                                        </a>
                                        <a href="#">
                                            <div class="header-notif">
                                                <div class="notif-image">
                                                    <span class="notification-badge bg-primary text-white">
                                                        <i class="fas fa-bolt"></i>
                                                    </span>
                                                </div>
                                                <div class="notif-text">
                                                    <p class="bold-notif-text">
                                                        faucibus dolor in commodo lectus mattis
                                                    </p>
                                                    <small>18:00</small>
                                                </div>
                                            </div>
                                        </a>
                                        <a href="#">
                                            <div class="header-notif">
                                                <div class="notif-image">
                                                    <span class="notification-badge bg-success text-white">
                                                        <i class="fas fa-at"></i>
                                                    </span>
                                                </div>
                                                <div class="notif-text">
                                                    <p>faucibus dolor in commodo lectus mattis</p>
                                                    <small>yesterday</small>
                                                </div>
                                            </div>
                                        </a>
                                        <a href="#">
                                            <div class="header-notif">
                                                <div class="notif-image">
                                                    <span class="notification-badge">
                                                        <img src="../../assets/images/avatars/avatar2.jpeg" alt="" />
                                                    </span>
                                                </div>
                                                <div class="notif-text">
                                                    <p>faucibus dolor in commodo lectus mattis</p>
                                                    <small>yesterday</small>
                                                </div>
                                            </div>
                                        </a>
                                        <a href="#">
                                            <div class="header-notif">
                                                <div class="notif-image">
                                                    <span class="notification-badge">
                                                        <img src="../../assets/images/avatars/avatar5.jpeg" alt="" />
                                                    </span>
                                                </div>
                                                <div class="notif-text">
                                                    <p>faucibus dolor in commodo lectus mattis</p>
                                                    <small>yesterday</small>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link profile-dropdown" href="#" id="profileDropDown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false"><img
                                            src="../../assets/images/avatars/avatar1.jpeg" alt="" /></a>
                                    <div class="dropdown-menu dropdown-menu-end profile-drop-menu"
                                        aria-labelledby="profileDropDown">
                                        <a class="dropdown-item" href="#"><i data-feather="user"></i>Profile</a>
                                        <a class="dropdown-item" href="#"><i data-feather="inbox"></i>Messages</a>
                                        <a class="dropdown-item" href="#"><i data-feather="edit"></i>Activities<span
                                                class="badge rounded-pill bg-success">12</span></a>
                                        <a class="dropdown-item" href="#"><i data-feather="check-circle"></i>Tasks</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><i data-feather="settings"></i>Settings</a>
                                        <a class="dropdown-item" href="#"><i data-feather="unlock"></i>Lock</a>
                                        <a class="dropdown-item" href="#"><i data-feather="log-out"></i>Logout</a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
                <div class="main-wrapper">
                    <div class="row">
                        <div class="col">
                            <div class="card">
                                <div class="card-body">

                                    <div class="invoice-details">
                                        <div class="row">


                                            <div class="col">
                                                <p class="info">Name:</p>
                                                <p> <?php echo $fetchsql['name'] ?></p>
                                            </div>





                                            <div class="col">
                                                <p class="info">ID:</p>
                                                <p>IO237</p>
                                            </div>
                                            <div class="col">
                                                <p class="info">Invoice to:</p>
                                                <p> City : <?php echo $fetchsql['city'] ?></p>
                                                <p>Address : <?php echo $fetchsql['address'] ?></p>
                                            </div>


                                            <div class="col">
                                                <p class="info">Invoice to:</p>
                                                <p> phoneone : <?php echo $fetchsql['phoneone'] ?></p>
                                                <p>phonetwo : <?php echo $fetchsql['phonetwo'] ?></p>
                                            </div>




                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="table-responsive">
                                            <table class="table invoice-table">
                                                <thead>
                                                    <tr>

                                                        <th scope="col">Product</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Quantity</th>

                                                        <th scope="col">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>




                                                    <?php


                                                    echo $fetchsql['htmltage'];

                                                    ?>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="row invoice-last">
                                        <div class="col-9">
                                            <p>
                                                Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                                                Fusce ut ante id elit molestie<br />dapibus id
                                                sollicitudin vel, luctus sit amet justo
                                            </p>
                                        </div>
                                        <div class="col-3">
                                            <div class="invoice-info">
                                                <p>Subtotal <span><?php echo $fetchsql['finaltotalprice'] ?> EGP</span>
                                                </p>
                                                <p>Order number <span><?php echo $fetchsql['id'] ?></span></p>
                                                <p>Product numbers <span>( <?php echo $fetchsql['numberofproducts'] ?>
                                                        )</span></p>
                                                <p>Total <span><?php echo $fetchsql['finaltotalprice'] ?> EGP</span>
                                                </p>




                                                <div class="d-grid gap-2">
                                                    <button class="btn btn-danger m-t-xs" type="button">
                                                        Print Invoice
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>




        <!-- Javascripts -->
        <script src=" ../script/jquery-3.4.1.min.js"></script>
        <script src="https://unpkg.com/@popperjs/core@2"></script>

        <script src="../script/bootstrap.min.js"></script>
        <script src="https://unpkg.com/feather-icons"></script>

        <script src="../script/perfect-scrollbar.min.js"></script>
        <script src="../script/pace.min.js"></script>
        <script src="../script/apexcharts.min.js"></script>
        <script src="../script/jquery.sparkline.min.js"></script>
        <script src="../script/main.min.js"></script>
        <script src="../script/dashboard.js"></script>
    </body>
















    <style>
        img.col-lg-3.viwe_img {
            border-radius: 10px;
        }

        .flex-fill.flex-between {
            background-image: url(../img/logo_.jpg);
            background-position: center center;
            background-size: contain;
            background-repeat: no-repeat;
        }

        img.col-lg-3.viwe_img {
            width: 35px;
            height: 35px;
            border-radius: 3px;
        }
    </style>
</body>

</html>