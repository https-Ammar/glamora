<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true,
    'cookie_samesite' => 'Lax'
]);

require('./db.php');

$base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://");
$base_url .= htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8') . "/glamora/";

$page_title = "Error Occurred";
$error_message = $_SESSION['error_message'] ?? "An unexpected error occurred.";
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base_url; ?>">Glamora</a>
        </div>
    </nav>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h1 class="h3 mb-3">Oops! Something went wrong</h1>
                        <p class="lead mb-4"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="<?php echo $base_url; ?>" class="btn btn-primary px-4">
                                <i class="bi bi-house-door me-2"></i>Go Home
                            </a>
                            <button onclick="window.history.back();" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-arrow-left me-2"></i>Go Back
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> Glamora. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>