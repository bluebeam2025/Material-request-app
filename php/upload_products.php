<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['user_type']!=='admin'){header('Location: ../dashboard.php');exit();}
include 'db_connect.php';

if(isset($_FILES['csv_file'])&&is_uploaded_file($_FILES['csv_file']['tmp_name'])){
  $file=fopen($_FILES['csv_file']['tmp_name'],'r');
  $row=0; $ok=0;
  while(($data=fgetcsv($file,1000,','))!==false){
    $row++;
    if(count($data)<3) continue;                      // skip bad line
    [$cat,$name,$unit]=$data;
    $cat =$conn->real_escape_string(trim($cat));
    $name=$conn->real_escape_string(trim($name));
    $unit=$conn->real_escape_string(trim($unit));
    if($cat&&$name&&$unit){
      $conn->query("INSERT INTO products (category,product_name,unit) VALUES ('$cat','$name','$unit')");
      $ok++;
    }
  }
  fclose($file);
  $_SESSION['success']="$ok products imported.";
}else{
  $_SESSION['error']='No file selected.';
}
header('Location: ../products.php');
exit();
?>
