<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['user_type']!=='admin'){header('Location: ../dashboard.php');exit();}
include 'db_connect.php';

if(isset($_FILES['csv_file'])&&is_uploaded_file($_FILES['csv_file']['tmp_name'])){
  $file=fopen($_FILES['csv_file']['tmp_name'],'r');
  $ok=0;
  while(($data=fgetcsv($file,1000,','))!==false){
    if(count($data)<7) continue;
    [$cat,$cmp,$ctc,$addr,$eml,$ph,$mob]=$data;
    $cat=$conn->real_escape_string(trim($cat));
    $cmp=$conn->real_escape_string(trim($cmp));
    $ctc=$conn->real_escape_string(trim($ctc));
    $addr=$conn->real_escape_string(trim($addr));
    $eml=$conn->real_escape_string(trim($eml));
    $ph =$conn->real_escape_string(trim($ph));
    $mob=$conn->real_escape_string(trim($mob));
    if($cat&&$cmp){
      $conn->query("INSERT INTO suppliers (category,company,contact,address,email,phone,mobile)
                    VALUES ('$cat','$cmp','$ctc','$addr','$eml','$ph','$mob')");
      $ok++;
    }
  }
  fclose($file);
  $_SESSION['success']="$ok suppliers imported.";
}else{
  $_SESSION['error']='No file selected.';
}
header('Location: ../suppliers.php');
exit();
?>
