<?php
session_start();
require('./db.php');

if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $userId = $_SESSION['userId'];
    $file = $_FILES['image'];
    $target = "uploads/" . basename($file['name']);
    move_uploaded_file($file['tmp_name'], $target);

    $conn->query("UPDATE users SET profile_image = '$target' WHERE id = $userId");
    header("Location: profile.php");
    exit();
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="image" required>
    <button type="submit">Upload</button>
</form>