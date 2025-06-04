<?php
session_start();
include 'db_connect.php';

if (!isset($_POST['start_date'], $_POST['end_date'], $_POST['approver1_id'], $_POST['approver2_id'])) {
  $_SESSION['error'] = 'Missing form data';
  header('Location: ../leave_request.php');
  exit;
}

$user_id      = (int)$_SESSION['user_id'];
$start_date   = $_POST['start_date'];
$end_date     = $_POST['end_date'];
$reason       = trim($_POST['reason']);
$approver1_id = (int)$_POST['approver1_id'];
$approver2_id = (int)$_POST['approver2_id'];

$stmt = $conn->prepare("
  INSERT INTO leave_requests 
  (user_id, approver1_id, approver2_id, start_date, end_date, reason, status)
  VALUES (?, ?, ?, ?, ?, ?, 'Pending-L1')
");

$stmt->bind_param('iiisss', $user_id, $approver1_id, $approver2_id, $start_date, $end_date, $reason);

if ($stmt->execute()) {
  $_SESSION['success'] = 'Leave request submitted';
} else {
  $_SESSION['error'] = 'Failed to submit request';
}

header('Location: ../leave_request.php');
exit;
?>
