<?php
session_start();
require './db.php';
require './phpmailer/src/Exception.php';
require './phpmailer/src/PHPMailer.php';
require './phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $otp = random_int(100000, 999999);
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $stmt->bind_param("ssi", $otp, $expiry, $user['id']);
            $stmt->execute();

            $_SESSION['verification_code'] = $otp;
            $_SESSION['verification_email'] = $email;

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ammar132004@gmail.com';
            $mail->Password = 'urkeowrpkygnyrhl';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('no-reply@yourdomain.com', 'Glamora');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Glamora - Reset Code';
            $mail->Body = "<p>Hello,</p><p>Your password reset verification code is:</p><h2 style='color:#222;'>$otp</h2><p>This code will expire in 15 minutes.</p>";

            $mail->send();
            header("Location: verify_code.php");
            exit();
        } else {
            $error = "No user found with this email.";
        }
    } else {
        $error = "Invalid email address.";
    }
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
                <h2 class="mb-2">Forgot Password</h2>
                <p class="text-muted">Enter your email to receive a 6-digit code:</p>
                <form method="POST" autocomplete="off">
                    <div class="floating-label-group text-start">
                        <input name="email" type="email" id="email" class="form-control" placeholder=" " required />
                        <label for="email">E-mail</label>
                    </div>
                    <?php if (isset($error)): ?>
                        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-login w-100">Send Code</button>
                    <div class="form-text-link mb-3 mt-3">
                        <a href="login.php">Back to login</a>
                    </div>
                </form>
            </div>
        </div>
    </main>


    <?php require('footer.php'); ?>

</body>

</html>