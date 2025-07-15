<?php
session_start();
require('./db.php');

if (!isset($_SESSION['userId'])) {
    header('Location: ./assets/page/login.php');
    exit();
}

$userid = $_SESSION['userId'];
$select = mysqli_query($conn, "SELECT * FROM usersadmin WHERE id = $userid");
$fetchname = mysqli_fetch_assoc($select);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE prouductid = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: index.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        $categoryId = intval($_POST['id']);
        mysqli_query($conn, "DELETE FROM products WHERE category_id = $categoryId");
        mysqli_query($conn, "DELETE FROM ads WHERE categoryid = $categoryId");

        $stmt = mysqli_prepare($conn, "DELETE FROM catageories WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $categoryId);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: index.php");
                exit();
            } else {
                echo "Error executing delete: " . mysqli_error($conn);
            }
        } else {
            echo "Error preparing delete: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['category']) && isset($_POST['linkaddress']) && isset($_FILES['photo'])) {
        $filepath = 'uploads/';
        $categories = $_POST['category'];
        $linkaddresses = $_POST['linkaddress'];

        if (is_array($_FILES['photo']['tmp_name'])) {
            foreach ($_FILES['photo']['tmp_name'] as $key => $tmp_name) {
                $photo = $_FILES['photo']['name'][$key];
                $photo_tmp = $_FILES['photo']['tmp_name'][$key];
                $photo_path = $filepath . basename($photo);

                if (!file_exists($filepath)) {
                    mkdir($filepath, 0777, true);
                }

                if (move_uploaded_file($photo_tmp, $photo_path)) {
                    $category_id = mysqli_real_escape_string($conn, $categories[$key]);
                    $photo_path_escaped = mysqli_real_escape_string($conn, $photo_path);
                    $linkaddress_escaped = mysqli_real_escape_string($conn, $linkaddresses[$key]);

                    $sql = "INSERT INTO ads (categoryid, photo, linkaddress) VALUES ('$category_id', '$photo_path_escaped', '$linkaddress_escaped')";
                    if (!mysqli_query($conn, $sql)) {
                        echo "Error: " . mysqli_error($conn);
                    }
                } else {
                    echo "Failed to upload file: " . htmlspecialchars($photo) . "<br>";
                }
            }
        } else {
            echo "No files were uploaded.";
        }

        mysqli_close($conn);
        header('Location: index.php');
        exit();
    } else {
        $name = trim($_POST['name'] ?? '');
        $image = $_FILES['image'] ?? null;

        if (!empty($name) && $image && $image['error'] === 0) {
            $name = $conn->real_escape_string($name);
            $targetDir = 'uploads/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $imageName = time() . '_' . basename($image['name']);
            $targetPath = $targetDir . $imageName;

            if (move_uploaded_file($image['tmp_name'], $targetPath)) {
                $stmt = $conn->prepare("INSERT INTO catageories (name, image) VALUES (?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ss", $name, $targetPath);
                    if ($stmt->execute()) {
                        $stmt->close();
                        header('Location: index.php');
                        exit();
                    } else {
                        echo "Insert failed: " . $stmt->error;
                    }
                } else {
                    echo "Prepare failed: " . $conn->error;
                }
            } else {
                echo "Image upload failed.";
            }
        } else {
            echo "Please fill all fields and select a valid image.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="/dashboard/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="./style/style.css">
    <link rel="stylesheet" href="./style/main.css">
    <link rel="stylesheet" href="./style/king.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" type="text/css" href="./style/vendor.css">
    <link rel="stylesheet" type="text/css" href="./style/all.css">
    <link rel="stylesheet" href="./style/next.css">



    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,700,800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />


    <!-- ######################################################################################################################## -->
    <!-- ######################################################################################################################## -->
    <link rel="stylesheet" href="./assets/style/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/style/all.min.css">
    <link rel="stylesheet" href="./assets/style/perfect-scrollbar.css">
    <link rel="stylesheet" href="./assets/style/pace.css">
    <link rel="stylesheet" href="./assets/style/apexcharts.css">
    <link rel="stylesheet" href="./assets/style/main.min.css">

    <link rel="stylesheet" href="./assets/style/custom.css">
    <!-- ######################################################################################################################## -->









    <title>Dashboard</title>
    <link rel="icon" type="image/png" href="./img/icon_web.PNG">

</head>

<body>
























    <!-- ######################################################################################################################## -->
    <!-- ######################################################################################################################## -->
    <!-- ######################################################################################################################## -->
    <!-- ######################################################################################################################## -->
    <!-- ######################################################################################################################## -->
    <!-- ######################################################################################################################## -->
    <!-- ######################################################################################################################## -->
    <!-- ######################################################################################################################## -->

    <body class="pace-done page-sidebar-collapsed no-loader">

        <div class="pace pace-inactive">
            <div class="pace-progress" data-progress-text="100%" data-progress="99"
                style="transform: translate3d(100%, 0px, 0px);">
                <div class="pace-progress-inner"></div>
            </div>
            <div class="pace-activity"></div>
        </div>







        <div class="page-container">
            <div class="page-sidebar ps">
                <a class="logo" href="index.html">Neo</a>
                <ul class="list-unstyled accordion-menu">
                    <li class="active-page">
                        <a href="#" class="active" onclick="changeElement('div1')"><svg
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="feather feather-activity">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>Profile

                        </a>



                    </li>

                    <li>
                        <a href="#" onclick="changeElement('div2')"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-code">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                            </svg>Dashboard </a>


                    </li>




                    <li>
                        <a href="#" onclick="changeElement('div3')"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-aperture">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="14.31" y1="8" x2="20.05" y2="17.94"></line>
                                <line x1="9.69" y1="8" x2="21.17" y2="8"></line>
                                <line x1="7.38" y1="12" x2="13.12" y2="2.06"></line>
                                <line x1="9.69" y1="16" x2="3.95" y2="6.06"></line>
                                <line x1="14.31" y1="16" x2="2.83" y2="16"></line>
                                <line x1="16.62" y1="12" x2="10.88" y2="21.94"></line>
                            </svg>Product List </a>


                    </li>


                    <li>
                        <a href="#" onclick="changeElement('div4')"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-box">
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                                </path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>Creat Product </a>


                    </li>
                    <li>
                        <a href="#" onclick="changeElement('div5')"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-star">
                                <polygon
                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                                </polygon>
                            </svg> Creat Catgory </a>


                    </li>
                    <li>
                        <a href="#" onclick="changeElement('div6')"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-droplet">
                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                            </svg>Advertisements </a>


                    </li>
                    <li>
                        <a href="#" onclick="changeElement('div7')"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-grid">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>Tables </a>


                    </li>
                    <li>
                        <!-- aaaaaaa -->
                        <a href="logout.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="feather feather-pie-chart">
                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                            </svg>Exit</a>
                    </li>
                </ul>
                <a href="#" id="sidebar-collapsed-toggle"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="feather feather-arrow-right">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg></a>
                <div class="ps__rail-x" style="left: 0px; bottom: 0px;">
                    <div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div>
                </div>
                <div class="ps__rail-y" style="top: 0px; right: 0px;">
                    <div class="ps__thumb-y" tabindex="0" style="top: 0px; height: 0px;"></div>
                </div>
            </div>
            <div class="page-content">
                <div class="page-header">
                    <nav class="navbar navbar-expand-lg d-flex justify-content-between">
                        <div class="header-title flex-fill">
                            <a href="#" id="sidebar-toggle"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left">
                                    <line x1="19" y1="12" x2="5" y2="12"></line>
                                    <polyline points="12 19 5 12 12 5"></polyline>
                                </svg></a>
                            <h5>


                                <span class="d-inline-block me-3">👋</span>Welcome !
                                <span><?php echo $fetchname['name'] ?></span>


                            </h5>
                        </div>
                        <div class="header-search">
                            <form>
                                <input class="form-control" type="text" placeholder="Type something.."
                                    aria-label="Search">
                                <a href="#" class="close-search"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="feather feather-x">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg></a>
                            </form>
                        </div>
                        <div class="flex-fill" id="headerNav">
                            <ul class="navbar-nav">
                                <li class="nav-item d-md-block d-lg-none">
                                    <a class="nav-link" href="#" id="toggle-search"><svg
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-search">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        </svg></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link activity-trigger" href="#" id="activity-sidebar-toggle"><svg
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-grid">
                                            <rect x="3" y="3" width="7" height="7"></rect>
                                            <rect x="14" y="3" width="7" height="7"></rect>
                                            <rect x="14" y="14" width="7" height="7"></rect>
                                            <rect x="3" y="14" width="7" height="7"></rect>
                                        </svg></a>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link notifications-dropdown" href="#" id="notificationsDropDown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">3
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
                                                        <img src="../../assets/images/avatars/avatar2.jpeg" alt="">
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
                                                        <img src="../../assets/images/avatars/avatar5.jpeg" alt="">
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
                                            src="../../assets/images/avatars/avatar1.jpeg" alt=""></a>
                                    <div class="dropdown-menu dropdown-menu-end profile-drop-menu"
                                        aria-labelledby="profileDropDown">
                                        <a class="dropdown-item" href="#"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-user">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>Profile</a>
                                        <a class="dropdown-item" href="#"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-inbox">
                                                <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline>
                                                <path
                                                    d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z">
                                                </path>
                                            </svg>Messages</a>
                                        <a class="dropdown-item" href="#"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-edit">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7">
                                                </path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z">
                                                </path>
                                            </svg>Activities<span class="badge rounded-pill bg-success">12</span></a>
                                        <a class="dropdown-item" href="#"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-check-circle">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>Tasks</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-settings">
                                                <circle cx="12" cy="12" r="3"></circle>
                                                <path
                                                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                                                </path>
                                            </svg>Settings</a>
                                        <a class="dropdown-item" href="#"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-unlock">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                                            </svg>Lock</a>
                                        <a class="dropdown-item" href="#"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" class="feather feather-log-out">
                                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                                <polyline points="16 17 21 12 16 7"></polyline>
                                                <line x1="21" y1="12" x2="9" y2="12"></line>
                                            </svg>Logout</a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>


                <!-- end-header -->



                <div class="profile_me  Div_Hid card-body" id="div1">
                    <div class="main-wrapper">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="profile-cover"></div>
                                <div class="profile-header">
                                    <div class="profile-img">
                                        <img src="./img/434038250_1559261361574300_806689860643605321_n.jpg" alt="">
                                    </div>
                                    <div class="profile-name">
                                        <h3><?php echo $fetchname['name'] ?></h3>
                                    </div>
                                    <div class="profile-header-menu">
                                        <ul class="list-unstyled">
                                            <li><a href="#" class="active">Feed</a></li>
                                            <li><a href="#">About</a></li>
                                            <li><a href="#">Friends</a></li>
                                            <li><a href="#">Photos</a></li>
                                            <li><a href="#">Videos</a></li>
                                            <li><a href="#">Music</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 col-lg-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">About</h5>
                                        <p>
                                            Quisque vel tellus sit amet quam efficitur sagittis. Fusce
                                            aliquam pulvinar suscipit.
                                        </p>
                                        <ul class="list-unstyled profile-about-list">
                                            <li>
                                                <i class="far fa-edit m-r-xxs"></i><span>Studied at <a href="#">San
                                                        Diego University</a></span>
                                            </li>
                                            <li>
                                                <i class="far fa-building m-r-xxs"></i><span>Manager at <a
                                                        href="#">Stacks</a></span>
                                            </li>
                                            <li>
                                                <i class="far fa-compass m-r-xxs"></i><span>From <a href="#">New
                                                        York</a></span>
                                            </li>
                                            <li>
                                                <i class="far fa-user m-r-xxs"></i><span>Followed by 320 people</span>
                                            </li>
                                            <li class="profile-about-list-buttons">
                                                <button class="btn btn-block btn-primary m-t-md">
                                                    Follow
                                                </button>
                                                <button class="btn btn-block btn-secondary m-t-md">
                                                    Message
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Contact Info</h5>
                                        <ul class="list-unstyled profile-about-list">
                                            <li>
                                                <i class="far fa-envelope m-r-xxs"></i><span>johan.doe@gmail.com</span>
                                            </li>
                                            <li>
                                                <i class="far fa-compass m-r-xxs"></i><span>Lives in <a href="#">San
                                                        Francisco, CA</a></span>
                                            </li>
                                            <li>
                                                <i class="far fa-address-book m-r-xxs"></i><span>+1 (678) 290
                                                    1680</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <div class="card card-bg">
                                    <div class="card-body">
                                        <div class="post">
                                            <div class="post-header">
                                                <img src="./img/434038250_1559261361574300_806689860643605321_n.jpg"
                                                    alt="">
                                                <div class="post-info">
                                                    <span
                                                        class="post-author"><?php echo $fetchname['name'] ?></span><br>
                                                    <span class="post-date">3hrs</span>
                                                </div>
                                                <div class="post-header-actions">
                                                    <a href="#"><i class="fas fa-ellipsis-h"></i></a>
                                                </div>
                                            </div>
                                            <div class="post-body">
                                                <p>
                                                    Proin eu fringilla dui. Pellentesque mattis lobortis
                                                    mauris eu tincidunt. Maecenas hendrerit faucibus dolor,
                                                    in commodo lectus mattis ac.
                                                </p>
                                                <img src=".../.../assets/images/card2.jpeg" class="post-image" alt="">
                                            </div>
                                            <div class="post-actions">
                                                <ul class="list-unstyled">
                                                    <li>
                                                        <a href="#" class="like-btn"><i
                                                                class="far fa-heart"></i>Like</a>
                                                    </li>
                                                    <li>
                                                        <a href="#"><i class="far fa-comment"></i>Comment</a>
                                                    </li>
                                                    <li>
                                                        <a href="#"><i class="far fa-paper-plane"></i>Share</a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="post-comments">
                                                <div class="post-comm">
                                                    <img src=".../.../assets/images/avatars/avatar3.jpeg"
                                                        class="comment-img" alt="">
                                                    <div class="comment-container">
                                                        <span class="comment-author">
                                                            Sonny Rosas
                                                            <small class="comment-date">5min</small>
                                                        </span>
                                                    </div>
                                                    <span class="comment-text">
                                                        Mauris ultrices convallis massa, nec facilisis enim
                                                        interdum ac.
                                                    </span>
                                                </div>
                                                <div class="post-comm">
                                                    <img src=".../.../assets/images/avatars/avatar4.jpeg"
                                                        class="comment-img" alt="">
                                                    <div class="comment-container">
                                                        <span class="comment-author">
                                                            Jacob Lee
                                                            <small class="comment-date">27min</small>
                                                        </span>
                                                    </div>
                                                    <span class="comment-text">
                                                        Cras tincidunt quam nisl, vitae aliquet enim pharetra
                                                        at. Nunc varius bibendum turpis, vitae ultrices tortor
                                                        facilisis ac.
                                                    </span>
                                                </div>
                                                <div class="new-comment">
                                                    <div class="input-group mb-3">
                                                        <input type="text" class="form-control"
                                                            placeholder="Type something" aria-label="Type Something"
                                                            aria-describedby="button-addon2">
                                                        <button class="btn btn-success" type="button"
                                                            id="button-addon2">
                                                            Comment
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Stories</h5>
                                        <div class="story-list">
                                            <div class="story">
                                                <a href="#"><img src=".../.../assets/images/avatars/avatar1.jpeg"
                                                        alt=""></a>
                                                <div class="story-info">
                                                    <a href="#"><span class="story-author">Johan Doe</span></a>
                                                    <span class="story-time">17min</span>
                                                </div>
                                            </div>
                                            <div class="story">
                                                <a href="#"><img src=".../.../assets/images/avatars/avatar2.jpeg"
                                                        alt=""></a>
                                                <div class="story-info">
                                                    <a href="#"><span class="story-author">Nina Doe</span></a>
                                                    <span class="story-time">54min</span>
                                                </div>
                                            </div>
                                            <div class="story">
                                                <a href="#"><img src=".../.../assets/images/avatars/avatar3.jpeg"
                                                        alt=""></a>
                                                <div class="story-info">
                                                    <a href="#"><span class="story-author">John Doe</span></a>
                                                    <span class="story-time">2hrs</span>
                                                </div>
                                            </div>
                                            <div class="story">
                                                <a href="#"><img src=".../.../assets/images/avatars/avatar4.jpeg"
                                                        alt=""></a>
                                                <div class="story-info">
                                                    <a href="#"><span class="story-author">Nina Doe</span></a>
                                                    <span class="story-time">7hrs</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
















                <div class="home_page Div_Hid" id="div2">
                    <div class="main-wrapper">













                        <!-- 3333333333333333 -->
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card main-chart-card">
                                    <div>
                                        <div id="apex3" style="min-height: 284px;">
                                            <div id="apexcharts0bkyxry9"
                                                class="apexcharts-canvas apexcharts0bkyxry9 apexcharts-theme-light"
                                                style="width: 549px; height: 269px;"><svg id="SvgjsSvg1123" width="549"
                                                    height="269" xmlns="http://www.w3.org/2000/svg" version="1.1"
                                                    xmlns:xlink="http://www.w3.org/1999/xlink"
                                                    xmlns:svgjs="http://svgjs.com/svgjs" class="apexcharts-svg"
                                                    xmlns:data="ApexChartsNS" transform="translate(0, 0)"
                                                    style="background: transparent;">
                                                    <foreignObject x="0" y="0" width="549" height="269">
                                                        <div class="apexcharts-legend apexcharts-align-center position-bottom"
                                                            xmlns="http://www.w3.org/1999/xhtml"
                                                            style="inset: auto 0px 1px; position: absolute; max-height: 134.5px;">
                                                            <div class="apexcharts-legend-series" rel="1"
                                                                seriesname="NetxProfit" data:collapsed="false"
                                                                style="margin: 2px 5px;"><span
                                                                    class="apexcharts-legend-marker" rel="1"
                                                                    data:collapsed="false"
                                                                    style="background: rgb(0, 143, 251) !important; color: rgb(0, 143, 251); height: 12px; width: 12px; left: 0px; top: 0px; border-width: 0px; border-color: rgb(255, 255, 255); border-radius: 2px;"></span><span
                                                                    class="apexcharts-legend-text" rel="1" i="0"
                                                                    data:default-text="Net%20Profit"
                                                                    data:collapsed="false"
                                                                    style="color: rgb(154, 156, 171); font-size: 12px; font-weight: 400; font-family: Helvetica, Arial, sans-serif;">Net
                                                                    Profit</span></div>
                                                            <div class="apexcharts-legend-series" rel="2"
                                                                seriesname="Revenue" data:collapsed="false"
                                                                style="margin: 2px 5px;"><span
                                                                    class="apexcharts-legend-marker" rel="2"
                                                                    data:collapsed="false"
                                                                    style="background: rgb(0, 227, 150) !important; color: rgb(0, 227, 150); height: 12px; width: 12px; left: 0px; top: 0px; border-width: 0px; border-color: rgb(255, 255, 255); border-radius: 2px;"></span><span
                                                                    class="apexcharts-legend-text" rel="2" i="1"
                                                                    data:default-text="Revenue" data:collapsed="false"
                                                                    style="color: rgb(154, 156, 171); font-size: 12px; font-weight: 400; font-family: Helvetica, Arial, sans-serif;">Revenue</span>
                                                            </div>
                                                            <div class="apexcharts-legend-series" rel="3"
                                                                seriesname="FreexCashxFlow" data:collapsed="false"
                                                                style="margin: 2px 5px;"><span
                                                                    class="apexcharts-legend-marker" rel="3"
                                                                    data:collapsed="false"
                                                                    style="background: rgb(254, 176, 25) !important; color: rgb(254, 176, 25); height: 12px; width: 12px; left: 0px; top: 0px; border-width: 0px; border-color: rgb(255, 255, 255); border-radius: 2px;"></span><span
                                                                    class="apexcharts-legend-text" rel="3" i="2"
                                                                    data:default-text="Free%20Cash%20Flow"
                                                                    data:collapsed="false"
                                                                    style="color: rgb(154, 156, 171); font-size: 12px; font-weight: 400; font-family: Helvetica, Arial, sans-serif;">Free
                                                                    Cash Flow</span></div>
                                                        </div>
                                                        <style type="text/css">
                                                            .apexcharts-legend {
                                                                display: flex;
                                                                overflow: auto;
                                                                padding: 0 10px;
                                                            }

                                                            .apexcharts-legend.position-bottom,
                                                            .apexcharts-legend.position-top {
                                                                flex-wrap: wrap
                                                            }

                                                            .apexcharts-legend.position-right,
                                                            .apexcharts-legend.position-left {
                                                                flex-direction: column;
                                                                bottom: 0;
                                                            }

                                                            .apexcharts-legend.position-bottom.apexcharts-align-left,
                                                            .apexcharts-legend.position-top.apexcharts-align-left,
                                                            .apexcharts-legend.position-right,
                                                            .apexcharts-legend.position-left {
                                                                justify-content: flex-start;
                                                            }

                                                            .apexcharts-legend.position-bottom.apexcharts-align-center,
                                                            .apexcharts-legend.position-top.apexcharts-align-center {
                                                                justify-content: center;
                                                            }

                                                            .apexcharts-legend.position-bottom.apexcharts-align-right,
                                                            .apexcharts-legend.position-top.apexcharts-align-right {
                                                                justify-content: flex-end;
                                                            }

                                                            .apexcharts-legend-series {
                                                                cursor: pointer;
                                                                line-height: normal;
                                                            }

                                                            .apexcharts-legend.position-bottom .apexcharts-legend-series,
                                                            .apexcharts-legend.position-top .apexcharts-legend-series {
                                                                display: flex;
                                                                align-items: center;
                                                            }

                                                            .apexcharts-legend-text {
                                                                position: relative;
                                                                font-size: 14px;
                                                            }

                                                            .apexcharts-legend-text *,
                                                            .apexcharts-legend-marker * {
                                                                pointer-events: none;
                                                            }

                                                            .apexcharts-legend-marker {
                                                                position: relative;
                                                                display: inline-block;
                                                                cursor: pointer;
                                                                margin-right: 3px;
                                                                border-style: solid;
                                                            }

                                                            .apexcharts-legend.apexcharts-align-right .apexcharts-legend-series,
                                                            .apexcharts-legend.apexcharts-align-left .apexcharts-legend-series {
                                                                display: inline-block;
                                                            }

                                                            .apexcharts-legend-series.apexcharts-no-click {
                                                                cursor: auto;
                                                            }

                                                            .apexcharts-legend .apexcharts-hidden-zero-series,
                                                            .apexcharts-legend .apexcharts-hidden-null-series {
                                                                display: none !important;
                                                            }

                                                            .apexcharts-inactive-legend {
                                                                opacity: 0.45;
                                                            }
                                                        </style>
                                                    </foreignObject>
                                                    <g id="SvgjsG1125" class="apexcharts-inner apexcharts-graphical"
                                                        transform="translate(52.859375, 30)">
                                                        <defs id="SvgjsDefs1124">
                                                            <linearGradient id="SvgjsLinearGradient1130" x1="0" y1="0"
                                                                x2="0" y2="1">
                                                                <stop id="SvgjsStop1131" stop-opacity="0.4"
                                                                    stop-color="rgba(216,227,240,0.4)" offset="0">
                                                                </stop>
                                                                <stop id="SvgjsStop1132" stop-opacity="0.5"
                                                                    stop-color="rgba(190,209,230,0.5)" offset="1">
                                                                </stop>
                                                                <stop id="SvgjsStop1133" stop-opacity="0.5"
                                                                    stop-color="rgba(190,209,230,0.5)" offset="1">
                                                                </stop>
                                                            </linearGradient>
                                                            <clipPath id="gridRectMask0bkyxry9">
                                                                <rect id="SvgjsRect1135" width="492.140625"
                                                                    height="185.348" x="-3" y="-1" rx="0" ry="0"
                                                                    opacity="1" stroke-width="0" stroke="none"
                                                                    stroke-dasharray="0" fill="#fff"></rect>
                                                            </clipPath>
                                                            <clipPath id="gridRectMarkerMask0bkyxry9">
                                                                <rect id="SvgjsRect1136" width="490.140625"
                                                                    height="187.348" x="-2" y="-2" rx="0" ry="0"
                                                                    opacity="1" stroke-width="0" stroke="none"
                                                                    stroke-dasharray="0" fill="#fff"></rect>
                                                            </clipPath>
                                                        </defs>
                                                        <rect id="SvgjsRect1134" width="9.902864583333333"
                                                            height="183.348" x="0" y="0" rx="0" ry="0" opacity="1"
                                                            stroke-width="0" stroke-dasharray="3"
                                                            fill="url(#SvgjsLinearGradient1130)"
                                                            class="apexcharts-xcrosshairs" y2="183.348" filter="none"
                                                            fill-opacity="0.9"></rect>
                                                        <g id="SvgjsG1171" class="apexcharts-xaxis"
                                                            transform="translate(0, 0)">
                                                            <g id="SvgjsG1172" class="apexcharts-xaxis-texts-g"
                                                                transform="translate(0, -4)"><text id="SvgjsText1174"
                                                                    font-family="Helvetica, Arial, sans-serif"
                                                                    x="27.0078125" y="212.348" text-anchor="middle"
                                                                    dominant-baseline="auto" font-size="12px"
                                                                    font-weight="400" fill="#9a9cab"
                                                                    class="apexcharts-text apexcharts-xaxis-label "
                                                                    style="font-family: Helvetica, Arial, sans-serif;">
                                                                    <tspan id="SvgjsTspan1175">Feb</tspan>
                                                                    <title>Feb</title>
                                                                </text><text id="SvgjsText1177"
                                                                    font-family="Helvetica, Arial, sans-serif"
                                                                    x="81.0234375" y="212.348" text-anchor="middle"
                                                                    dominant-baseline="auto" font-size="12px"
                                                                    font-weight="400" fill="#9a9cab"
                                                                    class="apexcharts-text apexcharts-xaxis-label "
                                                                    style="font-family: Helvetica, Arial, sans-serif;">
                                                                    <tspan id="SvgjsTspan1178">Mar</tspan>
                                                                    <title>Mar</title>
                                                                </text><text id="SvgjsText1180"
                                                                    font-family="Helvetica, Arial, sans-serif"
                                                                    x="135.0390625" y="212.348" text-anchor="middle"
                                                                    dominant-baseline="auto" font-size="12px"
                                                                    font-weight="400" fill="#9a9cab"
                                                                    class="apexcharts-text apexcharts-xaxis-label "
                                                                    style="font-family: Helvetica, Arial, sans-serif;">
                                                                    <tspan id="SvgjsTspan1181">Apr</tspan>
                                                                    <title>Apr</title>
                                                                </text><text id="SvgjsText1183"
                                                                    font-family="Helvetica, Arial, sans-serif"
                                                                    x="189.0546875" y="212.348" text-anchor="middle"
                                                                    dominant-baseline="auto" font-size="12px"
                                                                    font-weight="400" fill="#9a9cab"
                                                                    class="apexcharts-text apexcharts-xaxis-label "
                                                                    style="font-family: Helvetica, Arial, sans-serif;">
                                                                    <tspan id="SvgjsTspan1184">May</tspan>
                                                                    <title>May</title>
                                                                </text><text id="SvgjsText1186"
                                                                    font-family="Helvetica, Arial, sans-serif"
                                                                    x="243.0703125" y="212.348" text-anchor="middle"
                                                                    dominant-baseline="auto" font-size="12px"
                                                                    font-weight="400" fill="#9a9cab"
                                                                    class="apexcharts-text apexcharts-xaxis-label "
                                                                    style="font-family: Helvetica, Arial, sans-serif;">
                                                                    <tspan id="SvgjsTspan1187">Jun</tspan>
                                                                    <title>Jun</title>
                                                                </text><text id="SvgjsText1189"
                                                                    font-family="Helvetica, Arial, sans-serif"
                                                                    x="297.0859375" y="212.348" text-anchor="middle"
                                                                    dominant-baseline="auto" font-size="12px"
                                                                    font-weight="400" fill="#9a9cab"
                                                                    class="apexcharts-text apexcharts-xaxis-label "
                                                                    style="font-family: Helvetica, Arial, sans-serif;">
                                                                    <tspan id="SvgjsTspan1190">Jul</tspan>
                                                                    <title>Jul</title>
                                                                </text><text id="SvgjsText1192"
                                                                    font-family="Helvetica, Arial, sans-serif"
                                                                    x="351.1015625" y="212.348" text-anchor="middle"
                                                                    dominant-baseline="auto" font-size="12px"
                                                                    font-weight="400" fill="#9a9cab"
                                                                    class="apexcharts-text apexcharts-xaxis-label "
                                                                    style="font-family: Helvetica, Arial, sans-serif;">
                                                                    <tspan id="SvgjsTspan1193">Aug</tspan>
                                                                    <title>Aug</title>
                                                                </text><text id="SvgjsText1195"
                                                                    font-family="Helvetica, Arial, sans-serif"
                                                                    x="405.1171875" y="212.348" text-anchor="middle"
                                                                    dominant-baseline="auto" font-size="12px"
                                                                    font-weight="400" fill="#9a9cab"
                                                                    class="apexcharts-text apexcharts-xaxis-label "
                                                                    style="font-family: Helvetica, Arial, sans-serif;">
                                                                    <tspan id="SvgjsTspan1196">Sep</tspan>
                                                                    <title>Sep</title>
                                                                </text><text id="SvgjsText1198"
                                                                    font-family="Helvetica, Arial, sans-serif"
                                                                    x="459.1328125" y="212.348" text-anchor="middle"
                                                                    dominant-baseline="auto" font-size="12px"
                                                                    font-weight="400" fill="#9a9cab"
                                                                    class="apexcharts-text apexcharts-xaxis-label "
                                                                    style="font-family: Helvetica, Arial, sans-serif;">
                                                                    <tspan id="SvgjsTspan1199">Oct</tspan>
                                                                    <title>Oct</title>
                                                                </text></g>
                                                            <line id="SvgjsLine1200" x1="0" y1="184.348" x2="486.140625"
                                                                y2="184.348" stroke="#e0e0e0" stroke-dasharray="0"
                                                                stroke-width="1"></line>
                                                        </g>
                                                        <g id="SvgjsG1215" class="apexcharts-grid">
                                                            <g id="SvgjsG1216" class="apexcharts-gridlines-horizontal">
                                                                <line id="SvgjsLine1228" x1="0" y1="0" x2="486.140625"
                                                                    y2="0" stroke="#9a9cab" stroke-dasharray="4"
                                                                    class="apexcharts-gridline"></line>
                                                                <line id="SvgjsLine1229" x1="0" y1="45.837"
                                                                    x2="486.140625" y2="45.837" stroke="#9a9cab"
                                                                    stroke-dasharray="4" class="apexcharts-gridline">
                                                                </line>
                                                                <line id="SvgjsLine1230" x1="0" y1="91.674"
                                                                    x2="486.140625" y2="91.674" stroke="#9a9cab"
                                                                    stroke-dasharray="4" class="apexcharts-gridline">
                                                                </line>
                                                                <line id="SvgjsLine1231" x1="0" y1="137.51100000000002"
                                                                    x2="486.140625" y2="137.51100000000002"
                                                                    stroke="#9a9cab" stroke-dasharray="4"
                                                                    class="apexcharts-gridline"></line>
                                                                <line id="SvgjsLine1232" x1="0" y1="183.348"
                                                                    x2="486.140625" y2="183.348" stroke="#9a9cab"
                                                                    stroke-dasharray="4" class="apexcharts-gridline">
                                                                </line>
                                                            </g>
                                                            <g id="SvgjsG1217" class="apexcharts-gridlines-vertical">
                                                            </g>
                                                            <line id="SvgjsLine1218" x1="0" y1="184.348" x2="0"
                                                                y2="190.348" stroke="#e0e0e0" stroke-dasharray="0"
                                                                class="apexcharts-xaxis-tick"></line>
                                                            <line id="SvgjsLine1219" x1="54.015625" y1="184.348"
                                                                x2="54.015625" y2="190.348" stroke="#e0e0e0"
                                                                stroke-dasharray="0" class="apexcharts-xaxis-tick">
                                                            </line>
                                                            <line id="SvgjsLine1220" x1="108.03125" y1="184.348"
                                                                x2="108.03125" y2="190.348" stroke="#e0e0e0"
                                                                stroke-dasharray="0" class="apexcharts-xaxis-tick">
                                                            </line>
                                                            <line id="SvgjsLine1221" x1="162.046875" y1="184.348"
                                                                x2="162.046875" y2="190.348" stroke="#e0e0e0"
                                                                stroke-dasharray="0" class="apexcharts-xaxis-tick">
                                                            </line>
                                                            <line id="SvgjsLine1222" x1="216.0625" y1="184.348"
                                                                x2="216.0625" y2="190.348" stroke="#e0e0e0"
                                                                stroke-dasharray="0" class="apexcharts-xaxis-tick">
                                                            </line>
                                                            <line id="SvgjsLine1223" x1="270.078125" y1="184.348"
                                                                x2="270.078125" y2="190.348" stroke="#e0e0e0"
                                                                stroke-dasharray="0" class="apexcharts-xaxis-tick">
                                                            </line>
                                                            <line id="SvgjsLine1224" x1="324.09375" y1="184.348"
                                                                x2="324.09375" y2="190.348" stroke="#e0e0e0"
                                                                stroke-dasharray="0" class="apexcharts-xaxis-tick">
                                                            </line>
                                                            <line id="SvgjsLine1225" x1="378.109375" y1="184.348"
                                                                x2="378.109375" y2="190.348" stroke="#e0e0e0"
                                                                stroke-dasharray="0" class="apexcharts-xaxis-tick">
                                                            </line>
                                                            <line id="SvgjsLine1226" x1="432.125" y1="184.348"
                                                                x2="432.125" y2="190.348" stroke="#e0e0e0"
                                                                stroke-dasharray="0" class="apexcharts-xaxis-tick">
                                                            </line>
                                                            <line id="SvgjsLine1227" x1="486.140625" y1="184.348"
                                                                x2="486.140625" y2="190.348" stroke="#e0e0e0"
                                                                stroke-dasharray="0" class="apexcharts-xaxis-tick">
                                                            </line>
                                                            <line id="SvgjsLine1234" x1="0" y1="183.348" x2="486.140625"
                                                                y2="183.348" stroke="transparent" stroke-dasharray="0">
                                                            </line>
                                                            <line id="SvgjsLine1233" x1="0" y1="1" x2="0" y2="183.348"
                                                                stroke="transparent" stroke-dasharray="0"></line>
                                                        </g>
                                                        <g id="SvgjsG1137"
                                                            class="apexcharts-bar-series apexcharts-plot-series">
                                                            <g id="SvgjsG1138" class="apexcharts-series" rel="1"
                                                                seriesName="NetxProfit" data:realIndex="0">
                                                                <path id="SvgjsPath1140"
                                                                    d="M 12.153515625 183.348L 12.153515625 116.12040000000002L 20.056380208333334 116.12040000000002L 20.056380208333334 116.12040000000002L 20.056380208333334 183.348L 20.056380208333334 183.348z"
                                                                    fill="rgba(0,143,251,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="0" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 12.153515625 183.348L 12.153515625 116.12040000000002L 20.056380208333334 116.12040000000002L 20.056380208333334 116.12040000000002L 20.056380208333334 183.348L 20.056380208333334 183.348z"
                                                                    pathFrom="M 12.153515625 183.348L 12.153515625 183.348L 20.056380208333334 183.348L 20.056380208333334 183.348L 20.056380208333334 183.348L 12.153515625 183.348"
                                                                    cy="116.12040000000002" cx="65.169140625" j="0"
                                                                    val="44" barHeight="67.2276"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1141"
                                                                    d="M 66.169140625 183.348L 66.169140625 99.3135L 74.07200520833334 99.3135L 74.07200520833334 99.3135L 74.07200520833334 183.348L 74.07200520833334 183.348z"
                                                                    fill="rgba(0,143,251,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="0" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 66.169140625 183.348L 66.169140625 99.3135L 74.07200520833334 99.3135L 74.07200520833334 99.3135L 74.07200520833334 183.348L 74.07200520833334 183.348z"
                                                                    pathFrom="M 66.169140625 183.348L 66.169140625 183.348L 74.07200520833334 183.348L 74.07200520833334 183.348L 74.07200520833334 183.348L 66.169140625 183.348"
                                                                    cy="99.3135" cx="119.184765625" j="1" val="55"
                                                                    barHeight="84.03450000000001"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1142"
                                                                    d="M 120.184765625 183.348L 120.184765625 96.25770000000001L 128.08763020833334 96.25770000000001L 128.08763020833334 96.25770000000001L 128.08763020833334 183.348L 128.08763020833334 183.348z"
                                                                    fill="rgba(0,143,251,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="0" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 120.184765625 183.348L 120.184765625 96.25770000000001L 128.08763020833334 96.25770000000001L 128.08763020833334 96.25770000000001L 128.08763020833334 183.348L 128.08763020833334 183.348z"
                                                                    pathFrom="M 120.184765625 183.348L 120.184765625 183.348L 128.08763020833334 183.348L 128.08763020833334 183.348L 128.08763020833334 183.348L 120.184765625 183.348"
                                                                    cy="96.25770000000001" cx="173.200390625" j="2"
                                                                    val="57" barHeight="87.0903"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1143"
                                                                    d="M 174.200390625 183.348L 174.200390625 97.78560000000002L 182.10325520833334 97.78560000000002L 182.10325520833334 97.78560000000002L 182.10325520833334 183.348L 182.10325520833334 183.348z"
                                                                    fill="rgba(0,143,251,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="0" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 174.200390625 183.348L 174.200390625 97.78560000000002L 182.10325520833334 97.78560000000002L 182.10325520833334 97.78560000000002L 182.10325520833334 183.348L 182.10325520833334 183.348z"
                                                                    pathFrom="M 174.200390625 183.348L 174.200390625 183.348L 182.10325520833334 183.348L 182.10325520833334 183.348L 182.10325520833334 183.348L 174.200390625 183.348"
                                                                    cy="97.78560000000002" cx="227.216015625" j="3"
                                                                    val="56" barHeight="85.5624"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1144"
                                                                    d="M 228.216015625 183.348L 228.216015625 90.1461L 236.11888020833334 90.1461L 236.11888020833334 90.1461L 236.11888020833334 183.348L 236.11888020833334 183.348z"
                                                                    fill="rgba(0,143,251,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="0" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 228.216015625 183.348L 228.216015625 90.1461L 236.11888020833334 90.1461L 236.11888020833334 90.1461L 236.11888020833334 183.348L 236.11888020833334 183.348z"
                                                                    pathFrom="M 228.216015625 183.348L 228.216015625 183.348L 236.11888020833334 183.348L 236.11888020833334 183.348L 236.11888020833334 183.348L 228.216015625 183.348"
                                                                    cy="90.1461" cx="281.231640625" j="4" val="61"
                                                                    barHeight="93.20190000000001"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1145"
                                                                    d="M 282.231640625 183.348L 282.231640625 94.72980000000001L 290.13450520833334 94.72980000000001L 290.13450520833334 94.72980000000001L 290.13450520833334 183.348L 290.13450520833334 183.348z"
                                                                    fill="rgba(0,143,251,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="0" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 282.231640625 183.348L 282.231640625 94.72980000000001L 290.13450520833334 94.72980000000001L 290.13450520833334 94.72980000000001L 290.13450520833334 183.348L 290.13450520833334 183.348z"
                                                                    pathFrom="M 282.231640625 183.348L 282.231640625 183.348L 290.13450520833334 183.348L 290.13450520833334 183.348L 290.13450520833334 183.348L 282.231640625 183.348"
                                                                    cy="94.72980000000001" cx="335.247265625" j="5"
                                                                    val="58" barHeight="88.6182"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1146"
                                                                    d="M 336.247265625 183.348L 336.247265625 87.09030000000001L 344.15013020833334 87.09030000000001L 344.15013020833334 87.09030000000001L 344.15013020833334 183.348L 344.15013020833334 183.348z"
                                                                    fill="rgba(0,143,251,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="0" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 336.247265625 183.348L 336.247265625 87.09030000000001L 344.15013020833334 87.09030000000001L 344.15013020833334 87.09030000000001L 344.15013020833334 183.348L 344.15013020833334 183.348z"
                                                                    pathFrom="M 336.247265625 183.348L 336.247265625 183.348L 344.15013020833334 183.348L 344.15013020833334 183.348L 344.15013020833334 183.348L 336.247265625 183.348"
                                                                    cy="87.09030000000001" cx="389.262890625" j="6"
                                                                    val="63" barHeight="96.2577"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1147"
                                                                    d="M 390.262890625 183.348L 390.262890625 91.674L 398.16575520833334 91.674L 398.16575520833334 91.674L 398.16575520833334 183.348L 398.16575520833334 183.348z"
                                                                    fill="rgba(0,143,251,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="0" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 390.262890625 183.348L 390.262890625 91.674L 398.16575520833334 91.674L 398.16575520833334 91.674L 398.16575520833334 183.348L 398.16575520833334 183.348z"
                                                                    pathFrom="M 390.262890625 183.348L 390.262890625 183.348L 398.16575520833334 183.348L 398.16575520833334 183.348L 398.16575520833334 183.348L 390.262890625 183.348"
                                                                    cy="91.674" cx="443.278515625" j="7" val="60"
                                                                    barHeight="91.674" barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1148"
                                                                    d="M 444.278515625 183.348L 444.278515625 82.5066L 452.18138020833334 82.5066L 452.18138020833334 82.5066L 452.18138020833334 183.348L 452.18138020833334 183.348z"
                                                                    fill="rgba(0,143,251,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="0" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 444.278515625 183.348L 444.278515625 82.5066L 452.18138020833334 82.5066L 452.18138020833334 82.5066L 452.18138020833334 183.348L 452.18138020833334 183.348z"
                                                                    pathFrom="M 444.278515625 183.348L 444.278515625 183.348L 452.18138020833334 183.348L 452.18138020833334 183.348L 452.18138020833334 183.348L 444.278515625 183.348"
                                                                    cy="82.5066" cx="497.294140625" j="8" val="66"
                                                                    barHeight="100.84140000000001"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                            </g>
                                                            <g id="SvgjsG1149" class="apexcharts-series" rel="2"
                                                                seriesName="Revenue" data:realIndex="1">
                                                                <path id="SvgjsPath1151"
                                                                    d="M 22.056380208333334 183.348L 22.056380208333334 67.22760000000001L 29.959244791666666 67.22760000000001L 29.959244791666666 67.22760000000001L 29.959244791666666 183.348L 29.959244791666666 183.348z"
                                                                    fill="rgba(0,227,150,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="1" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 22.056380208333334 183.348L 22.056380208333334 67.22760000000001L 29.959244791666666 67.22760000000001L 29.959244791666666 67.22760000000001L 29.959244791666666 183.348L 29.959244791666666 183.348z"
                                                                    pathFrom="M 22.056380208333334 183.348L 22.056380208333334 183.348L 29.959244791666666 183.348L 29.959244791666666 183.348L 29.959244791666666 183.348L 22.056380208333334 183.348"
                                                                    cy="67.22760000000001" cx="75.07200520833334" j="0"
                                                                    val="76" barHeight="116.1204"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1152"
                                                                    d="M 76.07200520833334 183.348L 76.07200520833334 53.476500000000016L 83.97486979166666 53.476500000000016L 83.97486979166666 53.476500000000016L 83.97486979166666 183.348L 83.97486979166666 183.348z"
                                                                    fill="rgba(0,227,150,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="1" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 76.07200520833334 183.348L 76.07200520833334 53.476500000000016L 83.97486979166666 53.476500000000016L 83.97486979166666 53.476500000000016L 83.97486979166666 183.348L 83.97486979166666 183.348z"
                                                                    pathFrom="M 76.07200520833334 183.348L 76.07200520833334 183.348L 83.97486979166666 183.348L 83.97486979166666 183.348L 83.97486979166666 183.348L 76.07200520833334 183.348"
                                                                    cy="53.476500000000016" cx="129.08763020833334"
                                                                    j="1" val="85" barHeight="129.8715"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1153"
                                                                    d="M 130.08763020833334 183.348L 130.08763020833334 29.030100000000004L 137.99049479166666 29.030100000000004L 137.99049479166666 29.030100000000004L 137.99049479166666 183.348L 137.99049479166666 183.348z"
                                                                    fill="rgba(0,227,150,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="1" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 130.08763020833334 183.348L 130.08763020833334 29.030100000000004L 137.99049479166666 29.030100000000004L 137.99049479166666 29.030100000000004L 137.99049479166666 183.348L 137.99049479166666 183.348z"
                                                                    pathFrom="M 130.08763020833334 183.348L 130.08763020833334 183.348L 137.99049479166666 183.348L 137.99049479166666 183.348L 137.99049479166666 183.348L 130.08763020833334 183.348"
                                                                    cy="29.030100000000004" cx="183.10325520833334"
                                                                    j="2" val="101" barHeight="154.3179"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1154"
                                                                    d="M 184.10325520833334 183.348L 184.10325520833334 33.6138L 192.00611979166666 33.6138L 192.00611979166666 33.6138L 192.00611979166666 183.348L 192.00611979166666 183.348z"
                                                                    fill="rgba(0,227,150,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="1" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 184.10325520833334 183.348L 184.10325520833334 33.6138L 192.00611979166666 33.6138L 192.00611979166666 33.6138L 192.00611979166666 183.348L 192.00611979166666 183.348z"
                                                                    pathFrom="M 184.10325520833334 183.348L 184.10325520833334 183.348L 192.00611979166666 183.348L 192.00611979166666 183.348L 192.00611979166666 183.348L 184.10325520833334 183.348"
                                                                    cy="33.6138" cx="237.11888020833334" j="3" val="98"
                                                                    barHeight="149.73420000000002"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1155"
                                                                    d="M 238.11888020833334 183.348L 238.11888020833334 50.42070000000001L 246.02174479166666 50.42070000000001L 246.02174479166666 50.42070000000001L 246.02174479166666 183.348L 246.02174479166666 183.348z"
                                                                    fill="rgba(0,227,150,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="1" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 238.11888020833334 183.348L 238.11888020833334 50.42070000000001L 246.02174479166666 50.42070000000001L 246.02174479166666 50.42070000000001L 246.02174479166666 183.348L 246.02174479166666 183.348z"
                                                                    pathFrom="M 238.11888020833334 183.348L 238.11888020833334 183.348L 246.02174479166666 183.348L 246.02174479166666 183.348L 246.02174479166666 183.348L 238.11888020833334 183.348"
                                                                    cy="50.42070000000001" cx="291.13450520833334" j="4"
                                                                    val="87" barHeight="132.9273"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1156"
                                                                    d="M 292.13450520833334 183.348L 292.13450520833334 22.918500000000023L 300.03736979166666 22.918500000000023L 300.03736979166666 22.918500000000023L 300.03736979166666 183.348L 300.03736979166666 183.348z"
                                                                    fill="rgba(0,227,150,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="1" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 292.13450520833334 183.348L 292.13450520833334 22.918500000000023L 300.03736979166666 22.918500000000023L 300.03736979166666 22.918500000000023L 300.03736979166666 183.348L 300.03736979166666 183.348z"
                                                                    pathFrom="M 292.13450520833334 183.348L 292.13450520833334 183.348L 300.03736979166666 183.348L 300.03736979166666 183.348L 300.03736979166666 183.348L 292.13450520833334 183.348"
                                                                    cy="22.918500000000023" cx="345.15013020833334"
                                                                    j="5" val="105" barHeight="160.4295"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1157"
                                                                    d="M 346.15013020833334 183.348L 346.15013020833334 44.3091L 354.05299479166666 44.3091L 354.05299479166666 44.3091L 354.05299479166666 183.348L 354.05299479166666 183.348z"
                                                                    fill="rgba(0,227,150,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="1" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 346.15013020833334 183.348L 346.15013020833334 44.3091L 354.05299479166666 44.3091L 354.05299479166666 44.3091L 354.05299479166666 183.348L 354.05299479166666 183.348z"
                                                                    pathFrom="M 346.15013020833334 183.348L 346.15013020833334 183.348L 354.05299479166666 183.348L 354.05299479166666 183.348L 354.05299479166666 183.348L 346.15013020833334 183.348"
                                                                    cy="44.3091" cx="399.16575520833334" j="6" val="91"
                                                                    barHeight="139.0389" barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1158"
                                                                    d="M 400.16575520833334 183.348L 400.16575520833334 9.167400000000015L 408.06861979166666 9.167400000000015L 408.06861979166666 9.167400000000015L 408.06861979166666 183.348L 408.06861979166666 183.348z"
                                                                    fill="rgba(0,227,150,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="1" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 400.16575520833334 183.348L 400.16575520833334 9.167400000000015L 408.06861979166666 9.167400000000015L 408.06861979166666 9.167400000000015L 408.06861979166666 183.348L 408.06861979166666 183.348z"
                                                                    pathFrom="M 400.16575520833334 183.348L 400.16575520833334 183.348L 408.06861979166666 183.348L 408.06861979166666 183.348L 408.06861979166666 183.348L 400.16575520833334 183.348"
                                                                    cy="9.167400000000015" cx="453.18138020833334" j="7"
                                                                    val="114" barHeight="174.1806"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1159"
                                                                    d="M 454.18138020833334 183.348L 454.18138020833334 39.72540000000001L 462.08424479166666 39.72540000000001L 462.08424479166666 39.72540000000001L 462.08424479166666 183.348L 462.08424479166666 183.348z"
                                                                    fill="rgba(0,227,150,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="1" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 454.18138020833334 183.348L 454.18138020833334 39.72540000000001L 462.08424479166666 39.72540000000001L 462.08424479166666 39.72540000000001L 462.08424479166666 183.348L 462.08424479166666 183.348z"
                                                                    pathFrom="M 454.18138020833334 183.348L 454.18138020833334 183.348L 462.08424479166666 183.348L 462.08424479166666 183.348L 462.08424479166666 183.348L 454.18138020833334 183.348"
                                                                    cy="39.72540000000001" cx="507.19700520833334" j="8"
                                                                    val="94" barHeight="143.6226"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                            </g>
                                                            <g id="SvgjsG1160" class="apexcharts-series" rel="3"
                                                                seriesName="FreexCashxFlow" data:realIndex="2">
                                                                <path id="SvgjsPath1162"
                                                                    d="M 31.959244791666666 183.348L 31.959244791666666 129.87150000000003L 39.862109375 129.87150000000003L 39.862109375 129.87150000000003L 39.862109375 183.348L 39.862109375 183.348z"
                                                                    fill="rgba(254,176,25,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="2" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 31.959244791666666 183.348L 31.959244791666666 129.87150000000003L 39.862109375 129.87150000000003L 39.862109375 129.87150000000003L 39.862109375 183.348L 39.862109375 183.348z"
                                                                    pathFrom="M 31.959244791666666 183.348L 31.959244791666666 183.348L 39.862109375 183.348L 39.862109375 183.348L 39.862109375 183.348L 31.959244791666666 183.348"
                                                                    cy="129.87150000000003" cx="84.97486979166666" j="0"
                                                                    val="35" barHeight="53.4765"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1163"
                                                                    d="M 85.97486979166666 183.348L 85.97486979166666 120.70410000000001L 93.87773437499999 120.70410000000001L 93.87773437499999 120.70410000000001L 93.87773437499999 183.348L 93.87773437499999 183.348z"
                                                                    fill="rgba(254,176,25,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="2" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 85.97486979166666 183.348L 85.97486979166666 120.70410000000001L 93.87773437499999 120.70410000000001L 93.87773437499999 120.70410000000001L 93.87773437499999 183.348L 93.87773437499999 183.348z"
                                                                    pathFrom="M 85.97486979166666 183.348L 85.97486979166666 183.348L 93.87773437499999 183.348L 93.87773437499999 183.348L 93.87773437499999 183.348L 85.97486979166666 183.348"
                                                                    cy="120.70410000000001" cx="138.99049479166666"
                                                                    j="1" val="41" barHeight="62.6439"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1164"
                                                                    d="M 139.99049479166666 183.348L 139.99049479166666 128.3436L 147.893359375 128.3436L 147.893359375 128.3436L 147.893359375 183.348L 147.893359375 183.348z"
                                                                    fill="rgba(254,176,25,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="2" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 139.99049479166666 183.348L 139.99049479166666 128.3436L 147.893359375 128.3436L 147.893359375 128.3436L 147.893359375 183.348L 147.893359375 183.348z"
                                                                    pathFrom="M 139.99049479166666 183.348L 139.99049479166666 183.348L 147.893359375 183.348L 147.893359375 183.348L 147.893359375 183.348L 139.99049479166666 183.348"
                                                                    cy="128.3436" cx="193.00611979166666" j="2" val="36"
                                                                    barHeight="55.004400000000004"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1165"
                                                                    d="M 194.00611979166666 183.348L 194.00611979166666 143.6226L 201.908984375 143.6226L 201.908984375 143.6226L 201.908984375 183.348L 201.908984375 183.348z"
                                                                    fill="rgba(254,176,25,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="2" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 194.00611979166666 183.348L 194.00611979166666 143.6226L 201.908984375 143.6226L 201.908984375 143.6226L 201.908984375 183.348L 201.908984375 183.348z"
                                                                    pathFrom="M 194.00611979166666 183.348L 194.00611979166666 183.348L 201.908984375 183.348L 201.908984375 183.348L 201.908984375 183.348L 194.00611979166666 183.348"
                                                                    cy="143.6226" cx="247.02174479166666" j="3" val="26"
                                                                    barHeight="39.7254" barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1166"
                                                                    d="M 248.02174479166666 183.348L 248.02174479166666 114.59250000000002L 255.924609375 114.59250000000002L 255.924609375 114.59250000000002L 255.924609375 183.348L 255.924609375 183.348z"
                                                                    fill="rgba(254,176,25,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="2" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 248.02174479166666 183.348L 248.02174479166666 114.59250000000002L 255.924609375 114.59250000000002L 255.924609375 114.59250000000002L 255.924609375 183.348L 255.924609375 183.348z"
                                                                    pathFrom="M 248.02174479166666 183.348L 248.02174479166666 183.348L 255.924609375 183.348L 255.924609375 183.348L 255.924609375 183.348L 248.02174479166666 183.348"
                                                                    cy="114.59250000000002" cx="301.03736979166666"
                                                                    j="4" val="45" barHeight="68.7555"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1167"
                                                                    d="M 302.03736979166666 183.348L 302.03736979166666 110.00880000000001L 309.940234375 110.00880000000001L 309.940234375 110.00880000000001L 309.940234375 183.348L 309.940234375 183.348z"
                                                                    fill="rgba(254,176,25,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="2" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 302.03736979166666 183.348L 302.03736979166666 110.00880000000001L 309.940234375 110.00880000000001L 309.940234375 110.00880000000001L 309.940234375 183.348L 309.940234375 183.348z"
                                                                    pathFrom="M 302.03736979166666 183.348L 302.03736979166666 183.348L 309.940234375 183.348L 309.940234375 183.348L 309.940234375 183.348L 302.03736979166666 183.348"
                                                                    cy="110.00880000000001" cx="355.05299479166666"
                                                                    j="5" val="48" barHeight="73.3392"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1168"
                                                                    d="M 356.05299479166666 183.348L 356.05299479166666 103.89720000000001L 363.955859375 103.89720000000001L 363.955859375 103.89720000000001L 363.955859375 183.348L 363.955859375 183.348z"
                                                                    fill="rgba(254,176,25,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="2" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 356.05299479166666 183.348L 356.05299479166666 103.89720000000001L 363.955859375 103.89720000000001L 363.955859375 103.89720000000001L 363.955859375 183.348L 363.955859375 183.348z"
                                                                    pathFrom="M 356.05299479166666 183.348L 356.05299479166666 183.348L 363.955859375 183.348L 363.955859375 183.348L 363.955859375 183.348L 356.05299479166666 183.348"
                                                                    cy="103.89720000000001" cx="409.06861979166666"
                                                                    j="6" val="52" barHeight="79.4508"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1169"
                                                                    d="M 410.06861979166666 183.348L 410.06861979166666 102.36930000000001L 417.971484375 102.36930000000001L 417.971484375 102.36930000000001L 417.971484375 183.348L 417.971484375 183.348z"
                                                                    fill="rgba(254,176,25,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="2" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 410.06861979166666 183.348L 410.06861979166666 102.36930000000001L 417.971484375 102.36930000000001L 417.971484375 102.36930000000001L 417.971484375 183.348L 417.971484375 183.348z"
                                                                    pathFrom="M 410.06861979166666 183.348L 410.06861979166666 183.348L 417.971484375 183.348L 417.971484375 183.348L 417.971484375 183.348L 410.06861979166666 183.348"
                                                                    cy="102.36930000000001" cx="463.08424479166666"
                                                                    j="7" val="53" barHeight="80.9787"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                                <path id="SvgjsPath1170"
                                                                    d="M 464.08424479166666 183.348L 464.08424479166666 120.70410000000001L 471.987109375 120.70410000000001L 471.987109375 120.70410000000001L 471.987109375 183.348L 471.987109375 183.348z"
                                                                    fill="rgba(254,176,25,1)" fill-opacity="1"
                                                                    stroke="transparent" stroke-opacity="1"
                                                                    stroke-linecap="square" stroke-width="2"
                                                                    stroke-dasharray="0" class="apexcharts-bar-area"
                                                                    index="2" clip-path="url(#gridRectMask0bkyxry9)"
                                                                    pathTo="M 464.08424479166666 183.348L 464.08424479166666 120.70410000000001L 471.987109375 120.70410000000001L 471.987109375 120.70410000000001L 471.987109375 183.348L 471.987109375 183.348z"
                                                                    pathFrom="M 464.08424479166666 183.348L 464.08424479166666 183.348L 471.987109375 183.348L 471.987109375 183.348L 471.987109375 183.348L 464.08424479166666 183.348"
                                                                    cy="120.70410000000001" cx="517.0998697916667" j="8"
                                                                    val="41" barHeight="62.6439"
                                                                    barWidth="9.902864583333333"
                                                                    style="clip-path: inset(0% 0% -100% round 5px);">
                                                                </path>
                                                            </g>
                                                            <g id="SvgjsG1139" class="apexcharts-datalabels"
                                                                data:realIndex="0"></g>
                                                            <g id="SvgjsG1150" class="apexcharts-datalabels"
                                                                data:realIndex="1"></g>
                                                            <g id="SvgjsG1161" class="apexcharts-datalabels"
                                                                data:realIndex="2"></g>
                                                        </g>
                                                        <line id="SvgjsLine1235" x1="0" y1="0" x2="486.140625" y2="0"
                                                            stroke="#b6b6b6" stroke-dasharray="0" stroke-width="1"
                                                            class="apexcharts-ycrosshairs"></line>
                                                        <line id="SvgjsLine1236" x1="0" y1="0" x2="486.140625" y2="0"
                                                            stroke-dasharray="0" stroke-width="0"
                                                            class="apexcharts-ycrosshairs-hidden"></line>
                                                        <g id="SvgjsG1237" class="apexcharts-yaxis-annotations"></g>
                                                        <g id="SvgjsG1238" class="apexcharts-xaxis-annotations"></g>
                                                        <g id="SvgjsG1239" class="apexcharts-point-annotations"></g>
                                                    </g>
                                                    <g id="SvgjsG1201" class="apexcharts-yaxis" rel="0"
                                                        transform="translate(22.859375, 0)">
                                                        <g id="SvgjsG1202" class="apexcharts-yaxis-texts-g"><text
                                                                id="SvgjsText1203"
                                                                font-family="Helvetica, Arial, sans-serif" x="20"
                                                                y="31.4" text-anchor="end" dominant-baseline="auto"
                                                                font-size="11px" font-weight="400" fill="#9a9cab"
                                                                class="apexcharts-text apexcharts-yaxis-label "
                                                                style="font-family: Helvetica, Arial, sans-serif;">
                                                                <tspan id="SvgjsTspan1204">120</tspan>
                                                            </text><text id="SvgjsText1205"
                                                                font-family="Helvetica, Arial, sans-serif" x="20"
                                                                y="77.23700000000001" text-anchor="end"
                                                                dominant-baseline="auto" font-size="11px"
                                                                font-weight="400" fill="#9a9cab"
                                                                class="apexcharts-text apexcharts-yaxis-label "
                                                                style="font-family: Helvetica, Arial, sans-serif;">
                                                                <tspan id="SvgjsTspan1206">90</tspan>
                                                            </text><text id="SvgjsText1207"
                                                                font-family="Helvetica, Arial, sans-serif" x="20"
                                                                y="123.07400000000001" text-anchor="end"
                                                                dominant-baseline="auto" font-size="11px"
                                                                font-weight="400" fill="#9a9cab"
                                                                class="apexcharts-text apexcharts-yaxis-label "
                                                                style="font-family: Helvetica, Arial, sans-serif;">
                                                                <tspan id="SvgjsTspan1208">60</tspan>
                                                            </text><text id="SvgjsText1209"
                                                                font-family="Helvetica, Arial, sans-serif" x="20"
                                                                y="168.91100000000003" text-anchor="end"
                                                                dominant-baseline="auto" font-size="11px"
                                                                font-weight="400" fill="#9a9cab"
                                                                class="apexcharts-text apexcharts-yaxis-label "
                                                                style="font-family: Helvetica, Arial, sans-serif;">
                                                                <tspan id="SvgjsTspan1210">30</tspan>
                                                            </text><text id="SvgjsText1211"
                                                                font-family="Helvetica, Arial, sans-serif" x="20"
                                                                y="214.74800000000002" text-anchor="end"
                                                                dominant-baseline="auto" font-size="11px"
                                                                font-weight="400" fill="#9a9cab"
                                                                class="apexcharts-text apexcharts-yaxis-label "
                                                                style="font-family: Helvetica, Arial, sans-serif;">
                                                                <tspan id="SvgjsTspan1212">0</tspan>
                                                            </text></g>
                                                        <g id="SvgjsG1213" class="apexcharts-yaxis-title"><text
                                                                id="SvgjsText1214"
                                                                font-family="Helvetica, Arial, sans-serif"
                                                                x="22.69921875" y="121.674" text-anchor="end"
                                                                dominant-baseline="auto" font-size="11px"
                                                                font-weight="900" fill="#9a9cab"
                                                                class="apexcharts-text apexcharts-yaxis-title-text "
                                                                style="font-family: Helvetica, Arial, sans-serif;"
                                                                transform="rotate(-90 -13.359375 117.92400360107422)">$
                                                                (thousands)</text></g>
                                                    </g>
                                                    <g id="SvgjsG1126" class="apexcharts-annotations"></g>
                                                </svg>
                                                <div class="apexcharts-tooltip apexcharts-theme-light">
                                                    <div class="apexcharts-tooltip-title"
                                                        style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                    </div>
                                                    <div class="apexcharts-tooltip-series-group" style="order: 1;"><span
                                                            class="apexcharts-tooltip-marker"
                                                            style="background-color: rgb(0, 143, 251);"></span>
                                                        <div class="apexcharts-tooltip-text"
                                                            style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                            <div class="apexcharts-tooltip-y-group"><span
                                                                    class="apexcharts-tooltip-text-label"></span><span
                                                                    class="apexcharts-tooltip-text-value"></span></div>
                                                            <div class="apexcharts-tooltip-z-group"><span
                                                                    class="apexcharts-tooltip-text-z-label"></span><span
                                                                    class="apexcharts-tooltip-text-z-value"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="apexcharts-tooltip-series-group" style="order: 2;"><span
                                                            class="apexcharts-tooltip-marker"
                                                            style="background-color: rgb(0, 227, 150);"></span>
                                                        <div class="apexcharts-tooltip-text"
                                                            style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                            <div class="apexcharts-tooltip-y-group"><span
                                                                    class="apexcharts-tooltip-text-label"></span><span
                                                                    class="apexcharts-tooltip-text-value"></span></div>
                                                            <div class="apexcharts-tooltip-z-group"><span
                                                                    class="apexcharts-tooltip-text-z-label"></span><span
                                                                    class="apexcharts-tooltip-text-z-value"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="apexcharts-tooltip-series-group" style="order: 3;"><span
                                                            class="apexcharts-tooltip-marker"
                                                            style="background-color: rgb(254, 176, 25);"></span>
                                                        <div class="apexcharts-tooltip-text"
                                                            style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                            <div class="apexcharts-tooltip-y-group"><span
                                                                    class="apexcharts-tooltip-text-label"></span><span
                                                                    class="apexcharts-tooltip-text-value"></span></div>
                                                            <div class="apexcharts-tooltip-z-group"><span
                                                                    class="apexcharts-tooltip-text-z-label"></span><span
                                                                    class="apexcharts-tooltip-text-z-value"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="apexcharts-yaxistooltip apexcharts-yaxistooltip-0 apexcharts-yaxistooltip-left apexcharts-theme-light">
                                                    <div class="apexcharts-yaxistooltip-text"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="resize-triggers">
                                            <div class="expand-trigger">
                                                <div style="width: 600px; height: 335px;"></div>
                                            </div>
                                            <div class="contract-trigger"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="card stats-card">
                                            <div class="card-body">
                                                <div class="stats-info">
                                                    <h5 class="card-title">
                                                        $30K<span class="stats-change stats-change-danger">-8%</span>
                                                    </h5>
                                                    <p class="stats-text">Total revenue</p>
                                                </div>
                                                <div class="stats-icon change-danger">
                                                    <i class="material-icons">trending_down</i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card stats-card">
                                            <div class="card-body">
                                                <div class="stats-info">
                                                    <h5 class="card-title">
                                                        $21K<span class="stats-change stats-change-danger">-8%</span>
                                                    </h5>
                                                    <p class="stats-text">Total revenue</p>
                                                </div>
                                                <div class="stats-icon change-danger">
                                                    <i class="material-icons">trending_down</i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="card stats-card">
                                            <div class="card-body">
                                                <div class="stats-info">
                                                    <h5 class="card-title">
                                                        1681<span class="stats-change stats-change-success">+16%</span>
                                                    </h5>
                                                    <p class="stats-text">Unique visitors</p>
                                                </div>
                                                <div class="stats-icon change-success">
                                                    <i class="material-icons">trending_up</i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card stats-card">
                                            <div class="card-body">
                                                <div class="stats-info">
                                                    <h5 class="card-title">
                                                        4743<span class="stats-change stats-change-success">+12%</span>
                                                    </h5>
                                                    <p class="stats-text">Total investments</p>
                                                </div>
                                                <div class="stats-icon change-success">
                                                    <i class="material-icons">trending_up</i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card widget widget-info ">
                                    <div class="card-body">


                                        <!-- omnin -->

                                        <div class="card-body">




                                            <div class="row">
                                                <div class="table-responsive">
                                                    <table class="table invoice-table">
                                                        <thead>
                                                            <tr>
                                                                <!-- <th scope="col">#</th>
                    <th scope="col">Client</th>
                    <th scope="col">Issued Date</th>
                    <th scope="col">Total</th>
                    <th scope="col">Handle</th>
                    <th scope="col">Actions</th> -->
                                                                <th scope="col">ID</th>
                                                                <th scope="col">Name</th>
                                                                <th scope="col">Date</th>
                                                                <th scope="col">Phone</th>
                                                                <th scope="col">Phone</th>
                                                                <th scope="col"></th>

                                                            </tr>
                                                        </thead>


                                                        <tbody>

                                                            <?php
                                                            $sql = mysqli_query($conn, "SELECT * FROM orders1 WHERE orderstate = 'inprogress' OR orderstate = 'done' ORDER BY id DESC");

                                                            while ($fetchsql = mysqli_fetch_assoc($sql)) {
                                                                // Assign class based on the order state
                                                                if ($fetchsql['orderstate'] == 'done') {
                                                                    $class = 'bg-success';
                                                                } else {
                                                                    $class = 'bg-warning'; // Fixed typo from 'bg-waring' to 'bg-warning'
                                                                }

                                                                echo '<tr>

         <th scope="row">3311</th>
   
         

         
<td> ' . $fetchsql['name'] . '</td>
<td>' . $fetchsql['data'] . '</td>

<td><span   ><i class="' . $class . '"></i>
' . $fetchsql['orderstate'] . '</span></td>
<td>

<div class="d-flex align-items-center"><span class="me-2">' . $fetchsql['phoneone'] . '</span>
</div>
</td>
<td class="text-end">
<a href="./pags/order.php?id=' . $fetchsql['id'] . '" class="btn btn-sm btn-neutral"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg></a> 
<a href="indanger.php?id=' . $fetchsql['id'] . '"  class="btn btn-sm btn-neutral">

<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16"> <path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"></path> <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"></path> </svg>


</a>



<a href="done.php?id=' . $fetchsql['id'] . '"  class="btn btn-sm btn-neutral">

<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-all" viewBox="0 0 16 16">
  <path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0"/>
  <path d="m5.354 7.146.896.897-.707.707-.897-.896a.5.5 0 1 1 .708-.708"/>
</svg>

</a>
</td>
</tr>';
                                                            }
                                                            ?>



                                                        </tbody>


                                                    </table>
                                                </div>
                                            </div>
                                            <div class="row invoice-last">
                                                <div class="col-9">
                                                    <p>
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                                                        Fusce ut ante id elit molestie<br>dapibus id
                                                        sollicitudin vel, luctus sit amet justo
                                                    </p>
                                                </div>


                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>


                </div>


                <!-- 2222222222222222222222222222222222222222 -->




                <div class="User_Order  Div_Hid card-body" id="div3">
                    <div class="row">
                        <div class="table-responsive">
                            <!-- Input for searching -->
                            <div class="mb-3">
                                <input type="text" id="searchInput" class="form-control"
                                    placeholder=" Search From Name  ">
                            </div>
                            <table class="table invoice-table">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Img / Name</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">discount</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody id="productTableBody">
                                    <?php
                                    $getproductstodelete = mysqli_query($conn, "SELECT * FROM `products`");
                                    while ($fetctproducttodelete = mysqli_fetch_assoc($getproductstodelete)) {
                                        echo '
                            <tr>
                                <th scope="row">3311</th>
                                <td>
                                    <img style="object-fit: contain;" src="' . $fetctproducttodelete['img'] . '" alt="">
                                    ' . $fetctproducttodelete['name'] . '
                                </td>
                                <td>' . $fetctproducttodelete['total_final_price'] . '</td>
                                <td><span><i class="bg-success"></i> ' . $fetctproducttodelete['discount'] . ' %</span></td>
                                <td class="text-end">
                                    <a href="?id=' . $fetctproducttodelete['id'] . '" class="btn btn-sm btn-neutral">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16">
                                            <path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"/>
                                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                                        </svg>
                                    </a>
                                    <a href="edit.php?id=' . $fetctproducttodelete['id'] . '" class="btn btn-sm btn-neutral">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bezier" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M0 10.5A1.5 1.5 0 0 1 1.5 9h1A1.5 1.5 0 0 1 4 10.5v1A1.5 1.5 0 0 1 2.5 13h-1A1.5 1.5 0 0 1 0 11.5zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm10.5.5A1.5 1.5 0 0 1 13.5 9h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM6 4.5A1.5 1.5 0 0 1 7.5 3h1A1.5 1.5 0 0 1 10 4.5v1A1.5 1.5 0 0 1 8.5 7h-1A1.5 1.5 0 0 1 6 5.5zM7.5 4a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z"/>
                                            <path d="M6 4.5H1.866a1 1 0 1 0 0 1h2.668A6.52 6.52 0 0 0 1.814 9H2.5q.186 0 .358.043a5.52 5.52 0 0 1 3.185-3.185A1.5 1.5 0 0 1 6 5.5zm3.957 1.358A1.5 1.5 0 0 0 10 5.5v-1h4.134a1 1 0 1 1 0 1h-2.668a6.52 6.52 0 0 1 2.72 3.5H13.5q-.185 0-.358.043a5.52 5.52 0 0 0-3.185-3.185"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        ';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <script>
                    document.getElementById('searchInput').addEventListener('input', function () {
                        const filter = this.value.toLowerCase();
                        const rows = document.querySelectorAll('#productTableBody tr');
                        rows.forEach(row => {
                            const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                            row.style.display = name.includes(filter) ? '' : 'none';
                        });
                    });
                </script>




                <!-- 2222222222222222222222222 -->





                <!-- 2222222222222222222222222next -->

                <div class="Div_Hid" id="div4">
                    <div class="main-wrapper">



                        <div class="row">
                            <div class="col">
                                <div class="card">
                                    <div class="card-body">




                                        <h5 class="card-title">Validation</h5>
                                        <p>
                                            Provide valuable, actionable feedback to your users with
                                            HTML5 form validation.
                                        </p>
                                        <form class="row g-3 needs-validation" action="./assets/page/addproduct.php"
                                            method="POST" enctype="multipart/form-data"
                                            onsubmit="return validateForm()">

                                            <div class="col-md-4">
                                                <label for="validationCustom01" class="form-label">Product name
                                                </label>
                                                <!-- edit -->

                                                <input type="text" id="sku" name="sku" placeholder="Product name"
                                                    class="form-control" required>


                                            </div>

                                            <div class="col-md-4">
                                                <label for="validationCustom02" class="form-label">price</label>
                                                <input type="text" id="price" name="price" placeholder="price"
                                                    class="form-control" required>


                                            </div>
                                            <div class="col-md-4">
                                                <label for="validationCustomUsername" class="form-label">discount by %
                                                    (optional)</label>
                                                <div class="input-group has-validation">
                                                    <span class="input-group-text" id="inputGroupPrepend">@</span>

                                                    <input type="text" id="discount" name="discount"
                                                        class="form-control" placeholder="discount by %">

                                                    <div class="invalid-feedback">
                                                        Please choose a username.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="validationCustom03" class="form-label">description</label>
                                                <input type="text" id="barcode-type" name="barcode-type"
                                                    class="form-control" placeholder="description" required>


                                                <div class="invalid-feedback">
                                                    Please provide a valid city.
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="validationCustom04" class="form-label">select Catgory
                                                </label>
                                                <!--  -->

                                                <select id="unit" name="unit" class="form-select" required>
                                                    <option value="">select Catgory</option>
                                                    <?php
                                                    $sql = mysqli_query($conn, "SELECT * FROM catageories");
                                                    while ($fetchcat = mysqli_fetch_assoc($sql)) {
                                                        echo "<option value='" . $fetchcat['id'] . "'>" . $fetchcat['name'] . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <input type="hidden" name="cattype" id="inputoption">


                                                <!--  -->




                                                <div class="invalid-feedback">
                                                    Please select a valid state.
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="validationCustom05" class="form-label">img cover</label>
                                                <input type="file" id="img" name="img" placeholder="img"
                                                    class="form-control" required>

                                                <div class="invalid-feedback">
                                                    Please provide a valid zip.
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                        id="invalidCheck" required="">
                                                    <label class="form-check-label" for="invalidCheck">
                                                        Agree to terms and conditions
                                                    </label>
                                                    <div class="invalid-feedback">
                                                        You must agree before submitting.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">


                                                <button type="submit" class="up_lo btn btn-secondary">Submit
                                                    form</button>
                                            </div>
                                        </form>





                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>

                <!-- meroooo -->



                <!-- meroooo -->

                <!-- 2222222222222222222222222next -->






                <section class="Div_Hid card-body" id="div5">
                    <div class="Add_New_Catageories">
                        <div class="invoice-details">
                            <div class="row">
                                <div class="col">
                                    <p class="info">Date:</p>
                                    <p>Jan 8, 2021</p>
                                </div>
                                <div class="col">
                                    <p class="info">ID:</p>
                                    <p>IO237</p>
                                </div>
                                <div class="col">
                                    <p class="info">Invoice to:</p>
                                    <p>John Doe, New York</p>
                                    <p>5025 Collwood Blvd, apt. 2314</p>
                                </div>
                            </div>
                        </div>


                        <div class="row">
                            <div class="table-responsive">
                                <div class="row">
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <div class="col-md-4">
                                            <label class="form-label">Category Name</label>
                                            <input type="text" name="name" class="form-control" required>
                                            <br>

                                            <label class="form-label">Category Image</label>
                                            <input type="file" name="image" class="form-control" accept="image/*"
                                                required>
                                            <br>

                                            <button type="submit" class="up_lo btn btn-secondary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-node-plus" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd"
                                                        d="M11 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8M6.025 7.5a5 5 0 1 1 0 1H4A1.5 1.5 0 0 1 2.5 10h-1A1.5 1.5 0 0 1 0 8.5v-1A1.5 1.5 0 0 1 1.5 6h1A1.5 1.5 0 0 1 4 7.5zM11 5a.5.5 0 0 1 .5.5v2h2a.5.5 0 0 1 0 1h-2v2a.5.5 0 0 1-1 0v-2h-2a.5.5 0 0 1 0-1h2v-2A.5.5 0 0 1 11 5M1.5 7a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <table class="table invoice-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Image</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = mysqli_query($conn, "SELECT * FROM catageories ORDER BY id DESC");
                                        while ($fetch = mysqli_fetch_assoc($sql)) {
                                            $id = $fetch['id'];
                                            $name = htmlspecialchars($fetch['name'] ?? '');
                                            $image = htmlspecialchars($fetch['image'] ?? '');
                                            echo "
                    <tr>
                        <th scope='row'>{$id}</th>
                        <td>{$name}</td>
                        <td><img src='{$image}' width='50' height='50' alt='Category Image'></td>
                        <td class='text-end'>
                            <form action='' method='POST'>
                                <input type='hidden' name='id' value='{$id}'>
                                <button type='submit' class='btn btn-sm btn-danger'>
                                    <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor'
                                        class='bi bi-x-octagon' viewBox='0 0 16 16'>
                                        <path d='M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353z'/>
                                        <path d='M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708'/>
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>


                    </div>
                </section>





                <section class="Div_Hid" id="div6">




                    <div class="Add New Ads">





                        <div class="card-body">


                            <div class="invoice-details">
                                <div class="row">
                                    <div class="col">
                                        <p class="info">Date:</p>
                                        <p>Jan 8, 2021</p>
                                    </div>
                                    <div class="col">
                                        <p class="info">ID:</p>
                                        <p>IO237</p>
                                    </div>
                                    <div class="col">
                                        <p class="info">Invoice to:</p>
                                        <p>John Doe, New York</p>
                                        <p>5025 Collwood Blvd, apt. 2314</p>
                                    </div>
                                </div>


                                <div class="row">

                                    <form method="POST" action=" " enctype="multipart/form-data">



                                        <div id="table_body">

                                        </div>



                                        <div class="_block">
                                            <button type="submit" class="up_lo btn btn-secondary up_lo">Submit
                                                form</button>


                                            <button type="button" onclick="create_tr('table_body')"
                                                class="up_lo btn btn-secondary up_lo"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-node-plus" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd"
                                                        d="M11 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8M6.025 7.5a5 5 0 1 1 0 1H4A1.5 1.5 0 0 1 2.5 10h-1A1.5 1.5 0 0 1 0 8.5v-1A1.5 1.5 0 0 1 1.5 6h1A1.5 1.5 0 0 1 4 7.5zM11 5a.5.5 0 0 1 .5.5v2h2a.5.5 0 0 1 0 1h-2v2a.5.5 0 0 1-1 0v-2h-2a.5.5 0 0 1 0-1h2v-2A.5.5 0 0 1 11 5M1.5 7a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z" />
                                                </svg></button>
                                            <button type="button" onclick="edit_all()"
                                                class="up_lo btn btn-secondary up_lo"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-bezier" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd"
                                                        d="M0 10.5A1.5 1.5 0 0 1 1.5 9h1A1.5 1.5 0 0 1 4 10.5v1A1.5 1.5 0 0 1 2.5 13h-1A1.5 1.5 0 0 1 0 11.5zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm10.5.5A1.5 1.5 0 0 1 13.5 9h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM6 4.5A1.5 1.5 0 0 1 7.5 3h1A1.5 1.5 0 0 1 10 4.5v1A1.5 1.5 0 0 1 8.5 7h-1A1.5 1.5 0 0 1 6 5.5zM7.5 4a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5z" />
                                                    <path
                                                        d="M6 4.5H1.866a1 1 0 1 0 0 1h2.668A6.52 6.52 0 0 0 1.814 9H2.5q.186 0 .358.043a5.52 5.52 0 0 1 3.185-3.185A1.5 1.5 0 0 1 6 5.5zm3.957 1.358A1.5 1.5 0 0 0 10 5.5v-1h4.134a1 1 0 1 1 0 1h-2.668a6.52 6.52 0 0 1 2.72 3.5H13.5q-.185 0-.358.043a5.52 5.52 0 0 0-3.185-3.185" />
                                                </svg></button>
                                            <button type="button" onclick="save_all()"
                                                class="up_lo btn btn-secondary up_lo"><svg
                                                    xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-floppy" viewBox="0 0 16 16">
                                                    <path d="M11 2H9v3h2z" />
                                                    <path
                                                        d="M1.5 0h11.586a1.5 1.5 0 0 1 1.06.44l1.415 1.414A1.5 1.5 0 0 1 16 2.914V14.5a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 14.5v-13A1.5 1.5 0 0 1 1.5 0M1 1.5v13a.5.5 0 0 0 .5.5H2v-4.5A1.5 1.5 0 0 1 3.5 9h9a1.5 1.5 0 0 1 1.5 1.5V15h.5a.5.5 0 0 0 .5-.5V2.914a.5.5 0 0 0-.146-.353l-1.415-1.415A.5.5 0 0 0 13.086 1H13v4.5A1.5 1.5 0 0 1 11.5 7h-7A1.5 1.5 0 0 1 3 5.5V1H1.5a.5.5 0 0 0-.5.5m3 4a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5V1H4zM3 15h10v-4.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5z" />
                                                </svg></button>




                                        </div>












                                    </form>

                                </div>



                            </div>
                            <div class="row">
                                <div class="table-responsive">
                                    <table class="table invoice-table">
                                        <thead>
                                            <tr>

                                                <th scope="col">Category name
                                                </th>
                                                <th scope="col">Upload photos
                                                </th>
                                                <th scope="col">Product link
                                                </th>
                                                <th scope="col"></th>
                                            </tr>
                                        </thead>


                                        <tbody>



                                            <?php
                                            // Fetch all ads from the database
                                            $sql = mysqli_query($conn, "SELECT * FROM ads");

                                            while ($fetchsql = mysqli_fetch_assoc($sql)) {
                                                // Assuming $categoryid comes from the 'ads' table, fetching it from the row
                                                $catid = $fetchsql['categoryid'];

                                                // Fetch the category name based on the category ID
                                                $sqlcar = mysqli_query($conn, "SELECT * FROM catageories WHERE id = '$catid'");

                                                // Fetch the category result
                                                $fetchid = mysqli_fetch_assoc($sqlcar);

                                                // Output the HTML with data from both the ads and category tables
                                                echo '



                                                    <tr>


                                                         <th scope="row">   ' . $fetchid['name'] . '</th>
                                                         
      <td>
                                              
      
        <div class="star goldstar" style="background-image: url(' . $fetchsql['photo'] . ');"></div>
                                            </td>


                                                           <td>            <a href="' . $fetchsql['linkaddress'] . '">' . $fetchsql['linkaddress'] . '</a></td>




                                                                                                       <th scope="row">                                                           <a href="./deletead.php?ids=' . $fetchsql['id'] . '" type="button" class="btn btn-sm btn-square btn-neutral text-danger-hover">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16">
  <path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"></path>
  <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"></path>
</svg>
        </a></th>


            
                                        </tr>
     

 
                                                           ';
                                            }
                                            ?>
















                                        </tbody>


                                    </table>
                                </div>
                            </div>


                        </div>



                        <?php
                        // Generate the category options in PHP
                        $options = '';
                        $mysql = mysqli_query($conn, "SELECT * FROM `catageories`");
                        while ($fetch = mysqli_fetch_assoc($mysql)) {
                            $options .= '<option value="' . $fetch['id'] . '">' . $fetch['name'] . '</option>';
                        }
                        ?>
                        <!-- Hidden container to store the PHP-generated options -->
                        <div id="category_options" style="display: none;">
                            <?php echo $options; ?>
                        </div>





                        <script>
                            let inputCounter = 0;

                            function create_tr(table_id) {
                                inputCounter++;
                                const table_body = document.getElementById(table_id);

                                // Get the PHP-generated options from the hidden container
                                const categoryOptions = document.getElementById('category_options').innerHTML;

                                // Use template literal to create the new row, injecting the category options
                                const new_row = `

                               



            <div class="row g-3 needs-validation">
             <div class="col-md-3">



                                            <label for="validationCustom04" class="form-label">select Catgory
                                            </label>

                                            <select   name="category[]"     class="form-select" required="" placeholder="Enter category">

                                                  ${categoryOptions}

                                                                                    </select>

                                                                                    

                                   


                                      
                                                                                    
                                        </div>





                             


                                        



                <div class="col-md-3">
                                            <label for="validationCustom05" class="form-label">img cover</label>
                                            <input type="file" name="photo[]"  placeholder="Enter phone" class="form-control" required="">

                                            
                                        </div>




                                        

                <div class="col-md-5">
                                            <label for="validationCustom02" class="form-label">price</label>
                                            <input type="text" id="price"  name="linkaddress[]"  placeholder="Enter address"  class="form-control" required="">


                                            </div>



                                              
                                             


                                            


                                                    <button onclick="remove_tr(this)"   type="button"  class=" col-md-1 btn btn-sm btn-square btn-neutral text-danger-hover danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                           

                                      





                                      


            </div>
        `;

                                // Add the new row to the table body
                                table_body.innerHTML += new_row;
                            }

                            function remove_tr(button) {
                                button.parentElement.remove();
                            }

                            function edit_all() {
                                document.querySelectorAll("#table_body .grid__table input[type='text']").forEach(input => {
                                    input.removeAttribute("readonly");
                                });
                                document.querySelectorAll("#table_body .grid__table select, #table_body .grid__table input[type='file']").forEach(input => {
                                    input.removeAttribute("disabled");
                                });
                            }

                            function save_all() {
                                document.querySelectorAll("#table_body .grid__table input[type='text']").forEach(input => {
                                    input.setAttribute("readonly", "true");
                                });
                                document.querySelectorAll("#table_body .grid__table select, #table_body .grid__table input[type='file']").forEach(input => {
                                    input.setAttribute("disabled", "true");
                                });
                            }

                            // Make fields non-editable on page load
                            document.addEventListener('DOMContentLoaded', () => {
                                document.querySelectorAll("#table_body .grid__table input[type='text']").forEach(input => {
                                    input.setAttribute("readonly", "true");
                                });
                                document.querySelectorAll("#table_body .grid__table select, #table_body .grid__table input[type='file']").forEach(input => {
                                    input.setAttribute("disabled", "true");
                                });
                            });
                        </script>


                    </div>
                </section>




                <section class="Div_Hid" id="div7">


                    <div class="scting  ">
                        <div class="card">

                            <div class="card-header  flex">

                                <p>Last order</p>

                                <div class="_colo_r">



                                    <button class="up_lo btn btn-secondary" onclick="toggleSection('section1')"><svg
                                            xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-three-dots" viewBox="0 0 16 16">
                                            <path
                                                d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3" />
                                        </svg></button>


                                    <button class="up_lo btn btn-secondary" onclick="toggleSection('section2')"><svg
                                            xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-check2-all" viewBox="0 0 16 16">
                                            <path
                                                d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0" />
                                            <path d="m5.354 7.146.896.897-.707.707-.897-.896a.5.5 0 1 1 .708-.708" />
                                        </svg>
                                    </button>

                                    <button class="up_lo btn btn-secondary"
                                        onclick="toggleSection('section3')">inprogress </button>

                                    <button class="up_lo btn btn-secondary" onclick="toggleSection('section4')">I dont
                                        agree </button>








                                </div>
                            </div>



                            <div class="color_x">


                                <div id="section1" class="Hidden_Section">


                                    <div class="row">
                                        <div class="table-responsive">
                                            <table class="table invoice-table">
                                                <thead>
                                                    <tr>


                                                        <th scope="col">ID</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Date</th>
                                                        <th scope="col">Phone</th>
                                                        <th scope="col">Phone</th>
                                                        <th scope="col"></th>
                                                        <th scope="col"></th>

                                                    </tr>
                                                </thead>


                                                <tbody>





                                                    <?php
                                                    $sql = mysqli_query($conn, "SELECT * FROM orders1 WHERE orderstate = 'inprogress' OR orderstate = 'done' ORDER BY id DESC");

                                                    while ($fetchsql = mysqli_fetch_assoc($sql)) {
                                                        // Assign class based on the order state
                                                        if ($fetchsql['orderstate'] == 'done') {
                                                            $class = 'bg-success';
                                                        } else {
                                                            $class = 'bg-warning'; // Fixed typo from 'bg-waring' to 'bg-warning'
                                                        }

                                                        echo '<tr>

                                                                                                                <th scope="row">1</th>


        <td> <a class="text-heading font-semibold" href="#">' . $fetchsql['name'] . '</a></td>
        <td>' . $fetchsql['data'] . '</td>



             <td><i class="' . $class . '"></i>
            ' . $fetchsql['orderstate'] . '</span>

                </td>
                                                        <td>


                                                        



        <td>
            <div class="d-flex align-items-center"><span class="me-2">' . $fetchsql['phoneone'] . '</span>
            </div>
        </td>
        <td class="text-end">
            <a href="./pags/order.php?id=' . $fetchsql['id'] . '"   class="btn btn-sm btn-neutral" ><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye">
                                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                                    <circle cx="12" cy="12" r="3"></circle>
                                                                </svg></a> 
            <a href="indanger.php?id=' . $fetchsql['id'] . '"   class="btn btn-sm btn-neutral">
            
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16">
                                                                    <path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"></path>
                                                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"></path>
                                                                </svg>
            </a>
            <a href="done.php?id=' . $fetchsql['id'] . '"  class="btn btn-sm btn-neutral">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-all" viewBox="0 0 16 16">
                                                                    <path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0"></path>
                                                                    <path d="m5.354 7.146.896.897-.707.707-.897-.896a.5.5 0 1 1 .708-.708"></path>
                                                                </svg>
            </a>
        </td>
    </tr>';
                                                    }
                                                    ?>




                                                </tbody>


                                            </table>
                                        </div>
                                    </div>






                                </div>

                                <div id="section2" class="Hidden_Section">

                                    <div class="row">
                                        <div class="table-responsive">
                                            <table class="table invoice-table">
                                                <thead>
                                                    <tr>


                                                        <th scope="col">Name</th>
                                                        <th scope="col">Date</th>
                                                        <th scope="col">Phone</th>


                                                    </tr>
                                                </thead>


                                                <tbody>




                                                    <?php

                                                    $sql = mysqli_query($conn, "SELECT * FROM orders1 WHERE orderstate = 'done' ORDER BY id DESC");


                                                    while ($fetchsql = mysqli_fetch_assoc($sql)) {
                                                        echo '<tr>
                             <td> <a class="text-heading font-semibold" href="#">' . $fetchsql['name'] . '</a></td>
                             <td>' . $fetchsql['data'] . '</td>
                             <td><span class="badge badge-lg badge-dot"><i class="bg-success"></i>' . $fetchsql['orderstate'] . '</span>

                             <td>
                                 <div class="d-flex align-items-center"><span class="me-2">' . $fetchsql['phoneone'] . '</span>

                                 </div>
                             </td>
                             <td class="text-end"><a href="./pags/order.php?id=' . $fetchsql['id'] . '"
                                     class="btn btn-sm btn-neutral">View</a>
                                    <a href="indanger.php?id=' . $fetchsql['id'] . '"><button type="button" class="btn btn-sm btn-square btn-neutral text-danger-hover"><i class="bi bi-trash"></i></button></td>
                         </tr>';
                                                    }
                                                    ?>



                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>

                                <div id="section3" class="Hidden_Section">
                                    <div class="row">
                                        <div class="table-responsive">
                                            <table class="table invoice-table">
                                                <thead>
                                                    <tr>


                                                        <th scope="col">ID</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Date</th>
                                                        <th scope="col">Phone</th>
                                                        <th scope="col">Phone</th>


                                                    </tr>
                                                </thead>


                                                <tbody>

                                                    <?php

                                                    $sql = mysqli_query($conn, "SELECT * FROM orders1 WHERE orderstate = 'inprogress'   ORDER BY id DESC");


                                                    while ($fetchsql = mysqli_fetch_assoc($sql)) {
                                                        echo '<tr>
                             <td> <a class="text-heading font-semibold" href="#">' . $fetchsql['name'] . '</a></td>
                             <td>' . $fetchsql['data'] . '</td>
                             <td><span class="badge badge-lg badge-dot"><i class="bg-warning"></i>
                                   ' . $fetchsql['orderstate'] . '  </span></td>

                             <td>
                                 <div class="d-flex align-items-center"><span class="me-2">' . $fetchsql['phoneone'] . '</span>

                                 </div>
                             </td>
                             <td class="text-end"><a href="./pags/order.php?id=' . $fetchsql['id'] . '"
                                     class="btn btn-sm btn-neutral">View</a> 
                                         <a href="indanger.php?id=' . $fetchsql['id'] . '"><button type="button" class="btn btn-sm btn-square btn-neutral text-danger-hover"><i class="bi bi-trash"></i></button>
                                         <a href="done.php?id=' . $fetchsql['id'] . '"><button type="button" class="btn btn-sm btn-square btn-neutral text-danger-hover"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-all" viewBox="0 0 16 16">
                                                <path d="M8.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L2.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093L8.95 4.992zm-.92 5.14.92.92a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 1 0-1.091-1.028L9.477 9.417l-.485-.486z"></path>
                                            </svg></button></td>
                         </tr>';
                                                    }
                                                    ?>

                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>

                                <div id="section4" class="Hidden_Section">
                                    <div class="row">
                                        <div class="table-responsive">
                                            <table class="table invoice-table">
                                                <thead>
                                                    <tr>


                                                        <th scope="col">ID</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Date</th>
                                                        <th scope="col">Phone</th>
                                                        <th scope="col">Phone</th>
                                                        <th scope="col"></th>
                                                        <th scope="col"></th>

                                                    </tr>
                                                </thead>


                                                <tbody>
                                                    <?php

                                                    $sql = mysqli_query($conn, "SELECT * FROM orders1 WHERE orderstate = ' I dont agree'   ORDER BY id DESC");


                                                    while ($fetchsql = mysqli_fetch_assoc($sql)) {
                                                        echo '<tr>
                             <td> <a class="text-heading font-semibold" href="#">' . $fetchsql['name'] . '</a></td>
                             <td>' . $fetchsql['data'] . '</td>
                             <td><span class="badge badge-lg badge-dot"><i class="bg-danger"></i>
                                            ' . $fetchsql['orderstate'] . '</span></td>
                             

                             <td>
                                 <div class="d-flex align-items-center"><span class="me-2">' . $fetchsql['phoneone'] . '</span>

                                 </div>
                             </td>
                             <td class="text-end"><a href="./pags/order.php?id=' . $fetchsql['id'] . '"
                                     class="btn btn-sm btn-neutral">View</a>
                                       <a href="indanger.php?id=' . $fetchsql['id'] . '"><button type="button" class="btn btn-sm btn-square btn-neutral text-danger-hover"><i class="bi bi-trash"></i></button>
                                      <a href="done.php?id=' . $fetchsql['id'] . '"><button type="button" class="btn btn-sm btn-square btn-neutral text-danger-hover"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-all" viewBox="0 0 16 16">
                                                <path d="M8.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L2.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093L8.95 4.992zm-.92 5.14.92.92a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 1 0-1.091-1.028L9.477 9.417l-.485-.486z"></path>
                                            </svg></button></td>
                         </tr>';
                                                    }
                                                    ?>



                                                </tbody>
                                            </table>
                                        </div>



                                    </div>

                                </div>
                            </div>







                </section>









































                <script>
                    // Update hidden input for category selection
                    let unitSelect = document.getElementById('unit');
                    let inputoption = document.getElementById('inputoption');
                    unitSelect.addEventListener('change', function () {
                        inputoption.value = unitSelect.value;
                    });

                    // Validate form before submission
                    function validateForm() {
                        let sku = document.getElementById('sku').value.trim();
                        let description = document.getElementById('barcode-type').value.trim();
                        let price = document.getElementById('price').value.trim();
                        let category = document.getElementById('unit').value.trim();
                        let img = document.getElementById('img').value.trim();

                        if (!sku || !description || !price || !category || !img) {
                            alert("Please fill in all required fields.");
                            return false;
                        }

                        return true;
                    }
                </script>

























                <style>
                    .Hidden_Section {
                        display: none;


                    }
                </style>



                <script src="./app/script.js"></script>





                <!-- script -->
                <script src="./app/jquery-3.4.1.min.js"></script>
                <script src="https://unpkg.com/@popperjs/core@2"></script>
                <script src="https://unpkg.com/feather-icons"></script>
                <script src="./app/jquery.sparkline.min.js"></script>
                <script src="./app/perfect-scrollbar.min.js"></script>
                <script src="./app/pace.min.js"></script>
                <script src="./app/apexcharts.min.js"></script>



                <script src="./app/main.min.js"></script>
                <script src="./app/dashboard.js"></script>
                <script src="./app/script.js"></script>


                <!-- edit -->





                <style>
                    .Div_Hid {
                        display: none;

                    }
                </style>







                <script>
                    // عند تحميل الصفحة، يتم عرض القسم المحفوظ في localStorage أو الأول كافتراضي
                    window.onload = function () {
                        var sections = document.getElementsByClassName("Hidden_Section");
                        var activeSection = localStorage.getItem("activeSection") || "section1"; // استرجاع القسم المحفوظ من localStorage
                        for (var i = 0; i < sections.length; i++) {
                            sections[i].style.display = "none"; // إخفاء جميع الأقسام
                        }
                        document.getElementById(activeSection).style.display = "block"; // عرض القسم المحفوظ
                    };

                    // تغيير عرض الأقسام عند الضغط على الزر وحفظ الحالة في localStorage
                    function toggleSection(sectionId) {
                        var sections = document.getElementsByClassName("Hidden_Section");
                        for (var i = 0; i < sections.length; i++) {
                            sections[i].style.display = "none"; // إخفاء جميع الأقسام
                        }

                        document.getElementById(sectionId).style.display = "block"; // عرض القسم الذي تم الضغط عليه
                        localStorage.setItem("activeSection", sectionId); // حفظ القسم المفتوح في localStorage
                    }
                </script>



                <script>
                    // عند تحميل الصفحة، يتم عرض القسم المحفوظ في localStorage أو الأول كافتراضي
                    window.onload = function () {
                        var elements = document.getElementsByClassName("Div_Hid");
                        var savedElement = localStorage.getItem("openDiv") || "div1"; // استرجاع القسم المحفوظ من localStorage
                        for (var i = 0; i < elements.length; i++) {
                            elements[i].style.display = "none"; // إخفاء جميع الأقسام
                        }
                        document.getElementById(savedElement).style.display = "block"; // عرض القسم المحفوظ
                    };

                    // تغيير عرض الأقسام عند الضغط على الزر وحفظ الحالة في localStorage
                    function changeElement(elementId) {
                        var elements = document.getElementsByClassName("Div_Hid");
                        for (var i = 0; i < elements.length; i++) {
                            elements[i].style.display = "none"; // إخفاء جميع الأقسام
                        }

                        document.getElementById(elementId).style.display = "block"; // عرض القسم الذي تم الضغط عليه
                        localStorage.setItem("openDiv", elementId); // حفظ القسم المفتوح في localStorage
                    }
                </script>



                <style>
                    .row.g-3.needs-validation {
                        margin: 20px auto;
                    }

                    .star.goldstar {
                        width: 35px;
                        height: 35px;
                        border-radius: 3px;
                        background-size: cover;
                    }

                    input[type="file"]::file-selector-button {
                        visibility: hidden;
                    }

                    input#img {
                        color: #6c757d !important;
                        font-size: small;
                    }


                    .flex_Div {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        width: 100%;
                    }

                    /* div#div4 {
                        display: flex !important;
                        align-items: center;
                        justify-content: center;
                        height: 100vh !important;
                    } */


                    @media screen and (max-width:992px) {

                        .invoice-details .row {
                            display: block;
                        }
                    }

                    button.up_lo.btn.btn-secondary.up_lo {
                        background: none;
                        color: #262635;
                        border: 1px solid;
                    }

                    button.up_lo.btn.btn-secondary.up_lo:hover {
                        background: none;
                        box-shadow: none !important;
                    }
                </style>

    </body>

</html>