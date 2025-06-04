<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['user_type']!=='admin'){header('Location: ../dashboard.php');exit();}
include 'db_connect.php';

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['product_id'])){
  $id   =(int)$_POST['product_id'];
  $cat  =$conn->real_escape_string($_POST['category']);
  $name =$conn->real_escape_string($_POST['product_name']);
  $unit =$conn->real_escape_string($_POST['unit']);

  $conn->query("UPDATE products SET category='$cat', product_name='$name', unit='$unit' WHERE id=$id");
  $_SESSION['success']='Product updated.';
}
header('Location: ../products.php');
exit();
?>
