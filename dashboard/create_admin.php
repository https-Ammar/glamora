<?php
require('./db.php');

$name = "Ammar";
$email = "ammar132004@gmail.com";
$password = "123456"; // غيرها لو تحب
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// check if exists
$stmt = $conn->prepare("SELECT id FROM usersadmin WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "User already exists.";
} else {
    $stmt = $conn->prepare("INSERT INTO usersadmin (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo "Admin user created successfully with password: " . $password;
    } else {
        echo "Error: " . $stmt->error;
    }
}
$stmt->close();
$conn->close();
?>