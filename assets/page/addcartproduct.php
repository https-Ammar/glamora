<?php
require('db.php');
if(isset($_POST['productid'])){
$productid = $_POST['productid'];
$userid = $_COOKIE['userid'];
$qty = $_POST['qty'];
mysqli_query($conn,"INSERT INTO cart(userid,prouductid,qty) VALUES ($userid,$productid,'$qty')");
}

?>