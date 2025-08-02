<?php
require('./db.php');
$id = $_GET['ids'];
$product1 = mysqli_query($conn, "DELETE FROM ads WHERE id = $id ");
header('location:index.php');
