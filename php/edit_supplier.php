<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['user_type']!=='admin'){header('Location: ../dashboard.php');exit();}
include 'db_connect.php';

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['supplier_id'])){
  $id=(int)$_POST['supplier_id'];
  $cat =$conn->real_escape_string($_POST['category']);
  $cmp =$conn->real_escape_string($_POST['company']);
  $ctc =$conn->real_escape_string($_POST['contact']);
  $add =$conn->real_escape_string($_POST['address']);
  $eml =$conn->real_escape_string($_POST['email']);
  $ph  =$conn->real_escape_string($_POST['phone']);
  $mob =$conn->real_escape_string($_POST['mobile']);

  $conn->query("UPDATE suppliers SET category='$cat', company='$cmp', contact='$ctc',
                address='$add', email='$eml', phone='$ph', mobile='$mob' WHERE id=$id");
  $_SESSION['success']='Supplier updated.';
}
header('Location: ../suppliers.php');
exit();
?>
