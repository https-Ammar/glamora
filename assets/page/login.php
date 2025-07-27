<?php
session_start([
    'cookie_lifetime' => 86400, // 24 hours
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

require('./db.php');

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
        $error = "Please enter valid email and password.";
    } else {
        // Modified query to match your actual database columns
        $stmt = $conn->prepare("SELECT id, password, name FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    // Regenerate session ID for security
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name']; // Using the 'name' column instead of first_name/last_name
                    $_SESSION['logged_in'] = true;

                    header('Location: profile.php');
                    exit();
                } else {
                    $error = "Incorrect email or password.";
                }
            } else {
                $error = "Account not found.";
            }
            $stmt->close();
        } else {
            $error = "Database error. Please try again later.";
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
            padding: 27px !important;
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
            transition: background-color 0.3s;
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
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            color: #555;
        }

        h2.mb-2 {
            font-size: 36px;
            font-weight: normal;
        }

        p.text-muted {
            margin-bottom: 5vh;
            color: #6c757d;
        }

        button.btn.btn-login.w-100 {
            width: 100%;
            margin-top: 2vh;
        }

        main.main-content {
            text-align: center;
            width: 50%;
            margin: 5vh auto;
            padding: 20px;
        }

        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
            font-size: 14px;
        }

        @media screen and (max-width: 992px) {
            main.main-content {
                width: 90%;
            }

            .floating-label-group input {
                padding: 20px !important;
            }
        }
    </style>
</head>

<body>
    <?php require('./header.php'); ?>
    <main class="main-content">
        <div class="container">
            <div class="login-container text-center">
                <h2 class="mb-2">Login</h2>
                <p class="text-muted">Please enter your e-mail and password:</p>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="floating-label-group text-start">
                        <input name="email" type="email" id="email" class="form-control" placeholder=" " required
                            autocomplete="email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
                        <label for="email">E-mail</label>
                    </div>
                    <div class="floating-label-group text-start input-group-custom">
                        <input type="password" id="password" name="password" class="form-control" placeholder=" "
                            required autocomplete="current-password" />
                        <label for="password">Password</label>
                        <i class="fa-solid fa-eye input-icon" id="togglePassword"></i>
                    </div>
                    <div class="form-text-link mb-3">
                        <a href="./forgot_password.php">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-login w-100">LOGIN</button>
                    <div class="create-account mt-3">
                        New customer? <a href="register.php">Create an account</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php require('footer.php'); ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const togglePassword = document.getElementById("togglePassword");
            const password = document.getElementById("password");

            if (togglePassword && password) {
                togglePassword.addEventListener("click", function () {
                    const type = password.getAttribute("type") === "password" ? "text" : "password";
                    password.setAttribute("type", type);
                    this.classList.toggle("fa-eye");
                    this.classList.toggle("fa-eye-slash");
                });
            }

            // Focus on email field by default
            const emailField = document.getElementById("email");
            if (emailField) {
                emailField.focus();
            }
        });
    </script>
</body>

</html>