<?php
require('./db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = password_hash($_POST['password'] ?? '', PASSWORD_BCRYPT);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');

    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $targetDir = 'uploads/';
        $filename = time() . '_' . basename($_FILES['profile_image']['name']);
        $profile_image = $targetDir . $filename;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image);
    }

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, city, country, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $email, $password, $phone, $address, $city, $country, $profile_image);
    $stmt->execute();
    $stmt->close();

    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login | ARTSY</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../style/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        .main-content {
            font-family: Arial, sans-serif;


        }



        .floating-label-group {
            position: relative;
            margin-bottom: 25px;
        }

        .floating-label-group input {
            width: 100%;
            padding: 14px 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 18px;
            outline: none;
        }

        .floating-label-group input:focus {
            border: 1px solid #ccc;
            box-shadow: none;
        }

        .floating-label-group label {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            background-color: transparent;
            padding: 0 5px;
            color: #888;
            pointer-events: none;
            transition: 0.2s ease all;
        }

        .floating-label-group input:focus+label,
        .floating-label-group input:not(:placeholder-shown)+label {
            top: 0px;
            left: 10px;
            font-size: 12px;
            color: #222;
            background: white;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            cursor: pointer;
        }

        .input-group-custom {
            position: relative;
        }

        .btn-login {
            background-color: #222;
            color: #fff;
            border-radius: 10px;
            padding: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .btn-login:hover {
            background-color: #000;
        }

        .form-text-link {
            font-size: 0.875rem;
            text-align: right;
            margin-top: 5px;
        }

        .create-account {
            margin-top: 15px;
            font-size: 0.9rem;
        }

        a {
            color: black;
        }

        .floating-label-group input {
            padding: 27px !important;
        }

        h2.mb-2 {
            font-size: 36px;
            font-weight: normal;
        }

        p.text-muted {
            margin-bottom: 5vh;
        }

        button.btn.btn-login.w-100 {
            width: 100%;
            margin-top: 2vh;
        }

        main.main-content {
            text-align: center;
            width: 50%;
            margin: auto;
            margin: 5vh auto;
        }

        @media screen and (max-width: 992px) {
            main.main-content {
                width: 100%;
            }

        }
    </style>
</head>

<body>

    <?php require('./header.php'); ?>


    <main class="main-content">

        <div class="container">
            <div class="login-container text-center">
                <h2 class="mb-2">Register</h2>
                <p class="text-muted">Create a new account:</p>
                <form method="POST" enctype="multipart/form-data">
                    <div class="floating-label-group text-start">
                        <input name="name" type="text" id="name" class="form-control" placeholder=" " required />
                        <label for="name">Name</label>
                    </div>

                    <div class="floating-label-group text-start">
                        <input name="email" type="email" id="email" class="form-control" placeholder=" " required />
                        <label for="email">E-mail</label>
                    </div>

                    <div class="floating-label-group text-start input-group-custom">
                        <input type="password" id="password" name="password" class="form-control" placeholder=" "
                            required />
                        <label for="password">Password</label>
                        <i class="fa-solid fa-eye input-icon" id="togglePassword"></i>
                    </div>

                    <div class="floating-label-group text-start">
                        <input name="phone" type="text" id="phone" class="form-control" placeholder=" " />
                        <label for="phone">Phone</label>
                    </div>

                    <div class="floating-label-group text-start">
                        <input name="address" type="text" id="address" class="form-control" placeholder=" " />
                        <label for="address">Address</label>
                    </div>

                    <div class="floating-label-group text-start">
                        <input name="city" type="text" id="city" class="form-control" placeholder=" " />
                        <label for="city">City</label>
                    </div>

                    <div class="floating-label-group text-start">
                        <input name="country" type="text" id="country" class="form-control" placeholder=" " />
                        <label for="country">Country</label>
                    </div>

                    <div class="floating-label-group text-start">
                        <input name="profile_image" type="file" id="profile_image" class="form-control" />
                        <label for="profile_image">Profile Image</label>
                    </div>

                    <button type="submit" class="btn btn-login w-100 mt-3">Register</button>

                    <div class="create-account mt-3">
                        Already have an account? <a href="login.php">Login</a>
                    </div>
                </form>
            </div>
        </div>

    </main>


    <?php require('footer.php'); ?>

    <script>
        const togglePassword = document.getElementById("togglePassword");
        const password = document.getElementById("password");
        togglePassword.addEventListener("click", function () {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });
    </script>
</body>

</html>