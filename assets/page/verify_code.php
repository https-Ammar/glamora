<?php
session_start();
require('./db.php');
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';
require './PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendCode($email, $code)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ammar132004@gmail.com';
        $mail->Password = 'urkeowrpkygnyrhl'; // ❗ يفضل تخزينها في ملف .env أو خارج الكود
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('ammar132004@gmail.com', 'GLAMORA');
        $mail->addAddress($email);
        $mail->Subject = 'Verification Code';
        $mail->Body = 'Your verification code is: ' . $code;

        $mail->send();
    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
    }
}

if (!isset($_SESSION['verification_email'])) {
    header("Location: reset_password.php");
    exit();
}

$email = $_SESSION['verification_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        $newCode = strval(rand(100000, 999999));
        $_SESSION['verification_code'] = $newCode;
        sendCode($email, $newCode);
        $success = "A new code has been sent to your email.";
    }

    if (isset($_POST['code'])) {
        $code = trim($_POST['code']);
        if ($code === $_SESSION['verification_code']) {
            header("Location: new_password.php");
            exit();
        } else {
            $error = "Invalid verification code.";
        }
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
                <h2 class="mb-2">Enter Verification Code</h2>
                <p class="text-muted">Enter the 6-digit code sent to your email:</p>
                <form method="POST">
                    <div class="floating-label-group text-start">
                        <input type="text" name="code" id="code" class="form-control" placeholder=" " required />
                        <label for="code">Verification</label>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-login w-100">Submit Code</button>

                    <div class="form-text-link mb-3 mt-3">
                        <a href="login.php">Back to login</a> /

                    </div>
                </form>

                <form method="POST">
                    <button name="resend" class="btn btn-link p-0 m-0 align-baseline"
                        style="font-size: 0.875rem;">Resend
                        Code</button>
                </form>
            </div>
        </div>

    </main>
    <?php require('./footer.php'); ?>
    <script>
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500); // بعد ما يختفي يتم حذفه من الـ DOM
            });
        }, 2000); // بعد 2 ثانية
    </script>

</body>

</html>